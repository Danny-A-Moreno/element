<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

require_once __DIR__ . '/BaseDatos.php';
$db = new BaseDatos();

// Verificar que sea una petición POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit();
}

// Obtener datos JSON
$json = file_get_contents('php://input');
$data = json_decode($json, true);

$id_variante = intval($data['id_variante'] ?? 0);
$cantidad = intval($data['cantidad'] ?? 1);

// Validaciones
if ($id_variante <= 0) {
    echo json_encode(['success' => false, 'message' => 'Variante inválida']);
    exit();
}

if ($cantidad <= 0) {
    echo json_encode(['success' => false, 'message' => 'Cantidad inválida']);
    exit();
}

try {
    // Verificar que la variante existe y tiene stock
    $sqlVariante = "SELECT pv.*, p.nombre, p.precio 
                    FROM productos_variante pv
                    INNER JOIN productos p ON pv.id_producto = p.id_producto
                    WHERE pv.id_variante = :id AND pv.estado = 'activo' AND p.estado = 'activo'";
    
    $variante = $db->query($sqlVariante, ['id' => $id_variante])->fetch();
    
    if (!$variante) {
        echo json_encode(['success' => false, 'message' => 'Producto no disponible']);
        exit();
    }
    
    if ($variante['stock'] < $cantidad) {
        echo json_encode([
            'success' => false, 
            'message' => 'Stock insuficiente. Solo quedan ' . $variante['stock'] . ' unidades'
        ]);
        exit();
    }
    
    // ===== USUARIO LOGUEADO =====
    if (isset($_SESSION['id_usuario'])) {
        $id_usuario = $_SESSION['id_usuario'];
        
        // 1. Obtener o crear carrito activo
        $sqlCarrito = "SELECT id_carrito FROM carrito 
                      WHERE id_usuario = :usuario AND estado = 'activo'
                      LIMIT 1";
        
        $carrito = $db->query($sqlCarrito, ['usuario' => $id_usuario])->fetch();
        
        if (!$carrito) {
            // Crear nuevo carrito
            $sqlNuevoCarrito = "INSERT INTO carrito (id_usuario, fecha_creacion, estado) 
                               VALUES (:usuario, NOW(), 'activo')";
            $db->query($sqlNuevoCarrito, ['usuario' => $id_usuario]);
            $id_carrito = $db->getLastInsertId();
        } else {
            $id_carrito = $carrito['id_carrito'];
        }
        
        // 2. Verificar si ya existe el item en el carrito
        $sqlExisteItem = "SELECT id_item, cantidad 
                         FROM item_carrito 
                         WHERE id_carrito = :carrito AND id_variante = :variante";
        
        $itemExistente = $db->query($sqlExisteItem, [
            'carrito' => $id_carrito,
            'variante' => $id_variante
        ])->fetch();
        
        if ($itemExistente) {
            // Actualizar cantidad
            $nuevaCantidad = $itemExistente['cantidad'] + $cantidad;
            
            if ($nuevaCantidad > $variante['stock']) {
                echo json_encode([
                    'success' => false,
                    'message' => 'No puedes agregar más unidades. Stock máximo: ' . $variante['stock']
                ]);
                exit();
            }
            
            $sqlUpdateItem = "UPDATE item_carrito 
                            SET cantidad = :cantidad 
                            WHERE id_item = :id";
            
            $db->query($sqlUpdateItem, [
                'cantidad' => $nuevaCantidad,
                'id' => $itemExistente['id_item']
            ]);
            
        } else {
            // Insertar nuevo item
            $sqlInsertItem = "INSERT INTO item_carrito 
                             (id_carrito, id_variante, cantidad, precio_unitario) 
                             VALUES (:carrito, :variante, :cantidad, :precio)";
            
            $db->query($sqlInsertItem, [
                'carrito' => $id_carrito,
                'variante' => $id_variante,
                'cantidad' => $cantidad,
                'precio' => $variante['precio']
            ]);
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Producto agregado al carrito'
        ]);
        
    } 
    // ===== USUARIO NO LOGUEADO (CARRITO EN SESIÓN) =====
    else {
        if (!isset($_SESSION['carrito_temporal'])) {
            $_SESSION['carrito_temporal'] = [];
        }
        
        // Buscar si ya existe en el carrito temporal
        $encontrado = false;
        foreach ($_SESSION['carrito_temporal'] as &$item) {
            if ($item['id_variante'] == $id_variante) {
                $item['cantidad'] += $cantidad;
                
                if ($item['cantidad'] > $variante['stock']) {
                    echo json_encode([
                        'success' => false,
                        'message' => 'No puedes agregar más unidades. Stock máximo: ' . $variante['stock']
                    ]);
                    exit();
                }
                
                $encontrado = true;
                break;
            }
        }
        
        if (!$encontrado) {
            $_SESSION['carrito_temporal'][] = [
                'id_variante' => $id_variante,
                'cantidad' => $cantidad,
                'nombre' => $variante['nombre'],
                'precio' => $variante['precio'],
                'color' => $variante['color'],
                'talla' => $variante['talla']
            ];
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Producto agregado al carrito. Inicia sesión para finalizar tu compra.'
        ]);
    }
    
} catch (Exception $e) {
    error_log("Error en carrito-agregar.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error al agregar al carrito: ' . $e->getMessage()
    ]);
}
?>