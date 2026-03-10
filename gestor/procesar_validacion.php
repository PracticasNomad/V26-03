<?php
// Asegúrate de que estás recibiendo el valor correcto en 'ajax' y configurando el tipo de contenido
$isAjax = isset($_GET['ajax']) && $_GET['ajax'] == '1';

$accion = $_POST['accion'] ?? null; // Asegúrate de que estás recibiendo la acción correctamente

if ($accion === 'aprobar') {
    // Actualizar estado y validación
    $update = [
        'estado' => 'aprobado',
        'estaValidado' => true, // Marca como validado al aprobar
    ];
} elseif ($accion === 'rechazar') {
    // Si rechazas, actualiza estado y validación
    $update = [
        'estado' => 'rechazado',
        'estaValidado' => false, // Marca como no validado
    ];
}

//// Realiza la actualización (en tu base de datos o donde corresponda)

// Si la petición es AJAX, devuelve una respuesta JSON
if ($isAjax) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => true,
        'message' => 'Establecimiento validado correctamente.'
    ]);
    exit;
}

//// Si no es AJAX, redirige al listado de validaciones
header('Location: verValidar.php');
exit;
?>