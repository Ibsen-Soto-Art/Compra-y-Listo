<?php
ob_start();
if (!defined('ROOT_PATH')) define('ROOT_PATH', dirname(__DIR__));
if (!defined('SITE_URL'))  require_once ROOT_PATH . "/config/config.php";
include(ROOT_PATH . "/config/conection.php");
$con = conection();

/* WhatsApp dinámico */
$wsRow = mysqli_fetch_assoc(mysqli_query($con,
    "SELECT valor FROM configuracion WHERE clave='whatsapp' LIMIT 1"));
$whatsappNum = $wsRow['valor'] ?? '573123048308';

/* Búsqueda */
$busqueda = isset($_GET['q']) ? mysqli_real_escape_string($con, trim($_GET['q'])) : '';

/* ── Registro de visita ─────────────────────────────────────
   Solo crea la tabla la primera vez (guarda flag en sesión).
──────────────────────────────────────────────────────────── */
if(session_status() === PHP_SESSION_NONE) session_start();
if(empty($_SESSION['visitas_table_ok'])){
    mysqli_query($con, "CREATE TABLE IF NOT EXISTS visitas (
        id          INT AUTO_INCREMENT PRIMARY KEY,
        fecha       DATE        NOT NULL,
        hora        TINYINT     NOT NULL,
        ip_hash     VARCHAR(64) NOT NULL,
        INDEX idx_fecha   (fecha),
        INDEX idx_iphash  (ip_hash)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $_SESSION['visitas_table_ok'] = 1;
}

$ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
$bots = ['bot','crawl','spider','slurp','mediapartners','facebookexternalhit','curl','wget','python','java'];
$esBot = false;
foreach($bots as $b){ if(stripos($ua, $b) !== false){ $esBot = true; break; } }

if(!$esBot){
    $ip      = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $ipHash  = hash('sha256', $ip . date('Y-m-d'));   // hash diario: mismo visitante = mismo hash ese día
    $fecha   = date('Y-m-d');
    $hora    = (int)date('G');
    mysqli_query($con,
        "INSERT INTO visitas (fecha, hora, ip_hash) VALUES ('$fecha', $hora, '$ipHash')");
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="Author" content="Ibsen Alexis Soto Artunduaga">
    <meta name="keywords" content="compras, ventas, nuevo, usado, Colombia">
    <meta name="Description" content="Compra y Listo — encuentra productos nuevos y usados al mejor precio.">
    <title>Compra y Listo</title>
    <!-- Preconnect CDN fonts -->
    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
    <!-- Preload CSS crítico -->
    <link rel="preload" href="../assets/styleAll.min.css" as="style">
    <link rel="preload" href="../assets/mobile.min.css" as="style">
    <link rel="stylesheet" href="../assets/styleAll.min.css">
    <link rel="stylesheet" href="../assets/mobile.min.css">
    <!-- Bootstrap Icons: carga diferida sin bloquear render -->
    <link rel="stylesheet" href="../assets/bootstrap-icons/bootstrap-icons.css" media="print" onload="this.media='all'">
    <noscript><link rel="stylesheet" href="../assets/bootstrap-icons/bootstrap-icons.css"></noscript>
    <style>
    /* ══ BARRA DE FILTROS AVANZADOS ══════════════════════════ */
    .filtros-avanzados-bar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        padding: 10px 20px;
        border-bottom: 1px solid #f1f5f9;
        flex-wrap: wrap;
        background: #fff;
        position: sticky;
        top: 0;
        z-index: 50;
        box-shadow: 0 2px 8px rgba(0,0,0,.05);
    }
    .filtros-bar-izq { display:flex; align-items:center; gap:8px; flex-wrap:wrap; flex:1; }
    .filtros-bar-der { display:flex; align-items:center; gap:8px; flex-shrink:0; }

    .btn-filtros-abrir {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 7px 14px;
        background: #f8fafc;
        border: 1.5px solid #e2e8f0;
        border-radius: 8px;
        font-size: 13px;
        font-weight: 600;
        color: #374151;
        cursor: pointer;
        transition: all .15s;
        position: relative;
        white-space: nowrap;
    }
    .btn-filtros-abrir:hover,
    .btn-filtros-abrir.activo { background:#2E8B57; color:#fff; border-color:#2E8B57; }
    .filtros-badge-activos {
        position: absolute;
        top: -6px; right: -6px;
        background: #ef4444;
        color: #fff;
        font-size: 10px;
        font-weight: 700;
        width: 17px; height: 17px;
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        border: 2px solid #fff;
    }

    .filtros-activos-chips {
        display: flex;
        gap: 6px;
        flex-wrap: wrap;
        align-items: center;
    }
    .chip-activo {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        background: #e8f5e9;
        color: #1a5c38;
        border: 1px solid #a5d6b0;
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        cursor: pointer;
        white-space: nowrap;
    }
    .chip-activo:hover { background: #a5d6b0; }
    .chip-activo i { font-size: 11px; }

    .filtro-orden-select {
        padding: 7px 10px;
        border: 1.5px solid #e2e8f0;
        border-radius: 8px;
        font-size: 13px;
        color: #374151;
        background: #f8fafc;
        outline: none;
        cursor: pointer;
    }
    .filtro-orden-select:focus { border-color: #2E8B57; }

    /* ══ PANEL LATERAL ════════════════════════════════════════ */
    .filtros-overlay {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(0,0,0,.35);
        z-index: 500;
    }
    .filtros-overlay.visible { display: block; }

    .filtros-panel {
        position: fixed;
        top: 0; left: 0; bottom: 0;
        width: 300px;
        max-width: 88vw;
        background: #fff;
        z-index: 501;
        display: flex;
        flex-direction: column;
        overflow: hidden;
        box-shadow: 4px 0 24px rgba(0,0,0,.12);
        transform: translateX(-110%);
        transition: transform .28s cubic-bezier(.4,0,.2,1);
    }
    .filtros-panel.abierto { transform: translateX(0); }

    .filtros-panel-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 18px 20px;
        border-bottom: 1px solid #f1f5f9;
        font-size: 15px;
        font-weight: 700;
        color: #1e293b;
        flex-shrink: 0;
    }
    .filtros-panel-cerrar {
        background: none; border: none;
        font-size: 18px; color: #94a3b8;
        cursor: pointer; padding: 4px;
        border-radius: 6px;
    }
    .filtros-panel-cerrar:hover { background: #f1f5f9; color: #1e293b; }

    .filtros-panel-body {
        flex: 1;
        overflow-y: auto;
        padding: 16px 20px;
        display: flex;
        flex-direction: column;
        gap: 20px;
    }
    .filtros-panel-body::-webkit-scrollbar { width: 4px; }
    .filtros-panel-body::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 4px; }

    .filtro-seccion { display: flex; flex-direction: column; gap: 10px; }
    .filtro-seccion-titulo {
        margin: 0;
        font-size: 12px;
        font-weight: 700;
        color: #94a3b8;
        text-transform: uppercase;
        letter-spacing: .7px;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .filtro-seccion-titulo i { color: #2E8B57; }

    /* Precio */
    .filtro-precio-inputs {
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .filtro-precio-campo {
        flex: 1;
        display: flex;
        flex-direction: column;
        gap: 4px;
    }
    .filtro-precio-campo label { font-size: 11px; color: #94a3b8; font-weight: 600; }
    .filtro-precio-campo input {
        padding: 8px 10px;
        border: 1.5px solid #e2e8f0;
        border-radius: 8px;
        font-size: 13px;
        outline: none;
        width: 100%;
        box-sizing: border-box;
    }
    .filtro-precio-campo input:focus { border-color: #2E8B57; }
    .filtro-precio-sep { color: #94a3b8; font-weight: 700; flex-shrink: 0; }

    /* Toggle oferta */
    .filtro-toggle-wrap {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 10px 12px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        font-size: 13px;
        color: #374151;
        font-weight: 500;
        cursor: pointer;
    }

    /* Ubicaciones */
    .filtro-ubic-search-wrap {
        position: relative;
    }
    .filtro-ubic-search-icon {
        position: absolute;
        left: 10px;
        top: 50%;
        transform: translateY(-50%);
        color: #94a3b8;
        font-size: 13px;
        pointer-events: none;
    }
    .filtro-ubic-search {
        width: 100%;
        box-sizing: border-box;
        padding: 8px 10px 8px 32px;
        border: 1.5px solid #e2e8f0;
        border-radius: 8px;
        font-size: 13px;
        outline: none;
        color: #374151;
        background: #f8fafc;
        transition: border-color .15s;
    }
    .filtro-ubic-search:focus { border-color: #2E8B57; background: #fff; }
    .filtro-ubic-empty { font-size: 12px; color: #94a3b8; text-align: center; margin: 4px 0 0; }

    .filtro-ubicaciones {
        display: flex;
        flex-direction: column;
        gap: 2px;
        max-height: 180px;
        overflow-y: auto;
    }
    .filtro-ubicaciones::-webkit-scrollbar { width: 3px; }
    .filtro-ubicaciones::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 4px; }

    .filtro-ubic-item {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 7px 10px;
        border-radius: 8px;
        cursor: pointer;
        font-size: 13px;
        color: #374151;
        transition: background .12s;
    }
    .filtro-ubic-item:hover { background: #f1f5f9; }
    .filtro-ubic-item.checked { background: #e8f5e9; }
    .filtro-ubic-item input { accent-color: #2E8B57; width: 15px; height: 15px; cursor: pointer; flex-shrink: 0; }
    .filtro-ubic-count {
        margin-left: auto;
        font-size: 11px;
        color: #fff;
        font-weight: 700;
        background: #2E8B57;
        padding: 1px 7px;
        border-radius: 20px;
        min-width: 20px;
        text-align: center;
    }

    /* Categorías */
    .filtro-categorias-lista { display: flex; flex-direction: column; gap: 4px; }
    .filtro-cat-item {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 6px 10px;
        border-radius: 8px;
        cursor: pointer;
        font-size: 13px;
        color: #374151;
        transition: background .12s;
    }
    .filtro-cat-item:hover { background: #f1f5f9; }
    .filtro-cat-item input { accent-color: #2E8B57; width: 15px; height: 15px; cursor: pointer; flex-shrink: 0; }
    .filtro-cat-count { margin-left: auto; font-size: 11px; color: #94a3b8; font-weight: 600; }

    /* Footer panel */
    .filtros-panel-footer {
        display: flex;
        gap: 10px;
        padding: 14px 20px;
        padding-bottom: calc(14px + env(safe-area-inset-bottom, 70px));
        border-top: 2px solid #e2e8f0;
        flex-shrink: 0;
        background: #fff;
        box-shadow: 0 -4px 16px rgba(0,0,0,.08);
    }
    .filtros-limpiar {
        flex: 1;
        padding: 13px 10px;
        border: 2px solid #cbd5e1;
        border-radius: 12px;
        background: #f1f5f9;
        color: #334155;
        font-size: 14px;
        font-weight: 700;
        cursor: pointer;
        display: flex; align-items: center; justify-content: center; gap: 6px;
        transition: all .15s;
        min-height: 48px;
    }
    .filtros-limpiar:hover, .filtros-limpiar:active { background: #fee2e2; color: #dc2626; border-color: #fca5a5; }
    .filtros-aplicar {
        flex: 2;
        padding: 13px 10px;
        border: none;
        border-radius: 12px;
        background: #2E8B57;
        color: #fff;
        font-size: 15px;
        font-weight: 700;
        cursor: pointer;
        display: flex; align-items: center; justify-content: center; gap: 6px;
        transition: background .15s;
        min-height: 48px;
        box-shadow: 0 4px 12px rgba(46,139,87,.28);
        letter-spacing: .2px;
    }
    .filtros-aplicar:hover, .filtros-aplicar:active { background: #246d44; }

    /* Sin resultados */
    .filtros-sin-resultados {
        display: none;
        flex-direction: column;
        align-items: center;
        gap: 10px;
        padding: 60px 20px;
        color: #94a3b8;
        text-align: center;
    }
    .filtros-sin-resultados i { font-size: 40px; }
    .filtros-sin-resultados p { font-size: 15px; margin: 0; }
    .filtros-sin-resultados a { font-size: 13px; color: #2E8B57; text-decoration: none; }

    @media (max-width: 480px) {
        .filtros-avanzados-bar { padding: 8px 12px; }
        .filtro-orden-select { font-size: 12px; padding: 6px 8px; }
        .filtros-bar-der label { display: none; }
    }

    /* ══ MODAL DETALLE PRODUCTO — INFO ═══════════════════════ */
    .det-info-pro {
        display: flex;
        flex-direction: column;
        gap: 14px;
    }

    .det-fila-meta {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
    }

    .det-badge-estado {
        display: inline-flex;
        align-items: center;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 700;
        letter-spacing: .3px;
    }
    .det-badge-disponible {
        background: #dcfce7;
        color: #15803d;
    }
    .det-badge-agotado {
        background: #fee2e2;
        color: #dc2626;
    }

    .det-ubicacion {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        font-size: 13px;
        color: #64748b;
        font-weight: 500;
    }
    .det-ubicacion i { color: #2E8B57; }

    .det-nombre {
        margin: 0;
        font-size: 26px;
        font-weight: 900;
        color: #0f172a;
        line-height: 1.25;
        letter-spacing: -.3px;
    }

    .det-precio-wrap {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
    }
    .det-precio-original {
        font-size: 15px;
        color: #94a3b8;
        text-decoration: line-through;
        font-weight: 500;
    }
    .det-precio-final {
        font-size: 26px;
        font-weight: 900;
        color: #0f172a;
        letter-spacing: -.5px;
    }
    .det-precio-badge {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 800;
        background: linear-gradient(135deg, #f97316, #dc2626);
        color: #fff;
        letter-spacing: .3px;
        text-transform: uppercase;
        box-shadow: 0 2px 8px rgba(239,68,68,.3);
    }

    .det-sep {
        height: 1px;
        background: #f1f5f9;
        margin: 0;
    }

    .det-pills {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }
    .det-pill {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
    }
    .det-pill-cat {
        background: #e8f5e9;
        color: #1a5c38;
        border: 1px solid #a5d6b0;
    }
    .det-pill-cat i { color: #2E8B57; }
    .det-pill-sub {
        background: #f1faf4;
        color: #246d44;
        border: 1px solid #b7dfc4;
    }
    .det-pill-sub i { color: #2E8B57; }
    .det-pill-stock {
        background: #e8f5e9;
        color: #1a5c38;
        border: 1px solid #a5d6b0;
    }
    .det-pill-stock i { color: #2E8B57; }

    .det-pill-clickable {
        cursor: pointer;
        transition: filter .15s, transform .15s;
        background: inherit;
    }
    .det-pill-clickable:hover  { filter: brightness(.93); transform: translateY(-1px); }
    .det-pill-clickable:active { transform: scale(.96); }
    .det-pill-cat.det-pill-clickable  { border: 1px solid #a5d6b0; }
    .det-pill-sub.det-pill-clickable  { border: 1px solid #b7dfc4; }

    .det-desc-bloque {
        background: #f8fafc;
        border-radius: 12px;
        padding: 14px 16px;
        border: 1px solid #e2e8f0;
    }
    .det-desc-label {
        margin: 0 0 8px 0;
        font-size: 11px;
        font-weight: 700;
        color: #94a3b8;
        text-transform: uppercase;
        letter-spacing: .6px;
        display: flex;
        align-items: center;
        gap: 5px;
    }
    .det-desc {
        margin: 0;
        font-size: 14px;
        color: #475569;
        line-height: 1.65;
        white-space: pre-line;
    }

    .det-acciones {
        display: flex;
        flex-direction: column;
        gap: 8px;
        margin-top: 4px;
    }
    .pub-wa-detalle {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 13px 20px;
        background: #22c55e;
        color: #fff;
        border-radius: 12px;
        font-size: 14px;
        font-weight: 700;
        text-decoration: none;
        transition: background .15s, transform .1s;
        box-shadow: 0 4px 12px rgba(34,197,94,.3);
    }
    .pub-wa-detalle:hover { background: #16a34a; transform: translateY(-1px); }
    .det-btn-oferta {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 11px 20px;
        background: linear-gradient(135deg, #f97316, #dc2626);
        color: #fff;
        border-radius: 12px;
        font-size: 14px;
        font-weight: 700;
        text-decoration: none;
        transition: opacity .15s, transform .1s;
        box-shadow: 0 4px 12px rgba(239,68,68,.3);
    }
    .det-btn-oferta:hover { opacity: .88; transform: translateY(-1px); }
    </style>
</head>
<body>
<div class="contenedor">

    <!-- ══ HEADER PÚBLICO ══════════════════════════════════════ -->
    <div class="head pub-head">

        <!-- Logo + tagline -->
        <div class="imglogo">
            <a href="<?= SITE_URL ?>/" class="imglogo">
                <img class="imagenlogo"
                     src="../assets/imagenes/logo.png"
                     alt="Logo Compra y Listo">
            </a>

            <div class="pub-bienvenida">
                <span class="pub-saludo">Compra y Listo</span>
                <span class="pub-slogan">
                    <i class="bi bi-geo-alt-fill"></i> Colombia · Compra y vende fácil
                </span>
            </div>
        </div>

        <!-- Acciones del header -->
        <div class="pub-head-acciones">
            <a href="https://wa.me/<?php echo htmlspecialchars($whatsappNum); ?>"
               target="_blank" class="pub-head-wa">
                <i class="bi bi-whatsapp"></i>
                <span>Contacto</span>
            </a>
            <button class="btn-login-header" id="btnAbrirLogin">
                <i class="bi bi-person-fill"></i>
                <span>Iniciar Sesión</span>
            </button>
        </div>

    </div>

    <!-- ══ MODAL LOGIN ══════════════════════════════════════════ -->
    <div class="modal" id="modalLogin">
        <div class="modal-login-contenido">

            <!-- Franja decorativa superior -->
            <div class="modal-login-banner">
                <div class="modal-login-banner-icon">
                    <i class="bi bi-shield-lock-fill"></i>
                </div>
                <button class="modal-login-cerrar" id="cerrarLogin">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>

            <!-- Cuerpo del modal -->
            <div class="modal-login-body">

                <!-- ══ PASO 0: Formulario de login ══ -->
                <div id="resetStep0">
                    <h2 class="modal-login-titulo">Acceso de Gestión</h2>
                    <p class="modal-login-sub">Solo para administradores y gestores</p>

                    <form id="formLogin" class="modal-login-form">

                        <div class="login-group">
                            <label class="login-label">Correo electrónico</label>
                            <div class="login-field">
                                <i class="bi bi-envelope-fill"></i>
                                <input type="email" name="correo"
                                       placeholder="tucorreo@ejemplo.com" required autocomplete="email">
                            </div>
                        </div>

                        <div class="login-group">
                            <label class="login-label">Contraseña</label>
                            <div class="login-field">
                                <i class="bi bi-lock-fill"></i>
                                <input type="password" name="contraseña" id="inputPass"
                                       placeholder="••••••••" required autocomplete="current-password">
                                <button type="button" class="login-toggle-pass" id="togglePass"
                                        title="Mostrar contraseña">
                                    <i class="bi bi-eye-fill"></i>
                                </button>
                            </div>
                        </div>

                        <div id="loginMensaje"></div>

                        <button type="submit" class="btn-login-submit" id="btnLoginSubmit">
                            <i class="bi bi-box-arrow-in-right"></i> Ingresar al panel
                        </button>

                    </form>

                    <button class="reset-link" id="btnOlvidePass">
                        <i class="bi bi-key-fill"></i> ¿Olvidaste tu contraseña?
                    </button>

                    <p class="modal-login-nota">
                        <i class="bi bi-info-circle"></i>
                        ¿Eres cliente? Explora los productos sin necesidad de iniciar sesión.
                    </p>
                </div>

                <!-- ══ PASO 1: Ingresa tu correo para recibir código ══ -->
                <div id="resetStep1" class="reset-step" style="display:none;">
                    <div class="reset-step-icon">
                        <i class="bi bi-envelope-arrow-up-fill"></i>
                    </div>
                    <h2 class="modal-login-titulo">Restablecer contraseña</h2>
                    <p class="modal-login-sub">Ingresa tu correo y te enviaremos un código de 6 dígitos</p>

                    <form id="formEnviarCodigo" class="modal-login-form">
                        <div class="login-group">
                            <label class="login-label">Correo registrado</label>
                            <div class="login-field">
                                <i class="bi bi-envelope-fill"></i>
                                <input type="email" name="correo" id="resetCorreo"
                                       placeholder="tucorreo@ejemplo.com" required autocomplete="email">
                            </div>
                        </div>

                        <div id="resetMsg1"></div>

                        <button type="submit" class="btn-login-submit" id="btnEnviarCodigo">
                            <i class="bi bi-send-fill"></i> Enviar código
                        </button>
                    </form>

                    <button class="reset-volver" id="btnVolverLogin">
                        <i class="bi bi-arrow-left"></i> Volver al inicio de sesión
                    </button>
                </div>

                <!-- ══ PASO 2: Ingresar código de 6 dígitos ══ -->
                <div id="resetStep2" class="reset-step" style="display:none;">
                    <div class="reset-step-icon reset-step-icon-amber">
                        <i class="bi bi-shield-check-fill"></i>
                    </div>
                    <h2 class="modal-login-titulo">Verificar código</h2>
                    <p class="modal-login-sub">Ingresa el código de 6 dígitos enviado a <strong id="resetCorreoMostrado"></strong></p>

                    <form id="formVerificarCodigo" class="modal-login-form">
                        <div class="codigo-grid">
                            <input type="text" class="codigo-digit" maxlength="1" inputmode="numeric" pattern="[0-9]">
                            <input type="text" class="codigo-digit" maxlength="1" inputmode="numeric" pattern="[0-9]">
                            <input type="text" class="codigo-digit" maxlength="1" inputmode="numeric" pattern="[0-9]">
                            <input type="text" class="codigo-digit" maxlength="1" inputmode="numeric" pattern="[0-9]">
                            <input type="text" class="codigo-digit" maxlength="1" inputmode="numeric" pattern="[0-9]">
                            <input type="text" class="codigo-digit" maxlength="1" inputmode="numeric" pattern="[0-9]">
                        </div>

                        <p class="reset-timer">Expira en <strong id="resetTimer">10:00</strong></p>

                        <div id="resetMsg2"></div>

                        <button type="submit" class="btn-login-submit" id="btnVerificarCodigo" disabled>
                            <i class="bi bi-check-circle-fill"></i> Verificar código
                        </button>
                    </form>

                    <button class="reset-volver" id="btnReenviarCodigo">
                        <i class="bi bi-arrow-repeat"></i> Reenviar código
                    </button>
                </div>

                <!-- ══ PASO 3: Nueva contraseña ══ -->
                <div id="resetStep3" class="reset-step" style="display:none;">
                    <div class="reset-step-icon reset-step-icon-green">
                        <i class="bi bi-lock-fill"></i>
                    </div>
                    <h2 class="modal-login-titulo">Nueva contraseña</h2>
                    <p class="modal-login-sub">Crea una contraseña segura para tu cuenta</p>

                    <form id="formCambiarPassword" class="modal-login-form">

                        <div class="login-group">
                            <label class="login-label">Nueva contraseña</label>
                            <div class="login-field">
                                <i class="bi bi-lock-fill"></i>
                                <input type="password" name="nueva" id="inputNueva"
                                       placeholder="Mínimo 6 caracteres" required minlength="6">
                                <button type="button" class="login-toggle-pass" id="toggleNueva">
                                    <i class="bi bi-eye-fill"></i>
                                </button>
                            </div>
                        </div>

                        <div class="login-group">
                            <label class="login-label">Confirmar contraseña</label>
                            <div class="login-field">
                                <i class="bi bi-lock-fill"></i>
                                <input type="password" name="confirmar" id="inputConfirmar"
                                       placeholder="Repite la contraseña" required>
                                <button type="button" class="login-toggle-pass" id="toggleConfirmar">
                                    <i class="bi bi-eye-fill"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Barra de fortaleza -->
                        <div class="pass-strength-wrap">
                            <div class="pass-strength-bar" id="passStrengthBar"></div>
                        </div>
                        <p class="pass-strength-label" id="passStrengthLabel"></p>

                        <div id="resetMsg3"></div>

                        <button type="submit" class="btn-login-submit" id="btnCambiarPass">
                            <i class="bi bi-floppy-fill"></i> Guardar contraseña
                        </button>
                    </form>
                </div>

            </div>

        </div>
    </div>

    <!-- ══ HERO ════════════════════════════════════════════════ -->
    <?php
        $totalProd = mysqli_fetch_row(mysqli_query($con,"SELECT COUNT(DISTINCT p.idProducto) FROM producto p INNER JOIN iteminventario inv ON inv.idProducto=p.idProducto WHERE inv.estadoItem='Disponible'"))[0];
        $totalCat  = mysqli_fetch_row(mysqli_query($con,"SELECT COUNT(*) FROM categoria WHERE estadoCategoria='Activo'"))[0];
        $totalVend = mysqli_fetch_row(mysqli_query($con,"SELECT COUNT(*) FROM usuarios WHERE rol='gestor' OR rol='admin'"))[0];
    ?>
    <div class="pub-hero">
        <!-- Blobs decorativos -->
        <div class="pub-hero-blob pub-hero-blob1"></div>
        <div class="pub-hero-blob pub-hero-blob2"></div>

        <div class="pub-hero-inner">

            <div class="pub-hero-texto">
                <div class="pub-hero-badge">
                    <i class="bi bi-lightning-fill"></i> Compras al instante
                </div>
                <h1>Todo lo que buscas,<br><span>en un solo lugar</span></h1>
                <p>Productos nuevos y usados al mejor precio en Colombia</p>
            </div>

            <!-- Barra de búsqueda -->
            <form class="pub-search-bar" method="GET" action="<?= SITE_URL ?>/">
                <div class="pub-search-inner">
                    <i class="bi bi-search"></i>
                    <input type="search" name="q"
                           placeholder="¿Qué estás buscando hoy?"
                           value="<?php echo htmlspecialchars($busqueda); ?>"
                           autocomplete="off">
                </div>
                <button type="submit">
                    <i class="bi bi-search"></i>
                    <span class="pub-search-btn-text">Buscar</span>
                </button>
            </form>

            <?php if(!empty($busqueda)): ?>
            <div class="pub-search-resultado">
                Resultados para: <strong>"<?php echo htmlspecialchars($busqueda); ?>"</strong>
                <a href="<?= SITE_URL ?>/"><i class="bi bi-x-circle-fill"></i> Limpiar</a>
            </div>
            <?php endif; ?>

            <!-- Stats rápidos -->
            <div class="pub-hero-stats">
                <div class="pub-stat">
                    <strong><?php echo $totalProd; ?>+</strong>
                    <span>Productos</span>
                </div>
                <div class="pub-stat-sep"></div>
                <div class="pub-stat">
                    <strong><?php echo $totalCat; ?></strong>
                    <span>Categorías</span>
                </div>
                <div class="pub-stat-sep"></div>
                <div class="pub-stat">
                    <strong>100%</strong>
                    <span>Seguro</span>
                </div>
            </div>

        </div>
    </div>

    <!-- ══ CUERPO ═══════════════════════════════════════════════ -->
    <div class="cuepo">

        <!-- ── FILTROS DE CATEGORÍA ─────────────────────────── -->
        <?php
        $sqlCat = "SELECT c.idCategoria, c.nombreCategoria, COUNT(DISTINCT p.idProducto) AS total
                   FROM categoria c
                   INNER JOIN producto p ON (
                       p.idCategoria = c.idCategoria
                       OR EXISTS (
                           SELECT 1 FROM productosubcategoria ps
                           INNER JOIN subcategoria s ON s.idSubcategoria = ps.idSubcategoria
                           WHERE ps.idProducto = p.idProducto AND s.idCategoria = c.idCategoria
                       )
                   )
                   WHERE c.estadoCategoria = 'Activo'
                     AND EXISTS (SELECT 1 FROM iteminventario WHERE idProducto=p.idProducto AND estadoItem='Disponible')
                   GROUP BY c.idCategoria
                   HAVING total > 0
                   ORDER BY c.nombreCategoria";
        $queryCat = mysqli_query($con, $sqlCat);
        $categoriasFiltro = [];
        while($cat = mysqli_fetch_assoc($queryCat)) $categoriasFiltro[] = $cat;

        $qTotal = mysqli_query($con,"SELECT COUNT(DISTINCT p.idProducto) FROM producto p INNER JOIN iteminventario inv ON inv.idProducto=p.idProducto WHERE inv.estadoItem='Disponible'");
        $totalProductos = 0;
        if($qTotal){
            $rowTotal = mysqli_fetch_row($qTotal);
            $totalProductos = $rowTotal[0] ?? 0;
        }
        ?>

        <div class="filtro-barra">
            <div class="filtro-scroll" id="filtroScroll">

                <button class="filtro-chip filtro-chip-all activo" id="chipTodos">
                    <i class="bi bi-grid-fill"></i>
                    Todos
                    <span class="chip-count"><?php echo $totalProductos; ?></span>
                </button>

                <?php foreach($categoriasFiltro as $cat): ?>
                <div class="filtro-chip-wrap" data-id="<?php echo $cat['idCategoria']; ?>">

                    <button class="filtro-chip categoriaFiltro"
                            data-categoria="<?php echo $cat['idCategoria']; ?>">
                        <?php echo htmlspecialchars($cat['nombreCategoria']); ?>
                        <span class="chip-count"><?php echo $cat['total']; ?></span>
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
                        $qProd = mysqli_query($con, "SELECT DISTINCT p.nombreProducto FROM producto p WHERE (p.idCategoria=$idCat OR EXISTS (SELECT 1 FROM productosubcategoria ps INNER JOIN subcategoria s ON s.idSubcategoria=ps.idSubcategoria WHERE ps.idProducto=p.idProducto AND s.idCategoria=$idCat)) AND EXISTS (SELECT 1 FROM iteminventario WHERE idProducto=p.idProducto AND estadoItem='Disponible') ORDER BY p.nombreProducto");
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

        <!-- ── BARRA DE FILTROS AVANZADOS ──────────────────────── -->
        <div class="filtros-avanzados-bar" id="filtrosBar">

            <div class="filtros-bar-izq">
                <button class="btn-filtros-abrir" id="btnAbrirFiltros">
                    <i class="bi bi-sliders"></i> Filtros
                    <span class="filtros-badge-activos" id="filtrosBadge" style="display:none;"></span>
                </button>

                <!-- Chips de filtros activos -->
                <div class="filtros-activos-chips" id="filtrosActivosChips"></div>
            </div>

            <div class="filtros-bar-der">
                <label style="font-size:12.5px;color:#64748b;white-space:nowrap;">Ordenar:</label>
                <select id="selectOrden" class="filtro-orden-select">
                    <option value="relevancia">Relevancia</option>
                    <option value="precio-asc">Precio: menor a mayor</option>
                    <option value="precio-desc">Precio: mayor a menor</option>
                    <option value="nombre-asc">Nombre: A → Z</option>
                    <option value="nombre-desc">Nombre: Z → A</option>
                    <option value="oferta">Ofertas primero</option>
                </select>
            </div>

        </div>

        <!-- ── PANEL LATERAL DE FILTROS ─────────────────────────── -->
        <div class="filtros-overlay" id="filtrosOverlay"></div>
        <div class="filtros-panel" id="filtrosPanel">

            <div class="filtros-panel-header">
                <span><i class="bi bi-sliders"></i> Filtros</span>
                <button class="filtros-panel-cerrar" id="btnCerrarFiltros">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>

            <div class="filtros-panel-body">

                <!-- Rango de precio -->
                <div class="filtro-seccion">
                    <p class="filtro-seccion-titulo"><i class="bi bi-cash-coin"></i> Rango de precio</p>
                    <div class="filtro-precio-inputs">
                        <div class="filtro-precio-campo">
                            <label>Mínimo</label>
                            <input type="number" id="filtroMin" placeholder="$0" min="0">
                        </div>
                        <span class="filtro-precio-sep">—</span>
                        <div class="filtro-precio-campo">
                            <label>Máximo</label>
                            <input type="number" id="filtroMax" placeholder="Sin límite" min="0">
                        </div>
                    </div>
                </div>

                <!-- Solo ofertas -->
                <div class="filtro-seccion">
                    <p class="filtro-seccion-titulo"><i class="bi bi-tag-fill"></i> Promociones</p>
                    <label class="filtro-toggle-wrap">
                        <span>Solo productos en oferta</span>
                        <label class="toggle-switch">
                            <input type="checkbox" id="filtroSoloOferta">
                            <span class="toggle-slider"></span>
                        </label>
                    </label>
                </div>

                <!-- Ubicación -->
                <div class="filtro-seccion">
                    <p class="filtro-seccion-titulo"><i class="bi bi-geo-alt-fill"></i> Ubicación</p>
                    <div class="filtro-ubic-search-wrap">
                        <i class="bi bi-search filtro-ubic-search-icon"></i>
                        <input type="text" id="filtroUbicSearch" class="filtro-ubic-search" placeholder="Buscar ciudad...">
                    </div>
                    <div class="filtro-ubicaciones" id="filtroUbicaciones">
                        <!-- Se llena con JS -->
                    </div>
                    <p class="filtro-ubic-empty" id="filtroUbicEmpty" style="display:none;">Sin resultados</p>
                </div>

                <!-- Categoría (dentro del panel) -->
                <div class="filtro-seccion">
                    <p class="filtro-seccion-titulo"><i class="bi bi-bookmark-fill"></i> Categoría</p>
                    <div class="filtro-categorias-lista" id="filtroCategoriasLista">
                        <?php foreach($categoriasFiltro as $cat): ?>
                        <label class="filtro-cat-item">
                            <input type="checkbox" class="filtro-cat-cb" value="<?php echo $cat['idCategoria']; ?>">
                            <span><?php echo htmlspecialchars($cat['nombreCategoria']); ?></span>
                            <span class="filtro-cat-count"><?php echo $cat['total']; ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>

            </div>

            <div class="filtros-panel-footer">
                <button class="filtros-limpiar" id="btnLimpiarFiltros">
                    <i class="bi bi-x-circle"></i> Limpiar todo
                </button>
                <button class="filtros-aplicar" id="btnAplicarFiltros">
                    <i class="bi bi-check-lg"></i> Aplicar
                </button>
            </div>

        </div>

        <!-- ── GRID DE PRODUCTOS ─────────────────────────────── -->
        <?php
        // Detectar si ya existe la tabla municipio (post-migración)
        $tblMun = mysqli_fetch_assoc(mysqli_query($con,
            "SELECT COUNT(*) AS c FROM INFORMATION_SCHEMA.TABLES
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'municipio'"));
        $conMunicipio = (int)$tblMun['c'] > 0;

        if($conMunicipio){
            $sql = "SELECT
                        p.idProducto,
                        p.nombreProducto,
                        p.precio,
                        p.enOferta,
                        p.descuento,
                        COALESCE(c.idCategoria, pc.idCategoria, 0) AS idCat,
                        COALESCE(c.nombreCategoria, pc.nombreCategoria, 'Sin categoría') AS nombreCategoria,
                        i.rutaImagen,
                        i.esPrincipal,
                        (SELECT COUNT(*) FROM iteminventario WHERE idProducto=p.idProducto AND estadoItem='Disponible') AS disponibles,
                        (SELECT GROUP_CONCAT(DISTINCT ps2.idSubcategoria) FROM productosubcategoria ps2 WHERE ps2.idProducto = p.idProducto) AS subcatIds,
                        CASE
                            WHEN mun.idMunicipio IS NOT NULL THEN CONCAT(mun.nombre, ', ', dep.nombre)
                            ELSE COALESCE(p.ubicacion, '')
                        END AS ubicacion,
                        COALESCE(mun.idMunicipio, 0) AS idMun,
                        COALESCE(dep.nombre, '') AS nombreDepto
                    FROM producto p
                    LEFT JOIN imagenesproducto i ON p.idProducto = i.idProducto
                    LEFT JOIN productosubcategoria ps ON ps.idProducto = p.idProducto
                    LEFT JOIN subcategoria s ON s.idSubcategoria = ps.idSubcategoria
                    LEFT JOIN categoria c ON c.idCategoria = s.idCategoria
                    LEFT JOIN categoria pc ON pc.idCategoria = p.idCategoria
                    LEFT JOIN municipio   mun ON mun.idMunicipio   = p.idMunicipio
                    LEFT JOIN departamento dep ON dep.idDepartamento = mun.idDepartamento
                    WHERE EXISTS (SELECT 1 FROM iteminventario WHERE idProducto=p.idProducto AND estadoItem='Disponible')
                      AND (c.estadoCategoria = 'Activo' OR c.idCategoria IS NULL OR pc.estadoCategoria = 'Activo')";
        } else {
            $sql = "SELECT
                        p.idProducto,
                        p.nombreProducto,
                        p.precio,
                        p.enOferta,
                        p.descuento,
                        COALESCE(c.idCategoria, pc.idCategoria, 0) AS idCat,
                        COALESCE(c.nombreCategoria, pc.nombreCategoria, 'Sin categoría') AS nombreCategoria,
                        i.rutaImagen,
                        i.esPrincipal,
                        (SELECT COUNT(*) FROM iteminventario WHERE idProducto=p.idProducto AND estadoItem='Disponible') AS disponibles,
                        (SELECT GROUP_CONCAT(DISTINCT ps2.idSubcategoria) FROM productosubcategoria ps2 WHERE ps2.idProducto = p.idProducto) AS subcatIds,
                        COALESCE(p.ubicacion, '') AS ubicacion,
                        0 AS idMun,
                        '' AS nombreDepto
                    FROM producto p
                    LEFT JOIN imagenesproducto i ON p.idProducto = i.idProducto
                    LEFT JOIN productosubcategoria ps ON ps.idProducto = p.idProducto
                    LEFT JOIN subcategoria s ON s.idSubcategoria = ps.idSubcategoria
                    LEFT JOIN categoria c ON c.idCategoria = s.idCategoria
                    LEFT JOIN categoria pc ON pc.idCategoria = p.idCategoria
                    WHERE EXISTS (SELECT 1 FROM iteminventario WHERE idProducto=p.idProducto AND estadoItem='Disponible')
                      AND (c.estadoCategoria = 'Activo' OR c.idCategoria IS NULL OR pc.estadoCategoria = 'Activo')";
        }

        if(!empty($busqueda)){
            $sql .= " AND (p.nombreProducto LIKE '%$busqueda%'
                         OR c.nombreCategoria LIKE '%$busqueda%')";
        }
        $sql .= " ORDER BY p.idProducto DESC, i.orden ASC";

        $query = mysqli_query($con, $sql);
        $productosPorCategoria = [];

        while($row = mysqli_fetch_assoc($query)){
            $catId = $row['idCat'] ?? 0;
            $id    = $row['idProducto'];
            if(!isset($productosPorCategoria[$catId][$id])){
                $productosPorCategoria[$catId][$id] = [
                    "nombre"      => $row['nombreProducto'],
                    "precio"      => $row['precio'],
                    "ubicacion"   => $row['ubicacion'],
                    "idMun"       => (int)($row['idMun'] ?? 0),
                    "nombreDepto" => $row['nombreDepto'] ?? "",
                    "disponibles" => (int)$row['disponibles'],
                    "enOferta"    => (int)$row['enOferta'],
                    "descuento"   => (float)$row['descuento'],
                    "subcatIds"   => $row['subcatIds'] ?? "",
                    "imagenes"    => []
                ];
            }
            if($row['rutaImagen'] && !in_array($row['rutaImagen'], $productosPorCategoria[$catId][$id]['imagenes'])){
                $productosPorCategoria[$catId][$id]['imagenes'][] = $row['rutaImagen'];
            }
        }
        ?>

        <?php if(empty($productosPorCategoria)): ?>
        <div class="pub-empty">
            <i class="bi bi-search"></i>
            <p>No se encontraron productos<?php echo !empty($busqueda) ? ' para "'.htmlspecialchars($busqueda).'"' : ''; ?>.</p>
            <?php if(!empty($busqueda)): ?>
            <a href="<?= SITE_URL ?>/">Ver todos los productos</a>
            <?php endif; ?>
        </div>
        <?php else: ?>

        <div class="ProductGeneral">
            <?php
            $sqlCat2 = "SELECT c.idCategoria, c.nombreCategoria, COUNT(DISTINCT p.idProducto) AS total
                        FROM categoria c
                        INNER JOIN producto p ON (
                            p.idCategoria = c.idCategoria
                            OR EXISTS (
                                SELECT 1 FROM productosubcategoria ps
                                INNER JOIN subcategoria s ON s.idSubcategoria = ps.idSubcategoria
                                WHERE ps.idProducto = p.idProducto AND s.idCategoria = c.idCategoria
                            )
                        )
                        WHERE EXISTS (SELECT 1 FROM iteminventario WHERE idProducto=p.idProducto AND estadoItem='Disponible')
                        GROUP BY c.idCategoria
                        HAVING total > 0";
            if(!empty($busqueda)){
                $sqlCat2 = "SELECT c.idCategoria, c.nombreCategoria, COUNT(DISTINCT p.idProducto) AS total
                            FROM categoria c
                            INNER JOIN producto p ON (
                                p.idCategoria = c.idCategoria
                                OR EXISTS (
                                    SELECT 1 FROM productosubcategoria ps
                                    INNER JOIN subcategoria s ON s.idSubcategoria = ps.idSubcategoria
                                    WHERE ps.idProducto = p.idProducto AND s.idCategoria = c.idCategoria
                                )
                            )
                            WHERE EXISTS (SELECT 1 FROM iteminventario WHERE idProducto=p.idProducto AND estadoItem='Disponible')
                            AND (p.nombreProducto LIKE '%$busqueda%'
                               OR c.nombreCategoria LIKE '%$busqueda%')
                            GROUP BY c.idCategoria";
            }
            $queryCat2 = mysqli_query($con, $sqlCat2);

            while($cat = mysqli_fetch_assoc($queryCat2)):
                $idCat = $cat['idCategoria'];
                if(!isset($productosPorCategoria[$idCat])) continue;
            ?>

            <div class="nameCatArriba" data-cat-id="<?php echo $idCat; ?>">
                <h2>
                    <i class="bi bi-caret-down-fill flechaToggle"></i>
                    <?php echo htmlspecialchars($cat['nombreCategoria']); ?>
                    <span>(<?php echo count($productosPorCategoria[$idCat]); ?>)</span>
                </h2>
            </div>

            <div class="CartaProducto activo" data-cat-id="<?php echo $idCat; ?>">
                <?php foreach($productosPorCategoria[$idCat] as $id => $prod):
                    $imagenPrincipal = $prod['imagenes'][0] ?? null;
                    $totalImg = count($prod['imagenes']);
                ?>
                <?php
                    $precioMostrar = ($prod['enOferta'] && $prod['descuento'] > 0)
                        ? round($prod['precio'] * (1 - $prod['descuento'] / 100))
                        : $prod['precio'];
                    // Usar municipio normalizado o fallback al texto libre
                    $ubicNorm = $prod['idMun']
                        ? strtolower($prod['ubicacion'])
                        : preg_replace('/\s+/', ' ', trim(preg_replace('/[-_]+/', ' ', strtolower($prod['ubicacion']))));
                    $idMunCard = $prod['idMun'];
                ?>
                <div class="card-producto card-pub"
                     data-categoria="<?php echo $idCat; ?>"
                     data-subcategorias="<?php echo htmlspecialchars($prod['subcatIds'] ?? ''); ?>"
                     data-nombre="<?php echo strtolower(htmlspecialchars($prod['nombre'])); ?>"
                     data-precio="<?php echo $precioMostrar; ?>"
                     data-precio-original="<?php echo $prod['precio']; ?>"
                     data-oferta="<?php echo $prod['enOferta'] ? '1' : '0'; ?>"
                     data-ubicacion="<?php echo htmlspecialchars($ubicNorm); ?>"
                     data-id-mun="<?php echo $idMunCard; ?>"
                     id="producto-<?php echo $id; ?>">

                    <!-- Imagen / slider -->
                    <div class="slider-producto">
                        <?php if($imagenPrincipal): ?>
                            <?php foreach($prod['imagenes'] as $idx => $img): ?>
                            <img src="<?php echo $img; ?>"
                                 class="slide-producto <?php echo $idx===0?'active':''; ?>"
                                 loading="lazy">
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="card-sin-imagen"><i class="bi bi-image"></i></div>
                        <?php endif; ?>

                        <span class="card-estado-badge <?php echo $prod['disponibles'] > 0 ? 'badge-disponible' : 'badge-agotado'; ?>">
                            <?php echo $prod['disponibles'] > 0 ? 'Disponible' : 'Agotado'; ?>
                        </span>

                        <?php if($prod['enOferta'] && $prod['descuento'] > 0): ?>
                        <span class="card-oferta-badge-pub">
                            <i class="bi bi-tag-fill"></i> -<?php echo intval($prod['descuento']); ?>% OFERTA
                        </span>
                        <?php endif; ?>

                        <?php if($totalImg > 1): ?>
                        <span class="card-img-count">
                            <i class="bi bi-images"></i> <?php echo $totalImg; ?>
                        </span>
                        <?php endif; ?>
                    </div>

                    <!-- Info -->
                    <div class="card-info">
                        <h4 class="card-nombre"><?php echo htmlspecialchars($prod['nombre']); ?></h4>
                        <?php if($prod['enOferta'] && $prod['descuento'] > 0):
                            $precioFinal = $prod['precio'] * (1 - $prod['descuento'] / 100);
                        ?>
                        <p class="card-precio card-precio-oferta">
                            <span class="precio-original">$<?php echo number_format($prod['precio']); ?></span>
                            <span class="precio-descuento">$<?php echo number_format($precioFinal); ?></span>
                        </p>
                        <?php else: ?>
                        <p class="card-precio">$<?php echo number_format($prod['precio']); ?></p>
                        <?php endif; ?>
                        <div class="card-meta">
                            <span class="card-ubicacion">
                                <i class="bi bi-geo-alt-fill"></i>
                                <?php echo htmlspecialchars($prod['ubicacion']); ?>
                            </span>
                        </div>

                        <!-- Botón WhatsApp -->
                        <a href="https://wa.me/<?php echo htmlspecialchars($whatsappNum); ?>?text=<?php
                            echo urlencode('Hola, me interesa el producto: '.$prod['nombre']); ?>"
                           target="_blank"
                           class="card-wa-btn"
                           onclick="event.stopPropagation()">
                            <i class="bi bi-whatsapp"></i> Contactar
                        </a>
                    </div>

                </div>
                <?php endforeach; ?>
            </div>

            <?php endwhile; ?>
        </div>

        <?php endif; ?>

        <!-- ── MODAL DETALLE PRODUCTO ─────────────────────── -->
        <div class="modal" id="modalVistaProducto">
            <div class="modal-vista-contenido pub-modal-vista">

                <span class="cerrar" onclick="cerrarVista()">
                    <i class="bi bi-x-lg"></i>
                </span>

                <div class="vista-container">

                    <!-- Miniaturas -->
                    <div class="miniaturas" id="miniaturas"></div>

                    <!-- Imagen principal -->
                    <div class="imagen-grande" id="imagenGrande">
                        <img id="imagenPrincipalVista" alt="Imagen producto" draggable="false">
                        <!-- Badge oferta sobre imagen -->
                        <span id="detOfertaBadgeImg" style="display:none;position:absolute;top:12px;left:12px;z-index:5;background:linear-gradient(135deg,#f97316,#dc2626);color:#fff;font-size:11px;font-weight:800;padding:5px 12px;border-radius:20px;display:none;align-items:center;gap:5px;letter-spacing:.4px;text-transform:uppercase;box-shadow:0 3px 10px rgba(239,68,68,.4);"></span>
                        <div class="zoom-hint" id="zoomHint">
                            <i class="bi bi-zoom-in"></i> Clic para ampliar · Rueda para zoom
                        </div>
                        <button class="zoom-reset-btn" id="zoomResetBtn" onclick="window.dispatchEvent(new Event('resetZoom'))">
                            <i class="bi bi-arrows-fullscreen"></i> Restablecer
                        </button>
                    </div>

                    <!-- Info del producto -->
                    <div class="info-producto det-info-pro">

                        <!-- Nombre -->
                        <h2 class="det-nombre" id="tituloProducto"></h2>

                        <!-- Pills: categoría + subcategoría + stock -->
                        <div class="det-pills" id="detPills">
                            <span class="det-pill det-pill-cat det-pill-clickable" id="detPillCat" style="display:none;" title="Ver todos los productos de esta categoría">
                                <i class="bi bi-bookmark-fill"></i>
                                <span id="detCatNombre"></span>
                            </span>
                            <div id="detSubsWrap" style="display:contents;"></div>
                            <span class="det-pill det-pill-stock" id="detPillStock">
                                <i class="bi bi-box-seam-fill"></i>
                                <span id="detStockTxt"></span>
                            </span>
                        </div>

                        <!-- Separador -->
                        <div class="det-sep"></div>

                        <!-- Precio -->
                        <div class="det-precio-wrap" id="detPrecioWrap">
                            <span class="det-precio-original" id="detPrecioOriginal" style="display:none;"></span>
                            <span class="det-precio-final" id="detPrecioFinal"></span>
                            <span class="det-precio-badge" id="detPrecioBadge" style="display:none;"></span>
                        </div>

                        <!-- Fila meta: estado + ubicación -->
                        <div class="det-fila-meta">
                            <span class="det-badge-estado" id="estadoProducto"></span>
                            <span class="det-ubicacion">
                                <i class="bi bi-geo-alt-fill"></i>
                                <span id="ubicacionProducto"></span>
                            </span>
                        </div>

                        <!-- Descripción -->
                        <div class="det-desc-bloque" id="det-desc-bloque">
                            <p class="det-desc-label"><i class="bi bi-card-text"></i> Descripción</p>
                            <p class="det-desc" id="descripcionProducto"></p>
                        </div>

                        <!-- Botón WhatsApp -->
                        <div class="det-acciones">
                            <a id="btnWhatsapp" target="_blank" class="btn-whatsapp pub-wa-detalle">
                                <i class="bi bi-whatsapp"></i> Contactar por WhatsApp
                            </a>
                        </div>

                    </div>
                </div>

            </div>
        </div>

    </div><!-- /.cuepo -->

    <!-- ══ FOOTER ══════════════════════════════════════════════ -->
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
                    <a href="https://www.facebook.com/share/1DCnyAL7zk/?mibextid=wwXIfr"
                       target="_blank" class="fb" title="Facebook">
                        <i class="bi bi-facebook"></i>
                    </a>
                    <a href="https://www.tiktok.com/@compraylisto04?_r=1&_t=ZS-95LjiKiZoNG"
                       target="_blank" class="tt" title="TikTok">
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

    <!-- ══ BOTÓN FLOTANTE WHATSAPP ═══════════════════════════ -->
    <a href="https://wa.me/<?php echo htmlspecialchars($whatsappNum); ?>?text=<?php
        echo urlencode('Hola, me interesa conocer más sobre sus productos'); ?>"
       target="_blank"
       class="wa-flotante"
       title="Escríbenos por WhatsApp">
        <i class="bi bi-whatsapp"></i>
        <span class="wa-flotante-texto">¿Necesitas ayuda?</span>
    </a>

    <!-- ══ BOTTOM NAVIGATION BAR (solo móvil) ══════════════════════════ -->
    <nav class="mob-nav" id="mobNav" aria-label="Navegación principal">

        <button class="mob-nav-item activo" id="mobNavInicio" aria-label="Inicio">
            <i class="bi bi-house-fill"></i>
            <span>Inicio</span>
        </button>

        <button class="mob-nav-item" id="mobNavCategorias" aria-label="Categorías">
            <i class="bi bi-grid-fill"></i>
            <span>Categorías</span>
        </button>

        <button class="mob-nav-item mob-nav-center" id="mobNavMascota" aria-label="Inicio">
            <img src="../assets/imagenes/Mascota.png" alt="Mascota" class="mob-nav-mascota">
        </button>

        <button class="mob-nav-item" id="mobNavBuscar" aria-label="Buscar">
            <i class="bi bi-search"></i>
            <span>Buscar</span>
        </button>

        <button class="mob-nav-item" id="mobNavLogin" aria-label="Iniciar sesión">
            <i class="bi bi-person-fill"></i>
            <span>Acceso</span>
        </button>

    </nav>

    <!-- ══ SCROLL TO TOP (solo móvil) ══════════════════════════════════ -->
    <button class="mob-scroll-top" id="mobScrollTop" aria-label="Volver arriba">
        <i class="bi bi-chevron-up"></i>
    </button>

    <!-- ══ TOAST DE NOTIFICACIÓN (solo móvil) ═══════════════════════════ -->
    <div class="mob-toast" id="mobToast" role="status" aria-live="polite">
        <i class="bi bi-check-circle-fill"></i>
        <span id="mobToastMsg">Mensaje</span>
    </div>

</div><!-- /.contenedor -->


<!-- ══════ SCRIPTS ═══════════════════════════════════════════ -->

<!-- Login modal + Reset contraseña -->
<script>
(function(){
    const modalLogin  = document.getElementById("modalLogin");

    // ── Paso activo ────────────────────────────────────────
    let pasoActual  = 0;
    let timerInterval = null; // Mover aquí para evitar TDZ error

    // ── Abrir / cerrar modal ───────────────────────────────
    window.abrirModalLogin = function(){
        irPaso(0);
        modalLogin.style.display = "flex";
        requestAnimationFrame(() => {
            const c = modalLogin.querySelector(".modal-login-contenido");
            if(c) c.classList.add("visible");
        });
    };
    function abrirModalLogin(){ window.abrirModalLogin(); }
    function cerrarModalLogin(){
        const c = modalLogin.querySelector(".modal-login-contenido");
        if(c && window.matchMedia("(max-width:768px)").matches){
            c.classList.remove("visible");
            setTimeout(() => { modalLogin.style.display = "none"; }, 340);
        } else {
            modalLogin.style.display = "none";
        }
    }

    document.getElementById("btnAbrirLogin").addEventListener("click", abrirModalLogin);
    document.getElementById("cerrarLogin").addEventListener("click", () => {
        // En pasos 2 y 3 no se puede cerrar con la X hasta confirmar
        if(pasoActual >= 2) return;
        cerrarModalLogin();
    });
    window.addEventListener("click", e => {
        // Bloquear cierre por clic fuera en pasos 2 y 3
        if(pasoActual >= 2) return;
        if(e.target === modalLogin) cerrarModalLogin();
    });
    // Bloquear cierre con Escape en pasos 2 y 3
    window.addEventListener("keydown", e => {
        if(e.key === "Escape" && pasoActual >= 2) e.stopImmediatePropagation();
    }, true);

    // ── Toggle contraseña login ────────────────────────────
    togglePass("togglePass",    "inputPass");
    togglePass("toggleNueva",   "inputNueva");
    togglePass("toggleConfirmar","inputConfirmar");

    function togglePass(btnId, inputId){
        const btn = document.getElementById(btnId);
        if(!btn) return;
        btn.addEventListener("click", function(){
            const inp  = document.getElementById(inputId);
            const icon = this.querySelector("i");
            if(inp.type === "password"){
                inp.type = "text";
                icon.className = "bi bi-eye-slash-fill";
            } else {
                inp.type = "password";
                icon.className = "bi bi-eye-fill";
            }
        });
    }

    // ── Navegación entre pasos ─────────────────────────────
    function irPaso(n){
        pasoActual = n;
        [0,1,2,3].forEach(i => {
            const el = document.getElementById("resetStep"+i);
            if(el) el.style.display = (i === n) ? "block" : "none";
        });
        // En pasos 2 y 3 la X se vuelve gris/deshabilitada visualmente
        const xBtn = document.getElementById("cerrarLogin");
        if(xBtn){
            xBtn.style.opacity  = n >= 2 ? "0.3" : "1";
            xBtn.style.cursor   = n >= 2 ? "not-allowed" : "pointer";
            xBtn.title          = n >= 2 ? "Completa el proceso para cerrar" : "";
        }
        if(n === 0) limpiarReset();
    }

    function limpiarReset(){
        ["resetMsg1","resetMsg2","resetMsg3"].forEach(id => {
            const el = document.getElementById(id);
            if(el) el.innerHTML = "";
        });
        document.querySelectorAll(".codigo-digit").forEach(d => d.value = "");
        if(timerInterval) clearInterval(timerInterval);
    }

    document.getElementById("btnOlvidePass").addEventListener("click", () => irPaso(1));
    document.getElementById("btnVolverLogin").addEventListener("click", () => irPaso(0));
    document.getElementById("btnReenviarCodigo").addEventListener("click", () => irPaso(1));

    // ── FORMULARIO LOGIN ───────────────────────────────────
    document.getElementById("formLogin").addEventListener("submit", function(e){
        e.preventDefault();
        const btn = document.getElementById("btnLoginSubmit");
        const msg = document.getElementById("loginMensaje");
        btn.disabled = true;
        btn.innerHTML = '<i class="bi bi-arrow-repeat spin"></i> Ingresando...';
        msg.innerHTML = "";

        fetch("<?= SITE_URL ?>/auth/loginAjax.php", { method:"POST", body: new FormData(this) })
        .then(r => r.json())
        .then(data => {
            if(data.status === "success"){
                window.location.href = data.redirect;
            } else {
                msg.innerHTML = `<div class="login-error"><i class="bi bi-exclamation-circle-fill"></i> ${data.message}</div>`;
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-box-arrow-in-right"></i> Ingresar al panel';
            }
        })
        .catch(() => {
            msg.innerHTML = '<div class="login-error">Error de conexión.</div>';
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-box-arrow-in-right"></i> Ingresar al panel';
        });
    });

    // ══════════════════════════════════════════════════════
    // PASO 1 — Enviar código al correo
    // ══════════════════════════════════════════════════════
    document.getElementById("formEnviarCodigo").addEventListener("submit", function(e){
        e.preventDefault();
        const btn = document.getElementById("btnEnviarCodigo");
        const msg = document.getElementById("resetMsg1");
        btn.disabled = true;
        btn.innerHTML = '<i class="bi bi-arrow-repeat spin"></i> Enviando...';
        msg.innerHTML = "";

        fetch("<?= SITE_URL ?>/auth/enviarCodigoReset.php", { method:"POST", body: new FormData(this) })
        .then(r => r.json())
        .then(data => {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-send-fill"></i> Enviar código';
            if(data.status === "success"){
                const correo = document.getElementById("resetCorreo").value;
                document.getElementById("resetCorreoMostrado").textContent = correo;
                irPaso(2);
                iniciarTimer(600);
            } else {
                msg.innerHTML = `<div class="login-error"><i class="bi bi-exclamation-circle-fill"></i> ${data.message}</div>`;
            }
        })
        .catch(() => {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-send-fill"></i> Enviar código';
            msg.innerHTML = '<div class="login-error">Error de conexión.</div>';
        });
    });

    // ══════════════════════════════════════════════════════
    // PASO 2 — Inputs de 6 dígitos + verificación
    // ══════════════════════════════════════════════════════
    const digits = document.querySelectorAll(".codigo-digit");
    const btnVerif = document.getElementById("btnVerificarCodigo");

    digits.forEach((input, i) => {
        input.addEventListener("input", function(){
            this.value = this.value.replace(/\D/g, "").slice(0,1);
            if(this.value && i < digits.length - 1) digits[i+1].focus();
            btnVerif.disabled = ![...digits].every(d => d.value.length === 1);
        });
        input.addEventListener("keydown", function(e){
            if(e.key === "Backspace" && !this.value && i > 0) digits[i-1].focus();
        });
        input.addEventListener("paste", function(e){
            const pasted = (e.clipboardData || window.clipboardData).getData("text").replace(/\D/g,"");
            if(pasted.length === 6){
                digits.forEach((d, j) => { d.value = pasted[j] || ""; });
                btnVerif.disabled = false;
                digits[5].focus();
            }
            e.preventDefault();
        });
    });

    document.getElementById("formVerificarCodigo").addEventListener("submit", function(e){
        e.preventDefault();
        const codigo = [...digits].map(d => d.value).join("");
        const msg    = document.getElementById("resetMsg2");
        btnVerif.disabled = true;
        btnVerif.innerHTML = '<i class="bi bi-arrow-repeat spin"></i> Verificando...';
        msg.innerHTML = "";

        const fd = new FormData();
        fd.append("codigo", codigo);

        fetch("<?= SITE_URL ?>/auth/verificarCodigoReset.php", { method:"POST", body: fd })
        .then(r => r.json())
        .then(data => {
            btnVerif.disabled = false;
            btnVerif.innerHTML = '<i class="bi bi-check-circle-fill"></i> Verificar código';
            if(data.status === "success"){
                if(timerInterval) clearInterval(timerInterval);
                irPaso(3);
            } else if(data.status === "expired"){
                msg.innerHTML = `<div class="login-error"><i class="bi bi-clock-fill"></i> ${data.message}</div>`;
                setTimeout(() => irPaso(1), 2200);
            } else {
                msg.innerHTML = `<div class="login-error"><i class="bi bi-x-circle-fill"></i> ${data.message}</div>`;
                digits.forEach(d => { d.value = ""; d.classList.add("codigo-digit-error"); });
                setTimeout(() => digits.forEach(d => d.classList.remove("codigo-digit-error")), 600);
                digits[0].focus();
            }
        })
        .catch(() => {
            btnVerif.disabled = false;
            btnVerif.innerHTML = '<i class="bi bi-check-circle-fill"></i> Verificar código';
            msg.innerHTML = '<div class="login-error">Error de conexión.</div>';
        });
    });

    // ── Temporizador cuenta regresiva ──────────────────────
    function iniciarTimer(segundos){
        if(timerInterval) clearInterval(timerInterval);
        const lbl = document.getElementById("resetTimer");
        let restante = segundos;
        function actualizar(){
            const m = String(Math.floor(restante/60)).padStart(2,"0");
            const s = String(restante % 60).padStart(2,"0");
            if(lbl) lbl.textContent = `${m}:${s}`;
            if(restante <= 0){
                clearInterval(timerInterval);
                if(lbl) lbl.parentElement.innerHTML = '<span style="color:#ef4444;">Código expirado</span>';
            }
            restante--;
        }
        actualizar();
        timerInterval = setInterval(actualizar, 1000);
    }

    // ══════════════════════════════════════════════════════
    // PASO 3 — Guardar nueva contraseña
    // ══════════════════════════════════════════════════════
    document.getElementById("inputNueva").addEventListener("input", function(){
        const bar   = document.getElementById("passStrengthBar");
        const label = document.getElementById("passStrengthLabel");
        const v = this.value;
        let score = 0;
        if(v.length >= 6)  score++;
        if(v.length >= 10) score++;
        if(/[A-Z]/.test(v)) score++;
        if(/[0-9]/.test(v)) score++;
        if(/[^A-Za-z0-9]/.test(v)) score++;

        const niveles = [
            { w:"20%", color:"#ef4444", txt:"Muy débil" },
            { w:"40%", color:"#f97316", txt:"Débil" },
            { w:"60%", color:"#eab308", txt:"Regular" },
            { w:"80%", color:"#22c55e", txt:"Fuerte" },
            { w:"100%",color:"#16a34a", txt:"Muy fuerte" },
        ];
        const n = niveles[Math.max(0, score-1)] || niveles[0];
        bar.style.width       = v.length ? n.w : "0";
        bar.style.background  = n.color;
        label.textContent     = v.length ? n.txt : "";
        label.style.color     = n.color;
    });

    document.getElementById("formCambiarPassword").addEventListener("submit", function(e){
        e.preventDefault();
        const btn = document.getElementById("btnCambiarPass");
        const msg = document.getElementById("resetMsg3");
        btn.disabled = true;
        btn.innerHTML = '<i class="bi bi-arrow-repeat spin"></i> Guardando...';
        msg.innerHTML = "";

        fetch("<?= SITE_URL ?>/auth/cambiarPasswordReset.php", { method:"POST", body: new FormData(this) })
        .then(r => r.json())
        .then(data => {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-floppy-fill"></i> Guardar contraseña';
            if(data.status === "success"){
                msg.innerHTML = `<div class="login-ok"><i class="bi bi-check-circle-fill"></i> ${data.message}</div>`;
                setTimeout(() => { cerrarModalLogin(); irPaso(0); }, 1800);
            } else {
                msg.innerHTML = `<div class="login-error"><i class="bi bi-exclamation-circle-fill"></i> ${data.message}</div>`;
            }
        })
        .catch(() => {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-floppy-fill"></i> Guardar contraseña';
            msg.innerHTML = '<div class="login-error">Error de conexión.</div>';
        });
    });
})();
</script>

<!-- Slider de imágenes al hover -->
<script>
if(!window.matchMedia("(hover: none)").matches){
    document.querySelectorAll(".card-pub").forEach(card => {
        const slides = card.querySelectorAll(".slide-producto");
        if(slides.length <= 1) return;
        let idx = 0, timer;

        card.addEventListener("mouseenter", () => {
            timer = setInterval(() => {
                slides[idx].classList.remove("active");
                idx = (idx + 1) % slides.length;
                slides[idx].classList.add("active");
            }, 900);
        });
        card.addEventListener("mouseleave", () => {
            clearInterval(timer);
            slides.forEach(s => s.classList.remove("active"));
            idx = 0;
            slides[0].classList.add("active");
        });
    });
}
</script>

<!-- Filtros de categoría -->
<script>
document.addEventListener("DOMContentLoaded", function(){
    const chipTodos = document.getElementById("chipTodos");
    if(!chipTodos) return;

    function getSecciones(){
        return document.querySelectorAll(".nameCatArriba, .CartaProducto");
    }
    function setActivo(el){
        document.querySelectorAll(".filtro-chip").forEach(c => c.classList.remove("activo"));
        if(el) el.classList.add("activo");
    }
    function cerrarDropdowns(){
        document.querySelectorAll(".filtro-chip-wrap.abierto").forEach(w => w.classList.remove("abierto"));
    }

    // Mostrar todo
    function resetFiltro(){
        getSecciones().forEach(el => el.style.display = "");
        document.querySelectorAll(".card-pub").forEach(c => c.style.display = "");
        setActivo(chipTodos);
        cerrarDropdowns();
    }

    chipTodos.addEventListener("click", resetFiltro);

    // Filtrar por categoría — toggle: clic en activa = volver a todo
    document.querySelectorAll(".categoriaFiltro").forEach(btn => {
        btn.addEventListener("click", function(e){
            e.stopPropagation();
            if(this.classList.contains("activo")){ resetFiltro(); return; }

            const idCat = this.dataset.categoria;
            getSecciones().forEach(el => {
                el.style.display = el.dataset.catId === idCat ? "" : "none";
            });
            setActivo(this);
            cerrarDropdowns();
        });
    });

    // Expandir dropdown
    document.querySelectorAll(".filtro-chip-expand").forEach(btn => {
        btn.addEventListener("click", function(e){
            e.stopPropagation();
            const wrap = this.closest(".filtro-chip-wrap");
            const estaAbierto = wrap.classList.contains("abierto");
            cerrarDropdowns();
            if(!estaAbierto){
                wrap.classList.add("abierto");
                const dropdown = wrap.querySelector(".filtro-dropdown");
                const rect = wrap.getBoundingClientRect();
                dropdown.style.top  = (rect.bottom + 8) + "px";
                dropdown.style.left = rect.left + "px";
            }
        });
    });

    // Filtrar por producto individual del dropdown
    document.querySelectorAll(".nameProduct").forEach(item => {
        item.addEventListener("click", function(e){
            e.stopPropagation();
            const nombre = this.dataset.nombre;
            const wrap   = this.closest(".filtro-chip-wrap");
            const idCat  = wrap ? wrap.dataset.id : null;

            getSecciones().forEach(el => {
                el.style.display = (!idCat || el.dataset.catId === idCat) ? "" : "none";
            });
            document.querySelectorAll(".card-pub").forEach(card => {
                card.style.display = card.dataset.nombre.includes(nombre) ? "" : "none";
            });
            if(wrap) setActivo(wrap.querySelector(".categoriaFiltro"));
            cerrarDropdowns();
        });
    });

    document.addEventListener("click", cerrarDropdowns);
});
</script>

<!-- Toggle secciones por categoría -->
<script>
document.querySelectorAll(".flechaToggle").forEach(flecha => {
    flecha.closest(".nameCatArriba")?.addEventListener("click", function(){
        const siguiente = this.nextElementSibling;
        if(siguiente && siguiente.classList.contains("CartaProducto")){
            siguiente.classList.toggle("activo");
            flecha.classList.toggle("rotar");
        }
    });
});
</script>

<!-- Modal detalle producto -->
<script>
document.querySelectorAll(".card-pub").forEach(card => {
    card.addEventListener("click", function(){
        const id = this.id.replace("producto-","");
        verProducto(id);
    });
});

function cerrarVista(){
    document.getElementById("modalVistaProducto").style.display = "none";
    window.dispatchEvent(new Event("resetZoom"));
}

window.addEventListener("click", e => {
    const modal = document.getElementById("modalVistaProducto");
    if(e.target === modal) cerrarVista();
});

// ── Motor de Zoom / Pan ──────────────────────────────────
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

    window.addEventListener("resetZoom", () => { reset(true); showHintBriefly(); });

    wrap.addEventListener("wheel", function(e){
        e.preventDefault();
        const step = e.deltaY < 0 ? 0.3 : -0.3;
        const ns   = Math.min(MAX, Math.max(MIN, scale + step));
        const rect = wrap.getBoundingClientRect();
        const ox   = (e.clientX - rect.left  - rect.width  / 2) / scale;
        const oy   = (e.clientY - rect.top   - rect.height / 2) / scale;
        tx -= ox * (ns - scale);
        ty -= oy * (ns - scale);
        scale = ns;
        if(scale === MIN){ tx = 0; ty = 0; }
        clampT(); applyTransform(false);
        hint.classList.add("oculto");
    }, { passive: false });

    wrap.addEventListener("click", function(e){
        if(dragging) return;
        if(scale > 1){ reset(true); return; }
        const rect = wrap.getBoundingClientRect();
        const ox   = e.clientX - rect.left  - rect.width  / 2;
        const oy   = e.clientY - rect.top   - rect.height / 2;
        scale = 2.8;
        tx = -(ox / scale) * (scale - 1);
        ty = -(oy / scale) * (scale - 1);
        clampT(); applyTransform(true);
        hint.classList.add("oculto");
    });

    wrap.addEventListener("mousedown", function(e){
        if(scale <= 1) return;
        dragging = true; lastX = e.clientX; lastY = e.clientY;
        img.style.cursor = "grabbing"; e.preventDefault();
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
})();

function verProducto(id){
    fetch(`<?= SITE_URL ?>/api/producto-publico?id=${id}`)
    .then(r => r.json())
    .then(data => {
        if(data.error) return;

        const modal = document.getElementById("modalVistaProducto");
        modal.style.display = "flex";
        requestAnimationFrame(() => {
            const contenido = modal.querySelector(".pub-modal-vista");
            if(contenido) contenido.classList.add("visible");
        });

        window.dispatchEvent(new Event("resetZoom"));

        // Nombre
        document.getElementById("tituloProducto").textContent = data.nombre;
        // Ubicación
        document.getElementById("ubicacionProducto").textContent = data.ubicacion || "—";
        // Descripción
        document.getElementById("descripcionProducto").textContent = data.descripcion || "";
        const descBloque = document.getElementById("det-desc-bloque");
        if(descBloque) descBloque.style.display = data.descripcion ? "" : "none";

        // Badge oferta sobre imagen
        const ofertaBadgeImg = document.getElementById("detOfertaBadgeImg");
        if(ofertaBadgeImg){
            if(data.enOferta && data.descuento > 0){
                ofertaBadgeImg.innerHTML = `<i class="bi bi-tag-fill"></i> -${Math.round(data.descuento)}% OFERTA`;
                ofertaBadgeImg.style.display = "inline-flex";
            } else {
                ofertaBadgeImg.style.display = "none";
            }
        }

        // Precio
        const precioOriginalEl = document.getElementById("detPrecioOriginal");
        const precioFinalEl    = document.getElementById("detPrecioFinal");
        const precioBadgeEl    = document.getElementById("detPrecioBadge");
        if(data.enOferta && data.descuento > 0){
            precioOriginalEl.textContent = "$" + Number(data.precio).toLocaleString("es-CO");
            precioOriginalEl.style.display = "";
            precioFinalEl.textContent = "$" + Number(data.precioFinal).toLocaleString("es-CO");
            precioBadgeEl.innerHTML = `<i class="bi bi-tag-fill"></i> -${Math.round(data.descuento)}%`;
            precioBadgeEl.style.display = "inline-flex";
        } else {
            precioOriginalEl.style.display = "none";
            precioFinalEl.textContent = "$" + Number(data.precio).toLocaleString("es-CO");
            precioBadgeEl.style.display = "none";
        }

        // Pill categoría clickeable
        const pillCat = document.getElementById("detPillCat");
        if(pillCat){
            if(data.categoria && data.categoria !== "Sin categoría" && data.idCategoria){
                document.getElementById("detCatNombre").textContent = data.categoria;
                pillCat.style.display = "inline-flex";
                pillCat.dataset.catId = data.idCategoria;
                pillCat.onclick = function(){
                    cerrarVista();
                    filtrarPorCategoria(String(data.idCategoria));
                };
            } else {
                pillCat.style.display = "none";
                pillCat.onclick = null;
            }
        }
        // Pills subcategorías individuales clickeables
        const subsWrap = document.getElementById("detSubsWrap");
        if(subsWrap){
            subsWrap.innerHTML = "";
            if(Array.isArray(data.subcategorias) && data.subcategorias.length){
                data.subcategorias.forEach(sub => {
                    const sp = document.createElement("span");
                    sp.className = "det-pill det-pill-sub det-pill-clickable";
                    sp.title = "Ver productos de esta subcategoría";
                    sp.innerHTML = `<i class="bi bi-diagram-3-fill"></i> ${sub.nombre}`;
                    sp.style.cursor = "pointer";
                    sp.onclick = function(){
                        cerrarVista();
                        filtrarPorSubcategoria(String(sub.id), sub.nombre);
                    };
                    subsWrap.appendChild(sp);
                });
            }
        }
        // Pill stock
        const stockTxt = document.getElementById("detStockTxt");
        if(stockTxt) stockTxt.textContent = data.disponibles + " disponible" + (data.disponibles !== 1 ? "s" : "");

        // Botones WhatsApp
        const waBase = `https://wa.me/<?php echo htmlspecialchars($whatsappNum); ?>`;
        const waMsgNormal = encodeURIComponent("Hola, me interesa el producto: " + data.nombre);
        const waMsgOferta = encodeURIComponent("Hola, me interesa el producto: " + data.nombre + " que está en oferta con " + Math.round(data.descuento) + "% de descuento.");
        document.getElementById("btnWhatsapp").href = `${waBase}?text=${waMsgNormal}`;
        const btnWaOferta = document.getElementById("btnWaOferta");
        if(btnWaOferta){
            if(data.enOferta && data.descuento > 0){
                btnWaOferta.href = `${waBase}?text=${waMsgOferta}`;
                btnWaOferta.style.display = "flex";
            } else {
                btnWaOferta.style.display = "none";
            }
        }

        // Estado badge
        const badgeEl = document.getElementById("estadoProducto");
        badgeEl.textContent = data.estado;
        badgeEl.className = "det-badge-estado det-badge-" +
            data.estado.toLowerCase().normalize("NFD").replace(/[\u0300-\u036f]/g,"").replace(/\s+/g,"-");

        const contMini    = document.getElementById("miniaturas");
        const imgPrincipal = document.getElementById("imagenPrincipalVista");
        contMini.innerHTML = "";

        // Limpiar dots previos
        const dotsEl = document.getElementById("mobSwipeDots");
        if(dotsEl) dotsEl.innerHTML = "";

        const imagenGrandeEl = document.getElementById("imagenGrande");
        function setImgPrincipal(src){
            imgPrincipal.src = src;
            if(imagenGrandeEl) imagenGrandeEl.style.setProperty("--img-blur", `url(${src})`);
        }

        if(data.imagenes.length > 0){
            setImgPrincipal(data.imagenes[0].ruta);

            data.imagenes.forEach((img, idx) => {
                // Miniatura
                const mini = document.createElement("img");
                mini.src = img.ruta;
                if(idx === 0) mini.classList.add("miniatura-activa","activa");
                mini.addEventListener("click", () => {
                    setImgPrincipal(img.ruta);
                    contMini.querySelectorAll("img").forEach(m => {
                        m.classList.remove("miniatura-activa","activa");
                    });
                    mini.classList.add("miniatura-activa","activa");
                    actualizarDots(idx);
                    window.dispatchEvent(new Event("resetZoom"));
                });
                contMini.appendChild(mini);

                // Dot
                if(dotsEl && data.imagenes.length > 1){
                    const dot = document.createElement("span");
                    if(idx === 0) dot.classList.add("activo");
                    dotsEl.appendChild(dot);
                }
            });

            // Swipe de imágenes en móvil
            if(window.matchMedia("(max-width:768px)").matches){
                let touchStartX = 0;
                let imgIdx = 0;
                const imgs = data.imagenes;

                imgPrincipal.addEventListener("touchstart", e => {
                    touchStartX = e.touches[0].clientX;
                }, { passive: true });

                imgPrincipal.addEventListener("touchend", e => {
                    const diff = touchStartX - e.changedTouches[0].clientX;
                    if(Math.abs(diff) < 40) return; // no swipe
                    if(diff > 0 && imgIdx < imgs.length - 1) imgIdx++;
                    else if(diff < 0 && imgIdx > 0) imgIdx--;
                    setImgPrincipal(imgs[imgIdx].ruta);
                    contMini.querySelectorAll("img").forEach((m,i) => {
                        m.classList.toggle("miniatura-activa", i === imgIdx);
                        m.classList.toggle("activa", i === imgIdx);
                    });
                    actualizarDots(imgIdx);
                    window.dispatchEvent(new Event("resetZoom"));
                }, { passive: true });
            }
        } else {
            imgPrincipal.src = "";
        }
    });
}

function actualizarDots(idx){
    const dotsEl = document.getElementById("mobSwipeDots");
    if(!dotsEl) return;
    dotsEl.querySelectorAll("span").forEach((d,i) => d.classList.toggle("activo", i===idx));
}
</script>

<!-- ══ INTERACCIONES MÓVIL ══════════════════════════════════════════ -->
<script>
(function(){
    const isMobile = () => window.matchMedia("(max-width: 768px)").matches;

    /* ── Insertar contenedor de dots en la imagen grande ──────────── */
    const imgGrande = document.getElementById("imagenGrande");
    if(imgGrande){
        const dots = document.createElement("div");
        dots.className = "mob-swipe-dots";
        dots.id = "mobSwipeDots";
        imgGrande.insertAdjacentElement("afterend", dots);
    }

    /* ── Bottom Nav: acciones ─────────────────────────────────────── */
    const btnInicio     = document.getElementById("mobNavInicio");
    const btnCategorias = document.getElementById("mobNavCategorias");
    const btnBuscar     = document.getElementById("mobNavBuscar");
    const btnLogin      = document.getElementById("mobNavLogin");
    const btnMascota    = document.getElementById("mobNavMascota");
    if(btnMascota){
        btnMascota.addEventListener("click", () => {
            window.scrollTo({ top: 0, behavior: "smooth" });
            setNavActivo(btnInicio);
        });
    }

    function setNavActivo(btn){
        document.querySelectorAll(".mob-nav-item:not(.mob-nav-center)").forEach(b => b.classList.remove("activo"));
        if(btn) btn.classList.add("activo");
    }

    if(btnInicio){
        btnInicio.addEventListener("click", () => {
            window.scrollTo({ top: 0, behavior: "smooth" });
            setNavActivo(btnInicio);
        });
    }

    if(btnCategorias){
        btnCategorias.addEventListener("click", () => {
            const filtro = document.querySelector(".filtro-barra");
            if(filtro){
                const y = filtro.getBoundingClientRect().top + window.scrollY - 60;
                window.scrollTo({ top: y, behavior: "smooth" });
            }
            setNavActivo(btnCategorias);
        });
    }

    if(btnBuscar){
        btnBuscar.addEventListener("click", () => {
            const input = document.querySelector(".pub-search-bar input[name='q']");
            if(input){
                window.scrollTo({ top: 0, behavior: "smooth" });
                setTimeout(() => input.focus(), 350);
            }
            setNavActivo(btnBuscar);
        });
    }

    if(btnLogin){
        btnLogin.addEventListener("click", () => {
            if(typeof window.abrirModalLogin === "function"){
                window.abrirModalLogin();
            } else {
                const modal    = document.getElementById("modalLogin");
                const contenido = modal?.querySelector(".modal-login-contenido");
                if(modal){
                    modal.style.display = "flex";
                    requestAnimationFrame(() => {
                        if(contenido) contenido.classList.add("visible");
                    });
                }
            }
            setNavActivo(btnLogin);
        });
    }

    /* ── Scroll: actualizar nav activo + mostrar scroll-top ───────── */
    const scrollTop = document.getElementById("mobScrollTop");
    let ticking = false;

    window.addEventListener("scroll", () => {
        if(!isMobile()) return;
        if(ticking) return;
        ticking = true;
        requestAnimationFrame(() => {
            const sy = window.scrollY;
            if(scrollTop) scrollTop.classList.toggle("visible", sy > 300);

            // Detectar qué sección está visible
            const filtro = document.querySelector(".filtro-barra");
            if(filtro){
                const fy = filtro.getBoundingClientRect().top;
                if(fy <= 70 && sy > 100) setNavActivo(btnCategorias);
                else if(sy < 100) setNavActivo(btnInicio);
            }
            ticking = false;
        });
    }, { passive: true });

    if(scrollTop){
        scrollTop.addEventListener("click", () => {
            window.scrollTo({ top: 0, behavior: "smooth" });
            setNavActivo(btnInicio);
        });
    }

    /* ── Cerrar modal producto con animación ──────────────────────── */
    window._cerrarVistaMobOriginal = window.cerrarVista;
    window.cerrarVista = function(){
        const modal    = document.getElementById("modalVistaProducto");
        const contenido = modal?.querySelector(".pub-modal-vista");
        if(isMobile() && contenido){
            contenido.classList.remove("visible");
            setTimeout(() => {
                modal.style.display = "none";
                window.dispatchEvent(new Event("resetZoom"));
            }, 340);
        } else {
            modal.style.display = "none";
            window.dispatchEvent(new Event("resetZoom"));
        }
    };

    // Cerrar al tocar el overlay
    document.getElementById("modalVistaProducto")?.addEventListener("click", function(e){
        if(e.target === this) window.cerrarVista();
    });

    /* ── Toast helper ─────────────────────────────────────────────── */
    window.mobToast = function(msg, icon){
        if(!isMobile()) return;
        const el  = document.getElementById("mobToast");
        const txt = document.getElementById("mobToastMsg");
        const ic  = el?.querySelector("i");
        if(!el || !txt) return;
        txt.textContent = msg;
        if(ic && icon) ic.className = "bi bi-" + icon;
        el.classList.add("show");
        setTimeout(() => el.classList.remove("show"), 2800);
    };

    /* ── Swipe vertical para cerrar modal (bottom-sheet) ─────────── */
    (function setupSwipeClose(){
        const modal = document.getElementById("modalVistaProducto");
        const sheet = modal?.querySelector(".pub-modal-vista");
        if(!sheet) return;

        let startY = 0;

        sheet.addEventListener("touchstart", e => {
            startY = e.touches[0].clientY;
        }, { passive: true });

        sheet.addEventListener("touchend", e => {
            if(!isMobile()) return;
            const diff = e.changedTouches[0].clientY - startY;
            if(diff > 80) window.cerrarVista(); // deslizar abajo = cerrar
        }, { passive: true });
    })();

    /* ── Vibración táctil al abrir producto ──────────────────────── */
    document.querySelectorAll(".card-pub").forEach(card => {
        card.addEventListener("touchstart", () => {
            if(navigator.vibrate) navigator.vibrate(8);
        }, { passive: true });
    });

    /* ── iOS: evitar zoom al hacer foco en inputs ─────────────────── */
    // (ya manejado con font-size:16px en el CSS)

})();
</script>

<!-- ══ FILTROS AVANZADOS JS ═════════════════════════════════════════ -->
<script>
(function(){
    const panel      = document.getElementById("filtrosPanel");
    const overlay    = document.getElementById("filtrosOverlay");
    const btnAbrir   = document.getElementById("btnAbrirFiltros");
    const btnCerrar  = document.getElementById("btnCerrarFiltros");
    const btnAplicar = document.getElementById("btnAplicarFiltros");
    const btnLimpiar = document.getElementById("btnLimpiarFiltros");
    const selectOrden = document.getElementById("selectOrden");
    const badge      = document.getElementById("filtrosBadge");
    const chipsWrap  = document.getElementById("filtrosActivosChips");

    // Estado de filtros
    let filtros = { min: "", max: "", soloOferta: false, ubicaciones: [], categorias: [], subcategorias: [] };

    /* ── Abrir / cerrar panel ───────────────────────────── */
    function abrirPanel(){
        panel.classList.add("abierto");
        overlay.classList.add("visible");
        btnAbrir.classList.add("activo");
    }
    function cerrarPanel(){
        panel.classList.remove("abierto");
        overlay.classList.remove("visible");
        btnAbrir.classList.remove("activo");
    }
    btnAbrir.addEventListener("click", abrirPanel);
    btnCerrar.addEventListener("click", cerrarPanel);
    overlay.addEventListener("click", cerrarPanel);

    /* ── Construir lista de ubicaciones únicas ──────────── */
    function capitalizar(str){
        return str.replace(/\b\w/g, c => c.toUpperCase());
    }
    function buildUbicaciones(){
        // Agrupar por idMun (si existe) o por texto normalizado
        const map = {}; // key → { label, count, rawValues[] }
        document.querySelectorAll(".card-pub").forEach(card => {
            const idMun = card.dataset.idMun || "0";
            const ubic  = card.dataset.ubicacion?.trim();
            if(!ubic) return;
            const key = idMun !== "0" ? "mun_" + idMun : "txt_" + ubic;
            if(!map[key]){
                map[key] = { label: capitalizar(ubic), count: 0, rawValues: new Set() };
            }
            map[key].count++;
            map[key].rawValues.add(ubic);
        });

        const cont = document.getElementById("filtroUbicaciones");
        cont.innerHTML = "";
        Object.entries(map)
            .sort((a,b) => b[1].count - a[1].count)
            .forEach(([key, info]) => {
                const rawArr = [...info.rawValues];
                const isChecked = rawArr.some(v => filtros.ubicaciones.includes(v));
                const label = document.createElement("label");
                label.className = "filtro-ubic-item" + (isChecked ? " checked" : "");
                label.dataset.rawValues = rawArr.join("||");
                label.innerHTML = `
                    <input type="checkbox" class="filtro-ubic-cb" ${isChecked ? "checked" : ""}>
                    <span>${info.label}</span>
                    <span class="filtro-ubic-count">${info.count}</span>`;
                label.querySelector("input").addEventListener("change", function(){
                    label.classList.toggle("checked", this.checked);
                });
                cont.appendChild(label);
            });
    }
    buildUbicaciones();

    /* ── Buscador de ubicaciones ─────────────────────── */
    document.getElementById("filtroUbicSearch").addEventListener("input", function(){
        const q = this.value.trim().toLowerCase();
        const items = document.querySelectorAll("#filtroUbicaciones .filtro-ubic-item");
        let visible = 0;
        items.forEach(item => {
            const txt = item.querySelector("span")?.textContent.toLowerCase() || "";
            const match = !q || txt.includes(q);
            item.style.display = match ? "" : "none";
            if(match) visible++;
        });
        document.getElementById("filtroUbicEmpty").style.display = visible === 0 ? "" : "none";
    });

    /* ── Leer valores del panel ─────────────────────────── */
    function leerFiltros(){
        filtros.min         = document.getElementById("filtroMin").value.trim();
        filtros.max         = document.getElementById("filtroMax").value.trim();
        filtros.soloOferta  = document.getElementById("filtroSoloOferta").checked;
        // Recoger todos los rawValues de los labels marcados
        filtros.ubicaciones = [];
        document.querySelectorAll("#filtroUbicaciones .filtro-ubic-cb:checked").forEach(cb => {
            const rawVals = cb.closest("label")?.dataset.rawValues || "";
            rawVals.split("||").filter(Boolean).forEach(v => {
                if(!filtros.ubicaciones.includes(v)) filtros.ubicaciones.push(v);
            });
        });
        filtros.categorias  = [...document.querySelectorAll(".filtro-cat-cb:checked")].map(cb => cb.value);
    }

    /* ── Restaurar panel desde estado ──────────────────── */
    function restaurarPanel(){
        document.getElementById("filtroMin").value = filtros.min;
        document.getElementById("filtroMax").value = filtros.max;
        document.getElementById("filtroSoloOferta").checked = filtros.soloOferta;
        // Limpiar búsqueda y reconstruir lista normalizada
        const searchEl = document.getElementById("filtroUbicSearch");
        if(searchEl) { searchEl.value = ""; }
        buildUbicaciones();
        document.querySelector("#filtroUbicaciones .filtro-ubic-item")?.closest && null; // forzar repaint
        document.querySelectorAll(".filtro-ubic-cb").forEach(cb => {
            cb.checked = filtros.ubicaciones.includes(cb.value);
            cb.closest(".filtro-ubic-item")?.classList.toggle("checked", cb.checked);
        });
        document.getElementById("filtroUbicEmpty").style.display = "none";
        document.querySelectorAll("#filtroUbicaciones .filtro-ubic-item").forEach(i => i.style.display = "");
        document.querySelectorAll(".filtro-cat-cb").forEach(cb => {
            cb.checked = filtros.categorias.includes(cb.value);
        });
    }

    /* ── Contar filtros activos ─────────────────────────── */
    function countFiltros(){
        let n = 0;
        if(filtros.min) n++;
        if(filtros.max) n++;
        if(filtros.soloOferta) n++;
        n += filtros.ubicaciones.length;
        n += filtros.categorias.length;
        n += filtros.subcategorias.length;
        return n;
    }

    /* ── Renderizar chips activos ───────────────────────── */
    function renderChips(){
        chipsWrap.innerHTML = "";
        const add = (label, onRemove) => {
            const chip = document.createElement("button");
            chip.className = "chip-activo";
            chip.innerHTML = `${label} <i class="bi bi-x-lg"></i>`;
            chip.addEventListener("click", () => { onRemove(); aplicar(); });
            chipsWrap.appendChild(chip);
        };
        if(filtros.min) add(`Desde $${Number(filtros.min).toLocaleString("es-CO")}`, () => { filtros.min = ""; document.getElementById("filtroMin").value = ""; });
        if(filtros.max) add(`Hasta $${Number(filtros.max).toLocaleString("es-CO")}`, () => { filtros.max = ""; document.getElementById("filtroMax").value = ""; });
        if(filtros.soloOferta) add("Solo ofertas", () => { filtros.soloOferta = false; document.getElementById("filtroSoloOferta").checked = false; });
        filtros.ubicaciones.forEach(u => {
            add(`<i class="bi bi-geo-alt-fill"></i> ${u}`, () => {
                filtros.ubicaciones = filtros.ubicaciones.filter(x => x !== u);
                restaurarPanel();
            });
        });
        filtros.categorias.forEach(cid => {
            const cb = document.querySelector(`.filtro-cat-cb[value="${cid}"]`);
            const nombre = cb?.closest(".filtro-cat-item")?.querySelector("span")?.textContent || cid;
            add(`<i class="bi bi-bookmark-fill"></i> ${nombre}`, () => {
                filtros.categorias = filtros.categorias.filter(x => x !== cid);
                restaurarPanel();
            });
        });
        filtros.subcategorias.forEach(sid => {
            const nombre = window._subcatNames?.[sid] || `Subcategoría ${sid}`;
            add(`<i class="bi bi-diagram-3-fill"></i> ${nombre}`, () => {
                filtros.subcategorias = filtros.subcategorias.filter(x => x !== sid);
                aplicar();
            });
        });

        const n = countFiltros();
        if(n > 0){
            badge.textContent = n;
            badge.style.display = "flex";
        } else {
            badge.style.display = "none";
        }
    }

    /* ── Aplicar filtros + ordenamiento ─────────────────── */
    function aplicar(){
        const orden = selectOrden.value;
        const min   = filtros.min ? parseFloat(filtros.min) : -Infinity;
        const max   = filtros.max ? parseFloat(filtros.max) :  Infinity;

        // Reunir todas las cards
        const todasCards = [...document.querySelectorAll(".card-pub")];

        // Filtrar
        todasCards.forEach(card => {
            const precio   = parseFloat(card.dataset.precio)    || 0;
            const oferta   = card.dataset.oferta === "1";
            const ubicacion = card.dataset.ubicacion || "";
            const catId    = card.dataset.categoria  || "";

            let visible = true;
            if(precio < min || precio > max)                                    visible = false;
            if(filtros.soloOferta && !oferta)                                   visible = false;
            if(filtros.ubicaciones.length && !filtros.ubicaciones.includes(ubicacion)) visible = false;
            if(filtros.categorias.length  && !filtros.categorias.includes(catId))      visible = false;
            if(filtros.subcategorias.length){
                const cardSubs = (card.dataset.subcategorias || "").split(",").map(s => s.trim()).filter(Boolean);
                if(!filtros.subcategorias.some(sid => cardSubs.includes(sid))) visible = false;
            }

            card.style.display = visible ? "" : "none";
        });

        // Ordenar las cards visibles dentro de cada grid
        document.querySelectorAll(".CartaProducto").forEach(grid => {
            const cards = [...grid.querySelectorAll(".card-pub")].filter(c => c.style.display !== "none");
            const sorted = [...cards].sort((a, b) => {
                switch(orden){
                    case "precio-asc":  return parseFloat(a.dataset.precio) - parseFloat(b.dataset.precio);
                    case "precio-desc": return parseFloat(b.dataset.precio) - parseFloat(a.dataset.precio);
                    case "nombre-asc":  return (a.dataset.nombre||"").localeCompare(b.dataset.nombre||"");
                    case "nombre-desc": return (b.dataset.nombre||"").localeCompare(a.dataset.nombre||"");
                    case "oferta":      return (b.dataset.oferta||0) - (a.dataset.oferta||0);
                    default: return 0;
                }
            });
            sorted.forEach(c => grid.appendChild(c));
        });

        // Mostrar/ocultar secciones vacías
        document.querySelectorAll(".nameCatArriba").forEach(header => {
            const catId = header.dataset.catId;
            const grid  = document.querySelector(`.CartaProducto[data-cat-id="${catId}"]`);
            const visibles = grid ? [...grid.querySelectorAll(".card-pub")].filter(c => c.style.display !== "none").length : 0;
            header.style.display = visibles > 0 ? "" : "none";
            if(grid) grid.style.display = visibles > 0 ? "" : "none";
        });

        // Mensaje sin resultados
        let sinResultadosEl = document.getElementById("filtrosSinResultados");
        const hayVisibles = todasCards.some(c => c.style.display !== "none");
        if(!hayVisibles){
            if(!sinResultadosEl){
                sinResultadosEl = document.createElement("div");
                sinResultadosEl.id = "filtrosSinResultados";
                sinResultadosEl.className = "filtros-sin-resultados";
                sinResultadosEl.innerHTML = `
                    <i class="bi bi-funnel"></i>
                    <p>Ningún producto coincide con los filtros aplicados.</p>
                    <a href="#" id="btnQuitarFiltrosSR">Quitar filtros</a>`;
                document.querySelector(".ProductGeneral")?.after(sinResultadosEl);
                sinResultadosEl.querySelector("#btnQuitarFiltrosSR")?.addEventListener("click", e => {
                    e.preventDefault(); limpiar();
                });
            }
            sinResultadosEl.style.display = "flex";
        } else if(sinResultadosEl){
            sinResultadosEl.style.display = "none";
        }

        renderChips();
    }

    /* ── Limpiar todo ───────────────────────────────────── */
    function limpiar(){
        filtros = { min: "", max: "", soloOferta: false, ubicaciones: [], categorias: [], subcategorias: [] };
        restaurarPanel();
        selectOrden.value = "relevancia";
        document.querySelectorAll(".card-pub").forEach(c => c.style.display = "");
        document.querySelectorAll(".nameCatArriba, .CartaProducto").forEach(el => el.style.display = "");
        const sr = document.getElementById("filtrosSinResultados");
        if(sr) sr.style.display = "none";
        renderChips();
    }

    btnAplicar.addEventListener("click", () => { leerFiltros(); cerrarPanel(); aplicar(); });
    btnLimpiar.addEventListener("click", () => { limpiar(); cerrarPanel(); });
    selectOrden.addEventListener("change", () => { leerFiltros(); aplicar(); });

    /* Aplicar al abrir panel restaura checks */
    btnAbrir.addEventListener("click", restaurarPanel);

    /* ── Funciones globales llamadas desde los pills del modal ── */
    window.filtrarPorCategoria = function(catId){
        limpiar();
        filtros.categorias = [catId];
        aplicar();
        // Scroll suave a los productos
        document.querySelector(".ProductGeneral")?.scrollIntoView({ behavior: "smooth", block: "start" });
    };

    window.filtrarPorSubcategoria = function(subId, nombre){
        limpiar();
        filtros.subcategorias = [subId];
        if(nombre) {
            if(!window._subcatNames) window._subcatNames = {};
            window._subcatNames[subId] = nombre;
        }
        aplicar();
        document.querySelector(".ProductGeneral")?.scrollIntoView({ behavior: "smooth", block: "start" });
    };
})();
</script>

</body>
</html>
