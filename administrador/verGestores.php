<?php
require_once 'verificar_sesion_admin.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['token'])) {
    header('Location: inicio_sesion_admin.php');
    exit();
}

require '../vendor/autoload.php';
require_once __DIR__ . '/../gestor/invitacionGestoraToken.php';
require_once __DIR__ . '/../emails/invitacionGestora.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

$apiBase = 'http://' . $_ENV['SERVER_IP'] . ':' . $_ENV['DATABASE_PORT'] . '/rest/v1';
$errorDb = null;

function setFlashMessage($type, $message)
{
    $_SESSION['admin_gestores_flash'] = [
        'type' => $type,
        'message' => $message,
    ];
}

function getFlashMessage()
{
    if (!isset($_SESSION['admin_gestores_flash'])) {
        return null;
    }

    $flash = $_SESSION['admin_gestores_flash'];
    unset($_SESSION['admin_gestores_flash']);

    return $flash;
}

function buildInFilter(array $values)
{
    $formatted = [];

    foreach ($values as $value) {
        $value = (string) $value;

        if ($value === '') {
            continue;
        }

        if (ctype_digit($value) && !(strlen($value) > 1 && $value[0] === '0')) {
            $formatted[] = $value;
            continue;
        }

        $escaped = str_replace(['\\', '"'], ['\\\\', '\\"'], $value);
        $formatted[] = '"' . $escaped . '"';
    }

    return implode(',', array_values(array_unique($formatted)));
}

function apiRequest($path, $method = 'GET', $payload = null)
{
    global $apiBase;

    $url = $apiBase . $path;
    $ch = curl_init($url);

    $headers = [
        'Authorization: Bearer ' . $_ENV['SERVICE_APIKEY'],
        'apikey: ' . $_ENV['SERVICE_APIKEY'],
        'Accept: application/json',
    ];

    $options = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => $headers,
    ];

    if ($method !== 'GET') {
        $options[CURLOPT_CUSTOMREQUEST] = $method;
    }

    if ($payload !== null) {
        $headers[] = 'Content-Type: application/json';
        $headers[] = 'Prefer: return=representation';
        $options[CURLOPT_POSTFIELDS] = json_encode($payload);
        $options[CURLOPT_HTTPHEADER] = $headers;
    }

    curl_setopt_array($ch, $options);
    $body = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    $decoded = null;
    if (is_string($body) && $body !== '') {
        $decoded = json_decode($body, true);
    }

    return [
        'status' => $status,
        'body' => $body,
        'data' => $decoded,
        'error' => $error,
    ];
}

function normalizarEstadoValidacion($value)
{
    if ($value === true || $value === 'true' || $value === 't' || $value === 1 || $value === '1') {
        return 'aprobado';
    }

    if ($value === false || $value === 'false' || $value === 'f' || $value === 0 || $value === '0') {
        return 'rechazado';
    }

    return 'pendiente';
}

function formatPlanName($plan)
{
    $normalized = strtolower(trim((string) $plan));

    if ($normalized === 'premium') {
        return 'Premium';
    }

    if ($normalized === 'pro') {
        return 'Pro';
    }

    if ($normalized === 'basico' || $normalized === 'básico') {
        return 'Basico';
    }

    return $plan !== null && $plan !== '' ? (string) $plan : 'Sin plan';
}

function formatPlanClass($plan)
{
    $normalized = strtolower(trim((string) $plan));

    if ($normalized === 'premium') {
        return 'plan-premium';
    }

    if ($normalized === 'pro') {
        return 'plan-pro';
    }

    return 'plan-basico';
}

function formatDateLabel($value)
{
    if (empty($value)) {
        return 'Sin fecha';
    }

    $timestamp = strtotime((string) $value);
    if ($timestamp === false) {
        return 'Sin fecha';
    }

    return date('d/m/Y', $timestamp);
}

function buildInitials($name)
{
    $parts = preg_split('/\s+/', trim((string) $name));
    $initials = '';

    foreach ($parts as $part) {
        if ($part === '') {
            continue;
        }

        $initials .= strtoupper(substr($part, 0, 1));
        if (strlen($initials) >= 2) {
            break;
        }
    }

    return $initials !== '' ? $initials : 'G';
}

function fetchGestores(&$errorDb = null)
{
    $selects = [
        'id,name,email,phone,empresa,cif,codigo_postal,direccion,localidad,provincia,plan,plan_end,avatar_url',
        'id,name,email,phone,empresa,cif,codigo_postal,direccion,localidad,provincia',
    ];

    foreach ($selects as $select) {
        $response = apiRequest('/gestor?select=' . $select . '&order=name.asc');

        if ($response['status'] >= 200 && $response['status'] < 300 && is_array($response['data'])) {
            return $response['data'];
        }
    }

    $errorDb = 'No se pudieron cargar los gestores desde la base de datos.';

    return [];
}

