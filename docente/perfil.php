<?php
require_once '../includes/session.php';
require_once '../config/db.php';
require_once '../config/app.php';
require_once '../includes/permisos.php';
requiere_login();
if (($_SESSION['role'] ?? '') !== 'docente') { header('Location:/biblioteca/dashboard.php'); exit(); }
$uid=(int)$_SESSION['user_id'];
$st=$conn->prepare("SELECT nombre,email,telefono,carrera_id FROM docentes WHERE usuario_id=? AND sede_id=? LIMIT 1");
$sid=DANLI_SEDE_ID; $st->bind_param('ii',$uid,$sid); $st->execute(); $perfil=$st->get_result()->fetch_assoc() ?: []; $st->close();
$carrera='No especificada';
if(!empty($perfil['carrera_id'])){ $st=$conn->prepare("SELECT nombre FROM carreras WHERE id=?");$st->bind_param('i',$perfil['carrera_id']);$st->execute();$carrera=$st->get_result()->fetch_assoc()['nombre']??$carrera;$st->close(); }
include '../includes/header.php';
?>
<div class="container-fluid py-4"><div class="row justify-content-center"><div class="col-xl-7"><div class="card border-0 shadow-sm"><div class="card-header bg-primary text-white"><h4 class="mb-0">Mi perfil</h4></div><div class="card-body"><dl class="row mb-0"><dt class="col-sm-4">Nombre</dt><dd class="col-sm-8"><?=htmlspecialchars($perfil['nombre']??$_SESSION['nombre_completo'])?></dd><dt class="col-sm-4">Correo</dt><dd class="col-sm-8"><?=htmlspecialchars($perfil['email']??$_SESSION['username'])?></dd><dt class="col-sm-4">Teléfono</dt><dd class="col-sm-8"><?=htmlspecialchars($perfil['telefono']??'No registrado')?></dd><dt class="col-sm-4">Carrera</dt><dd class="col-sm-8"><?=htmlspecialchars($carrera)?></dd><dt class="col-sm-4">Sede</dt><dd class="col-sm-8">Danlí</dd></dl></div></div></div></div></div>
<?php include '../includes/footer.php'; ?>
