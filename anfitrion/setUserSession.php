<?php
session_start();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['error' => 'Datos JSON inválidos']);
    exit;
}

if (isset($data['already_guest']) && isset($data['id_user'])) {
    $_SESSION['already_guest'] = $data['already_guest'];
    $_SESSION['id_user'] = $data['id_user'];
    
    error_log("Sesión establecida - already_guest: " . ($data['already_guest'] ? 'true' : 'false') . ", id_user: " . $data['id_user']);
    
    echo json_encode([
        'success' => true,
        'message' => 'Variables de sesión establecidas correctamente'
    ]);
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Faltan parámetros requeridos']);
}
?>