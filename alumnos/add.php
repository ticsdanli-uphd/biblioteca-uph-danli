<?php

session_start();

include '../includes/session.php';
include '../config/db.php';


// =====================================================
// VERIFICAR SESIÓN
// =====================================================

if (!isset($_SESSION['user_id'])) {

    header('Location: ../login.php');
    exit();

}


// =====================================================
// SEDE FIJA: DANLÍ
// =====================================================

$sede_id = 4;


// =====================================================
// VARIABLES
// =====================================================

$error_msg = '';
$success_msg = '';


// =====================================================
// PROCESAR FORMULARIO
// =====================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // -------------------------------------------------
    // RECIBIR DATOS
    // -------------------------------------------------

    $nombre = trim($_POST['nombre'] ?? '');

    $telefono = trim($_POST['telefono'] ?? '');

    $email = trim($_POST['email'] ?? '');

    $password = $_POST['password'] ?? '';

    $carrera_id = !empty($_POST['carrera_id'])
        ? intval($_POST['carrera_id'])
        : null;


    // -------------------------------------------------
    // VALIDAR CAMPOS
    // -------------------------------------------------

    if (
        $nombre === '' ||
        $telefono === '' ||
        $email === '' ||
        $password === ''
    ) {

        $error_msg =
            'Todos los campos obligatorios deben completarse.';

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error_msg =
            'Ingrese un correo electrónico válido.';

    } elseif (strlen($password) < 6) {

        $error_msg =
            'La contraseña debe tener como mínimo 6 caracteres.';

    } else {


        // =================================================
        // VERIFICAR QUE EL CORREO NO EXISTA
        // =================================================

        $sqlCheck = "
            SELECT id
            FROM usuarios
            WHERE LOWER(username) = LOWER(?)
            LIMIT 1
        ";

        $stmtCheck = $conn->prepare($sqlCheck);

        if (!$stmtCheck) {

            $error_msg =
                'Error al preparar la consulta: '
                . $conn->error;

        } else {

            $stmtCheck->bind_param(
                's',
                $email
            );

            $stmtCheck->execute();

            $resultCheck =
                $stmtCheck->get_result();


            if ($resultCheck->num_rows > 0) {

                $error_msg =
                    'El correo electrónico ya está registrado.';

            } else {

                // Verificar también que el correo no pertenezca
                // a otro alumno.
                $sqlAlumnoCheck = "
                    SELECT id
                    FROM alumnos
                    WHERE LOWER(email) = LOWER(?)
                    LIMIT 1
                ";

                $stmtAlumnoCheck = $conn->prepare($sqlAlumnoCheck);

                if (!$stmtAlumnoCheck) {

                    $error_msg =
                        'Error al verificar el correo del alumno: '
                        . $conn->error;

                } else {

                    $stmtAlumnoCheck->bind_param('s', $email);
                    $stmtAlumnoCheck->execute();

                    $resultAlumnoCheck =
                        $stmtAlumnoCheck->get_result();

                    if ($resultAlumnoCheck->num_rows > 0) {

                        $error_msg =
                            'El correo electrónico ya pertenece a otro alumno.';

                    }

                    $stmtAlumnoCheck->close();
                }

                if (empty($error_msg)) {


                // =================================================
                // CREAR CONTRASEÑA SEGURA
                // =================================================

                $password_hash =
                    password_hash(
                        $password,
                        PASSWORD_DEFAULT
                    );


                // =================================================
                // INICIAR TRANSACCIÓN
                // =================================================

                $conn->begin_transaction();


                try {


                    // =============================================
                    // CREAR USUARIO
                    // =============================================

                    $sqlUsuario = "
                        INSERT INTO usuarios
                        (
                            username,
                            nombre,
                            password,
                            role,
                            sede_id
                        )
                        VALUES
                        (
                            ?,
                            ?,
                            ?,
                            'alumno',
                            4
                        )
                    ";


                    $stmtUsuario =
                        $conn->prepare(
                            $sqlUsuario
                        );


                    if (!$stmtUsuario) {

                        throw new Exception(
                            'Error al preparar el registro del usuario: '
                            . $conn->error
                        );

                    }


                    $stmtUsuario->bind_param(
                        'sss',
                        $email,
                        $nombre,
                        $password_hash
                    );


                    if (!$stmtUsuario->execute()) {

                        throw new Exception(
                            'Error al crear el usuario: '
                            . $stmtUsuario->error
                        );

                    }


                    // =============================================
                    // ID DEL USUARIO CREADO
                    // =============================================

                    $usuario_id =
                        $conn->insert_id;


                    // =============================================
                    // CREAR ALUMNO
                    // =============================================

                    $sqlAlumno = "
                        INSERT INTO alumnos
                        (
                            nombre,
                            telefono,
                            email,
                            carrera_id,
                            usuario_id
                        )
                        VALUES
                        (
                            ?,
                            ?,
                            ?,
                            ?,
                            ?
                        )
                    ";


                    $stmtAlumno =
                        $conn->prepare(
                            $sqlAlumno
                        );


                    if (!$stmtAlumno) {

                        throw new Exception(
                            'Error al preparar el registro del alumno: '
                            . $conn->error
                        );

                    }


                    $stmtAlumno->bind_param(
                        'sssii',
                        $nombre,
                        $telefono,
                        $email,
                        $carrera_id,
                        $usuario_id
                    );


                    if (!$stmtAlumno->execute()) {

                        throw new Exception(
                            'Error al registrar el alumno: '
                            . $stmtAlumno->error
                        );

                    }


                    // =============================================
                    // CONFIRMAR
                    // =============================================

                    $conn->commit();


                    $success_msg =
                        'Alumno y usuario de acceso registrados correctamente. '
                        . 'El alumno ya quedó vinculado a su cuenta para solicitar préstamos.';


                    // Limpiar campos después de guardar

                    $_POST = [];


                    $stmtUsuario->close();

                    $stmtAlumno->close();


                } catch (Exception $e) {


                    // =============================================
                    // DESHACER CAMBIOS
                    // =============================================

                    $conn->rollback();


                    $error_msg =
                        $e->getMessage();

                }

                }

            }


            $stmtCheck->close();

        }

    }

}


