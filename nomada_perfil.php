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
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@200&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-+0n0xVW2eSR5OomGNYDnhzAbDsOXxcvSN1TPprVMTNDbiYZCxYbOOl7+AMvyTG2x" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.1/dist/js/bootstrap.bundle.min.js" integrity="sha384-gtEjrD/SeCtmISkJkNUaaKMoLD0//ElJ19smozuHV6z3Iehds+3Ulb9Bn9Plx0x4" crossorigin="anonymous"></script>
    <link rel="icon" href="favicon-color.png">

    <link rel="icon" href="favicon-negro.png" media="(prefers-color-scheme: light)">

    <link rel="icon" href="favicon-color.png" media="(prefers-color-scheme: dark)">
    <title>TheNomadApp - Tu perfil</title>

    <style>
        :root {
            --primary-color: #007bff;
            --secondary-color: #6c757d;
            --cancel-color: #dc3545;
            --light-bg: #f8f9fa;
            --white: #ffffff;
            --dark-text: #343a40;
            --border-radius-lg: 15px;
            --border-radius-md: 10px;
            --border-radius-sm: 8px;
        }

        body {
            font-family: 'Nunito', sans-serif;
            background-color: var(--light-bg);
            color: var(--dark-text);
        }

        .contenedorPerfil {
            background-color: var(--white);
            border-radius: var(--border-radius-lg);
            padding: 40px 30px;
            margin: 30px auto;
            max-width: 900px;
            min-height: 600px;
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: flex-start;
        }

        .sombra {
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.08);
        }

        .fotoPerfilMovil {
            display: none;
        }

        .profile-image-container {
            width: 150px;
            height: 150px;
            overflow: hidden;
            border-radius: 50%;
            margin: 0 auto 15px auto;
            border: 4px solid var(--primary-color);
            flex-shrink: 0;
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
            padding-right: 15px;
            height: 100%;
        }

        .perfilInfo {
            flex-grow: 1;
            min-width: 300px;
            text-align: left;
            padding-left: 15px;
            height: 100%;
        }

        .perfilInfo .info-item {
            background-color: var(--light-bg);
            padding: 15px 20px;
            border-radius: var(--border-radius-md);
            margin-bottom: 18px;
            font-size: 1.1em;
            display: flex;
            align-items: center;
            box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.05);
        }

        .perfilInfo .info-item:last-child {
            margin-bottom: 0;
        }

        .perfilInfo .h5 {
            margin-bottom: 30px !important;
            padding-bottom: 12px;
            border-bottom: 2px solid var(--primary-color);
            display: inline-block;
        }

        .btn-primary,
        .btn-cancel {
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 10px 15px;
            font-size: 0.95em;
            width: 100%;
            margin-top: 10px !important;
            border-radius: 50rem !important;
        }

        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            color: var(--white);
        }

        .btn-primary:hover {
            background-color: #0056b3;
            border-color: #0056b3;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        .btn-cancel {
            background-color: var(--cancel-color);
            border-color: var(--cancel-color);
            color: var(--white);
        }

        .btn-cancel:hover {
            background-color: #c82333;
            border-color: #c82333;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        .footer {
            width: 100%;
            left: 0;
            right: 0;
            -webkit-user-select: none;
            -ms-user-select: none;
            user-select: none;
            bottom: 0;
            color: black;
            font-size: 15px;
            background: white;
            text-align: center;
            position: fixed;
            padding: 10px 0;
            box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.1);
            z-index: 1000;
            margin: 0;
        }

        .footer i {
            font-size: 2em;
        }

        .footer input[type="radio"] {
            display: none;
        }

        .footer-item {
            height: 60px;
        }

        .footer-label {
            display: block;
            padding: 5px 0;
            cursor: pointer;
        }

        .footer-link {
            display: block;
            text-decoration: none;
            color: black;
            transition: all 0.3s ease;
            padding: 5px 0;
        }

        .footer-link:hover {
            transform: translateY(-7px);
        }

        .footer-icon {
            margin-bottom: 3px;
        }

        #marcado {
            color: #81ba18;
        }

        .footer .row {
            margin: 0;
            width: 100%;
        }

        #loadingOverlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(255, 255, 255, 0.85);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            flex-direction: column;
        }

        .spinner-border {
            width: 3rem;
            height: 3rem;
            margin-bottom: 1rem;
        }

        .loading-text {
            font-size: 1.2rem;
            font-weight: bold;
        }

        .botonesMovil {
            display: none;
        }

        @media (max-width: 768px) {
            .contenedorPerfil {
                flex-direction: column;
                align-items: center;
                padding: 25px 20px;
                min-height: auto;
            }

            .perfilFotoBotones {
                display: none;
            }

            .fotoPerfilMovil {
                display: block;
                width: 100%;
                text-align: center;
                margin-bottom: 25px;
            }

            .perfilInfo {
                width: 100%;
                min-width: unset;
                padding-left: 0;
                padding-right: 0;
                margin-top: 25px;
                height: auto;
            }

            .botonesMovil {
                display: flex;
                flex-direction: column;
                width: 100%;
                text-align: center;
                margin-top: 25px;
            }

            .perfilInfo .info-item {
                font-size: 1em;
            }
        }
    </style>
