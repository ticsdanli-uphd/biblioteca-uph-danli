<?php
require_once '../includes/session.php';
require_once '../config/db.php';
require_once '../config/app.php';
require_once '../includes/permisos.php';
requiere_login();
if (($_SESSION['role'] ?? '') !== 'alumno') { header('Location:/biblioteca/dashboard.php'); exit(); }
$uid=(int)$_SESSION['user_id'];
$st=$conn->prepare("SELECT r.*,b.nombre libro,b.codigo FROM registro_visitas r LEFT JOIN bibliografia b ON b.id=r.bibliografia_id WHERE r.beneficiario_usuario_id=? AND r.tipo='prestamo' ORDER BY r.fecha DESC");
$st->bind_param('i',$uid); $st->execute(); $rows=$st->get_result();
include '../includes/header.php';
?>
<div class="container-fluid py-4"><div class="card border-0 shadow-sm"><div class="card-header bg-primary text-white"><h4 class="mb-0">Mis préstamos</h4></div><div class="card-body">
<div class="table-responsive"><table class="table table-striped align-middle"><thead><tr><th>Libro</th><th>Código</th><th>Préstamo</th><th>Devolución</th><th>Estado</th></tr></thead><tbody>
<?php while($r=$rows->fetch_assoc()): $v=!$r['devuelto'] && !empty($r['fecha_devolucion_esperada']) && $r['fecha_devolucion_esperada']<date('Y-m-d'); ?>
<tr><td><?=htmlspecialchars($r['libro']??'N/A')?></td><td><?=htmlspecialchars($r['codigo']??'N/A')?></td><td><?=htmlspecialchars($r['fecha'])?></td><td><?=htmlspecialchars($r['fecha_devolucion_esperada']??'')?></td><td><?= $r['devuelto'] ? '<span class="badge bg-success">Devuelto</span>' : ($v ? '<span class="badge bg-danger">Vencido</span>' : '<span class="badge bg-primary">Prestado</span>') ?></td></tr>
<?php endwhile; ?></tbody></table></div></div></div></div>
<?php include '../includes/footer.php'; ?>
