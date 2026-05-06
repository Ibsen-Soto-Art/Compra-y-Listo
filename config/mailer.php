<?php
require_once __DIR__ . '/config.php';

// Las credenciales SMTP se leen del .env
define('MAIL_HOST',    $_ENV['MAIL_HOST']   ?? 'smtp.gmail.com');
define('MAIL_PORT',    (int)($_ENV['MAIL_PORT'] ?? 587));
define('MAIL_USUARIO', $_ENV['MAIL_USER']   ?? '');
define('MAIL_PASS',    $_ENV['MAIL_PASS']   ?? '');
define('MAIL_NOMBRE',  $_ENV['MAIL_NOMBRE'] ?? 'Compra y Listo');
define('MAIL_FROM',    $_ENV['MAIL_FROM']   ?? '');
