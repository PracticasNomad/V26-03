<?php
require_once 'verificar_sesion_host.php';

require '../vendor/autoload.php';
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

// AÑADE ESTA LÍNEA AQUÍ:
require_once '../emails/notificacionesAnfitrion.php';

function generateUuidV4()
{
    $data = random_bytes(16);
    $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
    $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['token'])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'No hay sesión activa']);
        exit;
    }

    $establecimientoId = generateUuidV4();

    $formData = [
        'id' => $establecimientoId,
        'nombre' => $_POST['nombre'],
        'descripcion' => $_POST['descripcion'],
        'has_wifi' => isset($_POST['has_wifi']) ? 1 : 0,
        'has_parking' => isset($_POST['has_parking']) ? 1 : 0,
        'wifi_price' => isset($_POST['wifi_price']) ? floatval($_POST['wifi_price']) : 0.0,
        'parking_price' => isset($_POST['parking_price']) ? floatval($_POST['parking_price']) : 0.0,
        'direccion' => $_POST['direccion'] . ", " . $_POST['numero'],
        'piso' => $_POST['piso'],
        'codigo_postal' => $_POST['codigo_postal'],
        'localidad' => $_POST['localidad'],
        'provincia' => $_POST['provincia'],
        'latitude' => $_POST['latitude'],
        'longitude' => $_POST['longitude'],
        'host_id' => $_SESSION['user_id']
    ];

    $minioConfig = [
        'host' => rtrim($_ENV['MINIO_PUBLIC_URL'], '/'),
        'bucket' => 'establecimientos',
        'extensions' => ['jpg', 'jpeg', 'png', 'gif', 'webp', 'tiff', 'jfif', 'bmp', 'pjp', 'apng', 'svgz', 'heic', 'svg', 'heif', 'ico', 'xbm', 'dib', 'tif', 'pjpeg', 'avif'],
        'mimeTypes' => [
            'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png',
            'gif' => 'image/gif', 'webp' => 'image/webp', 'tiff' => 'image/tiff',
            'jfif' => 'image/jpeg', 'bmp' => 'image/bmp', 'pjp' => 'image/jpeg',
            'apng' => 'image/png', 'svgz' => 'image/svg+xml', 'heic' => 'image/heic',
            'svg' => 'image/svg+xml', 'heif' => 'image/heif', 'ico' => 'image/x-icon',
            'xbm' => 'image/x-xbitmap', 'dib' => 'image/bmp', 'tif' => 'image/tiff',
            'pjpeg' => 'image/pjpeg', 'avif' => 'image/avif'
        ]
    ];

    function subirImagenAMinio($archivo, $nombreArchivo, $config)
    {
        if (!$archivo || $archivo['error'] !== UPLOAD_ERR_OK)
            return ['success' => false, 'message' => 'Error en el archivo'];

        $extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
        if (!isset($config['mimeTypes'][$extension]))
            return ['success' => false, 'message' => 'Tipo de archivo no permitido'];

        $minioUrl = $config['host'] . '/' . $config['bucket'] . '/' . $nombreArchivo;
        $fileContent = file_get_contents($archivo['tmp_name']);

        $ch = curl_init($minioUrl);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fileContent);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // Desbloqueo SSL vital para la IP:
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: ' . $config['mimeTypes'][$extension],
            'Content-Length: ' . strlen($fileContent)
        ]);

        $resultado = curl_exec($ch);
        $codigoRespuesta = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return ($codigoRespuesta >= 200 && $codigoRespuesta < 300)
            ? ['success' => true, 'url' => $minioUrl, 'filename' => $nombreArchivo]
            : ['success' => false, 'message' => 'Error al subir a MinIO: HTTP ' . $codigoRespuesta];
    }

    function insertarImagenesEnGallery($establecimientoId, $imagenesSubidas, $token)
    {
        $galleryData = array_map(function ($imagen) use ($establecimientoId) {
            return [
                'image_url' => $imagen['url'],
                'establecimiento_id' => $establecimientoId
            ];
        }, $imagenesSubidas);

        $url = 'http://' . $_ENV['SERVER_IP'] . ':' . $_ENV['DATABASE_PORT'] . '/rest/v1/gallery';
        $ch = curl_init($url);

        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'apikey: ' . $_ENV['DATABASE_APIKEY'],
                'Authorization: Bearer ' . $token,
            ],
            CURLOPT_POSTFIELDS => json_encode($galleryData)
        ]);

        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return ['success' => ($httpCode === 201), 'httpCode' => $httpCode];
    }

    $imagenesSubidas = [];
    $erroresImagenes = [];
    $camposImagen = ['imagen', 'imagen2', 'imagen3', 'imagen4', 'imagen5'];

    foreach ($camposImagen as $index => $campo) {
        if (isset($_FILES[$campo]) && $_FILES[$campo]['error'] === UPLOAD_ERR_OK) {
            $extension = strtolower(pathinfo($_FILES[$campo]['name'], PATHINFO_EXTENSION));
            $nombreArchivo = 'establecimiento_' . $establecimientoId . '_' . ($index + 1) . '_' . time() . '.' . $extension;

            $resultado = subirImagenAMinio($_FILES[$campo], $nombreArchivo, $minioConfig);

            if ($resultado['success']) {
                $imagenesSubidas[] = ['filename' => $resultado['filename'], 'url' => $resultado['url'], 'order' => $index + 1];
            } else {
                $erroresImagenes[] = "Error en imagen " . ($index + 1) . ": " . $resultado['message'];
            }
        }
    }

    if (empty($imagenesSubidas)) {
        echo '<div class="alert alert-danger" role="alert" style="margin: 20px; font-family: sans-serif;">
                <h3>Error: No se ha detectado ninguna imagen</h3>
                <p>Asegúrate de seleccionar imágenes antes de crear el establecimiento. Si el problema persiste, es posible que la imagen sea demasiado pesada o el servidor no la aceptara.</p>
                <p>Detalles: ' . implode(', ', $erroresImagenes) . '</p>
                <a href="anadirEstablecimiento.php" class="btn btn-primary" style="padding:10px 20px; background:#00B7CF; color:white; text-decoration:none; border-radius:5px;">Volver</a>
              </div>';
        exit();
    }

    $url = 'http://' . $_ENV['SERVER_IP'] . ':' . $_ENV['DATABASE_PORT'] . '/rest/v1/establecimiento';
    $ch = curl_init($url);

    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'apikey: ' . $_ENV['DATABASE_APIKEY'],
            'Authorization: Bearer ' . $_SESSION['token'],
        ],
        CURLOPT_POSTFIELDS => json_encode($formData)
    ]);

    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 201) {
        $galleryResult = insertarImagenesEnGallery($establecimientoId, $imagenesSubidas, $_SESSION['token']);

        // --- AÑADIDO: ENVIAR CORREO AL ANFITRIÓN ---
        // Necesitamos el email del anfitrión (asumiendo que lo tienes en la sesión)
        // Si no está en la sesión, tendrías que consultarlo, pero lo normal es que esté:
        $emailDestinatario = $_SESSION['email'] ?? null; 
        
        if ($emailDestinatario) {
            enviarCorreoEstablecimientoSinEspacio(
                $emailDestinatario, 
                $formData['nombre'], // El nombre que acaba de escribir en el formulario
                $establecimientoId // El ID que acabamos de generar
            );
        } else {
            error_log("No se pudo enviar el correo de establecimiento: email no encontrado en la sesión.");
        }

        $_SESSION['success_message'] = 'Establecimiento creado exitosamente';
        if (isset($_GET['redirect']) && !empty($_GET['redirect'])) {
            header("Location: " . $_GET['redirect']);
        } else {
            header("Location: verEstablecimientos.php");
        }
        exit();
    } else {
        echo '<div class="alert alert-danger" role="alert">Error al crear el establecimiento en la base de datos. Código: ' . $httpCode . '</div>';
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://kit.fontawesome.com/b8814a2854.js" crossorigin="anonymous"></script>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;600;700&display=swap" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src='https://api.mapbox.com/mapbox-gl-js/v2.14.1/mapbox-gl.js'></script>
    <link href='https://api.mapbox.com/mapbox-gl-js/v2.14.1/mapbox-gl.css' rel='stylesheet'>
    <link rel="icon" href="../favicon-color.png">
    <title>Añadir Establecimiento</title>
    <style>
        body { font-family: 'Nunito', sans-serif; background-color: #f8f9fa; padding-bottom: 50px; }
        .custom-toast { border-radius: 10px; font-family: 'Nunito', sans-serif; z-index: 10500; }
        .contenedor-principal { max-width: 800px; margin: 2rem auto; padding: 0 15px; }
        .header-container { display: flex; flex-direction: column; justify-content: center; align-items: center; margin-bottom: 2rem; }
        .form-card { background-color: white; border-radius: 15px; box-shadow: 0 .5rem 1rem rgba(0, 0, 0, .15); padding: 2rem; margin-bottom: 2rem; }
        .form-section { margin-bottom: 2rem; padding-bottom: 1rem; border-bottom: 1px solid #e9ecef; }
        .form-section:last-child { border-bottom: none; margin-bottom: 0; padding-bottom: 0; }
        .section-title { font-weight: 700; margin-bottom: 1.5rem; color: #28a745; display: flex; align-items: center; gap: 10px; }
        .form-label { font-weight: 600; }
        .form-control, .form-select { border-radius: 10px; padding: 0.75rem; }
        .form-check-input { width: 1.2em; height: 1.2em; }
        .map-container { height: 400px; border-radius: 10px; overflow: hidden; margin: 15px 0; }
        .btn-action { border-radius: 50px; padding: 0.75rem 1.5rem; font-weight: 600; display: flex; align-items: center; justify-content: center; gap: 5px; transition: all 0.3s; }
        .btn-primary { background-color: #28a745; border: none; }
        .btn-primary:hover { background-color: #218838; transform: translateY(-2px); box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); }
        .btn-secondary { background-color: #6c757d; border: none; }
        .btn-secondary:hover { background-color: #5a6268; transform: translateY(-2px); box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); }
        .buttons-container { display: flex; justify-content: space-between; margin-top: 2rem; }
        .required-field::after { content: "*"; color: red; margin-left: 4px; }
        .location-buttons { display: flex; gap: 10px; margin-bottom: 1rem; }
        .btn-location { background-color: #f8f9fa; border: 1px solid #ced4da; color: #212529; border-radius: 10px; padding: 0.5rem 1rem; font-weight: 600; display: flex; align-items: center; gap: 5px; transition: all 0.3s; }
        .btn-location:hover { background-color: #e9ecef; }
        .btn-location.active { background-color: #28a745; border-color: #28a745; color: white; }
        .image-upload-container { border: 2px dashed #ced4da; border-radius: 10px; padding: 20px; text-align: center; margin-bottom: 15px; transition: all 0.3s; }
        .image-upload-container:hover { border-color: #28a745; background-color: #f8f9fa; }
        .image-preview { display: flex; flex-wrap: wrap; gap: 15px; margin-top: 15px; }
        .preview-item { position: relative; border-radius: 10px; overflow: hidden; border: 2px solid #e9ecef; }
        .preview-item img { width: 120px; height: 120px; object-fit: cover; display: block; }
        .preview-item .remove-btn { position: absolute; top: 5px; right: 5px; background: rgba(255, 0, 0, 0.8); color: white; border: none; border-radius: 50%; width: 25px; height: 25px; cursor: pointer; display: flex; align-items: center; justify-content: center; }
        .upload-btn { background-color: #28a745; color: white; padding: 12px 20px; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; display: inline-flex; align-items: center; gap: 8px; transition: all 0.3s; }
        .file-input { display: none; }
        .image-counter { color: #6c757d; font-size: 14px; margin-top: 10px; }
    </style>
</head>

<body>
    <div class="position-fixed top-0 end-0 p-3" style="z-index: 10500">
        <div id="liveToast" class="toast align-items-center text-white border-0 custom-toast" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body fw-bold" id="toastMessage"></div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    </div>

    <div class="contenedor-principal">
        <div class="header-container">
            <h1 class="fw-bold mb-4">Añadir Establecimiento</h1>
        </div>

        <form id="establecimiento-form"
            action="anadirEstablecimiento.php<?php echo isset($_GET['redirect']) ? '?redirect=' . urlencode($_GET['redirect']) : ''; ?>"
            method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
            <div class="form-card">
                <div class="form-section">
                    <h3 class="section-title"><i class="fas fa-info-circle"></i> Información General</h3>
                    <div class="mb-3">
                        <label for="nombre" class="form-label required-field">Nombre del establecimiento</label>
                        <input type="text" class="form-control" id="nombre" name="nombre" required>
                    </div>
                    <div class="mb-3">
                        <label for="descripcion" class="form-label required-field">Descripción</label>
                        <textarea class="form-control" id="descripcion" name="descripcion" rows="3" required></textarea>
                    </div>
                </div>

                <div class="form-section">
                    <h3 class="section-title"><i class="fas fa-images"></i> Imágenes</h3>
                    <div class="image-upload-container" id="uploadContainer">
                        <i class="fas fa-cloud-upload-alt fa-3x text-muted mb-3"></i>
                        <p class="mb-3">Arrastra tus imágenes aquí o haz clic para seleccionar</p>
                        <button type="button" class="upload-btn" onclick="document.getElementById('imageFiles').click()">
                            <i class="fas fa-plus"></i> Seleccionar Imágenes
                        </button>
                        <input type="file" id="imageFiles" class="file-input" multiple accept="image/*">
                        <div class="image-counter" id="imageCounter">0 de 5 imágenes seleccionadas</div>
                    </div>
                    <div class="image-preview" id="imagePreview"></div>
                    <input type="file" name="imagen" id="imagen" style="display: none;">
                    <input type="file" name="imagen2" id="imagen2" style="display: none;">
                    <input type="file" name="imagen3" id="imagen3" style="display: none;">
                    <input type="file" name="imagen4" id="imagen4" style="display: none;">
                    <input type="file" name="imagen5" id="imagen5" style="display: none;">
                </div>

                <div class="form-section">
                    <h3 class="section-title"><i class="fas fa-concierge-bell"></i> Servicios</h3>
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="has_wifi" name="has_wifi">
                            <label class="form-check-label" for="has_wifi"><i class="fas fa-wifi me-1"></i> Ofrece WiFi</label>
                        </div>
                        <div id="wifi-price-container" class="mt-3 ms-4 d-none">
                            <label for="wifi_price" class="form-label">Precio WiFi (€/hora)</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-euro-sign"></i></span>
                                <input type="number" class="form-control" id="wifi_price" name="wifi_price" step="0.01" min="0">
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="has_parking" name="has_parking">
                            <label class="form-check-label" for="has_parking"><i class="fas fa-parking me-1"></i> Ofrece Parking</label>
                        </div>
                        <div id="parking-price-container" class="mt-3 ms-4 d-none">
                            <label for="parking_price" class="form-label">Precio Parking (€/día)</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-euro-sign"></i></span>
                                <input type="number" class="form-control" id="parking_price" name="parking_price" step="0.01" min="0">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h3 class="section-title"><i class="fas fa-map-marker-alt"></i> Dirección</h3>
                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <label for="direccion" class="form-label required-field">Calle</label>
                            <input type="text" class="form-control" id="direccion" name="direccion" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="numero" class="form-label required-field">Número</label>
                            <input type="text" class="form-control" id="numero" name="numero" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="piso" class="form-label">Piso/Puerta (opcional)</label>
                        <input type="text" class="form-control" id="piso" name="piso">
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="codigo_postal" class="form-label required-field">Código Postal</label>
                            <input type="text" class="form-control" id="codigo_postal" name="codigo_postal" required>
                        </div>
                        <div class="col-md-8 mb-3">
                            <label for="localidad" class="form-label required-field">Localidad</label>
                            <input type="text" class="form-control" id="localidad" name="localidad" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="provincia" class="form-label required-field">Provincia</label>
                        <input type="text" class="form-control" id="provincia" name="provincia" required>
                    </div>
                </div>

                <div class="form-section">
                    <h3 class="section-title"><i class="fas fa-map"></i> Ubicación en el Mapa</h3>
                    <div class="location-buttons">
                        <button type="button" class="btn btn-location active" id="btn-click-map">
                            <i class="fas fa-map-pin"></i> Seleccionar en el mapa
                        </button>
                        <button type="button" class="btn btn-location" id="btn-current-location">
                            <i class="fas fa-location-arrow"></i> Usar mi ubicación actual
                        </button>
                    </div>
                    <div class="map-container" id="map"></div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="latitude" class="form-label required-field">Latitud</label>
                            <input type="text" class="form-control" id="latitude" name="latitude" required readonly>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="longitude" class="form-label required-field">Longitud</label>
                            <input type="text" class="form-control" id="longitude" name="longitude" required readonly>
                        </div>
                    </div>
                </div>
            </div>

            <div class="buttons-container">
                <a href="verEstablecimientos.php" class="btn btn-secondary btn-action">
                    <i class="fas fa-arrow-left"></i> Volver
                </a>
                <button type="submit" class="btn btn-primary btn-action">
                    <i class="fas fa-plus"></i> Añadir Establecimiento
                </button>
            </div>
        </form>
    </div>

    <script>
        const MAPBOX_TOKEN = "pk.eyJ1IjoiYW5kcnplamJhbmFzIiwiYSI6ImNrcHdrZXIyYTAyZWkyb3AwNGtpbmtrbXYifQ.PN_iZ4Mh08-V5EXHAHpCSg";
        const DEFAULT_COORDS = [-3.7038, 40.4168];
        const MAX_FILES = 5;

        let map, marker, selectedFiles = [];

        document.addEventListener('DOMContentLoaded', () => {
            initMap();
            setupEventListeners();
        });

        function mostrarNotificacion(mensaje, tipo = 'success') {
            const toastEl = document.getElementById('liveToast');
            if (!toastEl) return;
            const toastMessage = document.getElementById('toastMessage');

            toastEl.classList.remove('bg-success', 'bg-danger', 'bg-warning');
            if (tipo === 'success') {
                toastEl.classList.add('bg-success');
                mensaje = '✅ ' + mensaje;
            } else if (tipo === 'error') {
                toastEl.classList.add('bg-danger');
                mensaje = '⚠️ ' + mensaje;
            }

            toastMessage.textContent = mensaje;
            new bootstrap.Toast(toastEl, { delay: 3500 }).show();
        }

        function initMap() {
            mapboxgl.accessToken = MAPBOX_TOKEN;
            map = new mapboxgl.Map({
                container: 'map',
                style: 'mapbox://styles/mapbox/streets-v11',
                center: DEFAULT_COORDS,
                zoom: 12
            });
            map.addControl(new mapboxgl.NavigationControl());
            map.on('click', (e) => {
                if (document.getElementById('btn-click-map').classList.contains('active')) {
                    updateMarker(e.lngLat);
                }
            });
        }

        function setupEventListeners() {
            ['has_wifi', 'has_parking'].forEach(id => {
                const checkbox = document.getElementById(id);
                const container = document.getElementById(`${id.split('_')[1]}-price-container`);
                const priceInput = document.getElementById(`${id.split('_')[1]}_price`);
                checkbox.addEventListener('change', () => {
                    container.classList.toggle('d-none', !checkbox.checked);
                    if (!checkbox.checked) priceInput.value = '';
                });
            });

            const btnClick = document.getElementById('btn-click-map');
            const btnCurrent = document.getElementById('btn-current-location');
            btnClick.addEventListener('click', () => { btnClick.classList.add('active'); btnCurrent.classList.remove('active'); });
            btnCurrent.addEventListener('click', () => {
                btnCurrent.classList.add('active'); btnClick.classList.remove('active');
                getCurrentLocation();
            });

            document.getElementById('codigo_postal').addEventListener('blur', handlePostalCode);
            setupImageUpload();
            document.getElementById('establecimiento-form').addEventListener('submit', handleFormSubmit);
        }

        function getCurrentLocation() {
            if (!navigator.geolocation) {
                mostrarNotificacion('Tu navegador no soporta geolocalización', 'error');
                return;
            }
            navigator.geolocation.getCurrentPosition(
                (position) => {
                    const lngLat = { lng: position.coords.longitude, lat: position.coords.latitude };
                    map.flyTo({ center: [lngLat.lng, lngLat.lat], zoom: 15 });
                    updateMarker(lngLat);
                },
                (error) => {
                    mostrarNotificacion('Error al obtener la ubicación: ' + error.message, 'error');
                }
            );
        }

        function updateMarker(lngLat) {
            if (marker) marker.remove();
            marker = new mapboxgl.Marker({ color: '#28a745' }).setLngLat(lngLat).addTo(map);
            document.getElementById('latitude').value = lngLat.lat.toFixed(6);
            document.getElementById('longitude').value = lngLat.lng.toFixed(6);
        }

        async function handlePostalCode() {
            const codigo = this.value.trim();
            if (!/^\d{5}$/.test(codigo)) return;
            try {
                const response = await fetch(`https://api.mapbox.com/geocoding/v5/mapbox.places/${codigo}.json?country=es&types=postcode&access_token=${MAPBOX_TOKEN}`);
                const data = await response.json();
                if (data.features?.length) {
                    const feature = data.features[0];
                    const context = feature.context || [];
                    const localidad = context.find(item => item.id.startsWith('place'))?.text || feature.text;
                    const provincia = context.find(item => item.id.startsWith('region'))?.text;
                    if (localidad) document.getElementById('localidad').value = localidad;
                    if (provincia) document.getElementById('provincia').value = provincia;
                    if (feature.center) {
                        const [lng, lat] = feature.center;
                        map.flyTo({ center: [lng, lat], zoom: 13 });
                        updateMarker({ lng, lat });
                    }
                }
            } catch (error) { console.error('Error al obtener información del código postal:', error); }
        }

        function setupImageUpload() {
            const container = document.getElementById('uploadContainer');
            const fileInput = document.getElementById('imageFiles');
            const preview = document.getElementById('imagePreview');
            const counter = document.getElementById('imageCounter');

            ['dragover', 'dragleave', 'drop'].forEach(event => {
                container.addEventListener(event, (e) => {
                    e.preventDefault();
                    container.classList.toggle('dragover', event === 'dragover');
                    if (event === 'drop') handleFiles(Array.from(e.dataTransfer.files));
                });
            });

            fileInput.addEventListener('change', (e) => handleFiles(Array.from(e.target.files)));

            function handleFiles(files) {
                const imageFiles = files.filter(file => file.type.startsWith('image/'));
                const available = MAX_FILES - selectedFiles.length;
                imageFiles.slice(0, available).forEach(file => {
                    if (selectedFiles.length < MAX_FILES) {
                        selectedFiles.push(file);
                        createPreview(file, selectedFiles.length - 1);
                    }
                });
                updateImageUI();
            }

            function createPreview(file, index) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    const div = document.createElement('div');
                    div.className = 'preview-item';
                    div.innerHTML = `
                        <img src="${e.target.result}" alt="Preview">
                        <button type="button" class="remove-btn" onclick="removeImage(${index})"><i class="fas fa-times"></i></button>
                    `;
                    preview.appendChild(div);
                };
                reader.readAsDataURL(file);
            }

            // AQUI ESTABA EL FALLO QUE BORRABA LOS FICHEROS
            function updateImageUI() {
                counter.textContent = `${selectedFiles.length} de ${MAX_FILES} imágenes seleccionadas`;
                
                // 1. Limpiar los inputs recreándolos limpios
                for (let i = 1; i <= MAX_FILES; i++) {
                    const inputId = i === 1 ? 'imagen' : `imagen${i}`;
                    const input = document.getElementById(inputId);
                    if (input) {
                        const newInput = document.createElement('input');
                        newInput.type = 'file';
                        newInput.name = input.name;
                        newInput.id = input.id;
                        newInput.style.display = 'none';
                        input.parentNode.replaceChild(newInput, input);
                    }
                }
                
                // 2. Asignar los archivos correctamente
                selectedFiles.forEach((file, index) => {
                    const inputId = index === 0 ? 'imagen' : `imagen${index + 1}`;
                    const input = document.getElementById(inputId);
                    if (input) {
                        const dt = new DataTransfer(); 
                        dt.items.add(file); 
                        input.files = dt.files;
                    }
                });
            }

            window.removeImage = function (index) {
                selectedFiles.splice(index, 1);
                preview.innerHTML = '';
                selectedFiles.forEach((file, i) => createPreview(file, i));
                updateImageUI();
            };
        }

        function handleFormSubmit(event) {
            event.preventDefault();
            let isValid = true;
            this.querySelectorAll('[required]').forEach(field => {
                const valid = field.value.trim();
                field.classList.toggle('is-invalid', !valid);
                field.classList.toggle('is-valid', valid);
                if (!valid) isValid = false;
            });
            if (selectedFiles.length === 0) {
                mostrarNotificacion('Debes subir al menos una imagen del establecimiento.', 'error');
                isValid = false;
            }
            if (!document.getElementById('latitude').value || !document.getElementById('longitude').value) {
                mostrarNotificacion('Por favor, selecciona una ubicación en el mapa.', 'error');
                isValid = false;
            }
            this.classList.add('was-validated');
            if (isValid) {
                const btn = this.querySelector('button[type="submit"]');
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creando...';
                btn.disabled = true;
                this.submit();
            }
        }
    </script>
    <?php include '../typebot.php'; ?>
</body>
</html>