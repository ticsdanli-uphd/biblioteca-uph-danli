S
<?php
include '../includes/session.php';
include '../config/db.php';
include '../includes/header.php';
?>

<style>
/* =========================================================
   REPORTES - BIBLIOTECA UPH
   ========================================================= */

.reports-wrapper {
    width: 100%;
    max-width: 1500px;
    margin: 0 auto;
    padding: 25px 20px 50px;
}

/* Encabezado */
.reports-header {
    background: #ffffff;
    border-radius: 18px;
    padding: 25px 30px;
    margin-bottom: 25px;
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.07);
    border: 1px solid #e6eaf2;

    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
}

.reports-header-left {
    display: flex;
    align-items: center;
    gap: 18px;
}

.reports-header-icon {
    width: 58px;
    height: 58px;
    border-radius: 15px;

    display: flex;
    align-items: center;
    justify-content: center;

    background: linear-gradient(135deg, #3158dc, #4778f5);
    color: white;
    font-size: 25px;

    box-shadow: 0 8px 18px rgba(49, 88, 220, 0.25);
}

.reports-header h1 {
    margin: 0;
    font-size: 30px;
    font-weight: 700;
    color: #202b3c;
}

.reports-header p {
    margin: 5px 0 0;
    color: #6c7685;
    font-size: 15px;
}

.sede-badge {
    background: #eef4ff;
    color: #3158dc;
    padding: 11px 18px;
    border-radius: 12px;
    font-weight: 700;
    white-space: nowrap;
}

.sede-badge i {
    margin-right: 6px;
}

/* Título */
.section-title {
    margin: 25px 0 15px;
    color: #273449;
    font-size: 19px;
    font-weight: 700;
}

/* Grid */
.reports-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px;
}

/* Tarjetas */
.report-card {
    background: #ffffff;
    border-radius: 17px;
    overflow: hidden;

    border: 1px solid #e4e8ef;

    box-shadow: 0 7px 22px rgba(0, 0, 0, 0.06);

    transition:
        transform 0.2s ease,
        box-shadow 0.2s ease,
        border-color 0.2s ease;

    display: flex;
    flex-direction: column;
    min-height: 245px;
}

.report-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 14px 30px rgba(0, 0, 0, 0.10);
    border-color: #4778f5;
}

/* Parte superior */
.report-card-top {
    padding: 22px 22px 15px;
    display: flex;
    align-items: flex-start;
    gap: 15px;
}

.report-icon {
    width: 52px;
    height: 52px;
    min-width: 52px;

    border-radius: 14px;

    display: flex;
    align-items: center;
    justify-content: center;

    font-size: 21px;
}

.icon-blue {
    background: #e9efff;
    color: #3158dc;
}

.icon-green {
    background: #e8f8f0;
    color: #16a36a;
}

.icon-orange {
    background: #fff2dc;
    color: #f39a16;
}

.icon-purple {
    background: #f0eaff;
    color: #7651d7;
}

.icon-red {
    background: #ffe8eb;
    color: #df3545;
}

.icon-cyan {
    background: #e5f7fb;
    color: #169bb5;
}

.report-card h3 {
    margin: 0;
    color: #263247;
    font-size: 19px;
    font-weight: 700;
}

.report-card p {
    margin: 7px 0 0;
    color: #697586;
    font-size: 14px;
    line-height: 1.55;
}

/* Contenido */
.report-card-body {
    padding: 0 22px 20px;
    flex: 1;
}

.report-tag {
    display: inline-flex;
    align-items: center;

    padding: 5px 10px;
    border-radius: 20px;

    font-size: 12px;
    font-weight: 600;

    background: #f4f6fa;
    color: #697586;
}

/* Footer tarjeta */
.report-card-footer {
    border-top: 1px solid #edf0f5;
    background: #fafbfd;

    padding: 13px 20px;

    display: flex;
    align-items: center;
    justify-content: space-between;

    gap: 10px;
}

.btn-report {
    border: none;
    border-radius: 10px;

    padding: 9px 16px;

    background: #3158dc;
    color: white;

    font-size: 14px;
    font-weight: 600;

    text-decoration: none;

    display: inline-flex;
    align-items: center;
    gap: 7px;

    transition: 0.2s ease;
}

.btn-report:hover {
    background: #2448c4;
    color: white;
    transform: translateY(-1px);
}

.btn-alert {
    background: #f59d18;
}

.btn-alert:hover {
    background: #dc8709;
}

/* Indicador */
.report-arrow {
    color: #9aa4b2;
    font-size: 14px;
}

/* Aviso inferior */
.reports-info {
    margin-top: 25px;

    background: linear-gradient(
        90deg,
        #e7f3ff,
        #edf7ff
    );

    border-left: 4px solid #3975ed;

    border-radius: 12px;

    padding: 15px 18px;

    color: #3975ed;

    display: flex;
    align-items: center;
    gap: 10px;

    font-size: 14px;
    font-weight: 600;
}

.reports-info i {
    font-size: 17px;
}

