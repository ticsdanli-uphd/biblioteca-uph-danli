<?php

include '../includes/session.php';
include '../config/db.php';


// =====================================================
// VERIFICAR SESIÓN
// =====================================================

if (!isset($_SESSION['user_id'])) {

    header('Location: ../login.php');
    exit();

}


// =====================================================
// CONFIGURACIÓN
// =====================================================

// Danlí
$sede_id = 4;

$nombre_sede = 'Danli';


// =====================================================
// VARIABLES
// =====================================================

$error = '';
$success = '';


// =====================================================
// VERIFICAR USUARIO DE LA SESIÓN
// =====================================================

$user_id = intval($_SESSION['user_id']);

$stmtUsuario = $conn->prepare(
    "SELECT id, username, role
     FROM usuarios
     WHERE id = ?
     LIMIT 1"
);

$stmtUsuario->bind_param(
    "i",
    $user_id
);

$stmtUsuario->execute();

$resultUsuario = $stmtUsuario->get_result();


// Si el usuario de sesión no existe
if ($resultUsuario->num_rows === 0) {

    $stmtUsuario->close();

    session_destroy();

    header('Location: ../login.php');
    exit();

}


$usuarioSesion =
    $resultUsuario->fetch_assoc();

$stmtUsuario->close();


// =====================================================
// CARRERAS
// =====================================================

$sqlCarreras = "
    SELECT id, nombre
    FROM carreras
    ORDER BY nombre ASC
";

$resultCarreras =
    $conn->query($sqlCarreras);


// =====================================================
// PROCESAR FORMULARIO
// =====================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {


    // =================================================
    // RECIBIR DATOS
    // =================================================

    $codigo =
        trim($_POST['codigo'] ?? '');

    $dewey =
        trim($_POST['dewey'] ?? '');

    $clasificacion =
        trim($_POST['clasificacion'] ?? '');

    $nombre =
        trim($_POST['nombre'] ?? '');

    $autor =
        trim($_POST['autor'] ?? '');

    $editorial =
        trim($_POST['editorial'] ?? '');

    $edicion =
        trim($_POST['edicion'] ?? '');

    $anio =
        trim($_POST['anio'] ?? '');

    $isbn =
        trim($_POST['isbn'] ?? '');

    $estado =
        trim($_POST['estado'] ?? 'Disponible');

    $ubicacion =
        trim($_POST['ubicacion'] ?? '');

    $fecha_ingreso =
        trim($_POST['fecha_ingreso'] ?? date('Y-m-d'));

    $idioma =
        trim($_POST['idioma'] ?? '');

    $carrera_id =
        !empty($_POST['carrera_id'])
            ? intval($_POST['carrera_id'])
            : null;

    $cantidad =
        isset($_POST['cantidad'])
            ? intval($_POST['cantidad'])
            : 1;


    // =================================================
    // VALIDACIONES
    // =================================================

    if (
        empty($codigo) ||
        empty($nombre)
    ) {

        $error =
            "El código y el nombre del libro son obligatorios.";

    }


    elseif ($cantidad < 1) {

        $error =
            "La cantidad debe ser mayor que 0.";

    }


    elseif (!empty($anio) && !is_numeric($anio)) {

        $error =
            "El año ingresado no es válido.";

    }


    else {


        // =============================================
        // VERIFICAR CÓDIGO DUPLICADO
        // =============================================

        $stmtExiste = $conn->prepare(
            "SELECT id
             FROM bibliografia
             WHERE codigo = ?
             LIMIT 1"
        );

        $stmtExiste->bind_param(
            "s",
            $codigo
        );

        $stmtExiste->execute();

        $resultadoExiste =
            $stmtExiste->get_result();


        if ($resultadoExiste->num_rows > 0) {

            $error =
                "El código del libro ya existe.";

            $stmtExiste->close();

        } else {

            $stmtExiste->close();


            // =========================================
            // INSERTAR LIBRO
            // =========================================

            /*
             * IMPORTANTE:
             *
             * ingresado_por recibe el ID REAL
             * del usuario que inició sesión.
             *
             * NO se coloca 0.
             */

            // La columna real de la BD es `autores`.
            // El formulario mantiene name="autor" para no cambiar la interfaz.
            $sql = "
                INSERT INTO bibliografia (

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
                    cantidad,
                    sede_id,
                    ingresado_por

                )
                VALUES (

                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?

                )
            ";


            $stmt =
                $conn->prepare($sql);


            if (!$stmt) {

                $error =
                    "Error al preparar el registro: "
                    . $conn->error;

            } else {


                /*
                 * Para carrera_id permitimos NULL.
                 *
                 * Si no se seleccionó carrera,
                 * utilizamos NULL.
                 */

                $carreraValor =
                    $carrera_id;


                /*
                 * Año:
                 *
                 * Si está vacío enviamos NULL.
                 */

                $anioValor =
                    !empty($anio)
                        ? intval($anio)
                        : null;


                /*
                 * Tipos:
                 *
                 * 13 campos string
                 * carrera_id = integer
                 * cantidad = integer
                 * sede_id = integer
                 * ingresado_por = integer
                 */

                $stmt->bind_param(
                    "sssssssisssssiiii",
                    $codigo,
                    $dewey,
                    $clasificacion,
                    $nombre,
                    $autor,
                    $editorial,
                    $edicion,
                    $anioValor,
                    $isbn,
                    $estado,
                    $ubicacion,
                    $fecha_ingreso,
                    $idioma,
                    $carreraValor,
                    $cantidad,
                    $sede_id,
                    $user_id
                );


                /*
                 * IMPORTANTE:
                 *
                 * bind_param no permite enviar NULL
                 * directamente de la misma manera en
                 * algunas configuraciones.
                 *
                 * Si carrera/año son NULL, se ejecuta
                 * igualmente cuando la columna permite NULL.
                 */

                if ($stmt->execute()) {


                    $success =
                        "Libro registrado correctamente.";


                    /*
                     * Limpiar los valores del formulario
                     */

                    $codigo = '';
                    $dewey = '';
                    $clasificacion = '';
                    $nombre = '';
                    $autor = '';
                    $editorial = '';
                    $edicion = '';
                    $anio = '';
                    $isbn = '';
                    $estado = 'Disponible';
                    $ubicacion = '';
                    $fecha_ingreso = date('Y-m-d');
                    $idioma = '';
                    $carrera_id = null;
                    $cantidad = 1;


                } else {

                    $error =
                        "Error al registrar el libro: "
                        . $stmt->error;

                }


                $stmt->close();

            }

        }

    }

}


