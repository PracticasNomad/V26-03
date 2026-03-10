<?php
session_start();

// este script carga la lista de establecimientos cuyo estado sea "pendiente"
require '../vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

$establecimientos = [];
$establecimientosRechazados = [];
$data = [];
$backgroundImages = [
    '../img/bg1.jpg',
    '../img/bg2.jpg',
    '../img/bg3.jpg',
    '../img/bg4.jpg',
];

// consulta a la API para obtener TODOS los establecimientos
$url = 'http://' . $_ENV['SERVER_IP'] . ':' . $_ENV['DATABASE_PORT']
    . '/rest/v1/establecimiento';
$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'apikey: ' . $_ENV['DATABASE_APIKEY'],
        'Authorization: Bearer ' . ($_SESSION['token'] ?? ''),
    ],
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error = curl_error($ch);
curl_close($ch);

if ($httpCode === 200) {
    $data = json_decode($response, true);
    if (is_array($data)) {
        // Filtrar: pendientes = aquellos con estaValidado=false y estado != rechazado
        foreach ($data as $est) {
            $esValidado = isset($est['estaValidado']) ? $est['estaValidado'] : false;
            $estado = $est['estado'] ?? '';
            
            // Si no está validado y no está rechazado, es pendiente
            if (!$esValidado && $estado !== 'rechazado') {
                $establecimientos[] = $est;
            }
        }
    }
}

// consulta a la API para obtener los establecimientos rechazados
// Reutilizamos los datos que ya obtuvimos
if (is_array($data)) {
    foreach ($data as $est) {
        $estado = $est['estado'] ?? '';
        if ($estado === 'rechazado') {
            $establecimientosRechazados[] = $est;
        }
    }
}

// consulta a la API para obtener los establecimientos validados
$establecimientosValidados = [];
if (is_array($data)) {
    foreach ($data as $est) {
        $esValidado = isset($est['estaValidado']) ? $est['estaValidado'] : false;
        if ($esValidado) {
            $establecimientosValidados[] = $est;
        }
    }
}

function formatearDireccion($dir, $piso)
{
    $result = htmlspecialchars($dir);
    if (!empty($piso)) {
        $result .= ' Piso ' . htmlspecialchars($piso);
    }
    return $result;
}

