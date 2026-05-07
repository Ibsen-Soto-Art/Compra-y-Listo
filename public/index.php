<?php
// ══════════════════════════════════════════════════════════════
//  Front Controller — único punto de entrada de la aplicación
//  Todas las peticiones de página pasan por aquí.
//  Las rutas API/AJAX de los módulos siguen accesibles directamente.
// ══════════════════════════════════════════════════════════════

define('ROOT_PATH', dirname(__DIR__));
define('APP_PATH',  ROOT_PATH . '/app');

require ROOT_PATH . '/config/config.php';
require ROOT_PATH . '/vendor/autoload.php';

use App\Core\Router;
use App\Controllers\PublicoController;
use App\Controllers\AdminController;
use App\Controllers\ProductoController;

// ── Calcular path relativo (quitar base del SITE_URL) ──────────
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$basePath   = rtrim(parse_url(SITE_URL, PHP_URL_PATH) ?? '', '/');
$path       = '/' . ltrim(substr(
    parse_url($requestUri, PHP_URL_PATH),
    strlen($basePath)
), '/');

// ── Definir rutas ──────────────────────────────────────────────
$router = new Router();

// Páginas públicas
$router->get('/',       [PublicoController::class, 'index']);
$router->get('/index',  [PublicoController::class, 'index']);

// Panel de administración
$router->get('/admin',           [AdminController::class, 'dashboard']);
$router->get('/admin/dashboard', [AdminController::class, 'dashboard']);

// Gestor de productos (vista)
$router->get('/admin/productos', [ProductoController::class, 'index']);

// API productos
$router->get ('/api/productos/obtener',          [ProductoController::class, 'obtener']);
$router->get ('/api/productos/obtener-completo', [ProductoController::class, 'obtenerCompleto']);
$router->post('/api/productos/crear',            [ProductoController::class, 'crear']);
$router->post('/api/productos/actualizar',       [ProductoController::class, 'actualizar']);
$router->post('/api/productos/subir-imagen',     [ProductoController::class, 'subirImagen']);
$router->post('/api/productos/eliminar-imagen',  [ProductoController::class, 'eliminarImagen']);
$router->post('/api/productos/eliminar',         [ProductoController::class, 'eliminar']);
$router->post('/api/productos/eliminar-varios',  [ProductoController::class, 'eliminarVarios']);
$router->get ('/api/productos/exportar',         [ProductoController::class, 'exportar']);
$router->get ('/api/productos/plantilla',        [ProductoController::class, 'plantilla']);
$router->post('/api/productos/importar',         [ProductoController::class, 'importar']);

// ── Despachar ──────────────────────────────────────────────────
$router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $path);
