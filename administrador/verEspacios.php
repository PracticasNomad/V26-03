<?php
require_once 'verificar_sesion_admin.php';
require '../vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

$tieneError = false;
$espacios = [];
$errorMsg = "";

// 1. CONSTRUIR CONSULTA A LA API (Con filtros si existen)
$queryParams = ["select=*,space(*,schedule(*,services(*)))"];

// Filtrar por ciudad (localidad) en el establecimiento
if (!empty($_GET['buscar_ciudad'])) {
    $queryParams[] = 'localidad=ilike.*' . rawurlencode(trim($_GET['buscar_ciudad'])) . '*';
}
// Filtrar por código postal en el establecimiento
if (!empty($_GET['buscar_cp'])) {
    $queryParams[] = 'codigo_postal=eq.' . rawurlencode(trim($_GET['buscar_cp']));
}
// Filtrar por ID de establecimiento (si vienes de hacer clic en "Ver Espacios" desde una tarjeta)
if (!empty($_GET['establecimiento_id'])) {
    $queryParams[] = 'id=eq.' . rawurlencode(trim($_GET['establecimiento_id']));
}

$url = "http://" . $_ENV['SERVER_IP'] . ":" . $_ENV['DATABASE_PORT'] . "/rest/v1/establecimiento?" . implode('&', $queryParams);

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

if ($err || $httpCode >= 300) {
    $tieneError = true;
    $errorMsg = $err ? $err : "Error HTTP: $httpCode al obtener datos.";
} else {
    $establecimientos = json_decode($response, true);
    $filtroNombre = !empty($_GET['buscar_nombre']) ? strtolower(trim($_GET['buscar_nombre'])) : '';

    if (is_array($establecimientos)) {
        foreach ($establecimientos as $est) {
            if (!empty($est['space'])) {
                foreach ($est['space'] as $esp) {
                    // Si hay filtro por nombre de espacio, comprobamos si coincide
                    if ($filtroNombre !== '' && strpos(strtolower($esp['name']), $filtroNombre) === false) {
                        continue;
                    }

                    $esp['establecimiento'] = [
                        'nombre' => $est['nombre'] ?? 'Establecimiento desconocido',
                        'localidad' => $est['localidad'] ?? 'Sin localidad',
                        'codigo_postal' => $est['codigo_postal'] ?? 'Sin CP'
                    ];
                    $espacios[] = $esp;
                }
            }
        }
    }
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
    <link rel="icon" href="../favicon-negro.png" media="(prefers-color-scheme: light)">
    <link rel="icon" href="../favicon-color.png" media="(prefers-color-scheme: dark)">
    <title>Espacios Globales - Admin</title>
    <style>
        :root {
            --primary-color: #dc3545;
            /* Rojo de administrador */
            --primary-hover: #b02a37;
        }

        body {
            font-family: 'Nunito', sans-serif;
            background-color: #f8f9fa;
            padding-bottom: 120px;
        }

        .contenedorLista {
            max-width: 1400px;
            margin: 2rem auto;
            padding: 0 15px;
        }

        .header-container {
            display: flex;
            justify-content: center;
            align-items: center;
            border-bottom: 2px solid #f0f0f0;
            padding-bottom: 1rem;
            margin-bottom: 2rem;
        }

        .espacio-card {
            border: 1px solid #ced4da;
            border-radius: 15px;
            margin-bottom: 1.5rem;
            box-shadow: 0 .25rem .5rem rgba(0, 0, 0, .05);
            overflow: hidden;
            background-color: white;
            transition: all 0.3s ease;
        }

        .espacio-card:hover {
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }

        .espacio-oculto {
            opacity: 0.6;
            background-color: #f1f1f1;
        }

        .espacio-header {
            padding: 20px;
            background-color: #ffffff;
            border-bottom: 1px solid #f0f0f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .establecimiento-badge {
            background-color: #e9ecef;
            color: #495057;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: bold;
            display: inline-flex;
            align-items: center;
            margin-bottom: 10px;
        }

        .horarios-container {
            padding: 20px;
            display: none;
            background-color: #fafafa;
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
            background-color: #28a745;
            color: white;
        }

        .day-inactive {
            background-color: #dc3545;
            color: white;
        }

        .horario-item {
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            background-color: #ffffff;
        }

        .servicio-item {
            background-color: #f8f9fa;
            border-radius: 6px;
            padding: 10px;
            margin-top: 8px;
            border-left: 3px solid var(--primary-color);
        }

        .espacios-vacio {
            text-align: center;
            padding: 60px 20px;
            background-color: white;
            border-radius: 15px;
            color: #6c757d;
            box-shadow: 0 .25rem .5rem rgba(0, 0, 0, .05);
        }

        @media (max-width: 768px) {
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
                border-radius: 8px !important;
                margin: 0 !important;
                font-size: 0.85rem;
            }
        }

        /* Modales y Toasts */
        .modal-confirm .icon-box {
            width: 80px;
            height: 80px;
            margin: 0 auto;
            border-radius: 50%;
            z-index: 9;
            text-align: center;
            border: 3px solid #f15e5e;
        }

        .modal-confirm .icon-box i {
            color: #f15e5e;
            font-size: 46px;
            display: inline-block;
            margin-top: 13px;
        }

        .toast-container {
            position: fixed;
            bottom: 80px;
            right: 20px;
            z-index: 1050;
        }

        /* Menú Footer Admin */
        .footer {
            color: black;
            background-color: white;
            width: 100%;
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
        }

        .footer-item {
            padding: 8px 0;
            color: black;
            text-decoration: none;
            font-size: 0.8rem;
        }

        .icon-container {
            transition: transform 0.3s ease, color 0.3s ease;
            padding: 5px 0;
        }

        .footer-item:hover .icon-container {
            transform: translateY(-7px);
            color: var(--primary-color);
        }

        a {
            text-decoration: none;
            color: inherit;
        }
    </style>