include '../includes/header.php';

?>


<style>

/* =====================================================
   CONTENEDOR
===================================================== */

.book-form-container {

    max-width: 1050px;

    margin: 0 auto;

}


/* =====================================================
   ENCABEZADO
===================================================== */

.book-header {

    background: #ffffff;

    border-radius: 18px;

    padding: 25px 30px;

    margin-bottom: 20px;

    box-shadow:
        0 8px 25px rgba(0,0,0,.07);

}

.book-header h2 {

    color: #3159d8;

    font-weight: 700;

    margin-bottom: 5px;

}

.book-header p {

    color: #718096;

    margin: 0;

}


/* =====================================================
   TARJETA
===================================================== */

.book-card {

    background: #ffffff;

    border-radius: 18px;

    box-shadow:
        0 10px 30px rgba(0,0,0,.08);

    overflow: hidden;

}

.book-card-header {

    background: #3159d8;

    color: #ffffff;

    padding: 17px 25px;

    font-size: 18px;

    font-weight: 600;

}

.book-card-body {

    padding: 28px;

}


/* =====================================================
   CAMPOS
===================================================== */

.form-label {

    font-weight: 600;

    color: #27364b;

}

.form-control,
.form-select {

    border-radius: 10px;

    border: 1px solid #dce3ed;

    min-height: 46px;

}

.form-control:focus,
.form-select:focus {

    border-color: #3159d8;

    box-shadow:
        0 0 0 .2rem rgba(49,89,216,.12);

}


/* =====================================================
   SEDE
===================================================== */

.sede-box {

    background: #eef8f3;

    border: 1px solid #c8ebd9;

    color: #16865a;

    border-radius: 10px;

    padding: 12px 15px;

    font-weight: 600;

}


/* =====================================================
   BOTONES
===================================================== */

.btn-guardar {

    background: #3159d8;

    border-color: #3159d8;

    color: white;

    font-weight: 600;

    padding: 11px 22px;

    border-radius: 10px;

}

.btn-guardar:hover {

    background: #2649bd;

    border-color: #2649bd;

    color: white;

}

