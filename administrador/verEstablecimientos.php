<?php
require_once 'verificar_sesion_admin.php';
require_once 'establecimientos_logic.php';

require '../vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

$flashMessage = '';
$flashType = '';

// --- CONFIGURACIÓN Y FUNCIONES MINIO ---
$minioConfig = [
    'host' => rtrim($_ENV['MINIO_PUBLIC_URL'], '/'),
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
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_HTTPHEADER => [
            'Content-Type: ' . $config['mimeTypes'][$extension],
            'Content-Length: ' . strlen($fileContent)
        ]
    ]);
    $resultado = curl_exec($ch);
    $codigoRespuesta = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return ($codigoRespuesta === 200 || $codigoRespuesta === 201) ? ['success' => true, 'url' => $minioUrl, 'filename' => $nombreArchivo] : ['success' => false, 'message' => 'Error MinIO'];
}

function descargarImagenDesdeUrl($url, $nombreArchivo, $config)
{
    $context = stream_context_create(['http' => ['timeout' => 30], 'ssl' => ['verify_peer' => false, 'verify_peer_name' => false]]);
    $imageContent = file_get_contents($url, false, $context);
    if ($imageContent === false)
        return ['success' => false, 'message' => 'No se pudo descargar'];

    $extension = strtolower(pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION));
    if (!$extension) {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->buffer($imageContent);
        $extension = array_search($mimeType, $config['mimeTypes']) ?: 'jpg';
    }

    $nombreArchivoCompleto = $nombreArchivo . '.' . $extension;
    $minioUrl = $config['host'] . '/' . $config['bucket'] . '/' . $nombreArchivoCompleto;

    $ch = curl_init($minioUrl);
    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST => 'PUT',
        CURLOPT_POSTFIELDS => $imageContent,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_HTTPHEADER => ['Content-Type: ' . $config['mimeTypes'][$extension], 'Content-Length: ' . strlen($imageContent)]
    ]);
    $resultado = curl_exec($ch);
    $codigoRespuesta = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return ($codigoRespuesta === 200 || $codigoRespuesta === 201) ? ['success' => true, 'url' => $minioUrl, 'filename' => $nombreArchivoCompleto] : ['success' => false];
}

function eliminarImagenDeMinio($filename, $config)
{
    $ch = curl_init($config['host'] . '/' . $config['bucket'] . '/' . $filename);
    curl_setopt_array($ch, [CURLOPT_CUSTOMREQUEST => 'DELETE', CURLOPT_RETURNTRANSFER => true, CURLOPT_SSL_VERIFYPEER => false, CURLOPT_SSL_VERIFYHOST => false]);
    curl_exec($ch);
    curl_close($ch);
}

function eliminarImagenesDeGallery($establecimientoId, $token)
{
    $ch = curl_init('http://' . $_ENV['SERVER_IP'] . ':' . $_ENV['DATABASE_PORT'] . '/rest/v1/gallery?establecimiento_id=eq.' . $establecimientoId);
    curl_setopt_array($ch, [CURLOPT_CUSTOMREQUEST => 'DELETE', CURLOPT_RETURNTRANSFER => true, CURLOPT_HTTPHEADER => ['apikey: ' . $_ENV['SERVICE_APIKEY'], 'Authorization: Bearer ' . $_ENV['SERVICE_APIKEY']]]);
    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['success' => ($httpCode === 204), 'httpCode' => $httpCode];
}

function insertarImagenesEnGallery($establecimientoId, $imagenesSubidas)
{
    $galleryData = array_map(function ($img) use ($establecimientoId) {
        return ['image_url' => $img['url'], 'establecimiento_id' => $establecimientoId];
    }, $imagenesSubidas);

    $ch = curl_init('http://' . $_ENV['SERVER_IP'] . ':' . $_ENV['DATABASE_PORT'] . '/rest/v1/gallery');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'apikey: ' . $_ENV['SERVICE_APIKEY'], 'Authorization: Bearer ' . $_ENV['SERVICE_APIKEY']],
        CURLOPT_POSTFIELDS => json_encode($galleryData)
    ]);
    curl_exec($ch);
    curl_close($ch);
}


