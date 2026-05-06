<?php
session_start();
include("../../config/conection.php");
$con = conection();
header('Content-Type: application/json');

if(!isset($_SESSION['usuarios'])){ echo json_encode(["error"=>"No autorizado"]); exit(); }

$idProducto = intval($_GET['idProducto'] ?? 0);
if(!$idProducto){ echo json_encode(["error"=>"ID inválido"]); exit(); }

// Obtener categoría y subcategorías del producto
$qInfo = mysqli_query($con, "
    SELECT c.nombreCategoria, s.nombreSubcategoria
    FROM productosubcategoria ps
    INNER JOIN subcategoria s ON s.idSubcategoria = ps.idSubcategoria
    INNER JOIN categoria c    ON c.idCategoria    = s.idCategoria
    WHERE ps.idProducto = $idProducto
    ORDER BY c.nombreCategoria, s.nombreSubcategoria
");

$categoria    = '';
$subcategorias = [];
while($r = mysqli_fetch_assoc($qInfo)){
    if(empty($categoria)) $categoria = $r['nombreCategoria'];
    $subcategorias[] = $r['nombreSubcategoria'];
}

// Construir prefijo: 1ª letra categoría + 1ª letra de cada subcategoría
$prefix = '';
if($categoria) $prefix .= strtoupper(mb_substr($categoria, 0, 1));
foreach($subcategorias as $sub){
    $prefix .= strtoupper(mb_substr($sub, 0, 1));
}
$prefix .= '-';

// Calcular el siguiente número: buscar el máximo existente con este prefijo
$prefixEsc = mysqli_real_escape_string($con, $prefix);
$qMax = mysqli_query($con, "
    SELECT numeroSerie FROM iteminventario
    WHERE idProducto = $idProducto AND numeroSerie LIKE '$prefixEsc%'
");
$maxNum = 0;
while($r = mysqli_fetch_assoc($qMax)){
    $num = intval(substr($r['numeroSerie'], strlen($prefix)));
    if($num > $maxNum) $maxNum = $num;
}

echo json_encode([
    "prefix"        => $prefix,
    "nextNum"       => $maxNum + 1,
    "categoria"     => $categoria,
    "subcategorias" => $subcategorias
]);
