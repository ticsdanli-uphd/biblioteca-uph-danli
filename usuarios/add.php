<?php
require_once '../includes/session.php';
require_once '../config/db.php';
require_once '../config/app.php';
require_once '../includes/permisos.php';
requiere_admin();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $nombre = trim($_POST['nombre'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $tipo = $_POST['tipo'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    $carrera_id = !empty($_POST['carrera_id']) ? (int)$_POST['carrera_id'] : null;

    if ($username === '' || $nombre === '' || $tipo === '' || $password === '') {
        $error = 'Complete todos los campos obligatorios.';
    } elseif (!in_array($tipo, ['admin','docente','alumno'], true)) {
        $error = 'Seleccione un tipo de usuario válido.';
    } elseif ($password !== $confirm) {
        $error = 'Las contraseñas no coinciden.';
    } elseif (strlen($password) < 6) {
        $error = 'La contraseña debe tener al menos 6 caracteres.';
    } elseif ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'El correo electrónico no es válido.';
    } else {
        $stmt = $conn->prepare("SELECT id FROM usuarios WHERE username = ? LIMIT 1");
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $exists = $stmt->get_result()->num_rows > 0;
        $stmt->close();

        if ($exists) {
            $error = 'El usuario/correo ya está registrado.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $conn->begin_transaction();
            try {
                $stmt = $conn->prepare("INSERT INTO usuarios (username,nombre,password,role,sede_id,activo) VALUES (?,?,?,?,?,1)");
                $sede_id = DANLI_SEDE_ID;
                $stmt->bind_param('ssssi', $username, $nombre, $hash, $tipo, $sede_id);
                if (!$stmt->execute()) throw new Exception($stmt->error);
                $usuario_id = $stmt->insert_id;
                $stmt->close();

                $correo = $email !== '' ? $email : $username;

                if ($tipo === 'alumno') {
                    $stmt = $conn->prepare("INSERT INTO alumnos (usuario_id,nombre,telefono,email,carrera_id,sede_id) VALUES (?,?,?,?,?,?)");
                    $sede_id = DANLI_SEDE_ID;
                    $stmt->bind_param('isssii', $usuario_id, $nombre, $telefono, $correo, $carrera_id, $sede_id);
                    if (!$stmt->execute()) throw new Exception($stmt->error);
                    $stmt->close();
                } elseif ($tipo === 'docente') {
                    $stmt = $conn->prepare("INSERT INTO docentes (usuario_id,nombre,telefono,email,carrera_id,sede_id) VALUES (?,?,?,?,?,?)");
                    $sede_id = DANLI_SEDE_ID;
                    $stmt->bind_param('isssii', $usuario_id, $nombre, $telefono, $correo, $carrera_id, $sede_id);
                    if (!$stmt->execute()) throw new Exception($stmt->error);
                    $stmt->close();
                }

                $conn->commit();
                $success = 'Usuario creado correctamente en la sede Danlí.';
                $_POST = [];
            } catch (Throwable $e) {
                $conn->rollback();
                $error = 'No se pudo crear el usuario: ' . $e->getMessage();
            }
        }
    }
}

$carreras = $conn->query("SELECT id,nombre FROM carreras ORDER BY nombre");
include '../includes/header.php';
?>
<div class="container-fluid py-4">
<div class="row justify-content-center">
<div class="col-xl-8 col-lg-9">
<div class="card shadow-sm border-0">
<div class="card-header bg-primary text-white"><h4 class="mb-0">Crear usuario</h4><small>Sede Danlí</small></div>
<div class="card-body p-4">
<?php if($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<?php if($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
<form method="post">
<div class="row g-3">
<div class="col-md-6"><label class="form-label">Usuario / correo *</label><input name="username" class="form-control" required value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"></div>
<div class="col-md-6"><label class="form-label">Nombre completo *</label><input name="nombre" class="form-control" required value="<?= htmlspecialchars($_POST['nombre'] ?? '') ?>"></div>
<div class="col-md-6"><label class="form-label">Correo electrónico</label><input type="email" name="email" class="form-control" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"></div>
<div class="col-md-6"><label class="form-label">Teléfono</label><input name="telefono" class="form-control" value="<?= htmlspecialchars($_POST['telefono'] ?? '') ?>"></div>
<div class="col-md-6"><label class="form-label">Tipo de usuario *</label>
<select name="tipo" id="tipo" class="form-select" required>
<option value="">Seleccione...</option>
<option value="admin">Administrador</option>
<option value="docente">Docente</option>
<option value="alumno">Alumno</option>
</select></div>
<div class="col-md-6"><label class="form-label">Carrera</label>
<select name="carrera_id" class="form-select"><option value="">Seleccione...</option>
<?php if($carreras): while($c=$carreras->fetch_assoc()): ?><option value="<?= (int)$c['id'] ?>"><?= htmlspecialchars($c['nombre']) ?></option><?php endwhile; endif; ?>
</select></div>
<div class="col-md-6"><label class="form-label">Contraseña *</label><input type="password" name="password" class="form-control" minlength="6" required></div>
<div class="col-md-6"><label class="form-label">Confirmar contraseña *</label><input type="password" name="confirm_password" class="form-control" minlength="6" required></div>
<div class="col-12"><div class="alert alert-info mb-0"><i class="fas fa-info-circle me-2"></i>El usuario se crea manualmente y queda asignado exclusivamente a Danlí.</div></div>
</div>
<div class="d-flex flex-wrap gap-2 mt-4">
<a href="list.php" class="btn btn-secondary">Cancelar</a>
<button class="btn btn-primary"><i class="fas fa-save me-1"></i>Crear usuario</button>
</div>
</form>
</div></div></div></div></div>
<?php include '../includes/footer.php'; ?>
