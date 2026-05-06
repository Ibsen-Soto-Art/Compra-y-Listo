<?php
session_start();
include("../../config/conection.php");
$con = conection();

header('Content-Type: application/json');

if(!isset($_SESSION['usuarios'])){
    echo json_encode(["status"=>"error","message"=>"No autorizado"]);
    exit();
}

$ids = $_POST['ids'] ?? [];

if(empty($ids) || !is_array($ids)){
    echo json_encode(["status"=>"error","message"=>"No se recibieron IDs"]);
    exit();
}

// Solo enteros positivos
$ids = array_filter(array_map('intval', $ids), fn($id) => $id > 0);

if(empty($ids)){
    echo json_encode(["status"=>"error","message"=>"IDs inválidos"]);
    exit();
}

$placeholders = implode(',', $ids);

// 1. Obtener rutas de imágenes antes de borrar
$raiz = realpath(__DIR__ . '/../../') . '/';
$resImg = mysqli_query($con, "SELECT idProducto, rutaImagen FROM imagenesproducto WHERE idProducto IN ($placeholders)");
while($img = mysqli_fetch_assoc($resImg)){
    $ruta = $img['rutaImagen'];
    if(filter_var($ruta, FILTER_VALIDATE_URL)){
        $path = parse_url($ruta, PHP_URL_PATH);
        $path = preg_replace('#^/[^/]+/#', '/', $path);
        $archivoFisico = $raiz . ltrim($path, '/');
    } else {
        $ruta = str_replace('/compraylisto/', '/', $ruta);
        $archivoFisico = $raiz . ltrim($ruta, '/');
    }
    if(file_exists($archivoFisico)) unlink($archivoFisico);
}

// Eliminar carpetas vacías de cada producto
foreach($ids as $id){
    $carpeta = $raiz . "uploads/productos/$id/";
    if(is_dir($carpeta) && count(scandir($carpeta)) === 2) rmdir($carpeta);
}

// 2. Eliminar registros en BD
mysqli_query($con, "DELETE FROM imagenesproducto WHERE idProducto IN ($placeholders)");

// 3. Eliminar productos
$result = mysqli_query($con, "DELETE FROM producto WHERE idProducto IN ($placeholders)");

if($result){
    $eliminados = mysqli_affected_rows($con);
    echo json_encode([
        "status"     => "success",
        "message"    => "$eliminados producto(s) eliminado(s)",
        "eliminados" => $eliminados
    ]);
} else {
    echo json_encode(["status"=>"error","message"=>"Error al eliminar: ".mysqli_error($con)]);
}
?>
