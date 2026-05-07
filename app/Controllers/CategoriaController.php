<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\CategoriaModel;

class CategoriaController extends Controller {

    public function index(): void {
        $this->requireAuth();
        require ROOT_PATH . '/app/Views/categorias/index.php';
    }

    // GET /api/categorias/stats
    public function stats(): void {
        $this->json(CategoriaModel::getStats($this->db()));
    }

    // POST /api/categorias/insertar
    public function insertar(): void {
        $this->requireAuthJson();
        $con       = $this->db();
        $nombre    = trim($_POST['nombreCategoria'] ?? '');
        $imagen    = trim($_POST['imagenCategoria'] ?? '');
        $idUsuario = (int)($_SESSION['idUsuario']   ?? 0);

        if (!$nombre) { $this->json(['status' => 'error', 'message' => 'El nombre es obligatorio'], 422); }
        if (CategoriaModel::nombreExiste($con, $nombre)) {
            $this->json(['status' => 'error', 'message' => 'Ya existe una categoria con ese nombre'], 409);
        }

        $ok = CategoriaModel::insertar($con, $nombre, $imagen, $idUsuario);
        $this->json($ok
            ? ['status' => 'success', 'message' => 'Categoria registrada correctamente']
            : ['status' => 'error',   'message' => 'Error al registrar la categoria'], $ok ? 200 : 500);
    }

    // POST /api/categorias/editar
    public function editar(): void {
        $this->requireAuthJson();
        $con    = $this->db();
        $id     = (int)($_POST['id']     ?? 0);
        $nombre = trim($_POST['nombre']  ?? '');
        $imagen = trim($_POST['imagen']  ?? '');

        if ($id <= 0 || !$nombre) {
            $this->json(['status' => 'error', 'message' => 'Todos los campos son obligatorios'], 422);
        }
        if (CategoriaModel::nombreExiste($con, $nombre, $id)) {
            $this->json(['status' => 'error', 'message' => 'Ya existe una categoria con ese nombre'], 409);
        }

        $ok = CategoriaModel::actualizar($con, $id, $nombre, $imagen);
        $this->json($ok
            ? ['status' => 'success', 'message' => 'Categoria actualizada correctamente']
            : ['status' => 'error',   'message' => 'Error al actualizar la categoria'], $ok ? 200 : 500);
    }

    // GET /api/categorias/eliminar?idCategoria=N
    public function eliminar(): void {
        $this->requireAuthJson();
        $con = $this->db();
        $id  = (int)($_GET['idCategoria'] ?? 0);

        if ($id <= 0) { $this->json(['status' => 'error', 'message' => 'ID invalido'], 422); }

        $total = CategoriaModel::contarProductos($con, $id);
        if ($total > 0) {
            $this->json([
                'status'      => 'has_products',
                'count'       => $total,
                'idCategoria' => $id,
                'categorias'  => CategoriaModel::listarExcepto($con, $id),
            ]);
        }

        $ok = CategoriaModel::eliminar($con, $id);
        $this->json($ok
            ? ['status' => 'success', 'message' => 'Categoria eliminada']
            : ['status' => 'error',   'message' => 'Error al eliminar'], $ok ? 200 : 500);
    }

    // POST /api/categorias/eliminar-varias
    public function eliminarVarias(): void {
        $this->requireAuthJson();
        $con = $this->db();
        $ids = array_values(array_filter(array_map('intval', $_POST['ids'] ?? []), fn($id) => $id > 0));

        if (empty($ids)) { $this->json(['status' => 'error', 'message' => 'No se recibieron IDs'], 422); }

        $bloqueadas   = [];
        $sinProductos = [];
        foreach ($ids as $id) {
            $total = CategoriaModel::contarProductos($con, $id);
            if ($total > 0) {
                $bloqueadas[] = ['id' => $id, 'nombre' => CategoriaModel::obtenerNombre($con, $id), 'total' => $total];
            } else {
                $sinProductos[] = $id;
            }
        }

        $eliminadas = CategoriaModel::eliminarVarias($con, $sinProductos);
        $this->json([
            'status'     => 'success',
            'eliminadas' => $eliminadas,
            'bloqueadas' => $bloqueadas,
            'message'    => "$eliminadas categoria(s) eliminada(s)"
                . (count($bloqueadas) > 0 ? ', ' . count($bloqueadas) . ' bloqueada(s) por tener productos' : ''),
        ]);
    }

    // POST /api/categorias/mover-eliminar  (body JSON)
    public function moverYEliminar(): void {
        $this->requireAuthJson();
        $con       = $this->db();
        $data      = json_decode(file_get_contents('php://input'), true) ?? [];
        $idOrigen  = (int)($data['idOrigen']  ?? 0);
        $idDestino = (int)($data['idDestino'] ?? 0);

        if ($idOrigen <= 0 || $idDestino <= 0) { $this->json(['status' => 'error', 'message' => 'IDs invalidos'], 422); }
        if ($idOrigen === $idDestino)           { $this->json(['status' => 'error', 'message' => 'Origen y destino no pueden ser iguales'], 422); }
        if (!CategoriaModel::existe($con, $idDestino)) { $this->json(['status' => 'error', 'message' => 'Categoria destino no existe'], 404); }

        $resultado = CategoriaModel::moverYEliminar($con, $idOrigen, $idDestino);
        if (!$resultado['ok']) { $this->json(['status' => 'error', 'message' => $resultado['error']], 500); }

        $this->json([
            'status'  => 'success',
            'movidos' => $resultado['movidos'],
            'message' => $resultado['movidos'] . ' producto(s) movidos y categoria eliminada',
        ]);
    }

    // POST /api/categorias/toggle-estado  (body JSON)
    public function toggleEstado(): void {
        $this->requireAuthJson();
        $con  = $this->db();
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $id   = (int)($data['id'] ?? 0);

        if ($id <= 0) { $this->json(['status' => 'error', 'message' => 'ID invalido'], 422); }

        $estadoActual = CategoriaModel::obtenerEstado($con, $id);
        if ($estadoActual === null) { $this->json(['status' => 'error', 'message' => 'Categoria no encontrada'], 404); }

        $nuevoEstado = $estadoActual === 'Activo' ? 'Oculto' : 'Activo';
        CategoriaModel::toggleEstado($con, $id, $nuevoEstado);
        $this->json(['status' => 'success', 'nuevoEstado' => $nuevoEstado]);
    }

    // GET /api/categorias/plantilla
    public function plantilla(): void {
        $this->requireAuth();
        require ROOT_PATH . '/modules/Categoria/generarPlantillaCategorias.php';
    }

    // POST /api/categorias/importar
    public function importar(): void {
        $this->requireAuthJson();
        require ROOT_PATH . '/modules/Categoria/importarCategorias.php';
    }
}
