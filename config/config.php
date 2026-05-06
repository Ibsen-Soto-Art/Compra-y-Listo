<?php
// ══════════════════════════════════════════════════════════════
//  config.php — Configuracion central de Compra y Listo
//
//  Las credenciales se leen del archivo .env en la raiz del proyecto.
//  Copia .env.example a .env y completa los valores antes de usar.
// ══════════════════════════════════════════════════════════════

require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

$dotenv->required([
    'APP_ENV', 'SITE_URL',
    'DB_HOST', 'DB_USER', 'DB_NAME',
]);

define('ENTORNO',  $_ENV['APP_ENV']);
define('SITE_URL', rtrim($_ENV['SITE_URL'], '/'));
define('DB_HOST',  $_ENV['DB_HOST']);
define('DB_USER',  $_ENV['DB_USER']);
define('DB_PASS',  $_ENV['DB_PASS'] ?? '');
define('DB_NAME',  $_ENV['DB_NAME']);

// ── Mostrar errores solo en local (nunca en endpoints AJAX/JSON) ──
$esAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) ||
          str_contains($_SERVER['REQUEST_URI'] ?? '', 'obtener') ||
          str_contains($_SERVER['REQUEST_URI'] ?? '', 'getItem') ||
          str_contains($_SERVER['REQUEST_URI'] ?? '', 'Ajax') ||
          str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');

if (ENTORNO === 'local' && !$esAjax) {
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    error_reporting(0);
    ini_set('log_errors', 1);
}
