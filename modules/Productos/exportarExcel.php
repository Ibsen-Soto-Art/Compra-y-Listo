<?php
session_start();
include("../../config/conection.php");
require '../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

$con = conection();

// ── Productos ─────────────────────────────────────────────────────────────────
$sqlProd = "
    SELECT
        p.idProducto,
        p.nombreProducto,
        p.precio,
        p.enOferta,
        p.descuento,
        COALESCE(c.nombreCategoria, 'Sin categoría') AS nombreCategoria,
        p.ubicacion,
        p.descripcion,
        p.fechaProducto,
        COUNT(DISTINCT i.idImagen)      AS totalImagenes,
        COUNT(DISTINCT inv.idItemInventario)      AS stockTotal,
        SUM(inv.estadoItem = 'Disponible') AS stockDisponible,
        GROUP_CONCAT(DISTINCT s.nombreSubcategoria ORDER BY s.nombreSubcategoria SEPARATOR ', ') AS subcategorias
    FROM producto p
    LEFT JOIN categoria c              ON c.idCategoria   = p.idCategoria
    LEFT JOIN imagenesproducto i       ON i.idProducto    = p.idProducto
    LEFT JOIN iteminventario inv       ON inv.idProducto  = p.idProducto
    LEFT JOIN productosubcategoria ps  ON ps.idProducto   = p.idProducto
    LEFT JOIN subcategoria s           ON s.idSubcategoria = ps.idSubcategoria
    GROUP BY p.idProducto
    ORDER BY nombreCategoria ASC, p.nombreProducto ASC";

$resProd   = mysqli_query($con, $sqlProd);
$productos = mysqli_fetch_all($resProd, MYSQLI_ASSOC);
$total     = count($productos);
$totalValor= array_sum(array_column($productos, 'precio'));

// ── Resumen por categoría ─────────────────────────────────────────────────────
$sqlCat = "
    SELECT
        COALESCE(c.nombreCategoria, 'Sin categoría') AS nombreCategoria,
        COUNT(DISTINCT p.idProducto) AS cantidad,
        MIN(p.precio)  AS precioMin,
        MAX(p.precio)  AS precioMax,
        AVG(p.precio)  AS precioPromedio,
        SUM(p.precio)  AS precioTotal
    FROM producto p
    LEFT JOIN categoria c ON c.idCategoria = p.idCategoria
    GROUP BY p.idCategoria
    ORDER BY cantidad DESC";
$porCat = mysqli_fetch_all(mysqli_query($con, $sqlCat), MYSQLI_ASSOC);

// ── Resumen por subcategoría ──────────────────────────────────────────────────
$sqlSub = "
    SELECT
        COALESCE(c.nombreCategoria,'Sin categoría') AS nombreCategoria,
        COALESCE(s.nombreSubcategoria,'Sin subcategoría') AS nombreSubcategoria,
        COUNT(DISTINCT p.idProducto) AS cantidad,
        SUM(p.precio) AS precioTotal
    FROM producto p
    LEFT JOIN productosubcategoria ps  ON ps.idProducto    = p.idProducto
    LEFT JOIN subcategoria s           ON s.idSubcategoria  = ps.idSubcategoria
    LEFT JOIN categoria c              ON c.idCategoria     = COALESCE(s.idCategoria, p.idCategoria)
    GROUP BY c.idCategoria, s.idSubcategoria
    ORDER BY nombreCategoria, nombreSubcategoria";
$porSub = mysqli_fetch_all(mysqli_query($con, $sqlSub), MYSQLI_ASSOC);

// ── Helpers ───────────────────────────────────────────────────────────────────
$AZUL_OSC  = '1e3a5f';
$AZUL_MED  = '2563eb';
$AZUL_FILA = 'eff6ff';
$BLANCO    = 'FFFFFF';
$GRIS_BD   = 'D1D5DB';
$NEGRO     = '111827';

function borde(string $color = 'D1D5DB'): array {
    return ['borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => $color]]]];
}
function bordeExt(string $color): array {
    return ['borders' => ['outline' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => $color]]]];
}
function cop(): string { return '"$"#,##0'; }

