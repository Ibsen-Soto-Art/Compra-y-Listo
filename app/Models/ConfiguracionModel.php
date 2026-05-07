<?php

namespace App\Models;
// Modelo de acceso a datos para el modulo Configuracion.

class ConfiguracionModel {

    // Guarda o actualiza una clave de configuracion.
    public static function guardar($con, string $clave, string $valor): bool {
        $stmt = mysqli_prepare($con,
            "INSERT INTO configuracion (clave, valor) VALUES (?, ?)
             ON DUPLICATE KEY UPDATE valor = VALUES(valor)");
        mysqli_stmt_bind_param($stmt, "ss", $clave, $valor);
        return mysqli_stmt_execute($stmt);
    }

    public static function obtener($con, string $clave): ?string {
        $stmt = mysqli_prepare($con,
            "SELECT valor FROM configuracion WHERE clave = ?");
        mysqli_stmt_bind_param($stmt, "s", $clave);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $valor);
        mysqli_stmt_fetch($stmt);
        mysqli_stmt_close($stmt);
        return $valor ?? null;
    }
}
