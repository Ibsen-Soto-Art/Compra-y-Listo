<?php
session_start();
include "../../config/conection.php";
require_once "Model.php";
$con = conection();

$idLogeado  = (int)($_SESSION['idUsuario'] ?? 0);
$idEliminar = (int)($_GET['id']            ?? 0);

if (!$idLogeado || UsuarioModel::obtenerRol($con, $idLogeado) !== 'admin') {
    die("No tienes permisos");
}

if (!$idEliminar) {
    die("ID no valido");
}

if ($idEliminar === $idLogeado) {
    die("No puedes eliminar tu propio usuario");
}

UsuarioModel::eliminar($con, $idEliminar);

header("Location: FormularioUser.php");
exit;
