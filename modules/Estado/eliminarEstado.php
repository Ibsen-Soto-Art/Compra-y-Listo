<?php
session_start();
include("../../config/conection.php");
$con = conection();

header('Content-Type: application/json');

if(!isset($_SESSION['usuarios'])){
    echo json_encode(["status"=>"error","message"=>"No autorizado"]);
    exit();
}

$data = json_decode(file_get_contents("php://input"), true);

if(empty($data['ids']) || !is_array($data['ids'])){
    echo json_encode(["status"=>"error","message"=>"No se recibieron IDs"]);
    exit();
}

$ids = array_filter(array_map('intval', $data['ids']), fn($id) => $id > 0);

if(empty($ids)){
    echo json_encode(["status"=>"error","message"=>"IDs inválidos"]);
    exit();
}

$placeholders = implode(',', $ids);
$result = mysqli_query($con, "DELETE FROM estado WHERE idEstado IN ($placeholders)");

if($result){
    $eliminados = mysqli_affected_rows($con);
    echo json_encode(["status"=>"success","message"=>"$eliminados estado(s) eliminado(s)","eliminados"=>$eliminados]);
} else {
    echo json_encode(["status"=>"error","message"=>"Error: ".mysqli_error($con)]);
}
?>
