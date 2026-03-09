<?php
session_start();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['lat']) && isset($_POST['lng'])) {
    // Validar que las coordenadas sean números válidos
    $lat = filter_var($_POST['lat'], FILTER_VALIDATE_FLOAT);
    $lng = filter_var($_POST['lng'], FILTER_VALIDATE_FLOAT);
    
    if ($lat !== false && $lng !== false) {
        // Actualizar las coordenadas en la sesión con ambos nombres
        $_SESSION['establecimiento']['lat'] = $lat;
        $_SESSION['establecimiento']['lng'] = $lng;
        $_SESSION['establecimiento']['latitud'] = $lat;  // Para el HTML del paso 4
        $_SESSION['establecimiento']['longitud'] = $lng; // Para el HTML del paso 4
        
        echo json_encode(['success' => true, 'message' => 'Coordenadas actualizadas']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Coordenadas inválidas']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Datos faltantes']);
}
?>