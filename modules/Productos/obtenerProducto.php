<?php
ob_start();
include("../../config/conection.php");
$con = conection();
ob_end_clean();
ini_set('display_errors', 0);
error_reporting(0);
header('Content-Type: application/json; charset=utf-8');

// 🔴 VALIDAR ID
if(!isset($_GET['id']) || empty($_GET['id'])){
    echo json_encode(["error" => "ID no recibido"]);
    exit();
}

$id = intval($_GET['id']);

// 🔴 PRODUCTO
$sql = "SELECT
            p.idProducto,
            p.nombreProducto,
            p.precio,
            p.ubicacion,
            p.idCategoria,
            p.idEstado,
            p.descripcion,
            p.idMunicipio,
            m.idDepartamento
        FROM producto p
        LEFT JOIN municipio m ON m.idMunicipio = p.idMunicipio
        WHERE p.idProducto = $id";

$query = mysqli_query($con, $sql);

if(!$query){
    echo json_encode(["error" => "Error en la consulta"]);
    exit();
}

$producto = mysqli_fetch_assoc($query);

if(!$producto){
    echo json_encode(["error" => "Producto no encontrado"]);
    exit();
}

// 🔥 IMÁGENES (AQUÍ ESTÁ LO IMPORTANTE)
$sqlImg = "SELECT 
                idImagen,
                rutaImagen,
                esPrincipal,
                orden
           FROM imagenesproducto
           WHERE idProducto = $id
           ORDER BY orden ASC";

$queryImg = mysqli_query($con, $sqlImg);

$imagenes = [];

while($img = mysqli_fetch_assoc($queryImg)){
    $imagenes[] = $img;
}

// 🔴 UNIR TODO
$producto['imagenes'] = $imagenes;

// 🔴 RESPUESTA
header('Content-Type: application/json');
echo json_encode($producto);
?>