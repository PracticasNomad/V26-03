<?php
require_once 'verificar_sesion_admin.php';

// Verificamos que el usuario está logueado y es administrador
if (!isset($_SESSION['user_id']) || !isset($_SESSION['token']) || $_SESSION['rol'] !== 'administrador') {
    header('Location: inicio_sesion_admin.php');
    exit();
}

require '../vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

// 1. CARGAMOS LOS DATOS DEL ADMIN DESDE SUPABASE
$urlAdmin = "http://" . $_ENV['SERVER_IP'] . ":" . $_ENV['DATABASE_PORT'] . "/rest/v1/admin?id=eq." . $_SESSION["user_id"];
$ch = curl_init($urlAdmin);
curl_setopt_array($ch, [
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $_ENV['SERVICE_APIKEY'],
        'apikey: ' . $_ENV['SERVICE_APIKEY']
    ],
    CURLOPT_RETURNTRANSFER => true
]);
$resultado = curl_exec($ch);
curl_close($ch);

$datosAdmin = json_decode($resultado, true);
$informacionLegal = <<<HTML
<div class="legal-rich-content">
    <h6>Aviso Legal</h6>
    <p>
        En cumplimiento del articulo 10 de la Ley 34/2002, de 11 de julio, de Servicios de la Sociedad
        de la Informacion y Comercio Electronico, el titular expone sus datos identificativos:
    </p>
    <ul>
        <li><strong>Titular:</strong> Smartable IoT SLU</li>
        <li><strong>NIF:</strong> B54985536</li>
        <li><strong>Registro mercantil:</strong> Alicante, tomo 4012, folio 101, hoja A-153784</li>
        <li><strong>Domicilio:</strong> Rambla Mendez Nunez, 39 - 2o, 03002 Alicante, Espana</li>
        <li><strong>Correo electronico:</strong> <a href="mailto:hola@smartable.es">hola@smartable.es</a></li>
        <li><strong>Sitio web:</strong> <a href="https://yonomad.app" target="_blank" rel="noopener noreferrer">https://yonomad.app</a></li>
    </ul>
</div>
HTML;

if (is_array($datosAdmin) && count($datosAdmin) > 0) {
    $admin = $datosAdmin[0];
} else {
    session_destroy();
    header('Location: inicio_sesion_admin.php?error=no_data');
    exit();
}

// 2. LIMPIEZA DINÁMICA DEL AVATAR
$rawUrl = $admin['avatar_url'] ?? '../img/perfil.png';
$avatarUrl = '../img/perfil.png';

