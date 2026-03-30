<?php
session_start();

// Protección: solo admin puede acceder
if (!isset($_SESSION['id_usuario']) || strtolower($_SESSION['rol']) !== 'admin') {
    header('Location: ../index.php');
    exit();
}

require_once __DIR__ . '/../BaseDatos.php';
$db = new BaseDatos();

// ===== CAMBIAR ROL =====
if (isset($_GET['toggle_rol']) && is_numeric($_GET['toggle_rol'])) {
    $id = $_GET['toggle_rol'];

    // No permitir que el admin se cambie el rol a sí mismo
    if ($id == $_SESSION['id_usuario']) {
        $_SESSION['error'] = "No puedes cambiar tu propio rol.";
        header('Location: usuarios.php');
        exit();
    }

    try {
        // Ver si ya tiene rol asignado
        $rolActual = $db->query(
            "SELECT ur.id_rol, r.nombre_rol 
             FROM usuario_rol ur 
             JOIN roles r ON ur.id_rol = r.id_rol 
             WHERE ur.id_usuario = :id",
            ['id' => $id]
        )->fetch();

        if ($rolActual) {
            // Alternar entre ADMIN y USER
            $nuevoRol = strtoupper($rolActual['nombre_rol']) === 'ADMIN' ? 'CLIENTE' : 'ADMIN';
            $nuevoIdRol = $db->query(
                "SELECT id_rol FROM roles WHERE nombre_rol = :rol",
                ['rol' => $nuevoRol]
            )->fetch()['id_rol'];

            $db->query(
                "UPDATE usuario_rol SET id_rol = :id_rol WHERE id_usuario = :id",
                ['id_rol' => $nuevoIdRol, 'id' => $id]
            );
        } else {
            // Asignar rol USER por defecto si no tiene ninguno
            $idRolUser = $db->query(
                "SELECT id_rol FROM roles WHERE nombre_rol = 'USER'"
            )->fetch()['id_rol'];

            $db->query(
                "INSERT INTO usuario_rol (id_usuario, id_rol) VALUES (:id, :id_rol)",
                ['id' => $id, 'id_rol' => $idRolUser]
            );
        }

        $_SESSION['success'] = "Rol actualizado correctamente.";
    } catch (Exception $e) {
        $_SESSION['error'] = "Error al cambiar rol: " . $e->getMessage();
    }

    header('Location: usuarios.php');
    exit();
}

// ===== ELIMINAR USUARIO =====
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = $_GET['delete'];

    if ($id == $_SESSION['id_usuario']) {
        $_SESSION['error'] = "No puedes eliminarte a ti mismo.";
        header('Location: usuarios.php');
        exit();
    }

    try {
        // 1. Eliminar items del carrito
        $db->query("DELETE FROM item_carrito WHERE id_carrito IN (SELECT id_carrito FROM carrito WHERE id_usuario = :id)", ['id' => $id]);
        
        // 2. Eliminar carrito
        $db->query("DELETE FROM carrito WHERE id_usuario = :id", ['id' => $id]);
        
        // 3. Eliminar items de pedidos
        $db->query("DELETE FROM pedido_items WHERE id_pedido IN (SELECT id_pedido FROM pedidos WHERE id_usuario = :id)", ['id' => $id]);
        
        // 4. Eliminar pedidos
        $db->query("DELETE FROM pedidos WHERE id_usuario = :id", ['id' => $id]);
        
        // 5. Eliminar rol
        $db->query("DELETE FROM usuario_rol WHERE id_usuario = :id", ['id' => $id]);
        
        // 6. Eliminar usuario
        $db->query("DELETE FROM usuarios WHERE id_usuario = :id", ['id' => $id]);

        $_SESSION['success'] = "Usuario y todos sus datos eliminados correctamente.";
    } catch (Exception $e) {
        $_SESSION['error'] = "Error al eliminar: " . $e->getMessage();
    }

    header('Location: usuarios.php');
    exit();
}


// ===== FILTROS Y BÚSQUEDA =====
$busqueda   = trim($_GET['buscar'] ?? '');
$filtroRol  = $_GET['rol'] ?? '';

