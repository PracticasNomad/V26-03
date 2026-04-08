<?php
session_start();

require './vendor/autoload.php';
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['token'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'No hay sesión activa']);
    exit;
}

if (!isset($_FILES['imagen']) || $_FILES['imagen']['error'] !== UPLOAD_ERR_OK) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'No se ha enviado ninguna imagen o hubo un error al subirla']);
    exit;
}

$minioBucket = 'images';
$file = $_FILES['imagen'];
$extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$cleanUserId = trim($_SESSION['user_id']);
$nombreArchivo = 'avatar_' . $cleanUserId . '_' . time() . '.' . $extension;

$extensionesPermitidas = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

if (!in_array($extension, $extensionesPermitidas)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Tipo de archivo no permitido.']);
    exit;
}

$mimeTypes = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'gif' => 'image/gif', 'webp' => 'image/webp'];
$fileType = $mimeTypes[$extension];

// URL directa desde tu .env (ej: https://79.150.19.209:9000)
$minioUrl = rtrim($_ENV['MINIO_PUBLIC_URL'], '/') . '/' . $minioBucket . '/' . $nombreArchivo;
$rutaTemporal = $file['tmp_name'];
$fileContent = file_get_contents($rutaTemporal);

$ch = curl_init($minioUrl);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
curl_setopt($ch, CURLOPT_POSTFIELDS, $fileContent);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
// LA MAGIA PARA QUE PHP NO BLOQUEE LA IP HTTPS:
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: ' . $fileType,
    'Content-Length: ' . strlen($fileContent)
]);

$resultado = curl_exec($ch);
$codigoRespuesta = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($codigoRespuesta >= 200 && $codigoRespuesta < 300) {
    $url = 'http://' . $_ENV['SERVER_IP'] . ':' . $_ENV['DATABASE_PORT'] . '/rest/v1/user?id=eq.' . $cleanUserId;
    $ch_db = curl_init($url);
    $data = ['avatar_url' => $minioUrl];
    
    curl_setopt($ch_db, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch_db, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'apikey: ' . $_ENV['DATABASE_APIKEY'],
        'Authorization: Bearer ' . $_SESSION['token']
    ]);
    curl_setopt($ch_db, CURLOPT_CUSTOMREQUEST, 'PATCH');
    curl_setopt($ch_db, CURLOPT_POSTFIELDS, json_encode($data));
    
    $resultado_db = curl_exec($ch_db);
    $codigoRespuesta_db = curl_getinfo($ch_db, CURLINFO_HTTP_CODE);
    curl_close($ch_db);
    
    header('Content-Type: application/json');
    if ($codigoRespuesta_db >= 200 && $codigoRespuesta_db < 300) {
        echo json_encode(['success' => true, 'message' => 'Imagen actualizada', 'avatarUrl' => $minioUrl]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error en la BD.']);
    }
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Error MinIO: ' . $codigoRespuesta]);
}
?>