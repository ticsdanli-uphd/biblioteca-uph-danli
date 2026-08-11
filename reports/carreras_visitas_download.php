<?php
include '../includes/session.php';
include '../config/db.php';

// Parámetros de filtrado
$sede_seleccionada = isset($_SESSION['sede_seleccionada']) ? $_SESSION['sede_seleccionada'] : '';
$fecha_inicio = isset($_GET['fecha_inicio']) ? $_GET['fecha_inicio'] : '';
$fecha_fin = isset($_GET['fecha_fin']) ? $_GET['fecha_fin'] : '';
$tipo_visita = isset($_GET['tipo_visita']) ? $_GET['tipo_visita'] : '';

$where = [];
// Asegurarse de que hay una carrera asociada
$where[] = "r.carrera_id IS NOT NULL";

if (!empty($sede_seleccionada)) {
    $where[] = "(b.sede_id = $sede_seleccionada OR u.sede_id = $sede_seleccionada)";
}
if (!empty($fecha_inicio) && !empty($fecha_fin)) {
    $where[] = "(r.fecha BETWEEN '$fecha_inicio 00:00:00' AND '$fecha_fin 23:59:59')";
}
if (!empty($tipo_visita)) {
    $where[] = "r.tipo = '$tipo_visita'";
}
$where_sql = (count($where) > 0) ? "WHERE " . implode(" AND ", $where) : "";

// Consulta para obtener el conteo de visitas por carrera
$sql = "SELECT 
            c.nombre AS carrera, 
            COUNT(*) AS total_visitas,
            SUM(CASE WHEN r.tipo = 'visita' THEN 1 ELSE 0 END) AS visitas,
            SUM(CASE WHEN r.tipo = 'prestamo' THEN 1 ELSE 0 END) AS prestamos,
            SUM(CASE WHEN r.es_externo = 1 THEN 1 ELSE 0 END) AS visitantes_externos
        FROM registro_visitas r
        JOIN carreras c ON r.carrera_id = c.id
        LEFT JOIN bibliografia b ON r.bibliografia_id = b.id
        LEFT JOIN usuarios u ON r.user_id = u.id
        $where_sql
        GROUP BY c.id
        ORDER BY total_visitas DESC";
$result = $conn->query($sql);

// Nombre del archivo
$filename = "carreras_visitas_" . date('Y-m-d') . ".csv";

// Cabeceras para descargar el archivo
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=' . $filename);

// Crear un archivo de salida
$output = fopen('php://output', 'w');

// Agregar BOM para UTF-8
fputs($output, "\xEF\xBB\xBF");

// Encabezados del CSV
fputcsv($output, array(
    'Carrera', 
    'Total Visitas', 
    'Visitas', 
    'Préstamos', 
    'Visitantes Externos'
));

while ($row = $result->fetch_assoc()) {
    fputcsv($output, array(
        $row['carrera'],
        $row['total_visitas'],
        $row['visitas'],
        $row['prestamos'],
        $row['visitantes_externos']
    ));
}

fclose($output);
exit();