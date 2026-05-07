<?php
if (!defined('ROOT_PATH')) define('ROOT_PATH', realpath(__DIR__ . '/../../'));
if (!class_exists('PhpOffice\PhpSpreadsheet\IOFactory')) {
    require ROOT_PATH . '/vendor/autoload.php';
}
if (!function_exists('conection')) require_once ROOT_PATH . '/config/conection.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

$con = conection();

$sql = "SELECT
            p.idProducto,
            p.nombreProducto,
            p.precio,
            p.descripcion,
            p.enOferta,
            p.descuento,
            COALESCE(c.nombreCategoria, '') AS nombreCategoria,
            GROUP_CONCAT(DISTINCT s.nombreSubcategoria ORDER BY s.nombreSubcategoria SEPARATOR ', ') AS subcategorias,
            SUM(CASE WHEN inv.estadoItem = 'Disponible' THEN 1 ELSE 0 END) AS disponibles,
            COUNT(inv.idItem) AS totalStock,
            CASE
                WHEN m.idMunicipio IS NOT NULL THEN CONCAT(m.nombre, ', ', d.nombre)
                ELSE COALESCE(p.ubicacion, '')
            END AS ubicacion,
            u.nombreUsuario
        FROM producto p
        LEFT JOIN categoria c              ON c.idCategoria    = p.idCategoria
        LEFT JOIN productosubcategoria ps  ON ps.idProducto    = p.idProducto
        LEFT JOIN subcategoria s           ON s.idSubcategoria = ps.idSubcategoria
        LEFT JOIN iteminventario inv       ON inv.idProducto   = p.idProducto
        LEFT JOIN municipio m              ON m.idMunicipio    = p.idMunicipio
        LEFT JOIN departamento d           ON d.idDepartamento = m.idDepartamento
        LEFT JOIN usuarios u               ON u.idUsuario      = p.idUsuario
        GROUP BY p.idProducto
        ORDER BY p.idProducto DESC";

$res = mysqli_query($con, $sql);

$spreadsheet = new Spreadsheet();
$hoja = $spreadsheet->getActiveSheet();
$hoja->setTitle('Productos');

// ── Encabezados ──────────────────────────────────────────────
$cols = ['ID', 'Nombre', 'Precio', 'Descripción', 'Categoría', 'Subcategorías',
         'En Oferta', 'Descuento %', 'Disponibles', 'Stock Total', 'Ubicación', 'Creado por'];

foreach ($cols as $i => $titulo) {
    $letra = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i + 1);
    $hoja->setCellValue($letra . '1', $titulo);
}

$estiloHeader = [
    'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2E8B57']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
    'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '1a6640']]],
];
$ultimaColLetra = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($cols));
$hoja->getStyle('A1:' . $ultimaColLetra . '1')->applyFromArray($estiloHeader);
$hoja->getRowDimension(1)->setRowHeight(22);

// ── Datos ─────────────────────────────────────────────────────
$fila = 2;
while ($p = mysqli_fetch_assoc($res)) {
    $hoja->setCellValue('A' . $fila, (int)$p['idProducto']);
    $hoja->setCellValue('B' . $fila, $p['nombreProducto']);
    $hoja->setCellValue('C' . $fila, (float)$p['precio']);
    $hoja->setCellValue('D' . $fila, $p['descripcion'] ?? '');
    $hoja->setCellValue('E' . $fila, $p['nombreCategoria']);
    $hoja->setCellValue('F' . $fila, $p['subcategorias'] ?? '');
    $hoja->setCellValue('G' . $fila, $p['enOferta'] ? 'Sí' : 'No');
    $hoja->setCellValue('H' . $fila, (float)$p['descuento']);
    $hoja->setCellValue('I' . $fila, (int)$p['disponibles']);
    $hoja->setCellValue('J' . $fila, (int)$p['totalStock']);
    $hoja->setCellValue('K' . $fila, $p['ubicacion']);
    $hoja->setCellValue('L' . $fila, $p['nombreUsuario'] ?? '');

    // Formato precio
    $hoja->getStyle('C' . $fila)->getNumberFormat()->setFormatCode('#,##0');
    $hoja->getStyle('H' . $fila)->getNumberFormat()->setFormatCode('0.0"%"');

    // Alternar color de fila
    if ($fila % 2 === 0) {
        $hoja->getStyle('A' . $fila . ':L' . $fila)->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'f0fdf4']],
        ]);
    }

    $fila++;
}

// ── Anchos de columna ────────────────────────────────────────
$anchos = [8, 35, 14, 45, 22, 30, 10, 14, 13, 13, 28, 18];
foreach ($anchos as $i => $ancho) {
    $letra = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i + 1);
    $hoja->getColumnDimension($letra)->setWidth($ancho);
}

// ── Borde exterior del rango de datos ────────────────────────
if ($fila > 2) {
    $hoja->getStyle('A1:L' . ($fila - 1))->applyFromArray([
        'borders' => ['outline' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => '2E8B57']]],
    ]);
}

// ── Fila de totales ──────────────────────────────────────────
if ($fila > 2) {
    $ultimaFila = $fila - 1;
    $hoja->setCellValue('A' . $fila, 'TOTAL');
    $hoja->setCellValue('I' . $fila, "=SUM(I2:I$ultimaFila)");
    $hoja->setCellValue('J' . $fila, "=SUM(J2:J$ultimaFila)");
    $hoja->getStyle('A' . $fila . ':L' . $fila)->applyFromArray([
        'font' => ['bold' => true],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'd1fae5']],
    ]);
}

// ── Autofilter ───────────────────────────────────────────────
$hoja->setAutoFilter('A1:' . $ultimaColLetra . '1');

// ── Freeze primera fila ──────────────────────────────────────
$hoja->freezePane('A2');

$fecha = date('Y-m-d');
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header("Content-Disposition: attachment; filename=\"productos_$fecha.xlsx\"");
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
