<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

if (!function_exists('enviarCorreoRetirarInvertir')) {
    function enviarCorreoRetirarInvertir($destiny, $nombreUsuario = '', $tipoOperacion = 'invertir', $cantidad = 0, $concepto = '')
    {
        $dotenv = Dotenv::createImmutable(dirname(__DIR__));
        $dotenv->load();

        $mail = new PHPMailer(true);
        
        $usuarioLabel = trim((string) $nombreUsuario) !== '' ? htmlspecialchars((string) $nombreUsuario) : 'Usuario';
        $cantidadLabel = htmlspecialchars((string) $cantidad);
        $conceptoLabel = trim((string) $concepto) !== '' ? htmlspecialchars((string) $concepto) : 'sin concepto especificado';
        
        // Determine operation type and message
        $operacionTexto = strtolower($tipoOperacion) === 'retirar' ? 'retirada' : 'inversión';
        $operacionTitulo = strtolower($tipoOperacion) === 'retirar' ? 'Retirada de fondos' : 'Inversión en promociones';
        $operacionAccion = strtolower($tipoOperacion) === 'retirar' ? 'retiraste' : 'invertiste';

        $emailBody = '
        <div style="font-family: Nunito, Arial, sans-serif; color: #1f2933; line-height: 1.6;">
            <h2 style="color: #BDE742; background-color: #1f1f1f; padding: 15px; text-align: center;">' . $operacionTitulo . '</h2>
            <p>Hola ' . $usuarioLabel . ',</p>
            <p>Te confirmamos que hemos registrado tu ' . $operacionTexto . ' de fondos en TheNomadapp.</p>
            <div style="background: #f6f9fc; border: 1px solid #d8e1ea; border-radius: 14px; padding: 18px; margin: 20px 0;">
                <p style="margin: 0 0 8px;"><strong>Operación:</strong> ' . $operacionTitulo . '</p>
                <p style="margin: 0 0 8px;"><strong>Cantidad:</strong> €' . $cantidadLabel . '</p>
                <p style="margin: 0 0 8px;"><strong>Concepto:</strong> ' . $conceptoLabel . '</p>
                <p style="margin: 0;"><strong>Estado:</strong> Procesada correctamente</p>
            </div>
            <p>Si tienes alguna pregunta sobre esta transacción, no dudes en contactarnos a través de nuestro centro de soporte.</p>
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
            $mail->Subject = '[TheNomadapp] Confirmación de ' . $operacionTexto;
            $mail->Body = $emailBody;
            $mail->send();

            return [
                'success' => true,
                'message' => 'El correo de ' . $operacionTexto . ' se envió correctamente.',
            ];
        } catch (Exception $exception) {
            return [
                'success' => false,
                'message' => 'No se pudo enviar el correo de ' . $operacionTexto . ': ' . $mail->ErrorInfo,
            ];
        }
    }
}
