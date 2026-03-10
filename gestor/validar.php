<?php
require_once 'verificar_sesion_gestor.php';

// carga variables de entorno
require '../vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

// obtener id de establecimiento
$id = isset($_GET['id']) ? htmlspecialchars($_GET['id']) : null;
if (!$id) {
    header('Location: verValidar.php');
    exit;
}

// traer datos desde la API
$establecimiento = null;
$url = 'http://' . $_ENV['SERVER_IP'] . ':' . $_ENV['DATABASE_PORT']
    . '/rest/v1/establecimiento?id=eq.' . $id;
$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'apikey: ' . $_ENV['DATABASE_APIKEY'],
        'Authorization: Bearer ' . ($_SESSION['token'] ?? ''),
    ],
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    $data = json_decode($response, true);
    if (is_array($data) && count($data) > 0) {
        $establecimiento = $data[0];
    }
}

if (!$establecimiento) {
    header('Location: verValidar.php');
    exit;
}

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://kit.fontawesome.com/b8814a2854.js" crossorigin="anonymous"></script>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;600;700&display=swap" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src='https://api.mapbox.com/mapbox-gl-js/v2.14.1/mapbox-gl.js'></script>
    <link href='https://api.mapbox.com/mapbox-gl-js/v2.14.1/mapbox-gl.css' rel='stylesheet'>
    <title>Validar - <?php echo htmlspecialchars($establecimiento['nombre']); ?></title>

    <style>
        body {
            font-family: 'Nunito', sans-serif;
            background-color: #f8f9fa;
            padding-bottom: 15%;
        }

        .color-white {
            color: #333;
        }

        .contenedor-principal {
            max-width: 900px;
            margin: 1.5rem auto;
            padding: 0 20px;
        }

        .establecimiento-card {
            background-color: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
            overflow: hidden;
            transition: all 0.3s ease;
            border: 1px solid #e9ecef;
        }

        .card-header {
            position: relative;
            height: 180px;
            background-size: cover;
            background-position: center;
            display: flex;
            align-items: flex-end;
            background-color: #f8f9fa;
            background-image: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .card-header-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(to bottom, rgba(0, 0, 0, 0.1), rgba(0, 0, 0, 0.8));
        }

        .card-title {
            color: white;
            padding: 15px;
            font-weight: 600;
            font-size: 1.4rem;
            position: relative;
            width: 100%;
            z-index: 1;
        }

        .card-body {
            padding: 20px;
        }

        .info-row {
            display: flex;
            align-items: flex-start;
            margin-bottom: 12px;
            gap: 12px;
            font-size: 0.95rem;
        }

        .info-icon {
            color: #28a745;
            width: 20px;
            text-align: center;
            font-size: 1rem;
            flex-shrink: 0;
            margin-top: 2px;
        }

        .btn-actions {
            display: flex;
            gap: 8px;
            margin-top: 20px;
            flex-wrap: wrap;
            border-top: 1px solid #e9ecef;
            padding-top: 15px;
        }

        .btn-action {
            flex: 1;
            border-radius: 8px;
            padding: 0.6rem 1rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            transition: all 0.2s ease;
            color: white !important;
            font-size: 0.9rem;
            min-width: 120px;
        }

        /* Colores específicos para validación */
        .btn-aprobar {
            background-color: #28a745;
            border: none;
        }

        .btn-aprobar:hover {
            background-color: #218838;
            transform: translateY(-1px);
            box-shadow: 0 2px 6px rgba(40, 167, 69, 0.3);
        }

        .btn-aprobar:active {
            background-color: #1e7e34;
            transform: translateY(0);
        }

        .btn-rechazar {
            background-color: #dc3545;
            border: none;
        }

        .btn-rechazar:hover {
            background-color: #c82333;
            transform: translateY(-1px);
            box-shadow: 0 2px 6px rgba(220, 53, 69, 0.3);
        }

        .btn-rechazar:active {
            background-color: #bd2130;
            transform: translateY(0);
        }

        /* Estados después de validar/rechazar */
        .btn-action.processing {
            opacity: 0.8;
            pointer-events: none;
        }

        .btn-action.processing .btn-text::after {
            content: " ...";
            animation: dots 1s infinite;
        }

        @keyframes dots {
            0% {
                content: " .";
            }

            33% {
                content: " ..";
            }

            66% {
                content: " ...";
            }
        }

        .establecimiento-card.validado {
            border-left: 4px solid #28a745;
        }

        .establecimiento-card.rechazado {
            border-left: 4px solid #dc3545;
            opacity: 0.8;
        }

        .validation-status-badge {
            margin-bottom: 1rem;
            padding: 0.75rem;
            border-radius: 8px;
            font-weight: 600;
            display: none;
            animation: slideDown 0.3s ease;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .validation-status-badge.show {
            display: block;
        }

        .validation-status-badge.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .validation-status-badge.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .btn-volver {
            background-color: #6c757d;
            border: none;
        }

        .btn-volver:hover {
            background-color: #5a6268;
            transform: translateY(-1px);
            box-shadow: 0 2px 6px rgba(108, 117, 125, 0.3);
        }

        .btn-volver:active {
            background-color: #545b62;
            transform: translateY(0);
        }

        #establecimiento-main {
            width: 100%;
            max-width: 900px;
            margin: 0 auto;
        }

        .map-container {
            height: 300px;
            border-radius: 8px;
            overflow: hidden;
            margin: 12px 0;
            border: 1px solid #dee2e6;
        }

        .badge {
            font-size: 0.8rem;
            padding: 0.4rem 0.8rem;
        }

        /* ESTILOS DEL FOOTER COPIADOS DE TU CÓDIGO */
        .footer {
            color: black;
            background-color: white;
            width: 100%;
            user-select: none;
            bottom: 0;
            font-size: 15px;
            background: #E3E1E1;
            text-align: center;
            position: fixed;
            z-index: 1000;
        }

        .footer-container {
            background-color: white;
            box-shadow: 0px -2px 10px rgba(0, 0, 0, 0.1);
            padding-top: 1px !important;
            padding-bottom: 1px !important;
            height: auto;
            z-index: 1001;
        }

        .footer-item {
            padding: 8px 0;
            -webkit-tap-highlight-color: transparent;
        }

        .icon-container {
            transition: transform 0.3s ease;
            padding: 5px 0;
        }

        .footer-item:hover .icon-container {
            transform: translateY(-7px);
        }

        .footer-item:active .icon-container {
            transform: translateY(0);
        }

        .footer-item:focus .icon-container {
            transform: translateY(0);
        }

        a,
        a:visited,
        a:active {
            color: inherit;
            text-decoration: none;
        }

        /* Active state para el menú "Validar" */
        #lbl_val .icon-container {
            color: #007bff;
        }

        #lbl_val {
            color: #00B7CF !important;
        }
    </style>
