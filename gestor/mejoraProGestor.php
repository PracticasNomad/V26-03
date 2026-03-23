<?php
require_once 'verificar_sesion_gestor.php';
require '../vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

$supabaseKey = $_ENV['DATABASE_APIKEY'];
$serverIp = $_ENV['SERVER_IP'];
$dbPort = $_ENV['DATABASE_PORT'];

// --- OBTENER PRECIOS DE LA BASE DE DATOS ---
$url = 'http://' . $serverIp . ':' . $dbPort . '/rest/v1/planes_suscripcion?tipo_usuario=eq.gestor&nombre=eq.Pro&select=*';
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'apikey: ' . $supabaseKey,
    'Authorization: Bearer ' . $supabaseKey
]);
$result = curl_exec($ch);
curl_close($ch);
$planes = json_decode($result, true);

// Valores por defecto (Salvavidas adaptados a los precios de Gestor)
$precioMensual = 1900;
$precioAnual = 20900;

if (!empty($planes) && !isset($planes['error'])) {
    $precioMensual = floatval($planes[0]['precio_mensual']);
    $precioAnual = floatval($planes[0]['precio_anual']);
}

// Cálculo del ahorro
$ahorro = ($precioMensual * 12) - $precioAnual;
// --------------------------------------------------
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://kit.fontawesome.com/b8814a2854.js" crossorigin="anonymous"></script>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@200;400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta3/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="icon" href="../favicon-color.png">
    <link rel="icon" href="../favicon-negro.png" media="(prefers-color-scheme: light)">
    <link rel="icon" href="../favicon-color.png" media="(prefers-color-scheme: dark)">
    <title>Suscripción PRO Gestor</title>
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

        .form-control:focus {
            border-color: #00B7CF;
            box-shadow: 0 0 0 0.25rem rgba(0, 183, 207, 0.25);
        }

        .custom-checkbox {
            position: relative;
            padding-left: 28px;
            cursor: pointer;
            font-size: 16px;
            user-select: none;
            display: flex;
            align-items: center;
            margin-bottom: 0.5rem;
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
            <img class="header-img" src="https://cdn.pixabay.com/photo/2016/11/18/14/05/brick-wall-1834784_960_720.jpg"
                alt="Subscription Image">
            <div class="header-overlay text-center">
                <h1 class="fw-bold mb-2">Suscripción PRO Gestor</h1>
                <h3 class="fw-normal">Escala tu cartera de alojamientos</h3>
            </div>
        </div>
    </div>

    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-lg-7 mb-4">
                <?php if (!empty($error_msg)): ?>
                    <div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> <?php echo $error_msg; ?>
                    </div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0"><i class="fas fa-user-circle me-2"></i>Datos de Suscripción</h4>
                    </div>
                    <div class="card-body">
                        <form id="subscriptionForm" method="POST" action="procesarSuscripcionGestor.php">
                            <input type="hidden" name="tipoSuscripcion" value="Pro">
                            <h5 class="section-title"><i class="fas fa-map-marker-alt me-2"></i>Dirección de Facturación
                            </h5>

                            <div class="mb-3">
                                <label for="address" class="form-label">Dirección</label>
                                <input type="text" class="form-control" id="address" name="address" required>
                            </div>

                            <h5 class="section-title"><i class="fas fa-money-check-alt me-2"></i>Selecciona tu Plan</h5>

                            <div class="radio-option mb-3">
                                <div class="custom-checkbox">
                                    <input type="radio" id="planMensual" name="subscriptionPlan" value="mensual"
                                        class="custom-control-input" required>
                                    <label for="planMensual" class="custom-control-label">
                                        Plan Mensual - €<?php echo number_format($precioMensual, 0, ',', '.'); ?>
                                    </label>
                                </div>
                            </div>

                            <div class="radio-option mb-4">
                                <div class="custom-checkbox">
                                    <input type="radio" id="planAnual" name="subscriptionPlan" value="anual"
                                        class="custom-control-input" required>
                                    <label for="planAnual" class="custom-control-label">
                                        Plan Anual - €<?php echo number_format($precioAnual, 0, ',', '.'); ?> 
                                        <span class="text-success fw-bold">(¡Ahorra €<?php echo number_format($ahorro, 0, ',', '.'); ?>!)</span>
                                    </label>
                                </div>
                            </div>

                            <div class="checkbox-container mb-3">
                                <div class="custom-checkbox">
                                    <input type="checkbox" name="termsSubscription" id="termsSubscription"
                                        class="custom-control-input" required>
                                    <label for="termsSubscription" class="custom-control-label">
                                        Acepto los <a href="../condiciones/condicionesSuscripcion.php" target="_blank"
                                            class="terms-link">términos de suscripción</a>
                                    </label>
                                </div>
                            </div>

                            <div class="text-center mt-4">
                                <button type="submit" class="btn btn-nomad me-3" id="continueToPaymentBtn">
                                    <i class="fas fa-credit-card me-2"></i>Continuar con el Pago
                                </button>
                                <a href="Suscripciones.php" class="btn btn-secondary">
                                    <i class="fas fa-times-circle me-2"></i>Cancelar
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>