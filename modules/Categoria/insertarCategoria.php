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

$nombre    = trim($_POST['nombreCategoria'] ?? '');
$imagen    = trim($_POST['imagenCategoria'] ?? '');
$idUsuario = (int)($_SESSION['idUsuario'] ?? 0);

if (!$nombre) {
    echo json_encode(["status" => "error", "message" => "El nombre es obligatorio"]);
    exit;
}

if (CategoriaModel::nombreExiste($con, $nombre)) {
    echo json_encode(["status" => "error", "message" => "Ya existe una categoria con ese nombre"]);
    exit;
}

if (CategoriaModel::insertar($con, $nombre, $imagen, $idUsuario)) {
    echo json_encode(["status" => "success", "message" => "Categoria registrada correctamente"]);
} else {
    echo json_encode(["status" => "error", "message" => "Error al registrar la categoria"]);
}
