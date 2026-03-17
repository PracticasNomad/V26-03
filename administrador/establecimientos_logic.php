<?php
// Archivo de lógica para mostrar establecimientos en la vista del ADMIN
require_once 'verificar_sesion_admin.php'; // Cambiado a admin
require '../vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

// Configuración de API
$apiUrl = 'http://' . $_ENV['SERVER_IP'] . ':' . $_ENV['DATABASE_PORT'] . '/rest/v1';
$errorEstablecimientos = '';

// Imágenes de fondo para las tarjetas de establecimientos
$backgroundImages = [
    '../img/bg1.jpg',
    '../img/bg2.jpg',
    '../img/bg3.jpg',
    '../img/bg4.jpg',
];

function normalizarUrlImagen($url)
{
    if (empty($url)) {
        return '';
    }
    if (strpos($url, 'http://') === 0 || strpos($url, 'https://') === 0) {
        return $url;
    }
    if (strpos($url, '../') === 0 || strpos($url, './') === 0 || strpos($url, '/') === 0) {
        return $url;
    }
    if (strpos($url, 'uploads/') === 0) {
        return '../' . $url;
    }
    return 'http://' . ltrim($url, '/');
}

/**
 * Obtiene todos los establecimientos GLOBALES con filtros opcionales
 */
function getEstablecimientosAsignados()
{
    global $apiUrl, $errorEstablecimientos;

    $queryParams = ['select=*'];

    // Aplicar filtros si vienen por GET
    if (!empty($_GET['buscar_nombre'])) {
        $queryParams[] = 'nombre=ilike.*' . rawurlencode(trim($_GET['buscar_nombre'])) . '*';
    }
    if (!empty($_GET['buscar_ciudad'])) {
        $queryParams[] = 'localidad=ilike.*' . rawurlencode(trim($_GET['buscar_ciudad'])) . '*';
    }
    if (!empty($_GET['buscar_cp'])) {
        $queryParams[] = 'codigo_postal=eq.' . rawurlencode(trim($_GET['buscar_cp']));
    }

    $queryString = implode('&', $queryParams);
    $url = $apiUrl . '/establecimiento?' . $queryString;

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'apikey: ' . $_ENV['SERVICE_APIKEY'], // El admin usa Service Key
        'Authorization: Bearer ' . $_ENV['SERVICE_APIKEY'],
        'Content-Type: application/json'
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode >= 200 && $httpCode < 300) {
        $data = json_decode($response, true);
        $establecimientos = [];

        if (is_array($data)) {
            $ids = array_column($data, 'id');
            $galleryByEstablecimiento = [];

            // Obtener imágenes de galería
            if (!empty($ids)) {
                $idsFilter = array_map(function ($id) {
                    return '"' . str_replace('"', '\\"', (string) $id) . '"'; }, $ids);
                $urlGallery = $apiUrl . '/gallery?select=id,establecimiento_id,image_url&establecimiento_id=in.(' . implode(',', $idsFilter) . ')&order=establecimiento_id.asc,id.desc';

                $chGallery = curl_init($urlGallery);
                curl_setopt($chGallery, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($chGallery, CURLOPT_HTTPHEADER, [
                    'apikey: ' . $_ENV['SERVICE_APIKEY'],
                    'Authorization: Bearer ' . $_ENV['SERVICE_APIKEY'],
                    'Content-Type: application/json'
                ]);

                $responseGallery = curl_exec($chGallery);
                if (curl_getinfo($chGallery, CURLINFO_HTTP_CODE) === 200) {
                    $galleryData = json_decode($responseGallery, true);
                    if (is_array($galleryData)) {
                        foreach ($galleryData as $img) {
                            if (!isset($galleryByEstablecimiento[$img['establecimiento_id']]) && !empty($img['image_url'])) {
                                $galleryByEstablecimiento[$img['establecimiento_id']] = $img['image_url'];
                            }
                        }
                    }
                }
                curl_close($chGallery);
            }

            // Para el Admin NO excluimos los rechazados, queremos que vea todos
            foreach ($data as $est) {
                $idEst = $est['id'] ?? null;
                $banner = normalizarUrlImagen($est['image_url'] ?? '');

                if (empty($banner) && $idEst !== null && isset($galleryByEstablecimiento[$idEst])) {
                    $banner = normalizarUrlImagen($galleryByEstablecimiento[$idEst]);
                }
                $est['banner_image_url'] = !empty($banner) ? $banner : '';
                $establecimientos[] = $est;
            }
        }
        return $establecimientos;
    }

    $errorEstablecimientos = 'Error al obtener los establecimientos de la base de datos.';
    return [];
}

function getEstadisticasEstablecimientos()
{
    $establecimientos = getEstablecimientosAsignados();
    $total = count($establecimientos);
    $aprobados = 0;
    $pendientes = 0;

    foreach ($establecimientos as $est) {
        $estadoValidacion = $est['estaValidado'] ?? $est['estavalidado'] ?? null;
        if ($estadoValidacion === true || $estadoValidacion === 'true' || $estadoValidacion === 't' || $estadoValidacion === 1 || $estadoValidacion === '1') {
            $aprobados++;
        } elseif ($estadoValidacion === null || $estadoValidacion === '') {
            $pendientes++;
        } else {
            if (($est['estado'] ?? '') === 'aprobado')
                $aprobados++;
            if (($est['estado'] ?? '') === 'pendiente')
                $pendientes++;
        }
    }

    return ['total' => $total, 'aprobados' => $aprobados, 'pendientes' => $pendientes];
}

function formatearDireccion($direccion, $piso = null)
{
    $result = htmlspecialchars($direccion);
    if (!empty($piso))
        $result .= ', Piso ' . htmlspecialchars($piso);
    return $result;
}

function getImagenUrl($imageUrl)
{
    return empty($imageUrl) ? '' : normalizarUrlImagen($imageUrl);
}

$establecimientos = getEstablecimientosAsignados();
$estadisticas = getEstadisticasEstablecimientos();

$totalEstablecimientos = $estadisticas['total'];
$establecimientosAprobados = $estadisticas['aprobados'];
$establecimientosPendientes = $estadisticas['pendientes'];
?>