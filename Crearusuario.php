<?php

session_start();

include 'config/db.php';


// ======================================================
// CONFIGURACIÓN
// SISTEMA EXCLUSIVO PARA DANLÍ
// ======================================================

$sede_id = 6;
$sede_nombre = "Danlí";

$error = "";
$success = "";


// ======================================================
// VERIFICAR QUE EL USUARIO LOGUEADO SEA ADMIN
// ======================================================

if (
    !isset($_SESSION['role']) ||
    $_SESSION['role'] !== 'admin'
) {
    header('Location: /biblioteca/dashboard.php');
    exit();
}


// ======================================================
// PROCESAR FORMULARIO
// ======================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Datos enviados por el formulario

    $username = trim($_POST['username'] ?? '');

    $nombre = trim($_POST['nombre'] ?? '');

    $tipo = trim($_POST['tipo'] ?? '');

    $password = $_POST['password'] ?? '';

    $confirm_password =
        $_POST['confirm_password'] ?? '';


    // ==================================================
    // VALIDACIONES
    // ==================================================

    if (
        empty($username) ||
        empty($nombre) ||
        empty($tipo) ||
        empty($password) ||
        empty($confirm_password)
    ) {

        $error =
            "Todos los campos son obligatorios.";

    }

    elseif (
        !in_array(
            $tipo,
            ['admin', 'docente', 'usuario'],
            true
        )
    ) {

        $error =
            "El tipo de usuario seleccionado no es válido.";

    }

    elseif (
        $password !== $confirm_password
    ) {

        $error =
            "Las contraseñas no coinciden.";

    }

    elseif (
        strlen($password) < 6
    ) {

        $error =
            "La contraseña debe tener al menos 6 caracteres.";

    }

    else {

        // ==================================================
        // VERIFICAR SI EL USUARIO YA EXISTE
        // ==================================================

        $stmtCheck = $conn->prepare("
            SELECT id
            FROM usuarios
            WHERE username = ?
            LIMIT 1
        ");

        $stmtCheck->bind_param(
            "s",
            $username
        );

        $stmtCheck->execute();

        $resultCheck =
            $stmtCheck->get_result();


        if ($resultCheck->num_rows > 0) {

            $error =
                "El usuario '" .
                htmlspecialchars($username) .
                "' ya existe.";

        }

        else {

            // ==================================================
            // ENCRIPTAR CONTRASEÑA
            // ==================================================

            $password_hash =
                password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );


            // ==================================================
            // CREAR USUARIO
            // ==================================================

            $stmtInsert = $conn->prepare("
                INSERT INTO usuarios
                (
                    username,
                    nombre,
                    password,
                    role,
                    sede_id
                )
                VALUES
                (?, ?, ?, ?, ?)
            ");


            $stmtInsert->bind_param(
                "ssssi",
                $username,
                $nombre,
                $password_hash,
                $tipo,
                $sede_id
            );


            if ($stmtInsert->execute()) {

                // Convertir el tipo a texto para mostrar

                if ($tipo === 'admin') {

                    $tipoTexto =
                        'Administrador';

                } elseif ($tipo === 'docente') {

                    $tipoTexto =
                        'Docente';

                } else {

                    $tipoTexto =
                        'Alumno';
                }


                $success = "
                    <strong>Usuario creado correctamente.</strong>
                    <br><br>

                    Usuario:
                    <strong>" .
                    htmlspecialchars($username) .
                    "</strong>

                    <br>

                    Nombre:
                    <strong>" .
                    htmlspecialchars($nombre) .
                    "</strong>

                    <br>

                    Tipo:
                    <strong>" .
                    $tipoTexto .
                    "</strong>

                    <br>

                    Sede:
                    <strong>Danlí</strong>
                ";


                // Limpiar campos

                $username = "";
                $nombre = "";

            } else {

                $error =
                    "Error al crear el usuario: "
                    . $stmtInsert->error;
            }


            $stmtInsert->close();
        }


        $stmtCheck->close();
    }
}


// ======================================================
// HEADER
// ======================================================

include 'includes/header.php';

?>


