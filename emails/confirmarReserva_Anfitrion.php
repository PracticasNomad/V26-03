<?php
session_start();
require '../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

$destiny = $_GET['email_anfitrion'] ?? '';
$nombreNomada = $_GET['nombre'] ?? '';
$establecimiento = $_GET['establecimiento'] ?? '';
$espacio = $_GET['espacio'] ?? '';
$fecha = $_GET['fecha'] ?? '';
$hora = $_GET['hora'] ?? '';
$tamano = $_GET['tamano'] ?? '1';
$id = $_SESSION['codigo_reserva'] ?? '';

if (!empty($destiny)) {
    $mail = new PHPMailer(true);

    $emailBody = "
    <div style='font-family: Arial, sans-serif; color: #333;'>
        <h2 style='color: #BDE742; background-color: #1f1f1f; padding: 15px; text-align: center;'>¡Nueva reserva confirmada!</h2>
        <p>Estimado/a Anfitrión,</p>
        <p>Le informamos de que un/a nómada ha realizado una reserva en su espacio a través de The Nomadapp. Detalles:</p>
        <div style='background-color: #f8f9fa; border-left: 4px solid #00B7CF; padding: 15px; margin: 20px 0;'>
            <p><strong>Nómada:</strong> $nombreNomada</p>
            <p><strong>Establecimiento:</strong> $establecimiento</p>
            <p><strong>Espacio:</strong> $espacio</p>
            <p><strong>Fecha:</strong> $fecha</p>
            <p><strong>Hora:</strong> $hora</p>
            <p><strong>Tamaño del grupo:</strong> $tamano personas</p>
        </div>
        <p>Le recomendamos tener el espacio listo y disponible para asegurar una experiencia satisfactoria.</p>
        <p>Atentamente,<br>El equipo de The Nomadapp</p>
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
    </div>";

    try {
        $mail->isSMTP();
        $mail->Host = $_ENV['SMTP_HOST'];
        $mail->SMTPAuth = true;
        $mail->Username = $_ENV['SMTP_USERNAME'];
        $mail->Password = $_ENV['SMTP_PASSWORD'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = $_ENV['EMAIL_PORT'];
        $mail->CharSet = 'UTF-8';

        $mail->setFrom('noreply@yonomad.app', 'The Nomadapp');
        $mail->addAddress($destiny);

        // Cargar imágenes embebidas
        $mail->AddEmbeddedImage('../img/logo.jpg', 'logo', 'logo.jpg');
        $mail->AddEmbeddedImage('../img/facebook.png', 'facebook', 'facebook.png');
        $mail->AddEmbeddedImage('../img/twitter.png', 'twitter', 'twitter.png');
        $mail->AddEmbeddedImage('../img/linkedin.png', 'linkedin', 'linkedin.png');
        $mail->AddEmbeddedImage('../img/instagram.png', 'instagram', 'instagram.png');

        $mail->isHTML(true);
        $mail->Subject = mb_encode_mimeheader('[TheNomadapp] Nueva reserva confirmada', 'UTF-8');
        $mail->Body = $emailBody;

        $mail->send();
        // ESTE ES EL FINAL DEL RECORRIDO. AHORA SÍ VAMOS A LA VISTA DE ÉXITO.
        header('Location: ../reservarEspacio-completo.php?status=ok');
    } catch (Exception $e) {
        header('Location: ../reservarEspacio-completo.php?status=error');
    }
} else {
    // Si no hay correo del anfitrión, saltamos a la vista de éxito igualmente
    header('Location: ../reservarEspacio-completo.php?status=ok');
    exit;
}
