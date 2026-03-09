<?php
require_once 'verificar_sesion_guest.php';
$_SESSION['anfitrion_id'] = $_GET['id'];
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://kit.fontawesome.com/b8814a2854.js" crossorigin="anonymous"></script>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@200&display=swap" rel="stylesheet">
    <script src='https://api.mapbox.com/mapbox.js/v3.3.1/mapbox.js'></script>
    <link href='https://api.mapbox.com/mapbox.js/v3.3.1/mapbox.css' rel='stylesheet' />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-+0n0xVW2eSR5OomGNYDnhzAbDsOXxcvSN1TPprVMTNDbiYZCxYbOOl7+AMvyTG2x" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta3/dist/js/bootstrap.bundle.min.js" integrity="sha384-JEW9xMcG8R+pH31jmWH6WWP0WintQrMb4s7ZOdauHnUtxwoG2vI5DkLtS3qm9Ekf" crossorigin="anonymous"></script>
    <link rel="icon" href="favicon-color.png">

    <link rel="icon" href="favicon-negro.png" media="(prefers-color-scheme: light)">

    <link rel="icon" href="favicon-color.png" media="(prefers-color-scheme: dark)">
    <title>TheNomadApp - Ver Anfitrión</title>

    <script>
        window.onload = function() {
            document.getElementById('salir').scrollIntoView();
        }
        window.onbeforeunload = function() {
            window.scrollTo(0, document.body.scrollHeight)
        };

        function scroll() {
            window.scrollTo(0, document.body.scrollHeight);
        }

        let url = "getAnfitrionById.php";

        fetch(url)
            .then(response => response.json())
            .then(data => {
                console.log(data + "%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%~");
                applyData(data);
            })
            .catch(err => console.log(err));

        url = "nomadasLerrApi.php";

        fetch(url)
            .then(response => response.json())
            .then(data => {
                console.log("######################" + data);
                appendData(data);
            })
            .catch(err => {
                console.log(err);
                errorData();
            });


        function errorData() {
            const resultado = document.getElementById("espacios_disponibles");
            resultado.innerHTML = '<p><a href="login.php" style="text-decoration: none; font-weight: bold; ">Inicia Sesión</a> para poder ver los espacios disponibles y reservar uno.</p>';
        }

        function applyData(data) {
            let about_us = document.getElementById("about_us");
            about_us.innerHTML = '';
            about_us.innerHTML = data.descripcion;

            let icono_wifi = document.getElementById("icono_wifi");
            let icono_food = document.getElementById("icono_food");
            let icono_parking = document.getElementById("icono_parking");

            if (data.has_wifi != true) {
                icono_wifi.display = "none";
                icono_wifi.style.display = "none";
            }
            if (data.has_food != true) {
                icono_food.display = "none";
                icono_food.style.display = "none";
            }
            if (data.has_parking != true) {
                icono_parking.display = "none";
                icono_parking.style.display = "none";
                let parking_precio = document.getElementById("parking_precio");
                parking_precio.innerHTML = '';
            } else {
                let precio = document.getElementById("precio");
                precio.innerText = data.parking_price + " €";
            }

            // Actualizar el carrusel con las imágenes de la galería
            updateCarousel(data.gallery);
        }

        function updateCarousel(gallery) {
            // Actualizar indicadores
            const indicatorsContainer = document.querySelector('.carousel-indicators');
            indicatorsContainer.innerHTML = '';

            // Actualizar contenido del carrusel
            const carouselInner = document.querySelector('.carousel-inner');
            carouselInner.innerHTML = '';

            gallery.forEach((imagen, index) => {
                // Crear indicador
                const indicator = document.createElement('button');
                indicator.type = 'button';
                indicator.setAttribute('data-bs-target', '#my_carousel');
                indicator.setAttribute('data-bs-slide-to', index.toString());
                indicator.setAttribute('aria-label', `Slide ${index + 1}`);
                if (index === 0) {
                    indicator.classList.add('active');
                    indicator.setAttribute('aria-current', 'true');
                }
                indicatorsContainer.appendChild(indicator);

                // Crear item del carrusel
                const carouselItem = document.createElement('div');
                carouselItem.classList.add('carousel-item');
                if (index === 0) {
                    carouselItem.classList.add('active');
                }

                const img = document.createElement('img');
                var imagen = imagen.image_url;
                if (imagen != "./img/noimagen.jpg") {
                    imagen = "http://" + imagen;
                }
                img.src = imagen;
                img.classList.add('d-block', 'w-100');
                img.alt = `Imagen ${index + 1}`;
                img.style.height = '500px'; // Altura fija aumentada
                img.style.objectFit = 'contain'; // Cambiado para mostrar imagen completa
                img.style.backgroundColor = '#f8f9fa'; // Fondo gris claro

                carouselItem.appendChild(img);
                carouselInner.appendChild(carouselItem);
            });
        }

        function appendData(data) {
            const resultado = document.getElementById("espacios_disponibles");
            resultado.innerHTML = '';

            data.forEach((item, index) => {
                const expander = document.createElement("div");
                expander.className = "expander-container mb-3";
                expander.style.border = "1px solid #00B7CF";
                expander.style.borderRadius = "10px";
                expander.style.overflow = "hidden";

                const header = document.createElement("div");
                header.className = "expander-header d-flex justify-content-between align-items-center p-3";
                header.style.backgroundColor = "#00B7CF";
                header.style.color = "white";
                header.style.cursor = "pointer";

                const title = document.createElement("div");
                title.className = "fw-bold";
                title.textContent = item.name;

                const icon = document.createElement("i");
                icon.className = "fas fa-chevron-down";

                header.appendChild(title);
                header.appendChild(icon);

                const content = document.createElement("div");
                content.className = "expander-content p-3";
                content.style.display = "none";
                content.style.backgroundColor = "white";
                content.style.color = "#333";

                const description = document.createElement("div");
                description.className = "mb-3 mt-2";
                description.style.fontSize = "16px";
                description.style.lineHeight = "1.5";
                description.style.color = "#333";
                description.innerHTML = `${item.description}`;
                content.appendChild(description);

                for (let i = 0; i < item.schedule.length; i++) {
                    console.log(item.schedule[i]);
                    const days = document.createElement("div");
                    days.className = "mb-3 text-center";

                    const daysTitle = document.createElement("h4");
                    daysTitle.className = "fw-bold mb-4";
                    daysTitle.style.color = "#000000";
                    if (i == 0) {
                        daysTitle.textContent = "Disponibilidad";
                    }

                    days.appendChild(daysTitle);

                    const daysContainer = document.createElement("div");
                    daysContainer.className = "d-flex gap-2 justify-content-center mt-2";
                    days.appendChild(daysContainer);

                    const weekDays = [{
                            key: "has_monday",
                            label: "L"
                        },
                        {
                            key: "has_tuesday",
                            label: "M"
                        },
                        {
                            key: "has_wednesday",
                            label: "X"
                        },
                        {
                            key: "has_thursday",
                            label: "J"
                        },
                        {
                            key: "has_friday",
                            label: "V"
                        },
                        {
                            key: "has_saturday",
                            label: "S"
                        },
                        {
                            key: "has_sunday",
                            label: "D"
                        }
                    ];

                    weekDays.forEach(day => {
                        const dayIndicator = document.createElement("div");
                        dayIndicator.className = "day-indicator text-center d-flex justify-content-center align-items-center rounded-circle";
                        dayIndicator.style.width = "40px";
                        dayIndicator.style.height = "40px";
                        dayIndicator.style.fontWeight = "bold";

                        if (item.schedule[i][day.key]) {
                            dayIndicator.style.backgroundColor = "#BDE742";
                            dayIndicator.style.color = "black";
                        } else {
                            dayIndicator.style.backgroundColor = "#FF6B6B";
                            dayIndicator.style.color = "white";
                        }

                        dayIndicator.textContent = day.label;
                        daysContainer.appendChild(dayIndicator);
                    });

                    content.appendChild(days);

                    const schedule = document.createElement("div");
                    schedule.className = "mb-3 text-center h5";
                    schedule.style.color = "#333";
                    schedule.style.fontWeight = "bold";
                    console.log(item.schedule);
                    schedule.innerHTML = `${item.schedule[i].start_time.substring(0, 5)} - ${item.schedule[i].end_time.substring(0, 5)}`;
                    content.appendChild(schedule);
                }

                const buttonContainer = document.createElement("div");
                buttonContainer.className = "text-center mt-4";

                const reserveButton = document.createElement("button");
                reserveButton.className = "btn btn-success px-4";
                reserveButton.textContent = "Reservar";
                reserveButton.type = "button";
                reserveButton.addEventListener("click", function() {
                    showReservationPopup(item);
                });

                buttonContainer.appendChild(reserveButton);
                content.appendChild(buttonContainer);

                expander.appendChild(header);
                expander.appendChild(content);

                resultado.appendChild(expander);

                header.addEventListener("click", function() {
                    if (content.style.display === "none") {
                        content.style.display = "block";
                        icon.className = "fas fa-chevron-up";
                    } else {
                        content.style.display = "none";
                        icon.className = "fas fa-chevron-down";
                    }
                });
            });
        }

        function showReservationPopup(item) {
            location.href = "reservarEspacio.php?id=" + item.id;
        }
    </script>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Nunito', sans-serif;
            background-color: #E3E1E1;
            padding-inline: 5vw;
        }

        #imgheader {
            height: 400px;
        }

        #map {
            width: 100%;
            height: 100%;
            min-height: 200px;
            border-radius: 15px;
        }

        #map2 {
            width: 100%;
            height: 100%;
            min-height: 200px;
            border-radius: 15px;
            margin-block: 30px;
        }

        .carousel_box {
            width: 100%;
        }

        .carousel-inner {
            border-radius: 1rem;
        }

        .carousel-indicators button {
            position: relative;
            top: 3rem;
            margin-left: 10px !important;
            height: 10px !important;
            width: 10px !important;
            border-radius: 5px !important;
        }

        .header {
            width: 100%;
            margin: 3vh auto;
            background-color: #00B7CF;
            color: white;
            border-radius: 20px;
        }

        .contenedor1 {
            /* padding:25px; */
            margin-top: 4vh;
            margin-bottom: 2vh;
            margin-inline: 0;
        }

        .contenedor1-movil {
            display: none;
        }

        .contenedor2 {
            background-color: white;
            border-radius: 20px;
            margin-inline: auto;
            margin-bottom: 4vh;
            padding-top: 5vh;
            width: 100%;
        }

        .centrar {
            text-align: center;
            align-items: center;
        }

        .sombra {
            box-shadow: 0 2px 4px rgba(0, 0, 0, .25), 0 8px 16px rgba(0, 0, 0, .25);
        }

        .icons {
            color: #4CCBD4;
        }

        .select_day {
            border-radius: 0.5rem;
            background: white;
            color: black;
        }

        .select_day div div input {
            background: white;
            border: none;
            font-weight: bold;
            color: black;
        }

        .disponibilidad .col-12 {
            color: #00B7CF;
        }

        .disponibilidad .col-3 div {
            border-radius: 0.5rem;
            color: black;
            background-color: #BDE742;
        }

        /* .exit button {
            border: none;
            border-radius: 1rem;
            background-color: chocolate;
            height: 2rem;
        }

        .exit button a {
            color: white;
            text-decoration: none;
        } */



        #developed {
            color: gray;
            transition: 0.3s;
        }

        #developed:hover {
            color: #A3FF2E;
        }

        #avisos a {
            color: lightgray;
            display: inline-block;
            padding-left: 20px;
        }

        .anfitrionDetalles {
            background-color: #00B7CF;
        }

        .transparent {
            background-color: transparent;
        }

        .btn {
            margin: 5px auto;
            color: whitesmoke;
            text-transform: initial;
            font-family: sans-serif;
            font-size: 16px;
            border-radius: 20px;
            cursor: pointer;
            box-shadow: 0 2px 4px rgba(0, 0, 0, .15), 0 8px 16px rgba(0, 0, 0, .15);
            padding: 5px;
        }

        .btn-primary,
        .btn-primary:focus {
            background-color: #00B7CF;
            border: none;
        }

        .btn-success,
        .btn-success:focus {
            background-color: #81ba18;
            border: none;
        }

        .btn-cancel,
        .btn-cancel:focus {
            background-color: #a4a4a4;
            border: none;
        }

        .btn-primary:hover {
            background-color: #4CCBD4;
        }

        .btn-success:hover {
            background-color: #BDE742;
        }

        .btn-cancel:hover {
            background-color: #c6c6c6;
            color: white;
        }

        @media only screen and (max-width: 700px) {
            .contenedor1 {
                display: none;
            }

            .contenedor1-movil {
                display: block;
                margin-top: 4vh;
                margin-bottom: 2vh;
                margin-inline: 0;
            }

        }

        #imgheader {
            max-height: 600px;
            /* Aumentado de 400px */
        }

        #map {
            width: 100%;
            height: 100%;
            max-height: 650px;
            /* Aumentado de 200px */
            border-radius: 15px;
        }

        #map2 {
            width: 100%;
            height: 100%;
            max-height: 650px;
            /* Aumentado de 200px */
            border-radius: 15px;
            margin-block: 30px;
        }

        .carousel-inner {
            border-radius: 1rem;
            max-height: 650px;
            /* Añadido para hacer el carrusel más alto */
        }

        .carousel-inner img {
            max-height: 650px !important;
            /* Altura fija para todas las imágenes */
            object-fit: fill !important;
            /* Cambiado de cover a contain para mostrar imagen completa */
            background-color: #f8f9fa;
            /* Color de fondo para el espacio vacío */
        }
    </style>
