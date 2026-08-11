<?php

include '../includes/session.php';
include '../config/db.php';


// =====================================================
// CONFIGURACIÓN
// =====================================================

// Danlí = sede 4
$sede_id = 4;
$nombre_sede = 'Danli';


// =====================================================
// FILTROS
// =====================================================

$fecha_inicio = isset($_GET['fecha_inicio'])
    ? trim($_GET['fecha_inicio'])
    : '';

$fecha_fin = isset($_GET['fecha_fin'])
    ? trim($_GET['fecha_fin'])
    : '';


// =====================================================
// VALIDAR FECHAS
// =====================================================

function fechaValida($fecha)
{
    if (empty($fecha)) {
        return false;
    }

    $d = DateTime::createFromFormat(
        'Y-m-d',
        $fecha
    );

    return $d && $d->format('Y-m-d') === $fecha;
}


if (!empty($fecha_inicio) && !fechaValida($fecha_inicio)) {
    $fecha_inicio = '';
}

if (!empty($fecha_fin) && !fechaValida($fecha_fin)) {
    $fecha_fin = '';
}


// =====================================================
// CORREGIR RANGO DE FECHAS
// =====================================================

if (
    !empty($fecha_inicio) &&
    !empty($fecha_fin) &&
    $fecha_inicio > $fecha_fin
) {

    $temporal = $fecha_inicio;

    $fecha_inicio = $fecha_fin;

    $fecha_fin = $temporal;
}


// =====================================================
// CONSULTA
// =====================================================

$sql = "
    SELECT

        r.id,

        r.fecha,

        r.fecha_devolucion_esperada,

        r.observaciones,

        r.nombre_alumno,

        r.es_externo,

        b.nombre AS libro_nombre,

        b.codigo,

        u.username,

        c.nombre AS carrera_nombre,

        i.nombre AS institucion_nombre,

        s.nombre AS sede_nombre,

        DATEDIFF(
            r.fecha_devolucion_esperada,
            DATE(r.fecha)
        ) AS dias_prestamo

    FROM registro_visitas r

    LEFT JOIN bibliografia b
        ON r.bibliografia_id = b.id

    LEFT JOIN usuarios u
        ON r.user_id = u.id

    LEFT JOIN sedes s
        ON b.sede_id = s.id

    LEFT JOIN carreras c
        ON r.carrera_id = c.id

    LEFT JOIN instituciones_externas i
        ON r.institucion_id = i.id

    WHERE r.tipo = 'prestamo'

    AND r.devuelto = 1

    AND b.sede_id = ?

";


// =====================================================
// AGREGAR FILTROS
// =====================================================

$tipos = 'i';

$parametros = [$sede_id];


if (
    !empty($fecha_inicio) &&
    !empty($fecha_fin)
) {

    $sql .= "
        AND DATE(r.fecha)
        BETWEEN ? AND ?
    ";

    $tipos .= 'ss';

    $parametros[] = $fecha_inicio;
    $parametros[] = $fecha_fin;

} elseif (!empty($fecha_inicio)) {

    $sql .= "
        AND DATE(r.fecha) >= ?
    ";

    $tipos .= 's';

    $parametros[] = $fecha_inicio;

} elseif (!empty($fecha_fin)) {

    $sql .= "
        AND DATE(r.fecha) <= ?
    ";

    $tipos .= 's';

    $parametros[] = $fecha_fin;
}


$sql .= "
    ORDER BY r.fecha DESC
";


// =====================================================
// EJECUTAR CONSULTA
// =====================================================

$stmt = $conn->prepare($sql);


if (!$stmt) {

    die(
        'Error al preparar la consulta: '
        . htmlspecialchars(
            $conn->error,
            ENT_QUOTES,
            'UTF-8'
        )
    );

}


$stmt->bind_param(
    $tipos,
    ...$parametros
);


$stmt->execute();


$result = $stmt->get_result();


// =====================================================
// ESTADÍSTICAS DEL REPORTE
// =====================================================

$totalDevueltos = 0;

$totalExternos = 0;

