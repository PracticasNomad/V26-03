<?php
require_once 'verificar_sesion_guest.php';

// Cargar variables de entorno para usar MINIO_URL de forma segura
require './vendor/autoload.php';
use Dotenv\Dotenv;
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$_SESSION['anfitrion_id'] = $_GET['id'];
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://kit.fontawesome.com/b8814a2854.js" crossorigin="anonymous"></script>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <script src='https://api.mapbox.com/mapbox.js/v3.3.1/mapbox.js'></script>
    <link href='https://api.mapbox.com/mapbox.js/v3.3.1/mapbox.css' rel='stylesheet' />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta3/dist/js/bootstrap.bundle.min.js"></script>
    
    <link rel="icon" href="favicon-color.png">
    <link rel="icon" href="favicon-negro.png" media="(prefers-color-scheme: light)">
    <link rel="icon" href="favicon-color.png" media="(prefers-color-scheme: dark)">
    
    <title>TheNomadApp - <?php echo isset($_GET['nombre']) ? htmlspecialchars($_GET['nombre']) : 'Detalles del Anfitrión'; ?></title>

    <script>
        const MINIO_URL = "<?php echo rtrim($_ENV['MINIO_PUBLIC_URL'] ?? 'http://127.0.0.1:9000', '/'); ?>";
    </script>

    <style>
        :root {
            --primary: #00B7CF;
            --primary-hover: #0099ad;
            --accent: #BDE742;
            --accent-hover: #a6d128;
            --bg-color: #f4f6f9;
            --text-main: #2c3e50;
            --text-muted: #6c757d;
        }

        body {
            font-family: 'Nunito', sans-serif;
            background-color: var(--bg-color);
            color: var(--text-main);
            padding-bottom: 50px;
        }

        /* Hero Header */
        .hero-header {
            background: linear-gradient(135deg, var(--primary) 0%, #007c8c 100%);
            color: white;
            padding: 40px 20px;
            margin-bottom: 30px;
            box-shadow: 0 10px 20px rgba(0, 183, 207, 0.2);
        }

        .hero-title { font-weight: 800; font-size: 2.5rem; margin-bottom: 10px; }
        .hero-subtitle { font-size: 1.1rem; opacity: 0.9; font-weight: 400; }

        /* Cards & Containers */
        .modern-card {
            background: white;
            border-radius: 20px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            margin-bottom: 25px;
        }

        /* Carousel */
        .carousel-inner {
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .carousel-item img {
            height: 450px;
            object-fit: cover; /* Recorta la imagen para llenar el contenedor bonito */
            width: 100%;
            background-color: #e9ecef;
        }
        .carousel-indicators [data-bs-target] {
            width: 10px; height: 10px; border-radius: 50%; background-color: var(--primary);
        }

        /* Services & About */
        .service-badge {
            display: inline-flex; align-items: center; gap: 8px;
            background: #e0f8fb; color: var(--primary);
            padding: 10px 15px; border-radius: 50px; font-weight: 700; margin: 5px;
        }
        .service-badge.parking { background: #f2f9e8; color: #81ba18; }
        
        .about-text { font-size: 1.05rem; line-height: 1.6; color: var(--text-main); }

        /* Map */
        #map {
            width: 100%; height: 350px; border-radius: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05); border: 2px solid white;
        }

        /* Spaces Expanders */
        .expander-container {
            background: white; border: 1px solid #e9ecef; border-radius: 15px;
            margin-bottom: 15px; overflow: hidden; transition: all 0.3s ease;
        }
        .expander-container:hover { border-color: var(--primary); box-shadow: 0 5px 15px rgba(0,183,207,0.1); }
        
        .expander-header {
            padding: 18px 20px; background: white; cursor: pointer;
            display: flex; justify-content: space-between; align-items: center;
        }
        .expander-header .title { font-weight: 700; font-size: 1.1rem; color: var(--text-main); }
        .expander-header i { color: var(--primary); transition: transform 0.3s; }
        .expander-header.active i { transform: rotate(180deg); }
        
        .expander-content { padding: 0 20px 20px 20px; display: none; }
        .schedule-badge {
            background: #f8f9fa; border: 1px solid #e9ecef; padding: 8px 15px;
            border-radius: 10px; font-weight: 700; color: var(--text-main); display: inline-block;
        }
        
        .day-indicator {
            width: 35px; height: 35px; display: flex; align-items: center; justify-content: center;
            border-radius: 50%; font-weight: 700; font-size: 0.9rem; margin: 0 3px;
        }
        .day-active { background-color: var(--accent); color: #2c3e50; box-shadow: 0 2px 5px rgba(189, 231, 66, 0.4); }
        .day-inactive { background-color: #f1f3f5; color: #adb5bd; }

        /* Buttons */
        .btn-reserve {
            background-color: var(--accent); color: #2c3e50; font-weight: 800;
            border-radius: 50px; padding: 12px 30px; border: none; transition: all 0.3s;
            text-transform: uppercase; letter-spacing: 1px;
        }
        .btn-reserve:hover { background-color: var(--accent-hover); transform: translateY(-2px); box-shadow: 0 5px 15px rgba(189, 231, 66, 0.4); }
        
        .btn-back {
            background-color: white; color: var(--text-muted); font-weight: 700;
            border-radius: 50px; padding: 12px 30px; border: 2px solid #e9ecef; transition: all 0.3s;
        }
        .btn-back:hover { background-color: #e9ecef; color: var(--text-main); }
    </style>
</head>

<body>

    <div class="hero-header text-center">
        <h1 class="hero-title">
            <?php echo isset($_GET['nombre']) ? htmlspecialchars($_GET['nombre']) : 'Nombre del Establecimiento'; ?>
        </h1>
        <div class="hero-subtitle">
            <i class="fas fa-map-marker-alt me-2 text-warning"></i>
            <?php 
                $calle = isset($_GET['calle']) ? $_GET['calle'] : (isset($_GET['direccion']) ? $_GET['direccion'] : '');
                $piso = isset($_GET['piso']) && !empty($_GET['piso']) ? ", Piso: " . $_GET['piso'] : "";
                $ciudad = isset($_GET['ciudad']) ? $_GET['ciudad'] : '';
                $provincia = isset($_GET['provincia']) ? $_GET['provincia'] : '';
                
                echo htmlspecialchars($calle . $piso . " - " . $ciudad . ", " . $provincia);
            ?>
        </div>
    </div>

    <div class="container-fluid" style="max-width: 1300px;">
<div class="row g-4">
            
            <div class="col-lg-7">
                
                <div id="dynamic_carousel" class="carousel slide modern-card p-0" data-bs-ride="carousel">
                    <div class="carousel-indicators" id="carousel-indicators-container">
                    </div>
                    <div class="carousel-inner" id="carousel-inner-container">
                        <div class="text-center p-5 text-muted" id="carousel-loading">
                            <i class="fas fa-spinner fa-spin fa-2x mb-3"></i><br>Cargando imágenes...
                        </div>
                    </div>
                    <button class="carousel-control-prev" type="button" data-bs-target="#dynamic_carousel" data-bs-slide="prev">
                        <span class="carousel-control-prev-icon" aria-hidden="true" style="background-color: rgba(0,0,0,0.5); border-radius: 50%; padding: 20px;"></span>
                    </button>
                    <button class="carousel-control-next" type="button" data-bs-target="#dynamic_carousel" data-bs-slide="next">
                        <span class="carousel-control-next-icon" aria-hidden="true" style="background-color: rgba(0,0,0,0.5); border-radius: 50%; padding: 20px;"></span>
                    </button>
                </div>

                <div class="modern-card">
                    <h4 class="fw-bold mb-3" style="color: var(--primary);"><i class="fas fa-info-circle me-2"></i>Sobre Nosotros</h4>
                    <div id="about_us" class="about-text mb-4">Cargando descripción...</div>

                    <h5 class="fw-bold mb-3" style="color: var(--text-main);"><i class="fas fa-star me-2 text-warning"></i>Servicios Extras</h5>
                    <div id="servicios_container" class="d-flex flex-wrap">
                    </div>
                </div>

                <div class="modern-card mt-4">
                    <h4 class="fw-bold mb-3" style="color: var(--primary);"><i class="fas fa-comments me-2"></i>Valoraciones</h4>
                    
                    <div class="d-flex align-items-center mb-4">
                        <h2 class="fw-bold mb-0 me-3" id="media-estrellas" style="font-size: 2.5rem; color: var(--text-main);">-.-</h2>
                        <div>
                            <div class="text-warning fs-5" id="estrellas-container">
                                <i class="far fa-star"></i><i class="far fa-star"></i><i class="far fa-star"></i><i class="far fa-star"></i><i class="far fa-star"></i>
                            </div>
                            <span class="text-muted small" id="total-valoraciones">0 opiniones</span>
                        </div>
                    </div>

                    <div id="lista-comentarios" style="max-height: 400px; overflow-y: auto; padding-right: 5px;">
                        <div class="text-center text-muted py-3">
                            <i class="fas fa-spinner fa-spin mb-2"></i><br>Cargando opiniones...
                        </div>
                    </div>
                </div>

            </div> <div class="col-lg-5">
                
                <div class="modern-card p-2 mb-4">
                    <div id="map"></div>
                </div>

                <div class="modern-card">
                    <h4 class="fw-bold mb-4 text-center" style="color: var(--primary);">
                        <i class="far fa-calendar-check me-2"></i>Espacios Disponibles
                    </h4>
                    <div id="espacios_disponibles">
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-spinner fa-spin fa-2x mb-2"></i><br>Buscando espacios...
                        </div>
                    </div>
                </div>

                <div class="text-center mt-4">
                    <button class="btn-back" id="salir" onclick="location.href = new URLSearchParams(window.location.search).get('fromIndex') === 'true' ? 'index.php' : 'nomada_explorar.php'">
                        <i class="fas fa-arrow-left me-2"></i> Volver a Explorar
                    </button>
                </div>

            </div> </div>
    </div>

    <script>
        // Variables de Mapa
        var ciudaBuscadaY = <?php echo isset($_GET['coordinates1']) ? (float)$_GET['coordinates1'] : '40.4168'; ?>;
        var ciudaBuscadaX = <?php echo isset($_GET['coordinates0']) ? (float)$_GET['coordinates0'] : '-3.7038'; ?>;
        var nombreLugar = "<?php echo isset($_GET['nombre']) ? htmlspecialchars(addslashes($_GET['nombre'])) : ''; ?>";
        
        // Inicializar Mapa
        L.mapbox.accessToken = 'pk.eyJ1IjoiYW5kcnplamJhbmFzIiwiYSI6ImNrcHdrZXIyYTAyZWkyb3AwNGtpbmtrbXYifQ.PN_iZ4Mh08-V5EXHAHpCSg';
        var map = L.mapbox.map('map')
            .setView([ciudaBuscadaY, ciudaBuscadaX], 15)
            .addLayer(L.mapbox.styleLayer('mapbox://styles/mapbox/streets-v11'));

        var myIcon = L.icon({
            iconUrl: 'img/posicionAnfitrion.png',
            iconSize: [35, 35],
            iconAnchor: [17, 35],
        });

        L.marker([ciudaBuscadaY, ciudaBuscadaX], { icon: myIcon })
            .addTo(map)
            .bindPopup(`<div class="fw-bold text-center">${nombreLugar}</div>`)
            .openPopup();

        // 1. Cargar Datos del Anfitrión (Imágenes, Descripción, Servicios)
        fetch("getAnfitrionById.php")
            .then(response => response.json())
            .then(data => { applyAnfitrionData(data); })
            .catch(err => console.error("Error cargando anfitrión:", err));

        // 2. Cargar Espacios Disponibles
        fetch("nomadasLerrApi.php")
            .then(response => response.json())
            .then(data => { buildEspaciosCards(data); })
            .catch(err => {
                document.getElementById("espacios_disponibles").innerHTML = `
                    <div class="alert alert-warning text-center">
                        <i class="fas fa-lock mb-2 fa-2x"></i><br>
                        <a href="login.php" class="fw-bold text-dark">Inicia Sesión</a> para ver y reservar espacios.
                    </div>`;
            });

        // Aplicar los datos básicos y la galería
        function applyAnfitrionData(data) {
            document.getElementById("about_us").innerHTML = data.descripcion || "Este anfitrión no ha proporcionado una descripción todavía.";

            // Servicios
            const serviciosContainer = document.getElementById("servicios_container");
            serviciosContainer.innerHTML = ''; // limpiar

            if (data.has_wifi) {
                serviciosContainer.innerHTML += `<div class="service-badge"><i class="fas fa-wifi"></i> WiFi Disponible</div>`;
            }
            if (data.has_food) {
                serviciosContainer.innerHTML += `<div class="service-badge"><i class="fas fa-utensils"></i> Restauración</div>`;
            }
            if (data.has_parking) {
                const precio = data.parking_price > 0 ? `(${data.parking_price}€)` : "(Gratis)";
                serviciosContainer.innerHTML += `<div class="service-badge parking"><i class="fas fa-car"></i> Parking ${precio}</div>`;
            }
            if (serviciosContainer.innerHTML === '') {
                serviciosContainer.innerHTML = `<span class="text-muted">No hay servicios extra especificados.</span>`;
            }

            // Carrusel Dinámico con limpieza de URL
            buildDynamicCarousel(data.gallery);
        }

        function buildDynamicCarousel(gallery) {
            const indicators = document.getElementById('carousel-indicators-container');
            const inner = document.getElementById('carousel-inner-container');
            
            indicators.innerHTML = '';
            inner.innerHTML = '';

            if (!gallery || gallery.length === 0) {
                inner.innerHTML = `
                    <div class="carousel-item active">
                        <img src="img/bricks0.jpg" class="d-block w-100" alt="Sin imagen">
                    </div>`;
                return;
            }

            gallery.forEach((imgObj, index) => {
                // 1. MAGIA: Limpiar la URL usando MINIO_URL del .env
                let cleanUrl = imgObj.image_url;
                if (cleanUrl && cleanUrl !== "./img/noimagen.jpg") {
                    try {
                        let tempUrl = cleanUrl.startsWith('http') ? cleanUrl : 'http://' + cleanUrl;
                        let urlObj = new URL(tempUrl);
                        cleanUrl = MINIO_URL + urlObj.pathname;
                    } catch(e) {
                        cleanUrl = "https://" + cleanUrl.replace(/^(https?:\/\/)+/, ""); // fallback rudo si falla
                    }
                } else {
                    cleanUrl = "img/bricks0.jpg";
                }

                // 2. Crear Indicador
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.setAttribute('data-bs-target', '#dynamic_carousel');
                btn.setAttribute('data-bs-slide-to', index);
                if (index === 0) {
                    btn.classList.add('active');
                    btn.setAttribute('aria-current', 'true');
                }
                indicators.appendChild(btn);

                // 3. Crear Slide
                const item = document.createElement('div');
                item.className = `carousel-item ${index === 0 ? 'active' : ''}`;
                item.innerHTML = `<img src="${cleanUrl}" alt="Foto del local ${index + 1}">`;
                inner.appendChild(item);
            });
        }

        function buildEspaciosCards(data) {
            const container = document.getElementById("espacios_disponibles");
            container.innerHTML = '';

            if (!data || data.length === 0) {
                container.innerHTML = `<div class="text-center text-muted">No hay espacios disponibles actualmente.</div>`;
                return;
            }

            const weekDays = [
                { key: "has_monday", label: "L" }, { key: "has_tuesday", label: "M" },
                { key: "has_wednesday", label: "X" }, { key: "has_thursday", label: "J" },
                { key: "has_friday", label: "V" }, { key: "has_saturday", label: "S" },
                { key: "has_sunday", label: "D" }
            ];

            data.forEach((item, index) => {
                const expander = document.createElement('div');
                expander.className = 'expander-container';
                
                // Cabecera Acordeón
                const header = document.createElement('div');
                header.className = 'expander-header';
                header.innerHTML = `
                    <div class="title"><i class="fas fa-chair me-2 text-primary"></i>${item.name}</div>
                    <i class="fas fa-chevron-down"></i>
                `;
                
                // Contenido Acordeón
                const content = document.createElement('div');
                content.className = 'expander-content';
                
                let descHTML = `<div class="text-muted mb-3 mt-2">${item.description}</div>`;
                let schedulesHTML = '';

                if (item.schedule && item.schedule.length > 0) {
                    item.schedule.forEach(sch => {
                        let daysHTML = '';
                        weekDays.forEach(day => {
                            const activeClass = sch[day.key] ? 'day-active' : 'day-inactive';
                            daysHTML += `<div class="day-indicator ${activeClass}">${day.label}</div>`;
                        });

                        schedulesHTML += `
                            <div class="mb-4">
                                <div class="d-flex justify-content-center mb-2">${daysHTML}</div>
                                <div class="text-center">
                                    <span class="schedule-badge">
                                        <i class="far fa-clock me-2 text-primary"></i>
                                        ${sch.start_time.substring(0, 5)} - ${sch.end_time.substring(0, 5)}
                                    </span>
                                </div>
                            </div>
                        `;
                    });
                }

                content.innerHTML = `
                    ${descHTML}
                    <div class="border-top pt-3 mt-2">
                        <h6 class="fw-bold text-center mb-3">Horario Habilitado</h6>
                        ${schedulesHTML}
                    </div>
                    <div class="text-center mt-4">
                        <button class="btn-reserve" onclick="location.href='reservarEspacio.php?id=${item.id}'">
                            <i class="fas fa-bolt me-2"></i>Reservar Espacio
                        </button>
                    </div>
                `;

                expander.appendChild(header);
                expander.appendChild(content);
                container.appendChild(expander);

                // Lógica de Despliegue
                header.addEventListener('click', () => {
                    const isVisible = content.style.display === 'block';
                    // Opcional: Cerrar los demás
                    document.querySelectorAll('.expander-content').forEach(c => c.style.display = 'none');
                    document.querySelectorAll('.expander-header').forEach(h => h.classList.remove('active'));
                    
                    if (!isVisible) {
                        content.style.display = 'block';
                        header.classList.add('active');
                    }
                });
            });
        }

        // ======= INICIO: LÓGICA DE VALORACIONES =======
        const estId = new URLSearchParams(window.location.search).get('id');

        function cargarValoraciones() {
            fetch(`obtener_valoraciones.php?id_establecimiento=${estId}`)
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('media-estrellas').textContent = data.media > 0 ? parseFloat(data.media).toFixed(1) : '-.-';
                        document.getElementById('total-valoraciones').textContent = `${data.total} opiniones`;
                        
                        let estrellasHTML = '';
                        let notaRedondeada = Math.round(data.media);
                        for(let i = 1; i <= 5; i++) {
                            estrellasHTML += i <= notaRedondeada ? '<i class="fas fa-star"></i>' : '<i class="far fa-star"></i>';
                        }
                        document.getElementById('estrellas-container').innerHTML = estrellasHTML;

                        const lista = document.getElementById('lista-comentarios');
                        lista.innerHTML = '';
                        
                        if (data.total === 0) {
                            lista.innerHTML = '<div class="text-center text-muted py-4"><i class="far fa-comment-dots fs-1 mb-2"></i><br>Aún no hay opiniones. ¡Sé el primero!</div>';
                        } else {
                            data.valoraciones.forEach(val => {
                                let starsUser = '';
                                for(let i = 1; i <= 5; i++) {
                                    starsUser += i <= val.valoracion ? '<i class="fas fa-star text-warning"></i>' : '<i class="far fa-star text-warning"></i>';
                                }
                                let fecha = new Date(val.created_at).toLocaleDateString('es-ES', {year: 'numeric', month: 'short', day: 'numeric'});
                                
                                lista.innerHTML += `
                                    <div class="border-bottom py-3">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <strong style="color: var(--text-main); font-size: 0.95rem;">
                                                <i class="fas fa-user-circle me-1 text-secondary"></i> Nómada
                                            </strong>
                                            <small class="text-muted" style="font-size: 0.8rem;">${fecha}</small>
                                        </div>
                                        <div class="mb-2" style="font-size: 0.8rem;">${starsUser}</div>
                                        <p class="mb-0 text-muted" style="font-size: 0.95rem;">${val.comentario}</p>
                                    </div>
                                `;
                            });
                        }
                    }
                })
                .catch(err => console.error("Error al cargar valoraciones", err));
        }
        cargarValoraciones();
        // ======= FIN: LÓGICA DE VALORACIONES =======
    </script>
</body>
</html>