<?php
session_start();
$formError = '';
$formSuccess = '';

if (isset($_POST['siguiente'])) {
    $nombre = trim($_POST['nombre']);
    $descripcion = trim($_POST['descripcion']);

    $errors = [];

    if (empty($nombre)) {
        $errors[] = 'El nombre del espacio de trabajo es obligatorio.';
    }

    if (empty($descripcion)) {
        $errors[] = 'La descripción del espacio de trabajo es obligatoria.';
    }

    if (empty($_POST['dia']) || !is_array($_POST['dia']) || count($_POST['dia']) == 0) {
        $errors[] = 'Debe agregar al menos un horario para el espacio de trabajo.';
    }

    if (empty($errors)) {
        $horarios = [];

        if (isset($_POST['dia']) && is_array($_POST['dia'])) {
            foreach ($_POST['dia'] as $index => $dias) {
                $hora_inicio = $_POST['hora_inicio'][$index];
                $hora_fin = $_POST['hora_fin'][$index];
                $precio_hora = $_POST['precio_hora'][$index];

                if (empty($dias) || empty($hora_inicio) || empty($hora_fin) || !is_numeric($precio_hora)) {
                    continue;
                }

                $servicios = [];

                if (isset($_POST['servicio_nombre'][$index]) && is_array($_POST['servicio_nombre'][$index])) {
                    foreach ($_POST['servicio_nombre'][$index] as $sIndex => $servicio_nombre) {
                        $servicio_descripcion = $_POST['servicio_descripcion'][$index][$sIndex];
                        $servicio_precio = $_POST['servicio_precio'][$index][$sIndex];

                        if (empty($servicio_nombre) || empty($servicio_descripcion) || !is_numeric($servicio_precio)) {
                            continue;
                        }

                        $servicios[] = [
                            'nombre' => $servicio_nombre,
                            'descripcion' => $servicio_descripcion,
                            'precio' => $servicio_precio
                        ];
                    }
                }

                $horarios[] = [
                    'dias' => $dias,
                    'hora_inicio' => $hora_inicio,
                    'hora_fin' => $hora_fin,
                    'precio_hora' => $precio_hora,
                    'servicios' => $servicios
                ];
            }
        }

        $_SESSION['espacio_trabajo'] = [
            'nombre' => $nombre,
            'descripcion' => $descripcion,
            'horarios' => $horarios
        ];

        // Guardamos el mensaje para el Toast y hacemos la redirección con retardo
        $formSuccess = "Espacio de trabajo guardado correctamente.";

        echo "<script>
            setTimeout(function() {
                window.location.href = 'registerAnfitrion-paso6.php';
            }, 1500);
        </script>";

    } else {
        $formError = implode(' ', $errors);
    }
}

