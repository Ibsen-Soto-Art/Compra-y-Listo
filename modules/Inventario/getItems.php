<?php
use App\Models\InventarioModel;

session_start();
header('Content-Type: application/json');
include "../../config/conection.php";
$con = conection();

if (!isset($_SESSION['usuarios'])) { echo json_encode([]); exit; }

$idProducto = (int)($_GET['idProducto'] ?? 0);
echo json_encode(InventarioModel::getItems($con, $idProducto));
