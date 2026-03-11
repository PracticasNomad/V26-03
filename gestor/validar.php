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





//// traer datos desde la API
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

function normalizarUrlImagen($url) {
    if (empty($url)) {
        return '';
    }

    if (strpos($url, 'http://') === 0 || strpos($url, 'https://') === 0) {
        return $url;
    }

    if (strpos($url, '../') === 0 || strpos($url, './') === 0 || strpos($url, '/') === 0) {
        return $url;
    }

    if (strpos($url, 'uploads/') === 0) {
        return '../' . $url;
    }

    return 'http://' . ltrim($url, '/');
}

$galleryImages = [];
$urlGallery = 'http://' . $_ENV['SERVER_IP'] . ':' . $_ENV['DATABASE_PORT']
    . '/rest/v1/gallery?establecimiento_id=eq.' . $id . '&select=image_url';

$chGallery = curl_init($urlGallery);
curl_setopt_array($chGallery, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'apikey: ' . $_ENV['DATABASE_APIKEY'],
        'Authorization: Bearer ' . ($_SESSION['token'] ?? ''),
    ],
]);

$responseGallery = curl_exec($chGallery);
$httpCodeGallery = curl_getinfo($chGallery, CURLINFO_HTTP_CODE);
curl_close($chGallery);

if ($httpCodeGallery === 200) {
    $galleryData = json_decode($responseGallery, true);
    if (is_array($galleryData)) {
        foreach ($galleryData as $img) {
            if (!empty($img['image_url'])) {
                $galleryImages[] = $img['image_url'];
            }
        }
    }
}

$latitud = $establecimiento['latitude'] ?? $establecimiento['latitud'] ?? null;
$longitud = $establecimiento['longitude'] ?? $establecimiento['longitud'] ?? null;
$fotoPrincipal = normalizarUrlImagen($establecimiento['image_url'] ?? '');
if (empty($fotoPrincipal) && !empty($galleryImages)) {
    $fotoPrincipal = normalizarUrlImagen($galleryImages[0]);
}

$estadoValidacion = $establecimiento['estaValidado'] ?? $establecimiento['estavalidado'] ?? null;

