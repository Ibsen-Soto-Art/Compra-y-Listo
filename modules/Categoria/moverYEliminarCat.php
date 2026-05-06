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

$idOrigen  = intval($data['idOrigen']  ?? 0);
$idDestino = intval($data['idDestino'] ?? 0);

if($idOrigen <= 0 || $idDestino <= 0){
    echo json_encode(["status"=>"error","message"=>"IDs inválidos"]);
    exit();
}

if($idOrigen === $idDestino){
    echo json_encode(["status"=>"error","message"=>"Origen y destino no pueden ser iguales"]);
    exit();
}

// Verificar que el destino existe
$checkDest = mysqli_query($con, "SELECT idCategoria FROM categoria WHERE idCategoria=$idDestino");
if(mysqli_num_rows($checkDest) === 0){
    echo json_encode(["status"=>"error","message"=>"Categoría destino no existe"]);
    exit();
}

// Mover los productos
$mover = mysqli_query($con, "UPDATE producto SET idCategoria=$idDestino WHERE idCategoria=$idOrigen");
if(!$mover){
    echo json_encode(["status"=>"error","message"=>"Error al mover productos: ".mysqli_error($con)]);
    exit();
}
$movidos = mysqli_affected_rows($con);

// Eliminar la categoría origen (ya sin productos)
$eliminar = mysqli_query($con, "DELETE FROM categoria WHERE idCategoria=$idOrigen");
if(!$eliminar){
    echo json_encode(["status"=>"error","message"=>"Productos movidos pero error al eliminar categoría: ".mysqli_error($con)]);
    exit();
}

echo json_encode([
    "status"  => "success",
    "movidos" => $movidos,
    "message" => "$movidos producto(s) movidos y categoría eliminada"
]);
?>
