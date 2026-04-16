<?php
session_start();

$envPath = __DIR__ . '/../.env';
$env = parse_ini_file($envPath);

if ($env === false) {
    die('No se pudo leer el archivo .env en: ' . $envPath);
}

if (!isset($_SESSION['reserva'])) {
    die('No hay reserva en sesión');
}

$reserva = $_SESSION['reserva'];
$total = (float) ($reserva['price']['total'] ?? 0);

if ($total <= 0) {
    die('Total inválido');
}

$amount = (int) round($total * 100);
$orderExtId = 'nomad_' . time();

$payload = [
    'signature' => $env['PAYLANDS_SIGNATURE'],
    'amount' => $amount,
    'operative' => 'AUTHORIZATION',
    'secure' => true,
    'service' => $env['PAYLANDS_SERVICE_UUID'],
    'description' => 'Reserva Nomadapp',
    'order_ext_id' => $orderExtId,
    'url_post' => $env['PAYLANDS_URL_POST'],
    'url_ok' => $env['PAYLANDS_URL_OK'],
    'url_ko' => $env['PAYLANDS_URL_KO']
];

$ch = curl_init($env['PAYLANDS_BASE_URL'] . '/payment');

curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
    CURLOPT_USERPWD => $env['PAYLANDS_API_KEY'] . ':',
    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    CURLOPT_POSTFIELDS => json_encode($payload),
    CURLOPT_TIMEOUT => 30,
]);

$response = curl_exec($ch);

if ($response === false) {
    die('Error cURL: ' . curl_error($ch));
}

$http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$data = json_decode($response, true);

if (!is_array($data)) {
    exit('Respuesta inválida de Paylands');
}

if ($http !== 200 || empty($data['order']['token'])) {
    echo '<pre>';
    print_r($data);
    echo '</pre>';
    exit('Error creando pago');
}

$token = $data['order']['token'];
$orderUuid = $data['order']['uuid'] ?? null;

$_SESSION['paylands_order_uuid'] = $orderUuid;
$_SESSION['paylands_token'] = $token;
$_SESSION['paylands_order_ext_id'] = $orderExtId;

header('Location: ' . $env['PAYLANDS_BASE_URL'] . '/payment/process/' . urlencode($token));
exit;