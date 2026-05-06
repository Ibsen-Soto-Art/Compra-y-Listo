<?php
session_start();
include("../../config/conection.php");
$con = conection();

if(!isset($_SESSION['usuarios'])){
    echo "No autorizado";
    exit();
}

function guardarImagenOptimizada($tmp, $destino){
    move_uploaded_file($tmp, $destino);
}

$idUsuario = $_SESSION['idUsuario'];

$nombre      = trim($_POST['nombre'] ?? '');
$precio      = $_POST['precio'] ?? 0;
$idMunicipio = intval($_POST['idMunicipio'] ?? 0);
$descripcion = trim($_POST['descripcion'] ?? '');
$idCategoria = intval($_POST['idCategoria'] ?? 0);
$subcats     = $_POST['subcategorias'] ?? [];
$subcats     = array_map('intval', $subcats);
$subcats     = array_filter($subcats, fn($v) => $v > 0);
$enOferta    = isset($_POST['enOferta']) ? 1 : 0;
$descuento   = min(99, max(0, floatval($_POST['descuento'] ?? 0)));
if(!$enOferta) $descuento = 0;

if(empty($nombre) || empty($precio) || !$idCategoria){
    echo "Datos incompletos";
    exit();
}

// Derivar texto de ubicación desde municipio
$ubicacion = "";
if($idMunicipio){
    $rUbic = mysqli_fetch_assoc(mysqli_query($con,
        "SELECT CONCAT(m.nombre, ', ', d.nombre) AS ubic
         FROM municipio m JOIN departamento d ON d.idDepartamento = m.idDepartamento
         WHERE m.idMunicipio = $idMunicipio"));
    $ubicacion = $rUbic['ubic'] ?? "";
}

// Insertar producto
$sql  = "INSERT INTO producto (nombreProducto, idUsuario, idCategoria, descripcion, precio, ubicacion, idMunicipio, enOferta, descuento) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
$stmt = mysqli_prepare($con, $sql);
$idMunNullable = $idMunicipio ?: null;
mysqli_stmt_bind_param($stmt, "siisdsiid", $nombre, $idUsuario, $idCategoria, $descripcion, $precio, $ubicacion, $idMunNullable, $enOferta, $descuento);

if(!mysqli_stmt_execute($stmt)){
    echo "Error al guardar producto";
    exit();
}

$idProducto = mysqli_insert_id($con);

// Vincular subcategorías (múltiples)
$sqlSub = "INSERT INTO productosubcategoria (idProducto, idSubcategoria) VALUES (?, ?)";
$stmtSub = mysqli_prepare($con, $sqlSub);
foreach($subcats as $idSubcat){
    mysqli_stmt_bind_param($stmtSub, "ii", $idProducto, $idSubcat);
    mysqli_stmt_execute($stmtSub);
}

// Crear carpeta y guardar imágenes
$carpeta = "../../uploads/productos/$idProducto/";
if(!file_exists($carpeta)) mkdir($carpeta, 0777, true);

$ordenes = $_POST['orden'] ?? [];
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

// Auto-crear unidades de inventario si se indicó cantidad
$cantidad = intval($_POST['cantidad'] ?? 0);
if($cantidad > 0){
    // Calcular prefijo usando categoría directa + subcategorías si existen
    $rCat = mysqli_fetch_assoc(mysqli_query($con, "SELECT nombreCategoria FROM categoria WHERE idCategoria=$idCategoria"));
    $categoria = $rCat['nombreCategoria'] ?? '';
    $subcatsInv = [];
    $qSub = mysqli_query($con, "
        SELECT s.nombreSubcategoria
        FROM productosubcategoria ps
        INNER JOIN subcategoria s ON s.idSubcategoria = ps.idSubcategoria
        WHERE ps.idProducto = $idProducto
        ORDER BY s.nombreSubcategoria
    ");
    while($r = mysqli_fetch_assoc($qSub)) $subcatsInv[] = $r['nombreSubcategoria'];
    $prefix = '';
    if($categoria) $prefix .= strtoupper(mb_substr($categoria, 0, 1));
    foreach($subcatsInv as $sub) $prefix .= strtoupper(mb_substr($sub, 0, 1));
    $prefix .= '-';

    $stmtItem = mysqli_prepare($con, "INSERT INTO iteminventario (idProducto, numeroSerie, estadoItem) VALUES (?, ?, 'Disponible')");
    for($i = 1; $i <= $cantidad; $i++){
        $serie = $prefix . str_pad($i, 3, '0', STR_PAD_LEFT);
        mysqli_stmt_bind_param($stmtItem, "is", $idProducto, $serie);
        mysqli_stmt_execute($stmtItem);
    }
}

echo "ok";
