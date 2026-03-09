<?php

require_once 'verificar_sesion_host.php';

require '../vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

$id_space = $_GET['id'];
if (isset($id_space)) {
    $url = 'http://' . $_ENV['SERVER_IP'] . ':' . $_ENV['DATABASE_PORT'] . '/rest/v1/space?id=eq.' . $id_space;
    $ch = curl_init($url);
    curl_setopt_array($ch, array(
        CURLOPT_CUSTOMREQUEST => 'DELETE',
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json',
            'Authorization: Bearer ' . $_SESSION['token'],
            'apikey: ' . $_ENV['DATABASE_APIKEY']
        ),
        CURLOPT_RETURNTRANSFER => true,
    ));

    $resultado = curl_exec($ch);
    $codigoRespuesta = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if ($codigoRespuesta >= 200 && $codigoRespuesta < 300) {
        if ($resultado == "") {
            echo json_encode([
                "success" => true,
                "message" => "Espacio eliminado correctamente"
            ]);
        } else {
            echo $resultado;
        }
    } else if ($codigoRespuesta == 401) {
        header('Location: logout.php');
        exit;
    } else {
        echo json_encode(["error" => "Error en la peticion"]);
    }

    curl_close($ch);
}
