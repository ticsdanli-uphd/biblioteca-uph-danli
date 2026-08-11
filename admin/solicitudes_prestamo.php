<?php
// ============================================================
// admin/solicitudes_prestamo.php
// Bandeja administrativa de solicitudes.
// Solo Administrador.
// ============================================================
include '../includes/session.php';
include '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php'); exit();
}
$rol = strtolower(trim($_SESSION['role'] ?? $_SESSION['rol'] ?? $_SESSION['tipo_usuario'] ?? ''));
if (!in_array($rol,['admin','administrador'],true)) {
    header('Location: ../dashboard.php'); exit();
}
$sede = (int)($_SESSION['sede_seleccionada'] ?? 4);
$error=''; $success='';

$sql = "
SELECT s.*, b.nombre libro_nombre,b.codigo,b.ubicacion,
       c.nombre carrera_nombre, u.username
FROM solicitudes_prestamo s
INNER JOIN bibliografia b ON b.id=s.bibliografia_id
LEFT JOIN carreras c ON c.id=s.carrera_id
INNER JOIN usuarios u ON u.id=s.usuario_id
WHERE s.sede_id=?
ORDER BY FIELD(s.estado,'pendiente','aprobada','prestado','rechazada','cancelada'),s.fecha_solicitud DESC";
$stmt=$conn->prepare($sql); $stmt->bind_param('i',$sede); $stmt->execute(); $result=$stmt->get_result();

include '../includes/header.php';
?>
<style>
.page{max-width:1250px;margin:30px auto}.hero{background:#fff;border-radius:18px;padding:24px;box-shadow:0 8px 30px rgba(0,0,0,.07)}
.card-sol{background:#fff;border-radius:16px;padding:20px;box-shadow:0 6px 22px rgba(0,0,0,.06);height:100%}
.badge-p{background:#fff3cd;color:#946c00}.badge-a{background:#dbeafe;color:#1d4ed8}.badge-s{background:#d1fae5;color:#047857}.badge-r{background:#fee2e2;color:#b91c1c}
</style>
<div class="container-fluid"><div class="page">
<div class="hero mb-4">
<h2 class="fw-bold mb-1"><i class="fas fa-bell text-primary me-2"></i>Solicitudes de Préstamo</h2>
<p class="text-muted mb-0">Revise, apruebe y registre la entrega de libros solicitados.</p>
</div>
<?php if(isset($_SESSION['success_msg'])): ?><div class="alert alert-success"><?=htmlspecialchars($_SESSION['success_msg']); unset($_SESSION['success_msg']);?></div><?php endif;?>
<?php if(isset($_SESSION['error_msg'])): ?><div class="alert alert-danger"><?=htmlspecialchars($_SESSION['error_msg']); unset($_SESSION['error_msg']);?></div><?php endif;?>

<div class="row g-4">
<?php while($r=$result->fetch_assoc()): 
$map=['pendiente'=>['Pendiente','p'],'aprobada'=>['Aprobada - por recoger','a'],'prestado'=>['Prestado','s'],'rechazada'=>['Rechazada','r'],'cancelada'=>['Cancelada','r']];
[$txt,$cl]=$map[$r['estado']] ?? [$r['estado'],'p']; ?>
<div class="col-12 col-lg-6">
<div class="card-sol">
<div class="d-flex justify-content-between gap-2">
<h5 class="fw-bold mb-2"><?=htmlspecialchars($r['libro_nombre'])?></h5>
<span class="badge badge-<?=$cl?>"><?=htmlspecialchars($txt)?></span>
</div>
<div class="text-muted small mb-3">Código: <?=htmlspecialchars($r['codigo'])?></div>
<p class="mb-1"><strong>Solicitante:</strong> <?=htmlspecialchars($r['nombre_solicitante'])?></p>
<p class="mb-1"><strong>Usuario:</strong> <?=htmlspecialchars($r['username'])?></p>
<p class="mb-1"><strong>Carrera:</strong> <?=htmlspecialchars($r['carrera_nombre'] ?: 'No especificada')?></p>
<p class="mb-1"><strong>Ubicación:</strong> <?=htmlspecialchars($r['ubicacion'] ?: 'Biblioteca UPH Danlí')?></p>
<p class="mb-3"><strong>Solicitud:</strong> <?=htmlspecialchars(date('d/m/Y H:i',strtotime($r['fecha_solicitud'])))?></p>
<?php if($r['observaciones']): ?><div class="alert alert-light py-2"><strong>Observación:</strong> <?=htmlspecialchars($r['observaciones'])?></div><?php endif;?>
<div class="d-flex gap-2 flex-wrap">
<?php if($r['estado']==='pendiente'): ?>
<form method="post" action="solicitud_accion.php">
<input type="hidden" name="id" value="<?=$r['id']?>">
<input type="hidden" name="accion" value="aprobar">
<button class="btn btn-primary"><i class="fas fa-check me-1"></i>Aceptar préstamo</button>
</form>
<form method="post" action="solicitud_accion.php">
<input type="hidden" name="id" value="<?=$r['id']?>">
<input type="hidden" name="accion" value="rechazar">
<button class="btn btn-outline-danger"><i class="fas fa-times me-1"></i>Rechazar</button>
</form>
<?php elseif($r['estado']==='aprobada'): ?>
<form method="post" action="solicitud_accion.php">
<input type="hidden" name="id" value="<?=$r['id']?>">
<input type="hidden" name="accion" value="entregar">
<button class="btn btn-success"><i class="fas fa-hand-holding me-1"></i>Marcar como prestado / entregado</button>
</form>
<div class="w-100 small text-success"><i class="fas fa-map-marker-alt me-1"></i>El alumno puede recogerlo en: <strong><?=htmlspecialchars($r['ubicacion'] ?: 'Biblioteca UPH Danlí')?></strong></div>
<?php elseif($r['estado']==='prestado'): ?>
<div class="alert alert-success py-2 mb-0 w-100"><i class="fas fa-check-circle me-1"></i>Préstamo entregado. El libro está oficialmente prestado.</div>
<?php endif; ?>
</div>
</div></div>
<?php endwhile; ?>
</div></div></div>
<?php include '../includes/footer.php'; ?>
