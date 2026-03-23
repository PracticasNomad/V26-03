<?php
session_start();

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
$distribucionNombres = ['Nómadas', 'Anfitriones', 'Gestores'];
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
        }

        body {
            font-family: 'Nunito', sans-serif;
            background-color: #f8f9fa;
            padding-bottom: 15%;
            /* Espacio para el footer */
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
        .footer {
            color: black;
            background-color: white;
            width: 100%;
            -webkit-user-select: none;
            user-select: none;
            bottom: 0;
            font-size: 15px;
            background: #E3E1E1;
            text-align: center;
            position: fixed;
            z-index: 1000;
        }

        .footer-container {
            background-color: white;
            box-shadow: 0px -2px 10px rgba(0, 0, 0, 0.1);
            padding-top: 1px !important;
            padding-bottom: 1px !important;
            height: auto;
        }

        .footer-item {
            padding: 8px 0;
            text-decoration: none;
            color: black;
            font-size: 0.8rem;
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

        a,
        a:visited,
        a:active {
            text-decoration: none;
        }
    </style>
</head>

<body>

    <header>
        <div class="container-fluid info text-center">
            <div class="row">
                <div class="col color-white h2 fw-bold pt-3 pb-2">
                    Estadísticas Plataforma
                </div>
            </div>
        </div>
    </header>

    <div class="container mt-4">
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

    <div class="container-fluid footer mt-5 p-3">
        <div class="row text-center fixed-bottom bg-blanco pt-1 px-2 footer-container">
            <a href="dashboard.php" class="col-2 text-center footer-item">
                <div class="row">
                    <div class="col-12 icon-container" style="color:var(--primary-color);"><i
                            class="h3 fas fa-chart-line p-1 m-0"></i>
                        <div>Panel</div>
                    </div>
                </div>
            </a>
            <a href="verGestores.php" class="col-2 text-center footer-item">
                <div class="row">
                    <div class="col-12 icon-container"><i class="h3 fas fa-user-tie p-1 m-0"></i>
                        <div>Gestores</div>
                    </div>
                </div>
            </a>
            <a href="verAnfitriones.php" class="col-2 text-center footer-item">
                <div class="row">
                    <div class="col-12 icon-container"><i class="h3 fas fa-users p-1 m-0"></i>
                        <div>Anfitriones</div>
                    </div>
                </div>
            </a>
            <a href="verEstablecimientos.php" class="col-2 text-center footer-item">
                <div class="row">
                    <div class="col-12 icon-container"><i class="h3 fas fa-building p-1 m-0"></i>
                        <div>Establecimientos</div>
                    </div>
                </div>
            </a>
            <a href="verValidar.php" class="col-2 text-center footer-item">
                <div class="row">
                    <div class="col-12 icon-container"><i class="h3 fas fa-check-circle p-1 m-0"></i>
                        <div>Validar</div>
                    </div>
                </div>
            </a>
            <a href="tuPerfil.php" class="col-2 text-center footer-item">
                <div class="row">
                    <div class="col-12 icon-container"><i class="h3 fas fa-user-cog p-1 m-0"></i>
                        <div>Perfil</div>
                    </div>
                </div>
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