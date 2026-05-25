<?php
session_start();

require '../vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->safeLoad();

$formSuccess = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['plan'])) {
    $_SESSION['plan_seleccionado'] = $_POST['plan'];

    // --- ACTUALIZAR BORRADOR EN BASE DE DATOS ---
    if (isset($_SESSION['host']['email'])) {
        $emailUpd = $_SESSION['host']['email'];
        $urlUpd = 'http://' . $_ENV['SERVER_IP'] . ':' . $_ENV['DATABASE_PORT'] . '/rest/v1/registros_abandonados?email=eq.' . urlencode($emailUpd);
        $chUpd = curl_init($urlUpd);
        $dataUpd = [
            'paso' => 7, // El 7 representa el paso de 'Verificar'
            'datos_sesion' => json_encode($_SESSION)
        ];
        curl_setopt($chUpd, CURLOPT_CUSTOMREQUEST, 'PATCH');
        curl_setopt($chUpd, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($chUpd, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'apikey: ' . $_ENV['DATABASE_APIKEY']
        ]);
        curl_setopt($chUpd, CURLOPT_POSTFIELDS, json_encode($dataUpd));
        curl_exec($chUpd);
        curl_close($chUpd);
    }
    // --- FIN ACTUALIZAR BORRADOR ---

    $formSuccess = "Plan seleccionado y datos guardados correctamente. Redirigiendo...";

    echo "<script>
        setTimeout(function() {
            window.location.href = 'registerAnfitrion-pasoVerificar.php';
        }, 1500);
    </script>";
}

// --- OBTENER PRECIOS DE LOS PLANES DE LA BASE DE DATOS ---
$supabaseKey = $_ENV['DATABASE_APIKEY'];
$serverIp = $_ENV['SERVER_IP'];
$dbPort = $_ENV['DATABASE_PORT'];

$urlPlanes = "http://" . $serverIp . ":" . $dbPort . "/rest/v1/planes_suscripcion?tipo_usuario=eq.host&select=*";
$chPlanes = curl_init($urlPlanes);
curl_setopt_array($chPlanes, array(
    CURLOPT_CUSTOMREQUEST => "GET",
    CURLOPT_HTTPHEADER => array(
        'Content-Type: application/json',
        'apikey: ' . $supabaseKey
    ),
    CURLOPT_RETURNTRANSFER => true,
));
$resultadoPlanes = curl_exec($chPlanes);
curl_close($chPlanes);
$planesObtenidos = json_decode($resultadoPlanes, true);

$precioMensualPro = 9.99;
$precioAnualPro = 99.99;
$precioMensualPremium = 19.99;
$precioAnualPremium = 179.99;

if (is_array($planesObtenidos) && !isset($planesObtenidos['error'])) {
    foreach ($planesObtenidos as $p) {
        if ($p['nombre'] === 'Pro') {
            $precioMensualPro = floatval($p['precio_mensual']);
            $precioAnualPro = floatval($p['precio_anual']);
        } elseif ($p['nombre'] === 'Premium') {
            $precioMensualPremium = floatval($p['precio_mensual']);
            $precioAnualPremium = floatval($p['precio_anual']);
        }
    }
}

$ahorroPro = ($precioMensualPro * 12) - $precioAnualPro;
$ahorroPremium = ($precioMensualPremium * 12) - $precioAnualPremium;

