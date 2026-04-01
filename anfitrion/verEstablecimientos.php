<?php
require_once 'verificar_sesion_host.php';

require '../vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

/*
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
*/

//Control de creacion de establecimientos
//Obtencion del plan
$url = "http://" . $_ENV['SERVER_IP'] . ":" . $_ENV['DATABASE_PORT'] . "/rest/v1/host?id=eq." . $_SESSION['user_id'];

$ch = curl_init($url);
curl_setopt_array($ch, array(
    CURLOPT_CUSTOMREQUEST => "GET",
    CURLOPT_HTTPHEADER => array(
        'Content-Type: application/json',
        'apikey: ' . $_ENV['DATABASE_APIKEY']
    ),
    CURLOPT_RETURNTRANSFER => true,
));

$resultado = curl_exec($ch);
$codigoRespuesta = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($codigoRespuesta === 200) {
    $datos = json_decode($resultado, true);
    if (count($datos) > 0) {

        $plan = $datos[0]['plan'];
    }
}

$url = "http://" . $_ENV['SERVER_IP'] . ":" . $_ENV['DATABASE_PORT'] . "/rest/v1/establecimiento?host_id=eq." . $_SESSION['user_id'];

$ch = curl_init($url);
curl_setopt_array($ch, array(
    CURLOPT_CUSTOMREQUEST => "GET",
    CURLOPT_HTTPHEADER => array(
        'Content-Type: application/json',
        'apikey: ' . $_ENV['DATABASE_APIKEY']
    ),
    CURLOPT_RETURNTRANSFER => true,
));

$response = curl_exec($ch);
curl_close($ch);

$establecimientosData = json_decode($response, true);

$num_establecimientos = count($establecimientosData);

$limites = [
    'Basico' => 1,
    'Pro' => 2,
    'Premium' => PHP_INT_MAX // ilimitado
];

$mostrarMensajeLimite = false;
if ($num_establecimientos >= $limites[$plan]) {
    $mostrarMensajeLimite = true;
}


$curl = curl_init();
$url = "http://" . $_ENV['SERVER_IP'] . ":" . $_ENV['DATABASE_PORT'] . "/rest/v1/establecimiento?host_id=eq." . $_SESSION['user_id'];

curl_setopt_array($curl, [
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        "Content-Type: application/json",
        "apikey: " . $_ENV['DATABASE_APIKEY'],
        "Authorization: Bearer " . $_SESSION['token'],
    ]
]);

$response = curl_exec($curl);
$establecimientos = json_decode($response, true);
if (!$establecimientos || curl_error($curl)) {
    $establecimientos = [];
}

curl_close($curl);

