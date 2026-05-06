<?php
ob_start();
include("../../config/conection.php");
$con = conection();
ob_end_clean();
header('Content-Type: application/json; charset=utf-8');

$q = mysqli_query($con, "SELECT idDepartamento, nombre FROM departamento ORDER BY nombre ASC");
$result = [];
while($r = mysqli_fetch_assoc($q)){
    $result[] = ["id" => (int)$r['idDepartamento'], "nombre" => $r['nombre']];
}
echo json_encode($result);
