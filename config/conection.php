<?php
require_once __DIR__ . '/config.php';

function conection() {
    $con = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

    if (!$con) {
        if (ENTORNO === 'local') {
            die('Error de conexión: ' . mysqli_connect_error());
        } else {
            die('Error al conectar con la base de datos. Contacte al administrador.');
        }
    }

    mysqli_set_charset($con, 'utf8mb4');
    return $con;
}
