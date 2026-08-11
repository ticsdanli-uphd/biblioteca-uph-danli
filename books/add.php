<?php
require_once '../includes/session.php';
require_once '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$rol = strtolower(trim($_SESSION['role'] ?? ''));
if (!in_array($rol, ['admin','administrador'], true)) {
    header('Location: list.php');
    exit;
}

$sede_id = 4; // Danlí
$error = '';
$success = '';

function guardarFoto(array $file, string $prefijo): array
{
    if (!isset($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return [true, null];
    }

    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        return [false, 'No se pudo recibir la imagen.'];
    }

    if (($file['size'] ?? 0) > 5 * 1024 * 1024) {
        return [false, 'La imagen no puede superar 5 MB.'];
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $permitidas = ['jpg','jpeg','png','webp'];
    if (!in_array($ext, $permitidas, true)) {
        return [false, 'La imagen debe ser JPG, JPEG, PNG o WEBP.'];
    }

    $info = @getimagesize($file['tmp_name']);
    if ($info === false) {
        return [false, 'El archivo seleccionado no es una imagen válida.'];
    }

    $dir = __DIR__ . '/../uploads/';
    if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
        return [false, 'No se pudo crear la carpeta de imágenes.'];
    }

    $nombre = $prefijo . '_' . bin2hex(random_bytes(10)) . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], $dir . $nombre)) {
        return [false, 'No se pudo guardar la imagen.'];
    }

    return [true, $nombre];
}

$carreras = $conn->query("SELECT id,nombre FROM carreras ORDER BY nombre");

$codigo = trim($_POST['codigo'] ?? '');
$dewey = trim($_POST['dewey'] ?? '');
$clasificacion = trim($_POST['clasificacion'] ?? '');
$nombre = trim($_POST['nombre'] ?? '');
$autores = trim($_POST['autores'] ?? ($_POST['autor'] ?? ''));
$editorial = trim($_POST['editorial'] ?? '');
$edicion = trim($_POST['edicion'] ?? '');
$anio = !empty($_POST['anio']) ? (int)$_POST['anio'] : null;
$isbn = trim($_POST['isbn'] ?? '');
$estado = trim($_POST['estado'] ?? 'Disponible');
$estante = trim($_POST['estante'] ?? '');
$nivel = isset($_POST['nivel_estante']) ? (int)$_POST['nivel_estante'] : -1;
$fecha_ingreso = trim($_POST['fecha_ingreso'] ?? date('Y-m-d'));
$idioma = trim($_POST['idioma'] ?? 'Español');
$carrera_id = !empty($_POST['carrera_id']) ? (int)$_POST['carrera_id'] : null;
$catalogacion = trim($_POST['catalogacion'] ?? '');
$observaciones = trim($_POST['observaciones'] ?? '');
$cantidad = max(1, (int)($_POST['cantidad'] ?? 1));
$ubicacion = '';
if (preg_match('/^[AB]-[1-5]$/', $estante) && $nivel >= 0 && $nivel <= 4) {
    $ubicacion = 'Estante ' . $estante . ' - Nivel ' . $nivel;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($codigo === '' || $nombre === '') {
        $error = 'El código y el nombre del libro son obligatorios.';
    } elseif ($ubicacion === '') {
        $error = 'Seleccione un estante (A-1 a A-5 o B-1 a B-5) y un nivel del 0 al 4.';
    } elseif (!in_array($estado, ['Disponible','Prestado','Deteriorado','Baja'], true)) {
        $error = 'El estado seleccionado no es válido.';
    } elseif ($anio !== null && ($anio < 1000 || $anio > ((int)date('Y') + 1))) {
        $error = 'El año ingresado no es válido.';
    } else {
        $stmt = $conn->prepare('SELECT id FROM bibliografia WHERE codigo=? LIMIT 1');
        $stmt->bind_param('s', $codigo);
        $stmt->execute();
        $duplicado = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($duplicado) {
            $error = 'El código del libro ya existe.';
        }
    }

    $frontal = null;
    $trasera = null;

    if ($error === '') {
        [$okFrontal, $frontal, $errFrontal] = guardarFoto($_FILES['foto_frontal'] ?? [], 'libro_frontal');
        if (!$okFrontal) $error = $errFrontal;
        if ($error === '') {
            [$okTrasera, $trasera, $errTrasera] = guardarFoto($_FILES['foto_trasera'] ?? [], 'libro_trasera');
            if (!$okTrasera) $error = $errTrasera;
        }
    }

    if ($error === '') {
        $sql = "INSERT INTO bibliografia
            (codigo,dewey,clasificacion,nombre,autores,editorial,edicion,anio,isbn,estado,ubicacion,
             fecha_ingreso,idioma,carrera_id,catalogacion,observaciones,cantidad,foto, foto_frontal,
             foto_trasera,sede_id,ingresado_por)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            $error = 'Error al preparar el registro: ' . $conn->error;
        } else {
            $usuario_id = (int)$_SESSION['user_id'];
            $stmt->bind_param(
                'sssssssisssssississsii',
                $codigo,$dewey,$clasificacion,$nombre,$autores,$editorial,$edicion,$anio,$isbn,$estado,
                $ubicacion,$fecha_ingreso,$idioma,$carrera_id,$catalogacion,$observaciones,$cantidad,
                $frontal,$frontal,$trasera,$sede_id,$usuario_id
            );
            if ($stmt->execute()) {
                $success = 'Libro registrado correctamente con sus fotografías.';
                $codigo=$dewey=$clasificacion=$nombre=$autores=$editorial=$edicion=$isbn=$catalogacion=$observaciones='';
                $anio=null; $estado='Disponible'; $estante=''; $nivel=-1; $fecha_ingreso=date('Y-m-d');
                $idioma='Español'; $carrera_id=null; $cantidad=1; $ubicacion='';
            } else {
                if ($frontal && is_file(__DIR__.'/../uploads/'.basename($frontal))) @unlink(__DIR__.'/../uploads/'.basename($frontal));
                if ($trasera && is_file(__DIR__.'/../uploads/'.basename($trasera))) @unlink(__DIR__.'/../uploads/'.basename($trasera));
                $error = 'Error al registrar el libro: ' . $stmt->error;
            }
            $stmt->close();
        }
    }
}

