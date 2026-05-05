<?php

session_start();

require '../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

$destiny = $_GET['email'];
$nombre = $_GET['nombre'] ?? ''; 

if (isset($destiny) && isset($_SESSION['verification_code'])) {
    $mail = new PHPMailer(true);
    $emailBody = "
    Hola,<br><br>
    Gracias por registrarte.<br>
    Para completar tu registro, por favor utiliza el siguiente código de verificación:<br><br>
    🔐 Código de verificación: " . $_SESSION['verification_code'] . "<br><br>
    Este código es válido por un tiempo limitado. Si no solicitaste este registro, puedes ignorar este mensaje.<br><br>
    Este es un mensaje automático. Por favor, no respondas a este correo.<br><br>
    Atentamente,<br>
    El equipo de TheNomadApp<br><br>
    <img src='cid:logo' alt='TheNomadapp Logo' style='width: 120px; margin-top: 20px;'><br><br>

    <div style='display: flex; gap: 10px; align-items: center;'>
        <a href='https://www.facebook.com/profile.php?id=100067482289201' target='_blank'>
            <img src='cid:facebook' style='width: 50px; margin-right: 10px;'>
        </a>
        <a href='https://x.com/The_Nomadapp' target='_blank'>
            <img src='cid:twitter' style='width: 50px; margin-right: 10px'>
        </a>
        <a href='https://www.linkedin.com/showcase/the-nomadapp/' target='_blank'>
            <img src='cid:linkedin' style='width: 50px; margin-right: 10px'>
        </a>
        <a href='https://www.instagram.com/yonomadapp/' target='_blank'>
            <img src='cid:instagram' style='width: 50px;'>
        </a>
    </div>
    ";

    try {
        // Configuración del servidor SMTP
        $mail->isSMTP();
        $mail->CharSet = 'UTF-8';
        $mail->Encoding = 'base64';
        $mail->Host = $_ENV['SMTP_HOST'];
        $mail->SMTPAuth = true;
        $mail->Username = $_ENV['SMTP_USERNAME'];
        $mail->Password = $_ENV['SMTP_PASSWORD'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = $_ENV['EMAIL_PORT'];

        $mail->setFrom('noreply@yonomad.app', 'no-reply');
        $mail->addAddress($destiny, $nombre);
        // Cargar imágenes embebidas
        $mail->AddEmbeddedImage('../img/logo.jpg', 'logo', 'logo.jpg');
        $mail->AddEmbeddedImage('../img/facebook.png', 'facebook', 'facebook.png');
        $mail->AddEmbeddedImage('../img/twitter.png', 'twitter', 'twitter.png');
        $mail->AddEmbeddedImage('../img/linkedin.png', 'linkedin', 'linkedin.png');
        $mail->AddEmbeddedImage('../img/instagram.png', 'instagram', 'instagram.png');


        $mail->isHTML(true);
        $mail->Subject = '[TheNomadapp] Por favor, verifica tu cuenta';
        $mail->Body = $emailBody;

        $mail->send();
        header('Location: ../anfitrion/registerAnfitrion-pasoVerificar.php?status=ok');
    } catch (Exception $e) {
        header('Location: ../anfitrion/registerAnfitrion-pasoVerificar.php?status=error:' . $mail->ErrorInfo);
    }
} else {
    header('Location: ../anfitrion/registerAnfitrion-pasoVerificar.php?status=error:missing_parameters');
}
