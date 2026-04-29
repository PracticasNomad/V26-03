<?php

require '../vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

$anfitriones = [];
$error_db = null;
$mensaje_exito = null;

// --- MANEJO DE MENSAJES TRAS REDIRECCIÓN (Patrón PRG) ---
if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'updated') {
        $mensaje_exito = "Datos del anfitrión actualizados correctamente.";
    } elseif ($_GET['msg'] === 'downgraded') {
        $mensaje_exito = "Se ha cancelado la suscripción. El anfitrión ahora tiene el Plan Básico.";
    }
}
if (isset($_GET['error'])) {
    if ($_GET['error'] === 'update') {
        $error_db = "Error al actualizar los datos del anfitrión. Inténtalo de nuevo.";
    } elseif ($_GET['error'] === 'downgrade') {
        $error_db = "Fallo en la BD al intentar forzar el plan básico.";
    }
}
// --------------------------------------------------------


// --- LÓGICA: EDITAR DATOS DEL ANFITRIÓN ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'editar_anfitrion') {
    $edit_id = $_POST['edit_id'] ?? '';
    $edit_name = $_POST['edit_name'] ?? '';
    $edit_email = $_POST['edit_email'] ?? '';
    $edit_phone = $_POST['edit_phone'] ?? '';

    if (!empty($edit_id) && !empty($edit_name) && !empty($edit_email)) {
        $urlUpdate = "http://" . $_ENV['SERVER_IP'] . ":" . $_ENV['DATABASE_PORT'] . "/rest/v1/host?id=eq." . urlencode($edit_id);

        $datosUpdate = [
            'name' => $edit_name,
            'email' => $edit_email,
            'phone' => $edit_phone
        ];

        $chUpdate = curl_init($urlUpdate);
        curl_setopt_array($chUpdate, [
            CURLOPT_CUSTOMREQUEST => "PATCH",
            CURLOPT_POSTFIELDS => json_encode($datosUpdate),
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $_ENV['SERVICE_APIKEY'],
                'apikey: ' . $_ENV['SERVICE_APIKEY'],
                'Content-Type: application/json',
                'Prefer: return=representation' // Le pedimos que nos devuelva la fila modificada
            ],
            CURLOPT_RETURNTRANSFER => true,
        ]);

        $resUpdate = curl_exec($chUpdate);
        $codigoUpdate = curl_getinfo($chUpdate, CURLINFO_HTTP_CODE);
        curl_close($chUpdate);

        $filasModificadas = json_decode($resUpdate, true);

        // AHORA COMPROBAMOS QUE REALMENTE SE HAYA MODIFICADO AL MENOS 1 FILA
        if ($codigoUpdate >= 200 && $codigoUpdate < 300 && is_array($filasModificadas) && count($filasModificadas) > 0) {
            header("Location: verAnfitriones.php?msg=updated");
            exit;
        } else {
            header("Location: verAnfitriones.php?error=update");
            exit;
        }
    } else {
        $error_db = "El nombre y el correo electrónico son obligatorios.";
    }
}

// --- LÓGICA: BAJAR A PLAN BÁSICO ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'bajar_plan_basico') {
    $host_id = $_POST['host_id'] ?? '';
    
    if (!empty($host_id)) {
        $urlDowngrade = "http://" . $_ENV['SERVER_IP'] . ":" . $_ENV['DATABASE_PORT'] . "/rest/v1/host?id=eq." . urlencode($host_id);

        $datosDowngrade = [
            'plan' => 'Basico',
            'plan_end' => null // Borramos el límite
        ];

        $chDowngrade = curl_init($urlDowngrade);
        curl_setopt_array($chDowngrade, [
            CURLOPT_CUSTOMREQUEST => "PATCH",
            CURLOPT_POSTFIELDS => json_encode($datosDowngrade),
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $_ENV['SERVICE_APIKEY'],
                'apikey: ' . $_ENV['SERVICE_APIKEY'],
                'Content-Type: application/json',
                'Prefer: return=representation' // Le pedimos que nos devuelva la fila modificada
            ],
            CURLOPT_RETURNTRANSFER => true,
        ]);

        $resDowngrade = curl_exec($chDowngrade);
        $codigoDowngrade = curl_getinfo($chDowngrade, CURLINFO_HTTP_CODE);
        curl_close($chDowngrade);

        $filasModificadas = json_decode($resDowngrade, true);

        // AHORA COMPROBAMOS QUE REALMENTE SE HAYA MODIFICADO AL MENOS 1 FILA
        if ($codigoDowngrade >= 200 && $codigoDowngrade < 300 && is_array($filasModificadas) && count($filasModificadas) > 0) {
            header("Location: verAnfitriones.php?msg=downgraded");
            exit;
        } else {
            header("Location: verAnfitriones.php?error=downgrade");
            exit;
        }
    }
}


