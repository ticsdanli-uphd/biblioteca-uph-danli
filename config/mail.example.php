<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$autoload = __DIR__ . '/../vendor/autoload.php';
if (!file_exists($autoload)) {
    die('No se encontró PHPMailer. Ejecute: composer install');
}
require_once $autoload;

define('MAIL_TICS', 'tics.danli@uph.edu.hn');
define('MAIL_BIBLIOTECA', 'biblioteca.danli@uph.edu.hn');

define('MAIL_NAME_TICS', 'TICS Danlí - UPH');
define('MAIL_NAME_BIBLIOTECA', 'Biblioteca UPH - Danlí');

/*
 * Coloque aquí las CONTRASEÑAS DE APLICACIÓN.
 * No utilice códigos de respaldo.
 */
define('MAIL_TICS_PASSWORD', 'TU_CONTRASENA_DE_APLICACION');
define('MAIL_BIBLIOTECA_PASSWORD', 'TU_CONTRASENA_DE_APLICACION');

define('MAIL_HOST', 'smtp.gmail.com');
define('MAIL_PORT', 587);
define('MAIL_DEFAULT_ACCOUNT', 'tics');

function crearMailer(?string $cuenta = null): PHPMailer {
    $cuenta = strtolower(trim($cuenta ?: MAIL_DEFAULT_ACCOUNT));
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = MAIL_HOST;
    $mail->SMTPAuth = true;
    $mail->Port = MAIL_PORT;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->CharSet = 'UTF-8';
    $mail->Encoding = 'base64';
    $mail->isHTML(true);
    $mail->Timeout = 30;

    if ($cuenta === 'biblioteca') {
        $usuario = MAIL_BIBLIOTECA;
        $password = MAIL_BIBLIOTECA_PASSWORD;
        $nombre = MAIL_NAME_BIBLIOTECA;
    } else {
        $usuario = MAIL_TICS;
        $password = MAIL_TICS_PASSWORD;
        $nombre = MAIL_NAME_TICS;
    }

    $mail->Username = $usuario;
    $mail->Password = $password;
    $mail->setFrom($usuario, $nombre);
    return $mail;
}

function enviarCorreo(string $destinatario, string $nombre, string $asunto, string $mensaje, ?string $cuenta = null): bool {
    try {
        $mail = crearMailer($cuenta);
        $mail->addAddress($destinatario, $nombre);
        $mail->Subject = $asunto;
        $mail->Body = $mensaje;
        $mail->AltBody = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $mensaje));
        return $mail->send();
    } catch (Exception $e) {
        error_log('Biblioteca SMTP: ' . $e->getMessage());
        return false;
    }
}
