<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\UbicacionModel;

class UbicacionController extends Controller {

    // GET /api/ubicacion/departamentos
    public function departamentos(): void {
        $this->json(UbicacionModel::getDepartamentos($this->db()));
    }

    // GET /api/ubicacion/municipios?idDepartamento=N
    public function municipios(): void {
        $idDepartamento = (int)($_GET['idDepartamento'] ?? 0);
        if (!$idDepartamento) { $this->json([]); }
        $this->json(UbicacionModel::getMunicipios($this->db(), $idDepartamento));
    }
}
