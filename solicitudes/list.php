<?php
// Ruta de compatibilidad.
// La bandeja oficial de solicitudes está en admin/solicitudes_prestamo.php.
require_once '../includes/session.php';
require_once '../includes/permisos.php';

requiere_admin();

header('Location: ../admin/solicitudes_prestamo.php');
exit();
