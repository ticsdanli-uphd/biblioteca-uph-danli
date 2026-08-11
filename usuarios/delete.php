<?php

include '../includes/session.php';
include '../config/db.php';


// ======================================================
// VERIFICAR QUE EL USUARIO SEA ADMINISTRADOR
// ======================================================

if (
    !isset($_SESSION['role']) ||
    $_SESSION['role'] !== 'admin'
) {
    header('Location: /biblioteca/dashboard.php');
    exit();
}


// ======================================================
// OBTENER ID DEL USUARIO
// ======================================================

$id = isset($_GET['id'])
    ? intval($_GET['id'])
    : 0;


// ======================================================
// VALIDAR ID
// ======================================================

if ($id <= 0) {

    $_SESSION['error'] =
        "ID de usuario no válido.";

    header('Location: list.php');
    exit();
}


// ======================================================
// EVITAR ELIMINAR AL USUARIO ACTUAL
// ======================================================

if (
    isset($_SESSION['user_id']) &&
    $id == intval($_SESSION['user_id'])
) {

    $_SESSION['error'] =
        "No puedes eliminar tu propio usuario.";

    header('Location: list.php');
    exit();
}


// ======================================================
// OBTENER INFORMACIÓN DEL USUARIO
// SOLO DANLÍ
// ======================================================

$stmt = $conn->prepare("
    SELECT
        u.*,
        a.id AS alumno_id
    FROM usuarios u
    LEFT JOIN alumnos a
        ON u.id = a.usuario_id
    WHERE u.id = ?
      AND u.sede_id = 6
    LIMIT 1
");

$stmt->bind_param("i", $id);
$stmt->execute();

$result = $stmt->get_result();


// ======================================================
// VERIFICAR SI EXISTE
// ======================================================

if ($result->num_rows === 0) {

    $_SESSION['error'] =
        "Usuario no encontrado en la sede Danlí.";

    $stmt->close();

    header('Location: list.php');
    exit();
}


$usuario = $result->fetch_assoc();

$stmt->close();


// ======================================================
// INICIAR TRANSACCIÓN
// ======================================================

$conn->begin_transaction();


try {


    // ==================================================
    // ELIMINAR REGISTROS DE registro_visitas
    // ==================================================

    $stmt = $conn->prepare("
        DELETE FROM registro_visitas
        WHERE user_id = ?
    ");

    $stmt->bind_param("i", $id);

    if (!$stmt->execute()) {

        throw new Exception(
            "Error al eliminar registros de visitas: "
            . $stmt->error
        );
    }

    $stmt->close();


    // ==================================================
    // ELIMINAR REGISTROS DE visitas_biblioteca
    // ==================================================

    $stmt = $conn->prepare("
        DELETE FROM visitas_biblioteca
        WHERE user_id = ?
    ");

    $stmt->bind_param("i", $id);

    if (!$stmt->execute()) {

        throw new Exception(
            "Error al eliminar registros de visitas a biblioteca: "
            . $stmt->error
        );
    }

    $stmt->close();


    // ==================================================
    // ACTUALIZAR bibliografia - ingresado_por
    // ==================================================

    $stmt = $conn->prepare("
        UPDATE bibliografia
        SET ingresado_por = NULL
        WHERE ingresado_por = ?
    ");

    $stmt->bind_param("i", $id);

    if (!$stmt->execute()) {

        throw new Exception(
            "Error al actualizar referencias de bibliografía: "
            . $stmt->error
        );
    }

    $stmt->close();


    // ==================================================
    // ACTUALIZAR bibliografia - modificado_por
    // ==================================================

    $stmt = $conn->prepare("
        UPDATE bibliografia
        SET modificado_por = NULL
        WHERE modificado_por = ?
    ");

    $stmt->bind_param("i", $id);

    if (!$stmt->execute()) {

        throw new Exception(
            "Error al actualizar referencias de modificación: "
            . $stmt->error
        );
    }

    $stmt->close();


    // ==================================================
    // ELIMINAR REGISTRO DE ALUMNO
    // SI EXISTE
    // ==================================================

    if (
        isset($usuario['alumno_id']) &&
        !empty($usuario['alumno_id'])
    ) {

        $alumno_id =
            intval($usuario['alumno_id']);


        $stmt = $conn->prepare("
            DELETE FROM alumnos
            WHERE id = ?
        ");

        $stmt->bind_param(
            "i",
            $alumno_id
        );


        if (!$stmt->execute()) {

            throw new Exception(
                "Error al eliminar el registro del alumno: "
                . $stmt->error
            );
        }

        $stmt->close();
    }


    // ==================================================
    // ELIMINAR USUARIO
    // ==================================================

    $stmt = $conn->prepare("
        DELETE FROM usuarios
        WHERE id = ?
          AND sede_id = 6
    ");

    $stmt->bind_param(
        "i",
        $id
    );


    if (!$stmt->execute()) {

        throw new Exception(
            "Error al eliminar el usuario: "
            . $stmt->error
        );
    }


    // Verificar que realmente se eliminó

    if ($stmt->affected_rows === 0) {

        throw new Exception(
            "No se pudo eliminar el usuario."
        );
    }


    $stmt->close();


    // ==================================================
    // CONFIRMAR TRANSACCIÓN
    // ==================================================

    $conn->commit();


    $_SESSION['success'] =
        "Usuario eliminado correctamente.";


} catch (Exception $e) {


    // ==================================================
    // REVERTIR CAMBIOS
    // ==================================================

    $conn->rollback();


    $_SESSION['error'] =
        $e->getMessage();
}


// ======================================================
// REGRESAR AL LISTADO
// ======================================================

header('Location: list.php');

exit();

?>