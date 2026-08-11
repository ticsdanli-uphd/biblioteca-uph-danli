<?php

require_once '../includes/session.php';
require_once '../config/db.php';
require_once '../config/app.php';
require_once '../includes/permisos.php';

requiere_admin();

$error = '';
$success = '';


// ============================================================
// PROCESAR FORMULARIO
// ============================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = trim($_POST['username'] ?? '');
    $nombre = trim($_POST['nombre'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $tipo = strtolower(trim($_POST['tipo'] ?? ''));

    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    $carrera_id = !empty($_POST['carrera_id'])
        ? (int) $_POST['carrera_id']
        : null;

// La carrera pertenece únicamente al perfil de alumno.
if ($tipo === 'docente' || $tipo === 'admin') {
    $carrera_id = null;
}


    // ========================================================
    // VALIDACIONES
    // ========================================================

    if (
        $username === '' ||
        $nombre === '' ||
        $tipo === '' ||
        $password === ''
    ) {

        $error = 'Complete todos los campos obligatorios.';

    } elseif (
        !in_array(
            $tipo,
            ['admin', 'docente', 'alumno'],
            true
        )
    ) {

        $error = 'Seleccione un tipo de usuario válido.';

    } elseif (
        $password !== $confirm
    ) {

        $error = 'Las contraseñas no coinciden.';

    } elseif (
        strlen($password) < 6
    ) {

        $error =
            'La contraseña debe tener al menos 6 caracteres.';

    } elseif (
        $email !== '' &&
        !filter_var(
            $email,
            FILTER_VALIDATE_EMAIL
        )
    ) {

        $error =
            'El correo electrónico no es válido.';

    } elseif (
        $tipo === 'alumno' &&
        empty($carrera_id)
    ) {

        $error =
            'Para registrar un alumno debe seleccionar una carrera.';

    } elseif (
        in_array($tipo, ['alumno', 'docente'], true) &&
        !empty($carrera_id)
    ) {

        // Verificar que la carrera realmente exista para evitar
        // errores de clave foránea al guardar el usuario.
        $stmtCarrera = $conn->prepare("
            SELECT id
            FROM carreras
            WHERE id = ?
            LIMIT 1
        ");

        if (!$stmtCarrera) {

            $error =
                'No se pudo verificar la carrera: '
                . $conn->error;

        } else {

            $stmtCarrera->bind_param(
                'i',
                $carrera_id
            );

            $stmtCarrera->execute();

            $existeCarrera =
                $stmtCarrera->get_result()->num_rows === 1;

            $stmtCarrera->close();

            if (!$existeCarrera) {

                $error =
                    'La carrera seleccionada no existe. '
                    . 'Actualice la lista y seleccione una carrera válida.';
            }
        }

    } else {


        // ====================================================
        // NORMALIZAR CORREO
        // ====================================================

        $username = strtolower($username);

        if ($email !== '') {
            $email = strtolower($email);
        }


        // ====================================================
        // COMPROBAR USUARIO EXISTENTE
        // ====================================================

        $stmt = $conn->prepare("
            SELECT id
            FROM usuarios
            WHERE LOWER(username) = LOWER(?)
            LIMIT 1
        ");

        if (!$stmt) {

            $error =
                'Error preparando la consulta de usuario: '
                . $conn->error;

        } else {

            $stmt->bind_param(
                's',
                $username
            );

            $stmt->execute();

            $exists =
                $stmt->get_result()->num_rows > 0;

            $stmt->close();


            if ($exists) {

                $error =
                    'El usuario/correo ya está registrado.';

            } else {


                // =================================================
                // CORREO PARA TABLA ALUMNOS
                // =================================================

                $correo =
                    $email !== ''
                    ? $email
                    : $username;


                // =================================================
                // COMPROBAR CORREO EN ALUMNOS
                // =================================================

                if (
                    $tipo === 'alumno' &&
                    $correo !== ''
                ) {

                    $stmt = $conn->prepare("
                        SELECT id
                        FROM alumnos
                        WHERE LOWER(email) = LOWER(?)
                        LIMIT 1
                    ");

                    if ($stmt) {

                        $stmt->bind_param(
                            's',
                            $correo
                        );

                        $stmt->execute();

                        $correoExiste =
                            $stmt->get_result()->num_rows > 0;

                        $stmt->close();

                        if ($correoExiste) {

                            $error =
                                'Este correo ya pertenece a un alumno registrado.';
                        }
                    }
                }


                // =================================================
                // CREAR USUARIO
                // =================================================

                if ($error === '') {

                    $hash =
                        password_hash(
                            $password,
                            PASSWORD_DEFAULT
                        );

                    $conn->begin_transaction();

                    try {


                        // =========================================
                        // SEDE DANLÍ
                        // =========================================

                        $sede_id =
                            (int) DANLI_SEDE_ID;


                        // =========================================
                        // CREAR USUARIO
                        // =========================================

                        $stmt = $conn->prepare("
                            INSERT INTO usuarios
                            (
                                username,
                                nombre,
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
                                1
                            )
                        ");

                        if (!$stmt) {

                            throw new Exception(
                                'No se pudo preparar el registro del usuario: '
                                . $conn->error
                            );
                        }


                        $stmt->bind_param(
                            'ssssi',
                            $username,
                            $nombre,
                            $hash,
                            $tipo,
                            $sede_id
                        );


                        if (!$stmt->execute()) {

                            throw new Exception(
                                'No se pudo crear el usuario: '
                                . $stmt->error
                            );
                        }


                        // =========================================
                        // ID DEL USUARIO
                        // =========================================

                        $usuario_id =
                            (int) $stmt->insert_id;


                        $stmt->close();


                        // =================================================
                        // SI ES ALUMNO
                        // CREAR AUTOMÁTICAMENTE EN TABLA ALUMNOS
                        // =================================================

                        if ($tipo === 'alumno') {


                            $stmt = $conn->prepare("
                                INSERT INTO alumnos
                                (
                                    usuario_id,
                                    nombre,
                                    telefono,
                                    email,
                                    carrera_id,
                                    sede_id
                                )
                                VALUES
                                (
                                    ?,
                                    ?,
                                    ?,
                                    ?,
                                    ?,
                                    ?
                                )
                            ");


                            if (!$stmt) {

                                throw new Exception(
                                    'No se pudo preparar el registro del alumno: '
                                    . $conn->error
                                );
                            }


                            $stmt->bind_param(
                                'isssii',
                                $usuario_id,
                                $nombre,
                                $telefono,
                                $correo,
                                $carrera_id,
                                $sede_id
                            );


                            if (!$stmt->execute()) {

                                throw new Exception(
                                    'No se pudo crear el registro del alumno: '
                                    . $stmt->error
                                );
                            }


                            $alumno_id =
                                (int) $stmt->insert_id;


                            $stmt->close();


                            // =============================================
                            // CONFIRMAR VINCULACIÓN
                            // =============================================

                            $stmt = $conn->prepare("
                                SELECT
                                    a.id,
                                    a.usuario_id,
                                    a.nombre,
                                    a.carrera_id
                                FROM alumnos a
                                WHERE a.id = ?
                                  AND a.usuario_id = ?
                                LIMIT 1
                            ");


                            if (!$stmt) {

                                throw new Exception(
                                    'No se pudo comprobar la vinculación del alumno.'
                                );
                            }


                            $stmt->bind_param(
                                'ii',
                                $alumno_id,
                                $usuario_id
                            );


                            $stmt->execute();

                            $vinculado =
                                $stmt->get_result()->num_rows === 1;

                            $stmt->close();


                            if (!$vinculado) {

                                throw new Exception(
                                    'El usuario fue creado, pero no se pudo confirmar '
                                    . 'la vinculación con el alumno.'
                                );
                            }
                        }


                        // =================================================
                        // SI ES DOCENTE
                        // =================================================

                        elseif ($tipo === 'docente') {


                            $stmt = $conn->prepare("
                                INSERT INTO docentes
                                (
                                    usuario_id,
                                    nombre,
                                    telefono,
                                    email,
                                    carrera_id,
                                    sede_id
                                )
                                VALUES
                                (
                                    ?,
                                    ?,
                                    ?,
                                    ?,
                                    ?,
                                    ?
                                )
                            ");


                            if (!$stmt) {

                                throw new Exception(
                                    'No se pudo preparar el registro del docente: '
                                    . $conn->error
                                );
                            }


                            $stmt->bind_param(
                                'isssii',
                                $usuario_id,
                                $nombre,
                                $telefono,
                                $correo,
                                $carrera_id,
                                $sede_id
                            );


                            if (!$stmt->execute()) {

                                throw new Exception(
                                    'No se pudo crear el registro del docente: '
                                    . $stmt->error
                                );
                            }


                            $stmt->close();
                        }


                        // =================================================
                        // CONFIRMAR TRANSACCIÓN
                        // =================================================

                        $conn->commit();


                        // =================================================
                        // MENSAJE
                        // =================================================

                        if ($tipo === 'alumno') {

                            $success =
                                'Alumno registrado correctamente. '
                                . 'Su usuario quedó vinculado automáticamente '
                                . 'con su registro de alumno.';

                        } elseif ($tipo === 'docente') {

                            $success =
                                'Docente registrado correctamente. '
                                . 'Su usuario quedó vinculado automáticamente '
                                . 'con su registro de docente.';

                        } else {

                            $success =
                                'Administrador registrado correctamente.';
                        }


                        // Limpiar formulario
                        $_POST = [];


                    } catch (Throwable $e) {

                        $conn->rollback();

                        $error =
                            'No se pudo crear el usuario: '
                            . $e->getMessage();
                    }
                }
            }
        }
    }
}


// ============================================================
// CARRERAS
// ============================================================

$carreras = $conn->query("
    SELECT
        id,
        nombre
    FROM carreras
    ORDER BY nombre ASC
");

if (!$carreras) {
    $error = 'No se pudieron cargar las carreras: ' . $conn->error;
}


include '../includes/header.php';

?>

<style>

/* ============================================================
   CREAR USUARIO
============================================================ */

.usuario-container {
    max-width: 1000px;
    margin: 30px auto;
}

.usuario-card {
    border: 0;
    border-radius: 18px;
    overflow: hidden;
    box-shadow: 0 8px 30px rgba(0,0,0,.08);
}

.usuario-header {
    background: linear-gradient(
        135deg,
        #3159d9,
        #436ff0
    );
    color: #ffffff;
    padding: 22px 25px;
}

.usuario-header h4 {
    color: #ffffff !important;
    font-weight: 700;
    margin-bottom: 3px;
}

.usuario-header small {
    color: rgba(255,255,255,.85);
}

.form-label {
    font-weight: 600;
    color: #263238;
}

.form-control,
.form-select {
    min-height: 46px;
    border-radius: 10px;
}

.form-control:focus,
.form-select:focus {
    border-color: #3159d9;
    box-shadow:
        0 0 0 .2rem rgba(49,89,217,.15);
}

.info-danli {
    background: #eef4ff;
    border-left: 4px solid #3159d9;
    border-radius: 10px;
    padding: 14px 16px;
}

.info-alumno {
    display: none;
    background: #e8f7ef;
    border-left: 4px solid #198754;
    border-radius: 10px;
    padding: 14px 16px;
}

.info-docente {
    display: none;
    background: #fff5df;
    border-left: 4px solid #f4a000;
    border-radius: 10px;
    padding: 14px 16px;
}

@media (max-width: 576px) {

    .usuario-container {
        margin: 15px auto;
    }

    .card-body {
        padding: 18px !important;
    }

    .usuario-header {
        padding: 18px;
    }

    .usuario-header h4 {
        font-size: 21px;
    }
}

</style>


<div class="container-fluid py-4">

    <div class="usuario-container">

        <div class="card usuario-card">

            <!-- =================================================
                 ENCABEZADO
            ================================================== -->

            <div class="usuario-header">

                <h4>

                    <i class="fas fa-user-plus me-2"></i>

                    Crear usuario

                </h4>

                <small>

                    Sede Danlí

                </small>

            </div>


            <!-- =================================================
                 CUERPO
            ================================================== -->

            <div class="card-body p-4">


                <!-- ERROR -->

                <?php if ($error): ?>

                    <div class="alert alert-danger">

                        <i class="fas fa-exclamation-circle me-2"></i>

                        <?= htmlspecialchars(
                            $error,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>

                    </div>

                <?php endif; ?>


                <!-- ÉXITO -->

                <?php if ($success): ?>

                    <div class="alert alert-success">

                        <i class="fas fa-check-circle me-2"></i>

                        <?= htmlspecialchars(
                            $success,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>

                    </div>

                <?php endif; ?>


                <!-- =================================================
                     FORMULARIO
                ================================================== -->

                <form
                    method="post"
                    id="formUsuario"
                    autocomplete="off"
                >

                    <div class="row g-3">


                        <!-- =============================================
                             USUARIO
                        ============================================== -->

                        <div class="col-md-6">

                            <label
                                class="form-label"
                                for="username"
                            >

                                Usuario / correo *

                            </label>

                            <input
                                type="text"
                                name="username"
                                id="username"
                                class="form-control"
                                required
                                value="<?= htmlspecialchars(
                                    $_POST['username'] ?? '',
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>"
                            >

                        </div>


                        <!-- =============================================
                             NOMBRE
                        ============================================== -->

                        <div class="col-md-6">

                            <label
                                class="form-label"
                                for="nombre"
                            >

                                Nombre completo *

                            </label>

                            <input
                                type="text"
                                name="nombre"
                                id="nombre"
                                class="form-control"
                                required
                                value="<?= htmlspecialchars(
                                    $_POST['nombre'] ?? '',
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>"
                            >

                        </div>


                        <!-- =============================================
                             EMAIL
                        ============================================== -->

                        <div class="col-md-6">

                            <label
                                class="form-label"
                                for="email"
                            >

                                Correo electrónico

                            </label>

                            <input
                                type="email"
                                name="email"
                                id="email"
                                class="form-control"
                                value="<?= htmlspecialchars(
                                    $_POST['email'] ?? '',
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>"
                            >

                        </div>


                        <!-- =============================================
                             TELÉFONO
                        ============================================== -->

                        <div class="col-md-6">

                            <label
                                class="form-label"
                                for="telefono"
                            >

                                Teléfono

                            </label>

                            <input
                                type="text"
                                name="telefono"
                                id="telefono"
                                class="form-control"
                                value="<?= htmlspecialchars(
                                    $_POST['telefono'] ?? '',
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>"
                            >

                        </div>


                        <!-- =============================================
                             TIPO
                        ============================================== -->

                        <div class="col-md-6">

                            <label
                                class="form-label"
                                for="tipo"
                            >

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
                                    <?= (
                                        ($_POST['tipo'] ?? '')
                                        === 'admin'
                                    )
                                        ? 'selected'
                                        : ''
                                    ?>
                                >

                                    Administrador

                                </option>

                                <option
                                    value="docente"
                                    <?= (
                                        ($_POST['tipo'] ?? '')
                                        === 'docente'
                                    )
                                        ? 'selected'
                                        : ''
                                    ?>
                                >

                                    Docente

                                </option>

                                <option
                                    value="alumno"
                                    <?= (
                                        ($_POST['tipo'] ?? '')
                                        === 'alumno'
                                    )
                                        ? 'selected'
                                        : ''
                                    ?>
                                >

                                    Alumno

                                </option>

                            </select>

                        </div>


                        <!-- =============================================
                             CARRERA
                        ============================================== -->

                        <div
                            class="col-md-6"
                            id="contenedorCarrera"
                            style="display:block;"
                        >

                            <label
                                class="form-label"
                                for="carrera_id"
                            >

                                Carrera

                            </label>

                            <select
                                name="carrera_id"
                                id="carrera_id"
                                class="form-select"
                            >

                                <option value="">

                                    Seleccione...

                                </option>


                                <?php if ($carreras): ?>

                                    <?php
                                    $hayCarreras = false;

                                    while (
                                        $c =
                                        $carreras->fetch_assoc()
                                    ):
                                        $hayCarreras = true;
                                    ?>

                                        <option
                                            value="<?= (int)$c['id'] ?>"
                                            <?= (
                                                (int)(
                                                    $_POST['carrera_id']
                                                    ?? 0
                                                )
                                                === (int)$c['id']
                                            )
                                                ? 'selected'
                                                : ''
                                            ?>
                                        >

                                            <?= htmlspecialchars(
                                                $c['nombre'],
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>

                                        </option>

                                    <?php endwhile; ?>

                                    <?php if (!$hayCarreras): ?>

                                        <option value="" disabled>
                                            No hay carreras registradas en la base de datos
                                        </option>

                                    <?php endif; ?>

                                <?php endif; ?>

                            </select>

                        </div>


                        <!-- =============================================
                             CONTRASEÑA
                        ============================================== -->

                        <div class="col-md-6">

                            <label
                                class="form-label"
                                for="password"
                            >

                                Contraseña *

                            </label>

                            <input
                                type="password"
                                name="password"
                                id="password"
                                class="form-control"
                                minlength="6"
                                required
                            >

                        </div>


                        <!-- =============================================
                             CONFIRMAR
                        ============================================== -->

                        <div class="col-md-6">

                            <label
                                class="form-label"
                                for="confirm_password"
                            >

                                Confirmar contraseña *

                            </label>

                            <input
                                type="password"
                                name="confirm_password"
                                id="confirm_password"
                                class="form-control"
                                minlength="6"
                                required
                            >

                        </div>


                        <!-- =============================================
                             INFORMACIÓN DANLÍ
                        ============================================== -->

                        <div class="col-12">

                            <div class="info-danli">

                                <i class="fas fa-map-marker-alt me-2"></i>

                                <strong>Sede Danlí</strong>

                                <br>

                                <small>

                                    Este usuario será registrado
                                    exclusivamente en la sede Danlí.

                                </small>

                            </div>

                        </div>


                        <!-- =============================================
                             INFORMACIÓN ALUMNO
                        ============================================== -->

                        <div
                            class="col-12"
                            id="infoAlumno"
                        >

                            <div class="info-alumno">

                                <i class="fas fa-user-graduate me-2"></i>

                                <strong>Cuenta de alumno</strong>

                                <br>

                                <small>

                                    Al crear este usuario, el sistema
                                    creará automáticamente su registro
                                    en <strong>Alumnos</strong> y guardará
                                    la relación mediante
                                    <strong>usuario_id</strong>.

                                </small>

                            </div>

                        </div>


                        <!-- =============================================
                             INFORMACIÓN DOCENTE
                        ============================================== -->

                        <div
                            class="col-12"
                            id="infoDocente"
                        >

                            <div class="info-docente">

                                <i class="fas fa-chalkboard-teacher me-2"></i>

                                <strong>Cuenta de docente</strong>

                                <br>

                                <small>

                                    El docente quedará vinculado
                                    automáticamente con su cuenta
                                    de usuario.

                                </small>

                            </div>

                        </div>

                    </div>


                    <!-- =================================================
                         BOTONES
                    ================================================== -->

                    <div class="d-flex flex-wrap gap-2 mt-4">

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
                            id="btnCrear"
                        >

                            <i class="fas fa-user-plus me-1"></i>

                            Crear usuario

                        </button>

                    </div>

                </form>

            </div>

        </div>

    </div>

</div>


<script>

// ============================================================
// CONTROL DEL TIPO DE USUARIO
// ============================================================

document.addEventListener(
    'DOMContentLoaded',
    function () {

        const tipo =
            document.getElementById('tipo');

        const contenedorCarrera =
            document.getElementById(
                'contenedorCarrera'
            );

        const carrera =
            document.getElementById(
                'carrera_id'
            );

        const infoAlumno =
            document.getElementById(
                'infoAlumno'
            );

        const infoDocente =
            document.getElementById(
                'infoDocente'
            );


        function actualizarFormulario() {

            const valor =
                tipo.value;


            // ================================================
            // OCULTAR INFORMACIÓN
            // ================================================

            infoAlumno.style.display =
                'none';

            infoDocente.style.display =
                'none';


            // ================================================
            // ADMINISTRADOR
            // ================================================

            if (valor === 'admin') {

                contenedorCarrera.style.display =
                    'none';

                carrera.required =
                    false;

                carrera.value =
                    '';

            }


            // ================================================
            // ALUMNO
            // ================================================

            else if (valor === 'alumno') {

                contenedorCarrera.style.display =
                    'block';

                carrera.required =
                    true;

                infoAlumno.style.display =
                    'block';
            }


            // ================================================
            // DOCENTE
            // ================================================

            else if (valor === 'docente') {

                contenedorCarrera.style.display =
                    'none';

                carrera.required =
                    false;

                carrera.value =
                    '';

                infoDocente.style.display =
                    'block';

            }


            // ================================================
            // SIN SELECCIÓN
            // ================================================

            else {

                contenedorCarrera.style.display =
                    'block';

                carrera.required =
                    false;
            }
        }


        tipo.addEventListener(
            'change',
            actualizarFormulario
        );


        actualizarFormulario();

    }
);


// ============================================================
// EVITAR DOBLE ENVÍO
// ============================================================

document
    .getElementById('formUsuario')
    .addEventListener(
        'submit',
        function () {

            const boton =
                document.getElementById(
                    'btnCrear'
                );

            boton.disabled =
                true;

            boton.innerHTML =
                '<i class="fas fa-spinner fa-spin me-1"></i> ' +
                'Creando usuario...';

        }
    );

</script>


<?php

include '../includes/footer.php';

?>