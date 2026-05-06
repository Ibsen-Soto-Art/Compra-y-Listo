<?php
session_start();
include("../../config/conection.php");
$con = conection();
header('Content-Type: application/json');

if(!isset($_SESSION['usuarios'])){ echo json_encode(["status"=>"error","message"=>"No autorizado"]); exit(); }

$ids        = $_POST['ids'] ?? [];
$estadoItem = $_POST['estadoItem'] ?? 'Disponible';

if(!is_array($ids) || empty($ids)){ echo json_encode(["status"=>"error","message"=>"Sin ítems"]); exit(); }
if(!in_array($estadoItem, ['Disponible','Vendido'])) $estadoItem = 'Disponible';

$ids = array_values(array_filter(array_map('intval', $ids), fn($v) => $v > 0));
if(empty($ids)){ echo json_encode(["status"=>"error","message"=>"IDs inválidos"]); exit(); }

$inList = implode(',', $ids);
$stmt   = mysqli_prepare($con, "UPDATE iteminventario SET estadoItem=? WHERE idItemInventario IN ($inList)");
mysqli_stmt_bind_param($stmt, "s", $estadoItem);

if(mysqli_stmt_execute($stmt)){
    echo json_encode(["status"=>"success","actualizados"=>mysqli_affected_rows($con)]);
} else {
    echo json_encode(["status"=>"error","message"=>"Error en base de datos"]);
}
