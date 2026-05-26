<?php
require_once 'verificar_sesion_host.php';
$formError = '';
$formSuccess = '';
$space = null;

require '../vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: tusEspacios.php');
    exit;
}

function generarUuidV4()
{
    $data = random_bytes(16);

    // Establece los bits de versión (0100 para versión 4)
    $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);

    // Establece los bits del variant (10xxxxxx)
    $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);

    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

$space_id = $_GET['id'];

$curl = curl_init();
$url = "http://" . $_ENV['SERVER_IP'] . ":" . $_ENV['DATABASE_PORT'] . "/rest/v1/space?select=*,schedule(*,services(*))&id=eq." . $space_id;

curl_setopt_array($curl, [
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        "Content-Type: application/json",
        "apikey: " . $_ENV['DATABASE_APIKEY'],
        "Authorization: Bearer " . $_SESSION['token'],
    ]
]);

$response = curl_exec($curl);
$httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

if ($httpCode == 401) {
    header('Location: logoutHost.php');
    exit;
}

$space_data = json_decode($response, true);
curl_close($curl);

if (empty($space_data)) {
    $formError = "No se encontró el espacio de trabajo especificado.";
} else {
    $space = $space_data[0];
}

$curl = curl_init();
$url = "http://" . $_ENV['SERVER_IP'] . ":" . $_ENV['DATABASE_PORT'] . "/rest/v1/establecimiento?host_id=eq." . $_SESSION['user_id'];

curl_setopt_array($curl, [
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        "Content-Type: application/json",
        "apikey: " . $_ENV['DATABASE_APIKEY'],
        "Authorization: Bearer " . $_SESSION['token'],
    ]
]);

$response = curl_exec($curl);
$establecimientos = json_decode($response, true);

curl_close($curl);

