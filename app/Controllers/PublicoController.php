<?php

namespace App\Controllers;

use App\Core\Controller;

class PublicoController extends Controller {

    public function index(): void {
        // La vista sigue en public/ para que sus rutas relativas de assets funcionen sin cambios.
        require ROOT_PATH . '/public/catalogo.php';
    }

    public function favicon(): void {
        http_response_code(204);
        exit;
    }

    public function obtenerProducto(): void {
        require ROOT_PATH . '/public/obtenerProductoPublico.php';
    }
}
