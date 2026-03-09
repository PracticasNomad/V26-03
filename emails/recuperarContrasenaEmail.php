<?php
session_start();

require '../vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

$type = $_GET['type'] ?? '';
$destiny = $_GET['email'];
$nombre = $_GET['nombre'] ?? '';
$codigo = $_SESSION['recover_code'] ?? '';

if (isset($destiny)) {
    $mail = new PHPMailer(true);

    $emailBody = "

    <p>Hola $nombre,</p>

    <p>Por favor, usa el siguiente código para restablecer la contraseña de The Nomadapp:</p>

    <p style='font-size: 24px; font-weight: bold; letter-spacing: 3px;'>$codigo</p>

    <p>Si no solicitaste un cambio de contraseña, puedes ignorar este mensaje.</p>

    <p>Este es un mensaje automático. Por favor, no respondas a este correo.</p>

    <p>Atentamente,<br>
    El equipo de The Nomadapp</p>

    <img src='cid:logo' alt='TheNomadapp Logo' style='width: 120px; margin-top: 20px;'><br><br>

    <div style='display: flex; gap: 10px; align-items: center;'>
        <a href='https://www.facebook.com/profile.php?id=100067482289201' target='_blank'>
            <img src='cid:facebook' style='width: 50px; margin-right: 10px;'>
        </a>
        <a href='https://x.com/The_Nomadapp' target='_blank'>
            <img src='cid:twitter' style='width: 50px; margin-right: 10px;'>
        </a>
        <a href='https://www.linkedin.com/showcase/the-nomadapp/' target='_blank'>
            <img src='cid:linkedin' style='width: 50px; margin-right: 10px;'>
        </a>
        <a href='https://www.instagram.com/yonomadapp/' target='_blank'>
            <img src='cid:instagram' style='width: 50px;'>
        </a>
    </div>
    ";

    try {
        // Configuración SMTP
        $mail->isSMTP();
        $mail->Host = $_ENV['SMTP_HOST'];
        $mail->SMTPAuth = true;
        $mail->Username = $_ENV['SMTP_USERNAME'];
        $mail->CharSet = 'UTF-8';
        $mail->Encoding = 'base64';
        $mail->Password = $_ENV['SMTP_PASSWORD'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = $_ENV['EMAIL_PORT'];

        $mail->setFrom('noreply@yonomad.app', 'no-reply');
        $mail->addAddress($destiny, $nombre);

        // Embedding de imágenes
        $mail->AddEmbeddedImage('../img/logo.jpg', 'logo', 'logo.jpg');
        $mail->AddEmbeddedImage('../img/facebook.png', 'facebook', 'facebook.png');
        $mail->AddEmbeddedImage('../img/twitter.png', 'twitter', 'twitter.png');
        $mail->AddEmbeddedImage('../img/linkedin.png', 'linkedin', 'linkedin.png');
        $mail->AddEmbeddedImage('../img/instagram.png', 'instagram', 'instagram.png');

        $mail->isHTML(true);
        $mail->Subject = mb_encode_mimeheader('[TheNomadapp] Código para restablecer contraseña', 'UTF-8');
        $mail->Body = $emailBody;

        $mail->send();
        if($type == 'host'){
            header('Location: ../anfitrion/recuperar_password-Paso2.php?status=ok');
        } else {
            header('Location: ../recuperar_password-Paso2.php?status=ok');
        }
    } catch (Exception $e) {
        if ($type == 'host'){
            header('Location: ../anfitrion/recuperar_password-Paso2.php?status=error:' . $mail->ErrorInfo);
        } else {
            header('Location: ../recuperar_password-Paso2.php?status=error:' . $mail->ErrorInfo);
        }
        
    }
} else {
    if ($type == 'host') {
        header('Location: ../anfitrion/recuperar_password-Paso2.php?status=error:missing_parameters');
    } else {
            header('Location: ../recuperar_password-Paso2.php?status=error:missing_parameters');
        }
}
?>
