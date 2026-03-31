<?php
session_start();

// Mostrar mensaje de registro exitoso
if (isset($_SESSION['registro_exitoso'])) {
    echo "
    <div style='
        background: #4CAF50; 
        color: white; 
        padding: 15px; 
        text-align: center; 
        position: fixed; 
        top: 0; 
        left: 0; 
        right: 0; 
        z-index: 9999;
    '>
        " . htmlspecialchars($_SESSION['registro_exitoso']) . "
    </div>
    <script>
        setTimeout(() => {
            document.querySelector('[style*=\"background: #4CAF50\"]').remove();
        }, 3000);
    </script>
    ";
    unset($_SESSION['registro_exitoso']);
}
$_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
require_once __DIR__ . '/helpers/session.php'; 
require_once __DIR__ . '/config.php'; 
require_once __DIR__ . '/BaseDatos.php';
$db = new BaseDatos();

try {
    $sql = "SELECT 
                p.id_producto, 
                p.nombre, 
                p.precio,
                (SELECT nombre_archivo 
                 FROM producto_imagenes 
                 WHERE id_producto = p.id_producto 
                 AND es_principal = 1 
                 LIMIT 1) as imagen_principal
            FROM productos p
            WHERE p.estado = 'activo'
            ORDER BY p.fecha_creacion DESC";
    
    $productos = $db->query($sql)->fetchAll();
    
} catch (Exception $e) {
    $productos = [];
    error_log("Error al obtener productos: " . $e->getMessage());
}

?>


<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Título de la página -->
    <title>ELEMENT</title>

    <!-- Favicon (ICONO DE LA PESTAÑA) -->
    <link rel="icon" type="image/png" href="imagenes/logos/Element.ico">

    <!-- Enlace al archivo CSS -->
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <!-- Encabezado de la pagina (Logo, Barra de navegacion)-->
<header class="main-header">
    <div class="header-content">

        <!-- LOGO - vuelve al inicio -->
        <a href="index.php" class="logo-link">
            <img src="imagenes/logos/Element.jpg" alt="Logo" class="logo">
        </a>

        <!-- Menú desplegable de categorías dinámico -->
        <div class="dropdown">
            <a href="catalogo.php" class="nav-link">Productos</a>
        </div>
            
            <a href="index.php#quienes-somos" class="nav-link">Quienes somos</a>

        </nav>

    <!-- ICONOS A LA DERECHA -->
    <div class="nav-icons">
        <!-- Logo Usuario -->
        <div class="dropdown">

<?php if (isset($_SESSION['id_usuario'])): ?>
    <!-- Usuario está logueado -->
    
    <?php if (isset($_SESSION['rol']) && $_SESSION['rol'] === 'ADMIN'): ?>
        <!-- MENÚ ADMIN -->
        <a href="#" class="icon-link">
            <img src="imagenes/logos/profile.png" alt="Mi cuenta">
        </a>
        <div class="dropdown-content">
            <a href="/admin/dashboard.php">Dashboard</a>
            <a href="/admin/productos.php">Productos</a>
            <a href="/admin/pedidos.php">Pedidos</a>
            <a href="logout.php">Cerrar sesión</a>
        </div>
        
    <?php else: ?>
        <!-- MENÚ USUARIO NORMAL -->
        <a href="#" class="icon-link">
            <img src="imagenes/logos/profile.png" alt="Mi cuenta">
        </a>
        <div class="dropdown-content">
            <a href="perfil.php">Configuración</a>
            <a href="mis-pedidos.php">Mis pedidos</a>
            <a href="logout.php">Cerrar sesión</a>
        </div>
    <?php endif; ?>
    
<?php else: ?>
    <!-- USUARIO NO LOGUEADO -->
    <a href="#" class="icon-link" id="btn-login">
        <img src="imagenes/logos/profile.png" alt="Iniciar sesión">
    </a>
<?php endif; ?>

            </div>



            <!-- Imagen carrito -->
            <a href="carrito.php" class="icon-link">
                <img src="imagenes/logos/cart.png" alt="Carrito">
            </a>

        <form action="buscar.php" method="GET" class="search-box">
            <img class="search-icon" src="imagenes/logos/Lupa.png" alt="Buscar">
            <input type="text" 
                name="q" 
                class="search-input" 
                placeholder="Buscar productos..." 
                required>
        </form>
        </div>
    </div>
</header>