$nombre = isset($_SESSION['espacio_trabajo']['nombre']) ? $_SESSION['espacio_trabajo']['nombre'] : '';
$descripcion = isset($_SESSION['espacio_trabajo']['descripcion']) ? $_SESSION['espacio_trabajo']['descripcion'] : '';
$horarios = isset($_SESSION['espacio_trabajo']['horarios']) ? $_SESSION['espacio_trabajo']['horarios'] : [];
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
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="icon" href="../favicon-color.png">
    <link rel="icon" href="../favicon-negro.png" media="(prefers-color-scheme: light)">
    <link rel="icon" href="../favicon-color.png" media="(prefers-color-scheme: dark)">
    <title>Configurar Espacio de Trabajo</title>
    <style>
        body {
            font-family: 'Nunito', sans-serif;
            background-color: #f8f9fa;
        }

        .contenedorAlta {
            max-width: 800px;
            margin: 2rem auto;
            background-color: white;
            border-radius: 15px;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            padding: 1rem;
        }

        .form-control {
            border-radius: 10px;
            padding: 0.75rem;
            border: 1px solid #ced4da;
            transition: border-color 0.3s;
        }

        .form-control:focus {
            border-color: #80bdff;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
        }

        .btn-success {
            background-color: #28a745;
            border: none;
            font-weight: 600;
            padding: 0.75rem 2rem;
        }

        .btn-cancel {
            background-color: #f8f9fa;
            border: 1px solid #ced4da;
            color: #6c757d;
            font-weight: 600;
            padding: 0.75rem 2rem;
        }

        .btn-add {
            background-color: #007bff;
            border: none;
            color: white;
            border-radius: 10px;
            padding: 0.5rem 1rem;
            font-weight: 600;
        }

        .progress-container {
            width: 100%;
            height: 5px;
            background-color: #e9ecef;
            border-radius: 3px;
            overflow: hidden;
            margin: 1rem 0;
        }

        .progress-bar {
            height: 100%;
            width: 80%;
            /* Paso 5 de 6 = 83% */
            background-color: #28a745;
        }

        .alert {
            border-radius: 10px;
            padding: 0.75rem;
            margin-bottom: 1rem;
            display: none;
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

        .horario-container {
            border: 1px solid #ced4da;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
            position: relative;
        }

        .horario-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .servicios-container {
            background-color: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
            margin-top: 15px;
        }

        .servicio-item {
            border: 1px solid #ced4da;
            border-radius: 10px;
            padding: 10px;
            margin-bottom: 10px;
            position: relative;
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
            background-color: #28a745;
            color: white;
            border-color: #28a745;
        }

        .tooltip-container {
            position: relative;
            display: inline-block;
        }

        .tooltip-text {
            visibility: hidden;
            opacity: 0;
            width: 500px;
            background-color: #333;
            color: #fff;
            text-align: left;
            border-radius: 8px;
            padding: 12px 16px;
            position: absolute;
            z-index: 1000;
            top: 150%;
            left: 50%;
            transform: translateX(-50%);
            transition: opacity 0.3s;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            font-size: 14px;
            line-height: 1.5;
            font-weight: normal;
        }

        .tooltip-text::after {
            content: "";
            position: absolute;
            bottom: 100%;
            left: 50%;
            margin-left: -10px;
            border-width: 10px;
            border-style: solid;
            border-color: transparent transparent #333 transparent;
        }

        .tooltip-text.visible {
            visibility: visible;
            opacity: 1;
        }

        #imgInfo {
            cursor: pointer;
            transition: transform 0.2s;
            margin-left: 5px;
        }

        #imgInfo:hover {
            transform: scale(1.1);
        }

        .tooltip-container:hover .tooltip-text {
            visibility: visible;
            opacity: 1;
        }

        .loading-spinner {
            display: none;
            margin-left: 10px;
            vertical-align: middle;
        }

        .success-indicator {
            display: none;
            color: #28a745;
            margin-left: 10px;
            vertical-align: middle;
        }

        .error-indicator {
            display: none;
            color: #dc3545;
            margin-left: 10px;
            vertical-align: middle;
        }

        @media (max-width: 768px) {
            .tooltip-text {
                width: 350px;
                font-size: 13px;
            }

            .register-title {
                display: block;
                margin-bottom: 8px;
            }

            .info-icon-mobile {
                display: block;
                margin: 8px auto 0;
                text-align: center;
            }

            .tooltip-container.mobile {
                display: block;
                text-align: center;
            }

            .tooltip-text::after {
                left: 50%;
            }
        }
    </style>
</head>

