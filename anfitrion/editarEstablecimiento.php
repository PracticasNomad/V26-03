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

if (!isset($_SESSION['token'])) {
    header("Location: logoutHost.php");
    exit();
}
*/

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: verEstablecimientos.php");
    exit();
}

$establecimiento_id = $_GET['id'];

$url = 'http://' . $_ENV['SERVER_IP'] . ':' . $_ENV['DATABASE_PORT'] . '/rest/v1/establecimiento?id=eq.' . $establecimiento_id;
$ch = curl_init($url);

curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'apikey: ' . $_ENV['DATABASE_APIKEY'],
    'Authorization: Bearer ' . $_SESSION['token'],
]);

$result = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    if ($httpCode === 401) {
        header("Location: logoutHost.php");
        exit();
    } else {
        echo '<div class="alert alert-danger" role="alert">Error al recuperar los datos del establecimiento. Código de error: ' . $httpCode . '</div>';
        exit();
    }
}

$datos = json_decode($result, true);

if (empty($datos)) {
    echo '<div class="alert alert-danger" role="alert">No se encontró el establecimiento solicitado.</div>';
    exit();
}

$establecimiento = $datos[0];

// Obtener imágenes existentes de la galería
$url_gallery = 'http://' . $_ENV['SERVER_IP'] . ':' . $_ENV['DATABASE_PORT'] . '/rest/v1/gallery?establecimiento_id=eq.' . $establecimiento_id . '&select=*';
$ch_gallery = curl_init($url_gallery);

curl_setopt($ch_gallery, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch_gallery, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'apikey: ' . $_ENV['DATABASE_APIKEY'],
    'Authorization: Bearer ' . $_SESSION['token'],
]);

$gallery_result = curl_exec($ch_gallery);
$gallery_httpCode = curl_getinfo($ch_gallery, CURLINFO_HTTP_CODE);
curl_close($ch_gallery);

$imagenes_existentes = [];
if ($gallery_httpCode === 200) {
    $gallery_data = json_decode($gallery_result, true);
    if (!empty($gallery_data)) {
        $imagenes_existentes = $gallery_data;
    }
}

// Debug temporal - eliminar después
echo "";

if ($establecimiento['host_id'] != $_SESSION['user_id']) {
    header("Location: verEstablecimientos.php");
    exit();
}

$direccion_completa = $establecimiento['direccion'];
$partes_direccion = explode(", ", $direccion_completa);
$calle = $partes_direccion[0];
$numero = isset($partes_direccion[1]) ? $partes_direccion[1] : '';

function generateUuidV4()
{
    $data = random_bytes(16);
    $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
    $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

// Configuración MinIO
$minioConfig = [
    'host' => 'http://' . $_ENV['SERVER_IP'] . ':' . $_ENV['REPO_PORT'],
    'bucket' => 'establecimientos',
    'extensions' => ['jpg', 'jpeg', 'png', 'gif', 'webp', 'tiff', 'jfif', 'bmp', 'pjp', 'apng', 'svgz', 'heic', 'svg', 'heif', 'ico', 'xbm', 'dib', 'tif', 'pjpeg', 'avif'],
    'mimeTypes' => [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'webp' => 'image/webp',
        'tiff' => 'image/tiff',
        'jfif' => 'image/jpeg',
        'bmp' => 'image/bmp',
        'pjp' => 'image/jpeg',
        'apng' => 'image/png',
        'svgz' => 'image/svg+xml',
        'heic' => 'image/heic',
        'svg' => 'image/svg+xml',
        'heif' => 'image/heif',
        'ico' => 'image/x-icon',
        'xbm' => 'image/x-xbitmap',
        'dib' => 'image/bmp',
        'tif' => 'image/tiff',
        'pjpeg' => 'image/pjpeg',
        'avif' => 'image/avif'
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
    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST => 'PUT',
        CURLOPT_POSTFIELDS => $fileContent,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: ' . $config['mimeTypes'][$extension],
            'Content-Length: ' . strlen($fileContent)
        ]
    ]);

    $resultado = curl_exec($ch);
    $codigoRespuesta = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return ($codigoRespuesta === 200 || $codigoRespuesta === 201)
        ? ['success' => true, 'url' => $minioUrl, 'filename' => $nombreArchivo]
        : ['success' => false, 'message' => 'Error al subir a MinIO: ' . $codigoRespuesta];
}

/*
function insertarImagenesEnGallery($establecimientoId, $imagenesSubidas, $token)
{
    $galleryData = array_map(function ($imagen) use ($establecimientoId) {
        return [
            'image_url' => $_ENV['SERVER_IP'] . ':' . $_ENV['REPO_PORT'] . '/establecimientos/' . $imagen['filename'],
            'establecimiento_id' => $establecimientoId
        ];
    }, $imagenesSubidas); */

