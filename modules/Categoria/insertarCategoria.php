<?php
session_start();
include("../../config/conection.php");

$con = conection();

if(isset($_POST['nombreCategoria'])){

    $nombre = trim($_POST['nombreCategoria']);
    $imagen = $_POST['imagenCategoria'] ?? "";
    $idUsuario = $_SESSION['idUsuario'];

    /* Verificar que no exista otra categoría con el mismo nombre */
    $verificar = "SELECT idCategoria FROM categoria WHERE nombreCategoria = ?";
    $stmt = mysqli_prepare($con, $verificar);
    mysqli_stmt_bind_param($stmt, "s", $nombre);
    mysqli_stmt_execute($stmt);
    $resultado = mysqli_stmt_get_result($stmt);

    if(mysqli_num_rows($resultado) > 0){
        echo "<p style='color:red;'>Ya existe una categoría con ese nombre</p>";
        exit();
    }

    /* Insertar categoría */
    $sql = "INSERT INTO categoria(nombreCategoria, imagenCategoria, idUsuario)
            VALUES(?, ?, ?)";

    $stmt2 = mysqli_prepare($con, $sql);
    mysqli_stmt_bind_param($stmt2, "ssi", $nombre, $imagen, $idUsuario);

    if(mysqli_stmt_execute($stmt2)){
        echo "<p style='color:green;'>success: Categoría registrada correctamente</p>";
    }else{
        echo "<p style='color:red;'>Error al registrar la categoría</p>";
    }

}
?>
