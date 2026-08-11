<?php
include '../includes/session.php';
include '../config/db.php';

if (!isset($_GET['id'])) {
    die("No se especificó el libro.");
}

$bibliografia_id = intval($_GET['id']);
$user_id = $_SESSION['user_id'];
$fecha_esperada = date("Y-m-d", strtotime("+3 days"));

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $observaciones = isset($_POST['observaciones']) ? $conn->real_escape_string($_POST['observaciones']) : '';
    $nombre_alumno = isset($_POST['nombre_alumno']) ? $conn->real_escape_string($_POST['nombre_alumno']) : '';
    $es_externo = isset($_POST['es_externo']) ? 1 : 0;
    $institucion_id = ($es_externo && isset($_POST['institucion_id'])) ? intval($_POST['institucion_id']) : NULL;
    $carrera_id = isset($_POST['carrera_id']) ? intval($_POST['carrera_id']) : NULL;
    
    $sql = "INSERT INTO registro_visitas (bibliografia_id, user_id, tipo, observaciones, fecha_devolucion_esperada, 
            nombre_alumno, es_externo, institucion_id, carrera_id)
            VALUES ($bibliografia_id, $user_id, 'prestamo', '$observaciones', '$fecha_esperada', 
            '$nombre_alumno', $es_externo, ".(is_null($institucion_id) ? "NULL" : $institucion_id).", ".(is_null($carrera_id) ? "NULL" : $carrera_id).")";
    if ($conn->query($sql)) {
        header("Location: view.php?id=$bibliografia_id");
        exit();
    } else {
        $error = "Error al registrar préstamo: " . $conn->error;
    }
}

include '../includes/header.php';
?>
<h2>Registrar Préstamo</h2>
<p>La fecha de devolución esperada es <strong><?php echo $fecha_esperada; ?></strong> (3 días a partir de hoy).</p>
<?php if(isset($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>
<form method="post" action="prestamo.php?id=<?php echo $bibliografia_id; ?>">
  <div class="mb-3">
    <label class="form-label">Nombre del Alumno</label>
    <input type="text" name="nombre_alumno" class="form-control" required>
  </div>
  
  <div class="mb-3 form-check">
    <input type="checkbox" class="form-check-input" id="es_externo" name="es_externo">
    <label class="form-check-label" for="es_externo">Visitante Externo</label>
  </div>
  
  <div id="institucion_container" class="mb-3" style="display: none;">
    <label class="form-label">Institución</label>
    <select name="institucion_id" class="form-select">
      <option value="">Seleccione una institución</option>
      <?php
      $sql_instituciones = "SELECT id, nombre FROM instituciones_externas ORDER BY nombre";
      $result_instituciones = $conn->query($sql_instituciones);
      while($inst = $result_instituciones->fetch_assoc()) {
        echo "<option value='". $inst['id'] ."'>". $inst['nombre'] ."</option>";
      }
      ?>
    </select>
  </div>
  
  <div class="mb-3">
    <label class="form-label">Carrera</label>
    <select name="carrera_id" class="form-select">
      <option value="">Seleccione una carrera</option>
      <?php
      $sql_carreras = "SELECT id, nombre FROM carreras ORDER BY nombre";
      $result_carreras = $conn->query($sql_carreras);
      while($carrera = $result_carreras->fetch_assoc()) {
        echo "<option value='". $carrera['id'] ."'>". $carrera['nombre'] ."</option>";
      }
      ?>
    </select>
  </div>
  
  <div class="mb-3">
    <label class="form-label">Observaciones (opcional)</label>
    <textarea name="observaciones" class="form-control" rows="3"></textarea>
  </div>
  
  <button type="submit" class="btn btn-primary">Registrar Préstamo</button>
  <a href="view.php?id=<?php echo $bibliografia_id; ?>" class="btn btn-secondary">Cancelar</a>
</form>

<script>
  document.getElementById('es_externo').addEventListener('change', function() {
    document.getElementById('institucion_container').style.display = this.checked ? 'block' : 'none';
  });
</script>
<?php include '../includes/footer.php'; ?>