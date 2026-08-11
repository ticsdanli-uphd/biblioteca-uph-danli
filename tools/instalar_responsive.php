<?php
/*
 * INSTALADOR UNA SOLA VEZ - RESPONSIVE BIBLIOTECA UPH
 * Coloca este archivo en C:\xampp\htdocs\biblioteca\tools\
 * Abre: http://localhost/biblioteca/tools/instalar_responsive.php
 * Después de ejecutarlo, ELIMINA este archivo por seguridad.
 */
$root = dirname(__DIR__);
$header = $root . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'header.php';
$cssDir = $root . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'css';
$jsDir  = $root . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'js';
$css = $cssDir . DIRECTORY_SEPARATOR . 'responsive.css';
$js  = $jsDir . DIRECTORY_SEPARATOR . 'responsive.js';

if (!is_file($header)) die('No se encontró includes/header.php');
if (!is_dir($cssDir)) mkdir($cssDir, 0755, true);
if (!is_dir($jsDir)) mkdir($jsDir, 0755, true);

$cssContent = <<<'CSS'
/* RESPONSIVE GLOBAL - reemplazado por el paquete biblioteca_responsive_completo */
CSS;

/* El instalador espera que los archivos del paquete ya estén copiados. */
if (!is_file($css) || !is_file($js)) {
    die('Primero copie assets/css/responsive.css y assets/js/responsive.js del paquete.');
}

$original = file_get_contents($header);
$backup = $header . '.backup_' . date('Ymd_His');
if (!copy($header, $backup)) die('No se pudo crear respaldo de header.php');

$changed = false;
if (stripos($original, 'name="viewport"') === false) {
    $original = preg_replace('/<meta[^>]+charset=[^>]+>/i', '$0\n  <meta name="viewport" content="width=device-width, initial-scale=1.0">', $original, 1, $count);
    $changed = true;
}

if (strpos($original, '/biblioteca/assets/css/responsive.css') === false) {
    $tag = '  <link rel="stylesheet" href="/biblioteca/assets/css/responsive.css">\n';
    $original = str_ireplace('</head>', $tag . '</head>', $original, $count);
    $changed = true;
}

if (strpos($original, '/biblioteca/assets/js/responsive.js') === false) {
    $tag = '  <script src="/biblioteca/assets/js/responsive.js"></script>\n';
    $original = str_ireplace('</body>', $tag . '</body>', $original, $count);
    if (!$count) $original .= "\n" . $tag;
    $changed = true;
}

if ($changed && file_put_contents($header, $original) === false) {
    copy($backup, $header);
    die('No se pudo modificar header.php. Se restauró el respaldo.');
}

echo '<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Biblioteca UPH</title>';
echo '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"></head><body class="p-4">';
echo '<div class="container"><div class="alert alert-success"><h4>Responsive instalado correctamente</h4><p>Se agregó la capa responsive global al header.</p><p><strong>Respaldo:</strong> ' . htmlspecialchars(basename($backup)) . '</p><p>Ahora pruebe el sistema desde celular/tablet y después elimine <code>tools/instalar_responsive.php</code>.</p></div></div></body></html>';
?>
