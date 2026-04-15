<?php
require_once 'verificar_sesion_guest.php';

require './vendor/autoload.php';
use Dotenv\Dotenv;
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://kit.fontawesome.com/b8814a2854.js" crossorigin="anonymous"></script>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@200;400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="icon" href="favicon-color.png">
    <title>Reservas Históricas</title>

    <script>
        const MINIO_URL = "<?php echo rtrim($_ENV['MINIO_PUBLIC_URL'] ?? 'http://127.0.0.1:9000', '/'); ?>";
    </script>

    <style>
        body {
            font-family: 'Nunito', sans-serif;
            background-color: #f4f6f9;
            background-size: 65% 80%;
            background-position: center;
            background-repeat: no-repeat;
            opacity: 0.9;
            padding-bottom: 15%;
        }

        .btn-secondary {
            background-color: #6c757d !important;
            color: white !important;
            border: none;
            font-weight: bold;
        }

        .btn-secondary:hover {
            background-color: #5a6268 !important;
        }

        a,
        a:visited,
        a:active {
            color: black;
            text-decoration: none;
        }

        #loading-spinner {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(255, 255, 255, 0.8);
            z-index: 9999;
            justify-content: center;
            align-items: center;
            flex-direction: column;
        }

        .spinner-border {
            width: 3rem;
            height: 3rem;
            color: #81ba18 !important;
        }

        .loading-text {
            margin-top: 1rem;
            font-weight: bold;
            color: #00B7CF;
        }

        .card-header.bg-secondary {
            background-color: #6c757d !important;
        }

        .header-tabs {
            overflow: hidden;
            border-radius: 12px;
            background-color: white;
            margin-bottom: 1rem;
        }

        .header-tab {
            font-weight: bold;
            transition: all 0.3s ease;
            height: 100%;
            cursor: pointer;
            color: #00B7CF;
            background-color: white;
            border-bottom: 3px solid transparent;
        }

        .header-tab-active {
            color: white;
            background-color: #81ba18;
            border-color: #BDE742;
        }

        .header-tab-link {
            text-decoration: none;
            display: block;
            height: 100%;
        }

        .header-tab:hover:not(.header-tab-active) {
            background-color: #f8f9fa;
            color: #4CCBD4;
            border-bottom: 3px solid #E3E1E1;
        }

        .rounded-start {
            border-top-left-radius: 12px;
            border-bottom-left-radius: 12px;
        }

        .rounded-end {
            border-top-right-radius: 12px;
            border-bottom-right-radius: 12px;
        }

        .img-container {
            position: relative;
            overflow: hidden;
        }

        .img-container::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(45deg, rgba(0, 183, 207, 0.1), rgba(129, 186, 24, 0.1));
            pointer-events: none;
        }

        .page-shell {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 15px;
            box-sizing: border-box;
        }
    </style>
</head>

