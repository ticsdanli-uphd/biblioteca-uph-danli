<?php
include '../includes/session.php';
include '../config/db.php';
include '../includes/header.php';

// Parámetros de filtrado
$sede_seleccionada = isset($_SESSION['sede_seleccionada']) ? $_SESSION['sede_seleccionada'] : '';
$tipo = isset($_GET['tipo']) ? $_GET['tipo'] : '';
$fecha_inicio = isset($_GET['fecha_inicio']) ? $_GET['fecha_inicio'] : '';
$fecha_fin = isset($_GET['fecha_fin']) ? $_GET['fecha_fin'] : '';

$where = [];
// Excluir las visitas globales (donde bibliografia_id es NULL)
$where[] = "r.bibliografia_id IS NOT NULL";

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

$sql = "SELECT r.*, 
               IFNULL(b.nombre, 'N/A') AS libro_nombre, 
               IFNULL(b.codigo, 'N/A') AS codigo, 
               u.username, 
               s.nombre AS sede_nombre,
               r.nombre_alumno AS alumno,
               c.nombre AS carrera_nombre,
               i.nombre AS institucion_nombre,
               r.es_externo
        FROM registro_visitas r
        LEFT JOIN bibliografia b ON r.bibliografia_id = b.id
        LEFT JOIN usuarios u ON r.user_id = u.id
        LEFT JOIN sedes s ON b.sede_id = s.id
        LEFT JOIN carreras c ON r.carrera_id = c.id
        LEFT JOIN instituciones_externas i ON r.institucion_id = i.id
        $where_sql
        ORDER BY r.fecha DESC";
$result = $conn->query($sql);
?>
<h2>Reportes de Visitas y Préstamos</h2>

<form class="row g-3 mb-3" method="get" action="reports.php">
  <div class="col-auto">
    <label class="form-label" for="tipo">Tipo:</label>
    <select name="tipo" id="tipo" class="form-select">
      <option value="">Todos</option>
      <option value="visita" <?php if($tipo=='visita') echo 'selected'; ?>>Visitas</option>
      <option value="prestamo" <?php if($tipo=='prestamo') echo 'selected'; ?>>Préstamos</option>
    </select>
  </div>
  <div class="col-auto">
    <label class="form-label" for="fecha_inicio">Desde:</label>
    <input type="date" name="fecha_inicio" id="fecha_inicio" class="form-control" value="<?php echo $fecha_inicio; ?>">
  </div>
  <div class="col-auto">
    <label class="form-label" for="fecha_fin">Hasta:</label>
    <input type="date" name="fecha_fin" id="fecha_fin" class="form-control" value="<?php echo $fecha_fin; ?>">
  </div>
  <div class="col-auto align-self-end">
    <button type="submit" class="btn btn-primary">Filtrar</button>
  </div>
</form>

<a href="reports_download.php?tipo=<?php echo $tipo; ?>&fecha_inicio=<?php echo $fecha_inicio; ?>&fecha_fin=<?php echo $fecha_fin; ?>" 
   class="btn btn-success mb-3"><i class="fas fa-file-csv"></i> Descargar CSV</a>

<table class="table table-striped">
  <thead>
    <tr>
      <th>Fecha</th>
      <th>Tipo</th>
      <th>Libro</th>
      <th>Código</th>
      <th>Visitante</th>
      <th>Carrera</th>
      <th>Institución</th>
      <th>Usuario</th>
      <th>Sede</th>
      <th>Observaciones</th>
    </tr>
  </thead>
  <tbody>
    <?php while($row = $result->fetch_assoc()): ?>
    <tr>
      <td><?php echo $row['fecha']; ?></td>
      <td><?php echo $row['tipo']; ?></td>
      <td><?php echo $row['libro_nombre']; ?></td>
      <td><?php echo $row['codigo']; ?></td>
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
      <td><?php echo $row['sede_nombre']; ?></td>
      <td><?php echo $row['observaciones']; ?></td>
    </tr>
    <?php endwhile; ?>
  </tbody>
</table>
<?php include '../includes/footer.php'; ?>
