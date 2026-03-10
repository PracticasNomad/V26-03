<?php
// Solo iniciamos la sesión si no se ha iniciado ya
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Comprobamos si NO existe el token o el ID de usuario
if (!isset($_SESSION['user_id']) || !isset($_SESSION['token'])) {
    // Si no existen, borramos cualquier rastro de la sesión
    session_unset();
    session_destroy();

    // Y lo expulsamos a la pantalla de login del gestor
    header("Location: /");
    exit();
}
