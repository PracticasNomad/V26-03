<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

if (!function_exists('enviarCorreoInvitacionGestora')) {
    function enviarCorreoInvitacionGestora($destiny, $inviteLink, $empresa = '', $codigoPostal = '', $plan = 'Basico')
    {
        $dotenv = Dotenv::createImmutable(dirname(__DIR__));
        $dotenv->load();

        $mail = new PHPMailer(true);
        $empresaLabel = trim((string) $empresa) !== '' ? htmlspecialchars((string) $empresa) : 'tu nueva gestora';
        $codigoPostalLabel = trim((string) $codigoPostal) !== '' ? htmlspecialchars((string) $codigoPostal) : 'sin zona asignada';
        $planLabel = htmlspecialchars((string) $plan);

        $emailBody = '
        <div style="font-family: Nunito, Arial, sans-serif; color: #1f2933; line-height: 1.6;">
            {{ICONO_EMPRESA}}
            <p>Hola,</p>
            <p>Has recibido una invitacion para completar el alta de una gestora en TheNomadapp.</p>
            <div style="background: #f6f9fc; border: 1px solid #d8e1ea; border-radius: 14px; padding: 18px; margin: 20px 0;">
                <p style="margin: 0 0 8px;"><strong>Empresa:</strong> ' . $empresaLabel . '</p>
                <p style="margin: 0 0 8px;"><strong>Zona inicial:</strong> ' . $codigoPostalLabel . '</p>
                <p style="margin: 0;"><strong>Plan sugerido:</strong> ' . $planLabel . '</p>
            </div>
            <p>Utiliza este enlace para terminar el registro y crear tus credenciales:</p>
            <p style="margin: 24px 0;">
                <a href="' . htmlspecialchars($inviteLink) . '" style="display: inline-block; background: linear-gradient(135deg, #0f4c5c 0%, #1a6d85 100%); color: #ffffff; text-decoration: none; padding: 14px 22px; border-radius: 999px; font-weight: 700;">Completar registro de gestora</a>
            </p>
            <p style="font-size: 14px; color: #66788a;">Si el boton no funciona, copia y pega este enlace en tu navegador:<br>' . htmlspecialchars($inviteLink) . '</p>
            <p style="font-size: 14px; color: #66788a;">Este acceso caduca en 7 dias. Si no esperabas este correo, puedes ignorarlo.</p>
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

            $iconMarkup = '';
            $iconPath = dirname(__DIR__) . '/favicon-color.png';
            if (!file_exists($iconPath)) {
                $iconPath = dirname(__DIR__) . '/img/antena.png';
            }

            if (file_exists($iconPath)) {
                $mail->AddEmbeddedImage($iconPath, 'company_icon', basename($iconPath));
                $iconMarkup = '<div style="margin-bottom: 12px;"><img src="cid:company_icon" alt="Icono de empresa" style="width: 56px; height: 56px; border-radius: 14px; display: inline-block; object-fit: contain; background: #ffffff; border: 1px solid #d8e1ea; padding: 8px;"></div>';
            }

            $emailBody = str_replace('{{ICONO_EMPRESA}}', $iconMarkup, $emailBody);

            $logoPath = dirname(__DIR__) . '/img/logo.jpg';
            if (file_exists($logoPath)) {
                $mail->AddEmbeddedImage($logoPath, 'logo', 'logo.jpg');
                $emailBody .= '<div style="margin-top: 20px;"><img src="cid:logo" alt="TheNomadapp" style="width: 120px;"></div>';
            }

            $mail->isHTML(true);
            $mail->Subject = '[TheNomadapp] Invitacion para registrar tu gestora';
            $mail->Body = $emailBody;
            $mail->send();

            return [
                'success' => true,
                'message' => 'La invitacion se envio correctamente.',
            ];
        } catch (Exception $exception) {
            return [
                'success' => false,
                'message' => 'No se pudo enviar el correo de invitacion: ' . $mail->ErrorInfo,
            ];
        }
    }
}