// CORRECCIÓN INFALIBLE: Select * evita errores 400 de Supabase, y formateamos el CP para que coincida siempre.
function buildStatsByPostalCode(array $gestores)
{
    $statsByCp = [];

    foreach ($gestores as $gestor) {
        $cp = trim((string) ($gestor['codigo_postal'] ?? ''));
        // Rellenamos con ceros si el CP es número y tiene menos de 5 dígitos (ej: 3820 -> 03820)
        if (is_numeric($cp) && strlen($cp) < 5 && $cp !== '') {
            $cp = str_pad($cp, 5, '0', STR_PAD_LEFT);
        }

        if ($cp !== '') {
            if (!isset($statsByCp[$cp])) {
                $statsByCp[$cp] = [
                    'codigo_postal' => $cp,
                    'establecimientos' => 0,
                    'aprobados' => 0,
                    'pendientes' => 0,
                    'rechazados' => 0,
                    'espacios' => 0,
                    'reservas' => 0,
                    'anfitriones' => 0,
                ];
            }
        }
    }

    // DESCARGAMOS TODOS LOS ESTABLECIMIENTOS USANDO * (Para que no falle si la columna no existe)
    $estResp = apiRequest('/establecimiento?select=*,space(id)');
    $establecimientos = is_array($estResp['data']) ? $estResp['data'] : [];

    $hostIdsByCp = [];
    $spaceToCpMap = [];

    foreach ($establecimientos as $est) {
        $cp = trim((string) ($est['codigo_postal'] ?? ''));
        if (is_numeric($cp) && strlen($cp) < 5 && $cp !== '') {
            $cp = str_pad($cp, 5, '0', STR_PAD_LEFT);
        }

        // Si este CP no lo lleva ningún gestor, lo ignoramos
        if ($cp === '' || !isset($statsByCp[$cp])) {
            continue;
        }

        $statsByCp[$cp]['establecimientos']++;

        $val = $est['estaValidado'] ?? $est['estavalidado'] ?? $est['esta_validado'] ?? null;
        $estado = normalizarEstadoValidacion($val);

        if ($estado === 'aprobado') {
            $statsByCp[$cp]['aprobados']++;
        } elseif ($estado === 'rechazado') {
            $statsByCp[$cp]['rechazados']++;
        } else {
            $statsByCp[$cp]['pendientes']++;
        }

        if (!empty($est['host_id'])) {
            $hostIdsByCp[$cp][(string)$est['host_id']] = true;
        }

        if (!empty($est['space']) && is_array($est['space'])) {
            $statsByCp[$cp]['espacios'] += count($est['space']);
            foreach ($est['space'] as $sp) {
                if (isset($sp['id'])) {
                    $spaceToCpMap[$sp['id']] = $cp;
                }
            }
        }
    }

    foreach ($hostIdsByCp as $cp => $hostIds) {
        $statsByCp[$cp]['anfitriones'] = count($hostIds);
    }

    // DESCARGAMOS TODAS LAS RESERVAS USANDO *
    $resResp = apiRequest('/reservation?select=*,space(*)');
    $reservas = is_array($resResp['data']) ? $resResp['data'] : [];

    foreach ($reservas as $res) {
        $cp = '';

        // Asignamos la reserva al CP del espacio que mapeamos antes
        if (!empty($res['space_id']) && isset($spaceToCpMap[$res['space_id']])) {
            $cp = $spaceToCpMap[$res['space_id']];
        }

        if ($cp !== '' && isset($statsByCp[$cp])) {
            $statsByCp[$cp]['reservas']++;
        }
    }

    return $statsByCp;
}

function sanitizeText($key)
{
    return trim((string) ($_POST[$key] ?? ''));
}

function normalizeInvitePlan($value)
{
    $normalized = strtolower(trim((string) $value));

    if ($normalized === 'premium') {
        return 'Premium';
    }

    if ($normalized === 'pro') {
        return 'Pro';
    }

    return 'Basico';
}

function buildAppBaseUrl()
{
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $scriptName = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
    $basePath = dirname(dirname($scriptName));

    if ($basePath === '\\' || $basePath === '/' || $basePath === '.') {
        $basePath = '';
    }

    return $scheme . '://' . $host . rtrim($basePath, '/');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $gestorId = trim((string) ($_POST['gestor_id'] ?? ''));

    if ($action === 'enviar_invitacion_gestora') {
        $nombre = sanitizeText('invite_nombre');
        $email = sanitizeText('invite_email');
        $empresa = sanitizeText('invite_empresa');
        $cif = strtoupper(sanitizeText('invite_cif'));
        $codigoPostal = sanitizeText('invite_codigo_postal');
        $plan = normalizeInvitePlan($_POST['invite_plan'] ?? 'Basico');

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            setFlashMessage('danger', 'Debes indicar un correo valido para enviar la invitacion.');
            header('Location: verGestores.php');
            exit();
        }

        if (!preg_match('/^[0-9]{5}$/', $codigoPostal)) {
            setFlashMessage('danger', 'El codigo postal inicial debe contener 5 digitos.');
            header('Location: verGestores.php');
            exit();
        }

        $existingGestor = apiRequest('/gestor?select=id&email=eq.' . urlencode($email) . '&limit=1');
        if ($existingGestor['status'] >= 200 && $existingGestor['status'] < 300 && !empty($existingGestor['data'])) {
            setFlashMessage('warning', 'Ya existe una gestora registrada con ese correo electronico.');
            header('Location: verGestores.php');
            exit();
        }

        $token = createGestoraInvitationToken([
            'email' => $email,
            'nombre' => $nombre,
            'empresa' => $empresa,
            'cif' => $cif,
            'codigo_postal' => $codigoPostal,
            'plan' => $plan,
            'rol' => 'gestora',
        ]);

        $inviteLink = buildAppBaseUrl() . '/gestor/registerGestora.php?token=' . urlencode($token);
        $mailResult = enviarCorreoInvitacionGestora($email, $inviteLink, $empresa, $codigoPostal, $plan);

        if (!empty($mailResult['success'])) {
            setFlashMessage('success', 'La invitacion para registrar la gestora se envio a ' . $email . '.');
        } else {
            setFlashMessage('danger', $mailResult['message'] ?? 'No se pudo enviar la invitacion.');
        }

        header('Location: verGestores.php');
        exit();
    }

    if ($gestorId === '') {
        setFlashMessage('danger', 'No se recibio un gestor valido.');
        header('Location: verGestores.php');
        exit();
    }

    if ($action === 'editar_gestor') {
        $nombre = sanitizeText('nombre');
        $email = sanitizeText('email');
        $telefono = sanitizeText('telefono');
        $empresa = sanitizeText('empresa');
        $cif = strtoupper(sanitizeText('cif'));
        $direccion = sanitizeText('direccion');
        $localidad = sanitizeText('localidad');
        $provincia = sanitizeText('provincia');
        $plan = sanitizeText('plan');

        if ($nombre === '' || $email === '') {
            setFlashMessage('danger', 'El nombre y el correo electronico son obligatorios.');
            header('Location: verGestores.php');
            exit();
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            setFlashMessage('danger', 'El correo electronico no tiene un formato valido.');
            header('Location: verGestores.php');
            exit();
        }

        $payload = [
            'name' => $nombre,
            'email' => $email,
            'phone' => $telefono,
            'empresa' => $empresa,
            'cif' => $cif,
            'direccion' => $direccion,
            'localidad' => $localidad,
            'provincia' => $provincia,
        ];

        if ($plan !== '') {
            $payload['plan'] = $plan;
        }

        $response = apiRequest('/gestor?id=eq.' . urlencode($gestorId), 'PATCH', $payload);

        if ($response['status'] >= 200 && $response['status'] < 300) {
            setFlashMessage('success', 'Los datos del gestor se actualizaron correctamente.');
        } else {
            setFlashMessage('danger', 'No se pudieron actualizar los datos del gestor.');
        }

        header('Location: verGestores.php');
        exit();
    }

    if ($action === 'reasignar_cp') {
        $codigoPostal = sanitizeText('codigo_postal');

        if (!preg_match('/^[0-9]{5}$/', $codigoPostal)) {
            setFlashMessage('danger', 'El nuevo codigo postal debe contener 5 digitos.');
            header('Location: verGestores.php');
            exit();
        }

        $response = apiRequest('/gestor?id=eq.' . urlencode($gestorId), 'PATCH', [
            'codigo_postal' => $codigoPostal,
        ]);

        if ($response['status'] >= 200 && $response['status'] < 300) {
            setFlashMessage('success', 'El codigo postal del gestor fue reasignado correctamente.');
        } else {
            setFlashMessage('danger', 'No se pudo reasignar el codigo postal del gestor.');
        }

        header('Location: verGestores.php');
        exit();
    }

    if ($action === 'eliminar_gestor') {
        $response = apiRequest('/gestor?id=eq.' . urlencode($gestorId), 'DELETE');

        if ($response['status'] >= 200 && $response['status'] < 300) {
            setFlashMessage('success', 'El gestor se elimino correctamente.');
        } else {
            setFlashMessage('danger', 'No se pudo eliminar el gestor seleccionado.');
        }

        header('Location: verGestores.php');
        exit();
    }

    setFlashMessage('warning', 'La accion solicitada no es valida.');
    header('Location: verGestores.php');
    exit();
}

