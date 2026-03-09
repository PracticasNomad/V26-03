<?php
session_start();
$error = '';

require '../vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

// Verificar que tenemos los datos necesarios de los pasos anteriores
if (!isset($_SESSION['recovery_email']) || !isset($_SESSION['user_recover_id'])) {
    header('Location: recuperar_password.php');
    exit();
}

// Función para limpiar las variables de sesión del proceso de recuperación
function limpiarSesionRecuperacion()
{
    unset($_SESSION['recovery_email']);
    unset($_SESSION['recover_code']);
    unset($_SESSION['user_recover_id']);
    unset($_SESSION['code_generated_time']);
}

// Función para validar la contraseña
function validarPassword($password)
{
    // Mínimo 8 caracteres
    if (strlen($password) < 8) {
        return 'La contraseña debe tener al menos 8 caracteres.';
    }

    // Debe contener al menos una mayúscula
    if (!preg_match('/[A-Z]/', $password)) {
        return 'La contraseña debe contener al menos una letra mayúscula.';
    }

    // Debe contener al menos una minúscula
    if (!preg_match('/[a-z]/', $password)) {
        return 'La contraseña debe contener al menos una letra minúscula.';
    }

    // Debe contener al menos un número
    if (!preg_match('/[0-9]/', $password)) {
        return 'La contraseña debe contener al menos un número.';
    }

    return true;
}

// Si se presiona el botón de volver al login
if (isset($_POST['volver_login'])) {
    limpiarSesionRecuperacion();
    header('Location: inicio_sesion_anfitrion.php');
    exit();
}

