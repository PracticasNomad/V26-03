<?php
session_start();

// Comprobamos si tiene el pase VIP (viene directo de verificar el email)
$vieneDelRegistro = (isset($_SESSION['auth_from_registration']) && $_SESSION['auth_from_registration'] === true);

// Si no hay user_id, o (no hay token Y tampoco viene del registro verificado), lo echamos
if (!isset($_SESSION['user_id']) || (!isset($_SESSION['token']) && !$vieneDelRegistro)) {
    session_unset();
    session_destroy();
    header("Location: /"); // Te echaba aquí porque la condición fallaba
    exit();
}
?>