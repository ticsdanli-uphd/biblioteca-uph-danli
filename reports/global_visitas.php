<?php
include '../includes/session.php';
include '../config/db.php';
include '../includes/header.php';

// Consulta para obtener solo las visitas globales (donde bibliografia_id es NULL)
$sql = "SELECT r.fecha, r.observaciones, r.nombre_alumno AS alumno, u.username,
        c.nombre AS carrera_nombre, i.nombre AS institucion_nombre, r.es_externo
        FROM registro_visitas r
        LEFT JOIN usuarios u ON r.user_id = u.id
        LEFT JOIN carreras c ON r.carrera_id = c.id
        LEFT JOIN instituciones_externas i ON r.institucion_id = i.id
        WHERE r.bibliografia_id IS NULL
        ORDER BY r.fecha DESC";
$result = $conn->query($sql);
?>

<h2>Reporte de Visitas Globales</h2>
<p>En este reporte se muestran las visitas globales registradas en la biblioteca.</p>

<table class="table table-striped">
  <thead>
    <tr>
      <th>Fecha</th>
      <th>Visitante</th>
      <th>Carrera</th>
      <th>Institución</th>
      <th>Usuario</th>
      <th>Observaciones</th>
    </tr>
  </thead>
  <tbody>
    <?php while($row = $result->fetch_assoc()): ?>
    <tr>
      <td><?php echo $row['fecha']; ?></td>
      <td>
        <?php 
        echo $row['alumno']; 
        if ($row['es_externo']) {
          echo ' <span class="badge bg-info">Externo</span>';
        }
        ?>
      </td>
      <td><?php echo $row['carrera_nombre'] ? $row['carrera_nombre'] : 'No especificada'; ?></td>
      <td><?php echo $row['institucion_nombre'] ? $row['institucion_nombre'] : 'No especificada'; ?></td>
      <td><?php echo $row['username']; ?></td>
      <td><?php echo $row['observaciones']; ?></td>
    </tr>
    <?php endwhile; ?>
  </tbody>
</table>

<?php include '../includes/footer.php'; ?>