<?php
include '../includes/session.php';
include '../config/db.php';

if (!isset($_GET['id'])) {
    die("No se especificó el libro.");
}

$bibliografia_id = intval($_GET['id']);
$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $observaciones = isset($_POST['observaciones']) ? $conn->real_escape_string($_POST['observaciones']) : '';
    $sql = "INSERT INTO registro_visitas (bibliografia_id, user_id, tipo, observaciones)
            VALUES ($bibliografia_id, $user_id, 'visita', '$observaciones')";
    if ($conn->query($sql)) {
        header("Location: view.php?id=$bibliografia_id");
        exit();
    } else {
        $error = "Error al registrar visita: " . $conn->error;
    }
}

include '../includes/header.php';
?>
<h2>Registrar Visita (Libro Específico)</h2>
<?php if(isset($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>
<form method="post" action="visita.php?id=<?php echo $bibliografia_id; ?>">
  <div class="mb-3">
    <label class="form-label">Observaciones (opcional)</label>
    <textarea name="observaciones" class="form-control" rows="3"></textarea>
  </div>
  <button type="submit" class="btn btn-primary">Registrar Visita</button>
  <a href="view.php?id=<?php echo $bibliografia_id; ?>" class="btn btn-secondary">Cancelar</a>
</form>
<?php include '../includes/footer.php'; ?>