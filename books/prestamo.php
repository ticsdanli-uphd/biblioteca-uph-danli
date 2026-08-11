<?php

// ============================================================
// books/prestamo.php
// REGISTRAR PRÉSTAMO DE LIBROS
// ============================================================

include '../includes/session.php';
include '../config/db.php';


// ============================================================
// VERIFICAR SESIÓN
// ============================================================

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

$user_id = (int) $_SESSION['user_id'];


// ============================================================
// OBTENER ID DEL LIBRO
// ============================================================

$bibliografia_id = isset($_GET['id'])
    ? (int) $_GET['id']
    : 0;

if ($bibliografia_id <= 0) {

    $_SESSION['error_msg'] = 'Libro no válido.';

    header('Location: list.php');

    exit();
}


// ============================================================
// RESERVA
// ============================================================

$reserva_id = isset($_GET['reserva_id'])
    ? (int) $_GET['reserva_id']
    : 0;


// ============================================================
// DATOS PRESELECCIONADOS
// ============================================================

$alumno_id_preseleccionado = isset($_GET['alumno_id'])
    ? (int) $_GET['alumno_id']
    : 0;

$nombre_alumno_preseleccionado = isset($_GET['nombre_alumno'])
    ? trim($_GET['nombre_alumno'])
    : '';

$carrera_preseleccionada = 0;


// ============================================================
// SI VIENE UN ALUMNO PRESELECCIONADO
// OBTENER SU CARRERA
// ============================================================

if ($alumno_id_preseleccionado > 0) {

    $stmtAlumno = $conn->prepare("
        SELECT id, nombre, carrera_id
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

        $resultAlumno =
            $stmtAlumno->get_result();

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


// ============================================================
// OBTENER INFORMACIÓN DEL LIBRO
// ============================================================

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
        "Error preparando consulta del libro: "
        . htmlspecialchars($conn->error)
    );
}

$stmtLibro->bind_param(
    "i",
    $bibliografia_id
);

$stmtLibro->execute();

$resultLibro =
    $stmtLibro->get_result();

$bibliografia =
    $resultLibro->fetch_assoc();

$stmtLibro->close();


if (!$bibliografia) {

    $_SESSION['error_msg'] =
        'El libro no existe.';

    header('Location: list.php');

    exit();
}


// ============================================================
// DATOS DEL LIBRO
// ============================================================

$nombre_libro =
    $bibliografia['nombre'];

$codigo_libro =
    $bibliografia['codigo'];

$cantidad_libro =
    (int) $bibliografia['cantidad'];

$sede_libro =
    (int) $bibliografia['sede_id'];


// ============================================================
// CONTAR PRÉSTAMOS ACTIVOS
// ============================================================

$stmtActivos = $conn->prepare("
    SELECT COUNT(*) AS total
    FROM registro_visitas
    WHERE bibliografia_id = ?
      AND tipo = 'prestamo'
      AND devuelto = 0
");

if (!$stmtActivos) {

    die(
        "Error consultando préstamos activos: "
        . htmlspecialchars($conn->error)
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


// ============================================================
// DISPONIBILIDAD
// ============================================================

$disponible =
    $prestamos_activos < $cantidad_libro;


// ============================================================
// MENSAJE DE ERROR
// ============================================================

$error = '';


// ============================================================
// PROCESAR FORMULARIO
// ============================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // --------------------------------------------------------
    // DATOS RECIBIDOS
    // --------------------------------------------------------

    $alumno_id =
        isset($_POST['alumno_id'])
        ? (int) $_POST['alumno_id']
        : 0;

    $carrera_id =
        isset($_POST['carrera_id']) &&
        $_POST['carrera_id'] !== ''
        ? (int) $_POST['carrera_id']
        : 0;

    $observaciones =
        isset($_POST['observaciones'])
        ? trim($_POST['observaciones'])
        : '';

    $reserva_id_post =
        isset($_POST['reserva_id'])
        ? (int) $_POST['reserva_id']
        : 0;


    if ($reserva_id_post > 0) {
        $reserva_id = $reserva_id_post;
    }


    // --------------------------------------------------------
    // VALIDAR ALUMNO
    // --------------------------------------------------------

    if ($alumno_id <= 0) {

        $error =
            'Debe seleccionar un alumno de la lista.';

    } else {


        // ----------------------------------------------------
        // OBTENER ALUMNO
        // ----------------------------------------------------

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
                // USAR CARRERA DEL ALUMNO SI EXISTE
                // ------------------------------------------------

                if (
                    empty($carrera_id) &&
                    !empty($alumno['carrera_id'])
                ) {

                    $carrera_id =
                        (int) $alumno['carrera_id'];
                }


                $nombre_alumno =
                    $alumno['nombre'];


                // ------------------------------------------------
                // VOLVER A COMPROBAR DISPONIBILIDAD
                // ------------------------------------------------

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


                            // ------------------------------------
                            // FECHA DE DEVOLUCIÓN
                            // 3 DÍAS
                            // ------------------------------------

                            $fecha_devolucion =
                                date(
                                    'Y-m-d',
                                    strtotime('+3 days')
                                );


                            // ------------------------------------
                            // INICIAR TRANSACCIÓN
                            // ------------------------------------

                            $conn->begin_transaction();


                            try {


                                // --------------------------------
                                // INSERTAR PRÉSTAMO
                                // --------------------------------

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


                                if (!$stmtInsert->execute()) {

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
                                            SET
                                                estado = 'completada'
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


                                // --------------------------------
                                // VOLVER A ALERTAS
                                // --------------------------------

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


// ============================================================
// OBTENER CARRERAS
// ============================================================

$carreras = $conn->query("
    SELECT
        id,
        nombre
    FROM carreras
    ORDER BY nombre ASC
");


// ============================================================
// OBTENER ALUMNOS
// ============================================================

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
        . htmlspecialchars($conn->error)
    );
}


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

    box-shadow:
        0 8px 30px rgba(0,0,0,.08);

    overflow: hidden;

}

.prestamo-header {

    background: linear-gradient(
        135deg,
        #3159d9,
        #436ff0
    );

    color: white;

    padding: 22px 25px;

}

.prestamo-header h2 {

    margin: 0;

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

                        <i
                            class="fas fa-exclamation-circle me-2"
                        ></i>

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

                            <label
                                for="alumno_id"
                                class="form-label"
                            >

                                Alumno *

                            </label>


                            <select
                                name="alumno_id"
                                id="alumno_id"
                                class="form-select"
                                required
                            >

                                <option value="">

                                    Seleccione un alumno

                                </option>


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

                            </select>


                            <small class="text-muted">

                                Seleccione un alumno
                                previamente registrado.

                            </small>

                        </div>


                        <!-- =================================================
                             CARRERA
                        ================================================== -->

                        <div class="mb-4">

                            <label
                                for="carrera_id"
                                class="form-label"
                            >

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

                                    <?php while (
                                        $c =
                                        $carreras->fetch_assoc()
                                    ): ?>

                                        <option
                                            value="<?= (int)$c['id'] ?>"
                                            <?= (
                                                $carrera_preseleccionada
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

                                <?php endif; ?>

                            </select>

                            <small class="text-muted">

                                Se seleccionará automáticamente
                                según el alumno.

                            </small>

                        </div>


                        <!-- =================================================
                             OBSERVACIONES
                        ================================================== -->

                        <div class="mb-4">

                            <label
                                for="observaciones"
                                class="form-label"
                            >

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
// DEL ALUMNO
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

            }

        }


        alumnoSelect.addEventListener(
            'change',
            actualizarCarrera
        );


        // Ejecutar al cargar
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