<?php
session_start();
include("../../config/conection.php");
require '../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;

$con = conection();

// Detectar si ya existe la tabla municipio
$tblMun = mysqli_fetch_assoc(mysqli_query($con,
    "SELECT COUNT(*) AS c FROM INFORMATION_SCHEMA.TABLES
     WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='municipio'"));
$conMunicipio = (int)$tblMun['c'] > 0;

$spreadsheet = new Spreadsheet();

// ══════════════════════════════════════════════════════════════════
// HOJA 1 — Productos
// ══════════════════════════════════════════════════════════════════
$hoja = $spreadsheet->getActiveSheet();
$hoja->setTitle('Productos');

// Columnas: A=nombre, B=precio, C=categoria, D=subcategoria,
//           E=departamento, F=municipio, G=descripcion, H-L=imagen1-5
$encabezados = [
    'A' => 'nombreProducto',
    'B' => 'precio',
    'C' => 'categoria',
    'D' => 'subcategoria',
    'E' => 'departamento',
    'F' => 'municipio',
    'G' => 'descripcion',
    'H' => 'imagen1',
    'I' => 'imagen2',
    'J' => 'imagen3',
    'K' => 'imagen4',
    'L' => 'imagen5',
];

foreach ($encabezados as $col => $nombre) {
    $hoja->setCellValue($col . '1', $nombre);
}

// Estilo: obligatorios (A-C) azul oscuro
$hoja->getStyle('A1:C1')->applyFromArray([
    'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1F4E79']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
]);

// Estilo: opcionales (D-G) azul medio
$hoja->getStyle('D1:G1')->applyFromArray([
    'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2E75B6']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
]);

// Estilo: imágenes (H-L) verde
$hoja->getStyle('H1:L1')->applyFromArray([
    'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E6B3C']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
]);

$hoja->getRowDimension(1)->setRowHeight(22);

// Anchos de columna
$anchos = ['A'=>30,'B'=>14,'C'=>25,'D'=>28,'E'=>28,'F'=>28,'G'=>40,'H'=>26,'I'=>26,'J'=>26,'K'=>26,'L'=>26];
foreach ($anchos as $col => $w) $hoja->getColumnDimension($col)->setWidth($w);

// Fila de ejemplo
$ejemplo = [
    'A2' => 'Televisor Samsung 55"',
    'B2' => 850000,
    'C2' => 'Electrónica',
    'D2' => 'Televisores',
    'E2' => 'Caquetá',
    'F2' => 'Florencia',
    'G2' => 'Televisor en perfecto estado, con control remoto',
    'H2' => 'televisor_frontal.jpg',
    'I2' => 'televisor_lateral.jpg',
];
foreach ($ejemplo as $cell => $val) $hoja->setCellValue($cell, $val);

$hoja->getStyle('A2:L2')->applyFromArray([
    'font' => ['italic' => true, 'color' => ['rgb' => '999999']],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F9F9F9']],
]);

// Nota en M1
$hoja->setCellValue('M1',
    '* Azul oscuro = obligatorio | Azul = opcional | Verde = imágenes opcionales. ' .
    'En "departamento" escribe el nombre exacto (ver hoja Referencia). "municipio" debe pertenecer al departamento indicado.');
$hoja->getStyle('M1')->getFont()->setItalic(true)->setSize(9);
$hoja->getStyle('M1')->getFont()->getColor()->setRGB('666666');
$hoja->getColumnDimension('M')->setWidth(95);

// ── Validación desplegable: Categorías ───────────────────────────
$hojaCats = $spreadsheet->createSheet();
$hojaCats->setTitle('_Categorias');
$qCat = mysqli_query($con, "SELECT nombreCategoria FROM categoria WHERE estadoCategoria='Activo' ORDER BY nombreCategoria");
$rowIdx = 1;
while ($c = mysqli_fetch_assoc($qCat)) {
    $hojaCats->setCellValue('A' . $rowIdx, $c['nombreCategoria']);
    $rowIdx++;
}
$totalCats = $rowIdx - 1;
if ($totalCats > 0) {
    $valCat = $hoja->getDataValidation('C2:C1000');
    $valCat->setType(DataValidation::TYPE_LIST);
    $valCat->setErrorStyle(DataValidation::STYLE_STOP);
    $valCat->setAllowBlank(false);
    $valCat->setShowDropDown(true);
    $valCat->setShowErrorMessage(true);
    $valCat->setErrorTitle('Categoría inválida');
    $valCat->setError('Selecciona una categoría de la lista desplegable');
    $valCat->setFormula1('_Categorias!$A$1:$A$' . $totalCats);
}

