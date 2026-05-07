<?php
use App\Models\UsuarioModel;

session_start();
header('Content-Type: application/json');
include "../../config/conection.php";
$con = conection();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["status" => "error", "message" => "Acceso no permitido"]);
    exit;
}

$rolSesion = $_SESSION['rol']       ?? null;
$idLogeado = (int)($_SESSION['idUsuario'] ?? 0);

$id      = (int)trim($_POST['id']         ?? 0);
$nombre  = trim($_POST['nombre']          ?? '');
$correo  = trim($_POST['correo']          ?? '');
$pass    = trim($_POST['contraseña']      ?? '');
$rolNuevo = isset($_POST['rol']) ? trim($_POST['rol']) : null;

if (!$id || !$nombre || !$correo || !$pass) {
    echo json_encode(["status" => "error", "message" => "Campos obligatorios incompletos"]);
    exit;
}

if (UsuarioModel::correoExiste($con, $correo, $id)) {
    echo json_encode(["status" => "error", "message" => "El correo ya esta registrado en otro usuario"]);
    exit;
}

$rolActual = UsuarioModel::obtenerRol($con, $id);

// Gestores no pueden cambiar roles; admins no pueden cambiar su propio rol
if ($rolSesion === 'gestor' || ($rolSesion === 'admin' && $idLogeado === $id) || $rolNuevo === null) {
    $rolNuevo = $rolActual;
}

if (UsuarioModel::actualizar($con, $id, $nombre, $correo, $pass, $rolNuevo)) {
    echo json_encode(["status" => "success", "message" => "Usuario actualizado correctamente"]);
} else {
    echo json_encode(["status" => "error", "message" => "Error al actualizar usuario"]);
}
