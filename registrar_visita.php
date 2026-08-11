<?php

include 'includes/session.php';
include 'config/db.php';


// ======================================================
// CONFIGURACIÓN
// ======================================================

$sede_nombre = "Danlí";

$error = "";


// ======================================================
// PROCESAR FORMULARIO
// ======================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nombre_alumno = trim(
        $_POST['nombre_alumno'] ?? ''
    );

    $observaciones = trim(
        $_POST['observaciones'] ?? ''
    );

    $user_id = intval(
        $_SESSION['user_id']
    );


    // Institución
    $institucion_id = null;

    if (
        isset($_POST['institucion_id']) &&
        $_POST['institucion_id'] !== ''
    ) {

        $institucion_id =
            intval($_POST['institucion_id']);

    }


    // Carrera
    $carrera_id = null;

    if (
        isset($_POST['carrera_id']) &&
        $_POST['carrera_id'] !== ''
    ) {

        $carrera_id =
            intval($_POST['carrera_id']);

    }


    // Visitante externo
    $es_externo =
        isset($_POST['es_externo'])
        ? 1
        : 0;


    // ==================================================
    // VALIDACIONES
    // ==================================================

    if ($nombre_alumno === '') {

        $error =
            "Debe ingresar el nombre del visitante.";

    } elseif (
        $es_externo === 1 &&
        empty($institucion_id)
    ) {

        $error =
            "Debe seleccionar la institución del visitante externo.";

    } else {


        // ==============================================
        // INSERTAR VISITA
        // ==============================================

        $sql = "
            INSERT INTO registro_visitas
            (
                bibliografia_id,
                user_id,
                tipo,
                observaciones,
                nombre_alumno,
                institucion_id,
                carrera_id,
                es_externo
            )
            VALUES
            (
                NULL,
                ?,
                'visita',
                ?,
                ?,
                ?,
                ?,
                ?
            )
        ";


        $stmt = $conn->prepare($sql);


        if (!$stmt) {

            $error =
                "Error preparando el registro: "
                . $conn->error;

        } else {


            $stmt->bind_param(
                "issiii",
                $user_id,
                $observaciones,
                $nombre_alumno,
                $institucion_id,
                $carrera_id,
                $es_externo
            );


            if ($stmt->execute()) {

                $_SESSION['success_msg'] =
                    "La visita de "
                    . $nombre_alumno
                    . " se registró correctamente.";

                $stmt->close();

                header(
                    "Location: registrar_visita.php"
                );

                exit();

            } else {

                $error =
                    "Error al registrar la visita: "
                    . $stmt->error;

                $stmt->close();

            }

        }

    }

}


// ======================================================
// CONSULTAR INSTITUCIONES
// ======================================================

$sqlInstituciones = "
    SELECT id, nombre
    FROM instituciones_externas
    ORDER BY nombre ASC
";

$resultInstituciones =
    $conn->query(
        $sqlInstituciones
    );


// ======================================================
// CONSULTAR CARRERAS
// ======================================================

$sqlCarreras = "
    SELECT id, nombre
    FROM carreras
    ORDER BY nombre ASC
";

$resultCarreras =
    $conn->query(
        $sqlCarreras
    );


// ======================================================
// HEADER
// ======================================================

include 'includes/header.php';

?>


