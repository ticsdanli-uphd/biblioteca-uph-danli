<?php
include '../includes/session.php';
include '../config/db.php';

// Verificar que sea administrador
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: /biblioteca/tesis/list.php');
    exit();
}

$id = intval($_GET['id']);

// Eliminar registros dependientes primero (si es necesario)
$sql1 = "DELETE FROM registro_visitas WHERE bibliografia_id = $id";
$conn->query($sql1);

$sql2 = "DELETE FROM bibliografia WHERE id = $id";
if ($conn->query($sql2)) {
  $_SESSION['success_msg'] = "La bibliografía se ha eliminado con éxito.";
  header("Location: list.php");
  exit();
} else {
  echo "Error: " . $conn->error;
}
