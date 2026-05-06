<?php
header('Content-Type: application/json');

include("../../config/conection.php");
$con = conection();
session_start();

$rolUsuario = $_SESSION['rol'] ?? null;
$idLogeado = $_SESSION['idUsuario'] ?? null;

if($_SERVER["REQUEST_METHOD"] != "POST"){
    echo json_encode([
        "status"=>"error",
        "message"=>"Acceso no permitido"
    ]);
    exit();
}

$id = $_POST['id'];
$nombre = $_POST['nombre'];
$correo = $_POST['correo'];
$contraseña = $_POST['contraseña'];

$rolNuevo = isset($_POST['rol']) ? $_POST['rol'] : null;


/* Verificar correo duplicado */
$sqlVerificar = "SELECT idUsuario FROM usuarios 
                 WHERE correo=? AND idUsuario != ?";
$stmt = mysqli_prepare($con,$sqlVerificar);
mysqli_stmt_bind_param($stmt,"si",$correo,$id);
mysqli_stmt_execute($stmt);
$resultado = mysqli_stmt_get_result($stmt);

if(mysqli_num_rows($resultado) > 0){

    echo json_encode([
        "status"=>"error",
        "message"=>"El correo ya está registrado en otro usuario"
    ]);
    exit();
}


/* Obtener rol actual */
$sqlRol = "SELECT rol FROM usuarios WHERE idUsuario=?";
$stmt = mysqli_prepare($con,$sqlRol);
mysqli_stmt_bind_param($stmt,"i",$id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$rowRol = mysqli_fetch_assoc($result);

$rolActual = $rowRol['rol'];


/* Gestor no puede cambiar roles */
if($rolUsuario == "gestor"){
    $rolNuevo = $rolActual;
}

/* Admin no puede cambiar su propio rol */
if($rolUsuario == "admin" && $idLogeado == $id){
    $rolNuevo = $rolActual;
}

/* Si no llega rol */
if($rolNuevo == null){
    $rolNuevo = $rolActual;
}


/* Actualizar usuario */
$sql = "UPDATE usuarios 
        SET nombreUsuario=?,
            correo=?,
            contraseña=?,
            rol=?
        WHERE idUsuario=?";

$stmt = mysqli_prepare($con,$sql);
mysqli_stmt_bind_param($stmt,"ssssi",$nombre,$correo,$contraseña,$rolNuevo,$id);

if(mysqli_stmt_execute($stmt)){

    echo json_encode([
        "status"=>"success",
        "message"=>"Usuario actualizado correctamente"
    ]);

}else{

    echo json_encode([
        "status"=>"error",
        "message"=>"Error al actualizar usuario"
    ]);

}
?>