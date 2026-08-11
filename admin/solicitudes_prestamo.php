<?php
// ============================================================
// admin/solicitudes_prestamo.php
// BANDEJA ADMINISTRATIVA DE SOLICITUDES DE PRÉSTAMO
// Sede: Danlí
// Acceso: SOLO ADMINISTRADOR
// ============================================================

require_once '../includes/session.php';
require_once '../config/db.php';
require_once '../config/app.php';
require_once '../includes/permisos.php';

// ============================================================
// VERIFICAR SESIÓN
// ============================================================

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}


// ============================================================
// VERIFICAR ADMINISTRADOR
// ============================================================

$rol = strtolower(trim(
    $_SESSION['role']
    ?? $_SESSION['rol']
    ?? $_SESSION['tipo_usuario']
    ?? ''
));

if (!in_array($rol, ['admin', 'administrador'], true)) {
    header('Location: ../dashboard.php');
    exit();
}


// ============================================================
// SEDE DANLÍ
// ============================================================

$sede_id = defined('DANLI_SEDE_ID')
    ? (int) DANLI_SEDE_ID
    : 4;


// ============================================================
// VARIABLES
// ============================================================

$error = '';
$success = '';


// ============================================================
// CONSULTAR SOLICITUDES
// ============================================================

$sql = "
    SELECT
        s.id,
        s.user_id,
        s.bibliografia_id,
        
        s.nombre_solicitante,
        s.carrera_id,
        s.sede_id,
        s.estado,
        s.observaciones,
        s.fecha_solicitud,

        b.nombre AS libro_nombre,
        b.codigo AS libro_codigo,
        b.ubicacion AS libro_ubicacion,

        c.nombre AS carrera_nombre,

        u.username AS usuario,
        u.nombre AS usuario_nombre,
        u.role AS usuario_role

    FROM solicitudes_prestamo s

    INNER JOIN bibliografia b
        ON b.id = s.bibliografia_id

    LEFT JOIN carreras c
        ON c.id = s.carrera_id

    INNER JOIN usuarios u
        ON u.id = s.user_id

    WHERE s.sede_id = ?

    ORDER BY
        CASE s.estado
            WHEN 'pendiente' THEN 1
            WHEN 'aprobada' THEN 2
            WHEN 'prestado' THEN 3
            WHEN 'rechazada' THEN 4
            WHEN 'cancelada' THEN 5
            ELSE 6
        END,
        s.fecha_solicitud DESC
";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    $error = 'No se pudo preparar la consulta de solicitudes: ' . $conn->error;
    $result = false;
} else {

    $stmt->bind_param('i', $sede_id);

    if (!$stmt->execute()) {
        $error = 'No se pudieron cargar las solicitudes: ' . $stmt->error;
        $result = false;
    } else {
        $result = $stmt->get_result();
    }
}


// ============================================================
// CONTADORES
// ============================================================

$total_pendientes = 0;
$total_aprobadas = 0;
$total_prestados = 0;
$total_rechazadas = 0;

if ($result) {

    $result->data_seek(0);

    while ($tmp = $result->fetch_assoc()) {

        switch ($tmp['estado']) {

            case 'pendiente':
                $total_pendientes++;
                break;

            case 'aprobada':
                $total_aprobadas++;
                break;

            case 'prestado':
                $total_prestados++;
                break;

            case 'rechazada':
                $total_rechazadas++;
                break;
        }
    }

    $result->data_seek(0);
}


// ============================================================
// HEADER
// ============================================================

include '../includes/header.php';

?>

<style>

/* ============================================================
   CONTENEDOR
============================================================ */

.page-solicitudes {
    max-width: 1350px;
    margin: 30px auto;
}


/* ============================================================
   ENCABEZADO
============================================================ */

.hero-solicitudes {
    background: #ffffff;
    border-radius: 18px;
    padding: 26px 30px;
    box-shadow: 0 8px 30px rgba(0, 0, 0, .07);
    border: 1px solid #e5e7eb;
}

.hero-solicitudes h2 {
    color: #172554;
    font-weight: 700;
}


/* ============================================================
   TARJETAS DE ESTADÍSTICAS
============================================================ */

.stat-card {
    background: #ffffff;
    border-radius: 16px;
    padding: 20px;
    box-shadow: 0 6px 22px rgba(0, 0, 0, .06);
    border: 1px solid #e5e7eb;
    height: 100%;
}

