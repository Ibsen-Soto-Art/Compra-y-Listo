<?php
session_start();
include("../../config/conection.php");
$con = conection();
if(!isset($_SESSION['usuarios'])){ echo json_encode(["status"=>"error","message"=>"No autorizado"]); exit(); }

$id = intval($_POST['id'] ?? 0);
if(!$id){ echo json_encode(["status"=>"error","message"=>"ID inválido"]); exit(); }

if(mysqli_query($con, "DELETE FROM iteminventario WHERE idItemInventario=$id")){
    echo json_encode(["status"=>"success"]);
} else {
    echo json_encode(["status"=>"error","message"=>"Error al eliminar"]);
}
