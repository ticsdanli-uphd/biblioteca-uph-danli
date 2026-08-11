<?php
include '../includes/session.php';
include '../config/db.php';

// Parámetros de filtrado
$sede_seleccionada = isset($_SESSION['sede_seleccionada']) ? $_SESSION['sede_seleccionada'] : '';
$fecha_inicio = isset($_GET['fecha_inicio']) ? $_GET['fecha_inicio'] : '';
$fecha_fin = isset($_GET['fecha_fin']) ? $_GET['fecha_fin'] : '';

$where = [];
// Solo préstamos devueltos
$where[] = "r.tipo = 'prestamo'";
$where[] = "r.devuelto = 1";

if (!empty($sede_seleccionada)) {
    $where[] = "b.sede_id = $sede_seleccionada";
}
if (!empty($fecha_inicio) && !empty($fecha_fin)) {
    $where[] = "(r.fecha BETWEEN '$fecha_inicio 00:00:00' AND '$fecha_fin 23:59:59')";
}
$where_sql = (count($where) > 0) ? "WHERE " . implode(" AND ", $where) : "";

$sql = "SELECT r.fecha, 
               IFNULL(b.nombre, 'N/A') AS libro_nombre, 
               IFNULL(b.codigo, 'N/A') AS codigo, 
               r.fecha_devolucion_esperada,
               CASE 
                 WHEN r.fecha_devolucion_esperada < CURDATE() THEN 'Tardío'
                 ELSE 'A tiempo'
               END AS estado_devolucion,
               r.nombre_alumno AS alumno,
               c.nombre AS carrera_nombre,
               i.nombre AS institucion_nombre,
               r.es_externo,
               u.username, 
               s.nombre AS sede_nombre,
               r.observaciones
        FROM registro_visitas r
        LEFT JOIN bibliografia b ON r.bibliografia_id = b.id
        LEFT JOIN usuarios u ON r.user_id = u.id
        LEFT JOIN sedes s ON b.sede_id = s.id
        LEFT JOIN carreras c ON r.carrera_id = c.id
        LEFT JOIN instituciones_externas i ON r.institucion_id = i.id
        $where_sql
        ORDER BY r.fecha DESC";
$result = $conn->query($sql);

// Nombre del archivo
$filename = "prestamos_devueltos_" . date('Y-m-d') . ".csv";

// Cabeceras para descargar el archivo
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=' . $filename);

// Crear un archivo de salida
$output = fopen('php://output', 'w');

// Agregar BOM para UTF-8
fputs($output, "\xEF\xBB\xBF");

// Encabezados del CSV
fputcsv($output, array(
    'Fecha Préstamo', 
    'Libro', 
    'Código', 
    'Fecha Devolución Esperada', 
    'Estado', 
    'Alumno', 
    'Externo', 
    'Carrera', 
    'Institución', 
    'Usuario', 
    'Sede', 
    'Observaciones'
));

while ($row = $result->fetch_assoc()) {
    fputcsv($output, array(
        $row['fecha'],
        $row['libro_nombre'],
        $row['codigo'],
        $row['fecha_devolucion_esperada'],
        $row['estado_devolucion'],
        $row['alumno'],
        $row['es_externo'] ? 'Sí' : 'No',
        $row['carrera_nombre'] ? $row['carrera_nombre'] : 'No especificada',
        $row['institucion_nombre'] ? $row['institucion_nombre'] : 'No especificada',
        $row['username'],
        $row['sede_nombre'],
        $row['observaciones']
    ));
}

fclose($output);
exit();