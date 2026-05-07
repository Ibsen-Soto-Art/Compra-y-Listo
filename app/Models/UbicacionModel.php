<?php

namespace App\Models;
// Modelo de acceso a datos para el modulo Ubicacion.

class UbicacionModel {

    public static function getDepartamentos($con): array {
        $q = mysqli_query($con,
            "SELECT idDepartamento, nombre FROM departamento ORDER BY nombre ASC");
        $result = [];
        while ($r = mysqli_fetch_assoc($q)) {
            $result[] = ["id" => (int)$r['idDepartamento'], "nombre" => $r['nombre']];
        }
        return $result;
    }

    public static function getMunicipios($con, int $idDepartamento): array {
        $stmt = mysqli_prepare($con,
            "SELECT idMunicipio, nombre FROM municipio
             WHERE idDepartamento = ? ORDER BY nombre ASC");
        mysqli_stmt_bind_param($stmt, "i", $idDepartamento);
        mysqli_stmt_execute($stmt);
        $res    = mysqli_stmt_get_result($stmt);
        $result = [];
        while ($r = mysqli_fetch_assoc($res)) {
            $result[] = ["id" => (int)$r['idMunicipio'], "nombre" => $r['nombre']];
        }
        mysqli_free_result($res);
        mysqli_stmt_close($stmt);
        return $result;
    }
}
