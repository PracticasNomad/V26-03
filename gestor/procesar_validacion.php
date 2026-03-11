<?php
ob_start(); 
session_start();
require '../vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

$isAjax = isset($_GET['ajax']) && $_GET['ajax'] == '1';
$id = $_GET['id'] ?? null;
$accion = $_POST['accion'] ?? $_GET['accion'] ?? null;

if (!$id || !$accion) {
    if ($isAjax) {
        ob_clean();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'error' => 'Faltan datos de ID o Acción']);
        exit;
    }
    header('Location: verValidar.php');
    exit;
}

// Preparamos el dato que vamos a actualizar
$update = [];
if ($accion === 'aprobar') {
    $update = ['estaValidado' => true];
} elseif ($accion === 'rechazar') {
    $update = ['estaValidado' => false];
} else {
    if ($isAjax) {
        ob_clean();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'error' => 'Acción no válida']);
        exit;
    }
    header('Location: verValidar.php');
    exit;
}

// Conexión REAL a tu API (PostgREST)
$url = 'http://' . $_ENV['SERVER_IP'] . ':' . $_ENV['DATABASE_PORT'] . '/rest/v1/establecimiento?id=eq.' . $id;
$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CUSTOMREQUEST => 'PATCH',
    CURLOPT_POSTFIELDS => json_encode($update),
    CURLOPT_HTTPHEADER => [
        'apikey: ' . $_ENV['DATABASE_APIKEY'],
        'Authorization: Bearer ' . ($_SESSION['token'] ?? ''),
        'Content-Type: application/json',
        'Prefer: return=representation'
    ],
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$data = json_decode($response, true);
$filasAfectadas = is_array($data) ? count($data) : 0;

if ($isAjax) {
    ob_clean();
    header('Content-Type: application/json; charset=utf-8');
    
    if ($httpCode >= 400) {
        echo json_encode(['success' => false, 'error' => 'Error API (Código ' . $httpCode . ')']);
        exit;
    }
    
    if ($filasAfectadas === 0) {
        echo json_encode(['success' => false, 'error' => 'Supabase denegó el cambio (RLS o ID no encontrado).']);
        exit;
    }

    echo json_encode([
        'success' => true,
        'message' => $accion === 'aprobar'
            ? 'El establecimiento ha sido aprobado correctamente.'
            : 'El establecimiento ha sido rechazado correctamente.'
    ]);
    exit;
}

header('Location: verValidar.php');
exit;
?>