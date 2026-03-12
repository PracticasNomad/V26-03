<?php
session_start();
if (!isset($_SESSION['host'])) {
    header("Location: registerAnfitrion-paso1.php");
    exit;
}
$formError = '';
$formSuccess = '';

require '../vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

function generateUuidV4()
{
    $data = random_bytes(16);

    $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);

    $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);

    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

function insertarHorariosYServicios($uuid_espacio, $uuid_establecimiento, $token, $supabaseKey)
{
    $horarios = isset($_SESSION['espacio_trabajo']['horarios']) ? $_SESSION['espacio_trabajo']['horarios'] : [];
    foreach ($horarios as $index => $horario) {
        $has_monday = in_array('L', $horario['dias']);
        $has_tuesday = in_array('M', $horario['dias']);
        $has_wednesday = in_array('X', $horario['dias']);
        $has_thursday = in_array('J', $horario['dias']);
        $has_friday = in_array('V', $horario['dias']);
        $has_saturday = in_array('S', $horario['dias']);
        $has_sunday = in_array('D', $horario['dias']);

        $start_time = $horario['hora_inicio'];
        $end_time = $horario['hora_fin'];
        $price = $horario['precio_hora'];

        $services = $horario['servicios'];

        $uuid_schedule = generateUuidV4();

        $url = 'http://' . $_ENV['SERVER_IP'] . ':' . $_ENV['DATABASE_PORT'] . '/rest/v1/schedule';
        $ch = curl_init($url);
        $data = [
            'id' => $uuid_schedule,
            'has_monday' => $has_monday,
            'has_tuesday' => $has_tuesday,
            'has_wednesday' => $has_wednesday,
            'has_thursday' => $has_thursday,
            'has_friday' => $has_friday,
            'has_saturday' => $has_saturday,
            'has_sunday' => $has_sunday,
            'start_time' => $start_time,
            'end_time' => $end_time,
            'price' => $price,
            'space_id' => $uuid_espacio,
        ];

        $payload = json_encode($data);

        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'apikey: ' . $supabaseKey
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        $result = curl_exec($ch);
        $responseData = json_decode($result, true);
        foreach ($services as $servicio) {
            $name = $servicio['nombre'];
            $description = $servicio['descripcion'];
            $price = $servicio['precio'];

            $url = 'http://' . $_ENV['SERVER_IP'] . ':' . $_ENV['DATABASE_PORT'] . '/rest/v1/services';
            $ch = curl_init($url);
            $data = [
                'schedule_id' => $uuid_schedule,
                'name' => $name,
                'description' => $description,
                'price' => $price
            ];

            $payload = json_encode($data);

            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'apikey: ' . $supabaseKey
            ]);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
            $result = curl_exec($ch);
        }
    }
}

