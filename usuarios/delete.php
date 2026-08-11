<?php
require_once '../includes/session.php';
require_once '../config/db.php';

if (!isset($_SESSION['user_id'])) { header('Location: ../login.php'); exit; }
$rol=strtolower(trim($_SESSION['role']??$_SESSION['rol']??''));
if(!in_array($rol,['admin','administrador'],true)){header('Location: ../dashboard.php');exit;}

$id=(int)($_GET['id']??0);
if($id<=0){$_SESSION['error']='Usuario inválido.';header('Location:list.php');exit;}

if($id===(int)$_SESSION['user_id']){
    $_SESSION['error']='No puedes desactivar tu propio usuario.';
    header('Location:list.php');exit;
}

/* Se desactiva en lugar de borrar físicamente para no romper préstamos,
   solicitudes, visitas y demás relaciones históricas. */
$stmt=$conn->prepare("UPDATE usuarios SET activo=0 WHERE id=? AND sede_id=4");
$stmt->bind_param('i',$id);
$stmt->execute();

if($stmt->affected_rows>0){
    $_SESSION['success']='Usuario desactivado correctamente.';
}else{
    $_SESSION['error']='Usuario no encontrado en la sede Danlí.';
}
$stmt->close();

header('Location:list.php');exit;
?>
