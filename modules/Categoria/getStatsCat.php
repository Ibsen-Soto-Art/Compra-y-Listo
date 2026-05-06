<?php
include("../../config/conection.php");
$con = conection();
header('Content-Type: application/json');
$row = mysqli_fetch_assoc(mysqli_query($con,
    "SELECT COUNT(*) AS total,
            SUM(estadoCategoria='Activo') AS activas,
            SUM(estadoCategoria='Oculto') AS ocultas
     FROM categoria"));
echo json_encode([
    'total'   => (int)($row['total']   ?? 0),
    'activas' => (int)($row['activas'] ?? 0),
    'ocultas' => (int)($row['ocultas'] ?? 0),
]);
