<?php
if (!defined('ROOT_PATH')) define('ROOT_PATH', realpath(__DIR__ . '/../../'));
require ROOT_PATH . '/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;

if (!function_exists('conection')) require_once ROOT_PATH . '/config/conection.php';
$con = conection();

$spreadsheet = new Spreadsheet();
$hoja = $spreadsheet->getActiveSheet();
$hoja->setTitle('Productos');

// ── Encabezados ──────────────────────────────────────────────
$hoja->setCellValue('A1', 'nombreProducto');
$hoja->setCellValue('B1', 'precio');
$hoja->setCellValue('C1', 'descripcion');
$hoja->setCellValue('D1', 'nombreCategoria');
$hoja->setCellValue('E1', 'nombreSubcategoria');
$hoja->setCellValue('F1', 'enOferta');
$hoja->setCellValue('G1', 'descuento');

$estilo = [
    'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2E8B57']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
];
$hoja->getStyle('A1:G1')->applyFromArray($estilo);

// ── Anchos ───────────────────────────────────────────────────
$hoja->getColumnDimension('A')->setWidth(35);
$hoja->getColumnDimension('B')->setWidth(15);
$hoja->getColumnDimension('C')->setWidth(50);
$hoja->getColumnDimension('D')->setWidth(28);
$hoja->getColumnDimension('E')->setWidth(28);
$hoja->getColumnDimension('F')->setWidth(12);
$hoja->getColumnDimension('G')->setWidth(14);

// ── Fila de ejemplo ──────────────────────────────────────────
$hoja->setCellValue('A2', 'iPhone 14 Pro');
$hoja->setCellValue('B2', 2500000);
$hoja->setCellValue('C2', 'Smartphone Apple en excelente estado');
$hoja->setCellValue('D2', 'Electrónica');
$hoja->setCellValue('E2', 'Smartphones');
$hoja->setCellValue('F2', 'No');
$hoja->setCellValue('G2', 0);
$hoja->getStyle('A2:G2')->applyFromArray([
    'font' => ['italic' => true, 'color' => ['rgb' => '888888']],
]);

// ── Hoja oculta: Categorías disponibles ─────────────────────
$hojaCats = $spreadsheet->createSheet();
$hojaCats->setTitle('_Categorias');
$qCat = mysqli_query($con, "SELECT nombreCategoria FROM categoria WHERE estadoCategoria='Activo' ORDER BY nombreCategoria");
$ri = 1;
while ($c = mysqli_fetch_assoc($qCat)) {
    $hojaCats->setCellValue('A' . $ri, $c['nombreCategoria']);
    $ri++;
}
$totalCats = $ri - 1;

// ── Hoja oculta: Subcategorías disponibles ──────────────────
$hojaSubs = $spreadsheet->createSheet();
$hojaSubs->setTitle('_Subcategorias');
$qSub = mysqli_query($con, "SELECT nombreSubcategoria FROM subcategoria WHERE estadoSubcategoria='Activo' ORDER BY nombreSubcategoria");
$ri = 1;
while ($s = mysqli_fetch_assoc($qSub)) {
    $hojaSubs->setCellValue('A' . $ri, $s['nombreSubcategoria']);
    $ri++;
}
$totalSubs = $ri - 1;

// ── Validaciones desplegables ────────────────────────────────
if ($totalCats > 0) {
    $valCat = $hoja->getDataValidation('D2:D500');
    $valCat->setType(DataValidation::TYPE_LIST);
    $valCat->setErrorStyle(DataValidation::STYLE_STOP);
    $valCat->setAllowBlank(false);
    $valCat->setShowDropDown(true);
    $valCat->setShowErrorMessage(true);
    $valCat->setErrorTitle('Categoría inválida');
    $valCat->setError('Selecciona una categoría de la lista');
    $valCat->setFormula1('_Categorias!$A$1:$A$' . $totalCats);
}

if ($totalSubs > 0) {
    $valSub = $hoja->getDataValidation('E2:E500');
    $valSub->setType(DataValidation::TYPE_LIST);
    $valSub->setErrorStyle(DataValidation::STYLE_STOP);
    $valSub->setAllowBlank(true);
    $valSub->setShowDropDown(true);
    $valSub->setFormula1('_Subcategorias!$A$1:$A$' . $totalSubs);
}

$valOferta = $hoja->getDataValidation('F2:F500');
$valOferta->setType(DataValidation::TYPE_LIST);
$valOferta->setErrorStyle(DataValidation::STYLE_STOP);
$valOferta->setAllowBlank(false);
$valOferta->setShowDropDown(true);
$valOferta->setFormula1('"Si,No"');

// ── Nota instructiva ─────────────────────────────────────────
$hoja->setCellValue('H1', '* nombreCategoria y nombreSubcategoria deben coincidir exactamente con los valores del sistema. descuento: 0-99 (solo si enOferta=Si).');
$hoja->getStyle('H1')->getFont()->setItalic(true)->setSize(10)->getColor()->setRGB('888888');
$hoja->getColumnDimension('H')->setWidth(80);

$spreadsheet->setActiveSheetIndex(0);

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="plantillaProductos.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
