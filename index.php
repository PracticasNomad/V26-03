<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@500;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

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

        @media (max-width: 768px) {
            .lock-icon {
                top: 8px;
                right: 8px;
                font-size: 18px;
                padding: 6px;
            }
        }

        @keyframes fadeInModal {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .btn-modal-hover:hover {
            filter: brightness(0.9);
            transform: translateY(-1px);
        }

        /* ESTILOS DE LA CARD DEL MAPA */
        .custom-leaflet-popup .leaflet-popup-content-wrapper {
            padding: 0;
            overflow: hidden;
            border-radius: 16px;
            box-shadow: 0 12px 28px rgba(0,0,0,0.25);
        }
        .custom-leaflet-popup .leaflet-popup-content {
            margin: 0;
            width: 100% !important;
        }
        .custom-leaflet-popup .leaflet-popup-close-button {
            color: white !important;
            text-shadow: 0 1px 4px rgba(0,0,0,0.8);
            top: 8px !important;
            right: 8px !important;
            z-index: 10;
        }
        .custom-popup-card {
            display: flex;
            flex-direction: column;
            font-family: 'Nunito', sans-serif;
            background: #fff;
        }
        .popup-image {
            height: 140px;
            background-color: #e9ecef;
            background-image: url('https://images.unsplash.com/photo-1497366216548-37526070297c?auto=format&fit=crop&q=80&w=400');
            background-size: cover;
            background-position: center;
            position: relative;
        }
        .popup-image::after {
            content: '';
            position: absolute;
            bottom: 0; left: 0; right: 0;
            height: 50%;
            background: linear-gradient(to top, rgba(0,0,0,0.6), transparent);
        }
        .popup-details {
            padding: 20px;
            text-align: left;
        }
        .popup-title {
            font-weight: 800;
            font-size: 1.25rem;
            color: #333;
            margin: 0 0 5px 0;
            line-height: 1.2;
        }
        .popup-address {
            font-size: 0.85rem;
            color: #666;
            margin-bottom: 12px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .popup-badges {
            display: flex;
            gap: 6px;
            margin-bottom: 15px;
            flex-wrap: wrap;
        }
        .popup-badge {
            font-size: 0.70rem;
            padding: 4px 10px;
            border-radius: 20px;
            font-weight: 800;
            text-transform: uppercase;
        }
        .badge-wifi { background: #e6f2ff; color: #007bff; }
        .badge-parking { background: #e7f8ee; color: #146c43; }
        .badge-food { background: #fff3cd; color: #d39e00; }
        .badge-bed { background: #cff4fc; color: #087990; }
        .badge-default { background: #f8f9fa; color: #6c757d; border: 1px solid #dee2e6;}
        
        .popup-desc {
            font-size: 0.9rem;
            color: #555;
            margin-bottom: 20px;
            line-height: 1.5;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .btn-popup-reserve {
            width: 100%;
            background: #81ba18;
            color: white;
            border: none;
            padding: 12px;
            border-radius: 25px;
            font-weight: 800;
            font-size: 0.95rem;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 8px;
        }
        .btn-popup-reserve:hover {
            background: #6a9c12;
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(129, 186, 24, 0.3);
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
        
        <div id="map" class="map derecha flex"></div>
        
        <div class="botonesMovil">
            <button class="btn-index btn-primary fw-bold" type="button" onclick="location.href='anfitrion/inicio_sesion_anfitrion.php'">Tengo un espacio</button>
            <button class="btn-index btn-success fw-bold" type="button" onclick="location.href='login.php'">Busco un espacio</button>
        </div>
    </div>

    <div id="loginModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.6); z-index:9999; flex-direction:column; justify-content:center; align-items:center; backdrop-filter: blur(3px);">
        <div style="background:white; padding:35px 25px; border-radius:20px; text-align:center; max-width:400px; width:90%; box-shadow:0 15px 30px rgba(0,0,0,0.3); animation: fadeInModal 0.3s ease;">
            
            <h3 id="modalEstablecimientoNombre" style="font-family:'Nunito', sans-serif; font-weight:800; color:#333; margin-bottom: 5px; font-size:1.3rem;">Reserva tu espacio</h3>
            <p style="font-family:'Nunito', sans-serif; color:#888; font-size:0.85rem; margin-bottom: 20px;">Estadísticas de la comunidad Nómada:</p>
            
            <div id="modalStatsContainer"></div>

            <p style="font-family:'Nunito', sans-serif; color:#444; margin-bottom:25px; line-height: 1.5; font-size:0.95rem;">Para acceder a todas las fotos, horarios y poder reservar, inicia sesión.</p>
            
            <div style="display:flex; gap:15px; justify-content:center;">
                <button class="btn-modal-hover" onclick="document.getElementById('loginModal').style.display='none'" style="padding:10px 20px; border-radius:25px; border:none; background:#e9ecef; color:#333; font-weight:bold; cursor:pointer; transition: 0.2s; flex: 1;">Cancelar</button>
                <button class="btn-modal-hover" onclick="location.href='login.php'" style="padding:10px 20px; border-radius:25px; border:none; background:#81ba18; color:white; font-weight:bold; cursor:pointer; transition: 0.2s; flex: 1;">Identificarse</button>
            </div>
        </div>
    </div>

    <script>
        document.fonts.ready.then(() => {
            document.body.classList.add('fonts-loaded');
        });

        let latitud, longitud, zoom;
        navigator.geolocation.getCurrentPosition(showPosition, positionError, {
            enableHighAccuracy: false,
            timeout: 3000,
            maximumAge: 600000
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

        // NUEVA FUNCIÓN DEL MODAL: Calcula la prueba social y la pinta
        function showLoginModal(nombre, totalReservas, canceladas) {
            document.getElementById('modalEstablecimientoNombre').innerText = nombre;

            let statsHtml = '';
            
            if (totalReservas > 0) {
                // Calcular % de satisfacción (Éxito = Reservas que NO se cancelaron)
                let exitosas = totalReservas - canceladas;
                let porcentaje = Math.round((exitosas / totalReservas) * 100);

                statsHtml = `
                    <div style="background:#f8f9fa; border-radius:15px; padding:15px; margin-bottom:20px; display:flex; justify-content:space-around; align-items:center; border: 1px solid #eee;">
                        <div style="text-align:center;">
                            <span style="display:block; font-size:1.6rem; font-weight:900; color:#333;">${totalReservas}</span>
                            <span style="font-size:0.7rem; color:#666; text-transform:uppercase; font-weight:bold;">Nómadas<br>Lo han visitado</span>
                        </div>
                        <div style="width:2px; height:40px; background:#e9ecef;"></div>
                        <div style="text-align:center;">
                            <span style="display:block; font-size:1.6rem; font-weight:900; color:#81ba18;">${porcentaje}%</span>
                            <span style="font-size:0.7rem; color:#666; text-transform:uppercase; font-weight:bold;">Nivel de<br>Satisfacción</span>
                        </div>
                    </div>
                `;
            } else {
                // Si no hay reservas, mensaje animando a ser el primero
                statsHtml = `
                    <div style="background:#f0f9f2; border-radius:10px; padding:12px; margin-bottom:20px; border: 1px dashed #81ba18;">
                        <span style="color:#6a9c12; font-weight:bold; font-size: 0.95rem;"><i class="fas fa-star text-warning"></i> ¡Sé el primero en visitarlo!</span>
                    </div>
                `;
            }

            document.getElementById('modalStatsContainer').innerHTML = statsHtml;
            document.getElementById('loginModal').style.display = 'flex';
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
                            
                            let nombreEst = element.nombre || element.name || 'Espacio Nómada';
                            let direccionEst = element.direccion || '';
                            let localidadEst = element.localidad || element.city || element.ciudad || '';
                            let descEst = element.descripcion || 'Un espacio perfectamente acondicionado para que puedas trabajar, concentrarte y conectar.';
                            
                            // Lectura de los 4 servicios booleanos
                            let hasWifi = element.has_wifi == 1 || element.has_wifi === true;
                            let hasParking = element.has_parking == 1 || element.has_parking === true;
                            let hasFood = element.has_food == 1 || element.has_food === true;
                            let hasAccommodation = element.has_accommodation == 1 || element.has_accommodation === true;

                            let badgesHtml = '';
                            if (hasWifi) badgesHtml += '<span class="popup-badge badge-wifi"><i class="fas fa-wifi"></i> Alta Velocidad</span>';
                            if (hasParking) badgesHtml += '<span class="popup-badge badge-parking"><i class="fas fa-parking"></i> Parking</span>';
                            if (hasFood) badgesHtml += '<span class="popup-badge badge-food"><i class="fas fa-utensils"></i> Comida</span>';
                            if (hasAccommodation) badgesHtml += '<span class="popup-badge badge-bed"><i class="fas fa-bed"></i> Alojamiento</span>';
                            
                            // Fallback de seguridad si no tiene absolutamente NADA
                            if (!hasWifi && !hasParking && !hasFood && !hasAccommodation) {
                                badgesHtml += '<span class="popup-badge badge-default"><i class="fas fa-laptop-house"></i> Espacio de Trabajo</span>';
                            }

                            // Evitar errores de comillas simples en el nombre del establecimiento al pasarlo a la función JS
                            let safeNombre = nombreEst.replace(/'/g, "\\'");

                            var popupContent = `
                                <div class="custom-popup-card">
                                    <div class="popup-image"></div>
                                    <div class="popup-details">
                                        <h4 class="popup-title">${nombreEst}</h4>
                                        <p class="popup-address">
                                            <i class="fas fa-map-marker-alt" style="color:#00B7CF; margin-right:4px;"></i> 
                                            ${direccionEst}, ${localidadEst}
                                        </p>
                                        <div class="popup-badges">
                                            ${badgesHtml}
                                        </div>
                                        <p class="popup-desc">${descEst}</p>
                                        <button onclick="showLoginModal('${safeNombre}', ${element.total_reservas || 0}, ${element.canceladas || 0})" class="btn-popup-reserve">
                                            <i class="far fa-calendar-check"></i> Ver detalles y reservar
                                        </button>
                                    </div>
                                </div>
                            `;

                            var myPopup = L.popup({
                                offset: L.point(0, -30),
                                minWidth: 280,
                                maxWidth: 320,
                                className: 'custom-leaflet-popup'
                            }).setContent(popupContent);
                            
                            L.marker([element.latitude, element.longitude]).addTo(map).setIcon(myIcon).bindPopup(myPopup);
                        }
                    })
                })
                .catch(err => console.log(err));
        }

        document.querySelector('.lock-icon').addEventListener('click', function() {
            window.location.href = 'gestor/inicio_sesion_gestor.php';
        });
    </script>
</body>

</html>