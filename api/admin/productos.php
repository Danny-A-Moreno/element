<?php
session_start();

// Protección: solo admin puede acceder
if (!isset($_SESSION['id_usuario']) || strtolower($_SESSION['rol']) !== 'admin') {
    header('Location: ../index.php');
    exit();
}

require_once __DIR__ . '/../BaseDatos.php';
$db = new BaseDatos();

// ===== MANEJO DE ACCIONES =====

// ELIMINAR PRODUCTO (CON SOFT DELETE)
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = $_GET['delete'];
    
    try {
        // Verificar si el producto tiene pedidos
        $verificar = $db->query(
            "SELECT COUNT(*) as total 
             FROM pedido_items pi
             INNER JOIN productos_variante pv ON pi.id_variante = pv.id_variante
             WHERE pv.id_producto = :id",
            ['id' => $id]
        )->fetch();
        
        if ($verificar['total'] > 0) {
            // TIENE VENTAS - Solo desactivar (SOFT DELETE)
            $db->query("UPDATE productos SET estado = 'inactivo' WHERE id_producto = :id", ['id' => $id]);
            $db->query("UPDATE productos_variante SET estado = 'inactivo' WHERE id_producto = :id", ['id' => $id]);            
            $_SESSION['success'] = "Producto desactivado (tiene historial de ventas). No se puede eliminar permanentemente.";
            
            } else {
            // NO TIENE VENTAS - Eliminar físicamente
            
            // 1. Eliminar imágenes físicas del servidor
            $imagenes = $db->query(
                "SELECT nombre_archivo FROM producto_imagenes WHERE id_producto = :id",
                ['id' => $id]
            )->fetchAll();
            
            foreach ($imagenes as $img) {
                $rutaImagen = __DIR__ . '/../imagenes/productos/' . $img['nombre_archivo'];
                if (file_exists($rutaImagen)) {
                    unlink($rutaImagen);
                }
            }
            
            // 2. Eliminar registros de imágenes
            $db->query("DELETE FROM producto_imagenes WHERE id_producto = :id", ['id' => $id]);
            
            // 3. Eliminar variantes
            $db->query("DELETE FROM productos_variante WHERE id_producto = :id", ['id' => $id]);
            
            // 4. Eliminar producto
            $db->query("DELETE FROM productos WHERE id_producto = :id", ['id' => $id]);
            
            $_SESSION['success'] = "Producto eliminado completamente";
        }
        
    } catch (Exception $e) {
        $_SESSION['error'] = "Error al eliminar producto: " . $e->getMessage();
    }
    
    header('Location: productos.php');
    exit();
}

// CAMBIAR ESTADO DE PRODUCTO
if (isset($_GET['toggle_estado']) && is_numeric($_GET['toggle_estado'])) {
    $id = $_GET['toggle_estado'];
    
    $sql = "UPDATE productos SET estado = CASE 
                WHEN estado = 'activo' THEN 'inactivo' 
                ELSE 'activo' 
            END 
            WHERE id_producto = :id";
    
    $db->query($sql, ['id' => $id]);
    $_SESSION['success'] = "Estado actualizado";
    
    header('Location: productos.php');
    exit();
}

// ===== OBTENER DATOS =====

// Búsqueda y filtros
$busqueda = $_GET['buscar'] ?? '';
$filtroEstado = $_GET['estado'] ?? '';

$sql = "SELECT 
            p.id_producto,
            p.nombre,
            p.precio,
            p.estado,
            p.activo,
            p.fecha_creacion,
            COUNT(DISTINCT pv.id_variante) as total_variantes,
            COALESCE(SUM(pv.stock), 0) as stock_total
        FROM productos p
        LEFT JOIN productos_variante pv ON p.id_producto = pv.id_producto
        WHERE 1=1";

$params = [];

if ($busqueda) {
    $sql .= " AND p.nombre LIKE :busqueda";
    $params['busqueda'] = "%$busqueda%";
}

if ($filtroEstado) {
    $sql .= " AND p.estado = :estado";
    $params['estado'] = $filtroEstado;
}

$sql .= " GROUP BY p.id_producto ORDER BY p.fecha_creacion DESC";

$productos = $db->query($sql, $params)->fetchAll();

// Estadísticas rápidas
$totalProductos = count($productos);
$productosActivos = count(array_filter($productos, fn($p) => $p['estado'] === 'activo'));
$stockTotal = array_sum(array_column($productos, 'stock_total'));

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Productos - ELEMENT</title>
    <link rel="icon" type="image/png" href="../imagenes/logos/Element.ico">
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="dashboard.css">
    <link rel="stylesheet" href="productos.css">
</head>
<body>

