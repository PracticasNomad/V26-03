<?php
require_once 'verificar_sesion_guest.php';

require './vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://kit.fontawesome.com/b8814a2854.js" crossorigin="anonymous"></script>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@200;400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.1/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="icon" href="favicon-color.png">
    <title>Reservas Históricas</title>

    <script>
        const MINIO_URL = "<?php echo rtrim($_ENV['MINIO_PUBLIC_URL'] ?? 'http://127.0.0.1:9000', '/'); ?>";
    </script>

    <style>
        body {
            font-family: 'Nunito', sans-serif;
            background-color: #f4f6f9;
            background-size: 65% 80%;
            background-position: center;
            background-repeat: no-repeat;
            opacity: 0.9;
            padding-bottom: 15%;
        }

        .btn-secondary {
            background-color: #6c757d !important;
            color: white !important;
            border: none;
            font-weight: bold;
        }

        .btn-secondary:hover {
            background-color: #5a6268 !important;
        }

        a,
        a:visited,
        a:active {
            color: black;
            text-decoration: none;
        }

        #loading-spinner {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(255, 255, 255, 0.8);
            z-index: 9999;
            justify-content: center;
            align-items: center;
            flex-direction: column;
        }

        .spinner-border {
            width: 3rem;
            height: 3rem;
            color: #81ba18 !important;
        }

        .loading-text {
            margin-top: 1rem;
            font-weight: bold;
            color: #00B7CF;
        }

        .card-header.bg-secondary {
            background-color: #6c757d !important;
        }

        .img-container {
            position: relative;
            overflow: hidden;
        }

        .img-container::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(45deg, rgba(0, 183, 207, 0.1), rgba(129, 186, 24, 0.1));
            pointer-events: none;
        }

        .page-shell {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 15px;
            box-sizing: border-box;
        }
    </style>
</head>

