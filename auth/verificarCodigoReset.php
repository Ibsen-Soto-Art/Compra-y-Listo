<?php
session_start();
header('Content-Type: application/json');

if($_SERVER['REQUEST_METHOD'] !== 'POST'){
    echo json_encode(["status"=>"error","message"=>"Método no permitido"]);
    exit;
}

$codigo = trim($_POST['codigo'] ?? '');

if(empty($codigo)){
    echo json_encode(["status"=>"error","message"=>"Ingresa el código"]);
    exit;
}

// Verificar sesión activa
if(empty($_SESSION['reset_codigo']) || empty($_SESSION['reset_correo'])){
    echo json_encode(["status"=>"error","message"=>"Solicitud expirada. Vuelve a empezar."]);
    exit;
}

// Verificar expiración
if(time() > ($_SESSION['reset_expiry'] ?? 0)){
    unset($_SESSION['reset_codigo'], $_SESSION['reset_correo'],
          $_SESSION['reset_expiry'], $_SESSION['reset_nombre'],
          $_SESSION['reset_verificado']);
    echo json_encode(["status"=>"expired","message"=>"El código ha expirado. Solicita uno nuevo."]);
    exit;
}

// Verificar código (comparación tipo string para preservar ceros iniciales)
if($codigo !== $_SESSION['reset_codigo']){
    echo json_encode(["status"=>"error","message"=>"Código incorrecto"]);
    exit;
}

// Marcar como verificado
$_SESSION['reset_verificado'] = true;

echo json_encode(["status"=>"success","message"=>"Código correcto"]);
?>
