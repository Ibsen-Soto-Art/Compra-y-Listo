<?php
session_start();
header('Content-Type: application/json');
include "../../config/conection.php";
require_once "Model.php";
$con = conection();

if (!isset($_SESSION['usuarios'])) {
    echo json_encode(["status" => "error", "message" => "Sesion no valida"]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["status" => "error", "message" => "Solicitud no permitida"]);
    exit;
}

$id     = (int)($_POST['id']     ?? 0);
$nombre = trim($_POST['nombre']  ?? '');
$imagen = trim($_POST['imagen']  ?? '');

if ($id <= 0 || !$nombre) {
    echo json_encode(["status" => "error", "message" => "Todos los campos son obligatorios"]);
    exit;
}

if (CategoriaModel::nombreExiste($con, $nombre, $id)) {
    echo json_encode(["status" => "error", "message" => "Ya existe una categoria con ese nombre"]);
    exit;
}

if (CategoriaModel::actualizar($con, $id, $nombre, $imagen)) {
    echo json_encode(["status" => "success", "message" => "Categoria actualizada correctamente"]);
} else {
    echo json_encode(["status" => "error", "message" => "Error al actualizar la categoria"]);
}