</head>

<body>
    <div class="header sombra row p-3">
        <div class="col-12 h3 text-center p-1 fw-bold">
            <?php
            if (isset($_GET['nombre'])) {
                $nombre = $_GET['nombre'];
                echo $nombre;
            } else echo "Nombre";
            ?>
        </div>
        <div class="col-12 h5 text-center p-1 fw-bold">
            <?php
            if (isset($_GET['calle'])) {
                $calle = $_GET['calle'];
                echo $calle;
            }

            if (isset($_GET['direccion'])) {
                $direccion = $_GET['direccion'];
                echo $direccion;
            } else echo "Direccion";
            ?>
        </div>
        <!-- NUEVAS LÍNEAS PARA PISO Y PROVINCIA -->
        <div class="col-12 h5 text-center p-1 fw-bold">
            <?php
            if (isset($_GET['piso'])) {
                $piso = $_GET['piso'];
                echo "Piso: " . $piso;
            }
            ?>
        </div>
        <div class="col-12 h5 text-center p-1 fw-bold">
            <?php
            if (isset($_GET['provincia'])) {
                $provincia = $_GET['provincia'];
                echo $provincia;
            }
            ?>
        </div>
        <div class="col-12 h5 text-center p-1 fw-bold">
            <?php
            if (isset($_GET['ciudad'])) {
                $ciudad = $_GET['ciudad'];
                echo $ciudad;
            }
            ?>
        </div>
    </div>

    <div class="contenedor1 row" id="contenedor1">
        <div class="col">
            <div class="carousel_box">
                <div id="my_carousel" class="carousel carousel-dark slide" data-bs-ride="carousel">
                    <div class="carousel-indicators">
                        <button type="button" data-bs-target="#my_carousel" data-bs-slide-to="0" class="active" aria-current="true" aria-label="Slide 1"></button>
                        <button type="button" data-bs-target="#my_carousel" data-bs-slide-to="1" aria-label="Slide 2"></button>
                        <button type="button" data-bs-target="#my_carousel" data-bs-slide-to="2" aria-label="Slide 3"></button>
                    </div>
                    <div class="carousel-inner sombra">
                        <div class="carousel-item active">
                            <img src="https://images.pexels.com/photos/1170412/pexels-photo-1170412.jpeg?auto=compress&cs=tinysrgb&dpr=2&h=650&w=940" class="d-block w-100" alt="...">
                        </div>
                        <div class="carousel-item">
                            <img src="https://images.pexels.com/photos/3952034/pexels-photo-3952034.jpeg?auto=compress&cs=tinysrgb&dpr=2&h=650&w=940" class="d-block w-100" alt="...">
                        </div>
                        <div class="carousel-item">
                            <img src="https://images.pexels.com/photos/6077368/pexels-photo-6077368.jpeg?auto=compress&cs=tinysrgb&dpr=2&h=650&w=940" class="d-block w-100" alt="...">
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col">
            <div id="map" class="sombra"></div>
        </div>
    </div>

    <div class="contenedor1-movil" id="contenedor1-movil">
        <div class="row">
            <div class="col">
                <div class="carousel_box">
                    <div id="my_carousel" class="carousel carousel-dark slide" data-bs-ride="carousel">
                        <div class="carousel-indicators">
                            <button type="button" data-bs-target="#my_carousel" data-bs-slide-to="0" class="active" aria-current="true" aria-label="Slide 1"></button>
                            <button type="button" data-bs-target="#my_carousel" data-bs-slide-to="1" aria-label="Slide 2"></button>
                            <button type="button" data-bs-target="#my_carousel" data-bs-slide-to="2" aria-label="Slide 3"></button>
                        </div>
                        <div class="carousel-inner sombra">
                            <div class="carousel-item active">
                                <img src="https://images.pexels.com/photos/1170412/pexels-photo-1170412.jpeg?auto=compress&cs=tinysrgb&dpr=2&h=650&w=940" class="d-block w-100" alt="...">
                            </div>
                            <div class="carousel-item">
                                <img src="https://images.pexels.com/photos/3952034/pexels-photo-3952034.jpeg?auto=compress&cs=tinysrgb&dpr=2&h=650&w=940" class="d-block w-100" alt="...">
                            </div>
                            <div class="carousel-item">
                                <img src="https://images.pexels.com/photos/6077368/pexels-photo-6077368.jpeg?auto=compress&cs=tinysrgb&dpr=2&h=650&w=940" class="d-block w-100" alt="...">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col">
                <div id="map2" class="sombra"></div>
            </div>
        </div>
    </div>
    <form name="Filter" method="POST">
        <br>
        <div class="row contenedor2 sombra">
            <div class="row">
                <div class="col fw-bold text-center">
                    <div class="col-12 h5 fw-bold">
                        Sobre Nosotros:
                    </div>
                    <div id="about_us" class="h5"> </div>
                    <div class="col-12 h5 fw-bold">
                        Servicios Extras:
                    </div>
                    <div class="icons h4 p-1">
                        <i class="fas fa-wifi" id="icono_wifi"></i>
                        <i class="fas fa-utensils" id="icono_food"></i>
                        <i class="fas fa-parking" id="icono_parking"></i>
                        <br>
                    </div>
                    <div class="row" id="parking_precio">
                        <div class="col-12 h5 fw-bold">
                            Precio del parking:
                        </div>
                        <div class="col-12 h5 fw-bold" id="precio">
                        </div>
                    </div>
                </div>
            </div>
            <br>
            <div class="offset-1 col-10 text-center select_day border fw-bold">
                <div class="col-12 text-center" style="max-height: 400px; overflow-y: auto; overflow-x:hidden;">
                    <div class="row disponibilidad" id="dispo" name="dispo">
                        <div class="col-12 pb-1 pt-4 fw-bold h4">
                            Espacios Disponibles
                        </div>
                        <div id="espacios_disponibles" class="col-12">
                        </div>
                    </div>
                </div>

            </div>
            <div class="col-12 text-center my-5">
                <button class="btn btn-cancel px-4" id="salir" type="button" onclick="location.href = new URLSearchParams(window.location.search).get('fromIndex') === 'true' ? 'index.php' : 'nomada_explorar.php'">
                    Volver
                </button>
            </div>
    </form>


