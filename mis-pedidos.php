<?php
session_start();

if (!isset($_SESSION['id_usuario'])) {
    header('Location: index.php?login=true');
    exit();
}

require_once __DIR__ . '/BaseDatos.php';
$db = new BaseDatos();

// Obtener todos los pedidos del usuario
$pedidos = $db->query("
    SELECT * FROM pedidos 
    WHERE id_usuario = :id_usuario 
    ORDER BY fecha_creacion DESC",
    ['id_usuario' => $_SESSION['id_usuario']]
)->fetchAll();

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Pedidos - ELEMENT</title>
    <link rel="icon" type="image/png" href="imagenes/logos/Element.ico">
    <link rel="stylesheet" href="style.css">
    <style>
        .pedidos-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 2rem;
        }

        .pedidos-container h1 {
            font-size: 2.5rem;
            margin-bottom: 2rem;
        }

        .pedidos-grid {
            display: grid;
            gap: 1.5rem;
        }

        .pedido-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 2rem;
            transition: all 0.3s ease;
        }

        .pedido-card:hover {
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
        }

        .pedido-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #f0f0f0;
        }

        .pedido-number {
            font-size: 1.3rem;
            font-weight: 700;
        }

        .pedido-status {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
        }

        .status-pendiente {
            background: #fff3cd;
            color: #856404;
        }

        .status-procesando {
            background: #cfe2ff;
            color: #084298;
        }

        .status-completado {
            background: #d1e7dd;
            color: #0a3622;
        }

        .status-cancelado {
            background: #f8d7da;
            color: #721c24;
        }

        .pedido-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .info-item {
            display: flex;
            flex-direction: column;
        }

        .info-label {
            font-size: 0.85rem;
            color: #666;
            margin-bottom: 0.3rem;
        }

        .info-value {
            font-size: 1rem;
            font-weight: 600;
            color: #333;
        }

        .pedido-items {
            background: #f9f9f9;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem;
        }

        .item {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0;
            font-size: 0.9rem;
        }

        .btn-ver-detalle {
            display: inline-block;
            padding: 0.7rem 1.5rem;
            background: #d4af37;
            color: #000;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-ver-detalle:hover {
            background: #c9a02d;
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
        }

        .empty-state h2 {
            font-size: 2rem;
            color: #666;
            margin-bottom: 1rem;
        }

        .empty-state p {
            font-size: 1.1rem;
            color: #999;
            margin-bottom: 2rem;
        }

        .btn-primary {
            display: inline-block;
            padding: 1rem 2rem;
            background: #d4af37;
            color: #000;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 700;
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


<main class="pedidos-container">
    
    <h1>📦 Mis Pedidos</h1>

    <?php if (empty($pedidos)): ?>
        <div class="empty-state">
            <h2>No tienes pedidos aún</h2>
            <p>¡Empieza a comprar y disfruta de nuestros productos!</p>
            <a href="catalogo.php" class="btn-primary">Ver Catálogo</a>
        </div>
    <?php else: ?>
        <div class="pedidos-grid">
            <?php foreach ($pedidos as $pedido): ?>
                <?php
                // Obtener items del pedido
                $items = $db->query("
                    SELECT 
                        pi.*,
                        p.nombre as nombre_producto,
                        CONCAT(pv.color, ' / ', pv.talla) as variante
                    FROM pedido_items pi
                    JOIN productos_variante pv ON pi.id_variante = pv.id_variante
                    JOIN productos p ON pv.id_producto = p.id_producto
                    WHERE pi.id_pedido = :id",
                    ['id' => $pedido['id_pedido']]
                )->fetchAll();
                
                $estadoClass = 'status-' . $pedido['estado'];
                $estadoTexto = [
                    'pendiente' => 'Pendiente',
                    'procesando' => 'En Proceso',
                    'completado' => 'Completado',
                    'cancelado' => 'Cancelado'
                ];
                ?>
                
                <div class="pedido-card">
                    <div class="pedido-header">
                        <div class="pedido-number">Pedido #<?php echo $pedido['id_pedido']; ?></div>
                        <div class="pedido-status <?php echo $estadoClass; ?>">
                            <?php echo $estadoTexto[$pedido['estado']] ?? $pedido['estado']; ?>
                        </div>
                    </div>
                    
                    <div class="pedido-info">
                        <div class="info-item">
                            <span class="info-label">Fecha</span>
                            <span class="info-value"><?php echo date('d/m/Y', strtotime($pedido['fecha_creacion'])); ?></span>
                        </div>
                        
                        <div class="info-item">
                            <span class="info-label">Total</span>
                            <span class="info-value">$<?php echo number_format($pedido['total'], 0, ',', '.'); ?></span>
                        </div>
                        
                        <div class="info-item">
                            <span class="info-label">Método de Pago</span>
                            <span class="info-value"><?php 
                                $metodos = [
                                    'contraentrega' => 'Contraentrega',
                                    'transferencia' => 'Transferencia',
                                    'tarjeta' => 'Tarjeta',
                                    'sistecredito' => 'SisteCredito'
                                ];
                                echo $metodos[$pedido['metodo_pago']] ?? $pedido['metodo_pago'];
                            ?></span>
                        </div>
                    </div>
                    
                    <div class="pedido-items">
                        <strong style="display: block; margin-bottom: 0.5rem;">Productos:</strong>
                        <?php foreach ($items as $item): ?>
                            <div class="item">
                                <span><?php echo htmlspecialchars($item['nombre_producto']); ?> (<?php echo $item['variante']; ?>) × <?php echo $item['cantidad']; ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <a href="confirmacion_pedido.php?id=<?php echo $pedido['id_pedido']; ?>" class="btn-ver-detalle">
                        Ver Detalle
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</main>

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