// --- LÓGICA DE ELIMINACIÓN SEGURA ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_establecimiento') {
    $deleteId = $_POST['delete_id'] ?? '';

    if (!empty($deleteId)) {
        // Opcional: Eliminar las fotos asociadas en MinIO y Gallery antes de borrar el local
        eliminarImagenesDeGallery($deleteId, $_SESSION['token']);

        $urlDelete = 'http://' . $_ENV['SERVER_IP'] . ':' . $_ENV['DATABASE_PORT'] . '/rest/v1/establecimiento?id=eq.' . rawurlencode($deleteId);
        
        $chDelete = curl_init($urlDelete);
        curl_setopt_array($chDelete, [
            CURLOPT_CUSTOMREQUEST => 'DELETE',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'apikey: ' . $_ENV['SERVICE_APIKEY'],
                'Authorization: Bearer ' . $_ENV['SERVICE_APIKEY']
            ]
        ]);
        curl_exec($chDelete);
        $codeDelete = curl_getinfo($chDelete, CURLINFO_HTTP_CODE);
        curl_close($chDelete);

        if ($codeDelete >= 200 && $codeDelete < 300) {
            header('Location: verEstablecimientos.php?msg=deleted');
            exit;
        } else {
            header('Location: verEstablecimientos.php?msg=delete_error');
            exit;
        }
    }
}

