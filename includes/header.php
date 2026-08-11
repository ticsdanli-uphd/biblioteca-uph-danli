<?php

/* =========================================================
   CONFIGURACIÓN
========================================================= */

include_once __DIR__ . '/../config/db.php';


/* =========================================================
   SESIÓN
========================================================= */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


/* =========================================================
   VERIFICAR USUARIO
========================================================= */

$role = $_SESSION['role'] ?? '';

$username = $_SESSION['username'] ?? 'Invitado';

$nombreUsuario =
    $_SESSION['nombre_completo']
    ?? $_SESSION['nombre']
    ?? $username;


/* =========================================================
   SEDE FIJA: DANLÍ
========================================================= */

$sedeName = 'Danlí';

$currentSede = 4;


/*
|--------------------------------------------------------------------------
| Guardar siempre Danlí
|--------------------------------------------------------------------------
*/

$_SESSION['sede_seleccionada'] = 4;


/* =========================================================
   NORMALIZAR ROL
========================================================= */

$roleLower = strtolower(trim($role));


/*
|--------------------------------------------------------------------------
| Determinar tipo de usuario
|--------------------------------------------------------------------------
*/

$esAdmin =
    $roleLower === 'admin';

$esDocente =
    in_array(
        $roleLower,
        ['docente', 'teacher'],
        true
    );

$esAlumno =
    in_array(
        $roleLower,
        ['usuario', 'alumno', 'student'],
        true
    );


/* =========================================================
   SOLICITUDES PENDIENTES PARA ADMINISTRADOR
========================================================= */
$solicitudesPendientes = 0;

