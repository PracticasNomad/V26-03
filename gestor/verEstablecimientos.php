<?php
require_once 'verificar_sesion_gestor.php';
require_once 'establecimientos_logic.php';

require '../vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

$flashMessage = '';
$flashType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_establecimiento') {
    $id = $_POST['id'] ?? '';

    if (!empty($id)) {
        $payload = [
            'nombre' => trim($_POST['nombre'] ?? ''),
            'descripcion' => trim($_POST['descripcion'] ?? ''),
            'direccion' => trim($_POST['direccion'] ?? ''),
            'localidad' => trim($_POST['localidad'] ?? ''),
            'provincia' => trim($_POST['provincia'] ?? ''),
            'codigo_postal' => trim($_POST['codigo_postal'] ?? ''),
            'piso' => trim($_POST['piso'] ?? ''),
            'latitude' => ($_POST['latitude'] ?? '') !== '' ? (float)$_POST['latitude'] : null,
            'longitude' => ($_POST['longitude'] ?? '') !== '' ? (float)$_POST['longitude'] : null,
        ];

        $urlUpdate = 'http://' . $_ENV['SERVER_IP'] . ':' . $_ENV['DATABASE_PORT'] . '/rest/v1/establecimiento?id=eq.' . rawurlencode($id);
        $chUpdate = curl_init($urlUpdate);
        curl_setopt_array($chUpdate, [
            CURLOPT_CUSTOMREQUEST => 'PATCH',
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'apikey: ' . $_ENV['DATABASE_APIKEY'],
                'Authorization: Bearer ' . ($_SESSION['access_token'] ?? $_SESSION['token'] ?? ''),
                'Prefer: return=representation'
            ],
        ]);

        $responseUpdate = curl_exec($chUpdate);
        $httpCodeUpdate = curl_getinfo($chUpdate, CURLINFO_HTTP_CODE);
        curl_close($chUpdate);

        if ($httpCodeUpdate >= 200 && $httpCodeUpdate < 300) {
            header('Location: verEstablecimientos.php?msg=updated');
            exit;
        }

        $errorUpdate = json_decode($responseUpdate, true);
        $flashMessage = 'No se pudo actualizar el establecimiento. ' . htmlspecialchars($errorUpdate['message'] ?? 'Intenta de nuevo.');
        $flashType = 'danger';
    }
}

