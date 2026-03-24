<?php

require '../vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

$anfitriones = [];
$error_db = null;
$mensaje_exito = null;

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
                'Prefer: return=representation'
            ],
            CURLOPT_RETURNTRANSFER => true,
        ]);

        $resUpdate = curl_exec($chUpdate);
        $codigoUpdate = curl_getinfo($chUpdate, CURLINFO_HTTP_CODE);
        curl_close($chUpdate);

        if ($codigoUpdate >= 200 && $codigoUpdate < 300) {
            $mensaje_exito = "Datos del anfitrión actualizados correctamente.";
        } else {
            $error_db = "Error al actualizar el anfitrión (Código: $codigoUpdate).";
        }
    } else {
        $error_db = "El nombre y el correo electrónico son obligatorios.";
    }
}

$urlEstablecimientos = "http://" . $_ENV['SERVER_IP'] . ":" . $_ENV['DATABASE_PORT'] . "/rest/v1/establecimiento?select=host_id,host(id,name,email,phone,empresa)";

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
    <style>
        :root {
            --primary-color: #dc3545;
            /* Rojo admin */
            --bg: #f4f7fb;
            --ink: #1f2933;
            --line: #d8e1ea;
            --accent-dark: #8c1c13;
            --accent-mid: #c44536;
            --accent-soft: #fce8e5;
        }

        .page-hero {
            max-width: 1400px;
            margin: 1.2rem auto 0.5rem;
            padding: 0 15px;
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

        body {
            font-family: 'Nunito', sans-serif;
            background: #eef2f5;
            color: var(--ink);
            padding-bottom: 120px;
        }

        .contenedor-principal {
            max-width: 1400px;
            margin: 2rem auto;
            padding: 0 15px;
        }

        .select-container {
            width: 100%;
            max-width: 650px;
            margin: 0 auto 30px auto;
            background-color: white;
            padding: 20px;
            border-radius: 18px;
            box-shadow: 0 12px 24px rgba(31, 41, 51, 0.08);
            border: 1px solid rgba(216, 225, 234, 0.8);
        }

        /* Ajustes para que Select2 se vea bien con Bootstrap */
        .select2-container .select2-selection--single {
            height: 45px !important;
            padding: 8px 15px;
            border: 1px solid #ced4da;
            border-radius: 8px;
            font-size: 1.1rem;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 43px !important;
            right: 15px !important;
        }

        .anfitrion-card {
            background-color: white;
            border-radius: 20px;
            box-shadow: 0 18px 36px rgba(31, 41, 51, 0.12);
            margin-bottom: 2rem;
            overflow: hidden;
            transition: all 0.3s;
            width: 100%;
            max-width: 650px;
            margin: 0 auto;
            border: 1px solid var(--line);
        }

        .card-header-custom {
            background: linear-gradient(135deg, var(--accent-dark) 0%, var(--accent-mid) 55%, #df786c 100%);
            padding: 30px 20px;
            color: white;
            text-align: center;
            border-bottom: 5px solid var(--accent-dark);
        }

        .card-header-custom .icon-profile {
            font-size: 4rem;
            margin-bottom: 10px;
        }

        .card-body {
            padding: 30px;
        }

        .info-row {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
            gap: 15px;
            font-size: 1.1rem;
        }

        .info-icon {
            color: var(--accent-mid);
            width: 30px;
            text-align: center;
            font-size: 1.3rem;
        }

        .btn-actions {
            display: flex;
            gap: 10px;
            margin-top: 25px;
            flex-wrap: wrap;
        }

        .btn-action {
            flex: 1;
            border-radius: 10px;
            padding: 0.75rem 1rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.3s;
            color: white !important;
        }

        .btn-view-est {
            background-color: #17a2b8;
        }

        .btn-view-est:hover {
            background-color: #138496;
        }

        .btn-edit-data {
            background-color: #ffc107;
            color: #212529 !important;
        }

        .btn-edit-data:hover {
            background-color: #e0a800;
        }

        a,
        a:visited,
        a:active {
            text-decoration: none;
        }
    </style>
</head>

<body>

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
            <label for="select-anfitrion" class="form-label fw-bold mb-3 h5">
                <i class="fas fa-search me-2"></i>Buscar y seleccionar anfitrión:
            </label>
            <select id="select-anfitrion" class="form-select form-select-lg" <?php echo empty($anfitriones) ? 'disabled' : ''; ?>>
                <option value=""></option>
                <?php foreach ($anfitriones as $anf): ?>
                    <option value="<?php echo htmlspecialchars($anf['id']); ?>"
                        data-nombre="<?php echo htmlspecialchars($anf['name'] ?? 'Sin nombre'); ?>"
                        data-email="<?php echo htmlspecialchars($anf['email'] ?? 'Sin email'); ?>"
                        data-telefono="<?php echo htmlspecialchars($anf['phone'] ?? ''); ?>">
                        <?php echo htmlspecialchars($anf['name'] ?? 'Sin nombre'); ?> -
                        <?php echo htmlspecialchars($anf['email'] ?? ''); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div id="detalles-anfitrion" style="display: none;">
            <div class="anfitrion-card">
                <div class="card-header-custom">
                    <i class="fas fa-user-circle icon-profile"></i>
                    <h3 class="fw-bold m-0" id="card-nombre">Nombre Apellidos</h3>
                    <span class="badge bg-light text-dark mt-2">Perfil Anfitrión</span>
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
                            <i class="fas fa-building"></i> Ver Establecimientos
                        </a>
                        <button type="button" class="btn btn-action btn-edit-data" data-bs-toggle="modal"
                            data-bs-target="#modalEditarAnfitrion">
                            <i class="fas fa-user-edit"></i> Editar Datos
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalEditarAnfitrion" tabindex="-1" aria-labelledby="modalEditarAnfitrionLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="modalEditarAnfitrionLabel">
                        <i class="fas fa-edit me-2"></i>Editar Anfitrión
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="editar_anfitrion">
                        <input type="hidden" name="edit_id" id="form_edit_id">

                        <div class="mb-3">
                            <label for="form_edit_name" class="form-label fw-bold">Nombre</label>
                            <input type="text" class="form-control" id="form_edit_name" name="edit_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="form_edit_email" class="form-label fw-bold">Correo Electrónico</label>
                            <input type="email" class="form-control" id="form_edit_email" name="edit_email" required>
                        </div>
                        <div class="mb-3">
                            <label for="form_edit_phone" class="form-label fw-bold">Teléfono</label>
                            <input type="text" class="form-control" id="form_edit_phone" name="edit_phone">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            $('#select-anfitrion').select2({
                placeholder: "-- Busca o selecciona un anfitrión --",
                allowClear: true,
                width: '100%',
                language: {
                    noResults: function() {
                        return "No se encontró ningún anfitrión";
                    }
                }
            });

            $('#select-anfitrion').on('change', function() {
                var selectedOption = $(this).find('option:selected');
                var id = $(this).val();

                if (id) {
                    var nombre = selectedOption.data('nombre');
                    var email = selectedOption.data('email');
                    var telefono = selectedOption.data('telefono');

                    $('#card-nombre').text(nombre);
                    $('#card-email').text(email);
                    $('#card-telefono').text(telefono ? telefono : 'No registrado');
                    $('#card-id').text('#' + id);

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

    <?php include 'footerAdmin.php'; ?>
</body>

</html>