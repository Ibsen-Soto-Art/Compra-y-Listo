<?php
use App\Models\UbicacionModel;

ob_start();
include "../../config/conection.php";
$con = conection();
ob_end_clean();
header('Content-Type: application/json; charset=utf-8');

echo json_encode(UbicacionModel::getDepartamentos($con));
