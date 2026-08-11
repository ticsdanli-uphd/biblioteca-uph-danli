<?php
include '../config/db.php';

$term = isset($_GET['term']) ? $conn->real_escape_string($_GET['term']) : '';

$data = [];
if (!empty($term)) {
    $sql = "SELECT id, nombre, telefono, email FROM alumnos WHERE nombre LIKE '%$term%' ORDER BY nombre LIMIT 10";
    $result = $conn->query($sql);
    while ($row = $result->fetch_assoc()) {
        // Cada sugerencia tendrá 'label' para mostrar y 'value' para el ID
        $data[] = [
            'label' => $row['nombre'],
            'value' => $row['id'],
            'telefono' => $row['telefono'],
            'email' => $row['email']
        ];
    }
}

echo json_encode($data);
?>
