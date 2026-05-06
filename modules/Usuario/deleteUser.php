<?php
    session_start();
    include("../../config/conection.php");
    $con = conection();

    $idEliminar = $_GET['id'];
    $idUsuario = $_SESSION['idUsuario'];

    $sqlRol = "SELECT rol FROM usuarios WHERE idUsuario=$idUsuario";
    $res = mysqli_query($con,$sqlRol);
    $datos = mysqli_fetch_assoc($res);

    if($datos['rol'] != "admin"){
        die("No tienes permisos");
    }

    if($idEliminar == $idUsuario){
        die("No puedes eliminar tu propio usuario");
    }

    $sql = "DELETE FROM usuarios WHERE idUsuario=$idEliminar";
    mysqli_query($con,$sql);

    header("Location: FormularioUser.php");

?>