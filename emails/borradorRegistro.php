<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Cargamos el autoload por si este archivo se llama de forma independiente
require_once dirname(__DIR__) . '/vendor/autoload.php';

function enviarCorreoBorrador($emailDestino, $nombreAnfitrion, $enlaceMagico)
{
    $mail = new PHPMailer(true);

    $emailBody = "
    Hola $nombreAnfitrion,<br><br>
    Hemos notado que has dejado a medias el registro de tu establecimiento en <strong>TheNomadapp</strong>.<br><br>
    No te preocupes, hemos guardado tus progresos de forma segura. Cuando estés listo para continuar, solo tienes que hacer clic en el siguiente enlace:<br><br>
    🔗 <a href='$enlaceMagico' style='color: #007bff; font-weight: bold; text-decoration: none;'>Continuar mi registro</a><br><br>
    <em>Si el enlace anterior no funciona, copia y pega esta dirección en tu navegador:</em><br>
    <a href='$enlaceMagico' style='color: #6c757d; font-size: 0.9em; word-break: break-all;'>$enlaceMagico</a><br><br>
    Si no solicitaste este registro o ya lo has completado, puedes ignorar este mensaje.<br><br>
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
        // --- CONFIGURACIÓN DEL SERVIDOR SMTP ---
        $mail->isSMTP();
        $mail->CharSet = 'UTF-8';
        $mail->Encoding = 'base64';
        $mail->Host = $_ENV['SMTP_HOST'];
        $mail->SMTPAuth = true;
        $mail->Username = $_ENV['SMTP_USERNAME'];
        $mail->Password = $_ENV['SMTP_PASSWORD'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = $_ENV['EMAIL_PORT'];

        // --- REMITENTE Y DESTINATARIO ---
        $mail->setFrom('noreply@yonomad.app', 'TheNomadApp');
        $mail->addAddress($emailDestino, $nombreAnfitrion);

        // --- CARGAR IMÁGENES EMBEBIDAS (Usamos dirname para que siempre encuentre la ruta) ---
        $baseImgDir = dirname(__DIR__) . '/img/';
        $mail->AddEmbeddedImage($baseImgDir . 'logo.jpg', 'logo', 'logo.jpg');
        $mail->AddEmbeddedImage($baseImgDir . 'facebook.png', 'facebook', 'facebook.png');
        $mail->AddEmbeddedImage($baseImgDir . 'twitter.png', 'twitter', 'twitter.png');
        $mail->AddEmbeddedImage($baseImgDir . 'linkedin.png', 'linkedin', 'linkedin.png');
        $mail->AddEmbeddedImage($baseImgDir . 'instagram.png', 'instagram', 'instagram.png');

        // --- CONTENIDO DEL CORREO ---
        $mail->isHTML(true);
        $mail->Subject = '[TheNomadapp] Continúa tu registro de Anfitrión 🚀';
        $mail->Body = $emailBody;

        $mail->send();
        return ['success' => true, 'message' => 'Correo enviado'];
    } catch (Exception $e) {
        // Devolvemos el error por si necesitamos hacer debug en el futuro
        return ['success' => false, 'message' => 'Error: ' . $mail->ErrorInfo];
    }
}
