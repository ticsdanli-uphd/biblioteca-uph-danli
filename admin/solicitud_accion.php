<?php
// ============================================================
// admin/solicitud_accion.php
// Aceptar, rechazar y entregar solicitudes.
// SOLO ADMINISTRADOR
// Compatible con la estructura actual del sistema.
// ============================================================

require_once '../includes/session.php';
require_once '../config/db.php';
require_once '../config/app.php';


// ============================================================
// SESIÓN Y PERMISOS
// ============================================================

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

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
// DATOS RECIBIDOS
// ============================================================

$id = (int)($_POST['id'] ?? 0);
$accion = strtolower(trim($_POST['accion'] ?? ''));

if ($id <= 0) {
    $_SESSION['error_msg'] = 'Solicitud inválida.';
    header('Location: solicitudes_prestamo.php');
    exit();
}


// ============================================================
// FUNCIONES
// ============================================================

function ejecutar_stmt(mysqli $conn, string $sql, string $types = '', array $params = []): mysqli_stmt
{
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        throw new Exception(
            'Error preparando consulta: ' . $conn->error
        );
    }

    if ($types !== '') {
        $stmt->bind_param($types, ...$params);
    }

    if (!$stmt->execute()) {
        $error = $stmt->error;
        $stmt->close();

        throw new Exception($error);
    }

    return $stmt;
}


// ============================================================
// OBTENER SOLICITUD
// ============================================================

$sql = "
    SELECT
        s.*,
        b.cantidad,
        b.ubicacion,
        b.sede_id AS bibliografia_sede_id,
        b.nombre AS libro_nombre
    FROM solicitudes_prestamo s
    INNER JOIN bibliografia b
        ON b.id = s.bibliografia_id
    WHERE s.id = ?
    LIMIT 1
";

$stmt = ejecutar_stmt(
    $conn,
    $sql,
    'i',
    [$id]
);

$sol = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$sol) {
    $_SESSION['error_msg'] = 'Solicitud no encontrada.';
    header('Location: solicitudes_prestamo.php');
    exit();
}


// ============================================================
// SEDE
// ============================================================

$sede_id = (int)($sol['sede_id'] ?? $sol['bibliografia_sede_id'] ?? 0);


// ============================================================
// PROCESAR
// ============================================================

