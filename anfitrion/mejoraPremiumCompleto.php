<?php
require_once 'verificar_sesion_host.php';

require '../vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

/*
if (!isset($_SESSION['user_id']) || !isset($_SESSION['token'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'No hay sesion activa']);
}
*/

$url = 'http://' . $_ENV['SERVER_IP'] . ':' . $_ENV['DATABASE_PORT'] . '/rest/v1/host?id=eq.' . $_SESSION['user_id'];
$ch = curl_init($url);

$data = [
    'plan' => $_SESSION['tipoSuscripcion'],
    'plan_end' => $_SESSION['fecha_fin']
];

curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    'Content-Type: application/json',
    'apikey: ' . $_ENV['DATABASE_APIKEY'],
    'Authorization: Bearer ' . $_SESSION['token']
));

curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

$resultado = curl_exec($ch);
$codigoRespuesta = curl_getinfo($ch, CURLINFO_HTTP_CODE);

header('Content-Type: application/json');

if ($codigoRespuesta >= 200 && $codigoRespuesta < 300) {
    echo json_encode(['success' => true, 'message' => 'Perfil actualizado correctamente']);
    unset($_SESSION['direccion'], $_SESSION['plan'], $_SESSION['total'], $_SESSION['tipoSuscripcion']);
    curl_close($ch);
    header('Location: VistaPremiumCompletada.php');
    exit;
} else {
    echo json_encode(['success' => false, 'message' => 'Error al actualizar el perfil. Código: ' . $codigoRespuesta]);
    curl_close($ch);
}
