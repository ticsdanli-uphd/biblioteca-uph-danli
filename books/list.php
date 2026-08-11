<?php

include '../includes/session.php';
include '../config/db.php';

/*
|--------------------------------------------------------------------------
| VERIFICAR SESIÓN
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

/*
|--------------------------------------------------------------------------
| CONFIGURACIÓN
|--------------------------------------------------------------------------
*/

$sede_id = 4; // Danlí

$busqueda = trim($_GET['busqueda'] ?? '');

/*
|--------------------------------------------------------------------------
| PAGINACIÓN
|--------------------------------------------------------------------------
*/

$registrosPorPagina = 20;

$paginaActual = max(
    1,
    (int) ($_GET['pagina'] ?? 1)
);

$offset =
    ($paginaActual - 1)
    * $registrosPorPagina;


/*
|--------------------------------------------------------------------------
| FUNCIÓN PARA BIND DINÁMICO
|--------------------------------------------------------------------------
*/

function bindDynamic(
    mysqli_stmt $stmt,
    string $types,
    array $params
): void {

    $bind = [$types];

    foreach ($params as $key => $value) {
        $bind[$key + 1] = &$params[$key];
    }

    call_user_func_array(
        [$stmt, 'bind_param'],
        $bind
    );
}


/*
|--------------------------------------------------------------------------
| FILTROS
|--------------------------------------------------------------------------
*/

$where = "WHERE b.sede_id = ?";

$params = [$sede_id];

$types = "i";


/*
|--------------------------------------------------------------------------
| BÚSQUEDA
|--------------------------------------------------------------------------
*/

if ($busqueda !== '') {

    $where .= "
        AND (
            b.codigo LIKE ?
            OR b.dewey LIKE ?
            OR b.clasificacion LIKE ?
            OR b.nombre LIKE ?
            OR b.autores LIKE ?
            OR b.editorial LIKE ?
            OR b.edicion LIKE ?
            OR b.isbn LIKE ?
            OR b.estado LIKE ?
            OR b.ubicacion LIKE ?
            OR b.idioma LIKE ?
            OR c.nombre LIKE ?
        )
    ";

    $like =
        '%' . $busqueda . '%';

    for ($i = 0; $i < 12; $i++) {
        $params[] = $like;
    }

    $types .=
        str_repeat('s', 12);
}


/*
|--------------------------------------------------------------------------
| CONTAR REGISTROS
|--------------------------------------------------------------------------
*/

$countSql = "
    SELECT COUNT(*) AS total

    FROM bibliografia b

    LEFT JOIN carreras c
        ON c.id = b.carrera_id

    $where
";

$countStmt =
    $conn->prepare($countSql);

if (!$countStmt) {
    die(
        "Error en consulta: "
        . htmlspecialchars($conn->error)
    );
}

bindDynamic(
    $countStmt,
    $types,
    $params
);

$countStmt->execute();

$countResult =
    $countStmt->get_result();

$totalRows =
    (int) (
        $countResult
            ->fetch_assoc()['total']
        ?? 0
    );

$countStmt->close();


/*
|--------------------------------------------------------------------------
| TOTAL DE PÁGINAS
|--------------------------------------------------------------------------
*/

$totalPaginas =
    max(
        1,
        (int) ceil(
            $totalRows
            / $registrosPorPagina
        )
    );


/*
|--------------------------------------------------------------------------
| CORREGIR PÁGINA
|--------------------------------------------------------------------------
*/

if ($paginaActual > $totalPaginas) {

    $paginaActual =
        $totalPaginas;

    $offset =
        ($paginaActual - 1)
        * $registrosPorPagina;
}


/*
|--------------------------------------------------------------------------
| CONSULTAR LIBROS
|
| Se agrega:
| - préstamos activos
| - disponibilidad real
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT

        b.*,

        c.nombre AS carrera_nombre,

        s.nombre AS sede_nombre,

        (
            SELECT COUNT(*)

            FROM registro_visitas rv

            WHERE rv.bibliografia_id = b.id

              AND rv.tipo = 'prestamo'

              AND rv.devuelto = 0

        ) AS prestamos_activos

    FROM bibliografia b

    LEFT JOIN carreras c
        ON c.id = b.carrera_id

    LEFT JOIN sedes s
        ON s.id = b.sede_id

    $where

    ORDER BY b.codigo ASC

    LIMIT ?, ?
";


/*
|--------------------------------------------------------------------------
| PARÁMETROS DE CONSULTA
|--------------------------------------------------------------------------
*/

