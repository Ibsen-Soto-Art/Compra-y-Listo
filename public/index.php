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
use App\Controllers\CategoriaController;
use App\Controllers\SubcategoriaController;
use App\Controllers\UsuarioController;
use App\Controllers\InventarioController;
use App\Controllers\ConfiguracionController;

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

// Gestor de categorías (vista + API)
$router->get('/admin/categorias',                  [CategoriaController::class, 'index']);
$router->get ('/api/categorias/stats',             [CategoriaController::class, 'stats']);
$router->post('/api/categorias/insertar',          [CategoriaController::class, 'insertar']);
$router->post('/api/categorias/editar',            [CategoriaController::class, 'editar']);
$router->get ('/api/categorias/eliminar',          [CategoriaController::class, 'eliminar']);
$router->post('/api/categorias/eliminar-varias',   [CategoriaController::class, 'eliminarVarias']);
$router->post('/api/categorias/mover-eliminar',    [CategoriaController::class, 'moverYEliminar']);
$router->post('/api/categorias/toggle-estado',     [CategoriaController::class, 'toggleEstado']);
$router->get ('/api/categorias/plantilla',         [CategoriaController::class, 'plantilla']);
$router->post('/api/categorias/importar',          [CategoriaController::class, 'importar']);

// Gestor de subcategorías (vista + API)
$router->get('/admin/subcategorias',                    [SubcategoriaController::class, 'index']);
$router->get ('/api/subcategorias/stats',               [SubcategoriaController::class, 'stats']);
$router->get ('/api/subcategorias/por-categoria',       [SubcategoriaController::class, 'porCategoria']);
$router->post('/api/subcategorias/agregar',             [SubcategoriaController::class, 'agregar']);
$router->post('/api/subcategorias/editar',              [SubcategoriaController::class, 'editar']);
$router->get ('/api/subcategorias/eliminar',            [SubcategoriaController::class, 'eliminar']);
$router->post('/api/subcategorias/eliminar-varias',     [SubcategoriaController::class, 'eliminarVarias']);
$router->post('/api/subcategorias/toggle-estado',       [SubcategoriaController::class, 'toggleEstado']);
$router->get ('/api/subcategorias/plantilla',           [SubcategoriaController::class, 'plantilla']);
$router->post('/api/subcategorias/importar',            [SubcategoriaController::class, 'importar']);

// Gestor de usuarios (vista + API)
$router->get('/admin/usuarios',           [UsuarioController::class, 'index']);
$router->post('/api/usuarios/agregar',    [UsuarioController::class, 'agregar']);
$router->post('/api/usuarios/editar',     [UsuarioController::class, 'editar']);
$router->get ('/api/usuarios/eliminar',   [UsuarioController::class, 'eliminar']);

// Gestor de inventario (vista + API)
$router->get('/admin/inventario',                    [InventarioController::class, 'index']);
$router->get ('/api/inventario/items',               [InventarioController::class, 'getItems']);
$router->get ('/api/inventario/info',                [InventarioController::class, 'getInfo']);
$router->post('/api/inventario/guardar',             [InventarioController::class, 'guardar']);
$router->post('/api/inventario/eliminar',            [InventarioController::class, 'eliminar']);
$router->post('/api/inventario/eliminar-varios',     [InventarioController::class, 'eliminarVarios']);
$router->post('/api/inventario/agregar-masivo',      [InventarioController::class, 'agregarMasivo']);
$router->post('/api/inventario/cambiar-estado',      [InventarioController::class, 'cambiarEstado']);

// Configuracion
$router->post('/api/configuracion/guardar', [ConfiguracionController::class, 'guardar']);

// ── Despachar ──────────────────────────────────────────────────
$router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $path);
