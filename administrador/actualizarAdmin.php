<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'administrador') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'No autorizado.']);
    exit;
}

require '../vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $adminId = $_SESSION['user_id'];

    // Recogemos los datos del formulario (tienen que llamarse igual que en la base de datos)
    $data = [
        'name' => $_POST['nombre'] ?? '',
        'empresa' => $_POST['empresa'] ?? '',
        'phone' => $_POST['telefono'] ?? '',
        'cif' => $_POST['cif'] ?? '',
        'domicilio_social' => $_POST['domicilio_social'] ?? '',
        'provincia' => $_POST['provincia'] ?? '',
        'localidad' => $_POST['localidad'] ?? '',
        'codigo_postal' => $_POST['codigo_postal'] ?? ''
    ];

    // Actualizamos en la tabla admin
    $url = "http://" . $_ENV['SERVER_IP'] . ":" . $_ENV['DATABASE_PORT'] . "/rest/v1/admin?id=eq." . urlencode($adminId);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $_ENV['SERVICE_APIKEY'],
        'apikey: ' . $_ENV['SERVICE_APIKEY']
    ]);

    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode >= 200 && $httpCode < 300) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al guardar los datos en la base de datos (HTTP ' . $httpCode . ').']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Petición inválida.']);
}
