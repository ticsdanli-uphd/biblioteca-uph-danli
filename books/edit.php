<?php
// ============================================================
// books/edit.php
// Editar libro - Biblioteca UPH Danlí
// ============================================================

include '../includes/session.php';
include '../config/db.php';

// ============================================================
// SEGURIDAD
// ============================================================

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

$rol = strtolower(trim(
    $_SESSION['role']
    ?? $_SESSION['rol']
    ?? $_SESSION['tipo_usuario']
    ?? ''
));

if (!in_array($rol, ['admin', 'administrador'], true)) {
    header('Location: list.php');
    exit();
}

// ============================================================
// CONFIGURACIÓN
// ============================================================

const DANLI_SEDE_ID = 4;

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);

if ($id <= 0) {
    header('Location: list.php');
    exit();
}

$error = '';
$success = '';

// ============================================================
// CARGAR LIBRO
// ============================================================

$stmt = $conn->prepare("
    SELECT *
    FROM bibliografia
    WHERE id = ?
      AND sede_id = ?
    LIMIT 1
");

if (!$stmt) {
    die(
        'Error al preparar consulta: ' .
        htmlspecialchars($conn->error)
    );
}

$sede_id = DANLI_SEDE_ID;

$stmt->bind_param(
    'ii',
    $id,
    $sede_id
);

$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows === 0) {

    $stmt->close();

    $_SESSION['error_msg'] =
        'El libro no existe o no pertenece a la sede Danlí.';

    header('Location: list.php');
    exit();
}

$book = $result->fetch_assoc();

$stmt->close();

// ============================================================
// FOTOS ACTUALES
// ============================================================

// Foto frontal.
// Si no existe foto_frontal, intenta usar foto antigua.
$frontal = !empty($book['foto_frontal'])
    ? $book['foto_frontal']
    : ($book['foto'] ?? '');

$trasera = $book['foto_trasera'] ?? '';

// ============================================================
// UBICACIÓN ACTUAL
// ============================================================

$estante_actual = '';
$nivel_actual = '';

$ubicacion_actual = trim(
    $book['ubicacion'] ?? ''
);

// Intentar detectar:
// Estante A-1 - Nivel 0
if (
    preg_match(
        '/Estante\s+([AB]-[1-5])\s*-\s*Nivel\s*([0-4])/i',
        $ubicacion_actual,
        $matches
    )
) {

    $estante_actual = strtoupper($matches[1]);
    $nivel_actual = (int)$matches[2];

} else {

    // Compatibilidad con ubicaciones antiguas
    if (
        preg_match(
            '/\b([AB]-[1-5])\b/i',
            $ubicacion_actual,
            $matches
        )
    ) {
        $estante_actual =
            strtoupper($matches[1]);
    }

    if (
        preg_match(
            '/(?:nivel|level)\s*([0-4])/i',
            $ubicacion_actual,
            $matches
        )
    ) {
        $nivel_actual = (int)$matches[1];
    }
}

