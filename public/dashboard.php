<?php
    ob_start();
    if (!defined('ROOT_PATH')) define('ROOT_PATH', dirname(__DIR__));
    if (session_status() === PHP_SESSION_NONE) session_start();
    require_once ROOT_PATH . "/config/config.php";
    include(ROOT_PATH . "/config/conection.php");
    $con = conection();

    if(!isset($_SESSION['idUsuario'])){
        header("location:" . SITE_URL . "/");
        exit();
    }

    // Crear tabla configuracion solo una vez por sesión
    if(empty($_SESSION['config_table_ok'])){
        mysqli_query($con, "CREATE TABLE IF NOT EXISTS configuracion (
            id INT PRIMARY KEY AUTO_INCREMENT,
            clave VARCHAR(100) UNIQUE NOT NULL,
            valor TEXT NOT NULL
        )");
        mysqli_query($con, "INSERT IGNORE INTO configuracion (clave, valor) VALUES ('whatsapp', '573123048308')");
        $_SESSION['config_table_ok'] = 1;
    }

    // ── Estadísticas en UNA sola query ───────────────────────
    $statsRow = mysqli_fetch_assoc(mysqli_query($con,
        "SELECT
            (SELECT COUNT(*) FROM producto)      AS totalProductos,
            (SELECT COUNT(*) FROM usuarios)      AS totalUsuarios,
            (SELECT COUNT(*) FROM categoria)     AS totalCategorias,
            (SELECT COUNT(*) FROM subcategoria)  AS totalSubcategorias,
            (SELECT COUNT(*) FROM iteminventario WHERE estadoItem='Disponible') AS totalDisponibles,
            (SELECT COUNT(*) FROM producto p2 WHERE NOT EXISTS (
                SELECT 1 FROM imagenesproducto i WHERE i.idProducto = p2.idProducto
            )) AS prodSinImagen"
    ));
    $totalProductos     = (int)($statsRow['totalProductos']     ?? 0);
    $totalUsuarios      = (int)($statsRow['totalUsuarios']      ?? 0);
    $totalCategorias    = (int)($statsRow['totalCategorias']    ?? 0);
    $totalSubcategorias = (int)($statsRow['totalSubcategorias'] ?? 0);
    $totalDisponibles   = (int)($statsRow['totalDisponibles']   ?? 0);
    $prodSinImagen      = (int)($statsRow['prodSinImagen']      ?? 0);

    // ── Productos por categoría (top 6) ───────────────────────
    $resCatChart = mysqli_query($con,
        "SELECT c.nombreCategoria, COUNT(p.idProducto) AS total
         FROM categoria c
         INNER JOIN producto p ON p.idCategoria = c.idCategoria
         GROUP BY c.idCategoria
         ORDER BY total DESC
         LIMIT 6");
    $labelsCat = []; $dataCat = [];
    while($r = mysqli_fetch_assoc($resCatChart)){
        $labelsCat[] = $r['nombreCategoria'];
        $dataCat[]   = (int)$r['total'];
    }

    // ── Ítems por estado (Disponible / Vendido) ───────────────
    $resEstChart = mysqli_query($con,
        "SELECT estadoItem, COUNT(*) AS total FROM iteminventario GROUP BY estadoItem");
    $labelsEst = []; $dataEst = [];
    while($r = mysqli_fetch_assoc($resEstChart)){
        $labelsEst[] = $r['estadoItem'];
        $dataEst[]   = (int)$r['total'];
    }

    // ── Usuarios por rol ──────────────────────────────────────
    $resRolChart = mysqli_query($con,
        "SELECT rol, COUNT(*) AS total FROM usuarios GROUP BY rol");
    $labelsRol = []; $dataRol = [];
    while($r = mysqli_fetch_assoc($resRolChart)){
        $labelsRol[] = ucfirst($r['rol']);
        $dataRol[]   = (int)$r['total'];
    }

    // ── Últimos 5 productos ───────────────────────────────────
    $resRecientes = mysqli_query($con,
        "SELECT p.nombreProducto, p.precio,
                COALESCE(s.nombreSubcategoria,'—') AS nombreSubcat,
                COALESCE(c.nombreCategoria,'—')    AS nombreCategoria,
                SUM(CASE WHEN inv.estadoItem='Disponible' THEN 1 ELSE 0 END) AS disponibles,
                p.ubicacion
         FROM producto p
         LEFT JOIN productosubcategoria ps ON ps.idProducto = p.idProducto
         LEFT JOIN subcategoria s          ON s.idSubcategoria = ps.idSubcategoria
         LEFT JOIN categoria c             ON c.idCategoria = s.idCategoria
         LEFT JOIN iteminventario inv       ON inv.idProducto = p.idProducto
         GROUP BY p.idProducto
         ORDER BY p.idProducto DESC LIMIT 5");

    // ── WhatsApp actual ───────────────────────────────────────
    $wsRow = mysqli_fetch_assoc(mysqli_query($con, "SELECT valor FROM configuracion WHERE clave='whatsapp'"));
    $whatsappActual = $wsRow['valor'] ?? '573123048308';

    // ── Visitas ───────────────────────────────────────────────
    // Cachear existencia de tabla en sesión
    if(!isset($_SESSION['visitas_exists'])){
        $vCheck = mysqli_query($con, "SHOW TABLES LIKE 'visitas'");
        $_SESSION['visitas_exists'] = mysqli_num_rows($vCheck) > 0 ? 1 : 0;
    }
    $hayVisitas = (bool)$_SESSION['visitas_exists'];

    if ($hayVisitas) {
        // Todas las métricas en una sola query con condicionales
        $visRow = mysqli_fetch_assoc(mysqli_query($con,
            "SELECT
                SUM(fecha = CURDATE())                                           AS visHoy,
                SUM(fecha >= DATE_SUB(CURDATE(), INTERVAL 6 DAY))               AS visSemana,
                SUM(fecha >= DATE_SUB(CURDATE(), INTERVAL 29 DAY))              AS visMes,
                COUNT(DISTINCT ip_hash)                                          AS visUnicosTotal
             FROM visitas"
        ));
        $visHoy        = (int)($visRow['visHoy']        ?? 0);
        $visSemana     = (int)($visRow['visSemana']     ?? 0);
        $visMes        = (int)($visRow['visMes']        ?? 0);
        $visUnicosTotal= (int)($visRow['visUnicosTotal']?? 0);
        // Únicos hoy — requiere DISTINCT con filtro, query separada pero liviana
        $vhRow = mysqli_fetch_row(mysqli_query($con,
            "SELECT COUNT(DISTINCT ip_hash) FROM visitas WHERE fecha=CURDATE()"));
        $visHoyUnicos = (int)($vhRow[0] ?? 0);

        // Últimos 14 días para gráfica
        $resVis14 = mysqli_query($con,
            "SELECT fecha, COUNT(*) AS total, COUNT(DISTINCT ip_hash) AS unicos
             FROM visitas
             WHERE fecha >= DATE_SUB(CURDATE(), INTERVAL 13 DAY)
             GROUP BY fecha ORDER BY fecha ASC");
        $vis14Labels = []; $vis14Total = []; $vis14Unicos = [];
        // Rellenar todos los días del rango
        for ($i = 13; $i >= 0; $i--) {
            $d = date('Y-m-d', strtotime("-$i days"));
            $vis14Labels[] = date('d/m', strtotime($d));
            $vis14Total[$d]  = 0;
            $vis14Unicos[$d] = 0;
        }
        while ($rv = mysqli_fetch_assoc($resVis14)) {
            $vis14Total[$rv['fecha']]  = (int)$rv['total'];
            $vis14Unicos[$rv['fecha']] = (int)$rv['unicos'];
        }
        $vis14TotalArr  = array_values($vis14Total);
        $vis14UnicosArr = array_values($vis14Unicos);
    }

    // ── Datos del usuario logueado ────────────────────────────
    $idUsuario = $_SESSION['idUsuario'];
    $rowUser = mysqli_fetch_assoc(mysqli_query($con,
        "SELECT nombreUsuario, rol FROM usuarios WHERE idUsuario=$idUsuario"));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Compra y Listo – Dashboard</title>
    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
    <link rel="dns-prefetch" href="https://cdn.jsdelivr.net">
    <link rel="preload" href="../assets/styleAll.min.css" as="style">
    <link rel="preload" href="../assets/mobile-admin.min.css" as="style">
    <link rel="stylesheet" href="../assets/styleAll.min.css">
    <link rel="stylesheet" href="../assets/mobile-admin.min.css">
    <link rel="stylesheet" href="../assets/admin-overrides.css">
    <link rel="stylesheet" href="../assets/bootstrap-icons/bootstrap-icons.css" media="print" onload="this.media='all'">
    <noscript><link rel="stylesheet" href="../assets/bootstrap-icons/bootstrap-icons.css"></noscript>
</head>
<body>
<div class="contenedor">

    <!-- ── HEADER ─────────────────────────────────────────── -->
    <div class="head">
        <div class="imglogo">
            <img class="imagenlogo" src="../assets/imagenes/logo.png" alt="Logo">
            <div class="saludo" id="userMenu">
                <div class="user-info">
                    <div class="user-text">
                        <span class="bienvenido-texto">Bienvenido,
                            <?php echo $rowUser['rol'] === 'admin' ? 'Admin' : 'Gestor'; ?>
                        </span>
                        <span class="bienvenido-user">
                            <?php echo htmlspecialchars($rowUser['nombreUsuario']); ?>
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

    <!-- ── CUERPO ──────────────────────────────────────────── -->
    <div class="cuepo">

        <div class="ruta">
            <a href="<?= SITE_URL ?>/admin"><i class="bi bi-house-fill"></i> Panel</a>
            <span class="separator"><i class="bi bi-chevron-right"></i></span>
            <span class="actual"><i class="bi bi-speedometer2"></i> Dashboard</span>
        </div>

        <!-- TÍTULO -->
        <div class="dash-header">
            <h1>Panel de Reportes</h1>
            <p>Resumen general del sistema</p>
        </div>

        <!-- ── TARJETAS ESTADÍSTICAS ──────────────────────── -->
        <div class="dash-stats">

            <div class="stat-card stat-green">
                <div class="stat-icon"><i class="bi bi-boxes"></i></div>
                <div class="stat-info">
                    <span class="stat-num"><?php echo $totalProductos; ?></span>
                    <span class="stat-label">Productos</span>
                </div>
            </div>

            <div class="stat-card stat-blue">
                <div class="stat-icon"><i class="bi bi-people-fill"></i></div>
                <div class="stat-info">
                    <span class="stat-num"><?php echo $totalUsuarios; ?></span>
                    <span class="stat-label">Usuarios</span>
                </div>
            </div>

            <div class="stat-card stat-orange">
                <div class="stat-icon"><i class="bi bi-bookmark-fill"></i></div>
                <div class="stat-info">
                    <span class="stat-num"><?php echo $totalCategorias; ?></span>
                    <span class="stat-label">Categorías</span>
                </div>
            </div>

            <div class="stat-card stat-purple">
                <div class="stat-icon"><i class="bi bi-diagram-3-fill"></i></div>
                <div class="stat-info">
                    <span class="stat-num"><?php echo $totalSubcategorias; ?></span>
                    <span class="stat-label">Subcategorías</span>
                </div>
            </div>

            <div class="stat-card stat-purple">
                <div class="stat-icon"><i class="bi bi-archive-fill"></i></div>
                <div class="stat-info">
                    <span class="stat-num"><?php echo $totalDisponibles; ?></span>
                    <span class="stat-label">Disponibles</span>
                </div>
            </div>

            <div class="stat-card stat-red">
                <div class="stat-icon"><i class="bi bi-image-fill"></i></div>
                <div class="stat-info">
                    <span class="stat-num"><?php echo $prodSinImagen; ?></span>
                    <span class="stat-label">Sin imagen</span>
                </div>
            </div>

        </div>

        <!-- ── GRÁFICOS ───────────────────────────────────── -->
        <div class="dash-charts">

            <div class="chart-card">
                <h3><i class="bi bi-bar-chart-fill"></i> Productos por Categoría</h3>
                <canvas id="chartCategorias"></canvas>
            </div>

            <div class="chart-card">
                <h3><i class="bi bi-pie-chart-fill"></i> Inventario por Estado</h3>
                <canvas id="chartEstados"></canvas>
            </div>

            <div class="chart-card">
                <h3><i class="bi bi-person-badge-fill"></i> Usuarios por Rol</h3>
                <canvas id="chartRoles"></canvas>
            </div>

        </div>

        <!-- ── VISITAS ───────────────────────────────────── -->
        <?php if ($hayVisitas): ?>
        <div class="dash-section vis-section">
            <div class="dash-section-header">
                <h3><i class="bi bi-eye-fill"></i> Visitas al Sitio Público</h3>
                <span class="vis-badge">index.php</span>
            </div>

            <div class="vis-body">

                <div class="vis-stats">
                    <div class="vis-card vis-today">
                        <div class="vis-icon"><i class="bi bi-calendar-day-fill"></i></div>
                        <div class="vis-info">
                            <span class="vis-num"><?php echo $visHoy; ?></span>
                            <span class="vis-label">Visitas hoy</span>
                        </div>
                    </div>
                    <div class="vis-card vis-unique">
                        <div class="vis-icon"><i class="bi bi-person-check-fill"></i></div>
                        <div class="vis-info">
                            <span class="vis-num"><?php echo $visHoyUnicos; ?></span>
                            <span class="vis-label">Únicos hoy</span>
                        </div>
                    </div>
                    <div class="vis-card vis-week">
                        <div class="vis-icon"><i class="bi bi-calendar-week-fill"></i></div>
                        <div class="vis-info">
                            <span class="vis-num"><?php echo $visSemana; ?></span>
                            <span class="vis-label">Últimos 7 días</span>
                        </div>
                    </div>
                    <div class="vis-card vis-month">
                        <div class="vis-icon"><i class="bi bi-calendar-month-fill"></i></div>
                        <div class="vis-info">
                            <span class="vis-num"><?php echo $visMes; ?></span>
                            <span class="vis-label">Últimos 30 días</span>
                        </div>
                    </div>
                    <div class="vis-card vis-total">
                        <div class="vis-icon"><i class="bi bi-people-fill"></i></div>
                        <div class="vis-info">
                            <span class="vis-num"><?php echo $visUnicosTotal; ?></span>
                            <span class="vis-label">IPs únicas históricas</span>
                        </div>
                    </div>
                </div>

                <div class="vis-chart-wrap">
                    <canvas id="chartVisitas"></canvas>
                </div>

            </div>
        </div>
        <?php endif; ?>

        <!-- ── ÚLTIMOS PRODUCTOS ──────────────────────────── -->
        <div class="dash-section">
            <div class="dash-section-header">
                <h3><i class="bi bi-clock-history"></i> Últimos Productos Agregados</h3>
                <a href="<?= SITE_URL ?>/admin/productos" class="dash-link">Ver todos</a>
            </div>
            <div class="dash-table-wrap">
                <table class="dash-table">
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Precio</th>
                            <th>Categoría</th>
                            <th>Subcategoría</th>
                            <th>Disponibles</th>
                            <th>Ubicación</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($prod = mysqli_fetch_assoc($resRecientes)): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($prod['nombreProducto']); ?></td>
                            <td>$<?php echo number_format($prod['precio']); ?></td>
                            <td><span class="badge badge-green"><?php echo htmlspecialchars($prod['nombreCategoria']); ?></span></td>
                            <td><span class="badge badge-blue"><?php echo htmlspecialchars($prod['nombreSubcat']); ?></span></td>
                            <td>
                                <?php $d = (int)$prod['disponibles']; ?>
                                <span class="badge <?php echo $d > 0 ? 'badge-green' : 'badge-red'; ?>">
                                    <?php echo $d > 0 ? "$d disponible".($d>1?'s':'') : 'Agotado'; ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($prod['ubicacion']); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ── CONFIGURACIÓN (solo admin) ───────────────── -->
        <?php if ($rowUser['rol'] === 'admin'): ?>
        <div class="dash-section">
            <div class="dash-section-header">
                <h3><i class="bi bi-gear-fill"></i> Configuración</h3>
            </div>

            <div class="config-grid">

                <div class="config-card">
                    <div class="config-card-icon"><i class="bi bi-whatsapp"></i></div>
                    <div class="config-card-body">
                        <label>Número de WhatsApp</label>
                        <p class="config-hint">Número al que llegan los mensajes de contacto. Incluye código de país sin el +</p>
                        <div class="config-input-row">
                            <input type="text" id="inputWhatsapp"
                                value="<?php echo htmlspecialchars($whatsappActual); ?>"
                                placeholder="Ej: 573123048308">
                            <button onclick="guardarConfig('whatsapp', document.getElementById('inputWhatsapp').value)" class="config-btn">
                                <i class="bi bi-check-lg"></i> Guardar
                            </button>
                        </div>
                        <div id="msgWhatsapp" class="config-msg"></div>
                    </div>
                </div>

            </div>
        </div>
        <?php endif; ?>

    </div><!-- fin .cuepo -->

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

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js" defer></script>
<script>
// ── Charts — se ejecutan después que Chart.js cargó ─────────
window.addEventListener('load', function(){
const colorsPalette = ['#2E8B57','#3b82f6','#f97316','#8b5cf6','#ef4444','#14b8a6','#f59e0b'];

new Chart(document.getElementById('chartCategorias'), {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($labelsCat); ?>,
        datasets: [{
            label: 'Productos',
            data: <?php echo json_encode($dataCat); ?>,
            backgroundColor: colorsPalette,
            borderRadius: 6,
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
    }
});

new Chart(document.getElementById('chartEstados'), {
    type: 'doughnut',
    data: {
        labels: <?php echo json_encode($labelsEst); ?>,
        datasets: [{
            data: <?php echo json_encode($dataEst); ?>,
            backgroundColor: colorsPalette,
            borderWidth: 2,
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { position: 'bottom' } }
    }
});

new Chart(document.getElementById('chartRoles'), {
    type: 'doughnut',
    data: {
        labels: <?php echo json_encode($labelsRol); ?>,
        datasets: [{
            data: <?php echo json_encode($dataRol); ?>,
            backgroundColor: ['#2E8B57', '#3b82f6', '#f97316'],
            borderWidth: 2,
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { position: 'bottom' } }
    }
});

// ── Gráfica de visitas ───────────────────────────────────────
<?php if ($hayVisitas): ?>
(function(){
    const labels  = <?php echo json_encode($vis14Labels); ?>;
    const totales = <?php echo json_encode($vis14TotalArr); ?>;
    const unicos  = <?php echo json_encode($vis14UnicosArr); ?>;

    const canvasVis = document.getElementById('chartVisitas');
    // Escalar canvas al pixel ratio real del dispositivo para evitar borrosidad
    const dpr = window.devicePixelRatio || 1;
    const rect = canvasVis.parentElement.getBoundingClientRect();
    canvasVis.width  = rect.width  * dpr;
    canvasVis.height = rect.height * dpr;
    canvasVis.style.width  = rect.width  + 'px';
    canvasVis.style.height = rect.height + 'px';

    new Chart(canvasVis, {
        type: 'line',
        data: {
            labels,
            datasets: [
                {
                    label: 'Visitas totales',
                    data: totales,
                    borderColor: '#2E8B57',
                    backgroundColor: 'rgba(46,139,87,0.12)',
                    fill: true,
                    tension: 0.4,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    borderWidth: 2,
                },
                {
                    label: 'Visitantes únicos',
                    data: unicos,
                    borderColor: '#3b82f6',
                    backgroundColor: 'rgba(59,130,246,0.10)',
                    fill: true,
                    tension: 0.4,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    borderWidth: 2,
                }
            ]
        },
        options: {
            responsive: false,
            maintainAspectRatio: false,
            devicePixelRatio: dpr,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { position: 'top' },
                tooltip: { callbacks: { title: t => 'Día ' + t[0].label } }
            },
            scales: {
                y: { beginAtZero: true, ticks: { stepSize: 1 } }
            }
        }
    });
})();
<?php endif; ?>

}); // fin window.load

// ── Guardar configuración ────────────────────────────────────
function guardarConfig(clave, valor) {
    const msgEl = document.getElementById('msg' + clave.charAt(0).toUpperCase() + clave.slice(1));
    const fd = new FormData();
    fd.append('clave', clave);
    fd.append('valor', valor);

    fetch('<?= SITE_URL ?>/api/configuracion/guardar', {
        method: 'POST',
        body: fd
    })
    .then(r => r.json())
    .then(data => {
        if (data.status === 'success') {
            msgEl.innerHTML = '<span class="msg-ok"><i class="bi bi-check-circle-fill"></i> ' + data.message + '</span>';
        } else {
            msgEl.innerHTML = '<span class="msg-err"><i class="bi bi-x-circle-fill"></i> ' + (data.error || 'Error') + '</span>';
        }
        setTimeout(() => msgEl.innerHTML = '', 3000);
    });
}
</script>

</body>
</html>