<div class="container-fluid py-4">


    <!-- ==================================================
         ENCABEZADO
    ================================================== -->

    <div class="visita-header mb-4">

        <div>

            <h1>

                <i
                    class="
                        fas
                        fa-users
                        me-2
                    "
                ></i>

                Registrar Visita

            </h1>

            <p>

                Registra la visita de estudiantes y
                visitantes de la Biblioteca UPH.

            </p>

        </div>


        <div class="sede-badge">

            <i
                class="
                    fas
                    fa-map-marker-alt
                    me-1
                "
            ></i>

            Biblioteca UPH Danlí

        </div>

    </div>



    <!-- ==================================================
         MENSAJE DE ERROR
    ================================================== -->

    <?php if (!empty($error)): ?>

        <div
            class="
                alert
                alert-danger
                alert-dismissible
                fade
                show
                shadow-sm
            "
        >

            <i
                class="
                    fas
                    fa-exclamation-circle
                    me-2
                "
            ></i>

            <?php
            echo htmlspecialchars(
                $error
            );
            ?>


            <button
                type="button"
                class="btn-close"
                data-bs-dismiss="alert"
            ></button>

        </div>

    <?php endif; ?>



    <!-- ==================================================
         FORMULARIO
    ================================================== -->

    <div class="row justify-content-center">

        <div class="col-xl-9 col-lg-10">


            <div class="card visita-card shadow-sm">


                <!-- CARD HEADER -->

                <div class="card-header visita-card-header">

                    <div>

                        <h4>

                            <i
                                class="
                                    fas
                                    fa-clipboard-list
                                    me-2
                                "
                            ></i>

                            Datos de la visita

                        </h4>

                        <small>

                            Complete la información
                            solicitada.

                        </small>

                    </div>

                </div>



                <!-- CARD BODY -->

                <div class="card-body p-4">


                    <form
                        method="POST"
                        action="registrar_visita.php"
                        id="formVisita"
                    >


                        <!-- ==================================
                             NOMBRE
                        =================================== -->

                        <div class="mb-4">

                            <label
                                for="nombre_alumno"
                                class="form-label campo-label"
                            >

                                <i
                                    class="
                                        fas
                                        fa-user
                                        me-1
                                    "
                                ></i>

                                Nombre del visitante

                                <span class="text-danger">
                                    *
                                </span>

                            </label>


                            <input
                                type="text"
                                name="nombre_alumno"
                                id="nombre_alumno"
                                class="form-control form-control-lg"
                                placeholder="
                                    Ingrese el nombre completo
                                "
                                maxlength="150"
                                autocomplete="off"
                                required
                            >


                            <div class="form-text">

                                Escriba el nombre completo
                                de la persona que visita la biblioteca.

                            </div>

                        </div>



                        <!-- ==================================
                             TIPO DE VISITANTE
                        =================================== -->

                        <div class="tipo-visita-box mb-4">


                            <div
                                class="
                                    form-check
                                    form-switch
                                "
                            >

                                <input
                                    class="
                                        form-check-input
                                    "
                                    type="checkbox"
                                    name="es_externo"
                                    id="es_externo"
                                    value="1"
                                >


                                <label
                                    class="
                                        form-check-label
                                    "
                                    for="es_externo"
                                >

                                    <strong>

                                        Visitante de institución externa

                                    </strong>

                                    <small
                                        class="
                                            d-block
                                            text-muted
                                        "
                                    >

                                        Active esta opción si
                                        pertenece a otra institución.

                                    </small>

                                </label>

                            </div>


                        </div>



                        <!-- ==================================
                             INSTITUCIÓN
                        =================================== -->

                        <div
                            class="mb-4"
                            id="institucion_container"
                            style="display: none;"
                        >

                            <label
                                for="institucion_id"
                                class="form-label campo-label"
                            >

                                <i
                                    class="
                                        fas
                                        fa-building
                                        me-1
                                    "
                                ></i>

                                Institución

                                <span class="text-danger">
                                    *
                                </span>

                            </label>


                            <select
                                name="institucion_id"
                                id="institucion_id"
                                class="form-select form-select-lg"
                            >

                                <option value="">

                                    Seleccione una institución

                                </option>


                                <?php

                                if (
                                    $resultInstituciones
                                ):

                                    while (
                                        $institucion =
                                        $resultInstituciones
                                        ->fetch_assoc()
                                    ):

                                ?>

                                    <option
                                        value="<?php
                                            echo intval(
                                                $institucion['id']
                                            );
                                        ?>"
                                    >

                                        <?php
                                        echo htmlspecialchars(
                                            $institucion['nombre']
                                        );
                                        ?>

                                    </option>

                                <?php

                                    endwhile;

                                endif;

                                ?>

                            </select>

                        </div>



                        <!-- ==================================
                             CARRERA
                        =================================== -->

                        <div class="mb-4">

                            <label
                                for="carrera_id"
                                class="form-label campo-label"
                            >

                                <i
                                    class="
                                        fas
                                        fa-graduation-cap
                                        me-1
                                    "
                                ></i>

                                Carrera

                            </label>


                            <select
                                name="carrera_id"
                                id="carrera_id"
                                class="form-select form-select-lg"
                            >

                                <option value="">

                                    Seleccione una carrera

                                </option>


                                <?php

                                if (
                                    $resultCarreras
                                ):

                                    while (
                                        $carrera =
                                        $resultCarreras
                                        ->fetch_assoc()
                                    ):

                                ?>

                                    <option
                                        value="<?php
                                            echo intval(
                                                $carrera['id']
                                            );
                                        ?>"
                                    >

                                        <?php
                                        echo htmlspecialchars(
                                            $carrera['nombre']
                                        );
                                        ?>

                                    </option>

                                <?php

                                    endwhile;

                                endif;

                                ?>

                            </select>

                        </div>



                        <!-- ==================================
                             OBSERVACIONES
                        =================================== -->

                        <div class="mb-4">

                            <label
                                for="observaciones"
                                class="form-label campo-label"
                            >

                                <i
                                    class="
                                        fas
                                        fa-comment-alt
                                        me-1
                                    "
                                ></i>

                                Observaciones

                                <span
                                    class="
                                        text-muted
                                        fw-normal
                                    "
                                >

                                    (opcional)

                                </span>

                            </label>


                            <textarea
                                name="observaciones"
                                id="observaciones"
                                class="form-control"
                                rows="4"
                                maxlength="500"
                                placeholder="
                                    Escriba alguna observación
                                    relacionada con la visita...
                                "
                            ></textarea>


                            <div
                                class="
                                    d-flex
                                    justify-content-between
                                    mt-1
                                "
                            >

                                <small
                                    class="text-muted"
                                >

                                    Este campo es opcional.

                                </small>


                                <small
                                    class="text-muted"
                                >

                                    <span id="contador">
                                        0
                                    </span>
                                    / 500

                                </small>

                            </div>

                        </div>



                        <!-- ==================================
                             INFORMACIÓN
                        =================================== -->

                        <div
                            class="
                                alert
                                alert-primary
                                visita-info
                            "
                        >

                            <i
                                class="
                                    fas
                                    fa-info-circle
                                    me-2
                                "
                            ></i>

                            <strong>
                                Importante:
                            </strong>

                            Verifique que los datos
                            ingresados sean correctos antes
                            de registrar la visita.

                        </div>



                        <!-- ==================================
                             BOTONES
                        =================================== -->

                        <div
                            class="
                                d-flex
                                justify-content-between
                                align-items-center
                                flex-wrap
                                gap-2
                                mt-4
                            "
                        >


                            <a
                                href="dashboard.php"
                                class="
                                    btn
                                    btn-secondary
                                    btn-lg
                                "
                            >

                                <i
                                    class="
                                        fas
                                        fa-arrow-left
                                        me-1
                                    "
                                ></i>

                                Volver al menú

                            </a>


                            <button
                                type="submit"
                                class="
                                    btn
                                    btn-primary
                                    btn-lg
                                    btn-registrar
                                "
                            >

                                <i
                                    class="
                                        fas
                                        fa-save
                                        me-1
                                    "
                                ></i>

                                Registrar visita

                            </button>


                        </div>


                    </form>

                </div>

            </div>


        </div>

    </div>


