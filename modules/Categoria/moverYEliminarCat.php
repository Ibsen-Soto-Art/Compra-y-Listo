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

$data      = json_decode(file_get_contents("php://input"), true);
$idOrigen  = (int)($data['idOrigen']  ?? 0);
$idDestino = (int)($data['idDestino'] ?? 0);

if ($idOrigen <= 0 || $idDestino <= 0) {
    echo json_encode(["status" => "error", "message" => "IDs invalidos"]);
    exit;
}

if ($idOrigen === $idDestino) {
    echo json_encode(["status" => "error", "message" => "Origen y destino no pueden ser iguales"]);
    exit;
}

if (!CategoriaModel::existe($con, $idDestino)) {
    echo json_encode(["status" => "error", "message" => "Categoria destino no existe"]);
    exit;
}

$resultado = CategoriaModel::moverYEliminar($con, $idOrigen, $idDestino);

if (!$resultado['ok']) {
    echo json_encode(["status" => "error", "message" => $resultado['error']]);
    exit;
}

$movidos = $resultado['movidos'];
echo json_encode([
    "status"  => "success",
    "movidos" => $movidos,
    "message" => "$movidos producto(s) movidos y categoria eliminada",
]);