</head>

<body>

    <header>
        <div class="container-fluid info text-center mt-3">
            <div class="row">
                <div class="col color-white h2 fw-bold pt-3 pb-2">
                    Detalle de Validación
                </div>
            </div>
        </div>
    </header>

    <div class="container-fluid pb-5">
        <div id="establecimiento-main">
            <div class="establecimiento-card">

                <div class="card-header" style="background-image: url('<?php echo isset($establecimiento['image_url']) ? 'http://' . $establecimiento['image_url'] : '../img/default.jpg'; ?>');">
                    <div class="card-header-overlay"></div>
                    <div class="card-title">
                        <div><?php echo htmlspecialchars($establecimiento['nombre']); ?></div>
                        <span class="badge bg-warning text-dark fs-6"><?php echo isset($establecimiento['estaValidado']) && $establecimiento['estaValidado'] ? 'Validado' : 'Pendiente'; ?></span>
                    </div>
                </div>

                <div id="validation-status-badge" class="validation-status-badge"></div>

                <div class="card-body">
                    <div class="info-row">
                        <div class="info-icon"><i class="fas fa-align-left"></i></div>
                        <div><strong>Descripción:</strong> <br><?php echo nl2br(htmlspecialchars($establecimiento['descripcion'] ?? 'No especificada')); ?></div>
                    </div>

                    <div class="info-row">
                        <div class="info-icon"><i class="fas fa-map-marker-alt"></i></div>
                        <div><strong>Dirección:</strong> <br><?php echo htmlspecialchars($establecimiento['direccion'] ?? 'No especificada'); ?></div>
                    </div>

                    <div class="info-row">
                        <div class="info-icon"><i class="fas fa-city"></i></div>
                        <div><strong>Localidad:</strong> <?php echo htmlspecialchars($establecimiento['localidad'] ?? ''); ?> (<?php echo htmlspecialchars($establecimiento['codigo_postal'] ?? ''); ?>)</div>
                    </div>

                    <div class="info-row mt-4">
                        <div class="info-icon"><i class="fas fa-map"></i></div>
                        <div><strong>Ubicación en el mapa:</strong></div>
                    </div>
                    <div class="map-container" id="map-validacion">
                        <div class="d-flex justify-content-center align-items-center h-100 bg-light text-muted">
                            Mapa de Mapbox (Requiere JS)
                        </div>
                    </div>

                    <div class="btn-actions" id="btn-actions">
                        <a href="verValidar.php" class="btn btn-action btn-volver">
                            <i class="fas fa-arrow-left"></i> Volver
                        </a>

                        <button type="button" class="btn btn-action btn-aprobar" id="btn-aprobar" data-action="aprobar" data-id="<?php echo $establecimiento['id']; ?>">
                            <i class="fas fa-check-circle"></i> <span class="btn-text">Validar</span>
                        </button>

                        <button type="button" class="btn btn-action btn-rechazar" id="btn-rechazar" data-action="rechazar" data-id="<?php echo $establecimiento['id']; ?>">
                            <i class="fas fa-times-circle"></i> <span class="btn-text">Rechazar</span>
                        </button>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <div class="container-fluid footer mt-5 p-3">
        <div class="row text-center fixed-bottom bg-blanco pt-1 px-2 footer-container">
            <label id="lbl_anf" class="col-2 text-center footer-item">
                <div class="row">
                    <a href="Anfitriones.php">
                        <div class="col-12 icon-container">
                            <i class="h2 fas fa-users p-1 m-0"></i>
                            <div>Anfitriones</div>
                        </div>
                    </a>
                </div>
            </label>

            <label id="lbl_val" class="col-2 text-center footer-item">
                <div class="row">
                    <a href="verValidar.php">
                        <div class="col-12 icon-container">
                            <i class="h2 fas fa-check-circle p-1 m-0"></i>
                            <div>Validar</div>
                        </div>
                    </a>
                </div>
            </label>

            <label id="lbl_res" class="col-2 text-center footer-item">
                <div class="row">
                    <a href="verReservas.php">
                        <div class="col-12 icon-container">
                            <i class="h2 fas fa-book-open p-1 m-0"></i>
                            <div>Reservas</div>
                        </div>
                    </a>
                </div>
            </label>
            <label id="lbl_his" class="col-2 text-center footer-item">
                <div class="row">
                    <a href="verEstablecimientos.php">
                        <div class="col-12 icon-container">
                            <i class="h2 fas fa-building p-1 m-0"></i>
                            <div>Establecimientos</div>
                        </div>
                    </a>
                </div>
            </label>
            <label id="lbl_esp" class="col-2 text-center footer-item">
                <div class="row">
                    <a href="verEspacios.php">
                        <div class="col-12 icon-container">
                            <i class="h2 fas fa-chair p-1 m-0"></i>
                            <div>Espacios</div>
                        </div>
                    </a>
                </div>
            </label>
            <label id="lbl_per" class="col-2 text-center footer-item">
                <div class="row">
                    <a href="tuPerfil.php">
                        <div class="col-12 icon-container">
                            <i class="h2 fas fa-user-tie p-1 m-0"></i>
                            <div>Perfil</div>
                        </div>
                    </a>
                </div>
            </label>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const btnAprobar = document.getElementById('btn-aprobar');
            const btnRechazar = document.getElementById('btn-rechazar');
            const card = document.querySelector('.establecimiento-card');
            const statusBadge = document.getElementById('validation-status-badge');
            const btnActions = document.getElementById('btn-actions');

            function performValidation(action) {
                if (!confirm(action === 'aprobar' ?
                        '¿Aprobar y validar este establecimiento?' :
                        '¿Rechazar este establecimiento? No podrá ser publicado.')) {
                    return;
                }

                const id = btnAprobar.dataset.id;
                const button = action === 'aprobar' ? btnAprobar : btnRechazar;

                // Mostrar estado de procesamiento
                button.classList.add('processing');
                button.disabled = true;

                // Hacer petición AJAX
                fetch(`procesar_validacion.php?id=${id}&accion=${action}&ajax=1`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Éxito: mostrar mensaje y cambiar estilos
                            statusBadge.classList.add('show', 'success');
                            statusBadge.innerHTML = `<i class="fas fa-${action === 'aprobar' ? 'check' : 'times'}-circle me-2"></i>${data.message}`;

                            if (action === 'aprobar') {
                                card.classList.add('validado');
                            } else {
                                card.classList.add('rechazado');
                            }

                            // Deshabilitar ambos botones después de validar
                            btnAprobar.disabled = true;
                            btnRechazar.disabled = true;
                            btnAprobar.classList.remove('processing');
                            btnRechazar.classList.remove('processing');
                            btnAprobar.style.opacity = '0.5';
                            btnRechazar.style.opacity = '0.5';

                            // Redirigir después de 2 segundos
                            setTimeout(() => {
                                window.location.href = 'verValidar.php';
                            }, 2000);
                        } else {
                            // Error
                            statusBadge.classList.add('show', 'error');
                            statusBadge.innerHTML = `<i class="fas fa-exclamation-circle me-2"></i>${data.error}`;
                            button.classList.remove('processing');
                            button.disabled = false;
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        statusBadge.classList.add('show', 'error');
                        statusBadge.innerHTML = `<i class="fas fa-exclamation-circle me-2"></i>Error en la conexión`;
                        button.classList.remove('processing');
                        button.disabled = false;
                    });
            }

            btnAprobar.addEventListener('click', function() {
                performValidation('aprobar');
            });

            btnRechazar.addEventListener('click', function() {
                performValidation('rechazar');
            });
        });
    </script>

</body>

</html>