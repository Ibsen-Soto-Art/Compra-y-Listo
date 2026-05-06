<?php
ob_start();
include("../../config/conection.php");
$con = conection();
ob_end_clean();
ini_set('display_errors', 0);
error_reporting(0);
header('Content-Type: application/json; charset=utf-8');

// 🔴 VALIDAR ID
if(!isset($_GET['id']) || empty($_GET['id'])){
    echo json_encode(["error" => "ID no recibido"]);
    exit();
}

$id = intval($_GET['id']);

// 🔴 OBTENER DATOS DEL PRODUCTO
// Detectar si existe tabla municipio
$_tblMun = mysqli_fetch_assoc(mysqli_query($con,
    "SELECT COUNT(*) AS c FROM INFORMATION_SCHEMA.TABLES
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'municipio'"));
$_conMun = (int)$_tblMun['c'] > 0;

if($_conMun){
    $sqlProducto = "SELECT
                p.idProducto,
                p.nombreProducto,
                p.precio,
                p.descripcion,
                e.nombreEstado,
                p.idMunicipio,
                m.idDepartamento,
                CASE
                    WHEN m.idMunicipio IS NOT NULL
                    THEN CONCAT(m.nombre, ', ', d.nombre)
                    ELSE COALESCE(p.ubicacion, '')
                END AS ubicacion
            FROM producto p
            LEFT JOIN estado      e ON e.idEstado      = p.idEstado
            LEFT JOIN municipio   m ON m.idMunicipio   = p.idMunicipio
            LEFT JOIN departamento d ON d.idDepartamento = m.idDepartamento
            WHERE p.idProducto = $id";
} else {
    $sqlProducto = "SELECT
                p.idProducto,
                p.nombreProducto,
                p.precio,
                p.descripcion,
                e.nombreEstado,
                NULL AS idMunicipio,
                NULL AS idDepartamento,
                COALESCE(p.ubicacion, '') AS ubicacion
            FROM producto p
            LEFT JOIN estado e ON e.idEstado = p.idEstado
            WHERE p.idProducto = $id";
}

$queryProducto = mysqli_query($con, $sqlProducto);

if(!$queryProducto){
    echo json_encode(["error" => "Error en consulta producto"]);
    exit();
}

$producto = mysqli_fetch_assoc($queryProducto);

if(!$producto){
    echo json_encode(["error" => "Producto no encontrado"]);
    exit();
}

// 🔴 OBTENER IMÁGENES ORDENADAS
$sqlImagenes = "SELECT 
                    idImagen,
                    rutaImagen,
                    orden
                FROM imagenesproducto
                WHERE idProducto = $id
                ORDER BY orden ASC";

$queryImagenes = mysqli_query($con, $sqlImagenes);

$imagenes = [];

while($img = mysqli_fetch_assoc($queryImagenes)){
    $imagenes[] = [
        "idImagen" => $img['idImagen'],
        "ruta" => $img['rutaImagen'],
        "orden" => $img['orden']
    ];
}

// 🔴 RESPUESTA FINAL
$response = [
    "idProducto"    => $producto['idProducto'],
    "nombre"        => $producto['nombreProducto'],
    "precio"        => (float)$producto['precio'],
    "ubicacion"     => $producto['ubicacion'],
    "idMunicipio"   => $producto['idMunicipio'] ? (int)$producto['idMunicipio'] : null,
    "idDepartamento"=> $producto['idDepartamento'] ? (int)$producto['idDepartamento'] : null,
    "descripcion"   => $producto['descripcion'],
    "estado"        => $producto['nombreEstado'],
    "imagenes"      => $imagenes
];

// 🔴 JSON
header('Content-Type: application/json');
echo json_encode($response);
?>