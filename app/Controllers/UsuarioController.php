<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\UsuarioModel;

class UsuarioController extends Controller {

    public function index(): void {
        $this->requireAuth();
        require ROOT_PATH . '/app/Views/usuarios/index.php';
    }

    // POST /api/usuarios/agregar
    public function agregar(): void {
        $this->requireAuthJson();
        $con      = $this->db();
        $idSesion = (int)($_SESSION['idUsuario'] ?? 0);

        if (!$idSesion || UsuarioModel::obtenerRol($con, $idSesion) !== 'admin') {
            $this->json(['status' => 'error', 'message' => 'No tienes permisos para agregar usuarios'], 403);
        }

        $nombre = trim($_POST['nombre']     ?? '');
        $correo = trim($_POST['correo']     ?? '');
        $pass   = trim($_POST['contraseña'] ?? '');
        $rol    = trim($_POST['rol']        ?? '');

        if (!$nombre || !$correo || !$pass || !$rol) {
            $this->json(['status' => 'error', 'message' => 'Todos los campos son obligatorios'], 422);
        }
        if (!in_array($rol, ['admin', 'gestor'], true)) {
            $this->json(['status' => 'error', 'message' => 'Rol no valido'], 422);
        }
        if (UsuarioModel::correoExiste($con, $correo)) {
            $this->json(['status' => 'error', 'message' => 'El correo ya esta registrado'], 409);
        }

        $ok = UsuarioModel::crear($con, $nombre, $correo, $pass, $rol);
        $this->json($ok
            ? ['status' => 'success', 'message' => 'Usuario agregado correctamente']
            : ['status' => 'error',   'message' => 'Error al registrar usuario'], $ok ? 200 : 500);
    }

    // POST /api/usuarios/editar
    public function editar(): void {
        $this->requireAuthJson();
        $con       = $this->db();
        $rolSesion = $_SESSION['rol']         ?? null;
        $idLogeado = (int)($_SESSION['idUsuario'] ?? 0);

        $id       = (int)trim($_POST['id']         ?? 0);
        $nombre   = trim($_POST['nombre']           ?? '');
        $correo   = trim($_POST['correo']           ?? '');
        $pass     = trim($_POST['contraseña']       ?? '');
        $rolNuevo = isset($_POST['rol']) ? trim($_POST['rol']) : null;

        if (!$id || !$nombre || !$correo || !$pass) {
            $this->json(['status' => 'error', 'message' => 'Campos obligatorios incompletos'], 422);
        }
        if (UsuarioModel::correoExiste($con, $correo, $id)) {
            $this->json(['status' => 'error', 'message' => 'El correo ya esta registrado en otro usuario'], 409);
        }

        $rolActual = UsuarioModel::obtenerRol($con, $id);
        if ($rolSesion === 'gestor' || ($rolSesion === 'admin' && $idLogeado === $id) || $rolNuevo === null) {
            $rolNuevo = $rolActual;
        }

        $ok = UsuarioModel::actualizar($con, $id, $nombre, $correo, $pass, $rolNuevo);
        $this->json($ok
            ? ['status' => 'success', 'message' => 'Usuario actualizado correctamente']
            : ['status' => 'error',   'message' => 'Error al actualizar usuario'], $ok ? 200 : 500);
    }

    // GET /api/usuarios/eliminar?id=N
    public function eliminar(): void {
        $this->requireAuth();
        $con        = $this->db();
        $idLogeado  = (int)($_SESSION['idUsuario'] ?? 0);
        $idEliminar = (int)($_GET['id']            ?? 0);

        if (!$idLogeado || UsuarioModel::obtenerRol($con, $idLogeado) !== 'admin') {
            $this->redirect(SITE_URL . '/admin/usuarios');
        }
        if (!$idEliminar) { $this->redirect(SITE_URL . '/admin/usuarios'); }
        if ($idEliminar === $idLogeado) { $this->redirect(SITE_URL . '/admin/usuarios'); }

        UsuarioModel::eliminar($con, $idEliminar);
        $this->redirect(SITE_URL . '/admin/usuarios');
    }
}