if (isset($_POST['actualizar'])) {
    $establecimiento_id = isset($_POST['establecimiento_id']) ? $_POST['establecimiento_id'] : null;

    if (!$establecimiento_id) {
        $formError = "Debes seleccionar un establecimiento para este espacio de trabajo.";
    } else {
        $nombre = $_POST['nombre'];
        $descripcion = $_POST['descripcion'];

        $url = 'http://' . $_ENV['SERVER_IP'] . ':' . $_ENV['DATABASE_PORT'] . '/rest/v1/space?id=eq.' . $space_id;
        $ch = curl_init($url);
        $data = [
            'establecimiento_id' => $establecimiento_id,
            'name' => $nombre,
            'description' => $descripcion
        ];

        $payload = json_encode($data);

        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PATCH");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'apikey: ' . $_ENV['DATABASE_APIKEY'],
            'Authorization: Bearer ' . $_SESSION['token']
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        $result = curl_exec($ch);
        $responseData = json_decode($result, true);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($httpCode >= 200 && $httpCode < 300) {

            $url = 'http://' . $_ENV['SERVER_IP'] . ':' . $_ENV['DATABASE_PORT'] . '/rest/v1/schedule?space_id=eq.' . $space_id;
            $ch = curl_init($url);

            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'apikey: ' . $_ENV['DATABASE_APIKEY'],
                'Authorization: Bearer ' . $_SESSION['token']
            ]);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
            $result = curl_exec($ch);
            $responseData = json_decode($result, true);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            if ($httpCode >= 200 && $httpCode < 300) {
                foreach ($_POST['hora_inicio'] as $horario_index => $hora_inicio) {
                    $uuid_schedule = generarUuidV4();
                    $start_time = $_POST['hora_inicio'][$horario_index];
                    $end_time = $_POST['hora_fin'][$horario_index];
                    $price = $_POST['precio_hora'][$horario_index];

                    $dias_seleccionados = isset($_POST['dia'][$horario_index]) ? $_POST['dia'][$horario_index] : [];
                    $has_monday = in_array('L', $dias_seleccionados);
                    $has_tuesday = in_array('M', $dias_seleccionados);
                    $has_wednesday = in_array('X', $dias_seleccionados);
                    $has_thursday = in_array('J', $dias_seleccionados);
                    $has_friday = in_array('V', $dias_seleccionados);
                    $has_saturday = in_array('S', $dias_seleccionados);
                    $has_sunday = in_array('D', $dias_seleccionados);


                    $url = 'http://' . $_ENV['SERVER_IP'] . ':' . $_ENV['DATABASE_PORT'] . '/rest/v1/schedule';
                    $ch = curl_init($url);
                    $data = [
                        'id' => $uuid_schedule,
                        'has_monday' => $has_monday,
                        'has_tuesday' => $has_tuesday,
                        'has_wednesday' => $has_wednesday,
                        'has_thursday' => $has_thursday,
                        'has_friday' => $has_friday,
                        'has_saturday' => $has_saturday,
                        'has_sunday' => $has_sunday,
                        'start_time' => $start_time,
                        'end_time' => $end_time,
                        'price' => $price,
                        'space_id' => $space_id,
                    ];

                    $payload = json_encode($data);

                    curl_setopt($ch, CURLOPT_POST, true);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_HTTPHEADER, [
                        'Content-Type: application/json',
                        'apikey: ' . $_ENV['DATABASE_APIKEY'],
                        'Authorization: Bearer ' . $_SESSION['token'],
                    ]);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
                    $result = curl_exec($ch);

                    if (isset($_POST['servicio_nombre'][$horario_index]) && is_array($_POST['servicio_nombre'][$horario_index])) {
                        foreach ($_POST['servicio_nombre'][$horario_index] as $servicio_index => $nombre_servicio) {
                            $name_servicio = $_POST['servicio_nombre'][$horario_index][$servicio_index];
                            $descripcion_servicio = $_POST['servicio_descripcion'][$horario_index][$servicio_index];
                            $precio_servicio = $_POST['servicio_precio'][$horario_index][$servicio_index];

                            $url = 'http://' . $_ENV['SERVER_IP'] . ':' . $_ENV['DATABASE_PORT'] . '/rest/v1/services';
                            $ch = curl_init($url);
                            $data = [
                                'schedule_id' => $uuid_schedule,
                                'name' => $name_servicio,
                                'description' => $descripcion_servicio,
                                'price' => $precio_servicio
                            ];

                            $payload = json_encode($data);

                            curl_setopt($ch, CURLOPT_POST, true);
                            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                                'Content-Type: application/json',
                                'apikey: ' . $_ENV['DATABASE_APIKEY'],
                                'Authorization: Bearer ' . $_SESSION['token'],
                            ]);
                            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
                            $result = curl_exec($ch);
                        }
                    }
                }
            } else {
                $formError = "Error al eliminar los horarios existentes: " . $responseData['message'];
            }


            $formSuccess = "Espacio actualizado correctamente. Redirigiendo...";
        } else if ($httpCode == 401) {
            header('Location: logout.php');
            exit;
        } else {
            $formError = "Error al actualizar el espacio: " . $responseData['message'];
        }

        $formSuccess = "Espacio actualizado correctamente. Redirigiendo...";

        echo "<script>
            setTimeout(function() {
                window.location.href = 'tusEspacios.php';
            }, 1500);
        </script>";
    }
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
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="icon" href="../favicon-color.png">
    <link rel="icon" href="../favicon-negro.png" media="(prefers-color-scheme: light)">
    <link rel="icon" href="../favicon-color.png" media="(prefers-color-scheme: dark)">
    <title>Editar Espacio de Trabajo</title>
    <style>
        body {
            font-family: 'Nunito', sans-serif;
            background-color: #f8f9fa;
        }

        .contenedor {
            max-width: 800px;
            margin: 2rem auto;
            background-color: white;
            border-radius: 15px;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            padding: 1rem;
        }

        .form-control:focus {
            border-color: #80bdff;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
        }

        .btn-primary,
        .btn-success {
            font-weight: 600;
            padding: 0.75rem 2rem;
            border-radius: 50px;
        }

        .btn-secondary {
            background-color: #f8f9fa;
            border: 1px solid #ced4da;
            color: #6c757d;
            font-weight: 600;
            padding: 0.75rem 2rem;
            border-radius: 50px;
        }

        .btn-add {
            background-color: #007bff;
            border: none;
            color: white;
            border-radius: 10px;
            padding: 0.5rem 1rem;
            font-weight: 600;
        }

        .horario-container,
        .servicio-item {
            border: 1px solid #ced4da;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
            position: relative;
        }

        .servicios-container {
            background-color: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
            margin-top: 15px;
        }

        .remove-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            background-color: #dc3545;
            color: white;
            border: none;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            cursor: pointer;
        }

        .day-checkbox {
            display: inline-block;
            margin-right: 5px;
            margin-bottom: 10px;
        }

        .day-checkbox input[type="checkbox"] {
            display: none;
        }

        .day-checkbox label {
            display: inline-block;
            background-color: #f8f9fa;
            border: 1px solid #ced4da;
            border-radius: 5px;
            padding: 5px 10px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .day-checkbox input[type="checkbox"]:checked+label {
            background-color: #007bff;
            color: white;
            border-color: #007bff;
        }

        .info-icon {
            cursor: pointer;
            margin-left: 5px;
        }

        .tooltip-text {
            display: none;
            background-color: #333;
            color: #fff;
            text-align: left;
            border-radius: 8px;
            padding: 12px;
            position: absolute;
            z-index: 1000;
            max-width: 300px;
        }
    </style>
</head>

<body>
    <div class="contenedor">
        <div class="text-center py-3 fw-bold h4">
            <p>Editar espacio de trabajo
                <span class="info-icon" data-bs-toggle="tooltip" title="Un espacio de trabajo es una silla, mesa, sala o zona concreta dentro de tu establecimiento disponible para que los usuarios trabajen.">
                    <i class="fas fa-info-circle"></i>
                </span>
            </p>
        </div>

        <div class="text-center mb-3">
            <img src="../img/espacio.png" width="80" alt="Logo Espacio de Trabajo" class="bg-light rounded-circle p-3">
        </div>

        <?php if (!empty($formError)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle me-2"></i> <?php echo $formError; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($formSuccess)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle me-2"></i> <?php echo $formSuccess; ?>
            </div>
        <?php endif; ?>

        <?php if ($space): ?>
            <form method="post" action="" id="espacioTrabajoForm">
                <input type="hidden" name="space_id" value="<?php echo $space['id']; ?>">

                <div class="mb-3">
                    <label for="nombre" class="form-label fw-bold">Nombre del espacio de trabajo *</label>
                    <input type="text" class="form-control" id="nombre" name="nombre" value="<?php echo htmlspecialchars($space['name']); ?>" required>
                    <div class="form-text">Ej: Sala de reuniones, Oficina compartida, etc.</div>
                </div>

                <div class="mb-3">
                    <label for="descripcion" class="form-label fw-bold">Descripción Breve *</label>
                    <textarea class="form-control" id="descripcion" name="descripcion" rows="3" required><?php echo htmlspecialchars($space['description']); ?></textarea>
                    <div class="form-text">Describe qué ofrece este espacio de trabajo, características, etc.</div>
                </div>

                <div class="mb-3">
                    <label for="establecimiento_id" class="form-label fw-bold">Establecimiento *</label>
                    <select class="form-select" id="establecimiento_id" name="establecimiento_id" required>
                        <option value="" disabled>Selecciona un establecimiento</option>
                        <?php foreach ($establecimientos as $establecimiento): ?>
                            <option value="<?php echo $establecimiento['id']; ?>" <?php echo ($establecimiento['id'] == $space['establecimiento_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($establecimiento['nombre']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text">Selecciona el establecimiento al que pertenecerá este espacio de trabajo.</div>
                </div>

                <div class="mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <label class="form-label fw-bold mb-0">Horarios disponibles *</label>
                        <button type="button" class="btn btn-add" id="add-horario">
                            <i class="fas fa-plus me-2"></i> Añadir horario
                        </button>
                    </div>

                    <div id="horarios-container">
                    </div>

                    <div id="no-horarios-message" class="alert alert-info" style="display: none;">
                        <i class="fas fa-info-circle me-2"></i> Añade al menos un horario para tu espacio de trabajo.
                    </div>
                </div>

                <div class="d-flex justify-content-between mt-4">
                    <button type="button" class="btn btn-secondary" onclick="location.href='tusEspacios.php'">
                        <i class="fas fa-arrow-left me-2"></i> Volver
                    </button>
                    <button type="submit" name="actualizar" class="btn btn-success">
                        <i class="fas fa-save me-2"></i> Guardar cambios
                    </button>
                </div>
            </form>
        <?php endif; ?>
    </div>

    <template id="horario-template">
        <div class="horario-container">
            <button type="button" class="remove-btn remove-horario">
                <i class="fas fa-times"></i>
            </button>

            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0">Horario #<span class="horario-num"></span></h5>
            </div>

            <div class="mb-3">
                <label class="form-label">Días disponibles *</label>
                <div>
                    <div class="day-checkbox">
                        <input type="checkbox" id="dia_INDEX_L" name="dia[INDEX][]" value="L">
                        <label for="dia_INDEX_L">Lunes</label>
                    </div>
                    <div class="day-checkbox">
                        <input type="checkbox" id="dia_INDEX_M" name="dia[INDEX][]" value="M">
                        <label for="dia_INDEX_M">Martes</label>
                    </div>
                    <div class="day-checkbox">
                        <input type="checkbox" id="dia_INDEX_X" name="dia[INDEX][]" value="X">
                        <label for="dia_INDEX_X">Miércoles</label>
                    </div>
                    <div class="day-checkbox">
                        <input type="checkbox" id="dia_INDEX_J" name="dia[INDEX][]" value="J">
                        <label for="dia_INDEX_J">Jueves</label>
                    </div>
                    <div class="day-checkbox">
                        <input type="checkbox" id="dia_INDEX_V" name="dia[INDEX][]" value="V">
                        <label for="dia_INDEX_V">Viernes</label>
                    </div>
                    <div class="day-checkbox">
                        <input type="checkbox" id="dia_INDEX_S" name="dia[INDEX][]" value="S">
                        <label for="dia_INDEX_S">Sábado</label>
                    </div>
                    <div class="day-checkbox">
                        <input type="checkbox" id="dia_INDEX_D" name="dia[INDEX][]" value="D">
                        <label for="dia_INDEX_D">Domingo</label>
                    </div>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Hora de inicio *</label>
                    <input type="time" class="form-control" name="hora_inicio[INDEX]" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Hora de fin *</label>
                    <input type="time" class="form-control" name="hora_fin[INDEX]" required>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Precio por hora (€) *</label>
                <input type="number" class="form-control" step="0.01" min="0" name="precio_hora[INDEX]" required>
            </div>

            <div class="servicios-container">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <label class="form-label fw-bold mb-0">Servicios adicionales</label>
                    <button type="button" class="btn btn-sm btn-add add-servicio" data-horario="INDEX">
                        <i class="fas fa-plus me-1"></i> Añadir servicio
                    </button>
                </div>

                <div class="servicios-list">
                </div>
            </div>
        </div>
    </template>

    <template id="servicio-template">
        <div class="servicio-item">
            <button type="button" class="remove-btn remove-servicio">
                <i class="fas fa-times"></i>
            </button>

            <div class="mb-2">
                <label class="form-label">Nombre del servicio *</label>
                <input type="text" class="form-control" name="servicio_nombre[HORARIO_INDEX][SERVICIO_INDEX]" required>
            </div>

            <div class="mb-2">
                <label class="form-label">Descripción del servicio *</label>
                <textarea class="form-control" rows="2" name="servicio_descripcion[HORARIO_INDEX][SERVICIO_INDEX]" required></textarea>
            </div>

            <div class="mb-2 col-md-6">
                <label class="form-label">Precio (€) *</label>
                <input type="number" class="form-control" step="0.01" min="0" name="servicio_precio[HORARIO_INDEX][SERVICIO_INDEX]" required>
            </div>
        </div>
    </template>

    <script>
        $(document).ready(function() {
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function(tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });

            let horarioCount = 0;

            function updateNoHorariosMessage() {
                if ($('.horario-container').length > 0) {
                    $('#no-horarios-message').hide();
                } else {
                    $('#no-horarios-message').show();
                }
            }

            function updateHorarioNumbers() {
                $('.horario-container').each(function(index) {
                    $(this).find('.horario-num').text(index + 1);
                });
            }

            $('#add-horario').click(function() {
                const template = document.getElementById('horario-template').content.cloneNode(true);
                const horarioIndex = horarioCount++;

                let templateHTML = template.querySelector('.horario-container').outerHTML;
                templateHTML = templateHTML.replace(/INDEX/g, horarioIndex);

                $('#horarios-container').append(templateHTML);

                updateHorarioNumbers();
                updateNoHorariosMessage();
            });

            $(document).on('click', '.remove-horario', function() {
                $(this).closest('.horario-container').remove();
                updateHorarioNumbers();
                updateNoHorariosMessage();
            });

            $(document).on('click', '.add-servicio', function() {
                const horarioIndex = $(this).data('horario');
                const serviciosContainer = $(this).closest('.servicios-container').find('.servicios-list');
                const servicioIndex = serviciosContainer.children().length;

                const template = document.getElementById('servicio-template').content.cloneNode(true);
                let templateHTML = template.querySelector('.servicio-item').outerHTML;
                templateHTML = templateHTML.replace(/HORARIO_INDEX/g, horarioIndex);
                templateHTML = templateHTML.replace(/SERVICIO_INDEX/g, servicioIndex);

                serviciosContainer.append(templateHTML);
            });

            $(document).on('click', '.remove-servicio', function() {
                $(this).closest('.servicio-item').remove();
            });

            $('#espacioTrabajoForm').submit(function(e) {
                let valid = true;
                let errorMessages = [];

                if (!$('#establecimiento_id').val()) {
                    valid = false;
                    errorMessages.push('Debe seleccionar un establecimiento para este espacio de trabajo.');
                }

                if ($('.horario-container').length === 0) {
                    valid = false;
                    errorMessages.push('Debe agregar al menos un horario para el espacio de trabajo.');
                } else {
                    $('.horario-container').each(function(index) {
                        const diasSeleccionados = $(this).find('input[type="checkbox"]:checked').length;
                        if (diasSeleccionados === 0) {
                            valid = false;
                            errorMessages.push(`Debe seleccionar al menos un día en el Horario #${index + 1}.`);
                        }

                        const horaInicio = $(this).find('input[name^="hora_inicio"]').val();
                        const horaFin = $(this).find('input[name^="hora_fin"]').val();

                        if (horaInicio && horaFin && horaInicio >= horaFin) {
                            valid = false;
                            errorMessages.push(`La hora de fin debe ser posterior a la hora de inicio en el Horario #${index + 1}.`);
                        }

                        const precioHora = $(this).find('input[name^="precio_hora"]').val();
                        if (precioHora && (isNaN(precioHora) || precioHora <= 0)) {
                            valid = false;
                            errorMessages.push(`Debe especificar un precio por hora válido en el Horario #${index + 1}.`);
                        }
                    });
                }

                if (!valid) {
                    e.preventDefault();
                    $('<div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i>' + errorMessages.join(' ') + '</div>')
                        .insertBefore('form').delay(5000).fadeOut();

                    $('html, body').animate({
                        scrollTop: 0
                    }, 300);
                }

                return valid;
            });

            <?php if ($space && !empty($space['schedule'])): ?>
                <?php foreach ($space['schedule'] as $index => $horario): ?>
                    const horarioIndex_<?php echo $index; ?> = horarioCount++;
                    const template_<?php echo $index; ?> = document.getElementById('horario-template').content.cloneNode(true);
                    let templateHTML_<?php echo $index; ?> = template_<?php echo $index; ?>.querySelector('.horario-container').outerHTML;
                    templateHTML_<?php echo $index; ?> = templateHTML_<?php echo $index; ?>.replace(/INDEX/g, horarioIndex_<?php echo $index; ?>);

                    $('#horarios-container').append(templateHTML_<?php echo $index; ?>);

                    const $horario_<?php echo $index; ?> = $('.horario-container').last();
                    $horario_<?php echo $index; ?>.find('input[name="hora_inicio[' + horarioIndex_<?php echo $index; ?> + ']"]').val('<?php echo substr($horario['start_time'], 0, 5); ?>');
                    $horario_<?php echo $index; ?>.find('input[name="hora_fin[' + horarioIndex_<?php echo $index; ?> + ']"]').val('<?php echo substr($horario['end_time'], 0, 5); ?>');
                    $horario_<?php echo $index; ?>.find('input[name="precio_hora[' + horarioIndex_<?php echo $index; ?> + ']"]').val(<?php echo $horario['price']; ?>);

                    <?php if ($horario['has_monday']): ?>
                        $horario_<?php echo $index; ?>.find('#dia_' + horarioIndex_<?php echo $index; ?> + '_L').prop('checked', true);
                    <?php endif; ?>
                    <?php if ($horario['has_tuesday']): ?>
                        $horario_<?php echo $index; ?>.find('#dia_' + horarioIndex_<?php echo $index; ?> + '_M').prop('checked', true);
                    <?php endif; ?>
                    <?php if ($horario['has_wednesday']): ?>
                        $horario_<?php echo $index; ?>.find('#dia_' + horarioIndex_<?php echo $index; ?> + '_X').prop('checked', true);
                    <?php endif; ?>
                    <?php if ($horario['has_thursday']): ?>
                        $horario_<?php echo $index; ?>.find('#dia_' + horarioIndex_<?php echo $index; ?> + '_J').prop('checked', true);
                    <?php endif; ?>
                    <?php if ($horario['has_friday']): ?>
                        $horario_<?php echo $index; ?>.find('#dia_' + horarioIndex_<?php echo $index; ?> + '_V').prop('checked', true);
                    <?php endif; ?>
                    <?php if ($horario['has_saturday']): ?>
                        $horario_<?php echo $index; ?>.find('#dia_' + horarioIndex_<?php echo $index; ?> + '_S').prop('checked', true);
                    <?php endif; ?>
                    <?php if ($horario['has_sunday']): ?>
                        $horario_<?php echo $index; ?>.find('#dia_' + horarioIndex_<?php echo $index; ?> + '_D').prop('checked', true);
                    <?php endif; ?>

                    <?php if (!empty($horario['services'])): ?>
                        <?php foreach ($horario['services'] as $servicio_index => $servicio): ?>
                            const servicioIndex_<?php echo $index; ?>_<?php echo $servicio_index; ?> = $horario_<?php echo $index; ?>.find('.servicios-list').children().length;
                            const servicioTemplate_<?php echo $index; ?>_<?php echo $servicio_index; ?> = document.getElementById('servicio-template').content.cloneNode(true);
                            let servicioHTML_<?php echo $index; ?>_<?php echo $servicio_index; ?> = servicioTemplate_<?php echo $index; ?>_<?php echo $servicio_index; ?>.querySelector('.servicio-item').outerHTML;
                            servicioHTML_<?php echo $index; ?>_<?php echo $servicio_index; ?> = servicioHTML_<?php echo $index; ?>_<?php echo $servicio_index; ?>.replace(/HORARIO_INDEX/g, horarioIndex_<?php echo $index; ?>);
                            servicioHTML_<?php echo $index; ?>_<?php echo $servicio_index; ?> = servicioHTML_<?php echo $index; ?>_<?php echo $servicio_index; ?>.replace(/SERVICIO_INDEX/g, servicioIndex_<?php echo $index; ?>_<?php echo $servicio_index; ?>);

                            $horario_<?php echo $index; ?>.find('.servicios-list').append(servicioHTML_<?php echo $index; ?>_<?php echo $servicio_index; ?>);

                            const $servicio_<?php echo $index; ?>_<?php echo $servicio_index; ?> = $horario_<?php echo $index; ?>.find('.servicio-item').last();
                            $servicio_<?php echo $index; ?>_<?php echo $servicio_index; ?>.find('input[name="servicio_nombre[' + horarioIndex_<?php echo $index; ?> + '][' + servicioIndex_<?php echo $index; ?>_<?php echo $servicio_index; ?> + ']"]').val('<?php echo addslashes($servicio['name']); ?>');
                            $servicio_<?php echo $index; ?>_<?php echo $servicio_index; ?>.find('textarea[name="servicio_descripcion[' + horarioIndex_<?php echo $index; ?> + '][' + servicioIndex_<?php echo $index; ?>_<?php echo $servicio_index; ?> + ']"]').val('<?php echo addslashes($servicio['description']); ?>');
                            $servicio_<?php echo $index; ?>_<?php echo $servicio_index; ?>.find('input[name="servicio_precio[' + horarioIndex_<?php echo $index; ?> + '][' + servicioIndex_<?php echo $index; ?>_<?php echo $servicio_index; ?> + ']"]').val(<?php echo $servicio['price']; ?>);
                        <?php endforeach; ?>
                    <?php endif; ?>
                <?php endforeach; ?>

                updateHorarioNumbers();
                updateNoHorariosMessage();
            <?php endif; ?>
        });
    </script>
    <?php include '../typebot.php'; ?>
</body>

</html>