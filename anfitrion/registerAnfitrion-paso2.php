<?php
session_start();

// 1. INCLUIMOS NUESTRO NUEVO ARCHIVO DE CORREOS
require_once '../emails/borradorRegistro.php';

$formError = '';
$passwordError = false;
$telefonoError = false;
$emailError = false;
$nifError = false;

if (isset($_POST['siguiente'])) {
    $nombre = trim($_POST['nombre']);
    $email = trim($_POST['email']);
    $telefono = trim($_POST['telefono']);
    $razonsocial = trim($_POST['razonsocial']);
    $nif = trim($_POST['nif']);
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $password2 = isset($_POST['password2']) ? $_POST['password2'] : '';

    $errors = [];

    // Verificar si el email ya está registrado como host antes de proceder
    $emailValidado = isset($_POST['email_validado']) ? $_POST['email_validado'] : '0';
    if ($emailValidado === '0') {
        $errors[] = 'Este email ya está registrado como anfitrión. Por favor, utiliza otro email.';
        $_SESSION['already_guest'] = false;
        $emailError = true;
    } else {
        if (isset($_POST['user_type'])) {
            if ($_POST['user_type'] === 'guest') {
                $_SESSION['already_guest'] = true;
            } else {
                $_SESSION['already_guest'] = false;
            }
        } else {
            $_SESSION['already_guest'] = false;
        }
    }

    if (!isset($_SESSION['already_guest']) || !$_SESSION['already_guest']) {
        if ($password !== $password2) {
            $errors[] = 'Las contraseñas no coinciden. Por favor, inténtalo de nuevo.';
            $passwordError = true;
        }
    }

    if (strlen($telefono) != 9 || !preg_match('/^[0-9]{9}$/', $telefono)) {
        $errors[] = 'El teléfono debe contener exactamente 9 dígitos numéricos.';
        $telefonoError = true;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'El formato de email no es válido.';
        $emailError = true;
    }

    // Validación del NIF 8 números 1 letra
    if (!preg_match('/^[0-9]{8}[A-Z]$/i', $nif)){
        $errors[] = 'El formato de NIF no es válido.';
        $nifError = true;
    }

    if (!empty($errors)) {
        $formError = implode(' ', $errors);
    } else {
        $_SESSION['host']['nombre'] = $nombre;
        $_SESSION['host']['email'] = $email;
        $_SESSION['host']['telefono'] = $telefono;
        $_SESSION['host']['razonsocial'] = $razonsocial;
        $_SESSION['host']['nif'] = $nif;

        if (!isset($_SESSION['already_guest']) || !$_SESSION['already_guest']) {
            $_SESSION['host']['password'] = $password;
            $_SESSION['host']['password2'] = $password2;
        }

        if (isset($_SESSION['host'])) {
            require_once '../vendor/autoload.php';
            $dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
            $dotenv->safeLoad();

            $tokenResumen = bin2hex(random_bytes(16));

            $url = 'http://' . $_ENV['SERVER_IP'] . ':' . $_ENV['DATABASE_PORT'] . '/rest/v1/registros_abandonados';
            $ch = curl_init($url);
            $data = [
                'email' => $email,
                'nombre' => $nombre,
                'token' => $tokenResumen,
                'paso' => 3,
                'datos_sesion' => json_encode($_SESSION)
            ];

            $payload = json_encode($data);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'apikey: ' . $_ENV['DATABASE_APIKEY'],
                'Prefer: resolution=merge-duplicates'
            ]);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
            curl_exec($ch);
            curl_close($ch);

            // AUTO-GENERADOR DE URL PARA EL EMAIL
            $protocolo = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
            $dominio = $_SERVER['HTTP_HOST'];
            $carpeta = rtrim(dirname($_SERVER['REQUEST_URI']), '/');
            $enlaceMagico = $protocolo . $dominio . $carpeta . "/resumeRegistro.php?token=" . $tokenResumen;

            // 2. ¡ENVIAMOS EL CORREO REAL! 📨
            enviarCorreoBorrador($email, $nombre, $enlaceMagico);

            header('Location: registerAnfitrion-paso3.php');
            exit();
        } else {
            $formError = 'Error al guardar los datos de sesión. Por favor, inténtalo de nuevo.';
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://kit.fontawesome.com/b8814a2854.js" crossorigin="anonymous"></script>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;600;700&display=swap" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="icon" href="../favicon-color.png">
    <link rel="icon" href="../favicon-negro.png" media="(prefers-color-scheme: light)">
    <link rel="icon" href="../favicon-color.png" media="(prefers-color-scheme: dark)">
    <title>Crea tu perfil de anfitrión</title>
    <style>
        body {
            font-family: 'Nunito', sans-serif;
            background-color: #f8f9fa;
        }

        .contenedorAlta {
            max-width: 700px;
            margin: 2rem auto;
            background-color: white;
            border-radius: 15px;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            padding: 1rem;
        }

        .form-control {
            border-radius: 10px;
            padding: 0.75rem;
            border: 1px solid #ced4da;
            transition: border-color 0.3s;
        }

        .form-control:focus {
            border-color: #80bdff;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
        }

        .btn-success {
            background-color: #28a745;
            border: none;
            font-weight: 600;
            padding: 0.75rem 2rem;
        }

        .btn-cancel {
            background-color: #f8f9fa;
            border: 1px solid #ced4da;
            color: #6c757d;
            font-weight: 600;
            padding: 0.75rem 2rem;
        }

        .password-container {
            position: relative;
        }

        .no-see {
            position: absolute;
            right: 15px;
            top: 12px;
            cursor: pointer;
            color: #6c757d;
        }

        .progress-container {
            width: 100%;
            height: 5px;
            background-color: #e9ecef;
            border-radius: 3px;
            overflow: hidden;
            margin: 1rem 0;
        }

        .progress-bar {
            height: 100%;
            width: 20%;
            background-color: #28a745;
        }

        .alert {
            border-radius: 10px;
            padding: 0.75rem;
            margin-bottom: 1rem;
            display: none;
        }

        .alert-info {
            background-color: #d1ecf1;
            border-color: #bee5eb;
            color: #0c5460;
        }

        .logo-container {
            background-color: #f8f9fa;
            border-radius: 50%;
            width: 120px;
            height: 120px;
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 0 auto;
        }

        .spinner-border {
            display: none;
            width: 1rem;
            height: 1rem;
            margin-left: 10px;
        }

        .tooltip-container {
            position: relative;
            display: inline-block;
        }

        .tooltip-text {
            visibility: hidden;
            opacity: 0;
            width: 500px;
            background-color: #333;
            color: #fff;
            text-align: left;
            border-radius: 8px;
            padding: 12px 16px;
            position: absolute;
            z-index: 1000;
            top: 150%;
            left: 50%;
            transform: translateX(-50%);
            transition: opacity 0.3s;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            font-size: 14px;
            line-height: 1.5;
            font-weight: normal;
        }

        .tooltip-text::after {
            content: "";
            position: absolute;
            bottom: 100%;
            left: 50%;
            margin-left: -10px;
            border-width: 10px;
            border-style: solid;
            border-color: transparent transparent #333 transparent;
        }

        .tooltip-text.visible {
            visibility: visible;
            opacity: 1;
        }

        #imgInfo {
            cursor: pointer;
            transition: transform 0.2s;
            margin-left: 5px;
        }

        #imgInfo:hover {
            transform: scale(1.1);
        }

        .tooltip-container:hover .tooltip-text {
            visibility: visible;
            opacity: 1;
        }

        .password-disabled {
            background-color: #f8f9fa !important;
            opacity: 0.65;
        }

        @media (max-width: 768px) {
            .tooltip-text {
                width: 350px;
                font-size: 13px;
            }

            .register-title {
                display: block;
                margin-bottom: 8px;
            }

            .info-icon-mobile {
                display: block;
                margin: 8px auto 0;
                text-align: center;
            }

            .tooltip-container.mobile {
                display: block;
                text-align: center;
            }

            .tooltip-text::after {
                left: 50%;
            }
        }
    </style>
