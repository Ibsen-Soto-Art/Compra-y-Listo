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

$id        = (int)($_POST['id']          ?? 0);
$nombre    = trim($_POST['nombre']       ?? '');
$idCat     = (int)($_POST['idCategoria'] ?? 0);
$estado    = ($_POST['estado'] ?? '') === 'Oculto' ? 'Oculto' : 'Activo';
$imagenUrl = trim($_POST['imagenUrl'] ?? '') ?: null;

if ($id <= 0 || !$nombre || $idCat <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Datos incompletos']);
    exit;
}

if (SubcategoriaModel::nombreExiste($con, $nombre, $idCat, $id)) {
    echo json_encode(['status' => 'error', 'message' => 'Ya existe una subcategoria con ese nombre en esta categoria']);
    exit;
}

if (SubcategoriaModel::actualizar($con, $id, $nombre, $idCat, $estado, $imagenUrl)) {
    echo json_encode(['status' => 'success', 'message' => 'Subcategoria actualizada correctamente']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Error al actualizar']);
}
