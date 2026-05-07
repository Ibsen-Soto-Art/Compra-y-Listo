<?php
use App\Models\SubcategoriaModel;

header('Content-Type: application/json');
include "../../config/conection.php";
$con = conection();

echo json_encode(SubcategoriaModel::getStats($con));
