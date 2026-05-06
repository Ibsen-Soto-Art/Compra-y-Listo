<?php
session_start();
include("../../config/conection.php");
$con = conection();
if(!isset($_SESSION['usuarios'])){ echo json_encode([]); exit(); }
$idProducto = intval($_GET['idProducto'] ?? 0);
$q = mysqli_query($con, "SELECT * FROM iteminventario WHERE idProducto=$idProducto ORDER BY idItemInventario ASC");
$items = [];
while($r = mysqli_fetch_assoc($q)) $items[] = $r;
echo json_encode($items);
