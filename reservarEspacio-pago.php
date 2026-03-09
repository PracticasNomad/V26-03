<?php
require_once 'verificar_sesion_guest.php';
require './vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

if (!isset($_SESSION['reserva']) || !isset($_SESSION['spaceId'])) {
    header('Location: nomada_explorar.php');
    exit;
}

$reserva = $_SESSION['reserva'];
$spaceId = $_SESSION['spaceId'];

$ch = curl_init();
$url = "http://" . $_ENV['SERVER_IP'] . ":" . $_ENV['DATABASE_PORT'] . "/rest/v1/space?select=*,schedule(*),establecimiento(nombre)&id=eq." . $spaceId;

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

function calculatePrice($startTime, $endTime, $schedules)
{
    $startMinutes = timeToMinutes($startTime);
    $endMinutes = timeToMinutes($endTime);

    foreach ($schedules as $schedule) {
        $scheduleStart = timeToMinutes(substr($schedule['start_time'], 0, 5));
        $scheduleEnd = timeToMinutes(substr($schedule['end_time'], 0, 5));

        if ($startMinutes >= $scheduleStart && $endMinutes <= $scheduleEnd) {
            $durationHours = ($endMinutes - $startMinutes) / 60;

            $price = $durationHours * $schedule['price'];

            return [
                'price' => $price,
                'duration' => $durationHours,
                'hourly_rate' => $schedule['price']
            ];
        }
    }

    return false;
}

function timeToMinutes($time)
{
    list($hours, $minutes) = explode(':', $time);
    return ($hours * 60) + $minutes;
}

$priceDetails = calculatePrice($reserva['startTime'], $reserva['endTime'], $space['schedule']);

if (!$priceDetails) {
    $_SESSION['error_message'] = "No se pudo calcular el precio para el horario seleccionado.";
    header('Location: reservarEspacio.php?id=' . $spaceId . '&error=' . urlencode($_SESSION['error_message']));
    exit;
}

$subTotal = $priceDetails['price'];
$ivaRate = 0.21;
$ivaAmount = $subTotal * $ivaRate;
$totalPrice = $subTotal + $ivaAmount;

$formattedSubTotal = number_format($subTotal, 2, ',', '.');
$formattedIva = number_format($ivaAmount, 2, ',', '.');
$formattedTotal = number_format($totalPrice, 2, ',', '.');

$_SESSION['reserva']['price'] = [
    'subtotal' => $subTotal,
    'iva' => $ivaAmount,
    'total' => $totalPrice
];
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://kit.fontawesome.com/b8814a2854.js" crossorigin="anonymous"></script>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@200;400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-+0n0xVW2eSR5OomGNYDnhzAbDsOXxcvSN1TPprVMTNDbiYZCxYbOOl7+AMvyTG2x" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta3/dist/js/bootstrap.bundle.min.js" integrity="sha384-JEW9xMcG8R+pH31jmWH6WWP0WintQrMb4s7ZOdauHnUtxwoG2vI5DkLtS3qm9Ekf" crossorigin="anonymous"></script>
    <link rel="icon" href="favicon-color.png">

    <link rel="icon" href="favicon-negro.png" media="(prefers-color-scheme: light)">

    <link rel="icon" href="favicon-color.png" media="(prefers-color-scheme: dark)">

    <title>Pasarela de Pago - Nomadapp</title>

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
            height: 180px;
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

        .price-detail {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0;
            border-bottom: 1px solid #eee;
        }

        .price-total {
            display: flex;
            justify-content: space-between;
            padding: 1rem 0;
            font-weight: 700;
            font-size: 1.2rem;
            color: #00B7CF;
            border-top: 2px solid #00B7CF;
        }

        .iva-detail {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0;
            border-bottom: 1px solid #eee;
            color: #666;
            font-style: italic;
        }

        .reservation-info {
            background-color: #f4f9fa;
            border-left: 4px solid #00B7CF;
            padding: 1rem;
            border-radius: 0 8px 8px 0;
            margin-bottom: 1.5rem;
        }

        .info-label {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 0.2rem;
        }

        .info-value {
            font-weight: 600;
            margin-bottom: 0.7rem;
        }

        .payment-step {
            background-color: #BDE742;
            color: #333;
            width: 25px;
            height: 25px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            margin-right: 0.5rem;
            font-weight: 700;
            font-size: 0.9rem;
        }

        @media (max-width: 768px) {
            .header-container {
                height: 150px;
            }

            .header-overlay h1 {
                font-size: 1.5rem;
            }
        }
    </style>