// ============================================================
// PROCESAR FORMULARIO
// ============================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // --------------------------------------------------------
    // DATOS
    // --------------------------------------------------------

    $codigo = trim(
        $_POST['codigo'] ?? ''
    );

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
        $_POST['autores'] ?? ''
    );

    $editorial = trim(
        $_POST['editorial'] ?? ''
    );

    $edicion = trim(
        $_POST['edicion'] ?? ''
    );

    $anio = !empty($_POST['anio'])
        ? (int)$_POST['anio']
        : null;

    $isbn = trim(
        $_POST['isbn'] ?? ''
    );

    $estado = trim(
        $_POST['estado'] ?? 'Disponible'
    );

    $fecha_ingreso = !empty(
        $_POST['fecha_ingreso']
    )
        ? $_POST['fecha_ingreso']
        : null;

    $idioma = trim(
        $_POST['idioma'] ?? 'Español'
    );

    $carrera_id = !empty(
        $_POST['carrera_id']
    )
        ? (int)$_POST['carrera_id']
        : null;

    $catalogacion = trim(
        $_POST['catalogacion'] ?? ''
    );

    $observaciones = trim(
        $_POST['observaciones'] ?? ''
    );

    $cantidad = (int)(
        $_POST['cantidad'] ?? 1
    );

    // --------------------------------------------------------
    // ESTANTE Y NIVEL
    // --------------------------------------------------------

    $estante = strtoupper(
        trim($_POST['estante'] ?? '')
    );

    $nivel = isset(
        $_POST['nivel_estante']
    )
        ? (int)$_POST['nivel_estante']
        : -1;

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

    $ubicacion = '';

    if (
        in_array(
            $estante,
            $estantes_validos,
            true
        ) &&
        $nivel >= 0 &&
        $nivel <= 4
    ) {

        $ubicacion =
            'Estante ' .
            $estante .
            ' - Nivel ' .
            $nivel;
    }

    // --------------------------------------------------------
    // USUARIO QUE MODIFICA
    // --------------------------------------------------------

    $usuario_id =
        (int)$_SESSION['user_id'];

    // ========================================================
    // VALIDACIONES
    // ========================================================

    if (
        $codigo === '' ||
        $nombre === ''
    ) {

        $error =
            'El código y el nombre del libro son obligatorios.';

    } elseif ($cantidad < 1) {

        $error =
            'La cantidad debe ser mayor que 0.';

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
            $anio > 2100
        )
    ) {

        $error =
            'El año ingresado no es válido.';

    } elseif (
        !in_array(
            $estante,
            $estantes_validos,
            true
        )
    ) {

        $error =
            'Seleccione un estante válido.';

    } elseif (
        $nivel < 0 ||
        $nivel > 4
    ) {

        $error =
            'Seleccione un nivel entre 0 y 4.';

    }

    // ========================================================
    // VERIFICAR CÓDIGO DUPLICADO
    // ========================================================

    if ($error === '') {

        $check = $conn->prepare("
            SELECT id
            FROM bibliografia
            WHERE codigo = ?
              AND id <> ?
              AND sede_id = ?
            LIMIT 1
        ");

        if (!$check) {

            $error =
                'Error al verificar el código: ' .
                $conn->error;

        } else {

            $check->bind_param(
                'sii',
                $codigo,
                $id,
                $sede_id
            );

            $check->execute();

            $duplicado =
                $check
                    ->get_result()
                    ->fetch_assoc();

            $check->close();

            if ($duplicado) {

                $error =
                    'El código ya pertenece a otro libro.';
            }
        }
    }

    // ========================================================
    // FUNCIONES PARA FOTOS
    // ========================================================

    $uploadDir =
        __DIR__ . '/../uploads/';

    if (
        $error === '' &&
        !is_dir($uploadDir)
    ) {

        if (
            !mkdir(
                $uploadDir,
                0755,
                true
            )
        ) {

            $error =
                'No se pudo crear la carpeta uploads.';
        }
    }

    // ========================================================
    // FOTO FRONTAL
    // ========================================================

    if (
        $error === '' &&
        isset($_FILES['foto_frontal']) &&
        $_FILES['foto_frontal']['error']
            !== UPLOAD_ERR_NO_FILE
    ) {

        $file =
            $_FILES['foto_frontal'];

        if (
            $file['error']
            !== UPLOAD_ERR_OK
        ) {

            $error =
                'No se pudo cargar la foto frontal.';

        } elseif (
            $file['size']
            > 5 * 1024 * 1024
        ) {

            $error =
                'La foto frontal no puede superar 5 MB.';

        } else {

            $extension =
                strtolower(
                    pathinfo(
                        $file['name'],
                        PATHINFO_EXTENSION
                    )
                );

            $permitidas = [
                'jpg',
                'jpeg',
                'png',
                'webp'
            ];

            if (
                !in_array(
                    $extension,
                    $permitidas,
                    true
                )
            ) {

                $error =
                    'La foto frontal debe ser JPG, JPEG, PNG o WEBP.';

            } elseif (
                @getimagesize(
                    $file['tmp_name']
                ) === false
            ) {

                $error =
                    'La foto frontal no es una imagen válida.';

            } else {

                $nuevoNombre =
                    'libro_frontal_' .
                    bin2hex(
                        random_bytes(10)
                    ) .
                    '.' .
                    $extension;

                $destino =
                    $uploadDir .
                    $nuevoNombre;

                if (
                    move_uploaded_file(
                        $file['tmp_name'],
                        $destino
                    )
                ) {

                    // Eliminar anterior
                    if (!empty($frontal)) {

                        $anterior =
                            $uploadDir .
                            basename($frontal);

                        if (
                            is_file($anterior)
                        ) {
                            @unlink($anterior);
                        }

                        // Compatibilidad con carpeta antigua
                        $anteriorAntiguo =
                            __DIR__ .
                            '/../uploads/libros/' .
                            basename($frontal);

                        if (
                            is_file(
                                $anteriorAntiguo
                            )
                        ) {
                            @unlink(
                                $anteriorAntiguo
                            );
                        }
                    }

                    $frontal =
                        $nuevoNombre;

                } else {

                    $error =
                        'No se pudo guardar la foto frontal.';
                }
            }
        }
    }

    // ========================================================
    // FOTO TRASERA
    // ========================================================

    if (
        $error === '' &&
        isset($_FILES['foto_trasera']) &&
        $_FILES['foto_trasera']['error']
            !== UPLOAD_ERR_NO_FILE
    ) {

        $file =
            $_FILES['foto_trasera'];

        if (
            $file['error']
            !== UPLOAD_ERR_OK
        ) {

            $error =
                'No se pudo cargar la foto trasera.';

        } elseif (
            $file['size']
            > 5 * 1024 * 1024
        ) {

            $error =
                'La foto trasera no puede superar 5 MB.';

        } else {

            $extension =
                strtolower(
                    pathinfo(
                        $file['name'],
                        PATHINFO_EXTENSION
                    )
                );

            $permitidas = [
                'jpg',
                'jpeg',
                'png',
                'webp'
            ];

            if (
                !in_array(
                    $extension,
                    $permitidas,
                    true
                )
            ) {

                $error =
                    'La foto trasera debe ser JPG, JPEG, PNG o WEBP.';

            } elseif (
                @getimagesize(
                    $file['tmp_name']
                ) === false
            ) {

                $error =
                    'La foto trasera no es una imagen válida.';

            } else {

                $nuevoNombre =
                    'libro_trasera_' .
                    bin2hex(
                        random_bytes(10)
                    ) .
                    '.' .
                    $extension;

                $destino =
                    $uploadDir .
                    $nuevoNombre;

                if (
                    move_uploaded_file(
                        $file['tmp_name'],
                        $destino
                    )
                ) {

                    if (!empty($trasera)) {

                        $anterior =
                            $uploadDir .
                            basename($trasera);

                        if (
                            is_file($anterior)
                        ) {
                            @unlink($anterior);
                        }

                        // Compatibilidad carpeta antigua
                        $anteriorAntiguo =
                            __DIR__ .
                            '/../uploads/libros/' .
                            basename($trasera);

                        if (
                            is_file(
                                $anteriorAntiguo
                            )
                        ) {
                            @unlink(
                                $anteriorAntiguo
                            );
                        }
                    }

                    $trasera =
                        $nuevoNombre;

                } else {

                    $error =
                        'No se pudo guardar la foto trasera.';
                }
            }
        }
    }

    // ========================================================
    // ACTUALIZAR
    // ========================================================

    if ($error === '') {

        /*
         * Se conserva la columna foto para compatibilidad
         * con registros antiguos.
         *
         * foto         = frontal
         * foto_frontal = frontal
         * foto_trasera = trasera
         */

        $foto =
            !empty($frontal)
                ? $frontal
                : null;

        $sql = "
            UPDATE bibliografia
            SET
                codigo = ?,
                dewey = ?,
                clasificacion = ?,
                nombre = ?,
                autores = ?,
                editorial = ?,
                edicion = ?,
                anio = ?,
                isbn = ?,
                estado = ?,
                ubicacion = ?,
                fecha_ingreso = ?,
                idioma = ?,
                carrera_id = ?,
                catalogacion = ?,
                observaciones = ?,
                cantidad = ?,
                foto = ?,
                foto_frontal = ?,
                foto_trasera = ?,
                sede_id = ?,
                modificado_por = ?
            WHERE id = ?
              AND sede_id = ?
        ";

        $update =
            $conn->prepare($sql);

        if (!$update) {

            $error =
                'Error preparando la actualización: ' .
                $conn->error;

        } else {

            /*
             * 24 parámetros:
             *
             * 1  codigo          s
             * 2  dewey           s
             * 3  clasificacion   s
             * 4  nombre          s
             * 5  autores         s
             * 6  editorial       s
             * 7  edicion         s
             * 8  anio            i
             * 9  isbn            s
             * 10 estado          s
             * 11 ubicacion       s
             * 12 fecha_ingreso   s
             * 13 idioma          s
             * 14 carrera_id      i
             * 15 catalogacion    s
             * 16 observaciones   s
             * 17 cantidad        i
             * 18 foto            s
             * 19 foto_frontal    s
             * 20 foto_trasera    s
             * 21 sede_id         i
             * 22 modificado_por  i
             * 23 id              i
             * 24 sede_id WHERE   i
             */

            $update->bind_param(
                'sssssssisssssississsiiii',

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
                $usuario_id,
                $id,
                $sede_id
            );

            if ($update->execute()) {

                $_SESSION['success_msg'] =
                    'El libro se actualizó correctamente.';

                $update->close();

                header(
                    'Location: list.php'
                );

                exit();

            } else {

                $error =
                    'Error al actualizar: ' .
                    $update->error;

                $update->close();
            }
        }
    }
}

