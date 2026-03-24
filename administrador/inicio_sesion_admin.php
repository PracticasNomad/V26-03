<?php
require_once 'verificar_sesion_admin.php';

require '../vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

$errorMsg = "";

// ==========================================
// 🚀 LOGIN REAL CON SUPABASE
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!empty($email) && !empty($password)) {

        // 1. Autenticar al usuario en Auth de Supabase
        $authUrl = "http://" . $_ENV['SERVER_IP'] . ":" . $_ENV['DATABASE_PORT'] . "/auth/v1/token?grant_type=password";
        $authData = json_encode(['email' => $email, 'password' => $password]);

        $ch = curl_init($authUrl);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $authData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'apikey: ' . $_ENV['DATABASE_APIKEY'],
            'Content-Type: application/json'
        ]);

        $authResponse = curl_exec($ch);
        $authHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($authHttpCode === 200) {
            $authResult = json_decode($authResponse, true);
            $userId = $authResult['user']['id'];
            $token = $authResult['access_token'];

            // 2. VERIFICACIÓN DE ROL: ¿Es realmente un Administrador?
            // Buscamos su ID en la tabla 'admin' que creaste
            $adminCheckUrl = "http://" . $_ENV['SERVER_IP'] . ":" . $_ENV['DATABASE_PORT'] . "/rest/v1/admin?id=eq." . urlencode($userId) . "&select=id";

            $chAdmin = curl_init($adminCheckUrl);
            curl_setopt_array($chAdmin, [
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer ' . $_ENV['SERVICE_APIKEY'], // <-- Llave maestra
                    'apikey: ' . $_ENV['SERVICE_APIKEY']                // <-- Llave maestra
                ],
                CURLOPT_RETURNTRANSFER => true
            ]);
            $adminResponse = curl_exec($chAdmin);
            $adminHttpCode = curl_getinfo($chAdmin, CURLINFO_HTTP_CODE);
            curl_close($chAdmin);

            $adminData = json_decode($adminResponse, true);

            // Si devuelve un 200 y el array tiene datos, es que existe en la tabla admin
            if ($adminHttpCode === 200 && is_array($adminData) && count($adminData) > 0) {

                // ¡Acceso Concedido! Guardamos las variables de sesión reales
                $_SESSION["token"] = $token;
                $_SESSION["email"] = $email;
                $_SESSION["user_id"] = $userId;
                $_SESSION["rol"] = "administrador"; // Muy útil para luego proteger vistas

                header('Location: tuPerfil.php');
                exit();
            } else {
                // Existe en Auth pero NO es administrador (es un anfitrión o un gestor intentando colarse)
                $errorMsg = "Acceso denegado: Esta cuenta no tiene privilegios de Administrador.";
            }
        } else {
            // Error de email o contraseña
            $errorMsg = "Credenciales incorrectas. Revisa tu email y contraseña.";
        }
    } else {
        $errorMsg = "Por favor, rellena todos los campos.";
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
    <title>Inicio sesión Administrador</title>
    <style>
        body {
            font-family: 'Nunito', sans-serif;
            background-color: #212529;
            /* Fondo oscuro para diferenciarlo de gestor */
            min-height: 100vh;
        }

        .container {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        .login-container {
            background-color: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
            padding: 40px;
            max-width: 500px;
            width: 100%;
            text-align: center;
        }

        .login-logo {
            max-width: 150px;
            margin-bottom: 20px;
        }

        .login-title {
            color: #333;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .login-subtitle {
            color: #dc3545;
            /* Color rojo para el rol admin */
            font-weight: bold;
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
            border-radius: 30px;
            font-weight: 600;
            width: 100%;
            transition: all 0.3s ease;
        }

        .btn-custom:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
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
                <img src="../../img/antena.png" alt="Logo" class="login-logo">

                <h2 class="login-title">Panel de Control</h2>
                <h3 class="login-subtitle">Acceso exclusivo Administradores</h3>

                <div class="row text-start">
                    <label for="email" class="form-label">
                        <span>E-mail de administrador</span>
                        <input type="email" class="form-control" name="email" id="email" placeholder="admin@ejemplo.com" required>
                    </label>
                </div>

                <div class="row text-start">
                    <label for="password" class="form-label">
                        <span>Contraseña</span>
                        <input type="password" class="form-control" name="password" id="password" placeholder="******" required>
                    </label>
                </div>

                <?php if (!empty($errorMsg)): ?>
                    <div class="alert alert-danger text-center" role="alert" style="font-size: 0.9em; border-radius: 15px;">
                        <i class="fas fa-exclamation-triangle me-2"></i> <strong>Error:</strong> <?php echo $errorMsg; ?>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info text-center" role="alert" style="font-size: 0.9em; border-radius: 15px;">
                        <i class="fas fa-shield-alt me-2"></i> Área de acceso restringido.
                    </div>
                <?php endif; ?>

                <div class="d-flex justify-content-center mb-3 mt-3">
                    <button class="btn btn-danger btn-custom" type="submit">
                        <i class="fas fa-sign-in-alt me-2"></i> Entrar al Panel de Admin
                    </button>
                </div>

                <div class="powered-by">
                    Powered by <img src="../../img/smartable.png" alt="Smartable">
                </div>
            </form>
        </div>
    </div>
</body>

</html>