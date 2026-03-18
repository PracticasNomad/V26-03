<?php
require_once 'verificar_sesion_admin.php';
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
        $nuevaImageUrl = null;
        if (isset($_FILES['imagen_establecimiento']) && $_FILES['imagen_establecimiento']['error'] !== UPLOAD_ERR_NO_FILE) {
            $archivo = $_FILES['imagen_establecimiento'];

            if ($archivo['error'] !== UPLOAD_ERR_OK) {
                $mensajesError = [
                    UPLOAD_ERR_INI_SIZE => 'La imagen pesa demasiado (supera el limite configurado en PHP).',
                    UPLOAD_ERR_FORM_SIZE => 'La imagen supera el limite permitido por el formulario.',
                    UPLOAD_ERR_PARTIAL => 'El archivo se subio parcialmente.',
                    UPLOAD_ERR_NO_FILE => 'No se selecciono ninguna imagen.',
                    UPLOAD_ERR_NO_TMP_DIR => 'Falta la carpeta temporal del servidor.',
                    UPLOAD_ERR_CANT_WRITE => 'No se pudo escribir el archivo en disco.',
                    UPLOAD_ERR_EXTENSION => 'Una extension de PHP detuvo la subida.'
                ];
                $flashMessage = $mensajesError[$archivo['error']] ?? 'Error desconocido al subir la imagen.';
                $flashType = 'danger';
            } else {
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mimeReal = finfo_file($finfo, $archivo['tmp_name']);
                finfo_close($finfo);

                $permitidos = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
                if (!in_array($mimeReal, $permitidos, true)) {
                    $flashMessage = 'Formato no compatible (' . htmlspecialchars((string) $mimeReal) . '). Usa JPG, PNG, WEBP o GIF.';
                    $flashType = 'danger';
                } else {
                    $directorioDestino = dirname(__DIR__) . '/uploads/establecimientos/';
                    if (!file_exists($directorioDestino)) {
                        mkdir($directorioDestino, 0777, true);
                        chmod($directorioDestino, 0777);
                    }

                    $mimeToExt = [
                        'image/jpeg' => 'jpg',
                        'image/png' => 'png',
                        'image/webp' => 'webp',
                        'image/gif' => 'gif'
                    ];
                    $extension = $mimeToExt[$mimeReal] ?? 'jpg';

                    $nombreArchivo = 'establecimiento_' . $id . '_' . time() . '.' . $extension;
                    $rutaFinal = $directorioDestino . $nombreArchivo;
                    $rutaParaBD = 'uploads/establecimientos/' . $nombreArchivo;

                    if (is_uploaded_file($archivo['tmp_name']) && move_uploaded_file($archivo['tmp_name'], $rutaFinal)) {
                        $nuevaImageUrl = '../' . $rutaParaBD;
                    } else {
                        $flashMessage = 'No se pudo guardar la imagen en el servidor.';
                        $flashType = 'danger';
                    }
                }
            }
        }

        $payload = [
            'nombre' => trim($_POST['nombre'] ?? ''),
            'descripcion' => trim($_POST['descripcion'] ?? ''),
            'direccion' => trim($_POST['direccion'] ?? ''),
            'localidad' => trim($_POST['localidad'] ?? ''),
            'provincia' => trim($_POST['provincia'] ?? ''),
            'codigo_postal' => trim($_POST['codigo_postal'] ?? ''),
            'piso' => trim($_POST['piso'] ?? ''),
            'latitude' => ($_POST['latitude'] ?? '') !== '' ? (float) $_POST['latitude'] : null,
            'longitude' => ($_POST['longitude'] ?? '') !== '' ? (float) $_POST['longitude'] : null,
        ];

        if ($flashType === 'danger') {
            $payload = null;
        }

        if ($payload !== null) {
            $urlUpdate = 'http://' . $_ENV['SERVER_IP'] . ':' . $_ENV['DATABASE_PORT'] . '/rest/v1/establecimiento?id=eq.' . rawurlencode($id);
            $chUpdate = curl_init($urlUpdate);
            curl_setopt_array($chUpdate, [
                CURLOPT_CUSTOMREQUEST => 'PATCH',
                CURLOPT_POSTFIELDS => json_encode($payload),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'apikey: ' . $_ENV['SERVICE_APIKEY'],
                    'Authorization: Bearer ' . $_ENV['SERVICE_APIKEY'],
                    'Prefer: return=representation'
                ],
            ]);

            $responseUpdate = curl_exec($chUpdate);
            $httpCodeUpdate = curl_getinfo($chUpdate, CURLINFO_HTTP_CODE);
            curl_close($chUpdate);

            if ($httpCodeUpdate >= 200 && $httpCodeUpdate < 300) {
                if (!empty($nuevaImageUrl)) {
                    $urlGalleryPatch = 'http://' . $_ENV['SERVER_IP'] . ':' . $_ENV['DATABASE_PORT'] . '/rest/v1/gallery?establecimiento_id=eq.' . rawurlencode((string) $id);
                    $chGalleryPatch = curl_init($urlGalleryPatch);
                    curl_setopt_array($chGalleryPatch, [
                        CURLOPT_CUSTOMREQUEST => 'PATCH',
                        CURLOPT_POSTFIELDS => json_encode(['image_url' => $nuevaImageUrl]),
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_HTTPHEADER => [
                            'Content-Type: application/json',
                            'apikey: ' . $_ENV['SERVICE_APIKEY'],
                            'Authorization: Bearer ' . $_ENV['SERVICE_APIKEY'],
                            'Prefer: return=representation'
                        ],
                    ]);

                    $responseGalleryPatch = curl_exec($chGalleryPatch);
                    $httpCodeGalleryPatch = curl_getinfo($chGalleryPatch, CURLINFO_HTTP_CODE);
                    curl_close($chGalleryPatch);

                    $patchedRows = json_decode($responseGalleryPatch, true);
                    $patchedCount = is_array($patchedRows) ? count($patchedRows) : 0;

                    if ($httpCodeGalleryPatch < 200 || $httpCodeGalleryPatch >= 300) {
                        $errorGallery = json_decode($responseGalleryPatch, true);
                        $flashMessage = 'No se pudo actualizar la imagen en galeria. ' . htmlspecialchars($errorGallery['message'] ?? 'Intenta de nuevo.');
                        $flashType = 'danger';
                    } elseif ($patchedCount === 0) {
                        $urlGalleryPost = 'http://' . $_ENV['SERVER_IP'] . ':' . $_ENV['DATABASE_PORT'] . '/rest/v1/gallery';
                        $chGalleryPost = curl_init($urlGalleryPost);
                        curl_setopt_array($chGalleryPost, [
                            CURLOPT_CUSTOMREQUEST => 'POST',
                            CURLOPT_POSTFIELDS => json_encode([
                                'establecimiento_id' => $id,
                                'image_url' => $nuevaImageUrl
                            ]),
                            CURLOPT_RETURNTRANSFER => true,
                            CURLOPT_HTTPHEADER => [
                                'Content-Type: application/json',
                                'apikey: ' . $_ENV['SERVICE_APIKEY'],
                                'Authorization: Bearer ' . $_ENV['SERVICE_APIKEY'],
                                'Prefer: return=representation'
                            ],
                        ]);

                        $responseGalleryPost = curl_exec($chGalleryPost);
                        $httpCodeGalleryPost = curl_getinfo($chGalleryPost, CURLINFO_HTTP_CODE);
                        curl_close($chGalleryPost);

                        if ($httpCodeGalleryPost < 200 || $httpCodeGalleryPost >= 300) {
                            $errorGallery = json_decode($responseGalleryPost, true);
                            $flashMessage = 'No se pudo guardar la imagen en galeria. ' . htmlspecialchars($errorGallery['message'] ?? 'Intenta de nuevo.');
                            $flashType = 'danger';
                        }
                    }
                }

                if ($flashType !== 'danger') {
                    header('Location: verEstablecimientos.php?msg=updated');
                    exit;
                }
            } else {
                $errorUpdate = json_decode($responseUpdate, true);
                $flashMessage = 'No se pudo actualizar el establecimiento. ' . htmlspecialchars($errorUpdate['message'] ?? 'Intenta de nuevo.');
                $flashType = 'danger';
            }
        }
    }
}