<div class="container-fluid py-4">

    <div class="row justify-content-center">

        <div class="col-lg-8 col-xl-7">

            <div class="card shadow-sm border-0">


                <!-- ==================================================
                     ENCABEZADO
                ================================================== -->

                <div class="card-header bg-primary text-white py-3">

                    <h4 class="mb-0 text-white">

                        <i class="fas fa-user-plus me-2"></i>

                        Crear Nuevo Usuario

                    </h4>

                </div>


                <!-- ==================================================
                     CUERPO
                ================================================== -->

                <div class="card-body p-4">


                    <!-- ERROR -->

                    <?php if (!empty($error)): ?>

                        <div
                            class="
                                alert
                                alert-danger
                                alert-dismissible
                                fade
                                show
                            "
                        >

                            <i
                                class="
                                    fas
                                    fa-exclamation-triangle
                                    me-2
                                "
                            ></i>

                            <?php echo $error; ?>

                            <button
                                type="button"
                                class="btn-close"
                                data-bs-dismiss="alert"
                            ></button>

                        </div>

                    <?php endif; ?>


                    <!-- ÉXITO -->

                    <?php if (!empty($success)): ?>

                        <div
                            class="
                                alert
                                alert-success
                                alert-dismissible
                                fade
                                show
                            "
                        >

                            <i
                                class="
                                    fas
                                    fa-check-circle
                                    me-2
                                "
                            ></i>

                            <?php echo $success; ?>

                            <hr>

                            <a
                                href="usuarios/list.php"
                                class="btn btn-success btn-sm"
                            >

                                <i
                                    class="
                                        fas
                                        fa-users
                                        me-1
                                    "
                                ></i>

                                Ver Usuarios

                            </a>

                            <a
                                href="Crearusuario.php"
                                class="btn btn-primary btn-sm"
                            >

                                <i
                                    class="
                                        fas
                                        fa-user-plus
                                        me-1
                                    "
                                ></i>

                                Crear Otro Usuario

                            </a>

                        </div>

                    <?php endif; ?>


                    <!-- ==================================================
                         INFORMACIÓN
                    ================================================== -->

                    <div class="alert alert-info">

                        <i
                            class="
                                fas
                                fa-info-circle
                                me-2
                            "
                        ></i>

                        <strong>Información:</strong>

                        Complete los datos del usuario.
                        La sede se asignará automáticamente a
                        <strong>Danlí</strong>.

                    </div>


                    <!-- ==================================================
                         FORMULARIO
                    ================================================== -->

                    <?php if (empty($success)): ?>

                    <form
                        method="post"
                        action=""
                        autocomplete="off"
                    >


                        <!-- ==================================================
                             NOMBRE DE USUARIO
                        ================================================== -->

                        <div class="mb-3">

                            <label
                                for="username"
                                class="form-label"
                            >

                                <strong>
                                    Nombre de Usuario
                                </strong>

                            </label>

                            <input
                                type="text"
                                class="form-control"
                                id="username"
                                name="username"
                                value="<?php
                                    echo htmlspecialchars(
                                        $username ?? ''
                                    );
                                ?>"
                                placeholder="Ejemplo: docente1"
                                required
                            >

                            <div class="form-text">

                                Este será el usuario que utilizará
                                para iniciar sesión.

                            </div>

                        </div>


                        <!-- ==================================================
                             NOMBRE COMPLETO
                        ================================================== -->

                        <div class="mb-3">

                            <label
                                for="nombre"
                                class="form-label"
                            >

                                <strong>
                                    Nombre Completo
                                </strong>

                            </label>

                            <input
                                type="text"
                                class="form-control"
                                id="nombre"
                                name="nombre"
                                value="<?php
                                    echo htmlspecialchars(
                                        $nombre ?? ''
                                    );
                                ?>"
                                placeholder="
                                    Ejemplo: Juan Carlos Pérez
                                "
                                required
                            >

                        </div>


                        <!-- ==================================================
                             TIPO DE USUARIO
                        ================================================== -->

                        <div class="mb-3">

                            <label
                                for="tipo"
                                class="form-label"
                            >

                                <strong>
                                    Tipo de Usuario
                                </strong>

                            </label>


                            <select
                                class="form-select"
                                id="tipo"
                                name="tipo"
                                required
                            >

                                <option
                                    value=""
                                    disabled
                                    selected
                                >

                                    Seleccione el tipo de usuario

                                </option>


                                <option
                                    value="admin"
                                    <?php
                                    echo (
                                        ($tipo ?? '') ===
                                        'admin'
                                    )
                                    ? 'selected'
                                    : '';
                                    ?>
                                >

                                    Administrador

                                </option>


                                <option
                                    value="docente"
                                    <?php
                                    echo (
                                        ($tipo ?? '') ===
                                        'docente'
                                    )
                                    ? 'selected'
                                    : '';
                                    ?>
                                >

                                    Docente

                                </option>


                                <option
                                    value="usuario"
                                    <?php
                                    echo (
                                        ($tipo ?? '') ===
                                        'usuario'
                                    )
                                    ? 'selected'
                                    : '';
                                    ?>
                                >

                                    Alumno

                                </option>

                            </select>

                        </div>


                        <!-- ==================================================
                             CONTRASEÑA
                        ================================================== -->

                        <div class="mb-3">

                            <label
                                for="password"
                                class="form-label"
                            >

                                <strong>
                                    Contraseña
                                </strong>

                            </label>

                            <input
                                type="password"
                                class="form-control"
                                id="password"
                                name="password"
                                placeholder="
                                    Ingrese la contraseña
                                "
                                minlength="6"
                                required
                            >

                            <div class="form-text">

                                Mínimo 6 caracteres.

                            </div>

                        </div>


                        <!-- ==================================================
                             CONFIRMAR CONTRASEÑA
                        ================================================== -->

                        <div class="mb-3">

                            <label
                                for="confirm_password"
                                class="form-label"
                            >

                                <strong>
                                    Confirmar Contraseña
                                </strong>

                            </label>

                            <input
                                type="password"
                                class="form-control"
                                id="confirm_password"
                                name="confirm_password"
                                placeholder="
                                    Repita la contraseña
                                "
                                minlength="6"
                                required
                            >

                        </div>


                        <!-- ==================================================
                             SEDE FIJA
                        ================================================== -->

                        <div class="mb-4">

                            <label
                                for="sede"
                                class="form-label"
                            >

                                <strong>
                                    Sede
                                </strong>

                            </label>

                            <input
                                type="text"
                                class="form-control"
                                id="sede"
                                value="Danlí"
                                readonly
                            >

                            <div class="form-text">

                                📍 La sede está configurada
                                exclusivamente para Danlí.

                            </div>

                        </div>


                        <!-- ==================================================
                             BOTONES
                        ================================================== -->

                        <div
                            class="
                                d-flex
                                justify-content-between
                            "
                        >

                            <a
                                href="usuarios/list.php"
                                class="btn btn-secondary"
                            >

                                <i
                                    class="
                                        fas
                                        fa-arrow-left
                                        me-1
                                    "
                                ></i>

                                Cancelar

                            </a>


                            <button
                                type="submit"
                                class="btn btn-primary px-4"
                            >

                                <i
                                    class="
                                        fas
                                        fa-user-plus
                                        me-1
                                    "
                                ></i>

                                Crear Usuario

                            </button>

                        </div>


                    </form>

                    <?php endif; ?>


                </div>

            </div>

        </div>

    </div>

