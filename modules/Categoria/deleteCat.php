<?php
session_start();
include("../../config/conection.php");
$con = conection();

header('Content-Type: application/json');

if(!isset($_SESSION['usuarios'])){
    echo json_encode(["status"=>"error","message"=>"No autorizado"]);
    exit();
}

$id = intval($_GET['idCategoria'] ?? 0);
if($id <= 0){
    echo json_encode(["status"=>"error","message"=>"ID inválido"]);
    exit();
}

// Verificar si tiene productos
$check = mysqli_query($con, "SELECT COUNT(*) AS total FROM producto WHERE idCategoria=$id");
$total = mysqli_fetch_assoc($check)['total'];

if($total > 0){
    // Devolver lista de otras categorías disponibles para mover
    $cats = mysqli_query($con, "SELECT idCategoria, nombreCategoria FROM categoria WHERE idCategoria != $id ORDER BY nombreCategoria ASC");
    $lista = [];
    while($c = mysqli_fetch_assoc($cats)) $lista[] = $c;

    echo json_encode([
        "status"      => "has_products",
        "count"       => (int)$total,
        "idCategoria" => $id,
        "categorias"  => $lista
    ]);
    exit();
}

$result = mysqli_query($con, "DELETE FROM categoria WHERE idCategoria=$id");
if($result){
    echo json_encode(["status"=>"success","message"=>"Categoría eliminada"]);
} else {
    echo json_encode(["status"=>"error","message"=>"Error: ".mysqli_error($con)]);
}
?>
