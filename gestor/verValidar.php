<?php
session_start();

// este script carga la lista de establecimientos cuyo estado sea "pendiente"
require '../vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

$establecimientos = [];
$backgroundImages = [
    '../img/bg1.jpg',
    '../img/bg2.jpg',
    '../img/bg3.jpg',
    '../img/bg4.jpg',
];

// consulta a la API para obtener los establecimientos pendientes
$url = 'http://' . $_ENV['SERVER_IP'] . ':' . $_ENV['DATABASE_PORT']
    . '/rest/v1/establecimiento?estaValidado=eq.false';
$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'apikey: ' . $_ENV['DATABASE_APIKEY'],
        'Authorization: Bearer ' . ($_SESSION['token'] ?? ''),
    ],
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    $establecimientos = json_decode($response, true);
    if (!is_array($establecimientos)) {
        $establecimientos = [];
    }
}

function formatearDireccion($dir, $piso)
{
    $result = htmlspecialchars($dir);
    if (!empty($piso)) {
        $result .= ' Piso ' . htmlspecialchars($piso);
    }
    return $result;
}
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
    <link rel="icon" href="Nomadapp.ico" type="image/png">
    <title>Validaciones pendientes</title>
    <style>
        body {
            font-family: 'Nunito', sans-serif;
            background-color: #f8f9fa;
            padding-bottom: 50px;
        }

        .contenedor-principal {
            max-width: 1200px;
            margin: 1.5rem auto;
            padding: 0 20px;
        }

        .header-container {
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .btn-add {
            background-color: #28a745;
            border: none;
            font-weight: 600;
            padding: 0.6rem 1.2rem;
            border-radius: 25px;
            margin-bottom: 20px;
            transition: all 0.3s;
            display: flex;
            width: 100%;
            max-width: 600px;
            justify-content: center;
            align-items: center;
            font-size: 0.95rem;
        }

        .btn-add:hover {
            background-color: #218838;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .establecimiento-card {
            background-color: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            margin-bottom: 0;
            overflow: hidden;
            transition: all 0.3s ease;
            border: 1px solid #e9ecef;
        }

        .establecimiento-card:hover {
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.15);
            transform: translateY(-2px);
        }

        .card-header {
            position: relative;
            height: 140px;
            background-size: cover;
            background-position: center;
            display: flex;
            align-items: flex-end;
            background-color: #f8f9fa;
            background-image: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .card-header-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(to bottom, rgba(0, 0, 0, 0.1), rgba(0, 0, 0, 0.7));
        }

        .card-title {
            color: white;
            padding: 15px;
            font-weight: 600;
            font-size: 1.3rem;
            position: relative;
            width: 100%;
            z-index: 1;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .service-icons {
            display: flex;
            gap: 15px;
        }

        .service-icon {
            background-color: rgba(255, 255, 255, 0.9);
            color: #333;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.9rem;
            border: 1px solid rgba(0, 0, 0, 0.1);
        }

        .validation-badge {
            color: white !important;
            border: 2px solid rgba(255, 255, 255, 0.3);
            font-size: 1rem;
        }

        .validation-badge.bg-success {
            background-color: #28a745 !important;
        }

        .validation-badge.bg-warning {
            background-color: #ffc107 !important;
            color: #212529 !important;
        }

        .card-body {
            padding: 15px;
        }

        .info-row {
            display: flex;
            align-items: center;
            margin-bottom: 6px;
            gap: 8px;
        }

        .info-icon {
            color: #28a745;
            width: 18px;
            text-align: center;
            font-size: 0.9rem;
        }

        .btn-validar {
            background-color: #007bff;
            border: none;
            color: white;
            border-radius: 8px;
            padding: 0.4rem 0.8rem;
            font-weight: 500;
            font-size: 0.85rem;
            transition: all 0.2s ease;
        }

        .btn-validar:hover {
            background-color: #0069d9;
            transform: translateY(-1px);
        }

        .btn-validar:active {
            background-color: #0056b3;
        }

        .btn-validar:focus {
            outline: none;
            box-shadow: none;
        }

        .no-establecimientos {
            background-color: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            padding: 2rem;
            text-align: center;
            border: 1px solid #e9ecef;
        }

        /* Active state para el menú "Validar" */
        #lbl_val .icon-container {
            color: #007bff;
        }

        #lbl_val {
            color: #00B7CF !important;
        }

        /* ESTILOS DEL FOOTER */
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
            z-index: 1001;
        }

        .footer-item {
            padding: 8px 0;
            -webkit-tap-highlight-color: transparent;
        }

        .icon-container {
            transition: transform 0.3s ease;
            padding: 5px 0;
        }

        .footer-item:hover .icon-container {
            transform: translateY(-7px);
        }

        .footer-item:active .icon-container {
            transform: translateY(0);
        }

        .footer-item:focus .icon-container {
            transform: translateY(0);
        }

        a,
        a:visited,
        a:active {
            color: inherit;
            text-decoration: none;
        }
    </style>
    </style>