</div>


<script>

// ======================================================
// VALIDAR CONTRASEÑAS
// ======================================================

document
    .querySelector('form')
    ?.addEventListener(
        'submit',
        function(event) {

            const password =
                document.getElementById(
                    'password'
                ).value;

            const confirmPassword =
                document.getElementById(
                    'confirm_password'
                ).value;


            if (
                password !==
                confirmPassword
            ) {

                event.preventDefault();

                alert(
                    'Las contraseñas no coinciden.'
                );

                return;
            }


            if (
                password.length < 6
            ) {

                event.preventDefault();

                alert(
                    'La contraseña debe tener al menos 6 caracteres.'
                );

            }

        }
    );

</script>


<style>

/* ======================================================
   ENCABEZADO
====================================================== */

/* Encabezados azules: título e icono BLANCOS */
.card-header.bg-primary,
.card-header.bg-primary h1,
.card-header.bg-primary h2,
.card-header.bg-primary h3,
.card-header.bg-primary h4,
.card-header.bg-primary h5,
.card-header.bg-primary h6,
.card-header.bg-primary .card-title,
.card-header.bg-primary p,
.card-header.bg-primary span,
.card-header.bg-primary label,
.card-header.bg-primary i {
    color: #ffffff !important;
}


/* ======================================================
   FORMULARIO
====================================================== */

.form-label {
    color: #263238 !important;
    font-weight: 500;
}

.form-control,
.form-select {
    min-height: 45px;
}

.form-control:focus,
.form-select:focus {
    border-color: #0d6efd;
    box-shadow:
        0 0 0 0.2rem
        rgba(13, 110, 253, 0.15);
}


/* ======================================================
   ENCABEZADOS SOBRE FONDO CLARO
====================================================== */

.card-header:not(.bg-primary) {
    color: #1e293b !important;
}

.card-header:not(.bg-primary) h1,
.card-header:not(.bg-primary) h2,
.card-header:not(.bg-primary) h3,
.card-header:not(.bg-primary) h4,
.card-header:not(.bg-primary) h5,
.card-header:not(.bg-primary) h6,
.card-header:not(.bg-primary) .card-title {
    color: #1e293b !important;
}

.card-header:not(.bg-primary) p,
.card-header:not(.bg-primary) span,
.card-header:not(.bg-primary) label {
    color: #475569 !important;
}

.card-header:not(.bg-primary) i {
    color: #2563eb !important;
}


/* ======================================================
   TÍTULOS PRINCIPALES
====================================================== */

main h1,
main h2,
main h3,
.container h1,
.container h2,
.container h3 {
    color: #1e293b !important;
}


/* ======================================================
   BARRA SUPERIOR
====================================================== */

.navbar .navbar-brand,
.navbar .navbar-brand i,
.offcanvas-header .offcanvas-title {
    color: #ffffff !important;
}


/* ======================================================
   SEDE DANLÍ
====================================================== */

.sede-info {
    color: #ffffff !important;
}

.sede-info i,
.sede-info span,
.sede-info strong {
    color: #ffffff !important;
}


/* ======================================================
   BOTONES
====================================================== */

.btn-primary {
    color: #ffffff !important;
}

.btn-primary i {
    color: #ffffff !important;
}


/* ======================================================
   TÍTULOS DE FORMULARIOS
====================================================== */

.card-body h1,
.card-body h2,
.card-body h3,
.card-body h4,
.card-body h5,
.card-body h6 {
    color: #1e293b !important;
}
</style>


<?php

include 'includes/footer.php';

?>