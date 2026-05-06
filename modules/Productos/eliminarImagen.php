<?php
include "../../config/conection.php";
require_once "Model.php";
$con = conection();

$id = (int)($_POST['id'] ?? 0);
if (!$id) { echo "error"; exit; }

$img = ProductoModel::getImagenPorId($con, $id);
if ($img) {
    $raiz          = realpath(__DIR__ . '/../../') . '/';
    $archivoFisico = ProductoModel::rutaFisica($img['rutaImagen'], $raiz);
    if (file_exists($archivoFisico)) unlink($archivoFisico);
}

ProductoModel::eliminarImagen($con, $id);
echo "ok";
