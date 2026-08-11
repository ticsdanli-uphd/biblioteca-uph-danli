<?php
include '../includes/session.php';
include '../config/db.php';
include '../includes/header.php';

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

// Consulta para obtener datos para el gráfico
$sqlChart = "SELECT 
                c.nombre AS carrera, 
                COUNT(*) AS total_visitas
            FROM registro_visitas r
            JOIN carreras c ON r.carrera_id = c.id
            LEFT JOIN bibliografia b ON r.bibliografia_id = b.id
            LEFT JOIN usuarios u ON r.user_id = u.id
            $where_sql
            GROUP BY c.id
            ORDER BY total_visitas DESC
            LIMIT 10";
$resultChart = $conn->query($sqlChart);

// Preparar datos para el gráfico
$chartLabels = [];
$chartValues = [];
while ($row = $resultChart->fetch_assoc()) {
    $chartLabels[] = $row['carrera'];
    $chartValues[] = $row['total_visitas'];
}
?>
<h2>Reporte de Visitas por Carrera</h2>

<form class="row g-3 mb-3" method="get" action="carreras_visitas.php">
  <div class="col-auto">
    <label class="form-label" for="tipo_visita">Tipo:</label>
    <select name="tipo_visita" id="tipo_visita" class="form-select">
      <option value="">Todos</option>
      <option value="visita" <?php if($tipo_visita=='visita') echo 'selected'; ?>>Visitas</option>
      <option value="prestamo" <?php if($tipo_visita=='prestamo') echo 'selected'; ?>>Préstamos</option>
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

<a href="carreras_visitas_download.php?tipo_visita=<?php echo $tipo_visita; ?>&fecha_inicio=<?php echo $fecha_inicio; ?>&fecha_fin=<?php echo $fecha_fin; ?>" 
   class="btn btn-success mb-3"><i class="fas fa-file-csv"></i> Descargar CSV</a>

<div class="row">
  <!-- Gráfico de visitas por carrera -->
  <div class="col-md-6 mb-4">
    <div class="card">
      <div class="card-header">
        <h5 class="card-title mb-0">Top 10 Carreras con más Visitas</h5>
      </div>
      <div class="card-body">
        <canvas id="carrerasChart"></canvas>
      </div>
    </div>
  </div>
  
  <!-- Tabla de datos -->
  <div class="col-md-6 mb-4">
    <div class="card">
      <div class="card-header">
        <h5 class="card-title mb-0">Estadísticas Detalladas</h5>
      </div>
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-striped" id="tabla-carreras">
            <thead class="table-dark">
              <tr>
                <th>Carrera</th>
                <th>Total Visitas</th>
                <th>Visitas</th>
                <th>Préstamos</th>
                <th>Externos</th>
              </tr>
            </thead>
            <tbody>
              <?php while($row = $result->fetch_assoc()): ?>
              <tr>
                <td><?php echo $row['carrera']; ?></td>
                <td><?php echo $row['total_visitas']; ?></td>
                <td><?php echo $row['visitas']; ?></td>
                <td><?php echo $row['prestamos']; ?></td>
                <td><?php echo $row['visitantes_externos']; ?></td>
              </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Incluir DataTables para mejor visualización -->
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>

<!-- Incluir Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
$(document).ready(function() {
    $('#tabla-carreras').DataTable({
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.10.25/i18n/Spanish.json"
        },
        "order": [[ 1, "desc" ]],
        "pageLength": 10
    });
    
    // Configuración del gráfico
    var ctx = document.getElementById('carrerasChart').getContext('2d');
    var carrerasChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($chartLabels); ?>,
            datasets: [{
                label: 'Número de Visitas',
                data: <?php echo json_encode($chartValues); ?>,
                backgroundColor: 'rgba(37, 99, 235, 0.6)',
                borderColor: 'rgba(37, 99, 235, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { precision: 0 }
                }
            }
        }
    });
});
</script>

<?php include '../includes/footer.php'; ?>