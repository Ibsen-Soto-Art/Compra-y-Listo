<?php
ob_start();
if (!defined('ROOT_PATH')) define('ROOT_PATH', dirname(__DIR__));
include(ROOT_PATH . "/config/conection.php");
$con = conection();
ob_end_clean();

ini_set('display_errors', 0);
error_reporting(0);
header('Content-Type: application/json; charset=utf-8');

if(!isset($_GET['id']) || empty($_GET['id'])){
    echo json_encode(["error" => "ID no recibido"]);
    exit();
}

$id = intval($_GET['id']);

$sqlProducto = "SELECT
        p.idProducto,
        p.nombreProducto,
        p.precio,
        p.descripcion,
        p.enOferta,
        p.descuento,
        COALESCE(c.idCategoria, pc.idCategoria, 0) AS idCategoria,
        COALESCE(c.nombreCategoria, pc.nombreCategoria, 'Sin categoría') AS nombreCategoria,
        GROUP_CONCAT(DISTINCT CONCAT(s.idSubcategoria, '|', s.nombreSubcategoria) ORDER BY s.nombreSubcategoria SEPARATOR ';;') AS subcategorias_raw,
        (SELECT COUNT(*) FROM iteminventario WHERE idProducto=p.idProducto AND estadoItem='Disponible') AS disponibles,
        (SELECT COUNT(*) FROM iteminventario WHERE idProducto=p.idProducto) AS totalStock,
        COALESCE(p.ubicacion, '') AS ubicacion
    FROM producto p
    LEFT JOIN productosubcategoria ps ON ps.idProducto = p.idProducto
    LEFT JOIN subcategoria s          ON s.idSubcategoria = ps.idSubcategoria
    LEFT JOIN categoria c             ON c.idCategoria = s.idCategoria
    LEFT JOIN categoria pc            ON pc.idCategoria = p.idCategoria
    WHERE p.idProducto = $id
    GROUP BY p.idProducto";

// Si ya existe la tabla municipio, usar JOIN para mostrar nombre correcto
$tblMun = mysqli_fetch_assoc(mysqli_query($con,
    "SELECT COUNT(*) AS c FROM INFORMATION_SCHEMA.TABLES
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'municipio'"));
if((int)$tblMun['c'] > 0){
    $sqlProducto = str_replace(
        "COALESCE(p.ubicacion, '') AS ubicacion\n    FROM producto p",
        "CASE WHEN m.idMunicipio IS NOT NULL THEN CONCAT(m.nombre, ', ', d.nombre)
              ELSE COALESCE(p.ubicacion, '') END AS ubicacion
    FROM producto p
    LEFT JOIN municipio   m ON m.idMunicipio   = p.idMunicipio
    LEFT JOIN departamento d ON d.idDepartamento = m.idDepartamento",
        $sqlProducto
    );
}

$producto = mysqli_fetch_assoc(mysqli_query($con, $sqlProducto));

if(!$producto){
    echo json_encode(["error" => "Producto no encontrado"]);
    exit();
}

$sqlImagenes = "SELECT rutaImagen FROM imagenesproducto
                WHERE idProducto = $id ORDER BY orden ASC";
$imagenes = [];
$qImg = mysqli_query($con, $sqlImagenes);
while($img = mysqli_fetch_assoc($qImg)){
    $imagenes[] = ["ruta" => $img['rutaImagen']];
}

$disponibles = (int)$producto['disponibles'];
$totalStock  = (int)$producto['totalStock'];
$enOferta    = (int)$producto['enOferta'];
$descuento   = (float)$producto['descuento'];
$precioFinal = ($enOferta && $descuento > 0)
    ? round($producto['precio'] * (1 - $descuento / 100))
    : (float)$producto['precio'];

// Parsear subcategorias: "id|nombre;;id|nombre"
$subcats = [];
if(!empty($producto['subcategorias_raw'])){
    foreach(explode(';;', $producto['subcategorias_raw']) as $raw){
        $parts = explode('|', $raw, 2);
        if(count($parts) === 2){
            $subcats[] = ["id" => (int)$parts[0], "nombre" => $parts[1]];
        }
    }
}

echo json_encode([
    "nombre"        => $producto['nombreProducto'],
    "precio"        => (float)$producto['precio'],
    "precioFinal"   => $precioFinal,
    "ubicacion"     => $producto['ubicacion'],
    "descripcion"   => $producto['descripcion'],
    "idCategoria"   => (int)$producto['idCategoria'],
    "categoria"     => $producto['nombreCategoria'],
    "subcategorias" => $subcats,
    "enOferta"      => $enOferta,
    "descuento"     => $descuento,
    "disponibles"   => $disponibles,
    "totalStock"    => $totalStock,
    "estado"        => $disponibles > 0 ? "Disponible" : "Agotado",
    "imagenes"      => $imagenes
]);
