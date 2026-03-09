<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@500&display=swap" rel="stylesheet">
    <!-- Font Awesome para el icono del candado -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <script src='https://api.mapbox.com/mapbox.js/v3.3.1/mapbox.js'></script>
    <link href='https://api.mapbox.com/mapbox.js/v3.3.1/mapbox.css' rel='stylesheet' />

    <link href="https://api.mapbox.com/mapbox-gl-js/v2.9.1/mapbox-gl.css" rel="stylesheet">
    <script src="https://api.mapbox.com/mapbox-gl-js/v2.9.1/mapbox-gl.js"></script>

    <script src="https://cdn.jsdelivr.net/npm/es6-promise@4/dist/es6-promise.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/es6-promise@4/dist/es6-promise.auto.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/push.js/0.0.11/push.min.js"></script>

    <link rel="icon" href="favicon-color.png">

    <link rel="icon" href="favicon-negro.png" media="(prefers-color-scheme: light)">

    <link rel="icon" href="favicon-color.png" media="(prefers-color-scheme: dark)">
    <link rel="stylesheet" href="style.css">

    <style>
        .lock-icon {
            position: absolute;
            top: 10px;
            right: 10px;
            font-size: 20px;
            color: #333;
            cursor: pointer;
            z-index: 1000;
            transition: color 0.3s ease;
            background: rgba(255, 255, 255, 0.9);
            padding: 8px;
            border-radius: 4px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .lock-icon:hover {
            color: #007bff;
            background: rgba(255, 255, 255, 1);
        }

        /* Responsive para móviles */
        @media (max-width: 768px) {
            .lock-icon {
                top: 8px;
                right: 8px;
                font-size: 18px;
                padding: 6px;
            }
        }
    </style>

    <title>TheNomadApp - Encuentra un espacio de trabajo donde tu quieras</title>
</head>

<body>
    <div class="contenedor-index">
        <div class="header hr">
            <img src="img/logoNomada.png" alt="">
            <span class="logo">TheNomadApp</span>
        </div>

        <!-- Icono del candado -->
        <div class="lock-icon" title="Acceso Gestor" onclick="location.href='gestor/inicio_sesion_gestor.php'">
            <i class="fas fa-lock"></i>
        </div>

        <div class="bienvenida izquierda sombra">

            <div class="textoBienvenida" id="textoBienvenida">
                <br>ENCUENTRA <br>UN ESPACIO DE TRABAJO <br>DONDE TÚ QUIERAS.
                <br><br>
                <button class="btn-index btn-primary fw-bold" onclick="location.href='anfitrion/inicio_sesion_anfitrion.php'">Tengo un espacio</button>
                <button class="btn-index btn-success fw-bold" onclick="location.href='login.php'">Busco un espacio</button>
            </div>

        </div>
        <div id="map" class="map derecha flex"></div>
        <div class="botonesMovil">
            <button class="btn-index btn-primary fw-bold" type="button" onclick="location.href='anfitrion/inicio_sesion_anfitrion.php'">Tengo un espacio</button>
            <button class="btn-index btn-success fw-bold" type="button" onclick="location.href='login.php'">Busco un espacio</button>
        </div>
    </div>
    <script>
        document.fonts.ready.then(() => {
            document.body.classList.add('fonts-loaded');
        });

        let latitud, longitud, zoom;
        navigator.geolocation.getCurrentPosition(showPosition, positionError);

        function positionError() {
            latitud = 40.46;
            longitud = -3.74;
            zoom = 6;
            showMap();
        }

        function showPosition(position) {
            latitud = position.coords.latitude;
            longitud = position.coords.longitude;
            zoom = 13;
            showMap();
        }

        function showMap() {

            L.mapbox.accessToken = 'pk.eyJ1IjoiYW5kcnplamJhbmFzIiwiYSI6ImNrcHdrZXIyYTAyZWkyb3AwNGtpbmtrbXYifQ.PN_iZ4Mh08-V5EXHAHpCSg';

            var map = L.mapbox.map('map')
                .setView([latitud, longitud], zoom)
                .addLayer(L.mapbox.styleLayer('mapbox://styles/mapbox/streets-v11'));

            var myIcon = L.icon({
                iconUrl: 'img/posicionAnfitrion.png',
                iconSize: [30, 30],
                iconAnchor: [15, 32],
            });

            const url = "mapaLerrApi.php";
            fetch(url)
                .then(response => response.json())
                .then(data => {
                    data.forEach(element => {
                        if (element.latitude != null && element.longitude != null) {
                            var myPopup = L.popup({
                                offset: L.point(0, -20)
                            }).setContent('<a href="anfitrion.php?nombre=' + element.nombre + '&id=' + element.id + '&direccion=' + element.direccion + ", " + element.localidad + '&coordinates0=' + element.longitude + '&coordinates1=' + element.latitude + '&fromIndex=true"' + '<div class="colorTitleSitio text-center">' + element.nombre + '</div><div class="text-center">' + '</a>' + element.direccion + ", " + element.localidad + '</div>');
                            // }).setContent('<a href="anfitrion.php?nombre=' + element.properties.title + '&direccion=' + element.properties.description + '&coordinates0=' + element.geometry.coordinates[0] + '&coordinates1=' + element.geometry.coordinates[1] + '"' + '<div class="colorTitleSitio text-center">' + element.properties.title + '</div><div class="text-center">' + '</a>' + element.properties.description + '</div>' + '<div class="text-center">' + element.geometry.coordinates[0] + '</div>' + '<div class="text-center">' + element.geometry.coordinates[1] + '</div>');
                            L.marker([element.latitude, element.longitude]).addTo(map).setIcon(myIcon).bindPopup(myPopup);
                        }

                    })
                })
                .catch(err => console.log(err));
        }

        // Funcionalidad del candado - redirige a gestor
        document.querySelector('.lock-icon').addEventListener('click', function() {
            window.location.href = 'gestor/inicio_sesion_gestor.php';
        });
    </script>
</body>

</html>