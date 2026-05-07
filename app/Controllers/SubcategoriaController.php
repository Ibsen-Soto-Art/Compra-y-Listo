<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\SubcategoriaModel;

class SubcategoriaController extends Controller {

    public function index(): void {
        $this->requireAuth();
        require ROOT_PATH . '/app/Views/subcategorias/index.php';
    }

    // GET /api/subcategorias/stats
    public function stats(): void {
        $this->json(SubcategoriaModel::getStats($this->db()));
    }

    // GET /api/subcategorias/por-categoria?idCategoria=N
    public function porCategoria(): void {
        $this->requireAuthJson();
        $idCategoria = (int)($_GET['idCategoria'] ?? 0);
        if ($idCategoria <= 0) { $this->json([]); }
        $this->json(SubcategoriaModel::getPorCategoria($this->db(), $idCategoria));
    }

    // POST /api/subcategorias/agregar
    public function agregar(): void {
        $this->requireAuthJson();
        $con       = $this->db();
        $nombre    = trim($_POST['nombreSubcategoria'] ?? '');
        $idCat     = (int)($_POST['idCategoria']       ?? 0);
        $estado    = ($_POST['estadoSubcategoria'] ?? '') === 'Oculto' ? 'Oculto' : 'Activo';
        $imagenUrl = trim($_POST['imagenUrl'] ?? '') ?: null;

        if (!$nombre || $idCat <= 0) {
            $this->json(['status' => 'error', 'message' => 'Datos incompletos'], 422);
        }
        if (SubcategoriaModel::nombreExiste($con, $nombre, $idCat)) {
            $this->json(['status' => 'error', 'message' => 'Ya existe una subcategoria con ese nombre en esta categoria'], 409);
        }

        $ok = SubcategoriaModel::insertar($con, $nombre, $idCat, $estado, $imagenUrl);
        $this->json($ok
            ? ['status' => 'success', 'message' => 'Subcategoria agregada correctamente']
            : ['status' => 'error',   'message' => 'Error al guardar'], $ok ? 200 : 500);
    }

    // POST /api/subcategorias/editar
    public function editar(): void {
        $this->requireAuthJson();
        $con       = $this->db();
        $id        = (int)($_POST['id']          ?? 0);
        $nombre    = trim($_POST['nombre']        ?? '');
        $idCat     = (int)($_POST['idCategoria']  ?? 0);
        $estado    = ($_POST['estado'] ?? '') === 'Oculto' ? 'Oculto' : 'Activo';
        $imagenUrl = trim($_POST['imagenUrl'] ?? '') ?: null;

        if ($id <= 0 || !$nombre || $idCat <= 0) {
            $this->json(['status' => 'error', 'message' => 'Datos incompletos'], 422);
        }
        if (SubcategoriaModel::nombreExiste($con, $nombre, $idCat, $id)) {
            $this->json(['status' => 'error', 'message' => 'Ya existe una subcategoria con ese nombre en esta categoria'], 409);
        }

        $ok = SubcategoriaModel::actualizar($con, $id, $nombre, $idCat, $estado, $imagenUrl);
        $this->json($ok
            ? ['status' => 'success', 'message' => 'Subcategoria actualizada correctamente']
            : ['status' => 'error',   'message' => 'Error al actualizar'], $ok ? 200 : 500);
    }

    // GET /api/subcategorias/eliminar?id=N
    public function eliminar(): void {
        $this->requireAuthJson();
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) { $this->json(['status' => 'error', 'message' => 'ID invalido'], 422); }

        $ok = SubcategoriaModel::eliminar($this->db(), $id);
        $this->json($ok
            ? ['status' => 'success', 'message' => 'Subcategoria eliminada']
            : ['status' => 'error',   'message' => 'Error al eliminar'], $ok ? 200 : 500);
    }

    // POST /api/subcategorias/eliminar-varias
    public function eliminarVarias(): void {
        $this->requireAuthJson();
        $ids = array_values(array_filter(array_map('intval', $_POST['ids'] ?? []), fn($v) => $v > 0));
        if (empty($ids)) { $this->json(['status' => 'error', 'message' => 'No se enviaron IDs'], 422); }

        $eliminados = SubcategoriaModel::eliminarVarias($this->db(), $ids);
        $this->json(['status' => 'success', 'eliminados' => $eliminados]);
    }

    // POST /api/subcategorias/toggle-estado  (body JSON)
    public function toggleEstado(): void {
        $this->requireAuthJson();
        $con  = $this->db();
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $id   = (int)($data['id'] ?? 0);

        if ($id <= 0) { $this->json(['status' => 'error', 'message' => 'ID invalido'], 422); }

        $estadoActual = SubcategoriaModel::obtenerEstado($con, $id);
        if ($estadoActual === null) { $this->json(['status' => 'error', 'message' => 'Subcategoria no encontrada'], 404); }

        $nuevoEstado = $estadoActual === 'Activo' ? 'Oculto' : 'Activo';
        SubcategoriaModel::toggleEstado($con, $id, $nuevoEstado);
        $this->json(['status' => 'success', 'nuevoEstado' => $nuevoEstado]);
    }

    // GET /api/subcategorias/plantilla
    public function plantilla(): void {
        $this->requireAuth();
        require ROOT_PATH . '/modules/Subcategoria/generarPlantillaSubcategorias.php';
    }

    // POST /api/subcategorias/importar
    public function importar(): void {
        $this->requireAuthJson();
        require ROOT_PATH . '/modules/Subcategoria/importarSubcategorias.php';
    }
}
