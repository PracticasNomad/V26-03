<?php
require_once 'verificar_sesion_host.php';
if (isset($_POST['tipoSuscripcion'])) {
    $_SESSION['tipoSuscripcion'] = $_POST['tipoSuscripcion'];
}

// Verificamos que llegaron los datos
if (isset($_POST['address']) && isset($_POST['subscriptionPlan']) && isset($_POST['termsSubscription'])) {
    $_SESSION['direccion'] = $_POST['address'];

    // Convertimos el valor del plan a minúsculas para evitar problemas de mayúsculas
    $plan = strtolower($_POST['subscriptionPlan']);
    $_SESSION['plan'] = $plan;

    // Obtener la fecha de hoy
    $fechaInicio = date('Y-m-d');

    // Calcular fecha de fin y precio
    if ($_SESSION['tipoSuscripcion'] == 'Pro') {
        if ($plan === 'mensual') {
            $fechaFin = date('Y-m-d', strtotime('+1 month', strtotime($fechaInicio)));
            $precio = 9.99;
        } elseif ($plan === 'anual') {
            $fechaFin = date('Y-m-d', strtotime('+1 year', strtotime($fechaInicio)));
            $precio = 99.99;
        } else {
            // Plan no válido
            echo "Plan de suscripción no válido.";
            exit();
        }
    } else {
        if ($plan === 'mensual') {
            $fechaFin = date('Y-m-d', strtotime('+1 month', strtotime($fechaInicio)));
            $precio = 19.99;
        } elseif ($plan === 'anual') {
            $fechaFin = date('Y-m-d', strtotime('+1 year', strtotime($fechaInicio)));
            $precio = 179.99;
        } else {
            // Plan no válido
            echo "Plan de suscripción no válido.";
            exit();
        }
    }

    $_SESSION['fecha_fin'] = $fechaFin;
    $_SESSION['total'] = $precio;

    if ($_SESSION['tipoSuscripcion'] == 'Pro') {

        header("Location: mejorar.php");
    } else {
        header("Location: mejorarPremium.php");
    }
} else {
    echo "Faltan datos obligatorios. Por favor, vuelve atrás e intenta de nuevo.";
}
