<?php
require_once 'verificar_sesion_gestor.php';

require '../vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

$supabaseKey = $_ENV['DATABASE_APIKEY'];
$serverIp = $_ENV['SERVER_IP'];
$dbPort = $_ENV['DATABASE_PORT'];

// --- OBTENER EL PLAN ACTUAL DEL GESTOR ---
$url = "http://" . $serverIp . ":" . $dbPort . "/rest/v1/gestor?id=eq." . $_SESSION['user_id'];

$ch = curl_init($url);
curl_setopt_array($ch, array(
    CURLOPT_CUSTOMREQUEST => "GET",
    CURLOPT_HTTPHEADER => array(
        'Content-Type: application/json',
        'apikey: ' . $supabaseKey,
        'Authorization: Bearer ' . $_SESSION['token']
    ),
    CURLOPT_RETURNTRANSFER => true,
));

$resultado = curl_exec($ch);
$codigoRespuesta = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$plan = 'Basico'; // Plan por defecto

if ($codigoRespuesta >= 200 && $codigoRespuesta < 300) {
    $datos = json_decode($resultado, true);
    if (is_array($datos) && count($datos) > 0 && isset($datos[0]['plan'])) {
        $plan = $datos[0]['plan'];
        $_SESSION['plan_actual'] = $plan;
    }
}

// --- OBTENER PRECIOS DE LOS PLANES DE LA BASE DE DATOS (GESTOR) ---
$urlPlanes = "http://" . $serverIp . ":" . $dbPort . "/rest/v1/planes_suscripcion?tipo_usuario=eq.gestor&select=*";
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

// Valores por defecto (Salvavidas según precios de Gestor)
$precioMensualBasico = 700;
$precioAnualBasico = 7700;
$precioMensualPro = 1900;
$precioAnualPro = 20900;
$precioMensualPremium = 2850;
$precioAnualPremium = 31350;

if (is_array($planesObtenidos) && !isset($planesObtenidos['error'])) {
    foreach ($planesObtenidos as $p) {
        if ($p['nombre'] === 'Basico') {
            $precioMensualBasico = floatval($p['precio_mensual']);
            $precioAnualBasico = floatval($p['precio_anual']);
        } elseif ($p['nombre'] === 'Pro') {
            $precioMensualPro = floatval($p['precio_mensual']);
            $precioAnualPro = floatval($p['precio_anual']);
        } elseif ($p['nombre'] === 'Premium') {
            $precioMensualPremium = floatval($p['precio_mensual']);
            $precioAnualPremium = floatval($p['precio_anual']);
        }
    }
}

