<?php
session_start();
include("../../config/conection.php");
$con = conection();
if(!isset($_SESSION['usuarios'])){ echo json_encode(["status"=>"error","message"=>"No autorizado"]); exit(); }

$idProducto  = intval($_POST['idProducto'] ?? 0);
$idItem      = intval($_POST['idItem'] ?? 0);
$numeroSerie = trim($_POST['numeroSerie'] ?? '');
$estadoItem  = $_POST['estadoItem'] ?? 'Disponible';

if(!$idProducto || empty($numeroSerie)){
    echo json_encode(["status"=>"error","message"=>"Datos incompletos"]);
    exit();
}

if(!in_array($estadoItem, ['Disponible','Vendido'])) $estadoItem = 'Disponible';

if($idItem > 0){
    // Editar: verificar que el nuevo número de serie no exista en OTRO ítem
    $serieEsc = mysqli_real_escape_string($con, $numeroSerie);
    $check = mysqli_fetch_row(mysqli_query($con,
        "SELECT COUNT(*) FROM iteminventario
         WHERE numeroSerie='$serieEsc' AND idItemInventario != $idItem"
    ));
    if($check[0] > 0){
        echo json_encode(["status"=>"error","message"=>"Ese número de serie ya está en uso por otra unidad"]);
        exit();
    }
    $sql  = "UPDATE iteminventario SET numeroSerie=?, estadoItem=? WHERE idItemInventario=? AND idProducto=?";
    $stmt = mysqli_prepare($con, $sql);
    mysqli_stmt_bind_param($stmt, "ssii", $numeroSerie, $estadoItem, $idItem, $idProducto);
} else {
    // Crear: verificar unicidad global (toda la tabla, no solo este producto)
    $serieEsc = mysqli_real_escape_string($con, $numeroSerie);
    $check = mysqli_fetch_row(mysqli_query($con,
        "SELECT COUNT(*) FROM iteminventario WHERE numeroSerie='$serieEsc'"
    ));
    if($check[0] > 0){
        echo json_encode(["status"=>"error","message"=>"Ese número de serie ya existe en el inventario"]);
        exit();
    }
    $sql  = "INSERT INTO iteminventario (idProducto, numeroSerie, estadoItem) VALUES (?, ?, ?)";
    $stmt = mysqli_prepare($con, $sql);
    mysqli_stmt_bind_param($stmt, "iss", $idProducto, $numeroSerie, $estadoItem);
}

if(mysqli_stmt_execute($stmt)){
    echo json_encode(["status"=>"success"]);
} else {
    echo json_encode(["status"=>"error","message"=>"Error en base de datos: " . mysqli_error($con)]);
}
