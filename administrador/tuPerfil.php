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
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="icon" href="../favicon-color.png">
    <link rel="icon" href="../favicon-negro.png" media="(prefers-color-scheme: light)">
    <link rel="icon" href="../favicon-color.png" media="(prefers-color-scheme: dark)">
    <title>TheNomadapp - Panel Administrador</title>
    <style>
        :root {
            --primary-color: #c83a45;
            --primary-hover: #db4b56;
            --secondary-color: #6c757d;
            --cancel-color: #5f6772;
            --light-bg: #f8f9fb;
            --white: #ffffff;
            --dark-text: #151922;
            --border-radius-lg: 16px;
            --border-radius-md: 12px;
            --border-radius-sm: 8px;
            --bg: #eef2f6;
            --ink: #1f2933;
            --muted-text: #4f5b67;
            --line: #d8e1ea;
            --accent-dark: #4d131a;
            --accent-mid: #8f2a33;
            --surface-card: #ffffff;
            --surface-soft: #f4f7fb;
        }

        body {
            font-family: 'Roboto', 'Helvetica Neue', Arial, sans-serif;
            background: linear-gradient(155deg, #f9fbfd 0%, #eef2f6 48%, #f4f7fb 100%);
            color: var(--ink);
            padding-bottom: 120px;
            min-height: 100vh;
        }

        .page-shell {
            max-width: 1400px;
            margin: 0 auto;
            padding: 22px 16px 0;
        }

        .hero {
            background: linear-gradient(135deg, #962d22 0%, #c44536 52%, #df786c 100%);
            color: #fffaf8;
            border-radius: 24px;
            padding: 22px 24px;
            box-shadow: 0 18px 42px #cfd8e2;
            border: 1px solid #b93d41;
            margin-bottom: 20px;
            box-sizing: border-box;
            width: 100%;
            backdrop-filter: blur(2px);
        }

        .hero-title {
            font-size: 1.3rem;
            font-weight: 700;
            margin: 0;
            letter-spacing: 0.3px;
        }

        .title-row {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .contenedorPerfil {
            background-color: #ffffff;
            border-radius: 20px;
            padding: 40px 30px;
            margin: 0 auto;
            max-width: none;
            width: 100%;
            min-height: 600px;
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: flex-start;
            border: 1px solid #d8e1ea;
            box-sizing: border-box;
            backdrop-filter: blur(6px);
        }

        .sombra {
            box-shadow: 0 18px 34px #d9e1ea;
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
            border: 1.5px solid #dce4ee;
            box-shadow: 0 0 0 4px #f0d7da;
            flex-shrink: 0;
            background: #ffffff;
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

        .section-title {
            margin-bottom: 20px !important;
            padding-bottom: 12px;
            border-bottom: 1px solid #d8989e;
            display: flex;
            align-items: center;
            gap: 8px;
            color: #1f2933;
            font-weight: 600;
            letter-spacing: 0.2px;
        }

        .perfilInfo .info-item {
            background-color: #f8fafd;
            padding: 15px 20px;
            border-radius: var(--border-radius-md);
            margin-bottom: 12px;
            font-size: 1rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border: 1px solid #e2e8f0;
            color: var(--muted-text);
            line-height: 1.35;
        }

        .perfilInfo .info-item:last-child {
            margin-bottom: 0;
        }

        .perfilInfo .info-item strong {
            color: #1f2933;
            font-weight: 500;
            margin-right: 14px;
            border-right: 1px solid #d9e2ec;
            padding-right: 14px;
            min-width: 145px;
        }

        .btn-primary,
        .btn-cancel {
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 10px 14px;
            font-size: 0.88rem;
            letter-spacing: 0.2px;
            width: 100%;
            margin-top: 10px !important;
            border-radius: 10px !important;
            border: 1px solid transparent;
            box-shadow: none;
        }

        .btn-primary {
            background-color: #c83a45;
            border-color: #c83a45;
            color: #ffffff;
        }

        .btn-primary:hover {
            background-color: #b7333e;
            border-color: #b7333e;
            color: #ffffff;
            transform: translateY(-1px);
        }

        .btn-cancel {
            background-color: #eef2f6;
            border-color: #d5dde6;
            color: #2f3945;
        }

        .btn-cancel:hover {
            background-color: #dfe7ef;
            border-color: #c3cfdb;
            color: #1f2933;
            transform: translateY(-1px);
        }

        .badge-admin {
            background-color: #f5d9dc;
            color: #8d2e35;
            padding: 6px 15px;
            border-radius: 20px;
            font-size: 0.78rem;
            border: 1px solid #d89aa0;
            margin-bottom: 15px;
            display: inline-block;
        }

        .legal-info-wrapper {
            width: 100%;
            text-align: center;
            margin-bottom: 10px;
        }

        .legal-info-label {
            display: block;
            font-weight: 700;
            color: #1f2933;
            margin-bottom: 8px;
        }

        .legal-info-box {
            max-width: 520px;
            margin: 0 auto;
            background-color: #f8fafd;
            border: 1px solid #dde5ef;
            border-radius: 12px;
            padding: 10px 14px;
            color: #4f5b67;
            text-align: left;
            max-height: 240px;
            overflow-y: auto;
        }

        .legal-rich-content h6 {
            margin-bottom: 8px;
            font-weight: 800;
            color: #1f2933;
        }

        .legal-rich-content p {
            margin-bottom: 8px;
            color: #4f5b67;
            font-size: 0.92rem;
            line-height: 1.4;
        }

        .legal-rich-content ul {
            margin: 0;
            padding-left: 18px;
        }

        .legal-rich-content li {
            margin-bottom: 5px;
            font-size: 0.9rem;
            line-height: 1.35;
            color: #4f5b67;
        }

        .legal-rich-content a {
            color: #ff9299;
            text-decoration: none;
        }

        .legal-rich-content a:hover {
            text-decoration: underline;
        }

        .separator-line {
            border: 0;
            border-top: 1px solid #d8e1ea;
            margin: 14px 0;
        }

        .modal-content {
            background: #ffffff;
            color: #1f2933;
            border: 1px solid #dde5ef;
        }

        .modal-header,
        .modal-footer {
            border-color: #e3eaf2;
        }

        .form-control {
            background: #f8fafd;
            border: 1px solid #d6dee8;
            color: #1f2933;
        }

        .form-control:focus {
            background: #ffffff;
            color: #1f2933;
            border-color: #c83a45;
            box-shadow: 0 0 0 0.2rem #f2cfd2;
        }

        .form-label {
            color: #2f3945;
            font-size: 0.92rem;
        }

        @media (max-width: 768px) {
            .page-shell {
                padding-left: 12px;
                padding-right: 12px;
            }

            .hero {
                padding: 18px;
            }

            .hero-title {
                font-size: 1.2rem;
            }

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
                flex-direction: column;
                align-items: flex-start;
                gap: 6px;
            }

            .perfilInfo .info-item strong {
                min-width: auto;
                border-right: none;
                padding-right: 0;
            }
        }
    </style>
</head>

<body>
    <div class="page-shell">
        <section class="hero">
            <div class="title-row">
                <h1 class="hero-title"><i class="fas fa-user-cog me-2"></i>Perfil Global de Administrador</h1>
            </div>
        </section>

        <div class="contenedorPerfil sombra fw-bold mt-3">

            <div class="fotoPerfilMovil centrar">
                <span class="badge-admin fw-bold"><i class="fas fa-shield-alt"></i> Panel Global</span>
                <div class="profile-image-container sombra mb-3">
                    <img id="fotoPerfilMovil" src="<?= htmlspecialchars($admin['avatar_url'] ?? '../img/perfil.png') ?>" alt="Profile Image">
                </div>
                <button type="button" class="btn btn-primary mt-2 w-100" data-bs-toggle="modal" data-bs-target="#cambiarImagenModal">
                    <i class="fas fa-camera"></i> Cambiar imágen
                </button>
                <button type="button" class="btn btn-primary mt-2 w-100 botonEditar" data-bs-toggle="modal" data-bs-target="#editarPerfilModal">
                    <i class="fas fa-edit"></i> Información legal
                </button>
                <button type="button" class="btn btn-cancel mt-2 w-100" onclick="window.location.href='../cerrarSesion.php'">
                    <i class="fas fa-sign-out-alt"></i> Cerrar sesión
                </button>
            </div>

            <div class="perfilFotoBotones">
                <span class="badge-admin fw-bold"><i class="fas fa-shield-alt"></i> Administrador Global</span>
                <div class="profile-image-container sombra mb-3">
                    <img id="fotoPerfil" src="<?= htmlspecialchars($admin['avatar_url'] ?? '../img/perfil.png') ?>" alt="Profile Image">
                </div>
                <button type="button" class="btn btn-primary mt-2 w-100" data-bs-toggle="modal" data-bs-target="#cambiarImagenModal">
                    <i class="fas fa-camera"></i> Cambiar imágen
                </button>
                <button type="button" class="btn btn-primary mt-2 w-100 botonEditar" data-bs-toggle="modal" data-bs-target="#editarPerfilModal">
                    <i class="fas fa-edit"></i> Información legal
                </button>
                <button type="button" class="btn btn-cancel mt-2 w-100" onclick="window.location.href='cerrarSesion.php'">
                    <i class="fas fa-sign-out-alt"></i> Cerrar sesión
                </button>
            </div>

            <div class="perfilInfo">
                <p class="h5 section-title"><i class="fas fa-file-signature"></i> Información Legal Global</p>

                <div id="nombre" class="info-item"><strong>Nombre</strong> <?= htmlspecialchars($admin['name'] ?? '') ?></div>
                <div id="email" class="info-item"><strong>E-mail</strong> <?= htmlspecialchars($admin['email'] ?? '') ?></div>
                <div id="telefono" class="info-item"><strong>Teléfono</strong> <?= htmlspecialchars($admin['phone'] ?? '') ?></div>
                <div id="empresa" class="info-item"><strong>Empresa</strong> <?= htmlspecialchars($admin['empresa'] ?? '') ?></div>
                <div id="cif" class="info-item"><strong>CIF/NIF</strong> <?= htmlspecialchars($admin['cif'] ?? $admin['nif'] ?? '') ?></div>

                <hr class="separator-line">

                <div id="direccion" class="info-item"><strong>Dirección</strong> <?= htmlspecialchars($admin['domicilio_social'] ?? $admin['direccion'] ?? '') ?></div>
                <div id="localidad" class="info-item"><strong>Localidad</strong> <?= htmlspecialchars($admin['localidad'] ?? '') ?></div>
                <div id="provincia" class="info-item"><strong>Provincia</strong> <?= htmlspecialchars($admin['provincia'] ?? '') ?></div>
                <div id="codigoPostal" class="info-item"><strong>Código Postal</strong> <?= htmlspecialchars($admin['codigo_postal'] ?? '') ?></div>

                <input type="hidden" id="adminId" value="<?= htmlspecialchars($admin['id'] ?? '') ?>">
            </div>
        </div>


        <?php include 'footerAdmin.php'; ?>

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
                        <div class="legal-info-wrapper">
                            <span class="legal-info-label">Información Legal</span>
                            <div class="legal-info-box" id="legalInfoDisplay"><?php echo $informacionLegal ?? '<span>Sin informacion legal registrada</span>'; ?></div>
                        </div>
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