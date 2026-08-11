<?php

/**
 * ============================================================
 * ENVÍO DE RECORDATORIO DE PRÉSTAMO
 * BIBLIOTECA UPH - DANLÍ
 * ============================================================
 */

session_start();


/*
|--------------------------------------------------------------------------
| CONFIGURACIONES
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/config/db.php';

require_once __DIR__ . '/config/mail.php';


/*
|--------------------------------------------------------------------------
| VERIFICAR SESIÓN
|--------------------------------------------------------------------------
*/

if (
    !isset(
        $_SESSION['user_id']
    )
) {

    header(
        'Location: login.php'
    );

    exit();
}


/*
|--------------------------------------------------------------------------
| OBTENER ID DEL PRÉSTAMO
|--------------------------------------------------------------------------
*/

$id =
    isset($_GET['id'])
        ? intval($_GET['id'])
        : 0;


/*
|--------------------------------------------------------------------------
| CUENTA REMITENTE
|--------------------------------------------------------------------------
|
| TICS es la cuenta principal.
|
*/

$remitente =
    isset($_GET['remitente'])
        ? strtolower(
            trim(
                $_GET['remitente']
            )
        )
        : 'tics';


/*
|--------------------------------------------------------------------------
| VALIDAR REMITENTE
|--------------------------------------------------------------------------
*/

if (
    $remitente !== 'tics'
    &&
    $remitente !== 'biblioteca'
) {

    $remitente =
        'tics';
}


/*
|--------------------------------------------------------------------------
| VALIDAR ID
|--------------------------------------------------------------------------
*/

if (
    $id <= 0
) {

    $_SESSION['error'] =
        'El préstamo seleccionado no es válido.';

    header(
        'Location: alertas.php'
    );

    exit();
}


/*
|--------------------------------------------------------------------------
| CONSULTAR PRÉSTAMO
|--------------------------------------------------------------------------
*/

$sql = "

    SELECT

        r.id,

        r.fecha,

        r.fecha_devolucion_esperada,

        r.nombre_alumno,

        r.devuelto,

        r.observaciones,

        r.es_externo,

        b.nombre AS libro_nombre,

        b.codigo AS codigo,

        c.nombre AS carrera_nombre,

        a.email AS alumno_email,

        a.telefono AS alumno_telefono

    FROM registro_visitas r

    LEFT JOIN bibliografia b

        ON r.bibliografia_id = b.id

    LEFT JOIN carreras c

        ON r.carrera_id = c.id

    LEFT JOIN alumnos a

        ON TRIM(
            LOWER(a.nombre)
        )
        =
        TRIM(
            LOWER(r.nombre_alumno)
        )

    WHERE r.id = ?

      AND r.tipo = 'prestamo'

    LIMIT 1

";


$stmt =
    $conn->prepare(
        $sql
    );


if (!$stmt) {

    $_SESSION['error'] =
        'Error al preparar la consulta: '
        .
        $conn->error;

    header(
        'Location: alertas.php'
    );

    exit();
}


$stmt->bind_param(
    'i',
    $id
);


$stmt->execute();


$result =
    $stmt->get_result();


/*
|--------------------------------------------------------------------------
| VERIFICAR EXISTENCIA
|--------------------------------------------------------------------------
*/

if (
    $result->num_rows === 0
) {

    $stmt->close();

    $_SESSION['error'] =
        'El préstamo no existe.';

    header(
        'Location: alertas.php'
    );

    exit();
}


$prestamo =
    $result->fetch_assoc();


$stmt->close();


/*
|--------------------------------------------------------------------------
| VERIFICAR SI YA FUE DEVUELTO
|--------------------------------------------------------------------------
*/

if (
    (int)$prestamo['devuelto']
    ===
    1
) {

    $_SESSION['error'] =
        'Este préstamo ya fue devuelto.';

    header(
        'Location: alertas.php'
    );

    exit();
}


/*
|--------------------------------------------------------------------------
| OBTENER CORREO
|--------------------------------------------------------------------------
*/

$emailAlumno =
    trim(
        $prestamo['alumno_email']
        ??
        ''
    );


/*
|--------------------------------------------------------------------------
| VALIDAR CORREO
|--------------------------------------------------------------------------
*/

if (
    empty($emailAlumno)
) {

    $_SESSION['error'] =
        'El alumno no tiene un correo registrado.';

    header(
        'Location: alertas.php'
    );

    exit();
}


if (
    !filter_var(
        $emailAlumno,
        FILTER_VALIDATE_EMAIL
    )
) {

    $_SESSION['error'] =
        'El correo del alumno no es válido: '
        .
        $emailAlumno;

    header(
        'Location: alertas.php'
    );

    exit();
}


/*
|--------------------------------------------------------------------------
| DATOS
|--------------------------------------------------------------------------
*/

$nombreAlumno =
    trim(
        $prestamo['nombre_alumno']
        ??
        'Alumno'
    );


$nombreLibro =
    $prestamo['libro_nombre']
    ??
    'Libro no especificado';


$codigo =
    $prestamo['codigo']
    ??
    'N/A';


