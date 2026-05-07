<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <!-- Mapa -->
    <script src='https://api.mapbox.com/mapbox.js/v3.3.1/mapbox.js'></script>
    <link href='https://api.mapbox.com/mapbox.js/v3.3.1/mapbox.css' rel='stylesheet' />

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

        /* Estilos para la ventana flotante de aviso */
        @keyframes fadeInModal {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .btn-modal-hover:hover {
            filter: brightness(0.9);
            transform: translateY(-1px);
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
        
        <!-- Mapa div -->
        <div id="map" class="map derecha flex"></div>
        
        <div class="botonesMovil">
            <button class="btn-index btn-primary fw-bold" type="button" onclick="location.href='anfitrion/inicio_sesion_anfitrion.php'">Tengo un espacio</button>
            <button class="btn-index btn-success fw-bold" type="button" onclick="location.href='login.php'">Busco un espacio</button>
        </div>
    </div>

    <div id="loginModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.6); z-index:9999; flex-direction:column; justify-content:center; align-items:center; backdrop-filter: blur(3px);">
        <div style="background:white; padding:35px 25px; border-radius:20px; text-align:center; max-width:400px; width:90%; box-shadow:0 15px 30px rgba(0,0,0,0.3); animation: fadeInModal 0.3s ease;">
            <i class="fas fa-user-lock fa-3x" style="color:#00B7CF; margin-bottom:20px;"></i>
            <h3 style="font-family:'Nunito', sans-serif; font-weight:800; color:#333; margin-bottom: 15px; font-size:1.5rem;">¡Identifícate primero!</h3>
            <p style="font-family:'Nunito', sans-serif; color:#666; margin-bottom:30px; line-height: 1.5; font-size:1rem;">Para ver los detalles de este espacio y poder reservarlo, necesitas iniciar sesión como <b>Nómada</b>.</p>
            <div style="display:flex; gap:15px; justify-content:center;">
                <button class="btn-modal-hover" onclick="document.getElementById('loginModal').style.display='none'" style="padding:10px 20px; border-radius:25px; border:none; background:#e9ecef; color:#333; font-weight:bold; cursor:pointer; transition: 0.2s; flex: 1;">Cancelar</button>
                <button class="btn-modal-hover" onclick="location.href='login.php'" style="padding:10px 20px; border-radius:25px; border:none; background:#81ba18; color:white; font-weight:bold; cursor:pointer; transition: 0.2s; flex: 1;">Ir al Login</button>
            </div>
        </div>
    </div>

    <script>
        document.fonts.ready.then(() => {
            document.body.classList.add('fonts-loaded');
        });

        let latitud, longitud, zoom;
        // positionError(); // Prueba españa
        navigator.geolocation.getCurrentPosition(showPosition, positionError, {
            enableHighAccuracy: false,
            timeout: 3000,             // <-- Si en 3 segundos no te encuentra, salta al error (España).
            maximumAge: 600000         // <-- Usa una ubicación que tenga hasta 10 minutos de antigüedad.
        });

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

        // Función para mostrar el Modal de Login
        function showLoginModal() {
            const modal = document.getElementById('loginModal');
            modal.style.display = 'flex';
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
                            
                            // SE HA CAMBIADO EL ENLACE DEL POPUP PARA ABRIR LA VENTANA FLOTANTE EN LUGAR DE REDIRIGIR
                            var popupContent = 
                                '<a href="javascript:void(0);" onclick="showLoginModal();" style="text-decoration:none;">' + 
                                    '<div class="colorTitleSitio text-center" style="font-size:1.1rem;">' + element.nombre + '</div>' + 
                                '</a>' + 
                                '<div class="text-center" style="color:#555; margin-top:3px;">' + element.direccion + ", " + element.localidad + '</div>';

                            var myPopup = L.popup({
                                offset: L.point(0, -20)
                            }).setContent(popupContent);
                            
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