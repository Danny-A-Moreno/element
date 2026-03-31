<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['id_usuario'])) {
    header('Location: carrito.php');
    exit;
}

require_once __DIR__ . '/BaseDatos.php';

$idItem = intval($_POST['id_item'] ?? 0);

if (!$idItem) {
    header('Location: carrito.php');
    exit;
}

$db = new BaseDatos();

try {
    // Verificar que el item pertenece al usuario
    $sqlVerificar = "SELECT ic.id_item 
                    FROM item_carrito ic
                    INNER JOIN carrito c ON ic.id_carrito = c.id_carrito
                    WHERE ic.id_item = :id AND c.id_usuario = :usuario";
    
    $existe = $db->query($sqlVerificar, [
        'id' => $idItem,
        'usuario' => $_SESSION['id_usuario']
    ])->fetch();
    
    if (!$existe) {
        $_SESSION['error_carrito'] = "No tienes permiso para eliminar este producto";
        header('Location: carrito.php');
        exit;
    }
    
    // Eliminar el item
    $sqlDelete = "DELETE FROM item_carrito WHERE id_item = :id";
    $db->query($sqlDelete, ['id' => $idItem]);
    
    $_SESSION['success_carrito'] = "Producto eliminado del carrito";
    
} catch (Exception $e) {
    error_log("Error en carrito-eliminar.php: " . $e->getMessage());
    $_SESSION['error_carrito'] = "Error al eliminar el producto";
}

header('Location: carrito.php');
exit;
?>