if (!empty($establecimientos)) {
    foreach ($establecimientos as &$establecimiento) {
        $curl_gallery = curl_init();
        $gallery_url = "http://" . $_ENV['SERVER_IP'] . ":" . $_ENV['DATABASE_PORT'] . "/rest/v1/gallery?establecimiento_id=eq." . $establecimiento['id'] . "&limit=1";

        curl_setopt_array($curl_gallery, [
            CURLOPT_URL => $gallery_url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                "Content-Type: application/json",
                "apikey: " . $_ENV['DATABASE_APIKEY'],
                "Authorization: Bearer " . $_SESSION['token'],
            ]
        ]);

        $gallery_response = curl_exec($curl_gallery);
        $gallery_data = json_decode($gallery_response, true);

        // Asignar la primera imagen o la imagen por defecto
        if (!empty($gallery_data) && !curl_error($curl_gallery)) {
            $establecimiento['image_url'] = $gallery_data[0]['image_url'];
?> <script>
                console.log("<?php echo $establecimiento['image_url'] ?>")
            </script> <?php
                    } else {
                        $establecimiento['image_url'] = "../img/bricks0.jpg";
                    }


                    curl_close($curl_gallery);
                }
                unset($establecimiento); // Romper la referencia
            }

            if (!$establecimientos || curl_error($curl)) {
                $establecimientos = [];
            }

            if (!$establecimientos || curl_error($curl)) {
                $establecimientos = [];
            }

            function formatearDireccion($direccion, $piso = "")
            {
                if (!empty($piso)) {
                    $direccion .= ", $piso";
                }
                return $direccion;

            }

            $backgroundImages = [
                "../img/bricks0.jpg"
            ];


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
    <link href="style.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src='https://api.mapbox.com/mapbox-gl-js/v2.14.1/mapbox-gl.js'></script>
    <link href='https://api.mapbox.com/mapbox-gl-js/v2.14.1/mapbox-gl.css' rel='stylesheet'>
    <link rel="icon" href="../favicon-color.png">
    <link rel="icon" href="../favicon-negro.png" media="(prefers-color-scheme: light)">
    <link rel="icon" href="../favicon-color.png" media="(prefers-color-scheme: dark)">
    <title>Mis Establecimientos</title>
    <style>
        :root {
            --host-accent: #10bfeb;
            --host-accent-dark: #0a95b7;
            --host-accent-soft: #e7f8fd;
            --header-active-green: #81ba18;
            --header-active-green-dark: #6d9e14;
        }

        body {
            font-family: 'Nunito', sans-serif;
            padding-bottom: 15%;
        }

        .page-shell {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 15px;
            box-sizing: border-box;
        }

        .page-hero {
            max-width: 100%;
            margin: 1.2rem 0 0.5rem;
            padding: 0;
            box-sizing: border-box;
        }

        .page-hero-inner {
            border-radius: 20px;
            background: linear-gradient(135deg, var(--host-accent-dark) 0%, var(--host-accent) 62%, #51cfee 100%);
            color: #ffffff;
            padding: 1.1rem 1.2rem;
            box-shadow: 0 18px 40px rgba(16, 191, 235, 0.28);
            border: 1px solid rgba(255, 255, 255, 0.18);
        }

        .page-hero-title {
            font-size: 1.35rem;
            font-weight: 800;
            letter-spacing: 0.2px;
        }

        .hero-title-row {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .contenedor-principal {
            max-width: 1400px;
            margin: 2rem auto;
            padding: 0 15px;
        }

        .header-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin: 1.5rem 0;
        }

        .btn-add {
            background-color: #28a745;
            border: none;
            font-weight: 600;
            padding: 0.75rem 1.5rem;
            border-radius: 50px;
            transition: all 0.3s;
            display: flex;
            width: 100%;
            max-width: 650px;
            justify-content: center;
            align-items: center;
        }

        .btn-add:hover {
            background-color: #218838;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .establecimiento-card {
            background-color: white;
            border-radius: 15px;
            box-shadow: 0 .5rem 1rem rgba(0, 0, 0, .15);
            margin-bottom: 2rem;
            overflow: hidden;
            transition: all 0.3s;
        }

        .establecimiento-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 1rem 2rem rgba(0, 0, 0, .2);
        }

        .card-header {
            position: relative;
            height: 280px;
            background-size: cover;
            background-position: center;
            display: flex;
            align-items: flex-end;
        }

        .card-header-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(to bottom, rgba(0, 0, 0, 0.1), rgba(0, 0, 0, 0.7));
        }

        .card-title {
            color: white;
            padding: 20px;
            font-weight: 700;
            font-size: 1.5rem;
            position: relative;
            width: 100%;
            z-index: 1;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .service-icons {
            display: flex;
            gap: 15px;
        }

        .service-icon {
            background-color: rgba(255, 255, 255, 0.9);
            color: #333;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
        }

        .card-body {
            padding: 20px;
        }

        .info-row {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
            gap: 10px;
        }

        .info-icon {
            color: #28a745;
            width: 20px;
            text-align: center;
        }

        .collapsed-content {
            display: none;
            padding-top: 15px;
            border-top: 1px solid #e9ecef;
            margin-top: 15px;
        }

        .btn-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
            flex-wrap: wrap;
        }

        .btn-action {
            flex: 1;
            border-radius: 10px;
            padding: 0.5rem 1rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
            transition: all 0.3s;
        }

        .btn-toggle {
            background-color: #f8f9fa;
            border: 1px solid #ced4da;
            color: #6c757d;
            width: 100%;
            margin-bottom: 15px;
            border-radius: 10px;
            padding: 8px;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
            transition: all 0.3s;
        }

        .btn-toggle:hover {
            background-color: #e9ecef;
        }

        .btn-spaces {
            background-color: #a4a4a4;
            border: none;
            color: black;
        }

        .btn-spaces:hover {
            background-color: #8f8f8f;
        }

        .btn-edit {
            background-color: #17a2b8;
            border: none;
        }

        .btn-edit:hover {
            background-color: #138496;
        }

        .btn-delete {
            background-color: #dc3545;
            border: none;
            color: black;
        }

        .btn-delete:hover {
            background-color: #c82333;
        }

        .map-container {
            height: 400px;
            border-radius: 10px;
            overflow: hidden;
            margin: 15px 0;
        }

        .no-establecimientos {
            background-color: white;
            border-radius: 15px;
            box-shadow: 0 .5rem 1rem rgba(0, 0, 0, .15);
            padding: 2rem;
            text-align: center;
        }

        .precio-tag {
            background-color: #28a745;
            color: white;
            border-radius: 50px;
            padding: 5px 10px;
            font-size: 0.9rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            margin-left: 10px;
        }

        .modal-confirm {
            color: #636363;
        }

        .modal-confirm .modal-content {
            padding: 20px;
            border-radius: 15px;
            border: none;
        }

        .modal-confirm .modal-header {
            border-bottom: none;
            position: relative;
            text-align: center;
            margin: -20px -20px 0;
            border-top-left-radius: 15px;
            border-top-right-radius: 15px;
            padding: 35px;
        }

        .modal-confirm .modal-header.delete {
            background-color: #f7d7db;
        }

        .modal-confirm h4 {
            text-align: center;
            font-size: 26px;
            margin: 30px 0 -15px;
            color: #333;
        }

        .modal-confirm .form-control,
        .modal-confirm .btn {
            min-height: 40px;
            border-radius: 10px;
        }

        .modal-confirm .close {
            position: absolute;
            top: 15px;
            right: 15px;
            font-size: 24px;
            font-weight: bold;
            color: #999;
            opacity: 1;
        }

        .modal-confirm .modal-footer {
            border: none;
            text-align: center;
            border-radius: 15px;
            padding: 10px 15px 25px;
            justify-content: center;
        }

        .modal-confirm .icon-box {
            color: #fff;
            position: absolute;
            margin: 0 auto;
            left: 0;
            right: 0;
            top: -70px;
            width: 95px;
            height: 95px;
            border-radius: 50%;
            background-color: #f15e5e;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9;
            box-shadow: 0px 2px 2px rgba(0, 0, 0, 0.1);
        }

        .modal-confirm .icon-box i {
            font-size: 58px;
        }

        .modal-confirm.modal-dialog {
            margin-top: 80px;
        }

        .trigger-btn {
            display: inline-block;
            margin: 100px auto;
        }

        .spinner-container {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 200px;
        }

        .spinner {
            width: 60px;
            height: 60px;
        }

        @media (max-width: 767px) {

            .service-icons {
                align-self: flex-end;
            }

            .btn-actions {
                flex-direction: column;
            }

            .btn-action {
                width: 100%;
            }

            .header-container {
                flex-direction: column;
                align-items: stretch;
                gap: 15px;
            }

            .header-container h1 {
                text-align: center;
            }
        }

        #establecimiento-main {
            width: 100%;
            max-width: 650px;
            margin: 0 auto;
        }

        .row {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
        }

        body {
            padding-bottom: 15%;
        }

        label,
        .form-check input[type=checkbox] {
            position: static;
        }

        #res:checked~#lbl_res,
        #his:checked~#lbl_his,
        #esp:checked~#lbl_esp,
        #per:checked~#lbl_per {
            color: #00B7CF !important;
        }

        a,
        a:visited,
        a:active {
            color: black;
            text-decoration: none;
        }

        .fecha {
            border-radius: 0.5rem;
        }

        .espacio {
            border-radius: 1rem;
            background: #f3f3f3ff;
        }

        .hora {
            color: #00B7CF;
        }

        .spinner-border {
            color: #1976d2;
        }

        .mensaje-limite {
            background-color: #fff3cd;
            /* Fondo amarillo claro */
            border: 1px solid #ffeeba;
            /* Borde amarillo más oscuro */
            color: #856404;
            /* Texto marrón oscuro para contraste */
            padding: 15px;
            /* Espaciado interno */
            margin: 20px auto;
            /* Margen superior e inferior, y centrado horizontal */
            border-radius: 8px;
            /* Bordes ligeramente redondeados */
            text-align: center;
            /* Texto centrado */
            /* max-width: 650px; */
            /* Ancho máximo para el mensaje */
            font-size: 1rem;
            /* Tamaño de fuente */
            line-height: 1.5;
            /* Altura de línea */
        }

        .mensaje-limite a {
            color: #0056b3;
            /* Color azul para el enlace dentro del mensaje */
            font-weight: bold;
            /* Texto del enlace en negrita */
            text-decoration: underline;
            /* Subrayado del enlace */
        }

        .btn-add:disabled {
            background-color: #cccccc;
            /* Fondo gris claro */
            cursor: not-allowed;
            /* Cursor de "no permitido" */
            transform: none;
            /* Elimina la transformación al pasar el ratón */
            box-shadow: none;
            /* Elimina la sombra al pasar el ratón */
        }

        .btn-add:active {
            background-color: #cccccc;
            /* Fondo gris claro */
            cursor: not-allowed;
            /* Cursor de "no permitido" */
            transform: none;
            /* Elimina la transformación al pasar el ratón */
            box-shadow: none;
            /* Elimina la sombra al pasar el ratón */
        }

        #per:checked~#lbl_per .icon-container,
        #res:checked~#lbl_res .icon-container,
        #his:checked~#lbl_his .icon-container,
        #esp:checked~#lbl_esp .icon-container {
            color: #007bff;
        }
    </style>