.stat-icon {
    width: 48px;
    height: 48px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
}

.stat-number {
    font-size: 27px;
    font-weight: 700;
    margin: 0;
}

.stat-title {
    color: #64748b;
    font-size: 14px;
}


/* ============================================================
   TARJETA SOLICITUD
============================================================ */

.card-solicitud {
    background: #ffffff;
    border-radius: 18px;
    padding: 22px;
    box-shadow: 0 6px 22px rgba(0, 0, 0, .06);
    border: 1px solid #e5e7eb;
    height: 100%;
    transition: .2s ease;
}

.card-solicitud:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 30px rgba(0, 0, 0, .09);
}


/* ============================================================
   LIBRO
============================================================ */

.libro-nombre {
    color: #172554;
    font-weight: 700;
    font-size: 19px;
}

.codigo-libro {
    color: #64748b;
    font-size: 13px;
}


/* ============================================================
   INFORMACIÓN
============================================================ */

.info-row {
    margin-bottom: 8px;
    color: #334155;
}

.info-row strong {
    color: #172554;
}


/* ============================================================
   UBICACIÓN
============================================================ */

.ubicacion-box {
    background: #eff6ff;
    border: 1px solid #bfdbfe;
    border-left: 4px solid #2563eb;
    border-radius: 10px;
    padding: 12px 14px;
    margin: 15px 0;
}

.ubicacion-box strong {
    color: #1d4ed8;
}


/* ============================================================
   ESTADOS
============================================================ */

.estado {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 7px 11px;
    border-radius: 30px;
    font-size: 12px;
    font-weight: 700;
}

.estado-pendiente {
    background: #fff7d6;
    color: #946c00;
}

.estado-aprobada {
    background: #dbeafe;
    color: #1d4ed8;
}

.estado-prestado {
    background: #d1fae5;
    color: #047857;
}

.estado-rechazada {
    background: #fee2e2;
    color: #b91c1c;
}

.estado-cancelada {
    background: #f1f5f9;
    color: #475569;
}


/* ============================================================
   TIPO DE USUARIO
============================================================ */

.tipo-usuario {
    display: inline-block;
    padding: 4px 9px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 700;
    margin-left: 5px;
}

.tipo-alumno {
    background: #e0f2fe;
    color: #0369a1;
}

.tipo-docente {
    background: #ede9fe;
    color: #6d28d9;
}

.tipo-admin {
    background: #fee2e2;
    color: #b91c1c;
}


/* ============================================================
   OBSERVACIONES
============================================================ */

.observacion {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 12px;
    font-size: 14px;
}


/* ============================================================
   BOTONES
============================================================ */

.btn-accion {
    border-radius: 10px;
    font-weight: 600;
}


/* ============================================================
   VACÍO
============================================================ */

.empty-state {
    background: #ffffff;
    border-radius: 18px;
    padding: 60px 25px;
    text-align: center;
    box-shadow: 0 6px 22px rgba(0, 0, 0, .06);
    border: 1px solid #e5e7eb;
}

.empty-state i {
    font-size: 55px;
    color: #94a3b8;
    margin-bottom: 15px;
}


/* ============================================================
   RESPONSIVE
============================================================ */

@media (max-width: 768px) {

    .page-solicitudes {
        margin: 15px auto;
    }

    .hero-solicitudes {
        padding: 20px;
    }

    .card-solicitud {
        padding: 17px;
    }

}

</style>


<div class="container-fluid">

<div class="page-solicitudes">


<!-- ========================================================
     ENCABEZADO
========================================================= -->

<div class="hero-solicitudes mb-4">

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">

        <div>

            <h2 class="mb-1">

                <i class="fas fa-bell text-primary me-2"></i>

                Solicitudes de Préstamo

            </h2>

            <p class="text-muted mb-0">

                Revise, apruebe y registre la entrega de libros
                solicitados por alumnos y docentes.

            </p>

        </div>


        <span class="badge bg-success fs-6 px-3 py-2">

            <i class="fas fa-map-marker-alt me-1"></i>

            Sede Danlí

        </span>

    </div>

</div>



<!-- ========================================================
     MENSAJES
========================================================= -->

<?php if (!empty($_SESSION['success_msg'])): ?>

<div class="alert alert-success alert-dismissible fade show">

    <i class="fas fa-check-circle me-2"></i>

    <?= htmlspecialchars($_SESSION['success_msg']) ?>

    <button
        type="button"
        class="btn-close"
        data-bs-dismiss="alert">
    </button>

