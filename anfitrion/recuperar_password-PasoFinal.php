<?php
session_start();

function limpiarSesionRecuperacion()
{
    unset($_SESSION['recovery_email']);
    unset($_SESSION['recover_code']);
    unset($_SESSION['user_recover_id']);
    unset($_SESSION['code_generated_time']);
}

if (!isset($_SESSION['user_recover_id'])) {
    header('Location: recuperar_password.php');
    exit();
}

limpiarSesionRecuperacion();

if (isset($_POST['iniciar_sesion'])) {
    header('Location: inicio_sesion_anfitrion.php');
    exit();
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
    <title>Contraseña Actualizada - Anfitrión</title>
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

        .success-container {
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

        .success-icon {
            font-size: 4rem;
            color: #28a745;
            margin-bottom: 20px;
            animation: checkmark 0.6s ease-in-out;
        }

        @keyframes checkmark {
            0% {
                transform: scale(0);
                opacity: 0;
            }

            50% {
                transform: scale(1.2);
            }

            100% {
                transform: scale(1);
                opacity: 1;
            }
        }

        .success-title {
            color: #28a745;
            font-weight: 700;
            margin-bottom: 15px;
            font-size: 1.8rem;
        }

        .success-subtitle {
            color: #6c757d;
            margin-bottom: 25px;
            font-size: 1.1rem;
        }

        .success-message {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 30px;
            color: #155724;
        }

        .success-message i {
            font-size: 1.2rem;
            margin-bottom: 10px;
        }

        .btn-custom {
            padding: 15px 40px;
            border-radius: 30px;
            font-weight: 600;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            min-width: 200px;
        }

        .btn-custom:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(40, 167, 69, 0.3);
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

        .info-list {
            text-align: left;
            margin: 20px 0;
            color: #6c757d;
        }

        .info-list li {
            margin-bottom: 8px;
            display: flex;
            align-items: center;
        }

        .info-list i {
            color: #28a745;
            margin-right: 10px;
            width: 16px;
        }

        .security-tips {
            background-color: #e3f2fd;
            border: 1px solid #bbdefb;
            border-radius: 15px;
            padding: 20px;
            margin: 25px 0;
            text-align: left;
        }

        .security-tips h6 {
            color: #1976d2;
            font-weight: 700;
            margin-bottom: 15px;
        }

        .security-tips ul {
            margin: 0;
            padding-left: 20px;
        }

        .security-tips li {
            margin-bottom: 8px;
            color: #424242;
            font-size: 14px;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="success-container">
            <form method="post">
                <img src="../img/antena.png" alt="Establecimiento" class="login-logo">

                <div class="success-icon">
                    <i class="fas fa-check-circle"></i>
                </div>

                <h2 class="success-title">¡Contraseña Actualizada!</h2>
                <h3 class="success-subtitle">Tu contraseña se ha cambiado exitosamente</h3>

                <div class="success-message">
                    <i class="fas fa-shield-alt d-block"></i>
                    <strong>¡Perfecto!</strong> Tu contraseña ha sido actualizada correctamente.<br>
                    Ya puedes iniciar sesión usando tu nueva contraseña.
                </div>

                <div class="info-list">
                    <ul class="list-unstyled">
                        <li>
                            <i class="fas fa-check"></i>
                            Tu cuenta está ahora más segura
                        </li>
                        <li>
                            <i class="fas fa-check"></i>
                            Puedes acceder con tu nueva contraseña
                        </li>
                        <li>
                            <i class="fas fa-check"></i>
                            El proceso de recuperación ha finalizado
                        </li>
                    </ul>
                </div>

                <div class="security-tips">
                    <h6><i class="fas fa-lightbulb me-2"></i>Consejos de seguridad:</h6>
                    <ul>
                        <li>Mantén tu contraseña en un lugar seguro</li>
                        <li>No compartas tus credenciales con nadie</li>
                        <li>Cierra sesión cuando no uses la aplicación</li>
                        <li>Cambia tu contraseña periódicamente</li>
                    </ul>
                </div>

                <button class="btn btn-success btn-custom" type="submit" name="iniciar_sesion">
                    <i class="fas fa-sign-in-alt me-2"></i>
                    Iniciar Sesión
                </button>

                <div class="text-muted small mt-3">
                    <i class="fas fa-info-circle me-1"></i>
                    Si tienes problemas para acceder, contacta con el soporte técnico.
                </div>

                <div class="powered-by">
                    Powered by <img src="../img/smartable.png" alt="Smartable">
                </div>
            </form>
        </div>
    </div>

    <script>
        history.pushState(null, null, location.href);
        window.onpopstate = function() {
            history.go(1);
        };
    </script>
</body>

</html>