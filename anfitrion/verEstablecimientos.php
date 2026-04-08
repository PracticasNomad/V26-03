<?php
require_once 'verificar_sesion_host.php';

require '../vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

// Control de creacion de establecimientos - Obtencion del plan
$url = "http://" . $_ENV['SERVER_IP'] . ":" . $_ENV['DATABASE_PORT'] . "/rest/v1/host?id=eq." . $_SESSION['user_id'];

$ch = curl_init($url);
curl_setopt_array($ch, array(
    CURLOPT_CUSTOMREQUEST => "GET",
    CURLOPT_HTTPHEADER => array(
        'Content-Type: application/json',
        'apikey: ' . $_ENV['DATABASE_APIKEY']
    ),
    CURLOPT_RETURNTRANSFER => true,
));

$resultado = curl_exec($ch);
$codigoRespuesta = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($codigoRespuesta === 200) {
    $datos = json_decode($resultado, true);
    if (count($datos) > 0) {
        $plan = $datos[0]['plan'];
    }
}

$url = "http://" . $_ENV['SERVER_IP'] . ":" . $_ENV['DATABASE_PORT'] . "/rest/v1/establecimiento?host_id=eq." . $_SESSION['user_id'];

$ch = curl_init($url);
curl_setopt_array($ch, array(
    CURLOPT_CUSTOMREQUEST => "GET",
    CURLOPT_HTTPHEADER => array(
        'Content-Type: application/json',
        'apikey: ' . $_ENV['DATABASE_APIKEY']
    ),
    CURLOPT_RETURNTRANSFER => true,
));

$response = curl_exec($ch);
curl_close($ch);

$establecimientosData = json_decode($response, true);
$num_establecimientos = count($establecimientosData);

$limites = [
    'Basico' => 1,
    'Pro' => 2,
    'Premium' => PHP_INT_MAX // ilimitado
];

$mostrarMensajeLimite = false;
if ($num_establecimientos >= $limites[$plan]) {
    $mostrarMensajeLimite = true;
}

$curl = curl_init();
$url = "http://" . $_ENV['SERVER_IP'] . ":" . $_ENV['DATABASE_PORT'] . "/rest/v1/establecimiento?host_id=eq." . $_SESSION['user_id'];

curl_setopt_array($curl, [
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        "Content-Type: application/json",
        "apikey: " . $_ENV['DATABASE_APIKEY'],
        "Authorization: Bearer " . $_SESSION['token'],
    ]
]);

$response = curl_exec($curl);
$establecimientos = json_decode($response, true);

if (!$establecimientos || curl_error($curl)) {
    $establecimientos = [];
}
curl_close($curl);

if (!empty($establecimientos)) {
    foreach ($establecimientos as &$establecimiento) {
        $curl_gallery = curl_init();
        $gallery_url = "http://" . $_ENV['SERVER_IP'] . ":" . $_ENV['DATABASE_PORT'] . "/rest/v1/gallery?establecimiento_id=eq." . $establecimiento['id'];

        curl_setopt_array($curl_gallery, [
            CURLOPT_URL => $gallery_url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                "Content-Type: application/json",
                "apikey: " . $_ENV['DATABASE_APIKEY'],
                "Authorization: Bearer " . $_SESSION['token'],
            ]
        ]);

        $gallery_response = curl_exec($curl_gallery);
        $gallery_data = json_decode($gallery_response, true);

        // Asignar la primera imagen a la portada y guardar el resto en 'gallery'
        if (!empty($gallery_data) && !curl_error($curl_gallery)) {
            $establecimiento['image_url'] = $gallery_data[0]['image_url'];
            $establecimiento['gallery'] = $gallery_data; 
        } else {
            $establecimiento['image_url'] = "../img/bricks0.jpg";
            $establecimiento['gallery'] = [];
        }
        curl_close($curl_gallery);
    }
    unset($establecimiento); // Romper la referencia
}