</head>

<body>
    <header>
        <div class="container-fluid info text-center">
            <div class="row">
                <div class="col color-white h2 fw-bold pt-3 pb-2">
                    Validaciones pendientes
                </div>
            </div>
        </div>
    </header>

    <div class="container-fluid pb-5">
        <div class="row">
            <?php if (empty($establecimientos)): ?>
                <div class="no-establecimientos">
                    <img src="../img/establecimiento.png" width="80" alt="Sin pendientes" class="mb-3">
                    <h3 class="fw-bold mb-3">No hay establecimientos pendientes de validación</h3>
                    <p class="text-muted">Todos los establecimientos han sido revisados. Los nuevos establecimientos aparecerán aquí cuando requieran validación.</p>
                </div>
            <?php else: ?>
                <?php foreach ($establecimientos as $index => $establecimiento):
                    $direccionFormateada = formatearDireccion(
                        $establecimiento['direccion'],
                        $establecimiento['piso']
                    );
                ?>
                    <div class="col-12">
                        <div class="establecimiento-card">
                            <div class="card-header" style="background-image: url('<?php echo isset($establecimiento['image_url']) ? 'http://' . $establecimiento['image_url'] : '../img/default.jpg'; ?>');">
                                <div class="card-header-overlay"></div>
                                <div class="card-title">
                                    <div><?php echo htmlspecialchars($establecimiento['nombre']); ?></div>
                                </div>
                            </div>
                            <div class="card-body">
                                <div><strong>Dirección:</strong> <?php echo $direccionFormateada; ?></div>
                                <div><strong>Localidad:</strong> <?php echo htmlspecialchars($establecimiento['localidad'] ?? ''); ?></div>
                                <div><strong>Estado:</strong> <span class="badge bg-warning text-dark">Pendiente de validación</span></div>
                                <div class="btn-actions mt-3">
                                    <a href="validar.php?id=<?php echo $establecimiento['id']; ?>" class="btn btn-validar">
                                        <i class="fas fa-check-circle"></i> Revisar y validar
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <div class="container-fluid footer mt-5 p-3">
        <div class="row text-center fixed-bottom bg-blanco pt-1 px-2 footer-container">
            <label id="lbl_anf" class="col-2 text-center footer-item">
                <div class="row">
                    <a href="Anfitriones.php">
                        <div class="col-12 icon-container">
                            <i class="h2 fas fa-users p-1 m-0"></i>
                            <div>Anfitriones</div>
                        </div>
                    </a>
                </div>
            </label>
            <label id="lbl_val" class="col-2 text-center footer-item">
                <div class="row">
                    <a href="verValidar.php">
                        <div class="col-12 icon-container">
                            <i class="h2 fas fa-check-circle p-1 m-0"></i>
                            <div>Validar</div>
                        </div>
                    </a>
                </div>
            </label>
            <label id="lbl_res" class="col-2 text-center footer-item">
                <div class="row">
                    <a href="verReservas.php">
                        <div class="col-12 icon-container">
                            <i class="h2 fas fa-book-open p-1 m-0"></i>
                            <div>Reservas</div>
                        </div>
                    </a>
                </div>
            </label>
            <label id="lbl_his" class="col-2 text-center footer-item">
                <div class="row">
                    <a href="verEstablecimientos.php">
                        <div class="col-12 icon-container">
                            <i class="h2 fas fa-building p-1 m-0"></i>
                            <div>Establecimientos</div>
                        </div>
                    </a>
                </div>
            </label>
            <label id="lbl_esp" class="col-2 text-center footer-item">
                <div class="row">
                    <a href="verEspacios.php">
                        <div class="col-12 icon-container">
                            <i class="h2 fas fa-chair p-1 m-0"></i>
                            <div>Espacios</div>
                        </div>
                    </a>
                </div>
            </label>
            <label id="lbl_per" class="col-2 text-center footer-item">
                <div class="row">
                    <a href="tuPerfil.php">
                        <div class="col-12 icon-container">
                            <i class="h2 fas fa-user-tie p-1 m-0"></i>
                            <div>Perfil</div>
                        </div>
                    </a>
                </div>
            </label>
        </div>
    </div>
</body>

</html>