$flash = getFlashMessage();
$gestores = fetchGestores($errorDb);
$statsByCp = buildStatsByPostalCode($gestores);

$totalGestores = count($gestores);
$activePostalCodes = [];
$totalesGlobales = [
    'establecimientos' => 0,
    'reservas' => 0,
    'espacios' => 0,
];
$countedPostalCodes = [];

$gestoresForJs = [];

foreach ($gestores as &$gestor) {
    $cp = trim((string) ($gestor['codigo_postal'] ?? ''));
    if (is_numeric($cp) && strlen($cp) < 5 && $cp !== '') {
        $cp = str_pad($cp, 5, '0', STR_PAD_LEFT);
    }

    $gestor['codigo_postal'] = $cp;
    $gestor['plan_label'] = formatPlanName($gestor['plan'] ?? '');
    $gestor['plan_class'] = formatPlanClass($gestor['plan'] ?? '');
    $gestor['plan_end_label'] = formatDateLabel($gestor['plan_end'] ?? null);
    $gestor['initials'] = buildInitials($gestor['name'] ?? '');
    $gestor['estadisticas'] = $statsByCp[$cp] ?? [
        'codigo_postal' => $cp,
        'establecimientos' => 0,
        'aprobados' => 0,
        'pendientes' => 0,
        'rechazados' => 0,
        'espacios' => 0,
        'reservas' => 0,
        'anfitriones' => 0,
    ];

    if ($cp !== '') {
        $activePostalCodes[$cp] = true;
    }

    if ($cp !== '' && isset($statsByCp[$cp]) && !isset($countedPostalCodes[$cp])) {
        $totalesGlobales['establecimientos'] += $statsByCp[$cp]['establecimientos'];
        $totalesGlobales['reservas'] += $statsByCp[$cp]['reservas'];
        $totalesGlobales['espacios'] += $statsByCp[$cp]['espacios'];
        $countedPostalCodes[$cp] = true;
    }

    $gestoresForJs[$gestor['id']] = [
        'id' => (string) ($gestor['id'] ?? ''),
        'name' => (string) ($gestor['name'] ?? ''),
        'email' => (string) ($gestor['email'] ?? ''),
        'phone' => (string) ($gestor['phone'] ?? ''),
        'empresa' => (string) ($gestor['empresa'] ?? ''),
        'cif' => (string) ($gestor['cif'] ?? ''),
        'codigo_postal' => $cp,
        'direccion' => (string) ($gestor['direccion'] ?? ''),
        'localidad' => (string) ($gestor['localidad'] ?? ''),
        'provincia' => (string) ($gestor['provincia'] ?? ''),
        'plan' => (string) ($gestor['plan'] ?? ''),
        'plan_label' => $gestor['plan_label'],
        'plan_end_label' => $gestor['plan_end_label'],
        'estadisticas' => $gestor['estadisticas'],
    ];
}
unset($gestor);