</div>



<!-- ======================================================
     SWEETALERT
====================================================== -->

<?php

if (
    isset(
        $_SESSION['success_msg']
    )
):

    $successMessage =
        $_SESSION['success_msg'];

    unset(
        $_SESSION['success_msg']
    );

?>

<script>

document.addEventListener(
    'DOMContentLoaded',
    function () {

        if (
            typeof Swal !== 'undefined'
        ) {

            Swal.fire({

                icon: 'success',

                title:
                    '¡Visita registrada!',

                text:
                    <?php
                    echo json_encode(
                        $successMessage
                    );
                    ?>,

                confirmButtonText:
                    'Aceptar',

                timer: 2500,

                timerProgressBar: true

            });

        }

    }
);

</script>

<?php endif; ?>



<script>


// ======================================================
// MOSTRAR / OCULTAR INSTITUCIÓN
// ======================================================

const checkboxExterno =
    document.getElementById(
        'es_externo'
    );


const institucionContainer =
    document.getElementById(
        'institucion_container'
    );


const institucionSelect =
    document.getElementById(
        'institucion_id'
    );


function actualizarInstitucion() {


    if (
        checkboxExterno.checked
    ) {

        institucionContainer.style.display =
            'block';

        institucionSelect.required =
            true;

        setTimeout(
            function () {

                institucionSelect.focus();

            },
            100
        );

    } else {

        institucionContainer.style.display =
            'none';

        institucionSelect.required =
            false;

        institucionSelect.value =
            '';

    }

}


checkboxExterno.addEventListener(
    'change',
    actualizarInstitucion
);


// ======================================================
// CONTADOR DE OBSERVACIONES
// ======================================================

const observaciones =
    document.getElementById(
        'observaciones'
    );


const contador =
    document.getElementById(
        'contador'
    );


observaciones.addEventListener(
    'input',
    function () {

        contador.textContent =
            this.value.length;

    }
);


// ======================================================
// CONFIRMAR ENVÍO
// ======================================================

const formulario =
    document.getElementById(
        'formVisita'
    );


