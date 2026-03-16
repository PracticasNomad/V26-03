<?php
session_start();
$error = '';

require '../vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

if (isset($_POST['email']) && isset($_POST['password'])) {

    $email = $_POST['email'];
    $password = $_POST['password'];

    $url = 'http://' . $_ENV['SERVER_IP'] . ':' . $_ENV['DATABASE_PORT'] . '/auth/v1/token?grant_type=password';
    $supabaseKey = $_ENV['DATABASE_APIKEY'];

    $ch = curl_init($url);
    $data = array(
        'email' => $email,
        'password' => $password
    );
    $payload = json_encode($data);

    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'apikey: ' . $supabaseKey,
        'Prefer: return=minimal'
    ]);

    $result = curl_exec($ch);
    $result = json_decode($result, true);
    curl_close($ch);

    if (isset($result['access_token'])) {
        $_SESSION["token"] = $result['access_token'];
        $_SESSION["email"] = $email;
        $_SESSION["user_id"] = $result['user']['id'];

        // ✅ PRIMERO buscamos en la tabla "gestor"
        $urlGestor = "http://" . $_ENV['SERVER_IP'] . ":" . $_ENV['DATABASE_PORT'] . "/rest/v1/gestor?id=eq." . $_SESSION["user_id"];

        $ch = curl_init($urlGestor);
        curl_setopt_array($ch, array(
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                // CAMBIAMOS ESTAS DOS LÍNEAS PARA USAR LA SERVICE KEY:
                'Authorization: Bearer ' . $_ENV['SERVICE_APIKEY'],
                'apikey: ' . $_ENV['SERVICE_APIKEY']
            ),
            CURLOPT_RETURNTRANSFER => true,
        ));

        $resultadoGestor = curl_exec($ch);
        $codigoGestor = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($codigoGestor === 200) {
            $datosGestor = json_decode($resultadoGestor, true);
            if (count($datosGestor) > 0) {
                // ✅ Es un gestor
                header('Location: tuPerfil.php');
                exit();
            }
        }

        // 🧑‍💼 Si no es gestor, buscamos en la tabla "administrador"
        $urlAdmin = "http://" . $_ENV['SERVER_IP'] . ":" . $_ENV['DATABASE_PORT'] . "/rest/v1/administrador?id=eq." . $_SESSION["user_id"];

        $ch = curl_init($urlAdmin);
        curl_setopt_array($ch, array(
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'Authorization: Bearer ' . $_SESSION["token"],
                'apikey: ' . $supabaseKey
            ),
            CURLOPT_RETURNTRANSFER => true,
        ));

        $resultadoAdmin = curl_exec($ch);
        $codigoAdmin = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($codigoAdmin === 200) {
            $datosAdmin = json_decode($resultadoAdmin, true);
            if (count($datosAdmin) > 0) {
                // ✅ Es un administrador
                header('Location: ../administrador/tuPerfil.php');
                exit();
            }
        }

        // ❌ No es ni gestor ni administrador
        unset($_SESSION["token"]);
        unset($_SESSION["email"]);
        unset($_SESSION["user_id"]);
        $error = 'No tienes un rol asignado (ni gestor ni administrador)';
    } else {
        $error = 'Correo o contraseña incorrectos';
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
    <title>Inicio sesión Anfitrion</title>
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

        .forgot-password,
        .register-link {
            color: #28a745;
            text-decoration: none;
            font-weight: 600;
        }

        .forgot-password:hover,
        .register-link:hover {
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
        <div class="login-container">
            <form method="post">
                <img src="../../img/antena.png" alt="Establecimiento" class="login-logo">

                <h2 class="login-title">Inicia sesión</h2>
                <h3 class="login-subtitle">¿Tienes una cuenta de gestion asociada a yonomadapp?</h3>

                <div class="row">
                    <label for="email" class="form-label">
                        <span>Introduce tu e-mail</span>
                        <input type="text" class="form-control" name="email" id="email" required>
                    </label>
                </div>

                <div class="row">
                    <label for="password" class="form-label">
                        <span>Introduce tu contraseña</span>
                        <input type="password" class="form-control" name="password" id="password" required>
                    </label>
                </div>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger text-center" role="alert">
                        <?= $error ?>
                    </div>
                <?php endif; ?>

                <div class="d-flex justify-content-center mb-3">
                    <button class="btn btn-success btn-custom" type="submit">
                        Entrar
                    </button>
                </div>

                <div class="mb-3">
                    <a href="recuperar_password.php" class="forgot-password">He olvidado mi contraseña</a>
                </div>

                <div class="mt-4 pt-3 border-top">
                    <p class="text-muted small mb-2">Atajo de desarrollo:</p>
                    <a href="../administrador/inicio_sesion_admin.php" class="btn btn-outline-danger btn-sm"
                        style="border-radius: 20px; font-weight: 600;">
                        🧪 Ir a Login de Administrador
                    </a>
                </div>
                <div class="powered-by">
                    Powered by <img src="../../img/smartable.png" alt="Smartable">
                </div>
            </form>
        </div>
    </div>
</body>

</html>