<?php
require_once '../includes/session.php';
require_once '../config/db.php';
require_once '../config/app.php';
require_once '../includes/permisos.php';
requiere_admin();
$st=$conn->prepare("SELECT d.*,u.username,u.activo,c.nombre carrera FROM docentes d INNER JOIN usuarios u ON u.id=d.usuario_id LEFT JOIN carreras c ON c.id=d.carrera_id WHERE d.sede_id=? ORDER BY d.nombre");
$sid=DANLI_SEDE_ID;$st->bind_param('i',$sid);$st->execute();$rows=$st->get_result();
include '../includes/header.php';
?>
<div class="container-fluid py-4"><div class="card border-0 shadow-sm"><div class="card-header bg-primary text-white d-flex justify-content-between align-items-center"><h4 class="mb-0">Docentes - Danlí</h4><a href="../usuarios/add.php" class="btn btn-light text-primary">Crear docente</a></div><div class="card-body"><div class="table-responsive"><table class="table table-striped align-middle"><thead><tr><th>Nombre</th><th>Usuario</th><th>Correo</th><th>Teléfono</th><th>Carrera</th><th>Estado</th></tr></thead><tbody><?php while($d=$rows->fetch_assoc()):?><tr><td><?=htmlspecialchars($d['nombre'])?></td><td><?=htmlspecialchars($d['username'])?></td><td><?=htmlspecialchars($d['email']??'')?></td><td><?=htmlspecialchars($d['telefono']??'')?></td><td><?=htmlspecialchars($d['carrera']??'No especificada')?></td><td><?=$d['activo']?'<span class="badge bg-success">Activo</span>':'<span class="badge bg-secondary">Inactivo</span>'?></td></tr><?php endwhile;?></tbody></table></div></div></div></div>
<?php include '../includes/footer.php'; ?>
