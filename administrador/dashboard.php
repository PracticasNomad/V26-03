<?php
session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['token'])) {
    header('Location: inicio_sesion_admin.php');
    exit();
}

require '../vendor/autoload.php';
use Dotenv\Dotenv;
$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

$apiBase = 'http://' . $_ENV['SERVER_IP'] . ':' . $_ENV['DATABASE_PORT'] . '/rest/v1';

function getApiData($endpoint)
{
    global $apiBase;
    $url = $apiBase . '/' . $endpoint;
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

    if ($httpCode >= 200 && $httpCode < 300) {
        return json_decode($response, true);
    }
    return [];
}

$nomadas = getApiData('user?select=id,created_at');
$totalNomadas = is_array($nomadas) ? count($nomadas) : 0;
$nomadasNuevos = intval($totalNomadas * 0.15);

$anfitriones = getApiData('host?select=id,plan');
$totalAnfitriones = is_array($anfitriones) ? count($anfitriones) : 0;
$subsAnfitriones = is_array($anfitriones) ? count(array_filter($anfitriones, fn($h) => strtolower($h['plan'] ?? '') === 'pro' || strtolower($h['plan'] ?? '') === 'premium')) : 0;

$gestoras = getApiData('gestor?select=id,plan,codigo_postal');
$totalGestoras = is_array($gestoras) ? count($gestoras) : 0;
$subsGestoras = is_array($gestoras) ? count(array_filter($gestoras, fn($g) => strtolower($g['plan'] ?? '') === 'pro' || strtolower($g['plan'] ?? '') === 'premium')) : 0;


$reservas = getApiData('reserva?select=id,precio_total');
$totalReservas = is_array($reservas) ? count($reservas) : 0;

$volumenReservas = 0;
if (is_array($reservas)) {
    foreach ($reservas as $res) {
        $volumenReservas += floatval($res['precio_total'] ?? 0);
    }
}

$gastoPromedio = $totalReservas > 0 ? ($volumenReservas / $totalReservas) : 0;

$ingresosSubsHost = $subsAnfitriones * 49.99;
$ingresosSubsGestoras = $subsGestoras * 1900;
$ingresosTotales = $ingresosSubsHost + $ingresosSubsGestoras + $volumenReservas;


$ciudadesTop = ['Madrid', 'Alicante', 'Valencia', 'Elche', 'Murcia'];
$ingresosCiudades = [4500, 3200, 1800, 1500, 900];

