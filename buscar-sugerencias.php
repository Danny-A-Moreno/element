<?php
// API para sugerencias de búsqueda en tiempo real
header('Content-Type: application/json');

require_once __DIR__ . '/BaseDatos.php';
$db = new BaseDatos();

$query = trim($_GET['q'] ?? '');

if (strlen($query) < 2) {
    echo json_encode([]);
    exit();
}

try {
    $sql = "SELECT 
                p.id_producto,
                p.nombre,
                p.precio,
                (SELECT nombre_archivo 
                 FROM producto_imagenes 
                 WHERE id_producto = p.id_producto 
                 AND es_principal = 1 
                 LIMIT 1) as imagen_principal
            FROM productos p
            WHERE p.estado = 'activo'
            AND (p.nombre LIKE :query OR p.descripcion LIKE :query)
            ORDER BY p.fecha_creacion DESC
            LIMIT 5";
    
    $resultados = $db->query($sql, [
        'query' => '%' . $query . '%'
    ])->fetchAll();
    
    echo json_encode($resultados);
    
} catch (Exception $e) {
    error_log("Error en sugerencias: " . $e->getMessage());
    echo json_encode([]);
}