<?php
// Archivo: anfitrion/procesarAltaCobrosHost.php

session_start();
require '../vendor/autoload.php';
require_once '../includes/PaylandsClient.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

if (!isset($_SESSION['user_id']) || empty($_SESSION['token'])) {
    die(json_encode(['success' => false, 'message' => 'No estás logueado.']));
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // 1. Validar y limpiar campos
    $nombrePila = trim($_POST['nombre'] ?? '');
    $apellidos  = trim($_POST['apellidos'] ?? '');
    $empresa    = trim($_POST['empresa'] ?? '');
    $telefono   = trim($_POST['telefono'] ?? '');
    $dni        = strtoupper(trim($_POST['dni'] ?? ''));
    $fecha_nac  = $_POST['fecha_nacimiento'] ?? '';
    $iban       = str_replace(' ', '', strtoupper($_POST['iban'] ?? ''));

    if (empty($nombrePila) || empty($apellidos) || empty($dni) || empty($iban)) {
        echo json_encode(['success' => false, 'message' => 'Faltan campos obligatorios.']);
        exit;
    }

    $datosAnfitrion = [
        'nombre_completo'  => $nombrePila . ' ' . $apellidos,
        'nombre_comercial' => empty($empresa) ? ($nombrePila . ' ' . $apellidos) : $empresa,
        'nombre_pila'      => $nombrePila,
        'apellidos'        => $apellidos,
        'email'            => $_SESSION['email'],
        'telefono'         => $telefono,
        'dni'              => $dni,
        'fecha_nacimiento' => $fecha_nac,
        'caducidad_dni'    => $_POST['caducidad_dni'] ?? '2030-01-01',
        'direccion'        => trim($_POST['direccion'] ?? ''),
        'localidad'        => trim($_POST['localidad'] ?? ''),
        'provincia'        => trim($_POST['provincia'] ?? ''),
        'codigo_postal'    => trim($_POST['codigo_postal'] ?? ''),
        'iban'             => $iban,
        'banco_nombre'     => trim($_POST['banco_nombre'] ?? 'Banco')
    ];

    try {
        $paylands = new PaylandsClient();
        $respuesta = $paylands->registrarAutonomo($datosAnfitrion);

        if ($respuesta['status'] >= 200 && $respuesta['status'] < 300 && isset($respuesta['data']['merchant_id'])) {

            $merchantId = $respuesta['data']['merchant_id'];

            // 3. ACTUALIZAR TODOS LOS DATOS EN SUPABASE
            $urlSupabase = 'http://' . $_ENV['SERVER_IP'] . ':' . $_ENV['DATABASE_PORT'] . '/rest/v1/host?id=eq.' . $_SESSION['user_id'];
            $ch = curl_init($urlSupabase);

            // Aquí mapeamos con los nombres exactos de tu captura de pantalla
            $payloadBd = json_encode([
                'paylands_merchant_id' => (string) $merchantId,
                'name'                 => $nombrePila,
                'apellidos'            => $apellidos,
                'empresa'              => $empresa,
                'phone'                => $telefono,
                'nif'                  => $dni,
                'fecha_nac'            => $fecha_nac
            ]);

            curl_setopt_array($ch, [
                CURLOPT_CUSTOMREQUEST  => 'PATCH',
                CURLOPT_POSTFIELDS     => $payloadBd,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER     => [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $_SESSION['token'],
                    'apikey: ' . $_ENV['DATABASE_APIKEY']
                ]
            ]);

            $resBd = curl_exec($ch);
            $httpCodeBd = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCodeBd >= 200 && $httpCodeBd < 300) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Cobros configurados y perfil actualizado.'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Paylands OK, pero error al actualizar Supabase.'
                ]);
            }
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Error validando datos bancarios.',
                'paylands_error' => $respuesta['data']
            ]);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
