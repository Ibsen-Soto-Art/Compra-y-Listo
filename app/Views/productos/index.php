<?php
ob_start();
if (session_status() === PHP_SESSION_NONE) session_start();
    include(ROOT_PATH . "/config/conection.php");
    $con = conection();

    if(!isset($_SESSION['usuarios'])){
        header("location:../auth/login.php");
        exit();
    }

    $wsRow = mysqli_fetch_assoc(mysqli_query($con,
        "SELECT valor FROM configuracion WHERE clave='whatsapp' LIMIT 1"));
    $whatsappNum = $wsRow['valor'] ?? '573123048308';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="Author" content="Ibsen Alexis Soto Artunduaga">
    <meta name="keywords" content="compras, ventas, nuevo, usado">
    <meta name="Description" content="Página web diseñada para facilitar la compra y venta de productos nuevos y usados de manera rápida y sencilla.">
    <link rel="icon" type="image/x-icon" href="<?= SITE_URL ?>/favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= SITE_URL ?>/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="<?= SITE_URL ?>/favicon-16x16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="<?= SITE_URL ?>/assets/imagenes/partelogo.png">
    <title>Compra y Listo</title>
    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
    <link rel="preload" href="<?= SITE_URL ?>/assets/styleAll.min.css" as="style">
    <link rel="preload" href="<?= SITE_URL ?>/assets/mobile-admin.min.css" as="style">
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/styleAll.min.css">
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/mobile-admin.min.css">
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/admin-overrides.css">
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/bootstrap-icons/bootstrap-icons.css" media="print" onload="this.media='all'">
    <noscript><link rel="stylesheet" href="<?= SITE_URL ?>/assets/bootstrap-icons/bootstrap-icons.css"></noscript>
    <style>
        /* ── Inventario en card ─────────────────────────────── */
        .card-inv-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: 6px;
            gap: 6px;
        }
        .card-stock-pill {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 11.5px;
            font-weight: 600;
            padding: 3px 8px;
            border-radius: 20px;
            background: #f0fdf4;
            color: #16a34a;
            border: 1px solid #bbf7d0;
            white-space: nowrap;
        }
        .card-stock-pill.agotado {
            background: #fff7ed;
            color: #c2410c;
            border-color: #fed7aa;
        }
        .card-stock-pill.sin-stock {
            background: #f8fafc;
            color: #94a3b8;
            border-color: #e2e8f0;
        }
        .btn-ver-items-card {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 11.5px;
            font-weight: 600;
            padding: 3px 9px;
            border-radius: 20px;
            background: #eef2ff;
            color: #4f46e5;
            border: 1px solid #c7d2fe;
            cursor: pointer;
            transition: background .15s, color .15s;
            white-space: nowrap;
        }
        .btn-ver-items-card:hover {
            background: #4f46e5;
            color: #fff;
        }
        /* ── Botón items en acciones hover ─────────────────── */
        .btn-items-inv {
            background: #4f46e5 !important;
        }
        .btn-items-inv:hover {
            background: #3730a3 !important;
        }
        /* ── Modal inventario por producto ─────────────────── */
        .modal-inv-contenido {
            background: #fff;
            border-radius: 16px;
            width: 95%;
            max-width: 540px;
            max-height: 90vh;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            box-shadow: 0 20px 60px rgba(0,0,0,.18);
            animation: fadeInUp .22s ease;
        }
        .modal-inv-header {
            padding: 18px 20px 14px;
            border-bottom: 1px solid #f1f5f9;
        }
        .modal-inv-header h3 {
            margin: 0 0 10px;
            font-size: 15px;
            font-weight: 700;
            color: #1e293b;
            padding-right: 30px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .modal-inv-cerrar {
            position: absolute;
            top: 14px;
            right: 14px;
            background: none;
            border: none;
            font-size: 22px;
            cursor: pointer;
            color: #94a3b8;
            line-height: 1;
            padding: 4px;
        }
        .modal-inv-cerrar:hover { color: #1e293b; }
        .modal-inv-stats {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        .inv-stat {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 11.5px;
            font-weight: 600;
            padding: 3px 9px;
            border-radius: 20px;
        }
        .inv-stat-disp  { background:#f0fdf4; color:#16a34a; border:1px solid #bbf7d0; }
        .inv-stat-vend  { background:#fff7ed; color:#c2410c; border:1px solid #fed7aa; }
        .inv-stat-total { background:#f8fafc; color:#475569; border:1px solid #e2e8f0; }
        /* agregar */
        .modal-inv-agregar {
            padding: 12px 20px;
            border-bottom: 1px solid #f1f5f9;
            display: flex;
            gap: 8px;
            align-items: center;
        }
        .modal-inv-agregar input[type=number] {
            width: 80px;
            padding: 8px 10px;
            border: 1.5px solid #e2e8f0;
            border-radius: 8px;
            font-size: 13px;
            outline: none;
            text-align: center;
        }
        .modal-inv-agregar input[type=number]:focus { border-color: #6366f1; }
        .btn-inv-add {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 8px 14px;
            background: #6366f1;
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: background .15s;
        }
        .btn-inv-add:hover { background: #4f46e5; }
        .btn-inv-add:disabled { background: #a5b4fc; cursor: not-allowed; }
        /* barra búsqueda + selección */
        .modal-inv-toolbar {
            padding: 10px 20px 0;
            display: flex;
            gap: 8px;
            align-items: center;
        }
        .inv-search-wrap {
            flex: 1;
            display: flex;
            align-items: center;
            gap: 6px;
            background: #f8fafc;
            border: 1.5px solid #e2e8f0;
            border-radius: 8px;
            padding: 7px 10px;
        }
        .inv-search-wrap i { color: #94a3b8; font-size: 14px; }
        .inv-search-wrap input {
            border: none;
            background: none;
            outline: none;
            font-size: 13px;
            color: #1e293b;
            width: 100%;
        }
        .btn-inv-seleccion {
            display: flex;
            align-items: center;
            gap: 5px;
            padding: 7px 12px;
            background: #f1f5f9;
            border: 1.5px solid #e2e8f0;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
            color: #475569;
            cursor: pointer;
            white-space: nowrap;
            transition: background .15s, color .15s;
        }
        .btn-inv-seleccion.activo {
            background: #4f46e5;
            color: #fff;
            border-color: #4f46e5;
        }
        /* barra acciones bulk */
        .modal-inv-bulk {
            display: none;
            padding: 8px 20px;
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
            gap: 6px;
            flex-wrap: wrap;
            align-items: center;
        }
        .modal-inv-bulk.visible { display: flex; }
        .inv-bulk-info {
            font-size: 12px;
            font-weight: 600;
            color: #475569;
            flex: 1;
            white-space: nowrap;
        }
        .btn-bulk {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 5px 10px;
            border-radius: 6px;
            border: none;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: background .15s;
        }
        .btn-bulk-todo  { background:#e0e7ff; color:#4338ca; }
        .btn-bulk-todo:hover  { background:#c7d2fe; }
        .btn-bulk-ninguno { background:#f1f5f9; color:#475569; }
        .btn-bulk-ninguno:hover { background:#e2e8f0; }
        .btn-bulk-disp  { background:#f0fdf4; color:#16a34a; border:1px solid #bbf7d0; }
        .btn-bulk-disp:hover  { background:#dcfce7; }
        .btn-bulk-vend  { background:#fff7ed; color:#c2410c; border:1px solid #fed7aa; }
        .btn-bulk-vend:hover  { background:#ffedd5; }
        .btn-bulk-del   { background:#fef2f2; color:#dc2626; border:1px solid #fecaca; }
        .btn-bulk-del:hover   { background:#fee2e2; }
        .btn-bulk:disabled { opacity:.5; cursor:not-allowed; }
        /* lista */
        .modal-inv-lista {
            flex: 1;
            overflow-y: auto;
            padding: 8px 20px 16px;
        }
        .modal-inv-lista::-webkit-scrollbar { width: 5px; }
        .modal-inv-lista::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 4px; }
        .inv-item-row {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 9px 10px;
            border-radius: 8px;
            margin-bottom: 5px;
            background: #f8fafc;
            border: 1px solid #f1f5f9;
            font-size: 13px;
            transition: background .12s;
        }
        .inv-item-row.inv-hidden { display: none; }
        .inv-item-row.inv-seleccionado { background:#eef2ff; border-color:#c7d2fe; }
        .inv-item-cb { display:none; accent-color:#6366f1; width:15px; height:15px; cursor:pointer; flex-shrink:0; }
        .modo-seleccion .inv-item-cb { display:block; }
        .inv-item-serie {
            flex: 1;
            font-weight: 600;
            color: #1e293b;
            font-family: monospace;
            font-size: 12.5px;
        }
        .inv-item-badge {
            padding: 2px 9px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
        }
        .badge-disponible-item { background:#f0fdf4; color:#16a34a; border:1px solid #bbf7d0; }
        .badge-vendido-item    { background:#fff7ed; color:#c2410c; border:1px solid #fed7aa; }
        .btn-toggle-estado {
            background: none;
            border: 1.5px solid #e2e8f0;
            color: #64748b;
            cursor: pointer;
            padding: 3px 8px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 600;
            transition: all .15s;
            white-space: nowrap;
        }
        .btn-toggle-estado:hover { background:#f1f5f9; border-color:#cbd5e1; }
        .btn-inv-item-del {
            background: none;
            border: none;
            color: #cbd5e1;
            cursor: pointer;
            padding: 3px 6px;
            border-radius: 5px;
            font-size: 13px;
            transition: color .15s, background .15s;
            flex-shrink: 0;
        }
        .btn-inv-item-del:hover { color: #dc2626; background: #fef2f2; }
        .inv-empty-msg {
            text-align: center;
            color: #94a3b8;
            padding: 30px 0;
            font-size: 13px;
        }
        .inv-empty-msg i { font-size: 28px; display: block; margin-bottom: 8px; }
        /* ── Campo cantidad en formulario ───────────────────── */
        .wrap-cantidad-label {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 4px;
        }
        .wrap-cantidad-label label { margin: 0; }
        /* ── Cantidad field wrapper ─────────────────────────── */
        #wrapCantidad { margin-bottom: 12px; }
        /* ── Toggle oferta ──────────────────────────────────── */
        .oferta-wrap {
            display: flex;
            flex-direction: column;
            gap: 10px;
            padding: 12px 14px;
            background: #fff8f0;
            border: 1.5px solid #fed7aa;
            border-radius: 10px;
            margin-bottom: 12px;
        }
        .oferta-toggle-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .oferta-toggle-label {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13.5px;
            font-weight: 600;
            color: #c2410c;
        }
        .oferta-toggle-label i { font-size: 16px; }
        .toggle-switch {
            position: relative;
            width: 42px;
            height: 24px;
            flex-shrink: 0;
        }
        .toggle-switch input { opacity: 0; width: 0; height: 0; }
        .toggle-slider {
            position: absolute;
            inset: 0;
            background: #e2e8f0;
            border-radius: 24px;
            cursor: pointer;
            transition: background .2s;
        }
        .toggle-slider:before {
            content: "";
            position: absolute;
            width: 18px;
            height: 18px;
            left: 3px;
            top: 3px;
            background: #fff;
            border-radius: 50%;
            transition: transform .2s;
            box-shadow: 0 1px 4px rgba(0,0,0,.2);
        }
        .toggle-switch input:checked + .toggle-slider { background: #f97316; }
        .toggle-switch input:checked + .toggle-slider:before { transform: translateX(18px); }
        .oferta-descuento-row {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .oferta-descuento-row label {
            font-size: 12.5px;
            color: #7c3aed;
            font-weight: 600;
            white-space: nowrap;
        }
        .oferta-descuento-row input {
            width: 75px;
            padding: 6px 10px;
            border: 1.5px solid #e2e8f0;
            border-radius: 8px;
            font-size: 13px;
            outline: none;
            text-align: center;
        }
        .oferta-descuento-row input:focus { border-color: #f97316; }
        .oferta-precio-preview {
            font-size: 12px;
            color: #64748b;
            display: flex;
            align-items: center;
            gap: 6px;
            flex-wrap: wrap;
        }
        .oferta-precio-preview .precio-tachado {
            text-decoration: line-through;
            color: #94a3b8;
        }
        .oferta-precio-preview .precio-final {
            font-weight: 700;
            color: #ea580c;
        }
        /* ── Precio overflow prevention ─────────────────────── */
        .card-precio, .card-precio-tachado, .card-precio-descuento,
        #vistaPrecioFinal, #vistaPrecioOriginal {
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 100%;
            white-space: nowrap;
        }
        /* ── Badge oferta en card (gestor) ──────────────────── */
        .card-oferta-badge {
            position: absolute;
            bottom: 8px;
            left: 8px;
            z-index: 4;
            background: linear-gradient(135deg, #f97316, #ea580c);
            color: #fff;
            font-size: 11px;
            font-weight: 700;
            padding: 3px 8px;
            border-radius: 20px;
            display: flex;
            align-items: center;
            gap: 3px;
            letter-spacing: .3px;
            box-shadow: 0 2px 6px rgba(249,115,22,.4);
        }
    </style>
</head>
<body>
   <div class="contenedor">

        
        <div class="head">

            <!-- Logo que se envuentra en el Head -->
            <div class="imglogo" >

                <a href="<?= SITE_URL ?>/admin" class="imglogo">
                    <img class="imagenlogo" 
                        src="<?= SITE_URL ?>/assets/imagenes/logo.png" 
                        alt="Imagen del logo de la Empresa">
                </a>

                    <?php
                        $nombreUser=$_SESSION['idUsuario'];
                        $sql="SELECT nombreUsuario AS nameuser, rol FROM usuarios WHERE idUsuario=$nombreUser";
                        
                        $query=mysqli_query($con, $sql);
                        $row=mysqli_fetch_assoc($query);

                        $rolUsuario = $row['rol'];
                    ?>

                    <div class="saludo" id="userMenu">
                        <div class="user-info">
                            <div class="user-text">
                                <span class="bienvenido-texto">Bienvenido, <?php if($row['rol'] ==="admin"){
                                    echo'Admin';
                                }else{
                                    echo'Gestor';
                                }; ?>
                                </span>
                                <span class="bienvenido-user">
                                    <?php echo $row['nameuser']?>
                                    <i class="bi bi-caret-down-fill"></i>
                                </span>
                            </div>
                        </div>

                        <div class="dropdown-menu" id="dropdown">
                            <a href="<?= SITE_URL ?>/auth/logout.php">
                                <i class="bi bi-box-arrow-left"></i> Cerrar sesión
                            </a>
                        </div>
                    </div>

            </div>

            <!-- Menu de la pagina -->
         <div class="main" id="menuPrincipal">
                <div class="orgmain">
                    <a href="<?= SITE_URL ?>/admin/usuarios"  class="menu-card">
                        <div class="icon"><i class="bi bi-person-gear"></i></div>
                        <h3>Usuarios</h3>
                        <p>Gestión de usuarios del sistema</p>
                    </a>
                </div>

                <div class="orgmain">
                    <a href="<?= SITE_URL ?>/admin/categorias" class="menu-card">
                        <div class="icon"><i class="bi bi-bookmark-plus"></i></div>
                        <h3>Categorías</h3>
                        <p>Administrar categorías de productos</p>
                    </a>
                </div>

                <div class="orgmain">
                    <a href="<?= SITE_URL ?>/admin/subcategorias" class="menu-card">
                        <div class="icon"><i class="bi bi-diagram-3-fill"></i></div>
                        <h3>Subcategorías</h3>
                        <p>Gestionar subcategorías de productos</p>
                    </a>
                </div>

                <div class="orgmain">
                    <a  class="menu-card">
                        <div class="icon"><i class="bi bi-boxes"></i></div>
                        <h3>Productos</h3>
                        <p>Administrar productos del sistema</p>
                    </a>
                </div>
            
             </div>

            <a href="<?= SITE_URL ?>/auth/logout.php" class="btn-logout-head">
                <i class="bi bi-box-arrow-right"></i>
                <span>Cerrar sesión</span>
            </a>
        </div>
        <script>
            const userMenu = document.getElementById("userMenu");
            const dropdown = document.getElementById("dropdown");

            userMenu.addEventListener("click", () => {
                dropdown.style.display =
                    dropdown.style.display === "block" ? "none" : "block";
            });
            document.addEventListener("click", e => {
                if (!userMenu.contains(e.target)) dropdown.style.display = "none";
            });
        </script>

        <!-- Cuerpo de la pagina -->
         <div class="cuepo">

            <!-- RUTA -->
             
            <div class="ruta">
                <a href="<?= SITE_URL ?>/admin"><i class="bi bi-house-fill"></i> Panel</a>
                <span class="separator"><i class="bi bi-chevron-right"></i></span>
                <span class="actual"><i class="bi bi-boxes"></i> Productos</span>
            </div>


            <div class="primeraPart">
        
                <!-- Nombre del modulo -->
                <div class="rotulaApratado">
                    <h1>Gestión de Productos</h1>
                    <p>Gestionar los Productos del sistema</p>
                </div>
                <?php
                    $busqueda= isset($_GET['busqueda'])? mysqli_real_escape_string($con, trim($_GET['busqueda'])):'';
                    
                ?>
                
                <div class="barraSuperior">

                    <div class="buscarPanel">
                        <form method="GET" onsubmit="return false;">
                            <input
                                type="search"
                                id="inputBusquedaProd"
                                name="busqueda"
                                placeholder="¿Qué estás buscando para tu hogar?"
                                value="<?php echo htmlspecialchars($busqueda); ?>"
                                autocomplete="off"
                            >
                        </form>
                    </div>

                    <button class="btnPanel" id="abrirPanel">
                        <i class="bi bi-list"></i> Acciones
                    </button>

                    <button id="btnAgregarPanel" class="btn-agregar-barra">
                        <i class="bi bi-plus-lg"></i> Agregar Producto
                    </button>

                </div>

                <!-- PANEL LATERAL -->
                <div class="panelLateral" id="panelLateral">

                    <div class="panelHeader">
                        <h3>Acciones</h3>
                        <span id="cerrarPanel">&times;</span>
                    </div>

                    <div class="panelContenido">

                        <a href="#" class="accion" id="btnImportar">
                            <i class="bi bi-file-arrow-up-fill"></i>
                            <span>Importar Productos</span>
                        </a>

                        <a href="<?= SITE_URL ?>/api/productos/plantilla" class="accion">
                            <i class="bi bi-file-earmark-excel-fill"></i>
                            <span>Descargar Plantilla</span>
                        </a>

                        <a href="<?= SITE_URL ?>/api/productos/exportar" class="accion">
                            <i class="bi bi-file-earmark-arrow-down-fill"></i>
                            <span>Exportar</span>
                        </a>

                        <!-- SELECCIÓN MÚLTIPLE -->
                        <a class="accion accion-danger" id="btnActivarSeleccionProd">
                            <i class="bi bi-check2-square"></i>
                            <span>Eliminar varios</span>
                        </a>


                    </div>
                </div>

                <!-- FONDO OSCURO -->
                <div class="overlay" id="overlay"></div>

                <script>
                    const abrir = document.getElementById("abrirPanel");
                    const cerrar = document.getElementById("cerrarPanel");
                    const panel = document.getElementById("panelLateral");
                    const overlay = document.getElementById("overlay");

                    abrir.addEventListener("click", () => {
                        panel.classList.add("activo");
                        overlay.classList.add("activo");
                    });

                    cerrar.addEventListener("click", cerrarPanel);
                    overlay.addEventListener("click", cerrarPanel);

                    function cerrarPanel(){
                        panel.classList.remove("activo");
                        overlay.classList.remove("activo");
                    }
                </script>

                <!-- MODAL IMPORTAR PRODUCTOS -->
                <div class="modal" id="modalImportar">
                    <div class="modal-import">

                        <button class="modal-import-cerrar cerrarImportar">&times;</button>

                        <div class="modal-import-header">
                            <div class="modal-import-icon">
                                <i class="bi bi-file-earmark-excel-fill"></i>
                            </div>
                            <h2>Importar Productos</h2>
                            <p>Carga masiva desde un archivo <strong>Excel (.xlsx)</strong></p>
                        </div>

                        <form id="formImportar" enctype="multipart/form-data">

                            <!-- ── Paso 1: Excel ── -->
                            <div class="import-paso-label">
                                <span class="import-paso-num">1</span>
                                Selecciona el archivo Excel
                            </div>

                            <label class="dropzone" id="dropzoneProd" for="fileProd">
                                <input type="file" name="archivo" accept=".xlsx"
                                       required id="fileProd" hidden>
                                <i class="bi bi-file-earmark-excel dropzone-icon"></i>
                                <span class="dropzone-texto">Arrastra el Excel aquí</span>
                                <span class="dropzone-sub">o haz clic para seleccionar (.xlsx)</span>
                                <span class="dropzone-nombre" id="nombreArchivoProd">
                                    Ningún archivo seleccionado
                                </span>
                            </label>

                            <a href="<?= SITE_URL ?>/api/productos/plantilla" class="modal-import-plantilla">
                                <i class="bi bi-download"></i> Descargar plantilla de ejemplo
                            </a>

                            <!-- ── Paso 2: Imágenes ── -->
                            <div class="import-paso-label" style="margin-top:16px;">
                                <span class="import-paso-num import-paso-num-img">2</span>
                                Sube las imágenes <span class="import-opcional">(opcional)</span>
                            </div>
                            <p class="import-tip">
                                <i class="bi bi-info-circle-fill"></i>
                                En el Excel escribe el nombre del archivo en las columnas
                                <strong>imagen1–imagen5</strong> (ej: <em>silla_azul.jpg</em>).
                                Luego selecciona esos archivos aquí.
                            </p>

                            <label class="dropzone dropzone-img" id="dropzoneImg" for="fileImagenes">
                                <input type="file" name="imagenes[]" accept="image/*"
                                       multiple id="fileImagenes" hidden>
                                <i class="bi bi-images dropzone-icon"></i>
                                <span class="dropzone-texto">Arrastra las imágenes aquí</span>
                                <span class="dropzone-sub">o haz clic para seleccionar · jpg, png, webp…</span>
                                <span class="dropzone-nombre" id="contadorImagenes">
                                    Ninguna imagen seleccionada
                                </span>
                            </label>

                            <div id="listaImagenesImport" class="import-img-lista"></div>

                            <button type="submit" class="modal-import-btn" id="btnSubmitProd" disabled>
                                <i class="bi bi-upload"></i> Importar
                            </button>

                        </form>

                        <div id="resultadoImport" class="modal-import-resultado"></div>

                    </div>
                </div>

                <script>
                    (function(){
                        const modalImportar  = document.getElementById("modalImportar");
                        const fileInput      = document.getElementById("fileProd");
                        const nombreArchivo  = document.getElementById("nombreArchivoProd");
                        const btnSubmit      = document.getElementById("btnSubmitProd");
                        const dropzone       = document.getElementById("dropzoneProd");
                        const resultado      = document.getElementById("resultadoImport");

                        // ── Imágenes ──────────────────────────────────────────────
                        const fileImagenes   = document.getElementById("fileImagenes");
                        const dropzoneImg    = document.getElementById("dropzoneImg");
                        const contadorImg    = document.getElementById("contadorImagenes");
                        const listaImg       = document.getElementById("listaImagenesImport");
                        let imagenesImport   = [];

                        function actualizarListaImg() {
                            if (!imagenesImport.length) {
                                contadorImg.textContent = "Ninguna imagen seleccionada";
                                listaImg.innerHTML = "";
                                dropzoneImg.classList.remove("dropzone-activo");
                                return;
                            }
                            const n = imagenesImport.length;
                            contadorImg.textContent = n + " imagen" + (n > 1 ? "es" : "") + " seleccionada" + (n > 1 ? "s" : "");
                            dropzoneImg.classList.add("dropzone-activo");
                            listaImg.innerHTML = imagenesImport.map((f, i) =>
                                `<div class="import-img-item">
                                    <i class="bi bi-image-fill"></i>
                                    <span class="import-img-name">${f.name}</span>
                                    <button type="button" class="import-img-remove" data-idx="${i}">&times;</button>
                                </div>`
                            ).join("");
                            listaImg.querySelectorAll(".import-img-remove").forEach(btn => {
                                btn.addEventListener("click", function () {
                                    imagenesImport.splice(parseInt(this.dataset.idx), 1);
                                    actualizarListaImg();
                                });
                            });
                        }

                        function agregarImagenes(files) {
                            const extOk = ['jpg','jpeg','png','webp','gif'];
                            Array.from(files).forEach(f => {
                                const ext = f.name.split('.').pop().toLowerCase();
                                if (!extOk.includes(ext)) return;
                                // Evitar duplicados por nombre
                                if (!imagenesImport.find(x => x.name === f.name)) {
                                    imagenesImport.push(f);
                                }
                            });
                            actualizarListaImg();
                        }

                        fileImagenes.addEventListener("change", () => {
                            agregarImagenes(fileImagenes.files);
                            fileImagenes.value = ""; // reset para permitir re-seleccionar
                        });

                        dropzoneImg.addEventListener("dragover",  e => { e.preventDefault(); dropzoneImg.classList.add("dropzone-drag"); });
                        dropzoneImg.addEventListener("dragleave", ()  => dropzoneImg.classList.remove("dropzone-drag"));
                        dropzoneImg.addEventListener("drop", e => {
                            e.preventDefault();
                            dropzoneImg.classList.remove("dropzone-drag");
                            agregarImagenes(e.dataTransfer.files);
                        });
                        // ──────────────────────────────────────────────────────────

                        document.getElementById("btnImportar").onclick = () => {
                            modalImportar.style.display = "flex";
                        };

                        function resetImportProd() {
                            document.getElementById("formImportar").reset();
                            nombreArchivo.textContent = "Ningún archivo seleccionado";
                            dropzone.classList.remove("dropzone-activo");
                            imagenesImport = [];
                            actualizarListaImg();
                            btnSubmit.disabled = true;
                            resultado.innerHTML = "";
                        }

                        document.querySelector(".cerrarImportar").onclick = () => {
                            modalImportar.style.display = "none";
                            resetImportProd();
                        };
                        window.addEventListener("click", e => {
                            if (e.target === modalImportar) {
                                modalImportar.style.display = "none";
                                resetImportProd();
                            }
                        });

                        // Dropzone Excel
                        fileInput.addEventListener("change", () => {
                            if (fileInput.files.length) {
                                nombreArchivo.textContent = fileInput.files[0].name;
                                dropzone.classList.add("dropzone-activo");
                                btnSubmit.disabled = false;
                            }
                        });
                        dropzone.addEventListener("dragover",  e => { e.preventDefault(); dropzone.classList.add("dropzone-drag"); });
                        dropzone.addEventListener("dragleave", ()  => dropzone.classList.remove("dropzone-drag"));
                        dropzone.addEventListener("drop", e => {
                            e.preventDefault();
                            dropzone.classList.remove("dropzone-drag");
                            const file = e.dataTransfer.files[0];
                            if (file && file.name.endsWith(".xlsx")) {
                                const dt = new DataTransfer();
                                dt.items.add(file);
                                fileInput.files = dt.files;
                                nombreArchivo.textContent = file.name;
                                dropzone.classList.add("dropzone-activo");
                                btnSubmit.disabled = false;
                            }
                        });

                        document.getElementById("formImportar").addEventListener("submit", function(e){
                            e.preventDefault();
                            btnSubmit.disabled = true;
                            btnSubmit.innerHTML = '<i class="bi bi-arrow-repeat spin"></i> Importando...';
                            resultado.innerHTML = "";

                            const formEl = this;
                            Promise.all(imagenesImport.map(f => comprimirImagen(f)))
                            .then(comprimidas => {
                            const fd = new FormData(formEl);
                            comprimidas.forEach((blob, i) => fd.append("imagenes[]", blob, "img" + i + ".jpg"));
                            return fetch("<?= SITE_URL ?>/api/productos/importar", { method: "POST", body: fd });
                            }).then(r => r.json())
                            .then(data => {
                                if (data.error) {
                                    resultado.innerHTML = `<p class="import-fatal">${data.error}</p>`;
                                    btnSubmit.disabled = false;
                                    btnSubmit.innerHTML = '<i class="bi bi-upload"></i> Importar';
                                    return;
                                }
                                const errHTML  = data.detalleErrores?.length
                                    ? `<ul class="import-errores">${data.detalleErrores.map(e=>`<li>${e}</li>`).join("")}</ul>`
                                    : "";
                                const warnHTML = data.advertencias?.length
                                    ? `<ul class="import-warns">${data.advertencias.map(w=>`<li><i class="bi bi-exclamation-triangle-fill"></i> ${w}</li>`).join("")}</ul>`
                                    : "";
                                resultado.innerHTML = `
                                    <div class="import-stats">
                                        <span class="import-ok"><i class="bi bi-check-circle-fill"></i> ${data.insertados} insertados</span>
                                        <span class="import-err"><i class="bi bi-x-circle-fill"></i> ${data.errores} errores</span>
                                    </div>${errHTML}${warnHTML}`;
                                btnSubmit.disabled = false;
                                btnSubmit.innerHTML = '<i class="bi bi-upload"></i> Importar';
                                if (data.insertados > 0) setTimeout(() => location.reload(), 1800);
                            })
                            .catch(() => {
                                resultado.innerHTML = '<p class="import-fatal">Error de conexión</p>';
                                btnSubmit.disabled = false;
                                btnSubmit.innerHTML = '<i class="bi bi-upload"></i> Importar';
                            });
                        });
                    })();
                </script>

                <!-- Modal agregar Producto -->

                <div class="modal" id="modalProducto">
                    <div class="modal-contenidoImagenesProductos">


                        <h2>Agregar Producto</h2>

                        <form id="formProducto" enctype="multipart/form-data">

                            <input type="text" name="nombre" placeholder="Nombre del producto" required>

                            <input type="number" name="precio" placeholder="Precio" required min="0" max="999999999" step="any">

                            <label>Categoría <small style="color:#dc2626">*</small></label>
                            <select id="selectCategoriaProducto" name="idCategoria" required>
                                <option value="">— Seleccionar categoría —</option>
                                <?php
                                    $qCatProd = mysqli_query($con, "SELECT idCategoria, nombreCategoria FROM categoria WHERE estadoCategoria='Activo' ORDER BY nombreCategoria");
                                    while($cp = mysqli_fetch_assoc($qCatProd)){
                                        echo "<option value='{$cp['idCategoria']}'>" . htmlspecialchars($cp['nombreCategoria']) . "</option>";
                                    }
                                ?>
                            </select>

                            <label>Subcategorías <small style="color:#94a3b8">(opcional)</small></label>
                            <div id="subcatWidget">
                                <div id="subcatCheckboxes" style="min-height:40px;padding:8px;border:1px solid #e2e8f0;border-radius:8px;background:#f8fafc;">
                                    <span style="color:#94a3b8;font-size:13px">Selecciona primero una categoría</span>
                                </div>
                            </div>

                            <script>
                            (function(){
                                const selCat   = document.getElementById("selectCategoriaProducto");
                                const widget   = document.getElementById("subcatWidget");

                                // Renderiza el widget completo con buscador + lista con scroll
                                // subs: [{idSubcategoria, nombreSubcategoria}]
                                // seleccionados: array de ids ya marcados
                                window._renderSubcats = function(subs, seleccionados = []) {
                                    if (!subs || !subs.length) {
                                        widget.innerHTML = '<div id="subcatCheckboxes" style="min-height:40px;padding:8px;border:1px solid #e2e8f0;border-radius:8px;background:#f8fafc;"><span style="color:#94a3b8;font-size:13px">No hay subcategorías activas para esta categoría</span></div>';
                                        return;
                                    }

                                    widget.innerHTML = `
                                        <div style="position:relative;margin-bottom:6px;">
                                            <i class="bi bi-search" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:#94a3b8;font-size:13px;pointer-events:none"></i>
                                            <input id="subcatFiltro" type="text" placeholder="Buscar subcategoría..."
                                                style="width:100%;box-sizing:border-box;padding:7px 10px 7px 32px;border:1px solid #e2e8f0;border-radius:8px;font-size:13px;outline:none;background:#fff;"
                                                autocomplete="off">
                                        </div>
                                        <div id="subcatCheckboxes"
                                            style="max-height:180px;overflow-y:auto;padding:6px 8px;border:1px solid #e2e8f0;border-radius:8px;background:#f8fafc;display:flex;flex-direction:column;gap:2px;">
                                        </div>
                                        <div id="subcatContador" style="font-size:11px;color:#94a3b8;margin-top:4px;text-align:right;"></div>`;

                                    const lista    = document.getElementById("subcatCheckboxes");
                                    const filtro   = document.getElementById("subcatFiltro");
                                    const contador = document.getElementById("subcatContador");

                                    function renderItems(query) {
                                        const q = query.toLowerCase().trim();
                                        const filtradas = q ? subs.filter(s => s.nombreSubcategoria.toLowerCase().includes(q)) : subs;
                                        lista.innerHTML = filtradas.length
                                            ? filtradas.map(s => {
                                                const marcado = seleccionados.includes(s.idSubcategoria) ? 'checked' : '';
                                                const resaltado = q
                                                    ? s.nombreSubcategoria.replace(new RegExp(`(${q.replace(/[.*+?^${}()|[\]\\]/g,'\\$&')})`, 'gi'), '<mark style="background:#fef08a;border-radius:2px">$1</mark>')
                                                    : s.nombreSubcategoria;
                                                return `<label style="display:flex;align-items:center;gap:8px;padding:5px 6px;cursor:pointer;font-size:13px;border-radius:6px;transition:background .15s" onmouseover="this.style.background='#f1f5f9'" onmouseout="this.style.background=''">
                                                    <input type="checkbox" name="subcategorias[]" value="${s.idSubcategoria}" style="accent-color:#6366f1;width:15px;height:15px;flex-shrink:0" ${marcado}>
                                                    <span>${resaltado}</span>
                                                </label>`;
                                            }).join('')
                                            : '<span style="color:#94a3b8;font-size:13px;padding:4px">Sin resultados</span>';

                                        // Actualizar seleccionados desde checkboxes actuales
                                        lista.querySelectorAll('input[type=checkbox]').forEach(cb => {
                                            cb.addEventListener('change', () => {
                                                const marcados = lista.querySelectorAll('input:checked').length;
                                                contador.textContent = marcados > 0 ? `${marcados} seleccionada${marcados>1?'s':''}` : '';
                                            });
                                        });

                                        const marcados = lista.querySelectorAll('input:checked').length;
                                        contador.textContent = marcados > 0 ? `${marcados} seleccionada${marcados>1?'s':''}` : '';
                                    }

                                    renderItems('');
                                    filtro.addEventListener('input', () => {
                                        // Guardar ids actualmente marcados antes de re-renderizar
                                        lista.querySelectorAll('input:checked').forEach(cb => {
                                            if (!seleccionados.includes(parseInt(cb.value))) seleccionados.push(parseInt(cb.value));
                                        });
                                        lista.querySelectorAll('input:not(:checked)').forEach(cb => {
                                            const idx = seleccionados.indexOf(parseInt(cb.value));
                                            if (idx !== -1) seleccionados.splice(idx, 1);
                                        });
                                        renderItems(filtro.value);
                                    });
                                    filtro.addEventListener('keydown', e => {
                                        if (e.key === 'Escape') { filtro.value = ''; renderItems(''); }
                                    });
                                };

                                selCat.addEventListener("change", function(){
                                    const val = this.value;
                                    if(!val){
                                        widget.innerHTML = '<div id="subcatCheckboxes" style="min-height:40px;padding:8px;border:1px solid #e2e8f0;border-radius:8px;background:#f8fafc;"><span style="color:#94a3b8;font-size:13px">Selecciona primero una categoría</span></div>';
                                        return;
                                    }
                                    widget.innerHTML = '<div id="subcatCheckboxes" style="padding:8px;border:1px solid #e2e8f0;border-radius:8px;background:#f8fafc;"><span style="color:#94a3b8;font-size:13px"><i class="bi bi-arrow-repeat"></i> Cargando...</span></div>';
                                    fetch(`<?= SITE_URL ?>/api/subcategorias/por-categoria?idCategoria=${val}`)
                                    .then(r => r.json())
                                    .then(data => window._renderSubcats(data, []))
                                    .catch(() => {
                                        widget.innerHTML = '<div id="subcatCheckboxes" style="padding:8px;border:1px solid #e2e8f0;border-radius:8px;"><span style="color:#dc2626;font-size:13px">Error al cargar subcategorías</span></div>';
                                    });
                                });
                            })();
                            </script>

                            <!-- Ubicación: Departamento → Municipio -->
                            <input type="hidden" id="idMunicipio" name="idMunicipio">

                            <label>Departamento <small style="color:#94a3b8">(opcional)</small></label>
                            <select id="selectDepartamento" class="ubicacion-select">
                                <option value="">— Seleccionar departamento —</option>
                                <?php
                                    $chkDep = mysqli_fetch_assoc(mysqli_query($con,
                                        "SELECT COUNT(*) AS c FROM INFORMATION_SCHEMA.TABLES
                                         WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='departamento'"));
                                    if((int)$chkDep['c'] > 0){
                                        $qDeptos = mysqli_query($con, "SELECT idDepartamento, nombre FROM departamento ORDER BY nombre ASC");
                                        while($dep = mysqli_fetch_assoc($qDeptos)){
                                            echo "<option value='{$dep['idDepartamento']}'>" . htmlspecialchars($dep['nombre']) . "</option>";
                                        }
                                    } else {
                                        echo "<option disabled>⚠ Ejecuta la migración primero</option>";
                                    }
                                ?>
                            </select>

                            <label>Municipio <small style="color:#94a3b8">(opcional)</small></label>
                            <select id="selectMunicipio" class="ubicacion-select" disabled>
                                <option value="">— Primero selecciona un departamento —</option>
                            </select>
                            <p id="ubicacionSeleccionada" class="ubicacion-preview" style="display:none;"></p>

                            <script>
                            (function(){
                                const selDepto  = document.getElementById("selectDepartamento");
                                const selMun    = document.getElementById("selectMunicipio");
                                const hiddenMun = document.getElementById("idMunicipio");
                                const preview   = document.getElementById("ubicacionSeleccionada");

                                selDepto.addEventListener("change", function(){
                                    const idD = this.value;
                                    selMun.innerHTML = '<option value="">Cargando...</option>';
                                    selMun.disabled = true;
                                    hiddenMun.value = "";
                                    preview.style.display = "none";
                                    if(!idD) { selMun.innerHTML = '<option value="">— Primero selecciona un departamento —</option>'; return; }

                                    fetch(`<?= SITE_URL ?>/api/ubicacion/municipios?idDepartamento=${idD}`)
                                    .then(r => r.json())
                                    .then(data => {
                                        selMun.innerHTML = '<option value="">— Seleccionar municipio —</option>';
                                        data.forEach(m => {
                                            const opt = document.createElement("option");
                                            opt.value = m.id;
                                            opt.textContent = m.nombre;
                                            selMun.appendChild(opt);
                                        });
                                        selMun.disabled = false;
                                    });
                                });

                                selMun.addEventListener("change", function(){
                                    hiddenMun.value = this.value;
                                    if(this.value){
                                        const dep = selDepto.options[selDepto.selectedIndex].text;
                                        const mun = this.options[this.selectedIndex].text;
                                        preview.textContent = "📍 " + mun + ", " + dep;
                                        preview.style.display = "";
                                    } else {
                                        preview.style.display = "none";
                                    }
                                });

                                // Pre-llenar al editar (si idMunicipio ya viene)
                                window.preCargarUbicacion = function(idMunVal, idDeptoVal){
                                    if(!idDeptoVal || !idMunVal) return;
                                    selDepto.value = idDeptoVal;
                                    selDepto.dispatchEvent(new Event("change"));
                                    // Esperar que carguen los municipios y luego seleccionar
                                    setTimeout(() => {
                                        selMun.value = idMunVal;
                                        selMun.dispatchEvent(new Event("change"));
                                    }, 600);
                                };
                            })();
                            </script>

                            <textarea name="descripcion" placeholder="Descripción del producto" required></textarea>


                            <!-- OFERTA / DESCUENTO -->
                            <div class="oferta-wrap">
                                <div class="oferta-toggle-row">
                                    <span class="oferta-toggle-label">
                                        <i class="bi bi-tag-fill"></i> Producto en oferta
                                    </span>
                                    <label class="toggle-switch">
                                        <input type="checkbox" name="enOferta" id="chkEnOferta">
                                        <span class="toggle-slider"></span>
                                    </label>
                                </div>
                                <div id="wrapDescuento" style="display:none;">
                                    <div class="oferta-descuento-row">
                                        <label for="inputDescuento"><i class="bi bi-percent"></i> Descuento:</label>
                                        <input type="number" id="inputDescuento" name="descuento"
                                               min="1" max="99" value="10" placeholder="10">
                                        <span style="font-size:13px;color:#64748b;">%</span>
                                    </div>
                                    <div class="oferta-precio-preview" id="precioPreviewOferta" style="margin-top:6px;"></div>
                                </div>
                            </div>

                            <!-- CANTIDAD UNIDADES (solo al crear) -->
                            <div id="wrapCantidad">
                                <div class="wrap-cantidad-label">
                                    <label>Unidades iniciales de inventario</label>
                                    <small style="color:#94a3b8;font-size:12px">(opcional, solo al crear)</small>
                                </div>
                                <input type="number" id="cantidadUnidades" name="cantidad"
                                    placeholder="0" min="0" max="1000" value="0"
                                    style="width:100%;padding:10px 14px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:14px;outline:none;box-sizing:border-box;">
                                <small id="msgCantidadUnidades" style="color:#dc2626;font-size:11.5px;margin-top:4px;display:none;">
                                    <i class="bi bi-exclamation-circle"></i> Máximo 1000 unidades iniciales.
                                </small>
                                <small style="color:#94a3b8;font-size:11.5px;margin-top:4px;display:block">
                                    <i class="bi bi-info-circle"></i> Se crearán automáticamente las unidades en inventario al guardar
                                </small>
                            </div>

                            <!-- IMÁGENES -->
                            <label>Imágenes</label>
                            <div id="dropZoneImagenes" class="drop-zone-img">
                                <i class="bi bi-cloud-arrow-up drop-zone-icon"></i>
                                <p>Arrastra imágenes aquí o <span class="drop-zone-link">selecciona archivos</span></p>
                                <input type="file" id="imagenes" multiple accept="image/*">
                            </div>

                            <div id="previewImagenes"></div>

                            <button type="submit">Guardar</button>

                        </form>
                    </div>
                </div>

                
            </div>

            <!-- ══ BARRA SELECCIÓN MÚLTIPLE PRODUCTOS ══════════ -->
            <div class="seleccion-barra" id="seleccionBarraProd">
                <div class="seleccion-barra-inner">

                    <div class="seleccion-info">
                        <div class="seleccion-icon-wrap">
                            <i class="bi bi-check2-square"></i>
                        </div>
                        <div class="seleccion-texto">
                            <span class="seleccion-titulo">Modo selección</span>
                            <span id="seleccionContadorProd" class="seleccion-sub">0 productos seleccionados</span>
                        </div>
                    </div>

                    <div class="seleccion-acciones">
                        <button class="seleccion-btn seleccion-btn-todo" id="btnSelTodoProd">
                            <i class="bi bi-check-all"></i> Todos
                        </button>
                        <button class="seleccion-btn seleccion-btn-ninguna" id="btnSelNingunaProd">
                            <i class="bi bi-dash-lg"></i> Ninguno
                        </button>
                        <button class="seleccion-btn seleccion-btn-eliminar" id="btnEliminarSelProd" disabled>
                            <i class="bi bi-trash3-fill"></i> Eliminar seleccionados
                        </button>
                        <button class="seleccion-btn seleccion-btn-cancelar" id="btnCancelarSelProd">
                            <i class="bi bi-x-lg"></i> Cancelar
                        </button>
                    </div>

                </div>
            </div>

            <!-- ── BARRA DE FILTROS ─────────────────────────── -->
            <?php
                $sqlCat = "SELECT c.idCategoria, c.nombreCategoria, COUNT(DISTINCT p.idProducto) AS totalProductos
                           FROM categoria c
                           INNER JOIN producto p ON (
                               p.idCategoria = c.idCategoria
                               OR EXISTS (
                                   SELECT 1 FROM productosubcategoria ps
                                   INNER JOIN subcategoria s ON s.idSubcategoria = ps.idSubcategoria
                                   WHERE ps.idProducto = p.idProducto AND s.idCategoria = c.idCategoria
                               )
                           )
                           GROUP BY c.idCategoria
                           HAVING totalProductos > 0
                           ORDER BY c.nombreCategoria";
                $queryCat       = mysqli_query($con, $sqlCat);
                $productosPorCategoria = [];
                $categoriasFiltro = [];
                while($cat = mysqli_fetch_assoc($queryCat)){
                    $categoriasFiltro[] = $cat;
                }
                $totalTodosProductos = mysqli_fetch_row(mysqli_query($con,"SELECT COUNT(DISTINCT p.idProducto) FROM producto p"))[0];
            ?>

            <div class="filtro-barra">
                <div class="filtro-scroll" id="filtroScroll">

                    <!-- Chip "Todos" -->
                    <button class="filtro-chip filtro-chip-all activo" id="chipTodos">
                        <i class="bi bi-grid-fill"></i>
                        Todos
                        <span class="chip-count"><?php echo $totalTodosProductos; ?></span>
                    </button>

                    <?php foreach($categoriasFiltro as $cat): ?>
                    <div class="filtro-chip-wrap" data-id="<?php echo $cat['idCategoria']; ?>">

                        <button class="filtro-chip categoriaFiltro"
                                data-categoria="<?php echo $cat['idCategoria']; ?>">
                            <?php echo htmlspecialchars($cat['nombreCategoria']); ?>
                            <span class="chip-count"><?php echo $cat['totalProductos']; ?></span>
                        </button>

                        <button class="filtro-chip-expand" title="Ver productos">
                            <i class="bi bi-chevron-down"></i>
                        </button>

                        <div class="filtro-dropdown">
                            <p class="filtro-dropdown-titulo">
                                <i class="bi bi-bookmark-fill"></i>
                                <?php echo htmlspecialchars($cat['nombreCategoria']); ?>
                            </p>
                            <?php
                                $idCat = intval($cat['idCategoria']);
                                $qProd = mysqli_query($con, "SELECT DISTINCT p.nombreProducto FROM producto p WHERE (p.idCategoria=$idCat OR EXISTS (SELECT 1 FROM productosubcategoria ps INNER JOIN subcategoria s ON s.idSubcategoria=ps.idSubcategoria WHERE ps.idProducto=p.idProducto AND s.idCategoria=$idCat)) ORDER BY p.nombreProducto");
                                while($prod = mysqli_fetch_assoc($qProd)):
                            ?>
                            <div class="nameProduct filtro-dropdown-item"
                                 data-nombre="<?php echo strtolower(htmlspecialchars($prod['nombreProducto'])); ?>">
                                <i class="bi bi-dot"></i>
                                <?php echo htmlspecialchars($prod['nombreProducto']); ?>
                            </div>
                            <?php endwhile; ?>
                        </div>

                    </div>
                    <?php endforeach; ?>

                </div>
            </div>

            <script>
            document.addEventListener("DOMContentLoaded", function () {

                const chipTodos = document.getElementById("chipTodos");

                // Todas las secciones (cabecera + grid de cartas)
                function getSecciones() {
                    return document.querySelectorAll(".nameCatArriba, .CartaProducto");
                }

                function setChipActivo(el) {
                    document.querySelectorAll(".filtro-chip").forEach(c => c.classList.remove("activo"));
                    if (el) el.classList.add("activo");
                }

                function cerrarDropdowns() {
                    document.querySelectorAll(".filtro-chip-wrap.abierto").forEach(w => w.classList.remove("abierto"));
                }

                // Mostrar todas las secciones y limpiar overrides de cartas
                window.resetFiltro = function () {
                    getSecciones().forEach(el => el.classList.remove("filtro-oculto"));
                    document.querySelectorAll(".card-producto").forEach(c => c.classList.remove("filtro-oculto"));
                    setChipActivo(chipTodos);
                    cerrarDropdowns();
                };

                chipTodos.addEventListener("click", resetFiltro);

                // Filtrar por categoría — toggle: clic en activa = volver a todo
                document.querySelectorAll(".categoriaFiltro").forEach(btn => {
                    btn.addEventListener("click", function (e) {
                        e.stopPropagation();

                        // Si ya está activa, quitar el filtro
                        if (this.classList.contains("activo")) {
                            resetFiltro();
                            return;
                        }

                        const idCat = this.dataset.categoria;

                        // Mostrar solo la sección de esta categoría
                        getSecciones().forEach(el => {
                            el.classList.toggle("filtro-oculto", el.dataset.catId !== idCat);
                        });

                        setChipActivo(this);
                        cerrarDropdowns();
                    });
                });

                // Expandir dropdown de productos por categoría
                document.querySelectorAll(".filtro-chip-expand").forEach(btn => {
                    btn.addEventListener("click", function (e) {
                        e.stopPropagation();
                        const wrap = this.closest(".filtro-chip-wrap");
                        const estaAbierto = wrap.classList.contains("abierto");
                        cerrarDropdowns();
                        if (!estaAbierto) {
                            wrap.classList.add("abierto");
                            const dropdown = wrap.querySelector(".filtro-dropdown");
                            const rect = wrap.getBoundingClientRect();
                            dropdown.style.top  = (rect.bottom + 8) + "px";
                            dropdown.style.left = rect.left + "px";
                        }
                    });
                });

                // Filtrar por producto individual desde el dropdown
                document.querySelectorAll(".nameProduct").forEach(item => {
                    item.addEventListener("click", function (e) {
                        e.stopPropagation();
                        const nombre = this.dataset.nombre;

                        // Mostrar solo la sección de la categoría correspondiente
                        const wrap  = this.closest(".filtro-chip-wrap");
                        const idCat = wrap ? wrap.dataset.id : null;

                        getSecciones().forEach(el => {
                            el.classList.toggle("filtro-oculto", !(!idCat || el.dataset.catId === idCat));
                        });

                        // Dentro de esa sección, resaltar solo el producto buscado
                        document.querySelectorAll(".card-producto").forEach(card => {
                            card.classList.toggle("filtro-oculto", !card.dataset.nombre.includes(nombre));
                        });

                        if (wrap) setChipActivo(wrap.querySelector(".categoriaFiltro"));
                        cerrarDropdowns();
                    });
                });

                document.addEventListener("click", cerrarDropdowns);

                // ── Búsqueda en vivo ──────────────────────────────────────
                const inputBusqueda = document.getElementById("inputBusquedaProd");
                if (inputBusqueda) {
                    inputBusqueda.addEventListener("input", function () {
                        const term = this.value.trim().toLowerCase();

                        if (!term) {
                            resetFiltro();
                            return;
                        }

                        // Deseleccionar chip activo
                        document.querySelectorAll(".filtro-chip").forEach(c => c.classList.remove("activo"));

                        // Para cada sección (nameCatArriba + CartaProducto comparten data-cat-id)
                        const cabeceras = document.querySelectorAll(".nameCatArriba");
                        cabeceras.forEach(cab => {
                            const catId       = cab.dataset.catId;
                            const catNombre   = cab.dataset.catNombre || "";
                            const grid        = document.querySelector(`.CartaProducto[data-cat-id="${catId}"]`);
                            const cards       = grid ? grid.querySelectorAll(".card-producto") : [];

                            const catCoincide = catNombre.includes(term);

                            if (catCoincide) {
                                // Toda la categoría coincide → mostrar sección y todas sus cartas
                                cab.style.display  = "";
                                if (grid) grid.style.display = "";
                                cards.forEach(c => c.style.display = "");
                            } else {
                                // Buscar cartas que coincidan por nombre
                                let alguna = false;
                                cards.forEach(c => {
                                    const coincide = (c.dataset.nombre || "").includes(term);
                                    c.style.display = coincide ? "" : "none";
                                    if (coincide) alguna = true;
                                });

                                if (alguna) {
                                    cab.style.display  = "";
                                    if (grid) grid.style.display = "";
                                } else {
                                    cab.style.display  = "none";
                                    if (grid) grid.style.display = "none";
                                }
                            }
                        });
                    });
                }
            });
            </script>

            <!-- Parte Donde se van a ver los productos en Cartas -->

            <?php
                $sql = "SELECT
                    p.idProducto,
                    p.nombreProducto,
                    p.precio,
                    p.ubicacion,
                    p.descripcion,
                    p.enOferta,
                    p.descuento,
                    p.idMunicipio,
                    m.idDepartamento,
                    COALESCE(s.idSubcategoria, 0)   AS idSubcat,
                    COALESCE(s.nombreSubcategoria,'Sin subcategoría') AS nombreSubcat,
                    COALESCE(c.idCategoria, p.idCategoria, 0)       AS idCat,
                    COALESCE(c.nombreCategoria, pc.nombreCategoria, 'Sin categoría') AS nombreCat,
                    COALESCE(inv_agg.stockDisponible, 0) AS stockDisponible,
                    COALESCE(inv_agg.stockTotal, 0)      AS stockTotal,
                    COALESCE(pc.estadoCategoria, c.estadoCategoria, 'Activo') AS estadoCat,
                    i.idImagen,
                    i.rutaImagen,
                    i.esPrincipal
                FROM producto p
                LEFT JOIN productosubcategoria ps ON ps.idProducto = p.idProducto
                LEFT JOIN subcategoria s          ON s.idSubcategoria = ps.idSubcategoria
                LEFT JOIN categoria c             ON c.idCategoria = s.idCategoria
                LEFT JOIN categoria pc            ON pc.idCategoria = p.idCategoria
                LEFT JOIN imagenesproducto i      ON i.idProducto = p.idProducto
                LEFT JOIN municipio m             ON m.idMunicipio = p.idMunicipio
                LEFT JOIN (
                    SELECT idProducto,
                           SUM(estadoItem='Disponible') AS stockDisponible,
                           COUNT(*)                     AS stockTotal
                    FROM iteminventario
                    GROUP BY idProducto
                ) inv_agg ON inv_agg.idProducto = p.idProducto";

                if(!empty($busqueda)){
                    $sql .= " WHERE p.nombreProducto LIKE '%$busqueda%'
                              OR s.nombreSubcategoria LIKE '%$busqueda%'
                              OR c.nombreCategoria LIKE '%$busqueda%'";
                }

                $sql .= " ORDER BY c.nombreCategoria, p.nombreProducto, i.orden ASC";

                $query = mysqli_query($con, $sql);

                while($row = mysqli_fetch_assoc($query)){
                    $catId = $row['idCat'] ?: 0;
                    $id    = $row['idProducto'];

                    if(!isset($productosPorCategoria[$catId][$id])){
                        $productosPorCategoria[$catId][$id] = [
                            "nombre"         => $row['nombreProducto'],
                            "precio"         => $row['precio'],
                            "ubicacion"      => $row['ubicacion'],
                            "descripcion"    => $row['descripcion'],
                            "enOferta"       => (int)$row['enOferta'],
                            "descuento"      => (float)$row['descuento'],
                            "idCat"          => $catId,
                            "nombreCat"      => $row['nombreCat'],
                            "nombreSubcat"   => $row['nombreSubcat'],
                            "stockDisponible"=> $row['stockDisponible'],
                            "stockTotal"     => $row['stockTotal'],
                            "estadoCat"      => $row['estadoCat'],
                            "idMunicipio"    => $row['idMunicipio'] ? (int)$row['idMunicipio'] : null,
                            "idDepartamento" => $row['idDepartamento'] ? (int)$row['idDepartamento'] : null,
                            "subcats"        => [],
                            "imagenes"       => []
                        ];
                    }

                    if($row['idSubcat'] > 0){
                        $sid = intval($row['idSubcat']);
                        if(!in_array($sid, $productosPorCategoria[$catId][$id]['subcats'])){
                            $productosPorCategoria[$catId][$id]['subcats'][] = $sid;
                        }
                    }

                    if($row['rutaImagen']){
                        $imgKey = $row['idImagen'];
                        if(!isset($productosPorCategoria[$catId][$id]['imagenes'][$imgKey])){
                            $productosPorCategoria[$catId][$id]['imagenes'][$imgKey] = [
                                "idImagen"  => $row['idImagen'],
                                "rutaImagen"=> $row['rutaImagen'],
                                "principal" => $row['esPrincipal']
                            ];
                        }
                    }
                }
            ?>

            <div class="ProductGeneral">

                <?php
                $sqlCat2 = "SELECT c.*, COUNT(DISTINCT p.idProducto) AS totalProductos
                            FROM categoria c
                            INNER JOIN producto p ON (
                                p.idCategoria = c.idCategoria
                                OR EXISTS (
                                    SELECT 1 FROM productosubcategoria ps
                                    INNER JOIN subcategoria s ON s.idSubcategoria = ps.idSubcategoria
                                    WHERE ps.idProducto = p.idProducto AND s.idCategoria = c.idCategoria
                                )
                            )
                            GROUP BY c.idCategoria
                            ORDER BY c.nombreCategoria";

                $queryCat2 = mysqli_query($con, $sqlCat2);

                while($cat = mysqli_fetch_assoc($queryCat2)){
                    $idCat = $cat['idCategoria'];
                ?>

                    <div class="nameCatArriba" data-cat-id="<?php echo $idCat; ?>" data-cat-nombre="<?php echo strtolower($cat['nombreCategoria']); ?>">
                        <h2>
                            <i class="bi bi-caret-down-fill flechaToggle" ></i>
                            <?php echo $cat['nombreCategoria']; ?>
                            <span>(<?php echo $cat['totalProductos']; ?>)</span>
                        </h2>
                    </div>

                    <div class="CartaProducto activo" data-cat-id="<?php echo $idCat; ?>">

                        <?php 
                        if(isset($productosPorCategoria[$idCat])){
                            foreach($productosPorCategoria[$idCat] as $id => $prod){

                                $imagenPrincipal = null;
                                $otrasImagenes = [];

                                $imagenesArr = array_values($prod['imagenes']);
                                if(count($imagenesArr) > 0){
                                    $imagenPrincipal = $imagenesArr[0]['rutaImagen'];
                                    $otrasImagenes = array_slice(
                                        array_column($imagenesArr, 'rutaImagen'), 1
                                    );
                                } else {
                                    $imagenPrincipal = null;
                                    $otrasImagenes = [];
                                }
                        ?>
                        <?php
                            $prodJson = htmlspecialchars(json_encode([
                                "nombre"          => $prod['nombre'],
                                "precio"          => $prod['precio'],
                                "ubicacion"       => $prod['ubicacion'],
                                "descripcion"     => $prod['descripcion'],
                                "enOferta"        => $prod['enOferta'],
                                "descuento"       => $prod['descuento'],
                                "idCat"           => $prod['idCat'],
                                "nombreCat"       => $prod['nombreCat'],
                                "nombreSubcat"    => $prod['nombreSubcat'],
                                "subcats"         => $prod['subcats'],
                                "stockDisponible" => (int)$prod['stockDisponible'],
                                "stockTotal"      => (int)$prod['stockTotal'],
                                "idMunicipio"     => $prod['idMunicipio'],
                                "idDepartamento"  => $prod['idDepartamento'],
                                "imagenes"        => array_values($prod['imagenes'])
                            ], JSON_UNESCAPED_UNICODE), ENT_QUOTES);
                        ?>
                        <?php
                            $catOculta = $prod['estadoCat'] === 'Oculto';
                            $sinStock  = (int)$prod['stockDisponible'] === 0;
                            $ocultoPub = $catOculta || $sinStock;
                            $razones   = [];
                            if ($catOculta) $razones[] = ['icono' => 'bi-tag-fill', 'txt' => 'Categoría oculta', 'tipo' => 'cat'];
                            if ($sinStock)  $razones[] = ['icono' => 'bi-box',      'txt' => 'Sin unidades',     'tipo' => 'stock'];
                        ?>
                        <div class="card-producto<?php echo $ocultoPub ? ' card-oculta-pub' : ''; ?>"
                            data-categoria="<?php echo $idCat ?>"
                            data-nombre="<?php echo strtolower($prod['nombre']); ?>"
                            data-id="<?php echo $id ?>"
                            data-prod="<?php echo $prodJson ?>"
                            id="producto-<?php echo $id ?>">

                            <!-- Checkbox selección múltiple -->
                            <label class="prod-checkbox-wrap" title="Seleccionar">
                                <input type="checkbox" class="prod-checkbox"
                                       value="<?php echo $id ?>">
                                <span class="cat-checkbox-custom">
                                    <i class="bi bi-check-lg"></i>
                                </span>
                            </label>

                            <!-- Imagen / slider -->
                            <div class="slider-producto">
                                <?php if($imagenPrincipal){ ?>
                                    <img src="<?php echo $imagenPrincipal ?>" class="slide-producto active" loading="lazy">
                                <?php } else { ?>
                                    <div class="card-sin-imagen"><i class="bi bi-image"></i></div>
                                <?php } ?>
                                <?php foreach($otrasImagenes as $img){ ?>
                                    <img src="<?php echo $img ?>" class="slide-producto" loading="lazy">
                                <?php } ?>

                                <!-- Badge stock disponible -->
                                <?php
                                    $disp = $prod['stockDisponible'];
                                    $total = $prod['stockTotal'];
                                    $badgeClass = $disp > 0 ? 'badge-disponible' : 'badge-agotado';
                                    $badgeText  = $disp > 0 ? "$disp disponible".($disp>1?'s':'') : 'Agotado';
                                ?>
                                <span class="card-estado-badge <?php echo $badgeClass; ?>"><?php echo $badgeText; ?></span>
                                <?php if($prod['enOferta'] && $prod['descuento'] > 0): ?>
                                <span class="card-oferta-badge">
                                    <i class="bi bi-tag-fill"></i> -<?php echo intval($prod['descuento']); ?>%
                                </span>
                                <?php endif; ?>

                                <!-- Botones de acción (aparecen al hover) -->
                                <div class="acciones-producto">
                                    <button class="btn-accion btn-editar editarProducto"
                                        title="Editar"
                                        onclick="event.stopPropagation()">
                                        <i class="bi bi-pencil-fill"></i>
                                    </button>
                                    <button class="btn-accion btn-items-inv"
                                        title="Ver inventario"
                                        onclick="event.stopPropagation(); verItemsProducto(<?php echo $id ?>, '<?php echo htmlspecialchars(addslashes($prod['nombre'])) ?>')">
                                        <i class="bi bi-boxes"></i>
                                    </button>
                                    <button class="btn-accion btn-eliminar"
                                        title="Eliminar"
                                        onclick="event.stopPropagation(); eliminarProducto(<?php echo $id ?>)">
                                        <i class="bi bi-trash-fill"></i>
                                    </button>
                                </div>

                                <!-- Indicador de múltiples imágenes -->
                                <?php if(count($prod['imagenes']) > 1){ ?>
                                <span class="card-img-count">
                                    <i class="bi bi-images"></i> <?php echo count($prod['imagenes']); ?>
                                </span>
                                <?php } ?>
                            </div>

                            <!-- Info -->
                            <div class="card-info">
                                <h4 class="card-nombre"><?php echo htmlspecialchars($prod['nombre']); ?></h4>
                                <p class="card-precio<?php echo ($prod['enOferta'] && $prod['descuento'] > 0) ? ' card-precio-con-oferta' : ''; ?>">
                                    <?php if($prod['enOferta'] && $prod['descuento'] > 0):
                                        $precioFinal = $prod['precio'] * (1 - $prod['descuento'] / 100);
                                    ?>
                                        <span class="card-precio-tachado">$<?php echo number_format($prod['precio']); ?></span>
                                        <span class="card-precio-descuento">$<?php echo number_format(round($precioFinal)); ?></span>
                                    <?php else: ?>
                                        $<?php echo number_format($prod['precio']); ?>
                                    <?php endif; ?>
                                </p>
                                <div class="card-meta">
                                    <span class="card-ubicacion">
                                        <i class="bi bi-geo-alt-fill"></i>
                                        <?php echo htmlspecialchars($prod['ubicacion']); ?>
                                    </span>
                                </div>
                                <?php if ($ocultoPub): ?>
                                <div class="card-visibilidad-strip">
                                    <i class="bi bi-eye-slash-fill"></i>
                                    <span>Oculto al público</span>
                                    <?php foreach ($razones as $r): ?>
                                    <span class="card-razon-pill razon-<?php echo $r['tipo']; ?>">
                                        <i class="bi <?php echo $r['icono']; ?>"></i>
                                        <?php echo $r['txt']; ?>
                                    </span>
                                    <?php endforeach; ?>
                                </div>
                                <?php endif; ?>
                                <!-- Fila inventario -->
                                <div class="card-inv-row">
                                    <?php if($total > 0): ?>
                                        <span class="card-stock-pill <?php echo $disp == 0 ? 'agotado' : ''; ?>">
                                            <i class="bi bi-box-seam-fill"></i>
                                            <?php echo $disp; ?>/<?php echo $total; ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="card-stock-pill sin-stock">
                                            <i class="bi bi-box"></i> Sin stock
                                        </span>
                                    <?php endif; ?>
                                    <button class="btn-ver-items-card"
                                        onclick="event.stopPropagation(); verItemsProducto(<?php echo $id ?>, '<?php echo htmlspecialchars(addslashes($prod['nombre'])) ?>')">
                                        <i class="bi bi-list-ul"></i> Ver items
                                    </button>
                                </div>
                            </div>
                        </div>
                        <?php 
                            }
                        }
                        ?>
                    </div>

                <?php } ?>

            </div>

            <!-- Modal confirmación eliminación masiva productos -->
            <div class="modal" id="modalConfirmElimProd">
                <div class="modal-confirm-contenido">
                    <div class="modal-confirm-icon">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                    </div>
                    <h3 class="modal-confirm-titulo">¿Eliminar productos?</h3>
                    <p class="modal-confirm-sub">
                        Se eliminarán <strong id="confirmarNumeroProd">0</strong> producto(s) y todas sus imágenes permanentemente.<br>
                        Esta acción no se puede deshacer.
                    </p>
                    <div class="modal-confirm-botones">
                        <button class="modal-confirm-cancelar" id="confirmarCancelarProd">Cancelar</button>
                        <button class="modal-confirm-eliminar" id="confirmarEliminarProd">
                            <i class="bi bi-trash-fill"></i> Sí, eliminar
                        </button>
                    </div>
                </div>
            </div>

            <div class="modal" id="modalVistaProducto">
                <div class="modal-vista-contenido">

                    <span class="cerrar" onclick="cerrarVista()"><i class="bi bi-x-lg"></i></span>

                    <div class="vista-container">

                        <!-- MINIATURAS -->
                        <div class="miniaturas" id="miniaturas"></div>

                        <!-- IMAGEN GRANDE -->
                        <div class="imagen-grande" id="imagenGrande">
                            <img id="imagenPrincipalVista" draggable="false">
                            <div class="zoom-hint" id="zoomHint">
                                <i class="bi bi-zoom-in"></i> Clic para ampliar · Rueda para zoom
                            </div>
                            <button class="zoom-reset-btn" id="zoomResetBtn" onclick="window.dispatchEvent(new Event('resetZoom'))">
                                <i class="bi bi-arrows-fullscreen"></i> Restablecer
                            </button>
                        </div>

                        <!-- INFO -->
                        <div class="info-producto" style="display:flex;flex-direction:column;gap:10px;">

                            <!-- Nombre -->
                            <h2 id="tituloProducto" style="margin:0;font-size:18px;line-height:1.3;padding-right:28px;"></h2>
                            <!-- Badge oferta (línea propia) -->
                            <span id="vistaBadgeOferta" style="display:none;background:linear-gradient(135deg,#f97316,#ea580c);color:#fff;font-size:11px;font-weight:700;padding:4px 11px;border-radius:20px;white-space:nowrap;align-self:flex-start;"></span>

                            <!-- Precio -->
                            <div id="vistaPrecioWrap" style="display:flex;align-items:baseline;gap:8px;flex-wrap:wrap;">
                                <span id="vistaPrecioOriginal" style="display:none;text-decoration:line-through;color:#94a3b8;font-size:14px;"></span>
                                <span id="vistaPrecioFinal" style="font-size:22px;font-weight:800;color:#1e293b;"></span>
                            </div>

                            <!-- Separador -->
                            <hr style="border:none;border-top:1px solid #f1f5f9;margin:0;">

                            <!-- Pills de info -->
                            <div style="display:flex;flex-wrap:wrap;gap:8px;">
                                <span id="vistaUbicacion" style="display:inline-flex;align-items:center;gap:5px;font-size:12.5px;color:#475569;background:#f8fafc;border:1px solid #e2e8f0;padding:4px 10px;border-radius:20px;"></span>
                                <span id="vistaCategoria" style="display:inline-flex;align-items:center;gap:5px;font-size:12.5px;color:#4f46e5;background:#eef2ff;border:1px solid #c7d2fe;padding:4px 10px;border-radius:20px;"></span>
                                <span id="vistaSubcat" style="display:inline-flex;align-items:center;gap:5px;font-size:12.5px;color:#0369a1;background:#e0f2fe;border:1px solid #bae6fd;padding:4px 10px;border-radius:20px;"></span>
                            </div>

                            <!-- Stock -->
                            <div id="vistaStockWrap" style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                                <span id="vistaStockDisp" style="display:inline-flex;align-items:center;gap:5px;font-size:12.5px;font-weight:700;padding:4px 10px;border-radius:20px;"></span>
                                <span id="vistaStockTotal" style="display:inline-flex;align-items:center;gap:5px;font-size:12px;color:#475569;background:#f8fafc;border:1px solid #e2e8f0;padding:4px 10px;border-radius:20px;"></span>
                            </div>

                            <!-- Descripción -->
                            <div style="background:#f8fafc;border-radius:10px;padding:12px 14px;border:1px solid #f1f5f9;">
                                <p style="margin:0 0 4px;font-size:11px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.6px;">Descripción</p>
                                <p id="descripcionProducto" style="margin:0;font-size:13.5px;color:#374151;line-height:1.6;white-space:pre-line;"></p>
                            </div>

                            <a id="btnWhatsapp" target="_blank" class="btn-whatsapp">
                                <i class="bi bi-whatsapp"></i> Contactar por WhatsApp
                            </a>
                        </div>

                    </div>
                </div>
            </div>

                <!-- Modal Click  -->
                 <script>
                    document.querySelectorAll(".card-producto").forEach(card => {

                        card.addEventListener("click", function(){

                            // En modo selección no abrir el modal de vista
                            if(document.querySelector(".CartaProducto.modo-seleccion-prod")) return;

                            let id = this.id.replace("producto-", "");

                            verProducto(id);

                        });

                    });

                    function cerrarVista(){
                        document.getElementById("modalVistaProducto").style.display = "none";
                        window.dispatchEvent(new Event("resetZoom"));
                    }

                    window.addEventListener("click", function(e){
                        const modal = document.getElementById("modalVistaProducto");
                        if(e.target === modal){ cerrarVista(); }
                    });

                    // ── Motor de Zoom / Pan ──────────────────────────────
                    (function(){
                        const wrap     = document.getElementById("imagenGrande");
                        const img      = document.getElementById("imagenPrincipalVista");
                        const hint     = document.getElementById("zoomHint");
                        const resetBtn = document.getElementById("zoomResetBtn");
                        if(!wrap || !img) return;

                        const MAX = 5, MIN = 1;
                        let scale = 1, tx = 0, ty = 0;
                        let dragging = false, lastX = 0, lastY = 0;
                        let hintTimer = null;

                        function applyTransform(animated){
                            img.style.transition = animated ? "transform .22s ease" : "none";
                            img.style.transform  = `translate(${tx}px,${ty}px) scale(${scale})`;
                            img.style.cursor     = scale > 1 ? (dragging ? "grabbing" : "grab") : "zoom-in";
                            resetBtn.classList.toggle("visible", scale > 1);
                        }

                        function clampT(){
                            const wr = wrap.getBoundingClientRect();
                            const ir = img.getBoundingClientRect();
                            const mx = Math.max(0, (ir.width  - wr.width)  / (2 * scale));
                            const my = Math.max(0, (ir.height - wr.height) / (2 * scale));
                            tx = Math.min(mx, Math.max(-mx, tx));
                            ty = Math.min(my, Math.max(-my, ty));
                        }

                        function reset(animated){
                            scale = 1; tx = 0; ty = 0;
                            applyTransform(animated);
                            hint.classList.remove("oculto");
                        }

                        function showHintBriefly(){
                            clearTimeout(hintTimer);
                            hint.classList.remove("oculto");
                            hintTimer = setTimeout(() => hint.classList.add("oculto"), 2400);
                        }

                        window.addEventListener("resetZoom", () => reset(true));

                        // Scroll wheel zoom
                        wrap.addEventListener("wheel", function(e){
                            e.preventDefault();
                            const step  = e.deltaY < 0 ? 0.3 : -0.3;
                            const ns    = Math.min(MAX, Math.max(MIN, scale + step));
                            const rect  = wrap.getBoundingClientRect();
                            const ox    = (e.clientX - rect.left  - rect.width  / 2) / scale;
                            const oy    = (e.clientY - rect.top   - rect.height / 2) / scale;
                            tx -= ox * (ns - scale);
                            ty -= oy * (ns - scale);
                            scale = ns;
                            if(scale === MIN){ tx = 0; ty = 0; }
                            clampT();
                            applyTransform(false);
                            hint.classList.add("oculto");
                        }, { passive: false });

                        // Click: zoom in at point / reset
                        wrap.addEventListener("click", function(e){
                            if(dragging) return;
                            if(scale > 1){ reset(true); return; }
                            const rect = wrap.getBoundingClientRect();
                            const ox   = e.clientX - rect.left  - rect.width  / 2;
                            const oy   = e.clientY - rect.top   - rect.height / 2;
                            scale = 2.8;
                            tx = -(ox / scale) * (scale - 1);
                            ty = -(oy / scale) * (scale - 1);
                            clampT();
                            applyTransform(true);
                            hint.classList.add("oculto");
                        });

                        // Drag to pan
                        wrap.addEventListener("mousedown", function(e){
                            if(scale <= 1) return;
                            dragging = true; lastX = e.clientX; lastY = e.clientY;
                            img.style.cursor = "grabbing";
                            e.preventDefault();
                        });
                        window.addEventListener("mousemove", function(e){
                            if(!dragging) return;
                            tx += (e.clientX - lastX) / scale;
                            ty += (e.clientY - lastY) / scale;
                            lastX = e.clientX; lastY = e.clientY;
                            clampT(); applyTransform(false);
                        });
                        window.addEventListener("mouseup", function(){
                            if(dragging){ dragging = false; applyTransform(false); }
                        });

                        // Pinch zoom (touch)
                        let lastDist = 0;
                        wrap.addEventListener("touchstart", function(e){
                            if(e.touches.length === 2){
                                lastDist = Math.hypot(e.touches[0].clientX - e.touches[1].clientX, e.touches[0].clientY - e.touches[1].clientY);
                            } else if(e.touches.length === 1 && scale > 1){
                                dragging = true; lastX = e.touches[0].clientX; lastY = e.touches[0].clientY;
                            }
                        }, { passive: true });
                        wrap.addEventListener("touchmove", function(e){
                            if(e.touches.length === 2){
                                const d = Math.hypot(e.touches[0].clientX - e.touches[1].clientX, e.touches[0].clientY - e.touches[1].clientY);
                                scale = Math.min(MAX, Math.max(MIN, scale + (d - lastDist) * 0.012));
                                lastDist = d; clampT(); applyTransform(false);
                            } else if(dragging && e.touches.length === 1){
                                tx += (e.touches[0].clientX - lastX) / scale;
                                ty += (e.touches[0].clientY - lastY) / scale;
                                lastX = e.touches[0].clientX; lastY = e.touches[0].clientY;
                                clampT(); applyTransform(false);
                            }
                        }, { passive: true });
                        wrap.addEventListener("touchend", () => { dragging = false; });

                        // Show hint on modal open
                        showHintBriefly();
                    })();

                    function verProducto(id){
                        const card = document.getElementById("producto-" + id);
                        if(!card) return;
                        let data;
                        try { data = JSON.parse(card.dataset.prod); }
                        catch(e){ console.error("data-prod inválido", e); return; }

                        document.getElementById("modalVistaProducto").style.display = "flex";
                        window.dispatchEvent(new Event("resetZoom"));

                        // ── Nombre ──────────────────────────────────────────
                        document.getElementById("tituloProducto").textContent = data.nombre || "";

                        // ── Badge oferta ─────────────────────────────────────
                        const badgeOferta = document.getElementById("vistaBadgeOferta");
                        if(data.enOferta && data.descuento > 0){
                            badgeOferta.textContent = `🏷 -${data.descuento}% OFERTA`;
                            badgeOferta.style.display = "inline-flex";
                        } else {
                            badgeOferta.style.display = "none";
                        }

                        // ── Precio ───────────────────────────────────────────
                        const precio = Number(data.precio);
                        const precioOrigEl = document.getElementById("vistaPrecioOriginal");
                        const precioFinalEl = document.getElementById("vistaPrecioFinal");
                        if(data.enOferta && data.descuento > 0){
                            const final = precio * (1 - data.descuento / 100);
                            precioOrigEl.textContent  = "$" + precio.toLocaleString("es-CO");
                            precioOrigEl.style.display = "inline";
                            precioFinalEl.textContent = "$" + Math.round(final).toLocaleString("es-CO");
                            precioFinalEl.style.color = "#ea580c";
                        } else {
                            precioOrigEl.style.display = "none";
                            precioFinalEl.textContent = "$" + precio.toLocaleString("es-CO");
                            precioFinalEl.style.color = "#1e293b";
                        }

                        // ── Ubicación, Categoría, Subcategoría ───────────────
                        document.getElementById("vistaUbicacion").innerHTML =
                            `<i class="bi bi-geo-alt-fill"></i> ${data.ubicacion || "Sin ubicación"}`;
                        document.getElementById("vistaCategoria").innerHTML =
                            `<i class="bi bi-bookmark-fill"></i> ${data.nombreCat || "Sin categoría"}`;
                        const subcatEl = document.getElementById("vistaSubcat");
                        if(data.nombreSubcat && data.nombreSubcat !== "Sin subcategoría"){
                            subcatEl.innerHTML = `<i class="bi bi-diagram-3-fill"></i> ${data.nombreSubcat}`;
                            subcatEl.style.display = "inline-flex";
                        } else {
                            subcatEl.style.display = "none";
                        }

                        // ── Stock ────────────────────────────────────────────
                        const disp  = data.stockDisponible || 0;
                        const total = data.stockTotal || 0;
                        const dispEl  = document.getElementById("vistaStockDisp");
                        const totalEl = document.getElementById("vistaStockTotal");
                        if(disp > 0){
                            dispEl.style.cssText += ";background:#f0fdf4;border:1px solid #bbf7d0;color:#16a34a;";
                            dispEl.innerHTML = `<i class="bi bi-check-circle-fill"></i> ${disp} disponible${disp!==1?"s":""}`;
                        } else {
                            dispEl.style.cssText += ";background:#fff7ed;border:1px solid #fed7aa;color:#c2410c;";
                            dispEl.innerHTML = `<i class="bi bi-x-circle-fill"></i> Agotado`;
                        }
                        totalEl.innerHTML = `<i class="bi bi-boxes"></i> ${total} en total`;

                        // ── Descripción ──────────────────────────────────────
                        document.getElementById("descripcionProducto").textContent = data.descripcion || "Sin descripción.";

                        // ── WhatsApp ─────────────────────────────────────────
                        const numero  = "<?php echo htmlspecialchars($whatsappNum); ?>";
                        const mensaje = `Hola, estoy interesado en el producto ${data.nombre}`;
                        document.getElementById("btnWhatsapp").href =
                            `https://wa.me/${numero}?text=${encodeURIComponent(mensaje)}`;

                        // ── Imágenes ─────────────────────────────────────────
                        const contMini     = document.getElementById("miniaturas");
                        const imgPrincipal = document.getElementById("imagenPrincipalVista");
                        contMini.innerHTML = "";

                        const imagenes = data.imagenes || [];
                        if(imagenes.length > 0){
                            imgPrincipal.src = imagenes[0].rutaImagen;
                            imagenes.forEach((img, idx) => {
                                const mini = document.createElement("img");
                                mini.src = img.rutaImagen;
                                if(idx === 0) mini.classList.add("miniatura-activa");
                                mini.addEventListener("click", () => {
                                    imgPrincipal.src = img.rutaImagen;
                                    contMini.querySelectorAll("img").forEach(m => m.classList.remove("miniatura-activa"));
                                    mini.classList.add("miniatura-activa");
                                    window.dispatchEvent(new Event("resetZoom"));
                                });
                                contMini.appendChild(mini);
                            });
                        } else {
                            imgPrincipal.src = "";
                        }
                    }
                 </script>

                <!-- editar -->
                 <script>
                    document.querySelectorAll(".btn-editar").forEach(btn => {
                        btn.addEventListener("click", function(e){
                            e.stopPropagation();
                            const card = this.closest(".card-producto");
                            const id   = card.dataset.id;
                            editarProducto(id, card);
                        });
                    });

                    function editarProducto(id, card){
                        if(!card) card = document.getElementById("producto-" + id);
                        let data;
                        try { data = JSON.parse(card.dataset.prod); }
                        catch(e){ console.error("data-prod inválido", e); return; }

                        // Abrir modal
                        const modal = document.getElementById("modalProducto");
                        modal.querySelector("h2").textContent = "Editar Producto";
                        modal.style.display = "flex";

                        // Ocultar campo cantidad (solo al crear)
                        const wrapCant = document.getElementById("wrapCantidad");
                        if(wrapCant) wrapCant.style.display = "none";

                        // Llenar campos básicos
                        document.querySelector("[name='nombre']").value      = data.nombre      || "";
                        document.querySelector("[name='precio']").value      = data.precio      || "";
                        document.querySelector("[name='descripcion']").value = data.descripcion || "";
                        // Pre-cargar ubicación con Departamento → Municipio
                        if(typeof preCargarUbicacion === "function" && data.idMunicipio && data.idDepartamento){
                            preCargarUbicacion(data.idMunicipio, data.idDepartamento);
                        }

                        // Categoría + subcategorías
                        const selCat = document.getElementById("selectCategoriaProducto");
                        selCat.value = data.idCat || "";
                        if(data.idCat){
                            const divSubcat = document.getElementById("subcatCheckboxes");
                            divSubcat.innerHTML = '<span style="color:#94a3b8;font-size:13px"><i class="bi bi-arrow-repeat"></i> Cargando...</span>';
                            fetch(`<?= SITE_URL ?>/api/subcategorias/por-categoria?idCategoria=${data.idCat}`)
                            .then(r => r.json())
                            .then(subs => {
                                if(!subs.length){
                                    divSubcat.innerHTML = '<span style="color:#94a3b8;font-size:13px">Sin subcategorías</span>';
                                    return;
                                }
                                window._renderSubcats(subs, data.subcats || []);
                            })
                            .catch(() => { divSubcat.innerHTML = '<span style="color:#dc2626;font-size:13px">Error</span>'; });
                        }

                        // Oferta
                        const chkOferta = document.getElementById("chkEnOferta");
                        const inputDesc = document.getElementById("inputDescuento");
                        chkOferta.checked = !!data.enOferta;
                        inputDesc.value   = data.descuento || 10;
                        document.getElementById("wrapDescuento").style.display = data.enOferta ? "" : "none";
                        actualizarPreviewOferta();

                        // Guardar id en el form
                        document.getElementById("formProducto").dataset.id = id;

                        // Resetear imágenes nuevas y cargar existentes
                        imagenes = [];
                        cargarImagenesExistentes(data.imagenes || []);
                    }

                    function cargarImagenesExistentes(imagenesExist){
                        const preview = document.getElementById("previewImagenes");
                        preview.innerHTML = "";
                        imagenesExist.forEach(img => {
                            const div = document.createElement("div");
                            div.classList.add("img-preview");
                            div.innerHTML = `
                                <img src="${img.rutaImagen}">
                                <button type="button" class="eliminar-img" data-id="${img.idImagen}" title="Eliminar imagen">✕</button>
                            `;
                            div.querySelector(".eliminar-img").addEventListener("click", (e) => {
                                e.stopPropagation();
                                eliminarImagen(img.idImagen, div);
                            });
                            preview.appendChild(div);
                        });
                    }

                    function eliminarImagen(idImagen, elemento){
                        const btn = elemento.querySelector(".eliminar-img");
                        if(btn.dataset.confirmando === "1"){
                            btn.textContent = "...";
                            btn.disabled = true;
                            fetch("<?= SITE_URL ?>/api/productos/eliminar-imagen", {
                                method: "POST",
                                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                                body: "id=" + idImagen
                            })
                            .then(() => elemento.remove());
                        } else {
                            btn.dataset.confirmando = "1";
                            btn.textContent = "?";
                            btn.style.background = "#f57c00";
                            setTimeout(() => {
                                if(btn.dataset.confirmando === "1"){
                                    btn.dataset.confirmando = "0";
                                    btn.textContent = "✕";
                                    btn.style.background = "";
                                }
                            }, 2500);
                        }
                    }

                    // Redimensiona y convierte a JPEG en el navegador antes de subir.
                    // Objetivo: producir un blob < 1.5 MB para que el servidor lo mueva
                    // directamente sin pasar por GD (camino rápido).
                    // Si el primer intento supera el límite se recomprime a menor calidad.
                    const CLIENTE_MAX_BYTES = 1_400_000; // 1.4 MB — margen bajo el umbral del servidor

                    function redimensionarImagenCliente(file, maxPx = 1200, quality = 0.82) {
                        return new Promise((resolve) => {
                            if (!file.type.startsWith("image/")) { resolve(file); return; }
                            if (!document.createElement("canvas").getContext) { resolve(file); return; }

                            const img = new Image();
                            const url = URL.createObjectURL(file);
                            img.onload = () => {
                                URL.revokeObjectURL(url);
                                let { width, height } = img;
                                if (width > maxPx || height > maxPx) {
                                    if (width >= height) { height = Math.round(height * maxPx / width); width = maxPx; }
                                    else                 { width  = Math.round(width  * maxPx / height); height = maxPx; }
                                }
                                const canvas = document.createElement("canvas");
                                canvas.width = width; canvas.height = height;
                                const ctx = canvas.getContext("2d");
                                ctx.fillStyle = "#ffffff";
                                ctx.fillRect(0, 0, width, height);
                                ctx.drawImage(img, 0, 0, width, height);

                                // Primer intento con la calidad pedida
                                canvas.toBlob(blob => {
                                    if (!blob) { resolve(file); return; }
                                    // Si ya es pequeño, listo
                                    if (blob.size <= CLIENTE_MAX_BYTES) { resolve(blob); return; }
                                    // Segundo intento: bajar calidad hasta que quepa
                                    const q2 = Math.max(0.60, quality - 0.15);
                                    canvas.toBlob(blob2 => resolve(blob2 || blob), "image/jpeg", q2);
                                }, "image/jpeg", quality);
                            };
                            img.onerror = () => { URL.revokeObjectURL(url); resolve(file); };
                            img.src = url;
                        });
                    }

                    // Alias para compatibilidad con el bloque de importacion que usa comprimirImagen
                    function comprimirImagen(file, maxPx = 1200, quality = 0.82) {
                        return redimensionarImagenCliente(file, maxPx, quality);
                    }

                    document.getElementById("cantidadUnidades").addEventListener("input", function(){
                        const msg = document.getElementById("msgCantidadUnidades");
                        if(parseInt(this.value) > 1000){ this.value = 1000; msg.style.display = "block"; }
                        else msg.style.display = "none";
                    });

                    document.getElementById("formProducto").addEventListener("submit", async function(e){
                        e.preventDefault();
                        const cantInput = document.getElementById("cantidadUnidades");
                        if(cantInput && parseInt(cantInput.value) > 1000){
                            cantInput.value = 1000;
                            document.getElementById("msgCantidadUnidades").style.display = "block";
                            cantInput.focus();
                            return;
                        }
                        const btn = this.querySelector("button[type='submit']");
                        if (btn.disabled) return;
                        btn.disabled = true;
                        btn.textContent = imagenes.length > 0 ? "Procesando..." : "Guardando...";

                        const form = this;
                        const id   = form.dataset.id;
                        const url  = id ? "<?= SITE_URL ?>/api/productos/actualizar" : "<?= SITE_URL ?>/api/productos/crear";

                        // Barra de progreso inline
                        let barraContenedor = document.getElementById("_barraGuardar");
                        if (!barraContenedor) {
                            barraContenedor = document.createElement("div");
                            barraContenedor.id = "_barraGuardar";
                            barraContenedor.style.cssText = "margin:8px 0;height:6px;background:#e0e0e0;border-radius:4px;overflow:hidden";
                            barraContenedor.innerHTML = '<div id="_barraGuardarFill" style="height:100%;width:0;background:#4CAF50;transition:width .3s"></div>';
                            btn.parentNode.insertBefore(barraContenedor, btn);
                        }
                        const fill = document.getElementById("_barraGuardarFill");

                        try {
                            // Paso 1: comprimir todas las imágenes nuevas en paralelo (CPU cliente)
                            fill.style.width = "15%";
                            const comprimidas = await Promise.all(
                                imagenes.map(f => redimensionarImagenCliente(f))
                            );

                            // Paso 2: guardar datos del producto (sin imágenes) — rápido
                            btn.textContent = "Guardando...";
                            fill.style.width = "40%";

                            const formData = new FormData(form);
                            if (id) formData.append("idProducto", id);
                            // Quitar cualquier campo de imagen del form por si acaso
                            formData.delete("imagenes[]");

                            const res  = await fetch(url, { method: "POST", body: formData });
                            const data = await res.json();

                            if (!data.ok) {
                                toast.error(data.error || "Error al guardar");
                                btn.disabled = false;
                                btn.textContent = "Guardar";
                                barraContenedor.remove();
                                return;
                            }

                            fill.style.width = "60%";

                            // Paso 3: subir cada imagen en paralelo — cada una en su propio request
                            // El servidor procesa todas simultáneamente en procesos PHP separados
                            const idProducto = data.idProducto;
                            if (comprimidas.length > 0) {
                                btn.textContent = `Subiendo ${comprimidas.length} imagen${comprimidas.length > 1 ? 'es' : ''}...`;
                                let subidas = 0;
                                await Promise.all(comprimidas.map((blob, i) => {
                                    const fd = new FormData();
                                    fd.append("idProducto", idProducto);
                                    fd.append("orden", i);
                                    fd.append("imagen", blob, "img" + i + ".jpg");
                                    return fetch("<?= SITE_URL ?>/api/productos/subir-imagen", { method: "POST", body: fd })
                                        .then(r => r.json())
                                        .then(() => {
                                            subidas++;
                                            fill.style.width = (60 + Math.round(subidas / comprimidas.length * 35)) + "%";
                                        });
                                }));
                            }

                            fill.style.width = "100%";
                            toast.success("Guardado correctamente");
                            location.reload();
                        } catch {
                            btn.disabled = false;
                            btn.textContent = "Guardar";
                            barraContenedor.remove();
                        }
                    });
                 </script>
                


                <!-- mostrar boton categoria -->

                <script>
                        document.addEventListener("DOMContentLoaded", function(){

                            document.querySelectorAll(".flechaToggle").forEach(flecha => {

                                flecha.addEventListener("click", function(e){
                                    e.stopPropagation();

                                    let productos = this.closest(".nameCatArriba").nextElementSibling;

                                    productos.classList.toggle("activo");

                                    this.classList.toggle("rotar");
                                });

                            });

                        });
                    </script>


                <!-- Silder -->

                <script>

                    function eliminarProducto(id){
                        if(!confirm("¿Estás seguro de eliminar este producto?")) return;

                        fetch("<?= SITE_URL ?>/api/productos/eliminar", {
                            method: "POST",
                            headers: { "Content-Type": "application/x-www-form-urlencoded" },
                            body: "id=" + id
                        })
                        .then(res => res.json())
                        .then(data => {
                            if(data.ok){
                                toast.success("Producto eliminado correctamente");
                                setTimeout(() => location.reload(), 1200);
                            } else {
                                toast.error(data.error || "Error al eliminar");
                            }
                        })
                        .catch(() => toast.error("Error de conexión al eliminar"));
                    }

                    document.querySelectorAll(".card-producto").forEach(card => {

                        const slider = card.querySelector(".slider-producto");
                        if(!slider) return;

                        let slides = slider.querySelectorAll(".slide-producto");
                        let index = 0;
                        let interval = null;

                        // Mostrar solo la principal
                        slides.forEach((s,i)=>{
                            s.classList.toggle("active", i === 0);
                        });

                        card.addEventListener("mouseenter", () => {
                            if(slides.length <= 1) return;

                            interval = setInterval(() => {
                                slides[index].classList.remove("active");
                                index = (index + 1) % slides.length;
                                slides[index].classList.add("active");
                            }, 1100);
                        });

                        card.addEventListener("mouseleave", () => {
                            clearInterval(interval);

                            slides.forEach((s,i)=>{
                                s.classList.toggle("active", i === 0);
                            });

                            index = 0;
                        });

                    });


                    window.addEventListener("click", (e) => {
                        const modal = document.getElementById("modalProducto");
                        if(e.target === modal){
                            modal.style.display = "none";
                        }
                    });

                    function cerrarModal(){
                        document.getElementById("modalProducto").style.display = "none";
                    }

                    /* ── Toggle oferta ───────────────────────────── */
                    (function(){
                        const chk       = document.getElementById("chkEnOferta");
                        const wrap      = document.getElementById("wrapDescuento");
                        const inputDisc = document.getElementById("inputDescuento");
                        const precioInp = document.querySelector("[name='precio']");

                        chk.addEventListener("change", function(){
                            wrap.style.display = this.checked ? "" : "none";
                            actualizarPreviewOferta();
                        });
                        inputDisc.addEventListener("input", actualizarPreviewOferta);
                        precioInp.addEventListener("input", actualizarPreviewOferta);
                        precioInp.addEventListener("input", function(){
                            const v = parseFloat(this.value);
                            if(v > 999999999) this.value = 999999999;
                            if(v < 0) this.value = 0;
                        });
                    })();

                    function actualizarPreviewOferta(){
                        const chk       = document.getElementById("chkEnOferta");
                        const inputDisc = document.getElementById("inputDescuento");
                        const precioInp = document.querySelector("[name='precio']");
                        const preview   = document.getElementById("precioPreviewOferta");
                        if(!chk.checked){ preview.innerHTML = ""; return; }
                        const precio = parseFloat(precioInp.value) || 0;
                        const desc   = parseFloat(inputDisc.value) || 0;
                        if(!precio){ preview.innerHTML = ""; return; }
                        const final = precio * (1 - desc / 100);
                        preview.innerHTML = `
                            <span class="precio-tachado">$${precio.toLocaleString("es-CO")}</span>
                            <i class="bi bi-arrow-right" style="font-size:11px;color:#94a3b8;"></i>
                            <span class="precio-final">$${Math.round(final).toLocaleString("es-CO")}</span>
                            <span style="color:#94a3b8;">(−${desc}%)</span>`;
                    }

                    /* agregar varias imagenes */

                    const inputImagenes = document.getElementById("imagenes");
                    const preview = document.getElementById("previewImagenes");
                    const dropZone = document.getElementById("dropZoneImagenes");

                    let imagenes = [];

                    inputImagenes.addEventListener("change", (e) => {
                        agregarArchivos(Array.from(e.target.files));
                        inputImagenes.value = "";
                    });

                    dropZone.addEventListener("dragover", (e) => {
                        e.preventDefault();
                        dropZone.classList.add("drag-over");
                    });
                    dropZone.addEventListener("dragleave", () => {
                        dropZone.classList.remove("drag-over");
                    });
                    dropZone.addEventListener("drop", (e) => {
                        e.preventDefault();
                        dropZone.classList.remove("drag-over");
                        const archivos = Array.from(e.dataTransfer.files).filter(f => f.type.startsWith("image/"));
                        agregarArchivos(archivos);
                    });

                    function agregarArchivos(nuevos){
                        imagenes = imagenes.concat(nuevos);
                        renderPreview();
                    }

                    function renderPreview(){

                        preview.innerHTML = "";

                        imagenes.forEach((file, index) => {

                            let reader = new FileReader();

                            reader.onload = function(e){

                                let div = document.createElement("div");
                                div.classList.add("img-preview");
                                div.draggable = true;
                                div.dataset.index = index;

                                div.innerHTML = `
                                    <img src="${e.target.result}">
                                    <span>#${index + 1}</span>
                                `;

                                // Drag
                                div.addEventListener("dragstart", dragStart);
                                div.addEventListener("dragover", dragOver);
                                div.addEventListener("drop", drop);

                                preview.appendChild(div);
                            };

                            reader.readAsDataURL(file);
                        });
                    }

                    // Drag logic
                    let dragIndex;

                    function dragStart(e){
                        dragIndex = this.dataset.index;
                    }

                    function dragOver(e){
                        e.preventDefault();
                    }

                    function drop(e){
                        let dropIndex = this.dataset.index;

                        let temp = imagenes[dragIndex];
                        imagenes[dragIndex] = imagenes[dropIndex];
                        imagenes[dropIndex] = temp;

                        renderPreview();
                    }

                    document.getElementById("btnAgregarPanel").addEventListener("click", () => {
                        const modal = document.getElementById("modalProducto");
                        modal.querySelector("h2").textContent = "Agregar Producto";
                        modal.style.display = "flex";

                        const form = document.getElementById("formProducto");
                        form.reset();
                        delete form.dataset.id;
                        imagenes = [];
                        preview.innerHTML = "";

                        // Mostrar campo cantidad al crear
                        const wrapCant = document.getElementById("wrapCantidad");
                        if(wrapCant) wrapCant.style.display = "";

                        // Resetear oferta
                        document.getElementById("chkEnOferta").checked = false;
                        document.getElementById("wrapDescuento").style.display = "none";
                        document.getElementById("precioPreviewOferta").innerHTML = "";
                    });
                </script>
            </div>


            

         </div>

        <!-- ══ SCRIPT SELECCIÓN MÚLTIPLE PRODUCTOS ════════════ -->
        <script>
        (function(){
            const barra          = document.getElementById("seleccionBarraProd");
            const contador       = document.getElementById("seleccionContadorProd");
            const btnEliminarSel = document.getElementById("btnEliminarSelProd");
            const btnSelTodo     = document.getElementById("btnSelTodoProd");
            const btnSelNinguna  = document.getElementById("btnSelNingunaProd");
            const btnCancelarSel = document.getElementById("btnCancelarSelProd");
            const btnActivar     = document.getElementById("btnActivarSeleccionProd");

            const modalConfirm   = document.getElementById("modalConfirmElimProd");
            const confirmarNum   = document.getElementById("confirmarNumeroProd");
            const confirmarBtn   = document.getElementById("confirmarEliminarProd");
            const cancelarBtn    = document.getElementById("confirmarCancelarProd");

            let modoSeleccion = false;

            function getCheckboxes(){
                return document.querySelectorAll(".prod-checkbox");
            }

            function getSeleccionados(){
                return [...document.querySelectorAll(".prod-checkbox:checked")];
            }

            function actualizarContador(){
                const n = getSeleccionados().length;
                contador.textContent = n + " producto" + (n !== 1 ? "s" : "") + " seleccionado" + (n !== 1 ? "s" : "");
                btnEliminarSel.disabled = n === 0;
            }

            const filtroBarra = document.querySelector(".filtro-barra");

            function getHeaderH(){
                return document.querySelector(".head")?.offsetHeight || 0;
            }

            function ajustarStickyBarra(){
                const hHead = getHeaderH();
                barra.style.top = hHead + "px";
                if(filtroBarra){
                    // Solo en móvil el filtro-barra es sticky; en desktop no necesita ajuste
                    if(window.matchMedia("(max-width:768px)").matches){
                        filtroBarra.style.top = (hHead + barra.offsetHeight) + "px";
                    } else {
                        filtroBarra.style.top = "";
                    }
                }
            }

            function activarModo(){
                modoSeleccion = true;
                document.querySelectorAll(".CartaProducto").forEach(c => c.classList.add("modo-seleccion-prod"));
                barra.classList.add("visible");
                document.getElementById("panelLateral").classList.remove("activo");
                document.getElementById("overlay").classList.remove("activo");
                actualizarContador();
                // Ajustar sticky después de que la animación termine
                setTimeout(ajustarStickyBarra, 400);
            }

            function desactivarModo(){
                modoSeleccion = false;
                document.querySelectorAll(".CartaProducto").forEach(c => c.classList.remove("modo-seleccion-prod"));
                barra.classList.remove("visible");
                if(filtroBarra) filtroBarra.style.top = "";
                getCheckboxes().forEach(cb => {
                    cb.checked = false;
                    cb.closest(".card-producto")?.classList.remove("prod-seleccionada");
                });
                actualizarContador();
            }

            btnActivar.addEventListener("click", activarModo);
            btnCancelarSel.addEventListener("click", desactivarModo);

            btnSelTodo.addEventListener("click", () => {
                // Solo los visibles (filtro activo)
                document.querySelectorAll(".card-producto").forEach(card => {
                    if(card.style.display === "none") return;
                    const cb = card.querySelector(".prod-checkbox");
                    if(cb){ cb.checked = true; card.classList.add("prod-seleccionada"); }
                });
                actualizarContador();
            });

            btnSelNinguna.addEventListener("click", () => {
                getCheckboxes().forEach(cb => {
                    cb.checked = false;
                    cb.closest(".card-producto")?.classList.remove("prod-seleccionada");
                });
                actualizarContador();
            });

            // Cambio directo en checkbox
            document.addEventListener("change", function(e){
                if(!e.target.classList.contains("prod-checkbox")) return;
                e.target.closest(".card-producto")?.classList.toggle("prod-seleccionada", e.target.checked);
                actualizarContador();
            });

            // Click en card activa el checkbox en modo selección
            document.addEventListener("click", function(e){
                if(!modoSeleccion) return;
                const card = e.target.closest(".card-producto");
                if(!card) return;
                if(e.target.closest(".acciones-producto") || e.target.closest(".prod-checkbox-wrap")) return;
                const cb = card.querySelector(".prod-checkbox");
                if(!cb) return;
                cb.checked = !cb.checked;
                card.classList.toggle("prod-seleccionada", cb.checked);
                actualizarContador();
            });

            // Abrir modal confirmación
            btnEliminarSel.addEventListener("click", () => {
                confirmarNum.textContent = getSeleccionados().length;
                modalConfirm.style.display = "flex";
            });

            cancelarBtn.addEventListener("click", () => { modalConfirm.style.display = "none"; });
            window.addEventListener("click", e => {
                if(e.target === modalConfirm) modalConfirm.style.display = "none";
            });

            // Ejecutar eliminación
            confirmarBtn.addEventListener("click", function(){
                const ids = getSeleccionados().map(cb => cb.value);
                confirmarBtn.disabled = true;
                confirmarBtn.innerHTML = '<i class="bi bi-arrow-repeat spin"></i> Eliminando...';

                const fd = new FormData();
                ids.forEach(id => fd.append("ids[]", id));

                fetch("<?= SITE_URL ?>/api/productos/eliminar-varios", { method:"POST", body: fd })
                .then(r => r.json())
                .then(data => {
                    modalConfirm.style.display = "none";
                    if(data.status === "success"){
                        ids.forEach(id => {
                            const card = document.querySelector(`.card-producto[data-id="${id}"]`);
                            if(card){
                                card.style.transition = "transform 0.3s ease, opacity 0.3s ease";
                                card.style.transform  = "scale(0.8)";
                                card.style.opacity    = "0";
                            }
                        });
                        setTimeout(() => location.reload(), 400);
                    } else {
                        toast.error(data.message);
                        confirmarBtn.disabled = false;
                        confirmarBtn.innerHTML = '<i class="bi bi-trash-fill"></i> Sí, eliminar';
                    }
                })
                .catch(() => {
                    toast.error("Error de conexión");
                    confirmarBtn.disabled = false;
                    confirmarBtn.innerHTML = '<i class="bi bi-trash-fill"></i> Sí, eliminar';
                    modalConfirm.style.display = "none";
                });
            });
        })();
        </script>

        <!-- ══ MODAL INVENTARIO POR PRODUCTO ══════════════════ -->
        <div class="modal" id="modalItemsProducto" style="display:none;">
            <div class="modal-inv-contenido" style="position:relative;">

                <button class="modal-inv-cerrar" onclick="cerrarItemsModal()">&times;</button>

                <!-- Cabecera -->
                <div class="modal-inv-header">
                    <h3 id="modalInvTitulo">Inventario</h3>
                    <div class="modal-inv-stats">
                        <span class="inv-stat inv-stat-disp">
                            <i class="bi bi-check-circle-fill"></i>
                            <span id="invCountDisp">0</span> disponibles
                        </span>
                        <span class="inv-stat inv-stat-vend">
                            <i class="bi bi-bag-check-fill"></i>
                            <span id="invCountVend">0</span> vendidos
                        </span>
                        <span class="inv-stat inv-stat-total">
                            <i class="bi bi-boxes"></i>
                            <span id="invCountTotal">0</span> total
                        </span>
                    </div>
                </div>

                <!-- Agregar unidades -->
                <div class="modal-inv-agregar">
                    <input type="number" id="invCantidadNueva" min="1" max="1000" value="1" placeholder="Cant.">
                    <button class="btn-inv-add" id="btnInvAgregar" onclick="agregarItemsDesdeModal()">
                        <i class="bi bi-plus-lg"></i> Agregar unidades
                    </button>
                </div>

                <!-- Barra: búsqueda + botón selección -->
                <div class="modal-inv-toolbar" style="padding-bottom:8px;">
                    <div class="inv-search-wrap">
                        <i class="bi bi-search"></i>
                        <input type="text" id="invBuscador" placeholder="Buscar por número de serie…" oninput="filtrarItemsInv()">
                    </div>
                    <button class="btn-inv-seleccion" id="btnInvSeleccion" onclick="toggleModoSeleccion()">
                        <i class="bi bi-check2-square"></i> Seleccionar
                    </button>
                </div>

                <!-- Barra de acciones bulk (oculta hasta modo selección) -->
                <div class="modal-inv-bulk" id="invBulkBar">
                    <span class="inv-bulk-info" id="invBulkInfo">0 seleccionados</span>
                    <button class="btn-bulk btn-bulk-todo"    onclick="invSelTodo()"><i class="bi bi-check-all"></i> Todos</button>
                    <button class="btn-bulk btn-bulk-ninguno" onclick="invSelNinguno()"><i class="bi bi-dash-lg"></i> Ninguno</button>
                    <button class="btn-bulk btn-bulk-disp"    id="btnBulkDisp"  onclick="cambiarEstadoBulk('Disponible')" disabled><i class="bi bi-arrow-repeat"></i> → Disponible</button>
                    <button class="btn-bulk btn-bulk-vend"    id="btnBulkVend"  onclick="cambiarEstadoBulk('Vendido')"    disabled><i class="bi bi-arrow-repeat"></i> → Vendido</button>
                    <button class="btn-bulk btn-bulk-del"     id="btnBulkDel"   onclick="eliminarBulk()"                 disabled><i class="bi bi-trash-fill"></i> Eliminar</button>
                </div>

                <!-- Lista de items -->
                <div class="modal-inv-lista" id="listaItemsModal">
                    <div class="inv-empty-msg">
                        <i class="bi bi-arrow-repeat spin"></i> Cargando...
                    </div>
                </div>

            </div>
        </div>

        <script>
        (function(){
            let _invIdProducto = null;
            let _invItems      = [];
            let _modoSel       = false;

            /* ── Abrir / cerrar ─────────────────────── */
            window.verItemsProducto = function(id, nombre){
                _invIdProducto = id;
                document.getElementById("modalInvTitulo").textContent = nombre || "Inventario";
                document.getElementById("modalItemsProducto").style.display = "flex";
                desactivarSeleccion();
                document.getElementById("invBuscador").value = "";
                cargarItemsModal(id);
            };

            window.cerrarItemsModal = function(){
                document.getElementById("modalItemsProducto").style.display = "none";
                desactivarSeleccion();
                _invIdProducto = null;
                _invItems = [];
            };

            window.addEventListener("click", function(e){
                const m = document.getElementById("modalItemsProducto");
                if(e.target === m) cerrarItemsModal();
            });

            /* ── Cargar items ───────────────────────── */
            function cargarItemsModal(id){
                const lista = document.getElementById("listaItemsModal");
                lista.innerHTML = '<div class="inv-empty-msg"><i class="bi bi-arrow-repeat spin"></i> Cargando...</div>';

                fetch(`<?= SITE_URL ?>/api/inventario/items?idProducto=${id}`)
                .then(r => r.json())
                .then(items => {
                    _invItems = items;
                    actualizarStats(items);
                    renderListaItems(items);
                    actualizarBadgeCard(id);
                })
                .catch(() => {
                    lista.innerHTML = '<div class="inv-empty-msg"><i class="bi bi-x-circle"></i> Error al cargar</div>';
                });
            }

            function actualizarStats(items){
                const disp  = items.filter(i => i.estadoItem === "Disponible").length;
                const vend  = items.filter(i => i.estadoItem === "Vendido").length;
                document.getElementById("invCountDisp").textContent  = disp;
                document.getElementById("invCountVend").textContent  = vend;
                document.getElementById("invCountTotal").textContent = items.length;
            }

            function renderListaItems(items){
                const lista = document.getElementById("listaItemsModal");
                if(!items.length){
                    lista.innerHTML = `<div class="inv-empty-msg"><i class="bi bi-box-open"></i>Sin unidades registradas.<br>Agrega algunas arriba.</div>`;
                    return;
                }
                lista.innerHTML = items.map(it => {
                    const esDisp = it.estadoItem === "Disponible";
                    return `<div class="inv-item-row${_modoSel ? ' modo-seleccion' : ''}" id="inv-row-${it.idItemInventario}" data-id="${it.idItemInventario}" data-serie="${it.numeroSerie.toLowerCase()}">
                        <input type="checkbox" class="inv-item-cb" value="${it.idItemInventario}" onchange="onCheckItem(this)">
                        <span class="inv-item-serie">${it.numeroSerie}</span>
                        <span class="inv-item-badge ${esDisp ? 'badge-disponible-item' : 'badge-vendido-item'}">${it.estadoItem}</span>
                        <button class="btn-toggle-estado" title="${esDisp ? 'Marcar como Vendido' : 'Marcar como Disponible'}"
                            onclick="toggleEstadoItem(${it.idItemInventario}, '${esDisp ? 'Vendido' : 'Disponible'}', this)">
                            ${esDisp ? '<i class="bi bi-bag-check"></i> Vender' : '<i class="bi bi-arrow-counterclockwise"></i> Devolver'}
                        </button>
                        <button class="btn-inv-item-del" title="Eliminar" onclick="eliminarItemModal(${it.idItemInventario})">
                            <i class="bi bi-trash-fill"></i>
                        </button>
                    </div>`;
                }).join("");

                // Re-aplicar búsqueda si hay texto
                const term = document.getElementById("invBuscador").value.trim().toLowerCase();
                if(term) filtrarItemsInv();

                // Re-aplicar modo selección si está activo
                if(_modoSel) aplicarClaseSel();
            }

            /* ── Búsqueda ───────────────────────────── */
            window.filtrarItemsInv = function(){
                const term = document.getElementById("invBuscador").value.trim().toLowerCase();
                document.querySelectorAll("#listaItemsModal .inv-item-row").forEach(row => {
                    const serie = row.dataset.serie || "";
                    row.classList.toggle("inv-hidden", term !== "" && !serie.includes(term));
                });
            };

            /* ── Modo selección ─────────────────────── */
            window.toggleModoSeleccion = function(){
                _modoSel ? desactivarSeleccion() : activarSeleccion();
            };

            function activarSeleccion(){
                _modoSel = true;
                document.getElementById("btnInvSeleccion").classList.add("activo");
                document.getElementById("invBulkBar").classList.add("visible");
                aplicarClaseSel();
                actualizarBulkInfo();
            }

            function desactivarSeleccion(){
                _modoSel = false;
                document.getElementById("btnInvSeleccion")?.classList.remove("activo");
                document.getElementById("invBulkBar")?.classList.remove("visible");
                document.querySelectorAll("#listaItemsModal .inv-item-row").forEach(row => {
                    row.classList.remove("modo-seleccion","inv-seleccionado");
                    const cb = row.querySelector(".inv-item-cb");
                    if(cb) cb.checked = false;
                });
                actualizarBulkInfo();
            }

            function aplicarClaseSel(){
                document.querySelectorAll("#listaItemsModal .inv-item-row").forEach(row => {
                    row.classList.add("modo-seleccion");
                });
            }

            function onCheckItem(cb){
                const row = cb.closest(".inv-item-row");
                row.classList.toggle("inv-seleccionado", cb.checked);
                actualizarBulkInfo();
            }
            window.onCheckItem = onCheckItem;

            function getSelIds(){
                return [...document.querySelectorAll("#listaItemsModal .inv-item-cb:checked")].map(cb => cb.value);
            }

            function actualizarBulkInfo(){
                const n = getSelIds().length;
                document.getElementById("invBulkInfo").textContent = n + " seleccionado" + (n !== 1 ? "s" : "");
                const dis = n === 0;
                document.getElementById("btnBulkDisp").disabled = dis;
                document.getElementById("btnBulkVend").disabled = dis;
                document.getElementById("btnBulkDel").disabled  = dis;
            }

            window.invSelTodo = function(){
                document.querySelectorAll("#listaItemsModal .inv-item-row:not(.inv-hidden) .inv-item-cb").forEach(cb => {
                    cb.checked = true;
                    cb.closest(".inv-item-row").classList.add("inv-seleccionado");
                });
                actualizarBulkInfo();
            };

            window.invSelNinguno = function(){
                document.querySelectorAll("#listaItemsModal .inv-item-cb").forEach(cb => {
                    cb.checked = false;
                    cb.closest(".inv-item-row").classList.remove("inv-seleccionado");
                });
                actualizarBulkInfo();
            };

            /* ── Cambiar estado (individual) ────────── */
            window.toggleEstadoItem = function(idItem, nuevoEstado, btn){
                btn.disabled = true;
                const fd = new FormData();
                fd.append("ids[]", idItem);
                fd.append("estadoItem", nuevoEstado);
                fetch("<?= SITE_URL ?>/api/inventario/cambiar-estado", { method:"POST", body: fd })
                .then(r => r.json())
                .then(data => {
                    btn.disabled = false;
                    if(data.status === "success"){
                        // Actualizar ítem en _invItems y re-renderizar
                        const item = _invItems.find(i => i.idItemInventario == idItem);
                        if(item) item.estadoItem = nuevoEstado;
                        actualizarStats(_invItems);
                        renderListaItems(_invItems);
                        actualizarBadgeCard(_invIdProducto);
                    } else {
                        toast.error(data.message || "Error");
                    }
                })
                .catch(() => { btn.disabled = false; toast.error("Error de conexión"); });
            };

            /* ── Cambiar estado (bulk) ──────────────── */
            window.cambiarEstadoBulk = function(nuevoEstado){
                const ids = getSelIds();
                if(!ids.length) return;
                const fd = new FormData();
                ids.forEach(id => fd.append("ids[]", id));
                fd.append("estadoItem", nuevoEstado);

                setButtonsBulkLoading(true);
                fetch("<?= SITE_URL ?>/api/inventario/cambiar-estado", { method:"POST", body: fd })
                .then(r => r.json())
                .then(data => {
                    setButtonsBulkLoading(false);
                    if(data.status === "success"){
                        ids.forEach(id => {
                            const item = _invItems.find(i => i.idItemInventario == id);
                            if(item) item.estadoItem = nuevoEstado;
                        });
                        actualizarStats(_invItems);
                        renderListaItems(_invItems);
                        actualizarBadgeCard(_invIdProducto);
                        // Reactivar modo selección
                        if(_modoSel) aplicarClaseSel();
                    } else {
                        toast.error(data.message || "Error");
                    }
                })
                .catch(() => { setButtonsBulkLoading(false); toast.error("Error de conexión"); });
            };

            function setButtonsBulkLoading(loading){
                ["btnBulkDisp","btnBulkVend","btnBulkDel"].forEach(id => {
                    document.getElementById(id).disabled = loading;
                });
            }

            /* ── Eliminar (individual) ──────────────── */
            window.eliminarItemModal = function(idItem){
                if(!confirm("¿Eliminar esta unidad?")) return;
                const fd = new FormData();
                fd.append("id", idItem);
                fetch("<?= SITE_URL ?>/api/inventario/eliminar", { method:"POST", body: fd })
                .then(r => r.json())
                .then(data => {
                    if(data.status === "success"){
                        _invItems = _invItems.filter(i => i.idItemInventario != idItem);
                        actualizarStats(_invItems);
                        renderListaItems(_invItems);
                        actualizarBadgeCard(_invIdProducto);
                    } else {
                        toast.error(data.message || "Error al eliminar");
                    }
                })
                .catch(() => toast.error("Error de conexión"));
            };

            /* ── Eliminar (bulk) ────────────────────── */
            window.eliminarBulk = function(){
                const ids = getSelIds();
                if(!ids.length) return;
                if(!confirm(`¿Eliminar ${ids.length} unidad(es)? Esta acción no se puede deshacer.`)) return;

                setButtonsBulkLoading(true);
                const promises = ids.map(id => {
                    const fd = new FormData();
                    fd.append("id", id);
                    return fetch("<?= SITE_URL ?>/api/inventario/eliminar", { method:"POST", body: fd }).then(r => r.json());
                });

                Promise.all(promises).then(() => {
                    setButtonsBulkLoading(false);
                    _invItems = _invItems.filter(i => !ids.includes(String(i.idItemInventario)));
                    actualizarStats(_invItems);
                    renderListaItems(_invItems);
                    actualizarBadgeCard(_invIdProducto);
                    if(_modoSel) aplicarClaseSel();
                }).catch(() => { setButtonsBulkLoading(false); toast.error("Error de conexión"); });
            };

            /* ── Agregar unidades ───────────────────── */
            window.agregarItemsDesdeModal = function(){
                if(!_invIdProducto) return;
                const cant = parseInt(document.getElementById("invCantidadNueva").value) || 0;
                if(cant < 1){ toast.warning("Ingresa una cantidad mayor a 0"); return; }
                if(cant > 1000){ toast.warning("El máximo por operación es 1000 unidades"); document.getElementById("invCantidadNueva").value = 1000; return; }
                const btn = document.getElementById("btnInvAgregar");
                btn.disabled = true;
                btn.innerHTML = '<i class="bi bi-arrow-repeat spin"></i> Agregando...';

                const fd = new FormData();
                fd.append("idProducto", _invIdProducto);
                fd.append("cantidad", cant);
                fd.append("estadoItem", "Disponible");

                fetch("<?= SITE_URL ?>/api/inventario/agregar-masivo", { method:"POST", body: fd })
                .then(r => r.json())
                .then(data => {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="bi bi-plus-lg"></i> Agregar unidades';
                    if(data.status === "success"){
                        document.getElementById("invCantidadNueva").value = 1;
                        cargarItemsModal(_invIdProducto);
                    } else {
                        toast.error(data.message || "Error al agregar");
                    }
                })
                .catch(() => {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="bi bi-plus-lg"></i> Agregar unidades';
                    toast.error("Error de conexión");
                });
            };

            /* ── Actualizar badges en la card ───────── */
            function actualizarBadgeCard(idProducto){
                const disp  = _invItems.filter(i => i.estadoItem === "Disponible").length;
                const total = _invItems.length;
                const card  = document.querySelector(`.card-producto[data-id="${idProducto}"]`);
                if(!card) return;
                const badge = card.querySelector(".card-estado-badge");
                if(badge){
                    badge.className = "card-estado-badge " + (disp > 0 ? "badge-disponible" : "badge-agotado");
                    badge.textContent = disp > 0 ? disp + " disponible" + (disp > 1 ? "s" : "") : "Agotado";
                }
                const pill = card.querySelector(".card-stock-pill");
                if(pill){
                    if(total > 0){
                        pill.className = "card-stock-pill" + (disp === 0 ? " agotado" : "");
                        pill.innerHTML = `<i class="bi bi-box-seam-fill"></i> ${disp}/${total}`;
                    } else {
                        pill.className = "card-stock-pill sin-stock";
                        pill.innerHTML = `<i class="bi bi-box"></i> Sin stock`;
                    }
                }
            }
        })();
        </script>

        <!-- Footer -->
        <div class="footer">
            <div class="footer-container">

                <div class="footer-brand">
                    <h3>Compra y Listo</h3>
                    <p>La forma más fácil de comprar y vender en todo el mundo</p>
                </div>

                <div class="footer-contacto">
                    <h4>Contacto</h4>
                    <a href="mailto:compraylisto24@gmail.com">
                        <i class="bi bi-envelope-fill"></i> compraylisto24@gmail.com
                    </a>
                    <a href="https://wa.me/573123048308" target="_blank">
                        <i class="bi bi-telephone-fill"></i> 312 304 8308
                    </a>
                    <span>
                        <i class="bi bi-geo-alt-fill"></i> Florencia, Caquetá
                    </span>
                </div>

                <div class="footer-social">
                    <h4>Síguenos</h4>
                    <div class="footer-social-iconos">
                        <a href="https://www.facebook.com/share/1DCnyAL7zk/?mibextid=wwXIfr" target="_blank" class="fb" title="Facebook">
                            <i class="bi bi-facebook"></i>
                        </a>
                        <a href="https://www.tiktok.com/@compraylisto04?_r=1&_t=ZS-95LjiKiZoNG" target="_blank" class="tt" title="TikTok">
                            <i class="bi bi-tiktok"></i>
                        </a>
                        <a href="https://wa.me/573123048308" target="_blank" class="wa" title="WhatsApp">
                            <i class="bi bi-whatsapp"></i>
                        </a>
                    </div>
                </div>

            </div>
            <div class="footer-bottom">
                <p>© 2026 Compra y Listo | Desarrollado por <a href="#" target="_blank">Ibsen Soto</a> | v1.3.0</p>
            </div>
        </div>

    </div>
<script src="<?= SITE_URL ?>/assets/toast.js"></script>
<script>
/* ── Visibilidad en tiempo real ────────────────────────────────
   Al volver a este tab (visibilitychange) o cada 30 s, consulta
   los estados de categorías y el stock para actualizar las
   tarjetas sin recargar la página.
──────────────────────────────────────────────────────────── */
(function() {
    const SITE = <?= json_encode(SITE_URL) ?>;

    function aplicarVisibilidad(estadosCat, stockMap) {
        document.querySelectorAll('.card-producto').forEach(card => {
            const idCat     = parseInt(card.dataset.categoria) || 0;
            const idProd    = parseInt(card.dataset.id) || 0;
            const catOculta = (estadosCat[idCat] ?? 'Activo') === 'Oculto';
            const disponible = stockMap !== null
                ? (stockMap[idProd] ?? 0)
                : (() => { const p = JSON.parse(card.dataset.prod || '{}'); return parseInt(p.stockDisponible) || 0; })();
            const sinStock  = disponible === 0;
            const oculto    = catOculta || sinStock;

            card.classList.toggle('card-oculta-pub', oculto);

            // Actualizar o crear la franja
            let strip = card.querySelector('.card-visibilidad-strip');
            if (!oculto) { strip && strip.remove(); return; }

            if (!strip) {
                strip = document.createElement('div');
                strip.className = 'card-visibilidad-strip';
                const invRow = card.querySelector('.card-inv-row');
                if (invRow) invRow.before(strip);
            }

            const razones = [];
            if (catOculta) razones.push('<span class="card-razon-pill razon-cat"><i class="bi bi-tag-fill"></i> Categoría oculta</span>');
            if (sinStock)  razones.push('<span class="card-razon-pill razon-stock"><i class="bi bi-box"></i> Sin unidades</span>');

            strip.innerHTML = `<i class="bi bi-eye-slash-fill"></i><span>Oculto al público</span>${razones.join('')}`;
        });
    }

    function refrescarEstados() {
        Promise.all([
            fetch(`${SITE}/api/categorias/estados`).then(r => r.json()),
            fetch(`${SITE}/api/productos/stock`).then(r => r.json())
        ])
        .then(([cats, stock]) => aplicarVisibilidad(cats, stock))
        .catch(() => {});
    }

    // Al volver a este tab
    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'visible') refrescarEstados();
    });

    // Polling cada 30 s (por si cambia en la misma sesión)
    setInterval(refrescarEstados, 30_000);
})();

// ── Auto-ajuste de fuente en precios de cards ─────────────────
function ajustarFuentePrecios() {
    document.querySelectorAll('.card-precio').forEach(el => {
        el.style.fontSize = '';
        let size = parseFloat(getComputedStyle(el).fontSize) || 17;
        while (el.scrollWidth > el.clientWidth && size > 10) {
            size -= 0.5;
            el.style.fontSize = size + 'px';
        }
    });
}
document.addEventListener('DOMContentLoaded', ajustarFuentePrecios);
window.addEventListener('resize', ajustarFuentePrecios);
</script>
</body>
</html>

