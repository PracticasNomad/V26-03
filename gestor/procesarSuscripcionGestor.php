<?php
require_once 'verificar_sesion_gestor.php';

if (isset($_POST['tipoSuscripcion'])) {
    $_SESSION['tipoSuscripcion'] = $_POST['tipoSuscripcion'];
}

// Verificamos que llegaron los datos
if (isset($_POST['address']) && isset($_POST['subscriptionPlan']) && isset($_POST['termsSubscription'])) {
    $_SESSION['direccion'] = $_POST['address'];

    $plan = strtolower($_POST['subscriptionPlan']);
    $_SESSION['plan'] = $plan;
    $fechaInicio = date('Y-m-d');
    $_SESSION['fecha_inicio'] = $fechaInicio;

    // Calcular fecha de fin y precio
    if ($_SESSION['tipoSuscripcion'] == 'Basico') {
        $fechaFin = date('Y-m-d', strtotime('+1 ' . ($plan === 'mensual' ? 'month' : 'year')));
        $precio = $plan === 'mensual' ? 700.00 : 7700.00;
    } else if ($_SESSION['tipoSuscripcion'] == 'Pro') {
        $fechaFin = date('Y-m-d', strtotime('+1 ' . ($plan === 'mensual' ? 'month' : 'year')));
        $precio = $plan === 'mensual' ? 1900.00 : 20900.00;
    } else if ($_SESSION['tipoSuscripcion'] == 'Premium') {
        $fechaFin = date('Y-m-d', strtotime('+1 ' . ($plan === 'mensual' ? 'month' : 'year')));
        $precio = $plan === 'mensual' ? 2850.00 : 31350.00;
    }

    $_SESSION['fecha_fin'] = $fechaFin;
    $_SESSION['precio_base'] = $precio;
    $_SESSION['total'] = $precio;

    // REDIRECCIONES CORRECTAS (A los archivos que SÍ son la pantalla de resumen azul)
    if ($_SESSION['tipoSuscripcion'] == 'Basico') {
        header("Location: mejorarGestorBasico.php");
        exit();
    } else if ($_SESSION['tipoSuscripcion'] == 'Pro') {
        header("Location: mejorarGestorPro.php");
        exit();
    } else {
        header("Location: mejorarGestorPremium.php");
        exit();
    }
} else {
    echo "Faltan datos obligatorios. Por favor, vuelve atrás e intenta de nuevo.";
}