function insertarDatos()
{
    $supabaseKey = $_ENV['DATABASE_APIKEY'];
    $user_id = null;
    $token = null;

    // 1. Controlar si el usuario ya existe (es un nómada) o es completamente nuevo
    if (isset($_SESSION['already_guest']) && $_SESSION['already_guest'] == true) {
        // Ya es usuario, NO necesitamos buscar su contraseña ni crearlo en Auth de nuevo
        $user_id = $_SESSION['id_user'];
    } else {
        // Es un usuario nuevo, lo registramos en Auth
        $url = 'http://' . $_ENV['SERVER_IP'] . ':' . $_ENV['DATABASE_PORT'] . '/auth/v1/signup';

        // Aquí es seguro pedir la contraseña, porque si es nuevo, tuvo que rellenarla
        $data = [
            'email' => $_SESSION['host']['email'],
            'password' => $_SESSION['host']['password']
        ];

        $payload = json_encode($data);
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'apikey: ' . $supabaseKey
        ]);

        $result = curl_exec($ch);
        $responseData = json_decode($result, true);
        curl_close($ch);

        // Comprobar si se creó bien o ya existía
        if (isset($responseData['user']['id']) && isset($responseData['access_token'])) {
            $user_id = $responseData['user']['id'];
            $token = $responseData['access_token'];
        } else {
            // Si el signup falla (ej. correo ya registrado), intentamos autenticarlo
            $url = 'http://' . $_ENV['SERVER_IP'] . ':' . $_ENV['DATABASE_PORT'] . '/auth/v1/token?grant_type=password';
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'apikey: ' . $supabaseKey,
                'Prefer: return=minimal'
            ]);
            $result = curl_exec($ch);
            $resultDecoded = json_decode($result, true);
            curl_close($ch);

            if (isset($resultDecoded['user']['id'])) {
                $user_id = $resultDecoded['user']['id'];
                $token = $resultDecoded['access_token'];
            }
        }
    }

    // Comprobación de seguridad VITAL
    if (empty($user_id)) {
        echo '<script>console.error("Error crítico: No se pudo obtener el ID del usuario. Cancelando inserción.");</script>';
        return; // Detenemos la función aquí para no crear registros vacíos
    }

   // --- INICIAMOS SESIÓN AUTOMÁTICAMENTE ---
    $_SESSION['user_id'] = $user_id;

    // 1. Guardamos el token solo si existe (para no borrar el de nómadas)
    if (!empty($token)) {
        $_SESSION['token'] = $token;
    }

    // 2. Damos el pase VIP (Asegúrate de que esté FUERA del if)
    $_SESSION['auth_from_registration'] = true;

    // 3. RECUPERAMOS EL PLAN ELEGIDO (Corregimos el error del plan Básico)
    $planElegido = isset($_SESSION['plan_seleccionado']) ? $_SESSION['plan_seleccionado'] : 'Basico';

    // --- insertar anfitrion ---
    $url = 'http://' . $_ENV['SERVER_IP'] . ':' . $_ENV['DATABASE_PORT'] . '/rest/v1/host';
    $ch = curl_init($url);
    $data = [
        'id' => $user_id,
        'email' => $_SESSION['host']['email'],
        'nif' => $_SESSION['host']['nif'],
        'name' => $_SESSION['host']['nombre'],
        'phone' => $_SESSION['host']['telefono'],
        'empresa' => $_SESSION['host']['razonsocial'],
        'plan' => $planElegido // <--- Ahora sí guardará 'Pro' o 'Premium'
    ];

    $payload = json_encode($data);

    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'apikey: ' . $supabaseKey,
        'Prefer: resolution=merge-duplicates' // Evita el error si ya existe (UPSERT)
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode >= 300 && $httpCode != 409) {
        echo '<script>console.error("Error al guardar anfitrión: ' . addslashes($result) . '");</script>';
    }

    // --- insertar establecimiento ---
    // Ya no usamos el ELSE, siempre intentamos insertarlo
    $uuid_establecimiento = generateUuidV4();
    $url = 'http://' . $_ENV['SERVER_IP'] . ':' . $_ENV['DATABASE_PORT'] . '/rest/v1/establecimiento';
    $ch = curl_init($url);

    $parking_price = (isset($_SESSION['establecimiento']['precio_parking']) && $_SESSION['establecimiento']['precio_parking'] !== '') ? floatval($_SESSION['establecimiento']['precio_parking']) : 0;
    $wifi_price = (isset($_SESSION['establecimiento']['precio_wifi']) && $_SESSION['establecimiento']['precio_wifi'] !== '') ? floatval($_SESSION['establecimiento']['precio_wifi']) : 0;

    // Fallback de coordenadas por si hubo un fallo en el Paso 3
    $lat = isset($_SESSION['establecimiento']['latitud']) ? floatval($_SESSION['establecimiento']['latitud']) : 0;
    $lng = isset($_SESSION['establecimiento']['longitud']) ? floatval($_SESSION['establecimiento']['longitud']) : 0;

    $data = [
        'id' => $uuid_establecimiento,
        'host_id' => $user_id,
        'nombre' => $_SESSION['establecimiento']['nombre'],
        'descripcion' => $_SESSION['establecimiento']['descripcion'],
        'has_parking' => $_SESSION['establecimiento']['has_parking'] == 1,
        'parking_price' => $parking_price,
        'has_wifi' => $_SESSION['establecimiento']['has_wifi'] == 1,
        'wifi_price' => $wifi_price,
        'direccion' => $_SESSION['establecimiento']['calle'] . ", " . $_SESSION['establecimiento']['numero'],
        'localidad' => $_SESSION['establecimiento']['localidad'],
        'provincia' => $_SESSION['establecimiento']['provincia'],
        'piso' => $_SESSION['establecimiento']['piso'],
        'codigo_postal' => $_SESSION['establecimiento']['codigo_postal'],
        'latitude' => $lat,
        'longitude' => $lng,
    ];

    $payload = json_encode($data);

    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'apikey: ' . $supabaseKey
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    $result = curl_exec($ch);
    $httpCodeEst = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCodeEst >= 300) {
        echo '<script>console.error("Error Establecimiento: ' . addslashes($result) . '");</script>';
    }

    // --- insertar espacio ---
    $uuid_espacio = generateUuidV4();
    $url = 'http://' . $_ENV['SERVER_IP'] . ':' . $_ENV['DATABASE_PORT'] . '/rest/v1/space';
    $ch = curl_init($url);
    $data = [
        'id' => $uuid_espacio,
        'establecimiento_id' => $uuid_establecimiento,
        'name' => $_SESSION['espacio_trabajo']['nombre'],
        'description' => $_SESSION['espacio_trabajo']['descripcion'],
    ];

    $payload = json_encode($data);

    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'apikey: ' . $supabaseKey
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    $result = curl_exec($ch);
    $httpCodeEsp = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCodeEsp >= 300) {
        echo '<script>console.error("Error Espacio: ' . addslashes($result) . '");</script>';
    }

    // --- insertar galería ---
    if (isset($_SESSION['rutas']) && is_array($_SESSION['rutas'])) {
        foreach ($_SESSION['rutas'] as $ruta) {
            $url = 'http://' . $_ENV['SERVER_IP'] . ':' . $_ENV['DATABASE_PORT'] . '/rest/v1/gallery';
            $ch = curl_init($url);
            $data = [
                'establecimiento_id' => $uuid_establecimiento,
                'image_url' => $ruta
            ];

            $payload = json_encode($data);

            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'apikey: ' . $supabaseKey
            ]);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
            curl_exec($ch);
            curl_close($ch);
        }
    }

    insertarHorariosYServicios($uuid_espacio, $uuid_establecimiento, $token, $supabaseKey);
}