// ── Libro ─────────────────────────────────────────────────────────────────────
$spreadsheet = new Spreadsheet();
$spreadsheet->getProperties()
    ->setCreator('Compra y Listo')
    ->setTitle('Reporte de Productos')
    ->setDescription('Generado automáticamente');

// ══════════════════════════════════════════════════════════════════════════════
//  HOJA 1 — PRODUCTOS
// ══════════════════════════════════════════════════════════════════════════════
$h = $spreadsheet->getActiveSheet();
$h->setTitle('Productos');

// Título
$h->mergeCells('A1:L1');
$h->setCellValue('A1', 'COMPRA Y LISTO — Reporte de Productos');
$h->getStyle('A1')->applyFromArray([
    'font'      => ['bold' => true, 'size' => 16, 'color' => ['rgb' => $BLANCO]],
    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $AZUL_OSC]],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
]);
$h->getRowDimension(1)->setRowHeight(36);

// Subtítulo
$h->mergeCells('A2:L2');
$h->setCellValue('A2', 'Exportado: ' . date('d/m/Y H:i') . '   |   Total productos: ' . $total . '   |   Valor total: $' . number_format($totalValor, 0, ',', '.'));
$h->getStyle('A2')->applyFromArray([
    'font'      => ['size' => 10, 'italic' => true, 'color' => ['rgb' => $BLANCO]],
    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $AZUL_MED]],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
]);
$h->getRowDimension(2)->setRowHeight(22);

// Separador
$h->mergeCells('A3:L3');
$h->getStyle('A3')->applyFromArray(['fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'dbeafe']]]);
$h->getRowDimension(3)->setRowHeight(5);

// Encabezados
$cols = ['#', 'Nombre del Producto', 'Categoría', 'Subcategorías', 'Precio', 'En Oferta', 'Descuento', 'Ubicación', 'Stock Total', 'Disponibles', 'Imágenes', 'Fecha Registro'];
foreach ($cols as $ci => $label) {
    $h->setCellValue(chr(65 + $ci) . '4', $label);
}
$h->getStyle('A4:L4')->applyFromArray([
    'font'      => ['bold' => true, 'size' => 11, 'color' => ['rgb' => $BLANCO]],
    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $AZUL_MED]],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
]);
$h->getStyle('A4:L4')->applyFromArray(borde('1d4ed8'));
$h->getRowDimension(4)->setRowHeight(24);

