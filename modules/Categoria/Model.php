<?php
// Modelo de acceso a datos para el modulo Categoria.

class CategoriaModel {

    public static function insertar($con, string $nombre, string $imagen, int $idUsuario): bool {
        $stmt = mysqli_prepare($con,
            "INSERT INTO categoria (nombreCategoria, imagenCategoria, idUsuario) VALUES (?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "ssi", $nombre, $imagen, $idUsuario);
        return mysqli_stmt_execute($stmt);
    }

    public static function actualizar($con, int $id, string $nombre, string $imagen): bool {
        $stmt = mysqli_prepare($con,
            "UPDATE categoria SET nombreCategoria = ?, imagenCategoria = ? WHERE idCategoria = ?");
        mysqli_stmt_bind_param($stmt, "ssi", $nombre, $imagen, $id);
        return mysqli_stmt_execute($stmt);
    }

    public static function eliminar($con, int $id): bool {
        $stmt = mysqli_prepare($con, "DELETE FROM categoria WHERE idCategoria = ?");
        mysqli_stmt_bind_param($stmt, "i", $id);
        return mysqli_stmt_execute($stmt);
    }

    // Verifica si el nombre ya existe, excluyendo opcionalmente un ID.
    public static function nombreExiste($con, string $nombre, int $excluirId = 0): bool {
        $stmt = mysqli_prepare($con,
            "SELECT idCategoria FROM categoria WHERE nombreCategoria = ? AND idCategoria != ?");
        mysqli_stmt_bind_param($stmt, "si", $nombre, $excluirId);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        return mysqli_stmt_num_rows($stmt) > 0;
    }

    public static function obtenerEstado($con, int $id): ?string {
        $stmt = mysqli_prepare($con,
            "SELECT estadoCategoria FROM categoria WHERE idCategoria = ?");
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        return $row['estadoCategoria'] ?? null;
    }

    public static function toggleEstado($con, int $id, string $nuevoEstado): bool {
        $stmt = mysqli_prepare($con,
            "UPDATE categoria SET estadoCategoria = ? WHERE idCategoria = ?");
        mysqli_stmt_bind_param($stmt, "si", $nuevoEstado, $id);
        return mysqli_stmt_execute($stmt);
    }

    public static function contarProductos($con, int $id): int {
        $stmt = mysqli_prepare($con,
            "SELECT COUNT(*) AS total FROM producto WHERE idCategoria = ?");
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        return (int)($row['total'] ?? 0);
    }

    // Devuelve todas las categorias excepto la indicada, ordenadas por nombre.
    public static function listarExcepto($con, int $excluirId): array {
        $stmt = mysqli_prepare($con,
            "SELECT idCategoria, nombreCategoria FROM categoria
             WHERE idCategoria != ? ORDER BY nombreCategoria ASC");
        mysqli_stmt_bind_param($stmt, "i", $excluirId);
        mysqli_stmt_execute($stmt);
        return mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
    }

    public static function existe($con, int $id): bool {
        $stmt = mysqli_prepare($con,
            "SELECT idCategoria FROM categoria WHERE idCategoria = ?");
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        return mysqli_stmt_num_rows($stmt) > 0;
    }

    // Mueve todos los productos de una categoria a otra y elimina la de origen.
    public static function moverYEliminar($con, int $idOrigen, int $idDestino): array {
        $stmtMover = mysqli_prepare($con,
            "UPDATE producto SET idCategoria = ? WHERE idCategoria = ?");
        mysqli_stmt_bind_param($stmtMover, "ii", $idDestino, $idOrigen);
        if (!mysqli_stmt_execute($stmtMover)) {
            return ["ok" => false, "error" => "Error al mover productos"];
        }
        $movidos = mysqli_stmt_affected_rows($stmtMover);

        $stmtDel = mysqli_prepare($con, "DELETE FROM categoria WHERE idCategoria = ?");
        mysqli_stmt_bind_param($stmtDel, "i", $idOrigen);
        if (!mysqli_stmt_execute($stmtDel)) {
            return ["ok" => false, "error" => "Productos movidos pero error al eliminar categoria"];
        }
        return ["ok" => true, "movidos" => $movidos];
    }

    // Elimina multiples categorias (sin productos). Devuelve cuantas se eliminaron.
    public static function eliminarVarias($con, array $ids): int {
        if (empty($ids)) return 0;
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $tipos = str_repeat('i', count($ids));
        $stmt = mysqli_prepare($con,
            "DELETE FROM categoria WHERE idCategoria IN ($placeholders)");
        mysqli_stmt_bind_param($stmt, $tipos, ...$ids);
        mysqli_stmt_execute($stmt);
        return mysqli_stmt_affected_rows($stmt);
    }

    // Devuelve nombre de categoria por ID.
    public static function obtenerNombre($con, int $id): ?string {
        $stmt = mysqli_prepare($con,
            "SELECT nombreCategoria FROM categoria WHERE idCategoria = ?");
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        return $row['nombreCategoria'] ?? null;
    }

    public static function getStats($con): array {
        $row = mysqli_fetch_assoc(mysqli_query($con,
            "SELECT COUNT(*) AS total,
                    SUM(estadoCategoria = 'Activo') AS activas,
                    SUM(estadoCategoria = 'Oculto') AS ocultas
             FROM categoria"));
        return [
            'total'   => (int)($row['total']   ?? 0),
            'activas' => (int)($row['activas'] ?? 0),
            'ocultas' => (int)($row['ocultas'] ?? 0),
        ];
    }
}
