<?php
session_start();
include "../../config/conection.php";
require_once "Model.php";
require_once "ImagenHelper.php";
$con = conection();

if (!isset($_SESSION['usuarios'])) { echo json_encode(['ok' => false, 'error' => 'No autorizado']); exit; }
session_write_close(); // liberar el lock de sesión para permitir requests paralelos

$idProducto = (int)($_POST['idProducto'] ?? 0);
$orden      = (int)($_POST['orden']      ?? 0);

if (!$idProducto) { echo json_encode(['ok' => false, 'error' => 'ID no válido']); exit; }

if (!isset($_FILES['imagen']) || $_FILES['imagen']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['ok' => false, 'error' => 'Sin archivo']); exit;
}

$carpeta = "../../uploads/productos/$idProducto/";
if (!file_exists($carpeta)) mkdir($carpeta, 0777, true);

$resultado = ImagenHelper::procesarYGuardar($_FILES['imagen']['tmp_name'], $carpeta);
if (!$resultado['ok']) {
    echo json_encode(['ok' => false, 'error' => $resultado['error']]); exit;
}

$rutaBD = rtrim(SITE_URL, '/') . "/uploads/productos/$idProducto/" . $resultado['nombreArchivo'];

ProductoModel::insertarImagenesMasivo($con, $idProducto, [[
    'ruta'        => $rutaBD,
    'esPrincipal' => $orden === 0 ? 1 : 0,
    'orden'       => $orden,
]]);

echo json_encode(['ok' => true]);
