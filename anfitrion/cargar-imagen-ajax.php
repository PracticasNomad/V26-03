<?php

header('Content-Type: application/json');
session_start();

function guardarImagen($file, $upload_dir = 'uploads/establecimientos/') {
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    $nombre_original = basename($file['name']);
    $extension = pathinfo($nombre_original, PATHINFO_EXTENSION);
    $nombre_archivo = uniqid() . '_' . time() . '.' . $extension;
    $ruta_completa = $upload_dir . $nombre_archivo;
    
    if (move_uploaded_file($file['tmp_name'], $ruta_completa)) {
        return [
            'nombre_original' => $nombre_original,
            'nombre_archivo' => $nombre_archivo,
            'ruta' => $ruta_completa,
            'tipo' => $file['type'],
            'tamano' => $file['size']
        ];
    }
    
    return false;
}

if (isset($_FILES['foto']) && !empty($_FILES['foto']['name'])) {
    $allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
    $max_size = 5 * 1024 * 1024;
    
    $file = $_FILES['foto'];
    
    if (!in_array($file['type'], $allowed_types)) {
        echo json_encode([
            'success' => false,
            'message' => 'Formato de imagen no válido. Use JPG o PNG.'
        ]);
        exit;
    }
    
    if ($file['size'] > $max_size) {
        echo json_encode([
            'success' => false,
            'message' => 'La imagen excede el tamaño máximo permitido (5MB).'
        ]);
        exit;
    }
    
    $imagen_guardada = guardarImagen($file);
    
    if ($imagen_guardada) {
        if (!isset($_SESSION['establecimiento']['fotos'])) {
            $_SESSION['establecimiento']['fotos'] = [];
        }
        
        $_SESSION['establecimiento']['fotos'][] = $imagen_guardada;
        
        echo json_encode([
            'success' => true,
            'message' => 'Imagen subida correctamente',
            'imagen' => $imagen_guardada
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Error al guardar la imagen en el servidor.'
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'No se ha enviado ninguna imagen.'
    ]);
}
?>