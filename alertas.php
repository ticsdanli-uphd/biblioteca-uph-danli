<?php

include 'includes/session.php';
include 'config/db.php';

/*
 * =========================================================
 * ACCESO: SOLO ADMINISTRADOR
 * =========================================================
 * Las alertas de préstamos son una herramienta administrativa.
 * Alumnos y docentes NO deben ver esta pantalla.
 */
$rolActual = strtolower(trim(
    $_SESSION['role']
    ?? $_SESSION['tipo']
    ?? $_SESSION['rol']
    ?? ''
));

if (!in_array($rolActual, ['admin', 'administrador'], true)) {
    header('Location: dashboard.php');
    exit;
}


// =========================================================
// SEDE ACTUAL
// =========================================================

$currentSede = isset($_SESSION['sede_seleccionada'])
    ? intval($_SESSION['sede_seleccionada'])
    : 0;


// =========================================================
// CONSULTAR PRÉSTAMOS
// =========================================================

$sql = "
    SELECT
        r.id,
        r.fecha,
        r.fecha_devolucion_esperada,
        r.nombre_alumno,
        r.es_externo,
        r.devuelto,

        u.username,

        b.nombre AS libro_nombre,
        b.codigo AS codigo,
        b.sede_id,

        c.nombre AS carrera_nombre,

        i.nombre AS institucion_nombre,

        a.email AS alumno_email,
        a.telefono AS alumno_telefono

    FROM registro_visitas r

    LEFT JOIN usuarios u
        ON r.user_id = u.id

    LEFT JOIN bibliografia b
        ON r.bibliografia_id = b.id

    LEFT JOIN carreras c
        ON r.carrera_id = c.id

    LEFT JOIN instituciones_externas i
        ON r.institucion_id = i.id

    LEFT JOIN alumnos a
        ON TRIM(LOWER(a.nombre))
        =
        TRIM(LOWER(r.nombre_alumno))

    WHERE r.tipo = 'prestamo'
      AND r.devuelto = 0
";


// =========================================================
// FILTRO POR SEDE
// =========================================================

if ($currentSede > 0) {

    $sql .= "
        AND b.sede_id = " . $currentSede;
}


$sql .= "
    ORDER BY
        CASE

            WHEN r.fecha_devolucion_esperada < CURDATE()
            THEN 1

            WHEN r.fecha_devolucion_esperada = CURDATE()
            THEN 2

            ELSE 3

        END,

        r.fecha_devolucion_esperada ASC
";


$result = $conn->query($sql);


if (!$result) {

    die(
        "Error al consultar préstamos: "
        . htmlspecialchars($conn->error)
    );
}


// =========================================================
// GUARDAR PRÉSTAMOS
// =========================================================

$prestamos = [];

$prestamosActivos = 0;

$prestamosVencidos = 0;

$prestamosPendientes = 0;


while ($row = $result->fetch_assoc()) {

    $prestamos[] = $row;

    $prestamosActivos++;


    if (!empty($row['fecha_devolucion_esperada'])) {

        $fechaDevolucion = strtotime(
            $row['fecha_devolucion_esperada']
        );

        $hoy = strtotime(date('Y-m-d'));


        if ($fechaDevolucion < $hoy) {

            $prestamosVencidos++;

        } else {

            $prestamosPendientes++;

        }
    }
}


include 'includes/header.php';


// =========================================================
// MENSAJES
// =========================================================

if (isset($_SESSION['success'])):

?>

<div class="alert alert-success alert-dismissible fade show shadow-sm">

    <i class="fas fa-check-circle me-2"></i>

    <?= htmlspecialchars($_SESSION['success']) ?>

    <button
        type="button"
        class="btn-close"
        data-bs-dismiss="alert">
    </button>

</div>

<?php

unset($_SESSION['success']);

endif;


if (isset($_SESSION['error'])):

?>

