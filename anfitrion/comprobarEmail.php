<?php
require '../vendor/autoload.php';
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

if (!isset($_GET['email'])) {
    http_response_code(400);
    echo json_encode(["error" => "Parámetro 'email' requerido"]);
    exit;
}

$email = $_GET['email'];
error_log("Verificando email: $email");

// Primero verificar en la tabla host
$urlHost = "http://" . $_ENV['SERVER_IP'] . ":" . $_ENV['DATABASE_PORT'] . "/rest/v1/host?email=eq." . urlencode($email);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $urlHost);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'apikey: ' . $_ENV['DATABASE_APIKEY'],
    'Content-Type: application/json'
]);

$responseHost = curl_exec($ch);
$httpCodeHost = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if (curl_errno($ch)) {
    error_log("Error CURL al verificar email en host: " . curl_error($ch));
    http_response_code(500);
    echo json_encode(["error" => curl_error($ch)]);
    curl_close($ch);
    exit;
}

curl_close($ch);

$hostData = json_decode($responseHost, true);

// Si existe un host con ese email, devolver error
if (is_array($hostData) && count($hostData) > 0) {
    error_log("Email ya registrado como host: $email");
    echo json_encode([
        "status" => "host_exists",
        "message" => "Este email ya está registrado como anfitrión"
    ]);
    exit;
}

// Si no existe host, verificar en la tabla user
$urlUser = "http://" . $_ENV['SERVER_IP'] . ":" . $_ENV['DATABASE_PORT'] . "/rest/v1/user?email=eq." . urlencode($email);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $urlUser);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'apikey: ' . $_ENV['DATABASE_APIKEY'],
    'Content-Type: application/json'
]);

$responseUser = curl_exec($ch);
$httpCodeUser = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if (curl_errno($ch)) {
    error_log("Error CURL al verificar email en user: " . curl_error($ch));
    http_response_code(500);
    echo json_encode(["error" => curl_error($ch)]);
    curl_close($ch);
    exit;
}

curl_close($ch);

$userData = json_decode($responseUser, true);

// Si existe un user con ese email
if (is_array($userData) && count($userData) > 0) {
    error_log("Email encontrado como user: $email");
    echo json_encode([
        "status" => "user_exists",
        "message" => "Email registrado como usuario",
        "user_id" => $userData[0]['id']
    ]);
    exit;
}

// Si no existe ni como host ni como user, email disponible
error_log("Email disponible: $email");
echo json_encode([
    "status" => "available",
    "message" => "Email disponible"
]);
?>