<?php
include '../includes/session.php';
include '../config/db.php';

// Verificar que sea administrador
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: /biblioteca/tesis/list.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST'){
    $numero    = $conn->real_escape_string($_POST['numero']);
    $cuenta    = $conn->real_escape_string($_POST['cuenta']);
    $alumno    = $conn->real_escape_string($_POST['alumno']);
    $carrera   = $conn->real_escape_string($_POST['carrera']);  // valor seleccionado del dropdown
    $titulo    = $conn->real_escape_string($_POST['titulo']);
    $anio_egresado = intval($_POST['anio_egresado']);
    $asesor_metodologico = $conn->real_escape_string($_POST['asesor_metodologico']);
    $asesor_tematico     = $conn->real_escape_string($_POST['asesor_tematico']);
    $cantidad  = intval($_POST['cantidad']);
    
    // Para sede: si el usuario es admin, se permite elegir; de lo contrario, se usa la sede de la sesión.
    if ($_SESSION['role'] == 'admin' && !empty($_POST['sede_id'])) {
        $sede_id = intval($_POST['sede_id']);
    } else {
        $sede_id = isset($_SESSION['sede_seleccionada']) ? $_SESSION['sede_seleccionada'] : $_SESSION['sede_id'];
    }
    
    $sql = "INSERT INTO tesis (numero, cuenta, alumno, carrera, titulo, anio_egresado, asesor_metodologico, asesor_tematico, cantidad, sede_id)
            VALUES ('$numero','$cuenta','$alumno','$carrera','$titulo',$anio_egresado,'$asesor_metodologico','$asesor_tematico',$cantidad,$sede_id)";
    
    if($conn->query($sql)){
         $_SESSION['success_msg'] = "La tesis se ha agregado con éxito.";
         header("Location: list.php");
         exit();
    } else {
         $error = "Error: " . $conn->error;
    }
}

// Obtener las carreras desde la tabla 'carreras'
$sqlCarreras = "SELECT * FROM carreras ORDER BY nombre ASC";
$resCarreras = $conn->query($sqlCarreras);

include '../includes/header.php';
?>

<div class="container my-4">
  <div class="card shadow-sm">
    <div class="card-header bg-primary text-white">
      <h2 class="mb-0">Agregar Tesis</h2>
    </div>
    <div class="card-body">
      <?php if(isset($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>
      <form method="post" action="add.php">
        <div class="row g-3">
          <div class="col-md-6">
            <div class="form-floating">
              <input type="text" name="numero" class="form-control" id="numero" placeholder="Número de Tesis" required>
              <label for="numero">Número de Tesis</label>
            </div>
          </div>
          <div class="col-md-6">
            <div class="form-floating">
              <input type="text" name="cuenta" class="form-control" id="cuenta" placeholder="Número de Cuenta" required>
              <label for="cuenta">Número de Cuenta</label>
            </div>
          </div>
        </div>
        <div class="row g-3 my-3">
          <div class="col-md-6">
            <div class="form-floating">
              <input type="text" name="alumno" class="form-control" id="alumno" placeholder="Nombre del Alumno" required>
              <label for="alumno">Nombre del Alumno</label>
            </div>
          </div>
          <div class="col-md-6">
            <div class="form-floating">
              <select name="carrera" class="form-select" id="carrera" required>
                <option value="">-- Seleccione Carrera --</option>
                <?php while($row = $resCarreras->fetch_assoc()): ?>
                  <option value="<?php echo $row['nombre']; ?>"><?php echo $row['nombre']; ?></option>
                <?php endwhile; ?>
              </select>
              <label for="carrera">Carrera</label>
            </div>
          </div>
        </div>
        <div class="mb-3">
          <div class="form-floating">
            <input type="text" name="titulo" class="form-control" id="titulo" placeholder="Título de la Tesis" required>
            <label for="titulo">Título de la Tesis</label>
          </div>
        </div>
        <div class="row g-3 my-3">
          <div class="col-md-4">
            <div class="form-floating">
              <input type="number" name="anio_egresado" class="form-control" id="anio_egresado" placeholder="Año Egresado" required>
              <label for="anio_egresado">Año Egresado</label>
            </div>
          </div>
          <div class="col-md-4">
            <div class="form-floating">
              <input type="text" name="asesor_metodologico" class="form-control" id="asesor_metodologico" placeholder="Asesor Metodológico">
              <label for="asesor_metodologico">Asesor Metodológico</label>
            </div>
          </div>
          <div class="col-md-4">
            <div class="form-floating">
              <input type="text" name="asesor_tematico" class="form-control" id="asesor_tematico" placeholder="Asesor Temático">
              <label for="asesor_tematico">Asesor Temático</label>
            </div>
          </div>
        </div>
        <div class="row g-3 mb-3">
          <div class="col-md-6">
            <div class="form-floating">
              <input type="number" name="cantidad" class="form-control" id="cantidad" placeholder="Cantidad" required>
              <label for="cantidad">Cantidad</label>
            </div>
          </div>
          <?php if ($_SESSION['role'] == 'admin'): ?>
          <div class="col-md-6">
            <div class="form-floating">
              <select name="sede_id" class="form-select" id="sede_id" required>
                <?php
                $sql_all_sedes = "SELECT * FROM sedes ORDER BY nombre ASC";
                $r_sedes = $conn->query($sql_all_sedes);
                while ($sd = $r_sedes->fetch_assoc()) {
                    echo "<option value='{$sd['id']}'>{$sd['nombre']}</option>";
                }
                ?>
              </select>
              <label for="sede_id">Sede</label>
            </div>
          </div>
          <?php endif; ?>
        </div>
        <div class="d-flex justify-content-between">
          <button type="submit" class="btn btn-primary">Agregar Tesis</button>
          <a href="list.php" class="btn btn-secondary">Cancelar</a>
        </div>
      </form>
    </div>
  </div>
</div>

<?php include '../includes/footer.php'; ?>
