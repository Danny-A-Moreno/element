<?php
require_once __DIR__ . '/helpers/admin.php';
require_once __DIR__ . '/BaseDatos.php';

$db = new BaseDatos();
$productos = $db->obtenerProductos();

<h2>Productos</h2>

<a href="admin-producto-crear.php">+ Nuevo producto</a>

<table>
    <tr>
        <th>Nombre</th>
        <th>Precio</th>
        <th>Stock</th>
        <th>Acciones</th>
    </tr>

<?php foreach ($productos as $p): ?>
<tr>
    <td><?= htmlspecialchars($p['nombre']) ?></td>
    <td>$<?= number_format($p['precio'], 2) ?></td>
    <td><?= $p['stock'] ?></td>
    <td>
        <a href="admin-producto-editar.php?id=<?= $p['id_producto'] ?>">Editar</a>
        <a href="admin-producto-eliminar.php?id=<?= $p['id_producto'] ?>">Eliminar</a>
    </td>
</tr>
<?php endforeach; ?>
</table>