$totalInternos = 0;

$totalDias = 0;

$datos = [];


// =====================================================
// RECORRER RESULTADOS
// =====================================================

while (
    $row = $result->fetch_assoc()
) {

    $totalDevueltos++;


    if ((int)$row['es_externo'] === 1) {

        $totalExternos++;

    } else {

        $totalInternos++;

    }


    $dias = !empty($row['dias_prestamo'])
        ? (int)$row['dias_prestamo']
        : 0;


    $totalDias += $dias;


    $datos[] = $row;

}


$stmt->close();


// =====================================================
// PROMEDIO DE DÍAS
// =====================================================

$promedioDias = 0;

if ($totalDevueltos > 0) {

    $promedioDias =
        round(
            $totalDias / $totalDevueltos,
            1
        );

}


// =====================================================
// HEADER
// =====================================================

include '../includes/header.php';

?>


<style>

/* =====================================================
   REPORTE DE PRÉSTAMOS DEVUELTOS
   Mantiene la estética actual
===================================================== */

.reporte-container {
    max-width: 1300px;
    margin: 0 auto;
}


/* =====================================================
   ENCABEZADO
===================================================== */

.reporte-header {
    margin-bottom: 20px;
}

.reporte-header h2 {
    margin-bottom: 5px;
    color: #202b3c;
}

.reporte-header p {
    margin-bottom: 0;
    color: #718096;
}


/* =====================================================
   FILTROS
===================================================== */

.filtros-card {
    background: #ffffff;
    border-radius: 15px;
    padding: 20px;
    box-shadow: 0 8px 25px rgba(0,0,0,.06);
    margin-bottom: 20px;
}

.filtros-card label {
    font-weight: 600;
}


/* =====================================================
   BOTONES
===================================================== */

.btn-filtrar {
    background: #3159d8;
    border-color: #3159d8;
    color: #ffffff;
    font-weight: 600;
    box-shadow: 0 5px 15px rgba(49,89,216,.20);
}

.btn-filtrar:hover {
    background: #2649bd;
    border-color: #2649bd;
    color: #ffffff;
}

.btn-csv {
    background: #20b978;
    border-color: #20b978;
    color: #ffffff;
    font-weight: 600;
}

.btn-csv:hover {
    background: #179b63;
    border-color: #179b63;
    color: #ffffff;
}


/* =====================================================
   TARJETAS DE RESUMEN
===================================================== */

.resumen-grid {
    display: grid;
    grid-template-columns:
        repeat(4, minmax(0, 1fr));

    gap: 15px;

    margin-bottom: 20px;
}

.resumen-card {
    background: #ffffff;
    border-radius: 15px;
    padding: 18px;
    box-shadow: 0 8px 25px rgba(0,0,0,.06);

    display: flex;
    align-items: center;

    gap: 15px;
}

.resumen-icon {
    width: 52px;
    height: 52px;

    border-radius: 14px;

    display: flex;
    align-items: center;
    justify-content: center;

    color: #ffffff;

    font-size: 22px;

    flex-shrink: 0;
}

.resumen-blue {
    background: #3159d8;
}

.resumen-green {
    background: #20b978;
}

.resumen-orange {
    background: #ff9f1c;
}

.resumen-purple {
    background: #7357d8;
}

.resumen-numero {
    font-size: 25px;
    font-weight: 700;
    color: #202b3c;
    line-height: 1;
}

.resumen-label {
    color: #718096;
    font-size: 13px;
    margin-top: 5px;
}


/* =====================================================
   TABLA
===================================================== */

.tabla-card {
    background: #ffffff;
    border-radius: 15px;

    box-shadow:
        0 8px 25px rgba(0,0,0,.06);

    overflow: hidden;
}

.tabla-card-header {
    padding: 18px 20px;

    border-bottom:
        1px solid #edf0f5;

    display: flex;
    justify-content: space-between;
    align-items: center;

    gap: 15px;
}

.tabla-card-header h5 {
    margin: 0;

    color: #202b3c;

    font-weight: 700;
}