<div class="alert alert-danger alert-dismissible fade show shadow-sm">

    <i class="fas fa-exclamation-circle me-2"></i>

    <?= htmlspecialchars($_SESSION['error']) ?>

    <button
        type="button"
        class="btn-close"
        data-bs-dismiss="alert">
    </button>

</div>

<?php

unset($_SESSION['error']);

endif;

?>


<div class="container-fluid px-3 px-md-4 py-4">


    <!-- =====================================================
         ENCABEZADO
    ====================================================== -->

    <div class="card border-0 shadow-sm mb-4">

        <div class="card-body">

            <div class="
                d-flex
                flex-column
                flex-md-row
                justify-content-between
                align-items-start
                align-items-md-center
                gap-3
            ">

                <div>

                    <h1 class="h2 fw-bold mb-1">

                        <i class="fas fa-bell text-primary me-2"></i>

                        Alertas de Préstamos - Administración

                    </h1>

                    <p class="text-muted mb-0">

                        Préstamos pendientes de devolución
                        de la Biblioteca UPH.

                    </p>

                </div>


                <div>

                    <span class="badge bg-success fs-6 px-3 py-2">

                        <i class="fas fa-map-marker-alt me-1"></i>

                        <?= isset($_SESSION['sede_nombre'])
                            ? htmlspecialchars($_SESSION['sede_nombre'])
                            : 'Sede actual'
                        ?>

                    </span>

                </div>

            </div>

        </div>

    </div>



    <!-- =====================================================
         ESTADÍSTICAS
    ====================================================== -->

    <div class="row g-3 mb-4">


        <!-- ACTIVOS -->

        <div class="col-12 col-md-4">

            <div class="card border-0 shadow-sm h-100">

                <div class="card-body">

                    <div class="d-flex align-items-center">

                        <div class="
                            rounded-3
                            bg-primary
                            bg-opacity-10
                            text-primary
                            p-3
                            me-3
                        ">

                            <i class="fas fa-book fa-lg"></i>

                        </div>

                        <div>

                            <div class="text-muted">
                                Préstamos activos
                            </div>

                            <div class="fs-3 fw-bold">

                                <?= $prestamosActivos ?>

                            </div>

                        </div>

                    </div>

                </div>

            </div>

        </div>



        <!-- VENCIDOS -->

        <div class="col-12 col-md-4">

            <div class="card border-0 shadow-sm h-100">

                <div class="card-body">

                    <div class="d-flex align-items-center">

                        <div class="
                            rounded-3
                            bg-danger
                            bg-opacity-10
                            text-danger
                            p-3
                            me-3
                        ">

                            <i class="
                                fas
                                fa-exclamation-triangle
                                fa-lg
                            "></i>

                        </div>

                        <div>

                            <div class="text-muted">
                                Préstamos vencidos
                            </div>

                            <div class="
                                fs-3
                                fw-bold
                                text-danger
                            ">

                                <?= $prestamosVencidos ?>

                            </div>

                        </div>

                    </div>

                </div>

            </div>

        </div>



        <!-- PENDIENTES -->

        <div class="col-12 col-md-4">

            <div class="card border-0 shadow-sm h-100">

                <div class="card-body">

                    <div class="d-flex align-items-center">

                        <div class="
                            rounded-3
                            bg-warning
                            bg-opacity-10
                            text-warning
                            p-3
                            me-3
                        ">

                            <i class="fas fa-clock fa-lg"></i>

                        </div>

                        <div>

                            <div class="text-muted">
                                Pendientes
                            </div>

                            <div class="
                                fs-3
                                fw-bold
                                text-warning
                            ">

                                <?= $prestamosPendientes ?>

                            </div>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>



    <!-- =====================================================
         MENSAJE
    ====================================================== -->

    <?php if ($prestamosActivos == 0): ?>

        <div class="
            alert
            alert-success
            shadow-sm
        ">

            <i class="fas fa-check-circle me-2"></i>

            <strong>Todo está al día.</strong>

            No existen préstamos pendientes.

        </div>

    <?php else: ?>

        <div class="
            alert
            alert-info
            shadow-sm
        ">

            <i class="fas fa-info-circle me-2"></i>

            Hay

            <strong>
                <?= $prestamosActivos ?>
            </strong>

            préstamo(s) activo(s).

        </div>

    <?php endif; ?>



    <!-- =====================================================
         TABLA
    ====================================================== -->

    <div class="card border-0 shadow-sm">

        <div class="card-body p-0">

            <div class="table-responsive">

                <table
                    id="tablaAlertas"
                    class="
                        table
                        table-hover
                        align-middle
                        mb-0
                    "
                >

                    <thead class="table-primary">

                        <tr>

                            <th>Fecha Préstamo</th>

                            <th>Libro</th>

                            <th>Código</th>

                            <th>Usuario</th>

                            <th>Fecha Devolución</th>

                            <th>Alumno</th>

                            <th>Carrera</th>

                            <th>Institución</th>

                            <th>Correo</th>

                            <th>Teléfono</th>

                            <th>Estado</th>

                            <th>Acción</th>

                        </tr>

                    </thead>


                    <tbody>


                    <?php foreach ($prestamos as $row): ?>


                        <?php

                        $hoy = strtotime(
                            date('Y-m-d')
                        );


                        $fechaDevolucion = !empty(
                            $row['fecha_devolucion_esperada']
                        )
                            ? strtotime(
                                $row['fecha_devolucion_esperada']
                            )
                            : 0;


                        $diasRestantes = null;


                        if ($fechaDevolucion) {

                            $diasRestantes = floor(

                                (
                                    $fechaDevolucion
                                    -
                                    $hoy
                                )
                                / 86400

                            );

                        }


                        if (
                            $diasRestantes !== null
                            &&
                            $diasRestantes < 0
                        ) {

                            $estado = 'Vencido';

                            $estadoClase = 'danger';

                            $filaClase = 'table-danger';

                        }

                        elseif (
                            $diasRestantes !== null
                            &&
                            $diasRestantes == 0
                        ) {

                            $estado = 'Vence hoy';

                            $estadoClase = 'warning';

                            $filaClase = 'table-warning';

                        }

                        elseif (
                            $diasRestantes !== null
                            &&
                            $diasRestantes == 1
                        ) {

                            $estado = 'Vence mañana';

                            $estadoClase = 'warning';

                            $filaClase = 'table-warning';

                        }

                        else {

                            $estado = 'Pendiente';

                            $estadoClase = 'primary';

                            $filaClase = '';

                        }


                        $email = trim(
                            $row['alumno_email'] ?? ''
                        );

                        ?>


                        <tr class="<?= $filaClase ?>">


                            <td>

                                <?= !empty($row['fecha'])
                                    ? date(
                                        'd/m/Y H:i',
                                        strtotime($row['fecha'])
                                    )
                                    : 'N/A'
                                ?>

                            </td>


                            <td>

                                <strong>

                                    <?= htmlspecialchars(
                                        $row['libro_nombre']
                                        ?? 'N/A'
                                    ) ?>

                                </strong>

                            </td>


                            <td>

                                <span class="badge bg-secondary">

                                    <?= htmlspecialchars(
                                        $row['codigo']
                                        ?? 'N/A'
                                    ) ?>

                                </span>

                            </td>


                            <td>

                                <?= htmlspecialchars(
                                    $row['username']
                                    ?? 'N/A'
                                ) ?>

                            </td>


                            <td>

                                <?= !empty(
                                    $row['fecha_devolucion_esperada']
                                )
                                    ? date(
                                        'd/m/Y',
                                        strtotime(
                                            $row[
                                                'fecha_devolucion_esperada'
                                            ]
                                        )
                                    )
                                    : 'N/A'
                                ?>

                            </td>


                            <td>

                                <strong>

                                    <?= htmlspecialchars(
                                        $row['nombre_alumno']
                                        ?? 'N/A'
                                    ) ?>

                                </strong>

                            </td>


                            <td>

                                <?= htmlspecialchars(
                                    $row['carrera_nombre']
                                    ?? 'No especificada'
                                ) ?>

                            </td>


                            <td>

                                <?= htmlspecialchars(
                                    $row['institucion_nombre']
                                    ?? 'No especificada'
                                ) ?>

                            </td>


                            <td>

                                <?php if (!empty($email)): ?>

                                    <a
                                        href="mailto:<?= htmlspecialchars($email) ?>"
                                        class="text-decoration-none"
                                    >

                                        <i class="
                                            fas
                                            fa-envelope
                                            me-1
                                        "></i>

                                        <?= htmlspecialchars($email) ?>

                                    </a>

                                <?php else: ?>

                                    <span class="text-danger">

                                        <i class="
                                            fas
                                            fa-times-circle
                                        "></i>

                                        Sin correo

                                    </span>

                                <?php endif; ?>

                            </td>


                            <td>

                                <?php if (
                                    !empty(
                                        $row['alumno_telefono']
                                    )
                                ): ?>

                                    <a
                                        href="tel:<?= htmlspecialchars(
                                            $row['alumno_telefono']
                                        ) ?>"
                                        class="text-decoration-none"
                                    >

                                        <i class="
                                            fas
                                            fa-phone
                                            me-1
                                        "></i>

                                        <?= htmlspecialchars(
                                            $row[
                                                'alumno_telefono'
                                            ]
                                        ) ?>

                                    </a>

                                <?php else: ?>

                                    <span class="text-muted">
                                        No registrado
                                    </span>

                                <?php endif; ?>

                            </td>


                            <td>

                                <span
                                    class="
                                        badge
                                        bg-<?= $estadoClase ?>
                                    "
                                >

                                    <?= $estado ?>

                                </span>

                            </td>


                            <td>

                                <?php if (!empty($email)): ?>

                                    <button
                                        type="button"
                                        class="
                                            btn
                                            btn-primary
                                            btn-sm
                                        "
                                        onclick="abrirCorreo(
                                            <?= (int)$row['id'] ?>,
                                            '<?= htmlspecialchars(
                                                $row['nombre_alumno'],
                                                ENT_QUOTES
                                            ) ?>',
                                            '<?= htmlspecialchars(
                                                $email,
                                                ENT_QUOTES
                                            ) ?>'
                                        )"
                                    >

                                        <i class="
                                            fas
                                            fa-envelope
                                            me-1
                                        "></i>

                                        Enviar correo

                                    </button>

                                <?php else: ?>

                                    <button
                                        type="button"
                                        class="
                                            btn
                                            btn-secondary
                                            btn-sm
                                        "
                                        disabled
                                    >

                                        <i class="
                                            fas
                                            fa-envelope-slash
                                        "></i>

                                        Sin correo

                                    </button>

                                <?php endif; ?>


                                <a
                                    href="marcar_devueltos.php?id=<?= (int)$row['id'] ?>"
                                    class="
                                        btn
                                        btn-success
                                        btn-sm
                                        mt-1
                                    "
                                    onclick="
                                        return confirm(
                                            '¿Confirmas que el préstamo fue devuelto?'
                                        );
                                    "
                                >

                                    <i class="
                                        fas
                                        fa-check
                                        me-1
                                    "></i>

                                    Marcar devuelto

                                </a>

                            </td>


                        </tr>


                    <?php endforeach; ?>


                    </tbody>

                </table>

            </div>

        </div>

    </div>

