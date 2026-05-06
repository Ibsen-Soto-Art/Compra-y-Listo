<?php
include("../../config/conection.php");
$con = conection();
session_start();

if(!isset($_SESSION['idUsuario'])){
    echo json_encode(["status"=>"error","message"=>"No autorizado"]);
    exit();
}

$idSesion = $_SESSION['idUsuario'];
$rowSesion = mysqli_fetch_assoc(mysqli_query($con, "SELECT rol FROM usuarios WHERE idUsuario=$idSesion"));
if(($rowSesion['rol'] ?? '') !== 'admin'){
    echo json_encode(["status"=>"error","message"=>"No tienes permisos para agregar usuarios"]);
    exit();
}

if($_SERVER["REQUEST_METHOD"]=="POST"){

    $nombre = $_POST['nombre'];
    $correo = $_POST['correo'];
    $contraseña =$_POST['contraseña'];
    $rol = $_POST['rol'];

    // Verificar correo
    $sqlVerificar = "SELECT * FROM usuarios WHERE correo='$correo'";
    $queryVerificar = mysqli_query($con, $sqlVerificar);

    if(mysqli_num_rows($queryVerificar) > 0){
        echo json_encode([
            "status" => "error",
            "message" => "El correo ya está registrado"
        ]);
        exit();
    }

    $sqlInsert = "INSERT INTO usuarios(nombreUsuario, correo, contraseña, rol)
                  VALUES('$nombre','$correo','$contraseña', '$rol')";

    if(mysqli_query($con, $sqlInsert)){
        echo json_encode([
            "status" => "success",
            "message" => "Usuario agregado correctamente"
        ]);
    } else {
        echo json_encode([
            "status" => "error",
            "message" => "Error al registrar usuario"
        ]);
    }
}
?>