<?php
require_once 'verificar_sesion_guest.php';
require './vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$spaceId = $_GET['id'] ?? null;

if (!$spaceId) {
    header('Location: nomada_explorar.php');
    exit;
}

$_SESSION['spaceId'] = $spaceId;

$ch = curl_init();
$url = "http://" . $_ENV['SERVER_IP'] . ":" . $_ENV['DATABASE_PORT'] . "/rest/v1/space?select=*,schedule(has_monday,has_tuesday,has_wednesday,has_thursday,has_friday,has_saturday,has_sunday,start_time,end_time),establecimiento(*,host(*))&id=eq." . $spaceId;

curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'apikey: ' . $_ENV['DATABASE_APIKEY'],
    'Content-Type: application/json'
]);

$response = curl_exec($ch);
curl_close($ch);

$spaceData = json_decode($response, true);

if (empty($spaceData)) {
    header('Location: nomada_explorar.php');
    exit;
}

$space = $spaceData[0];
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://kit.fontawesome.com/b8814a2854.js" crossorigin="anonymous"></script>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@200;400;600;700&display=swap" rel="stylesheet">
    <script src='https://api.mapbox.com/mapbox.js/v3.3.1/mapbox.js'></script>
    <link href='https://api.mapbox.com/mapbox.js/v3.3.1/mapbox.css' rel='stylesheet' />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-+0n0xVW2eSR5OomGNYDnhzAbDsOXxcvSN1TPprVMTNDbiYZCxYbOOl7+AMvyTG2x" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta3/dist/js/bootstrap.bundle.min.js" integrity="sha384-JEW9xMcG8R+pH31jmWH6WWP0WintQrMb4s7ZOdauHnUtxwoG2vI5DkLtS3qm9Ekf" crossorigin="anonymous"></script>
    <link rel="icon" href="favicon-color.png">

    <link rel="icon" href="favicon-negro.png" media="(prefers-color-scheme: light)">

    <link rel="icon" href="favicon-color.png" media="(prefers-color-scheme: dark)">

    <title>Reservar Espacio</title>

    <style>
        body {
            min-height: 100vh;
            font-family: 'Nunito', sans-serif;
            background-color: #f8f9fa;
            color: #333;
        }

        .header-container {
            position: relative;
            width: 100%;
            height: 250px;
            overflow: hidden;
            margin-bottom: 0;
        }

        .header-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            filter: brightness(0.7);
        }

        .header-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(rgba(0, 183, 207, 0.7), rgba(0, 183, 207, 0.9));
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            color: white;
            padding: 1rem;
        }

        .card {
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            margin-bottom: 1.5rem;
            border: none;
            overflow: hidden;
        }

        .card-header {
            background-color: #00B7CF;
            color: white;
            font-weight: 600;
            padding: 1rem;
            border-bottom: none;
        }

        .section-title {
            color: #00B7CF;
            font-weight: 700;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #BDE742;
        }

        .info-item {
            display: flex;
            align-items: center;
            margin-bottom: 0.8rem;
        }

        .info-icon {
            color: #00B7CF;
            width: 25px;
            margin-right: 10px;
            text-align: center;
        }

        #map {
            height: 300px;
            width: 100%;
            border-radius: 10px;
        }

        .btn-nomad {
            background-color: #00B7CF;
            color: white;
            border: none;
            border-radius: 50px;
            padding: 0.5rem 1.5rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-nomad:hover {
            background-color: #4CCBD4;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .schedule-badge {
            background-color: #BDE742;
            color: #333;
            padding: 0.5rem 1rem;
            border-radius: 50px;
            margin-right: 0.5rem;
            margin-bottom: 0.5rem;
            display: inline-flex;
            align-items: center;
            font-weight: 600;
        }

        .schedule-badge i {
            margin-right: 5px;
        }

        .form-control:focus {
            border-color: #00B7CF;
            box-shadow: 0 0 0 0.25rem rgba(0, 183, 207, 0.25);
        }

        .day-indicator {
            display: inline-block;
            width: 30px;
            height: 30px;
            line-height: 30px;
            text-align: center;
            border-radius: 50%;
            margin-right: 5px;
            font-weight: 600;
        }

        .day-available {
            background-color: #BDE742;
            color: #333;
        }

        .day-unavailable {
            background-color: #ff5a5a;
            color: white;
        }

        .schedule-row {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
        }

        .availability-days {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
            margin-top: 10px;
        }

        @media (max-width: 768px) {
            .header-container {
                height: 200px;
            }

            .header-overlay h1 {
                font-size: 1.5rem;
            }

            .header-overlay h3 {
                font-size: 1rem;
            }

            .day-indicator {
                width: 25px;
                height: 25px;
                line-height: 25px;
                font-size: 0.8rem;
            }
        }

        .custom-checkbox {
            position: relative;
            padding-left: 28px;
            cursor: pointer;
            font-size: 16px;
            user-select: none;
            display: flex;
            align-items: center;
        }

        .custom-checkbox input {
            position: absolute;
            opacity: 0;
            cursor: pointer;
            height: 0;
            width: 0;
        }

        .custom-control-label {
            position: relative;
            cursor: pointer;
            padding-left: 5px;
        }

        .custom-control-label::before {
            content: "";
            position: absolute;
            left: -28px;
            top: 2px;
            width: 20px;
            height: 20px;
            border: 2px solid #00B7CF;
            background-color: white;
            border-radius: 4px;
            transition: all 0.3s ease;
        }

        .custom-control-input:checked~.custom-control-label::before {
            background-color: #00B7CF;
            border-color: #00B7CF;
        }

        .custom-control-label::after {
            content: "";
            position: absolute;
            left: -24px;
            top: 6px;
            width: 12px;
            height: 12px;
            opacity: 0;
            transition: all 0.3s ease;
        }

        .custom-control-input:checked~.custom-control-label::after {
            opacity: 1;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='white' stroke-width='3' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='20 6 9 17 4 12'%3E%3C/polyline%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: center;
        }

        .custom-control-input:focus~.custom-control-label::before {
            box-shadow: 0 0 0 0.25rem rgba(0, 183, 207, 0.25);
        }

        .terms-link {
            color: #00B7CF;
            font-weight: 600;
            text-decoration: none;
            position: relative;
            transition: all 0.3s ease;
        }

        .terms-link:after {
            content: '';
            position: absolute;
            width: 100%;
            height: 2px;
            bottom: -2px;
            left: 0;
            background-color: #BDE742;
            transform: scaleX(0);
            transform-origin: bottom right;
            transition: transform 0.3s ease;
        }

        .terms-link:hover {
            color: #4CCBD4;
        }

        .terms-link:hover:after {
            transform: scaleX(1);
            transform-origin: bottom left;
        }
    </style>
</head>

<body>
    <div class="container p-0">
        <div class="header-container">
            <img class="header-img" src="https://cdn.pixabay.com/photo/2016/11/18/14/05/brick-wall-1834784_960_720.jpg" alt="Space Image">
            <div class="header-overlay">
                <div class="text-center">
                    <h1 class="fw-bold mb-2"><?php echo htmlspecialchars($space['name']); ?></h1>
                    <h3 class="fw-normal"><?php echo htmlspecialchars($space['establecimiento']['nombre']); ?></h3>
                </div>
            </div>
        </div>
    </div>

    <div class="container py-4">
        <div class="row">
            <div class="col-lg-7 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0"><i class="fas fa-calendar-check me-2"></i>Crear Nueva Reserva</h4>
                    </div>
                    <div class="card-body">
                        <form id="reservationForm" method="POST" action="procesarReserva.php">
                            <h5 class="section-title"><i class="fas fa-clock me-2"></i>Fecha y Horario</h5>

                            <div class="mb-3">
                                <label for="reservationDate" class="form-label">Fecha de Reserva</label>
                                <input type="date" class="form-control" id="reservationDate" name="reservationDate" required>
                                <div class="form-text">Selecciona un día disponible según el horario del espacio.</div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="startTime" class="form-label">Hora de Inicio</label>
                                    <input type="time" class="form-control" id="startTime" name="startTime" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="endTime" class="form-label">Hora de Fin</label>
                                    <input type="time" class="form-control" id="endTime" name="endTime" required>
                                </div>
                            </div>

                            <div id="timeAlert" class="alert alert-danger d-none">
                                La hora seleccionada no está disponible en este espacio. Por favor revisa los horarios disponibles.
                            </div>

                            <h5 class="section-title"><i class="fas fa-user me-2"></i>Datos Personales</h5>

                            <div class="mb-3">
                                <label for="dni" class="form-label">DNI/NIE</label>
                                <input type="text" class="form-control" id="dni" name="dni" required maxlength="9">
                                <div id="dniAlert" class="text-danger small mt-1 d-none fw-bold">
                                    <i class="fas fa-exclamation-circle"></i> El DNI o NIE introducido no es válido.
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="direccion" class="form-label">Dirección</label>
                                <input type="text" class="form-control" id="direccion" name="direccion" required>
                            </div>

                            <h5 class="section-title"><i class="fas fa-comment me-2"></i>Información Adicional</h5>

                            <div class="mb-4">
                                <label for="message" class="form-label">Mensaje (opcional)</label>
                                <textarea class="form-control" id="message" name="message" rows="3" placeholder="Añade cualquier detalle o requisito especial para tu reserva"></textarea>
                            </div>

                            <div class="checkbox-container mb-3">
                                <div class="custom-checkbox">
                                    <input type="checkbox" name="terms" id="terms" class="custom-control-input" required>
                                    <label for="terms" class="custom-control-label">
                                        Acepto las <a href="./condiciones/condicionesReserva.php" target="_blank" class="terms-link">condiciones de reservas</a>
                                    </label>
                                </div>
                            </div>

                            <div class="text-center mt-4">
                                <a href="nomada_explorar.php" class="btn btn-secondary me-3">
                                    <i class="fas fa-arrow-left me-2"></i>Volver Atrás
                                </a>
                                <button type="submit" class="btn btn-nomad" id="submitBtn">
                                    <i class="fas fa-check-circle me-2"></i>Continuar a Pago
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-5 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0"><i class="fas fa-info-circle me-2"></i>Información del Espacio</h4>
                    </div>
                    <div class="card-body">
                        <h5 class="section-title"><i class="fas fa-building me-2"></i>Detalles</h5>
                        <div class="mb-4">
                            <p class="mb-2"><?php echo htmlspecialchars($space['description']); ?></p>
                        </div>

                        <h5 class="section-title"><i class="fas fa-calendar-alt me-2"></i>Disponibilidad</h5>
                        <div id="scheduleInfo" class="mb-4">
                            <?php foreach ($space['schedule'] as $index => $schedule): ?>
                                <div class="schedule-row">
                                    <div class="d-flex align-items-center mb-2">
                                        <i class="far fa-clock me-2 text-primary"></i>
                                        <span class="fw-bold"><?php echo substr($schedule['start_time'], 0, 5); ?> - <?php echo substr($schedule['end_time'], 0, 5); ?></span>
                                    </div>
                                    <div class="availability-days">
                                        <div class="day-indicator <?php echo $schedule['has_monday'] ? 'day-available' : 'day-unavailable'; ?>">L</div>
                                        <div class="day-indicator <?php echo $schedule['has_tuesday'] ? 'day-available' : 'day-unavailable'; ?>">M</div>
                                        <div class="day-indicator <?php echo $schedule['has_wednesday'] ? 'day-available' : 'day-unavailable'; ?>">X</div>
                                        <div class="day-indicator <?php echo $schedule['has_thursday'] ? 'day-available' : 'day-unavailable'; ?>">J</div>
                                        <div class="day-indicator <?php echo $schedule['has_friday'] ? 'day-available' : 'day-unavailable'; ?>">V</div>
                                        <div class="day-indicator <?php echo $schedule['has_saturday'] ? 'day-available' : 'day-unavailable'; ?>">S</div>
                                        <div class="day-indicator <?php echo $schedule['has_sunday'] ? 'day-available' : 'day-unavailable'; ?>">D</div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <h5 class="section-title"><i class="fas fa-user me-2"></i>Información del Host</h5>
                        <div class="mb-4">
                            <div class="info-item">
                                <div class="info-icon"><i class="fas fa-user"></i></div>
                                <div><?php echo htmlspecialchars($space['establecimiento']['host']['name']); ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-icon"><i class="fas fa-phone"></i></div>
                                <div><?php echo htmlspecialchars($space['establecimiento']['host']['phone']); ?></div>
                            </div>
                            <?php if (!empty($space['establecimiento']['descripcion'])): ?>
                                <div class="mt-2">
                                    <p class="text-muted"><?php echo htmlspecialchars($space['establecimiento']['descripcion']); ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const schedules = <?php echo json_encode($space['schedule']); ?>;
        const weekdays = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];

        document.addEventListener('DOMContentLoaded', function() {
            const dateInput = document.getElementById('reservationDate');
            const startTimeInput = document.getElementById('startTime');
            const endTimeInput = document.getElementById('endTime');
            const form = document.getElementById('reservationForm');
            const timeAlert = document.getElementById('timeAlert');
            const submitBtn = document.getElementById('submitBtn');

            const today = new Date();
            const formattedToday = today.toISOString().split('T')[0];
            dateInput.setAttribute('min', formattedToday);
            dateInput.value = formattedToday;

            function validateSchedule() {
                if (!dateInput.value || !startTimeInput.value || !endTimeInput.value) {
                    return false;
                }

                const selectedDate = new Date(dateInput.value);
                const dayOfWeek = selectedDate.getDay();
                const dayName = weekdays[dayOfWeek];

                const startTime = startTimeInput.value;
                const endTime = endTimeInput.value;

                const now = new Date();
                const todayStr = now.getFullYear() + '-' + String(now.getMonth() + 1).padStart(2, '0') + '-' + String(now.getDate()).padStart(2, '0');

                if (dateInput.value === todayStr) {
                    const currentHours = String(now.getHours()).padStart(2, '0');
                    const currentMinutes = String(now.getMinutes()).padStart(2, '0');
                    const currentTime = `${currentHours}:${currentMinutes}`;

                    if (startTime <= currentTime) {
                        timeAlert.textContent = "No puedes reservar a una hora que ya ha pasado en el día de hoy.";
                        timeAlert.classList.remove('d-none');
                        return false;
                    }
                }

                if (startTime >= endTime) {
                    timeAlert.textContent = "La hora de inicio debe ser anterior a la hora de fin.";
                    timeAlert.classList.remove('d-none');
                    return false;
                }

                const compatibleSchedule = schedules.find(schedule => {
                    if (!schedule[`has_${dayName}`]) {
                        return false;
                    }

                    return startTime >= schedule.start_time.substring(0, 5) &&
                        endTime <= schedule.end_time.substring(0, 5);
                });

                if (!compatibleSchedule) {
                    timeAlert.textContent = "El horario seleccionado no está disponible. Por favor, revisa los horarios disponibles para este día.";
                    timeAlert.classList.remove('d-none');
                    return false;
                }

                timeAlert.classList.add('d-none');
                return true;
            }

            [startTimeInput, endTimeInput, dateInput].forEach(input => {
                input.addEventListener('change', validateSchedule);
            });

            // --- FUNCIÓN MATEMÁTICA PARA VALIDAR DNI/NIE ---
            function validarDNI(dni) {
                let numero, letraIngresada, letraCalculada;
                const expresion_regular_dni = /^[XYZ]?\d{5,8}[A-Z]$/i;
                dni = dni.trim().toUpperCase();

                if (expresion_regular_dni.test(dni) === true) {
                    numero = dni.substr(0, dni.length - 1);
                    numero = numero.replace('X', 0).replace('Y', 1).replace('Z', 2);
                    letraIngresada = dni.substr(dni.length - 1, 1); // Extraemos la letra que ha puesto el usuario
                    numero = numero % 23;
                    letraCalculada = 'TRWAGMYFPDXBNJZSQVHLCKE'.substring(numero, numero + 1); // Calculamos la real
                    return letraCalculada === letraIngresada;
                }
                return false;
            }

            form.addEventListener('submit', function(e) {
                e.preventDefault();

                // 1. Validar el horario
                if (!validateSchedule()) {
                    return false;
                }

                // 2. Validar el DNI/NIE
                const inputDni = document.getElementById('dni');
                const dniAlert = document.getElementById('dniAlert');
                
                if (!validarDNI(inputDni.value)) {
                    inputDni.classList.add('is-invalid');
                    dniAlert.classList.remove('d-none');
                    inputDni.focus();
                    return false; // Cortamos el envío aquí
                } else {
                    inputDni.classList.remove('is-invalid');
                    dniAlert.classList.add('d-none');
                }

                // Si pasamos las dos validaciones, guardamos y enviamos
                const formData = new FormData(form);

                const reservationData = {
                    spaceId: '<?php echo $space['id']; ?>',
                    date: formData.get('reservationDate'),
                    startTime: formData.get('startTime'),
                    endTime: formData.get('endTime'),
                    dni: formData.get('dni'),
                    direccion: formData.get('direccion'),
                    message: formData.get('message')
                };

                sessionStorage.setItem('reservationData', JSON.stringify(reservationData));

                form.submit();
            });
        });
    </script>
</body>

</html>