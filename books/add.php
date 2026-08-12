<?php
// ============================================================
// books/add.php
// Registrar nuevo libro - Biblioteca UPH Danlí
// ============================================================

require_once '../includes/session.php';
require_once '../config/db.php';

// ------------------------------------------------------------
// SEGURIDAD
// ------------------------------------------------------------
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$rol = strtolower(trim(
    $_SESSION['role']
    ?? $_SESSION['rol']
    ?? $_SESSION['tipo_usuario']
    ?? ''
));

if (!in_array($rol, ['admin', 'administrador'], true)) {
    header('Location: list.php');
    exit;
}

// ------------------------------------------------------------
// CONFIGURACIÓN
// ------------------------------------------------------------
$sede_id = 4; // Danlí

$error = '';
$success = '';

// ------------------------------------------------------------
// FUNCIÓN PARA GUARDAR FOTOGRAFÍAS
// ------------------------------------------------------------
function guardarFoto(array $file, string $prefijo): array
{
    if (
        !isset($file['error']) ||
        $file['error'] === UPLOAD_ERR_NO_FILE
    ) {
        return [true, null, null];
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return [
            false,
            null,
            'No se pudo recibir la imagen.'
        ];
    }

    // Máximo 5 MB
    if (($file['size'] ?? 0) > 5 * 1024 * 1024) {
        return [
            false,
            null,
            'La imagen no puede superar los 5 MB.'
        ];
    }

    $ext = strtolower(
        pathinfo($file['name'], PATHINFO_EXTENSION)
    );

    $permitidas = [
        'jpg',
        'jpeg',
        'png',
        'webp'
    ];

    if (!in_array($ext, $permitidas, true)) {
        return [
            false,
            null,
            'La imagen debe ser JPG, JPEG, PNG o WEBP.'
        ];
    }

    // Verificar que realmente sea una imagen
    $info = @getimagesize($file['tmp_name']);

    if ($info === false) {
        return [
            false,
            null,
            'El archivo seleccionado no es una imagen válida.'
        ];
    }

    // Carpeta
    $dir = __DIR__ . '/../uploads/';

    if (!is_dir($dir)) {

        if (!mkdir($dir, 0755, true)) {
            return [
                false,
                null,
                'No se pudo crear la carpeta de imágenes.'
            ];
        }
    }

    // Nombre único
    try {
        $nombre =
            $prefijo . '_' .
            bin2hex(random_bytes(10)) .
            '.' . $ext;
    } catch (Throwable $e) {

        $nombre =
            $prefijo . '_' .
            uniqid() .
            '.' . $ext;
    }

    if (
        !move_uploaded_file(
            $file['tmp_name'],
            $dir . $nombre
        )
    ) {
        return [
            false,
            null,
            'No se pudo guardar la imagen.'
        ];
    }

    return [
        true,
        $nombre,
        null
    ];
}

// ------------------------------------------------------------
// CARRERAS
// ------------------------------------------------------------
$carreras = $conn->query(
    "SELECT id, nombre
     FROM carreras
     ORDER BY nombre"
);

// ------------------------------------------------------------
// VARIABLES DEL FORMULARIO
// ------------------------------------------------------------
$codigo = trim($_POST['codigo'] ?? '');

$dewey = trim(
    $_POST['dewey'] ?? ''
);

$clasificacion = trim(
    $_POST['clasificacion'] ?? ''
);

$nombre = trim(
    $_POST['nombre'] ?? ''
);

$autores = trim(
    $_POST['autores']
    ?? $_POST['autor']
    ?? ''
);

$editorial = trim(
    $_POST['editorial'] ?? ''
);

$edicion = trim(
    $_POST['edicion'] ?? ''
);

$anio = !empty($_POST['anio'])
    ? (int) $_POST['anio']
    : null;

$isbn = trim(
    $_POST['isbn'] ?? ''
);

$estado = trim(
    $_POST['estado'] ?? 'Disponible'
);

$estante = trim(
    $_POST['estante'] ?? ''
);

