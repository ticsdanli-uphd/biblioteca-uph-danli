<?php
include '../includes/session.php';
include '../config/db.php';

$sede_seleccionada = isset($_SESSION['sede_seleccionada']) ? $_SESSION['sede_seleccionada'] : '';

$where = "";
if(!empty($sede_seleccionada)) {
    $where = "WHERE b.sede_id = $sede_seleccionada";
}

$sql = "SELECT b.*, s.nombre AS sede_nombre FROM bibliografia b LEFT JOIN sedes s ON b.sede_id = s.id $where ORDER BY b.nombre ASC";
$result = $conn->query($sql);

$sql_total = "SELECT SUM(cantidad) as total FROM bibliografia b $where";
$result_total = $conn->query($sql_total);
$total = 0;
if($row_total = $result_total->fetch_assoc()){
    $total = $row_total['total'];
}

header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=reporte_libros.xls");
?>
<html>
<head>
<meta charset="utf-8">
<style>
    table, th, td { border: 1px solid black; border-collapse: collapse; }
    th, td { padding: 5px; }
</style>
</head>
<body>
<h2>Reporte de Libros</h2>
<p>Total de libros ingresados (suma de cantidades): <?php echo $total; ?></p>
<table>
  <thead>
    <tr>
      <th>Código</th>
      <th>Nombre</th>
      <th>Autores</th>
      <th>Editorial</th>
      <th>Edición</th>
      <th>Año</th>
      <th>ISBN</th>
      <th>Catalogación</th>
      <th>Observaciones</th>
      <th>Cantidad</th>
      <th>Sede</th>
    </tr>
  </thead>
  <tbody>
<?php while($row = $result->fetch_assoc()): ?>
    <tr>
      <td><?php echo $row['codigo']; ?></td>
      <td><?php echo $row['nombre']; ?></td>
      <td><?php echo $row['autores']; ?></td>
      <td><?php echo $row['editorial']; ?></td>
      <td><?php echo $row['edicion']; ?></td>
      <td><?php echo $row['anio']; ?></td>
      <td><?php echo $row['isbn']; ?></td>
      <td><?php echo $row['catalogacion']; ?></td>
      <td><?php echo $row['observaciones']; ?></td>
      <td><?php echo $row['cantidad']; ?></td>
      <td><?php echo $row['sede_nombre']; ?></td>
    </tr>
<?php endwhile; ?>
  </tbody>
</table>
</body>
</html>