<?php
session_start();

require '../vendor/autoload.php';
require_once __DIR__ . '/invitacionGestoraToken.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

$inviteToken = trim((string) ($_GET['token'] ?? $_POST['token'] ?? ''));
$inviteData = null;
$inviteError = '';
$formError = '';
$selectedPlan = 'Basico';

function sanitizeField($key)
{
    return trim((string) ($_POST[$key] ?? ''));
}

function normalizePlanSelection($value)
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

function authRequest($path, array $payload)
{
    $url = 'http://' . $_ENV['SERVER_IP'] . ':' . $_ENV['DATABASE_PORT'] . $path;
    $ch = curl_init($url);

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'apikey: ' . $_ENV['DATABASE_APIKEY'],
            'Prefer: return=minimal',
        ],
    ]);

    $body = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    return [
        'status' => $status,
        'data' => json_decode((string) $body, true),
        'error' => $error,
    ];
}

function authAdminRequest($path, array $payload, $method = 'POST')
{
    $url = 'http://' . $_ENV['SERVER_IP'] . ':' . $_ENV['DATABASE_PORT'] . $path;
    $ch = curl_init($url);

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $_ENV['SERVICE_APIKEY'],
            'apikey: ' . $_ENV['SERVICE_APIKEY'],
        ],
    ]);

    $body = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    return [
        'status' => $status,
        'data' => json_decode((string) $body, true),
        'error' => $error,
    ];
}

function apiRequestRegisterGestora($path, $method = 'GET', $payload = null)
{
    $url = 'http://' . $_ENV['SERVER_IP'] . ':' . $_ENV['DATABASE_PORT'] . '/rest/v1' . $path;
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
        $headers[] = 'Prefer: resolution=merge-duplicates,return=representation';
        $options[CURLOPT_POSTFIELDS] = json_encode($payload);
        $options[CURLOPT_HTTPHEADER] = $headers;
    }

    curl_setopt_array($ch, $options);
    $body = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    return [
        'status' => $status,
        'data' => json_decode((string) $body, true),
        'error' => $error,
    ];
}

// OBTENER PRECIOS REALES DE LA BASE DE DATOS
$urlPlanes = 'http://' . $_ENV['SERVER_IP'] . ':' . $_ENV['DATABASE_PORT'] . '/rest/v1/planes_suscripcion?tipo_usuario=eq.gestor&select=*';
$chPlanes = curl_init($urlPlanes);
curl_setopt_array($chPlanes, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => ['apikey: ' . $_ENV['DATABASE_APIKEY']]
]);
$resPlanes = curl_exec($chPlanes);
curl_close($chPlanes);
$planesDb = json_decode($resPlanes, true);

// Precios por defecto en caso de fallo
$precios = [
    'Basico' => ['mensual' => 700, 'anual' => 7700],
    'Pro' => ['mensual' => 1900, 'anual' => 20900],
    'Premium' => ['mensual' => 2850, 'anual' => 31350]
];

if (is_array($planesDb)) {
    foreach ($planesDb as $p) {
        $nombrePlan = $p['nombre'];
        if (isset($precios[$nombrePlan])) {
            $precios[$nombrePlan]['mensual'] = floatval($p['precio_mensual']);
            $precios[$nombrePlan]['anual'] = floatval($p['precio_anual']);
        }
    }
}