try {

    $conn->begin_transaction();

    $usuario_admin = (int)$_SESSION['user_id'];


    // ========================================================
    // ACEPTAR
    // ========================================================

    if ($accion === 'aprobar') {

        if ($sol['estado'] !== 'pendiente') {
            throw new Exception(
                'La solicitud ya fue procesada.'
            );
        }


        // Verificar disponibilidad.
        $stmt = ejecutar_stmt(
            $conn,
            "
            SELECT COUNT(*) AS total
            FROM registro_visitas
            WHERE bibliografia_id = ?
              AND tipo = 'prestamo'
              AND devuelto = 0
            ",
            'i',
            [(int)$sol['bibliografia_id']]
        );

        $fila = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $activos = (int)($fila['total'] ?? 0);
        $cantidad = (int)($sol['cantidad'] ?? 1);

        if ($activos >= $cantidad) {
            throw new Exception(
                'El libro ya no tiene ejemplares disponibles.'
            );
        }


        // Aprobar solicitud.
        $stmt = ejecutar_stmt(
            $conn,
            "
            UPDATE solicitudes_prestamo
            SET
                estado = 'aprobada',
                fecha_respuesta = NOW(),
                atendido_por = ?
            WHERE id = ?
            ",
            'ii',
            [
                $usuario_admin,
                $id
            ]
        );

        $stmt->close();

        $_SESSION['success_msg'] =
            'Solicitud aprobada correctamente. '
            . 'El usuario puede consultar la ubicación del libro.';
    }


    // ========================================================
    // RECHAZAR
    // ========================================================

    elseif ($accion === 'rechazar') {

        if ($sol['estado'] !== 'pendiente') {
            throw new Exception(
                'La solicitud ya fue procesada.'
            );
        }


        $motivo = trim(
            $_POST['motivo'] ?? ''
        );

        if ($motivo === '') {
            $motivo =
                'Solicitud no aprobada por la Biblioteca UPH.';
        }


        $stmt = ejecutar_stmt(
            $conn,
            "
            UPDATE solicitudes_prestamo
            SET
                estado = 'rechazada',
                motivo_rechazo = ?,
                fecha_respuesta = NOW(),
                atendido_por = ?
            WHERE id = ?
            ",
            'sii',
            [
                $motivo,
                $usuario_admin,
                $id
            ]
        );

        $stmt->close();

        $_SESSION['success_msg'] =
            'Solicitud rechazada correctamente.';
    }


    // ========================================================
    // ENTREGAR / MARCAR PRESTADO
    // ========================================================

    elseif ($accion === 'entregar') {

        if ($sol['estado'] !== 'aprobada') {
            throw new Exception(
                'La solicitud debe estar aprobada antes de entregar el libro.'
            );
        }


        // ----------------------------------------------------
        // Verificar disponibilidad nuevamente.
        // ----------------------------------------------------

        $stmt = ejecutar_stmt(
            $conn,
            "
            SELECT COUNT(*) AS total
            FROM registro_visitas
            WHERE bibliografia_id = ?
              AND tipo = 'prestamo'
              AND devuelto = 0
            ",
            'i',
            [(int)$sol['bibliografia_id']]
        );

        $fila = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $activos = (int)($fila['total'] ?? 0);
        $cantidad = (int)($sol['cantidad'] ?? 1);

        if ($activos >= $cantidad) {
            throw new Exception(
                'No hay ejemplares disponibles para entregar.'
            );
        }


        // ----------------------------------------------------
        // Fecha de devolución: 3 días.
        // ----------------------------------------------------

        $fecha_devolucion =
            date(
                'Y-m-d',
                strtotime('+3 days')
            );


        // ----------------------------------------------------
        // DATOS DE SOLICITUD
        // ----------------------------------------------------

        $bibliografia_id =
            (int)$sol['bibliografia_id'];

        $user_id =
            (int)$sol['user_id'];

        $nombre_solicitante =
            trim(
                (string)($sol['nombre_solicitante'] ?? '')
            );

        if ($nombre_solicitante === '') {
            $nombre_solicitante =
                'Usuario Biblioteca';
        }


        $observaciones =
            trim(
                (string)($sol['observaciones'] ?? '')
            );

        if ($observaciones === '') {
            $observaciones =
                'Solicitud de préstamo aprobada y entregada.';
        }


        /*
         * IMPORTANTE:
         * No se consulta alumnos.usuario_id.
         *
         * La solicitud ya contiene:
         * solicitudes_prestamo.user_id
         * solicitudes_prestamo.carrera_id
         *
         * Por lo tanto usamos directamente esos valores.
         */

        $carrera_id = null;

        if (
            isset($sol['carrera_id'])
            && $sol['carrera_id'] !== ''
            && (int)$sol['carrera_id'] > 0
        ) {
            $carrera_id =
                (int)$sol['carrera_id'];
        }


        // ----------------------------------------------------
        // REGISTRAR PRÉSTAMO
        // ----------------------------------------------------

        /*
         * user_id corresponde a registro_visitas.user_id.
         * No usamos usuario_id en registro_visitas.
         */

        $sqlInsert = "
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
        ";

        $stmt = $conn->prepare($sqlInsert);

        if (!$stmt) {
            throw new Exception(
                'No se pudo preparar el registro del préstamo: '
                . $conn->error
            );
        }


        /*
         * bind_param no acepta directamente NULL con tipo i
         * en todas las configuraciones de PHP/MySQL.
         * Se utiliza una variable nullable.
         */

        $stmt->bind_param(
            'iissis',
            $bibliografia_id,
            $user_id,
            $observaciones,
            $nombre_solicitante,
            $carrera_id,
            $fecha_devolucion
        );


        if (!$stmt->execute()) {

            $error =
                $stmt->error;

            $stmt->close();

            throw new Exception(
                'No se pudo registrar el préstamo: '
                . $error
            );
        }


        $registro_id =
            (int)$stmt->insert_id;

        $stmt->close();


        // ----------------------------------------------------
        // ACTUALIZAR SOLICITUD
        // ----------------------------------------------------

        $stmt = ejecutar_stmt(
            $conn,
            "
            UPDATE solicitudes_prestamo
            SET
                estado = 'prestado',
                fecha_entrega = NOW(),
                fecha_respuesta =
                    COALESCE(
                        fecha_respuesta,
                        NOW()
                    ),
                atendido_por = ?,
                registro_visita_id = ?
            WHERE id = ?
            ",
            'iii',
            [
                $usuario_admin,
                $registro_id,
                $id
            ]
        );

        $stmt->close();


        // ----------------------------------------------------
        // ACTUALIZAR ESTADO DEL LIBRO
        // ----------------------------------------------------

        /*
         * Solo colocamos Prestado cuando el ejemplar fue
         * realmente entregado.
         */

        // Actualizar el estado general del libro según los ejemplares activos.
        $stmt = ejecutar_stmt(
            $conn,
            "
            SELECT cantidad
            FROM bibliografia
            WHERE id = ?
            LIMIT 1
            ",
            'i',
            [$bibliografia_id]
        );
        $datosLibro = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $cantidadTotal = (int)($datosLibro['cantidad'] ?? 1);

        $stmt = ejecutar_stmt(
            $conn,
            "
            SELECT COUNT(*) AS total
            FROM registro_visitas
            WHERE bibliografia_id = ?
              AND tipo = 'prestamo'
              AND devuelto = 0
            ",
            'i',
            [$bibliografia_id]
        );
        $datosActivos = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $activosDespues = (int)($datosActivos['total'] ?? 0);
        $estadoLibro = ($activosDespues >= $cantidadTotal)
            ? 'Prestado'
            : 'Disponible';

        $stmt = ejecutar_stmt(
            $conn,
            "
            UPDATE bibliografia
            SET estado = ?
            WHERE id = ?
            ",
            'si',
            [$estadoLibro, $bibliografia_id]
        );
        $stmt->close();


        $_SESSION['success_msg'] =
            'Préstamo entregado y registrado correctamente.';
    }


    // ========================================================
    // ACCIÓN NO VÁLIDA
    // ========================================================

    else {

        throw new Exception(
            'Acción no válida.'
        );
    }


    // ========================================================
    // CONFIRMAR
    // ========================================================

    $conn->commit();

} catch (Throwable $e) {

    if ($conn->errno === 0) {
        // La transacción puede seguir abierta aunque errno sea 0;
        // rollback es seguro en este contexto.
    }

    try {
        $conn->rollback();
    } catch (Throwable $rollbackError) {
        // No hacer nada.
    }

    $_SESSION['error_msg'] =
        $e->getMessage();
}


// ============================================================
// VOLVER A SOLICITUDES
// ============================================================

header(
    'Location: solicitudes_prestamo.php'
);

exit();
?>