<?php
require_once 'verificar_sesion_admin.php';

// Verificación de sesión igual que en el resto del admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['token'])) {
    header('Location: inicio_sesion_admin.php');
    exit();
}

require '../vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

$apiBase = 'http://' . $_ENV['SERVER_IP'] . ':' . $_ENV['DATABASE_PORT'] . '/rest/v1';

// Función para obtener la cantidad total de registros desde Supabase
function getCountFromApi($endpoint)
{
    global $apiBase;

    $url = $apiBase . '/' . $endpoint . '?select=id';
    $ch = curl_init($url);

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $_ENV['SERVICE_APIKEY'],
            'apikey: ' . $_ENV['SERVICE_APIKEY']
        ],
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200) {
        $data = json_decode($response, true);
        return is_array($data) ? count($data) : 0;
    }
    return 0;
}

// Extraemos los datos reales llamando a tu API
$totalNomadas = getCountFromApi('user');
$totalAnfitriones = getCountFromApi('host');
$totalGestores = getCountFromApi('gestor');
$totalEstablecimientos = getCountFromApi('establecimiento');
$totalReservas = getCountFromApi('reserva');

// Distribución para el gráfico circular
$distribucionNombres = ['Nómadas', 'Anfitriones', 'Gestoras'];
$distribucionCantidades = [$totalNomadas, $totalAnfitriones, $totalGestores];