$carrera =
    $prestamo['carrera_nombre']
    ??
    'No especificada';


/*
|--------------------------------------------------------------------------
| FECHA PRÉSTAMO
|--------------------------------------------------------------------------
*/

if (
    !empty(
        $prestamo['fecha']
    )
) {

    $fechaPrestamo =
        date(
            'd/m/Y H:i',
            strtotime(
                $prestamo['fecha']
            )
        );

} else {

    $fechaPrestamo =
        'No disponible';
}


/*
|--------------------------------------------------------------------------
| FECHA DEVOLUCIÓN
|--------------------------------------------------------------------------
*/

if (
    !empty(
        $prestamo[
            'fecha_devolucion_esperada'
        ]
    )
) {

    $fechaDevolucion =
        date(
            'd/m/Y',
            strtotime(
                $prestamo[
                    'fecha_devolucion_esperada'
                ]
            )
        );

} else {

    $fechaDevolucion =
        'No especificada';
}


/*
|--------------------------------------------------------------------------
| DETERMINAR ESTADO
|--------------------------------------------------------------------------
*/

$hoy =
    strtotime(
        date('Y-m-d')
    );


$fechaDev =
    !empty(
        $prestamo[
            'fecha_devolucion_esperada'
        ]
    )
        ? strtotime(
            $prestamo[
                'fecha_devolucion_esperada'
            ]
        )
        : 0;


if (
    $fechaDev
    &&
    $fechaDev < $hoy
) {

    $estado =
        'PRÉSTAMO VENCIDO';

    $colorEstado =
        '#dc3545';

}

elseif (
    $fechaDev
    &&
    $fechaDev == $hoy
) {

    $estado =
        'VENCE HOY';

    $colorEstado =
        '#dc3545';

}

elseif (
    $fechaDev
    &&
    $fechaDev ==
    strtotime(
        '+1 day'
    )
) {

    $estado =
        'VENCE MAÑANA';

    $colorEstado =
        '#f59f00';

}

else {

    $estado =
        'PRÉSTAMO PENDIENTE';

    $colorEstado =
        '#0d6efd';
}


/*
|--------------------------------------------------------------------------
| NOMBRE DEL REMITENTE
|--------------------------------------------------------------------------
*/

if (
    $remitente === 'biblioteca'
) {

    $correoRemitente =
        MAIL_BIBLIOTECA;

    $nombreRemitente =
        MAIL_NAME_BIBLIOTECA;

} else {

    /*
    | TICS = PRINCIPAL
    */

    $correoRemitente =
        MAIL_TICS;

    $nombreRemitente =
        MAIL_NAME_TICS;
}


/*
|--------------------------------------------------------------------------
| ASUNTO
|--------------------------------------------------------------------------
*/

if (
    $estado ===
    'PRÉSTAMO VENCIDO'
) {

    $asunto =
        '⚠️ Préstamo vencido - Biblioteca UPH';

}

elseif (
    $estado ===
    'VENCE HOY'
) {

    $asunto =
        '🔔 Recordatorio de devolución - Biblioteca UPH';

}

else {

    $asunto =
        '📚 Recordatorio de préstamo - Biblioteca UPH';
}


/*
|--------------------------------------------------------------------------
| CREAR CORREO
|--------------------------------------------------------------------------
*/

