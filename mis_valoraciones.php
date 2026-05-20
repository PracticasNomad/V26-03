<?php
require_once 'verificar_sesion_guest.php';
require './vendor/autoload.php';
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

header('Content-Type: application/json');

// Atrapamos tu ID de sesión
$id_user = $_SESSION['user_id'] ?? $_SESSION['id'] ?? $_SESSION['id_usuario'] ?? null;

if (!$id_user) {
    echo json_encode([]);
    exit;
}

// Le pedimos a Supabase solo los IDs de los locales que tú has valorado
$url = "http://" . $_ENV['SERVER_IP'] . ":" . $_ENV['DATABASE_PORT'] . "/rest/v1/valoraciones?id_user=eq." . urlencode($id_user) . "&select=id_establecimiento";

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_HTTPHEADER => [
        'apikey: ' . $_ENV['SERVICE_APIKEY'],
        'Authorization: Bearer ' . $_ENV['SERVICE_APIKEY'],
        'Accept: application/json'
    ],
    CURLOPT_RETURNTRANSFER => true
]);

$response = curl_exec($ch);
curl_close($ch);

// Convertimos la respuesta en una lista simple [1, 5, 12...]
$valoraciones = json_decode($response, true) ?: [];
$ids_valorados = array_map(function($v) { return $v['id_establecimiento']; }, $valoraciones);

echo json_encode($ids_valorados);
?>