<?php
require_once '../includes/session.php';
require_once '../config/db.php';

if (!isset($_SESSION['user_id'])) { header('Location: ../login.php'); exit; }
$rol = strtolower(trim($_SESSION['role'] ?? $_SESSION['rol'] ?? ''));
if (!in_array($rol, ['admin','administrador'], true)) { header('Location: ../dashboard.php'); exit; }

const DANLI_SEDE_ID = 4;
$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
if ($id <= 0) { $_SESSION['error']='Usuario inválido.'; header('Location: list.php'); exit; }

$error = '';

$stmt = $conn->prepare("
SELECT u.id,u.username,u.nombre,u.role,u.sede_id,u.activo,
       COALESCE(a.nombre,d.nombre,u.nombre) AS nombre_perfil,
       a.telefono AS alumno_telefono,a.email AS alumno_email,a.carrera_id AS alumno_carrera,
       d.telefono AS docente_telefono,d.email AS docente_email
FROM usuarios u
LEFT JOIN alumnos a ON a.usuario_id=u.id
LEFT JOIN docentes d ON d.usuario_id=u.id
WHERE u.id=? AND u.sede_id=4 LIMIT 1");
$stmt->bind_param('i',$id);
$stmt->execute();
$user=$stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    $_SESSION['error']='Usuario no encontrado en la sede Danlí.';
    header('Location: list.php'); exit;
}

$tipo = in_array($user['role'],['admin','administrador'],true) ? 'admin' :
        (in_array($user['role'],['docente'],true) ? 'docente' : 'alumno');

if ($_SERVER['REQUEST_METHOD']==='POST') {
    $nombre=trim($_POST['nombre']??'');
    $tipoNuevo=$_POST['tipo']??$tipo;
    $telefono=trim($_POST['telefono']??'');
    $email=trim($_POST['email']??'');
    $carrera_id=!empty($_POST['carrera_id'])?(int)$_POST['carrera_id']:null;
    $password=$_POST['password']??'';

    if ($nombre==='') $error='Ingrese el nombre completo.';
    elseif (!in_array($tipoNuevo,['admin','docente','alumno'],true)) $error='Tipo de usuario inválido.';
    elseif ($email!=='' && !filter_var($email,FILTER_VALIDATE_EMAIL)) $error='El correo no es válido.';
    elseif ($password!=='' && strlen($password)<6) $error='La contraseña debe tener al menos 6 caracteres.';
    elseif ($tipoNuevo==='alumno' && !$carrera_id) $error='Seleccione la carrera del alumno.';
    else {
        $conn->begin_transaction();
        try {
            if ($password!=='') {
                $hash=password_hash($password,PASSWORD_DEFAULT);
                $stmt=$conn->prepare("UPDATE usuarios SET nombre=?,role=?,password=? WHERE id=? AND sede_id=4");
                $stmt->bind_param('sssi',$nombre,$tipoNuevo,$hash,$id);
            } else {
                $stmt=$conn->prepare("UPDATE usuarios SET nombre=?,role=? WHERE id=? AND sede_id=4");
                $stmt->bind_param('ssi',$nombre,$tipoNuevo,$id);
            }
            if(!$stmt->execute()) throw new Exception($stmt->error);
            $stmt->close();

            $stmt=$conn->prepare("DELETE FROM alumnos WHERE usuario_id=?");
            $stmt->bind_param('i',$id); $stmt->execute(); $stmt->close();
            $stmt=$conn->prepare("DELETE FROM docentes WHERE usuario_id=?");
            $stmt->bind_param('i',$id); $stmt->execute(); $stmt->close();

            if($tipoNuevo==='alumno'){
                $correo=$email;
                $stmt=$conn->prepare("INSERT INTO alumnos(usuario_id,nombre,telefono,email,carrera_id,sede_id) VALUES(?,?,?,?,?,4)");
                $stmt->bind_param('isssi',$id,$nombre,$telefono,$correo,$carrera_id);
                if(!$stmt->execute()) throw new Exception($stmt->error);
                $stmt->close();
            } elseif($tipoNuevo==='docente'){
                $correo=$email;
                $stmt=$conn->prepare("INSERT INTO docentes(usuario_id,nombre,telefono,email,carrera_id,sede_id) VALUES(?,?,?,?,NULL,4)");
                $stmt->bind_param('isss',$id,$nombre,$telefono,$correo);
                if(!$stmt->execute()) throw new Exception($stmt->error);
                $stmt->close();
            }

            $conn->commit();
            $_SESSION['success']='Usuario actualizado correctamente.';
            header('Location: list.php'); exit;
        } catch(Throwable $e) {
            $conn->rollback();
            $error='No se pudo actualizar: '.$e->getMessage();
        }
    }
}

