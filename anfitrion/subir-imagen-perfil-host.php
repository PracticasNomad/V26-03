<?php
session_start();

require '../vendor/autoload.php';
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(dirname(__DIR__));
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

// Para la subida (conexión interna de PHP a Minio)
$minioHost = 'http://' . $_ENV['SERVER_IP'] . ':' . $_ENV['REPO_PORT'];
$minioBucket = 'images'; // Asegúrate de que el bucket de avatares se llama 'images' (o cámbialo a 'perfiles')

$file = $_FILES['imagen'];
$extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$nombreArchivo = 'avatar_host_' . $_SESSION['user_id'] . '_' . time() . '.' . $extension;
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

// URL INTERNA PARA LA SUBIDA CURL
$minioUrlInterna = $minioHost . '/' . $minioBucket . '/' . $nombreArchivo;

// URL PÚBLICA PARA GUARDAR EN LA BD Y ENVIAR AL NAVEGADOR
$dominioPublico = rtrim($_ENV['MINIO_PUBLIC_URL'], '/');
$minioUrlPublica = $dominioPublico . '/' . $minioBucket . '/' . $nombreArchivo;

// Subimos la imagen a la red local de tu servidor (rápido y seguro sin cortafuegos)
$ch = curl_init($minioUrlInterna);
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
curl_close($ch);

if ($codigoRespuesta >= 200 && $codigoRespuesta < 300) {
    // Si la subida fue un éxito, guardamos LA URL PÚBLICA (HTTPS) en la tabla 'host'
    $url = 'http://' . $_ENV['SERVER_IP'] . ':' . $_ENV['DATABASE_PORT'] . '/rest/v1/host?id=eq.' . $_SESSION['user_id'];
    $ch_db = curl_init($url);
    
    $data = [
        'avatar_url' => $minioUrlPublica
    ];
    
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
        echo json_encode([
            'success' => true, 
            'message' => 'Imagen de perfil actualizada correctamente',
            'avatarUrl' => $minioUrlPublica // Devolvemos el link https al navegador
        ]);
    } else {
        echo json_encode([
            'success' => false, 
            'message' => 'Error al guardar en base de datos. Código: ' . $codigoRespuesta_db
        ]);
    }
} else {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false, 
        'message' => 'Error al subir la imagen al servidor. Código: ' . $codigoRespuesta
    ]);
}
?>