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
            }

            $errorUpdate = json_decode($responseUpdate, true);
            $flashMessage = 'No se pudo actualizar el establecimiento. ' . htmlspecialchars($errorUpdate['message'] ?? 'Intenta de nuevo.');
            $flashType = 'danger';
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

// ======= LÓGICA DE FILTRADO Y OBTENCIÓN DE ANFITRIONES =======
$filtro_host_id = $_GET['host_id'] ?? null;
$nombresAnfitriones = [];
$uniqueHostNames = []; 

if (isset($establecimientos) && is_array($establecimientos)) {
    if ($filtro_host_id) {
        $establecimientos = array_values(array_filter($establecimientos, function ($est) use ($filtro_host_id) {
            return isset($est['host_id']) && $est['host_id'] === $filtro_host_id;
        }));

        $totalEstablecimientos = count($establecimientos);
        $establecimientosAprobados = 0;
        $establecimientosPendientes = 0;
        foreach ($establecimientos as $est) {
            $estado = $est['estaValidado'] ?? $est['estavalidado'] ?? null;
            if ($estado === true || $estado === 'true' || $estado === 't' || $estado === 1 || $estado === '1') {
                $establecimientosAprobados++;
            } else if ($estado === false || $estado === 'false' || $estado === 'f' || $estado === 0 || $estado === '0') {
            } else {
                $establecimientosPendientes++;
            }
        }
    }

    $hostIds = array_unique(array_filter(array_column($establecimientos, 'host_id')));

    if (!empty($hostIds)) {
        $urlHosts = "http://" . $_ENV['SERVER_IP'] . ":" . $_ENV['DATABASE_PORT'] . "/rest/v1/host?id=in.(" . implode(',', $hostIds) . ")&select=id,name";
        $chH = curl_init($urlHosts);
        curl_setopt_array($chH, [
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $_ENV['SERVICE_APIKEY'],
                'apikey: ' . $_ENV['SERVICE_APIKEY']
            ],
            CURLOPT_RETURNTRANSFER => true
        ]);
        $resH = curl_exec($chH);
        curl_close($chH);

        $hostsData = json_decode($resH, true);
        if (is_array($hostsData)) {
            foreach ($hostsData as $h) {
                $nombresAnfitriones[$h['id']] = $h['name'];
                $uniqueHostNames[$h['name']] = $h['name']; 
            }
            sort($uniqueHostNames); 
        }
    }
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
    <title>Mis Establecimientos</title>
    <style>
        :root {
            --brand-ink: #1f2933;
            --brand-deep: #0f4c5c;
            --brand-accent: #e9724c;
            --brand-soft: #f3f5f7;
            --card-radius: 16px;
        }

        body { font-family: 'Nunito', sans-serif; background: #eef2f5; padding-bottom: 50px; color: var(--brand-ink); }
        .contenedor-principal { max-width: 1400px; margin: 1.5rem auto; padding: 0 20px; }
        .search-bar-wrapper { margin: 0 auto 2rem; max-width: 1400px; padding: 0 15px; }
        .search-bar-container { background: white; border-radius: 12px; padding: 5px 20px; display: flex; align-items: center; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05); border: 1px solid rgba(15, 76, 92, 0.1); transition: all 0.3s; height: 100%; }
        .search-bar-container:focus-within { box-shadow: 0 6px 20px rgba(0, 0, 0, 0.1); border-color: #17a2b8; }
        .search-bar-icon { color: #17a2b8; font-size: 1.2rem; margin-right: 15px; }
        .search-bar-input { border: none; box-shadow: none; font-size: 1.05rem; padding: 10px 0; background: transparent; width: 100%; color: #2c3e50; }
        .search-bar-input:focus { outline: none; box-shadow: none; }
        .filter-select { border-radius: 12px; border: 1px solid rgba(15, 76, 92, 0.1); box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05); padding: 12px 15px; color: #2c3e50; font-weight: 600; height: 100%; transition: all 0.3s; }
        .filter-select:focus { border-color: #17a2b8; box-shadow: 0 6px 20px rgba(0, 0, 0, 0.1); outline: none; }
        
        .establecimiento-card { background-color: white; border-radius: var(--card-radius); box-shadow: 0 10px 25px rgba(31, 41, 51, 0.09); margin-bottom: 0; overflow: hidden; transition: all 0.3s ease; border: 1px solid rgba(15, 76, 92, 0.08); }
        .establecimiento-card:hover { box-shadow: 0 18px 36px rgba(31, 41, 51, 0.15); transform: translateY(-3px); }
        .card-header { position: relative; height: 140px; background-size: cover; background-position: center; display: flex; align-items: flex-end; background-color: #f8f9fa; }
        .card-header.default-image { background-image: none !important; background-color: #c4ccd3; }
        .card-header-overlay { position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0, 0, 0, 0.45); }
        .card-title { color: white; padding: 15px; font-weight: 700; font-size: 1.3rem; position: relative; width: 100%; z-index: 1; display: flex; justify-content: space-between; align-items: center; }
        .service-icons { display: flex; gap: 15px; }
        .service-icon { background-color: rgba(255, 255, 255, 0.9); color: #333; width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.9rem; border: 1px solid rgba(0, 0, 0, 0.1); }
        .validation-badge { color: white !important; border: 2px solid rgba(255, 255, 255, 0.3); font-size: 1rem; }
        .validation-badge.bg-success { background-color: #6f8f79 !important; }
        .validation-badge.bg-warning { background-color: #c3b37a !important; color: #2e2a18 !important; }
        .card-body { padding: 16px; }
        .info-row { display: flex; align-items: center; margin-bottom: 6px; gap: 8px; }
        .info-icon { color: #28a745; width: 18px; text-align: center; font-size: 0.9rem; }
        .collapsed-content { max-height: 0; overflow: hidden; padding-top: 0; border-top: 1px solid #e9ecef; margin-top: 0; transition: all 0.3s ease; opacity: 0; }
        .collapsed-content.show { max-height: 1500px; padding-top: 8px; margin-top: 8px; opacity: 1; }
        .btn-actions { display: flex; gap: 8px; margin-top: 8px; flex-wrap: wrap; }
        .btn-action { flex: 1; border-radius: 8px; padding: 0.4rem 0.8rem; font-weight: 500; display: flex; align-items: center; justify-content: center; gap: 4px; transition: all 0.2s ease; font-size: 0.85rem; }
        .btn-toggle { background-color: #f3f6f8; border: 1px solid #d8e0e6; color: #4b5a66; width: 100%; margin-bottom: 8px; border-radius: 8px; padding: 6px 12px; font-weight: 500; display: flex; align-items: center; justify-content: center; gap: 5px; transition: all 0.2s ease; font-size: 0.9rem; }
        .btn-toggle:hover { background-color: #e7edf2; border-color: #b5c1ca; }
        .btn-spaces { background-color: #6b7280; border: none; color: white; }
        .btn-spaces:hover { background-color: #4b5563; }
        .btn-edit { background-color: #17a2b8; border: none; color: white; }
        .btn-edit:hover { background-color: #138496; color: white; }
        .btn-delete { background-color: #dc3545; border: none; color: white; }
        .btn-delete:hover { background-color: #c82333; }
        .map-container { height: 220px; border-radius: 8px; overflow: hidden; margin: 8px 0; border: 1px solid #dee2e6; }
        .est-card-col { margin-bottom: 12px; }
        .establecimientos-grid { --bs-gutter-x: 0.75rem; row-gap: 0.2rem; }
        .no-establecimientos { background-color: white; border-radius: 18px; box-shadow: 0 12px 28px rgba(31, 41, 51, 0.12); padding: 2rem; text-align: center; }
        
        .stats-grid .card { border: 1px solid rgba(15, 76, 92, 0.08); border-radius: 14px; box-shadow: 0 8px 18px rgba(31, 41, 51, 0.08); transition: transform 0.2s ease, box-shadow 0.2s ease; }
        .stats-grid .card:hover { transform: translateY(-2px); box-shadow: 0 12px 24px rgba(31, 41, 51, 0.12); }
        .stats-grid .card-title { font-size: 1.8rem; font-weight: 800; margin-bottom: 0.15rem; color: var(--brand-deep) !important; }
        .stats-grid .col-md-4:nth-child(2) .card-title { color: #4f9c67 !important; }
        .stats-grid .col-md-4:nth-child(3) .card-title { color: #c3a643 !important; }
        .stats-grid .card-text { color: #5f6d79; font-weight: 600; margin-bottom: 0; }
        .precio-tag { background-color: #28a745; color: white; border-radius: 20px; padding: 3px 8px; font-size: 0.75rem; font-weight: 500; display: inline-flex; align-items: center; gap: 3px; margin-left: 8px; }

        @media (max-width: 767px) {
            .btn-actions { flex-direction: column; }
            .btn-action { width: 100%; }
            .search-bar-wrapper .row>div { margin-bottom: 10px; }
        }
        body { padding-bottom: 15%; }
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

            new mapboxgl.Marker({ color: '#28a745' }).setLngLat([lng, lat]).addTo(map);
            setTimeout(() => map.resize(), 250);
        }

        function confirmarEliminacion(id, nombre) {
            document.getElementById('establecimiento-nombre').textContent = nombre;
            document.getElementById('btn-confirmar-eliminar').onclick = function () {
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
    <?php include 'headerGestor.php'; ?>

    <?php if ($filtro_host_id): ?>
        <div class="container mt-3" style="max-width: 1400px; padding: 0 15px;">
            <div class="alert alert-info d-flex justify-content-between align-items-center shadow-sm" role="alert"
                style="border-radius: 15px;">
                <div>
                    <i class="fas fa-filter me-2"></i> Mostrando solo los establecimientos del anfitrión seleccionado.
                </div>
                <a href="verEstablecimientos.php" class="btn btn-sm btn-outline-info fw-bold"
                    style="border-radius: 20px; border-width: 2px;">
                    Ver todos
                </a>
            </div>
        </div>
    <?php endif; ?>

    <?php if (!empty($flashMessage)): ?>
        <div class="container mt-3" style="max-width: 1400px; padding: 0 15px;">
            <div class="alert alert-<?php echo $flashType === 'danger' ? 'danger' : 'success'; ?> alert-dismissible fade show"
                role="alert" style="border-radius: 15px;">
                <?php echo $flashMessage; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </div>
    <?php endif; ?>

    <div class="row mb-4 stats-grid" style="max-width: 1400px; margin: 1.2rem auto 1.5rem; padding: 0 15px;">
        <div class="col-md-4">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="card-title text-primary"><?php echo $totalEstablecimientos ?? 0; ?></h5>
                    <p class="card-text">Total Establecimientos</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="card-title text-success"><?php echo $establecimientosAprobados ?? 0; ?></h5>
                    <p class="card-text">Aprobados</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="card-title text-warning"><?php echo $establecimientosPendientes ?? 0; ?></h5>
                    <p class="card-text">Pendientes</p>
                </div>
            </div>
        </div>
    </div>

    <?php if (!empty($establecimientos)): ?>
        <div class="search-bar-wrapper">
            <div class="row g-3">
                <div class="col-md-8">
                    <div class="search-bar-container">
                        <i class="fas fa-search search-bar-icon"></i>
                        <input type="text" id="searchInputEst" class="search-bar-input"
                            placeholder="Buscar por nombre, localidad, estado...">
                    </div>
                </div>
                <div class="col-md-4">
                    <select id="filterHostEst" class="form-select filter-select w-100">
                        <option value="">Todos los Anfitriones</option>
                        <?php foreach ($uniqueHostNames as $hName): ?>
                            <option value="<?php echo htmlspecialchars($hName); ?>"><?php echo htmlspecialchars($hName); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div id="no-results-est" class="no-establecimientos mt-4" style="display: none;">
                <i class="fas fa-search fa-3x text-muted mb-3"></i>
                <h4 class="fw-bold">Sin coincidencias</h4>
                <p class="text-muted">No hemos encontrado ningún establecimiento que coincida con tus filtros.</p>
            </div>
        </div>
    <?php endif; ?>

    <?php if (empty($establecimientos)): ?>
        <div class="no-establecimientos mx-3">
            <img src="../img/establecimiento.png" width="80" alt="Logo Establecimiento" class="mb-3">
            <h3 class="fw-bold mb-3">No hay establecimientos</h3>
            <p class="text-muted">No se encontraron establecimientos con el filtro actual.</p>
        </div>
    <?php else: ?>
        <div class="row establecimientos-grid" style="max-width: 1400px; margin: 0 auto; padding: 0 15px;">
            <?php foreach ($establecimientos as $index => $establecimiento):
                $nombreAnfitrion = $nombresAnfitriones[$establecimiento['host_id']] ?? 'Anfitrión Desconocido';
                $direccionFormateada = formatearDireccion($establecimiento['direccion'], $establecimiento['piso']);
                ?>
                <div class="col-12 col-md-6 col-xl-4 est-card-col"
                    data-host-name="<?php echo htmlspecialchars($nombreAnfitrion); ?>">
                    <div id="establecimiento-main">
                        <div class="establecimiento-card" id="establecimiento-<?php echo $establecimiento['id']; ?>">
                            <div class="card-header<?php echo empty(getImagenUrl($establecimiento['banner_image_url'] ?? $establecimiento['image_url'] ?? '')) ? ' default-image' : ''; ?>"
                                <?php if (!empty(getImagenUrl($establecimiento['banner_image_url'] ?? $establecimiento['image_url'] ?? ''))): ?>
                                    style="background-image: url('<?php echo getImagenUrl($establecimiento['banner_image_url'] ?? $establecimiento['image_url'] ?? ''); ?>');"
                                <?php endif; ?>>
                                <div class="card-header-overlay"></div>
                                <div class="card-title">
                                    <div class="d-flex flex-column">
                                        <span><?php echo htmlspecialchars($establecimiento['nombre']); ?></span>
                                        <span style="font-size: 0.85rem; font-weight: 500; margin-top: 4px; color: #e9ecef; text-shadow: 1px 1px 3px rgba(0,0,0,0.8);">
                                            <i class="fas fa-user-tie me-1"></i> Creado por:
                                            <?php echo htmlspecialchars($nombreAnfitrion); ?>
                                        </span>
                                    </div>
                                    
                                    <!-- AQUI ESTÁN LOS ICONOS DE SERVICIOS -->
                                    <div class="service-icons">
                                        <?php
                                        $estadoValidacion = $establecimiento['estaValidado'] ?? $establecimiento['estavalidado'] ?? null;
                                        if ($estadoValidacion === true || $estadoValidacion === 'true' || $estadoValidacion === 't' || $estadoValidacion === 1 || $estadoValidacion === '1') {
                                            $estadoClass = 'success'; $estadoText = 'Aprobado'; $estadoIcon = 'check-circle';
                                        } elseif ($estadoValidacion === false || $estadoValidacion === 'false' || $estadoValidacion === 'f' || $estadoValidacion === 0 || $estadoValidacion === '0') {
                                            $estadoClass = 'danger'; $estadoText = 'Rechazado'; $estadoIcon = 'ban';
                                        } else {
                                            $estadoClass = 'warning'; $estadoText = 'Pendiente'; $estadoIcon = 'clock';
                                        }
                                        ?>
                                        <div class="service-icon validation-badge bg-<?php echo $estadoClass; ?>" title="<?php echo $estadoText; ?>">
                                            <i class="fas fa-<?php echo $estadoIcon; ?>"></i><span class="d-none"><?php echo $estadoText; ?></span>
                                        </div>
                                        
                                        <?php if ($establecimiento['has_wifi']): ?>
                                            <div class="service-icon" title="WiFi disponible">
                                                <i class="fas fa-wifi text-primary"></i><span class="d-none">wifi</span>
                                            </div>
                                        <?php endif; ?>
                                        <?php if ($establecimiento['has_parking']): ?>
                                            <div class="service-icon" title="Parking disponible">
                                                <i class="fas fa-parking text-secondary"></i><span class="d-none">parking</span>
                                            </div>
                                        <?php endif; ?>
                                        <?php if (!empty($establecimiento['has_food'])): ?>
                                            <div class="service-icon" title="Servicio de comida">
                                                <i class="fas fa-utensils text-warning"></i><span class="d-none">comida</span>
                                            </div>
                                        <?php endif; ?>
                                        <?php if (!empty($establecimiento['has_accommodation'])): ?>
                                            <div class="service-icon" title="Alojamiento disponible">
                                                <i class="fas fa-bed text-info"></i><span class="d-none">alojamiento</span>
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
                                        <div><strong>Descripción:</strong>
                                            <?php echo htmlspecialchars($establecimiento['descripcion']); ?></div>
                                    </div>
                                    
                                    <?php if ($establecimiento['has_wifi']): ?>
                                        <div class="info-row">
                                            <div class="info-icon"><i class="fas fa-wifi text-primary"></i></div>
                                            <div><strong>WiFi disponible</strong> <span class="precio-tag"><i class="fas fa-euro-sign"></i> <?php echo number_format($establecimiento['wifi_price'], 2); ?>/hora</span></div>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($establecimiento['has_parking']): ?>
                                        <div class="info-row">
                                            <div class="info-icon"><i class="fas fa-parking text-secondary"></i></div>
                                            <div><strong>Parking disponible</strong> <span class="precio-tag"><i class="fas fa-euro-sign"></i> <?php echo number_format($establecimiento['parking_price'], 2); ?>/día</span></div>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <!-- AQUI ESTA LA INFORMACIÓN DE COMIDA Y ALOJAMIENTO DETALLADA -->
                                    <?php if (!empty($establecimiento['has_food'])): ?>
                                        <div class="info-row">
                                            <div class="info-icon"><i class="fas fa-utensils text-warning"></i></div>
                                            <div><strong>Servicio de comida disponible</strong></div>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($establecimiento['has_accommodation'])): ?>
                                        <div class="info-row">
                                            <div class="info-icon"><i class="fas fa-bed text-info"></i></div>
                                            <div><strong>Alojamiento disponible</strong></div>
                                        </div>
                                    <?php endif; ?>

                                    <div class="map-container" id="map-<?php echo $establecimiento['id']; ?>"
                                        data-lat="<?php echo htmlspecialchars((string) ($establecimiento['latitude'] ?? '')); ?>"
                                        data-lng="<?php echo htmlspecialchars((string) ($establecimiento['longitude'] ?? '')); ?>">
                                    </div>
                                </div>

                                <div class="btn-actions">
                                    <a href="verEspacios.php?establecimiento_id=<?php echo htmlspecialchars($establecimiento['id']); ?>"
                                        class="btn btn-action btn-spaces">
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
                                        'image_url' => $establecimiento['image_url'] ?? '',
                                        'latitude' => $establecimiento['latitude'] ?? '',
                                        'longitude' => $establecimiento['longitude'] ?? ''
                                    ], JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'>
                                        <i class="fas fa-edit"></i> Editar
                                    </button>
                                    <button class="btn btn-action btn-delete"
                                        onclick='confirmarEliminacion(<?php echo json_encode((string) $establecimiento['id']); ?>, <?php echo json_encode((string) ($establecimiento['nombre'] ?? '')); ?>)'>
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

    <div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <form method="POST" action="verEstablecimientos.php" enctype="multipart/form-data">
                    <div class="modal-header">
                        <h5 class="modal-title">Editar establecimiento</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update_establecimiento">
                        <input type="hidden" id="edit-id" name="id">

                        <div class="row g-3">
                            <div class="col-md-6"><label class="form-label">Nombre</label><input type="text"
                                    class="form-control" id="edit-nombre" name="nombre" required></div>
                            <div class="col-md-6"><label class="form-label">Dirección</label><input type="text"
                                    class="form-control" id="edit-direccion" name="direccion" required></div>
                            <div class="col-12"><label class="form-label">Descripción</label><textarea
                                    class="form-control" id="edit-descripcion" name="descripcion" rows="2"></textarea>
                            </div>
                            <div class="col-md-4"><label class="form-label">Localidad</label><input type="text"
                                    class="form-control" id="edit-localidad" name="localidad"></div>
                            <div class="col-md-4"><label class="form-label">Provincia</label><input type="text"
                                    class="form-control" id="edit-provincia" name="provincia"></div>
                            <div class="col-md-4"><label class="form-label">Código postal</label><input type="text"
                                    class="form-control" id="edit-codigo-postal" name="codigo_postal"></div>
                            <div class="col-md-4"><label class="form-label">Piso</label><input type="text"
                                    class="form-control" id="edit-piso" name="piso"></div>
                            <div class="col-12"><label class="form-label">Seleccionar nueva imagen</label><input
                                    type="file" class="form-control" id="edit-image-file" name="imagen_establecimiento"
                                    accept="image/jpeg,image/png,image/gif,image/webp"></div>
                            <div class="col-md-4"><label class="form-label">Latitude</label><input type="number"
                                    step="any" class="form-control" id="edit-latitude" name="latitude"></div>
                            <div class="col-md-4"><label class="form-label">Longitude</label><input type="number"
                                    step="any" class="form-control" id="edit-longitude" name="longitude"></div>
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
                    <div class="icon-box"><i class="fas fa-trash"></i></div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <h4 class="modal-title mb-4">¿Estás seguro?</h4>
                    <p>¿Realmente deseas eliminar el establecimiento "<span id="establecimiento-nombre"></span>"? Esta
                        acción no se puede deshacer.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" id="btn-confirmar-eliminar" class="btn btn-danger">Sí, eliminar</button>
                </div>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>

    <script>
        $(document).ready(function () {
            function filterEstablecimientos() {
                const searchTerm = $('#searchInputEst').val().toLowerCase();
                const hostTerm = $('#filterHostEst').val().toLowerCase();
                let visibleCount = 0;

                $('.est-card-col').each(function () {
                    const cardText = $(this).text().toLowerCase();
                    const cardHost = ($(this).data('host-name') || '').toLowerCase();

                    const matchesSearch = cardText.includes(searchTerm);
                    const matchesHost = hostTerm === '' || cardHost === hostTerm;

                    if (matchesSearch && matchesHost) {
                        $(this).show();
                        visibleCount++;
                    } else {
                        $(this).hide();
                    }
                });

                if (visibleCount === 0) {
                    $('#no-results-est').show();
                    $('.establecimientos-grid').hide();
                } else {
                    $('#no-results-est').hide();
                    $('.establecimientos-grid').show();
                }
            }

            $('#searchInputEst').on('input', filterEstablecimientos);
            $('#filterHostEst').on('change', filterEstablecimientos);
        });
    </script>
</body>

</html>