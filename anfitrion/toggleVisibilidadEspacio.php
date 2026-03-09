<?php
// Ocultamos errores visuales de PHP para no romper el formato
error_reporting(0);
header('Content-Type: application/json');

session_start();

// Hacemos la comprobación de sesión a mano para DEVOLVER JSON en lugar de redirigir (HTML)
if (!isset($_SESSION['user_id']) || !isset($_SESSION['token'])) {
    echo json_encode(['success' => false, 'error' => 'Tu sesión ha caducado. Recarga la página.']);
    exit();
}

require '../vendor/autoload.php';
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

// Leemos lo que nos envía el botón
$inputJSON = file_get_contents("php://input");
$data = json_decode($inputJSON, true);

if (!isset($data['id']) || !isset($data['visible'])) {
    echo json_encode(['success' => false, 'error' => 'Faltan parámetros en la petición.']);
    exit;
}

$espacioId = $data['id'];

// Preparar petición a Supabase (Método PATCH para actualizar solo ese campo)
$url = "http://" . $_ENV['SERVER_IP'] . ":" . $_ENV['DATABASE_PORT'] . "/rest/v1/space?id=eq." . $espacioId;
$ch = curl_init($url);

$payload = json_encode([
    'visible' => (bool) $data['visible']
]);

curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PATCH");
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    'Content-Type: application/json',
    'Prefer: return=representation',
    'Authorization: Bearer ' . $_SESSION['token'], // <-- ESTA ES LA LÍNEA QUE FALTABA
    'apikey: ' . $_ENV['DATABASE_APIKEY']
));

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error = curl_error($ch);
curl_close($ch);

if ($curl_error) {
    echo json_encode(['success' => false, 'error' => 'Error interno del servidor: ' . $curl_error]);
    exit;
}

// Si la respuesta es 200 OK o 204 No Content, fue exitoso
if ($httpCode >= 200 && $httpCode < 300) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Error en base de datos. Código: ' . $httpCode, 'details' => json_decode($response)]);
}
?>