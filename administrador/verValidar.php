<?php
require_once 'verificar_sesion_admin.php'; // Asegurado de que usa tu verificador
require '../vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

//Función para renovar el token si expiró
function renewTokenIfExpired() {
    if (!isset($_SESSION['refresh_token'])) {
        return $_SESSION['token'] ?? null;
    }

    $refreshTokenUrl = 'http://' . $_ENV['SERVER_IP'] . ':' . $_ENV['DATABASE_PORT']
        . '/auth/v1/token?grant_type=refresh_token';
    
    $ch = curl_init($refreshTokenUrl);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['refresh_token' => $_SESSION['refresh_token']]));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'apikey: ' . $_ENV['DATABASE_APIKEY'],
        'Content-Type: application/json'
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200) {
        $result = json_decode($response, true);
        if (isset($result['access_token'])) {
            $_SESSION['token'] = $result['access_token'];
            if (isset($result['refresh_token'])) {
                $_SESSION['refresh_token'] = $result['refresh_token'];
            }
            return $result['access_token'];
        }
    }

    return $_SESSION['token'] ?? null;
}

// Renovar token antes de hacer peticiones
$validToken = renewTokenIfExpired();

$establecimientos = [];
$establecimientosRechazados = [];
$establecimientosValidados = [];
$error_db = null;
$cpGestor = null;

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

$gestorId = $_SESSION['user_id'] ?? null;

if ($gestorId) {
    $urlGestor = 'http://' . $_ENV['SERVER_IP'] . ':' . $_ENV['DATABASE_PORT']
        . '/rest/v1/gestor?select=codigo_postal&id=eq.' . urlencode((string) $gestorId);

    $chGestor = curl_init($urlGestor);
    curl_setopt_array($chGestor, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $_ENV['SERVICE_APIKEY'],
            'apikey: ' . $_ENV['SERVICE_APIKEY'],
        ],
    ]);
    $resGestor = curl_exec($chGestor);
    $httpCodeGestor = curl_getinfo($chGestor, CURLINFO_HTTP_CODE);
    curl_close($chGestor);

    if ($httpCodeGestor >= 200 && $httpCodeGestor < 300) {
        $datosGestor = json_decode($resGestor, true);
        if (is_array($datosGestor) && !empty($datosGestor)) {
            $cpGestor = $datosGestor[0]['codigo_postal'] ?? null;
        }
    }
}


// if (!empty($cpGestor)) { 
$url = 'http://' . $_ENV['SERVER_IP'] . ':' . $_ENV['DATABASE_PORT']
    . '/rest/v1/establecimiento'; //Cambio la URL para obtener todos los establecimientos sin filtrar por código postal.
$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'apikey: ' . $_ENV['SERVICE_APIKEY'],
        'Authorization: Bearer ' . $validToken,
    ],
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode >= 200 && $httpCode < 300) {
    $data = json_decode($response, true);
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
                return '"' . str_replace('"', '\\"', (string) $id) . '"';
            }, $ids);

            $urlGallery = 'http://' . $_ENV['SERVER_IP'] . ':' . $_ENV['DATABASE_PORT']
                . '/rest/v1/gallery?select=id,establecimiento_id,image_url&establecimiento_id=in.(' . implode(',', $idsFilter) . ')&order=establecimiento_id.asc,id.desc';

            $chGallery = curl_init($urlGallery);
            curl_setopt_array($chGallery, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => [
                    'apikey: ' . $_ENV['SERVICE_APIKEY'],
                    'Authorization: Bearer ' . $validToken,
                ],
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

                        // Con order por id.desc, la primera fila de cada establecimiento es la mas reciente.
                        if ($estId !== null && !isset($galleryByEstablecimiento[$estId]) && !empty($imgUrl)) {
                            $galleryByEstablecimiento[$estId] = $imgUrl;
                        }
                    }
                }
            }
        }

        foreach ($data as $est) {
            $idEst = $est['id'] ?? null;
            $banner = '';

            // Priorizamos gallery porque es donde se estan guardando los cambios desde editar.
            if ($idEst !== null && isset($galleryByEstablecimiento[$idEst])) {
                $banner = normalizarUrlImagen($galleryByEstablecimiento[$idEst]);
            }

            if (empty($banner)) {
                $banner = normalizarUrlImagen($est['image_url'] ?? '');
            }

            $est['banner_image_url'] = !empty($banner) ? $banner : '';

            // 1. Buscamos el valor independientemente de mayúsculas/minúsculas
            $val = $est['estaValidado'] ?? $est['estavalidado'] ?? null;

            // 2. Comprobación BLINDADA de Validados (True)
            if ($val === true || $val === 'true' || $val === 't' || $val === 1 || $val === '1') {
                $establecimientosValidados[] = $est;
            }
            // 3. Comprobación BLINDADA de Rechazados (False)
            elseif ($val === false || $val === 'false' || $val === 'f' || $val === 0 || $val === '0') {
                $establecimientosRechazados[] = $est;
            }
            // 4. Todo lo demás (NULL, vacíos) va a Pendientes
            else {
                $establecimientos[] = $est;
            }
        }
    }
} else {
    $error_db = 'Error al obtener los establecimientos.';
}
// } else {
//     $error_db = 'Tu perfil de gestor no tiene un código postal asignado. Actualiza tu perfil primero.';
// }