function insertarImagenesEnGallery($establecimientoId, $imagenesSubidas, $token)
{
    // Usamos la variable del .env que apunta al dominio de tu compañero
    $publicDomain = rtrim($_ENV['MINIO_PUBLIC_URL'], '/');

    $galleryData = array_map(function ($imagen) use ($establecimientoId, $publicDomain) {
        return [
            // Guardamos: https://minio.yonomad.app/establecimientos/nombre.jpg
            'image_url' => $publicDomain . '/establecimientos/' . $imagen['filename'],
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

    return ['success' => ($httpCode === 201), 'httpCode' => $httpCode, 'response' => $result];
}

function descargarImagenDesdeUrl($url, $nombreArchivo, $config)
{
    // Crear contexto para la petición HTTP
    $context = stream_context_create([
        'http' => [
            'timeout' => 30,
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
        ]
    ]);

    // Descargar la imagen
    $imageContent = file_get_contents($url, false, $context);
    if ($imageContent === false) {
        return ['success' => false, 'message' => 'No se pudo descargar la imagen'];
    }

    // Obtener extensión de la URL o detectar por contenido
    $extension = strtolower(pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION));
    if (!$extension) {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->buffer($imageContent);
        $extension = array_search($mimeType, $config['mimeTypes']) ?: 'jpg';
    }

    if (!isset($config['mimeTypes'][$extension])) {
        return ['success' => false, 'message' => 'Tipo de archivo no permitido'];
    }

    $nombreArchivoCompleto = $nombreArchivo . '.' . $extension;
    $minioUrl = $config['host'] . '/' . $config['bucket'] . '/' . $nombreArchivoCompleto;

    $ch = curl_init($minioUrl);
    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST => 'PUT',
        CURLOPT_POSTFIELDS => $imageContent,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: ' . $config['mimeTypes'][$extension],
            'Content-Length: ' . strlen($imageContent)
        ]
    ]);

    $resultado = curl_exec($ch);
    $codigoRespuesta = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return ($codigoRespuesta === 200 || $codigoRespuesta === 201)
        ? ['success' => true, 'url' => $minioUrl, 'filename' => $nombreArchivoCompleto]
        : ['success' => false, 'message' => 'Error al subir a MinIO: ' . $codigoRespuesta];
}

function eliminarImagenDeMinio($filename, $config)
{
    $deleteUrl = $config['host'] . '/' . $config['bucket'] . '/' . $filename;
    $ch = curl_init($deleteUrl);
    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST => 'DELETE',
        CURLOPT_RETURNTRANSFER => true
    ]);
    curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return $httpCode === 204;
}

function eliminarImagenesDeGallery($establecimientoId, $token)
{
    $url = 'http://' . $_ENV['SERVER_IP'] . ':' . $_ENV['DATABASE_PORT'] . '/rest/v1/gallery?establecimiento_id=eq.' . $establecimientoId;
    $ch = curl_init($url);

    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST => 'DELETE',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'apikey: ' . $_ENV['DATABASE_APIKEY'],
            'Authorization: Bearer ' . $token,
        ]
    ]);

    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return ['success' => ($httpCode === 204), 'httpCode' => $httpCode];
}

