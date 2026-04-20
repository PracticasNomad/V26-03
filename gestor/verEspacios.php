<?php
require_once 'verificar_sesion_gestor.php';
require '../vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

$tieneError = false;
$espacios = [];
$errorMsg = "";
$gestorId = $_SESSION["user_id"];

// Filtros URL
$filtro_establecimiento_id = $_GET['establecimiento_id'] ?? null;
$filtro_host_id = $_GET['host_id'] ?? null;

// Variables para los selects
$uniqueHosts = [];
$uniqueEstablecimientos = [];

// 1. OBTENER EL CÓDIGO POSTAL DEL GESTOR
$urlGestor = "http://" . $_ENV['SERVER_IP'] . ":" . $_ENV['DATABASE_PORT'] . "/rest/v1/gestor?select=codigo_postal&id=eq." . $gestorId;
$chGestor = curl_init($urlGestor);
curl_setopt_array($chGestor, [
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $_ENV['SERVICE_APIKEY'],
        'apikey: ' . $_ENV['SERVICE_APIKEY']
    ],
    CURLOPT_RETURNTRANSFER => true
]);
$resGestor = curl_exec($chGestor);
curl_close($chGestor);

$datosGestor = json_decode($resGestor, true);
$cpGestor = $datosGestor[0]['codigo_postal'] ?? null;

