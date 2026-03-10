<?php
session_start();
$error = '';
$success = '';

require '../vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

// Verificar que tenemos el email de recuperación
if (!isset($_SESSION['recovery_email'])) {
    header('Location: recuperar_password.php');
    exit();
}

$email = $_SESSION['recovery_email'];

// Generar código de verificación si no existe o si se solicita regenerar
if (!isset($_SESSION['recover_code']) || isset($_POST['regenerar_codigo'])) {
    $_SESSION['recover_code'] = sprintf("%06d", mt_rand(1, 999999));
    $_SESSION['code_generated_time'] = time();

    // Obtener datos del gestor usando SERVICE_APIKEY
    $url = "http://" . $_ENV['SERVER_IP'] . ":" . $_ENV['DATABASE_PORT'] . "/rest/v1/gestor?email=eq." . urlencode($email);

    $ch = curl_init($url);
    curl_setopt_array($ch, array(
        CURLOPT_CUSTOMREQUEST => "GET",
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json',
            'apikey: ' . $_ENV['SERVICE_APIKEY'],
            'Authorization: Bearer ' . $_ENV['SERVICE_APIKEY']
        ),
        CURLOPT_RETURNTRANSFER => true,
    ));

    $resultado = curl_exec($ch);
    curl_close($ch);

    $datos = json_decode($resultado, true);
    $nombre = '';
    if (is_array($datos) && count($datos) > 0) {
        $nombre = $datos[0]['name'] ?? '';
    }

    function sendVerificationEmail($email, $nombre)
    {
        header("Location: ../emails/recuperarContrasenaEmail.php?email=" . urlencode($email) . "&nombre=" . urlencode($nombre) . "&type=gestor");
        exit();
    }

    sendVerificationEmail($email, $nombre);
}

if (isset($_POST['codigo_verificacion'])) {
    $codigoIntroducido = $_POST['codigo_verificacion'];

    if ($codigoIntroducido == $_SESSION['recover_code']) {
        header('Location: recuperar_password-Paso3.php');
        exit();
    } else {
        $error = 'El código de verificación introducido no es correcto.';
    }
}

$tiempoTranscurrido = 0;
if (isset($_SESSION['code_generated_time'])) {
    $tiempoTranscurrido = time() - $_SESSION['code_generated_time'];
}

if (isset($_GET['status']) && $_GET['status'] == 'ok') {
    $success = 'El código ha sido enviado correctamente a tu correo.';
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
    <link rel="icon" href="../img/favicon-color.png">

    <link rel="icon" href="../img/favicon-negro.png" media="(prefers-color-scheme: light)">

    <link rel="icon" href="../img/favicon-color.png" media="(prefers-color-scheme: dark)">
    <title>Verificación de Código - Gestor</title>
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

        .login-container {
            background-color: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            padding: 40px;
            max-width: 500px;
            width: 100%;
            text-align: center;
            margin: 35px auto;
        }

        .login-logo {
            max-width: 150px;
            margin-bottom: 20px;
        }

        .login-title {
            color: #333;
            font-weight: 700;
            margin-bottom: 15px;
        }

        .login-subtitle {
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
        }

        .codigo-input {
            font-size: 24px;
            text-align: center;
            letter-spacing: 8px;
            font-weight: bold;
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

        .regenerar-link {
            color: #28a745;
            text-decoration: none;
            font-weight: 600;
            cursor: pointer;
        }

        .regenerar-link:hover {
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

        .info-text {
            color: #6c757d;
            margin-bottom: 25px;
            font-size: 14px;
            line-height: 1.5;
        }

        .email-info {
            background-color: #e9f7ef;
            border: 1px solid #d4edda;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
        }

        .email-info i {
            color: #28a745;
            margin-right: 8px;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="login-container">
            <img src="../img/antena.png" alt="Establecimiento" class="login-logo">

            <h2 class="login-title">Verificación de Código</h2>
            <h3 class="login-subtitle">Revisa tu correo electrónico</h3>

            <div class="email-info">
                <i class="fas fa-envelope"></i>
                Hemos enviado un código de verificación de 6 dígitos a:<br>
                <strong><?= htmlspecialchars($email) ?></strong>
            </div>

            <div class="info-text">
                Introduce el código que has recibido para continuar con el proceso de recuperación de contraseña.
            </div>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success text-center" role="alert">
                    <i class="fas fa-check-circle me-2"></i>
                    <?= $success ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger text-center" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?= $error ?>
                </div>
            <?php endif; ?>

            <form method="post">
                <div class="row">
                    <label for="codigo_verificacion" class="form-label">
                        <span>Código de verificación</span>
                        <input type="text" class="form-control codigo-input" name="codigo_verificacion"
                            id="codigo_verificacion" maxlength="6" pattern="[0-9]{6}"
                            placeholder="000000" required>
                    </label>
                </div>

                <div class="d-flex justify-content-center mb-3">
                    <button class="btn btn-outline-secondary btn-custom" type="button" onclick="location.href='recuperar_password.php'">
                        Volver atrás
                    </button>
                    <button class="btn btn-success btn-custom" type="submit">
                        Verificar código
                    </button>
                </div>
            </form>

            <div class="mb-3">
                ¿No has recibido el código?
                <form method="post" style="display: inline;">
                    <button type="submit" name="regenerar_codigo" class="regenerar-link btn p-0 border-0 bg-transparent">
                        Reenviar código
                    </button>
                </form>
            </div>

            <?php if ($tiempoTranscurrido > 0): ?>
                <div class="text-muted small mb-3">
                    <i class="fas fa-clock me-1"></i>
                    Código enviado hace <?= gmdate("i:s", $tiempoTranscurrido) ?> minutos
                </div>
            <?php endif; ?>

            <div class="powered-by">
                Powered by <img src="../img/smartable.png" alt="Smartable">
            </div>
        </div>
    </div>

    <script>
        document.getElementById('codigo_verificacion').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 6) value = value.slice(0, 6);
            e.target.value = value;
        });

        document.getElementById('codigo_verificacion').addEventListener('input', function(e) {
            if (e.target.value.length === 6) {}
        });

        // ECHO POR CONSOLA DEL CÓDIGO (PARA PRUEBAS)
        console.log("=========================================");
        console.log("CÓDIGO DE VERIFICACIÓN:");
        console.log("<?= isset($_SESSION['recover_code']) ? $_SESSION['recover_code'] : 'Ninguno' ?>");
        console.log("=========================================");
    </script>
</body>

</html>