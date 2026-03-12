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
    // 2. BUSCAR ESTABLECIMIENTOS DE ESE CP Y SUS ESPACIOS
    $url = "http://" . $_ENV['SERVER_IP'] . ":" . $_ENV['DATABASE_PORT'] . "/rest/v1/establecimiento?select=*,space(*,schedule(*,services(*)))&codigo_postal=eq." . urlencode($cpGestor);

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
                    foreach ($est['space'] as $esp) {
                        $esp['establecimiento'] = [
                            'nombre' => $est['nombre'] ?? 'Establecimiento desconocido',
                            'image_url' => $est['image_url'] ?? null
                        ];
                        $espacios[] = $esp;
                    }
                }
            }
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
    <link rel="icon" href="../img/favicon-color.png">
    <title>Espacios de tu Zona</title>
    <style>
        body {
            font-family: 'Nunito', sans-serif;
            background-color: #f8f9fa;
            padding-bottom: 15%;
        }

        .contenedorLista {
            max-width: 1000px;
            margin: 2rem auto;
            background-color: white;
            border-radius: 15px;
            box-shadow: 0 .5rem 1rem rgba(0, 0, 0, .15);
            padding: 2rem;
            position: relative;
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
            border-radius: 10px;
            margin-bottom: 1.5rem;
            box-shadow: 0 .25rem .5rem rgba(0, 0, 0, .05);
            overflow: hidden;
            background-color: white;
            transition: opacity 0.3s;
        }

        .espacio-oculto {
            opacity: 0.6;
            background-color: #f1f1f1;
        }

        .espacio-header {
            padding: 20px;
            background-color: #f8f9fa;
            border-bottom: 1px solid #ced4da;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .establecimiento-badge {
            background-color: #e9ecef;
            color: #495057;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: bold;
            display: inline-flex;
            align-items: center;
            margin-bottom: 8px;
        }

        .horarios-container {
            padding: 20px;
            display: none;
            background-color: white;
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
            background-color: #fdfdfd;
        }

        .servicio-item {
            background-color: #f8f9fa;
            border-radius: 6px;
            padding: 10px;
            margin-top: 8px;
            border-left: 3px solid #00B7CF;
        }

        .espacios-vacio {
            text-align: center;
            padding: 40px 20px;
            background-color: #f8f9fa;
            border-radius: 10px;
            color: #6c757d;
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
                border-radius: 5px !important;
                margin: 0 !important;
                font-size: 0.85rem;
            }
        }

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

        /* Menú Footer */
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
        }

        .icon-container {
            transition: transform 0.3s ease;
            padding: 5px 0;
        }

        .footer-item:hover .icon-container {
            transform: translateY(-7px);
            color: #007bff;
        }

        .footer-item:hover {
            color: #00B7CF !important;
        }

        .footer {
            background-color: white;
            width: 100%;
            bottom: 0;
            font-size: 15px;
            background: #E3E1E1;
            text-align: center;
            position: fixed;
            z-index: 1000;
        }
    </style>
</head>

<body>
    <header>
        <div class="container-fluid info text-center" style="background-color: #00B7CF; color: white;">
            <div class="row">
                <div class="col h3 fw-bold pt-3 pb-2 m-0">
                    Espacios de tu zona
                </div>
            </div>
        </div>
    </header>

    <div class="contenedorLista mt-4">

        <div class="header-container flex-column">
            <h4 class="m-0 fw-bold text-center">
                <i class="fas fa-chair me-2 text-primary"></i> Espacios Registrados
            </h4>
            <?php if ($cpGestor && !$tieneError): ?>
                <span class="badge bg-info text-dark mt-2">Código Postal: <?php echo htmlspecialchars($cpGestor); ?></span>
            <?php endif; ?>
        </div>

        <?php if ($tieneError): ?>
            <div class="alert alert-danger shadow-sm rounded-pill" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i> <?php echo $errorMsg; ?>
            </div>
        <?php else: ?>

            <div id="espacios-container">
                <?php if (empty($espacios)): ?>
                    <div class="espacios-vacio">
                        <i class="fas fa-box-open fa-3x mb-3 text-muted"></i>
                        <h4>Sin espacios en tu zona</h4>
                        <p class="mb-0">Aún no hay ningún espacio registrado en los establecimientos de tu código postal.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($espacios as $espacio): ?>
                        <?php $esVisible = isset($espacio['visible']) ? $espacio['visible'] : true; ?>

                        <div class="espacio-card <?php echo $esVisible ? '' : 'espacio-oculto'; ?>"
                            id="card-<?php echo $espacio['id']; ?>">
                            <div class="espacio-header flex-wrap">
                                <div class="mb-2 mb-md-0">
                                    <div class="establecimiento-badge">
                                        <i class="fas fa-building me-1"></i>
                                        <?php echo htmlspecialchars($espacio['establecimiento']['nombre']); ?>
                                    </div>
                                    <h5 class="mb-1 fw-bold text-primary"><?php echo htmlspecialchars($espacio['name']); ?></h5>
                                    <p class="mb-0 text-muted small"><?php echo htmlspecialchars($espacio['description']); ?></p>
                                </div>
                                <div class="btn-group">
                                    <button class="btn btn-outline-info btn-sm toggle-horarios fw-bold"
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
                                        class="btn btn-primary btn-sm fw-bold ms-1">
                                        <i class="fas fa-edit me-1"></i> Editar
                                    </a>

                                    <button class="btn btn-danger btn-sm btn-eliminar fw-bold ms-1"
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
                                                            <?php echo substr($horario['end_time'], 0, 5); ?>
                                                        </div>
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

    <div class="container-fluid footer p-3">
        <div class="row text-center fixed-bottom bg-blanco pt-1 px-2 footer-container">
            <a href="Anfitriones.php" class="col-2 text-center footer-item">
                <div class="row">
                    <div class="col-12 icon-container"><i class="h2 fas fa-users p-1 m-0"></i>
                        <div>Anfitriones</div>
                    </div>
                </div>
            </a>
            <a href="verValidar.php" class="col-2 text-center footer-item">
                <div class="row">
                    <div class="col-12 icon-container"><i class="h2 fas fa-check-circle p-1 m-0"></i>
                        <div>Validar</div>
                    </div>
                </div>
            </a>
            <a href="verReservas.php" class="col-2 text-center footer-item">
                <div class="row">
                    <div class="col-12 icon-container"><i class="h2 fas fa-book-open p-1 m-0"></i>
                        <div>Reservas</div>
                    </div>
                </div>
            </a>
            <a href="verEstablecimientos.php" class="col-2 text-center footer-item">
                <div class="row">
                    <div class="col-12 icon-container"><i class="h2 fas fa-building p-1 m-0"></i>
                        <div>Establecimientos</div>
                    </div>
                </div>
            </a>
            <a href="verEspacios.php" class="col-2 text-center footer-item" style="color: #00B7CF;">
                <div class="row">
                    <div class="col-12 icon-container" style="color: #007bff;"><i class="h2 fas fa-chair p-1 m-0"></i>
                        <div>Espacios</div>
                    </div>
                </div>
            </a>
            <a href="tuPerfil.php" class="col-2 text-center footer-item">
                <div class="row">
                    <div class="col-12 icon-container"><i class="h2 fas fa-user-tie p-1 m-0"></i>
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
                    $(this).removeClass('btn-outline-info').addClass('btn-info text-white');
                    $(this).html('<i class="fas fa-chevron-up me-1"></i> Ocultar');
                } else {
                    icon.removeClass('fa-chevron-up').addClass('fa-clock');
                    $(this).removeClass('btn-info text-white').addClass('btn-outline-info');
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