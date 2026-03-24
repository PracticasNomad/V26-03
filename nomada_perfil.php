<?php
require_once 'verificar_sesion_guest.php';
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://kit.fontawesome.com/b8814a2854.js" crossorigin="anonymous"></script>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.1/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="icon" href="favicon-color.png">
    <link rel="icon" href="favicon-negro.png" media="(prefers-color-scheme: light)">
    <link rel="icon" href="favicon-color.png" media="(prefers-color-scheme: dark)">
    <title>TheNomadApp - Tu perfil</title>

    <style>
        :root {
            /* Colores ajustados a verde Nómada */
            --primary-color: #1cda6b;
            --primary-hover: #04b00f;
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
            /* Espacio para el footer */
        }

        .contenedorPerfil {
            background-color: var(--white);
            border-radius: var(--border-radius-lg);
            padding: 40px 30px;
            margin: 40px auto;
            max-width: 850px;
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
            box-shadow: 0 8px 20px rgba(129, 186, 24, 0.3);
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
            background-color: #f2f9e8;
            /* Fondo verde muy clarito */
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
            box-shadow: 0 4px 12px rgba(129, 186, 24, 0.3);
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

        /* Overlay de Carga */
        #loadingOverlay {
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

        /* Estilos Modales */
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
            padding: 12px 15px;
            background-color: #f8f9fa;
            border: 1px solid #e9ecef;
            font-weight: 600;
            color: var(--dark-text);
        }

        .form-control:focus {
            background-color: #fff;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(129, 186, 24, 0.25);
        }

        .form-control[readonly] {
            background-color: #e9ecef;
            color: #6c757d;
        }

        /* Personalización de la barra flotante (Toast) */
        .custom-toast {
            border-radius: var(--border-radius-sm);
            font-family: 'Nunito', sans-serif;
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
    <div id="loadingOverlay">
        <div class="spinner-border" role="status">
            <span class="visually-hidden">Cargando...</span>
        </div>
        <div class="loading-text">Cargando tu perfil...</div>
    </div>

    <div class="position-fixed top-0 end-0 p-3" style="z-index: 10050">
        <div id="liveToast" class="toast align-items-center text-white border-0 custom-toast" role="alert"
            aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body fw-bold" id="toastMessage">
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"
                    aria-label="Close"></button>
            </div>
        </div>
    </div>

    <div class="contenedorPerfil">
        <div class="fotoPerfilMovil">
            <div class="profile-image-container">
                <img id="fotoPerfilMovil" src="img/perfil.png" alt="Tu foto">
            </div>
        </div>

        <div class="perfilFotoBotones">
            <div class="profile-image-container">
                <img id="fotoPerfil" src="img/perfil.png" alt="Tu foto">
            </div>
            <button type="button" class="btn-custom btn-brand btn-change-image">
                <i class="fas fa-camera"></i> Cambiar imagen
            </button>
            <button type="button" class="btn-custom btn-brand btn-edit-profile">
                <i class="fas fa-user-edit"></i> Editar perfil
            </button>
            <button type="button" class="btn-custom btn-logout mt-2" onclick="location.href='logout.php'">
                <i class="fas fa-sign-out-alt"></i> Cerrar sesión
            </button>
        </div>

        <div class="perfilInfo">
            <div class="perfil-header">
                <h4>Detalles de la cuenta</h4>
            </div>

            <div class="info-card">
                <div class="info-icon"><i class="fas fa-user"></i></div>
                <div class="info-content">
                    <span class="info-label">Nombre completo</span>
                    <span class="info-value" id="val-nombre">...</span>
                </div>
            </div>

            <div class="info-card">
                <div class="info-icon"><i class="fas fa-envelope"></i></div>
                <div class="info-content">
                    <span class="info-label">Dirección E-mail</span>
                    <span class="info-value" id="val-email">...</span>
                </div>
            </div>

            <div class="info-card">
                <div class="info-icon"><i class="fas fa-phone-alt"></i></div>
                <div class="info-content">
                    <span class="info-label">Teléfono de contacto</span>
                    <span class="info-value" id="val-telefono">...</span>
                </div>
            </div>
        </div>

        <div class="botonesMovil">
            <button type="button" class="btn-custom btn-brand btn-change-image">
                <i class="fas fa-camera"></i> Cambiar imagen
            </button>
            <button type="button" class="btn-custom btn-brand btn-edit-profile">
                <i class="fas fa-user-edit"></i> Editar perfil
            </button>
            <button type="button" class="btn-custom btn-logout mt-3" onclick="location.href='logout.php'">
                <i class="fas fa-sign-out-alt"></i> Cerrar sesión
            </button>
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
                            <img id="previewImagen" src="img/perfil.png" alt="Vista previa">
                        </div>
                        <div class="text-start mb-2">
                            <label for="inputImagen"
                                class="form-label fw-bold text-muted small text-uppercase">Selecciona una imagen de tu
                                galería</label>
                            <input type="file" class="form-control" id="inputImagen" name="imagen" accept="image/*">
                            <input type="hidden" id="imagenAnfitrionId" name="anfitrionId" value="">
                        </div>
                    </form>
                </div>
                <div class="modal-footer d-flex justify-content-between">
                    <button type="button" class="btn btn-light rounded-pill px-4 fw-bold"
                        data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-brand rounded-pill px-4" style="width: auto; margin:0;"
                        id="btnGuardarImagen">Guardar foto</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editarPerfilModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Editar datos personales</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="formEditarPerfil">
                        <div class="mb-3">
                            <label for="editNombre" class="form-label text-muted small text-uppercase fw-bold">Nombre
                                completo</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-0"><i
                                        class="fas fa-user text-muted"></i></span>
                                <input type="text" class="form-control border-start-0 ps-0" id="editNombre"
                                    name="nombre">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="editEmail" class="form-label text-muted small text-uppercase fw-bold">E-mail (No
                                editable)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0"><i
                                        class="fas fa-envelope text-muted"></i></span>
                                <input type="email" class="form-control border-start-0 ps-0" id="editEmail" name="email"
                                    readonly>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="editTelefono"
                                class="form-label text-muted small text-uppercase fw-bold">Teléfono</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-0"><i
                                        class="fas fa-phone-alt text-muted"></i></span>
                                <input type="text" class="form-control border-start-0 ps-0" id="editTelefono"
                                    name="telefono">
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer d-flex justify-content-between">
                    <button type="button" class="btn btn-light rounded-pill px-4 fw-bold"
                        data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-brand rounded-pill px-4" style="width: auto; margin:0;"
                        id="btnGuardarCambios">Guardar cambios</button>
                </div>
            </div>
        </div>
    </div>

    <?php include 'footerNomada.php'; ?>

    <script>
        // Función para mostrar notificaciones estilo "App"
        function mostrarNotificacion(mensaje, tipo = 'success') {
            const toastEl = document.getElementById('liveToast');
            const toastMessage = document.getElementById('toastMessage');

            // Quitar clases anteriores
            toastEl.classList.remove('bg-success', 'bg-danger', 'bg-warning');

            // Asignar color dependiendo de si es éxito o error
            if (tipo === 'success') {
                // Usamos success nativo o podemos forzar un color
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

        document.addEventListener('DOMContentLoaded', function () {
            document.getElementById("loadingOverlay").style.display = "flex";

            const url = "getNomadaByEmailAsync.php";

            // Cargar datos
            fetch(url)
                .then(response => response.json())
                .then(data => {
                    if (data.unauthorized) {
                        location.href = "./logout.php";
                        return;
                    }
                    if (data.error) {
                        console.error("Error:", data.error);
                        return;
                    }

                    // Asignamos imágenes
                    const avatarUrl = data.avatar_url ? data.avatar_url : 'img/perfil.png';
                    document.getElementById("fotoPerfil").src = avatarUrl;
                    document.getElementById("fotoPerfilMovil").src = avatarUrl;
                    document.getElementById("previewImagen").src = avatarUrl;

                    // Asignamos textos a las nuevas tarjetas
                    document.getElementById("val-nombre").textContent = data.name || 'Sin nombre';
                    document.getElementById("val-email").textContent = data.email || 'Sin correo';
                    document.getElementById("val-telefono").textContent = data.telefono || 'Aún no especificado';

                    document.getElementById("loadingOverlay").style.display = "none";
                })
                .catch(err => {
                    console.log(err);
                    document.getElementById("loadingOverlay").style.display = "none";
                });

            // Modal Imagen
            const imageModal = new bootstrap.Modal(document.getElementById('cambiarImagenModal'));
            const changeImageButtons = document.querySelectorAll('.btn-change-image');
            changeImageButtons.forEach(button => {
                button.addEventListener('click', () => imageModal.show());
            });

            document.getElementById("inputImagen").addEventListener('change', function (event) {
                const file = event.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function (e) {
                        document.getElementById("previewImagen").src = e.target.result;
                    };
                    reader.readAsDataURL(file);
                }
            });

            document.getElementById("btnGuardarImagen").addEventListener('click', function () {
                const formData = new FormData();
                const inputImagen = document.getElementById("inputImagen");

                if (inputImagen.files.length === 0) {
                    mostrarNotificacion("Debes seleccionar una imagen primero", "error");
                    return;
                }

                formData.append('imagen', inputImagen.files[0]);

                this.disabled = true;
                this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';

                fetch("subir-imagen-minio.php", {
                    method: "POST",
                    body: formData
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            document.getElementById("fotoPerfil").src = data.avatarUrl;
                            document.getElementById("fotoPerfilMovil").src = data.avatarUrl;

                            // Reemplazamos el alert() por la notificación bonita
                            mostrarNotificacion("Imagen de perfil actualizada correctamente", "success");

                            const modal = bootstrap.Modal.getInstance(document.getElementById('cambiarImagenModal'));
                            modal.hide();
                        } else {
                            mostrarNotificacion(data.message, "error");
                        }
                    })
                    .catch(error => {
                        console.error("Error:", error);
                        mostrarNotificacion("Ha ocurrido un error al conectar con el servidor", "error");
                    })
                    .finally(() => {
                        this.disabled = false;
                        this.innerHTML = 'Guardar foto';
                    });
            });

            // Modal Perfil
            const editModal = new bootstrap.Modal(document.getElementById('editarPerfilModal'));
            const editProfileButtons = document.querySelectorAll('.btn-edit-profile');

            editProfileButtons.forEach(button => {
                button.addEventListener('click', () => {
                    cargarDatosModal();
                    editModal.show();
                });
            });

            document.getElementById("btnGuardarCambios").addEventListener("click", guardarCambios);
        });

        function cargarDatosModal() {
            document.getElementById("editNombre").value = document.getElementById("val-nombre").textContent;
            document.getElementById("editEmail").value = document.getElementById("val-email").textContent;

            let telefonoActual = document.getElementById("val-telefono").textContent;
            if (telefonoActual === 'Aún no especificado') telefonoActual = '';
            document.getElementById("editTelefono").value = telefonoActual;
        }

        function guardarCambios() {
            const formData = new FormData(document.getElementById("formEditarPerfil"));
            const btn = document.getElementById("btnGuardarCambios");

            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';

            fetch("actualizarNomada.php", {
                method: "POST",
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Actualizar UI
                        document.getElementById("val-nombre").textContent = formData.get('nombre') || 'Sin nombre';
                        document.getElementById("val-telefono").textContent = formData.get('telefono') || 'Aún no especificado';

                        // Reemplazamos el alert() por la notificación bonita
                        mostrarNotificacion("Perfil actualizado correctamente", "success");

                        const modal = bootstrap.Modal.getInstance(document.getElementById('editarPerfilModal'));
                        modal.hide();
                    } else {
                        mostrarNotificacion("Error al actualizar: " + data.message, "error");
                    }
                })
                .catch(error => {
                    console.error("Error:", error);
                    mostrarNotificacion("Ha ocurrido un error al guardar los cambios", "error");
                })
                .finally(() => {
                    btn.disabled = false;
                    btn.innerHTML = 'Guardar cambios';
                });
        }
    </script>
</body>

</html>