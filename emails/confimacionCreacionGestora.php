<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

if (!function_exists('enviarCorreoConfirmacionGestora')) {
    function enviarCorreoConfirmacionGestora($destiny, $nombreGestora = '', $nombreResponsable = '', $email = '', $zonaCobertura = '', $telefonoContacto = '')
    {
        $dotenv = Dotenv::createImmutable(dirname(__DIR__));
        $dotenv->load();

        $mail = new PHPMailer(true);
        
        $gestoraLabel = trim((string) $nombreGestora) !== '' ? htmlspecialchars((string) $nombreGestora) : 'tu gestora';
        $responsableLabel = trim((string) $nombreResponsable) !== '' ? htmlspecialchars((string) $nombreResponsable) : 'Responsable';
        $emailLabel = trim((string) $email) !== '' ? htmlspecialchars((string) $email) : 'sin especificar';
        $zonaLabel = trim((string) $zonaCobertura) !== '' ? htmlspecialchars((string) $zonaCobertura) : 'sin zona asignada';
        $telefonoLabel = trim((string) $telefonoContacto) !== '' ? htmlspecialchars((string) $telefonoContacto) : 'sin teléfono';

        $emailBody = '
        <div style="font-family: Nunito, Arial, sans-serif; color: #1f2933; line-height: 1.6;">
            <h2 style="color: #BDE742; background-color: #1f1f1f; padding: 15px; text-align: center;">¡Gestora creada exitosamente!</h2>
            <p>Hola ' . $responsableLabel . ',</p>
            <p>Te confirmamos que tu gestora ha sido registrada correctamente en TheNomadapp.</p>
            <div style="background: #f6f9fc; border: 1px solid #d8e1ea; border-radius: 14px; padding: 18px; margin: 20px 0;">
                <p style="margin: 0 0 8px;"><strong>Nombre de la gestora:</strong> ' . $gestoraLabel . '</p>
                <p style="margin: 0 0 8px;"><strong>Responsable:</strong> ' . $responsableLabel . '</p>
                <p style="margin: 0 0 8px;"><strong>Correo electrónico:</strong> ' . $emailLabel . '</p>
                <p style="margin: 0 0 8px;"><strong>Zona de cobertura:</strong> ' . $zonaLabel . '</p>
                <p style="margin: 0;"><strong>Teléfono de contacto:</strong> ' . $telefonoLabel . '</p>
            </div>
            <p>Ya puedes acceder a tu panel de administrador para empezar a gestionar tus espacios y reservas. Inicia sesión en nuestro portal con tus credenciales.</p>
            <p style="margin: 24px 0;">
                <a href="https://yonomad.app/gestor/login/" style="display: inline-block; background: linear-gradient(135deg, #0f4c5c 0%, #1a6d85 100%); color: #ffffff; text-decoration: none; padding: 14px 22px; border-radius: 999px; font-weight: 700;">Acceder al panel de gestora</a>
            </p>
            <p>Si tienes cualquier pregunta o necesitas asistencia, no dudes en contactar con nuestro equipo de soporte.</p>
            <p style="font-size: 14px; color: #66788a;">Este es un mensaje automático. Por favor, no respondas a este correo.</p>
            <p>Atentamente,<br>Equipo de TheNomadapp</p>
        </div>';

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

            $mail->setFrom('noreply@yonomad.app', 'TheNomadapp');
            $mail->addAddress($destiny);

            // Cargar imágenes embebidas si existen
            $logoPath = dirname(__DIR__) . '/img/logo.jpg';
            if (file_exists($logoPath)) {
                $mail->AddEmbeddedImage($logoPath, 'logo', 'logo.jpg');
                $emailBody .= '<div style="margin-top: 20px;"><img src="cid:logo" alt="TheNomadapp" style="width: 120px;"></div>';
            }

            $mail->isHTML(true);
            $mail->Subject = '[TheNomadapp] ¡Bienvenido! Tu gestora ha sido creada';
            $mail->Body = $emailBody;
            $mail->send();

            return [
                'success' => true,
                'message' => 'El correo de confirmación de gestora se envió correctamente.',
            ];
        } catch (Exception $exception) {
            return [
                'success' => false,
                'message' => 'No se pudo enviar el correo de confirmación: ' . $mail->ErrorInfo,
            ];
        }
    }
}
