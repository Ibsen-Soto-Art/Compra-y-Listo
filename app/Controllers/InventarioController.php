<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\InventarioModel;

class InventarioController extends Controller {

    public function index(): void {
        $this->requireAuth();
        require ROOT_PATH . '/app/Views/inventario/index.php';
    }

    // GET /api/inventario/items?idProducto=N
    public function getItems(): void {
        $this->requireAuthJson();
        $idProducto = (int)($_GET['idProducto'] ?? 0);
        $this->json(InventarioModel::getItems($this->db(), $idProducto));
    }

    // GET /api/inventario/info?idProducto=N
    public function getInfo(): void {
        $this->requireAuthJson();
        $con        = $this->db();
        $idProducto = (int)($_GET['idProducto'] ?? 0);
        if (!$idProducto) { $this->json(['error' => 'ID invalido'], 422); }

        $rows          = InventarioModel::getInfoProducto($con, $idProducto);
        $categoria     = '';
        $subcategorias = [];
        foreach ($rows as $r) {
            if (!$categoria) $categoria = $r['nombreCategoria'];
            $subcategorias[] = $r['nombreSubcategoria'];
        }

        $prefix = '';
        if ($categoria) $prefix .= strtoupper(mb_substr($categoria, 0, 1));
        foreach ($subcategorias as $sub) $prefix .= strtoupper(mb_substr($sub, 0, 1));
        $prefix .= '-';

        $nextNum = InventarioModel::getMaxNumSerie($con, $prefix) + 1;

        $this->json([
            'prefix'        => $prefix,
            'nextNum'       => $nextNum,
            'categoria'     => $categoria,
            'subcategorias' => $subcategorias,
        ]);
    }

    // POST /api/inventario/guardar
    public function guardar(): void {
        $this->requireAuthJson();
        $con        = $this->db();
        $idProducto = (int)($_POST['idProducto'] ?? 0);
        $idItem     = (int)($_POST['idItem']     ?? 0);
        $serie      = trim($_POST['numeroSerie'] ?? '');
        $estado     = in_array($_POST['estadoItem'] ?? '', ['Disponible', 'Vendido'])
            ? $_POST['estadoItem'] : 'Disponible';

        if (!$idProducto || !$serie) {
            $this->json(['status' => 'error', 'message' => 'Datos incompletos'], 422);
        }

        if ($idItem > 0) {
            if (InventarioModel::serieEnUso($con, $serie, $idItem)) {
                $this->json(['status' => 'error', 'message' => 'Ese numero de serie ya esta en uso por otra unidad'], 409);
            }
            $ok = InventarioModel::actualizar($con, $idItem, $idProducto, $serie, $estado);
        } else {
            if (InventarioModel::serieEnUso($con, $serie)) {
                $this->json(['status' => 'error', 'message' => 'Ese numero de serie ya existe en el inventario'], 409);
            }
            $ok = InventarioModel::insertar($con, $idProducto, $serie, $estado);
        }

        $this->json($ok
            ? ['status' => 'success']
            : ['status' => 'error', 'message' => 'Error en base de datos'], $ok ? 200 : 500);
    }

    // POST /api/inventario/eliminar
    public function eliminar(): void {
        $this->requireAuthJson();
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) { $this->json(['status' => 'error', 'message' => 'ID invalido'], 422); }

        $ok = InventarioModel::eliminar($this->db(), $id);
        $this->json($ok
            ? ['status' => 'success']
            : ['status' => 'error', 'message' => 'Error al eliminar'], $ok ? 200 : 500);
    }

    // POST /api/inventario/eliminar-varios
    public function eliminarVarios(): void {
        $this->requireAuthJson();
        $ids = array_values(array_filter(array_map('intval', $_POST['ids'] ?? []), fn($v) => $v > 0));
        if (empty($ids)) { $this->json(['status' => 'error', 'message' => 'No se recibieron IDs'], 422); }

        $eliminados = InventarioModel::eliminarVarios($this->db(), $ids);
        $this->json(['status' => 'success', 'eliminados' => $eliminados]);
    }

    // POST /api/inventario/agregar-masivo
    public function agregarMasivo(): void {
        $this->requireAuthJson();
        $con        = $this->db();
        $idProducto = (int)($_POST['idProducto'] ?? 0);
        $cantidad   = (int)($_POST['cantidad']   ?? 0);
        $estado     = in_array($_POST['estadoItem'] ?? '', ['Disponible', 'Vendido'])
            ? $_POST['estadoItem'] : 'Disponible';

        if (!$idProducto || $cantidad < 1) {
            $this->json(['status' => 'error', 'message' => 'Datos invalidos'], 422);
        }

        $rows      = InventarioModel::getInfoProducto($con, $idProducto);
        $categoria = '';
        $subcats   = [];
        foreach ($rows as $r) {
            if (!$categoria) $categoria = $r['nombreCategoria'];
            $subcats[] = $r['nombreSubcategoria'];
        }

        $prefix = '';
        if ($categoria) $prefix .= strtoupper(mb_substr($categoria, 0, 1));
        foreach ($subcats as $sub) $prefix .= strtoupper(mb_substr($sub, 0, 1));
        $prefix .= '-P' . $idProducto . '-';

        $maxNum     = InventarioModel::getMaxNumSerie($con, $prefix);
        $insertados = 0;
        $errores    = [];

        for ($i = 1; $i <= $cantidad; $i++) {
            $serie = $prefix . str_pad($maxNum + $i, 3, '0', STR_PAD_LEFT);
            if (InventarioModel::serieEnUso($con, $serie)) {
                $serie = $prefix . strtoupper(base_convert(mt_rand(100000, 999999), 10, 36));
            }
            if (InventarioModel::insertar($con, $idProducto, $serie, $estado)) {
                $insertados++;
            } else {
                $errores[] = "Fallo al insertar serie $serie";
            }
        }

        $this->json([
            'status'     => $insertados > 0 ? 'success' : 'error',
            'insertados' => $insertados,
            'message'    => $insertados > 0
                ? "$insertados item(s) agregado(s) correctamente"
                : 'Error al insertar: ' . implode(', ', $errores),
        ], $insertados > 0 ? 200 : 500);
    }

    // POST /api/inventario/cambiar-estado
    public function cambiarEstado(): void {
        $this->requireAuthJson();
        $ids = array_values(array_filter(array_map('intval', $_POST['ids'] ?? []), fn($v) => $v > 0));
        if (empty($ids)) { $this->json(['status' => 'error', 'message' => 'Sin items'], 422); }

        $estado = in_array($_POST['estadoItem'] ?? '', ['Disponible', 'Vendido'])
            ? $_POST['estadoItem'] : 'Disponible';

        $actualizados = InventarioModel::cambiarEstado($this->db(), $ids, $estado);
        $this->json(['status' => 'success', 'actualizados' => $actualizados]);
    }
}
