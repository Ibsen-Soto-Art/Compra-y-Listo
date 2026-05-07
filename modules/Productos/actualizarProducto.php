<?php
session_start();
include "../../config/conection.php";
require_once "Model.php";
require_once "ImagenHelper.php";
$con = conection();

if (!isset($_SESSION['usuarios'])) { echo "No autorizado"; exit; }

$idProducto      = (int)($_POST['idProducto']  ?? 0);
if (!$idProducto) { echo "ID no valido"; exit; }

$nombre          = trim($_POST['nombre']       ?? '');
$precio          = (float)($_POST['precio']    ?? 0);
$idMunicipioPost = $_POST['idMunicipio']       ?? '';
$descripcion     = trim($_POST['descripcion']  ?? '');
$idCategoria     = (int)($_POST['idCategoria'] ?? 0);
$subcats         = array_values(array_filter(array_map('intval', $_POST['subcategorias'] ?? []), fn($v) => $v > 0));
$enOferta        = isset($_POST['enOferta']) ? 1 : 0;
$descuento       = $enOferta ? min(99, max(0, (float)($_POST['descuento'] ?? 0))) : 0;

if (!$nombre || !$precio || !$idCategoria) { echo "Datos incompletos"; exit; }

// Resolver ubicacion: usar la nueva si viene, conservar la existente si no
if ($idMunicipioPost !== '' && (int)$idMunicipioPost > 0) {
    $idMunicipio = (int)$idMunicipioPost;
    $ubicacion   = ProductoModel::getUbicacion($con, $idMunicipio);
} else {
    $actual      = ProductoModel::getUbicacionActual($con, $idProducto);
    $idMunicipio = $actual['idMunicipio'] ?: null;
    $ubicacion   = $actual['ubicacion'] ?? '';
}

if (!ProductoModel::actualizar($con, $idProducto, [
    'nombre'      => $nombre,
    'precio'      => $precio,
    'ubicacion'   => $ubicacion,
    'idMunicipio' => $idMunicipio,
    'descripcion' => $descripcion,
    'idCategoria' => $idCategoria,
    'enOferta'    => $enOferta,
    'descuento'   => $descuento,
])) { echo "Error al actualizar producto"; exit; }

// Actualizar subcategorias: borrar y reinsertar
ProductoModel::eliminarSubcategorias($con, $idProducto);
ProductoModel::insertarSubcategorias($con, $idProducto, $subcats);

// Guardar nuevas imagenes
$carpeta = "../../uploads/productos/$idProducto/";
if (!file_exists($carpeta)) mkdir($carpeta, 0777, true);

$ordenes      = $_POST['orden'] ?? [];
$imagenesRows = [];

if (isset($_FILES['imagenes'])) {
    foreach ($_FILES['imagenes']['tmp_name'] as $key => $tmp) {
        if ($_FILES['imagenes']['error'][$key] !== 0) continue;

        $resultado = ImagenHelper::procesarYGuardar($tmp, $carpeta);
        if (!$resultado['ok']) {
            error_log("actualizarProducto imagen[$key]: " . $resultado['error']);
            continue;
        }

        $orden       = (int)($ordenes[$key] ?? $key);
        $rutaBD      = rtrim(SITE_URL, '/') . "/uploads/productos/$idProducto/" . $resultado['nombreArchivo'];
        $imagenesRows[] = [
            'ruta'        => $rutaBD,
            'esPrincipal' => $orden === 0 ? 1 : 0,
            'orden'       => $orden,
        ];
    }
}

if (!empty($imagenesRows)) {
    ProductoModel::insertarImagenesMasivo($con, $idProducto, $imagenesRows);
}

// Reordenar imagenes existentes
foreach ($_POST['ordenExistentes'] ?? [] as $idImagen => $orden) {
    $orden       = (int)$orden;
    $esPrincipal = $orden === 0 ? 1 : 0;
    ProductoModel::actualizarOrdenImagen($con, (int)$idImagen, $orden, $esPrincipal);
}

echo "ok";