$topEstablecimientos = [
    ['nombre' => 'Coworking Centro', 'visitas' => 145, 'tiempo_medio' => '4h', 'ingresos' => 1250],
    ['nombre' => 'Cafetería Work&Coffee', 'visitas' => 89, 'tiempo_medio' => '1.5h', 'ingresos' => 680],
    ['nombre' => 'Sala Reuniones Premium', 'visitas' => 45, 'tiempo_medio' => '2h', 'ingresos' => 2100],
];
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BI Dashboard | Nomadapp Admin</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://kit.fontawesome.com/b8814a2854.js" crossorigin="anonymous"></script>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;600;700;800&display=swap" rel="stylesheet">

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        :root {
            --primary-color: #dc3545;
        }

        body {
            font-family: 'Nunito', sans-serif;
            background-color: #f4f7fb;
            padding-bottom: 120px;
        }

        .header-admin {
            background: linear-gradient(135deg, #1f2933 0%, #364152 100%);
            color: white;
            padding: 20px 0;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .kpi-card {
            background: white;
            border-radius: 16px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.04);
            border: 1px solid #e1e5eb;
            transition: transform 0.2s;
            height: 100%;
        }

        .kpi-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.08);
        }

        .kpi-value {
            font-size: 2rem;
            font-weight: 800;
            color: #1f2933;
            line-height: 1;
            margin: 10px 0;
        }

        .kpi-label {
            color: #6b7c93;
            font-size: 0.85rem;
            font-weight: 700;
            text-transform: uppercase;
        }

        .kpi-icon {
            width: 45px;
            height: 45px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
        }

        .icon-green {
            background: #e7f8ee;
            color: #146c43;
        }

        .icon-blue {
            background: #e6f2ff;
            color: #0d6efd;
        }

        .icon-red {
            background: #fce8e5;
            color: #dc3545;
        }

        .icon-purple {
            background: #f3e8ff;
            color: #6f42c1;
        }

        /* Estilos Pestañas */
        .nav-pills .nav-link {
            border-radius: 50px;
            color: #6b7c93;
            font-weight: 700;
            padding: 10px 20px;
            margin-right: 5px;
            border: 1px solid transparent;
            transition: all 0.3s;
        }

        .nav-pills .nav-link.active {
            background-color: var(--primary-color);
            color: white;
            box-shadow: 0 4px 10px rgba(220, 53, 69, 0.3);
        }

        .nav-pills .nav-link:hover:not(.active) {
            background-color: #e1e5eb;
        }

        .table-custom th {
            color: #6b7c93;
            font-size: 0.8rem;
            text-transform: uppercase;
            border-bottom: 2px solid #e1e5eb;
        }

        .table-custom td {
            vertical-align: middle;
            font-weight: 600;
            color: #1f2933;
        }

        /* FOOTER EXACTO */
        .footer {
            color: black;
            background-color: white;
            width: 100%;
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

    <header class="header-admin mb-4">
        <div class="container-fluid px-4">
            <div class="d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-3">
                    <img src="../img/logo.jpg" alt="Nomadapp" style="height: 45px; border-radius: 10px;">
                    <div>
                        <h4 class="mb-0 fw-bold">Dashboard TheNomadapp</h4>
                        <small class="text-white-50">Estadísticas </small>
                    </div>
                </div>
                <a href="cerrarSesion.php" class="btn btn-outline-light btn-sm rounded-pill px-3"><i
                        class="fas fa-sign-out-alt me-1"></i> Salir</a>
            </div>
        </div>
    </header>

    <div class="container-fluid px-4">

        <ul class="nav nav-pills mb-4 bg-white p-2 rounded-pill shadow-sm d-inline-flex" id="dashboardTabs"
            role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="finanzas-tab" data-bs-toggle="pill" data-bs-target="#finanzas"
                    type="button" role="tab"><i class="fas fa-chart-line me-2"></i>Global & Finanzas</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="guests-tab" data-bs-toggle="pill" data-bs-target="#guests" type="button"
                    role="tab"><i class="fas fa-laptop-house me-2"></i>Nómadas (Guests)</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="hosts-tab" data-bs-toggle="pill" data-bs-target="#hosts" type="button"
                    role="tab"><i class="fas fa-store me-2"></i>Anfitriones (Hosts)</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="gestoras-tab" data-bs-toggle="pill" data-bs-target="#gestoras"
                    type="button" role="tab"><i class="fas fa-user-tie me-2"></i>Gestoras</button>
            </li>
        </ul>

        <div class="tab-content" id="dashboardTabsContent">

            <div class="tab-pane fade show active" id="finanzas" role="tabpanel">
                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <div class="kpi-card border-bottom border-success border-4">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="kpi-label">Ingresos Totales (Mes)</div>
                                    <div class="kpi-value">€<?php echo number_format($ingresosTotales, 2); ?></div>
                                    <small class="text-success"><i class="fas fa-arrow-up"></i> +12% vs mes
                                        anterior</small>
                                </div>
                                <div class="kpi-icon icon-green"><i class="fas fa-euro-sign"></i></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="kpi-card">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="kpi-label">Total Suscripciones Activas</div>
                                    <div class="kpi-value"><?php echo ($subsAnfitriones + $subsGestoras); ?></div>
                                    <small class="text-muted"><?php echo $subsAnfitriones; ?> Hosts |
                                        <?php echo $subsGestoras; ?> Gestoras</small>
                                </div>
                                <div class="kpi-icon icon-blue"><i class="fas fa-gem"></i></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="kpi-card border-bottom border-purple border-4">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="kpi-label">Reservas Plataforma</div>
                                    <div class="kpi-value"><?php echo $totalReservas; ?></div>
                                    <small class="text-muted">Ingreso Real Reservas:
                                        €<?php echo number_format($volumenReservas, 2); ?></small>
                                </div>
                                <div class="kpi-icon icon-purple"><i class="fas fa-calendar-check"></i></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="kpi-card">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="kpi-label">Usuarios Activos</div>
                                    <div class="kpi-value">


                                        <?php echo ($totalNomadas + $totalAnfitriones + $totalGestoras); ?>
                                    </div>
                                    <small class="text-muted">En todo el ecosistema</small>
                                </div>
                                <div class="kpi-icon icon-red"><i class="fas fa-users"></i></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row g-4">
                    <div class="col-lg-8">
                        <div class="kpi-card">
                            <h5 class="fw-bold mb-4">Mapa de Calor: Ingresos por Zona <i
                                    class="fas fa-fire text-danger"></i></h5>
                            <canvas id="mapaCalorChart" height="100"></canvas>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="kpi-card">
                            <h5 class="fw-bold mb-4">Desglose de Ingresos</h5>
                            <canvas id="ingresosDoughnut" height="200"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="guests" role="tabpanel">
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <div class="kpi-card">
                            <div class="kpi-label">Total Nómadas Registrados</div>
                            <div class="kpi-value text-primary"><?php echo $totalNomadas; ?></div>
                            <div class="progress" style="height: 5px;">
                                <div class="progress-bar bg-primary" style="width: 100%"></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="kpi-card">
                            <div class="kpi-label">Nuevos Nómadas (Este mes)</div>
                            <div class="kpi-value text-success">+<?php echo $nomadasNuevos; ?></div>
                            <small class="text-muted">Crecimiento estimado</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="kpi-card border-bottom border-warning border-4">
                            <div class="kpi-label">Gasto Promedio por Nómada</div>
                            <div class="kpi-value text-warning">€<?php echo number_format($gastoPromedio, 2); ?></div>
                            <small class="text-muted">Ticket medio real de tu BD</small>
                        </div>
                    </div>
                </div>

                <div class="kpi-card">
                    <h5 class="fw-bold mb-4">Uso de Establecimientos por Nómadas</h5>
                    <div class="table-responsive">
                        <table class="table table-custom table-hover">
                            <thead>
                                <tr>
                                    <th>Establecimiento Más Utilizado</th>
                                    <th>Visitas Totales</th>
                                    <th>Tiempo Medio Estancia</th>
                                    <th>Ingresos</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($topEstablecimientos as $est): ?>
                                    <tr>
                                        <td><i class="fas fa-store text-muted me-2"></i> <?php echo $est['nombre']; ?></td>
                                        <td><span
                                                class="badge bg-light text-dark border"><?php echo $est['visitas']; ?></span>
                                        </td>
                                        <td><i class="far fa-clock text-warning"></i> <?php echo $est['tiempo_medio']; ?>
                                        </td>
                                        <td class="text-success fw-bold">€<?php echo number_format($est['ingresos'], 2); ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="hosts" role="tabpanel">
                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <div class="kpi-card">
                            <div class="kpi-label">Volumen de Anfitriones</div>
                            <div class="kpi-value"><?php echo $totalAnfitriones; ?></div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="kpi-card border-bottom border-primary border-4">
                            <div class="kpi-label text-primary">Suscripciones Activas</div>
                            <div class="kpi-value"><?php echo $subsAnfitriones; ?> <i
                                    class="fas fa-gem fs-4 ms-2 text-primary opacity-50"></i></div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="kpi-card border-bottom border-success border-4">
                            <div class="kpi-label text-success">Ingresos por Subs. Host</div>
                            <div class="kpi-value">€<?php echo number_format($ingresosSubsHost, 2); ?></div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="kpi-card">
                            <div class="kpi-label">Aportación Global</div>
                            <div class="kpi-value text-info">
                                <?php echo $ingresosTotales > 0 ? round(($ingresosSubsHost / $ingresosTotales) * 100) : 0; ?>%
                            </div>
                            <small class="text-muted">Del revenue de la plataforma</small>
                        </div>
                    </div>
                </div>
                <div class="kpi-card">
                    <h5 class="fw-bold mb-3">Qué nos dan los Anfitriones (Distribución de Espacios)</h5>
                    <canvas id="espaciosHostChart" height="60"></canvas>
                </div>
            </div>

            <div class="tab-pane fade" id="gestoras" role="tabpanel">
                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <div class="kpi-card bg-light">
                            <div class="kpi-label">Gestoras Registradas</div>
                            <div class="kpi-value"><?php echo $totalGestoras; ?></div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="kpi-card border-bottom border-primary border-4">
                            <div class="kpi-label text-primary">Suscripciones Activas (Pro/Premium)</div>
                            <div class="kpi-value"><?php echo $subsGestoras; ?></div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="kpi-card border-bottom border-success border-4">
                            <div class="kpi-label text-success">Ingresos Subs. Gestoras</div>
                            <div class="kpi-value">€<?php echo number_format($ingresosSubsGestoras, 2); ?></div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="kpi-card border-bottom border-warning border-4">
                            <div class="kpi-label text-warning">Hosts a su cargo</div>
                            <div class="kpi-value">124</div> <small class="text-muted">Promedio de rentabilidad:
                                Alta</small>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <div class="kpi-card">
                            <h5 class="fw-bold mb-3">Análisis de Rentabilidad de Gestoras</h5>
                            <p class="text-muted mb-4">Relación entre la suscripción que pagan y el volumen de reservas
                                que generan en su zona.</p>
                            <canvas id="rentabilidadGestorasChart" height="80"></canvas>
                        </div>
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
            <a href="verGestoras.php" class="col-2 text-center footer-item">
                <div class="row">
                    <div class="col-12 icon-container"><i class="h3 fas fa-user-tie p-1 m-0"></i>
                        <div>Gestoras</div>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        new Chart(document.getElementById('mapaCalorChart').getContext('2d'), {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($ciudadesTop); ?>,
                datasets: [{
                    label: 'Ingresos por Zona (€)',
                    data: <?php echo json_encode($ingresosCiudades); ?>,
                    backgroundColor: ['#dc3545', '#e4606d', '#eb8c95', '#f1b8bd', '#f8e4e6'],
                    borderRadius: 8
                }]
            },
            options: {
                indexAxis: 'y', responsive: true, plugins: { legend: { display: false } },
                scales: { x: { grid: { display: false } }, y: { grid: { display: false } } }
            }
        });

        new Chart(document.getElementById('ingresosDoughnut').getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: ['Suscripciones Hosts', 'Suscripciones Gestoras', 'Comisiones Nómadas'],
                datasets: [{
                    data: [<?php echo $ingresosSubsHost; ?>, <?php echo $ingresosSubsGestoras; ?>, <?php echo $volumenReservas; ?>],
                    backgroundColor: ['#0d6efd', '#198754', '#ffc107'],
                    borderWidth: 0, hoverOffset: 10
                }]
            },
            options: { responsive: true, cutout: '70%', plugins: { legend: { position: 'bottom' } } }
        });

        new Chart(document.getElementById('espaciosHostChart').getContext('2d'), {
            type: 'bar',
            data: {
                labels: ['Mesas Compartidas', 'Salas de Reuniones', 'Despachos Privados', 'Cabinas'],
                datasets: [{
                    label: 'Cantidad Ofrecida por Hosts',
                    data: [350, 120, 85, 40],
                    backgroundColor: '#0d6efd', borderRadius: 5
                }]
            },
            options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
        });

        new Chart(document.getElementById('rentabilidadGestorasChart').getContext('2d'), {
            type: 'line',
            data: {
                labels: ['Gestora Norte', 'Gestora Sur', 'Gestora Centro', 'Gestora Costa', 'Gestora Este'],
                datasets: [
                    {
                        label: 'Suscripción que pagan',
                        data: [1900, 1900, 2850, 1900, 0],
                        borderColor: '#dc3545', backgroundColor: '#dc3545', tension: 0.4
                    },
                    {
                        label: 'Volumen Reservas Generado',
                        data: [4500, 3200, 8100, 2100, 800],
                        borderColor: '#198754', backgroundColor: 'rgba(25, 135, 84, 0.1)', fill: true, tension: 0.4
                    }
                ]
            },
            options: { responsive: true }
        });
    </script>

</body>

</html>