<body>
    <div id="loading-spinner">
        <div class="spinner-border" role="status"></div>
        <p class="loading-text">Cargando tu historial de reservas...</p>
    </div>

    <div class="page-shell">
        <?php include 'headerNomada.php'; ?>

        <div id="container" class="mt-4"></div>
    </div>

    <div class="modal fade" id="modalValoracion" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius: 20px;">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold" style="color: #00B7CF;"><i
                            class="fas fa-star text-warning me-2"></i>Valora tu experiencia</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body pt-2">
                    <p class="text-muted small mb-3">Estás valorando: <strong id="modalEstNombre"
                            class="text-dark"></strong></p>
                    <div id="alerta-modal"></div>

                    <form id="formHistoricoValoracion">
                        <input type="hidden" id="modalEstId">
                        <div class="mb-3">
                            <select class="form-select border-0 shadow-sm" id="modalInputValoracion" required
                                style="background-color: #f4f6f9;">
                                <option value="" disabled selected>Puntúa este lugar...</option>
                                <option value="5">⭐⭐⭐⭐⭐ (5/5 - Excelente)</option>
                                <option value="4">⭐⭐⭐⭐ (4/5 - Muy bueno)</option>
                                <option value="3">⭐⭐⭐ (3/5 - Bueno)</option>
                                <option value="2">⭐⭐ (2/5 - Regular)</option>
                                <option value="1">⭐ (1/5 - Malo)</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <textarea class="form-control border-0 shadow-sm" id="modalInputComentario" rows="3"
                                placeholder="¿Qué te pareció este espacio? Ayuda a otros nómadas con tu opinión."
                                required style="background-color: #f4f6f9;"></textarea>
                        </div>
                        <div class="text-end">
                            <button type="submit" class="btn text-white px-4 rounded-pill fw-bold"
                                style="background-color: #00B7CF;">
                                Publicar Reseña
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php include 'footerNomada.php'; ?>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.getElementById('loading-spinner').style.display = 'flex';

            const url = "getReservasNomada.php?nocache=" + new Date().getTime();

            fetch(url, { cache: "no-store" })
                .then(response => response.json())
                .then(data => {
                    if (!data || data.length === 0) {
                        document.getElementById('loading-spinner').style.display = 'none';
                        appendData([], {}, []);
                        return;
                    }

                    const today = new Date().toISOString().split('T')[0];

                    // ESCUDO ANTI-CANCELACIONES FANTASMA
                    const reservasHistoricas = data.filter(reserva => {
                        let isCanceled = false;
                        if (reserva.cancelada === true || reserva.cancelada == 1) isCanceled = true;
                        if (reserva.estado_cancelacion === true || reserva.estado_cancelacion == 1 || (reserva.estado_cancelacion && String(reserva.estado_cancelacion).toLowerCase() === 'cancelada')) isCanceled = true;

                        return reserva.day < today || isCanceled;
                    });

                    if (reservasHistoricas.length === 0) {
                        document.getElementById('loading-spinner').style.display = 'none';
                        appendData([], {}, []);
                        return;
                    }

                    // ESCUDO ANTI-NULOS PARA IDs
                    const establecimientoIds = [...new Set(reservasHistoricas.map(r => r.space?.establecimiento?.id).filter(id => id != null))];

                    return Promise.all([
                        fetch(`getGalleryImages.php?ids=${establecimientoIds.join(',')}`).then(res => res.json()).catch(() => ({})),
                        fetch('mis_valoraciones.php').then(res => res.json()).catch(() => [])
                    ])
                        .then(([galleryImages, misValoraciones]) => {
                            document.getElementById('loading-spinner').style.display = 'none';
                            appendData(data, galleryImages, misValoraciones);
                        });
                })
                .catch(err => {
                    document.getElementById('loading-spinner').style.display = 'none';
                    const container = document.getElementById('container');
                    if (container) {
                        container.innerHTML += `
                            <div class="alert alert-danger mt-3" role="alert">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                Error al cargar el historial de reservas. Revisa la consola.
                            </div>
                        `;
                    }
                });

            function appendData(data, galleryImages, misValoraciones) {
                var contenedor = document.getElementById("container");

                if (!contenedor) return;

                const today = new Date().toISOString().split('T')[0];
                const defaultImage = "https://cdn.pixabay.com/photo/2016/11/18/14/05/brick-wall-1834784_960_720.jpg";
                let reservasHistoricasCount = 0;

                for (var i = data.length - 1; i >= 0; i--) {
                    let isCanceled = false;
                    if (data[i].cancelada === true || data[i].cancelada == 1) isCanceled = true;
                    if (data[i].estado_cancelacion === true || data[i].estado_cancelacion == 1 || (data[i].estado_cancelacion && String(data[i].estado_cancelacion).toLowerCase() === 'cancelada')) isCanceled = true;

                    if (data[i].day < today || isCanceled) {
                        reservasHistoricasCount++;

                        const fecha = new Date(data[i].day);
                        const opciones = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
                        const fechaFormateada = fecha.toLocaleDateString('es-ES', opciones);
                        const fechaFormateadaFinal = fechaFormateada.charAt(0).toUpperCase() + fechaFormateada.slice(1);

                        // DATOS SEGUROS
                        const est = data[i].space?.establecimiento || {};
                        const estNombre = est.nombre || 'Establecimiento desconocido';
                        const estDireccion = est.direccion || 'Dirección no disponible';
                        const estLocalidad = est.localidad || '';
                        const estProvincia = est.provincia || '';
                        const establecimientoId = est.id || null;

                        let imageUrl = (establecimientoId && galleryImages[establecimientoId]) ? galleryImages[establecimientoId] : defaultImage;

                        if (imageUrl !== defaultImage) {
                            try {
                                let tempUrl = imageUrl.startsWith('http') ? imageUrl : 'http://' + imageUrl;
                                let urlObj = new URL(tempUrl);
                                imageUrl = MINIO_URL + urlObj.pathname;
                            } catch (e) { }
                        }

                        // ICONOS SEGUROS
                        let serviciosHtml = '';
                        if (est.has_wifi) serviciosHtml += '<i class="fas fa-wifi text-primary me-2" title="WiFi"></i>';
                        if (est.has_parking) serviciosHtml += '<i class="fas fa-car text-secondary me-2" title="Parking"></i>';
                        if (est.has_food) serviciosHtml += '<i class="fas fa-utensils text-warning me-2" title="Comida"></i>';
                        if (est.has_accommodation) serviciosHtml += '<i class="fas fa-bed text-info me-2" title="Dormir"></i>';

                        const card = document.createElement("div");
                        card.className = "card reservation-card mb-4 shadow-sm";

                        const yaValorado = establecimientoId && misValoraciones && misValoraciones.some(id => String(id) === String(establecimientoId));

                        let botonValorarHTML = '';
                        if (isCanceled) {
                            botonValorarHTML = '<span class="badge bg-danger">Cancelada</span>';
                            card.style.border = "1px solid #dc3545"; // Borde rojo tenue si está cancelada
                        } else if (yaValorado) {
                            botonValorarHTML = '<span class="text-success fw-bold me-3" style="font-size:0.9rem;"><i class="fas fa-check"></i> Valorada</span>';
                        } else {
                            botonValorarHTML = `<button type="button" class="btn btn-warning me-2 text-dark" style="font-weight: bold;" data-bs-toggle="modal" data-bs-target="#modalValoracion" data-est-id="${establecimientoId}" data-est-nombre="${estNombre}"><i class="fas fa-star"></i> Valorar</button>`;
                        }

                        const startTimeStr = data[i].start_time ? data[i].start_time.substring(0, 5) : '--:--';
                        const endTimeStr = data[i].end_time ? data[i].end_time.substring(0, 5) : '--:--';

                        card.innerHTML = `
                            <div class="card-header ${isCanceled ? 'bg-danger' : 'bg-primary'} text-white">
                                <h5 class="mb-0">${fechaFormateadaFinal} ${isCanceled ? '(Cancelada)' : ''}</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="img-container" style="background-image: url('${imageUrl}');
                                                                        height: 150px; background-size: cover;
                                                                        background-position: center; border-radius: 5px; opacity: 0.8;">
                                        </div>
                                    </div>
                                    <div class="col-md-8">
                                        <h4>${estNombre}</h4>
                                        <p class="mb-2">
                                            <i class="fas fa-map-marker-alt text-danger me-2"></i>
                                            ${estDireccion}<br>
                                            <span class="ms-4">${estLocalidad} ${estProvincia ? ', ' + estProvincia : ''}</span>
                                        </p>
                                        <div class="mb-3 ms-4">
                                            ${serviciosHtml}
                                        </div>
                                        
                                        <div class="d-flex justify-content-between align-items-center mt-3">
                                            <div>
                                                <span class="badge bg-info text-dark">Inicio: ${startTimeStr}</span>
                                                <span class="badge bg-secondary ms-2">Fin: ${endTimeStr}</span>
                                            </div>
                                            <div> 
                                                ${botonValorarHTML} 
                                                <a href="reservadetalles.php?reservaId=${data[i].id}" class="btn btn-secondary">
                                                    Ver detalles
                                                </a>
                                            </div>
                                        </div> 
                                    </div>
                                </div>
                            </div>
                        `;
                        contenedor.appendChild(card);
                    }
                }

                if (reservasHistoricasCount === 0) {
                    contenedor.innerHTML += `
                        <div class="alert alert-info mt-3" role="alert">
                            No tienes reservas en tu historial.
                        </div>
                    `;
                }
            }

            // ======= LÓGICA DEL MODAL DE VALORACIÓN =======
            document.getElementById('modalValoracion').addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget;
                document.getElementById('modalEstId').value = button.getAttribute('data-est-id');
                document.getElementById('modalEstNombre').textContent = button.getAttribute('data-est-nombre');
                document.getElementById('alerta-modal').innerHTML = '';
            });

            document.getElementById('formHistoricoValoracion').addEventListener('submit', function (e) {
                e.preventDefault();
                const btnSubmit = this.querySelector('button[type="submit"]');
                const originalText = btnSubmit.innerHTML;
                btnSubmit.disabled = true;
                btnSubmit.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';

                const payload = {
                    id_establecimiento: document.getElementById('modalEstId').value,
                    valoracion: document.getElementById('modalInputValoracion').value,
                    comentario: document.getElementById('modalInputComentario').value
                };

                fetch('guardar_valoracion.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            document.getElementById('alerta-modal').innerHTML = '<div class="alert alert-success py-2 small"><i class="fas fa-check-circle me-1"></i> ¡Reseña publicada con éxito!</div>';

                            const botonesValorar = document.querySelectorAll(`button[data-est-id="${payload.id_establecimiento}"]`);
                            botonesValorar.forEach(b => {
                                b.outerHTML = '<span class="text-success fw-bold me-3" style="font-size:0.9rem;"><i class="fas fa-check"></i> Valorada</span>';
                            });

                            setTimeout(() => {
                                var myModalEl = document.getElementById('modalValoracion');
                                var modal = bootstrap.Modal.getInstance(myModalEl);
                                modal.hide();
                                document.getElementById('formHistoricoValoracion').reset();
                            }, 1500);

                        } else {
                            document.getElementById('alerta-modal').innerHTML = `<div class="alert alert-danger py-2 small"><i class="fas fa-exclamation-circle me-1"></i> ${data.message}</div>`;
                        }
                    })
                    .catch(err => {
                        document.getElementById('alerta-modal').innerHTML = '<div class="alert alert-danger py-2 small">Error de conexión al servidor.</div>';
                    })
                    .finally(() => {
                        btnSubmit.disabled = false;
                        btnSubmit.innerHTML = originalText;
                    });
            });
        });
    </script>
    <?php include 'typebot.php'; ?>
</body>

</html>