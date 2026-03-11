<?php
session_start();
require_once 'verificar_sesion_gestor.php';
require '../vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

$espacio = null;
$mensaje = null;
$tipoMensaje = null;

// 1. SI SE ENVÍA EL FORMULARIO PRINCIPAL (Actualizar datos generales y horarios)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_space') {
    $espacioId = $_POST['id'];
    $scheduleId = $_POST['schedule_id'] ?? null;
    $errorActualizacion = false;

    // --- A. ACTUALIZAR HORARIOS (SCHEDULE) ---
    if (!empty($scheduleId)) {
        $monday = isset($_POST['weekday-mon']);
        $tuesday = isset($_POST['weekday-tue']);
        $wednesday = isset($_POST['weekday-wed']);
        $thursday = isset($_POST['weekday-thu']);
        $friday = isset($_POST['weekday-fri']);
        $saturday = isset($_POST['weekday-sat']);
        $sunday = isset($_POST['weekday-sun']);

        $horaEntrada = str_pad($_POST['horaEntrada'], 2, "0", STR_PAD_LEFT);
        $minutoEntrada = str_pad($_POST['minutoEntrada'], 2, "0", STR_PAD_LEFT);
        $horaSalida = str_pad($_POST['horaSalida'], 2, "0", STR_PAD_LEFT);
        $minutoSalida = str_pad($_POST['minutoSalida'], 2, "0", STR_PAD_LEFT);

        $dataSchedule = array(
            "start_time" => "$horaEntrada:$minutoEntrada:00",
            "end_time" => "$horaSalida:$minutoSalida:00",
            "has_monday" => $monday,
            "has_tuesday" => $tuesday,
            "has_wednesday" => $wednesday,
            "has_thursday" => $thursday,
            "has_friday" => $friday,
            "has_saturday" => $saturday,
            "has_sunday" => $sunday,
            "price" => isset($_POST['precio_hora']) && $_POST['precio_hora'] !== '' ? (float) $_POST['precio_hora'] : 0
        );

        $urlSchedule = "http://" . $_ENV['SERVER_IP'] . ":" . $_ENV['DATABASE_PORT'] . "/rest/v1/schedule?id=eq." . $scheduleId;
        $chSch = curl_init($urlSchedule);
        curl_setopt_array($chSch, array(
            CURLOPT_CUSTOMREQUEST => "PATCH",
            CURLOPT_POSTFIELDS => json_encode($dataSchedule),
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'apikey: ' . $_ENV['SERVICE_APIKEY'],
                'Authorization: Bearer ' . $_ENV['SERVICE_APIKEY'],
                'Prefer: return=representation'
            ),
            CURLOPT_RETURNTRANSFER => true,
        ));
        $resSch = curl_exec($chSch);
        $codSch = curl_getinfo($chSch, CURLINFO_HTTP_CODE);
        curl_close($chSch);

        if ($codSch >= 400) {
            $errorActualizacion = true;
            $errDecoded = json_decode($resSch, true);
            $mensaje = "Error al actualizar horario: " . ($errDecoded['message'] ?? 'Revisa los datos.');
            $tipoMensaje = "danger";
        }
    }

    // --- B. ACTUALIZAR DATOS DEL ESPACIO (SPACE) ---
    if (!$errorActualizacion) {
        $dataSpace = array(
            "name" => trim($_POST['name']),
            "description" => trim($_POST['description'])
            // Eliminados wifi, food, parking
        );

        $urlSpace = "http://" . $_ENV['SERVER_IP'] . ":" . $_ENV['DATABASE_PORT'] . "/rest/v1/space?id=eq." . $espacioId;
        $chSp = curl_init($urlSpace);
        curl_setopt_array($chSp, array(
            CURLOPT_CUSTOMREQUEST => "PATCH",
            CURLOPT_POSTFIELDS => json_encode($dataSpace),
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'apikey: ' . $_ENV['SERVICE_APIKEY'],
                'Authorization: Bearer ' . $_ENV['SERVICE_APIKEY'],
                'Prefer: return=representation'
            ),
            CURLOPT_RETURNTRANSFER => true,
        ));

        $resSpace = curl_exec($chSp);
        $codSpace = curl_getinfo($chSp, CURLINFO_HTTP_CODE);
        curl_close($chSp);

        if ($codSpace >= 200 && $codSpace < 300) {
            $mensaje = "El espacio y sus datos han sido actualizados correctamente.";
            $tipoMensaje = "success";
        } else {
            $errorDecoded = json_decode($resSpace, true);
            $msgBd = isset($errorDecoded['message']) ? $errorDecoded['message'] : $resSpace;
            $mensaje = "Error al actualizar (Cód $codSpace): " . htmlspecialchars($msgBd);
            $tipoMensaje = "danger";
        }
    }
}

