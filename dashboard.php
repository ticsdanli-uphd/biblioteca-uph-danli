<?php

include 'includes/session.php';
include 'config/db.php';


// =====================================================
// CONFIGURACIÓN
// =====================================================

$sede_id = 4;
$nombre_sede = 'Danlí';


// =====================================================
// FUNCIÓN SEGURA PARA CONSULTAS
// =====================================================

function obtenerValor($conn, $sql, $campo = 'total')
{
    $resultado = $conn->query($sql);

    if (!$resultado) {
        return 0;
    }

    $fila = $resultado->fetch_assoc();

    return isset($fila[$campo])
        ? (int)$fila[$campo]
        : 0;
}


// =====================================================
// TOTAL DE LIBROS
// =====================================================

$totalLibros = obtenerValor(
    $conn,
    "
    SELECT COALESCE(SUM(cantidad), 0) AS total
    FROM bibliografia
    WHERE sede_id = $sede_id
    "
);


// =====================================================
// LIBROS DISPONIBLES
// =====================================================

$librosDisponibles = obtenerValor(
    $conn,
    "
    SELECT COALESCE(SUM(cantidad), 0) AS total
    FROM bibliografia
    WHERE sede_id = $sede_id
    AND estado = 'Disponible'
    "
);


// =====================================================
// LIBROS PRESTADOS
// =====================================================

$librosPrestados = obtenerValor(
    $conn,
    "
    SELECT COUNT(*) AS total
    FROM registro_visitas r
    INNER JOIN bibliografia b
        ON r.bibliografia_id = b.id
    WHERE b.sede_id = $sede_id
    AND r.tipo = 'prestamo'
    AND r.devuelto = 0
    "
);


// =====================================================
// PRÉSTAMOS VENCIDOS
// =====================================================

$prestamosVencidos = obtenerValor(
    $conn,
    "
    SELECT COUNT(*) AS total
    FROM registro_visitas r
    INNER JOIN bibliografia b
        ON r.bibliografia_id = b.id
    WHERE b.sede_id = $sede_id
    AND r.tipo = 'prestamo'
    AND r.devuelto = 0
    AND r.fecha_devolucion_esperada IS NOT NULL
    AND r.fecha_devolucion_esperada < CURDATE()
    "
);


// =====================================================
// VISITAS DEL MES
// =====================================================

$visitasMes = obtenerValor(
    $conn,
    "
    SELECT COUNT(*) AS total
    FROM registro_visitas r
    INNER JOIN usuarios u
        ON r.user_id = u.id
    WHERE u.sede_id = $sede_id
    AND r.tipo = 'visita'
    AND MONTH(r.fecha) = MONTH(CURDATE())
    AND YEAR(r.fecha) = YEAR(CURDATE())
    "
);


// =====================================================
// TOTAL TESIS
// =====================================================

$totalTesis = obtenerValor(
    $conn,
    "
    SELECT COUNT(*) AS total
    FROM tesis
    WHERE sede_id = $sede_id
    "
);


// =====================================================
// RESERVAS PENDIENTES
// =====================================================

$reservasPendientes = obtenerValor(
    $conn,
    "
    SELECT COUNT(*) AS total
    FROM reservas_libros r
    INNER JOIN bibliografia b
        ON r.bibliografia_id = b.id
    WHERE b.sede_id = $sede_id
    AND r.estado = 'pendiente'
    "
);


// =====================================================
// ACTIVIDAD MENSUAL
// =====================================================

$meses = [
    'Enero',
    'Febrero',
    'Marzo',
    'Abril',
    'Mayo',
    'Junio',
    'Julio',
    'Agosto',
    'Septiembre',
    'Octubre',
    'Noviembre',
    'Diciembre'
];

$visitasMensuales = array_fill(0, 12, 0);
$prestamosMensuales = array_fill(0, 12, 0);


// -----------------------------------------------------
// VISITAS POR MES
// -----------------------------------------------------

$sqlVisitasMensuales = "
    SELECT
        MONTH(r.fecha) AS mes,
        COUNT(*) AS total
    FROM registro_visitas r
    INNER JOIN usuarios u
        ON r.user_id = u.id
    WHERE u.sede_id = $sede_id
    AND r.tipo = 'visita'
    AND YEAR(r.fecha) = YEAR(CURDATE())
    GROUP BY MONTH(r.fecha)
    ORDER BY MONTH(r.fecha)
