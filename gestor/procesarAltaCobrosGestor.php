<?php
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

    $empresa = trim($_POST['empresa'] ?? '');
    $cif     = strtoupper(trim($_POST['cif'] ?? ''));
    $iban    = str_replace(' ', '', strtoupper($_POST['iban'] ?? ''));

    if (empty($empresa) || empty($cif) || empty($iban)) {
        echo json_encode(['success' => false, 'message' => 'Faltan campos obligatorios.']);
        exit;
    }

    $datosGestora = [
        'empresa'                     => $empresa,
        'cif'                         => $cif,
        'email'                       => $_SESSION['email'],
        'telefono'                    => trim($_POST['telefono'] ?? ''),
        'direccion'                   => trim($_POST['direccion'] ?? ''),
        'localidad'                   => trim($_POST['localidad'] ?? ''),
        'provincia'                   => trim($_POST['provincia'] ?? ''),
        'codigo_postal'               => trim($_POST['codigo_postal'] ?? ''),
        'iban'                        => $iban,
        'representante_nombre'        => trim($_POST['representante_nombre'] ?? ''),
        'representante_apellidos'     => trim($_POST['representante_apellidos'] ?? ''),
        'representante_dni'           => strtoupper(trim($_POST['representante_dni'] ?? '')),
        'representante_nacimiento'    => $_POST['representante_nacimiento'] ?? '',
        'representante_caducidad_dni' => $_POST['representante_caducidad_dni'] ?? '2030-01-01',
    ];

    try {
        $paylands = new PaylandsClient();
        // USAMOS LA NUEVA FUNCIÓN PARA EMPRESAS
        $respuesta = $paylands->registrarEmpresa($datosGestora);

        if ($respuesta['status'] >= 200 && $respuesta['status'] < 300 && isset($respuesta['data']['merchant_id'])) {

            $merchantId = $respuesta['data']['merchant_id'];

            // GUARDAR EN LA TABLA GESTOR
            $urlSupabase = 'http://' . $_ENV['SERVER_IP'] . ':' . $_ENV['DATABASE_PORT'] . '/rest/v1/gestor?id=eq.' . $_SESSION['user_id'];
            $ch = curl_init($urlSupabase);

            $payloadBd = json_encode([
                'paylands_merchant_id' => (string) $merchantId,
                'empresa'              => $empresa,
                'cif'                  => $cif,
                'phone'                => $datosGestora['telefono'],
                'direccion'            => $datosGestora['direccion'],
                'localidad'            => $datosGestora['localidad'],
                'provincia'            => $datosGestora['provincia'],
                'codigo_postal'        => $datosGestora['codigo_postal']
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
                echo json_encode(['success' => true, 'message' => 'Cuenta de Gestora activada correctamente.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Paylands OK, error en BD.']);
            }
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Error validando empresa en Paylands.',
                'paylands_error' => $respuesta['data']
            ]);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
