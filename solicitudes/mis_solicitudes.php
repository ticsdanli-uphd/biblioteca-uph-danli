<?php

include '../includes/session.php';
include '../config/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

$role = $_SESSION['role'] ?? '';

if (!in_array($role, ['usuario', 'docente'], true)) {
    header('Location: ../dashboard.php');
    exit();
}

$user_id = (int) $_SESSION['user_id'];

$sql = "
    SELECT
        s.id,
        s.estado,
        s.observaciones,
        s.respuesta_admin,
        s.fecha_solicitud,
        s.fecha_respuesta,

        b.codigo,
        b.nombre AS libro

    FROM solicitudes_prestamo s

    INNER JOIN bibliografia b
        ON b.id = s.bibliografia_id

    WHERE s.user_id = ?
      AND s.sede_id = 4

    ORDER BY s.fecha_solicitud DESC
";

$stmt = $conn->prepare($sql);

$stmt->bind_param(
    "i",
    $user_id
);

$stmt->execute();

$result = $stmt->get_result();

include '../includes/header.php';

?>

<div class="container py-4">

    <div class="card border-0 shadow-sm">

        <div class="card-header bg-primary text-white">

            <h4 class="mb-0">

                <i class="fas fa-clipboard-list me-2"></i>

                Mis Solicitudes de Préstamo

            </h4>

        </div>

        <div class="card-body">

            <?php if ($result->num_rows === 0): ?>

                <div class="alert alert-info">

                    <i class="fas fa-info-circle me-2"></i>

                    No tienes solicitudes de préstamo.

                </div>

            <?php else: ?>

                <div class="table-responsive">

                    <table class="table table-hover align-middle">

                        <thead class="table-primary">

                            <tr>

                                <th>Fecha</th>
                                <th>Libro</th>
                                <th>Código</th>
                                <th>Estado</th>
                                <th>Respuesta</th>

                            </tr>

                        </thead>

                        <tbody>

                        <?php while ($row = $result->fetch_assoc()): ?>

                            <tr>

                                <td>

                                    <?= date(
                                        'd/m/Y H:i',
                                        strtotime(
                                            $row['fecha_solicitud']
                                        )
                                    ) ?>

                                </td>

                                <td>

                                    <strong>

                                        <?= htmlspecialchars(
                                            $row['libro']
                                        ) ?>

                                    </strong>

                                </td>

                                <td>

                                    <?= htmlspecialchars(
                                        $row['codigo']
                                    ) ?>

                                </td>

                                <td>

                                    <?php

                                    $estado = $row['estado'];

                                    if ($estado === 'pendiente'):

                                    ?>

                                        <span class="badge bg-warning text-dark">

                                            Pendiente

                                        </span>

                                    <?php elseif ($estado === 'aprobada'): ?>

                                        <span class="badge bg-success">

                                            Aprobada

                                        </span>

                                    <?php elseif ($estado === 'rechazada'): ?>

                                        <span class="badge bg-danger">

                                            Rechazada

                                        </span>

                                    <?php elseif ($estado === 'completada'): ?>

                                        <span class="badge bg-primary">

                                            Completada

                                        </span>

                                    <?php else: ?>

                                        <span class="badge bg-secondary">

                                            Cancelada

                                        </span>

                                    <?php endif; ?>

                                </td>

                                <td>

                                    <?= htmlspecialchars(
                                        $row['respuesta_admin']
                                        ?? 'Sin respuesta'
                                    ) ?>

                                </td>

                            </tr>

                        <?php endwhile; ?>

                        </tbody>

                    </table>

                </div>

            <?php endif; ?>

        </div>

    </div>

</div>

<?php

$stmt->close();

include '../includes/footer.php';

?>