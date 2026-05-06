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

$ids = array_values(array_filter(array_map('intval', $_POST['ids'] ?? []), fn($v) => $v > 0));
if (empty($ids)) {
    echo json_encode(["status" => "error", "message" => "Sin items"]);
    exit;
}

$estado = in_array($_POST['estadoItem'] ?? '', ['Disponible', 'Vendido'])
    ? $_POST['estadoItem']
    : 'Disponible';

$actualizados = InventarioModel::cambiarEstado($con, $ids, $estado);
echo json_encode(["status" => "success", "actualizados" => $actualizados]);
