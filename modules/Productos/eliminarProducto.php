<?php
session_start();
include("../../config/conection.php");
$con = conection();

if(!isset($_SESSION['usuarios'])){
    echo "No autorizado";
    exit();
}

if($_SERVER['REQUEST_METHOD'] == 'POST'){

    $id = intval($_POST['id']);

    // Raíz del proyecto en el servidor de archivos
    $raiz = realpath(__DIR__ . '/../../') . '/';

    // 1. Obtener rutas de imágenes antes de borrar
    $resImg = mysqli_query($con, "SELECT rutaImagen FROM imagenesproducto WHERE idProducto = $id");
    while($img = mysqli_fetch_assoc($resImg)){
        $ruta = $img['rutaImagen'];

        // Soporta rutas relativas (/compraylisto/uploads/...) y URLs completas (https://...)
        if(filter_var($ruta, FILTER_VALIDATE_URL)){
            // URL completa → extraer solo el path
            $path = parse_url($ruta, PHP_URL_PATH);
            // Quitar el subfolder si existe (ej: /compraylisto/)
            $path = preg_replace('#^/[^/]+/#', '/', $path); // /compraylisto/uploads/... → /uploads/...
            $archivoFisico = $raiz . ltrim($path, '/');
        } else {
            // Ruta relativa antigua
            $ruta = str_replace('/compraylisto/', '/', $ruta);
            $archivoFisico = $raiz . ltrim($ruta, '/');
        }

        if(file_exists($archivoFisico)) unlink($archivoFisico);
    }

    // Eliminar carpeta si quedó vacía
    $carpeta = $raiz . "uploads/productos/$id/";
    if(is_dir($carpeta) && count(scandir($carpeta)) === 2) rmdir($carpeta);

    // 2. Eliminar registros en BD
    mysqli_query($con, "DELETE FROM imagenesProducto WHERE idProducto = $id");

    // 3. Eliminar producto
    if(mysqli_query($con, "DELETE FROM producto WHERE idProducto = $id")){
        echo "ok";
    } else {
        echo "error";
    }
}
?>
