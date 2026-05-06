<?php
session_start();
require_once 'verificar_sesion_host.php';
require '../vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

// 1. OBTENER DATOS ACTUALES DEL ANFITRIÓN PARA PRE-RELLENAR
$url = 'http://' . $_ENV['SERVER_IP'] . ':' . $_ENV['DATABASE_PORT'] . '/rest/v1/host?id=eq.' . $_SESSION['user_id'];
$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_CUSTOMREQUEST => "GET",
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $_SESSION['token'],
        'apikey: ' . $_ENV['DATABASE_APIKEY']
    ],
    CURLOPT_RETURNTRANSFER => true,
]);
$resultado = curl_exec($ch);
curl_close($ch);

$datosHost = json_decode($resultado, true);
$host = !empty($datosHost) ? $datosHost[0] : [];

// Mapeo directo con las columnas de tu base de datos
$nombrePila = $host['name'] ?? '';
$apellidos  = $host['apellidos'] ?? '';
$empresa    = $host['empresa'] ?? '';
$telefono   = $host['phone'] ?? '';
$nif        = $host['nif'] ?? '';
$fecha_nac  = $host['fecha_nac'] ?? '';
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://kit.fontawesome.com/b8814a2854.js" crossorigin="anonymous"></script>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="icon" href="../favicon-color.png">

    <title>Configurar Cobros - Tu Perfil</title>

    <style>
        :root {
            --primary: #00B7CF;
            --primary-hover: #0099ad;
            --bg-color: #f4f6f9;
            --text-main: #2c3e50;
        }

        body {
            font-family: 'Nunito', sans-serif;
            background-color: var(--bg-color);
            color: var(--text-main);
            padding-bottom: 50px;
        }

        .form-container {
            background: white;
            border-radius: 20px;
            padding: 35px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            max-width: 850px;
            margin: 40px auto;
        }

        .section-title {
            color: var(--primary);
            font-weight: 800;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e9ecef;
            font-size: 1.3rem;
        }

        .form-control {
            border-radius: 10px;
            padding: 10px 15px;
            border: 1px solid #ced4da;
            background-color: #f8f9fa;
        }

        .form-control:focus {
            background-color: #ffffff;
            border-color: var(--primary);
            box-shadow: 0 0 0 0.25rem rgba(0, 183, 207, 0.25);
        }

        .btn-submit {
            background-color: var(--primary);
            color: white;
            font-weight: 800;
            border-radius: 50px;
            padding: 12px 30px;
            border: none;
            transition: all 0.3s;
            width: 100%;
            margin-top: 20px;
            text-transform: uppercase;
        }

        .btn-submit:hover {
            background-color: var(--primary-hover);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 183, 207, 0.3);
        }

        .header-cobros {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 30px;
        }

        .btn-back {
            color: #6c757d;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s;
        }

        .btn-back:hover {
            color: var(--primary);
        }
    </style>
</head>

