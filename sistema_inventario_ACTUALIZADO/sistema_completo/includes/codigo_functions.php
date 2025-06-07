<?php
/**
 * Funciones para la generación de códigos automáticos
 * 
 * Este archivo contiene funciones para generar códigos únicos para productos,
 * clientes, pedidos y facturas.
 */

/**
 * Genera un código único para un producto
 * 
 * @param PDO $pdo Conexión a la base de datos
 * @return string Código generado (formato: PROD-0000001)
 */
function generarCodigoProducto($pdo) {
    // Intentar usar el procedimiento almacenado
    try {
        $stmt = $pdo->prepare("CALL generar_codigo('PROD', @codigo)");
        $stmt->execute();
        
        $stmt = $pdo->query("SELECT @codigo AS codigo");
        $resultado = $stmt->fetch();
        
        if ($resultado && !empty($resultado['codigo'])) {
            return $resultado['codigo'];
        }
    } catch (PDOException $e) {
        // Si falla el procedimiento almacenado, usar método alternativo
    }
    
    // Método alternativo: obtener el último código y generar uno nuevo
    $sql = "SELECT MAX(SUBSTRING(codigo, 6)) AS ultimo_numero 
            FROM productos 
            WHERE codigo LIKE 'PROD-%'";
    
    $stmt = $pdo->query($sql);
    $resultado = $stmt->fetch();
    
    $ultimo_numero = (int)($resultado['ultimo_numero'] ?? 0);
    $nuevo_numero = $ultimo_numero + 1;
    
    return 'PROD-' . str_pad($nuevo_numero, 7, '0', STR_PAD_LEFT);
}

/**
 * Genera un código único para un cliente
 * 
 * @param PDO $pdo Conexión a la base de datos
 * @return string Código generado (formato: CLIE-0000001)
 */
function generarCodigoCliente($pdo) {
    // Intentar usar el procedimiento almacenado
    try {
        $stmt = $pdo->prepare("CALL generar_codigo('CLIE', @codigo)");
        $stmt->execute();
        
        $stmt = $pdo->query("SELECT @codigo AS codigo");
        $resultado = $stmt->fetch();
        
        if ($resultado && !empty($resultado['codigo'])) {
            return $resultado['codigo'];
        }
    } catch (PDOException $e) {
        // Si falla el procedimiento almacenado, usar método alternativo
    }
    
    // Método alternativo: obtener el último código y generar uno nuevo
    $sql = "SELECT MAX(SUBSTRING(codigo, 6)) AS ultimo_numero 
            FROM clientes 
            WHERE codigo LIKE 'CLIE-%'";
    
    $stmt = $pdo->query($sql);
    $resultado = $stmt->fetch();
    
    $ultimo_numero = (int)($resultado['ultimo_numero'] ?? 0);
    $nuevo_numero = $ultimo_numero + 1;
    
    return 'CLIE-' . str_pad($nuevo_numero, 7, '0', STR_PAD_LEFT);
}

/**
 * Genera un código único para un pedido
 * 
 * @param PDO $pdo Conexión a la base de datos
 * @return string Código generado (formato: PED-0000001)
 */
function generarCodigoPedido($pdo) {
    // Intentar usar el procedimiento almacenado
    try {
        $stmt = $pdo->prepare("CALL generar_codigo('PED', @codigo)");
        $stmt->execute();
        
        $stmt = $pdo->query("SELECT @codigo AS codigo");
        $resultado = $stmt->fetch();
        
        if ($resultado && !empty($resultado['codigo'])) {
            return $resultado['codigo'];
        }
    } catch (PDOException $e) {
        // Si falla el procedimiento almacenado, usar método alternativo
    }
    
    // Método alternativo: obtener el último código y generar uno nuevo
    $sql = "SELECT MAX(SUBSTRING(codigo, 5)) AS ultimo_numero 
            FROM pedidos 
            WHERE codigo LIKE 'PED-%'";
    
    $stmt = $pdo->query($sql);
    $resultado = $stmt->fetch();
    
    $ultimo_numero = (int)($resultado['ultimo_numero'] ?? 0);
    $nuevo_numero = $ultimo_numero + 1;
    
    return 'PED-' . str_pad($nuevo_numero, 7, '0', STR_PAD_LEFT);
}

/**
 * Genera un código único para una factura
 * 
 * @param PDO $pdo Conexión a la base de datos
 * @return string Código generado (formato: FACT-0000001)
 */
function generarCodigoFactura($pdo) {
    // Intentar usar el procedimiento almacenado
    try {
        $stmt = $pdo->prepare("CALL generar_codigo('FACT', @codigo)");
        $stmt->execute();
        
        $stmt = $pdo->query("SELECT @codigo AS codigo");
        $resultado = $stmt->fetch();
        
        if ($resultado && !empty($resultado['codigo'])) {
            return $resultado['codigo'];
        }
    } catch (PDOException $e) {
        // Si falla el procedimiento almacenado, usar método alternativo
    }
    
    // Método alternativo: obtener el último código y generar uno nuevo
    $sql = "SELECT MAX(SUBSTRING(codigo, 6)) AS ultimo_numero 
            FROM facturas 
            WHERE codigo LIKE 'FACT-%'";
    
    $stmt = $pdo->query($sql);
    $resultado = $stmt->fetch();
    
    $ultimo_numero = (int)($resultado['ultimo_numero'] ?? 0);
    $nuevo_numero = $ultimo_numero + 1;
    
    return 'FACT-' . str_pad($nuevo_numero, 7, '0', STR_PAD_LEFT);
}

