<?php
require_once 'verificar_sesion_gestor.php';

require '../vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

$anfitriones = [];
$error_db = null;

$url = "http://" . $_ENV['SERVER_IP'] . ":" . $_ENV['DATABASE_PORT'] . "/rest/v1/host?select=id,name,email,phone,empresa&order=name.asc";
$ch = curl_init($url);
curl_setopt_array($ch, array(
    CURLOPT_CUSTOMREQUEST => "GET",
    CURLOPT_HTTPHEADER => array(
        'Content-Type: application/json',
        'apikey: ' . $_ENV['DATABASE_APIKEY'],
    ),
    CURLOPT_RETURNTRANSFER => true,
));

$resultado = curl_exec($ch);
$codigoRespuesta = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($codigoRespuesta === 200) {
    $datos = json_decode($resultado, true);
    if (is_array($datos)) {
        $anfitriones = $datos;
    }
} else {
    $error_db = "Error al conectar con la API de la base de datos (Código: $codigoRespuesta).";
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
    <link rel="icon" href="../favicon-color.png">
    <link rel="icon" href="../favicon-negro.png" media="(prefers-color-scheme: light)">
    <link rel="icon" href="../favicon-color.png" media="(prefers-color-scheme: dark)">
    <title>Gestión de Anfitriones</title>
    <style>
        body {
            font-family: 'Nunito', sans-serif;
            background-color: #f8f9fa;
            padding-bottom: 15%;
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
            border-radius: 15px;
            box-shadow: 0 .5rem 1rem rgba(0, 0, 0, .10);
        }

        .anfitrion-card {
            background-color: white;
            border-radius: 15px;
            box-shadow: 0 .5rem 1rem rgba(0, 0, 0, .15);
            margin-bottom: 2rem;
            overflow: hidden;
            transition: all 0.3s;
            width: 100%;
            max-width: 650px;
            margin: 0 auto;
        }

        .card-header-custom {
            background: linear-gradient(135deg, #00B7CF, #007bff);
            padding: 30px 20px;
            color: white;
            text-align: center;
            border-bottom: 5px solid #0056b3;
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
            color: #00B7CF;
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

        .btn-edit {
            background-color: #17a2b8;
        }

        .btn-edit:hover {
            background-color: #138496;
        }

        .btn-delete {
            background-color: #dc3545;
        }

        .btn-delete:hover {
            background-color: #c82333;
        }

        .modal-confirm .modal-content {
            border-radius: 15px;
        }

        .modal-confirm .icon-box {
            width: 80px;
            height: 80px;
            margin: 0 auto;
            border-radius: 50%;
            z-index: 9;
            text-align: center;
            border: 3px solid #f15e5e;
        }

        .modal-confirm .icon-box i {
            color: #f15e5e;
            font-size: 46px;
            display: inline-block;
            margin-top: 13px;
        }

        .toast-container {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 1050;
        }

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
        }

        .icon-container {
            transition: transform 0.3s ease;
            padding: 5px 0;
        }

        .footer-item:hover .icon-container {
            transform: translateY(-7px);
        }

        a,
        a:visited,
        a:active {
            color: black;
            text-decoration: none;
        }

        #lbl_anf {
            color: #00B7CF !important;
        }

        #lbl_anf .icon-container {
            color: #007bff;
        }

        .footer-item:hover {
            color: #00B7CF !important;
        }

        .footer-item:hover .icon-container {
            color: #007bff;
        }
    </style>
</head>

<body>
    <header>
        <div class="container-fluid info text-center">
            <div class="row">
                <div class="col color-white h2 fw-bold pt-3 pb-2">
                    Anfitriones
                </div>
            </div>
        </div>
    </header>

    <div class="contenedor-principal">
        <?php if (isset($error_db)): ?>
            <div class="alert alert-danger text-center">
                <?php echo $error_db; ?>
            </div>
        <?php endif; ?>

        <div class="select-container">
            <label for="select-anfitrion" class="form-label fw-bold mb-3 h5">
                <i class="fas fa-search me-2"></i>Buscar y seleccionar anfitrión:
            </label>
            <select id="select-anfitrion" class="form-select form-select-lg">
                <option value="">-- Selecciona un anfitrión de la lista --</option>
                <?php foreach ($anfitriones as $anf): ?>
                    <option value="<?php echo htmlspecialchars($anf['id']); ?>"
                        data-nombre="<?php echo htmlspecialchars($anf['name'] ?? 'Sin nombre'); ?>"
                        data-email="<?php echo htmlspecialchars($anf['email'] ?? 'Sin email'); ?>"
                        data-telefono="<?php echo htmlspecialchars($anf['phone'] ?? 'No registrado'); ?>">
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
                        <a href="#" id="btn-edit-anfitrion" class="btn btn-action btn-edit">
                            <i class="fas fa-user-edit"></i> Editar Perfil
                        </a>

                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade modal-confirm" id="confirmModal" tabindex="-1" aria-labelledby="confirmModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center pt-0">
                    <div class="icon-box mb-3">
                        <i class="fas fa-user-slash"></i>
                    </div>
                    <h5 class="modal-title mb-2 fw-bold" id="confirmModalLabel">Confirmar Suspensión</h5>
                    <p class="text-muted mb-0">¿Estás seguro de que deseas suspender al anfitrión <strong
                            id="anfitrionNombreModal"></strong>?</p>
                </div>
                <div class="modal-footer border-0 d-flex justify-content-center">
                    <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-danger px-4" id="btnConfirmarSuspender">Suspender
                        Anfitrión</button>
                </div>
            </div>
        </div>
    </div>

    <div class="toast-container">
        <div class="toast align-items-center text-white bg-success border-0" role="alert" aria-live="assertive"
            aria-atomic="true" id="toastExito">
            <div class="d-flex">
                <div class="toast-body">
                    <i class="fas fa-check-circle me-2"></i> Anfitrión suspendido correctamente.
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"
                    aria-label="Close"></button>
            </div>
        </div>
        <div class="toast align-items-center text-white bg-danger border-0" role="alert" aria-live="assertive"
            aria-atomic="true" id="toastError">
            <div class="d-flex">
                <div class="toast-body">
                    <i class="fas fa-exclamation-circle me-2"></i> Error al intentar suspender al anfitrión.
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"
                    aria-label="Close"></button>
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            let anfitrionIdActual = null;
            let anfitrionNombreActual = "";

            $('#select-anfitrion').change(function() {
                var selectedOption = $(this).find('option:selected');
                var id = $(this).val();

                if (id) {
                    anfitrionIdActual = id;
                    anfitrionNombreActual = selectedOption.data('nombre');
                    var email = selectedOption.data('email');
                    var telefono = selectedOption.data('telefono');

                    // Rellenar la tarjeta
                    $('#card-nombre').text(anfitrionNombreActual);
                    $('#card-email').text(email);
                    $('#card-telefono').text(telefono);
                    $('#card-id').text('#' + id);

                    // Enlace de edición real (si lo creas luego)
                    $('#btn-edit-anfitrion').attr('href', 'editarAnfitrion.php?id=' + id);

                    $('#detalles-anfitrion').fadeIn();
                } else {
                    anfitrionIdActual = null;
                    $('#detalles-anfitrion').fadeOut();
                }
            });
        });
    </script>

    <div class="container-fluid footer p-3">
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