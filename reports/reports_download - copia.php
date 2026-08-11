<?php
include '../includes/session.php';
include '../config/db.php';

$tipo = isset($_GET['tipo']) ? $_GET['tipo'] : '';
$fecha_inicio = isset($_GET['fecha_inicio']) ? $_GET['fecha_inicio'] : '';
$fecha_fin = isset($_GET['fecha_fin']) ? $_GET['fecha_fin'] : '';
$sede_seleccionada = isset($_SESSION['sede_seleccionada']) ? $_SESSION['sede_seleccionada'] : '';

$where = [];
if (!empty($sede_seleccionada)) {
    $where[] = "b.sede_id = $sede_seleccionada";
}
if (!empty($tipo)) {
    $where[] = "r.tipo = '$tipo'";
}
if (!empty($fecha_inicio) && !empty($fecha_fin)) {
    $where[] = "(r.fecha BETWEEN '$fecha_inicio 00:00:00' AND '$fecha_fin 23:59:59')";
}
$where_sql = (count($where) > 0) ? "WHERE " . implode(" AND ", $where) : "";

$sql = "SELECT r.fecha, r.tipo, 
               IFNULL(b.nombre, 'N/A') AS libro_nombre, 
               IFNULL(b.codigo, 'N/A') AS codigo, 
               u.username, s.nombre AS sede_nombre, r.observaciones
        FROM registro_visitas r
        LEFT JOIN bibliografia b ON r.bibliografia_id = b.id
        LEFT JOIN usuarios u ON r.user_id = u.id
        LEFT JOIN sedes s ON b.sede_id = s.id
        $where_sql
        ORDER BY r.fecha DESC";
$result = $conn->query($sql);

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=reportes.csv');

$output = fopen('php://output', 'w');
fputcsv($output, array('Fecha', 'Tipo', 'Libro', 'Código', 'Usuario', 'Sede', 'Observaciones'));

while ($row = $result->fetch_assoc()) {
    fputcsv($output, array(
        $row['fecha'],
        $row['tipo'],
        $row['libro_nombre'],
        $row['codigo'],
        $row['username'],
        $row['sede_nombre'],
        $row['observaciones']
    ));
}

fclose($output);
exit();