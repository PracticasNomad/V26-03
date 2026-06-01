<?php
require_once 'verificar_sesion_guest.php';
require './vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

function generateUuidV4()
{
    $data = random_bytes(16);
    $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
    $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

$sendEmail = isset($_GET['sendEmail']) ? $_GET['sendEmail'] : false;

if (!isset($_SESSION['reserva']) || !isset($_SESSION['spaceId'])) {
    header('Location: nomada_explorar.php');
    exit;
}

// Guardamos los datos en variables locales para poder usarlos en el HTML después
$reserva = $_SESSION['reserva'];
$spaceId = $_SESSION['spaceId'];

$ch = curl_init();
$url = "http://" . $_ENV['SERVER_IP'] . ":" . $_ENV['DATABASE_PORT'] . "/rest/v1/space?select=*,establecimiento(*,host(*))&id=eq." . $spaceId;

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

$correoEnviado = false;
$mensajeError = '';

function insertarReserva($reservaData, $codigoUnico)
{
    $url = 'http://' . $_ENV['SERVER_IP'] . ':' . $_ENV['DATABASE_PORT'] . '/rest/v1/reservation';
    $ch = curl_init($url);
    $data = array(
        "id" => $codigoUnico,
        "user_id" => $_SESSION['user_id'],
        "space_id" => $reservaData['spaceId'],
        "start_time" => $reservaData['startTime'],
        "end_time" => $reservaData['endTime'],
        "day" => $reservaData['date'],
        "message" => $reservaData['message'] ?? '',
        "dni_nomada" => $reservaData['dni'] ?? null,
        "direccion" => $reservaData['direccion'] ?? null
    );
    $payload = json_encode($data);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Authorization: Bearer ' . $_SESSION['token'],
        'Content-Type: application/json',
        'apikey: ' . $_ENV['DATABASE_APIKEY'],
        'Prefer: return=representation'
    ));
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode >= 200 && $httpCode < 300) {
        return true;
    } else {
        $errorData = json_decode($response, true);
        $_SESSION['db_error_message'] = isset($errorData['message']) ? $errorData['message'] : $response;
        return false;
    }
}

function enviarCorreo($reservaData, $spaceData)
{
    $emailAnfitrion = $spaceData['establecimiento']['host']['email'];
    $parameters = urlencode('nombre') . '=' . urlencode($_SESSION['name']) . '&' .
        urlencode('establecimiento') . '=' . urlencode($spaceData['establecimiento']['nombre']) . '&' .
        urlencode('espacio') . '=' . urlencode($spaceData['name']) . '&' .
        urlencode('fecha') . '=' . urlencode(date('d/m/Y', strtotime($reservaData['date']))) . '&' .
        urlencode('hora') . '=' . urlencode($reservaData['startTime'] . ' - ' . $reservaData['endTime']) . '&' .
        urlencode('email_anfitrion') . '=' . urlencode($emailAnfitrion) . '&' .
        urlencode('email') . '=' . urlencode($_SESSION['email']);

    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
    $url = $protocol . "://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/../emails/confirmarReserva.php?' . $parameters;

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 3); 
    curl_exec($ch);
    curl_close($ch);
    
    return true;
}

