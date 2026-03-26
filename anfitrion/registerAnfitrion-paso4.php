<?php
session_start();

require '../vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

$formError = '';
$formSuccess = '';

if (isset($_SESSION['establecimiento']['lat']) && isset($_SESSION['establecimiento']['lng'])) {
    $lat = $_SESSION['establecimiento']['lat'];
    $lng = $_SESSION['establecimiento']['lng'];
} else {
    $codigo_postal = $_SESSION['establecimiento']['codigo_postal'];
    $mapboxToken = $_ENV['MAPBOX_APIKEY'];
    $query = urlencode($codigo_postal);

    $url = "https://api.mapbox.com/geocoding/v5/mapbox.places/$query.json?country=es&types=postcode&access_token=$mapboxToken";

    $response = file_get_contents($url);
    $data = json_decode($response, true);

    if (isset($data['features']) && count($data['features']) > 0) {
        $coordinates = $data['features'][0]['center'];
        $lng = $coordinates[0];
        $lat = $coordinates[1];

        $_SESSION['establecimiento']['lat'] = $lat;
        $_SESSION['establecimiento']['lng'] = $lng;
    } else {
        $lat = 40.41678;
        $lng = -3.70379;

        $_SESSION['establecimiento']['lat'] = $lat;
        $_SESSION['establecimiento']['lng'] = $lng;
    }
}
$_SESSION['establecimiento']['latitud'] = $lat;
$_SESSION['establecimiento']['longitud'] = $lng;

function guardarImagen($file, $upload_dir = 'uploads/establecimientos/')
{
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $nombre_original = basename($file['name']);
    $extension = pathinfo($nombre_original, PATHINFO_EXTENSION);
    $nombre_archivo = uniqid() . '_' . time() . '.' . $extension;
    $ruta_completa = $upload_dir . $nombre_archivo;

    if (move_uploaded_file($file['tmp_name'], $ruta_completa)) {
        return [
            'nombre_original' => $nombre_original,
            'nombre_archivo' => $nombre_archivo,
            'ruta' => $ruta_completa,
            'tipo' => $file['type'],
            'tamano' => $file['size']
        ];
    }
    return false;
}

function subirImagenAMinio($tmpName, $fileName, $fileType)
{
    try {
        $minioHost = 'http://' . $_ENV['SERVER_IP'] . ':' . $_ENV['REPO_PORT'];
        $minioBucket = 'establecimientos';

        $minioUrl = $minioHost . '/' . $minioBucket . '/' . $fileName;

        if (!file_exists($tmpName) || !is_readable($tmpName)) {
            error_log("Archivo temporal no existe o no es legible: $tmpName");
            return false;
        }

        $fileContent = file_get_contents($tmpName);

        if ($fileContent === false) {
            error_log("No se pudo leer el contenido del archivo: $tmpName");
            return false;
        }

        $ch = curl_init($minioUrl);

        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => 'PUT',
            CURLOPT_POSTFIELDS => $fileContent,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => [
                'Content-Type: ' . $fileType,
                'Content-Length: ' . strlen($fileContent)
            ],
            CURLOPT_VERBOSE => false,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);

        $resultado = curl_exec($ch);
        $codigoRespuesta = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);

        curl_close($ch);

        if ($curlError) {
            error_log("Error CURL subiendo a Minio: $curlError");
            return false;
        }

        if ($codigoRespuesta >= 200 && $codigoRespuesta < 300) {
            error_log("Subida exitosa a Minio: $fileName (Código: $codigoRespuesta)");
            return $minioUrl;
        } else {
            error_log("Error HTTP subiendo a Minio: $fileName (Código: $codigoRespuesta) - Respuesta: $resultado");
            return false;
        }
    } catch (Exception $e) {
        error_log("Excepción subiendo a Minio: " . $e->getMessage());
        return false;
    }
}

