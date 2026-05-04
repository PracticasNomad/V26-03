<?php
require_once 'verificar_sesion_host.php';
?>
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
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.js" integrity="sha256-H+K7U5CnXl1h5ywQfKtSj8PCmoN9aaq30gDh27Xc0jk=" crossorigin="anonymous"></script>
    <link rel="icon" href="../favicon-color.png">
    <link rel="icon" href="../favicon-negro.png" media="(prefers-color-scheme: light)">
    <link rel="icon" href="../favicon-color.png" media="(prefers-color-scheme: dark)">
    <title>Tus historias</title>


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

            function hideLoadingIndicator() {}

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

                    if (data[i].space.establecimiento) {
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

                            var div = document.createElement("div");
                            div.style.backgroundColor = '#f8fbff';
                            div.style.marginTop = '20px';
                            div.className = "row pt-3 px-4 pb-3 mb-3 border rounded shadow-sm";
                            div.style.borderColor = '#90caf9';
                            div.style.borderWidth = '1px';
                            container.appendChild(div);

                            var divFecha = document.createElement("div");
                            divFecha.className = "col-12 fecha fw-bold h5 pt-1 mb-3 text-center py-2 rounded";
                            divFecha.style.backgroundColor = '#506572';
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

                if (!historiasEncontradas) {
                    container.innerHTML = `
                        <div class="alert alert-info mt-4" role="alert">
                            <i class="fas fa-info-circle me-2"></i>
                            No tienes historial de reservas anteriores.
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

        .page-hero {
            max-width: 100%;
            margin: 1.2rem 0 0.5rem;
            padding: 0;
            box-sizing: border-box;
        }

        .page-hero-inner {
            border-radius: 20px;
            background: linear-gradient(135deg, var(--host-accent-dark) 0%, var(--host-accent) 62%, #51cfee 100%);
            color: #ffffff;
            padding: 1.1rem 1.2rem;
            box-shadow: 0 18px 40px rgba(16, 191, 235, 0.28);
            border: 1px solid rgba(255, 255, 255, 0.18);
        }

        .page-hero-title {
            font-size: 1.35rem;
            font-weight: 800;
            letter-spacing: 0.2px;
        }

        .hero-title-row {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
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

        #per:checked~#lbl_per .icon-container,
        #res:checked~#lbl_res .icon-container,
        #his:checked~#lbl_his .icon-container,
        #esp:checked~#lbl_esp .icon-container {
            color: #007bff;
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

</body>

</html>