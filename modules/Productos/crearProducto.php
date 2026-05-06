<?php
session_start();
include "../../config/conection.php";
require_once "Model.php";
$con = conection();

if (!isset($_SESSION['usuarios'])) { echo "No autorizado"; exit; }

$idUsuario   = (int)$_SESSION['idUsuario'];
$nombre      = trim($_POST['nombre']      ?? '');
$precio      = (float)($_POST['precio']   ?? 0);
$idMunicipio = (int)($_POST['idMunicipio'] ?? 0);
$descripcion = trim($_POST['descripcion'] ?? '');
$idCategoria = (int)($_POST['idCategoria'] ?? 0);
$subcats     = array_values(array_filter(array_map('intval', $_POST['subcategorias'] ?? []), fn($v) => $v > 0));
$enOferta    = isset($_POST['enOferta']) ? 1 : 0;
$descuento   = $enOferta ? min(99, max(0, (float)($_POST['descuento'] ?? 0))) : 0;

if (!$nombre || !$precio || !$idCategoria) { echo "Datos incompletos"; exit; }

$ubicacion = $idMunicipio ? ProductoModel::getUbicacion($con, $idMunicipio) : '';

$idProducto = ProductoModel::insertar($con, [
    'nombre'      => $nombre,
    'idUsuario'   => $idUsuario,
    'idCategoria' => $idCategoria,
    'descripcion' => $descripcion,
    'precio'      => $precio,
    'ubicacion'   => $ubicacion,
    'idMunicipio' => $idMunicipio ?: null,
    'enOferta'    => $enOferta,
    'descuento'   => $descuento,
]);

if (!$idProducto) { echo "Error al guardar producto"; exit; }

ProductoModel::insertarSubcategorias($con, $idProducto, $subcats);

// Guardar imagenes
$carpeta = "../../uploads/productos/$idProducto/";
if (!file_exists($carpeta)) mkdir($carpeta, 0777, true);

$ordenes = $_POST['orden'] ?? [];
if (isset($_FILES['imagenes'])) {
    foreach ($_FILES['imagenes']['tmp_name'] as $key => $tmp) {
        if ($_FILES['imagenes']['error'][$key] === 0) {
            $orden         = (int)($ordenes[$key] ?? $key);
            $esPrincipal   = $orden === 0 ? 1 : 0;
            $nombreArchivo = uniqid() . ".jpg";
            move_uploaded_file($tmp, $carpeta . $nombreArchivo);
            $rutaBD = rtrim(SITE_URL, '/') . "/uploads/productos/$idProducto/$nombreArchivo";
            ProductoModel::insertarImagen($con, $idProducto, $rutaBD, $esPrincipal, $orden);
        }
    }
}

// Auto-crear unidades de inventario si se indico cantidad
$cantidad = (int)($_POST['cantidad'] ?? 0);
if ($cantidad > 0) {
    $rCat = mysqli_fetch_assoc(mysqli_query($con,
        "SELECT nombreCategoria FROM categoria WHERE idCategoria = $idCategoria"));
    $categoria = $rCat['nombreCategoria'] ?? '';

    $qSub = mysqli_query($con,
        "SELECT s.nombreSubcategoria FROM productosubcategoria ps
         INNER JOIN subcategoria s ON s.idSubcategoria = ps.idSubcategoria
         WHERE ps.idProducto = $idProducto ORDER BY s.nombreSubcategoria");
    $subcatsInv = [];
    while ($r = mysqli_fetch_assoc($qSub)) $subcatsInv[] = $r['nombreSubcategoria'];

    $prefix = '';
    if ($categoria) $prefix .= strtoupper(mb_substr($categoria, 0, 1));
    foreach ($subcatsInv as $sub) $prefix .= strtoupper(mb_substr($sub, 0, 1));
    $prefix .= '-';

    $stmt = mysqli_prepare($con,
        "INSERT INTO iteminventario (idProducto, numeroSerie, estadoItem) VALUES (?, ?, 'Disponible')");
    for ($i = 1; $i <= $cantidad; $i++) {
        $serie = $prefix . str_pad($i, 3, '0', STR_PAD_LEFT);
        mysqli_stmt_bind_param($stmt, "is", $idProducto, $serie);
        mysqli_stmt_execute($stmt);
    }
}

echo "ok";