<div class="floating-buttons">
    <a href="https://wa.me/c/573236133924" class="float-btn whatsapp" target="_blank">
        <img src="imagenes/logos/whatsapp-logo.ico" alt="WhatsApp">
    </a>
</div>

<body>

    <!-- Banner-->
<section class="catalogo-banner" 
         style="background-image: url('imagenes/logos/fondo_elegante.jpg');">
    <div class="catalogo-overlay"></div>

    <div class="catalogo-banner-content">
        <img src="imagenes/logos/Element_NF.png" alt="ELEMENT" class="catalogo-logo">

        <p class="catalogo-text">
            En <strong>ELEMENT</strong>, creemos que la comodidad no está reñida con el estilo. 
            Nacimos con una idea clara: ofrecer ropa holgada, versátil y auténtica que se adapte 
            a todos los cuerpos, estilos y formas de vivir. Diseñamos prendas que fluyen contigo, 
            que respetan tu espacio y te invitan a moverte libremente.
        </p>
    </div>
</section>

<!-- ===== FILTROS GENERALES (ACTUALIZADOS) ===== -->
<section class="catalogo-filtros">
    <button class="filtro-btn activo" data-filter="todo">Todo</button>
    <button class="filtro-btn" data-filter="hombre">Hombre</button>
    <button class="filtro-btn" data-filter="mujer">Mujer</button>
    <button class="filtro-btn" data-filter="unisex">Unisex</button>
</section>

<!-- ===== GRID DE PRODUCTOS DINÁMICOS (ACTUALIZADO) ===== -->
<section class="catalogo-grid">

    <?php if (empty($productos)): ?>
        <!-- Mensaje si no hay productos -->
        <div style="grid-column: 1/-1; text-align: center; padding: 4rem 2rem; color: #666;">
            <h2>No hay productos disponibles</h2>
            <p>Vuelve pronto para ver nuestras novedades</p>
        </div>
    <?php else: ?>
        
        <?php foreach ($productos as $producto): ?>
            <?php
            // Obtener tallas disponibles para este producto
            $tallas = $db->query(
                "SELECT DISTINCT talla FROM productos_variante 
                 WHERE id_producto = :id AND estado = 'activo' AND stock > 0
                 ORDER BY FIELD(talla, 'XS', 'S', 'M', 'L', 'XL', 'XXL')",
                ['id' => $producto['id_producto']]
            )->fetchAll();
            ?>
            
            <!-- AHORA USA LA CATEGORÍA REAL DE LA BD -->
            <a href="producto-detalle.php?id=<?php echo $producto['id_producto']; ?>" 
               class="producto-card producto-card-link" 
               data-category="<?php echo htmlspecialchars($producto['categoria'] ?? 'unisex'); ?>">
                
                <div class="producto-img">
                    <?php if ($producto['imagen_principal']): ?>
                        <img src="imagenes/productos/<?php echo htmlspecialchars($producto['imagen_principal']); ?>" 
                             alt="<?php echo htmlspecialchars($producto['nombre']); ?>">
                    <?php else: ?>
                        <img src="imagenes/placeholder.png" alt="Sin imagen">
                    <?php endif; ?>
                </div>

                <div class="tallas-hover">
                    <?php if (empty($tallas)): ?>
                        <span>Sin stock</span>
                    <?php else: ?>
                        <?php foreach ($tallas as $talla): ?>
                            <span><?php echo $talla['talla']; ?></span>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <div class="producto-info">
                    <p class="producto-nuevo">Nuevo</p>
                    <h3 class="producto-nombre"><?php echo htmlspecialchars($producto['nombre']); ?></h3>
                    <p class="producto-precio">$<?php echo number_format($producto['precio'], 0, ',', '.'); ?></p>

                    <!-- Badge de categoría -->
                    <span class="categoria-badge categoria-<?php echo $producto['categoria'] ?? 'unisex'; ?>">
                        <?php 
                        $categorias = [
                            'mujer' => ' Mujer',
                            'hombre' => ' Hombre',
                            'unisex' => ' Unisex'
                        ];
                        echo $categorias[$producto['categoria'] ?? 'unisex'];
                        ?>
                    </span>

                    <!-- Tallas visibles en CELULAR -->
                    <div class="tallas-mobile">
                        <?php if (empty($tallas)): ?>
                            <span>Sin stock</span>
                        <?php else: ?>
                            <?php foreach ($tallas as $talla): ?>
                                <span><?php echo $talla['talla']; ?></span>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </a>
        
        <?php endforeach; ?>
        
    <?php endif; ?>