if (isset($_POST['siguiente'])) {
    $latitud = trim($_POST['latitud']);
    $longitud = trim($_POST['longitud']);

    $errors = [];

    if (empty($latitud) || !is_numeric($latitud)) {
        $errors[] = 'La latitud es obligatoria y debe ser un valor numérico.';
    }

    if (empty($longitud) || !is_numeric($longitud)) {
        $errors[] = 'La longitud es obligatoria y debe ser un valor numérico.';
    }

    $fotos_subidas = false;
    $archivos_validos = [];

    if (isset($_FILES['fotos']['name']) && is_array($_FILES['fotos']['name'])) {
        $archivos = [];
        $totalArchivos = count($_FILES['fotos']['name']);

        for ($i = 0; $i < $totalArchivos; $i++) {
            if (!empty($_FILES['fotos']['name'][$i])) {
                $archivos[] = [
                    'name' => $_FILES['fotos']['name'][$i],
                    'type' => $_FILES['fotos']['type'][$i],
                    'size' => $_FILES['fotos']['size'][$i],
                    'tmp_name' => $_FILES['fotos']['tmp_name'][$i],
                    'error' => $_FILES['fotos']['error'][$i]
                ];
            }
        }

        if (!empty($archivos)) {
            $fotos_subidas = true;
            $archivos_validos = $archivos;
        }
    }

    if (!$fotos_subidas && isset($_FILES['fotos_hidden']) && is_array($_FILES['fotos_hidden']['name'])) {
        $archivos = [];
        $totalArchivos = count($_FILES['fotos_hidden']['name']);

        for ($i = 0; $i < $totalArchivos; $i++) {
            if (!empty($_FILES['fotos_hidden']['name'][$i])) {
                $archivos[] = [
                    'name' => $_FILES['fotos_hidden']['name'][$i],
                    'type' => $_FILES['fotos_hidden']['type'][$i],
                    'size' => $_FILES['fotos_hidden']['size'][$i],
                    'tmp_name' => $_FILES['fotos_hidden']['tmp_name'][$i],
                    'error' => $_FILES['fotos_hidden']['error'][$i]
                ];
            }
        }

        if (!empty($archivos)) {
            $fotos_subidas = true;
            $archivos_validos = $archivos;
        }
    }

    if (!$fotos_subidas) {
        $errors[] = 'Debe subir al menos una foto del establecimiento.';
    }

    $fotos_data = [];
    $rutas_minio = [];

    if (empty($errors) && $fotos_subidas) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
        $max_size = 5 * 1024 * 1024;

        unset($_SESSION['rutas']);

        foreach ($archivos_validos as $i => $archivo) {
            $nombre = $archivo['name'];
            $tipo = $archivo['type'];
            $tamano = $archivo['size'];
            $tmp_name = $archivo['tmp_name'];
            $error = $archivo['error'];

            if (!in_array($tipo, $allowed_types)) {
                $errors[] = "El formato de la imagen '$nombre' no es válido. Use JPG o PNG.";
                continue;
            }

            if ($tamano > $max_size) {
                $errors[] = "La imagen '$nombre' excede el tamaño máximo permitido (5MB).";
                continue;
            }

            if ($error !== UPLOAD_ERR_OK) {
                $errors[] = "Error al subir la imagen '$nombre'. Código de error: $error";
                continue;
            }

            if (!file_exists($tmp_name)) {
                $errors[] = "El archivo temporal para '$nombre' no existe.";
                continue;
            }

            $extension = pathinfo($nombre, PATHINFO_EXTENSION);
            $nombreArchivo = 'establecimiento_' . uniqid('', true) . '_' . time() . '_' . $i . '.' . $extension;

            $urlMinio = subirImagenAMinio($tmp_name, $nombreArchivo, $tipo);

            if ($urlMinio) {
                $imagen_guardada = guardarImagen([
                    'name' => $nombre,
                    'type' => $tipo,
                    'size' => $tamano,
                    'tmp_name' => $tmp_name
                ]);

                if ($imagen_guardada) {
                    $fotos_data[] = $imagen_guardada;
                    $rutaSinHttp = str_replace('http://', '', $urlMinio);
                    $rutas_minio[] = $rutaSinHttp;
                    error_log("Imagen subida exitosamente: $nombre -> $urlMinio");
                } else {
                    $errors[] = "Error al guardar la imagen '$nombre' localmente.";
                }
            } else {
                $errors[] = "Error al subir la imagen '$nombre' al repositorio.";
            }
        }

        if (empty($rutas_minio) && empty($errors)) {
            $errors[] = "No se pudo subir ninguna imagen. Inténtelo de nuevo.";
        }
    }

    if (!empty($errors)) {
        $formError = implode(' ', $errors);
        error_log("Errores en subida: " . implode(', ', $errors));
    } else {
        $_SESSION['establecimiento']['latitud'] = $latitud;
        $_SESSION['establecimiento']['longitud'] = $longitud;
        $_SESSION['establecimiento']['fotos'] = $fotos_data;
        $_SESSION['rutas'] = $rutas_minio;

        // --- ACTUALIZAR BORRADOR EN BASE DE DATOS ---
        if (isset($_SESSION['host']['email'])) {
            $emailUpd = $_SESSION['host']['email'];
            $urlUpd = 'http://' . $_ENV['SERVER_IP'] . ':' . $_ENV['DATABASE_PORT'] . '/rest/v1/registros_abandonados?email=eq.' . urlencode($emailUpd);
            $chUpd = curl_init($urlUpd);
            $dataUpd = [
                'paso' => 5,
                'datos_sesion' => json_encode($_SESSION)
            ];
            curl_setopt($chUpd, CURLOPT_CUSTOMREQUEST, 'PATCH');
            curl_setopt($chUpd, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($chUpd, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'apikey: ' . $_ENV['DATABASE_APIKEY']
            ]);
            curl_setopt($chUpd, CURLOPT_POSTFIELDS, json_encode($dataUpd));
            curl_exec($chUpd);
            curl_close($chUpd);
        }
        // --- FIN ACTUALIZAR BORRADOR ---

        $formSuccess = "Datos guardados correctamente. Redirigiendo...";

        error_log("Éxito: " . count($rutas_minio) . " imágenes subidas correctamente");

        echo "<script>
            setTimeout(function() {
                window.location.href = 'registerAnfitrion-paso5.php';
            }, 1500);
        </script>";
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
    <script src='https://api.mapbox.com/mapbox-gl-js/v2.9.1/mapbox-gl.js'></script>
    <link href='https://api.mapbox.com/mapbox-gl-js/v2.9.1/mapbox-gl.css' rel='stylesheet' />
    <link rel="icon" href="../favicon-color.png">
    <link rel="icon" href="../favicon-negro.png" media="(prefers-color-scheme: light)">
    <link rel="icon" href="../favicon-color.png" media="(prefers-color-scheme: dark)">
    <title>Fotos y ubicación de tu establecimiento</title>
    <style>
        body {
            font-family: 'Nunito', sans-serif;
            background-color: #f8f9fa;
        }

        .contenedorAlta {
            max-width: 700px;
            margin: 2rem auto;
            background-color: white;
            border-radius: 15px;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            padding: 1rem;
        }

        .form-control {
            border-radius: 10px;
            padding: 0.75rem;
            border: 1px solid #ced4da;
            transition: border-color 0.3s;
        }

        .form-control:focus {
            border-color: #80bdff;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
        }

        .btn-success {
            background-color: #28a745;
            border: none;
            font-weight: 600;
            padding: 0.75rem 2rem;
        }

        .btn-cancel {
            background-color: #f8f9fa;
            border: 1px solid #ced4da;
            color: #6c757d;
            font-weight: 600;
            padding: 0.75rem 2rem;
        }

        .progress-container {
            width: 100%;
            height: 5px;
            background-color: #e9ecef;
            border-radius: 3px;
            overflow: hidden;
            margin: 1rem 0;
        }

        .progress-bar {
            height: 100%;
            width: 60%;
            background-color: #28a745;
        }

        .alert {
            border-radius: 10px;
            padding: 0.75rem;
            margin-bottom: 1rem;
            display: none;
        }

        .logo-container {
            background-color: #f8f9fa;
            border-radius: 50%;
            width: 120px;
            height: 120px;
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 0 auto;
        }

        #map {
            width: 100%;
            height: 500px;
            border-radius: 10px;
            margin-bottom: 15px;
        }

        .upload-container {
            border: 2px dashed #ced4da;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            margin-bottom: 20px;
            position: relative;
        }

        .upload-container:hover {
            border-color: #28a745;
            cursor: pointer;
        }

        .fotos-preview {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 15px;
        }

        .foto-preview {
            width: 100px;
            height: 100px;
            border-radius: 8px;
            object-fit: cover;
            position: relative;
        }

        .foto-delete {
            position: absolute;
            top: -10px;
            right: -10px;
            background-color: #dc3545;
            color: white;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 12px;
        }

        .location-methods {
            display: flex;
            gap: 15px;
            margin-bottom: 15px;
        }

        .location-method {
            flex: 1;
            padding: 15px;
            border: 1px solid #ced4da;
            border-radius: 10px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }

        .location-method:hover,
        .location-method.active {
            border-color: #28a745;
            background-color: #f0f9f2;
        }

        .location-method i {
            font-size: 24px;
            margin-bottom: 10px;
            color: #28a745;
        }

        .preview-container {
            position: relative;
            display: inline-block;
            margin-right: 10px;
            margin-bottom: 10px;
        }

        .tooltip-container {
            position: relative;
            display: inline-block;
        }

        .tooltip-text {
            visibility: hidden;
            opacity: 0;
            width: 500px;
            background-color: #333;
            color: #fff;
            text-align: left;
            border-radius: 8px;
            padding: 12px 16px;
            position: absolute;
            z-index: 1000;
            top: 150%;
            left: 50%;
            transform: translateX(-50%);
            transition: opacity 0.3s;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            font-size: 14px;
            line-height: 1.5;
            font-weight: normal;
        }

        .tooltip-text::after {
            content: "";
            position: absolute;
            bottom: 100%;
            left: 50%;
            margin-left: -10px;
            border-width: 10px;
            border-style: solid;
            border-color: transparent transparent #333 transparent;
        }

        .tooltip-text.visible {
            visibility: visible;
            opacity: 1;
        }

        #imgInfo {
            cursor: pointer;
            transition: transform 0.2s;
            margin-left: 5px;
        }

        #imgInfo:hover {
            transform: scale(1.1);
        }

        .tooltip-container:hover .tooltip-text {
            visibility: visible;
            opacity: 1;
        }

        .loading-spinner {
            display: none;
            margin-left: 10px;
            vertical-align: middle;
        }

        .success-indicator {
            display: none;
            color: #28a745;
            margin-left: 10px;
            vertical-align: middle;
        }

        .error-indicator {
            display: none;
            color: #dc3545;
            margin-left: 10px;
            vertical-align: middle;
        }

        @media (max-width: 768px) {
            .tooltip-text {
                width: 350px;
                font-size: 13px;
            }

            .register-title {
                display: block;
                margin-bottom: 8px;
            }

            .info-icon-mobile {
                display: block;
                margin: 8px auto 0;
                text-align: center;
            }

            .tooltip-container.mobile {
                display: block;
                text-align: center;
            }

            .tooltip-text::after {
                left: 50%;
            }
        }
    </style>
