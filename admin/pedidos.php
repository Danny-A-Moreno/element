<?php
session_start();

if (!isset($_SESSION['id_usuario']) || strtolower($_SESSION['rol']) !== 'admin') {
    header('Location: ../index.php');
    exit();
}

require_once __DIR__ . '/../BaseDatos.php';
$db = new BaseDatos();

// ── Filtro de estado ──────────────────────────────────────────────────────────
$filtro_estado = $_GET['estado'] ?? 'todos';
$estados_validos = ['todos','pendiente_pago','pendiente','procesando','enviado','completado','cancelado'];
if (!in_array($filtro_estado, $estados_validos)) $filtro_estado = 'todos';

$where  = $filtro_estado !== 'todos' ? "WHERE p.estado = :estado" : "";
$params = $filtro_estado !== 'todos' ? ['estado' => $filtro_estado] : [];

// ── Contadores por estado ─────────────────────────────────────────────────────
$contadores = [];
foreach ($db->query("SELECT estado, COUNT(*) as total FROM pedidos GROUP BY estado", [])->fetchAll() as $row) {
    $contadores[$row['estado']] = $row['total'];
}
$totalTodos = array_sum($contadores);

// ── Lista de pedidos ──────────────────────────────────────────────────────────
$sqlPedidos = "
    SELECT
        p.id_pedido,
        p.total,
        p.estado,
        p.metodo_pago,
        p.direccion_envio,
        p.telefono,
        p.notas,
        p.comprobante_pago,
        p.fecha_creacion,
        u.nombre,
        u.apellido,
        u.correo
    FROM pedidos p
    JOIN usuarios u ON p.id_usuario = u.id_usuario
    $where
    ORDER BY p.fecha_creacion DESC
";
$pedidos = $db->query($sqlPedidos, $params)->fetchAll();

// ── Flash ─────────────────────────────────────────────────────────────────────
$flash_ok    = $_SESSION['flash_ok']    ?? null; unset($_SESSION['flash_ok']);
$flash_error = $_SESSION['flash_error'] ?? null; unset($_SESSION['flash_error']);

