<?php
session_start();
include("../../config/conection.php");
$con = conection();

header('Content-Type: application/json');

if(!isset($_SESSION['usuarios'])){
    echo json_encode(["status"=>"error","message"=>"No autorizado"]);
    exit();
}

$ids = $_POST['ids'] ?? [];

if(empty($ids) || !is_array($ids)){
    echo json_encode(["status"=>"error","message"=>"No se recibieron IDs"]);
    exit();
}

$ids = array_filter(array_map('intval', $ids), fn($id) => $id > 0);

if(empty($ids)){
    echo json_encode(["status"=>"error","message"=>"IDs inválidos"]);
    exit();
}

// Separar las que tienen productos de las que no
$conProductos = [];
$sinProductos  = [];

foreach($ids as $id){
    $check = mysqli_query($con, "SELECT COUNT(*) AS total, (SELECT nombreCategoria FROM categoria WHERE idCategoria=$id) AS nombre FROM producto WHERE idCategoria=$id");
    $row   = mysqli_fetch_assoc($check);
    if($row['total'] > 0){
        $conProductos[] = ["id" => $id, "nombre" => $row['nombre'], "total" => (int)$row['total']];
    } else {
        $sinProductos[] = $id;
    }
}

// Eliminar solo las que no tienen productos
$eliminadas = 0;
if(!empty($sinProductos)){
    $placeholders = implode(',', $sinProductos);
    $result = mysqli_query($con, "DELETE FROM categoria WHERE idCategoria IN ($placeholders)");
    if($result) $eliminadas = mysqli_affected_rows($con);
}

echo json_encode([
    "status"       => "success",
    "eliminadas"   => $eliminadas,
    "bloqueadas"   => $conProductos,
    "message"      => "$eliminadas categoría(s) eliminada(s)" . (count($conProductos) > 0 ? ", " . count($conProductos) . " bloqueada(s) por tener productos" : "")
]);
?>
