<?php
session_start();

$formSuccess = '';

// Si el usuario elige un plan, lo guardamos en sesión y preparamos la redirección
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['plan'])) {
    $_SESSION['plan_seleccionado'] = $_POST['plan'];
    $formSuccess = "Plan seleccionado y datos guardados correctamente. Redirigiendo...";

    // Aquí hacemos la redirección con JavaScript para que dé tiempo a ver el mensaje
    echo "<script>
        setTimeout(function() {
            window.location.href = 'registerAnfitrion-pasoVerificar.php';
        }, 1500);
    </script>";
}
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
    <link rel="icon" href="../favicon-color.png">
    <title>Elige tu Plan - Paso 6</title>
    <style>
        body {
            font-family: 'Nunito', sans-serif;
            background-color: #f8f9fa;
        }

        /* Estilos del cuadro verde (Hero Section) */
        .hero-section {
            text-align: center;
            margin-bottom: 2rem;
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            padding: 2.5rem 2rem;
            border-radius: 20px;
            box-shadow: 0 0.5rem 2rem rgba(40, 167, 69, 0.3);
        }

        .hero-section h1 {
            font-weight: 700;
            font-size: 2.2rem;
            margin-bottom: 1rem;
        }

        .hero-section p {
            font-size: 1.1rem;
            opacity: 0.9;
            max-width: 600px;
            margin: 0 auto;
        }

        @media (max-width: 768px) {
            .hero-section h1 { font-size: 1.8rem; }
            .hero-section p { font-size: 1rem; }
        }

        .contenedorAlta {
            max-width: 1000px;
            margin: 2rem auto;
            background-color: white;
            border-radius: 15px;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            padding: 2rem;
        }

        .progress-container {
            width: 100%;
            height: 5px;
            background-color: #e9ecef;
            border-radius: 3px;
            overflow: hidden;
            margin: 1rem 0;
        }

        .progress-bar {
            height: 100%;
            width: 100%;
            background-color: #28a745;
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

        /* Estilos de las tarjetas de suscripción adaptados */
        .plans-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
        }

        .plan-card {
            border-radius: 20px;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.08);
            padding: 2rem;
            position: relative;
            transition: all 0.3s ease;
            border: 2px solid #e9ecef;
            text-align: center;
        }

       /* Hover general para las tarjetas normales (Básico y Premium) */
        .plan-card:hover { 
            transform: translateY(-10px); 
            box-shadow: 0 1rem 3rem rgba(0, 0, 0, 0.15); 
        }

        /* Estado normal de la tarjeta Pro (centro) */
        .plan-card.popular { 
            border-color: #28a745; 
            transform: scale(1.05); 
            z-index: 1; /* Para que sobresalga por encima de las otras */
        }

        /* Hover específico para la tarjeta Pro (centro) */
        .plan-card.popular:hover { 
            transform: scale(1.08) translateY(-10px); 
            box-shadow: 0 1.5rem 3rem rgba(40, 167, 69, 0.25); 
        }

        /* Estilos de precio y periodo */
        .plan-price .currency { font-size: 1.2rem; vertical-align: super; }
        .plan-period { color: #6c757d; font-size: 0.9rem; margin-bottom: 1rem; }
        
        /* Caja de precio anual (ahorro) */
        .plan-annual {
            background-color: #f0f9f2;
            border: 1px solid #28a745;
            border-radius: 10px;
            padding: 0.5rem;
            font-size: 0.85rem;
            color: #28a745;
            font-weight: 600;
            margin-bottom: 1.5rem;
        }

        /* Texto verde para características destacadas */
        .plan-features .highlight {
            font-weight: 600;
            color: #28a745;
        }

        /* Etiqueta superior MÁS POPULAR */
        .plan-card.popular::before { 
            content: "MÁS POPULAR"; 
            position: absolute; 
            top: -20px; 
            left: 50%; 
            transform: translateX(-50%); 
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%); 
            color: white; 
            padding: 0.3rem 1.5rem; 
            border-radius: 25px; 
            font-size: 0.8rem; 
            font-weight: 700; 
            letter-spacing: 1px;
        }
        
        .plan-name {
            font-size: 1.5rem;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 0.5rem;
            text-transform: uppercase;
        }

        .plan-price {
            font-size: 2.5rem;
            font-weight: 700;
            color: #28a745;
            margin-bottom: 0.5rem;
        }

        .commission-badge {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            padding: 0.5rem;
            border-radius: 15px;
            margin-bottom: 1.5rem;
            font-weight: 600;
        }

        .plan-features {
            list-style: none;
            padding: 0;
            margin-bottom: 2rem;
            text-align: left;
        }

        .plan-features li {
            margin-bottom: 1rem;
            color: #2c3e50;
        }

        .plan-features li i {
            color: #28a745;
            margin-right: 0.5rem;
        }

        .btn-plan {
            width: 100%;
            padding: 1rem;
            border-radius: 15px;
            font-weight: 600;
            border: none;
            transition: all 0.3s ease;
            text-transform: uppercase;
        }

        .btn-basic {
            background-color: #6c757d;
            color: white;
        }

        .btn-pro {
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            color: white;
        }

        .btn-premium {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
        }

        .btn-plan:hover {
            filter: brightness(1.1);
            transform: translateY(-2px);
            color: white;
        }
    </style>
