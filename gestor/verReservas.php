<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-eOJMYsd53ii+scO/bJGFsiCZc+5NDVN2yr8+0RDqr0Ql0h+rP48ckxlpbzKgwra6" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta3/dist/js/bootstrap.bundle.min.js" integrity="sha384-JEW9xMcG8R+pH31jmWH6WWP0WintQrMb4s7ZOdauHnUtxwoG2vI5DkLtS3qm9Ekf" crossorigin="anonymous"></script>
    <link href="style.css" rel="stylesheet">
    <script src="https://kit.fontawesome.com/b8814a2854.js" crossorigin="anonymous"></script>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@200&display=swap" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.js" integrity="sha256-H+K7U5CnXl1h5ywQfKtSj8PCmoN9aaq30gDh27Xc0jk=" crossorigin="anonymous"></script>
    <link rel="icon" href="favicon-color.png">

    <link rel="icon" href="favicon-negro.png" media="(prefers-color-scheme: light)">

    <link rel="icon" href="favicon-color.png" media="(prefers-color-scheme: dark)">
    <title>Tus reservas</title>
    <?php
    session_start();
    ?>

    <script>
        window.onload = function() {
            const today = new Date().toISOString().split('T')[0];
            const container = document.getElementById('container');

            showLoadingIndicator();

            const url = "AllReservasAnfitrion.php";

            fetch(url)
                .then(response => response.json())
                .then(data => {
                    hideLoadingIndicator();
                    console.log(data);
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
                        <p class="mt-3 text-primary">Cargando tus reservas...</p>
                    </div>
                `;
            }

            function hideLoadingIndicator() {}

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
                    if (data[i].space.establecimiento) {
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
                            div.style.backgroundColor = '#f8fbff';
                            div.style.marginTop = '20px';
                            div.className = "row pt-3 px-4 pb-3 mb-3 border rounded shadow-sm";
                            div.style.borderColor = '#90caf9';
                            div.style.borderWidth = '1px';
                            container.appendChild(div);

                            var divFecha = document.createElement("div");
                            divFecha.className = "col-12 fecha fw-bold h5 pt-1 mb-3 text-center py-2 rounded";
                            divFecha.style.backgroundColor = '#2196f3';
                            divFecha.style.color = 'white';
                            divFecha.textContent = fechaFormateada;
                            div.appendChild(divFecha);

                            var divContenido = document.createElement("div");
                            divContenido.className = "col-12";
                            div.appendChild(divContenido);

                            var divEspacio = document.createElement("div");
                            divEspacio.className = "h6 mb-3";
                            divEspacio.innerHTML = '<i class="fas fa-map-marker-alt me-2" style="color: #1976d2;"></i><strong style="color: #1976d2;">Espacio:</strong> ' + data[i].space.name;
                            divContenido.appendChild(divEspacio);

                            var divHorario = document.createElement("div");
                            divHorario.className = "mb-3";
                            divHorario.innerHTML = '<i class="far fa-clock me-2" style="color: #1976d2;"></i><strong style="color: #1976d2;">Horario:</strong> ' +
                                data[i].start_time.substring(0, 5) + ' - ' + data[i].end_time.substring(0, 5);
                            divContenido.appendChild(divHorario);

                            var divUsuario = document.createElement("div");
                            divUsuario.className = "mb-3";
                            divUsuario.innerHTML = '<i class="far fa-user me-2" style="color: #1976d2;"></i><strong style="color: #1976d2;">Reservado por:</strong> ' + data[i].user.name;
                            divContenido.appendChild(divUsuario);

                            var divider = document.createElement("hr");
                            divider.style.borderColor = '#bbdefb';
                            divider.style.opacity = '0.5';
                            divContenido.appendChild(divider);

                            var divBoton = document.createElement("div");
                            divBoton.className = "mt-3 text-end";
                            divContenido.appendChild(divBoton);

                            var botonDetalles = document.createElement("a");
                            botonDetalles.href = 'detalles_reserva.php?id=' + data[i].id;
                            botonDetalles.className = "btn btn-sm";
                            botonDetalles.style.backgroundColor = '#1976d2';
                            botonDetalles.style.color = 'white';
                            botonDetalles.style.boxShadow = '0 2px 5px rgba(33, 150, 243, 0.3)';
                            botonDetalles.innerHTML = '<i class="fas fa-info-circle me-1"></i>Mostrar detalles';

                            botonDetalles.onmouseover = function() {
                                this.style.backgroundColor = '#0d47a1';
                                this.style.transition = 'background-color 0.3s';
                            };
                            botonDetalles.onmouseout = function() {
                                this.style.backgroundColor = '#1976d2';
                            };

                            divBoton.appendChild(botonDetalles);
                        }

                    }

                }

                if (!reservasEncontradas) {
                    container.innerHTML = `
                        <div class="alert alert-info mt-4" role="alert">
                            <i class="fas fa-info-circle me-2"></i>
                            No tienes reservas próximas.
                        </div>
                    `;
                }
            }
        };
    </script>

    <style>
        body {
            padding-bottom: 15%;
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

        .espacio {
            border-radius: 1rem;
            background: #f3f3f3ff;
        }

        .hora {
            color: #00B7CF;
        }

        .spinner-border {
            color: #1976d2;
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
        }

        .footer-item:hover .icon-container {
            transform: translateY(-7px);
        }

        #per:checked~#lbl_per .icon-container,
        #res:checked~#lbl_res .icon-container,
        #his:checked~#lbl_his .icon-container,
        #esp:checked~#lbl_esp .icon-container {
            color: #007bff;
        }

        /* New hover styles for "Establecimientos" and "Perfil" */
        #lbl_his:hover,
        #lbl_per:hover,
        #lbl_anf:hover,
        #lbl_val:hover,
        #lbl_res:hover,
        #lbl_esp:hover {
            color: #00B7CF !important;
            /* For the text */
        }

        #lbl_his:hover .icon-container,
        #lbl_per:hover .icon-container,
        #lbl_anf:hover .icon-container,
        #lbl_val:hover .icon-container,
        #lbl_res:hover .icon-container,
        #lbl_esp:hover .icon-container {
            color: #007bff;
            /* For the icon */
        }

        .header-main {
            overflow-x: hidden;
            margin-right: 1rem;
        }

        .header-tabs {
            overflow: hidden;
            border-radius: 12px;
            background-color: white;
            margin-bottom: 1rem;
            margin-left: 3rem;
            margin-right: 3rem;
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
    <header>
        <div class="container-fluid info text-center">
            <div class="row">
                <div class="col color-white h2 fw-bold pt-3 pb-2">
                    Tus Reservas
                </div>
            </div>
        </div>
    </header>
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
                    <a href="validar.php">
                        <div class="col-12 icon-container">
                            <i class="h2 fas fa-check-circle p-1 m-0"></i>
                            <div>Validar</div>
                        </div>
                    </a>
                </div>
            </label>

            <label for="res" id="lbl_res" class="col-2 text-center footer-item">
                <div class="row">
                    <a href="tusReservas.php">
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
                            <i class="h2 fas fa-user-tie p-1 m-0"></i>
                            <div>Perfil</div>
                        </div>
                    </a>
                </div>
            </label>
        </div>
    </div>
</body>

</html>