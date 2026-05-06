<?php
header('Content-Type: application/json');
include "../../config/conection.php";
require_once "Model.php";
$con = conection();

echo json_encode(SubcategoriaModel::getStats($con));
