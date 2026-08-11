<?php
include '../includes/session.php';
include '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: list.php');
    exit();
}

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: list.php');
    exit();
}

$stmt = $conn->prepare("SELECT * FROM bibliografia WHERE id = ? AND sede_id = 4");
$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error_msg'] = 'El libro no existe o no pertenece a Danlí.';
    header('Location: list.php');
    exit();
}

$book = $result->fetch_assoc();
$stmt->close();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $codigo = trim($_POST['codigo'] ?? '');
    $dewey = trim($_POST['dewey'] ?? '');
    $clasificacion = trim($_POST['clasificacion'] ?? '');
    $nombre = trim($_POST['nombre'] ?? '');
    $autores = trim($_POST['autores'] ?? '');
    $editorial = trim($_POST['editorial'] ?? '');
    $edicion = trim($_POST['edicion'] ?? '');
    $anio = !empty($_POST['anio']) ? (int)$_POST['anio'] : null;
    $isbn = trim($_POST['isbn'] ?? '');
    $estado = trim($_POST['estado'] ?? 'Disponible');
    $ubicacion = trim($_POST['ubicacion'] ?? '');
    $fecha_ingreso = !empty($_POST['fecha_ingreso']) ? $_POST['fecha_ingreso'] : null;
    $idioma = trim($_POST['idioma'] ?? '');
    $carrera_id = !empty($_POST['carrera_id']) ? (int)$_POST['carrera_id'] : null;
    $catalogacion = trim($_POST['catalogacion'] ?? '');
    $observaciones = trim($_POST['observaciones'] ?? '');
    $cantidad = (int)($_POST['cantidad'] ?? 1);
    $usuario_id = (int)$_SESSION['user_id'];

    if ($codigo === '' || $nombre === '') {
        $error = 'Código y nombre son obligatorios.';
    } elseif ($cantidad < 1) {
        $error = 'La cantidad debe ser mayor que 0.';
    } elseif (!in_array($estado, ['Disponible','Prestado','Deteriorado','Baja'], true)) {
        $error = 'El estado seleccionado no es válido.';
    } else {
        $check = $conn->prepare("SELECT id FROM bibliografia WHERE codigo = ? AND id <> ?");
        $check->bind_param('si', $codigo, $id);
        $check->execute();

        if ($check->get_result()->num_rows > 0) {
            $error = 'El código ya pertenece a otro libro.';
        }
        $check->close();
    }

    $foto = $book['foto'] ?: null;

    if ($error === '' && isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['jpg','jpeg','png','webp'];
        $extension = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));

        if (!in_array($extension, $allowed, true)) {
            $error = 'La foto debe ser JPG, JPEG, PNG o WEBP.';
        } elseif ($_FILES['foto']['size'] > 5 * 1024 * 1024) {
            $error = 'La foto no puede superar 5 MB.';
        } else {
            $uploadDir = __DIR__ . '/../uploads/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $nuevoNombre = 'libro_' . uniqid('', true) . '.' . $extension;

            if (move_uploaded_file($_FILES['foto']['tmp_name'], $uploadDir . $nuevoNombre)) {
                if (!empty($book['foto'])) {
                    $old = $uploadDir . basename($book['foto']);
                    if (is_file($old)) {
                        @unlink($old);
                    }
                }
                $foto = $nuevoNombre;
            } else {
                $error = 'No se pudo guardar la nueva imagen.';
            }
        }
    }

    if ($error === '') {
        $sql = "UPDATE bibliografia SET
            codigo=?, dewey=?, clasificacion=?, nombre=?, autores=?, editorial=?, edicion=?,
            anio=?, isbn=?, estado=?, ubicacion=?, fecha_ingreso=?, idioma=?, carrera_id=?,
            catalogacion=?, observaciones=?, cantidad=?, foto=?, sede_id=4, modificado_por=?
            WHERE id=? AND sede_id=4";

        $update = $conn->prepare($sql);

        if (!$update) {
            $error = 'Error preparando la actualización: ' . $conn->error;
        } else {
            $update->bind_param(
                'sssssssisssssissisii',
                $codigo,$dewey,$clasificacion,$nombre,$autores,$editorial,$edicion,
                $anio,$isbn,$estado,$ubicacion,$fecha_ingreso,$idioma,$carrera_id,
                $catalogacion,$observaciones,$cantidad,$foto,$usuario_id,$id
            );

            if ($update->execute()) {
                $_SESSION['success_msg'] = 'El libro se actualizó correctamente.';
                $update->close();
                header('Location: list.php');
                exit();
            }

            $error = 'Error al actualizar: ' . $update->error;
            $update->close();
        }
    }
}

