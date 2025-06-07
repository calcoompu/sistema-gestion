<?php
/**
 * Acciones para productos
 * 
 * Este archivo maneja las acciones CRUD para productos.
 */

// Incluir configuración
require_once '../../config/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/inventario_functions.php';

// Iniciar sesión segura
iniciarSesionSegura();

// Verificar si el usuario está logueado
requireLogin('../../login.php');

// Verificar si se envió el formulario
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error_message'] = "Método no permitido.";
    header('Location: index.php');
    exit;
}

// Verificar token CSRF
if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
    $_SESSION['error_message'] = "Error de validación. Por favor, intente nuevamente.";
    header('Location: index.php');
    exit;
}

// Obtener acción
$accion = isset($_POST['accion']) ? $_POST['accion'] : '';

// Conectar a la base de datos
$pdo = conectarDB();

// Procesar según la acción
switch ($accion) {
    case 'eliminar':
        // Verificar permisos
        requirePermission('inventario_productos', 'delete', 'index.php');
        
        // Obtener ID del producto
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        
        if ($id <= 0) {
            $_SESSION['error_message'] = "ID de producto no válido.";
            header('Location: index.php');
            exit;
        }
        
        // Obtener datos del producto antes de eliminar
        $producto = obtenerProductoPorId($pdo, $id);
        
        if (!$producto) {
            $_SESSION['error_message'] = "El producto no existe.";
            header('Location: index.php');
            exit;
        }
        
        // Eliminar producto (marcar como inactivo)
        $resultado = eliminarProducto($pdo, $id);
        
        if ($resultado) {
            // Registrar en log
            logSistema('ELIMINAR', 'productos', $id, json_encode([
                'codigo' => $producto['codigo'],
                'nombre' => $producto['nombre']
            ]));
            
            $_SESSION['success_message'] = "Producto eliminado correctamente.";
        } else {
            $_SESSION['error_message'] = "Error al eliminar el producto.";
        }
        
        header('Location: index.php');
        break;
        
    case 'actualizar_stock':
        // Verificar permisos
        requirePermission('inventario_stock', 'update', 'index.php');
        
        // Obtener datos del formulario
        $producto_id = isset($_POST['producto_id']) ? (int)$_POST['producto_id'] : 0;
        $tipo = isset($_POST['tipo']) ? $_POST['tipo'] : '';
        $cantidad = isset($_POST['cantidad']) ? (int)$_POST['cantidad'] : 0;
        $descripcion = isset($_POST['descripcion']) ? trim($_POST['descripcion']) : '';
        
        // Validar datos
        if ($producto_id <= 0) {
            $_SESSION['error_message'] = "ID de producto no válido.";
            header('Location: index.php');
            exit;
        }
        
        if (!in_array($tipo, ['entrada', 'salida', 'ajuste'])) {
            $_SESSION['error_message'] = "Tipo de movimiento no válido.";
            header('Location: actualizar_stock.php?id=' . $producto_id);
            exit;
        }
        
        if ($cantidad <= 0) {
            $_SESSION['error_message'] = "La cantidad debe ser mayor que cero.";
            header('Location: actualizar_stock.php?id=' . $producto_id);
            exit;
        }
        
        // Obtener producto
        $producto = obtenerProductoPorId($pdo, $producto_id);
        
        if (!$producto) {
            $_SESSION['error_message'] = "El producto no existe.";
            header('Location: index.php');
            exit;
        }
        
        // Verificar stock suficiente para salidas
        if ($tipo === 'salida' && $cantidad > $producto['stock']) {
            $_SESSION['error_message'] = "No hay suficiente stock para realizar esta operación.";
            header('Location: actualizar_stock.php?id=' . $producto_id);
            exit;
        }
        
        // Registrar movimiento de stock
        $resultado = registrarMovimientoStock(
            $pdo,
            $producto_id,
            $tipo,
            $cantidad,
            $descripcion,
            $_SESSION['usuario_id']
        );
        
        if ($resultado) {
            $_SESSION['success_message'] = "Stock actualizado correctamente.";
            header('Location: producto_detalle.php?id=' . $producto_id);
        } else {
            $_SESSION['error_message'] = "Error al actualizar el stock.";
            header('Location: actualizar_stock.php?id=' . $producto_id);
        }
        break;
        
    default:
        $_SESSION['error_message'] = "Acción no válida.";
        header('Location: index.php');
        break;
}

exit;

