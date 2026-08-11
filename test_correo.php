<?php

require_once __DIR__ . '/config/mail.php';

$destinatario =
    'TU_CORREO_DE_PRUEBA@gmail.com';

$nombre =
    'Prueba';


try {

    $mail =
        crearMailer('tics');


    $mail->addAddress(
        $destinatario,
        $nombre
    );


    $mail->Subject =
        'Prueba SMTP - Biblioteca UPH';


    $mail->Body = '

        <h2>
            Biblioteca UPH - Danlí
        </h2>

        <p>
            Esta es una prueba del sistema
            de correo.
        </p>

        <p>
            Correo remitente:
        </p>

        <strong>
            tics.danli@uph.edu.hn
        </strong>

    ';


    $mail->AltBody =
        'Prueba de correo de Biblioteca UPH Danlí';


    $mail->send();


    echo '

        <div style="
            max-width:600px;
            margin:50px auto;
            padding:30px;
            background:#d1e7dd;
            color:#0f5132;
            border-radius:12px;
            font-family:Arial;
        ">

            <h2>
                ✓ Correo enviado correctamente
            </h2>

            <p>
                El correo fue enviado desde:
            </p>

            <strong>
                tics.danli@uph.edu.hn
            </strong>

        </div>

    ';


} catch (
    Exception $e
) {

    echo '

        <div style="
            max-width:700px;
            margin:50px auto;
            padding:30px;
            background:#f8d7da;
            color:#842029;
            border-radius:12px;
            font-family:Arial;
        ">

            <h2>
                ✗ Error SMTP
            </h2>

            <p>
            ' .
            htmlspecialchars(
                $mail->ErrorInfo
            )
            .
            '
            </p>

        </div>

    ';
}