</head>

<body>
    <div class="container p-0">
        <div class="header-container">
            <img class="header-img" src="https://cdn.pixabay.com/photo/2016/11/18/14/05/brick-wall-1834784_960_720.jpg" alt="Payment Header">
            <div class="header-overlay">
                <div class="text-center">
                    <h1 class="fw-bold mb-2">Completar Pago</h1>
                    <h5 class="fw-normal">Estás a un paso de reservar tu espacio</h5>
                </div>
            </div>
        </div>
    </div>

    <div class="container py-4">
        <div class="row">
            <div class="col-lg-8 mb-4">
                <div class="card">
                    <div class="card-header d-flex align-items-center">
                        <span class="payment-step">2</span>
                        <h4 class="mb-0">Confirmar Reserva</h4>
                    </div>
                    <div class="card-body">
                        <form id="paymentForm" method="POST" action="reservarEspacio-completo.php?sendEmail=true">
                            <div class="reservation-info mb-4">
                                <h5 class="mb-3">Resumen de tu Reserva</h5>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <div class="info-label">Espacio</div>
                                        <div class="info-value"><?php echo htmlspecialchars($space['name']); ?></div>

                                        <div class="info-label">Establecimiento</div>
                                        <div class="info-value"><?php echo htmlspecialchars($space['establecimiento']['nombre']); ?></div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <div class="info-label">Fecha</div>
                                        <div class="info-value"><?php echo date('d/m/Y', strtotime($reserva['date'])); ?></div>

                                        <div class="info-label">Horario</div>
                                        <div class="info-value"><?php echo $reserva['startTime']; ?> - <?php echo $reserva['endTime']; ?></div>
                                    </div>
                                </div>
                            </div>

                            <h5 class="section-title"><i class="fas fa-credit-card me-2"></i>Método de Pago</h5>
                            <div class="mb-4">
                            </div>

                            <div class="text-center mt-4">
                                <a href="reservarEspacio.php?id=<?php echo $spaceId; ?>" class="btn btn-secondary me-3">
                                    <i class="fas fa-arrow-left me-2"></i>Volver Atrás
                                </a>
                                <button type="submit" class="btn btn-nomad">
                                    <i class="fas fa-check-circle me-2"></i>Confirmar Reserva
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-4 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0"><i class="fas fa-receipt me-2"></i>Resumen de Pago</h4>
                    </div>
                    <div class="card-body">
                        <h5 class="section-title">Detalles del Precio</h5>

                        <div class="price-detail">
                            <div>Tarifa base</div>
                            <div><?php echo $formattedSubTotal; ?> €</div>
                        </div>
                        <div class="price-detail">
                            <div>Duración</div>
                            <div>
                                <?php
                                $hours = floor($priceDetails['duration']);
                                $minutes = round(($priceDetails['duration'] - $hours) * 60);

                                if ($hours > 0) {
                                    echo $hours . ' h';
                                    if ($minutes > 0) {
                                        echo ' ' . $minutes . ' min';
                                    }
                                } else {
                                    echo $minutes . ' min';
                                }
                                ?>
                            </div>
                        </div>
                        <div class="price-detail">
                            <div>Precio/hora</div>
                            <div><?php echo number_format($priceDetails['hourly_rate'], 2, ',', '.'); ?> €</div>
                        </div>
                        <div class="iva-detail">
                            <div>IVA (21%)</div>
                            <div><?php echo $formattedIva; ?> €</div>
                        </div>

                        <div class="price-total mt-2">
                            <div>Total</div>
                            <div><?php echo $formattedTotal; ?> €</div>
                        </div>

                        <div class="mt-3">
                            <small class="text-muted">
                                Al completar esta reserva, aceptas nuestros términos de servicio y política de privacidad.
                                El cargo aparecerá en tu cuenta como "NOMADAPP". <br>
                                <strong>IVA incluido en el precio final.</strong>
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>