$carreras=$conn->query("SELECT id,nombre FROM carreras ORDER BY nombre");
include '../includes/header.php';
?>
<div class="container-fluid py-4">
<div class="row justify-content-center"><div class="col-xl-8 col-lg-10">
<div class="card shadow-sm border-0">
<div class="card-header bg-primary text-white py-3">
<h4 class="mb-1 text-white"><i class="fas fa-user-edit me-2"></i>Editar Usuario</h4>
<small class="text-white"><i class="fas fa-map-marker-alt me-1"></i>Sede Danlí</small>
</div>
<div class="card-body p-4">
<?php if($error): ?><div class="alert alert-danger"><?=htmlspecialchars($error)?></div><?php endif;?>
<div class="alert alert-info"><i class="fas fa-info-circle me-2"></i>La sede está fija en <strong>Danlí</strong>. La carrera solamente se utiliza para alumnos.</div>
<form method="post">
<input type="hidden" name="id" value="<?=$id?>">
<div class="mb-3"><label class="form-label">Nombre de usuario</label><input class="form-control" value="<?=htmlspecialchars($user['username'])?>" readonly></div>
<div class="mb-3"><label class="form-label">Nombre completo *</label><input name="nombre" class="form-control" required value="<?=htmlspecialchars($user['nombre_perfil'])?>"></div>
<div class="mb-3"><label class="form-label">Tipo de usuario *</label>
<select name="tipo" id="tipo" class="form-select" required>
<option value="admin" <?=$tipo==='admin'?'selected':''?>>Administrador</option>
<option value="docente" <?=$tipo==='docente'?'selected':''?>>Docente</option>
<option value="alumno" <?=$tipo==='alumno'?'selected':''?>>Alumno</option>
</select></div>
<div id="datosPersona">
<div class="row g-3">
<div class="col-md-6"><label class="form-label">Teléfono</label><input name="telefono" class="form-control" value="<?=htmlspecialchars($tipo==='alumno'?($user['alumno_telefono']??''):($user['docente_telefono']??''))?>"></div>
<div class="col-md-6"><label class="form-label">Correo electrónico</label><input type="email" name="email" class="form-control" value="<?=htmlspecialchars($tipo==='alumno'?($user['alumno_email']??''):($user['docente_email']??''))?>"></div>
</div>
</div>
<div id="carreraBox" class="mt-3">
<label class="form-label">Carrera del alumno</label>
<select name="carrera_id" class="form-select">
<option value="">Seleccione...</option>
<?php while($c=$carreras->fetch_assoc()): ?>
<option value="<?=$c['id']?>" <?=((int)($user['alumno_carrera']??0)===(int)$c['id'])?'selected':''?>><?=htmlspecialchars($c['nombre'])?></option>
<?php endwhile;?>
</select>
</div>
<div class="mt-3"><label class="form-label">Nueva contraseña</label><input type="password" name="password" class="form-control" minlength="6"><small class="text-muted">Déjela vacía para conservar la contraseña actual.</small></div>
<div class="d-flex gap-2 mt-4"><a href="list.php" class="btn btn-secondary">Cancelar</a><button class="btn btn-primary"><i class="fas fa-save me-1"></i>Guardar cambios</button></div>
</form>
</div></div></div></div></div>
<script>
function ajustarTipo(){
 const tipo=document.getElementById('tipo').value;
 document.getElementById('carreraBox').style.display=tipo==='alumno'?'block':'none';
 document.getElementById('datosPersona').style.display=(tipo==='alumno'||tipo==='docente')?'block':'none';
}
document.getElementById('tipo').addEventListener('change',ajustarTipo);
ajustarTipo();
</script>
<?php include '../includes/footer.php'; ?>
