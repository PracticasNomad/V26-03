<?php
session_start();

// carga variables de entorno
require '../vendor/autoload.php';
use Dotenv\Dotenv;
$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

// obtener id de establecimiento
$id = isset($_GET['id']) ? intval($_GET['id']) : null;
if (!$id) {
    header('Location: verValidar.php');
    exit;
}

// traer datos desde la API
$establecimiento = null;
$url = 'http://' . $_ENV['SERVER_IP'] . ':' . $_ENV['DATABASE_PORT']
    . '/rest/v1/establecimiento?id=eq.' . $id;
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
    $data = json_decode($response, true);
    if (is_array($data) && count($data) > 0) {
        $establecimiento = $data[0];
    }
}

if (!$establecimiento) {
    header('Location: verValidar.php');
    exit;
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
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src='https://api.mapbox.com/mapbox-gl-js/v2.14.1/mapbox-gl.js'></script>
    <link href='https://api.mapbox.com/mapbox-gl-js/v2.14.1/mapbox-gl.css' rel='stylesheet'>
    <title>Validar - <?php echo htmlspecialchars($establecimiento['nombre']); ?></title>
    
    <style>
        body { font-family: 'Nunito', sans-serif; background-color: #f8f9fa; padding-bottom: 15%; }
        .color-white { color: #333; } /* Ajuste para el header si el fondo es claro */
        
        .establecimiento-card { background-color: white; border-radius: 15px; box-shadow: 0 .5rem 1rem rgba(0,0,0,.15); margin-bottom: 2rem; overflow: hidden; transition: all 0.3s; }
        .card-header { position: relative; height: 250px; background-size: cover; background-position: center; display: flex; align-items: flex-end; }
        .card-header-overlay { position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: linear-gradient(to bottom, rgba(0,0,0,0.1), rgba(0,0,0,0.8)); }
        .card-title { color: white; padding: 20px; font-weight: 700; font-size: 1.8rem; position: relative; width: 100%; z-index: 1; display: flex; justify-content: space-between; align-items: center; }
        
        .card-body { padding: 20px; }
        .info-row { display: flex; align-items: center; margin-bottom: 15px; gap: 15px; font-size: 1.1rem;}
        .info-icon { color: #28a745; width: 25px; text-align: center; font-size: 1.2rem; }
        
        .btn-actions { display: flex; gap: 10px; margin-top: 25px; flex-wrap: wrap; }
        .btn-action { flex: 1; border-radius: 10px; padding: 0.8rem 1rem; font-weight: 600; display: flex; align-items: center; justify-content: center; gap: 8px; transition: all 0.3s; color: white !important; }
        
        /* Colores específicos para validación */
        .btn-aprobar { background-color: #28a745; border: none; }
        .btn-aprobar:hover { background-color: #218838; transform: translateY(-2px); }
        .btn-rechazar { background-color: #dc3545; border: none; }
        .btn-rechazar:hover { background-color: #c82333; transform: translateY(-2px); }
        .btn-volver { background-color: #6c757d; border: none; }
        .btn-volver:hover { background-color: #5a6268; }

        #establecimiento-main { width: 100%; max-width: 800px; margin: 0 auto; }
        .map-container { height: 300px; border-radius: 10px; overflow: hidden; margin: 20px 0; border: 1px solid #ddd; }
        
        /* ESTILOS DEL FOOTER COPIADOS DE TU CÓDIGO */
        .footer { color: black; background-color: white; width: 100%; user-select: none; bottom: 0; font-size: 15px; background: #E3E1E1; text-align: center; position: fixed; z-index: 1000; }
        .footer-container { background-color: white; box-shadow: 0px -2px 10px rgba(0, 0, 0, 0.1); padding-top: 1px !important; padding-bottom: 1px !important; height: auto; }
        .footer-item { padding: 8px 0; }
        .icon-container { transition: transform 0.3s ease; padding: 5px 0; }
        .footer-item:hover .icon-container { transform: translateY(-7px); }
        a, a:visited, a:active { color: inherit; text-decoration: none; }
        
        /* Active state para el menú "Validar" */
        #lbl_val .icon-container { color: #007bff; }
        #lbl_val { color: #00B7CF !important; }
    </style>
</head>
<body>
    
    <header>
        <div class="container-fluid info text-center mt-3">
            <div class="row">
                <div class="col color-white h2 fw-bold pt-3 pb-2">
                    Detalle de Validación
                </div>
            </div>
        </div>
    </header>

    <div class="container-fluid pb-5">
        <div id="establecimiento-main">
            <div class="establecimiento-card">
                
                <div class="card-header" style="background-image: url('<?php echo isset($establecimiento['image_url']) ? 'http://' . $establecimiento['image_url'] : '../img/default.jpg'; ?>');">
                    <div class="card-header-overlay"></div>
                    <div class="card-title">
                        <div><?php echo htmlspecialchars($establecimiento['nombre']); ?></div>
                        <span class="badge bg-warning text-dark fs-6"><?php echo htmlspecialchars($establecimiento['estado'] ?? 'Pendiente'); ?></span>
                    </div>
                </div>
                
                <div class="card-body">
                    <div class="info-row">
                        <div class="info-icon"><i class="fas fa-align-left"></i></div>
                        <div><strong>Descripción:</strong> <br><?php echo nl2br(htmlspecialchars($establecimiento['descripcion'] ?? 'No especificada')); ?></div>
                    </div>

                    <div class="info-row">
                        <div class="info-icon"><i class="fas fa-map-marker-alt"></i></div>
                        <div><strong>Dirección:</strong> <br><?php echo htmlspecialchars($establecimiento['direccion'] ?? 'No especificada'); ?></div>
                    </div>
                    
                    <div class="info-row">
                        <div class="info-icon"><i class="fas fa-city"></i></div>
                        <div><strong>Localidad:</strong> <?php echo htmlspecialchars($establecimiento['localidad'] ?? ''); ?> (<?php echo htmlspecialchars($establecimiento['codigo_postal'] ?? ''); ?>)</div>
                    </div>

                    <div class="info-row mt-4">
                        <div class="info-icon"><i class="fas fa-map"></i></div>
                        <div><strong>Ubicación en el mapa:</strong></div>
                    </div>
                    <div class="map-container" id="map-validacion">
                        <div class="d-flex justify-content-center align-items-center h-100 bg-light text-muted">
                            Mapa de Mapbox (Requiere JS)
                        </div>
                    </div>

                    <div class="btn-actions border-top pt-4">
                        <a href="verValidar.php" class="btn btn-action btn-volver">
                            <i class="fas fa-arrow-left"></i> Volver
                        </a>
                        
                        <a href="procesar_validacion.php?accion=rechazar&id=<?php echo $establecimiento['id']; ?>" class="btn btn-action btn-rechazar" onclick="return confirm('¿Seguro que quieres rechazar este espacio?');">
                            <i class="fas fa-times"></i> Rechazar
                        </a>
                        
                        <a href="procesar_validacion.php?accion=aprobar&id=<?php echo $establecimiento['id']; ?>" class="btn btn-action btn-aprobar" onclick="return confirm('¿Aprobar y publicar este espacio?');">
                            <i class="fas fa-check"></i> Aprobar
                        </a>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <div class="container-fluid footer mt-5 p-3">
        <div class="row text-center fixed-bottom bg-blanco pt-1 px-2 footer-container">
            <label for="anf" id="lbl_anf" class="col-2 text-center footer-item">
                <div class="row">
                    <a href="Anfitriones.php">
                        <div class="col-12 icon-container">
                            <i class="h2 fas fa-users p-1 m-0"></i>
                            <div>Anfitriones</div>
                        </div>
                    </a>
                </div>
            </label>

            <label for="val" id="lbl_val" class="col-2 text-center footer-item">
                <div class="row">
                    <a href="verValidar.php">
                        <div class="col-12 icon-container">
                            <i class="h2 fas fa-check-circle p-1 m-0"></i>
                            <div>Validar</div>
                        </div>
                    </a>
                </div>
            </label>

            <label for="res" id="lbl_res" class="col-2 text-center footer-item">
                <div class="row">
                    <a href="verReservas.php">
                        <div class="col-12 icon-container">
                            <i class="h2 fas fa-book-open p-1 m-0"></i>
                            <div>Reservas</div>
                        </div>
                    </a>
                </div>
            </label>
            <label for="his" id="lbl_his" class="col-2 text-center footer-item">
                <div class="row">
                    <a href="verEstablecimientos.php">
                        <div class="col-12 icon-container">
                            <i class="h2 fas fa-building p-1 m-0"></i>
                            <div>Establecimientos</div>
                        </div>
                    </a>
                </div>
            </label>
            <label for="esp" id="lbl_esp" class="col-2 text-center footer-item">
                <div class="row">
                    <a href="verEspacios.php">
                        <div class="col-12 icon-container">
                            <i class="h2 fas fa-chair p-1 m-0"></i>
                            <div>Espacios</div>
                        </div>
                    </a>
                </div>
            </label>
            <label for="per" id="lbl_per" class="col-2 text-center footer-item">
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