// CÓDIGO A REEMPLAZAR EN EL BLOQUE if ($_SERVER['REQUEST_METHOD'] === 'POST')
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = $_POST['nombre'];
    $descripcion = $_POST['descripcion'];
    $has_wifi = isset($_POST['has_wifi']) ? 1 : 0;
    $has_parking = isset($_POST['has_parking']) ? 1 : 0;
    $wifi_price = isset($_POST['wifi_price']) ? floatval($_POST['wifi_price']) : 0.0;
    $parking_price = isset($_POST['parking_price']) ? floatval($_POST['parking_price']) : 0.0;
    $direccion = $_POST['direccion'] . ", " . $_POST['numero'];
    $piso = $_POST['piso'];
    $codigo_postal = $_POST['codigo_postal'];
    $localidad = $_POST['localidad'];
    $provincia = $_POST['provincia'];
    $latitude = $_POST['latitude'];
    $longitude = $_POST['longitude'];
    $host_id = $_SESSION['user_id'];

    // Procesar imágenes del formulario
    // Procesar imágenes del formulario (NUEVAS)
    $imagenesSubidas = [];
    $erroresImagenes = [];
    $camposImagen = ['imagen', 'imagen2', 'imagen3', 'imagen4', 'imagen5'];

    foreach ($camposImagen as $index => $campo) {
        if (isset($_FILES[$campo]) && $_FILES[$campo]['error'] === UPLOAD_ERR_OK) {
            $extension = strtolower(pathinfo($_FILES[$campo]['name'], PATHINFO_EXTENSION));
            $nombreArchivo = 'establecimiento_' . $establecimiento_id . '_' . ($index + 1) . '_' . time() . '.' . $extension;

            $resultado = subirImagenAMinio($_FILES[$campo], $nombreArchivo, $minioConfig);

            if ($resultado['success']) {
                $imagenesSubidas[] = ['filename' => $resultado['filename'], 'url' => $resultado['url'], 'order' => $index + 1];
            } else {
                $erroresImagenes[] = "Error en imagen " . ($index + 1) . ": " . $resultado['message'];
            }
        }
    }

    // Procesar imágenes EXISTENTES que se mantienen
    $imagenesExistentesConservadas = [];
    $existingImagesCount = intval($_POST['existing_images_count'] ?? 0);

    for ($i = 0; $i < $existingImagesCount; $i++) {
        $existingImageUrl = $_POST["existing_image_$i"] ?? '';
        if (!empty($existingImageUrl)) {
            // Extraer el nombre del archivo de la URL existente
            $parsedUrl = parse_url($existingImageUrl);
            $pathParts = explode('/', $parsedUrl['path']);
            $oldFilename = end($pathParts);

            // Crear nuevo nombre de archivo para evitar conflictos
            $extension = strtolower(pathinfo($oldFilename, PATHINFO_EXTENSION));
            $newFilename = 'establecimiento_' . $establecimiento_id . '_existing_' . ($i + 1) . '_' . time() . '.' . $extension;

            // Descargar y re-subir la imagen con el nuevo nombre
            $resultado = descargarImagenDesdeUrl(
                $existingImageUrl,
                'establecimiento_' . $establecimiento_id . '_existing_' . ($i + 1) . '_' . time(),
                $minioConfig
            );

            if ($resultado['success']) {
                $imagenesExistentesConservadas[] = ['filename' => $resultado['filename'], 'url' => $resultado['url'], 'order' => count($imagenesSubidas) + $i + 1];
            } else {
                $erroresImagenes[] = "Error al conservar imagen existente " . ($i + 1) . ": " . $resultado['message'];
            }
        }
    }

    // Combinar todas las imágenes (nuevas + existentes conservadas)
    $todasLasImagenes = array_merge($imagenesSubidas, $imagenesExistentesConservadas);

    // Validar que haya al menos una imagen
    if (empty($todasLasImagenes)) {
        echo '<div class="alert alert-danger" role="alert">Error: Debes tener al menos una imagen.</div>';
        exit();
    }

    // SIEMPRE eliminar todas las imágenes anteriores de la galería
    $eliminarResult = eliminarImagenesDeGallery($establecimiento_id, $_SESSION['token']);

    if (!$eliminarResult['success']) {
        // Si falla la eliminación, limpiar las nuevas imágenes subidas y mostrar error
        foreach ($todasLasImagenes as $imagen) {
            eliminarImagenDeMinio($imagen['filename'], $minioConfig);
        }
        echo '<div class="alert alert-danger" role="alert">Error al eliminar imágenes anteriores de la galería. Código de error: ' . $eliminarResult['httpCode'] . '</div>';
        exit();
    }

    // Actualizar establecimiento
    $url = 'http://' . $_ENV['SERVER_IP'] . ':' . $_ENV['DATABASE_PORT'] . '/rest/v1/establecimiento?id=eq.' . $establecimiento_id;
    $ch = curl_init($url);
    $data = [
        'host_id' => $host_id,
        'nombre' => $nombre,
        'descripcion' => $descripcion,
        'has_parking' => $has_parking,
        'parking_price' => $parking_price,
        'has_wifi' => $has_wifi,
        'wifi_price' => $wifi_price,
        'direccion' => $direccion,
        'localidad' => $localidad,
        'provincia' => $provincia,
        'piso' => $piso,
        'codigo_postal' => $codigo_postal,
        'latitude' => $latitude,
        'longitude' => $longitude
    ];

    $payload = json_encode($data);

    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'apikey: ' . $_ENV['DATABASE_APIKEY'],
        'Authorization: Bearer ' . $_SESSION['token'],
        'Prefer: return=minimal'
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 204) {
        // SIEMPRE insertar las nuevas imágenes en la galería
        $galleryResult = insertarImagenesEnGallery($establecimiento_id, $todasLasImagenes, $_SESSION['token']);

        if (!$galleryResult['success']) {
            $erroresImagenes[] = "Error al insertar nuevas imágenes en gallery: HTTP " . $galleryResult['httpCode'];
        }

        // Mensaje de éxito
        $_SESSION[empty($erroresImagenes) ? 'success_message' : 'warning_message'] =
            empty($erroresImagenes) ? 'Establecimiento actualizado exitosamente' :
            'Establecimiento actualizado, pero hubo problemas con las imágenes: ' . implode(', ', $erroresImagenes);

        header("Location: verEstablecimientos.php");


        exit();
    } else if ($httpCode === 401) {
        header("Location: logoutHost.php");
        exit();
    } else {
        // Si falla la actualización, limpiar imágenes subidas
        foreach ($imagenesSubidas as $imagen) {
            eliminarImagenDeMinio($imagen['filename'], $minioConfig);
        }
        echo '<div class="alert alert-danger" role="alert">Error al actualizar el establecimiento. Código de error: ' . $httpCode . '</div>';
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
    <title>Editar Establecimiento</title>
    <style>
        body {
            font-family: 'Nunito', sans-serif;
            background-color: #f8f9fa;
            padding-bottom: 50px;
        }

        .custom-toast {
            border-radius: 10px;
            font-family: 'Nunito', sans-serif;
            z-index: 10500;
        }

        .contenedor-principal {
            max-width: 800px;
            margin: 2rem auto;
            padding: 0 15px;
        }

        .header-container {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            margin-bottom: 2rem;
        }

        .form-card {
            background-color: white;
            border-radius: 15px;
            box-shadow: 0 .5rem 1rem rgba(0, 0, 0, .15);
            padding: 2rem;
            margin-bottom: 2rem;
        }

        .form-section {
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #e9ecef;
        }

        .form-section:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }

        .section-title {
            font-weight: 700;
            margin-bottom: 1.5rem;
            color: #28a745;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-label {
            font-weight: 600;
        }

        .form-control,
        .form-select {
            border-radius: 10px;
            padding: 0.75rem;
        }

        .form-check-input {
            width: 1.2em;
            height: 1.2em;
        }

        .map-container {
            height: 400px;
            border-radius: 10px;
            overflow: hidden;
            margin: 15px 0;
        }

        .price-input-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .btn-action {
            border-radius: 50px;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
            transition: all 0.3s;
        }

        .btn-primary {
            background-color: #28a745;
            border: none;
        }

        .btn-primary:hover {
            background-color: #218838;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .btn-secondary {
            background-color: #6c757d;
            border: none;
        }

        .btn-secondary:hover {
            background-color: #5a6268;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .buttons-container {
            display: flex;
            justify-content: space-between;
            margin-top: 2rem;
        }

        .alert {
            border-radius: 10px;
        }

        .required-field::after {
            content: "*";
            color: red;
            margin-left: 4px;
        }

        .location-buttons {
            display: flex;
            gap: 10px;
            margin-bottom: 1rem;
        }

        .btn-location {
            background-color: #f8f9fa;
            border: 1px solid #ced4da;
            color: #212529;
            border-radius: 10px;
            padding: 0.5rem 1rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 5px;
            transition: all 0.3s;
        }

        .btn-location:hover {
            background-color: #e9ecef;
        }

        .btn-location.active {
            background-color: #28a745;
            border-color: #28a745;
            color: white;
        }

        @media (max-width: 767px) {
            .form-card {
                padding: 1.5rem;
            }

            .buttons-container {
                flex-direction: column;
                gap: 15px;
            }

            .btn-action {
                width: 100%;
            }
        }

        .image-upload-container {
            border: 2px dashed #ced4da;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            margin-bottom: 15px;
            transition: all 0.3s;
        }

        .image-upload-container:hover {
            border-color: #28a745;
            background-color: #f8f9fa;
        }

        .image-upload-container.dragover {
            border-color: #28a745;
            background-color: #e6f7e6;
        }

        .image-preview {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-top: 15px;
        }

        .preview-item {
            position: relative;
            border-radius: 10px;
            overflow: hidden;
            border: 2px solid #e9ecef;
        }

        .preview-item img {
            width: 120px;
            height: 120px;
            object-fit: cover;
            display: block;
        }

        .preview-item .remove-btn {
            position: absolute;
            top: 5px;
            right: 5px;
            background: rgba(255, 0, 0, 0.8);
            color: white;
            border: none;
            border-radius: 50%;
            width: 25px;
            height: 25px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
        }

        .preview-item .remove-btn:hover {
            background: rgba(255, 0, 0, 1);
        }

        .upload-btn {
            background-color: #28a745;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
        }

        .upload-btn:hover {
            background-color: #218838;
            transform: translateY(-2px);
        }

        .file-input {
            display: none;
        }

        .image-counter {
            color: #6c757d;
            font-size: 14px;
            margin-top: 10px;
        }
    </style>