// Calculamos el ahorro automáticamente
$ahorroBasico = ($precioMensualBasico * 12) - $precioAnualBasico;
$ahorroPro = ($precioMensualPro * 12) - $precioAnualPro;
$ahorroPremium = ($precioMensualPremium * 12) - $precioAnualPremium;
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
    <title>Planes de Suscripción - Gestoras</title>
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
            background-color: #ccc !important;
            background-image: none !important;
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

        @media (max-width: 768px) {
            .hero-section h1 { font-size: 2rem; }
            .hero-section p { font-size: 1rem; }
            .plans-container { grid-template-columns: 1fr; gap: 1.5rem; }
            .plan-card.popular { transform: none; }
        }

        /* Estilos del footer (adaptado a gestoras) */
        .footer-container {
            background-color: white;
            box-shadow: 0px -2px 10px rgba(0, 0, 0, 0.1);
            padding-top: 1px !important;
            padding-bottom: 1px !important;
            height: auto;
        }

        .footer-item { padding: 8px 0; }
        .icon-container { transition: transform 0.3s ease; padding: 5px 0; }
        .footer-item:hover .icon-container { transform: translateY(-7px); }
        .footer-item a { text-decoration: none !important; color: #000000 !important; }
        .footer-item a:hover, .footer-item a:visited { color: #000000 !important; }
        
        /* Ocultamos radio buttons del footer */
        .footer-container input[type="radio"] { display: none; }
    </style>
</head>

<body>
    <div class="container-plans">
        <div class="hero-section">
            <h1><i class="fas fa-crown"></i> Planes de Suscripción</h1>
            <p>Elige el plan perfecto para tu negocio de gestión de alojamiento. Más anfitriones, más establecimientos, mayores beneficios.</p>
        </div>

        <div class="plans-container">
            <div class="plan-card">
                <div class="plan-header">
                    <div class="plan-name">Plan Básico</div>
                    <div class="plan-price">
                        <span class="currency">€</span><?php echo number_format($precioMensualBasico, 0, ',', '.'); ?>
                        <div class="plan-period">/mes</div>
                    </div>
                    <div class="plan-annual">
                        💰 €<?php echo number_format($precioAnualBasico, 0, ',', '.'); ?>/año (ahorra €<?php echo number_format($ahorroBasico, 0, ',', '.'); ?>)
                    </div>
                </div>

                <div class="commission-badge">
                    Hasta 10 anfitriones
                </div>

                <ul class="plan-features">
                    <li><i class="fas fa-check"></i> <span class="highlight">Hasta 10 anfitriones</span></li>
                    <li><i class="fas fa-check"></i> <span class="highlight">Hasta 25 establecimientos</span></li>
                    <li><i class="fas fa-check"></i> Perfecto para empezar</li>
                </ul>

                <button id="btnBasic" class="btn btn-plan btn-basic" onclick="location.href='../contactanos.php'">
                    <i class="fas fa-rocket"></i> Comenzar ahora
                </button>
            </div>

            <div class="plan-card popular">
                <div class="plan-header">
                    <div class="plan-name">Plan Pro</div>
                    <div class="plan-price">
                        <span class="currency">€</span><?php echo number_format($precioMensualPro, 0, ',', '.'); ?>
                        <div class="plan-period">/mes</div>
                    </div>
                    <div class="plan-annual">
                        💰 €<?php echo number_format($precioAnualPro, 0, ',', '.'); ?>/año (ahorra €<?php echo number_format($ahorroPro, 0, ',', '.'); ?>)
                    </div>
                </div>

                <div class="commission-badge">
                    Hasta 20 anfitriones
                </div>

                <ul class="plan-features">
                    <li><i class="fas fa-check"></i> <span class="highlight">Hasta 20 anfitriones</span></li>
                    <li><i class="fas fa-check"></i> <span class="highlight">Hasta 50 establecimientos</span></li>
                    <li><i class="fas fa-check"></i> Es un buen equilibrio para gestoras que tienen más que ofrecer</li>
                </ul>

                <form action="mejoraProGestor.php" method="get">
                    <button id="btnPro" type="submit" class="btn btn-plan btn-pro">
                        <i class="fas fa-star"></i> Elegir Pro
                    </button>
                </form>
            </div>

            <div class="plan-card">
                <div class="plan-header">
                    <div class="plan-name">Plan Premium</div>
                    <div class="plan-price">
                        <span class="currency">€</span><?php echo number_format($precioMensualPremium, 0, ',', '.'); ?>
                        <div class="plan-period">/mes</div>
                    </div>
                    <div class="plan-annual">
                        💰 €<?php echo number_format($precioAnualPremium, 0, ',', '.'); ?>/año (ahorra €<?php echo number_format($ahorroPremium, 0, ',', '.'); ?>)
                    </div>
                </div>

                <div class="commission-badge">
                    Anfitriones ilimitados
                </div>

                <ul class="plan-features">
                    <li><i class="fas fa-check"></i> <span class="highlight">Anfitriones ilimitados</span></li>
                    <li><i class="fas fa-check"></i> <span class="highlight">+50 establecimientos</span></li>
                    <li><i class="fas fa-check"></i> Ideal para grandes gestoras</li>
                </ul>
                <form action="mejoraPremiumGestor.php" method="get">
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
                            <td class="text-center">€<?php echo number_format($precioMensualBasico, 0, ',', '.'); ?></td>
                            <td class="text-center">€<?php echo number_format($precioMensualPro, 0, ',', '.'); ?></td>
                            <td class="text-center">€<?php echo number_format($precioMensualPremium, 0, ',', '.'); ?></td>
                        </tr>
                        <tr>
                            <td><strong>Anfitriones incluidos</strong></td>
                            <td class="text-center">Hasta 10</td>
                            <td class="text-center">Hasta 20</td>
                            <td class="text-center">Ilimitados</td>
                        </tr>
                        <tr>
                            <td><strong>Establecimientos</strong></td>
                            <td class="text-center">Hasta 25</td>
                            <td class="text-center">Hasta 50</td>
                            <td class="text-center">+50</td>
                        </tr>
                        <tr>
                            <td><strong>Ideal para</strong></td>
                            <td class="text-center">Empezar</td>
                            <td class="text-center">Gestoras medianas</td>
                            <td class="text-center">Grandes gestoras</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>

  <script>
        var currentPlan = "<?php echo !empty($plan) ? $plan : 'Basico'; ?>";
        console.log("Plan actual:", currentPlan);

        document.addEventListener('DOMContentLoaded', function() {
            
            // 1. Configuramos nuestra jerarquía de niveles
            const planes = {
                'Basico': {
                    level: 1,
                    btn: document.getElementById("btnBasic"),
                    name: 'Básico',
                    downgradeParam: 'BajarPlanGestorBasico'
                },
                'Pro': {
                    level: 2,
                    btn: document.getElementById("btnPro"),
                    name: 'Pro',
                    downgradeParam: 'BajarPlanGestorPro'
                },
                'Premium': {
                    level: 3,
                    btn: document.getElementById("btnPremium"),
                    name: 'Premium',
                    downgradeParam: 'BajarPlanGestorPremium'
                }
            };

            if (!planes[currentPlan]) {
                currentPlan = 'Basico';
            }

            const nivelActual = planes[currentPlan].level;

            // 2. Evaluamos los botones basándonos en el nivel
            for (const [nombrePlan, datosPlan] of Object.entries(planes)) {
                let boton = datosPlan.btn;

                if (!boton) continue; 

                if (datosPlan.level < nivelActual) {
                    // PLAN INFERIOR (Downgrade) -> Botón de contacto
                    boton.disabled = false; 
                    boton.innerHTML = '<i class="fas fa-arrow-down"></i> Bajar a ' + datosPlan.name;
                    boton.classList.remove("disabled");
                    
                    // CREAMOS EL MENSAJE DE AVISO DE SOPORTE ---
                    let aviso = document.createElement("div");
                    aviso.className = "text-center mt-2";
                    aviso.innerHTML = "<small style='color: #dc3545; font-weight: 700;'><i class='fas fa-headset'></i> Contactarás con soporte para gestionar esta bajada.</small>";
                    boton.parentNode.appendChild(aviso);
                    // -----------------------------------------------------

                    boton.onclick = function(e) {
                        e.preventDefault();
                        window.location.href = "../contactanos.php?asunto=" + datosPlan.downgradeParam;
                    };

                } else if (datosPlan.level === nivelActual) {
                    // PLAN ACTUAL -> Botón gris bloqueado
                    boton.disabled = true;
                    boton.innerHTML = '<i class="fas fa-check"></i> Tu plan actual';
                    boton.classList.add("disabled");
                    
                    boton.onclick = function(e) {
                        e.preventDefault(); 
                    };
                }
            }

            // Efectos Hover de las tarjetas
            document.querySelectorAll('.plan-card').forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = this.classList.contains('popular') ? 'scale(1.08) translateY(-10px)' : 'translateY(-10px)';
                });

                card.addEventListener('mouseleave', function() {
                    this.style.transform = this.classList.contains('popular') ? 'scale(1.05)' : 'none';
                });
            });
        });
    </script>
</body>
</html>