if (!isset($_SESSION['espacio_trabajo'])) {
    header("Location: registerAnfitrion-paso5.php");
    exit;
}

if (!isset($_SESSION['verification_code'])) {
    $_SESSION['verification_code'] = sprintf("%06d", mt_rand(100000, 999999));

    sendVerificationEmail();
}

function sendVerificationEmail()
{
    header('Location: ../emails/codigoEmailHost.php?email=' . $_SESSION['host']['email']);
}

if (isset($_POST['verificar'])) {
    $inputCode = trim($_POST['codigo']);

    if (empty($inputCode)) {
        $formError = "Debe ingresar el código de verificación.";
    } elseif ($inputCode != $_SESSION['verification_code']) {
        $formError = "El código ingresado no es válido. Por favor, verifique e intente nuevamente.";
    } else {
        $formSuccess = "Código verificado correctamente. Procesando su registro...";

        insertarDatos();

       // Redirección inteligente según el plan que eligió en el Paso 6
        $redirectUrl = 'registerAnfitrion-completo.php'; // Por defecto Básico
        
        if (isset($_SESSION['plan_seleccionado'])) {
            if ($_SESSION['plan_seleccionado'] === 'Pro') {
                $redirectUrl = 'mejoraPro.php';
            } elseif ($_SESSION['plan_seleccionado'] === 'Premium') {
                $redirectUrl = 'mejoraPremium.php';
            }
        }

        echo "<script>
        setTimeout(function() {
            window.location.href = '" . $redirectUrl . "';
        }, 1500);
        </script>";
    }
}

