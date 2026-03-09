<?php

session_start();
$token = $_SESSION["token"];
$id = $_SESSION["anfitrion_id"];
$email = urlencode($_SESSION["email"]);

require './vendor/autoload.php';
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Obtener datos del establecimiento
$url = "http://" . $_ENV['SERVER_IP'] . ":" . $_ENV['DATABASE_PORT'] . "/rest/v1/establecimiento?id=eq." . $id;

$ch = curl_init($url);
curl_setopt_array($ch, array(
    CURLOPT_CUSTOMREQUEST => "GET",
    CURLOPT_HTTPHEADER => array(
        'Content-Type: application/json',
        'apikey: ' . $_ENV['DATABASE_APIKEY'],
    ),
    CURLOPT_RETURNTRANSFER => true,
));

$resultado = curl_exec($ch);
$codigoRespuesta = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($codigoRespuesta === 200) {
    $datos = json_decode($resultado, true);
    if (count($datos) > 0) {
        $establecimiento = $datos[0];
        
        // Obtener las imágenes de la galería
        $urlGaleria = "http://" . $_ENV['SERVER_IP'] . ":" . $_ENV['DATABASE_PORT'] . "/rest/v1/gallery?establecimiento_id=eq." . $id;
        
        $chGaleria = curl_init($urlGaleria);
        curl_setopt_array($chGaleria, array(
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'apikey: ' . $_ENV['DATABASE_APIKEY'],
            ),
            CURLOPT_RETURNTRANSFER => true,
        ));
        
        $resultadoGaleria = curl_exec($chGaleria);
        $codigoRespuestaGaleria = curl_getinfo($chGaleria, CURLINFO_HTTP_CODE);
        curl_close($chGaleria);
        
        if ($codigoRespuestaGaleria === 200) {
            $imagenes = json_decode($resultadoGaleria, true);
            // Si no hay imágenes, agregar imagen por defecto
            if (empty($imagenes)) {
                $imagenes = [["image_url" => "./img/noimagen.jpg"]];
            }
            $establecimiento['gallery'] = $imagenes;
        } else {
            // Si hay error obteniendo la galería, usar imagen por defecto
            $establecimiento['gallery'] = [["image_url" => "./img/noimagen.jpg"]];
        }
        
        echo json_encode($establecimiento);
    } else {
        echo json_encode(["error" => "Usuario no encontrado"]);
    }
} else {
    echo json_encode(["error" => "Error consultando Supabase"]);
}