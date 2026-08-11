<?php
// ============================================================
// admin/solicitud_accion.php
// Aceptar, rechazar y entregar solicitudes.
// ============================================================
include '../includes/session.php';
include '../config/db.php';

if (!isset($_SESSION['user_id'])) { header('Location: ../login.php'); exit(); }
$rol=strtolower(trim($_SESSION['role'] ?? $_SESSION['rol'] ?? $_SESSION['tipo_usuario'] ?? ''));
if(!in_array($rol,['admin','administrador'],true)){header('Location: ../dashboard.php');exit();}

$id=(int)($_POST['id']??0);
$accion=$_POST['accion']??'';
if($id<=0){$_SESSION['error_msg']='Solicitud inválida.';header('Location: solicitudes_prestamo.php');exit();}

$stmt=$conn->prepare("
SELECT s.*, b.cantidad,b.ubicacion,b.sede_id,b.nombre libro_nombre
FROM solicitudes_prestamo s
INNER JOIN bibliografia b ON b.id=s.bibliografia_id
WHERE s.id=? LIMIT 1");
$stmt->bind_param('i',$id);$stmt->execute();$sol=$stmt->get_result()->fetch_assoc();$stmt->close();
if(!$sol){$_SESSION['error_msg']='Solicitud no encontrada.';header('Location: solicitudes_prestamo.php');exit();}

try{
$conn->begin_transaction();

if($accion==='aprobar'){
    if($sol['estado']!=='pendiente') throw new Exception('La solicitud ya fue procesada.');

    $stmt=$conn->prepare("SELECT COUNT(*) total FROM registro_visitas WHERE bibliografia_id=? AND tipo='prestamo' AND devuelto=0");
    $stmt->bind_param('i',$sol['bibliografia_id']);$stmt->execute();
    $act=(int)$stmt->get_result()->fetch_assoc()['total'];$stmt->close();
    if($act >= (int)$sol['cantidad']) throw new Exception('El libro ya no tiene ejemplares disponibles.');

    $stmt=$conn->prepare("UPDATE solicitudes_prestamo SET estado='aprobada',fecha_respuesta=NOW(),atendido_por=? WHERE id=?");
    $uid=(int)$_SESSION['user_id'];$stmt->bind_param('ii',$uid,$id);$stmt->execute();$stmt->close();
    $_SESSION['success_msg']='Solicitud aprobada. El alumno puede consultar dónde recoger el libro.';
}
elseif($accion==='rechazar'){
    if($sol['estado']!=='pendiente') throw new Exception('La solicitud ya fue procesada.');
    $motivo=trim($_POST['motivo']??'Solicitud no aprobada por la biblioteca.');
    $uid=(int)$_SESSION['user_id'];
    $stmt=$conn->prepare("UPDATE solicitudes_prestamo SET estado='rechazada',motivo_rechazo=?,fecha_respuesta=NOW(),atendido_por=? WHERE id=?");
    $stmt->bind_param('sii',$motivo,$uid,$id);$stmt->execute();$stmt->close();
    $_SESSION['success_msg']='Solicitud rechazada.';
}
elseif($accion==='entregar'){
    if($sol['estado']!=='aprobada') throw new Exception('La solicitud debe estar aprobada antes de entregarla.');

    $stmt=$conn->prepare("SELECT COUNT(*) total FROM registro_visitas WHERE bibliografia_id=? AND tipo='prestamo' AND devuelto=0");
    $stmt->bind_param('i',$sol['bibliografia_id']);$stmt->execute();
    $act=(int)$stmt->get_result()->fetch_assoc()['total'];$stmt->close();
    if($act >= (int)$sol['cantidad']) throw new Exception('No hay ejemplares disponibles para entregar.');

    $fecha=date('Y-m-d',strtotime('+3 days'));
    $stmt=$conn->prepare("
        INSERT INTO registro_visitas
        (bibliografia_id,user_id,tipo,observaciones,nombre_alumno,institucion_id,carrera_id,es_externo,fecha_devolucion_esperada,devuelto)
        VALUES(?,?, 'prestamo', ?, ?, NULL, ?, 0, ?, 0)");
    $observ=$sol['observaciones'] ?: 'Solicitud de préstamo aprobada.';
    $userId=(int)$sol['usuario_id'];
    $carrera=(int)($sol['carrera_id']??0);
    $stmt->bind_param('iissis',$sol['bibliografia_id'],$userId,$observ,$sol['nombre_solicitante'],$carrera,$fecha);
    if(!$stmt->execute()) throw new Exception($stmt->error);
    $registro=$stmt->insert_id;$stmt->close();

    $stmt=$conn->prepare("UPDATE solicitudes_prestamo SET estado='prestado',fecha_entrega=NOW(),fecha_respuesta=COALESCE(fecha_respuesta,NOW()),atendido_por=?,registro_visita_id=? WHERE id=?");
    $uid=(int)$_SESSION['user_id'];$stmt->bind_param('iii',$uid,$registro,$id);$stmt->execute();$stmt->close();

    $stmt=$conn->prepare("UPDATE bibliografia SET estado='Prestado' WHERE id=?");
    $stmt->bind_param('i',$sol['bibliografia_id']);$stmt->execute();$stmt->close();

    $_SESSION['success_msg']='Préstamo entregado y registrado correctamente.';
}
else { throw new Exception('Acción no válida.'); }

$conn->commit();
}catch(Throwable $e){$conn->rollback();$_SESSION['error_msg']=$e->getMessage();}
header('Location: solicitudes_prestamo.php');exit();