";

$resultVisitasMensuales =
    $conn->query($sqlVisitasMensuales);

if ($resultVisitasMensuales) {

    while (
        $fila =
        $resultVisitasMensuales->fetch_assoc()
    ) {

        $indice =
            ((int)$fila['mes']) - 1;

        $visitasMensuales[$indice] =
            (int)$fila['total'];
    }
}


// -----------------------------------------------------
// PRÉSTAMOS POR MES
// -----------------------------------------------------

$sqlPrestamosMensuales = "
    SELECT
        MONTH(r.fecha) AS mes,
        COUNT(*) AS total
    FROM registro_visitas r
    INNER JOIN bibliografia b
        ON r.bibliografia_id = b.id
    WHERE b.sede_id = $sede_id
    AND r.tipo = 'prestamo'
    AND YEAR(r.fecha) = YEAR(CURDATE())
    GROUP BY MONTH(r.fecha)
    ORDER BY MONTH(r.fecha)
";

$resultPrestamosMensuales =
    $conn->query($sqlPrestamosMensuales);

if ($resultPrestamosMensuales) {

    while (
        $fila =
        $resultPrestamosMensuales->fetch_assoc()
    ) {

        $indice =
            ((int)$fila['mes']) - 1;

        $prestamosMensuales[$indice] =
            (int)$fila['total'];
    }
}


// =====================================================
// TESIS POR CARRERA
// =====================================================

$tesisCarreras = [];
$tesisCantidades = [];

$sqlTesisCarreras = "
    SELECT
        carrera,
        COUNT(*) AS total
    FROM tesis
    WHERE sede_id = $sede_id
    GROUP BY carrera
    ORDER BY total DESC
";

$resultTesisCarreras =
    $conn->query($sqlTesisCarreras);

if ($resultTesisCarreras) {

    while (
        $fila =
        $resultTesisCarreras->fetch_assoc()
    ) {

        $tesisCarreras[] =
            $fila['carrera'];

        $tesisCantidades[] =
            (int)$fila['total'];
    }
}


// =====================================================
// PORCENTAJE DE DISPONIBILIDAD
// =====================================================

$porcentajeDisponibles = 0;

if ($totalLibros > 0) {

    $porcentajeDisponibles =
        round(
            ($librosDisponibles / $totalLibros) * 100
        );

}


// =====================================================
// CARGAR HEADER
// =====================================================

include 'includes/header.php';

?>


<style>
/* =====================================================
   DASHBOARD BIBLIOTECA UPH - RESPONSIVE
   Solo Danlí
===================================================== */

.dashboard-container{
    width:100%;
    max-width:1250px;
    margin:0 auto;
    padding:10px 15px 30px;
    box-sizing:border-box;
}

/* ENCABEZADO */
.dashboard-title-card{
    background:#fff;
    border-radius:20px;
    padding:28px 32px;
    box-shadow:0 10px 30px rgba(0,0,0,.08);
    margin-bottom:24px;
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:20px;
}

.dashboard-title{
    margin:0;
    color:#3159d8 !important;
    font-size:clamp(24px,4vw,36px);
    line-height:1.2;
    font-weight:700;
}

.dashboard-title i{
    margin-right:8px;
}

.sede-indicator{
    flex-shrink:0;
}

.sede-button{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    gap:7px;
    background:#3159d8;
    color:#fff !important;
    border-radius:30px;
    padding:12px 22px;
    font-weight:600;
    white-space:nowrap;
}

/* TARJETAS */
.stats-grid{
    display:grid;
    grid-template-columns:repeat(4,minmax(0,1fr));
    gap:20px;
    margin-bottom:24px;
}

.stat-card{
    background:#fff;
    border-radius:20px;
    padding:24px;
    min-height:215px;
    box-shadow:0 10px 30px rgba(0,0,0,.08);
    position:relative;
    overflow:hidden;
    box-sizing:border-box;
}

.stat-card::before{
    content:"";
    position:absolute;
    top:0;
    left:0;
    right:0;
    height:4px;
}

