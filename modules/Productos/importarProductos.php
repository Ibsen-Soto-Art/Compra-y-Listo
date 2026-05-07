<?php
// Importador real de productos desde Excel
if (!defined('ROOT_PATH')) define('ROOT_PATH', realpath(__DIR__ . '/../../'));
if (!class_exists('PhpOffice\PhpSpreadsheet\IOFactory')) {
    require ROOT_PATH . '/vendor/autoload.php';
}
if (!function_exists('conection')) require_once ROOT_PATH . '/config/conection.php';

header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) session_start();
$idUsuario = (int)($_SESSION['idUsuario'] ?? 0);

$con = conection();

if (empty($_FILES['archivo']['tmp_name'])) {
    echo json_encode(['error' => 'No se recibió ningún archivo.']);
    exit;
}

try {
    $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($_FILES['archivo']['tmp_name']);
} catch (\Exception $e) {
    echo json_encode(['error' => 'Archivo Excel inválido: ' . $e->getMessage()]);
    exit;
}

$hoja       = $spreadsheet->getActiveSheet();
$filas      = $hoja->getHighestRow();
$insertados = 0;
$errores    = 0;
$detalle    = [];
$avisos     = [];

// Mapear encabezados fila 1
$headers = [];
$colMax  = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($hoja->getHighestColumn());
for ($c = 1; $c <= $colMax; $c++) {
    $letra = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($c);
    $val   = trim((string)$hoja->getCell($letra . '1')->getValue());
    if ($val !== '') $headers[strtolower($val)] = $letra;
}

$colNombre = $headers['nombreproducto'] ?? null;
$colPrecio = $headers['precio']         ?? null;
$colDesc   = $headers['descripcion']    ?? null;
$colCat    = $headers['nombrecategoria']    ?? null;
$colSub    = $headers['nombresubcategoria'] ?? null;
$colOferta = $headers['enoferta']       ?? null;
$colDesc2  = $headers['descuento']      ?? null;

if (!$colNombre || !$colPrecio || !$colCat) {
    echo json_encode(['error' => 'Las columnas "nombreProducto", "precio" y "nombreCategoria" son obligatorias.']);
    exit;
}

// Caché categorías
$cacheCat = [];
$qc = mysqli_query($con, "SELECT idCategoria, nombreCategoria FROM categoria");
while ($r = mysqli_fetch_assoc($qc)) {
    $cacheCat[strtolower(trim($r['nombreCategoria']))] = (int)$r['idCategoria'];
}

// Caché subcategorías nombre→[id, idCategoria]
$cacheSub = [];
$qs = mysqli_query($con, "SELECT idSubcategoria, nombreSubcategoria, idCategoria FROM subcategoria");
while ($r = mysqli_fetch_assoc($qs)) {
    $cacheSub[strtolower(trim($r['nombreSubcategoria']))] = ['id' => (int)$r['idSubcategoria'], 'idCat' => (int)$r['idCategoria']];
}

for ($f = 2; $f <= $filas; $f++) {
    $nombre = trim((string)$hoja->getCell($colNombre . $f)->getValue());
    $precio = (float)$hoja->getCell($colPrecio . $f)->getValue();
    $catNombre = trim((string)$hoja->getCell($colCat . $f)->getValue());

    if ($nombre === '' || $precio <= 0 || $catNombre === '') continue;

    $idCat = $cacheCat[strtolower($catNombre)] ?? null;
    if (!$idCat) {
        $errores++;
        $detalle[] = "Fila $f: categoría '$catNombre' no encontrada.";
        continue;
    }

    $descripcion = $colDesc  ? trim((string)$hoja->getCell($colDesc  . $f)->getValue()) : '';
    $enOferta    = 0;
    $descuento   = 0.0;
    if ($colOferta) {
        $ofVal = strtolower(trim((string)$hoja->getCell($colOferta . $f)->getValue()));
        $enOferta = in_array($ofVal, ['si', 'sí', '1', 'yes']) ? 1 : 0;
    }
    if ($enOferta && $colDesc2) {
        $descuento = min(99, max(0, (float)$hoja->getCell($colDesc2 . $f)->getValue()));
    }

    // Insertar producto
    $stmt = mysqli_prepare($con,
        "INSERT INTO producto (nombreProducto, idUsuario, idCategoria, descripcion, precio, ubicacion, idMunicipio, enOferta, descuento)
         VALUES (?, ?, ?, ?, ?, '', NULL, ?, ?)");
    mysqli_stmt_bind_param($stmt, 'siisdid', $nombre, $idUsuario, $idCat, $descripcion, $precio, $enOferta, $descuento);
    if (!mysqli_stmt_execute($stmt)) {
        $errores++;
        $detalle[] = "Fila $f: error al insertar '$nombre'.";
        mysqli_stmt_close($stmt);
        continue;
    }
    $idProducto = (int)mysqli_insert_id($con);
    mysqli_stmt_close($stmt);

    // Vincular subcategoría si se especificó
    if ($colSub) {
        $subNombre = trim((string)$hoja->getCell($colSub . $f)->getValue());
        if ($subNombre !== '') {
            $subData = $cacheSub[strtolower($subNombre)] ?? null;
            if ($subData) {
                $s2 = mysqli_prepare($con, "INSERT IGNORE INTO productosubcategoria (idProducto, idSubcategoria) VALUES (?, ?)");
                mysqli_stmt_bind_param($s2, 'ii', $idProducto, $subData['id']);
                mysqli_stmt_execute($s2);
                mysqli_stmt_close($s2);
            } else {
                $avisos[] = "Fila $f: subcategoría '$subNombre' no encontrada, producto importado sin subcategoría.";
            }
        }
    }

    $insertados++;
}

echo json_encode([
    'insertados'    => $insertados,
    'errores'       => $errores,
    'detalleErrores'=> $detalle,
    'avisos'        => $avisos,
]);
exit;
