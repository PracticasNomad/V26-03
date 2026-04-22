<?php
session_start();
require '../vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

// Identificamos qué tipo de operación acaba de pagar con éxito
$tipo_operacion = $_SESSION['pending_op'] ?? 'reserva';
$user_id = $_SESSION['user_id'] ?? null;

$baseUrl = isset($_ENV['SUPABASE_URL']) ? $_ENV['SUPABASE_URL'] : "http://" . $_ENV['SERVER_IP'] . ":" . $_ENV['DATABASE_PORT'];
$apiKey = isset($_ENV['SERVICE_APIKEY']) ? $_ENV['SERVICE_APIKEY'] : $_ENV['DATABASE_APIKEY'];

// Función de ayuda para actualizar los planes en Supabase
function actualizarPlanSuscripcion($baseUrl, $apiKey, $tabla, $userId, $nuevoPlan, $periodo)
{
    // Si eligió anual le sumamos 1 año, si no, 1 mes.
    $tiempoSumar = ($periodo === 'anual') ? '+1 year' : '+1 month';
    $plan_end = date('Y-m-d H:i:s', strtotime($tiempoSumar));

    $url = $baseUrl . "/rest/v1/" . $tabla . "?id=eq." . $userId;
    $data = json_encode([
        'plan' => $nuevoPlan,
        'plan_end' => $plan_end
    ]);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'apikey: ' . $apiKey,
        'Authorization: Bearer ' . $apiKey,
        'Prefer: return=minimal'
    ]);

    curl_exec($ch);
    curl_close($ch);
}

// -------------------------------------------------------------
// 1. LÓGICA SI PAGÓ UNA SUSCRIPCIÓN DE ANFITRIÓN
// -------------------------------------------------------------
if ($tipo_operacion === 'suscripcion_host' && $user_id) {
    $nuevoPlan = $_SESSION['pending_plan'] ?? 'Pro';
    $periodo = $_SESSION['pending_period'] ?? 'mensual';

    actualizarPlanSuscripcion($baseUrl, $apiKey, 'host', $user_id, $nuevoPlan, $periodo);

    // Limpiamos la sesión de compra
    unset($_SESSION['pending_op'], $_SESSION['pending_plan'], $_SESSION['pending_period'], $_SESSION['pending_amount']);

    // Redirigimos a la vista de éxito
    if ($nuevoPlan === 'Premium') {
        header("Location: ../anfitrion/VistaPremiumCompletada.php");
    } else {
        header("Location: ../anfitrion/VistaProCompletada.php");
    }
    exit;

    // -------------------------------------------------------------
    // 2. LÓGICA SI PAGÓ UNA SUSCRIPCIÓN DE GESTORA
    // -------------------------------------------------------------
} elseif ($tipo_operacion === 'suscripcion_gestor' && $user_id) {
    $nuevoPlan = $_SESSION['pending_plan'] ?? 'Pro';
    $periodo = $_SESSION['pending_period'] ?? 'mensual';

    actualizarPlanSuscripcion($baseUrl, $apiKey, 'gestor', $user_id, $nuevoPlan, $periodo);

    // Limpiamos la sesión de compra
    unset($_SESSION['pending_op'], $_SESSION['pending_plan'], $_SESSION['pending_period'], $_SESSION['pending_amount']);

    if ($nuevoPlan === 'Premium') {
        header("Location: ../gestor/VistaPremiumGestorCompletada.php");
    } else {
        header("Location: ../gestor/VistaProGestorCompletada.php");
    }
    exit;

    // -------------------------------------------------------------
    // 3. LÓGICA SI PAGÓ UNA RESERVA
    // -------------------------------------------------------------
} elseif ($tipo_operacion === 'reserva') {
    // Aquí ejecutarías el POST a procesarReserva.php o la inserción directa en tu tabla "reservas"
    // usando los datos que tienes en $_SESSION['reserva_temp']

    unset($_SESSION['pending_op']);
    header("Location: ../reservarEspacio-completo.php");
    exit;
} else {
    // Por si se accede al archivo sin operación pendiente
    header("Location: ../index.php");
    exit;
}