</head>

<body>
    <header>
        <div class="container-fluid info text-center" style="background-color: var(--primary-color); color: white;">
            <div class="row">
                <div class="col h3 fw-bold pt-3 pb-2 m-0">
                    Gestión Global de Espacios
                </div>
            </div>
        </div>
    </header>

    <div class="contenedorLista">

        <div class="card shadow-sm border-0 mb-4" style="border-radius: 15px;">
            <div class="card-body p-4">
                <h5 class="fw-bold mb-3"><i class="fas fa-filter text-danger"></i> Buscar y Filtrar Espacios</h5>
                <form method="GET" action="verEspacios.php" class="row g-3">
                    <?php if (!empty($_GET['establecimiento_id'])): ?>
                        <input type="hidden" name="establecimiento_id"
                            value="<?php echo htmlspecialchars($_GET['establecimiento_id']); ?>">
                    <?php endif; ?>

                    <div class="col-md-4">
                        <label class="form-label text-muted">Nombre del Espacio</label>
                        <input type="text" class="form-control" name="buscar_nombre" placeholder="Ej. Sala de Reuniones"
                            value="<?php echo htmlspecialchars($_GET['buscar_nombre'] ?? ''); ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label text-muted">Ciudad / Localidad</label>
                        <input type="text" class="form-control" name="buscar_ciudad" placeholder="Ej. Madrid"
                            value="<?php echo htmlspecialchars($_GET['buscar_ciudad'] ?? ''); ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label text-muted">Código Postal</label>
                        <input type="text" class="form-control" name="buscar_cp" placeholder="Ej. 28001"
                            value="<?php echo htmlspecialchars($_GET['buscar_cp'] ?? ''); ?>">
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-danger w-100" style="border-radius: 8px;"><i
                                class="fas fa-search"></i> Filtrar</button>
                    </div>
                </form>
            </div>
        </div>

        <?php if ($tieneError): ?>
            <div class="alert alert-danger shadow-sm rounded-pill text-center" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <?php echo $errorMsg; ?>
            </div>
        <?php else: ?>
            <div id="espacios-container">
                <?php if (empty($espacios)): ?>
                    <div class="espacios-vacio">
                        <i class="fas fa-box-open fa-3x mb-3 text-muted"></i>
                        <h4>No se encontraron espacios</h4>
                        <p class="mb-3">No hay resultados para la búsqueda actual o no hay espacios registrados.</p>
                        <?php if (!empty($_GET)): ?>
                            <a href="verEspacios.php" class="btn btn-outline-danger mt-2 px-4 rounded-pill">Limpiar Filtros</a>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="mb-3 text-muted">
                        <i class="fas fa-list"></i> Mostrando <strong>
                            <?php echo count($espacios); ?>
                        </strong> espacio(s).
                    </div>

                    <?php foreach ($espacios as $espacio): ?>
                        <?php $esVisible = isset($espacio['visible']) ? $espacio['visible'] : true; ?>

                        <div class="espacio-card <?php echo $esVisible ? '' : 'espacio-oculto'; ?>"
                            id="card-<?php echo $espacio['id']; ?>">
                            <div class="espacio-header flex-wrap">
                                <div class="mb-2 mb-md-0">
                                    <div class="establecimiento-badge">
                                        <i class="fas fa-building me-2"></i>
                                        <?php echo htmlspecialchars($espacio['establecimiento']['nombre']); ?>
                                        <span class="text-muted fw-normal ms-1">(
                                            <?php echo htmlspecialchars($espacio['establecimiento']['localidad'] . ' - ' . $espacio['establecimiento']['codigo_postal']); ?>)
                                        </span>
                                    </div>
                                    <h5 class="mb-1 fw-bold text-dark">
                                        <?php echo htmlspecialchars($espacio['name']); ?>
                                    </h5>
                                    <p class="mb-0 text-muted small">
                                        <?php echo htmlspecialchars($espacio['description']); ?>
                                    </p>
                                </div>
                                <div class="btn-group">
                                    <button class="btn btn-outline-dark btn-sm toggle-horarios fw-bold rounded-start"
                                        data-espacio-id="<?php echo $espacio['id']; ?>">
                                        <i class="fas fa-clock me-1"></i> Horarios
                                    </button>

                                    <button
                                        class="btn btn-<?php echo $esVisible ? 'warning' : 'secondary'; ?> btn-sm btn-toggle-visibilidad fw-bold ms-1"
                                        data-espacio-id="<?php echo $espacio['id']; ?>"
                                        data-visible="<?php echo $esVisible ? 'true' : 'false'; ?>">
                                        <i class="fas fa-eye<?php echo $esVisible ? '-slash' : ''; ?> me-1"></i>
                                        <?php echo $esVisible ? 'Ocultar' : 'Mostrar'; ?>
                                    </button>

                                    <a href="editarEspacio.php?id=<?php echo $espacio['id']; ?>"
                                        class="btn btn-info text-white btn-sm fw-bold ms-1">
                                        <i class="fas fa-edit me-1"></i> Editar
                                    </a>

                                    <button class="btn btn-danger btn-sm btn-eliminar fw-bold ms-1 rounded-end"
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
                                    <h6 class="fw-bold mb-3 border-bottom pb-2">Configuración de Horarios y Precios</h6>
                                    <div class="row">
                                        <?php foreach ($espacio['schedule'] as $horario): ?>
                                            <div class="col-12 col-md-6">
                                                <div class="horario-item shadow-sm">
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
                                                            <?php echo substr($horario['end_time'], 0, 5); ?>
                                                        </div>
                                                    </div>
                                                    <div class="d-flex justify-content-between mb-3">
                                                        <div><i class="fas fa-euro-sign text-success me-2"></i><strong>Precio:</strong>
                                                            <?php echo number_format($horario['price'], 2); ?>€/hora
                                                        </div>
                                                    </div>

                                                    <?php if (!empty($horario['services'])): ?>
                                                        <div class="mt-3">
                                                            <strong class="text-secondary"><i class="fas fa-plus-circle me-1"></i> Servicios
                                                                Extras:</strong>
                                                            <?php foreach ($horario['services'] as $servicio): ?>
                                                                <div class="servicio-item">
                                                                    <div class="d-flex justify-content-between">
                                                                        <strong>
                                                                            <?php echo htmlspecialchars($servicio['name']); ?>
                                                                        </strong>
                                                                        <span class="badge bg-success">
                                                                            <?php echo number_format($servicio['price'], 2); ?>€
                                                                        </span>
                                                                    </div>
                                                                    <div class="small text-muted mt-1">
                                                                        <?php echo htmlspecialchars($servicio['description']); ?>
                                                                    </div>
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
                    <div class="icon-box mb-4">
                        <i class="fas fa-trash-alt"></i>
                    </div>
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
                <div class="toast-body" id="mensajeExito">
                    <i class="fas fa-check-circle me-2"></i> Operación realizada.
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
        <div class="toast align-items-center text-white bg-danger border-0" role="alert" aria-live="assertive"
            aria-atomic="true" id="toastError">
            <div class="d-flex">
                <div class="toast-body" id="mensajeError">
                    <i class="fas fa-exclamation-circle me-2"></i> Error en la operación.
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    </div>

    <div class="container-fluid footer mt-5 p-3">
        <div class="row text-center fixed-bottom bg-blanco pt-1 px-2 footer-container">
            <a href="dashboard.php" class="col-2 text-center footer-item">
                <div class="row">
                    <div class="col-12 icon-container"><i class="h3 fas fa-chart-line p-1 m-0"></i>
                        <div>Panel</div>
                    </div>
                </div>
            </a>
            <a href="verGestores.php" class="col-2 text-center footer-item">
                <div class="row">
                    <div class="col-12 icon-container"><i class="h3 fas fa-user-tie p-1 m-0"></i>
                        <div>Gestores</div>
                    </div>
                </div>
            </a>
            <a href="verAnfitriones.php" class="col-2 text-center footer-item">
                <div class="row">
                    <div class="col-12 icon-container"><i class="h3 fas fa-users p-1 m-0"></i>
                        <div>Anfitriones</div>
                    </div>
                </div>
            </a>
            <a href="verEstablecimientos.php" class="col-2 text-center footer-item">
                <div class="row">
                    <div class="col-12 icon-container" style="color:var(--primary-color);"><i
                            class="h3 fas fa-building p-1 m-0"></i>
                        <div>Establecimientos</div>
                    </div>
                </div>
            </a>
            <a href="verValidar.php" class="col-2 text-center footer-item">
                <div class="row">
                    <div class="col-12 icon-container"><i class="h3 fas fa-check-circle p-1 m-0"></i>
                        <div>Validar</div>
                    </div>
                </div>
            </a>
            <a href="tuPerfil.php" class="col-2 text-center footer-item">
                <div class="row">
                    <div class="col-12 icon-container"><i class="h3 fas fa-user-cog p-1 m-0"></i>
                        <div>Perfil</div>
                    </div>
                </div>
            </a>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            $('.toggle-horarios').click(function() {
                const espacioId = $(this).data('espacio-id');
                $(`#horarios-${espacioId}`).slideToggle();
                const icon = $(this).find('i');
                if (icon.hasClass('fa-clock')) {
                    icon.removeClass('fa-clock').addClass('fa-chevron-up');
                    $(this).removeClass('btn-outline-dark').addClass('btn-dark text-white');
                    $(this).html('<i class="fas fa-chevron-up me-1"></i> Ocultar');
                } else {
                    icon.removeClass('fa-chevron-up').addClass('fa-clock');
                    $(this).removeClass('btn-dark text-white').addClass('btn-outline-dark');
                    $(this).html('<i class="fas fa-clock me-1"></i> Horarios');
                }
            });

            $('.btn-toggle-visibilidad').click(function() {
                const btn = $(this);
                const espacioId = btn.data('espacio-id');
                const esVisible = btn.data('visible') === true || btn.data('visible') === 'true';
                const nuevaVisibilidad = !esVisible;
                btn.prop('disabled', true);

                $.ajax({
                    url: 'toggleVisibilidadEspacio.php',
                    type: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify({
                        id: espacioId,
                        visible: nuevaVisibilidad
                    }),
                    success: function(response) {
                        if (response.success) {
                            if (nuevaVisibilidad) {
                                btn.removeClass('btn-secondary').addClass('btn-warning');
                                btn.html('<i class="fas fa-eye-slash me-1"></i> Ocultar');
                                $(`#card-${espacioId}`).removeClass('espacio-oculto');
                            } else {
                                btn.removeClass('btn-warning').addClass('btn-secondary');
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
                    error: function() {
                        $('#mensajeError').text('Error de conexión con el servidor.');
                        new bootstrap.Toast(document.getElementById('toastError')).show();
                        btn.prop('disabled', false);
                    }
                });
            });

            let espacioIdAEliminar = null;
            $('.btn-eliminar').click(function() {
                espacioIdAEliminar = $(this).data('espacio-id');
                $('#espacioNombre').text($(this).data('espacio-nombre'));
                new bootstrap.Modal(document.getElementById('confirmModal')).show();
            });

            $('#btnConfirmarEliminar').click(function() {
                if (espacioIdAEliminar) {
                    bootstrap.Modal.getInstance(document.getElementById('confirmModal')).hide();
                    $.ajax({
                        url: 'eliminarEspacio.php',
                        type: 'POST',
                        data: {
                            id: espacioIdAEliminar
                        },
                        success: function(response) {
                            if (response.success) {
                                $('#mensajeExito').html('<i class="fas fa-check-circle me-2"></i> Espacio eliminado correctamente.');
                                new bootstrap.Toast(document.getElementById('toastExito')).show();
                                $(`#card-${espacioIdAEliminar}`).fadeOut(500, function() {
                                    $(this).remove();
                                });
                            } else {
                                $('#mensajeError').text(response.error || 'Error al eliminar');
                                new bootstrap.Toast(document.getElementById('toastError')).show();
                            }
                        },
                        error: function() {
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