</div>

<?php
unset($_SESSION['success_msg']);
endif;
?>


<?php if (!empty($_SESSION['error_msg'])): ?>

<div class="alert alert-danger alert-dismissible fade show">

    <i class="fas fa-exclamation-circle me-2"></i>

    <?= htmlspecialchars($_SESSION['error_msg']) ?>

    <button
        type="button"
        class="btn-close"
        data-bs-dismiss="alert">
    </button>

</div>

<?php
unset($_SESSION['error_msg']);
endif;
?>


<?php if (!empty($error)): ?>

<div class="alert alert-danger">

    <i class="fas fa-exclamation-triangle me-2"></i>

    <?= htmlspecialchars($error) ?>

</div>

<?php endif; ?>



<!-- ========================================================
     ESTADÍSTICAS
========================================================= -->

<div class="row g-4 mb-4">


    <!-- PENDIENTES -->

    <div class="col-12 col-md-6 col-xl-3">

        <div class="stat-card">

            <div class="d-flex align-items-center gap-3">

                <div
                    class="stat-icon"
                    style="background:#fff3cd;color:#f59e0b;"
                >

                    <i class="fas fa-clock"></i>

                </div>

                <div>

                    <div class="stat-title">
                        Pendientes
                    </div>

                    <p class="stat-number text-warning">
                        <?= $total_pendientes ?>
                    </p>

                </div>

            </div>

        </div>

    </div>


    <!-- APROBADAS -->

    <div class="col-12 col-md-6 col-xl-3">

        <div class="stat-card">

            <div class="d-flex align-items-center gap-3">

                <div
                    class="stat-icon"
                    style="background:#dbeafe;color:#2563eb;"
                >

                    <i class="fas fa-check"></i>

                </div>

                <div>

                    <div class="stat-title">
                        Aprobadas
                    </div>

                    <p class="stat-number text-primary">
                        <?= $total_aprobadas ?>
                    </p>

                </div>

            </div>

        </div>

    </div>


    <!-- PRESTADOS -->

    <div class="col-12 col-md-6 col-xl-3">

        <div class="stat-card">

            <div class="d-flex align-items-center gap-3">

                <div
                    class="stat-icon"
                    style="background:#d1fae5;color:#059669;"
                >

                    <i class="fas fa-book-reader"></i>

                </div>

                <div>

                    <div class="stat-title">
                        Prestados
                    </div>

                    <p class="stat-number text-success">
                        <?= $total_prestados ?>
                    </p>

                </div>

            </div>

        </div>

    </div>


    <!-- RECHAZADAS -->

    <div class="col-12 col-md-6 col-xl-3">

        <div class="stat-card">

            <div class="d-flex align-items-center gap-3">

                <div
                    class="stat-icon"
                    style="background:#fee2e2;color:#dc2626;"
                >

                    <i class="fas fa-times"></i>

                </div>

                <div>

                    <div class="stat-title">
                        Rechazadas
                    </div>

                    <p class="stat-number text-danger">
                        <?= $total_rechazadas ?>
                    </p>

                </div>

            </div>

        </div>

    </div>

</div>



<!-- ========================================================
     SOLICITUDES
========================================================= -->

<?php if ($result && $result->num_rows > 0): ?>


<div class="row g-4">


<?php while ($r = $result->fetch_assoc()): ?>


<?php

// ------------------------------------------------------------
// ESTADO
// ------------------------------------------------------------

$estado = strtolower(
    trim((string)$r['estado'])
);


switch ($estado) {

    case 'pendiente':

        $estado_texto = 'Pendiente';
        $estado_clase = 'estado-pendiente';
        $estado_icono = 'fa-clock';

        break;


    case 'aprobada':

        $estado_texto = 'Aprobada - Por recoger';
        $estado_clase = 'estado-aprobada';
        $estado_icono = 'fa-check';

        break;


    case 'prestado':

        $estado_texto = 'Prestado';
        $estado_clase = 'estado-prestado';
        $estado_icono = 'fa-book-reader';

        break;


    case 'rechazada':

        $estado_texto = 'Rechazada';
        $estado_clase = 'estado-rechazada';
        $estado_icono = 'fa-times';

        break;


    case 'cancelada':

        $estado_texto = 'Cancelada';
        $estado_clase = 'estado-cancelada';
        $estado_icono = 'fa-ban';

        break;


    default:

        $estado_texto = ucfirst($estado);
        $estado_clase = 'estado-cancelada';
        $estado_icono = 'fa-info-circle';

        break;
}