</head>

<body>
    <div id="loadingOverlay">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Cargando...</span>
        </div>
        <div class="loading-text">Cargando Datos...</div>
    </div>

    <div class="contenedorPerfil sombra fw-bold">
        <div class="fotoPerfilMovil centrar">
            <div class="profile-image-container sombra mb-3">
                <img id="fotoPerfilMovil" src="img/perfil.png" alt="">
            </div>
        </div>

        <div class="perfilFotoBotones">
            <div class="profile-image-container sombra mb-3">
                <img id="fotoPerfil" src="img/perfil.png" alt="">
            </div>
            <button type="button" class="btn btn-primary rounded-pill mt-2 w-100 btn-change-image">
                <i class="fas fa-camera"></i> Cambiar imágen
            </button>
            <button type="button" class="btn btn-primary rounded-pill mt-2 w-100">
                <i class="fas fa-edit"></i> Editar perfil
            </button>
            <button type="button" class="btn btn-cancel rounded-pill mt-2 w-100" onclick="location.href='logout.php'">
                <i class="fas fa-sign-out-alt"></i> Cerrar sesión
            </button>
        </div>

        <div class="perfilInfo">
            <p class="h5 fw-bold mb-3"><u>Tu perfil:</u></p>
            <div id="nombre" class="info-item">Nombre: </div>
            <div id="email" class="info-item">E-mail: </div>
            <div id="telefono" class="info-item">Teléfono: </div>
        </div>

        <div class="botonesMovil">
            <button type="button" class="btn btn-primary rounded-pill mt-2 w-100 btn-change-image">
                <i class="fas fa-camera"></i> Cambiar imágen
            </button>
            <button type="button" class="btn btn-primary rounded-pill mt-2 w-100">
                <i class="fas fa-edit"></i> Editar perfil
            </button>
            <button type="button" class="btn btn-cancel rounded-pill mt-2 w-100" onclick="location.href='logout.php'">
                <i class="fas fa-sign-out-alt"></i> Cerrar sesión
            </button>
        </div>
    </div>

    <div class="modal fade" id="cambiarImagenModal" tabindex="-1" aria-labelledby="cambiarImagenModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="cambiarImagenModalLabel">Cambiar imagen de perfil</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="formCambiarImagen">
                        <div class="text-center mb-4">
                            <div class="profile-image-container mx-auto">
                                <img id="previewImagen" src="img/perfil.png" alt="Imagen de perfil">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="inputImagen" class="form-label fw-bold">Seleccionar nueva imagen:</label>
                            <input type="file" class="form-control" id="inputImagen" name="imagen" accept="image/*">
                            <input type="hidden" id="imagenAnfitrionId" name="anfitrionId" value="">
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-cancel rounded-pill px-4" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary rounded-pill px-4" id="btnGuardarImagen">Guardar cambios</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editarPerfilModal" tabindex="-1" aria-labelledby="editarPerfilModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="editarPerfilModalLabel">Editar perfil</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="formEditarPerfil">
                        <div class="mb-3">
                            <label for="editNombre" class="form-label fw-bold">Nombre:</label>
                            <input type="text" class="form-control" id="editNombre" name="nombre">
                        </div>
                        <div class="mb-3">
                            <label for="editEmail" class="form-label fw-bold">E-mail:</label>
                            <input type="email" class="form-control" id="editEmail" name="email" readonly>
                        </div>
                        <div class="mb-3">
                            <label for="editTelefono" class="form-label fw-bold">Teléfono:</label>
                            <input type="text" class="form-control" id="editTelefono" name="telefono">
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-cancel rounded-pill px-4" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary rounded-pill px-4" id="btnGuardarCambios">Guardar cambios</button>
                </div>
            </div>
        </div>
    </div>

    <div class="footer">
        <div class="row g-0">
            <input type="radio" name="footer" id="exp">
            <input type="radio" name="footer" id="res">
            <input type="radio" name="footer" id="per">
            <div class="col-4 text-center footer-item">
                <label for="exp" id="lbl_exp" class="w-100 h-100 footer-label">
                    <a href="nomada_explorar.php" class="footer-link">
                        <div class="footer-icon"><i class="fas fa-search-location"></i></div>
                        <div class="fw-bold">Explorar</div>
                    </a>
                </label>
            </div>

            <div class="col-4 text-center footer-item">
                <label for="res" id="lbl_res" class="w-100 h-100 footer-label">
                    <a href="nomada_reservas.php" class="footer-link">
                        <div class="footer-icon"><i class="fas fa-book"></i></div>
                        <div class="fw-bold">Reservas</div>
                    </a>
                </label>
            </div>

            <div class="col-4 text-center footer-item">
                <label for="per" id="lbl_per" class="w-100 h-100 footer-label">
                    <a href="nomada_perfil.php" class="footer-link" id="marcado">
                        <div class="footer-icon"><i class="fas fa-user-tie"></i></div>
                        <div class="fw-bold">Perfil</div>
                    </a>
                </label>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById("loadingOverlay").style.display = "flex";

            const url = "getNomadaByEmailAsync.php";

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
                    document.getElementById("fotoPerfil").src = data.avatar_url;
                    document.getElementById("fotoPerfilMovil").src = data.avatar_url;
                    document.getElementById("previewImagen").src = data.avatar_url;
                    document.getElementById("nombre").innerHTML = "Nombre: " + data.name;
                    document.getElementById("email").innerHTML = "E-mail: " + data.email;
                    document.getElementById("telefono").innerHTML = "Teléfono: " + data.telefono;

                    document.getElementById("loadingOverlay").style.display = "none";
                })
                .catch(err => {
                    console.log(err);
                    document.getElementById("loadingOverlay").style.display = "none";
                });

            const imageModal = new bootstrap.Modal(document.getElementById('cambiarImagenModal'));

            const changeImageButtons = document.querySelectorAll('.btn-change-image');
            changeImageButtons.forEach(button => {
                button.addEventListener('click', function() {
                    imageModal.show();
                });
            });

            document.getElementById("inputImagen").addEventListener('change', function(event) {
                const file = event.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        document.getElementById("previewImagen").src = e.target.result;
                    };
                    reader.readAsDataURL(file);
                }
            });

            document.getElementById("btnGuardarImagen").addEventListener('click', function() {
                const formData = new FormData();
                const inputImagen = document.getElementById("inputImagen");

                if (inputImagen.files.length === 0) {
                    alert("Debes seleccionar una imagen");
                    return;
                }

                formData.append('imagen', inputImagen.files[0]);

                document.getElementById("btnGuardarImagen").disabled = true;
                document.getElementById("btnGuardarImagen").textContent = "Guardando...";

                fetch("subir-imagen-minio.php", {
                        method: "POST",
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            document.getElementById("fotoPerfil").src = data.avatarUrl;
                            document.getElementById("fotoPerfilMovil").src = data.avatarUrl;

                            alert("Imagen de perfil actualizada correctamente");

                            const modal = bootstrap.Modal.getInstance(document.getElementById('cambiarImagenModal'));
                            modal.hide();
                        } else {
                            alert("Error: " + data.message);
                        }
                    })
                    .catch(error => {
                        console.error("Error:", error);
                        alert("Ha ocurrido un error al guardar la imagen");
                    })
                    .finally(() => {
                        document.getElementById("btnGuardarImagen").disabled = false;
                        document.getElementById("btnGuardarImagen").textContent = "Guardar cambios";
                    });
            });

            const editProfileButtons = document.querySelectorAll('button.btn.btn-primary');

            editProfileButtons.forEach(function(button) {
                if (button.textContent.trim().includes("Editar perfil")) {
                    button.setAttribute('data-bs-toggle', 'modal');
                    button.setAttribute('data-bs-target', '#editarPerfilModal');
                    button.addEventListener('click', cargarDatosModal);
                    button.classList.add('btn-edit-profile');
                }
            });

            document.getElementById("btnGuardarCambios").addEventListener("click", function() {
                guardarCambios();
            });
        });

        function cargarDatosModal() {
            document.getElementById("editNombre").value = document.getElementById("nombre").textContent.replace("Nombre: ", "").trim();
            document.getElementById("editEmail").value = document.getElementById("email").textContent.replace("E-mail: ", "").trim();
            document.getElementById("editTelefono").value = document.getElementById("telefono").textContent.replace("Teléfono: ", "").trim();
        }

        function guardarCambios() {
            const formData = new FormData(document.getElementById("formEditarPerfil"));

            fetch("actualizarNomada.php", {
                    method: "POST",
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert("Perfil actualizado correctamente");

                        document.getElementById("nombre").innerHTML = "Nombre: " + formData.get('nombre');
                        document.getElementById("email").innerHTML = "E-mail: " + formData.get('email');
                        document.getElementById("telefono").innerHTML = "Teléfono: " + formData.get('telefono');

                        const modal = bootstrap.Modal.getInstance(document.getElementById('editarPerfilModal'));
                        modal.hide();
                    } else {
                        alert("Error al actualizar el perfil: " + data.message);
                    }
                })
                .catch(error => {
                    console.error("Error:", error);
                    alert("Ha ocurrido un error al guardar los cambios");
                });
        }
    </script>
</body>

</html>