if (isset($_POST['regenerar'])) {
    $_SESSION['verification_code'] = sprintf("%06d", mt_rand(100000, 999999));

    sendVerificationEmail();

    echo "<script>console.log('Nuevo código de verificación: " . $_SESSION['verification_code'] . "');</script>";

    $formSuccess = "Se ha generado un nuevo código de verificación. Por favor, revise su email.";
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://kit.fontawesome.com/b8814a2854.js" crossorigin="anonymous"></script>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;600;700&display=swap" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="icon" href="../favicon-color.png">
    <link rel="icon" href="../favicon-negro.png" media="(prefers-color-scheme: light)">
    <link rel="icon" href="../favicon-color.png" media="(prefers-color-scheme: dark)">
    <title>Verificación de Email</title>
    <style>
        body {
            font-family: 'Nunito', sans-serif;
            background-color: #f8f9fa;
        }

        .contenedorAlta {
            max-width: 800px;
            margin: 2rem auto;
            background-color: white;
            border-radius: 15px;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            padding: 1rem;
        }

        .form-control {
            border-radius: 10px;
            padding: 0.75rem;
            border: 1px solid #ced4da;
            transition: border-color 0.3s;
        }

        .form-control:focus {
            border-color: #80bdff;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
        }

        .btn-success {
            background-color: #28a745;
            border: none;
            font-weight: 600;
            padding: 0.75rem 2rem;
        }

        .btn-cancel {
            background-color: #f8f9fa;
            border: 1px solid #ced4da;
            color: #6c757d;
            font-weight: 600;
            padding: 0.75rem 2rem;
        }

        .btn-regenerate {
            background-color: #007bff;
            border: none;
            color: white;
            font-weight: 600;
            padding: 0.5rem 1rem;
        }

        .progress-container {
            width: 100%;
            height: 5px;
            background-color: #e9ecef;
            border-radius: 3px;
            overflow: hidden;
            margin: 1rem 0;
        }

        .progress-bar {
            height: 100%;
            width: 100%;
            background-color: #28a745;
        }

        .alert {
            border-radius: 10px;
            padding: 0.75rem;
            margin-bottom: 1rem;
            display: none;
        }

        .logo-container {
            background-color: #f8f9fa;
            border-radius: 50%;
            width: 120px;
            height: 120px;
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 0 auto;
        }

        .verification-code-container {
            display: flex;
            justify-content: center;
            margin: 20px 0;
        }

        .code-input {
            width: 3rem;
            height: 3.5rem;
            margin: 0 5px;
            text-align: center;
            font-size: 1.5rem;
            border-radius: 8px;
            border: 1px solid #ced4da;
        }
    </style>
</head>

<body>
    <div class="contenedorAlta">
        <div class="col-12 text-center py-3 fw-bold h4">
            Verificación de Email
        </div>

        <div class="col-12 text-center mb-3">
            <div class="logo-container">
                <img src="../img/email.png" width="80" alt="Verificación Email">
            </div>
        </div>

        <div class="col-12 text-center h4 mb-4 fw-bold">
            Verifica tu dirección de correo electrónico
        </div>

        <div class="alert alert-danger" id="error-message" <?php echo !empty($formError) ? 'style="display:block"' : ''; ?>>
            <i class="fas fa-exclamation-circle me-2"></i> <span id="error-text"><?php echo $formError; ?></span>
        </div>

        <div class="alert alert-success" id="success-message" <?php echo !empty($formSuccess) ? 'style="display:block"' : ''; ?>>
            <i class="fas fa-check-circle me-2"></i> <span id="success-text"><?php echo $formSuccess; ?></span>
        </div>

        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-8 text-center">
                    <p>Hemos enviado un código de verificación de 6 dígitos a tu dirección de correo electrónico. Por
                        favor, ingresa el código a continuación para completar tu registro.</p>

                    <form method="post" action="" id="verificationForm">
                        <div class="mb-4">
                            <label for="codigo" class="form-label fw-bold">Código de verificación</label>
                            <div class="verification-code-container">
                                <input type="text" id="codigo" name="codigo" class="form-control" maxlength="6"
                                    autocomplete="off" placeholder="Ingresa el código de 6 dígitos">
                            </div>
                        </div>

                        <div class="d-flex justify-content-center mb-4">
                            <p class="me-2">¿No recibiste el código?</p>
                            <button type="submit" name="regenerar" class="btn btn-link p-0">Enviar nuevo código</button>
                        </div>

                        <div class="progress-container">
                            <div class="progress-bar"></div>
                        </div>

                        <div class="row mt-4">
                            <div class="col-6 text-end">
                                <button class="btn btn-cancel rounded-pill" type="button"
                                    onclick="location.href='registerAnfitrion-paso5.php'">Anterior</button>
                            </div>
                            <div class="col-6">
                                <button type="submit" name="verificar" id="btnVerificar"
                                    class="btn btn-success rounded-pill">Verificar</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="container-fluid p-3">
            <div class="row text-center">
                <div class="col-12">Verificación Final</div>
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function() {


            $('#verificationForm').submit(function(e) {
                if ($(this).find('button[name="regenerar"]').is(':focus')) {
                    return true;
                }

                const codigo = $('#codigo').val().trim();

                if (!codigo) {
                    e.preventDefault();
                    $('#error-message').show();
                    $('#error-text').text('Por favor, ingresa el código de verificación.');
                    return false;
                }

                if (codigo.length !== 6 || !/^\d+$/.test(codigo)) {
                    e.preventDefault();
                    $('#error-message').show();
                    $('#error-text').text('El código debe ser de 6 dígitos numéricos.');
                    return false;
                }

                return true;
            });

            $('#codigo').on('input', function() {
                $('#error-message').hide();
            });
        });
    </script>
</body>

</html>