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
    <link href="../style.css" rel="stylesheet">
    <script src="https://kit.fontawesome.com/b8814a2854.js" crossorigin="anonymous"></script>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@200&display=swap" rel="stylesheet">
    <link rel="icon" href="../img/favicon-color.png">
    <title>TheNomadapp - Tu perfil Gestora</title>
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
            display: flex;
            flex-direction: column;
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
        .btn-cancel,
        .btn-plan {
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

        #anf:checked~#lbl_anf .icon-container,
        #val:checked~#lbl_val .icon-container,
        #res:checked~#lbl_res .icon-container,
        #his:checked~#lbl_his .icon-container,
        #esp:checked~#lbl_esp .icon-container,
        #per:checked~#lbl_per .icon-container {
            color: var(--primary-color);
        }

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

        @media (max-width: 768px) {
            .contenedorPerfil {
                flex-direction: column;
                align-items: center;
                padding: 25px 20px;
                min-height: auto;
            }

            .perfilInfo+div.col-3 {
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
    <div class="contenedorPerfil sombra fw-bold mt-5">
        <div class="fotoPerfilMovil centrar">
            <div class="profile-image-container sombra mb-3">
                <img id="fotoPerfilMovil" src="<?= htmlspecialchars($gestora['avatar_url'] ?? '../img/perfil.png') ?>"
                    alt="Profile Image">
            </div>
            <button type="button" class="btn btn-primary rounded-pill mt-2 w-100" data-bs-toggle="modal"
                data-bs-target="#cambiarImagenModal">
                <i class="fas fa-camera"></i> Cambiar imágen
            </button>
            <button type="button" class="btn btn-primary rounded-pill mt-2 w-100 botonEditar" data-bs-toggle="modal"
                data-bs-target="#editarPerfilModal">
                <i class="fas fa-edit"></i> Editar perfil
            </button>
            <button type="button" class="btn btn-plan rounded-pill mt-2 w-100"
                onclick="window.location.href='Suscripciones.php'">
                <i class="fas fa-exchange-alt"></i> Cambiar plan
            </button>
            <button type="button" class="btn btn-cancel rounded-pill mt-2 w-100"
                onclick="window.location.href='cerrarSesion.php'">
                <i class="fas fa-sign-out-alt"></i> Cerrar sesión
            </button>
        </div>

        <div class="perfilFotoBotones">
            <div class="profile-image-container sombra mb-3">
                <img id="fotoPerfil" src="<?= htmlspecialchars($gestora['avatar_url'] ?? '../img/perfil.png') ?>"
                    alt="Profile Image">
            </div>
            <button type="button" class="btn btn-primary rounded-pill mt-2 w-100" data-bs-toggle="modal"
                data-bs-target="#cambiarImagenModal">
                <i class="fas fa-camera"></i> Cambiar imágen
            </button>
            <button type="button" class="btn btn-primary rounded-pill mt-2 w-100 botonEditar" data-bs-toggle="modal"
                data-bs-target="#editarPerfilModal">
                <i class="fas fa-edit"></i> Editar perfil
            </button>
            <button type="button" class="btn btn-plan rounded-pill mt-2 w-100"
                onclick="window.location.href='Suscripciones.php'">
                <i class="fas fa-exchange-alt"></i> Cambiar plan
            </button>
            <button type="button" class="btn btn-cancel rounded-pill mt-2 w-100"
                onclick="window.location.href='cerrarSesion.php'">
                <i class="fas fa-sign-out-alt"></i> Cerrar sesión
            </button>
        </div>

        <div class="perfilInfo">
            <p class="h5 fw-bold mb-3"><u>Tu perfil de gestora:</u></p>

            <div id="nombre" class="info-item">Nombre: <?= htmlspecialchars($gestora['name'] ?? '') ?></div>
            <div id="email" class="info-item">E-mail: <?= htmlspecialchars($gestora['email'] ?? '') ?></div>
            <div id="telefono" class="info-item">Teléfono: <?= htmlspecialchars($gestora['phone'] ?? '') ?></div>
            <div id="empresa" class="info-item">Empresa: <?= htmlspecialchars($gestora['empresa'] ?? '') ?></div>
            <div id="cif" class="info-item">CIF/NIF: <?= htmlspecialchars($gestora['cif'] ?? $gestora['nif'] ?? '') ?>
            </div>

            <div id="direccion" class="info-item">Dirección: <?= htmlspecialchars($gestora['direccion'] ?? '') ?></div>
            <div id="localidad" class="info-item">Localidad: <?= htmlspecialchars($gestora['localidad'] ?? '') ?></div>
            <div id="provincia" class="info-item">Provincia: <?= htmlspecialchars($gestora['provincia'] ?? '') ?></div>

            <div id="codigoPostal" class="info-item">Código Postal:
                <?= htmlspecialchars($gestora['codigo_postal'] ?? '') ?>
            </div>
            <div id="plan" class="info-item">Plan: <?= htmlspecialchars($gestora['plan'] ?? 'Básico') ?></div>

            <div id="finPlan" class="info-item">
                <i class="fas fa-calendar-alt text-primary me-2"></i> <strong>Fin del plan:</strong>
                &nbsp;<?= !empty($gestora['fin_plan']) ? date('d/m/Y', strtotime($gestora['fin_plan'])) : 'Pendiente de asignar' ?>
            </div>

            <input type="hidden" id="gestorId" value="<?= htmlspecialchars($gestora['id'] ?? '') ?>">
        </div>
    </div>

    <div class="modal fade" id="editarPerfilModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Editar perfil</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="formEditarPerfil">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Nombre:</label>
                            <input type="text" class="form-control" id="editNombre" name="nombre">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">E-mail (No editable):</label>
                            <input disabled type="email" class="form-control" id="editEmail">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Empresa:</label>
                            <input type="text" class="form-control" id="editEmpresa" name="empresa">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Teléfono:</label>
                            <input type="text" class="form-control" id="editTelefono" name="telefono">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">CIF/NIF:</label>
                            <input type="text" class="form-control" id="editCIF" name="cif">
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Dirección:</label>
                            <input type="text" class="form-control" id="editDireccion" name="direccion">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Localidad:</label>
                            <input type="text" class="form-control" id="editLocalidad" name="localidad">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Provincia:</label>
                            <input type="text" class="form-control" id="editProvincia" name="provincia">
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Código Postal:</label>
                            <input type="text" class="form-control" id="editCodigoPostal" name="codigo_postal">
                        </div>
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
    <div class="modal fade" id="cambiarImagenModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Cambiar imagen de perfil</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="formCambiarImagen">
                        <div class="text-center mb-4">
                            <div class="profile-image-container mx-auto">
                                <img id="previewImagen"
                                    src="<?= htmlspecialchars($gestora['avatar_url'] ?? '../img/perfil.png') ?>"
                                    alt="Imagen de perfil">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="inputImagen" class="form-label fw-bold">Seleccionar nueva imagen:</label>
                            <input type="file" class="form-control" id="inputImagen" name="imagen" accept="image/*">
                            <input type="hidden" id="imagenGestorId" name="gestorId"
                                value="<?= htmlspecialchars($gestora['id'] ?? '') ?>">
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
        
        <?php include 'footer.php'; ?>
    <script>
        // Cargar los datos en el modal cuando se abre
        document.querySelectorAll('.botonEditar').forEach(boton => {
            boton.addEventListener('click', function () {
                document.getElementById("editNombre").value = "<?= htmlspecialchars($gestora['name'] ?? '') ?>";
                document.getElementById("editEmail").value = "<?= htmlspecialchars($gestora['email'] ?? '') ?>";
                document.getElementById("editEmpresa").value = "<?= htmlspecialchars($gestora['empresa'] ?? '') ?>";
                document.getElementById("editTelefono").value = "<?= htmlspecialchars($gestora['phone'] ?? '') ?>";
                document.getElementById("editCIF").value = "<?= htmlspecialchars($gestora['cif'] ?? $gestora['nif'] ?? '') ?>";
                document.getElementById("editCodigoPostal").value = "<?= htmlspecialchars($gestora['codigo_postal'] ?? '') ?>";

                // Cargar los 3 campos nuevos en el modal
                document.getElementById("editDireccion").value = "<?= htmlspecialchars($gestora['direccion'] ?? '') ?>";
                document.getElementById("editLocalidad").value = "<?= htmlspecialchars($gestora['localidad'] ?? '') ?>";
                document.getElementById("editProvincia").value = "<?= htmlspecialchars($gestora['provincia'] ?? '') ?>";
            });
        });

        // Guardar cambios apuntando al nuevo archivo
        document.getElementById("btnGuardarCambios").addEventListener("click", function () {
            const formData = new FormData(document.getElementById("formEditarPerfil"));
            const btn = this;
            btn.disabled = true;
            btn.textContent = "Guardando...";

            fetch("actualizarGestor.php", {
                method: "POST",
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert("Perfil actualizado correctamente");
                        location.reload();
                    } else {
                        alert("Error: " + data.message);
                        btn.disabled = false;
                        btn.textContent = "Guardar cambios";
                    }
                })
                .catch(error => {
                    console.error("Error:", error);
                    alert("Ha ocurrido un error de conexión.");
                    btn.disabled = false;
                    btn.textContent = "Guardar cambios";
                });
        });

        // --- SCRIPT PARA CAMBIAR IMAGEN ---
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

        document.getElementById('btnGuardarImagen').addEventListener('click', function () {
            const formData = new FormData();
            const inputImagen = document.getElementById("inputImagen");

            if (inputImagen.files.length === 0) {
                alert("Debes seleccionar una imagen");
                return;
            }

            formData.append('imagen', inputImagen.files[0]);
            formData.append('gestorId', document.getElementById('imagenGestorId').value);

            const btn = this;
            btn.disabled = true;
            btn.textContent = "Guardando...";

            fetch("subir-imagen-perfil-gestor.php", {
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
                    btn.disabled = false;
                    btn.textContent = "Guardar cambios";
                });
        });
    </script>

</body>

</html>