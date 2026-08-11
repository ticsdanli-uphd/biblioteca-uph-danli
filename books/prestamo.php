<?php
// ============================================================
// books/prestamo.php
// SOLICITUD DE PRÉSTAMO
// Alumno/Docente: solicita. NO se crea el préstamo todavía.
// Administrador: puede registrar un préstamo directo.
// ============================================================

include '../includes/session.php';
include '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

$user_id = (int)$_SESSION['user_id'];

$rol = strtolower(trim(
    $_SESSION['role']
    ?? $_SESSION['rol']
    ?? $_SESSION['tipo_usuario']
    ?? $_SESSION['user_role']
    ?? ''
));

if (in_array($rol, ['student','estudiante'], true)) $rol = 'alumno';
if (in_array($rol, ['teacher','profesor'], true)) $rol = 'docente';

$bibliografia_id = (int)($_GET['id'] ?? 0);
if ($bibliografia_id <= 0) {
    header('Location: list.php');
    exit();
}

$stmt = $conn->prepare("
    SELECT id,nombre,codigo,cantidad,sede_id,ubicacion
    FROM bibliografia
    WHERE id = ?
    LIMIT 1
");
$stmt->bind_param('i',$bibliografia_id);
$stmt->execute();
$libro = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$libro) {
    $_SESSION['error_msg'] = 'El libro no existe.';
    header('Location: list.php');
    exit();
}

