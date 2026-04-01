<?php
require_once 'verificar_sesion_host.php';
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
    <link rel="icon" href="../favicon-color.png">
    <title>TheNomadapp - Tu perfil de Anfitrión</title>

    <style>
        :root {
            --primary-color: #09b2fb; /* Cyan Anfitrión */
            --primary-hover: #0084d6;
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

           .page-shell {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 15px;
            box-sizing: border-box;
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
            box-shadow: 0 8px 20px rgba(0, 183, 207, 0.2);
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
            background-color: #e0f8fb; /* Fondo cyan clarito */
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
            box-shadow: 0 4px 12px rgba(0, 183, 207, 0.3);
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

        /* Loading Overlay */
        #loadingContainer {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(255, 255, 255, 0.9);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            flex-direction: column;
        }

        .spinner-border {
            width: 3rem;
            height: 3rem;
            color: var(--primary-color);
            margin-bottom: 1rem;
        }

        .loading-text {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--primary-color);
        }

        .botonesMovil {
            display: none;
        }

        /* Modal y Toasts */
        .modal-content {
            border-radius: var(--border-radius-lg);
            border: none;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
        }
        .modal-header { border-bottom: 1px solid #f1f1f1; padding: 20px 25px; }
        .modal-body { padding: 25px; }
        .modal-footer { border-top: none; padding: 15px 25px 25px; }
        .form-control {
            border-radius: var(--border-radius-sm);
            padding: 12px 15px;
            background-color: #f8f9fa;
            border: 1px solid #e9ecef;
            font-weight: 600;
        }
        .form-control:focus {
            background-color: #fff;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(0, 183, 207, 0.25);
        }
        .custom-toast {
            border-radius: var(--border-radius-sm);
            font-family: 'Nunito', sans-serif;
            z-index: 10500;
        }

        @media (max-width: 768px) {
            .contenedorPerfil {
                flex-direction: column;
                align-items: center;
                padding: 30px 20px;
                margin: 20px 15px;
            }
            .perfilFotoBotones { display: none; }
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
    <div class="page-shell">
        
        <?php include 'headerAnfitrion.php'; ?>

        <div id="container" style="max-width: 100%; overflow-x: hidden; box-sizing: border-box;"></div>
    </div>
    <div id="loadingContainer">
        <div class="spinner-border" role="status">
            <span class="visually-hidden">Cargando...</span>
        </div>
        <div class="loading-text">Cargando tu perfil...</div>
    </div>

    <div class="position-fixed top-0 end-0 p-3" style="z-index: 10500">
        <div id="liveToast" class="toast align-items-center text-white border-0 custom-toast" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body fw-bold" id="toastMessage"></div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    </div>

    <div class="contenedorPerfil">
        <div class="fotoPerfilMovil">
            <div class="profile-image-container">
                <img id="fotoPerfilMovil" src="../img/perfil.png" alt="Profile Image">
            </div>
        </div>

        <div class="perfilFotoBotones">
            <div class="profile-image-container">
                <img id="fotoPerfil" src="../img/perfil.png" alt="Profile Image">
            </div>
            <button type="button" class="btn-custom btn-brand" data-bs-toggle="modal" data-bs-target="#cambiarImagenModal" onclick="prepararModalImagen()">
                <i class="fas fa-camera"></i> Cambiar imagen
            </button>
            <button type="button" class="btn-custom btn-brand botonEditar" data-bs-toggle="modal" data-bs-target="#editarPerfilModal" onclick="cargarDatosModal()">
                <i class="fas fa-user-edit"></i> Editar perfil
            </button>
            <button type="button" class="btn-custom btn-brand" onclick="location.href='Suscripciones.php'">
                <i class="fas fa-crown"></i> Cambiar plan
            </button>
            <button type="button" class="btn-custom btn-logout mt-2" onclick="location.href='./logoutHost.php'">
                <i class="fas fa-sign-out-alt"></i> Cerrar sesión
            </button>
        </div>

        <div class="perfilInfo">
            <div class="perfil-header">
                <h4>Detalles de tu cuenta</h4>
            </div>

            <div class="info-card">
                <div class="info-icon"><i class="fas fa-user"></i></div>
                <div class="info-content">
                    <span class="info-label">Nombre</span>
                    <span class="info-value" id="val-nombre">...</span>
                </div>
            </div>

            <div class="info-card">
                <div class="info-icon"><i class="fas fa-envelope"></i></div>
                <div class="info-content">
                    <span class="info-label">E-mail</span>
                    <span class="info-value" id="val-email">...</span>
                </div>
            </div>

            <div class="info-card">
                <div class="info-icon"><i class="fas fa-building"></i></div>
                <div class="info-content">
                    <span class="info-label">Empresa</span>
                    <span class="info-value" id="val-empresa">...</span>
                </div>
            </div>

            <div class="info-card">
                <div class="info-icon"><i class="fas fa-phone-alt"></i></div>
                <div class="info-content">
                    <span class="info-label">Teléfono</span>
                    <span class="info-value" id="val-telefono">...</span>
                </div>
            </div>

            <div class="info-card">
                <div class="info-icon"><i class="fas fa-id-card"></i></div>
                <div class="info-content">
                    <span class="info-label">NIF</span>
                    <span class="info-value" id="val-nif">...</span>
                </div>
            </div>

            <div class="info-card">
                <div class="info-icon"><i class="fas fa-star"></i></div>
                <div class="info-content">
                    <span class="info-label">Suscripción actual</span>
                    <span class="info-value">
                        Plan <span id="val-plan" class="text-primary fw-bolder">...</span> 
                        <small class="text-muted" style="font-size:0.8rem; margin-left: 5px;">(Válido hasta: <span id="val-planEnd">...</span>)</small>
                    </span>
                </div>
            </div>

            <input type="hidden" id="anfitrionId" name="anfitrionId">
        </div>

        <div class="botonesMovil">
            <button type="button" class="btn-custom btn-brand" data-bs-toggle="modal" data-bs-target="#cambiarImagenModal" onclick="prepararModalImagen()">
                <i class="fas fa-camera"></i> Cambiar imagen
            </button>
            <button type="button" class="btn-custom btn-brand botonEditar" data-bs-toggle="modal" data-bs-target="#editarPerfilModal" onclick="cargarDatosModal()">
                <i class="fas fa-user-edit"></i> Editar perfil
            </button>
            <button type="button" class="btn-custom btn-brand" onclick="location.href='Suscripciones.php'">
                <i class="fas fa-crown"></i> Cambiar plan
            </button>
            <button type="button" class="btn-custom btn-logout mt-3" onclick="location.href='./logoutHost.php'">
                <i class="fas fa-sign-out-alt"></i> Cerrar sesión
            </button>
        </div>
    </div>

    <div class="modal fade" id="editarPerfilModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Editar datos personales</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="formEditarPerfil">
                        <div class="mb-3">
                            <label class="form-label text-muted small text-uppercase fw-bold">Nombre</label>
                            <input type="text" class="form-control" id="editNombre" name="nombre">
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-muted small text-uppercase fw-bold">E-mail</label>
                            <input disabled type="email" class="form-control bg-light" id="editEmail" name="email">
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-muted small text-uppercase fw-bold">Empresa</label>
                            <input type="text" class="form-control" id="editEmpresa" name="empresa">
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-muted small text-uppercase fw-bold">Teléfono</label>
                            <input type="text" class="form-control" id="editTelefono" name="telefono">
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-muted small text-uppercase fw-bold">NIF</label>
                            <input type="text" class="form-control" id="editNIF" name="nif">
                        </div>
                        <input type="hidden" id="editAnfitrionId" name="anfitrionId">
                    </form>
                </div>
                <div class="modal-footer d-flex justify-content-between">
                    <button type="button" class="btn btn-light rounded-pill px-4 fw-bold" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-brand rounded-pill px-4" style="margin:0; width:auto;" id="btnGuardarCambios">Guardar cambios</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="cambiarImagenModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Actualizar foto de perfil</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                    <form id="formCambiarImagen">
                        <div class="profile-image-container mx-auto mb-4" style="width: 120px; height: 120px;">
                            <img id="previewImagen" src="../img/perfil.png" alt="Vista previa">
                        </div>
                        <div class="text-start mb-2">
                            <label class="form-label fw-bold text-muted small text-uppercase">Selecciona una imagen de tu galería</label>
                            <input type="file" class="form-control" id="inputImagen" name="imagen" accept="image/*">
                            <input type="hidden" id="imagenAnfitrionId" name="anfitrionId" value="">
                        </div>
                    </form>
                </div>
                <div class="modal-footer d-flex justify-content-between">
                    <button type="button" class="btn btn-light rounded-pill px-4 fw-bold" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-brand rounded-pill px-4" style="margin:0; width:auto;" id="btnGuardarImagen">Guardar foto</button>
                </div>
            </div>
        </div>
    </div>

    <?php include 'footerAnfitrion.php'; ?>

    <script>
        // Función Toasts
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

        // Cargar datos al iniciar
        document.addEventListener("DOMContentLoaded", function() {
            const url = "../getAnfitrionByEmailAsync.php";

            fetch(url)
                .then(response => {
                    if (!response.ok) throw new Error('Error de red');
                    return response.json();
                })
                .then(data => {
                    const avatarUrl = data.avatar_url ? data.avatar_url : '../img/perfil.png';
                    document.getElementById("fotoPerfil").src = avatarUrl;
                    document.getElementById("fotoPerfilMovil").src = avatarUrl;

                    document.getElementById("val-nombre").textContent = data.name || "Sin especificar";
                    document.getElementById("val-email").textContent = data.email || "Sin especificar";
                    document.getElementById("val-empresa").textContent = data.empresa || "Sin especificar";
                    document.getElementById("val-telefono").textContent = data.phone || "Sin especificar";
                    document.getElementById("val-nif").textContent = data.nif || "Sin especificar";
                    document.getElementById("val-plan").textContent = data.plan || "N/A";

                    if (data.plan_end) {
                        document.getElementById("val-planEnd").textContent = new Date(data.plan_end).toLocaleDateString('es-ES');
                    } else {
                        document.getElementById("val-planEnd").textContent = "N/A";
                    }

                    document.getElementById("anfitrionId").value = data.id;
                    sessionStorage.setItem('anfitrionId', data.id);

                    document.getElementById("loadingContainer").style.display = "none";
                })
                .catch(err => {
                    document.getElementById("loadingContainer").innerHTML = `
                        <div class="alert alert-danger m-4 text-center">
                            <i class="fas fa-exclamation-triangle h3 mb-3 d-block"></i>
                            Error al cargar los datos. Por favor, recarga la página.
                        </div>`;
                });

            // Guardar Cambios
            document.getElementById("btnGuardarCambios").addEventListener("click", function() {
                const formData = new FormData(document.getElementById("formEditarPerfil"));
                const btn = this;
                
                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';

                fetch("actualizarAnfitrion.php", {
                        method: "POST",
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Actualizar la interfaz sin recargar
                            document.getElementById("val-nombre").textContent = formData.get('nombre') || "Sin especificar";
                            document.getElementById("val-empresa").textContent = formData.get('empresa') || "Sin especificar";
                            document.getElementById("val-telefono").textContent = formData.get('telefono') || "Sin especificar";
                            document.getElementById("val-nif").textContent = formData.get('nif') || "Sin especificar";

                            mostrarNotificacion("Perfil actualizado correctamente", "success");
                            const modal = bootstrap.Modal.getInstance(document.getElementById('editarPerfilModal'));
                            modal.hide();
                        } else {
                            mostrarNotificacion(data.message, "error");
                        }
                    })
                    .catch(error => mostrarNotificacion("Error al guardar los cambios", "error"))
                    .finally(() => {
                        btn.disabled = false;
                        btn.innerHTML = 'Guardar cambios';
                    });
            });

            // Preview Imagen
            document.getElementById('inputImagen').addEventListener('change', function(event) {
                const archivo = event.target.files[0];
                if (archivo) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        document.getElementById('previewImagen').src = e.target.result;
                    };
                    reader.readAsDataURL(archivo);
                }
            });

            // Guardar Imagen
            document.getElementById('btnGuardarImagen').addEventListener('click', function() {
                const formData = new FormData();
                const inputImagen = document.getElementById("inputImagen");

                if (inputImagen.files.length === 0) {
                    mostrarNotificacion("Debes seleccionar una imagen", "error");
                    return;
                }

                formData.append('imagen', inputImagen.files[0]);
                formData.append('anfitrionId', document.getElementById('imagenAnfitrionId').value);

                const btn = this;
                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';

                fetch("subir-imagen-perfil-host.php", {
                        method: "POST",
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            document.getElementById("fotoPerfil").src = data.avatarUrl;
                            document.getElementById("fotoPerfilMovil").src = data.avatarUrl;
                            
                            mostrarNotificacion("Foto actualizada correctamente", "success");
                            const modal = bootstrap.Modal.getInstance(document.getElementById('cambiarImagenModal'));
                            modal.hide();
                        } else {
                            mostrarNotificacion(data.message, "error");
                        }
                    })
                    .catch(error => mostrarNotificacion("Error al guardar la imagen", "error"))
                    .finally(() => {
                        btn.disabled = false;
                        btn.innerHTML = 'Guardar foto';
                    });
            });
        });

        function cargarDatosModal() {
            document.getElementById("editNombre").value = document.getElementById("val-nombre").textContent !== "Sin especificar" ? document.getElementById("val-nombre").textContent : "";
            document.getElementById("editEmail").value = document.getElementById("val-email").textContent !== "Sin especificar" ? document.getElementById("val-email").textContent : "";
            document.getElementById("editEmpresa").value = document.getElementById("val-empresa").textContent !== "Sin especificar" ? document.getElementById("val-empresa").textContent : "";
            document.getElementById("editTelefono").value = document.getElementById("val-telefono").textContent !== "Sin especificar" ? document.getElementById("val-telefono").textContent : "";
            document.getElementById("editNIF").value = document.getElementById("val-nif").textContent !== "Sin especificar" ? document.getElementById("val-nif").textContent : "";
            document.getElementById("editAnfitrionId").value = document.getElementById("anfitrionId").value;
        }

        function prepararModalImagen() {
            document.getElementById('imagenAnfitrionId').value = document.getElementById('anfitrionId').value;
            document.getElementById('previewImagen').src = document.getElementById('fotoPerfil').src;
            document.getElementById('inputImagen').value = "";
        }
    </script>
</body>
</html>