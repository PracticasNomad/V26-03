<?php
require_once 'verificar_sesion_gestor.php';
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-eOJMYsd53ii+scO/bJGFsiCZc+5NDVN2yr8+0RDqr0Ql0h+rP48ckxlpbzKgwra6" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-JEW9xMcG8R+pH31jmWH6WWP0WintQrMb4s7ZOdauHnUtxwoG2vI5DkLtS3qm9Ekf" crossorigin="anonymous">
    </script>
    <link href="../style.css" rel="stylesheet">
    <script src="https://kit.fontawesome.com/b8814a2854.js" crossorigin="anonymous"></script>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@200&display=swap" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.js"
        integrity="sha256-H+K7U5CnXl1h5ywQfKtSj8PCmoN9aaq30gDh27Xc0jk=" crossorigin="anonymous"></script>
    <link rel="icon" href="favicon-color.png">

    <link rel="icon" href="favicon-negro.png" media="(prefers-color-scheme: light)">

    <link rel="icon" href="favicon-color.png" media="(prefers-color-scheme: dark)">
    <title>TheNomadapp - Tu perfil</title>
    <style>
        :root {
            --primary-color: #007bff;
            --secondary-color: #6c757d;
            --cancel-color: #dc3545;
            --plan-color: #28a745;
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
            /* Increased top/bottom padding from 30px to 40px */
            margin: 30px auto;
            max-width: 900px;
            min-height: 600px;
            /* Added minimum height to make container taller */
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            /* Space between the two main columns */
            align-items: flex-start;
            /* Align items to the top */
        }

        .sombra {
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.08);
        }

        .fotoPerfilMovil {
            display: none;
            /* Hidden by default, shown on mobile */
        }

        .profile-image-container {
            width: 150px;
            height: 150px;
            overflow: hidden;
            border-radius: 50%;
            margin: 0 auto 15px auto;
            /* Centered, with margin below */
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
            /* Fixed width for this column */
            max-width: 250px;
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding-right: 15px;
            /* Add some spacing to the right of this column */
            height: 100%;
            /* Take full height of container */
        }

        .perfilInfo {
            flex-grow: 1;
            /* Allows this column to take up remaining space */
            min-width: 300px;
            text-align: left;
            padding-left: 15px;
            /* Add some spacing to the left of this column */
            height: 100%;
            /* Take full height of container */
            display: flex;
            flex-direction: column;
            /* Ensure items stack vertically */
        }

        .perfilInfo .info-item {
            background-color: var(--light-bg);
            padding: 15px 20px;
            /* Increased padding from 12px to 15px for more height */
            border-radius: var(--border-radius-md);
            /* More pronounced rounding */
            margin-bottom: 18px;
            /* Increased margin from 15px to 18px */
            font-size: 1.1em;
            display: flex;
            align-items: center;
            box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.05);
            /* Subtle inner shadow */
        }

        .perfilInfo .info-item:last-child {
            margin-bottom: 0;
            /* No margin for the last item */
        }

        .perfilInfo .h5 {
            margin-bottom: 30px !important;
            /* Increased margin from 25px to 30px */
            padding-bottom: 12px;
            /* Increased padding from 10px to 12px */
            border-bottom: 2px solid var(--primary-color);
            /* Underline with theme color */
            display: inline-block;
            /* To make border-bottom fit content */
        }

        .btn-primary,
        .btn-cancel,
        .btn-plan {
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 10px 15px;
            /* Increased padding from 8px to 10px for taller buttons */
            font-size: 0.95em;
            width: 100%;
            margin-top: 10px !important;
            /* Increased margin from 8px to 10px */
            border-radius: 50rem !important;
            /* Fully rounded buttons */
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

        .btn-plan {
            background-color: var(--plan-color);
            border-color: var(--plan-color);
            color: var(--white);
        }

        .btn-plan:hover {
            background-color: #218838;
            border-color: #218838;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        /* Footer styles remain largely the same, only adapting colors */
        .footer-container {
            background-color: var(--white);
            box-shadow: 0px -2px 10px rgba(0, 0, 0, 0.1);
            padding-top: 1px !important;
            padding-bottom: 1px !important;
            height: auto;
        }

        .footer-item {
            padding: 8px 0;
        }

        .icon-container {
            transition: transform 0.3s ease, color 0.3s ease;
            padding: 5px 0;
            color: #000000;
        }

        .footer-item:hover .icon-container {
            transform: translateY(-7px);
            color: var(--primary-color);
        }

        /* Removed the checked state styles for radio inputs */
        #anf:checked~#lbl_anf .icon-container,
        #val:checked~#lbl_val .icon-container,
        #res:checked~#lbl_res .icon-container,
        #his:checked~#lbl_his .icon-container,
        #esp:checked~#lbl_esp .icon-container,
        #per:checked~#lbl_per .icon-container {
            color: var(--primary-color);
        }

        /* Loading Overlay */
        #loadingContainer {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(255, 255, 255, 0.8);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }

        .loading-content {
            background-color: white;
            padding: 20px 40px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .spinner-border {
            margin-right: 10px;
        }

        /* Responsive Adjustments */
        @media (max-width: 768px) {
            .contenedorPerfil {
                flex-direction: column;
                align-items: center;
                padding: 25px 20px;
                /* Adjusted padding for mobile */
                min-height: auto;
                /* Remove min-height constraint on mobile */
            }

            /* Hide the right-side buttons on mobile directly in CSS */
            .perfilInfo+div.col-3 {
                /* Selects the column that used to hold the right buttons */
                display: none;
            }

            .fotoPerfilMovil {
                display: block;
                /* Show mobile profile image */
                width: 100%;
                text-align: center;
                margin-bottom: 25px;
                /* Increased margin from 20px to 25px */
            }

            .perfilInfo {
                width: 100%;
                min-width: unset;
                /* Remove min-width for mobile */
                padding-left: 0;
                padding-right: 0;
                margin-top: 25px;
                /* Increased margin from 20px to 25px */
                height: auto;
                /* Remove height constraint on mobile */
            }

            .botonesMovil {
                display: flex;
                /* Show mobile buttons */
                flex-direction: column;
                width: 100%;
                text-align: center;
                margin-top: 25px;
                /* Increased margin from 20px to 25px */
            }

            .perfilInfo .info-item {
                font-size: 1em;
                /* Adjust font size for mobile */
            }
        }
    </style>

</head>

<body>
    <div id="loadingContainer">
        <div class="loading-content">
            <div class="d-flex align-items-center">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Cargando...</span>
                </div>
                <span class="h5 ms-3 mb-0">Cargando Datos...</span>
            </div>
        </div>
    </div>

    <div class="contenedorPerfil sombra fw-bold">
        <div class="fotoPerfilMovil centrar">
            <div class="profile-image-container sombra mb-3">
                <img id="fotoPerfilMovil" src="../img/perfil.png" alt="Profile Image">
            </div>
            <button type="button" class="btn btn-primary rounded-pill mt-2 w-100" data-bs-toggle="modal"
                data-bs-target="#cambiarImagenModal">
                <i class="fas fa-camera"></i> Cambiar imágen
            </button>
            <button type="button" class="btn btn-primary rounded-pill mt-2 w-100 botonEditar" data-bs-toggle="modal"
                data-bs-target="#editarPerfilModal">
                <i class="fas fa-edit"></i> Editar perfil
            </button>
            <button type="button" class="btn btn-plan rounded-pill mt-2 w-100" onclick="cambiarPlan()">
                <i class="fas fa-exchange-alt"></i> Cambiar plan
            </button>
            <button type="button" class="btn btn-cancel rounded-pill mt-2 w-100"
                onclick="window.location.href='../cerrarSesion.php'">
                <i class="fas fa-sign-out-alt"></i> Cerrar sesión
            </button>
        </div>

        <div class="perfilFotoBotones">
            <div class="profile-image-container sombra mb-3">
                <img id="fotoPerfil" src="../img/perfil.png" alt="Profile Image">
            </div>
            <button type="button" class="btn btn-primary rounded-pill mt-2 w-100" data-bs-toggle="modal"
                data-bs-target="#cambiarImagenModal">
                <i class="fas fa-camera"></i> Cambiar imágen
            </button>
            <button type="button" class="btn btn-primary rounded-pill mt-2 w-100 botonEditar" data-bs-toggle="modal"
                data-bs-target="#editarPerfilModal">
                <i class="fas fa-edit"></i> Editar perfil
            </button>
            <button type="button" class="btn btn-plan rounded-pill mt-2 w-100" onclick="cambiarPlan()">
                <i class="fas fa-exchange-alt"></i> Cambiar plan
            </button>
            <button type="button" class="btn btn-cancel rounded-pill mt-2 w-100"
                onclick="window.location.href='../cerrarSesion.php'">
                <i class="fas fa-sign-out-alt"></i> Cerrar sesión
            </button>
        </div>

        <div class="perfilInfo">
            <p class="h5 fw-bold mb-3"><u>Tu perfil de gestora:</u></p>

            <div id="nombre" class="info-item">Nombre: </div>
            <div id="email" class="info-item">E-mail: </div>
            <div id="telefono" class="info-item">Teléfono: </div>
            <div id="empresa" class="info-item">Empresa: </div>
            <div id="cif" class="info-item">CIF: </div>
            <div id="domicilioSocial" class="info-item">Domicilio social: </div>
            <div id="codigoPostal" class="info-item">Código Postal: </div>
            <div id="plan" class="info-item">Plan: </div>
            <div id="finPlan" class="info-item">Fin del plan: </div>

            <input type="text" class="form-control" id="anfitrionId" name="anfitrionId" hidden>
        </div>

        <div class="col-3 d-none d-md-block">
        </div>

    </div>


    <div class="container-fluid footer mt-5 p-3">
        <div class="row text-center fixed-bottom bg-blanco pt-1 px-2 footer-container">
            <label for="anf" id="lbl_anf" class="col-2 text-center footer-item">
                <div class="row">
                    <a href="Anfitriones.php">
                        <div class="col-12 icon-container">
                            <i class="h2 fas fa-users p-1 m-0"></i>
                            <div>Anfitriones</div>
                        </div>
                    </a>
                </div>
            </label>

            <label for="val" id="lbl_val" class="col-2 text-center footer-item">
                <div class="row">
                    <a href="verValidar.php">
                        <div class="col-12 icon-container">
                            <i class="h2 fas fa-check-circle p-1 m-0"></i>
                            <div>Validar</div>
                        </div>
                    </a>
                </div>
            </label>

            <label for="res" id="lbl_res" class="col-2 text-center footer-item">
                <div class="row">
                    <a href="verReservas.php">
                        <div class="col-12 icon-container">
                            <i class="h2 fas fa-book-open p-1 m-0"></i>
                            <div>Reservas</div>
                        </div>
                    </a>
                </div>
            </label>
            <label for="his" id="lbl_his" class="col-2 text-center footer-item">
                <div class="row">
                    <a href="verEstablecimientos.php">
                        <div class="col-12 icon-container">
                            <i class="h2 fas fa-building p-1 m-0"></i>
                            <div>Establecimientos</div>
                        </div>
                    </a>
                </div>
            </label>
            <label for="esp" id="lbl_esp" class="col-2 text-center footer-item">
                <div class="row">
                    <a href="verEspacios.php">
                        <div class="col-12 icon-container">
                            <i class="h2 fas fa-chair p-1 m-0"></i>
                            <div>Espacios</div>
                        </div>
                    </a>
                </div>
            </label>
            <label for="per" id="lbl_per" class="col-2 text-center footer-item">
                <div class="row">
                    <a href="tuPerfil.php">
                        <div class="col-12 icon-container">
                            <i class="fas fa-user-tie p-1 m-0"></i>
                            <div>Perfil</div>
                        </div>
                    </a>
                </div>
            </label>
        </div>
    </div>

    <div class="modal fade" id="editarPerfilModal" tabindex="-1" aria-labelledby="editarPerfilModalLabel"
        aria-hidden="true">
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
                            <input disabled type="email" class="form-control" id="editEmail" name="email">
                        </div>
                        <div class="mb-3">
                            <label for="editEmpresa" class="form-label fw-bold">Empresa:</label>
                            <input type="text" class="form-control" id="editEmpresa" name="empresa">
                        </div>
                        <div class="mb-3">
                            <label for="editTelefono" class="form-label fw-bold">Teléfono:</label>
                            <input type="text" class="form-control" id="editTelefono" name="telefono">
                        </div>
                        <div class="mb-3">
                            <label for="editCIF" class="form-label fw-bold">CIF:</label>
                            <input type="text" class="form-control" id="editCIF" name="cif">
                        </div>
                        <div class="mb-3">
                            <label for="editDomicilioSocial" class="form-label fw-bold">Domicilio social:</label>
                            <input type="text" class="form-control" id="editDomicilioSocial" name="domicilio_social">
                        </div>
                        <div class="mb-3">
                            <label for="editCodigoPostal" class="form-label fw-bold">Código Postal:</label>
                            <input type="text" class="form-control" id="editCodigoPostal" name="codigo_postal">
                        </div>
                        <input type="hidden" id="editAnfitrionId" name="anfitrionId">
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-cancel rounded-pill px-4"
                        data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary rounded-pill px-4" id="btnGuardarCambios">Guardar
                        cambios</button>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="cambiarImagenModal" tabindex="-1" aria-labelledby="cambiarImagenModalLabel"
        aria-hidden="true">
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
                                <img id="previewImagen" src="../img/perfil.png" alt="Imagen de perfil">
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
                    <button type="button" class="btn btn-cancel rounded-pill px-4"
                        data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary rounded-pill px-4" id="btnGuardarImagen">Guardar
                        cambios</button>
                </div>
            </div>
        </div>
    </div>
    <script>
        function cargarDatosModal() {
            document.getElementById("editNombre").value = document.getElementById("nombre").textContent.replace("Nombre: ", "").trim();
            document.getElementById("editEmail").value = document.getElementById("email").textContent.replace("E-mail: ", "").trim();
            document.getElementById("editEmpresa").value = document.getElementById("empresa").textContent.replace("Empresa: ", "").trim();
            document.getElementById("editTelefono").value = document.getElementById("telefono").textContent.replace("Teléfono: ", "").trim();
            document.getElementById("editCIF").value = document.getElementById("cif").textContent.replace("CIF: ", "").trim();
            document.getElementById("editDomicilioSocial").value = document.getElementById("domicilioSocial").textContent.replace("Domicilio social: ", "").trim();
            document.getElementById("editCodigoPostal").value = document.getElementById("codigoPostal").textContent.replace("Código Postal: ", "").trim(); // Added
            document.getElementById("editAnfitrionId").value = document.getElementById("anfitrionId").value;
        }

        function cambiarPlan() {
            // Aquí puedes redirigir a la página de cambio de plan
            window.location.href = 'Suscripciones.php';
        }

        document.addEventListener("DOMContentLoaded", function() {
            const botonesEditar = document.querySelectorAll('.botonEditar');

            botonesEditar.forEach(function(boton) {
                boton.setAttribute('data-bs-toggle', 'modal');
                boton.setAttribute('data-bs-target', '#editarPerfilModal');
                boton.addEventListener('click', cargarDatosModal);
            });

            document.getElementById("btnGuardarCambios").addEventListener("click", function() {
                guardarCambios();
            });
        });

        function guardarCambios() {
            const formData = new FormData(document.getElementById("formEditarPerfil"));

            fetch("actualizarAnfitrion.php", {
                    method: "POST",
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert("Perfil actualizado correctamente");

                        location.reload();
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
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const botonesCambiarImagen = document.querySelectorAll('.btn.btn-primary.rounded-pill.mt-2.w-100'); // Updated selector

            botonesCambiarImagen.forEach(function(boton) {
                // Check if the button's inner HTML contains the camera icon or the specific text
                if (boton.innerHTML.includes('<i class="fas fa-camera"></i>') || boton.textContent.trim().includes("Cambiar imágen")) {
                    boton.setAttribute('data-bs-toggle', 'modal');
                    boton.setAttribute('data-bs-target', '#cambiarImagenModal');
                    boton.addEventListener('click', prepararModalImagen);
                }
            });

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

            document.getElementById('btnGuardarImagen').addEventListener('click', function() {
                guardarImagen();
            });
        });

        function prepararModalImagen() {
            document.getElementById('imagenAnfitrionId').value = document.getElementById('anfitrionId').value;
            document.getElementById('previewImagen').src = document.getElementById('fotoPerfil').src; // Set current profile image as preview
            document.getElementById('inputImagen').value = ""; // Clear the input
        }

        function guardarImagen() {
            const formData = new FormData();
            const inputImagen = document.getElementById("inputImagen");

            if (inputImagen.files.length === 0) {
                alert("Debes seleccionar una imagen");
                return;
            }

            formData.append('imagen', inputImagen.files[0]);
            formData.append('anfitrionId', document.getElementById('imagenAnfitrionId').value); // Add anfitrionId to formData

            document.getElementById("btnGuardarImagen").disabled = true;
            document.getElementById("btnGuardarImagen").textContent = "Guardando...";

            fetch("subir-imagen-perfil-host.php", {
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
        }
    </script>

    <script>
        const url = "../getAnfitrionByEmailAsync.php";

        fetch(url)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok ' + response.statusText);
                }
                return response.json();
            })
            .then(data => {
                console.log(data);
                document.getElementById("fotoPerfil").src = data.avatar_url;
                document.getElementById("fotoPerfilMovil").src = data.avatar_url;

                var nombre = document.getElementById("nombre");
                nombre.textContent += " " + data.name;
                var email = document.getElementById("email");
                email.innerText += " " + data.email;
                var nombreempresa = document.getElementById("empresa");
                nombreempresa.innerText += " " + data.empresa;
                var telefono = document.getElementById("telefono");
                telefono.innerText += " " + data.phone
                var cif = document.getElementById("cif");
                cif.innerText += " " + data.cif;
                var domicilioSocial = document.getElementById("domicilioSocial");
                domicilioSocial.innerText += " " + data.domicilio_social;
                var codigoPostal = document.getElementById("codigoPostal");
                codigoPostal.innerText += " " + data.codigo_postal;
                var plan = document.getElementById("plan");
                plan.innerText += " " + data.plan;
                var finPlan = document.getElementById("finPlan");
                finPlan.innerText += " " + data.fin_plan;

                // Guardar el ID del anfitrión
                document.getElementById("anfitrionId").value = data.id;

                // Ocultar el loading
                document.getElementById("loadingContainer").style.display = "none";
            })
            .catch(error => {
                console.error('There was a problem with the fetch operation:', error);
                document.getElementById("loadingContainer").style.display = "none";
                alert("Error al cargar los datos del perfil");
            });
    </script>

</body>

</html>