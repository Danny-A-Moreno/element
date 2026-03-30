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
$accion = $_POST['accion'] ?? null;

if (!$idItem || !in_array($accion, ['mas', 'menos'])) {
    header('Location: carrito.php');
    exit;
}

$db = new BaseDatos();

try {
    // Obtener info del item y su stock disponible
    $sql = "SELECT ic.cantidad, pv.stock 
            FROM item_carrito ic
            INNER JOIN productos_variante pv ON ic.id_variante = pv.id_variante
            WHERE ic.id_item = :id";
    
    $item = $db->query($sql, ['id' => $idItem])->fetch();
    
    if (!$item) {
        $_SESSION['error_carrito'] = "Producto no encontrado";
        header('Location: carrito.php');
        exit;
    }
    
    if ($accion === 'mas') {
        $nuevaCantidad = $item['cantidad'] + 1;
        
        // Verificar stock disponible
        if ($nuevaCantidad > $item['stock']) {
            $_SESSION['error_carrito'] = "Stock insuficiente. Máximo " . $item['stock'] . " unidades";
            header('Location: carrito.php');
            exit;
        }
        
        $sqlUpdate = "UPDATE item_carrito 
                     SET cantidad = cantidad + 1 
                     WHERE id_item = :id";
        
        $db->query($sqlUpdate, ['id' => $idItem]);
    }
    
    if ($accion === 'menos') {
        if ($item['cantidad'] <= 1) {
            // Si la cantidad es 1, eliminar el item
            $sqlDelete = "DELETE FROM item_carrito WHERE id_item = :id";
            $db->query($sqlDelete, ['id' => $idItem]);
        } else {
            // Disminuir cantidad
            $sqlUpdate = "UPDATE item_carrito 
                         SET cantidad = cantidad - 1 
                         WHERE id_item = :id";
            
            $db->query($sqlUpdate, ['id' => $idItem]);
        }
    }
    
    $_SESSION['success_carrito'] = "Carrito actualizado";
    
} catch (Exception $e) {
    error_log("Error en carrito-actualizar.php: " . $e->getMessage());
    $_SESSION['error_carrito'] = "Error al actualizar el carrito";
}

header('Location: carrito.php');
exit;
?>