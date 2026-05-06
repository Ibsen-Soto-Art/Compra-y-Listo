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
$id   = intval($data['id'] ?? 0);

if($id <= 0){
    echo json_encode(["status"=>"error","message"=>"ID inválido"]);
    exit();
}

// Obtener estado actual
$row = mysqli_fetch_assoc(mysqli_query($con, "SELECT estadoCategoria FROM categoria WHERE idCategoria=$id"));
if(!$row){
    echo json_encode(["status"=>"error","message"=>"Categoría no encontrada"]);
    exit();
}

$nuevo = ($row['estadoCategoria'] === 'Activo') ? 'Oculto' : 'Activo';
mysqli_query($con, "UPDATE categoria SET estadoCategoria='$nuevo' WHERE idCategoria=$id");

echo json_encode([
    "status"        => "success",
    "nuevoEstado"   => $nuevo
]);
?>
