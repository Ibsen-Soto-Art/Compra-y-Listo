<?php
// Importador real de categorías desde Excel
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

// Mapear encabezados (fila 1) → columna letra
$headers = [];
$colMax  = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($hoja->getHighestColumn());
for ($c = 1; $c <= $colMax; $c++) {
    $letra = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($c);
    $val   = trim((string)$hoja->getCell($letra . '1')->getValue());
    if ($val !== '') $headers[strtolower($val)] = $letra;
}

$colNombre = $headers['nombrecategoria'] ?? null;
$colImagen = $headers['imagencategoria'] ?? null;
$colEstado = $headers['estadocategoria'] ?? null;

if (!$colNombre) {
    echo json_encode(['insertados' => 0, 'errores' => 0, 'detalleErrores' => ['La columna "nombreCategoria" es obligatoria.']]);
    exit;
}

for ($f = 2; $f <= $filas; $f++) {
    $nombre = trim((string)$hoja->getCell($colNombre . $f)->getValue());
    if ($nombre === '') continue;

    $imagen = $colImagen ? trim((string)$hoja->getCell($colImagen . $f)->getValue()) : '';
    $estado = $colEstado ? trim((string)$hoja->getCell($colEstado . $f)->getValue()) : 'Activo';
    if (!in_array($estado, ['Activo', 'Oculto'])) $estado = 'Activo';

    $stmt = mysqli_prepare($con, "INSERT IGNORE INTO categoria (nombreCategoria, imagenCategoria, estadoCategoria) VALUES (?, ?, ?)");
    mysqli_stmt_bind_param($stmt, 'sss', $nombre, $imagen, $estado);
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
