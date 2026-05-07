<?php
// MIGRADO → ProductoController::criar()
// Este stub redirige al front controller para mantener compatibilidad.
define('ROOT_PATH', realpath(__DIR__ . '/../../'));
define('APP_PATH',  ROOT_PATH . '/app');
require ROOT_PATH . '/config/config.php';
require ROOT_PATH . '/vendor/autoload.php';
$_SERVER['REQUEST_URI'] = rtrim(parse_url(SITE_URL, PHP_URL_PATH), '/') . '/api/productos/crear';
require ROOT_PATH . '/public/index.php';
