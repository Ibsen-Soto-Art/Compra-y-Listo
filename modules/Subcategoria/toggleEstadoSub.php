<?php
header('Content-Type: application/json');
session_start();
include("../../config/conection.php");
$con = conection();

if(!isset($_SESSION['usuarios'])){
    echo json_encode(['status'=>'error','message'=>'No autorizado']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$id   = intval($data['id'] ?? 0);

if($id <= 0){
    echo json_encode(['status'=>'error','message'=>'ID inválido']);
    exit();
}

// Obtener estado actual
$stmt = mysqli_prepare($con, "SELECT estadoSubcategoria FROM subcategoria WHERE idSubcategoria=?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $estadoActual);
mysqli_stmt_fetch($stmt);
mysqli_stmt_close($stmt);

if($estadoActual === null){
    echo json_encode(['status'=>'error','message'=>'Subcategoría no encontrada']);
    exit();
}

$nuevoEstado = ($estadoActual === 'Activo') ? 'Oculto' : 'Activo';

$stmt2 = mysqli_prepare($con, "UPDATE subcategoria SET estadoSubcategoria=? WHERE idSubcategoria=?");
mysqli_stmt_bind_param($stmt2, "si", $nuevoEstado, $id);

if(mysqli_stmt_execute($stmt2)){
    echo json_encode(['status'=>'success','nuevoEstado'=>$nuevoEstado]);
} else {
    echo json_encode(['status'=>'error','message'=>'Error al actualizar: ' . mysqli_error($con)]);
}
