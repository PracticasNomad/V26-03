<?php
require_once 'verificar_sesion_gestor.php';
require '../vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

header('Content-Type: application/json');

$inputJSON = file_get_contents("php://input");
$data = json_decode($inputJSON, true);

if (!isset($data['id']) || !isset($data['visible'])) {
    echo json_encode(['success' => false, 'error' => 'Faltan parámetros en la petición.']);
    exit;
}

$espacioId = $data['id'];
$nuevaVisibilidad = filter_var($data['visible'], FILTER_VALIDATE_BOOLEAN);

$url = "http://" . $_ENV['SERVER_IP'] . ":" . $_ENV['DATABASE_PORT'] . "/rest/v1/space?id=eq." . $espacioId;
$ch = curl_init($url);

$payload = json_encode(['visible' => $nuevaVisibilidad]);

curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PATCH");
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);

curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    'Content-Type: application/json',
    'Authorization: Bearer ' . $_ENV['SERVICE_APIKEY'],
    'apikey: ' . $_ENV['SERVICE_APIKEY']
));

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error = curl_error($ch);
curl_close($ch);

if ($curl_error) {
    echo json_encode(['success' => false, 'error' => 'Fallo cURL: ' . $curl_error]);
} else if ($httpCode >= 200 && $httpCode < 300) {
    echo json_encode(['success' => true]);
} else {
    $detalles = json_decode($response, true);
    $mensajeError = isset($detalles['message']) ? $detalles['message'] : 'Error en BD';
    echo json_encode(['success' => false, 'error' => 'Error BD: ' . $mensajeError]);
}
?>