// ============================================================
// CARRERAS
// ============================================================

$carreras =
    $conn->query("
        SELECT id, nombre
        FROM carreras
        ORDER BY nombre ASC
    ");

// ============================================================
// VALORES PARA MOSTRAR
// ============================================================

$valorCodigo =
    $_POST['codigo']
    ?? $book['codigo']
    ?? '';

$valorDewey =
    $_POST['dewey']
    ?? $book['dewey']
    ?? '';

$valorClasificacion =
    $_POST['clasificacion']
    ?? $book['clasificacion']
    ?? '';

$valorNombre =
    $_POST['nombre']
    ?? $book['nombre']
    ?? '';

$valorAutores =
    $_POST['autores']
    ?? $book['autores']
    ?? '';

$valorEditorial =
    $_POST['editorial']
    ?? $book['editorial']
    ?? '';

$valorEdicion =
    $_POST['edicion']
    ?? $book['edicion']
    ?? '';

$valorAnio =
    $_POST['anio']
    ?? $book['anio']
    ?? '';

$valorIsbn =
    $_POST['isbn']
    ?? $book['isbn']
    ?? '';

$valorEstado =
    $_POST['estado']
    ?? $book['estado']
    ?? 'Disponible';

$valorFecha =
    $_POST['fecha_ingreso']
    ?? $book['fecha_ingreso']
    ?? '';

$valorIdioma =
    $_POST['idioma']
    ?? $book['idioma']
    ?? 'Español';

$valorCatalogacion =
    $_POST['catalogacion']
    ?? $book['catalogacion']
    ?? '';

$valorObservaciones =
    $_POST['observaciones']
    ?? $book['observaciones']
    ?? '';

$valorCantidad =
    $_POST['cantidad']
    ?? $book['cantidad']
    ?? 1;

$valorCarrera =
    $_POST['carrera_id']
    ?? $book['carrera_id']
    ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $estante_actual =
        strtoupper(
            trim(
                $_POST['estante'] ?? ''
            )
        );

    $nivel_actual =
        isset($_POST['nivel_estante'])
            ? (int)$_POST['nivel_estante']
            : -1;
}