// ── Validación desplegable: Departamentos (si existe la tabla) ───
if ($conMunicipio) {
    $hojaDeptos = $spreadsheet->createSheet();
    $hojaDeptos->setTitle('_Departamentos');
    $qDep = mysqli_query($con, "SELECT nombre FROM departamento ORDER BY nombre ASC");
    $dIdx = 1;
    while ($d = mysqli_fetch_assoc($qDep)) {
        $hojaDeptos->setCellValue('A' . $dIdx, $d['nombre']);
        $dIdx++;
    }
    $totalDeptos = $dIdx - 1;
    if ($totalDeptos > 0) {
        $valDep = $hoja->getDataValidation('E2:E1000');
        $valDep->setType(DataValidation::TYPE_LIST);
        $valDep->setErrorStyle(DataValidation::STYLE_STOP);
        $valDep->setAllowBlank(true);
        $valDep->setShowDropDown(true);
        $valDep->setShowErrorMessage(true);
        $valDep->setErrorTitle('Departamento inválido');
        $valDep->setError('Selecciona un departamento de la lista');
        $valDep->setFormula1('_Departamentos!$A$1:$A$' . $totalDeptos);
    }

    // ── Hoja de referencia Departamentos ↔ Municipios ──────────
    $hojaUbic = $spreadsheet->createSheet();
    $hojaUbic->setTitle('Municipios');
    $hojaUbic->setCellValue('A1', 'Departamento');
    $hojaUbic->setCellValue('B1', 'Municipio');
    $hojaUbic->getStyle('A1:B1')->applyFromArray([
        'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2E8B57']],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
    ]);
    $hojaUbic->getColumnDimension('A')->setWidth(30);
    $hojaUbic->getColumnDimension('B')->setWidth(30);
    $qMun = mysqli_query($con,
        "SELECT d.nombre AS depto, m.nombre AS mun
         FROM municipio m JOIN departamento d ON d.idDepartamento = m.idDepartamento
         ORDER BY d.nombre, m.nombre");
    $mIdx = 2;
    $lastDepto = '';
    while ($m = mysqli_fetch_assoc($qMun)) {
        $hojaUbic->setCellValue('A' . $mIdx, $m['depto']);
        $hojaUbic->setCellValue('B' . $mIdx, $m['mun']);
        if ($m['depto'] !== $lastDepto) {
            $hojaUbic->getStyle('A' . $mIdx)->getFont()->setBold(true);
            $lastDepto = $m['depto'];
        }
        $mIdx++;
    }
}

// ══════════════════════════════════════════════════════════════════
// HOJA — Referencia categorías/subcategorías
// ══════════════════════════════════════════════════════════════════
$hojaRef = $spreadsheet->createSheet();
$hojaRef->setTitle('Referencia');
$hojaRef->setCellValue('A1', 'Categoría');
$hojaRef->setCellValue('B1', 'Subcategoría');
$hojaRef->getStyle('A1:B1')->applyFromArray([
    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1F4E79']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
]);
$hojaRef->getColumnDimension('A')->setWidth(28);
$hojaRef->getColumnDimension('B')->setWidth(28);
$qRef = mysqli_query($con, "
    SELECT c.nombreCategoria, s.nombreSubcategoria
    FROM categoria c
    LEFT JOIN subcategoria s ON s.idCategoria = c.idCategoria AND s.estadoSubcategoria = 'Activo'
    WHERE c.estadoCategoria = 'Activo'
    ORDER BY c.nombreCategoria, s.nombreSubcategoria");
$filaRef = 2;
$lastCat = '';
while ($r = mysqli_fetch_assoc($qRef)) {
    $hojaRef->setCellValue('A' . $filaRef, $r['nombreCategoria']);
    $hojaRef->setCellValue('B' . $filaRef, $r['nombreSubcategoria'] ?? '(sin subcategorías)');
    if ($r['nombreCategoria'] !== $lastCat) {
        $hojaRef->getStyle('A' . $filaRef)->getFont()->setBold(true);
        $lastCat = $r['nombreCategoria'];
    }
    $filaRef++;
}

// ══════════════════════════════════════════════════════════════════
// HOJA — Instrucciones
// ══════════════════════════════════════════════════════════════════
$hojaInstr = $spreadsheet->createSheet();
$hojaInstr->setTitle('Instrucciones');

$instrucciones = [
    ['Campo',           'Obligatorio', 'Descripción'],
    ['nombreProducto',  'Sí',          'Nombre del producto (máx. 255 caracteres)'],
    ['precio',          'Sí',          'Número mayor a 0, sin puntos ni comas. Ej: 850000'],
    ['categoria',       'Sí',          'Debe coincidir exactamente con una categoría activa (usa el desplegable)'],
    ['subcategoria',    'No',          'Debe pertenecer a la categoría indicada. Puede dejarse vacío'],
    ['departamento',    'No',          'Selecciona de la lista desplegable o consulta la hoja "Municipios"'],
    ['municipio',       'No',          'Escribe el nombre exacto del municipio (ver hoja "Municipios")'],
    ['descripcion',     'No',          'Descripción detallada del producto'],
    ['imagen1–imagen5', 'No',          'Nombre exacto del archivo a subir (ej: foto.jpg). imagen1 será la principal'],
    ['', '', ''],
    ['IMPORTANTE:', '', 'Al importar, selecciona también las imágenes desde tu computador para que el sistema las vincule por nombre de archivo.'],
    ['', '', 'Si el municipio no se encuentra en la BD, la ubicación se guardará como texto en el campo "municipio" sin normalizar.'],
];

$hojaInstr->getStyle('A1:C1')->applyFromArray([
    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2E8B57']],
]);

foreach ($instrucciones as $f => $cols) {
    $hojaInstr->setCellValue('A' . ($f + 1), $cols[0]);
    $hojaInstr->setCellValue('B' . ($f + 1), $cols[1]);
    $hojaInstr->setCellValue('C' . ($f + 1), $cols[2]);
}
$hojaInstr->getColumnDimension('A')->setWidth(20);
$hojaInstr->getColumnDimension('B')->setWidth(13);
$hojaInstr->getColumnDimension('C')->setWidth(90);

// Activar hoja Productos
$spreadsheet->setActiveSheetIndex(0);

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="plantillaProductos.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
