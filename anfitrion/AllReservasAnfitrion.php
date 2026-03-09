<?php

require_once 'verificar_sesion_host.php';

require '../vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

$token = $_SESSION["token"];
$reservaId = $_SESSION["user_id"];
$url = "http://" . $_ENV['SERVER_IP'] . ":" . $_ENV['DATABASE_PORT'] . "/rest/v1/reservation?select=*,space(*,establecimiento(*)),user(*)&space.establecimiento.host_id=eq." . $reservaId; //"http://yonomadapp.hopto.org:8089/api/reserva/AllReservasAnfitrion";
$ch = curl_init($url);


curl_setopt_array($ch, array(
    CURLOPT_CUSTOMREQUEST => "GET",
    CURLOPT_HTTPHEADER => array(
        'Content-Type: application/json;charset=UTF-8',
        'Authorization: Bearer ' . $token,
        'apikey: ' . $_ENV['DATABASE_APIKEY']
    ),
    CURLOPT_RETURNTRANSFER => true,
));

$resultado = curl_exec($ch);

echo $resultado;
$codigoRespuesta = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if ($codigoRespuesta === 200) {
    $respuestaDecodificada = json_decode($resultado);
} else {
    echo "Error consultando. Código de respuesta: $codigoRespuesta";
}
curl_close($ch);