// DEBUG - Verificar datos
$debug_info = "<!-- DEBUG INFO:\n";
$debug_info .= "Total Pendientes: " . count($establecimientos) . "\n";
$debug_info .= "Total Rechazados: " . count($establecimientosRechazados) . "\n";
$debug_info .= "Total Validados: " . count($establecimientosValidados) . "\n";
$debug_info .= "Total Datos API: " . count($data) . "\n";
$debug_info .= "HTTP Code: " . (isset($httpCode) ? $httpCode : 'NO DEFINIDO') . "\n";
$debug_info .= "Token presente: " . (isset($_SESSION['token']) ? 'SÍ' : 'NO') . "\n";
if (!empty($curl_error)) {
    $debug_info .= "CURL Error: " . $curl_error . "\n";
}
$debug_info .= "-->";
// Descomentar para debug: echo $debug_info;
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://kit.fontawesome.com/b8814a2854.js" crossorigin="anonymous"></script>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="icon" href="../favicon-color.png">
    <link rel="icon" href="../favicon-negro.png" media="(prefers-color-scheme: light)">
    <link rel="icon" href="../favicon-color.png" media="(prefers-color-scheme: dark)">
    <title>Validaciones pendientes</title>
    <style>
        body {
            font-family: 'Nunito', sans-serif;
            background-color: #f8f9fa;
            padding-bottom: 50px;
        }

        .contenedor-principal {
            max-width: 1200px;
            margin: 1.5rem auto;
            padding: 0 20px;
        }

        .header-container {
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .btn-add {
            background-color: #28a745;
            border: none;
            font-weight: 600;
            padding: 0.6rem 1.2rem;
            border-radius: 25px;
            margin-bottom: 20px;
            transition: all 0.3s;
            display: flex;
            width: 100%;
            max-width: 600px;
            justify-content: center;
            align-items: center;
            font-size: 0.95rem;
        }

        .btn-add:hover {
            background-color: #218838;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .establecimiento-card {
            background-color: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            margin-bottom: 0;
            overflow: hidden;
            transition: all 0.3s ease;
            border: 1px solid #e9ecef;
        }

        .establecimiento-card:hover {
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.15);
            transform: translateY(-2px);
        }

        .card-header {
            position: relative;
            height: 140px;
            background-size: cover;
            background-position: center;
            display: flex;
            align-items: flex-end;
            background-color: #f8f9fa;
            background-image: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .card-header-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(to bottom, rgba(0, 0, 0, 0.1), rgba(0, 0, 0, 0.7));
        }

        .card-title {
            color: white;
            padding: 15px;
            font-weight: 600;
            font-size: 1.3rem;
            position: relative;
            width: 100%;
            z-index: 1;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .service-icons {
            display: flex;
            gap: 15px;
        }

        .service-icon {
            background-color: rgba(255, 255, 255, 0.9);
            color: #333;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.9rem;
            border: 1px solid rgba(0, 0, 0, 0.1);
        }

        .validation-badge {
            color: white !important;
            border: 2px solid rgba(255, 255, 255, 0.3);
            font-size: 1rem;
        }

        .validation-badge.bg-success {
            background-color: #28a745 !important;
        }

        .validation-badge.bg-warning {
            background-color: #ffc107 !important;
            color: #212529 !important;
        }

        .card-body {
            padding: 15px;
        }

        .info-row {
            display: flex;
            align-items: center;
            margin-bottom: 6px;
            gap: 8px;
        }

        .info-icon {
            color: #28a745;
            width: 18px;
            text-align: center;
            font-size: 0.9rem;
        }

        .btn-validar {
            background-color: #007bff;
            border: none;
            color: white;
            border-radius: 8px;
            padding: 0.4rem 0.8rem;
            font-weight: 500;
            font-size: 0.85rem;
            transition: all 0.2s ease;
        }

        .btn-validar:hover {
            background-color: #0069d9;
            transform: translateY(-1px);
        }

        .btn-validar:active {
            background-color: #0056b3;
        }

        .btn-validar:focus {
            outline: none;
            box-shadow: none;
        }

        .no-establecimientos {
            background-color: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            padding: 2rem;
            text-align: center;
            border: 1px solid #e9ecef;
        }

        /* Active state para el menú "Validar" */
        #lbl_val .icon-container {
            color: #007bff;
        }

        #lbl_val {
            color: #00B7CF !important;
        }

        /* ESTILOS DEL FOOTER */
        .footer {
            color: black;
            background-color: white;
            width: 100%;
            user-select: none;
            bottom: 0;
            font-size: 15px;
            background: #E3E1E1;
            text-align: center;
            position: fixed;
            z-index: 1000;
        }

        .footer-container {
            background-color: white;
            box-shadow: 0px -2px 10px rgba(0, 0, 0, 0.1);
            padding-top: 1px !important;
            padding-bottom: 1px !important;
            height: auto;
            z-index: 1001;
        }

        .footer-item {
            padding: 8px 0;
            -webkit-tap-highlight-color: transparent;
        }

        .icon-container {
            transition: transform 0.3s ease;
            padding: 5px 0;
        }

        .footer-item:hover .icon-container {
            transform: translateY(-7px);
        }

        .footer-item:active .icon-container {
            transform: translateY(0);
        }

        .footer-item:focus .icon-container {
            transform: translateY(0);
        }

        a,
        a:visited,
        a:active {
            color: inherit;
            text-decoration: none;
        }

        .btn-toggle {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            color: #333;
            padding: 0.6rem 0.9rem;
            border-radius: 6px;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: space-between;
            width: 100%;
            font-weight: 500;
            margin-top: 0.8rem;
            margin-bottom: 0.8rem;
        }

        .btn-toggle:hover {
            background-color: #e9ecef;
            border-color: #adb5bd;
        }

        .btn-toggle i {
            transition: transform 0.3s ease;
            margin-left: 10px;
        }

        .btn-toggle.show i {
            transform: rotate(180deg);
        }

        .collapsed-content {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease;
            border-top: 1px solid #e9ecef;
            padding-top: 0;
        }

        .collapsed-content.show {
            max-height: 1200px;
            padding-top: 1rem;
        }

        .collapsed-content .info-row {
            margin-bottom: 0.8rem;
        }

        .precio-tag {
            background-color: #e7f3ff;
            color: #0066cc;
            padding: 0.25rem 0.6rem;
            border-radius: 4px;
            font-size: 0.85rem;
            font-weight: 600;
            margin-left: 10px;
            display: inline-block;
        }

        .btn-actions {
            display: flex;
            gap: 8px;
            margin-top: 1rem;
            flex-wrap: wrap;
        }

        .btn-action {
            background-color: #6c757d;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            color: white;
            cursor: pointer;
            transition: all 0.2s ease;
            font-size: 0.85rem;
            flex: 1;
            min-width: 120px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }

        .btn-action:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
        }

        .btn-validar {
            background-color: #007bff;
            border: none;
            color: white;
            border-radius: 8px;
            padding: 0.5rem 1rem;
            font-weight: 500;
            font-size: 0.85rem;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            width: 100%;
        }

        .btn-validar:hover {
            background-color: #0069d9;
            transform: translateY(-1px);
            box-shadow: 0 2px 6px rgba(0, 123, 255, 0.3);
        }

        .btn-validar:active {
            background-color: #0056b3;
            transform: translateY(0);
        }
    </style>
