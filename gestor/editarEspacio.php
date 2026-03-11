<?php
session_start();
require_once 'verificar_sesion_gestor.php';
require '../vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

$espacio = null;
$mensaje = null;
$tipoMensaje = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $id = $_POST['id'];
    $urlUpdate = "http://" . $_ENV['SERVER_IP'] . ":" . $_ENV['DATABASE_PORT'] . "/rest/v1/space?id=eq." . $id;

    // Recoger los datos del formulario
    $dataToUpdate = json_encode([
        'name' => $_POST['name'],
        'description' => $_POST['description']
    ]);

    $chUpdate = curl_init($urlUpdate);
    curl_setopt_array($chUpdate, array(
        CURLOPT_CUSTOMREQUEST => "PATCH",
        CURLOPT_POSTFIELDS => $dataToUpdate,
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json',
            'apikey: ' . $_ENV['SERVICE_APIKEY'],
            'Authorization: Bearer ' . $_ENV['SERVICE_APIKEY']
        ),
        CURLOPT_RETURNTRANSFER => true,
    ));

    $resUpdate = curl_exec($chUpdate);
    $codUpdate = curl_getinfo($chUpdate, CURLINFO_HTTP_CODE);
    curl_close($chUpdate);

    if ($codUpdate >= 200 && $codUpdate < 300) {
        $mensaje = "El espacio ha sido actualizado correctamente.";
        $tipoMensaje = "success";
    } else {
        $mensaje = "Error al actualizar los datos. Código: " . $codUpdate;
        $tipoMensaje = "danger";
    }
}

if (isset($_GET['id']) || isset($_POST['id'])) {
    $idConsulta = $_GET['id'] ?? $_POST['id'];
    $urlGet = "http://" . $_ENV['SERVER_IP'] . ":" . $_ENV['DATABASE_PORT'] . "/rest/v1/space?id=eq." . $idConsulta . "&select=*,establecimiento(nombre)";

    $chGet = curl_init($urlGet);
    curl_setopt_array($chGet, array(
        CURLOPT_CUSTOMREQUEST => "GET",
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json',
            'apikey: ' . $_ENV['DATABASE_APIKEY']
        ),
        CURLOPT_RETURNTRANSFER => true,
    ));

    $resGet = curl_exec($chGet);
    $codGet = curl_getinfo($chGet, CURLINFO_HTTP_CODE);
    curl_close($chGet);

    if ($codGet === 200) {
        $datos = json_decode($resGet, true);
        if (!empty($datos)) {
            $espacio = $datos[0];
        }
    }
}

if (!$espacio) {
    die("<div style='font-family:sans-serif; text-align:center; margin-top:50px;'>
            <h3>Espacio no encontrado</h3>
            <p>No se ha proporcionado un ID válido o el espacio no existe.</p>
            <a href='verEspacios.php'>Volver a la lista</a>
         </div>");
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Espacio</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://kit.fontawesome.com/b8814a2854.js" crossorigin="anonymous"></script>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Nunito', sans-serif;
            background-color: #f8f9fa;
        }

        .header-custom {
            background-color: #00B7CF;
            color: white;
            padding: 25px 20px;
            text-align: center;
            border-radius: 10px 10px 0 0;
            position: relative;
        }

        .form-container {
            max-width: 600px;
            margin: 3rem auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }

        .form-body {
            padding: 30px;
        }

        .btn-guardar {
            background-color: #00B7CF;
            border: none;
            color: white;
            font-weight: bold;
        }

        .btn-guardar:hover {
            background-color: #0093a8;
            color: white;
        }

        .badge-est {
            background-color: rgba(255, 255, 255, 0.2);
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.9rem;
            display: inline-block;
            margin-top: 10px;
        }
    </style>
</head>

<body>

    <div class="container">
        <div class="form-container">
            <div class="header-custom">
                <h3 class="fw-bold m-0"><i class="fas fa-edit me-2"></i>Editar Espacio</h3>
                <div class="badge-est">
                    <i class="fas fa-building me-1"></i>
                    <?php echo htmlspecialchars($espacio['establecimiento']['nombre'] ?? 'Establecimiento general'); ?>
                </div>
            </div>

            <div class="form-body">
                <?php if ($mensaje): ?>
                    <div class="alert alert-<?php echo $tipoMensaje; ?> text-center shadow-sm">
                        <?php echo $mensaje; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="editarEspacio.php?id=<?php echo htmlspecialchars($espacio['id']); ?>">
                    <input type="hidden" name="id" value="<?php echo htmlspecialchars($espacio['id']); ?>">

                    <div class="mb-4">
                        <label class="form-label fw-bold text-secondary">Nombre del Espacio</label>
                        <input type="text" class="form-control form-control-lg" name="name"
                            value="<?php echo htmlspecialchars($espacio['name'] ?? ''); ?>" required>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold text-secondary">Descripción</label>
                        <textarea class="form-control" name="description" rows="4"
                            required><?php echo htmlspecialchars($espacio['description'] ?? ''); ?></textarea>
                    </div>

                    <div class="d-flex justify-content-between mt-5">
                        <a href="verEspacios.php" class="btn btn-secondary px-4">
                            <i class="fas fa-arrow-left me-2"></i>Volver
                        </a>
                        <button type="submit" class="btn btn-guardar px-4">
                            <i class="fas fa-save me-2"></i>Guardar Cambios
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</body>

</html>