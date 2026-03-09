<?php

session_start();

function limpiarSesionesReserva() {
    unset($_SESSION['reserva']);
    unset($_SESSION['spaceId']);
    unset($_SESSION['reservaExitosa']);
    unset($_SESSION['codigo_reserva']);
    unset($_SESSION['reserva_procesada']);
}

limpiarSesionesReserva();

header('Content-Type: application/json');
echo json_encode(['status' => 'success', 'message' => 'Sesiones limpiadas correctamente']);
?>