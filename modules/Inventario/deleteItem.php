<?php
use App\Models\InventarioModel;

session_start();
header('Content-Type: application/json');
include "../../config/conection.php";
$con = conection();

if (!isset($_SESSION['usuarios'])) {
    echo json_encode(["status" => "error", "message" => "No autorizado"]);
    exit;
}

$id = (int)($_POST['id'] ?? 0);
if (!$id) {
    echo json_encode(["status" => "error", "message" => "ID invalido"]);
    exit;
}

if (InventarioModel::eliminar($con, $id)) {
    echo json_encode(["status" => "success"]);
} else {
    echo json_encode(["status" => "error", "message" => "Error al eliminar"]);
}