</body>

<script>
    var ciudaBuscadaY = <?php echo $_GET['coordinates1']; ?>;
    var ciudaBuscadaX = <?php echo $_GET['coordinates0']; ?>;
    var nombreLugar = "<?php echo $_GET['nombre']; ?>";
    var direccionLugar = "<?php echo isset($_GET['direccion']) ? $_GET['direccion'] : ''; ?>";

    console.log(ciudaBuscadaY);
    console.log(ciudaBuscadaX);
    var myIcon = L.icon({
        iconUrl: 'img/posicionAnfitrion.png',
        iconSize: [30, 30],
        iconAnchor: [15, 32],
    });

    // Mapa para vista desktop
    if (document.getElementById("contenedor1").display != "none") {
        L.mapbox.accessToken = 'pk.eyJ1IjoiYW5kcnplamJhbmFzIiwiYSI6ImNrcHdrZXIyYTAyZWkyb3AwNGtpbmtrbXYifQ.PN_iZ4Mh08-V5EXHAHpCSg';
        var mapDesktop = L.mapbox.map('map')
            .setView([ciudaBuscadaY, ciudaBuscadaX], 14)
            .addLayer(L.mapbox.styleLayer('mapbox://styles/mapbox/streets-v11'));

        var popupDesktop = L.popup({
            offset: L.point(0, -20)
        }).setContent('<div class="colorTitleSitio text-center">' + nombreLugar + '</div><div class="text-center">' + direccionLugar + '</div>');

        L.marker([ciudaBuscadaY, ciudaBuscadaX], {
            icon: myIcon
        }).addTo(mapDesktop).bindPopup(popupDesktop);
    }

    L.mapbox.accessToken = 'pk.eyJ1IjoiYW5kcnplamJhbmFzIiwiYSI6ImNrcHdrZXIyYTAyZWkyb3AwNGtpbmtrbXYifQ.PN_iZ4Mh08-V5EXHAHpCSg';
    var mapMobile = L.mapbox.map('map2')
        .setView([ciudaBuscadaY, ciudaBuscadaX], 14)
        .addLayer(L.mapbox.styleLayer('mapbox://styles/mapbox/streets-v11'));

    var popupMobile = L.popup({
        offset: L.point(0, -20)
    }).setContent('<div class="colorTitleSitio text-center">' + nombreLugar + '</div><div class="text-center">' + direccionLugar + '</div>');

    L.marker([ciudaBuscadaY, ciudaBuscadaX], {
        icon: myIcon
    }).addTo(mapMobile).bindPopup(popupMobile);
</script>

</html>