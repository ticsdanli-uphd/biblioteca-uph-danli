<?php
require_once '../includes/session.php';
require_once '../config/db.php';
require_once '../config/app.php';
require_once '../includes/permisos.php';
requiere_login();
if (($_SESSION['role'] ?? '') !== 'alumno') { header('Location: /biblioteca/dashboard.php'); exit(); }
$uid=(int)$_SESSION['user_id'];
$st=$conn->prepare("SELECT nombre,email,telefono FROM alumnos WHERE usuario_id=? AND sede_id=? LIMIT 1");
$sid=DANLI_SEDE_ID; $st->bind_param('ii',$uid,$sid); $st->execute(); $perfil=$st->get_result()->fetch_assoc() ?: []; $st->close();
$st=$conn->prepare("SELECT COUNT(*) total FROM registro_visitas WHERE user_id=? AND tipo='prestamo' AND devuelto=0");
$st->bind_param('i',$uid); $st->execute(); $prestamos=(int)$st->get_result()->fetch_assoc()['total']; $st->close();
$st=$conn->prepare("SELECT COUNT(*) total FROM reservas_libros WHERE user_id=? AND estado IN('pendiente','notificada')");
$st->bind_param('i',$uid); $st->execute(); $reservas=(int)$st->get_result()->fetch_assoc()['total']; $st->close();
$st=$conn->prepare("SELECT COUNT(*) total FROM registro_visitas WHERE user_id=? AND tipo='prestamo' AND devuelto=0 AND fecha_devolucion_esperada<CURDATE()");
$st->bind_param('i',$uid); $st->execute(); $vencidos=(int)$st->get_result()->fetch_assoc()['total']; $st->close();
include '../includes/header.php';
?>
<div class="container-fluid py-4">
<div class="mb-4"><h2>Bienvenido(a), <?=htmlspecialchars($perfil['nombre'] ?? $_SESSION['nombre_completo'])?></h2><p class="text-muted">Portal de Biblioteca UPH · Alumno · Danlí</p></div>
<div class="row g-3 mb-4">
<div class="col-12 col-md-4"><div class="card border-0 shadow-sm h-100"><div class="card-body"><div class="text-muted">Mis préstamos</div><div class="display-6 fw-bold"><?=$prestamos?></div></div></div></div>
<div class="col-12 col-md-4"><div class="card border-0 shadow-sm h-100"><div class="card-body"><div class="text-muted">Mis reservas</div><div class="display-6 fw-bold"><?=$reservas?></div></div></div></div>
<div class="col-12 col-md-4"><div class="card border-0 shadow-sm h-100"><div class="card-body"><div class="text-muted">Préstamos vencidos</div><div class="display-6 fw-bold text-danger"><?=$vencidos?></div></div></div></div>
</div>
<div class="row g-3">
<div class="col-12 col-md-6"><a href="/biblioteca/books/list.php" class="text-decoration-none"><div class="card border-0 shadow-sm h-100"><div class="card-body"><h4><i class="fas fa-book me-2"></i>Catálogo</h4><p class="text-muted mb-0">Consulta libros disponibles de la Biblioteca UPH.</p></div></div></a></div>
<div class="col-12 col-md-6"><a href="/biblioteca/usuario/mis_prestamos.php" class="text-decoration-none"><div class="card border-0 shadow-sm h-100"><div class="card-body"><h4><i class="fas fa-book-reader me-2"></i>Mis préstamos</h4><p class="text-muted mb-0">Consulta fechas de devolución y estado.</p></div></div></a></div>
</div></div>
<?php include '../includes/footer.php'; ?>