// Datos para el gráfico de barras (simulados por ahora para evitar saturar la BD)
$mesesGrafico = ['Oct', 'Nov', 'Dic', 'Ene', 'Feb', 'Mar'];
$cantidadesGrafico = [12, 18, 25, 30, 45, $totalReservas];
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | Nomadapp Admin</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://kit.fontawesome.com/b8814a2854.js" crossorigin="anonymous"></script>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;600;700&display=swap" rel="stylesheet">

    <link rel="icon" href="../favicon-color.png">
    <link rel="icon" href="../favicon-negro.png" media="(prefers-color-scheme: light)">
    <link rel="icon" href="../favicon-color.png" media="(prefers-color-scheme: dark)">

    <style>
        :root {
            --primary-color: #dc3545;
            /* Rojo admin */
            --bg: #f4f7fb;
            --ink: #1f2933;
            --accent-dark: #8c1c13;
            --accent-mid: #c44536;

        }

        body {
            font-family: 'Nunito', sans-serif;
            background: #eef2f5;
            color: var(--ink);
            padding-bottom: 120px;
            /* Espacio para el footer */
        }

        .page-shell {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 15px;
            box-sizing: border-box;
        }

        .page-hero {
            width: 100%;
            margin: 1.2rem 0 0.5rem;
            padding: 0;
            box-sizing: border-box;
        }

        .contenedorDashboard {
            width: 100%;
            box-sizing: border-box;
            margin-top: 1rem;
        }

        .page-hero-inner {
            border-radius: 20px;
            background: linear-gradient(135deg, var(--accent-dark) 0%, var(--accent-mid) 52%, #df786c 100%);
            color: #ffffff;
            padding: 1.1rem 1.2rem;
            box-shadow: 0 18px 40px rgba(140, 28, 19, 0.24);
            border: 1px solid rgba(255, 255, 255, 0.18);
        }

        .page-hero-title {
            font-size: 1.35rem;
            font-weight: 800;
            letter-spacing: 0.2px;
        }

        .hero-title-row {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            transition: transform 0.2s;
        }

        .card:hover {
            transform: translateY(-5px);
        }

        .icon-box {
            font-size: 3.5rem;
            opacity: 0.2;
            position: absolute;
            right: 20px;
            bottom: 10px;
        }

        /* ESTILOS DEL FOOTER ADMIN EXACTOS */

        a,
        a:visited,
        a:active {
            text-decoration: none;
        }
    </style>
</head>

<body>
    <div class="page-shell">
        <section class="page-hero">
            <div class="page-hero-inner">
                <div class="hero-title-row">
                    <div class="page-hero-title"><i class="fas fa-chart-line me-2"></i>Estadísticas Plataforma</div>
                </div>
            </div>
        </section>


        <div class="contenedorDashboard mt-4">
            <div class="row g-4 mb-4">
                <div class="col-xl-3 col-md-6">
                    <div class="card bg-primary text-white p-3 h-100 position-relative">
                        <div class="card-body p-2">
                            <h6 class="text-uppercase fw-bold opacity-75 mb-1">Total Nómadas</h6>
                            <h1 class="display-4 fw-bold mb-0"><?php echo $totalNomadas; ?></h1>
                            <i class="fas fa-users icon-box"></i>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6">
                    <div class="card bg-success text-white p-3 h-100 position-relative">
                        <div class="card-body p-2">
                            <h6 class="text-uppercase fw-bold opacity-75 mb-1">Proveedores Activos</h6>
                            <h1 class="display-4 fw-bold mb-0"><?php echo ($totalAnfitriones + $totalGestores); ?></h1>
                            <small><?php echo $totalAnfitriones; ?> Anfitriones | <?php echo $totalGestores; ?>
                                Gestores</small>
                            <i class="fas fa-user-tie icon-box"></i>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6">
                    <div class="card bg-warning text-dark p-3 h-100 position-relative">
                        <div class="card-body p-2">
                            <h6 class="text-uppercase fw-bold opacity-75 mb-1">Establecimientos</h6>
                            <h1 class="display-4 fw-bold mb-0"><?php echo $totalEstablecimientos; ?></h1>
                            <i class="fas fa-store icon-box text-white"></i>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6">
                    <div class="card bg-danger text-white p-3 h-100 position-relative">
                        <div class="card-body p-2">
                            <h6 class="text-uppercase fw-bold opacity-75 mb-1">Reservas Emitidas</h6>
                            <h1 class="display-4 fw-bold mb-0"><?php echo $totalReservas; ?></h1>
                            <i class="fas fa-calendar-check icon-box"></i>
                        </div>
                    </div>
                </div>
            </div>

<<<<<<< HEAD
            <div class="col-xl-3 col-md-6">
                <div class="card bg-success text-white p-3 h-100 position-relative">
                    <div class="card-body p-2">
                        <h6 class="text-uppercase fw-bold opacity-75 mb-1">Proveedores Activos</h6>
                        <h1 class="display-4 fw-bold mb-0"><?php echo ($totalAnfitriones + $totalGestores); ?></h1>
                        <small><?php echo $totalAnfitriones; ?> Anfitriones | <?php echo $totalGestores; ?>
                            Gestoras</small>
                        <i class="fas fa-user-tie icon-box"></i>
=======
            <div class="row g-4">
                <div class="col-lg-8">
                    <div class="card p-4 h-100">
                        <h5 class="fw-bold mb-4 text-secondary">Evolución de Crecimiento (Reservas)</h5>
                        <div style="position: relative; height: 300px; width: 100%;">
                            <canvas id="graficoReservas"></canvas>
                        </div>
>>>>>>> f3b346ec444b9b85ee7ae26a906ddd4a919f6dd6
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card p-4 h-100">
                        <h5 class="fw-bold mb-4 text-secondary">Distribución de Usuarios</h5>
                        <div
                            style="position: relative; height: 300px; width: 100%; display: flex; justify-content: center;">
                            <canvas id="graficoUsuarios"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'footerAdmin.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
        // DIBUJAR GRÁFICO DE LÍNEAS
        const ctxReservas = document.getElementById('graficoReservas').getContext('2d');
        new Chart(ctxReservas, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($mesesGrafico); ?>,
                datasets: [{
                    label: 'Volumen',
                    data: <?php echo json_encode($cantidadesGrafico); ?>,
                    borderColor: '#dc3545',
                    backgroundColor: 'rgba(220, 53, 69, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#dc3545',
                    pointRadius: 5
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            borderDash: [5, 5]
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });

        // DIBUJAR GRÁFICO CIRCULAR
        const ctxUsuarios = document.getElementById('graficoUsuarios').getContext('2d');
        new Chart(ctxUsuarios, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode($distribucionNombres); ?>,
                datasets: [{
                    data: <?php echo json_encode($distribucionCantidades); ?>,
                    backgroundColor: ['#0d6efd', '#198754', '#ffc107'],
                    borderWidth: 0,
                    hoverOffset: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '75%',
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    </script>

</body>

</html>