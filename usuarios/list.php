<?php

include '../includes/session.php';
include '../config/db.php';


// ======================================================
// CONFIGURACIÓN
// SISTEMA EXCLUSIVO PARA DANLÍ
// ======================================================

$sede_id = 6;
$sede_nombre = "Danlí";


// ======================================================
// VERIFICAR QUE SEA ADMINISTRADOR
// ======================================================

if (
    !isset($_SESSION['role']) ||
    $_SESSION['role'] !== 'admin'
) {
    header('Location: /biblioteca/dashboard.php');
    exit();
}


// ======================================================
// VARIABLES
// ======================================================

$success = "";
$error = "";


// ======================================================
// CAMBIAR CONTRASEÑA
// ======================================================

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['action']) &&
    $_POST['action'] === 'change_password'
) {

    $user_id = intval($_POST['user_id'] ?? 0);
    $new_password = $_POST['new_password'] ?? '';


    if ($user_id <= 0) {

        $error = "ID de usuario no válido.";

    } elseif (empty($new_password)) {

        $error = "La contraseña no puede estar vacía.";

    } elseif (strlen($new_password) < 6) {

        $error =
            "La contraseña debe tener al menos 6 caracteres.";

    } elseif (
        isset($_SESSION['user_id']) &&
        $user_id == intval($_SESSION['user_id'])
    ) {

        // Se permite cambiar la contraseña,
        // pero el usuario sigue siendo de Danlí.

        $hashed_password =
            password_hash(
                $new_password,
                PASSWORD_DEFAULT
            );


        $stmt = $conn->prepare("
            UPDATE usuarios
            SET password = ?
            WHERE id = ?
              AND sede_id = 6
        ");

        $stmt->bind_param(
            "si",
            $hashed_password,
            $user_id
        );


        if ($stmt->execute()) {

            if ($stmt->affected_rows >= 0) {

                $success =
                    "Contraseña actualizada correctamente.";

            } else {

                $error =
                    "No se encontró el usuario.";

            }

        } else {

            $error =
                "Error al actualizar la contraseña: "
                . $stmt->error;
        }


        $stmt->close();

    } else {

        // --------------------------------------------------
        // ACTUALIZAR CONTRASEÑA DE OTRO USUARIO
        // --------------------------------------------------

        $hashed_password =
            password_hash(
                $new_password,
                PASSWORD_DEFAULT
            );


        $stmt = $conn->prepare("
            UPDATE usuarios
            SET password = ?
            WHERE id = ?
              AND sede_id = 6
        ");

        $stmt->bind_param(
            "si",
            $hashed_password,
            $user_id
        );


        if ($stmt->execute()) {

            if ($stmt->affected_rows > 0) {

                $success =
                    "Contraseña actualizada correctamente.";

            } else {

                $error =
                    "Usuario no encontrado en la sede Danlí.";
            }

        } else {

            $error =
                "Error al actualizar la contraseña: "
                . $stmt->error;
        }


        $stmt->close();
    }
}


// ======================================================
// OBTENER USUARIOS
// SOLAMENTE DANLÍ
// ======================================================
//
// IMPORTANTE:
// role = admin      -> Administrador
// role = docente    -> Docente
// role = usuario    -> Alumno
//
// También se consulta alumnos para obtener
// el nombre del alumno cuando exista.
// ======================================================

$sql = "

    SELECT

        u.id,

        u.username,

        u.nombre,

        u.role,

        u.sede_id,

        a.id AS alumno_id,

        a.nombre AS nombre_alumno,

        CASE

            WHEN u.role = 'admin'
                THEN 'Administrador'

            WHEN u.role = 'docente'
                THEN 'Docente'

            WHEN u.role = 'usuario'
                THEN 'Alumno'

            WHEN a.id IS NOT NULL
                THEN 'Alumno'

            ELSE 'Usuario'

        END AS tipo_usuario,

        COALESCE(
            NULLIF(u.nombre, ''),
            NULLIF(a.nombre, ''),
            'Sin nombre'
        ) AS nombre_completo

    FROM usuarios u

    LEFT JOIN alumnos a
        ON u.id = a.usuario_id

    WHERE u.sede_id = 6

    ORDER BY u.username ASC
";


$result = $conn->query($sql);


if (!$result) {

    die(
        "Error al obtener usuarios: "
        . $conn->error
    );

}


// ======================================================
// HEADER
// ======================================================

include '../includes/header.php';

?>


<div class="container-fluid py-4">


    <!-- ==================================================
         ENCABEZADO
    ================================================== -->

    <div class="card shadow mb-4 border-0">

    <div class="card-header bg-primary text-white py-3">
    <div class="d-flex justify-content-between align-items-center">

        <div>
            <h5 class="mb-1 fw-bold text-white">
                <i class="fas fa-users me-2 text-white"></i>
                Gestión de Usuarios
            </h5>

            <small class="text-white">
                <i class="fas fa-map-marker-alt me-1"></i>
                Sede Danlí
            </small>
        </div>

        <div>
            <span class="badge bg-white text-primary">
                <i class="fas fa-users me-1"></i>
                Usuarios de Danlí
            </span>
        </div>

    </div>
</div>


        <div class="card-body">


            <!-- ==================================================
                 MENSAJE DE ÉXITO
            ================================================== -->

            <?php if (!empty($success)): ?>

                <div
                    class="
                        alert
                        alert-success
                        alert-dismissible
                        fade
                        show
                    "
                    role="alert"
                >

                    <i class="fas fa-check-circle me-2"></i>

                    <?php echo htmlspecialchars($success); ?>

                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="alert"
                    ></button>

                </div>

            <?php endif; ?>


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
                    "
                    role="alert"
                >

                    <i
                        class="
                            fas
                            fa-exclamation-triangle
                            me-2
                        "
                    ></i>

                    <?php echo htmlspecialchars($error); ?>

                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="alert"
                    ></button>

                </div>

            <?php endif; ?>


            <!-- ==================================================
                 MENSAJE SESSION SUCCESS
            ================================================== -->

            <?php if (isset($_SESSION['success'])): ?>

                <div
                    class="
                        alert
                        alert-success
                        alert-dismissible
                        fade
                        show
                    "
                    role="alert"
                >

                    <i class="fas fa-check-circle me-2"></i>

                    <?php
                    echo htmlspecialchars(
                        $_SESSION['success']
                    );
                    ?>

                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="alert"
                    ></button>

                </div>

                <?php unset($_SESSION['success']); ?>

            <?php endif; ?>


            <!-- ==================================================
                 MENSAJE SESSION ERROR
            ================================================== -->

            <?php if (isset($_SESSION['error'])): ?>

                <div
                    class="
                        alert
                        alert-danger
                        alert-dismissible
                        fade
                        show
                    "
                    role="alert"
                >

                    <i
                        class="
                            fas
                            fa-exclamation-triangle
                            me-2
                        "
                    ></i>

                    <?php
                    echo htmlspecialchars(
                        $_SESSION['error']
                    );
                    ?>

                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="alert"
                    ></button>

                </div>

                <?php unset($_SESSION['error']); ?>

            <?php endif; ?>


            <!-- ==================================================
                 BOTÓN CREAR USUARIO
            ================================================== -->

            <div class="mb-3">

                <a
                    href="../Crearusuario.php"
                    class="btn btn-success"
                >

                    <i class="fas fa-user-plus me-1"></i>

                    Crear Nuevo Usuario

                </a>

            </div>


            <!-- ==================================================
                 TABLA
            ================================================== -->

            <div class="table-responsive">

                <table
                    id="tablaUsuarios"
                    class="
                        table
                        table-bordered
                        table-striped
                        table-hover
                        align-middle
                    "
                >

                    <thead
                        class="
                            table-primary
                            text-center
                        "
                    >

                        <tr>

                            <th>
                                Usuario
                            </th>

                            <th>
                                Nombre
                            </th>

                            <th>
                                Tipo
                            </th>

                            <th>
                                Sede
                            </th>

                            <th
                                style="width: 280px;"
                            >
                                Acciones
                            </th>

                        </tr>

                    </thead>


                    <tbody>


                        <?php
                        while (
                            $row =
                            $result->fetch_assoc()
                        ):
                        ?>


                        <tr>


                            <!-- USUARIO -->

                            <td>

                                <strong>

                                    <?php
                                    echo htmlspecialchars(
                                        $row['username']
                                    );
                                    ?>

                                </strong>

                            </td>


                            <!-- NOMBRE -->

                            <td>

                                <?php
                                echo htmlspecialchars(
                                    $row['nombre_completo']
                                );
                                ?>

                            </td>


                            <!-- TIPO -->

                            <td class="text-center">

                                <?php

                                $tipo =
                                    $row['tipo_usuario'];

                                if (
                                    $tipo ===
                                    'Administrador'
                                ) {

                                    $badge =
                                        'danger';

                                    $icon =
                                        'fa-user-shield';

                                } elseif (
                                    $tipo ===
                                    'Docente'
                                ) {

                                    $badge =
                                        'primary';

                                    $icon =
                                        'fa-chalkboard-teacher';

                                } elseif (
                                    $tipo ===
                                    'Alumno'
                                ) {

                                    $badge =
                                        'info';

                                    $icon =
                                        'fa-user-graduate';

                                } else {

                                    $badge =
                                        'secondary';

                                    $icon =
                                        'fa-user';
                                }

                                ?>


                                <span
                                    class="
                                        badge
                                        bg-<?php
                                        echo $badge;
                                        ?>
                                    "
                                >

                                    <i
                                        class="
                                            fas
                                            <?php
                                            echo $icon;
                                            ?>
                                            me-1
                                        "
                                    ></i>

                                    <?php
                                    echo htmlspecialchars(
                                        $tipo
                                    );
                                    ?>

                                </span>

                            </td>


                            <!-- SEDE -->

                            <td class="text-center">

                                <span
                                    class="
                                        badge
                                        bg-success
                                    "
                                >

                                    <i
                                        class="
                                            fas
                                            fa-map-marker-alt
                                            me-1
                                        "
                                    ></i>

                                    Danlí

                                </span>

                            </td>


                            <!-- ACCIONES -->

                            <td class="text-center">

                                <div
                                    class="
                                        btn-group
                                        btn-group-sm
                                    "
                                    role="group"
                                >


                                    <!-- EDITAR -->

                                    <a
                                        href="
                                            edit.php?id=<?php
                                            echo intval(
                                                $row['id']
                                            );
                                            ?>
                                        "
                                        class="
                                            btn
                                            btn-outline-primary
                                        "
                                        title="Editar"
                                        data-bs-toggle="tooltip"
                                    >

                                        <i
                                            class="
                                                fas
                                                fa-edit
                                            "
                                        ></i>

                                    </a>


                                    <!-- CAMBIAR CONTRASEÑA -->

                                    <button
                                        type="button"
                                        class="
                                            btn
                                            btn-outline-warning
                                        "
                                        onclick="
                                            mostrarCambioPassword(
                                                <?php
                                                echo intval(
                                                    $row['id']
                                                );
                                                ?>,
                                                <?php
                                                echo htmlspecialchars(
                                                    json_encode(
                                                        $row['username']
                                                    ),
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                );
                                                ?>
                                            )
                                        "
                                        title="Cambiar Contraseña"
                                        data-bs-toggle="tooltip"
                                    >

                                        <i
                                            class="
                                                fas
                                                fa-key
                                            "
                                        ></i>

                                    </button>


                                    <!-- ELIMINAR -->

                                    <?php
                                    if (
                                        intval(
                                            $row['id']
                                        ) !=
                                        intval(
                                            $_SESSION['user_id']
                                        )
                                    ):
                                    ?>


                                        <button
                                            type="button"
                                            onclick="
                                                confirmarEliminarUsuario(
                                                    <?php
                                                    echo intval(
                                                        $row['id']
                                                    );
                                                    ?>,
                                                    <?php
                                                    echo htmlspecialchars(
                                                        json_encode(
                                                            $row['username']
                                                        ),
                                                        ENT_QUOTES,
                                                        'UTF-8'
                                                    );
                                                    ?>
                                                )
                                            "
                                            class="
                                                btn
                                                btn-outline-danger
                                            "
                                            title="Eliminar"
                                            data-bs-toggle="tooltip"
                                        >

                                            <i
                                                class="
                                                    fas
                                                    fa-trash-alt
                                                "
                                            ></i>

                                        </button>


                                    <?php else: ?>


                                        <button
                                            type="button"
                                            class="
                                                btn
                                                btn-outline-danger
                                            "
                                            disabled
                                            title="
                                                No puedes eliminar
                                                tu propio usuario
                                            "
                                            data-bs-toggle="tooltip"
                                        >

                                            <i
                                                class="
                                                    fas
                                                    fa-trash-alt
                                                "
                                            ></i>

                                        </button>


                                    <?php endif; ?>


                                </div>

                            </td>


                        </tr>


                        <?php endwhile; ?>


                    </tbody>

                </table>

            </div>

        </div>

    </div>

</div>



<!-- ======================================================
     MODAL CAMBIAR CONTRASEÑA
====================================================== -->

<div
    class="modal fade"
    id="cambioPasswordModal"
    tabindex="-1"
    aria-hidden="true"
>

    <div class="modal-dialog">

        <div class="modal-content">


            <!-- HEADER -->

            <div class="modal-header">

                <h5 class="modal-title">

                    <i class="fas fa-key me-2"></i>

                    Cambiar Contraseña

                </h5>

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="modal"
                ></button>

            </div>


            <!-- FORMULARIO -->

            <form
                method="post"
                id="formCambioPassword"
            >

                <div class="modal-body">


                    <input
                        type="hidden"
                        name="action"
                        value="change_password"
                    >


                    <input
                        type="hidden"
                        name="user_id"
                        id="user_id"
                    >


                    <div class="alert alert-info">

                        Cambiando contraseña para:

                        <strong
                            id="username_display"
                        ></strong>

                    </div>


                    <!-- NUEVA CONTRASEÑA -->

                    <div class="mb-3">

                        <label
                            for="new_password"
                            class="form-label"
                        >

                            Nueva Contraseña

                        </label>

                        <input
                            type="password"
                            class="form-control"
                            id="new_password"
                            name="new_password"
                            minlength="6"
                            required
                        >

                        <small class="text-muted">

                            Mínimo 6 caracteres.

                        </small>

                    </div>


                    <!-- CONFIRMAR -->

                    <div class="mb-3">

                        <label
                            for="confirm_password"
                            class="form-label"
                        >

                            Confirmar Contraseña

                        </label>

                        <input
                            type="password"
                            class="form-control"
                            id="confirm_password"
                            minlength="6"
                            required
                        >

                    </div>


                </div>


                <div class="modal-footer">

                    <button
                        type="button"
                        class="btn btn-secondary"
                        data-bs-dismiss="modal"
                    >

                        Cancelar

                    </button>


                    <button
                        type="submit"
                        class="btn btn-primary"
                    >

                        <i
                            class="
                                fas
                                fa-save
                                me-1
                            "
                        ></i>

                        Guardar Cambios

                    </button>

                </div>

            </form>

        </div>

    </div>

</div>



<!-- ======================================================
     DATATABLES
====================================================== -->

<link
    href="
        https://cdn.datatables.net/1.13.1/css/
        dataTables.bootstrap5.min.css
    "
    rel="stylesheet"
>


<script
    src="
        https://cdn.datatables.net/1.13.1/js/
        jquery.dataTables.min.js
    "
></script>


<script
    src="
        https://cdn.datatables.net/1.13.1/js/
        dataTables.bootstrap5.min.js
    "
></script>



<!-- ======================================================
     JAVASCRIPT
====================================================== -->

<script>


// ======================================================
// DATATABLE
// ======================================================

$(document).ready(function() {


    $('#tablaUsuarios').DataTable({

        language: {

            url:
                "//cdn.datatables.net/plug-ins/"
                + "1.13.1/i18n/es-ES.json"

        },


        responsive: true,

        pageLength: 10,

        order: [
            [0, 'asc']
        ],


        columnDefs: [

            {
                orderable: false,
                targets: 4
            }

        ],


        dom:
            '<"row mb-3"' +
            '<"col-md-6"l>' +
            '<"col-md-6"f>' +
            '>rtip',


        lengthMenu: [

            [10, 25, 50, -1],

            [10, 25, 50, "Todos"]

        ]

    });


    // ==================================================
    // TOOLTIPS
    // ==================================================

    const tooltipTriggerList =
        [].slice.call(
            document.querySelectorAll(
                '[data-bs-toggle="tooltip"]'
            )
        );


    tooltipTriggerList.map(
        function(tooltipTriggerEl) {

            return new bootstrap.Tooltip(
                tooltipTriggerEl,
                {
                    placement: 'top',
                    trigger: 'hover'
                }
            );

        }
    );

});



// ======================================================
// MOSTRAR MODAL DE CONTRASEÑA
// ======================================================

function mostrarCambioPassword(
    userId,
    username
) {

    document.getElementById(
        'user_id'
    ).value = userId;


    document.getElementById(
        'username_display'
    ).textContent = username;


    document.getElementById(
        'new_password'
    ).value = '';


    document.getElementById(
        'confirm_password'
    ).value = '';


    const modal =
        new bootstrap.Modal(
            document.getElementById(
                'cambioPasswordModal'
            )
        );


    modal.show();

}



// ======================================================
// CONFIRMAR ELIMINACIÓN
// ======================================================

function confirmarEliminarUsuario(
    userId,
    username
) {

    Swal.fire({

        title:
            '¿Estás seguro?',

        text:
            '¿Realmente deseas eliminar al usuario "' +
            username +
            '"? Esta acción no se puede deshacer.',

        icon:
            'warning',

        showCancelButton:
            true,

        confirmButtonColor:
            '#d33',

        cancelButtonColor:
            '#3085d6',

        confirmButtonText:
            'Sí, eliminar',

        cancelButtonText:
            'Cancelar',

        customClass: {

            confirmButton:
                'btn btn-danger me-2',

            cancelButton:
                'btn btn-secondary'

        },

        buttonsStyling:
            false

    }).then(
        function(result) {

            if (result.isConfirmed) {

                window.location.href =
                    'delete.php?id=' +
                    encodeURIComponent(
                        userId
                    );

            }

        }
    );

}



// ======================================================
// VALIDAR CONTRASEÑAS
// ======================================================

document
    .getElementById(
        'formCambioPassword'
    )
    .addEventListener(
        'submit',
        function(e) {


            const password =
                document.getElementById(
                    'new_password'
                ).value;


            const confirmPassword =
                document.getElementById(
                    'confirm_password'
                ).value;


            if (
                password.length < 6
            ) {

                e.preventDefault();


                Swal.fire({

                    icon:
                        'error',

                    title:
                        'Contraseña inválida',

                    text:
                        'La contraseña debe tener al menos 6 caracteres.'

                });


                return;

            }


            if (
                password !==
                confirmPassword
            ) {

                e.preventDefault();


                Swal.fire({

                    icon:
                        'error',

                    title:
                        'Error',

                    text:
                        'Las contraseñas no coinciden.'

                });

            }

        }
    );

</script>



<style>

/* ======================================================
   TABLA DE USUARIOS
====================================================== */

#tablaUsuarios th {

    font-weight: 600;

    vertical-align: middle;

    padding:
        0.75rem
        0.5rem;

}


#tablaUsuarios td {

    padding:
        0.6rem;

    vertical-align: middle;

}