.stat-blue::before{background:#3159d8}
.stat-green::before{background:#20b978}
.stat-orange::before{background:#ff9f1c}
.stat-red::before{background:#f25f63}
.stat-purple::before{background:#7357d8}

.stat-icon{
    width:70px;
    height:70px;
    border-radius:18px;
    display:flex;
    align-items:center;
    justify-content:center;
    color:#fff !important;
    font-size:30px;
    margin-bottom:15px;
}

.icon-blue{background:#3159d8}
.icon-green{background:#20b978}
.icon-orange{background:#ff9f1c}
.icon-red{background:#f25f63}
.icon-purple{background:#7357d8}

.stat-number{
    font-size:clamp(28px,3vw,38px);
    font-weight:700;
    color:#202b3c !important;
    line-height:1;
}

.stat-label{
    color:#718096 !important;
    font-size:15px;
    margin-top:8px;
}

.stat-location{
    color:#20b978 !important;
    font-weight:600;
    margin-top:8px;
}

.stat-link{
    display:inline-block;
    margin-top:16px;
    color:#3159d8 !important;
    font-weight:600;
    text-decoration:none;
}

.stat-link:hover{
    text-decoration:underline;
}

/* GRÁFICAS */
.dashboard-grid{
    display:grid;
    grid-template-columns:minmax(0,2fr) minmax(280px,1fr);
    gap:20px;
    margin-bottom:24px;
}

.dashboard-card{
    background:#fff;
    border-radius:20px;
    box-shadow:0 10px 30px rgba(0,0,0,.08);
    overflow:hidden;
    min-width:0;
}

.dashboard-card-header{
    padding:20px 24px;
    border-top:4px solid #3159d8;
    display:flex;
    align-items:center;
    gap:14px;
}

.dashboard-card-icon{
    width:48px;
    height:48px;
    flex:0 0 48px;
    background:#3159d8;
    color:#fff !important;
    border-radius:14px;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:20px;
}

.dashboard-card-title{
    margin:0;
    font-size:20px;
    font-weight:700;
    color:#202b3c !important;
}

.dashboard-card-subtitle{
    margin:3px 0 0;
    color:#718096 !important;
    font-size:14px;
}

.chart-container{
    position:relative;
    width:100%;
    height:340px;
    padding:15px 20px 25px;
    box-sizing:border-box;
}

.chart-container canvas{
    max-width:100% !important;
}

/* ALERTAS */
.alert-card{
    padding:22px;
}

.alert-item{
    display:flex;
    align-items:center;
    gap:12px;
    padding:15px;
    border-radius:12px;
    background:#f8fafc;
    margin-bottom:12px;
}

.alert-item:last-child{
    margin-bottom:0;
}

.alert-icon{
    width:45px;
    height:45px;
    flex:0 0 45px;
    border-radius:12px;
    display:flex;
    align-items:center;
    justify-content:center;
    color:#fff !important;
}

.alert-danger{background:#f25f63}
.alert-warning{background:#ff9f1c}
.alert-primary{background:#3159d8}
.alert-success{background:#20b978}

.alert-info-text{
    flex:1;
    min-width:0;
    margin-left:0;
}

.alert-info-text strong{
    display:block;
    color:#202b3c !important;
}

.alert-info-text span{
    color:#718096 !important;
    font-size:13px;
}

.alert-number{
    flex:0 0 auto;
    font-size:23px;
    font-weight:700;
    color:#202b3c !important;
}

/* TABLETS */
@media (max-width:1100px){
    .stats-grid{
        grid-template-columns:repeat(2,minmax(0,1fr));
    }

    .dashboard-grid{
        grid-template-columns:1fr;
    }

    .chart-container{
        height:320px;
    }
}

/* MÓVILES */
@media (max-width:700px){
    .dashboard-container{
        padding:8px 10px 25px;
    }

    .dashboard-title-card{
        padding:20px;
        flex-direction:column;
        align-items:flex-start;
        border-radius:16px;
    }

    .dashboard-title{
        font-size:27px;
    }

    .sede-button{
        width:100%;
        box-sizing:border-box;
    }

    .stats-grid{
        grid-template-columns:1fr;
        gap:15px;
    }

    .stat-card{
        min-height:auto;
        padding:20px;
        border-radius:16px;
    }

    .stat-icon{
        width:58px;
        height:58px;
        font-size:25px;
        border-radius:15px;
    }

    .dashboard-grid{
        grid-template-columns:1fr;
        gap:15px;
    }

    .dashboard-card{
        border-radius:16px;
    }

    .dashboard-card-header{
        padding:17px;
    }

    .dashboard-card-title{
        font-size:17px;
    }

    .dashboard-card-subtitle{
        font-size:12px;
    }

    .chart-container{
        height:280px;
        padding:12px;
    }

    .alert-card{
        padding:14px;
    }

    .alert-item{
        align-items:flex-start;
        padding:12px;
    }

    .alert-info-text span{
        display:block;
        line-height:1.35;
    }

    .alert-number{
        font-size:19px;
    }
}

/* TELÉFONOS PEQUEÑOS */
@media (max-width:430px){
    .dashboard-title{
        font-size:23px;
    }

    .dashboard-title-card{
        padding:17px;
    }

    .dashboard-card-header{
        gap:10px;
    }

    .dashboard-card-icon{
        width:42px;
        height:42px;
        flex-basis:42px;
        font-size:17px;
    }

    .chart-container{
        height:245px;
    }

    .alert-item{
        flex-wrap:wrap;
    }

    .alert-number{
        margin-left:auto;
    }
}

/* EVITAR DESBORDAMIENTO */
.dashboard-container,
.dashboard-container *{
    max-width:100%;
}

.dashboard-container .row{
    margin-left:0;
    margin-right:0;
}
</style>


<div class="dashboard-container">


    <!-- =================================================
         ENCABEZADO
    ================================================== -->

    <div class="dashboard-title-card">

        <div>

            <h1 class="dashboard-title">

                <i class="fas fa-chart-line"></i>

                Dashboard Biblioteca UPH

            </h1>

        </div>


        <div class="sede-indicator">

            <div class="sede-button">

                <i class="fas fa-map-marker-alt"></i>

                Sede: Danlí

            </div>

        </div>

    </div>


    <!-- =================================================
         TARJETAS PRINCIPALES
    ================================================== -->

    <div class="stats-grid">


        <!-- LIBROS -->

        <div class="stat-card stat-blue">

            <div class="stat-icon icon-blue">

                <i class="fas fa-book"></i>

            </div>

            <div class="stat-number">

                <?php echo number_format($totalLibros); ?>

            </div>

            <div class="stat-label">

                Total Libros

            </div>

            <div class="stat-location">

                <i class="fas fa-book"></i>
                Biblioteca Danlí

            </div>

            <a
                href="books/list.php"
                class="stat-link"
            >

                Ver detalles
                <i class="fas fa-arrow-right"></i>

            </a>

        </div>


        <!-- TESIS -->

        <div class="stat-card stat-blue">

            <div class="stat-icon icon-blue">

                <i class="fas fa-graduation-cap"></i>

            </div>

            <div class="stat-number">

                <?php echo number_format($totalTesis); ?>

            </div>

            <div class="stat-label">

                Total Tesis

            </div>

            <div class="stat-location">

                <i class="fas fa-graduation-cap"></i>
                Danlí

            </div>

            <a
                href="tesis/list.php"
                class="stat-link"
            >

                Ver detalles
                <i class="fas fa-arrow-right"></i>

            </a>

        </div>


        <!-- VISITAS -->

        <div class="stat-card stat-orange">

            <div class="stat-icon icon-orange">

                <i class="fas fa-users"></i>

            </div>

            <div class="stat-number">

                <?php echo number_format($visitasMes); ?>

            </div>

            <div class="stat-label">

                Visitas del Mes

            </div>

            <div class="stat-location">

                <i class="fas fa-map-marker-alt"></i>
                Danlí

            </div>

            <a
                href="registrar_visita.php"
                class="stat-link"
            >

                Registrar visita
                <i class="fas fa-arrow-right"></i>

            </a>

        </div>


        <!-- PRÉSTAMOS -->

        <div class="stat-card stat-red">

            <div class="stat-icon icon-red">

                <i class="fas fa-handshake"></i>

            </div>

            <div class="stat-number">

                <?php echo number_format($librosPrestados); ?>

            </div>

            <div class="stat-label">

                Préstamos Activos

            </div>

            <div class="stat-location">

                <i class="fas fa-book"></i>
                Danlí

            </div>

            <a
                href="alertas.php"
                class="stat-link"
            >

                Ver alertas
                <i class="fas fa-arrow-right"></i>

            </a>

        </div>


    </div>


    <!-- =================================================
         SEGUNDA FILA
    ================================================== -->

    <div class="stats-grid">


        <!-- DISPONIBLES -->

        <div class="stat-card stat-green">

            <div class="stat-icon icon-green">

                <i class="fas fa-book-open"></i>

            </div>

            <div class="stat-number">

                <?php
                echo number_format(
                    $librosDisponibles
                );
                ?>

            </div>

            <div class="stat-label">

                Libros Disponibles

            </div>

            <div class="stat-location">

                <?php
                echo $porcentajeDisponibles;
                ?>% del catálogo

            </div>

        </div>


        <!-- VENCIDOS -->

        <div class="stat-card stat-red">

            <div class="stat-icon icon-red">

                <i class="fas fa-exclamation-triangle"></i>

            </div>

            <div class="stat-number">

                <?php
                echo number_format(
                    $prestamosVencidos
                );
                ?>

            </div>

            <div class="stat-label">

                Préstamos Vencidos

            </div>

            <div class="stat-location">

                <?php if ($prestamosVencidos > 0): ?>

                    <span style="color:#f25f63;">
                        Requieren atención
                    </span>

                <?php else: ?>

                    <span>
                        Todo al día
                    </span>

                <?php endif; ?>

            </div>

        </div>


        <!-- RESERVAS -->

        <div class="stat-card stat-purple">

            <div class="stat-icon icon-purple">

                <i class="fas fa-bookmark"></i>

            </div>

            <div class="stat-number">

                <?php
                echo number_format(
                    $reservasPendientes
                );
                ?>

            </div>

            <div class="stat-label">

                Reservas Pendientes

            </div>

            <div class="stat-location">

                <i class="fas fa-map-marker-alt"></i>
                Danlí

            </div>

        </div>


        <!-- ESTADO -->

        <div class="stat-card stat-green">

            <div class="stat-icon icon-green">

                <i class="fas fa-check-circle"></i>

            </div>

            <div class="stat-number">

                <?php
                echo $porcentajeDisponibles;
                ?>%

            </div>

            <div class="stat-label">

                Disponibilidad

            </div>

            <div class="stat-location">

                Catálogo disponible

            </div>

        </div>


    </div>


    <!-- =================================================
         GRÁFICAS
    ================================================== -->

    <div class="dashboard-grid">


        <!-- ACTIVIDAD -->

        <div class="dashboard-card">

            <div class="dashboard-card-header">

                <div class="dashboard-card-icon">

                    <i class="fas fa-chart-line"></i>

                </div>

                <div>

                    <h3 class="dashboard-card-title">

                        Actividad Mensual

                    </h3>

                    <p class="dashboard-card-subtitle">

                        Visitas + Préstamos — Danlí

                    </p>

                </div>

            </div>


            <div class="chart-container">

                <canvas id="actividadChart"></canvas>

            </div>

        </div>


        <!-- TESIS -->

        <div class="dashboard-card">

            <div class="dashboard-card-header">

                <div class="dashboard-card-icon">

                    <i class="fas fa-graduation-cap"></i>

                </div>

                <div>

                    <h3 class="dashboard-card-title">

                        Distribución de Tesis

                    </h3>

                    <p class="dashboard-card-subtitle">

                        Por carrera — Danlí

                    </p>

                </div>

            </div>


            <div
                class="chart-container"
                style="height:340px;"
            >

                <canvas id="tesisChart"></canvas>

            </div>

        </div>


    </div>


    <!-- =================================================
         ALERTAS
    ================================================== -->

    <div class="dashboard-card mb-4">

        <div class="dashboard-card-header">

            <div class="dashboard-card-icon">

                <i class="fas fa-bell"></i>

            </div>

            <div>

                <h3 class="dashboard-card-title">

                    Estado de la Biblioteca

                </h3>

                <p class="dashboard-card-subtitle">

                    Indicadores importantes de Danlí

                </p>

            </div>

        </div>


        <div class="alert-card">


            <!-- VENCIDOS -->

            <div class="alert-item">

                <div class="alert-icon alert-danger">

                    <i class="fas fa-exclamation-triangle"></i>

                </div>

                <div class="alert-info-text">

                    <strong>
                        Préstamos vencidos
                    </strong>

                    <span>
                        Libros que deben ser devueltos
                    </span>

                </div>

                <div class="alert-number">

                    <?php
                    echo number_format(
                        $prestamosVencidos
                    );
                    ?>

                </div>

            </div>


            <!-- RESERVAS -->

            <div class="alert-item">

                <div class="alert-icon alert-warning">

                    <i class="fas fa-bookmark"></i>

                </div>

                <div class="alert-info-text">

                    <strong>
                        Reservas pendientes
                    </strong>

                    <span>
                        Solicitudes pendientes de atención
                    </span>

                </div>

                <div class="alert-number">

                    <?php
                    echo number_format(
                        $reservasPendientes
                    );
                    ?>

                </div>

            </div>


            <!-- DISPONIBLES -->

            <div class="alert-item">

                <div class="alert-icon alert-success">

                    <i class="fas fa-book-open"></i>

                </div>

                <div class="alert-info-text">

                    <strong>
                        Libros disponibles
                    </strong>

                    <span>
                        Ejemplares disponibles para préstamo
                    </span>

                </div>

                <div class="alert-number">

                    <?php
                    echo number_format(
                        $librosDisponibles
                    );
                    ?>

                </div>

            </div>


        </div>

    </div>


</div>


<!-- =====================================================
     CHART.JS
====================================================== -->

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>


<script>

document.addEventListener(
    'DOMContentLoaded',
    function () {


        // =================================================
        // ACTIVIDAD MENSUAL
        // =================================================

        const actividadCanvas =
            document.getElementById(
                'actividadChart'
            );


        if (actividadCanvas) {

            new Chart(
                actividadCanvas,
                {

                    type: 'line',

                    data: {

                        labels: <?php
                            echo json_encode(
                                $meses,
                                JSON_UNESCAPED_UNICODE
                            );
                        ?>,

                        datasets: [

                            {

                                label: 'Visitas',

                                data: <?php
                                    echo json_encode(
                                        $visitasMensuales
                                    );
                                ?>,

                                borderColor: '#3159d8',

                                backgroundColor:
                                    'rgba(49,89,216,0.10)',

                                tension: 0.35,

                                fill: true,

                                borderWidth: 3,

                                pointRadius: 4

                            },


                            {

                                label: 'Préstamos',

                                data: <?php
                                    echo json_encode(
                                        $prestamosMensuales
                                    );
                                ?>,

                                borderColor: '#ff9f1c',

                                backgroundColor:
                                    'rgba(255,159,28,0.08)',

                                tension: 0.35,

                                fill: true,

                                borderWidth: 3,

                                pointRadius: 4

                            }

                        ]

                    },

                    options: {

                        responsive: true,

                        maintainAspectRatio: false,

                        interaction: {

                            intersect: false,

                            mode: 'index'

                        },

                        plugins: {

                            legend: {

                                position: 'top'

                            }

                        },

                        scales: {

                            y: {

                                beginAtZero: true,

                                ticks: {

                                    precision: 0

                                }

                            }

                        }

                    }

                }
            );

        }


        // =================================================
        // TESIS
        // =================================================

        const tesisCanvas =
            document.getElementById(
                'tesisChart'
            );


        if (tesisCanvas) {


            const tesisLabels =
                <?php
                echo json_encode(
                    $tesisCarreras,
                    JSON_UNESCAPED_UNICODE
                );
                ?>;


            const tesisData =
                <?php
                echo json_encode(
                    $tesisCantidades
                );
                ?>;


            if (tesisLabels.length > 0) {

                new Chart(
                    tesisCanvas,
                    {

                        type: 'doughnut',

                        data: {

                            labels: tesisLabels,

                            datasets: [

                                {

                                    data: tesisData,

                                    backgroundColor: [

                                        '#3159d8',
                                        '#20b978',
                                        '#ff9f1c',
                                        '#f25f63',
                                        '#7357d8',
                                        '#2d86bc',
                                        '#eab416',
                                        '#607d8b'

                                    ],

                                    borderWidth: 2

                                }

                            ]

                        },

                        options: {

                            responsive: true,

                            maintainAspectRatio: false,

                            plugins: {

                                legend: {

                                    position: 'bottom'

                                }

                            }

                        }

                    }
                );

            } else {

                const contexto =
                    tesisCanvas.getContext(
                        '2d'
                    );

                contexto.font =
                    '16px Arial';

                contexto.fillStyle =
                    '#718096';

                contexto.textAlign =
                    'center';

                contexto.fillText(
                    'No hay tesis registradas',
                    tesisCanvas.width / 2,
                    170
                );

            }

        }

    }

);

</script>


<?php

include 'includes/footer.php';

?>