</div>



<!-- =====================================================
     MODAL PARA SELECCIONAR CORREO
====================================================== -->

<div
    class="modal fade"
    id="modalCorreo"
    tabindex="-1"
    aria-hidden="true"
>

    <div class="modal-dialog modal-dialog-centered">

        <div class="modal-content border-0 shadow">

            <div class="modal-header">

                <h5 class="modal-title">

                    <i class="
                        fas
                        fa-envelope
                        text-primary
                        me-2
                    "></i>

                    Enviar recordatorio

                </h5>

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="modal"
                ></button>

            </div>


            <div class="modal-body">

                <div class="mb-3">

                    <strong>Alumno:</strong>

                    <div id="modalAlumno"
                         class="text-muted">
                    </div>

                </div>


                <div class="mb-3">

                    <strong>Correo del alumno:</strong>

                    <div id="modalCorreoAlumno"
                         class="text-primary">
                    </div>

                </div>


                <hr>


                <label
                    for="correo_remitente"
                    class="form-label fw-bold"
                >

                    Enviar desde:

                </label>


                <select
                    id="correo_remitente"
                    class="form-select"
                >

                    <option value="biblioteca">

                        📚 Biblioteca UPH Danlí
                        (biblioteca.danli@uph.edu.hn)

                    </option>


                    <option value="tics">

                        💻 TICS Danlí
                        (tics.danli@uph.edu.hn)

                    </option>

                </select>


                <div class="
                    alert
                    alert-info
                    mt-3
                    mb-0
                ">

                    <i class="
                        fas
                        fa-info-circle
                        me-1
                    "></i>

                    Selecciona el correo institucional
                    desde el cual deseas enviar el recordatorio.

                </div>

            </div>


            <div class="modal-footer">

                <button
                    type="button"
                    class="btn btn-secondary"
                    data-bs-dismiss="modal"
                >

                    Cancelar

                </button>


                <button
                    type="button"
                    class="btn btn-primary"
                    onclick="enviarCorreo()"
                >

                    <i class="
                        fas
                        fa-paper-plane
                        me-1
                    "></i>

                    Enviar correo

                </button>

            </div>

        </div>

    </div>