/* ======================================================
   BOTONES
====================================================== */

.btn-group-sm > .btn {

    padding:
        0.3rem
        0.55rem;

}


/* ======================================================
   DATATABLES
====================================================== */

.dataTables_wrapper
.dataTables_length
select {

    min-width:
        80px;

    font-weight:
        500;

    border:
        1px solid #dee2e6;

    border-radius:
        4px;

    padding:
        0.375rem
        0.75rem;

}


.dataTables_wrapper
.dataTables_filter
input {

    min-width:
        250px;

    border:
        1px solid #dee2e6;

    border-radius:
        4px;

    padding:
        0.375rem
        0.75rem;

}


.dataTables_info,
.dataTables_paginate {

    margin-top:
        1rem;

}


.paginate_button {

    font-weight:
        500;

    margin:
        0 2px;

    border-radius:
        4px;

}


.paginate_button.current {

    background-color:
        #0d6efd !important;

    border-color:
        #0d6efd !important;

    color:
        white !important;

}

/* ======================================================
   ENCABEZADO GESTIÓN DE USUARIOS
====================================================== */

.card-header.bg-primary h5,
.card-header.bg-primary h5 i,
.card-header.bg-primary small,
.card-header.bg-primary small i {
    color: #ffffff !important;
}

.card-header.bg-primary {
    background-color: #0d6efd !important;
}

.card-header.bg-primary .badge {
    color: #0d6efd !important;
    background-color: #ffffff !important;

</style>


<?php

include '../includes/footer.php';

?>