<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function enviarCorreoEstablecimientoSinEspacio($destinatario, $nombreEstablecimiento, $establecimientoId)
{
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

        $mail->setFrom('noreply@yonomad.app', 'TheNomadApp');
        $mail->addAddress($destinatario);
        // Cargar imágenes embebidas
        $mail->AddEmbeddedImage('../img/logo.jpg', 'logo', 'logo.jpg');
        $mail->AddEmbeddedImage('../img/facebook.png', 'facebook', 'facebook.png');
        $mail->AddEmbeddedImage('../img/twitter.png', 'twitter', 'twitter.png');
        $mail->AddEmbeddedImage('../img/linkedin.png', 'linkedin', 'linkedin.png');
        $mail->AddEmbeddedImage('../img/instagram.png', 'instagram', 'instagram.png');

        $mail->isHTML(true);
        $mail->Subject = '[TheNomadapp] Has creado un nuevo establecimiento';
        $mail->Body = "
            Hola,<br><br>
            Has creado exitosamente el establecimiento <b>" . htmlspecialchars($nombreEstablecimiento) . "</b>.<br>
            Recuerda que para que tu establecimiento esté disponible para reservas, debes añadirle al menos un espacio de trabajo.<br><br>
            <a href='http://tu_dominio.com/anfitrion/crearEspacio.php?establecimiento_id=" . $establecimientoId . "'>Añadir espacio ahora</a><br><br>
            Atentamente,<br>
            El equipo de TheNomadApp<br><br>
            <img src='cid:logo' alt='TheNomadapp Logo' style='width: 120px; margin-top: 20px;'><br>
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

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Error al enviar correo de establecimiento: {$mail->ErrorInfo}");
        return false;
    }
}

function enviarCorreoNuevoEspacio($destinatario, $nombreEspacio)
{
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

        $mail->setFrom('noreply@yonomad.app', 'TheNomadApp');
        $mail->addAddress($destinatario);

        $mail->AddEmbeddedImage('../img/logo.jpg', 'logo', 'logo.jpg');

        $mail->isHTML(true);
        $mail->Subject = '[TheNomadapp] Nuevo espacio creado';
        $mail->Body = "
            Hola,<br><br>
            Has añadido exitosamente el espacio <b>" . htmlspecialchars($nombreEspacio) . "</b> a tu establecimiento.<br>
            Tu espacio ahora está listo para recibir reservas según los horarios que configuraste.<br><br>
            Atentamente,<br>
            El equipo de TheNomadApp<br><br>
             <img src='cid:logo' alt='TheNomadapp Logo' style='width: 120px; margin-top: 20px;'><br>
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

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Error al enviar correo de espacio: {$mail->ErrorInfo}");
        return false;
    }
}

function enviarCorreoAnfitrionSinEstablecimiento($destinatario, $nombreAnfitrion)
{
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

        $mail->setFrom('noreply@yonomad.app', 'TheNomadApp');
        $mail->addAddress($destinatario);
        
        // Cargar imágenes embebidas
        $mail->AddEmbeddedImage('../img/logo.jpg', 'logo', 'logo.jpg');
        $mail->AddEmbeddedImage('../img/facebook.png', 'facebook', 'facebook.png');
        $mail->AddEmbeddedImage('../img/twitter.png', 'twitter', 'twitter.png');
        $mail->AddEmbeddedImage('../img/linkedin.png', 'linkedin', 'linkedin.png');
        $mail->AddEmbeddedImage('../img/instagram.png', 'instagram', 'instagram.png');

        $mail->isHTML(true);
        $mail->Subject = '[TheNomadapp] Continua tu registro: Añade tu establecimiento';
        $mail->Body = "
            Hola <b>" . htmlspecialchars($nombreAnfitrion) . "</b>,<br><br>
            Vimos que empezaste a crear tu perfil de anfitrión, pero te quedaste a medias.<br>
            Para poder empezar a recibir nómadas digitales y generar ingresos, necesitamos que registres los datos de tu establecimiento.<br><br>
            Es un proceso muy rápido. Haz clic en el siguiente enlace para continuar justo donde lo dejaste:<br><br>
            <a href='http://localhost:8000/anfitrion/registerAnfitrion-paso3.php' style='display: inline-block; padding: 10px 20px; background-color: #28a745; color: white; text-decoration: none; border-radius: 5px; font-weight: bold;'>Continuar mi registro</a><br><br>
            ¡Te esperamos!<br>
            El equipo de TheNomadApp<br><br>
            <img src='cid:logo' alt='TheNomadapp Logo' style='width: 120px; margin-top: 20px;'><br>
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

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Error al enviar correo de anfitrión sin establecimiento: {$mail->ErrorInfo}");
        return false;
    }
}
?>