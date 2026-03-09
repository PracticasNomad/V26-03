<?php
session_start();

if (!isset($_SESSION['email_guest']) || !isset($_SESSION['nombre_guest'])) {
    header('Location: registrar.php');
    exit;
}

require __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$error_message = '';
$success_message = '';

function sendVerificationEmail(): void
{

    $url = '../emails/codigoEmailGuest.php?email=' . urlencode($_SESSION['email_guest']);
    header('Location: ' . $url);
    exit;
}

function insertarDatos(): bool
{
    $url = 'http://' . $_ENV['SERVER_IP'] . ':' . $_ENV['DATABASE_PORT'] . '/auth/v1/signup';
    $supabaseKey = $_ENV['DATABASE_APIKEY'];

    $data = [
        'email' => $_SESSION['email_guest'],
        'password' => $_SESSION['password_guest'],
    ];

    $payload = json_encode($data);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'apikey: ' . $supabaseKey,
    ]);

    $result = curl_exec($ch);
    $responseData = json_decode((string) $result, true);

    if ($result === false && !isset($_SESSION['already_host'])) {
        curl_close($ch);
        return false;
    }

    $httpCode = 200;
    if (!isset($_SESSION['already_host'])) {
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    }
    curl_close($ch);

    if (
        $httpCode >= 200 && $httpCode < 300
        && (isset($responseData['user']['id']) || (isset($_SESSION['host_id']) && isset($_SESSION['already_host'])))
    ) {
        $user_id = isset($_SESSION['host_id']) ? $_SESSION['host_id'] : $responseData['user']['id'];

        $url2 = 'http://' . $_ENV['SERVER_IP'] . ':' . $_ENV['DATABASE_PORT'] . '/rest/v1/user';
        $data2 = [
            'id' => $user_id,
            'email' => $_SESSION['email_guest'],
            'name' => $_SESSION['nombre_guest'],
            'telefono' => $_SESSION['telefono_guest'],
            'avatar_url' => 'img/perfil.png',
        ];
        $payload2 = json_encode($data2);

        $ch2 = curl_init($url2);
        curl_setopt($ch2, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ch2, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch2, CURLOPT_POST, true);
        curl_setopt($ch2, CURLOPT_POSTFIELDS, $payload2);
        curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch2, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'apikey: ' . $supabaseKey,
        ]);

        $result2 = curl_exec($ch2);
        $httpCode2 = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
        curl_close($ch2);

        return ($httpCode2 >= 200 && $httpCode2 < 300);
    }

    if ($httpCode == 422) {
        return false;
    }

    return false;
}

if (isset($_GET['sent']) && $_GET['sent'] === '1') {
    $success_message = 'Se ha enviado un nuevo código de verificación a tu correo electrónico';
}
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (isset($_GET['resend']) && $_GET['resend'] === 'true') {
        $_SESSION['verification_code_guest'] = sprintf('%06d', random_int(100000, 999999));
        $success_message = 'Se ha solicitado el reenvío del código de verificación.';
        sendVerificationEmail();
    } elseif (!isset($_SESSION['verification_code_guest'])) {
        $_SESSION['verification_code_guest'] = sprintf('%06d', random_int(100000, 999999));
        sendVerificationEmail();
    }
}

if (isset($_POST['verificar'])) {
    $codigo_ingresado = trim($_POST['codigo'] ?? '');

    if ($codigo_ingresado === '') {
        $error_message = 'Por favor, ingresa el código de verificación';
    } elseif (strcmp((string) $codigo_ingresado, (string) $_SESSION['verification_code_guest']) !== 0) {
        $error_message = 'El código de verificación no es válido';
    } else {
        if (insertarDatos()) {
            header('Location: registerCompleto.php');
            exit;
        }
        $error_message = 'No se ha podido completar el registro. Por favor, inténtelo más tarde.';
    }
}

if (isset($_POST['volver'])) {
    header('Location: registrar.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://kit.fontawesome.com/b8814a2854.js" crossorigin="anonymous"></script>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@200;300;400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="icon" href="favicon-color.png">

    <link rel="icon" href="favicon-negro.png" media="(prefers-color-scheme: light)">

    <link rel="icon" href="favicon-color.png" media="(prefers-color-scheme: dark)">
    <title>Verificación de cuenta</title>
    <style>
        body {
            font-family: 'Nunito', sans-serif;
            background-color: #f4f6f9;
            min-height: 100vh;
        }

        .container {
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .verification-container {
            background-color: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            padding: 40px;
            max-width: 500px;
            width: 100%;
            text-align: center;
            margin: 35px auto;
        }

        .verification-logo {
            max-width: 200px;
            margin-bottom: 20px;
        }

        .verification-title {
            color: #333;
            font-weight: 700;
            margin-bottom: 15px;
        }

        .verification-subtitle {
            color: #6c757d;
            margin-bottom: 25px;
        }

        .form-label span {
            display: block;
            text-align: left;
            margin-bottom: 5px;
            color: #6c757d;
            font-weight: 600;
        }

        .form-control {
            border-radius: 30px;
            padding: 10px 20px;
            margin-bottom: 15px;
            text-align: center;
            font-size: 1.2rem;
            letter-spacing: 3px;
        }

        .btn-custom {
            padding: 12px 30px;
            margin: 0 10px;
            border-radius: 30px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-custom:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .verification-info {
            margin-bottom: 25px;
            color: #6c757d;
        }

        .resend-link {
            color: #28a745;
            text-decoration: none;
            font-weight: 600;
            cursor: pointer;
        }

        .resend-link:hover {
            text-decoration: underline;
        }

        .powered-by {
            color: #6c757d;
            margin-top: 30px;
            font-weight: 600;
        }

        .powered-by img {
            max-height: 30px;
            margin-left: 10px;
            margin-bottom: 20px;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="verification-container">
            <img src="img/tenda.png" alt="Nómada" class="verification-logo">

            <h2 class="verification-title">Verificación de cuenta</h2>
            <p class="verification-subtitle">Hemos enviado un código de verificación a tu correo electrónico</p>

            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger" role="alert">
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success" role="alert">
                    <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>

            <div class="verification-info">
                <p>Por favor, introduce el código de 6 dígitos que hemos enviado a:</p>
                <p><strong><?php echo htmlspecialchars($_SESSION['email_guest']); ?></strong></p>
            </div>

            <form method="post" id="verificationForm">
                <div class="row">
                    <label for="codigo" class="form-label">
                        <span>Código de verificación</span>
                        <input type="text" class="form-control" id="codigo" name="codigo" maxlength="6"
                            autocomplete="off" inputmode="numeric" pattern="[0-9]*">
                    </label>
                </div>

                <div class="mb-4">
                    <p>¿No has recibido el código? <a class="resend-link" onclick="resendCode()">Reenviar</a></p>
                </div>

                <div class="d-flex justify-content-between mb-4">
                    <button type="button" class="btn btn-outline-secondary btn-custom"
                        onclick="window.location.href='registrar.php'">
                        Volver
                    </button>

                    <button type="submit" name="verificar" class="btn btn-success btn-custom">
                        Verificar
                    </button>
                </div>

                <div class="powered-by">
                    Powered by <img src="img/smartable.png" alt="Smartable">
                </div>
            </form>
        </div>
    </div>

    <script>
        function resendCode() {
            window.location.href = 'verificarRegister.php?resend=true';
        }

        window.onload = function() {
            const input = document.getElementById('codigo');
            if (input) {
                input.focus();
                input.addEventListener('input', function() {
                    this.value = this.value.replace(/[^0-9]/g, '');
                });
            }
        };
    </script>
</body>

</html>