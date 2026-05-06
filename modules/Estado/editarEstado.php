<?php

session_start();
include("../../config/conection.php");

$con = conection();

header('Content-Type: application/json');

if(!isset($_SESSION['idUsuario'])){
    echo json_encode([
        "status" => "error",
        "message" => "Sesión no válida"
    ]);
    exit();
}

if($_SERVER["REQUEST_METHOD"] == "POST"){

    $idEstado = $_POST['idEstado'];
    $nombreEstado = trim($_POST['nombreEstado']);

    if(empty($nombreEstado)){
        echo json_encode([
            "status" => "error",
            "message" => "El nombre del estado es obligatorio"
        ]);
        exit();
    }

    // verificar duplicados
    $verificar = "SELECT idEstado FROM estado 
                  WHERE nombreEstado = ? AND idEstado != ?";

    $stmt = mysqli_prepare($con, $verificar);
    mysqli_stmt_bind_param($stmt, "si", $nombreEstado, $idEstado);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);

    if(mysqli_stmt_num_rows($stmt) > 0){

        echo json_encode([
            "status" => "error",
            "message" => "Este estado ya existe"
        ]);
        exit();

    }

    mysqli_stmt_close($stmt);

    // actualizar estado
    $sql = "UPDATE estado 
            SET nombreEstado = ?
            WHERE idEstado = ?";

    $stmt = mysqli_prepare($con, $sql);
    mysqli_stmt_bind_param($stmt, "si", $nombreEstado, $idEstado);

    if(mysqli_stmt_execute($stmt)){

        echo "<p style='color:green;' class='success'>Estado Actualizó correctamente</p>";
    }else{
        echo "<p style='color:red;'>Error al agregar el estado</p>";
    }

}
?>