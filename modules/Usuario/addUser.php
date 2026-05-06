<?php
session_start();
header('Content-Type: application/json');
include "../../config/conection.php";
require_once "Model.php";
$con = conection();

// Solo admins pueden crear usuarios
$idSesion = (int)($_SESSION['idUsuario'] ?? 0);
if (!$idSesion) {
    echo json_encode(["status" => "error", "message" => "No autorizado"]);
    exit;
}
if (UsuarioModel::obtenerRol($con, $idSesion) !== 'admin') {
    echo json_encode(["status" => "error", "message" => "No tienes permisos para agregar usuarios"]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["status" => "error", "message" => "Metodo no permitido"]);
    exit;
}

$nombre  = trim($_POST['nombre']     ?? '');
$correo  = trim($_POST['correo']     ?? '');
$pass    = trim($_POST['contraseña'] ?? '');
$rol     = trim($_POST['rol']        ?? '');

if (!$nombre || !$correo || !$pass || !$rol) {
    echo json_encode(["status" => "error", "message" => "Todos los campos son obligatorios"]);
    exit;
}

$rolesValidos = ['admin', 'gestor'];
if (!in_array($rol, $rolesValidos, true)) {
    echo json_encode(["status" => "error", "message" => "Rol no valido"]);
    exit;
}

if (UsuarioModel::correoExiste($con, $correo)) {
    echo json_encode(["status" => "error", "message" => "El correo ya esta registrado"]);
    exit;
}

if (UsuarioModel::crear($con, $nombre, $correo, $pass, $rol)) {
    echo json_encode(["status" => "success", "message" => "Usuario agregado correctamente"]);
} else {
    echo json_encode(["status" => "error", "message" => "Error al registrar usuario"]);
}
