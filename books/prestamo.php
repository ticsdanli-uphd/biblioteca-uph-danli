<?php
// ============================================================
// books/prestamo.php
// REGISTRAR PRÉSTAMO DE LIBROS
// Versión corregida:
// - Alumno: se selecciona automáticamente desde la sesión.
// - Administrador: puede seleccionar cualquier alumno.
// - Docente: puede seleccionar alumno si administra préstamos.
// - La carrera del alumno se carga automáticamente.
// - Mantiene la estructura actual: registro_visitas.
// ============================================================

include '../includes/session.php';
include '../config/db.php';

// ------------------------------------------------------------
// VERIFICAR SESIÓN
// ------------------------------------------------------------

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

$user_id = (int) $_SESSION['user_id'];

// ------------------------------------------------------------
// DETECTAR ROL
// ------------------------------------------------------------

$rol = strtolower(trim(
    $_SESSION['role']
    ?? $_SESSION['rol']
    ?? $_SESSION['tipo_usuario']
    ?? $_SESSION['user_role']
    ?? ''
));

if (in_array($rol, ['student', 'estudiante', 'usuario', 'user'], true)) {
    $rol = 'alumno';
}

if (in_array($rol, ['teacher', 'profesor', 'docente'], true)) {
    $rol = 'docente';
}

if (in_array($rol, ['administrator', 'administrador', 'admin'], true)) {
    $rol = 'admin';
}

// ------------------------------------------------------------
// ID DEL LIBRO
// ------------------------------------------------------------

$bibliografia_id = isset($_GET['id'])
    ? (int) $_GET['id']
    : 0;

if ($bibliografia_id <= 0) {
    $_SESSION['error_msg'] = 'Libro no válido.';
    header('Location: list.php');
    exit();
}

// ------------------------------------------------------------
// RESERVA
// ------------------------------------------------------------

$reserva_id = isset($_GET['reserva_id'])
    ? (int) $_GET['reserva_id']
    : 0;

// ------------------------------------------------------------
// VARIABLES
// ------------------------------------------------------------

$error = '';

$alumno_id_preseleccionado = 0;
$nombre_alumno_preseleccionado = '';
$carrera_preseleccionada = 0;

// ------------------------------------------------------------
// OBTENER ALUMNO DE LA SESIÓN
//
// IMPORTANTE:
// El login debe guardar el ID del alumno en una de estas
// variables de sesión:
// $_SESSION['alumno_id']
//
// También se aceptan:
// $_SESSION['student_id']
// $_SESSION['id_alumno']
// ------------------------------------------------------------

