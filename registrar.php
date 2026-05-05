<?php
// Prueba Yon
session_start();

require './vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$error_message = '';
$success_message = '';

// Función para hacer peticiones HTTP
function makeHttpRequest($url, $headers = [])
{
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => implode("\r\n", $headers),
            'timeout' => 10
        ]
    ]);

    $result = @file_get_contents($url, false, $context);
    if ($result === FALSE) {
        return false;
    }

    return json_decode($result, true);
}

// Endpoint AJAX para validar email
if (isset($_POST['validate_email'])) {
    header('Content-Type: application/json');

    $email = trim($_POST['email']);
    $response = ['status' => 'success', 'message' => '', 'is_host' => false];

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response['status'] = 'error';
        $response['message'] = 'El formato del email no es válido';
        echo json_encode($response);
        exit;
    }

    // Verificar si existe como user (guest)
    $url = "http://" . $_ENV['SERVER_IP'] . ":" . $_ENV['DATABASE_PORT'] . "/rest/v1/user?email=eq." . urlencode($email);
    $headers = ['apikey: ' . $_ENV['DATABASE_APIKEY']];

    $userResult = makeHttpRequest($url, $headers);

    if ($userResult && !empty($userResult)) {
        $response['status'] = 'error';
        $response['message'] = 'Ya existe una cuenta con este email';
        echo json_encode($response);
        exit;
    }

    // Verificar si existe como host
    $url = "http://" . $_ENV['SERVER_IP'] . ":" . $_ENV['DATABASE_PORT'] . "/rest/v1/host?email=eq." . urlencode($email);

    $hostResult = makeHttpRequest($url, $headers);

    if ($hostResult && !empty($hostResult)) {
        $response['is_host'] = true;
        $response['message'] = 'Email válido - Usuario host detectado';
        $_SESSION['already_host'] = true;
        $_SESSION['host_id'] = $hostResult[0]['id']; // Asumiendo que el id está en el primer resultado
    } else {
        unset($_SESSION['already_host']);
        unset($_SESSION['host_id']);
        $response['message'] = 'Email válido';
    }

    echo json_encode($response);
    exit;
}

