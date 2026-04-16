<?php
session_start();

if (!function_exists('generateUuidV4')) {
    function generateUuidV4()
    {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}

if (!isset($_SESSION['codigo_reserva'])) {
    $_SESSION['codigo_reserva'] = generateUuidV4();
}

$_SESSION['reservaExitosa'] = false;
$_SESSION['reserva_procesada'] = false;

header('Location: ../reservarEspacio-completo.php?sendEmail=true');
exit;