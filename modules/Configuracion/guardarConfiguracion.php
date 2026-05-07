<?php
use App\Models\ConfiguracionModel;

session_start();
header('Content-Type: application/json');
include "../../config/conection.php";
$con = conection();

if (!isset($_SESSION['idUsuario'])) {
    echo json_encode(["error" => "No autorizado"]);
    exit;
}

// Solo admins pueden cambiar la configuracion
$stmtRol = mysqli_prepare($con, "SELECT rol FROM usuarios WHERE idUsuario = ?");
mysqli_stmt_bind_param($stmtRol, "i", $_SESSION['idUsuario']);
mysqli_stmt_execute($stmtRol);
$rol = mysqli_fetch_assoc(mysqli_stmt_get_result($stmtRol))['rol'] ?? '';

if ($rol !== 'admin') {
    echo json_encode(["error" => "No autorizado"]);
    exit;
}

$clave = trim($_POST['clave'] ?? '');
$valor = trim($_POST['valor'] ?? '');

if (!$clave || !$valor) {
    echo json_encode(["error" => "Datos incompletos"]);
    exit;
}

if (ConfiguracionModel::guardar($con, $clave, $valor)) {
    echo json_encode(["status" => "success", "message" => "Configuracion guardada"]);
} else {
    echo json_encode(["error" => "Error al guardar"]);
}
