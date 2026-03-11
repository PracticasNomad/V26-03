<?php
// Archivo de lógica para mostrar establecimientos en la vista del gestor
// Incluye funciones para obtener establecimientos asignados y estadísticas

require_once 'verificar_sesion_gestor.php';
require '../vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

// Configuración de API
$apiUrl = 'http://' . $_ENV['SERVER_IP'] . ':' . $_ENV['DATABASE_PORT'] . '/rest/v1';

// Imágenes de fondo para las tarjetas de establecimientos
$backgroundImages = [
    '../img/bg1.jpg',
    '../img/bg2.jpg',
    '../img/bg3.jpg',
    '../img/bg4.jpg',
];

function normalizarUrlImagen($url) {
    if (empty($url)) {
        return '';
    }

    if (strpos($url, 'http://') === 0 || strpos($url, 'https://') === 0) {
        return $url;
    }

    // Rutas locales relativas (subidas desde el gestor)
    if (strpos($url, '../') === 0 || strpos($url, './') === 0 || strpos($url, '/') === 0) {
        return $url;
    }

    // Rutas locales sin prefijo (ej: uploads/establecimientos/archivo.jpg)
    if (strpos($url, 'uploads/') === 0) {
        return '../' . $url;
    }

    return 'http://' . ltrim($url, '/');
}

/**
 * Obtiene todos los establecimientos (excluyendo rechazados)
 */
function getEstablecimientosAsignados() {
    global $apiUrl;

    // Consulta para obtener todos los establecimientos
    $url = $apiUrl . '/establecimiento';

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'apikey: ' . $_ENV['DATABASE_APIKEY'],
        'Authorization: Bearer ' . ($_SESSION['access_token'] ?? $_SESSION['token'] ?? ''),
        'Content-Type: application/json'
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200) {
        $data = json_decode($response, true);
        $establecimientos = [];
        if (is_array($data)) {
            $ids = [];
            foreach ($data as $estTmp) {
                if (isset($estTmp['id'])) {
                    $ids[] = $estTmp['id'];
                }
            }

            $galleryByEstablecimiento = [];
            if (!empty($ids)) {
                $idsFilter = array_map(function ($id) {
                    if (is_numeric($id)) {
                        return $id;
                    }
                    return '"' . str_replace('"', '\\"', (string)$id) . '"';
                }, $ids);

                $urlGallery = $apiUrl . '/gallery?select=id,establecimiento_id,image_url&establecimiento_id=in.(' . implode(',', $idsFilter) . ')&order=establecimiento_id.asc,id.desc';

                $chGallery = curl_init($urlGallery);
                curl_setopt($chGallery, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($chGallery, CURLOPT_HTTPHEADER, [
                    'apikey: ' . $_ENV['DATABASE_APIKEY'],
                    'Authorization: Bearer ' . ($_SESSION['access_token'] ?? $_SESSION['token'] ?? ''),
                    'Content-Type: application/json'
                ]);

                $responseGallery = curl_exec($chGallery);
                $httpCodeGallery = curl_getinfo($chGallery, CURLINFO_HTTP_CODE);
                curl_close($chGallery);

                if ($httpCodeGallery === 200) {
                    $galleryData = json_decode($responseGallery, true);
                    if (is_array($galleryData)) {
                        foreach ($galleryData as $img) {
                            $estId = $img['establecimiento_id'] ?? null;
                            $imgUrl = $img['image_url'] ?? null;

                            if ($estId !== null && !isset($galleryByEstablecimiento[$estId]) && !empty($imgUrl)) {
                                $galleryByEstablecimiento[$estId] = $imgUrl;
                            }
                        }
                    }
                }
            }

            // Filtrar para excluir rechazados
            foreach ($data as $est) {
                if (($est['estado'] ?? '') !== 'rechazado') {
                    $idEst = $est['id'] ?? null;
                    $banner = normalizarUrlImagen($est['image_url'] ?? '');

                    if (empty($banner) && $idEst !== null && isset($galleryByEstablecimiento[$idEst])) {
                        $banner = normalizarUrlImagen($galleryByEstablecimiento[$idEst]);
                    }

                    $est['banner_image_url'] = !empty($banner) ? $banner : '';
                    $establecimientos[] = $est;
                }
            }
        }
        return $establecimientos;
    }

    return [];
}

/**
 * Obtiene estadísticas de los establecimientos del gestor (excluyendo rechazados)
 */
function getEstadisticasEstablecimientos() {
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
            // Fallback al campo estado si estaValidado no existe
            switch ($est['estado'] ?? '') {
                case 'aprobado':
                    $aprobados++;
                    break;
                case 'pendiente':
                    $pendientes++;
                    break;
            }
        }
    }

    return [
        'total' => $total,
        'aprobados' => $aprobados,
        'pendientes' => $pendientes
    ];
}

/**
 * Formatea la dirección incluyendo piso si existe
 */
function formatearDireccion($direccion, $piso = null) {
    $result = htmlspecialchars($direccion);
    if (!empty($piso)) {
        $result .= ', Piso ' . htmlspecialchars($piso);
    }
    return $result;
}

/**
 * Obtiene la URL completa de la imagen del establecimiento
 */
function getImagenUrl($imageUrl) {
    if (empty($imageUrl)) {
        return ''; // No mostrar imagen por defecto
    }
    return normalizarUrlImagen($imageUrl);
}

// Obtener datos principales
$establecimientos = getEstablecimientosAsignados();
$estadisticas = getEstadisticasEstablecimientos();

// Variables para usar en la vista
$totalEstablecimientos = $estadisticas['total'];
$establecimientosAprobados = $estadisticas['aprobados'];
$establecimientosPendientes = $estadisticas['pendientes'];
?>