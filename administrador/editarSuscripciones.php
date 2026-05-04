<?php
require_once 'verificar_sesion_admin.php';
require '../vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

$serverIp = $_ENV['SERVER_IP'];
$dbPort = $_ENV['DATABASE_PORT'];
$supabaseKey = $_ENV['DATABASE_APIKEY'];
$serviceKey = $_ENV['SERVICE_APIKEY']; // Clave con permisos de Admin para editar

$flashMessage = '';
$flashType = '';

// Procesar el formulario cuando el admin edite un plan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_plan') {
    $id = $_POST['plan_id'] ?? '';
    $nombre = trim($_POST['nombre'] ?? ''); // Recogemos el nombre editable
    $descripcion = trim($_POST['descripcion'] ?? '');
    $precio_mensual = floatval($_POST['precio_mensual'] ?? 0);
    $precio_anual = floatval($_POST['precio_anual'] ?? 0);

    if (!empty($id)) {
        $payload = [
            'nombre' => $nombre,
            'descripcion' => $descripcion,
            'precio_mensual' => $precio_mensual,
            'precio_anual' => $precio_anual
        ];

        $urlUpdate = 'http://' . $serverIp . ':' . $dbPort . '/rest/v1/planes_suscripcion?id=eq.' . rawurlencode($id);
        $chUpdate = curl_init($urlUpdate);
        curl_setopt_array($chUpdate, [
            CURLOPT_CUSTOMREQUEST => 'PATCH',
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'apikey: ' . $serviceKey,
                'Authorization: Bearer ' . $serviceKey,
                'Prefer: return=representation'
            ],
        ]);

        $responseUpdate = curl_exec($chUpdate);
        $httpCodeUpdate = curl_getinfo($chUpdate, CURLINFO_HTTP_CODE);
        curl_close($chUpdate);

        if ($httpCodeUpdate >= 200 && $httpCodeUpdate < 300) {
            $flashMessage = 'El plan de suscripción se ha actualizado correctamente.';
            $flashType = 'success';
        } else {
            $errorData = json_decode($responseUpdate, true);
            $flashMessage = 'Error al actualizar el plan: ' . htmlspecialchars($errorData['message'] ?? 'Error desconocido');
            $flashType = 'danger';
        }
    }
}

// Obtener todos los planes para mostrarlos
$urlPlanes = "http://" . $serverIp . ":" . $dbPort . "/rest/v1/planes_suscripcion?order=precio_mensual.asc";
$chPlanes = curl_init($urlPlanes);
curl_setopt_array($chPlanes, array(
    CURLOPT_CUSTOMREQUEST => "GET",
    CURLOPT_HTTPHEADER => array(
        'Content-Type: application/json',
        'apikey: ' . $supabaseKey
    ),
    CURLOPT_RETURNTRANSFER => true,
));
$resultadoPlanes = curl_exec($chPlanes);
curl_close($chPlanes);
$planesObtenidos = json_decode($resultadoPlanes, true);

$planesAnfitrion = [];
$planesGestor = [];

