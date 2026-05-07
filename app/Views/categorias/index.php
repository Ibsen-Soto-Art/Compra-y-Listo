<?php
ob_start();
    session_start();
    include(ROOT_PATH . "/config/conection.php");
    $con = conection();

    if(!isset($_SESSION['usuarios'])){
        header("location:" . SITE_URL . "/auth/login.php");
        exit();
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="Author" content="Ibsen Alexis Soto Artunduaga">
    <meta name="keywords" content="compras, ventas, nuevo, usado">
    <meta name="Description" content="Página web diseñada para facilitar la compra y venta de productos nuevos y usados de manera rápida y sencilla.">
    <title>Compra y Listo</title>
    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
    <link rel="preload" href="../../assets/styleAll.min.css" as="style">
    <link rel="preload" href="../../assets/mobile-admin.min.css" as="style">
    <link rel="stylesheet" href="../../assets/styleAll.min.css">
    <link rel="stylesheet" href="../../assets/mobile-admin.min.css">
    <link rel="stylesheet" href="../../assets/admin-overrides.css">
    <link rel="stylesheet" href="../../assets/bootstrap-icons/bootstrap-icons.css" media="print" onload="this.media='all'">
    <noscript><link rel="stylesheet" href="../../assets/bootstrap-icons/bootstrap-icons.css"></noscript>
    <style>
        /* ── Stats bar ── */
        .cat-stats-bar {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin-bottom: 18px;
        }
        .cat-stat-card {
            flex: 1;
            min-width: 110px;
            background: #fff;
            border-radius: 12px;
            padding: 14px 18px;
            box-shadow: 0 1px 6px rgba(0,0,0,.07);
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .cat-stat-icon {
            width: 40px; height: 40px;
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 18px;
            flex-shrink: 0;
        }
        .cat-stat-icon.total   { background: #e8f5e9; color: #2E8B57; }
        .cat-stat-icon.activas { background: #dcfce7; color: #16a34a; }
        .cat-stat-icon.ocultas { background: #fee2e2; color: #dc2626; }
        .cat-stat-num   { font-size: 22px; font-weight: 800; color: #1e293b; line-height: 1; }
        .cat-stat-label { font-size: 11px; color: #94a3b8; font-weight: 500; margin-top: 2px; }

        /* ── Grid ── */
        .primeraPart { overflow: visible !important; }
        .grid-categorias {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
            gap: 16px;
            padding: 6px 2px 30px;
            overflow: visible;
        }

        /* ── Card ── */
        .card-categoria {
            background: #fff !important;
            border-radius: 14px !important;
            box-shadow: 0 1px 6px rgba(0,0,0,.06) !important;
            overflow: hidden !important;
            position: relative !important;
            border: 1.5px solid #f1f5f9 !important;
            display: flex !important;
            flex-direction: column !important;
            width: auto !important; min-width: 0 !important; flex-shrink: unset !important;
            cursor: default !important;
            transition: box-shadow .22s, transform .22s, border-color .22s !important;
        }
        .card-categoria:hover {
            box-shadow: 0 6px 22px rgba(46,139,87,.12) !important;
            transform: translateY(-2px) !important;
            border-color: #bbddc8 !important;
        }
        .card-categoria.cat-seleccionada {
            border-color: #2E8B57 !important;
            box-shadow: 0 0 0 3px rgba(46,139,87,.18) !important;
        }

        /* Checkbox */
        .cat-checkbox-wrap {
            position: absolute; top: 8px; left: 8px;
            display: none; z-index: 5;
        }
        .modo-seleccion .cat-checkbox-wrap { display: block; }
        .cat-checkbox-wrap input[type=checkbox] { display: none; }
        .cat-checkbox-custom {
            width: 22px; height: 22px; border-radius: 6px;
            border: 2px solid #2E8B57; background: rgba(255,255,255,.92);
            display: flex; align-items: center; justify-content: center;
            cursor: pointer; transition: background .15s; backdrop-filter: blur(4px);
        }
        .cat-checkbox-wrap input:checked + .cat-checkbox-custom { background: #2E8B57; color: #fff; }

        /* Badge estado */
        .cat-estado-badge {
            position: absolute !important;
            top: 8px !important; right: 8px !important; left: auto !important;
            width: fit-content !important;
            font-size: 9px !important; font-weight: 700 !important;
            border-radius: 20px !important;
            padding: 2px 8px !important;
            display: inline-flex !important; align-items: center !important; gap: 3px !important;
            z-index: 4 !important; letter-spacing: .4px !important;
            text-transform: uppercase !important;
            backdrop-filter: blur(4px) !important;
        }
        .cat-estado-activo { background: rgba(220,252,231,.92); color: #15803d; }
        .cat-estado-oculto { background: rgba(254,226,226,.92); color: #dc2626; }

        /* Imagen / placeholder */
        .cat-card-img-wrap {
            position: relative !important; width: 100% !important;
            height: 105px !important; overflow: hidden !important; flex-shrink: 0 !important;
        }
        .cat-card-img {
            width: 100%; height: 100%; object-fit: cover;
            display: block; transition: transform .35s ease;
        }
        .card-categoria:hover .cat-card-img { transform: scale(1.06); }
        .cat-card-img-placeholder {
            width: 100%; height: 100%;
            display: flex; align-items: center; justify-content: center;
            background: linear-gradient(135deg, #e8f5e9 0%, #f0fdf4 100%);
            position: relative; overflow: hidden;
        }
        .cat-card-img-placeholder::before {
            content: ''; position: absolute;
            width: 80px; height: 80px; border-radius: 50%;
            background: rgba(46,139,87,.07); top: -20px; right: -20px;
        }
        .cat-card-img-placeholder i { font-size: 28px; color: #a5d6b0; position: relative; z-index: 1; }
        .cat-card-img-placeholder span { display: none; }

        /* Body */
        .cat-card-body {
            padding: 10px 12px 12px;
            display: flex; flex-direction: column; flex: 1; gap: 6px;
        }
        .cat-card-nombre {
            font-size: 13px; font-weight: 700; color: #1e293b;
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
            margin: 0; line-height: 1.3;
        }

        /* Meta row */
        .cat-card-meta { display: flex; align-items: center; gap: 5px; flex-wrap: wrap; }
        .cat-sub-badge {
            display: inline-flex; align-items: center; gap: 3px;
            background: #e8f5e9; color: #1a5c38; border-radius: 20px;
            font-size: 10px; font-weight: 600; padding: 2px 8px;
            border: 1px solid #bbddc8;
            max-width: 110px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
        }
        .cat-prod-badge {
            display: inline-flex; align-items: center; gap: 3px;
            background: #f1f5f9; color: #475569; border-radius: 20px;
            font-size: 10px; font-weight: 600; padding: 2px 7px; margin-left: auto;
        }
        .cat-fecha {
            font-size: 10px; color: #b0bec5;
            display: flex; align-items: center; gap: 3px;
        }

        /* Acciones: fila de íconos fuera del body */
        .cat-acciones {
            display: flex !important;
            flex-direction: row !important;
            justify-content: flex-end !important;
            align-items: center !important;
            gap: 5px !important;
            padding: 0 10px 10px !important;
            position: static !important;
            opacity: 1; transform: none;
            background: transparent !important;
            border: none !important; height: auto !important;
        }
        .cat-btn {
            border: none !important;
            border-radius: 8px !important;
            width: 32px !important; height: 32px !important;
            font-size: 13px !important; font-weight: 600 !important;
            cursor: pointer !important;
            display: flex !important; align-items: center !important; justify-content: center !important;
            transition: filter .15s, transform .12s, box-shadow .15s !important;
            box-shadow: none !important; text-decoration: none !important;
            padding: 0 !important;
        }
        .cat-btn:hover  { filter: brightness(.88) !important; transform: translateY(-1px) !important; box-shadow: 0 3px 8px rgba(0,0,0,.12) !important; }
        .cat-btn:active { transform: scale(.95) !important; }
        .cat-btn-editar   { background: #dbeafe !important; color: #1d4ed8 !important; }
        .cat-btn-toggle   { background: #d1fae5 !important; color: #065f46 !important; }
        .cat-btn-info     { background: #ede9fe !important; color: #6d28d9 !important; }
        .cat-btn-eliminar { background: #fee2e2 !important; color: #b91c1c !important; }

        /* Desktop: ocultar acciones, aparecen en hover */
        @media (hover: hover) and (pointer: fine) {
            .cat-acciones {
                opacity: 0 !important;
                transform: translateY(4px) !important;
                transition: opacity .18s ease, transform .18s ease !important;
                pointer-events: none !important;
            }
            .card-categoria:hover .cat-acciones {
                opacity: 1 !important;
                transform: translateY(0) !important;
                pointer-events: auto !important;
            }
        }

        /* Empty state */
        .cat-empty {
            grid-column: 1 / -1;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 60px 20px;
            color: #94a3b8;
            gap: 10px;
        }
        .cat-empty i   { font-size: 48px; color: #cbd5e1; }
        .cat-empty p   { font-size: 15px; margin: 0; }
        .cat-empty span{ font-size: 13px; }

        /* Image preview in modals */
        .img-url-wrap { display: flex; flex-direction: column; gap: 8px; }
        .img-url-preview {
            width: 100%; height: 120px; object-fit: cover;
            border-radius: 10px; border: 1.5px solid #e2e8f0;
            display: none; background: #f8fafc;
        }
        .img-url-placeholder {
            width: 100%; height: 120px; border-radius: 10px;
            border: 1.5px dashed #e2e8f0;
            display: flex; align-items: center; justify-content: center;
            gap: 8px; color: #94a3b8; font-size: 13px; background: #f8fafc;
        }
    </style>
</head>
<body>
<div class="contenedor">

    <div class="head">
        <div class="imglogo">
            <a href="<?= SITE_URL ?>/admin" class="imglogo">
                <img class="imagenlogo"
                    src="../../assets/imagenes/logo.png"
                    alt="Imagen del logo de la Empresa">
            </a>

            <?php
                $nombreUser = $_SESSION['idUsuario'];
                $sql = "SELECT nombreUsuario AS nameuser, rol FROM usuarios WHERE idUsuario=$nombreUser";
                $query = mysqli_query($con, $sql);
                $row   = mysqli_fetch_assoc($query);
                $rolUsuario = $row['rol'];
            ?>

            <div class="saludo" id="userMenu">
                <div class="user-info">
                    <div class="user-text">
                        <span class="bienvenido-texto">Bienvenido the best, <?php echo $row['rol'] === 'admin' ? 'Admin' : 'Gestor'; ?></span>
                        <span class="bienvenido-user">
                            <?php echo $row['nameuser']; ?>
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

        <div class="main" id="menuPrincipal">
            <div class="orgmain">
                <a href="<?= SITE_URL ?>/admin/usuarios" class="menu-card">
                    <div class="icon"><i class="bi bi-person-gear"></i></div>
                    <h3>Usuarios</h3>
                    <p>Gestión de usuarios del sistema</p>
                </a>
            </div>
            <div class="orgmain">
                <a class="menu-card">
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
                <a href="<?= SITE_URL ?>/admin/productos" class="menu-card">
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
            dropdown.style.display = dropdown.style.display === "block" ? "none" : "block";
        });
        document.addEventListener("click", e => {
            if (!userMenu.contains(e.target)) dropdown.style.display = "none";
        });
    </script>

    <!-- Cuerpo -->
    <div class="cuepo">
        <div class="ruta">
            <a href="<?= SITE_URL ?>/admin"><i class="bi bi-house-fill"></i> Panel</a>
            <span class="separator"><i class="bi bi-chevron-right"></i></span>
            <span class="actual"><i class="bi bi-bookmark-fill"></i> Categorías</span>
        </div>

        <div class="primeraPart">
            <div class="rotulaApratado">
                <h1>Gestión de Categorías</h1>
                <p>Gestionar las categorías que organizan los productos del sistema</p>
            </div>

            <div class="barraSuperior">
                <!-- BUSCADOR -->
                <div class="filtrar">
                    <div class="input-busqueda">
                        <i class="bi bi-search"></i>
                        <input type="search" id="inputBuscarCat"
                               placeholder="Buscar por categoría"
                               autocomplete="off">
                    </div>
                </div>

                <!-- BOTÓN ACCIONES -->
                <button class="btnPanel" id="abrirPanel">
                    <i class="bi bi-list"></i> Acciones
                </button>

                <button id="btnAgregarPanel" class="btn-agregar-barra">
                    <i class="bi bi-plus-lg"></i> Agregar Categoría
                </button>
            </div>

            <!-- PANEL LATERAL -->
            <div class="panelLateral" id="panelLateral">
                <div class="panelHeader">
                    <h3>Acciones</h3>
                    <span id="cerrarPanel">&times;</span>
                </div>
                <div class="panelContenido">
                    <a class="accion" id="btnImportar" style="cursor:pointer">
                        <i class="bi bi-file-arrow-up-fill"></i>
                        <span>Importar Categorías</span>
                    </a>
                    <a href="<?= SITE_URL ?>/api/categorias/plantilla" class="accion">
                        <i class="bi bi-file-earmark-excel-fill"></i>
                        <span>Descargar Plantilla</span>
                    </a>
                    <a class="accion accion-danger" id="btnActivarSeleccion">
                        <i class="bi bi-check2-square"></i>
                        <span>Eliminar varios</span>
                    </a>
                </div>
            </div>

            <!-- OVERLAY -->
            <div class="overlay" id="overlay"></div>

            <script>
                const abrir = document.getElementById("abrirPanel");
                const cerrarPanelBtn = document.getElementById("cerrarPanel");
                const panel = document.getElementById("panelLateral");
                const overlay = document.getElementById("overlay");

                abrir.addEventListener("click", () => {
                    panel.classList.add("activo");
                    overlay.classList.add("activo");
                });
                cerrarPanelBtn.addEventListener("click", cerrarPanel);
                overlay.addEventListener("click", cerrarPanel);

                function cerrarPanel(){
                    panel.classList.remove("activo");
                    overlay.classList.remove("activo");
                }

                document.getElementById("btnAgregarPanel").addEventListener("click", () => {
                    document.getElementById("modalAgregar").style.display = "flex";
                });
            </script>

            <!-- Modal Importar -->
            <div class="modal" id="modalImportar">
                <div class="modal-import">
                    <button class="modal-import-cerrar cerrarImportar">&times;</button>
                    <div class="modal-import-header">
                        <div class="modal-import-icon">
                            <i class="bi bi-file-earmark-excel-fill"></i>
                        </div>
                        <h2>Importar Categorías</h2>
                        <p>Carga masiva desde un archivo <strong>Excel (.xlsx)</strong></p>
                    </div>
                    <form id="formImportarCat" enctype="multipart/form-data">
                        <label class="dropzone" id="dropzoneCat" for="fileCat">
                            <input type="file" name="archivo" accept=".xlsx" required id="fileCat" hidden>
                            <i class="bi bi-cloud-arrow-up-fill dropzone-icon"></i>
                            <span class="dropzone-texto">Arrastra tu archivo aquí</span>
                            <span class="dropzone-sub">o haz clic para seleccionar</span>
                            <span class="dropzone-nombre" id="nombreArchivoCat">Ningún archivo seleccionado</span>
                        </label>
                        <a href="<?= SITE_URL ?>/api/categorias/plantilla" class="modal-import-plantilla">
                            <i class="bi bi-download"></i> Descargar plantilla de ejemplo
                        </a>
                        <button type="submit" class="modal-import-btn" id="btnSubmitCat" disabled>
                            <i class="bi bi-upload"></i> Importar
                        </button>
                    </form>
                    <div id="resultadoImportCat" class="modal-import-resultado"></div>
                </div>
            </div>

            <script>
            (function(){
                const modalImportar = document.getElementById("modalImportar");
                const fileInput     = document.getElementById("fileCat");
                const nombreArchivo = document.getElementById("nombreArchivoCat");
                const btnSubmit     = document.getElementById("btnSubmitCat");
                const dropzone      = document.getElementById("dropzoneCat");
                const resultado     = document.getElementById("resultadoImportCat");

                document.getElementById("btnImportar").onclick = () => {
                    cerrarPanel();
                    modalImportar.style.display = "flex";
                };
                document.querySelector(".cerrarImportar").onclick = () => {
                    modalImportar.style.display = "none";
                    resetImportCat();
                };
                window.addEventListener("click", e => {
                    if(e.target === modalImportar){ modalImportar.style.display = "none"; resetImportCat(); }
                });
                function resetImportCat(){
                    document.getElementById("formImportarCat").reset();
                    nombreArchivo.textContent = "Ningún archivo seleccionado";
                    dropzone.classList.remove("dropzone-activo");
                    btnSubmit.disabled = true;
                    resultado.innerHTML = "";
                }
                fileInput.addEventListener("change", () => {
                    if(fileInput.files.length){
                        nombreArchivo.textContent = fileInput.files[0].name;
                        dropzone.classList.add("dropzone-activo");
                        btnSubmit.disabled = false;
                    }
                });
                dropzone.addEventListener("dragover", e => { e.preventDefault(); dropzone.classList.add("dropzone-drag"); });
                dropzone.addEventListener("dragleave", () => dropzone.classList.remove("dropzone-drag"));
                dropzone.addEventListener("drop", e => {
                    e.preventDefault();
                    dropzone.classList.remove("dropzone-drag");
                    const file = e.dataTransfer.files[0];
                    if(file && file.name.endsWith(".xlsx")){
                        const dt = new DataTransfer();
                        dt.items.add(file);
                        fileInput.files = dt.files;
                        nombreArchivo.textContent = file.name;
                        dropzone.classList.add("dropzone-activo");
                        btnSubmit.disabled = false;
                    }
                });
                document.getElementById("formImportarCat").addEventListener("submit", function(e){
                    e.preventDefault();
                    btnSubmit.disabled = true;
                    btnSubmit.innerHTML = '<i class="bi bi-arrow-repeat spin"></i> Importando...';
                    resultado.innerHTML = "";
                    fetch("<?= SITE_URL ?>/api/categorias/importar", { method:"POST", body: new FormData(this) })
                    .then(r => r.json())
                    .then(data => {
                        const errHTML = data.detalleErrores?.length
                            ? `<ul class="import-errores">${data.detalleErrores.map(e=>`<li>${e}</li>`).join("")}</ul>`
                            : "";
                        resultado.innerHTML = `
                            <div class="import-stats">
                                <span class="import-ok"><i class="bi bi-check-circle-fill"></i> ${data.insertados} insertados</span>
                                <span class="import-err"><i class="bi bi-x-circle-fill"></i> ${data.errores} errores</span>
                            </div>${errHTML}`;
                        btnSubmit.disabled = false;
                        btnSubmit.innerHTML = '<i class="bi bi-upload"></i> Importar';
                        if(data.insertados > 0) setTimeout(() => location.reload(), 1800);
                    })
                    .catch(() => {
                        resultado.innerHTML = '<p class="import-fatal">Error de conexión</p>';
                        btnSubmit.disabled = false;
                        btnSubmit.innerHTML = '<i class="bi bi-upload"></i> Importar';
                    });
                });
            })();
            </script>

            <!-- Modal Agregar -->
            <div id="modalAgregar" class="modal">
                <div class="modal-contenido">
                    <span class="cerrar" id="cerrarModal">&times;</span>
                    <div class="modal-header">
                        <h2>Agregar Categoría</h2>
                    </div>
                    <br>
                    <form class="modal-form" id="formAgregarCategoria">
                        <label>Nombre de la Categoría</label>
                        <input type="text" name="nombreCategoria" id="nombreCategoria" required>

                        <label>Imagen (URL) <span style="color:#94a3b8;font-weight:400;font-size:11px;">— opcional</span></label>
                        <div class="img-url-wrap">
                            <div class="img-url-placeholder" id="addImgPlaceholder">
                                <i class="bi bi-image"></i> Vista previa de la imagen
                            </div>
                            <img class="img-url-preview" id="addImgPreview" alt="Vista previa">
                            <input type="url" name="imagenCategoria" id="addImagenUrl"
                                   placeholder="https://ejemplo.com/imagen.jpg"
                                   autocomplete="off">
                        </div>

                        <div id="mensajeAgregar"></div>

                        <div class="modal-botones">
                            <button type="submit" class="guardar">Guardar</button>
                            <button type="button" class="cancelar" id="cancelarModal">Cancelar</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Modal Editar -->
            <div class="modal" id="modalEditarCategoria">
                <div class="modal-contenido">
                    <div class="modal-header-row">
                        <h2>Editar Categoría</h2>
                        <button type="button" class="cerrarEditarCategoria modal-close-x" aria-label="Cerrar">
                            <i class="bi bi-x"></i>
                        </button>
                    </div>
                    <form class="modal-form" id="formEditarCategoria">
                        <input type="hidden" name="id" id="editIdCategoria">

                        <label>Nombre de la Categoría</label>
                        <input type="text" name="nombre" id="editNombreCategoria" required>

                        <label>Imagen (URL) <span style="color:#94a3b8;font-weight:400;font-size:11px;">— opcional</span></label>
                        <div class="img-url-wrap">
                            <div class="img-url-placeholder" id="editImgPlaceholder">
                                <i class="bi bi-image"></i> Vista previa de la imagen
                            </div>
                            <img class="img-url-preview" id="editImgPreview" alt="Vista previa">
                            <input type="url" name="imagen" id="editImagenUrl"
                                   placeholder="https://ejemplo.com/imagen.jpg"
                                   autocomplete="off">
                        </div>

                        <div id="mensajeEditarCategoria"></div>

                        <div class="modal-botones">
                            <button type="submit" class="guardar">Actualizar</button>
                        </div>
                    </form>
                </div>
            </div>

        </div><!-- /primeraPart -->

        <!-- BARRA DE SELECCIÓN MÚLTIPLE -->
        <div class="seleccion-barra" id="seleccionBarra">
            <div class="seleccion-barra-inner">
                <div class="seleccion-info">
                    <div class="seleccion-icon-wrap">
                        <i class="bi bi-check2-square"></i>
                    </div>
                    <div class="seleccion-texto">
                        <span class="seleccion-titulo">Modo selección</span>
                        <span id="seleccionContador" class="seleccion-sub">0 categorías seleccionadas</span>
                    </div>
                </div>
                <div class="seleccion-acciones">
                    <button class="seleccion-btn seleccion-btn-todo" id="btnSelTodo">
                        <i class="bi bi-check-all"></i> Todas
                    </button>
                    <button class="seleccion-btn seleccion-btn-ninguna" id="btnSelNinguna">
                        <i class="bi bi-dash-lg"></i> Ninguna
                    </button>
                    <button class="seleccion-btn seleccion-btn-eliminar" id="btnEliminarSel" disabled>
                        <i class="bi bi-trash3-fill"></i> Eliminar seleccionadas
                    </button>
                    <button class="seleccion-btn seleccion-btn-cancelar" id="btnCancelarSel">
                        <i class="bi bi-x-lg"></i> Cancelar
                    </button>
                </div>
            </div>
        </div>
        <br>

        <!-- Modal confirmación eliminación masiva -->
        <div class="modal" id="modalConfirmElim">
            <div class="modal-confirm-contenido">
                <div class="modal-confirm-icon">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                </div>
                <h3 class="modal-confirm-titulo">¿Eliminar categorías?</h3>
                <p class="modal-confirm-sub">
                    Se eliminarán <strong id="confirmarNumero">0</strong> categoría(s) permanentemente.<br>
                    Esta acción no se puede deshacer.
                </p>
                <div class="modal-confirm-botones">
                    <button class="modal-confirm-cancelar" id="confirmarCancelar">Cancelar</button>
                    <button class="modal-confirm-eliminar" id="confirmarEliminar">
                        <i class="bi bi-trash-fill"></i> Sí, eliminar
                    </button>
                </div>
            </div>
        </div>

        <!-- Modal: Mover productos antes de eliminar -->
        <div class="modal" id="modalMoverCat">
            <div class="modal-mover">
                <div class="modal-mover-header">
                    <div class="modal-mover-header-icon">
                        <i class="bi bi-arrow-left-right"></i>
                    </div>
                    <div class="modal-mover-header-texto">
                        <h2>Mover productos</h2>
                        <p>Reasigna los productos antes de eliminar la categoría</p>
                    </div>
                    <button class="modal-mover-cerrar" id="cerrarMoverCat">&times;</button>
                </div>
                <div class="modal-mover-body">
                    <input type="hidden" id="moverCatIdOrigen">
                    <div class="modal-mover-alerta">
                        <i class="bi bi-info-circle-fill"></i>
                        <span>Esta categoría tiene <strong id="moverCatCount">0</strong> producto(s).
                        Selecciona la categoría destino para moverlos antes de eliminar.</span>
                    </div>
                    <div class="modal-mover-campo">
                        <label>Categoría destino</label>
                        <select id="moverCatDestino">
                            <option value="">— Seleccionar categoría —</option>
                        </select>
                    </div>
                    <div id="moverCatMsg"></div>
                    <div class="modal-mover-botones">
                        <button type="button" class="modal-mover-btn-cancel" id="btnCancelarMover">Cancelar</button>
                        <button id="btnConfirmarMover" class="modal-mover-btn-confirm" disabled>
                            <i class="bi bi-arrow-left-right"></i> Mover y eliminar
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal: categorías bloqueadas en bulk delete -->
        <div class="modal" id="modalBloqueadas">
            <div class="modal-confirm-contenido" style="max-width:440px;">
                <div class="modal-confirm-icon" style="background:linear-gradient(135deg,#f59e0b,#d97706);">
                    <i class="bi bi-exclamation-circle-fill"></i>
                </div>
                <h3 class="modal-confirm-titulo">Eliminación parcial</h3>
                <p class="modal-confirm-sub" id="bloqueadasTexto"></p>
                <ul id="bloqueadasLista" style="text-align:left;padding:0 16px;color:#64748b;font-size:13px;margin:8px 0 16px;"></ul>
                <div class="modal-confirm-botones">
                    <button class="modal-confirm-cancelar" id="cerrarBloqueadas">Entendido</button>
                </div>
            </div>
        </div>

        <!-- Modal Detalle -->
        <div class="modal" id="modalDetalle">
            <div class="modal-contenido">
                <span class="cerrar cerrarDetalle">&times;</span>
                <h2>Detalles de la categoría</h2>
                <br>
                <p><strong>Creado por:</strong> <span id="detalleUsuario"></span></p>
                <p><strong>Fecha creación:</strong> <span id="detalleFecha"></span></p>
            </div>
        </div>

        <?php
            // Stats
            $statsRow = mysqli_fetch_assoc(mysqli_query($con,
                "SELECT COUNT(*) AS total,
                        SUM(estadoCategoria='Activo') AS activas,
                        SUM(estadoCategoria='Oculto') AS ocultas
                 FROM categoria"));
            $statTotal   = (int)($statsRow['total']   ?? 0);
            $statActivas = (int)($statsRow['activas'] ?? 0);
            $statOcultas = (int)($statsRow['ocultas'] ?? 0);
        ?>

        <!-- Stats bar -->
        <div class="cat-stats-bar">
            <div class="cat-stat-card">
                <div class="cat-stat-icon total"><i class="bi bi-bookmark-fill"></i></div>
                <div>
                    <div class="cat-stat-num" id="statCatTotal"><?php echo $statTotal; ?></div>
                    <div class="cat-stat-label">TOTAL</div>
                </div>
            </div>
            <div class="cat-stat-card">
                <div class="cat-stat-icon activas"><i class="bi bi-eye-fill"></i></div>
                <div>
                    <div class="cat-stat-num" id="statCatActivas"><?php echo $statActivas; ?></div>
                    <div class="cat-stat-label">ACTIVAS</div>
                </div>
            </div>
            <div class="cat-stat-card">
                <div class="cat-stat-icon ocultas"><i class="bi bi-eye-slash-fill"></i></div>
                <div>
                    <div class="cat-stat-num" id="statCatOcultas"><?php echo $statOcultas; ?></div>
                    <div class="cat-stat-label">OCULTAS</div>
                </div>
            </div>
        </div>

        <!-- Grid de categorías -->
        <div class="grid-categorias" id="gridCategorias">
            <?php
                $sqlCat = "SELECT
                               c.idCategoria,
                               c.nombreCategoria,
                               c.imagenCategoria,
                               c.fechaCategoria,
                               c.estadoCategoria,
                               u.nombreUsuario,
                               COUNT(DISTINCT s.idSubcategoria) AS totalSubs,
                               COUNT(DISTINCT p.idProducto)     AS totalProductos
                           FROM categoria c
                           INNER JOIN usuarios u ON u.idUsuario = c.idUsuario
                           LEFT JOIN subcategoria s ON s.idCategoria = c.idCategoria
                           LEFT JOIN producto p ON p.idCategoria = c.idCategoria
                           GROUP BY c.idCategoria
                           ORDER BY c.nombreCategoria ASC";
                $qCat = mysqli_query($con, $sqlCat);
                while($cat = mysqli_fetch_assoc($qCat)):
                    $estadoClass = strtolower($cat['estadoCategoria']);
                    $fecha = $cat['fechaCategoria'] ? date('d M Y', strtotime($cat['fechaCategoria'])) : '—';
            ?>
            <div class="card-categoria"
                 data-id="<?php echo $cat['idCategoria']; ?>"
                 data-nombre="<?php echo htmlspecialchars($cat['nombreCategoria']); ?>">

                <!-- Checkbox selección múltiple -->
                <label class="cat-checkbox-wrap" title="Seleccionar">
                    <input type="checkbox" class="cat-checkbox" value="<?php echo $cat['idCategoria']; ?>">
                    <span class="cat-checkbox-custom"><i class="bi bi-check-lg"></i></span>
                </label>

                <!-- Badge estado -->
                <span class="cat-estado-badge cat-estado-<?php echo $estadoClass; ?>">
                    <?php echo $cat['estadoCategoria'] === 'Activo'
                        ? '<i class="bi bi-eye-fill"></i> Activo'
                        : '<i class="bi bi-eye-slash-fill"></i> Oculto'; ?>
                </span>

                <!-- Imagen -->
                <div class="cat-card-img-wrap">
                    <?php if(!empty($cat['imagenCategoria'])): ?>
                    <img class="cat-card-img"
                         src="<?php echo htmlspecialchars($cat['imagenCategoria']); ?>"
                         alt="<?php echo htmlspecialchars($cat['nombreCategoria']); ?>"
                         onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                    <div class="cat-card-img-placeholder" style="display:none;">
                        <i class="bi bi-image"></i>
                        <span>Sin imagen</span>
                    </div>
                    <?php else: ?>
                    <div class="cat-card-img-placeholder">
                        <i class="bi bi-bookmark-fill"></i>
                        <span>Sin imagen</span>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Body -->
                <div class="cat-card-body">
                    <p class="cat-card-nombre"><?php echo htmlspecialchars($cat['nombreCategoria']); ?></p>

                    <div class="cat-card-meta">
                        <span class="cat-sub-badge">
                            <i class="bi bi-diagram-3-fill"></i> <?php echo $cat['totalSubs']; ?> sub<?php echo $cat['totalSubs'] != 1 ? 's' : ''; ?>
                        </span>
                        <span class="cat-prod-badge">
                            <i class="bi bi-box-seam"></i> <?php echo $cat['totalProductos']; ?>
                        </span>
                    </div>

                    <div class="cat-fecha"><i class="bi bi-calendar3"></i> <?php echo $fecha; ?></div>
                </div>

                <!-- Acciones: solo íconos -->
                <div class="cat-acciones">
                    <button class="cat-btn cat-btn-editar btnEditar"
                        title="Editar"
                        data-id="<?php echo $cat['idCategoria']; ?>"
                        data-nombre="<?php echo htmlspecialchars($cat['nombreCategoria']); ?>"
                        data-imagen="<?php echo htmlspecialchars($cat['imagenCategoria'] ?? ''); ?>">
                        <i class="bi bi-pencil-fill"></i>
                    </button>
                    <button class="cat-btn cat-btn-toggle btnToggleEstado"
                        title="<?php echo $cat['estadoCategoria'] === 'Activo' ? 'Ocultar' : 'Activar'; ?>"
                        data-id="<?php echo $cat['idCategoria']; ?>"
                        data-estado="<?php echo $cat['estadoCategoria']; ?>">
                        <i class="bi <?php echo $cat['estadoCategoria'] === 'Activo' ? 'bi-eye-slash-fill' : 'bi-eye-fill'; ?>"></i>
                    </button>
                    <button class="cat-btn cat-btn-info btnDetalle"
                        title="Info"
                        data-usuario="<?php echo htmlspecialchars($cat['nombreUsuario']); ?>"
                        data-fecha="<?php echo date('d M Y - H:i', strtotime($cat['fechaCategoria'])); ?>">
                        <i class="bi bi-info-lg"></i>
                    </button>
                    <button class="cat-btn cat-btn-eliminar btnEliminarCat"
                        title="Eliminar"
                        data-id="<?php echo $cat['idCategoria']; ?>">
                        <i class="bi bi-trash-fill"></i>
                    </button>
                </div>
            </div>
            <?php endwhile; ?>

            <!-- Empty state -->
            <div class="cat-empty" id="catEmpty" style="display:none;">
                <i class="bi bi-search"></i>
                <p>No se encontraron categorías</p>
                <span>Prueba con otro término de búsqueda</span>
            </div>
        </div>

    </div><!-- /cuepo -->

    <!-- ══ SCRIPTS ══════════════════════════════════════════════════════ -->

    <!-- Modales Agregar / Editar / Detalle -->
    <script>
    (function(){
        const modalAgregar  = document.getElementById("modalAgregar");
        const modalEditar   = document.getElementById("modalEditarCategoria");
        const modalDetalle  = document.getElementById("modalDetalle");
        const formAgregar   = document.getElementById("formAgregarCategoria");
        const formEditar    = document.getElementById("formEditarCategoria");
        const mensajeAgreg  = document.getElementById("mensajeAgregar");
        const mensajeEditar = document.getElementById("mensajeEditarCategoria");

        function cerrarModalAgregar(){
            modalAgregar.style.display = "none";
            formAgregar.reset();
            mensajeAgreg.innerHTML = "";
            document.getElementById("addImgPreview").style.display = "none";
            document.getElementById("addImgPlaceholder").style.display = "flex";
        }
        function cerrarModalEditar(){
            modalEditar.style.display = "none";
            formEditar.reset();
            mensajeEditar.innerHTML = "";
        }

        document.getElementById("cerrarModal").onclick = cerrarModalAgregar;
        document.getElementById("cancelarModal").onclick = cerrarModalAgregar;
        document.querySelectorAll(".cerrarEditarCategoria").forEach(el => el.onclick = cerrarModalEditar);

        window.addEventListener("click", e => {
            if(e.target === modalAgregar) cerrarModalAgregar();
            if(e.target === modalEditar)  cerrarModalEditar();
            if(e.target === modalDetalle) modalDetalle.style.display = "none";
        });
        document.querySelector(".cerrarDetalle").onclick = () => { modalDetalle.style.display = "none"; };

        // Vista previa imagen
        function setImgPreview(url, previewId, placeholderId){
            const img = document.getElementById(previewId);
            const ph  = document.getElementById(placeholderId);
            if(url.trim()){
                img.src = url.trim();
                img.style.display = "block";
                ph.style.display  = "none";
                img.onerror = () => { img.style.display = "none"; ph.style.display = "flex"; };
            } else {
                img.style.display = "none";
                img.src = "";
                ph.style.display  = "flex";
            }
        }
        document.getElementById("addImagenUrl").addEventListener("input", function(){
            setImgPreview(this.value, "addImgPreview", "addImgPlaceholder");
        });
        document.getElementById("editImagenUrl").addEventListener("input", function(){
            setImgPreview(this.value, "editImgPreview", "editImgPlaceholder");
        });

        // Abrir editar
        document.addEventListener("click", function(e){
            const btn = e.target.closest(".btnEditar");
            if(!btn) return;
            e.preventDefault();
            document.getElementById("editIdCategoria").value = btn.dataset.id;
            document.getElementById("editNombreCategoria").value = btn.dataset.nombre;
            const imgUrl = btn.dataset.imagen || "";
            document.getElementById("editImagenUrl").value = imgUrl;
            setImgPreview(imgUrl, "editImgPreview", "editImgPlaceholder");
            mensajeEditar.innerHTML = "";
            modalEditar.style.display = "flex";
        });

        // Abrir detalle
        document.addEventListener("click", function(e){
            const btn = e.target.closest(".btnDetalle");
            if(!btn) return;
            document.getElementById("detalleUsuario").textContent = btn.dataset.usuario;
            document.getElementById("detalleFecha").textContent   = btn.dataset.fecha;
            modalDetalle.style.display = "flex";
        });

        // Submit Agregar
        formAgregar.addEventListener("submit", function(e){
            e.preventDefault();
            fetch("<?= SITE_URL ?>/api/categorias/insertar", { method:"POST", body: new FormData(formAgregar) })
            .then(r => r.json())
            .then(data => {
                mensajeAgreg.textContent = data.message;
                mensajeAgreg.style.color = data.status === "success" ? "green" : "red";
                if(data.status === "success") setTimeout(() => { cerrarModalAgregar(); location.reload(); }, 800);
            });
        });

        // Submit Editar
        formEditar.addEventListener("submit", function(e){
            e.preventDefault();
            fetch("<?= SITE_URL ?>/api/categorias/editar", { method:"POST", body: new FormData(formEditar) })
            .then(r => r.json())
            .then(data => {
                if(data.status === "success"){
                    mensajeEditar.innerHTML = `<div style="color:green;padding:8px">${data.message}</div>`;
                    setTimeout(() => { cerrarModalEditar(); location.reload(); }, 1000);
                } else {
                    mensajeEditar.innerHTML = `<div style="color:#dc2626;padding:8px">${data.message}</div>`;
                }
            })
            .catch(() => { mensajeEditar.innerHTML = '<div style="color:#dc2626;padding:8px">Error de conexión</div>'; });
        });
    })();
    </script>

    <!-- Toggle estado + Eliminar individual + Mover -->
    <script>
    (function(){
        const modalMover    = document.getElementById("modalMoverCat");
        const cerrarMoverEl = document.getElementById("cerrarMoverCat");
        const btnCancelarM  = document.getElementById("btnCancelarMover");
        const btnConfirmarM = document.getElementById("btnConfirmarMover");
        const selectDestino = document.getElementById("moverCatDestino");
        const moverCatMsg   = document.getElementById("moverCatMsg");
        const moverCatCount = document.getElementById("moverCatCount");
        const idOrigenInput = document.getElementById("moverCatIdOrigen");

        function cerrarModalMover(){
            modalMover.style.display = "none";
            selectDestino.value = "";
            moverCatMsg.innerHTML = "";
            btnConfirmarM.disabled = true;
        }
        cerrarMoverEl.addEventListener("click", cerrarModalMover);
        btnCancelarM.addEventListener("click", cerrarModalMover);
        window.addEventListener("click", e => { if(e.target === modalMover) cerrarModalMover(); });
        selectDestino.addEventListener("change", () => { btnConfirmarM.disabled = !selectDestino.value; });

        // Eliminar individual
        document.addEventListener("click", function(e){
            const btn = e.target.closest(".btnEliminarCat");
            if(!btn) return;
            e.preventDefault();
            const id = btn.dataset.id;
            fetch(`<?= SITE_URL ?>/api/categorias/eliminar?idCategoria=${id}`)
            .then(r => r.json())
            .then(data => {
                if(data.status === "success"){
                    animarEliminar(id);
                } else if(data.status === "has_products"){
                    idOrigenInput.value = id;
                    moverCatCount.textContent = data.count;
                    selectDestino.innerHTML = '<option value="">-- Seleccionar categoría --</option>';
                    data.categorias.forEach(c => {
                        const opt = document.createElement("option");
                        opt.value = c.idCategoria;
                        opt.textContent = c.nombreCategoria;
                        selectDestino.appendChild(opt);
                    });
                    btnConfirmarM.disabled = true;
                    moverCatMsg.innerHTML = "";
                    modalMover.style.display = "flex";
                } else {
                    toast.error(data.message);
                }
            })
            .catch(() => toast.error("Error de conexión"));
        });

        // Confirmar mover y eliminar
        btnConfirmarM.addEventListener("click", function(){
            const idOrigen  = idOrigenInput.value;
            const idDestino = selectDestino.value;
            btnConfirmarM.disabled = true;
            btnConfirmarM.innerHTML = '<i class="bi bi-arrow-repeat spin"></i> Moviendo...';
            fetch("<?= SITE_URL ?>/api/categorias/mover-eliminar", {
                method: "POST",
                headers: {"Content-Type":"application/json"},
                body: JSON.stringify({ idOrigen: parseInt(idOrigen), idDestino: parseInt(idDestino) })
            })
            .then(r => r.json())
            .then(data => {
                btnConfirmarM.innerHTML = '<i class="bi bi-arrow-left-right"></i> Mover y eliminar';
                if(data.status === "success"){
                    cerrarModalMover();
                    animarEliminar(idOrigen);
                } else {
                    moverCatMsg.innerHTML = `<p style="color:#ef4444;">${data.message}</p>`;
                    btnConfirmarM.disabled = false;
                }
            })
            .catch(() => {
                moverCatMsg.innerHTML = '<p style="color:#ef4444;">Error de conexión</p>';
                btnConfirmarM.disabled = false;
                btnConfirmarM.innerHTML = '<i class="bi bi-arrow-left-right"></i> Mover y eliminar';
            });
        });

        // Toggle estado
        document.addEventListener("click", function(e){
            const btn = e.target.closest(".btnToggleEstado");
            if(!btn) return;
            const id = btn.dataset.id;
            fetch("<?= SITE_URL ?>/api/categorias/toggle-estado", {
                method: "POST",
                headers: {"Content-Type":"application/json"},
                body: JSON.stringify({ id: parseInt(id) })
            })
            .then(r => r.json())
            .then(data => {
                if(data.status !== "success") return;
                const nuevo = data.nuevoEstado;
                const card  = btn.closest(".card-categoria");
                const badge = card.querySelector(".cat-estado-badge");
                btn.dataset.estado = nuevo;
                btn.title = nuevo === "Activo" ? "Ocultar" : "Activar";
                btn.innerHTML = nuevo === "Activo"
                    ? '<i class="bi bi-eye-slash-fill"></i>'
                    : '<i class="bi bi-eye-fill"></i>';
                badge.className = `cat-estado-badge cat-estado-${nuevo.toLowerCase()}`;
                badge.innerHTML = nuevo === "Activo"
                    ? '<i class="bi bi-eye-fill"></i> Activo'
                    : '<i class="bi bi-eye-slash-fill"></i> Oculto';
                actualizarStatsCat();
            })
            .catch(() => toast.error("Error de conexión"));
        });

        function animarEliminar(id){
            actualizarStatsCat();
            const card = document.querySelector(`.card-categoria[data-id="${id}"]`);
            if(card){
                card.style.transition = "transform .3s ease, opacity .3s ease";
                card.style.transform  = "scale(.85)";
                card.style.opacity    = "0";
                setTimeout(() => card.remove(), 320);
            }
        }
        window._animarEliminarCat = animarEliminar;
    })();
    </script>

    <!-- Búsqueda en tiempo real -->
    <script>
    (function(){
        const inputBuscar = document.getElementById("inputBuscarCat");
        const catEmpty    = document.getElementById("catEmpty");
        inputBuscar.addEventListener("input", function(){
            const q = this.value.trim().toLowerCase();
            let visible = 0;
            document.querySelectorAll(".card-categoria").forEach(card => {
                const nombre = card.dataset.nombre.toLowerCase();
                const show = !q || nombre.includes(q);
                card.style.display = show ? "" : "none";
                if(show) visible++;
            });
            if(catEmpty) catEmpty.style.display = visible === 0 ? "flex" : "none";
        });
    })();
    </script>

    <!-- Selección múltiple -->
    <script>
    (function(){
        const grid           = document.getElementById("gridCategorias");
        const barra          = document.getElementById("seleccionBarra");
        const contador       = document.getElementById("seleccionContador");
        const btnEliminarSel = document.getElementById("btnEliminarSel");
        const btnSelTodo     = document.getElementById("btnSelTodo");
        const btnSelNinguna  = document.getElementById("btnSelNinguna");
        const btnCancelarSel = document.getElementById("btnCancelarSel");
        const btnActivar     = document.getElementById("btnActivarSeleccion");
        const modalConfirm   = document.getElementById("modalConfirmElim");
        const confirmarNum   = document.getElementById("confirmarNumero");
        const confirmarBtn   = document.getElementById("confirmarEliminar");
        const cancelarBtn    = document.getElementById("confirmarCancelar");
        const modalBloqueadas  = document.getElementById("modalBloqueadas");
        const bloqueadasTexto  = document.getElementById("bloqueadasTexto");
        const bloqueadasLista  = document.getElementById("bloqueadasLista");
        const cerrarBloqueadas = document.getElementById("cerrarBloqueadas");

        let modoSeleccion = false;

        function getCheckboxes(){ return document.querySelectorAll(".cat-checkbox"); }
        function getSeleccionados(){ return [...document.querySelectorAll(".cat-checkbox:checked")]; }

        function actualizarContador(){
            const n = getSeleccionados().length;
            contador.textContent = n + " categoría" + (n !== 1 ? "s" : "") + " seleccionada" + (n !== 1 ? "s" : "");
            btnEliminarSel.disabled = n === 0;
        }

        function activarModo(){
            modoSeleccion = true;
            grid.classList.add("modo-seleccion");
            barra.classList.add("visible");
            document.getElementById("panelLateral").classList.remove("activo");
            document.getElementById("overlay").classList.remove("activo");
            actualizarContador();
        }

        function desactivarModo(){
            modoSeleccion = false;
            grid.classList.remove("modo-seleccion");
            barra.classList.remove("visible");
            getCheckboxes().forEach(cb => {
                cb.checked = false;
                cb.closest(".card-categoria").classList.remove("cat-seleccionada");
            });
            actualizarContador();
        }

        btnActivar.addEventListener("click", activarModo);
        btnCancelarSel.addEventListener("click", desactivarModo);

        btnSelTodo.addEventListener("click", () => {
            getCheckboxes().forEach(cb => {
                cb.checked = true;
                cb.closest(".card-categoria").classList.add("cat-seleccionada");
            });
            actualizarContador();
        });

        btnSelNinguna.addEventListener("click", () => {
            getCheckboxes().forEach(cb => {
                cb.checked = false;
                cb.closest(".card-categoria").classList.remove("cat-seleccionada");
            });
            actualizarContador();
        });

        document.addEventListener("change", function(e){
            if(!e.target.classList.contains("cat-checkbox")) return;
            e.target.closest(".card-categoria").classList.toggle("cat-seleccionada", e.target.checked);
            actualizarContador();
        });

        document.addEventListener("click", function(e){
            if(!modoSeleccion) return;
            const card = e.target.closest(".card-categoria");
            if(!card) return;
            if(e.target.closest(".cat-acciones") || e.target.closest(".cat-checkbox-wrap")) return;
            const cb = card.querySelector(".cat-checkbox");
            cb.checked = !cb.checked;
            card.classList.toggle("cat-seleccionada", cb.checked);
            actualizarContador();
        });

        btnEliminarSel.addEventListener("click", () => {
            confirmarNum.textContent = getSeleccionados().length;
            modalConfirm.style.display = "flex";
        });

        cancelarBtn.addEventListener("click", () => { modalConfirm.style.display = "none"; });
        window.addEventListener("click", e => {
            if(e.target === modalConfirm)   modalConfirm.style.display = "none";
            if(e.target === modalBloqueadas) modalBloqueadas.style.display = "none";
        });
        cerrarBloqueadas.addEventListener("click", () => { modalBloqueadas.style.display = "none"; });

        confirmarBtn.addEventListener("click", function(){
            const ids = getSeleccionados().map(cb => cb.value);
            confirmarBtn.disabled = true;
            confirmarBtn.innerHTML = '<i class="bi bi-arrow-repeat spin"></i> Eliminando...';
            const fd = new FormData();
            ids.forEach(id => fd.append("ids[]", id));
            fetch("<?= SITE_URL ?>/api/categorias/eliminar-varias", { method:"POST", body: fd })
            .then(r => r.json())
            .then(data => {
                modalConfirm.style.display = "none";
                confirmarBtn.disabled = false;
                confirmarBtn.innerHTML = '<i class="bi bi-trash-fill"></i> Sí, eliminar';
                const bloqueadasIds = (data.bloqueadas || []).map(b => String(b.id));
                ids.forEach(id => {
                    if(!bloqueadasIds.includes(String(id))) window._animarEliminarCat(id);
                });
                actualizarStatsCat();
                setTimeout(desactivarModo, 400);
                if(data.bloqueadas && data.bloqueadas.length > 0){
                    bloqueadasTexto.innerHTML = `<strong>${data.eliminadas}</strong> categoría(s) eliminada(s).<br>
                        Las siguientes no se pudieron eliminar porque tienen productos:`;
                    bloqueadasLista.innerHTML = data.bloqueadas
                        .map(b => `<li><strong>${b.nombre}</strong> — ${b.total} producto(s)</li>`)
                        .join("");
                    modalBloqueadas.style.display = "flex";
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
<script src="../../assets/toast.js"></script>
<script>
function actualizarStatsCat() {
    fetch('<?= SITE_URL ?>/api/categorias/stats')
    .then(r => r.json())
    .then(d => {
        const anim = (el, val) => {
            if (!el) return;
            el.style.transition = 'transform .2s, opacity .2s';
            el.style.transform = 'scale(.8)';
            el.style.opacity = '0';
            setTimeout(() => {
                el.textContent = val;
                el.style.transform = 'scale(1)';
                el.style.opacity = '1';
            }, 160);
        };
        anim(document.getElementById('statCatTotal'),   d.total);
        anim(document.getElementById('statCatActivas'), d.activas);
        anim(document.getElementById('statCatOcultas'), d.ocultas);
    }).catch(() => {});
}
</script>
</body>
</html>
