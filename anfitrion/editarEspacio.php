<?php
require_once 'verificar_sesion_host.php';

require '../vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

/*
if (!isset($_SESSION['user_id']) || !isset($_SESSION['token'])) {
    header("Location: login.php");
    exit();
}

*/
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (
        isset($_POST['id']) &&
        isset($_POST['schedule_id']) &&
        isset($_POST['nombre']) &&
        isset($_POST['descripcion']) &&
        isset($_POST['horaEntrada']) &&
        isset($_POST['minutoEntrada']) &&
        isset($_POST['horaSalida']) &&
        isset($_POST['minutoSalida'])
    ) {
        $espacioId = $_POST['id'];
        $scheduleId = $_POST['schedule_id'];
        $nombre = $_POST['nombre'];
        $descripcion = $_POST['descripcion'];
        $horaEntrada = $_POST['horaEntrada'];
        $minutoEntrada = $_POST['minutoEntrada'];
        $horaSalida = $_POST['horaSalida'];
        $minutoSalida = $_POST['minutoSalida'];

        $monday = isset($_POST['weekday-mon']) ? $_POST['weekday-mon'] === 'true' : false;
        $tuesday = isset($_POST['weekday-tue']) ? $_POST['weekday-tue'] === 'true' : false;
        $wednesday = isset($_POST['weekday-wed']) ? $_POST['weekday-wed'] === 'true' : false;
        $thursday = isset($_POST['weekday-thu']) ? $_POST['weekday-thu'] === 'true' : false;
        $friday = isset($_POST['weekday-fri']) ? $_POST['weekday-fri'] === 'true' : false;
        $saturday = isset($_POST['weekday-sat']) ? $_POST['weekday-sat'] === 'true' : false;
        $sunday = isset($_POST['weekday-sun']) ? $_POST['weekday-sun'] === 'true' : false;

        $url = 'http://' . $_ENV['SERVER_IP'] . ':' . $_ENV['DATABASE_PORT'] . '/rest/v1/schedule?id=eq.' . $scheduleId;
        $ch = curl_init($url);
        $data = array(
            "start_time" => sprintf("%02d:%02d", $horaEntrada, $minutoEntrada),
            "end_time" => sprintf("%02d:%02d", $horaSalida, $minutoSalida),
            "has_monday" => $monday,
            "has_tuesday" => $tuesday,
            "has_wednesday" => $wednesday,
            "has_thursday" => $thursday,
            "has_friday" => $friday,
            "has_saturday" => $saturday,
            "has_sunday" => $sunday
        );

        $payload = json_encode($data);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PATCH");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type:application/json',
            'apikey: ' . $_ENV['DATABASE_APIKEY'],
            'Authorization: Bearer ' . $_SESSION['token']
        ));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $scheduleResult = curl_exec($ch);
        $scheduleHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($scheduleHttpCode >= 200 && $scheduleHttpCode < 300) {
            $url = 'http://' . $_ENV['SERVER_IP'] . ':' . $_ENV['DATABASE_PORT'] . '/rest/v1/space?id=eq.' . $espacioId;
            $ch = curl_init($url);
            $data = array(
                "name" => $nombre,
                "description" => $descripcion,
                "has_wifi" => isset($_POST['wifi']) ? true : false,
                "has_food" => isset($_POST['comida']) ? true : false,
                "has_parking" => isset($_POST['parking']) ? true : false,
                "parking_price" => isset($_POST['parkingPrice']) ? $_POST['parkingPrice'] : 0
            );

            $payload = json_encode($data);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PATCH");
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type:application/json',
                'apikey: ' . $_ENV['DATABASE_APIKEY'],
                'Authorization: Bearer ' . $_SESSION['token']
            ));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $spaceResult = curl_exec($ch);
            $spaceHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if ($spaceHttpCode >= 200 && $spaceHttpCode < 300) {
                header("Location: tusEspacios.php?updated=true");
                exit();
            } else {
                header("Location: tusEspacios.php?error=space");
                exit();
            }
        }
    }
}
