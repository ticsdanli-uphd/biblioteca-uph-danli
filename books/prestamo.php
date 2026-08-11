<?php
// ============================================================
// books/prestamo.php
// SOLICITAR PRÉSTAMO - SOLO ALUMNOS Y DOCENTES
// El préstamo NO se registra hasta que el administrador lo apruebe
// y posteriormente marque el libro como entregado.
// ============================================================

require_once '../includes/session.php';
require_once '../config/db.php';
require_once '../config/app.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

$user_id = (int) $_SESSION['user_id'];
$sede_id = defined('DANLI_SEDE_ID') ? (int) DANLI_SEDE_ID : 4;

// ------------------------------------------------------------
// Identificar usuario real desde la base
// ------------------------------------------------------------
$stmt = $conn->prepare("
    SELECT id, username, nombre, role, sede_id, activo
    FROM usuarios
    WHERE id = ?
    LIMIT 1
");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$usuarioActual = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$usuarioActual || (int)$usuarioActual['activo'] !== 1) {
    $_SESSION['error_msg'] = 'La cuenta no está disponible.';
    header('Location: ../login.php');
    exit();
}

$rol = strtolower(trim((string)$usuarioActual['role']));

if (in_array($rol, ['admin', 'administrador', 'administrator'], true)) {
    header('Location: ../admin/solicitudes_prestamo.php');
    exit();
}

if (in_array($rol, ['usuario', 'estudiante', 'student'], true)) {
    $rol = 'alumno';
}

if (in_array($rol, ['profesor', 'teacher'], true)) {
    $rol = 'docente';
}

if (!in_array($rol, ['alumno', 'docente'], true)) {
    header('Location: ../dashboard.php');
    exit();
}

// ------------------------------------------------------------
// Libro
// ------------------------------------------------------------
$bibliografia_id = (int)($_GET['id'] ?? 0);

if ($bibliografia_id <= 0) {
    header('Location: list.php');
    exit();
}

$stmt = $conn->prepare("
    SELECT id, nombre, codigo, cantidad, sede_id, ubicacion, estado
    FROM bibliografia
    WHERE id = ?
      AND sede_id = ?
    LIMIT 1
");
$stmt->bind_param('ii', $bibliografia_id, $sede_id);
$stmt->execute();
$libro = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$libro) {
    $_SESSION['error_msg'] = 'El libro no existe en la sede Danlí.';
    header('Location: list.php');
    exit();
}

