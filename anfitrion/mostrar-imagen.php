<?php
if (!isset($_GET['ruta']) || empty($_GET['ruta'])) {
    header('HTTP/1.0 400 Bad Request');
    echo 'Ruta de imagen no proporcionada';
    exit;
}

$ruta = $_GET['ruta'];

if (!file_exists($ruta)) {
    header('HTTP/1.0 404 Not Found');
    echo 'Imagen no encontrada';
    exit;
}

$extension = strtolower(pathinfo($ruta, PATHINFO_EXTENSION));
$tipos = [
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png' => 'image/png',
    'gif' => 'image/gif'
];
$tipo = isset($tipos[$extension]) ? $tipos[$extension] : 'application/octet-stream';

if (strpos($tipo, 'image/') !== 0) {
    header('HTTP/1.0 400 Bad Request');
    echo 'El archivo no es una imagen';
    exit;
}

header('Content-Type: ' . $tipo);
header('Content-Length: ' . filesize($ruta));

readfile($ruta);
exit;
?>