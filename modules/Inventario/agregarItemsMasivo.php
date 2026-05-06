<?php
session_start();
include("../../config/conection.php");
$con = conection();
header('Content-Type: application/json');

if(!isset($_SESSION['usuarios'])){ echo json_encode(["status"=>"error","message"=>"No autorizado"]); exit(); }

$idProducto = intval($_POST['idProducto'] ?? 0);
$cantidad   = intval($_POST['cantidad']   ?? 0);
$estadoItem = $_POST['estadoItem'] ?? 'Disponible';

if(!$idProducto || $cantidad < 1){
    echo json_encode(["status"=>"error","message"=>"Datos inválidos"]);
    exit();
}
if(!in_array($estadoItem, ['Disponible','Vendido'])) $estadoItem = 'Disponible';

// Construir prefijo con iniciales de categoría + subcategorías
$qInfo = mysqli_query($con, "
    SELECT c.nombreCategoria, s.nombreSubcategoria
    FROM productosubcategoria ps
    INNER JOIN subcategoria s ON s.idSubcategoria = ps.idSubcategoria
    INNER JOIN categoria c    ON c.idCategoria    = s.idCategoria
    WHERE ps.idProducto = $idProducto
    ORDER BY c.nombreCategoria, s.nombreSubcategoria
");
$categoria = '';
$subcats   = [];
while($r = mysqli_fetch_assoc($qInfo)){
    if(empty($categoria)) $categoria = $r['nombreCategoria'];
    $subcats[] = $r['nombreSubcategoria'];
}

$prefix = '';
if($categoria) $prefix .= strtoupper(mb_substr($categoria, 0, 1));
foreach($subcats as $sub) $prefix .= strtoupper(mb_substr($sub, 0, 1));
// Incluir el ID del producto para unicidad estructural: ej. CA-P42-001
$prefix .= '-P' . $idProducto . '-';

// Calcular el siguiente número disponible globalmente (toda la tabla)
$prefixEsc = mysqli_real_escape_string($con, $prefix);
$qMax = mysqli_query($con,
    "SELECT numeroSerie FROM iteminventario WHERE numeroSerie LIKE '$prefixEsc%'"
);
$maxNum = 0;
while($r = mysqli_fetch_assoc($qMax)){
    $num = intval(substr($r['numeroSerie'], strlen($prefix)));
    if($num > $maxNum) $maxNum = $num;
}

// Insertar verificando unicidad global en cada iteración
$insertados = 0;
$errores    = [];
$stmt = mysqli_prepare($con, "INSERT INTO iteminventario (idProducto, numeroSerie, estadoItem) VALUES (?, ?, ?)");

for($i = 1; $i <= $cantidad; $i++){
    $serie = $prefix . str_pad($maxNum + $i, 3, '0', STR_PAD_LEFT);

    // Verificar unicidad global antes de insertar
    $existe = mysqli_fetch_row(mysqli_query($con,
        "SELECT 1 FROM iteminventario WHERE numeroSerie='" . mysqli_real_escape_string($con, $serie) . "' LIMIT 1"
    ));
    if($existe){
        // Si por alguna razón ya existe, generar uno con timestamp+random
        $serie = $prefix . strtoupper(base_convert(mt_rand(100000, 999999), 10, 36));
    }

    mysqli_stmt_bind_param($stmt, "iss", $idProducto, $serie, $estadoItem);
    if(mysqli_stmt_execute($stmt)){
        $insertados++;
    } else {
        $errores[] = mysqli_error($con);
    }
}

echo json_encode([
    "status"     => $insertados > 0 ? "success" : "error",
    "insertados" => $insertados,
    "message"    => $insertados > 0
        ? "$insertados ítem(s) agregado(s) correctamente"
        : "Error al insertar: " . implode(', ', $errores)
]);
