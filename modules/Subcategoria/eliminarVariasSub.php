<?php
header('Content-Type: application/json');
session_start();
include("../../config/conection.php");
$con = conection();

if(!isset($_SESSION['usuarios'])){
    echo json_encode(['status'=>'error','message'=>'No autorizado']);
    exit();
}

$ids = $_POST['ids'] ?? [];
$ids = array_map('intval', $ids);
$ids = array_filter($ids, fn($v) => $v > 0);

if(empty($ids)){
    echo json_encode(['status'=>'error','message'=>'No se enviaron IDs']);
    exit();
}

$placeholders = implode(',', array_fill(0, count($ids), '?'));
$types = str_repeat('i', count($ids));

$stmt = mysqli_prepare($con, "DELETE FROM subcategoria WHERE idSubcategoria IN ($placeholders)");
mysqli_stmt_bind_param($stmt, $types, ...$ids);

if(mysqli_stmt_execute($stmt)){
    $eliminados = mysqli_stmt_affected_rows($stmt);
    echo json_encode(['status'=>'success','eliminados'=>$eliminados]);
} else {
    echo json_encode(['status'=>'error','message'=>'Error al eliminar: ' . mysqli_error($con)]);
}