if ($rol === 'alumno') {

    $alumno_id_sesion = (int) (
        $_SESSION['alumno_id']
        ?? $_SESSION['student_id']
        ?? $_SESSION['id_alumno']
        ?? 0
    );

    // Recuperar el alumno automáticamente desde la cuenta de usuario.
    if ($alumno_id_sesion <= 0 && $user_id > 0) {

        $stmtVinculo = $conn->prepare("
            SELECT id
            FROM alumnos
            WHERE usuario_id = ?
            LIMIT 1
        ");

        if ($stmtVinculo) {

            $stmtVinculo->bind_param("i", $user_id);
            $stmtVinculo->execute();

            $resultVinculo = $stmtVinculo->get_result();

            if ($vinculo = $resultVinculo->fetch_assoc()) {

                $alumno_id_sesion = (int)$vinculo['id'];

                $_SESSION['alumno_id'] = $alumno_id_sesion;
            }

            $stmtVinculo->close();
        }
    }

    if ($alumno_id_sesion > 0) {

        $stmtAlumno = $conn->prepare("
            SELECT
                id,
                nombre,
                carrera_id
            FROM alumnos
            WHERE id = ?
            LIMIT 1
        ");

        if ($stmtAlumno) {

            $stmtAlumno->bind_param(
                "i",
                $alumno_id_sesion
            );

            $stmtAlumno->execute();

            $resultAlumno = $stmtAlumno->get_result();

            if ($alumno = $resultAlumno->fetch_assoc()) {

                $alumno_id_preseleccionado =
                    (int) $alumno['id'];

                $nombre_alumno_preseleccionado =
                    $alumno['nombre'];

                $carrera_preseleccionada =
                    !empty($alumno['carrera_id'])
                    ? (int) $alumno['carrera_id']
                    : 0;

            } else {

                $error =
                    'El usuario estudiante no está vinculado '
                    . 'a un alumno registrado en la biblioteca.';
            }

            $stmtAlumno->close();

        } else {

            $error =
                'No se pudo consultar la información del alumno: '
                . $conn->error;
        }

    } else {

        $error =
            'No se encontró el alumno asociado a su cuenta. '
            . 'Debe vincular su usuario con un alumno.';
    }

} else {

    // --------------------------------------------------------
    // ADMIN / DOCENTE
    // Permite usar alumno enviado por GET si existe.
    // --------------------------------------------------------

    $alumno_id_preseleccionado = isset($_GET['alumno_id'])
        ? (int) $_GET['alumno_id']
        : 0;

    if ($alumno_id_preseleccionado > 0) {

        $stmtAlumno = $conn->prepare("
            SELECT
                id,
                nombre,
                carrera_id
            FROM alumnos
            WHERE id = ?
            LIMIT 1
        ");

        if ($stmtAlumno) {

            $stmtAlumno->bind_param(
                "i",
                $alumno_id_preseleccionado
            );

            $stmtAlumno->execute();

            $resultAlumno = $stmtAlumno->get_result();

            if ($alumno = $resultAlumno->fetch_assoc()) {

                $nombre_alumno_preseleccionado =
                    $alumno['nombre'];

                $carrera_preseleccionada =
                    !empty($alumno['carrera_id'])
                    ? (int) $alumno['carrera_id']
                    : 0;
            }

            $stmtAlumno->close();
        }
    }
}

// ------------------------------------------------------------
// INFORMACIÓN DEL LIBRO
// ------------------------------------------------------------

$stmtLibro = $conn->prepare("
    SELECT
        id,
        nombre,
        codigo,
        cantidad,
        sede_id
    FROM bibliografia
    WHERE id = ?
    LIMIT 1
");

if (!$stmtLibro) {
    die(
        'Error preparando consulta del libro: '
        . htmlspecialchars($conn->error, ENT_QUOTES, 'UTF-8')
    );
}

$stmtLibro->bind_param(
    "i",
    $bibliografia_id
);

$stmtLibro->execute();

$resultLibro = $stmtLibro->get_result();

$bibliografia = $resultLibro->fetch_assoc();

$stmtLibro->close();

if (!$bibliografia) {

    $_SESSION['error_msg'] =
        'El libro no existe.';

    header('Location: list.php');
    exit();
}

// ------------------------------------------------------------
// DATOS DEL LIBRO
// ------------------------------------------------------------

$nombre_libro =
    $bibliografia['nombre'];

$codigo_libro =
    $bibliografia['codigo'];

$cantidad_libro =
    (int) $bibliografia['cantidad'];

$sede_libro =
    (int) $bibliografia['sede_id'];

// ------------------------------------------------------------
// CONTAR PRÉSTAMOS ACTIVOS
// ------------------------------------------------------------

$stmtActivos = $conn->prepare("
    SELECT COUNT(*) AS total
    FROM registro_visitas
    WHERE bibliografia_id = ?
      AND tipo = 'prestamo'
      AND devuelto = 0
");

if (!$stmtActivos) {
    die(
        'Error consultando préstamos activos: '
        . htmlspecialchars($conn->error, ENT_QUOTES, 'UTF-8')
    );
}

$stmtActivos->bind_param(
    "i",
    $bibliografia_id
);

$stmtActivos->execute();

$resultActivos =
    $stmtActivos->get_result();

$dataActivos =
    $resultActivos->fetch_assoc();

$stmtActivos->close();

$prestamos_activos =
    (int) ($dataActivos['total'] ?? 0);

$disponible =
    $prestamos_activos < $cantidad_libro;

// ------------------------------------------------------------
// PROCESAR FORMULARIO
// ------------------------------------------------------------

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && empty($error)
) {

    // --------------------------------------------------------
    // RESERVA
    // --------------------------------------------------------

    $reserva_id_post = isset($_POST['reserva_id'])
        ? (int) $_POST['reserva_id']
        : 0;

    if ($reserva_id_post > 0) {
        $reserva_id = $reserva_id_post;
    }

    // --------------------------------------------------------
    // ALUMNO
    // --------------------------------------------------------
    //
    // SI ES ALUMNO:
    // IGNORAMOS COMPLETAMENTE EL alumno_id DEL FORMULARIO
    // Y USAMOS EL ID DE LA SESIÓN.
    //
    // ESTO EVITA QUE UN ESTUDIANTE PUEDA CAMBIAR EL ID
    // PARA REALIZAR UN PRÉSTAMO A NOMBRE DE OTRA PERSONA.
    // --------------------------------------------------------

    if ($rol === 'alumno') {

        $alumno_id =
            $alumno_id_preseleccionado;

    } else {

        $alumno_id =
            isset($_POST['alumno_id'])
            ? (int) $_POST['alumno_id']
            : 0;
    }

    // --------------------------------------------------------
    // CARRERA
    // --------------------------------------------------------

    $carrera_id =
        isset($_POST['carrera_id'])
        && $_POST['carrera_id'] !== ''
        ? (int) $_POST['carrera_id']
        : 0;

    // --------------------------------------------------------
    // OBSERVACIONES
    // --------------------------------------------------------

    $observaciones =
        isset($_POST['observaciones'])
        ? trim($_POST['observaciones'])
        : '';

    // --------------------------------------------------------
    // VALIDAR ALUMNO
    // --------------------------------------------------------

    if ($alumno_id <= 0) {

        $error =
            'No se encontró un alumno válido para el préstamo.';

    } else {

        $stmtAlumno = $conn->prepare("
            SELECT
                id,
                nombre,
                carrera_id
            FROM alumnos
            WHERE id = ?
            LIMIT 1
        ");

        if (!$stmtAlumno) {

            $error =
                'Error al consultar el alumno: '
                . $conn->error;

        } else {

            $stmtAlumno->bind_param(
                "i",
                $alumno_id
            );

            $stmtAlumno->execute();

            $resultAlumno =
                $stmtAlumno->get_result();

            $alumno =
                $resultAlumno->fetch_assoc();

            $stmtAlumno->close();

            if (!$alumno) {

                $error =
                    'El alumno seleccionado no existe.';

            } else {

                // ------------------------------------------------
                // SEGURIDAD EXTRA:
                // UN ALUMNO SOLO PUEDE HACER EL PRÉSTAMO
                // PARA SU PROPIA CUENTA.
                // ------------------------------------------------

                if ($rol === 'alumno') {

                    if (
                        $alumno_id
                        !== $alumno_id_preseleccionado
                    ) {

                        $error =
                            'No puede realizar préstamos '
                            . 'a nombre de otro alumno.';
                    }
                }

                // ------------------------------------------------
                // CARRERA AUTOMÁTICA
                // ------------------------------------------------

                if (
                    empty($carrera_id)
                    && !empty($alumno['carrera_id'])
                ) {

                    $carrera_id =
                        (int) $alumno['carrera_id'];
                }

                $nombre_alumno =
                    $alumno['nombre'];

                // ------------------------------------------------
                // DISPONIBILIDAD
                // ------------------------------------------------

                if (empty($error)) {

                    $stmtDisponibilidad =
                        $conn->prepare("
                            SELECT
                                b.cantidad,
                                (
                                    SELECT COUNT(*)
                                    FROM registro_visitas rv
                                    WHERE rv.bibliografia_id = b.id
                                      AND rv.tipo = 'prestamo'
                                      AND rv.devuelto = 0
                                ) AS prestamos_activos
                            FROM bibliografia b
                            WHERE b.id = ?
                            LIMIT 1
                        ");

                    if (!$stmtDisponibilidad) {

                        $error =
                            'Error comprobando disponibilidad: '
                            . $conn->error;

                    } else {

                        $stmtDisponibilidad->bind_param(
                            "i",
                            $bibliografia_id
                        );

                        $stmtDisponibilidad->execute();

                        $resultDisponibilidad =
                            $stmtDisponibilidad->get_result();

                        $disponibilidad =
                            $resultDisponibilidad->fetch_assoc();

                        $stmtDisponibilidad->close();

                        if (!$disponibilidad) {

                            $error =
                                'No se encontró el libro.';

                        } else {

                            $cantidad_actual =
                                (int) $disponibilidad['cantidad'];

                            $prestamos_actuales =
                                (int) $disponibilidad['prestamos_activos'];

                            if (
                                $prestamos_actuales
                                >= $cantidad_actual
                            ) {

                                $error =
                                    'Este libro no está disponible '
                                    . 'para préstamo.';

                            } else {

                                // --------------------------------
                                // FECHA DE DEVOLUCIÓN
                                // --------------------------------

                                $fecha_devolucion =
                                    date(
                                        'Y-m-d',
                                        strtotime('+3 days')
                                    );

                                // --------------------------------
                                // REGISTRAR
                                // --------------------------------

                                $conn->begin_transaction();

                                try {

                                    $stmtInsert =
                                        $conn->prepare("
                                            INSERT INTO registro_visitas
                                            (
                                                bibliografia_id,
                                                user_id,
                                                tipo,
                                                observaciones,
                                                nombre_alumno,
                                                institucion_id,
                                                carrera_id,
                                                es_externo,
                                                fecha_devolucion_esperada,
                                                devuelto
                                            )
                                            VALUES
                                            (
                                                ?,
                                                ?,
                                                'prestamo',
                                                ?,
                                                ?,
                                                NULL,
                                                ?,
                                                0,
                                                ?,
                                                0
                                            )
                                        ");

                                    if (!$stmtInsert) {

                                        throw new Exception(
                                            'Error preparando el préstamo: '
                                            . $conn->error
                                        );
                                    }

                                    $stmtInsert->bind_param(
                                        "iissis",
                                        $bibliografia_id,
                                        $user_id,
                                        $observaciones,
                                        $nombre_alumno,
                                        $carrera_id,
                                        $fecha_devolucion
                                    );

                                    if (
                                        !$stmtInsert->execute()
                                    ) {

                                        throw new Exception(
                                            'Error al registrar el préstamo: '
                                            . $stmtInsert->error
                                        );
                                    }

                                    $nuevo_prestamo_id =
                                        $stmtInsert->insert_id;

                                    $stmtInsert->close();

                                    // --------------------------------
                                    // ACTUALIZAR RESERVA
                                    // --------------------------------

                                    if ($reserva_id > 0) {

                                        $stmtReserva =
                                            $conn->prepare("
                                                UPDATE reservas_libros
                                                SET estado = 'completada'
                                                WHERE id = ?
                                            ");

                                        if (!$stmtReserva) {

                                            throw new Exception(
                                                'Error preparando la reserva: '
                                                . $conn->error
                                            );
                                        }

                                        $stmtReserva->bind_param(
                                            "i",
                                            $reserva_id
                                        );

                                        if (
                                            !$stmtReserva->execute()
                                        ) {

                                            throw new Exception(
                                                'Error actualizando la reserva: '
                                                . $stmtReserva->error
                                            );
                                        }

                                        $stmtReserva->close();
                                    }

                                    // --------------------------------
                                    // CONFIRMAR
                                    // --------------------------------

                                    $conn->commit();

                                    $_SESSION['success_msg'] =
                                        'Préstamo registrado correctamente. '
                                        . 'Fecha de devolución: '
                                        . date(
                                            'd/m/Y',
                                            strtotime(
                                                $fecha_devolucion
                                            )
                                        );

                                    header(
                                        'Location: ../alertas.php'
                                    );

                                    exit();

                                } catch (Exception $e) {

                                    $conn->rollback();

                                    $error =
                                        $e->getMessage();
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}

// ------------------------------------------------------------
// OBTENER CARRERAS
// ------------------------------------------------------------

$carreras = $conn->query("
    SELECT
        id,
        nombre
    FROM carreras
    ORDER BY nombre ASC
");

// ------------------------------------------------------------
// OBTENER ALUMNOS
// SOLO SE NECESITAN PARA ADMIN / DOCENTE
// ------------------------------------------------------------

$alumnos = null;

if ($rol !== 'alumno') {

    $alumnos = $conn->query("
        SELECT
            id,
            nombre,
            carrera_id
        FROM alumnos
        ORDER BY nombre ASC
    ");

    if (!$alumnos) {

        die(
            'Error obteniendo alumnos: '
            . htmlspecialchars(
                $conn->error,
                ENT_QUOTES,
                'UTF-8'
            )
        );
    }
}

// ------------------------------------------------------------
// HEADER
// ------------------------------------------------------------

include '../includes/header.php';

?>

<style>

/* ============================================================
   FORMULARIO DE PRÉSTAMO
============================================================ */

.prestamo-container {
    width: 100%;
    max-width: 1100px;
    margin: 30px auto;
}

.prestamo-card {
    background: #ffffff;
    border-radius: 18px;
    box-shadow: 0 8px 30px rgba(0,0,0,.08);
    overflow: hidden;
}

.prestamo-header {
    background: linear-gradient(
        135deg,
        #3159d9,
        #436ff0
    );
    color: #ffffff;
    padding: 22px 25px;
}

.prestamo-header h2 {
    margin: 0;
    color: #ffffff !important;
    font-weight: 700;
}

.prestamo-body {
    padding: 28px;
}

.libro-info {
    background: #f5f7fb;
    border-radius: 14px;
    padding: 18px;
    margin-bottom: 25px;
}

.libro-titulo {
    font-size: 24px;
    font-weight: 700;
    color: #263238;
}

.libro-datos {
    color: #6c757d;
    margin-top: 5px;
}

.form-label {
    font-weight: 600;
    color: #263238;
}

.form-control,
.form-select {
    min-height: 48px;
    border-radius: 10px;
}

.form-control:focus,
.form-select:focus {
    border-color: #3159d9;
    box-shadow:
        0 0 0 .2rem rgba(49,89,217,.15);
}

.info-devolucion {
    background: #e8f7ef;
    border-left: 4px solid #198754;
    border-radius: 10px;
    padding: 14px 16px;
    margin-bottom: 20px;
}

/* ============================================================
   ALUMNO SELECCIONADO AUTOMÁTICAMENTE
============================================================ */

.alumno-seleccionado {
    display: flex;
    align-items: center;
    gap: 14px;
    background: #eef4ff;
    border: 2px solid #d8e4ff;
    border-radius: 14px;
    padding: 15px 17px;
}

.alumno-icono {
    width: 48px;
    height: 48px;
    min-width: 48px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #3159d9;
    color: #ffffff;
    border-radius: 12px;
    font-size: 20px;
}

.alumno-info {
    flex: 1;
    min-width: 0;
}

.alumno-info strong {
    display: block;
    color: #172554;
    font-size: 16px;
    font-weight: 700;
}

.alumno-info small {
    display: block;
    color: #64748b;
    margin-top: 3px;
}

.alumno-check {
    color: #198754;
    font-size: 23px;
}

.alumno-ayuda {
    margin-top: 8px;
    color: #64748b;
    font-size: 13px;
}

.botones {
    display: flex;
    justify-content: space-between;
    gap: 10px;
    flex-wrap: wrap;
}

@media (max-width: 576px) {

    .prestamo-container {
        margin: 15px auto;
    }

    .prestamo-body {
        padding: 18px;
    }

    .prestamo-header {
        padding: 18px;
    }

    .prestamo-header h2 {
        font-size: 22px;
    }

    .libro-titulo {
        font-size: 20px;
    }

    .botones {
        flex-direction: column-reverse;
    }

    .botones .btn {
        width: 100%;
    }

    .alumno-seleccionado {
        padding: 13px;
        gap: 10px;
    }

    .alumno-icono {
        width: 42px;
        height: 42px;
        min-width: 42px;
        font-size: 17px;
    }

    .alumno-info strong {
        font-size: 14px;
    }

    .alumno-info small {
        font-size: 11px;
    }
}

</style>


<div class="container-fluid">

    <div class="prestamo-container">

        <div class="prestamo-card">

            <!-- =================================================
                 ENCABEZADO
            ================================================== -->

            <div class="prestamo-header">

                <h2>
                    <i class="fas fa-book-reader me-2"></i>
                    Registrar Préstamo
                </h2>

            </div>


            <!-- =================================================
                 CUERPO
            ================================================== -->

            <div class="prestamo-body">

                <!-- ERROR -->

                <?php if (!empty($error)): ?>

                    <div
                        class="alert alert-danger"
                        role="alert"
                    >

                        <i class="fas fa-exclamation-circle me-2"></i>

                        <?= htmlspecialchars(
                            $error,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>

                    </div>

                <?php endif; ?>


                <!-- =================================================
                     INFORMACIÓN DEL LIBRO
                ================================================== -->

                <div class="libro-info">

                    <div class="libro-titulo">

                        <?= htmlspecialchars(
                            $nombre_libro,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>

                    </div>

                    <div class="libro-datos">

                        <strong>Código:</strong>

                        <?= htmlspecialchars(
                            $codigo_libro,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>

                        &nbsp; | &nbsp;

                        <strong>Ejemplares:</strong>

                        <?= $cantidad_libro ?>

                        &nbsp; | &nbsp;

                        <strong>Prestados:</strong>

                        <?= $prestamos_activos ?>

                    </div>

                </div>


                <?php if (!$disponible): ?>

                    <div
                        class="alert alert-warning"
                        role="alert"
                    >

                        <i class="fas fa-exclamation-triangle me-2"></i>

                        <strong>Libro no disponible.</strong>

                        Todos los ejemplares se encuentran
                        actualmente prestados.

                    </div>


                    <a
                        href="view.php?id=<?= $bibliografia_id ?>"
                        class="btn btn-secondary"
                    >

                        <i class="fas fa-arrow-left me-1"></i>

                        Regresar

                    </a>


                <?php else: ?>


                    <!-- =================================================
                         INFORMACIÓN DEVOLUCIÓN
                    ================================================== -->

                    <div class="info-devolucion">

                        <i class="fas fa-calendar-check me-2"></i>

                        <strong>Fecha de devolución:</strong>

                        <?= date(
                            'd/m/Y',
                            strtotime('+3 days')
                        ) ?>

                        <br>

                        <small class="text-muted">

                            El préstamo tendrá un plazo
                            de 3 días.

                        </small>

                    </div>


                    <!-- =================================================
                         FORMULARIO
                    ================================================== -->

                    <form
                        method="post"
                        action=""
                        id="formPrestamo"
                    >

                        <?php if ($reserva_id > 0): ?>

                            <input
                                type="hidden"
                                name="reserva_id"
                                value="<?= $reserva_id ?>"
                            >

                        <?php endif; ?>


                        <!-- =================================================
                             ALUMNO
                        ================================================== -->

                        <div class="mb-4">

                            <label class="form-label">

                                <i class="fas fa-user-graduate me-1"></i>

                                <?= $rol === 'alumno' ? 'Solicitante' : 'Alumno *' ?>

                            </label>


                            <?php if ($rol === 'alumno'): ?>

                                <!-- =====================================
                                     ALUMNO LOGUEADO
                                ====================================== -->

                                <div class="alumno-seleccionado">

                                    <div class="alumno-icono">

                                        <i class="fas fa-user-graduate"></i>

                                    </div>


                                    <div class="alumno-info">

                                        <strong>

                                            <?= htmlspecialchars(
                                                $nombre_alumno_preseleccionado,
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>

                                        </strong>

                                        <small>

                                            Datos tomados de su cuenta de estudiante.
                                            No necesita seleccionar alumno ni carrera.

                                        </small>

                                    </div>


                                    <div class="alumno-check">

                                        <i class="fas fa-check-circle"></i>

                                    </div>

                                </div>


                                <!--
                                    IMPORTANTE:
                                    El alumno no puede cambiar este ID.
                                -->

                                <input
                                    type="hidden"
                                    name="alumno_id"
                                    value="<?= (int)$alumno_id_preseleccionado ?>"
                                >


                                <div class="alumno-ayuda">

                                    <i class="fas fa-lock me-1"></i>

                                    Su usuario está vinculado
                                    automáticamente a este alumno.

                                </div>


                            <?php else: ?>

                                <!-- =====================================
                                     ADMIN / DOCENTE
                                ====================================== -->

                                <select
                                    name="alumno_id"
                                    id="alumno_id"
                                    class="form-select"
                                    required
                                >

                                    <option value="">

                                        Seleccione un alumno

                                    </option>


                                    <?php if ($alumnos): ?>

                                        <?php while (
                                            $alumnoRow =
                                            $alumnos->fetch_assoc()
                                        ): ?>

                                            <option
                                                value="<?= (int)$alumnoRow['id'] ?>"
                                                data-carrera="<?= (int)($alumnoRow['carrera_id'] ?? 0) ?>"
                                                <?= (
                                                    $alumno_id_preseleccionado
                                                    === (int)$alumnoRow['id']
                                                )
                                                    ? 'selected'
                                                    : ''
                                                ?>
                                            >

                                                <?= htmlspecialchars(
                                                    $alumnoRow['nombre'],
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ) ?>

                                            </option>

                                        <?php endwhile; ?>

                                    <?php endif; ?>

                                </select>


                                <small class="text-muted">

                                    Seleccione un alumno
                                    previamente registrado.

                                </small>

                            <?php endif; ?>

                        </div>


                        <!-- =================================================
                             CARRERA
                        ================================================== -->

                        <?php if ($rol !== 'alumno'): ?>

                            <div class="mb-4">

                                <label
                                    for="carrera_id"
                                    class="form-label"
                                >

                                    <i class="fas fa-graduation-cap me-1"></i>

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

                                    <?php if ($carreras): ?>

                                        <?php while ($c = $carreras->fetch_assoc()): ?>

                                            <option
                                                value="<?= (int)$c['id'] ?>"
                                                <?= $carrera_preseleccionada === (int)$c['id'] ? 'selected' : '' ?>
                                            >
                                                <?= htmlspecialchars(
                                                    $c['nombre'],
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ) ?>
                                            </option>

                                        <?php endwhile; ?>

                                    <?php endif; ?>

                                </select>

                                <small class="text-muted">
                                    Se seleccionará automáticamente según el alumno.
                                </small>

                            </div>

                        <?php else: ?>

                            <!--
                                El alumno YA tiene carrera registrada en la
                                tabla alumnos. No se muestra ningún selector.
                            -->
                            <input
                                type="hidden"
                                name="carrera_id"
                                value="<?= (int)$carrera_preseleccionada ?>"
                            >

                        <?php endif; ?>


                        <!-- =================================================
                             OBSERVACIONES
                        ================================================== -->

                        <div class="mb-4">

                            <label
                                for="observaciones"
                                class="form-label"
                            >

                                <i class="fas fa-comment-alt me-1"></i>

                                Observaciones

                            </label>


                            <textarea
                                name="observaciones"
                                id="observaciones"
                                class="form-control"
                                rows="4"
                                placeholder="Escriba alguna observación del préstamo..."
                            ></textarea>

                        </div>


                        <!-- =================================================
                             BOTONES
                        ================================================== -->

                        <div class="botones">

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
                                id="btnRegistrar"
                                <?= (
                                    $rol === 'alumno'
                                    && $alumno_id_preseleccionado <= 0
                                )
                                    ? 'disabled'
                                    : ''
                                ?>
                            >

                                <i class="fas fa-book-reader me-1"></i>

                                Registrar Préstamo

                            </button>

                        </div>

                    </form>

                <?php endif; ?>

            </div>

        </div>

    </div>

</div>


<script>

// ============================================================
// SELECCIONAR AUTOMÁTICAMENTE LA CARRERA
// PARA ADMINISTRADOR / DOCENTE
// ============================================================

document.addEventListener(
    'DOMContentLoaded',
    function () {

        const alumnoSelect =
            document.getElementById(
                'alumno_id'
            );

        const carreraSelect =
            document.getElementById(
                'carrera_id'
            );


        if (!alumnoSelect || !carreraSelect) {
            return;
        }


        function actualizarCarrera() {

            const option =
                alumnoSelect.options[
                    alumnoSelect.selectedIndex
                ];


            if (!option) {
                return;
            }


            const carrera =
                option.getAttribute(
                    'data-carrera'
                );


            if (
                carrera &&
                carrera !== '0'
            ) {

                carreraSelect.value =
                    carrera;

            } else {

                carreraSelect.value = '';

            }

        }


        alumnoSelect.addEventListener(
            'change',
            actualizarCarrera
        );


        actualizarCarrera();

    }
);


// ============================================================
// EVITAR DOBLE ENVÍO
// ============================================================

const formulario =
    document.getElementById(
        'formPrestamo'
    );


if (formulario) {

    formulario.addEventListener(
        'submit',
        function () {

            const boton =
                document.getElementById(
                    'btnRegistrar'
                );


            if (boton) {

                boton.disabled = true;

                boton.innerHTML =
                    '<i class="fas fa-spinner fa-spin me-1"></i>' +
                    ' Registrando...';

            }

        }
    );

}

</script>


<?php

include '../includes/footer.php';

?>