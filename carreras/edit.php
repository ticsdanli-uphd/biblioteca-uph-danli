<?php
include '../includes/session.php';
include '../config/db.php';

if (!isset($_GET['id'])) {
    header('Location: list.php');
    exit();
}

$id = intval($_GET['id']);

// Obtener datos de la carrera
$sql = "SELECT * FROM carreras WHERE id = $id";
$result = $conn->query($sql);

if ($result->num_rows == 0) {
    header('Location: list.php');
    exit();
}

$carrera = $result->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = $conn->real_escape_string($_POST['nombre']);
    
    // Verificar si ya existe otra carrera con el mismo nombre
    $check_sql = "SELECT COUNT(*) as count FROM carreras WHERE nombre = '$nombre' AND id != $id";
    $check_result = $conn->query($check_sql);
    $check_row = $check_result->fetch_assoc();
    
    if ($check_row['count'] > 0) {
        $error = "Ya existe otra carrera con este nombre.";
    } else {
        $update_sql = "UPDATE carreras SET nombre = '$nombre' WHERE id = $id";
        
        if ($conn->query($update_sql)) {
            $success = "Carrera actualizada correctamente.";
            // Actualizar los datos mostrados
            $carrera['nombre'] = $nombre;
        } else {
            $error = "Error al actualizar la carrera: " . $conn->error;
        }
    }
}

include '../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Editar Carrera</h2>
    <a href="list.php" class="btn btn-secondary">Volver a la Lista</a>
</div>

<?php if(isset($success)): ?>
<div class="alert alert-success"><?php echo $success; ?></div>
<?php endif; ?>

<?php if(isset($error)): ?>
<div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <form method="post" action="edit.php?id=<?php echo $id; ?>">
            <div class="mb-3">
                <label for="nombre" class="form-label">Nombre de la Carrera</label>
                <input type="text" class="form-control" id="nombre" name="nombre" value="<?php echo $carrera['nombre']; ?>" required>
            </div>
            <button type="submit" class="btn btn-primary">Actualizar</button>
            <a href="list.php" class="btn btn-secondary">Cancelar</a>
        </form>
    </div>
</div>

<script>
// Usar SweetAlert2 para mostrar mensajes de éxito
document.addEventListener('DOMContentLoaded', function() {
    <?php if(isset($success)): ?>
    Swal.fire({
        title: 'Éxito!',
        text: '<?php echo $success; ?>',
        icon: 'success',
        confirmButtonText: 'Ok'
    });
    <?php endif; ?>
});
</script>

<?php include '../includes/footer.php'; ?>