if ($inviteToken === '') {
    $inviteError = 'El enlace de registro no es valido o esta incompleto.';
} else {
    try {
        $inviteData = decodeGestoraInvitationToken($inviteToken);
        $selectedPlan = normalizePlanSelection($inviteData['plan'] ?? 'Basico');
    } catch (Throwable $throwable) {
        $inviteError = 'La invitacion ha expirado o no se pudo validar.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $inviteError === '' && is_array($inviteData)) {
    $selectedPlan = normalizePlanSelection($_POST['plan'] ?? 'Basico');
    $nombre = sanitizeField('nombre');
    $telefono = sanitizeField('telefono');
    $direccion = sanitizeField('direccion');
    $localidad = sanitizeField('localidad');
    $provincia = sanitizeField('provincia');
    $razon_social = sanitizeField('razon_social');
    $cif = sanitizeField('cif');
    $codigo_postal = sanitizeField('codigo_postal');
    $password = (string) ($_POST['password'] ?? '');
    $passwordConfirm = (string) ($_POST['password_confirm'] ?? '');

    if ($nombre === '' || $razon_social === '' || $direccion === '' || $localidad === '' || $provincia === '' || $cif === '' || $codigo_postal === '') {
        $formError = 'Todos los campos de dirección, CIF y Código Postal son obligatorios.';
    } elseif (!preg_match('/^[0-9]{5}$/', $codigo_postal)) {
        $formError = 'El código postal debe contener exactamente 5 dígitos.';
    } elseif ($password === '' || strlen($password) < 8) {
        $formError = 'La contraseña debe tener al menos 8 caracteres.';
    } elseif ($password !== $passwordConfirm) {
        $formError = 'Las contraseñas no coinciden.';
    } else {
        $email = (string) ($inviteData['email'] ?? '');
        $signupPayload = [
            'email' => $email,
            'password' => $password,
            'email_confirm' => true,
            'user_metadata' => [
                'rol' => 'gestora',
            ],
        ];

        $signupResponse = authAdminRequest('/auth/v1/admin/users', $signupPayload);
        $userId = '';
        $accessToken = '';

        if ($signupResponse['status'] >= 200 && $signupResponse['status'] < 300 && isset($signupResponse['data']['id'])) {
            $userId = (string) $signupResponse['data']['id'];
        } elseif ($signupResponse['status'] === 422) {
            $formError = 'Ya existe una cuenta de acceso para este correo. Contacta con soporte si no puedes iniciar sesion.';
        } else {
            $formError = 'No se pudo crear el acceso de la gestora en autenticacion.';
        }

        if ($formError === '') {
            $loginResponse = authRequest('/auth/v1/token?grant_type=password', [
                'email' => $email,
                'password' => $password,
            ]);

            if (isset($loginResponse['data']['access_token'])) {
                $accessToken = (string) $loginResponse['data']['access_token'];
            } else {
                $formError = 'La cuenta se creo, pero no se pudo iniciar sesion automaticamente.';
            }
        }

        if ($formError === '') {
            $gestorPayload = [
                'id' => $userId,
                'email' => $email,
                'name' => $nombre,
                'phone' => $telefono,
                'empresa' => (string) ($inviteData['empresa'] ?? ''),
                'cif' => $cif,
                'codigo_postal' => $codigo_postal,
                'direccion' => $direccion,
                'localidad' => $localidad,
                'provincia' => $provincia,
                'plan' => $selectedPlan, // Guardamos el plan seleccionado correctamente
                'razon_social' => $razon_social,
            ];

            $gestorResponse = apiRequestRegisterGestora('/gestor', 'POST', $gestorPayload);

            if ($gestorResponse['status'] < 200 || $gestorResponse['status'] >= 300) {
                $formError = 'La cuenta se creo, pero no se pudo guardar el perfil de la gestora en base de datos.';
            } else {
                $_SESSION['token'] = $accessToken;
                $_SESSION['user_id'] = $userId;
                $_SESSION['email'] = $email;
                $_SESSION['auth_from_registration'] = true;

                if ($selectedPlan === 'Pro') {
                    header('Location: mejoraProGestor.php');
                    exit();
                }

                if ($selectedPlan === 'Premium') {
                    header('Location: mejoraPremiumGestor.php');
                    exit();
                }

                header('Location: tuPerfil.php?registro=ok');
                exit();
            }
        }
    }
}
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
    <title>Registro de Gestora</title>
    <style>
        :root {
            --ink: #1f2933;
            --muted: #66788a;
            --line: #d8e1ea;
            --surface: #ffffff;
            --brand: #0f4c5c;
            --brand-2: #1f7a8c;
            --accent: #ffb703;
            --bg: #f4f8fb;
            --shadow: 0 20px 38px rgba(15, 76, 92, 0.14);
        }

        body {
            font-family: 'Nunito', sans-serif;
            background:
                radial-gradient(circle at 8% 0%, rgba(15, 76, 92, 0.12), transparent 30%),
                radial-gradient(circle at 92% 8%, rgba(255, 183, 3, 0.18), transparent 24%),
                linear-gradient(180deg, #f9fcfe 0%, var(--bg) 100%);
            color: var(--ink);
            min-height: 100vh;
            padding: 24px 0 40px;
        }

        .page-shell {
            max-width: 1180px;
            margin: 0 auto;
            padding: 0 16px;
        }

        .hero {
            background: linear-gradient(135deg, #0f4c5c 0%, #146879 58%, #4d98ab 100%);
            border-radius: 28px;
            color: #fff;
            padding: 28px;
            box-shadow: 0 20px 48px rgba(15, 76, 92, 0.26);
            margin-bottom: 18px;
        }

        .hero h1 {
            font-size: 2rem;
            font-weight: 800;
            margin-bottom: 10px;
        }

        .hero p {
            max-width: 780px;
            margin: 0;
            font-size: 1.05rem;
            opacity: 0.94;
        }

        .hero-meta {
            display: inline-flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 16px;
        }

        .hero-chip {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 14px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.14);
            font-weight: 700;
        }

        .content-grid {
            display: grid;
            grid-template-columns: minmax(0, 1.15fr) minmax(320px, 0.85fr);
            gap: 18px;
        }

        .panel {
            background: rgba(255, 255, 255, 0.92);
            border: 1px solid rgba(216, 225, 234, 0.85);
            border-radius: 24px;
            box-shadow: var(--shadow);
            overflow: hidden;
        }

        .panel-head {
            padding: 22px 24px 12px;
        }

        .panel-title {
            font-size: 1.2rem;
            font-weight: 800;
            margin: 0;
        }

        .panel-subtitle {
            color: var(--muted);
            margin-top: 6px;
        }

        .panel-body {
            padding: 0 24px 24px;
        }

        .summary-list {
            display: grid;
            gap: 12px;
        }

        .summary-item {
            background: #f7fbfd;
            border: 1px solid var(--line);
            border-radius: 18px;
            padding: 14px 16px;
        }

        .summary-label {
            color: var(--muted);
            font-size: 0.88rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            font-weight: 800;
        }

        .summary-value {
            margin-top: 4px;
            font-size: 1.02rem;
            font-weight: 700;
        }

        .form-control,
        .form-select {
            border-radius: 14px;
            min-height: 50px;
            border-color: var(--line);
        }

        .form-control:focus,
        .form-select:focus {
            border-color: rgba(15, 76, 92, 0.45);
            box-shadow: 0 0 0 0.2rem rgba(15, 76, 92, 0.12);
        }

        .plans-grid {
            display: grid;
            gap: 14px;
        }

        .plan-card {
            border: 2px solid #dbe5ed;
            border-radius: 22px;
            padding: 18px;
            background: #fff;
            cursor: pointer;
            transition: transform 0.22s ease, box-shadow 0.22s ease, border-color 0.22s ease;
        }

        .plan-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 16px 28px rgba(15, 76, 92, 0.12);
        }

        .plan-card.selected {
            border-color: var(--brand);
            box-shadow: 0 18px 30px rgba(15, 76, 92, 0.16);
        }

        .plan-card.popular {
            position: relative;
        }

        .plan-card.popular::before {
            content: 'Recomendado';
            position: absolute;
            top: -12px;
            right: 18px;
            background: linear-gradient(135deg, var(--accent) 0%, #ffcf5c 100%);
            color: #7a4a00;
            border-radius: 999px;
            padding: 5px 12px;
            font-size: 0.78rem;
            font-weight: 800;
        }

        .plan-name {
            font-size: 1.15rem;
            font-weight: 800;
        }

        .plan-price {
            font-size: 2rem;
            font-weight: 800;
            color: var(--brand);
            line-height: 1;
            margin-top: 6px;
        }

        .plan-note {
            color: var(--muted);
            margin-top: 6px;
            font-weight: 600;
        }

        .plan-features {
            list-style: none;
            margin: 14px 0 0;
            padding: 0;
            display: grid;
            gap: 8px;
        }

        .plan-features li {
            display: flex;
            gap: 8px;
            align-items: flex-start;
        }

        .plan-features i {
            color: var(--brand-2);
            margin-top: 3px;
        }

        .submit-row {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 24px;
        }

        .btn-primary-soft {
            background: linear-gradient(135deg, #0f4c5c 0%, #1f7a8c 100%);
            color: #fff;
            border: 0;
            border-radius: 999px;
            padding: 12px 20px;
            font-weight: 800;
        }

        .btn-primary-soft:hover {
            color: #fff;
            transform: translateY(-1px);
        }

        @media (max-width: 991px) {
            .content-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <div class="page-shell">
        <section class="hero">
            <h1><i class="fas fa-user-tie me-2"></i>Completa el alta de tu gestora</h1>
            <p>Configura tus datos de acceso, revisa la zona inicial asignada y elige el plan con el que quieres
                arrancar la operativa.</p>
            <?php if (is_array($inviteData) && $inviteError === ''): ?>
                <div class="hero-meta">
                    <span class="hero-chip"><i class="fas fa-envelope"></i><?php echo htmlspecialchars((string) ($inviteData['email'] ?? '')); ?></span>
                </div>
            <?php endif; ?>
        </section>

        <?php if ($inviteError !== ''): ?>
            <div class="alert alert-danger rounded-4 border-0 shadow-sm mb-3">
                <i class="fas fa-circle-exclamation me-2"></i><?php echo htmlspecialchars($inviteError); ?>
            </div>
        <?php endif; ?>

        <?php if ($formError !== ''): ?>
            <div class="alert alert-danger rounded-4 border-0 shadow-sm mb-3">
                <i class="fas fa-circle-exclamation me-2"></i><?php echo htmlspecialchars($formError); ?>
            </div>
        <?php endif; ?>

        <?php if ($inviteError === '' && is_array($inviteData)): ?>
            <form method="POST">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($inviteToken); ?>">
                <input type="hidden" name="plan" id="planInput" value="<?php echo htmlspecialchars($selectedPlan); ?>">

                <div class="content-grid">
                    <section class="panel">
                        <div class="panel-head">
                            <h2 class="panel-title">Datos del perfil</h2>
                            <p class="panel-subtitle">Estos datos completan el alta y se guardan en el perfil de la gestora.
                            </p>
                        </div>
                        <div class="panel-body">
                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label for="nombre" class="form-label fw-bold">Nombre completo *</label>
                                    <input type="text" class="form-control" id="nombre" name="nombre"
                                        value="<?php echo htmlspecialchars((string) ($_POST['nombre'] ?? $inviteData['nombre'] ?? '')); ?>"
                                        required>
                                </div>
                                <div class="col-md-6">
                                    <label for="telefono" class="form-label fw-bold">Teléfono *</label>
                                    <input type="text" class="form-control" id="telefono" name="telefono"
                                        value="<?php echo htmlspecialchars((string) ($_POST['telefono'] ?? $inviteData['telefono'] ?? '')); ?>" required>
                                </div>

                                <div class="col-12">
                                    <label for="razon_social" class="form-label fw-bold">Razón Social *</label>
                                    <input type="text" class="form-control" id="razon_social" name="razon_social"
                                        value="<?php echo htmlspecialchars((string) ($_POST['razon_social'] ?? '')); ?>"
                                        required>
                                </div>

                                <div class="col-md-6">
                                    <label for="password" class="form-label fw-bold">Contraseña *</label>
                                    <input type="password" class="form-control" id="password" name="password" minlength="8"
                                        required>
                                </div>
                                <div class="col-md-6">
                                    <label for="password_confirm" class="form-label fw-bold">Repite la contraseña *</label>
                                    <input type="password" class="form-control" id="password_confirm"
                                        name="password_confirm" minlength="8" required>
                                </div>
                                <div class="col-12">
                                    <label for="direccion" class="form-label fw-bold">Dirección *</label>
                                    <input type="text" class="form-control" id="direccion" name="direccion"
                                        value="<?php echo htmlspecialchars((string) ($_POST['direccion'] ?? '')); ?>"
                                        required>
                                </div>
                                <div class="col-md-6">
                                    <label for="localidad" class="form-label fw-bold">Localidad *</label>
                                    <input type="text" class="form-control" id="localidad" name="localidad"
                                        value="<?php echo htmlspecialchars((string) ($_POST['localidad'] ?? '')); ?>"
                                        required>
                                </div>
                                <div class="col-md-6">
                                    <label for="provincia" class="form-label fw-bold">Provincia *</label>
                                    <input type="text" class="form-control" id="provincia" name="provincia"
                                        value="<?php echo htmlspecialchars((string) ($_POST['provincia'] ?? '')); ?>"
                                        required>
                                </div>
                                <div class="col-md-6">
                                    <label for="cif" class="form-label fw-bold">CIF *</label>
                                    <input type="text" class="form-control" id="cif" name="cif"
                                        value="<?php echo htmlspecialchars((string) ($_POST['cif'] ?? '')); ?>"
                                        required>
                                </div>
                                <div class="col-md-6">
                                    <label for="codigo_postal" class="form-label fw-bold">Código Postal de tu zona *</label>
                                    <input type="text" class="form-control" id="codigo_postal" name="codigo_postal" maxlength="5"
                                        value="<?php echo htmlspecialchars((string) ($_POST['codigo_postal'] ?? '')); ?>"
                                        required>
                                </div>
                            </div>

                            <div class="summary-list">
                                <div class="summary-item">
                                    <div class="summary-label">Correo de acceso</div>
                                    <div class="summary-value">
                                        <?php echo htmlspecialchars((string) ($inviteData['email'] ?? '')); ?>
                                    </div>
                                </div>
                                <div class="summary-item">
                                    <div class="summary-label">Empresa</div>
                                    <div class="summary-value">
                                        <?php echo htmlspecialchars((string) ($inviteData['empresa'] ?? 'Sin empresa indicada')); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>

                    <aside class="panel">
                        <div class="panel-head">
                            <h2 class="panel-title">Última pantalla: planes de suscripción</h2>
                            <p class="panel-subtitle">Selecciona el plan con el que quieres arrancar. Si eliges Pro o
                                Premium, continuarás con la pasarela de pago al terminar el alta.</p>
                        </div>
                        <div class="panel-body">
                            <div class="plans-grid">
                                <article class="plan-card <?php echo $selectedPlan === 'Basico' ? 'selected' : ''; ?>"
                                    data-plan="Basico">
                                    <div class="plan-name">Básico</div>
                                    <div class="plan-price">EUR <?php echo number_format($precios['Basico']['mensual'], 0, ',', '.'); ?></div>
                                    <div class="plan-note">Mensual. Anual desde EUR <?php echo number_format($precios['Basico']['anual'], 0, ',', '.'); ?></div>
                                    <ul class="plan-features">
                                        <li><i class="fas fa-check"></i><span>Gestión de hasta 5 establecimientos.</span></li>
                                        <li><i class="fas fa-check"></i><span>Zona y cartera inicial de trabajo</span></li>
                                        <li><i class="fas fa-check"></i><span>Ideal para empezar la operativa</span></li>
                                    </ul>
                                </article>

                                <article class="plan-card popular <?php echo $selectedPlan === 'Pro' ? 'selected' : ''; ?>"
                                    data-plan="Pro">
                                    <div class="plan-name">Pro</div>
                                    <div class="plan-price">EUR <?php echo number_format($precios['Pro']['mensual'], 0, ',', '.'); ?></div>
                                    <div class="plan-note">Mensual. Anual desde EUR <?php echo number_format($precios['Pro']['anual'], 0, ',', '.'); ?></div>
                                    <ul class="plan-features">
                                        <li><i class="fas fa-check"></i><span>Gestión de hasta 20 establecimientos.</span></li>
                                        <li><i class="fas fa-check"></i><span>Escalado pensado para gestoras medianas</span>
                                        </li>
                                        <li><i class="fas fa-check"></i><span>Tras el alta continuaras con el resumen de pago</span></li>
                                    </ul>
                                </article>

                                <article class="plan-card <?php echo $selectedPlan === 'Premium' ? 'selected' : ''; ?>"
                                    data-plan="Premium">
                                    <div class="plan-name">Premium</div>
                                    <div class="plan-price">EUR <?php echo number_format($precios['Premium']['mensual'], 0, ',', '.'); ?></div>
                                    <div class="plan-note">Mensual. Anual desde EUR <?php echo number_format($precios['Premium']['anual'], 0, ',', '.'); ?></div>
                                    <ul class="plan-features">
                                        <li><i class="fas fa-check"></i><span>Ilimitado, marca blanca y soporte dedicado.</span></li>
                                        <li><i class="fas fa-check"></i><span>Pensado para despliegues de gran volumen</span></li>
                                        <li><i class="fas fa-check"></i><span>Continua despues del alta con la confirmacion del pago</span></li>
                                    </ul>
                                </article>
                            </div>

                            <div class="submit-row">
                                <button type="submit" class="btn btn-primary-soft">
                                    <i class="fas fa-check-circle me-2"></i>Terminar registro
                                </button>
                            </div>
                        </div>
                    </aside>
                </div>
            </form>
        <?php endif; ?>
    </div>

    <script>
        const planInput = document.getElementById('planInput');
        const planCards = Array.from(document.querySelectorAll('.plan-card'));

        planCards.forEach((card) => {
            card.addEventListener('click', () => {
                const plan = card.dataset.plan || 'Basico';
                if (planInput) {
                    planInput.value = plan;
                }

                planCards.forEach((item) => item.classList.remove('selected'));
                card.classList.add('selected');
            });
        });
    </script>
</body>

</html>