// ------------------------------------------------------------
// TIPO DE USUARIO
// ------------------------------------------------------------

$role_usuario = strtolower(
    trim((string)$r['usuario_role'])
);


if (
    in_array(
        $role_usuario,
        ['alumno','estudiante','student'],
        true
    )
) {

    $tipo_usuario = 'Alumno';
    $tipo_clase = 'tipo-alumno';

} elseif (
    in_array(
        $role_usuario,
        ['docente','profesor','teacher'],
        true
    )
) {

    $tipo_usuario = 'Docente';
    $tipo_clase = 'tipo-docente';

} else {

    $tipo_usuario = ucfirst(
        $role_usuario ?: 'Usuario'
    );

    $tipo_clase = 'tipo-admin';
}


// ------------------------------------------------------------
// NOMBRE
// ------------------------------------------------------------

$nombre_solicitante =
    trim((string)$r['nombre_solicitante']);

if ($nombre_solicitante === '') {

    $nombre_solicitante =
        trim((string)$r['usuario_nombre']);
}


// ------------------------------------------------------------
// CARRERA
// ------------------------------------------------------------

if ($tipo_usuario === 'Docente') {

    $carrera_mostrar = 'No aplica';

} else {

    $carrera_mostrar =
        trim((string)$r['carrera_nombre']);

    if ($carrera_mostrar === '') {

        $carrera_mostrar =
            'No especificada';
    }
}


// ------------------------------------------------------------
// UBICACIÓN
// ------------------------------------------------------------

$ubicacion =
    trim((string)$r['libro_ubicacion']);

if ($ubicacion === '') {

    $ubicacion =
        'Biblioteca UPH Danlí - Ubicación no especificada';
}


// ------------------------------------------------------------
// FECHA
// ------------------------------------------------------------

$fecha_solicitud = '';

if (!empty($r['fecha_solicitud'])) {

    $timestamp =
        strtotime($r['fecha_solicitud']);

    if ($timestamp !== false) {

        $fecha_solicitud =
            date(
                'd/m/Y H:i',
                $timestamp
            );
    }
}

?>


<!-- ========================================================
     TARJETA
========================================================= -->

<div class="col-12 col-lg-6">


<div class="card-solicitud">


<!-- CABECERA -->

<div
    class="d-flex justify-content-between
           align-items-start gap-3 mb-2"
>

    <div>

        <div class="libro-nombre">

            <i class="fas fa-book text-primary me-2"></i>

            <?= htmlspecialchars(
                $r['libro_nombre']
            ) ?>

        </div>

        <div class="codigo-libro mt-1">

            Código:

            <strong>
                <?= htmlspecialchars(
                    $r['libro_codigo']
                ) ?>
            </strong>

        </div>

    </div>


    <span class="estado <?= $estado_clase ?>">

        <i class="fas <?= $estado_icono ?>"></i>

        <?= htmlspecialchars($estado_texto) ?>

    </span>

</div>



<hr>



<!-- SOLICITANTE -->

<div class="info-row">

    <strong>
        <i class="fas fa-user me-1"></i>
        Solicitante:
    </strong>

    <?= htmlspecialchars(
        $nombre_solicitante
    ) ?>


    <span class="tipo-usuario <?= $tipo_clase ?>">

        <?= htmlspecialchars(
            $tipo_usuario
        ) ?>

    </span>

</div>



<!-- USUARIO -->

<div class="info-row">

    <strong>
        <i class="fas fa-at me-1"></i>
        Usuario:
    </strong>

    <?= htmlspecialchars(
        $r['usuario']
    ) ?>

</div>



<!-- CARRERA -->

<div class="info-row">

    <strong>
        <i class="fas fa-graduation-cap me-1"></i>
        Carrera:
    </strong>

    <?= htmlspecialchars(
        $carrera_mostrar
    ) ?>

</div>



<!-- FECHA -->

<div class="info-row">

    <strong>
        <i class="fas fa-calendar-alt me-1"></i>
        Solicitud:
    </strong>

    <?= htmlspecialchars(
        $fecha_solicitud
    ) ?>

</div>



<!-- UBICACIÓN -->

