<?php
require_once 'verificar_sesion_host.php';

require '../vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

$supabaseKey = $_ENV['DATABASE_APIKEY'];
$serverIp = $_ENV['SERVER_IP'];
$dbPort = $_ENV['DATABASE_PORT'];

// --- 1. OBTENER EL PLAN ACTUAL DEL USUARIO ---
$url = "http://" . $serverIp . ":" . $dbPort . "/rest/v1/host?id=eq." . $_SESSION['user_id'];
$ch = curl_init($url);
curl_setopt_array($ch, array(
    CURLOPT_CUSTOMREQUEST => "GET",
    CURLOPT_HTTPHEADER => array(
        'Content-Type: application/json',
        'apikey: ' . $supabaseKey
    ),
    CURLOPT_RETURNTRANSFER => true,
));
$resultado = curl_exec($ch);
$codigoRespuesta = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$plan = "Basico"; // Por defecto
if ($codigoRespuesta === 200) {
    $datos = json_decode($resultado, true);
    if (count($datos) > 0) {
        $plan = $datos[0]['plan'];
    }
}

// --- 2. OBTENER PRECIOS DE LOS PLANES DE LA BASE DE DATOS ---
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

// Valores por defecto (Salvavidas según tus nuevos precios)
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

// Calculamos el ahorro automáticamente
$ahorroPro = ($precioMensualPro * 12) - $precioAnualPro;
$ahorroPremium = ($precioMensualPremium * 12) - $precioAnualPremium;

// Función para separar los euros de los céntimos para el diseño HTML (<small>)
function getWholeAndDecimal($price) {
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
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="icon" href="../favicon-color.png">
    <link rel="icon" href="../favicon-negro.png" media="(prefers-color-scheme: light)">
    <link rel="icon" href="../favicon-color.png" media="(prefers-color-scheme: dark)">
    <title>Planes de Suscripción - Nomad</title>
    <style>
        body {
            font-family: 'Nunito', sans-serif;
            background-color: #f8f9fa;
            min-height: 100vh;
        }

        .container-plans {
            max-width: 1200px;
            margin: 2rem auto;
            margin-bottom: 120px;
            padding: 1rem;
        }

        .hero-section {
            text-align: center;
            margin-bottom: 3rem;
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            padding: 3rem 2rem;
            border-radius: 20px;
            box-shadow: 0 0.5rem 2rem rgba(40, 167, 69, 0.3);
        }

        .hero-section h1 {
            font-weight: 700;
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }

        .hero-section p {
            font-size: 1.2rem;
            opacity: 0.9;
            max-width: 600px;
            margin: 0 auto;
        }

        .plans-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
        }

        .plan-card {
            background-color: white;
            border-radius: 20px;
            box-shadow: 0 0.5rem 2rem rgba(0, 0, 0, 0.08);
            padding: 2rem;
            position: relative;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }

        .plan-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 1rem 3rem rgba(0, 0, 0, 0.15);
        }

        .plan-card.popular {
            border-color: #28a745;
            transform: scale(1.05);
        }

        .plan-card.popular::before {
            content: "MÁS POPULAR";
            position: absolute;
            top: -15px;
            left: 50%;
            transform: translateX(-50%);
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            padding: 0.5rem 1.5rem;
            border-radius: 25px;
            font-size: 0.8rem;
            font-weight: 700;
            letter-spacing: 1px;
        }

        .plan-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .plan-name {
            font-size: 1.5rem;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 0.5rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .plan-price {
            font-size: 2.5rem;
            font-weight: 700;
            color: #28a745;
            margin-bottom: 0.5rem;
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
        }

        .plan-features {
            list-style: none;
            padding: 0;
            margin-bottom: 2rem;
        }

        .plan-features li {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
            font-size: 1rem;
            color: #2c3e50;
        }

        .plan-features li i {
            color: #28a745;
            margin-right: 0.75rem;
            font-size: 1.1rem;
            width: 20px;
        }

        .plan-features .highlight {
            font-weight: 600;
            color: #28a745;
        }

        .commission-badge {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 25px;
            text-align: center;
            margin-bottom: 1.5rem;
            font-weight: 600;
            font-size: 1.1rem;
        }

        .btn-plan {
            width: 100%;
            padding: 1rem;
            border-radius: 15px;
            font-weight: 600;
            font-size: 1.1rem;
            border: none;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .btn-basic {
            background-color: #6c757d;
            color: white;
        }

        .btn-basic:hover {
            background-color: #5a6268;
            transform: translateY(-2px);
        }

        .btn-pro {
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            color: white;
        }

        .btn-pro:hover {
            background: linear-gradient(135deg, #0056b3 0%, #004085 100%);
            transform: translateY(-2px);
        }

        .btn-premium {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
        }

        .btn-premium:hover {
            background: linear-gradient(135deg, #20c997 0%, #17a2b8 100%);
            transform: translateY(-2px);
        }

        button.disabled {
            background-color: #ccc;
            cursor: not-allowed;
            opacity: 0.6;
        }

        .comparison-section {
            background-color: white;
            border-radius: 20px;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.08);
            padding: 2rem;
            margin-bottom: 2rem;
        }

        .comparison-title {
            text-align: center;
            font-size: 2rem;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 2rem;
        }

        .comparison-table {
            overflow-x: auto;
        }

        .table {
            margin-bottom: 0;
        }

        .table thead th {
            background-color: #f8f9fa;
            font-weight: 700;
            color: #2c3e50;
            border: none;
            padding: 1rem;
        }

        .table tbody td {
            padding: 1rem;
            vertical-align: middle;
            border-color: #e9ecef;
        }

        .check-icon {
            color: #28a745;
            font-size: 1.2rem;
        }

        .cross-icon {
            color: #dc3545;
            font-size: 1.2rem;
        }

        .faq-section {
            background-color: white;
            border-radius: 20px;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.08);
            padding: 2rem;
        }

        .faq-title {
            text-align: center;
            font-size: 2rem;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 2rem;
        }

        .accordion-button {
            border-radius: 15px !important;
            font-weight: 600;
            color: #2c3e50;
        }

        .accordion-button:not(.collapsed) {
            background-color: #f0f9f2;
            color: #28a745;
        }

        @media (max-width: 768px) {
            .hero-section h1 {
                font-size: 2rem;
            }

            .hero-section p {
                font-size: 1rem;
            }

            .plans-container {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }

            .plan-card.popular {
                transform: none;
            }

            .plan-price {
                font-size: 2rem;
            }
        }

        /* Estilo especial para el perfil seleccionado */
        #lbl_per a {
            color: #007bff !important;
        }

        #lbl_per a:hover {
            color: #0056b3 !important;
        }

        #lbl_per a:visited {
            color: #007bff !important;
        }
    </style>
