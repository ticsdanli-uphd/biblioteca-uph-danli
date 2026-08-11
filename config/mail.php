<?php
/**
 * Biblioteca UPH - configuración de correo.
 *
 * IMPORTANTE:
 * Las contraseñas NO deben guardarse en Git.
 * Para XAMPP local puedes crear config/mail.local.php (ignorado por Git)
 * que defina MAIL_TICS_PASSWORD y MAIL_BIBLIOTECA_PASSWORD.
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$autoload = __DIR__ . '/../vendor/autoload.php';

if (!file_exists($autoload)) {
    die('No se encontró PHPMailer. Ejecuta: composer install');
}
require_once $autoload;

define('MAIL_TICS', getenv('MAIL_TICS') ?: 'tics.danli@uph.edu.hn');
define('MAIL_BIBLIOTECA', getenv('MAIL_BIBLIOTECA') ?: 'biblioteca.danli@uph.edu.hn');

define('MAIL_NAME_TICS', getenv('MAIL_NAME_TICS') ?: 'TICS Danlí - UPH');
define('MAIL_NAME_BIBLIOTECA', getenv('MAIL_NAME_BIBLIOTECA') ?: 'Biblioteca UPH - Danlí');

define('MAIL_TICS_PASSWORD', getenv('MAIL_TICS_PASSWORD') ?: '');
define('MAIL_BIBLIOTECA_PASSWORD', getenv('MAIL_BIBLIOTECA_PASSWORD') ?: '');

define('MAIL_HOST', getenv('MAIL_HOST') ?: 'smtp.gmail.com');
define('MAIL_PORT', (int)(getenv('MAIL_PORT') ?: 587));
define('MAIL_DEFAULT_ACCOUNT', 'tics');

/* Archivo local opcional, ignorado por Git */
$localMail = __DIR__ . '/mail.local.php';
if (file_exists($localMail)) {
    require_once $localMail;
}

function crearMailer($cuenta = null)
{
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

    if ($password === '') {
        throw new RuntimeException(
            'No hay contraseña SMTP configurada. Configura las variables de entorno ' .
            'MAIL_TICS_PASSWORD / MAIL_BIBLIOTECA_PASSWORD o crea config/mail.local.php.'
        );
    }

    $mail->Username = $usuario;
    $mail->Password = $password;
    $mail->setFrom($usuario, $nombre);

    return $mail;
}

function enviarCorreo($destinatario, $nombre, $asunto, $mensaje, $cuenta = null)
{
    try {
        $mail = crearMailer($cuenta);
        $mail->addAddress($destinatario, $nombre);
        $mail->Subject = $asunto;
        $mail->Body = $mensaje;
        $mail->AltBody = strip_tags(
            str_replace(['<br>', '<br/>', '<br />'], "\n", $mensaje)
        );
        return $mail->send();
    } catch (Throwable $e) {
        error_log('ERROR SMTP: ' . $e->getMessage());
        return false;
    }
}
