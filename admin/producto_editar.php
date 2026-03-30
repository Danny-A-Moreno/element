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

// PROCESAR ACTUALIZACIÓN
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $precio = floatval($_POST['precio'] ?? 0);
    $estado = $_POST['estado'] ?? 'activo';
    
    // Validaciones
    if (empty($nombre)) {
        $_SESSION['error'] = "El nombre del producto es obligatorio";
    } elseif ($precio <= 0) {
        $_SESSION['error'] = "El precio debe ser mayor a 0";
    } else {
        try {
            $sql = "UPDATE productos 
                    SET nombre = :nombre, 
                        descripcion = :descripcion, 
                        precio = :precio, 
                        estado = :estado,
                        fecha_actualizacion = NOW()
                    WHERE id_producto = :id";
            
            $db->query($sql, [
                'nombre' => $nombre,
                'descripcion' => $descripcion,
                'precio' => $precio,
                'estado' => $estado,
                'id' => $id
            ]);
            
            $_SESSION['success'] = "Producto actualizado exitosamente";
            header("Location: productos.php");
            exit();
            
        } catch (Exception $e) {
            $_SESSION['error'] = "Error al actualizar: " . $e->getMessage();
        }
    }
}

// Obtener datos del producto
$sql = "SELECT * FROM productos WHERE id_producto = :id";
$producto = $db->query($sql, ['id' => $id])->fetch();

if (!$producto) {
    $_SESSION['error'] = "Producto no encontrado";
    header('Location: productos.php');
    exit();
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Producto - ELEMENT</title>
    <link rel="icon" type="image/png" href="../imagenes/Element.ico">
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="dashboard.css">
    <link rel="stylesheet" href="productos.css">
</head>
<body>

<!-- HEADER -->
<header class="main-header">
    <div class="header-content">
        <a href="../index.php" class="logo-link">
            <img src="../imagenes/Element.jpg" alt="Logo" class="logo">
        </a>

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
        <h1>✏️ Editar Producto</h1>
        <a href="productos.php" class="btn btn-secondary">← Volver a productos</a>
    </div>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-error">
            ✗ <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>

    <div class="form-container">
        <form method="POST" class="product-form-full">
            
            <div class="form-section">
                <h3>Información Básica</h3>
                
                <div class="form-group">
                    <label for="nombre">Nombre del Producto *</label>
                    <input type="text" id="nombre" name="nombre" required 
                           value="<?php echo htmlspecialchars($producto['nombre']); ?>">
                </div>

                <div class="form-group">
                    <label for="descripcion">Descripción</label>
                    <textarea id="descripcion" name="descripcion" rows="5"><?php echo htmlspecialchars($producto['descripcion'] ?? ''); ?></textarea>
                </div>
            </div>

            <div class="form-section">
                <h3>Precio y Estado</h3>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="precio">Precio *</label>
                        <input type="number" id="precio" name="precio" required 
                               step="0.01" min="0" 
                               value="<?php echo $producto['precio']; ?>">
                    </div>

                    <div class="form-group">
                        <label for="estado">Estado *</label>
                        <select id="estado" name="estado" required>
                            <option value="activo" <?php echo $producto['estado'] === 'activo' ? 'selected' : ''; ?>>Activo</option>
                            <option value="inactivo" <?php echo $producto['estado'] === 'inactivo' ? 'selected' : ''; ?>>Inactivo</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="form-actions-full">
                <a href="productos.php" class="btn btn-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary">💾 Guardar Cambios</button>
            </div>
        </form>

        <!-- SECCIÓN DE VARIANTES -->
        <div class="variants-section">
            <div class="section-header">
                <h3>🎨 Variantes del Producto</h3>
                <a href="producto_variantes.php?id=<?php echo $id; ?>" class="btn btn-primary">
                    Gestionar Variantes
                </a>
            </div>
        </div>
    </div>

</main>

<style>
.form-container {
    background: var(--medium-grey);
    border: 1px solid var(--border-grey);
    border-radius: 12px;
    padding: 2rem;
    max-width: 800px;
}

.product-form-full {
    display: flex;
    flex-direction: column;
    gap: 2rem;
}

.form-section {
    padding-bottom: 2rem;
    border-bottom: 1px solid var(--border-grey);
}

.form-section:last-of-type {
    border-bottom: none;
}

.form-section h3 {
    color: var(--primary-white);
    margin-bottom: 1.5rem;
    font-size: 1.3rem;
}

.form-actions-full {
    display: flex;
    gap: 1rem;
    justify-content: flex-end;
    padding-top: 1rem;
}

.variants-section {
    margin-top: 2rem;
    padding-top: 2rem;
    border-top: 2px solid var(--border-grey);
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.section-header h3 {
    color: var(--primary-white);
    font-size: 1.3rem;
}
</style>

</body>
</html>