$paramsData =
    $params;

$typesData =
    $types . "ii";

$paramsData[] =
    $offset;

$paramsData[] =
    $registrosPorPagina;


/*
|--------------------------------------------------------------------------
| EJECUTAR
|--------------------------------------------------------------------------
*/

$stmt =
    $conn->prepare($sql);

if (!$stmt) {

    die(
        "Error preparando consulta: "
        . htmlspecialchars($conn->error)
    );
}

bindDynamic(
    $stmt,
    $typesData,
    $paramsData
);

$stmt->execute();

$result =
    $stmt->get_result();


/*
|--------------------------------------------------------------------------
| RANGO DE REGISTROS
|--------------------------------------------------------------------------
*/

$inicioMostrar =
    $totalRows > 0
        ? $offset + 1
        : 0;

$finMostrar =
    min(
        $totalRows,
        $offset + $registrosPorPagina
    );


/*
|--------------------------------------------------------------------------
| HEADER
|--------------------------------------------------------------------------
*/

include '../includes/header.php';


/*
|--------------------------------------------------------------------------
| MENSAJE DE ÉXITO
|--------------------------------------------------------------------------
*/

if (isset($_SESSION['success_msg'])) {

    $msg =
        $_SESSION['success_msg'];

    unset(
        $_SESSION['success_msg']
    );

    echo '
    <script>

    document.addEventListener(
        "DOMContentLoaded",
        function(){

            if(typeof Swal !== "undefined"){

                Swal.fire({

                    icon: "success",

                    title: "¡Correcto!",

                    text: '
        . json_encode($msg)
        . ',

                    timer: 2200,

                    showConfirmButton: false

                });

            }

        }

    );

    </script>';
}


/*
|--------------------------------------------------------------------------
| MENSAJE DE ERROR
|--------------------------------------------------------------------------
*/

if (isset($_SESSION['error_msg'])) {

    $msg =
        $_SESSION['error_msg'];

    unset(
        $_SESSION['error_msg']
    );

    echo '
    <script>

    document.addEventListener(
        "DOMContentLoaded",
        function(){

            if(typeof Swal !== "undefined"){

                Swal.fire({

                    icon: "error",

                    title: "Error",

                    text: '
        . json_encode($msg)
        . '

                });

            }

        }

    );

    </script>';
}

?>


<!-- =====================================================
     CONTENIDO
===================================================== -->

