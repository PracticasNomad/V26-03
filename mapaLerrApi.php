<?php
session_start();
require './vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$select = '*,gallery(image_url),space(id,schedule(has_monday,has_tuesday,has_wednesday,has_thursday,has_friday,has_saturday,has_sunday))';
$url = 'http://' . $_ENV['SERVER_IP'] . ':' . $_ENV['DATABASE_PORT'] . '/rest/v1/establecimiento?select=' . rawurlencode($select);

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    'Content-Type: application/json',
    'Accept: application/json',
    'apikey: ' . $_ENV['DATABASE_APIKEY']
));
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');

$response = curl_exec($ch);
$data = json_decode($response, true);

$processedData = [];

if (is_array($data)) {
    foreach ($data as $establecimiento) {

        // Si no tiene espacios, lo saltamos
        if (!isset($establecimiento['space']) || empty($establecimiento['space'])) {
            continue;
        }

        $item = $establecimiento;

        // Asignar imagen si la tiene
        if (isset($establecimiento['gallery']) && !empty($establecimiento['gallery'])) {
            $item['imagen'] = $establecimiento['gallery'][0]['image_url'];
        } else {
            $item['imagen'] = null;
        }

        $hasAvailability = false;
        foreach ($establecimiento['space'] as $space) {
            if (isset($space['schedule']) && is_array($space['schedule'])) {
                foreach ($space['schedule'] as $sched) {
                    if (
                        $sched['has_monday'] ||
                        $sched['has_tuesday'] ||
                        $sched['has_wednesday'] ||
                        $sched['has_thursday'] ||
                        $sched['has_friday'] ||
                        $sched['has_saturday'] ||
                        $sched['has_sunday']
                    ) {
                        $hasAvailability = true;
                        break 2;
                    }
                }
            }
        }

        $item['tiene_disponibilidad'] = $hasAvailability;

        unset($item['gallery']);
        unset($item['space']);

        $processedData[] = $item;
    }
}

echo json_encode($processedData);

curl_close($ch);
?>