// ------------------------------------

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_establecimiento') {
    $id = $_POST['id'] ?? '';

    if (!empty($id)) {
        $imagenesSubidas = [];
        $erroresImagenes = [];
        $camposImagen = ['imagen', 'imagen2', 'imagen3', 'imagen4', 'imagen5'];

        foreach ($camposImagen as $index => $campo) {
            if (isset($_FILES[$campo]) && $_FILES[$campo]['error'] === UPLOAD_ERR_OK) {
                $extension = strtolower(pathinfo($_FILES[$campo]['name'], PATHINFO_EXTENSION));
                $nombreArchivo = 'establecimiento_' . $id . '_' . ($index + 1) . '_' . time() . '.' . $extension;
                $resultado = subirImagenAMinio($_FILES[$campo], $nombreArchivo, $minioConfig);

                if ($resultado['success']) {
                    $imagenesSubidas[] = ['filename' => $resultado['filename'], 'url' => $resultado['url']];
                } else {
                    $erroresImagenes[] = "Error en imagen " . ($index + 1) . ": " . $resultado['message'];
                }
            }
        }

        $imagenesExistentesConservadas = [];
        $existingImagesCount = intval($_POST['existing_images_count'] ?? 0);

        for ($i = 0; $i < $existingImagesCount; $i++) {
            $existingImageUrl = $_POST["existing_image_$i"] ?? '';
            if (!empty($existingImageUrl)) {
                $parsedUrl = parse_url($existingImageUrl);
                $pathParts = explode('/', $parsedUrl['path']);
                $oldFilename = end($pathParts);

                $resultado = descargarImagenDesdeUrl($existingImageUrl, 'establecimiento_' . $id . '_existing_' . ($i + 1) . '_' . time(), $minioConfig);
                if ($resultado['success']) {
                    $imagenesExistentesConservadas[] = ['filename' => $resultado['filename'], 'url' => $resultado['url']];
                }
            }
        }

        $todasLasImagenes = array_merge($imagenesSubidas, $imagenesExistentesConservadas);

        if (!empty($todasLasImagenes)) {
            eliminarImagenesDeGallery($id, $_SESSION['token']);
            insertarImagenesEnGallery($id, $todasLasImagenes);
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
            header('Location: verEstablecimientos.php?msg=updated');
            exit;
        } else {
            $errorUpdate = json_decode($responseUpdate, true);
            $flashMessage = 'No se pudo actualizar. ' . htmlspecialchars($errorUpdate['message'] ?? 'Intenta de nuevo.');
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
            CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $_ENV['SERVICE_APIKEY'], 'apikey: ' . $_ENV['SERVICE_APIKEY']],
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

// PRECARGAMOS LAS GALERÍAS PARA EL HTML Y JS
$galeriasJSON = "{}";
$galeriasPorEst = [];
if (!empty($establecimientos)) {
    $estIds = array_column($establecimientos, 'id');
    $urlGallery = "http://" . $_ENV['SERVER_IP'] . ":" . $_ENV['DATABASE_PORT'] . "/rest/v1/gallery?establecimiento_id=in.(" . implode(',', $estIds) . ")";
    $chG = curl_init($urlGallery);
    curl_setopt_array($chG, [
        CURLOPT_HTTPHEADER => ['apikey: ' . $_ENV['SERVICE_APIKEY'], 'Authorization: Bearer ' . $_ENV['SERVICE_APIKEY']],
        CURLOPT_RETURNTRANSFER => true
    ]);
    $resG = curl_exec($chG);
    curl_close($chG);

    $galeriasData = json_decode($resG, true) ?? [];
    if (!empty($galeriasData) && is_array($galeriasData)) {
        foreach ($galeriasData as $g) {
            $galeriasPorEst[$g['establecimiento_id']][] = $g;
        }
    }
    $galeriasJSON = json_encode($galeriasPorEst);
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
    <title>Establecimientos Globales - Admin</title>
    <style>
        :root {
            --brand-ink: #1f2933;
            --brand-deep: #0f4c5c;
            --brand-accent: #dc3545;
            --brand-soft: #f3f5f7;
            --card-radius: 16px;
            --primary-color: #dc3545;
            --bg: #f4f7fb;
            --accent-dark: #8c1c13;
            --accent-mid: #c44536;
        }

        body {
            font-family: 'Nunito', sans-serif;
            background: #eef2f5;
            padding-bottom: 120px;
            color: var(--brand-ink);
        }

        .page-shell {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 15px;
            box-sizing: border-box;
        }

        
        .bloque-margen-general {
            margin-left: 2.5rem;
            margin-right: 2.5rem;
        }

       
        @media (max-width: 768px) {
            .bloque-margen-general {
                margin-left: 0.5rem;
                margin-right: 0.5rem;
            }
        }
        /* FILTROS */
        .search-bar-wrapper {
            margin: 0 auto 2rem;
            max-width: 1400px;
            padding: 0 15px;
        }

        .search-bar-container {
            background: white;
            border-radius: 12px;
            padding: 5px 20px;
            display: flex;
            align-items: center;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            border: 1px solid rgba(15, 76, 92, 0.1);
            transition: all 0.3s;
            height: 100%;
        }

        .search-bar-container:focus-within {
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.1);
            border-color: var(--primary-color);
        }

        .search-bar-icon {
            color: var(--primary-color);
            font-size: 1.2rem;
            margin-right: 15px;
        }

        .search-bar-input {
            border: none;
            box-shadow: none;
            font-size: 1.05rem;
            padding: 10px 0;
            background: transparent;
            width: 100%;
            color: #2c3e50;
        }

        .search-bar-input:focus {
            outline: none;
            box-shadow: none;
        }

        .filter-select {
            border-radius: 12px;
            border: 1px solid rgba(15, 76, 92, 0.1);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            padding: 12px 15px;
            color: #2c3e50;
            font-weight: 600;
            height: 100%;
            transition: all 0.3s;
        }

        .filter-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.1);
            outline: none;
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

        /* .page-hero {
            width: 100%;
            margin: 1.2rem 0 0.5rem;
            padding: 0;
            box-sizing: border-box;
        } */

        /* .page-hero-inner {
            border-radius: 20px;
            background: linear-gradient(135deg, var(--accent-dark) 0%, var(--accent-mid) 52%, #df786c 100%);
            color: #ffffff;
            padding: 1.1rem 1.2rem;
            box-shadow: 0 18px 40px rgba(140, 28, 19, 0.24);
            border: 1px solid rgba(255, 255, 255, 0.18);
        } */

        /* .page-hero-title {
            font-size: 1.35rem;
            font-weight: 800;
            letter-spacing: 0.2px;
        } */

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

        /* Estilos DRAG & DROP */
        .image-upload-container {
            border: 2px dashed #ced4da;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            margin-bottom: 15px;
            transition: all 0.3s;
        }

        .image-upload-container:hover {
            border-color: var(--primary-color);
            background-color: #f8f9fa;
        }

        .image-upload-container.dragover {
            border-color: var(--primary-color);
            background-color: #fce8ea;
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
            width: 100px;
            height: 100px;
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
            width: 22px;
            height: 22px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            z-index: 10;
        }

        .upload-btn {
            background-color: var(--primary-color);
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
        }

        .file-input {
            display: none;
        }

        .image-counter {
            color: #6c757d;
            font-size: 13px;
            margin-top: 10px;
        }

        .modal-confirm .modal-content {
            padding: 20px;
            border-radius: 15px;
            border: none;
            text-align: center;
        }

        .no-establecimientos {
            background-color: white;
            border-radius: 18px;
            box-shadow: 0 12px 28px rgba(31, 41, 51, 0.12);
            padding: 2rem;
            text-align: center;
        }

        @media (max-width: 767px) {
            .search-bar-wrapper .row>div {
                margin-bottom: 10px;
            }
        }
    </style>
