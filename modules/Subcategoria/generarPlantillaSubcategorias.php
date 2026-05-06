<?php
require '../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;

include("../../config/conection.php");
$con = conection();

$spreadsheet = new Spreadsheet();
$hoja = $spreadsheet->getActiveSheet();
$hoja->setTitle('Subcategorias');

// Encabezados
$hoja->setCellValue('A1', 'nombreSubcategoria');
$hoja->setCellValue('B1', 'nombreCategoria');
$hoja->setCellValue('C1', 'estadoSubcategoria');
$hoja->setCellValue('D1', 'imagenUrl');

$estilo = [
    'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '6366f1']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
];
$hoja->getStyle('A1:D1')->applyFromArray($estilo);

$hoja->getColumnDimension('A')->setWidth(30);
$hoja->getColumnDimension('B')->setWidth(30);
$hoja->getColumnDimension('C')->setWidth(20);
$hoja->getColumnDimension('D')->setWidth(50);

// Fila de ejemplo
$hoja->setCellValue('A2', 'Smartphones');
$hoja->setCellValue('B2', 'Electrónica');
$hoja->setCellValue('C2', 'Activo');
$hoja->setCellValue('D2', 'https://ejemplo.com/imagen.jpg');

$hoja->getStyle('A2:D2')->applyFromArray([
    'font' => ['italic' => true, 'color' => ['rgb' => '888888']],
]);

// Validación desplegable de estado
$valEstado = $hoja->getDataValidation('C2:C500');
$valEstado->setType(DataValidation::TYPE_LIST);
$valEstado->setErrorStyle(DataValidation::STYLE_STOP);
$valEstado->setAllowBlank(false);
$valEstado->setShowDropDown(true);
$valEstado->setShowErrorMessage(true);
$valEstado->setErrorTitle('Valor inválido');
$valEstado->setError('Solo se permite: Activo o Oculto');
$valEstado->setFormula1('"Activo,Oculto"');

// Hoja oculta con las categorías disponibles para validación desplegable en columna B
$hojaCats = $spreadsheet->createSheet();
$hojaCats->setTitle('_Categorias');
$qCat = mysqli_query($con, "SELECT nombreCategoria FROM categoria WHERE estadoCategoria='Activo' ORDER BY nombreCategoria");
$rowIdx = 1;
while($c = mysqli_fetch_assoc($qCat)){
    $hojaCats->setCellValue('A'.$rowIdx, $c['nombreCategoria']);
    $rowIdx++;
}
$totalCats = $rowIdx - 1;

if($totalCats > 0){
    $valCat = $hoja->getDataValidation('B2:B500');
    $valCat->setType(DataValidation::TYPE_LIST);
    $valCat->setErrorStyle(DataValidation::STYLE_STOP);
    $valCat->setAllowBlank(false);
    $valCat->setShowDropDown(true);
    $valCat->setShowErrorMessage(true);
    $valCat->setErrorTitle('Categoría inválida');
    $valCat->setError('Selecciona una categoría de la lista');
    $valCat->setFormula1('_Categorias!$A$1:$A$'.$totalCats);
}

// Nota en E1
$hoja->setCellValue('E1', '* nombreCategoria debe coincidir exactamente con una categoría existente. imagenUrl es opcional.');
$hoja->getStyle('E1')->getFont()->setItalic(true)->setSize(10);
$hoja->getStyle('E1')->getFont()->getColor()->setRGB('888888');
$hoja->getColumnDimension('E')->setWidth(70);

$spreadsheet->setActiveSheetIndex(0);

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="plantillaSubcategorias.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