if (isset($_GET['msg']) && $_GET['msg'] === 'updated') {
    $flashMessage = 'Establecimiento actualizado correctamente.';
    $flashType = 'success';
}

if (isset($_GET['msg']) && $_GET['msg'] === 'deleted') {
    $flashMessage = 'Establecimiento eliminado correctamente.';
    $flashType = 'success';
}

if (isset($_GET['msg']) && $_GET['msg'] === 'delete_error') {
    $flashMessage = 'No se pudo eliminar el establecimiento.';
    $flashType = 'danger';
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
    <link rel="icon" href="../favicon-color.png">
    <link rel="icon" href="../favicon-negro.png" media="(prefers-color-scheme: light)">
    <link rel="icon" href="../favicon-color.png" media="(prefers-color-scheme: dark)">
    <title>Establecimientos Globales - Admin</title>
    <style>
        :root {
            --brand-ink: #1f2933;
            --brand-deep: #0f4c5c;
            --brand-accent: #dc3545;
            /* Rojo admin */
            --brand-soft: #f3f5f7;
            --card-radius: 16px;
            --primary-color: #dc3545;
        }

        body {
            font-family: 'Nunito', sans-serif;
            background: #eef2f5;
            padding-bottom: 120px;
            color: var(--brand-ink);
        }

        .contenedor-principal {
            max-width: 1400px;
            margin: 1.5rem auto;
            padding: 0 20px;
        }

        .establecimiento-card {
            background-color: white;
            border-radius: var(--card-radius);
            box-shadow: 0 10px 25px rgba(31, 41, 51, 0.09);
            margin-bottom: 0;
            overflow: hidden;
            transition: all 0.3s ease;
            border: 1px solid rgba(15, 76, 92, 0.08);
        }

        .establecimiento-card:hover {
            box-shadow: 0 18px 36px rgba(31, 41, 51, 0.15);
            transform: translateY(-3px);
        }

        .card-header {
            position: relative;
            height: 140px;
            background-size: cover;
            background-position: center;
            display: flex;
            align-items: flex-end;
            background-color: #f8f9fa;
        }

        .card-header.default-image {
            background-image: none !important;
            background-color: #c4ccd3;
        }

        .card-header-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.45);
        }

        .card-title {
            color: white;
            padding: 15px;
            font-weight: 700;
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
            background-color: #6f8f79 !important;
        }

        .validation-badge.bg-warning {
            background-color: #c3b37a !important;
            color: #2e2a18 !important;
        }

        .validation-badge.bg-danger {
            background-color: #dc3545 !important;
        }

        .card-body {
            padding: 16px;
        }

        .info-row {
            display: flex;
            align-items: center;
            margin-bottom: 6px;
            gap: 8px;
        }

        .info-icon {
            color: var(--primary-color);
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
            max-height: 1500px;
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
            background-color: #f3f6f8;
            border: 1px solid #d8e0e6;
            color: #4b5a66;
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
            background-color: #e7edf2;
            border-color: #b5c1ca;
        }

        .btn-spaces {
            background-color: #6b7280;
            border: none;
            color: white;
        }

        .btn-spaces:hover {
            background-color: #4b5563;
        }

        .btn-edit {
            background-color: #17a2b8;
            border: none;
            color: white;
        }

        .btn-edit:hover {
            background-color: #138496;
            color: white;
        }

        .btn-delete {
            background-color: #dc3545;
            border: none;
            color: white;
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

        .page-hero {
            max-width: 1400px;
            margin: 1.2rem auto 0.5rem;
            padding: 0 15px;
        }

        .page-hero-inner {
            border-radius: 20px;
            background: var(--primary-color);
            color: #ffffff;
            padding: 1.1rem 1.2rem;
            box-shadow: 0 14px 30px rgba(220, 53, 69, 0.25);
        }

        .page-hero-title {
            font-size: 1.25rem;
            font-weight: 800;
            letter-spacing: 0.2px;
        }

        .hero-title-row {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .stats-grid .card {
            border: 1px solid rgba(15, 76, 92, 0.08);
            border-radius: 14px;
            box-shadow: 0 8px 18px rgba(31, 41, 51, 0.08);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .stats-grid .card-title {
            font-size: 1.8rem;
            font-weight: 800;
            margin-bottom: 0.15rem;
        }

        .stats-grid .card-text {
            color: #5f6d79;
            font-weight: 600;
            margin-bottom: 0;
        }

        .precio-tag {
            background-color: var(--primary-color);
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

        /* ADMIN FOOTER */
        .footer {
            color: black;
            background-color: white;
            width: 100%;
            -webkit-user-select: none;
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
        }

        .footer-item {
            padding: 8px 0;
            text-decoration: none;
            color: black;
            font-size: 0.8rem;
        }

        .icon-container {
            transition: transform 0.3s ease, color 0.3s ease;
            padding: 5px 0;
            color: #000000;
        }

        .footer-item:hover .icon-container {
            transform: translateY(-7px);
            color: var(--primary-color);
        }

        a {
            text-decoration: none;
            color: inherit;
        }
    </style>
    <script>
        const mapasInicializados = {};

        function toggleDetails(establecimientoId) {
            const detailsElement = document.getElementById('details-' + establecimientoId);
            const toggleText = document.getElementById('toggle-text-' + establecimientoId);
            const toggleIcon = document.getElementById('toggle-icon-' + establecimientoId);

            if (detailsElement.classList.contains('show')) {
                detailsElement.classList.remove('show');
                toggleText.textContent = 'Ver más detalles';
                toggleIcon.className = 'fas fa-chevron-down';
            } else {
                detailsElement.classList.add('show');
                toggleText.textContent = 'Ver menos detalles';
                toggleIcon.className = 'fas fa-chevron-up';

                if (!mapasInicializados[establecimientoId]) {
                    setTimeout(() => {
                        inicializarMapa(establecimientoId);
                    }, 300);
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

            new mapboxgl.Marker({
                    color: '#dc3545'
                })
                .setLngLat([lng, lat])
                .addTo(map);

            setTimeout(() => map.resize(), 250);
        }

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
            document.getElementById('edit-image-file').value = '';
            document.getElementById('edit-latitude').value = est.latitude ?? '';
            document.getElementById('edit-longitude').value = est.longitude ?? '';

            new bootstrap.Modal(document.getElementById('editModal')).show();
        }
    </script>
</head>

<body>
    <section class="page-hero">
        <div class="page-hero-inner">
            <div class="hero-title-row">
                <div class="page-hero-title">Gestión Global de Establecimientos</div>
            </div>
        </div>
    </section>

    <?php if (!empty($flashMessage)): ?>
        <div class="container mt-3" style="max-width: 1400px;">
            <div class="alert alert-<?php echo $flashType === 'danger' ? 'danger' : 'success'; ?> alert-dismissible fade show"
                role="alert">
                <?php echo $flashMessage; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </div>
    <?php endif; ?>

    <div class="container mt-4 mb-4" style="max-width: 1400px;">
        <div class="card shadow-sm border-0" style="border-radius: 15px;">
            <div class="card-body p-4">
                <h5 class="fw-bold mb-3"><i class="fas fa-filter text-danger"></i> Buscar y Filtrar</h5>
                <form method="GET" action="verEstablecimientos.php" class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label text-muted">Nombre del Establecimiento</label>
                        <input type="text" class="form-control" name="buscar_nombre" placeholder="Ej. Coworking Central"
                            value="<?php echo htmlspecialchars($_GET['buscar_nombre'] ?? ''); ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label text-muted">Ciudad / Localidad</label>
                        <input type="text" class="form-control" name="buscar_ciudad" placeholder="Ej. Madrid"
                            value="<?php echo htmlspecialchars($_GET['buscar_ciudad'] ?? ''); ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label text-muted">Código Postal</label>
                        <input type="text" class="form-control" name="buscar_cp" placeholder="Ej. 28001"
                            value="<?php echo htmlspecialchars($_GET['buscar_cp'] ?? ''); ?>">
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-danger w-100" style="border-radius: 8px;"><i
                                class="fas fa-search"></i> Filtrar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="row mb-4 stats-grid" style="max-width: 1400px; margin: 0 auto; padding: 0 15px;">
        <div class="col-md-4">
            <div class="card text-center py-2">
                <div class="card-body">
                    <h5 class="card-title text-dark">
                        <?php echo $totalEstablecimientos; ?>
                    </h5>
                    <p class="card-text">Total Registrados</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-center py-2">
                <div class="card-body">
                    <h5 class="card-title text-success">
                        <?php echo $establecimientosAprobados; ?>
                    </h5>
                    <p class="card-text">Aprobados</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-center py-2">
                <div class="card-body">
                    <h5 class="card-title text-warning">
                        <?php echo $establecimientosPendientes; ?>
                    </h5>
                    <p class="card-text">Pendientes / Rechazados</p>
                </div>
            </div>
        </div>
    </div>

    <?php if (empty($establecimientos)): ?>
        <div class="container" style="max-width: 1400px;">
            <div class="card text-center shadow-sm border-0" style="border-radius: 18px; padding: 3rem;">
                <h3 class="fw-bold mb-3 text-muted">No se encontraron establecimientos</h3>
                <p class="text-muted">No hay resultados para la búsqueda actual o el sistema está vacío.</p>
                <?php if (!empty($_GET)): ?>
                    <a href="verEstablecimientos.php" class="btn btn-outline-danger mt-3 d-inline-block mx-auto"
                        style="width: auto;">Limpiar Filtros</a>
                <?php endif; ?>
            </div>
        </div>
    <?php else: ?>
        <div class="row establecimientos-grid" style="max-width: 1400px; margin: 0 auto;">
            <?php foreach ($establecimientos as $index => $establecimiento):
                $direccionFormateada = formatearDireccion($establecimiento['direccion'], $establecimiento['piso']);
            ?>
                <div class="col-12 col-md-6 col-xl-4 est-card-col">
                    <div class="establecimiento-card" id="establecimiento-<?php echo $establecimiento['id']; ?>">
                        <div class="card-header<?php echo empty(getImagenUrl($establecimiento['banner_image_url'] ?? $establecimiento['image_url'] ?? '')) ? ' default-image' : ''; ?>" <?php if (!empty(getImagenUrl($establecimiento['banner_image_url'] ?? $establecimiento['image_url'] ?? ''))): ?>style="background-image: url('<?php echo getImagenUrl($establecimiento['banner_image_url'] ?? $establecimiento['image_url'] ?? ''); ?>');" <?php endif; ?>>
                            <?php if (!empty(getImagenUrl($establecimiento['banner_image_url'] ?? $establecimiento['image_url'] ?? ''))): ?> style="background-image: url('
                                <?php echo getImagenUrl($establecimiento['banner_image_url'] ?? $establecimiento['image_url'] ?? ''); ?>');"
                                <?php endif; ?>>
                                <div class="card-header-overlay"></div>
                                <div class="card-title">
                                    <div>
                                        <?php echo htmlspecialchars($establecimiento['nombre']); ?>
                                    </div>
                                    <div class="service-icons">
                                        <?php
                                        $estadoValidacion = $establecimiento['estaValidado'] ?? $establecimiento['estavalidado'] ?? null;
                                        if ($estadoValidacion === true || $estadoValidacion === 'true' || $estadoValidacion === 't' || $estadoValidacion === 1 || $estadoValidacion === '1') {
                                            $estadoClass = 'success';
                                            $estadoIcon = 'check-circle';
                                            $estadoText = 'Aprobado';
                                        } elseif ($estadoValidacion === false || $estadoValidacion === 'false' || $estadoValidacion === 'f' || $estadoValidacion === 0 || $estadoValidacion === '0') {
                                            $estadoClass = 'danger';
                                            $estadoIcon = 'ban';
                                            $estadoText = 'Rechazado';
                                        } else {
                                            $estadoClass = 'warning';
                                            $estadoIcon = 'clock';
                                            $estadoText = 'Pendiente';
                                        }
                                        ?>
                                        <div class="service-icon validation-badge bg-<?php echo $estadoClass; ?>"
                                            title="<?php echo $estadoText; ?>">
                                            <i class="fas fa-<?php echo $estadoIcon; ?>"></i>
                                        </div>
                                        <?php if ($establecimiento['has_wifi']): ?>
                                            <div class="service-icon" title="WiFi disponible"><i class="fas fa-wifi"></i></div>
                                        <?php endif; ?>
                                        <?php if ($establecimiento['has_parking']): ?>
                                            <div class="service-icon" title="Parking disponible"><i class="fas fa-parking"></i></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                        </div>

                        <div class="card-body">
                            <div class="info-row">
                                <div class="info-icon"><i class="fas fa-map-marker-alt"></i></div>
                                <div>
                                    <?php echo htmlspecialchars($direccionFormateada); ?>
                                </div>
                            </div>
                            <div class="info-row">
                                <div class="info-icon"><i class="fas fa-city"></i></div>
                                <div>
                                    <?php echo htmlspecialchars($establecimiento['localidad'] . ' (' . $establecimiento['codigo_postal'] . ')'); ?>
                                </div>
                            </div>

                            <button class="btn btn-toggle" onclick="toggleDetails('<?php echo $establecimiento['id']; ?>')">
                                <span id="toggle-text-<?php echo $establecimiento['id']; ?>">Ver más detalles</span>
                                <i class="fas fa-chevron-down" id="toggle-icon-<?php echo $establecimiento['id']; ?>"></i>
                            </button>

                            <div class="collapsed-content" id="details-<?php echo $establecimiento['id']; ?>">
                                <div class="info-row">
                                    <div class="info-icon"><i class="fas fa-align-left"></i></div>
                                    <div><strong>Descripción:</strong>
                                        <?php echo htmlspecialchars($establecimiento['descripcion']); ?>
                                    </div>
                                </div>
                                <div class="info-row">
                                    <div class="info-icon"><i class="fas fa-user-tie"></i></div>
                                    <div><strong>Host ID:</strong>
                                        <?php echo htmlspecialchars($establecimiento['host_id'] ?? 'N/A'); ?>
                                    </div>
                                </div>

                                <?php if ($establecimiento['has_wifi']): ?>
                                    <div class="info-row">
                                        <div class="info-icon"><i class="fas fa-wifi"></i></div>
                                        <div><strong>WiFi:</strong> <span class="precio-tag"><i class="fas fa-euro-sign"></i>
                                                <?php echo number_format($establecimiento['wifi_price'], 2); ?>/h
                                            </span></div>
                                    </div>
                                <?php endif; ?>
                                <?php if ($establecimiento['has_parking']): ?>
                                    <div class="info-row">
                                        <div class="info-icon"><i class="fas fa-parking"></i></div>
                                        <div><strong>Parking:</strong> <span class="precio-tag"><i class="fas fa-euro-sign"></i>
                                                <?php echo number_format($establecimiento['parking_price'], 2); ?>/día
                                            </span></div>
                                    </div>
                                <?php endif; ?>

                                <div class="map-container" id="map-<?php echo $establecimiento['id']; ?>"
                                    data-lat="<?php echo htmlspecialchars((string) ($establecimiento['latitude'] ?? '')); ?>"
                                    data-lng="<?php echo htmlspecialchars((string) ($establecimiento['longitude'] ?? '')); ?>">
                                </div>
                            </div>

                            <div class="btn-actions">
                                <a href="verEspacios.php?establecimiento_id=<?php echo $establecimiento['id']; ?>"
                                    class="btn btn-action btn-spaces">
                                    <i class="fas fa-door-open"></i> Espacios
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
                                <button class="btn btn-action btn-delete"
                                    onclick='confirmarEliminacion(<?php echo json_encode((string) $establecimiento['id']); ?>,
                            <?php echo json_encode((string) ($establecimiento['nombre'] ?? '')); ?>)'>
                                    <i class="fas fa-trash-alt"></i> Eliminar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <form method="POST" action="verEstablecimientos.php" enctype="multipart/form-data">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Editar establecimiento</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update_establecimiento">
                        <input type="hidden" id="edit-id" name="id">

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Nombre</label>
                                <input type="text" class="form-control" id="edit-nombre" name="nombre" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Dirección</label>
                                <input type="text" class="form-control" id="edit-direccion" name="direccion" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-bold">Descripción</label>
                                <textarea class="form-control" id="edit-descripcion" name="descripcion"
                                    rows="2"></textarea>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Localidad</label>
                                <input type="text" class="form-control" id="edit-localidad" name="localidad">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Provincia</label>
                                <input type="text" class="form-control" id="edit-provincia" name="provincia">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Código postal</label>
                                <input type="text" class="form-control" id="edit-codigo-postal" name="codigo_postal">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Piso</label>
                                <input type="text" class="form-control" id="edit-piso" name="piso">
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-bold">Actualizar Imagen (Opcional)</label>
                                <input type="file" class="form-control" id="edit-image-file"
                                    name="imagen_establecimiento" accept="image/jpeg,image/png,image/gif,image/webp">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Latitud</label>
                                <input type="number" step="any" class="form-control" id="edit-latitude" name="latitude">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Longitud</label>
                                <input type="number" step="any" class="form-control" id="edit-longitude"
                                    name="longitude">
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
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content text-center" style="border-radius: 15px;">
                <div class="modal-header bg-danger text-white justify-content-center"
                    style="border-top-left-radius: 15px; border-top-right-radius: 15px;">
                    <h5 class="modal-title w-100"><i class="fas fa-exclamation-triangle fa-2x"></i></h5>
                </div>
                <div class="modal-body p-4">
                    <h4 class="mb-3 text-dark">¿Estás seguro?</h4>
                    <p class="text-muted">Se eliminará el establecimiento "<strong
                            id="establecimiento-nombre"></strong>". Esta acción no se puede deshacer.</p>
                </div>
                <div class="modal-footer justify-content-center border-0 mb-3">
                    <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" id="btn-confirmar-eliminar" class="btn btn-danger px-4">Sí, eliminar</button>
                </div>
            </div>
        </div>
    </div>

    <div class="container-fluid footer mt-5 p-3">
        <div class="row text-center fixed-bottom bg-blanco pt-1 px-2 footer-container">
            <a href="dashboard.php" class="col-2 text-center footer-item">
                <div class="row">
                    <div class="col-12 icon-container"><i class="h3 fas fa-chart-line p-1 m-0"></i>
                        <div>Panel</div>
                    </div>
                </div>
            </a>
            <a href="verGestores.php" class="col-2 text-center footer-item">
                <div class="row">
                    <div class="col-12 icon-container"><i class="h3 fas fa-user-tie p-1 m-0"></i>
                        <div>Gestores</div>
                    </div>
                </div>
            </a>
            <a href="verAnfitriones.php" class="col-2 text-center footer-item">
                <div class="row">
                    <div class="col-12 icon-container"><i class="h3 fas fa-users p-1 m-0"></i>
                        <div>Anfitriones</div>
                    </div>
                </div>
            </a>
            <a href="verEstablecimientos.php" class="col-2 text-center footer-item">
                <div class="row">
                    <div class="col-12 icon-container" style="color:var(--primary-color);"><i
                            class="h3 fas fa-building p-1 m-0"></i>
                        <div>Espacios</div>
                    </div>
                </div>
            </a>
            <a href="verValidar.php" class="col-2 text-center footer-item">
                <div class="row">
                    <div class="col-12 icon-container"><i class="h3 fas fa-check-circle p-1 m-0"></i>
                        <div>Validar</div>
                    </div>
                </div>
            </a>
            <a href="tuPerfil.php" class="col-2 text-center footer-item">
                <div class="row">
                    <div class="col-12 icon-container"><i class="h3 fas fa-user-cog p-1 m-0"></i>
                        <div>Perfil</div>
                    </div>
                </div>
            </a>
        </div>
    </div>
</body>

</html>