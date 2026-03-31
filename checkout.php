<?php
session_start();

if (!isset($_SESSION['id_usuario'])) {
    header('Location: index.php?login=true');
    exit();
}

require_once __DIR__ . '/BaseDatos.php';
$db = new BaseDatos();

$sql = "SELECT 
            ic.id_item,
            ic.cantidad,
            ic.precio_unitario,
            pv.id_variante,
            pv.color,
            pv.talla,
            p.id_producto,
            p.nombre as nombre_producto,
            p.precio,
            CONCAT(pv.color, ' / ', pv.talla) as variante
        FROM item_carrito ic
        INNER JOIN carrito c ON ic.id_carrito = c.id_carrito
        INNER JOIN productos_variante pv ON ic.id_variante = pv.id_variante
        INNER JOIN productos p ON pv.id_producto = p.id_producto
        WHERE c.id_usuario = :usuario 
        AND c.estado = 'activo'
        AND pv.estado = 'activo'
        AND p.estado = 'activo'
        ORDER BY ic.id_item DESC";

$items = $db->query($sql, ['usuario' => $_SESSION['id_usuario']])->fetchAll();

if (empty($items)) {
    $_SESSION['error'] = "Tu carrito está vacío";
    header('Location: carrito.php');
    exit();
}

$total = 0;
foreach ($items as $item) {
    $total += $item['cantidad'] * $item['precio_unitario'];
}

// Obtener datos del usuario incluyendo los nuevos campos
$usuario = $db->query(
    "SELECT * FROM usuarios WHERE id_usuario = :id",
    ['id' => $_SESSION['id_usuario']]
)->fetch();

