<?php
if (!defined('ROOT_PATH')) define('ROOT_PATH', realpath(__DIR__ . '/../../'));
if (!class_exists('PhpOffice\PhpSpreadsheet\IOFactory')) {
    require ROOT_PATH . '/vendor/autoload.php';
}
if (!function_exists('conection')) require_once ROOT_PATH . '/config/conection.php';

header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) session_start();
$idUsuario = (int)($_SESSION['idUsuario'] ?? 1);

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

// Mapear encabezados fila 1 → letra de columna
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
    echo json_encode(['insertados' => 0, 'errores' => 0, 'detalleErrores' => ['Columna "nombreCategoria" no encontrada. Encabezados detectados: ' . implode(', ', array_keys($headers))]]);
    exit;
}

for ($f = 2; $f <= $filas; $f++) {
    $nombre = trim((string)$hoja->getCell($colNombre . $f)->getValue());
    if ($nombre === '') continue;

    $imagen = $colImagen ? trim((string)$hoja->getCell($colImagen . $f)->getValue()) : '';
    $estado = $colEstado ? trim((string)$hoja->getCell($colEstado . $f)->getValue()) : 'Activo';
    if (!in_array($estado, ['Activo', 'Oculto'])) $estado = 'Activo';

    // idUsuario es requerido por la tabla categoria
    $stmt = mysqli_prepare($con,
        "INSERT INTO categoria (nombreCategoria, imagenCategoria, estadoCategoria, idUsuario)
         VALUES (?, ?, ?, ?)");

    if (!$stmt) {
        echo json_encode(['insertados' => 0, 'errores' => 0, 'detalleErrores' => ['Error preparando consulta: ' . mysqli_error($con)]]);
        exit;
    }

    mysqli_stmt_bind_param($stmt, 'sssi', $nombre, $imagen, $estado, $idUsuario);

    if (mysqli_stmt_execute($stmt) && mysqli_stmt_affected_rows($stmt) > 0) {
        $insertados++;
    } else {
        $err = mysqli_stmt_error($stmt);
        $errores++;
        $detalle[] = "Fila $f: '$nombre'" . ($err ? " — $err" : " (ya existe)") . ".";
    }
    mysqli_stmt_close($stmt);
}

echo json_encode(['insertados' => $insertados, 'errores' => $errores, 'detalleErrores' => $detalle]);
exit;