formulario.addEventListener(
    'submit',
    function (event) {


        const nombre =
            document.getElementById(
                'nombre_alumno'
            ).value.trim();


        if (!nombre) {

            event.preventDefault();

            if (
                typeof Swal !== 'undefined'
            ) {

                Swal.fire({

                    icon: 'warning',

                    title:
                        'Nombre requerido',

                    text:
                        'Ingrese el nombre completo del visitante.',

                    confirmButtonText:
                        'Aceptar'

                });

            } else {

                alert(
                    'Ingrese el nombre completo del visitante.'
                );

            }

            return;

        }


        if (
            checkboxExterno.checked &&
            !institucionSelect.value
        ) {

            event.preventDefault();

            if (
                typeof Swal !== 'undefined'
            ) {

                Swal.fire({

                    icon: 'warning',

                    title:
                        'Institución requerida',

                    text:
                        'Seleccione la institución del visitante externo.',

                    confirmButtonText:
                        'Aceptar'

                });

            } else {

                alert(
                    'Seleccione la institución.'
                );

            }

            return;

        }


    }
);

</script>



<style>

/* ======================================================
   ENCABEZADO
====================================================== */

.visita-header {

    display: flex;

    justify-content: space-between;

    align-items: center;

    gap: 20px;

}


.visita-header h1 {

    color:
        #263238 !important;

    font-size:
        2rem;

    font-weight:
        700;

    margin-bottom:
        5px;

}


.visita-header h1 i {

    color:
        #3159d8 !important;

}


.visita-header p {

    color:
        #64748b;

    margin:
        0;

}


.sede-badge {

    background:
        #3159d8;

    color:
        #ffffff;

    padding:
        10px 18px;

    border-radius:
        10px;

    font-weight:
        600;

    white-space:
        nowrap;

    box-shadow:
        0 4px 10px
        rgba(49, 89, 216, .2);

}


/* ======================================================
   CARD
====================================================== */

.visita-card {

    border:
        none;

    border-radius:
        14px;

    overflow:
        hidden;

}


.visita-card-header {

    background:
        #3159d8;

    color:
        #ffffff;

    padding:
        20px 24px;

    border:
        none;

}


.visita-card-header h4 {

    color:
        #ffffff !important;

    margin:
        0 0 4px;

    font-weight:
        700;

}


.visita-card-header h4 i {

    color:
        #ffffff !important;

}


.visita-card-header small {

    color:
        rgba(255,255,255,.85);

}


/* ======================================================
   LABELS
====================================================== */

.campo-label {

    font-weight:
        600;

    color:
        #263238;

    margin-bottom:
        8px;

}


.campo-label i {

    color:
        #3159d8;

}


/* ======================================================
   INPUTS
====================================================== */

.form-control,
.form-select {

    border:
        1px solid
        #d8dee9;

    border-radius:
        9px;

}


.form-control-lg,
.form-select-lg {

    min-height:
        50px;

}


.form-control:focus,
.form-select:focus {

    border-color:
        #3159d8;

    box-shadow:
        0 0 0
        3px
        rgba(49,89,216,.12);

}


/* ======================================================
   VISITANTE EXTERNO
====================================================== */

.tipo-visita-box {

    background:
        #f5f7ff;

    border:
        1px solid
        #dbe3ff;

    border-radius:
        10px;

    padding:
        16px 18px;

}


.form-switch .form-check-input {

    width:
        2.6em;

    height:
        1.35em;

}


.form-switch .form-check-input:checked {

    background-color:
        #3159d8;

    border-color:
        #3159d8;

}


/* ======================================================
   INFORMACIÓN
====================================================== */

.visita-info {

    border:
        none;

    border-left:
        4px solid
        #3159d8;

    background:
        #eef3ff;

    color:
        #334155;

}


/* ======================================================
   BOTÓN REGISTRAR
====================================================== */

.btn-registrar {

    min-width:
        190px;

    font-weight:
        600;

    box-shadow:
        0 4px 10px
        rgba(49,89,216,.20);

}


/* ======================================================
   RESPONSIVE
====================================================== */

@media (
    max-width: 768px
) {

    .visita-header {

        flex-direction:
            column;

        align-items:
            flex-start;

    }


    .visita-header h1 {

        font-size:
            1.5rem;

    }


    .sede-badge {

        width:
            100%;

        text-align:
            center;

    }


    .visita-card .card-body {

        padding:
            20px !important;

    }


    .btn-registrar {

        width:
            100%;

    }

}

</style>


<?php

include 'includes/footer.php';

?>