<?php
require_once 'verificar_sesion_gestor.php';
require '../vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

$gestorId = $_SESSION["user_id"];

// 1. OBTENEMOS EL CÓDIGO POSTAL DEL GESTOR
$urlGestor = "http://" . $_ENV['SERVER_IP'] . ":" . $_ENV['DATABASE_PORT'] . "/rest/v1/gestor?select=codigo_postal&id=eq." . $gestorId;
$chGestor = curl_init($urlGestor);
curl_setopt_array($chGestor, [
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $_ENV['SERVICE_APIKEY'],
        'apikey: ' . $_ENV['SERVICE_APIKEY']
    ],
    CURLOPT_RETURNTRANSFER => true
]);
$resGestor = curl_exec($chGestor);
curl_close($chGestor);

$datosGestor = json_decode($resGestor, true);
$cpGestor = $datosGestor[0]['codigo_postal'] ?? null;

header('Content-Type: application/json');

if ($cpGestor) {
    // 2. BUSCAMOS LAS RESERVAS DE LOS ESTABLECIMIENTOS DE ESE CP
    $url = "http://" . $_ENV['SERVER_IP'] . ":" . $_ENV['DATABASE_PORT'] . "/rest/v1/reservation?select=*,space(*,establecimiento(*)),user(*)&space.establecimiento.codigo_postal=eq." . urlencode($cpGestor);

    $ch = curl_init($url);
    curl_setopt_array($ch, array(
        CURLOPT_CUSTOMREQUEST => "GET",
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json;charset=UTF-8',
            'Authorization: Bearer ' . $_ENV['SERVICE_APIKEY'],
            'apikey: ' . $_ENV['SERVICE_APIKEY']
        ),
        CURLOPT_RETURNTRANSFER => true,
    ));

    $resultado = curl_exec($ch);
    $codigoRespuesta = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($codigoRespuesta >= 200 && $codigoRespuesta < 300) {
        echo $resultado;
    } else {
        echo json_encode([]);
    }
} else {
    // Si el gestor no tiene CP, devolvemos vacío para no romper la pantalla
    echo json_encode([]);
}
