<?php

session_start();

include 'config/db.php';

/*
==========================================================
   CONFIGURACIÓN DEL SISTEMA
   SISTEMA EXCLUSIVO PARA DANLÍ
==========================================================
*/

$sede_id = 6;
$sede_nombre = "Danlí";


/*
==========================================================
   VERIFICAR SI YA EXISTE UN ADMINISTRADOR
==========================================================
*/

$sqlAdminCheck = "
    SELECT id
    FROM usuarios
    WHERE role = 'admin'
    LIMIT 1
";

$resultAdminCheck = $conn->query($sqlAdminCheck);

if ($resultAdminCheck && $resultAdminCheck->num_rows > 0) {

    include 'includes/header.php';

    echo "
    <div class='container my-4'>

        <div class='alert alert-info'>

            <h5 class='mb-2'>
                <i class='fas fa-info-circle me-2'></i>
                Administrador existente
            </h5>

            El usuario administrador ya existe.

            <a href='login.php' class='alert-link'>
                Inicie sesión
            </a>.

        </div>

    </div>
    ";

    include 'includes/footer.php';

    exit();
}


$error_msg = "";
$success_msg = "";


/*
==========================================================
   PROCESAR FORMULARIO
==========================================================
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /*
    ------------------------------------------------------
       RECIBIR DATOS
    ------------------------------------------------------
    */

    $email = trim($_POST['email'] ?? '');

    $password = $_POST['password'] ?? '';

    $confirm_password = $_POST['confirm_password'] ?? '';


    /*
    ------------------------------------------------------
       VALIDAR CAMPOS
    ------------------------------------------------------
    */

    if (
        empty($email) ||
        empty($password) ||
        empty($confirm_password)
    ) {

        $error_msg =
            "Todos los campos son obligatorios.";

    }

    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error_msg =
            "Ingrese un correo electrónico válido.";

    }

    elseif ($password !== $confirm_password) {

        $error_msg =
            "Las contraseñas no coinciden.";

    }

    elseif (strlen($password) < 6) {

        $error_msg =
            "La contraseña debe tener al menos 6 caracteres.";

    }

    else {

        /*
        --------------------------------------------------
           VERIFICAR SI EL USUARIO YA EXISTE
        --------------------------------------------------
        */

        $stmtCheck = $conn->prepare("
            SELECT id
            FROM usuarios
            WHERE username = ?
            LIMIT 1
        ");

        $stmtCheck->bind_param(
            "s",
            $email
        );

        $stmtCheck->execute();

        $resultCheck =
            $stmtCheck->get_result();


        if ($resultCheck->num_rows > 0) {

            $error_msg =
                "El email ya está registrado.";

        }

        else {

            /*
            ----------------------------------------------
               GENERAR HASH DE CONTRASEÑA
            ----------------------------------------------
            */

            $hashedPassword =
                password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );


            /*
            ----------------------------------------------
               CREAR ADMINISTRADOR
               SEDE FIJA: DANLÍ
            ----------------------------------------------
            */

            $role = "admin";


            $stmtInsert = $conn->prepare("
                INSERT INTO usuarios
                (
                    username,
                    password,
                    role,
                    sede_id
                )
                VALUES
                (
                    ?,
                    ?,
                    ?,
                    ?
                )
            ");


            $stmtInsert->bind_param(
                "sssi",
                $email,
                $hashedPassword,
                $role,
                $sede_id
            );


            if ($stmtInsert->execute()) {

                $success_msg = "
                    Usuario administrador creado correctamente
                    para la sede <strong>Danlí</strong>.

                    <br><br>

                    <a
                        href='login.php'
                        class='btn btn-success btn-sm'
                    >
                        <i class='fas fa-sign-in-alt me-1'></i>
                        Iniciar sesión
                    </a>
                ";

            }

            else {

                $error_msg =
                    "Error al crear el administrador: "
                    . $stmtInsert->error;

            }


            $stmtInsert->close();

        }


        $stmtCheck->close();

    }

}


/*
==========================================================
   HEADER
==========================================================
*/

include 'includes/header.php';

?>


<div class="container my-4">

    <div class="card shadow-sm">

        <!-- HEADER -->

        <div class="card-header bg-primary text-white">

            <h2 class="mb-0">

                <i class="fas fa-user-shield me-2"></i>

                Crear Usuario Administrador

            </h2>

        </div>


        <!-- CUERPO -->

        <div class="card-body">


            <!-- MENSAJE DE ERROR -->

            <?php if (!empty($error_msg)): ?>

                <div class="alert alert-danger">

                    <i class="fas fa-exclamation-triangle me-2"></i>

                    <?php echo $error_msg; ?>

                </div>

            <?php endif; ?>


            <!-- MENSAJE DE ÉXITO -->

            <?php if (!empty($success_msg)): ?>

                <div class="alert alert-success">

                    <i class="fas fa-check-circle me-2"></i>

                    <?php echo $success_msg; ?>

                </div>

            <?php endif; ?>


            <?php if (empty($success_msg)): ?>


                <!-- INFORMACIÓN DE SEDE -->

                <div class="alert alert-info">

                    <i class="fas fa-map-marker-alt me-2"></i>

                    <strong>Sede:</strong> Danlí

                    <br>

                    <small class="text-muted">

                        Este sistema está configurado
                        exclusivamente para la sede Danlí.

                    </small>

                </div>


                <!-- FORMULARIO -->

                <form
                    method="post"
                    action="setup_admin.php"
                >


                    <!-- EMAIL -->

                    <div class="mb-3 form-floating">

                        <input
                            type="email"
                            name="email"
                            class="form-control"
                            id="email"
                            placeholder="Email"
                            required
                        >

                        <label for="email">

                            Correo electrónico

                        </label>

                    </div>


                    <!-- CONTRASEÑA -->

                    <div class="mb-3 form-floating">

                        <input
                            type="password"
                            name="password"
                            class="form-control"
                            id="password"
                            placeholder="Contraseña"
                            minlength="6"
                            required
                        >

                        <label for="password">

                            Contraseña

                        </label>

                    </div>


                    <!-- CONFIRMAR CONTRASEÑA -->

                    <div class="mb-3 form-floating">

                        <input
                            type="password"
                            name="confirm_password"
                            class="form-control"
                            id="confirm_password"
                            placeholder="Confirmar Contraseña"
                            minlength="6"
                            required
                        >

                        <label for="confirm_password">

                            Confirmar Contraseña

                        </label>

                    </div>


                    <!-- SEDE FIJA -->

                    <div class="mb-3 form-floating">

                        <input
                            type="text"
                            class="form-control"
                            value="Danlí"
                            readonly
                        >

                        <label>

                            Sede

                        </label>

                    </div>


                    <!-- BOTÓN -->

                    <button
                        type="submit"
                        class="btn btn-primary"
                    >

                        <i class="fas fa-user-plus me-2"></i>

                        Crear Administrador

                    </button>


                </form>


            <?php endif; ?>


        </div>

    </div>

</div>


<?php

include 'includes/footer.php';

?>