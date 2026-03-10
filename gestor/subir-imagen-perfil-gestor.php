
<?php
require_once 'verificar_sesion_gestor.php';
require '../vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['imagen'])) {

    $gestorId = $_POST['gestorId'] ?? $_SESSION['user_id'];
    $archivo = $_FILES['imagen'];

    // 1. Validar la imagen
    $permitidos = ['image/jpeg', 'image/png', 'image/jpg'];
    if (!in_array($archivo['type'], $permitidos)) {
        echo json_encode(['success' => false, 'message' => 'Formato no permitido. Solo JPG o PNG.']);
        exit;
    }

    // 2. Crear un nombre único y la ruta de destino
    $directorioDestino = '../uploads/perfiles/';
    // Si la carpeta no existe, la crea
    if (!file_exists($directorioDestino)) {
        mkdir($directorioDestino, 0777, true);
    }

    $extension = pathinfo($archivo['name'], PATHINFO_EXTENSION);
    $nombreArchivo = 'gestor_' . $gestorId . '_' . time() . '.' . $extension;
    $rutaFinal = $directorioDestino . $nombreArchivo;
    $rutaParaBD = 'uploads/perfiles/' . $nombreArchivo; // Ruta relativa para guardar en BD

    // 3. Mover el archivo subido
    if (move_uploaded_file($archivo['tmp_name'], $rutaFinal)) {

        // 4. Actualizar la base de datos (Tabla gestor)
        $url = "http://" . $_ENV['SERVER_IP'] . ":" . $_ENV['DATABASE_PORT'] . "/rest/v1/gestor?id=eq." . $gestorId;

        $data = ['avatar_url' => '../' . $rutaParaBD];

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
            echo json_encode(['success' => true, 'avatarUrl' => '../' . $rutaParaBD]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al guardar en base de datos.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al guardar el archivo en el servidor.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'No se ha enviado ninguna imagen.']);
}
?>