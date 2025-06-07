<?php
/**
 * API para obtener datos del dashboard
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
    
    // Obtener estadísticas
    $stats = [];
    
    // Total de productos
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM productos WHERE activo = 1");
        $result = $stmt->fetch();
        $stats['productos'] = number_format($result['total'] ?? 0);
    } catch (Exception $e) {
        $stats['productos'] = '0';
    }
    
    // Total de categorías
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM categorias WHERE activo = 1");
        $result = $stmt->fetch();
        $stats['categorias'] = number_format($result['total'] ?? 0);
    } catch (Exception $e) {
        $stats['categorias'] = '0';
    }
    
    // Stock total
    try {
        $stmt = $pdo->query("SELECT SUM(stock) as total FROM productos WHERE activo = 1");
        $result = $stmt->fetch();
        $stats['stock_total'] = number_format($result['total'] ?? 0);
    } catch (Exception $e) {
        $stats['stock_total'] = '0';
    }
    
    // Valor total del inventario
    try {
        $stmt = $pdo->query("SELECT SUM(stock * precio_venta) as total FROM productos WHERE activo = 1");
        $result = $stmt->fetch();
        $stats['valor_total'] = number_format($result['total'] ?? 0, 0, ',', '.');
    } catch (Exception $e) {
        $stats['valor_total'] = '0';
    }
    
    echo json_encode([
        'success' => true,
        'stats' => $stats
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error obteniendo estadísticas: ' . $e->getMessage()
    ]);
}
?>

