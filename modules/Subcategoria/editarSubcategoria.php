<?php
header('Content-Type: application/json');
session_start();
include("../../config/conection.php");
$con = conection();

if(!isset($_SESSION['usuarios'])){
    echo json_encode(['status'=>'error','message'=>'No autorizado']);
    exit();
}

$id        = intval($_POST['id'] ?? 0);
$nombre    = trim($_POST['nombre'] ?? '');
$idCat     = intval($_POST['idCategoria'] ?? 0);
$estado    = $_POST['estado'] ?? 'Activo';
$estado    = ($estado === 'Oculto') ? 'Oculto' : 'Activo';
$imagenUrl = trim($_POST['imagenUrl'] ?? '') ?: null;

if($id <= 0 || empty($nombre) || $idCat <= 0){
    echo json_encode(['status'=>'error','message'=>'Datos incompletos']);
    exit();
}

// Verificar duplicado excluyendo el mismo registro
$stmt = mysqli_prepare($con, "SELECT idSubcategoria FROM subcategoria WHERE nombreSubcategoria=? AND idCategoria=? AND idSubcategoria!=?");
mysqli_stmt_bind_param($stmt, "sii", $nombre, $idCat, $id);
mysqli_stmt_execute($stmt);
mysqli_stmt_store_result($stmt);
if(mysqli_stmt_num_rows($stmt) > 0){
    echo json_encode(['status'=>'error','message'=>'Ya existe una subcategoría con ese nombre en esta categoría']);
    exit();
}

$stmt2 = mysqli_prepare($con, "UPDATE subcategoria SET nombreSubcategoria=?, idCategoria=?, estadoSubcategoria=?, imagenUrl=? WHERE idSubcategoria=?");
mysqli_stmt_bind_param($stmt2, "sissi", $nombre, $idCat, $estado, $imagenUrl, $id);

if(mysqli_stmt_execute($stmt2)){
    echo json_encode(['status'=>'success','message'=>'Subcategoría actualizada correctamente']);
} else {
    echo json_encode(['status'=>'error','message'=>'Error al actualizar: ' . mysqli_error($con)]);
}
