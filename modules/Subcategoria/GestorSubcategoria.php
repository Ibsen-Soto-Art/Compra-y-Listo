<?php
define('ROOT_PATH', realpath(__DIR__ . '/../../'));
require ROOT_PATH . '/config/config.php';
header('Location: ' . SITE_URL . '/admin/subcategorias');
exit;