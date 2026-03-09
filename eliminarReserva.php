<?php
require_once 'verificar_sesion_guest.php';
require './vendor/autoload.php';

use Dotenv\Dotenv;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$id_reserva = $_GET['id'];
$motivo = $_GET['motivo'] ?? "";
$info_adicional = $_GET['info_adicional'] ?? "";
$actor = $_GET['actor'] ?? "guest";

if (isset($id_reserva)) {
    // 1. OBTENER DATOS COMPLETOS DE LA RESERVA (Para los correos)
    $urlGet = 'http://' . $_ENV['SERVER_IP'] . ':' . $_ENV['DATABASE_PORT'] . '/rest/v1/reservation?select=*,space(*,establecimiento(*,host(*))),user(*)&id=eq.' . $id_reserva;
    $chGet = curl_init($urlGet);
    curl_setopt_array($chGet, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'apikey: ' . $_ENV['DATABASE_APIKEY'],
            'Authorization: Bearer ' . $_SESSION['token']
        ]
    ]);
    $resGet = curl_exec($chGet);
    curl_close($chGet);

    $datosReserva = json_decode($resGet, true);
    $reservaCompleta = $datosReserva[0] ?? null;

    // 2. CANCELAR LA RESERVA EN LA BASE DE DATOS
    $url = 'http://' . $_ENV['SERVER_IP'] . ':' . $_ENV['DATABASE_PORT'] . '/rest/v1/reservation?id=eq.' . $id_reserva;
    $ch = curl_init($url);
    $data = [
        'cancelada' => true,
        'motivo_cancelacion' => $motivo,
        'informacion_cancelacion' => $info_adicional
    ];

    curl_setopt_array($ch, array(
        CURLOPT_CUSTOMREQUEST => 'PATCH',
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json',
            'Authorization: Bearer ' . $_SESSION['token'],
            'apikey: ' . $_ENV['DATABASE_APIKEY']
        ),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POSTFIELDS => json_encode($data)
    ));

    $resultado = curl_exec($ch);
    $codigoRespuesta = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // 3. SI SE CANCELA CON ÉXITO, ENVIAR LOS DOS CORREOS
    if ($codigoRespuesta >= 200 && $codigoRespuesta < 300) {
        if ($reservaCompleta) {
            enviarCorreosCancelacion($reservaCompleta, $motivo, $actor);
        }
        echo $resultado;
    } else if ($codigoRespuesta == 401) {
        header('Location: logout.php');
        exit;
    } else {
        echo json_encode(["error" => "Error en la peticion"]);
    }
}

