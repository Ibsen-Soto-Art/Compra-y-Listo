<?php
// ══════════════════════════════════════════════════════════════
//  config.php — Configuración central de Compra y Listo
//
//  EN LOCAL (XAMPP):
//      SITE_URL  = 'http://localhost/compraylisto'
//      DB_USER   = 'root'
//      DB_PASS   = ''
//
//  EN PRODUCCIÓN (Ferozo):
//      SITE_URL  = 'https://tudominio.com'   ← sin barra al final
//      DB_USER   = el usuario que creas en cPanel
//      DB_PASS   = la contraseña que pusiste en cPanel
//      DB_NAME   = el nombre de la BD que creas en cPanel
// ══════════════════════════════════════════════════════════════

// ── Detectar entorno automáticamente ─────────────────────────
$esLocal = (
    $_SERVER['SERVER_NAME'] === 'localhost' ||
    str_starts_with($_SERVER['SERVER_ADDR'] ?? '', '127.') ||
    str_starts_with($_SERVER['SERVER_ADDR'] ?? '', '192.168.')
);

if ($esLocal) {

    // ─────────────── ENTORNO LOCAL ────────────────────────────
    define('ENTORNO',  'local');
    define('SITE_URL', 'http://localhost/compraylisto');  // ← URL local correcta
    define('DB_HOST',  'localhost');
    define('DB_USER',  'root');
    define('DB_PASS',  '');
    define('DB_NAME',  'compraylisto');

} else {

    // ─────────────── ENTORNO PRODUCCIÓN (Ferozo) ─────────────
    define('ENTORNO',  'produccion');
    define('SITE_URL', 'https://compraylisto.co');
    define('DB_HOST',  'localhost');
    define('DB_USER',  'c2742026_compra');
    define('DB_PASS',  'WO92kufote');
    define('DB_NAME',  'c2742026_compra');

}

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
