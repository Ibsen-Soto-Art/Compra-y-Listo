<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\ConfiguracionModel;
use App\Models\UsuarioModel;

class ConfiguracionController extends Controller {

    // POST /api/configuracion/guardar
    public function guardar(): void {
        $this->requireAuthJson();
        $con      = $this->db();
        $idSesion = (int)($_SESSION['idUsuario'] ?? 0);

        if (!$idSesion || UsuarioModel::obtenerRol($con, $idSesion) !== 'admin') {
            $this->json(['error' => 'No autorizado'], 403);
        }

        $clave = trim($_POST['clave'] ?? '');
        $valor = trim($_POST['valor'] ?? '');

        if (!$clave || !$valor) {
            $this->json(['error' => 'Datos incompletos'], 422);
        }

        $ok = ConfiguracionModel::guardar($con, $clave, $valor);
        $this->json($ok
            ? ['status' => 'success', 'message' => 'Configuracion guardada']
            : ['error' => 'Error al guardar'], $ok ? 200 : 500);
    }
}
