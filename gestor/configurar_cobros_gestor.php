<?php
session_start();
require_once 'verificar_sesion_gestor.php'; // Actívalo cuando lo tengas
require '../vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

// 1. OBTENER DATOS ACTUALES DE LA GESTORA
$url = 'http://' . $_ENV['SERVER_IP'] . ':' . $_ENV['DATABASE_PORT'] . '/rest/v1/gestor?id=eq.' . $_SESSION['user_id'];
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

$datosGestor = json_decode($resultado, true);
$gestor = !empty($datosGestor) ? $datosGestor[0] : [];

$empresa   = $gestor['empresa'] ?? '';
$cif       = $gestor['cif'] ?? '';
$telefono  = $gestor['phone'] ?? '';
$direccion = $gestor['direccion'] ?? '';
$cp        = $gestor['codigo_postal'] ?? '';
$localidad = $gestor['localidad'] ?? '';
$provincia = $gestor['provincia'] ?? '';
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://kit.fontawesome.com/b8814a2854.js" crossorigin="anonymous"></script>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <title>Configurar Cobros - Gestora</title>
    <!-- Usa los mismos estilos CSS que en el host -->
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

        .btn-submit {
            background-color: var(--primary);
            color: white;
            font-weight: 800;
            border-radius: 50px;
            padding: 12px 30px;
            border: none;
            width: 100%;
            margin-top: 20px;
            text-transform: uppercase;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="form-container">
            <h3 class="fw-bold mb-4"><i class="fas fa-building me-2 text-primary"></i> Datos de Facturación (Gestora)</h3>

            <div id="alertMessage" class="alert d-none rounded-3" role="alert"></div>

            <form id="formCobrosGestor">
                <h4 class="section-title">Datos de la Empresa</h4>
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label fw-bold text-muted small">RAZÓN SOCIAL / EMPRESA *</label>
                        <input type="text" class="form-control" name="empresa" required value="<?= htmlspecialchars($empresa) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold text-muted small">CIF *</label>
                        <input type="text" class="form-control" name="cif" required value="<?= htmlspecialchars($cif) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold text-muted small">TELÉFONO *</label>
                        <input type="tel" class="form-control" name="telefono" required value="<?= htmlspecialchars($telefono) ?>">
                    </div>
                </div>

                <h4 class="section-title">Datos del Representante Legal (Administrador)</h4>
                <div class="alert alert-secondary small border-0">
                    <i class="fas fa-shield-alt me-2"></i> Por normativas antiblanqueo, la pasarela de pagos requiere los datos del administrador de la empresa. Estos datos se envían cifrados y no se guardan en nuestro servidor.
                </div>
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label fw-bold text-muted small">NOMBRE *</label>
                        <input type="text" class="form-control" name="representante_nombre" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold text-muted small">APELLIDOS *</label>
                        <input type="text" class="form-control" name="representante_apellidos" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold text-muted small">DNI / NIE *</label>
                        <input type="text" class="form-control" name="representante_dni" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold text-muted small">FECHA NACIMIENTO *</label>
                        <input type="date" class="form-control" name="representante_nacimiento" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold text-muted small">CADUCIDAD DNI *</label>
                        <input type="date" class="form-control" name="representante_caducidad_dni" required>
                    </div>
                </div>

                <h4 class="section-title">Dirección Fiscal</h4>
                <div class="row g-3 mb-4">
                    <div class="col-12">
                        <label class="form-label fw-bold text-muted small">DIRECCIÓN COMPLETA *</label>
                        <input type="text" class="form-control" name="direccion" required value="<?= htmlspecialchars($direccion) ?>">
                    </div>
                    <div class="col-md-5">
                        <label class="form-label fw-bold text-muted small">LOCALIDAD *</label>
                        <input type="text" class="form-control" name="localidad" required value="<?= htmlspecialchars($localidad) ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold text-muted small">PROVINCIA *</label>
                        <input type="text" class="form-control" name="provincia" required value="<?= htmlspecialchars($provincia) ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold text-muted small">C.P. *</label>
                        <input type="text" class="form-control" name="codigo_postal" required value="<?= htmlspecialchars($cp) ?>">
                    </div>
                </div>

                <h4 class="section-title">Cuenta Bancaria de la Empresa</h4>
                <div class="row g-3 mb-4">
                    <div class="col-md-12">
                        <label class="form-label fw-bold text-muted small">IBAN (DONDE RECIBIRÁS LAS COMISIONES) *</label>
                        <input type="text" class="form-control" name="iban" required placeholder="ES00 0000 0000 0000 0000 0000">
                    </div>
                </div>

                <button type="submit" class="btn-submit" id="btnGuardar">
                    <i class="fas fa-lock me-2"></i> Activar Cobros para Gestora
                </button>
            </form>
        </div>
    </div>

    <script>
        document.getElementById('formCobrosGestor').addEventListener('submit', function(e) {
            e.preventDefault();
            const btn = document.getElementById('btnGuardar');
            const alertBox = document.getElementById('alertMessage');

            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Registrando Empresa...';
            alertBox.classList.add('d-none');

            fetch('procesarAltaCobrosGestor.php', {
                    method: 'POST',
                    body: new FormData(this)
                })
                .then(res => res.json())
                .then(data => {
                    alertBox.classList.remove('d-none', 'alert-success', 'alert-danger');
                    if (data.success) {
                        alertBox.classList.add('alert-success');
                        alertBox.innerHTML = '<i class="fas fa-check-circle me-2"></i> ' + data.message;
                        document.getElementById('formCobrosGestor').style.display = 'none';
                        setTimeout(() => {
                            window.location.href = 'inicio_gestor.php';
                        }, 2500);
                    } else {
                        alertBox.classList.add('alert-danger');
                        alertBox.innerHTML = '<i class="fas fa-exclamation-triangle me-2"></i> ' + data.message;
                        btn.disabled = false;
                        btn.innerHTML = '<i class="fas fa-lock me-2"></i> Intentar de nuevo';
                    }
                })
                .catch(err => {
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