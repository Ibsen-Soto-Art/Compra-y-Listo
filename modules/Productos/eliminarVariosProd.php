<?php
session_start();
header('Content-Type: application/json');
include "../../config/conection.php";
require_once "Model.php";
$con = conection();

if (!isset($_SESSION['usuarios'])) {
    echo json_encode(["status" => "error", "message" => "No autorizado"]);
    exit;
}

$ids = array_values(array_filter(array_map('intval', $_POST['ids'] ?? []), fn($id) => $id > 0));
if (empty($ids)) {
    echo json_encode(["status" => "error", "message" => "No se recibieron IDs"]);
    exit;
}

$raiz = realpath(__DIR__ . '/../../') . '/';

// Eliminar archivos fisicos de todas las imagenes de los productos
foreach ($ids as $id) {
    foreach (ProductoModel::getImagenes($con, $id) as $img) {
        $archivo = ProductoModel::rutaFisica($img['rutaImagen'], $raiz);
        if (file_exists($archivo)) unlink($archivo);
    }
    $carpeta = $raiz . "uploads/productos/$id/";
    if (is_dir($carpeta) && count(scandir($carpeta)) === 2) rmdir($carpeta);
}

ProductoModel::eliminarImagenesPorProducto($con, $ids);
$eliminados = ProductoModel::eliminarVarios($con, $ids);

echo json_encode([
    "status"     => "success",
    "eliminados" => $eliminados,
    "message"    => "$eliminados producto(s) eliminado(s)",
]);
