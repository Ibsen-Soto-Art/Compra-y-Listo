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

$data = json_decode(file_get_contents('php://input'), true);
$id   = (int)($data['id'] ?? 0);

if ($id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'ID invalido']);
    exit;
}

$estadoActual = SubcategoriaModel::obtenerEstado($con, $id);
if ($estadoActual === null) {
    echo json_encode(['status' => 'error', 'message' => 'Subcategoria no encontrada']);
    exit;
}

$nuevoEstado = $estadoActual === 'Activo' ? 'Oculto' : 'Activo';
SubcategoriaModel::toggleEstado($con, $id, $nuevoEstado);

echo json_encode(['status' => 'success', 'nuevoEstado' => $nuevoEstado]);
