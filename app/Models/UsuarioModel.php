<?php

namespace App\Models;
// Modelo de acceso a datos para el modulo Usuario.
// Solo contiene metodos que ejecutan queries; la logica de negocio
// y la validacion de permisos quedan en los controladores.

class UsuarioModel {

    // Devuelve el rol de un usuario por su ID, o null si no existe.
    public static function obtenerRol($con, int $id): ?string {
        $stmt = mysqli_prepare($con, "SELECT rol FROM usuarios WHERE idUsuario = ?");
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $rol);
        mysqli_stmt_fetch($stmt);
        mysqli_stmt_close($stmt);
        return $rol ?? null;
    }

    // Verifica si un correo ya existe, excluyendo opcionalmente un ID.
    public static function correoExiste($con, string $correo, int $excluirId = 0): bool {
        $stmt = mysqli_prepare($con,
            "SELECT idUsuario FROM usuarios WHERE correo = ? AND idUsuario != ?");
        mysqli_stmt_bind_param($stmt, "si", $correo, $excluirId);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        return mysqli_stmt_num_rows($stmt) > 0;
    }

    // Inserta un nuevo usuario. Devuelve true en exito.
    public static function crear($con, string $nombre, string $correo, string $pass, string $rol): bool {
        $stmt = mysqli_prepare($con,
            "INSERT INTO usuarios (nombreUsuario, correo, contraseña, rol) VALUES (?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "ssss", $nombre, $correo, $pass, $rol);
        return mysqli_stmt_execute($stmt);
    }

    // Actualiza los datos de un usuario existente. Devuelve true en exito.
    public static function actualizar($con, int $id, string $nombre, string $correo, string $pass, string $rol): bool {
        $stmt = mysqli_prepare($con,
            "UPDATE usuarios SET nombreUsuario = ?, correo = ?, contraseña = ?, rol = ? WHERE idUsuario = ?");
        mysqli_stmt_bind_param($stmt, "ssssi", $nombre, $correo, $pass, $rol, $id);
        return mysqli_stmt_execute($stmt);
    }

    // Elimina un usuario por ID. Devuelve true en exito.
    public static function eliminar($con, int $id): bool {
        $stmt = mysqli_prepare($con, "DELETE FROM usuarios WHERE idUsuario = ?");
        mysqli_stmt_bind_param($stmt, "i", $id);
        return mysqli_stmt_execute($stmt);
    }
}