</section>

<style>
/* ===== ESTILOS PARA BADGE DE CATEGORÍA ===== */
.categoria-badge {
    display: inline-block;
    padding: 0.3rem 0.8rem;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
    margin-top: 0.5rem;
}

.categoria-mujer {
    background: #ffc1e3;
    color: #c2185b;
}

.categoria-hombre {
    background: #bbdefb;
    color: #1976d2;
}

.categoria-unisex {
    background: #e0e0e0;
    color: #424242;
}

/* ===== ESTILOS PARA FILTROS ===== */
.catalogo-filtros {
    display: flex;
    justify-content: center;
    gap: 1rem;
    padding: 2rem;
    flex-wrap: wrap;
}

.filtro-btn {
    padding: 0.8rem 2rem;
    border: 2px solid #ddd;
    background: #fff;
    border-radius: 30px;
    cursor: pointer;
    font-size: 1rem;
    font-weight: 600;
    transition: all 0.3s ease;
    color: #666;
}


.filtro-btn.activo {
    background: #000;
    border-color: #000;
    color: #fff;
}
</style>

<script>
// ===== FILTROS DE CATÁLOGO (ACTUALIZADO) =====
document.addEventListener('DOMContentLoaded', function() {
    const filtroBtns = document.querySelectorAll(".filtro-btn");
    const productos = document.querySelectorAll(".producto-card");

    filtroBtns.forEach(btn => {
        btn.addEventListener("click", () => {
            // Remover clase activo de todos los botones
            filtroBtns.forEach(b => b.classList.remove("activo"));
            // Agregar clase activo al botón clickeado
            btn.classList.add("activo");

            const filtro = btn.dataset.filter;

            // Filtrar productos
            productos.forEach(card => {
                const categoria = card.dataset.category;
                
                if (filtro === "todo") {
                    card.style.display = "block";
                } else if (categoria === filtro) {
                    card.style.display = "block";
                } else {
                    card.style.display = "none";
                }
            });
        });
    });
});
</script>



<style>
/* ===== ESTILOS PARA HACER LAS CARDS DE CATÁLOGO CLICKEABLES ===== */

/* Convertir la card en un enlace sin perder estilos */
.producto-card-link {
    text-decoration: none;
    color: inherit;
    display: block;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

/* Efecto hover - Elevar la card */
.producto-card-link:hover {
    transform: translateY(-10px);
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
}

/* Mantener los colores originales del texto */
.producto-card-link h3,
.producto-card-link p,
.producto-card-link .producto-precio,
.producto-card-link .producto-nombre,
.producto-card-link .producto-nuevo {
    color: inherit;
}

/* Efecto en la imagen al hacer hover */
.producto-card-link .producto-img {
    overflow: hidden;
    position: relative;
}

.producto-card-link .producto-img img {
    transition: transform 0.4s ease;
}

.producto-card-link:hover .producto-img img {
    transform: scale(1.1);
}



.producto-card-link:hover .producto-img::after {
    opacity: 1;
}


/* Efecto en el precio */
.producto-card-link:hover .producto-precio {
    color: #167912;
    font-weight: 700;
}

/* Efecto en las tallas hover */
.producto-card-link:hover .tallas-hover {
    opacity: 1;
    visibility: visible;
}

/* Cursor pointer para indicar que es clickeable */
.producto-card-link {
    cursor: pointer;
}

/* Animación suave en las tallas mobile */
.producto-card-link:hover .tallas-mobile span {
    background: #000;
    color: #fff;
    transform: scale(1.05);
}

.tallas-mobile span {
    transition: all 0.3s ease;
}

/* Efecto de brillo sutil en toda la card */
.producto-card-link::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 50%;
    height: 100%;
    background: linear-gradient(
        90deg,
        transparent,
        rgba(255, 255, 255, 0.2),
        transparent
    );
    transition: left 0.5s ease;
    z-index: 1;
    pointer-events: none;
}

.producto-card-link:hover::before {
    left: 100%;
}

/* Asegurar que el contenido esté sobre el efecto de brillo */
.producto-card-link > * {
    position: relative;
    z-index: 2;
}
</style>
</section>

