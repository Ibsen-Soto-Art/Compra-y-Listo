<?php
session_start();
include("../config/conection.php");
header('Content-Type: application/json');

if($_SERVER['REQUEST_METHOD'] !== 'POST'){
    echo json_encode(["status"=>"error","message"=>"Método no permitido"]);
    exit;
}

// Verificar que el código fue validado
if(empty($_SESSION['reset_verificado']) || $_SESSION['reset_verificado'] !== true){
    echo json_encode(["status"=>"error","message"=>"No autorizado. Verifica el código primero."]);
    exit;
}

if(empty($_SESSION['reset_correo'])){
    echo json_encode(["status"=>"error","message"=>"Sesión expirada. Vuelve a empezar."]);
    exit;
}

$nueva    = $_POST['nueva']    ?? '';
$confirmar = $_POST['confirmar'] ?? '';

if(empty($nueva) || empty($confirmar)){
    echo json_encode(["status"=>"error","message"=>"Completa todos los campos"]);
    exit;
}

if(strlen($nueva) < 6){
    echo json_encode(["status"=>"error","message"=>"La contraseña debe tener al menos 6 caracteres"]);
    exit;
}

if($nueva !== $confirmar){
    echo json_encode(["status"=>"error","message"=>"Las contraseñas no coinciden"]);
    exit;
}

$correo = $_SESSION['reset_correo'];
$con    = conection();

$stmt = mysqli_prepare($con, "UPDATE usuarios SET contraseña = ? WHERE correo = ?");
mysqli_stmt_bind_param($stmt, "ss", $nueva, $correo);
$ok = mysqli_stmt_execute($stmt);

if($ok && mysqli_stmt_affected_rows($stmt) > 0){
    // Limpiar sesión de reset
    unset($_SESSION['reset_correo'],   $_SESSION['reset_codigo'],
          $_SESSION['reset_expiry'],   $_SESSION['reset_nombre'],
          $_SESSION['reset_verificado']);

    echo json_encode(["status"=>"success","message"=>"Contraseña actualizada correctamente"]);
} else {
    echo json_encode(["status"=>"error","message"=>"No se pudo actualizar la contraseña"]);
}
?>
