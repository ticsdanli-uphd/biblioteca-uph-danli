<?php
session_start();

require_once 'config/db.php';

$error = "";

/*
|--------------------------------------------------------------------------
| LOGIN BIBLIOTECA UPH
|--------------------------------------------------------------------------
| Permite iniciar sesión a:
| - Administrador
| - Docente
| - Alumno
|
| El formulario NO depende de que exista un administrador.
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {

        $error = "Ingrese su usuario y contraseña.";

    } else {

        /*
        |--------------------------------------------------------------------------
        | Buscar usuario
        |--------------------------------------------------------------------------
        */

        $stmt = $conn->prepare("
            SELECT
                id,
                username,
                nombre,
                password,
                role,
                sede_id
            FROM usuarios
            WHERE LOWER(username) = LOWER(?)
            LIMIT 1
        ");

        if (!$stmt) {

            $error = "No se pudo preparar la consulta de inicio de sesión.";

        } else {

            $stmt->bind_param("s", $username);
            $stmt->execute();

            $result = $stmt->get_result();

            if ($result && $result->num_rows === 1) {

                $user = $result->fetch_assoc();

                /*
                |--------------------------------------------------------------------------
                | Verificar contraseña
                |--------------------------------------------------------------------------
                */

                if (password_verify($password, $user['password'])) {

                    /*
                    |--------------------------------------------------------------------------
                    | Normalizar rol
                    |--------------------------------------------------------------------------
                    */

                    $rol_original = strtolower(trim($user['role'] ?? ''));

                    switch ($rol_original) {

                        case 'admin':
                        case 'administrador':
                        case 'administrator':
                            $rol = 'admin';
                            break;

                        case 'docente':
                        case 'profesor':
                        case 'teacher':
                            $rol = 'docente';
                            break;

                        case 'alumno':
                        case 'estudiante':
                        case 'student':
                            $rol = 'alumno';
                            break;

                        case 'usuario':
                        case 'user':
                            $rol = 'alumno';
                            break;

                        default:
                            $rol = $rol_original;
                            break;
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Crear sesión
                    |--------------------------------------------------------------------------
                    */

                    $_SESSION['user_id'] = (int)$user['id'];

                    $_SESSION['username'] = $user['username'];

                    $_SESSION['role'] = $rol;

                    $_SESSION['sede_id'] = !empty($user['sede_id'])
                        ? (int)$user['sede_id']
                        : 4;

                    $_SESSION['sede_seleccionada'] = $_SESSION['sede_id'];

                    $_SESSION['nombre_completo'] =
                        !empty($user['nombre'])
                        ? $user['nombre']
                        : $user['username'];

                    /*
                    |--------------------------------------------------------------------------
                    | VINCULAR CUENTA CON ALUMNO / DOCENTE
                    |--------------------------------------------------------------------------
                    */

                    $_SESSION['alumno_id'] = null;
                    $_SESSION['docente_id'] = null;

                    if ($rol === 'alumno') {

                        $stmtAlumno = $conn->prepare("
                            SELECT id
                            FROM alumnos
                            WHERE usuario_id = ?
                            LIMIT 1
                        ");

                        if ($stmtAlumno) {

                            $usuario_id = (int)$user['id'];

                            $stmtAlumno->bind_param(
                                'i',
                                $usuario_id
                            );

                            $stmtAlumno->execute();

                            $resultAlumno =
                                $stmtAlumno->get_result();

                            if (
                                $resultAlumno &&
                                $resultAlumno->num_rows === 1
                            ) {

                                $alumno =
                                    $resultAlumno->fetch_assoc();

                                $_SESSION['alumno_id'] =
                                    (int)$alumno['id'];
                            }

                            $stmtAlumno->close();
                        }

                    } elseif ($rol === 'docente') {

                        $stmtDocente = $conn->prepare("
                            SELECT id
                            FROM docentes
                            WHERE usuario_id = ?
                            LIMIT 1
                        ");

                        if ($stmtDocente) {

                            $usuario_id = (int)$user['id'];

                            $stmtDocente->bind_param(
                                'i',
                                $usuario_id
                            );

                            $stmtDocente->execute();

                            $resultDocente =
                                $stmtDocente->get_result();

                            if (
                                $resultDocente &&
                                $resultDocente->num_rows === 1
                            ) {

                                $docente =
                                    $resultDocente->fetch_assoc();

                                $_SESSION['docente_id'] =
                                    (int)$docente['id'];
                            }

                            $stmtDocente->close();
                        }
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Seguridad de sesión
                    |--------------------------------------------------------------------------
                    */

                    session_regenerate_id(true);

                    /*
                    |--------------------------------------------------------------------------
                    | Redirección
                    |--------------------------------------------------------------------------
                    |
                    | Todos ingresan al dashboard.
                    | El menú se controla mediante $_SESSION['role'].
                    |--------------------------------------------------------------------------
                    */

                    if (
                        $rol === 'alumno' &&
                        empty($_SESSION['alumno_id'])
                    ) {

                        session_unset();
                        session_destroy();

                        $error =
                            "La cuenta de alumno existe, pero no está vinculada "
                            . "a un registro de alumno. Contacte al administrador.";

                    } elseif (
                        $rol === 'docente' &&
                        empty($_SESSION['docente_id'])
                    ) {

                        session_unset();
                        session_destroy();

                        $error =
                            "La cuenta de docente existe, pero no está vinculada "
                            . "a un registro de docente. Contacte al administrador.";

                    } else {

                        header("Location: /biblioteca/dashboard.php");
                        exit();
                    }

                } else {

                    $error = "Usuario o contraseña incorrectos.";
                }

            } else {

                $error = "Usuario o contraseña incorrectos.";
            }

            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Iniciar Sesión - Biblioteca UPH</title>

    <!-- Bootstrap -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <!-- Google Fonts -->
    <link
        rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
    >

    <!-- Animate -->
    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"
    >

    <!-- Font Awesome -->
    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
    >

<style>

/* =========================================================
   VARIABLES
========================================================= */

:root {

    --primary-gradient:
        linear-gradient(
            135deg,
            #4facfe 0%,
            #00f2fe 100%
        );

    --secondary-gradient:
        linear-gradient(
            135deg,
            #ffd700 0%,
            #ffb347 100%
        );

    --success-gradient:
        linear-gradient(
            135deg,
            #4facfe 0%,
            #00f2fe 100%
        );

    --accent-gradient:
        linear-gradient(
            135deg,
            #43e97b 0%,
            #38f9d7 100%
        );

    --glass-bg:
        rgba(255,255,255,0.15);

    --glass-border:
        rgba(255,255,255,0.30);

    --shadow-light:
        0 8px 32px
        rgba(79,172,254,0.37);

    --shadow-heavy:
        0 20px 40px
        rgba(0,0,0,0.15);

    --text-primary:
        #1a1a1a;

    --text-secondary:
        rgba(26,26,26,0.80);

    --text-muted:
        rgba(26,26,26,0.60);
}


/* =========================================================
   RESET
========================================================= */

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}


/* =========================================================
   BODY
========================================================= */

body {

    font-family: 'Inter', sans-serif;

    background:
        linear-gradient(
            135deg,
            #4facfe 0%,
            #00f2fe 50%,
            #ffd700 100%
        );

    background-size: 400% 400%;

    animation:
        gradientShift 20s ease infinite;

    min-height: 100vh;

    overflow-x: hidden;

    position: relative;
}


/* =========================================================
   FONDO
========================================================= */

body::before {

    content: '';

    position: fixed;

    top: 0;
    left: 0;

    width: 100%;
    height: 100%;

    background:

        radial-gradient(
            circle at 20% 80%,
            rgba(79,172,254,0.30) 0%,
            transparent 50%
        ),

        radial-gradient(
            circle at 80% 20%,
            rgba(255,215,0,0.30) 0%,
            transparent 50%
        ),

        radial-gradient(
            circle at 40% 40%,
            rgba(120,219,255,0.20) 0%,
            transparent 50%
        );

    pointer-events: none;

    z-index: 1;
}


@keyframes gradientShift {

    0% {
        background-position: 0% 50%;
    }

    50% {
        background-position: 100% 50%;
    }

    100% {
        background-position: 0% 50%;
    }
}


/* =========================================================
   FIGURAS FLOTANTES
========================================================= */

.floating-shapes {

    position: fixed;

    top: 0;
    left: 0;

    width: 100%;
    height: 100%;

    pointer-events: none;

    z-index: 1;

    overflow: hidden;
}


.shape {

    position: absolute;

    border-radius: 50%;

    animation:
        float 8s ease-in-out infinite;

    opacity: 0.6;
}


.shape:nth-child(1) {

    width: 100px;
    height: 100px;

    top: 15%;
    left: 8%;

    background:
        linear-gradient(
            135deg,
            rgba(255,255,255,0.20),
            rgba(79,172,254,0.30)
        );

    animation-duration: 12s;
}


.shape:nth-child(2) {

    width: 150px;
    height: 150px;

    top: 55%;
    right: 8%;

    background:
        linear-gradient(
            135deg,
            rgba(255,215,0,0.30),
            rgba(255,179,71,0.20)
        );

    animation-delay: 3s;

    animation-duration: 15s;
}


.shape:nth-child(3) {

    width: 80px;
    height: 80px;

    bottom: 25%;
    left: 15%;

    background:
        linear-gradient(
            135deg,
            rgba(79,172,254,0.30),
            rgba(0,242,254,0.20)
        );

    animation-delay: 6s;

    animation-duration: 10s;
}


.shape:nth-child(4) {

    width: 60px;
    height: 60px;

    top: 30%;
    right: 25%;

    background:
        linear-gradient(
            135deg,
            rgba(67,233,123,0.30),
            rgba(56,249,215,0.20)
        );

    animation-delay: 9s;

    animation-duration: 14s;
}


.shape:nth-child(5) {

    width: 120px;
    height: 120px;

    bottom: 10%;
    right: 30%;

    background:
        linear-gradient(
            135deg,
            rgba(255,255,255,0.15),
            rgba(79,172,254,0.30)
        );

    animation-delay: 12s;

    animation-duration: 18s;
}


@keyframes float {

    0%,100% {

        transform:
            translateY(0)
            translateX(0)
            rotate(0deg)
            scale(1);

        opacity: 0.6;
    }

    25% {

        transform:
            translateY(-30px)
            translateX(20px)
            rotate(90deg)
            scale(1.1);

        opacity: 0.8;
    }

    50% {

        transform:
            translateY(-60px)
            translateX(0)
            rotate(180deg)
            scale(0.9);

        opacity: 0.4;
    }

    75% {

        transform:
            translateY(-30px)
            translateX(-20px)
            rotate(270deg)
            scale(1.05);

        opacity: 0.7;
    }
}


/* =========================================================
   CONTENEDOR LOGIN
========================================================= */

.login-container {

    min-height: 100vh;

    display: flex;

    align-items: center;

    justify-content: center;

    padding: 20px;

    position: relative;

    z-index: 2;
}


/* =========================================================
   TARJETA
========================================================= */

.login-card {

    max-width: 450px;

    width: 100%;

    background: var(--glass-bg);

    backdrop-filter: blur(25px);

    -webkit-backdrop-filter: blur(25px);

    border:
        2px solid
        var(--glass-border);

    border-radius: 24px;

    box-shadow:
        var(--shadow-light),
        0 0 60px
        rgba(79,172,254,0.20);

    padding: 45px 40px;

    position: relative;

    overflow: hidden;

    animation:
        slideInUp 1s ease-out;
}


.login-card::before {

    content: '';

    position: absolute;

    top: 0;
    left: 0;
    right: 0;

    height: 4px;

    background:
        var(--primary-gradient);

    border-radius:
        24px 24px 0 0;

    animation:
        shimmer 3s ease-in-out infinite;
}


.login-card::after {

    content: '';

    position: absolute;

    top: -2px;
    left: -2px;
    right: -2px;
    bottom: -2px;

    background:
        linear-gradient(
            45deg,
            rgba(79,172,254,0.30) 0%,
            rgba(255,215,0,0.30) 25%,
            rgba(79,172,254,0.30) 50%,
            rgba(67,233,123,0.30) 75%,
            rgba(79,172,254,0.30) 100%
        );

    border-radius: 26px;

    z-index: -1;

    animation:
        borderGlow 4s ease-in-out infinite;

    opacity: 0.6;
}


@keyframes shimmer {

    0%,100% {
        opacity: 1;
    }

    50% {
        opacity: 0.7;
    }
}


@keyframes borderGlow {

    0%,100% {

        opacity: 0.6;

        transform: scale(1);
    }

    50% {

        opacity: 0.8;

        transform: scale(1.02);
    }
}


@keyframes slideInUp {

    from {

        opacity: 0;

        transform:
            translateY(30px);
    }

    to {

        opacity: 1;

        transform:
            translateY(0);
    }
}


/* =========================================================
   LOGO
========================================================= */

.logo-container {

    text-align: center;

    margin-bottom: 30px;

    position: relative;
}


.logo-container::before {

    content: '';

    position: absolute;

    top: 50%;
    left: 50%;

    transform:
        translate(-50%,-50%);

    width: 180px;
    height: 130px;

    background:
        radial-gradient(
            circle,
            rgba(79,172,254,0.30) 0%,
            transparent 70%
        );

    border-radius: 50%;

    animation:
        logoGlow 3s ease-in-out infinite;

    z-index: -1;
}


.logo {

    width: 160px;

    height: 110px;

    border-radius: 20px;

    object-fit: contain;

    border:
        3px solid
        rgba(255,255,255,0.40);

    box-shadow:
        0 15px 35px
        rgba(0,0,0,0.20),

        0 5px 15px
        rgba(79,172,254,0.30);

    transition:
        all 0.4s
        cubic-bezier(
            0.175,
            0.885,
            0.32,
            1.275
        );

    animation:
        logoFloat 4s ease-in-out infinite;

    background:
        rgba(255,255,255,0.10);

    backdrop-filter:
        blur(10px);
}


.logo:hover {

    transform:
        scale(1.08)
        translateY(-5px);

    box-shadow:
        0 20px 40px
        rgba(0,0,0,0.30),

        0 10px 20px
        rgba(79,172,254,0.40);

    border-color:
        rgba(255,255,255,0.60);
}


@keyframes logoGlow {

    0%,100% {

        opacity: 0.6;

        transform:
            translate(-50%,-50%)
            scale(1);
    }

    50% {

        opacity: 1;

        transform:
            translate(-50%,-50%)
            scale(1.1);
    }
}


@keyframes logoFloat {

    0%,100% {

        transform:
            translateY(0)
            rotate(0deg);
    }

    50% {

        transform:
            translateY(-10px)
            rotate(1deg);
    }
}


/* =========================================================
   TITULO
========================================================= */

.login-title {

    font-weight: 800;

    font-size: 2rem;

    margin-bottom: 10px;

    text-align: center;

    color: #1a1a1a;

    letter-spacing: -0.5px;
}


.login-subtitle {

    text-align: center;

    color:
        rgba(26,26,26,0.70);

    font-size: 1rem;

    margin-bottom: 30px;

    font-weight: 500;

    letter-spacing: 0.5px;
}


/* =========================================================
   INPUTS
========================================================= */

.input-group {

    position: relative;

    margin-bottom: 20px;
}


.input-icon {

    position: absolute;

    left: 15px;

    top: 50%;

    transform:
        translateY(-50%);

    color:
        rgba(26,26,26,0.60);

    z-index: 10;

    font-size: 1.1rem;
}


.form-floating {

    width: 100%;
}


.form-control {

    background:
        rgba(255,255,255,0.90);

    border:
        2px solid
        rgba(255,255,255,0.15);

    border-radius: 16px;

    color:
        #1a1a1a;

    font-size: 1rem;

    padding:
        18px 20px 18px 45px;

    transition:
        all 0.4s
        cubic-bezier(
            0.4,
            0,
            0.2,
            1
        );

    backdrop-filter:
        blur(15px);

    font-weight: 500;
}


.form-control:focus {

    background:
        rgba(255,255,255,0.95);

    border-color:
        rgba(79,172,254,0.60);

    box-shadow:
        0 0 0 4px
        rgba(79,172,254,0.15),

        0 8px 25px
        rgba(79,172,254,0.20);

    color:
        #1a1a1a;

    transform:
        translateY(-2px);
}


.form-control::placeholder {

    color:
        rgba(26,26,26,0.50);
}


.form-floating label {

    color:
        rgba(26,26,26,0.70);

    font-weight: 600;
}


.form-floating >
.form-control:focus ~ label {

    color:
        #2196f3;
}


/* =========================================================
   BOTON PASSWORD
========================================================= */

.password-toggle {

    position: absolute;

    right: 15px;

    top: 50%;

    transform:
        translateY(-50%);

    background: none;

    border: none;

    color:
        rgba(26,26,26,0.55);

    cursor: pointer;

    z-index: 20;

    font-size: 1.1rem;
}


.password-toggle:hover {

    color:
        #2196f3;
}


/* =========================================================
   BOTON LOGIN
========================================================= */

.btn-login {

    background:
        var(--primary-gradient);

    border: none;

    border-radius: 16px;

    color:
        #ffffff;

    font-weight: 700;

    font-size: 0.95rem;

    padding:
        18px 24px;

    width: 100%;

    margin-top: 5px;

    transition:
        all 0.4s
        cubic-bezier(
            0.175,
            0.885,
            0.32,
            1.275
        );

    box-shadow:
        0 8px 25px
        rgba(79,172,254,0.30);

    letter-spacing: 0.5px;

    text-transform: uppercase;
}


.btn-login:hover {

    transform:
        translateY(-4px)
        scale(1.02);

    box-shadow:
        0 15px 35px
        rgba(79,172,254,0.40),

        0 5px 15px
        rgba(0,0,0,0.20);
}


.btn-login:active {

    transform:
        translateY(-2px)
        scale(1.01);
}


/* =========================================================
   ALERTAS
========================================================= */

.alert {

    border-radius: 12px;

    margin-bottom: 20px;

    backdrop-filter:
        blur(10px);

    font-weight: 500;
}


.alert-danger {

    background:
        rgba(239,68,68,0.20);

    border:
        1px solid
        rgba(239,68,68,0.35);

    color:
        #8b1e1e;
}


/* =========================================================
   INFORMACION DE ACCESO
========================================================= */

.access-info {

    margin-top: 25px;

    text-align: center;

    color:
        rgba(26,26,26,0.65);

    font-size: 0.85rem;
}


.access-info i {

    color:
        #2196f3;
}


.access-roles {

    display: flex;

    justify-content: center;

    gap: 8px;

    flex-wrap: wrap;

    margin-top: 12px;
}


.role-badge {

    background:
        rgba(255,255,255,0.35);

    border:
        1px solid
        rgba(255,255,255,0.30);

    border-radius: 20px;

    padding:
        6px 12px;

    font-size: 0.75rem;

    font-weight: 600;

    color:
        #263238;
}


/* =========================================================
   RESPONSIVE
========================================================= */

@media (max-width: 576px) {

    .login-container {

        padding: 15px;
    }

    .login-card {

        padding:
            35px 25px;

        border-radius: 20px;
    }

    .login-title {

        font-size: 1.6rem;
    }

    .login-subtitle {

        font-size: 0.9rem;
    }

    .logo {

        width: 140px;

        height: 95px;
    }

    .shape:nth-child(2) {

        width: 90px;

        height: 90px;
    }
}


</style>

</head>


<body>


<!-- =====================================================
     FIGURAS DE FONDO
====================================================== -->

<div class="floating-shapes">

    <div class="shape"></div>

    <div class="shape"></div>

    <div class="shape"></div>

    <div class="shape"></div>

    <div class="shape"></div>

</div>


<!-- =====================================================
     LOGIN
====================================================== -->

<div class="login-container">

    <div
        class="
            login-card
            animate__animated
            animate__fadeInUp
        "
    >


        <!-- LOGO -->

        <div class="logo-container">

            <img
                src="/biblioteca/imagenes/logouph.png"
                alt="Logo Universidad Politécnica de Honduras"
                class="logo"
            >

        </div>


        <!-- TITULO -->

        <h2 class="login-title">
            Biblioteca UPH
        </h2>


        <p class="login-subtitle">
            Sistema de Gestión Bibliotecaria
        </p>


        <!-- ERROR -->

        <?php if (!empty($error)): ?>

            <div
                class="
                    alert
                    alert-danger
                    text-center
                    animate__animated
                    animate__shakeX
                "
                role="alert"
            >

                <i class="fas fa-exclamation-triangle me-2"></i>

                <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>

            </div>

        <?php endif; ?>


        <!-- =================================================
             FORMULARIO
        ================================================== -->

        <form
            method="post"
            action="login.php"
            id="loginForm"
            autocomplete="on"
        >


            <!-- USUARIO -->

            <div class="input-group">

                <i
                    class="fas fa-user input-icon"
                ></i>

                <div class="form-floating">

                    <input
                        type="text"
                        name="username"
                        id="username"
                        class="form-control"
                        placeholder="Usuario"
                        autocomplete="username"
                        required
                        autofocus
                    >

                    <label for="username">
                        Usuario
                    </label>

                </div>

            </div>


            <!-- CONTRASEÑA -->

            <div class="input-group mb-4">

                <i
                    class="fas fa-lock input-icon"
                ></i>

                <div class="form-floating">

                    <input
                        type="password"
                        name="password"
                        id="password"
                        class="form-control"
                        placeholder="Contraseña"
                        autocomplete="current-password"
                        required
                    >

                    <label for="password">
                        Contraseña
                    </label>

                    <button
                        type="button"
                        class="password-toggle"
                        id="passwordToggle"
                        aria-label="Mostrar contraseña"
                    >

                        <i
                            class="fas fa-eye"
                            id="toggleIcon"
                        ></i>

                    </button>

                </div>

            </div>


            <!-- BOTON -->

            <button
                type="submit"
                class="btn btn-login"
                id="loginBtn"
            >

                <i
                    class="fas fa-sign-in-alt me-2"
                    id="loginIcon"
                ></i>

                <span id="btnText">
                    Iniciar Sesión
                </span>

                <span
                    class="
                        spinner-border
                        spinner-border-sm
                        d-none
                    "
                    id="loginSpinner"
                    role="status"
                ></span>

            </button>


        </form>


        <!-- =================================================
             TIPOS DE USUARIO
        ================================================== -->

        <div class="access-info">

            <div>
                <i class="fas fa-shield-halved me-1"></i>

                Acceso autorizado para:
            </div>


            <div class="access-roles">

                <span class="role-badge">
                    <i class="fas fa-user-shield me-1"></i>
                    Administrador
                </span>

                <span class="role-badge">
                    <i class="fas fa-chalkboard-user me-1"></i>
                    Docente
                </span>

                <span class="role-badge">
                    <i class="fas fa-user-graduate me-1"></i>
                    Alumno
                </span>

            </div>

        </div>


    </div>

</div>


<!-- Bootstrap -->

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"
></script>


<script>

/* =========================================================
   MOSTRAR / OCULTAR CONTRASEÑA
========================================================= */

const passwordInput =
    document.getElementById('password');

const passwordToggle =
    document.getElementById('passwordToggle');

const toggleIcon =
    document.getElementById('toggleIcon');


passwordToggle.addEventListener(
    'click',
    function () {

        if (
            passwordInput.type === 'password'
        ) {

            passwordInput.type = 'text';

            toggleIcon.classList.remove(
                'fa-eye'
            );

            toggleIcon.classList.add(
                'fa-eye-slash'
            );

            passwordToggle.setAttribute(
                'aria-label',
                'Ocultar contraseña'
            );

        } else {

            passwordInput.type = 'password';

            toggleIcon.classList.remove(
                'fa-eye-slash'
            );

            toggleIcon.classList.add(
                'fa-eye'
            );

            passwordToggle.setAttribute(
                'aria-label',
                'Mostrar contraseña'
            );
        }
    }
);


/* =========================================================
   CARGANDO AL INICIAR SESIÓN
========================================================= */

const loginForm =
    document.getElementById('loginForm');

const loginBtn =
    document.getElementById('loginBtn');

const btnText =
    document.getElementById('btnText');

const loginSpinner =
    document.getElementById('loginSpinner');

const loginIcon =
    document.getElementById('loginIcon');


loginForm.addEventListener(
    'submit',
    function () {

        loginBtn.disabled = true;

        btnText.textContent =
            'Verificando...';

        loginIcon.classList.add(
            'd-none'
        );

        loginSpinner.classList.remove(
            'd-none'
        );
    }
);


/* =========================================================
   EFECTO DE ENTRADA
========================================================= */

window.addEventListener(
    'load',
    function () {

        document.body.style.opacity = '0';

        document.body.style.transition =
            'opacity 0.5s ease';

        setTimeout(
            function () {

                document.body.style.opacity = '1';

            },
            100
        );
    }
);


/* =========================================================
   PARTÍCULAS
========================================================= */

function createFloatingParticles() {

    const container =
        document.querySelector(
            '.floating-shapes'
        );

    if (!container) {
        return;
    }


    const colors = [

        'linear-gradient(135deg, rgba(255,255,255,0.15), rgba(79,172,254,0.20))',

        'linear-gradient(135deg, rgba(255,215,0,0.20), rgba(255,179,71,0.15))',

        'linear-gradient(135deg, rgba(79,172,254,0.20), rgba(0,242,254,0.15))',

        'linear-gradient(135deg, rgba(67,233,123,0.20), rgba(56,249,215,0.15))'
    ];


    for (
        let i = 0;
        i < 8;
        i++
    ) {

        const particle =
            document.createElement('div');

        particle.className =
            'shape';


        const size =
            Math.random() * 60 + 30;


        particle.style.width =
            size + 'px';

        particle.style.height =
            size + 'px';


        particle.style.left =
            Math.random() * 100 + '%';

        particle.style.top =
            Math.random() * 100 + '%';


        particle.style.background =
            colors[
                Math.floor(
                    Math.random() *
                    colors.length
                )
            ];


        particle.style.animationDelay =
            Math.random() * 8 + 's';


        particle.style.animationDuration =
            Math.random() * 6 + 8 + 's';


        particle.style.opacity =
            Math.random() * 0.4 + 0.3;


        container.appendChild(
            particle
        );
    }
}


createFloatingParticles();


/* =========================================================
   ENTER EN USUARIO → PASSWORD
========================================================= */

document.addEventListener(
    'keydown',
    function (e) {

        if (e.key === 'Enter') {

            const active =
                document.activeElement;

            if (
                active &&
                active.id === 'username'
            ) {

                e.preventDefault();

                passwordInput.focus();
            }
        }
    }
);


/* =========================================================
   OCULTAR ERROR DESPUÉS DE 5 SEGUNDOS
========================================================= */

const errorAlert =
    document.querySelector(
        '.alert-danger'
    );


if (errorAlert) {

    setTimeout(
        function () {

            errorAlert.style.opacity =
                '0';

            errorAlert.style.transform =
                'translateY(-10px)';

            errorAlert.style.transition =
                'all 0.3s ease';


            setTimeout(
                function () {

                    errorAlert.style.display =
                        'none';

                },
                300
            );

        },
        5000
    );
}

</script>


</body>

</html>