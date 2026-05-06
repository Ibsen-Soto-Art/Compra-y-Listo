<?php
include("../../config/conection.php");
$con = conection();

$id = intval($_POST['id'] ?? 0);
if(!$id){ echo "error"; exit(); }

$sql = "SELECT rutaImagen FROM imagenesproducto WHERE idImagen = $id";
$res = mysqli_query($con, $sql);
$img = mysqli_fetch_assoc($res);

if($img){
    $raiz = realpath(__DIR__ . '/../../') . '/';
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

mysqli_query($con, "DELETE FROM imagenesproducto WHERE idImagen = $id");
echo "ok";
?>
