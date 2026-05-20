<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\ProductoModel;
use App\Helpers\ImagenHelper;

class ProductoController extends Controller {

    // ── Vista principal del gestor de productos ─────────────────
    public function index(): void {
        $this->requireAuth();
        require ROOT_PATH . '/app/Views/productos/index.php';
    }

    // ── GET /api/productos/obtener?id=N ─────────────────────────
    public function obtener(): void {
        ob_clean();
        $con = $this->db();
        $id  = (int)($_GET['id'] ?? 0);
        if (!$id) { $this->json(['error' => 'ID no recibido'], 400); }

        $producto = ProductoModel::obtener($con, $id);
        if (!$producto) { $this->json(['error' => 'Producto no encontrado'], 404); }

        $producto['imagenes'] = ProductoModel::getImagenes($con, $id);
        $this->json($producto);
    }

    // ── GET /api/productos/obtener-completo?id=N ────────────────
    public function obtenerCompleto(): void {
        ob_clean();
        $con = $this->db();
        $id  = (int)($_GET['id'] ?? 0);
        if (!$id) { $this->json(['error' => 'ID no recibido'], 400); }

        $conMunicipio = ProductoModel::municipioExiste($con);
        $producto     = ProductoModel::obtenerCompleto($con, $id, $conMunicipio);
        if (!$producto) { $this->json(['error' => 'Producto no encontrado'], 404); }

        $imagenes = array_map(fn($img) => [
            'idImagen' => $img['idImagen'],
            'ruta'     => $img['rutaImagen'],
            'orden'    => $img['orden'],
        ], ProductoModel::getImagenes($con, $id));

        $this->json([
            'idProducto'     => $producto['idProducto'],
            'nombre'         => $producto['nombreProducto'],
            'precio'         => (float)$producto['precio'],
            'ubicacion'      => $producto['ubicacion'],
            'idMunicipio'    => $producto['idMunicipio']    ? (int)$producto['idMunicipio']    : null,
            'idDepartamento' => $producto['idDepartamento'] ? (int)$producto['idDepartamento'] : null,
            'descripcion'    => $producto['descripcion'],
            'estado'         => $producto['nombreEstado'],
            'imagenes'       => $imagenes,
        ]);
    }

