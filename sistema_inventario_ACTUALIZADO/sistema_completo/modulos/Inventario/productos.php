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
    
    // Consulta para obtener productos
    $query = "SELECT p.id, p.codigo, p.nombre, p.descripcion, p.stock, p.precio_venta, 
              c.nombre as categoria, l.nombre as lugar
              FROM productos p
              LEFT JOIN categorias c ON p.categoria_id = c.id
              LEFT JOIN lugares l ON p.lugar_id = l.id
              ORDER BY p.nombre";
    
    $stmt = $pdo->query($query);
    $productos = $stmt->fetchAll();
    
    // Formatear productos para el frontend
    $productosFormateados = [];
    foreach ($productos as $producto) {
        $productosFormateados[] = [
            'id' => $producto['id'],
            'codigo' => $producto['codigo'],
            'nombre' => $producto['nombre'],
            'descripcion' => $producto['descripcion'],
            'categoria' => $producto['categoria'] ?? 'Sin categoría',
            'lugar' => $producto['lugar'] ?? 'Sin lugar',
            'stock' => (int)$producto['stock'],
            'precio_venta' => number_format((float)$producto['precio_venta'], 2, '.', ''),
            'precio_formateado' => number_format((float)$producto['precio_venta'], 2, ',', '.')
        ];
    }
    
    // Devolver resultado como JSON
    echo json_encode([
        'success' => true,
        'productos' => $productosFormateados,
        'total' => count($productosFormateados)
    ]);
    
} catch(PDOException $e) {
    // Devolver error como JSON
    echo json_encode([
        'success' => false,
        'message' => 'Error de conexión: ' . $e->getMessage()
    ]);
}
?>