</head>

<body>
    <div class="container-plans">

        <?php
        $mensaje = isset($_GET['mensaje']) ? $_GET['mensaje'] : '';
        $error = isset($_GET['error']) ? $_GET['error'] : '';
        ?>

        <?php if ($mensaje == 'plan_bajado'): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle"></i> Tu plan ha sido modificado a <strong>Básico</strong> exitosamente.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if ($error == 'limites_basico'): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle"></i> <strong>No puedes cambiar al Plan Básico.</strong><br>
                Actualmente tienes más de 1 establecimiento o más de 3 espacios en total. Debes <a
                    href="verEstablecimientos.php" class="alert-link">eliminar los necesarios</a> antes de hacer este
                cambio.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if ($error == 'fallo_actualizacion'): ?>
            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                <i class="fas fa-times-circle"></i> Ocurrió un error al intentar cambiar de plan. Por favor, inténtalo más
                tarde.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        <div class="hero-section">
            <h1><i class="fas fa-crown"></i> Planes de Suscripción</h1>
            <p>Elige el plan perfecto para tu negocio de alojamiento. Más establecimientos, menos comisiones, mayores
                beneficios.</p>
        </div>

        <div class="plans-container">
            <div class="plan-card">
                <div class="plan-header">
                    <div class="plan-name">Plan Básico</div>
                    <div class="plan-price">
                        <span class="currency">€</span>0
                        <div class="plan-period">/mes</div>
                    </div>
                </div>

                <div class="commission-badge">
                    Comisión del 15%
                </div>

                <ul class="plan-features">
                    <li><i class="fas fa-check"></i> <span class="highlight">1 establecimiento</span></li>
                    <li><i class="fas fa-check"></i> <span class="highlight">3 espacios máximo</span></li>
                    <li><i class="fas fa-check"></i> Perfecto para empezar</li>
                </ul>

                <button id="btnBasic" class="btn btn-plan btn-basic">
                    <i class="fas fa-rocket"></i> Comenzar gratis
                </button>
            </div>

            <div class="plan-card popular">
                <div class="plan-header">
                    <div class="plan-name">Plan Pro</div>
                    <div class="plan-price">
                        <span class="currency">€</span><?php echo $proPriceParts['whole']; ?><small>.<?php echo $proPriceParts['decimal']; ?></small>
                        <div class="plan-period">/mes</div>
                    </div>
                    <div class="plan-annual">
                        💰 €<?php echo number_format($precioAnualPro, 2); ?>/año (ahorra €<?php echo number_format($ahorroPro, 2); ?>)
                    </div>
                </div>

                <div class="commission-badge">
                    Comisión del 12%
                </div>

                <ul class="plan-features">
                    <li><i class="fas fa-check"></i> <span class="highlight">2 establecimientos</span></li>
                    <li><i class="fas fa-check"></i> <span class="highlight">10 espacios máximo</span></li>
                    <li><i class="fas fa-check"></i> Es un buen equilibrio para aquellos anfitriones que tienen más que
                        ofrecer</li>
                </ul>

                <form action="mejoraPro.php" method="get">
                    <button id="btnPro" type="submit" class="btn btn-plan btn-pro">
                        <i class="fas fa-star"></i> Elegir Pro
                    </button>
                </form>
            </div>

            <div class="plan-card">
                <div class="plan-header">
                    <div class="plan-name">Plan Premium</div>
                    <div class="plan-price">
                        <span class="currency">€</span><?php echo $premiumPriceParts['whole']; ?><small>.<?php echo $premiumPriceParts['decimal']; ?></small>
                        <div class="plan-period">/mes</div>
                    </div>
                    <div class="plan-annual">
                        💰 €<?php echo number_format($precioAnualPremium, 2); ?>/año (ahorra €<?php echo number_format($ahorroPremium, 2); ?>)
                    </div>
                </div>

                <div class="commission-badge">
                    Comisión del 10%
                </div>

                <ul class="plan-features">
                    <li><i class="fas fa-check"></i> <span class="highlight">Establecimientos ilimitados</span></li>
                    <li><i class="fas fa-check"></i> <span class="highlight">Espacios ilimitados</span></li>
                    <li><i class="fas fa-check"></i> Ideal para grandes anfitriones</li>
                </ul>
                <form action="mejoraPremium.php" method="get">
                    <button id="btnPremium" type="submit" class="btn btn-plan btn-premium">
                        <i class="fas fa-crown"></i> Elegir Premium
                    </button>
                </form>

            </div>
        </div>

        <div class="comparison-section">
            <h2 class="comparison-title">Comparativa de Planes</h2>
            <div class="comparison-table">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Características</th>
                            <th class="text-center">Básico</th>
                            <th class="text-center">Pro</th>
                            <th class="text-center">Premium</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong>Precio mensual</strong></td>
                            <td class="text-center">Gratis</td>
                            <td class="text-center">€<?php echo number_format($precioMensualPro, 2); ?></td>
                            <td class="text-center">€<?php echo number_format($precioMensualPremium, 2); ?></td>
                        </tr>
                        <tr>
                            <td><strong>Comisión por reserva</strong></td>
                            <td class="text-center">15%</td>
                            <td class="text-center">12%</td>
                            <td class="text-center">10%</td>
                        </tr>
                        <tr>
                            <td><strong>Establecimientos</strong></td>
                            <td class="text-center">1</td>
                            <td class="text-center">2</td>
                            <td class="text-center">Ilimitados</td>
                        </tr>
                        <tr>
                            <td><strong>Espacios por establecimiento</strong></td>
                            <td class="text-center">3</td>
                            <td class="text-center">10</td>
                            <td class="text-center">Ilimitados</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <?php include 'footerAnfitrion.php'; ?>

    <script>
        var currentPlan = "<?php echo htmlspecialchars($plan); ?>";
        console.log("Plan actual:", currentPlan);

        document.addEventListener('DOMContentLoaded', function () {
            const btnBasic = document.getElementById("btnBasic");
            const btnPro = document.getElementById("btnPro");
            const btnPremium = document.getElementById("btnPremium");

            if (currentPlan == "Pro") {
                btnBasic.disabled = false;
                btnBasic.classList.remove("disabled");
                btnBasic.innerHTML = '<i class="fas fa-arrow-down"></i> Cambiar a Básico';
                btnBasic.onclick = function (e) {
                    window.location.href = "bajarBasico.php";
                };

                btnPro.disabled = true;
                btnPro.innerHTML = '<i class="fas fa-check"></i> Tu plan actual';
                btnPro.classList.add("disabled");

            } else if (currentPlan == "Premium") {
                // Deshabilitar cambiar a Básico o Pro
                btnBasic.disabled = true;
                btnBasic.textContent = "No disponible";
                btnBasic.classList.add("disabled");

                btnPro.disabled = true;
                btnPro.textContent = "No disponible";
                btnPro.classList.add("disabled");

                btnPremium.disabled = false;
                btnPremium.classList.remove("disabled");
                btnPremium.innerHTML = '<i class="fas fa-envelope"></i> Contactar para salir';

                // REDIRECCIÓN AL FORMULARIO CENTRALIZADO
                btnPremium.addEventListener('click', function (e) {
                    e.preventDefault();
                    window.location.href = "../contactanos.php?asunto=CancelarPlanPremiumAnfitrion";
                });

            } else if (currentPlan == "Basico") {
                btnBasic.disabled = true;
                btnBasic.innerHTML = '<i class="fas fa-check"></i> Tu plan actual';
                btnBasic.classList.add("disabled");
            }

            document.querySelectorAll('.plan-card').forEach(card => {
                card.addEventListener('mouseenter', function () {
                    this.style.transform = this.classList.contains('popular') ? 'scale(1.08) translateY(-10px)' : 'translateY(-10px)';
                });

                card.addEventListener('mouseleave', function () {
                    this.style.transform = this.classList.contains('popular') ? 'scale(1.05)' : 'none';
                });
            });
        });
    </script>
</body>

</html>