/* Acciones rápidas */
.quick-actions {
    margin-top: 25px;

    background: #ffffff;
    border: 1px solid #e5e9f0;

    border-radius: 17px;

    padding: 20px;

    box-shadow: 0 7px 22px rgba(0,0,0,0.05);
}

.quick-actions-title {
    font-size: 17px;
    font-weight: 700;
    color: #273449;
    margin-bottom: 15px;
}

.quick-actions-buttons {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
}

.quick-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;

    padding: 10px 15px;

    border-radius: 10px;

    background: #f4f6fa;
    color: #334155;

    text-decoration: none;

    font-size: 14px;
    font-weight: 600;

    transition: 0.2s;
}

.quick-btn:hover {
    background: #e8edfa;
    color: #3158dc;
}

/* =========================================================
   RESPONSIVE
   ========================================================= */

@media (max-width: 1100px) {

    .reports-grid {
        grid-template-columns: repeat(2, 1fr);
    }

}

@media (max-width: 768px) {

    .reports-wrapper {
        padding: 18px 12px 35px;
    }

    .reports-header {
        padding: 20px;
        flex-direction: column;
        align-items: flex-start;
    }

    .reports-header-left {
        width: 100%;
    }

    .reports-header h1 {
        font-size: 24px;
    }

    .reports-header p {
        font-size: 14px;
    }

    .sede-badge {
        width: 100%;
        text-align: center;
    }

    .reports-grid {
        grid-template-columns: 1fr;
        gap: 15px;
    }

    .report-card {
        min-height: auto;
    }

}

@media (max-width: 480px) {

    .reports-header-left {
        align-items: flex-start;
    }

    .reports-header-icon {
        width: 48px;
        height: 48px;
        min-width: 48px;
        font-size: 20px;
    }

    .reports-header h1 {
        font-size: 21px;
    }

    .reports-card-top {
        padding: 18px;
    }

    .report-card-top {
        padding: 18px 18px 12px;
    }

    .report-card-body {
        padding: 0 18px 18px;
    }

    .report-card-footer {
        padding: 12px 15px;
    }

    .btn-report {
        width: 100%;
        justify-content: center;
    }

    .report-arrow {
        display: none;
    }

    .quick-actions-buttons {
        flex-direction: column;
    }

    .quick-btn {
        width: 100%;
        justify-content: center;
    }

}
</style>


