<?php
ob_start();
    session_start();
    include("../../config/conection.php");
    $con = conection();

    if(!isset($_SESSION['usuarios'])){
        header("location:../auth/login.php");
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
    <link rel="stylesheet" href="../../assets/bootstrap-icons/bootstrap-icons.css" media="print" onload="this.media='all'">
    <noscript><link rel="stylesheet" href="../../assets/bootstrap-icons/bootstrap-icons.css"></noscript>
    <style>
        /* ── Stats bar ── */
        .sub-stats-bar {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin-bottom: 18px;
        }
        .sub-stat-card {
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
        .sub-stat-icon {
            width: 40px; height: 40px;
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 18px;
            flex-shrink: 0;
        }
        .sub-stat-icon.total   { background: #e8f5e9; color: #2E8B57; }
        .sub-stat-icon.activas { background: #dcfce7; color: #16a34a; }
        .sub-stat-icon.ocultas { background: #fee2e2; color: #dc2626; }
        .sub-stat-num  { font-size: 22px; font-weight: 800; color: #1e293b; line-height: 1; }
        .sub-stat-label{ font-size: 11px; color: #94a3b8; font-weight: 500; margin-top: 2px; }

        /* ── Filtro por categoría ── */
        .sub-filtro-wrap {
            display: flex;
            align-items: center;
            gap: 6px;
            margin-bottom: 18px;
            background: #fff;
            border: 1.5px solid #e2e8f0;
            border-radius: 14px;
            padding: 8px 10px;
            box-shadow: 0 1px 4px rgba(0,0,0,.05);
        }
        .sub-filtro-label {
            font-size: 11px;
            font-weight: 700;
            color: #94a3b8;
            white-space: nowrap;
            text-transform: uppercase;
            letter-spacing: .5px;
            flex-shrink: 0;
            padding-right: 4px;
        }
        .sub-filtro-scroll-btn {
            flex-shrink: 0;
            width: 28px; height: 28px;
            border: 1.5px solid #e2e8f0;
            border-radius: 8px;
            background: #f8fafc;
            color: #64748b;
            display: flex; align-items: center; justify-content: center;
            cursor: pointer;
            font-size: 13px;
            transition: all .15s;
        }
        .sub-filtro-scroll-btn:hover { background: #e8f5e9; border-color: #2E8B57; color: #2E8B57; }
        .sub-filtro-scroll-btn:disabled { opacity: .3; cursor: default; }
        .sub-filtro-cats {
            display: flex;
            gap: 6px;
            overflow-x: auto;
            scroll-behavior: smooth;
            flex: 1;
            padding: 2px 0;
            -ms-overflow-style: none;
            scrollbar-width: none;
        }
        .sub-filtro-cats::-webkit-scrollbar { display: none; }
        .sub-filtro-chip {
            padding: 5px 13px;
            border-radius: 20px;
            border: 1.5px solid #e2e8f0;
            background: #f8fafc;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            color: #64748b;
            transition: all .15s;
            white-space: nowrap;
            flex-shrink: 0;
        }
        .sub-filtro-chip:hover  { border-color: #2E8B57; color: #2E8B57; background: #f0fdf4; }
        .sub-filtro-chip.activo { background: #2E8B57; color: #fff; border-color: #2E8B57; }

        /* ── Grid ── */
        .primeraPart { overflow: visible !important; }
        .grid-subcategorias {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
            gap: 16px;
            padding: 6px 2px 30px;
            overflow: visible;
        }

        /* ── Card ── */
        .card-subcategoria {
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 1px 6px rgba(0,0,0,.06);
            overflow: hidden;
            position: relative;
            border: 1.5px solid #f1f5f9;
            display: flex;
            flex-direction: column;
            transition: box-shadow .22s, transform .22s, border-color .22s;
        }
        .card-subcategoria:hover {
            box-shadow: 0 6px 22px rgba(46,139,87,.12);
            transform: translateY(-2px);
            border-color: #bbddc8;
        }
        .card-subcategoria.sub-seleccionada {
            border-color: #2E8B57;
            box-shadow: 0 0 0 3px rgba(46,139,87,.18);
        }

        /* Checkbox selección */
        .sub-checkbox-wrap {
            position: absolute;
            top: 8px; left: 8px;
            display: none;
            z-index: 5;
        }
        .modo-seleccion-sub .sub-checkbox-wrap { display: block; }
        .sub-checkbox-wrap input[type=checkbox] { display: none; }
        .sub-checkbox-custom {
            width: 22px; height: 22px;
            border-radius: 6px;
            border: 2px solid #2E8B57;
            background: rgba(255,255,255,.92);
            display: flex; align-items: center; justify-content: center;
            cursor: pointer;
            transition: background .15s;
            backdrop-filter: blur(4px);
        }
        .sub-checkbox-wrap input:checked + .sub-checkbox-custom {
            background: #2E8B57; color: #fff;
        }

        /* Badge estado — superpuesto en imagen */
        .sub-estado-badge {
            position: absolute;
            top: 8px; right: 8px;
            font-size: 9px;
            font-weight: 700;
            border-radius: 20px;
            padding: 2px 8px;
            display: flex; align-items: center; gap: 3px;
            z-index: 4;
            letter-spacing: .4px;
            text-transform: uppercase;
            backdrop-filter: blur(4px);
        }
        .sub-estado-activo { background: rgba(220,252,231,.92); color: #15803d; }
        .sub-estado-oculto { background: rgba(254,226,226,.92); color: #dc2626; }

        /* ── Imagen / placeholder ── */
        .sub-card-img-wrap {
            position: relative;
            width: 100%;
            height: 105px;
            overflow: hidden;
            flex-shrink: 0;
        }
        .sub-card-img {
            width: 100%; height: 100%;
            object-fit: cover;
            display: block;
            transition: transform .35s ease;
        }
        .card-subcategoria:hover .sub-card-img { transform: scale(1.06); }

        /* Placeholder elegante: inicial grande + degradado */
        .sub-card-img-placeholder {
            width: 100%; height: 100%;
            display: flex; align-items: center; justify-content: center;
            background: linear-gradient(135deg, #e8f5e9 0%, #f0fdf4 100%);
            position: relative;
            overflow: hidden;
        }
        .sub-card-img-placeholder::before {
            content: '';
            position: absolute;
            width: 80px; height: 80px;
            border-radius: 50%;
            background: rgba(46,139,87,.07);
            top: -20px; right: -20px;
        }
        .sub-card-img-placeholder i {
            font-size: 28px;
            color: #a5d6b0;
            position: relative;
            z-index: 1;
        }
        .sub-card-img-placeholder span { display: none; }

        /* ── Body ── */
        .sub-card-body {
            padding: 10px 12px 12px;
            display: flex;
            flex-direction: column;
            flex: 1;
            gap: 6px;
        }
        .sub-card-nombre {
            font-size: 13px;
            font-weight: 700;
            color: #1e293b;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            margin: 0;
            line-height: 1.3;
        }

        /* Meta row: categoría + productos + fecha todo en una línea */
        .sub-card-meta {
            display: flex;
            align-items: center;
            gap: 5px;
            flex-wrap: wrap;
        }
        .sub-cat-badge {
            display: inline-flex; align-items: center; gap: 3px;
            background: #e8f5e9; color: #1a5c38;
            border-radius: 20px; font-size: 10px; font-weight: 600;
            padding: 2px 8px; border: 1px solid #bbddc8;
            max-width: 110px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
        }
        .sub-prod-badge {
            display: inline-flex; align-items: center; gap: 3px;
            background: #f1f5f9; color: #475569;
            border-radius: 20px; font-size: 10px; font-weight: 600;
            padding: 2px 7px; margin-left: auto;
        }
        .sub-fecha {
            font-size: 10px; color: #b0bec5;
            display: flex; align-items: center; gap: 3px;
        }

        /* ── Acciones: overlay fijo al fondo de la imagen ── */
        .sub-acciones {
            display: flex;
            justify-content: flex-end;
            gap: 5px;
            padding: 0 10px 10px;
        }
        .sub-btn {
            border: none;
            border-radius: 8px;
            width: 32px; height: 32px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            display: flex; align-items: center; justify-content: center;
            transition: filter .15s, transform .12s, opacity .18s, box-shadow .15s;
        }
        .sub-btn:hover  { filter: brightness(.88); transform: translateY(-1px); box-shadow: 0 3px 8px rgba(0,0,0,.12); }
        .sub-btn:active { transform: scale(.95); }
        .sub-btn-editar   { background: #dbeafe; color: #1d4ed8; }
        .sub-btn-toggle   { background: #d1fae5; color: #065f46; }
        .sub-btn-eliminar { background: #fee2e2; color: #b91c1c; }

        /* Desktop: acciones ocultas, aparecen en hover */
        @media (hover: hover) and (pointer: fine) {
            .sub-acciones {
                opacity: 0;
                transform: translateY(4px);
                transition: opacity .18s ease, transform .18s ease;
                pointer-events: none;
            }
            .card-subcategoria:hover .sub-acciones {
                opacity: 1;
                transform: translateY(0);
                pointer-events: auto;
            }
        }

        /* Empty state */
        .sub-empty {
            grid-column: 1 / -1;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 60px 20px;
            color: #94a3b8;
            gap: 10px;
        }
        .sub-empty i   { font-size: 48px; color: #cbd5e1; }
        .sub-empty p   { font-size: 15px; margin: 0; }
        .sub-empty span{ font-size: 13px; }

        /* Input URL preview en modales */
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
            <a href="../../public/dashboardAdmin.php" class="imglogo">
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
                        <span class="bienvenido-texto">Bienvenido, <?php echo $row['rol'] === 'admin' ? 'Admin' : 'Gestor'; ?></span>
                        <span class="bienvenido-user">
                            <?php echo $row['nameuser']; ?>
                            <i class="bi bi-caret-down-fill"></i>
                        </span>
                    </div>
                </div>
                <div class="dropdown-menu" id="dropdown">
                    <a href="../../auth/logout.php">
                        <i class="bi bi-box-arrow-left"></i> Cerrar sesión
                    </a>
                </div>
            </div>
        </div>

        <!-- Menú principal -->
        <div class="main" id="menuPrincipal">
            <div class="orgmain">
                <a href="../Usuario/FormularioUser.php" class="menu-card">
                    <div class="icon"><i class="bi bi-person-gear"></i></div>
                    <h3>Usuarios</h3>
                    <p>Gestión de usuarios del sistema</p>
                </a>
            </div>
            <div class="orgmain">
                <a href="../Categoria/FormularioCat.php" class="menu-card">
                    <div class="icon"><i class="bi bi-bookmark-plus"></i></div>
                    <h3>Categorías</h3>
                    <p>Administrar categorías de productos</p>
                </a>
            </div>
            <div class="orgmain">
                <a class="menu-card">
                    <div class="icon"><i class="bi bi-diagram-3-fill"></i></div>
                    <h3>Subcategorías</h3>
                    <p>Gestionar subcategorías de productos</p>
                </a>
            </div>
            <div class="orgmain">
                <a href="../Productos/FormularioProduc.php" class="menu-card">
                    <div class="icon"><i class="bi bi-boxes"></i></div>
                    <h3>Productos</h3>
                    <p>Administrar productos del sistema</p>
                </a>
            </div>
        </div>

        <a href="../../auth/logout.php" class="btn-logout-head">
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
            <a href="../../public/dashboardAdmin.php">Página Principal</a>
            <span class="separator"><i class="bi bi-chevron-right separator"></i></span>
            <span class="actual">Subcategorías</span>
        </div>

        <div class="primeraPart">
            <div class="rotulaApratado">
                <h1>Gestión de Subcategorías</h1>
                <p>Administrar las subcategorías que organizan los productos</p>
            </div>

            <div class="barraSuperior">
                <!-- BUSCADOR -->
                <div class="filtrar">
                    <div class="input-busqueda">
                        <i class="bi bi-search"></i>
                        <input type="search" id="inputBuscarSub"
                               placeholder="Buscar por subcategoría"
                               autocomplete="off">
                    </div>
                </div>

                <!-- BOTÓN ACCIONES -->
                <button class="btnPanel" id="abrirPanel">
                    <i class="bi bi-list"></i> Acciones
                </button>

                <button id="btnAgregarSub" class="btn-agregar-barra">
                    <i class="bi bi-plus-lg"></i> Agregar Subcategoría
                </button>
            </div>

            <!-- PANEL LATERAL -->
            <div class="panelLateral" id="panelLateral">
                <div class="panelHeader">
                    <h3>Acciones</h3>
                    <span id="cerrarPanel">&times;</span>
                </div>
                <div class="panelContenido">

                    <a class="accion" id="btnImportarSub" style="cursor:pointer">
                        <i class="bi bi-file-earmark-arrow-up"></i>
                        <span>Importar subcategorías</span>
                    </a>
                    
                    <a class="accion" href="generarPlantillaSubcategorias.php" target="_blank">
                        <i class="bi bi-file-earmark-arrow-down"></i>
                        <span>Descargar plantilla Excel</span>
                    </a>
                    

                    <a class="accion accion-danger" id="btnActivarSeleccion">
                        <i class="bi bi-check2-square"></i>
                        <span>Eliminar varios</span>
                    </a>
                </div>
            </div>

            <!-- Modal importar subcategorías -->
            <div class="modal" id="modalImportarSub">
                <div class="modal-import">
                    <button class="modal-import-cerrar" id="cerrarModalImportSub">&times;</button>
                    <div class="modal-import-header">
                        <div class="modal-import-icon">
                            <i class="bi bi-file-earmark-excel-fill"></i>
                        </div>
                        <h2>Importar Subcategorías</h2>
                        <p>Carga masiva desde un archivo <strong>Excel (.xlsx)</strong></p>
                    </div>
                    <form id="formImportarSub" enctype="multipart/form-data">
                        <label class="dropzone" id="dropzoneSub" for="fileSubcat">
                            <input type="file" name="archivo" accept=".xlsx" required id="fileSubcat" hidden>
                            <i class="bi bi-cloud-arrow-up-fill dropzone-icon"></i>
                            <span class="dropzone-texto">Arrastra tu archivo aquí</span>
                            <span class="dropzone-sub">o haz clic para seleccionar</span>
                            <span class="dropzone-nombre" id="nombreArchivoSub">Ningún archivo seleccionado</span>
                        </label>
                        <a href="generarPlantillaSubcategorias.php" class="modal-import-plantilla">
                            <i class="bi bi-download"></i> Descargar plantilla de ejemplo
                        </a>
                        <button type="submit" class="modal-import-btn" id="btnSubmitSub" disabled>
                            <i class="bi bi-upload"></i> Importar
                        </button>
                    </form>
                    <div id="resultadoImportSub" class="modal-import-resultado"></div>
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

                document.getElementById("btnAgregarSub").addEventListener("click", () => {
                    document.getElementById("modalAgregarSub").style.display = "flex";
                });

                // ── Importar subcategorías ──────────────────────────────
                (function(){
                    const modal      = document.getElementById("modalImportarSub");
                    const fileInput  = document.getElementById("fileSubcat");
                    const nombreEl   = document.getElementById("nombreArchivoSub");
                    const btnSubmit  = document.getElementById("btnSubmitSub");
                    const dropzone   = document.getElementById("dropzoneSub");
                    const resultado  = document.getElementById("resultadoImportSub");

                    document.getElementById("btnImportarSub").addEventListener("click", () => {
                        cerrarPanel();
                        modal.style.display = "flex";
                    });

                    document.getElementById("cerrarModalImportSub").addEventListener("click", () => {
                        modal.style.display = "none";
                        resetForm();
                    });
                    window.addEventListener("click", e => {
                        if(e.target === modal){ modal.style.display = "none"; resetForm(); }
                    });

                    function resetForm(){
                        document.getElementById("formImportarSub").reset();
                        nombreEl.textContent = "Ningún archivo seleccionado";
                        dropzone.classList.remove("dropzone-activo");
                        btnSubmit.disabled = true;
                        resultado.innerHTML = "";
                    }

                    fileInput.addEventListener("change", () => {
                        if(fileInput.files.length){
                            nombreEl.textContent = fileInput.files[0].name;
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
                            nombreEl.textContent = file.name;
                            dropzone.classList.add("dropzone-activo");
                            btnSubmit.disabled = false;
                        }
                    });

                    document.getElementById("formImportarSub").addEventListener("submit", function(e){
                        e.preventDefault();
                        btnSubmit.disabled = true;
                        btnSubmit.innerHTML = '<i class="bi bi-arrow-repeat spin"></i> Importando...';
                        resultado.innerHTML = "";
                        fetch("importarSubcategorias.php", { method: "POST", body: new FormData(this) })
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

            <!-- Modal Agregar Subcategoría -->
            <div id="modalAgregarSub" class="modal">
                <div class="modal-contenido">
                    <span class="cerrar" id="cerrarModalAgregar">&times;</span>
                    <div class="modal-header">
                        <h2>Agregar Subcategoría</h2>
                    </div>
                    <br>
                    <form id="formAgregarSub" class="modal-form">

                        <label>Categoría padre</label>
                        <select name="idCategoria" id="addCategoria" required>
                            <option value="">— Seleccionar categoría —</option>
                            <?php
                                $qCat = mysqli_query($con, "SELECT idCategoria, nombreCategoria FROM categoria WHERE estadoCategoria='Activo' ORDER BY nombreCategoria");
                                while($c = mysqli_fetch_assoc($qCat)){
                                    echo "<option value='{$c['idCategoria']}'>" . htmlspecialchars($c['nombreCategoria']) . "</option>";
                                }
                            ?>
                        </select>

                        <label>Nombre de la Subcategoría</label>
                        <input type="text" name="nombreSubcategoria" id="addNombre" required>

                        <label>Estado</label>
                        <select name="estadoSubcategoria" id="addEstado">
                            <option value="Activo">Activo</option>
                            <option value="Oculto">Oculto</option>
                        </select>

                        <label>Imagen (URL) <span style="color:#94a3b8;font-weight:400;font-size:11px;">— opcional</span></label>
                        <div class="img-url-wrap">
                            <div class="img-url-placeholder" id="addImgPlaceholder">
                                <i class="bi bi-image"></i> Vista previa de la imagen
                            </div>
                            <img class="img-url-preview" id="addImgPreview" alt="Vista previa">
                            <input type="url" name="imagenUrl" id="addImagenUrl"
                                   placeholder="https://ejemplo.com/imagen.jpg"
                                   autocomplete="off">
                        </div>

                        <div id="mensajeAgregar"></div>

                        <div class="modal-botones">
                            <button type="submit" class="guardar">Guardar</button>
                            <button type="button" class="cancelar" id="cancelarModalAgregar">Cancelar</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Modal Editar Subcategoría -->
            <div id="modalEditarSub" class="modal">
                <div class="modal-contenido">
                    <span class="cerrar" id="cerrarModalEditar">&times;</span>
                    <h2>Editar Subcategoría</h2>
                    <br>
                    <form id="formEditarSub" class="modal-form">
                        <input type="hidden" name="id" id="editId">

                        <label>Categoría padre</label>
                        <select name="idCategoria" id="editCategoria" required>
                            <option value="">— Seleccionar categoría —</option>
                            <?php
                                $qCat2 = mysqli_query($con, "SELECT idCategoria, nombreCategoria FROM categoria WHERE estadoCategoria='Activo' ORDER BY nombreCategoria");
                                while($c2 = mysqli_fetch_assoc($qCat2)){
                                    echo "<option value='{$c2['idCategoria']}'>" . htmlspecialchars($c2['nombreCategoria']) . "</option>";
                                }
                            ?>
                        </select>

                        <label>Nombre de la Subcategoría</label>
                        <input type="text" name="nombre" id="editNombre" required>

                        <label>Estado</label>
                        <select name="estado" id="editEstado">
                            <option value="Activo">Activo</option>
                            <option value="Oculto">Oculto</option>
                        </select>

                        <label>Imagen (URL) <span style="color:#94a3b8;font-weight:400;font-size:11px;">— opcional</span></label>
                        <div class="img-url-wrap">
                            <div class="img-url-placeholder" id="editImgPlaceholder">
                                <i class="bi bi-image"></i> Vista previa de la imagen
                            </div>
                            <img class="img-url-preview" id="editImgPreview" alt="Vista previa">
                            <input type="url" name="imagenUrl" id="editImagenUrl"
                                   placeholder="https://ejemplo.com/imagen.jpg"
                                   autocomplete="off">
                        </div>

                        <div id="mensajeEditar"></div>

                        <div class="modal-botones">
                            <button type="submit" class="guardar">Actualizar</button>
                            <button type="button" class="cancelar" id="cancelarModalEditar">Cancelar</button>
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
                        <span id="seleccionContador" class="seleccion-sub">0 subcategorías seleccionadas</span>
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
                <h3 class="modal-confirm-titulo">¿Eliminar subcategorías?</h3>
                <p class="modal-confirm-sub">
                    Se eliminarán <strong id="confirmarNumero">0</strong> subcategoría(s) permanentemente.<br>
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

        <?php
            // Stats
            $statsRow = mysqli_fetch_assoc(mysqli_query($con,
                "SELECT COUNT(*) AS total,
                        SUM(estadoSubcategoria='Activo') AS activas,
                        SUM(estadoSubcategoria='Oculto') AS ocultas
                 FROM subcategoria"));
            $statTotal   = (int)($statsRow['total']   ?? 0);
            $statActivas = (int)($statsRow['activas'] ?? 0);
            $statOcultas = (int)($statsRow['ocultas'] ?? 0);

            // Category chips
            $qChipCats = mysqli_query($con, "SELECT idCategoria, nombreCategoria FROM categoria WHERE estadoCategoria='Activo' ORDER BY nombreCategoria");
            $chipCats = [];
            while ($cc = mysqli_fetch_assoc($qChipCats)) $chipCats[] = $cc;
        ?>

        <!-- Stats bar -->
        <div class="sub-stats-bar">
            <div class="sub-stat-card">
                <div class="sub-stat-icon total"><i class="bi bi-diagram-3-fill"></i></div>
                <div>
                    <div class="sub-stat-num" id="statSubTotal"><?php echo $statTotal; ?></div>
                    <div class="sub-stat-label">TOTAL</div>
                </div>
            </div>
            <div class="sub-stat-card">
                <div class="sub-stat-icon activas"><i class="bi bi-eye-fill"></i></div>
                <div>
                    <div class="sub-stat-num" id="statSubActivas"><?php echo $statActivas; ?></div>
                    <div class="sub-stat-label">ACTIVAS</div>
                </div>
            </div>
            <div class="sub-stat-card">
                <div class="sub-stat-icon ocultas"><i class="bi bi-eye-slash-fill"></i></div>
                <div>
                    <div class="sub-stat-num" id="statSubOcultas"><?php echo $statOcultas; ?></div>
                    <div class="sub-stat-label">OCULTAS</div>
                </div>
            </div>
        </div>

        <!-- Category filter chips -->
        <?php if (!empty($chipCats)): ?>
        <div class="sub-filtro-wrap">
            <span class="sub-filtro-label"><i class="bi bi-funnel-fill"></i> Cat.</span>
            <button class="sub-filtro-scroll-btn" id="filtroScrollLeft" title="Anterior">
                <i class="bi bi-chevron-left"></i>
            </button>
            <div class="sub-filtro-cats" id="filtroCats">
                <button class="sub-filtro-chip activo" data-cat-id="">Todas</button>
                <?php foreach ($chipCats as $cc): ?>
                <button class="sub-filtro-chip" data-cat-id="<?php echo $cc['idCategoria']; ?>">
                    <?php echo htmlspecialchars($cc['nombreCategoria']); ?>
                </button>
                <?php endforeach; ?>
            </div>
            <button class="sub-filtro-scroll-btn" id="filtroScrollRight" title="Siguiente">
                <i class="bi bi-chevron-right"></i>
            </button>
        </div>
        <?php endif; ?>

        <!-- Grid de subcategorías -->
        <div class="grid-subcategorias" id="gridSubcategorias">
            <?php
                $sqlSub = "SELECT s.idSubcategoria, s.nombreSubcategoria, s.estadoSubcategoria, s.fechaRegistro,
                               s.imagenUrl,
                               c.idCategoria, c.nombreCategoria,
                               COUNT(DISTINCT ps.idProducto) AS totalProductos
                           FROM subcategoria s
                           INNER JOIN categoria c ON c.idCategoria = s.idCategoria
                           LEFT JOIN productosubcategoria ps ON ps.idSubcategoria = s.idSubcategoria
                           GROUP BY s.idSubcategoria
                           ORDER BY c.nombreCategoria, s.nombreSubcategoria";
                $qSub = mysqli_query($con, $sqlSub);
                while($sub = mysqli_fetch_assoc($qSub)):
                    $estadoClass = strtolower($sub['estadoSubcategoria']);
                    $fecha = $sub['fechaRegistro'] ? date('d M Y', strtotime($sub['fechaRegistro'])) : '—';
            ?>
            <div class="card-subcategoria"
                 data-id="<?php echo $sub['idSubcategoria']; ?>"
                 data-nombre="<?php echo htmlspecialchars($sub['nombreSubcategoria']); ?>"
                 data-categoria-id="<?php echo $sub['idCategoria']; ?>">

                <!-- Checkbox selección múltiple -->
                <label class="sub-checkbox-wrap" title="Seleccionar">
                    <input type="checkbox" class="sub-checkbox" value="<?php echo $sub['idSubcategoria']; ?>">
                    <span class="sub-checkbox-custom"><i class="bi bi-check-lg"></i></span>
                </label>

                <!-- Badge estado -->
                <span class="sub-estado-badge sub-estado-<?php echo $estadoClass; ?>">
                    <?php echo $sub['estadoSubcategoria'] === 'Activo'
                        ? '<i class="bi bi-eye-fill"></i> Activo'
                        : '<i class="bi bi-eye-slash-fill"></i> Oculto'; ?>
                </span>

                <!-- Imagen -->
                <div class="sub-card-img-wrap">
                    <?php if(!empty($sub['imagenUrl'])): ?>
                    <img class="sub-card-img"
                         src="<?php echo htmlspecialchars($sub['imagenUrl']); ?>"
                         alt="<?php echo htmlspecialchars($sub['nombreSubcategoria']); ?>"
                         onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                    <div class="sub-card-img-placeholder" style="display:none;">
                        <i class="bi bi-image"></i>
                        <span>Sin imagen</span>
                    </div>
                    <?php else: ?>
                    <div class="sub-card-img-placeholder">
                        <i class="bi bi-image"></i>
                        <span>Sin imagen</span>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Body -->
                <div class="sub-card-body">
                    <p class="sub-card-nombre"><?php echo htmlspecialchars($sub['nombreSubcategoria']); ?></p>

                    <div class="sub-card-meta">
                        <span class="sub-cat-badge">
                            <i class="bi bi-bookmark-fill"></i> <?php echo htmlspecialchars($sub['nombreCategoria']); ?>
                        </span>
                        <span class="sub-prod-badge">
                            <i class="bi bi-box-seam"></i> <?php echo $sub['totalProductos']; ?>
                        </span>
                    </div>

                    <div class="sub-fecha"><i class="bi bi-calendar3"></i> <?php echo $fecha; ?></div>
                </div>

                <!-- Acciones: solo íconos -->
                <div class="sub-acciones">
                    <button class="sub-btn sub-btn-editar btnEditarSub"
                        title="Editar"
                        data-id="<?php echo $sub['idSubcategoria']; ?>"
                        data-nombre="<?php echo htmlspecialchars($sub['nombreSubcategoria']); ?>"
                        data-idcategoria="<?php echo $sub['idCategoria']; ?>"
                        data-estado="<?php echo $sub['estadoSubcategoria']; ?>"
                        data-imagen="<?php echo htmlspecialchars($sub['imagenUrl'] ?? ''); ?>">
                        <i class="bi bi-pencil-fill"></i>
                    </button>
                    <button class="sub-btn sub-btn-toggle btnToggleEstadoSub"
                        title="<?php echo $sub['estadoSubcategoria'] === 'Activo' ? 'Ocultar' : 'Activar'; ?>"
                        data-id="<?php echo $sub['idSubcategoria']; ?>"
                        data-estado="<?php echo $sub['estadoSubcategoria']; ?>">
                        <i class="bi <?php echo $sub['estadoSubcategoria'] === 'Activo' ? 'bi-eye-slash-fill' : 'bi-eye-fill'; ?>"></i>
                    </button>
                    <button class="sub-btn sub-btn-eliminar btnEliminarSub"
                        title="Eliminar"
                        data-id="<?php echo $sub['idSubcategoria']; ?>">
                        <i class="bi bi-trash-fill"></i>
                    </button>
                </div>
            </div>
            <?php endwhile; ?>

            <!-- Empty state -->
            <div class="sub-empty" id="subEmpty" style="display:none;">
                <i class="bi bi-search"></i>
                <p>No se encontraron subcategorías</p>
                <span>Prueba con otro filtro o término de búsqueda</span>
            </div>
        </div>

    </div><!-- /cuepo -->

    <!-- ══ SCRIPTS ══════════════════════════════════════════════════════ -->

    <!-- Modales Agregar / Editar -->
    <script>
    (function(){
        const modalAgregar = document.getElementById("modalAgregarSub");
        const cerrarAgregar = document.getElementById("cerrarModalAgregar");
        const cancelarAgregar = document.getElementById("cancelarModalAgregar");
        const formAgregar = document.getElementById("formAgregarSub");
        const msgAgregar = document.getElementById("mensajeAgregar");

        const modalEditar = document.getElementById("modalEditarSub");
        const cerrarEditar = document.getElementById("cerrarModalEditar");
        const cancelarEditar = document.getElementById("cancelarModalEditar");
        const formEditar = document.getElementById("formEditarSub");
        const msgEditar = document.getElementById("mensajeEditar");

        function cerrarModalAgregar(){
            modalAgregar.style.display = "none";
            formAgregar.reset();
            msgAgregar.innerHTML = "";
            document.getElementById("addImgPreview").style.display = "none";
            document.getElementById("addImgPlaceholder").style.display = "flex";
        }
        function cerrarModalEditar(){
            modalEditar.style.display = "none";
            formEditar.reset();
            msgEditar.innerHTML = "";
        }

        cerrarAgregar.onclick = cancelarAgregar.onclick = cerrarModalAgregar;
        cerrarEditar.onclick = cancelarEditar.onclick = cerrarModalEditar;

        window.addEventListener("click", e => {
            if(e.target === modalAgregar) cerrarModalAgregar();
            if(e.target === modalEditar) cerrarModalEditar();
        });

        // Vista previa imagen — Agregar
        document.getElementById("addImagenUrl").addEventListener("input", function(){
            setImgPreview(this.value, "addImgPreview", "addImgPlaceholder");
        });
        // Vista previa imagen — Editar
        document.getElementById("editImagenUrl").addEventListener("input", function(){
            setImgPreview(this.value, "editImgPreview", "editImgPlaceholder");
        });
        function setImgPreview(url, previewId, placeholderId){
            const img  = document.getElementById(previewId);
            const ph   = document.getElementById(placeholderId);
            const trim = url.trim();
            if(trim){
                img.src = trim;
                img.style.display = "block";
                ph.style.display  = "none";
                img.onerror = () => {
                    img.style.display = "none";
                    ph.style.display  = "flex";
                };
            } else {
                img.style.display = "none";
                img.src = "";
                ph.style.display  = "flex";
            }
        }

        // Abrir editar
        document.addEventListener("click", function(e){
            const btn = e.target.closest(".btnEditarSub");
            if(!btn) return;
            document.getElementById("editId").value = btn.dataset.id;
            document.getElementById("editNombre").value = btn.dataset.nombre;
            document.getElementById("editCategoria").value = btn.dataset.idcategoria;
            document.getElementById("editEstado").value = btn.dataset.estado;
            const imgUrl = btn.dataset.imagen || "";
            document.getElementById("editImagenUrl").value = imgUrl;
            setImgPreview(imgUrl, "editImgPreview", "editImgPlaceholder");
            msgEditar.innerHTML = "";
            modalEditar.style.display = "flex";
        });

        // Submit Agregar
        formAgregar.addEventListener("submit", function(e){
            e.preventDefault();
            fetch("agregarSubcategoria.php", { method:"POST", body: new FormData(formAgregar) })
            .then(r => r.json())
            .then(data => {
                if(data.status === "success"){
                    msgAgregar.innerHTML = `<div style="color:green;padding:8px">${data.message}</div>`;
                    setTimeout(() => { cerrarModalAgregar(); location.reload(); }, 900);
                } else {
                    msgAgregar.innerHTML = `<div style="color:#dc2626;padding:8px">${data.message}</div>`;
                }
            })
            .catch(() => { msgAgregar.innerHTML = '<div style="color:#dc2626;padding:8px">Error de conexión</div>'; });
        });

        // Submit Editar
        formEditar.addEventListener("submit", function(e){
            e.preventDefault();
            fetch("editarSubcategoria.php", { method:"POST", body: new FormData(formEditar) })
            .then(r => r.json())
            .then(data => {
                if(data.status === "success"){
                    msgEditar.innerHTML = `<div style="color:green;padding:8px">${data.message}</div>`;
                    setTimeout(() => { cerrarModalEditar(); location.reload(); }, 900);
                } else {
                    msgEditar.innerHTML = `<div style="color:#dc2626;padding:8px">${data.message}</div>`;
                }
            })
            .catch(() => { msgEditar.innerHTML = '<div style="color:#dc2626;padding:8px">Error de conexión</div>'; });
        });
    })();
    </script>

    <!-- Eliminar individual + Toggle estado -->
    <script>
    (function(){
        function animarEliminar(id){
            actualizarStatsSub();
            const card = document.querySelector(`.card-subcategoria[data-id="${id}"]`);
            if(card){
                card.style.transition = "transform .3s ease, opacity .3s ease";
                card.style.transform  = "scale(.85)";
                card.style.opacity    = "0";
                setTimeout(() => card.remove(), 320);
            }
        }

        // Eliminar individual
        document.addEventListener("click", function(e){
            const btn = e.target.closest(".btnEliminarSub");
            if(!btn) return;
            const id = btn.dataset.id;
            if(!confirm("¿Eliminar esta subcategoría?")) return;
            fetch(`eliminarSubcategoria.php?id=${id}`)
            .then(r => r.json())
            .then(data => {
                if(data.status === "success") animarEliminar(id);
                else toast.error(data.message);
            })
            .catch(() => toast.error("Error de conexión"));
        });

        // Toggle estado
        document.addEventListener("click", function(e){
            const btn = e.target.closest(".btnToggleEstadoSub");
            if(!btn) return;
            const id = btn.dataset.id;
            fetch("toggleEstadoSub.php", {
                method:"POST",
                headers:{"Content-Type":"application/json"},
                body: JSON.stringify({ id: parseInt(id) })
            })
            .then(r => r.json())
            .then(data => {
                if(data.status !== "success") return;
                const nuevo = data.nuevoEstado;
                const card  = btn.closest(".card-subcategoria");
                const badge = card.querySelector(".sub-estado-badge");

                btn.dataset.estado = nuevo;
                btn.title = nuevo === "Activo" ? "Ocultar" : "Activar";
                btn.innerHTML = nuevo === "Activo"
                    ? '<i class="bi bi-eye-slash-fill"></i> Ocultar'
                    : '<i class="bi bi-eye-fill"></i> Activar';

                badge.className = `sub-estado-badge sub-estado-${nuevo.toLowerCase()}`;
                badge.innerHTML = nuevo === "Activo"
                    ? '<i class="bi bi-eye-fill"></i> Activo'
                    : '<i class="bi bi-eye-slash-fill"></i> Oculto';
                actualizarStatsSub();
            })
            .catch(() => toast.error("Error de conexión"));
        });
    })();
    </script>

    <!-- Búsqueda + filtro categoría -->
    <script>
    (function(){
        let catFiltro = "";
        const inputBuscar = document.getElementById("inputBuscarSub");
        const subEmpty = document.getElementById("subEmpty");

        function aplicarFiltros(){
            const q = inputBuscar.value.trim().toLowerCase();
            let visible = 0;
            document.querySelectorAll(".card-subcategoria").forEach(card => {
                const nombre = card.dataset.nombre.toLowerCase();
                const catId  = card.dataset.categoriaId || "";
                const matchQ   = !q || nombre.includes(q);
                const matchCat = !catFiltro || catId === catFiltro;
                const show = matchQ && matchCat;
                card.style.display = show ? "" : "none";
                if (show) visible++;
            });
            if (subEmpty) subEmpty.style.display = visible === 0 ? "flex" : "none";
        }

        inputBuscar.addEventListener("input", aplicarFiltros);

        const filtroCats = document.getElementById("filtroCats");
        if (filtroCats) {
            filtroCats.addEventListener("click", function(e){
                const chip = e.target.closest(".sub-filtro-chip");
                if (!chip) return;
                filtroCats.querySelectorAll(".sub-filtro-chip").forEach(c => c.classList.remove("activo"));
                chip.classList.add("activo");
                catFiltro = chip.dataset.catId;
                aplicarFiltros();
            });

            // Scroll buttons
            const btnL = document.getElementById("filtroScrollLeft");
            const btnR = document.getElementById("filtroScrollRight");
            const STEP = 200;
            if (btnL && btnR) {
                btnL.addEventListener("click", () => { filtroCats.scrollLeft -= STEP; });
                btnR.addEventListener("click", () => { filtroCats.scrollLeft += STEP; });
            }
        }
    })();
    </script>

    <!-- Selección múltiple -->
    <script>
    (function(){
        const grid           = document.getElementById("gridSubcategorias");
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

        let modoSeleccion = false;

        function getCheckboxes(){ return document.querySelectorAll(".sub-checkbox"); }
        function getSeleccionados(){ return [...document.querySelectorAll(".sub-checkbox:checked")]; }

        function actualizarContador(){
            const n = getSeleccionados().length;
            contador.textContent = n + " subcategoría" + (n !== 1 ? "s" : "") + " seleccionada" + (n !== 1 ? "s" : "");
            btnEliminarSel.disabled = n === 0;
        }

        function activarModo(){
            modoSeleccion = true;
            grid.classList.add("modo-seleccion-sub");
            barra.classList.add("visible");
            document.getElementById("panelLateral").classList.remove("activo");
            document.getElementById("overlay").classList.remove("activo");
            actualizarContador();
        }

        function desactivarModo(){
            modoSeleccion = false;
            grid.classList.remove("modo-seleccion-sub");
            barra.classList.remove("visible");
            getCheckboxes().forEach(cb => {
                cb.checked = false;
                cb.closest(".card-subcategoria").classList.remove("sub-seleccionada");
            });
            actualizarContador();
        }

        btnActivar.addEventListener("click", activarModo);
        btnCancelarSel.addEventListener("click", desactivarModo);

        btnSelTodo.addEventListener("click", () => {
            getCheckboxes().forEach(cb => {
                cb.checked = true;
                cb.closest(".card-subcategoria").classList.add("sub-seleccionada");
            });
            actualizarContador();
        });

        btnSelNinguna.addEventListener("click", () => {
            getCheckboxes().forEach(cb => {
                cb.checked = false;
                cb.closest(".card-subcategoria").classList.remove("sub-seleccionada");
            });
            actualizarContador();
        });

        document.addEventListener("change", function(e){
            if(!e.target.classList.contains("sub-checkbox")) return;
            e.target.closest(".card-subcategoria").classList.toggle("sub-seleccionada", e.target.checked);
            actualizarContador();
        });

        document.addEventListener("click", function(e){
            if(!modoSeleccion) return;
            const card = e.target.closest(".card-subcategoria");
            if(!card) return;
            if(e.target.closest(".sub-acciones") || e.target.closest(".sub-checkbox-wrap")) return;
            const cb = card.querySelector(".sub-checkbox");
            cb.checked = !cb.checked;
            card.classList.toggle("sub-seleccionada", cb.checked);
            actualizarContador();
        });

        btnEliminarSel.addEventListener("click", () => {
            confirmarNum.textContent = getSeleccionados().length;
            modalConfirm.style.display = "flex";
        });

        cancelarBtn.addEventListener("click", () => { modalConfirm.style.display = "none"; });
        window.addEventListener("click", e => {
            if(e.target === modalConfirm) modalConfirm.style.display = "none";
        });

        confirmarBtn.addEventListener("click", function(){
            const ids = getSeleccionados().map(cb => cb.value);
            confirmarBtn.disabled = true;
            confirmarBtn.innerHTML = '<i class="bi bi-arrow-repeat spin"></i> Eliminando...';

            const fd = new FormData();
            ids.forEach(id => fd.append("ids[]", id));

            fetch("eliminarVariasSub.php", { method:"POST", body: fd })
            .then(r => r.json())
            .then(data => {
                modalConfirm.style.display = "none";
                confirmarBtn.disabled = false;
                confirmarBtn.innerHTML = '<i class="bi bi-trash-fill"></i> Sí, eliminar';
                ids.forEach(id => {
                    const card = document.querySelector(`.card-subcategoria[data-id="${id}"]`);
                    if(card){
                        card.style.transition = "transform .3s ease, opacity .3s ease";
                        card.style.transform  = "scale(.85)";
                        card.style.opacity    = "0";
                        setTimeout(() => card.remove(), 320);
                    }
                });
                actualizarStatsSub();
                setTimeout(desactivarModo, 400);
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

</div><!-- /contenedor -->
<script src="../../assets/toast.js"></script>
<script>
function actualizarStatsSub() {
    fetch('getStatsSub.php')
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
        anim(document.getElementById('statSubTotal'),   d.total);
        anim(document.getElementById('statSubActivas'), d.activas);
        anim(document.getElementById('statSubOcultas'), d.ocultas);
    }).catch(() => {});
}
</script>
</body>
</html>
