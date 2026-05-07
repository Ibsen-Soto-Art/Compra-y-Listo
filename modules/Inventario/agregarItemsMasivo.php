<?php
use App\Models\InventarioModel;

session_start();
header('Content-Type: application/json');
include "../../config/conection.php";
$con = conection();

if (!isset($_SESSION['usuarios'])) {
    echo json_encode(["status" => "error", "message" => "No autorizado"]);
    exit;
}

$idProducto = (int)($_POST['idProducto'] ?? 0);
$cantidad   = (int)($_POST['cantidad']   ?? 0);
$estado     = in_array($_POST['estadoItem'] ?? '', ['Disponible', 'Vendido'])
    ? $_POST['estadoItem'] : 'Disponible';

if (!$idProducto || $cantidad < 1) {
    echo json_encode(["status" => "error", "message" => "Datos invalidos"]);
    exit;
}

// Construir prefijo con iniciales de categoria + subcategorias + ID producto
$rows = InventarioModel::getInfoProducto($con, $idProducto);
$categoria = '';
$subcats   = [];
foreach ($rows as $r) {
    if (!$categoria) $categoria = $r['nombreCategoria'];
    $subcats[] = $r['nombreSubcategoria'];
}

$prefix = '';
if ($categoria) $prefix .= strtoupper(mb_substr($categoria, 0, 1));
foreach ($subcats as $sub) $prefix .= strtoupper(mb_substr($sub, 0, 1));
$prefix .= '-P' . $idProducto . '-';

$maxNum    = InventarioModel::getMaxNumSerie($con, $prefix);
$insertados = 0;
$errores    = [];

for ($i = 1; $i <= $cantidad; $i++) {
    $serie = $prefix . str_pad($maxNum + $i, 3, '0', STR_PAD_LEFT);

    // Fallback en caso de colision
    if (InventarioModel::serieEnUso($con, $serie)) {
        $serie = $prefix . strtoupper(base_convert(mt_rand(100000, 999999), 10, 36));
    }

    if (InventarioModel::insertar($con, $idProducto, $serie, $estado)) {
        $insertados++;
    } else {
        $errores[] = "Fallo al insertar serie $serie";
    }
}

echo json_encode([
    "status"     => $insertados > 0 ? "success" : "error",
    "insertados" => $insertados,
    "message"    => $insertados > 0
        ? "$insertados item(s) agregado(s) correctamente"
        : "Error al insertar: " . implode(', ', $errores),
]);
