<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once dirname(__DIR__) . '/vendor/autoload.php';

function enviarCorreoInvitacionGestora($emailDestino, $enlaceMagico, $empresa = '', $codigoPostal = '', $plan = '')
{
    $mail = new PHPMailer(true);

    // Si hay empresa, la mostramos. Si no, no mostramos el bloque gris.
    $bloqueEmpresa = "";
    if (!empty($empresa)) {
        $bloqueEmpresa = "
        <div style='background-color: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #e9ecef;'>
            <strong>Empresa vinculada:</strong> $empresa
        </div>";
    }

    $emailBody = "
    Hola,<br><br>
    Has recibido una invitación para completar el alta de una gestora en <strong>TheNomadapp</strong>.<br><br>
    $bloqueEmpresa
    Para terminar el registro, crear tus credenciales de acceso, configurar tu zona de trabajo inicial y elegir tu plan de suscripción, haz clic en el siguiente botón:<br><br>
    <div style='margin: 30px 0;'>
        <a href='$enlaceMagico' style='background-color: #0f4c5c; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; font-weight: bold; display: inline-block;'>Completar registro de gestora</a>
    </div>
    <em>Si el botón no funciona, copia y pega esta dirección en tu navegador:</em><br>
    <a href='$enlaceMagico' style='color: #007bff; font-size: 0.9em; word-break: break-all;'>$enlaceMagico</a><br><br>
    Este acceso caduca en 7 días. Si no esperabas este correo, puedes ignorarlo.<br><br>
    Atentamente,<br>
    Equipo de TheNomadApp<br><br>
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
        $mail->isSMTP();
        $mail->CharSet = 'UTF-8';
        $mail->Encoding = 'base64';
        $mail->Host = $_ENV['SMTP_HOST'];
        $mail->SMTPAuth = true;
        $mail->Username = $_ENV['SMTP_USERNAME'];
        $mail->Password = $_ENV['SMTP_PASSWORD'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = $_ENV['EMAIL_PORT'];

        $mail->setFrom('noreply@yonomad.app', 'TheNomadApp');
        $mail->addAddress($emailDestino);

        $baseImgDir = dirname(__DIR__) . '/img/';
        $mail->AddEmbeddedImage($baseImgDir . 'logo.jpg', 'logo', 'logo.jpg');
        $mail->AddEmbeddedImage($baseImgDir . 'facebook.png', 'facebook', 'facebook.png');
        $mail->AddEmbeddedImage($baseImgDir . 'twitter.png', 'twitter', 'twitter.png');
        $mail->AddEmbeddedImage($baseImgDir . 'linkedin.png', 'linkedin', 'linkedin.png');
        $mail->AddEmbeddedImage($baseImgDir . 'instagram.png', 'instagram', 'instagram.png');

        $mail->isHTML(true);
        $mail->Subject = '[TheNomadapp] Invitacion para registrar tu gestora';
        $mail->Body = $emailBody;

        $mail->send();
        return ['success' => true, 'message' => 'Correo enviado'];
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Error: ' . $mail->ErrorInfo];
    }
}
