<?php
session_start();

require './vendor/autoload.php';
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

header('Content-Type: application/json');

if(isset($_SESSION['user_id'])){
    // Obtener los IDs de establecimientos desde el parámetro GET
    $establecimiento_ids = isset($_GET['ids']) ? $_GET['ids'] : '';
    
    if(empty($establecimiento_ids)) {
        echo json_encode([]);
        exit;
    }
    
    // Construir la consulta para obtener la primera imagen de cada establecimiento
    $url = 'http://' . $_ENV['SERVER_IP'] . ':' . $_ENV['DATABASE_PORT'] . '/rest/v1/gallery?select=establecimiento_id,image_url&establecimiento_id=in.(' . $establecimiento_ids . ')&order=establecimiento_id.asc';
    
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
        $images = json_decode($resultado, true);
        
        // Organizar las imágenes por establecimiento_id (solo la primera de cada uno)
        $imagesByEstablecimiento = [];
        foreach($images as $image) {
            $establecimiento_id = $image['establecimiento_id'];
            if(!isset($imagesByEstablecimiento[$establecimiento_id])) {
                $imagesByEstablecimiento[$establecimiento_id] = $image['image_url'];
            }
        }
        
        echo json_encode($imagesByEstablecimiento);
    } else if ($codigoRespuesta == 401){
        http_response_code(401);
        echo json_encode(["error" => "Sesión expirada."]);
        exit;
    } else {
        echo json_encode([]);
    }
    
    curl_close($ch);
} else {
    http_response_code(401);
    echo json_encode(["error" => "No estas logeado."]);
    exit;
}
?>