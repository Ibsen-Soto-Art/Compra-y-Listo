<?php
session_start();
include("../../config/conection.php");
$con = conection();
header('Content-Type: application/json');

if(!isset($_SESSION['usuarios'])){ echo json_encode(["status"=>"error","message"=>"No autorizado"]); exit(); }

$ids = $_POST['ids'] ?? [];
$ids = array_map('intval', $ids);
$ids = array_filter($ids, fn($v) => $v > 0);

if(empty($ids)){
    echo json_encode(["status"=>"error","message"=>"No se recibieron IDs"]);
    exit();
}

$placeholders = implode(',', array_fill(0, count($ids), '?'));
$tipos = str_repeat('i', count($ids));

$stmt = mysqli_prepare($con, "DELETE FROM iteminventario WHERE idItemInventario IN ($placeholders)");
mysqli_stmt_bind_param($stmt, $tipos, ...$ids);

if(mysqli_stmt_execute($stmt)){
    echo json_encode(["status"=>"success","eliminados"=>mysqli_affected_rows($con)]);
} else {
    echo json_encode(["status"=>"error","message"=>"Error al eliminar"]);
}
