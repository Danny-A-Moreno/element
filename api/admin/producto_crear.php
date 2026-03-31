<?php
session_start();

// Protección: solo admin puede acceder
if (!isset($_SESSION['id_usuario']) || strtolower($_SESSION['rol']) !== 'admin') {
    header('Location: ../index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: productos.php');
    exit();
}

require_once __DIR__ . '/../BaseDatos.php';
$db = new BaseDatos();

// Obtener datos del formulario
$nombre = trim($_POST['nombre'] ?? '');
$descripcion = trim($_POST['descripcion'] ?? '');
$precio = floatval($_POST['precio'] ?? 0);
$estado = $_POST['estado'] ?? 'activo';

// Validaciones
if (empty($nombre)) {
    $_SESSION['error'] = "El nombre del producto es obligatorio";
    header('Location: productos.php');
    exit();
}

if ($precio <= 0) {
    $_SESSION['error'] = "El precio debe ser mayor a 0";
    header('Location: productos.php');
    exit();
}

try {
    // Insertar producto
    $sql = "INSERT INTO productos (nombre, descripcion, precio, estado, fecha_creacion, fecha_actualizacion) 
            VALUES (:nombre, :descripcion, :precio, :estado, NOW(), NOW())";
    
    $db->query($sql, [
        'nombre' => $nombre,
        'descripcion' => $descripcion,
        'precio' => $precio,
        'estado' => $estado
    ]);
    
    $idProducto = $db->pdo->lastInsertId();
    
    $_SESSION['success'] = "Producto '$nombre' creado exitosamente. Ahora puedes agregar variantes.";
    header("Location: producto_variantes.php?id=$idProducto");
    exit();
    
} catch (Exception $e) {
    $_SESSION['error'] = "Error al crear producto: " . $e->getMessage();
    header('Location: productos.php');
    exit();
}
?>