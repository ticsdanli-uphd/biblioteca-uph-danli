<?php
// Ruta de compatibilidad para alumnos y docentes.
require_once 'includes/session.php';

$rol = strtolower(trim(
    $_SESSION['role'] ?? $_SESSION['rol'] ?? ''
));

if (in_array($rol, ['alumno','usuario','estudiante','student','docente','teacher','profesor'], true)) {
    header('Location: usuario/mis_prestamos.php');
    exit();
}

header('Location: dashboard.php');
exit();
