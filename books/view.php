<?php
include '../includes/session.php';
include '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

$id = (int)($_GET['id'] ?? 0);

$stmt = $conn->prepare("
    SELECT b.*,
           c.nombre AS carrera_nombre,
           s.nombre AS sede_nombre,
           u1.username AS creado_por,
           u2.username AS editado_por,
           (SELECT COUNT(*) FROM registro_visitas rv
            WHERE rv.bibliografia_id=b.id AND rv.tipo='prestamo' AND rv.devuelto=0) AS prestamos_activos,
           (SELECT COUNT(*) FROM reservas_libros rl
            WHERE rl.bibliografia_id=b.id AND rl.estado IN ('pendiente','notificada')) AS reservas_activas
    FROM bibliografia b
    LEFT JOIN carreras c ON c.id=b.carrera_id
    LEFT JOIN sedes s ON s.id=b.sede_id
    LEFT JOIN usuarios u1 ON u1.id=b.ingresado_por
    LEFT JOIN usuarios u2 ON u2.id=b.modificado_por
    WHERE b.id=? AND b.sede_id=4
");
$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error_msg'] = 'Libro no encontrado o no pertenece a Danlí.';
    header('Location: list.php');
    exit();
}

$book = $result->fetch_assoc();
$stmt->close();

$user_id = (int)$_SESSION['user_id'];

$stmt = $conn->prepare("
    SELECT id FROM reservas_libros
    WHERE bibliografia_id=? AND user_id=?
      AND estado IN ('pendiente','notificada')
    LIMIT 1
");
$stmt->bind_param('ii', $id, $user_id);
$stmt->execute();
$usuario_tiene_reserva = $stmt->get_result()->num_rows > 0;
$stmt->close();

$prestamosActivos = (int)$book['prestamos_activos'];
$cantidad = (int)$book['cantidad'];
$disponible = $cantidad > $prestamosActivos && ($book['estado'] ?? 'Disponible') !== 'Baja';

include '../includes/header.php';
?>

<div class="container py-4">
    <?php if (isset($_GET['msg']) && $_GET['msg'] === 'prestamo_ok'): ?>
        <div class="alert alert-success"><i class="fas fa-check-circle me-2"></i>Préstamo registrado correctamente.</div>
    <?php elseif (isset($_GET['msg']) && $_GET['msg'] === 'reserva_ok'): ?>
        <div class="alert alert-success"><i class="fas fa-check-circle me-2"></i>Reserva registrada correctamente.</div>
    <?php endif; ?>

    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
        <div>
            <h1 class="fw-bold mb-1"><i class="fas fa-book text-primary me-2"></i>Detalle del Libro</h1>
            <p class="text-muted mb-0">Biblioteca UPH - Danlí</p>
        </div>
        <span class="badge bg-primary fs-6 p-2"><i class="fas fa-map-marker-alt me-1"></i>Danlí</span>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body p-4">
            <div class="row g-4">
                <div class="col-lg-3 text-center">
                    <?php if (!empty($book['foto'])): ?>
                        <img src="/biblioteca/uploads/<?= rawurlencode(basename($book['foto'])) ?>"
                             class="img-fluid rounded shadow-sm" style="max-height:320px;" alt="Portada">
                    <?php else: ?>
                        <div class="bg-light rounded p-5 text-muted">
                            <i class="fas fa-book fa-4x"></i>
                            <div class="mt-2">Sin portada</div>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="col-lg-9">
                    <h2 class="fw-bold"><?= htmlspecialchars($book['nombre']) ?></h2>
                    <p class="text-muted"><strong>Código:</strong> <?= htmlspecialchars($book['codigo']) ?></p>

                    <div class="row g-2">
                        <?php
                        $details = [
                            'Dewey' => $book['dewey'] ?? '',
                            'Clasificación' => $book['clasificacion'] ?? '',
                            'Autor(es)' => $book['autores'] ?? '',
                            'Editorial' => $book['editorial'] ?? '',
                            'Edición' => $book['edicion'] ?? '',
                            'Año' => $book['anio'] ?? '',
                            'ISBN' => $book['isbn'] ?? '',
                            'Ubicación' => $book['ubicacion'] ?? '',
                            'Idioma' => $book['idioma'] ?? '',
                            'Carrera' => $book['carrera_nombre'] ?? 'Todas / General',
                            'Fecha de ingreso' => !empty($book['fecha_ingreso']) ? date('d/m/Y', strtotime($book['fecha_ingreso'])) : '',
                            'Cantidad' => $cantidad,
                            'Sede' => $book['sede_nombre'] ?? 'Danlí'
                        ];
                        foreach ($details as $label => $value):
                        ?>
                            <div class="col-md-6">
                                <div class="border rounded p-2 h-100">
                                    <small class="text-muted d-block"><?= $label ?></small>
                                    <strong><?= htmlspecialchars((string)$value) ?></strong>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="mt-3">
                        <span class="badge bg-<?= $disponible ? 'success' : 'danger' ?> fs-6">
                            <?= $disponible ? 'Disponible' : 'No disponible' ?>
                        </span>
                        <span class="badge bg-info text-dark fs-6"><?= $prestamosActivos ?> préstamo(s) activo(s)</span>
                        <?php if ((int)$book['reservas_activas'] > 0): ?>
                            <span class="badge bg-warning text-dark fs-6"><?= (int)$book['reservas_activas'] ?> reserva(s)</span>
                        <?php endif; ?>
                    </div>

                    <?php if (!empty($book['observaciones'])): ?>
                        <div class="alert alert-light border mt-3">
                            <strong>Observaciones:</strong><br>
                            <?= nl2br(htmlspecialchars($book['observaciones'])) ?>
                        </div>
                    <?php endif; ?>

                    <div class="d-flex flex-wrap gap-2 mt-4">
                        <a href="list.php" class="btn btn-secondary"><i class="fas fa-arrow-left me-1"></i>Volver</a>

                        <?php if (($_SESSION['role'] ?? '') === 'admin'): ?>
                            <a href="edit.php?id=<?= $id ?>" class="btn btn-warning"><i class="fas fa-edit me-1"></i>Editar</a>
                        <?php endif; ?>

                        <?php if ($disponible): ?>
                            <a href="prestamo.php?id=<?= $id ?>" class="btn btn-primary"><i class="fas fa-book-reader me-1"></i>Registrar Préstamo</a>
                        <?php elseif ($usuario_tiene_reserva): ?>
                            <button class="btn btn-secondary" disabled><i class="fas fa-bookmark me-1"></i>Ya tienes reserva</button>
                        <?php else: ?>
                            <a href="reservar.php?id=<?= $id ?>" class="btn btn-primary"><i class="fas fa-bookmark me-1"></i>Reservar Libro</a>
                        <?php endif; ?>

                        <a href="visita.php?id=<?= $id ?>" class="btn btn-info text-white"><i class="fas fa-user-check me-1"></i>Registrar Visita</a>
                    </div>

                    <hr>

                    <small class="text-muted">
                        Ingresado por: <?= htmlspecialchars($book['creado_por'] ?? 'N/D') ?> |
                        Creado: <?= htmlspecialchars($book['fecha_creacion'] ?? '') ?><br>
                        Modificado por: <?= htmlspecialchars($book['editado_por'] ?? 'N/D') ?> |
                        Última modificación: <?= htmlspecialchars($book['ultima_modificacion'] ?? '') ?>
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