if ($cpGestor) {
    // 2. BUSCAR ESTABLECIMIENTOS Y ESPACIOS
    $url = "http://" . $_ENV['SERVER_IP'] . ":" . $_ENV['DATABASE_PORT'] . "/rest/v1/establecimiento?select=*,host(name),space(*,schedule(*,services(*)))&codigo_postal=eq." . urlencode($cpGestor);

    if ($filtro_establecimiento_id) {
        $url .= "&id=eq." . urlencode($filtro_establecimiento_id);
    } elseif ($filtro_host_id) {
        $url .= "&host_id=eq." . urlencode($filtro_host_id);
    }

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $_ENV['SERVICE_APIKEY'],
        'apikey: ' . $_ENV['SERVICE_APIKEY'],
        'Content-Type: application/json'
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);

    if ($err || $httpCode !== 200) {
        $tieneError = true;
        $errorMsg = $err ? $err : "Error HTTP: $httpCode";
    } else {
        $establecimientos = json_decode($response, true);
        if (is_array($establecimientos)) {
            foreach ($establecimientos as $est) {
                if (!empty($est['space'])) {
                    $hostName = $est['host']['name'] ?? 'Anfitrión desconocido';
                    $estName = $est['nombre'] ?? 'Establecimiento desconocido';

                    foreach ($est['space'] as $esp) {
                        $esp['establecimiento'] = [
                            'nombre' => $estName,
                            'host_name' => $hostName,
                            'image_url' => $est['image_url'] ?? null
                        ];
                        $espacios[] = $esp;
                    }

                    // Rellenar variables para los Selects
                    $uniqueHosts[$hostName] = $hostName;
                    $uniqueEstablecimientos[$estName] = $estName;
                }
            }
            sort($uniqueHosts);
            sort($uniqueEstablecimientos);
        }
    }
} else {
    $tieneError = true;
    $errorMsg = "Tu perfil de gestor no tiene un código postal asignado. Actualiza tu perfil primero.";
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width,initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://kit.fontawesome.com/b8814a2854.js" crossorigin="anonymous"></script>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;600;700&display=swap" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="icon" href="../favicon-color.png">
    <title>Espacios de tu Zona</title>
    <style>
        :root {
            --azul: #1976d2;
            --azul-dark: #0d47a1;
            --azul-light: #e3f0fb;
            --azul-mid: #bbdefb;
            --text: #1a2333;
            --muted: #5b7088;
            --danger: #c62828;
            --danger-dark: #8e0000;
            --warning: #ef6c00;
            --warning-dark: #d84315;
            --surface: #ffffff;
            --surface-alt: #f8fbff;
            --border: #d9e7f4;
        }

        body {
            font-family: 'Nunito', sans-serif;
            background: linear-gradient(158deg, #e9f3fd 0%, #f5f8ff 50%, #e9f5f1 100%);
            color: var(--text);
            padding-bottom: 15%;
            min-height: 100vh;
        }

        .page-header {
            max-width: 1320px;
            margin: 20px auto 10px;
            padding: 0 12px;
        }

        .page-header-inner {
            border-radius: 16px;
            background: linear-gradient(130deg, #123b49 0%, #0f4c5c 65%, #2a4b57 120%);
            color: #ffffff;
            padding: 18px 20px;
            box-shadow: 0 14px 28px rgba(15, 76, 92, 0.22);
        }

        .page-title {
            margin: 0;
            font-size: 1.32rem;
            font-weight: 800;
            letter-spacing: 0.45px;
            text-align: left;
        }

        .title-row {
            display: flex;
            align-items: center;
            justify-content: flex-start;
            gap: 10px;
            width: 100%;
        }

        .info-hint-btn {
            width: 30px;
            height: 30px;
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

        .contenedorLista {
            max-width: 1000px;
            margin: 2rem auto;
            background: var(--surface);
            border-radius: 18px;
            border: 1px solid var(--border);
            box-shadow: 0 12px 35px rgba(25, 118, 210, 0.12);
            padding: 2rem;
            position: relative;
        }

        /* BARRA DE BÚSQUEDA */
        .search-bar-wrapper {
            margin: 0 auto 2rem;
            width: 100%;
        }

        .search-bar-container {
            background: white;
            border-radius: 12px;
            padding: 5px 20px;
            display: flex;
            align-items: center;
            box-shadow: 0 4px 15px rgba(25, 118, 210, 0.08);
            border: 1px solid var(--border);
            transition: all 0.3s;
            height: 100%;
        }

        .search-bar-container:focus-within {
            box-shadow: 0 6px 20px rgba(25, 118, 210, 0.15);
            border-color: var(--azul);
        }

        .search-bar-icon {
            color: var(--azul);
            font-size: 1.2rem;
            margin-right: 15px;
        }

        .search-bar-input {
            border: none;
            box-shadow: none;
            font-size: 1.05rem;
            padding: 10px 0;
            background: transparent;
            width: 100%;
            color: var(--text);
        }

        .search-bar-input:focus {
            outline: none;
            box-shadow: none;
        }

        .filter-select {
            border-radius: 12px;
            border: 1px solid var(--border);
            box-shadow: 0 4px 15px rgba(25, 118, 210, 0.08);
            padding: 12px 15px;
            color: var(--text);
            font-weight: 600;
            height: 100%;
            transition: all 0.3s;
        }

        .filter-select:focus {
            border-color: var(--azul);
            box-shadow: 0 6px 20px rgba(25, 118, 210, 0.15);
            outline: none;
        }

        .header-container {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 0.7rem;
            border-bottom: 1px solid var(--border);
            padding-bottom: 1.15rem;
            margin-bottom: 2rem;
            text-align: center;
        }

        .section-title {
            color: var(--text);
            font-size: 1.12rem;
            letter-spacing: 0.35px;
            padding: 0.48rem 0.95rem;
            border-radius: 999px;
            background: linear-gradient(180deg, #f8fbff 0%, #eef5fd 100%);
            border: 1px solid var(--azul-mid);
            box-shadow: 0 6px 16px rgba(25, 118, 210, 0.10);
        }

        .cp-badge {
            margin-top: 2px;
            padding: 0.5rem 1rem;
            border-radius: 999px;
            border: 1px solid #9ec7ee;
            background: linear-gradient(180deg, #e9f3ff 0%, #ddecff 100%);
            color: #0f4c5c;
            font-weight: 700;
            font-size: 0.9rem;
            letter-spacing: 0.15px;
            box-shadow: 0 8px 16px rgba(25, 118, 210, 0.12);
        }

        .espacio-card {
            border: 1px solid var(--border);
            border-radius: 14px;
            margin-bottom: 1.5rem;
            box-shadow: 0 6px 20px rgba(25, 118, 210, 0.09);
            overflow: hidden;
            background: var(--surface);
            transition: opacity 0.3s, transform 0.22s ease, box-shadow 0.22s ease;
        }

        .espacio-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 24px rgba(25, 118, 210, 0.14);
        }

        .espacio-oculto {
            opacity: 0.72;
            filter: saturate(0.7);
            background-color: #f4f7fb;
        }

        .espacio-header {
            padding: 20px;
            background: linear-gradient(180deg, #f9fcff 0%, #f2f8fe 100%);
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .space-title {
            margin-bottom: 0.35rem;
            font-weight: 800;
            color: var(--azul-dark);
            font-size: 1.12rem;
        }

        .space-description {
            margin-bottom: 0;
            color: var(--muted);
            font-size: 0.9rem;
            max-width: 680px;
        }

        .establecimiento-badge {
            background: var(--azul-light);
            color: var(--azul-dark);
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: bold;
            display: inline-flex;
            align-items: center;
            margin-bottom: 8px;
            border: 1px solid var(--azul-mid);
        }

        .host-badge {
            background: #fff5f6;
            color: #c83a45;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: bold;
            display: inline-flex;
            align-items: center;
            margin-bottom: 8px;
            border: 1px solid #fbd6da;
            margin-left: 5px;
        }

        .horarios-container {
            padding: 20px;
            display: none;
            background: var(--surface);
            border-top: 1px dashed var(--border);
        }

        .horarios-title {
            color: var(--azul-dark);
            border-color: var(--azul-mid) !important;
        }

        .day-badge {
            display: inline-block;
            width: 30px;
            height: 30px;
            line-height: 30px;
            text-align: center;
            border-radius: 50%;
            margin-right: 5px;
            font-weight: bold;
            font-size: 0.85rem;
        }

        .day-active {
            background-color: #2e7d32;
            color: white;
            box-shadow: inset 0 -1px 0 rgba(0, 0, 0, 0.06);
        }

        .day-inactive {
            background-color: #cfd8dc;
            color: #546e7a;
            box-shadow: inset 0 -1px 0 rgba(0, 0, 0, 0.06);
        }

        .horario-item {
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            background: linear-gradient(180deg, #ffffff 0%, #fbfdff 100%);
            box-shadow: 0 4px 12px rgba(25, 118, 210, 0.06);
        }

        .servicio-item {
            background-color: #f8fbff;
            border-radius: 6px;
            padding: 10px;
            margin-top: 8px;
            border-left: 3px solid var(--azul);
        }

        .espacios-vacio {
            text-align: center;
            padding: 40px 20px;
            background: linear-gradient(180deg, #f8fbff 0%, #eff6fe 100%);
            border: 1px dashed var(--azul-mid);
            border-radius: 14px;
            color: var(--muted);
        }

        .empty-icon {
            color: #90a4ae;
        }

        .actions-group .btn {
            border-radius: 9px !important;
            font-weight: 700;
            letter-spacing: 0.1px;
        }

        .btn-horarios {
            border: 1px solid var(--azul);
            color: var(--azul);
            background: #ffffff;
        }

        .btn-horarios:hover,
        .btn-horarios-open {
            background: var(--azul);
            color: #ffffff;
        }

        .btn-vis-hide {
            background: #fff3e0;
            border: 1px solid #ffcc80;
            color: var(--warning-dark);
        }

        .btn-vis-hide:hover {
            background: #ffe0b2;
            color: var(--warning-dark);
        }

        .btn-vis-show {
            background: #eceff1;
            border: 1px solid #cfd8dc;
            color: #455a64;
        }

        .btn-vis-show:hover {
            background: #dfe5ea;
            color: #37474f;
        }

        .btn-edit-space {
            background: var(--azul);
            border: 1px solid var(--azul);
        }

        .btn-edit-space:hover {
            background: var(--azul-dark);
            border-color: var(--azul-dark);
        }

        .btn-delete-space {
            background: var(--danger);
            border: 1px solid var(--danger);
        }

        .btn-delete-space:hover {
            background: var(--danger-dark);
            border-color: var(--danger-dark);
        }

        .alert-modern {
            border-radius: 12px;
            border: 1px solid #ffccd1;
            background: #fff5f6;
            color: #8a1f2f;
            box-shadow: 0 5px 16px rgba(198, 40, 40, 0.1);
        }

        .modal-confirm .modal-content {
            border-radius: 16px;
            box-shadow: 0 12px 34px rgba(17, 24, 39, 0.2);
        }

        .modal-confirm .icon-box {
            width: 80px;
            height: 80px;
            margin: 0 auto;
            border-radius: 50%;
            z-index: 9;
            text-align: center;
            border: 3px solid #ef9a9a;
            background: #ffebee;
        }

        .modal-confirm .icon-box i {
            color: var(--danger);
            font-size: 46px;
            display: inline-block;
            margin-top: 13px;
        }

        .toast-container {
            position: fixed;
            top: 18px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 1050;
        }

        .btn-close:focus,
        .btn:focus {
            box-shadow: none !important;
        }

        @media (max-width: 768px) {
            .page-header {
                margin-top: 14px;
            }

            .page-header-inner {
                padding: 14px 12px;
            }

            .page-title {
                font-size: 1.12rem;
            }

            .title-row {
                gap: 8px;
            }

            .espacio-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .btn-group {
                margin-top: 15px;
                width: 100%;
                display: flex;
                flex-wrap: wrap;
                gap: 5px;
            }

            .btn-group .btn {
                flex: 1;
                border-radius: 5px !important;
                margin: 0 !important;
                font-size: 0.85rem;
            }

            .search-bar-wrapper .row>div {
                margin-bottom: 10px;
            }
        }
    </style>
</head>

<body>
    <header class="page-header">
        <div class="page-header-inner">
            <div class="title-row">
                <h1 class="page-title">Espacios de tu zona</h1>
                <span class="info-hint-btn" data-bs-toggle="tooltip" data-bs-placement="right"
                    title="Gestiona los espacios de tu zona, revisa horarios, visibilidad y acciones rápidas."><i
                        class="fas fa-info"></i></span>
            </div>
        </div>
    </header>

    <div class="contenedorLista mt-4">

        <?php if ($filtro_establecimiento_id || $filtro_host_id): ?>
            <div class="alert alert-info d-flex justify-content-between align-items-center mb-4"
                style="border-radius: 12px; background: #eef5fd; border: 1px solid var(--azul-mid); color: var(--azul-dark);">
                <div>
                    <i class="fas fa-filter me-2"></i> Mostrando solo los espacios del
                    <?php echo $filtro_establecimiento_id ? 'establecimiento' : 'anfitrión'; ?> seleccionado.
                </div>
                <a href="verEspacios.php" class="btn btn-sm btn-outline-primary fw-bold" style="border-radius: 20px;">
                    Ver todos
                </a>
            </div>
        <?php endif; ?>

        <div class="header-container flex-column">
            <h4 class="m-0 fw-bold text-center section-title">
                <i class="fas fa-chair me-2 text-primary"></i> Espacios Registrados
            </h4>
            <?php if ($cpGestor && !$tieneError): ?>
                <span class="cp-badge">Código Postal: <?php echo htmlspecialchars($cpGestor); ?></span>
            <?php endif; ?>
        </div>

        <?php if ($tieneError): ?>
            <div class="alert alert-modern" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i> <?php echo $errorMsg; ?>
            </div>
        <?php else: ?>

            <?php if (!empty($espacios)): ?>
                <div class="search-bar-wrapper">
                    <div class="row g-2 mb-3">
                        <div class="col-md-4">
                            <div class="search-bar-container h-100">
                                <i class="fas fa-search search-bar-icon"></i>
                                <input type="text" id="searchInputEsp" class="search-bar-input"
                                    placeholder="Buscar espacio o desc...">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <select id="filterEstEsp" class="form-select filter-select w-100">
                                <option value="">Todos los Establecimientos</option>
                                <?php foreach ($uniqueEstablecimientos as $est): ?>
                                    <option value="<?php echo htmlspecialchars($est); ?>"><?php echo htmlspecialchars($est); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <select id="filterHostEsp" class="form-select filter-select w-100">
                                <option value="">Todos los Anfitriones</option>
                                <?php foreach ($uniqueHosts as $hst): ?>
                                    <option value="<?php echo htmlspecialchars($hst); ?>"><?php echo htmlspecialchars($hst); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <div id="no-results-esp" class="espacios-vacio mt-4" style="display: none;">
                    <i class="fas fa-search fa-3x mb-3 empty-icon"></i>
                    <h4>Sin coincidencias</h4>
                    <p class="mb-0">No hemos encontrado ningún espacio que coincida con tus filtros.</p>
                </div>
            <?php endif; ?>

            <div id="espacios-container">
                <?php if (empty($espacios)): ?>
                    <div class="espacios-vacio">
                        <i class="fas fa-box-open fa-3x mb-3 empty-icon"></i>
                        <h4>Sin espacios encontrados</h4>
                        <p class="mb-0">No hay espacios registrados para la búsqueda actual.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($espacios as $espacio): ?>
                        <?php $esVisible = isset($espacio['visible']) ? $espacio['visible'] : true; ?>

                        <div class="espacio-card <?php echo $esVisible ? '' : 'espacio-oculto'; ?>"
                            id="card-<?php echo $espacio['id']; ?>"
                            data-est-name="<?php echo htmlspecialchars($espacio['establecimiento']['nombre']); ?>"
                            data-host-name="<?php echo htmlspecialchars($espacio['establecimiento']['host_name']); ?>">

                            <div class="espacio-header flex-wrap">
                                <div class="mb-2 mb-md-0">
                                    <div class="establecimiento-badge">
                                        <i class="fas fa-building me-1"></i>
                                        <?php echo htmlspecialchars($espacio['establecimiento']['nombre']); ?>
                                    </div>
                                    <div class="host-badge">
                                        <i class="fas fa-user-tie me-1"></i>
                                        <?php echo htmlspecialchars($espacio['establecimiento']['host_name']); ?>
                                    </div>

                                    <h5 class="space-title"><?php echo htmlspecialchars($espacio['name']); ?></h5>
                                    <p class="space-description"><?php echo htmlspecialchars($espacio['description']); ?></p>
                                </div>
                                <div class="btn-group actions-group">
                                    <button class="btn btn-sm toggle-horarios btn-horarios"
                                        data-espacio-id="<?php echo $espacio['id']; ?>">
                                        <i class="fas fa-clock me-1"></i> Horarios
                                    </button>
                                    <button
                                        class="btn btn-sm btn-toggle-visibilidad ms-1 <?php echo $esVisible ? 'btn-vis-hide' : 'btn-vis-show'; ?>"
                                        data-espacio-id="<?php echo $espacio['id']; ?>"
                                        data-visible="<?php echo $esVisible ? 'true' : 'false'; ?>">
                                        <i class="fas fa-eye<?php echo $esVisible ? '-slash' : ''; ?> me-1"></i>
                                        <?php echo $esVisible ? 'Ocultar' : 'Mostrar'; ?>
                                    </button>
                                    <a href="editarEspacio.php?id=<?php echo $espacio['id']; ?>"
                                        class="btn btn-sm btn-edit-space text-white ms-1">
                                        <i class="fas fa-edit me-1"></i> Editar
                                    </a>
                                    <button class="btn btn-sm btn-delete-space text-white btn-eliminar ms-1"
                                        data-espacio-id="<?php echo $espacio['id']; ?>"
                                        data-espacio-nombre="<?php echo htmlspecialchars($espacio['name']); ?>">
                                        <i class="fas fa-trash-alt me-1"></i> Eliminar
                                    </button>
                                </div>
                            </div>

                            <div class="horarios-container" id="horarios-<?php echo $espacio['id']; ?>">
                                <?php if (empty($espacio['schedule'])): ?>
                                    <div class="alert alert-secondary m-0">Este espacio no tiene horarios configurados.</div>
                                <?php else: ?>
                                    <h6 class="fw-bold mb-3 border-bottom pb-2 horarios-title">Configuración de Horarios y Precios</h6>
                                    <div class="row">
                                        <?php foreach ($espacio['schedule'] as $horario): ?>
                                            <div class="col-12 col-md-6">
                                                <div class="horario-item">
                                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                                        <div>
                                                            <span
                                                                class="day-badge <?php echo $horario['has_monday'] ? 'day-active' : 'day-inactive'; ?>">L</span>
                                                            <span
                                                                class="day-badge <?php echo $horario['has_tuesday'] ? 'day-active' : 'day-inactive'; ?>">M</span>
                                                            <span
                                                                class="day-badge <?php echo $horario['has_wednesday'] ? 'day-active' : 'day-inactive'; ?>">X</span>
                                                            <span
                                                                class="day-badge <?php echo $horario['has_thursday'] ? 'day-active' : 'day-inactive'; ?>">J</span>
                                                            <span
                                                                class="day-badge <?php echo $horario['has_friday'] ? 'day-active' : 'day-inactive'; ?>">V</span>
                                                            <span
                                                                class="day-badge <?php echo $horario['has_saturday'] ? 'day-active' : 'day-inactive'; ?>">S</span>
                                                            <span
                                                                class="day-badge <?php echo $horario['has_sunday'] ? 'day-active' : 'day-inactive'; ?>">D</span>
                                                        </div>
                                                    </div>

                                                    <div class="d-flex justify-content-between mb-2">
                                                        <div><i class="fas fa-hourglass-half text-primary me-2"></i><strong>Horas:</strong>
                                                            <?php echo substr($horario['start_time'], 0, 5); ?> -
                                                            <?php echo substr($horario['end_time'], 0, 5); ?></div>
                                                    </div>
                                                    <div class="d-flex justify-content-between mb-3">
                                                        <div><i class="fas fa-euro-sign text-success me-2"></i><strong>Precio:</strong>
                                                            <?php echo number_format($horario['price'], 2); ?>€/hora</div>
                                                    </div>

                                                    <?php if (!empty($horario['services'])): ?>
                                                        <div class="mt-3">
                                                            <strong class="text-secondary"><i class="fas fa-plus-circle me-1"></i> Servicios
                                                                Extras:</strong>
                                                            <?php foreach ($horario['services'] as $servicio): ?>
                                                                <div class="servicio-item">
                                                                    <div class="d-flex justify-content-between">
                                                                        <strong><?php echo htmlspecialchars($servicio['name']); ?></strong>
                                                                        <span
                                                                            class="badge bg-success"><?php echo number_format($servicio['price'], 2); ?>€</span>
                                                                    </div>
                                                                    <div class="small text-muted mt-1">
                                                                        <?php echo htmlspecialchars($servicio['description']); ?></div>
                                                                </div>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    <?php else: ?>
                                                        <div class="text-muted mt-2 small fst-italic"><i class="fas fa-info-circle me-1"></i> No
                                                            hay servicios adicionales</div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="modal fade modal-confirm" id="confirmModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 p-4">
                <div class="modal-header border-0 pb-0 position-relative">
                    <button type="button" class="btn-close position-absolute top-0 end-0" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body text-center pt-0">
                    <div class="icon-box mb-4"><i class="fas fa-trash-alt"></i></div>
                    <h4 class="mb-3 fw-bold">¿Estás seguro?</h4>
                    <p class="text-muted mb-0">¿Deseas eliminar el espacio <strong id="espacioNombre"
                            class="text-dark"></strong>?</p>
                    <p class="text-danger small mt-2">Esta acción no se puede deshacer.</p>
                </div>
                <div class="modal-footer border-0 d-flex justify-content-center gap-2">
                    <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-danger px-4" id="btnConfirmarEliminar">Sí, eliminar</button>
                </div>
            </div>
        </div>
    </div>

    <div class="toast-container">
        <div class="toast align-items-center text-white bg-success border-0" role="alert" aria-live="assertive"
            aria-atomic="true" id="toastExito">
            <div class="d-flex">
                <div class="toast-body" id="mensajeExito"><i class="fas fa-check-circle me-2"></i> Operación realizada.
                </div><button type="button" class="btn-close btn-close-white me-2 m-auto"
                    data-bs-dismiss="toast"></button>
            </div>
        </div>
        <div class="toast align-items-center text-white bg-danger border-0" role="alert" aria-live="assertive"
            aria-atomic="true" id="toastError">
            <div class="d-flex">
                <div class="toast-body" id="mensajeError"><i class="fas fa-exclamation-circle me-2"></i> Error en la
                    operación.</div><button type="button" class="btn-close btn-close-white me-2 m-auto"
                    data-bs-dismiss="toast"></button>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>

    <script>
        $(document).ready(function () {
            // Lógica Combinada (Buscador + Selects)
            function filterEspacios() {
                const searchTerm = $('#searchInputEsp').val().toLowerCase();
                const estTerm = $('#filterEstEsp').val().toLowerCase();
                const hostTerm = $('#filterHostEsp').val().toLowerCase();
                let visibleCount = 0;

                $('.espacio-card').each(function () {
                    const cardText = $(this).text().toLowerCase();
                    const cardEst = ($(this).data('est-name') || '').toLowerCase();
                    const cardHost = ($(this).data('host-name') || '').toLowerCase();

                    const matchesSearch = cardText.includes(searchTerm);
                    const matchesEst = estTerm === '' || cardEst === estTerm;
                    const matchesHost = hostTerm === '' || cardHost === hostTerm;

                    if (matchesSearch && matchesEst && matchesHost) {
                        $(this).show();
                        visibleCount++;
                    } else {
                        $(this).hide();
                    }
                });

                if (visibleCount === 0) {
                    $('#no-results-esp').show();
                    $('#espacios-container').hide();
                } else {
                    $('#no-results-esp').hide();
                    $('#espacios-container').show();
                }
            }

            $('#searchInputEsp').on('input', filterEspacios);
            $('#filterEstEsp, #filterHostEsp').on('change', filterEspacios);

            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });

            $('.toggle-horarios').click(function () {
                const espacioId = $(this).data('espacio-id');
                $(`#horarios-${espacioId}`).slideToggle();
                const icon = $(this).find('i');
                if (icon.hasClass('fa-clock')) {
                    icon.removeClass('fa-clock').addClass('fa-chevron-up');
                    $(this).addClass('btn-horarios-open');
                    $(this).html('<i class="fas fa-chevron-up me-1"></i> Ocultar');
                } else {
                    icon.removeClass('fa-chevron-up').addClass('fa-clock');
                    $(this).removeClass('btn-horarios-open');
                    $(this).html('<i class="fas fa-clock me-1"></i> Horarios');
                }
            });

            $('.btn-toggle-visibilidad').click(function () {
                const btn = $(this);
                const espacioId = btn.data('espacio-id');
                const esVisible = btn.data('visible') === true || btn.data('visible') === 'true';
                const nuevaVisibilidad = !esVisible;
                btn.prop('disabled', true);

                $.ajax({
                    url: 'toggleVisibilidadEspacio.php',
                    type: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify({ id: espacioId, visible: nuevaVisibilidad }),
                    success: function (response) {
                        if (response.success) {
                            if (nuevaVisibilidad) {
                                btn.removeClass('btn-vis-show').addClass('btn-vis-hide');
                                btn.html('<i class="fas fa-eye-slash me-1"></i> Ocultar');
                                $(`#card-${espacioId}`).removeClass('espacio-oculto');
                            } else {
                                btn.removeClass('btn-vis-hide').addClass('btn-vis-show');
                                btn.html('<i class="fas fa-eye me-1"></i> Mostrar');
                                $(`#card-${espacioId}`).addClass('espacio-oculto');
                            }
                            btn.data('visible', nuevaVisibilidad);
                        } else {
                            $('#mensajeError').text(response.error || 'Error al cambiar visibilidad');
                            new bootstrap.Toast(document.getElementById('toastError')).show();
                        }
                        btn.prop('disabled', false);
                    },
                    error: function () {
                        $('#mensajeError').text('Error de conexión con el servidor.');
                        new bootstrap.Toast(document.getElementById('toastError')).show();
                        btn.prop('disabled', false);
                    }
                });
            });

            let espacioIdAEliminar = null;
            $('.btn-eliminar').click(function () {
                espacioIdAEliminar = $(this).data('espacio-id');
                $('#espacioNombre').text($(this).data('espacio-nombre'));
                new bootstrap.Modal(document.getElementById('confirmModal')).show();
            });

            $('#btnConfirmarEliminar').click(function () {
                if (espacioIdAEliminar) {
                    bootstrap.Modal.getInstance(document.getElementById('confirmModal')).hide();
                    $.ajax({
                        url: 'eliminarEspacio.php',
                        type: 'POST',
                        data: { id: espacioIdAEliminar },
                        success: function (response) {
                            if (response.success) {
                                $('#mensajeExito').html('<i class="fas fa-check-circle me-2"></i> Espacio eliminado correctamente.');
                                new bootstrap.Toast(document.getElementById('toastExito')).show();
                                $(`#card-${espacioIdAEliminar}`).fadeOut(500, function () { $(this).remove(); });
                            } else {
                                $('#mensajeError').text(response.error || 'Error al eliminar');
                                new bootstrap.Toast(document.getElementById('toastError')).show();
                            }
                        },
                        error: function () {
                            $('#mensajeError').text('Error de conexión.');
                            new bootstrap.Toast(document.getElementById('toastError')).show();
                        }
                    });
                }
            });
        });
    </script>
</body>

</html>