<!-- HEADER -->
<header class="main-header">
    <div class="header-content">


        <nav class="nav-menu">
            <a href="dashboard.php" class="nav-link">Dashboard</a>
            <a href="productos.php" class="nav-link active">Productos</a>
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

<!-- CONTENIDO PRINCIPAL -->
<main class="admin-main">
    
    <!-- TÍTULO Y MENSAJES -->
    <div class="page-header">
        <h1>📦 Gestión de Productos</h1>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                ✓ <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error">
                ✗ <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- ESTADÍSTICAS RÁPIDAS -->
    <div class="quick-stats">
        <div class="stat-mini">
            <span class="stat-value"><?php echo $totalProductos; ?></span>
            <span class="stat-label">Total Productos</span>
        </div>
        <div class="stat-mini">
            <span class="stat-value"><?php echo $productosActivos; ?></span>
            <span class="stat-label">Activos</span>
        </div>
        <div class="stat-mini">
            <span class="stat-value"><?php echo number_format($stockTotal); ?></span>
            <span class="stat-label">Stock Total</span>
        </div>
    </div>

    <!-- BARRA DE ACCIONES -->
    <div class="actions-bar">
        <a href="crear_productos.php" class="btn btn-primary">
            ➕ Agregar Producto
        </a>
        
        <div class="filters">
            <form method="GET" class="filter-form">
                <input type="text" 
                       name="buscar" 
                       placeholder="Buscar producto..." 
                       value="<?php echo htmlspecialchars($busqueda); ?>"
                       class="search-input">
                
                <select name="estado" class="filter-select" onchange="this.form.submit()">
                    <option value="">Todos los estados</option>
                    <option value="activo" <?php echo $filtroEstado === 'activo' ? 'selected' : ''; ?>>Activos</option>
                    <option value="inactivo" <?php echo $filtroEstado === 'inactivo' ? 'selected' : ''; ?>>Inactivos</option>
                </select>
                
                <button type="submit" class="btn btn-secondary">Buscar</button>
                <?php if ($busqueda || $filtroEstado): ?>
                    <a href="productos.php" class="btn btn-secondary">Limpiar</a>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <!-- TABLA DE PRODUCTOS -->
    <div class="table-container">
        <?php if (empty($productos)): ?>
            <div class="empty-state">
                <p>📦 No hay productos registrados</p>
            </div>
        <?php else: ?>
            <table class="products-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Precio</th>
                        <th>Variantes</th>
                        <th>Stock</th>
                        <th>Estado</th>
                        <th>Fecha Creación</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($productos as $producto): ?>
                        <tr class="<?php echo $producto['estado'] === 'inactivo' ? 'row-inactive' : ''; ?>">
                            <td>#<?php echo $producto['id_producto']; ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($producto['nombre']); ?></strong>
                            </td>
                            <td>$<?php echo number_format($producto['precio'], 0); ?></td>
                            <td><?php echo $producto['total_variantes']; ?> variantes</td>
                            <td>
                                <span class="stock-badge <?php echo $producto['stock_total'] < 10 ? 'stock-low' : ''; ?>">
                                    <?php echo $producto['stock_total']; ?> unidades
                                </span>
                            </td>
                            <td>
                                <span class="badge badge-<?php echo $producto['estado']; ?>">
                                    <?php echo ucfirst($producto['estado']); ?>
                                </span>
                            </td>
                            <td><?php echo date('d/m/Y', strtotime($producto['fecha_creacion'])); ?></td>
                            <td>
                                <div class="action-buttons">
                                    <a href="producto_form.php?id=<?php echo $producto['id_producto']; ?>" 
                                       class="btn-icon" title="Editar">
                                        ✏️
                                    </a>
                                    <a href="producto_variantes.php?id=<?php echo $producto['id_producto']; ?>" 
                                       class="btn-icon" title="Gestionar variantes">
                                        🎨
                                    </a>
                                    <a href="?toggle_estado=<?php echo $producto['id_producto']; ?>" 
                                       class="btn-icon" 
                                       title="<?php echo $producto['estado'] === 'activo' ? 'Desactivar' : 'Activar'; ?>"
                                       onclick="return confirm('¿Cambiar estado del producto?')">
                                        <?php echo $producto['estado'] === 'activo' ? '👁️' : '🚫'; ?>
                                    </a>
                                    <a href="?delete=<?php echo $producto['id_producto']; ?>" 
                                       class="btn-icon btn-delete" 
                                       title="Eliminar"
                                       onclick="return confirm('¿Estás seguro de eliminar este producto? Si tiene ventas, solo se desactivará.')">
                                        🗑️
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

</main>

<script>
// Auto-ocultar alertas después de 5 segundos
setTimeout(() => {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        alert.style.opacity = '0';
        setTimeout(() => alert.remove(), 300);
    });
}, 5000);
</script>

</body>
</html>