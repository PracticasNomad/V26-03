<?php
require_once 'verificar_sesion_gestor.php';

require '../vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

$anfitriones = [];
$error_db = null;
$gestorId = $_SESSION["user_id"];

// 1. OBTENER EL CÓDIGO POSTAL DEL GESTOR
$urlGestor = "http://" . $_ENV['SERVER_IP'] . ":" . $_ENV['DATABASE_PORT'] . "/rest/v1/gestor?select=codigo_postal&id=eq." . $gestorId;
$chGestor = curl_init($urlGestor);
curl_setopt_array($chGestor, [
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $_ENV['SERVICE_APIKEY'],
        'apikey: ' . $_ENV['SERVICE_APIKEY']
    ],
    CURLOPT_RETURNTRANSFER => true
]);
$resGestor = curl_exec($chGestor);
curl_close($chGestor);

$datosGestor = json_decode($resGestor, true);
$cpGestor = $datosGestor[0]['codigo_postal'] ?? null;

if ($cpGestor) {
    // 2. BUSCAR ESTABLECIMIENTOS DE ESE CP Y EXTRAER SUS ANFITRIONES (Añadido avatar_url)
    $urlEstablecimientos = "http://" . $_ENV['SERVER_IP'] . ":" . $_ENV['DATABASE_PORT'] . "/rest/v1/establecimiento?select=host_id,host(id,name,email,phone,empresa,avatar_url)&codigo_postal=eq." . urlencode($cpGestor);

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

        // Extraemos los anfitriones y eliminamos duplicados
        $anfitrionesUnicos = [];
        foreach ($establecimientos as $est) {
            if (isset($est['host']) && $est['host']) {
                $hostId = $est['host']['id'];
                if (!isset($anfitrionesUnicos[$hostId])) {
                    $anfitrionesUnicos[$hostId] = $est['host'];
                }
            }
        }

        // Convertimos el array asociativo a uno indexado y ordenamos
        $anfitriones = array_values($anfitrionesUnicos);
        usort($anfitriones, function ($a, $b) {
            return strcmp($a['name'] ?? '', $b['name'] ?? '');
        });
    } else {
        $error_db = "Error al obtener los datos de la zona (Código: $codigoRespuesta).";
    }
} else {
    $error_db = "Tu perfil de gestor no tiene un código postal asignado. Actualiza tu perfil primero.";
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
    
    <script>
        const MINIO_URL = "<?php echo rtrim($_ENV['MINIO_PUBLIC_URL'] ?? 'https://79.150.19.209:9000', '/'); ?>";
    </script>

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

        /* Nueve estilo para la imagen de perfil redonda en lugar del icono */
        .card-header-custom .img-profile {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid white;
            margin-bottom: 10px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.2);
            background-color: white;
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
                    Anfitriones de tu zona
                </div>
            </div>
        </div>
    </header>

    <div class="contenedor-principal">
        <?php if (isset($error_db)): ?>
            <div class="alert alert-danger text-center shadow-sm rounded-pill mb-4">
                <i class="fas fa-exclamation-triangle me-2"></i> <?php echo $error_db; ?>
            </div>
        <?php endif; ?>

        <?php if (!isset($error_db) && empty($anfitriones)): ?>
            <div class="alert alert-info text-center shadow-sm rounded-pill mb-4">
                <i class="fas fa-info-circle me-2"></i> No hay establecimientos registrados en tu código postal
                (<?php echo htmlspecialchars($cpGestor); ?>).
            </div>
        <?php endif; ?>

        <div class="select-container">
            <label for="select-anfitrion" class="form-label fw-bold mb-3 h5">
                <i class="fas fa-search me-2"></i>Buscar y seleccionar anfitrión:
            </label>
            <select id="select-anfitrion" class="form-select form-select-lg" <?php echo empty($anfitriones) ? 'disabled' : ''; ?>>
                <option value="">-- Selecciona un anfitrión de la lista --</option>
                <?php foreach ($anfitriones as $anf): ?>
                    <option value="<?php echo htmlspecialchars($anf['id']); ?>"
                        data-nombre="<?php echo htmlspecialchars($anf['name'] ?? 'Sin nombre'); ?>"
                        data-email="<?php echo htmlspecialchars($anf['email'] ?? 'Sin email'); ?>"
                        data-telefono="<?php echo htmlspecialchars($anf['phone'] ?? 'No registrado'); ?>"
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
                            <i class="fas fa-user-edit"></i> Ver Establecimientos
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>

    <script>
        $(document).ready(function () {
            let anfitrionIdActual = null;
            let anfitrionNombreActual = "";

            $('#select-anfitrion').change(function () {
                var selectedOption = $(this).find('option:selected');
                var id = $(this).val();

                if (id) {
                    anfitrionIdActual = id;
                    anfitrionNombreActual = selectedOption.data('nombre');
                    var email = selectedOption.data('email');
                    var telefono = selectedOption.data('telefono');
                    var avatarRaw = selectedOption.data('avatar'); // Leemos el avatar

                    // Rellenar la tarjeta
                    $('#card-nombre').text(anfitrionNombreActual);
                    $('#card-email').text(email);
                    $('#card-telefono').text(telefono);
                    $('#card-id').text('#' + id);

                    // PROCESAR IMAGEN MINIO
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

                    // Redirigir a los establecimientos de ESE anfitrión
                    $('#btn-edit-anfitrion').attr('href', 'verEstablecimientos.php?host_id=' + id);

                    $('#detalles-anfitrion').fadeIn();
                } else {
                    anfitrionIdActual = null;
                    $('#detalles-anfitrion').fadeOut();
                }
            });
        });
    </script>
</body>

</html>