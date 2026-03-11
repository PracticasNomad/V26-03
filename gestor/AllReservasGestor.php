<?php
require_once 'verificar_sesion_gestor.php';
require '../vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

$gestorId = $_SESSION["user_id"];

// Buscamos las reservas filtrando por los establecimientos que pertenecen a este gestor
$url = "http://" . $_ENV['SERVER_IP'] . ":" . $_ENV['DATABASE_PORT'] . "/rest/v1/reservation?select=*,space(*,establecimiento(*)),user(*)&space.establecimiento.gestor_id=eq." . $gestorId;

$ch = curl_init($url);
curl_setopt_array($ch, array(
    CURLOPT_CUSTOMREQUEST => "GET",
    CURLOPT_HTTPHEADER => array(
        'Content-Type: application/json;charset=UTF-8',
        'Authorization: Bearer ' . $_ENV['SERVICE_APIKEY'], // Usamos Service Key por RLS
        'apikey: ' . $_ENV['SERVICE_APIKEY']                // Usamos Service Key por RLS
    ),
    CURLOPT_RETURNTRANSFER => true,
));

$resultado = curl_exec($ch);
$codigoRespuesta = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

header('Content-Type: application/json');

if ($codigoRespuesta >= 200 && $codigoRespuesta < 300) {
    echo $resultado;
} else {
    // Si hay error, devolvemos un array vacío para que el JS no pete
    echo json_encode([]);
}