.badge-sede {
    background: #20b978;
    color: white;

    padding: 7px 12px;

    border-radius: 20px;

    font-weight: 600;
}

.tabla-wrapper {
    overflow-x: auto;
}

#tabla-prestamos-devueltos {
    margin-bottom: 0 !important;
}

#tabla-prestamos-devueltos thead th {
    background: #3159d8 !important;
    color: #ffffff !important;

    border: none !important;

    white-space: nowrap;

    font-weight: 600;
}

#tabla-prestamos-devueltos tbody td {
    vertical-align: middle;
}


/* =====================================================
   ESTADO
===================================================== */

.estado-devuelto {
    background: #20b978;
    color: white;

    padding: 6px 10px;

    border-radius: 20px;

    font-size: 12px;

    font-weight: 600;

    white-space: nowrap;
}

.estado-externo {
    background: #0dcaf0;
    color: #ffffff;

    padding: 4px 8px;

    border-radius: 15px;

    font-size: 11px;
}


/* =====================================================
   MENSAJE SIN RESULTADOS
===================================================== */

.sin-resultados {
    text-align: center;

    padding: 50px 20px;

    color: #718096;
}

.sin-resultados i {
    font-size: 45px;

    margin-bottom: 15px;

    color: #cbd5e1;
}


/* =====================================================
   RESPONSIVE
===================================================== */

@media (max-width: 1000px) {

    .resumen-grid {
        grid-template-columns:
            repeat(2, 1fr);
    }

}


@media (max-width: 600px) {

    .resumen-grid {
        grid-template-columns: 1fr;
    }

    .tabla-card-header {
        flex-direction: column;
        align-items: flex-start;
    }

}

</style>


