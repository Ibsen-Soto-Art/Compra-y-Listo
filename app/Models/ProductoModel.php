<?php

namespace App\Models;
// Modelo de acceso a datos para el modulo Productos.

class ProductoModel {

    // Resuelve el texto de ubicacion a partir de un idMunicipio.
    public static function getUbicacion($con, int $idMunicipio): string {
        $stmt = mysqli_prepare($con,
            "SELECT CONCAT(m.nombre, ', ', d.nombre) AS ubic
             FROM municipio m JOIN departamento d ON d.idDepartamento = m.idDepartamento
             WHERE m.idMunicipio = ?");
        mysqli_stmt_bind_param($stmt, "i", $idMunicipio);
        mysqli_stmt_execute($stmt);
        $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        return $row['ubic'] ?? '';
    }

    // Devuelve idMunicipio y ubicacion actuales de un producto.
    public static function getUbicacionActual($con, int $idProducto): array {
        $stmt = mysqli_prepare($con,
            "SELECT idMunicipio, ubicacion FROM producto WHERE idProducto = ?");
        mysqli_stmt_bind_param($stmt, "i", $idProducto);
        mysqli_stmt_execute($stmt);
        $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        return $row ?? ['idMunicipio' => null, 'ubicacion' => ''];
    }

    public static function insertar($con, array $d): int {
        $stmt = mysqli_prepare($con,
            "INSERT INTO producto
             (nombreProducto, idUsuario, idCategoria, descripcion, precio, ubicacion, idMunicipio, enOferta, descuento)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "siisdsiid",
            $d['nombre'], $d['idUsuario'], $d['idCategoria'],
            $d['descripcion'], $d['precio'], $d['ubicacion'],
            $d['idMunicipio'], $d['enOferta'], $d['descuento']);
        return mysqli_stmt_execute($stmt) ? (int)mysqli_insert_id($con) : 0;
    }

    public static function actualizar($con, int $id, array $d): bool {
        $stmt = mysqli_prepare($con,
            "UPDATE producto
             SET nombreProducto=?, precio=?, ubicacion=?, idMunicipio=?,
                 descripcion=?, idCategoria=?, enOferta=?, descuento=?
             WHERE idProducto=?");
        mysqli_stmt_bind_param($stmt, "sdsisiidi",
            $d['nombre'], $d['precio'], $d['ubicacion'], $d['idMunicipio'],
            $d['descripcion'], $d['idCategoria'], $d['enOferta'], $d['descuento'], $id);
        return mysqli_stmt_execute($stmt);
    }

    public static function eliminar($con, int $id): bool {
        $stmt = mysqli_prepare($con, "DELETE FROM producto WHERE idProducto = ?");
        mysqli_stmt_bind_param($stmt, "i", $id);
        return mysqli_stmt_execute($stmt);
    }

    public static function eliminarVarios($con, array $ids): int {
        if (empty($ids)) return 0;
        $placeholders = implode(',', $ids);
        $result = mysqli_query($con, "DELETE FROM producto WHERE idProducto IN ($placeholders)");
        return $result ? (int)mysqli_affected_rows($con) : 0;
    }

    // Subcategorias
    public static function eliminarSubcategorias($con, int $idProducto): void {
        $stmt = mysqli_prepare($con, "DELETE FROM productosubcategoria WHERE idProducto = ?");
        mysqli_stmt_bind_param($stmt, "i", $idProducto);
        mysqli_stmt_execute($stmt);
    }

    public static function insertarSubcategorias($con, int $idProducto, array $subcats): void {
        if (empty($subcats)) return;
        $stmt = mysqli_prepare($con,
            "INSERT INTO productosubcategoria (idProducto, idSubcategoria) VALUES (?, ?)");
        foreach ($subcats as $idSub) {
            mysqli_stmt_bind_param($stmt, "ii", $idProducto, $idSub);
            mysqli_stmt_execute($stmt);
        }
    }

    // Imagenes
    public static function getImagenes($con, int $idProducto): array {
        $stmt = mysqli_prepare($con,
            "SELECT idImagen, rutaImagen, esPrincipal, orden
             FROM imagenesproducto WHERE idProducto = ? ORDER BY orden ASC");
        mysqli_stmt_bind_param($stmt, "i", $idProducto);
        mysqli_stmt_execute($stmt);
        return mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
    }

    public static function insertarImagen($con, int $idProducto, string $ruta, int $esPrincipal, int $orden): bool {
        $stmt = mysqli_prepare($con,
            "INSERT INTO imagenesproducto (idProducto, rutaImagen, esPrincipal, orden) VALUES (?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "isii", $idProducto, $ruta, $esPrincipal, $orden);
        return mysqli_stmt_execute($stmt);
    }

    /**
     * Inserta multiples imagenes en un solo INSERT.
     * $imagenes: array de ['ruta' => string, 'esPrincipal' => int, 'orden' => int]
     */
    public static function insertarImagenesMasivo($con, int $idProducto, array $imagenes): bool {
        if (empty($imagenes)) return true;

        $placeholders = implode(', ', array_fill(0, count($imagenes), '(?, ?, ?, ?)'));
        $sql  = "INSERT INTO imagenesproducto (idProducto, rutaImagen, esPrincipal, orden) VALUES $placeholders";
        $stmt = mysqli_prepare($con, $sql);

        // Construir arrays de tipos y valores para bind dinamico
        $tipos  = str_repeat('isii', count($imagenes));
        $params = [];
        foreach ($imagenes as $img) {
            $params[] = $idProducto;
            $params[] = $img['ruta'];
            $params[] = $img['esPrincipal'];
            $params[] = $img['orden'];
        }

        mysqli_stmt_bind_param($stmt, $tipos, ...$params);
        return mysqli_stmt_execute($stmt);
    }

    public static function actualizarOrdenImagen($con, int $idImagen, int $orden, int $esPrincipal): bool {
        $stmt = mysqli_prepare($con,
            "UPDATE imagenesproducto SET orden = ?, esPrincipal = ? WHERE idImagen = ?");
        mysqli_stmt_bind_param($stmt, "iii", $orden, $esPrincipal, $idImagen);
        return mysqli_stmt_execute($stmt);
    }

    public static function getImagenPorId($con, int $idImagen): ?array {
        $stmt = mysqli_prepare($con,
            "SELECT rutaImagen FROM imagenesproducto WHERE idImagen = ?");
        mysqli_stmt_bind_param($stmt, "i", $idImagen);
        mysqli_stmt_execute($stmt);
        return mysqli_fetch_assoc(mysqli_stmt_get_result($stmt)) ?: null;
    }

    public static function eliminarImagen($con, int $idImagen): bool {
        $stmt = mysqli_prepare($con, "DELETE FROM imagenesproducto WHERE idImagen = ?");
        mysqli_stmt_bind_param($stmt, "i", $idImagen);
        return mysqli_stmt_execute($stmt);
    }

    public static function eliminarImagenesPorProducto($con, array $ids): void {
        if (empty($ids)) return;
        $placeholders = implode(',', $ids);
        mysqli_query($con, "DELETE FROM imagenesproducto WHERE idProducto IN ($placeholders)");
    }

    // Devuelve datos basicos del producto con municipio para el formulario de edicion.
    public static function obtener($con, int $id): ?array {
        $stmt = mysqli_prepare($con,
            "SELECT p.idProducto, p.nombreProducto, p.precio, p.ubicacion,
                    p.idCategoria, p.idEstado, p.descripcion, p.idMunicipio,
                    m.idDepartamento
             FROM producto p
             LEFT JOIN municipio m ON m.idMunicipio = p.idMunicipio
             WHERE p.idProducto = ?");
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        return mysqli_fetch_assoc(mysqli_stmt_get_result($stmt)) ?: null;
    }

    // Devuelve datos completos del producto con ubicacion calculada (para vista publica).
    public static function obtenerCompleto($con, int $id, bool $conMunicipio): ?array {
        if ($conMunicipio) {
            $stmt = mysqli_prepare($con,
                "SELECT p.idProducto, p.nombreProducto, p.precio, p.descripcion,
                        e.nombreEstado, p.idMunicipio, m.idDepartamento,
                        CASE WHEN m.idMunicipio IS NOT NULL
                             THEN CONCAT(m.nombre, ', ', d.nombre)
                             ELSE COALESCE(p.ubicacion, '')
                        END AS ubicacion
                 FROM producto p
                 LEFT JOIN estado       e ON e.idEstado       = p.idEstado
                 LEFT JOIN municipio    m ON m.idMunicipio    = p.idMunicipio
                 LEFT JOIN departamento d ON d.idDepartamento = m.idDepartamento
                 WHERE p.idProducto = ?");
        } else {
            $stmt = mysqli_prepare($con,
                "SELECT p.idProducto, p.nombreProducto, p.precio, p.descripcion,
                        e.nombreEstado,
                        NULL AS idMunicipio, NULL AS idDepartamento,
                        COALESCE(p.ubicacion, '') AS ubicacion
                 FROM producto p
                 LEFT JOIN estado e ON e.idEstado = p.idEstado
                 WHERE p.idProducto = ?");
        }
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        return mysqli_fetch_assoc(mysqli_stmt_get_result($stmt)) ?: null;
    }

    public static function municipioExiste($con): bool {
        $row = mysqli_fetch_assoc(mysqli_query($con,
            "SELECT COUNT(*) AS c FROM INFORMATION_SCHEMA.TABLES
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'municipio'"));
        return (int)($row['c'] ?? 0) > 0;
    }

    // Resuelve la ruta fisica de una imagen a partir de su URL o ruta relativa.
    public static function rutaFisica(string $ruta, string $raiz): string {
        if (filter_var($ruta, FILTER_VALIDATE_URL)) {
            $path = parse_url($ruta, PHP_URL_PATH);
            $path = preg_replace('#^/[^/]+/#', '/', $path);
            return $raiz . ltrim($path, '/');
        }
        $ruta = str_replace('/compraylisto/', '/', $ruta);
        return $raiz . ltrim($ruta, '/');
    }
}