// Datos
$fila = 5;
foreach ($productos as $idx => $prod) {
    $bg = ($idx % 2 === 0) ? $BLANCO : $AZUL_FILA;

    $precioFinal = $prod['enOferta'] ? round($prod['precio'] * (1 - $prod['descuento'] / 100)) : (float)$prod['precio'];

    $h->setCellValue('A' . $fila, $prod['idProducto']);
    $h->setCellValue('B' . $fila, $prod['nombreProducto']);
    $h->setCellValue('C' . $fila, $prod['nombreCategoria']);
    $h->setCellValue('D' . $fila, $prod['subcategorias'] ?: '—');
    $h->setCellValue('E' . $fila, $precioFinal);
    $h->setCellValue('F' . $fila, $prod['enOferta'] ? 'Sí' : 'No');
    $h->setCellValue('G' . $fila, $prod['enOferta'] ? $prod['descuento'] . '%' : '—');
    $h->setCellValue('H' . $fila, $prod['ubicacion'] ?: '—');
    $h->setCellValue('I' . $fila, (int)$prod['stockTotal']);
    $h->setCellValue('J' . $fila, (int)$prod['stockDisponible']);
    $h->setCellValue('K' . $fila, (int)$prod['totalImagenes']);
    $h->setCellValue('L' . $fila, $prod['fechaProducto'] ? date('d/m/Y', strtotime($prod['fechaProducto'])) : '—');

    $h->getStyle("A{$fila}:L{$fila}")->applyFromArray([
        'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $bg]],
        'font'      => ['size' => 10, 'color' => ['rgb' => $NEGRO]],
        'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
    ]);
    $h->getStyle("A{$fila}:L{$fila}")->applyFromArray(borde());
    $h->getStyle("A{$fila}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $h->getStyle("E{$fila}")->getNumberFormat()->setFormatCode(cop());
    $h->getStyle("E{$fila}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
    foreach (['F','G','I','J','K','L'] as $col) {
        $h->getStyle("{$col}{$fila}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    }
    $h->getRowDimension($fila)->setRowHeight(20);
    $fila++;
}

// Fila total
$h->mergeCells("A{$fila}:D{$fila}");
$h->setCellValue("A{$fila}", 'TOTAL');
$h->setCellValue("E{$fila}", $totalValor);
$h->setCellValue("I{$fila}", array_sum(array_column($productos, 'stockTotal')));
$h->setCellValue("J{$fila}", array_sum(array_column($productos, 'stockDisponible')));
$h->setCellValue("K{$fila}", array_sum(array_column($productos, 'totalImagenes')));
$h->getStyle("A{$fila}:L{$fila}")->applyFromArray([
    'font'      => ['bold' => true, 'size' => 11, 'color' => ['rgb' => $BLANCO]],
    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $AZUL_OSC]],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
]);
$h->getStyle("E{$fila}")->getNumberFormat()->setFormatCode(cop());
$h->getStyle("E{$fila}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
$h->getStyle("A{$fila}:L{$fila}")->applyFromArray(borde($AZUL_MED));
$h->getRowDimension($fila)->setRowHeight(22);

$h->getStyle("A1:L{$fila}")->applyFromArray(bordeExt($AZUL_OSC));

// Anchos
foreach ([
    'A'=>8, 'B'=>32, 'C'=>22, 'D'=>30, 'E'=>18,
    'F'=>10, 'G'=>12, 'H'=>18, 'I'=>12, 'J'=>14, 'K'=>11, 'L'=>16
] as $col => $w) {
    $h->getColumnDimension($col)->setWidth($w);
}
$h->freezePane('A5');
$h->setAutoFilter("A4:L4");
$h->getPageSetup()->setPrintArea("A1:L{$fila}");
$h->getPageSetup()->setFitToWidth(1);
$h->getPageSetup()->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE);

// ══════════════════════════════════════════════════════════════════════════════
//  HOJA 2 — POR CATEGORÍA
// ══════════════════════════════════════════════════════════════════════════════
$hCat = $spreadsheet->createSheet();
$hCat->setTitle('Por Categoría');

$hCat->mergeCells('A1:F1');
$hCat->setCellValue('A1', 'Resumen por Categoría');
$hCat->getStyle('A1')->applyFromArray([
    'font'      => ['bold' => true, 'size' => 14, 'color' => ['rgb' => $BLANCO]],
    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $AZUL_OSC]],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
]);
$hCat->getRowDimension(1)->setRowHeight(30);

foreach (['Categoría', 'Cantidad', 'Precio Mínimo', 'Precio Máximo', 'Precio Promedio', 'Valor Total'] as $ci => $label) {
    $hCat->setCellValue(chr(65 + $ci) . '2', $label);
}
$hCat->getStyle('A2:F2')->applyFromArray([
    'font'      => ['bold' => true, 'color' => ['rgb' => $BLANCO]],
    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $AZUL_MED]],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
]);
$hCat->getStyle('A2:F2')->applyFromArray(borde('1d4ed8'));
$hCat->getRowDimension(2)->setRowHeight(20);

