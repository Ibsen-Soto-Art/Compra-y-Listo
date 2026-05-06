<?php
session_start();
include "../../config/conection.php";
require_once "Model.php";
$con = conection();

if (!isset($_SESSION['usuarios'])) { echo "No autorizado"; exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { exit; }

$id   = (int)($_POST['id'] ?? 0);
$raiz = realpath(__DIR__ . '/../../') . '/';

foreach (ProductoModel::getImagenes($con, $id) as $img) {
    $archivo = ProductoModel::rutaFisica($img['rutaImagen'], $raiz);
    if (file_exists($archivo)) unlink($archivo);
}

$carpeta = $raiz . "uploads/productos/$id/";
if (is_dir($carpeta) && count(scandir($carpeta)) === 2) rmdir($carpeta);

ProductoModel::eliminarImagenesPorProducto($con, [$id]);

echo ProductoModel::eliminar($con, $id) ? "ok" : "error";
