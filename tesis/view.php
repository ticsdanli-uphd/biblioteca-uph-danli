<?php
include '../includes/session.php';
include '../config/db.php';
include '../includes/header.php';

$id = intval($_GET['id']);
$sql = "SELECT * FROM tesis WHERE id = $id";
$result = $conn->query($sql);
$tesis = $result->fetch_assoc();
?>
<h2>Detalle de la Tesis</h2>
<div class="card">
  <div class="card-body">
    <h5 class="card-title"><?php echo "(" . $tesis['numero'] . ") " . $tesis['titulo']; ?></h5>
    <p class="card-text">
      <strong>N° Cuenta:</strong> <?php echo $tesis['cuenta']; ?><br>
      <strong>Alumno:</strong> <?php echo $tesis['alumno']; ?><br>
      <strong>Carrera:</strong> <?php echo $tesis['carrera']; ?><br>
      <strong>Año Egresado:</strong> <?php echo $tesis['anio_egresado']; ?><br>
      <strong>Asesor Metodológico:</strong> <?php echo $tesis['asesor_metodologico']; ?><br>
      <strong>Asesor Temático:</strong> <?php echo $tesis['asesor_tematico']; ?><br>
      <strong>Cantidad:</strong> <?php echo $tesis['cantidad']; ?><br>
    </p>
    <?php if(isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
      <a href="edit.php?id=<?php echo $tesis['id']; ?>" class="btn btn-warning"><i class="fas fa-edit"></i> Editar</a>
    <?php endif; ?>
    <a href="list.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Volver a la lista</a>
  </div>
</div>
<?php include '../includes/footer.php'; ?>