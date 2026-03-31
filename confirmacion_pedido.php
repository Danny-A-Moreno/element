<?php
session_start();

if (!isset($_SESSION['id_usuario'])) {
    header('Location: index.php');
    exit();
}

require_once __DIR__ . '/BaseDatos.php';
$db = new BaseDatos();

$idPedido = $_GET['id'] ?? 0;

if (!$idPedido) {
    header('Location: index.php');
    exit();
}

// Obtener datos del pedido
$pedido = $db->query("SELECT * FROM pedidos WHERE id_pedido = :id AND id_usuario = :id_usuario", [
    'id' => $idPedido,
    'id_usuario' => $_SESSION['id_usuario']
])->fetch();

if (!$pedido) {
    header('Location: index.php');
    exit();
}

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
    ['id' => $idPedido]
)->fetchAll();

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmación de Pedido - ELEMENT</title>
    <link rel="icon" type="image/png" href="imagenes/logos/Element.ico">
    <link rel="stylesheet" href="style.css">
    <style>
        .confirmation-container {
            max-width: 800px;
            margin: 3rem auto;
            padding: 2rem;
        }

        .confirmation-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            padding: 3rem;
            text-align: center;
        }

        .success-icon {
            width: 100px;
            height: 100px;
            background: #4CAF50;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 2rem;
            font-size: 3rem;
        }

        .confirmation-card h1 {
            font-size: 2rem;
            color: #333;
            margin-bottom: 1rem;
        }

        .confirmation-card p {
            font-size: 1.1rem;
            color: #666;
            margin-bottom: 2rem;
        }

        .order-number {
            font-size: 1.5rem;
            font-weight: 700;
            color: #d4af37;
            margin: 1rem 0;
        }

        .order-details {
            background: #f9f9f9;
            border-radius: 8px;
            padding: 2rem;
            margin: 2rem 0;
            text-align: left;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 0.8rem 0;
            border-bottom: 1px solid #eee;
        }

        .detail-row:last-child {
            border-bottom: none;
            font-weight: 700;
            font-size: 1.2rem;
        }

        .items-list {
            margin: 1.5rem 0;
        }

        .item {
            display: flex;
            justify-content: space-between;
            padding: 0.8rem 0;
            font-size: 0.95rem;
        }

        .btn-actions {
            display: flex;
            gap: 1rem;
            justify-content: center;
            margin-top: 2rem;
        }

        .btn {
            padding: 1rem 2rem;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: #d4af37;
            color: #000;
        }

        .btn-primary:hover {
            background: #c9a02d;
        }

        .btn-secondary {
            background: #f0f0f0;
            color: #333;
        }

        .btn-secondary:hover {
            background: #e0e0e0;
        }
    </style>
</head>
<body>

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


<main class="confirmation-container">
    <div class="confirmation-card">
        <div class="success-icon">✓</div>
        
        <h1>¡Pedido Confirmado!</h1>
        <p>Gracias por tu compra. Hemos recibido tu pedido correctamente.</p>
        
        <div class="order-number">
            Pedido #<?php echo $idPedido; ?>
        </div>
        
        <div class="order-details">
            <h3>Detalles del Pedido</h3>
            
            <div class="items-list">
                <?php foreach ($items as $item): ?>
                    <div class="item">
                        <span><?php echo htmlspecialchars($item['nombre_producto']); ?> (<?php echo $item['variante']; ?>) × <?php echo $item['cantidad']; ?></span>
                        <span>$<?php echo number_format($item['cantidad'] * $item['precio_unitario'], 0, ',', '.'); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="detail-row">
                <span>Método de Pago:</span>
                <span><?php 
                    $metodos = [
                        'contraentrega' => 'Pago Contraentrega',
                        'transferencia' => 'Transferencia/PSE',
                        'tarjeta' => 'Tarjeta de Crédito/Débito',
                        'sistecredito' => 'SisteCredito'
                    ];
                    echo $metodos[$pedido['metodo_pago']] ?? $pedido['metodo_pago'];
                ?></span>
            </div>
            
            <div class="detail-row">
                <span>Dirección de Envío:</span>
                <span><?php echo htmlspecialchars($pedido['direccion_envio']); ?></span>
            </div>
            
            <div class="detail-row">
                <span>Total:</span>
                <span>$<?php echo number_format($pedido['total'], 0, ',', '.'); ?></span>
            </div>
        </div>
        
        <p style="font-size: 0.95rem; color: #666;">
            Te hemos enviado un correo de confirmación a tu email.<br>
            Puedes seguir el estado de tu pedido en "Mis Pedidos".
        </p>
        
        <div class="btn-actions">
            <a href="mis-pedidos.php" class="btn btn-primary">Ver Mis Pedidos</a>
            <a href="index.php" class="btn btn-secondary">Volver al Inicio</a>
        </div>
    </div>
</main>

</body>
</html>