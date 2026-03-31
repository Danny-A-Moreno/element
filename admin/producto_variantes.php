<?php
session_start();

// Protección: solo admin puede acceder
if (!isset($_SESSION['id_usuario']) || strtolower($_SESSION['rol']) !== 'admin') {
    header('Location: ../index.php');
    exit();
}

require_once __DIR__ . '/../BaseDatos.php';
$db = new BaseDatos();

// Obtener ID del producto
$id = $_GET['id'] ?? 0;

if (!$id || !is_numeric($id)) {
    $_SESSION['error'] = "Producto no válido";
    header('Location: productos.php');
    exit();
}

// ELIMINAR VARIANTE
if (isset($_GET['delete_variante']) && is_numeric($_GET['delete_variante'])) {
    $idVariante = $_GET['delete_variante'];
    
    try {
        // 1. Eliminar pedido_items que referencian esta variante
        $db->query("DELETE FROM pedido_items WHERE id_variante = :id", ['id' => $idVariante]);
        
        // 2. Eliminar la variante
        $db->query("DELETE FROM productos_variante WHERE id_variante = :id AND id_producto = :id_producto", [
            'id' => $idVariante,
            'id_producto' => $id
        ]);
        
        $_SESSION['success'] = "Variante eliminada correctamente";
    } catch (Exception $e) {
        $_SESSION['error'] = "Error al eliminar variante: " . $e->getMessage();
    }
    
    header("Location: producto_variantes.php?id=$id");
    exit();
}

// AGREGAR VARIANTE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['agregar_variante'])) {
    $color = trim($_POST['color'] ?? '');
    $talla = trim($_POST['talla'] ?? '');
    $stock = intval($_POST['stock'] ?? 0);
    $estadoVariante = $_POST['estado_variante'] ?? 'activo';
    
    if (empty($color) || empty($talla)) {
        $_SESSION['error'] = "Color y talla son obligatorios";
    } elseif ($stock < 0) {
        $_SESSION['error'] = "El stock no puede ser negativo";
    } else {
        try {
            // Verificar si ya existe esta combinación
            $sqlCheck = "SELECT COUNT(*) as existe FROM productos_variante 
                         WHERE id_producto = :id_producto 
                         AND color = :color 
                         AND talla = :talla";
            
            $existe = $db->query($sqlCheck, [
                'id_producto' => $id,
                'color' => $color,
                'talla' => $talla
            ])->fetch()['existe'];
            
            if ($existe > 0) {
                $_SESSION['error'] = "Ya existe una variante con ese color y talla";
            } else {
                $sql = "INSERT INTO productos_variante (id_producto, color, talla, stock, estado) 
                        VALUES (:id_producto, :color, :talla, :stock, :estado)";
                
                $db->query($sql, [
                    'id_producto' => $id,
                    'color' => $color,
                    'talla' => $talla,
                    'stock' => $stock,
                    'estado' => $estadoVariante
                ]);
                
                $_SESSION['success'] = "Variante agregada exitosamente";
            }
        } catch (Exception $e) {
            $_SESSION['error'] = "Error al agregar variante: " . $e->getMessage();
        }
    }
    
    header("Location: producto_variantes.php?id=$id");
    exit();
}

// ACTUALIZAR STOCK
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizar_stock'])) {
    $idVariante = intval($_POST['id_variante'] ?? 0);
    $nuevoStock = intval($_POST['nuevo_stock'] ?? 0);
    
    if ($nuevoStock < 0) {
        $_SESSION['error'] = "El stock no puede ser negativo";
    } else {
        try {
            $sql = "UPDATE productos_variante 
                    SET stock = :stock 
                    WHERE id_variante = :id AND id_producto = :id_producto";
            
            $db->query($sql, [
                'stock' => $nuevoStock,
                'id' => $idVariante,
                'id_producto' => $id
            ]);
            
            $_SESSION['success'] = "Stock actualizado";
        } catch (Exception $e) {
            $_SESSION['error'] = "Error al actualizar stock: " . $e->getMessage();
        }
    }
    
    header("Location: producto_variantes.php?id=$id");
    exit();
}

// Obtener datos del producto
$sqlProducto = "SELECT * FROM productos WHERE id_producto = :id";
$producto = $db->query($sqlProducto, ['id' => $id])->fetch();

if (!$producto) {
    $_SESSION['error'] = "Producto no encontrado";
    header('Location: productos.php');
    exit();
}

// Obtener variantes
$sqlVariantes = "SELECT * FROM productos_variante WHERE id_producto = :id ORDER BY color, talla";
$variantes = $db->query($sqlVariantes, ['id' => $id])->fetchAll();

// Calcular stock total
$stockTotal = array_sum(array_column($variantes, 'stock'));

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestionar Variantes - <?php echo htmlspecialchars($producto['nombre']); ?></title>
    <link rel="icon" type="image/png" href="../imagenes/Element.ico">
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="dashboard.css">
    <link rel="stylesheet" href="productos.css">
</head>
<body>

