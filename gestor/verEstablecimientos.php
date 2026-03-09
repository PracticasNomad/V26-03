<?php
session_start();

require '../vendor/autoload.php';
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

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
    <link rel="icon" href="Nomadapp.ico" type="image/png">
    <title>Mis Establecimientos</title>
    <style>
        body {
            font-family: 'Nunito', sans-serif;
            background-color: #f8f9fa;
            padding-bottom: 50px;
        }
        
        .contenedor-principal {
            max-width: 1400px;
            margin: 2rem auto;
            padding: 0 15px;
        }
        
        .header-container {
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        .btn-add {
            background-color: #28a745;
            border: none;
            font-weight: 600;
            padding: 0.75rem 1.5rem;
            border-radius: 50px;
            margin-bottom: 30px;
            transition: all 0.3s;
            display: flex;
            width: 100%;
            max-width: 650px;
            justify-content: center;
            align-items: center;
        }
        
        .btn-add:hover {
            background-color: #218838;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .establecimiento-card {
            background-color: white;
            border-radius: 15px;
            box-shadow: 0 .5rem 1rem rgba(0,0,0,.15);
            margin-bottom: 2rem;
            overflow: hidden;
            transition: all 0.3s;
        }
        
        .establecimiento-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 1rem 2rem rgba(0,0,0,.2);
        }
        
        .card-header {
            position: relative;
            height: 180px;
            background-size: cover;
            background-position: center;
            display: flex;
            align-items: flex-end;
        }
        
        .card-header-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(to bottom, rgba(0,0,0,0.1), rgba(0,0,0,0.7));
        }
        
        .card-title {
            color: white;
            padding: 20px;
            font-weight: 700;
            font-size: 1.5rem;
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
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
        }
        
        .card-body {
            padding: 20px;
        }
        
        .info-row {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
            gap: 10px;
        }
        
        .info-icon {
            color: #28a745;
            width: 20px;
            text-align: center;
        }
        
        .collapsed-content {
            display: none;
            padding-top: 15px;
            border-top: 1px solid #e9ecef;
            margin-top: 15px;
        }
        
        .btn-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
            flex-wrap: wrap;
        }
        
        .btn-action {
            flex: 1;
            border-radius: 10px;
            padding: 0.5rem 1rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
            transition: all 0.3s;
        }
        
        .btn-toggle {
            background-color: #f8f9fa;
            border: 1px solid #ced4da;
            color: #6c757d;
            width: 100%;
            margin-bottom: 15px;
            border-radius: 10px;
            padding: 8px;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
            transition: all 0.3s;
        }
        
        .btn-toggle:hover {
            background-color: #e9ecef;
        }
        
        .btn-spaces {
            background-color: #a4a4a4;
            border: none;
            color: black;
        }
        
        .btn-spaces:hover {
            background-color: #8f8f8f;
        }
        
        .btn-edit {
            background-color: #17a2b8;
            border: none;
        }
        
        .btn-edit:hover {
            background-color: #138496;
        }
        
        .btn-delete {
            background-color: #dc3545;
            border: none;
            color: black;
        }
        
        .btn-delete:hover {
            background-color: #c82333;
        }
        
        .map-container {
            height: 400px;
            border-radius: 10px;
            overflow: hidden;
            margin: 15px 0;
        }
        
        .no-establecimientos {
            background-color: white;
            border-radius: 15px;
            box-shadow: 0 .5rem 1rem rgba(0,0,0,.15);
            padding: 2rem;
            text-align: center;
        }
        
        .precio-tag {
            background-color: #28a745;
            color: white;
            border-radius: 50px;
            padding: 5px 10px;
            font-size: 0.9rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            margin-left: 10px;
        }
        
        .modal-confirm {
            color: #636363;
        }
        
        .modal-confirm .modal-content {
            padding: 20px;
            border-radius: 15px;
            border: none;
        }
        
        .modal-confirm .modal-header {
            border-bottom: none;   
            position: relative;
            text-align: center;
            margin: -20px -20px 0;
            border-top-left-radius: 15px;
            border-top-right-radius: 15px;
            padding: 35px;
        }
        
        .modal-confirm .modal-header.delete {
            background-color: #f7d7db;
        }
        
        .modal-confirm h4 {
            text-align: center;
            font-size: 26px;
            margin: 30px 0 -15px;
            color: #333;
        }
        
        .modal-confirm .form-control, .modal-confirm .btn {
            min-height: 40px;
            border-radius: 10px; 
        }
        
        .modal-confirm .close {
            position: absolute;
            top: 15px;
            right: 15px;
            font-size: 24px;
            font-weight: bold;
            color: #999;
            opacity: 1;
        }
        
        .modal-confirm .modal-footer {
            border: none;
            text-align: center;
            border-radius: 15px;
            padding: 10px 15px 25px;
            justify-content: center;
        }
        
        .modal-confirm .icon-box {
            color: #fff;
            position: absolute;
            margin: 0 auto;
            left: 0;
            right: 0;
            top: -70px;
            width: 95px;
            height: 95px;
            border-radius: 50%;
            background-color: #f15e5e;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9;
            box-shadow: 0px 2px 2px rgba(0, 0, 0, 0.1);
        }
        
        .modal-confirm .icon-box i {
            font-size: 58px;
        }
        
        .modal-confirm.modal-dialog {
            margin-top: 80px;
        }
        
        .trigger-btn {
            display: inline-block;
            margin: 100px auto;
        }
        
        .spinner-container {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 200px;
        }
        
        .spinner {
            width: 60px;
            height: 60px;
        }
        
        @media (max-width: 767px) {
            
            .service-icons {
                align-self: flex-end;
            }
            
            .btn-actions {
                flex-direction: column;
            }
            
            .btn-action {
                width: 100%;
            }
            
            .header-container {
                flex-direction: column;
                align-items: stretch;
                gap: 15px;
            }
            
            .header-container h1 {
                text-align: center;
            }
        }

        #establecimiento-main {
            width: 100%;
            max-width: 650px;
            margin: 0 auto;
        }

        .row{
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
        }

        body {
            padding-bottom: 15%;
        }

        .footer {
            color: black;
            background-color: white;
            width: 100%;
            -webkit-user-select: none;
            -ms-user-select: none;
            user-select: none;
            bottom: 0;
            font-size: 15px;
            background: #E3E1E1;
            text-align: center;
            position: fixed;
        }

        .footer input[type="radio"] {
            display: none;
        }

        label,
        .form-check input[type=checkbox] {
            position: static;
        }

        #res:checked~#lbl_res,
        #his:checked~#lbl_his,
        #esp:checked~#lbl_esp,
        #per:checked~#lbl_per {
            color: #00B7CF !important;
        }

        a,
        a:visited,
        a:active {
            color: black;
            text-decoration: none;
        }

        .fecha {
            border-radius: 0.5rem;
        }

        .espacio {
            border-radius: 1rem;
            background: #f3f3f3ff;
        }

        .hora {
            color: #00B7CF;
        }
        
        .spinner-border {
            color: #1976d2;
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
        }

        .icon-container {
            transition: transform 0.3s ease;
            padding: 5px 0;
        }

        .footer-item:hover .icon-container {
            transform: translateY(-7px);
        }
        
        .mensaje-limite {
    background-color: #fff3cd; /* Fondo amarillo claro */
    border: 1px solid #ffeeba; /* Borde amarillo más oscuro */
    color: #856404; /* Texto marrón oscuro para contraste */
    padding: 15px; /* Espaciado interno */
    margin: 20px auto; /* Margen superior e inferior, y centrado horizontal */
    border-radius: 8px; /* Bordes ligeramente redondeados */
    text-align: center; /* Texto centrado */
    max-width: 650px; /* Ancho máximo para el mensaje */
    font-size: 1rem; /* Tamaño de fuente */
    line-height: 1.5; /* Altura de línea */
}

