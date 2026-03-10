<?php
require_once 'verificar_sesion_gestor.php';

require '../vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: Anfitriones.php");
    exit;
}

$id_anfitrion = $_GET['id'];
$error_msg = null;
$success_msg = null;


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'name' => trim($_POST['nombre'] ?? ''),
        'email' => trim($_POST['email'] ?? ''),
        'phone' => trim($_POST['telefono'] ?? ''),
        'empresa' => trim($_POST['empresa'] ?? ''),
        'nif' => trim($_POST['nif'] ?? '')
    ];

    $url_patch = "http://" . $_ENV['SERVER_IP'] . ":" . $_ENV['DATABASE_PORT'] . "/rest/v1/host?id=eq." . urlencode($id_anfitrion);

    $ch_patch = curl_init($url_patch);
    curl_setopt($ch_patch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch_patch, CURLOPT_CUSTOMREQUEST, 'PATCH');
    curl_setopt($ch_patch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch_patch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'Authorization: Bearer ' . $_ENV['SERVICE_APIKEY'],
        'apikey: ' . $_ENV['SERVICE_APIKEY'],
        'Prefer: return=representation'
    ));

    $resultado_patch = curl_exec($ch_patch);
    $codigoPatch = curl_getinfo($ch_patch, CURLINFO_HTTP_CODE);
    curl_close($ch_patch);

    $datos_modificados = json_decode($resultado_patch, true);

    if ($codigoPatch >= 200 && $codigoPatch < 300 && is_array($datos_modificados) && count($datos_modificados) > 0) {
        $success_msg = "Los datos del anfitrión se han actualizado correctamente.";
    } else {
        $error_msg = "Error al guardar. Verifica los permisos de RLS o si el usuario existe.";
    }
}

$host_data = null;
$url_get = "http://" . $_ENV['SERVER_IP'] . ":" . $_ENV['DATABASE_PORT'] . "/rest/v1/host?id=eq." . urlencode($id_anfitrion) . "&select=*";

$ch_get = curl_init($url_get);
curl_setopt_array($ch_get, array(
    CURLOPT_CUSTOMREQUEST => "GET",
    CURLOPT_HTTPHEADER => array(
        'Content-Type: application/json',
        'Authorization: Bearer ' . $_ENV['SERVICE_APIKEY'],
        'apikey: ' . $_ENV['SERVICE_APIKEY']
    ),
    CURLOPT_RETURNTRANSFER => true,
));

$resultado_get = curl_exec($ch_get);
$codigoGet = curl_getinfo($ch_get, CURLINFO_HTTP_CODE);
curl_close($ch_get);

