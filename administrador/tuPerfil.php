<?php
session_start();

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

// Si existe en la BD, recogemos sus datos. Si no, redirigimos al login (algo fue mal)
if (is_array($datosAdmin) && count($datosAdmin) > 0) {
    $admin = $datosAdmin[0];
} else {
    // Seguridad extra: Si tiene sesión pero no existe en la BD, lo echamos
    session_destroy();
    header('Location: inicio_sesion_admin.php?error=no_data');
    exit();
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
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@200;400;600;700&display=swap" rel="stylesheet">
    <link rel="icon" href="../favicon-color.png">
    <link rel="icon" href="../favicon-negro.png" media="(prefers-color-scheme: light)">
    <link rel="icon" href="../favicon-color.png" media="(prefers-color-scheme: dark)">
    <title>TheNomadapp - Panel Administrador</title>
    <style>
        :root {
            --primary-color: #dc3545;
            --secondary-color: #6c757d;
            --cancel-color: #343a40;
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
            padding-bottom: 100px;
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
            border-top: 5px solid var(--primary-color);
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
            background-color: #b02a37;
            border-color: #b02a37;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        .btn-cancel {
            background-color: var(--cancel-color);
            border-color: var(--cancel-color);
            color: var(--white);
        }

        .btn-cancel:hover {
            background-color: #23272b;
            border-color: #23272b;
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
            text-decoration: none;
            color: black;
            font-size: 0.8rem;
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

        .badge-admin {
            background-color: var(--primary-color);
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.8rem;
            margin-bottom: 15px;
            display: inline-block;
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

            .perfilInfo .info-item {
                font-size: 1em;
            }
        }
    </style>
</head>

<body>
    <div class="contenedorPerfil sombra fw-bold mt-5">

        <div class="fotoPerfilMovil centrar">
            <span class="badge-admin fw-bold"><i class="fas fa-shield-alt"></i> Panel Global</span>
            <div class="profile-image-container sombra mb-3">
                <img id="fotoPerfilMovil" src="<?= htmlspecialchars($admin['avatar_url'] ?? '../img/perfil.png') ?>" alt="Profile Image">
            </div>
            <button type="button" class="btn btn-primary rounded-pill mt-2 w-100" data-bs-toggle="modal" data-bs-target="#cambiarImagenModal">
                <i class="fas fa-camera"></i> Cambiar imágen
            </button>
            <button type="button" class="btn btn-primary rounded-pill mt-2 w-100 botonEditar" data-bs-toggle="modal" data-bs-target="#editarPerfilModal">
                <i class="fas fa-edit"></i> Información legal
            </button>
            <button type="button" class="btn btn-cancel rounded-pill mt-2 w-100" onclick="window.location.href='../cerrarSesion.php'">
                <i class="fas fa-sign-out-alt"></i> Cerrar sesión
            </button>
        </div>

        <div class="perfilFotoBotones">
            <span class="badge-admin fw-bold"><i class="fas fa-shield-alt"></i> Administrador Global</span>
            <div class="profile-image-container sombra mb-3">
                <img id="fotoPerfil" src="<?= htmlspecialchars($admin['avatar_url'] ?? '../img/perfil.png') ?>" alt="Profile Image">
            </div>
            <button type="button" class="btn btn-primary rounded-pill mt-2 w-100" data-bs-toggle="modal" data-bs-target="#cambiarImagenModal">
                <i class="fas fa-camera"></i> Cambiar imágen
            </button>
            <button type="button" class="btn btn-primary rounded-pill mt-2 w-100 botonEditar" data-bs-toggle="modal" data-bs-target="#editarPerfilModal">
                <i class="fas fa-edit"></i> Información legal
            </button>
            <button type="button" class="btn btn-cancel rounded-pill mt-2 w-100" onclick="window.location.href='cerrarSesion.php'">
                <i class="fas fa-sign-out-alt"></i> Cerrar sesión
            </button>
        </div>

        <div class="perfilInfo">
            <p class="h5 fw-bold mb-3"><u>Información Legal Global:</u></p>

            <div id="nombre" class="info-item">Nombre: <?= htmlspecialchars($admin['name'] ?? '') ?></div>
            <div id="email" class="info-item">E-mail: <?= htmlspecialchars($admin['email'] ?? '') ?></div>
            <div id="telefono" class="info-item">Teléfono: <?= htmlspecialchars($admin['phone'] ?? '') ?></div>
            <div id="empresa" class="info-item">Empresa: <?= htmlspecialchars($admin['empresa'] ?? '') ?></div>
            <div id="cif" class="info-item">CIF/NIF: <?= htmlspecialchars($admin['cif'] ?? $admin['nif'] ?? '') ?></div>

            <div id="direccion" class="info-item">Dirección: <?= htmlspecialchars($admin['domicilio_social'] ?? $admin['direccion'] ?? '') ?></div>
            <div id="localidad" class="info-item">Localidad: <?= htmlspecialchars($admin['localidad'] ?? '') ?></div>
            <div id="provincia" class="info-item">Provincia: <?= htmlspecialchars($admin['provincia'] ?? '') ?></div>
            <div id="codigoPostal" class="info-item">Código Postal: <?= htmlspecialchars($admin['codigo_postal'] ?? '') ?></div>

            <input type="hidden" id="adminId" value="<?= htmlspecialchars($admin['id'] ?? '') ?>">
        </div>
    </div>

    <div class="container-fluid footer mt-5 p-3">
        <div class="row text-center fixed-bottom bg-blanco pt-1 px-2 footer-container">
            <a href="dashboard.php" class="col-2 text-center footer-item">
                <div class="row">
                    <div class="col-12 icon-container">
                        <i class="h3 fas fa-chart-line p-1 m-0"></i>
                        <div>Panel</div>
                    </div>
                </div>
            </a>
            <a href="verGestores.php" class="col-2 text-center footer-item">
                <div class="row">
                    <div class="col-12 icon-container">
                        <i class="h3 fas fa-user-tie p-1 m-0"></i>
                        <div>Gestores</div>
                    </div>
                </div>
            </a>
            <a href="verAnfitriones.php" class="col-2 text-center footer-item">
                <div class="row">
                    <div class="col-12 icon-container">
                        <i class="h3 fas fa-users p-1 m-0"></i>
                        <div>Anfitriones</div>
                    </div>
                </div>
            </a>
            <a href="verEstablecimientos.php" class="col-2 text-center footer-item">
                <div class="row">
                    <div class="col-12 icon-container">
                        <i class="h3 fas fa-building p-1 m-0"></i>
                        <div>Establecimientos</div>
                    </div>
                </div>
            </a>
            <a href="verValidar.php" class="col-2 text-center footer-item">
                <div class="row">
                    <div class="col-12 icon-container">
                        <i class="h3 fas fa-check-circle p-1 m-0"></i>
                        <div>Validar</div>
                    </div>
                </div>
            </a>
            <a href="tuPerfil.php" class="col-2 text-center footer-item">
                <div class="row">
                    <div class="col-12 icon-container" style="color:var(--primary-color);">
                        <i class="h3 fas fa-user-cog p-1 m-0"></i>
                        <div>Perfil</div>
                    </div>
                </div>
            </a>
        </div>
    </div>

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
                                <label class="form-label fw-bold">Nombre del Administrador:</label>
                                <input type="text" class="form-control" id="editNombre" name="nombre">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">E-mail de contacto:</label>
                                <input disabled type="email" class="form-control" id="editEmail">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-bold">Empresa / Razón Social:</label>
                                <input type="text" class="form-control" id="editEmpresa" name="empresa">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-bold">CIF/NIF:</label>
                                <input type="text" class="form-control" id="editCIF" name="cif">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-bold">Teléfono Legal:</label>
                                <input type="text" class="form-control" id="editTelefono" name="telefono">
                            </div>
                        </div>

                        <hr>
                        <h6 class="fw-bold mb-3 text-danger"><i class="fas fa-map-marker-alt me-1"></i> Dirección de Facturación Global</h6>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Dirección Completa:</label>
                            <input type="text" class="form-control" id="editDireccion" name="domicilio_social">
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-bold">Provincia:</label>
                                <input type="text" class="form-control" id="editProvincia" name="provincia">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-bold">Localidad:</label>
                                <input type="text" class="form-control" id="editLocalidad" name="localidad">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-bold">Código Postal:</label>
                                <input type="text" class="form-control" id="editCodigoPostal" name="codigo_postal">
                            </div>
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

    <div class="modal fade" id="cambiarImagenModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Cambiar imagen de administrador</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="formCambiarImagen">
                        <div class="text-center mb-4">
                            <div class="profile-image-container mx-auto">
                                <img id="previewImagen" src="<?= htmlspecialchars($admin['avatar_url'] ?? '../img/perfil.png') ?>" alt="Imagen de administrador">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="inputImagen" class="form-label fw-bold">Seleccionar nueva imagen:</label>
                            <input type="file" class="form-control" id="inputImagen" name="imagen" accept="image/*">
                            <input type="hidden" id="imagenAdminId" name="adminId" value="<?= htmlspecialchars($admin['id'] ?? '') ?>">
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

    <script>
        // Cargar los datos en el modal de edición
        document.querySelectorAll('.botonEditar').forEach(boton => {
            boton.addEventListener('click', function() {
                document.getElementById("editNombre").value = "<?= htmlspecialchars($admin['name'] ?? '') ?>";
                document.getElementById("editEmail").value = "<?= htmlspecialchars($admin['email'] ?? '') ?>";
                document.getElementById("editEmpresa").value = "<?= htmlspecialchars($admin['empresa'] ?? '') ?>";
                document.getElementById("editTelefono").value = "<?= htmlspecialchars($admin['phone'] ?? '') ?>";
                document.getElementById("editCIF").value = "<?= htmlspecialchars($admin['cif'] ?? $admin['nif'] ?? '') ?>";

                document.getElementById("editDireccion").value = "<?= htmlspecialchars($admin['domicilio_social'] ?? $admin['direccion'] ?? '') ?>";
                document.getElementById("editProvincia").value = "<?= htmlspecialchars($admin['provincia'] ?? '') ?>";
                document.getElementById("editLocalidad").value = "<?= htmlspecialchars($admin['localidad'] ?? '') ?>";
                document.getElementById("editCodigoPostal").value = "<?= htmlspecialchars($admin['codigo_postal'] ?? '') ?>";
            });
        });

        // Guardar cambios del perfil
        document.getElementById("btnGuardarCambios").addEventListener("click", function() {
            const formData = new FormData(document.getElementById("formEditarPerfil"));
            const btn = this;
            btn.disabled = true;
            btn.textContent = "Guardando...";

            fetch("actualizarAdmin.php", {
                    method: "POST",
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert("Información global actualizada correctamente");
                        location.reload();
                    } else {
                        alert("Error: " + data.message);
                        btn.disabled = false;
                        btn.textContent = "Guardar cambios";
                    }
                })
                .catch(error => {
                    console.error("Error:", error);
                    alert("Aviso: Falta el archivo actualizarAdmin.php para procesar los datos.");
                    btn.disabled = false;
                    btn.textContent = "Guardar cambios";
                });
        });

        // Previsualizar la imagen seleccionada
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

        // Guardar nueva imagen
        document.getElementById('btnGuardarImagen').addEventListener('click', function() {
            const inputImagen = document.getElementById("inputImagen");

            if (inputImagen.files.length === 0) {
                alert("Debes seleccionar una imagen");
                return;
            }

            const formData = new FormData();
            formData.append('imagen', inputImagen.files[0]);
            formData.append('adminId', document.getElementById('imagenAdminId').value);

            const btn = this;
            btn.disabled = true;
            btn.textContent = "Guardando...";

            fetch("subir-imagen-perfil-admin.php", {
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
                    alert("Aviso: Falta el archivo subir-imagen-perfil-admin.php para guardar la foto.");
                })
                .finally(() => {
                    btn.disabled = false;
                    btn.textContent = "Guardar cambios";
                });
        });
    </script>
</body>

</html>