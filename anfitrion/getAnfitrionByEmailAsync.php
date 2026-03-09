<?php  
session_start();

require '../vendor/autoload.php';
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

$token = $_SESSION['token'];

$select = rawurlencode('*,schedule!space_schedule_id_fkey(*)');
$url = "http://" . $_ENV['SERVER_IP'] . ":" . $_ENV['DATABASE_PORT'] . "/rest/v1/space?select=" . $select . "&host_id=eq." . $_SESSION["user_id"];
$ch = curl_init($url);

curl_setopt_array($ch, array(
    CURLOPT_CUSTOMREQUEST => "GET",
    CURLOPT_HTTPHEADER => array(
        'Content-Type: application/json;charset=UTF-8',
        'apikey: ' . $_ENV['DATABASE_APIKEY'],
        'Authorization: Bearer ' . $token
    ),
    CURLOPT_RETURNTRANSFER => true,
));

$resultado = curl_exec($ch);
$codigoRespuesta = curl_getinfo($ch, CURLINFO_HTTP_CODE);
if ($codigoRespuesta === 200) {
    $respuestaDecodificada = json_decode($resultado, true);
    
    if (json_last_error() === JSON_ERROR_NONE) {
        header('Content-Type: application/json');
        
        echo json_encode($respuestaDecodificada);
    } else {
        echo json_encode(["error" => "Error al decodificar la respuesta JSON del servidor"]);
    }
} else {
    echo json_encode(["error" => "Error consultando. Código de respuesta: $codigoRespuesta"]);
}

curl_close($ch);