function getWholeAndDecimal($price)
{
    $parts = explode('.', number_format($price, 2, '.', ''));
    return ['whole' => $parts[0], 'decimal' => $parts[1]];
}
$proPriceParts = getWholeAndDecimal($precioMensualPro);
$premiumPriceParts = getWholeAndDecimal($precioMensualPremium);
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
    <link rel="icon" href="../favicon-color.png">
    <title>Elige tu Plan - Paso 6</title>
    <style>
        body {
            font-family: 'Nunito', sans-serif;
            background-color: #f8f9fa;
        }

        /* Estilos de botones estilo Paso 5 */
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

        /* Estilos del cuadro verde (Hero Section) */
        .hero-section {
            text-align: center;
            margin-bottom: 2rem;
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            padding: 2.5rem 2rem;
            border-radius: 20px;
            box-shadow: 0 0.5rem 2rem rgba(40, 167, 69, 0.3);
        }

        .hero-section h1 {
            font-weight: 700;
            font-size: 2.2rem;
            margin-bottom: 1rem;
        }

        .hero-section p {
            font-size: 1.1rem;
            opacity: 0.9;
            max-width: 600px;
            margin: 0 auto;
        }

        @media (max-width: 768px) {
            .hero-section h1 {
                font-size: 1.8rem;
            }

            .hero-section p {
                font-size: 1rem;
            }
        }

        .contenedorAlta {
            max-width: 1200px;
            margin: 2rem auto;
            background-color: white;
            border-radius: 15px;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            padding: 2rem;
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
            width: 100%;
            background-color: #28a745;
        }

        .plans-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-top: 2.5rem;
        }

        .plan-card {
            border-radius: 20px;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.08);
            padding: 2rem;
            position: relative;
            transition: all 0.3s ease;
            border: 2px solid #e9ecef;
            text-align: center;
            background-color: white;
            cursor: pointer;
            /* Que parezca clicable */
            display: flex;
            flex-direction: column;
            height: 100%;
        }

        /* Clase añadida por JavaScript cuando se selecciona un plan */
        .plan-card.selected {
            border: 3px solid #28a745;
            box-shadow: 0 1.5rem 3rem rgba(40, 167, 69, 0.25);
            transform: translateY(-10px);
        }

        .plan-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 1rem 3rem rgba(0, 0, 0, 0.15);
        }

        .plan-card.popular {
            border-color: #28a74699;
            transform: scale(1.05);
            z-index: 1;
        }

        .plan-card.popular:hover {
            transform: scale(1.08) translateY(-10px);
            box-shadow: 0 1.5rem 3rem rgba(40, 167, 69, 0.25);
        }

        /* Al seleccionar el Pro, lo hacemos ligeramente más grande aún */
        .plan-card.popular.selected {
            transform: scale(1.08) translateY(-10px);
            border: 3px solid #28a745;
        }

        .plan-price .currency {
            font-size: 1.2rem;
            vertical-align: super;
        }

        .plan-period {
            color: #6c757d;
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }

        .plan-annual {
            background-color: #f0f9f2;
            border: 1px solid #28a745;
            border-radius: 10px;
            padding: 0.5rem;
            font-size: 0.85rem;
            color: #28a745;
            font-weight: 600;
            margin-bottom: 0.75rem;
        }

        .plan-features .highlight {
            font-weight: 600;
            color: #28a745;
        }

        .plan-card.popular::before {
            content: "MÁS POPULAR";
            position: absolute;
            top: -20px;
            left: 50%;
            transform: translateX(-50%);
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            padding: 0.3rem 1.5rem;
            border-radius: 25px;
            font-size: 0.8rem;
            font-weight: 700;
            letter-spacing: 1px;
        }

        .plan-name {
            font-size: 1.5rem;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 0.5rem;
            text-transform: uppercase;
        }

        .plan-price {
            font-size: 2.5rem;
            font-weight: 700;
            color: #28a745;
            margin-bottom: 0.5rem;
        }

        .commission-badge {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            padding: 0.5rem;
            border-radius: 15px;
            margin-bottom: 1rem;
            font-weight: 600;
        }

        .plan-features {
            list-style: none;
            padding: 0;
            margin-bottom: 1rem;
            text-align: left;
        }

        .plan-features li {
            margin-bottom: 1rem;
            color: #2c3e50;
        }

        .plan-features li i {
            color: #28a745;
            margin-right: 0.5rem;
        }

        .btn-plan {
            width: 100%;
            padding: 1rem;
            border-radius: 15px;
            font-weight: 600;
            border: none;
            transition: all 0.3s ease;
            text-transform: uppercase;
            pointer-events: none;
            /* Para que el click lo recoja la tarjeta entera */
            margin-top: auto;
        }

        .btn-basic {
            background-color: #28a745;
            color: white;
        }

        .btn-pro {
            background-color: #0069d9;
            color: white;
        }

        .btn-premium {
            background-color: #28a745;
            color: white;
        }
    </style>
</head>

