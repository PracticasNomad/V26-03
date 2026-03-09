<?php

require_once 'verificar_sesion_guest.php';

require './vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Usuario no autenticado. Por favor, inicie sesión.']);
    exit;
}

$message = "";

$user_id = $_SESSION['user_id'];
$start_time = $_POST['startTime'];
$end_time = $_POST['endTime'];
$day = $_POST['date'];
$day = date('Y-m-d', strtotime($_POST['date']));
$space_id = $_POST['spaceId'];

if (isset($_POST['message'])) {
    $message = $_POST['message'];
}

$url = 'http://' . $_ENV['SERVER_IP'] . ':' . $_ENV['DATABASE_PORT'] . '/rest/v1/reservation';
$ch = curl_init($url);
$data = array(
    "user_id" => $user_id,
    "space_id" => $space_id,
    "start_time" => $start_time,
    "end_time" => $end_time,
    "day" => $day,
    "message" => $message
);
$payload = json_encode($data);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    'Authorization: Bearer ' . $_SESSION['token'],
    'Content-Type: application/json',
    'apikey: ' . $_ENV['DATABASE_APIKEY']
));
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
$response = curl_exec($ch);

$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
if ($httpCode >= 200 && $httpCode < 300) {
    if (json_decode($response) !== null) {
        echo $response;
    } else {
        echo json_encode(['success' => true, 'message' => 'Reserva realizada con éxito']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Error: ' . curl_error($ch)]);
}

curl_close($ch);
