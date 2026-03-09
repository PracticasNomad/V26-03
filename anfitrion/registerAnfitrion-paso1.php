<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crea tu perfil de anfitrión</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="icon" href="../favicon-color.png">
    <link rel="icon" href="../favicon-negro.png" media="(prefers-color-scheme: light)">
    <link rel="icon" href="../favicon-color.png" media="(prefers-color-scheme: dark)">
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
        }

        .registration-container {
            background-color: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            padding: 40px;
            max-width: 600px;
            width: 100%;
            text-align: center;
            margin: 35px auto;
        }

        .registration-logo {
            max-width: 150px;
            margin-bottom: 20px;
        }

        .registration-title {
            color: #333;
            font-weight: 700;
            margin-bottom: 15px;
        }

        .registration-subtitle {
            color: #6c757d;
            margin-bottom: 25px;
        }

        .registration-description {
            color: #6c757d;
            margin-bottom: 30px;
            font-weight: 600;
        }

        .btn-custom {
            padding: 12px 30px;
            margin: 0 10px;
            border-radius: 30px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-custom:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .login-link {
            color: #28a745;
            text-decoration: none;
            font-weight: 600;
        }

        .login-link:hover {
            text-decoration: underline;
        }

        .progress-step {
            color: #6c757d;
            margin-top: 20px;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="registration-container">
            <div class="row">
                <div class="col-12">
                    <img src="../img/antena.png" alt="Establecimiento" class="registration-logo">

                    <h2 class="registration-title">Crea tu perfil</h2>
                    <h3 class="registration-subtitle">Registra tu establecimiento en unos simples pasos</h3>

                    <p class="registration-description">
                        Para realizar un registro más cómodo y rápido deberías estar en el establecimiento que vas a registrar
                    </p>

                    <div class="mb-4">
                        <span>¿Ya tienes cuenta?</span>
                        <a href="inicio_sesion_anfitrion.php" class="login-link">Inicia Sesión</a>
                    </div>

                    <div>
                        <button class="btn btn-outline-secondary btn-custom" onclick="location.href='../index.php'">
                            Volver
                        </button>
                        <button class="btn btn-success btn-custom" onclick="location.href='registerAnfitrion-paso2.php'">
                            Siguiente
                        </button>
                    </div>

                    <div class="progress-step">
                        Paso 1 de 5
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>