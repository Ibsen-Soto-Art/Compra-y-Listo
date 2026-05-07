<?php
use App\Models\CategoriaModel;

session_start();
header('Content-Type: application/json');
include "../../config/conection.php";
$con = conection();

if (!isset($_SESSION['usuarios'])) {
    echo json_encode(["status" => "error", "message" => "No autorizado"]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$id   = (int)($data['id'] ?? 0);

if ($id <= 0) {
    echo json_encode(["status" => "error", "message" => "ID invalido"]);
    exit;
}

$estadoActual = CategoriaModel::obtenerEstado($con, $id);
if ($estadoActual === null) {
    echo json_encode(["status" => "error", "message" => "Categoria no encontrada"]);
    exit;
}

$nuevoEstado = $estadoActual === 'Activo' ? 'Oculto' : 'Activo';
CategoriaModel::toggleEstado($con, $id, $nuevoEstado);

echo json_encode(["status" => "success", "nuevoEstado" => $nuevoEstado]);
