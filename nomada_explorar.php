<?php
require_once 'verificar_sesion_guest.php';


require './vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$nombreCookie = "mi_cookie_visitas";
if (isset($_COOKIE[$nombreCookie])) {
} else {
    $tiempoExpiracion = time() + 60 * 60 * 24 * 30;
    setcookie($nombreCookie, true, $tiempoExpiracion);
}
/*
if (isset($_POST['cerrar'])) {
    session_unset();
    session_destroy();
    $nombreCookie = "mi_cookie_visitas";
    $tiempoExpiracion = time() - 1;
    setcookie($nombreCookie, "", $tiempoExpiracion);
    header('Location: https://nomadappme.yonomad.app/login.php');
    exit;
}
*/
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://kit.fontawesome.com/b8814a2854.js" crossorigin="anonymous"></script>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.2.1.slim.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.11.0/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-beta/js/bootstrap.min.js"></script>
    <link rel="icon" href="favicon-color.png">
    <link rel="icon" href="favicon-negro.png" media="(prefers-color-scheme: light)">
    <link rel="icon" href="favicon-color.png" media="(prefers-color-scheme: dark)">
    <title>Explorar espacios</title>

    <script>
        const MINIO_URL = "<?php echo rtrim($_ENV['MINIO_PUBLIC_URL'] ?? 'http://127.0.0.1:9000', '/'); ?>";
    </script>

    <style>
        body {
            font-family: 'Nunito', sans-serif;
            background-color: #f4f6f9;
            min-height: 100vh;
            padding-bottom: 80px;
        }

        .page-shell {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 15px;
            box-sizing: border-box;
        }

        .contenedorAnfitriones {
            max-width: 100%;
            margin: 0 auto;
            padding: 0 15px;
        }

        .header {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px 0;
            margin-bottom: 30px;
        }

        .header img {
            height: 45px;
            margin-right: 15px;
        }

        .logo {
            font-size: 1.5rem;
            font-weight: 700;
            color: #333;
        }

        .filters-container {
            background: white;
            border-radius: 20px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .filters-title {
            font-size: 1.2rem;
            font-weight: 700;
            color: #333;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .filter-group {
            margin-bottom: 15px;
        }

        .filter-label {
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
            display: block;
        }

        .filter-select {
            width: 100%;
            padding: 10px 15px;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            background-color: white;
            font-size: 0.9rem;
            transition: border-color 0.3s ease;
        }

        .filter-select:focus {
            outline: none;
            border-color: #28a745;
        }

        .filter-checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
            background-color: #f8f9fa;
            padding: 12px 15px;
            border-radius: 10px;
            border: 2px solid #e9ecef;
            transition: border-color 0.3s ease;
        }

        .filter-checkbox-group:hover {
            border-color: #28a745;
        }

        .filter-checkbox {
            width: 18px;
            height: 18px;
            accent-color: #28a745;
        }

        .filter-checkbox-label {
            margin: 0;
            font-weight: 600;
            color: #333;
            cursor: pointer;
        }

        .clear-filters-btn {
            background-color: #6c757d;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.8rem;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .clear-filters-btn:hover {
            background-color: #5a6268;
            transform: translateY(-2px);
        }

        .results-count {
            color: #6c757d;
            font-size: 0.9rem;
            margin-bottom: 20px;
            text-align: center;
        }

        #contenedor {
            display: grid;
            gap: 20px;
            grid-template-columns: repeat(3, 1fr);
        }

        .anfitrion {
            background-color: white !important;
            border-radius: 20px !important;
            overflow: hidden;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1) !important;
            position: relative;
            height: auto !important;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            margin-bottom: 0 !important;
            flex-grow: 0;
        }

        .page-shell {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 15px;
            box-sizing: border-box;
        }

        @media (max-width: 992px) {
            #contenedor {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        @media (max-width: 768px) {
            #contenedor {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 576px) {
            #contenedor {
                grid-template-columns: 1fr;
            }
        }

        .anfitrion:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.15) !important;
        }

        .gradient {
            background: white;
            padding: 20px;
            position: relative;
        }

        .nombre-anfitrion {
            font-size: 1.2rem;
            font-weight: 700;
            color: #333;
            margin-bottom: 10px;
        }

        .direccion-anfitrion {
            color: #6c757d;
            font-size: 0.9rem;
            margin-bottom: 15px;
        }

        .icons {
            display: flex;
            gap: 15px;
            margin-bottom: 15px;
            color: #28a745;
        }

        .icons i {
            font-size: 1.1rem;
        }

        .star {
            display: flex;
            align-items: center;
            color: #ffc107;
            font-weight: 600;
            font-size: 1rem;
            position: absolute;
            top: 20px;
            right: 20px;
        }

        .star i {
            margin-right: 5px;
        }

        .enlace {
            text-align: right;
            margin-top: 10px;
        }

        .azul {
            display: inline-block;
            color: white !important;
            background-color: #28a745;
            text-decoration: none !important;
            padding: 8px 15px;
            border-radius: 30px;
            font-weight: 600;
            transition: all 0.3s ease;
            font-size: 0.9rem;
        }

        .azul:hover {
            background-color: #218838;
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);
        }

        #loading-spinner {
            padding: 40px;
            text-align: center;
        }

        .spinner-border {
            width: 3rem;
            height: 3rem;
            color: #28a745;
        }

        .anfitrion-img {
            height: 120px;
            background-size: cover;
            background-position: center;
            position: relative;
            border-top-left-radius: 20px;
            border-top-right-radius: 20px;
        }

        .anfitrion-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(to bottom, rgba(0, 0, 0, 0.1), rgba(0, 0, 0, 0.5));
            border-top-left-radius: 20px;
            border-top-right-radius: 20px;
        }

        .image-placeholder {
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #f8f9fa;
            color: #6c757d;
            font-size: 0.9rem;
        }

        .no-results {
            text-align: center;
            padding: 40px 20px;
            color: #6c757d;
        }

        .no-results i {
            font-size: 3rem;
            margin-bottom: 20px;
            color: #dee2e6;
        }
    </style>