<footer class="footer-premium">

    <div class="footer-container">

        <!-- Ubicaciones -->
        <div class="footer-locations">
            <h3>Tiendas ELEMENT</h3>

            <ul>
                <li>
                    <a href="https://www.google.com/maps/place/ELEMENT+cota/@4.8095624,-74.102194,17z/data=!3m1!4b1!4m6!3m5!1s0x8e3f873e9ca6f9a9:0xd7f6848d611d577d!8m2!3d4.8095624!4d-74.102194!16s%2Fg%2F11rcspbkmz?entry=ttu&g_ep=EgoyMDI1MTIwNy4wIKXMDSoASAFQAw%3D%3D" target="_blank">
                        <i class="fas fa-map-marker-alt"></i> Cota: Cra 5 # 11-63
                    </a>
                </li>

                <li>
                    <a href="https://www.google.com/maps/place/Cra.+7+%239+-+28,+Tocancip%C3%A1,+Cundinamarca/@4.9650471,-73.9171895,17z/data=!3m1!4b1!4m6!3m5!1s0x8e4073c05a1230c7:0x2b4867c5f186de08!8m2!3d4.9650418!4d-73.9146146!16s%2Fg%2F11sd7p1zx4?entry=ttu&g_ep=EgoyMDI1MTIwNy4wIKXMDSoASAFQAw%3D%3D" target="_blank">
                        <i class="fas fa-map-marker-alt"></i> Tocancipá: Cra 7 # 9-25, Local 135
                    </a>
                </li>

                <li>
                    <a href="https://www.google.com/maps/place/Cra.+13+%23+10A-62,+Funza,+Cundinamarca/@4.7151921,-74.2324099,15z/data=!3m1!4b1!4m6!3m5!1s0x8e3f82a964092f53:0x23c18cbfdb622db0!8m2!3d4.715171!4d-74.213956!16s%2Fg%2F11x7gvzj8y?entry=ttu&g_ep=EgoyMDI1MTIwNy4wIKXMDSoASAFQAw%3D%3D" target="_blank">
                        <i class="fas fa-map-marker-alt"></i> Funza: Cra 13 # 10A-62
                    </a>
                </li>

                <li>
                    <a href="https://www.google.com/maps/place/Cra.+5+Sur+%23+3-6,+Facatativ%C3%A1,+Cundinamarca/@4.8046143,-74.3577859,17z/data=!3m1!4b1!4m6!3m5!1s0x8e3f7c6319b04dc5:0x1dddd6f80d55a35!8m2!3d4.804609!4d-74.355211!16s%2Fg%2F11x11g_4p2?entry=ttu&g_ep=EgoyMDI1MTIwNy4wIKXMDSoASAFQAw%3D%3D" target="_blank">
                        <i class="fas fa-map-marker-alt"></i> Facatativá: Cra 5 # 36
                    </a>
                </li>

                <li>
                    <a href="https://www.google.com/maps/place/Cra.+100+%2320+-+59,+Fontib%C3%B3n,+Centro,+Bogot%C3%A1/@4.6756762,-74.1612481,15z/data=!3m1!4b1!4m6!3m5!1s0x8e3f9c8c3495080f:0x390162db9622fdbb!8m2!3d4.6756551!4d-74.1427942!16s%2Fg%2F11xs_sbcw8?entry=ttu&g_ep=EgoyMDI1MTIwNy4wIKXMDSoASAFQAw%3D%3D" target="_blank">
                        <i class="fas fa-map-marker-alt"></i> Fontibón Centro: Cra 100 # 20-59
                    </a>
                </li>
            </ul>
        </div>

        <!-- MARCAS -->
        <div class="footer-logos">
            <h3>Marcas que manejamos</h3>

            <div class="logos-grid">
                <img src="" alt="Marca 1">
                <img src="" alt="Marca 2">
                <img src="" alt="Marca 3">
                <img src="" alt="Marca 4">
            </div>
        </div>

        <!-- Medios de pago -->
        <div class="footer-payment">
            <h3>Medios de pago</h3>

            <div class="payment-grid">
                <img src="imagenes/logos/Visa.ico" alt="Visa">
                <img src="imagenes/logos/mastercard.ico" alt="Mastercard">
                <img src="imagenes/logos/PSE.ico" alt="PSE">
                <img src="imagenes/logos/SisteCredito.jpeg" alt="SisteCredito">
            </div>

    </div>
        <!-- Redes sociales -->
        <div class="footer-bottom">

            <div class="redes-sociales">

                <a href="https://www.facebook.com/profile.php?id=100085505333409" target="_blank">
                    <img src="imagenes/logos/facebook.png" alt="Facebook">
                </a>

                <a href="https://www.instagram.com/elementtiendas" target="_blank">
                    <img src="imagenes/logos/instagram.png" alt="Instagram">
                </a>

                <a href="https://www.tiktok.com/@elementtiendas" target="_blank">
                    <img src="imagenes/logos/tik-tok.png" alt="TikTok">
                </a>
            </div>

            <p class="footer-copy">© 2025 Copyrights, All Rights Reserved.</p>

            <div class="footer-divider"></div>

            <a href="https://wa.me/573125781377"
            target="_blank"
            class="footer-credit">
                Hecho por <span>Danny Moreno</span>
            </a>

        </div>
