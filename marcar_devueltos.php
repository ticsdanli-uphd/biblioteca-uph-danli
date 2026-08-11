<?php
include 'includes/session.php';
include 'config/db.php';

if (!isset($_GET['id'])) {
    die("ID no especificado");
}
$id = intval($_GET['id']);
$sql = "UPDATE registro_visitas SET devuelto = 1 WHERE id = $id";
if ($conn->query($sql)) {
    header("Location: alertas.php");
    exit();
} else {
    echo "Error: " . $conn->error;
}
?>