include '../includes/header.php';
?>
<style>
.book-card{max-width:1100px;margin:auto;background:#fff;border-radius:18px;box-shadow:0 8px 30px rgba(0,0,0,.07);overflow:hidden}
.book-card-header{background:#0d6efd;color:#fff;padding:18px 24px;font-size:20px;font-weight:700}
.book-card-body{padding:24px}
.preview-img{width:100%;height:220px;object-fit:contain;border:1px dashed #cbd5e1;border-radius:12px;background:#f8fafc;padding:8px}
</style>
<div class="container-fluid py-4">
<div class="book-card">
<div class="book-card-header"><i class="fas fa-book me-2"></i>Agregar Libro — Biblioteca UPH Danlí</div>
<div class="book-card-body">
<?php if($error): ?><div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i><?=htmlspecialchars($error)?></div><?php endif; ?>
<?php if($success): ?><div class="alert alert-success"><i class="fas fa-check-circle me-2"></i><?=htmlspecialchars($success)?></div><?php endif; ?>
<form method="post" enctype="multipart/form-data">
<div class="row g-3">
<div class="col-md-4"><label class="form-label">Código *</label><input name="codigo" class="form-control" required value="<?=htmlspecialchars($codigo)?>"></div>
<div class="col-md-4"><label class="form-label">Dewey</label><input name="dewey" class="form-control" value="<?=htmlspecialchars($dewey)?>"></div>
<div class="col-md-4"><label class="form-label">Clasificación</label><input name="clasificacion" class="form-control" value="<?=htmlspecialchars($clasificacion)?>"></div>
<div class="col-12"><label class="form-label">Nombre del libro *</label><input name="nombre" class="form-control" required value="<?=htmlspecialchars($nombre)?>"></div>
<div class="col-md-6"><label class="form-label">Autor(es)</label><input name="autores" class="form-control" value="<?=htmlspecialchars($autores)?>"></div>
<div class="col-md-6"><label class="form-label">Editorial</label><input name="editorial" class="form-control" value="<?=htmlspecialchars($editorial)?>"></div>
<div class="col-md-4"><label class="form-label">Edición</label><input name="edicion" class="form-control" value="<?=htmlspecialchars($edicion)?>"></div>
<div class="col-md-4"><label class="form-label">Año</label><input type="number" name="anio" class="form-control" value="<?=htmlspecialchars((string)$anio)?>"></div>
<div class="col-md-4"><label class="form-label">ISBN</label><input name="isbn" class="form-control" value="<?=htmlspecialchars($isbn)?>"></div>
<div class="col-md-3"><label class="form-label">Estado</label><select name="estado" class="form-select"><?php foreach(['Disponible','Prestado','Deteriorado','Baja'] as $e): ?><option value="<?=$e?>" <?=$estado===$e?'selected':''?>><?=$e?></option><?php endforeach;?></select></div>
<div class="col-md-3"><label class="form-label">Cantidad *</label><input type="number" min="1" name="cantidad" class="form-control" required value="<?=$cantidad?>"></div>
<div class="col-md-3"><label class="form-label">Fecha de ingreso</label><input type="date" name="fecha_ingreso" class="form-control" value="<?=htmlspecialchars($fecha_ingreso)?>"></div>
<div class="col-md-3"><label class="form-label">Idioma</label><input name="idioma" class="form-control" value="<?=htmlspecialchars($idioma)?>"></div>
<div class="col-md-6"><label class="form-label">Carrera</label><select name="carrera_id" class="form-select"><option value="">Todas / General</option><?php if($carreras): while($c=$carreras->fetch_assoc()): ?><option value="<?=$c['id']?>" <?=$carrera_id===$c['id']?'selected':''?>><?=htmlspecialchars($c['nombre'])?></option><?php endwhile; endif;?></select></div>
<div class="col-md-3"><label class="form-label">Estante *</label><select name="estante" class="form-select" required><option value="">Seleccione</option><?php foreach(['A-1','A-2','A-3','A-4','A-5','B-1','B-2','B-3','B-4','B-5'] as $e): ?><option value="<?=$e?>" <?=$estante===$e?'selected':''?>><?=$e?></option><?php endforeach;?></select></div>
<div class="col-md-3"><label class="form-label">Nivel *</label><select name="nivel_estante" class="form-select" required><option value="">Seleccione</option><?php for($n=0;$n<=4;$n++): ?><option value="<?=$n?>" <?=$nivel===$n?'selected':''?>>Nivel <?=$n?></option><?php endfor;?></select></div>
<div class="col-12"><label class="form-label">Ubicación</label><input class="form-control" value="<?=htmlspecialchars($ubicacion)?>" readonly><small class="text-muted">Se genera automáticamente con estante y nivel.</small></div>
<div class="col-md-6">
<label class="form-label fw-bold">📕 Foto frontal / portada</label>
<input type="file" name="foto_frontal" id="foto_frontal" class="form-control" accept="image/jpeg,image/png,image/webp">
<div class="mt-2"><img id="preview_frontal" class="preview-img" alt="Vista previa frontal" style="display:none"></div>
</div>
<div class="col-md-6">
<label class="form-label fw-bold">📗 Foto trasera / contraportada</label>
<input type="file" name="foto_trasera" id="foto_trasera" class="form-control" accept="image/jpeg,image/png,image/webp">
<div class="mt-2"><img id="preview_trasera" class="preview-img" alt="Vista previa trasera" style="display:none"></div>
</div>
<div class="col-12"><label class="form-label">Catalogación</label><input name="catalogacion" class="form-control" value="<?=htmlspecialchars($catalogacion)?>"></div>
<div class="col-12"><label class="form-label">Observaciones</label><textarea name="observaciones" class="form-control" rows="3"><?=htmlspecialchars($observaciones)?></textarea></div>
</div>
<div class="d-flex justify-content-between mt-4"><a href="list.php" class="btn btn-secondary"><i class="fas fa-arrow-left me-1"></i>Cancelar</a><button class="btn btn-primary"><i class="fas fa-save me-1"></i>Guardar Libro</button></div>
</form>
</div></div></div>
<script>
function preview(input,id){const f=input.files[0],img=document.getElementById(id);if(f){img.src=URL.createObjectURL(f);img.style.display='block';}}
document.getElementById('foto_frontal').addEventListener('change',function(){preview(this,'preview_frontal')});
document.getElementById('foto_trasera').addEventListener('change',function(){preview(this,'preview_trasera')});
</script>
<?php include '../includes/footer.php'; ?>
