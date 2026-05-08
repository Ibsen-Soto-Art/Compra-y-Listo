<?php
ob_start();
session_start();
include(ROOT_PATH . "/config/conection.php");
$con = conection();

if(!isset($_SESSION['usuarios'])){
    header("location:" . SITE_URL . "/auth/login.php");
    exit();
}

$idSesion = $_SESSION['idUsuario'];
$rowSesion = mysqli_fetch_assoc(mysqli_query($con, "SELECT nombreUsuario AS nameuser, rol FROM usuarios WHERE idUsuario=$idSesion"));
$rolUsuario = $rowSesion['rol'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Compra y Listo – Inventario</title>
    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
    <link rel="preload" href="<?= SITE_URL ?>/assets/styleAll.min.css" as="style">
    <link rel="preload" href="<?= SITE_URL ?>/assets/mobile-admin.min.css" as="style">
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/styleAll.min.css">
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/mobile-admin.min.css">
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/admin-overrides.css">
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/bootstrap-icons/bootstrap-icons.css" media="print" onload="this.media='all'">
    <noscript><link rel="stylesheet" href="<?= SITE_URL ?>/assets/bootstrap-icons/bootstrap-icons.css"></noscript>
</head>
<body>
<div class="contenedor">

    <!-- HEADER -->
    <div class="head">
        <div class="imglogo">
            <a href="<?= SITE_URL ?>/admin" class="imglogo">
                <img class="imagenlogo" src="<?= SITE_URL ?>/assets/imagenes/logo.png" alt="Logo">
            </a>
            <div class="saludo" id="userMenu">
                <div class="user-info">
                    <div class="user-text">
                        <span class="bienvenido-texto">Bienvenido, <?php echo $rolUsuario==='admin'?'Admin':'Gestor'; ?></span>
                        <span class="bienvenido-user"><?php echo htmlspecialchars($rowSesion['nameuser']); ?> <i class="bi bi-caret-down-fill"></i></span>
                    </div>
                </div>
                <div class="dropdown-menu" id="dropdown">
                    <a href="<?= SITE_URL ?>/auth/logout.php"><i class="bi bi-box-arrow-left"></i> Cerrar sesión</a>
                </div>
            </div>
        </div>

        <div class="main" id="menuPrincipal">
            <div class="orgmain"><a href="<?= SITE_URL ?>/admin/usuarios" class="menu-card"><div class="icon"><i class="bi bi-person-gear"></i></div><h3>Usuarios</h3><p>Gestión de usuarios</p></a></div>
            <div class="orgmain"><a href="<?= SITE_URL ?>/admin/categorias" class="menu-card"><div class="icon"><i class="bi bi-bookmark-plus"></i></div><h3>Categorías</h3><p>Administrar categorías</p></a></div>
            <div class="orgmain"><a href="<?= SITE_URL ?>/admin/subcategorias" class="menu-card"><div class="icon"><i class="bi bi-diagram-3-fill"></i></div><h3>Subcategorías</h3><p>Gestionar subcategorías de productos</p></a></div>
            <div class="orgmain"><a class="menu-card"><div class="icon"><i class="bi bi-archive-fill"></i></div><h3>Inventario</h3><p>Control de inventario</p></a></div>
            <div class="orgmain"><a href="<?= SITE_URL ?>/admin/productos" class="menu-card"><div class="icon"><i class="bi bi-boxes"></i></div><h3>Productos</h3><p>Administrar productos</p></a></div>
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

    <!-- CUERPO -->
    <div class="cuepo">
        <div class="ruta">
            <a href="<?= SITE_URL ?>/admin"><i class="bi bi-house-fill"></i> Panel</a>
            <span class="separator"><i class="bi bi-chevron-right"></i></span>
            <span class="actual"><i class="bi bi-archive-fill"></i> Inventario</span>
        </div>

        <div class="primeraPart">
            <div class="rotulaApratado">
                <h1>Gestión de Inventario</h1>
                <p>Controla el stock de cada producto por número de serie</p>
            </div>

            <!-- Buscador -->
            <div class="barraSuperior">
                <div class="buscarPanel">
                    <div class="input-busqueda">
                        <i class="bi bi-search"></i>
                        <input type="search" id="inputBuscarInv" placeholder="Buscar producto..." autocomplete="off">
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabla de productos con stock -->
        <div class="inv-tabla-wrap">
            <table class="inv-tabla" id="tablaInventario">
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th>Subcategoría</th>
                        <th>Disponibles</th>
                        <th>Vendidos</th>
                        <th>Total</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $qProd = mysqli_query($con, "
                    SELECT
                        p.idProducto,
                        p.nombreProducto,
                        COALESCE(GROUP_CONCAT(DISTINCT s.nombreSubcategoria ORDER BY s.nombreSubcategoria SEPARATOR ', '), '—') AS subcategorias,
                        (SELECT COUNT(*) FROM iteminventario WHERE idProducto=p.idProducto AND estadoItem='Disponible') AS disponibles,
                        (SELECT COUNT(*) FROM iteminventario WHERE idProducto=p.idProducto AND estadoItem='Vendido')    AS vendidos,
                        (SELECT COUNT(*) FROM iteminventario WHERE idProducto=p.idProducto)                             AS total
                    FROM producto p
                    LEFT JOIN productosubcategoria ps ON ps.idProducto = p.idProducto
                    LEFT JOIN subcategoria s           ON s.idSubcategoria = ps.idSubcategoria
                    GROUP BY p.idProducto
                    ORDER BY p.nombreProducto ASC
                ");
                while($p = mysqli_fetch_assoc($qProd)):
                    $badgeClass = $p['disponibles'] > 0 ? 'inv-badge-ok' : 'inv-badge-out';
                ?>
                <tr class="inv-fila" data-nombre="<?php echo strtolower(htmlspecialchars($p['nombreProducto'])); ?>">
                    <td class="inv-nombre"><?php echo htmlspecialchars($p['nombreProducto']); ?></td>
                    <td><span class="inv-subcat"><?php echo htmlspecialchars($p['subcategorias']); ?></span></td>
                    <td><span class="inv-badge <?php echo $badgeClass; ?>"><?php echo $p['disponibles']; ?></span></td>
                    <td><?php echo $p['vendidos']; ?></td>
                    <td><?php echo $p['total']; ?></td>
                    <td>
                        <button class="inv-btn-items" data-id="<?php echo $p['idProducto']; ?>" data-nombre="<?php echo htmlspecialchars($p['nombreProducto']); ?>">
                            <i class="bi bi-list-ul"></i> Ver ítems
                        </button>
                    </td>
                </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- MODAL: Ítems de inventario de un producto -->
    <div class="modal" id="modalItems" style="display:none;">
        <div class="modal-contenido" style="max-width:640px;max-height:90vh;display:flex;flex-direction:column;overflow:hidden;">
            <span id="cerrarModalItems" class="cerrar">&times;</span>
            <h2 id="modalItemsTitulo" style="flex-shrink:0;">Ítems de inventario</h2>

            <!-- Barra superior del modal -->
            <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;flex-shrink:0;margin-bottom:10px;">
                <button class="inv-btn-agregar" id="btnAgregarItem" style="flex-shrink:0;">
                    <i class="bi bi-plus-lg"></i> Agregar ítems
                </button>
                <div style="flex:1;min-width:160px;display:flex;align-items:center;gap:6px;background:#f1f5f9;border-radius:8px;padding:7px 12px;">
                    <i class="bi bi-search" style="color:#94a3b8;"></i>
                    <input type="search" id="inputBuscarItems" placeholder="Buscar N° serie…"
                        style="border:none;background:transparent;outline:none;font-size:13px;width:100%;" autocomplete="off">
                </div>
            </div>

            <!-- Barra selección múltiple (oculta por defecto) -->
            <div id="barraSelItems" style="display:none;flex-shrink:0;background:#fef3c7;border:1px solid #fcd34d;border-radius:8px;padding:8px 14px;margin-bottom:8px;display:none;align-items:center;gap:10px;flex-wrap:wrap;">
                <span id="contSelItems" style="font-size:13px;font-weight:600;color:#92400e;flex:1;">0 seleccionados</span>
                <button id="btnSelTodosItems" style="font-size:12px;padding:4px 10px;border:1px solid #d97706;border-radius:6px;background:#fff;color:#92400e;cursor:pointer;">
                    <i class="bi bi-check-all"></i> Todos
                </button>
                <button id="btnSelNingunoItems" style="font-size:12px;padding:4px 10px;border:1px solid #d97706;border-radius:6px;background:#fff;color:#92400e;cursor:pointer;">
                    <i class="bi bi-dash-lg"></i> Ninguno
                </button>
                <button id="btnEliminarSelItems" disabled style="font-size:12px;padding:4px 12px;border:none;border-radius:6px;background:#dc2626;color:#fff;cursor:pointer;opacity:.5;">
                    <i class="bi bi-trash3-fill"></i> Eliminar sel.
                </button>
                <button id="btnCancelarSelItems" style="font-size:12px;padding:4px 10px;border:1px solid #94a3b8;border-radius:6px;background:#fff;color:#64748b;cursor:pointer;">
                    <i class="bi bi-x-lg"></i> Cancelar
                </button>
            </div>

            <!-- Botón activar selección múltiple -->
            <div style="flex-shrink:0;margin-bottom:6px;">
                <button id="btnActivarSelItems" style="font-size:12px;padding:4px 12px;border:1px solid #e2e8f0;border-radius:6px;background:#fff;color:#64748b;cursor:pointer;">
                    <i class="bi bi-check2-square"></i> Eliminar varios
                </button>
            </div>

            <!-- Lista de ítems (scrolleable) -->
            <div class="inv-items-wrap" id="listaItems" style="overflow-y:auto;flex:1;min-height:0;">
                <p class="inv-cargando"><i class="bi bi-arrow-repeat spin"></i> Cargando...</p>
            </div>
        </div>
    </div>

    <!-- MODAL: Agregar ítems masivo -->
    <div class="modal" id="modalAgregarMasivo" style="display:none;">
        <div class="modal-contenido" style="max-width:460px;">
            <span id="cerrarAgregarMasivo" class="cerrar">&times;</span>
            <h2><i class="bi bi-plus-square-fill" style="color:#6366f1;margin-right:8px;"></i>Agregar ítems</h2>

            <div id="masivoInfoPrefix" style="background:#f1f5f9;border-radius:10px;padding:12px 16px;margin:14px 0 6px;">
                <span style="font-size:12px;color:#64748b;font-weight:600;text-transform:uppercase;letter-spacing:.5px;">Prefijo generado</span>
                <div style="font-size:22px;font-weight:700;color:#4f46e5;letter-spacing:2px;margin-top:4px;" id="masivoPrefix">—</div>
                <span style="font-size:11px;color:#94a3b8;" id="masivoOrigenPrefix"></span>
            </div>

            <div class="modal-form" style="margin-top:0;">
                <label>Cantidad de ítems a agregar</label>
                <input type="number" id="masivoCantidad" min="1" max="200" value="1" style="font-size:18px;font-weight:700;text-align:center;">

                <label>Estado inicial</label>
                <select id="masivoEstado">
                    <option value="Disponible">Disponible</option>
                    <option value="Vendido">Vendido</option>
                </select>

                <div id="masivoPreview" style="background:#f8fafc;border:1px dashed #cbd5e1;border-radius:8px;padding:10px 14px;font-size:13px;color:#475569;min-height:36px;margin-top:4px;"></div>

                <div id="masivoMsg"></div>

                <div class="modal-botones">
                    <button type="button" class="guardar" id="btnConfirmarMasivo">
                        <i class="bi bi-plus-lg"></i> Agregar ítems
                    </button>
                    <button type="button" class="cancelar" id="btnCancelarMasivo">Cancelar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL: Editar ítem individual -->
    <div class="modal" id="modalFormItem" style="display:none;">
        <div class="modal-contenido" style="max-width:420px;">
            <span id="cerrarFormItem" class="cerrar">&times;</span>
            <h2 id="formItemTitulo">Editar ítem</h2>
            <form id="formItem" class="modal-form">
                <input type="hidden" id="itemIdProducto">
                <input type="hidden" id="itemIdItem">

                <label>Número de serie</label>
                <input type="text" id="itemNumSerie" placeholder="Ej: SN-001" required>

                <label>Estado</label>
                <select id="itemEstado">
                    <option value="Disponible">Disponible</option>
                    <option value="Vendido">Vendido</option>
                </select>

                <div id="formItemMsg"></div>

                <div class="modal-botones">
                    <button type="submit" class="guardar" id="btnGuardarItem">Guardar</button>
                    <button type="button" class="cancelar" id="btnCancelarFormItem">Cancelar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Footer -->
    <div class="footer">
        <div class="footer-container">
            <div class="footer-brand"><h3>Compra y Listo</h3><p>La forma más fácil de comprar y vender</p></div>
            <div class="footer-contacto">
                <h4>Contacto</h4>
                <a href="mailto:compraylisto24@gmail.com"><i class="bi bi-envelope-fill"></i> compraylisto24@gmail.com</a>
                <span><i class="bi bi-geo-alt-fill"></i> Florencia, Caquetá</span>
            </div>
            <div class="footer-social">
                <h4>Síguenos</h4>
                <div class="footer-social-iconos">
                    <a href="https://www.facebook.com/share/1DCnyAL7zk/?mibextid=wwXIfr" target="_blank" class="fb"><i class="bi bi-facebook"></i></a>
                    <a href="https://www.tiktok.com/@compraylisto04" target="_blank" class="tt"><i class="bi bi-tiktok"></i></a>
                    <a href="https://wa.me/573123048308" target="_blank" class="wa"><i class="bi bi-whatsapp"></i></a>
                </div>
            </div>
        </div>
        <div class="footer-bottom"><p>© 2026 Compra y Listo | Desarrollado por Ibsen Soto | v1.3.0</p></div>
    </div>

</div>

<script>
// ── Filtro en tiempo real ─────────────────────────────────
document.getElementById("inputBuscarInv").addEventListener("input", function(){
    const q = this.value.trim().toLowerCase();
    document.querySelectorAll(".inv-fila").forEach(row => {
        row.style.display = (!q || row.dataset.nombre.includes(q)) ? "" : "none";
    });
});

// ── Referencias globales ──────────────────────────────────
const modalItems       = document.getElementById("modalItems");
const listaItems       = document.getElementById("listaItems");
const modalItemsTit    = document.getElementById("modalItemsTitulo");
const modalFormItem    = document.getElementById("modalFormItem");
const modalAgregarMas  = document.getElementById("modalAgregarMasivo");
const formItem         = document.getElementById("formItem");
const formItemMsg      = document.getElementById("formItemMsg");
let productoActual     = null;
let masivoInfo         = null; // {prefix, nextNum}

// ── Abrir modal ítems ─────────────────────────────────────
document.querySelectorAll(".inv-btn-items").forEach(btn => {
    btn.addEventListener("click", function(){
        productoActual = { id: this.dataset.id, nombre: this.dataset.nombre };
        modalItemsTit.textContent = "Ítems — " + productoActual.nombre;
        modalItems.style.display = "flex";
        cargarItems(productoActual.id);
    });
});

// Cerrar modales
document.getElementById("cerrarModalItems").onclick  = () => {
    modalItems.style.display = "none";
    modoSelItems = false;
    barraSelItems.style.display = "none";
    btnActivarSelItems.style.display = "";
};
document.getElementById("cerrarFormItem").onclick    = () => modalFormItem.style.display = "none";
document.getElementById("btnCancelarFormItem").onclick = () => modalFormItem.style.display = "none";
document.getElementById("cerrarAgregarMasivo").onclick = () => modalAgregarMas.style.display = "none";
document.getElementById("btnCancelarMasivo").onclick   = () => modalAgregarMas.style.display = "none";

window.addEventListener("click", e => {
    if(e.target === modalItems){
        modalItems.style.display = "none";
        modoSelItems = false;
        barraSelItems.style.display = "none";
        btnActivarSelItems.style.display = "";
    }
    if(e.target === modalFormItem)   modalFormItem.style.display   = "none";
    if(e.target === modalAgregarMas) modalAgregarMas.style.display = "none";
});

// ── Selección múltiple en modal ítems ────────────────────
let modoSelItems = false;

const barraSelItems      = document.getElementById("barraSelItems");
const contSelItems       = document.getElementById("contSelItems");
const btnElimSelItems    = document.getElementById("btnEliminarSelItems");
const btnActivarSelItems = document.getElementById("btnActivarSelItems");

btnActivarSelItems.addEventListener("click", () => {
    modoSelItems = true;
    barraSelItems.style.display = "flex";
    btnActivarSelItems.style.display = "none";
    listaItems.querySelectorAll(".inv-check-item").forEach(cb => cb.closest("td").style.display = "");
    actualizarContSelItems();
});

document.getElementById("btnCancelarSelItems").addEventListener("click", () => {
    modoSelItems = false;
    barraSelItems.style.display = "none";
    btnActivarSelItems.style.display = "";
    listaItems.querySelectorAll(".inv-check-item").forEach(cb => {
        cb.checked = false;
        cb.closest("td").style.display = "none";
    });
    actualizarContSelItems();
});

document.getElementById("btnSelTodosItems").addEventListener("click", () => {
    listaItems.querySelectorAll(".inv-check-item:not([style*='display: none'])").forEach(cb => {
        const tr = cb.closest("tr");
        if(!tr || tr.style.display === "none") return;
        cb.checked = true;
    });
    actualizarContSelItems();
});

document.getElementById("btnSelNingunoItems").addEventListener("click", () => {
    listaItems.querySelectorAll(".inv-check-item").forEach(cb => cb.checked = false);
    actualizarContSelItems();
});

btnElimSelItems.addEventListener("click", () => {
    const ids = [...listaItems.querySelectorAll(".inv-check-item:checked")].map(cb => cb.value);
    if(!ids.length) return;
    if(!confirm(`¿Eliminar ${ids.length} ítem(s)?`)) return;
    btnElimSelItems.disabled = true;

    const fd = new FormData();
    ids.forEach(id => fd.append("ids[]", id));
    fetch("<?= SITE_URL ?>/api/inventario/eliminar-varios", { method:"POST", body: fd })
    .then(r => r.json())
    .then(d => {
        if(d.status === "success"){
            modoSelItems = false;
            barraSelItems.style.display = "none";
            btnActivarSelItems.style.display = "";
            cargarItems(productoActual.id);
        }
        btnElimSelItems.disabled = false;
    })
    .catch(() => btnElimSelItems.disabled = false);
});

function actualizarContSelItems(){
    const n = listaItems.querySelectorAll(".inv-check-item:checked").length;
    contSelItems.textContent = `${n} seleccionado${n !== 1 ? 's' : ''}`;
    btnElimSelItems.disabled = n === 0;
    btnElimSelItems.style.opacity = n > 0 ? "1" : ".5";
}

// ── Buscador dentro del modal ─────────────────────────────
document.getElementById("inputBuscarItems").addEventListener("input", function(){
    const q = this.value.trim().toLowerCase();
    listaItems.querySelectorAll("tr.inv-item-fila").forEach(tr => {
        const serie = tr.dataset.serie || "";
        tr.style.display = (!q || serie.includes(q)) ? "" : "none";
    });
    actualizarContSelItems();
});

// ── Cargar ítems en el modal ──────────────────────────────
function cargarItems(idProducto){
    listaItems.innerHTML = '<p class="inv-cargando"><i class="bi bi-arrow-repeat spin"></i> Cargando...</p>';
    document.getElementById("inputBuscarItems").value = "";
    fetch("<?= SITE_URL ?>/api/inventario/items?idProducto=" + idProducto)
    .then(r => r.json())
    .then(items => {
        if(!items.length){
            listaItems.innerHTML = '<p class="inv-vacio">Sin ítems registrados. Agrega el primero.</p>';
            return;
        }
        let html = '<table class="inv-items-tabla"><thead><tr>' +
            '<th style="width:32px;"></th>' +
            '<th>N° Serie</th><th>Estado</th><th></th>' +
            '</tr></thead><tbody>';
        items.forEach(it => {
            const cls = it.estadoItem === 'Disponible' ? 'inv-badge-ok' : 'inv-badge-out';
            html += `<tr class="inv-item-fila" data-serie="${it.numeroSerie.toLowerCase()}">
                <td style="${modoSelItems ? '' : 'display:none'}">
                    <input type="checkbox" class="inv-check-item" value="${it.idItemInventario}"
                        style="width:16px;height:16px;accent-color:#6366f1;cursor:pointer;">
                </td>
                <td class="inv-serie">${it.numeroSerie}</td>
                <td><span class="inv-badge ${cls}">${it.estadoItem}</span></td>
                <td class="inv-acciones-row">
                    <button class="inv-btn-edit-item"
                        data-id="${it.idItemInventario}"
                        data-serie="${it.numeroSerie}"
                        data-estado="${it.estadoItem}">
                        <i class="bi bi-pencil-fill"></i>
                    </button>
                    <button class="inv-btn-del-item" data-id="${it.idItemInventario}">
                        <i class="bi bi-trash-fill"></i>
                    </button>
                </td>
            </tr>`;
        });
        html += '</tbody></table>';
        listaItems.innerHTML = html;

        listaItems.querySelectorAll(".inv-check-item").forEach(cb => {
            cb.addEventListener("change", actualizarContSelItems);
        });

        listaItems.querySelectorAll(".inv-btn-edit-item").forEach(b => {
            b.addEventListener("click", function(){
                abrirFormItem(productoActual.id, this.dataset.id, this.dataset.serie, this.dataset.estado);
            });
        });

        listaItems.querySelectorAll(".inv-btn-del-item").forEach(b => {
            b.addEventListener("click", function(){
                if(!confirm("¿Eliminar este ítem?")) return;
                fetch("<?= SITE_URL ?>/api/inventario/eliminar", { method:"POST", body: new URLSearchParams({ id: this.dataset.id }) })
                .then(r => r.json())
                .then(d => { if(d.status==="success") cargarItems(productoActual.id); });
            });
        });

        actualizarContSelItems();
    })
    .catch(() => { listaItems.innerHTML = '<p style="color:red">Error de conexión</p>'; });
}

// ── Botón "Agregar ítem" → abre modal masivo ──────────────
document.getElementById("btnAgregarItem").onclick = () => {
    document.getElementById("masivoCantidad").value = 1;
    document.getElementById("masivoEstado").value   = "Disponible";
    document.getElementById("masivoPrefix").textContent   = "Calculando…";
    document.getElementById("masivoOrigenPrefix").textContent = "";
    document.getElementById("masivoPreview").textContent  = "";
    document.getElementById("masivoMsg").innerHTML         = "";
    masivoInfo = null;
    modalAgregarMas.style.display = "flex";

    // Cargar info del prefijo
    fetch("<?= SITE_URL ?>/api/inventario/info?idProducto=" + productoActual.id)
    .then(r => r.json())
    .then(info => {
        masivoInfo = info;
        document.getElementById("masivoPrefix").textContent = info.prefix;
        let origen = "";
        if(info.categoria){
            origen = `1ª letra de "${info.categoria}"`;
            if(info.subcategorias && info.subcategorias.length){
                origen += " + 1ª de: " + info.subcategorias.map(s => `"${s}"`).join(", ");
            }
        } else {
            document.getElementById("masivoPrefix").textContent = "SN-";
            origen = "Sin categoría asignada — usando prefijo SN-";
            masivoInfo.prefix  = "SN-";
            masivoInfo.nextNum = masivoInfo.nextNum || 1;
        }
        document.getElementById("masivoOrigenPrefix").textContent = origen;
        actualizarPreview();
    })
    .catch(() => {
        document.getElementById("masivoPrefix").textContent = "Error al cargar";
    });
};

// ── Preview de series a generar ───────────────────────────
function actualizarPreview(){
    if(!masivoInfo) return;
    const cantidad = parseInt(document.getElementById("masivoCantidad").value) || 1;
    const start    = masivoInfo.nextNum || 1;
    const prefix   = masivoInfo.prefix;
    const mostrar  = Math.min(cantidad, 5);
    let series = [];
    for(let i = 0; i < mostrar; i++){
        series.push(prefix + String(start + i).padStart(3, '0'));
    }
    let texto = "Se generarán: " + series.join(", ");
    if(cantidad > 5) texto += ` … hasta ${prefix}${String(start + cantidad - 1).padStart(3,'0')}`;
    document.getElementById("masivoPreview").textContent = texto;
}

document.getElementById("masivoCantidad").addEventListener("input", actualizarPreview);

// ── Confirmar agregar masivo ──────────────────────────────
document.getElementById("btnConfirmarMasivo").addEventListener("click", function(){
    if(!masivoInfo){ return; }
    const cantidad = parseInt(document.getElementById("masivoCantidad").value) || 0;
    if(cantidad < 1){ document.getElementById("masivoMsg").innerHTML = '<p style="color:#dc2626">Indica una cantidad válida.</p>'; return; }

    const btn = this;
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-arrow-repeat spin"></i> Agregando…';
    document.getElementById("masivoMsg").innerHTML = "";

    const fd = new FormData();
    fd.append("idProducto", productoActual.id);
    fd.append("cantidad",   cantidad);
    fd.append("estadoItem", document.getElementById("masivoEstado").value);

    fetch("<?= SITE_URL ?>/api/inventario/agregar-masivo", { method:"POST", body: fd })
    .then(r => r.json())
    .then(d => {
        if(d.status === "success"){
            modalAgregarMas.style.display = "none";
            cargarItems(productoActual.id);
            // Actualizar contador en la tabla principal
            location.reload();
        } else {
            document.getElementById("masivoMsg").innerHTML = `<p style="color:#dc2626">${d.message}</p>`;
        }
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-plus-lg"></i> Agregar ítems';
    })
    .catch(() => {
        document.getElementById("masivoMsg").innerHTML = '<p style="color:#dc2626">Error de conexión</p>';
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-plus-lg"></i> Agregar ítems';
    });
});

// ── Modal editar ítem individual ──────────────────────────
function abrirFormItem(idProd, idItem, serie, estado){
    document.getElementById("itemIdProducto").value = idProd;
    document.getElementById("itemIdItem").value     = idItem || "";
    document.getElementById("itemNumSerie").value   = serie;
    document.getElementById("itemEstado").value     = estado;
    formItemMsg.innerHTML = "";
    modalFormItem.style.display = "flex";
}

formItem.addEventListener("submit", function(e){
    e.preventDefault();
    const btn = this.querySelector("button[type='submit']");
    btn.disabled = true;

    const fd = new FormData();
    fd.append("idProducto", document.getElementById("itemIdProducto").value);
    fd.append("idItem",     document.getElementById("itemIdItem").value);
    fd.append("numeroSerie",document.getElementById("itemNumSerie").value);
    fd.append("estadoItem", document.getElementById("itemEstado").value);

    fetch("<?= SITE_URL ?>/api/inventario/guardar", { method:"POST", body: fd })
    .then(r => r.json())
    .then(d => {
        if(d.status === "success"){
            modalFormItem.style.display = "none";
            cargarItems(productoActual.id);
        } else {
            formItemMsg.innerHTML = `<p style="color:#dc2626">${d.message}</p>`;
        }
        btn.disabled = false;
    })
    .catch(() => { formItemMsg.innerHTML = '<p style="color:#dc2626">Error de conexión</p>'; btn.disabled = false; });
});
</script>

</body>
</html>