$postalCoverage = count($activePostalCodes);
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://kit.fontawesome.com/b8814a2854.js" crossorigin="anonymous"></script>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <link rel="icon" href="../favicon-color.png">
    <link rel="icon" href="../favicon-negro.png" media="(prefers-color-scheme: light)">
    <link rel="icon" href="../favicon-color.png" media="(prefers-color-scheme: dark)">
    <title>Gestion de Gestores</title>
    <style>
        :root {
            --bg: #f4f7fb;
            --surface: #ffffff;
            --ink: #1f2933;
            --muted: #6b7c93;
            --line: #d8e1ea;
            --accent: #c44536;
            --accent-dark: #8c1c13;
            --accent-soft: #fce8e5;
            --blue-soft: #e6f2ff;
            --green-soft: #e7f8ee;
            --shadow: 0 18px 36px rgba(31, 41, 51, 0.12);
        }

        body {
            font-family: 'Nunito', sans-serif;
            background:
                radial-gradient(circle at 0% 0%, rgba(196, 69, 54, 0.12), transparent 32%),
                radial-gradient(circle at 100% 5%, rgba(43, 86, 140, 0.1), transparent 28%),
                linear-gradient(180deg, #f9fbfd 0%, var(--bg) 100%);
            color: var(--ink);
            padding-bottom: 110px;
        }

        .page-shell {
            max-width: 1380px;
            margin: 0 auto;
            padding: 22px 16px 0;
        }

        .hero {
            background: linear-gradient(135deg, #962d22 0%, #c44536 52%, #df786c 100%);
            color: #fff;
            border-radius: 24px;
            padding: 24px;
            box-shadow: 0 18px 40px rgba(140, 28, 19, 0.24);
            margin-bottom: 20px;
        }

        .hero-title {
            font-size: 1.85rem;
            font-weight: 800;
            margin: 0 0 6px;
        }

        .title-row {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .info-hint-btn {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            border: 1px solid rgba(255, 255, 255, 0.48);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            background: rgba(255, 255, 255, 0.12);
            cursor: pointer;
            transition: 0.2s ease;
            font-size: 0.92rem;
            font-weight: 800;
        }

        .info-hint-btn:hover {
            background: rgba(255, 255, 255, 0.22);
            transform: translateY(-1px);
        }

        .hero-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-top: 14px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.15);
            padding: 8px 14px;
            font-weight: 700;
            letter-spacing: 0.2px;
        }

        .hero-actions {
            display: flex;
            justify-content: flex-end;
            align-items: center;
        }

        .btn-hero-primary {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border-radius: 999px;
            border: 1px solid rgba(255, 255, 255, 0.28);
            background: rgba(255, 255, 255, 0.16);
            color: #ffffff;
            font-weight: 800;
            padding: 12px 18px;
            box-shadow: 0 10px 24px rgba(0, 0, 0, 0.12);
        }

        .btn-hero-primary:hover {
            color: #ffffff;
            background: rgba(255, 255, 255, 0.24);
            transform: translateY(-1px);
        }

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 14px;
            margin-bottom: 20px;
        }

        .summary-card {
            background: var(--surface);
            border: 1px solid rgba(255, 255, 255, 0.5);
            border-radius: 20px;
            padding: 18px 20px;
            box-shadow: var(--shadow);
            position: relative;
            overflow: hidden;
        }

        .summary-card::after {
            content: '';
            position: absolute;
            inset: auto -15px -15px auto;
            width: 90px;
            height: 90px;
            border-radius: 50%;
            background: rgba(196, 69, 54, 0.08);
        }

        .summary-label {
            color: var(--muted);
            font-size: 0.92rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        .summary-value {
            font-size: 2rem;
            font-weight: 800;
            line-height: 1.1;
            margin-top: 6px;
        }

        .summary-meta {
            margin-top: 4px;
            color: var(--muted);
            font-size: 0.95rem;
        }

        .toolbar {
            background: rgba(255, 255, 255, 0.86);
            border: 1px solid rgba(216, 225, 234, 0.8);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 18px;
            box-shadow: 0 12px 24px rgba(31, 41, 51, 0.08);
            margin-bottom: 18px;
        }

        .toolbar-label {
            font-weight: 800;
            margin-bottom: 10px;
        }

        .search-input {
            border-radius: 14px;
            min-height: 52px;
            border: 1px solid var(--line);
            padding-left: 48px;
            font-size: 1rem;
        }

        .search-wrap {
            position: relative;
        }

        .search-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--muted);
        }

        .toolbar-meta {
            color: var(--muted);
            margin-top: 10px;
            font-weight: 600;
        }

        .gestores-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(330px, 1fr));
            gap: 18px;
        }

        .gestor-card {
            background: var(--surface);
            border-radius: 24px;
            border: 1px solid var(--line);
            box-shadow: var(--shadow);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            transition: transform 0.22s ease, box-shadow 0.22s ease;
        }

        .gestor-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 22px 36px rgba(31, 41, 51, 0.16);
        }

        .card-top {
            padding: 20px;
            background:
                linear-gradient(140deg, rgba(196, 69, 54, 0.1), rgba(255, 255, 255, 0)),
                linear-gradient(180deg, #fff 0%, #fff8f7 100%);
            border-bottom: 1px solid var(--line);
        }

        .identity-row {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .avatar-shell {
            width: 58px;
            height: 58px;
            border-radius: 18px;
            background: linear-gradient(135deg, var(--accent-dark), var(--accent));
            color: #fff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 1.15rem;
            box-shadow: 0 10px 20px rgba(196, 69, 54, 0.26);
        }

        .card-name {
            font-size: 1.2rem;
            font-weight: 800;
            margin: 0;
        }

        .card-company {
            color: var(--muted);
            font-weight: 700;
            margin-top: 2px;
        }

        .plan-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            border-radius: 999px;
            padding: 6px 12px;
            font-weight: 800;
            font-size: 0.82rem;
            margin-top: 12px;
        }

        .plan-basico {
            background: #eef2f6;
            color: #4b5d70;
        }

        .plan-pro {
            background: var(--blue-soft);
            color: #245ea8;
        }

        .plan-premium {
            background: #fff2d8;
            color: #9c6500;
        }

        .card-body {
            padding: 20px;
        }

        .info-list {
            display: grid;
            gap: 12px;
        }

        .info-item {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            color: var(--ink);
        }

        .info-icon {
            width: 20px;
            color: var(--accent);
            margin-top: 3px;
            text-align: center;
        }

        .info-label {
            display: block;
            font-weight: 800;
            margin-bottom: 2px;
        }

        .info-value {
            color: var(--muted);
            word-break: break-word;
        }

        .stats-strip {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 10px;
            margin-top: 18px;
        }

        .stat-chip {
            border-radius: 16px;
            background: #f8fafc;
            border: 1px solid var(--line);
            padding: 10px;
            text-align: center;
        }

        .stat-chip-value {
            display: block;
            font-size: 1.2rem;
            font-weight: 800;
            line-height: 1;
        }

        .stat-chip-label {
            display: block;
            color: var(--muted);
            margin-top: 4px;
            font-size: 0.82rem;
            font-weight: 700;
        }

        .actions-row {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;
            margin-top: 18px;
        }

        .btn-soft {
            border-radius: 14px;
            font-weight: 700;
            padding: 10px 12px;
            border: none;
        }

        .btn-soft-primary {
            background: var(--accent-soft);
            color: var(--accent-dark);
        }

        .btn-soft-dark {
            background: #eef2f6;
            color: #364152;
        }

        .btn-soft-danger {
            background: #fdecec;
            color: #b42318;
        }

        .btn-soft-success {
            background: var(--green-soft);
            color: #146c43;
        }

        .empty-state {
            background: var(--surface);
            border-radius: 24px;
            border: 1px dashed var(--line);
            text-align: center;
            padding: 42px 20px;
            color: var(--muted);
            box-shadow: 0 14px 28px rgba(31, 41, 51, 0.08);
        }

        .footer {
            color: #111827;
            background-color: transparent;
            width: 100%;
            user-select: none;
            font-size: 15px;
        }

        .footer-container {
            background-color: rgba(255, 255, 255, 0.96);
            box-shadow: 0 -8px 24px rgba(31, 41, 51, 0.1);
            backdrop-filter: blur(14px);
            border-top: 1px solid rgba(216, 225, 234, 0.9);
        }

        .footer-item {
            padding: 8px 0;
        }

        .icon-container {
            transition: transform 0.25s ease;
            padding: 5px 0;
        }

        .footer-item:hover .icon-container {
            transform: translateY(-6px);
            color: var(--accent);
        }

        .footer a,
        .footer a:visited,
        .footer a:active {
            color: #111827;
            text-decoration: none;
        }

        .footer-active {
            color: var(--accent) !important;
        }

        .modal-content {
            border: none;
            border-radius: 24px;
            overflow: hidden;
        }

        .modal-header-brand {
            background: linear-gradient(135deg, #962d22, #c44536);
            color: #fff;
        }

        .modal-header-neutral {
            background: #1f2933;
            color: #fff;
        }

        .modal-stat-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
        }

        .modal-stat-card {
            border-radius: 16px;
            border: 1px solid var(--line);
            background: #f8fafc;
            padding: 14px;
        }

        .modal-stat-card strong {
            display: block;
            font-size: 1.35rem;
        }

        @media (max-width: 767px) {
            .page-shell {
                padding-left: 12px;
                padding-right: 12px;
            }

            .hero {
                padding: 20px;
            }

            .hero-title {
                font-size: 1.55rem;
            }

            .stats-strip,
            .actions-row,
            .modal-stat-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }
    </style>
</head>

<body>
    <div class="page-shell">
        <section class="hero">
            <div class="d-flex flex-column flex-lg-row justify-content-between align-items-start gap-3">
                <div>
                    <div class="title-row">
                        <h1 class="hero-title"><i class="fas fa-user-tie me-2"></i>Vista Global de Gestores</h1>
                        <span class="info-hint-btn" data-bs-toggle="tooltip" data-bs-placement="right"
                            title="Lista, filtra y administra las gestiones activas. Cada tarjeta resume la zona asignada por codigo postal y permite editar datos, reasignar cobertura, ver estadisticas y eliminar el perfil.">?</span>
                    </div>
                    <div class="hero-pill"><i class="fas fa-map-marked-alt"></i> Cobertura activa en <?php echo $postalCoverage; ?> codigos postales</div>
                </div>
                <div class="hero-actions">
                    <button type="button" class="btn btn-hero-primary" id="openInviteGestora">
                        <i class="fas fa-paper-plane"></i>Invitar gestora
                    </button>
                </div>
            </div>
        </section>

        <?php if ($flash): ?>
            <div class="alert alert-<?php echo htmlspecialchars($flash['type']); ?> shadow-sm rounded-4 border-0 mb-4">
                <i class="fas fa-circle-info me-2"></i><?php echo htmlspecialchars($flash['message']); ?>
            </div>
        <?php endif; ?>

        <?php if ($errorDb): ?>
            <div class="alert alert-danger shadow-sm rounded-4 border-0 mb-4">
                <i class="fas fa-triangle-exclamation me-2"></i><?php echo htmlspecialchars($errorDb); ?>
            </div>
        <?php endif; ?>

        <section class="summary-grid">
            <article class="summary-card">
                <div class="summary-label">Gestores</div>
                <div class="summary-value"><?php echo $totalGestores; ?></div>
                <div class="summary-meta">Perfiles listados en esta vista</div>
            </article>
            <article class="summary-card">
                <div class="summary-label">Zonas activas</div>
                <div class="summary-value"><?php echo $postalCoverage; ?></div>
                <div class="summary-meta">Codigos postales con gestor asignado</div>
            </article>
            <article class="summary-card">
                <div class="summary-label">Establecimientos</div>
                <div class="summary-value"><?php echo $totalesGlobales['establecimientos']; ?></div>
                <div class="summary-meta">Total asociado a las zonas cargadas</div>
            </article>
            <article class="summary-card">
                <div class="summary-label">Reservas</div>
                <div class="summary-value"><?php echo $totalesGlobales['reservas']; ?></div>
                <div class="summary-meta">Reservas detectadas en esas zonas</div>
            </article>
        </section>

        <section class="toolbar">
            <label for="gestor-search" class="toolbar-label"><i class="fas fa-search me-2"></i>Buscar por nombre, CIF o codigo postal</label>
            <div class="search-wrap">
                <i class="fas fa-magnifying-glass search-icon"></i>
                <input id="gestor-search" type="search" class="form-control search-input" placeholder="Ejemplo: Marta, B12345678 o 28001">
            </div>
            <div class="toolbar-meta"><span id="result-count"><?php echo $totalGestores; ?></span> gestores visibles</div>
        </section>

        <?php if (!$errorDb && empty($gestores)): ?>
            <section class="empty-state">
                <div class="display-6 mb-3"><i class="fas fa-user-slash"></i></div>
                <h2 class="h4 fw-bold">No hay gestores registrados</h2>
                <p class="mb-0">Cuando existan perfiles en la tabla gestor apareceran aqui con sus acciones administrativas.</p>
            </section>
        <?php else: ?>
            <section id="gestores-grid" class="gestores-grid">
                <?php foreach ($gestores as $gestor): ?>
                    <?php
                    $searchBlob = strtolower(trim(implode(' ', [
                        (string) ($gestor['name'] ?? ''),
                        (string) ($gestor['cif'] ?? ''),
                        (string) ($gestor['codigo_postal'] ?? ''),
                        (string) ($gestor['empresa'] ?? ''),
                        (string) ($gestor['email'] ?? ''),
                    ])));
                    ?>
                    <article class="gestor-card" data-search="<?php echo htmlspecialchars($searchBlob); ?>">
                        <div class="card-top">
                            <div class="identity-row">
                                <div class="avatar-shell"><?php echo htmlspecialchars($gestor['initials']); ?></div>
                                <div class="flex-grow-1">
                                    <h2 class="card-name"><?php echo htmlspecialchars($gestor['name'] ?? 'Sin nombre'); ?></h2>
                                    <div class="card-company"><?php echo htmlspecialchars($gestor['empresa'] ?? 'Sin empresa'); ?></div>
                                </div>
                            </div>
                            <div class="plan-badge <?php echo htmlspecialchars($gestor['plan_class']); ?>">
                                <i class="fas fa-layer-group"></i>
                                <?php echo htmlspecialchars($gestor['plan_label']); ?>
                                <span class="opacity-75">· hasta <?php echo htmlspecialchars($gestor['plan_end_label']); ?></span>
                            </div>
                        </div>

                        <div class="card-body">
                            <div class="info-list">
                                <div class="info-item">
                                    <div class="info-icon"><i class="fas fa-envelope"></i></div>
                                    <div>
                                        <span class="info-label">Correo</span>
                                        <div class="info-value"><?php echo htmlspecialchars($gestor['email'] ?? 'Sin correo'); ?></div>
                                    </div>
                                </div>
                                <div class="info-item">
                                    <div class="info-icon"><i class="fas fa-id-card"></i></div>
                                    <div>
                                        <span class="info-label">CIF</span>
                                        <div class="info-value"><?php echo htmlspecialchars($gestor['cif'] ?? 'No indicado'); ?></div>
                                    </div>
                                </div>
                                <div class="info-item">
                                    <div class="info-icon"><i class="fas fa-phone"></i></div>
                                    <div>
                                        <span class="info-label">Telefono</span>
                                        <div class="info-value"><?php echo htmlspecialchars($gestor['phone'] ?? 'No indicado'); ?></div>
                                    </div>
                                </div>
                                <div class="info-item">
                                    <div class="info-icon"><i class="fas fa-map-pin"></i></div>
                                    <div>
                                        <span class="info-label">Codigo postal asignado</span>
                                        <div class="info-value"><?php echo htmlspecialchars($gestor['codigo_postal'] !== '' ? $gestor['codigo_postal'] : 'Sin asignar'); ?></div>
                                    </div>
                                </div>
                            </div>

                            <div class="stats-strip">
                                <div class="stat-chip">
                                    <span class="stat-chip-value"><?php echo (int) $gestor['estadisticas']['establecimientos']; ?></span>
                                    <span class="stat-chip-label">Establec.</span>
                                </div>
                                <div class="stat-chip">
                                    <span class="stat-chip-value"><?php echo (int) $gestor['estadisticas']['espacios']; ?></span>
                                    <span class="stat-chip-label">Espacios</span>
                                </div>
                                <div class="stat-chip">
                                    <span class="stat-chip-value"><?php echo (int) $gestor['estadisticas']['reservas']; ?></span>
                                    <span class="stat-chip-label">Reservas</span>
                                </div>
                                <div class="stat-chip">
                                    <span class="stat-chip-value"><?php echo (int) $gestor['estadisticas']['anfitriones']; ?></span>
                                    <span class="stat-chip-label">Anfitr.</span>
                                </div>
                            </div>

                            <div class="actions-row">
                                <button type="button" class="btn btn-soft btn-soft-primary js-open-edit" data-id="<?php echo htmlspecialchars($gestor['id']); ?>">
                                    <i class="fas fa-pen-to-square me-1"></i>Modificar
                                </button>
                                <button type="button" class="btn btn-soft btn-soft-dark js-open-stats" data-id="<?php echo htmlspecialchars($gestor['id']); ?>">
                                    <i class="fas fa-chart-column me-1"></i>Estadisticas
                                </button>
                                <button type="button" class="btn btn-soft btn-soft-success js-open-cp" data-id="<?php echo htmlspecialchars($gestor['id']); ?>">
                                    <i class="fas fa-location-crosshairs me-1"></i>Reasignar CP
                                </button>
                                <button type="button" class="btn btn-soft btn-soft-danger js-open-delete" data-id="<?php echo htmlspecialchars($gestor['id']); ?>">
                                    <i class="fas fa-trash-can me-1"></i>Eliminar
                                </button>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </section>

            <section id="empty-filter-state" class="empty-state mt-3" style="display:none;">
                <div class="display-6 mb-3"><i class="fas fa-filter-circle-xmark"></i></div>
                <h2 class="h4 fw-bold">No hay coincidencias</h2>
                <p class="mb-0">Ajusta la busqueda para ver gestores por nombre, CIF o codigo postal.</p>
            </section>
        <?php endif; ?>
    </div>

    <div class="modal fade" id="editGestorModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header modal-header-brand">
                    <h5 class="modal-title"><i class="fas fa-user-pen me-2"></i>Modificar gestor</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST">
                    <div class="modal-body p-4">
                        <input type="hidden" name="action" value="editar_gestor">
                        <input type="hidden" name="gestor_id" id="edit_gestor_id">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="edit_nombre" class="form-label fw-bold">Nombre</label>
                                <input type="text" class="form-control" id="edit_nombre" name="nombre" required>
                            </div>
                            <div class="col-md-6">
                                <label for="edit_email" class="form-label fw-bold">Correo electronico</label>
                                <input type="email" class="form-control" id="edit_email" name="email" required>
                            </div>
                            <div class="col-md-6">
                                <label for="edit_telefono" class="form-label fw-bold">Telefono</label>
                                <input type="text" class="form-control" id="edit_telefono" name="telefono">
                            </div>
                            <div class="col-md-6">
                                <label for="edit_empresa" class="form-label fw-bold">Empresa</label>
                                <input type="text" class="form-control" id="edit_empresa" name="empresa">
                            </div>
                            <div class="col-md-6">
                                <label for="edit_cif" class="form-label fw-bold">CIF</label>
                                <input type="text" class="form-control" id="edit_cif" name="cif">
                            </div>
                            <div class="col-md-6">
                                <label for="edit_plan" class="form-label fw-bold">Plan</label>
                                <select class="form-select" id="edit_plan" name="plan">
                                    <option value="">Sin cambios</option>
                                    <option value="Basico">Basico</option>
                                    <option value="Pro">Pro</option>
                                    <option value="Premium">Premium</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label for="edit_direccion" class="form-label fw-bold">Direccion</label>
                                <input type="text" class="form-control" id="edit_direccion" name="direccion">
                            </div>
                            <div class="col-md-6">
                                <label for="edit_localidad" class="form-label fw-bold">Localidad</label>
                                <input type="text" class="form-control" id="edit_localidad" name="localidad">
                            </div>
                            <div class="col-md-6">
                                <label for="edit_provincia" class="form-label fw-bold">Provincia</label>
                                <input type="text" class="form-control" id="edit_provincia" name="provincia">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer px-4 pb-4 border-0">
                        <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-danger rounded-pill px-4">Guardar cambios</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="inviteGestoraModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header modal-header-brand">
                    <h5 class="modal-title"><i class="fas fa-paper-plane me-2"></i>Enviar invitacion de gestora</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST">
                    <div class="modal-body p-4">
                        <input type="hidden" name="action" value="enviar_invitacion_gestora">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="invite_nombre" class="form-label fw-bold">Nombre de contacto</label>
                                <input type="text" class="form-control" id="invite_nombre" name="invite_nombre">
                            </div>
                            <div class="col-md-6">
                                <label for="invite_email" class="form-label fw-bold">Correo electronico</label>
                                <input type="email" class="form-control" id="invite_email" name="invite_email" required>
                            </div>
                            <div class="col-md-6">
                                <label for="invite_empresa" class="form-label fw-bold">Empresa</label>
                                <input type="text" class="form-control" id="invite_empresa" name="invite_empresa">
                            </div>
                            <div class="col-md-6">
                                <label for="invite_cif" class="form-label fw-bold">CIF</label>
                                <input type="text" class="form-control" id="invite_cif" name="invite_cif">
                            </div>
                            <div class="col-md-6">
                                <label for="invite_codigo_postal" class="form-label fw-bold">Codigo postal inicial</label>
                                <input type="text" class="form-control" id="invite_codigo_postal" name="invite_codigo_postal" maxlength="5" pattern="[0-9]{5}" inputmode="numeric" required>
                            </div>
                            <div class="col-md-6">
                                <label for="invite_plan" class="form-label fw-bold">Plan sugerido</label>
                                <select class="form-select" id="invite_plan" name="invite_plan">
                                    <option value="Basico">Basico</option>
                                    <option value="Pro">Pro</option>
                                    <option value="Premium">Premium</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <div class="form-text">Se enviara un enlace firmado para completar el alta y elegir el plan final de suscripcion de la gestora.</div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer px-4 pb-4 border-0">
                        <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-danger rounded-pill px-4">Enviar invitacion</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="statsGestorModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header modal-header-neutral">
                    <h5 class="modal-title"><i class="fas fa-chart-pie me-2"></i>Estadisticas de zona</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <h3 class="h5 fw-bold mb-1" id="stats_nombre"></h3>
                    <p class="text-muted mb-4">Codigo postal gestionado: <strong id="stats_cp"></strong></p>
                    <div class="modal-stat-grid">
                        <div class="modal-stat-card"><span class="text-muted">Establecimientos</span><strong id="stats_establecimientos">0</strong></div>
                        <div class="modal-stat-card"><span class="text-muted">Espacios</span><strong id="stats_espacios">0</strong></div>
                        <div class="modal-stat-card"><span class="text-muted">Reservas</span><strong id="stats_reservas">0</strong></div>
                        <div class="modal-stat-card"><span class="text-muted">Anfitriones unicos</span><strong id="stats_anfitriones">0</strong></div>
                        <div class="modal-stat-card"><span class="text-muted">Validados</span><strong id="stats_aprobados">0</strong></div>
                        <div class="modal-stat-card"><span class="text-muted">Pendientes</span><strong id="stats_pendientes">0</strong></div>
                        <div class="modal-stat-card"><span class="text-muted">Rechazados</span><strong id="stats_rechazados">0</strong></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="cpGestorModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header modal-header-brand">
                    <h5 class="modal-title"><i class="fas fa-route me-2"></i>Reasignar codigo postal</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST">
                    <div class="modal-body p-4">
                        <input type="hidden" name="action" value="reasignar_cp">
                        <input type="hidden" name="gestor_id" id="cp_gestor_id">
                        <p class="mb-3">Vas a cambiar la zona de gestion de <strong id="cp_nombre"></strong>.</p>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Codigo postal actual</label>
                            <input type="text" class="form-control" id="cp_actual" readonly>
                        </div>
                        <div class="mb-0">
                            <label for="cp_nuevo" class="form-label fw-bold">Nuevo codigo postal</label>
                            <input type="text" class="form-control" id="cp_nuevo" name="codigo_postal" maxlength="5" pattern="[0-9]{5}" inputmode="numeric" required>
                            <div class="form-text">El gestor heredara las estadisticas y la operativa de la nueva zona.</div>
                        </div>
                    </div>
                    <div class="modal-footer px-4 pb-4 border-0">
                        <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-danger rounded-pill px-4">Guardar reasignacion</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="deleteGestorModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header modal-header-neutral">
                    <h5 class="modal-title"><i class="fas fa-trash-can me-2"></i>Eliminar gestor</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST">
                    <div class="modal-body p-4">
                        <input type="hidden" name="action" value="eliminar_gestor">
                        <input type="hidden" name="gestor_id" id="delete_gestor_id">
                        <p class="mb-2">Se eliminara el perfil de <strong id="delete_nombre"></strong>.</p>
                        <p class="text-muted mb-0">Esta accion quitara el acceso del gestor al sistema. Las entidades de negocio asociadas por codigo postal no se borran automaticamente.</p>
                    </div>
                    <div class="modal-footer px-4 pb-4 border-0">
                        <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-danger rounded-pill px-4">Eliminar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php include 'footerAdmin.php'; ?>

    <script>
        const gestoresData = <?php echo json_encode($gestoresForJs, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;

        document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach((element) => {
            new bootstrap.Tooltip(element);
        });

        const searchInput = document.getElementById('gestor-search');
        const cards = Array.from(document.querySelectorAll('.gestor-card'));
        const resultCount = document.getElementById('result-count');
        const emptyFilterState = document.getElementById('empty-filter-state');

        function applyFilter() {
            const query = (searchInput?.value || '').trim().toLowerCase();
            let visible = 0;

            cards.forEach((card) => {
                const haystack = card.dataset.search || '';
                const matches = query === '' || haystack.includes(query);
                card.style.display = matches ? '' : 'none';
                if (matches) {
                    visible += 1;
                }
            });

            if (resultCount) {
                resultCount.textContent = String(visible);
            }

            if (emptyFilterState) {
                emptyFilterState.style.display = visible === 0 && cards.length > 0 ? '' : 'none';
            }
        }

        searchInput?.addEventListener('input', applyFilter);
        applyFilter();

        const editModal = new bootstrap.Modal(document.getElementById('editGestorModal'));
        const inviteModal = new bootstrap.Modal(document.getElementById('inviteGestoraModal'));
        const statsModal = new bootstrap.Modal(document.getElementById('statsGestorModal'));
        const cpModal = new bootstrap.Modal(document.getElementById('cpGestorModal'));
        const deleteModal = new bootstrap.Modal(document.getElementById('deleteGestorModal'));

        document.getElementById('openInviteGestora')?.addEventListener('click', () => {
            inviteModal.show();
        });

        function getGestor(id) {
            return gestoresData[id] || null;
        }

        document.querySelectorAll('.js-open-edit').forEach((button) => {
            button.addEventListener('click', () => {
                const gestor = getGestor(button.dataset.id);
                if (!gestor) {
                    return;
                }

                document.getElementById('edit_gestor_id').value = gestor.id || '';
                document.getElementById('edit_nombre').value = gestor.name || '';
                document.getElementById('edit_email').value = gestor.email || '';
                document.getElementById('edit_telefono').value = gestor.phone || '';
                document.getElementById('edit_empresa').value = gestor.empresa || '';
                document.getElementById('edit_cif').value = gestor.cif || '';
                document.getElementById('edit_direccion').value = gestor.direccion || '';
                document.getElementById('edit_localidad').value = gestor.localidad || '';
                document.getElementById('edit_provincia').value = gestor.provincia || '';
                document.getElementById('edit_plan').value = gestor.plan || '';
                editModal.show();
            });
        });

        document.querySelectorAll('.js-open-stats').forEach((button) => {
            button.addEventListener('click', () => {
                const gestor = getGestor(button.dataset.id);
                if (!gestor) {
                    return;
                }

                const stats = gestor.estadisticas || {};
                document.getElementById('stats_nombre').textContent = gestor.name || 'Gestor';
                document.getElementById('stats_cp').textContent = gestor.codigo_postal || 'Sin asignar';
                document.getElementById('stats_establecimientos').textContent = stats.establecimientos || 0;
                document.getElementById('stats_espacios').textContent = stats.espacios || 0;
                document.getElementById('stats_reservas').textContent = stats.reservas || 0;
                document.getElementById('stats_anfitriones').textContent = stats.anfitriones || 0;
                document.getElementById('stats_aprobados').textContent = stats.aprobados || 0;
                document.getElementById('stats_pendientes').textContent = stats.pendientes || 0;
                document.getElementById('stats_rechazados').textContent = stats.rechazados || 0;
                statsModal.show();
            });
        });

        document.querySelectorAll('.js-open-cp').forEach((button) => {
            button.addEventListener('click', () => {
                const gestor = getGestor(button.dataset.id);
                if (!gestor) {
                    return;
                }

                document.getElementById('cp_gestor_id').value = gestor.id || '';
                document.getElementById('cp_nombre').textContent = gestor.name || 'este gestor';
                document.getElementById('cp_actual').value = gestor.codigo_postal || 'Sin asignar';
                document.getElementById('cp_nuevo').value = gestor.codigo_postal || '';
                cpModal.show();
            });
        });

        document.querySelectorAll('.js-open-delete').forEach((button) => {
            button.addEventListener('click', () => {
                const gestor = getGestor(button.dataset.id);
                if (!gestor) {
                    return;
                }

                document.getElementById('delete_gestor_id').value = gestor.id || '';
                document.getElementById('delete_nombre').textContent = gestor.name || 'este gestor';
                deleteModal.show();
            });
        });
    </script>
</body>

</html>