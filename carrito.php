<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/BaseDatos.php';
$db = new BaseDatos();

$items = [];
$total = 0;

// ===== OBTENER ITEMS DEL CARRITO =====
if (isset($_SESSION['id_usuario'])) {
    // Usuario logueado - obtener de BD
    $id_usuario = $_SESSION['id_usuario'];
    
    $sql = "SELECT 
                ic.id_item,
                ic.cantidad,
                ic.precio_unitario,
                pv.id_variante,
                pv.color,
                pv.talla,
                pv.stock,
                p.id_producto,
                p.nombre as nombre_producto,
                p.precio,
                (SELECT nombre_archivo 
                 FROM producto_imagenes 
                 WHERE id_producto = p.id_producto 
                 AND es_principal = 1 
                 LIMIT 1) as imagen
            FROM item_carrito ic
            INNER JOIN carrito c ON ic.id_carrito = c.id_carrito
            INNER JOIN productos_variante pv ON ic.id_variante = pv.id_variante
            INNER JOIN productos p ON pv.id_producto = p.id_producto
            WHERE c.id_usuario = :usuario 
            AND c.estado = 'activo'
            AND pv.estado = 'activo'
            AND p.estado = 'activo'
            ORDER BY ic.id_item DESC";
    
    $items = $db->query($sql, ['usuario' => $id_usuario])->fetchAll();
    
} else {
    // Usuario no logueado - carrito temporal en sesión
    if (isset($_SESSION['carrito_temporal']) && !empty($_SESSION['carrito_temporal'])) {
        foreach ($_SESSION['carrito_temporal'] as $item) {
            // Obtener info actualizada de la variante
            $sqlVariante = "SELECT 
                                pv.id_variante,
                                pv.color,
                                pv.talla,
                                pv.stock,
                                p.id_producto,
                                p.nombre as nombre_producto,
                                p.precio,
                                (SELECT nombre_archivo 
                                 FROM producto_imagenes 
                                 WHERE id_producto = p.id_producto 
                                 AND es_principal = 1 
                                 LIMIT 1) as imagen
                            FROM productos_variante pv
                            INNER JOIN productos p ON pv.id_producto = p.id_producto
                            WHERE pv.id_variante = :id 
                            AND pv.estado = 'activo'
                            AND p.estado = 'activo'";
            
            $variante = $db->query($sqlVariante, ['id' => $item['id_variante']])->fetch();
            
            if ($variante) {
                $items[] = [
                    'id_item' => 'temp_' . $item['id_variante'],
                    'id_variante' => $item['id_variante'],
                    'cantidad' => $item['cantidad'],
                    'precio_unitario' => $variante['precio'],
                    'color' => $variante['color'],
                    'talla' => $variante['talla'],
                    'stock' => $variante['stock'],
                    'id_producto' => $variante['id_producto'],
                    'nombre_producto' => $variante['nombre_producto'],
                    'precio' => $variante['precio'],
                    'imagen' => $variante['imagen']
                ];
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carrito de Compras - ELEMENT</title>
    <link rel="icon" type="image/png" href="imagenes/logos/Element.ico">
    <style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}
/* ===========================
   CARRITO DE COMPRAS
=========================== */
/* ------------ HEADER GENERAL ------------ */
.main-header {
    width: 100%;
    background: #0a0a0a;
    padding: 12px 25px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    position: sticky;
    top: 0;
    z-index: 1000;
}

.header-content {
    max-width: 1300px;
    margin: auto;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

/* ------------ LOGO ------------ */
.logo-link {
    display: flex;
    align-items: center;
}

.logo {
    height: 55px;
    width: auto;
    cursor: pointer;
}

/* ------------ MENU  ------------ */
.nav-menu {
    display: flex;
    align-items: center;
    gap: 25px;
    margin-left: 20px; 
}

.nav-link {
    text-decoration: none;
    font-size: 16px;
    color: #ffffff;
    font-weight: 500;
    position: relative;
    padding: 5px 0;
    transition: color .2s ease;
}

.nav-link:hover {
    color: rgb(150, 143, 143);
}

/* ------------ DESPLEGABLE (ÚNICO Y CORRECTO) ------------ */
.dropdown {
    position: relative;
}

.dropdown-content {
    position: absolute;
    top: 120%;
    right: 0;
    background: #0a0a0a;
    min-width: 180px;
    border-radius: 6px;
    padding: 10px 0;
    box-shadow: 0 4px 12px rgba(255, 255, 255, 0.15);

    display: none;
    opacity: 0;
    visibility: hidden;

    transition: opacity .25s ease, visibility .25s ease;
    z-index: 3000;
}

.dropdown:hover .dropdown-content {
    display: block;
    opacity: 1;
    visibility: visible;
}

.dropdown-content a {
    display: block;
    padding: 10px 15px;
    text-decoration: none;
    font-size: 15px;
    color: #fffcfc;
    white-space: nowrap;
}

.dropdown-content a:hover {
    background: #646161;
}

/* ------------ ICONOS A LA DERECHA ------------ */
.nav-icons {
    display: flex;
    align-items: center;
    gap: 18px;
}

.icon-link img {
    height: 28px; 
    width: auto;
    cursor: pointer;
    transition: transform .2s ease;
}

.icon-link img:hover {
    transform: scale(1.1);
}

/* ------------ RESPONSIVE ------------ */
@media (max-width: 768px) {
    .nav-menu {
        gap: 15px;
    }
    .nav-link {
        font-size: 14px;
    }
    .logo {
        height: 45px;
    }
    .icon-link img {
        height: 24px;
    }
}

/* ---------------- BUSCADOR ---------------- */

.search-box {
    position: relative;
    display: flex;
    align-items: center;
    transition: all .3s ease;
}

.search-icon {
    height: 26px;
    width: auto;
    cursor: pointer;
    transition: transform .2s ease;
}

.search-icon:hover {
    transform: scale(1.1);
}

/* Input oculto inicialmente */
.search-input {
    width: 0;
    opacity: 0;
    border: none;
    outline: none;
    margin-left: 10px;

    /* Estilo del texto */
    color: white;
    font-size: 15px;

    /* Fondo transparente */
    background: transparent;

    border-bottom: 1px solid white;
    padding: 4px 0;

    transition: width .35s ease, opacity .25s ease;
}

/* Cuando está activo */
.search-box.active .search-input {
    width: 180px;    
    opacity: 1;
}

.carrito-main {
    max-width: 900px;
    margin: 2rem auto;
    padding: 0 2rem;
    min-height: 60vh;
}

.carrito-items-section {
    background: #fff;
    padding: 2rem;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    margin-bottom: 2rem;
}

.carrito-titulo {
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 2rem;
    color: #000;
}

/* ── Carrito vacío ── */
.carrito-vacio {
    text-align: center;
    padding: 4rem 2rem;
    color: #666;
}

.carrito-vacio h2 {
    font-size: 1.5rem;
    margin-bottom: 1rem;
}

.carrito-vacio a {
    display: inline-block;
    margin-top: 1rem;
    padding: 1rem 2rem;
    background: #000;
    color: #fff;
    text-decoration: none;
    border-radius: 8px;
    font-weight: 600;
    transition: all 0.3s ease;
}

.carrito-vacio a:hover {
    background: #333;
    transform: translateY(-2px);
}

/* ── Items ── */
.carrito-item {
    display: grid;
    grid-template-columns: 120px 1fr auto;
    gap: 1.5rem;
    padding: 1.5rem;
    border-bottom: 1px solid #e0e0e0;
    transition: background 0.2s ease;
}

.carrito-item:hover {
    background: #f9f9f9;
}

.carrito-item:last-child {
    border-bottom: none;
}

.carrito-img {
    width: 120px;
    height: 120px;
    object-fit: cover;
    border-radius: 8px;
    background: #f5f5f5;
}

.carrito-info {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.carrito-info h4 {
    font-size: 1.1rem;
    font-weight: 600;
    color: #000;
    margin: 0;
}

.variante-info {
    font-size: 0.9rem;
    color: #666;
}

.precio-unitario {
    font-size: 1.1rem;
    font-weight: 600;
    color: #000;
    margin-top: 0.5rem;
}

/* ── Cantidad ── */
.cantidad-control {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-top: 1rem;
}

.cantidad-btn {
    width: 35px;
    height: 35px;
    border: 1px solid #ddd;
    background: #fff;
    border-radius: 6px;
    cursor: pointer;
    font-size: 1.2rem;
    font-weight: 700;
    transition: all 0.2s ease;
}

.cantidad-btn:hover {
    background: #f5f5f5;
    border-color: #000;
}

.cantidad-valor {
    width: 50px;
    text-align: center;
    font-weight: 600;
    font-size: 1rem;
}

.stock-warning {
    font-size: 0.85rem;
    color: #ff9800;
    margin-top: 0.5rem;
}

/* ── Acciones por item ── */
.carrito-acciones {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 1rem;
}

.subtotal {
    font-size: 1.3rem;
    font-weight: 700;
    color: #000;
}

.btn-eliminar {
    padding: 0.5rem 1rem;
    border: 1px solid #ff5252;
    background: #fff;
    color: #ff5252;
    border-radius: 6px;
    cursor: pointer;
    font-size: 0.9rem;
    font-weight: 600;
    transition: all 0.3s ease;
}

.btn-eliminar:hover {
    background: #ff5252;
    color: #fff;
}

/* ── Resumen del pedido ── */
.carrito-resumen {
    background: #fff;
    padding: 2rem;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    border: 1px solid #e8e8e8;
}

.resumen-titulo {
    font-size: 1.2rem;
    font-weight: 600;
    margin: 0 0 1.25rem;
    color: #000;
}

.resumen-linea {
    display: flex;
    justify-content: space-between;
    padding: 0.65rem 0;
    font-size: 0.95rem;
    color: #666;
    border-bottom: 1px solid #f0f0f0;
}

.resumen-linea.total {
    border-bottom: none;
    border-top: 2px solid #e0e0e0;
    margin-top: 0.5rem;
    padding-top: 1rem;
    font-size: 1.2rem;
    font-weight: 700;
    color: #000;
}

.btn-checkout {
    width: 100%;
    padding: 1rem;
    background: #000;
    color: #fff;
    border: none;
    border-radius: 8px;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    margin-top: 1.25rem;
    letter-spacing: 0.03em;
}

.btn-checkout:hover {
    background: #222;
    transform: translateY(-2px);
}

.btn-checkout:disabled {
    background: #ccc;
    cursor: not-allowed;
    transform: none;
}

.btn-seguir-comprando {
    width: 100%;
    padding: 0.85rem;
    background: #fff;
    color: #000;
    border: 1px solid #ddd;
    border-radius: 8px;
    font-size: 0.95rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s ease;
    margin-top: 0.75rem;
    text-decoration: none;
    display: block;
    text-align: center;
    box-sizing: border-box;
}

.btn-seguir-comprando:hover {
    background: #f5f5f5;
    border-color: #bbb;
}

/* ── Aviso login ── */
.mensaje-login {
    background: #fff3cd;
    border: 1px solid #ffc107;
    color: #856404;
    padding: 1rem;
    border-radius: 8px;
    margin-bottom: 1.5rem;
    text-align: center;
}

.mensaje-login a {
    color: #856404;
    font-weight: 600;
    text-decoration: underline;
}

/* ── Responsive ── */
@media (max-width: 968px) {
    .carrito-main {
        padding: 1rem;
    }

    .carrito-item {
        grid-template-columns: 80px 1fr;
        gap: 1rem;
    }

    .carrito-acciones {
        grid-column: 1 / -1;
        flex-direction: row;
        justify-content: space-between;
        align-items: center;
    }

    .carrito-img {
        width: 80px;
        height: 80px;
    }
}
    </style>
</head>
<body>

<!-- ===== HEADER ===== -->
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
    </div>
</header>

<!-- ===== CONTENIDO PRINCIPAL ===== -->
<div class="carrito-main">
    
    <!-- ===== ITEMS DEL CARRITO ===== -->
    <div class="carrito-items-section">
        <h1 class="carrito-titulo">Tu Carrito</h1>

        <?php if (!isset($_SESSION['id_usuario'])): ?>
            <div class="mensaje-login">
                ⚠️ <a href="login.php">Inicia sesión</a> para poder finalizar tu compra
            </div>
        <?php endif; ?>

        <?php if (empty($items)): ?>
            <div class="carrito-vacio">
                <h2>Tu carrito está vacío</h2>
                <p>¡Agrega productos para comenzar tu compra!</p>
                <a href="catalogo.php">Ver Catálogo</a>
            </div>
        <?php else: ?>
            <?php foreach ($items as $item): 
                $subtotal = $item['precio_unitario'] * $item['cantidad'];
                $total += $subtotal;
                $esCarritoTemporal = strpos($item['id_item'], 'temp_') === 0;
            ?>
                <div class="carrito-item">
                    <img 
                        src="imagenes/productos/<?php echo htmlspecialchars($item['imagen'] ?: 'placeholder.png'); ?>" 
                        alt="<?php echo htmlspecialchars($item['nombre_producto']); ?>"
                        class="carrito-img"
                    >

                    <div class="carrito-info">
                        <h4><?php echo htmlspecialchars($item['nombre_producto']); ?></h4>
                        <p class="variante-info">
                            Color: <strong><?php echo htmlspecialchars($item['color']); ?></strong> | 
                            Talla: <strong><?php echo htmlspecialchars($item['talla']); ?></strong>
                        </p>
                        <p class="precio-unitario">
                            $<?php echo number_format($item['precio_unitario'], 0, ',', '.'); ?>
                        </p>

                        <?php if ($item['cantidad'] > $item['stock']): ?>
                            <p class="stock-warning">
                                ⚠️ Solo quedan <?php echo $item['stock']; ?> unidades disponibles
                            </p>
                        <?php endif; ?>

                        <?php if (!$esCarritoTemporal): ?>
                            <form class="cantidad-control" action="carrito-actualizar.php" method="POST">
                                <input type="hidden" name="id_item" value="<?php echo $item['id_item']; ?>">
                                <button type="submit" name="accion" value="menos" class="cantidad-btn">−</button>
                                <span class="cantidad-valor"><?php echo $item['cantidad']; ?></span>
                                <button type="submit" name="accion" value="mas" class="cantidad-btn">+</button>
                            </form>
                        <?php else: ?>
                            <div class="cantidad-control">
                                <span class="cantidad-valor">Cantidad: <?php echo $item['cantidad']; ?></span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="carrito-acciones">
                        <p class="subtotal">
                            $<?php echo number_format($subtotal, 0, ',', '.'); ?>
                        </p>
                        
                        <?php if (!$esCarritoTemporal): ?>
                            <form action="carrito-eliminar.php" method="POST">
                                <input type="hidden" name="id_item" value="<?php echo $item['id_item']; ?>">
                                <button type="submit" class="btn-eliminar" 
                                        onclick="return confirm('¿Eliminar este producto del carrito?')">
                                    Eliminar
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- ===== RESUMEN  ===== -->
    <?php if (!empty($items)): ?>
<div class="carrito-resumen">
    <p class="resumen-titulo">Resumen del pedido</p>

    <div class="resumen-linea">
        <span>Subtotal (<?php echo count($items); ?> producto<?php echo count($items) > 1 ? 's' : ''; ?>)</span>
        <span>$<?php echo number_format($total, 0, ',', '.'); ?></span>
    </div>

    <div class="resumen-linea">
        <span>Envío</span>
        <span style="font-style:italic; color:#999;">A calcular</span>
    </div>

    <div class="resumen-linea total">
        <span>Total</span>
        <span>$<?php echo number_format($total, 0, ',', '.'); ?></span>
    </div>

    <?php if (isset($_SESSION['id_usuario'])): ?>
        <button class="btn-checkout" onclick="location.href='checkout.php'">
            Proceder al pago
        </button>
    <?php else: ?>
        <button class="btn-checkout" onclick="location.href='login.php?redirect=checkout'">
            Iniciar sesión para continuar
        </button>
    <?php endif; ?>

    <a href="catalogo.php" class="btn-seguir-comprando">Seguir comprando</a>
</div>
<?php endif; ?>
</div>

</body>

<script>
const searchBox = document.querySelector('.search-box');
const searchIcon = document.querySelector('.search-icon');
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
</script>
</html>