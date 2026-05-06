<?php
header('Content-Type: application/json');
session_start();
include("../../config/conection.php");
$con = conection();

if(!isset($_SESSION['usuarios'])){
    echo json_encode(['status'=>'error','message'=>'No autorizado']);
    exit();
}

$nombre    = trim($_POST['nombreSubcategoria'] ?? '');
$idCat     = intval($_POST['idCategoria'] ?? 0);
$estado    = $_POST['estadoSubcategoria'] ?? 'Activo';
$estado    = ($estado === 'Oculto') ? 'Oculto' : 'Activo';
$imagenUrl = trim($_POST['imagenUrl'] ?? '') ?: null;

if(empty($nombre) || $idCat <= 0){
    echo json_encode(['status'=>'error','message'=>'Datos incompletos']);
    exit();
}

// Verificar duplicado en la misma categoría
$stmt = mysqli_prepare($con, "SELECT idSubcategoria FROM subcategoria WHERE nombreSubcategoria=? AND idCategoria=?");
mysqli_stmt_bind_param($stmt, "si", $nombre, $idCat);
mysqli_stmt_execute($stmt);
mysqli_stmt_store_result($stmt);
if(mysqli_stmt_num_rows($stmt) > 0){
    echo json_encode(['status'=>'error','message'=>'Ya existe una subcategoría con ese nombre en esta categoría']);
    exit();
}

$stmt2 = mysqli_prepare($con, "INSERT INTO subcategoria (nombreSubcategoria, idCategoria, estadoSubcategoria, imagenUrl) VALUES (?, ?, ?, ?)");
mysqli_stmt_bind_param($stmt2, "siss", $nombre, $idCat, $estado, $imagenUrl);

if(mysqli_stmt_execute($stmt2)){
    echo json_encode(['status'=>'success','message'=>'Subcategoría agregada correctamente']);
} else {
    echo json_encode(['status'=>'error','message'=>'Error al guardar: ' . mysqli_error($con)]);
}
