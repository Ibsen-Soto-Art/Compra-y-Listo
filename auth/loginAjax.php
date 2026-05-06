<?php
session_start();
include("../config/conection.php");
$con = conection();

header('Content-Type: application/json');

if($_SERVER["REQUEST_METHOD"] !== "POST"){
    echo json_encode(["status"=>"error","message"=>"Método no permitido"]);
    exit();
}

$correo    = trim($_POST['correo'] ?? '');
$password  = trim($_POST['contraseña'] ?? '');

if(empty($correo) || empty($password)){
    echo json_encode(["status"=>"error","message"=>"Completa todos los campos."]);
    exit();
}

$stmt = mysqli_prepare($con, "SELECT * FROM usuarios WHERE correo = ?");
mysqli_stmt_bind_param($stmt, "s", $correo);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$row    = mysqli_fetch_assoc($result);

if($row && $row['contraseña'] === $password){
    $_SESSION['usuarios'] = $row['nombreUsuario'];
    $_SESSION['idUsuario'] = $row['idUsuario'];
    echo json_encode(["status"=>"success","redirect"=>"../public/dashboardAdmin.php"]);
} else {
    echo json_encode(["status"=>"error","message"=>"Correo o contraseña incorrectos."]);
}
?>