</head>

<body>
    <div class="page-shell">
        <div class="contenedorAnfitriones">
            <div class="header hr my-3">
                <img src="img/logoNomada.png" alt="">
                <span class="logo">Encuentra tu espacio</span>
            </div>

            <div class="filters-container">
                <div class="filters-title">
                    <i class="fas fa-filter"></i> Filtrar espacios
                </div>
                <div class="row">
                    <div class="col-md-4">
                        <div class="filter-group">
                            <label class="filter-label">Localidad</label>
                            <select id="filter-localidad" class="filter-select">
                                <option value="">Todas las localidades</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="filter-group">
                            <label class="filter-label">Provincia</label>
                            <select id="filter-provincia" class="filter-select">
                                <option value="">Todas las provincias</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="filter-group">
                            <label class="filter-label">Servicios</label>
                            <div class="row g-2">
                                <div class="col-6">
                                    <div class="filter-checkbox-group">
                                        <input type="checkbox" id="filter-wifi" class="filter-checkbox">
                                        <label for="filter-wifi" class="filter-checkbox-label"><i class="fas fa-wifi"></i>
                                            WiFi</label>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="filter-checkbox-group">
                                        <input type="checkbox" id="filter-parking" class="filter-checkbox">
                                        <label for="filter-parking" class="filter-checkbox-label"><i class="fas fa-car"></i>
                                            Parking</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="text-end mt-3">
                    <button class="clear-filters-btn" onclick="clearAllFilters()">
                        <i class="fas fa-times"></i> Limpiar filtros
                    </button>
                </div>
            </div>

            <div class="results-count" id="results-count" style="display: none;"></div>

            <div id="loading-spinner" class="text-center my-4">
                <div class="spinner-border" role="status">
                    <span class="visually-hidden">Cargando...</span>
                </div>
                <p class="mt-3 fw-bold text-secondary">Cargando espacios...</p>
            </div>

            <div id="contenedor" style="display: none;"></div>

            <?php include 'footerNomada.php'; ?>

        </div>
    </div>

    <script>
        let allAnfitriones = [];
        let filteredAnfitriones = [];

        const url = "./mapaLerrApi.php";

        document.getElementById("loading-spinner").style.display = "block";
        document.getElementById("contenedor").style.display = "none";

        fetch(url)
            .then(response => response.json())
            .then(data => {
                allAnfitriones = data;
                filteredAnfitriones = [...data];

                populateFilters(data);
                appendData(filteredAnfitriones);
                updateResultsCount();

                document.getElementById("loading-spinner").style.display = "none";
                document.getElementById("contenedor").style.display = "grid";
                document.getElementById("results-count").style.display = "block";
            })
            .catch(err => {
                console.log(err);
                document.getElementById("loading-spinner").style.display = "none";
                document.getElementById("contenedor").innerHTML = '<div class="alert alert-danger">Error al cargar los datos. Por favor, inténtalo de nuevo más tarde.</div>';
                document.getElementById("contenedor").style.display = "block";
            });

        function populateFilters(data) {
            const localidades = [...new Set(data.map(item => item.localidad))].sort();
            const provincias = [...new Set(data.map(item => item.provincia || 'Sin provincia'))].sort();

            const localidadSelect = document.getElementById('filter-localidad');
            const provinciaSelect = document.getElementById('filter-provincia');

            localidades.forEach(localidad => {
                if (localidad) localidadSelect.appendChild(new Option(localidad, localidad));
            });

            provincias.forEach(provincia => {
                if (provincia) provinciaSelect.appendChild(new Option(provincia, provincia));
            });
        }

        document.getElementById('filter-localidad').addEventListener('change', applyFilters);
        document.getElementById('filter-provincia').addEventListener('change', applyFilters);
        document.getElementById('filter-wifi').addEventListener('change', applyFilters);
        document.getElementById('filter-parking').addEventListener('change', applyFilters);

        function applyFilters() {
            const localidadFilter = document.getElementById('filter-localidad').value;
            const provinciaFilter = document.getElementById('filter-provincia').value;
            const wifiFilter = document.getElementById('filter-wifi').checked;
            const parkingFilter = document.getElementById('filter-parking').checked;

            filteredAnfitriones = allAnfitriones.filter(anfitrion => {
                let matches = true;
                if (localidadFilter && anfitrion.localidad !== localidadFilter) matches = false;
                if (provinciaFilter && (anfitrion.provincia || 'Sin provincia') !== provinciaFilter) matches = false;
                if (wifiFilter && !anfitrion.has_wifi) matches = false;
                if (parkingFilter && !anfitrion.has_parking) matches = false;
                return matches;
            });

            document.getElementById("contenedor").innerHTML = '';
            if (filteredAnfitriones.length === 0) {
                showNoResults();
            } else {
                appendData(filteredAnfitriones);
            }
            updateResultsCount();
        }

        function clearAllFilters() {
            document.getElementById('filter-localidad').value = '';
            document.getElementById('filter-provincia').value = '';
            document.getElementById('filter-wifi').checked = false;
            document.getElementById('filter-parking').checked = false;

            filteredAnfitriones = [...allAnfitriones];
            document.getElementById("contenedor").innerHTML = '';
            appendData(filteredAnfitriones);
            updateResultsCount();
        }

        function updateResultsCount() {
            const count = filteredAnfitriones.length;
            const total = allAnfitriones.length;
            const resultsElement = document.getElementById('results-count');
            resultsElement.textContent = count === total ? `Mostrando ${total} espacios` : `Mostrando ${count} de ${total} espacios`;
        }

        function showNoResults() {
            const contenedor = document.getElementById("contenedor");
            contenedor.innerHTML = `
                <div class="no-results w-100">
                    <i class="fas fa-search"></i>
                    <h4>No se encontraron espacios</h4>
                    <p>Intenta ajustar los filtros para encontrar más opciones</p>
                </div>
            `;
            contenedor.style.display = 'block';
        }

        function appendData(data) {
            var contenedor = document.getElementById("contenedor");
            contenedor.style.display = 'grid';

            data.forEach(anfitrion => {
                var card = document.createElement("div");
                card.className = "anfitrion";

                var imgContainer = document.createElement("div");
                imgContainer.className = "anfitrion-img";

                // MAGIA DE URLS AQUÍ: Limpiamos la URL y aplicamos la de MINIO_URL inyectada por PHP
                if (anfitrion.imagen) {
                    let cleanUrl = anfitrion.imagen;
                    try {
                        let tempUrl = cleanUrl.startsWith('http') ? cleanUrl : 'http://' + cleanUrl;
                        let urlObj = new URL(tempUrl);
                        cleanUrl = MINIO_URL + urlObj.pathname;
                    } catch (e) {}

                    imgContainer.style.backgroundImage = `url('${cleanUrl}')`;
                } else {
                    imgContainer.style.backgroundImage = "url('img/bricks0.jpg')";
                    imgContainer.classList.add('image-placeholder');
                }

                var overlay = document.createElement("div");
                overlay.className = "anfitrion-overlay";

                var content = document.createElement("div");
                content.className = "gradient";

                var nombre = document.createElement("div");
                nombre.className = "nombre-anfitrion";
                nombre.textContent = anfitrion.nombre;

                var direccion = document.createElement("div");
                direccion.className = "direccion-anfitrion";
                direccion.textContent = anfitrion.direccion + ", " + anfitrion.localidad;

                var iconos = document.createElement("div");
                iconos.className = "icons";

                var iconoEdificio = document.createElement("i");
                iconoEdificio.className = "fas fa-building";
                iconos.appendChild(iconoEdificio);

                if (anfitrion.has_wifi) {
                    var iconoWifi = document.createElement("i");
                    iconoWifi.className = "fas fa-wifi";
                    iconos.appendChild(iconoWifi);
                }

                if (anfitrion.has_parking) {
                    var iconoParking = document.createElement("i");
                    iconoParking.className = "fas fa-car";
                    iconos.appendChild(iconoParking);
                }

                var puntuacion = document.createElement("div");
                puntuacion.className = "star";
                puntuacion.innerHTML = '<i class="fas fa-star"></i> 4.5';

                var enlace = document.createElement("div");
                enlace.className = "enlace";
                enlace.innerHTML = '<a href="anfitrion.php?nombre=' + anfitrion.nombre + '&id=' + anfitrion.id + '&direccion=' + anfitrion.direccion + ", " + anfitrion.localidad + '&coordinates0=' + anfitrion.longitude + '&coordinates1=' + anfitrion.latitude + '&fromIndex=false"><span class="azul">Ver detalles</span></a>';

                card.appendChild(imgContainer);
                imgContainer.appendChild(overlay);
                card.appendChild(content);
                content.appendChild(nombre);
                content.appendChild(direccion);
                content.appendChild(iconos);
                content.appendChild(puntuacion);
                content.appendChild(enlace);

                contenedor.appendChild(card);
            });
        }
    </script>
</body>

</html>