<div class="reporte-container">


    <!-- =================================================
         ENCABEZADO
    ================================================== -->

    <div class="reporte-header">

        <h2>

            <i class="fas fa-clipboard-check me-2"></i>

            Reporte de Préstamos Devueltos

        </h2>

        <p>

            Consulta y descarga el historial de préstamos
            devueltos de la Biblioteca UPH.

        </p>

    </div>


    <!-- =================================================
         FILTROS
    ================================================== -->

    <div class="filtros-card">

        <form
            method="get"
            action="prestamos_devueltos.php"
            class="row g-3 align-items-end"
        >


            <!-- DESDE -->

            <div class="col-md-3">

                <label
                    for="fecha_inicio"
                    class="form-label"
                >

                    Desde:

                </label>

                <input
                    type="date"
                    name="fecha_inicio"
                    id="fecha_inicio"
                    class="form-control"
                    value="<?php
                        echo htmlspecialchars(
                            $fecha_inicio,
                            ENT_QUOTES,
                            'UTF-8'
                        );
                    ?>"
                >

            </div>


            <!-- HASTA -->

            <div class="col-md-3">

                <label
                    for="fecha_fin"
                    class="form-label"
                >

                    Hasta:

                </label>

                <input
                    type="date"
                    name="fecha_fin"
                    id="fecha_fin"
                    class="form-control"
                    value="<?php
                        echo htmlspecialchars(
                            $fecha_fin,
                            ENT_QUOTES,
                            'UTF-8'
                        );
                    ?>"
                >

            </div>


            <!-- FILTRAR -->

            <div class="col-md-auto">

                <button
                    type="submit"
                    class="btn btn-filtrar"
                >

                    <i class="fas fa-filter me-1"></i>

                    Filtrar

                </button>

            </div>


            <!-- LIMPIAR -->

            <div class="col-md-auto">

                <a
                    href="prestamos_devueltos.php"
                    class="btn btn-secondary"
                >

                    <i class="fas fa-undo me-1"></i>

                    Limpiar

                </a>

            </div>


        </form>

    </div>


    <!-- =================================================
         RESUMEN
    ================================================== -->

    <div class="resumen-grid">


        <!-- TOTAL -->

        <div class="resumen-card">

            <div class="resumen-icon resumen-blue">

                <i class="fas fa-book"></i>

            </div>

            <div>

                <div class="resumen-numero">

                    <?php
                    echo number_format(
                        $totalDevueltos
                    );
                    ?>

                </div>

                <div class="resumen-label">

                    Préstamos devueltos

                </div>

            </div>

        </div>


        <!-- INTERNOS -->

        <div class="resumen-card">

            <div class="resumen-icon resumen-green">

                <i class="fas fa-user"></i>

            </div>

            <div>

                <div class="resumen-numero">

                    <?php
                    echo number_format(
                        $totalInternos
                    );
                    ?>

                </div>

                <div class="resumen-label">

                    Usuarios internos

                </div>

            </div>

        </div>


        <!-- EXTERNOS -->

        <div class="resumen-card">

            <div class="resumen-icon resumen-orange">

                <i class="fas fa-building"></i>

            </div>

            <div>

                <div class="resumen-numero">

                    <?php
                    echo number_format(
                        $totalExternos
                    );
                    ?>

                </div>

                <div class="resumen-label">

                    Usuarios externos

                </div>

            </div>

        </div>


        <!-- PROMEDIO -->

        <div class="resumen-card">

            <div class="resumen-icon resumen-purple">

                <i class="fas fa-calendar-alt"></i>

            </div>

            <div>

                <div class="resumen-numero">

                    <?php
                    echo $promedioDias;
                    ?>

                </div>

                <div class="resumen-label">

                    Días promedio de préstamo

                </div>

            </div>

        </div>


    </div>


    <!-- =================================================
         BOTÓN CSV
    ================================================== -->

    <div class="mb-3">

        <a
            href="prestamos_devueltos_download.php?fecha_inicio=<?php
                echo urlencode($fecha_inicio);
            ?>&fecha_fin=<?php
                echo urlencode($fecha_fin);
            ?>"
            class="btn btn-csv"
        >

            <i class="fas fa-file-csv me-1"></i>

            Descargar CSV

        </a>

    </div>


    <!-- =================================================
         TABLA
    ================================================== -->

    <div class="tabla-card">


        <div class="tabla-card-header">

            <h5>

                <i class="fas fa-list me-2"></i>

                Historial de préstamos

            </h5>


            <span class="badge-sede">

                <i class="fas fa-map-marker-alt me-1"></i>

                Danli

            </span>

        </div>


        <div class="tabla-wrapper">


            <table
                class="table table-striped table-hover"
                id="tabla-prestamos-devueltos"
            >


                <thead>

                    <tr>

                        <th>
                            Fecha Préstamo
                        </th>

                        <th>
                            Libro
                        </th>

                        <th>
                            Código
                        </th>

                        <th>
                            Fecha Devolución Esperada
                        </th>

                        <th>
                            Estado
                        </th>

                        <th>
                            Días
                        </th>

                        <th>
                            Alumno
                        </th>

                        <th>
                            Carrera
                        </th>

                        <th>
                            Institución
                        </th>

                        <th>
                            Usuario
                        </th>

                        <th>
                            Sede
                        </th>

                        <th>
                            Observaciones
                        </th>

                    </tr>

                </thead>


                <tbody>


                    <?php if (count($datos) > 0): ?>


                        <?php foreach ($datos as $row): ?>


                            <tr>


                                <!-- FECHA -->

                                <td>

                                    <?php

                                    echo htmlspecialchars(
                                        date(
                                            'd/m/Y H:i',
                                            strtotime(
                                                $row['fecha']
                                            )
                                        ),
                                        ENT_QUOTES,
                                        'UTF-8'
                                    );

                                    ?>

                                </td>


                                <!-- LIBRO -->

                                <td>

                                    <?php

                                    echo htmlspecialchars(
                                        $row['libro_nombre']
                                            ?: 'N/A',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    );

                                    ?>

                                </td>


                                <!-- CÓDIGO -->

                                <td>

                                    <?php

                                    echo htmlspecialchars(
                                        $row['codigo']
                                            ?: 'N/A',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    );

                                    ?>

                                </td>


                                <!-- FECHA ESPERADA -->

                                <td>

                                    <?php

                                    if (
                                        !empty(
                                            $row[
                                                'fecha_devolucion_esperada'
                                            ]
                                        )
                                    ) {

                                        echo date(
                                            'd/m/Y',
                                            strtotime(
                                                $row[
                                                    'fecha_devolucion_esperada'
                                                ]
                                            )
                                        );

                                    } else {

                                        echo 'No especificada';

                                    }

                                    ?>

                                </td>


                                <!-- ESTADO -->

                                <td>

                                    <span
                                        class="estado-devuelto"
                                    >

                                        <i
                                            class="fas fa-check-circle me-1"
                                        ></i>

                                        Devuelto

                                    </span>

                                </td>


                                <!-- DÍAS -->

                                <td>

                                    <?php

                                    $dias =
                                        (int)(
                                            $row[
                                                'dias_prestamo'
                                            ] ?? 0
                                        );

                                    echo $dias;

                                    ?>

                                    día<?php
                                    echo $dias == 1
                                        ? ''
                                        : 's';
                                    ?>

                                </td>


                                <!-- ALUMNO -->

                                <td>

                                    <?php

                                    echo htmlspecialchars(
                                        $row[
                                            'nombre_alumno'
                                        ]
                                            ?: 'N/A',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    );

                                    ?>


                                    <?php
                                    if (
                                        (int)$row[
                                            'es_externo'
                                        ] === 1
                                    ):
                                    ?>

                                        <br>

                                        <span
                                            class="estado-externo"
                                        >

                                            Externo

                                        </span>

                                    <?php endif; ?>

                                </td>


                                <!-- CARRERA -->

                                <td>

                                    <?php

                                    echo htmlspecialchars(
                                        $row[
                                            'carrera_nombre'
                                        ]
                                            ?: 'No especificada',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    );

                                    ?>

                                </td>


                                <!-- INSTITUCIÓN -->

                                <td>

                                    <?php

                                    echo htmlspecialchars(
                                        $row[
                                            'institucion_nombre'
                                        ]
                                            ?: 'No especificada',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    );

                                    ?>

                                </td>


                                <!-- USUARIO -->

                                <td>

                                    <?php

                                    echo htmlspecialchars(
                                        $row[
                                            'username'
                                        ]
                                            ?: 'N/A',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    );

                                    ?>

                                </td>


                                <!-- SEDE -->

                                <td>

                                    <span
                                        class="badge bg-success"
                                    >

                                        Danli

                                    </span>

                                </td>


                                <!-- OBSERVACIONES -->

                                <td>

                                    <?php

                                    echo !empty(
                                        $row[
                                            'observaciones'
                                        ]
                                    )
                                        ? htmlspecialchars(
                                            $row[
                                                'observaciones'
                                            ],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        )
                                        : '—';

                                    ?>

                                </td>


                            </tr>


                        <?php endforeach; ?>


                    <?php endif; ?>


                </tbody>

            </table>


            <?php if (count($datos) === 0): ?>

                <div class="sin-resultados">

                    <i
                        class="fas fa-folder-open d-block"
                    ></i>

                    <h5>
                        No hay préstamos devueltos
                    </h5>

                    <p>
                        No se encontraron registros
                        para los filtros seleccionados.
                    </p>

                </div>

            <?php endif; ?>


        </div>

    </div>


</div>


<!-- =====================================================
     DATATABLES
====================================================== -->

<link
    rel="stylesheet"
    href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css"
>


<script
    src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"
></script>


<script
    src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"
></script>


<script>

$(document).ready(function () {


    $('#tabla-prestamos-devueltos').DataTable({

        language: {

            url:
                '//cdn.datatables.net/plug-ins/1.10.25/i18n/Spanish.json'

        },

        order: [
            [0, 'desc']
        ],

        pageLength: 25,

        lengthMenu: [
            [10, 25, 50, 100, -1],
            [10, 25, 50, 100, 'Todos']
        ],

        responsive: false,

        scrollX: true

    });


});

</script>


<?php

include '../includes/footer.php';

?>