$whatsapp_numero = '573125781377';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finalizar Compra - ELEMENT</title>
    <link rel="icon" type="image/png" href="imagenes/logos/Element.ico">
    <link rel="stylesheet" href="style.css">
    <style>
        .checkout-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 2rem;
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 2rem;
        }

        .checkout-form {
            background: #fff;
            padding: 2rem;
            border-radius: 14px;
            border: 1px solid #e8e8e8;
        }

        .form-section {
            margin-bottom: 2rem;
            padding-bottom: 2rem;
            border-bottom: 1px solid #f0f0f0;
        }

        .form-section:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }

        .form-section h2 {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 1.25rem;
            color: #000;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.35rem;
            margin-bottom: 0.75rem;
        }

        .form-group label {
            font-size: 0.78rem;
            font-weight: 500;
            color: #888;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .form-group input,
        .form-group textarea {
            padding: 0.75rem 1rem;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            font-size: 0.9rem;
            color: #000;
            outline: none;
            transition: border-color 0.2s;
            font-family: inherit;
            background: #fff;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            border-color: #000;
        }

        .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }

        /* ===== MÉTODOS DE PAGO ===== */
        .payment-methods {
            display: grid;
            gap: 0.75rem;
        }

        .payment-option {
            border: 1.5px solid #e0e0e0;
            border-radius: 10px;
            padding: 1rem 1.25rem;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .payment-option:hover {
            border-color: #000;
            background: #fafafa;
        }

        .payment-option.selected {
            border-color: #000;
            background: #f8f8f8;
        }

        .payment-option input[type="radio"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
            accent-color: #000;
        }

        .payment-icon-circle {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            flex-shrink: 0;
        }

        .payment-info h4 {
            margin: 0 0 0.2rem;
            font-size: 0.95rem;
            font-weight: 600;
            color: #000;
        }

        .payment-info p {
            margin: 0;
            font-size: 0.8rem;
            color: #888;
        }

        /* ===== PANEL NEQUI ===== */
        .nequi-panel {
            display: none;
            background: #f8f0ff;
            border: 1.5px solid #d8b4fe;
            border-radius: 12px;
            padding: 1.5rem;
            margin-top: 1rem;
            text-align: center;
        }

        .nequi-panel.show { display: block; }

        .nequi-panel h4 {
            font-size: 1rem;
            font-weight: 600;
            color: #6b21a8;
            margin: 0 0 0.5rem;
        }

        .nequi-panel p {
            font-size: 0.85rem;
            color: #7c3aed;
            margin: 0 0 1rem;
        }

        .nequi-qr {
            width: 180px;
            height: 180px;
            border-radius: 12px;
            border: 3px solid #fff;
            box-shadow: 0 4px 12px rgba(109,40,217,0.2);
            margin: 0 auto 1rem;
            display: block;
            object-fit: contain;
            background: #fff;
        }

        .nequi-amount {
            font-size: 1.5rem;
            font-weight: 700;
            color: #6b21a8;
            margin-bottom: 0.5rem;
        }

        .nequi-steps {
            text-align: left;
            background: #fff;
            border-radius: 8px;
            padding: 1rem;
            margin-top: 1rem;
        }

        .nequi-steps p {
            font-size: 0.82rem;
            color: #555;
            margin: 0.3rem 0;
        }

        /* Upload comprobante */
        .upload-label {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.75rem;
            border: 1.5px dashed #d8b4fe;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.85rem;
            color: #7c3aed;
            margin-top: 0.75rem;
            transition: all 0.2s;
        }

        .upload-label:hover {
            background: #f3e8ff;
            border-color: #a855f7;
        }

        .upload-label input { display: none; }

        .upload-nombre {
            font-size: 0.8rem;
            color: #6b21a8;
            text-align: center;
            margin-top: 0.4rem;
            display: none;
        }

        /* ===== BOTÓN SUBMIT ===== */
        .btn-submit {
            width: 100%;
            padding: 1rem;
            background: #000;
            color: #fff;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 1.5rem;
            font-family: inherit;
            letter-spacing: 0.03em;
        }

        .btn-submit:hover {
            background: #222;
            transform: translateY(-2px);
        }

        .btn-secondary {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.65rem 1.25rem;
            background: #f5f5f5;
            color: #333;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 500;
            font-size: 0.9rem;
            margin-bottom: 1.5rem;
            transition: background 0.2s;
        }

        .btn-secondary:hover { background: #e8e8e8; }

        .alert {
            padding: 0.9rem 1rem;
            border-radius: 8px;
            margin-bottom: 1.25rem;
            font-size: 0.88rem;
        }

        .alert-error {
            background: #ffebee;
            color: #c62828;
            border: 1px solid #ef9a9a;
        }

        /* ===== RESUMEN ===== */
        .order-summary {
            background: #fff;
            padding: 1.75rem;
            border-radius: 14px;
            border: 1px solid #e8e8e8;
            height: fit-content;
            position: sticky;
            top: 2rem;
        }

        .order-summary h2 {
            font-size: 1.1rem;
            font-weight: 600;
            margin: 0 0 1.25rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid #f0f0f0;
            color: #000;
        }

        .summary-item {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding: 0.7rem 0;
            border-bottom: 1px solid #f5f5f5;
            gap: 1rem;
        }

        .summary-item:last-of-type { border-bottom: none; }

        .summary-item-info h4 {
            margin: 0 0 0.2rem;
            font-size: 0.88rem;
            font-weight: 600;
            color: #000;
        }

        .summary-item-info p {
            margin: 0;
            font-size: 0.8rem;
            color: #888;
        }

        .summary-item-precio {
            font-weight: 600;
            font-size: 0.9rem;
            white-space: nowrap;
            color: #000;
        }

        .summary-linea {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0;
            font-size: 0.88rem;
            color: #888;
        }

        .summary-total {
            display: flex;
            justify-content: space-between;
            font-size: 1.25rem;
            font-weight: 700;
            color: #000;
            padding-top: 1rem;
            margin-top: 0.5rem;
            border-top: 2px solid #000;
        }

        .summary-garantias {
            margin-top: 1.25rem;
            padding-top: 1rem;
            border-top: 1px solid #f0f0f0;
        }

        .summary-garantias p {
            font-size: 0.8rem;
            color: #aaa;
            margin: 0.3rem 0;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .checkout-container {
                grid-template-columns: 1fr;
                padding: 1rem;
            }
            .order-summary { position: static; }
            .form-row { grid-template-columns: 1fr; }
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
                    <?php if (isset($_SESSION['rol']) && $_SESSION['rol'] === 'ADMIN'): ?>
                        <a href="#" class="icon-link"><img src="imagenes/logos/profile.png" alt="Mi cuenta"></a>
                        <div class="dropdown-content">
                            <a href="/admin/dashboard.php">Dashboard</a>
                            <a href="/admin/productos.php">Productos</a>
                            <a href="/admin/pedidos.php">Pedidos</a>
                            <a href="logout.php">Cerrar sesión</a>
                        </div>
                    <?php else: ?>
                        <a href="#" class="icon-link"><img src="imagenes/logos/profile.png" alt="Mi cuenta"></a>
                        <div class="dropdown-content">
                            <a href="perfil.php">Configuración</a>
                            <a href="mis-pedidos.php">Mis pedidos</a>
                            <a href="favoritos.php">Favoritos</a>
                            <a href="logout.php">Cerrar sesión</a>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <a href="#" class="icon-link" id="btn-login">
                        <img src="imagenes/logos/profile.png" alt="Iniciar sesión">
                    </a>
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

<main class="checkout-container">

    <div>
        <a href="carrito.php" class="btn-secondary">← Volver al carrito</a>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error">
                <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <form action="procesar_pedido.php" method="POST" class="checkout-form"
              id="checkoutForm" enctype="multipart/form-data">

            <!-- INFO DE CONTACTO -->
            <div class="form-section">
                <h2>📋 Información de contacto</h2>

                <div class="form-row">
                    <div class="form-group">
                        <label>Nombre *</label>
                        <input type="text" name="nombre"
                               value="<?php echo htmlspecialchars($usuario['nombre'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Apellido *</label>
                        <input type="text" name="apellido"
                               value="<?php echo htmlspecialchars($usuario['apellido'] ?? ''); ?>" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Correo electrónico *</label>
                        <input type="email" name="email"
                               value="<?php echo htmlspecialchars($usuario['correo'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Teléfono *</label>
                        <input type="tel" name="telefono" id="telefono"
                               value="<?php echo htmlspecialchars($usuario['telefono'] ?? ''); ?>"
                               placeholder="3001234567" required>
                    </div>
                </div>
            </div>

            <!-- DIRECCIÓN -->
            <div class="form-section">
                <h2>📍 Dirección de envío</h2>

                <div class="form-group">
                    <label>Dirección completa *</label>
                    <input type="text" name="direccion" id="direccion"
                           value="<?php echo htmlspecialchars($usuario['direccion'] ?? ''); ?>"
                           placeholder="Calle, número, apartamento" required>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Ciudad *</label>
                        <input type="text" name="ciudad" id="ciudad"
                               value="<?php echo htmlspecialchars($usuario['ciudad'] ?? ''); ?>"
                               placeholder="Ej: Bogotá" required>
                    </div>
                    <div class="form-group">
                        <label>Barrio</label>
                        <input type="text" name="barrio"
                               value="<?php echo htmlspecialchars($usuario['barrio'] ?? ''); ?>"
                               placeholder="Ej: Chapinero">
                    </div>
                </div>

                <div class="form-group">
                    <label>Indicaciones adicionales</label>
                    <textarea name="notas"
                              placeholder="Ej: Dejar con el portero, apartamento 301..."></textarea>
                </div>
            </div>

            <!-- MÉTODO DE PAGO -->
            <div class="form-section">
                <h2>💳 Método de pago</h2>

                <div class="payment-methods">

                    <!-- Contraentrega -->
                    <label class="payment-option selected" id="opt-contraentrega">
                        <input type="radio" name="metodo_pago" value="contraentrega" checked
                               onchange="seleccionarPago('contraentrega')">
                        <div class="payment-icon-circle" style="background:#dcfce7;">💵</div>
                        <div class="payment-info">
                            <h4>Pago contraentrega</h4>
                            <p>Paga en efectivo al recibir tu pedido</p>
                        </div>
                    </label>

                    <!-- Nequi -->
                    <label class="payment-option" id="opt-nequi">
                        <input type="radio" name="metodo_pago" value="nequi"
                               onchange="seleccionarPago('nequi')">
                        <div class="payment-icon-circle" style="background:#f3e8ff;">
                            <img src="imagenes/logos/nequi-icon.png"
                                 onerror="this.style.display='none'; this.parentElement.textContent='N'"
                                 style="width:28px; height:28px; object-fit:contain;">
                        </div>
                        <div class="payment-info">
                            <h4>Nequi</h4>
                            <p>Paga escaneando el QR con tu app Nequi</p>
                        </div>
                    </label>

                </div>

                <!-- Panel Nequi -->
                <div class="nequi-panel" id="nequiPanel">
                    <h4>Pago con Nequi</h4>
                    <p>Escanea el QR con tu app Nequi por el valor exacto</p>

                    <!-- ⬇ PON AQUÍ TU IMAGEN DE QR -->
                    <img src="imagenes/logos/nequi-qr.png"
                         alt="QR Nequi"
                         class="nequi-qr"
                         onerror="this.src=''; this.alt='[Agrega tu QR en imagenes/logos/nequi-qr.png]';">

                    <div class="nequi-amount" id="nequiMonto">
                        $<?php echo number_format($total, 0, ',', '.'); ?>
                    </div>

                    <div class="nequi-steps">
                        <p>1️⃣ Abre tu app Nequi</p>
                        <p>2️⃣ Ve a <strong>Pagar → Escanear QR</strong></p>
                        <p>3️⃣ Ingresa el monto exacto: <strong>$<?php echo number_format($total, 0, ',', '.'); ?></strong></p>
                        <p>4️⃣ Sube la captura del pago aquí abajo</p>
                        <p>5️⃣ El admin confirmará tu pago y procesará el envío</p>
                    </div>

                    <label class="upload-label">
                        📎 Adjuntar captura de pago
                        <input type="file" name="comprobante_pago" id="comprobanteInput"
                               accept="image/*,.pdf" onchange="mostrarNombre(this)">
                    </label>
                    <p class="upload-nombre" id="uploadNombre"></p>
                </div>

            </div>

            <button type="submit" class="btn-submit" id="btnSubmit">
                Confirmar pedido
            </button>

        </form>
    </div>

    <!-- RESUMEN -->
    <aside class="order-summary">
        <h2>Resumen del pedido</h2>

        <?php foreach ($items as $item): ?>
            <div class="summary-item">
                <div class="summary-item-info">
                    <h4><?php echo htmlspecialchars($item['nombre_producto']); ?></h4>
                    <p><?php echo htmlspecialchars($item['variante']); ?> × <?php echo $item['cantidad']; ?></p>
                </div>
                <span class="summary-item-precio">
                    $<?php echo number_format($item['cantidad'] * $item['precio_unitario'], 0, ',', '.'); ?>
                </span>
            </div>
        <?php endforeach; ?>

        <div class="summary-linea" style="margin-top:0.75rem;">
            <span>Subtotal</span>
            <span>$<?php echo number_format($total, 0, ',', '.'); ?></span>
        </div>
        <div class="summary-linea">
            <span>Envío</span>
            <span style="font-style:italic; color:#bbb;">A calcular</span>
        </div>
        <div class="summary-total">
            <span>Total</span>
            <span>$<?php echo number_format($total, 0, ',', '.'); ?></span>
        </div>

        <div class="summary-garantias">
            <p>✓ Envío a todo Colombia</p>
            <p>✓ Compra segura</p>
            <p>✓ Soporte por WhatsApp</p>
        </div>
    </aside>

</main>

<script>
const total = <?php echo $total; ?>;
const whatsapp = '<?php echo $whatsapp_numero; ?>';

// Datos del pedido para WhatsApp
const items = <?php echo json_encode(array_map(function($i) {
    return [
        'nombre'   => $i['nombre_producto'],
        'variante' => $i['variante'],
        'cantidad' => $i['cantidad'],
        'precio'   => $i['cantidad'] * $i['precio_unitario']
    ];
}, $items)); ?>;

function seleccionarPago(metodo) {
    document.querySelectorAll('.payment-option').forEach(o => o.classList.remove('selected'));
    document.getElementById('opt-' + metodo).classList.add('selected');

    const panel = document.getElementById('nequiPanel');
    if (metodo === 'nequi') {
        panel.classList.add('show');
    } else {
        panel.classList.remove('show');
    }
}

function mostrarNombre(input) {
    const label = document.getElementById('uploadNombre');
    if (input.files && input.files[0]) {
        label.textContent = '✓ ' + input.files[0].name;
        label.style.display = 'block';
    }
}

function construirMensajeWhatsApp(datos) {
    let msg = '🛍️ *NUEVO PEDIDO - ELEMENT*\n\n';
    msg += '👤 *Cliente:* ' + datos.nombre + ' ' + datos.apellido + '\n';
    msg += '📧 *Correo:* ' + datos.email + '\n';
    msg += '📱 *Teléfono:* ' + datos.telefono + '\n\n';
    msg += '📍 *Dirección de envío:*\n';
    msg += datos.direccion + '\n';
    if (datos.barrio) msg += 'Barrio: ' + datos.barrio + '\n';
    msg += 'Ciudad: ' + datos.ciudad + '\n';
    if (datos.notas) msg += '📝 Notas: ' + datos.notas + '\n';
    msg += '\n🛒 *Productos:*\n';
    items.forEach((item, i) => {
        msg += (i + 1) + '. ' + item.nombre + ' (' + item.variante + ') x' + item.cantidad;
        msg += ' → $' + item.precio.toLocaleString('es-CO') + '\n';
    });
    msg += '\n💰 *Total: $' + total.toLocaleString('es-CO') + '*\n';
    msg += '💳 *Método de pago:* ' + datos.metodo_pago + '\n';
    return encodeURIComponent(msg);
}

document.getElementById('checkoutForm').addEventListener('submit', function(e) {
    e.preventDefault();

    const telefono = document.getElementById('telefono').value;
    if (!/^\d{7,10}$/.test(telefono)) {
        alert('Por favor ingresa un número de teléfono válido (7-10 dígitos)');
        return;
    }

    const metodo = document.querySelector('input[name="metodo_pago"]:checked').value;

    // Validar comprobante si es Nequi
    if (metodo === 'nequi') {
        const comprobante = document.getElementById('comprobanteInput').files[0];
        if (!comprobante) {
            alert('Por favor adjunta la captura de tu pago con Nequi');
            return;
        }
    }

    const datos = {
        nombre:      document.querySelector('input[name="nombre"]').value,
        apellido:    document.querySelector('input[name="apellido"]').value,
        email:       document.querySelector('input[name="email"]').value,
        telefono:    telefono,
        direccion:   document.querySelector('input[name="direccion"]').value,
        ciudad:      document.querySelector('input[name="ciudad"]').value,
        barrio:      document.querySelector('input[name="barrio"]').value,
        notas:       document.querySelector('textarea[name="notas"]').value,
        metodo_pago: metodo === 'contraentrega' ? 'Contraentrega' : 'Nequi'
    };

    if (!confirm('¿Confirmar pedido por $' + total.toLocaleString('es-CO') + '?')) return;

    // Abrir WhatsApp con el mensaje del pedido
    const mensaje = construirMensajeWhatsApp(datos);
    window.open('https://wa.me/' + whatsapp + '?text=' + mensaje, '_blank');

    // Enviar formulario para guardar en BD
    this.submit();
});
</script>

</body>
</html>