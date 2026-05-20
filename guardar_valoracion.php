<?php
// Usamos tu verificador de sesión
require_once 'verificar_sesion_guest.php';
require './vendor/autoload.php';
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

header('Content-Type: application/json');

// Recibimos los datos del JavaScript
$data = json_decode(file_get_contents('php://input'), true);

$id_establecimiento = $data['id_establecimiento'] ?? null;
$comentario = $data['comentario'] ?? '';
$valoracion = $data['valoracion'] ?? null;

// TRUCO: Buscamos tu ID de sesión en los 3 nombres más comunes en PHP
$id_user = $_SESSION['user_id'] ?? $_SESSION['id'] ?? $_SESSION['id_usuario'] ?? null;

// COMPROBACIONES ESTRICTAS (Si algo falla, te lo dirá en rojo en la pantalla)
if (!$id_establecimiento) {
    echo json_encode(['success' => false, 'message' => 'Falta el ID del establecimiento.']);
    exit;
}
if (!$valoracion) {
    echo json_encode(['success' => false, 'message' => 'Tienes que seleccionar una puntuación con estrellas.']);
    exit;
}
if (!$id_user) {
    echo json_encode(['success' => false, 'message' => 'Fallo de sesión: No encuentro tu ID de Nómada.']);
    exit;
}

$url = "http://" . $_ENV['SERVER_IP'] . ":" . $_ENV['DATABASE_PORT'] . "/rest/v1/valoraciones";

// Creamos el paquete de datos
$payload = [
    'id_user' => $id_user,
    'id_establecimiento' => $id_establecimiento,
    'comentario' => trim($comentario),
    'valoracion' => (float)$valoracion
];

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($payload),
    CURLOPT_HTTPHEADER => [
        'apikey: ' . $_ENV['SERVICE_APIKEY'],
        'Authorization: Bearer ' . $_ENV['SERVICE_APIKEY'],
        'Content-Type: application/json',
        'Prefer: return=minimal'
    ],
    CURLOPT_RETURNTRANSFER => true
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// SI TODO VA BIEN (Código 201 = Creado en Supabase)
if ($httpCode >= 200 && $httpCode < 300) {
    echo json_encode(['success' => true]);
} else {
    // SI FALLA, QUE NOS ENSEÑE EL ERROR REAL DE SUPABASE
    echo json_encode(['success' => false, 'message' => 'Error de BD (' . $httpCode . '): ' . $response]);
}
?>