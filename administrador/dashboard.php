<?php
session_start();

// Verificación de sesión
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
$totalNomadas = getCountFromApi('user'); // Corregido a 'user'
$totalAnfitriones = getCountFromApi('host');
$totalGestores = getCountFromApi('gestor');
$totalEstablecimientos = getCountFromApi('establecimiento');
$totalReservas = getCountFromApi('reserva');

// Distribución para el gráfico circular
$distribucionNombres = ['Nómadas', 'Anfitriones', 'Gestores'];
$distribucionCantidades = [$totalNomadas, $totalAnfitriones, $totalGestores];

// Datos para el gráfico de barras
$mesesGrafico = ['Oct', 'Nov', 'Dic', 'Ene', 'Feb', 'Mar'];
$cantidadesGrafico = [12, 18, 25, 30, 45, $totalReservas];
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | Nomadapp Admin</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary-color: #dc3545;
            /* Tu rojo de admin */
            --white: #ffffff;
            --light-bg: #f8f9fa;
        }

        body {
            background-color: var(--light-bg);
            font-family: 'Nunito', sans-serif;
            padding-bottom: 100px;
            /* Importante para que el contenido no quede tapado por el footer */
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

        .footer-container {
            background-color: var(--white);
            box-shadow: 0px -2px 10px rgba(0, 0, 0, 0.1);
            padding-top: 1px !important;
            padding-bottom: 1px !important;
            height: auto;
            z-index: 1000;
        }

        .footer-item {
            padding: 8px 0;
            text-decoration: none;
            color: black;
            font-size: 0.8rem;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .icon-container {
            transition: transform 0.3s ease, color 0.3s ease;
            padding: 5px 0;
            color: #000000;
        }

        .footer-item:hover .icon-container {
            transform: translateY(-7px);
            color: var(--primary-color);
        }
    </style>
</head>

<body>

    <header class="bg-white shadow-sm py-3 mb-4">
        <div class="container-fluid px-4">
            <div class="row align-items-center">
                <div class="col-8">
                    <img src="../img/logo.jpg" alt="Nomadapp" style="height: 40px; border-radius: 8px;">
                    <span class="ms-2 fw-bold text-dark h5 mb-0 align-middle">Panel Global</span>
                </div>
                <div class="col-4 text-end">
                    <a href="cerrarSesion.php" class="btn btn-danger btn-sm rounded-pill px-3">
                        <i class="fas fa-sign-out-alt me-1"></i> Salir
                    </a>
                </div>
            </div>
        </div>
    </header>

    <div class="container-fluid px-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="mb-0 text-dark fw-bold">Estadísticas Plataforma</h3>
        </div>

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

        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card p-4 h-100">
                    <h5 class="fw-bold mb-4 text-secondary">Evolución de Crecimiento (Reservas)</h5>
                    <div style="position: relative; height: 300px; width: 100%;">
                        <canvas id="graficoReservas"></canvas>
                    </div>
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

    <div class="fixed-bottom footer-container">
        <div class="container-fluid d-flex justify-content-around align-items-center px-1">
            <a href="dashboard.php" class="text-center footer-item">
                <div class="icon-container"><i class="fas fa-chart-pie fa-lg" style="color: var(--primary-color);"></i>
                </div>
                <span class="fw-bold" style="color: var(--primary-color);">Dashboard</span>
            </a>
            <a href="verAnfitriones.php" class="text-center footer-item">
                <div class="icon-container"><i class="fas fa-users fa-lg"></i></div>
                <span>Usuarios</span>
            </a>
            <a href="verEstablecimientos.php" class="text-center footer-item">
                <div class="icon-container"><i class="fas fa-building fa-lg"></i></div>
                <span>Establecim.</span>
            </a>
            <a href="verEspacios.php" class="text-center footer-item">
                <div class="icon-container"><i class="fas fa-door-open fa-lg"></i></div>
                <span>Espacios</span>
            </a>
            <a href="verValidar.php" class="text-center footer-item">
                <div class="icon-container"><i class="fas fa-check-circle fa-lg"></i></div>
                <span>Validar</span>
            </a>
            <a href="tuPerfil.php" class="text-center footer-item">
                <div class="icon-container"><i class="fas fa-user-shield fa-lg"></i></div>
                <span>Perfil</span>
            </a>
        </div>
    </div>

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
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, grid: { borderDash: [5, 5] } },
                    x: { grid: { display: false } }
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
                    legend: { position: 'bottom' }
                }
            }
        });
    </script>

</body>

</html>