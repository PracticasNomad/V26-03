<?php
require './vendor/autoload.php';
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

header('Content-Type: application/json');

// Recibimos el ID del establecimiento desde el JavaScript
$id_establecimiento = $_GET['id_establecimiento'] ?? '';

if (empty($id_establecimiento)) {
    echo json_encode(['success' => false, 'message' => 'Falta el ID del establecimiento']);
    exit;
}

// Consultamos Supabase ordenando por fecha (los más nuevos primero)
$url = "http://" . $_ENV['SERVER_IP'] . ":" . $_ENV['DATABASE_PORT'] . "/rest/v1/valoraciones?id_establecimiento=eq." . urlencode($id_establecimiento) . "&order=created_at.desc";

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
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode >= 200 && $httpCode < 300) {
    $valoraciones = json_decode($response, true) ?: [];

    // Calculamos la nota media exacta
    $total = count($valoraciones);
    $suma = 0;
    foreach ($valoraciones as $val) {
        $suma += (float)$val['valoracion'];
    }
    $media = $total > 0 ? round($suma / $total, 1) : 0;

    echo json_encode([
        'success' => true,
        'media' => $media,
        'total' => $total,
        'valoraciones' => $valoraciones
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Error al leer la base de datos']);
}
?>