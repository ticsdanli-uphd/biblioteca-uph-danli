<?php
include '../includes/session.php';
include '../config/db.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: list.php');
    exit();
}

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    $_SESSION['error_msg'] = 'ID de libro no válido.';
    header('Location: list.php');
    exit();
}

$stmt = $conn->prepare("SELECT id, nombre, foto FROM bibliografia WHERE id=? AND sede_id=4");
$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error_msg'] = 'El libro no existe o no pertenece a Danlí.';
    header('Location: list.php');
    exit();
}

$book = $result->fetch_assoc();
$stmt->close();

$conn->begin_transaction();

try {
    // No se elimina el historial: se desvinculan visitas y reservas.
    $stmt = $conn->prepare("UPDATE registro_visitas SET bibliografia_id=NULL WHERE bibliografia_id=?");
    $stmt->bind_param('i', $id);
    if (!$stmt->execute()) {
        throw new Exception($stmt->error);
    }
    $stmt->close();

    $stmt = $conn->prepare("DELETE FROM reservas_libros WHERE bibliografia_id=?");
    $stmt->bind_param('i', $id);
    if (!$stmt->execute()) {
        throw new Exception($stmt->error);
    }
    $stmt->close();

    $stmt = $conn->prepare("DELETE FROM bibliografia WHERE id=? AND sede_id=4");
    $stmt->bind_param('i', $id);
    if (!$stmt->execute()) {
        throw new Exception($stmt->error);
    }
    $stmt->close();

    if (!empty($book['foto'])) {
        $file = __DIR__ . '/../uploads/' . basename($book['foto']);
        if (is_file($file)) {
            @unlink($file);
        }
    }

    $conn->commit();

    $_SESSION['success_msg'] = 'El libro "' . $book['nombre'] . '" fue eliminado correctamente.';
} catch (Throwable $e) {
    $conn->rollback();
    $_SESSION['error_msg'] = 'No se pudo eliminar el libro: ' . $e->getMessage();
}

header('Location: list.php');
exit();
?>