</head>

<body>
    <div class="contenedorAlta">
        <div class="col-12 text-center py-3 fw-bold h4">
            <div class="d-none d-md-block">
                <p>Registra tu establecimiento
                    <span class="tooltip-container">
                        <img src="../img/informacion.png" alt="Información" id="imgInfo" width="24px" height="24px">
                        <span id="masInfo" class="tooltip-text">Un <b>establecimiento</b> es el negocio o lugar físico donde se encuentran uno o varios espacios de trabajo y donde se ofrece servicios a nómadas digitales.</span>
                    </span>
                </p>
            </div>
            <div class="d-block d-md-none">
                <p class="register-title">Registra tu establecimiento</p>
                <span class="tooltip-container mobile">
                    <img src="../img/informacion.png" alt="Información" id="imgInfoMobile" width="24px" height="24px">
                    <span id="masInfoMobile" class="tooltip-text">Un <b>establecimiento</b> es el negocio o lugar físico donde se encuentran uno o varios espacios de trabajo y donde se ofrece servicios a nómadas digitales.</span>
                </span>
            </div>
        </div>

        <div class="col-12 text-center mb-3">
            <div class="logo-container">
                <img src="../img/establecimiento.png" width="80" alt="Logo Fotos y Ubicación">
            </div>
        </div>

        <div class="col-12 text-center h4 mb-4 fw-bold">
            Fotos y ubicación del establecimiento
        </div>

        <div class="alert alert-danger" id="error-message" <?php echo !empty($formError) ? 'style="display:block"' : ''; ?>>
            <i class="fas fa-exclamation-circle me-2"></i> <span id="error-text"><?php echo $formError; ?></span>
        </div>

        <div class="alert alert-success" id="success-message" <?php echo !empty($formSuccess) ? 'style="display:block"' : ''; ?>>
            <i class="fas fa-check-circle me-2"></i> <span id="success-text"><?php echo $formSuccess; ?></span>
        </div>

        <form method="post" action="" class="container" id="ubicacionForm" enctype="multipart/form-data">
            <div class="row g-3">
                <div class="col-md-12">
                    <label class="form-label fw-bold">Fotos del establecimiento *</label>
                    <p class="small text-muted">Sube al menos 1 foto y máximo 5 fotos (JPG o PNG, máx. 5MB cada una)</p>

                    <div class="upload-container" id="upload-container">
                        <input type="file" id="fotos" name="fotos[]" multiple accept="image/jpeg,image/png,image/jpg" style="display: none;">
                        <i class="fas fa-cloud-upload-alt mb-2" style="font-size: 2rem; color: #28a745;"></i>
                        <div>Haz clic aquí para seleccionar fotos</div>
                        <div class="small text-muted">o arrastra y suelta las imágenes</div>
                    </div>

                    <div class="fotos-preview" id="fotos-preview"></div>
                </div>

                <div class="col-md-12 mt-4">
                    <label class="form-label fw-bold">Ubicación del establecimiento *</label>
                    <p class="small text-muted">Selecciona cómo quieres indicar la ubicación:</p>

                    <div class="location-methods">
                        <div class="location-method active" id="map-method">
                            <i class="fas fa-map-marker-alt"></i>
                            <div>Seleccionar en el mapa</div>
                        </div>
                        <div class="location-method" id="gps-method">
                            <i class="fas fa-location-arrow"></i>
                            <div>Usar mi ubicación actual</div>
                        </div>
                    </div>

                    <div id="map-container">
                        <div id="map"></div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <label for="latitud" class="form-label fw-bold">Latitud *</label>
                            <input type="text" class="form-control" id="latitud" name="latitud" required readonly
                                value="<?php echo isset($_SESSION['establecimiento']['latitud']) ? htmlspecialchars(number_format($_SESSION['establecimiento']['latitud'], 6)) : ''; ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="longitud" class="form-label fw-bold">Longitud *</label>
                            <input type="text" class="form-control" id="longitud" name="longitud" required readonly
                                value="<?php echo isset($_SESSION['establecimiento']['longitud']) ? htmlspecialchars(number_format($_SESSION['establecimiento']['longitud'], 6)) : ''; ?>">
                        </div>
                    </div>
                </div>
            </div>

            <div class="progress-container mt-4">
                <div class="progress-bar"></div>
            </div>

            <div class="container mt-4">
                <div class="row">
                    <div class="col-6 text-end">
                        <button class="btn btn-cancel rounded-pill" type="button" onclick="location.href='registerAnfitrion-paso3.php'">Anterior</button>
                    </div>
                    <div class="col-6">
                        <button type="submit" name="siguiente" id="btnSiguiente" class="btn btn-success rounded-pill">Siguiente</button>
                    </div>
                </div>
            </div>
        </form>

        <div class="container-fluid p-3">
            <div class="row text-center">
                <div class="col-12">Paso 4 de 6</div>
            </div>
        </div>
    </div>

    <script>
        mapboxgl.accessToken = 'pk.eyJ1IjoiYW5kcnplamJhbmFzIiwiYSI6ImNrcHdrZXIyYTAyZWkyb3AwNGtpbmtrbXYifQ.PN_iZ4Mh08-V5EXHAHpCSg';

        let map;
        let marker;


        const defaultLocation = [<?php echo $lng; ?>, <?php echo $lat; ?>];


        function initMap() {
            map = new mapboxgl.Map({
                container: 'map',
                style: 'mapbox://styles/mapbox/streets-v11',
                center: defaultLocation,
                zoom: 12
            });

            map.addControl(new mapboxgl.NavigationControl());

            const latInput = document.getElementById('latitud');
            const lngInput = document.getElementById('longitud');

            if (latInput.value && lngInput.value) {
                const savedLocation = [parseFloat(lngInput.value), parseFloat(latInput.value)];
                addMarker(savedLocation);
                map.setCenter(savedLocation);
            } else {
                addMarker(defaultLocation);
            }

            map.on('click', function(e) {
                const lngLat = [e.lngLat.lng, e.lngLat.lat];
                addMarker(lngLat);
                updateCoordinateInputs(lngLat[1], lngLat[0]);
            });
        }

        function addMarker(lngLat) {
            if (marker) {
                marker.remove();
            }

            const el = document.createElement('div');
            el.className = 'custom-marker';
            el.style.backgroundImage = 'url("../img/posicionAnfitrion.png")';
            el.style.width = '40px';
            el.style.height = '40px';
            el.style.backgroundSize = 'contain';
            el.style.backgroundRepeat = 'no-repeat';
            el.style.backgroundPosition = 'center';

            marker = new mapboxgl.Marker({
                    element: el,
                    draggable: true,
                    anchor: 'bottom'
                })
                .setLngLat(lngLat)
                .addTo(map);

            marker.on('dragend', function() {
                const lngLat = marker.getLngLat();
                updateCoordinateInputs(lngLat.lat, lngLat.lng);
            });
        }

        function updateCoordinateInputs(lat, lng) {
            document.getElementById('latitud').value = lat.toFixed(6);
            document.getElementById('longitud').value = lng.toFixed(6);
        }

        function getCurrentLocation() {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    function(position) {
                        const lat = position.coords.latitude;
                        const lng = position.coords.longitude;
                        const lngLat = [lng, lat];

                        map.setCenter(lngLat);
                        addMarker(lngLat);
                        updateCoordinateInputs(lat, lng);
                    },
                    function(error) {
                        let errorMsg = '';
                        switch (error.code) {
                            case error.PERMISSION_DENIED:
                                errorMsg = "Permiso de geolocalización denegado.";
                                break;
                            case error.POSITION_UNAVAILABLE:
                                errorMsg = "La información de ubicación no está disponible.";
                                break;
                            case error.TIMEOUT:
                                errorMsg = "Se agotó el tiempo para obtener la ubicación.";
                                break;
                            default:
                                errorMsg = "Error desconocido al obtener la ubicación.";
                        }
                        showError(errorMsg);
                    }
                );
            } else {
                showError("La geolocalización no es compatible con este navegador.");
            }
        }

        function showError(message) {
            $('#error-message').show();
            $('#error-text').text(message);

            setTimeout(function() {
                $('#error-message').fadeOut();
            }, 5000);
        }

        // Variable global para mantener todos los archivos seleccionados
        let selectedFiles = [];

        function handleFileSelect(newFiles) {
            const previewContainer = document.getElementById('fotos-preview');

            // Verificar límite total
            if (selectedFiles.length + newFiles.length > 5) {
                showError("Solo puedes subir un máximo de 5 fotos.");
                return;
            }

            // Procesar cada archivo nuevo
            for (let i = 0; i < newFiles.length; i++) {
                const file = newFiles[i];

                // Verificar si el archivo ya existe (por nombre y tamaño)
                const existe = selectedFiles.some(f => f.name === file.name && f.size === file.size);
                if (existe) {
                    showError(`La imagen "${file.name}" ya ha sido seleccionada.`);
                    continue;
                }

                if (!file.type.match('image/jpeg') && !file.type.match('image/png') && !file.type.match('image/jpg')) {
                    showError(`El archivo "${file.name}" no es una imagen válida (JPG o PNG).`);
                    continue;
                }

                if (file.size > 5 * 1024 * 1024) {
                    showError(`La imagen "${file.name}" excede el tamaño máximo permitido (5MB).`);
                    continue;
                }

                // Agregar archivo al array global
                selectedFiles.push(file);

                const previewWrapper = document.createElement('div');
                previewWrapper.className = 'preview-container';
                previewWrapper.setAttribute('data-filename', file.name);
                previewWrapper.setAttribute('data-filesize', file.size);

                const img = document.createElement('img');
                img.className = 'foto-preview';
                img.file = file;
                previewWrapper.appendChild(img);

                const deleteBtn = document.createElement('div');
                deleteBtn.className = 'foto-delete';
                deleteBtn.innerHTML = '<i class="fas fa-times"></i>';
                deleteBtn.onclick = function(e) {
                    e.stopPropagation();
                    removeFileFromSelection(file.name, file.size);
                    previewWrapper.remove();
                };
                previewWrapper.appendChild(deleteBtn);

                previewContainer.appendChild(previewWrapper);

                const reader = new FileReader();
                reader.onload = (function(aImg) {
                    return function(e) {
                        aImg.src = e.target.result;
                    };
                })(img);
                reader.readAsDataURL(file);
            }

            // Actualizar el input con todos los archivos seleccionados
            updateFileInput();

            console.log('Archivos seleccionados:', selectedFiles.length);
        }

        function removeFileFromSelection(filename, filesize) {
            // Eliminar del array global
            selectedFiles = selectedFiles.filter(file => !(file.name === filename && file.size === filesize));

            // Actualizar el input
            updateFileInput();

            console.log('Archivos restantes:', selectedFiles.length);
        }

        function updateFileInput() {
            const fileInput = document.getElementById('fotos');

            try {
                // Crear un nuevo DataTransfer solo si hay archivos
                if (selectedFiles.length > 0) {
                    const dt = new DataTransfer();

                    selectedFiles.forEach(file => {
                        dt.items.add(file);
                    });

                    fileInput.files = dt.files;
                } else {
                    // Si no hay archivos, limpiar el input
                    fileInput.value = '';
                }

                console.log('Input actualizado con archivos:', fileInput.files.length);
            } catch (error) {
                console.error('Error actualizando input:', error);
                // Fallback: usar un input oculto con FormData si DataTransfer no funciona
                createHiddenFileInputs();
            }
        }

        function createHiddenFileInputs() {
            // Eliminar inputs ocultos previos
            const existingHiddenInputs = document.querySelectorAll('input[name="fotos_hidden[]"]');
            existingHiddenInputs.forEach(input => input.remove());

            // Crear inputs ocultos para cada archivo
            const form = document.getElementById('ubicacionForm');

            selectedFiles.forEach((file, index) => {
                const hiddenInput = document.createElement('input');
                hiddenInput.type = 'file';
                hiddenInput.name = 'fotos_hidden[]';
                hiddenInput.style.display = 'none';

                // Crear DataTransfer para este archivo individual
                const dt = new DataTransfer();
                dt.items.add(file);
                hiddenInput.files = dt.files;

                form.appendChild(hiddenInput);
            });
        }

        function validateForm() {
            const latitud = document.getElementById('latitud').value.trim();
            const longitud = document.getElementById('longitud').value.trim();

            let hasErrors = false;
            let errorMessages = [];

            $('#error-message').hide();
            $('#error-text').text('');

            if (!latitud || !longitud) {
                hasErrors = true;
                errorMessages.push('Debes seleccionar la ubicación del establecimiento en el mapa.');
            }

            // Verificar archivos usando el array global
            if (selectedFiles.length === 0) {
                hasErrors = true;
                errorMessages.push('Debes subir al menos una foto del establecimiento.');
            }

            console.log('Archivos para validación:', selectedFiles.length);

            if (hasErrors) {
                $('#error-message').show();
                $('#error-text').text(errorMessages.join(' '));
                return false;
            }

            return true;
        }

        $(document).ready(function() {
            initMap();

            const uploadContainer = document.getElementById('upload-container');
            const fileInput = document.getElementById('fotos');

            uploadContainer.addEventListener('click', function() {
                fileInput.click();
            });

            fileInput.addEventListener('change', function() {
                if (this.files.length > 0) {
                    handleFileSelect(this.files);
                    // NO limpiar el value aquí, se maneja en updateFileInput
                }
            });

            uploadContainer.addEventListener('dragover', function(e) {
                e.preventDefault();
                e.stopPropagation();
                this.style.borderColor = '#28a745';
                this.style.backgroundColor = '#f0f9f2';
            });

            uploadContainer.addEventListener('dragleave', function(e) {
                e.preventDefault();
                e.stopPropagation();
                this.style.borderColor = '#ced4da';
                this.style.backgroundColor = '';
            });

            uploadContainer.addEventListener('drop', function(e) {
                e.preventDefault();
                e.stopPropagation();
                this.style.borderColor = '#ced4da';
                this.style.backgroundColor = '';

                handleFileSelect(e.dataTransfer.files);
            });

            $('#map-method').click(function() {
                $(this).addClass('active');
                $('#gps-method').removeClass('active');
            });

            $('#gps-method').click(function() {
                $(this).addClass('active');
                $('#map-method').removeClass('active');
                getCurrentLocation();
            });

            $('#ubicacionForm').submit(function(e) {
                if (!validateForm()) {
                    e.preventDefault();
                    return false;
                }

                // Asegurar que el input tenga todos los archivos antes del envío
                updateFileInput();

                return true;
            });
        });
    </script>
</body>

</html>