// ── Badge de estado ───────────────────────────────────────────────────────────
function badgeEstado(string $estado): string {
    $map = [
        'pendiente_pago' => ['Pago pendiente', '#f59e0b'],
        'pendiente'      => ['Pendiente',      '#3b82f6'],
        'procesando'     => ['Procesando',     '#a855f7'],
        'enviado'        => ['Enviado',        '#06b6d4'],
        'completado'     => ['Completado',     '#22c55e'],
        'cancelado'      => ['Cancelado',      '#ef4444'],
    ];
    [$label, $color] = $map[$estado] ?? [ucfirst($estado), '#888'];
    return "<span style=\"color:{$color};font-weight:600;font-size:0.82rem;\">{$label}</span>";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Pedidos - ELEMENT</title>
    <link rel="icon" type="image/png" href="../imagenes/logos/Element.ico">
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="dashboard.css">
    <link rel="stylesheet" href="productos.css">
    <style>
        /* ── Tabs de filtro ── */
        .filter-tabs {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
            margin-bottom: 1.5rem;
        }
        .tab {
            padding: 0.4rem 1rem;
            border-radius: 20px;
            font-size: 0.82rem;
            font-weight: 500;
            text-decoration: none;
            border: 1.5px solid #333;
            color: #aaa;
            background: transparent;
            transition: all .2s;
            white-space: nowrap;
        }
        .tab:hover { border-color: #c9a84c; color: #c9a84c; }
        .tab.active { background: #c9a84c; color: #000; border-color: #c9a84c; font-weight: 700; }
        .tab-count {
            display: inline-block;
            background: rgba(255,255,255,.12);
            border-radius: 10px;
            padding: 0 6px;
            margin-left: 3px;
            font-size: 0.75rem;
        }
        .tab.active .tab-count { background: rgba(0,0,0,.2); }

        /* ── Botones de acción ── */
        .btn-action {
            padding: .35rem .75rem;
            border: none;
            border-radius: 6px;
            font-size: .78rem;
            font-weight: 600;
            cursor: pointer;
            font-family: inherit;
            transition: all .2s;
        }
        .btn-confirmar { background: rgba(34,197,94,.15); color: #22c55e; }
        .btn-confirmar:hover { background: #22c55e; color: #000; }
        .btn-rechazar  { background: rgba(239,68,68,.15); color: #ef4444; }
        .btn-rechazar:hover  { background: #ef4444; color: #fff; }
        .btn-estado    { background: rgba(168,85,247,.15); color: #a855f7; }
        .btn-estado:hover    { background: #a855f7; color: #fff; }
        .btn-ver {
            background: rgba(201,168,76,.15); color: #c9a84c;
            border: none; border-radius: 6px; padding: .35rem .75rem;
            font-size: .78rem; font-weight: 600; cursor: pointer;
            font-family: inherit; transition: all .2s;
        }
        .btn-ver:hover { background: #c9a84c; color: #000; }

        .acciones { display: flex; gap: .35rem; flex-wrap: wrap; }

        /* ── Modal ── */
        .modal-overlay {
            display: none; position: fixed; inset: 0;
            background: rgba(0,0,0,.78); z-index: 1000;
            align-items: center; justify-content: center;
        }
        .modal-overlay.open { display: flex; }
        .modal-box {
            background: #1a1a1a; border: 1px solid #333;
            border-radius: 14px; padding: 1.5rem;
            max-width: 480px; width: 92%;
            max-height: 90vh; overflow-y: auto; position: relative;
        }
        .modal-box h3 { margin: 0 0 1.25rem; font-size: 1rem; font-weight: 700; color: #c9a84c; }
        .modal-close {
            position: absolute; top: 1rem; right: 1rem;
            background: #2a2a2a; border: none; border-radius: 50%;
            width: 30px; height: 30px; cursor: pointer; color: #aaa;
            font-size: .9rem; display: flex; align-items: center; justify-content: center;
        }
        .modal-close:hover { background: #333; color: #fff; }

        .detail-table { width: 100%; border-collapse: collapse; font-size: .85rem; }
        .detail-table td { padding: .4rem 0; vertical-align: top; border-bottom: 1px solid #222; }
        .detail-table tr:last-child td { border-bottom: none; }
        .detail-table td:first-child { color: #777; width: 120px; }
        .detail-table td:last-child { color: #ddd; }

        .modal-box img {
            width: 100%; border-radius: 10px;
            border: 1px solid #333; margin-top: .75rem;
        }

        /* Modal estado */
        .modal-estado { max-width: 360px; }
        .modal-estado select {
            width: 100%; padding: .7rem .9rem;
            background: #111; border: 1.5px solid #333;
            border-radius: 8px; color: #ddd;
            font-size: .9rem; font-family: inherit;
            outline: none; margin: 1rem 0;
        }
        .modal-estado select:focus { border-color: #c9a84c; }
        .modal-estado .btn-save {
            width: 100%; padding: .8rem;
            background: #c9a84c; color: #000;
            border: none; border-radius: 9px;
            font-size: .9rem; font-weight: 700;
            cursor: pointer; font-family: inherit;
        }
        .modal-estado .btn-save:hover { background: #b8973b; }

        /* Flash */
        .flash { padding: .8rem 1rem; border-radius: 8px; margin-bottom: 1.25rem; font-size: .88rem; font-weight: 500; }
        .flash-ok    { background: rgba(34,197,94,.12); color: #22c55e; border: 1px solid rgba(34,197,94,.3); }
        .flash-error { background: rgba(239,68,68,.12); color: #ef4444; border: 1px solid rgba(239,68,68,.3); }

        .empty-msg { text-align: center; padding: 3rem; color: #555; font-size: .95rem; }

        .link-pdf {
            display: inline-block; padding: .5rem 1rem;
            background: rgba(168,85,247,.15); color: #a855f7;
            border-radius: 8px; text-decoration: none;
            font-size: .83rem; font-weight: 600; margin-top: .75rem;
        }
        .link-pdf:hover { background: #a855f7; color: #fff; }

        @media(max-width:768px){
            thead th:nth-child(3), td:nth-child(3),
            thead th:nth-child(4), td:nth-child(4) { display: none; }
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
            <a href="pedidos.php"   class="nav-link active">Pedidos</a>
            <a href="usuarios.php"  class="nav-link">Usuarios</a>
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

    <!-- TÍTULO -->
    <div class="page-header">
        <h1>📋 Gestión de Pedidos</h1>

        <?php if ($flash_ok):    ?><div class="flash flash-ok"><?= htmlspecialchars($flash_ok) ?></div><?php endif; ?>
        <?php if ($flash_error): ?><div class="flash flash-error"><?= htmlspecialchars($flash_error) ?></div><?php endif; ?>
    </div>

    <!-- ESTADÍSTICAS RÁPIDAS -->
    <div class="quick-stats">
        <div class="stat-mini">
            <span class="stat-value"><?= $totalTodos ?></span>
            <span class="stat-label">Total Pedidos</span>
        </div>
        <div class="stat-mini">
            <span class="stat-value" style="color:#f59e0b;"><?= $contadores['pendiente_pago'] ?? 0 ?></span>
            <span class="stat-label">Pago Pendiente</span>
        </div>
        <div class="stat-mini">
            <span class="stat-value" style="color:#22c55e;"><?= $contadores['completado'] ?? 0 ?></span>
            <span class="stat-label">Completados</span>
        </div>
        <div class="stat-mini">
            <span class="stat-value" style="color:#ef4444;"><?= $contadores['cancelado'] ?? 0 ?></span>
            <span class="stat-label">Cancelados</span>
        </div>
    </div>

    <!-- TABS DE FILTRO -->
    <div class="filter-tabs">
        <a href="?estado=todos" class="tab <?= $filtro_estado==='todos'?'active':'' ?>">
            Todos <span class="tab-count"><?= $totalTodos ?></span>
        </a>
        <?php
        $tabs = [
            'pendiente_pago' => '🟡 Pago pendiente',
            'pendiente'      => '🔵 Pendiente',
            'procesando'     => '🟣 Procesando',
            'enviado'        => '🔵 Enviado',
            'completado'     => '🟢 Completado',
            'cancelado'      => '🔴 Cancelado',
        ];
        foreach ($tabs as $key => $label):
            $count = $contadores[$key] ?? 0;
        ?>
            <a href="?estado=<?= $key ?>" class="tab <?= $filtro_estado===$key?'active':'' ?>">
                <?= $label ?> <span class="tab-count"><?= $count ?></span>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- TABLA -->
    <div class="table-container">
        <?php if (empty($pedidos)): ?>
            <p class="empty-msg">No hay pedidos en esta categoría.</p>
        <?php else: ?>
        <table class="products-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Cliente</th>
                    <th>Total</th>
                    <th>Pago</th>
                    <th>Estado</th>
                    <th>Fecha</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($pedidos as $p): ?>
                <tr>
                    <td><strong>#<?= $p['id_pedido'] ?></strong></td>
                    <td>
                        <strong><?= htmlspecialchars($p['nombre'].' '.$p['apellido']) ?></strong>
                        <div style="font-size:.78rem;color:#666;"><?= htmlspecialchars($p['correo']) ?></div>
                    </td>
                    <td><strong>$<?= number_format($p['total'], 0, ',', '.') ?></strong></td>
                    <td style="font-size:.83rem;"><?= htmlspecialchars(ucfirst($p['metodo_pago'])) ?></td>
                    <td><?= badgeEstado($p['estado']) ?></td>
                    <td style="font-size:.8rem;color:#666;white-space:nowrap;">
                        <?= date('d/m/Y H:i', strtotime($p['fecha_creacion'])) ?>
                    </td>
                    <td>
                        <div class="acciones">

                            <!-- Ver detalle -->
                            <button class="btn-ver"
                                    onclick="verDetalle(<?= htmlspecialchars(json_encode($p)) ?>)">
                                🔍 Ver
                            </button>

                            <!-- Confirmar -->
                            <?php if (in_array($p['estado'], ['pendiente_pago','pendiente'])): ?>
                                <form method="POST" action="actualizar_pedido.php" style="margin:0;"
                                      onsubmit="return confirm('¿Confirmar y aprobar este pedido?')">
                                    <input type="hidden" name="id_pedido"    value="<?= $p['id_pedido'] ?>">
                                    <input type="hidden" name="nuevo_estado" value="procesando">
                                    <input type="hidden" name="filtro"       value="<?= $filtro_estado ?>">
                                    <button type="submit" class="btn-action btn-confirmar">✓ Confirmar</button>
                                </form>
                            <?php endif; ?>

                            <!-- Cancelar -->
                            <?php if (!in_array($p['estado'], ['completado','cancelado'])): ?>
                                <form method="POST" action="actualizar_pedido.php" style="margin:0;"
                                      onsubmit="return confirm('¿Cancelar este pedido?')">
                                    <input type="hidden" name="id_pedido"    value="<?= $p['id_pedido'] ?>">
                                    <input type="hidden" name="nuevo_estado" value="cancelado">
                                    <input type="hidden" name="filtro"       value="<?= $filtro_estado ?>">
                                    <button type="submit" class="btn-action btn-rechazar">✕ Cancelar</button>
                                </form>
                            <?php endif; ?>

                            <!-- Cambiar estado libre -->
                            <button class="btn-action btn-estado"
                                    onclick="abrirModalEstado(<?= $p['id_pedido'] ?>, '<?= $p['estado'] ?>', '<?= $filtro_estado ?>')">
                                ↕ Estado
                            </button>
                            <!-- Eliminar pedido -->
                            <form method="POST" action="actualizar_pedido.php" style="margin:0;"
                                onsubmit="return confirm('¿Eliminar este pedido permanentemente?')">
                                <input type="hidden" name="id_pedido"    value="<?= $p['id_pedido'] ?>">
                                <input type="hidden" name="nuevo_estado" value="eliminar">
                                <input type="hidden" name="filtro"       value="<?= $filtro_estado ?>">
                                <button type="submit" class="btn-action btn-rechazar">🗑️</button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

</main>

<!-- ── Modal Detalle ── -->
<div class="modal-overlay" id="modalDetalle" onclick="cerrarModal('modalDetalle')">
    <div class="modal-box" onclick="event.stopPropagation()">
        <button class="modal-close" onclick="cerrarModal('modalDetalle')">✕</button>
        <h3 id="mdTitulo">Detalle del pedido</h3>
        <div id="mdContenido"></div>
    </div>
</div>

<!-- ── Modal Cambiar Estado ── -->
<div class="modal-overlay" id="modalEstado" onclick="cerrarModal('modalEstado')">
    <div class="modal-box modal-estado" onclick="event.stopPropagation()">
        <button class="modal-close" onclick="cerrarModal('modalEstado')">✕</button>
        <h3>Cambiar estado</h3>
        <form method="POST" action="actualizar_pedido.php">
            <input type="hidden" name="id_pedido" id="meIdPedido">
            <input type="hidden" name="filtro"    id="meFiltro">
            <select name="nuevo_estado" id="meSelect">
                <option value="pendiente_pago">🟡 Pago pendiente</option>
                <option value="pendiente">🔵 Pendiente</option>
                <option value="procesando">🟣 Procesando</option>
                <option value="enviado">🔵 Enviado</option>
                <option value="completado">🟢 Completado</option>
                <option value="cancelado">🔴 Cancelado</option>
            </select>
            <button type="submit" class="btn-save">Guardar cambio</button>
        </form>
    </div>
</div>

<script>
function verDetalle(p) {
    document.getElementById('mdTitulo').textContent = 'Pedido #' + p.id_pedido;

    let html = `<table class="detail-table">
        <tr><td>Cliente</td>    <td><strong>${p.nombre} ${p.apellido}</strong></td></tr>
        <tr><td>Correo</td>     <td>${p.correo}</td></tr>
        <tr><td>Teléfono</td>   <td>${p.telefono || '—'}</td></tr>
        <tr><td>Dirección</td>  <td>${p.direccion_envio || '—'}</td></tr>
        <tr><td>Método pago</td><td>${p.metodo_pago}</td></tr>
        <tr><td>Total</td>      <td><strong style="color:#c9a84c;">$${parseInt(p.total).toLocaleString('es-CO')}</strong></td></tr>
        ${p.notas ? `<tr><td>Notas</td><td style="font-style:italic;color:#aaa;">${p.notas}</td></tr>` : ''}
    </table>`;

    if (p.comprobante_pago) {
        const ext = p.comprobante_pago.split('.').pop().toLowerCase();
        html += `<div style="margin-top:1rem;">
                    <p style="font-size:.78rem;color:#777;font-weight:600;margin:0 0 .4rem;text-transform:uppercase;">Comprobante de pago</p>`;
        if (ext === 'pdf') {
            html += `<a href="../${p.comprobante_pago}" target="_blank" class="link-pdf">📄 Ver PDF del comprobante</a>`;
        } else {
            html += `<img src="../${p.comprobante_pago}" alt="Comprobante"
                         onerror="this.parentElement.innerHTML='<p style=color:#ef4444;font-size:.83rem>No se pudo cargar la imagen.</p>'">`;
        }
        html += `</div>`;
    } else if (p.metodo_pago === 'nequi') {
        html += `<p style="color:#ef4444;font-size:.83rem;margin-top:1rem;">⚠️ Sin comprobante adjunto</p>`;
    }

    document.getElementById('mdContenido').innerHTML = html;
    document.getElementById('modalDetalle').classList.add('open');
}

function abrirModalEstado(idPedido, estadoActual, filtro) {
    document.getElementById('meIdPedido').value = idPedido;
    document.getElementById('meFiltro').value   = filtro;
    document.getElementById('meSelect').value   = estadoActual;
    document.getElementById('modalEstado').classList.add('open');
}

function cerrarModal(id) {
    document.getElementById(id).classList.remove('open');
}

document.addEventListener('keydown', e => {
    if (e.key === 'Escape') {
        cerrarModal('modalDetalle');
        cerrarModal('modalEstado');
    }
});

setTimeout(() => {
    document.querySelectorAll('.flash').forEach(el => {
        el.style.opacity = '0';
        el.style.transition = 'opacity .3s';
        setTimeout(() => el.remove(), 300);
    });
}, 5000);
</script>

</body>
</html>