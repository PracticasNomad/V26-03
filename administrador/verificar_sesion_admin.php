<?php
// Solo iniciamos la sesión si no se ha iniciado ya
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Comprobamos si NO existe el token, el ID, o si NO es administrador
if (!isset($_SESSION['user_id']) || !isset($_SESSION['token']) || !isset($_SESSION['rol']) || $_SESSION['rol'] !== 'administrador') {


    session_unset();
    session_destroy();

    // Los expulsamos a la pantalla principal
    header("Location: /");
    exit();
}