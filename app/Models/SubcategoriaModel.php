<?php

namespace App\Models;
// Modelo de acceso a datos para el modulo Subcategoria.

class SubcategoriaModel {

    public static function insertar($con, string $nombre, int $idCategoria, string $estado, ?string $imagenUrl): bool {
        $stmt = mysqli_prepare($con,
            "INSERT INTO subcategoria (nombreSubcategoria, idCategoria, estadoSubcategoria, imagenUrl)
             VALUES (?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "siss", $nombre, $idCategoria, $estado, $imagenUrl);
        return mysqli_stmt_execute($stmt);
    }

    public static function actualizar($con, int $id, string $nombre, int $idCategoria, string $estado, ?string $imagenUrl): bool {
        $stmt = mysqli_prepare($con,
            "UPDATE subcategoria SET nombreSubcategoria = ?, idCategoria = ?, estadoSubcategoria = ?, imagenUrl = ?
             WHERE idSubcategoria = ?");
        mysqli_stmt_bind_param($stmt, "sissi", $nombre, $idCategoria, $estado, $imagenUrl, $id);
        return mysqli_stmt_execute($stmt);
    }

    public static function eliminar($con, int $id): bool {
        $stmt = mysqli_prepare($con, "DELETE FROM subcategoria WHERE idSubcategoria = ?");
        mysqli_stmt_bind_param($stmt, "i", $id);
        return mysqli_stmt_execute($stmt);
    }

    public static function eliminarVarias($con, array $ids): int {
        if (empty($ids)) return 0;
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $tipos = str_repeat('i', count($ids));
        $stmt = mysqli_prepare($con,
            "DELETE FROM subcategoria WHERE idSubcategoria IN ($placeholders)");
        mysqli_stmt_bind_param($stmt, $tipos, ...$ids);
        mysqli_stmt_execute($stmt);
        return mysqli_stmt_affected_rows($stmt);
    }

    // Verifica si el nombre ya existe en la misma categoria, excluyendo opcionalmente un ID.
    public static function nombreExiste($con, string $nombre, int $idCategoria, int $excluirId = 0): bool {
        $stmt = mysqli_prepare($con,
            "SELECT idSubcategoria FROM subcategoria
             WHERE nombreSubcategoria = ? AND idCategoria = ? AND idSubcategoria != ?");
        mysqli_stmt_bind_param($stmt, "sii", $nombre, $idCategoria, $excluirId);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        return mysqli_stmt_num_rows($stmt) > 0;
    }

    public static function obtenerEstado($con, int $id): ?string {
        $stmt = mysqli_prepare($con,
            "SELECT estadoSubcategoria FROM subcategoria WHERE idSubcategoria = ?");
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $estado);
        mysqli_stmt_fetch($stmt);
        return $estado;
    }

    public static function toggleEstado($con, int $id, string $nuevoEstado): bool {
        $stmt = mysqli_prepare($con,
            "UPDATE subcategoria SET estadoSubcategoria = ? WHERE idSubcategoria = ?");
        mysqli_stmt_bind_param($stmt, "si", $nuevoEstado, $id);
        return mysqli_stmt_execute($stmt);
    }

    public static function getPorCategoria($con, int $idCategoria): array {
        $stmt = mysqli_prepare($con,
            "SELECT idSubcategoria, nombreSubcategoria FROM subcategoria
             WHERE idCategoria = ? AND estadoSubcategoria = 'Activo'
             ORDER BY nombreSubcategoria ASC");
        mysqli_stmt_bind_param($stmt, "i", $idCategoria);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $idSub, $nombreSub);
        $result = [];
        while (mysqli_stmt_fetch($stmt)) {
            $result[] = ['idSubcategoria' => $idSub, 'nombreSubcategoria' => $nombreSub];
        }
        mysqli_stmt_close($stmt);
        return $result;
    }

    public static function getStats($con): array {
        $row = mysqli_fetch_assoc(mysqli_query($con,
            "SELECT COUNT(*) AS total,
                    SUM(estadoSubcategoria = 'Activo') AS activas,
                    SUM(estadoSubcategoria = 'Oculto') AS ocultas
             FROM subcategoria"));
        return [
            'total'   => (int)($row['total']   ?? 0),
            'activas' => (int)($row['activas'] ?? 0),
            'ocultas' => (int)($row['ocultas'] ?? 0),
        ];
    }
}
