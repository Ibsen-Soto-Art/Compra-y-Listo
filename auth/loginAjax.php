<?php
session_start();
require_once __DIR__ . "/../config/config.php";
require_once __DIR__ . "/../config/conection.php";
$con = conection();

header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(["status" => "error", "message" => "Método no permitido"]);
    exit();
}

$correo   = trim($_POST['correo']     ?? '');
$password = trim($_POST['contraseña'] ?? '');

if (empty($correo) || empty($password)) {
    echo json_encode(["status" => "error", "message" => "Completa todos los campos."]);
    exit();
}

// Usar bind_result en lugar de get_result (compatible con todos los hostings)
$stmt = mysqli_prepare($con, "SELECT idUsuario, nombreUsuario, contraseña, rol FROM usuarios WHERE correo = ? LIMIT 1");
if (!$stmt) {
    echo json_encode(["status" => "error", "message" => "Error interno."]);
    exit();
}

mysqli_stmt_bind_param($stmt, "s", $correo);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $idUsuario, $nombreUsuario, $hashPassword, $rol);
$found = mysqli_stmt_fetch($stmt);
mysqli_stmt_close($stmt);

if (!$found) {
    echo json_encode(["status" => "error", "message" => "Correo o contraseña incorrectos."]);
    exit();
}

// Verificar contraseña (soporta hash bcrypt y texto plano por compatibilidad)
$ok = password_verify($password, $hashPassword) || ($hashPassword === $password);

if ($ok) {
    $_SESSION['usuarios']  = $nombreUsuario;
    $_SESSION['idUsuario'] = $idUsuario;
    $_SESSION['rol']       = $rol;
    echo json_encode(["status" => "success", "redirect" => SITE_URL . "/admin"]);
} else {
    echo json_encode(["status" => "error", "message" => "Correo o contraseña incorrectos."]);
}