if ($codigoGet === 200) {
    $datos = json_decode($resultado_get, true);
    if (is_array($datos) && count($datos) > 0) {
        $host_data = $datos[0];
    } else {
        $error_msg = "Anfitrión no encontrado.";
    }
} else {
    $error_msg = "Error al cargar los datos. (Código: $codigoGet)";
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
    <link rel="icon" href="../favicon-color.png">
    <link rel="icon" href="../favicon-negro.png" media="(prefers-color-scheme: light)">
    <link rel="icon" href="../favicon-color.png" media="(prefers-color-scheme: dark)">
    <title>Editar Anfitrión</title>
    <style>
        body {
            font-family: 'Nunito', sans-serif;
            background-color: #f8f9fa;
            padding-bottom: 15%;
        }

        .contenedor-principal {
            max-width: 800px;
            margin: 2rem auto;
            padding: 0 15px;
        }

        .form-card {
            background-color: white;
            border-radius: 15px;
            box-shadow: 0 .5rem 1rem rgba(0, 0, 0, .10);
            padding: 30px;
        }

        .header-edit {
            border-bottom: 2px solid #00B7CF;
            padding-bottom: 15px;
            margin-bottom: 25px;
            color: #00B7CF;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .btn-volver {
            background-color: #6c757d;
            color: white;
            font-weight: bold;
            border-radius: 10px;
        }

        .btn-volver:hover {
            background-color: #5a6268;
            color: white;
        }

        .btn-guardar {
            background-color: #00B7CF;
            color: white;
            font-weight: bold;
            border-radius: 10px;
        }

        .btn-guardar:hover {
            background-color: #0098ab;
            color: white;
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
                    Edición de Perfil
                </div>
            </div>
        </div>
    </header>

    <div class="contenedor-principal">
        <?php if ($error_msg): ?>
            <div class="alert alert-danger text-center shadow-sm">
                <i class="fas fa-exclamation-triangle me-2"></i> <?php echo htmlspecialchars($error_msg); ?>
            </div>
        <?php endif; ?>

        <?php if ($success_msg): ?>
            <div class="alert alert-success text-center shadow-sm">
                <i class="fas fa-check-circle me-2"></i> <?php echo htmlspecialchars($success_msg); ?>
            </div>
        <?php endif; ?>

        <div class="form-card">
            <div class="header-edit">
                <i class="fas fa-user-edit h2 m-0"></i>
                <h3 class="m-0 fw-bold">Detalles del Anfitrión #<?php echo htmlspecialchars($id_anfitrion); ?></h3>
            </div>

            <?php if ($host_data): ?>
                <form action="editarAnfitrion.php?id=<?php echo urlencode($id_anfitrion); ?>" method="POST">

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold"><i class="fas fa-user me-2"></i>Nombre / Apellidos</label>
                            <input type="text" class="form-control" name="nombre" value="<?php echo htmlspecialchars($host_data['name'] ?? ''); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold"><i class="fas fa-envelope me-2"></i>Correo Electrónico</label>
                            <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($host_data['email'] ?? ''); ?>" required>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-4">
                            <label class="form-label fw-bold"><i class="fas fa-phone me-2"></i>Teléfono</label>
                            <input type="text" class="form-control" name="telefono" value="<?php echo htmlspecialchars($host_data['phone'] ?? ''); ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold"><i class="fas fa-building me-2"></i>Empresa</label>
                            <input type="text" class="form-control" name="empresa" value="<?php echo htmlspecialchars($host_data['empresa'] ?? ''); ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold"><i class="fas fa-id-card me-2"></i>NIF/CIF</label>
                            <input type="text" class="form-control" name="nif" value="<?php echo htmlspecialchars($host_data['nif'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="d-flex justify-content-between mt-4 pt-3 border-top">
                        <a href="Anfitriones.php" class="btn btn-volver px-4 py-2">
                            <i class="fas fa-arrow-left me-2"></i> Volver a la lista
                        </a>
                        <button type="submit" class="btn btn-guardar px-4 py-2">
                            <i class="fas fa-save me-2"></i> Guardar Cambios
                        </button>
                    </div>
                </form>
            <?php else: ?>
                <div class="text-center mt-4">
                    <p class="text-muted">No hay datos disponibles para editar.</p>
                    <a href="Anfitriones.php" class="btn btn-volver mt-3 px-4">Volver</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="container-fluid footer p-3">
        <div class="row text-center fixed-bottom bg-blanco pt-1 px-2 footer-container">
            <label for="anf" id="lbl_anf" class="col-2 text-center footer-item">
                <div class="row"><a href="Anfitriones.php">
                        <div class="col-12 icon-container"><i class="h2 fas fa-users p-1 m-0"></i>
                            <div>Anfitriones</div>
                        </div>
                    </a></div>
            </label>
            <label for="val" id="lbl_val" class="col-2 text-center footer-item">
                <div class="row"><a href="verValidar.php">
                        <div class="col-12 icon-container"><i class="h2 fas fa-check-circle p-1 m-0"></i>
                            <div>Validar</div>
                        </div>
                    </a></div>
            </label>
            <label for="res" id="lbl_res" class="col-2 text-center footer-item">
                <div class="row"><a href="verReservas.php">
                        <div class="col-12 icon-container"><i class="h2 fas fa-book-open p-1 m-0"></i>
                            <div>Reservas</div>
                        </div>
                    </a></div>
            </label>
            <label for="his" id="lbl_his" class="col-2 text-center footer-item">
                <div class="row"><a href="verEstablecimientos.php">
                        <div class="col-12 icon-container"><i class="h2 fas fa-building p-1 m-0"></i>
                            <div>Establecimientos</div>
                        </div>
                    </a></div>
            </label>
            <label for="esp" id="lbl_esp" class="col-2 text-center footer-item">
                <div class="row"><a href="verEspacios.php">
                        <div class="col-12 icon-container"><i class="h2 fas fa-chair p-1 m-0"></i>
                            <div>Espacios</div>
                        </div>
                    </a></div>
            </label>
            <label for="per" id="lbl_per" class="col-2 text-center footer-item">
                <div class="row"><a href="tuPerfil.php">
                        <div class="col-12 icon-container"><i class="h2 fas fa-user-tie p-1 m-0"></i>
                            <div>Perfil</div>
                        </div>
                    </a></div>
            </label>
        </div>
    </div>
</body>

</html>