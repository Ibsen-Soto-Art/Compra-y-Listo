<?php
// ══════════════════════════════════════════════════════════════
//  config.php — Configuracion central de Compra y Listo
//
//  Lee el .env de la raíz sin dependencias externas.
// ══════════════════════════════════════════════════════════════

$envFile = __DIR__ . '/../.env';
if (!file_exists($envFile)) {
    die('Archivo .env no encontrado. Copia .env.example a .env y completa los valores.');
}

foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
    $line = trim($line);
    if ($line === '' || $line[0] === '#') continue;
    if (!str_contains($line, '=')) continue;
    [$key, $val] = explode('=', $line, 2);
    $key = trim($key);
    $val = trim($val);
    if (strlen($val) >= 2 && (
        ($val[0] === '"' && $val[-1] === '"') ||
        ($val[0] === "'" && $val[-1] === "'")
    )) {
        $val = substr($val, 1, -1);
    }
    if (!isset($_ENV[$key])) {
        $_ENV[$key]    = $val;
        $_SERVER[$key] = $val;
        putenv("$key=$val");
    }
}

foreach (['APP_ENV', 'SITE_URL', 'DB_HOST', 'DB_USER', 'DB_NAME'] as $req) {
    if (empty($_ENV[$req])) die("Variable requerida '$req' no definida en .env");
}

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