// Procesar el formulario de nueva contraseña
if (isset($_POST['nueva_password']) && isset($_POST['confirmar_password'])) {
    $nuevaPassword = $_POST['nueva_password'];
    $confirmarPassword = $_POST['confirmar_password'];

    // Validaciones
    if (empty($nuevaPassword) || empty($confirmarPassword)) {
        $error = 'Por favor, rellena todos los campos.';
    } elseif ($nuevaPassword !== $confirmarPassword) {
        $error = 'Las contraseñas no coinciden.';
    } else {
        // Validar la nueva contraseña
        $validacionPassword = validarPassword($nuevaPassword);
        if ($validacionPassword !== true) {
            $error = $validacionPassword;
        } else {
            // Las contraseñas coinciden y cumplen los requisitos, hacer la petición para cambiar la contraseña
            $url = 'http://' . $_ENV['SERVER_IP'] . ':' . $_ENV['DATABASE_PORT'] . '/auth/v1/admin/users/' . $_SESSION['user_recover_id'];

            $ch = curl_init($url);

            $data = array(
                'password' => $nuevaPassword
            );

            $payload = json_encode($data);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'apikey: ' . $_ENV['SERVICE_APIKEY'],
                'Authorization: Bearer ' . $_ENV['SERVICE_APIKEY']
            ]);

            $result = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode >= 200 && $httpCode < 300) {
                // Éxito - redirigir al paso final
                header('Location: recuperar_password-PasoFinal.php');
                exit();
            } else {
                // Error en la petición
                $error = 'Ha ocurrido un error al actualizar la contraseña. Por favor, inténtalo de nuevo.';

                // Log del error para debugging (opcional)
                error_log("Error updating password: HTTP $httpCode - " . $result);
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
    <script src="https://kit.fontawesome.com/b8814a2854.js" crossorigin="anonymous"></script>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@200;300;400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="icon" href="../favicon-color.png">
    <link rel="icon" href="../favicon-negro.png" media="(prefers-color-scheme: light)">
    <link rel="icon" href="../favicon-color.png" media="(prefers-color-scheme: dark)">
    <title>Nueva Contraseña - Anfitrión</title>
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
            padding: 12px 50px 12px 20px;
            margin-bottom: 15px;
            height: 48px;
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

        .password-requirements {
            background-color: #e3f2fd;
            border: 1px solid #bbdefb;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
            font-size: 13px;
            text-align: left;
        }

        .password-requirements ul {
            margin: 0;
            padding-left: 20px;
        }

        .password-requirements li {
            margin-bottom: 5px;
        }

        .input-group {
            position: relative;
        }

        .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #6c757d;
            cursor: pointer;
            z-index: 10;
            height: 24px;
            width: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-top: -12px;
        }

        .password-toggle:hover {
            color: #28a745;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="login-container">
            <form method="post">
                <img src="../img/antena.png" alt="Establecimiento" class="login-logo">

                <h2 class="login-title">Nueva Contraseña</h2>
                <h3 class="login-subtitle">Crea una contraseña segura</h3>

                <div class="info-text">
                    Por tu seguridad, elige una contraseña que sea fácil de recordar para ti pero difícil de adivinar para otros.
                </div>

                <div class="password-requirements">
                    <strong><i class="fas fa-shield-alt me-2"></i>Requisitos de la contraseña:</strong>
                    <ul>
                        <li>Mínimo 8 caracteres</li>
                        <li>Al menos 1 letra mayúscula</li>
                        <li>Al menos 1 letra minúscula</li>
                        <li>Al menos 1 número</li>
                    </ul>
                </div>

                <div class="row">
                    <label for="nueva_password" class="form-label">
                        <span>Nueva contraseña</span>
                        <div class="input-group">
                            <input type="password" class="form-control" name="nueva_password" id="nueva_password" required>
                            <button type="button" class="password-toggle" onclick="togglePassword('nueva_password', this)">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </label>
                </div>

                <div class="row">
                    <label for="confirmar_password" class="form-label">
                        <span>Confirmar nueva contraseña</span>
                        <div class="input-group">
                            <input type="password" class="form-control" name="confirmar_password" id="confirmar_password" required>
                            <button type="button" class="password-toggle" onclick="togglePassword('confirmar_password', this)">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </label>
                </div>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger text-center" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <?= $error ?>
                    </div>
                <?php endif; ?>

                <div class="d-flex justify-content-center mb-3">
                    <button class="btn btn-outline-secondary btn-custom" type="submit" name="volver_login">
                        Volver al login
                    </button>
                    <button class="btn btn-success btn-custom" type="submit">
                        Cambiar contraseña
                    </button>
                </div>

                <div class="text-muted small">
                    <i class="fas fa-info-circle me-1"></i>
                    Una vez cambiada la contraseña, podrás iniciar sesión con tus nuevas credenciales.
                </div>

                <div class="powered-by">
                    Powered by <img src="../img/smartable.png" alt="Smartable">
                </div>
            </form>
        </div>
    </div>

    <script>
        function togglePassword(inputId, button) {
            const input = document.getElementById(inputId);
            const icon = button.querySelector('i');

            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        // Función para validar la contraseña en tiempo real
        function validarPasswordEnTiempoReal(password) {
            const requisitos = {
                longitud: password.length >= 8,
                mayuscula: /[A-Z]/.test(password),
                minuscula: /[a-z]/.test(password),
                numero: /[0-9]/.test(password)
            };

            return requisitos.longitud && requisitos.mayuscula && requisitos.minuscula && requisitos.numero;
        }

        document.getElementById('confirmar_password').addEventListener('input', function() {
            const nuevaPassword = document.getElementById('nueva_password').value;
            const confirmarPassword = this.value;

            if (confirmarPassword && nuevaPassword !== confirmarPassword) {
                this.style.borderColor = '#dc3545';
            } else {
                this.style.borderColor = '#ced4da';
            }
        });

        document.getElementById('nueva_password').addEventListener('input', function() {
            const password = this.value;

            if (password.length > 0 && !validarPasswordEnTiempoReal(password)) {
                this.style.borderColor = '#dc3545';
            } else {
                this.style.borderColor = '#ced4da';
            }

            // También validar la confirmación si ya tiene contenido
            const confirmarPassword = document.getElementById('confirmar_password');
            if (confirmarPassword.value) {
                if (password !== confirmarPassword.value) {
                    confirmarPassword.style.borderColor = '#dc3545';
                } else {
                    confirmarPassword.style.borderColor = '#ced4da';
                }
            }
        });
    </script>
</body>

</html>