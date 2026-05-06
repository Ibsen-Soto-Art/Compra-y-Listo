<?php
ob_start();
include("../../config/conection.php");
$con = conection();
ob_end_clean();
header('Content-Type: application/json; charset=utf-8');

$idDepartamento = intval($_GET['idDepartamento'] ?? 0);
if(!$idDepartamento){ echo json_encode([]); exit(); }

$q = mysqli_query($con,
    "SELECT idMunicipio, nombre FROM municipio
     WHERE idDepartamento = $idDepartamento
     ORDER BY nombre ASC");

$result = [];
while($r = mysqli_fetch_assoc($q)){
    $result[] = ["id" => (int)$r['idMunicipio'], "nombre" => $r['nombre']];
}
echo json_encode($result);
