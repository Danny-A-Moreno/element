<?php
session_start();

if (!isset($_SESSION['id_usuario']) || $_SESSION['rol'] !== 'admin') {
    header('Location: index.php?acceso=denegado');
    exit;
}
require_once __DIR__ . '/helpers/admin.php';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel Administrador</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<h1>Panel de Administración</h1>

<ul class="admin-menu">
    <li><a href="admin-productos.php">Gestionar productos</a></li>
    <li><a href="admin-pedidos.php">Pedidos</a></li>
    <li><a href="admin-descuentos.php">Descuentos</a></li>
    <li><a href="admin-estadisticas.php">Estadísticas</a></li>
</ul>

</body>
</html>
