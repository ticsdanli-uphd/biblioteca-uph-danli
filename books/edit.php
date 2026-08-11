<?php
require_once '../includes/session.php';
require_once '../config/db.php';
if (!isset($_SESSION['user_id'])) { header('Location: ../login.php'); exit; }
if (!in_array(strtolower(trim($_SESSION['role'] ?? '')), ['admin','administrador'], true)) { header('Location: list.php'); exit; }
$id=(int)($_GET['id'] ?? $_POST['id'] ?? 0);
if($id<=0){header('Location:list.php');exit;}
$stmt=$conn->prepare('SELECT * FROM bibliografia WHERE id=? AND sede_id=4 LIMIT 1');$stmt->bind_param('i',$id);$stmt->execute();$book=$stmt->get_result()->fetch_assoc();$stmt->close();
if(!$book){$_SESSION['error_msg']='El libro no existe o no pertenece a Danlí.';header('Location:list.php');exit;}
$error='';
function reemplazarFoto(array $file, ?string $actual, string $prefijo, string &$error): ?string {
    if(($file['error']??UPLOAD_ERR_NO_FILE)===UPLOAD_ERR_NO_FILE)return $actual;
    if(($file['error']??0)!==UPLOAD_ERR_OK){$error='No se pudo recibir la imagen.';return $actual;}
    if(($file['size']??0)>5*1024*1024){$error='La imagen no puede superar 5 MB.';return $actual;}
    $ext=strtolower(pathinfo($file['name'],PATHINFO_EXTENSION));
    if(!in_array($ext,['jpg','jpeg','png','webp'],true)||@getimagesize($file['tmp_name'])===false){$error='La imagen debe ser JPG, JPEG, PNG o WEBP válida.';return $actual;}
    $dir=__DIR__.'/../uploads/';if(!is_dir($dir))mkdir($dir,0755,true);
    $nuevo=$prefijo.'_'.bin2hex(random_bytes(10)).'.'.$ext;
    if(!move_uploaded_file($file['tmp_name'],$dir.$nuevo)){$error='No se pudo guardar la imagen.';return $actual;}
    if($actual && is_file($dir.basename($actual)))@unlink($dir.basename($actual));
    return $nuevo;
}
$estante='';$nivel=-1;if(!empty($book['ubicacion'])&&preg_match('/Estante\s+([AB])[- ]([1-5]).*?Nivel\s*([0-4])/iu',$book['ubicacion'],$m)){$estante=strtoupper($m[1]).'-'.$m[2];$nivel=(int)$m[3];}
if($_SERVER['REQUEST_METHOD']==='POST'){
    $codigo=trim($_POST['codigo']??'');$nombre=trim($_POST['nombre']??'');$dewey=trim($_POST['dewey']??'');$clasificacion=trim($_POST['clasificacion']??'');$autores=trim($_POST['autores']??'');$editorial=trim($_POST['editorial']??'');$edicion=trim($_POST['edicion']??'');$anio=!empty($_POST['anio'])?(int)$_POST['anio']:null;$isbn=trim($_POST['isbn']??'');$estado=trim($_POST['estado']??'Disponible');$estante=trim($_POST['estante']??'');$nivel=(int)($_POST['nivel_estante']??-1);$ubicacion='';if(preg_match('/^[AB]-[1-5]$/',$estante)&&$nivel>=0&&$nivel<=4)$ubicacion='Estante '.$estante.' - Nivel '.$nivel;$fecha_ingreso=trim($_POST['fecha_ingreso']??'');$idioma=trim($_POST['idioma']??'');$carrera_id=!empty($_POST['carrera_id'])?(int)$_POST['carrera_id']:null;$catalogacion=trim($_POST['catalogacion']??'');$observaciones=trim($_POST['observaciones']??'');$cantidad=max(1,(int)($_POST['cantidad']??1));
    if($codigo===''||$nombre==='')$error='Código y nombre son obligatorios.';elseif($ubicacion==='')$error='Seleccione estante y nivel.';elseif(!in_array($estado,['Disponible','Prestado','Deteriorado','Baja'],true))$error='Estado inválido.';else{$q=$conn->prepare('SELECT id FROM bibliografia WHERE codigo=? AND id<>? LIMIT 1');$q->bind_param('si',$codigo,$id);$q->execute();if($q->get_result()->fetch_assoc())$error='El código ya pertenece a otro libro.';$q->close();}
    $frontal=$book['foto_frontal']??($book['foto']??null);$trasera=$book['foto_trasera']??null;
    if($error==='')$frontal=reemplazarFoto($_FILES['foto_frontal']??[],$frontal,'libro_frontal',$error);
    if($error==='')$trasera=reemplazarFoto($_FILES['foto_trasera']??[],$trasera,'libro_trasera',$error);
    if($error===''){
        $sql="UPDATE bibliografia SET codigo=?,dewey=?,clasificacion=?,nombre=?,autores=?,editorial=?,edicion=?,anio=?,isbn=?,estado=?,ubicacion=?,fecha_ingreso=?,idioma=?,carrera_id=?,catalogacion=?,observaciones=?,cantidad=?,foto=?,foto_frontal=?,foto_trasera=?,sede_id=4,modificado_por=? WHERE id=? AND sede_id=4";
        $q=$conn->prepare($sql);$uid=(int)$_SESSION['user_id'];
        $q->bind_param('sssssssisssssississsii',$codigo,$dewey,$clasificacion,$nombre,$autores,$editorial,$edicion,$anio,$isbn,$estado,$ubicacion,$fecha_ingreso,$idioma,$carrera_id,$catalogacion,$observaciones,$cantidad,$frontal,$frontal,$trasera,$uid,$id);
        if($q->execute()){$_SESSION['success_msg']='Libro actualizado correctamente.';$q->close();header('Location:view.php?id='.$id);exit;}else{$error='Error al actualizar: '.$q->error;$q->close();}
    }
}
$carreras=$conn->query('SELECT id,nombre FROM carreras ORDER BY nombre');include '../includes/header.php';
?>
<div class="container-fluid py-4"><div class="row justify-content-center"><div class="col-xl-10"><div class="card shadow-sm border-0"><div class="card-header bg-primary text-white"><h4 class="mb-0 text-white"><i class="fas fa-edit me-2"></i>Editar Libro</h4><small class="text-white">Biblioteca UPH - Danlí</small></div><div class="card-body p-4">
<?php if($error):?><div class="alert alert-danger"><?=htmlspecialchars($error)?></div><?php endif;?>
<form method="post" enctype="multipart/form-data"><input type="hidden" name="id" value="<?=$id?>"><div class="row g-3">
<div class="col-md-4"><label class="form-label">Código *</label><input name="codigo" class="form-control" required value="<?=htmlspecialchars($book['codigo'])?>"></div><div class="col-md-4"><label class="form-label">Dewey</label><input name="dewey" class="form-control" value="<?=htmlspecialchars($book['dewey']??'')?>"></div><div class="col-md-4"><label class="form-label">Clasificación</label><input name="clasificacion" class="form-control" value="<?=htmlspecialchars($book['clasificacion']??'')?>"></div>
<div class="col-12"><label class="form-label">Nombre *</label><input name="nombre" class="form-control" required value="<?=htmlspecialchars($book['nombre'])?>"></div><div class="col-md-6"><label class="form-label">Autor(es)</label><input name="autores" class="form-control" value="<?=htmlspecialchars($book['autores']??'')?>"></div><div class="col-md-6"><label class="form-label">Editorial</label><input name="editorial" class="form-control" value="<?=htmlspecialchars($book['editorial']??'')?>"></div>
<div class="col-md-4"><label class="form-label">Edición</label><input name="edicion" class="form-control" value="<?=htmlspecialchars($book['edicion']??'')?>"></div><div class="col-md-4"><label class="form-label">Año</label><input type="number" name="anio" class="form-control" value="<?=htmlspecialchars((string)($book['anio']??''))?>"></div><div class="col-md-4"><label class="form-label">ISBN</label><input name="isbn" class="form-control" value="<?=htmlspecialchars($book['isbn']??'')?>"></div>
<div class="col-md-3"><label class="form-label">Estado</label><select name="estado" class="form-select"><?php foreach(['Disponible','Prestado','Deteriorado','Baja'] as $e):?><option value="<?=$e?>" <?=($book['estado']===$e?'selected':'')?>><?=$e?></option><?php endforeach;?></select></div><div class="col-md-3"><label class="form-label">Cantidad</label><input type="number" min="1" name="cantidad" class="form-control" value="<?=max(1,(int)$book['cantidad'])?>"></div><div class="col-md-3"><label class="form-label">Estante</label><select name="estante" class="form-select" required><option value="">Seleccione</option><?php foreach(['A-1','A-2','A-3','A-4','A-5','B-1','B-2','B-3','B-4','B-5'] as $e):?><option value="<?=$e?>" <?=($estante===$e?'selected':'')?>><?=$e?></option><?php endforeach;?></select></div><div class="col-md-3"><label class="form-label">Nivel</label><select name="nivel_estante" class="form-select" required><option value="">Seleccione</option><?php for($n=0;$n<=4;$n++):?><option value="<?=$n?>" <?=($nivel===$n?'selected':'')?>>Nivel <?=$n?></option><?php endfor;?></select></div>
<div class="col-md-6"><label class="form-label">Carrera</label><select name="carrera_id" class="form-select"><option value="">Todas / General</option><?php while($c=$carreras->fetch_assoc()):?><option value="<?=$c['id']?>" <?=((int)($book['carrera_id']??0)===(int)$c['id']?'selected':'')?>><?=htmlspecialchars($c['nombre'])?></option><?php endwhile;?></select></div><div class="col-md-6"><label class="form-label">Fecha de ingreso</label><input type="date" name="fecha_ingreso" class="form-control" value="<?=htmlspecialchars($book['fecha_ingreso']??'')?>"></div>
<div class="col-md-6"><label class="form-label">📕 Foto frontal / portada</label><input type="file" name="foto_frontal" id="foto_frontal" class="form-control" accept="image/jpeg,image/png,image/webp"><?php if($frontal):?><img class="preview mt-2" src="../uploads/<?=htmlspecialchars(basename($frontal))?>" alt="Portada actual"><?php endif;?></div>
<div class="col-md-6"><label class="form-label">📗 Foto trasera / contraportada</label><input type="file" name="foto_trasera" id="foto_trasera" class="form-control" accept="image/jpeg,image/png,image/webp"><?php if($trasera):?><img class="preview mt-2" src="../uploads/<?=htmlspecialchars(basename($trasera))?>" alt="Contraportada actual"><?php endif;?></div>
<div class="col-12"><label class="form-label">Catalogación</label><input name="catalogacion" class="form-control" value="<?=htmlspecialchars($book['catalogacion']??'')?>"></div><div class="col-12"><label class="form-label">Observaciones</label><textarea name="observaciones" class="form-control" rows="3"><?=htmlspecialchars($book['observaciones']??'')?></textarea></div>
</div><div class="d-flex justify-content-between mt-4"><a href="view.php?id=<?=$id?>" class="btn btn-secondary">Cancelar</a><button class="btn btn-primary"><i class="fas fa-save me-1"></i>Guardar Cambios</button></div></form>
</div></div></div></div></div>
<style>.preview{width:100%;height:230px;object-fit:contain;border:1px solid #dee2e6;border-radius:12px;padding:6px;background:#f8f9fa}.card-header.bg-primary h4,.card-header.bg-primary small{color:#fff!important}</style>
<?php include '../includes/footer.php'; ?>
