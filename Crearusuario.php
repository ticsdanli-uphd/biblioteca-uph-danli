<?php
// Compatibilidad con la ruta antigua.
// La creación oficial de usuarios está en usuarios/add.php.
require_once 'includes/session.php';
require_once 'includes/permisos.php';

requiere_admin();

header('Location: usuarios/add.php');
exit();