if (!empty($rawUrl) && $rawUrl != '../img/perfil.png') {
    if (strpos($rawUrl, '../') === 0) {
        $avatarUrl = $rawUrl;
    } else {
        $path = parse_url($rawUrl, PHP_URL_PATH);
        $avatarUrl = rtrim($_ENV['MINIO_PUBLIC_URL'], '/') . $path;
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta3/dist/js/bootstrap.bundle.min.js"></script>
    <link href="../style.css" rel="stylesheet">
    <script src="https://kit.fontawesome.com/b8814a2854.js" crossorigin="anonymous"></script>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="icon" href="../favicon-color.png">
    <link rel="icon" href="../favicon-negro.png" media="(prefers-color-scheme: light)">
    <link rel="icon" href="../favicon-color.png" media="(prefers-color-scheme: dark)">
    <title>TheNomadapp - Panel Administrador</title>

    <style>
        :root {
            /* Paleta roja Administrador */
            --primary-color: #c83a45;
            --primary-hover: #b7333e;
            --cancel-color: #dc3545;
            --light-bg: #f4f6f9;
            --white: #ffffff;
            --dark-text: #2c3e50;
            --border-radius-lg: 20px;
            --border-radius-md: 15px;
            --border-radius-sm: 10px;
            --icon-bg: #fce8ea;
            /* Rojo muy claro para el fondo de los iconos */
        }

        body {
            font-family: 'Nunito', sans-serif;
            background-color: var(--light-bg);
            color: var(--dark-text);
            padding-bottom: 90px;
        }

        .contenedorPerfil {
            background-color: var(--white);
            border-radius: var(--border-radius-lg);
            padding: 40px 30px;
            margin: 40px auto;
            max-width: 900px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: flex-start;
            height: auto !important;
            overflow-y: visible !important;
        }

        .fotoPerfilMovil {
            display: none;
        }

        .profile-image-container {
            width: 160px;
            height: 160px;
            overflow: hidden;
            border-radius: 50%;
            margin: 0 auto 20px auto;
            border: 4px solid var(--primary-color);
            box-shadow: 0 8px 20px rgba(200, 58, 69, 0.2);
            flex-shrink: 0;
            background-color: #fff;
        }

        .profile-image-container img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .perfilFotoBotones {
            flex-basis: 250px;
            max-width: 250px;
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding-right: 20px;
        }

        .perfilInfo {
            flex-grow: 1;
            min-width: 300px;
            text-align: left;
            padding-left: 20px;
            height: auto !important;
            overflow-y: visible !important;
        }

        .perfil-header {
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #e9ecef;
        }

        .perfil-header h4 {
            font-weight: 800;
            color: var(--dark-text);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* Estilo de Tarjetas Gestora */
        .info-card {
            background-color: var(--white);
            border: 1px solid #e9ecef;
            padding: 15px 20px;
            border-radius: var(--border-radius-md);
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            transition: all 0.3s ease;
        }

        .info-card:hover {
            border-color: var(--primary-color);
            transform: translateX(5px);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.03);
        }

        .info-icon {
            background-color: var(--icon-bg);
            color: var(--primary-color);
            width: 45px;
            height: 45px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            margin-right: 15px;
            flex-shrink: 0;
        }

        .info-content {
            display: flex;
            flex-direction: column;
            width: 100%;
        }

        .info-label {
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #8795a1;
            font-weight: 700;
            margin-bottom: 2px;
        }

        .info-value {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--dark-text);
            word-break: break-word;
        }
        
        .btn-light {
            background-color: #e9ecef !important;
            color: var(--dark-text) !important; /* Fuerza el texto a color oscuro */
            border: 1px solid #dee2e6;
        }

        .btn-light:hover {
            background-color: #d3d9df !important;
            color: #000 !important; /* Texto negro al pasar el ratón */
        }
        
        /* Botones */
        .btn-custom {
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 12px 15px;
            font-size: 0.95em;
            font-weight: 700;
            width: 100%;
            margin-bottom: 12px;
            border-radius: 50rem;
            border: none;
        }

        .btn-brand {
            background-color: var(--primary-color);
            color: var(--white);
        }

        .btn-brand:hover {
            background-color: var(--primary-hover);
            color: var(--white);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(200, 58, 69, 0.3);
        }

        .btn-logout {
            background-color: #fff;
            color: var(--cancel-color);
            border: 2px solid #ffdde1;
        }

        .btn-logout:hover {
            background-color: var(--cancel-color);
            border-color: var(--cancel-color);
            color: var(--white);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(220, 53, 69, 0.2);
        }

        .botonesMovil {
            display: none;
        }

        .badge-admin {
            background-color: var(--primary-color);
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.85rem;
            margin-bottom: 15px;
            display: inline-block;
            font-weight: 700;
        }

        /* Modales */
        .modal-content {
            border-radius: var(--border-radius-lg);
            border: none;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
        }

        .modal-header {
            border-bottom: 1px solid #f1f1f1;
            padding: 20px 25px;
        }

        .modal-body {
            padding: 25px;
        }

        .modal-footer {
            border-top: none;
            padding: 15px 25px 25px;
            display: flex;
            justify-content: space-between;
        }

        .form-control {
            border-radius: var(--border-radius-sm);
            padding: 10px 15px;
            background-color: #f8f9fa;
            border: 1px solid #e9ecef;
            font-weight: 600;
        }

        .form-control:focus {
            background-color: #fff;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(200, 58, 69, 0.25);
        }

        .custom-toast {
            border-radius: var(--border-radius-sm);
            font-family: 'Nunito', sans-serif;
            z-index: 10500;
        }

        /* Legal Box en el modal */
        .legal-info-wrapper {
            width: 100%;
            text-align: center;
            margin-bottom: 15px;
        }

        .legal-info-box {
            max-width: 100%;
            background-color: #f8fafd;
            border: 1px solid #dde5ef;
            border-radius: 12px;
            padding: 15px;
            color: #4f5b67;
            text-align: left;
            max-height: 180px;
            overflow-y: auto;
            font-size: 0.9rem;
        }

        .legal-rich-content h6 {
            font-weight: 800;
            color: var(--dark-text);
        }

        .legal-rich-content a {
            color: var(--primary-color);
            text-decoration: none;
        }

        .modal-footer .btn-light {
            margin: 0 15px 0 0 !important; /* Le quita el automático y le da 15px de separación a la derecha */
            width: auto !important;
        }

        .modal-footer .btn-brand {
            margin: 0 !important; /* Le quita el automático para que no salga volando */
            width: auto !important;
        }

        @media (max-width: 768px) {
            .contenedorPerfil {
                flex-direction: column;
                align-items: center;
                padding: 30px 20px;
                margin: 20px 15px;
            }

            .perfilFotoBotones {
                display: none;
            }

            .fotoPerfilMovil {
                display: block;
                width: 100%;
                text-align: center;
                margin-bottom: 10px;
            }

            .perfilInfo {
                width: 100%;
                min-width: unset;
                padding-left: 0;
            }

            .botonesMovil {
                display: flex;
                flex-direction: column;
                width: 100%;
                margin-top: 30px;
                border-top: 1px solid #e9ecef;
                padding-top: 20px;
            }
        }
    </style>
</head>

<body>
    <!-- TOAST DE NOTIFICACIONES -->
    <div class="position-fixed top-0 end-0 p-3" style="z-index: 10500">
        <div id="liveToast" class="toast align-items-center text-white border-0 custom-toast" role="alert"
            aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body fw-bold" id="toastMessage"></div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    </div>

    <!-- CONTENEDOR PRINCIPAL -->
    <div class="page-shell">
        <?php include 'headerAdmin.php'; ?> <!--Añadimos el header-->
        <div class="contenedorPerfil mt-4">

            <!-- VISTA MÓVIL (AVATAR) -->
            <div class="fotoPerfilMovil">
                <span class="badge-admin d-block mb-3"><i class="fas fa-shield-alt me-1"></i> Global Admin</span>
                <div class="profile-image-container">
                    <img id="fotoPerfilMovil" src="<?= htmlspecialchars($avatarUrl) ?>" alt="Profile Image">
                </div>
            </div>

            <!-- VISTA ESCRITORIO (AVATAR Y BOTONES FIJOS) -->
            <div class="perfilFotoBotones">
                <span class="badge-admin"><i class="fas fa-shield-alt me-1"></i> Global Admin</span>
                <div class="profile-image-container">
                    <img id="fotoPerfil" src="<?= htmlspecialchars($avatarUrl) ?>" alt="Profile Image">
                </div>

                <!-- Botones fijos y limpios, sin desplegables que se rallan -->
                <button type="button" class="btn-custom btn-brand" data-bs-toggle="modal" data-bs-target="#cambiarImagenModal">
                    <i class="fas fa-camera"></i> Cambiar imagen
                </button>
                <button type="button" class="btn-custom btn-brand botonEditar" data-bs-toggle="modal" data-bs-target="#editarPerfilModal">
                    <i class="fas fa-file-signature"></i> Editar Info Legal
                </button>
                <button type="button" class="btn-custom btn-logout mt-2" onclick="window.location.href='cerrarSesion.php'">
                    <i class="fas fa-sign-out-alt"></i> Cerrar sesión
                </button>
            </div>

            <!-- TARJETAS DE INFORMACIÓN DEL PERFIL -->
            <div class="perfilInfo">
                <div class="perfil-header">
                    <h4><i class="fas fa-user-cog text-muted"></i> Tu perfil de Administrador</h4>
                </div>

                <div class="info-card">
                    <div class="info-icon"><i class="fas fa-user"></i></div>
                    <div class="info-content">
                        <span class="info-label">Nombre completo</span>
                        <span class="info-value" id="val-nombre"><?= htmlspecialchars($admin['name'] ?? 'Sin especificar') ?></span>
                    </div>
                </div>

                <div class="info-card">
                    <div class="info-icon"><i class="fas fa-envelope"></i></div>
                    <div class="info-content">
                        <span class="info-label">E-mail de Contacto</span>
                        <span class="info-value" id="val-email"><?= htmlspecialchars($admin['email'] ?? 'Sin especificar') ?></span>
                    </div>
                </div>

                <div class="row w-100 g-0">
                    <div class="col-md-6 pe-md-2">
                        <div class="info-card">
                            <div class="info-icon"><i class="fas fa-phone-alt"></i></div>
                            <div class="info-content">
                                <span class="info-label">Teléfono Legal</span>
                                <span class="info-value" id="val-telefono"><?= htmlspecialchars($admin['phone'] ?? 'Sin especificar') ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 ps-md-2">
                        <div class="info-card">
                            <div class="info-icon"><i class="fas fa-id-card"></i></div>
                            <div class="info-content">
                                <span class="info-label">CIF/NIF</span>
                                <span class="info-value" id="val-cif"><?= htmlspecialchars($admin['cif'] ?? $admin['nif'] ?? 'Sin especificar') ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="info-card">
                    <div class="info-icon"><i class="fas fa-building"></i></div>
                    <div class="info-content">
                        <span class="info-label">Empresa / Razón Social</span>
                        <span class="info-value" id="val-empresa"><?= htmlspecialchars($admin['empresa'] ?? 'Sin especificar') ?></span>
                    </div>
                </div>

                <div class="info-card">
                    <div class="info-icon"><i class="fas fa-map-marker-alt"></i></div>
                    <div class="info-content">
                        <span class="info-label">Dirección de Facturación Global</span>
                        <span class="info-value" id="val-direccion-completa">
                            <?= htmlspecialchars($admin['domicilio_social'] ?? $admin['direccion'] ?? 'Sin dirección') ?>,
                            <?= htmlspecialchars($admin['localidad'] ?? 'Sin localidad') ?>,
                            <?= htmlspecialchars($admin['provincia'] ?? 'Sin provincia') ?>
                            (<?= htmlspecialchars($admin['codigo_postal'] ?? 'CP') ?>)
                        </span>
                    </div>
                </div>

                <!-- Campos ocultos para llenar el Modal -->
                <input type="hidden" id="adminId" value="<?= htmlspecialchars($admin['id'] ?? '') ?>">
                <input type="hidden" id="raw-direccion" value="<?= htmlspecialchars($admin['domicilio_social'] ?? $admin['direccion'] ?? '') ?>">
                <input type="hidden" id="raw-localidad" value="<?= htmlspecialchars($admin['localidad'] ?? '') ?>">
                <input type="hidden" id="raw-provincia" value="<?= htmlspecialchars($admin['provincia'] ?? '') ?>">
                <input type="hidden" id="raw-cp" value="<?= htmlspecialchars($admin['codigo_postal'] ?? '') ?>">
            </div>

            <!-- VISTA MÓVIL (BOTONES INFERIORES) -->
            <div class="botonesMovil">
                <button type="button" class="btn-custom btn-brand" data-bs-toggle="modal" data-bs-target="#cambiarImagenModal">
                    <i class="fas fa-camera"></i> Cambiar imagen
                </button>
                <button type="button" class="btn-custom btn-brand botonEditar" data-bs-toggle="modal" data-bs-target="#editarPerfilModal">
                    <i class="fas fa-file-signature"></i> Editar Info Legal
                </button>
                <button type="button" class="btn-custom btn-logout mt-3" onclick="window.location.href='cerrarSesion.php'">
                    <i class="fas fa-sign-out-alt"></i> Cerrar sesión
                </button>
            </div>
        </div>
    </div>

    <!-- INCLUIMOS EL FOOTER -->
    <?php include 'footerAdmin.php'; ?>

    <!-- MODAL: EDITAR PERFIL -->
    <div class="modal fade" id="editarPerfilModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Editar información legal (Administrador)</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="formEditarPerfil">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-muted small text-uppercase fw-bold">Nombre del Admin</label>
                                <input type="text" class="form-control" id="editNombre" name="nombre">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-muted small text-uppercase fw-bold">E-mail (No editable)</label>
                                <input disabled type="email" class="form-control" id="editEmail">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label text-muted small text-uppercase fw-bold">Teléfono Legal</label>
                                <input type="text" class="form-control" id="editTelefono" name="telefono">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label text-muted small text-uppercase fw-bold">CIF/NIF</label>
                                <input type="text" class="form-control" id="editCIF" name="cif">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label text-muted small text-uppercase fw-bold">Razón Social</label>
                                <input type="text" class="form-control" id="editEmpresa" name="empresa">
                            </div>
                        </div>

                        <h6 class="mt-4 mb-3 fw-bold border-bottom pb-2 text-danger"><i class="fas fa-map-marker-alt me-1"></i> Dirección de Facturación Global</h6>

                        <div class="mb-3">
                            <label class="form-label text-muted small text-uppercase fw-bold">Dirección Completa</label>
                            <input type="text" class="form-control" id="editDireccion" name="domicilio_social">
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label text-muted small text-uppercase fw-bold">Localidad</label>
                                <input type="text" class="form-control" id="editLocalidad" name="localidad">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label text-muted small text-uppercase fw-bold">Provincia</label>
                                <input type="text" class="form-control" id="editProvincia" name="provincia">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label text-muted small text-uppercase fw-bold">Código Postal</label>
                                <input type="text" class="form-control" id="editCodigoPostal" name="codigo_postal">
                            </div>
                        </div>

                        <div class="legal-info-wrapper mt-3">
                            <span class="form-label text-muted small text-uppercase fw-bold d-block text-start mb-2">Información Legal Generada</span>
                            <div class="legal-info-box" id="legalInfoDisplay"><?php echo $informacionLegal ?? ''; ?></div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light rounded-pill px-4 fw-bold" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-brand rounded-pill px-4" style="margin:0; width:auto;" id="btnGuardarCambios">Guardar cambios</button>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL: CAMBIAR IMAGEN -->
    <div class="modal fade" id="cambiarImagenModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Actualizar foto de perfil</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <form id="formCambiarImagen">
                        <div class="profile-image-container mx-auto mb-4" style="width: 120px; height: 120px;">
                            <img id="previewImagen" src="<?= htmlspecialchars($avatarUrl) ?>" alt="Vista previa">
                        </div>
                        <div class="text-start mb-2">
                            <label class="form-label fw-bold text-muted small text-uppercase">Selecciona una imagen</label>
                            <input type="file" class="form-control" id="inputImagen" name="imagen" accept="image/*">
                            <input type="hidden" id="imagenAdminId" name="adminId" value="<?= htmlspecialchars($admin['id'] ?? '') ?>">
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light rounded-pill px-4 fw-bold" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-brand rounded-pill px-4" style="margin:0; width:auto;" id="btnGuardarImagen">Guardar foto</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        function mostrarNotificacion(mensaje, tipo = 'success') {
            const toastEl = document.getElementById('liveToast');
            const toastMessage = document.getElementById('toastMessage');
            toastEl.classList.remove('bg-success', 'bg-danger', 'bg-warning');

            if (tipo === 'success') {
                toastEl.classList.add('bg-success');
                mensaje = '✅ ' + mensaje;
            } else if (tipo === 'error') {
                toastEl.classList.add('bg-danger');
                mensaje = '⚠️ ' + mensaje;
            }

            toastMessage.textContent = mensaje;
            new bootstrap.Toast(toastEl, {
                delay: 3500
            }).show();
        }

        document.querySelectorAll('.botonEditar').forEach(boton => {
            boton.addEventListener('click', function() {
                document.getElementById("editNombre").value = document.getElementById("val-nombre").textContent !== 'Sin especificar' ? document.getElementById("val-nombre").textContent : "";
                document.getElementById("editEmail").value = document.getElementById("val-email").textContent;
                document.getElementById("editEmpresa").value = document.getElementById("val-empresa").textContent !== 'Sin especificar' ? document.getElementById("val-empresa").textContent : "";
                document.getElementById("editTelefono").value = document.getElementById("val-telefono").textContent !== 'Sin especificar' ? document.getElementById("val-telefono").textContent : "";
                document.getElementById("editCIF").value = document.getElementById("val-cif").textContent !== 'Sin especificar' ? document.getElementById("val-cif").textContent : "";

                document.getElementById("editDireccion").value = document.getElementById("raw-direccion").value;
                document.getElementById("editLocalidad").value = document.getElementById("raw-localidad").value;
                document.getElementById("editProvincia").value = document.getElementById("raw-provincia").value;
                document.getElementById("editCodigoPostal").value = document.getElementById("raw-cp").value;
            });
        });

        document.getElementById("btnGuardarCambios").addEventListener("click", function() {
            const formData = new FormData(document.getElementById("formEditarPerfil"));
            const btn = this;
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';

            fetch("actualizarAdmin.php", {
                    method: "POST",
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById("val-nombre").textContent = formData.get('nombre') || 'Sin especificar';
                        document.getElementById("val-telefono").textContent = formData.get('telefono') || 'Sin especificar';
                        document.getElementById("val-cif").textContent = formData.get('cif') || 'Sin especificar';
                        document.getElementById("val-empresa").textContent = formData.get('empresa') || 'Sin especificar';

                        const dir = formData.get('domicilio_social') || 'Sin dirección';
                        const loc = formData.get('localidad') || 'Sin localidad';
                        const prov = formData.get('provincia') || 'Sin provincia';
                        const cp = formData.get('codigo_postal') || 'CP';

                        document.getElementById("val-direccion-completa").textContent = `${dir}, ${loc}, ${prov} (${cp})`;

                        document.getElementById("raw-direccion").value = dir;
                        document.getElementById("raw-localidad").value = loc;
                        document.getElementById("raw-provincia").value = prov;
                        document.getElementById("raw-cp").value = cp;

                        mostrarNotificacion("Perfil actualizado correctamente", "success");
                        bootstrap.Modal.getInstance(document.getElementById('editarPerfilModal')).hide();
                    } else {
                        mostrarNotificacion("Error: " + data.message, "error");
                    }
                })
                .catch(error => mostrarNotificacion("Ha ocurrido un error de conexión.", "error"))
                .finally(() => {
                    btn.disabled = false;
                    btn.textContent = "Guardar cambios";
                });
        });

        document.getElementById('inputImagen').addEventListener('change', function(event) {
            if (event.target.files[0]) {
                const reader = new FileReader();
                reader.onload = e => document.getElementById('previewImagen').src = e.target.result;
                reader.readAsDataURL(event.target.files[0]);
            }
        });

        document.getElementById('btnGuardarImagen').addEventListener('click', function() {
            const inputImagen = document.getElementById("inputImagen");
            if (inputImagen.files.length === 0) return mostrarNotificacion("Debes seleccionar una imagen", "error");

            const formData = new FormData();
            formData.append('imagen', inputImagen.files[0]);
            formData.append('adminId', document.getElementById('imagenAdminId').value);

            const btn = this;
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';

            fetch("subir-imagen-perfil-admin.php", {
                    method: "POST",
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById("fotoPerfil").src = data.avatarUrl;
                        document.getElementById("fotoPerfilMovil").src = data.avatarUrl;
                        mostrarNotificacion("Foto actualizada correctamente", "success");
                        bootstrap.Modal.getInstance(document.getElementById('cambiarImagenModal')).hide();
                    } else {
                        mostrarNotificacion("Error: " + data.message, "error");
                    }
                })
                .catch(error => mostrarNotificacion("Ha ocurrido un error al guardar la imagen", "error"))
                .finally(() => {
                    btn.disabled = false;
                    btn.textContent = "Guardar foto";
                });
        });
    </script>
</body>

</html>