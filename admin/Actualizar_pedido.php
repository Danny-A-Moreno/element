<?php
session_start();

if (!isset($_SESSION['id_usuario']) || strtolower($_SESSION['rol']) !== 'admin') {
    header('Location: ../index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: pedidos.php');
    exit();
}

require_once __DIR__ . '/../BaseDatos.php';
$db = new BaseDatos();

$id_pedido    = (int) ($_POST['id_pedido']    ?? 0);
$nuevo_estado = trim($_POST['nuevo_estado']   ?? '');
$filtro       = $_POST['filtro'] ?? 'todos';

$estados_validos = ['pendiente_pago','pendiente','procesando','enviado','completado','cancelado','eliminar'];

if (!$id_pedido || !in_array($nuevo_estado, $estados_validos)) {
    $_SESSION['flash_error'] = "Datos inválidos.";
    header('Location: pedidos.php?estado=' . urlencode($filtro));
    exit();
}

// Eliminar pedido
if ($nuevo_estado === 'eliminar') {
    $db->query("DELETE FROM pedido_items WHERE id_pedido = :id", ['id' => $id_pedido]);
    $db->query("DELETE FROM pedidos WHERE id_pedido = :id",      ['id' => $id_pedido]);
    $_SESSION['flash_ok'] = "Pedido #$id_pedido eliminado correctamente.";
    header('Location: pedidos.php?estado=' . urlencode($filtro));
    exit();
}

// Verificar que el pedido existe
$pedido = $db->query(
    "SELECT id_pedido, estado FROM pedidos WHERE id_pedido = :id",
    ['id' => $id_pedido]
)->fetch();

if (!$pedido) {
    $_SESSION['flash_error'] = "Pedido #$id_pedido no encontrado.";
    header('Location: pedidos.php?estado=' . urlencode($filtro));
    exit();
}

// Actualizar estado
$db->query(
    "UPDATE pedidos SET estado = :estado WHERE id_pedido = :id",
    ['estado' => $nuevo_estado, 'id' => $id_pedido]
);

$etiquetas = [
    'pendiente_pago' => 'Pago pendiente',
    'pendiente'      => 'Pendiente',
    'procesando'     => 'Procesando',
    'enviado'        => 'Enviado',
    'completado'     => 'Completado',
    'cancelado'      => 'Cancelado',
];

$_SESSION['flash_ok'] = "Pedido #$id_pedido actualizado a: " . ($etiquetas[$nuevo_estado] ?? $nuevo_estado);
header('Location: pedidos.php?estado=' . urlencode($filtro));
exit();