<body>
    <div class="contenedorAlta">
        <div class="hero-section">
            <h1><i class="fas fa-crown"></i> Planes de Suscripción</h1>
            <p>Elige el plan perfecto para tu negocio de alojamiento. Más establecimientos, menos comisiones, mayores
                beneficios.</p>
        </div>

        <div class="alert alert-success" id="success-message" <?php echo !empty($formSuccess) ? 'style="display:block"' : 'style="display:none"'; ?>>
            <i class="fas fa-check-circle me-2"></i> <span id="success-text"><?php echo $formSuccess; ?></span>
        </div>

        <form method="post" action="" id="planForm">
            <input type="hidden" name="plan" id="planInput" value="Basico">

            <div class="plans-container">
                <div class="plan-card selected" onclick="seleccionarPlan('Basico', this)">
                    <div class="plan-name">PLAN BÁSICO</div>
                    <div class="plan-price">
                        <span class="currency">€</span>0
                        <div class="plan-period">/mes</div>
                    </div>
                    <div class="commission-badge">Comisión del 15%</div>
                    <ul class="plan-features">
                        <li><i class="fas fa-check"></i> <span class="highlight">1 establecimiento</span></li>
                        <li><i class="fas fa-check"></i> <span class="highlight">3 espacios máximo</span></li>
                        <li><i class="fas fa-check"></i> Perfecto para empezar</li>
                    </ul>
                    <button type="button" class="btn btn-plan btn-basic">
                        <i class="fas fa-rocket"></i> Elegir Básico
                    </button>
                </div>

                <div class="plan-card popular" onclick="seleccionarPlan('Pro', this)">
                    <div class="plan-name">PLAN PRO</div>
                    <div class="plan-price">
                        <span class="currency">€</span><?php echo $proPriceParts['whole']; ?><small>.<?php echo $proPriceParts['decimal']; ?></small>
                        <div class="plan-period">/mes</div>
                    </div>
                    <div class="plan-annual">
                        💰 €<?php echo number_format($precioAnualPro, 2); ?>/año (ahorra €<?php echo number_format($ahorroPro, 2); ?>)
                    </div>
                    <div class="commission-badge">Comisión del 12%</div>
                    <ul class="plan-features">
                        <li><i class="fas fa-check"></i> <span class="highlight">2 establecimientos</span></li>
                        <li><i class="fas fa-check"></i> <span class="highlight">10 espacios máximo</span></li>
                        <li><i class="fas fa-check"></i> Es un buen equilibrio para aquellos anfitriones que tienen más
                            que ofrecer</li>
                    </ul>
                    <button type="button" class="btn btn-plan btn-pro">
                        <i class="fas fa-star"></i> Elegir Pro
                    </button>
                </div>

                <div class="plan-card" onclick="seleccionarPlan('Premium', this)">
                    <div class="plan-name">PLAN PREMIUM</div>
                    <div class="plan-price">
                        <span class="currency">€</span><?php echo $premiumPriceParts['whole']; ?><small>.<?php echo $premiumPriceParts['decimal']; ?></small>
                        <div class="plan-period">/mes</div>
                    </div>
                    <div class="plan-annual">
                        💰 €<?php echo number_format($precioAnualPremium, 2); ?>/año (ahorra €<?php echo number_format($ahorroPremium, 2); ?>)
                    </div>
                    <div class="commission-badge">Comisión del 10%</div>
                    <ul class="plan-features">
                        <li><i class="fas fa-check"></i> <span class="highlight">Establecimientos ilimitados</span></li>
                        <li><i class="fas fa-check"></i> <span class="highlight">Espacios ilimitados</span></li>
                        <li><i class="fas fa-check"></i> Ideal para grandes anfitriones</li>
                    </ul>
                    <button type="button" class="btn btn-plan btn-premium">
                        <i class="fas fa-crown"></i> Elegir Premium
                    </button>
                </div>
            </div>

            <div class="progress-container mt-5">
                <div class="progress-bar" style="width: 100%;"></div>
            </div>

            <div class="container mt-4">
                <div class="row">
                    <div class="col-6 text-end">
                        <button class="btn btn-cancel rounded-pill" type="button"
                            onclick="location.href='registerAnfitrion-paso5.php'">Anterior</button>
                    </div>
                    <div class="col-6">
                        <button type="submit" name="siguiente" id="btnSiguiente"
                            class="btn btn-success rounded-pill">Terminar</button>
                    </div>
                </div>
            </div>
        </form>

        <div class="container-fluid p-3">
            <div class="row text-center">
                <div class="col-12">Paso 6 de 6</div>
            </div>
        </div>
    </div>

    <script>
        function seleccionarPlan(planName, element) {
            // Actualizar el valor del input oculto
            document.getElementById('planInput').value = planName;

            // Quitar la clase 'selected' de todas las tarjetas
            var tarjetas = document.querySelectorAll('.plan-card');
            tarjetas.forEach(function(tarjeta) {
                tarjeta.classList.remove('selected');
            });

            // Añadir la clase 'selected' a la tarjeta donde se ha hecho click
            element.classList.add('selected');
        }
    </script>
    <?php include '../typebot.php'; ?>
</body>

</html>