$fCat = 3;
foreach ($porCat as $idx => $cat) {
    $bg = ($idx % 2 === 0) ? $BLANCO : $AZUL_FILA;
    $hCat->setCellValue("A{$fCat}", $cat['nombreCategoria']);
    $hCat->setCellValue("B{$fCat}", (int)$cat['cantidad']);
    $hCat->setCellValue("C{$fCat}", (float)$cat['precioMin']);
    $hCat->setCellValue("D{$fCat}", (float)$cat['precioMax']);
    $hCat->setCellValue("E{$fCat}", (float)$cat['precioPromedio']);
    $hCat->setCellValue("F{$fCat}", (float)$cat['precioTotal']);
    $hCat->getStyle("A{$fCat}:F{$fCat}")->applyFromArray([
        'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $bg]],
        'font'      => ['size' => 10],
        'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
    ]);
    $hCat->getStyle("A{$fCat}:F{$fCat}")->applyFromArray(borde());
    $hCat->getStyle("B{$fCat}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    foreach (['C','D','E','F'] as $col) {
        $hCat->getStyle("{$col}{$fCat}")->getNumberFormat()->setFormatCode(cop());
        $hCat->getStyle("{$col}{$fCat}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
    }
    $hCat->getRowDimension($fCat)->setRowHeight(18);
    $fCat++;
}
$hCat->setCellValue("A{$fCat}", 'TOTAL');
$hCat->setCellValue("B{$fCat}", $total);
$hCat->setCellValue("F{$fCat}", $totalValor);
$hCat->getStyle("A{$fCat}:F{$fCat}")->applyFromArray([
    'font'      => ['bold' => true, 'color' => ['rgb' => $BLANCO]],
    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $AZUL_OSC]],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
]);
$hCat->getStyle("F{$fCat}")->getNumberFormat()->setFormatCode(cop());
$hCat->getStyle("F{$fCat}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
$hCat->getStyle("A{$fCat}:F{$fCat}")->applyFromArray(borde($AZUL_MED));
$hCat->getStyle("A1:F{$fCat}")->applyFromArray(bordeExt($AZUL_OSC));
foreach (['A'=>28,'B'=>12,'C'=>18,'D'=>18,'E'=>20,'F'=>20] as $col => $w) {
    $hCat->getColumnDimension($col)->setWidth($w);
}
$hCat->freezePane('A3');

// ══════════════════════════════════════════════════════════════════════════════
//  HOJA 3 — POR SUBCATEGORÍA
// ══════════════════════════════════════════════════════════════════════════════
$hSub = $spreadsheet->createSheet();
$hSub->setTitle('Por Subcategoría');

$hSub->mergeCells('A1:D1');
$hSub->setCellValue('A1', 'Resumen por Subcategoría');
$hSub->getStyle('A1')->applyFromArray([
    'font'      => ['bold' => true, 'size' => 14, 'color' => ['rgb' => $BLANCO]],
    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $AZUL_OSC]],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
]);
$hSub->getRowDimension(1)->setRowHeight(30);

foreach (['Categoría', 'Subcategoría', 'Cantidad', 'Valor Total'] as $ci => $label) {
    $hSub->setCellValue(chr(65 + $ci) . '2', $label);
}
$hSub->getStyle('A2:D2')->applyFromArray([
    'font'      => ['bold' => true, 'color' => ['rgb' => $BLANCO]],
    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $AZUL_MED]],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
]);
$hSub->getStyle('A2:D2')->applyFromArray(borde('1d4ed8'));
$hSub->getRowDimension(2)->setRowHeight(20);

$fSub = 3;
foreach ($porSub as $idx => $sub) {
    $bg = ($idx % 2 === 0) ? $BLANCO : $AZUL_FILA;
    $hSub->setCellValue("A{$fSub}", $sub['nombreCategoria']);
    $hSub->setCellValue("B{$fSub}", $sub['nombreSubcategoria']);
    $hSub->setCellValue("C{$fSub}", (int)$sub['cantidad']);
    $hSub->setCellValue("D{$fSub}", (float)$sub['precioTotal']);
    $hSub->getStyle("A{$fSub}:D{$fSub}")->applyFromArray([
        'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $bg]],
        'font'      => ['size' => 10],
        'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
    ]);
    $hSub->getStyle("A{$fSub}:D{$fSub}")->applyFromArray(borde());
    $hSub->getStyle("C{$fSub}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $hSub->getStyle("D{$fSub}")->getNumberFormat()->setFormatCode(cop());
    $hSub->getStyle("D{$fSub}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
    $hSub->getRowDimension($fSub)->setRowHeight(18);
    $fSub++;
}
$hSub->getStyle("A1:D{$fSub}")->applyFromArray(bordeExt($AZUL_OSC));
foreach (['A'=>26,'B'=>26,'C'=>12,'D'=>20] as $col => $w) {
    $hSub->getColumnDimension($col)->setWidth($w);
}
$hSub->freezePane('A3');

// ── Descargar ─────────────────────────────────────────────────────────────────
$spreadsheet->setActiveSheetIndex(0);
$fecha = date('Y-m-d_His');
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header("Content-Disposition: attachment; filename=\"reporte_productos_{$fecha}.xlsx\"");
header('Cache-Control: max-age=0');
(new Xlsx($spreadsheet))->save('php://output');
exit;
