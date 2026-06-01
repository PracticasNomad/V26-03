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
    <link href="style.css" rel="stylesheet">
    <script src="https://kit.fontawesome.com/b8814a2854.js" crossorigin="anonymous"></script>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.js"></script>
    <link rel="icon" href="../favicon-color.png">
    <link rel="icon" href="../favicon-negro.png" media="(prefers-color-scheme: light)">
    <link rel="icon" href="../favicon-color.png" media="(prefers-color-scheme: dark)">
    <title>Tus historias</title>

    <script>
        window.onload = function () {
            const today = new Date().toISOString().split('T')[0];
            const container = document.getElementById('container');

            showLoadingIndicator();

            const url = "AllReservasAnfitrion.php";

            fetch(url)
                .then(response => response.json())
                .then(data => {
                    hideLoadingIndicator();
                    appendData(data);
                })
                .catch(err => {
                    console.log(err);
                    hideLoadingIndicator();
                    showErrorMessage();
                });

            function showLoadingIndicator() {
                container.innerHTML = `
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
                            <span class="visually-hidden">Cargando...</span>
                        </div>
                        <p class="mt-3 text-primary">Cargando tus historias...</p>
                    </div>
                `;
            }

            function hideLoadingIndicator() { }

            function showErrorMessage() {
                container.innerHTML = `
                    <div class="alert alert-danger mt-4" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        No se pudieron cargar las historias. Por favor, intenta de nuevo más tarde.
                    </div>
                `;
            }

            function appendData(data) {
                container.innerHTML = '';
                let historiasEncontradas = false;

                for (var i = 0; i < data.length; i++) {
                    if (data[i].space && data[i].space.establecimiento) {

                        let isCanceled = false;
                        if (data[i].cancelada === true || data[i].cancelada == 1) isCanceled = true;
                        if (data[i].estado_cancelacion === true || data[i].estado_cancelacion == 1 || (data[i].estado_cancelacion && String(data[i].estado_cancelacion).toLowerCase() === 'cancelada')) isCanceled = true;

                        if (data[i].day < today || isCanceled) {
                            historiasEncontradas = true;
                            const fechaReserva = new Date(data[i].day);
                            const opciones = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
                            const fechaFormateada = fechaReserva.toLocaleDateString('es-ES', opciones);
                            const fechaFormateadaFinal = fechaFormateada.charAt(0).toUpperCase() + fechaFormateada.slice(1);

                            const nombreUsuario = data[i].user ? data[i].user.name : 'Usuario Desconocido';

                            const headerClass = isCanceled ? 'bg-danger' : 'bg-secondary';
                            const iconClass = isCanceled ? 'fa-calendar-times' : 'fa-calendar-check';
                            const cancelText = isCanceled ? ' <span class="badge bg-light text-danger ms-2">Cancelada</span>' : '';

                            var card = document.createElement("div");
                            card.className = "card reservation-card mb-4 shadow-sm";
                            if (isCanceled) card.style.border = "1px solid #dc3545";

                            card.innerHTML = `
                                <div class="card-header ${headerClass} text-white">
                                    <h5 class="mb-0"><i class="fas ${iconClass} me-2"></i>${fechaFormateadaFinal}${cancelText}</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-12">
                                            <h4 class="mb-1 text-dark">${data[i].space.name}</h4>
                                            <p class="text-muted mb-3"><i class="fas fa-building me-1"></i> ${data[i].space.establecimiento.nombre}</p>
                                            
                                            <p class="mb-2" style="font-size: 1.05rem;">
                                                <i class="fas fa-user-check text-${isCanceled ? 'danger' : 'secondary'} me-2"></i>
                                                <strong>Reservado por:</strong> ${nombreUsuario}
                                            </p>
                                            
                                            <div class="d-flex justify-content-between align-items-center mt-4 pt-2 border-top">
                                                <div>
                                                    <span class="badge bg-info text-dark">Inicio: ${data[i].start_time.substring(0, 5)}</span>
                                                    <span class="badge bg-secondary ms-2">Fin: ${data[i].end_time.substring(0, 5)}</span>
                                                </div>
                                                <div>
                                                    <a href="detalles_reserva.php?id=${data[i].id}" class="btn btn-secondary">
                                                        Ver detalles
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            `;
                            container.appendChild(card);
                        }
                    }
                }

                if (!historiasEncontradas) {
                    container.innerHTML = `
                        <div class="empty-state">
                            <div class="empty-state__icon"><i class="fas fa-history"></i></div>
                            <div class="empty-state__text">No hay historial de reservas anteriores.</div>
                        </div>
                    `;
                }
            }
        };
    </script>

    <style>
        :root {
            --host-accent: #10bfeb;
            --host-accent-dark: #0a95b7;
            --host-accent-soft: #e7f8fd;
            --header-active-green: #81ba18;
            --header-active-green-dark: #6d9e14;
        }

        body {
            font-family: 'Nunito', sans-serif;
            padding-bottom: 15%;
            background-color: #f4f6f9;
        }

        .page-shell {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 15px;
            box-sizing: border-box;
        }

        .reservation-card {
            border: none;
            border-radius: 12px;
            overflow: hidden;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            margin-top: 15px;
        }

        .reservation-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1) !important;
        }

        .card-header {
            padding: 1rem 1.25rem;
            border-bottom: none;
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

        .empty-state {
            text-align: center;
            padding: 50px 20px;
            color: #6c757d;
        }

        .empty-state__icon {
            font-size: 3rem;
            margin-bottom: 16px;
            opacity: 0.4;
        }

        .empty-state__text {
            font-size: 1.05rem;
            font-weight: 600;
        }

        .header-main {
            overflow-x: hidden;
        }

        .header-tabs {
            overflow: hidden;
            border-radius: 12px;
            background-color: white;
        }

        .header-tab {
            font-weight: bold;
            transition: all 0.3s ease;
            height: 100%;
            cursor: pointer;
            color: #00B7CF;
            background-color: white;
            border-bottom: 3px solid transparent;
        }

        .header-tab-active {
            color: white;
            background-color: #81ba18;
            border-color: #BDE742;
        }

        .header-tab-link {
            text-decoration: none;
            display: block;
            height: 100%;
        }

        .header-tab:hover:not(.header-tab-active) {
            background-color: #f8f9fa;
            color: #4CCBD4;
            border-bottom: 3px solid #E3E1E1;
        }
    </style>
</head>

<body>
    <div class="page-shell">
        <?php include 'headerAnfitrion.php'; ?>
        <div id="container" style="max-width: 100%; overflow-x: hidden; box-sizing: border-box;"></div>
    </div>

    <?php include 'footerAnfitrion.php'; ?>
    <?php include '../typebot.php'; ?>
</body>

</html>