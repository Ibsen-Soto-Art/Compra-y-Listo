<?php
require_once __DIR__ . '/config.php';

// ── Credenciales SMTP ─────────────────────────────────────────
// Las credenciales se definen aquí una sola vez.
// En producción, asegúrate de que este archivo NO sea accesible
// desde el navegador (el .htaccess raíz ya lo bloquea).

define('MAIL_HOST',    'smtp.gmail.com');
define('MAIL_PORT',    587);
define('MAIL_USUARIO', 'compraylisto24@gmail.com');
define('MAIL_PASS',    'oxdo vdfq juzi fooc');   // ← contraseña de aplicación Gmail
define('MAIL_NOMBRE',  'Compra y Listo');
define('MAIL_FROM',    'compraylisto24@gmail.com');
