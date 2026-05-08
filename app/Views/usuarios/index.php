<?php
ob_start();
    /* Esto es para la conection */
   
if (session_status() === PHP_SESSION_NONE) session_start();
    $usuarioLogeado = $_SESSION['usuarios'];
    include(ROOT_PATH . "/config/conection.php");
    $con= conection();

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
    <link rel="icon" type="image/png" sizes="64x64" href="<?= SITE_URL ?>/assets/imagenes/partelogo.png">
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
                                <span class="bienvenido-texto">Bienvenido,
                                    <?php if($row['rol'] ==="admin"){
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
                    <a class="menu-card">
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
                dropdown.style.display =
                    dropdown.style.display === "block" ? "none" : "block";
            });
            document.addEventListener("click", e => {
                if (!userMenu.contains(e.target)) dropdown.style.display = "none";
            });
        </script>

        <!-- Cuerpo de la pagina -->
         <div class="cuepo">

            <!-- La Ruta que se observa en la parte superior de la pagina -->
            <div class="ruta">
                <a href="<?= SITE_URL ?>/admin"><i class="bi bi-house-fill"></i> Panel</a>
                <span class="separator"><i class="bi bi-chevron-right"></i></span>
                <span class="actual"><i class="bi bi-people-fill"></i> Usuarios</span>
            </div>

            <div class="primeraPart">
                 <!-- Nombre del modulo -->
                <div class="rotulaApratado">
                    <h1>Gestión de Usuarios</h1>
                    <p>Administrar la información de usuarios registrados</p>
                </div>

                <!-- Agregar un Nuevo Usuario -->
                <?php if($rolUsuario === 'admin'): ?>
                <div class="agregar">
                    <div>
                        <button id="btnAgregar">
                            <i class="bi bi-person-fill-add"></i>
                            Agregar Usuario
                        </button>
                    </div>
                </div>
                <?php endif; ?>
                <!-- MODAL AGREGAR -->
                <div class="modal" id="modalUsuario">
                    <div class="modal-contenido">
                        <span class="cerrar" id="cerrarModal">&times;</span>
                        <div class="modal-header">
                            
                            <h2>Agregar Usuario</h2>
                            
                        </div>
                        <br>
                        <form action="<?= SITE_URL ?>/api/usuarios/agregar" method="POST" class="modal-form" id="formAgregarUsuario">

                            <label>Nombre</label>
                            <input type="text" name="nombre" required>
                            <label>Correo</label>
                            <input type="email" name="correo" required>
                            <label>Contraseña</label>
                            <input type="password" name="contraseña" required>
                            <label>Rol</label>
                            <select name="rol" required>
                                <option value="">Seleccionar rol</option>
                                <option value="admin">Administrador</option>
                                <option value="gestor">Gestor</option>
                            </select>
                            <div id="mensajeModal"></div>
                            

                            <div class="modal-botones">
                                <button type="submit" class="guardar">Guardar</button>
                                <button type="button" class="cancelar" id="cancelarModal">Cancelar</button>
                            </div>
                            
                        </form>
                       
                    </div>
                </div>
            
                <script>
                document.getElementById("formAgregarUsuario")
                .addEventListener("submit", function(e){

                    e.preventDefault(); // evita recarga

                    const formData = new FormData(this);

                    fetch("<?= SITE_URL ?>/api/usuarios/agregar", {
                        method: "POST",
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {

                        const mensaje = document.getElementById("mensajeModal");

                        if(data.status === "error"){
                            mensaje.innerHTML = 
                                `<div style="color:red; padding:10px;">
                                    ${data.message}
                                </div>`;
                        }

                        if(data.status === "success"){
                            location.reload();
                        }
                    })
                    .catch(error => {
                        console.error("Error:", error);
                    });
                });
                </script>
                <script>
                    const btnAgregar = document.getElementById("btnAgregar");
                    const modal = document.getElementById("modalUsuario");
                    const cerrar = document.getElementById("cerrarModal");
                    const cancelar = document.getElementById("cancelarModal");

                    btnAgregar.addEventListener("click", () => {
                        modal.style.display = "flex";
                    });

                    cerrar.addEventListener("click", () => {
                        modal.style.display = "none";
                    });

                    cancelar.addEventListener("click", () => {
                        modal.style.display = "none";
                    });

                    window.addEventListener("click", (e) => {
                        if (e.target === modal) {
                            modal.style.display = "none";
                        }
                    });
                </script>
            </div>
            <!-- CARDS -->
            <div class="contenedor-usuarios">

                <?php
                $sql="SELECT * FROM usuarios";
                $query=mysqli_query($con,$sql);

                while($row=mysqli_fetch_assoc($query)){

                $esMiPerfil = $row['idUsuario'] == $usuarioLogeado;
                $soyAdmin = $rolUsuario == "admin";
                ?>

                <div class="card-usuario <?php echo $row['rol'] === 'admin' ? 'card-usuario-admin' : 'card-usuario-gestor'; ?>">

                    <!-- Franja superior + avatar -->
                    <div class="card-usuario-top">
                        <div class="usuario-avatar">
                            <i class="bi bi-person-fill"></i>
                        </div>
                        <?php if($esMiPerfil): ?>
                            <span class="card-usuario-yo">Tú</span>
                        <?php endif; ?>
                    </div>

                    <!-- Información -->
                    <div class="usuario-body">
                        <h3 class="usuario-nombre"><?php echo htmlspecialchars($row['nombreUsuario']); ?></h3>

                        <span class="rol-usuario <?php echo $row['rol'] === 'admin' ? 'rol-admin' : 'rol-gestor'; ?>">
                            <i class="bi bi-shield-fill-check"></i>
                            <?php echo ucfirst($row['rol']); ?>
                        </span>

                        <div class="usuario-datos">
                            <div class="usuario-dato">
                                <i class="bi bi-envelope-fill"></i>
                                <span><?php echo htmlspecialchars($row['correo']); ?></span>
                            </div>
                            <?php if($esMiPerfil || $soyAdmin): ?>
                            <div class="usuario-dato usuario-pass">
                                <i class="bi bi-key-fill"></i>
                                <span><?php echo str_repeat('●', min(strlen($row['contraseña']), 10)); ?></span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Acciones -->
                    <div class="usuario-acciones">
                        <?php if($esMiPerfil || $soyAdmin): ?>
                        <a href="#"
                           class="btn-usuario btn-editar-usuario btnEditar"
                           data-id="<?php echo $row['idUsuario']?>"
                           data-nombre="<?php echo htmlspecialchars($row['nombreUsuario'])?>"
                           data-correo="<?php echo htmlspecialchars($row['correo'])?>"
                           data-pass="<?php echo htmlspecialchars($row['contraseña'])?>"
                           data-rol="<?php echo $row['rol']?>">
                            <i class="bi bi-pencil-fill"></i> Editar
                        </a>
                        <?php endif; ?>

                        <?php if($soyAdmin && !$esMiPerfil): ?>
                        <a href="<?= SITE_URL ?>/api/usuarios/eliminar?id=<?php echo $row['idUsuario']; ?>"
                           onclick="return confirm('¿Eliminar usuario?')"
                           class="btn-usuario btn-eliminar-usuario">
                            <i class="bi bi-trash-fill"></i> Eliminar
                        </a>
                        <?php endif; ?>
                    </div>

                </div>

                <?php 
                    } 
                ?>

            </div>


            

            <!-- Modal de Editar -->
            <div class="modal" id="modalEditar">
                <div class="modal-contenido">
                    <span class="cerrarEditar">&times;</span>
                    <h2>Editar Usuario</h2>

                    <form method="POST"  id="formEditarUsuario">
                        <input type="hidden" name="id" id="editId">

                        <label>Nombre</label>
                        <input type="text" name="nombre" id="editNombre" required>

                        <label>Correo</label>
                        <input type="email" name="correo" id="editCorreo" required>

                        <label>Contraseña</label>
                        <input type="text" name="contraseña" id="editPass" required>

                        <label>Rol</label>
                        <select name="rol" id="editRol">
                            <option value="admin">Administrador</option>
                            <option value="gestor">Gestor</option>
                        </select>

                        <div id="mensajeEditar"></div>

                        <button type="submit">Actualizar</button>

                    </form>
                </div>
            </div>
            <script>
                document.getElementById("formEditarUsuario")
                .addEventListener("submit", function(e){

                    e.preventDefault();

                    const formData = new FormData(this);

                    fetch("<?= SITE_URL ?>/api/usuarios/editar", {
                        method: "POST",
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {

                        const mensaje = document.getElementById("mensajeEditar");

                        if(data.status === "error"){
                            mensaje.innerHTML = `
                                <div style="color:red; padding:10px;">
                                    ${data.message}
                                </div>`;
                        }

                        if(data.status === "success"){
                            location.reload();
                        }

                    })
                    .catch(error => console.error("Error:", error));

                });

            </script>

            <script>
    
                document.addEventListener("DOMContentLoaded", function(){

                const botonesEditar = document.querySelectorAll(".btnEditar");
                const modalEditar = document.getElementById("modalEditar");
                const cerrarEditar = document.querySelector(".cerrarEditar");

                botonesEditar.forEach(boton => {

                    boton.addEventListener("click", function(e){

                        e.preventDefault();

                        document.getElementById("editId").value = this.dataset.id;
                        document.getElementById("editNombre").value = this.dataset.nombre;
                        document.getElementById("editCorreo").value = this.dataset.correo;
                        document.getElementById("editPass").value = this.dataset.pass;

                        const rolSelect = document.getElementById("editRol");
                        rolSelect.value = this.dataset.rol;

                        const usuarioLogeado = <?php echo json_encode($usuarioLogeado); ?>;
                        const rolUsuario = <?php echo json_encode($rolUsuario); ?>;

                        if(rolUsuario === "gestor"){
                            rolSelect.disabled = true;
                        } else if(this.dataset.id == usuarioLogeado){
                            rolSelect.disabled = true;
                        } else {
                            rolSelect.disabled = false;
                        }

                        modalEditar.style.display = "flex";
                    });

                });

                cerrarEditar.addEventListener("click", () => {
                    modalEditar.style.display = "none";
                });

                window.addEventListener("click", (e) => {
                    if (e.target === modalEditar) modalEditar.style.display = "none";
                });

            });
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
</body>
</html>