// =======================================================
// EL GRAN ARREGLO: LÓGICA A PRUEBA DE FALLOS FANTASMA
// =======================================================
if ($sendEmail) {
    // 1. Generamos un código único y limpio SÍ O SÍ
    $codigoReserva = generateUuidV4();
    $_SESSION['codigo_reserva'] = $codigoReserva;
    
    // 2. Obligamos a ejecutar el INSERT en la Base de Datos
    $_SESSION['reservaExitosa'] = insertarReserva($reserva, $codigoReserva);
    
    if ($_SESSION['reservaExitosa']) {
        $correoEnviado = enviarCorreo($reserva, $space);
        
        // 3. LIMPIAMOS LA SESIÓN AL INSTANTE
        // Esto evita que si vas a hacer otra reserva asuma que ya está hecha
        unset($_SESSION['reserva']);
        unset($_SESSION['spaceId']);
    } else {
        // MODO CHIVATO: Si Supabase la rechaza ahora (por ej. por ser de noche), 
        // ¡Por fin lo veremos en letras rojas en tu pantalla!
        $errorDB = isset($_SESSION['db_error_message']) ? $_SESSION['db_error_message'] : 'Error desconocido.';
        $mensajeError = 'La base de datos ha rechazado la reserva. ERROR: ' . $errorDB;
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://kit.fontawesome.com/b8814a2854.js" crossorigin="anonymous"></script>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@200;400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.1/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-+0n0xVW2eSR5OomGNYDnhzAbDsOXxcvSN1TPprVMTNDbiYZCxYbOOl7+AMvyTG2x" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-JEW9xMcG8R+pH31jmWH6WWP0WintQrMb4s7ZOdauHnUtxwoG2vI5DkLtS3qm9Ekf"
        crossorigin="anonymous"></script>
    <link rel="icon" href="favicon-color.png">

    <link rel="icon" href="favicon-negro.png" media="(prefers-color-scheme: light)">

    <link rel="icon" href="favicon-color.png" media="(prefers-color-scheme: dark)">

    <title>Reserva Completada - Nomadapp</title>

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

        .confirmation-icon {
            font-size: 4rem;
            color: #BDE742;
            margin-bottom: 1rem;
        }

        .error-icon {
            font-size: 4rem;
            color: #ff5a5a;
            margin-bottom: 1rem;
        }

        .reservation-code {
            background-color: #f0f8ff;
            border: 2px dashed #00B7CF;
            padding: 1rem;
            text-align: center;
            border-radius: 8px;
            font-size: 1.5rem;
            font-weight: 700;
            color: #00B7CF;
            margin: 1.5rem 0;
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

        .success-message {
            color: #28a745;
            font-weight: 600;
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
            <img class="header-img" src="https://cdn.pixabay.com/photo/2016/11/18/14/05/brick-wall-1834784_960_720.jpg"
                alt="Confirmation Header">
            <div class="header-overlay">
                <div class="text-center">
                    <h1 class="fw-bold mb-2">
                        <?php if ($_SESSION['reservaExitosa']): ?>
                            ¡Reserva Completada!
                        <?php else: ?>
                            Error en la Reserva
                        <?php endif; ?>
                    </h1>
                    <h5 class="fw-normal">
                        <?php if ($_SESSION['reservaExitosa']): ?>
                            Tu espacio ha sido reservado con éxito
                        <?php else: ?>
                            Ha ocurrido un problema al procesar tu reserva
                        <?php endif; ?>
                    </h5>
                </div>
            </div>
        </div>
    </div>

    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header d-flex align-items-center">
                        <span class="payment-step">3</span>
                        <h4 class="mb-0">
                            <?php if ($_SESSION['reservaExitosa']): ?>
                                Confirmación de Reserva
                            <?php else: ?>
                                Estado de la Reserva
                            <?php endif; ?>
                        </h4>
                    </div>
                    <div class="card-body">
                        <div class="text-center mb-4">
                            <?php if ($_SESSION['reservaExitosa']): ?>
                                <i class="fas fa-check-circle confirmation-icon"></i>
                                <h3 class="mb-3">¡Tu reserva ha sido confirmada!</h3>
                                <p class="lead">Hemos enviado un correo electrónico con los detalles de tu reserva.</p>

                                <?php if (isset($_SESSION['codigo_reserva'])): ?>
                                    <div class="reservation-code">
                                        <div class="small text-muted mb-2">Código de Reserva</div>
                                        <?php echo $_SESSION['codigo_reserva']; ?>
                                    </div>
                                <?php endif; ?>
                            <?php else: ?>
                                <i class="fas fa-exclamation-circle error-icon"></i>
                                <h3 class="mb-3">Ha ocurrido un problema</h3>
                                <p class="lead text-danger"><?php echo $mensajeError; ?></p>
                            <?php endif; ?>
                        </div>

                        <?php if ($_SESSION['reservaExitosa']): ?>
                            <h5 class="section-title"><i class="fas fa-info-circle me-2"></i>Detalles de la Reserva</h5>
                            <div class="reservation-info mb-4">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <div class="info-label">Espacio</div>
                                        <div class="info-value"><?php echo htmlspecialchars($space['name']); ?></div>

                                        <div class="info-label">Establecimiento</div>
                                        <div class="info-value">
                                            <?php echo htmlspecialchars($space['establecimiento']['nombre']); ?></div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <div class="info-label">Fecha</div>
                                        <div class="info-value"><?php echo date('d/m/Y', strtotime($reserva['date'])); ?>
                                        </div>

                                        <div class="info-label">Horario</div>
                                        <div class="info-value"><?php echo $reserva['startTime']; ?> -
                                            <?php echo $reserva['endTime']; ?></div>
                                    </div>
                                </div>

                                <?php if (isset($reserva['price'])): ?>
                                    <div class="row border-top pt-3 mt-2">
                                        <div class="col-md-6 mb-3">
                                            <div class="info-label">Importe Total (IVA incluido)</div>
                                            <div class="info-value">
                                                <?php echo number_format($reserva['price']['total'], 2, ',', '.'); ?> €</div>

                                            <?php if (isset($reserva['price']['iva'])): ?>
                                                <div class="small text-muted">
                                                    Base: <?php echo number_format($reserva['price']['subtotal'], 2, ',', '.'); ?> €
                                                    +
                                                    IVA (21%): <?php echo number_format($reserva['price']['iva'], 2, ',', '.'); ?> €
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="acciones-reserva">
                                            <h3>Acciones disponibles</h3>

                                            <button class="btn-descargar-pdf" onclick="descargarPDF()"
                                                title="Descargar factura en PDF">
                                                📄 Descargar Factura PDF
                                            </button>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <div class="info-label">Estado del Pago</div>
                                            <div class="info-value success-message"><i class="fas fa-check-circle me-1"></i>
                                                Completado</div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="alert alert-info">
                                <i class="fas fa-envelope me-2"></i> Hemos enviado un correo electrónico con los detalles de
                                tu reserva. Si no lo recibes en los próximos minutos, revisa tu carpeta de spam.
                            </div>
                        <?php endif; ?>

                        <div class="text-center mt-4">
                            <?php if ($_SESSION['reservaExitosa']): ?>
                                <a href="nomada_explorar.php" class="btn btn-nomad" onclick="limpiarSesiones()">
                                    <i class="fas fa-home me-2"></i>Volver al Inicio
                                </a>
                            <?php else: ?>
                                <a href="reservarEspacio.php?id=<?php echo $spaceId; ?>" class="btn btn-secondary me-3">
                                    <i class="fas fa-arrow-left me-2"></i>Volver a la Reserva
                                </a>
                                <a href="nomada_explorar.php" class="btn btn-nomad" onclick="limpiarSesiones()">
                                    <i class="fas fa-home me-2"></i>Volver al Inicio
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function descargarPDF() {
            const url = 'generarFactura.php?id=<?php echo isset($_SESSION['codigo_reserva']) ? $_SESSION['codigo_reserva'] : ''; ?>&dia=<?php echo isset($reserva['date']) ? $reserva['date'] : ''; ?>&start_time=<?php echo isset($reserva['startTime']) ? $reserva['startTime'] : ''; ?>&end_time=<?php echo isset($reserva['endTime']) ? $reserva['endTime'] : ''; ?>&space_name=<?php echo isset($space['name']) ? urlencode($space['name']) : ''; ?>&establecimiento_nombre=<?php echo isset($space['establecimiento']['nombre']) ? urlencode($space['establecimiento']['nombre']) : ''; ?>&dni_nomada=<?php echo isset($reserva['dni']) ? $reserva['dni'] : ''; ?>&direccion=<?php echo isset($reserva['direccion']) ? urlencode($reserva['direccion']) : ''; ?>&price=<?php echo isset($reserva['price']['total']) ? $reserva['price']['total'] : ''; ?>';
            window.open(url, '_blank');
        }

        function limpiarSesiones() {
            fetch('limpiar_sesiones_reserva.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                }
            });
        }

        document.addEventListener('DOMContentLoaded', function () {
            <?php if ($_SESSION['reservaExitosa'] && ($correoEnviado || $reservaYaProcesada)): ?>
                setTimeout(function () {
                    <?php
                    if ($sendEmail || $reservaYaProcesada) {
                    }
                    ?>
                }, 1000);
            <?php endif; ?>
        });
    </script>
</body>

</html>