if (isset($_GET['msg']) && $_GET['msg'] === 'updated') {
    $flashMessage = 'Establecimiento actualizado correctamente.';
    $flashType = 'success';
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
    <link rel="icon" href="Nomadapp.ico" type="image/png">
    <title>Mis Establecimientos</title>
    <style>
        body {
            font-family: 'Nunito', sans-serif;
            background-color: #f8f9fa;
            padding-bottom: 50px;
        }

        .contenedor-principal {
            max-width: 1400px;
            margin: 1.5rem auto;
            padding: 0 20px;
        }

        .header-container {
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .btn-add {
            background-color: #28a745;
            border: none;
            font-weight: 600;
            padding: 0.6rem 1.2rem;
            border-radius: 25px;
            margin-bottom: 20px;
            transition: all 0.3s;
            display: flex;
            width: 100%;
            max-width: 600px;
            justify-content: center;
            align-items: center;
            font-size: 0.95rem;
        }

        .btn-add:hover {
            background-color: #218838;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .establecimiento-card {
            background-color: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            margin-bottom: 0;
            overflow: hidden;
            transition: all 0.3s ease;
            border: 1px solid #e9ecef;
        }

        .establecimiento-card:hover {
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.15);
            transform: translateY(-2px);
        }

        .card-header {
            position: relative;
            height: 140px;
            background-size: cover;
            background-position: center;
            display: flex;
            align-items: flex-end;
            background-color: #f8f9fa;
            background-image: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .card-header.default-image {
            background-image: none !important;
            background-color: #bfc5cc;
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
            padding: 15px;
            font-weight: 600;
            font-size: 1.3rem;
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
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.9rem;
            border: 1px solid rgba(0, 0, 0, 0.1);
        }

        .validation-badge {
            color: white !important;
            border: 2px solid rgba(255, 255, 255, 0.3);
            font-size: 1rem;
        }

        .validation-badge.bg-success {
            background-color: #28a745 !important;
        }

        .validation-badge.bg-warning {
            background-color: #ffc107 !important;
            color: #212529 !important;
        }

        .card-body {
            padding: 15px;
        }

        .info-row {
            display: flex;
            align-items: center;
            margin-bottom: 6px;
            gap: 8px;
        }

        .info-icon {
            color: #28a745;
            width: 18px;
            text-align: center;
            font-size: 0.9rem;
        }

        .collapsed-content {
            max-height: 0;
            overflow: hidden;
            padding-top: 0;
            border-top: 1px solid #e9ecef;
            margin-top: 0;
            transition: all 0.3s ease;
            opacity: 0;
        }

        .collapsed-content.show {
            max-height: 1500px; /* Suficiente para contener todo el contenido */
            padding-top: 8px;
            margin-top: 8px;
            opacity: 1;
        }

        .btn-actions {
            display: flex;
            gap: 8px;
            margin-top: 8px;
            flex-wrap: wrap;
        }

        .btn-action {
            flex: 1;
            border-radius: 8px;
            padding: 0.4rem 0.8rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 4px;
            transition: all 0.2s ease;
            font-size: 0.85rem;
        }

        .btn-toggle {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            color: #6c757d;
            width: 100%;
            margin-bottom: 8px;
            border-radius: 8px;
            padding: 6px 12px;
            font-weight: 500;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
            transition: all 0.2s ease;
            font-size: 0.9rem;
        }

        .btn-toggle:hover {
            background-color: #e9ecef;
            border-color: #adb5bd;
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
            height: 220px;
            border-radius: 8px;
            overflow: hidden;
            margin: 8px 0;
            border: 1px solid #dee2e6;
        }

        .est-card-col {
            margin-bottom: 12px;
        }

        .establecimientos-grid {
            --bs-gutter-x: 0.75rem;
            row-gap: 0.2rem;
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
            border-radius: 20px;
            padding: 3px 8px;
            font-size: 0.75rem;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 3px;
            margin-left: 8px;
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
            margin: 0;
        }

        body {
            padding-bottom: 15%;
        }

        .footer {
            color: black;
            background-color: white;
            width: 100%;
            -webkit-user-select: none;
            -ms-user-select: none;
            user-select: none;
            bottom: 0;
            font-size: 15px;
            background: #E3E1E1;
            text-align: center;
            position: fixed;
            z-index: 1000;
        }

        .footer input[type="radio"] {
            display: none;
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
        }

        .icon-container {
            transition: transform 0.3s ease;
            padding: 5px 0;
        }

        .footer-item:hover .icon-container {
            transform: translateY(-7px);
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
            max-width: 650px;
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

        #per:checked~#lbl_per .icon-container,
        #res:checked~#lbl_res .icon-container,
        #his:checked~#lbl_his .icon-container,
        #esp:checked~#lbl_esp .icon-container {
            color: #007bff;
        }

        /* New hover styles for "Establecimientos" and "Perfil" */
        #lbl_his:hover,
        #lbl_per:hover,
        #lbl_anf:hover,
        #lbl_val:hover,
        #lbl_res:hover,
        #lbl_esp:hover {
            color: #00B7CF !important;
            /* For the text */
        }

        #lbl_his:hover .icon-container,
        #lbl_per:hover .icon-container,
        #lbl_anf:hover .icon-container,
        #lbl_val:hover .icon-container,
        #lbl_res:hover .icon-container,
        #lbl_esp:hover .icon-container {
            color: #007bff;
            /* For the icon */
        }
    </style>
    <script>
        // Almacenar mapas inicializados para evitar recrearlos
        const mapasInicializados = {};

        function toggleDetails(establecimientoId) {
            const detailsElement = document.getElementById('details-' + establecimientoId);
            const toggleText = document.getElementById('toggle-text-' + establecimientoId);
            const toggleIcon = document.getElementById('toggle-icon-' + establecimientoId);

            if (detailsElement.classList.contains('show')) {
                // Ocultar detalles
                detailsElement.classList.remove('show');
                toggleText.textContent = 'Ver más detalles';
                toggleIcon.className = 'fas fa-chevron-down';
            } else {
                // Mostrar detalles
                detailsElement.classList.add('show');
                toggleText.textContent = 'Ver menos detalles';
                toggleIcon.className = 'fas fa-chevron-up';

                // Inicializar mapa si no está inicializado aún
                if (!mapasInicializados[establecimientoId]) {
                    setTimeout(() => {
                        inicializarMapa(establecimientoId);
                    }, 300); // Esperar a que termine la transición
                    mapasInicializados[establecimientoId] = true;
                }
            }
        }

        function inicializarMapa(establecimientoId) {
            const mapContainer = document.getElementById('map-' + establecimientoId);
            if (!mapContainer) return;

            const lat = parseFloat(mapContainer.dataset.lat || '');
            const lng = parseFloat(mapContainer.dataset.lng || '');

            if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
                mapContainer.innerHTML = '<div style="display:flex;align-items:center;justify-content:center;height:100%;background-color:#f8f9fa;border-radius:10px;color:#6c757d;">Coordenadas no disponibles</div>';
                return;
            }

            if (typeof mapboxgl === 'undefined') {
                mapContainer.innerHTML = '<div style="display:flex;align-items:center;justify-content:center;height:100%;background-color:#f8f9fa;border-radius:10px;color:#6c757d;">No se pudo cargar Mapbox</div>';
                return;
            }

            mapboxgl.accessToken = 'pk.eyJ1IjoiYW5kcnplamJhbmFzIiwiYSI6ImNrcHdrZXIyYTAyZWkyb3AwNGtpbmtrbXYifQ.PN_iZ4Mh08-V5EXHAHpCSg';
            const map = new mapboxgl.Map({
                container: 'map-' + establecimientoId,
                style: 'mapbox://styles/mapbox/streets-v11',
                center: [lng, lat],
                zoom: 14,
            });

            new mapboxgl.Marker({ color: '#28a745' })
                .setLngLat([lng, lat])
                .addTo(map);

            setTimeout(() => map.resize(), 250);
        }

        // Función para confirmar eliminación
        function confirmarEliminacion(id, nombre) {
            document.getElementById('establecimiento-nombre').textContent = nombre;
            document.getElementById('btn-confirmar-eliminar').onclick = function() {
                window.location.href = 'establecimiento.php?id=' + id;
            };
            new bootstrap.Modal(document.getElementById('deleteModal')).show();
        }

        function abrirModalEditar(est) {
            document.getElementById('edit-id').value = est.id || '';
            document.getElementById('edit-nombre').value = est.nombre || '';
            document.getElementById('edit-descripcion').value = est.descripcion || '';
            document.getElementById('edit-direccion').value = est.direccion || '';
            document.getElementById('edit-localidad').value = est.localidad || '';
            document.getElementById('edit-provincia').value = est.provincia || '';
            document.getElementById('edit-codigo-postal').value = est.codigo_postal || '';
            document.getElementById('edit-piso').value = est.piso || '';
            document.getElementById('edit-latitude').value = est.latitude ?? '';
            document.getElementById('edit-longitude').value = est.longitude ?? '';

            new bootstrap.Modal(document.getElementById('editModal')).show();
        }
    </script>