<body>

    <div class="container">
        <div class="form-container">

            <div class="header-cobros">
                <a href="inicio_anfitrion.php" class="btn-back"><i class="fas fa-arrow-left me-2"></i> Volver al perfil</a>
                <h3 class="fw-bold m-0"><i class="fas fa-university me-2 text-primary"></i> Datos de Facturación</h3>
            </div>

            <div class="alert alert-info border-0 rounded-3 mb-4">
                <i class="fas fa-info-circle me-2"></i> Por favor, revisa y completa tus datos para activar tu cuenta de cobros.
            </div>

            <div id="alertMessage" class="alert d-none rounded-3" role="alert"></div>

            <form id="formCobrosHost">

                <h4 class="section-title">Datos Personales y Fiscales</h4>
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label fw-bold text-muted small">NOMBRE DE PILA *</label>
                        <input type="text" class="form-control" name="nombre" required value="<?= htmlspecialchars($nombrePila) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold text-muted small">APELLIDOS *</label>
                        <input type="text" class="form-control" name="apellidos" required value="<?= htmlspecialchars($apellidos) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold text-muted small">EMPRESA / NOMBRE COMERCIAL</label>
                        <input type="text" class="form-control" name="empresa" value="<?= htmlspecialchars($empresa) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold text-muted small">TELÉFONO *</label>
                        <input type="tel" class="form-control" name="telefono" required value="<?= htmlspecialchars($telefono) ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold text-muted small">NIF / DNI *</label>
                        <input type="text" class="form-control" name="dni" required value="<?= htmlspecialchars($nif) ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold text-muted small">FECHA DE NACIMIENTO *</label>
                        <input type="date" class="form-control" name="fecha_nacimiento" required value="<?= htmlspecialchars($fecha_nac) ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold text-muted small">CADUCIDAD DEL NIF/DNI *</label>
                        <input type="date" class="form-control" name="caducidad_dni" required>
                    </div>
                </div>

                <h4 class="section-title">Dirección de Facturación</h4>
                <div class="row g-3 mb-4">
                    <div class="col-12">
                        <label class="form-label fw-bold text-muted small">DIRECCIÓN COMPLETA *</label>
                        <input type="text" class="form-control" name="direccion" required placeholder="Calle, número, piso...">
                    </div>
                    <div class="col-md-5">
                        <label class="form-label fw-bold text-muted small">LOCALIDAD *</label>
                        <input type="text" class="form-control" name="localidad" required placeholder="Ej: Málaga">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold text-muted small">PROVINCIA *</label>
                        <input type="text" class="form-control" name="provincia" required placeholder="Ej: Málaga">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold text-muted small">CÓDIGO POSTAL *</label>
                        <input type="text" class="form-control" name="codigo_postal" required placeholder="29001">
                    </div>
                </div>

                <h4 class="section-title">Cuenta Bancaria</h4>
                <div class="row g-3 mb-4">
                    <div class="col-md-8">
                        <label class="form-label fw-bold text-muted small">IBAN (DONDE RECIBIRÁS EL DINERO) *</label>
                        <input type="text" class="form-control" name="iban" required placeholder="ES00 0000 0000 0000 0000 0000">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold text-muted small">NOMBRE DEL BANCO *</label>
                        <input type="text" class="form-control" name="banco_nombre" required placeholder="Ej: BBVA, Unicaja...">
                    </div>
                </div>

                <button type="submit" class="btn-submit" id="btnGuardar">
                    <i class="fas fa-lock me-2"></i> Guardar y Activar Cobros
                </button>
            </form>
        </div>
    </div>

    <script>
        document.getElementById('formCobrosHost').addEventListener('submit', function(e) {
            e.preventDefault();

            const btn = document.getElementById('btnGuardar');
            const alertBox = document.getElementById('alertMessage');

            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Procesando datos...';
            alertBox.classList.add('d-none');

            const formData = new FormData(this);

            fetch('procesarAltaCobrosHost.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    alertBox.classList.remove('d-none', 'alert-success', 'alert-danger');
                    if (data.success) {
                        alertBox.classList.add('alert-success');
                        alertBox.innerHTML = '<i class="fas fa-check-circle me-2"></i> ' + data.message;
                        document.getElementById('formCobrosHost').style.display = 'none';
                        setTimeout(() => {
                            window.location.href = 'inicio_anfitrion.php';
                        }, 2500);
                    } else {
                        alertBox.classList.add('alert-danger');
                        alertBox.innerHTML = '<i class="fas fa-exclamation-triangle me-2"></i> ' + data.message;
                        btn.disabled = false;
                        btn.innerHTML = '<i class="fas fa-lock me-2"></i> Intentar de nuevo';
                    }
                })
                .catch(error => {
                    alertBox.classList.remove('d-none');
                    alertBox.classList.add('alert-danger');
                    alertBox.innerHTML = '<i class="fas fa-wifi me-2"></i> Error de conexión.';
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-lock me-2"></i> Intentar de nuevo';
                });
        });
    </script>
</body>

</html>