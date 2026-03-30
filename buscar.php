<?php
session_start();
require_once __DIR__ . '/BaseDatos.php';
$db = new BaseDatos();

// Obtener parámetros de búsqueda
$busqueda = trim($_GET['q'] ?? '');
$categoria = trim($_GET['categoria'] ?? '');

// Construir query SQL con búsqueda inteligente
$sql = "SELECT DISTINCT
            p.id_producto,
            p.nombre,
            p.precio,
            p.descripcion,
            (SELECT nombre_archivo FROM producto_imagenes WHERE id_producto = p.id_producto AND es_principal = 1 LIMIT 1) as imagen_principal
        FROM productos p
        WHERE p.estado = 'activo'";

$params = [];

// BÚSQUEDA POR TEXTO (en nombre y descripción)
if (!empty($busqueda)) {
    $sql .= " AND (
        LOWER(p.nombre) LIKE LOWER(:busqueda1) OR 
        LOWER(p.descripcion) LIKE LOWER(:busqueda2)
    )";
    $params['busqueda1'] = "%$busqueda%";
    $params['busqueda2'] = "%$busqueda%";
}

// FILTRO POR CATEGORÍA (búsqueda en nombre/descripción)
if (!empty($categoria)) {
    $sql .= " AND (
        LOWER(p.nombre) LIKE LOWER(:categoria1) OR 
        LOWER(p.descripcion) LIKE LOWER(:categoria2)
    )";
    $params['categoria1'] = "%$categoria%";
    $params['categoria2'] = "%$categoria%";
}

$sql .= " ORDER BY p.fecha_creacion DESC";

$productos = $db->query($sql, $params)->fetchAll();

// Título dinámico según búsqueda
$titulo = "Todos los Productos";
if (!empty($busqueda) && !empty($categoria)) {
    $titulo = ucfirst($busqueda) . " - " . ucfirst($categoria);
} elseif (!empty($busqueda)) {
    $titulo = "Resultados para: " . ucfirst($busqueda);
} elseif (!empty($categoria)) {
    $titulo = "Ropa de " . ucfirst($categoria);
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $titulo; ?> - ELEMENT</title>
    <link rel="icon" type="image/png" href="imagenes/logos/Element.ico">
    <link rel="stylesheet" href="style.css">
    <style>
        .buscar-container {
            max-width: 1400px;
            margin: 2rem auto;
            padding: 0 2rem;
        }

        .buscar-header {
            margin-bottom: 2rem;
        }

        .buscar-header h1 {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }

        .buscar-info {
            color: #666;
            font-size: 1rem;
        }

        .productos-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 2rem;
        }

        .producto-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            cursor: pointer;
        }

        .producto-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
        }

        .producto-imagen {
            width: 100%;
            height: 350px;
            object-fit: cover;
            background: #f0f0f0;
        }

        .producto-info {
            padding: 1.5rem;
        }

        .producto-info h3 {
            font-size: 1.2rem;
            margin-bottom: 0.5rem;
            color: #333;
        }

        .producto-precio {
            font-size: 1.3rem;
            font-weight: 700;
            color: #000;
        }

        .producto-tallas {
            display: flex;
            gap: 0.5rem;
            margin-top: 1rem;
            flex-wrap: wrap;
        }

        .producto-tallas span {
            padding: 0.3rem 0.8rem;
            background: #f0f0f0;
            border-radius: 4px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: #666;
        }

        .empty-state h2 {
            font-size: 2rem;
            margin-bottom: 1rem;
        }

        .empty-state p {
            font-size: 1.1rem;
            margin-bottom: 2rem;
        }

        .btn-catalogo {
            display: inline-block;
            padding: 1rem 2rem;
            background: #000;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-catalogo:hover {
            background: #333;
        }

        .filtros-activos {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
        }

        .filtro-tag {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            background: #f0f0f0;
            border-radius: 20px;
            font-size: 0.9rem;
        }

        .filtro-tag a {
            color: #666;
            text-decoration: none;
            font-weight: 600;
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
            <a href="favoritos.php">Favoritos</a>
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

<main class="buscar-container">
    
    <div class="buscar-header">
        <h1><?php echo htmlspecialchars($titulo); ?></h1>
        <p class="buscar-info">
            <?php echo count($productos); ?> producto<?php echo count($productos) != 1 ? 's' : ''; ?> encontrado<?php echo count($productos) != 1 ? 's' : ''; ?>
        </p>
    </div>

    <!-- FILTROS ACTIVOS -->
    <?php if (!empty($busqueda) || !empty($categoria)): ?>
        <div class="filtros-activos">
            <?php if (!empty($busqueda)): ?>
                <div class="filtro-tag">
                    Búsqueda: <strong><?php echo htmlspecialchars($busqueda); ?></strong>
                    <a href="buscar.php?categoria=<?php echo htmlspecialchars($categoria); ?>">✕</a>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($categoria)): ?>
                <div class="filtro-tag">
                    Categoría: <strong><?php echo htmlspecialchars(ucfirst($categoria)); ?></strong>
                    <a href="buscar.php?q=<?php echo htmlspecialchars($busqueda); ?>">✕</a>
                </div>
            <?php endif; ?>
            
            <div class="filtro-tag">
                <a href="catalogo.php">Limpiar todos los filtros</a>
            </div>
        </div>
    <?php endif; ?>

    <!-- PRODUCTOS -->
    <?php if (empty($productos)): ?>
        <div class="empty-state">
            <h2>No se encontraron productos</h2>
            <p>
                <?php if (!empty($busqueda)): ?>
                    No hay productos que coincidan con "<?php echo htmlspecialchars($busqueda); ?>"
                <?php elseif (!empty($categoria)): ?>
                    No hay productos en la categoría "<?php echo htmlspecialchars($categoria); ?>"
                <?php else: ?>
                    No hay productos disponibles en este momento
                <?php endif; ?>
            </p>
            <a href="catalogo.php" class="btn-catalogo">Ver todo el catálogo</a>
        </div>
    <?php else: ?>
        <div class="productos-grid">
            <?php foreach ($productos as $producto): ?>
                <?php
                // Obtener tallas disponibles
                $tallas = $db->query(
                    "SELECT DISTINCT talla FROM productos_variante 
                     WHERE id_producto = :id AND estado = 'activo' AND stock > 0
                     ORDER BY FIELD(talla, 'XS', 'S', 'M', 'L', 'XL', 'XXL')",
                    ['id' => $producto['id_producto']]
                )->fetchAll();
                ?>
                
                <a href="producto_detalle.php?id=<?php echo $producto['id_producto']; ?>" style="text-decoration: none; color: inherit;">
                    <div class="producto-card">
                        <?php if ($producto['imagen_principal']): ?>
                            <img src="imagenes/productos/<?php echo htmlspecialchars($producto['imagen_principal']); ?>" 
                                 alt="<?php echo htmlspecialchars($producto['nombre']); ?>" 
                                 class="producto-imagen">
                        <?php else: ?>
                            <div class="producto-imagen" style="display: flex; align-items: center; justify-content: center; color: #999;">
                                Sin imagen
                            </div>
                        <?php endif; ?>
                        
                        <div class="producto-info">
                            <h3><?php echo htmlspecialchars($producto['nombre']); ?></h3>
                            <p class="producto-precio">$<?php echo number_format($producto['precio'], 0, ',', '.'); ?></p>
                            
                            <?php if (!empty($tallas)): ?>
                                <div class="producto-tallas">
                                    <?php foreach ($tallas as $talla): ?>
                                        <span><?php echo $talla['talla']; ?></span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</main>

</body>
</html>