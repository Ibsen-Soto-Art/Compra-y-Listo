<?php

namespace App\Core;

class Controller {

    private $con = null;

    // Retorna la conexión mysqli, creándola lazy la primera vez.
    protected function db() {
        if ($this->con === null) {
            require_once ROOT_PATH . '/config/conection.php';
            $this->con = conection();
        }
        return $this->con;
    }

    // Renderiza una vista pasándole variables.
    // $view: ruta relativa a app/Views/, ej: 'publico/catalogo'
    protected function render(string $view, array $data = []): void {
        extract($data, EXTR_SKIP);
        $viewFile = APP_PATH . '/Views/' . ltrim($view, '/') . '.php';
        if (!file_exists($viewFile)) {
            http_response_code(500);
            echo "Vista no encontrada: $view";
            return;
        }
        require $viewFile;
    }

    // Responde JSON y termina la ejecucion.
    protected function json($data, int $status = 200): void {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data);
        exit;
    }

    // Redirige a otra URL.
    protected function redirect(string $url): void {
        header("Location: $url");
        exit;
    }

    // Verifica sesion de usuario autenticado; redirige si no está logueado.
    protected function requireAuth(): void {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (!isset($_SESSION['idUsuario'])) {
            $this->redirect(SITE_URL . '/');
        }
    }

    // Verifica sesion y termina con JSON 401 si no está autenticado (para endpoints AJAX).
    protected function requireAuthJson(): void {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (!isset($_SESSION['usuarios'])) {
            $this->json(['ok' => false, 'error' => 'No autorizado'], 401);
        }
        session_write_close();
    }
}
