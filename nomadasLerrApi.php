<?php

session_start();

require './vendor/autoload.php';
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$select = rawurlencode('*,schedule(has_monday, has_tuesday, has_wednesday, has_thursday, has_friday, has_saturday, has_sunday, start_time, end_time)');

$url = 'http://' . $_ENV['SERVER_IP'] . ':' . $_ENV['DATABASE_PORT'] . '/rest/v1/space?select=' . $select . '&establecimiento_id=eq.' . $_SESSION['anfitrion_id'] . '&visible=eq.true';

//echo $_SESSION['anfitrion_id'];
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    'Authorization: Bearer ' . $_SESSION['token'],
    'Content-Type: application/json',
    'apikey: ' . $_ENV['DATABASE_APIKEY'],
));
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
$response = curl_exec($ch);

$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
if ($httpCode >= 200 && $httpCode < 300) {
    echo $response;
} else {
    echo 'Error: ' . curl_error($ch);
}

curl_close($ch);
?>