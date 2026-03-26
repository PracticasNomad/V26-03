<?php
require_once 'verificar_sesion_gestor.php';
require '../vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

// 1. CARGAMOS LOS DATOS DE LA GESTORA DIRECTAMENTE EN PHP (Usando Service Key por RLS)
$urlGestor = "http://" . $_ENV['SERVER_IP'] . ":" . $_ENV['DATABASE_PORT'] . "/rest/v1/gestor?id=eq." . $_SESSION["user_id"];
$ch = curl_init($urlGestor);
curl_setopt_array($ch, [
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $_ENV['SERVICE_APIKEY'],
        'apikey: ' . $_ENV['SERVICE_APIKEY']
    ],
    CURLOPT_RETURNTRANSFER => true
]);
$resultado = curl_exec($ch);
curl_close($ch);

$datosGestor = json_decode($resultado, true);
$gestora = count($datosGestor) > 0 ? $datosGestor[0] : [];
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://kit.fontawesome.com/b8814a2854.js" crossorigin="anonymous"></script>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@200;400;600;700&display=swap" rel="stylesheet">
    <link rel="icon" href="../img/favicon-color.png">
    <title>TheNomadapp - Tu perfil Gestora</title>

    <style>
        :root {
            --primary-color: #1976d2;
            /* Azul Puro/Clásico */
            --primary-hover: #1565c0;
            --cancel-color: #dc3545;
            --light-bg: #f4f6f9;
            --white: #ffffff;
            --dark-text: #2c3e50;
            --border-radius-lg: 20px;
            --border-radius-md: 15px;
            --border-radius-sm: 10px;
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
            box-shadow: 0 8px 20px rgba(25, 118, 210, 0.2);
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
        }

        /* Tarjetas de información */
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
            background-color: #e3f2fd;
            /* Azul clarito */
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
            box-shadow: 0 4px 12px rgba(25, 118, 210, 0.3);
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

        /* Modal y Toasts */
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
            box-shadow: 0 0 0 0.25rem rgba(25, 118, 210, 0.25);
        }

        .custom-toast {
            border-radius: var(--border-radius-sm);
            font-family: 'Nunito', sans-serif;
            z-index: 10500;
        }

        /* ESTILOS ORIGINALES DEL FOOTER - NO TOCAR NI ALTERAR  */

        .footer {
            color: black;
            background-color: white;
            width: 100%;
            -webkit-user-select: none;
            -ms-user-select: none;
            user-select: none;
            bottom: 0;
            font-size: 15px;
            opacity: 0.9;
            background: #E3E1E1;
            text-align: center;
            position: fixed;
        }

        .footer input[type="radio"] {
            display: none;
        }

        label,
        .form-check input[type=checkbox] {
            position: static;
        }

        #res:checked~#lbl_res,
        #his:checked~#lbl_his,
        #esp:checked~#lbl_esp,
        #per:checked~#lbl_per {
            color: #00B7CF !important;
        }

        a,
        a:visited,
        a:active {
            color: black;
            text-decoration: none;
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

        #per:checked~#lbl_per .icon-container,
        #res:checked~#lbl_res .icon-container,
        #his:checked~#lbl_his .icon-container,
        #esp:checked~#lbl_esp .icon-container {
            color: #007bff;
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
    <div class="position-fixed top-0 end-0 p-3" style="z-index: 10500">
        <div id="liveToast" class="toast align-items-center text-white border-0 custom-toast" role="alert"
            aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body fw-bold" id="toastMessage"></div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    </div>

    <div class="contenedorPerfil mt-5">

        <div class="fotoPerfilMovil">
            <div class="profile-image-container">
                <img id="fotoPerfilMovil" src="<?= htmlspecialchars($gestora['avatar_url'] ?? '../img/perfil.png') ?>"
                    alt="Profile Image">
            </div>
        </div>

        <div class="perfilFotoBotones">
            <div class="profile-image-container">
                <img id="fotoPerfil" src="<?= htmlspecialchars($gestora['avatar_url'] ?? '../img/perfil.png') ?>"
                    alt="Profile Image">
            </div>
            <button type="button" class="btn-custom btn-brand" data-bs-toggle="modal"
                data-bs-target="#cambiarImagenModal">
                <i class="fas fa-camera"></i> Cambiar imagen
            </button>
            <button type="button" class="btn-custom btn-brand botonEditar" data-bs-toggle="modal"
                data-bs-target="#editarPerfilModal">
                <i class="fas fa-user-edit"></i> Editar perfil
            </button>
            <button type="button" class="btn-custom btn-brand" onclick="window.location.href='Suscripciones.php'">
                <i class="fas fa-exchange-alt"></i> Cambiar plan
            </button>
            <button type="button" class="btn-custom btn-logout mt-2" onclick="window.location.href='cerrarSesion.php'">
                <i class="fas fa-sign-out-alt"></i> Cerrar sesión
            </button>
        </div>

        <div class="perfilInfo">
            <div class="perfil-header">
                <h4>Tu perfil de gestora</h4>
            </div>

            <div class="info-card">
                <div class="info-icon"><i class="fas fa-user"></i></div>
                <div class="info-content">
                    <span class="info-label">Nombre completo</span>
                    <span class="info-value"
                        id="val-nombre"><?= htmlspecialchars($gestora['name'] ?? 'Sin especificar') ?></span>
                </div>
            </div>

            <div class="info-card">
                <div class="info-icon"><i class="fas fa-envelope"></i></div>
                <div class="info-content">
                    <span class="info-label">E-mail</span>
                    <span class="info-value"
                        id="val-email"><?= htmlspecialchars($gestora['email'] ?? 'Sin especificar') ?></span>
                </div>
            </div>

            <div class="row w-100 g-0">
                <div class="col-md-6 pe-md-2">
                    <div class="info-card">
                        <div class="info-icon"><i class="fas fa-phone-alt"></i></div>
                        <div class="info-content">
                            <span class="info-label">Teléfono</span>
                            <span class="info-value"
                                id="val-telefono"><?= htmlspecialchars($gestora['phone'] ?? 'Sin especificar') ?></span>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 ps-md-2">
                    <div class="info-card">
                        <div class="info-icon"><i class="fas fa-id-card"></i></div>
                        <div class="info-content">
                            <span class="info-label">CIF/NIF</span>
                            <span class="info-value"
                                id="val-cif"><?= htmlspecialchars($gestora['cif'] ?? $gestora['nif'] ?? 'Sin especificar') ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="info-card">
                <div class="info-icon"><i class="fas fa-building"></i></div>
                <div class="info-content">
                    <span class="info-label">Empresa</span>
                    <span class="info-value"
                        id="val-empresa"><?= htmlspecialchars($gestora['empresa'] ?? 'Sin especificar') ?></span>
                </div>
            </div>

            <div class="info-card">
                <div class="info-icon"><i class="fas fa-map-marker-alt"></i></div>
                <div class="info-content">
                    <span class="info-label">Dirección Fiscal</span>
                    <span class="info-value" id="val-direccion-completa">
                        <?= htmlspecialchars($gestora['direccion'] ?? 'Sin dirección') ?>,
                        <?= htmlspecialchars($gestora['localidad'] ?? 'Sin localidad') ?>,
                        <?= htmlspecialchars($gestora['provincia'] ?? 'Sin provincia') ?>
                        (<?= htmlspecialchars($gestora['codigo_postal'] ?? 'CP') ?>)
                    </span>
                </div>
            </div>

            <div class="info-card" style="border-color: #cce5ff;">
                <div class="info-icon" style="background-color: var(--primary-color); color: white;"><i
                        class="fas fa-crown"></i></div>
                <div class="info-content">
                    <span class="info-label">Suscripción actual</span>
                    <span class="info-value">
                        Plan <span
                            class="text-primary fw-bolder"><?= htmlspecialchars($gestora['plan'] ?? 'Básico') ?></span>
                        <small class="text-muted" style="font-size:0.8rem; margin-left: 5px;">
                            (Válido hasta:
                            <?= !empty($gestora['fin_plan']) ? date('d/m/Y', strtotime($gestora['fin_plan'])) : 'N/A' ?>)
                        </small>
                    </span>
                </div>
            </div>

            <input type="hidden" id="gestorId" value="<?= htmlspecialchars($gestora['id'] ?? '') ?>">
            <input type="hidden" id="raw-direccion" value="<?= htmlspecialchars($gestora['direccion'] ?? '') ?>">
            <input type="hidden" id="raw-localidad" value="<?= htmlspecialchars($gestora['localidad'] ?? '') ?>">
            <input type="hidden" id="raw-provincia" value="<?= htmlspecialchars($gestora['provincia'] ?? '') ?>">
            <input type="hidden" id="raw-cp" value="<?= htmlspecialchars($gestora['codigo_postal'] ?? '') ?>">
        </div>

        <div class="botonesMovil">
            <button type="button" class="btn-custom btn-brand" data-bs-toggle="modal"
                data-bs-target="#cambiarImagenModal">
                <i class="fas fa-camera"></i> Cambiar imagen
            </button>
            <button type="button" class="btn-custom btn-brand botonEditar" data-bs-toggle="modal"
                data-bs-target="#editarPerfilModal">
                <i class="fas fa-user-edit"></i> Editar perfil
            </button>
            <button type="button" class="btn-custom btn-brand" onclick="window.location.href='Suscripciones.php'">
                <i class="fas fa-exchange-alt"></i> Cambiar plan
            </button>
            <button type="button" class="btn-custom btn-logout mt-3" onclick="window.location.href='cerrarSesion.php'">
                <i class="fas fa-sign-out-alt"></i> Cerrar sesión
            </button>
        </div>
    </div>

    <div class="modal fade" id="editarPerfilModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Editar datos de gestora</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="formEditarPerfil">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-muted small text-uppercase fw-bold">Nombre</label>
                                <input type="text" class="form-control" id="editNombre" name="nombre">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-muted small text-uppercase fw-bold">E-mail (No
                                    editable)</label>
                                <input disabled type="email" class="form-control" id="editEmail">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label text-muted small text-uppercase fw-bold">Teléfono</label>
                                <input type="text" class="form-control" id="editTelefono" name="telefono">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label text-muted small text-uppercase fw-bold">CIF/NIF</label>
                                <input type="text" class="form-control" id="editCIF" name="cif">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label text-muted small text-uppercase fw-bold">Empresa</label>
                                <input type="text" class="form-control" id="editEmpresa" name="empresa">
                            </div>
                        </div>

                        <h6 class="mt-3 mb-3 fw-bold border-bottom pb-2">Datos de facturación</h6>

                        <div class="mb-3">
                            <label class="form-label text-muted small text-uppercase fw-bold">Dirección</label>
                            <input type="text" class="form-control" id="editDireccion" name="direccion">
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
                    </form>
                </div>
                <div class="modal-footer d-flex justify-content-between">
                    <button type="button" class="btn btn-light rounded-pill px-4 fw-bold"
                        data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-brand rounded-pill px-4" style="margin:0; width:auto;"
                        id="btnGuardarCambios">Guardar cambios</button>
                </div>
            </div>
        </div>
    </div>

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
                            <img id="previewImagen"
                                src="<?= htmlspecialchars($gestora['avatar_url'] ?? '../img/perfil.png') ?>"
                                alt="Vista previa">
                        </div>
                        <div class="text-start mb-2">
                            <label class="form-label fw-bold text-muted small text-uppercase">Selecciona una
                                imagen</label>
                            <input type="file" class="form-control" id="inputImagen" name="imagen" accept="image/*">
                            <input type="hidden" id="imagenGestorId" name="gestorId"
                                value="<?= htmlspecialchars($gestora['id'] ?? '') ?>">
                        </div>
                    </form>
                </div>
                <div class="modal-footer d-flex justify-content-between">
                    <button type="button" class="btn btn-light rounded-pill px-4 fw-bold"
                        data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-brand rounded-pill px-4" style="margin:0; width:auto;"
                        id="btnGuardarImagen">Guardar foto</button>
                </div>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>

    <script>
        // Función de Notificaciones estilo "App"
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
            const toast = new bootstrap.Toast(toastEl, { delay: 3500 });
            toast.show();
        }

        // Cargar los datos en el modal cuando se abre
        document.querySelectorAll('.botonEditar').forEach(boton => {
            boton.addEventListener('click', function () {
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

        // Guardar cambios del perfil
        document.getElementById("btnGuardarCambios").addEventListener("click", function () {
            const formData = new FormData(document.getElementById("formEditarPerfil"));
            const btn = this;
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';

            fetch("actualizarGestor.php", {
                method: "POST",
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Actualizamos la UI visualmente para no tener que recargar la página entera
                        document.getElementById("val-nombre").textContent = formData.get('nombre') || 'Sin especificar';
                        document.getElementById("val-telefono").textContent = formData.get('telefono') || 'Sin especificar';
                        document.getElementById("val-cif").textContent = formData.get('cif') || 'Sin especificar';
                        document.getElementById("val-empresa").textContent = formData.get('empresa') || 'Sin especificar';

                        const dir = formData.get('direccion') || 'Sin dirección';
                        const loc = formData.get('localidad') || 'Sin localidad';
                        const prov = formData.get('provincia') || 'Sin provincia';
                        const cp = formData.get('codigo_postal') || 'CP';

                        document.getElementById("val-direccion-completa").textContent = `${dir}, ${loc}, ${prov} (${cp})`;

                        // Actualizamos los campos ocultos
                        document.getElementById("raw-direccion").value = formData.get('direccion');
                        document.getElementById("raw-localidad").value = formData.get('localidad');
                        document.getElementById("raw-provincia").value = formData.get('provincia');
                        document.getElementById("raw-cp").value = formData.get('codigo_postal');

                        mostrarNotificacion("Perfil actualizado correctamente", "success");
                        bootstrap.Modal.getInstance(document.getElementById('editarPerfilModal')).hide();
                    } else {
                        mostrarNotificacion("Error: " + data.message, "error");
                    }
                })
                .catch(error => {
                    console.error("Error:", error);
                    mostrarNotificacion("Ha ocurrido un error de conexión.", "error");
                })
                .finally(() => {
                    btn.disabled = false;
                    btn.textContent = "Guardar cambios";
                });
        });

        // Previsualizar Imagen
        document.getElementById('inputImagen').addEventListener('change', function (event) {
            const archivo = event.target.files[0];
            if (archivo) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    document.getElementById('previewImagen').src = e.target.result;
                };
                reader.readAsDataURL(archivo);
            }
        });

        // Guardar Nueva Imagen
        document.getElementById('btnGuardarImagen').addEventListener('click', function () {
            const formData = new FormData();
            const inputImagen = document.getElementById("inputImagen");

            if (inputImagen.files.length === 0) {
                mostrarNotificacion("Debes seleccionar una imagen primero", "error");
                return;
            }

            formData.append('imagen', inputImagen.files[0]);
            formData.append('gestorId', document.getElementById('imagenGestorId').value);

            const btn = this;
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';

            fetch("subir-imagen-perfil-gestor.php", {
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
                .catch(error => {
                    console.error("Error:", error);
                    mostrarNotificacion("Ha ocurrido un error al guardar la imagen", "error");
                })
                .finally(() => {
                    btn.disabled = false;
                    btn.textContent = "Guardar foto";
                });
        });
    </script>
</body>

</html>