// =====================================================
// OBTENER CARRERAS
// =====================================================

$sqlCarreras = "
    SELECT id, nombre
    FROM carreras
    ORDER BY nombre ASC
";

$resultCarreras =
    $conn->query(
        $sqlCarreras
    );


include '../includes/header.php';

?>


<div class="container my-4">

    <div class="card shadow-sm">


        <!-- =================================================
             ENCABEZADO
        ================================================== -->

        <div class="card-header bg-primary text-white">

            <h2 class="mb-0">

                <i class="fas fa-user-plus"></i>

                Registrar Alumno

            </h2>

        </div>


        <div class="card-body">


            <!-- =================================================
                 MENSAJE DE ERROR
            ================================================== -->

            <?php if (!empty($error_msg)): ?>

                <div class="alert alert-danger">

                    <i class="fas fa-exclamation-circle"></i>

                    <?php
                    echo htmlspecialchars(
                        $error_msg,
                        ENT_QUOTES,
                        'UTF-8'
                    );
                    ?>

                </div>

            <?php endif; ?>


            <!-- =================================================
                 MENSAJE DE ÉXITO
            ================================================== -->

            <?php if (!empty($success_msg)): ?>

                <div class="alert alert-success">

                    <i class="fas fa-check-circle"></i>

                    <?php
                    echo htmlspecialchars(
                        $success_msg,
                        ENT_QUOTES,
                        'UTF-8'
                    );
                    ?>

                    <div class="mt-2">

                        <a
                            href="list.php"
                            class="btn btn-success btn-sm"
                        >

                            <i class="fas fa-users"></i>

                            Ver alumnos

                        </a>

                    </div>

                </div>

            <?php endif; ?>


            <!-- =================================================
                 HERRAMIENTAS ADMINISTRADOR
            ================================================== -->

            <?php if (
                isset($_SESSION['role']) &&
                $_SESSION['role'] === 'admin'
            ): ?>

                <div class="mb-3">

                    <a
                        href="download_template.php"
                        class="btn btn-secondary btn-sm me-2"
                    >

                        <i class="fas fa-download"></i>

                        Descargar plantilla en blanco

                    </a>


                    <a
                        href="upload_excel.php"
                        class="btn btn-info btn-sm"
                    >

                        <i class="fas fa-file-upload"></i>

                        Subir plantilla de alumnos

                    </a>

                </div>

            <?php endif; ?>


            <!-- =================================================
                 FORMULARIO
            ================================================== -->

            <form
                method="post"
                action="add.php"
                autocomplete="off"
            >


                <!-- NOMBRE -->

                <div class="mb-3 form-floating">

                    <input
                        type="text"
                        name="nombre"
                        class="form-control"
                        id="nombre"
                        placeholder="Nombre del Alumno"
                        maxlength="255"
                        value="<?php
                            echo htmlspecialchars(
                                $_POST['nombre'] ?? '',
                                ENT_QUOTES,
                                'UTF-8'
                            );
                        ?>"
                        required
                    >

                    <label for="nombre">

                        Nombre del Alumno

                    </label>

                </div>


                <!-- TELÉFONO -->

                <div class="mb-3 form-floating">

                    <input
                        type="tel"
                        name="telefono"
                        class="form-control"
                        id="telefono"
                        placeholder="Teléfono"
                        maxlength="50"
                        value="<?php
                            echo htmlspecialchars(
                                $_POST['telefono'] ?? '',
                                ENT_QUOTES,
                                'UTF-8'
                            );
                        ?>"
                        required
                    >

                    <label for="telefono">

                        Teléfono

                    </label>

                </div>


                <!-- EMAIL -->

                <div class="mb-3 form-floating">

                    <input
                        type="email"
                        name="email"
                        class="form-control"
                        id="email"
                        placeholder="Email"
                        maxlength="255"
                        value="<?php
                            echo htmlspecialchars(
                                $_POST['email'] ?? '',
                                ENT_QUOTES,
                                'UTF-8'
                            );
                        ?>"
                        required
                    >

                    <label for="email">

                        Email

                    </label>

                </div>


                <!-- CONTRASEÑA -->

                <div class="mb-3 form-floating">

                    <input
                        type="password"
                        name="password"
                        class="form-control"
                        id="password"
                        placeholder="Contraseña"
                        minlength="6"
                        required
                    >

                    <label for="password">

                        Contraseña

                    </label>

                </div>


                <!-- CARRERA -->

                <div class="mb-3 form-floating">

                    <select
                        name="carrera_id"
                        class="form-select"
                        id="carrera_id"
                    >

                        <option value="">
                            Seleccione una carrera
                        </option>


                        <?php

                        if ($resultCarreras):

                            while (
                                $carrera =
                                $resultCarreras->fetch_assoc()
                            ):

                                $selected = '';

                                if (
                                    isset(
                                        $_POST['carrera_id']
                                    ) &&
                                    intval(
                                        $_POST['carrera_id']
                                    ) ===
                                    intval(
                                        $carrera['id']
                                    )
                                ) {

                                    $selected =
                                        'selected';

                                }

                        ?>

                            <option
                                value="<?php
                                    echo (int)$carrera['id'];
                                ?>"
                                <?php
                                    echo $selected;
                                ?>
                            >

                                <?php
                                echo htmlspecialchars(
                                    $carrera['nombre'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                );
                                ?>

                            </option>

                        <?php

                            endwhile;

                        endif;

                        ?>

                    </select>

                    <label for="carrera_id">

                        Carrera

                    </label>

                </div>


                <!-- =================================================
                     BOTONES
                ================================================== -->

                <div class="d-flex justify-content-between">

                    <button
                        type="submit"
                        class="btn btn-primary"
                    >

                        <i class="fas fa-user-plus"></i>

                        Registrar Alumno

                    </button>


                    <a
                        href="list.php"
                        class="btn btn-secondary"
                    >

                        Cancelar

                    </a>

                </div>


            </form>

        </div>

    </div>

</div>


<?php

include '../includes/footer.php';

?>