// OBTENCIÓN DE DATOS 
$urlEstablecimientos = "http://" . $_ENV['SERVER_IP'] . ":" . $_ENV['DATABASE_PORT'] . "/rest/v1/establecimiento?select=host_id,host(id,name,email,phone,empresa,avatar_url,plan)";

$ch = curl_init($urlEstablecimientos);
curl_setopt_array($ch, [
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $_ENV['SERVICE_APIKEY'],
        'apikey: ' . $_ENV['SERVICE_APIKEY'],
    ],
    CURLOPT_RETURNTRANSFER => true,
]);

$resultado = curl_exec($ch);
$codigoRespuesta = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($codigoRespuesta >= 200 && $codigoRespuesta < 300) {
    $establecimientos = json_decode($resultado, true);

    $anfitrionesUnicos = [];
    foreach ($establecimientos as $est) {
        if (isset($est['host']) && $est['host']) {
            $hostId = $est['host']['id'];
            if (!isset($anfitrionesUnicos[$hostId])) {
                $anfitrionesUnicos[$hostId] = $est['host'];
            }
        }
    }

    $anfitriones = array_values($anfitrionesUnicos);
    usort($anfitriones, function ($a, $b) {
        return strcmp($a['name'] ?? '', $b['name'] ?? '');
    });
} else {
    $error_db = "Error al obtener los datos (Código: $codigoRespuesta).";
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

    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <link rel="icon" href="../favicon-color.png">
    <link rel="icon" href="../favicon-negro.png" media="(prefers-color-scheme: light)">
    <link rel="icon" href="../favicon-color.png" media="(prefers-color-scheme: dark)">
    <title>Gestión Global de Anfitriones</title>
    
    <script>
        const MINIO_URL = "<?php echo rtrim($_ENV['MINIO_PUBLIC_URL'] ?? 'https://127.0.0.1:9000', '/'); ?>";
    </script>

    <style>
        :root {
            --primary-color: #dc3545;
            --bg: #f4f7fb;
            --ink: #1f2933;
            --line: #d8e1ea;
            --accent-dark: #8c1c13;
            --accent-mid: #c44536;
            --accent-soft: #fce8e5;
        }

        .page-shell { max-width: 1400px; margin: 0 auto; padding: 0 15px; box-sizing: border-box; }
        .page-hero { max-width: 100%; margin: 1.2rem 0 0.5rem; padding: 0; box-sizing: border-box; }
        .page-hero-inner { border-radius: 20px; background: linear-gradient(135deg, var(--accent-dark) 0%, var(--accent-mid) 52%, #df786c 100%); color: #ffffff; padding: 1.1rem 1.2rem; box-shadow: 0 18px 40px rgba(140, 28, 19, 0.24); border: 1px solid rgba(255, 255, 255, 0.18); }
        .page-hero-title { font-size: 1.35rem; font-weight: 800; letter-spacing: 0.2px; }
        .hero-title-row { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }

        body { font-family: 'Nunito', sans-serif; background: #eef2f5; color: var(--ink); padding-bottom: 120px; }
        .contenedor-principal { max-width: 100%; margin: 2rem 0 0; padding: 0; box-sizing: border-box; }

        .select-container { width: 100%; max-width: 980px; margin: 0 auto 30px auto; background-color: rgba(255, 255, 255, 0.92); padding: 20px 22px; border-radius: 18px; box-shadow: 0 14px 28px rgba(31, 41, 51, 0.1); border: 1px solid rgba(216, 225, 234, 0.8); backdrop-filter: blur(8px); }
        .select-toolbar-title { font-size: 1.05rem; margin-bottom: 0.25rem; }
        .select-toolbar-subtitle { margin-bottom: 0.9rem; color: #5f6d79; font-size: 0.92rem; font-weight: 600; }

        .select2-container .select2-selection--single { height: 50px !important; padding: 10px 15px; border: 1px solid #d7dfe8; border-radius: 12px; font-size: 1rem; box-shadow: inset 0 1px 2px rgba(15, 23, 42, 0.04); background-color: #fff; }
        .select2-container--default .select2-selection--single .select2-selection__arrow { height: 48px !important; right: 15px !important; }
        .select2-container--default .select2-selection--single .select2-selection__rendered { color: #2f3c4a; line-height: 30px; font-weight: 600; }

        .anfitrion-card { background-color: white; border-radius: 20px; box-shadow: 0 18px 36px rgba(31, 41, 51, 0.12); margin-bottom: 2rem; overflow: hidden; transition: all 0.3s; width: 100%; max-width: 980px; margin: 0 auto; border: 1px solid var(--line); }
        .card-header-custom { background: linear-gradient(135deg, var(--accent-dark) 0%, var(--accent-mid) 55%, #df786c 100%); padding: 30px 20px; color: white; text-align: center; border-bottom: 5px solid var(--accent-dark); }
        .card-header-custom .img-profile { width: 100px; height: 100px; border-radius: 50%; object-fit: cover; border: 4px solid white; margin-bottom: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.2); background-color: white; }
        .card-body { padding: 30px; }

        .info-row { display: flex; align-items: center; margin-bottom: 15px; gap: 15px; font-size: 1.1rem; }
        .info-icon { color: var(--accent-mid); width: 30px; text-align: center; font-size: 1.3rem; }

        .btn-actions { display: flex; gap: 10px; margin-top: 25px; flex-wrap: wrap; }
        .btn-action { flex: 1; border-radius: 10px; padding: 0.75rem 1rem; font-weight: 600; display: flex; align-items: center; justify-content: center; gap: 8px; transition: all 0.3s; color: white !important; }
        .btn-view-est { background-color: #17a2b8; }
        .btn-view-est:hover { background-color: #138496; }
        .btn-edit-data { background-color: #ffc107; color: #212529 !important; }
        .btn-edit-data:hover { background-color: #e0a800; }
        .btn-downgrade { background-color: #dc3545; }
        .btn-downgrade:hover { background-color: #c82333; }

        a, a:visited, a:active { text-decoration: none; }
    </style>
</head>

<body>
    <div class="page-shell">

        <section class="page-hero">
            <div class="page-hero-inner">
                <div class="hero-title-row">
                    <div class="page-hero-title"><i class="fas fa-users me-2"></i>Todos los Anfitriones</div>
                </div>
            </div>
        </section>

        <div class="contenedor-principal">
            <?php if ($error_db): ?>
                <div class="alert alert-danger text-center shadow-sm rounded-pill mb-4">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?php echo $error_db; ?>
                </div>
            <?php endif; ?>

            <?php if ($mensaje_exito): ?>
                <div class="alert alert-success text-center shadow-sm rounded-pill mb-4">
                    <i class="fas fa-check-circle me-2"></i>
                    <?php echo $mensaje_exito; ?>
                </div>
            <?php endif; ?>

            <?php if (!$error_db && empty($anfitriones)): ?>
                <div class="alert alert-info text-center shadow-sm rounded-pill mb-4">
                    <i class="fas fa-info-circle me-2"></i> No hay anfitriones registrados en el sistema.
                </div>
            <?php endif; ?>

            <div class="select-container">
                <label for="select-anfitrion" class="form-label fw-bold select-toolbar-title">
                    <i class="fas fa-search me-2"></i>Buscar y seleccionar anfitrión:
                </label>
                <p class="select-toolbar-subtitle">Elige un perfil para ver sus datos y acceder a sus acciones de gestión.</p>
                <select id="select-anfitrion" class="form-select form-select-lg" <?php echo empty($anfitriones) ? 'disabled' : ''; ?>>
                    <option value=""></option>
                    <?php foreach ($anfitriones as $anf): ?>
                        <option value="<?php echo htmlspecialchars($anf['id']); ?>"
                            data-nombre="<?php echo htmlspecialchars($anf['name'] ?? 'Sin nombre'); ?>"
                            data-email="<?php echo htmlspecialchars($anf['email'] ?? 'Sin email'); ?>"
                            data-telefono="<?php echo htmlspecialchars($anf['phone'] ?? ''); ?>"
                            data-plan="<?php echo htmlspecialchars($anf['plan'] ?? 'Basico'); ?>"
                            data-avatar="<?php echo htmlspecialchars($anf['avatar_url'] ?? ''); ?>"> 
                            <?php echo htmlspecialchars($anf['name'] ?? 'Sin nombre'); ?> -
                            <?php echo htmlspecialchars($anf['email'] ?? ''); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div id="detalles-anfitrion" style="display: none;">
                <div class="anfitrion-card">
                    <div class="card-header-custom">
                        <img id="card-avatar" src="../img/perfil.png" alt="Avatar" class="img-profile">
                        <h3 class="fw-bold m-0" id="card-nombre">Nombre Apellidos</h3>
                        <div class="mt-2">
                            <span class="badge bg-light text-dark">Perfil Anfitrión</span>
                            <span id="card-plan-badge" class="badge bg-secondary ms-1">Plan Básico</span>
                        </div>
                    </div>

                    <div class="card-body">
                        <div class="info-row">
                            <div class="info-icon"><i class="fas fa-envelope"></i></div>
                            <div>
                                <strong>Email:</strong><br>
                                <span id="card-email" class="text-muted">correo@ejemplo.com</span>
                            </div>
                        </div>

                        <div class="info-row">
                            <div class="info-icon"><i class="fas fa-phone"></i></div>
                            <div>
                                <strong>Teléfono:</strong><br>
                                <span id="card-telefono" class="text-muted">+34 000 000 000</span>
                            </div>
                        </div>

                        <div class="info-row">
                            <div class="info-icon"><i class="fas fa-id-card"></i></div>
                            <div>
                                <strong>ID Interno:</strong><br>
                                <span id="card-id" class="text-muted">#</span>
                            </div>
                        </div>

                        <div class="btn-actions mt-4">
                            <a href="#" id="btn-view-est" class="btn btn-action btn-view-est">
                                <i class="fas fa-building"></i> Establecimientos
                            </a>
                            <button type="button" class="btn btn-action btn-edit-data" data-bs-toggle="modal" data-bs-target="#modalEditarAnfitrion">
                                <i class="fas fa-user-edit"></i> Editar
                            </button>
                            <button type="button" id="btn-bajar-plan" class="btn btn-action btn-downgrade" style="display: none;">
                                <i class="fas fa-level-down-alt"></i> Forzar Básico
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'footerAdmin.php'; ?>

    <div class="modal fade" id="modalEditarAnfitrion" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white" style="background-color: var(--primary-color) !important;">
                    <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Editar Anfitrión</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="editar_anfitrion">
                        <input type="hidden" name="edit_id" id="form_edit_id">

                        <div class="mb-3">
                            <label class="form-label fw-bold">Nombre</label>
                            <input type="text" class="form-control" id="form_edit_name" name="edit_name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Correo Electrónico</label>
                            <input type="email" class="form-control" id="form_edit_email" name="edit_email" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Teléfono</label>
                            <input type="text" class="form-control" id="form_edit_phone" name="edit_phone">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary" style="background-color: var(--primary-color); border:none;">Guardar Cambios</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalBajarPlan" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title"><i class="fas fa-exclamation-triangle me-2"></i>Forzar Plan Básico</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body text-center p-4">
                        <input type="hidden" name="action" value="bajar_plan_basico">
                        <input type="hidden" name="host_id" id="form_bajar_host_id">
                        
                        <h4 class="mb-3 text-dark">¿Estás seguro?</h4>
                        <p class="text-muted mb-3">Vas a cancelar la suscripción actual de <strong id="bajar_nombre_anfitrion" class="text-dark"></strong> y cambiar su plan a <strong>Básico</strong>.</p>
                        <div class="alert alert-warning small text-start">
                            <i class="fas fa-info-circle me-1"></i> Esto eliminará su periodo de finalización y el anfitrión quedará sujeto a los límites del plan básico (1 establecimiento máximo).
                        </div>
                    </div>
                    <div class="modal-footer justify-content-center border-0 mb-2">
                        <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-danger px-4">Sí, bajar plan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function() {

            // --- AUTO-OCULTAR ALERTAS DE ÉXITO O ERROR ---
            setTimeout(function() {
                $('.alert-success, .alert-danger').fadeOut('slow', function() {
                    $(this).remove();
                });
            }, 4000);

            // --- MAGIA: LIMPIAR LA URL (QUITA EL ?msg=... PARA QUE NO SE REPITA AL RECARGAR) ---
            if (window.history.replaceState) {
                const url = new URL(window.location.href);
                if (url.searchParams.has('msg') || url.searchParams.has('error')) {
                    url.searchParams.delete('msg');
                    url.searchParams.delete('error');
                    window.history.replaceState(null, null, url);
                }
            }

            $('#select-anfitrion').select2({
                placeholder: "-- Busca o selecciona un anfitrión --",
                allowClear: true,
                width: '100%',
                language: {
                    noResults: function() { return "No se encontró ningún anfitrión"; }
                }
            });

            $('#select-anfitrion').on('change', function() {
                var selectedOption = $(this).find('option:selected');
                var id = $(this).val();

                if (id) {
                    var nombre = selectedOption.data('nombre');
                    var email = selectedOption.data('email');
                    var telefono = selectedOption.data('telefono');
                    var avatarRaw = selectedOption.data('avatar');
                    var plan = selectedOption.data('plan') || 'Basico';

                    $('#card-nombre').text(nombre);
                    $('#card-email').text(email);
                    $('#card-telefono').text(telefono ? telefono : 'No registrado');
                    $('#card-id').text('#' + id);

                    let badgeClass = 'bg-secondary';
                    if(plan === 'Premium') badgeClass = 'bg-success';
                    else if(plan === 'Pro') badgeClass = 'bg-primary';
                    $('#card-plan-badge').text('Plan ' + plan).removeClass('bg-secondary bg-success bg-primary').addClass(badgeClass);

                    if(plan === 'Basico') {
                        $('#btn-bajar-plan').hide();
                    } else {
                        $('#btn-bajar-plan').show();
                        $('#btn-bajar-plan').off('click').on('click', function() {
                            $('#form_bajar_host_id').val(id);
                            $('#bajar_nombre_anfitrion').text(nombre);
                            new bootstrap.Modal(document.getElementById('modalBajarPlan')).show();
                        });
                    }

                    let finalAvatar = '../img/perfil.png';
                    if (avatarRaw && avatarRaw !== '../img/perfil.png' && avatarRaw !== '') {
                        if (avatarRaw.startsWith('../')) {
                            finalAvatar = avatarRaw; 
                        } else {
                            try {
                                let tempUrl = avatarRaw.startsWith('http') ? avatarRaw : 'http://' + avatarRaw;
                                let urlObj = new URL(tempUrl);
                                finalAvatar = MINIO_URL + urlObj.pathname;
                            } catch(e) {
                                finalAvatar = avatarRaw;
                            }
                        }
                    }
                    $('#card-avatar').attr('src', finalAvatar);

                    $('#btn-view-est').attr('href', 'verEstablecimientos.php?host_id=' + id);

                    $('#form_edit_id').val(id);
                    $('#form_edit_name').val(nombre);
                    $('#form_edit_email').val(email);
                    $('#form_edit_phone').val(telefono);

                    $('#detalles-anfitrion').fadeIn();
                } else {
                    $('#detalles-anfitrion').fadeOut();
                }
            });
        });
    </script>

</body>

</html>