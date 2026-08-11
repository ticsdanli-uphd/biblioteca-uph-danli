<?php

include '../includes/session.php';
include '../config/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/*
|--------------------------------------------------------------------------
| VERIFICAR SESIÓN
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

/*
|--------------------------------------------------------------------------
| SOLO ALUMNOS Y DOCENTES
|--------------------------------------------------------------------------
*/

$role = $_SESSION['role'] ?? '';

if (!in_array($role, ['usuario', 'docente'], true)) {
    header('Location: ../dashboard.php');
    exit();
}

$user_id = (int) $_SESSION['user_id'];

$sede_id = 4;

$error = '';
$success = '';

/*
|--------------------------------------------------------------------------
| OBTENER LIBRO
|--------------------------------------------------------------------------
*/

$bibliografia_id = isset($_GET['id'])
    ? (int) $_GET['id']
    : (int) ($_POST['bibliografia_id'] ?? 0);

if ($bibliografia_id <= 0) {
    header('Location: ../books/list.php');
    exit();
}

$stmt = $conn->prepare("
    SELECT
        b.id,
        b.codigo,
        b.nombre,
        b.autores,
        b.editorial,
        b.cantidad,
        b.estado,
        b.sede_id
    FROM bibliografia b
    WHERE b.id = ?
      AND b.sede_id = 4
    LIMIT 1
");

$stmt->bind_param("i", $bibliografia_id);
$stmt->execute();

$resultado = $stmt->get_result();
$libro = $resultado->fetch_assoc();

$stmt->close();

if (!$libro) {
    die("El libro no existe o no pertenece a la sede de Danlí.");
}

/*
|--------------------------------------------------------------------------
| VERIFICAR DISPONIBILIDAD
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    SELECT COUNT(*) AS total
    FROM registro_visitas
    WHERE bibliografia_id = ?
      AND tipo = 'prestamo'
      AND devuelto = 0
");

$stmt->bind_param("i", $bibliografia_id);
$stmt->execute();

$prestamos = $stmt->get_result()->fetch_assoc();
$stmt->close();

$prestamos_activos = (int) ($prestamos['total'] ?? 0);
$cantidad = (int) ($libro['cantidad'] ?? 0);

$disponible = $cantidad > $prestamos_activos
    && $libro['estado'] === 'Disponible';

/*
|--------------------------------------------------------------------------
| PROCESAR SOLICITUD
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $observaciones = trim(
        $_POST['observaciones'] ?? ''
    );

    if (!$disponible) {

        $error = "Este libro no está disponible actualmente.";

    } else {

        /*
        | Evitar solicitudes duplicadas
        */

        $stmt = $conn->prepare("
            SELECT id
            FROM solicitudes_prestamo
            WHERE bibliografia_id = ?
              AND user_id = ?
              AND estado = 'pendiente'
            LIMIT 1
        ");

        $stmt->bind_param(
            "ii",
            $bibliografia_id,
            $user_id
        );

        $stmt->execute();

        $existe = $stmt->get_result()->fetch_assoc();

        $stmt->close();

        if ($existe) {

            $error =
                "Ya tienes una solicitud pendiente para este libro.";

        } else {

            $stmt = $conn->prepare("
                INSERT INTO solicitudes_prestamo
                (
                    bibliografia_id,
                    user_id,
                    sede_id,
                    estado,
                    observaciones
                )
                VALUES (?, ?, 4, 'pendiente', ?)
            ");

            $stmt->bind_param(
                "iis",
                $bibliografia_id,
                $user_id,
                $observaciones
            );

            if ($stmt->execute()) {

                $success =
                    "Solicitud enviada correctamente. "
                    . "La biblioteca revisará tu solicitud.";

            } else {

                $error =
                    "No se pudo registrar la solicitud.";

            }

            $stmt->close();
        }
    }
}

include '../includes/header.php';

?>

<div class="container py-4">

    <div class="card shadow-sm border-0">

        <div class="card-header bg-primary text-white py-3">

            <h4 class="mb-0">

                <i class="fas fa-book-reader me-2"></i>

                Solicitar préstamo

            </h4>

        </div>

        <div class="card-body">

            <?php if ($success): ?>

                <div class="alert alert-success">

                    <i class="fas fa-check-circle me-2"></i>

                    <?= htmlspecialchars($success) ?>

                </div>

                <a
                    href="../books/list.php"
                    class="btn btn-primary"
                >
                    <i class="fas fa-book me-1"></i>
                    Volver a libros
                </a>

            <?php else: ?>

                <?php if ($error): ?>

                    <div class="alert alert-danger">

                        <i class="fas fa-exclamation-circle me-2"></i>

                        <?= htmlspecialchars($error) ?>

                    </div>

                <?php endif; ?>

                <div class="row g-4">

                    <div class="col-lg-8">

                        <h3 class="mb-3">

                            <?= htmlspecialchars(
                                $libro['nombre']
                            ) ?>

                        </h3>

                        <div class="mb-2">

                            <strong>Código:</strong>

                            <?= htmlspecialchars(
                                $libro['codigo']
                            ) ?>

                        </div>

                        <div class="mb-2">

                            <strong>Autor(es):</strong>

                            <?= htmlspecialchars(
                                $libro['autores'] ?? 'No especificado'
                            ) ?>

                        </div>

                        <div class="mb-3">

                            <strong>Editorial:</strong>

                            <?= htmlspecialchars(
                                $libro['editorial'] ?? 'No especificada'
                            ) ?>

                        </div>

                        <?php if ($disponible): ?>

                            <div class="alert alert-success">

                                <i class="fas fa-check-circle me-2"></i>

                                <strong>Disponible para préstamo</strong>

                            </div>

                        <?php else: ?>

                            <div class="alert alert-warning">

                                <i class="fas fa-clock me-2"></i>

                                Este libro no está disponible
                                actualmente.

                            </div>

                        <?php endif; ?>

                    </div>

                </div>

                <?php if ($disponible): ?>

                    <hr>

                    <form method="POST">

                        <input
                            type="hidden"
                            name="bibliografia_id"
                            value="<?= $bibliografia_id ?>"
                        >

                        <div class="mb-3">

                            <label class="form-label">

                                Observaciones

                            </label>

                            <textarea
                                name="observaciones"
                                class="form-control"
                                rows="4"
                                placeholder="Escriba alguna observación si es necesario..."
                            ></textarea>

                        </div>

                        <button
                            type="submit"
                            class="btn btn-primary"
                        >

                            <i class="fas fa-paper-plane me-1"></i>

                            Enviar solicitud

                        </button>

                        <a
                            href="../books/list.php"
                            class="btn btn-secondary"
                        >

                            Cancelar

                        </a>

                    </form>

                <?php endif; ?>

            <?php endif; ?>

        </div>

    </div>

</div>

<?php include '../includes/footer.php'; ?>