</head>

<body>
    <header>
        <div class="container-fluid info text-center">
            <div class="row">
                <div class="col color-white h2 fw-bold pt-3 pb-2">
                    Establecimientos
                </div>
            </div>
        </div>
    </header>

        <?php if (!empty($flashMessage)): ?>
            <div class="container mt-3" style="max-width: 900px;">
                <div class="alert alert-<?php echo $flashType === 'danger' ? 'danger' : 'success'; ?> alert-dismissible fade show" role="alert">
                    <?php echo $flashMessage; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            </div>
        <?php endif; ?>

        <!-- Estadísticas del gestor -->
        <div class="row mb-4" style="max-width: 1400px; margin: 2rem auto; padding: 0 15px;">
            <div class="col-md-4">
                <div class="card text-center">
                    <div class="card-body">
                        <h5 class="card-title text-primary"><?php echo $totalEstablecimientos; ?></h5>
                        <p class="card-text">Total Establecimientos</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-center">
                    <div class="card-body">
                        <h5 class="card-title text-success"><?php echo $establecimientosAprobados; ?></h5>
                        <p class="card-text">Aprobados</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-center">
                    <div class="card-body">
                        <h5 class="card-title text-warning"><?php echo $establecimientosPendientes; ?></h5>
                        <p class="card-text">Pendientes</p>
                    </div>
                </div>
            </div>
        </div>

        <?php if (empty($establecimientos)): ?>
            <div class="no-establecimientos">
                <img src="../img/establecimiento.png" width="80" alt="Logo Establecimiento" class="mb-3">
                <h3 class="fw-bold mb-3">No tienes establecimientos asignados</h3>
                <p class="text-muted">Los establecimientos que te sean asignados aparecerán aquí para su gestión.</p>

            </div>
        <?php else: ?>
            <div class="row establecimientos-grid" style="max-width: 1400px; margin: 0 auto;">
                <?php foreach ($establecimientos as $index => $establecimiento):
                    $randomImage = $backgroundImages[$index % count($backgroundImages)];
                    $direccionFormateada = formatearDireccion(
                        $establecimiento['direccion'],
                        $establecimiento['piso']
                    );
                ?>
                    <div class="col-12 col-md-6 col-xl-4 est-card-col">
                    <div id="establecimiento-main">
                        <div class="establecimiento-card" id="establecimiento-<?php echo $establecimiento['id']; ?>">
                            <div class="card-header<?php echo empty(getImagenUrl($establecimiento['banner_image_url'] ?? $establecimiento['image_url'] ?? '')) ? ' default-image' : ''; ?>"<?php if (!empty(getImagenUrl($establecimiento['banner_image_url'] ?? $establecimiento['image_url'] ?? ''))): ?> style="background-image: url('<?php echo getImagenUrl($establecimiento['banner_image_url'] ?? $establecimiento['image_url'] ?? ''); ?>');"<?php endif; ?>>
                                <div class="card-header-overlay"></div>
                                <div class="card-title">
                                    <div><?php echo htmlspecialchars($establecimiento['nombre']); ?></div>
                                    <div class="service-icons">
                                        <?php
                                        // Determinar el estado de validación
                                        $estadoValidacion = $establecimiento['estaValidado'] ?? $establecimiento['estavalidado'] ?? null;
                                        if ($estadoValidacion === true || $estadoValidacion === 'true' || $estadoValidacion === 't' || $estadoValidacion === 1 || $estadoValidacion === '1') {
                                            $estadoClass = 'success';
                                            $estadoText = 'Aprobado';
                                            $estadoIcon = 'check-circle';
                                        } elseif ($estadoValidacion === false || $estadoValidacion === 'false' || $estadoValidacion === 'f' || $estadoValidacion === 0 || $estadoValidacion === '0') {
                                            $estadoClass = 'danger';
                                            $estadoText = 'Rechazado';
                                            $estadoIcon = 'ban';
                                        } else {
                                            $estadoClass = 'warning';
                                            $estadoText = 'Pendiente';
                                            $estadoIcon = 'clock';
                                        }
                                        ?>
                                        <div class="service-icon validation-badge bg-<?php echo $estadoClass; ?>" title="<?php echo $estadoText; ?>">
                                            <i class="fas fa-<?php echo $estadoIcon; ?>"></i>
                                        </div>
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

                                    <div class="map-container" id="map-<?php echo $establecimiento['id']; ?>" data-lat="<?php echo htmlspecialchars((string)($establecimiento['latitude'] ?? '')); ?>" data-lng="<?php echo htmlspecialchars((string)($establecimiento['longitude'] ?? '')); ?>"></div>
                                </div>

                                <div class="btn-actions">
                                    <a href="verEspacios.php" class="btn btn-action btn-spaces">
                                        <i class="fas fa-door-open"></i> Gestionar Espacios
                                    </a>
                                    <button class="btn btn-action btn-edit" type="button" onclick='abrirModalEditar(<?php echo json_encode([
                                        'id' => $establecimiento['id'],
                                        'nombre' => $establecimiento['nombre'] ?? '',
                                        'descripcion' => $establecimiento['descripcion'] ?? '',
                                        'direccion' => $establecimiento['direccion'] ?? '',
                                        'localidad' => $establecimiento['localidad'] ?? '',
                                        'provincia' => $establecimiento['provincia'] ?? '',
                                        'codigo_postal' => $establecimiento['codigo_postal'] ?? '',
                                        'piso' => $establecimiento['piso'] ?? '',
                                        'latitude' => $establecimiento['latitude'] ?? '',
                                        'longitude' => $establecimiento['longitude'] ?? ''
                                    ], JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'>
                                        <i class="fas fa-edit"></i> Editar
                                    </button>
                                    <button class="btn btn-action btn-delete" onclick="confirmarEliminacion('<?php echo $establecimiento['id']; ?>', '<?php echo htmlspecialchars($establecimiento['nombre']); ?>')">
                                        <i class="fas fa-trash-alt"></i> Eliminar
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        </div>

        <div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <form method="POST" action="verEstablecimientos.php">
                        <div class="modal-header">
                            <h5 class="modal-title">Editar establecimiento</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" name="action" value="update_establecimiento">
                            <input type="hidden" id="edit-id" name="id">

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Nombre</label>
                                    <input type="text" class="form-control" id="edit-nombre" name="nombre" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Dirección</label>
                                    <input type="text" class="form-control" id="edit-direccion" name="direccion" required>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Descripción</label>
                                    <textarea class="form-control" id="edit-descripcion" name="descripcion" rows="2"></textarea>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Localidad</label>
                                    <input type="text" class="form-control" id="edit-localidad" name="localidad">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Provincia</label>
                                    <input type="text" class="form-control" id="edit-provincia" name="provincia">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Código postal</label>
                                    <input type="text" class="form-control" id="edit-codigo-postal" name="codigo_postal">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Piso</label>
                                    <input type="text" class="form-control" id="edit-piso" name="piso">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Latitude</label>
                                    <input type="number" step="any" class="form-control" id="edit-latitude" name="latitude">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Longitude</label>
                                    <input type="number" step="any" class="form-control" id="edit-longitude" name="longitude">
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-primary">Guardar cambios</button>
                        </div>
                    </form>
                </div>
            </div>
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


        <div class="container-fluid footer mt-5 p-3">
            <div class="row text-center fixed-bottom bg-blanco pt-1 px-2 footer-container">
                <label for="anf" id="lbl_anf" class="col-2 text-center footer-item">
                    <div class="row">
                        <a href="Anfitriones.php">
                            <div class="col-12 icon-container">
                                <i class="h2 fas fa-users p-1 m-0"></i>
                                <div>Anfitriones</div>
                            </div>
                        </a>
                    </div>
                </label>

                <label for="val" id="lbl_val" class="col-2 text-center footer-item">
                    <div class="row">
                        <a href="verValidar.php">
                            <div class="col-12 icon-container">
                                <i class="h2 fas fa-check-circle p-1 m-0"></i>
                                <div>Validar</div>
                            </div>
                        </a>
                    </div>
                </label>

                <label for="res" id="lbl_res" class="col-2 text-center footer-item">
                    <div class="row">
                        <a href="verReservas.php">
                            <div class="col-12 icon-container">
                                <i class="h2 fas fa-book-open p-1 m-0"></i>
                                <div>Reservas</div>
                            </div>
                        </a>
                    </div>
                </label>
                <label for="his" id="lbl_his" class="col-2 text-center footer-item">
                    <div class="row">
                        <a href="verEstablecimientos.php">
                            <div class="col-12 icon-container">
                                <i class="h2 fas fa-building p-1 m-0"></i>
                                <div>Establecimientos</div>
                            </div>
                        </a>
                    </div>
                </label>
                <label for="esp" id="lbl_esp" class="col-2 text-center footer-item">
                    <div class="row">
                        <a href="verEspacios.php">
                            <div class="col-12 icon-container">
                                <i class="h2 fas fa-chair p-1 m-0"></i>
                                <div>Espacios</div>
                            </div>
                        </a>
                    </div>
                </label>
                <label for="per" id="lbl_per" class="col-2 text-center footer-item">
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

    </body>

</html>