.mensaje-limite a {
    color: #0056b3; /* Color azul para el enlace dentro del mensaje */
    font-weight: bold; /* Texto del enlace en negrita */
    text-decoration: underline; /* Subrayado del enlace */
}
.btn-add:disabled {
    background-color: #cccccc; /* Fondo gris claro */
    cursor: not-allowed; /* Cursor de "no permitido" */
    transform: none; /* Elimina la transformación al pasar el ratón */
    box-shadow: none; /* Elimina la sombra al pasar el ratón */
}

        #per:checked ~ #lbl_per .icon-container,
        #res:checked ~ #lbl_res .icon-container,
        #his:checked ~ #lbl_his .icon-container,
        #esp:checked ~ #lbl_esp .icon-container {
            color: #007bff;
        }
        /* New hover styles for "Establecimientos" and "Perfil" */
        #lbl_his:hover,
        #lbl_per:hover,
        #lbl_anf:hover,
        #lbl_val:hover,
        #lbl_res:hover,
        #lbl_esp:hover {
            color: #00B7CF !important; /* For the text */
        }

        #lbl_his:hover .icon-container,
        #lbl_per:hover .icon-container,
        #lbl_anf:hover .icon-container,
        #lbl_val:hover .icon-container,
        #lbl_res:hover .icon-container,
        #lbl_esp:hover .icon-container {
            color: #007bff; /* For the icon */
        }
    </style>
</head>
<body>
    <header>
        <div class="container-fluid info text-center">
            <div class="row">
                <div class="col color-white h2 fw-bold pt-3 pb-2">
                    Establecimientos
                </div>
            </div>
        </div>
    </header>
    <body>
