<?php
session_start();
require '../vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

// Mostrar información de debug
echo "<h1>DEBUG API CONNECTION</h1>";
echo "<pre>";

echo "=== SESSION ===\n";
echo "Token: " . (isset($_SESSION['token']) ? substr($_SESSION['token'], 0, 20) . "..." : "NO DEFINIDO") . "\n";
echo "Access Token: " . (isset($_SESSION['access_token']) ? substr($_SESSION['access_token'], 0, 20) . "..." : "NO DEFINIDO") . "\n";

echo "\n=== ENVIRONMENT ===\n";
echo "SERVER_IP: " . $_ENV['SERVER_IP'] . "\n";
echo "DATABASE_PORT: " . $_ENV['DATABASE_PORT'] . "\n";
echo "DATABASE_APIKEY: " . (isset($_ENV['DATABASE_APIKEY']) ? substr($_ENV['DATABASE_APIKEY'], 0, 20) . "..." : "NO DEFINIDO") . "\n";

echo "\n=== TESTING API CONNECTION ===\n";

// Test 1: Obtener todos los establecimientos
$url = 'http://' . $_ENV['SERVER_IP'] . ':' . $_ENV['DATABASE_PORT'] . '/rest/v1/establecimiento';
echo "URL: $url\n\n";

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'apikey: ' . $_ENV['DATABASE_APIKEY'],
        'Authorization: Bearer ' . ($_SESSION['token'] ?? $_SESSION['access_token'] ?? ''),
    ],
    CURLOPT_VERBOSE => true,
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error = curl_error($ch);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
echo "CURL Error: " . ($curl_error ? $curl_error : "NONE") . "\n";

if ($httpCode === 200) {
    $data = json_decode($response, true);
    echo "Total Establecimientos: " . count($data) . "\n";
    
    if (count($data) > 0) {
        echo "\nPrimer Establecimiento:\n";
        print_r($data[0]);
    }
} else {
    echo "Response:\n$response\n";
}

echo "</pre>";
?>
