<?php
ob_start();
    /* Esto es para la conection */
    session_start();
    include("../../config/conection.php");
    $con= conection();

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
</head>
<body>
   <div class="contenedor">

        
        <div class="head">

            <!-- Logo que se envuentra en el Head -->
            <div class="imglogo" >

                <a href="../../public/dashboardAdmin.php" class="imglogo">
                    <img class="imagenlogo" 
                        src="../../assets/imagenes/logo.png" 
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
                            <a href="../../auth/logout.php">
                                <i class="bi bi-box-arrow-left"></i> Cerrar sesión
                            </a>
                        </div>
                    </div>

            </div>

            <!-- Menu de la pagina -->
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
                dropdown.style.display =
                    dropdown.style.display === "block" ? "none" : "block";
            });
            document.addEventListener("click", e => {
                if (!userMenu.contains(e.target)) dropdown.style.display = "none";
            });
        </script>

        <!-- Cuerpo de la pagina -->
         <div class="cuepo">
            <div class="ruta">
                <a href="../../public/dashboardAdmin.php">Página Principal</a>
                <span class="separator"><i class="bi bi-chevron-right separator"></i></span>
                <span class="actual">Estado</span>
            </div>

            <div class="primeraPart">
                <!-- Nombre del modulo -->
                <div class="rotulaApratado">
                    <h1>Gestión de Estados</h1>
                    <p>Gestionar los estados de los registros del sistema.</p>
                </div>
                <div class="barraSuperior">
                    <div class="buscarPanel">
                        <div class="input-busqueda">
                            <i class="bi bi-search"></i>
                            <input
                                type="search"
                                id="inputBuscarEst"
                                placeholder="Buscar estado..."
                                autocomplete="off"
                            >
                        </div>
                    </div>
                    <button class="btnPanel" id="abrirPanelEstado">
                        <i class="bi bi-list"></i> Acciones
                    </button>

                    <button id="btnAgregarEstadoPanel" class="btn-agregar-barra">
                        <i class="bi bi-plus-lg"></i> Agregar Estado
                    </button>

                </div>

                <!-- PANEL LATERAL -->
                <div class="panelLateral" id="panelLateralEstado">
                    <div class="panelHeader">
                        <h3>Acciones</h3>
                        <span id="cerrarPanelEstado">&times;</span>
                    </div>
                    <div class="panelContenido">
                        <a class="accion accion-danger" id="btnActivarSeleccionEst">
                            <i class="bi bi-check2-square"></i>
                            <span>Eliminar varios</span>
                        </a>
                    </div>
                </div>

                <!-- OVERLAY -->
                <div class="overlay" id="overlayEstado"></div>

                <script>
                    const abrirPanelEst  = document.getElementById("abrirPanelEstado");
                    const cerrarPanelEst = document.getElementById("cerrarPanelEstado");
                    const panelEst       = document.getElementById("panelLateralEstado");
                    const overlayEst     = document.getElementById("overlayEstado");

                    function cerrarPanelEstado(){
                        panelEst.classList.remove("activo");
                        overlayEst.classList.remove("activo");
                    }

                    abrirPanelEst.addEventListener("click", () => {
                        panelEst.classList.add("activo");
                        overlayEst.classList.add("activo");
                    });
                    cerrarPanelEst.addEventListener("click", cerrarPanelEstado);
                    overlayEst.addEventListener("click", cerrarPanelEstado);

                    document.getElementById("btnAgregarEstadoPanel").addEventListener("click", () => {
                        cerrarPanelEstado();
                        document.getElementById("modalAgregarEstado").style.display = "flex";
                    });
                </script>

                <!-- Modal Agregar Estado -->
                <div id="modalAgregarEstado" class="modal">
                    <div class="modal-contenido">
                        <span class="cerrar" id="cerrarModalEstado">&times;</span>
                        <div class="modal-header">
                            <h2>Agregar Estado</h2>
                        </div>
                        <br>
                        <form action="insertarEstado.php" method="POST" class="modal-form" id="formAgregarEstado">
                            <label>Nombre del Estado</label>
                            <input type="text" name="nombreEstado" id="nombreEstado" required>
                            <div id="mensajeAgregarEstado"></div>
                            <div class="modal-botones">
                                <button type="submit" class="guardar">Guardar</button>
                                <button type="button" class="cancelar" id="cancelarModalEstado">Cancelar</button>
                            </div>
                        </form>
                    </div>
                </div>
                <script>
                    const modalEstado    = document.getElementById("modalAgregarEstado");
                    const cerrarEstado   = document.getElementById("cerrarModalEstado");
                    const cancelarEstado = document.getElementById("cancelarModalEstado");
                    const formEstado     = document.getElementById("formAgregarEstado");
                    const mensajeEstado  = document.getElementById("mensajeAgregarEstado");

                    cerrarEstado.onclick  = () => cerrarModalEstado();
                    cancelarEstado.onclick = () => cerrarModalEstado();

                    function cerrarModalEstado(){
                        modalEstado.style.display = "none";
                        mensajeEstado.innerHTML = "";
                        formEstado.reset();
                    }

                    window.addEventListener("click", e => {
                        if(e.target === modalEstado) cerrarModalEstado();
                    });

                    formEstado.addEventListener("submit", function(e){
                        e.preventDefault();
                        fetch("insertarEstado.php", { method:"POST", body: new FormData(formEstado) })
                        .then(res => res.text())
                        .then(data => {
                            mensajeEstado.innerHTML = data;
                            if(data.includes("success")){
                                setTimeout(() => { cerrarModalEstado(); location.reload(); }, 800);
                            }
                        });
                    });
                </script>

            </div>
            <!-- ══ BARRA SELECCIÓN MÚLTIPLE ════════════════════ -->
            <div class="seleccion-barra" id="seleccionBarraEst">
                <div class="seleccion-barra-inner">
                    <div class="seleccion-info">
                        <div class="seleccion-icon-wrap">
                            <i class="bi bi-check2-square"></i>
                        </div>
                        <div class="seleccion-texto">
                            <span class="seleccion-titulo">Modo selección</span>
                            <span id="seleccionContadorEst" class="seleccion-sub">0 estados seleccionados</span>
                        </div>
                    </div>
                    <div class="seleccion-acciones">
                        <button class="seleccion-btn seleccion-btn-todo" id="btnSelTodoEst">
                            <i class="bi bi-check-all"></i> Todos
                        </button>
                        <button class="seleccion-btn seleccion-btn-ninguna" id="btnSelNingunaEst">
                            <i class="bi bi-dash-lg"></i> Ninguno
                        </button>
                        <button class="seleccion-btn seleccion-btn-eliminar" id="btnEliminarSelEst" disabled>
                            <i class="bi bi-trash3-fill"></i> Eliminar seleccionados
                        </button>
                        <button class="seleccion-btn seleccion-btn-cancelar" id="btnCancelarSelEst">
                            <i class="bi bi-x-lg"></i> Cancelar
                        </button>
                    </div>
                </div>
            </div>

            <div class="sliderEstados">

                <button class="btnSlide" id="prevEstado">
                    <i class="bi bi-chevron-left"></i>
                </button>

                <div class="contenedorEstados" id="contenedorEstados">
                    <?php
                    $sql = "SELECT * FROM estado ORDER BY nombreEstado";
                    $query = mysqli_query($con, $sql);

                    while($row = mysqli_fetch_assoc($query)){
                        $letra = strtoupper(substr($row['nombreEstado'], 0, 1));
                    ?>

                    <div class="estadoCard" data-id="<?php echo $row['idEstado']; ?>">

                        <!-- Checkbox selección múltiple -->
                        <label class="est-checkbox-wrap" title="Seleccionar">
                            <input type="checkbox" class="est-checkbox"
                                   value="<?php echo $row['idEstado']; ?>">
                            <span class="cat-checkbox-custom">
                                <i class="bi bi-check-lg"></i>
                            </span>
                        </label>

                        <!-- Letra / número -->
                        <div class="estadoLetra"><?php echo $letra; ?></div>

                        <!-- Nombre -->
                        <div class="estadoNombre"><?php echo htmlspecialchars($row['nombreEstado']); ?></div>

                        <!-- Botones hover -->
                        <div class="est-acciones">
                            <a href="#"
                               class="est-btn est-btn-editar btnEditarEstado"
                               title="Editar"
                               data-id="<?php echo $row['idEstado']; ?>"
                               data-nombre="<?php echo htmlspecialchars($row['nombreEstado']); ?>">
                                <i class="bi bi-pencil-fill"></i>
                            </a>
                            <button class="est-btn est-btn-eliminar btnEliminarUnoEst"
                                    title="Eliminar"
                                    data-id="<?php echo $row['idEstado']; ?>">
                                <i class="bi bi-trash-fill"></i>
                            </button>
                        </div>

                    </div>

                    <?php } ?>
                </div>

                <button class="btnSlide" id="nextEstado">
                    <i class="bi bi-chevron-right"></i>
                </button>

            </div>

            <!-- Modal Editar Estado -->
            <div class="modal" id="modalEditarEstado">
                <div class="modal-contenido">
                    <span class="cerrarEditarEstado">&times;</span>
                    <h2>Editar Estado</h2>
                    <form method="POST" action="editarEstado.php" id="formEditarEstado">
                        <input type="hidden" name="idEstado" id="editIdEstado">
                        <label>Nombre del Estado</label>
                        <input type="text" name="nombreEstado" id="editNombreEstado" required>
                        <div id="mensajeEditarEstado"></div>
                        <button type="submit">Actualizar</button>
                    </form>
                </div>
            </div>

            <!-- Modal confirmación eliminación masiva -->
            <div class="modal" id="modalConfirmElimEst">
                <div class="modal-confirm-contenido">
                    <div class="modal-confirm-icon">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                    </div>
                    <h3 class="modal-confirm-titulo">¿Eliminar estados?</h3>
                    <p class="modal-confirm-sub">
                        Se eliminarán <strong id="confirmarNumeroEst">0</strong> estado(s) permanentemente.<br>
                        Esta acción no se puede deshacer.
                    </p>
                    <div class="modal-confirm-botones">
                        <button class="modal-confirm-cancelar" id="confirmarCancelarEst">Cancelar</button>
                        <button class="modal-confirm-eliminar" id="confirmarEliminarEst">
                            <i class="bi bi-trash-fill"></i> Sí, eliminar
                        </button>
                    </div>
                </div>
            </div>

            <script>
                // ── Slider ──
                const contenedor = document.getElementById("contenedorEstados");
                document.getElementById("nextEstado").onclick = () => { contenedor.scrollLeft += 200; };
                document.getElementById("prevEstado").onclick = () => { contenedor.scrollLeft -= 200; };

                // ── Editar (botón en card) ──
                const modalEditarEstado    = document.getElementById("modalEditarEstado");
                const cerrarEditarEstado   = document.querySelector(".cerrarEditarEstado");
                const formEditarEstado     = document.getElementById("formEditarEstado");
                const mensajeEditarEstado  = document.getElementById("mensajeEditarEstado");

                document.querySelectorAll(".btnEditarEstado").forEach(boton => {
                    boton.addEventListener("click", function(e){
                        e.preventDefault();
                        e.stopPropagation();
                        document.getElementById("editIdEstado").value   = this.dataset.id;
                        document.getElementById("editNombreEstado").value = this.dataset.nombre;
                        mensajeEditarEstado.innerHTML = "";
                        modalEditarEstado.style.display = "flex";
                    });
                });

                cerrarEditarEstado.onclick = () => {
                    modalEditarEstado.style.display = "none";
                    mensajeEditarEstado.innerHTML = "";
                };
                window.addEventListener("click", e => {
                    if(e.target === modalEditarEstado){
                        modalEditarEstado.style.display = "none";
                        mensajeEditarEstado.innerHTML = "";
                    }
                });

                formEditarEstado.addEventListener("submit", function(e){
                    e.preventDefault();
                    fetch("editarEstado.php", { method:"POST", body: new FormData(formEditarEstado) })
                    .then(res => res.text())
                    .then(data => {
                        mensajeEditarEstado.innerHTML = data;
                        if(data.includes("success")){
                            setTimeout(() => { modalEditarEstado.style.display = "none"; location.reload(); }, 900);
                        }
                    });
                });

                // ── Eliminar uno (botón en card) ──
                document.querySelectorAll(".btnEliminarUnoEst").forEach(btn => {
                    btn.addEventListener("click", function(e){
                        e.stopPropagation();
                        const id   = this.dataset.id;
                        const card = this.closest(".estadoCard");
                        document.getElementById("confirmarNumeroEst").textContent = 1;
                        document.getElementById("modalConfirmElimEst").style.display = "flex";
                        // guardar referencia temporal
                        document.getElementById("modalConfirmElimEst").dataset.idsTemp = JSON.stringify([id]);
                        document.getElementById("modalConfirmElimEst").dataset.cardId  = id;
                    });
                });
            </script>

            <script>
            // ── Selección múltiple ──
            (function(){
                const barra          = document.getElementById("seleccionBarraEst");
                const contador       = document.getElementById("seleccionContadorEst");
                const btnEliminarSel = document.getElementById("btnEliminarSelEst");
                const btnSelTodo     = document.getElementById("btnSelTodoEst");
                const btnSelNinguna  = document.getElementById("btnSelNingunaEst");
                const btnCancelarSel = document.getElementById("btnCancelarSelEst");
                const btnActivar     = document.getElementById("btnActivarSeleccionEst");

                const modalConfirm   = document.getElementById("modalConfirmElimEst");
                const confirmarNum   = document.getElementById("confirmarNumeroEst");
                const confirmarBtn   = document.getElementById("confirmarEliminarEst");
                const cancelarBtn    = document.getElementById("confirmarCancelarEst");

                let modoSeleccion = false;

                const getCheckboxes  = () => document.querySelectorAll(".est-checkbox");
                const getSeleccionados = () => [...document.querySelectorAll(".est-checkbox:checked")];

                function actualizarContador(){
                    const n = getSeleccionados().length;
                    contador.textContent = n + " estado" + (n!==1?"s":"") + " seleccionado" + (n!==1?"s":"");
                    btnEliminarSel.disabled = n === 0;
                }

                function getHeaderH(){
                    return document.querySelector(".head")?.offsetHeight || 0;
                }

                function ajustarStickyBarra(){
                    barra.style.top = getHeaderH() + "px";
                }

                function activarModo(){
                    modoSeleccion = true;
                    document.getElementById("contenedorEstados").classList.add("modo-seleccion-est");
                    barra.classList.add("visible");
                    cerrarPanelEstado();
                    actualizarContador();
                    setTimeout(ajustarStickyBarra, 400);
                }

                function desactivarModo(){
                    modoSeleccion = false;
                    document.getElementById("contenedorEstados").classList.remove("modo-seleccion-est");
                    barra.classList.remove("visible");
                    barra.style.top = "";
                    getCheckboxes().forEach(cb => {
                        cb.checked = false;
                        cb.closest(".estadoCard")?.classList.remove("est-seleccionada");
                    });
                    actualizarContador();
                }

                btnActivar.addEventListener("click", activarModo);
                btnCancelarSel.addEventListener("click", desactivarModo);

                btnSelTodo.addEventListener("click", () => {
                    getCheckboxes().forEach(cb => {
                        cb.checked = true;
                        cb.closest(".estadoCard")?.classList.add("est-seleccionada");
                    });
                    actualizarContador();
                });

                btnSelNinguna.addEventListener("click", () => {
                    getCheckboxes().forEach(cb => {
                        cb.checked = false;
                        cb.closest(".estadoCard")?.classList.remove("est-seleccionada");
                    });
                    actualizarContador();
                });

                document.addEventListener("change", function(e){
                    if(!e.target.classList.contains("est-checkbox")) return;
                    e.target.closest(".estadoCard")?.classList.toggle("est-seleccionada", e.target.checked);
                    actualizarContador();
                });

                // Click en card activa checkbox en modo selección
                document.addEventListener("click", function(e){
                    if(!modoSeleccion) return;
                    const card = e.target.closest(".estadoCard");
                    if(!card) return;
                    if(e.target.closest(".est-acciones") || e.target.closest(".est-checkbox-wrap")) return;
                    const cb = card.querySelector(".est-checkbox");
                    if(!cb) return;
                    cb.checked = !cb.checked;
                    card.classList.toggle("est-seleccionada", cb.checked);
                    actualizarContador();
                });

                // Abrir confirm para selección múltiple
                btnEliminarSel.addEventListener("click", () => {
                    const ids = getSeleccionados().map(cb => cb.value);
                    confirmarNum.textContent = ids.length;
                    modalConfirm.dataset.idsTemp = JSON.stringify(ids);
                    modalConfirm.style.display = "flex";
                });

                cancelarBtn.addEventListener("click", () => { modalConfirm.style.display = "none"; });
                window.addEventListener("click", e => {
                    if(e.target === modalConfirm) modalConfirm.style.display = "none";
                });

                // ── Filtro en tiempo real (sin recargar página) ──────────
                document.getElementById("inputBuscarEst").addEventListener("input", function(){
                    const q = this.value.trim().toLowerCase();
                    document.querySelectorAll(".estadoCard").forEach(card => {
                        const nombre = card.querySelector(".estadoNombre")?.textContent.toLowerCase() || "";
                        card.style.display = (!q || nombre.includes(q)) ? "" : "none";
                    });
                });

                // Ejecutar eliminación
                confirmarBtn.addEventListener("click", function(){
                    const ids = JSON.parse(modalConfirm.dataset.idsTemp || "[]");
                    if(!ids.length) return;

                    confirmarBtn.disabled = true;
                    confirmarBtn.innerHTML = '<i class="bi bi-arrow-repeat spin"></i> Eliminando...';

                    fetch("eliminarEstado.php", {
                        method: "POST",
                        headers: { "Content-Type": "application/json" },
                        body: JSON.stringify({ ids: ids })
                    })
                    .then(r => r.json())
                    .then(data => {
                        modalConfirm.style.display = "none";
                        if(data.status === "success"){
                            ids.forEach(id => {
                                const card = document.querySelector(`.estadoCard[data-id="${id}"]`);
                                if(card){
                                    card.style.transition = "transform 0.3s ease, opacity 0.3s ease";
                                    card.style.transform  = "scale(0.8)";
                                    card.style.opacity    = "0";
                                    setTimeout(() => card.remove(), 320);
                                }
                            });
                            setTimeout(desactivarModo, 400);
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
        </div>

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
</body>
</html>