$nivel = isset($_POST['nivel_estante'])
    ? (int) $_POST['nivel_estante']
    : -1;

$fecha_ingreso = trim(
    $_POST['fecha_ingreso']
    ?? date('Y-m-d')
);

$idioma = trim(
    $_POST['idioma']
    ?? 'Español'
);

$carrera_id = !empty($_POST['carrera_id'])
    ? (int) $_POST['carrera_id']
    : null;

$catalogacion = trim(
    $_POST['catalogacion'] ?? ''
);

$observaciones = trim(
    $_POST['observaciones'] ?? ''
);

$cantidad = max(
    1,
    (int) ($_POST['cantidad'] ?? 1)
);

// ------------------------------------------------------------
// UBICACIÓN
// ------------------------------------------------------------
$ubicacion = '';

$estantes_validos = [
    'A-1',
    'A-2',
    'A-3',
    'A-4',
    'A-5',
    'B-1',
    'B-2',
    'B-3',
    'B-4',
    'B-5'
];

if (
    in_array($estante, $estantes_validos, true) &&
    $nivel >= 0 &&
    $nivel <= 4
) {

    $ubicacion =
        'Estante ' .
        $estante .
        ' - Nivel ' .
        $nivel;
}

// ------------------------------------------------------------
// PROCESAR FORMULARIO
// ------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // --------------------------------------------------------
    // VALIDACIONES
    // --------------------------------------------------------
    if ($codigo === '' || $nombre === '') {

        $error =
            'El código y el nombre del libro son obligatorios.';

    } elseif ($ubicacion === '') {

        $error =
            'Seleccione un estante y un nivel válido.';

    } elseif (
        !in_array(
            $estado,
            [
                'Disponible',
                'Prestado',
                'Deteriorado',
                'Baja'
            ],
            true
        )
    ) {

        $error =
            'El estado seleccionado no es válido.';

    } elseif (
        $anio !== null &&
        (
            $anio < 1000 ||
            $anio > ((int) date('Y') + 1)
        )
    ) {

        $error =
            'El año ingresado no es válido.';
    }

    // --------------------------------------------------------
    // VERIFICAR CÓDIGO DUPLICADO
    // --------------------------------------------------------
    if ($error === '') {

        $stmt = $conn->prepare(
            "SELECT id
             FROM bibliografia
             WHERE codigo = ?
             LIMIT 1"
        );

        if (!$stmt) {

            $error =
                'Error al verificar el código: ' .
                $conn->error;

        } else {

            $stmt->bind_param(
                's',
                $codigo
            );

            $stmt->execute();

            $duplicado =
                $stmt
                    ->get_result()
                    ->fetch_assoc();

            $stmt->close();

            if ($duplicado) {

                $error =
                    'El código del libro ya existe.';
            }
        }
    }

    // --------------------------------------------------------
    // FOTOGRAFÍAS
    // --------------------------------------------------------
    $foto = null;
    $frontal = null;
    $trasera = null;

    if ($error === '') {

        // FOTO FRONTAL
        [
            $okFrontal,
            $frontal,
            $errFrontal
        ] = guardarFoto(
            $_FILES['foto_frontal'] ?? [],
            'libro_frontal'
        );

        if (!$okFrontal) {
            $error = $errFrontal;
        }
    }

    if ($error === '') {

        // FOTO TRASERA
        [
            $okTrasera,
            $trasera,
            $errTrasera
        ] = guardarFoto(
            $_FILES['foto_trasera'] ?? [],
            'libro_trasera'
        );

        if (!$okTrasera) {
            $error = $errTrasera;
        }
    }

    // --------------------------------------------------------
    // INSERTAR LIBRO
    // --------------------------------------------------------
    if ($error === '') {

        $sql = "
            INSERT INTO bibliografia
            (
                codigo,
                dewey,
                clasificacion,
                nombre,
                autores,
                editorial,
                edicion,
                anio,
                isbn,
                estado,
                ubicacion,
                fecha_ingreso,
                idioma,
                carrera_id,
                catalogacion,
                observaciones,
                cantidad,
                foto,
                foto_frontal,
                foto_trasera,
                sede_id,
                ingresado_por
            )
            VALUES
            (
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
                ?, ?
            )
        ";

        $stmt = $conn->prepare($sql);

        if (!$stmt) {

            $error =
                'Error al preparar el registro: ' .
                $conn->error;

        } else {

            $usuario_id =
                (int) $_SESSION['user_id'];

            /*
             * IMPORTANTE:
             *
             * foto         = NULL
             * foto_frontal = $frontal
             * foto_trasera = $trasera
             *
             * Antes tenías:
             *
             * $frontal,$frontal,$trasera
             *
             * Eso era incorrecto.
             */

            $stmt->bind_param(
                'sssssssisssssississsii',
                $codigo,
                $dewey,
                $clasificacion,
                $nombre,
                $autores,
                $editorial,
                $edicion,
                $anio,
                $isbn,
                $estado,
                $ubicacion,
                $fecha_ingreso,
                $idioma,
                $carrera_id,
                $catalogacion,
                $observaciones,
                $cantidad,
                $foto,
                $frontal,
                $trasera,
                $sede_id,
                $usuario_id
            );

            if ($stmt->execute()) {

                $success =
                    'Libro registrado correctamente.';

                // ------------------------------------------------
                // LIMPIAR FORMULARIO
                // ------------------------------------------------
                $codigo = '';
                $dewey = '';
                $clasificacion = '';
                $nombre = '';
                $autores = '';
                $editorial = '';
                $edicion = '';
                $anio = null;
                $isbn = '';
                $estado = 'Disponible';
                $estante = '';
                $nivel = -1;
                $fecha_ingreso = date('Y-m-d');
                $idioma = 'Español';
                $carrera_id = null;
                $catalogacion = '';
                $observaciones = '';
                $cantidad = 1;
                $ubicacion = '';

            } else {

                // Eliminar fotografías si falló el INSERT
                if (
                    $frontal &&
                    is_file(
                        __DIR__ .
                        '/../uploads/' .
                        basename($frontal)
                    )
                ) {
                    @unlink(
                        __DIR__ .
                        '/../uploads/' .
                        basename($frontal)
                    );
                }

                if (
                    $trasera &&
                    is_file(
                        __DIR__ .
                        '/../uploads/' .
                        basename($trasera)
                    )
                ) {
                    @unlink(
                        __DIR__ .
                        '/../uploads/' .
                        basename($trasera)
                    );
                }

                $error =
                    'Error al registrar el libro: ' .
                    $stmt->error;
            }

            $stmt->close();
        }
    }
}

