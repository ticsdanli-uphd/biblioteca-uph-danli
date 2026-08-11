<?php
require_once '../includes/session.php';
require_once '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$rol = strtolower(trim($_SESSION['role'] ?? $_SESSION['rol'] ?? ''));
if (!in_array($rol, ['admin', 'administrador'], true)) {
    header('Location: ../dashboard.php');
    exit;
}

const DANLI_SEDE_ID = 4;

$success = $_SESSION['success'] ?? '';
$error   = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);

/* Cambiar contraseña */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'change_password') {
    $user_id = (int)($_POST['user_id'] ?? 0);
    $new_password = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if ($user_id <= 0) {
        $error = 'ID de usuario no válido.';
    } elseif (strlen($new_password) < 6) {
        $error = 'La contraseña debe tener al menos 6 caracteres.';
    } elseif ($new_password !== $confirm) {
        $error = 'Las contraseñas no coinciden.';
    } else {
        $stmt = $conn->prepare("SELECT id FROM usuarios WHERE id = ? AND sede_id = ? LIMIT 1");
        $stmt->bind_param('ii', $user_id, $sede_id);
        $sede_id = DANLI_SEDE_ID;
        $stmt->execute();
        $exists = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$exists) {
            $error = 'Usuario no encontrado en la sede Danlí.';
        } else {
            $hash = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE usuarios SET password = ? WHERE id = ? AND sede_id = ?");
            $stmt->bind_param('sii', $hash, $user_id, $sede_id);
            if ($stmt->execute()) {
                $success = 'Contraseña actualizada correctamente.';
            } else {
                $error = 'No se pudo actualizar la contraseña: ' . $stmt->error;
            }
            $stmt->close();
        }
    }
}

/* Lista únicamente usuarios activos de Danlí */
$sql = "
SELECT
    u.id,
    u.username,
    u.nombre,
    u.role,
    u.sede_id,
    u.activo,
    COALESCE(a.id, d.id) AS perfil_id,
    CASE
        WHEN u.role = 'admin' THEN 'Administrador'
        WHEN u.role = 'docente' THEN 'Docente'
        WHEN u.role IN ('alumno','usuario') OR a.id IS NOT NULL THEN 'Alumno'
        ELSE 'Usuario'
    END AS tipo_usuario,
    COALESCE(NULLIF(u.nombre,''), NULLIF(a.nombre,''), NULLIF(d.nombre,''), 'Sin nombre') AS nombre_completo
FROM usuarios u
LEFT JOIN alumnos a ON a.usuario_id = u.id
LEFT JOIN docentes d ON d.usuario_id = u.id
WHERE u.sede_id = 4
ORDER BY u.nombre ASC, u.username ASC
";

$result = $conn->query($sql);
if (!$result) {
    die('Error al obtener usuarios de Danlí: ' . htmlspecialchars($conn->error));
}