<div class="container-fluid py-4">


    <!-- =================================================
         ENCABEZADO
    ================================================== -->

    <div
        class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4"
    >

        <div>

            <h1 class="fw-bold mb-1">

                <i
                    class="fas fa-books text-primary me-2"
                ></i>

                Catálogo de Libros

            </h1>

            <p class="text-muted mb-0">

                Biblioteca UPH - Sede Danlí

            </p>

        </div>


        <span
            class="badge bg-primary fs-6 p-2"
        >

            <i
                class="fas fa-map-marker-alt me-1"
            ></i>

            Danlí

        </span>

    </div>


    <!-- =================================================
         BUSCADOR
    ================================================== -->

    <div
        class="card shadow-sm border-0 mb-4"
    >

        <div class="card-body">

            <form
                method="get"
                class="row g-2"
            >

                <div class="col-lg-10 col-md-9">

                    <input
                        type="text"
                        name="busqueda"
                        class="form-control form-control-lg"
                        value="<?= htmlspecialchars(
                            $busqueda,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>"
                        placeholder="Buscar por código, Dewey, clasificación, nombre, autor, ISBN, ubicación, idioma o carrera..."
                    >

                </div>


                <div
                    class="col-lg-2 col-md-3 d-flex gap-2"
                >

                    <button
                        type="submit"
                        class="btn btn-primary btn-lg flex-fill"
                    >

                        <i
                            class="fas fa-search me-1"
                        ></i>

                        Buscar

                    </button>


                    <a
                        href="list.php"
                        class="btn btn-secondary btn-lg"
                        title="Limpiar búsqueda"
                    >

                        <i
                            class="fas fa-sync"
                        ></i>

                    </a>

                </div>

            </form>

        </div>

    </div>


    <!-- =================================================
         BOTONES ADMINISTRADOR
    ================================================== -->

    <?php
    if (
        ($_SESSION['role'] ?? '')
        === 'admin'
    ):
    ?>

        <div
            class="d-flex flex-wrap gap-2 mb-3"
        >

            <a
                href="add.php"
                class="btn btn-primary"
            >

                <i
                    class="fas fa-plus me-1"
                ></i>

                Agregar Libro

            </a>


            <a
                href="upload_excel.php"
                class="btn btn-info text-white"
            >

                <i
                    class="fas fa-file-upload me-1"
                ></i>

                Importar Excel

            </a>


            <a
                href="download_template.php"
                class="btn btn-secondary"
            >

                <i
                    class="fas fa-download me-1"
                ></i>

                Plantilla Excel

            </a>


            <a
                href="exportar_libros.php"
                class="btn btn-success"
            >

                <i
                    class="fas fa-file-excel me-1"
                ></i>

                Exportar Libros

            </a>


            <!-- SOLICITUDES -->

            <a
                href="../solicitudes/list.php"
                class="btn btn-warning"
            >

                <i
                    class="fas fa-bell me-1"
                ></i>

                Solicitudes de Préstamo

            </a>

        </div>

    <?php endif; ?>


    <!-- =================================================
         TABLA
    ================================================== -->

    <div
        class="card shadow-sm border-0"
    >

        <div
            class="card-header bg-primary text-white"
        >

            <div
                class="d-flex justify-content-between align-items-center flex-wrap gap-2"
            >

                <h5 class="mb-0">

                    <i
                        class="fas fa-book me-2"
                    ></i>

                    Libros registrados

                </h5>


                <span
                    class="badge bg-light text-primary"
                >

                    <?= $totalRows ?>

                    registros

                </span>

            </div>

        </div>


        <div class="card-body">


            <?php if ($totalRows > 0): ?>


                <!-- =================================================
                     TABLA RESPONSIVA
                ================================================== -->

                <div
                    class="table-responsive"
                >

                    <table
                        id="tablaLibros"
                        class="table table-bordered table-hover align-middle"
                    >

                        <thead
                            class="table-primary"
                        >

                            <tr>

                                <th>Código</th>

                                <th>Dewey</th>

                                <th>Clasificación</th>

                                <th>Nombre</th>

                                <th>Autor(es)</th>

                                <th>Editorial</th>

                                <th>Edición</th>

                                <th>Año</th>

                                <th>ISBN</th>

                                <th>Estado</th>

                                <th>Ubicación</th>

                                <th>Fecha ingreso</th>

                                <th>Idioma</th>

                                <th>Carrera</th>

                                <th>Cantidad</th>

                                <th>Disponibilidad</th>

                                <th>Sede ID</th>

                                <th>Acciones</th>

                            </tr>

                        </thead>


                        <tbody>


                        <?php while (
                            $row =
                                $result->fetch_assoc()
                        ): ?>


                            <?php

                            /*
                            |--------------------------------------------------------------------------
                            | DATOS
                            |--------------------------------------------------------------------------
                            */

                            $estado =
                                $row['estado']
                                ?? 'Disponible';


                            $cantidad =
                                (int) (
                                    $row['cantidad']
                                    ?? 0
                                );


                            $prestamosActivos =
                                (int) (
                                    $row[
                                        'prestamos_activos'
                                    ]
                                    ?? 0
                                );


                            /*
                            | Disponibilidad REAL
                            */

                            $disponible =
                                (
                                    $cantidad
                                    > $prestamosActivos
                                )
                                &&
                                $estado === 'Disponible';


                            /*
                            |--------------------------------------------------------------------------
                            | CLASE DEL ESTADO
                            |--------------------------------------------------------------------------
                            */

                            switch ($estado) {

                                case 'Disponible':

                                    $estadoClase =
                                        'success';

                                    break;


                                case 'Prestado':

                                    $estadoClase =
                                        'warning text-dark';

                                    break;


                                case 'Deteriorado':

                                    $estadoClase =
                                        'danger';

                                    break;


                                case 'Baja':

                                    $estadoClase =
                                        'dark';

                                    break;


                                default:

                                    $estadoClase =
                                        'secondary';

                                    break;
                            }

                            ?>


                            <tr>


                                <!-- CÓDIGO -->

                                <td>

                                    <strong>

                                        <?= htmlspecialchars(
                                            $row['codigo']
                                            ?? '',
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>

                                    </strong>

                                </td>


                                <!-- DEWEY -->

                                <td>

                                    <?= htmlspecialchars(
                                        $row['dewey']
                                        ?? '',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>

                                </td>


                                <!-- CLASIFICACIÓN -->

                                <td>

                                    <?= htmlspecialchars(
                                        $row[
                                            'clasificacion'
                                        ]
                                        ?? '',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>

                                </td>


                                <!-- NOMBRE -->

                                <td>

                                    <strong>

                                        <?= htmlspecialchars(
                                            $row['nombre']
                                            ?? '',
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>

                                    </strong>

                                </td>


                                <!-- AUTORES -->

                                <td>

                                    <?= htmlspecialchars(
                                        $row['autores']
                                        ?? '',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>

                                </td>


                                <!-- EDITORIAL -->

                                <td>

                                    <?= htmlspecialchars(
                                        $row['editorial']
                                        ?? '',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>

                                </td>


                                <!-- EDICIÓN -->

                                <td>

                                    <?= htmlspecialchars(
                                        $row['edicion']
                                        ?? '',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>

                                </td>


                                <!-- AÑO -->

                                <td>

                                    <?= htmlspecialchars(
                                        $row['anio']
                                        ?? '',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>

                                </td>


                                <!-- ISBN -->

                                <td>

                                    <?= htmlspecialchars(
                                        $row['isbn']
                                        ?? '',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>

                                </td>


                                <!-- ESTADO -->

                                <td>

                                    <span
                                        class="badge bg-<?= $estadoClase ?>"
                                    >

                                        <?= htmlspecialchars(
                                            $estado,
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>

                                    </span>

                                </td>


                                <!-- UBICACIÓN -->

                                <td>

                                    <?= htmlspecialchars(
                                        $row[
                                            'ubicacion'
                                        ]
                                        ?? '',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>

                                </td>


                                <!-- FECHA -->

                                <td>

                                    <?php

                                    if (
                                        !empty(
                                            $row[
                                                'fecha_ingreso'
                                            ]
                                        )
                                    ) {

                                        echo date(
                                            'd/m/Y',
                                            strtotime(
                                                $row[
                                                    'fecha_ingreso'
                                                ]
                                            )
                                        );

                                    }

                                    ?>

                                </td>


                                <!-- IDIOMA -->

                                <td>

                                    <?= htmlspecialchars(
                                        $row['idioma']
                                        ?? '',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>

                                </td>


                                <!-- CARRERA -->

                                <td>

                                    <?= htmlspecialchars(
                                        $row[
                                            'carrera_nombre'
                                        ]
                                        ?? 'Todas / General',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>

                                </td>


                                <!-- CANTIDAD -->

                                <td
                                    class="text-center"
                                >

                                    <strong>

                                        <?= $cantidad ?>

                                    </strong>

                                </td>


                                <!-- DISPONIBILIDAD -->

                                <td
                                    class="text-center"
                                >

                                    <?php if ($disponible): ?>

                                        <span
                                            class="badge bg-success"
                                        >

                                            <i
                                                class="fas fa-check-circle me-1"
                                            ></i>

                                            Disponible

                                        </span>

                                        <div
                                            class="small text-muted mt-1"
                                        >

                                            <?= $cantidad
                                                - $prestamosActivos ?>

                                            disponible(s)

                                        </div>

                                    <?php else: ?>

                                        <span
                                            class="badge bg-danger"
                                        >

                                            <i
                                                class="fas fa-times-circle me-1"
                                            ></i>

                                            No disponible

                                        </span>

                                        <div
                                            class="small text-muted mt-1"
                                        >

                                            <?= $prestamosActivos ?>

                                            prestado(s)

                                        </div>

                                    <?php endif; ?>

                                </td>


                                <!-- SEDE -->

                                <td
                                    class="text-center"
                                >

                                    <span
                                        class="badge bg-success"
                                    >

                                        <?= (int) (
                                            $row['sede_id']
                                            ?? 4
                                        ) ?>

                                    </span>

                                </td>


                                <!-- ACCIONES -->

                                <td>

                                    <div
                                        class="d-flex flex-wrap gap-1"
                                    >


                                        <!-- VER -->

                                        <a
                                            href="view.php?id=<?= (int)$row['id'] ?>"
                                            class="btn btn-sm btn-outline-info"
                                            title="Ver libro"
                                        >

                                            <i
                                                class="fas fa-eye"
                                            ></i>

                                        </a>


                                        <?php

                                        /*
                                        |--------------------------------------------------------------------------
                                        | ALUMNO / DOCENTE
                                        |--------------------------------------------------------------------------
                                        */

                                        $rolUsuario =
                                            $_SESSION['role']
                                            ?? '';

                                        if (
                                            in_array(
                                                $rolUsuario,
                                                [
                                                    'usuario',
                                                    'docente'
                                                ],
                                                true
                                            )
                                        ):

                                        ?>


                                            <?php if ($disponible): ?>

                                                <a
                                                    href="../solicitudes/solicitar.php?id=<?= (int)$row['id'] ?>"
                                                    class="btn btn-sm btn-primary"
                                                    title="Solicitar préstamo"
                                                >

                                                    <i
                                                        class="fas fa-book-reader"
                                                    ></i>

                                                </a>

                                            <?php else: ?>

                                                <button
                                                    type="button"
                                                    class="btn btn-sm btn-outline-secondary"
                                                    disabled
                                                    title="Libro no disponible"
                                                >

                                                    <i
                                                        class="fas fa-clock"
                                                    ></i>

                                                </button>

                                            <?php endif; ?>


                                        <?php endif; ?>


                                        <?php

                                        /*
                                        |--------------------------------------------------------------------------
                                        | ADMINISTRADOR
                                        |--------------------------------------------------------------------------
                                        */

                                        if (
                                            $rolUsuario
                                            === 'admin'
                                        ):

                                        ?>

                                            <a
                                                href="edit.php?id=<?= (int)$row['id'] ?>"
                                                class="btn btn-sm btn-outline-warning"
                                                title="Editar"
                                            >

                                                <i
                                                    class="fas fa-edit"
                                                ></i>

                                            </a>


                                            <button
                                                type="button"
                                                class="btn btn-sm btn-outline-danger"
                                                onclick="confirmarEliminar(<?= (int)$row['id'] ?>)"
                                                title="Eliminar"
                                            >

                                                <i
                                                    class="fas fa-trash"
                                                ></i>

                                            </button>

                                        <?php endif; ?>


                                    </div>

                                </td>


                            </tr>


                        <?php endwhile; ?>


                        </tbody>

                    </table>

                </div>


            <?php else: ?>


                <!-- SIN RESULTADOS -->

                <div
                    class="alert alert-info mt-3 mb-0"
                >

                    <i
                        class="fas fa-info-circle me-2"
                    ></i>

                    No se encontraron libros
                    en Danlí.

                </div>


            <?php endif; ?>


            <!-- =================================================
                 PAGINACIÓN
            ================================================== -->

            <?php if ($totalRows > 0): ?>

                <div
                    class="d-flex justify-content-between align-items-center flex-wrap gap-3 mt-4"
                >


                    <span class="text-muted">

                        Mostrando

                        <strong>

                            <?= $inicioMostrar ?>

                        </strong>

                        -

                        <strong>

                            <?= $finMostrar ?>

                        </strong>

                        de

                        <strong>

                            <?= $totalRows ?>

                        </strong>

                    </span>


                    <?php if ($totalPaginas > 1): ?>


                        <nav
                            aria-label="Paginación de libros"
                        >

                            <ul
                                class="pagination mb-0"
                            >


                                <?php

                                $query =
                                    !empty($busqueda)

                                    ? '&busqueda='
                                      . urlencode(
                                          $busqueda
                                      )

                                    : '';

                                ?>


                                <!-- ANTERIOR -->

                                <li
                                    class="page-item
                                    <?= $paginaActual <= 1
                                        ? 'disabled'
                                        : '' ?>"
                                >

                                    <a
                                        class="page-link"
                                        href="?pagina=<?= max(
                                            1,
                                            $paginaActual - 1
                                        ) . $query ?>"
                                    >

                                        Anterior

                                    </a>

                                </li>


                                <?php

                                $start =
                                    max(
                                        1,
                                        $paginaActual - 2
                                    );

                                $end =
                                    min(
                                        $totalPaginas,
                                        $paginaActual + 2
                                    );

                                ?>


                                <?php for (
                                    $p = $start;
                                    $p <= $end;
                                    $p++
                                ): ?>


                                    <li
                                        class="page-item
                                        <?= $p === $paginaActual
                                            ? 'active'
                                            : '' ?>"
                                    >

                                        <a
                                            class="page-link"
                                            href="?pagina=<?= $p . $query ?>"
                                        >

                                            <?= $p ?>

                                        </a>

                                    </li>


                                <?php endfor; ?>


                                <!-- SIGUIENTE -->

                                <li
                                    class="page-item
                                    <?= $paginaActual >= $totalPaginas
                                        ? 'disabled'
                                        : '' ?>"
                                >

                                    <a
                                        class="page-link"
                                        href="?pagina=<?= min(
                                            $totalPaginas,
                                            $paginaActual + 1
                                        ) . $query ?>"
                                    >

                                        Siguiente

                                    </a>

                                </li>


                            </ul>

                        </nav>


                    <?php endif; ?>


                </div>

            <?php endif; ?>


        </div>

    </div>

</div>


<!-- =====================================================
     JAVASCRIPT
===================================================== -->

<script>

function confirmarEliminar(id) {

    const eliminar = () => {

        window.location.href =
            'delete.php?id=' + id;

    };


    if (
        typeof Swal !== 'undefined'
    ) {

        Swal.fire({

            title:
                '¿Eliminar este libro?',

            text:
                'El registro se eliminará y sus reservas se cancelarán.',

            icon:
                'warning',

            showCancelButton:
                true,

            confirmButtonText:
                'Sí, eliminar',

            cancelButtonText:
                'Cancelar',

            confirmButtonColor:
                '#dc3545',

            cancelButtonColor:
                '#6c757d'

        }).then(
            result => {

                if (
                    result.isConfirmed
                ) {

                    eliminar();

                }

            }
        );

    }

    else if (
        confirm(
            '¿Está seguro de eliminar este libro?'
        )
    ) {

        eliminar();

    }

}

</script>


<!-- =====================================================
     ESTILOS
===================================================== -->

<style>

/*
|--------------------------------------------------------------------------
| TABLA
|--------------------------------------------------------------------------
*/

#tablaLibros thead th {

    white-space: nowrap;

    vertical-align: middle;

}

#tablaLibros td {

    vertical-align: middle;

}


/*
|--------------------------------------------------------------------------
| BOTONES
|--------------------------------------------------------------------------
*/

#tablaLibros .btn {

    min-width: 34px;

}


/*
|--------------------------------------------------------------------------
| RESPONSIVE
|--------------------------------------------------------------------------
*/

@media (max-width: 992px) {

    #tablaLibros {

        font-size: 0.9rem;

    }

}


@media (max-width: 768px) {

    .container-fluid {

        padding-left: 10px;

        padding-right: 10px;

    }


    h1 {

        font-size: 1.6rem;

    }


    .form-control-lg,
    .btn-lg {

        font-size: 1rem;

    }


    #tablaLibros {

        font-size: 0.85rem;

    }


    #tablaLibros .btn {

        padding: 0.3rem 0.5rem;

    }

}


/*
|--------------------------------------------------------------------------
| DISPONIBILIDAD
|--------------------------------------------------------------------------
*/

.badge {

    white-space: nowrap;

}

</style>


<?php

$stmt->close();

include '../includes/footer.php';

?>