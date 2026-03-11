<?php
// 1. Iniciamos la sesión para poder interactuar con ella
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. Vaciamos todas las variables de sesión actuales ($_SESSION['user_id'], $_SESSION['token'], etc.)
$_SESSION = array();

// 3. Borramos la cookie de la sesión en el navegador (Paso extra de seguridad)
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// 4. Destruimos la sesión por completo en el servidor
session_destroy();

// 5. Redirigimos al usuario a la página principal (o a la pantalla de login)
header("Location: /");
exit();
