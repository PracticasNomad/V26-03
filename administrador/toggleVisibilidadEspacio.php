<?php
require_once 'verificar_sesion_admin.php';
require '../vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

header('Content-Type: application/json');

// Recibir los datos enviados por AJAX
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['id']) || !isset($data['visible'])) {
    echo json_encode(['success' => false, 'error' => 'Faltan datos para realizar la acción.']);
    exit;
}

$espacioId = $data['id'];
$payload = json_encode(['visible' => $data['visible']]);

// Llamada a la API de Supabase (Actualizar Espacio)
$url = "http://" . $_ENV['SERVER_IP'] . ":" . $_ENV['DATABASE_PORT'] . "/rest/v1/space?id=eq." . urlencode($espacioId);

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $_ENV['SERVICE_APIKEY'], // Permisos globales admin
    'apikey: ' . $_ENV['SERVICE_APIKEY'],
    'Content-Type: application/json',
    'Prefer: return=representation'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode >= 200 && $httpCode < 300) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => "Error al cambiar visibilidad (HTTP $httpCode)."]);
}
?>