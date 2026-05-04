<?php
session_start();

// 1. CARGAMOS DEPENDENCIAS
$autoloadPath = __DIR__ . '/vendor/autoload.php';
if (file_exists($autoloadPath)) {
    require_once $autoloadPath;
    
    if (class_exists('Dotenv\Dotenv') && file_exists(__DIR__ . '/.env')) {
        $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
        $dotenv->load();
    }
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// 2. CONFIGURACIÓN DEL CORREO
$correo_admin = $_ENV['SUPPORT_EMAIL'] ?? "desarrolloweb@yonomad.app"; 

$mensaje_alerta = '';
$tipo_alerta = '';
$enviado_exitoso = false; // Chivato para la redirección

// 3. AUTORRELLENAR DATOS
$nombre_usuario = $_SESSION['nombre'] ?? $_SESSION['name'] ?? '';
$email_usuario = $_SESSION['email'] ?? '';

$asunto_get = $_GET['asunto'] ?? '';

$traduccionesAsuntos = [
    'BajarPlanGestorBasico' => 'Solicitud de bajada a Plan Básico (Gestora)',
    'BajarPlanGestorPro' => 'Solicitud de bajada a Plan Pro (Gestora)',
    'BajarPlanGestorPremium' => 'Solicitud de bajada a Plan Premium (Gestora)',
    'CancelarPlanPremiumAnfitrion' => 'Solicitud para cancelar plan Premium (Anfitrión)'
];
$asunto_mostrar = $traduccionesAsuntos[$asunto_get] ?? $asunto_get;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $asunto = trim($_POST['asunto'] ?? '');
    $mensaje = trim($_POST['mensaje'] ?? '');

    if (!empty($nombre) && !empty($email) && !empty($asunto) && !empty($mensaje)) {
        
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->CharSet = 'UTF-8';
            $mail->Host = $_ENV['SMTP_HOST'];
            $mail->SMTPAuth = true;
            $mail->Username = $_ENV['SMTP_USERNAME'];
            $mail->Password = $_ENV['SMTP_PASSWORD'];
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = $_ENV['EMAIL_PORT'];

            $mail->setFrom('noreply@yonomad.app', 'TheNomadApp Web'); 
            $mail->addAddress($correo_admin); 
            $mail->addReplyTo($email, $nombre); 

            $mail->isHTML(false);
            $mail->Subject = "Soporte TheNomadApp: " . $asunto;
            $mail->Body = "Has recibido un nuevo mensaje de contacto:\n\n👤 Remitente: $nombre\n📧 Email: $email\n📌 Asunto: $asunto\n\n💬 MENSAJE:\n$mensaje";

            $mail->send();
            
            $mensaje_alerta = "Tu mensaje ha sido enviado correctamente. Redirigiéndote...";
            $tipo_alerta = "success";
            $enviado_exitoso = true; // Activamos la redirección
            
        } catch (Exception $e) {
            $mensaje_alerta = "Error al enviar el correo. Por favor, inténtalo más tarde.";
            $tipo_alerta = "danger";
        }
    } else {
        $mensaje_alerta = "Por favor, rellena todos los campos obligatorios.";
        $tipo_alerta = "warning";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contacto y Soporte - TheNomadApp</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://kit.fontawesome.com/b8814a2854.js" crossorigin="anonymous"></script>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="icon" href="favicon-color.png">
    <style>
        body { font-family: 'Nunito', sans-serif; background: linear-gradient(135deg, #f5f7fa 0%, #e4e9f2 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 2rem 15px; }
        .contact-container { max-width: 900px; width: 100%; background: white; border-radius: 20px; box-shadow: 0 15px 35px rgba(0,0,0,0.1); overflow: hidden; display: flex; flex-wrap: wrap; }
        .contact-info { background: linear-gradient(135deg, #123b49 0%, #0f4c5c 100%); color: white; padding: 40px; flex: 1; min-width: 300px; display: flex; flex-direction: column; justify-content: space-between; }
        .contact-info h3 { font-weight: 800; margin-bottom: 20px; }
        .info-item { display: flex; align-items: center; margin-top: 30px; gap: 20px; }
        .info-item i { background: rgba(255,255,255,0.15); width: 45px; height: 45px; display: flex; align-items: center; justify-content: center; border-radius: 50%; font-size: 1.3rem; flex-shrink: 0; }
        .contact-form { padding: 40px; flex: 2; min-width: 300px; background: white; }
        .form-control { border-radius: 10px; padding: 12px 15px; border: 1px solid #e2e8f0; background-color: #f8fafc; }
        .btn-submit { background-color: #0f4c5c; color: white; border: none; padding: 12px 30px; border-radius: 10px; font-weight: 700; width: 100%; transition: all 0.3s; }
        .btn-submit:hover { background-color: #123b49; transform: translateY(-2px); box-shadow: 0 8px 15px rgba(15, 76, 92, 0.2); }
        @media (max-width: 768px) { .contact-container { flex-direction: column; } .contact-info, .contact-form { padding: 30px; } }
    </style>
</head>
<body>

    <div class="contact-container">
        <div class="contact-info">
            <div>
                <a href="javascript:history.back()" class="text-white text-decoration-none mb-4 d-inline-block opacity-75 hover-opacity-100">
                    <i class="fas fa-arrow-left me-2"></i> Volver atrás
                </a>
                <h3>¿Necesitas ayuda?</h3>
                <p>Si deseas cambiar tu plan de suscripción, reportar un problema o resolver cualquier duda, nuestro equipo de soporte está aquí para ayudarte.</p>
                
                <div class="info-item">
                    <i class="fas fa-envelope"></i>
                    <div>
                        <small class="d-block opacity-75">Correo electrónico</small>
                        <strong style="word-break: break-all;"><?php echo htmlspecialchars($correo_admin); ?></strong>
                    </div>
                </div>

                <div class="info-item">
                    <i class="fas fa-clock"></i>
                    <div>
                        <small class="d-block opacity-75">Atención al cliente</small>
                        <strong>Lun - Vie (9:00 - 18:00)</strong>
                    </div>
                </div>
            </div>

            <div class="mt-5 text-center">
                <img src="favicon-negro.png" width="45" alt="Logo TheNomadApp" style="filter: brightness(0) invert(1); opacity: 0.8;">
            </div>
        </div>

        <div class="contact-form">
            <h4 class="fw-bold mb-4" style="color: #2d3748;">Envíanos un mensaje</h4>

            <?php if (!empty($mensaje_alerta)): ?>
                <div class="alert alert-<?php echo $tipo_alerta; ?> alert-dismissible fade show" role="alert">
                    <i class="fas <?php echo $tipo_alerta === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?> me-2"></i>
                    <?php echo htmlspecialchars($mensaje_alerta); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <form method="POST" action="contactanos.php">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-600">Tu Nombre</label>
                        <input type="text" class="form-control" name="nombre" value="<?php echo htmlspecialchars($nombre_usuario); ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-600">Email de Contacto</label>
                        <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($email_usuario); ?>" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-600">Asunto</label>
                    <input type="text" class="form-control" name="asunto" value="<?php echo htmlspecialchars($asunto_mostrar); ?>" required>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-600">Mensaje</label>
                    <textarea class="form-control" name="mensaje" rows="5" placeholder="Escribe aquí los detalles..." required></textarea>
                </div>

                <button type="submit" class="btn-submit">
                    <i class="fas fa-paper-plane me-2"></i> Enviar Mensaje
                </button>
            </form>
        </div>
    </div>

    <?php if ($enviado_exitoso): ?>
    <script>
        // Si el envío fue bien, esperamos 3 segundos y volvemos atrás
        setTimeout(function() {
            window.history.go(-2); // Redirige a la página de suscripciones
        }, 3000);
    </script>
    <?php endif; ?>

</body>
</html>