// ============================================================
// HEADER
// ============================================================

include '../includes/header.php';

?>

<style>

.edit-book-card {
    max-width: 1150px;
    margin: auto;
}

.preview-img {
    width: 180px;
    height: 240px;
    object-fit: cover;
    border-radius: 12px;
    border: 1px solid #dee2e6;
    background: #f8f9fa;
    padding: 5px;
}

.location-box {
    background: #eef5ff;
    border-left: 5px solid #0d6efd;
    border-radius: 10px;
    padding: 15px;
    font-weight: 600;
}

.current-location {
    font-size: 18px;
    color: #0d6efd;
}

</style>


<div class="container-fluid py-4">

<div class="edit-book-card">

    <!-- =====================================================
         ENCABEZADO
    ====================================================== -->

    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">

        <div>

            <h1 class="fw-bold mb-1">

                <i class="fas fa-edit text-primary me-2"></i>

                Editar Libro

            </h1>

            <p class="text-muted mb-0">

                Biblioteca UPH - Sede Danlí

            </p>

        </div>


        <span class="badge bg-primary fs-6 p-2">

            <i class="fas fa-map-marker-alt me-1"></i>

            Danlí

        </span>

    </div>


    <!-- ERROR -->

    <?php if ($error): ?>

        <div class="alert alert-danger">

            <i class="fas fa-exclamation-triangle me-2"></i>

            <?= htmlspecialchars($error) ?>

        </div>

    <?php endif; ?>


    <div class="card shadow-sm border-0">

        <div class="card-header bg-primary text-white">

            <h5 class="mb-0">

                <i class="fas fa-book me-2"></i>

                Información del libro

            </h5>

        </div>


        <div class="card-body">

            <form
                method="post"
                enctype="multipart/form-data"
            >

                <input
                    type="hidden"
                    name="id"
                    value="<?= $id ?>"
                >


                <div class="row g-3">


                    <!-- CÓDIGO -->

                    <div class="col-md-4">

                        <label class="form-label fw-semibold">

                            Código *

                        </label>

                        <input
                            type="text"
                            name="codigo"
                            class="form-control"
                            required
                            value="<?= htmlspecialchars($valorCodigo) ?>"
                        >

                    </div>


                    <!-- DEWEY -->

                    <div class="col-md-4">

                        <label class="form-label">

                            Dewey

                        </label>

                        <input
                            type="text"
                            name="dewey"
                            class="form-control"
                            value="<?= htmlspecialchars($valorDewey) ?>"
                        >

                    </div>


                    <!-- CLASIFICACIÓN -->

                    <div class="col-md-4">

                        <label class="form-label">

                            Clasificación

                        </label>

                        <input
                            type="text"
                            name="clasificacion"
                            class="form-control"
                            value="<?= htmlspecialchars($valorClasificacion) ?>"
                        >

                    </div>


                    <!-- NOMBRE -->

                    <div class="col-12">

                        <label class="form-label fw-semibold">

                            Nombre del libro *

                        </label>

                        <input
                            type="text"
                            name="nombre"
                            class="form-control"
                            required
                            value="<?= htmlspecialchars($valorNombre) ?>"
                        >

                    </div>


                    <!-- AUTOR -->

                    <div class="col-md-6">

                        <label class="form-label">

                            Autor(es)

                        </label>

                        <input
                            type="text"
                            name="autores"
                            class="form-control"
                            value="<?= htmlspecialchars($valorAutores) ?>"
                        >

                    </div>


                    <!-- EDITORIAL -->

                    <div class="col-md-6">

                        <label class="form-label">

                            Editorial

                        </label>

                        <input
                            type="text"
                            name="editorial"
                            class="form-control"
                            value="<?= htmlspecialchars($valorEditorial) ?>"
                        >

                    </div>


                    <!-- EDICIÓN -->

                    <div class="col-md-4">

                        <label class="form-label">

                            Edición

                        </label>

                        <input
                            type="text"
                            name="edicion"
                            class="form-control"
                            value="<?= htmlspecialchars($valorEdicion) ?>"
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
                            min="1000"
                            max="2100"
                            value="<?= htmlspecialchars($valorAnio) ?>"
                        >

                    </div>


                    <!-- ISBN -->

                    <div class="col-md-4">

                        <label class="form-label">

                            ISBN

                        </label>

                        <input
                            type="text"
                            name="isbn"
                            class="form-control"
                            value="<?= htmlspecialchars($valorIsbn) ?>"
                        >

                    </div>


                    <!-- ESTADO -->

                    <div class="col-md-3">

                        <label class="form-label fw-semibold">

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

                            <?php foreach ($estados as $estado): ?>

                                <option
                                    value="<?= htmlspecialchars($estado) ?>"
                                    <?= $valorEstado === $estado
                                        ? 'selected'
                                        : '' ?>
                                >

                                    <?= htmlspecialchars($estado) ?>

                                </option>

                            <?php endforeach; ?>

                        </select>

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
                            value="<?= htmlspecialchars($valorFecha) ?>"
                        >

                    </div>


                    <!-- CANTIDAD -->

                    <div class="col-md-3">

                        <label class="form-label fw-semibold">

                            Cantidad *

                        </label>

                        <input
                            type="number"
                            name="cantidad"
                            class="form-control"
                            min="1"
                            required
                            value="<?= (int)$valorCantidad ?>"
                        >

                    </div>


                    <!-- IDIOMA -->

                    <div class="col-md-3">

                        <label class="form-label">

                            Idioma

                        </label>

                        <input
                            type="text"
                            name="idioma"
                            class="form-control"
                            value="<?= htmlspecialchars($valorIdioma) ?>"
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

                                <?php while (
                                    $c =
                                    $carreras->fetch_assoc()
                                ): ?>

                                    <option
                                        value="<?= (int)$c['id'] ?>"
                                        <?= (
                                            (string)$valorCarrera ===
                                            (string)$c['id']
                                        )
                                            ? 'selected'
                                            : '' ?>
                                    >

                                        <?= htmlspecialchars(
                                            $c['nombre']
                                        ) ?>

                                    </option>

                                <?php endwhile; ?>

                            <?php endif; ?>

                        </select>

                    </div>


                    <!-- SEDE -->

                    <div class="col-md-6">

                        <label class="form-label">

                            Sede

                        </label>

                        <input
                            type="text"
                            class="form-control"
                            value="4 - Danlí"
                            readonly
                        >

                    </div>


                    <!-- =================================================
                         UBICACIÓN
                    ================================================== -->

                    <div class="col-12">

                        <hr>

                        <h5 class="fw-bold">

                            <i class="fas fa-layer-group text-primary me-2"></i>

                            Ubicación física del libro

                        </h5>

                        <p class="text-muted">

                            Seleccione en qué estante y nivel se encuentra
                            físicamente el libro.

                        </p>

                    </div>


                    <!-- ESTANTE -->

                    <div class="col-md-6">

                        <label class="form-label fw-semibold">

                            Estante *

                        </label>

                        <select
                            name="estante"
                            id="estante"
                            class="form-select"
                            required
                        >

                            <option value="">

                                Seleccione el estante

                            </option>


                            <optgroup label="Estantes A">

                                <?php for (
                                    $i = 1;
                                    $i <= 5;
                                    $i++
                                ): ?>

                                    <?php
                                    $valorEstante =
                                        'A-' . $i;
                                    ?>

                                    <option
                                        value="<?= $valorEstante ?>"
                                        <?= $estante_actual ===
                                            $valorEstante
                                            ? 'selected'
                                            : '' ?>
                                    >

                                        Estante <?= $valorEstante ?>

                                    </option>

                                <?php endfor; ?>

                            </optgroup>


                            <optgroup label="Estantes B">

                                <?php for (
                                    $i = 1;
                                    $i <= 5;
                                    $i++
                                ): ?>

                                    <?php
                                    $valorEstante =
                                        'B-' . $i;
                                    ?>

                                    <option
                                        value="<?= $valorEstante ?>"
                                        <?= $estante_actual ===
                                            $valorEstante
                                            ? 'selected'
                                            : '' ?>
                                    >

                                        Estante <?= $valorEstante ?>

                                    </option>

                                <?php endfor; ?>

                            </optgroup>

                        </select>

                    </div>


                    <!-- NIVEL -->

                    <div class="col-md-6">

                        <label class="form-label fw-semibold">

                            Nivel *

                        </label>

                        <select
                            name="nivel_estante"
                            id="nivel_estante"
                            class="form-select"
                            required
                        >

                            <option value="">

                                Seleccione el nivel

                            </option>

                            <?php for (
                                $n = 0;
                                $n <= 4;
                                $n++
                            ): ?>

                                <option
                                    value="<?= $n ?>"
                                    <?= (
                                        (string)$nivel_actual ===
                                        (string)$n
                                    )
                                        ? 'selected'
                                        : '' ?>
                                >

                                    Nivel <?= $n ?>

                                </option>

                            <?php endfor; ?>

                        </select>

                    </div>


                    <!-- UBICACIÓN GENERADA -->

                    <div class="col-12">

                        <div class="location-box">

                            <i class="fas fa-map-marker-alt text-primary me-2"></i>

                            <strong>Ubicación:</strong>

                            <span
                                id="ubicacionTexto"
                                class="current-location"
                            >

                                <?= $ubicacion_actual !== ''
                                    ? htmlspecialchars($ubicacion_actual)
                                    : 'Seleccione estante y nivel' ?>

                            </span>

                        </div>

                    </div>


                    <!-- =================================================
                         FOTO FRONTAL
                    ================================================== -->

                    <div class="col-md-6">

                        <label class="form-label fw-semibold">

                            📕 Foto frontal / portada

                        </label>

                        <input
                            type="file"
                            name="foto_frontal"
                            id="foto_frontal"
                            class="form-control"
                            accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                        >

                        <small class="text-muted">

                            Máximo 5 MB.

                        </small>


                        <div
                            id="previewFrontal"
                            class="mt-3"
                        >

                            <?php if (!empty($frontal)): ?>

                                <img
                                    src="../uploads/<?= htmlspecialchars(
                                        rawurlencode(
                                            basename($frontal)
                                        )
                                    ) ?>"
                                    class="preview-img"
                                    alt="Foto frontal"
                                    onerror="this.style.display='none';"
                                >

                                <div class="small text-muted mt-1">

                                    Foto frontal actual.

                                </div>

                            <?php else: ?>

                                <div class="alert alert-light">

                                    No hay foto frontal registrada.

                                </div>

                            <?php endif; ?>

                        </div>

                    </div>


                    <!-- =================================================
                         FOTO TRASERA
                    ================================================== -->

                    <div class="col-md-6">

                        <label class="form-label fw-semibold">

                            📗 Foto trasera / contraportada

                        </label>

                        <input
                            type="file"
                            name="foto_trasera"
                            id="foto_trasera"
                            class="form-control"
                            accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                        >

                        <small class="text-muted">

                            Máximo 5 MB.

                        </small>


                        <div
                            id="previewTrasera"
                            class="mt-3"
                        >

                            <?php if (!empty($trasera)): ?>

                                <img
                                    src="../uploads/<?= htmlspecialchars(
                                        rawurlencode(
                                            basename($trasera)
                                        )
                                    ) ?>"
                                    class="preview-img"
                                    alt="Foto trasera"
                                    onerror="this.style.display='none';"
                                >

                                <div class="small text-muted mt-1">

                                    Foto trasera actual.

                                </div>

                            <?php else: ?>

                                <div class="alert alert-light">

                                    No hay foto trasera registrada.

                                </div>

                            <?php endif; ?>

                        </div>

                    </div>


                    <!-- CATALOGACIÓN -->

                    <div class="col-12">

                        <label class="form-label">

                            Catalogación

                        </label>

                        <input
                            type="text"
                            name="catalogacion"
                            class="form-control"
                            value="<?= htmlspecialchars($valorCatalogacion) ?>"
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
                        ><?= htmlspecialchars($valorObservaciones) ?></textarea>

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

                        Guardar Cambios

                    </button>

                </div>

            </form>

        </div>

    </div>