</head>

<body>
    <div class="contenedorAlta">
        <div class="col-12 text-center py-3 fw-bold h4">
            <div class="d-none d-md-block">
                <p>Registrate como anfitrión
                    <span class="tooltip-container">
                        <img src="../img/informacion.png" alt="Información" id="imgInfo" width="24px" height="24px">
                        <span id="masInfo" class="tooltip-text">Un anfitrión es la persona o negocio que publica y gestiona uno o varios establecimientos. Como anfitrión, puedes ofrecer espacios de trabajo a nómadas digitales, establecer precios y disponibilidad, así como recibir reservas directamente desde la app.</span>
                    </span>
                </p>
            </div>
            <div class="d-block d-md-none">
                <p class="register-title">Registrate como anfitrión</p>
                <span class="tooltip-container mobile">
                    <img src="../img/informacion.png" alt="Información" id="imgInfoMobile" width="24px" height="24px">
                    <span id="masInfoMobile" class="tooltip-text">Un anfitrión es la persona o negocio que publica y gestiona uno o varios establecimientos. Como anfitrión, puedes ofrecer espacios de trabajo a nómadas digitales, establecer precios y disponibilidad, así como recibir reservas directamente desde la app.</span>
                </span>
            </div>
        </div>

        <div class="col-12 text-center mb-3">
            <div class="logo-container">
                <img src="../img/antena.png" width="80" alt="Logo">
            </div>
        </div>

        <div class="col-12 text-center h4 mb-4 fw-bold">
            Crear cuenta
        </div>

        <div class="alert alert-danger" id="error-message" <?php echo !empty($formError) ? 'style="display:block"' : ''; ?>>
            <i class="fas fa-exclamation-circle me-2"></i> <span id="error-text"><?php echo $formError; ?></span>
        </div>

        <div class="alert alert-info" id="user-exists-message" style="display: <?php echo (isset($_SESSION['already_guest']) && $_SESSION['already_guest']) ? 'block' : 'none'; ?>;">
            <i class="fas fa-info-circle me-2"></i> <span id="user-exists-text">Ya tienes una cuenta como usuario con este email. No necesitas crear una nueva contraseña.</span>
        </div>

        <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="container" id="registerForm">
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="input_nombre" class="form-label fw-bold">Tu nombre *</label>
                    <input type="text" class="form-control" id="input_nombre" name="nombre" required placeholder="Tu nombre"
                        value="<?php echo isset($_SESSION['host']['nombre']) ? htmlspecialchars($_SESSION['host']['nombre']) : ''; ?>">
                </div>

                <div class="col-md-6">
                    <label for="input_email" class="form-label fw-bold">Tu Email *
                        <span class="spinner-border text-primary" id="email-loading" role="status">
                            <span class="visually-hidden">Verificando...</span>
                        </span>
                    </label>
                    <input type="email" class="form-control <?php echo $emailError ? 'is-invalid' : ''; ?>"
                        id="input_email" name="email" required placeholder="ejemplo@correo.com"
                        value="<?php echo isset($_SESSION['host']['email']) ? htmlspecialchars($_SESSION['host']['email']) : ''; ?>">
                    <div class="invalid-feedback" id="email-feedback">Este email ya está registrado. Por favor, utiliza otro.</div>
                </div>

                <div class="col-md-6">
                    <label for="input_telefono" class="form-label fw-bold">Teléfono *</label>
                    <input type="tel" class="form-control <?php echo $telefonoError ? 'is-invalid' : ''; ?>"
                        id="input_telefono" name="telefono" required placeholder="Ejemplo: 612345678"
                        value="<?php echo isset($_SESSION['host']['telefono']) ? htmlspecialchars($_SESSION['host']['telefono']) : ''; ?>"
                        pattern="[0-9]{9}" title="Debe contener 9 dígitos">
                    <?php if ($telefonoError): ?>
                        <div class="invalid-feedback">El teléfono debe contener 9 dígitos numéricos.</div>
                    <?php endif; ?>
                </div>

                <div class="col-md-6">
                    <label for="input_nif" class="form-label fw-bold">NIF/CIF *</label>
                    <input type="text" class="form-control" id="input_nif" name="nif" required placeholder="Ejemplo: 12345678A"
                        value="<?php echo isset($_SESSION['host']['nif']) ? htmlspecialchars($_SESSION['host']['nif']) : ''; ?>" 
                        oninput="this.value = this.value.toUpperCase()" pattern="^[0-9]{8}[A-Za-z]$" maxlength="9"> 
                </div>  

                <div class="col-md-6" style="margin-right: 5px;">
                    <label for="input_razonsocial" class="form-label fw-bold">Razón Social *</label>
                    <input type="text" class="form-control" id="input_razonsocial" name="razonsocial" required placeholder="Razon Social"
                        value="<?php echo isset($_SESSION['host']['razonsocial']) ? htmlspecialchars($_SESSION['host']['razonsocial']) : ''; ?>">
                </div>

                <div class="col-md-6 password-container">
                    <label for="password" class="form-label fw-bold">Contraseña *</label>
                    <input minlength="8" type="password" pattern="^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$"
                        class="form-control <?php echo $passwordError ? 'is-invalid' : ''; ?> <?php echo (isset($_SESSION['already_guest']) && $_SESSION['already_guest']) ? 'password-disabled' : ''; ?>"
                        name="password" id="password"
                        <?php echo (isset($_SESSION['already_guest']) && $_SESSION['already_guest']) ? 'disabled' : 'required'; ?>
                        placeholder="<?php echo (isset($_SESSION['already_guest']) && $_SESSION['already_guest']) ? 'No necesario - ya tienes cuenta' : 'Una mayúscula, minúscula y número'; ?>"
                        title="La contraseña debe contener al menos una letra mayúscula, una letra minúscula y un número."
                        value="<?php echo (isset($_SESSION['already_guest']) && $_SESSION['already_guest']) ? '' : (isset($_SESSION['host']['password']) ? htmlspecialchars($_SESSION['host']['password']) : ''); ?>">
                    <span class="no-see"><i class="fas fa-eye-slash" onclick="mostrarContrasena('password')"></i></span>
                </div>

                <div class="col-md-6 password-container">
                    <label for="password2" class="form-label fw-bold">Confirmar contraseña *</label>
                    <input type="password" pattern="^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$"
                        class="form-control <?php echo $passwordError ? 'is-invalid' : ''; ?> <?php echo (isset($_SESSION['already_guest']) && $_SESSION['already_guest']) ? 'password-disabled' : ''; ?>"
                        id="password2" name="password2"
                        <?php echo (isset($_SESSION['already_guest']) && $_SESSION['already_guest']) ? 'disabled' : 'required'; ?>
                        placeholder="<?php echo (isset($_SESSION['already_guest']) && $_SESSION['already_guest']) ? 'No necesario - ya tienes cuenta' : 'Repite la contraseña'; ?>"
                        value="<?php echo (isset($_SESSION['already_guest']) && $_SESSION['already_guest']) ? '' : (isset($_SESSION['host']['password2']) ? htmlspecialchars($_SESSION['host']['password2']) : ''); ?>">
                    <span class="no-see"><i class="fas fa-eye-slash" onclick="mostrarContrasena('password2')"></i></span>
                    <?php if ($passwordError): ?>
                        <div class="invalid-feedback">Las contraseñas no coinciden.</div>
                    <?php endif; ?>
                </div>

                <div class="col-12 mt-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="politicas" name="politicas" required
                            <?php echo isset($_POST['politicas']) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="politicas">
                            Acepto la <a href="https://yonomad.app/aviso-legal/" target="_blank">Política de datos</a> y
                            <a href="https://yonomad.app/politica-de-cookies/" target="_blank">Política de Cookies</a>
                        </label>
                    </div>
                </div>
            </div>

            <div class="progress-container mt-4">
                <div class="progress-bar"></div>
            </div>

            <div class="container mt-4">
                <div class="row">
                    <div class="col-6 text-end">
                        <button class="btn btn-cancel rounded-pill" type="button" onclick="location.href='registerAnfitrion-paso1.php'">Anterior</button>
                    </div>
                    <div class="col-6">
                        <button type="submit" name="siguiente" id="btnSiguiente" class="btn btn-success rounded-pill">Siguiente</button>
                    </div>
                </div>
            </div>

            <input type="hidden" id="email_validado" name="email_validado" value="1">
            <input type="hidden" id="user_type" name="user_type" value="">
        </form>

        <div class="container-fluid p-3">
            <div class="row text-center">
                <div class="col-12">Paso 2 de 6</div>
            </div>
        </div>
    </div>

    <script>
        let emailTimeout;

        document.addEventListener('DOMContentLoaded', function() {
            const tooltip = document.getElementById('masInfo');
            const infoIcon = document.getElementById('imgInfo');
            let infoVisible = false;

            if (tooltip && infoIcon) {
                infoIcon.addEventListener('mouseenter', function() {
                    tooltip.classList.add('visible');
                });

                infoIcon.addEventListener('mouseleave', function() {
                    if (!infoVisible) {
                        tooltip.classList.remove('visible');
                    }
                });

                infoIcon.addEventListener('click', function(e) {
                    e.stopPropagation();
                    infoVisible = !infoVisible;

                    if (infoVisible) {
                        tooltip.classList.add('visible');
                    } else {
                        tooltip.classList.remove('visible');
                    }
                });
            }

            const tooltipMobile = document.getElementById('masInfoMobile');
            const infoIconMobile = document.getElementById('imgInfoMobile');
            let infoVisibleMobile = false;

            if (tooltipMobile && infoIconMobile) {
                infoIconMobile.addEventListener('click', function(e) {
                    e.stopPropagation();
                    infoVisibleMobile = !infoVisibleMobile;

                    if (infoVisibleMobile) {
                        tooltipMobile.classList.add('visible');
                    } else {
                        tooltipMobile.classList.remove('visible');
                    }
                });
            }

            document.addEventListener('click', function() {
                if (infoVisible && tooltip) {
                    tooltip.classList.remove('visible');
                    infoVisible = false;
                }

                if (infoVisibleMobile && tooltipMobile) {
                    tooltipMobile.classList.remove('visible');
                    infoVisibleMobile = false;
                }
            });
        });

        function mostrarContrasena(id) {
            var tipo = document.getElementById(id);
            var icon = event.target;

            if (tipo.type == "password") {
                tipo.type = "text";
                icon.classList.remove("fa-eye-slash");
                icon.classList.add("fa-eye");
            } else {
                tipo.type = "password";
                icon.classList.remove("fa-eye");
                icon.classList.add("fa-eye-slash");
            }
        }

        function disablePasswordFields() {
            const password = document.getElementById('password');
            const password2 = document.getElementById('password2');

            password.disabled = true;
            password2.disabled = true;
            password.required = false;
            password2.required = false;
            password.classList.add('password-disabled');
            password2.classList.add('password-disabled');
            password.value = '';
            password2.value = '';
            password.placeholder = 'No necesario - ya tienes cuenta';
            password2.placeholder = 'No necesario - ya tienes cuenta';
        }

        function enablePasswordFields() {
            const password = document.getElementById('password');
            const password2 = document.getElementById('password2');

            password.disabled = false;
            password2.disabled = false;
            password.required = true;
            password2.required = true;
            password.classList.remove('password-disabled');
            password2.classList.remove('password-disabled');
            password.placeholder = 'Una mayúscula, minúscula y número';
            password2.placeholder = 'Repite la contraseña';
        }

        document.getElementById('input_telefono').addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9]/g, '');
            if (this.value.length > 9) {
                this.value = this.value.slice(0, 9);
            }
        });

        async function verificarEmail(email) {
            document.getElementById('email-loading').style.display = 'inline-block';

            try {
                const response = await fetch('comprobarEmail.php?email=' + encodeURIComponent(email));
                const data = await response.json();

                const emailInput = document.getElementById('input_email');
                const emailFeedback = document.getElementById('email-feedback');
                const userExistsMessage = document.getElementById('user-exists-message');

                if (data.status === 'host_exists') {
                    emailInput.classList.remove('is-valid');
                    emailInput.classList.add('is-invalid');
                    emailFeedback.textContent = data.message;
                    userExistsMessage.style.display = 'none';
                    enablePasswordFields();
                    document.getElementById('email_validado').value = "0";
                    document.getElementById('user_type').value = "host";
                    return false;
                } else if (data.status === 'user_exists') {
                    emailInput.classList.remove('is-invalid');
                    emailInput.classList.add('is-valid');
                    userExistsMessage.style.display = 'block';
                    disablePasswordFields();

                    fetch('setUserSession.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            already_guest: true,
                            id_user: data.user_id
                        })
                    });

                    document.getElementById('email_validado').value = "1";
                    document.getElementById('user_type').value = "guest";
                    return true;
                } else if (data.status === 'available') {
                    emailInput.classList.remove('is-invalid');
                    emailInput.classList.add('is-valid');
                    userExistsMessage.style.display = 'none';
                    enablePasswordFields();
                    document.getElementById('email_validado').value = "1";
                    document.getElementById('user_type').value = "new";
                    return true;
                }
            } catch (error) {
                console.error('Error al verificar el email:', error);
            } finally {
                document.getElementById('email-loading').style.display = 'none';
            }
        }

        document.getElementById('input_email').addEventListener('input', function() {
            const email = this.value.trim();
            clearTimeout(emailTimeout);
            if (!email) return;
            emailTimeout = setTimeout(() => {
                verificarEmail(email);
            }, 500);
        });
    </script>
</body>

</html>