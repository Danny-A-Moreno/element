<?php
session_start();
require_once __DIR__ . '/helpers/session.php'; 
require_once __DIR__ . '/config.php'; 
require_once __DIR__ . '/BaseDatos.php';

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
            
            <a href="#quienes-somos" class="nav-link">Quienes somos</a>

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
            <a href="/element/admin/dashboard.php">Dashboard</a>
            <a href="/element/admin/productos.php">Productos</a>
            <a href="/element/admin/pedidos.php">Pedidos</a>
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

    <!-- SIEMPRE visible -->
    <a href="https://wa.me/c/573236133924" class="float-btn whatsapp" target="_blank">
        <img src="imagenes/logos/whatsapp-logo.ico" alt="WhatsApp">
    </a>
</div>

    <!-- Banner de inicio-->
<div class="hero-slider">

    <!-- SLIDE 1 -->
    <div class="slide" style="background-image: url('imagenes/productos/DSC_0932.JPG');">
        <div class="hero-content">
            <h4 class="subtitle">ELEMENT</h4>
            <h1 class="title">Moda que se adapta a ti</h1>
            <p class="description">¿No sabes qué outfit escoger hoy? Compra fácil, viste increíble.</p>
            <a href="catalogo.php" class="btn-hero">COMPRAR AHORA</a>
        </div>
    </div>

    <!-- SLIDE 2 -->
    <div class="slide" style="background-image: url('imagenes/productos/DSC_0804.JPG');">
        <div class="hero-content">
            <h4 class="subtitle">ELEMENT</h4>
            <h1 class="title">Nueva colección</h1>
            <p class="description">Lo mejor de la moda, hecho para ti.</p>
            <a href="catalogo.php" class="btn-hero">VER COLECCIÓN</a>
        </div>
    </div>

</div>

<!-- Carrusel de fotos de nueva coleccion-->
<section class="nvcoleccion">
    <h3 class="coleccion-sub">ELEMENT</h3>
    <h1 class="coleccion-title">NUEVA COLECCIÓN</h1>
</section>
<?php
$sql = "SELECT 
            p.id_producto,
            p.nombre,
            p.precio,
            (SELECT nombre_archivo FROM producto_imagenes WHERE id_producto = p.id_producto AND es_principal = 1 LIMIT 1) as imagen_principal
        FROM productos p
        WHERE p.estado = 'activo'
        ORDER BY p.fecha_creacion DESC
        LIMIT 10";

$productosCarrusel = $db->query($sql, [])->fetchAll();
?>

<div class="carousel-container">
    <button class="carousel-btn prev">&#10094;</button>

    <div class="carousel-track">
        <?php if (empty($productosCarrusel)): ?>
            <!-- Mensaje si no hay productos -->
            <div class="card" style="text-align: center; padding: 2rem;">
                <p>No hay productos disponibles aún</p>
            </div>
        <?php else: ?>
            <?php foreach ($productosCarrusel as $producto): ?>
                <?php
                // Obtener tallas disponibles
                $tallas = $db->query(
                    "SELECT DISTINCT talla FROM productos_variante 
                     WHERE id_producto = :id AND estado = 'activo' AND stock > 0
                     ORDER BY FIELD(talla, 'XS', 'S', 'M', 'L', 'XL', 'XXL')",
                    ['id' => $producto['id_producto']]
                )->fetchAll();
                ?>
                
                <!-- AHORA LA CARD COMPLETA ES UN ENLACE -->
                <a href="producto-detalle.php?id=<?php echo $producto['id_producto']; ?>" class="card card-link">
                    <?php if ($producto['imagen_principal']): ?>
                        <img src="imagenes/productos/<?php echo htmlspecialchars($producto['imagen_principal']); ?>" 
                             alt="<?php echo htmlspecialchars($producto['nombre']); ?>">
                    <?php else: ?>
                        <img src="imagenes/placeholder.png" alt="Sin imagen">
                    <?php endif; ?>
                    
                    <div class="card-sizes">
                        <?php if (empty($tallas)): ?>
                            <span>-</span>
                        <?php else: ?>
                            <?php foreach ($tallas as $talla): ?>
                                <span><?php echo $talla['talla']; ?></span>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    
                    <div class="card-info">
                        <h3><?php echo htmlspecialchars($producto['nombre']); ?></h3>
                        <p class="price">$<?php echo number_format($producto['precio'], 0, ',', '.'); ?></p>
                    </div>
                </a>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <button class="carousel-btn next">&#10095;</button>
</div>

<style>
/* ===== ESTILOS PARA HACER LAS CARDS CLICKEABLES ===== */
.card-link {
    text-decoration: none;
    color: inherit;
    display: block;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}


/* Mantener los colores originales del texto */
.card-link h3,
.card-link p,
.card-link .price {
    color: inherit;
}



/* Efecto de overlay "Ver Detalles" al hacer hover */
.card-link {
    position: relative;
}


.card-link:hover::before {
    opacity: 1;
}


/* Cursor pointer para indicar que es clickeable */
.card-link {
    cursor: pointer;
}

</style>

