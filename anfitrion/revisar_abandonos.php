<?php
// revisar_abandonos.php
require_once dirname(__DIR__) . '/vendor/autoload.php';
require_once dirname(__DIR__) . '/emails/borradorRegistro.php'; // Tu archivo de correos

use Dotenv\Dotenv;

function revisarYEnviarAbandonos()
{
    $dotenv = Dotenv::createImmutable(dirname(__DIR__));
    $dotenv->safeLoad();

    $supabaseKey = $_ENV['DATABASE_APIKEY'];
    $serverIp = $_ENV['SERVER_IP'];
    $dbPort = $_ENV['DATABASE_PORT'];

    // 1. Buscamos registros creados hace más de 30 minutos (ajusta los minutos si quieres)
    $haceMediaHora = date('Y-m-d\TH:i:s', strtotime('-30 minutes'));

    // Buscamos los que NO se ha enviado correo aún (asumimos que creaste la columna 'correo_enviado' en Supabase)
    $urlBusqueda = "http://{$serverIp}:{$dbPort}/rest/v1/registros_abandonados?correo_enviado=is.false&created_at=lt." . urlencode($haceMediaHora);

    $ch = curl_init($urlBusqueda);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 2, // Solo le damos 2 segundos para no ralentizar la web al usuario actual
        CURLOPT_HTTPHEADER => [
            'apikey: ' . $supabaseKey,
            'Authorization: Bearer ' . $supabaseKey
        ]
    ]);
    $respuesta = curl_exec($ch);
    curl_close($ch);

    $abandonos = json_decode($respuesta, true);

    // Si hay gente que lo dejó a medias...
    if (is_array($abandonos) && count($abandonos) > 0) {
        $protocolo = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
        $dominio = $_SERVER['HTTP_HOST'];
        $carpeta = rtrim(dirname($_SERVER['REQUEST_URI']), '/');

        foreach ($abandonos as $usuario) {
            $email = $usuario['email'];
            $nombre = $usuario['nombre'];
            $token = $usuario['token'];

            $enlaceMagico = $protocolo . $dominio . $carpeta . "/resumeRegistro.php?token=" . $token;

            // Enviamos el correo
            $envio = enviarCorreoBorrador($email, $nombre, $enlaceMagico);

            // Si se envió bien, lo marcamos en la base de datos para no volver a enviarlo
            if ($envio['success']) {
                $urlActualizar = "http://{$serverIp}:{$dbPort}/rest/v1/registros_abandonados?id=eq." . $usuario['id'];
                $chUpd = curl_init($urlActualizar);
                curl_setopt_array($chUpd, [
                    CURLOPT_CUSTOMREQUEST => 'PATCH',
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_TIMEOUT => 1,
                    CURLOPT_POSTFIELDS => json_encode(['correo_enviado' => true]),
                    CURLOPT_HTTPHEADER => [
                        'Content-Type: application/json',
                        'apikey: ' . $supabaseKey,
                        'Authorization: Bearer ' . $supabaseKey
                    ]
                ]);
                curl_exec($chUpd);
                curl_close($chUpd);
            }
        }
    }
}

// Ejecutamos la función de forma silenciosa
try {
    revisarYEnviarAbandonos();
} catch (Exception $e) {
    // Si falla, no hacemos nada para no molestar al usuario que está navegando
}
