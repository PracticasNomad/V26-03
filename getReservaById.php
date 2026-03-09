<?php

session_start();

require './vendor/autoload.php';
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

if(isset($_SESSION['reservaId'])){

    $select = '*,space(*,establecimiento(*,host(*)),schedule(*,services(*))),user(*)';
    $select_encoded = urlencode($select);

    $url = 'http://' . $_ENV['SERVER_IP'] . ':' . $_ENV['DATABASE_PORT'] . '/rest/v1/reservation?select=' . $select_encoded . '&id=eq.' . $_SESSION['reservaId'];
    $ch = curl_init($url);
    curl_setopt_array($ch, array(
        CURLOPT_CUSTOMREQUEST => "GET",
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json',
            'Authorization: Bearer ' . $_SESSION['token'],
            'apikey: ' . $_ENV['DATABASE_APIKEY']
        ),
        CURLOPT_RETURNTRANSFER => true,
    ));
    
    $resultado = curl_exec($ch);
    $codigoRespuesta = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if($codigoRespuesta >= 200 && $codigoRespuesta < 300){
        echo $resultado;
    } else if ($codigoRespuesta == 401){
        http_response_code(401);
        header('Location: logout.php');
        exit;
    } else {
        echo json_encode(["error" => "Error en la peticion"]);
    }
    
    curl_close($ch);
} else {
    echo json_encode(["error" => "No estas logeado."]);
    exit;
}



?>