if ($estadoValidacion === true || $estadoValidacion === 'true' || $estadoValidacion === 't' || $estadoValidacion === 1 || $estadoValidacion === '1') {
    $badgeClass = 'bg-success';
    $badgeTextClass = '';
    $badgeLabel = 'Aprobado';
} elseif ($estadoValidacion === false || $estadoValidacion === 'false' || $estadoValidacion === 'f' || $estadoValidacion === 0 || $estadoValidacion === '0') {
    $badgeClass = 'bg-danger';
    $badgeTextClass = '';
    $badgeLabel = 'Rechazado';
} else {
    $badgeClass = 'bg-warning';
    $badgeTextClass = ' text-dark';
    $badgeLabel = 'Pendiente';
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
    <title>Validar - <?php echo htmlspecialchars($establecimiento['nombre']); ?></title>

    <style>
        :root {
            --ink: #1f2933;
            --surface: #ffffff;
            --line: #d9e2ec;
            --brand: #0f4c5c;
        }

        body {
            font-family: 'Nunito', sans-serif;
            background:
                radial-gradient(circle at 12% 0%, rgba(15, 76, 92, 0.08), transparent 30%),
                radial-gradient(circle at 88% 6%, rgba(31, 41, 51, 0.08), transparent 28%),
                linear-gradient(180deg, #f8fafc 0%, #eef2f6 100%);
            padding-bottom: 15%;
            color: var(--ink);
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
            background-color: var(--surface);
            border-radius: 16px;
            box-shadow: 0 10px 22px rgba(31, 41, 51, 0.1);
            margin-bottom: 2rem;
            overflow: hidden;
            transition: all 0.3s ease;
            border: 1px solid var(--line);
        }

        .card-header {
            position: relative;
            height: 180px;
            background-size: cover;
            background-position: center;
            display: flex;
            align-items: flex-end;
            background-color: #c4ccd3;
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
            background: linear-gradient(to bottom, rgba(0, 0, 0, 0.08), rgba(0, 0, 0, 0.68));
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
            color: var(--brand);
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
            background-color: #1f8f5d;
            border: none;
        }

        .btn-aprobar:hover {
            background-color: #187448;
            transform: translateY(-1px);
            box-shadow: 0 2px 6px rgba(24, 116, 72, 0.35);
        }

        .btn-aprobar:active {
            background-color: #1e7e34;
            transform: translateY(0);
        }

        .btn-rechazar {
            background-color: #b54857;
            border: none;
        }

        .btn-rechazar:hover {
            background-color: #983a47;
            transform: translateY(-1px);
            box-shadow: 0 2px 6px rgba(152, 58, 71, 0.35);
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
            background-color: #295b83;
            border: none;
        }

        .btn-volver:hover {
            background-color: #214969;
            transform: translateY(-1px);
            box-shadow: 0 2px 6px rgba(33, 73, 105, 0.35);
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

        .gallery-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
            gap: 10px;
            margin-top: 8px;
        }

        .gallery-item {
            border-radius: 8px;
            overflow: hidden;
            border: 1px solid #dee2e6;
            background: #f8f9fa;
            height: 90px;
        }

        .gallery-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .field-value {
            color: #495057;
            word-break: break-word;
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

                <div class="card-header<?php echo empty($fotoPrincipal) ? ' default-image' : ''; ?>"<?php if (!empty($fotoPrincipal)): ?> style="background-image: url('<?php echo htmlspecialchars($fotoPrincipal); ?>');"<?php endif; ?>>
                    <div class="card-header-overlay"></div>
                    <div class="card-title">
                        <div><?php echo htmlspecialchars($establecimiento['nombre']); ?></div>
                        <span class="badge <?php echo $badgeClass . $badgeTextClass; ?> fs-6"><?php echo $badgeLabel; ?></span>
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

                    <div class="info-row">
                        <div class="info-icon"><i class="fas fa-image"></i></div>
                        <div>
                            <strong>Foto principal:</strong>
                            <div class="field-value"><?php echo !empty($establecimiento['image_url']) ? htmlspecialchars($establecimiento['image_url']) : 'Sin imagen principal'; ?></div>
                        </div>
                    </div>

                    <div class="info-row">
                        <div class="info-icon"><i class="fas fa-images"></i></div>
                        <div>
                            <strong>Fotos de galeria:</strong>
                            <?php if (!empty($galleryImages)): ?>
                                <div class="gallery-grid">
                                    <?php foreach ($galleryImages as $imgUrl): ?>
                                        <div class="gallery-item">
                                            <img src="<?php echo htmlspecialchars(normalizarUrlImagen($imgUrl)); ?>" alt="Foto de galeria">
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="field-value">Sin fotos en gallery</div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="info-row">
                        <div class="info-icon"><i class="fas fa-map-pin"></i></div>
                        <div>
                            <strong>Coordenadas (latitude / longitude):</strong>
                            <div class="field-value">
                                <?php if ($latitud !== null && $longitud !== null): ?>
                                    <?php echo htmlspecialchars((string)$latitud); ?>, <?php echo htmlspecialchars((string)$longitud); ?>
                                <?php else: ?>
                                    Coordenadas no disponibles
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="info-row mt-4">
                        <div class="info-icon"><i class="fas fa-map"></i></div>
                        <div><strong>Mapa (latitude / longitude):</strong></div>
                    </div>
                    <div class="map-container" id="map-validacion">
                        <div class="d-flex justify-content-center align-items-center h-100 bg-light text-muted">
                            Cargando mapa...
                        </div>
                    </div>

                    <div class="btn-actions" id="btn-actions">
                        <a href="verValidar.php" class="btn btn-action btn-volver">
                            <i class="fas fa-arrow-left"></i> Volver
                        </a>

                        <button type="button" class="btn btn-action btn-aprobar" id="btn-aprobar" data-action="aprobar" data-id="<?php echo $establecimiento['id']; ?>">
                            <i class="fas fa-check-circle"></i> <span class="btn-text">Aprobar</span>
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
    document.addEventListener('DOMContentLoaded', function () {
        const btnAprobar = document.getElementById('btn-aprobar');
        const btnRechazar = document.getElementById('btn-rechazar');
        const card = document.querySelector('.establecimiento-card');
        const statusBadge = document.getElementById('validation-status-badge');
        const btnActions = document.getElementById('btn-actions');
        const mapContainer = document.getElementById('map-validacion');

        const latitude = <?php echo json_encode($latitud); ?>;
        const longitude = <?php echo json_encode($longitud); ?>;
        const nombreEstablecimiento = <?php echo json_encode($establecimiento['nombre'] ?? 'Establecimiento'); ?>;
        const direccionEstablecimiento = <?php echo json_encode(($establecimiento['direccion'] ?? '') . ', ' . ($establecimiento['localidad'] ?? '')); ?>;

        const MAPBOX_TOKEN = 'pk.eyJ1IjoiYW5kcnplamJhbmFzIiwiYSI6ImNrcHdrZXIyYTAyZWkyb3AwNGtpbmtrbXYifQ.PN_iZ4Mh08-V5EXHAHpCSg';

        function initMap() {
            const lat = Number(latitude);
            const lng = Number(longitude);

            if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
                mapContainer.innerHTML = '<div class="d-flex justify-content-center align-items-center h-100 bg-light text-muted">No hay coordenadas validas (latitude / longitude) para mostrar el mapa.</div>';
                return;
            }

            if (typeof mapboxgl === 'undefined') {
                mapContainer.innerHTML = '<div class="d-flex justify-content-center align-items-center h-100 bg-light text-muted">No se pudo cargar Mapbox.</div>';
                return;
            }

            mapboxgl.accessToken = MAPBOX_TOKEN;

            const map = new mapboxgl.Map({
                container: 'map-validacion',
                style: 'mapbox://styles/mapbox/streets-v11',
                center: [lng, lat],
                zoom: 14
            });

            new mapboxgl.Marker({ color: '#1f8f5d' })
                .setLngLat([lng, lat])
                .setPopup(new mapboxgl.Popup().setHTML('<strong>' + nombreEstablecimiento + '</strong><br>' + direccionEstablecimiento))
                .addTo(map);
        }

        initMap();

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

            // Hacer la petición AJAX
            const formData = new FormData();
            formData.append('accion', action);

            fetch('procesar_validacion.php?id=' + id + '&ajax=1', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json()) // Espera una respuesta JSON
                .then(data => {
                    if (data.success) {
                        // Éxito: mostrar mensaje y cambiar estilos
                        statusBadge.classList.remove('error', 'success');
                        statusBadge.classList.add('show', action === 'aprobar' ? 'success' : 'error');
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

        btnAprobar.addEventListener('click', function () {
            performValidation('aprobar');
        });

        btnRechazar.addEventListener('click', function () {
            performValidation('rechazar');
        });
    });
</script>

</body>

</html>

