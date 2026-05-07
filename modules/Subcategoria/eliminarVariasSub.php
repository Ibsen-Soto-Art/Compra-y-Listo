<?php
use App\Models\SubcategoriaModel;

session_start();
header('Content-Type: application/json');
include "../../config/conection.php";
$con = conection();

if (!isset($_SESSION['usuarios'])) {
    echo json_encode(['status' => 'error', 'message' => 'No autorizado']);
    exit;
}

$ids = array_values(array_filter(array_map('intval', $_POST['ids'] ?? []), fn($v) => $v > 0));
if (empty($ids)) {
    echo json_encode(['status' => 'error', 'message' => 'No se enviaron IDs']);
    exit;
}

$eliminados = SubcategoriaModel::eliminarVarias($con, $ids);
echo json_encode(['status' => 'success', 'eliminados' => $eliminados]);
