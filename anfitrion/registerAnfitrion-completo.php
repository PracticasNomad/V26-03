<?php
session_start();

session_destroy();
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://kit.fontawesome.com/b8814a2854.js" crossorigin="anonymous"></script>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;600;700&display=swap" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="icon" href="../favicon-color.png">
    <link rel="icon" href="../favicon-negro.png" media="(prefers-color-scheme: light)">
    <link rel="icon" href="../favicon-color.png" media="(prefers-color-scheme: dark)">
    <title>Registro Completado</title>
    <style>
        body {
            font-family: 'Nunito', sans-serif;
            background-color: #f8f9fa;
        }

        .contenedorAlta {
            max-width: 800px;
            margin: 2rem auto;
            background-color: white;
            border-radius: 15px;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            padding: 1rem;
        }

        .btn-success {
            background-color: #28a745;
            border: none;
            font-weight: 600;
            padding: 0.75rem 2rem;
        }

        .logo-container {
            background-color: #f8f9fa;
            border-radius: 50%;
            width: 120px;
            height: 120px;
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 0 auto;
        }

        .celebration-text {
            font-size: 2.5rem;
            font-weight: 700;
            color: #28a745;
            margin-bottom: 0.5rem;
        }

        .info-text {
            font-size: 1.2rem;
            color: #6c757d;
            margin-bottom: 2rem;
        }
    </style>
</head>

<body>
    <div class="contenedorAlta">
        <div class="col-12 text-center py-3 fw-bold h4">
            Registro Completado
        </div>

        <div class="col-12 text-center mb-3">
            <div class="logo-container">
                <img src="../img/antena.png" width="80" alt="Registro Completado">
            </div>
        </div>

        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-8 text-center">
                    <div class="celebration-text">¡Ya esta!</div>
                    <p class="info-text">Inicia sesión para gestionar tus espacios y establecimientos</p>

                    <div class="mt-5">
                        <button onclick="location.href='inicio_sesion_anfitrion.php'" class="btn btn-success rounded-pill px-5 py-3">
                            <i class="fas fa-home me-2"></i>Volver al inicio
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>