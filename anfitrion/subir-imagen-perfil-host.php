<?php
session_start();

require '../vendor/autoload.php';
use Dotenv\Dotenv;
    
$dotenv = Dotenv::createImmutable(DIRNAME(__DIR__));
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

$minioHost = 'http://' . $_ENV['SERVER_IP'] . ':' . $_ENV['REPO_PORT']; 
$minioBucket = 'images';

$file = $_FILES['imagen'];
$extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$nombreArchivo = 'avatar_' . $_SESSION['user_id'] . '_' . time() . '.' . $extension;
$rutaTemporal = $file['tmp_name'];

$extensionesPermitidas = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

if (!in_array($extension, $extensionesPermitidas)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Tipo de archivo no permitido. Solo se permiten imágenes (JPG, PNG, GIF, WEBP)']);
    exit;
}

$mimeTypes = [
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png' => 'image/png',
    'gif' => 'image/gif',
    'webp' => 'image/webp'
];
$fileType = $mimeTypes[$extension];

$minioUrl = $minioHost . '/' . $minioBucket . '/' . $nombreArchivo;

$ch = curl_init($minioUrl);
$fileContent = file_get_contents($rutaTemporal);

curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
curl_setopt($ch, CURLOPT_POSTFIELDS, $fileContent);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: ' . $fileType,
    'Content-Length: ' . strlen($fileContent)
]);

$resultado = curl_exec($ch);
$codigoRespuesta = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if ($codigoRespuesta >= 200 && $codigoRespuesta < 300) {
    $url = 'http://' . $_ENV['SERVER_IP'] . ':' . $_ENV['DATABASE_PORT'] . '/rest/v1/host?id=eq.' . $_SESSION['user_id'];
    $ch = curl_init($url);
    
    $data = [
        'avatar_url' => $minioUrl
    ];
    
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'apikey: ' . $_ENV['DATABASE_APIKEY'],
        'Authorization: Bearer ' . $_SESSION['token']
    ]);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    
    $resultado = curl_exec($ch);
    $codigoRespuesta = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    header('Content-Type: application/json');
    if ($codigoRespuesta >= 200 && $codigoRespuesta < 300) {
        echo json_encode([
            'success' => true, 
            'message' => 'Imagen de perfil actualizada correctamente',
            'avatarUrl' => $minioUrl
        ]);
    } else {
        echo json_encode([
            'success' => false, 
            'message' => 'Error al actualizar el avatar. Código: ' . $codigoRespuesta,
            'response' => $resultado
        ]);
    }
    
    curl_close($ch);
} else {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false, 
        'message' => 'Error al subir la imagen a Minio. Código: ' . $codigoRespuesta,
        'response' => $resultado
    ]);
}
?>