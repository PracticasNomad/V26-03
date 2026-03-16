<?php

require_once '../vendor/autoload.php';
require_once '../emails/notificacionesAnfitrion.php'; // Cargamos tu función de enviar correos

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->safeLoad();

$supabaseKey = $_ENV['DATABASE_APIKEY'];
$serverIp = $_ENV['SERVER_IP'];
$dbPort = $_ENV['DATABASE_PORT'];

//  Calculamos la hora límite (Hace 1 hora en UTC, que es como guarda Supabase)
// $limite_tiempo = gmdate('Y-m-d\TH:i:s', strtotime('-1 hour'));


$limite_tiempo = gmdate('Y-m-d\TH:i:s', strtotime('-1 minute'));

echo "Buscando registros abandonados antes de: " . $limite_tiempo . "<br>\n";

// Buscamos en la base de datos a los que llevan más de ese tiempo
$url = 'http://' . $serverIp . ':' . $dbPort . '/rest/v1/registros_abandonados?created_at=lte.' . urlencode($limite_tiempo);

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'apikey: ' . $supabaseKey,
    'Authorization: Bearer ' . $supabaseKey
]);
$response = curl_exec($ch);
curl_close($ch);

$registros = json_decode($response, true);

if (empty($registros) || isset($registros['error'])) {
    echo "No hay registros abandonados pendientes de avisar.<br>\n";
    exit;
}

// Procesamos cada usuario abandonado
foreach ($registros as $registro) {
    $email = $registro['email'];
    $nombre = $registro['nombre'];
    $id = $registro['id'];
    
    echo "Procesando a: $email... ";
    
    // Llamamos a la función que creamos en notificacionesAnfitrion.php
    $correoEnviado = enviarCorreoAnfitrionSinEstablecimiento($email, $nombre);
    
    if ($correoEnviado) {
        echo "¡Correo enviado con éxito! ";
        
        // Lo borramos de la tabla para no volver a molestarle
        $urlBorrar = 'http://' . $serverIp . ':' . $dbPort . '/rest/v1/registros_abandonados?id=eq.' . $id;
        $chBorrar = curl_init($urlBorrar);
        curl_setopt($chBorrar, CURLOPT_CUSTOMREQUEST, "DELETE");
        curl_setopt($chBorrar, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($chBorrar, CURLOPT_HTTPHEADER, [
            'apikey: ' . $supabaseKey,
            'Authorization: Bearer ' . $supabaseKey
        ]);
        curl_exec($chBorrar);
        curl_close($chBorrar);
        
        echo "(Registro eliminado de la sala de espera).<br>\n";
    } else {
        echo "Error al enviar el correo.<br>\n";
    }
}

echo "Proceso terminado.<br>\n";
?>