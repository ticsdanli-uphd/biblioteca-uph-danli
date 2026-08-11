<?php
include '../includes/session.php';
include '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

$bibliografia_id = (int)($_GET['id'] ?? 0);
$user_id = (int)$_SESSION['user_id'];

$stmt = $conn->prepare("
    SELECT b.*,
           (SELECT COUNT(*) FROM registro_visitas rv
            WHERE rv.bibliografia_id=b.id AND rv.tipo='prestamo' AND rv.devuelto=0) AS prestamos_activos
    FROM bibliografia b
    WHERE b.id=? AND b.sede_id=4
");
$stmt->bind_param('i', $bibliografia_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error_msg'] = 'Libro no encontrado o no pertenece a Danlí.';
    header('Location: list.php');
    exit();
}

$libro = $result->fetch_assoc();
$stmt->close();

$disponible = (int)$libro['cantidad'] > (int)$libro['prestamos_activos']
              && !in_array($libro['estado'], ['Baja','Deteriorado'], true);

$stmt = $conn->prepare("
    SELECT id FROM reservas_libros
    WHERE bibliografia_id=? AND user_id=?
      AND estado IN ('pendiente','notificada')
    LIMIT 1
");
$stmt->bind_param('ii', $bibliografia_id, $user_id);
$stmt->execute();
$ya_reservado = $stmt->get_result()->num_rows > 0;
$stmt->close();

$fecha_disponibilidad = date('Y-m-d');

if (!$disponible) {
    $stmt = $conn->prepare("
        SELECT MIN(fecha_devolucion_esperada) AS fecha_proxima
        FROM registro_visitas
        WHERE bibliografia_id=? AND tipo='prestamo' AND devuelto=0
    ");
    $stmt->bind_param('i', $bibliografia_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!empty($row['fecha_proxima'])) {
        $fecha_disponibilidad = date('Y-m-d', strtotime($row['fecha_proxima'] . ' +1 day'));
    }
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$ya_reservado && !$disponible) {
    $alumno_id = (int)($_POST['alumno_id'] ?? 0);
    $nombre_alumno = trim($_POST['alumno_autocomplete'] ?? '');
    $observaciones = trim($_POST['observaciones'] ?? '');
    $carrera_id = !empty($_POST['carrera_id']) ? (int)$_POST['carrera_id'] : null;

    if ($nombre_alumno === '') {
        $error = 'Debe ingresar el nombre del alumno.';
    } else {
        if ($alumno_id > 0 && empty($carrera_id)) {
            $stmt = $conn->prepare("SELECT carrera_id,nombre FROM alumnos WHERE id=?");
            $stmt->bind_param('i', $alumno_id);
            $stmt->execute();
            $a = $stmt->get_result()->fetch_assoc();
            if ($a) {
                $nombre_alumno = trim($a['nombre']);
                $carrera_id = $a['carrera_id'] !== null ? (int)$a['carrera_id'] : null;
            }
            $stmt->close();
        }

        $stmt = $conn->prepare("
            INSERT INTO reservas_libros
            (bibliografia_id,user_id,alumno_id,nombre_alumno,fecha_disponibilidad_estimada,observaciones,carrera_id)
            VALUES (?,?,?,?,?,?,?)
        ");

        $stmt->bind_param(
            'iiisssi',
            $bibliografia_id,$user_id,$alumno_id,$nombre_alumno,
            $fecha_disponibilidad,$observaciones,$carrera_id
        );

        if ($stmt->execute()) {
            $stmt->close();
            header("Location: view.php?id=$bibliografia_id&msg=reserva_ok");
            exit();
        }

        $error = 'Error al registrar la reserva: ' . $stmt->error;
        $stmt->close();
    }
}

$carreras = $conn->query("SELECT id,nombre FROM carreras ORDER BY nombre ASC");

include '../includes/header.php';
?>

<div class="container py-4">
    <div class="card shadow-sm border-0">
        <div class="card-header bg-primary text-white">
            <h4 class="mb-0"><i class="fas fa-bookmark me-2"></i>Reservar Libro</h4>
        </div>

        <div class="card-body">
            <h4><?= htmlspecialchars($libro['nombre']) ?></h4>
            <p class="text-muted">Código: <strong><?= htmlspecialchars($libro['codigo']) ?></strong></p>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <?php if ($ya_reservado): ?>
                <div class="alert alert-warning">Ya existe una reserva activa para este usuario y libro.</div>
                <a href="view.php?id=<?= $bibliografia_id ?>" class="btn btn-secondary">Volver</a>

            <?php elseif ($disponible): ?>
                <div class="alert alert-info">
                    El libro está disponible. Puede registrarse directamente el préstamo.
                </div>
                <a href="prestamo.php?id=<?= $bibliografia_id ?>" class="btn btn-primary">Registrar Préstamo</a>
                <a href="view.php?id=<?= $bibliografia_id ?>" class="btn btn-secondary">Volver</a>

            <?php else: ?>
                <div class="alert alert-warning">
                    El libro no está disponible actualmente.
                    Fecha estimada: <strong><?= date('d/m/Y', strtotime($fecha_disponibilidad)) ?></strong>
                </div>

                <form method="post">
                    <div class="mb-3">
                        <label class="form-label">Alumno *</label>
                        <input type="text" id="alumno_autocomplete" name="alumno_autocomplete"
                               class="form-control" required>
                        <input type="hidden" id="alumno_id" name="alumno_id">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Carrera</label>
                        <select name="carrera_id" id="carrera_id" class="form-select">
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

                    <button class="btn btn-primary"><i class="fas fa-bookmark me-1"></i>Confirmar Reserva</button>
                    <a href="view.php?id=<?= $bibliografia_id ?>" class="btn btn-secondary">Cancelar</a>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
<script>
$(function () {
    $("#alumno_autocomplete").autocomplete({
        source: function(request, response) {
            $.ajax({
                url: "../alumnos/search.php",
                dataType: "json",
                data: {term: request.term},
                success: response,
                error: function() { response([]); }
            });
        },
        minLength: 2,
        select: function(event, ui) {
            $("#alumno_autocomplete").val(ui.item.label);
            $("#alumno_id").val(ui.item.value || ui.item.id || "");
            if (ui.item.carrera_id) $("#carrera_id").val(ui.item.carrera_id);
            return false;
        }
    });
});
</script>

<?php include '../includes/footer.php'; ?>
