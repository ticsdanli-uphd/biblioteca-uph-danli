<?php
session_start();

// Si ya hay una sesión activa (usuario autenticado), redirige al dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: /biblioteca/dashboard.php");
    exit();
}

// Si no está autenticado, redirige al login
header("Location: login.php");
exit();
?>