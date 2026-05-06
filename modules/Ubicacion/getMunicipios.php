<?php
ob_start();
include "../../config/conection.php";
require_once "Model.php";
$con = conection();
ob_end_clean();
header('Content-Type: application/json; charset=utf-8');

$idDepartamento = (int)($_GET['idDepartamento'] ?? 0);
if (!$idDepartamento) {
    echo json_encode([]);
    exit;
}

echo json_encode(UbicacionModel::getMunicipios($con, $idDepartamento));
