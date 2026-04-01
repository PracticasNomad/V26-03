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
    <title>TheNomadapp - Histórico Gestor</title>

    <script>
        window.onload = function() {
            const today = new Date().toISOString().split('T')[0];
            const container = document.getElementById('container');

            showLoadingIndicator();

            // Usamos el mismo archivo que creamos antes para obtener todas las reservas
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
                        <p class="mt-3 text-primary">Cargando el histórico de tus establecimientos...</p>
                    </div>
                `;
            }

            function hideLoadingIndicator() {}

            function showErrorMessage() {
                container.innerHTML = `
                    <div class="alert alert-danger mt-4" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        No se pudo cargar el histórico. Por favor, intenta de nuevo más tarde.
                    </div>
                `;
            }

            function appendData(data) {
                container.innerHTML = '';
                let historiasEncontradas = false;

                for (var i = 0; i < data.length; i++) {
                    if (data[i].space && data[i].space.establecimiento) {

                        // FILTRO PARA HISTÓRICO: Reservas pasadas o canceladas
                        if (data[i].day < today || data[i].cancelada == true) {
                            historiasEncontradas = true;
                            const fechaReserva = new Date(data[i].day);
                            const diasSemana = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
                            const meses = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];

                            const diaSemana = diasSemana[fechaReserva.getDay()];
                            const dia = fechaReserva.getDate();
                            const mes = meses[fechaReserva.getMonth()];
                            const anio = fechaReserva.getFullYear();
                            const fechaFormateada = `${diaSemana}, ${dia} de ${mes} del ${anio}`;

                            const cancelada = data[i].cancelada == true;

                            var div = document.createElement('div');
                            div.className = 'reserva-card' + (cancelada ? ' reserva-card--cancelada' : '');
                            container.appendChild(div);

                            var divFecha = document.createElement('div');
                            divFecha.className = 'reserva-fecha' + (cancelada ? ' reserva-fecha--cancelada' : '');
                            if (cancelada) {
                                divFecha.innerHTML = '<i class="fas fa-calendar-times me-2"></i>' + fechaFormateada +
                                    ' <span class="badge-cancelada">CANCELADA</span>';
                            } else {
                                divFecha.innerHTML = '<i class="fas fa-calendar-check me-2"></i>' + fechaFormateada;
                            }
                            div.appendChild(divFecha);

                            var divContenido = document.createElement('div');
                            divContenido.className = 'reserva-body';
                            div.appendChild(divContenido);

                            var divEspacio = document.createElement('div');
                            divEspacio.className = 'reserva-row';
                            divEspacio.innerHTML = '<span class="reserva-icon"><i class="fas fa-map-marker-alt"></i></span>' +
                                '<span><strong>Espacio:</strong> ' + data[i].space.name + ' &mdash; ' + data[i].space.establecimiento.nombre + '</span>';
                            divContenido.appendChild(divEspacio);

                            var divHorario = document.createElement('div');
                            divHorario.className = 'reserva-row';
                            divHorario.innerHTML = '<span class="reserva-icon"><i class="far fa-clock"></i></span>' +
                                '<span><strong>Horario:</strong> ' + data[i].start_time.substring(0, 5) + ' &ndash; ' + data[i].end_time.substring(0, 5) + '</span>';
                            divContenido.appendChild(divHorario);

                            var nombreUsuario = data[i].user ? data[i].user.name : 'Usuario Desconocido';
                            var divUsuario = document.createElement('div');
                            divUsuario.className = 'reserva-row';
                            divUsuario.innerHTML = '<span class="reserva-icon"><i class="far fa-user"></i></span>' +
                                '<span><strong>Reservado por:</strong> ' + nombreUsuario + '</span>';
                            divContenido.appendChild(divUsuario);

                            var divider = document.createElement('hr');
                            divider.className = 'reserva-divider';
                            divContenido.appendChild(divider);

                            var divBoton = document.createElement('div');
                            divBoton.className = 'reserva-boton';
                            divContenido.appendChild(divBoton);

                            var botonDetalles = document.createElement('a');
                            botonDetalles.href = 'detalles_reserva.php?id=' + data[i].id;
                            botonDetalles.className = 'btn-detalle' + (cancelada ? ' btn-detalle--cancelada' : '');
                            botonDetalles.innerHTML = '<i class="fas fa-arrow-right me-1"></i>Ver detalles';
                            divBoton.appendChild(botonDetalles);
                        }
                    }
                }

                if (!historiasEncontradas) {
                    container.innerHTML = `
                        <div class="empty-state">
                            <div class="empty-state__icon"><i class="fas fa-history"></i></div>
                            <div class="empty-state__text">No hay histórico de reservas para tus establecimientos.</div>
                        </div>
                    `;
                }
            }
        };
    </script>

    <style>
        :root {
            --azul:        #1976d2;
            --azul-dark:   #0d47a1;
            --azul-light:  #e3f0fb;
            --azul-mid:    #bbdefb;
            --slate:       #455a68;
            --slate-dark:  #2e3f4c;
            --slate-light: #eceff1;
            --slate-mid:   #b0bec5;
            --rojo:        #c62828;
            --rojo-dark:   #8e0000;
            --rojo-light:  #ffebee;
            --rojo-mid:    #ef9a9a;
            --text:        #1a2333;
            --muted:       #546e8a;
        }

        body {
            padding-bottom: 15%;
            background: linear-gradient(160deg, #eef2f5 0%, #f5f5f5 50%, #f0f4f8 100%);
            min-height: 100vh;
            font-family: 'Nunito', sans-serif;
        }

        a, a:visited, a:active { color: black; text-decoration: none; }

        .spinner-border { color: var(--azul); }

        /* ── Tarjeta ── */
        .reserva-card {
            background: #ffffff;
            border: 1px solid var(--slate-mid);
            border-radius: 16px;
            margin-bottom: 18px;
            overflow: hidden;
            box-shadow: 0 4px 18px rgba(69, 90, 104, 0.10);
            transition: transform 0.22s ease, box-shadow 0.22s ease;
        }
        .reserva-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 28px rgba(69, 90, 104, 0.16);
        }
        .reserva-card--cancelada {
            border-color: var(--rojo-mid);
            box-shadow: 0 4px 18px rgba(198, 40, 40, 0.10);
        }
        .reserva-card--cancelada:hover {
            box-shadow: 0 10px 28px rgba(198, 40, 40, 0.18);
        }

        /* Banda de fecha */
        .reserva-fecha {
            background: linear-gradient(120deg, var(--slate-dark) 0%, var(--slate) 60%, #607d8b 100%);
            color: #ffffff;
            font-weight: 700;
            font-size: 1rem;
            padding: 12px 20px;
            display: flex;
            align-items: center;
            gap: 8px;
            letter-spacing: 0.2px;
        }
        .reserva-fecha--cancelada {
            background: linear-gradient(120deg, var(--rojo-dark) 0%, var(--rojo) 60%, #e53935 100%);
        }

        .badge-cancelada {
            margin-left: auto;
            background: rgba(255,255,255,0.22);
            border: 1px solid rgba(255,255,255,0.40);
            color: #fff;
            font-size: 0.72rem;
            font-weight: 700;
            padding: 2px 10px;
            border-radius: 20px;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        /* Cuerpo */
        .reserva-body {
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
            background: var(--slate-light);
            color: var(--slate);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.82rem;
            flex-shrink: 0;
        }
        .reserva-card--cancelada .reserva-icon {
            background: var(--rojo-light);
            color: var(--rojo);
        }

        .reserva-divider {
            border: 0;
            border-top: 1px solid var(--slate-mid);
            opacity: 0.5;
            margin: 6px 0 12px;
        }
        .reserva-card--cancelada .reserva-divider {
            border-color: var(--rojo-mid);
        }

        .reserva-boton { text-align: right; padding-bottom: 12px; }

        .btn-detalle {
            display: inline-block;
            background: var(--slate);
            color: #ffffff;
            padding: 7px 18px;
            border-radius: 8px;
            font-size: 0.88rem;
            font-weight: 600;
            text-decoration: none;
            transition: background 0.25s ease, transform 0.2s ease;
        }
        .btn-detalle:hover {
            background: var(--slate-dark);
            color: #ffffff;
            transform: translateY(-1px);
        }
        .btn-detalle--cancelada { background: var(--rojo); }
        .btn-detalle--cancelada:hover { background: var(--rojo-dark); }

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
        .container#container { padding: 0 8px; }

        /* Footer */
        .footer-container {
            background-color: white;
            box-shadow: 0px -2px 10px rgba(0, 0, 0, 0.1);
            padding-top: 1px !important;
            padding-bottom: 1px !important;
            height: auto;
        }
        .footer-item { padding: 8px 0; }
        .icon-container {
            transition: transform 0.3s ease;
            padding: 5px 0;
            color: #000000;
        }
        .footer-item:hover .icon-container {
            transform: translateY(-7px);
            color: #007bff;
        }

        /* Tabs */
        .header-main { overflow-x: hidden; margin-right: 1rem; }
        .header-tabs {
            overflow: hidden;
            border-radius: 12px;
            background-color: #ffffff;
            margin-bottom: 1rem;
            margin-left: 1.2rem;
            margin-right: 1.2rem;
            box-shadow: 0 2px 10px rgba(69,90,104,0.10);
            border: 1px solid var(--slate-mid);
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

        @media (max-width: 576px) {
            .header-tabs { margin-left: 0.5rem; margin-right: 0.5rem; }
            .reserva-fecha { font-size: 0.88rem; }
        }
    </style>
</head>

<body>
    <header>
        <div class="container-fluid text-center" style="background: linear-gradient(120deg, #2e3f4c 0%, #455a68 55%, #607d8b 100%); padding: 18px 0 14px; margin-bottom: 4px; box-shadow: 0 4px 16px rgba(46,63,76,0.18);">
            <div class="row">
                <div class="col fw-bold pt-1 pb-1" style="color:#ffffff; font-size:1.3rem; font-family:'Nunito',sans-serif; letter-spacing:0.2px;">
                    <i class="fas fa-history me-2"></i>Histórico de tus Establecimientos
                </div>
            </div>
        </div>
    </header>

    <div class="row py-3 mb-4 header-main">
        <div class="col-12">
            <div class="header-tabs shadow-sm">
                <div class="row g-0">
                    <div class="col-6">
                        <a href="verReservas.php" class="header-tab-link">
                            <div class="header-tab py-3 text-center rounded-start">
                                <i class="fas fa-calendar-check me-2"></i>RESERVAS
                            </div>
                        </a>
                    </div>
                    <div class="col-6">
                        <div class="header-tab header-tab-active py-3 text-center rounded-end">
                            <i class="fas fa-history me-2"></i>HISTÓRICO
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container" id="container">
    </div>

    <div class="container-fluid footer mt-5 p-3">
        <div class="row text-center fixed-bottom bg-blanco pt-1 px-2 footer-container">
            <label for="anf" class="col-2 text-center footer-item">
                <div class="row"><a href="Anfitriones.php">
                        <div class="col-12 icon-container"><i class="h2 fas fa-users p-1 m-0"></i>
                            <div>Anfitriones</div>
                        </div>
                    </a></div>
            </label>
            <label for="val" class="col-2 text-center footer-item">
                <div class="row"><a href="verValidar.php">
                        <div class="col-12 icon-container"><i class="h2 fas fa-check-circle p-1 m-0"></i>
                            <div>Validar</div>
                        </div>
                    </a></div>
            </label>
            <label for="res" class="col-2 text-center footer-item">
                <div class="row"><a href="verReservas.php">
                        <div class="col-12 icon-container" style="color: #007bff;"><i class="h2 fas fa-book-open p-1 m-0"></i>
                            <div>Reservas</div>
                        </div>
                    </a></div>
            </label>
            <label for="his" class="col-2 text-center footer-item">
                <div class="row"><a href="verEstablecimientos.php">
                        <div class="col-12 icon-container"><i class="h2 fas fa-building p-1 m-0"></i>
                            <div>Establecimientos</div>
                        </div>
                    </a></div>
            </label>
            <label for="esp" class="col-2 text-center footer-item">
                <div class="row"><a href="verEspacios.php">
                        <div class="col-12 icon-container"><i class="h2 fas fa-chair p-1 m-0"></i>
                            <div>Espacios</div>
                        </div>
                    </a></div>
            </label>
            <label for="per" class="col-2 text-center footer-item">
                <div class="row"><a href="tuPerfil.php">
                        <div class="col-12 icon-container"><i class="h2 fas fa-user-tie p-1 m-0"></i>
                            <div>Perfil</div>
                        </div>
                    </a></div>
            </label>
        </div>
    </div>
</body>

</html>