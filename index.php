<?php
/**
 * Archivo de redirección para el hosting.
 * Este archivo redirige el tráfico de la raíz a la carpeta public/
 */
header("Location: public/index.php");
exit();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cargando Compra y Listo...</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f7f6;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            color: #333;
        }
        .container {
            text-align: center;
            padding: 2rem;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        .btn {
            display: inline-block;
            margin-top: 20px;
            padding: 12px 24px;
            background-color: #2E8B57;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: bold;
            transition: background 0.3s;
        }
        .btn:hover {
            background-color: #246d44;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Bienvenido a Compra y Listo</h2>
        <p>Estamos redirigiéndote al sitio principal...</p>
        <a href="public/index.php" class="btn">Entrar al Sitio</a>
    </div>
</body>
</html>