// ------------------------------------------------------------
// Préstamos activos
// ------------------------------------------------------------
$stmt = $conn->prepare("
    SELECT COUNT(*) AS total
    FROM registro_visitas
    WHERE bibliografia_id = ?
      AND tipo = 'prestamo'
      AND devuelto = 0
");
$stmt->bind_param('i', $bibliografia_id);
$stmt->execute();
$prestamosActivos = (int)($stmt->get_result()->fetch_assoc()['total'] ?? 0);
$stmt->close();

$disponibles = max(0, (int)$libro['cantidad'] - $prestamosActivos);

$error = '';
$nombre = trim((string)($usuarioActual['nombre'] ?? ''));
$carrera_id = null;
$alumno_id = null;

// ------------------------------------------------------------
// Vinculación del alumno
// ------------------------------------------------------------
if ($rol === 'alumno') {
    $stmt = $conn->prepare("
        SELECT id, nombre, carrera_id
        FROM alumnos
        WHERE usuario_id = ?
          AND sede_id = ?
          AND activo = 1
        LIMIT 1
    ");
    $stmt->bind_param('ii', $user_id, $sede_id);
    $stmt->execute();
    $alumno = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$alumno) {
        $error = 'Tu usuario está registrado como alumno, pero no tiene un registro de alumno vinculado. Solicita al administrador que complete la vinculación desde Gestión de Usuarios.';
    } else {
        $alumno_id = (int)$alumno['id'];
        $nombre = trim((string)$alumno['nombre']) !== ''
            ? $alumno['nombre']
            : $nombre;
        $carrera_id = !empty($alumno['carrera_id'])
            ? (int)$alumno['carrera_id']
            : null;
    }
}

// ------------------------------------------------------------
// Docente
// ------------------------------------------------------------
if ($rol === 'docente' && $nombre === '') {
    $error = 'No se pudo identificar el nombre del docente.';
}

// ------------------------------------------------------------
// Enviar solicitud
// ------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $error === '') {
    $observaciones = trim($_POST['observaciones'] ?? '');

    if ($disponibles <= 0) {
        $error = 'El libro no tiene ejemplares disponibles.';
    } else {
        try {
            $conn->begin_transaction();

            // Evitar solicitudes repetidas pendientes/aprobadas.
            $stmt = $conn->prepare("
                SELECT id
                FROM solicitudes_prestamo
                WHERE user_id = ?
                  AND bibliografia_id = ?
                  AND estado IN ('pendiente', 'aprobada')
                LIMIT 1
                FOR UPDATE
            ");
            $stmt->bind_param('ii', $user_id, $bibliografia_id);
            $stmt->execute();
            $yaExiste = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($yaExiste) {
                throw new Exception('Ya tienes una solicitud pendiente o aprobada para este libro.');
            }

            // Solo alumnos llevan carrera; docentes quedan con NULL.
            $carreraSolicitud = ($rol === 'alumno' && $carrera_id)
                ? $carrera_id
                : null;

            $stmt = $conn->prepare("
                INSERT INTO solicitudes_prestamo
                (
                    bibliografia_id,
                    user_id,
                    nombre_solicitante,
                    carrera_id,
                    sede_id,
                    estado,
                    observaciones
                )
                VALUES (?, ?, ?, ?, ?, 'pendiente', ?)
            ");

            $stmt->bind_param(
                'iisiis',
                $bibliografia_id,
                $user_id,
                $nombre,
                $carreraSolicitud,
                $sede_id,
                $observaciones
            );

            if (!$stmt->execute()) {
                throw new Exception($stmt->error);
            }

            $stmt->close();
            $conn->commit();

            $_SESSION['success_msg'] =
                'Solicitud enviada correctamente. La Biblioteca la revisará antes de realizar el préstamo.';

            header('Location: ../usuario/mis_prestamos.php');
            exit();

        } catch (Throwable $e) {
            try {
                $conn->rollback();
            } catch (Throwable $ignored) {
            }

            $error = 'No se pudo procesar la solicitud: ' . $e->getMessage();
        }
    }
}

include '../includes/header.php';
?>
<style>
.prestamo-container{max-width:1050px;margin:30px auto}
.prestamo-card{background:#fff;border-radius:18px;box-shadow:0 8px 30px rgba(0,0,0,.08);overflow:hidden}
.prestamo-header{background:linear-gradient(135deg,#3159d9,#436ff0);color:#fff;padding:22px 25px}
.prestamo-header h2{margin:0;color:#fff!important;font-weight:700}
.prestamo-body{padding:28px}
.libro-info{background:#f5f7fb;border-radius:14px;padding:18px;margin-bottom:22px}
.libro-titulo{font-size:24px;font-weight:700;color:#263238}
.libro-datos{color:#64748b;margin-top:5px}
.info{background:#e8f7ef;border-left:4px solid #198754;border-radius:10px;padding:14px 16px;margin-bottom:20px}
.recogida{background:#eef4ff;border:1px solid #d8e4ff;border-radius:12px;padding:15px;margin-bottom:20px}
.form-label{font-weight:600;color:#263238}
.form-control{min-height:48px;border-radius:10px}
@media(max-width:576px){.prestamo-body{padding:18px}.libro-titulo{font-size:20px}}
</style>

<div class="container-fluid">
    <div class="prestamo-container">
        <div class="prestamo-card">

            <div class="prestamo-header">
                <h2>
                    <i class="fas fa-book-reader me-2"></i>
                    Solicitar Préstamo
                </h2>
            </div>

            <div class="prestamo-body">

                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <div class="libro-info">
                    <div class="libro-titulo">
                        <?= htmlspecialchars($libro['nombre']) ?>
                    </div>

                    <div class="libro-datos">
                        <strong>Código:</strong>
                        <?= htmlspecialchars($libro['codigo']) ?>
                        &nbsp; | &nbsp;
                        <strong>Ejemplares:</strong>
                        <?= (int)$libro['cantidad'] ?>
                        &nbsp; | &nbsp;
                        <strong>Disponibles:</strong>
                        <?= $disponibles ?>
                    </div>
                </div>

                <?php if ($disponibles <= 0): ?>

                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Sin disponibilidad.</strong>
                        No hay ejemplares disponibles actualmente.
                    </div>

                <?php else: ?>

                    <div class="info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Importante:</strong>
                        al enviar esta solicitud todavía
                        <strong>no se realiza el préstamo</strong>.
                        La Biblioteca revisará tu solicitud y te notificará
                        cuando sea aprobada.
                    </div>

                    <div class="recogida">
                        <i class="fas fa-map-marker-alt text-primary me-2"></i>
                        <strong>Ubicación para recoger:</strong>
                        <?= htmlspecialchars($libro['ubicacion'] ?: 'Biblioteca UPH Danlí') ?>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">
                            <?= $rol === 'alumno' ? 'Alumno' : 'Docente' ?>
                        </label>

                        <div class="form-control bg-light">
                            <?= htmlspecialchars($nombre) ?>
                        </div>

                        <small class="text-muted">
                            Usuario identificado automáticamente por tu sesión.
                        </small>
                    </div>

                    <?php if ($rol === 'alumno' && $carrera_id): ?>
                        <?php
                        $stmtCarrera = $conn->prepare(
                            "SELECT nombre FROM carreras WHERE id = ? LIMIT 1"
                        );
                        $stmtCarrera->bind_param('i', $carrera_id);
                        $stmtCarrera->execute();
                        $carreraNombre = $stmtCarrera->get_result()->fetch_assoc()['nombre'] ?? '';
                        $stmtCarrera->close();
                        ?>
                        <?php if ($carreraNombre): ?>
                            <div class="mb-4">
                                <label class="form-label">Carrera</label>
                                <div class="form-control bg-light">
                                    <?= htmlspecialchars($carreraNombre) ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>

                    <form method="post">
                        <div class="mb-4">
                            <label class="form-label">Observaciones</label>
                            <textarea
                                name="observaciones"
                                class="form-control"
                                rows="4"
                                placeholder="Escriba una observación (opcional)..."
                            ></textarea>
                        </div>

                        <div class="d-flex justify-content-between gap-2 flex-wrap">
                            <a
                                href="view.php?id=<?= $bibliografia_id ?>"
                                class="btn btn-secondary"
                            >
                                <i class="fas fa-arrow-left me-1"></i>
                                Cancelar
                            </a>

                            <button
                                type="submit"
                                class="btn btn-primary"
                            >
                                <i class="fas fa-paper-plane me-1"></i>
                                Enviar Solicitud
                            </button>
                        </div>
                    </form>

                <?php endif; ?>

            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