if (is_array($planesObtenidos) && !isset($planesObtenidos['error'])) {
    foreach ($planesObtenidos as $plan) {
        if ($plan['tipo_usuario'] === 'host') {
            $planesAnfitrion[] = $plan;
        } elseif ($plan['tipo_usuario'] === 'gestor') {
            $planesGestor[] = $plan;
        }
    }
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
    <title>Suscripciones Globales - Admin</title>
    <style>
        :root {
            --brand-ink: #1f2933;
            --brand-deep: #0f4c5c;
            --brand-accent: #dc3545;
            /* Rojo admin */
            --brand-soft: #f3f5f7;
            --card-radius: 16px;
            --primary-color: #dc3545;
            --bg: #f4f7fb;
            --accent-dark: #8c1c13;
            --accent-mid: #c44536;
        }

        body {
            font-family: 'Nunito', sans-serif;
            background: #eef2f5;
            padding-bottom: 120px;
            color: var(--brand-ink);
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

        .plan-card {
            background-color: white;
            border-radius: var(--card-radius);
            box-shadow: 0 10px 25px rgba(31, 41, 51, 0.05);
            border: 1px solid rgba(15, 76, 92, 0.08);
            transition: all 0.3s ease;
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .plan-card:hover {
            box-shadow: 0 18px 36px rgba(31, 41, 51, 0.12);
            transform: translateY(-3px);
        }

        .card-header-plan {
            background-color: #f8f9fa;
            border-bottom: 2px solid #e9ecef;
            padding: 1.5rem;
            border-top-left-radius: var(--card-radius);
            border-top-right-radius: var(--card-radius);
            text-align: center;
        }

        .plan-name {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--brand-ink);
            text-transform: uppercase;
        }

        .plan-price-large {
            font-size: 2.2rem;
            font-weight: 800;
            color: var(--primary-color);
            margin: 10px 0;
        }

        .plan-price-large small {
            font-size: 1.2rem;
            color: #6c757d;
            font-weight: 600;
        }

        .plan-body {
            padding: 1.5rem;
            flex-grow: 1;
        }

        .plan-desc {
            color: #5f6d79;
            font-size: 0.95rem;
            margin-bottom: 1.5rem;
            min-height: 40px;
        }

        .btn-edit {
            background-color: var(--primary-color);
            border: none;
            color: white;
            border-radius: 8px;
            padding: 0.6rem;
            font-weight: 600;
            width: 100%;
            transition: all 0.2s ease;
        }

        .btn-edit:hover {
            background-color: #b02a37;
            color: white;
            transform: translateY(-2px);
        }

        .section-header {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--brand-ink);
            margin: 2rem 0 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid var(--primary-color);
        }
    </style>
</head>

<body>
    <section class="page-hero">
        <div class="page-hero-inner">
            <div class="hero-title-row">
                <div class="page-hero-title"><i class="fas fa-tags me-2"></i>Gestión de Precios y Suscripciones</div>
            </div>
        </div>
    </section>

    <div class="container mt-3" style="max-width: 1400px;">
        <?php if (!empty($flashMessage)): ?>
            <div class="alert alert-<?php echo $flashType; ?> alert-dismissible fade show" role="alert">
                <?php echo $flashMessage; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <h3 class="section-header"><i class="fas fa-home text-danger"></i> Planes para Anfitriones</h3>
        <div class="row g-4">
            <?php foreach ($planesAnfitrion as $plan): ?>
                <div class="col-md-6 col-xl-4">
                    <div class="plan-card">
                        <div class="card-header-plan">
                            <div class="plan-name"><?php echo htmlspecialchars($plan['nombre']); ?></div>
                            <div class="plan-price-large">
                                €<?php echo number_format($plan['precio_mensual'], 2); ?> <small>/ mes</small>
                            </div>
                            <?php if ($plan['precio_anual'] > 0 || $plan['precio_mensual'] > 0): ?>
                                <div class="badge bg-success" style="font-size: 0.9rem;">
                                    Anual: €<?php echo number_format($plan['precio_anual'], 2); ?>
                                </div>
                            <?php elseif ($plan['es_infinito']): ?>
                                <div class="badge bg-secondary" style="font-size: 0.9rem;">Plan Ilimitado (Gratis)</div>
                            <?php endif; ?>
                        </div>
                        <div class="plan-body d-flex flex-column">
                            <div class="plan-desc">
                                <strong>Descripción:</strong><br>
                                <?php echo htmlspecialchars($plan['descripcion']); ?>
                            </div>
                            <button class="btn btn-edit mt-auto" onclick='abrirModalEditar(<?php echo json_encode($plan, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'>
                                <i class="fas fa-edit me-2"></i>Editar Precios y Textos
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <h3 class="section-header mt-5"><i class="fas fa-user-tie text-danger"></i> Planes para Gestoras</h3>
        <div class="row g-4">
            <?php foreach ($planesGestor as $plan): ?>
                <div class="col-md-6 col-xl-4">
                    <div class="plan-card">
                        <div class="card-header-plan">
                            <div class="plan-name"><?php echo htmlspecialchars($plan['nombre']); ?></div>
                            <div class="plan-price-large">
                                €<?php echo number_format($plan['precio_mensual'], 0, ',', '.'); ?> <small>/ mes</small>
                            </div>
                            <?php if ($plan['precio_anual'] > 0): ?>
                                <div class="badge bg-success" style="font-size: 0.9rem;">
                                    Anual: €<?php echo number_format($plan['precio_anual'], 0, ',', '.'); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="plan-body d-flex flex-column">
                            <div class="plan-desc">
                                <strong>Descripción:</strong><br>
                                <?php echo htmlspecialchars($plan['descripcion']); ?>
                            </div>
                            <button class="btn btn-edit mt-auto" onclick='abrirModalEditar(<?php echo json_encode($plan, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'>
                                <i class="fas fa-edit me-2"></i>Editar Precios y Textos
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius: 15px;">
                <form method="POST" action="editarSuscripciones.php">
                    <div class="modal-header bg-danger text-white" style="border-top-left-radius: 15px; border-top-right-radius: 15px;">
                        <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Editar Plan</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        <input type="hidden" name="action" value="update_plan">
                        <input type="hidden" id="edit-id" name="plan_id">

                        <div class="mb-3">
                            <label class="form-label fw-bold text-muted">Nombre del Plan <span id="etiqueta-tipo"></span></label>
                            <input type="text" class="form-control" id="edit-nombre" name="nombre" required>
                            <small class="text-warning fw-bold"><i class="fas fa-exclamation-triangle"></i> Cuidado: Si cambias este nombre, asegúrate de actualizarlo en el código fuente (Suscripciones.php).</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Descripción del Plan</label>
                            <textarea class="form-control" id="edit-descripcion" name="descripcion" rows="3" required></textarea>
                            <small class="text-muted">Este texto aparecerá en las tarjetas de pago.</small>
                        </div>

                        <div class="row g-3">
                            <div class="col-6">
                                <label class="form-label fw-bold text-success">Precio Mensual (€)</label>
                                <input type="number" step="0.01" class="form-control border-success" id="edit-precio-mensual" name="precio_mensual" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label fw-bold text-success">Precio Anual (€)</label>
                                <input type="number" step="0.01" class="form-control border-success" id="edit-precio-anual" name="precio_anual" required>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pb-4 justify-content-center">
                        <button type="button" class="btn btn-secondary px-4 rounded-pill" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-danger px-4 rounded-pill">Guardar Cambios</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php include 'footerAdmin.php'; ?>

    <script>
        function abrirModalEditar(plan) {
            // Rellenar datos ocultos e informativos
            document.getElementById('edit-id').value = plan.id;

            // Etiqueta visual para distinguir si es de Gestor o Anfitrión
            let tipoVisual = plan.tipo_usuario === 'host' ? '(Anfitrión)' : '(Gestor)';
            document.getElementById('etiqueta-tipo').innerText = tipoVisual;

            // Textos y precios a editar
            document.getElementById('edit-nombre').value = plan.nombre; // Editable
            document.getElementById('edit-descripcion').value = plan.descripcion || '';
            document.getElementById('edit-precio-mensual').value = plan.precio_mensual || 0;
            document.getElementById('edit-precio-anual').value = plan.precio_anual || 0;

            // Al asegurarnos de que la opción sea siempre editable, habilitamos siempre los inputs:
            document.getElementById('edit-precio-mensual').readOnly = false;
            document.getElementById('edit-precio-anual').readOnly = false;

            new bootstrap.Modal(document.getElementById('editModal')).show();
        }
    </script>
</body>

</html>