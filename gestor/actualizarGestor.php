<?php
require_once 'verificar_sesion_gestor.php';
require '../vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Recogemos los datos del formulario
    $data = [
        'name' => $_POST['nombre'] ?? '',
        'empresa' => $_POST['empresa'] ?? '',
        'phone' => $_POST['telefono'] ?? '',
        'cif' => $_POST['cif'] ?? '', // Si tu base de datos usa 'nif', cambialo aquí a 'nif' => $_POST['cif']
        //'domicilio_social' => $_POST['domicilio_social'] ?? '',
        'codigo_postal' => $_POST['codigo_postal'] ?? ''
    ];

    // Llamada PATCH a la tabla gestor
    $url = "http://" . $_ENV['SERVER_IP'] . ":" . $_ENV['DATABASE_PORT'] . "/rest/v1/gestor?id=eq." . $_SESSION['user_id'];

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
        echo json_encode(['success' => false, 'message' => 'Error BD: ' . $result]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
}
