<?php
require_once 'verificar_sesion_host.php';
require '../vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

// Verificamos el ID y el TOKEN
if (!isset($_SESSION['user_id']) || !isset($_SESSION['token'])) {
    header("Location: inicio_sesion_anfitrion.php");
    exit();
}

$url_check = "http://" . $_ENV['SERVER_IP'] . ":" . $_ENV['DATABASE_PORT'] . "/rest/v1/establecimiento?select=*,space(*)&host_id=eq." . $_SESSION['user_id'];

$ch_check = curl_init($url_check);
curl_setopt_array($ch_check, array(
    CURLOPT_CUSTOMREQUEST => "GET",
    CURLOPT_HTTPHEADER => array(
        'Content-Type: application/json',
        'apikey: ' . $_ENV['DATABASE_APIKEY'],
        'Authorization: Bearer ' . $_SESSION['token']
    ),
    CURLOPT_RETURNTRANSFER => true,
));
$res_check = curl_exec($ch_check);
curl_close($ch_check);

$establecimientos = json_decode($res_check, true);

// Contamos establecimientos y espacios
$num_establecimientos = is_array($establecimientos) ? count($establecimientos) : 0;
$num_espacios = 0;

if (is_array($establecimientos)) {
    foreach ($establecimientos as $est) {
        if (isset($est['space']) && is_array($est['space'])) {
            $num_espacios += count($est['space']);
        }
    }
}

if ($num_establecimientos > 1 || $num_espacios > 3) {
    header("Location: Suscripciones.php?error=limites_basico");
    exit();
}
$url_patch = "http://" . $_ENV['SERVER_IP'] . ":" . $_ENV['DATABASE_PORT'] . "/rest/v1/host?id=eq." . $_SESSION['user_id'];

$datosActualizar = json_encode([
    'plan' => 'Basico'
]);

$ch = curl_init($url_patch);
curl_setopt_array($ch, array(
    CURLOPT_CUSTOMREQUEST => "PATCH",
    CURLOPT_POSTFIELDS => $datosActualizar,
    CURLOPT_HTTPHEADER => array(
        'Content-Type: application/json',
        'apikey: ' . $_ENV['DATABASE_APIKEY'],
        'Authorization: Bearer ' . $_SESSION['token']
    ),
    CURLOPT_RETURNTRANSFER => true,
));

$resultado = curl_exec($ch);
$codigoRespuesta = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($codigoRespuesta >= 200 && $codigoRespuesta < 300) {
    header("Location: Suscripciones.php?mensaje=plan_bajado");
    exit();
} else {
    header("Location: Suscripciones.php?error=fallo_actualizacion");
    exit();
}
?>