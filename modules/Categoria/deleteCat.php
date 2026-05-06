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

$id = (int)($_GET['idCategoria'] ?? 0);
if ($id <= 0) {
    echo json_encode(["status" => "error", "message" => "ID invalido"]);
    exit;
}

$total = CategoriaModel::contarProductos($con, $id);

if ($total > 0) {
    echo json_encode([
        "status"      => "has_products",
        "count"       => $total,
        "idCategoria" => $id,
        "categorias"  => CategoriaModel::listarExcepto($con, $id),
    ]);
    exit;
}

if (CategoriaModel::eliminar($con, $id)) {
    echo json_encode(["status" => "success", "message" => "Categoria eliminada"]);
} else {
    echo json_encode(["status" => "error", "message" => "Error al eliminar"]);
}