function formatearDireccion($direccion, $piso = "")
{
    if (!empty($piso)) {
        $direccion .= ", $piso";
    }
    return $direccion;
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
    <link href="style.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src='https://api.mapbox.com/mapbox-gl-js/v2.14.1/mapbox-gl.js'></script>
    <link href='https://api.mapbox.com/mapbox-gl-js/v2.14.1/mapbox-gl.css' rel='stylesheet'>
    <link rel="icon" href="../favicon-color.png">
    <title>Tus Establecimientos</title>
    
    <style>
        :root {
            --host-accent: #10bfeb;
            --host-accent-dark: #0a95b7;
        }

        body {
            font-family: 'Nunito', sans-serif;
            padding-bottom: 15%;
            background-color: #f4f6f9;
        }

        .page-shell {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 15px;
            box-sizing: border-box;
        }

        /* Toasts personalizados */
        .custom-toast { border-radius: 10px; font-family: 'Nunito', sans-serif; z-index: 10500; }

        /* Mensajes de límite */
        .mensaje-limite {
            background-color: #fff3cd;
            border: 1px solid #ffeeba;
            color: #856404;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            margin-bottom: 20px;
            font-weight: 600;
        }

        .mensaje-limite a {
            color: #0056b3;
            font-weight: bold;
            text-decoration: underline;
        }

        /* DISEÑO DE FILA-CARD EN UNA COLUMNA */
        .filaEstablecimiento {
            background-color: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            flex-wrap: wrap; 
            border: 1px solid #e9ecef;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.03);
            transition: all 0.3s ease;
        }

        .filaEstablecimiento:hover {
            border-color: var(--host-accent);
            transform: translateY(-3px);
            box-shadow: 0 8px 15px rgba(16, 191, 235, 0.1);
        }

        /* Contenedor compacto de imagen */
        .imagenCompacta {
            width: 120px;
            height: 120px;
            border-radius: 12px;
            overflow: hidden;
            margin-right: 25px;
            flex-shrink: 0;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .imagenCompacta img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        /* Información principal */
        .infoPrincipal {
            flex-grow: 1;
            min-width: 250px;
        }

        .nombreEstablecimiento {
            font-weight: 800;
            font-size: 1.3rem;
            color: #333;
            margin-bottom: 5px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .descripcionCorta {
            color: #6c757d;
            font-size: 0.95rem;
            margin-bottom: 8px;
            line-height: 1.4;
        }

        .direccionFiscal {
            font-size: 0.9rem;
            color: #333;
            font-weight: 600;
        }

        /* Acciones alineadas a la derecha */
        .accionesFila {
            display: flex;
            gap: 10px;
            margin-left: 20px;
        }

        .btn-accion {
            border-radius: 8px;
            padding: 8px 15px;
            font-weight: 700;
            font-size: 0.9rem;
            transition: 0.3s;
        }

        .btn-outline-secondary { border-color: #ced4da; color: #6c757d; }
        .btn-outline-secondary:hover { background-color: #f8f9fa; color: #333; }

        /* Contenido Desplegable (Mapa y detalles extras) */
        .collapsed-content {
            display: none;
            width: 100%;
            flex-basis: 100%;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 2px dashed #e9ecef;
        }

        .map-container-individual {
            height: 300px;
            border-radius: 10px;
            overflow: hidden;
            margin-top: 15px;
            border: 1px solid #e9ecef;
        }

        /* Estilo para la Galería de Fotos */
        .galeria-establecimiento {
            display: flex;
            gap: 12px;
            overflow-x: auto;
            padding-bottom: 10px;
            margin-top: 15px;
        }

        .galeria-item {
            width: 120px;
            height: 120px;
            border-radius: 10px;
            object-fit: cover;
            flex-shrink: 0;
            border: 1px solid #e9ecef;
            box-shadow: 0 3px 8px rgba(0,0,0,0.08);
            transition: transform 0.2s;
        }
        
        .galeria-item:hover {
            transform: scale(1.05);
        }

        .galeria-establecimiento::-webkit-scrollbar { height: 8px; }
        .galeria-establecimiento::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 4px; }
        .galeria-establecimiento::-webkit-scrollbar-thumb { background: #cbd5e0; border-radius: 4px; }
        .galeria-establecimiento::-webkit-scrollbar-thumb:hover { background: #a0aec0; }

        .precio-tag {
            background-color: #28a745;
            color: white;
            border-radius: 50px;
            padding: 3px 10px;
            font-size: 0.8rem;
            font-weight: 700;
            margin-left: 5px;
        }

        .no-establecimientos {
            background-color: white;
            border-radius: 15px;
            box-shadow: 0 .5rem 1rem rgba(0, 0, 0, .05);
            padding: 3rem;
            text-align: center;
        }

        /* Modal Eliminar */
        .modal-confirm .modal-content { padding: 20px; border-radius: 15px; border: none; }
        .modal-confirm .modal-header.delete { background-color: #f7d7db; border-bottom: none; position: relative; text-align: center; margin: -20px -20px 0; border-top-left-radius: 15px; border-top-right-radius: 15px; padding: 35px; }
        .modal-confirm h4 { text-align: center; font-size: 26px; margin: 30px 0 -15px; color: #333; }
        .modal-confirm .icon-box { color: #fff; position: absolute; margin: 0 auto; left: 0; right: 0; top: -40px; width: 80px; height: 80px; border-radius: 50%; background-color: #f15e5e; display: flex; align-items: center; justify-content: center; box-shadow: 0px 2px 2px rgba(0, 0, 0, 0.1); }
        .modal-confirm .icon-box i { font-size: 40px; }

        @media (max-width: 768px) {
            .filaEstablecimiento { flex-direction: column; text-align: center; }
            .imagenCompacta { margin-right: 0; margin-bottom: 15px; width: 150px; height: 150px; }
            .accionesFila { margin-left: 0; margin-top: 15px; flex-wrap: wrap; justify-content: center; width: 100%; }
            .nombreEstablecimiento { justify-content: center; }
        }
    </style>
</head>

<body>
    <div class="position-fixed top-0 end-0 p-3" style="z-index: 10500">
        <div id="liveToast" class="toast align-items-center text-white border-0 custom-toast" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body fw-bold" id="toastMessage"></div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    </div>

    <div class="page-shell">
        
        
        <?php if ($mostrarMensajeLimite): ?>
            <div class="mensaje-limite mt-4">
                Ha alcanzado el número máximo de establecimientos para su suscripción.
                Para mejorar su plan <a href="Suscripciones.php">pulse aquí</a>.
            </div>
        <?php endif; ?>

        <?php 
        $heroActionButton = '';
        if ($mostrarMensajeLimite) {
            $heroActionButton = '<button type="button" class="btn-hero-action disabled-style" disabled><i class="fas fa-plus me-2"></i> Añadir Establecimiento</button>';
        } else {
            $heroActionButton = '<form action="anadirEstablecimiento.php" method="get" style="margin: 0;"><button type="submit" class="btn-hero-action"><i class="fas fa-plus me-2"></i> Añadir Establecimiento</button></form>';
        }
        include 'headerAnfitrion.php';
        ?>

        <?php if (empty($establecimientos)): ?>
            <div class="no-establecimientos">
                <img src="../img/establecimiento.png" width="80" alt="Logo Establecimiento" class="mb-3">
                <h3 class="fw-bold mb-3">No tienes establecimientos registrados</h3>
                <p>¡Comienza a añadir tu primer establecimiento para ofrecer espacios de trabajo a nómadas digitales!</p>
            </div>
        <?php else: ?>

            <div class="row">
                <div class="col-12">
                <?php foreach ($establecimientos as $index => $establecimiento):
                    $direccionFormateada = formatearDireccion($establecimiento['direccion'], $establecimiento['piso']);
                ?>
                    <div class="filaEstablecimiento" id="establecimiento-<?php echo $establecimiento['id']; ?>">
                        
                    
                        <div class="imagenCompacta">
                            <?php
                            /*
                            $imgSrc = strpos($establecimiento['image_url'], 'http') === 0 
                                ? $establecimiento['image_url'] 
                                : 'http://' . $establecimiento['image_url'];
                            
                            if($establecimiento['image_url'] == '../img/bricks0.jpg'){
                                $imgSrc = '../img/bricks0.jpg';
                            }
                            */
                
 
        // Dentro del loop de establecimientos
            $rawUrl = $establecimiento['image_url'];
            $imgSrc = '../img/bricks0.jpg';

            if (!empty($rawUrl) && $rawUrl != '../img/bricks0.jpg') {
            // Extraemos solo la ruta (ej: /establecimientos/foto.jpg) borrando cualquier IP o dominio viejo
            $path = parse_url($rawUrl, PHP_URL_PATH);
             $imgSrc = rtrim($_ENV['MINIO_PUBLIC_URL'], '/') . $path;
                }
            ?>
            <img src="<?= htmlspecialchars($imgSrc) ?>" ...>
                            
                            <img src="<?php echo htmlspecialchars($imgSrc); ?>" alt="Imagen local">
                        </div>

                        <div class="infoPrincipal">
                            <div class="nombreEstablecimiento">
                                <?php echo htmlspecialchars($establecimiento['nombre']); ?>
                                
                                <?php if ($establecimiento['has_wifi']): ?>
                                    <i class="fas fa-wifi text-primary fs-6" title="WiFi"></i>
                                <?php endif; ?>
                                <?php if ($establecimiento['has_parking']): ?>
                                    <i class="fas fa-parking text-secondary fs-6" title="Parking"></i>
                                <?php endif; ?>
                            </div>

                            <p class="descripcionCorta">
                                <?php echo htmlspecialchars(mb_strimwidth($establecimiento['descripcion'] ?? 'Sin descripción.', 0, 150, "...")); ?>
                            </p>
                            
                            <p class="direccionFiscal mb-0">
                                <i class="fas fa-map-marker-alt text-danger me-1"></i> 
                                <?php echo htmlspecialchars($direccionFormateada); ?>, 
                                <?php echo htmlspecialchars($establecimiento['localidad']); ?>
                            </p>
                        </div>

                        <div class="accionesFila">
                            <button class="btn btn-outline-secondary btn-accion bg-white" onclick="toggleDetails('<?php echo $establecimiento['id']; ?>')">
                                <span id="toggle-text-<?php echo $establecimiento['id']; ?>">Ver detalles</span>
                                <i class="fas fa-chevron-down ms-1" id="toggle-icon-<?php echo $establecimiento['id']; ?>"></i>
                            </button>
                            <a href="editarEstablecimiento.php?id=<?php echo $establecimiento['id']; ?>" class="btn btn-info text-white btn-accion">
                                <i class="fas fa-edit"></i>
                            </a>
                            <button class="btn btn-danger btn-accion" onclick="confirmarEliminacion('<?php echo $establecimiento['id']; ?>', '<?php echo htmlspecialchars($establecimiento['nombre']); ?>')">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </div>

                        <div class="collapsed-content" id="details-<?php echo $establecimiento['id']; ?>">
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong><i class="fas fa-align-left text-success me-2"></i>Descripción completa:</strong><br> <?php echo htmlspecialchars($establecimiento['descripcion']); ?></p>
                                    <p><strong><i class="fas fa-map text-success me-2"></i>Provincia:</strong> <?php echo htmlspecialchars($establecimiento['provincia']); ?> (CP: <?php echo htmlspecialchars($establecimiento['codigo_postal']); ?>)</p>
                                    
                                    <?php if ($establecimiento['has_wifi']): ?>
                                        <p><strong><i class="fas fa-wifi text-success me-2"></i>WiFi:</strong> <span class="precio-tag"><i class="fas fa-euro-sign"></i> <?php echo number_format($establecimiento['wifi_price'], 2); ?>/h</span></p>
                                    <?php endif; ?>

                                    <?php if ($establecimiento['has_parking']): ?>
                                        <p><strong><i class="fas fa-parking text-success me-2"></i>Parking:</strong> <span class="precio-tag"><i class="fas fa-euro-sign"></i> <?php echo number_format($establecimiento['parking_price'], 2); ?>/día</span></p>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="map-container-individual" id="map-<?php echo $establecimiento['id']; ?>"></div>
                                </div>

                                <?php if (!empty($establecimiento['gallery'])): ?>
                                    <div class="col-12 mt-4">
                                        <h6 class="fw-bold mb-2 text-muted text-uppercase" style="font-size: 0.8rem; letter-spacing: 1px;">
                                            <i class="fas fa-images text-primary me-2"></i> Galería de Fotos (<?php echo count($establecimiento['gallery']); ?>)
                                        </h6>
                                        <div class="galeria-establecimiento">
                                            <?php foreach ($establecimiento['gallery'] as $img): 
                                                $imgSrc = strpos($img['image_url'], 'http') === 0 
                                                    ? $img['image_url'] 
                                                    : 'http://' . $img['image_url'];
                                            ?>
                                                <img src="<?php echo htmlspecialchars($imgSrc); ?>" class="galeria-item shadow-sm" alt="Foto galería">
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                    </div>
                <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-confirm">
            <div class="modal-content">
                <div class="modal-header delete">
                    <div class="icon-box">
                        <i class="fas fa-trash"></i>
                    </div>
                </div>
                <div class="modal-body text-center mt-4">
                    <h4 class="modal-title mb-4">¿Estás seguro?</h4>
                    <p>¿Realmente deseas eliminar el establecimiento "<span id="establecimiento-nombre" class="fw-bold"></span>"? Esta acción no se puede deshacer.</p>
                </div>
                <div class="modal-footer d-flex justify-content-center">
                    <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" id="btn-confirmar-eliminar" class="btn btn-danger px-4">Sí, eliminar</button>
                </div>
            </div>
        </div>
    </div>

    <?php include 'footerAnfitrion.php'; ?>

    <script>
        const MAPBOX_ACCESS_TOKEN = "pk.eyJ1IjoiYW5kcnplamJhbmFzIiwiYSI6ImNrcHdrZXIyYTAyZWkyb3AwNGtpbmtrbXYifQ.PN_iZ4Mh08-V5EXHAHpCSg";
        const maps = {};

        // Función para mostrar notificaciones Toasts sin alerts
        function mostrarNotificacion(mensaje, tipo = 'success') {
            const toastEl = document.getElementById('liveToast');
            const toastMessage = document.getElementById('toastMessage');

            toastEl.classList.remove('bg-success', 'bg-danger', 'bg-warning');
            
            if (tipo === 'success') {
                toastEl.classList.add('bg-success');
                mensaje = '✅ ' + mensaje;
            } else if (tipo === 'error') {
                toastEl.classList.add('bg-danger');
                mensaje = '⚠️ ' + mensaje;
            }

            toastMessage.textContent = mensaje;
            const toast = new bootstrap.Toast(toastEl, { delay: 3500 });
            toast.show();
        }

        function toggleDetails(id) {
            const detailsElement = document.getElementById(`details-${id}`);
            const toggleText = document.getElementById(`toggle-text-${id}`);
            const toggleIcon = document.getElementById(`toggle-icon-${id}`);

            if (detailsElement.style.display === 'block') {
                detailsElement.style.display = 'none';
                toggleText.innerText = 'Ver detalles';
                toggleIcon.classList.remove('fa-chevron-up');
                toggleIcon.classList.add('fa-chevron-down');
            } else {
                detailsElement.style.display = 'block';
                toggleText.innerText = 'Ocultar detalles';
                toggleIcon.classList.remove('fa-chevron-down');
                toggleIcon.classList.add('fa-chevron-up');
                initMapIfNeeded(id);
            }
        }

        function initMapIfNeeded(id) {
            const mapContainer = document.getElementById(`map-${id}`);

            if (maps[id]) {
                maps[id].resize();
                return;
            }

            const establecimientos = <?php echo json_encode($establecimientos); ?>;
            const establecimiento = establecimientos.find(e => e.id === id);
            
            if (!establecimiento || !establecimiento.latitude || !establecimiento.longitude) {
                mapContainer.innerHTML = '<div class="alert alert-warning m-3">No hay coordenadas disponibles para mostrar el mapa</div>';
                return;
            }

            mapboxgl.accessToken = MAPBOX_ACCESS_TOKEN;
            const map = new mapboxgl.Map({
                container: `map-${id}`,
                style: 'mapbox://styles/mapbox/streets-v11',
                center: [establecimiento.longitude, establecimiento.latitude],
                zoom: 15
            });

            map.addControl(new mapboxgl.NavigationControl(), 'top-right');

            const el = document.createElement('div');
            el.className = 'marker';
            el.style.backgroundImage = `url('../img/posicionAnfitrion.png')`;
            el.style.width = '40px';
            el.style.height = '40px';
            el.style.backgroundSize = '100%';

            new mapboxgl.Marker(el)
                .setLngLat([establecimiento.longitude, establecimiento.latitude])
                .setPopup(new mapboxgl.Popup({ offset: 25 })
                    .setHTML(`<h6 class="fw-bold m-0">${establecimiento.nombre}</h6><small>${establecimiento.direccion}</small>`))
                .addTo(map);

            maps[id] = map;
            
            setTimeout(() => { map.resize(); }, 100);
        }

        function confirmarEliminacion(id, nombre) {
            document.getElementById('establecimiento-nombre').textContent = nombre;

            const btnConfirmar = document.getElementById('btn-confirmar-eliminar');
            const nuevoBtn = btnConfirmar.cloneNode(true);
            btnConfirmar.parentNode.replaceChild(nuevoBtn, btnConfirmar);

            nuevoBtn.addEventListener('click', function() {
                eliminarEstablecimiento(id);
            });

            const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
            deleteModal.show();
        }

        function eliminarEstablecimiento(id) {
            const btn = document.getElementById('btn-confirmar-eliminar');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Eliminando...';

            fetch(`eliminarEstablecimiento.php?id=${id}`)
                .then(response => {
                    if (response.ok) return response.json();
                    throw new Error('Error al eliminar');
                })
                .then(data => {
                    bootstrap.Modal.getInstance(document.getElementById('deleteModal')).hide();
                    mostrarNotificacion("Establecimiento eliminado correctamente", "success");
                    
                    // Esperamos a que se lea la notificación antes de recargar
                    setTimeout(() => {
                        location.reload();
                    }, 1500);
                })
                .catch(error => {
                    console.error('Error:', error);
                    mostrarNotificacion("Error al eliminar el establecimiento", "error");
                    bootstrap.Modal.getInstance(document.getElementById('deleteModal')).hide();
                })
                .finally(() => {
                    btn.disabled = false;
                    btn.textContent = "Sí, eliminar";
                });
        }
    </script>
</body>

</html>