include '../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="card shadow mb-4 border-0">
        <div class="card-header bg-primary text-white py-3">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-1 fw-bold text-white">
                        <i class="fas fa-users me-2"></i>Gestión de Usuarios
                    </h5>
                    <small class="text-white">
                        <i class="fas fa-map-marker-alt me-1"></i>Sede Danlí
                    </small>
                </div>
                <span class="badge bg-white text-primary">
                    <i class="fas fa-users me-1"></i>Usuarios de Danlí
                </span>
            </div>
        </div>

        <div class="card-body">
            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($success) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="fas fa-exclamation-triangle me-2"></i><?= htmlspecialchars($error) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="mb-3">
                <a href="../Crearusuario.php" class="btn btn-success">
                    <i class="fas fa-user-plus me-1"></i>Crear Nuevo Usuario
                </a>
            </div>

            <div class="table-responsive">
                <table id="tablaUsuarios" class="table table-bordered table-striped table-hover align-middle">
                    <thead class="table-primary text-center">
                        <tr>
                            <th>Usuario</th>
                            <th>Nombre</th>
                            <th>Tipo</th>
                            <th>Sede</th>
                            <th style="width:280px">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <?php
                        $tipo = $row['tipo_usuario'];
                        $badge = $tipo === 'Administrador' ? 'danger' :
                                 ($tipo === 'Docente' ? 'primary' :
                                 ($tipo === 'Alumno' ? 'info' : 'secondary'));
                        $icon = $tipo === 'Administrador' ? 'fa-user-shield' :
                                ($tipo === 'Docente' ? 'fa-chalkboard-teacher' :
                                ($tipo === 'Alumno' ? 'fa-user-graduate' : 'fa-user'));
                        ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($row['username']) ?></strong></td>
                            <td><?= htmlspecialchars($row['nombre_completo']) ?></td>
                            <td class="text-center">
                                <span class="badge bg-<?= $badge ?>">
                                    <i class="fas <?= $icon ?> me-1"></i><?= htmlspecialchars($tipo) ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-success">
                                    <i class="fas fa-map-marker-alt me-1"></i>Danlí
                                </span>
                            </td>
                            <td class="text-center">
                                <div class="btn-group btn-group-sm">
                                    <a href="edit.php?id=<?= (int)$row['id'] ?>"
                                       class="btn btn-outline-primary" title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </a>

                                    <button type="button" class="btn btn-outline-warning"
                                            onclick="mostrarCambioPassword(<?= (int)$row['id'] ?>, <?= htmlspecialchars(json_encode($row['username']), ENT_QUOTES, 'UTF-8') ?>)"
                                            title="Cambiar contraseña">
                                        <i class="fas fa-key"></i>
                                    </button>

                                    <?php if ((int)$row['id'] !== (int)$_SESSION['user_id']): ?>
                                        <button type="button" class="btn btn-outline-danger"
                                                onclick="confirmarEliminarUsuario(<?= (int)$row['id'] ?>, <?= htmlspecialchars(json_encode($row['username']), ENT_QUOTES, 'UTF-8') ?>)"
                                                title="Desactivar">
                                            <i class="fas fa-user-slash"></i>
                                        </button>
                                    <?php else: ?>
                                        <button type="button" class="btn btn-outline-secondary" disabled title="No puedes desactivar tu propio usuario">
                                            <i class="fas fa-user-slash"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="cambioPasswordModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-key me-2"></i>Cambiar Contraseña</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="change_password">
                    <input type="hidden" name="user_id" id="user_id">

                    <div class="alert alert-info">
                        Cambiando contraseña para <strong id="username_display"></strong>
                    </div>

                    <label class="form-label">Nueva contraseña</label>
                    <input type="password" class="form-control mb-3" name="new_password" id="new_password" minlength="6" required>

                    <label class="form-label">Confirmar contraseña</label>
                    <input type="password" class="form-control" name="confirm_password" id="confirm_password" minlength="6" required>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.1/css/dataTables.bootstrap5.min.css">
<script src="https://cdn.datatables.net/1.13.1/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.1/js/dataTables.bootstrap5.min.js"></script>

<script>
$(function () {
    $('#tablaUsuarios').DataTable({
        language: { url: '//cdn.datatables.net/plug-ins/1.13.1/i18n/es-ES.json' },
        responsive: true,
        pageLength: 10,
        order: [[1, 'asc']],
        columnDefs: [{ orderable: false, targets: 4 }],
        lengthMenu: [[10,25,50,-1],[10,25,50,'Todos']]
    });
});

function mostrarCambioPassword(id, username) {
    document.getElementById('user_id').value = id;
    document.getElementById('username_display').textContent = username;
    document.getElementById('new_password').value = '';
    document.getElementById('confirm_password').value = '';
    new bootstrap.Modal(document.getElementById('cambioPasswordModal')).show();
}

function confirmarEliminarUsuario(id, username) {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: '¿Desactivar usuario?',
            text: 'El usuario "' + username + '" dejará de poder iniciar sesión.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, desactivar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#d33'
        }).then(r => {
            if (r.isConfirmed) window.location.href = 'delete.php?id=' + encodeURIComponent(id);
        });
    } else if (confirm('¿Desactivar al usuario "' + username + '"?')) {
        window.location.href = 'delete.php?id=' + encodeURIComponent(id);
    }
}
</script>

<style>
.card-header.bg-primary,
.card-header.bg-primary h5,
.card-header.bg-primary small,
.card-header.bg-primary i { color:#fff !important; }
.card-header.bg-primary { background-color:#0d6efd !important; }
#tablaUsuarios th { font-weight:600; vertical-align:middle; }
#tablaUsuarios td { vertical-align:middle; }
</style>

<?php include '../includes/footer.php'; ?>
