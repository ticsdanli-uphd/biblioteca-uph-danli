<?php
include '../includes/session.php';
include '../config/db.php';

$success_msg = "";
$error = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST'){
    // Recoger datos del formulario
    $nombre = $conn->real_escape_string($_POST['nombre']);
    $descripcion = $conn->real_escape_string($_POST['descripcion']);

    // Verificar si la institución ya existe
    $sqlCheck = "SELECT id FROM instituciones_externas WHERE nombre = '$nombre'";
    $resultCheck = $conn->query($sqlCheck);
    if ($resultCheck->num_rows > 0) {
        $error = "La institución '$nombre' ya está registrada.";
    } else {
        // Insertar institución en la tabla "instituciones_externas"
        $sqlInstitucion = "INSERT INTO instituciones_externas (nombre, descripcion) VALUES ('$nombre', '$descripcion')";
        if ($conn->query($sqlInstitucion)) {
            $success_msg = "Institución registrada con éxito.";
        } else {
            $error = "Error al registrar institución: " . $conn->error;
        }
    }
}

include '../includes/header.php';
?>

<div class="container my-4">
  <div class="card shadow-sm">
    <div class="card-header bg-primary text-white">
      <h2 class="mb-0">Registrar Institución Externa</h2>
    </div>
    <div class="card-body">
      <?php 
      if(!empty($error)) {
          echo "<div class='alert alert-danger'>$error</div>";
      }
      ?>
      
      <form method="post" action="add.php">
        <div class="mb-3 form-floating">
          <input type="text" name="nombre" class="form-control" id="nombre" placeholder="Nombre de la Institución" required>
          <label for="nombre">Nombre de la Institución</label>
        </div>
        <div class="mb-3 form-floating">
          <textarea name="descripcion" class="form-control" id="descripcion" placeholder="Descripción" style="height: 100px;"></textarea>
          <label for="descripcion">Descripción</label>
        </div>
        <div class="d-flex justify-content-between">
          <button type="submit" class="btn btn-primary">Registrar Institución</button>
          <a href="list.php" class="btn btn-secondary">Volver</a>
        </div>
      </form>
    </div>
  </div>
</div>

<?php include '../includes/footer.php'; ?>

<?php if (!empty($success_msg)) : ?>
<script>
  // Mostrar el mensaje emergente con SweetAlert2
  Swal.fire({
    icon: 'success',
    title: 'Registro Exitoso',
    html: '<?php echo $success_msg; ?>',
    confirmButtonText: 'OK'
  });
</script>
<?php endif; ?>