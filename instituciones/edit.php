<?php
include '../includes/session.php';
include '../config/db.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: list.php");
    exit();
}

$id = intval($_GET['id']);
$success_msg = "";
$error = "";

// Obtener datos de la institución
$sql = "SELECT * FROM instituciones_externas WHERE id = $id";
$result = $conn->query($sql);

if ($result->num_rows == 0) {
    header("Location: list.php");
    exit();
}

$institucion = $result->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] == 'POST'){
    // Recoger datos del formulario
    $nombre = $conn->real_escape_string($_POST['nombre']);
    $descripcion = $conn->real_escape_string($_POST['descripcion']);

    // Verificar si el nombre ya existe (excluyendo la institución actual)
    $sqlCheck = "SELECT id FROM instituciones_externas WHERE nombre = '$nombre' AND id != $id";
    $resultCheck = $conn->query($sqlCheck);
    if ($resultCheck->num_rows > 0) {
        $error = "Ya existe otra institución con el nombre '$nombre'.";
    } else {
        // Actualizar institución
        $sqlUpdate = "UPDATE instituciones_externas SET nombre = '$nombre', descripcion = '$descripcion' WHERE id = $id";
        if ($conn->query($sqlUpdate)) {
            $success_msg = "Institución actualizada con éxito.";
            // Actualizar los datos mostrados
            $institucion['nombre'] = $nombre;
            $institucion['descripcion'] = $descripcion;
        } else {
            $error = "Error al actualizar institución: " . $conn->error;
        }
    }
}

include '../includes/header.php';
?>

<div class="container my-4">
  <div class="card shadow-sm">
    <div class="card-header bg-primary text-white">
      <h2 class="mb-0">Editar Institución Externa</h2>
    </div>
    <div class="card-body">
      <?php 
      if(!empty($error)) {
          echo "<div class='alert alert-danger'>$error</div>";
      }
      ?>
      
      <form method="post" action="edit.php?id=<?php echo $id; ?>">
        <div class="mb-3 form-floating">
          <input type="text" name="nombre" class="form-control" id="nombre" placeholder="Nombre de la Institución" value="<?php echo htmlspecialchars($institucion['nombre']); ?>" required>
          <label for="nombre">Nombre de la Institución</label>
        </div>
        <div class="mb-3 form-floating">
          <textarea name="descripcion" class="form-control" id="descripcion" placeholder="Descripción" style="height: 100px;"><?php echo htmlspecialchars($institucion['descripcion']); ?></textarea>
          <label for="descripcion">Descripción</label>
        </div>
        <div class="d-flex justify-content-between">
          <button type="submit" class="btn btn-primary">Actualizar Institución</button>
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
    title: 'Actualización Exitosa',
    html: '<?php echo $success_msg; ?>',
    confirmButtonText: 'OK'
  });
</script>
<?php endif; ?>