.btn-cancelar {

    border-radius: 10px;

    padding: 11px 22px;

    font-weight: 600;

}


/* =====================================================
   RESPONSIVE
===================================================== */

@media (max-width: 700px) {

    .book-card-body {

        padding: 20px;

    }

    .book-header {

        padding: 20px;

    }

}

</style>


<div class="book-form-container">


    <!-- =================================================
         ENCABEZADO
    ================================================== -->

    <div class="book-header">

        <h2>

            <i class="fas fa-book-medical me-2"></i>

            Registrar Nuevo Libro

        </h2>

        <p>

            Registra el material bibliográfico
            correspondiente a la sede Danlí.

        </p>

    </div>


    <!-- =================================================
         MENSAJES
    ================================================== -->

    <?php if (!empty($error)): ?>

        <div
            class="alert alert-danger alert-dismissible fade show"
            role="alert"
        >

            <i class="fas fa-exclamation-triangle me-2"></i>

            <?php
            echo htmlspecialchars(
                $error,
                ENT_QUOTES,
                'UTF-8'
            );
            ?>

            <button
                type="button"
                class="btn-close"
                data-bs-dismiss="alert"
            ></button>

        </div>

    <?php endif; ?>


    <?php if (!empty($success)): ?>

        <div
            class="alert alert-success alert-dismissible fade show"
            role="alert"
        >

            <i class="fas fa-check-circle me-2"></i>

            <?php
            echo htmlspecialchars(
                $success,
                ENT_QUOTES,
                'UTF-8'
            );
            ?>

            <button
                type="button"
                class="btn-close"
                data-bs-dismiss="alert"
            ></button>

        </div>

    <?php endif; ?>


    <!-- =================================================
         FORMULARIO
    ================================================== -->

    <div class="book-card">


        <div class="book-card-header">

            <i class="fas fa-book me-2"></i>

            Información del Libro

        </div>


        <div class="book-card-body">


            <form
                method="post"
                action=""
            >


                <!-- =====================================
                     CÓDIGO / DEWEY
                ====================================== -->

                <div class="row">


                    <div class="col-md-6 mb-3">

                        <label
                            for="codigo"
                            class="form-label"
                        >

                            Código *

                        </label>

                        <input
                            type="text"
                            name="codigo"
                            id="codigo"
                            class="form-control"
                            value="<?php
                                echo htmlspecialchars(
                                    $codigo ?? '',
                                    ENT_QUOTES,
                                    'UTF-8'
                                );
                            ?>"
                            placeholder="Ej. UPH-04-BLGM-000001"
                            required
                        >

                    </div>


                    <div class="col-md-6 mb-3">

                        <label
                            for="dewey"
                            class="form-label"
                        >

                            Clasificación Dewey

                        </label>

                        <input
                            type="text"
                            name="dewey"
                            id="dewey"
                            class="form-control"
                            value="<?php
                                echo htmlspecialchars(
                                    $dewey ?? '',
                                    ENT_QUOTES,
                                    'UTF-8'
                                );
                            ?>"
                            placeholder="Ej. 100"
                        >

                    </div>


                </div>


                <!-- =====================================
                     CLASIFICACIÓN
                ====================================== -->

                <div class="mb-3">

                    <label
                        for="clasificacion"
                        class="form-label"
                    >

                        Clasificación

                    </label>

                    <input
                        type="text"
                        name="clasificacion"
                        id="clasificacion"
                        class="form-control"
                        value="<?php
                            echo htmlspecialchars(
                                $clasificacion ?? '',
                                ENT_QUOTES,
                                'UTF-8'
                            );
                        ?>"
                        placeholder="Ej. Generalidades"
                    >

                </div>


                <!-- =====================================
                     NOMBRE
                ====================================== -->

                <div class="mb-3">

                    <label
                        for="nombre"
                        class="form-label"
                    >

                        Nombre del Libro *

                    </label>

                    <input
                        type="text"
                        name="nombre"
                        id="nombre"
                        class="form-control"
                        value="<?php
                            echo htmlspecialchars(
                                $nombre ?? '',
                                ENT_QUOTES,
                                'UTF-8'
                            );
                        ?>"
                        placeholder="Nombre del libro"
                        required
                    >

                </div>


                <!-- =====================================
                     AUTOR / EDITORIAL
                ====================================== -->

                <div class="row">


                    <div class="col-md-6 mb-3">

                        <label
                            for="autor"
                            class="form-label"
                        >

                            Autor(es)

                        </label>

                        <input
                            type="text"
                            name="autor"
                            id="autor"
                            class="form-control"
                            value="<?php
                                echo htmlspecialchars(
                                    $autor ?? '',
                                    ENT_QUOTES,
                                    'UTF-8'
                                );
                            ?>"
                            placeholder="Autor o autores"
                        >

                    </div>


                    <div class="col-md-6 mb-3">

                        <label
                            for="editorial"
                            class="form-label"
                        >

                            Editorial

                        </label>

                        <input
                            type="text"
                            name="editorial"
                            id="editorial"
                            class="form-control"
                            value="<?php
                                echo htmlspecialchars(
                                    $editorial ?? '',
                                    ENT_QUOTES,
                                    'UTF-8'
                                );
                            ?>"
                            placeholder="Editorial"
                        >

                    </div>


                </div>


                <!-- =====================================
                     EDICIÓN / AÑO
                ====================================== -->

                <div class="row">


                    <div class="col-md-6 mb-3">

                        <label
                            for="edicion"
                            class="form-label"
                        >

                            Edición

                        </label>

                        <input
                            type="text"
                            name="edicion"
                            id="edicion"
                            class="form-control"
                            value="<?php
                                echo htmlspecialchars(
                                    $edicion ?? '',
                                    ENT_QUOTES,
                                    'UTF-8'
                                );
                            ?>"
                            placeholder="Ej. Cuarta Reimpresión"
                        >

                    </div>


                    <div class="col-md-6 mb-3">

                        <label
                            for="anio"
                            class="form-label"
                        >

                            Año

                        </label>

                        <input
                            type="number"
                            name="anio"
                            id="anio"
                            class="form-control"
                            value="<?php
                                echo htmlspecialchars(
                                    $anio ?? '',
                                    ENT_QUOTES,
                                    'UTF-8'
                                );
                            ?>"
                            placeholder="Ej. 1994"
                            min="1000"
                            max="2100"
                        >

                    </div>


                </div>


                <!-- =====================================
                     ISBN / IDIOMA
                ====================================== -->

                <div class="row">


                    <div class="col-md-6 mb-3">

                        <label
                            for="isbn"
                            class="form-label"
                        >

                            ISBN

                        </label>

                        <input
                            type="text"
                            name="isbn"
                            id="isbn"
                            class="form-control"
                            value="<?php
                                echo htmlspecialchars(
                                    $isbn ?? '',
                                    ENT_QUOTES,
                                    'UTF-8'
                                );
                            ?>"
                            placeholder="ISBN"
                        >

                    </div>


                    <div class="col-md-6 mb-3">

                        <label
                            for="idioma"
                            class="form-label"
                        >

                            Idioma

                        </label>

                        <input
                            type="text"
                            name="idioma"
                            id="idioma"
                            class="form-control"
                            value="<?php
                                echo htmlspecialchars(
                                    $idioma ?? '',
                                    ENT_QUOTES,
                                    'UTF-8'
                                );
                            ?>"
                            placeholder="Ej. Español"
                        >

                    </div>


                </div>


                <!-- =====================================
                     ESTADO / CANTIDAD
                ====================================== -->

                <div class="row">


                    <div class="col-md-6 mb-3">

                        <label
                            for="estado"
                            class="form-label"
                        >

                            Estado

                        </label>

                        <select
                            name="estado"
                            id="estado"
                            class="form-select"
                        >

                            <option
                                value="Disponible"
                                <?php
                                echo (
                                    ($estado ?? 'Disponible')
                                    === 'Disponible'
                                )
                                    ? 'selected'
                                    : '';
                                ?>
                            >

                                Disponible

                            </option>

                            <option
                                value="Prestado"
                                <?php
                                echo (
                                    ($estado ?? '')
                                    === 'Prestado'
                                )
                                    ? 'selected'
                                    : '';
                                ?>
                            >

                                Prestado

                            </option>

                            <option
                                value="Deteriorado"
                                <?php
                                echo (
                                    ($estado ?? '')
                                    === 'Deteriorado'
                                )
                                    ? 'selected'
                                    : '';
                                ?>
                            >

                                Dañado

                            </option>

                            <option
                                value="Baja"
                                <?php
                                echo (
                                    ($estado ?? '')
                                    === 'Baja'
                                )
                                    ? 'selected'
                                    : '';
                                ?>
                            >

                                Baja

                            </option>

                        </select>

                    </div>


                    <div class="col-md-6 mb-3">

                        <label
                            for="cantidad"
                            class="form-label"
                        >

                            Cantidad *

                        </label>

                        <input
                            type="number"
                            name="cantidad"
                            id="cantidad"
                            class="form-control"
                            value="<?php
                                echo (int)(
                                    $cantidad ?? 1
                                );
                            ?>"
                            min="1"
                            required
                        >

                    </div>


                </div>


                <!-- =====================================
                     UBICACIÓN / FECHA
                ====================================== -->

                <div class="row">


                    <div class="col-md-6 mb-3">

                        <label
                            for="ubicacion"
                            class="form-label"
                        >

                            Ubicación

                        </label>

                        <input
                            type="text"
                            name="ubicacion"
                            id="ubicacion"
                            class="form-control"
                            value="<?php
                                echo htmlspecialchars(
                                    $ubicacion ?? '',
                                    ENT_QUOTES,
                                    'UTF-8'
                                );
                            ?>"
                            placeholder="Ej. Estante A-01"
                        >

                    </div>


                    <div class="col-md-6 mb-3">

                        <label
                            for="fecha_ingreso"
                            class="form-label"
                        >

                            Fecha de ingreso

                        </label>

                        <input
                            type="date"
                            name="fecha_ingreso"
                            id="fecha_ingreso"
                            class="form-control"
                            value="<?php
                                echo htmlspecialchars(
                                    $fecha_ingreso
                                        ?? date('Y-m-d'),
                                    ENT_QUOTES,
                                    'UTF-8'
                                );
                            ?>"
                        >

                    </div>


                </div>


                <!-- =====================================
                     CARRERA
                ====================================== -->

                <div class="mb-3">

                    <label
                        for="carrera_id"
                        class="form-label"
                    >

                        Carrera

                    </label>

                    <select
                        name="carrera_id"
                        id="carrera_id"
                        class="form-select"
                    >

                        <option value="">

                            Seleccione una carrera

                        </option>


                        <?php if ($resultCarreras): ?>


                            <?php while (
                                $carrera =
                                $resultCarreras->fetch_assoc()
                            ): ?>

                                <option
                                    value="<?php
                                        echo (int)
                                            $carrera['id'];
                                    ?>"
                                    <?php
                                    echo (
                                        isset(
                                            $carrera_id
                                        )
                                        &&
                                        $carrera_id ==
                                            $carrera['id']
                                    )
                                        ? 'selected'
                                        : '';
                                    ?>
                                >

                                    <?php

                                    echo htmlspecialchars(
                                        $carrera['nombre'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    );

                                    ?>

                                </option>

                            <?php endwhile; ?>


                        <?php endif; ?>


                    </select>

                </div>


                <!-- =====================================
                     SEDE
                ====================================== -->

                <div class="mb-4">

                    <label
                        class="form-label"
                    >

                        Sede

                    </label>


                    <div class="sede-box">

                        <i
                            class="fas fa-map-marker-alt me-2"
                        ></i>

                        Danli

                        <input
                            type="hidden"
                            name="sede_id"
                            value="4"
                        >

                    </div>

                </div>


                <!-- =====================================
                     USUARIO REGISTRADOR
                ====================================== -->

                <div class="mb-4">

                    <label
                        class="form-label"
                    >

                        Registrado por

                    </label>


                    <div class="sede-box">

                        <i
                            class="fas fa-user me-2"
                        ></i>

                        <?php

                        echo htmlspecialchars(
                            $usuarioSesion['username'],
                            ENT_QUOTES,
                            'UTF-8'
                        );

                        ?>

                    </div>

                </div>


                <!-- =====================================
                     BOTONES
                ====================================== -->

                <div
                    class="d-flex justify-content-between align-items-center flex-wrap gap-2"
                >


                    <a
                        href="list.php"
                        class="btn btn-secondary btn-cancelar"
                    >

                        <i
                            class="fas fa-arrow-left me-1"
                        ></i>

                        Cancelar

                    </a>


                    <button
                        type="submit"
                        class="btn btn-guardar"
                    >

                        <i
                            class="fas fa-save me-1"
                        ></i>

                        Registrar Libro

                    </button>


                </div>


            </form>


        </div>

    </div>


</div>


<?php

include '../includes/footer.php';

?>