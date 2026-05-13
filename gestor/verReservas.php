<?php
require_once 'verificar_sesion_gestor.php';
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
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="icon" href="../favicon-color.png">
    <link rel="icon" href="../favicon-negro.png" media="(prefers-color-scheme: light)">
    <link rel="icon" href="../favicon-color.png" media="(prefers-color-scheme: dark)">
    <title>TheNomadapp - Reservas Gestor</title>

    <script>
        window.onload = function () {
            const today = new Date().toISOString().split('T')[0];
            const container = document.getElementById('container');

            showLoadingIndicator();

            // CAMBIO CLAVE: Apuntamos al archivo del Gestor
            const url = "AllReservasGestor.php";

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
                        <p class="mt-3 text-primary">Cargando las reservas de tus establecimientos...</p>
                    </div>
                `;
            }

            function hideLoadingIndicator() { }

            function showErrorMessage() {
                container.innerHTML = `
                    <div class="alert alert-danger mt-4" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        No se pudieron cargar las reservas. Por favor, intenta de nuevo más tarde.
                    </div>
                `;
            }

            function appendData(data) {
                container.innerHTML = '';
                let reservasEncontradas = false;

                for (var i = 0; i < data.length; i++) {
                    // El filtro es igual: Supabase anula 'establecimiento' si no pertenece al gestor
                    if (data[i].space && data[i].space.establecimiento) {
                        if (data[i].day >= today && data[i].cancelada == false) {
                            reservasEncontradas = true;
                            const fechaReserva = new Date(data[i].day);
                            const diasSemana = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
                            const meses = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];

                            const diaSemana = diasSemana[fechaReserva.getDay()];
                            const dia = fechaReserva.getDate();
                            const mes = meses[fechaReserva.getMonth()];
                            const anio = fechaReserva.getFullYear();

                            const fechaFormateada = `${diaSemana}, ${dia} de ${mes} del ${anio}`;

                            var div = document.createElement("div");
                            div.className = "reserva-card";
                            container.appendChild(div);

                            var divFecha = document.createElement("div");
                            divFecha.className = "reserva-fecha";
                            divFecha.innerHTML = '<i class="fas fa-calendar-alt me-2"></i>' + fechaFormateada;
                            div.appendChild(divFecha);
                            div.appendChild(divFecha);

                            var divContenido = document.createElement("div");
                            divContenido.className = "col-12";
                            div.appendChild(divContenido);

                            var divEspacio = document.createElement("div");
                            divEspacio.className = "reserva-row";
                            divEspacio.innerHTML = '<span class="reserva-icon"><i class="fas fa-map-marker-alt"></i></span><span><strong>Espacio:</strong> ' + data[i].space.name + ' &mdash; ' + data[i].space.establecimiento.nombre + '</span>';
                            divContenido.appendChild(divEspacio);

                            var divHorario = document.createElement("div");
                            divHorario.className = "reserva-row";
                            divHorario.innerHTML = '<span class="reserva-icon"><i class="far fa-clock"></i></span><span><strong>Horario:</strong> ' +
                                data[i].start_time.substring(0, 5) + ' &ndash; ' + data[i].end_time.substring(0, 5) + '</span>';
                            divContenido.appendChild(divHorario);

                            var nombreUsuario = data[i].user ? data[i].user.name : 'Usuario Desconocido';
                            var divUsuario = document.createElement("div");
                            divUsuario.className = "reserva-row";
                            divUsuario.innerHTML = '<span class="reserva-icon"><i class="far fa-user"></i></span><span><strong>Reservado por:</strong> ' + nombreUsuario + '</span>';
                            divContenido.appendChild(divUsuario);

                            var divider = document.createElement("hr");
                            divider.className = "reserva-divider";
                            divContenido.appendChild(divider);

                            var divBoton = document.createElement("div");
                            divBoton.className = "reserva-boton";
                            divContenido.appendChild(divBoton);

                            var botonDetalles = document.createElement("a");
                            botonDetalles.href = 'detalles_reserva.php?id=' + data[i].id;
                            botonDetalles.className = "btn-detalle";
                            botonDetalles.innerHTML = '<i class="fas fa-arrow-right me-1"></i>Ver detalles';
                            divBoton.appendChild(botonDetalles);
                        }
                    }
                }

                if (!reservasEncontradas) {
                    container.innerHTML = `
                        <div class="empty-state">
                            <div class="empty-state__icon"><i class="fas fa-calendar-day"></i></div>
                            <div class="empty-state__text">No hay reservas próximas para tus establecimientos.</div>
                        </div>
                    `;
                }
            }
        };
    </script>

    <style>
        :root {
            --azul:       #1976d2;
            --azul-dark:  #0d47a1;
            --azul-light: #e3f0fb;
            --azul-mid:   #bbdefb;
            --text:       #1a2333;
            --muted:      #546e8a;
        }

        body {
            padding-bottom: 15%;
            background: linear-gradient(160deg, #e8f1fb 0%, #f0f5ff 50%, #e6f4f1 100%);
            min-height: 100vh;
            font-family: 'Nunito', sans-serif;
        }

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

        .fecha {
            border-radius: 0.5rem;
        }

        .spinner-border {
            color: var(--azul);
        }

        /* ── Tarjeta de reserva ── */
        .reserva-card {
            background: #ffffff;
            border: 1px solid var(--azul-mid);
            border-radius: 16px;
            margin-bottom: 18px;
            overflow: hidden;
            box-shadow: 0 4px 18px rgba(25, 118, 210, 0.10);
            transition: transform 0.22s ease, box-shadow 0.22s ease;
        }

        .reserva-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 28px rgba(25, 118, 210, 0.16);
        }

        /* Banda de fecha */
        .reserva-fecha {
            background: linear-gradient(120deg, #1565c0 0%, #1976d2 55%, #42a5f5 100%);
            color: #ffffff;
            font-weight: 700;
            font-size: 1rem;
            padding: 12px 20px;
            letter-spacing: 0.2px;
        }

        /* Cuerpo */
        .reserva-card .col-12 {
            padding: 16px 20px 8px;
        }

        .reserva-row {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
            font-size: 0.97rem;
            color: var(--text);
        }

        .reserva-icon {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: var(--azul-light);
            color: var(--azul);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.82rem;
            flex-shrink: 0;
        }

        .reserva-divider {
            border: 0;
            border-top: 1px solid var(--azul-mid);
            opacity: 0.6;
            margin: 6px 0 12px;
        }

        .reserva-boton {
            text-align: right;
            padding-bottom: 12px;
        }

        .btn-detalle {
            display: inline-block;
            background: var(--azul);
            color: #ffffff;
            padding: 7px 18px;
            border-radius: 8px;
            font-size: 0.88rem;
            font-weight: 600;
            text-decoration: none;
            transition: background 0.25s ease, transform 0.2s ease;
        }

        .btn-detalle:hover {
            background: var(--azul-dark);
            color: #ffffff;
            transform: translateY(-1px);
        }

        /* Estado vacío */
        .empty-state {
            text-align: center;
            padding: 50px 20px;
            color: var(--muted);
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

        /* Contenedor principal */
        .container#container {
            padding: 0 8px;
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
            color: #000000;
        }

        .footer-item:hover .icon-container {
            transform: translateY(-7px);
            color: #007bff;
        }

        #lbl_his:hover,
        #lbl_per:hover,
        #lbl_anf:hover,
        #lbl_val:hover,
        #lbl_res:hover,
        #lbl_esp:hover {
            color: #00B7CF !important;
        }

        .header-main {
            overflow-x: hidden;
            margin-right: 1rem;
        }

        .header-tabs {
            overflow: hidden;
            border-radius: 12px;
            background-color: #ffffff;
            margin-bottom: 1rem;
            margin-left: 1.2rem;
            margin-right: 1.2rem;
            box-shadow: 0 2px 10px rgba(25, 118, 210, 0.10);
            border: 1px solid var(--azul-mid);
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
    <!-- <header>
        <div class="container-fluid text-center" style="background: linear-gradient(120deg, #1565c0 0%, #1976d2 55%, #42a5f5 100%); padding: 18px 0 14px; margin-bottom: 4px; box-shadow: 0 4px 16px rgba(21,101,192,0.18);">
            <div class="row">
                <div class="col fw-bold pt-1 pb-1" style="color:#ffffff; font-size:1.3rem; font-family:'Nunito',sans-serif; letter-spacing:0.2px;">
                    <i class="fas fa-calendar-check me-2"></i>Reservas de tus Establecimientos
                </div>
            </div>
        </div>
    </header> -->
    <?php include 'headerGestor.php'; ?>
    <div class="container" style="max-width: 1400px; margin-top: 20px;">
        <div class="row py-3 mb-4 header-main">
            <div class="col-12">
                <div class="header-tabs shadow-sm">
                    <div class="row g-0">
                        <div class="col-6">
                            <div class="header-tab header-tab-active py-3 text-center rounded-start">
                                <i class="fas fa-calendar-check me-2"></i>RESERVAS
                            </div>
                        </div>
                        <div class="col-6">
                            <a href="tusHistorias.php" class="header-tab-link">
                                <div class="header-tab py-3 text-center rounded-end">
                                    <i class="fas fa-history me-2"></i>HISTÓRICO
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
            </div> 
        </div>

        <div class="container" id="container">
        </div>
    </div>
    <?php include 'footer.php'; ?>
</body>

</html>