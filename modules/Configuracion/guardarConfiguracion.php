<?php
session_start();
include("../../config/conection.php");
$con = conection();

header('Content-Type: application/json');

if (!isset($_SESSION['idUsuario'])) {
    echo json_encode(["error" => "No autorizado"]);
    exit;
}

$stmt_rol = mysqli_prepare($con, "SELECT rol FROM usuarios WHERE idUsuario = ?");
mysqli_stmt_bind_param($stmt_rol, "i", $_SESSION['idUsuario']);
mysqli_stmt_execute($stmt_rol);
$rol = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_rol))['rol'] ?? '';

if ($rol !== 'admin') {
    echo json_encode(["error" => "No autorizado"]);
    exit;
}

$clave = $_POST['clave'] ?? '';
$valor = trim($_POST['valor'] ?? '');

if (empty($clave) || empty($valor)) {
    echo json_encode(["error" => "Datos incompletos"]);
    exit;
}

// Crear tabla si no existe
mysqli_query($con, "CREATE TABLE IF NOT EXISTS configuracion (
    id INT PRIMARY KEY AUTO_INCREMENT,
    clave VARCHAR(100) UNIQUE NOT NULL,
    valor TEXT NOT NULL
)");

$stmt = mysqli_prepare($con, "INSERT INTO configuracion (clave, valor) VALUES (?, ?)
    ON DUPLICATE KEY UPDATE valor = VALUES(valor)");
mysqli_stmt_bind_param($stmt, "ss", $clave, $valor);

if (mysqli_stmt_execute($stmt)) {
    echo json_encode(["status" => "success", "message" => "Configuración guardada"]);
} else {
    echo json_encode(["error" => "Error al guardar"]);
}
