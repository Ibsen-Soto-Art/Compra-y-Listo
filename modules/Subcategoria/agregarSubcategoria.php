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

$nombre    = trim($_POST['nombreSubcategoria'] ?? '');
$idCat     = (int)($_POST['idCategoria']       ?? 0);
$estado    = ($_POST['estadoSubcategoria'] ?? '') === 'Oculto' ? 'Oculto' : 'Activo';
$imagenUrl = trim($_POST['imagenUrl'] ?? '') ?: null;

if (!$nombre || $idCat <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Datos incompletos']);
    exit;
}

if (SubcategoriaModel::nombreExiste($con, $nombre, $idCat)) {
    echo json_encode(['status' => 'error', 'message' => 'Ya existe una subcategoria con ese nombre en esta categoria']);
    exit;
}

if (SubcategoriaModel::insertar($con, $nombre, $idCat, $estado, $imagenUrl)) {
    echo json_encode(['status' => 'success', 'message' => 'Subcategoria agregada correctamente']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Error al guardar']);
}
