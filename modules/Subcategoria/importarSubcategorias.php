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
    "detalleErrores" => []
];

if(!isset($_FILES['archivo'])){
    echo json_encode(["error" => "No se envió archivo"]);
    exit;
}

$archivo = $_FILES['archivo']['tmp_name'];

// Cargar mapa de categorías (nombre → id)
$mapCat = [];
$qCat = mysqli_query($con, "SELECT idCategoria, nombreCategoria FROM categoria");
while($c = mysqli_fetch_assoc($qCat)){
    $mapCat[strtolower(trim($c['nombreCategoria']))] = (int)$c['idCategoria'];
}

try {
    $excel = IOFactory::load($archivo);
    $hoja  = $excel->getActiveSheet();
    $filas = $hoja->toArray();

    for($i = 1; $i < count($filas); $i++){
        $nombre  = trim($filas[$i][0] ?? '');
        $catNom  = trim($filas[$i][1] ?? '');
        $estado  = trim($filas[$i][2] ?? '');
        $imagen  = trim($filas[$i][3] ?? '');

        if(empty($nombre)){
            $response["errores"]++;
            $response["detalleErrores"][] = "Fila ".($i+1).": Nombre vacío";
            continue;
        }

        if(empty($catNom)){
            $response["errores"]++;
            $response["detalleErrores"][] = "Fila ".($i+1).": Categoría vacía";
            continue;
        }

        $idCategoria = $mapCat[strtolower($catNom)] ?? null;
        if(!$idCategoria){
            $response["errores"]++;
            $response["detalleErrores"][] = "Fila ".($i+1).": Categoría '$catNom' no existe";
            continue;
        }

        $estadosValidos = ['Activo', 'Oculto'];
        if(empty($estado)){
            $estado = 'Activo';
        } else {
            $estado = ucfirst(strtolower($estado));
            if(!in_array($estado, $estadosValidos)){
                $response["errores"]++;
                $response["detalleErrores"][] = "Fila ".($i+1).": Estado inválido '$estado'. Use Activo u Oculto";
                continue;
            }
        }

        if(!empty($imagen) && !filter_var($imagen, FILTER_VALIDATE_URL)){
            $response["errores"]++;
            $response["detalleErrores"][] = "Fila ".($i+1).": URL de imagen inválida";
            continue;
        }

        // Evitar duplicados (mismo nombre y misma categoría)
        $stmt = mysqli_prepare($con, "SELECT idSubcategoria FROM subcategoria WHERE nombreSubcategoria=? AND idCategoria=?");
        mysqli_stmt_bind_param($stmt, "si", $nombre, $idCategoria);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        if(mysqli_stmt_num_rows($stmt) > 0){
            $response["errores"]++;
            $response["detalleErrores"][] = "Fila ".($i+1).": '$nombre' ya existe en '$catNom'";
            mysqli_stmt_close($stmt);
            continue;
        }
        mysqli_stmt_close($stmt);

        $stmt2 = mysqli_prepare($con, "INSERT INTO subcategoria (nombreSubcategoria, idCategoria, estadoSubcategoria, imagenUrl) VALUES (?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt2, "siss", $nombre, $idCategoria, $estado, $imagen);
        if(mysqli_stmt_execute($stmt2)){
            $response["insertados"]++;
        } else {
            $response["errores"]++;
            $response["detalleErrores"][] = "Fila ".($i+1).": Error BD — ".mysqli_error($con);
        }
        mysqli_stmt_close($stmt2);
    }

} catch(Exception $e){
    echo json_encode(["error" => $e->getMessage()]);
    exit;
}

echo json_encode($response);
