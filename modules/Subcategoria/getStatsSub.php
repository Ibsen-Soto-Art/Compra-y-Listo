<?php
include("../../config/conection.php");
$con = conection();
header('Content-Type: application/json');
$row = mysqli_fetch_assoc(mysqli_query($con,
    "SELECT COUNT(*) AS total,
            SUM(estadoSubcategoria='Activo') AS activas,
            SUM(estadoSubcategoria='Oculto') AS ocultas
     FROM subcategoria"));
echo json_encode([
    'total'   => (int)($row['total']   ?? 0),
    'activas' => (int)($row['activas'] ?? 0),
    'ocultas' => (int)($row['ocultas'] ?? 0),
]);
