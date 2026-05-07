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

$ids = $_POST['ids'] ?? [];
if (empty($ids) || !is_array($ids)) {
    echo json_encode(["status" => "error", "message" => "No se recibieron IDs"]);
    exit;
}

$ids = array_values(array_filter(array_map('intval', $ids), fn($id) => $id > 0));
if (empty($ids)) {
    echo json_encode(["status" => "error", "message" => "IDs invalidos"]);
    exit;
}

$bloqueadas  = [];
$sinProductos = [];

foreach ($ids as $id) {
    $total = CategoriaModel::contarProductos($con, $id);
    if ($total > 0) {
        $bloqueadas[] = [
            "id"     => $id,
            "nombre" => CategoriaModel::obtenerNombre($con, $id),
            "total"  => $total,
        ];
    } else {
        $sinProductos[] = $id;
    }
}

$eliminadas = CategoriaModel::eliminarVarias($con, $sinProductos);

echo json_encode([
    "status"     => "success",
    "eliminadas" => $eliminadas,
    "bloqueadas" => $bloqueadas,
    "message"    => "$eliminadas categoria(s) eliminada(s)"
        . (count($bloqueadas) > 0 ? ", " . count($bloqueadas) . " bloqueada(s) por tener productos" : ""),
]);
