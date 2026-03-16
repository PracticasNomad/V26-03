<?php
require_once 'verificar_sesion_gestor.php';
require '../vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

// Si no nos pasan ID por la URL, lo devolvemos a las reservas
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: verReservas.php");
    exit;
}

$reservaId = $_GET['id'];

// OBTENEMOS LOS DETALLES DE LA RESERVA (Usando Service Key)
$url = "http://" . $_ENV['SERVER_IP'] . ":" . $_ENV['DATABASE_PORT'] . "/rest/v1/reservation?select=*,space(*,establecimiento(*)),user(*)&id=eq." . urlencode($reservaId);

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $_ENV['SERVICE_APIKEY'],
        'apikey: ' . $_ENV['SERVICE_APIKEY']
    ],
    CURLOPT_RETURNTRANSFER => true
]);
$resultado = curl_exec($ch);
curl_close($ch);

$datos = json_decode($resultado, true);
$reserva = (is_array($datos) && count($datos) > 0) ? $datos[0] : null;

// Formatear fechas si hay reserva
if ($reserva) {
    $fechaReserva = date("d/m/Y", strtotime($reserva['day']));
    $horaInicio = substr($reserva['start_time'], 0, 5);
    $horaFin = substr($reserva['end_time'], 0, 5);
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://kit.fontawesome.com/b8814a2854.js" crossorigin="anonymous"></script>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="icon" href="../favicon-color.png">
    <link rel="icon" href="../favicon-negro.png" media="(prefers-color-scheme: light)">
    <link rel="icon" href="../favicon-color.png" media="(prefers-color-scheme: dark)">
    <title>Detalles de la Reserva</title>
    <style>
        body {
            font-family: 'Nunito', sans-serif;
            background-color: #f8f9fa;
            padding-bottom: 50px;
        }

        .detalle-card {
            max-width: 600px;
            margin: 40px auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 .5rem 1rem rgba(0, 0, 0, .15);
            overflow: hidden;
        }

        .detalle-header {
            background: #00B7CF;
            color: white;
            padding: 20px;
            text-align: center;
        }

        .detalle-body {
            padding: 30px;
        }

        .info-group {
            margin-bottom: 20px;
            border-bottom: 1px solid #eee;
            padding-bottom: 15px;
        }

        .info-group:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }

        .info-label {
            font-weight: bold;
            color: #6c757d;
            font-size: 0.9rem;
            margin-bottom: 5px;
            display: block;
        }

        .info-value {
            font-size: 1.1rem;
            color: #333;
        }

        .btn-volver {
            width: 100%;
            max-width: 600px;
            margin: 0 auto;
            display: block;
            border-radius: 50rem;
            padding: 12px;
            font-weight: bold;
            background-color: #506572;
            color: white;
            transition: all 0.3s;
        }

        .btn-volver:hover {
            background-color: #3b4a54;
            color: white;
        }
    </style>
</head>

<body>
    <header>
        <div class="container-fluid text-center" style="background-color: #00B7CF; color: white;">
            <div class="row">
                <div class="col h3 fw-bold pt-3 pb-2 m-0">Info de la Reserva</div>
            </div>
        </div>
    </header>

    <div class="container">
        <?php if (!$reserva): ?>
            <div class="alert alert-danger mt-5 text-center shadow-sm">
                <i class="fas fa-exclamation-triangle fa-2x mb-3 d-block"></i>
                No se ha encontrado la reserva o no tienes permisos para verla.
            </div>
            <a href="javascript:history.back()" class="btn btn-volver mt-4"><i class="fas fa-arrow-left me-2"></i> Volver</a>
        <?php else: ?>
            <div class="detalle-card">
                <div class="detalle-header">
                    <h4 class="m-0"><i class="far fa-calendar-check me-2"></i> <?php echo $fechaReserva; ?></h4>
                    <span class="badge bg-light text-dark mt-2"><?php echo $horaInicio . ' - ' . $horaFin; ?></span>
                </div>

                <div class="detalle-body">
                    <div class="info-group">
                        <span class="info-label"><i class="fas fa-building me-1"></i> Establecimiento y Espacio</span>
                        <div class="info-value">
                            <strong><?php echo htmlspecialchars($reserva['space']['establecimiento']['nombre'] ?? 'Desconocido'); ?></strong><br>
                            <span class="text-muted"><?php echo htmlspecialchars($reserva['space']['name'] ?? ''); ?></span>
                        </div>
                    </div>

                    <div class="info-group">
                        <span class="info-label"><i class="fas fa-user me-1"></i> Datos del Nómada (Cliente)</span>
                        <div class="info-value">
                            <strong><?php echo htmlspecialchars($reserva['user']['name'] ?? 'Usuario no registrado'); ?></strong><br>
                            <?php if (isset($reserva['user']['email'])): ?>
                                <i class="fas fa-envelope text-muted me-1 mt-2"></i> <?php echo htmlspecialchars($reserva['user']['email']); ?><br>
                            <?php endif; ?>
                            <?php if (isset($reserva['user']['telefono']) && !empty($reserva['user']['telefono'])): ?>
                                <i class="fas fa-phone text-muted me-1 mt-1"></i> <?php echo htmlspecialchars($reserva['user']['telefono']); ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="info-group">
                        <span class="info-label"><i class="fas fa-info-circle me-1"></i> Estado de la reserva</span>
                        <div class="info-value">
                            <?php if ($reserva['cancelada']): ?>
                                <span class="badge bg-danger"><i class="fas fa-times me-1"></i> Cancelada</span>
                            <?php else: ?>
                                <span class="badge bg-success"><i class="fas fa-check me-1"></i> Confirmada</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <a href="javascript:history.back()" class="btn btn-volver mt-4 mb-5 shadow-sm">
                <i class="fas fa-arrow-left me-2"></i> Volver a la lista
            </a>
        <?php endif; ?>
    </div>

</body>

</html>