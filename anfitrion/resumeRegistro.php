<?php
session_start();
require '../vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->safeLoad();

if (!isset($_GET['token']) || empty($_GET['token'])) {
    die("Enlace inválido o caducado.");
}

$token = trim($_GET['token']);
$supabaseKey = $_ENV['DATABASE_APIKEY'];

// Buscamos el borrador por el token
$url = 'http://' . $_ENV['SERVER_IP'] . ':' . $_ENV['DATABASE_PORT'] . '/rest/v1/registros_abandonados?token=eq.' . urlencode($token);
$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'apikey: ' . $supabaseKey,
        'Authorization: Bearer ' . $supabaseKey
    ]
]);
$resultado = curl_exec($ch);
curl_close($ch);

$datos = json_decode($resultado, true);

if (is_array($datos) && count($datos) > 0) {
    $borrador = $datos[0];

    // RESTAURAMOS LA SESIÓN COMPLETA DESDE EL JSONB DE SUPABASE
    if (!empty($borrador['datos_sesion'])) {
        $sesionGuardada = is_string($borrador['datos_sesion']) ? json_decode($borrador['datos_sesion'], true) : $borrador['datos_sesion'];
        foreach ($sesionGuardada as $key => $value) {
            $_SESSION[$key] = $value;
        }
    }

    // Redirigimos al paso donde se quedó
    $pasoDestino = $borrador['paso'] ?? 2;
    if ($pasoDestino === 'Verificar') {
        header("Location: registerAnfitrion-pasoVerificar.php");
    } else {
        header("Location: registerAnfitrion-paso" . $pasoDestino . ".php");
    }
    exit;
} else {
    die("No se ha encontrado el registro o el enlace ha caducado.");
}