function formatearDireccion($dir, $piso)
{
    $result = htmlspecialchars($dir ?? '');
    if (!empty($piso))
        $result .= ' Piso ' . htmlspecialchars($piso);
    return $result;
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://kit.fontawesome.com/b8814a2854.js" crossorigin="anonymous"></script>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="icon" href="../favicon-color.png">
    <link rel="icon" href="../favicon-negro.png" media="(prefers-color-scheme: light)">
    <link rel="icon" href="../favicon-color.png" media="(prefers-color-scheme: dark)">
    <title>Validaciones Globales - Admin</title>
    <style>
        :root {
            --brand-ink: #1f2933;
            --brand-accent: #dc3545;
            /* Rojo admin */
            --brand-soft: #f8f9fa;
            --success-color: #28a745;
            --reject-color: #343a40;
            /* Gris oscuro para rechazar, contraste con el rojo principal */
            --card-radius: 16px;
            --bg: #f4f7fb;
            --accent-dark: #8c1c13;
            --accent-mid: #c44536;
        }

        body {
            font-family: 'Nunito', sans-serif;
            background: #eef2f5;
            color: var(--brand-ink);
            padding-bottom: 120px;
        }

        /* ESTILOS DEL HERO (CABECERA ADMIN) */
        .page-hero {
            max-width: 1400px;
            margin: 1.2rem auto 0.5rem;
            padding: 0 15px;
        }

        .page-hero-inner {
            border-radius: 20px;
            background: linear-gradient(135deg, var(--accent-dark) 0%, var(--accent-mid) 52%, #df786c 100%);
            color: #ffffff;
            padding: 1.1rem 1.2rem;
            box-shadow: 0 18px 40px rgba(140, 28, 19, 0.24);
            border: 1px solid rgba(255, 255, 255, 0.18);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .page-hero-title {
            font-size: 1.35rem;
            font-weight: 800;
            letter-spacing: 0.2px;
            margin: 0;
        }

        .info-hint-btn {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            border: 1px solid rgba(255, 255, 255, 0.45);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            background: rgba(255, 255, 255, 0.12);
            cursor: pointer;
            transition: 0.2s ease;
            font-size: 0.9rem;
        }

        .info-hint-btn:hover {
            background: rgba(255, 255, 255, 0.22);
            transform: translateY(-1px);
        }

        /* ESTILOS DE TABS (PESTAÑAS) */
        .nav-tabs {
            border-bottom: 2px solid #dee2e6;
            margin-bottom: 1rem;
        }

        .nav-tabs .nav-link {
            color: #6c757d;
            font-weight: 700;
            border: none;
            border-bottom: 3px solid transparent;
            padding: 12px 20px;
            transition: all 0.3s ease;
            background: transparent;
        }

        .nav-tabs .nav-link:hover {
            color: var(--brand-accent);
            border-bottom: 3px solid rgba(220, 53, 69, 0.3);
        }

        .nav-tabs .nav-link.active {
            color: var(--brand-accent);
            border-bottom: 3px solid var(--brand-accent);
            background: transparent;
        }

        /* CONTENEDOR PRINCIPAL */
        .validation-shell {
            max-width: 1400px;
            margin: 0 auto;
        }

        /* TARJETAS DE ESTABLECIMIENTOS */
        .establecimiento-card {
            background-color: white;
            border-radius: var(--card-radius);
            box-shadow: 0 10px 25px rgba(31, 41, 51, 0.06);
            margin-bottom: 1.5rem;
            overflow: hidden;
            transition: all 0.3s ease;
            border: 1px solid rgba(0, 0, 0, 0.05);
            display: flex;
            flex-direction: column;
            height: 100%;
        }

        .establecimiento-card:hover {
            box-shadow: 0 18px 36px rgba(31, 41, 51, 0.12);
            transform: translateY(-3px);
        }

        .card-header {
            height: 150px;
            background-size: cover;
            background-position: center;
            display: flex;
            align-items: flex-end;
            position: relative;
            background-color: #e9ecef;
        }

        .card-header.default-image {
            background-image: none !important;
            background-color: #dee2e6;
        }

        .card-header-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(to bottom, rgba(0, 0, 0, 0.1), rgba(0, 0, 0, 0.75));
        }

        .card-title {
            color: white;
            padding: 15px;
            font-weight: 700;
            font-size: 1.15rem;
            z-index: 1;
            width: 100%;
            margin: 0;
        }

        .card-body {
            padding: 20px;
            display: flex;
            flex-direction: column;
            flex: 1;
        }

        .info-row {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
            gap: 10px;
            font-size: 0.95rem;
            color: #495057;
        }

        .info-icon {
            color: var(--brand-accent);
            width: 20px;
            text-align: center;
            font-size: 1rem;
        }

        .badge {
            font-size: 0.85rem;
            padding: 5px 10px;
            border-radius: 8px;
        }

        /* BOTONES DE ACCIÓN */
        .action-buttons-container {
            margin-top: auto;
            padding-top: 15px;
            border-top: 1px solid #f1f3f5;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .quick-actions {
            display: flex;
            gap: 10px;
        }

        .btn-quick {
            flex: 1;
            border: none;
            border-radius: 8px;
            padding: 0.6rem;
            font-size: 0.9rem;
            font-weight: 700;
            color: white;
            transition: all 0.2s ease;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 5px;
        }

        .btn-quick.approve {
            background-color: var(--success-color);
        }

        .btn-quick.approve:hover {
            background-color: #218838;
            transform: translateY(-2px);
        }

        .btn-quick.reject {
            background-color: var(--reject-color);
        }

        .btn-quick.reject:hover {
            background-color: #23272b;
            transform: translateY(-2px);
        }

        .btn-quick:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none !important;
        }

        .btn-validar {
            background-color: transparent;
            border: 2px solid var(--brand-accent);
            color: var(--brand-accent);
            border-radius: 8px;
            padding: 0.6rem;
            font-weight: 700;
            font-size: 0.9rem;
            text-align: center;
            text-decoration: none;
            display: block;
            transition: all 0.2s ease;
        }

        .btn-validar:hover {
            background-color: var(--brand-accent);
            color: white;
        }

        /* MENSAJE VACÍO */
        .no-establecimientos {
            background-color: white;
            border-radius: 16px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            padding: 4rem 2rem;
            text-align: center;
            border: 1px dashed #ced4da;
            width: 100%;
            margin-top: 1rem;
        }

    </style>
</head>

<body>
    <section class="page-hero">
        <div class="page-hero-inner">
            <div class="hero-title-row">
                <div class="page-hero-title"><i class="fas fa-check-circle me-2"></i>Gestión Global de Validaciones</div>
            </div>
        </div>
    </section>

    <div class="container-fluid pb-5 px-3 px-md-4 validation-shell mt-3">
        <?php if (!empty($error_db)): ?>
            <div class="alert alert-warning border-0 shadow-sm rounded-3" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error_db); ?>
            </div>
        <?php endif; ?>

        <ul class="nav nav-tabs" id="validationTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="pendientes-tab" data-bs-toggle="tab" data-bs-target="#pendientes" type="button" role="tab">
                    <i class="fas fa-hourglass-half me-2"></i>Pendientes
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="rechazados-tab" data-bs-toggle="tab" data-bs-target="#rechazados" type="button" role="tab">
                    <i class="fas fa-times-circle me-2"></i>Rechazados
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="validados-tab" data-bs-toggle="tab" data-bs-target="#validados" type="button" role="tab">
                    <i class="fas fa-check-circle me-2"></i>Aprobados
                </button>
            </li>
        </ul>

        <div class="tab-content mt-4" id="validationTabsContent">

            <div class="tab-pane fade show active" id="pendientes" role="tabpanel">
                <div class="row" id="row-pendientes">
                    <div class="no-establecimientos" id="msg-no-pendientes"
                        style="<?php echo empty($establecimientos) ? 'display:block;' : 'display:none;'; ?>">
                        <img src="../img/establecimiento.png" width="80" alt="Sin pendientes" class="mb-3 opacity-50">
                        <h4 class="fw-bold mb-2 text-muted">No hay establecimientos pendientes</h4>
                        <p class="text-muted mb-0">Todo está al día en la plataforma.</p>
                    </div>

                    <?php foreach ($establecimientos as $establecimiento):
                        $direccionFormateada = formatearDireccion($establecimiento['direccion'], $establecimiento['piso']);
                    ?>
                        <div class="col-12 col-md-6 col-lg-4 mb-4 card-container"
                            id="col-est-<?php echo $establecimiento['id']; ?>">
                            <div class="establecimiento-card" id="card-<?php echo $establecimiento['id']; ?>">
                                <div class="card-header<?php echo empty($establecimiento['banner_image_url']) ? ' default-image' : ''; ?>"
                                    <?php if (!empty($establecimiento['banner_image_url'])): ?> style="background-image:
                                url('<?php echo htmlspecialchars($establecimiento['banner_image_url']); ?>');" <?php endif; ?>>
                                    <div class="card-header-overlay"></div>
                                    <div class="card-title">
                                        <?php echo htmlspecialchars($establecimiento['nombre']); ?>
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
                                    <div id="badge-container-<?php echo $establecimiento['id']; ?>">
                                        <div class="info-row mt-2">
                                            <div class="info-icon"><i class="fas fa-hourglass-half text-warning"></i>
                                            </div>
                                            <div><strong>Estado:</strong> <span
                                                    class="badge bg-warning text-dark border">Pendiente</span></div>
                                        </div>
                                    </div>

                                    <div class="action-buttons-container"
                                        id="action-container-<?php echo $establecimiento['id']; ?>">
                                        <a href="validar.php?id=<?php echo $establecimiento['id']; ?>"
                                            class="btn-validar mb-2"><i class="fas fa-search me-1"></i> Revisar completo</a>
                                        <div class="quick-actions" id="quick-actions-<?php echo $establecimiento['id']; ?>">
                                            <button class="btn-quick approve shadow-sm"
                                                onclick="procesarValidacionRapida('<?php echo $establecimiento['id']; ?>', 'aprobar', this)"><i
                                                    class="fas fa-check"></i> Aprobar</button>
                                            <button class="btn-quick reject shadow-sm"
                                                onclick="procesarValidacionRapida('<?php echo $establecimiento['id']; ?>', 'rechazar', this)"><i
                                                    class="fas fa-times"></i> Rechazar</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="tab-pane fade" id="rechazados" role="tabpanel">
                <div class="row" id="row-rechazados">
                    <div class="no-establecimientos" id="msg-no-rechazados"
                        style="<?php echo empty($establecimientosRechazados) ? 'display:block;' : 'display:none;'; ?>">
                        <h4 class="fw-bold mb-2 text-muted">No hay establecimientos rechazados</h4>
                    </div>

                    <?php foreach ($establecimientosRechazados as $establecimiento):
                        $direccionFormateada = formatearDireccion($establecimiento['direccion'], $establecimiento['piso']);
                    ?>
                        <div class="col-12 col-md-6 col-lg-4 mb-4 card-container"
                            id="col-est-<?php echo $establecimiento['id']; ?>">
                            <div class="establecimiento-card" id="card-<?php echo $establecimiento['id']; ?>"
                                style="border-left: 4px solid var(--brand-accent); opacity: 0.85;">
                                <div class="card-header<?php echo empty($establecimiento['banner_image_url']) ? ' default-image' : ''; ?>"
                                    <?php if (!empty($establecimiento['banner_image_url'])): ?> style="background-image:
                                    url('<?php echo htmlspecialchars($establecimiento['banner_image_url']); ?>');"
                                    <?php endif; ?>>
                                    <div class="card-header-overlay"></div>
                                    <div class="card-title">
                                        <?php echo htmlspecialchars($establecimiento['nombre']); ?>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="info-row">
                                        <div class="info-icon"><i class="fas fa-map-marker-alt text-muted"></i></div>
                                        <div><?php echo htmlspecialchars($direccionFormateada); ?></div>
                                    </div>
                                    <div class="info-row">
                                        <div class="info-icon"><i class="fas fa-city text-muted"></i></div>
                                        <div><?php echo htmlspecialchars($establecimiento['localidad'] ?? ''); ?>
                                        </div>
                                    </div>
                                    <div id="badge-container-<?php echo $establecimiento['id']; ?>">
                                        <div class="info-row mt-2">
                                            <div class="info-icon"><i class="fas fa-ban text-danger"></i></div>
                                            <div><strong>Estado:</strong> <span class="badge bg-danger">Rechazado</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="action-buttons-container"
                                        id="action-container-<?php echo $establecimiento['id']; ?>">
                                        <a href="validar.php?id=<?php echo $establecimiento['id']; ?>"
                                            class="btn-validar"><i class="fas fa-search me-1"></i> Ver detalle completo</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="tab-pane fade" id="validados" role="tabpanel">
                <div class="row" id="row-validados">
                    <div class="no-establecimientos" id="msg-no-validados"
                        style="<?php echo empty($establecimientosValidados) ? 'display:block;' : 'display:none;'; ?>">
                        <h4 class="fw-bold mb-2 text-muted">No hay establecimientos aprobados</h4>
                    </div>

                    <?php foreach ($establecimientosValidados as $establecimiento):
                        $direccionFormateada = formatearDireccion($establecimiento['direccion'], $establecimiento['piso']);
                    ?>
                        <div class="col-12 col-md-6 col-lg-4 mb-4 card-container"
                            id="col-est-<?php echo $establecimiento['id']; ?>">
                            <div class="establecimiento-card" id="card-<?php echo $establecimiento['id']; ?>"
                                style="border-left: 4px solid var(--success-color);">
                                <div class="card-header<?php echo empty($establecimiento['banner_image_url']) ? ' default-image' : ''; ?>"
                                    <?php if (!empty($establecimiento['banner_image_url'])): ?>
                                    style="background-image: url('<?php echo htmlspecialchars($establecimiento['banner_image_url']); ?>');"
                                    <?php endif; ?>>
                                    <div class="card-header-overlay"></div>
                                    <div class="card-title">
                                        <?php echo htmlspecialchars($establecimiento['nombre']); ?>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="info-row">
                                        <div class="info-icon"><i class="fas fa-map-marker-alt text-muted"></i></div>
                                        <div><?php echo htmlspecialchars($direccionFormateada); ?></div>
                                    </div>
                                    <div class="info-row">
                                        <div class="info-icon"><i class="fas fa-city text-muted"></i></div>
                                        <div>
                                            <?php echo htmlspecialchars($establecimiento['localidad'] ?? ''); ?>
                                        </div>
                                    </div>
                                    <div id="badge-container-<?php echo $establecimiento['id']; ?>">
                                        <div class="info-row mt-2">
                                            <div class="info-icon"><i class="fas fa-check-circle text-success"></i></div>
                                            <div><strong>Estado:</strong> <span class="badge bg-success">Aprobado</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="action-buttons-container"
                                        id="action-container-<?php echo $establecimiento['id']; ?>">
                                        <a href="validar.php?id=<?php echo $establecimiento['id']; ?>"
                                            class="btn-validar"><i class="fas fa-search me-1"></i> Ver detalle
                                            completo</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

        </div>
    </div>

     <?php include 'footerAdmin.php'; ?>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.forEach(function(el) {
                new bootstrap.Tooltip(el);
            });
        });

        function procesarValidacionRapida(id, accion, btnElement) {
            if (!confirm(accion === 'aprobar' ? '¿Aprobar y publicar este establecimiento?' : '¿Rechazar establecimiento de la plataforma?')) return;

            const originalText = btnElement.innerHTML;
            btnElement.innerHTML = '<i class="fas fa-spinner fa-spin"></i>...';
            btnElement.disabled = true;

            const formData = new FormData();
            formData.append('accion', accion);

            fetch('procesar_validacion.php?id=' + id + '&ajax=1', {
                    method: 'POST',
                    body: formData
                })
                .then(async response => {
                    const textoCrudo = await response.text();
                    try {
                        return JSON.parse(textoCrudo);
                    } catch (e) {
                        alert("⚠️ Error del servidor:\n\n" + textoCrudo.substring(0, 150));
                        throw new Error("Respuesta inválida");
                    }
                })
                .then(data => {
                    if (data.success) {
                        moverTarjetaEnDOM(id, accion);
                    } else {
                        alert('🚨 Error al guardar: ' + data.error);
                        btnElement.innerHTML = originalText;
                        btnElement.disabled = false;
                    }
                })
                .catch(err => {
                    console.error(err);
                    btnElement.innerHTML = originalText;
                    btnElement.disabled = false;
                });
        }

        function moverTarjetaEnDOM(id, accion) {
            const cleanId = String(id).trim();
            const colContenedor = document.getElementById('col-est-' + cleanId);
            const tarjeta = document.getElementById('card-' + cleanId);
            const botonesRapidos = document.getElementById('quick-actions-' + cleanId);
            const badgeContenedor = document.getElementById('badge-container-' + cleanId);

            if (!colContenedor || !tarjeta) return;

            // Quitamos los botones de Aprobar/Rechazar al moverse
            if (botonesRapidos) botonesRapidos.remove();

            if (accion === 'aprobar') {
                tarjeta.style.borderLeft = '4px solid #28a745';
                tarjeta.style.opacity = '1';
                badgeContenedor.innerHTML = '<div class="info-row mt-2"><div class="info-icon"><i class="fas fa-check-circle text-success"></i></div><div><strong>Estado:</strong> <span class="badge bg-success">Aprobado</span></div></div>';

                document.getElementById('row-validados').appendChild(colContenedor);
                const msgValidados = document.getElementById('msg-no-validados');
                if (msgValidados) msgValidados.style.display = 'none';

            } else {
                tarjeta.style.borderLeft = '4px solid #dc3545';
                tarjeta.style.opacity = '0.85';
                badgeContenedor.innerHTML = '<div class="info-row mt-2"><div class="info-icon"><i class="fas fa-ban text-danger"></i></div><div><strong>Estado:</strong> <span class="badge bg-danger">Rechazado</span></div></div>';

                document.getElementById('row-rechazados').appendChild(colContenedor);
                const msgRechazados = document.getElementById('msg-no-rechazados');
                if (msgRechazados) msgRechazados.style.display = 'none';
            }

            // Validar si la lista de pendientes quedó vacía para enseñar el mensaje
            const pendientesActivos = document.querySelectorAll('#row-pendientes .card-container');
            if (pendientesActivos.length === 0) {
                const msgPendientes = document.getElementById('msg-no-pendientes');
                if (msgPendientes) msgPendientes.style.display = 'block';
            }
        }
    </script>
</body>

</html>