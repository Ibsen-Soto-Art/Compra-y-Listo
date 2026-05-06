<?php
header('Content-Type: application/json');
session_start();
include("../../config/conection.php");
$con = conection();

if(!isset($_SESSION['usuarios'])){
    echo json_encode(['status'=>'error','message'=>'No autorizado']);
    exit();
}

$id = intval($_GET['id'] ?? 0);
if($id <= 0){
    echo json_encode(['status'=>'error','message'=>'ID inválido']);
    exit();
}

$stmt = mysqli_prepare($con, "DELETE FROM subcategoria WHERE idSubcategoria=?");
mysqli_stmt_bind_param($stmt, "i", $id);

if(mysqli_stmt_execute($stmt)){
    echo json_encode(['status'=>'success','message'=>'Subcategoría eliminada']);
} else {
    echo json_encode(['status'=>'error','message'=>'Error al eliminar: ' . mysqli_error($con)]);
}
