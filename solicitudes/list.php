<?php

include '../includes/session.php';
include '../config/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/*
|--------------------------------------------------------------------------
| SOLO ADMIN
|--------------------------------------------------------------------------
*/

if (
    !isset($_SESSION['user_id']) ||
    ($_SESSION['role'] ?? '') !== 'admin'
) {

    header('Location: ../login.php');
    exit();
}

$admin_id = (int) $_SESSION['user_id'];

$mensaje = '';
$error = '';

/*
|--------------------------------------------------------------------------
| PROCESAR ACCIONES
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $solicitud_id =
        (int) ($_POST['solicitud_id'] ?? 0);

    $accion =
        $_POST['accion'] ?? '';

    $respuesta =
        trim($_POST['respuesta'] ?? '');

    if ($solicitud_id <= 0) {

        $error = "Solicitud inválida.";

    } elseif (
        !in_array(
            $accion,
            ['aprobar', 'rechazar'],
            true
        )
    ) {

        $error = "Acción inválida.";

    } else {

        /*
        |--------------------------------------------------------------------------
        | OBTENER SOLICITUD
        |--------------------------------------------------------------------------
        */

        $stmt = $conn->prepare("
            SELECT
                s.*,
                b.nombre AS libro_nombre,
                b.codigo,
                b.cantidad,
                b.estado AS libro_estado,
                u.nombre AS usuario_nombre,
                u.username,
                u.role

            FROM solicitudes_prestamo s

            INNER JOIN bibliografia b
                ON b.id = s.bibliografia_id

            INNER JOIN usuarios u
                ON u.id = s.user_id

            WHERE s.id = ?
              AND s.sede_id = 4

            LIMIT 1
        ");

        $stmt->bind_param(
            "i",
            $solicitud_id
        );

        $stmt->execute();

        $solicitud =
            $stmt->get_result()->fetch_assoc();

        $stmt->close();

        if (!$solicitud) {

            $error = "La solicitud no existe.";

        } elseif (
            $solicitud['estado'] !== 'pendiente'
        ) {

            $error =
                "Esta solicitud ya fue atendida.";

        } else {

            /*
            |--------------------------------------------------------------------------
            | RECHAZAR
            |--------------------------------------------------------------------------
            */

            if ($accion === 'rechazar') {

                if ($respuesta === '') {

                    $respuesta =
                        "Solicitud rechazada por la biblioteca.";

                }

                $stmt = $conn->prepare("
                    UPDATE solicitudes_prestamo

                    SET
                        estado = 'rechazada',
                        respuesta_admin = ?,
                        fecha_respuesta = NOW(),
                        atendido_por = ?

                    WHERE id = ?
                      AND estado = 'pendiente'
                ");

                $stmt->bind_param(
                    "sii",
                    $respuesta,
                    $admin_id,
                    $solicitud_id
                );

                if ($stmt->execute()) {

                    $mensaje =
                        "Solicitud rechazada correctamente.";

                } else {

                    $error =
                        "No se pudo rechazar la solicitud.";

                }

                $stmt->close();

            }

            /*
            |--------------------------------------------------------------------------
            | APROBAR
            |--------------------------------------------------------------------------
            */

            if ($accion === 'aprobar') {

                /*
                | Usar transacción para evitar
                | prestar el mismo ejemplar dos veces.
                */

                $conn->begin_transaction();

                try {

                    /*
                    | Bloquear libro
                    */

                    $stmt = $conn->prepare("
                        SELECT
                            id,
                            cantidad,
                            estado

                        FROM bibliografia

                        WHERE id = ?
                          AND sede_id = 4

                        FOR UPDATE
                    ");

                    $stmt->bind_param(
                        "i",
                        $solicitud['bibliografia_id']
                    );

                    $stmt->execute();

                    $libro =
                        $stmt->get_result()->fetch_assoc();

                    $stmt->close();

                    if (!$libro) {

                        throw new Exception(
                            "El libro no existe."
                        );
                    }

                    /*
                    | Contar préstamos activos
                    */

                    $stmt = $conn->prepare("
                        SELECT COUNT(*) AS total

                        FROM registro_visitas

                        WHERE bibliografia_id = ?
                          AND tipo = 'prestamo'
                          AND devuelto = 0
                    ");

                    $stmt->bind_param(
                        "i",
                        $solicitud['bibliografia_id']
                    );

                    $stmt->execute();

                    $datos =
                        $stmt->get_result()->fetch_assoc();

                    $stmt->close();

                    $activos =
                        (int) ($datos['total'] ?? 0);

                    $cantidad =
                        (int) $libro['cantidad'];

                    if ($activos >= $cantidad) {

                        throw new Exception(
                            "El libro ya no tiene ejemplares disponibles."
                        );
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | CREAR PRÉSTAMO
                    |--------------------------------------------------------------------------
                    */

                    $observacionPrestamo =
                        "Préstamo generado desde solicitud #"
                        . $solicitud_id;

                    if ($respuesta !== '') {

                        $observacionPrestamo .=
                            " | " . $respuesta;
                    }

                    /*
                    | Si es alumno obtenemos carrera.
                    */

                    $carrera_id = null;

                    $stmt = $conn->prepare("
                        SELECT carrera_id

                        FROM alumnos

                        WHERE usuario_id = ?

                        LIMIT 1
                    ");

                    $stmt->bind_param(
                        "i",
                        $solicitud['user_id']
                    );

                    $stmt->execute();

                    $alumno =
                        $stmt->get_result()->fetch_assoc();

                    $stmt->close();

                    if ($alumno) {

                        $carrera_id =
                            !empty($alumno['carrera_id'])
                            ? (int) $alumno['carrera_id']
                            : null;
                    }

                    /*
                    | Insertar préstamo real
                    */

                    $stmt = $conn->prepare("
                        INSERT INTO registro_visitas
                        (
                            bibliografia_id,
                            user_id,
                            tipo,
                            observaciones,
                            nombre_alumno,
                            carrera_id,
                            es_externo,
                            devuelto
                        )

                        VALUES
                        (
                            ?,
                            ?,
                            'prestamo',
                            ?,
                            ?,
                            ?,
                            0,
                            0
                        )
                    ");

                    $nombreUsuario =
                        $solicitud['usuario_nombre']
                        ?: $solicitud['username'];

                    $stmt->bind_param(
                        "iissi",
                        $solicitud['bibliografia_id'],
                        $solicitud['user_id'],
                        $observacionPrestamo,
                        $nombreUsuario,
                        $carrera_id
                    );

                    if (!$stmt->execute()) {

                        throw new Exception(
                            "No se pudo registrar el préstamo."
                        );
                    }

                    $stmt->close();

                    /*
                    |--------------------------------------------------------------------------
                    | ACTUALIZAR SOLICITUD
                    |--------------------------------------------------------------------------
                    */

                    $respuestaFinal =
                        $respuesta !== ''
                        ? $respuesta
                        : "Solicitud aprobada y préstamo registrado.";

                    $stmt = $conn->prepare("
                        UPDATE solicitudes_prestamo

                        SET
                            estado = 'completada',
                            respuesta_admin = ?,
                            fecha_respuesta = NOW(),
                            atendido_por = ?

                        WHERE id = ?
                          AND estado = 'pendiente'
                    ");

                    $stmt->bind_param(
                        "sii",
                        $respuestaFinal,
                        $admin_id,
                        $solicitud_id
                    );

                    if (!$stmt->execute()) {

                        throw new Exception(
                            "No se pudo actualizar la solicitud."
                        );
                    }

                    $stmt->close();

                    /*
                    |--------------------------------------------------------------------------
                    | CONFIRMAR
                    |--------------------------------------------------------------------------
                    */

                    $conn->commit();

                    $mensaje =
                        "Solicitud aprobada y préstamo registrado correctamente.";

                } catch (Throwable $e) {

                    $conn->rollback();

                    $error =
                        $e->getMessage();
                }
            }
        }
    }
}

/*
|--------------------------------------------------------------------------
| OBTENER SOLICITUDES
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT

        s.id,
        s.estado,
        s.observaciones,
        s.respuesta_admin,
        s.fecha_solicitud,
        s.fecha_respuesta,

        b.nombre AS libro,
        b.codigo,

        u.nombre AS usuario,
        u.username,
        u.role,

        a.nombre AS alumno_nombre,

        c.nombre AS carrera

    FROM solicitudes_prestamo s

    INNER JOIN bibliografia b
        ON b.id = s.bibliografia_id

    INNER JOIN usuarios u
        ON u.id = s.user_id

    LEFT JOIN alumnos a
        ON a.usuario_id = u.id

    LEFT JOIN carreras c
        ON c.id = a.carrera_id

    WHERE s.sede_id = 4

    ORDER BY

        CASE s.estado
            WHEN 'pendiente' THEN 1
            WHEN 'aprobada' THEN 2
            WHEN 'rechazada' THEN 3
            WHEN 'completada' THEN 4
            ELSE 5
        END,

        s.fecha_solicitud DESC
";

$result =
    $conn->query($sql);

include '../includes/header.php';

?>

<div class="container-fluid py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>

            <h1 class="h3 mb-1">

                <i class="fas fa-bell text-primary me-2"></i>

                Solicitudes de Préstamo

            </h1>

            <p class="text-muted mb-0">

                Solicitudes realizadas por alumnos y docentes de Danlí.

            </p>

        </div>

        <span class="badge bg-primary fs-6">

            <i class="fas fa-map-marker-alt me-1"></i>

            Danlí

        </span>

    </div>

    <?php if ($mensaje): ?>

        <div class="alert alert-success alert-dismissible fade show">

            <i class="fas fa-check-circle me-2"></i>

            <?= htmlspecialchars($mensaje) ?>

            <button
                type="button"
                class="btn-close"
                data-bs-dismiss="alert"
            ></button>

        </div>

    <?php endif; ?>

    <?php if ($error): ?>

        <div class="alert alert-danger alert-dismissible fade show">

            <i class="fas fa-exclamation-circle me-2"></i>

            <?= htmlspecialchars($error) ?>

            <button
                type="button"
                class="btn-close"
                data-bs-dismiss="alert"
            ></button>

        </div>

    <?php endif; ?>

    <div class="card border-0 shadow-sm">

        <div class="card-body">

            <?php if ($result->num_rows === 0): ?>

                <div class="text-center py-5">

                    <i
                        class="fas fa-inbox fa-3x text-muted mb-3"
                    ></i>

                    <h5>

                        No hay solicitudes

                    </h5>

                    <p class="text-muted">

                        Actualmente no existen solicitudes de préstamo.

                    </p>

                </div>

            <?php else: ?>

                <div class="table-responsive">

                    <table
                        class="table table-hover align-middle"
                        id="tablaSolicitudes"
                    >

                        <thead class="table-primary">

                            <tr>

                                <th>Fecha</th>
                                <th>Solicitante</th>
                                <th>Tipo</th>
                                <th>Libro</th>
                                <th>Código</th>
                                <th>Estado</th>
                                <th>Acciones</th>

                            </tr>

                        </thead>

                        <tbody>

                        <?php while ($row = $result->fetch_assoc()): ?>

                            <tr>

                                <td>

                                    <?= date(
                                        'd/m/Y H:i',
                                        strtotime(
                                            $row['fecha_solicitud']
                                        )
                                    ) ?>

                                </td>

                                <td>

                                    <strong>

                                        <?= htmlspecialchars(
                                            $row['usuario']
                                            ?: $row['username']
                                        ) ?>

                                    </strong>

                                    <br>

                                    <small class="text-muted">

                                        <?= htmlspecialchars(
                                            $row['username']
                                        ) ?>

                                    </small>

                                </td>

                                <td>

                                    <?php if ($row['role'] === 'docente'): ?>

                                        <span class="badge bg-info">

                                            <i class="fas fa-chalkboard-teacher me-1"></i>

                                            Docente

                                        </span>

                                    <?php else: ?>

                                        <span class="badge bg-secondary">

                                            <i class="fas fa-user-graduate me-1"></i>

                                            Alumno

                                        </span>

                                    <?php endif; ?>

                                </td>

                                <td>

                                    <?= htmlspecialchars(
                                        $row['libro']
                                    ) ?>

                                </td>

                                <td>

                                    <code>

                                        <?= htmlspecialchars(
                                            $row['codigo']
                                        ) ?>

                                    </code>

                                </td>

                                <td>

                                    <?php if ($row['estado'] === 'pendiente'): ?>

                                        <span class="badge bg-warning text-dark">

                                            Pendiente

                                        </span>

                                    <?php elseif ($row['estado'] === 'completada'): ?>

                                        <span class="badge bg-success">

                                            Préstamo realizado

                                        </span>

                                    <?php elseif ($row['estado'] === 'rechazada'): ?>

                                        <span class="badge bg-danger">

                                            Rechazada

                                        </span>

                                    <?php else: ?>

                                        <span class="badge bg-secondary">

                                            <?= htmlspecialchars(
                                                ucfirst(
                                                    $row['estado']
                                                )
                                            ) ?>

                                        </span>

                                    <?php endif; ?>

                                </td>

                                <td>

                                    <?php if ($row['estado'] === 'pendiente'): ?>

                                        <button
                                            class="btn btn-sm btn-primary"
                                            data-bs-toggle="modal"
                                            data-bs-target="#modalSolicitud<?= $row['id'] ?>"
                                        >

                                            <i class="fas fa-eye me-1"></i>

                                            Revisar

                                        </button>

                                    <?php else: ?>

                                        <button
                                            class="btn btn-sm btn-outline-secondary"
                                            data-bs-toggle="modal"
                                            data-bs-target="#modalDetalle<?= $row['id'] ?>"
                                        >

                                            <i class="fas fa-eye me-1"></i>

                                            Ver

                                        </button>

                                    <?php endif; ?>

                                </td>

                            </tr>

                            <?php if ($row['estado'] === 'pendiente'): ?>

                                <div
                                    class="modal fade"
                                    id="modalSolicitud<?= $row['id'] ?>"
                                    tabindex="-1"
                                >

                                    <div class="modal-dialog modal-lg modal-dialog-centered">

                                        <div class="modal-content">

                                            <div class="modal-header bg-primary text-white">

                                                <h5 class="modal-title">

                                                    <i class="fas fa-book-reader me-2"></i>

                                                    Revisar solicitud

                                                </h5>

                                                <button
                                                    type="button"
                                                    class="btn-close btn-close-white"
                                                    data-bs-dismiss="modal"
                                                ></button>

                                            </div>

                                            <div class="modal-body">

                                                <div class="row g-3">

                                                    <div class="col-md-6">

                                                        <label class="fw-bold">

                                                            Solicitante

                                                        </label>

                                                        <div>

                                                            <?= htmlspecialchars(
                                                                $row['usuario']
                                                                ?: $row['username']
                                                            ) ?>

                                                        </div>

                                                    </div>

                                                    <div class="col-md-6">

                                                        <label class="fw-bold">

                                                            Tipo

                                                        </label>

                                                        <div>

                                                            <?= $row['role'] === 'docente'
                                                                ? 'Docente'
                                                                : 'Alumno'
                                                            ?>

                                                        </div>

                                                    </div>

                                                    <div class="col-md-8">

                                                        <label class="fw-bold">

                                                            Libro

                                                        </label>

                                                        <div>

                                                            <?= htmlspecialchars(
                                                                $row['libro']
                                                            ) ?>

                                                        </div>

                                                    </div>

                                                    <div class="col-md-4">

                                                        <label class="fw-bold">

                                                            Código

                                                        </label>

                                                        <div>

                                                            <?= htmlspecialchars(
                                                                $row['codigo']
                                                            ) ?>

                                                        </div>

                                                    </div>

                                                    <?php if ($row['carrera']): ?>

                                                        <div class="col-12">

                                                            <label class="fw-bold">

                                                                Carrera

                                                            </label>

                                                            <div>

                                                                <?= htmlspecialchars(
                                                                    $row['carrera']
                                                                ) ?>

                                                            </div>

                                                        </div>

                                                    <?php endif; ?>

                                                    <div class="col-12">

                                                        <label class="fw-bold">

                                                            Observaciones del usuario

                                                        </label>

                                                        <div class="bg-light p-3 rounded">

                                                            <?= nl2br(
                                                                htmlspecialchars(
                                                                    $row['observaciones']
                                                                    ?: 'Sin observaciones.'
                                                                )
                                                            ) ?>

                                                        </div>

                                                    </div>

                                                </div>

                                                <hr>

                                                <form method="POST">

                                                    <input
                                                        type="hidden"
                                                        name="solicitud_id"
                                                        value="<?= $row['id'] ?>"
                                                    >

                                                    <div class="mb-3">

                                                        <label class="form-label fw-bold">

                                                            Respuesta / Observación del administrador

                                                        </label>

                                                        <textarea
                                                            name="respuesta"
                                                            class="form-control"
                                                            rows="3"
                                                            placeholder="Escriba una respuesta..."
                                                        ></textarea>

                                                    </div>

                                                    <div class="d-flex gap-2">

                                                        <button
                                                            type="submit"
                                                            name="accion"
                                                            value="aprobar"
                                                            class="btn btn-success"
                                                        >

                                                            <i class="fas fa-check me-1"></i>

                                                            Aprobar y registrar préstamo

                                                        </button>

                                                        <button
                                                            type="submit"
                                                            name="accion"
                                                            value="rechazar"
                                                            class="btn btn-danger"
                                                        >

                                                            <i class="fas fa-times me-1"></i>

                                                            Rechazar

                                                        </button>

                                                    </div>

                                                </form>

                                            </div>

                                        </div>

                                    </div>

                                </div>

                            <?php endif; ?>

                        <?php endwhile; ?>

                        </tbody>

                    </table>

                </div>

            <?php endif; ?>

        </div>

    </div>

</div>

<style>

#tablaSolicitudes th {

    white-space: nowrap;

}

@media (max-width: 768px) {

    .container-fluid {

        padding-left: 12px;
        padding-right: 12px;

    }

    #tablaSolicitudes {

        font-size: 14px;

    }

}

</style>

<?php include '../includes/footer.php'; ?>