<section class="hero-banner" style="background-image: url('imagenes/productos/DSC_0819.JPG');">
    <div class="hero-overlay"></div>

    <div class="hero-banner-content">
        <h3>ELEMENT</h3>
        <h1>Que tu outfit no te limite</h1>
        <p>La ropa no cambia quién eres. Pero puede ayudarte a mostrarlo.</p>
        <a href="catalogo.php" class="hero-banner-btn">VER MÁS</a>
    </div>
</section>

<!-- SOBRE NOSOTROS -->
<section class="sobre-nosotros" id="quienes-somos">
    <div class="sn-imagenes">

        <img src="imagenes/productos/DSC_1102.JPG" 
             class="sobre-img-tall" 
             loading="lazy" decoding="async"
             width="900" height="1500">

        <img src="imagenes/productos/DSC_0865.JPG" 
             loading="lazy" decoding="async"
             width="900" height="1200">

        <img src="imagenes/productos/DSC_0831.JPG" 
             loading="lazy" decoding="async"
             width="900" height="1200">

        <img src="imagenes/productos/DSC_1192.JPG" 
             class="sobre-img-tall"
             loading="lazy" decoding="async"
             width="900" height="1500">

    </div>

    <div class="sn-texto">
        <h4 class="sn-sub">ELEMENT</h4>
        <h1 class="sn-title">Quiénes Somos</h1>

        <p>
            En ELEMENT, creemos que la comodidad no está reñida con el estilo. Buscamos crear prendas versátiles,
            auténticas y pensadas para moverse contigo. Diseñamos ropa que fluye, que respira, que te acompaña.
        </p>

        <p>
            Somos más que una marca: somos una comunidad que valora la libertad, el confort y la expresión personal.
            Bienvenido a un espacio donde el estilo se vive sin reglas.
        </p>
    </div>
</section>

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
   </div>
</div>

</body>
<!-- Java Script para animaciones y funciones -->
<script>
// =========================
//   VARIABLES PRINCIPALES
// =========================
const track = document.querySelector('.carousel-track');
const btnPrev = document.querySelector('.carousel-btn.prev');
const btnNext = document.querySelector('.carousel-btn.next');
const searchBox = document.querySelector('.search-box');
const searchIcon = document.querySelector('.search-icon');
const loginBtn = document.getElementById("btn-login");
const loginModal = document.getElementById("loginModal"); // solo existe si NO logueado
const userPanel = document.getElementById("userPanel");   // solo existe si logueado
const closeLogin = document.getElementById("closeLogin");
const scrollAmount = 260;
let autoScroll;


// =========================
//   MOVER CARRUSEL
// =========================
function moveCarousel(amount, manual = false) {
    if (!track) return;

    track.scrollBy({ left: amount, behavior: "smooth" });

    if (!manual) {
        setTimeout(() => {
            if (track.scrollLeft + track.clientWidth >= track.scrollWidth - 5) {
                track.scrollTo({ left: 0, behavior: "smooth" });
            }
        }, 400);
    }
}


// =========================
//   BOTONES CARRUSEL
// =========================
btnNext?.addEventListener("click", () => moveCarousel(scrollAmount, true));
btnPrev?.addEventListener("click", () => moveCarousel(-scrollAmount, true));


// =========================
//   AUTO-SCROLL
// =========================
function startAutoScroll() {
    autoScroll = setInterval(() => moveCarousel(scrollAmount), 45000);
}

function stopAutoScroll() {
    clearInterval(autoScroll);
}

if (track) {
    startAutoScroll();
    track.addEventListener('mouseenter', stopAutoScroll);
    track.addEventListener('mouseleave', startAutoScroll);
}


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
//  FIX MOBILE
// =========================
window.addEventListener("load", () => {
    if (window.innerWidth <= 768 && track) {
        track.scrollTo({ left: 0 });
    }
});


// =========================
// LOGIN / PANEL USUARIO
// =========================
if (loginBtn) {
    loginBtn.addEventListener("click", (e) => {
        e.preventDefault();
        e.stopPropagation(); // 🔑 CLAVE

        // Usuario NO logueado → modal
        if (loginModal) {
            loginModal.classList.add("active");
            return;
        }

        // Usuario logueado → panel
        if (userPanel) {
            userPanel.classList.toggle("active");
        }
    });
}



// =========================
// CERRAR MODAL LOGIN
// =========================
closeLogin?.addEventListener("click", () => {
    loginModal?.classList.remove("active");
});

// Click fuera del modal
loginModal?.addEventListener("click", (e) => {
    if (e.target === loginModal) {
        loginModal.classList.remove("active");
    }
});


// =========================
// CERRAR PANEL USUARIO
// =========================
document.addEventListener("click", (e) => {
    if (
        userPanel &&
        !userPanel.contains(e.target) &&
        !loginBtn.contains(e.target)
    ) {
        userPanel.classList.remove("active");
    }
});


// =========================
// ABRIR MODAL DESDE ?login=true
// =========================
document.addEventListener("DOMContentLoaded", () => {
    const params = new URLSearchParams(window.location.search);

    if (params.get("login") === "true" && loginModal) {
        loginModal.classList.add("active");
        document.body.style.overflow = "hidden";
        window.history.replaceState({}, document.title, window.location.pathname);
    }
});
</script>
<script type="module" src="public_html/js/firebase-init.js"></script>
<script type="module" src="public_html/js/google-auth.js"></script>
<script src="busqueda-instantanea.js"></script>

</html>