try {

    $mail =
        crearMailer(
            $remitente
        );


    /*
    |--------------------------------------------------------------------------
    | DESTINATARIO
    |--------------------------------------------------------------------------
    */

    $mail->addAddress(
        $emailAlumno,
        $nombreAlumno
    );


    /*
    |--------------------------------------------------------------------------
    | RESPUESTA
    |--------------------------------------------------------------------------
    */

    $mail->addReplyTo(
        $correoRemitente,
        $nombreRemitente
    );


    /*
    |--------------------------------------------------------------------------
    | ASUNTO
    |--------------------------------------------------------------------------
    */

    $mail->Subject =
        $asunto;


    /*
    |--------------------------------------------------------------------------
    | MENSAJE HTML
    |--------------------------------------------------------------------------
    */

    $mail->Body = '

<!DOCTYPE html>

<html lang="es">

<head>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width,initial-scale=1.0">

<title>
Recordatorio de préstamo
</title>

</head>


<body style="
    margin:0;
    padding:0;
    background:#f4f6f9;
    font-family:Arial,Helvetica,sans-serif;
">


<div style="
    max-width:650px;
    margin:30px auto;
    background:#ffffff;
    border-radius:14px;
    overflow:hidden;
    box-shadow:0 4px 18px rgba(0,0,0,.10);
">


<!-- ENCABEZADO -->

<div style="
    background:#342c74;
    color:#ffffff;
    padding:28px;
    text-align:center;
">

    <h1 style="
        margin:0;
        font-size:26px;
    ">

        Biblioteca UPH

    </h1>


    <p style="
        margin:8px 0 0;
        font-size:15px;
    ">

        Centro Asociado Danlí

    </p>

</div>



<!-- CONTENIDO -->

<div style="
    padding:30px;
">


    <h2 style="
        margin-top:0;
        color:#222222;
    ">

        Estimado(a)
        ' .
        htmlspecialchars(
            $nombreAlumno
        )
        . '

    </h2>


    <p style="
        color:#555555;
        font-size:16px;
        line-height:1.6;
    ">

        Por medio del presente correo le
        recordamos que tiene un material
        bibliográfico pendiente de devolución
        en la Biblioteca UPH.

    </p>



    <!-- INFORMACIÓN DEL PRÉSTAMO -->

    <div style="
        background:#f7f8fb;
        border-radius:10px;
        padding:20px;
        margin:25px 0;
    ">


        <p>

            <strong>
                📚 Libro:
            </strong>

            <br>

            ' .
            htmlspecialchars(
                $nombreLibro
            )
            . '

        </p>


        <p>

            <strong>
                🔖 Código:
            </strong>

            <br>

            ' .
            htmlspecialchars(
                $codigo
            )
            . '

        </p>


        <p>

            <strong>
                📅 Fecha del préstamo:
            </strong>

            <br>

            ' .
            htmlspecialchars(
                $fechaPrestamo
            )
            . '

        </p>


        <p>

            <strong>
                📅 Fecha de devolución:
            </strong>

            <br>

            ' .
            htmlspecialchars(
                $fechaDevolucion
            )
            . '

        </p>


        <p>

            <strong>
                🎓 Carrera:
            </strong>

            <br>

            ' .
            htmlspecialchars(
                $carrera
            )
            . '

        </p>


    </div>



    <!-- ESTADO -->

    <div style="
        background:' .
        $colorEstado
        . ';
        color:#ffffff;
        padding:15px;
        border-radius:8px;
        text-align:center;
        font-weight:bold;
        margin-bottom:25px;
    ">

        ' .
        htmlspecialchars(
            $estado
        )
        . '

    </div>



    <p style="
        color:#555555;
        line-height:1.6;
    ">

        Agradecemos realizar la devolución
        del material bibliográfico dentro del
        plazo establecido.

    </p>


    <p style="
        color:#555555;
        line-height:1.6;
    ">

        Si ya realizó la devolución,
        puede omitir este mensaje.

    </p>


    <br>


    <p style="
        color:#555555;
    ">

        Atentamente,

    </p>


    <p style="
        color:#342c74;
        font-weight:bold;
        font-size:17px;
        margin-bottom:3px;
    ">

        Biblioteca UPH Danlí

    </p>


    <p style="
        color:#777777;
        margin-top:0;
    ">

        Universidad Politécnica de Honduras

    </p>


</div>



<!-- PIE -->

<div style="
    background:#f1f3f5;
    padding:18px;
    text-align:center;
    font-size:12px;
    color:#777777;
">

    Sistema de Biblioteca UPH

    <br>

    Enviado desde:
    ' .
    htmlspecialchars(
        $correoRemitente
    )
    . '

</div>


</div>


</body>

</html>

';


    /*
    |--------------------------------------------------------------------------
    | VERSIÓN TEXTO
    |--------------------------------------------------------------------------
    */

    $mail->AltBody =

        "Biblioteca UPH - Centro Asociado Danlí\n\n"

        .

        "Estimado(a) "
        .
        $nombreAlumno
        .
        "\n\n"

        .

        "Tiene un material bibliográfico "
        .
        "pendiente de devolución.\n\n"

        .

        "Libro: "
        .
        $nombreLibro
        .
        "\n"

        .

        "Código: "
        .
        $codigo
        .
        "\n"

        .

        "Fecha del préstamo: "
        .
        $fechaPrestamo
        .
        "\n"

        .

        "Fecha de devolución: "
        .
        $fechaDevolucion
        .
        "\n"

        .

        "Carrera: "
        .
        $carrera
        .
        "\n\n"

        .

        "Estado: "
        .
        $estado
        .
        "\n\n"

        .

        "Atentamente,\n"

        .

        "Biblioteca UPH Danlí\n"

        .

        "Universidad Politécnica de Honduras";


    /*
    |--------------------------------------------------------------------------
    | ENVIAR
    |--------------------------------------------------------------------------
    */

    $mail->send();


    /*
    |--------------------------------------------------------------------------
    | MENSAJE DE ÉXITO
    |--------------------------------------------------------------------------
    */

    $_SESSION['success'] =

        'El recordatorio fue enviado correctamente a '
        .
        $emailAlumno
        .
        ' desde '
        .
        $correoRemitente
        .
        '.';


} catch (
    Exception $e
) {


    /*
    |--------------------------------------------------------------------------
    | ERROR
    |--------------------------------------------------------------------------
    */

    $_SESSION['error'] =

        'No se pudo enviar el correo desde '
        .
        $correoRemitente
        .
        '. Error SMTP: '
        .
        $mail->ErrorInfo;
}


/*
|--------------------------------------------------------------------------
| REGRESAR A ALERTAS
|--------------------------------------------------------------------------
*/

header(
    'Location: alertas.php'
);

exit();