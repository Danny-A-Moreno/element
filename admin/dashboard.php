<?php
session_start();

// Protección: solo admin puede acceder
if (!isset($_SESSION['id_usuario']) || strtolower($_SESSION['rol']) !== 'admin') {
    header('Location: ../index.php');
    exit();
}

require_once __DIR__ . '/../BaseDatos.php';
$db = new BaseDatos();

// ===== ESTADÍSTICAS PRINCIPALES =====

// Total de usuarios registrados
$sqlUsuarios = "SELECT COUNT(*) as total FROM usuarios";
$resultUsuarios = $db->query($sqlUsuarios, []);
$totalUsuarios = $resultUsuarios->fetch()['total'];

// Total de productos
$sqlProductos = "SELECT COUNT(*) as total FROM productos";
$resultProductos = $db->query($sqlProductos, []);
$totalProductos = $resultProductos->fetch()['total'];

// Total de pedidos
$sqlPedidos = "SELECT COUNT(*) as total FROM pedidos";
$resultPedidos = $db->query($sqlPedidos, []);
$totalPedidos = $resultPedidos->fetch()['total'];

// Pedidos pendientes de envío
$sqlPendientes = "SELECT COUNT(*) as total FROM pedidos WHERE estado IN ('pendiente', 'procesando')";
$resultPendientes = $db->query($sqlPendientes, []);
$pedidosPendientes = $resultPendientes->fetch()['total'];

// Ventas totales (suma de todos los pedidos completados)
$sqlVentas = "SELECT COALESCE(SUM(total), 0) as total_ventas FROM pedidos WHERE estado = 'completado'";
$resultVentas = $db->query($sqlVentas, []);
$ventasTotales = $resultVentas->fetch()['total_ventas'];

// ===== TOP 10 PRODUCTOS MÁS VENDIDOS =====
$sqlTopProductos = "
    SELECT 
        p.id_producto,
        p.nombre,
        p.precio,
        COALESCE(SUM(pi.cantidad), 0) as total_vendido,
        COALESCE(SUM(pi.cantidad * pi.precio_unitario), 0) as ingresos_totales
    FROM productos p
    LEFT JOIN productos_variante pv ON p.id_producto = pv.id_producto
    LEFT JOIN pedido_items pi ON pv.id_variante = pi.id_variante
    LEFT JOIN pedidos ped ON pi.id_pedido = ped.id_pedido AND ped.estado = 'completado'
    GROUP BY p.id_producto, p.nombre, p.precio
    ORDER BY total_vendido DESC
    LIMIT 10
";
$topProductos = $db->query($sqlTopProductos, [])->fetchAll();

// ===== ÚLTIMOS PEDIDOS =====
$sqlUltimosPedidos = "
    SELECT 
        p.id_pedido,
        u.nombre as cliente,
        p.total,
        p.estado,
        p.fecha_creacion
    FROM pedidos p
    JOIN usuarios u ON p.id_usuario = u.id_usuario
    ORDER BY p.fecha_creacion DESC
    LIMIT 8
";
$ultimosPedidos = $db->query($sqlUltimosPedidos, [])->fetchAll();

// ===== PRODUCTOS CON STOCK BAJO =====
$sqlStockBajo = "
    SELECT 
        p.id_producto,
        p.nombre,
        COALESCE(SUM(pv.stock), 0) as stock_total
    FROM productos p
    LEFT JOIN productos_variante pv ON p.id_producto = pv.id_producto
    GROUP BY p.id_producto
    HAVING stock_total < 10
    ORDER BY stock_total ASC
    LIMIT 5
";
$stockBajo = $db->query($sqlStockBajo, [])->fetchAll();

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - ELEMENT</title>
    <link rel="icon" type="image/png" href="../imagenes/logos/Element.ico">
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="dashboard.css">
    <!-- Chart.js para gráficas -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

<!-- HEADER IGUAL AL PRINCIPAL -->
<header class="main-header">
    <div class="header-content">

        <nav class="nav-menu">
            <a href="dashboard.php" class="nav-link active">Dashboard</a>
            <a href="productos.php" class="nav-link">Productos</a>
            <a href="pedidos.php" class="nav-link">Pedidos</a>
            <a href="usuarios.php" class="nav-link">Usuarios</a>
        </nav>

        <div class="nav-icons">
            <div class="dropdown">
                <a href="#" class="icon-link">
                    <img src="../imagenes/logos/profile.png" alt="Admin">
                </a>
                <div class="dropdown-content">
                    <a href="../index.php">Ver tienda</a>
                    <a href="../logout.php">Cerrar sesión</a>
                </div>
            </div>
        </div>
    </div>
</header>

