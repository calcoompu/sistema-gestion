<?php
/**
 * API para obtener lista de productos
 */
require_once '../config/config.php';

iniciarSesionSegura();

header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

try {
    $pdo = conectarDB();
    
    // Obtener productos con información de categoría
    $sql = "SELECT p.id, p.codigo, p.nombre, p.stock, p.precio_venta, 
                   c.nombre as categoria
            FROM productos p 
            LEFT JOIN categorias c ON p.categoria_id = c.id 
            WHERE p.activo = 1 
            ORDER BY p.nombre";
    
    $stmt = $pdo->query($sql);
    $productos = $stmt->fetchAll();
    
    // Formatear precios
    foreach ($productos as &$producto) {
        $producto['precio_venta'] = number_format($producto['precio_venta'] ?? 0, 0, ',', '.');
        $producto['stock'] = number_format($producto['stock'] ?? 0);
    }
    
    echo json_encode([
        'success' => true,
        'productos' => $productos
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error obteniendo productos: ' . $e->getMessage()
    ]);
}
?>