</head>

<body>
    <header>
        <div class="container-fluid info text-center">
            <div class="row">
                <div class="col color-white h2 fw-bold pt-3 pb-2">
                    Validaciones pendientes
                </div>
            </div>
        </div>
    </header>

    <div class="container-fluid pb-5">
        <?php if (isset($_SESSION['validation_message'])): ?>
            <div class="alert alert-success alert-dismissible fade show m-3" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                <?php echo htmlspecialchars($_SESSION['validation_message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['validation_message']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['validation_error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show m-3" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>
                <?php echo htmlspecialchars($_SESSION['validation_error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['validation_error']); ?>
        <?php endif; ?>

        <ul class="nav nav-tabs" id="validationTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="pendientes-tab" data-bs-toggle="tab" data-bs-target="#pendientes" type="button" role="tab" aria-controls="pendientes" aria-selected="true">
                    <i class="fas fa-hourglass-half me-2"></i>Pendientes de validación
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="rechazados-tab" data-bs-toggle="tab" data-bs-target="#rechazados" type="button" role="tab" aria-controls="rechazados" aria-selected="false">
                    <i class="fas fa-times-circle me-2"></i>Rechazados
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="validados-tab" data-bs-toggle="tab" data-bs-target="#validados" type="button" role="tab" aria-controls="validados" aria-selected="false">
                    <i class="fas fa-check-circle me-2"></i>Validados
                </button>
            </li>
        </ul>

        <div class="tab-content" id="validationTabsContent">
            <div class="tab-pane fade show active" id="pendientes" role="tabpanel" aria-labelledby="pendientes-tab">
                <div class="row mt-3">
                    <?php if (empty($establecimientos)): ?>
                        <div class="no-establecimientos col-12">
                            <img src="../img/establecimiento.png" width="80" alt="Sin pendientes" class="mb-3">
                            <h3 class="fw-bold mb-3">No hay establecimientos pendientes de validación</h3>
                            <p class="text-muted">Todos los establecimientos han sido revisados. Los nuevos establecimientos aparecerán aquí cuando requieran validación.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($establecimientos as $index => $establecimiento):
                            $direccionFormateada = formatearDireccion(
                                $establecimiento['direccion'],
                                $establecimiento['piso']
                            );
                        ?>
                            <div class="col-12">
                                <div class="establecimiento-card" id="establecimiento-<?php echo $establecimiento['id']; ?>">
                                    <div class="card-header" style="background-image: url('<?php echo isset($establecimiento['image_url']) ? 'http://' . $establecimiento['image_url'] : '../img/default.jpg'; ?>');">
                                        <div class="card-header-overlay"></div>
                                        <div class="card-title">
                                            <div><?php echo htmlspecialchars($establecimiento['nombre']); ?></div>
                                            <div class="service-icons">
                                                <?php if ($establecimiento['has_wifi']): ?>
                                                    <div class="service-icon" title="WiFi disponible">
                                                        <i class="fas fa-wifi"></i>
                                                    </div>
                                                <?php endif; ?>

                                                <?php if ($establecimiento['has_parking']): ?>
                                                    <div class="service-icon" title="Parking disponible">
                                                        <i class="fas fa-parking"></i>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="card-body">
                                        <div class="info-row">
                                            <div class="info-icon"><i class="fas fa-map-marker-alt"></i></div>
                                            <div><?php echo htmlspecialchars($direccionFormateada); ?></div>
                                        </div>

                                        <div class="info-row">
                                            <div class="info-icon"><i class="fas fa-city"></i></div>
                                            <div><?php echo htmlspecialchars($establecimiento['localidad'] ?? ''); ?></div>
                                        </div>

                                        <button class="btn btn-toggle" onclick="toggleDetails('<?php echo $establecimiento['id']; ?>')">
                                            <span id="toggle-text-<?php echo $establecimiento['id']; ?>">Ver más detalles</span>
                                            <i class="fas fa-chevron-down" id="toggle-icon-<?php echo $establecimiento['id']; ?>"></i>
                                        </button>

                                        <div class="collapsed-content" id="details-<?php echo $establecimiento['id']; ?>">
                                            <?php if (!empty($establecimiento['descripcion'])): ?>
                                                <div class="info-row">
                                                    <div class="info-icon"><i class="fas fa-align-left"></i></div>
                                                    <div><strong>Descripción:</strong> <?php echo htmlspecialchars($establecimiento['descripcion']); ?></div>
                                                </div>
                                            <?php endif; ?>

                                            <?php if (!empty($establecimiento['provincia'])): ?>
                                                <div class="info-row">
                                                    <div class="info-icon"><i class="fas fa-map"></i></div>
                                                    <div><strong>Provincia:</strong> <?php echo htmlspecialchars($establecimiento['provincia']); ?></div>
                                                </div>
                                            <?php endif; ?>

                                            <?php if (!empty($establecimiento['codigo_postal'])): ?>
                                                <div class="info-row">
                                                    <div class="info-icon"><i class="fas fa-map-pin"></i></div>
                                                    <div><strong>Código Postal:</strong> <?php echo htmlspecialchars($establecimiento['codigo_postal']); ?></div>
                                                </div>
                                            <?php endif; ?>

                                            <?php if ($establecimiento['has_wifi']): ?>
                                                <div class="info-row">
                                                    <div class="info-icon"><i class="fas fa-wifi"></i></div>
                                                    <div>
                                                        <strong>WiFi disponible</strong>
                                                        <span class="precio-tag">
                                                            <i class="fas fa-euro-sign"></i> <?php echo number_format($establecimiento['wifi_price'] ?? 0, 2); ?>/hora
                                                        </span>
                                                    </div>
                                                </div>
                                            <?php endif; ?>

                                            <?php if ($establecimiento['has_parking']): ?>
                                                <div class="info-row">
                                                    <div class="info-icon"><i class="fas fa-parking"></i></div>
                                                    <div>
                                                        <strong>Parking disponible</strong>
                                                        <span class="precio-tag">
                                                            <i class="fas fa-euro-sign"></i> <?php echo number_format($establecimiento['parking_price'] ?? 0, 2); ?>/día
                                                        </span>
                                                    </div>
                                                </div>
                                            <?php endif; ?>

                                            <?php if (!empty($establecimiento['piso'])): ?>
                                                <div class="info-row">
                                                    <div class="info-icon"><i class="fas fa-building"></i></div>
                                                    <div><strong>Piso:</strong> <?php echo htmlspecialchars($establecimiento['piso']); ?></div>
                                                </div>
                                            <?php endif; ?>
                                        </div>

                                        <div class="btn-actions mt-3">
                                            <a href="validar.php?id=<?php echo $establecimiento['id']; ?>" class="btn btn-validar">
                                                <i class="fas fa-check-circle"></i> Revisar y validar
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="tab-pane fade" id="rechazados" role="tabpanel" aria-labelledby="rechazados-tab">
                <div class="row mt-3">
                    <?php if (empty($establecimientosRechazados)): ?>
                        <div class="no-establecimientos col-12">
                            <img src="../img/establecimiento.png" width="80" alt="Sin rechazados" class="mb-3">
                            <h3 class="fw-bold mb-3">No hay establecimientos rechazados</h3>
                            <p class="text-muted">Los establecimientos rechazados aparecerán en esta sección.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($establecimientosRechazados as $index => $establecimiento):
                            $direccionFormateada = formatearDireccion(
                                $establecimiento['direccion'],
                                $establecimiento['piso']
                            );
                        ?>
                            <div class="col-12">
                                <div class="establecimiento-card" id="establecimiento-<?php echo $establecimiento['id']; ?>" style="border-left: 4px solid #dc3545; opacity: 0.85;">
                                    <div class="card-header" style="background-image: url('<?php echo isset($establecimiento['image_url']) ? 'http://' . $establecimiento['image_url'] : '../img/default.jpg'; ?>');">
                                        <div class="card-header-overlay"></div>
                                        <div class="card-title">
                                            <div><?php echo htmlspecialchars($establecimiento['nombre']); ?></div>
                                            <div class="service-icons">
                                                <?php if ($establecimiento['has_wifi']): ?>
                                                    <div class="service-icon" title="WiFi disponible">
                                                        <i class="fas fa-wifi"></i>
                                                    </div>
                                                <?php endif; ?>

                                                <?php if ($establecimiento['has_parking']): ?>
                                                    <div class="service-icon" title="Parking disponible">
                                                        <i class="fas fa-parking"></i>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="card-body">
                                        <div class="info-row">
                                            <div class="info-icon"><i class="fas fa-map-marker-alt"></i></div>
                                            <div><?php echo htmlspecialchars($direccionFormateada); ?></div>
                                        </div>

                                        <div class="info-row">
                                            <div class="info-icon"><i class="fas fa-city"></i></div>
                                            <div><?php echo htmlspecialchars($establecimiento['localidad'] ?? ''); ?></div>
                                        </div>

                                        <div class="info-row mt-2">
                                            <div class="info-icon"><i class="fas fa-ban"></i></div>
                                            <div><strong>Estado:</strong> <span class="badge bg-danger">Rechazado</span></div>
                                        </div>

                                        <button class="btn btn-toggle" onclick="toggleDetails('<?php echo $establecimiento['id']; ?>')">
                                            <span id="toggle-text-<?php echo $establecimiento['id']; ?>">Ver más detalles</span>
                                            <i class="fas fa-chevron-down" id="toggle-icon-<?php echo $establecimiento['id']; ?>"></i>
                                        </button>

                                        <div class="collapsed-content" id="details-<?php echo $establecimiento['id']; ?>">
                                            <?php if (!empty($establecimiento['descripcion'])): ?>
                                                <div class="info-row">
                                                    <div class="info-icon"><i class="fas fa-align-left"></i></div>
                                                    <div><strong>Descripción:</strong> <?php echo htmlspecialchars($establecimiento['descripcion']); ?></div>
                                                </div>
                                            <?php endif; ?>

                                            <?php if (!empty($establecimiento['provincia'])): ?>
                                                <div class="info-row">
                                                    <div class="info-icon"><i class="fas fa-map"></i></div>
                                                    <div><strong>Provincia:</strong> <?php echo htmlspecialchars($establecimiento['provincia']); ?></div>
                                                </div>
                                            <?php endif; ?>

                                            <?php if (!empty($establecimiento['codigo_postal'])): ?>
                                                <div class="info-row">
                                                    <div class="info-icon"><i class="fas fa-map-pin"></i></div>
                                                    <div><strong>Código Postal:</strong> <?php echo htmlspecialchars($establecimiento['codigo_postal']); ?></div>
                                                </div>
                                            <?php endif; ?>

                                            <?php if ($establecimiento['has_wifi']): ?>
                                                <div class="info-row">
                                                    <div class="info-icon"><i class="fas fa-wifi"></i></div>
                                                    <div>
                                                        <strong>WiFi disponible</strong>
                                                        <span class="precio-tag">
                                                            <i class="fas fa-euro-sign"></i> <?php echo number_format($establecimiento['wifi_price'] ?? 0, 2); ?>/hora
                                                        </span>
                                                    </div>
                                                </div>
                                            <?php endif; ?>

                                            <?php if ($establecimiento['has_parking']): ?>
                                                <div class="info-row">
                                                    <div class="info-icon"><i class="fas fa-parking"></i></div>
                                                    <div>
                                                        <strong>Parking disponible</strong>
                                                        <span class="precio-tag">
                                                            <i class="fas fa-euro-sign"></i> <?php echo number_format($establecimiento['parking_price'] ?? 0, 2); ?>/día
                                                        </span>
                                                    </div>
                                                </div>
                                            <?php endif; ?>

                                            <?php if (!empty($establecimiento['piso'])): ?>
                                                <div class="info-row">
                                                    <div class="info-icon"><i class="fas fa-building"></i></div>
                                                    <div><strong>Piso:</strong> <?php echo htmlspecialchars($establecimiento['piso']); ?></div>
                                                </div>
                                            <?php endif; ?>
                                        </div>

                                        <p class="text-muted small mt-2"><i class="fas fa-info-circle me-1"></i>Este establecimiento ha sido rechazado y no puede ser publicado.</p>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="tab-pane fade" id="validados" role="tabpanel" aria-labelledby="validados-tab">
                <div class="row mt-3">
                    <?php if (empty($establecimientosValidados)): ?>
                        <div class="no-establecimientos col-12">
                            <img src="../img/establecimiento.png" width="80" alt="Sin validados" class="mb-3">
                            <h3 class="fw-bold mb-3">No hay establecimientos validados</h3>
                            <p class="text-muted">Los establecimientos validados aparecerán en esta sección.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($establecimientosValidados as $index => $establecimiento):
                            $direccionFormateada = formatearDireccion(
                                $establecimiento['direccion'],
                                $establecimiento['piso']
                            );
                        ?>
                            <div class="col-12">
                                <div class="establecimiento-card" id="establecimiento-<?php echo $establecimiento['id']; ?>" style="border-left: 4px solid #28a745;">
                                    <div class="card-header" style="background-image: url('<?php echo isset($establecimiento['image_url']) ? 'http://' . $establecimiento['image_url'] : '../img/default.jpg'; ?>');">
                                        <div class="card-header-overlay"></div>
                                        <div class="card-title">
                                            <div><?php echo htmlspecialchars($establecimiento['nombre']); ?></div>
                                            <div class="service-icons">
                                                <?php if ($establecimiento['has_wifi']): ?>
                                                    <div class="service-icon" title="WiFi disponible">
                                                        <i class="fas fa-wifi"></i>
                                                    </div>
                                                <?php endif; ?>

                                                <?php if ($establecimiento['has_parking']): ?>
                                                    <div class="service-icon" title="Parking disponible">
                                                        <i class="fas fa-parking"></i>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="card-body">
                                        <div class="info-row">
                                            <div class="info-icon"><i class="fas fa-map-marker-alt"></i></div>
                                            <div><?php echo htmlspecialchars($direccionFormateada); ?></div>
                                        </div>

                                        <div class="info-row">
                                            <div class="info-icon"><i class="fas fa-city"></i></div>
                                            <div><?php echo htmlspecialchars($establecimiento['localidad'] ?? ''); ?></div>
                                        </div>

                                        <div class="info-row mt-2">
                                            <div class="info-icon"><i class="fas fa-check-circle"></i></div>
                                            <div><strong>Estado:</strong> <span class="badge bg-success">Validado</span></div>
                                        </div>

                                        <button class="btn btn-toggle" onclick="toggleDetails('<?php echo $establecimiento['id']; ?>')">
                                            <span id="toggle-text-<?php echo $establecimiento['id']; ?>">Ver más detalles</span>
                                            <i class="fas fa-chevron-down" id="toggle-icon-<?php echo $establecimiento['id']; ?>"></i>
                                        </button>

                                        <div class="collapsed-content" id="details-<?php echo $establecimiento['id']; ?>">
                                            <?php if (!empty($establecimiento['descripcion'])): ?>
                                                <div class="info-row">
                                                    <div class="info-icon"><i class="fas fa-align-left"></i></div>
                                                    <div><strong>Descripción:</strong> <?php echo htmlspecialchars($establecimiento['descripcion']); ?></div>
                                                </div>
                                            <?php endif; ?>

                                            <?php if (!empty($establecimiento['provincia'])): ?>
                                                <div class="info-row">
                                                    <div class="info-icon"><i class="fas fa-map"></i></div>
                                                    <div><strong>Provincia:</strong> <?php echo htmlspecialchars($establecimiento['provincia']); ?></div>
                                                </div>
                                            <?php endif; ?>

                                            <?php if (!empty($establecimiento['codigo_postal'])): ?>
                                                <div class="info-row">
                                                    <div class="info-icon"><i class="fas fa-map-pin"></i></div>
                                                    <div><strong>Código Postal:</strong> <?php echo htmlspecialchars($establecimiento['codigo_postal']); ?></div>
                                                </div>
                                            <?php endif; ?>

                                            <?php if ($establecimiento['has_wifi']): ?>
                                                <div class="info-row">
                                                    <div class="info-icon"><i class="fas fa-wifi"></i></div>
                                                    <div>
                                                        <strong>WiFi disponible</strong>
                                                        <span class="precio-tag">
                                                            <i class="fas fa-euro-sign"></i> <?php echo number_format($establecimiento['wifi_price'] ?? 0, 2); ?>/hora
                                                        </span>
                                                    </div>
                                                </div>
                                            <?php endif; ?>

                                            <?php if ($establecimiento['has_parking']): ?>
                                                <div class="info-row">
                                                    <div class="info-icon"><i class="fas fa-parking"></i></div>
                                                    <div>
                                                        <strong>Parking disponible</strong>
                                                        <span class="precio-tag">
                                                            <i class="fas fa-euro-sign"></i> <?php echo number_format($establecimiento['parking_price'] ?? 0, 2); ?>/día
                                                        </span>
                                                    </div>
                                                </div>
                                            <?php endif; ?>

                                            <?php if (!empty($establecimiento['piso'])): ?>
                                                <div class="info-row">
                                                    <div class="info-icon"><i class="fas fa-building"></i></div>
                                                    <div><strong>Piso:</strong> <?php echo htmlspecialchars($establecimiento['piso']); ?></div>
                                                </div>
                                            <?php endif; ?>
                                        </div>

                                        <p class="text-muted small mt-2"><i class="fas fa-info-circle me-1"></i>Este establecimiento ha sido validado y está publicado.</p>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="container-fluid footer mt-5 p-3">
        <div class="row text-center fixed-bottom bg-blanco pt-1 px-2 footer-container">
            <label id="lbl_anf" class="col-2 text-center footer-item">
                <div class="row">
                    <a href="Anfitriones.php">
                        <div class="col-12 icon-container">
                            <i class="h2 fas fa-users p-1 m-0"></i>
                            <div>Anfitriones</div>
                        </div>
                    </a>
                </div>
            </label>
            <label id="lbl_val" class="col-2 text-center footer-item">
                <div class="row">
                    <a href="verValidar.php">
                        <div class="col-12 icon-container">
                            <i class="h2 fas fa-check-circle p-1 m-0"></i>
                            <div>Validar</div>
                        </div>
                    </a>
                </div>
            </label>
            <label id="lbl_res" class="col-2 text-center footer-item">
                <div class="row">
                    <a href="verReservas.php">
                        <div class="col-12 icon-container">
                            <i class="h2 fas fa-book-open p-1 m-0"></i>
                            <div>Reservas</div>
                        </div>
                    </a>
                </div>
            </label>
            <label id="lbl_his" class="col-2 text-center footer-item">
                <div class="row">
                    <a href="verEstablecimientos.php">
                        <div class="col-12 icon-container">
                            <i class="h2 fas fa-building p-1 m-0"></i>
                            <div>Establecimientos</div>
                        </div>
                    </a>
                </div>
            </label>
            <label id="lbl_esp" class="col-2 text-center footer-item">
                <div class="row">
                    <a href="verEspacios.php">
                        <div class="col-12 icon-container">
                            <i class="h2 fas fa-chair p-1 m-0"></i>
                            <div>Espacios</div>
                        </div>
                    </a>
                </div>
            </label>
            <label id="lbl_per" class="col-2 text-center footer-item">
                <div class="row">
                    <a href="tuPerfil.php">
                        <div class="col-12 icon-container">
                            <i class="h2 fas fa-user-tie p-1 m-0"></i>
                            <div>Perfil</div>
                        </div>
                    </a>
                </div>
            </label>
        </div>
    </div>

    <script>
        // Function to toggle establishment details
        function toggleDetails(id) {
            const detailsContent = document.getElementById('details-' + id);
            const toggleBtn = document.querySelector('[onclick="toggleDetails(\'' + id + '\')"]');
            const toggleIcon = document.getElementById('toggle-icon-' + id);
            
            if (detailsContent) {
                detailsContent.classList.toggle('show');
            }
            
            if (toggleBtn) {
                toggleBtn.classList.toggle('show');
            }
        }
    </script>

</body>

</html>