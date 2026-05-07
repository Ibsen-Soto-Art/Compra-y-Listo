<?php
// Importador real de subcategorías desde Excel
if (!defined('ROOT_PATH')) define('ROOT_PATH', realpath(__DIR__ . '/../../'));
if (!class_exists('PhpOffice\PhpSpreadsheet\IOFactory')) {
    require ROOT_PATH . '/vendor/autoload.php';
}
if (!function_exists('conection')) require_once ROOT_PATH . '/config/conection.php';

header('Content-Type: application/json; charset=utf-8');

$con = conection();

if (empty($_FILES['archivo']['tmp_name'])) {
    echo json_encode(['insertados' => 0, 'errores' => 0, 'detalleErrores' => ['No se recibió ningún archivo.']]);
    exit;
}

try {
    $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($_FILES['archivo']['tmp_name']);
} catch (\Exception $e) {
    echo json_encode(['insertados' => 0, 'errores' => 0, 'detalleErrores' => ['Archivo Excel inválido: ' . $e->getMessage()]]);
    exit;
}

$hoja       = $spreadsheet->getActiveSheet();
$filas      = $hoja->getHighestRow();
$insertados = 0;
$errores    = 0;
$detalle    = [];

// Mapear encabezados
$headers = [];
$colMax  = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($hoja->getHighestColumn());
for ($c = 1; $c <= $colMax; $c++) {
    $letra = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($c);
    $val   = trim((string)$hoja->getCell($letra . '1')->getValue());
    if ($val !== '') $headers[strtolower($val)] = $letra;
}

$colNombre = $headers['nombresubcategoria'] ?? null;
$colCat    = $headers['nombrecategoria']    ?? null;
$colEstado = $headers['estadosubcategoria'] ?? null;
$colImagen = $headers['imagenurl']          ?? $headers['imagen'] ?? null;

if (!$colNombre || !$colCat) {
    echo json_encode(['insertados' => 0, 'errores' => 0, 'detalleErrores' => ['Las columnas "nombreSubcategoria" y "nombreCategoria" son obligatorias.']]);
    exit;
}

// Caché de categorías nombre→id
$cacheCat = [];
$qc = mysqli_query($con, "SELECT idCategoria, nombreCategoria FROM categoria");
while ($r = mysqli_fetch_assoc($qc)) {
    $cacheCat[strtolower(trim($r['nombreCategoria']))] = (int)$r['idCategoria'];
}

for ($f = 2; $f <= $filas; $f++) {
    $nombre   = trim((string)$hoja->getCell($colNombre . $f)->getValue());
    $catNombre = trim((string)$hoja->getCell($colCat   . $f)->getValue());
    if ($nombre === '' || $catNombre === '') continue;

    $idCat = $cacheCat[strtolower($catNombre)] ?? null;
    if (!$idCat) {
        $errores++;
        $detalle[] = "Fila $f: categoría '$catNombre' no existe.";
        continue;
    }

    $estado = $colEstado ? trim((string)$hoja->getCell($colEstado . $f)->getValue()) : 'Activo';
    if (!in_array($estado, ['Activo', 'Oculto'])) $estado = 'Activo';
    $imagen = $colImagen ? trim((string)$hoja->getCell($colImagen . $f)->getValue()) : '';

    $stmt = mysqli_prepare($con, "INSERT IGNORE INTO subcategoria (nombreSubcategoria, idCategoria, estadoSubcategoria, imagenSubcategoria) VALUES (?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, 'siss', $nombre, $idCat, $estado, $imagen);
    if (mysqli_stmt_execute($stmt) && mysqli_stmt_affected_rows($stmt) > 0) {
        $insertados++;
    } else {
        $errores++;
        $detalle[] = "Fila $f: '$nombre' ya existe o es inválida.";
    }
    mysqli_stmt_close($stmt);
}

echo json_encode(['insertados' => $insertados, 'errores' => $errores, 'detalleErrores' => $detalle]);
exit;