<body>
    <div class="contenedorAlta">
        <div class="col-12 text-center py-3 fw-bold h4">
            <div class="d-none d-md-block">
                <p>Registra tu espacio
                    <span class="tooltip-container">
                        <img src="../img/informacion.png" alt="Información" id="imgInfo" width="24px" height="24px">
                        <span id="masInfo" class="tooltip-text">Un espacio de trabajo es una silla, mesa, sala o zona
                            concreta dentro de tu establecimiento disponible para que los usuarios trabajen.</span>
                    </span>
                </p>
            </div>
            <div class="d-block d-md-none">
                <p class="register-title">Registra tu espacio</p>
                <span class="tooltip-container mobile">
                    <img src="../img/informacion.png" alt="Información" id="imgInfoMobile" width="24px" height="24px">
                    <span id="masInfoMobile" class="tooltip-text">Un espacio de trabajo es una silla, mesa, sala o zona
                        concreta dentro de tu establecimiento disponible para que los usuarios trabajen.</span>
                </span>
            </div>
        </div>

        <div class="col-12 text-center mb-3">
            <div class="logo-container">
                <img src="../img/espacio.png" width="80" alt="Logo Espacio de Trabajo">
            </div>
        </div>

        <div class="col-12 text-center h4 mb-4 fw-bold">
            Configura tu espacio de trabajo
        </div>

        <div class="alert alert-danger" id="error-message" <?php echo !empty($formError) ? 'style="display:block"' : ''; ?>>
            <i class="fas fa-exclamation-circle me-2"></i> <span id="error-text"><?php echo $formError; ?></span>
        </div>

        <div class="alert alert-success" id="success-message" <?php echo !empty($formSuccess) ? 'style="display:block"' : ''; ?>>
            <i class="fas fa-check-circle me-2"></i> <span id="success-text"><?php echo $formSuccess; ?></span>
        </div>

        <form method="post" action="" class="container" id="espacioTrabajoForm">
            <div class="row g-3">
                <div class="col-md-12">
                    <label for="nombre" class="form-label fw-bold">Nombre del espacio de trabajo *</label>
                    <input type="text" class="form-control" id="nombre" name="nombre" required
                        value="<?php echo htmlspecialchars($nombre); ?>">
                    <div class="form-text">Ej: Sala de reuniones, Oficina compartida, etc.</div>
                </div>

                <div class="col-md-12">
                    <label for="descripcion" class="form-label fw-bold">Descripción *</label>
                    <textarea class="form-control" id="descripcion" name="descripcion" rows="3"
                        required><?php echo htmlspecialchars($descripcion); ?></textarea>
                    <div class="form-text">Describe qué ofrece este espacio de trabajo, características, etc.</div>
                </div>

                <div class="col-md-12 mt-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <label class="form-label fw-bold mb-0">Horarios disponibles *</label>
                        <button type="button" class="btn btn-add" id="add-horario">
                            <i class="fas fa-plus me-2"></i> Añadir horario
                        </button>
                    </div>

                    <div id="horarios-container">
                        <?php if (!empty($horarios)): ?>
                            <?php foreach ($horarios as $h_index => $horario): ?>
                                <div class="horario-container">
                                    <button type="button" class="remove-btn remove-horario">
                                        <i class="fas fa-times"></i>
                                    </button>

                                    <div class="horario-header">
                                        <h5 class="mb-0">Horario #<?php echo $h_index + 1; ?></h5>
                                    </div>

                                    <div class="row mb-3">
                                        <div class="col-12">
                                            <label class="form-label">Días disponibles *</label>
                                            <div>
                                                <?php
                                                $dias_semana = ['L', 'M', 'X', 'J', 'V', 'S', 'D'];
                                                $nombres_dias = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];
                                                foreach ($dias_semana as $i => $dia_codigo):
                                                    $checked = in_array($dia_codigo, $horario['dias']) ? 'checked' : '';
                                                    ?>
                                                    <div class="day-checkbox">
                                                        <input type="checkbox"
                                                            id="dia_<?php echo $h_index; ?>_<?php echo $dia_codigo; ?>"
                                                            name="dia[<?php echo $h_index; ?>][]" value="<?php echo $dia_codigo; ?>"
                                                            <?php echo $checked; ?>>
                                                        <label
                                                            for="dia_<?php echo $h_index; ?>_<?php echo $dia_codigo; ?>"><?php echo $nombres_dias[$i]; ?></label>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Hora de inicio *</label>
                                            <input type="time" class="form-control" name="hora_inicio[<?php echo $h_index; ?>]"
                                                required value="<?php echo $horario['hora_inicio']; ?>">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Hora de fin *</label>
                                            <input type="time" class="form-control" name="hora_fin[<?php echo $h_index; ?>]"
                                                required value="<?php echo $horario['hora_fin']; ?>">
                                        </div>
                                    </div>

                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Precio por hora (€) *</label>
                                            <input type="number" class="form-control" step="0.01" min="0"
                                                name="precio_hora[<?php echo $h_index; ?>]" required
                                                value="<?php echo $horario['precio_hora']; ?>">
                                        </div>
                                    </div>

                                    <div class="servicios-container">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <label class="form-label fw-bold mb-0">Servicios disponibles en este horario</label>
                                            <button type="button" class="btn btn-sm btn-add add-servicio"
                                                data-horario="<?php echo $h_index; ?>">
                                                <i class="fas fa-plus me-1"></i> Añadir servicio
                                            </button>
                                        </div>

                                        <div class="servicios-list">
                                            <?php if (!empty($horario['servicios'])): ?>
                                                <?php foreach ($horario['servicios'] as $s_index => $servicio): ?>
                                                    <div class="servicio-item">
                                                        <button type="button" class="remove-btn remove-servicio">
                                                            <i class="fas fa-times"></i>
                                                        </button>

                                                        <div class="row mb-2">
                                                            <div class="col-md-12">
                                                                <label class="form-label">Nombre del servicio *</label>
                                                                <input type="text" class="form-control"
                                                                    name="servicio_nombre[<?php echo $h_index; ?>][<?php echo $s_index; ?>]"
                                                                    required
                                                                    value="<?php echo htmlspecialchars($servicio['nombre']); ?>">
                                                            </div>
                                                        </div>

                                                        <div class="row mb-2">
                                                            <div class="col-md-12">
                                                                <label class="form-label">Descripción del servicio *</label>
                                                                <textarea class="form-control" rows="2"
                                                                    name="servicio_descripcion[<?php echo $h_index; ?>][<?php echo $s_index; ?>]"
                                                                    required><?php echo htmlspecialchars($servicio['descripcion']); ?></textarea>
                                                            </div>
                                                        </div>

                                                        <div class="row mb-2">
                                                            <div class="col-md-6">
                                                                <label class="form-label">Precio (€) *</label>
                                                                <input type="number" class="form-control" step="0.01" min="0"
                                                                    name="servicio_precio[<?php echo $h_index; ?>][<?php echo $s_index; ?>]"
                                                                    required value="<?php echo $servicio['precio']; ?>">
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <div id="no-horarios-message" class="alert alert-info" <?php echo !empty($horarios) ? 'style="display:none"' : 'style="display:block"'; ?>>
                        <i class="fas fa-info-circle me-2"></i> Añade al menos un horario para tu espacio de trabajo.
                    </div>
                </div>
            </div>

            <div class="progress-container mt-4">
                <div class="progress-bar"></div>
            </div>

            <div class="container mt-4">
                <div class="row">
                    <div class="col-6 text-end">
                        <button class="btn btn-cancel rounded-pill" type="button"
                            onclick="location.href='registerAnfitrion-paso4.php'">Anterior</button>
                    </div>
                    <div class="col-6">
                        <button type="submit" name="siguiente" id="btnSiguiente"
                            class="btn btn-success rounded-pill">Siguiente</button>
                    </div>
                </div>
            </div>
        </form>

        <div class="container-fluid p-3">
            <div class="row text-center">
                <div class="col-12">Paso 5 de 6</div>
            </div>
        </div>
    </div>

    <template id="horario-template">
        <div class="horario-container">
            <button type="button" class="remove-btn remove-horario">
                <i class="fas fa-times"></i>
            </button>

            <div class="horario-header">
                <h5 class="mb-0">Horario #<span class="horario-num"></span></h5>
            </div>

            <div class="row mb-3">
                <div class="col-12">
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

            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Precio por hora (€) *</label>
                    <input type="number" class="form-control" step="0.01" min="0" name="precio_hora[INDEX]" required>
                </div>
            </div>

            <div class="servicios-container">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <label class="form-label fw-bold mb-0">Servicios disponibles en este horario</label>
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

            <div class="row mb-2">
                <div class="col-md-12">
                    <label class="form-label">Nombre del servicio *</label>
                    <input type="text" class="form-control" name="servicio_nombre[HORARIO_INDEX][SERVICIO_INDEX]"
                        required>
                </div>
            </div>

            <div class="row mb-2">
                <div class="col-md-12">
                    <label class="form-label">Descripción del servicio *</label>
                    <textarea class="form-control" rows="2" name="servicio_descripcion[HORARIO_INDEX][SERVICIO_INDEX]"
                        required></textarea>
                </div>
            </div>

            <div class="row mb-2">
                <div class="col-md-6">
                    <label class="form-label">Precio (€) *</label>
                    <input type="number" class="form-control" step="0.01" min="0"
                        name="servicio_precio[HORARIO_INDEX][SERVICIO_INDEX]" required>
                </div>
            </div>
        </div>
    </template>

    <script>
        $(document).ready(function () {
            let horarioCount = $('.horario-container').length;

            // Mostrar el Toast flotante si hay un mensaje de éxito desde PHP
        <?php if (!empty($formSuccess)): ?>
            const toastEl = document.getElementById('toastExitoPaso5');
            const toast = new bootstrap.Toast(toastEl, { delay: 2000 });
            toast.show();
        <?php endif; ?>

            function updateNoHorariosMessage() {
                if ($('.horario-container').length > 0) {
                    $('#no-horarios-message').hide();
                } else {
                    $('#no-horarios-message').show();
                }
            }

            function updateHorarioNumbers() {
                $('.horario-container').each(function (index) {
                    $(this).find('.horario-num').text(index + 1);
                });
            }

            $('#add-horario').click(function () {
                const template = document.getElementById('horario-template').content.cloneNode(true);
                const horarioIndex = horarioCount++;

                let templateHTML = template.querySelector('.horario-container').outerHTML;
                templateHTML = templateHTML.replace(/INDEX/g, horarioIndex);

                $('#horarios-container').append(templateHTML);

                updateHorarioNumbers();
                updateNoHorariosMessage();
            });

            $(document).on('click', '.remove-horario', function () {
                $(this).closest('.horario-container').remove();
                updateHorarioNumbers();
                updateNoHorariosMessage();
            });

            $(document).on('click', '.add-servicio', function () {
                const horarioIndex = $(this).data('horario');
                const serviciosContainer = $(this).closest('.servicios-container').find('.servicios-list');
                const servicioIndex = serviciosContainer.children().length;

                const template = document.getElementById('servicio-template').content.cloneNode(true);
                let templateHTML = template.querySelector('.servicio-item').outerHTML;
                templateHTML = templateHTML.replace(/HORARIO_INDEX/g, horarioIndex);
                templateHTML = templateHTML.replace(/SERVICIO_INDEX/g, servicioIndex);

                serviciosContainer.append(templateHTML);
            });

            $(document).on('click', '.remove-servicio', function () {
                $(this).closest('.servicio-item').remove();
            });

            $('#espacioTrabajoForm').submit(function (e) {
                let valid = true;
                let errorMessages = [];

                if ($('.horario-container').length === 0) {
                    valid = false;
                    errorMessages.push('Debe agregar al menos un horario para el espacio de trabajo.');
                } else {
                    $('.horario-container').each(function (index) {
                        const diasSeleccionados = $(this).find('input[type="checkbox"]:checked').length;
                        if (diasSeleccionados === 0) {
                            valid = false;
                            errorMessages.push(`Debe seleccionar al menos un día en el Horario #${index + 1}.`);
                        }

                        const horaInicio = $(this).find('input[name^="hora_inicio"]').val();
                        const horaFin = $(this).find('input[name^="hora_fin"]').val();

                        if (!horaInicio || !horaFin) {
                            valid = false;
                            errorMessages.push(`Debe completar las horas en el Horario #${index + 1}.`);
                        } else if (horaInicio >= horaFin) {
                            valid = false;
                            errorMessages.push(`La hora de fin debe ser posterior a la hora de inicio en el Horario #${index + 1}.`);
                        }

                        const precioHora = $(this).find('input[name^="precio_hora"]').val();
                        if (!precioHora || isNaN(precioHora) || precioHora <= 0) {
                            valid = false;
                            errorMessages.push(`Debe especificar un precio por hora válido en el Horario #${index + 1}.`);
                        }
                    });
                }

                if (!valid) {
                    e.preventDefault();
                    $('#error-message').show();
                    $('#error-text').text(errorMessages.join(' '));
                    $('html, body').animate({
                        scrollTop: $('#error-message').offset().top - 100
                    }, 300);
                }

                return valid;
            });

            updateHorarioNumbers();
            updateNoHorariosMessage();

            if (horarioCount === 0) {
                $('#add-horario').click();
            }
        });
    </script>
</body>

</html>