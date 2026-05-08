<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="<?= SITE_URL ?>/assets/imagenes/partelogo.png">
    <title>404 — Página no encontrada</title>
    <style>
        body { font-family: sans-serif; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; background: #f5f5f5; }
        .box { text-align: center; }
        h1 { font-size: 5rem; margin: 0; color: #333; }
        p  { color: #666; margin: .5rem 0 2rem; }
        a  { color: #0d6efd; text-decoration: none; }
    </style>
</head>
<body>
    <div class="box">
        <h1>404</h1>
        <p>La página que buscas no existe.</p>
        <a href="<?= defined('SITE_URL') ? SITE_URL : '/' ?>/">Volver al inicio</a>
    </div>
</body>
</html>