$carreras = $conn->query("SELECT id, nombre FROM carreras ORDER BY nombre ASC");

include '../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
        <div>
            <h1 class="fw-bold mb-1"><i class="fas fa-edit text-primary me-2"></i>Editar Libro</h1>
            <p class="text-muted mb-0">Biblioteca UPH - Sede Danlí</p>
        </div>
        <span class="badge bg-primary fs-6 p-2"><i class="fas fa-map-marker-alt me-1"></i>Danlí</span>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="card shadow-sm border-0">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Información bibliográfica</h5>
        </div>

        <div class="card-body">
            <form method="post" enctype="multipart/form-data">
                <div class="row g-3">
                    <?php
                    $textFields = [
                        'codigo' => 'Código',
                        'dewey' => 'Dewey',
                        'clasificacion' => 'Clasificación',
                        'nombre' => 'Nombre del libro',
                        'autores' => 'Autor(es)',
                        'editorial' => 'Editorial',
                        'edicion' => 'Edición',
                        'isbn' => 'ISBN',
                        'ubicacion' => 'Ubicación',
                        'idioma' => 'Idioma',
                        'catalogacion' => 'Catalogación'
                    ];
                    foreach ($textFields as $field => $label):
                    ?>
                        <div class="<?= $field === 'nombre' ? 'col-md-8' : 'col-md-4' ?>">
                            <label class="form-label"><?= $label ?><?= in_array($field,['codigo','nombre'],true) ? ' *' : '' ?></label>
                            <input type="text" name="<?= $field ?>" class="form-control"
                                   value="<?= htmlspecialchars($book[$field] ?? '') ?>"
                                   <?= in_array($field,['codigo','nombre'],true) ? 'required' : '' ?>>
                        </div>
                    <?php endforeach; ?>

                    <div class="col-md-3">
                        <label class="form-label">Año</label>
                        <input type="number" name="anio" class="form-control" min="1000" max="2100"
                               value="<?= htmlspecialchars($book['anio'] ?? '') ?>">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Estado</label>
                        <select name="estado" class="form-select">
                            <?php foreach (['Disponible','Prestado','Deteriorado','Baja'] as $e): ?>
                                <option value="<?= $e ?>" <?= (($book['estado'] ?? '') === $e) ? 'selected' : '' ?>>
                                    <?= $e ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Fecha de ingreso</label>
                        <input type="date" name="fecha_ingreso" class="form-control"
                               value="<?= htmlspecialchars($book['fecha_ingreso'] ?? '') ?>">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Cantidad *</label>
                        <input type="number" name="cantidad" class="form-control" min="1"
                               value="<?= (int)$book['cantidad'] ?>" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Carrera</label>
                        <select name="carrera_id" class="form-select">
                            <option value="">Todas / General</option>
                            <?php while ($c = $carreras->fetch_assoc()): ?>
                                <option value="<?= (int)$c['id'] ?>"
                                    <?= ((int)($book['carrera_id'] ?? 0) === (int)$c['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($c['nombre']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Sede</label>
                        <input type="text" class="form-control" value="4 - Danlí" readonly>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Foto de portada</label>
                        <input type="file" name="foto" class="form-control" accept=".jpg,.jpeg,.png,.webp">
                        <?php if (!empty($book['foto'])): ?>
                            <img src="/biblioteca/uploads/<?= rawurlencode(basename($book['foto'])) ?>"
                                 alt="Portada" class="mt-2 rounded" style="max-width:150px;">
                        <?php endif; ?>
                    </div>

                    <div class="col-12">
                        <label class="form-label">Observaciones</label>
                        <textarea name="observaciones" class="form-control" rows="3"><?= htmlspecialchars($book['observaciones'] ?? '') ?></textarea>
                    </div>
                </div>

                <div class="d-flex justify-content-between mt-4">
                    <a href="list.php" class="btn btn-secondary"><i class="fas fa-arrow-left me-1"></i>Cancelar</a>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