// 2. LÓGICA DE SERVICIOS ADICIONALES (Añadir, Editar, Borrar) - POR AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] !== 'update_space') {
    header('Content-Type: application/json');
    $action = $_POST['action'];

    if ($action === 'add_service') {
        $data = [
            "schedule_id" => $_POST['schedule_id'],
            "name" => $_POST['name'],
            "description" => $_POST['description'],
            "price" => (float) $_POST['price']
        ];
        $url = "http://" . $_ENV['SERVER_IP'] . ":" . $_ENV['DATABASE_PORT'] . "/rest/v1/services";
        $method = "POST";
    } elseif ($action === 'edit_service') {
        $serviceId = $_POST['service_id'];
        $data = [
            "name" => $_POST['name'],
            "description" => $_POST['description'],
            "price" => (float) $_POST['price']
        ];
        $url = "http://" . $_ENV['SERVER_IP'] . ":" . $_ENV['DATABASE_PORT'] . "/rest/v1/services?id=eq." . $serviceId;
        $method = "PATCH";
    } elseif ($action === 'delete_service') {
        $serviceId = $_POST['service_id'];
        $url = "http://" . $_ENV['SERVER_IP'] . ":" . $_ENV['DATABASE_PORT'] . "/rest/v1/services?id=eq." . $serviceId;
        $method = "DELETE";
        $data = []; // No payload needed for delete
    }

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    if (!empty($data)) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'apikey: ' . $_ENV['SERVICE_APIKEY'],
        'Authorization: Bearer ' . $_ENV['SERVICE_APIKEY']
    ));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode >= 200 && $httpCode < 300) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Error en base de datos.']);
    }
    exit; // Stop execution for AJAX calls
}

// 3. OBTENER DATOS ACTUALES PARA MOSTRAR LA PÁGINA
if (isset($_GET['id']) || isset($_POST['id'])) {
    $idConsulta = $_GET['id'] ?? $_POST['id'];

    $urlGet = "http://" . $_ENV['SERVER_IP'] . ":" . $_ENV['DATABASE_PORT'] . "/rest/v1/space?id=eq." . $idConsulta . "&select=*,establecimiento(nombre),schedule(*,services(*))";

    $chGet = curl_init($urlGet);
    curl_setopt_array($chGet, array(
        CURLOPT_CUSTOMREQUEST => "GET",
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json',
            'apikey: ' . $_ENV['DATABASE_APIKEY']
        ),
        CURLOPT_RETURNTRANSFER => true,
    ));

    $resGet = curl_exec($chGet);
    $codGet = curl_getinfo($chGet, CURLINFO_HTTP_CODE);
    curl_close($chGet);

    if ($codGet === 200) {
        $datos = json_decode($resGet, true);
        if (!empty($datos)) {
            $espacio = $datos[0];
        }
    }
}

