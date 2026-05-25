<?php
session_start();
$error = '';
$success = '';

require '../vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

if (isset($_POST['email'])) {
    $email = $_POST['email'];

    $url = "http://" . $_ENV['SERVER_IP'] . ":" . $_ENV['DATABASE_PORT'] . "/rest/v1/host?email=eq." . urlencode($email);

    $ch = curl_init($url);
    curl_setopt_array($ch, array(
        CURLOPT_CUSTOMREQUEST => "GET",
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json',
            'apikey: ' . $_ENV['DATABASE_APIKEY']
        ),
        CURLOPT_RETURNTRANSFER => true,
    ));

    $resultado = curl_exec($ch);
    $codigoRespuesta = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($codigoRespuesta === 200) {
        $datos = json_decode($resultado, true);
        if (count($datos) > 0) {
            $_SESSION['recovery_email'] = $email;
            $_SESSION['user_recover_id'] = $datos[0]['id'];
            header('Location: recuperar_password-Paso2.php');
            exit();
        } else {
            $error = 'No se encuentra un host registrado con ese correo electrónico';
        }
    } else {
        $error = 'Error al verificar el correo electrónico. Inténtalo de nuevo.';
    }
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
    <link rel="icon" href="../favicon-color.png">
    <link rel="icon" href="../favicon-negro.png" media="(prefers-color-scheme: light)">
    <link rel="icon" href="../favicon-color.png" media="(prefers-color-scheme: dark)">
    <title>Recuperar Contraseña - Anfitrión</title>
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

        .back-login {
            color: #28a745;
            text-decoration: none;
            font-weight: 600;
        }

        .back-login:hover {
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
    </style>
</head>

<body>
    <div class="container">
        <div class="login-container">
            <form method="post">
                <img src="../img/antena.png" alt="Establecimiento" class="login-logo">

                <h2 class="login-title">Recuperar Contraseña</h2>
                <h3 class="login-subtitle">¿Has olvidado tu contraseña?</h3>

                <div class="info-text">
                    Introduce tu dirección de correo electrónico y te ayudaremos a recuperar el acceso a tu cuenta de anfitrión.
                </div>

                <div class="row">
                    <label for="email" class="form-label">
                        <span>Introduce tu e-mail</span>
                        <input type="email" class="form-control" name="email" id="email"
                            value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>"
                            required>
                    </label>
                </div>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger text-center" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <?= $error ?>
                    </div>
                <?php endif; ?>

                <div class="d-flex justify-content-center mb-3">
                    <button class="btn btn-outline-secondary btn-custom" type="button" onclick="location.href='inicio_sesion_anfitrion.php'">
                        Volver al login
                    </button>
                    <button class="btn btn-success btn-custom" type="submit">
                        Siguiente
                    </button>
                </div>

                <div>
                    ¿Ya tienes acceso a tu cuenta?
                    <a href="inicio_sesion_anfitrion.php" class="back-login">Inicia sesión</a>
                </div>

                <div class="powered-by">
                    Powered by <img src="../img/smartable.png" alt="Smartable">
                </div>
            </form>
        </div>
    </div>
    <?php include '../typebot.php'; ?>
</body>

</html>