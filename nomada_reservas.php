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
    <title>Tus Reservas</title>

    <script>
        const MINIO_URL = "<?php echo rtrim($_ENV['MINIO_PUBLIC_URL'] ?? 'http://127.0.0.1:9000', '/'); ?>";
    </script>

    <style>
        body { font-family: 'Nunito', sans-serif; background-color: #E3E1E1; background-size: 65% 80%; background-position: center; background-repeat: no-repeat; opacity: 0.9; padding-bottom: 15%; }
        .btn-primary, .btn-primary:focus { background-color: #00B7CF; border: none; }
        .btn-primary:hover { background-color: #4CCBD4; }
        a, a:visited, a:active { color: black; text-decoration: none; }
        #loading-spinner { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(255, 255, 255, 0.8); z-index: 9999; justify-content: center; align-items: center; flex-direction: column; }
        .spinner-border { width: 3rem; height: 3rem; color: #81ba18 !important; }
        .loading-text { margin-top: 1rem; font-weight: bold; color: #00B7CF; }
        .header-tabs { overflow: hidden; border-radius: 12px; background-color: white; margin-bottom: 1rem; }
        .header-tab { font-weight: bold; transition: all 0.3s ease; height: 100%; cursor: pointer; color: #00B7CF; background-color: white; border-bottom: 3px solid transparent; }
        .header-tab-active { color: white; background-color: #81ba18; border-color: #BDE742; }
        .header-tab-link { text-decoration: none; display: block; height: 100%; }
        .header-tab:hover:not(.header-tab-active) { background-color: #f8f9fa; color: #4CCBD4; border-bottom: 3px solid #E3E1E1; }
        .rounded-start { border-top-left-radius: 12px; border-bottom-left-radius: 12px; }
        .rounded-end { border-top-right-radius: 12px; border-bottom-right-radius: 12px; }
        .img-container { position: relative; overflow: hidden; }
        .img-container::after { content: ''; position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: linear-gradient(45deg, rgba(0, 183, 207, 0.1), rgba(129, 186, 24, 0.1)); pointer-events: none; }
    </style>
</head>

<body>
    <div id="loading-spinner">
        <div class="spinner-border" role="status"></div>
        <p class="loading-text">Cargando tus reservas...</p>
    </div>

    <div class="container" id="container">
        <div class="row py-3 mb-4">
            <div class="col-12">
                <div class="header-tabs shadow-sm">
                    <div class="row g-0">
                        <div class="col-6">
                            <div class="header-tab header-tab-active py-3 text-center rounded-start">
                                <i class="fas fa-calendar-check me-2"></i>RESERVAS
                            </div>
                        </div>
                        <div class="col-6">
                            <a href="nomada_historico.php" class="header-tab-link">
                                <div class="header-tab py-3 text-center rounded-end">
                                    <i class="fas fa-history me-2"></i>HISTÓRICO
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'footerNomada.php'; ?>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
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
                    const establecimientoIds = [...new Set(data.map(reserva => reserva.space.establecimiento.id))];
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
                            Error al cargar las reservas. Por favor, intenta de nuevo más tarde.
                        </div>
                    `;
                });

            function appendData(data, galleryImages) {
                var contenedor = document.getElementById("container");
                const today = new Date().toISOString().split('T')[0];
                const defaultImage = "https://cdn.pixabay.com/photo/2016/11/18/14/05/brick-wall-1834784_960_720.jpg";

                if (data.length === 0) {
                    contenedor.innerHTML += `
                        <div class="alert alert-info mt-3" role="alert">
                            <i class="far fa-calendar-times me-2"></i> No tienes reservas programadas.
                        </div>
                    `;
                    return;
                }

                for (var i = 0; i < data.length; i++) {
                    if (data[i].day >= today && data[i].cancelada == false) {
                        const fecha = new Date(data[i].day);
                        const opciones = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
                        const fechaFormateada = fecha.toLocaleDateString('es-ES', opciones);
                        const fechaFormateadaFinal = fechaFormateada.charAt(0).toUpperCase() + fechaFormateada.slice(1);

                        const establecimientoId = data[i].space.establecimiento.id;
                        let imageUrl = galleryImages[establecimientoId] || defaultImage;
                        
                        // PARCHE MAGICO DE URL CON MINIO
                        if (imageUrl !== defaultImage) {
                            try {
                                let tempUrl = imageUrl.startsWith('http') ? imageUrl : 'http://' + imageUrl;
                                let urlObj = new URL(tempUrl);
                                imageUrl = MINIO_URL + urlObj.pathname;
                            } catch(e) {}
                        }

                        const card = document.createElement("div");
                        card.className = "card reservation-card mb-4 shadow-sm";

                        card.innerHTML = `
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0 fecha-reserva">${fechaFormateadaFinal}</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="img-container" style="background-image: url('${imageUrl}'); 
                                                                        height: 150px; 
                                                                        background-size: cover; 
                                                                        background-position: center;
                                                                        border-radius: 5px;">
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
                                                <span class="badge bg-info text-dark"><i class="far fa-clock me-1"></i> Inicio: ${data[i].start_time.substring(0,5)}</span>
                                                <span class="badge bg-secondary ms-2"><i class="fas fa-hourglass-end me-1"></i> Fin: ${data[i].end_time.substring(0,5)}</span>
                                            </div>
                                            <a href="reservadetalles.php?nombre=${data[i].space.establecimiento.nombre}&direccion=${data[i].space.establecimiento.direccion}&poblacion=${data[i].space.establecimiento.localidad}+, ${data[i].space.establecimiento.provincia}&horaInicio=${data[i].start_time}&horaFinal=${data[i].end_time}&reservaId=${data[i].id}&anfitrionId=${data[i].space.host_id}&Id=${data[i].user_id}&coordinates0=${data[i].space.establecimiento.longitude}&coordinates1=${data[i].space.establecimiento.latitude}" 
                                            class="btn btn-primary">
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
            }
        });
    </script>
</body>
</html>