<div class="reports-wrapper">

    <!-- ENCABEZADO -->
    <div class="reports-header">

        <div class="reports-header-left">

            <div class="reports-header-icon">
                <i class="fas fa-chart-line"></i>
            </div>

            <div>
                <h1>Reportes del Sistema</h1>

                <p>
                    Biblioteca UPH · Consulta, análisis y descarga
                    de información.
                </p>
            </div>

        </div>

        <?php
        $sede_nombre = 'Sede';
        
        if (isset($_SESSION['sede_nombre']) && !empty($_SESSION['sede_nombre'])) {
            $sede_nombre = $_SESSION['sede_nombre'];
        } elseif (isset($_SESSION['sede_seleccionada']) && !empty($_SESSION['sede_seleccionada'])) {
            $sede_nombre = 'Sede seleccionada';
        }
        ?>

        <div class="sede-badge">
            <i class="fas fa-location-dot"></i>
            <?= htmlspecialchars($sede_nombre) ?>
        </div>

    </div>


    <!-- TÍTULO -->
    <div class="section-title">
        <i class="fas fa-file-chart-column me-2"></i>
        Reportes disponibles
    </div>


    <!-- REPORTES -->
    <div class="reports-grid">


        <!-- VISITAS Y PRÉSTAMOS -->
        <div class="report-card">

            <div class="report-card-top">

                <div class="report-icon icon-blue">
                    <i class="fas fa-chart-column"></i>
                </div>

                <div>
                    <h3>Visitas y Préstamos</h3>

                    <p>
                        Reporte general de las visitas y préstamos
                        registrados en la biblioteca.
                    </p>
                </div>

            </div>

            <div class="report-card-body">

                <span class="report-tag">
                    <i class="fas fa-chart-line me-1"></i>
                    Estadísticas generales
                </span>

            </div>

            <div class="report-card-footer">

                <a href="reports.php" class="btn-report">
                    <i class="fas fa-eye"></i>
                    Ver reporte
                </a>

                <span class="report-arrow">
                    <i class="fas fa-arrow-right"></i>
                </span>

            </div>

        </div>


        <!-- PRÉSTAMOS DEVUELTOS -->
        <div class="report-card">

            <div class="report-card-top">

                <div class="report-icon icon-green">
                    <i class="fas fa-rotate-left"></i>
                </div>

                <div>
                    <h3>Préstamos Devueltos</h3>

                    <p>
                        Consulta los préstamos que han sido
                        marcados como devueltos.
                    </p>
                </div>

            </div>

            <div class="report-card-body">

                <span class="report-tag">
                    <i class="fas fa-calendar-check me-1"></i>
                    Devoluciones
                </span>

            </div>

            <div class="report-card-footer">

                <a href="prestamos_devueltos.php" class="btn-report">
                    <i class="fas fa-eye"></i>
                    Ver reporte
                </a>

                <span class="report-arrow">
                    <i class="fas fa-arrow-right"></i>
                </span>

            </div>

        </div>


        <!-- VISITAS POR CARRERA -->
        <div class="report-card">

            <div class="report-card-top">

                <div class="report-icon icon-purple">
                    <i class="fas fa-graduation-cap"></i>
                </div>

                <div>
                    <h3>Visitas por Carrera</h3>

                    <p>
                        Analiza qué carreras utilizan con mayor
                        frecuencia los servicios de la biblioteca.
                    </p>
                </div>

            </div>

            <div class="report-card-body">

                <span class="report-tag">
                    <i class="fas fa-chart-pie me-1"></i>
                    Por carrera
                </span>

            </div>

            <div class="report-card-footer">

                <a href="carreras_visitas.php" class="btn-report">
                    <i class="fas fa-eye"></i>
                    Ver reporte
                </a>

                <span class="report-arrow">
                    <i class="fas fa-arrow-right"></i>
                </span>

            </div>

        </div>


        <!-- VISITAS GLOBALES -->
        <div class="report-card">

            <div class="report-card-top">

                <div class="report-icon icon-cyan">
                    <i class="fas fa-users"></i>
                </div>

                <div>
                    <h3>Visitas Globales</h3>

                    <p>
                        Consulta todas las visitas generales
                        registradas en la biblioteca.
                    </p>
                </div>

            </div>

            <div class="report-card-body">

                <span class="report-tag">
                    <i class="fas fa-users me-1"></i>
                    Control de visitas
                </span>

            </div>

            <div class="report-card-footer">

                <a href="global_visitas.php" class="btn-report">
                    <i class="fas fa-eye"></i>
                    Ver reporte
                </a>

                <span class="report-arrow">
                    <i class="fas fa-arrow-right"></i>
                </span>

            </div>

        </div>


        <!-- REPORTE DE LIBROS -->
        <div class="report-card">

            <div class="report-card-top">

                <div class="report-icon icon-orange">
                    <i class="fas fa-book"></i>
                </div>

                <div>
                    <h3>Reporte de Libros</h3>

                    <p>
                        Consulta información y estadísticas
                        relacionadas con el inventario bibliográfico.
                    </p>
                </div>

            </div>

            <div class="report-card-body">

                <span class="report-tag">
                    <i class="fas fa-book-open me-1"></i>
                    Inventario bibliográfico
                </span>

            </div>

            <div class="report-card-footer">

                <a href="reporte_libros.php" class="btn-report">
                    <i class="fas fa-eye"></i>
                    Ver reporte
                </a>

                <span class="report-arrow">
                    <i class="fas fa-arrow-right"></i>
                </span>

            </div>

        </div>


        <!-- ALERTAS -->
        <div class="report-card">

            <div class="report-card-top">

                <div class="report-icon icon-red">
                    <i class="fas fa-bell"></i>
                </div>

                <div>
                    <h3>Alertas de Préstamos</h3>

                    <p>
                        Consulta préstamos activos, pendientes
                        y devoluciones que requieren atención.
                    </p>
                </div>

            </div>

            <div class="report-card-body">

                <span class="report-tag">
                    <i class="fas fa-triangle-exclamation me-1"></i>
                    Seguimiento
                </span>

            </div>

            <div class="report-card-footer">

                <a href="../alertas.php" class="btn-report btn-alert">
                    <i class="fas fa-bell"></i>
                    Ver alertas
                </a>

                <span class="report-arrow">
                    <i class="fas fa-arrow-right"></i>
                </span>

            </div>

        </div>


    </div>


    <!-- ACCIONES RÁPIDAS -->
    <div class="quick-actions">

        <div class="quick-actions-title">
            <i class="fas fa-bolt me-2"></i>
            Acciones rápidas
        </div>

        <div class="quick-actions-buttons">

            <a href="reports.php" class="quick-btn">
                <i class="fas fa-chart-line"></i>
                Visitas y préstamos
            </a>

            <a href="prestamos_devueltos.php" class="quick-btn">
                <i class="fas fa-rotate-left"></i>
                Préstamos devueltos
            </a>

            <a href="carreras_visitas.php" class="quick-btn">
                <i class="fas fa-graduation-cap"></i>
                Visitas por carrera
            </a>

            <a href="global_visitas.php" class="quick-btn">
                <i class="fas fa-users"></i>
                Visitas globales
            </a>

            <a href="reporte_libros.php" class="quick-btn">
                <i class="fas fa-book"></i>
                Libros
            </a>

            <a href="../alertas.php" class="quick-btn">
                <i class="fas fa-bell"></i>
                Alertas
            </a>

        </div>

    </div>


    <!-- INFORMACIÓN -->
    <div class="reports-info">

        <i class="fas fa-circle-info"></i>

        <span>
            Los reportes pueden consultarse por diferentes criterios
            y utilizarse para el control y seguimiento de las actividades
            de la Biblioteca UPH.
        </span>

    </div>

</div>


<?php
include '../includes/footer.php';
?>