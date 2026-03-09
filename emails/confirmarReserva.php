<?php
session_start();

require '../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

$destiny = $_GET['email'];
$id_espacio = $_GET['id'] ?? '';

// Datos de reserva — ¡puedes cambiarlos por los que uses tú!
$nombre = $_GET['nombre'] ?? '';
$establecimiento = $_GET['establecimiento'] ?? '';
$espacio = $_GET['espacio'] ?? '';
$fecha = $_GET['fecha'] ?? '';
$hora = $_GET['hora'] ?? '';
$tamano = $_GET['tamano'] ?? '3';
$id = $_SESSION['codigo_reserva'] ?? '';

if (isset($destiny)) {
    $mail = new PHPMailer(true);

    $emailBody = "
    <p>Asunto: Confirmación reserva The Nomadapp</p>

    <p>Estimado/a $nombre,</p>

    <p>Le confirmamos su reserva y esperamos su visita. A continuación, le indicamos todos los detalles:</p>

    <p>
    ID de la Reserva: <strong>$id</strong><br>
    Establecimiento: <strong>$establecimiento</strong><br>
    Espacio: <strong>$espacio</strong><br>
    Fecha: <strong>$fecha</strong><br>
    Hora: <strong>$hora</strong><br>
    Tamaño del grupo: <strong>$tamano</strong> personas</p>

    <p>¡Gracias por confiar en nosotros!</p>

    <p>Atentamente,<br>
    El equipo de The Nomadapp</p>

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

        // Cargar imágenes embebidas
        $mail->AddEmbeddedImage('../img/logo.jpg', 'logo', 'logo.jpg');
        $mail->AddEmbeddedImage('../img/facebook.png', 'facebook', 'facebook.png');
        $mail->AddEmbeddedImage('../img/twitter.png', 'twitter', 'twitter.png');
        $mail->AddEmbeddedImage('../img/linkedin.png', 'linkedin', 'linkedin.png');
        $mail->AddEmbeddedImage('../img/instagram.png', 'instagram', 'instagram.png');

        $mail->isHTML(true);
        $mail->Subject = mb_encode_mimeheader('[TheNomadapp] Confirmación de reserva', 'UTF-8');
        $mail->Body = $emailBody;

        $mail->send();
        $query_string = $_SERVER['QUERY_STRING'];
        header('Location: confirmarReserva_Anfitrion.php?' . $query_string);
    } catch (Exception $e) {
        header('Location: ../reservarEspacio-completo.php?status=error:' . $mail->ErrorInfo);
    }
} else {
    header('Location: ../reservarEspacio-completo.php?status=error:missing_parameters');
}
