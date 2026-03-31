<?php
session_start();

if (!isset($_SESSION['id_usuario'])) {
    header('Location: index.php?login=true');
    exit();
}

require_once __DIR__ . '/BaseDatos.php';
$db = new BaseDatos();

$id = $_SESSION['id_usuario'];

// ===== PROCESAR ACCIONES =====

// Cambiar nombre/apellido
if (isset($_POST['accion']) && $_POST['accion'] === 'datos_personales') {
    $nombre   = trim($_POST['nombre'] ?? '');
    $apellido = trim($_POST['apellido'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');

    if ($nombre && $apellido) {
        $db->query(
            "UPDATE usuarios SET nombre = :n, apellido = :a, telefono = :t, fecha_actualizacion = NOW() WHERE id_usuario = :id",
            ['n' => $nombre, 'a' => $apellido, 't' => $telefono, 'id' => $id]
        );
        $_SESSION['nombre'] = $nombre;
        $_SESSION['perfil_success'] = "Datos personales actualizados.";
    } else {
        $_SESSION['perfil_error'] = "Nombre y apellido son obligatorios.";
    }
    header('Location: perfil.php#datos');
    exit();
}

// Cambiar dirección
if (isset($_POST['accion']) && $_POST['accion'] === 'direccion') {
    $direccion = trim($_POST['direccion'] ?? '');
    $ciudad    = trim($_POST['ciudad'] ?? '');
    $barrio    = trim($_POST['barrio'] ?? '');

    $db->query(
        "UPDATE usuarios SET direccion = :d, ciudad = :c, barrio = :b, fecha_actualizacion = NOW() WHERE id_usuario = :id",
        ['d' => $direccion, 'c' => $ciudad, 'b' => $barrio, 'id' => $id]
    );
    $_SESSION['perfil_success'] = "Dirección actualizada correctamente.";
    header('Location: perfil.php#direccion');
    exit();
}

// Cambiar correo
if (isset($_POST['accion']) && $_POST['accion'] === 'correo') {
    $nuevo_correo = trim($_POST['nuevo_correo'] ?? '');

    if (!filter_var($nuevo_correo, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['perfil_error'] = "El correo no tiene un formato válido.";
    } elseif ($db->buscarUsuarioPorCorreo($nuevo_correo)) {
        $_SESSION['perfil_error'] = "Ese correo ya está registrado por otro usuario.";
    } else {
        $db->query(
            "UPDATE usuarios SET correo = :c, email_verificado = 0, fecha_actualizacion = NOW() WHERE id_usuario = :id",
            ['c' => $nuevo_correo, 'id' => $id]
        );
        $_SESSION['correo'] = $nuevo_correo;
        $_SESSION['perfil_success'] = "Correo actualizado. Por favor verifica tu nuevo correo.";
    }
    header('Location: perfil.php#correo');
    exit();
}

// Cambiar contraseña
if (isset($_POST['accion']) && $_POST['accion'] === 'password') {
    $actual  = $_POST['password_actual'] ?? '';
    $nueva   = $_POST['password_nueva'] ?? '';
    $confirm = $_POST['password_confirm'] ?? '';

    $usuario = $db->query("SELECT contrasena_hash FROM usuarios WHERE id_usuario = :id", ['id' => $id])->fetch();

    if (!password_verify($actual, $usuario['contrasena_hash'])) {
        $_SESSION['perfil_error'] = "La contraseña actual es incorrecta.";
    } elseif (strlen($nueva) < 6) {
        $_SESSION['perfil_error'] = "La nueva contraseña debe tener mínimo 6 caracteres.";
    } elseif ($nueva !== $confirm) {
        $_SESSION['perfil_error'] = "Las contraseñas nuevas no coinciden.";
    } else {
        $hash = password_hash($nueva, PASSWORD_BCRYPT);
        $db->query(
            "UPDATE usuarios SET contrasena_hash = :h, fecha_actualizacion = NOW() WHERE id_usuario = :id",
            ['h' => $hash, 'id' => $id]
        );
        $_SESSION['perfil_success'] = "Contraseña actualizada correctamente.";
    }
    header('Location: perfil.php#password');
    exit();
}

// Eliminar cuenta
if (isset($_POST['accion']) && $_POST['accion'] === 'eliminar_cuenta') {
    $confirm = $_POST['confirmar_eliminar'] ?? '';
    if ($confirm === 'ELIMINAR') {
        try {
            $db->query("DELETE FROM item_carrito WHERE id_carrito IN (SELECT id_carrito FROM carrito WHERE id_usuario = :id)", ['id' => $id]);
            $db->query("DELETE FROM carrito WHERE id_usuario = :id", ['id' => $id]);
            $db->query("DELETE FROM pedido_items WHERE id_pedido IN (SELECT id_pedido FROM pedidos WHERE id_usuario = :id)", ['id' => $id]);
            $db->query("DELETE FROM pedidos WHERE id_usuario = :id", ['id' => $id]);
            $db->query("DELETE FROM usuario_rol WHERE id_usuario = :id", ['id' => $id]);
            $db->query("DELETE FROM usuarios WHERE id_usuario = :id", ['id' => $id]);
            session_destroy();
            header('Location: index.php');
            exit();
        } catch (Exception $e) {
            $_SESSION['perfil_error'] = "Error al eliminar la cuenta.";
        }
    } else {
        $_SESSION['perfil_error'] = "Debes escribir ELIMINAR para confirmar.";
    }
    header('Location: perfil.php#eliminar');
    exit();
}

// ===== OBTENER DATOS DEL USUARIO =====
$usuario = $db->query("SELECT * FROM usuarios WHERE id_usuario = :id", ['id' => $id])->fetch();

// ===== OBTENER PEDIDOS =====
$pedidos = $db->query(
    "SELECT id_pedido, total, estado, fecha_creacion,
            (SELECT COUNT(*) FROM pedido_items WHERE id_pedido = p.id_pedido) as total_items
     FROM pedidos p
     WHERE id_usuario = :id
     ORDER BY fecha_creacion DESC
     LIMIT 10",
    ['id' => $id]
)->fetchAll();

$inicial = strtoupper(mb_substr($usuario['nombre'], 0, 1));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Perfil - ELEMENT</title>
    <link rel="icon" type="image/png" href="imagenes/logos/Element.ico">
    <link rel="stylesheet" href="style.css">
    <style>
        /* ===== LAYOUT GENERAL ===== */
        .perfil-page {
            min-height: 100vh;
            background: #f5f5f5;
            display: flex;
        }

        /* ===== SIDEBAR ===== */
        .sidebar {
            width: 260px;
            min-width: 260px;
            background: #0a0a0a;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 0;
            left: -260px;
            z-index: 2000;
            transition: left 0.3s ease;
            overflow-y: auto;
        }

        .sidebar.open {
            left: 0;
        }

        .sidebar-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.5);
            z-index: 1999;
        }

        .sidebar-overlay.show {
            display: block;
        }

        .sidebar-header {
            padding: 1.5rem;
            border-bottom: 1px solid #1e1e1e;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .sidebar-logo {
            height: 40px;
            border-radius: 4px;
        }

        .sidebar-close {
            background: none;
            border: none;
            color: #fff;
            font-size: 1.5rem;
            cursor: pointer;
            padding: 0.25rem;
            line-height: 1;
        }

        /* Avatar en sidebar */
        .sidebar-user {
            padding: 1.5rem;
            border-bottom: 1px solid #1e1e1e;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .sidebar-avatar {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background: #fff;
            color: #000;
            font-weight: 700;
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .sidebar-user-info p {
            margin: 0;
            color: #fff;
            font-size: 0.9rem;
            font-weight: 600;
        }

        .sidebar-user-info span {
            font-size: 0.75rem;
            color: #666;
        }

        /* Nav links */
        .sidebar-nav {
            padding: 1rem 0;
            flex: 1;
        }

        .sidebar-section {
            padding: 0.5rem 1rem 0.25rem;
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: #444;
            font-weight: 600;
        }

        .sidebar-link {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1.5rem;
            color: #aaa;
            text-decoration: none;
            font-size: 0.9rem;
            transition: all 0.2s;
            border-left: 3px solid transparent;
        }

        .sidebar-link:hover,
        .sidebar-link.active {
            color: #fff;
            background: #111;
            border-left-color: #fff;
        }

        .sidebar-link .icon {
            font-size: 1rem;
            width: 20px;
            text-align: center;
        }

        .sidebar-footer {
            padding: 1rem 1.5rem;
            border-top: 1px solid #1e1e1e;
        }

        .sidebar-logout {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            color: #f44336;
            text-decoration: none;
            font-size: 0.9rem;
            transition: opacity 0.2s;
        }

        .sidebar-logout:hover { opacity: 0.7; }

        /* ===== CONTENIDO PRINCIPAL ===== */
        .perfil-main {
            flex: 1;
            margin-left: 0;
            transition: margin-left 0.3s ease;
        }

        /* ===== TOPBAR ===== */
        .perfil-topbar {
            background: #0a0a0a;
            padding: 1rem 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .hamburger-btn {
            background: none;
            border: none;
            cursor: pointer;
            padding: 0.5rem;
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .hamburger-btn span {
            display: block;
            width: 24px;
            height: 2px;
            background: #fff;
            border-radius: 2px;
            transition: all 0.3s;
        }

        .perfil-topbar-title {
            color: #fff;
            font-size: 1rem;
            font-weight: 500;
            flex: 1;
        }

        .topbar-home {
            color: #aaa;
            text-decoration: none;
            font-size: 0.85rem;
            transition: color 0.2s;
        }

        .topbar-home:hover { color: #fff; }

        /* ===== CONTENIDO ===== */
        .perfil-content {
            padding: 2rem;
            max-width: 760px;
            margin: 0 auto;
        }

        /* Header con avatar */
        .perfil-hero {
            background: #0a0a0a;
            border-radius: 16px;
            padding: 2rem;
            display: flex;
            align-items: center;
            gap: 1.5rem;
            margin-bottom: 2rem;
            color: #fff;
        }

        .perfil-avatar-large {
            width: 72px;
            height: 72px;
            border-radius: 50%;
            background: #fff;
            color: #000;
            font-size: 1.8rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .perfil-hero-info h2 {
            font-size: 1.3rem;
            font-weight: 600;
            margin: 0 0 0.25rem;
        }

        .perfil-hero-info p {
            font-size: 0.85rem;
            color: #888;
            margin: 0;
        }

        .perfil-hero-badge {
            margin-left: auto;
            background: #1a1a1a;
            border: 1px solid #333;
            color: #aaa;
            font-size: 0.75rem;
            padding: 0.3rem 0.75rem;
            border-radius: 20px;
        }

        /* Alertas */
        .alert {
            padding: 0.9rem 1.1rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            font-size: 0.88rem;
            font-weight: 500;
        }

        .alert-success {
            background: #e8f5e9;
            border: 1px solid #a5d6a7;
            color: #2e7d32;
        }

        .alert-error {
            background: #ffebee;
            border: 1px solid #ef9a9a;
            color: #c62828;
        }

        /* Secciones */
        .perfil-section {
            background: #fff;
            border-radius: 14px;
            padding: 1.75rem;
            margin-bottom: 1.5rem;
            border: 1px solid #e8e8e8;
            scroll-margin-top: 5rem;
        }

        .perfil-section h3 {
            font-size: 1rem;
            font-weight: 600;
            color: #000;
            margin: 0 0 1.5rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid #f0f0f0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        /* Formularios */
        .perfil-form {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .perfil-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .perfil-group {
            display: flex;
            flex-direction: column;
            gap: 0.35rem;
        }

        .perfil-group label {
            font-size: 0.78rem;
            color: #888;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .perfil-group input,
        .perfil-group select {
            padding: 0.75rem 1rem;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            font-size: 0.9rem;
            color: #000;
            outline: none;
            transition: border-color 0.2s;
            background: #fff;
            font-family: inherit;
        }

        .perfil-group input:focus {
            border-color: #000;
        }

        .perfil-group input::placeholder {
            color: #bbb;
        }

        /* Botón guardar */
        .btn-guardar {
            align-self: flex-start;
            padding: 0.75rem 1.75rem;
            background: #000;
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            font-family: inherit;
        }

        .btn-guardar:hover {
            background: #222;
            transform: translateY(-1px);
        }

        /* Zona peligrosa */
        .danger-zone {
            background: #fff5f5;
            border: 1px solid #fecaca;
            border-radius: 14px;
            padding: 1.75rem;
            margin-bottom: 1.5rem;
            scroll-margin-top: 5rem;
        }

        .danger-zone h3 {
            font-size: 1rem;
            font-weight: 600;
            color: #dc2626;
            margin: 0 0 0.75rem;
        }

        .danger-zone p {
            font-size: 0.85rem;
            color: #888;
            margin: 0 0 1rem;
            line-height: 1.6;
        }

        .danger-zone input {
            padding: 0.75rem 1rem;
            border: 1px solid #fecaca;
            border-radius: 8px;
            font-size: 0.9rem;
            width: 100%;
            margin-bottom: 0.75rem;
            outline: none;
            font-family: inherit;
        }

        .danger-zone input:focus {
            border-color: #dc2626;
        }

        .btn-eliminar-cuenta {
            padding: 0.75rem 1.75rem;
            background: #dc2626;
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            font-family: inherit;
        }

        .btn-eliminar-cuenta:hover {
            background: #b91c1c;
        }

        /* Pedidos */
        .pedidos-list {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .pedido-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 1.25rem;
            background: #f8f8f8;
            border-radius: 10px;
            border: 1px solid #f0f0f0;
            text-decoration: none;
            color: inherit;
            transition: all 0.2s;
        }

        .pedido-item:hover {
            background: #f0f0f0;
            border-color: #e0e0e0;
        }

        .pedido-item-left h4 {
            font-size: 0.9rem;
            font-weight: 600;
            margin: 0 0 0.2rem;
            color: #000;
        }

        .pedido-item-left p {
            font-size: 0.8rem;
            color: #888;
            margin: 0;
        }

        .pedido-item-right {
            text-align: right;
        }

        .pedido-precio {
            font-size: 1rem;
            font-weight: 700;
            color: #000;
            display: block;
        }

        .badge-estado {
            display: inline-block;
            padding: 0.2rem 0.6rem;
            border-radius: 20px;
            font-size: 0.72rem;
            font-weight: 600;
            margin-top: 0.25rem;
        }

        .badge-pendiente   { background: #fff3cd; color: #856404; }
        .badge-procesando  { background: #cfe2ff; color: #084298; }
        .badge-enviado     { background: #d1ecf1; color: #0c5460; }
        .badge-completado  { background: #d4edda; color: #155724; }
        .badge-cancelado   { background: #f8d7da; color: #721c24; }

        .empty-pedidos {
            text-align: center;
            padding: 2rem;
            color: #aaa;
            font-size: 0.9rem;
        }

        /* Password wrapper */
        .pw-wrapper {
            position: relative;
        }

        .pw-wrapper input {
            width: 100%;
            padding-right: 2.8rem;
        }

        .pw-toggle {
            position: absolute;
            right: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #bbb;
            cursor: pointer;
            font-size: 0.85rem;
        }

        /* Responsive */
        @media (max-width: 640px) {
            .perfil-content { padding: 1rem; }
            .perfil-row { grid-template-columns: 1fr; }
            .perfil-hero { flex-wrap: wrap; }
            .perfil-hero-badge { margin-left: 0; }
        }
    </style>
</head>
<body>

<!-- OVERLAY -->
<div class="sidebar-overlay" id="overlay" onclick="closeSidebar()"></div>

<!-- ===== SIDEBAR ===== -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <img src="imagenes/logos/Element.jpg" class="sidebar-logo" alt="ELEMENT">
        <button class="sidebar-close" onclick="closeSidebar()">✕</button>
    </div>

    <div class="sidebar-user">
        <div class="sidebar-avatar"><?php echo htmlspecialchars($inicial); ?></div>
        <div class="sidebar-user-info">
            <p><?php echo htmlspecialchars($usuario['nombre'] . ' ' . $usuario['apellido']); ?></p>
            <span><?php echo htmlspecialchars($usuario['correo']); ?></span>
        </div>
    </div>

    <nav class="sidebar-nav">
        <p class="sidebar-section">Navegación</p>
        <a href="index.php" class="sidebar-link">
            <span class="icon">🏠</span> Inicio
        </a>
        <a href="catalogo.php" class="sidebar-link">
            <span class="icon">👗</span> Catálogo
        </a>
        <a href="carrito.php" class="sidebar-link">
            <span class="icon">🛒</span> Carrito
        </a>

        <p class="sidebar-section">Mi cuenta</p>
        <a href="perfil.php#datos" class="sidebar-link active" onclick="closeSidebar()">
            <span class="icon">👤</span> Datos personales
        </a>
        <a href="perfil.php#direccion" class="sidebar-link" onclick="closeSidebar()">
            <span class="icon">📍</span> Dirección
        </a>
        <a href="perfil.php#correo" class="sidebar-link" onclick="closeSidebar()">
            <span class="icon">✉️</span> Correo
        </a>
        <a href="perfil.php#password" class="sidebar-link" onclick="closeSidebar()">
            <span class="icon">🔒</span> Contraseña
        </a>
        <a href="perfil.php#pedidos" class="sidebar-link" onclick="closeSidebar()">
            <span class="icon">📦</span> Mis pedidos
        </a>
    </nav>

    <div class="sidebar-footer">
        <a href="logout.php" class="sidebar-logout">
            <span>↩</span> Cerrar sesión
        </a>
    </div>
</aside>

<!-- ===== CONTENIDO ===== -->
<div class="perfil-page">
    <div class="perfil-main">

        <!-- TOPBAR -->
        <div class="perfil-topbar">
            <button class="hamburger-btn" onclick="openSidebar()">
                <span></span><span></span><span></span>
            </button>
            <span class="perfil-topbar-title">Mi perfil</span>
            <a href="index.php" class="topbar-home">← Volver a la tienda</a>
        </div>

        <!-- CONTENIDO PRINCIPAL -->
        <div class="perfil-content">

            <!-- HERO -->
            <div class="perfil-hero">
                <div class="perfil-avatar-large"><?php echo htmlspecialchars($inicial); ?></div>
                <div class="perfil-hero-info">
                    <h2><?php echo htmlspecialchars($usuario['nombre'] . ' ' . $usuario['apellido']); ?></h2>
                    <p>Miembro desde <?php echo date('F Y', strtotime($usuario['fecha_creacion'])); ?></p>
                </div>
                <span class="perfil-hero-badge">
                    <?php echo $usuario['email_verificado'] ? '✓ Verificado' : '⚠ Sin verificar'; ?>
                </span>
            </div>

            <!-- ALERTAS -->
            <?php if (isset($_SESSION['perfil_success'])): ?>
                <div class="alert alert-success">
                    ✓ <?php echo htmlspecialchars($_SESSION['perfil_success']); unset($_SESSION['perfil_success']); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['perfil_error'])): ?>
                <div class="alert alert-error">
                    ✗ <?php echo htmlspecialchars($_SESSION['perfil_error']); unset($_SESSION['perfil_error']); ?>
                </div>
            <?php endif; ?>

            <!-- 1. DATOS PERSONALES -->
            <div class="perfil-section" id="datos">
                <h3>👤 Datos personales</h3>
                <form class="perfil-form" method="POST">
                    <input type="hidden" name="accion" value="datos_personales">
                    <div class="perfil-row">
                        <div class="perfil-group">
                            <label>Nombre</label>
                            <input type="text" name="nombre" value="<?php echo htmlspecialchars($usuario['nombre']); ?>" required>
                        </div>
                        <div class="perfil-group">
                            <label>Apellido</label>
                            <input type="text" name="apellido" value="<?php echo htmlspecialchars($usuario['apellido']); ?>" required>
                        </div>
                    </div>
                    <div class="perfil-group">
                        <label>Teléfono</label>
                        <input type="tel" name="telefono" value="<?php echo htmlspecialchars($usuario['telefono'] ?? ''); ?>" placeholder="3001234567">
                    </div>
                    <button type="submit" class="btn-guardar">Guardar cambios</button>
                </form>
            </div>

            <!-- 2. DIRECCIÓN -->
            <div class="perfil-section" id="direccion">
                <h3>📍 Dirección de envío</h3>
                <form class="perfil-form" method="POST">
                    <input type="hidden" name="accion" value="direccion">
                    <div class="perfil-group">
                        <label>Dirección</label>
                        <input type="text" name="direccion" value="<?php echo htmlspecialchars($usuario['direccion'] ?? ''); ?>" placeholder="Calle, número, apartamento">
                    </div>
                    <div class="perfil-row">
                        <div class="perfil-group">
                            <label>Ciudad</label>
                            <input type="text" name="ciudad" value="<?php echo htmlspecialchars($usuario['ciudad'] ?? ''); ?>" placeholder="Ej: Bogotá">
                        </div>
                        <div class="perfil-group">
                            <label>Barrio</label>
                            <input type="text" name="barrio" value="<?php echo htmlspecialchars($usuario['barrio'] ?? ''); ?>" placeholder="Ej: Chapinero">
                        </div>
                    </div>
                    <button type="submit" class="btn-guardar">Guardar dirección</button>
                </form>
            </div>

            <!-- 3. CORREO -->
            <div class="perfil-section" id="correo">
                <h3>✉️ Correo electrónico</h3>
                <form class="perfil-form" method="POST">
                    <input type="hidden" name="accion" value="correo">
                    <div class="perfil-group">
                        <label>Correo actual</label>
                        <input type="email" value="<?php echo htmlspecialchars($usuario['correo']); ?>" disabled style="background:#f8f8f8; color:#aaa;">
                    </div>
                    <div class="perfil-group">
                        <label>Nuevo correo</label>
                        <input type="email" name="nuevo_correo" placeholder="nuevo@correo.com" required>
                    </div>
                    <button type="submit" class="btn-guardar">Actualizar correo</button>
                </form>
            </div>

            <!-- 4. CONTRASEÑA -->
            <div class="perfil-section" id="password">
                <h3>🔒 Cambiar contraseña</h3>
                <form class="perfil-form" method="POST">
                    <input type="hidden" name="accion" value="password">
                    <div class="perfil-group">
                        <label>Contraseña actual</label>
                        <div class="pw-wrapper">
                            <input type="password" name="password_actual" id="pwActual" required>
                            <button type="button" class="pw-toggle" onclick="togglePw('pwActual')">👁</button>
                        </div>
                    </div>
                    <div class="perfil-row">
                        <div class="perfil-group">
                            <label>Nueva contraseña</label>
                            <div class="pw-wrapper">
                                <input type="password" name="password_nueva" id="pwNueva" required minlength="6">
                                <button type="button" class="pw-toggle" onclick="togglePw('pwNueva')">👁</button>
                            </div>
                        </div>
                        <div class="perfil-group">
                            <label>Confirmar nueva</label>
                            <div class="pw-wrapper">
                                <input type="password" name="password_confirm" id="pwConfirm" required>
                                <button type="button" class="pw-toggle" onclick="togglePw('pwConfirm')">👁</button>
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="btn-guardar">Cambiar contraseña</button>
                </form>
            </div>

            <!-- 5. MIS PEDIDOS -->
            <div class="perfil-section" id="pedidos">
                <h3>📦 Mis pedidos recientes</h3>
                <?php if (empty($pedidos)): ?>
                    <div class="empty-pedidos">
                        <p>Aún no tienes pedidos realizados</p>
                        <a href="catalogo.php" style="color:#000; font-weight:600;">Ir al catálogo →</a>
                    </div>
                <?php else: ?>
                    <div class="pedidos-list">
                        <?php foreach ($pedidos as $pedido): ?>
                            <a href="mis-pedidos.php?id=<?php echo $pedido['id_pedido']; ?>" class="pedido-item">
                                <div class="pedido-item-left">
                                    <h4>Pedido #<?php echo $pedido['id_pedido']; ?></h4>
                                    <p><?php echo $pedido['total_items']; ?> producto(s) · <?php echo date('d/m/Y', strtotime($pedido['fecha_creacion'])); ?></p>
                                </div>
                                <div class="pedido-item-right">
                                    <span class="pedido-precio">$<?php echo number_format($pedido['total'], 0, ',', '.'); ?></span>
                                    <span class="badge-estado badge-<?php echo $pedido['estado']; ?>">
                                        <?php echo ucfirst($pedido['estado']); ?>
                                    </span>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                    <a href="mis-pedidos.php" style="display:block; text-align:center; margin-top:1rem; color:#000; font-size:0.85rem; font-weight:600;">Ver todos los pedidos →</a>
                <?php endif; ?>
            </div>

            <!-- 6. ELIMINAR CUENTA -->
            <div class="danger-zone" id="eliminar">
                <h3>⚠️ Zona de peligro</h3>
                <p>Eliminar tu cuenta es una acción permanente. Se borrarán todos tus datos, pedidos e historial. Esta acción <strong>no se puede deshacer</strong>.</p>
                <form method="POST" onsubmit="return confirm('¿Estás completamente seguro? Esta acción no se puede deshacer.')">
                    <input type="hidden" name="accion" value="eliminar_cuenta">
                    <input type="text" name="confirmar_eliminar" placeholder="Escribe ELIMINAR para confirmar">
                    <button type="submit" class="btn-eliminar-cuenta">Eliminar mi cuenta</button>
                </form>
            </div>

        </div>
    </div>
</div>

<script>
function openSidebar() {
    document.getElementById('sidebar').classList.add('open');
    document.getElementById('overlay').classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closeSidebar() {
    document.getElementById('sidebar').classList.remove('open');
    document.getElementById('overlay').classList.remove('show');
    document.body.style.overflow = '';
}

function togglePw(id) {
    const input = document.getElementById(id);
    input.type = input.type === 'password' ? 'text' : 'password';
}

// Auto-ocultar alertas
setTimeout(() => {
    document.querySelectorAll('.alert').forEach(a => {
        a.style.transition = 'opacity 0.4s';
        a.style.opacity = '0';
        setTimeout(() => a.remove(), 400);
    });
}, 4000);
</script>

</body>
</html>