// ------------------------------------------------------------
// HEADER
// ------------------------------------------------------------
include '../includes/header.php';
?>

<style>

.book-card {
    max-width: 1100px;
    margin: auto;
    background: #fff;
    border-radius: 18px;
    box-shadow: 0 8px 30px rgba(0,0,0,.07);
    overflow: hidden;
}

.book-card-header {
    background: #0d6efd;
    color: #fff;
    padding: 20px 24px;
    font-size: 21px;
    font-weight: 700;
}

.book-card-body {
    padding: 24px;
}

.preview-img {
    width: 100%;
    height: 230px;
    object-fit: contain;
    border: 1px dashed #cbd5e1;
    border-radius: 12px;
    background: #f8fafc;
    padding: 8px;
}

.location-preview {
    background: #eef5ff;
    border-left: 4px solid #0d6efd;
    padding: 12px 15px;
    border-radius: 8px;
    font-weight: 600;
}

</style>


<div class="container-fluid py-4">

<div class="book-card">

    <!-- HEADER -->
    <div class="book-card-header">

        <i class="fas fa-book me-2"></i>

        Agregar Libro — Biblioteca UPH Danlí

    </div>


    <div class="book-card-body">

        <!-- MENSAJES -->

        <?php if ($error): ?>

            <div class="alert alert-danger">

                <i class="fas fa-exclamation-triangle me-2"></i>

                <?= htmlspecialchars($error) ?>

            </div>

        <?php endif; ?>


        <?php if ($success): ?>

            <div class="alert alert-success">

                <i class="fas fa-check-circle me-2"></i>

                <?= htmlspecialchars($success) ?>

            </div>

        <?php endif; ?>


        <form
            method="post"
            enctype="multipart/form-data"
        >

        <div class="row g-3">


            <!-- CÓDIGO -->
            <div class="col-md-4">

                <label class="form-label">
                    Código *
                </label>

                <input
                    name="codigo"
                    class="form-control"
                    required
                    value="<?= htmlspecialchars($codigo) ?>"
                >

            </div>


            <!-- DEWEY -->
            <div class="col-md-4">

                <label class="form-label">
                    Dewey
                </label>

                <input
                    name="dewey"
                    class="form-control"
                    value="<?= htmlspecialchars($dewey) ?>"
                >

            </div>


            <!-- CLASIFICACIÓN -->
            <div class="col-md-4">

                <label class="form-label">
                    Clasificación
                </label>

                <input
                    name="clasificacion"
                    class="form-control"
                    value="<?= htmlspecialchars($clasificacion) ?>"
                >

            </div>


            <!-- NOMBRE -->
            <div class="col-12">

                <label class="form-label">
                    Nombre del libro *
                </label>

                <input
                    name="nombre"
                    class="form-control"
                    required
                    value="<?= htmlspecialchars($nombre) ?>"
                >

            </div>


            <!-- AUTORES -->
            <div class="col-md-6">

                <label class="form-label">
                    Autor(es)
                </label>

                <input
                    name="autores"
                    class="form-control"
                    value="<?= htmlspecialchars($autores) ?>"
                >

            </div>


            <!-- EDITORIAL -->
            <div class="col-md-6">

                <label class="form-label">
                    Editorial
                </label>

                <input
                    name="editorial"
                    class="form-control"
                    value="<?= htmlspecialchars($editorial) ?>"
                >

            </div>


            <!-- EDICIÓN -->
            <div class="col-md-4">

                <label class="form-label">
                    Edición
                </label>

                <input
                    name="edicion"
                    class="form-control"
                    value="<?= htmlspecialchars($edicion) ?>"
                >

            </div>


            <!-- AÑO -->
            <div class="col-md-4">

                <label class="form-label">
                    Año
                </label>

                <input
                    type="number"
                    name="anio"
                    class="form-control"
                    value="<?= htmlspecialchars((string)$anio) ?>"
                >

            </div>


            <!-- ISBN -->
            <div class="col-md-4">

                <label class="form-label">
                    ISBN
                </label>

                <input
                    name="isbn"
                    class="form-control"
                    value="<?= htmlspecialchars($isbn) ?>"
                >

            </div>


            <!-- ESTADO -->
            <div class="col-md-3">

                <label class="form-label">
                    Estado
                </label>

                <select
                    name="estado"
                    class="form-select"
                >

                    <?php
                    $estados = [
                        'Disponible',
                        'Prestado',
                        'Deteriorado',
                        'Baja'
                    ];
                    ?>

                    <?php foreach ($estados as $e): ?>

                        <option
                            value="<?= htmlspecialchars($e) ?>"
                            <?= $estado === $e
                                ? 'selected'
                                : '' ?>
                        >

                            <?= htmlspecialchars($e) ?>

                        </option>

                    <?php endforeach; ?>

                </select>

            </div>


            <!-- CANTIDAD -->
            <div class="col-md-3">

                <label class="form-label">
                    Cantidad *
                </label>

                <input
                    type="number"
                    min="1"
                    name="cantidad"
                    class="form-control"
                    required
                    value="<?= $cantidad ?>"
                >

            </div>


            <!-- FECHA -->
            <div class="col-md-3">

                <label class="form-label">
                    Fecha de ingreso
                </label>

                <input
                    type="date"
                    name="fecha_ingreso"
                    class="form-control"
                    value="<?= htmlspecialchars($fecha_ingreso) ?>"
                >

            </div>


            <!-- IDIOMA -->
            <div class="col-md-3">

                <label class="form-label">
                    Idioma
                </label>

                <input
                    name="idioma"
                    class="form-control"
                    value="<?= htmlspecialchars($idioma) ?>"
                >

            </div>


            <!-- CARRERA -->
            <div class="col-md-6">

                <label class="form-label">
                    Carrera
                </label>

                <select
                    name="carrera_id"
                    class="form-select"
                >

                    <option value="">
                        Todas / General
                    </option>

                    <?php if ($carreras): ?>

                        <?php while ($c = $carreras->fetch_assoc()): ?>

                            <option
                                value="<?= (int)$c['id'] ?>"
                                <?= (
                                    $carrera_id !== null &&
                                    $carrera_id == $c['id']
                                )
                                    ? 'selected'
                                    : '' ?>
                            >

                                <?= htmlspecialchars($c['nombre']) ?>

                            </option>

                        <?php endwhile; ?>

                    <?php endif; ?>

                </select>

            </div>


            <!-- ESTANTE -->
            <div class="col-md-3">

                <label class="form-label">

                    <i class="fas fa-layer-group me-1"></i>

                    Estante *

                </label>

                <select
                    name="estante"
                    id="estante"
                    class="form-select"
                    required
                >

                    <option value="">
                        Seleccione
                    </option>

                    <optgroup label="Estantes A">

                        <?php
                        for ($i = 1; $i <= 5; $i++):
                        ?>

                            <?php
                            $valor = 'A-' . $i;
                            ?>

                            <option
                                value="<?= $valor ?>"
                                <?= $estante === $valor
                                    ? 'selected'
                                    : '' ?>
                            >

                                Estante <?= $valor ?>

                            </option>

                        <?php endfor; ?>

                    </optgroup>


                    <optgroup label="Estantes B">

                        <?php
                        for ($i = 1; $i <= 5; $i++):
                        ?>

                            <?php
                            $valor = 'B-' . $i;
                            ?>

                            <option
                                value="<?= $valor ?>"
                                <?= $estante === $valor
                                    ? 'selected'
                                    : '' ?>
                            >

                                Estante <?= $valor ?>

                            </option>

                        <?php endfor; ?>

                    </optgroup>

                </select>

            </div>


            <!-- NIVEL -->
            <div class="col-md-3">

                <label class="form-label">

                    <i class="fas fa-sort-amount-up me-1"></i>

                    Nivel *

                </label>

                <select
                    name="nivel_estante"
                    id="nivel_estante"
                    class="form-select"
                    required
                >

                    <option value="">
                        Seleccione
                    </option>

                    <?php for ($n = 0; $n <= 4; $n++): ?>

                        <option
                            value="<?= $n ?>"
                            <?= $nivel === $n
                                ? 'selected'
                                : '' ?>
                        >

                            Nivel <?= $n ?>

                        </option>

                    <?php endfor; ?>

                </select>

            </div>


            <!-- UBICACIÓN -->
            <div class="col-12">

                <div
                    class="location-preview"
                    id="ubicacion_preview"
                >

                    <i class="fas fa-map-marker-alt me-2"></i>

                    <strong>Ubicación:</strong>

                    <span id="ubicacion_text">

                        <?= $ubicacion !== ''
                            ? htmlspecialchars($ubicacion)
                            : 'Seleccione estante y nivel' ?>

                    </span>

                </div>

            </div>


            <!-- FOTO FRONTAL -->
            <div class="col-md-6">

                <label class="form-label fw-bold">

                    📕 Foto frontal / portada

                </label>

                <input
                    type="file"
                    name="foto_frontal"
                    id="foto_frontal"
                    class="form-control"
                    accept="image/jpeg,image/png,image/webp"
                >

                <small class="text-muted">
                    JPG, PNG o WEBP. Máximo 5 MB.
                </small>

                <div class="mt-2">

                    <img
                        id="preview_frontal"
                        class="preview-img"
                        alt="Vista previa frontal"
                        style="display:none"
                    >

                </div>

            </div>


            <!-- FOTO TRASERA -->
            <div class="col-md-6">

                <label class="form-label fw-bold">

                    📗 Foto trasera / contraportada

                </label>

                <input
                    type="file"
                    name="foto_trasera"
                    id="foto_trasera"
                    class="form-control"
                    accept="image/jpeg,image/png,image/webp"
                >

                <small class="text-muted">
                    JPG, PNG o WEBP. Máximo 5 MB.
                </small>

                <div class="mt-2">

                    <img
                        id="preview_trasera"
                        class="preview-img"
                        alt="Vista previa trasera"
                        style="display:none"
                    >

                </div>

            </div>


            <!-- CATALOGACIÓN -->
            <div class="col-12">

                <label class="form-label">
                    Catalogación
                </label>

                <input
                    name="catalogacion"
                    class="form-control"
                    value="<?= htmlspecialchars($catalogacion) ?>"
                >

            </div>


            <!-- OBSERVACIONES -->
            <div class="col-12">

                <label class="form-label">
                    Observaciones
                </label>

                <textarea
                    name="observaciones"
                    class="form-control"
                    rows="3"
                ><?= htmlspecialchars($observaciones) ?></textarea>

            </div>


        </div>


        <!-- BOTONES -->
        <div class="d-flex justify-content-between mt-4">

            <a
                href="list.php"
                class="btn btn-secondary"
            >

                <i class="fas fa-arrow-left me-1"></i>

                Cancelar

            </a>


            <button
                type="submit"
                class="btn btn-primary"
            >

                <i class="fas fa-save me-1"></i>

                Guardar Libro

            </button>

        </div>


        </form>

    </div>

