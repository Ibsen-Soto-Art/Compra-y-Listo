<?php
ob_start();
include "../../config/conection.php";
require_once "Model.php";
$con = conection();
ob_end_clean();
header('Content-Type: application/json; charset=utf-8');

echo json_encode(UbicacionModel::getDepartamentos($con));
