<?php
session_start();

// ==========================================
// 🚀 MODO PRUEBA: INGRESO DIRECTO COMO ADMIN
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Generamos variables de sesión falsas para engañar a tus verificadores
    $_SESSION["token"] = "token_de_prueba_administrador";
    $_SESSION["email"] = "admin@yonomadapp.com";
    $_SESSION["user_id"] = "id_simulado_admin_12345";

    // Opcional: si en el futuro usas una variable de rol para las vistas admin
    $_SESSION["rol"] = "administrador";

    // Redirigimos directamente a la vista de perfil/dashboard del admin
    header('Location: tuPerfil.php');
    exit();
}
// ==========================================
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

                <div class="row">
                    <label for="email" class="form-label">
                        <span>E-mail de administrador</span>
                        <input type="text" class="form-control" name="email" id="email" value="admin@ejemplo.com">
                    </label>
                </div>

                <div class="row">
                    <label for="password" class="form-label">
                        <span>Contraseña</span>
                        <input type="password" class="form-control" name="password" id="password" value="123456">
                    </label>
                </div>

                <div class="alert alert-warning text-center" role="alert" style="font-size: 0.9em;">
                    <strong>Modo Desarrollo:</strong> El login está puenteado. Haz clic abajo para entrar directo a las
                    vistas y probar.
                </div>

                <div class="d-flex justify-content-center mb-3">
                    <button class="btn btn-danger btn-custom" type="submit">
                        🧪 Entrar al Panel de Admin
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