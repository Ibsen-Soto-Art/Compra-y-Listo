<?php
if (!defined('ROOT_PATH')) define('ROOT_PATH', realpath(__DIR__ . '/../../'));
require ROOT_PATH . '/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;

$spreadsheet = new Spreadsheet();
$hoja = $spreadsheet->getActiveSheet();
$hoja->setTitle('Categorias');

// Encabezados
$hoja->setCellValue('A1', 'nombreCategoria');
$hoja->setCellValue('B1', 'imagenCategoria');
$hoja->setCellValue('C1', 'estadoCategoria');

// Estilo encabezados
$estilo = [
    'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4472C4']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
];
$hoja->getStyle('A1:C1')->applyFromArray($estilo);

// Ancho de columnas
$hoja->getColumnDimension('A')->setWidth(30);
$hoja->getColumnDimension('B')->setWidth(50);
$hoja->getColumnDimension('C')->setWidth(18);

// Fila de ejemplo
$hoja->setCellValue('A2', 'Electrónica');
$hoja->setCellValue('B2', 'https://ejemplo.com/imagen.jpg');
$hoja->setCellValue('C2', 'Activo');

$estEjemplo = [
    'font' => ['italic' => true, 'color' => ['rgb' => '888888']],
];
$hoja->getStyle('A2:C2')->applyFromArray($estEjemplo);

// Validación desplegable para estadoCategoria (columna C, filas 2-500)
$validation = $hoja->getDataValidation('C2:C500');
$validation->setType(DataValidation::TYPE_LIST);
$validation->setErrorStyle(DataValidation::STYLE_STOP);
$validation->setAllowBlank(false);
$validation->setShowInputMessage(true);
$validation->setShowErrorMessage(true);
$validation->setShowDropDown(true);
$validation->setErrorTitle('Valor inválido');
$validation->setError('Solo se permite: Activo o Oculto');
$validation->setPromptTitle('Estado categoría');
$validation->setPrompt('Selecciona Activo u Oculto');
$validation->setFormula1('"Activo,Oculto"');

// Nota instructiva en D1
$hoja->setCellValue('D1', '* estadoCategoria: "Activo" u "Oculto". Si se omite, se usará "Activo".');
$hoja->getStyle('D1')->getFont()->setItalic(true)->setSize(10);
$hoja->getStyle('D1')->getFont()->getColor()->setRGB('888888');
$hoja->getColumnDimension('D')->setWidth(55);

// Descargar
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="plantillaCategorias.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
