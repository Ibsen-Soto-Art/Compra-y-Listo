<?php
use App\Models\SubcategoriaModel;

session_start();
header('Content-Type: application/json');
include "../../config/conection.php";
$con = conection();

if (!isset($_SESSION['usuarios'])) {
    echo json_encode([]);
    exit;
}

$idCategoria = (int)($_GET['idCategoria'] ?? 0);
if ($idCategoria <= 0) {
    echo json_encode([]);
    exit;
}

echo json_encode(SubcategoriaModel::getPorCategoria($con, $idCategoria));