$sql = "SELECT 
            u.id_usuario,
            u.nombre,
            u.apellido,
            u.correo,
            u.estado,
            u.email_verificado,
            u.fecha_creacion,
            COALESCE(r.nombre_rol, 'SIN ROL') as rol
        FROM usuarios u
        LEFT JOIN usuario_rol ur ON u.id_usuario = ur.id_usuario
        LEFT JOIN roles r ON ur.id_rol = r.id_rol
        WHERE 1=1";

$params = [];

if ($busqueda) {
    $sql .= " AND (u.nombre LIKE :busqueda OR u.apellido LIKE :busqueda2 OR u.correo LIKE :busqueda3)";
    $params['busqueda']  = "%$busqueda%";
    $params['busqueda2'] = "%$busqueda%";
    $params['busqueda3'] = "%$busqueda%";
}

if ($filtroRol) {
    $sql .= " AND r.nombre_rol = :rol";
    $params['rol'] = $filtroRol;
}

$sql .= " ORDER BY u.fecha_creacion DESC";

$usuarios      = $db->query($sql, $params)->fetchAll();
$totalUsuarios = count($usuarios);
$totalAdmins   = count(array_filter($usuarios, fn($u) => strtoupper($u['rol']) === 'ADMIN'));
$totalActivos  = count(array_filter($usuarios, fn($u) => $u['estado'] === 'activo'));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Usuarios - ELEMENT</title>
    <link rel="icon" type="image/png" href="../imagenes/logos/Element.ico">
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="dashboard.css">
    <style>
        /* ===== PÁGINA USUARIOS ===== */
        .admin-main {
            padding: 2rem;
            max-width: 1300px;
            margin: 0 auto;
        }

        .page-header {
            margin-bottom: 1.5rem;
        }

        .page-header h1 {
            font-size: 1.8rem;
            color: #fff;
            margin-bottom: 0.5rem;
        }

        /* Alertas */
        .alert {
            padding: 0.9rem 1.2rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            font-weight: 500;
        }

        .alert-success {
            background: #1a3a1a;
            color: #4caf50;
            border: 1px solid #4caf50;
        }

        .alert-error {
            background: #3a1a1a;
            color: #f44336;
            border: 1px solid #f44336;
        }

        /* Stats */
        .quick-stats {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
        }

        .stat-mini {
            background: #1e1e1e;
            border: 1px solid #333;
            border-radius: 10px;
            padding: 1rem 1.5rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            min-width: 130px;
        }

        .stat-value {
            font-size: 1.8rem;
            font-weight: 700;
            color: #f0c040;
        }

        .stat-label {
            font-size: 0.75rem;
            color: #aaa;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-top: 0.2rem;
        }

        /* Barra de acciones */
        .actions-bar {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 1.5rem;
        }

        .filter-form {
            display: flex;
            gap: 0.6rem;
            flex-wrap: wrap;
            align-items: center;
        }

        .search-input {
            background: #1e1e1e;
            border: 1px solid #444;
            color: #fff;
            padding: 0.55rem 1rem;
            border-radius: 8px;
            font-size: 0.9rem;
            width: 220px;
        }

        .search-input::placeholder { color: #777; }

        .filter-select {
            background: #1e1e1e;
            border: 1px solid #444;
            color: #fff;
            padding: 0.55rem 1rem;
            border-radius: 8px;
            font-size: 0.9rem;
            cursor: pointer;
        }

        .btn {
            padding: 0.55rem 1.2rem;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.9rem;
            cursor: pointer;
            border: none;
            text-decoration: none;
            display: inline-block;
        }

        .btn-primary   { background: #f0c040; color: #000; }
        .btn-secondary { background: #333; color: #ccc; }

        /* Tabla */
        .table-container {
            background: #1e1e1e;
            border: 1px solid #333;
            border-radius: 12px;
            overflow: hidden;
        }

        .users-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.9rem;
        }

        .users-table thead {
            background: #111;
        }

        .users-table th {
            padding: 0.9rem 1rem;
            text-align: left;
            color: #f0c040;
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            border-bottom: 1px solid #333;
        }

        .users-table td {
            padding: 0.85rem 1rem;
            color: #ddd;
            border-bottom: 1px solid #2a2a2a;
            vertical-align: middle;
        }

        .users-table tbody tr:last-child td {
            border-bottom: none;
        }

        .users-table tbody tr:hover {
            background: #252525;
        }

        /* Badges */
        .badge {
            padding: 0.25rem 0.7rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            display: inline-block;
        }

        .badge-ADMIN  { background: #3a2a00; color: #f0c040; border: 1px solid #f0c040; }
        .badge-USER   { background: #1a2a3a; color: #64b5f6; border: 1px solid #64b5f6; }
        .badge-SIN\ ROL { background: #2a2a2a; color: #888; border: 1px solid #555; }

        .badge-activo   { background: #1a3a1a; color: #4caf50; }
        .badge-inactivo { background: #3a1a1a; color: #f44336; }

        .badge-verificado     { background: #1a3a1a; color: #4caf50; font-size: 0.7rem; }
        .badge-no-verificado  { background: #2a2a1a; color: #ff9800; font-size: 0.7rem; }

        /* Botón de acción rol */
        .btn-rol {
            background: #2a2a2a;
            border: 1px solid #444;
            color: #ccc;
            padding: 0.3rem 0.7rem;
            border-radius: 6px;
            font-size: 0.78rem;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.2s;
        }

        .btn-rol:hover {
            background: #f0c040;
            color: #000;
            border-color: #f0c040;
        }

        .btn-rol-disabled {
            opacity: 0.35;
            cursor: not-allowed;
            pointer-events: none;
        }

        /* Avatar inicial */
        .user-avatar {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            background: #f0c040;
            color: #000;
            font-weight: 700;
            font-size: 0.85rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-right: 0.6rem;
            flex-shrink: 0;
        }

        .user-cell {
            display: flex;
            align-items: center;
        }

        .user-name {
            font-weight: 600;
            color: #fff;
        }

        .user-id {
            font-size: 0.75rem;
            color: #666;
        }

        /* Empty state */
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #666;
            font-size: 1rem;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .users-table th:nth-child(4),
            .users-table td:nth-child(4),
            .users-table th:nth-child(5),
            .users-table td:nth-child(5) {
                display: none;
            }

            .search-input { width: 100%; }
            .actions-bar  { justify-content: flex-start; }
        }
    </style>
</head>
<body>

<!-- HEADER -->
<header class="main-header">
    <div class="header-content">


        <nav class="nav-menu">
            <a href="dashboard.php" class="nav-link">Dashboard</a>
            <a href="productos.php" class="nav-link">Productos</a>
            <a href="pedidos.php" class="nav-link">Pedidos</a>
            <a href="usuarios.php" class="nav-link active">Usuarios</a>
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

<!-- CONTENIDO -->
<main class="admin-main">

    <!-- TÍTULO Y ALERTAS -->
    <div class="page-header">
        <h1>👥 Gestión de Usuarios</h1>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                ✓ <?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error">
                ✗ <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- ESTADÍSTICAS -->
    <div class="quick-stats">
        <div class="stat-mini">
            <span class="stat-value"><?php echo $totalUsuarios; ?></span>
            <span class="stat-label">Total Usuarios</span>
        </div>
        <div class="stat-mini">
            <span class="stat-value"><?php echo $totalActivos; ?></span>
            <span class="stat-label">Activos</span>
        </div>
        <div class="stat-mini">
            <span class="stat-value"><?php echo $totalAdmins; ?></span>
            <span class="stat-label">Admins</span>
        </div>
        <div class="stat-mini">
            <span class="stat-value"><?php echo $totalUsuarios - $totalAdmins; ?></span>
            <span class="stat-label">Clientes</span>
        </div>
    </div>

    <!-- FILTROS -->
    <div class="actions-bar">
        <form method="GET" class="filter-form">
            <input type="text"
                   name="buscar"
                   class="search-input"
                   placeholder="Buscar por nombre o correo..."
                   value="<?php echo htmlspecialchars($busqueda); ?>">

            <select name="rol" class="filter-select" onchange="this.form.submit()">
                <option value="">Todos los roles</option>
                <option value="ADMIN" <?php echo $filtroRol === 'ADMIN' ? 'selected' : ''; ?>>Admin</option>
                <option value="CLIENTE" <?php echo $filtroRol === 'CLIENTE' ? 'selected' : ''; ?>>Cliente</option>
            </select>

            <button type="submit" class="btn btn-primary">Buscar</button>

            <?php if ($busqueda || $filtroRol): ?>
                <a href="usuarios.php" class="btn btn-secondary">Limpiar</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- TABLA -->
    <div class="table-container">
        <?php if (empty($usuarios)): ?>
            <div class="empty-state">
                👤 No se encontraron usuarios
            </div>
        <?php else: ?>
            <table class="users-table">
                <thead>
                    <tr>
                        <th>Usuario</th>
                        <th>Correo</th>
                        <th>Rol</th>
                        <th>Estado</th>
                        <th>Email</th>
                        <th>Registro</th>
                        <th>Acción</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($usuarios as $u): ?>
                        <?php
                            $inicial   = strtoupper(mb_substr($u['nombre'], 0, 1));
                            $rolUpper  = strtoupper($u['rol']);
                            $esMismoAdmin = ($u['id_usuario'] == $_SESSION['id_usuario']);
                        ?>
                        <tr>
                            <!-- Nombre + avatar -->
                            <td>
                                <div class="user-cell">
                                    <div class="user-avatar"><?php echo htmlspecialchars($inicial); ?></div>
                                    <div>
                                        <div class="user-name">
                                            <?php echo htmlspecialchars($u['nombre'] . ' ' . $u['apellido']); ?>
                                        </div>
                                        <div class="user-id">#<?php echo $u['id_usuario']; ?></div>
                                    </div>
                                </div>
                            </td>

                            <!-- Correo -->
                            <td><?php echo htmlspecialchars($u['correo']); ?></td>

                            <!-- Rol -->
                            <td>
                                <span class="badge badge-<?php echo $rolUpper; ?>">
                                    <?php echo $rolUpper; ?>
                                </span>
                            </td>

                            <!-- Estado -->
                            <td>
                                <span class="badge badge-<?php echo $u['estado']; ?>">
                                    <?php echo ucfirst($u['estado']); ?>
                                </span>
                            </td>

                            <!-- Email verificado -->
                            <td>
                                <?php if ($u['email_verificado']): ?>
                                    <span class="badge badge-verificado">✓ Verificado</span>
                                <?php else: ?>
                                    <span class="badge badge-no-verificado">⚠ Pendiente</span>
                                <?php endif; ?>
                            </td>

                            <!-- Fecha registro -->
                            <td><?php echo date('d/m/Y', strtotime($u['fecha_creacion'])); ?></td>

                            <!-- Acción cambio de rol -->
                            <td>
                                <?php if ($esMismoAdmin): ?>
                                    <span class="btn-rol btn-rol-disabled" title="No puedes cambiar tu propio rol">
                                        🔒 Tú
                                    </span>
                                <?php else: ?>
                                    <a href="?toggle_rol=<?php echo $u['id_usuario']; ?>"
                                       class="btn-rol"
                                       onclick="return confirm('¿Cambiar rol de <?php echo htmlspecialchars($u['nombre']); ?> a <?php echo $rolUpper === 'ADMIN' ? 'USER' : 'ADMIN'; ?>?')"
                                       title="Cambiar a <?php echo $rolUpper === 'ADMIN' ? 'USER' : 'ADMIN'; ?>">
                                        <?php echo $rolUpper === 'ADMIN' ? '⬇ Quitar Admin' : '⬆ Hacer Admin'; ?>
                                    <a href="?delete=<?php echo $u['id_usuario']; ?>"
                                        class="btn-rol"
                                        style="background:#3a1a1a; border-color:#f44336; color:#f44336; margin-left:0.4rem;"
                                        onclick="return confirm('¿Eliminar a <?php echo htmlspecialchars($u['nombre']); ?>? Esta acción no se puede deshacer.')">
                                            🗑️ Eliminar
                                    </a>
                                    </a>
                                <?php endif; ?>
                            </td>
                            
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

</main>

<script>
// Auto-ocultar alertas
setTimeout(() => {
    document.querySelectorAll('.alert').forEach(a => {
        a.style.transition = 'opacity 0.3s';
        a.style.opacity = '0';
        setTimeout(() => a.remove(), 300);
    });
}, 4000);
</script>

</body>
</html>