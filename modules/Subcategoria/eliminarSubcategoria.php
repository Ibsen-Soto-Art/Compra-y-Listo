<?php
session_start();
header('Content-Type: application/json');
include "../../config/conection.php";
require_once "Model.php";
$con = conection();

if (!isset($_SESSION['usuarios'])) {
    echo json_encode(['status' => 'error', 'message' => 'No autorizado']);
    exit;
}

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'ID invalido']);
    exit;
}

if (SubcategoriaModel::eliminar($con, $id)) {
    echo json_encode(['status' => 'success', 'message' => 'Subcategoria eliminada']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Error al eliminar']);
}