$prestamos = 0;
$stmt = $conn->prepare("
    SELECT COUNT(*) total
    FROM registro_visitas
    WHERE bibliografia_id = ?
      AND tipo = 'prestamo'
      AND devuelto = 0
");
$stmt->bind_param('i',$bibliografia_id);
$stmt->execute();
$prestamos = (int)($stmt->get_result()->fetch_assoc()['total'] ?? 0);
$stmt->close();

$disponibles = max(0, (int)$libro['cantidad'] - $prestamos);
$error = '';

$alumno_id = 0;
$nombre = '';
$carrera_id = 0;

if ($rol === 'alumno') {
    $alumno_id = (int)($_SESSION['alumno_id'] ?? $_SESSION['student_id'] ?? $_SESSION['id_alumno'] ?? 0);

    if ($alumno_id > 0) {
        $stmt = $conn->prepare("SELECT id,nombre,carrera_id FROM alumnos WHERE id=? LIMIT 1");
        $stmt->bind_param('i',$alumno_id);
        $stmt->execute();
        $alumno = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($alumno) {
            $nombre = $alumno['nombre'];
            $carrera_id = (int)($alumno['carrera_id'] ?? 0);
        } else {
            $error = 'Su usuario no está vinculado a un alumno.';
        }
    } else {
        $error = 'Su usuario no tiene un alumno vinculado.';
    }
} elseif ($rol === 'docente') {
    $nombre = $_SESSION['nombre'] ?? '';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$error) {
    $observaciones = trim($_POST['observaciones'] ?? '');

    if ($disponibles <= 0) {
        $error = 'El libro no tiene ejemplares disponibles.';
    } elseif ($rol === 'alumno' && $alumno_id <= 0) {
        $error = 'No se pudo identificar al alumno.';
    } elseif ($rol === 'docente' && $nombre === '') {
        $error = 'No se pudo identificar al docente.';
    } elseif (!in_array($rol,['alumno','docente','admin','administrador'],true)) {
        $error = 'Su usuario no tiene permiso para solicitar préstamos.';
    } else {
        try {
            if (in_array($rol,['admin','administrador'],true)) {
                // Administración directa: conserva el flujo administrativo.
                $alumno_id_post = (int)($_POST['alumno_id'] ?? 0);
                if ($alumno_id_post <= 0) throw new Exception('Seleccione un alumno.');

                $stmt = $conn->prepare("SELECT id,nombre,carrera_id FROM alumnos WHERE id=? LIMIT 1");
                $stmt->bind_param('i',$alumno_id_post);
                $stmt->execute();
                $a = $stmt->get_result()->fetch_assoc();
                $stmt->close();
                if (!$a) throw new Exception('El alumno no existe.');

                $fecha = date('Y-m-d',strtotime('+3 days'));
                $conn->begin_transaction();

                $stmt = $conn->prepare("
                    INSERT INTO registro_visitas
                    (bibliografia_id,user_id,tipo,observaciones,nombre_alumno,institucion_id,carrera_id,es_externo,fecha_devolucion_esperada,devuelto)
                    VALUES (?, ?, 'prestamo', ?, ?, NULL, ?, 0, ?, 0)
                ");
                $stmt->bind_param('iissis',$bibliografia_id,$user_id,$observaciones,$a['nombre'],$a['carrera_id'],$fecha);
                if (!$stmt->execute()) throw new Exception($stmt->error);
                $stmt->close();

                $stmt = $conn->prepare("UPDATE bibliografia SET estado='Prestado' WHERE id=?");
                $stmt->bind_param('i',$bibliografia_id);
                $stmt->execute();
                $stmt->close();

                $conn->commit();
                $_SESSION['success_msg'] = 'Préstamo registrado correctamente.';
                header('Location: ../alertas.php');
                exit();
            }

            // Alumno/docente: SOLO CREA SOLICITUD.
            $sede_id = (int)($libro['sede_id'] ?? 4);
            $stmt = $conn->prepare("
                SELECT id FROM solicitudes_prestamo
                WHERE usuario_id = ?
                  AND bibliografia_id = ?
                  AND estado IN ('pendiente','aprobada')
                LIMIT 1
            ");
            $stmt->bind_param('ii',$user_id,$bibliografia_id);
            $stmt->execute();
            $ya = $stmt->get_result()->num_rows > 0;
            $stmt->close();

            if ($ya) {
                throw new Exception('Ya existe una solicitud pendiente para este libro.');
            }

            $stmt = $conn->prepare("
                INSERT INTO solicitudes_prestamo
                (bibliografia_id,usuario_id,alumno_id,nombre_solicitante,carrera_id,sede_id,observaciones)
                VALUES (?,?,?,?,?,?,?)
            ");
            $alumno_id_db = $rol === 'alumno' ? $alumno_id : null;
            $stmt->bind_param(
                'iiisiis',
                $bibliografia_id,
                $user_id,
                $alumno_id_db,
                $nombre,
                $carrera_id,
                $sede_id,
                $observaciones
            );
            if (!$stmt->execute()) throw new Exception($stmt->error);
            $stmt->close();

            $_SESSION['success_msg'] =
                'Solicitud enviada. La Biblioteca debe aprobarla antes de entregarte el libro.';
            header('Location: ../usuario/mis_prestamos.php');
            exit();

        } catch (Throwable $e) {
            if ($conn->errno) { @ $conn->rollback(); }
            $error = 'No se pudo procesar la solicitud: '.$e->getMessage();
        }
    }
}

$carreras = $conn->query("SELECT id,nombre FROM carreras ORDER BY nombre");
$alumnos = null;
if (in_array($rol,['admin','administrador'],true)) {
    $alumnos = $conn->query("SELECT id,nombre,carrera_id FROM alumnos ORDER BY nombre");
}

include '../includes/header.php';
?>
<style>
.prestamo-container{max-width:1050px;margin:30px auto}
.prestamo-card{background:#fff;border-radius:18px;box-shadow:0 8px 30px rgba(0,0,0,.08);overflow:hidden}
.prestamo-header{background:linear-gradient(135deg,#3159d9,#436ff0);color:#fff;padding:22px 25px}
.prestamo-header h2{margin:0;color:#fff!important;font-weight:700}
.prestamo-body{padding:28px}.libro-info{background:#f5f7fb;border-radius:14px;padding:18px;margin-bottom:22px}
.libro-titulo{font-size:24px;font-weight:700;color:#263238}.libro-datos{color:#64748b;margin-top:5px}
.info{background:#e8f7ef;border-left:4px solid #198754;border-radius:10px;padding:14px 16px;margin-bottom:20px}
.recogida{background:#eef4ff;border:1px solid #d8e4ff;border-radius:12px;padding:15px;margin-bottom:20px}
.form-label{font-weight:600;color:#263238}.form-control,.form-select{min-height:48px;border-radius:10px}
@media(max-width:576px){.prestamo-body{padding:18px}.libro-titulo{font-size:20px}}
</style>

<div class="container-fluid">
<div class="prestamo-container">
<div class="prestamo-card">
<div class="prestamo-header">
<h2><i class="fas fa-book-reader me-2"></i>
<?= in_array($rol,['admin','administrador'],true) ? 'Registrar Préstamo' : 'Solicitar Préstamo' ?>
</h2>
</div>
<div class="prestamo-body">

<?php if($error): ?>
<div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="libro-info">
<div class="libro-titulo"><?= htmlspecialchars($libro['nombre']) ?></div>
<div class="libro-datos">
<strong>Código:</strong> <?= htmlspecialchars($libro['codigo']) ?>
&nbsp; | &nbsp; <strong>Ejemplares:</strong> <?= (int)$libro['cantidad'] ?>
&nbsp; | &nbsp; <strong>Disponibles:</strong> <?= $disponibles ?>
</div>
</div>

<?php if($disponibles <= 0): ?>
<div class="alert alert-warning">
<i class="fas fa-exclamation-triangle me-2"></i>
<strong>Sin disponibilidad.</strong> No hay ejemplares disponibles actualmente.
</div>
<?php else: ?>

<?php if(!in_array($rol,['admin','administrador'],true)): ?>
<div class="info">
<i class="fas fa-info-circle me-2"></i>
<strong>Importante:</strong> al enviar esta solicitud todavía <strong>no se realiza el préstamo</strong>.
La Biblioteca revisará tu solicitud y te notificará si fue aprobada.
</div>

<div class="recogida">
<i class="fas fa-map-marker-alt text-primary me-2"></i>
<strong>Ubicación para recoger:</strong>
<?= htmlspecialchars($libro['ubicacion'] ?: 'Consulta en Biblioteca UPH Danlí') ?>
</div>
<?php endif; ?>

<form method="post">
<?php if($rol === 'admin' || $rol === 'administrador'): ?>
<div class="mb-4">
<label class="form-label">Alumno *</label>
<select name="alumno_id" class="form-select" required>
<option value="">Seleccione un alumno</option>
<?php if($alumnos): while($a=$alumnos->fetch_assoc()): ?>
<option value="<?= (int)$a['id'] ?>"><?= htmlspecialchars($a['nombre']) ?></option>
<?php endwhile; endif; ?>
</select>
</div>
<?php else: ?>
<div class="mb-4">
<label class="form-label"><?= $rol==='alumno'?'Alumno':'Docente' ?></label>
<div class="form-control bg-light"><?= htmlspecialchars($nombre ?: 'Usuario actual') ?></div>
</div>
<?php endif; ?>

<div class="mb-4">
<label class="form-label">Observaciones</label>
<textarea name="observaciones" class="form-control" rows="4" placeholder="Escriba una observación (opcional)..."></textarea>
</div>

<div class="d-flex justify-content-between gap-2 flex-wrap">
<a href="view.php?id=<?= $bibliografia_id ?>" class="btn btn-secondary">
<i class="fas fa-arrow-left me-1"></i>Cancelar</a>
<button class="btn btn-primary">
<i class="fas fa-paper-plane me-1"></i>
<?= in_array($rol,['admin','administrador'],true) ? 'Registrar Préstamo' : 'Enviar Solicitud' ?>
</button>
</div>
</form>
<?php endif; ?>
</div></div></div></div>

<?php include '../includes/footer.php'; ?>
