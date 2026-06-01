<?php
session_start();
require './vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

// 1. LLAMADA ORIGINAL SEGURA (Establecimientos)
$select = '*,gallery(image_url),space(id,schedule(has_monday,has_tuesday,has_wednesday,has_thursday,has_friday,has_saturday,has_sunday)),valoraciones(valoracion)';
$url = 'http://' . $_ENV['SERVER_IP'] . ':' . $_ENV['DATABASE_PORT'] . '/rest/v1/establecimiento?select=' . rawurlencode($select);

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    'Content-Type: application/json',
    'Accept: application/json',
    'apikey: ' . $_ENV['DATABASE_APIKEY'],
    'Authorization: Bearer ' . $_ENV['SERVICE_APIKEY']
));
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
$response = curl_exec($ch);
$data = json_decode($response, true);
curl_close($ch);

// 2. SEGUNDA LLAMADA PARA LAS RESERVAS (Independiente y con escudo de seguridad)
$urlReservas = 'http://' . $_ENV['SERVER_IP'] . ':' . $_ENV['DATABASE_PORT'] . '/rest/v1/reservation?select=*';
$chRes = curl_init($urlReservas);
curl_setopt($chRes, CURLOPT_RETURNTRANSFER, true);
curl_setopt($chRes, CURLOPT_HTTPHEADER, array(
    'Content-Type: application/json',
    'Accept: application/json',
    'apikey: ' . $_ENV['DATABASE_APIKEY'],
    'Authorization: Bearer ' . $_ENV['SERVICE_APIKEY']
));
$responseReservas = curl_exec($chRes);
$httpCodeReservas = curl_getinfo($chRes, CURLINFO_HTTP_CODE);
$reservasData = json_decode($responseReservas, true);
curl_close($chRes);

// Agrupamos las reservas por space_id usando PHP (Solo si la base de datos respondió OK)
$reservasPorEspacio = [];
if ($httpCodeReservas >= 200 && $httpCodeReservas < 300 && is_array($reservasData) && !isset($reservasData['message'])) {
    foreach ($reservasData as $res) {
        $sId = $res['space_id'] ?? null;
        
        if (!$sId) continue; // Si la reserva no tiene ID de espacio, la saltamos

        if (!isset($reservasPorEspacio[$sId])) {
            $reservasPorEspacio[$sId] = ['total' => 0, 'canceladas' => 0];
        }
        $reservasPorEspacio[$sId]['total']++;
        
        // Buscador dinámico de cancelaciones (comprueba varios nombres de columna comunes)
        $cancelada = false;
        if (isset($res['estado_cancelacion']) && ($res['estado_cancelacion'] == 1 || $res['estado_cancelacion'] === true)) $cancelada = true;
        if (isset($res['estado']) && strtolower($res['estado']) == 'cancelada') $cancelada = true;
        if (isset($res['status']) && strtolower($res['status']) == 'cancelada') $cancelada = true;
        if (isset($res['motivo_cancelacion']) && !empty($res['motivo_cancelacion'])) $cancelada = true;

        if ($cancelada) {
            $reservasPorEspacio[$sId]['canceladas']++;
        }
    }
}

$processedData = [];

// Procesamos si los datos del establecimiento son correctos
if (is_array($data) && !isset($data['error']) && !isset($data['message'])) {
    foreach ($data as $establecimiento) {

        // Oculta los establecimientos sin espacios
        if (!isset($establecimiento['space']) || empty($establecimiento['space'])) {
            continue;
        }

        $item = $establecimiento;
        
        // Forzamos booleanos para evitar fallos en el JavaScript de los filtros
        $item['has_wifi'] = (bool)$establecimiento['has_wifi'];
        $item['has_parking'] = (bool)$establecimiento['has_parking'];
        $item['has_food'] = isset($establecimiento['has_food']) ? (bool)$establecimiento['has_food'] : false;
        $item['has_accommodation'] = isset($establecimiento['has_accommodation']) ? (bool)$establecimiento['has_accommodation'] : false;

        // CRUZAMOS LAS ESTADÍSTICAS
        $totalReservasEst = 0;
        $canceladasEst = 0;

        foreach ($establecimiento['space'] as $space) {
            $sId = $space['id'];
            if (isset($reservasPorEspacio[$sId])) {
                $totalReservasEst += $reservasPorEspacio[$sId]['total'];
                $canceladasEst += $reservasPorEspacio[$sId]['canceladas'];
            }
        }

        $item['total_reservas'] = $totalReservasEst; 
        $item['canceladas'] = $canceladasEst;

        // Asignación de imagen principal
        if (isset($establecimiento['gallery']) && is_array($establecimiento['gallery']) && count($establecimiento['gallery']) > 0) {
            $item['imagen'] = $establecimiento['gallery'][0]['image_url'];
        } else {
            $item['imagen'] = null;
        }

        // Cálculo de valoraciones medias
        $totalValoraciones = 0;
        $sumaValoraciones = 0;
        if (isset($establecimiento['valoraciones']) && is_array($establecimiento['valoraciones'])) {
            $totalValoraciones = count($establecimiento['valoraciones']);
            foreach ($establecimiento['valoraciones'] as $val) {
                $sumaValoraciones += (float)$val['valoracion'];
            }
        }
        
        if ($totalValoraciones > 0) {
            $item['media_valoracion'] = round($sumaValoraciones / $totalValoraciones, 1);
        } else {
            $item['media_valoracion'] = null;
        }

        // Comprobación de disponibilidad
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

        // Limpiamos memoria
        unset($item['gallery']);
        unset($item['space']);
        unset($item['valoraciones']);
             
        $processedData[] = $item;
    }
}

// Devolvemos los datos limpios al mapa
echo json_encode($processedData);
?>