<?php
require_once 'verificar_sesion_gestor.php';

require '../vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

$url = 'http://' . $_ENV['SERVER_IP'] . ':' . $_ENV['DATABASE_PORT'] . '/rest/v1/gestor?id=eq.' . $_SESSION['user_id'];
$ch = curl_init($url);

// AÑADIMOS LA DIRECCIÓN A LA BBDD
$data = [
    'plan' => $_SESSION['tipoSuscripcion'],
    'plan_end' => $_SESSION['fecha_fin'],
    'domicilio_social' => $_SESSION['direccion']
];

curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    'Content-Type: application/json',
    'apikey: ' . $_ENV['DATABASE_APIKEY'],
    'Authorization: Bearer ' . (isset($_SESSION['token']) ? $_SESSION['token'] : ''),
    'Prefer: return=representation' // MUY IMPORTANTE: Obliga a Supabase a confirmar si se guardó algo
));

curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

$resultado = curl_exec($ch);
$codigoRespuesta = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$datosActualizados = json_decode($resultado, true);

// Evaluamos si todo fue bien Y si Supabase realmente modificó alguna fila
if ($codigoRespuesta >= 200 && $codigoRespuesta < 300 && !empty($datosActualizados)) {
    // ÉXITO
    unset($_SESSION['direccion'], $_SESSION['plan'], $_SESSION['total'], $_SESSION['tipoSuscripcion'], $_SESSION['fecha_inicio'], $_SESSION['fecha_fin'], $_SESSION['precio_base']);
    header('Location: VistaProGestorCompletada.php');
    exit;
} else {
    // ERROR O FALLO SILENCIOSO
    echo "<div style='font-family: sans-serif; padding: 40px; text-align: center; background-color: #f8f9fa; min-height: 100vh;'>";
    echo "<h2 style='color: #dc3545;'>⚠️ No se pudo actualizar tu plan</h2>";
    
    if (empty($datosActualizados) && $codigoRespuesta < 300) {
        echo "<p>El servidor respondió OK, pero <strong>no se actualizó ninguna fila en la tabla gestor</strong>.</p>";
        echo "<p style='color: #666;'>Esto suele ocurrir porque el usuario no tiene permisos de escritura (RLS de Supabase) o porque tu sesión ha caducado.</p>";
    } else {
        echo "<p><strong>Código HTTP:</strong> " . $codigoRespuesta . "</p>";
        echo "<p><strong>Respuesta:</strong> " . htmlspecialchars($resultado) . "</p>";
    }
    
    echo "<br><a href='Suscripciones.php' style='padding: 10px 20px; background-color: #00B7CF; color: white; text-decoration: none; border-radius: 5px; font-weight: bold;'>Volver a Suscripciones</a>";
    echo "</div>";
    exit;
}