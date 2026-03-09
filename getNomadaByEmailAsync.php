<?php
session_start();

require './vendor/autoload.php';
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$token = $_SESSION["token"];
$id = $_SESSION["user_id"];
$email = urlencode($_SESSION["email"]);

$url = "http://" . $_ENV['SERVER_IP'] . ":" . $_ENV['DATABASE_PORT'] . "/rest/v1/user?id=eq.$id";

$ch = curl_init($url);

curl_setopt_array($ch, array(
    CURLOPT_CUSTOMREQUEST => "GET",
    CURLOPT_HTTPHEADER => array(
        'Content-Type: application/json',
        'Authorization: Bearer ' . $token,
        'apikey: ' . $_ENV['DATABASE_APIKEY']
    ),
    CURLOPT_RETURNTRANSFER => true,
));

$resultado = curl_exec($ch);
$codigoRespuesta = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($codigoRespuesta === 200) {
    $datos = json_decode($resultado, true);
    if (count($datos) > 0) {
        echo json_encode($datos[0]); 
    } else {
        echo json_encode(["error" => "Usuario no encontrado"]);
    }
} else if ($codigoRespuesta === 401) {
    echo json_encode(["unauthorized" => "Token Expirado"]);
} else {
    echo json_encode(["error" => "Error consultando Supabase"]);
}
?>
