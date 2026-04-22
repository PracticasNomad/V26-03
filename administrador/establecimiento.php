<?php
require_once 'verificar_sesion_admin.php';
require '../vendor/autoload.php';
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

if (!isset($_GET['id']) || $_GET['id'] === '') {
    header('Location: verEstablecimientos.php');
    exit;
}

$id = (string)$_GET['id'];

// Llamar a API para eliminar el establecimiento
$url = 'http://' . $_ENV['SERVER_IP'] . ':' . $_ENV['DATABASE_PORT']
    . '/rest/v1/establecimiento?id=eq.' . rawurlencode($id);

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_CUSTOMREQUEST => 'DELETE',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'apikey: ' . $_ENV['SERVICE_APIKEY'],
        'Authorization: Bearer ' . $_ENV['SERVICE_APIKEY'],
    ],
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode >= 200 && $httpCode < 300) {
    header('Location: verEstablecimientos.php?msg=deleted');
    exit;
}

header('Location: verEstablecimientos.php?msg=delete_error');
exit;