</head>

<body>
    <div class="page-shell">
    <?php include 'headerAdmin.php'; ?>
    <!-- <section class="page-hero">
            <div class="page-hero-inner">
                <div class="hero-title-row">
                    <div class="page-hero-title"><i class="fas fa-building me-2"></i>Gestión Global de Establecimientos
                    </div>
                </div>
            </div>
        </section> -->

        <?php if (!empty($flashMessage)): ?>
            <div class="mt-3">
                <div class="alert alert-<?php echo $flashType === 'danger' ? 'danger' : 'success'; ?> alert-dismissible fade show"
                    role="alert">
                    <?php echo $flashMessage; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!empty($establecimientos) || $filtro_host_id): ?>
            <div class="search-bar-wrapper mt-4">
                <div class="row g-3">
                    <div class="col-md-8">
                        <div class="search-bar-container">
                            <i class="fas fa-search search-bar-icon"></i>
                            <input type="text" id="searchInputEst" class="search-bar-input"
                                placeholder="Buscar por nombre, localidad, código postal...">
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

        <div class="row mb-4 stats-grid">
            <div class="col-md-4">
                <div class="card text-center py-2">
                    <div class="card-body">
                        <h5 class="card-title text-dark"><?php echo $totalEstablecimientos; ?></h5>
                        <p class="card-text">Total Registrados</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-center py-2">
                    <div class="card-body">
                        <h5 class="card-title text-success"><?php echo $establecimientosAprobados; ?></h5>
                        <p class="card-text">Aprobados</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-center py-2">
                    <div class="card-body">
                        <h5 class="card-title text-warning"><?php echo $establecimientosPendientes; ?></h5>
                        <p class="card-text">Pendientes / Rechazados</p>
                    </div>
                </div>
            </div>
        </div>

        <?php if (empty($establecimientos)): ?>
            <div class="empty-state-container">
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
            <div class="row establecimientos-grid">
                <?php foreach ($establecimientos as $index => $establecimiento):
                    $direccionFormateada = formatearDireccion($establecimiento['direccion'], $establecimiento['piso']);
                    $nombreAnfitrion = $nombresAnfitriones[$establecimiento['host_id']] ?? 'Anfitrión Desconocido';
                    ?>
                    <div class="col-12 col-md-6 col-xl-4 est-card-col"
                        data-host-name="<?php echo htmlspecialchars($nombreAnfitrion); ?>">
                        <div class="establecimiento-card" id="establecimiento-<?php echo $establecimiento['id']; ?>">
                            <div class="card-header<?php echo empty(getImagenUrl($establecimiento['banner_image_url'] ?? $establecimiento['image_url'] ?? '')) ? ' default-image' : ''; ?>"
                                <?php if (!empty(getImagenUrl($establecimiento['banner_image_url'] ?? $establecimiento['image_url'] ?? ''))): ?>
                                    style="background-image: url('<?php echo getImagenUrl($establecimiento['banner_image_url'] ?? $establecimiento['image_url'] ?? ''); ?>');"
                                <?php endif; ?>>
                                <div class="card-header-overlay"></div>
                                <div class="card-title">
                                    <div class="d-flex flex-column">
                                        <span><?php echo htmlspecialchars($establecimiento['nombre']); ?></span>
                                        <span
                                            style="font-size: 0.85rem; font-weight: 500; margin-top: 4px; color: #e9ecef; text-shadow: 1px 1px 3px rgba(0,0,0,0.8);">
                                            <i class="fas fa-user-tie me-1"></i> Creado por:
                                            <?php echo htmlspecialchars($nombreAnfitrion); ?>
                                        </span>
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
                                            title="<?php echo $estadoText; ?>"><i class="fas fa-<?php echo $estadoIcon; ?>"></i>
                                        </div>
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
                                            <?php echo htmlspecialchars($establecimiento['descripcion']); ?></div>
                                    </div>

                                    <?php
                                    $misImagenes = $galeriasPorEst[$establecimiento['id']] ?? [];
                                    if (!empty($misImagenes)):
                                        ?>
                                        <div class="info-row mt-3">
                                            <div class="info-icon"><i class="fas fa-images"></i></div>
                                            <div><strong>Galería de imágenes:</strong></div>
                                        </div>
                                        <div class="d-flex gap-2 overflow-auto py-2 px-1 mb-2">
                                            <?php foreach ($misImagenes as $imgObj):
                                                $rawImg = $imgObj['image_url'];
                                                if (str_starts_with($rawImg, '../')) {
                                                    $srcUrl = $rawImg;
                                                } else {
                                                    $path = parse_url($rawImg, PHP_URL_PATH);
                                                    $srcUrl = rtrim($_ENV['MINIO_PUBLIC_URL'], '/') . $path;
                                                }
                                                ?>
                                                <img src="<?php echo htmlspecialchars($srcUrl); ?>" class="rounded"
                                                    style="width: 65px; height: 65px; object-fit: cover; border: 1px solid #dee2e6; box-shadow: 0 2px 4px rgba(0,0,0,0.05); cursor: pointer;"
                                                    onclick="window.open(this.src, '_blank')" title="Clic para ampliar">
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>

                                    <div class="map-container" id="map-<?php echo $establecimiento['id']; ?>"
                                        data-lat="<?php echo htmlspecialchars((string) ($establecimiento['latitude'] ?? '')); ?>"
                                        data-lng="<?php echo htmlspecialchars((string) ($establecimiento['longitude'] ?? '')); ?>">
                                    </div>
                                </div>

                                <div class="btn-actions">
                                    <a href="verEspacios.php?establecimiento_id=<?php echo $establecimiento['id']; ?>"
                                        class="btn btn-action btn-spaces"><i class="fas fa-door-open"></i> Espacios</a>
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
                                    ], JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'><i class="fas fa-edit"></i> Editar</button>
                                    <button class="btn btn-action btn-delete"
                                        onclick='confirmarEliminacion(<?php echo json_encode((string) $establecimiento['id']); ?>, <?php echo json_encode((string) ($establecimiento['nombre'] ?? '')); ?>)'><i
                                            class="fas fa-trash-alt"></i> Eliminar</button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <?php include 'footerAdmin.php'; ?>

    <div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <form id="establecimiento-form" method="POST" action="verEstablecimientos.php"
                    enctype="multipart/form-data">
                    <div class="modal-header bg-primary text-white"
                        style="background-color: var(--primary-color) !important;">
                        <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Editar establecimiento</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update_establecimiento">
                        <input type="hidden" id="edit-id" name="id">

                        <div class="row g-3">
                            <div class="col-md-6"><label class="form-label fw-bold">Nombre</label><input type="text"
                                    class="form-control" id="edit-nombre" name="nombre" required></div>
                            <div class="col-md-6"><label class="form-label fw-bold">Dirección</label><input type="text"
                                    class="form-control" id="edit-direccion" name="direccion" required></div>
                            <div class="col-12"><label class="form-label fw-bold">Descripción</label><textarea
                                    class="form-control" id="edit-descripcion" name="descripcion" rows="2"></textarea>
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-bold">Galería de Imágenes (Hasta 5)</label>
                                <div class="image-upload-container" id="uploadContainer">
                                    <i class="fas fa-cloud-upload-alt fa-2x text-muted mb-2"></i>
                                    <p class="mb-2 small">Arrastra tus imágenes aquí o haz clic para seleccionar</p>
                                    <button type="button" class="upload-btn"
                                        onclick="document.getElementById('imageFiles').click()"><i
                                            class="fas fa-plus"></i> Seleccionar</button>
                                    <input type="file" id="imageFiles" class="file-input" multiple accept="image/*">
                                    <div class="image-counter" id="imageCounter">0 de 5 imágenes</div>
                                </div>
                                <div class="image-preview" id="imagePreview"></div>
                                <input type="file" name="imagen" id="imagen" style="display: none;">
                                <input type="file" name="imagen2" id="imagen2" style="display: none;">
                                <input type="file" name="imagen3" id="imagen3" style="display: none;">
                                <input type="file" name="imagen4" id="imagen4" style="display: none;">
                                <input type="file" name="imagen5" id="imagen5" style="display: none;">
                                <input type="hidden" name="existing_images_count" id="existing_images_count" value="0">
                            </div>

                            <div class="col-md-4"><label class="form-label fw-bold">Localidad</label><input type="text"
                                    class="form-control" id="edit-localidad" name="localidad"></div>
                            <div class="col-md-4"><label class="form-label fw-bold">Provincia</label><input type="text"
                                    class="form-control" id="edit-provincia" name="provincia"></div>
                            <div class="col-md-4"><label class="form-label fw-bold">Código postal</label><input
                                    type="text" class="form-control" id="edit-codigo-postal" name="codigo_postal"></div>
                            <div class="col-md-4"><label class="form-label fw-bold">Piso</label><input type="text"
                                    class="form-control" id="edit-piso" name="piso"></div>
                            <div class="col-md-4"><label class="form-label fw-bold">Latitud</label><input type="number"
                                    step="any" class="form-control" id="edit-latitude" name="latitude"></div>
                            <div class="col-md-4"><label class="form-label fw-bold">Longitud</label><input type="number"
                                    step="any" class="form-control" id="edit-longitude" name="longitude"></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary"
                            style="background-color: var(--primary-color); border:none;">Guardar cambios</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="deleteImageModal" tabindex="-1" aria-hidden="true" style="z-index: 1060;">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content text-center">
                <div class="modal-body p-4">
                    <h5 class="fw-bold mb-3">¿Eliminar imagen?</h5>
                    <div class="d-flex justify-content-center gap-2 mt-4">
                        <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal">Cancelar</button>
                        <button type="button" id="btn-confirmar-eliminar-imagen"
                            class="btn btn-danger px-3">Eliminar</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

   <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content text-center" style="border-radius: 15px;">
                <form method="POST" action="verEstablecimientos.php">
                    <input type="hidden" name="action" value="delete_establecimiento">
                    <input type="hidden" name="delete_id" id="delete-id">
                    
                    <div class="modal-header bg-danger text-white justify-content-center" style="border-top-left-radius: 15px; border-top-right-radius: 15px;">
                        <h5 class="modal-title w-100"><i class="fas fa-exclamation-triangle fa-2x"></i></h5>
                    </div>
                    <div class="modal-body p-4">
                        <h4 class="mb-3 text-dark">¿Estás seguro?</h4>
                        <p class="text-muted">Se eliminará el establecimiento "<strong id="establecimiento-nombre"></strong>". Esta acción no se puede deshacer.</p>
                    </div>
                    <div class="modal-footer justify-content-center border-0 mb-3">
                        <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-danger px-4">Sí, eliminar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        const MINIO_URL = "<?php echo rtrim($_ENV['MINIO_PUBLIC_URL'] ?? '', '/'); ?>";
        const MAX_FILES = 5;
        let selectedFiles = [];
        const galeriasPrecargadas = <?php echo $galeriasJSON; ?>;
        const mapasInicializados = {};

        document.addEventListener('DOMContentLoaded', function () {
            setupImageUpload();

            // BUSCADOR INTELIGENTE
            function filterEstablecimientos() {
                const searchInput = $('#searchInputEst').val().toLowerCase().trim();
                const searchTerms = searchInput === '' ? [] : searchInput.split(/\s+/);
                const hostTerm = $('#filterHostEst').val().toLowerCase();
                let visibleCount = 0;

                $('.est-card-col').each(function () {
                    const cardText = $(this).text().toLowerCase();
                    const cardHost = ($(this).data('host-name') || '').toLowerCase();

                    const matchesSearch = searchTerms.every(term => cardText.includes(term));
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

            new mapboxgl.Marker({ color: '#dc3545' }).setLngLat([lng, lat]).addTo(map);
            setTimeout(() => map.resize(), 250);
        }

        function setupImageUpload() {
            const container = document.getElementById('uploadContainer');
            const fileInput = document.getElementById('imageFiles');

            function preventDefaults(e) {
                e.preventDefault();
                e.stopPropagation();
            }

            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                container.addEventListener(eventName, preventDefaults, false);
                document.body.addEventListener(eventName, preventDefaults, false);
            });

            container.addEventListener('dragover', () => container.classList.add('dragover'), false);
            ['dragleave', 'drop'].forEach(eventName => container.addEventListener(eventName, () => container.classList.remove('dragover'), false));

            container.addEventListener('drop', (e) => handleFiles(Array.from(e.dataTransfer.files)), false);
            fileInput.addEventListener('change', (e) => handleFiles(Array.from(e.target.files)));
        }

        function handleFiles(files) {
            const imageFiles = files.filter(file => file.type.startsWith('image/'));
            const existingCount = document.querySelectorAll('.existing-image').length;
            const available = MAX_FILES - selectedFiles.length - existingCount;

            if (imageFiles.length === 0) return;

            imageFiles.slice(0, available).forEach(file => {
                selectedFiles.push(file);
                createPreview(file, selectedFiles.length - 1);
            });

            updateImageUI();
            document.getElementById('imageFiles').value = '';
        }

        function createPreview(file, index) {
            const reader = new FileReader();
            reader.onload = (e) => {
                const div = document.createElement('div');
                div.className = 'preview-item new-image';
                div.innerHTML = `
                    <img src="${e.target.result}" alt="Preview">
                    <button type="button" class="remove-btn" onclick="removeImage(${index})"><i class="fas fa-times"></i></button>
                `;
                document.getElementById('imagePreview').appendChild(div);
            };
            reader.readAsDataURL(file);
        }

        function updateImageUI() {
            const counter = document.getElementById('imageCounter');
            const existingImages = document.querySelectorAll('.existing-image').length;
            const totalImages = existingImages + selectedFiles.length;

            document.getElementById('existing_images_count').value = existingImages;
            counter.textContent = `${totalImages} de ${MAX_FILES} imágenes (${existingImages} existentes)`;

            for (let i = 1; i <= MAX_FILES; i++) {
                const inputId = i === 1 ? 'imagen' : `imagen${i}`;
                const input = document.getElementById(inputId);
                if (input) {
                    const newInput = document.createElement('input');
                    newInput.type = 'file'; newInput.name = input.name; newInput.id = input.id; newInput.style.display = 'none';
                    input.parentNode.replaceChild(newInput, input);
                }
            }

            selectedFiles.forEach((file, index) => {
                const inputId = index === 0 ? 'imagen' : `imagen${index + 1}`;
                const input = document.getElementById(inputId);
                if (input) {
                    const dt = new DataTransfer(); dt.items.add(file); input.files = dt.files;
                }
            });

            let existingContainer = document.getElementById('existing-images-container');
            if (existingContainer) existingContainer.remove();
            existingContainer = document.createElement('div');
            existingContainer.id = 'existing-images-container';
            existingContainer.style.display = 'none';

            document.querySelectorAll('.existing-image').forEach((item, index) => {
                const input = document.createElement('input');
                input.type = 'hidden'; input.name = `existing_image_${index}`; input.value = item.querySelector('img').src;
                existingContainer.appendChild(input);
            });
            document.getElementById('establecimiento-form').appendChild(existingContainer);
        }

        window.removeImage = function (index) {
            selectedFiles.splice(index, 1);
            document.querySelectorAll('.new-image').forEach(el => el.remove());
            selectedFiles.forEach((file, i) => createPreview(file, i));
            updateImageUI();
        };

        window.removeExistingImage = function (imageId, buttonElement) {
            const deleteImageModal = new bootstrap.Modal(document.getElementById('deleteImageModal'));
            const btnConfirmar = document.getElementById('btn-confirmar-eliminar-imagen');
            const nuevoBtn = btnConfirmar.cloneNode(true);
            btnConfirmar.parentNode.replaceChild(nuevoBtn, btnConfirmar);

            nuevoBtn.addEventListener('click', function () {
                buttonElement.closest('.preview-item').remove();
                updateImageUI();
                deleteImageModal.hide();
            });
            deleteImageModal.show();
        };

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

            selectedFiles = [];
            const preview = document.getElementById('imagePreview');
            preview.innerHTML = '';

            const imagenesExistentes = galeriasPrecargadas[est.id] || [];

            if (imagenesExistentes.length > 0) {
                imagenesExistentes.forEach((imagen, index) => {
                    let imageUrl = imagen.image_url;
                    try {
                        let tempUrl = imageUrl.startsWith('http') ? imageUrl : 'http://' + imageUrl;
                        let urlObj = new URL(tempUrl);
                        imageUrl = MINIO_URL + urlObj.pathname;
                    } catch (e) {
                        if (!imageUrl.startsWith('http')) imageUrl = 'https://' + imageUrl;
                    }

                    const div = document.createElement('div');
                    div.className = 'preview-item existing-image';
                    div.dataset.imageId = imagen.id || index;
                    div.innerHTML = `
                        <img src="${imageUrl}" alt="Img ${index}">
                        <button type="button" class="remove-btn" onclick="removeExistingImage('${imagen.id || index}', this)"><i class="fas fa-times"></i></button>
                    `;
                    preview.appendChild(div);
                });
            }
            updateImageUI();

            new bootstrap.Modal(document.getElementById('editModal')).show();
        }

       function confirmarEliminacion(id, nombre) {
            document.getElementById('establecimiento-nombre').textContent = nombre;
            // Inyectamos el ID en el input oculto del formulario
            document.getElementById('delete-id').value = id;
            
            new bootstrap.Modal(document.getElementById('deleteModal')).show();
        }
    </script>
</body>

</html>