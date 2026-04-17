<?php
session_start();
error_reporting(0); // Ocultamos posibles warnings de PHP para no corromper el JSON
header('Content-Type: application/json'); // Obligamos a que la respuesta sea JSON puro

require __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;

try {
    $dotenv = Dotenv::createImmutable(__DIR__);
    $dotenv->load();
} catch (Exception $e) {
    // Ignoramos si falla, por si las variables ya están inyectadas en el servidor
}

// Comprobar que existe la sesión
if (!isset($_SESSION["user_id"])) {
    http_response_code(401);
    echo json_encode(["error" => "No autorizado o sesión expirada"]);
    exit;
}

$id = $_SESSION["user_id"];

// Soportar ambas configuraciones (SERVER_IP o SUPABASE_URL)
$baseUrl = isset($_ENV['SUPABASE_URL'])
    ? $_ENV['SUPABASE_URL']
    : "http://" . $_ENV['SERVER_IP'] . ":" . $_ENV['DATABASE_PORT'];

// Soportar ambas nomenclaturas de API KEY
$apiKey = isset($_ENV['SERVICE_APIKEY'])
    ? $_ENV['SERVICE_APIKEY']
    : (isset($_ENV['DATABASE_APIKEY']) ? $_ENV['DATABASE_APIKEY'] : '');

$url = $baseUrl . "/rest/v1/host?id=eq." . $id;

$ch = curl_init($url);

curl_setopt_array($ch, array(
    CURLOPT_CUSTOMREQUEST => "GET",
    CURLOPT_HTTPHEADER => array(
        'Content-Type: application/json',
        'apikey: ' . $apiKey,
        'Authorization: Bearer ' . $apiKey
    ),
    CURLOPT_RETURNTRANSFER => true,
));

$resultado = curl_exec($ch);
$codigoRespuesta = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($codigoRespuesta === 200 && $resultado) {
    $datos = json_decode($resultado, true);
    if (!empty($datos)) {
        echo json_encode($datos[0]); // Mandamos solo el objeto del anfitrión
    } else {
        http_response_code(404);
        echo json_encode(["error" => "Anfitrión no encontrado en la base de datos"]);
    }
} else {
    http_response_code($codigoRespuesta ?: 500);
    echo json_encode([
        "error" => "Error de conexión con la base de datos",
        "status" => $codigoRespuesta,
        "detalle" => json_decode($resultado) ?: $resultado
    ]);
}
