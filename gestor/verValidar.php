<?php
session_start();
require '../vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

$establecimientos = [];
$establecimientosRechazados = [];
$establecimientosValidados = [];

$url = 'http://' . $_ENV['SERVER_IP'] . ':' . $_ENV['DATABASE_PORT'] . '/rest/v1/establecimiento';
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
    if (is_array($data)) {
        foreach ($data as $est) {
            
            // 1. Buscamos el valor independientemente de mayúsculas/minúsculas
            $val = $est['estaValidado'] ?? $est['estavalidado'] ?? null;

            // 2. Comprobación BLINDADA de Validados (True)
            if ($val === true || $val === 'true' || $val === 't' || $val === 1 || $val === '1') {
                $establecimientosValidados[] = $est;
            } 
            // 3. Comprobación BLINDADA de Rechazados (False)
            elseif ($val === false || $val === 'false' || $val === 'f' || $val === 0 || $val === '0') {
                $establecimientosRechazados[] = $est;
            } 
            // 4. Todo lo demás (NULL, vacíos) va a Pendientes
            else {
                $establecimientos[] = $est;
            }
        }
    }
}

function formatearDireccion($dir, $piso) {
    $result = htmlspecialchars($dir ?? '');
    if (!empty($piso)) $result .= ' Piso ' . htmlspecialchars($piso);
    return $result;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://kit.fontawesome.com/b8814a2854.js" crossorigin="anonymous"></script>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;600;700&display=swap" rel="stylesheet">
    <title>Gestión de Validaciones</title>
    <style>
        body { font-family: 'Nunito', sans-serif; background-color: #f8f9fa; padding-bottom: 80px; }
        .establecimiento-card {
            background-color: white; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            margin-bottom: 1.5rem; overflow: hidden; transition: 0.3s; border: 1px solid #e9ecef;
            display: flex; flex-direction: column; height: 100%;
        }
        .establecimiento-card:hover { box-shadow: 0 6px 16px rgba(0,0,0,0.15); transform: translateY(-2px); }
        .card-header { height: 140px; background-size: cover; background-position: center; display: flex; align-items: flex-end; position: relative; background-color: #f8f9fa; }
        .card-header-overlay { position: absolute; top:0; left:0; right:0; bottom:0; background: linear-gradient(to bottom, transparent, rgba(0,0,0,0.8)); }
        .card-title { color: white; padding: 15px; font-weight: 600; font-size: 1.1rem; z-index: 1; width: 100%; }
        .card-body { padding: 15px; display: flex; flex-direction: column; flex: 1; }
        .info-row { display: flex; align-items: center; margin-bottom: 8px; gap: 8px; font-size: 0.95rem; }
        .info-icon { color: #28a745; width: 18px; text-align: center; }
        .action-buttons-container { margin-top: auto; padding-top: 15px; border-top: 1px solid #eee; display: flex; flex-direction: column; gap: 8px; }
        .quick-actions { display: flex; gap: 8px; }
        .btn-quick { flex: 1; border: none; border-radius: 6px; padding: 0.5rem; font-size: 0.85rem; font-weight: 600; color: white; transition: 0.2s; }
        .btn-quick.approve { background-color: #28a745; }
        .btn-quick.approve:hover { background-color: #218838; }
        .btn-quick.reject { background-color: #dc3545; }
        .btn-quick.reject:hover { background-color: #c82333; }
        .btn-quick:disabled { opacity: 0.7; cursor: not-allowed; }
        .btn-validar { background-color: #007bff; border: none; color: white; border-radius: 6px; padding: 0.5rem; font-weight: 600; font-size: 0.85rem; text-align: center; text-decoration: none; display: block; transition: 0.2s; }
        .btn-validar:hover { background-color: #0056b3; color: white; }
        .no-establecimientos { background-color: white; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); padding: 3rem 1rem; text-align: center; border: 1px solid #e9ecef; width: 100%; margin-top: 1rem; }
        
        /* Footer */
        .footer { background-color: #E3E1E1; position: fixed; bottom: 0; width: 100%; z-index: 1000; font-size: 15px; }
        .footer-container { background-color: white; box-shadow: 0px -2px 10px rgba(0,0,0,0.1); padding-top: 1px !important; padding-bottom: 1px !important; }
        .footer-item { padding: 8px 0; -webkit-tap-highlight-color: transparent; }
        .icon-container { transition: transform 0.3s ease; padding: 5px 0; }
        .footer-item:hover .icon-container { transform: translateY(-7px); }
        a { color: inherit; text-decoration: none; }
        #lbl_val .icon-container { color: #007bff; }
        #lbl_val { color: #00B7CF !important; }
    </style>
</head>

<body>
    <header>
        <div class="container-fluid info text-center">
            <div class="row">
                <div class="col h2 fw-bold pt-3 pb-2 text-dark">Gestión de Validaciones</div>
            </div>
        </div>
    </header>

    <div class="container-fluid pb-5 px-3 px-md-5">
        <ul class="nav nav-tabs" id="validationTabs" role="tablist">
            <li class="nav-item" role="presentation"><button class="nav-link active" id="pendientes-tab" data-bs-toggle="tab" data-bs-target="#pendientes" type="button" role="tab"><i class="fas fa-hourglass-half me-2"></i>Pendientes</button></li>
            <li class="nav-item" role="presentation"><button class="nav-link" id="rechazados-tab" data-bs-toggle="tab" data-bs-target="#rechazados" type="button" role="tab"><i class="fas fa-times-circle me-2"></i>Rechazados</button></li>
            <li class="nav-item" role="presentation"><button class="nav-link" id="validados-tab" data-bs-toggle="tab" data-bs-target="#validados" type="button" role="tab"><i class="fas fa-check-circle me-2"></i>Aprobados</button></li>
        </ul>

        <div class="tab-content mt-4" id="validationTabsContent">
            
            <div class="tab-pane fade show active" id="pendientes" role="tabpanel">
                <div class="row" id="row-pendientes">
                    <div class="no-establecimientos" id="msg-no-pendientes" style="<?php echo empty($establecimientos) ? 'display:block;' : 'display:none;'; ?>">
                        <img src="../img/establecimiento.png" width="80" alt="Sin pendientes" class="mb-3">
                        <h4 class="fw-bold mb-3">No hay establecimientos pendientes</h4>
                    </div>

                    <?php foreach ($establecimientos as $establecimiento): 
                        $direccionFormateada = formatearDireccion($establecimiento['direccion'], $establecimiento['piso']);
                    ?>
                        <div class="col-12 col-md-6 col-lg-4 mb-4 card-container" id="col-est-<?php echo $establecimiento['id']; ?>">
                            <div class="establecimiento-card" id="card-<?php echo $establecimiento['id']; ?>">
                                <div class="card-header" style="background-image: url('<?php echo isset($establecimiento['image_url']) ? 'http://' . $establecimiento['image_url'] : '../img/default.jpg'; ?>');">
                                    <div class="card-header-overlay"></div>
                                    <div class="card-title"><?php echo htmlspecialchars($establecimiento['nombre']); ?></div>
                                </div>
                                <div class="card-body">
                                    <div class="info-row"><div class="info-icon"><i class="fas fa-map-marker-alt"></i></div><div><?php echo htmlspecialchars($direccionFormateada); ?></div></div>
                                    <div class="info-row"><div class="info-icon"><i class="fas fa-city"></i></div><div><?php echo htmlspecialchars($establecimiento['localidad'] ?? ''); ?></div></div>
                                    <div id="badge-container-<?php echo $establecimiento['id']; ?>">
                                        <div class="info-row mt-2"><div class="info-icon"><i class="fas fa-hourglass-half text-warning"></i></div><div><strong>Estado:</strong> <span class="badge bg-warning text-dark">Pendiente</span></div></div>
                                    </div>
                                    
                                    <div class="action-buttons-container" id="action-container-<?php echo $establecimiento['id']; ?>">
                                        <a href="validar.php?id=<?php echo $establecimiento['id']; ?>" class="btn-validar mb-2"><i class="fas fa-eye"></i> Revisar completo</a>
                                        <div class="quick-actions" id="quick-actions-<?php echo $establecimiento['id']; ?>">
                                            <button class="btn-quick approve" onclick="procesarValidacionRapida('<?php echo $establecimiento['id']; ?>', 'aprobar', this)"><i class="fas fa-check"></i> Aprobar</button>
                                            <button class="btn-quick reject" onclick="procesarValidacionRapida('<?php echo $establecimiento['id']; ?>', 'rechazar', this)"><i class="fas fa-times"></i> Rechazar</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="tab-pane fade" id="rechazados" role="tabpanel">
                <div class="row" id="row-rechazados">
                    <div class="no-establecimientos" id="msg-no-rechazados" style="<?php echo empty($establecimientosRechazados) ? 'display:block;' : 'display:none;'; ?>">
                        <h4 class="fw-bold mb-3">No hay establecimientos rechazados</h4>
                    </div>

                    <?php foreach ($establecimientosRechazados as $establecimiento): 
                        $direccionFormateada = formatearDireccion($establecimiento['direccion'], $establecimiento['piso']);
                    ?>
                        <div class="col-12 col-md-6 col-lg-4 mb-4 card-container" id="col-est-<?php echo $establecimiento['id']; ?>">
                            <div class="establecimiento-card" id="card-<?php echo $establecimiento['id']; ?>" style="border-left: 4px solid #dc3545; opacity: 0.85;">
                                <div class="card-header" style="background-image: url('<?php echo isset($establecimiento['image_url']) ? 'http://' . $establecimiento['image_url'] : '../img/default.jpg'; ?>');">
                                    <div class="card-header-overlay"></div>
                                    <div class="card-title"><?php echo htmlspecialchars($establecimiento['nombre']); ?></div>
                                </div>
                                <div class="card-body">
                                    <div class="info-row"><div class="info-icon"><i class="fas fa-map-marker-alt"></i></div><div><?php echo htmlspecialchars($direccionFormateada); ?></div></div>
                                    <div class="info-row"><div class="info-icon"><i class="fas fa-city"></i></div><div><?php echo htmlspecialchars($establecimiento['localidad'] ?? ''); ?></div></div>
                                    <div id="badge-container-<?php echo $establecimiento['id']; ?>">
                                        <div class="info-row mt-2"><div class="info-icon"><i class="fas fa-ban text-danger"></i></div><div><strong>Estado:</strong> <span class="badge bg-danger">Rechazado</span></div></div>
                                    </div>
                                    <div class="action-buttons-container" id="action-container-<?php echo $establecimiento['id']; ?>">
                                        <a href="validar.php?id=<?php echo $establecimiento['id']; ?>" class="btn-validar"><i class="fas fa-eye"></i> Ver detalle completo</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="tab-pane fade" id="validados" role="tabpanel">
                <div class="row" id="row-validados">
                    <div class="no-establecimientos" id="msg-no-validados" style="<?php echo empty($establecimientosValidados) ? 'display:block;' : 'display:none;'; ?>">
                        <h4 class="fw-bold mb-3">No hay establecimientos aprobados</h4>
                    </div>

                    <?php foreach ($establecimientosValidados as $establecimiento): 
                        $direccionFormateada = formatearDireccion($establecimiento['direccion'], $establecimiento['piso']);
                    ?>
                        <div class="col-12 col-md-6 col-lg-4 mb-4 card-container" id="col-est-<?php echo $establecimiento['id']; ?>">
                            <div class="establecimiento-card" id="card-<?php echo $establecimiento['id']; ?>" style="border-left: 4px solid #28a745;">
                                <div class="card-header" style="background-image: url('<?php echo isset($establecimiento['image_url']) ? 'http://' . $establecimiento['image_url'] : '../img/default.jpg'; ?>');">
                                    <div class="card-header-overlay"></div>
                                    <div class="card-title"><?php echo htmlspecialchars($establecimiento['nombre']); ?></div>
                                </div>
                                <div class="card-body">
                                    <div class="info-row"><div class="info-icon"><i class="fas fa-map-marker-alt"></i></div><div><?php echo htmlspecialchars($direccionFormateada); ?></div></div>
                                    <div class="info-row"><div class="info-icon"><i class="fas fa-city"></i></div><div><?php echo htmlspecialchars($establecimiento['localidad'] ?? ''); ?></div></div>
                                    <div id="badge-container-<?php echo $establecimiento['id']; ?>">
                                        <div class="info-row mt-2"><div class="info-icon"><i class="fas fa-check-circle text-success"></i></div><div><strong>Estado:</strong> <span class="badge bg-success">Aprobado</span></div></div>
                                    </div>
                                    <div class="action-buttons-container" id="action-container-<?php echo $establecimiento['id']; ?>">
                                        <a href="validar.php?id=<?php echo $establecimiento['id']; ?>" class="btn-validar"><i class="fas fa-eye"></i> Ver detalle completo</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

        </div>
    </div>

    <div class="container-fluid footer mt-5 p-3">
        <div class="row text-center fixed-bottom bg-blanco pt-1 px-2 footer-container">
            <label id="lbl_anf" class="col-2 text-center footer-item"><div class="row"><a href="Anfitriones.php"><div class="col-12 icon-container"><i class="h2 fas fa-users p-1 m-0"></i><div>Anfitriones</div></div></a></div></label>
            <label id="lbl_val" class="col-2 text-center footer-item"><div class="row"><a href="verValidar.php"><div class="col-12 icon-container"><i class="h2 fas fa-check-circle p-1 m-0"></i><div>Validar</div></div></a></div></label>
            <label id="lbl_res" class="col-2 text-center footer-item"><div class="row"><a href="verReservas.php"><div class="col-12 icon-container"><i class="h2 fas fa-book-open p-1 m-0"></i><div>Reservas</div></div></a></div></label>
            <label id="lbl_his" class="col-2 text-center footer-item"><div class="row"><a href="verEstablecimientos.php"><div class="col-12 icon-container"><i class="h2 fas fa-building p-1 m-0"></i><div>Establecimientos</div></div></a></div></label>
            <label id="lbl_esp" class="col-2 text-center footer-item"><div class="row"><a href="verEspacios.php"><div class="col-12 icon-container"><i class="h2 fas fa-chair p-1 m-0"></i><div>Espacios</div></div></a></div></label>
            <label id="lbl_per" class="col-2 text-center footer-item"><div class="row"><a href="tuPerfil.php"><div class="col-12 icon-container"><i class="h2 fas fa-user-tie p-1 m-0"></i><div>Perfil</div></div></a></div></label>
        </div>
    </div>

    <script>
        function procesarValidacionRapida(id, accion, btnElement) {
            if (!confirm(accion === 'aprobar' ? '¿Aprobar y publicar?' : '¿Rechazar establecimiento?')) return;

            const originalText = btnElement.innerHTML;
            btnElement.innerHTML = '<i class="fas fa-spinner fa-spin"></i>...';
            btnElement.disabled = true;

            const formData = new FormData();
            formData.append('accion', accion);

            fetch('procesar_validacion.php?id=' + id + '&ajax=1', {
                method: 'POST',
                body: formData
            })
            .then(async response => {
                const textoCrudo = await response.text();
                try {
                    return JSON.parse(textoCrudo);
                } catch (e) {
                    alert("⚠️ Error del servidor:\n\n" + textoCrudo.substring(0, 150));
                    throw new Error("Respuesta inválida");
                }
            })
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    moverTarjetaEnDOM(id, accion);
                } else {
                    alert('🚨 Error al guardar: ' + data.error);
                    btnElement.innerHTML = originalText;
                    btnElement.disabled = false;
                }
            })
            .catch(err => {
                console.error(err);
                btnElement.innerHTML = originalText;
                btnElement.disabled = false;
            });
        }

        function moverTarjetaEnDOM(id, accion) {
            const cleanId = String(id).trim();
            const colContenedor = document.getElementById('col-est-' + cleanId);
            const tarjeta = document.getElementById('card-' + cleanId);
            const botonesRapidos = document.getElementById('quick-actions-' + cleanId);
            const badgeContenedor = document.getElementById('badge-container-' + cleanId);
            
            if (!colContenedor || !tarjeta) return;

            // Quitamos los botones de Aprobar/Rechazar al moverse
            if (botonesRapidos) botonesRapidos.remove();

            if (accion === 'aprobar') {
                tarjeta.style.borderLeft = '4px solid #28a745';
                tarjeta.style.opacity = '1';
                badgeContenedor.innerHTML = '<div class="info-row mt-2"><div class="info-icon"><i class="fas fa-check-circle text-success"></i></div><div><strong>Estado:</strong> <span class="badge bg-success">Aprobado</span></div></div>';
                
                document.getElementById('row-validados').appendChild(colContenedor);
                const msgValidados = document.getElementById('msg-no-validados');
                if (msgValidados) msgValidados.style.display = 'none';

            } else {
                tarjeta.style.borderLeft = '4px solid #dc3545';
                tarjeta.style.opacity = '0.85';
                badgeContenedor.innerHTML = '<div class="info-row mt-2"><div class="info-icon"><i class="fas fa-ban text-danger"></i></div><div><strong>Estado:</strong> <span class="badge bg-danger">Rechazado</span></div></div>';
                
                document.getElementById('row-rechazados').appendChild(colContenedor);
                const msgRechazados = document.getElementById('msg-no-rechazados');
                if (msgRechazados) msgRechazados.style.display = 'none';
            }

            // Validar si la lista de pendientes quedó vacía para enseñar el mensaje
            const pendientesActivos = document.querySelectorAll('#row-pendientes .card-container');
            if (pendientesActivos.length === 0) {
                const msgPendientes = document.getElementById('msg-no-pendientes');
                if (msgPendientes) msgPendientes.style.display = 'block';
            }
        }
    </script>
</body>
</html>