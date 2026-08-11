<?php
include '../includes/session.php';
include '../config/db.php';

// Eliminar carrera
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    
    // Verificar si la carrera está en uso
    $check_sql = "SELECT COUNT(*) as count FROM alumnos WHERE carrera_id = $id";
    $check_result = $conn->query($check_sql);
    $check_row = $check_result->fetch_assoc();
    
    if ($check_row['count'] > 0) {
        $error = "No se puede eliminar la carrera porque está siendo utilizada por alumnos.";
    } else {
        $delete_sql = "DELETE FROM carreras WHERE id = $id";
        if ($conn->query($delete_sql)) {
            $success = "Carrera eliminada correctamente.";
        } else {
            $error = "Error al eliminar la carrera: " . $conn->error;
        }
    }
}

// Obtener todas las carreras
$sql = "SELECT * FROM carreras ORDER BY nombre";
$result = $conn->query($sql);

include '../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Gestión de Carreras</h2>
    <a href="add.php" class="btn btn-primary">Agregar Nueva Carrera</a>
</div>

<?php if(isset($success)): ?>
<div class="alert alert-success"><?php echo $success; ?></div>
<?php endif; ?>

<?php if(isset($error)): ?>
<div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php while($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $row['id']; ?></td>
                        <td><?php echo $row['nombre']; ?></td>
                        <td>
                            <a href="edit.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-info">Editar</a>
                            <a href="list.php?delete=<?php echo $row['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Está seguro de eliminar esta carrera?')">Eliminar</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="3" class="text-center">No hay carreras registradas</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include '../includes/footer.php'; ?>