</footer>

<!-- USUARIO NO LOGUEADO -->
<?php if (!isset($_SESSION['id_usuario'])): ?>
<div class="login-modal" id="loginModal">
    <div class="login-box">
        <span class="close-login" id="closeLogin">&times;</span>

        <h2>Iniciar sesión</h2>

        <form class="login-form" action="login.php" method="POST">
            <input type="email" name="correo" placeholder="Correo electrónico" required>
            <input type="password" name="contrasena" placeholder="Contraseña" required>
            <button type="submit">Ingresar</button>
        </form>

        <a href="registro.php?login=true">Registrarme</a>
    </div>
</div>
<?php endif; ?>

</body>

<script type="module">
import { initializeApp } from "https://www.gstatic.com/firebasejs/10.12.0/firebase-app.js";
import { getAuth, GoogleAuthProvider, signInWithPopup } 
from "https://www.gstatic.com/firebasejs/10.12.0/firebase-auth.js";

const firebaseConfig = {
    apiKey: "AIzaSyD3Gjjvh88hVcNW6jqHn0PJAIh7CaHxa_s",
    authDomain: "element-e8f03.firebaseapp.com",
    projectId: "element-e8f03",
    storageBucket: "element-e8f03.appspot.com",
    messagingSenderId: "856363717478",
    appId: "1:856363717478:web:eddd96b17717bfff96aea7"
};

const app = initializeApp(firebaseConfig);
const auth = getAuth(app);
const provider = new GoogleAuthProvider();

document.addEventListener('click', async (e) => {
    if (e.target.closest('#googleLogin')) {
        try {
            const result = await signInWithPopup(auth, provider);
            const token = await result.user.getIdToken();

            const res = await fetch('google-login.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ token })
            });

            const text = await res.text();
            console.log('RESPUESTA PHP:', text);
            const data = JSON.parse(text);

            if (data.success) {
                location.reload();
            } else {
                alert(data.message);
            }

        } catch (err) {
            console.error(err);
            alert('Error con Google');
        }
    }
});
</script>

<script>
const searchBox = document.querySelector('.search-box');
const searchIcon = document.querySelector('.search-icon');
const loginBtn = document.getElementById("btn-login");
const loginModal = document.getElementById("loginModal");
const closeLogin = document.getElementById("closeLogin");

// =========================
//  BUSCADOR
// =========================
searchIcon?.addEventListener("click", () => {
    searchBox?.classList.toggle("active");
});

document.addEventListener("click", (e) => {
    if (
        searchBox &&
        !searchBox.contains(e.target) &&
        !searchIcon.contains(e.target)
    ) {
        searchBox.classList.remove("active");
    }
});

// =========================
// LOGIN / PANEL USUARIO
// =========================
if (loginBtn) {
    loginBtn.addEventListener("click", (e) => {
        e.preventDefault();
        e.stopPropagation();

        if (loginModal) {
            loginModal.classList.add("active");
        }
    });
}

// =========================
// CERRAR MODAL LOGIN
// =========================
closeLogin?.addEventListener("click", () => {
    loginModal?.classList.remove("active");
});

loginModal?.addEventListener("click", (e) => {
    if (e.target === loginModal) {
        loginModal.classList.remove("active");
    }
});

// =========================
// FILTROS DE CATÁLOGO
// =========================
const filtroBtns = document.querySelectorAll(".filtro-btn");
const productos = document.querySelectorAll(".producto-card");

filtroBtns.forEach(btn => {
    btn.addEventListener("click", () => {
        filtroBtns.forEach(b => b.classList.remove("activo"));
        btn.classList.add("activo");

        const filtro = btn.dataset.filter;

        productos.forEach(card => {
            card.style.display = 
                filtro === "todo" || card.dataset.category === filtro
                ? "block"
                : "none";
        });
    });
});
</script>

</html>