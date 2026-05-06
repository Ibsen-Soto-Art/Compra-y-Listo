<?php
ob_start();
include "../../config/conection.php";
require_once "Model.php";
$con = conection();
ob_end_clean();
header('Content-Type: application/json; charset=utf-8');

$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    echo json_encode(["error" => "ID no recibido"]);
    exit;
}

$producto = ProductoModel::obtener($con, $id);
if (!$producto) {
    echo json_encode(["error" => "Producto no encontrado"]);
    exit;
}

$producto['imagenes'] = ProductoModel::getImagenes($con, $id);
echo json_encode($producto);