</head>

<body>

    <div class="page-shell">

        <header class="page-hero">
            <div class="page-hero-inner">
                <div class="hero-title-row">
                    <div class="page-hero-title"><i class="fas fa-building me-2"></i>Tus Establecimientos</div>
                </div>
            </div>
        </header>

        <?php if ($mostrarMensajeLimite): ?>
            <div class="mensaje-limite">
                Ha alcanzado el número máximo de establecimientos para su suscripción.
                Para mejorar su plan <a href="Suscripciones.php">pulse aquí</a>.
            </div>
        <?php endif; ?>

        <div class="header-container">
            <?php if ($mostrarMensajeLimite): ?>
                <form action="#">
                    <button type="submit" class="btn btn-add text-white" disabled>
                        <i class="fas fa-plus me-2"></i> Añadir Establecimiento
                    </button>
                </form>
            <?php else: ?>
                <form action="anadirEstablecimiento.php" method="get">
                    <button type="submit" class="btn btn-add text-white">
                        <i class="fas fa-plus me-2"></i> Añadir Establecimiento
                    </button>
                </form>
            <?php endif; ?>
        </div>

        <?php if (empty($establecimientos)): ?>
            <div class="no-establecimientos">
                <img src="../img/establecimiento.png" width="80" alt="Logo Establecimiento" class="mb-3">
                <h3 class="fw-bold mb-3">No tienes establecimientos registrados</h3>
                <p>¡Comienza a añadir tu primer establecimiento para ofrecer espacios de trabajo a nómadas digitales!</p>
            </div>
        <?php else: ?>

            <div class="row">
                <?php foreach ($establecimientos as $index => $establecimiento):
                    $randomImage = $backgroundImages[$index % count($backgroundImages)];
                    $direccionFormateada = formatearDireccion(
                        $establecimiento['direccion'],
                        $establecimiento['piso']
                    );
                ?>
                    <div id="establecimiento-main">
                        <div class="establecimiento-card" id="establecimiento-<?php echo $establecimiento['id']; ?>">
                            <script>
                                console.log("<?php echo $establecimiento['image_url'] ?>")
                            </script>
                            <div class="card-header" style="background-image: url('<?php echo 'http://' . $establecimiento['image_url'] ?>');">
                                <div class="card-header-overlay"></div>
                                <div class="card-title">
                                    <div><?php echo htmlspecialchars($establecimiento['nombre']); ?></div>
                                    <div class="service-icons">
                                        <?php if ($establecimiento['has_wifi']): ?>
                                            <div class="service-icon" title="WiFi disponible">
                                                <i class="fas fa-wifi"></i>
                                            </div>
                                        <?php endif; ?>

                                        <?php if ($establecimiento['has_parking']): ?>
                                            <div class="service-icon" title="Parking disponible">
                                                <i class="fas fa-parking"></i>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <div class="card-body">
                                <div class="info-row">
                                    <div class="info-icon"><i class="fas fa-map-marker-alt"></i></div>
                                    <div><?php echo htmlspecialchars($direccionFormateada); ?></div>
                                </div>

                                <div class="info-row">
                                    <div class="info-icon"><i class="fas fa-city"></i></div>
                                    <div><?php echo htmlspecialchars($establecimiento['localidad']); ?></div>
                                </div>

                                <button class="btn btn-toggle" onclick="toggleDetails('<?php echo $establecimiento['id']; ?>')">
                                    <span id="toggle-text-<?php echo $establecimiento['id']; ?>">Ver más detalles</span>
                                    <i class="fas fa-chevron-down" id="toggle-icon-<?php echo $establecimiento['id']; ?>"></i>
                                </button>

                                <div class="collapsed-content" id="details-<?php echo $establecimiento['id']; ?>">
                                    <div class="info-row">
                                        <div class="info-icon"><i class="fas fa-align-left"></i></div>
                                        <div><strong>Descripción:</strong> <?php echo htmlspecialchars($establecimiento['descripcion']); ?></div>
                                    </div>

                                    <div class="info-row">
                                        <div class="info-icon"><i class="fas fa-map"></i></div>
                                        <div><strong>Provincia:</strong> <?php echo htmlspecialchars($establecimiento['provincia']); ?></div>
                                    </div>

                                    <div class="info-row">
                                        <div class="info-icon"><i class="fas fa-map-pin"></i></div>
                                        <div><strong>Código Postal:</strong> <?php echo htmlspecialchars($establecimiento['codigo_postal']); ?></div>
                                    </div>

                                    <?php if ($establecimiento['has_wifi']): ?>
                                        <div class="info-row">
                                            <div class="info-icon"><i class="fas fa-wifi"></i></div>
                                            <div>
                                                <strong>WiFi disponible</strong>
                                                <span class="precio-tag">
                                                    <i class="fas fa-euro-sign"></i> <?php echo number_format($establecimiento['wifi_price'], 2); ?>/hora
                                                </span>
                                            </div>
                                        </div>
                                    <?php endif; ?>

                                    <?php if ($establecimiento['has_parking']): ?>
                                        <div class="info-row">
                                            <div class="info-icon"><i class="fas fa-parking"></i></div>
                                            <div>
                                                <strong>Parking disponible</strong>
                                                <span class="precio-tag">
                                                    <i class="fas fa-euro-sign"></i> <?php echo number_format($establecimiento['parking_price'], 2); ?>/día
                                                </span>
                                            </div>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (!empty($establecimiento['piso'])): ?>
                                        <div class="info-row">
                                            <div class="info-icon"><i class="fas fa-building"></i></div>
                                            <div><strong>Piso:</strong> <?php echo htmlspecialchars($establecimiento['piso']); ?></div>
                                        </div>
                                    <?php endif; ?>

                                    <div class="map-container" id="map-<?php echo $establecimiento['id']; ?>"></div>
                                </div>

                                <div class="btn-actions">

                                    <a href="editarEstablecimiento.php?id=<?php echo $establecimiento['id']; ?>" class="btn btn-action btn-edit">
                                        <i class="fas fa-edit"></i> Editar
                                    </a>
                                    <button class="btn btn-action btn-delete" onclick="confirmarEliminacion('<?php echo $establecimiento['id']; ?>', '<?php echo htmlspecialchars($establecimiento['nombre']); ?>')">
                                        <i class="fas fa-trash-alt"></i> Eliminar
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-confirm">
            <div class="modal-content">
                <div class="modal-header delete">
                    <div class="icon-box">
                        <i class="fas fa-trash"></i>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <h4 class="modal-title mb-4">¿Estás seguro?</h4>
                    <p>¿Realmente deseas eliminar el establecimiento "<span id="establecimiento-nombre"></span>"? Esta acción no se puede deshacer.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" id="btn-confirmar-eliminar" class="btn btn-danger">Sí, eliminar</button>
                </div>
            </div>
        </div>
    </div>

    </div>

    <?php include 'footerAnfitrion.php'; ?>

    <script>
        const MAPBOX_ACCESS_TOKEN = "pk.eyJ1IjoiYW5kcnplamJhbmFzIiwiYSI6ImNrcHdrZXIyYTAyZWkyb3AwNGtpbmtrbXYifQ.PN_iZ4Mh08-V5EXHAHpCSg";

        const maps = {};

        function toggleDetails(id) {
            const detailsElement = document.getElementById(`details-${id}`);
            const toggleText = document.getElementById(`toggle-text-${id}`);
            const toggleIcon = document.getElementById(`toggle-icon-${id}`);

            if (detailsElement.style.display === 'block') {
                detailsElement.style.display = 'none';
                toggleText.innerText = 'Ver más detalles';
                toggleIcon.classList.remove('fa-chevron-up');
                toggleIcon.classList.add('fa-chevron-down');
            } else {
                detailsElement.style.display = 'block';
                toggleText.innerText = 'Ocultar detalles';
                toggleIcon.classList.remove('fa-chevron-down');
                toggleIcon.classList.add('fa-chevron-up');

                initMapIfNeeded(id);
            }
        }

        function initMapIfNeeded(id) {
            const mapContainer = document.getElementById(`map-${id}`);

            if (maps[id]) return;

            const establecimientos = <?php echo json_encode($establecimientos); ?>;
            const establecimiento = establecimientos.find(e => e.id === id);
            console.log(establecimiento.latitude + " " + establecimiento.longitude);
            if (!establecimiento || !establecimiento.latitude || !establecimiento.longitude) {
                mapContainer.innerHTML = '<div class="alert alert-warning">No hay coordenadas disponibles para mostrar en el mapa</div>';
                return;
            }

            mapboxgl.accessToken = MAPBOX_ACCESS_TOKEN;
            const map = new mapboxgl.Map({
                container: `map-${id}`,
                style: 'mapbox://styles/mapbox/streets-v11',
                center: [establecimiento.longitude, establecimiento.latitude],
                zoom: 15
            });

            map.addControl(new mapboxgl.NavigationControl(), 'top-right');

            const el = document.createElement('div');
            el.className = 'marker';
            el.style.backgroundImage = `url('../img/posicionAnfitrion.png')`;
            el.style.width = '40px';
            el.style.height = '40px';
            el.style.backgroundSize = '100%';

            new mapboxgl.Marker(el)
                .setLngLat([establecimiento.longitude, establecimiento.latitude])
                .setPopup(new mapboxgl.Popup({
                        offset: 25
                    })
                    .setHTML(`<h5>${establecimiento.nombre}</h5><p>${establecimiento.direccion}</p>`))
                .addTo(map);

            maps[id] = map;
        }

        function confirmarEliminacion(id, nombre) {
            document.getElementById('establecimiento-nombre').textContent = nombre;

            const btnConfirmar = document.getElementById('btn-confirmar-eliminar');
            const nuevoBtn = btnConfirmar.cloneNode(true);
            btnConfirmar.parentNode.replaceChild(nuevoBtn, btnConfirmar);

            nuevoBtn.addEventListener('click', function() {
                eliminarEstablecimiento(id);
            });

            const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
            deleteModal.show();
        }

        function eliminarEstablecimiento(id) {

            fetch(`eliminarEstablecimiento.php?id=${id}`)
                .then(response => {
                    if (response.ok) {
                        return response.json();
                    }
                    throw new Error('Error al eliminar el establecimiento');
                })
                .then(data => {
                    alert("Establecimiento eliminado correctamente");
                    location.reload();
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Ha ocurrido un error al eliminar el establecimiento. Por favor, inténtalo de nuevo.');
                    bootstrap.Modal.getInstance(document.getElementById('deleteModal')).hide();
                });
        }

        document.addEventListener('DOMContentLoaded', function() {
            <?php if (!empty($establecimientos)): ?>
                const establecimientos = <?php echo json_encode($establecimientos); ?>;

                const mainMapContainer = document.getElementById('main-map-container');
                if (mainMapContainer && establecimientos.length > 0) {
                    mapboxgl.accessToken = MAPBOX_ACCESS_TOKEN;
                    const mainMap = new mapboxgl.Map({
                        container: 'main-map-container',
                        style: 'mapbox://styles/mapbox/streets-v11',
                        center: [-3.703790, 40.416775],
                        zoom: 5
                    });

                    mainMap.addControl(new mapboxgl.NavigationControl(), 'top-right');

                    const bounds = new mapboxgl.LngLatBounds();
                    let hasValidCoords = false;

                    establecimientos.forEach(est => {
                        if (est.longitude && est.latitude) {
                            hasValidCoords = true;

                            const el = document.createElement('div');
                            el.className = 'marker';
                            el.style.backgroundImage = `url('../img/posicionAnfitrion.png')`;
                            el.style.width = '40px';
                            el.style.height = '40px';
                            el.style.backgroundSize = '100%';
                            el.style.cursor = 'pointer';

                            el.addEventListener('click', () => {
                                document.getElementById(`establecimiento-${est.id}`).scrollIntoView({
                                    behavior: 'smooth',
                                    block: 'start'
                                });
                            });

                            new mapboxgl.Marker(el)
                                .setLngLat([est.longitude, est.latitude])
                                .setPopup(new mapboxgl.Popup({
                                        offset: 25
                                    })
                                    .setHTML(`<h6 class="fw-bold mb-1">${est.nombre}</h6><p class="mb-0 text-muted" style="font-size: 0.85rem;">${est.direccion}</p>`))
                                .addTo(mainMap);

                            bounds.extend([est.longitude, est.latitude]);
                        }
                    });

                    if (hasValidCoords) {
                        mainMap.fitBounds(bounds, {
                            padding: 50,
                            maxZoom: 14
                        });
                    }
                }
            <?php endif; ?>
        });
    </script>
</body>

</html>