</head>

<body>

    <div class="position-fixed top-0 end-0 p-3" style="z-index: 10500">
        <div id="liveToast" class="toast align-items-center text-white border-0 custom-toast" role="alert"
            aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body fw-bold" id="toastMessage"></div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    </div>

    <div class="modal fade" id="deleteImageModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius: 15px; border: none;">
                <div class="modal-header" style="border-bottom: none;">
                    <h5 class="modal-title fw-bold">¿Eliminar imagen?</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <p>¿Estás seguro de que quieres eliminar esta imagen?</p>
                </div>
                <div class="modal-footer d-flex justify-content-center" style="border-top: none;">
                    <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal"
                        style="border-radius: 10px;">Cancelar</button>
                    <button type="button" id="btn-confirmar-eliminar-imagen" class="btn btn-danger px-4"
                        style="border-radius: 10px;">Sí, eliminar</button>
                </div>
            </div>
        </div>
    </div>

    <div class="contenedor-principal">
        <div class="header-container">
            <h1 class="fw-bold mb-4">Editar Establecimiento</h1>
        </div>

        <form id="establecimiento-form" method="POST" class="needs-validation" enctype="multipart/form-data" novalidate>
            <div class="form-card">
                <div class="form-section">
                    <h3 class="section-title">
                        <i class="fas fa-info-circle"></i> Información General
                    </h3>

                    <div class="mb-3">
                        <label for="nombre" class="form-label required-field">Nombre del establecimiento</label>
                        <input type="text" class="form-control" id="nombre" name="nombre"
                            value="<?php echo htmlspecialchars($establecimiento['nombre']); ?>" required>
                        <div class="invalid-feedback">Por favor, introduce un nombre para el establecimiento.</div>
                    </div>

                    <div class="mb-3">
                        <label for="descripcion" class="form-label required-field">Descripción</label>
                        <textarea class="form-control" id="descripcion" name="descripcion" rows="3"
                            required><?php echo htmlspecialchars($establecimiento['descripcion']); ?></textarea>
                        <div class="invalid-feedback">Por favor, introduce una descripción.</div>
                    </div>
                </div>

                <div class="form-section">
                    <h3 class="section-title">
                        <i class="fas fa-images"></i> Imágenes
                    </h3>

                    <div class="image-upload-container" id="uploadContainer">
                        <i class="fas fa-cloud-upload-alt fa-3x text-muted mb-3"></i>
                        <p class="mb-3">Arrastra tus imágenes aquí o haz clic para seleccionar</p>
                        <button type="button" class="upload-btn"
                            onclick="document.getElementById('imageFiles').click()">
                            <i class="fas fa-plus"></i>
                            Seleccionar Imágenes
                        </button>
                        <input type="file" id="imageFiles" class="file-input" multiple
                            accept=".tiff,.jfif,.bmp,.pjp,.apng,.webp,.svgz,.heic,.gif,.svg,.heif,.ico,.xbm,.dib,.tif,.pjpeg,.avif,.jpg,.jpeg,.png">
                        <div class="image-counter" id="imageCounter">0 de 5 imágenes seleccionadas</div>
                    </div>

                    <div class="image-preview" id="imagePreview"></div>

                    <input type="file" name="imagen" id="imagen" style="display: none;">
                    <input type="file" name="imagen2" id="imagen2" style="display: none;">
                    <input type="file" name="imagen3" id="imagen3" style="display: none;">
                    <input type="file" name="imagen4" id="imagen4" style="display: none;">
                    <input type="file" name="imagen5" id="imagen5" style="display: none;">
                    <input type="hidden" name="existing_images_count" id="existing_images_count" value="0">
                </div>

                <div class="form-section">
                    <h3 class="section-title">
                        <i class="fas fa-concierge-bell"></i> Servicios
                    </h3>

                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="has_wifi" name="has_wifi" <?php echo $establecimiento['has_wifi'] ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="has_wifi">
                                <i class="fas fa-wifi me-1"></i> Ofrece WiFi
                            </label>
                        </div>

                        <div id="wifi-price-container"
                            class="mt-3 ms-4 <?php echo $establecimiento['has_wifi'] ? '' : 'd-none'; ?>">
                            <label for="wifi_price" class="form-label">Precio WiFi (€/hora)</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-euro-sign"></i></span>
                                <input type="number" class="form-control" id="wifi_price" name="wifi_price" step="0.01"
                                    min="0" value="<?php echo htmlspecialchars($establecimiento['wifi_price']); ?>">
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="has_parking" name="has_parking" <?php echo $establecimiento['has_parking'] ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="has_parking">
                                <i class="fas fa-parking me-1"></i> Ofrece Parking
                            </label>
                        </div>

                        <div id="parking-price-container"
                            class="mt-3 ms-4 <?php echo $establecimiento['has_parking'] ? '' : 'd-none'; ?>">
                            <label for="parking_price" class="form-label">Precio Parking (€/día)</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-euro-sign"></i></span>
                                <input type="number" class="form-control" id="parking_price" name="parking_price"
                                    step="0.01" min="0"
                                    value="<?php echo htmlspecialchars($establecimiento['parking_price']); ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h3 class="section-title">
                        <i class="fas fa-map-marker-alt"></i> Dirección
                    </h3>

                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <label for="direccion" class="form-label required-field">Calle</label>
                            <input type="text" class="form-control" id="direccion" name="direccion"
                                value="<?php echo htmlspecialchars($calle); ?>" required>
                            <div class="invalid-feedback">Por favor, introduce la calle.</div>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label for="numero" class="form-label required-field">Número</label>
                            <input type="text" class="form-control" id="numero" name="numero"
                                value="<?php echo htmlspecialchars($numero); ?>" required>
                            <div class="invalid-feedback">Por favor, introduce el número.</div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="piso" class="form-label">Piso/Puerta (opcional)</label>
                        <input type="text" class="form-control" id="piso" name="piso"
                            value="<?php echo htmlspecialchars($establecimiento['piso'] ?? ''); ?>">
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="codigo_postal" class="form-label required-field">Código Postal</label>
                            <input type="text" class="form-control" id="codigo_postal" name="codigo_postal"
                                value="<?php echo htmlspecialchars($establecimiento['codigo_postal']); ?>" required>
                            <div class="invalid-feedback">Por favor, introduce el código postal.</div>
                        </div>

                        <div class="col-md-8 mb-3">
                            <label for="localidad" class="form-label required-field">Localidad</label>
                            <input type="text" class="form-control" id="localidad" name="localidad"
                                value="<?php echo htmlspecialchars($establecimiento['localidad']); ?>" required>
                            <div class="invalid-feedback">Por favor, introduce la localidad.</div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="provincia" class="form-label required-field">Provincia</label>
                        <input type="text" class="form-control" id="provincia" name="provincia"
                            value="<?php echo htmlspecialchars($establecimiento['provincia']); ?>" required>
                        <div class="invalid-feedback">Por favor, introduce la provincia.</div>
                    </div>
                </div>

                <div class="form-section">
                    <h3 class="section-title">
                        <i class="fas fa-map"></i> Ubicación en el Mapa
                    </h3>

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
                            <input type="text" class="form-control" id="latitude" name="latitude"
                                value="<?php echo htmlspecialchars($establecimiento['latitude']); ?>" required readonly>
                            <div class="invalid-feedback">Por favor, selecciona una ubicación en el mapa.</div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="longitude" class="form-label required-field">Longitud</label>
                            <input type="text" class="form-control" id="longitude" name="longitude"
                                value="<?php echo htmlspecialchars($establecimiento['longitude']); ?>" required
                                readonly>
                            <div class="invalid-feedback">Por favor, selecciona una ubicación en el mapa.</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="buttons-container">
                <a href="verEstablecimientos.php" class="btn btn-secondary btn-action">
                    <i class="fas fa-arrow-left"></i> Volver
                </a>
                <button type="submit" class="btn btn-primary btn-action">
                    <i class="fas fa-save"></i> Confirmar Datos
                </button>
            </div>
        </form>
    </div>

    <script>
        const MAPBOX_ACCESS_TOKEN = "pk.eyJ1IjoiYW5kcnplamJhbmFzIiwiYSI6ImNrcHdrZXIyYTAyZWkyb3AwNGtpbmtrbXYifQ.PN_iZ4Mh08-V5EXHAHpCSg";
        let map;
        let marker;
        let selectedFiles = [];
        const MAX_FILES = 5;

        const establecimientoLat = <?php echo $establecimiento['latitude']; ?>;
        const establecimientoLng = <?php echo $establecimiento['longitude']; ?>;

        document.addEventListener('DOMContentLoaded', function () {
            initMap();
            setupFormValidation();
            setupServiceCheckboxes();
            setupMapButtons();
            setupImageUpload();
            setupPostalCodeAutocompletion();
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
            } else if (tipo === 'warning') {
                toastEl.classList.add('bg-warning');
                mensaje = '⚠️ ' + mensaje;
            }

            toastMessage.textContent = mensaje;
            const toast = new bootstrap.Toast(toastEl, { delay: 3500 });
            toast.show();
        }

        function initMap() {
            mapboxgl.accessToken = MAPBOX_ACCESS_TOKEN;
            map = new mapboxgl.Map({
                container: 'map',
                style: 'mapbox://styles/mapbox/streets-v11',
                center: [establecimientoLng, establecimientoLat],
                zoom: 15
            });

            map.addControl(new mapboxgl.NavigationControl(), 'top-right');

            map.on('click', function (e) {
                addMarker(e.lngLat.lng, e.lngLat.lat);
            });

            map.on('load', function () {
                addMarker(establecimientoLng, establecimientoLat);
            });
        }

        function addMarker(lng, lat) {
            if (marker) {
                marker.remove();
            }

            const el = document.createElement('div');
            el.className = 'marker';
            el.style.backgroundImage = `url('../img/posicionAnfitrion.png')`;
            el.style.width = '40px';
            el.style.height = '40px';
            el.style.backgroundSize = '100%';

            marker = new mapboxgl.Marker(el)
                .setLngLat([lng, lat])
                .addTo(map);

            document.getElementById('latitude').value = lat.toFixed(6);
            document.getElementById('longitude').value = lng.toFixed(6);

            map.flyTo({
                center: [lng, lat],
                zoom: 15
            });
        }

        function getCurrentLocation() {
            if (navigator.geolocation) {
                document.getElementById('btn-current-location').innerHTML = '<i class="fas fa-spinner fa-spin"></i> Obteniendo ubicación...';

                navigator.geolocation.getCurrentPosition(
                    function (position) {
                        const lat = position.coords.latitude;
                        const lng = position.coords.longitude;

                        addMarker(lng, lat);

                        document.getElementById('btn-current-location').innerHTML = '<i class="fas fa-location-arrow"></i> Usar mi ubicación actual';
                        document.getElementById('btn-current-location').classList.add('active');
                        document.getElementById('btn-click-map').classList.remove('active');
                    },
                    function (error) {
                        let errorMessage;
                        switch (error.code) {
                            case error.PERMISSION_DENIED:
                                errorMessage = "No has dado permiso para acceder a tu ubicación.";
                                break;
                            case error.POSITION_UNAVAILABLE:
                                errorMessage = "La información de ubicación no está disponible.";
                                break;
                            case error.TIMEOUT:
                                errorMessage = "Se agotó el tiempo de espera al solicitar tu ubicación.";
                                break;
                            case error.UNKNOWN_ERROR:
                                errorMessage = "Ha ocurrido un error desconocido.";
                                break;
                        }

                        mostrarNotificacion(errorMessage, "error");
                        document.getElementById('btn-current-location').innerHTML = '<i class="fas fa-location-arrow"></i> Usar mi ubicación actual';
                    }
                );
            } else {
                mostrarNotificacion("Tu navegador no soporta geolocalización.", "error");
            }
        }

        function setupMapButtons() {
            document.getElementById('btn-current-location').addEventListener('click', function () {
                getCurrentLocation();
            });

            document.getElementById('btn-click-map').addEventListener('click', function () {
                document.getElementById('btn-click-map').classList.add('active');
                document.getElementById('btn-current-location').classList.remove('active');
            });
        }

        function setupServiceCheckboxes() {
            document.getElementById('has_wifi').addEventListener('change', function () {
                document.getElementById('wifi-price-container').classList.toggle('d-none', !this.checked);
                if (this.checked) {
                    document.getElementById('wifi_price').setAttribute('required', '');
                } else {
                    document.getElementById('wifi_price').removeAttribute('required');
                }
            });

            document.getElementById('has_parking').addEventListener('change', function () {
                document.getElementById('parking-price-container').classList.toggle('d-none', !this.checked);
                if (this.checked) {
                    document.getElementById('parking_price').setAttribute('required', '');
                } else {
                    document.getElementById('parking_price').removeAttribute('required');
                }
            });
        }

        function setupImageUpload() {
            const container = document.getElementById('uploadContainer');
            const fileInput = document.getElementById('imageFiles');
            const preview = document.getElementById('imagePreview');
            const counter = document.getElementById('imageCounter');

            // Cargar imágenes existentes al inicio
            loadExistingImages();

            // Drag & Drop events
            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                container.addEventListener(eventName, preventDefaults, false);
                document.body.addEventListener(eventName, preventDefaults, false);
            });

            ['dragenter', 'dragover'].forEach(eventName => {
                container.addEventListener(eventName, () => container.classList.add('dragover'), false);
            });

            ['dragleave', 'drop'].forEach(eventName => {
                container.addEventListener(eventName, () => container.classList.remove('dragover'), false);
            });

            container.addEventListener('drop', handleDrop, false);

            function preventDefaults(e) {
                e.preventDefault();
                e.stopPropagation();
            }

            function handleDrop(e) {
                const dt = e.dataTransfer;
                const files = dt.files;
                handleFiles(Array.from(files));
            }

            // File input change
            fileInput.addEventListener('change', (e) => {
                handleFiles(Array.from(e.target.files));
            });

            function handleFiles(files) {
                const imageFiles = files.filter(file => file.type.startsWith('image/'));
                const existingCount = document.querySelectorAll('.existing-image').length;
                const available = MAX_FILES - selectedFiles.length - existingCount;

                if (imageFiles.length === 0) {
                    mostrarNotificacion('Por favor, selecciona solo archivos de imagen.', 'error');
                    return;
                }

                if (imageFiles.length > available) {
                    mostrarNotificacion(`Solo puedes añadir ${available} imagen(es) más.`, 'warning');
                }

                imageFiles.slice(0, available).forEach(file => {
                    selectedFiles.push(file);
                    createPreview(file, selectedFiles.length - 1);
                });

                updateImageUI();
                fileInput.value = '';
            }

            function createPreview(file, index) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    const div = document.createElement('div');
                    div.className = 'preview-item new-image';
                    div.dataset.index = index;
                    div.innerHTML = `
                <img src="${e.target.result}" alt="Preview ${index + 1}">
                <button type="button" class="remove-btn" onclick="removeImage(${index})">
                    <i class="fas fa-times"></i>
                </button>
            `;
                    preview.appendChild(div);
                };
                reader.readAsDataURL(file);
            }

            // Cargar imágenes existentes

            function loadExistingImages() {
                const existingImages = <?php echo json_encode($imagenes_existentes ?? []); ?>;
                const publicDomain = "<?php echo rtrim($_ENV['MINIO_PUBLIC_URL'], '/'); ?>";

                if (existingImages && existingImages.length > 0) {
                    existingImages.forEach((imagen, index) => {

                        let imageUrl = imagen.image_url;

                        // Limpieza robusta para evitar "http://https://"
                        try {
                            let tempUrl = imageUrl.startsWith('http') ? imageUrl : 'http://' + imageUrl;
                            let urlObj = new URL(tempUrl);
                            imageUrl = publicDomain + urlObj.pathname;
                        } catch (e) {
                            if (!imageUrl.startsWith('http')) {
                                imageUrl = 'https://' + imageUrl;
                            }
                        }

                        // Crear elemento de preview para imagen existente
                        const div = document.createElement('div');
                        div.className = 'preview-item existing-image';
                        div.dataset.imageId = imagen.id || index;
                        div.innerHTML = `
        <img src="${imageUrl}" alt="Imagen existente ${index + 1}">
        <button type="button" class="remove-btn" onclick="removeExistingImage('${imagen.id || index}', this)">
            <i class="fas fa-times"></i>
        </button>
    `;
                        preview.appendChild(div);
                    });
                }

                updateImageUI();
            }

            function updateImageUI() {
                const existingImages = document.querySelectorAll('.existing-image').length;
                const totalImages = existingImages + selectedFiles.length;

                // NUEVO: Actualizar el contador de imágenes existentes
                document.getElementById('existing_images_count').value = existingImages;

                counter.textContent = `${totalImages} de ${MAX_FILES} imágenes`;
                if (existingImages > 0) {
                    counter.textContent += ` (${existingImages} existentes)`;
                }

                const uploadBtn = container.querySelector('.upload-btn');
                const isDisabled = totalImages >= MAX_FILES;

                if (isDisabled) {
                    container.style.opacity = '0.6';
                    uploadBtn.style.pointerEvents = 'none';
                    uploadBtn.innerHTML = '<i class="fas fa-check"></i> Máximo de imágenes alcanzado';
                } else {
                    container.style.opacity = '1';
                    uploadBtn.style.pointerEvents = 'auto';
                    uploadBtn.innerHTML = '<i class="fas fa-plus"></i> Seleccionar Imágenes';
                }

                updateHiddenInputs();
            }

            function updateHiddenInputs() {
                // Limpiar todos los inputs ocultos
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

                // Asignar archivos a los inputs correspondientes
                selectedFiles.forEach((file, index) => {
                    const inputId = index === 0 ? 'imagen' : `imagen${index + 1}`;
                    const input = document.getElementById(inputId);
                    if (input) {
                        const dt = new DataTransfer();
                        dt.items.add(file);
                        input.files = dt.files;
                    }
                });

                // NUEVO: Crear inputs ocultos para las imágenes existentes que se mantienen
                const existingImagesContainer = document.getElementById('existing-images-container');
                if (existingImagesContainer) {
                    existingImagesContainer.remove();
                }

                const container = document.createElement('div');
                container.id = 'existing-images-container';
                container.style.display = 'none';

                document.querySelectorAll('.existing-image').forEach((item, index) => {
                    const imageId = item.dataset.imageId;
                    const img = item.querySelector('img');
                    const imageUrl = img.src;

                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = `existing_image_${index}`;
                    input.value = imageUrl;
                    container.appendChild(input);
                });

                document.querySelector('form').appendChild(container);
            }

            // Función global para remover imagen nueva
            window.removeImage = function (index) {
                selectedFiles.splice(index, 1);

                // Remover solo las nuevas imágenes del preview
                document.querySelectorAll('.new-image').forEach(el => el.remove());

                // Recrear previews con índices actualizados
                selectedFiles.forEach((file, i) => {
                    createPreview(file, i);
                });

                updateImageUI();
            };

            // Función global para remover imagen existente con MODAL
            window.removeExistingImage = function (imageId, buttonElement) {
                const deleteImageModal = new bootstrap.Modal(document.getElementById('deleteImageModal'));

                const btnConfirmar = document.getElementById('btn-confirmar-eliminar-imagen');
                const nuevoBtn = btnConfirmar.cloneNode(true);
                btnConfirmar.parentNode.replaceChild(nuevoBtn, btnConfirmar);

                nuevoBtn.addEventListener('click', function () {
                    const previewItem = buttonElement.closest('.preview-item');
                    if (previewItem) previewItem.remove();
                    updateImageUI();
                    deleteImageModal.hide();
                });

                deleteImageModal.show();
            };
        }

        function setupFormValidation() {
            const form = document.getElementById('establecimiento-form');
            form.addEventListener('submit', function (event) {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }

                form.classList.add('was-validated');
            });
        }

        function setupPostalCodeAutocompletion() {
            document.getElementById('codigo_postal').addEventListener('blur', function () {
                const codigoPostal = this.value.trim();

                if (codigoPostal.length === 5 && /^\d+$/.test(codigoPostal)) {
                    const geocodingUrl = `https://api.mapbox.com/geocoding/v5/mapbox.places/${codigoPostal}.json?country=es&types=postcode&access_token=${MAPBOX_ACCESS_TOKEN}`;

                    fetch(geocodingUrl)
                        .then(response => response.json())
                        .then(data => {
                            if (data.features && data.features.length > 0) {
                                const feature = data.features[0];
                                const context = feature.context || [];

                                let localidad = '';
                                let provincia = '';

                                context.forEach(item => {
                                    if (item.id.startsWith('place')) {
                                        localidad = item.text;
                                    } else if (item.id.startsWith('region')) {
                                        provincia = item.text;
                                    }
                                });

                                if (!localidad && feature.text) {
                                    localidad = feature.text;
                                }

                                if (localidad) {
                                    document.getElementById('localidad').value = localidad;
                                }

                                if (provincia) {
                                    document.getElementById('provincia').value = provincia;
                                }

                                if (feature.center) {
                                    const [lng, lat] = feature.center;
                                    addMarker(lng, lat);
                                }
                            }
                        })
                        .catch(error => {
                            console.error('Error al obtener información del código postal:', error);
                        });
                }
            });
        }

        function handleFormSubmit(event) {
            event.preventDefault();
            event.stopPropagation();

            let isValid = true;

            // Validar campos requeridos
            this.querySelectorAll('[required]').forEach(field => {
                const valid = field.value.trim();
                field.classList.toggle('is-invalid', !valid);
                field.classList.toggle('is-valid', valid);
                if (!valid) isValid = false;
            });

            // Validar imágenes
            const existingCount = document.querySelectorAll('.existing-image').length;
            if (selectedFiles.length === 0 && existingCount === 0) {
                mostrarNotificacion('Debes subir o mantener al menos una imagen del establecimiento.', 'error');
                isValid = false;
            }

            // Validar coordenadas
            const lat = document.getElementById('latitude');
            const lng = document.getElementById('longitude');

            if (!lat.value || !lng.value) {
                mostrarNotificacion('Por favor, selecciona una ubicación en el mapa.', 'error');
                lat.classList.add('is-invalid');
                lng.classList.add('is-invalid');
                isValid = false;
            }

            this.classList.add('was-validated');

            if (isValid) {
                const btn = this.querySelector('button[type="submit"]');
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
                btn.disabled = true;
                this.submit();
            }
        }

        // Adjuntamos la validación del submit de forma segura al form
        document.getElementById('establecimiento-form').addEventListener('submit', handleFormSubmit);
    </script>
</body>

</html>