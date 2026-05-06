<?php

session_start();
include("../../config/conection.php");

$con = conection();

if(!isset($_SESSION['idUsuario'])){
    echo "<p style='color:red;'>Sesión no válida</p>";
    exit();
}

if($_SERVER["REQUEST_METHOD"] == "POST"){

    $nombreEstado = trim($_POST['nombreEstado']);
    $idUsuario = $_SESSION['idUsuario']; // usuario logueado

    if(empty($nombreEstado)){
        echo "<p style='color:red;'>El nombre del estado es obligatorio</p>";
        exit();
    }

    // verificar duplicados
    $verificar = "SELECT idEstado FROM estado WHERE nombreEstado = ?";
    $stmt = mysqli_prepare($con, $verificar);
    mysqli_stmt_bind_param($stmt, "s", $nombreEstado);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);

    if(mysqli_stmt_num_rows($stmt) > 0){
        echo "<p style='color:red;'>Este estado ya existe</p>";
        exit();
    }

    mysqli_stmt_close($stmt);



    // insertar estado con idUsuario
    $sql = "INSERT INTO estado(nombreEstado, idUsuario) VALUES(?,?)";
    $stmt = mysqli_prepare($con, $sql);
    mysqli_stmt_bind_param($stmt, "si", $nombreEstado, $idUsuario);

    if(mysqli_stmt_execute($stmt)){
        echo "<p style='color:green;' class='success'>Estado agregado correctamente</p>";
    }else{
        echo "<p style='color:red;'>Error al agregar el estado</p>";
    }

}
?>