</div>

</div>


<script>

// ============================================================
// ACTUALIZAR UBICACIÓN
// ============================================================

function actualizarUbicacion() {

    const estante =
        document.getElementById(
            'estante'
        ).value;

    const nivel =
        document.getElementById(
            'nivel_estante'
        ).value;

    const texto =
        document.getElementById(
            'ubicacionTexto'
        );

    if (
        estante !== '' &&
        nivel !== ''
    ) {

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
// PREVISUALIZAR FOTO
// ============================================================

function previsualizarFoto(
    input,
    contenedor
) {

    const archivo =
        input.files[0];

    const div =
        document.getElementById(
            contenedor
        );

    if (!archivo) {
        return;
    }

    // Tamaño
    if (
        archivo.size >
        5 * 1024 * 1024
    ) {

        alert(
            'La imagen no puede superar los 5 MB.'
        );

        input.value = '';

        return;
    }

    // Tipo
    const tipos = [
        'image/jpeg',
        'image/png',
        'image/webp'
    ];

    if (
        !tipos.includes(
            archivo.type
        )
    ) {

        alert(
            'Seleccione una imagen JPG, PNG o WEBP.'
        );

        input.value = '';

        return;
    }

    const lector =
        new FileReader();

    lector.onload =
        function(e) {

            div.innerHTML = '';

            const img =
                document.createElement(
                    'img'
                );

            img.src =
                e.target.result;

            img.className =
                'preview-img';

            img.alt =
                'Vista previa';

            div.appendChild(img);

            const texto =
                document.createElement(
                    'div'
                );

            texto.className =
                'small text-success mt-1';

            texto.textContent =
                'Nueva imagen seleccionada.';

            div.appendChild(texto);
        };

    lector.readAsDataURL(
        archivo
    );
}


// FOTO FRONTAL

document
    .getElementById(
        'foto_frontal'
    )
    .addEventListener(
        'change',
        function() {

            previsualizarFoto(
                this,
                'previewFrontal'
            );

        }
    );


// FOTO TRASERA

document
    .getElementById(
        'foto_trasera'
    )
    .addEventListener(
        'change',
        function() {

            previsualizarFoto(
                this,
                'previewTrasera'
            );

        }
    );


// Inicializar ubicación

actualizarUbicacion();

</script>


<?php

include '../includes/footer.php';

?>