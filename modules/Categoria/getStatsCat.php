<?php
use App\Models\CategoriaModel;

header('Content-Type: application/json');
include "../../config/conection.php";
$con = conection();

echo json_encode(CategoriaModel::getStats($con));
