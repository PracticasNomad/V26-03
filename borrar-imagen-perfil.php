<?php
session_start();
require_once __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado: Inicie sesión']);
    exit;
}

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

$userId = $_SESSION['user_id'];

// Recibimos el tipo de perfil directamente desde la URL (?tipo=gestor, host, admin, user)
$tipo = isset($_GET['tipo']) ? strtolower(trim($_GET['tipo'])) : 'user';

// Mapeo seguro a las tablas de tu base de datos
$tabla = 'user';
if ($tipo === 'admin' || $tipo === 'administrador') $tabla = 'admin';
if ($tipo === 'host' || $tipo === 'anfitrion') $tabla = 'host';
if ($tipo === 'gestor' || $tipo === 'gestora') $tabla = 'gestor';

$baseUrl = isset($_ENV['SUPABASE_URL']) ? $_ENV['SUPABASE_URL'] : "http://" . $_ENV['SERVER_IP'] . ":" . $_ENV['DATABASE_PORT'];
$url = rtrim($baseUrl, '/') . "/rest/v1/" . $tabla . "?id=eq." . $userId;

$data = json_encode(['avatar_url' => null]);

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_CUSTOMREQUEST => 'PATCH',
    CURLOPT_POSTFIELDS => $data,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $_ENV['SERVICE_APIKEY'],
        'apikey: ' . $_ENV['SERVICE_APIKEY'],
        'Prefer: return=minimal'
    ]
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode >= 200 && $httpCode < 300) {
    echo json_encode(['success' => true, 'avatarUrl' => '../img/perfil.png']);
} else {
    echo json_encode(['success' => false, 'message' => 'Error al limpiar el avatar en la tabla ' . $tabla]);
}
