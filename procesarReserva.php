<?php
require_once 'verificar_sesion_guest.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $_SESSION['reserva'] = [
        'spaceId' => $_SESSION['spaceId'],
        'date' => $_POST['reservationDate'],
        'startTime' => $_POST['startTime'],
        'endTime' => $_POST['endTime'],
        'dni' => $_POST['dni'],
        'direccion' => $_POST['direccion'],
        'message' => $_POST['message'] ?? ''
    ];

    header('Location: reservarEspacio-pago.php');
    exit;
} else {
    header('Location: nomada_explorar.php');
    exit;
}
