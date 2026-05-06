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

try {

    $excel = IOFactory::load($archivo);
    $hoja  = $excel->getActiveSheet();
    $filas = $hoja->toArray();

    for($i = 1; $i < count($filas); $i++){

        $nombre = trim($filas[$i][0] ?? '');
        $imagen = trim($filas[$i][1] ?? '');
        $estado = trim($filas[$i][2] ?? '');

        // Validar nombre
        if(empty($nombre)){
            $response["errores"]++;
            $response["detalleErrores"][] = "Fila ".($i+1).": Nombre vacío";
            continue;
        }

        // Validar URL de imagen
        if(!empty($imagen) && !filter_var($imagen, FILTER_VALIDATE_URL)){
            $response["errores"]++;
            $response["detalleErrores"][] = "Fila ".($i+1).": URL de imagen inválida";
            continue;
        }

        // Validar y normalizar estadoCategoria
        $estadosValidos = ['Activo', 'Oculto'];
        if(empty($estado)){
            $estado = 'Activo';
        } else {
            // Normalizar capitalización
            $estadoNorm = ucfirst(strtolower($estado));
            if(!in_array($estadoNorm, $estadosValidos)){
                $response["errores"]++;
                $response["detalleErrores"][] = "Fila ".($i+1).": estadoCategoria inválido ('$estado'). Use 'Activo' u 'Oculto'";
                continue;
            }
            $estado = $estadoNorm;
        }

        // Evitar duplicados
        $nombreEsc = mysqli_real_escape_string($con, $nombre);
        $check = mysqli_query($con, "SELECT idCategoria FROM categoria WHERE nombreCategoria='$nombreEsc'");

        if(mysqli_num_rows($check) > 0){
            $response["errores"]++;
            $response["detalleErrores"][] = "Fila ".($i+1).": Categoría '$nombre' ya existe";
            continue;
        }

        // Insertar
        $idUsuario  = $_SESSION['idUsuario'];
        $imagenEsc  = mysqli_real_escape_string($con, $imagen);
        $estadoEsc  = mysqli_real_escape_string($con, $estado);

        $sql = "INSERT INTO categoria (nombreCategoria, imagenCategoria, estadoCategoria, idUsuario)
                VALUES ('$nombreEsc', '$imagenEsc', '$estadoEsc', '$idUsuario')";

        if(mysqli_query($con, $sql)){
            $response["insertados"]++;
        } else {
            $response["errores"]++;
            $response["detalleErrores"][] = "Fila ".($i+1).": Error BD — ".mysqli_error($con);
        }

    }

} catch(Exception $e){
    echo json_encode(["error" => $e->getMessage()]);
    exit;
}

echo json_encode($response);
