<?php
session_start();

if (!isset($_SESSION['id_usuario'])) {
    header('Location: index.php?login=true');
    exit();
}

if (!isset($_SESSION['pedido_confirmado'])) {
    header('Location: index.php');
    exit();
}

$id_pedido = (int) $_SESSION['pedido_confirmado'];
unset($_SESSION['pedido_confirmado']); // Limpiar para que no se pueda recargar

require_once __DIR__ . '/BaseDatos.php';
$db = new BaseDatos();

// Datos del pedido
$pedido = $db->query(
    "SELECT p.*, u.nombre, u.apellido, u.correo
     FROM pedidos p
     JOIN usuarios u ON p.id_usuario = u.id_usuario
     WHERE p.id_pedido = :id AND p.id_usuario = :usuario",
    ['id' => $id_pedido, 'usuario' => $_SESSION['id_usuario']]
)->fetch();

if (!$pedido) {
    header('Location: index.php');
    exit();
}

// Productos del pedido
$items = $db->query(
    "SELECT 
        pi.cantidad,
        pi.precio_unitario,
        p.nombre as nombre_producto,
        CONCAT(pv.color, ' / ', pv.talla) as variante
     FROM pedido_items pi
     JOIN productos_variante pv ON pi.id_variante = pv.id_variante
     JOIN productos p ON pv.id_producto = p.id_producto
     WHERE pi.id_pedido = :id",
    ['id' => $id_pedido]
)->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pedido Confirmado - ELEMENT</title>
    <link rel="icon" type="image/png" href="imagenes/logos/Element.ico">
    <link rel="stylesheet" href="style.css">
    <style>
        .confirm-page {
            min-height: 80vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
            background: #f7f7f7;
        }

        .confirm-card {
            background: #fff;
            border-radius: 18px;
            border: 1px solid #e8e8e8;
            max-width: 600px;
            width: 100%;
            overflow: hidden;
        }

        /* ── Cabecera verde ── */
        .confirm-header {
            background: #000;
            padding: 2.5rem 2rem;
            text-align: center;
        }

        .confirm-check {
            width: 64px;
            height: 64px;
            background: #fff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            font-size: 1.8rem;
        }

        .confirm-header h1 {
            color: #fff;
            font-size: 1.4rem;
            font-weight: 700;
            margin: 0 0 0.4rem;
        }

        .confirm-header p {
            color: #aaa;
            font-size: 0.88rem;
            margin: 0;
        }

        .confirm-numero {
            display: inline-block;
            background: rgba(255,255,255,.12);
            color: #fff;
            font-size: 0.85rem;
            font-weight: 600;
            padding: 0.4rem 1rem;
            border-radius: 20px;
            margin-top: 0.75rem;
            letter-spacing: 0.03em;
        }

        /* ── Cuerpo ── */
        .confirm-body {
            padding: 2rem;
        }

        .confirm-section {
            margin-bottom: 1.75rem;
        }

        .confirm-section:last-child {
            margin-bottom: 0;
        }

        .confirm-section h3 {
            font-size: 0.72rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #aaa;
            margin: 0 0 0.9rem;
        }

        /* ── Items ── */
        .item-list {
            display: flex;
            flex-direction: column;
            gap: 0.6rem;
        }

        .item-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding: 0.75rem 0;
            border-bottom: 1px solid #f5f5f5;
            gap: 1rem;
        }

        .item-row:last-child {
            border-bottom: none;
        }

        .item-nombre {
            font-size: 0.88rem;
            font-weight: 600;
            color: #000;
            margin: 0 0 0.2rem;
        }

        .item-variante {
            font-size: 0.78rem;
            color: #aaa;
            margin: 0;
        }

        .item-precio {
            font-size: 0.9rem;
            font-weight: 600;
            color: #000;
            white-space: nowrap;
        }

        /* ── Total ── */
        .confirm-total {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 0 0;
            border-top: 2px solid #000;
            margin-top: 0.5rem;
        }

        .confirm-total span:first-child {
            font-size: 1rem;
            font-weight: 700;
            color: #000;
        }

        .confirm-total span:last-child {
            font-size: 1.25rem;
            font-weight: 700;
            color: #000;
        }

        /* ── Dirección ── */
        .confirm-direccion {
            background: #f9f9f9;
            border-radius: 10px;
            padding: 1rem 1.25rem;
            font-size: 0.88rem;
            color: #333;
            line-height: 1.6;
        }

        /* ── Método de pago ── */
        .confirm-pago {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            background: #f9f9f9;
            border-radius: 10px;
            padding: 0.9rem 1.25rem;
        }

        .pago-icon {
            font-size: 1.4rem;
        }

        .pago-info strong {
            display: block;
            font-size: 0.88rem;
            font-weight: 600;
            color: #000;
        }

        .pago-info span {
            font-size: 0.78rem;
            color: #aaa;
        }

        /* ── Nequi aviso ── */
        .nequi-aviso {
            background: #faf5ff;
            border: 1px solid #e9d5ff;
            border-radius: 10px;
            padding: 0.9rem 1.25rem;
            font-size: 0.83rem;
            color: #7c3aed;
            margin-top: 0.75rem;
        }

        /* ── Botones ── */
        .confirm-actions {
            display: flex;
            gap: 0.75rem;
            margin-top: 1.75rem;
            flex-wrap: wrap;
        }

        .btn-primary-dark {
            flex: 1;
            padding: 0.85rem 1rem;
            background: #000;
            color: #fff;
            border: none;
            border-radius: 10px;
            font-size: 0.9rem;
            font-weight: 600;
            text-decoration: none;
            text-align: center;
            transition: background 0.2s;
            font-family: inherit;
        }

        .btn-primary-dark:hover { background: #222; }

        .btn-outline {
            flex: 1;
            padding: 0.85rem 1rem;
            background: #fff;
            color: #000;
            border: 1.5px solid #e0e0e0;
            border-radius: 10px;
            font-size: 0.9rem;
            font-weight: 600;
            text-decoration: none;
            text-align: center;
            transition: all 0.2s;
        }

        .btn-outline:hover {
            border-color: #000;
            background: #f9f9f9;
        }

        @media (max-width: 480px) {
            .confirm-body { padding: 1.25rem; }
            .confirm-header { padding: 2rem 1.25rem; }
            .confirm-actions { flex-direction: column; }
        }
    </style>
</head>
<body>

<!-- HEADER -->
<header class="main-header">
    <div class="header-content">
        <a href="index.php" class="logo-link">
            <img src="imagenes/logos/Element.jpg" alt="Logo" class="logo">
        </a>
        <div class="dropdown">
            <a href="catalogo.php" class="nav-link">Productos</a>
        </div>
        <a href="#quienes-somos" class="nav-link">Quienes somos</a>
        <div class="nav-icons">
            <div class="dropdown">
                <?php if (isset($_SESSION['id_usuario'])): ?>
                    <a href="#" class="icon-link"><img src="imagenes/logos/profile.png" alt="Mi cuenta"></a>
                    <div class="dropdown-content">
                        <a href="perfil.php">Configuración</a>
                        <a href="mis-pedidos.php">Mis pedidos</a>
                        <a href="favoritos.php">Favoritos</a>
                        <a href="logout.php">Cerrar sesión</a>
                    </div>
                <?php endif; ?>
            </div>
            <a href="carrito.php" class="icon-link">
                <img src="imagenes/logos/cart.png" alt="Carrito">
            </a>
            <form action="buscar.php" method="GET" class="search-box">
                <img class="search-icon" src="imagenes/logos/Lupa.png" alt="Buscar">
                <input type="text" name="q" class="search-input" placeholder="Buscar productos..." required>
            </form>
        </div>
    </div>
</header>

<div class="confirm-page">
    <div class="confirm-card">

        <!-- Cabecera -->
        <div class="confirm-header">
            <div class="confirm-check">✓</div>
            <h1>¡Pedido confirmado!</h1>
            <p>Gracias <?= htmlspecialchars($pedido['nombre']) ?>, tu pedido fue recibido.</p>
            <span class="confirm-numero">Pedido #<?= $id_pedido ?></span>
        </div>

        <!-- Cuerpo -->
        <div class="confirm-body">

            <!-- Productos -->
            <div class="confirm-section">
                <h3>Productos</h3>
                <div class="item-list">
                    <?php foreach ($items as $item): ?>
                        <div class="item-row">
                            <div>
                                <p class="item-nombre"><?= htmlspecialchars($item['nombre_producto']) ?></p>
                                <p class="item-variante"><?= htmlspecialchars($item['variante']) ?> × <?= $item['cantidad'] ?></p>
                            </div>
                            <span class="item-precio">
                                $<?= number_format($item['cantidad'] * $item['precio_unitario'], 0, ',', '.') ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="confirm-total">
                    <span>Total</span>
                    <span>$<?= number_format($pedido['total'], 0, ',', '.') ?></span>
                </div>
            </div>

            <!-- Dirección -->
            <div class="confirm-section">
                <h3>Dirección de envío</h3>
                <div class="confirm-direccion">
                    <?= htmlspecialchars($pedido['direccion_envio'] ?? '—') ?>
                </div>
            </div>

            <!-- Método de pago -->
            <div class="confirm-section">
                <h3>Método de pago</h3>
                <div class="confirm-pago">
                    <span class="pago-icon">
                        <?= $pedido['metodo_pago'] === 'nequi' ? '💜' : '💵' ?>
                    </span>
                    <div class="pago-info">
                        <strong>
                            <?= $pedido['metodo_pago'] === 'nequi' ? 'Nequi' : 'Pago contraentrega' ?>
                        </strong>
                        <span>
                            <?= $pedido['metodo_pago'] === 'nequi'
                                ? 'Pago transferido — pendiente de confirmación'
                                : 'Pagas en efectivo al recibir tu pedido' ?>
                        </span>
                    </div>
                </div>

                <?php if ($pedido['metodo_pago'] === 'nequi'): ?>
                    <div class="nequi-aviso">
                        ⏳ Tu pago está siendo verificado. Una vez confirmado, procesaremos tu envío. Te contactaremos al correo <strong><?= htmlspecialchars($pedido['correo']) ?></strong>.
                    </div>
                <?php endif; ?>
            </div>

            <!-- Botones -->
            <div class="confirm-actions">
                <a href="mis-pedidos.php" class="btn-primary-dark">Ver mis pedidos</a>
                <a href="catalogo.php"    class="btn-outline">Seguir comprando</a>
            </div>

        </div>
    </div>
</div>

</body>
</html>
