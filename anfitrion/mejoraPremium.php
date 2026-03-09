<?php
require_once 'verificar_sesion_host.php';

// Verificamos que llegaron los datos
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['address']) && isset($_POST['subscriptionPlan']) && isset($_POST['termsSubscription'])) {
        $_SESSION['direccion'] = $_POST['address'];
        $_SESSION['plan'] = $_POST['subscriptionPlan'];

        // Establecer la fecha de inicio (hoy)
        $fechaInicio = date('Y-m-d');
        $_SESSION['fecha_inicio'] = $fechaInicio;

        // Calcular fecha de fin y precios según el plan
        if ($_POST['subscriptionPlan'] === 'Mensual') {
            $fechaFin = date('Y-m-d', strtotime('+1 month'));
            $precio = 19.99;
        } else { // Asumimos que cualquier otro valor válido es 'Anual'
            $fechaFin = date('Y-m-d', strtotime('+1 year'));
            $precio = 99.99;
        }

        $_SESSION['fecha_fin'] = $fechaFin;
        $_SESSION['precio_base'] = $precio;
        $_SESSION['total'] = $precio;

        // Redirigir a la página de mejora
        header("Location: mejorarPremium.php");
        exit();
    } else {
        echo "Faltan datos obligatorios. Por favor, vuelve atrás e intenta de nuevo.";
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-+0n0xVW2eSR5OomGNYDnhzAbDsOXxcvSN1TPprVMTNDbiYZCxYbOOl7+AMvyTG2x" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta3/dist/js/bootstrap.bundle.min.js" integrity="sha384-JEW9xMcG8R+pH31jmWH6WWP0WintQrMb4s7ZOdauHnUtxwoG2vI5DkLtS3qm9Ekf" crossorigin="anonymous"></script>
    <link rel="icon" href="../favicon-color.png">
    <link rel="icon" href="../favicon-negro.png" media="(prefers-color-scheme: light)">
    <link rel="icon" href="../favicon-color.png" media="(prefers-color-scheme: dark)">

    <title>Suscripción PREMIUM</title>

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
            /* Added margin for spacing between checkboxes */
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

        .radio-option {
            margin-bottom: 1rem;
        }
    </style>
</head>

<body>
    <div class="container p-0">
        <div class="header-container">
            <img class="header-img" src="https://cdn.pixabay.com/photo/2016/11/18/14/05/brick-wall-1834784_960_720.jpg" alt="Subscription Image">
            <div class="header-overlay text-center">
                <h1 class="fw-bold mb-2">Suscripción PREMIUM</h1>
                <h3 class="fw-normal">Desbloquea todas las ventajas de NomadApp</h3>
            </div>
        </div>
    </div>

    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-lg-7 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0"><i class="fas fa-user-circle me-2"></i>Datos de Suscripción</h4>
                    </div>
                    <div class="card-body">
                        <form id="subscriptionForm" method="POST" action="procesarSuscripcion.php">
                            <input type="hidden" name="tipoSuscripcion" value="Premium">
                            <h5 class="section-title"><i class="fas fa-map-marker-alt me-2"></i>Dirección</h5>

                            <div class="mb-3">
                                <label for="address" class="form-label">Dirección</label>
                                <input type="text" class="form-control" id="address" name="address" required>
                            </div>

                            <h5 class="section-title"><i class="fas fa-money-check-alt me-2"></i>Selecciona tu Plan</h5>

                            <div class="radio-option">
                                <div class="custom-checkbox">
                                    <input type="radio" id="planMensual" name="subscriptionPlan" value="mensual" class="custom-control-input" required>
                                    <label for="planMensual" class="custom-control-label">
                                        Plan Mensual - 19.99 €
                                    </label>
                                </div>
                            </div>

                            <div class="radio-option mb-4">
                                <div class="custom-checkbox">
                                    <input type="radio" id="planAnual" name="subscriptionPlan" value="anual" class="custom-control-input" required>
                                    <label for="planAnual" class="custom-control-label">
                                        Plan Anual - 179.99 € (¡Ahorra 59.89 €!)
                                    </label>
                                </div>
                            </div>

                            <div class="checkbox-container mb-3">
                                <div class="custom-checkbox">
                                    <input type="checkbox" name="termsSubscription" id="termsSubscription" class="custom-control-input" required>
                                    <label for="termsSubscription" class="custom-control-label">
                                        Acepto los <a href="../condiciones/condicionesSuscripcion.php" target="_blank" class="terms-link">términos de suscripción</a>
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