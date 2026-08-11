<?php
// ============================================================
// usuario/mis_prestamos.php
// Vista de Alumno/Docente: solo sus solicitudes y préstamos.
// ============================================================
include '../includes/session.php';
include '../config/db.php';

if(!isset($_SESSION['user_id'])){header('Location: ../login.php');exit();}
$user_id=(int)$_SESSION['user_id'];

$rol=strtolower(trim($_SESSION['role'] ?? $_SESSION['rol'] ?? $_SESSION['tipo_usuario'] ?? ''));
if(in_array($rol,['student','estudiante'],true))$rol='alumno';
if(in_array($rol,['teacher','profesor'],true))$rol='docente';
if(!in_array($rol,['alumno','docente'],true)){header('Location: ../dashboard.php');exit();}

$stmt=$conn->prepare("
SELECT s.*,b.nombre libro_nombre,b.codigo,b.ubicacion,
       c.nombre carrera_nombre
FROM solicitudes_prestamo s
INNER JOIN bibliografia b ON b.id=s.bibliografia_id
LEFT JOIN carreras c ON c.id=s.carrera_id
WHERE s.usuario_id=?
ORDER BY s.fecha_solicitud DESC");
$stmt->bind_param('i',$user_id);$stmt->execute();$res=$stmt->get_result();

include '../includes/header.php';
?>
<style>
.page{max-width:1100px;margin:30px auto}.hero{background:#fff;border-radius:18px;padding:24px;box-shadow:0 8px 30px rgba(0,0,0,.07)}
.loan{background:#fff;border-radius:16px;padding:20px;box-shadow:0 6px 22px rgba(0,0,0,.06);margin-bottom:16px}
.pend{color:#946c00;background:#fff3cd}.ap{color:#1d4ed8;background:#dbeafe}.pre{color:#047857;background:#d1fae5}.rej{color:#b91c1c;background:#fee2e2}
.badge{padding:8px 11px;border-radius:999px}
.pick{background:#eef4ff;border:1px solid #d8e4ff;border-radius:12px;padding:14px}
</style>
<div class="container-fluid"><div class="page">
<div class="hero mb-4">
<h2 class="fw-bold"><i class="fas fa-book-reader text-primary me-2"></i>Mis préstamos y solicitudes</h2>
<p class="text-muted mb-0">Aquí puedes consultar si tu solicitud fue aceptada y cuándo puedes recoger tu libro.</p>
</div>
<?php if(isset($_SESSION['success_msg'])): ?><div class="alert alert-success"><?=htmlspecialchars($_SESSION['success_msg']);unset($_SESSION['success_msg']);?></div><?php endif;?>
<?php if(isset($_SESSION['error_msg'])): ?><div class="alert alert-danger"><?=htmlspecialchars($_SESSION['error_msg']);unset($_SESSION['error_msg']);?></div><?php endif;?>
<?php if($res->num_rows===0): ?>
<div class="alert alert-info">Aún no tienes solicitudes de préstamo.</div>
<?php endif;?>
<?php while($r=$res->fetch_assoc()):
$estado=$r['estado'];
$labels=['pendiente'=>'Solicitud pendiente','aprobada'=>'Aprobada - pendiente de recoger','prestado'=>'Prestado','rechazada'=>'Solicitud rechazada','cancelada'=>'Cancelada'];
$classes=['pendiente'=>'pend','aprobada'=>'ap','prestado'=>'pre','rechazada'=>'rej','cancelada'=>'rej'];
?>
<div class="loan">
<div class="d-flex justify-content-between align-items-start gap-2 flex-wrap">
<div><h4 class="mb-1 fw-bold"><?=htmlspecialchars($r['libro_nombre'])?></h4><div class="text-muted">Código: <?=htmlspecialchars($r['codigo'])?></div></div>
<span class="badge <?=$classes[$estado]??'pend'?>"><?=htmlspecialchars($labels[$estado]??$estado)?></span>
</div>
<hr>
<div class="row g-3">
<div class="col-md-4"><strong>Solicitud:</strong><br><?=date('d/m/Y H:i',strtotime($r['fecha_solicitud']))?></div>
<div class="col-md-4"><strong>Carrera:</strong><br><?=htmlspecialchars($r['carrera_nombre']?:'No especificada')?></div>
<div class="col-md-4"><strong>Ubicación:</strong><br><?=htmlspecialchars($r['ubicacion']?:'Biblioteca UPH Danlí')?></div>
</div>
<?php if($estado==='pendiente'): ?>
<div class="alert alert-warning mt-3 mb-0"><i class="fas fa-clock me-1"></i>Tu solicitud está siendo revisada por la Biblioteca.</div>
<?php elseif($estado==='aprobada'): ?>
<div class="pick mt-3">
<i class="fas fa-map-marker-alt text-primary me-1"></i>
<strong>¡Tu solicitud fue aprobada!</strong><br>
Puedes venir a recoger el libro en: <strong><?=htmlspecialchars($r['ubicacion']?:'Biblioteca UPH Danlí')?></strong>.<br>
<small class="text-muted">Cuando el personal te entregue el libro, el estado cambiará a <strong>Prestado</strong>.</small>
</div>
<?php elseif($estado==='prestado'): ?>
<div class="alert alert-success mt-3 mb-0"><i class="fas fa-check-circle me-1"></i><strong>Préstamo confirmado.</strong> El libro fue entregado y figura como prestado en tu cuenta.</div>
<?php elseif($estado==='rechazada'): ?>
<div class="alert alert-danger mt-3 mb-0"><strong>Solicitud no aprobada.</strong><br><?=htmlspecialchars($r['motivo_rechazo']?:'Consulta con la Biblioteca UPH.')?></div>
<?php endif;?>
</div>
<?php endwhile;?>
</div></div>
<?php include '../includes/footer.php'; ?>
