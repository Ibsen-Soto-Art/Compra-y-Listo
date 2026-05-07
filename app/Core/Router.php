<?php

namespace App\Core;

class Router {

    private array $routes = [];

    // Registra una ruta GET
    public function get(string $path, array $handler): void {
        $this->routes['GET'][$path] = $handler;
    }

    // Registra una ruta POST
    public function post(string $path, array $handler): void {
        $this->routes['POST'][$path] = $handler;
    }

    // Despacha la peticion actual al controller correcto.
    // $uri ya viene normalizado (sin base path, sin query string).
    public function dispatch(string $method, string $uri): void {
        $handler = $this->routes[$method][$uri] ?? null;

        // Intentar con slash final omitido/agregado
        if (!$handler) {
            $alt     = rtrim($uri, '/') ?: '/';
            $handler = $this->routes[$method][$alt] ?? null;
        }

        if (!$handler) {
            http_response_code(404);
            if (file_exists(APP_PATH . '/Views/errors/404.php')) {
                require APP_PATH . '/Views/errors/404.php';
            } else {
                echo '404 — Página no encontrada';
            }
            return;
        }

        [$class, $action] = $handler;
        (new $class())->$action();
    }
}