if (isset($_POST['enviar'])) {
    $nombre = trim($_POST['nombre']);
    $email = trim($_POST['email']);
    $telefono = trim($_POST['telefono']);
    $password = $_POST['password'];
    $password2 = $_POST['password2'];

    if (empty($nombre) || empty($email) || empty($telefono)) {
        $error_message = 'Los campos nombre, email y teléfono son obligatorios';
    } elseif (!isset($_SESSION['already_host']) && (empty($password) || empty($password2))) {
        $error_message = 'Las contraseñas son obligatorias';
    } elseif (!isset($_SESSION['already_host']) && $password !== $password2) {
        $error_message = 'Las contraseñas no coinciden';
    } elseif (!isset($_SESSION['already_host']) && strlen($password) < 8) {
        $error_message = 'La contraseña debe tener al menos 8 caracteres';
    } elseif (!isset($_SESSION['already_host']) && !preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/', $password)) {
        $error_message = 'La contraseña debe contener al menos una letra minúscula, una mayúscula y un número';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = 'El formato del email no es válido';
    } elseif (!preg_match('/^[0-9]{9}$/', $telefono)) {
        $error_message = 'El teléfono debe ser un número de 9 dígitos';
    } else {
        $_SESSION['nombre_guest'] = $nombre;
        $_SESSION['email_guest'] = $email;
        $_SESSION['telefono_guest'] = $telefono;
        if (!isset($_SESSION['already_host'])) {
            $_SESSION['password_guest'] = $password;
        }

        // Vaciamos el código antiguo, así cuando cuando vayamos a verificarRegister.php envíe un nuevo mail
        unset($_SESSION['verification_code_guest']);

        header('Location: verificarRegister.php');
        exit();
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
    <link rel="icon" href="favicon-color.png">

    <link rel="icon" href="favicon-negro.png" media="(prefers-color-scheme: light)">

    <link rel="icon" href="favicon-color.png" media="(prefers-color-scheme: dark)">
    <title>¡Crea tu cuenta!</title>
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
            max-width: 100%;
        }

        .register-container {
            background-color: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            padding: 40px;
            max-width: 500px;
            width: 100%;
            text-align: center;
            margin: 35px auto;
        }

        .register-logo {
            max-width: 200px;
            margin-bottom: 20px;
        }

        .register-title {
            color: #333;
            font-weight: 700;
            margin-bottom: 15px;
        }

        .register-subtitle {
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

        .form-control:disabled {
            background-color: #f8f9fa;
            opacity: 0.6;
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

        .login-link {
            color: #28a745;
            text-decoration: none;
            font-weight: 600;
        }

        .login-link:hover {
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

        .password-field {
            position: relative;
        }

        .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #6c757d;
        }

        .checkbox-container {
            text-align: left;
            margin-bottom: 20px;
        }

        .checkbox-container a {
            color: #28a745;
            text-decoration: none;
        }

        .checkbox-container a:hover {
            text-decoration: underline;
        }

        .password-requirements {
            text-align: left;
            font-size: 0.8rem;
            color: #6c757d;
            margin-top: -10px;
            margin-bottom: 15px;
            padding-left: 20px;
        }

        .email-validation-status {
            text-align: left;
            font-size: 0.8rem;
            margin-top: -10px;
            margin-bottom: 15px;
            padding-left: 20px;
        }

        .email-validation-status.success {
            color: #28a745;
        }

        .email-validation-status.error {
            color: #dc3545;
        }

        .host-notice {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-size: 0.9rem;
        }

        .loading-spinner {
            display: none;
            width: 16px;
            height: 16px;
            border: 2px solid #f3f3f3;
            border-top: 2px solid #28a745;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-left: 10px;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="register-container">
            <img src="img/tenda.png" alt="Nómada" class="register-logo">

            <h2 class="register-title">Crear cuenta</h2>
            <h3 class="register-subtitle">¿Quieres un espacio cómodo para trabajar o estudiar?</h3>

            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger" role="alert">
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success" role="alert">
                    <?php echo $success_message; ?>
                </div>
            <?php endif; ?>

            <div id="host-notice" class="host-notice" style="display: none;">
                <i class="fas fa-info-circle"></i> Hemos detectado que ya tienes una cuenta como anfitrión.
                Usarás la misma contraseña para acceder como invitado.
            </div>

            <form method="post" id="registerForm">
                <div class="row">
                    <label for="nombre" class="form-label">
                        <span>Introduce tu nombre</span>
                        <input type="text" class="form-control" id="nombre" name="nombre" value="<?php echo isset($_SESSION['nombre_guest']) ? htmlspecialchars($_SESSION['nombre_guest']) : ''; ?>">
                    </label>
                </div>

                <div class="row">
                    <label for="email" class="form-label">
                        <span>Introduce tu e-mail <span class="loading-spinner" id="email-spinner"></span></span>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo isset($_SESSION['email_guest']) ? htmlspecialchars($_SESSION['email_guest']) : ''; ?>">
                    </label>
                    <div id="email-validation" class="email-validation-status"></div>
                </div>

                <div class="row">
                    <label for="telefono" class="form-label">
                        <span>Introduce tu teléfono</span>
                        <input type="tel" class="form-control" id="telefono" name="telefono" pattern="[0-9]{9}" maxlength="9" value="<?php echo isset($_SESSION['telefono_guest']) ? htmlspecialchars($_SESSION['telefono_guest']) : ''; ?>">
                    </label>
                    <div class="password-requirements">
                        El teléfono debe tener exactamente 9 dígitos
                    </div>
                </div>

                <div class="row" id="password-row">
                    <label for="password" class="form-label">
                        <span>Introduce tu contraseña</span>
                        <div class="password-field">
                            <input type="password" class="form-control" id="password" name="password" minlength="8" pattern="^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$" <?php echo isset($_SESSION['already_host']) ? 'disabled' : ''; ?>>
                            <span class="password-toggle" onclick="togglePassword('password')">
                                <i class="fas fa-eye-slash" id="password-icon"></i>
                            </span>
                        </div>
                    </label>
                    <div class="password-requirements" id="password-requirements">
                        La contraseña debe tener al menos 8 caracteres, una letra minúscula, una mayúscula y un número
                    </div>
                </div>

                <div class="row" id="password2-row">
                    <label for="password2" class="form-label">
                        <span>Confirma tu contraseña</span>
                        <div class="password-field">
                            <input type="password" class="form-control" id="password2" name="password2" minlength="8" pattern="^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$" <?php echo isset($_SESSION['already_host']) ? 'disabled' : ''; ?>>
                            <span class="password-toggle" onclick="togglePassword('password2')">
                                <i class="fas fa-eye-slash" id="password2-icon"></i>
                            </span>
                        </div>
                    </label>
                </div>

                <div class="checkbox-container">
                    <label>
                        <input type="checkbox" name="terms" id="terms" required>
                        Acepto la <a href="https://yonomad.app/aviso-legal/" target="_blank">Política de datos</a> y
                        <a href="https://yonomad.app/politica-de-cookies/" target="_blank">Política de Cookies</a>
                    </label>
                </div>

                <div class="d-flex justify-content-center mb-4">
                    <button type="button" onclick="validateForm()" name="enviar" id="enviar" class="btn btn-success btn-custom">
                        Crear cuenta
                    </button>
                </div>

                <div class="mb-3">
                    ¿Ya tienes una cuenta?
                    <a href="login.php" class="login-link">Inicia Sesión</a>
                </div>

                <div class="powered-by">
                    Powered by <img src="img/smartable.png" alt="Smartable">
                </div>
            </form>
        </div>
    </div>

    <script>
        let emailValidationTimeout;
        let isEmailValid = false;
        let isHost = <?php echo isset($_SESSION['already_host']) ? 'true' : 'false'; ?>;

        // Inicializar estado si ya es host
        if (isHost) {
            disablePasswordFields();
            document.getElementById('host-notice').style.display = 'block';
        }

        function togglePassword(inputId) {
            var input = document.getElementById(inputId);
            var icon = document.getElementById(inputId + '-icon');

            if (input.disabled) return;

            if (input.type === "password") {
                input.type = "text";
                icon.classList.remove("fa-eye-slash");
                icon.classList.add("fa-eye");
            } else {
                input.type = "password";
                icon.classList.remove("fa-eye");
                icon.classList.add("fa-eye-slash");
            }
        }

        function disablePasswordFields() {
            document.getElementById('password').disabled = true;
            document.getElementById('password2').disabled = true;
            document.getElementById('password').removeAttribute('required');
            document.getElementById('password2').removeAttribute('required');
            document.getElementById('password-requirements').style.display = 'none';
            document.getElementById('host-notice').style.display = 'block';
        }

        function enablePasswordFields() {
            document.getElementById('password').disabled = false;
            document.getElementById('password2').disabled = false;
            document.getElementById('password').setAttribute('required', 'required');
            document.getElementById('password2').setAttribute('required', 'required');
            document.getElementById('password-requirements').style.display = 'block';
            document.getElementById('host-notice').style.display = 'none';
        }

        // Validación de email en tiempo real
        document.getElementById('email').addEventListener('input', function() {
            const email = this.value.trim();
            const validationDiv = document.getElementById('email-validation');
            const spinner = document.getElementById('email-spinner');

            // Limpiar timeout anterior
            if (emailValidationTimeout) {
                clearTimeout(emailValidationTimeout);
            }

            // Limpiar validación anterior
            validationDiv.textContent = '';
            validationDiv.className = 'email-validation-status';
            isEmailValid = false;

            if (email === '') {
                spinner.style.display = 'none';
                return;
            }

            // Mostrar spinner
            spinner.style.display = 'inline-block';

            // Esperar 500ms antes de hacer la petición
            emailValidationTimeout = setTimeout(function() {
                validateEmail(email);
            }, 500);
        });

        function validateEmail(email) {
            const validationDiv = document.getElementById('email-validation');
            const spinner = document.getElementById('email-spinner');

            // Crear FormData para la petición
            const formData = new FormData();
            formData.append('validate_email', '1');
            formData.append('email', email);

            fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    spinner.style.display = 'none';

                    if (data.status === 'success') {
                        validationDiv.textContent = data.message;
                        validationDiv.className = 'email-validation-status success';
                        isEmailValid = true;

                        if (data.is_host) {
                            isHost = true;
                            disablePasswordFields();
                        } else {
                            isHost = false;
                            enablePasswordFields();
                        }
                    } else {
                        validationDiv.textContent = data.message;
                        validationDiv.className = 'email-validation-status error';
                        isEmailValid = false;
                        isHost = false;
                        enablePasswordFields();
                    }
                })
                .catch(error => {
                    spinner.style.display = 'none';
                    validationDiv.textContent = 'Error al validar el email';
                    validationDiv.className = 'email-validation-status error';
                    isEmailValid = false;
                    console.error('Error:', error);
                });
        }

        function validateForm() {
            var nombre = document.getElementById('nombre').value.trim();
            var email = document.getElementById('email').value.trim();
            var telefono = document.getElementById('telefono').value.trim();
            var password = document.getElementById('password').value;
            var password2 = document.getElementById('password2').value;
            var terms = document.getElementById('terms').checked;
            var isValid = true;
            var errorMessage = '';

            if (nombre === '' || email === '' || telefono === '') {
                errorMessage = 'Los campos nombre, email y teléfono son obligatorios';
                isValid = false;
            } else if (!isEmailValid) {
                errorMessage = 'Debes introducir un email válido y verificado';
                isValid = false;
            } else if (!isHost && (password === '' || password2 === '')) {
                errorMessage = 'Las contraseñas son obligatorias';
                isValid = false;
            } else if (!isHost && password !== password2) {
                errorMessage = 'Las contraseñas no coinciden';
                isValid = false;
            } else if (!isHost && password.length < 8) {
                errorMessage = 'La contraseña debe tener al menos 8 caracteres';
                isValid = false;
            } else if (!isHost && !password.match(/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/)) {
                errorMessage = 'La contraseña debe contener al menos una letra minúscula, una mayúscula y un número';
                isValid = false;
            } else if (!/^\S+@\S+\.\S+$/.test(email)) {
                errorMessage = 'El formato del email no es válido';
                isValid = false;
            } else if (!/^[0-9]{9}$/.test(telefono)) {
                errorMessage = 'El teléfono debe ser un número de 9 dígitos';
                isValid = false;
            } else if (!terms) {
                errorMessage = 'Debes aceptar la política de datos y cookies';
                isValid = false;
            }

            if (!isValid) {
                var existingAlert = document.querySelector('.alert-danger');
                if (existingAlert) {
                    existingAlert.textContent = errorMessage;
                } else {
                    var alertDiv = document.createElement('div');
                    alertDiv.className = 'alert alert-danger';
                    alertDiv.role = 'alert';
                    alertDiv.textContent = errorMessage;

                    var form = document.getElementById('registerForm');
                    form.insertBefore(alertDiv, form.firstChild);
                }

                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
            } else {
                var form = document.getElementById('registerForm');
                var hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.name = 'enviar';
                hiddenInput.value = '1';
                form.appendChild(hiddenInput);
                form.submit();
            }
        }
    </script>
</body>

</html>