    // ── POST /api/productos/crear ───────────────────────────────
    public function crear(): void {
        $this->requireAuthJson();
        $con = $this->db();

        $idUsuario   = (int)$_SESSION['idUsuario'];
        $nombre      = trim($_POST['nombre']       ?? '');
        $precio      = (float)($_POST['precio']    ?? 0);
        $idMunicipio = (int)($_POST['idMunicipio'] ?? 0);
        $descripcion = trim($_POST['descripcion']  ?? '');
        $idCategoria = (int)($_POST['idCategoria'] ?? 0);
        $subcats     = array_values(array_filter(array_map('intval', $_POST['subcategorias'] ?? []), fn($v) => $v > 0));
        $enOferta    = isset($_POST['enOferta']) ? 1 : 0;
        $descuento   = $enOferta ? min(99, max(0, (float)($_POST['descuento'] ?? 0))) : 0;

        if (!$nombre || !$precio || !$idCategoria) {
            $this->json(['ok' => false, 'error' => 'Datos incompletos'], 422);
        }
        if ($precio > 999999999) {
            $this->json(['ok' => false, 'error' => 'El precio excede el máximo permitido (999,999,999)'], 422);
        }

        $ubicacion  = $idMunicipio ? ProductoModel::getUbicacion($con, $idMunicipio) : '';
        $idProducto = ProductoModel::insertar($con, [
            'nombre'      => $nombre,
            'idUsuario'   => $idUsuario,
            'idCategoria' => $idCategoria,
            'descripcion' => $descripcion,
            'precio'      => $precio,
            'ubicacion'   => $ubicacion,
            'idMunicipio' => $idMunicipio ?: null,
            'enOferta'    => $enOferta,
            'descuento'   => $descuento,
        ]);

        if (!$idProducto) { $this->json(['ok' => false, 'error' => 'Error al guardar producto'], 500); }

        ProductoModel::insertarSubcategorias($con, $idProducto, $subcats);

        $carpeta = ROOT_PATH . "/uploads/productos/$idProducto/";
        if (!file_exists($carpeta)) mkdir($carpeta, 0777, true);

        // Auto-crear unidades de inventario si se indicó cantidad (máx 1000)
        $cantidad = min((int)($_POST['cantidad'] ?? 0), 1000);
        if ($cantidad > 0) {
            $rCat = mysqli_fetch_assoc(mysqli_query($con,
                "SELECT nombreCategoria FROM categoria WHERE idCategoria = $idCategoria"));
            $catNombre  = $rCat['nombreCategoria'] ?? '';
            $qSub       = mysqli_query($con,
                "SELECT s.nombreSubcategoria FROM productosubcategoria ps
                 INNER JOIN subcategoria s ON s.idSubcategoria = ps.idSubcategoria
                 WHERE ps.idProducto = $idProducto ORDER BY s.nombreSubcategoria");
            $subcatsInv = [];
            while ($r = mysqli_fetch_assoc($qSub)) $subcatsInv[] = $r['nombreSubcategoria'];

            $prefix = $catNombre ? strtoupper(mb_substr($catNombre, 0, 1)) : '';
            foreach ($subcatsInv as $sub) $prefix .= strtoupper(mb_substr($sub, 0, 1));
            $prefix .= '-';

            $stmt = mysqli_prepare($con,
                "INSERT INTO iteminventario (idProducto, numeroSerie, estadoItem) VALUES (?, ?, 'Disponible')");
            for ($i = 1; $i <= $cantidad; $i++) {
                $serie = $prefix . str_pad($i, 3, '0', STR_PAD_LEFT);
                mysqli_stmt_bind_param($stmt, 'is', $idProducto, $serie);
                mysqli_stmt_execute($stmt);
            }
        }

        $this->json(['ok' => true, 'idProducto' => $idProducto]);
    }

    // ── POST /api/productos/actualizar ──────────────────────────
    public function actualizar(): void {
        $this->requireAuthJson();
        $con = $this->db();

        $idProducto      = (int)($_POST['idProducto']  ?? 0);
        if (!$idProducto) { $this->json(['ok' => false, 'error' => 'ID no válido'], 422); }

        $nombre          = trim($_POST['nombre']       ?? '');
        $precio          = (float)($_POST['precio']    ?? 0);
        $idMunicipioPost = $_POST['idMunicipio']       ?? '';
        $descripcion     = trim($_POST['descripcion']  ?? '');
        $idCategoria     = (int)($_POST['idCategoria'] ?? 0);
        $subcats         = array_values(array_filter(array_map('intval', $_POST['subcategorias'] ?? []), fn($v) => $v > 0));
        $enOferta        = isset($_POST['enOferta']) ? 1 : 0;
        $descuento       = $enOferta ? min(99, max(0, (float)($_POST['descuento'] ?? 0))) : 0;

        if (!$nombre || !$precio || !$idCategoria) {
            $this->json(['ok' => false, 'error' => 'Datos incompletos'], 422);
        }
        if ($precio > 999999999) {
            $this->json(['ok' => false, 'error' => 'El precio excede el máximo permitido (999,999,999)'], 422);
        }

        if ($idMunicipioPost !== '' && (int)$idMunicipioPost > 0) {
            $idMunicipio = (int)$idMunicipioPost;
            $ubicacion   = ProductoModel::getUbicacion($con, $idMunicipio);
        } else {
            $actual      = ProductoModel::getUbicacionActual($con, $idProducto);
            $idMunicipio = $actual['idMunicipio'] ?: null;
            $ubicacion   = $actual['ubicacion'] ?? '';
        }

        if (!ProductoModel::actualizar($con, $idProducto, [
            'nombre'      => $nombre,
            'precio'      => $precio,
            'ubicacion'   => $ubicacion,
            'idMunicipio' => $idMunicipio,
            'descripcion' => $descripcion,
            'idCategoria' => $idCategoria,
            'enOferta'    => $enOferta,
            'descuento'   => $descuento,
        ])) { $this->json(['ok' => false, 'error' => 'Error al actualizar producto'], 500); }

        ProductoModel::eliminarSubcategorias($con, $idProducto);
        ProductoModel::insertarSubcategorias($con, $idProducto, $subcats);

        $carpeta = ROOT_PATH . "/uploads/productos/$idProducto/";
        if (!file_exists($carpeta)) mkdir($carpeta, 0777, true);

        foreach ($_POST['ordenExistentes'] ?? [] as $idImagen => $orden) {
            $orden       = (int)$orden;
            $esPrincipal = $orden === 0 ? 1 : 0;
            ProductoModel::actualizarOrdenImagen($con, (int)$idImagen, $orden, $esPrincipal);
        }

        $this->json(['ok' => true, 'idProducto' => $idProducto]);
    }

    // ── POST /api/productos/subir-imagen ────────────────────────
    public function subirImagen(): void {
        $this->requireAuthJson(); // ya llama session_write_close()
        $con = $this->db();

        $idProducto = (int)($_POST['idProducto'] ?? 0);
        $orden      = (int)($_POST['orden']      ?? 0);

        if (!$idProducto) { $this->json(['ok' => false, 'error' => 'ID no válido'], 422); }

        if (!isset($_FILES['imagen']) || $_FILES['imagen']['error'] !== UPLOAD_ERR_OK) {
            $this->json(['ok' => false, 'error' => 'Sin archivo'], 422);
        }

        $carpeta = ROOT_PATH . "/uploads/productos/$idProducto/";
        if (!file_exists($carpeta)) mkdir($carpeta, 0777, true);

        $resultado = ImagenHelper::procesarYGuardar($_FILES['imagen']['tmp_name'], $carpeta);
        if (!$resultado['ok']) { $this->json(['ok' => false, 'error' => $resultado['error']], 422); }

        $rutaBD = rtrim(SITE_URL, '/') . "/uploads/productos/$idProducto/" . $resultado['nombreArchivo'];
        ProductoModel::insertarImagenesMasivo($con, $idProducto, [[
            'ruta'        => $rutaBD,
            'esPrincipal' => $orden === 0 ? 1 : 0,
            'orden'       => $orden,
        ]]);

        $this->json(['ok' => true]);
    }

    // ── POST /api/productos/eliminar-imagen ─────────────────────
    public function eliminarImagen(): void {
        $this->requireAuthJson();
        $con = $this->db();

        $id = (int)($_POST['id'] ?? 0);
        if (!$id) { $this->json(['ok' => false, 'error' => 'ID no válido'], 422); }

        $img = ProductoModel::getImagenPorId($con, $id);
        if ($img) {
            $raiz    = ROOT_PATH . '/';
            $archivo = ProductoModel::rutaFisica($img['rutaImagen'], $raiz);
            if (file_exists($archivo)) unlink($archivo);
        }

        ProductoModel::eliminarImagen($con, $id);
        $this->json(['ok' => true]);
    }

    // ── POST /api/productos/eliminar ────────────────────────────
    public function eliminar(): void {
        $this->requireAuthJson();
        $con  = $this->db();
        $id   = (int)($_POST['id'] ?? 0);
        $raiz = ROOT_PATH . '/';

        foreach (ProductoModel::getImagenes($con, $id) as $img) {
            $archivo = ProductoModel::rutaFisica($img['rutaImagen'], $raiz);
            if (file_exists($archivo)) unlink($archivo);
        }

        $carpeta = $raiz . "uploads/productos/$id/";
        if (is_dir($carpeta) && count(scandir($carpeta)) === 2) rmdir($carpeta);

        ProductoModel::eliminarImagenesPorProducto($con, [$id]);
        $ok = ProductoModel::eliminar($con, $id);
        $this->json($ok ? ['ok' => true] : ['ok' => false, 'error' => 'Error al eliminar'], $ok ? 200 : 500);
    }

    // ── POST /api/productos/eliminar-varios ─────────────────────
    public function eliminarVarios(): void {
        $this->requireAuthJson();
        $con  = $this->db();
        $ids  = array_values(array_filter(array_map('intval', $_POST['ids'] ?? []), fn($id) => $id > 0));
        $raiz = ROOT_PATH . '/';

        if (empty($ids)) { $this->json(['status' => 'error', 'message' => 'No se recibieron IDs'], 422); }

        foreach ($ids as $id) {
            foreach (ProductoModel::getImagenes($con, $id) as $img) {
                $archivo = ProductoModel::rutaFisica($img['rutaImagen'], $raiz);
                if (file_exists($archivo)) unlink($archivo);
            }
            $carpeta = $raiz . "uploads/productos/$id/";
            if (is_dir($carpeta) && count(scandir($carpeta)) === 2) rmdir($carpeta);
        }

        ProductoModel::eliminarImagenesPorProducto($con, $ids);
        $eliminados = ProductoModel::eliminarVarios($con, $ids);

        $this->json([
            'status'     => 'success',
            'eliminados' => $eliminados,
            'message'    => "$eliminados producto(s) eliminado(s)",
        ]);
    }

    // ── GET /api/productos/exportar ─────────────────────────────
    public function exportar(): void {
        $this->requireAuth();
        require ROOT_PATH . '/modules/Productos/exportarExcel.php';
    }

    // ── GET /api/productos/plantilla ────────────────────────────
    public function plantilla(): void {
        $this->requireAuth();
        require ROOT_PATH . '/modules/Productos/generarPlantillaProductos.php';
    }

    // ── POST /api/productos/importar ────────────────────────────
    public function importar(): void {
        $this->requireAuthJson();
        require ROOT_PATH . '/modules/Productos/importarProductos.php';
    }

    // ── GET /api/productos/stock  — stock disponible por producto ─
    public function stock(): void {
        $this->requireAuth();
        $res  = mysqli_query($this->db(),
            "SELECT idProducto, SUM(estadoItem='Disponible') AS disponible
             FROM iteminventario GROUP BY idProducto");
        $data = [];
        while ($r = mysqli_fetch_assoc($res)) {
            $data[(int)$r['idProducto']] = (int)$r['disponible'];
        }
        $this->json($data);
    }
}
