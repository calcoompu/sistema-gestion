<?php
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');

// Incluir configuración
require_once '../../config/config.php';

// Iniciar sesión
session_start();

// Verificar sesión
if (!isset($_SESSION['usuario_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Sesión no válida'
    ]);
    exit;
}

try {
    // Conectar a la base de datos
    $pdo = conectarDB();
    
    // Estadísticas de productos
    $query = "SELECT COUNT(*) as total_productos FROM productos";
    $stmt = $pdo->query($query);
    $total_productos = $stmt->fetchColumn();
    
    // Total de stock
    $query = "SELECT SUM(stock) as total_stock FROM productos";
    $stmt = $pdo->query($query);
    $total_stock = $stmt->fetchColumn() ?? 0;
    
    // Valor total del inventario
    $query = "SELECT SUM(precio_venta * stock) as valor_total FROM productos";
    $stmt = $pdo->query($query);
    $valor_total = $stmt->fetchColumn() ?? 0;
    
    // Contar categorías (si existe la tabla)
    $total_categorias = 0;
    try {
        $query = "SELECT COUNT(*) as total_categorias FROM categorias";
        $stmt = $pdo->query($query);
        $total_categorias = $stmt->fetchColumn();
    } catch(PDOException $e) {
        // Si no existe la tabla categorías, usar 0
        $total_categorias = 0;
    }
    
    // Productos con bajo stock
    $query = "SELECT id, nombre, stock FROM productos WHERE stock < 10 ORDER BY stock ASC LIMIT 5";
    $stmt = $pdo->query($query);
    $bajo_stock = $stmt->fetchAll();
    
    // Devolver resultado como JSON
    echo json_encode([
        'success' => true,
        'stats' => [
            'productos' => (int)$total_productos,
            'categorias' => (int)$total_categorias,
            'stock_total' => (int)$total_stock,
            'valor_total' => number_format((float)$valor_total, 2, '.', '')
        ],
        'bajo_stock' => $bajo_stock
    ]);
    
} catch(PDOException $e) {
    // Devolver error como JSON
    echo json_encode([
        'success' => false,
        'message' => 'Error de conexión: ' . $e->getMessage()
    ]);
}
?>

