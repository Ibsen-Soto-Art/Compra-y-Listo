<?php
session_start();
include("../../config/conection.php");
require '../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

header('Content-Type: application/json');

$con = conection();

$response = [
    "insertados"     => 0,
    "errores"        => 0,
    "detalleErrores" => [],
    "advertencias"   => []
];

if (!isset($_FILES['archivo'])) {
    echo json_encode(["error" => "No se envió archivo"]);
    exit;
}

// Mapa de imágenes subidas: nombre_lowercase → tmp_path
$mapaImagenes = [];
if (isset($_FILES['imagenes']) && is_array($_FILES['imagenes']['name'])) {
    foreach ($_FILES['imagenes']['name'] as $k => $nombreOrig) {
        if ($_FILES['imagenes']['error'][$k] === UPLOAD_ERR_OK && !empty($nombreOrig)) {
            $mapaImagenes[strtolower(trim($nombreOrig))] = [
                'tmp'  => $_FILES['imagenes']['tmp_name'][$k],
                'orig' => $nombreOrig
            ];
        }
    }
}

$archivo = $_FILES['archivo']['tmp_name'];

try {
    $excel = IOFactory::load($archivo);
    $hoja  = $excel->getActiveSheet();
    $filas = $hoja->toArray();

    // Cachear categorías
    $categorias = [];
    $resCat = mysqli_query($con, "SELECT idCategoria, nombreCategoria FROM categoria");
    while ($c = mysqli_fetch_assoc($resCat)) {
        $categorias[strtolower(trim($c['nombreCategoria']))] = (int)$c['idCategoria'];
    }

    // Cachear subcategorías: [idCategoria][nombre_lower] = idSubcategoria
    $subcategorias = [];
    $resSub = mysqli_query($con, "SELECT idSubcategoria, idCategoria, nombreSubcategoria FROM subcategoria");
    while ($s = mysqli_fetch_assoc($resSub)) {
        $subcategorias[(int)$s['idCategoria']][strtolower(trim($s['nombreSubcategoria']))] = (int)$s['idSubcategoria'];
    }

    // Cachear municipios: [depto_lower][mun_lower] = [idMunicipio, textoUbicacion]
    $municipiosMap = [];
    $tblMun = mysqli_fetch_assoc(mysqli_query($con,
        "SELECT COUNT(*) AS c FROM INFORMATION_SCHEMA.TABLES
         WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='municipio'"));
    if ((int)$tblMun['c'] > 0) {
        $qMun = mysqli_query($con,
            "SELECT m.idMunicipio, m.nombre AS mun, d.nombre AS dep
             FROM municipio m JOIN departamento d ON d.idDepartamento = m.idDepartamento");
        while ($m = mysqli_fetch_assoc($qMun)) {
            $depKey = strtolower(trim($m['dep']));
            $munKey = strtolower(trim($m['mun']));
            $municipiosMap[$depKey][$munKey] = [
                'id'    => (int)$m['idMunicipio'],
                'texto' => $m['mun'] . ', ' . $m['dep'],
            ];
        }
    }

    $idUsuario = (int)$_SESSION['idUsuario'];

    for ($i = 1; $i < count($filas); $i++) {
        $nombre      = trim($filas[$i][0] ?? '');
        $precio      = trim($filas[$i][1] ?? '');
        $catNombre   = trim($filas[$i][2] ?? '');
        $subNombre   = trim($filas[$i][3] ?? '');
        $deptoNombre = trim($filas[$i][4] ?? '');
        $munNombre   = trim($filas[$i][5] ?? '');
        $descripcion = trim($filas[$i][6] ?? '');

        // Imágenes (columnas H-L, índices 7-11)
        $nombresImagen = [];
        for ($c = 7; $c <= 11; $c++) {
            $n = trim($filas[$i][$c] ?? '');
            if ($n !== '') $nombresImagen[] = $n;
        }

        // Saltar filas vacías
        if (empty($nombre) && empty($precio) && empty($catNombre)) continue;

        // Validaciones obligatorias
        if (empty($nombre)) {
            $response["errores"]++;
            $response["detalleErrores"][] = "Fila " . ($i + 1) . ": Nombre vacío";
            continue;
        }
        if (!is_numeric($precio) || floatval($precio) <= 0) {
            $response["errores"]++;
            $response["detalleErrores"][] = "Fila " . ($i + 1) . ": Precio inválido ('$precio')";
            continue;
        }
        $idCategoria = $categorias[strtolower($catNombre)] ?? null;
        if (!$idCategoria) {
            $response["errores"]++;
            $response["detalleErrores"][] = "Fila " . ($i + 1) . ": Categoría '$catNombre' no encontrada";
            continue;
        }

        // Subcategoría opcional
        $idSubcategoria = null;
        if (!empty($subNombre)) {
            $idSubcategoria = $subcategorias[$idCategoria][strtolower($subNombre)] ?? null;
            if (!$idSubcategoria) {
                $response["errores"]++;
                $response["detalleErrores"][] = "Fila " . ($i + 1) . ": Subcategoría '$subNombre' no encontrada en '$catNombre'";
                continue;
            }
        }

        // Resolver municipio
        $idMunicipio  = null;
        $ubicacion    = '';
        if (!empty($munNombre)) {
            $depKey = strtolower($deptoNombre);
            $munKey = strtolower($munNombre);
            if (!empty($municipiosMap[$depKey][$munKey])) {
                $idMunicipio = $municipiosMap[$depKey][$munKey]['id'];
                $ubicacion   = $municipiosMap[$depKey][$munKey]['texto'];
            } else {
                // Municipio no encontrado: guardar texto libre y advertir
                $ubicacion = trim($munNombre . ($deptoNombre ? ', ' . $deptoNombre : ''));
                $response["advertencias"][] = "Fila " . ($i + 1) . " ($nombre): municipio '$munNombre' no encontrado, se guardó como texto";
            }
        }

        // Insertar producto
        $precioFloat = floatval($precio);
        $stmt = mysqli_prepare($con,
            "INSERT INTO producto (nombreProducto, idCategoria, idUsuario, descripcion, precio, ubicacion, idMunicipio)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        mysqli_stmt_bind_param($stmt, "siisdsi", $nombre, $idCategoria, $idUsuario, $descripcion, $precioFloat, $ubicacion, $idMunicipio);

        if (!mysqli_stmt_execute($stmt)) {
            $response["errores"]++;
            $response["detalleErrores"][] = "Fila " . ($i + 1) . ": Error al insertar — " . mysqli_error($con);
            continue;
        }

        $idProducto = (int)mysqli_insert_id($con);
        $response["insertados"]++;

        // Vincular subcategoría si se indicó
        if ($idSubcategoria) {
            $stmtSub = mysqli_prepare($con, "INSERT INTO productosubcategoria (idProducto, idSubcategoria) VALUES (?, ?)");
            mysqli_stmt_bind_param($stmtSub, "ii", $idProducto, $idSubcategoria);
            mysqli_stmt_execute($stmtSub);
        }

        // Procesar imágenes
        if (empty($nombresImagen)) continue;

        $carpeta = "../../uploads/productos/$idProducto/";
        if (!is_dir($carpeta)) mkdir($carpeta, 0777, true);

        $orden = 0;
        foreach ($nombresImagen as $imgNombre) {
            $key = strtolower($imgNombre);
            if (!isset($mapaImagenes[$key])) {
                $response["advertencias"][] = "Fila " . ($i + 1) . " ($nombre): imagen '$imgNombre' no fue subida";
                continue;
            }
            $tmpPath = $mapaImagenes[$key]['tmp'];
            if (!is_uploaded_file($tmpPath)) {
                $response["advertencias"][] = "Fila " . ($i + 1) . " ($nombre): imagen '$imgNombre' no válida";
                continue;
            }
            $ext = strtolower(pathinfo($imgNombre, PATHINFO_EXTENSION));
            if (!in_array($ext, ['jpg','jpeg','png','webp','gif'])) {
                $response["advertencias"][] = "Fila " . ($i + 1) . " ($nombre): extensión '$ext' no permitida";
                continue;
            }
            $nombreArchivo = uniqid("prod_") . "." . $ext;
            if (copy($tmpPath, $carpeta . $nombreArchivo)) {
                $rutaBD    = rtrim(SITE_URL, '/') . "/uploads/productos/$idProducto/$nombreArchivo";
                $principal = ($orden === 0) ? 1 : 0;
                $stmtImg   = mysqli_prepare($con, "INSERT INTO imagenesproducto (idProducto, rutaImagen, esPrincipal, orden) VALUES (?, ?, ?, ?)");
                mysqli_stmt_bind_param($stmtImg, "isii", $idProducto, $rutaBD, $principal, $orden);
                mysqli_stmt_execute($stmtImg);
                $orden++;
            } else {
                $response["advertencias"][] = "Fila " . ($i + 1) . " ($nombre): no se pudo guardar '$imgNombre'";
            }
        }
    }

} catch (Exception $e) {
    echo json_encode(["error" => $e->getMessage()]);
    exit;
}

echo json_encode($response);
