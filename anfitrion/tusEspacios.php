<?php
require_once 'verificar_sesion_host.php';
/*
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}
*/

require '../vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

// 1. Obtener el plan del anfitrión
$url_host = "http://" . $_ENV['SERVER_IP'] . ":" . $_ENV['DATABASE_PORT'] . "/rest/v1/host?id=eq." . $_SESSION['user_id'];
$ch_host = curl_init($url_host);
curl_setopt_array($ch_host, array(
    CURLOPT_CUSTOMREQUEST => "GET",
    CURLOPT_HTTPHEADER => array(
        'Content-Type: application/json',
        'apikey: ' . $_ENV['DATABASE_APIKEY']
    ),
    CURLOPT_RETURNTRANSFER => true,
));
$resultado_host = curl_exec($ch_host);
$codigoRespuesta_host = curl_getinfo($ch_host, CURLINFO_HTTP_CODE);
curl_close($ch_host);

$plan = 'Basico'; // Plan por defecto
if ($codigoRespuesta_host === 200) {
    $datos_host = json_decode($resultado_host, true);
    if (count($datos_host) > 0) {
        $plan = $datos_host[0]['plan'];
    }
}

function getEstablecimientos()
{
    $url = "http://" . $_ENV['SERVER_IP'] . ":" . $_ENV['DATABASE_PORT'] . "/rest/v1/establecimiento?select=*,space(*,schedule(*,services(*)))&host_id=eq." . $_SESSION["user_id"];
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'apikey: ' . $_ENV['DATABASE_APIKEY'],
        'Content-Type: application/json'
    ]);
    $response = curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);

    if ($err) {
        return ["error" => $err];
    } else {
        return json_decode($response, true);
    }
}

$establecimientos = getEstablecimientos();
$tieneError = isset($establecimientos['error']);

// 2. Contar los espacios actuales
$num_espacios = 0;
if (!$tieneError && !empty($establecimientos)) {
    foreach ($establecimientos as $establecimiento) {
        if (!empty($establecimiento['space'])) {
            $num_espacios += count($establecimiento['space']);
        }
    }
}

// 3. Evaluar los límites de espacios según el plan
$limites = [
    'Basico' => 3,
    'Pro' => 10,
    'Premium' => PHP_INT_MAX // ilimitado
];

