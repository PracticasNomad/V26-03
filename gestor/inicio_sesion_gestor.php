<?php
session_start();
$error = '';

require '../vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

if (isset($_POST['email']) && isset($_POST['password'])) {

    $email = trim($_POST['email']);
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
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $result = json_decode($result, true);
    curl_close($ch);

    // Si el login en Auth fue correcto
    if ($httpCode === 200 && isset($result['access_token'])) {
        
        $userId = $result['user']['id'];
        $token = $result['access_token'];

        // Asignamos variables base
        $_SESSION["token"] = $token;
        $_SESSION["email"] = $email;
        $_SESSION["user_id"] = $userId;

        // ===============================================================
        // 1. VERIFICAR SI ES ADMINISTRADOR (Fusión Oculta)
        // ===============================================================
        // Buscamos tanto en "admin" como en "administrador" para evitar fallos de nombres
        $tablasAdmin = ['admin', 'administrador'];
        $esAdmin = false;

        foreach ($tablasAdmin as $tabla) {
            $urlAdmin = "http://" . $_ENV['SERVER_IP'] . ":" . $_ENV['DATABASE_PORT'] . "/rest/v1/" . $tabla . "?id=eq." . urlencode($userId) . "&select=id";
            $chAdmin = curl_init($urlAdmin);
            curl_setopt_array($chAdmin, [
                CURLOPT_CUSTOMREQUEST => "GET",
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer ' . $_ENV['SERVICE_APIKEY'],
                    'apikey: ' . $_ENV['SERVICE_APIKEY']
                ],
                CURLOPT_RETURNTRANSFER => true
            ]);
            $resAdmin = curl_exec($chAdmin);
            $codeAdmin = curl_getinfo($chAdmin, CURLINFO_HTTP_CODE);
            curl_close($chAdmin);

            $datosAdmin = json_decode($resAdmin, true);

            // Si devuelve 200 y encuentra datos, es que está en esta tabla
            if ($codeAdmin === 200 && is_array($datosAdmin) && count($datosAdmin) > 0) {
                $esAdmin = true;
                break; // Lo hemos encontrado, dejamos de buscar
            }
        }

        if ($esAdmin) {
            // 🎉 ES ADMINISTRADOR: Le damos el rol y lo redirigimos a su panel oculto
            $_SESSION["rol"] = "administrador";
            header('Location: ../administrador/tuPerfil.php');
            exit();
        }

        // ===============================================================
        // 2. SI NO ES ADMIN, VERIFICAMOS SI ES GESTOR NORMAL
        // ===============================================================
        $urlGestor = "http://" . $_ENV['SERVER_IP'] . ":" . $_ENV['DATABASE_PORT'] . "/rest/v1/gestor?id=eq." . urlencode($userId);

        $chGestor = curl_init($urlGestor);
        curl_setopt_array($chGestor, [
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $_ENV['SERVICE_APIKEY'],
                'apikey: ' . $_ENV['SERVICE_APIKEY']
            ],
            CURLOPT_RETURNTRANSFER => true,
        ]);

        $resGestor = curl_exec($chGestor);
        $codeGestor = curl_getinfo($chGestor, CURLINFO_HTTP_CODE);
        curl_close($chGestor);

        $datosGestor = json_decode($resGestor, true);

        if ($codeGestor === 200 && is_array($datosGestor) && count($datosGestor) > 0) {
            // 🎉 ES GESTOR: Limpiamos rol admin por si acaso y entra a su panel
            unset($_SESSION["rol"]); 
            header('Location: tuPerfil.php');
            exit();
        }

        // ===============================================================
        // 3. SI NO ES NADA (Ej: Un nómada o un anfitrión intentando colarse)
        // ===============================================================
        unset($_SESSION["token"]);
        unset($_SESSION["email"]);
        unset($_SESSION["user_id"]);
        $error = 'Acceso denegado: Tu cuenta no tiene permisos de gestión.';

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
    <title>Inicio sesión</title>
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
            min-height: 100vh;
        }

        .login-container {
            background-color: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
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
            width: 100%;
        }

        .btn-custom:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .forgot-password {
            color: #28a745;
            text-decoration: none;
            font-weight: 600;
        }

        .forgot-password:hover {
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
                <h3 class="login-subtitle">Acceso a tu panel de gestión de TheNomadApp</h3>

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
                    <div class="alert alert-danger text-center" role="alert" style="border-radius: 15px;">
                        <i class="fas fa-exclamation-circle me-1"></i> <?= $error ?>
                    </div>
                <?php endif; ?>

                <div class="d-flex justify-content-center mb-3 mt-4">
                    <button class="btn btn-success btn-custom" type="submit">
                        Entrar al Panel
                    </button>
                </div>

                <div class="mb-3">
                    <a href="recuperar_password.php" class="forgot-password">He olvidado mi contraseña</a>
                </div>

                <div class="powered-by">
                    Powered by <img src="../../img/smartable.png" alt="Smartable">
                </div>
            </form>
        </div>
    </div>
</body>

</html>