if ($esAdmin && isset($conn) && $conn instanceof mysqli) {
    $stmtPend = $conn->prepare("
        SELECT COUNT(*) AS total
        FROM solicitudes_prestamo
        WHERE sede_id = 4
          AND estado = 'pendiente'
    ");

    if ($stmtPend) {
        $stmtPend->execute();
        $solicitudesPendientes = (int)($stmtPend->get_result()->fetch_assoc()['total'] ?? 0);
        $stmtPend->close();
    }
}


/* =========================================================
   TEXTO DEL ROL
========================================================= */

if ($esAdmin) {

    $rolTexto = 'Administrador';

} elseif ($esDocente) {

    $rolTexto = 'Docente';

} elseif ($esAlumno) {

    $rolTexto = 'Alumno';

} else {

    $rolTexto =
        ucfirst(
            $roleLower ?: 'Usuario'
        );

}

?>

<!DOCTYPE html>

<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0, viewport-fit=cover"
    >

    <meta
        name="theme-color"
        content="#2563eb"
    >

    <title>
        Sistema de Biblioteca UPH
    </title>


    <!-- =====================================================
         BOOTSTRAP
    ====================================================== -->

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >


    <!-- =====================================================
         FONT AWESOME
    ====================================================== -->

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css"
    >


    <!-- =====================================================
         GOOGLE FONT
    ====================================================== -->

    <link
        rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
    >


    <!-- =====================================================
         SWEET ALERT
    ====================================================== -->

    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css"
    >


    <!-- =====================================================
         RESPONSIVE CSS
    ====================================================== -->

    <link
        rel="stylesheet"
        href="/biblioteca/assets/css/responsive.css"
    >


    <!-- =====================================================
         ESTILOS PRINCIPALES
    ====================================================== -->

    <style>

        :root {

            --primary-color: #2563eb;

            --primary-dark: #1d4ed8;

            --primary-light: #3b82f6;

            --secondary-color: #64748b;

            --success-color: #198754;

            --warning-color: #f59e0b;

            --danger-color: #dc3545;

            --info-color: #0dcaf0;

            --light-bg: #f8fafc;

            --card-bg: #ffffff;

            --text-primary: #1e293b;

            --text-secondary: #64748b;

            --border-color: #e2e8f0;

            --shadow-sm:
                0 1px 3px rgba(0,0,0,.08);

            --shadow-md:
                0 4px 15px rgba(0,0,0,.10);

            --shadow-lg:
                0 10px 30px rgba(0,0,0,.15);

            --gradient:
                linear-gradient(
                    135deg,
                    #2563eb 0%,
                    #1d4ed8 100%
                );

        }


        /* =====================================================
           BODY
        ====================================================== */

        html,
        body {

            width: 100%;

            min-height: 100%;

            margin: 0;

            padding: 0;

        }


        body {

            font-family:
                'Inter',
                sans-serif;

            background:
                linear-gradient(
                    135deg,
                    #f8fafc 0%,
                    #e2e8f0 100%
                );

            color:
                var(--text-primary);

            line-height: 1.6;

            overflow-x: hidden;

        }


        /* =====================================================
           NAVBAR
        ====================================================== */

        .navbar {

            background:
                var(--gradient)
                !important;

            box-shadow:
                0 4px 20px
                rgba(0,0,0,.12);

            min-height: 70px;

            position: sticky;

            top: 0;

            z-index: 1050;

        }


        .navbar-brand {

            color: #fff !important;

            font-weight: 800;

            font-size: 1.35rem;

            text-decoration: none;

            white-space: nowrap;

        }


        .navbar-brand:hover {

            color: #fff !important;

        }


        /* =====================================================
           BOTÓN MENÚ
        ====================================================== */

        .menu-button {

            width: 44px;

            height: 44px;

            display: flex;

            align-items: center;

            justify-content: center;

            border-radius: 10px;

            border:
                1px solid
                rgba(255,255,255,.35);

            color: #fff;

            background:
                rgba(255,255,255,.10);

        }


        .menu-button:hover {

            background:
                rgba(255,255,255,.20);

            color: #fff;

        }


        /* =====================================================
           SEDE
        ====================================================== */

        .sede-info {

            display: inline-flex;

            align-items: center;

            gap: 6px;

            padding:
                8px 14px;

            color: #fff;

            background:
                rgba(255,255,255,.12);

            border:
                1px solid
                rgba(255,255,255,.25);

            border-radius: 10px;

            white-space: nowrap;

            font-size: .9rem;

        }


        .sede-info strong {

            color: #fff;

            font-weight: 700;

        }


        /* =====================================================
           USUARIO
        ====================================================== */

        .user-btn {

            display: flex;

            align-items: center;

            gap: 10px;

            color: #1e293b !important;

            background: #fff !important;

            border: none !important;

            border-radius: 12px;

            padding:
                6px 12px;

        }


        .user-btn:hover {

            background:
                #f8fafc !important;

        }


        .user-avatar {

            width: 36px;

            height: 36px;

            border-radius: 50%;

            display: flex;

            align-items: center;

            justify-content: center;

            color: #fff;

            background:
                var(--gradient);

        }


        .user-info {

            display: flex;

            flex-direction: column;

            align-items: flex-start;

            line-height: 1.15;

        }


        .user-name {

            color: #1e293b !important;

            font-size: .85rem;

            font-weight: 700;

            max-width: 180px;

            overflow: hidden;

            text-overflow: ellipsis;

            white-space: nowrap;

        }


        .user-role {

            color: #64748b !important;

            font-size: .72rem;

        }


        /* =====================================================
           DROPDOWN USUARIO
        ====================================================== */

        .user-dropdown-menu {

            min-width: 280px;

            border: none;

            border-radius: 14px;

            box-shadow:
                var(--shadow-lg);

            padding: 10px;

        }


        .user-dropdown-menu
        .dropdown-item {

            border-radius: 9px;

            padding:
                10px 12px;

            font-weight: 500;

        }


        .user-dropdown-menu
        .dropdown-item:hover {

            background:
                #eff6ff;

            color:
                var(--primary-color);

        }


        /* =====================================================
           OFFCANVAS
        ====================================================== */

        .offcanvas {

            width: 320px !important;

            background:
                #fff;

            border: none;

            box-shadow:
                var(--shadow-lg);

        }


        .offcanvas-header {

            background:
                var(--gradient);

            color: #fff;

            min-height: 70px;

        }


        .offcanvas-title {

            color: #fff !important;

            font-weight: 800;

        }


        .offcanvas .btn-close {

            filter:
                brightness(0)
                invert(1);

        }


        .offcanvas-body {

            padding:
                12px 8px;

            overflow-y: auto;

        }


        /* =====================================================
           SECCIONES DEL MENÚ
        ====================================================== */

        .sidebar-section-title {

            color:
                var(--primary-color);

            font-size:
                .75rem;

            font-weight:
                800;

            text-transform:
                uppercase;

            letter-spacing:
                .08em;

            margin:
                18px 12px 8px;

            padding:
                8px 12px;

            background:
                #eff6ff;

            border-radius:
                8px;

            border-left:
                4px solid
                var(--primary-color);

        }


        /* =====================================================
           ENLACES DEL SIDEBAR
        ====================================================== */

        .sidebar-link {

            display:
                flex !important;

            align-items:
                center;

            gap:
                10px;

            color:
                #334155 !important;

            text-decoration:
                none;

            padding:
                11px 14px;

            margin:
                3px 8px;

            border-radius:
                10px;

            font-weight:
                500;

            transition:
                all .2s ease;

        }


        .sidebar-link i {

            width:
                24px;

            text-align:
                center;

            color:
                var(--primary-color);

        }


        .sidebar-link:hover {

            background:
                #eff6ff;

            color:
                var(--primary-color) !important;

            transform:
                translateX(4px);

        }


        .sidebar-link:hover i {

            color:
                var(--primary-dark);

        }


        /* =====================================================
           BADGE DE SOLICITUDES
        ====================================================== */

        .solicitudes-badge {

            margin-left:
                auto;

            font-size:
                .7rem;

        }


        /* =====================================================
           CARDS
        ====================================================== */

        .card {

            background:
                var(--card-bg);

            border:
                1px solid
                var(--border-color);

            border-radius:
                14px;

            box-shadow:
                var(--shadow-sm);

        }


        .card-header {

            color:
                var(--text-primary) !important;

            font-weight:
                600;

        }


        .card-header.bg-primary {

            background:
                var(--gradient)
                !important;

            color:
                #fff !important;

        }


        .card-header.bg-primary * {

            color:
                #fff !important;

        }


        .card-header:not(.bg-primary) {

            background:
                #fff;

        }


        .card-header:not(.bg-primary) * {

            color:
                var(--text-primary) !important;

        }


        /* =====================================================
           TÍTULOS
        ====================================================== */

        h1,
        h2,
        h3,
        h4,
        h5,
        h6 {

            color:
                var(--text-primary);

            font-weight:
                700;

        }


        .card-body h1,
        .card-body h2,
        .card-body h3,
        .card-body h4,
        .card-body h5,
        .card-body h6 {

            color:
                var(--text-primary)
                !important;

        }


        /* =====================================================
           TABLAS
        ====================================================== */

        .table {

            background:
                #fff;

            margin-bottom:
                0;

        }


        .table thead th {

            background:
                var(--gradient);

            color:
                #fff !important;

            font-weight:
                700;

            white-space:
                nowrap;

            vertical-align:
                middle;

        }


        .table tbody td {

            vertical-align:
                middle;

        }


        /* =====================================================
           FORMULARIOS
        ====================================================== */

        .form-label {

            color:
                var(--text-primary);

            font-weight:
                600;

        }


        .form-control,
        .form-select {

            min-height:
                45px;

            border:
                1px solid
                var(--border-color);

            border-radius:
                9px;

        }


        .form-control:focus,
        .form-select:focus {

            border-color:
                var(--primary-color);

            box-shadow:
                0 0 0
                .2rem
                rgba(37,99,235,.12);

        }


        /* =====================================================
           BOTONES
        ====================================================== */

        .btn {

            border-radius:
                9px;

            font-weight:
                600;

        }


        .btn-primary {

            background:
                var(--gradient);

            border-color:
                var(--primary-color);

        }


        .btn-primary:hover {

            background:
                linear-gradient(
                    135deg,
                    #1d4ed8,
                    #1e40af
                );

        }


        /* =====================================================
           CONTENEDOR PRINCIPAL
        ====================================================== */

        .main-container {

            width:
                100%;

            max-width:
                100%;

        }


        /* =====================================================
           RESPONSIVE TABLES
        ====================================================== */

        .table-responsive {

            width:
                100%;

            overflow-x:
                auto;

            -webkit-overflow-scrolling:
                touch;

        }


        /* =====================================================
           MÓVILES
        ====================================================== */

        @media (max-width: 768px) {


            .navbar {

                min-height:
                    60px;

            }


            .navbar-brand {

                font-size:
                    1rem;

            }


            .sede-info {

                padding:
                    7px 9px;

                font-size:
                    .78rem;

            }


            .sede-info span {

                display:
                    none;

            }


            .user-btn {

                padding:
                    5px;

            }


            .user-info {

                display:
                    none;

            }


            .user-btn::after {

                margin-left:
                    3px;

            }


            .offcanvas {

                width:
                    min(88vw, 320px)
                    !important;

            }


            .container,
            .container-fluid {

                padding-left:
                    10px;

                padding-right:
                    10px;

            }


            .card {

                border-radius:
                    10px;

            }


            h1 {

                font-size:
                    1.5rem;

            }


            h2 {

                font-size:
                    1.35rem;

            }


            h3 {

                font-size:
                    1.2rem;

            }


            .btn {

                min-height:
                    42px;

            }


            .table {

                font-size:
                    .85rem;

            }

        }


        /* =====================================================
           TELÉFONOS PEQUEÑOS
        ====================================================== */

        @media (max-width: 480px) {


            .navbar-brand {

                font-size:
                    .9rem;

            }


            .sede-info {

                display:
                    none;

            }


            .menu-button {

                width:
                    40px;

                height:
                    40px;

            }


            .offcanvas {

                width:
                    90vw !important;

            }


            .sidebar-section-title {

                margin-left:
                    8px;

                margin-right:
                    8px;

            }


            .sidebar-link {

                margin-left:
                    4px;

                margin-right:
                    4px;

            }

        }

    </style>

</head>


<body>


<!-- =========================================================
     NAVBAR
========================================================= -->

<nav
    class="navbar navbar-expand-lg sticky-top"
>

    <div
        class="container-fluid px-2 px-md-3"
    >


        <!-- BOTÓN MENÚ -->

        <button
            class="btn menu-button me-2"
            type="button"
            data-bs-toggle="offcanvas"
            data-bs-target="#offcanvasSidebar"
            aria-controls="offcanvasSidebar"
            aria-label="Abrir menú"
        >

            <i
                class="fas fa-bars"
            ></i>

        </button>


        <!-- LOGO / NOMBRE -->

        <a
            class="navbar-brand"
            href="/biblioteca/dashboard.php"
        >

            <i
                class="fas fa-book-open me-2"
            ></i>

            Biblioteca UPH

        </a>


        <!-- INFORMACIÓN DERECHA -->

        <div
            class="d-flex align-items-center ms-auto gap-2"
        >


            <!-- SEDE -->

            <div
                class="sede-info"
            >

                <i
                    class="fas fa-map-marker-alt"
                ></i>

                <span>Sede:</span>

                <strong>
                    Danlí
                </strong>

            </div>


            <!-- USUARIO -->

            <div
                class="dropdown user-dropdown"
            >

                <button
                    class="btn user-btn dropdown-toggle"
                    type="button"
                    id="dropdownUser"
                    data-bs-toggle="dropdown"
                    aria-expanded="false"
                >

                    <div
                        class="user-avatar"
                    >

                        <i
                            class="fas fa-user"
                        ></i>

                    </div>


                    <div
                        class="user-info"
                    >

                        <span
                            class="user-name"
                        >

                            <?= htmlspecialchars(
                                $nombreUsuario,
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>

                        </span>


                        <small
                            class="user-role"
                        >

                            <?= htmlspecialchars(
                                $rolTexto,
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>

                        </small>

                    </div>

                </button>


                <ul
                    class="dropdown-menu dropdown-menu-end user-dropdown-menu"
                    aria-labelledby="dropdownUser"
                >

                    <li>

                        <div
                            class="dropdown-header"
                        >

                            <strong>

                                <?= htmlspecialchars(
                                    $nombreUsuario,
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>

                            </strong>

                            <br>

                            <small
                                class="text-muted"
                            >

                                <?= htmlspecialchars(
                                    $rolTexto,
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>

                            </small>

                        </div>

                    </li>


                    <li>
                        <hr
                            class="dropdown-divider"
                        >
                    </li>


                    <li>

                        <a
                            class="dropdown-item"
                            href="/biblioteca/dashboard.php"
                        >

                            <i
                                class="fas fa-home me-2"
                            ></i>

                            Dashboard

                        </a>

                    </li>


                    <?php if ($esAdmin): ?>

                        <li>

                            <a
                                class="dropdown-item"
                                href="/biblioteca/usuarios/list.php"
                            >

                                <i
                                    class="fas fa-users-cog me-2"
                                ></i>

                                Gestión de Usuarios

                            </a>

                        </li>

                    <?php endif; ?>


                    <?php if (
                        $esAlumno ||
                        $esDocente
                    ): ?>

                        <li>

                            <a
                                class="dropdown-item"
                                href="/biblioteca/books/list.php"
                            >

                                <i
                                    class="fas fa-book me-2"
                                ></i>

                                Buscar Libros

                            </a>

                        </li>

                        <li>

                            <a
                                class="dropdown-item"
                                href="/biblioteca/usuario/mis_prestamos.php"
                            >

                                <i
                                    class="fas fa-clipboard-list me-2"
                                ></i>

                                Mis Solicitudes

                            </a>

                        </li>

                    <?php endif; ?>


                    <li>
                        <hr
                            class="dropdown-divider"
                        >
                    </li>


                    <li>

                        <a
                            class="dropdown-item text-danger"
                            href="/biblioteca/logout.php"
                        >

                            <i
                                class="fas fa-sign-out-alt me-2"
                            ></i>

                            Cerrar Sesión

                        </a>

                    </li>

                </ul>

            </div>

        </div>

    </div>

</nav>


<!-- =========================================================
     SIDEBAR
========================================================= -->

<div
    class="offcanvas offcanvas-start"
    tabindex="-1"
    id="offcanvasSidebar"
    aria-labelledby="offcanvasSidebarLabel"
>


    <!-- HEADER -->

    <div
        class="offcanvas-header"
    >

        <h5
            class="offcanvas-title"
            id="offcanvasSidebarLabel"
        >

            <i
                class="fas fa-book-open me-2"
            ></i>

            Biblioteca UPH

        </h5>


        <button
            type="button"
            class="btn-close"
            data-bs-dismiss="offcanvas"
            aria-label="Cerrar"
        ></button>

    </div>


    <!-- BODY -->

    <div
        class="offcanvas-body"
    >


        <!-- =================================================
             GENERAL
        ================================================== -->

        <div
            class="sidebar-section-title"
        >

            General

        </div>


        <a
            href="/biblioteca/dashboard.php"
            class="sidebar-link"
        >

            <i
                class="fas fa-home"
            ></i>

            Dashboard

        </a>


        <!-- =================================================
             ADMINISTRADOR
        ================================================== -->

        <?php if ($esAdmin): ?>


            <div
                class="sidebar-section-title"
            >

                Administración

            </div>


            <a
                href="/biblioteca/books/list.php"
                class="sidebar-link"
            >

                <i
                    class="fas fa-book
                    me-2"
                ></i>

                Lista de Libros

            </a>


            <a
                href="/biblioteca/tesis/list.php"
                class="sidebar-link"
            >

                <i
                    class="fas fa-graduation-cap"
                ></i>

                Lista de Tesis

            </a>


            <a
                href="/biblioteca/alumnos/add.php"
                class="sidebar-link"
            >

                <i
                    class="fas fa-user-plus"
                ></i>

                Registrar Alumno

            </a>


            <a
                href="/biblioteca/carreras/list.php"
                class="sidebar-link"
            >

                <i
                    class="fas fa-university"
                ></i>

                Gestión de Carreras

            </a>


            <a
                href="/biblioteca/instituciones/list.php"
                class="sidebar-link"
            >

                <i
                    class="fas fa-building"
                ></i>

                Instituciones Externas

            </a>


            <a
                href="/biblioteca/usuarios/list.php"
                class="sidebar-link"
            >

                <i
                    class="fas fa-users-cog"
                ></i>

                Gestión de Usuarios

            </a>


            <!-- =================================================
                 PRÉSTAMOS
            ================================================== -->

            <div
                class="sidebar-section-title"
            >

                Préstamos y Reservas

            </div>


            <a
                href="/biblioteca/admin/solicitudes_prestamo.php"
                class="sidebar-link"
            >

                <i
                    class="fas fa-bell"
                ></i>

                Solicitudes de Préstamo

                <?php if ($solicitudesPendientes > 0): ?>
                    <span class="badge bg-danger solicitudes-badge">
                        <?= $solicitudesPendientes ?>
                    </span>
                <?php endif; ?>

            </a>


            <a
                href="/biblioteca/reservas/list.php"
                class="sidebar-link"
            >

                <i
                    class="fas fa-bookmark"
                ></i>

                Gestión de Reservas

            </a>


            <a
                href="/biblioteca/alertas.php"
                class="sidebar-link"
            >

                <i
                    class="fas fa-exclamation-triangle"
                ></i>

                Alertas de Préstamos

            </a>


            <!-- =================================================
                 REPORTES
            ================================================== -->

            <div
                class="sidebar-section-title"
            >

                Reportes

            </div>


            <a
                href="/biblioteca/reports/index.php"
                class="sidebar-link"
            >

                <i
                    class="fas fa-chart-line"
                ></i>

                Reportes

            </a>


            <a
                href="/biblioteca/reports/prestamos_devueltos.php"
                class="sidebar-link"
            >

                <i
                    class="fas fa-check-circle"
                ></i>

                Préstamos Devueltos

            </a>


            <a
                href="/biblioteca/reports/carreras_visitas.php"
                class="sidebar-link"
            >

                <i
                    class="fas fa-chart-bar"
                ></i>

                Visitas por Carrera

            </a>


            <a
                href="/biblioteca/reports/reporte_libros.php"
                class="sidebar-link"
            >

                <i
                    class="fas fa-file-excel"
                ></i>

                Reporte de Libros

            </a>


            <!-- =================================================
                 VISITAS
            ================================================== -->

            <div
                class="sidebar-section-title"
            >

                Visitas

            </div>


            <a
                href="/biblioteca/registrar_visita.php"
                class="sidebar-link"
            >

                <i
                    class="fas fa-user-check"
                ></i>

                Registrar Visita

            </a>


        <?php endif; ?>


        <!-- =================================================
             ALUMNO / DOCENTE
        ================================================== -->

        <?php if (
            $esAlumno ||
            $esDocente
        ): ?>


            <div
                class="sidebar-section-title"
            >

                Biblioteca

            </div>


            <a
                href="/biblioteca/books/list.php"
                class="sidebar-link"
            >

                <i
                    class="fas fa-search"
                ></i>

                Buscar Libros

            </a>


            <a
                href="/biblioteca/tesis/list.php"
                class="sidebar-link"
            >

                <i
                    class="fas fa-graduation-cap"
                ></i>

                Buscar Tesis

            </a>


            <a
                href="/biblioteca/usuario/mis_prestamos.php"
                class="sidebar-link"
            >

                <i
                    class="fas fa-clipboard-list"
                ></i>

                Mis Solicitudes

            </a>


            <?php if ($esDocente): ?>

                <div
                    class="sidebar-section-title"
                >

                    Docente

                </div>


                <a
                    href="/biblioteca/books/list.php"
                    class="sidebar-link"
                >

                    <i
                        class="fas fa-book-reader"
                    ></i>

                    Solicitar Préstamo

                </a>

            <?php endif; ?>


            <?php if ($esAlumno): ?>

                <div
                    class="sidebar-section-title"
                >

                    Alumno

                </div>


                <a
                    href="/biblioteca/books/list.php"
                    class="sidebar-link"
                >

                    <i
                        class="fas fa-book-reader"
                    ></i>

                    Solicitar Préstamo

                </a>

            <?php endif; ?>


        <?php endif; ?>


    </div>

</div>


<!-- =========================================================
     CONTENEDOR PRINCIPAL
========================================================= -->

<div
    class="container-fluid main-container mt-4"
>