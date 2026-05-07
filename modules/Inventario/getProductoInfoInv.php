<?php
use App\Models\InventarioModel;

session_start();
header('Content-Type: application/json');
include "../../config/conection.php";
$con = conection();

if (!isset($_SESSION['usuarios'])) {
    echo json_encode(["error" => "No autorizado"]);
    exit;
}

$idProducto = (int)($_GET['idProducto'] ?? 0);
if (!$idProducto) {
    echo json_encode(["error" => "ID invalido"]);
    exit;
}

$rows = InventarioModel::getInfoProducto($con, $idProducto);
$categoria     = '';
$subcategorias = [];
foreach ($rows as $r) {
    if (!$categoria) $categoria = $r['nombreCategoria'];
    $subcategorias[] = $r['nombreSubcategoria'];
}

$prefix = '';
if ($categoria) $prefix .= strtoupper(mb_substr($categoria, 0, 1));
foreach ($subcategorias as $sub) $prefix .= strtoupper(mb_substr($sub, 0, 1));
$prefix .= '-';

$nextNum = InventarioModel::getMaxNumSerie($con, $prefix) + 1;

echo json_encode([
    "prefix"        => $prefix,
    "nextNum"       => $nextNum,
    "categoria"     => $categoria,
    "subcategorias" => $subcategorias,
]);