<!-- CONTENIDO DEL DASHBOARD -->
<main class="dashboard-main">
    
    <!-- BIENVENIDA -->
    <section class="welcome-section">
        <h1>Bienvenido, <?php echo htmlspecialchars($_SESSION['nombre']); ?></h1>
        <p>Panel de administración - ELEMENT</p>
    </section>

    <!-- TARJETAS DE ESTADÍSTICAS -->
    <section class="stats-grid">
        
        <div class="stat-card card-usuarios">
            <div class="stat-icon">👥</div>
            <div class="stat-info">
                <h3><?php echo number_format($totalUsuarios); ?></h3>
                <p>Usuarios Registrados</p>
            </div>
        </div>

        <div class="stat-card card-productos">
            <div class="stat-icon">📦</div>
            <div class="stat-info">
                <h3><?php echo number_format($totalProductos); ?></h3>
                <p>Productos Totales</p>
            </div>
        </div>

        <div class="stat-card card-pedidos">
            <div class="stat-icon">🛍️</div>
            <div class="stat-info">
                <h3><?php echo number_format($totalPedidos); ?></h3>
                <p>Pedidos Totales</p>
            </div>
        </div>

        <div class="stat-card card-ventas">
            <div class="stat-icon">💰</div>
            <div class="stat-info">
                <h3>$<?php echo number_format($ventasTotales, 0); ?></h3>
                <p>Ventas Totales</p>
            </div>
        </div>

        <div class="stat-card card-pendientes">
            <div class="stat-icon">⏳</div>
            <div class="stat-info">
                <h3><?php echo number_format($pedidosPendientes); ?></h3>
                <p>Pedidos Pendientes</p>
            </div>
        </div>

    </section>

    <!-- SECCIÓN PRINCIPAL -->
    <div class="dashboard-content">

        <!-- COLUMNA IZQUIERDA -->
        <div class="left-column">

            <!-- TOP 10 PRODUCTOS MÁS VENDIDOS -->
            <section class="dashboard-card">
                <h2>🔥 Top 10 Productos Más Vendidos</h2>
                <div class="top-products-list">
                    <?php if (empty($topProductos)): ?>
                        <p class="empty-state">No hay datos de ventas aún</p>
                    <?php else: ?>
                        <?php foreach ($topProductos as $index => $producto): ?>
                            <div class="product-item">
                                <span class="ranking">#<?php echo $index + 1; ?></span>
                                <div class="product-info">
                                    <strong><?php echo htmlspecialchars($producto['nombre']); ?></strong>
                                    <span class="product-stats">
                                        <?php echo $producto['total_vendido']; ?> vendidos • 
                                        $<?php echo number_format($producto['ingresos_totales'], 0); ?>
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>

            <!-- PRODUCTOS CON STOCK BAJO -->
            <section class="dashboard-card alert-card">
                <h2>⚠️ Productos con Stock Bajo</h2>
                <div class="stock-list">
                    <?php if (empty($stockBajo)): ?>
                        <p class="empty-state">✅ Todos los productos tienen stock suficiente</p>
                    <?php else: ?>
                        <?php foreach ($stockBajo as $producto): ?>
                            <div class="stock-item">
                                <div class="stock-info">
                                    <strong><?php echo htmlspecialchars($producto['nombre']); ?></strong>
                                    <span class="stock-warning">
                                        Stock: <?php echo $producto['stock_total']; ?> unidades
                                    </span>
                                </div>
                                <a href="productos.php?edit=<?php echo $producto['id_producto']; ?>" class="btn-small">
                                    Reponer
                                </a>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>

        </div>

        <!-- COLUMNA DERECHA -->
        <div class="right-column">

            <!-- ÚLTIMOS PEDIDOS -->
            <section class="dashboard-card">
                <h2>📋 Últimos Pedidos</h2>
                <div class="orders-table">
                    <?php if (empty($ultimosPedidos)): ?>
                        <p class="empty-state">No hay pedidos registrados</p>
                    <?php else: ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Cliente</th>
                                    <th>Total</th>
                                    <th>Estado</th>
                                    <th>Fecha</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($ultimosPedidos as $pedido): ?>
                                    <tr>
                                        <td>#<?php echo $pedido['id_pedido']; ?></td>
                                        <td><?php echo htmlspecialchars($pedido['cliente']); ?></td>
                                        <td>$<?php echo number_format($pedido['total'], 0); ?></td>
                                        <td>
                                            <span class="badge badge-<?php echo $pedido['estado']; ?>">
                                                <?php echo ucfirst($pedido['estado']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('d/m/Y', strtotime($pedido['fecha_creacion'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <a href="pedidos.php" class="btn-view-all">Ver todos los pedidos →</a>
                    <?php endif; ?>
                </div>
            </section>

        </div>

    </div>

</main>

</body>
</html>