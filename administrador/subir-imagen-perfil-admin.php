<?php
session_start();

// Verificación básica de seguridad
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'administrador') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'No autorizado.']);
    exit;
}

require '../vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['imagen'])) {

    $adminId = $_POST['adminId'] ?? $_SESSION['user_id'];
    $archivo = $_FILES['imagen'];

    // 1. CAPTURAR ERRORES DE LÍMITE DE PESO (A prueba de Mac/iPhones)
    if ($archivo['error'] !== UPLOAD_ERR_OK) {
        $mensajesError = [
            UPLOAD_ERR_INI_SIZE   => 'La imagen pesa demasiado (Supera el límite de PHP).',
            UPLOAD_ERR_FORM_SIZE  => 'La imagen supera el límite del formulario.',
            UPLOAD_ERR_PARTIAL    => 'El archivo se subió a medias.',
            UPLOAD_ERR_NO_FILE    => 'No se subió ningún archivo.',
            UPLOAD_ERR_NO_TMP_DIR => 'Falta la carpeta temporal en el servidor.',
            UPLOAD_ERR_CANT_WRITE => 'Fallo al escribir en el disco (Revisa permisos).',
            UPLOAD_ERR_EXTENSION  => 'Una extensión de PHP detuvo la subida.'
        ];
        $mensaje = $mensajesError[$archivo['error']] ?? 'Error desconocido al subir.';
        echo json_encode(['success' => false, 'message' => $mensaje]);
        exit;
    }

    // 2. VALIDAR EL ARCHIVO REAL
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeReal = finfo_file($finfo, $archivo['tmp_name']);
    finfo_close($finfo);

    $permitidos = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];

    if (!in_array($mimeReal, $permitidos)) {
        echo json_encode(['success' => false, 'message' => "Formato no compatible ($mimeReal). Usa JPG o PNG."]);
        exit;
    }

    // 3. Crear directorio con permisos si no existe
    $directorioDestino = '../uploads/perfiles/';
    if (!file_exists($directorioDestino)) {
        mkdir($directorioDestino, 0777, true);
        chmod($directorioDestino, 0777);
    }

    $extension = pathinfo($archivo['name'], PATHINFO_EXTENSION);
    $extension = strtolower($extension);
    if ($extension == 'jpeg') $extension = 'jpg';

    $nombreArchivo = 'admin_' . $adminId . '_' . time() . '.' . $extension;
    $rutaFinal = $directorioDestino . $nombreArchivo;
    $rutaParaBD = '../uploads/perfiles/' . $nombreArchivo;

    // 4. Mover el archivo subido
    if (move_uploaded_file($archivo['tmp_name'], $rutaFinal)) {

        // 5. Actualizar la base de datos (Usando Service Key en tabla admin)
        $url = "http://" . $_ENV['SERVER_IP'] . ":" . $_ENV['DATABASE_PORT'] . "/rest/v1/admin?id=eq." . urlencode($adminId);
        $data = ['avatar_url' => $rutaParaBD];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $_ENV['SERVICE_APIKEY'],
            'apikey: ' . $_ENV['SERVICE_APIKEY']
        ]);

        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode >= 200 && $httpCode < 300) {
            echo json_encode(['success' => true, 'avatarUrl' => $rutaParaBD]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al guardar ruta en BD.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'No se pudo mover el archivo por permisos del servidor.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Petición vacía o incorrecta.']);
}