$mostrarMensajeLimite = false;
if ($num_espacios >= $limites[$plan]) {
    $mostrarMensajeLimite = true;
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
    <title>Mis Espacios</title>
    <style>
        body {
            font-family: 'Nunito', sans-serif;
            background-color: #f8f9fa;
            padding-bottom: 15%;
        }

        .page-shell {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 15px;
            box-sizing: border-box;
        }

        .contenedorLista {
            max-width: 100%;
            background-color: white;
            border-radius: 15px;
            box-shadow: 0 .5rem 1rem rgba(0, 0, 0, .15);
            padding: 1rem;
            position: relative;
        }

        .form-control {
            border-radius: 10px;
            padding: .75rem;
            border: 1px solid #ced4da;
            transition: border-color .3s;
        }

        .form-control:focus {
            border-color: #80bdff;
            box-shadow: 0 0 0 .2rem rgba(0, 123, 255, .25);
        }

        .btn-success {
            background-color: #28a745;
            border: none;
            font-weight: 600;
            padding: .75rem 2rem;
        }

        .btn-primary {
            background-color: #007bff;
            border: none;
            font-weight: 600;
            padding: .5rem 1rem;
        }

        .btn-danger {
            background-color: #dc3545;
            border: none;
            font-weight: 600;
            padding: .5rem 1rem;
        }

        .btn-info {
            background-color: #17a2b8;
            border: none;
            font-weight: 600;
            padding: .5rem 1rem;
            color: white;
        }

        /* Nuevos botones de visibilidad */
        .btn-warning {
            background-color: #ffc107;
            border: none;
            font-weight: 600;
            padding: .5rem 1rem;
            color: black;
        }

        .btn-secondary {
            background-color: #6c757d;
            border: none;
            font-weight: 600;
            padding: .5rem 1rem;
            color: white;
        }

        .logo-container {
            background-color: #f8f9fa;
            border-radius: 50%;
            width: 120px;
            height: 120px;
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 0 auto;
        }

        .establecimiento-header {
            background-color: #f8f9fa;
            padding: 10px;
            border-radius: 10px;
            margin-bottom: 15px;
            font-weight: bold;
        }

        .espacio-card {
            border: 1px solid #ced4da;
            border-radius: 10px;
            margin-bottom: 15px;
            box-shadow: 0 .25rem .5rem rgba(0, 0, 0, .05);
        }

        .espacio-header {
            padding: 15px;
            background-color: #f8f9fa;
            border-bottom: 1px solid #ced4da;
            border-radius: 10px 10px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .horarios-container {
            padding: 15px;
            display: none;
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
            border: 1px solid #ced4da;
            border-radius: 8px;
            padding: 10px;
            margin-bottom: 10px;
        }

        .servicio-item {
            background-color: #f8f9fa;
            border-radius: 6px;
            padding: 8px;
            margin-top: 5px;
        }

        .no-espacios {
            text-align: center;
            padding: 20px;
            color: #6c757d;
        }

        .add-btn {
            position: absolute;
            top: 15px;
            right: 15px;
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background-color: #28a745;
            color: white;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 20px;
            box-shadow: 0 .3rem .5rem rgba(0, 0, 0, .15);
            text-decoration: none;
            z-index: 10;
        }

        .add-btn:hover {
            background-color: #218838;
            color: white;
        }

        .establecimiento-vacio {
            text-align: center;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 10px;
            margin-bottom: 15px;
            color: #6c757d;
        }

        .toast-container {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 1050;
        }

        .custom-toast {
            min-width: 250px;
        }

        @media (max-width: 768px) {
            .espacio-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .btn-group {
                margin-top: 10px;
                width: 100%;
                display: flex;
                flex-wrap: wrap;
                gap: 5px;
            }

            .btn-group .btn {
                flex: 1;
                border-radius: 5px !important;
            }
        }

        .modal-confirm {
            font-family: 'Nunito', sans-serif;
        }

        .modal-confirm .modal-content {
            padding: 20px;
            border-radius: 15px;
        }

        .modal-confirm .modal-header {
            border-bottom: none;
            position: relative;
        }

        .modal-confirm .modal-title {
            text-align: center;
            font-size: 24px;
            font-weight: bold;
        }

        .modal-confirm .modal-body {
            color: #636363;
        }

        .modal-confirm .modal-footer {
            border-top: none;
            text-align: center;
            justify-content: center;
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

        .modal-confirm .btn-danger,
        .modal-confirm .btn-secondary {
            min-width: 100px;
        }

        label,
        .form-check input[type=checkbox] {
            position: static;
        }

        a,
        a:visited,
        a:active {
            color: black;
            text-decoration: none;
        }

        #res:checked~#lbl_res,
        #his:checked~#lbl_his,
        #esp:checked~#lbl_esp,
        #per:checked~#lbl_per {
            color: #00B7CF !important;
        }

        #per:checked~#lbl_per .icon-container,
        #res:checked~#lbl_res .icon-container,
        #his:checked~#lbl_his .icon-container,
        #esp:checked~#lbl_esp .icon-container {
            color: #007bff;
        }

        .header-container {
            position: relative;
            margin-bottom: 15px;
        }
    </style>
</head>

<body>
    <div class="page-shell">

        <?php include 'headerAnfitrion.php'; ?>

        <div id="container" style="max-width: 100%; overflow-x: hidden; box-sizing: border-box;"></div>

        <div class="contenedorLista">
            <div class="header-container">
                <div class="col-12 text-center py-3 fw-bold h4">
                    <p>Mis Espacios</p>
                </div>

                <?php if (!empty($establecimientos) && !$tieneError): ?>
                    <?php if ($mostrarMensajeLimite): ?>
                        <a href="#" id="btnAvisoLimiteEspacio" class="add-btn" style="background-color: #6c757d;">
                            <i class="fas fa-lock"></i>
                        </a>
                    <?php else: ?>
                        <a href="crearEspacio.php" class="add-btn">
                            <i class="fas fa-plus"></i>
                        </a>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

            <div class="col-12 text-center mb-3">
                <div class="logo-container">
                    <img src="../img/establecimiento.png" width="80" alt="Logo Establecimiento">
                </div>
            </div>

            <?php if ($tieneError): ?>
                <div class="alert alert-danger" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    Ha ocurrido un error al cargar los datos: <?php echo $establecimientos['error']; ?>
                </div>
            <?php elseif (empty($establecimientos)): ?>
                <div class="alert alert-info" role="alert">
                    <i class="fas fa-info-circle me-2"></i>
                    No tienes establecimientos registrados.
                    Para crear uno, antes deberás crear un establecimiento <a href="verEstablecimientos.php"> aquí</a>
                </div>
            <?php else: ?>
                <div id="espacios-container">
                    <?php
                    foreach ($establecimientos as $establecimiento):
                    ?>
                        <div class="establecimiento-header">
                            <i class="fas fa-building me-2"></i> <?php echo htmlspecialchars($establecimiento['nombre']); ?>
                        </div>

                        <?php if (empty($establecimiento['space'])): ?>
                            <div class="establecimiento-vacio">
                                <i class="fas fa-exclamation-circle mb-2"></i>
                                <p class="mb-0">Este establecimiento no tiene ningún espacio registrado.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($establecimiento['space'] as $espacio): ?>
                                <div class="espacio-card">
                                    <div class="espacio-header">
                                        <div>
                                            <h5 class="mb-1"><?php echo htmlspecialchars($espacio['name']); ?></h5>
                                            <p class="mb-0"><?php echo htmlspecialchars($espacio['description']); ?></p>
                                        </div>
                                        <div class="btn-group">
                                            <button class="btn btn-info btn-sm toggle-horarios"
                                                data-espacio-id="<?php echo $espacio['id']; ?>">
                                                <i class="fas fa-clock me-1"></i> Horarios
                                            </button>

                                            <?php
                                            // Asumimos que vas a crear un campo "visible" en la BD (por defecto true)
                                            $esVisible = isset($espacio['visible']) ? $espacio['visible'] : true;
                                            ?>
                                            <button
                                                class="btn btn-<?php echo $esVisible ? 'warning' : 'secondary'; ?> btn-sm btn-visibilidad"
                                                data-espacio-id="<?php echo $espacio['id']; ?>"
                                                data-visible="<?php echo $esVisible ? 'true' : 'false'; ?>">
                                                <?php if ($esVisible): ?>
                                                    <i class="fas fa-eye-slash me-1"></i> Ocultar
                                                <?php else: ?>
                                                    <i class="fas fa-eye me-1"></i> Mostrar
                                                <?php endif; ?>
                                            </button>

                                            <a href="editarSpace.php?id=<?php echo $espacio['id']; ?>" class="btn btn-primary btn-sm">
                                                <i class="fas fa-edit me-1"></i> Editar
                                            </a>
                                            <button class="btn btn-danger btn-sm btn-eliminar"
                                                data-espacio-id="<?php echo $espacio['id']; ?>"
                                                data-espacio-nombre="<?php echo htmlspecialchars($espacio['name']); ?>">
                                                <i class="fas fa-trash-alt me-1"></i> Eliminar
                                            </button>
                                        </div>
                                    </div>

                                    <div class="horarios-container" id="horarios-<?php echo $espacio['id']; ?>">
                                        <?php if (empty($espacio['schedule'])): ?>
                                            <div class="alert alert-secondary">Este espacio no tiene horarios configurados.</div>
                                        <?php else: ?>
                                            <?php foreach ($espacio['schedule'] as $horario): ?>
                                                <div class="horario-item">
                                                    <div class="d-flex justify-content-between align-items-center mb-2">
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
                                                        <div>
                                                            <strong><?php echo substr($horario['start_time'], 0, 5); ?> -
                                                                <?php echo substr($horario['end_time'], 0, 5); ?></strong>
                                                        </div>
                                                    </div>

                                                    <div class="d-flex justify-content-between mb-2">
                                                        <div>
                                                            <strong>Precio:</strong> <?php echo number_format($horario['price'], 2); ?>€/hora
                                                        </div>
                                                    </div>

                                                    <?php if (!empty($horario['services'])): ?>
                                                        <div>
                                                            <strong>Servicios:</strong>
                                                            <?php foreach ($horario['services'] as $servicio): ?>
                                                                <div class="servicio-item">
                                                                    <div class="d-flex justify-content-between">
                                                                        <strong><?php echo htmlspecialchars($servicio['name']); ?></strong>
                                                                        <span><?php echo number_format($servicio['price'], 2); ?>€</span>
                                                                    </div>
                                                                    <div><?php echo htmlspecialchars($servicio['description']); ?></div>
                                                                </div>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    <?php else: ?>
                                                        <div class="text-muted">No hay servicios adicionales</div>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

        </div>

        <div class="modal fade modal-confirm" id="avisoLimiteEspacioModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">¡Límite de espacios alcanzado!</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body text-center">
                        <div class="icon-box" style="border-color: #ffc107;">
                            <i class="fas fa-crown" style="color: #ffc107;"></i>
                        </div>
                        <p class="mt-4">Has alcanzado el límite de
                            <b><?php echo $limites[$plan] === PHP_INT_MAX ? 'ilimitados' : $limites[$plan]; ?> espacios</b>
                            permitidos en tu plan <b><?php echo htmlspecialchars($plan); ?></b>.
                        </p>
                        <p>Mejora tu suscripción para crear más espacios y hacer crecer tu negocio.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <a href="Suscripciones.php" class="btn btn-warning"
                            style="border:none; font-weight: bold; color: black; background-color: #ffc107;">Mejorar
                            Plan</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade modal-confirm" id="avisoEstablecimientoModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">¡Atención!</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <div class="icon-box" style="border-color: #ffc107;">
                        <i class="fas fa-exclamation-triangle" style="color: #ffc107;"></i>
                    </div>
                    <p class="mt-4">Para poder crear un espacio, primero debes añadir al menos un establecimiento a tu
                        cuenta.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <a href="anadirEstablecimiento.php?redirect=crearEspacio.php" class="btn btn-primary"
                        style="background-color: #007bff; border:none;">Crear Establecimiento</a>
                </div>
            </div>
        </div>
    </div>

    <div class="toast-container">
        <div class="toast custom-toast align-items-center text-white bg-success border-0" role="alert"
            aria-live="assertive" aria-atomic="true" id="toastExito">
            <div class="d-flex">
                <div class="toast-body">
                    <i class="fas fa-check-circle me-2"></i> Acción realizada correctamente.
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"
                    aria-label="Close"></button>
            </div>
        </div>
        <div class="toast custom-toast align-items-center text-white bg-danger border-0" role="alert"
            aria-live="assertive" aria-atomic="true" id="toastError">
            <div class="d-flex">
                <div class="toast-body" id="mensajeError">
                    <i class="fas fa-exclamation-circle me-2"></i> Ha ocurrido un error.
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"
                    aria-label="Close"></button>
            </div>
        </div>
    </div>

    <?php include 'footerAnfitrion.php'; ?>

    <script>
        $(document).ready(function() {
            $('.toggle-horarios').click(function() {
                const espacioId = $(this).data('espacio-id');
                $(`#horarios-${espacioId}`).slideToggle();

                const icon = $(this).find('i');
                if (icon.hasClass('fa-clock')) {
                    icon.removeClass('fa-clock').addClass('fa-chevron-up');
                    $(this).html('<i class="fas fa-chevron-up me-1"></i> Ocultar');
                } else {
                    icon.removeClass('fa-chevron-up').addClass('fa-clock');
                    $(this).html('<i class="fas fa-clock me-1"></i> Horarios');
                }
            });

            // Variables para toast
            const toastExito = new bootstrap.Toast(document.getElementById('toastExito'), {
                delay: 3000
            });
            const toastError = new bootstrap.Toast(document.getElementById('toastError'), {
                delay: 5000
            });

            // Lógica botón Mostrar / Ocultar Espacio
            $('.btn-visibilidad').click(function() {
                const btn = $(this);
                const espacioId = btn.data('espacio-id');
                const currentState = btn.attr('data-visible') === 'true';
                const newState = !currentState;

                // Desactivar temporalmente el botón
                btn.prop('disabled', true);

                fetch('toggleVisibilidadEspacio.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            id: espacioId,
                            visible: newState
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        btn.prop('disabled', false);

                        if (data.success) {
                            btn.attr('data-visible', newState);

                            if (newState) {
                                btn.removeClass('btn-secondary').addClass('btn-warning');
                                btn.html('<i class="fas fa-eye-slash me-1"></i> Ocultar');
                            } else {
                                btn.removeClass('btn-warning').addClass('btn-secondary');
                                btn.html('<i class="fas fa-eye me-1"></i> Mostrar');
                            }

                            $('#toastExito .toast-body').html('<i class="fas fa-check-circle me-2"></i> Visibilidad del espacio actualizada.');
                            toastExito.show();
                        } else {
                            $('#mensajeError').html(`<i class="fas fa-exclamation-circle me-2"></i> Error: ${data.error || 'No se pudo actualizar.'}`);
                            toastError.show();
                        }
                    })
                    .catch(err => {
                        console.error('Error:', err);
                        btn.prop('disabled', false);
                        $('#mensajeError').html('<i class="fas fa-exclamation-circle me-2"></i> Error de conexión con el servidor.');
                        toastError.show();
                    });
            });

            // Disparar modal de límite de espacios
            $('#btnAvisoLimiteEspacio').click(function(e) {
                e.preventDefault();
                const avisoLimiteModal = new bootstrap.Modal(document.getElementById('avisoLimiteEspacioModal'));
                avisoLimiteModal.show();
            });

            $('#btnAvisoEstablecimiento').click(function(e) {
                e.preventDefault(); // Esto evita que se ponga el # en la URL
                const avisoModal = new bootstrap.Modal(document.getElementById('avisoEstablecimientoModal'));
                avisoModal.show();
            });

            let espacioIdAEliminar = null;

            $('.btn-eliminar').click(function() {
                espacioIdAEliminar = $(this).data('espacio-id');
                const espacioNombre = $(this).data('espacio-nombre');


                if (document.getElementById('confirmModal')) {
                    $('#espacioNombre').text(espacioNombre);
                    const confirmModal = new bootstrap.Modal(document.getElementById('confirmModal'));
                    confirmModal.show();
                } else {
                    if (confirm("¿Estás seguro de que quieres eliminar el espacio " + espacioNombre + "?")) {
                        eliminarEspacio(espacioIdAEliminar);
                    }
                }
            });

            $('#btnConfirmarEliminar').click(function() {
                if (espacioIdAEliminar) {
                    eliminarEspacio(espacioIdAEliminar);
                    if (typeof confirmModal !== 'undefined') confirmModal.hide();
                }
            });

            function mostrarAvisoEstablecimiento(e) {
                e.preventDefault();
                const avisoModal = new bootstrap.Modal(document.getElementById('avisoEstablecimientoModal'));
                avisoModal.show();
            }

            function eliminarEspacio(espacioId) {
                fetch('eliminarEspacio.php?id=' + espacioId, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            $('#toastExito .toast-body').html('<i class="fas fa-check-circle me-2"></i> Espacio eliminado correctamente.');
                            toastExito.show();

                            const espacioElement = $(`button[data-espacio-id="${espacioId}"]`).closest('.espacio-card');

                            const establecimientoHeader = espacioElement.prev('.establecimiento-header');
                            const nextElement = espacioElement.next();

                            espacioElement.fadeOut(300, function() {
                                $(this).remove();

                                if (!nextElement.hasClass('espacio-card')) {
                                    establecimientoHeader.after(`
                                    <div class="establecimiento-vacio">
                                        <i class="fas fa-exclamation-circle mb-2"></i>
                                        <p class="mb-0">Este establecimiento no tiene ningún espacio registrado.</p>
                                    </div>
                                `);
                                }
                            });
                        } else {
                            $('#mensajeError').html(`<i class="fas fa-exclamation-circle me-2"></i> ${data.message || 'Error al eliminar el espacio.'}`);
                            toastError.show();
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        $('#mensajeError').html('<i class="fas fa-exclamation-circle me-2"></i> Error de conexión al eliminar el espacio.');
                        toastError.show();
                    });
            }
        });
    </script>
</body>

</html>