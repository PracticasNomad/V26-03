<?php
session_start();

$envPath = __DIR__ . '/../.env';
$env = parse_ini_file($envPath);

if ($env === false) {
    die('No se pudo leer el archivo .env en: ' . $envPath);
}

// -------------------------------------------------------------------
// 1. IDENTIFICAR QUÉ ESTAMOS COBRANDO (Reserva vs Suscripción)
// -------------------------------------------------------------------
$tipo_operacion = $_POST['tipo_operacion'] ?? 'reserva';

if ($tipo_operacion === 'suscripcion_host' || $tipo_operacion === 'suscripcion_gestor') {

    // --- LÓGICA DE SUSCRIPCIONES ---
    if (empty($_POST['amount'])) {
        die('Importe de suscripción inválido. Vuelve atrás y selecciona un plan.');
    }

    $amount = (int) $_POST['amount']; // Ya viene en céntimos desde el data-amount del HTML
    $plan = $_POST['plan'] ?? 'Premium';
    $periodo = $_POST['subscriptionPlan'] ?? 'mensual';
    $description = 'Suscripcion Plan ' . $plan . ' (' . $periodo . ')';

    // Guardamos en sesión lo que ha elegido para activarlo en pago-ok.php
    $_SESSION['pending_op'] = $tipo_operacion;
    $_SESSION['pending_plan'] = $plan;
    $_SESSION['pending_period'] = $periodo;
    $_SESSION['pending_amount'] = $amount;
} else {

    // --- LÓGICA DE RESERVAS (Tu código original 100% intacto) ---
    if (!isset($_SESSION['reserva'])) {
        die('No hay reserva en sesión');
    }

    $reserva = $_SESSION['reserva'];
    $total = (float) ($reserva['price']['total'] ?? 0);

    if ($total <= 0) {
        die('Total inválido');
    }

    $amount = (int) round($total * 100);
    $description = 'Reserva Nomadapp';
    $_SESSION['pending_op'] = 'reserva';
}


// -------------------------------------------------------------------
// 2. CREACIÓN DEL PAYLOAD DE PAYLANDS (Tu código original)
// -------------------------------------------------------------------
$orderExtId = 'nomad_' . time();

$payload = [
    'signature' => $env['PAYLANDS_SIGNATURE'],
    'amount' => $amount,
    'operative' => 'AUTHORIZATION',
    'secure' => true,
    'service' => $env['PAYLANDS_SERVICE_UUID'],
    'description' => $description,
    'order_ext_id' => $orderExtId,
    'url_post' => $env['PAYLANDS_URL_POST'],
    'url_ok' => $env['PAYLANDS_URL_OK'],
    'url_ko' => $env['PAYLANDS_URL_KO']
];

// -------------------------------------------------------------------
// 3. CONEXIÓN cURL (Tu código original)
// -------------------------------------------------------------------
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

// -------------------------------------------------------------------
// 4. REDIRECCIÓN AL TPV (Tu código original)
// -------------------------------------------------------------------
$token = $data['order']['token'];
$orderUuid = $data['order']['uuid'] ?? null;

$_SESSION['paylands_order_uuid'] = $orderUuid;
$_SESSION['paylands_token'] = $token;
$_SESSION['paylands_order_ext_id'] = $orderExtId;

header('Location: ' . $env['PAYLANDS_BASE_URL'] . '/payment/process/' . urlencode($token));
exit;
