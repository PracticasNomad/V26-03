<?php
session_start();
require '../vendor/autoload.php';
use Dotenv\Dotenv;
$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

if (!isset($_GET['id'], $_GET['accion'])) {
    header('Location: verValidar.php');
    exit;
}

$id = intval($_GET['id']);
$accion = $_GET['accion'];

$update = [];
if ($accion === 'aprobar') {
    $update['estado'] = 'aprobado';
} elseif ($accion === 'rechazar') {
    $update['estado'] = 'rechazado';
} else {
    header('Location: verValidar.php');
    exit;
}

$url = 'http://' . $_ENV['SERVER_IP'] . ':' . $_ENV['DATABASE_PORT']
    . '/rest/v1/establecimiento?id=eq.' . $id;

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_CUSTOMREQUEST => 'PATCH',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'apikey: ' . $_ENV['DATABASE_APIKEY'],
        'Authorization: Bearer ' . ($_SESSION['token'] ?? ''),
    ],
    CURLOPT_POSTFIELDS => json_encode($update),
]);
$result = curl_exec($ch);
curl_close($ch);

header('Location: verValidar.php');
exit;
