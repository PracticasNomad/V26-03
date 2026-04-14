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

    // 1. CAPTURAR ERRORES DE LÍMITE DE PESO
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

    $extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
    if ($extension == 'jpeg') $extension = 'jpg';

    $permitidos = ['jpg', 'png', 'webp', 'gif'];
    if (!in_array($extension, $permitidos)) {
        echo json_encode(['success' => false, 'message' => "Formato no compatible. Usa JPG o PNG."]);
        exit;
    }

    $mimeTypes = [
        'jpg' => 'image/jpeg', 'png' => 'image/png', 
        'gif' => 'image/gif', 'webp' => 'image/webp'
    ];
    $fileType = $mimeTypes[$extension];

    // Configuración MinIO
    $minioBucket = 'images';
    $nombreArchivo = 'admin_' . trim($adminId) . '_' . time() . '.' . $extension;
    $minioUrl = rtrim($_ENV['MINIO_PUBLIC_URL'], '/') . '/' . $minioBucket . '/' . $nombreArchivo;
    
    $rutaTemporal = $archivo['tmp_name'];
    $fileContent = file_get_contents($rutaTemporal);

    // 2. SUBIDA DIRECTA A MINIO
    $ch = curl_init($minioUrl);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
    curl_setopt($ch, CURLOPT_POSTFIELDS, $fileContent);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    // --- BYPASS SSL PARA LA IP LOCAL ---
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    // -----------------------------------

    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: ' . $fileType,
        'Content-Length: ' . strlen($fileContent)
    ]);

    $resultado = curl_exec($ch);
    $codigoRespuesta = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($codigoRespuesta >= 200 && $codigoRespuesta < 300) {

        // 3. Actualizar la base de datos
        $url = "http://" . $_ENV['SERVER_IP'] . ":" . $_ENV['DATABASE_PORT'] . "/rest/v1/admin?id=eq." . urlencode($adminId);
        $data = ['avatar_url' => $minioUrl];

        $ch_db = curl_init($url);
        curl_setopt($ch_db, CURLOPT_CUSTOMREQUEST, 'PATCH');
        curl_setopt($ch_db, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch_db, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch_db, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $_ENV['SERVICE_APIKEY'],
            'apikey: ' . $_ENV['SERVICE_APIKEY']
        ]);

        $result_db = curl_exec($ch_db);
        $httpCode_db = curl_getinfo($ch_db, CURLINFO_HTTP_CODE);
        curl_close($ch_db);

        if ($httpCode_db >= 200 && $httpCode_db < 300) {
            echo json_encode(['success' => true, 'avatarUrl' => $minioUrl]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al guardar ruta en BD.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Error MinIO: ' . $codigoRespuesta . ' - ' . $curlError]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Petición vacía o incorrecta.']);
}
?>