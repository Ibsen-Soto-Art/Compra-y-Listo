<?php
// Modelo de acceso a datos para el modulo Inventario.

class InventarioModel {

    public static function getItems($con, int $idProducto): array {
        $stmt = mysqli_prepare($con,
            "SELECT * FROM iteminventario WHERE idProducto = ? ORDER BY idItemInventario ASC");
        mysqli_stmt_bind_param($stmt, "i", $idProducto);
        mysqli_stmt_execute($stmt);
        return mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
    }

    public static function serieEnUso($con, string $serie, int $excluirId = 0): bool {
        $stmt = mysqli_prepare($con,
            "SELECT 1 FROM iteminventario WHERE numeroSerie = ? AND idItemInventario != ?");
        mysqli_stmt_bind_param($stmt, "si", $serie, $excluirId);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        return mysqli_stmt_num_rows($stmt) > 0;
    }

    public static function insertar($con, int $idProducto, string $serie, string $estado): bool {
        $stmt = mysqli_prepare($con,
            "INSERT INTO iteminventario (idProducto, numeroSerie, estadoItem) VALUES (?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "iss", $idProducto, $serie, $estado);
        return mysqli_stmt_execute($stmt);
    }

    public static function actualizar($con, int $idItem, int $idProducto, string $serie, string $estado): bool {
        $stmt = mysqli_prepare($con,
            "UPDATE iteminventario SET numeroSerie = ?, estadoItem = ?
             WHERE idItemInventario = ? AND idProducto = ?");
        mysqli_stmt_bind_param($stmt, "ssii", $serie, $estado, $idItem, $idProducto);
        return mysqli_stmt_execute($stmt);
    }

    public static function eliminar($con, int $id): bool {
        $stmt = mysqli_prepare($con,
            "DELETE FROM iteminventario WHERE idItemInventario = ?");
        mysqli_stmt_bind_param($stmt, "i", $id);
        return mysqli_stmt_execute($stmt);
    }

    public static function eliminarVarios($con, array $ids): int {
        if (empty($ids)) return 0;
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $tipos = str_repeat('i', count($ids));
        $stmt = mysqli_prepare($con,
            "DELETE FROM iteminventario WHERE idItemInventario IN ($placeholders)");
        mysqli_stmt_bind_param($stmt, $tipos, ...$ids);
        mysqli_stmt_execute($stmt);
        return mysqli_stmt_affected_rows($stmt);
    }

    public static function cambiarEstado($con, array $ids, string $estado): int {
        if (empty($ids)) return 0;
        $inList = implode(',', $ids);
        $stmt = mysqli_prepare($con,
            "UPDATE iteminventario SET estadoItem = ? WHERE idItemInventario IN ($inList)");
        mysqli_stmt_bind_param($stmt, "s", $estado);
        mysqli_stmt_execute($stmt);
        return mysqli_stmt_affected_rows($stmt);
    }

    // Devuelve info de categoria/subcategorias de un producto para construir prefijos de serie.
    public static function getInfoProducto($con, int $idProducto): array {
        $stmt = mysqli_prepare($con, "
            SELECT c.nombreCategoria, s.nombreSubcategoria
            FROM productosubcategoria ps
            INNER JOIN subcategoria s ON s.idSubcategoria = ps.idSubcategoria
            INNER JOIN categoria c    ON c.idCategoria    = s.idCategoria
            WHERE ps.idProducto = ?
            ORDER BY c.nombreCategoria, s.nombreSubcategoria");
        mysqli_stmt_bind_param($stmt, "i", $idProducto);
        mysqli_stmt_execute($stmt);
        return mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
    }

    // Devuelve el mayor numero de serie existente para un prefijo dado.
    public static function getMaxNumSerie($con, string $prefix): int {
        $like = $prefix . '%';
        $stmt = mysqli_prepare($con,
            "SELECT numeroSerie FROM iteminventario WHERE numeroSerie LIKE ?");
        mysqli_stmt_bind_param($stmt, "s", $like);
        mysqli_stmt_execute($stmt);
        $maxNum = 0;
        $result = mysqli_stmt_get_result($stmt);
        while ($r = mysqli_fetch_assoc($result)) {
            $num = (int)substr($r['numeroSerie'], strlen($prefix));
            if ($num > $maxNum) $maxNum = $num;
        }
        return $maxNum;
    }
}