</div>

</div>


<script>

// ============================================================
// PREVISUALIZACIÓN DE UBICACIÓN
// ============================================================

function actualizarUbicacion() {

    const estante =
        document.getElementById('estante').value;

    const nivel =
        document.getElementById('nivel_estante').value;

    const texto =
        document.getElementById('ubicacion_text');

    if (estante !== '' && nivel !== '') {

        texto.textContent =
            'Estante ' +
            estante +
            ' - Nivel ' +
            nivel;

    } else {

        texto.textContent =
            'Seleccione estante y nivel';
    }
}


document
    .getElementById('estante')
    .addEventListener(
        'change',
        actualizarUbicacion
    );


document
    .getElementById('nivel_estante')
    .addEventListener(
        'change',
        actualizarUbicacion
    );


// ============================================================
// PREVISUALIZACIÓN DE IMÁGENES
// ============================================================

function preview(input, id) {

    const archivo = input.files[0];

    const imagen =
        document.getElementById(id);

    if (!archivo) {

        imagen.src = '';
        imagen.style.display = 'none';

        return;
    }

    // Validar tamaño
    if (archivo.size > 5 * 1024 * 1024) {

        alert(
            'La imagen no puede superar los 5 MB.'
        );

        input.value = '';

        imagen.src = '';
        imagen.style.display = 'none';

        return;
    }

    // Validar tipo
    const tiposPermitidos = [
        'image/jpeg',
        'image/png',
        'image/webp'
    ];

    if (!tiposPermitidos.includes(archivo.type)) {

        alert(
            'Seleccione una imagen JPG, PNG o WEBP.'
        );

        input.value = '';

        imagen.src = '';
        imagen.style.display = 'none';

        return;
    }

    imagen.src =
        URL.createObjectURL(archivo);

    imagen.style.display = 'block';
}


document
    .getElementById('foto_frontal')
    .addEventListener(
        'change',
        function () {

            preview(
                this,
                'preview_frontal'
            );

        }
    );


document
    .getElementById('foto_trasera')
    .addEventListener(
        'change',
        function () {

            preview(
                this,
                'preview_trasera'
            );

        }
    );


// Inicializar ubicación
actualizarUbicacion();

</script>


<?php
include '../includes/footer.php';
?>