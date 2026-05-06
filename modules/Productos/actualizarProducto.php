<?php
session_start();
include("../../config/conection.php");
$con = conection();

function guardarImagenOptimizada($tmp, $destino){
    move_uploaded_file($tmp, $destino);
}

if(!isset($_SESSION['usuarios'])){
    echo "No autorizado";
    exit();
}

$idProducto = intval($_POST['idProducto'] ?? 0);
if(!$idProducto){ echo "ID no válido"; exit(); }

$nombre      = trim($_POST['nombre'] ?? '');
$precio      = $_POST['precio'] ?? 0;
$idMunicipioPost = $_POST['idMunicipio'] ?? '';
$descripcion = trim($_POST['descripcion'] ?? '');
$idCategoria = intval($_POST['idCategoria'] ?? 0);
$subcats     = $_POST['subcategorias'] ?? [];
$subcats     = array_filter(array_map('intval', $subcats), fn($v) => $v > 0);
$enOferta    = isset($_POST['enOferta']) ? 1 : 0;
$descuento   = min(99, max(0, floatval($_POST['descuento'] ?? 0)));
if(!$enOferta) $descuento = 0;

if(empty($nombre) || empty($precio) || !$idCategoria){ echo "Datos incompletos"; exit(); }

// Resolver idMunicipio: usar el nuevo si vino, conservar el existente si no
if($idMunicipioPost !== '' && intval($idMunicipioPost) > 0){
    $idMunicipio = intval($idMunicipioPost);
    $rUbic = mysqli_fetch_assoc(mysqli_query($con,
        "SELECT CONCAT(m.nombre, ', ', d.nombre) AS ubic
         FROM municipio m JOIN departamento d ON d.idDepartamento = m.idDepartamento
         WHERE m.idMunicipio = $idMunicipio"));
    $ubicacion = $rUbic['ubic'] ?? "";
    $idMunNullable = $idMunicipio;
} else {
    // Conservar valor existente en la base de datos
    $rExist = mysqli_fetch_assoc(mysqli_query($con,
        "SELECT idMunicipio, ubicacion FROM producto WHERE idProducto=$idProducto"));
    $idMunNullable = $rExist['idMunicipio'] ?: null;
    $ubicacion     = $rExist['ubicacion'] ?? "";
}

// Actualizar producto
$sql  = "UPDATE producto SET nombreProducto=?, precio=?, ubicacion=?, idMunicipio=?, descripcion=?, idCategoria=?, enOferta=?, descuento=? WHERE idProducto=?";
$stmt = mysqli_prepare($con, $sql);
mysqli_stmt_bind_param($stmt, "sdsisiidi", $nombre, $precio, $ubicacion, $idMunNullable, $descripcion, $idCategoria, $enOferta, $descuento, $idProducto);

if(!mysqli_stmt_execute($stmt)){ echo "Error al actualizar producto"; exit(); }

// Actualizar subcategorías: borrar y reinsertar todas
mysqli_query($con, "DELETE FROM productosubcategoria WHERE idProducto=$idProducto");
if(!empty($subcats)){
    $stmtSub = mysqli_prepare($con, "INSERT INTO productosubcategoria (idProducto, idSubcategoria) VALUES (?, ?)");
    foreach($subcats as $idSub){
        mysqli_stmt_bind_param($stmtSub, "ii", $idProducto, $idSub);
        mysqli_stmt_execute($stmtSub);
    }
}

// Carpeta
$carpeta = "../../uploads/productos/$idProducto/";
if(!file_exists($carpeta)) mkdir($carpeta, 0777, true);

$ordenes = $_POST['orden'] ?? [];

// Nuevas imágenes
if(isset($_FILES['imagenes'])){
    $stmtImg = mysqli_prepare($con,
        "INSERT INTO imagenesproducto (idProducto, rutaImagen, esPrincipal, orden) VALUES (?, ?, ?, ?)");
    foreach($_FILES['imagenes']['tmp_name'] as $key => $tmp){
        if($_FILES['imagenes']['error'][$key] === 0){
            $orden       = isset($ordenes[$key]) ? intval($ordenes[$key]) : $key;
            $esPrincipal = ($orden == 0) ? 1 : 0;
            $nombreArchivo = uniqid() . ".jpg";
            $destino = $carpeta . $nombreArchivo;
            guardarImagenOptimizada($tmp, $destino);
            $rutaBD = rtrim(SITE_URL, '/') . "/uploads/productos/$idProducto/$nombreArchivo";
            mysqli_stmt_bind_param($stmtImg, "isii", $idProducto, $rutaBD, $esPrincipal, $orden);
            mysqli_stmt_execute($stmtImg);
        }
    }
}

// Reordenar existentes
if(isset($_POST['ordenExistentes'])){
    foreach($_POST['ordenExistentes'] as $idImagen => $orden){
        $orden = intval($orden);
        $esPrincipal = ($orden == 0) ? 1 : 0;
        $sqlUp = "UPDATE imagenesproducto SET orden=?, esPrincipal=? WHERE idImagen=?";
        $stmtUp = mysqli_prepare($con, $sqlUp);
        mysqli_stmt_bind_param($stmtUp, "iii", $orden, $esPrincipal, $idImagen);
        mysqli_stmt_execute($stmtUp);
    }
}

echo "ok";
