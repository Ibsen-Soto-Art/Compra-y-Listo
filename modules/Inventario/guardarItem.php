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

$idProducto  = (int)($_POST['idProducto'] ?? 0);
$idItem      = (int)($_POST['idItem']     ?? 0);
$serie       = trim($_POST['numeroSerie'] ?? '');
$estado      = in_array($_POST['estadoItem'] ?? '', ['Disponible', 'Vendido'])
    ? $_POST['estadoItem'] : 'Disponible';

if (!$idProducto || !$serie) {
    echo json_encode(["status" => "error", "message" => "Datos incompletos"]);
    exit;
}

if ($idItem > 0) {
    if (InventarioModel::serieEnUso($con, $serie, $idItem)) {
        echo json_encode(["status" => "error", "message" => "Ese numero de serie ya esta en uso por otra unidad"]);
        exit;
    }
    $ok = InventarioModel::actualizar($con, $idItem, $idProducto, $serie, $estado);
} else {
    if (InventarioModel::serieEnUso($con, $serie)) {
        echo json_encode(["status" => "error", "message" => "Ese numero de serie ya existe en el inventario"]);
        exit;
    }
    $ok = InventarioModel::insertar($con, $idProducto, $serie, $estado);
}

echo json_encode($ok
    ? ["status" => "success"]
    : ["status" => "error", "message" => "Error en base de datos"]
);
