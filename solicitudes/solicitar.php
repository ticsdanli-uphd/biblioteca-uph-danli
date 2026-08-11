<?php
// Ruta de compatibilidad para solicitudes.
// El formulario oficial está en books/prestamo.php.
require_once '../includes/session.php';

$rol = strtolower(trim(
    $_SESSION['role'] ?? $_SESSION['rol'] ?? ''
));

if (!in_array($rol, ['alumno','usuario','estudiante','student','docente','teacher','profesor'], true)) {
    header('Location: ../dashboard.php');
    exit();
}

$id = (int)($_GET['id'] ?? $_POST['bibliografia_id'] ?? 0);

if ($id <= 0) {
    header('Location: ../books/list.php');
    exit();
}

header('Location: ../books/prestamo.php?id=' . $id);
exit();
