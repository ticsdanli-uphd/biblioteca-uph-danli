<?php

include '../includes/session.php';
include '../config/db.php';


// ======================================================
// CONFIGURACIÓN
// SISTEMA EXCLUSIVO PARA DANLÍ
// ======================================================

$sede_id = 6;
$sede_nombre = "Danlí";


// ======================================================
// VERIFICAR QUE SEA ADMINISTRADOR
// ======================================================

if (
    !isset($_SESSION['role']) ||
    $_SESSION['role'] !== 'admin'
) {
    header('Location: /biblioteca/dashboard.php');
    exit();
}


// ======================================================
// OBTENER ID
// ======================================================

$id = isset($_GET['id'])
    ? intval($_GET['id'])
    : 0;


// ======================================================
// VALIDAR ID
// ======================================================

if ($id <= 0) {

    $_SESSION['error'] =
        "ID de usuario no válido.";

    header('Location: list.php');
    exit();
}


// ======================================================
// OBTENER USUARIO
// SOLAMENTE DANLÍ
// ======================================================

$stmt = $conn->prepare("
    SELECT
        u.id,
        u.username,
        u.nombre,
        u.role,
        u.sede_id,
        a.id AS alumno_id,
        a.nombre AS nombre_alumno
    FROM usuarios u
    LEFT JOIN alumnos a
        ON u.id = a.usuario_id
    WHERE u.id = ?
      AND u.sede_id = 6
    LIMIT 1
");

$stmt->bind_param("i", $id);

$stmt->execute();

$result = $stmt->get_result();


if ($result->num_rows === 0) {

    $stmt->close();

    $_SESSION['error'] =
        "Usuario no encontrado en la sede Danlí.";

    header('Location: list.php');
    exit();
}


$usuario = $result->fetch_assoc();

$stmt->close();


// ======================================================
// VARIABLES DEL FORMULARIO
// ======================================================

$error = "";
$success = "";


// ======================================================
// PROCESAR FORMULARIO
// ======================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // --------------------------------------------------
    // RECIBIR DATOS
    // --------------------------------------------------

    $username =
        trim($_POST['username'] ?? '');

    $nombre_completo =
        trim($_POST['nombre_completo'] ?? '');

    $tipo =
        trim($_POST['tipo'] ?? '');

    $nombre_alumno =
        trim($_POST['nombre_alumno'] ?? '');

    $password =
        $_POST['password'] ?? '';

    $confirm_password =
        $_POST['confirm_password'] ?? '';


    // --------------------------------------------------
    // VALIDAR
    // --------------------------------------------------

    if (
        empty($username) ||
        empty($nombre_completo) ||
        empty($tipo)
    ) {

        $error =
            "Los campos obligatorios deben completarse.";

    }

    elseif (
        !in_array(
            $tipo,
            ['admin', 'docente', 'usuario'],
            true
        )
    ) {

        $error =
            "Tipo de usuario no válido.";

    }

    elseif (
        !empty($password) &&
        $password !== $confirm_password
    ) {

        $error =
            "Las contraseñas no coinciden.";

    }

    elseif (
        !empty($password) &&
        strlen($password) < 6
    ) {

        $error =
            "La contraseña debe tener al menos 6 caracteres.";

    }

    else {

        // --------------------------------------------------
        // VERIFICAR USERNAME DUPLICADO
        // --------------------------------------------------

        $stmtCheck = $conn->prepare("
            SELECT id
            FROM usuarios
            WHERE username = ?
              AND id != ?
            LIMIT 1
        ");

        $stmtCheck->bind_param(
            "si",
            $username,
            $id
        );

        $stmtCheck->execute();

        $resultCheck =
            $stmtCheck->get_result();


        if ($resultCheck->num_rows > 0) {

            $error =
                "El nombre de usuario ya está registrado.";

            $stmtCheck->close();

        } else {

            $stmtCheck->close();


            // ==================================================
            // INICIAR TRANSACCIÓN
            // ==================================================

            $conn->begin_transaction();


            try {


                // ==============================================
                // ACTUALIZAR USUARIO
                // ==============================================

                if (!empty($password)) {

                    $hashed_password =
                        password_hash(
                            $password,
                            PASSWORD_DEFAULT
                        );


                    $stmtUpdate = $conn->prepare("
                        UPDATE usuarios
                        SET
                            username = ?,
                            nombre = ?,
                            role = ?,
                            password = ?,
                            sede_id = 6
                        WHERE id = ?
                          AND sede_id = 6
                    ");

                    $stmtUpdate->bind_param(
                        "ssssi",
                        $username,
                        $nombre_completo,
                        $tipo,
                        $hashed_password,
                        $id
                    );

                } else {

                    $stmtUpdate = $conn->prepare("
                        UPDATE usuarios
                        SET
                            username = ?,
                            nombre = ?,
                            role = ?,
                            sede_id = 6
                        WHERE id = ?
                          AND sede_id = 6
                    ");

                    $stmtUpdate->bind_param(
                        "sssi",
                        $username,
                        $nombre_completo,
                        $tipo,
                        $id
                    );
                }


                if (!$stmtUpdate->execute()) {

                    throw new Exception(
                        "Error al actualizar el usuario: "
                        . $stmtUpdate->error
                    );
                }


                $stmtUpdate->close();


                // ==============================================
                // SI ES ALUMNO
                // ==============================================

                if ($tipo === 'usuario') {


                    // ------------------------------------------
                    // VERIFICAR SI YA EXISTE EN alumnos
                    // ------------------------------------------

                    $stmtAlumno = $conn->prepare("
                        SELECT id
                        FROM alumnos
                        WHERE usuario_id = ?
                        LIMIT 1
                    ");

                    $stmtAlumno->bind_param(
                        "i",
                        $id
                    );

                    $stmtAlumno->execute();

                    $resultAlumno =
                        $stmtAlumno->get_result();


                    if ($resultAlumno->num_rows > 0) {

                        $alumno =
                            $resultAlumno->fetch_assoc();

                        $alumno_id =
                            intval($alumno['id']);

                        $stmtAlumno->close();


                        // ------------------------------
                        // ACTUALIZAR ALUMNO
                        // ------------------------------

                        $stmtUpdateAlumno =
                            $conn->prepare("
                                UPDATE alumnos
                                SET nombre = ?
                                WHERE id = ?
                            ");

                        $stmtUpdateAlumno->bind_param(
                            "si",
                            $nombre_alumno,
                            $alumno_id
                        );


                        if (
                            !$stmtUpdateAlumno->execute()
                        ) {

                            throw new Exception(
                                "Error al actualizar "
                                . "los datos del alumno: "
                                . $stmtUpdateAlumno->error
                            );
                        }


                        $stmtUpdateAlumno->close();

                    } else {

                        $stmtAlumno->close();


                        // ------------------------------
                        // CREAR REGISTRO DE ALUMNO
                        // ------------------------------

                        $stmtInsertAlumno =
                            $conn->prepare("
                                INSERT INTO alumnos
                                (
                                    usuario_id,
                                    nombre
                                )
                                VALUES
                                (?, ?)
                            ");

                        $stmtInsertAlumno->bind_param(
                            "is",
                            $id,
                            $nombre_alumno
                        );


                        if (
                            !$stmtInsertAlumno->execute()
                        ) {

                            throw new Exception(
                                "Error al crear "
                                . "el registro del alumno: "
                                . $stmtInsertAlumno->error
                            );
                        }


                        $stmtInsertAlumno->close();

                    }


                } else {

                    // ==========================================
                    // SI DEJA DE SER ALUMNO
                    // ELIMINAR REGISTRO DE alumnos
                    // ==========================================

                    $stmtDeleteAlumno =
                        $conn->prepare("
                            DELETE FROM alumnos
                            WHERE usuario_id = ?
                        ");

                    $stmtDeleteAlumno->bind_param(
                        "i",
                        $id
                    );


                    if (
                        !$stmtDeleteAlumno->execute()
                    ) {

                        throw new Exception(
                            "Error al eliminar "
                            . "el registro del alumno: "
                            . $stmtDeleteAlumno->error
                        );
                    }


                    $stmtDeleteAlumno->close();
                }


                // ==============================================
                // CONFIRMAR
                // ==============================================

                $conn->commit();


                $success =
                    "Usuario actualizado correctamente.";


                // ----------------------------------------------
                // ACTUALIZAR DATOS MOSTRADOS EN EL FORMULARIO
                // ----------------------------------------------

                $usuario['username'] =
                    $username;

                $usuario['nombre'] =
                    $nombre_completo;

                $usuario['role'] =
                    $tipo;

                $usuario['sede_id'] =
                    6;

                $usuario['nombre_alumno'] =
                    $nombre_alumno;


            } catch (Exception $e) {

                // ----------------------------------------------
                // REVERTIR
                // ----------------------------------------------

                $conn->rollback();


                $error =
                    $e->getMessage();
            }
        }
    }
}


// ======================================================
// DETERMINAR DATOS PARA EL FORMULARIO
// ======================================================

$tipo_actual =
    $usuario['role'] ?? 'usuario';


// ======================================================
// HEADER
// ======================================================

include '../includes/header.php';

?>


<div class="container-fluid py-4">

    <div class="row justify-content-center">

        <div class="col-lg-8 col-xl-7">


            <div class="card shadow-sm border-0">


                <!-- ==================================================
                     HEADER
                ================================================== -->

                <div class="card-header bg-primary text-white py-3">

                    <h4 class="mb-0">

                        <i class="fas fa-user-edit me-2"></i>

                        Editar Usuario

                    </h4>

                </div>


                <!-- ==================================================
                     BODY
                ================================================== -->

                <div class="card-body p-4">


                    <!-- ERROR -->

                    <?php if (!empty($error)): ?>

                        <div
                            class="
                                alert
                                alert-danger
                                alert-dismissible
                                fade
                                show
                            "
                        >

                            <i
                                class="
                                    fas
                                    fa-exclamation-triangle
                                    me-2
                                "
                            ></i>

                            <?php echo htmlspecialchars($error); ?>

                            <button
                                type="button"
                                class="btn-close"
                                data-bs-dismiss="alert"
                            ></button>

                        </div>

                    <?php endif; ?>


                    <!-- ÉXITO -->

                    <?php if (!empty($success)): ?>

                        <div
                            class="
                                alert
                                alert-success
                                alert-dismissible
                                fade
                                show
                            "
                        >

                            <i
                                class="
                                    fas
                                    fa-check-circle
                                    me-2
                                "
                            ></i>

                            <?php echo htmlspecialchars($success); ?>

                            <button
                                type="button"
                                class="btn-close"
                                data-bs-dismiss="alert"
                            ></button>

                        </div>

                    <?php endif; ?>


                    <!-- INFORMACIÓN -->

                    <div class="alert alert-info">

                        <i
                            class="
                                fas
                                fa-map-marker-alt
                                me-2
                            "
                        ></i>

                        Este usuario pertenece a:

                        <strong>
                            Sede Danlí
                        </strong>

                    </div>


                    <!-- ==================================================
                         FORMULARIO
                    ================================================== -->

                    <form
                        method="post"
                        action=""
                        autocomplete="off"
                    >


                        <!-- USUARIO -->

                        <div class="mb-3">

                            <label
                                for="username"
                                class="form-label"
                            >

                                <strong>
                                    Nombre de Usuario
                                </strong>

                            </label>


                            <input
                                type="text"
                                class="form-control"
                                id="username"
                                name="username"
                                value="<?php
                                    echo htmlspecialchars(
                                        $usuario['username'] ?? ''
                                    );
                                ?>"
                                required
                            >

                        </div>


                        <!-- NOMBRE COMPLETO -->

                        <div class="mb-3">

                            <label
                                for="nombre_completo"
                                class="form-label"
                            >

                                <strong>
                                    Nombre Completo
                                </strong>

                            </label>


                            <input
                                type="text"
                                class="form-control"
                                id="nombre_completo"
                                name="nombre_completo"
                                value="<?php
                                    echo htmlspecialchars(
                                        $usuario['nombre'] ?? ''
                                    );
                                ?>"
                                placeholder="
                                    Ingrese el nombre completo
                                "
                                required
                            >

                        </div>


                        <!-- TIPO -->

                        <div class="mb-3">

                            <label
                                for="tipo"
                                class="form-label"
                            >

                                <strong>
                                    Tipo de Usuario
                                </strong>

                            </label>


                            <select
                                class="form-select"
                                id="tipo"
                                name="tipo"
                                onchange="toggleAlumnoFields()"
                                required
                            >


                                <option
                                    value="admin"
                                    <?php
                                    echo (
                                        $tipo_actual === 'admin'
                                    )
                                    ? 'selected'
                                    : '';
                                    ?>
                                >

                                    Administrador

                                </option>


                                <option
                                    value="docente"
                                    <?php
                                    echo (
                                        $tipo_actual === 'docente'
                                    )
                                    ? 'selected'
                                    : '';
                                    ?>
                                >

                                    Docente

                                </option>


                                <option
                                    value="usuario"
                                    <?php
                                    echo (
                                        $tipo_actual === 'usuario'
                                    )
                                    ? 'selected'
                                    : '';
                                    ?>
                                >

                                    Alumno

                                </option>


                            </select>

                        </div>


                        <!-- ==================================================
                             CAMPOS ALUMNO
                        ================================================== -->

                        <div
                            id="alumno_fields"
                            class="mb-3"
                            <?php

                            echo (
                                $tipo_actual !== 'usuario'
                            )
                            ? 'style="display:none;"'
                            : '';

                            ?>
                        >


                            <label
                                for="nombre_alumno"
                                class="form-label"
                            >

                                <strong>
                                    Nombre del Alumno
                                </strong>

                            </label>


                            <input
                                type="text"
                                class="form-control"
                                id="nombre_alumno"
                                name="nombre_alumno"
                                value="<?php
                                    echo htmlspecialchars(
                                        $usuario['nombre_alumno'] ?? ''
                                    );
                                ?>"
                                placeholder="
                                    Ingrese el nombre del alumno
                                "
                            >


                            <small class="text-muted">

                                Este campo se utiliza para el
                                registro de alumnos.

                            </small>


                        </div>


                        <!-- ==================================================
                             CONTRASEÑA
                        ================================================== -->

                        <div class="mb-3">

                            <label
                                for="password"
                                class="form-label"
                            >

                                <strong>
                                    Nueva Contraseña
                                </strong>

                            </label>


                            <input
                                type="password"
                                class="form-control"
                                id="password"
                                name="password"
                                minlength="6"
                                placeholder="
                                    Dejar vacío para conservar
                                    la contraseña actual
                                "
                            >


                            <small class="text-muted">

                                Si no desea cambiarla,
                                deje este campo vacío.

                            </small>

                        </div>


                        <!-- CONFIRMAR CONTRASEÑA -->

                        <div class="mb-3">

                            <label
                                for="confirm_password"
                                class="form-label"
                            >

                                <strong>
                                    Confirmar Nueva Contraseña
                                </strong>

                            </label>


                            <input
                                type="password"
                                class="form-control"
                                id="confirm_password"
                                name="confirm_password"
                                minlength="6"
                                placeholder="
                                    Repita la nueva contraseña
                                "
                            >

                        </div>


                        <!-- ==================================================
                             SEDE
                        ================================================== -->

                        <div class="mb-4">

                            <label
                                for="sede"
                                class="form-label"
                            >

                                <strong>
                                    Sede
                                </strong>

                            </label>


                            <input
                                type="text"
                                class="form-control"
                                id="sede"
                                value="Danlí"
                                readonly
                            >


                            <small class="text-muted">

                                La sede no puede modificarse.
                                Todos los usuarios pertenecen
                                a Danlí.

                            </small>

                        </div>


                        <!-- ==================================================
                             BOTONES
                        ================================================== -->

                        <div
                            class="
                                d-flex
                                justify-content-between
                                align-items-center
                            "
                        >


                            <a
                                href="list.php"
                                class="btn btn-secondary"
                            >

                                <i
                                    class="
                                        fas
                                        fa-arrow-left
                                        me-1
                                    "
                                ></i>

                                Cancelar

                            </a>


                            <button
                                type="submit"
                                class="btn btn-primary px-4"
                            >

                                <i
                                    class="
                                        fas
                                        fa-save
                                        me-1
                                    "
                                ></i>

                                Guardar Cambios

                            </button>


                        </div>


                    </form>

                </div>

            </div>

        </div>

    </div>

</div>


<script>


// ======================================================
// MOSTRAR / OCULTAR CAMPOS DEL ALUMNO
// ======================================================

function toggleAlumnoFields() {

    const tipo =
        document.getElementById(
            'tipo'
        ).value;


    const camposAlumno =
        document.getElementById(
            'alumno_fields'
        );


    const nombreAlumno =
        document.getElementById(
            'nombre_alumno'
        );


    if (tipo === 'usuario') {

        camposAlumno.style.display =
            'block';

    } else {

        camposAlumno.style.display =
            'none';

        nombreAlumno.value =
            '';

    }

}


// ======================================================
// EJECUTAR AL CARGAR
// ======================================================

document.addEventListener(
    'DOMContentLoaded',
    function() {

        toggleAlumnoFields();

    }
);


// ======================================================
// VALIDAR CONTRASEÑA
// ======================================================

document.querySelector('form')
    .addEventListener(
        'submit',
        function(event) {


            const password =
                document.getElementById(
                    'password'
                ).value;


            const confirmPassword =
                document.getElementById(
                    'confirm_password'
                ).value;


            // Si se escribió contraseña,
            // debe coincidir

            if (
                password !== '' &&
                password !== confirmPassword
            ) {

                event.preventDefault();

                alert(
                    'Las contraseñas no coinciden.'
                );

                return;

            }


            // Validar mínimo

            if (
                password !== '' &&
                password.length < 6
            ) {

                event.preventDefault();

                alert(
                    'La contraseña debe tener al menos 6 caracteres.'
                );

            }

        }
    );

</script>


<?php

include '../includes/footer.php';

?>