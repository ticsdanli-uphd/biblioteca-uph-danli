<?php
// ============================================================
// usuarios/add.php
// CREAR USUARIO - BIBLIOTECA UPH
// Sede fija: Danlí (4)
// ============================================================

require_once '../includes/session.php';
require_once '../config/db.php';
require_once '../config/app.php';
require_once '../includes/permisos.php';

requiere_admin();

$error = '';
$success = '';

$sede_id = 4;

// ============================================================
// PROCESAR FORMULARIO
// ============================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = trim($_POST['username'] ?? '');
    $nombre = trim($_POST['nombre'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $tipo = strtolower(trim($_POST['tipo'] ?? ''));
    $carrera_id = !empty($_POST['carrera_id'])
        ? (int)$_POST['carrera_id']
        : null;

    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // ========================================================
    // VALIDACIONES
    // ========================================================

    if ($username === '') {

        $error = 'Debe ingresar el usuario o correo.';

    } elseif ($nombre === '') {

        $error = 'Debe ingresar el nombre completo.';

    } elseif (!in_array($tipo, ['admin', 'docente', 'alumno'], true)) {

        $error = 'Debe seleccionar un tipo de usuario válido.';

    } elseif ($password === '') {

        $error = 'Debe ingresar una contraseña.';

    } elseif (strlen($password) < 6) {

        $error = 'La contraseña debe tener al menos 6 caracteres.';

    } elseif ($password !== $confirm_password) {

        $error = 'Las contraseñas no coinciden.';

    } elseif ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error = 'El correo electrónico no es válido.';

    }

    // ========================================================
    // VALIDACIÓN DE CARRERA
    // SOLO ALUMNOS PUEDEN TENER CARRERA
    // ========================================================

    if ($error === '') {

        if ($tipo !== 'alumno') {
            $carrera_id = null;
        }

        if ($tipo === 'alumno' && $carrera_id !== null) {

            $stmt = $conn->prepare("
                SELECT id
                FROM carreras
                WHERE id = ?
                LIMIT 1
            ");

            $stmt->bind_param('i', $carrera_id);
            $stmt->execute();

            $carreraExiste = $stmt->get_result()->num_rows > 0;

            $stmt->close();

            if (!$carreraExiste) {
                $error = 'La carrera seleccionada no existe.';
            }
        }
    }

    // ========================================================
    // COMPROBAR USUARIO EXISTENTE
    // ========================================================

    if ($error === '') {

        $stmt = $conn->prepare("
            SELECT id, username
            FROM usuarios
            WHERE username = ?
            LIMIT 1
        ");

        $stmt->bind_param('s', $username);
        $stmt->execute();

        $existente = $stmt->get_result()->fetch_assoc();

        $stmt->close();

        if ($existente) {

            $error =
                'El usuario "' .
                htmlspecialchars($username) .
                '" ya está registrado. Utilice otro usuario.';

        }
    }

    // ========================================================
    // CREAR USUARIO
    // ========================================================

    if ($error === '') {

        $conn->begin_transaction();

        try {

            $hash = password_hash(
                $password,
                PASSWORD_DEFAULT
            );

            // ------------------------------------------------
            // CREAR USUARIO PRINCIPAL
            // ------------------------------------------------

            $stmt = $conn->prepare("
                INSERT INTO usuarios
                (
                    username,
                    nombre,
                    email,
                    telefono,
                    password,
                    role,
                    sede_id,
                    activo
                )
                VALUES
                (
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    1
                )
            ");

            $stmt->bind_param(
                'ssssssi',
                $username,
                $nombre,
                $email,
                $telefono,
                $hash,
                $tipo,
                $sede_id
            );

            if (!$stmt->execute()) {
                throw new Exception(
                    'No se pudo crear el usuario: ' .
                    $stmt->error
                );
            }

            $usuario_id = $stmt->insert_id;

            $stmt->close();

            // ------------------------------------------------
            // ALUMNO
            // ------------------------------------------------

            if ($tipo === 'alumno') {

                if ($carrera_id !== null) {

                    $stmt = $conn->prepare("
                        INSERT INTO alumnos
                        (
                            usuario_id,
                            nombre,
                            telefono,
                            email,
                            carrera_id,
                            sede_id,
                            activo
                        )
                        VALUES
                        (
                            ?,
                            ?,
                            ?,
                            ?,
                            ?,
                            ?,
                            1
                        )
                    ");

                    $stmt->bind_param(
                        'isssii',
                        $usuario_id,
                        $nombre,
                        $telefono,
                        $email,
                        $carrera_id,
                        $sede_id
                    );

                } else {

                    $stmt = $conn->prepare("
                        INSERT INTO alumnos
                        (
                            usuario_id,
                            nombre,
                            telefono,
                            email,
                            carrera_id,
                            sede_id,
                            activo
                        )
                        VALUES
                        (
                            ?,
                            ?,
                            ?,
                            ?,
                            NULL,
                            ?,
                            1
                        )
                    ");

                    $stmt->bind_param(
                        'isssi',
                        $usuario_id,
                        $nombre,
                        $telefono,
                        $email,
                        $sede_id
                    );
                }

                if (!$stmt->execute()) {

                    throw new Exception(
                        'Usuario creado, pero no se pudo vincular como alumno: ' .
                        $stmt->error
                    );
                }

                $stmt->close();
            }

            // ------------------------------------------------
            // DOCENTE
            // NO SE SOLICITA CARRERA
            // ------------------------------------------------

            elseif ($tipo === 'docente') {

                $stmt = $conn->prepare("
                    INSERT INTO docentes
                    (
                        usuario_id,
                        nombre,
                        telefono,
                        email,
                        carrera_id,
                        sede_id,
                        activo
                    )
                    VALUES
                    (
                        ?,
                        ?,
                        ?,
                        ?,
                        NULL,
                        ?,
                        1
                    )
                ");

                $stmt->bind_param(
                    'isssi',
                    $usuario_id,
                    $nombre,
                    $telefono,
                    $email,
                    $sede_id
                );

                if (!$stmt->execute()) {

                    throw new Exception(
                        'Usuario creado, pero no se pudo vincular como docente: ' .
                        $stmt->error
                    );
                }

                $stmt->close();
            }

            // ------------------------------------------------
            // ADMINISTRADOR
            // NO SE CREA EN ALUMNOS NI DOCENTES
            // ------------------------------------------------

            elseif ($tipo === 'admin') {

                // No se inserta en alumnos
                // No se inserta en docentes
            }

            // ------------------------------------------------
            // CONFIRMAR TODO
            // ------------------------------------------------

            $conn->commit();

            $_SESSION['success_msg'] =
                'Usuario "' .
                $nombre .
                '" creado correctamente y vinculado a la sede Danlí.';

            header('Location: list.php');
            exit();

        } catch (Throwable $e) {

            $conn->rollback();

            $error = $e->getMessage();
        }
    }
}

// ============================================================
// CARRERAS
// ============================================================

$carreras = [];

$result = $conn->query("
    SELECT id, nombre
    FROM carreras
    ORDER BY nombre ASC
");

if ($result) {

    while ($row = $result->fetch_assoc()) {
        $carreras[] = $row;
    }
}

// ============================================================
// HEADER
// ============================================================

include '../includes/header.php';
?>

<style>

.usuario-card {
    max-width: 950px;
    margin: 35px auto;
}

.usuario-header {
    background: linear-gradient(
        135deg,
        #315be5,
        #4169e8
    );

    color: white;
    border-radius: 18px 18px 0 0;
    padding: 25px 30px;
}

.usuario-header h2 {
    margin: 0;
    font-weight: 700;
}

.usuario-header small {
    opacity: .9;
}

.usuario-body {
    background: white;
    padding: 30px;
    border-radius: 0 0 18px 18px;
    box-shadow: 0 8px 30px rgba(0,0,0,.08);
}

.form-label {
    font-weight: 600;
    color: #172b4d;
}

.form-control,
.form-select {
    min-height: 46px;
    border-radius: 10px;
}

.info-sede {
    background: #eef4ff;
    border-left: 4px solid #315be5;
    padding: 15px;
    border-radius: 8px;
    margin-top: 20px;
}

.btn-primary {
    background: #315be5;
    border-color: #315be5;
}

.btn-primary:hover {
    background: #244bd0;
    border-color: #244bd0;
}

</style>

<div class="container-fluid">

    <div class="usuario-card">

        <div class="usuario-header">

            <h2>
                <i class="fas fa-user-plus me-2"></i>
                Crear usuario
            </h2>

            <small>
                Sede Danlí
            </small>

        </div>

        <div class="usuario-body">

            <?php if ($error !== ''): ?>

                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle me-2"></i>

                    <?= htmlspecialchars($error) ?>

                </div>

            <?php endif; ?>

            <?php if (!empty($_SESSION['success_msg'])): ?>

                <div class="alert alert-success">

                    <i class="fas fa-check-circle me-2"></i>

                    <?= htmlspecialchars($_SESSION['success_msg']) ?>

                </div>

                <?php unset($_SESSION['success_msg']); ?>

            <?php endif; ?>


            <form method="POST" autocomplete="off">

                <div class="row g-3">

                    <!-- USUARIO -->

                    <div class="col-md-6">

                        <label class="form-label">
                            Usuario / correo *
                        </label>

                        <input
                            type="text"
                            name="username"
                            class="form-control"
                            required
                            value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                        >

                    </div>


                    <!-- NOMBRE -->

                    <div class="col-md-6">

                        <label class="form-label">
                            Nombre completo *
                        </label>

                        <input
                            type="text"
                            name="nombre"
                            class="form-control"
                            required
                            value="<?= htmlspecialchars($_POST['nombre'] ?? '') ?>"
                        >

                    </div>


                    <!-- EMAIL -->

                    <div class="col-md-6">

                        <label class="form-label">
                            Correo electrónico
                        </label>

                        <input
                            type="email"
                            name="email"
                            class="form-control"
                            value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                        >

                    </div>


                    <!-- TELEFONO -->

                    <div class="col-md-6">

                        <label class="form-label">
                            Teléfono
                        </label>

                        <input
                            type="text"
                            name="telefono"
                            class="form-control"
                            value="<?= htmlspecialchars($_POST['telefono'] ?? '') ?>"
                        >

                    </div>


                    <!-- TIPO -->

                    <div class="col-md-6">

                        <label class="form-label">
                            Tipo de usuario *
                        </label>

                        <select
                            name="tipo"
                            id="tipo"
                            class="form-select"
                            required
                        >

                            <option value="">
                                Seleccione...
                            </option>

                            <option
                                value="admin"
                                <?= (($_POST['tipo'] ?? '') === 'admin') ? 'selected' : '' ?>
                            >
                                Administrador
                            </option>

                            <option
                                value="docente"
                                <?= (($_POST['tipo'] ?? '') === 'docente') ? 'selected' : '' ?>
                            >
                                Docente
                            </option>

                            <option
                                value="alumno"
                                <?= (($_POST['tipo'] ?? '') === 'alumno') ? 'selected' : '' ?>
                            >
                                Alumno
                            </option>

                        </select>

                    </div>


                    <!-- CARRERA -->

                    <div
                        class="col-md-6"
                        id="contenedorCarrera"
                    >

                        <label class="form-label">
                            Carrera
                        </label>

                        <select
                            name="carrera_id"
                            id="carrera_id"
                            class="form-select"
                        >

                            <option value="">
                                Seleccione una carrera
                            </option>

                            <?php foreach ($carreras as $carrera): ?>

                                <option
                                    value="<?= (int)$carrera['id'] ?>"
                                    <?= (
                                        isset($_POST['carrera_id']) &&
                                        (int)$_POST['carrera_id'] === (int)$carrera['id']
                                    ) ? 'selected' : '' ?>
                                >

                                    <?= htmlspecialchars($carrera['nombre']) ?>

                                </option>

                            <?php endforeach; ?>

                        </select>

                        <small class="text-muted">
                            La carrera solamente aplica para alumnos.
                        </small>

                    </div>


                    <!-- CONTRASEÑA -->

                    <div class="col-md-6">

                        <label class="form-label">
                            Contraseña *
                        </label>

                        <input
                            type="password"
                            name="password"
                            class="form-control"
                            minlength="6"
                            required
                        >

                    </div>


                    <!-- CONFIRMAR -->

                    <div class="col-md-6">

                        <label class="form-label">
                            Confirmar contraseña *
                        </label>

                        <input
                            type="password"
                            name="confirm_password"
                            class="form-control"
                            minlength="6"
                            required
                        >

                    </div>

                </div>


                <!-- SEDE -->

                <div class="info-sede">

                    <strong>
                        <i class="fas fa-map-marker-alt me-2"></i>
                        Sede Danlí
                    </strong>

                    <div class="small mt-1">
                        Este usuario será registrado exclusivamente
                        en la sede Danlí.
                    </div>

                </div>


                <!-- BOTONES -->

                <div class="d-flex gap-2 mt-4">

                    <a
                        href="list.php"
                        class="btn btn-secondary"
                    >
                        <i class="fas fa-arrow-left me-1"></i>
                        Cancelar
                    </a>

                    <button
                        type="submit"
                        class="btn btn-primary"
                    >

                        <i class="fas fa-user-plus me-1"></i>

                        Crear usuario

                    </button>

                </div>

            </form>

        </div>

    </div>

</div>


<script>

function controlarCarrera() {

    const tipo = document.getElementById('tipo');
    const contenedor = document.getElementById('contenedorCarrera');
    const carrera = document.getElementById('carrera_id');

    if (!tipo) return;

    if (tipo.value === 'alumno') {

        contenedor.style.display = 'block';
        carrera.disabled = false;

    } else {

        contenedor.style.display = 'none';
        carrera.value = '';
        carrera.disabled = true;

    }
}

document.addEventListener(
    'DOMContentLoaded',
    controlarCarrera
);

document.getElementById('tipo').addEventListener(
    'change',
    controlarCarrera
);

</script>

<?php include '../includes/footer.php'; ?>