</div>



<script>

let prestamoSeleccionado = 0;


// =====================================================
// ABRIR MODAL
// =====================================================

function abrirCorreo(
    id,
    nombre,
    correo
) {

    prestamoSeleccionado = id;


    document.getElementById(
        'modalAlumno'
    ).textContent = nombre;


    document.getElementById(
        'modalCorreoAlumno'
    ).textContent = correo;


    const modal =
        new bootstrap.Modal(
            document.getElementById(
                'modalCorreo'
            )
        );


    modal.show();

}


// =====================================================
// ENVIAR CORREO
// =====================================================

function enviarCorreo() {

    const remitente =
        document.getElementById(
            'correo_remitente'
        ).value;


    if (!prestamoSeleccionado) {

        alert(
            'No se ha seleccionado un préstamo.'
        );

        return;

    }


    window.location.href =
        'enviar_recordatorio.php?id='
        +
        encodeURIComponent(
            prestamoSeleccionado
        )
        +
        '&remitente='
        +
        encodeURIComponent(
            remitente
        );

}


// =====================================================
// DATATABLE
// =====================================================

$(document).ready(function () {

    $('#tablaAlertas').DataTable({

        responsive: true,

        pageLength: 10,

        order: [
            [4, 'asc']
        ],

        language: {

            search: 'Buscar:',

            lengthMenu:
                'Mostrar _MENU_ registros',

            info:
                'Mostrando _START_ a _END_ de _TOTAL_ registros',

            infoEmpty:
                'No hay registros',

            zeroRecords:
                'No se encontraron préstamos',

            paginate: {

                first: 'Primero',

                last: 'Último',

                next: 'Siguiente',

                previous: 'Anterior'

            }

        }

    });

});

</script>


<style>

#tablaAlertas th {

    white-space: nowrap;

    vertical-align: middle;

}


#tablaAlertas td {

    vertical-align: middle;

}


.btn-sm {

    white-space: nowrap;

}


.table-danger {

    --bs-table-bg: #fff1f1;

}


.table-warning {

    --bs-table-bg: #fff9e6;

}


@media (max-width: 768px) {

    .container-fluid {

        padding-left: 10px !important;

        padding-right: 10px !important;

    }


    h1.h2 {

        font-size: 1.5rem;

    }


    .card-body {

        padding: 1rem;

    }

}

</style>


<?php

include 'includes/footer.php';

?>