<!-- HEADER -->
<header class="main-header">
    <div class="header-content">>

        <nav class="nav-menu">
            <a href="dashboard.php" class="nav-link">Dashboard</a>
            <a href="productos.php" class="nav-link active">Productos</a>
            <a href="pedidos.php" class="nav-link">Pedidos</a>
            <a href="usuarios.php" class="nav-link">Usuarios</a>
        </nav>

        <div class="nav-icons">
            <div class="dropdown">
                <a href="#" class="icon-link">
                    <img src="../imagenes/profile.png" alt="Admin">
                </a>
                <div class="dropdown-content">
                    <a href="../index.php">Ver tienda</a>
                    <a href="../logout.php">Cerrar sesión</a>
                </div>
            </div>
        </div>
    </div>
</header>

<!-- CONTENIDO -->
<main class="admin-main">
    
    <div class="page-header">
        <div>
            <h1>🎨 Gestionar Variantes</h1>
            <p style="color: var(--text-grey); margin-top: 0.5rem;">
                Producto: <strong style="color: var(--primary-white);"><?php echo htmlspecialchars($producto['nombre']); ?></strong>
            </p>
        </div>
        <a href="productos.php" class="btn btn-secondary">← Volver a productos</a>
    </div>

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

    <!-- ESTADÍSTICAS -->
    <div class="quick-stats">
        <div class="stat-mini">
            <span class="stat-value"><?php echo count($variantes); ?></span>
            <span class="stat-label">Variantes</span>
        </div>
        <div class="stat-mini">
            <span class="stat-value"><?php echo $stockTotal; ?></span>
            <span class="stat-label">Stock Total</span>
        </div>
        <div class="stat-mini">
            <span class="stat-value">$<?php echo number_format($producto['precio'], 0); ?></span>
            <span class="stat-label">Precio Base</span>
        </div>
    </div>

    <!-- FORMULARIO AGREGAR VARIANTE -->
    <div class="variant-form-container">
        <h2>➕ Agregar Nueva Variante</h2>
        <form method="POST" class="variant-form">
            <input type="hidden" name="agregar_variante" value="1">
            
            <div class="form-row">
                <div class="form-group">
                    <label for="color">Color *</label>
                    <input type="text" id="color" name="color" required 
                           placeholder="Ej: Azul, Negro, Rojo">
                </div>

                <div class="form-group">
                    <label for="talla">Talla *</label>
                    <input type="text" id="talla" name="talla" required 
                           placeholder="Ej: S, M, L, XL">
                </div>

                <div class="form-group">
                    <label for="stock">Stock Inicial *</label>
                    <input type="number" id="stock" name="stock" required 
                           min="0" value="0">
                </div>

                <div class="form-group">
                    <label for="estado_variante">Estado</label>
                    <select id="estado_variante" name="estado_variante">
                        <option value="activo">Activo</option>
                        <option value="inactivo">Inactivo</option>
                    </select>
                </div>
            </div>

            <button type="submit" class="btn btn-primary">Agregar Variante</button>
        </form>
    </div>

    <!-- TABLA DE VARIANTES -->
    <div class="table-container">
        <h2 style="padding: 1.5rem 1.5rem 0; color: var(--primary-white);">Variantes Existentes</h2>
        
        <?php if (empty($variantes)): ?>
            <div class="empty-state">
                <p>🎨 No hay variantes creadas para este producto</p>
                <p style="color: var(--text-grey); font-size: 0.9rem;">Agrega variantes con diferentes colores, tallas y stock</p>
            </div>
        <?php else: ?>
            <table class="products-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Color</th>
                        <th>Talla</th>
                        <th>Stock</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($variantes as $variante): ?>
                        <tr class="<?php echo $variante['estado'] === 'inactivo' ? 'row-inactive' : ''; ?>">
                            <td>#<?php echo $variante['id_variante']; ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($variante['color']); ?></strong>
                            </td>
                            <td><?php echo htmlspecialchars($variante['talla']); ?></td>
                            <td>
                                <form method="POST" style="display: inline-flex; gap: 0.5rem; align-items: center;">
                                    <input type="hidden" name="actualizar_stock" value="1">
                                    <input type="hidden" name="id_variante" value="<?php echo $variante['id_variante']; ?>">
                                    <input type="number" 
                                           name="nuevo_stock" 
                                           value="<?php echo $variante['stock']; ?>" 
                                           min="0"
                                           style="width: 80px; padding: 0.4rem; background: var(--dark-grey); border: 1px solid var(--border-grey); border-radius: 4px; color: var(--primary-white);">
                                    <button type="submit" class="btn-icon" title="Actualizar">💾</button>
                                </form>
                            </td>
                            <td>
                                <span class="badge badge-<?php echo $variante['estado']; ?>">
                                    <?php echo ucfirst($variante['estado']); ?>
                                </span>
                            </td>
                            <td>
                                <a href="?id=<?php echo $id; ?>&delete_variante=<?php echo $variante['id_variante']; ?>" 
                                   class="btn-icon btn-delete" 
                                   title="Eliminar"
                                   onclick="return confirm('¿Eliminar esta variante?')">
                                    🗑️
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

</main>

<style>
.variant-form-container {
    background: var(--medium-grey);
    border: 1px solid var(--border-grey);
    border-radius: 12px;
    padding: 2rem;
    margin-bottom: 2rem;
}

.variant-form-container h2 {
    color: var(--primary-white);
    margin-bottom: 1.5rem;
    font-size: 1.4rem;
}

.variant-form {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.page-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 2rem;
}
</style>

</body>
</html>