function enviarCorreosCancelacion($reserva, $motivo, $actor)
{
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = $_ENV['SMTP_HOST'];
        $mail->SMTPAuth = true;
        $mail->Username = $_ENV['SMTP_USERNAME'];
        $mail->Password = $_ENV['SMTP_PASSWORD'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = $_ENV['EMAIL_PORT'];
        $mail->CharSet = 'UTF-8';
        $mail->isHTML(true);

        // Extraer datos
        $nombreNomada = $reserva['user']['name'] ?? 'Usuario';
        $emailNomada = $reserva['user']['email'] ?? '';

        $nombreAnfitrion = $reserva['space']['establecimiento']['host']['name'] ?? 'Anfitrión';
        $emailAnfitrion = $reserva['space']['establecimiento']['host']['email'] ?? '';

        $espacio = $reserva['space']['name'] ?? '';
        $establecimiento = $reserva['space']['establecimiento']['nombre'] ?? '';
        $fecha = date('d/m/Y', strtotime($reserva['day'])) . ' (' . substr($reserva['start_time'], 0, 5) . ' - ' . substr($reserva['end_time'], 0, 5) . ')';
        $motivoTexto = empty($motivo) ? 'No especificado' : $motivo;

        $mail->setFrom('noreply@yonomad.app', 'The Nomadapp');

        // Cargar imágenes embebidas
        $mail->AddEmbeddedImage('./img/logo.jpg', 'logo', 'logo.jpg');
        $mail->AddEmbeddedImage('./img/facebook.png', 'facebook', 'facebook.png');
        $mail->AddEmbeddedImage('./img/twitter.png', 'twitter', 'twitter.png');
        $mail->AddEmbeddedImage('./img/linkedin.png', 'linkedin', 'linkedin.png');
        $mail->AddEmbeddedImage('./img/instagram.png', 'instagram', 'instagram.png');

        if ($actor === 'guest') {
            // CORREO 1: Para el Nómada (él ha cancelado)
            if (!empty($emailNomada)) {
                $mail->clearAddresses();
                $mail->addAddress($emailNomada);
                $mail->Subject = mb_encode_mimeheader('[TheNomadapp] Proceso de reembolso tras cancelación', 'UTF-8');
                $mail->Body = "
                <div style='font-family: Arial, sans-serif; color: #333;'>
                    <h2 style='color: white; background-color: #00B7CF; padding: 15px; text-align:center;'>Reserva cancelada</h2>
                    <p>Hola $nombreNomada,</p>
                    <p>Hemos recibido la cancelación de tu reserva en <strong>$espacio</strong> ($establecimiento).</p>
                    <p><strong>Fecha:</strong> $fecha<br><strong>Motivo:</strong> $motivoTexto</p>
                    <p>Si estás dentro de las condiciones, gestionaremos el reembolso en 5 a 10 días laborables.</p>
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
                $mail->send();
            }

            // CORREO 2: Para el Anfitrión (avisándole)
            if (!empty($emailAnfitrion)) {
                $mail->clearAddresses();
                $mail->addAddress($emailAnfitrion);
                $mail->Subject = mb_encode_mimeheader('[TheNomadapp] Cancelación de reserva por el usuario', 'UTF-8');
                $mail->Body = "
                <div style='font-family: Arial, sans-serif; color: #333;'>
                    <h2 style='color: white; background-color: #ff5a5a; padding: 15px; text-align:center;'>Reserva cancelada</h2>
                    <p>Hola $nombreAnfitrion,</p>
                    <p>El nómada <strong>$nombreNomada</strong> ha cancelado su reserva en <strong>$espacio</strong> ($establecimiento).</p>
                    <p><strong>Fecha:</strong> $fecha<br><strong>Motivo:</strong> $motivoTexto</p>
                    <p>En caso de que el reembolso proceda, se gestionará automáticamente a través de nuestro sistema.</p>
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
                $mail->send();
            }
        } else { // Si el actor es el Anfitrión ('host')

            // CORREO 1: Para el Anfitrión (confirmándole)
            if (!empty($emailAnfitrion)) {
                $mail->clearAddresses();
                $mail->addAddress($emailAnfitrion);
                $mail->Subject = mb_encode_mimeheader('[TheNomadapp] Confirmación de cancelación', 'UTF-8');
                $mail->Body = "
                <div style='font-family: Arial, sans-serif; color: #333;'>
                    <h2 style='color: #1f1f1f; background-color: #ffc107; padding: 15px; text-align:center;'>Reserva cancelada</h2>
                    <p>Hola $nombreAnfitrion,</p>
                    <p>Te confirmamos que has cancelado la reserva de <strong>$nombreNomada</strong> en <strong>$espacio</strong> ($establecimiento).</p>
                    <p><strong>Fecha:</strong> $fecha<br><strong>Motivo:</strong> $motivoTexto</p>
                    <p>Se procederá al reembolso automático del importe al nómada.</p>
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
                $mail->send();
            }

            // CORREO 2: Para el Nómada (avisándole que le cancelaron)
            if (!empty($emailNomada)) {
                $mail->clearAddresses();
                $mail->addAddress($emailNomada);
                $mail->Subject = mb_encode_mimeheader('[TheNomadapp] Tu reserva ha sido cancelada', 'UTF-8');
                $mail->Body = "
                <div style='font-family: Arial, sans-serif; color: #333;'>
                    <h2 style='color: white; background-color: #ff5a5a; padding: 15px; text-align:center;'>Reserva cancelada</h2>
                    <p>Hola $nombreNomada,</p>
                    <p>Te informamos que el anfitrión ha cancelado tu reserva en <strong>$espacio</strong> ($establecimiento).</p>
                    <p><strong>Fecha:</strong> $fecha<br><strong>Motivo:</strong> $motivoTexto</p>
                    <p>Estamos gestionando el reembolso correspondiente, que será procesado en 5 a 10 días laborables.</p>
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
                $mail->send();
            }
        }
    } catch (Exception $e) {
    }
}
