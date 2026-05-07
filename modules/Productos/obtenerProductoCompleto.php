<?php
use App\Models\ProductoModel;

ob_start();
include "../../config/conection.php";
$con = conection();
ob_end_clean();
header('Content-Type: application/json; charset=utf-8');

$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    echo json_encode(["error" => "ID no recibido"]);
    exit;
}

$conMunicipio = ProductoModel::municipioExiste($con);
$producto = ProductoModel::obtenerCompleto($con, $id, $conMunicipio);

if (!$producto) {
    echo json_encode(["error" => "Producto no encontrado"]);
    exit;
}

$imagenes = array_map(fn($img) => [
    "idImagen" => $img['idImagen'],
    "ruta"     => $img['rutaImagen'],
    "orden"    => $img['orden'],
], ProductoModel::getImagenes($con, $id));

echo json_encode([
    "idProducto"     => $producto['idProducto'],
    "nombre"         => $producto['nombreProducto'],
    "precio"         => (float)$producto['precio'],
    "ubicacion"      => $producto['ubicacion'],
    "idMunicipio"    => $producto['idMunicipio']    ? (int)$producto['idMunicipio']    : null,
    "idDepartamento" => $producto['idDepartamento'] ? (int)$producto['idDepartamento'] : null,
    "descripcion"    => $producto['descripcion'],
    "estado"         => $producto['nombreEstado'],
    "imagenes"       => $imagenes,
]);