if (!$espacio) {
    die("<div style='font-family:sans-serif; text-align:center; margin-top:50px;'>
            <h3>Espacio no encontrado</h3>
            <p>No se ha proporcionado un ID válido o el espacio no existe.</p>
            <a href='verEspacios.php'>Volver a la lista</a>
         </div>");
}

// Extraer el horario y servicios para rellenar los campos
$schedule = !empty($espacio['schedule']) ? $espacio['schedule'][0] : null;
$servicios = $schedule && !empty($schedule['services']) ? $schedule['services'] : [];

// Preparar horas por defecto
$hEntrada = "09";
$mEntrada = "00";
$hSalida = "18";
$mSalida = "00";
$precioHora = 0;

if ($schedule) {
    if (!empty($schedule['start_time'])) {
        $partesEntrada = explode(":", $schedule['start_time']);
        $hEntrada = $partesEntrada[0];
        $mEntrada = $partesEntrada[1] ?? '00';
    }
    if (!empty($schedule['end_time'])) {
        $partesSalida = explode(":", $schedule['end_time']);
        $hSalida = $partesSalida[0];
        $mSalida = $partesSalida[1] ?? '00';
    }
    if (isset($schedule['price'])) {
        $precioHora = $schedule['price'];
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Espacio Avanzado</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://kit.fontawesome.com/b8814a2854.js" crossorigin="anonymous"></script>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;600;700&display=swap" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        body {
            font-family: 'Nunito', sans-serif;
            background-color: #f8f9fa;
        }

        .header-custom {
            background-color: #00B7CF;
            color: white;
            padding: 25px 20px;
            text-align: center;
            border-radius: 10px 10px 0 0;
            position: relative;
        }

        .form-container {
            max-width: 900px;
            margin: 3rem auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }

        .form-body {
            padding: 30px;
        }

        .btn-guardar {
            background-color: #00B7CF;
            border: none;
            color: white;
            font-weight: bold;
        }

        .btn-guardar:hover {
            background-color: #0093a8;
            color: white;
        }

        .badge-est {
            background-color: rgba(255, 255, 255, 0.2);
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.9rem;
            display: inline-block;
            margin-top: 10px;
        }

        .seccion-titulo {
            border-bottom: 2px solid #f0f0f0;
            padding-bottom: 10px;
            margin-bottom: 20px;
            margin-top: 30px;
            color: #00B7CF;
            font-weight: bold;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .day-checkbox {
            display: none;
        }

        .day-label {
            display: inline-block;
            width: 45px;
            height: 45px;
            line-height: 45px;
            text-align: center;
            border-radius: 50%;
            background-color: #e9ecef;
            color: #495057;
            cursor: pointer;
            font-weight: bold;
            transition: all 0.2s;
            user-select: none;
        }

        .day-checkbox:checked+.day-label {
            background-color: #28a745;
            color: white;
            box-shadow: 0 2px 5px rgba(40, 167, 69, 0.4);
        }

        .time-input {
            width: 70px;
            text-align: center;
        }

        .table-servicios th {
            background-color: #f8f9fa;
            color: #495057;
        }

        .toast-container {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 1055;
        }
    </style>
</head>

<body>

    <div class="container">
        <div class="form-container">
            <div class="header-custom">
                <h3 class="fw-bold m-0"><i class="fas fa-edit me-2"></i>Edición de Espacio (Gestor)</h3>
                <div class="badge-est">
                    <i class="fas fa-building me-1"></i>
                    <?php echo htmlspecialchars($espacio['establecimiento']['nombre'] ?? 'Establecimiento general'); ?>
                </div>
            </div>

            <div class="form-body">
                <?php if ($mensaje): ?>
                    <div class="alert alert-<?php echo $tipoMensaje; ?> text-center shadow-sm">
                        <?php echo $mensaje; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="editarEspacio.php?id=<?php echo htmlspecialchars($espacio['id']); ?>"
                    id="formMain">
                    <input type="hidden" name="id" value="<?php echo htmlspecialchars($espacio['id']); ?>">
                    <input type="hidden" name="action" value="update_space">
                    <input type="hidden" name="schedule_id"
                        value="<?php echo $schedule ? htmlspecialchars($schedule['id']) : ''; ?>" id="main_schedule_id">

                    <h5 class="seccion-titulo"><i class="fas fa-info-circle me-2"></i>Datos Generales</h5>
                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <label class="form-label fw-bold text-secondary">Nombre del Espacio</label>
                            <input type="text" class="form-control" name="name"
                                value="<?php echo htmlspecialchars($espacio['name'] ?? ''); ?>" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-bold text-secondary"><i class="fas fa-euro-sign me-1"></i>
                                Precio Base / Hora</label>
                            <input type="number" step="0.01" min="0" class="form-control text-success fw-bold"
                                name="precio_hora" value="<?php echo $precioHora; ?>" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold text-secondary">Descripción</label>
                        <textarea class="form-control" name="description" rows="3"
                            required><?php echo htmlspecialchars($espacio['description'] ?? ''); ?></textarea>
                    </div>

                    <?php if ($schedule): ?>
                        <h5 class="seccion-titulo"><i class="fas fa-calendar-alt me-2"></i>Horarios de Disponibilidad</h5>

                        <div class="mb-4 text-center">
                            <p class="text-muted small mb-2">Selecciona los días que el espacio estará abierto</p>
                            <div class="d-flex justify-content-center gap-2 flex-wrap">
                                <div>
                                    <input type="checkbox" id="mon" name="weekday-mon" class="day-checkbox" <?php echo !empty($schedule['has_monday']) ? 'checked' : ''; ?>>
                                    <label for="mon" class="day-label">L</label>
                                </div>
                                <div>
                                    <input type="checkbox" id="tue" name="weekday-tue" class="day-checkbox" <?php echo !empty($schedule['has_tuesday']) ? 'checked' : ''; ?>>
                                    <label for="tue" class="day-label">M</label>
                                </div>
                                <div>
                                    <input type="checkbox" id="wed" name="weekday-wed" class="day-checkbox" <?php echo !empty($schedule['has_wednesday']) ? 'checked' : ''; ?>>
                                    <label for="wed" class="day-label">X</label>
                                </div>
                                <div>
                                    <input type="checkbox" id="thu" name="weekday-thu" class="day-checkbox" <?php echo !empty($schedule['has_thursday']) ? 'checked' : ''; ?>>
                                    <label for="thu" class="day-label">J</label>
                                </div>
                                <div>
                                    <input type="checkbox" id="fri" name="weekday-fri" class="day-checkbox" <?php echo !empty($schedule['has_friday']) ? 'checked' : ''; ?>>
                                    <label for="fri" class="day-label">V</label>
                                </div>
                                <div>
                                    <input type="checkbox" id="sat" name="weekday-sat" class="day-checkbox" <?php echo !empty($schedule['has_saturday']) ? 'checked' : ''; ?>>
                                    <label for="sat" class="day-label">S</label>
                                </div>
                                <div>
                                    <input type="checkbox" id="sun" name="weekday-sun" class="day-checkbox" <?php echo !empty($schedule['has_sunday']) ? 'checked' : ''; ?>>
                                    <label for="sun" class="day-label">D</label>
                                </div>
                            </div>
                        </div>

                        <div class="row text-center mb-4">
                            <div class="col-6">
                                <label class="form-label fw-bold text-secondary d-block"><i
                                        class="fas fa-sign-in-alt text-success me-1"></i>Hora Apertura</label>
                                <div class="d-flex justify-content-center align-items-center gap-1">
                                    <input type="number" class="form-control time-input" name="horaEntrada" min="0" max="23"
                                        value="<?php echo $hEntrada; ?>" required>
                                    <span>:</span>
                                    <input type="number" class="form-control time-input" name="minutoEntrada" min="0"
                                        max="59" value="<?php echo $mEntrada; ?>" required>
                                </div>
                            </div>
                            <div class="col-6">
                                <label class="form-label fw-bold text-secondary d-block"><i
                                        class="fas fa-sign-out-alt text-danger me-1"></i>Hora Cierre</label>
                                <div class="d-flex justify-content-center align-items-center gap-1">
                                    <input type="number" class="form-control time-input" name="horaSalida" min="0" max="23"
                                        value="<?php echo $hSalida; ?>" required>
                                    <span>:</span>
                                    <input type="number" class="form-control time-input" name="minutoSalida" min="0"
                                        max="59" value="<?php echo $mSalida; ?>" required>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning mt-4">
                            <i class="fas fa-exclamation-triangle me-2"></i> Este espacio no tiene horarios asignados en la
                            base de datos, por lo que no pueden editarse.
                        </div>
                    <?php endif; ?>

                    <div class="d-flex justify-content-between mt-4">
                        <a href="verEspacios.php" class="btn btn-secondary px-4">
                            <i class="fas fa-arrow-left me-2"></i>Volver
                        </a>
                        <button type="submit" class="btn btn-guardar px-4">
                            <i class="fas fa-save me-2"></i>Guardar Espacio y Horarios
                        </button>
                    </div>
                </form>

                <?php if ($schedule): ?>
                    <h5 class="seccion-titulo mt-5">
                        <span><i class="fas fa-concierge-bell me-2"></i>Servicios Adicionales</span>
                        <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal"
                            data-bs-target="#modalAddService">
                            <i class="fas fa-plus me-1"></i> Añadir Servicio
                        </button>
                    </h5>

                    <div class="table-responsive">
                        <table class="table table-bordered table-hover table-servicios align-middle">
                            <thead>
                                <tr>
                                    <th>Nombre del Servicio</th>
                                    <th>Descripción</th>
                                    <th class="text-center" width="120">Precio (€)</th>
                                    <th class="text-center" width="100">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($servicios)): ?>
                                    <tr>
                                        <td colspan="4" class="text-center text-muted p-4">No hay servicios adicionales
                                            configurados para este espacio.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($servicios as $srv): ?>
                                        <tr id="row-service-<?php echo $srv['id']; ?>">
                                            <td class="fw-bold"><?php echo htmlspecialchars($srv['name']); ?></td>
                                            <td class="text-muted small"><?php echo htmlspecialchars($srv['description']); ?></td>
                                            <td class="text-center text-success fw-bold">
                                                <?php echo number_format($srv['price'], 2); ?>€</td>
                                            <td class="text-center">
                                                <div class="btn-group btn-group-sm">
                                                    <button type="button" class="btn btn-primary btn-edit-service"
                                                        data-id="<?php echo $srv['id']; ?>"
                                                        data-name="<?php echo htmlspecialchars($srv['name']); ?>"
                                                        data-desc="<?php echo htmlspecialchars($srv['description']); ?>"
                                                        data-price="<?php echo $srv['price']; ?>">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-danger btn-del-service"
                                                        data-id="<?php echo $srv['id']; ?>">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>

            </div>
        </div>
    </div>

    <div class="modal fade" id="modalAddService" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title"><i class="fas fa-plus-circle me-2"></i>Nuevo Servicio</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="formAddService">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Nombre del Servicio (Ej: Proyector, Catering...)</label>
                            <input type="text" class="form-control" id="add_srv_name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Descripción (Opcional)</label>
                            <textarea class="form-control" id="add_srv_desc" rows="2"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Precio Adicional (€)</label>
                            <input type="number" step="0.01" min="0" class="form-control" id="add_srv_price"
                                value="0.00" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-success" id="btnSaveNewService">Añadir Servicio</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalEditService" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Editar Servicio</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="formEditService">
                        <input type="hidden" id="edit_srv_id">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Nombre del Servicio</label>
                            <input type="text" class="form-control" id="edit_srv_name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Descripción</label>
                            <textarea class="form-control" id="edit_srv_desc" rows="2"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Precio Adicional (€)</label>
                            <input type="number" step="0.01" min="0" class="form-control" id="edit_srv_price" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" id="btnUpdateService">Guardar Cambios</button>
                </div>
            </div>
        </div>
    </div>

    <div class="toast-container">
        <div class="toast align-items-center text-white bg-success border-0" id="toastAjaxOk" role="alert"
            aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body"><i class="fas fa-check-circle me-2"></i> Operación realizada.</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"
                    aria-label="Close"></button>
            </div>
        </div>
        <div class="toast align-items-center text-white bg-danger border-0" id="toastAjaxErr" role="alert"
            aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body"><i class="fas fa-exclamation-circle me-2"></i> Error en la operación.</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"
                    aria-label="Close"></button>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        $(document).ready(function () {

            // Forzar formato dos dígitos al perder el foco (ej: 9 -> 09) en las horas
            $('.time-input').blur(function () {
                let val = $(this).val();
                if (val.length === 1) {
                    $(this).val('0' + val);
                } else if (val === '') {
                    $(this).val('00');
                }
            });

            // --- LÓGICA AJAX PARA SERVICIOS ---

            function showToast(isSuccess, msg) {
                if (isSuccess) {
                    $('#toastAjaxOk .toast-body').html('<i class="fas fa-check-circle me-2"></i> ' + msg);
                    new bootstrap.Toast(document.getElementById('toastAjaxOk')).show();
                } else {
                    $('#toastAjaxErr .toast-body').html('<i class="fas fa-exclamation-circle me-2"></i> ' + msg);
                    new bootstrap.Toast(document.getElementById('toastAjaxErr')).show();
                }
            }

            // AÑADIR SERVICIO
            $('#btnSaveNewService').click(function () {
                const name = $('#add_srv_name').val().trim();
                const desc = $('#add_srv_desc').val().trim();
                const price = $('#add_srv_price').val();
                const scheduleId = $('#main_schedule_id').val();

                if (!name) { alert("El nombre es obligatorio"); return; }

                $(this).prop('disabled', true).text('Guardando...');

                $.ajax({
                    url: 'editarEspacio.php',
                    type: 'POST',
                    data: {
                        action: 'add_service',
                        schedule_id: scheduleId,
                        name: name,
                        description: desc,
                        price: price
                    },
                    success: function (res) {
                        if (res.success) {
                            showToast(true, 'Servicio añadido. Recargando...');
                            setTimeout(() => location.reload(), 1000);
                        } else {
                            showToast(false, res.error);
                            $('#btnSaveNewService').prop('disabled', false).text('Añadir Servicio');
                        }
                    },
                    error: function () {
                        showToast(false, 'Error de conexión');
                        $('#btnSaveNewService').prop('disabled', false).text('Añadir Servicio');
                    }
                });
            });

            // ABRIR MODAL EDITAR SERVICIO
            $('.btn-edit-service').click(function () {
                $('#edit_srv_id').val($(this).data('id'));
                $('#edit_srv_name').val($(this).data('name'));
                $('#edit_srv_desc').val($(this).data('desc'));
                $('#edit_srv_price').val($(this).data('price'));
                new bootstrap.Modal(document.getElementById('modalEditService')).show();
            });

            // GUARDAR EDICIÓN DE SERVICIO
            $('#btnUpdateService').click(function () {
                const id = $('#edit_srv_id').val();
                const name = $('#edit_srv_name').val().trim();
                const desc = $('#edit_srv_desc').val().trim();
                const price = $('#edit_srv_price').val();

                if (!name) { alert("El nombre es obligatorio"); return; }
                $(this).prop('disabled', true).text('Actualizando...');

                $.ajax({
                    url: 'editarEspacio.php',
                    type: 'POST',
                    data: {
                        action: 'edit_service',
                        service_id: id,
                        name: name,
                        description: desc,
                        price: price
                    },
                    success: function (res) {
                        if (res.success) {
                            showToast(true, 'Servicio actualizado. Recargando...');
                            setTimeout(() => location.reload(), 1000);
                        } else {
                            showToast(false, res.error);
                            $('#btnUpdateService').prop('disabled', false).text('Guardar Cambios');
                        }
                    },
                    error: function () {
                        showToast(false, 'Error de conexión');
                        $('#btnUpdateService').prop('disabled', false).text('Guardar Cambios');
                    }
                });
            });

            // ELIMINAR SERVICIO
            $('.btn-del-service').click(function () {
                if (!confirm("¿Estás seguro de que deseas eliminar este servicio?")) return;

                const id = $(this).data('id');
                const btn = $(this);
                btn.prop('disabled', true);

                $.ajax({
                    url: 'editarEspacio.php',
                    type: 'POST',
                    data: {
                        action: 'delete_service',
                        service_id: id
                    },
                    success: function (res) {
                        if (res.success) {
                            showToast(true, 'Servicio eliminado.');
                            $(`#row-service-${id}`).fadeOut(400, function () { $(this).remove(); });
                        } else {
                            showToast(false, res.error);
                            btn.prop('disabled', false);
                        }
                    },
                    error: function () {
                        showToast(false, 'Error de conexión');
                        btn.prop('disabled', false);
                    }
                });
            });
        });
    </script>
</body>

</html>