<body>
    <div id="loading-spinner">
        <div class="spinner-border" role="status"></div>
        <p class="loading-text">Cargando tu historial de reservas...</p>
    </div>

    <div class="page-shell">
        <?php include 'headerNomada.php'; ?>
    </div>

    <?php include 'footerNomada.php'; ?>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.getElementById('loading-spinner').style.display = 'flex';
            const url = "getReservasNomada.php";

            fetch(url)
                .then(response => response.json())
                .then(data => {
                    if (data.length === 0) {
                        document.getElementById('loading-spinner').style.display = 'none';
                        appendData(data, {});
                        return;
                    }

                    const today = new Date().toISOString().split('T')[0];
                    const reservasHistoricas = data.filter(reserva => reserva.day < today || reserva.cancelada == true);

                    if (reservasHistoricas.length === 0) {
                        document.getElementById('loading-spinner').style.display = 'none';
                        appendData(data, {});
                        return;
                    }

                    const establecimientoIds = [...new Set(reservasHistoricas.map(reserva => reserva.space.establecimiento.id))];

                    return fetch(`getGalleryImages.php?ids=${establecimientoIds.join(',')}`)
                        .then(response => response.json())
                        .then(galleryImages => {
                            document.getElementById('loading-spinner').style.display = 'none';
                            appendData(data, galleryImages);
                        });
                })
                .catch(err => {
                    document.getElementById('loading-spinner').style.display = 'none';
                    document.getElementById('container').innerHTML += `
                            <div class="alert alert-danger mt-3" role="alert">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                Error al cargar el historial de reservas. Por favor, intenta de nuevo más tarde.
                            </div>
                        `;
                });

            function appendData(data, galleryImages) {
                var contenedor = document.getElementById("container");
                const today = new Date().toISOString().split('T')[0];
                const defaultImage = "https://cdn.pixabay.com/photo/2016/11/18/14/05/brick-wall-1834784_960_720.jpg";
                let reservasHistoricas = 0;

                for (var i = data.length - 1; i >= 0; i--) {
                    if (data[i].day < today || data[i].cancelada == true) {
                        reservasHistoricas++;
                        const fecha = new Date(data[i].day);
                        const opciones = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
                        const fechaFormateada = fecha.toLocaleDateString('es-ES', opciones);
                        const fechaFormateadaFinal = fechaFormateada.charAt(0).toUpperCase() + fechaFormateada.slice(1);

                        const establecimientoId = data[i].space.establecimiento.id;
                        let imageUrl = galleryImages[establecimientoId] || defaultImage;

                        // PARCHE MAGICO DE URL
                        if (imageUrl !== defaultImage) {
                            try {
                                let tempUrl = imageUrl.startsWith('http') ? imageUrl : 'http://' + imageUrl;
                                let urlObj = new URL(tempUrl);
                                imageUrl = MINIO_URL + urlObj.pathname;
                            } catch (e) { }
                        }

                        const card = document.createElement("div");
                        card.className = "card reservation-card mb-4 shadow-sm";

                        card.innerHTML = `
                            <div class="card-header bg-secondary text-white">
                                <h5 class="mb-0 fecha-reserva">${fechaFormateadaFinal}</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="img-container" style="background-image: url('${imageUrl}'); 
                                                                        height: 150px; 
                                                                        background-size: cover; 
                                                                        background-position: center;
                                                                        border-radius: 5px;
                                                                        opacity: 0.7;">
                                        </div>
                                    </div>
                                    <div class="col-md-8">
                                        <h4 class="card-title">${data[i].space.establecimiento.nombre}</h4>
                                        <p class="card-text">
                                            <i class="fas fa-map-marker-alt text-danger me-2"></i>
                                            ${data[i].space.establecimiento.direccion}<br>
                                            <span class="ms-4">${data[i].space.establecimiento.localidad}, ${data[i].space.establecimiento.provincia}</span>
                                        </p>
                                        <div class="d-flex justify-content-between align-items-center mt-3">
                                            <div class="horario">
                                                <span class="badge bg-info text-dark"><i class="far fa-clock me-1"></i> Inicio: ${data[i].start_time.substring(0, 5)}</span>
                                                <span class="badge bg-secondary ms-2"><i class="fas fa-hourglass-end me-1"></i> Fin: ${data[i].end_time.substring(0, 5)}</span>
                                            </div>
                                            <a href="reservadetalles.php?nombre=${data[i].space.establecimiento.nombre}&direccion=${data[i].space.establecimiento.direccion}&poblacion=${data[i].space.establecimiento.localidad}+, ${data[i].space.establecimiento.provincia}&horaInicio=${data[i].start_time}&horaFinal=${data[i].end_time}&reservaId=${data[i].id}&anfitrionId=${data[i].space.host_id}&Id=${data[i].user_id}&coordinates0=${data[i].space.establecimiento.longitude}&coordinates1=${data[i].space.establecimiento.latitude}" 
                                            class="btn btn-secondary">
                                                <i class="fas fa-info-circle me-1"></i> Ver detalles
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `;

                        contenedor.appendChild(card);
                    }
                }

                if (reservasHistoricas === 0) {
                    contenedor.innerHTML += `
                        <div class="alert alert-info mt-3" role="alert">
                            <i class="far fa-calendar-times me-2"></i> No tienes reservas en tu historial.
                        </div>
                    `;
                }
            }
        });
    </script>
</body>

</html>