<?php if (empty($establecimientos)): ?>
            <div class="no-establecimientos">
                <img src="../img/establecimiento.png" width="80" alt="Logo Establecimiento" class="mb-3">
                <h3 class="fw-bold mb-3">No hay establecimientos registrados en este codigo postal</h3>
                
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($establecimientos as $index => $establecimiento): 
                    $randomImage = $backgroundImages[$index % count($backgroundImages)];
                    $direccionFormateada = formatearDireccion(
                        $establecimiento['direccion'], 
                        $establecimiento['piso']
                    );
                ?>
                <div id="establecimiento-main">
                    <div class="establecimiento-card" id="establecimiento-<?php echo $establecimiento['id']; ?>">
                        <script>console.log("<?php echo $establecimiento['image_url']?>")</script>
                        <div class="card-header" style="background-image: url('<?php echo 'http://' . $establecimiento['image_url']?>');">
                            <div class="card-header-overlay"></div>
                            <div class="card-title">
                                <div><?php echo htmlspecialchars($establecimiento['nombre']); ?></div>
                                <div class="service-icons">
                                    <?php if ($establecimiento['has_wifi']): ?>
                                        <div class="service-icon" title="WiFi disponible">
                                            <i class="fas fa-wifi"></i>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($establecimiento['has_parking']): ?>
                                        <div class="service-icon" title="Parking disponible">
                                            <i class="fas fa-parking"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card-body">
                            <div class="info-row">
                                <div class="info-icon"><i class="fas fa-map-marker-alt"></i></div>
                                <div><?php echo htmlspecialchars($direccionFormateada); ?></div>
                            </div>
                            
                            <div class="info-row">
                                <div class="info-icon"><i class="fas fa-city"></i></div>
                                <div><?php echo htmlspecialchars($establecimiento['localidad']); ?></div>
                            </div>
                            
                            <button class="btn btn-toggle" onclick="toggleDetails('<?php echo $establecimiento['id']; ?>')">
                                <span id="toggle-text-<?php echo $establecimiento['id']; ?>">Ver más detalles</span>
                                <i class="fas fa-chevron-down" id="toggle-icon-<?php echo $establecimiento['id']; ?>"></i>
                            </button>
                            
                            <div class="collapsed-content" id="details-<?php echo $establecimiento['id']; ?>">
                                <div class="info-row">
                                    <div class="info-icon"><i class="fas fa-align-left"></i></div>
                                    <div><strong>Descripción:</strong> <?php echo htmlspecialchars($establecimiento['descripcion']); ?></div>
                                </div>
                                
                                <div class="info-row">
                                    <div class="info-icon"><i class="fas fa-map"></i></div>
                                    <div><strong>Provincia:</strong> <?php echo htmlspecialchars($establecimiento['provincia']); ?></div>
                                </div>
                                
                                <div class="info-row">
                                    <div class="info-icon"><i class="fas fa-map-pin"></i></div>
                                    <div><strong>Código Postal:</strong> <?php echo htmlspecialchars($establecimiento['codigo_postal']); ?></div>
                                </div>
                                
                                <?php if ($establecimiento['has_wifi']): ?>
                                <div class="info-row">
                                    <div class="info-icon"><i class="fas fa-wifi"></i></div>
                                    <div>
                                        <strong>WiFi disponible</strong>
                                        <span class="precio-tag">
                                            <i class="fas fa-euro-sign"></i> <?php echo number_format($establecimiento['wifi_price'], 2); ?>/hora
                                        </span>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <?php if ($establecimiento['has_parking']): ?>
                                <div class="info-row">
                                    <div class="info-icon"><i class="fas fa-parking"></i></div>
                                    <div>
                                        <strong>Parking disponible</strong>
                                        <span class="precio-tag">
                                            <i class="fas fa-euro-sign"></i> <?php echo number_format($establecimiento['parking_price'], 2); ?>/día
                                        </span>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($establecimiento['piso'])): ?>
                                <div class="info-row">
                                    <div class="info-icon"><i class="fas fa-building"></i></div>
                                    <div><strong>Piso:</strong> <?php echo htmlspecialchars($establecimiento['piso']); ?></div>
                                </div>
                                <?php endif; ?>
                                
                                <div class="map-container" id="map-<?php echo $establecimiento['id']; ?>"></div>
                            </div>
                            
                            <div class="btn-actions">
                                <a href="espacios.php?establecimiento_id=<?php echo $establecimiento['id']; ?>" class="btn btn-action btn-spaces">
                                    <i class="fas fa-door-open"></i> Gestionar Espacios
                                </a>
                                <a href="editarEstablecimiento.php?id=<?php echo $establecimiento['id']; ?>" class="btn btn-action btn-edit">
                                    <i class="fas fa-edit"></i> Editar
                                </a>
                                <button class="btn btn-action btn-delete" onclick="confirmarEliminacion('<?php echo $establecimiento['id']; ?>', '<?php echo htmlspecialchars($establecimiento['nombre']); ?>')">
                                    <i class="fas fa-trash-alt"></i> Eliminar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-confirm">
            <div class="modal-content">
                <div class="modal-header delete">
                    <div class="icon-box">
                        <i class="fas fa-trash"></i>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <h4 class="modal-title mb-4">¿Estás seguro?</h4>
                    <p>¿Realmente deseas eliminar el establecimiento "<span id="establecimiento-nombre"></span>"? Esta acción no se puede deshacer.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" id="btn-confirmar-eliminar" class="btn btn-danger">Sí, eliminar</button>
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
