<?php
function requiere_login(): void {
    if (!isset($_SESSION['user_id'])) {
        header('Location: /biblioteca/login.php');
        exit();
    }
}
function es_admin(): bool { return ($_SESSION['role'] ?? '') === 'admin'; }
function es_docente(): bool { return ($_SESSION['role'] ?? '') === 'docente'; }
function es_alumno(): bool { return in_array($_SESSION['role'] ?? '', ['alumno','usuario'], true); }
function requiere_admin(): void {
    requiere_login();
    if (!es_admin()) {
        header('Location: /biblioteca/dashboard.php');
        exit();
    }
}
