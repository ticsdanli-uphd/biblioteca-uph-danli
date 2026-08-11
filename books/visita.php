<?php
include '../includes/session.php';
include '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

$bibliografia_id = (int)($_GET['id'] ?? 0);
$user_id = (int)$_SESSION['user_id'];

$stmt = $conn->prepare("SELECT id,nombre,codigo FROM bibliografia WHERE id=? AND sede_id=4");
$stmt->bind_param('i', $bibliografia_id);
$stmt->execute();
$bookResult = $stmt->get_result();

if ($bookResult->num_rows === 0) {
    $_SESSION['error_msg'] = 'Libro no encontrado o no pertenece a Danlí.';
    header('Location: list.php');
    exit();
}

$book = $bookResult->fetch_assoc();
$stmt->close();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre_alumno = trim($_POST['nombre_alumno'] ?? '');
    $observaciones = trim($_POST['observaciones'] ?? '');
    $institucion_id = !empty($_POST['institucion_id']) ? (int)$_POST['institucion_id'] : null;
    $carrera_id = !empty($_POST['carrera_id']) ? (int)$_POST['carrera_id'] : null;
    $es_externo = isset($_POST['es_externo']) ? 1 : 0;

    if ($nombre_alumno === '') {
        $error = 'Debe ingresar el nombre del visitante.';
    } elseif ($es_externo && empty($institucion_id)) {
        $error = 'Seleccione la institución del visitante externo.';
    } else {
        $stmt = $conn->prepare("
            INSERT INTO registro_visitas
            (bibliografia_id,user_id,tipo,observaciones,nombre_alumno,institucion_id,carrera_id,es_externo)
            VALUES (?,?, 'visita',?,?,?,?,?)
        ");

        $stmt->bind_param(
            'iissiii',
            $bibliografia_id,$user_id,$observaciones,$nombre_alumno,
            $institucion_id,$carrera_id,$es_externo
        );

        if ($stmt->execute()) {
            $stmt->close();
            $_SESSION['success_msg'] = 'La visita se registró correctamente.';
            header("Location: view.php?id=$bibliografia_id");
            exit();
        }

        $error = 'Error al registrar la visita: ' . $stmt->error;
        $stmt->close();
    }
}

$instituciones = $conn->query("SELECT id,nombre FROM instituciones_externas ORDER BY nombre");
$carreras = $conn->query("SELECT id,nombre FROM carreras ORDER BY nombre");

include '../includes/header.php';
?>

<div class="container py-4">
    <div class="card shadow-sm border-0">
        <div class="card-header bg-primary text-white">
            <h4 class="mb-0"><i class="fas fa-user-check me-2"></i>Registrar Visita al Libro</h4>
        </div>

        <div class="card-body">
            <div class="alert alert-light border">
                <strong>Libro:</strong> <?= htmlspecialchars($book['nombre']) ?><br>
                <strong>Código:</strong> <?= htmlspecialchars($book['codigo']) ?>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="post">
                <div class="mb-3">
                    <label class="form-label">Nombre del Visitante *</label>
                    <input type="text" name="nombre_alumno" class="form-control"
                           placeholder="Nombre completo" required>
                </div>

                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" name="es_externo"
                           id="es_externo" value="1">
                    <label class="form-check-label" for="es_externo">
                        <strong>Visitante de institución externa</strong>
                    </label>
                </div>

                <div class="mb-3" id="institucion_container" style="display:none;">
                    <label class="form-label">Institución *</label>
                    <select name="institucion_id" id="institucion_id" class="form-select">
                        <option value="">Seleccione una institución</option>
                        <?php while ($i = $instituciones->fetch_assoc()): ?>
                            <option value="<?= (int)$i['id'] ?>"><?= htmlspecialchars($i['nombre']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Carrera</label>
                    <select name="carrera_id" class="form-select">
                        <option value="">Seleccione una carrera</option>
                        <?php while ($c = $carreras->fetch_assoc()): ?>
                            <option value="<?= (int)$c['id'] ?>"><?= htmlspecialchars($c['nombre']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Observaciones</label>
                    <textarea name="observaciones" class="form-control" rows="3"></textarea>
                </div>

                <button class="btn btn-primary"><i class="fas fa-save me-1"></i>Registrar Visita</button>
                <a href="view.php?id=<?= $bibliografia_id ?>" class="btn btn-secondary">Cancelar</a>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('es_externo').addEventListener('change', function () {
    const box = document.getElementById('institucion_container');
    const select = document.getElementById('institucion_id');
    box.style.display = this.checked ? 'block' : 'none';
    select.required = this.checked;
    if (!this.checked) select.value = '';
});
</script>

<?php include '../includes/footer.php'; ?>
