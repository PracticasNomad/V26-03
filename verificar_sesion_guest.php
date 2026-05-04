<?php
session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['token'])) {
    session_unset();
    session_destroy();
    header("Location: /"); // cambiar en producción
    exit();
}
