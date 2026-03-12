<?php
require_once 'verificar_sesion_gestor.php';

require '../vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(DIRNAME(__DIR__));
$dotenv->load();

if (!isset($_SESSION['direccion'], $_SESSION['plan'], $_SESSION['fecha_fin'], $_SESSION['total'])) {
    echo "No hay datos de suscripción almacenados. <a href='Suscripciones.php'>Volver a planes</a>";
    exit;
}

$direccion = htmlspecialchars($_SESSION['direccion']);
$plan = htmlspecialchars(ucfirst($_SESSION['plan']));
$fechaFin = date('d/m/Y', strtotime($_SESSION['fecha_fin']));
$total = number_format($_SESSION['total'], 2, ',', '.');

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Resumen de Pago - Nomadapp</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://kit.fontawesome.com/b8814a2854.js" crossorigin="anonymous"></script>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@200;400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta3/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="icon" href="../favicon-color.png">
    <link rel="icon" href="../favicon-negro.png" media="(prefers-color-scheme: light)">
    <link rel="icon" href="../favicon-color.png" media="(prefers-color-scheme: dark)">
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
            border: none;
        }

        .card-header {
            background-color: #00B7CF;
            color: white;
            font-weight: 600;
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
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            color: white;
        }

        .price-detail,
        .price-total {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0;
        }

        .price-total {
            font-weight: 700;
            font-size: 1.2rem;
            color: #00B7CF;
            border-top: 2px solid #00B7CF;
            padding-top: 1rem;
        }

        .subscription-info {
            background-color: #f4f9fa;
            border-left: 4px solid #00B7CF;
            padding: 1rem;
            border-radius: 0 8px 8px 0;
        }

        .info-label {
            color: #666;
            font-size: 0.9rem;
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
    </style>
</head>

<body>
    <div class="container p-0">
        <div class="header-container">
            <img class="header-img" src="https://cdn.pixabay.com/photo/2016/11/18/14/05/brick-wall-1834784_960_720.jpg"
                alt="Header">
            <div class="header-overlay">
                <div class="text-center">
                    <h1 class="fw-bold mb-2">Completar Mejora (Premium Gestor)</h1>
                    <h5 class="fw-normal">Consigue acceso ilimitado a todas las herramientas</h5>
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
                        <h4 class="mb-0">Resumen de Pago</h4>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="mejoraPremiumGestorCompleto.php">
                            <div class="subscription-info mb-4">
                                <h5 class="mb-3">Tus Datos</h5>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="info-label">Dirección</div>
                                        <div class="info-value"><?php echo $direccion; ?></div>
                                        <div class="info-label">Plan Seleccionado</div>
                                        <div class="info-value">Premium Gestor (<?php echo $plan; ?>)</div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="info-label">Fecha de Renovación</div>
                                        <div class="info-value"><?php echo $fechaFin; ?></div>
                                    </div>
                                </div>
                            </div>

                            <h5 class="section-title"><i class="fas fa-credit-card me-2"></i>Pasarela de Pago</h5>
                            <p class="text-muted small">Aquí iría el elemento de pago con tarjeta (Stripe/Redsys).</p>

                            <div class="text-center mt-4">
                                <a href="mejoraPremiumGestor.php" class="btn btn-secondary me-3">
                                    <i class="fas fa-arrow-left me-2"></i>Volver Atrás
                                </a>

                                <button type="submit" class="btn btn-nomad">
                                    <i class="fas fa-check-circle me-2"></i>Confirmar y Pagar
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-4 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0"><i class="fas fa-receipt me-2"></i>Importe</h4>
                    </div>
                    <div class="card-body">
                        <h5 class="section-title">Detalles</h5>
                        <div class="price-detail">
                            <span>Suscripción <?php echo $plan; ?></span>
                            <span>€<?php echo $total; ?></span>
                        </div>

                        <div class="price-total mt-2">
                            <span>Total a pagar</span>
                            <span>€<?php echo $total; ?></span>
                        </div>

                        <div class="mt-3">
                            <small class="text-muted">
                                Al completar esta mejora, aceptas nuestros términos de servicio y política de
                                privacidad.
                                El cargo aparecerá como "NOMADAPP".
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>