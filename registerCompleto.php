<?php
session_start();

unset($_SESSION['nombre_guest']);
unset($_SESSION['email_guest']);
unset($_SESSION['telefono_guest']);
unset($_SESSION['password_guest']);
unset($_SESSION['verification_code_guest']);
unset($_SESSION['already_host']);
unset($_SESSION['host_id']);
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://kit.fontawesome.com/b8814a2854.js" crossorigin="anonymous"></script>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@200;300;400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="icon" href="favicon-color.png">

    <link rel="icon" href="favicon-negro.png" media="(prefers-color-scheme: light)">

    <link rel="icon" href="favicon-color.png" media="(prefers-color-scheme: dark)">
    <title>¡Registro completado!</title>
    <style>
        body {
            font-family: 'Nunito', sans-serif;
            background-color: #f4f6f9;
            min-height: 100vh;
        }

        .container {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        .success-container {
            background-color: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            padding: 40px;
            max-width: 500px;
            width: 100%;
            text-align: center;
            margin: 35px auto;
        }

        .success-logo {
            max-width: 200px;
            margin-bottom: 20px;
        }

        .success-title {
            color: #333;
            font-weight: 700;
            margin-bottom: 15px;
        }

        .success-message {
            color: #6c757d;
            margin-bottom: 30px;
            font-size: 1.1rem;
            line-height: 1.6;
        }

        .btn-custom {
            padding: 12px 40px;
            border-radius: 30px;
            font-weight: 600;
            transition: all 0.3s ease;
            font-size: 1.1rem;
        }

        .btn-custom:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .success-icon {
            font-size: 5rem;
            color: #28a745;
            margin-bottom: 20px;
        }

        .powered-by {
            color: #6c757d;
            margin-top: 30px;
            font-weight: 600;
        }

        .powered-by img {
            max-height: 30px;
            margin-left: 10px;
            margin-bottom: 20px;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="success-container">
            <img src="img/tenda.png" alt="Nómada" class="success-logo">

            <div class="success-icon">
                <i class="fas fa-check-circle"></i>
            </div>

            <h2 class="success-title">¡Registro completado con éxito!</h2>

            <div class="success-message">
                <p>¡Felicidades! Tu cuenta de nómada ha sido creada correctamente.</p>
                <p>Ahora podrás buscar y reservar espacios de trabajo adaptados a tus necesidades.</p>
                <p>Comienza tu experiencia nómada iniciando sesión con tus credenciales.</p>
            </div>

            <div class="d-flex justify-content-center mb-4">
                <a href="login.php" class="btn btn-success btn-custom">
                    <i class="fas fa-sign-in-alt me-2"></i>Iniciar sesión
                </a>
            </div>

            <div class="powered-by">
                Powered by <img src="img/smartable.png" alt="Smartable">
            </div>
        </div>
    </div>
</body>

</html>