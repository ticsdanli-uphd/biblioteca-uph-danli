<?php
include '../includes/session.php';
include '../config/db.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: ../dashboard.php');
    exit();
}

$sql = "SELECT
            b.codigo,b.dewey,b.clasificacion,b.nombre,b.autores,b.editorial,
            b.edicion,b.anio,b.isbn,b.estado,b.ubicacion,b.fecha_ingreso,
            b.idioma,c.nombre AS carrera_nombre,b.cantidad,b.sede_id
        FROM bibliografia b
        LEFT JOIN carreras c ON c.id=b.carrera_id
        WHERE b.sede_id=4
        ORDER BY b.codigo ASC";

$result = $conn->query($sql);

header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
header('Content-Disposition: attachment; filename="libros_Danli.xls"');
header('Pragma: no-cache');
header('Expires: 0');

echo "\xEF\xBB\xBF";
?>
<table border="1">
    <tr>
        <th>Código</th><th>Dewey</th><th>Clasificación</th><th>Nombre</th>
        <th>Autor(es)</th><th>Editorial</th><th>Edición</th><th>Año</th>
        <th>ISBN</th><th>Estado</th><th>Ubicación</th><th>Fecha de ingreso</th>
        <th>Idioma</th><th>Carrera</th><th>Cantidad</th><th>Sede ID</th>
    </tr>
<?php while ($row = $result->fetch_assoc()): ?>
    <tr>
        <?php foreach ([
            $row['codigo'],$row['dewey'],$row['clasificacion'],$row['nombre'],
            $row['autores'],$row['editorial'],$row['edicion'],$row['anio'],
            $row['isbn'],$row['estado'],$row['ubicacion'],$row['fecha_ingreso'],
            $row['idioma'],$row['carrera_nombre'],$row['cantidad'],$row['sede_id']
        ] as $value): ?>
            <td><?= htmlspecialchars((string)$value) ?></td>
        <?php endforeach; ?>
    </tr>
<?php endwhile; ?>
</table>
<?php exit; ?>