<div class="ubicacion-box">

    <div>

        <i class="fas fa-map-marker-alt text-primary me-1"></i>

        <strong>
            Ubicación para recoger
        </strong>

    </div>

    <div class="mt-1">

        <?= htmlspecialchars($ubicacion) ?>
        <div class="small text-muted mt-1"><i class="fas fa-info-circle me-1"></i>El estante y nivel se registran al crear o editar el libro.</div>

    </div>

</div>



<!-- OBSERVACIONES -->

<?php if (
    trim((string)$r['observaciones']) !== ''
): ?>

<div class="observacion mb-3">

    <strong>

        <i class="fas fa-comment-alt me-1"></i>

        Observación:

    </strong>

    <div class="mt-1">

        <?= nl2br(
            htmlspecialchars(
                $r['observaciones']
            )
        ) ?>

    </div>

</div>

<?php endif; ?>



<!-- ========================================================
     ACCIONES
========================================================= -->

<div class="d-flex flex-wrap gap-2">


<?php if ($estado === 'pendiente'): ?>


<!-- ACEPTAR -->

<form
    method="post"
    action="solicitud_accion.php"
    class="m-0"
>

    <input
        type="hidden"
        name="id"
        value="<?= (int)$r['id'] ?>"
    >

    <input
        type="hidden"
        name="accion"
        value="aprobar"
    >

    <button
        type="submit"
        class="btn btn-primary btn-accion"
        onclick="
            return confirm(
                '¿Desea aceptar esta solicitud de préstamo?'
            );
        "
    >

        <i class="fas fa-check me-1"></i>

        Aceptar préstamo

    </button>

</form>



<!-- RECHAZAR -->

<form
    method="post"
    action="solicitud_accion.php"
    class="m-0"
>

    <input
        type="hidden"
        name="id"
        value="<?= (int)$r['id'] ?>"
    >

    <input
        type="hidden"
        name="accion"
        value="rechazar"
    >

    <button
        type="submit"
        class="btn btn-outline-danger btn-accion"
        onclick="
            return confirm(
                '¿Desea rechazar esta solicitud?'
            );
        "
    >

        <i class="fas fa-times me-1"></i>

        Rechazar

    </button>

</form>


<?php elseif ($estado === 'aprobada'): ?>


<!-- ENTREGAR -->

<form
    method="post"
    action="solicitud_accion.php"
    class="m-0"
>

    <input
        type="hidden"
        name="id"
        value="<?= (int)$r['id'] ?>"
    >

    <input
        type="hidden"
        name="accion"
        value="entregar"
    >

    <button
        type="submit"
        class="btn btn-success btn-accion"
        onclick="
            return confirm(
                '¿Confirma que el libro fue entregado al solicitante?'
            );
        "
    >

        <i class="fas fa-hand-holding me-1"></i>

        Marcar como prestado / entregado

    </button>

</form>


<div class="w-100">

    <small class="text-success">

        <i class="fas fa-map-marker-alt me-1"></i>

        El usuario puede recogerlo en:

        <strong>
            <?= htmlspecialchars($ubicacion) ?>
        </strong>

    </small>

</div>


<?php elseif ($estado === 'prestado'): ?>


<!-- PRESTADO -->

<div class="alert alert-success py-2 mb-0 w-100">

    <i class="fas fa-check-circle me-1"></i>

    <strong>
        Préstamo entregado.
    </strong>

    El libro está oficialmente prestado.

</div>


<?php elseif ($estado === 'rechazada'): ?>


<div class="alert alert-danger py-2 mb-0 w-100">

    <i class="fas fa-times-circle me-1"></i>

    Esta solicitud fue rechazada.

</div>


<?php elseif ($estado === 'cancelada'): ?>


<div class="alert alert-secondary py-2 mb-0 w-100">

    <i class="fas fa-ban me-1"></i>

    Esta solicitud fue cancelada.

</div>


<?php endif; ?>


</div>

</div>

</div>


<?php endwhile; ?>


</div>


<?php else: ?>


<!-- ========================================================
     SIN SOLICITUDES
========================================================= -->

<div class="empty-state">

    <i class="fas fa-inbox"></i>

    <h4 class="fw-bold">
        No hay solicitudes de préstamo
    </h4>

    <p class="text-muted mb-0">

        Actualmente no existen solicitudes
        registradas para la sede Danlí.

    </p>

</div>


<?php endif; ?>


</div>

</div>


<?php

if ($stmt) {
    $stmt->close();
}

include '../includes/footer.php';

?>