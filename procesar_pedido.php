<?php
session_start();

if (!isset($_SESSION['id_usuario'])) {
    header('Location: index.php?login=true');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: carrito.php');
    exit();
}

require_once __DIR__ . '/BaseDatos.php';
$db = new BaseDatos();

// ── 1. Validar campos obligatorios ──────────────────────────────────────────
$campos = ['nombre','apellido','email','telefono','direccion','ciudad','metodo_pago'];
foreach ($campos as $campo) {
    if (empty(trim($_POST[$campo] ?? ''))) {
        $_SESSION['error'] = "El campo '$campo' es obligatorio.";
        header('Location: checkout.php');
        exit();
    }
}

$metodo = $_POST['metodo_pago'];

// ── 2. Subir comprobante si es Nequi ────────────────────────────────────────
$ruta_comprobante = null;

if ($metodo === 'nequi') {
    if (empty($_FILES['comprobante_pago']['name'])) {
        $_SESSION['error'] = "Debes adjuntar el comprobante de pago Nequi.";
        header('Location: checkout.php');
        exit();
    }

    $dir_uploads = __DIR__ . '/uploads/comprobantes/';
    if (!is_dir($dir_uploads)) {
        mkdir($dir_uploads, 0755, true);
    }

    $ext       = strtolower(pathinfo($_FILES['comprobante_pago']['name'], PATHINFO_EXTENSION));
    $permitidos = ['jpg','jpeg','png','gif','webp','pdf'];

    if (!in_array($ext, $permitidos)) {
        $_SESSION['error'] = "Formato de comprobante no permitido. Usa JPG, PNG o PDF.";
        header('Location: checkout.php');
        exit();
    }

    if ($_FILES['comprobante_pago']['size'] > 5 * 1024 * 1024) {
        $_SESSION['error'] = "El comprobante no puede pesar más de 5 MB.";
        header('Location: checkout.php');
        exit();
    }

    $nombre_archivo = 'nequi_' . $_SESSION['id_usuario'] . '_' . time() . '.' . $ext;
    $destino        = $dir_uploads . $nombre_archivo;

    if (!move_uploaded_file($_FILES['comprobante_pago']['tmp_name'], $destino)) {
        $_SESSION['error'] = "Error al subir el comprobante. Inténtalo de nuevo.";
        header('Location: checkout.php');
        exit();
    }

    $ruta_comprobante = 'uploads/comprobantes/' . $nombre_archivo;
}

// ── 3. Obtener items del carrito ─────────────────────────────────────────────
$sqlItems = "
    SELECT ic.id_variante, ic.cantidad, ic.precio_unitario
    FROM item_carrito ic
    INNER JOIN carrito c ON ic.id_carrito = c.id_carrito
    WHERE c.id_usuario = :usuario AND c.estado = 'activo'
";
$items = $db->query($sqlItems, ['usuario' => $_SESSION['id_usuario']])->fetchAll();

if (empty($items)) {
    $_SESSION['error'] = "Tu carrito está vacío.";
    header('Location: carrito.php');
    exit();
}

$total = 0;
foreach ($items as $item) {
    $total += $item['cantidad'] * $item['precio_unitario'];
}

// ── 4. Armar dirección y notas ───────────────────────────────────────────────
$direccion_envio  = trim($_POST['direccion']);
$direccion_envio .= !empty($_POST['barrio']) ? ', Barrio ' . trim($_POST['barrio']) : '';
$direccion_envio .= ', ' . trim($_POST['ciudad']);

$notas = trim($_POST['notas'] ?? '');

// ── 5. Insertar pedido ───────────────────────────────────────────────────────
try {
    $db->beginTransaction();

    // Estado inicial: nequi espera confirmación, contraentrega va directo a pendiente
    $estado_inicial = ($metodo === 'nequi') ? 'pendiente_pago' : 'pendiente';

    $sqlPedido = "
        INSERT INTO pedidos
            (id_usuario, total, estado, metodo_pago, direccion_envio,
             telefono, notas, comprobante_pago, fecha_creacion)
        VALUES
            (:usuario, :total, :estado, :metodo, :direccion,
             :telefono, :notas, :comprobante, NOW())
    ";

    $db->query($sqlPedido, [
        'usuario'     => $_SESSION['id_usuario'],
        'total'       => $total,
        'estado'      => $estado_inicial,
        'metodo'      => $metodo,
        'direccion'   => $direccion_envio,
        'telefono'    => trim($_POST['telefono']),
        'notas'       => $notas ?: null,
        'comprobante' => $ruta_comprobante,
    ]);

    $id_pedido = $db->lastInsertId();

    // ── 6. Insertar items del pedido ─────────────────────────────────────────
    $sqlItem = "
        INSERT INTO pedido_items (id_pedido, id_variante, cantidad, precio_unitario)
        VALUES (:pedido, :variante, :cantidad, :precio)
    ";

    foreach ($items as $item) {
        $db->query($sqlItem, [
            'pedido'   => $id_pedido,
            'variante' => $item['id_variante'],
            'cantidad' => $item['cantidad'],
            'precio'   => $item['precio_unitario'],
        ]);

        // Descontar stock
        $db->query(
            "UPDATE productos_variante SET stock = stock - :cantidad WHERE id_variante = :variante",
            ['cantidad' => $item['cantidad'], 'variante' => $item['id_variante']]
        );
    }

    // ── 7. Vaciar carrito ────────────────────────────────────────────────────
    $db->query(
        "UPDATE carrito SET estado = 'completado' WHERE id_usuario = :usuario AND estado = 'activo'",
        ['usuario' => $_SESSION['id_usuario']]
    );

    $db->commit();

    $_SESSION['pedido_confirmado'] = $id_pedido;
    header('Location: pedido-confirmado.php');
    exit();

} catch (Exception $e) {
    $db->rollback();
    $_SESSION['error'] = "Ocurrió un error al procesar tu pedido. Inténtalo de nuevo.";
    header('Location: checkout.php');
    exit();
}