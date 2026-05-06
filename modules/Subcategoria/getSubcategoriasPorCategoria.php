<?php
header('Content-Type: application/json');
session_start();
include("../../config/conection.php");
$con = conection();

if(!isset($_SESSION['usuarios'])){
    echo json_encode([]);
    exit();
}

$idCategoria = intval($_GET['idCategoria'] ?? 0);
if($idCategoria <= 0){
    echo json_encode([]);
    exit();
}

$stmt = mysqli_prepare($con, "SELECT idSubcategoria, nombreSubcategoria FROM subcategoria WHERE idCategoria=? AND estadoSubcategoria='Activo' ORDER BY nombreSubcategoria");
mysqli_stmt_bind_param($stmt, "i", $idCategoria);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$subcats = [];
while($row = mysqli_fetch_assoc($result)){
    $subcats[] = $row;
}

echo json_encode($subcats);
