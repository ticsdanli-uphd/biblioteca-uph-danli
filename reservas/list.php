<?php

include '../includes/session.php';
include '../config/db.php';


// ======================================================
// VERIFICAR PERMISOS
// ======================================================

if (
    !isset($_SESSION['user_id']) ||
    $_SESSION['role'] !== 'admin'
) {
    header('Location: ../index.php');
    exit();
}


// ======================================================
// CONFIGURACIÓN
// SISTEMA EXCLUSIVO PARA DANLÍ
// ======================================================

$sede_id = 6;
$sede_nombre = 'Danlí';


// ======================================================
// MENSAJES
// ======================================================

$msg = '';


// ======================================================
// PROCESAR CAMBIOS DE ESTADO
// ======================================================

if (
    isset($_GET['action']) &&
    isset($_GET['id'])
) {

    $reserva_id = intval($_GET['id']);

    $action = $_GET['action'];


    if ($reserva_id > 0) {

        switch ($action) {

            // ------------------------------------------
            // NOTIFICAR
            // ------------------------------------------

            case 'notificar':

                $stmt = $conn->prepare("
                    UPDATE reservas_libros
                    SET estado = 'notificada'
                    WHERE id = ?
                ");

                $stmt->bind_param(
                    'i',
                    $reserva_id
                );

                $stmt->execute();

                $stmt->close();

                $msg = 'notificada_ok';

                break;


            // ------------------------------------------
            // COMPLETAR
            // ------------------------------------------

            case 'completar':

                $stmt = $conn->prepare("
                    UPDATE reservas_libros
                    SET estado = 'completada'
                    WHERE id = ?
                ");

                $stmt->bind_param(
                    'i',
                    $reserva_id
                );

                $stmt->execute();

                $stmt->close();

                $msg = 'completada_ok';

                break;


            // ------------------------------------------
            // CANCELAR
            // ------------------------------------------

            case 'cancelar':

                $stmt = $conn->prepare("
                    UPDATE reservas_libros
                    SET estado = 'cancelada'
                    WHERE id = ?
                ");

                $stmt->bind_param(
                    'i',
                    $reserva_id
                );

                $stmt->execute();

                $stmt->close();

                $msg = 'cancelada_ok';

                break;
        }


        // Redireccionar para evitar repetir acción
        if (!empty($msg)) {

            header(
                "Location: list.php?msg="
                . urlencode($msg)
            );

            exit();
        }
    }
}


// ======================================================
// FILTRO POR ESTADO
// ======================================================

$estado_filter =
    isset($_GET['estado'])
    ? trim($_GET['estado'])
    : '';


// ======================================================
// VALIDAR ESTADOS PERMITIDOS
// ======================================================

$estados_permitidos = [
    'pendiente',
    'notificada',
    'completada',
    'cancelada'
];


if (
    !empty($estado_filter) &&
    !in_array(
        $estado_filter,
        $estados_permitidos,
        true
    )
) {

    $estado_filter = '';

}


// ======================================================
// CONSTRUIR FILTRO
// ======================================================

$where_estado = '';

$params = [];

$types = '';


if (!empty($estado_filter)) {

    $where_estado =
        " AND r.estado = ? ";

    $params[] =
        $estado_filter;

    $types .= 's';

}


// ======================================================
// CONSULTA
// EXCLUSIVAMENTE SEDE DANLÍ
// ======================================================

$sql = "

    SELECT

        r.*,

        b.nombre AS libro_nombre,

        b.autores AS libro_autores,

        b.cantidad AS libro_cantidad,

        b.sede_id,

        c.nombre AS carrera_nombre,

        u.username,

        u.nombre AS usuario_nombre,

        (

            SELECT COUNT(*)

            FROM registro_visitas rv

            WHERE
                rv.bibliografia_id =
                r.bibliografia_id

                AND rv.tipo = 'prestamo'

                AND rv.devuelto = 0

        ) AS prestamos_activos

    FROM reservas_libros r

    LEFT JOIN bibliografia b
        ON r.bibliografia_id = b.id

    LEFT JOIN usuarios u
        ON r.user_id = u.id

    LEFT JOIN carreras c
        ON r.carrera_id = c.id

    WHERE
        b.sede_id = 6

        $where_estado

    ORDER BY

        CASE r.estado

            WHEN 'notificada' THEN 1

            WHEN 'pendiente' THEN 2

            WHEN 'completada' THEN 3

            WHEN 'cancelada' THEN 4

            ELSE 5

        END,

        r.fecha_reserva DESC

";


$stmt = $conn->prepare($sql);


if (!$stmt) {

    die(
        'Error preparando consulta: '
        . $conn->error
    );

}


if (!empty($params)) {

    $stmt->bind_param(
        $types,
        ...$params
    );

}


$stmt->execute();


$result =
    $stmt->get_result();


// ======================================================
// CONTADORES
// ======================================================

$total_reservas = 0;

$total_pendientes = 0;

$total_notificadas = 0;

$total_completadas = 0;

$total_canceladas = 0;


$reservas = [];


while (
    $row = $result->fetch_assoc()
) {

    $reservas[] = $row;

    $total_reservas++;


    switch ($row['estado']) {

        case 'pendiente':

            $total_pendientes++;

            break;

        case 'notificada':

            $total_notificadas++;

            break;

        case 'completada':

            $total_completadas++;

            break;

        case 'cancelada':

            $total_canceladas++;

            break;
    }

}


$stmt->close();


// ======================================================
// HEADER
// ======================================================

include '../includes/header.php';

?>


<div class="container-fluid py-4">


    <!-- ==================================================
         ENCABEZADO
    ================================================== -->

    <div class="reservas-header mb-4">

        <div>

            <h1>

                <i
                    class="
                        fas
                        fa-bookmark
                        me-2
                    "
                ></i>

                Gestión de Reservas

            </h1>


            <p>

                Administración de reservas de libros
                de la Biblioteca UPH.

            </p>

        </div>


        <div class="sede-badge">

            <i
                class="
                    fas
                    fa-map-marker-alt
                    me-1
                "
            ></i>

            Sede Danlí

        </div>

    </div>



    <!-- ==================================================
         MENSAJES
    ================================================== -->

    <?php if (
        isset($_GET['msg'])
    ): ?>


        <?php if (
            $_GET['msg'] ===
            'notificada_ok'
        ): ?>

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

                La reserva ha sido marcada
                como notificada correctamente.

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="alert"
                ></button>

            </div>

        <?php endif; ?>


        <?php if (
            $_GET['msg'] ===
            'completada_ok'
        ): ?>

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

                La reserva ha sido completada
                correctamente.

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="alert"
                ></button>

            </div>

        <?php endif; ?>


        <?php if (
            $_GET['msg'] ===
            'cancelada_ok'
        ): ?>

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

                La reserva ha sido cancelada
                correctamente.

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="alert"
                ></button>

            </div>

        <?php endif; ?>


    <?php endif; ?>



    <!-- ==================================================
         ESTADÍSTICAS
    ================================================== -->

    <div class="row g-3 mb-4">


        <!-- TOTAL -->

        <div class="col-md-3">

            <div class="estadistica">

                <div class="estadistica-icon bg-primary">

                    <i
                        class="
                            fas
                            fa-bookmark
                        "
                    ></i>

                </div>

                <div>

                    <small>
                        Total
                    </small>

                    <h3>
                        <?php
                        echo $total_reservas;
                        ?>
                    </h3>

                </div>

            </div>

        </div>


        <!-- PENDIENTES -->

        <div class="col-md-3">

            <div class="estadistica">

                <div class="estadistica-icon bg-warning">

                    <i
                        class="
                            fas
                            fa-clock
                        "
                    ></i>

                </div>

                <div>

                    <small>
                        Pendientes
                    </small>

                    <h3 class="text-warning">
                        <?php
                        echo $total_pendientes;
                        ?>
                    </h3>

                </div>

            </div>

        </div>


        <!-- NOTIFICADAS -->

        <div class="col-md-3">

            <div class="estadistica">

                <div class="estadistica-icon bg-info">

                    <i
                        class="
                            fas
                            fa-bell
                        "
                    ></i>

                </div>

                <div>

                    <small>
                        Notificadas
                    </small>

                    <h3 class="text-info">
                        <?php
                        echo $total_notificadas;
                        ?>
                    </h3>

                </div>

            </div>

        </div>


        <!-- COMPLETADAS -->

        <div class="col-md-3">

            <div class="estadistica">

                <div class="estadistica-icon bg-success">

                    <i
                        class="
                            fas
                            fa-check
                        "
                    ></i>

                </div>

                <div>

                    <small>
                        Completadas
                    </small>

                    <h3 class="text-success">
                        <?php
                        echo $total_completadas;
                        ?>
                    </h3>

                </div>

            </div>

        </div>


    </div>



    <!-- ==================================================
         CARD PRINCIPAL
    ================================================== -->

    <div class="card reservas-card shadow-sm">


        <!-- HEADER -->

        <div
            class="
                card-header
                reservas-card-header
            "
        >

            <div>

                <h5>

                    <i
                        class="
                            fas
                            fa-list
                            me-2
                        "
                    ></i>

                    Listado de Reservas

                </h5>


                <small>

                    <?php
                    echo $total_reservas;
                    ?>

                    reserva(s) registrada(s)
                    en Danlí.

                </small>

            </div>


            <!-- FILTRO -->

            <div class="dropdown">

                <button
                    class="
                        btn
                        btn-outline-primary
                        dropdown-toggle
                    "
                    type="button"
                    data-bs-toggle="dropdown"
                >

                    <i
                        class="
                            fas
                            fa-filter
                            me-1
                        "
                    ></i>

                    <?php

                    if (
                        $estado_filter ===
                        'pendiente'
                    ) {

                        echo 'Pendientes';

                    } elseif (
                        $estado_filter ===
                        'notificada'
                    ) {

                        echo 'Notificadas';

                    } elseif (
                        $estado_filter ===
                        'completada'
                    ) {

                        echo 'Completadas';

                    } elseif (
                        $estado_filter ===
                        'cancelada'
                    ) {

                        echo 'Canceladas';

                    } else {

                        echo 'Todos los estados';

                    }

                    ?>

                </button>


                <ul
                    class="
                        dropdown-menu
                        dropdown-menu-end
                    "
                >

                    <li>

                        <a
                            class="dropdown-item"
                            href="list.php"
                        >

                            Todos los estados

                        </a>

                    </li>


                    <li>

                        <a
                            class="dropdown-item"
                            href="list.php?estado=pendiente"
                        >

                            <i
                                class="
                                    fas
                                    fa-clock
                                    text-warning
                                    me-2
                                "
                            ></i>

                            Pendientes

                        </a>

                    </li>


                    <li>

                        <a
                            class="dropdown-item"
                            href="list.php?estado=notificada"
                        >

                            <i
                                class="
                                    fas
                                    fa-bell
                                    text-info
                                    me-2
                                "
                            ></i>

                            Notificadas

                        </a>

                    </li>


                    <li>

                        <a
                            class="dropdown-item"
                            href="list.php?estado=completada"
                        >

                            <i
                                class="
                                    fas
                                    fa-check
                                    text-success
                                    me-2
                                "
                            ></i>

                            Completadas

                        </a>

                    </li>


                    <li>

                        <a
                            class="dropdown-item"
                            href="list.php?estado=cancelada"
                        >

                            <i
                                class="
                                    fas
                                    fa-times
                                    text-danger
                                    me-2
                                "
                            ></i>

                            Canceladas

                        </a>

                    </li>

                </ul>

            </div>

        </div>


        <!-- BODY -->

        <div class="card-body">


            <?php if (
                $total_reservas === 0
            ): ?>


                <!-- ==========================================
                     SIN RESERVAS
                =========================================== -->

                <div class="sin-reservas">

                    <div class="sin-reservas-icon">

                        <i
                            class="
                                fas
                                fa-book-open
                            "
                        ></i>

                    </div>


                    <h4>

                        No hay reservas

                    </h4>


                    <p>

                        Actualmente no existen reservas
                        registradas para la sede Danlí.

                    </p>


                </div>


            <?php else: ?>


                <!-- ==========================================
                     TABLA
                =========================================== -->

                <div class="table-responsive">

                    <table
                        class="
                            table
                            table-hover
                            align-middle
                        "
                        id="dataTable"
                    >

                        <thead>

                            <tr>

                                <th>
                                    ID
                                </th>

                                <th>
                                    Libro
                                </th>

                                <th>
                                    Alumno
                                </th>

                                <th>
                                    Carrera
                                </th>

                                <th>
                                    Fecha Reserva
                                </th>

                                <th>
                                    Disponibilidad Estimada
                                </th>

                                <th>
                                    Estado
                                </th>

                                <th>
                                    Disponibilidad
                                </th>

                                <th>
                                    Acciones
                                </th>

                            </tr>

                        </thead>


                        <tbody>


                        <?php foreach (
                            $reservas
                            as $row
                        ): ?>


                            <?php

                            // ======================================
                            // DISPONIBILIDAD
                            // ======================================

                            $cantidad =
                                intval(
                                    $row[
                                        'libro_cantidad'
                                    ]
                                    ?? 0
                                );


                            $prestamos =
                                intval(
                                    $row[
                                        'prestamos_activos'
                                    ]
                                    ?? 0
                                );


                            $disponible =
                                $cantidad >
                                $prestamos;

                            ?>


                            <tr>


                                <!-- ID -->

                                <td>

                                    <strong>

                                        #
                                        <?php
                                        echo intval(
                                            $row['id']
                                        );
                                        ?>

                                    </strong>

                                </td>


                                <!-- LIBRO -->

                                <td>

                                    <a
                                        href="../books/view.php?id=<?php
                                            echo intval(
                                                $row[
                                                    'bibliografia_id'
                                                ]
                                            );
                                        ?>"
                                        class="
                                            libro-link
                                            text-decoration-none
                                        "
                                    >

                                        <strong>

                                            <?php
                                            echo htmlspecialchars(
                                                $row[
                                                    'libro_nombre'
                                                ]
                                                ??
                                                'Sin nombre'
                                            );
                                            ?>

                                        </strong>

                                    </a>


                                    <?php if (
                                        !empty(
                                            $row[
                                                'libro_autores'
                                            ]
                                        )
                                    ): ?>

                                        <div
                                            class="
                                                small
                                                text-muted
                                            "
                                        >

                                            <?php
                                            echo htmlspecialchars(
                                                $row[
                                                    'libro_autores'
                                                ]
                                            );
                                            ?>

                                        </div>

                                    <?php endif; ?>


                                </td>


                                <!-- ALUMNO -->

                                <td>

                                    <i
                                        class="
                                            fas
                                            fa-user
                                            text-primary
                                            me-1
                                        "
                                    ></i>

                                    <?php
                                    echo htmlspecialchars(
                                        $row[
                                            'nombre_alumno'
                                        ]
                                        ??
                                        'No especificado'
                                    );
                                    ?>

                                </td>


                                <!-- CARRERA -->

                                <td>

                                    <?php
                                    echo htmlspecialchars(
                                        $row[
                                            'carrera_nombre'
                                        ]
                                        ??
                                        'No especificada'
                                    );
                                    ?>

                                </td>


                                <!-- FECHA RESERVA -->

                                <td>

                                    <?php

                                    if (
                                        !empty(
                                            $row[
                                                'fecha_reserva'
                                            ]
                                        )
                                    ) {

                                        echo date(
                                            "d/m/Y H:i",
                                            strtotime(
                                                $row[
                                                    'fecha_reserva'
                                                ]
                                            )
                                        );

                                    } else {

                                        echo 'N/A';

                                    }

                                    ?>

                                </td>


                                <!-- DISPONIBILIDAD ESTIMADA -->

                                <td>

                                    <?php

                                    if (
                                        !empty(
                                            $row[
                                                'fecha_disponibilidad_estimada'
                                            ]
                                        )
                                    ) {

                                        echo date(
                                            "d/m/Y",
                                            strtotime(
                                                $row[
                                                    'fecha_disponibilidad_estimada'
                                                ]
                                            )
                                        );

                                    } else {

                                        echo '
                                            <span class="text-muted">
                                                No estimada
                                            </span>
                                        ';

                                    }

                                    ?>

                                </td>


                                <!-- ESTADO -->

                                <td>

                                    <?php

                                    switch (
                                        $row['estado']
                                    ):

                                        case 'pendiente':

                                    ?>

                                        <span
                                            class="
                                                badge
                                                bg-warning
                                                text-dark
                                            "
                                        >

                                            <i
                                                class="
                                                    fas
                                                    fa-clock
                                                    me-1
                                                "
                                            ></i>

                                            Pendiente

                                        </span>

                                    <?php

                                        break;

                                        case 'notificada':

                                    ?>

                                        <span
                                            class="
                                                badge
                                                bg-info
                                            "
                                        >

                                            <i
                                                class="
                                                    fas
                                                    fa-bell
                                                    me-1
                                                "
                                            ></i>

                                            Notificada

                                        </span>

                                    <?php

                                        break;

                                        case 'completada':

                                    ?>

                                        <span
                                            class="
                                                badge
                                                bg-success
                                            "
                                        >

                                            <i
                                                class="
                                                    fas
                                                    fa-check
                                                    me-1
                                                "
                                            ></i>

                                            Completada

                                        </span>

                                    <?php

                                        break;

                                        case 'cancelada':

                                    ?>

                                        <span
                                            class="
                                                badge
                                                bg-danger
                                            "
                                        >

                                            <i
                                                class="
                                                    fas
                                                    fa-times
                                                    me-1
                                                "
                                            ></i>

                                            Cancelada

                                        </span>

                                    <?php

                                        break;

                                        default:

                                    ?>

                                        <span
                                            class="
                                                badge
                                                bg-secondary
                                            "
                                        >

                                            <?php
                                            echo htmlspecialchars(
                                                $row['estado']
                                            );
                                            ?>

                                        </span>

                                    <?php

                                    endswitch;

                                    ?>

                                </td>


                                <!-- DISPONIBILIDAD -->

                                <td>

                                    <?php if (
                                        $disponible
                                    ): ?>

                                        <span
                                            class="
                                                badge
                                                bg-success
                                            "
                                        >

                                            <i
                                                class="
                                                    fas
                                                    fa-check-circle
                                                    me-1
                                                "
                                            ></i>

                                            Disponible

                                        </span>

                                    <?php else: ?>

                                        <span
                                            class="
                                                badge
                                                bg-danger
                                            "
                                        >

                                            <i
                                                class="
                                                    fas
                                                    fa-times-circle
                                                    me-1
                                                "
                                            ></i>

                                            No disponible

                                        </span>

                                    <?php endif; ?>

                                </td>


                                <!-- ACCIONES -->

                                <td>

                                    <div
                                        class="
                                            dropdown
                                        "
                                    >

                                        <button
                                            type="button"
                                            class="
                                                btn
                                                btn-sm
                                                btn-primary
                                                dropdown-toggle
                                            "
                                            data-bs-toggle="dropdown"
                                        >

                                            <i
                                                class="
                                                    fas
                                                    fa-cog
                                                    me-1
                                                "
                                            ></i>

                                            Acciones

                                        </button>


                                        <ul
                                            class="
                                                dropdown-menu
                                                dropdown-menu-end
                                            "
                                        >


                                            <!-- NOTIFICAR -->

                                            <?php if (
                                                $row[
                                                    'estado'
                                                ]
                                                ===
                                                'pendiente'
                                                &&
                                                $disponible
                                            ): ?>

                                                <li>

                                                    <a
                                                        class="
                                                            dropdown-item
                                                        "
                                                        href="
                                                            list.php?action=notificar&id=<?php
                                                            echo intval(
                                                                $row['id']
                                                            );
                                                            ?>"
                                                        onclick="
                                                            return confirm(
                                                                '¿Desea marcar esta reserva como notificada?'
                                                            );
                                                        "
                                                    >

                                                        <i
                                                            class="
                                                                fas
                                                                fa-bell
                                                                text-info
                                                                me-2
                                                            "
                                                        ></i>

                                                        Marcar como notificada

                                                    </a>

                                                </li>

                                            <?php endif; ?>


                                            <!-- COMPLETAR -->

                                            <?php if (
                                                $row[
                                                    'estado'
                                                ]
                                                ===
                                                'notificada'
                                            ): ?>

                                                <li>

                                                    <a
                                                        class="
                                                            dropdown-item
                                                        "
                                                        href="
                                                            list.php?action=completar&id=<?php
                                                            echo intval(
                                                                $row['id']
                                                            );
                                                            ?>"
                                                        onclick="
                                                            return confirm(
                                                                '¿Desea completar esta reserva?'
                                                            );
                                                        "
                                                    >

                                                        <i
                                                            class="
                                                                fas
                                                                fa-check
                                                                text-success
                                                                me-2
                                                            "
                                                        ></i>

                                                        Completar reserva

                                                    </a>

                                                </li>

                                            <?php endif; ?>


                                            <!-- CANCELAR -->

                                            <?php if (
                                                $row[
                                                    'estado'
                                                ]
                                                ===
                                                'pendiente'
                                                ||
                                                $row[
                                                    'estado'
                                                ]
                                                ===
                                                'notificada'
                                            ): ?>

                                                <li>

                                                    <a
                                                        class="
                                                            dropdown-item
                                                        "
                                                        href="
                                                            list.php?action=cancelar&id=<?php
                                                            echo intval(
                                                                $row['id']
                                                            );
                                                            ?>"
                                                        onclick="
                                                            return confirm(
                                                                '¿Está seguro de cancelar esta reserva?'
                                                            );
                                                        "
                                                    >

                                                        <i
                                                            class="
                                                                fas
                                                                fa-times
                                                                text-danger
                                                                me-2
                                                            "
                                                        ></i>

                                                        Cancelar reserva

                                                    </a>

                                                </li>

                                            <?php endif; ?>


                                            <!-- PRÉSTAMO -->

                                            <?php if (
                                                $disponible
                                                &&
                                                (
                                                    $row[
                                                        'estado'
                                                    ]
                                                    ===
                                                    'pendiente'
                                                    ||
                                                    $row[
                                                        'estado'
                                                    ]
                                                    ===
                                                    'notificada'
                                                )
                                            ): ?>


                                                <li>

                                                    <hr
                                                        class="
                                                            dropdown-divider
                                                        "
                                                    >

                                                </li>


                                                <li>

                                                    <a
                                                        class="
                                                            dropdown-item
                                                            text-primary
                                                        "
                                                        href="
                                                            ../books/prestamo.php?id=<?php
                                                            echo intval(
                                                                $row[
                                                                    'bibliografia_id'
                                                                ]
                                                            );
                                                            ?>&nombre_alumno=<?php
                                                            echo urlencode(
                                                                $row[
                                                                    'nombre_alumno'
                                                                ]
                                                                ??
                                                                ''
                                                            );
                                                            ?>&reserva_id=<?php
                                                            echo intval(
                                                                $row['id']
                                                            );
                                                            ?>"
                                                    >

                                                        <i
                                                            class="
                                                                fas
                                                                fa-book
                                                                me-2
                                                            "
                                                        ></i>

                                                        Realizar préstamo

                                                    </a>

                                                </li>

                                            <?php endif; ?>


                                        </ul>

                                    </div>

                                </td>


                            </tr>


                        <?php endforeach; ?>


                        </tbody>

                    </table>

                </div>


            <?php endif; ?>


        </div>

    </div>


</div>



<!-- ======================================================
     DATATABLES
====================================================== -->

<script>

$(document).ready(function () {

    $('#dataTable').DataTable({

        language: {

            url:
                '//cdn.datatables.net/plug-ins/1.13.8/i18n/es-ES.json'

        },

        responsive: true,

        pageLength: 10,

        order: [
            [4, 'desc']
        ],

        columnDefs: [

            {
                orderable: false,
                targets: [8]
            }

        ]

    });

});

</script>



<style>

/* ======================================================
   ENCABEZADO
====================================================== */

.reservas-header {

    display: flex;

    justify-content: space-between;

    align-items: center;

    gap: 20px;

}


.reservas-header h1 {

    color: #263238 !important;

    font-size: 2rem;

    font-weight: 700;

    margin-bottom: 5px;

}


.reservas-header h1 i {

    color: #3159d8 !important;

}


.reservas-header p {

    color: #64748b;

    margin: 0;

}


.sede-badge {

    background: #3159d8;

    color: #ffffff;

    padding:
        10px
        18px;

    border-radius: 10px;

    font-weight: 600;

    white-space: nowrap;

}


/* ======================================================
   ESTADÍSTICAS
====================================================== */

.estadistica {

    background: #ffffff;

    border-radius: 12px;

    padding: 18px;

    display: flex;

    align-items: center;

    gap: 15px;

    box-shadow:
        0 4px 15px
        rgba(0, 0, 0, 0.07);

}


.estadistica-icon {

    width: 52px;

    height: 52px;

    border-radius: 10px;

    display: flex;

    align-items: center;

    justify-content: center;

    color: #ffffff;

    font-size: 20px;

}


.estadistica small {

    color: #64748b;

}


.estadistica h3 {

    margin: 2px 0 0;

    font-weight: 700;

}


/* ======================================================
   CARD RESERVAS
====================================================== */

.reservas-card {

    border: none;

    border-radius: 12px;

    overflow: visible;

}


.reservas-card-header {

    background: #ffffff;

    border-bottom:
        1px solid
        #e5e7eb;

    padding:
        18px;

    display: flex;

    justify-content: space-between;

    align-items: center;

    gap: 15px;

}


.reservas-card-header h5 {

    color: #263238 !important;

    font-weight: 700;

    margin: 0;

}


.reservas-card-header h5 i {

    color: #3159d8 !important;

}


.reservas-card-header small {

    color: #64748b;

}


/* ======================================================
   TABLA
====================================================== */

#dataTable thead th {

    background-color:
        #3159d8 !important;

    color:
        #ffffff !important;

    border: none;

    white-space: nowrap;

}


#dataTable tbody td {

    vertical-align: middle;

}


#dataTable tbody tr:hover {

    background-color:
        #f8faff;

}


.libro-link {

    color:
        #3159d8 !important;

}


/* ======================================================
   SIN RESERVAS
====================================================== */

.sin-reservas {

    text-align: center;

    padding:
        60px
        20px;

}


.sin-reservas-icon {

    width: 80px;

    height: 80px;

    margin:
        0 auto
        20px;

    border-radius: 50%;

    background:
        #eef2ff;

    color:
        #3159d8;

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 32px;

}


.sin-reservas h4 {

    color: #263238;

    font-weight: 700;

}


.sin-reservas p {

    color: #64748b;

}


/* ======================================================
   BOTONES
====================================================== */

.dropdown-item {

    padding:
        9px
        14px;

}


.dropdown-item:hover {

    background-color:
        #f1f5ff;

}


/* ======================================================
   RESPONSIVE
====================================================== */

@media (
    max-width: 768px
) {

    .reservas-header {

        flex-direction: column;

        align-items: flex-start;

    }


    .reservas-header h1 {

        font-size: 1.5rem;

    }


    .reservas-card-header {

        flex-direction: column;

        align-items: flex-start;

    }


    .sede-badge {

        width: 100%;

        text-align: center;

    }

}

</style>


<?php

include '../includes/footer.php';

?>