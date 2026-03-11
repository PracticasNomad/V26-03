<?php
require_once 'verificar_sesion_gestor.php';
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Gracias por Mejorar - Nomadapp</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://kit.fontawesome.com/b8814a2854.js" crossorigin="anonymous"></script>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@200;400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta3/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="icon" href="../favicon-color.png">
    <style>
        body {
            min-height: 100vh;
            font-family: 'Nunito', sans-serif;
            background-color: #f8f9fa;
            color: #333;
        }

        .header-container {
            position: relative;
            width: 100%;
            height: 180px;
            overflow: hidden;
        }

        .header-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            filter: brightness(0.7);
        }

        .header-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(rgba(0, 183, 207, 0.7), rgba(0, 183, 207, 0.9));
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            color: white;
            padding: 1rem;
        }

        .card {
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            border: none;
        }

        .card-header {
            background-color: #00B7CF;
            color: white;
            font-weight: 600;
        }

        .section-title {
            color: #00B7CF;
            font-weight: 700;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #BDE742;
        }

        .btn-nomad {
            background-color: #00B7CF;
            color: white;
            border: none;
            border-radius: 50px;
            padding: 0.5rem 1.5rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-nomad:hover {
            background-color: #4CCBD4;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            color: white;
        }

        .custom-list {
            list-style: none;
            padding-left: 0;
        }

        .custom-list li {
            margin-bottom: 0.5rem;
            font-size: 1.05rem;
        }
    </style>
</head>

<body>
    <div class="container p-0">
        <div class="header-container">
            <img class="header-img" src="https://cdn.pixabay.com/photo/2016/11/18/14/05/brick-wall-1834784_960_720.jpg"
                alt="Header">
            <div class="header-overlay">
                <div class="text-center">
                    <h1 class="fw-bold mb-2">¡Gracias por unirte al plan Premium Gestor!</h1>
                    <h5 class="fw-normal">Tu suscripción ha sido activada correctamente</h5>
                </div>
            </div>
        </div>
    </div>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card p-4">
                    <h4 class="section-title"><i class="fas fa-info-circle me-2"></i>Nuevos Límites Disponibles</h4>
                    <p class="mb-2">A partir de ahora dispones de:</p>

                    <ul class="custom-list mb-4">
                        <li><i class="fas fa-check text-success me-2"></i> <strong>Anfitriones Ilimitados</strong> en tu
                            cartera.</li>
                        <li><i class="fas fa-check text-success me-2"></i> <strong>Más de 50 establecimientos</strong>
                            gestionados.</li>
                        <li><i class="fas fa-check text-success me-2"></i> Ideal para grandes gestoras.</li>
                    </ul>

                    <p class="text-muted">Podrás disfrutar de tu suscripción hasta el
                        <strong><?= isset($_SESSION['fecha_fin']) ? date('d/m/Y', strtotime($_SESSION['fecha_fin'])) : 'próximo ciclo' ?></strong>.
                    </p>

                    <div class="text-center mt-4">
                        <a href="tuPerfil.php" class="btn btn-nomad">
                            <i class="fas fa-user-circle me-2"></i>Ir a mi panel de control
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>
<?php unset($_SESSION['fecha_fin']); ?>