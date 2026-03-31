<?php
session_start();

// Protección: solo admin puede acceder
if (!isset($_SESSION['id_usuario']) || strtolower($_SESSION['rol']) !== 'admin') {
    header('Location: ../index.php');
    exit();
}

require_once __DIR__ . '/../BaseDatos.php';
$db = new BaseDatos();

$idPedido = $_GET['id'] ?? 0;

if (!$idPedido) {
    header('Location: pedidos.php');
    exit();
}

// Obtener datos del pedido
$pedido = $db->query("
    SELECT 
        p.*,
        u.nombre as nombre_usuario,
        u.apellido as apellido_usuario,
        u.correo as correo_usuario
    FROM pedidos p
    INNER JOIN usuarios u ON p.id_usuario = u.id_usuario
    WHERE p.id_pedido = :id",
    ['id' => $idPedido]
)->fetch();

if (!$pedido) {
    $_SESSION['error'] = "Pedido no encontrado";
    header('Location: pedidos.php');
    exit();
}

// Obtener items del pedido
$items = $db->query("
    SELECT 
        pi.*,
        p.nombre as nombre_producto,
        pv.color,
        pv.talla,
        (SELECT nombre_archivo FROM producto_imagenes WHERE id_producto = p.id_producto AND es_principal = 1 LIMIT 1) as imagen
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
    <title>Detalle Pedido #<?php echo $idPedido; ?> - ELEMENT Admin</title>
    <link rel="icon" type="image/png" href="../imagenes/logos/Element.ico">
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="dashboard.css">
    <style>
        .detalle-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 2rem;
        }

        .detalle-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .detalle-header h1 {
            font-size: 2rem;
            color: #333;
        }

        .status-badge {
            padding: 0.8rem 1.5rem;
            border-radius: 25px;
            font-weight: 700;
            font-size: 1.1rem;
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

        .grid-detalle {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
        }

        .card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 2rem;
        }

        .card h2 {
            font-size: 1.5rem;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #f0f0f0;
        }

        .item-pedido {
            display: grid;
            grid-template-columns: 80px 1fr auto;
            gap: 1rem;
            padding: 1rem 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .item-pedido:last-child {
            border-bottom: none;
        }

        .item-img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
            background: #f5f5f5;
        }

        .item-info h4 {
            margin: 0 0 0.5rem 0;
            font-size: 1rem;
        }

        .item-info p {
            margin: 0;
            font-size: 0.9rem;
            color: #666;
        }

        .item-precio {
            text-align: right;
            font-weight: 700;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 0.8rem 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-label {
            font-weight: 600;
            color: #666;
        }

        .info-value {
            color: #333;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 1.5rem 0;
            margin-top: 1rem;
            border-top: 2px solid #333;
            font-size: 1.5rem;
            font-weight: 700;
        }

        .btn-volver {
            display: inline-block;
            padding: 0.8rem 1.5rem;
            background: #f0f0f0;
            color: #333;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-volver:hover {
            background: #e0e0e0;
        }

        .btn-cambiar-estado {
            padding: 0.8rem 1.5rem;
            background: #d4af37;
            color: #000;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-cambiar-estado:hover {
            background: #c9a02d;
        }

        @media (max-width: 968px) {
            .grid-detalle {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

<header class="main-header">
    <div class="header-content">

        <nav class="nav-menu">
            <a href="dashboard.php" class="nav-link">Dashboard</a>
            <a href="productos.php" class="nav-link">Productos</a>
            <a href="pedidos.php" class="nav-link active">Pedidos</a>
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

<main class="admin-main">
    <div class="detalle-container">
        
        <div class="detalle-header">
            <div>
                <a href="pedidos.php" class="btn-volver">← Volver a Pedidos</a>
                <h1>Pedido #<?php echo $idPedido; ?></h1>
            </div>
            <div class="status-badge status-<?php echo $pedido['estado']; ?>">
                <?php 
                $estados = [
                    'pendiente' => 'Pendiente',
                    'procesando' => 'En Proceso',
                    'completado' => 'Completado',
                    'cancelado' => 'Cancelado'
                ];
                echo $estados[$pedido['estado']] ?? $pedido['estado'];
                ?>
            </div>
        </div>

        <div class="grid-detalle">
            
            <!-- ITEMS DEL PEDIDO -->
            <div class="card">
                <h2>Productos del Pedido</h2>
                
                <?php foreach ($items as $item): ?>
                    <div class="item-pedido">
                        <img src="../imagenes/productos/<?php echo htmlspecialchars($item['imagen'] ?: 'placeholder.png'); ?>" 
                             alt="<?php echo htmlspecialchars($item['nombre_producto']); ?>"
                             class="item-img">
                        
                        <div class="item-info">
                            <h4><?php echo htmlspecialchars($item['nombre_producto']); ?></h4>
                            <p>Color: <?php echo htmlspecialchars($item['color']); ?> | Talla: <?php echo htmlspecialchars($item['talla']); ?></p>
                            <p>Cantidad: <?php echo $item['cantidad']; ?> × $<?php echo number_format($item['precio_unitario'], 0, ',', '.'); ?></p>
                        </div>
                        
                        <div class="item-precio">
                            $<?php echo number_format($item['cantidad'] * $item['precio_unitario'], 0, ',', '.'); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <div class="total-row">
                    <span>Total:</span>
                    <span>$<?php echo number_format($pedido['total'], 0, ',', '.'); ?></span>
                </div>
            </div>

            <!-- INFORMACIÓN DEL PEDIDO -->
            <div>
                <div class="card" style="margin-bottom: 2rem;">
                    <h2>Información del Cliente</h2>
                    
                    <div class="info-row">
                        <span class="info-label">Nombre:</span>
                        <span class="info-value"><?php echo htmlspecialchars($pedido['nombre_usuario'] . ' ' . $pedido['apellido_usuario']); ?></span>
                    </div>
                    
                    <div class="info-row">
                        <span class="info-label">Correo:</span>
                        <span class="info-value"><?php echo htmlspecialchars($pedido['correo_usuario']); ?></span>
                    </div>
                    
                    <div class="info-row">
                        <span class="info-label">Teléfono:</span>
                        <span class="info-value"><?php echo htmlspecialchars($pedido['telefono']); ?></span>
                    </div>
                    
                    <div class="info-row">
                        <span class="info-label">Dirección:</span>
                        <span class="info-value"><?php echo htmlspecialchars($pedido['direccion_envio']); ?></span>
                    </div>
                    
                    <?php if ($pedido['notas']): ?>
                        <div class="info-row">
                            <span class="info-label">Notas:</span>
                            <span class="info-value"><?php echo htmlspecialchars($pedido['notas']); ?></span>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="card">
                    <h2>Detalles del Pedido</h2>
                    
                    <div class="info-row">
                        <span class="info-label">Fecha:</span>
                        <span class="info-value"><?php echo date('d/m/Y H:i', strtotime($pedido['fecha_creacion'])); ?></span>
                    </div>
                    
                    <div class="info-row">
                        <span class="info-label">Método de Pago:</span>
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
                    
                    <div class="info-row">
                        <span class="info-label">Estado:</span>
                        <span class="info-value">
                            <form action="pedidos.php" method="GET" style="display: inline;">
                                <select name="nuevo_estado" onchange="if(confirm('¿Cambiar estado del pedido?')) this.form.submit();" 
                                        style="padding: 0.5rem; border-radius: 4px; border: 1px solid #ddd;">
                                    <option value="pendiente" <?php echo $pedido['estado'] === 'pendiente' ? 'selected' : ''; ?>>Pendiente</option>
                                    <option value="procesando" <?php echo $pedido['estado'] === 'procesando' ? 'selected' : ''; ?>>En Proceso</option>
                                    <option value="completado" <?php echo $pedido['estado'] === 'completado' ? 'selected' : ''; ?>>Completado</option>
                                    <option value="cancelado" <?php echo $pedido['estado'] === 'cancelado' ? 'selected' : ''; ?>>Cancelado</option>
                                </select>
                                <input type="hidden" name="cambiar_estado" value="<?php echo $idPedido; ?>">
                            </form>
                        </span>
                    </div>
                </div>
            </div>

        </div>

    </div>
</main>

</body>
</html>