</head>

<body>
    <div class="contenedorAlta">
       <div class="hero-section">
            <h1><i class="fas fa-crown"></i> Planes de Suscripción</h1>
            <p>Elige el plan perfecto para tu negocio de alojamiento. Más establecimientos, menos comisiones, mayores beneficios.</p>
        </div>
        <div class="alert alert-success" id="success-message" <?php echo !empty($formSuccess) ? 'style="display:block"' : 'style="display:none"'; ?>>
            <i class="fas fa-check-circle me-2"></i> <span id="success-text"><?php echo $formSuccess; ?></span>
        </div>

        <form method="post" action="" id="planForm">
            <div class="plans-container">
                <div class="plan-card">
                    <div class="plan-name">Básico</div>
                    <div class="plan-price">€0<small style="font-size: 1rem; color: #6c757d;">/mes</small></div>
                    <div class="commission-badge">Comisión del 15%</div>
                    <ul class="plan-features">
                        <li><i class="fas fa-check"></i> <b>1</b> establecimiento</li>
                        <li><i class="fas fa-check"></i> <b>3</b> espacios máximo</li>
                        <li><i class="fas fa-check"></i> Perfecto para empezar</li>
                    </ul>
                    <button type="submit" name="plan" value="Basico" class="btn btn-plan btn-basic">Elegir
                        Básico</button>
                </div>

                <div class="plan-card popular">
                    <div class="plan-name">Pro</div>
                    <div class="plan-price">€9.99<small style="font-size: 1rem; color: #6c757d;">/mes</small></div>
                    <div class="commission-badge">Comisión del 12%</div>
                    <ul class="plan-features">
                        <li><i class="fas fa-check"></i> <b>2</b> establecimientos</li>
                        <li><i class="fas fa-check"></i> <b>10</b> espacios máximo</li>
                        <li><i class="fas fa-check"></i> Equilibrio perfecto</li>
                    </ul>
                    <button type="submit" name="plan" value="Pro" class="btn btn-plan btn-pro">Elegir Pro</button>
                </div>

                <div class="plan-card">
                    <div class="plan-name">Premium</div>
                    <div class="plan-price">€19.99<small style="font-size: 1rem; color: #6c757d;">/mes</small></div>
                    <div class="commission-badge">Comisión del 10%</div>
                    <ul class="plan-features">
                        <li><i class="fas fa-check"></i> Establecimientos <b>ilimitados</b></li>
                        <li><i class="fas fa-check"></i> Espacios <b>ilimitados</b></li>
                        <li><i class="fas fa-check"></i> Para grandes anfitriones</li>
                    </ul>
                    <button type="submit" name="plan" value="Premium" class="btn btn-plan btn-premium">Elegir
                        Premium</button>
                </div>
            </div>

            <div class="progress-container mt-5">
                <div class="progress-bar" style="width: 100%;"></div>
            </div>

            <div class="container mt-4">
                <div class="row">
                    <div class="col-12 text-start">
                        <button class="btn btn-light border rounded-pill px-4" type="button"
                            onclick="location.href='registerAnfitrion-paso5.php'">Anterior</button>
                    </div>
                </div>
            </div>
        </form>

        <div class="container-fluid p-3">
            <div class="row text-center">
                <div class="col-12">Paso 6 de 6</div>
            </div>
        </div>
    </div>
</body>

</html>