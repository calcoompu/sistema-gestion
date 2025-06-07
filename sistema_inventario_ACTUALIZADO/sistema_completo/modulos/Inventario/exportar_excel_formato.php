<?php
/**
 * Exportación de datos a Excel
 * 
 * Este archivo maneja la exportación de diferentes tipos de reportes a formato Excel.
 */

require_once '../../config/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/inventario_functions.php';

iniciarSesionSegura();

requireLogin('../../login.php');

requirePermission('inventario_reportes', 'generate', '../../menu_principal.php');

$pdo = conectarDB();

// Obtener el tipo de exportación
$tipo = isset($_GET['tipo']) ? $_GET['tipo'] : 'inventario_completo';

// Configurar headers para descarga de Excel
header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $tipo . '_' . date('Y-m-d_H-i-s') . '.xls"');
header('Pragma: no-cache');
header('Expires: 0');

// Función para limpiar datos para Excel
function limpiarParaExcel($texto) {
    return htmlspecialchars(strip_tags($texto), ENT_QUOTES, 'UTF-8');
}

// Función para formatear moneda para Excel
function formatearMonedaExcel($valor) {
    return number_format((float)$valor, 2, '.', '');
}

echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"
 xmlns:o="urn:schemas-microsoft-com:office:office"
 xmlns:x="urn:schemas-microsoft-com:office:excel"
 xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet"
 xmlns:html="http://www.w3.org/TR/REC-html40">

<Styles>
 <Style ss:ID="header">
  <Font ss:Bold="1"/>
  <Interior ss:Color="#4472C4" ss:Pattern="Solid"/>
  <Font ss:Color="#FFFFFF"/>
 </Style>
 <Style ss:ID="currency">
  <NumberFormat ss:Format="&quot;$&quot;#,##0.00"/>
 </Style>
 <Style ss:ID="number">
  <NumberFormat ss:Format="#,##0"/>
 </Style>
</Styles>

<Worksheet ss:Name="<?php echo ucfirst(str_replace('_', ' ', $tipo)); ?>">
<Table>

<?php
switch ($tipo) {
    case 'inventario_completo':
        // Exportar inventario completo
        $sql = "SELECT p.codigo, p.nombre, p.descripcion, 
                       COALESCE(c.nombre, 'Sin categoría') as categoria,
                       COALESCE(l.nombre, 'Sin ubicación') as lugar,
                       p.stock, p.stock_minimo, p.precio_venta,
                       (p.stock * p.precio_venta) as valor_total
                FROM productos p 
                LEFT JOIN categorias c ON p.categoria_id = c.id 
                LEFT JOIN lugares l ON p.lugar_id = l.id 
                WHERE p.activo = 1
                ORDER BY p.nombre";
        
        $stmt = $pdo->query($sql);
        $productos = $stmt->fetchAll();
        
        // Encabezados
        echo '<Row>';
        echo '<Cell ss:StyleID="header"><Data ss:Type="String">Código</Data></Cell>';
        echo '<Cell ss:StyleID="header"><Data ss:Type="String">Nombre</Data></Cell>';
        echo '<Cell ss:StyleID="header"><Data ss:Type="String">Descripción</Data></Cell>';
        echo '<Cell ss:StyleID="header"><Data ss:Type="String">Categoría</Data></Cell>';
        echo '<Cell ss:StyleID="header"><Data ss:Type="String">Ubicación</Data></Cell>';
        echo '<Cell ss:StyleID="header"><Data ss:Type="String">Stock</Data></Cell>';
        echo '<Cell ss:StyleID="header"><Data ss:Type="String">Stock Mínimo</Data></Cell>';
        echo '<Cell ss:StyleID="header"><Data ss:Type="String">Precio Venta</Data></Cell>';
        echo '<Cell ss:StyleID="header"><Data ss:Type="String">Valor Total</Data></Cell>';
        echo '</Row>';
        
        // Datos
        foreach ($productos as $producto) {
            echo '<Row>';
            echo '<Cell><Data ss:Type="String">' . limpiarParaExcel($producto['codigo']) . '</Data></Cell>';
            echo '<Cell><Data ss:Type="String">' . limpiarParaExcel($producto['nombre']) . '</Data></Cell>';
            echo '<Cell><Data ss:Type="String">' . limpiarParaExcel($producto['descripcion']) . '</Data></Cell>';
            echo '<Cell><Data ss:Type="String">' . limpiarParaExcel($producto['categoria']) . '</Data></Cell>';
            echo '<Cell><Data ss:Type="String">' . limpiarParaExcel($producto['lugar']) . '</Data></Cell>';
            echo '<Cell ss:StyleID="number"><Data ss:Type="Number">' . $producto['stock'] . '</Data></Cell>';
            echo '<Cell ss:StyleID="number"><Data ss:Type="Number">' . $producto['stock_minimo'] . '</Data></Cell>';
            echo '<Cell ss:StyleID="currency"><Data ss:Type="Number">' . formatearMonedaExcel($producto['precio_venta']) . '</Data></Cell>';
            echo '<Cell ss:StyleID="currency"><Data ss:Type="Number">' . formatearMonedaExcel($producto['valor_total']) . '</Data></Cell>';
            echo '</Row>';
        }
        break;
        
    case 'productos_bajo_stock':
        // Exportar productos con bajo stock
        $sql = "SELECT p.codigo, p.nombre, p.descripcion, 
                       COALESCE(c.nombre, 'Sin categoría') as categoria,
                       p.stock, p.stock_minimo, 
                       (p.stock_minimo - p.stock) as diferencia,
                       p.precio_venta
                FROM productos p 
                LEFT JOIN categorias c ON p.categoria_id = c.id 
                WHERE p.activo = 1 AND p.stock <= p.stock_minimo
                ORDER BY diferencia DESC";
        
        $stmt = $pdo->query($sql);
        $productos = $stmt->fetchAll();
        
        // Encabezados
        echo '<Row>';
        echo '<Cell ss:StyleID="header"><Data ss:Type="String">Código</Data></Cell>';
        echo '<Cell ss:StyleID="header"><Data ss:Type="String">Nombre</Data></Cell>';
        echo '<Cell ss:StyleID="header"><Data ss:Type="String">Categoría</Data></Cell>';
        echo '<Cell ss:StyleID="header"><Data ss:Type="String">Stock Actual</Data></Cell>';
        echo '<Cell ss:StyleID="header"><Data ss:Type="String">Stock Mínimo</Data></Cell>';
        echo '<Cell ss:StyleID="header"><Data ss:Type="String">Diferencia</Data></Cell>';
        echo '<Cell ss:StyleID="header"><Data ss:Type="String">Precio Venta</Data></Cell>';
        echo '<Cell ss:StyleID="header"><Data ss:Type="String">Estado</Data></Cell>';
        echo '</Row>';
        
        // Datos
        foreach ($productos as $producto) {
            $estado = $producto['stock'] == 0 ? 'Sin Stock' : 'Bajo Stock';
            echo '<Row>';
            echo '<Cell><Data ss:Type="String">' . limpiarParaExcel($producto['codigo']) . '</Data></Cell>';
            echo '<Cell><Data ss:Type="String">' . limpiarParaExcel($producto['nombre']) . '</Data></Cell>';
            echo '<Cell><Data ss:Type="String">' . limpiarParaExcel($producto['categoria']) . '</Data></Cell>';
            echo '<Cell ss:StyleID="number"><Data ss:Type="Number">' . $producto['stock'] . '</Data></Cell>';
            echo '<Cell ss:StyleID="number"><Data ss:Type="Number">' . $producto['stock_minimo'] . '</Data></Cell>';
            echo '<Cell ss:StyleID="number"><Data ss:Type="Number">' . $producto['diferencia'] . '</Data></Cell>';
            echo '<Cell ss:StyleID="currency"><Data ss:Type="Number">' . formatearMonedaExcel($producto['precio_venta']) . '</Data></Cell>';
            echo '<Cell><Data ss:Type="String">' . $estado . '</Data></Cell>';
            echo '</Row>';
        }
        break;
        
    case 'valoracion_inventario':
        // Exportar valoración del inventario
        $sql = "SELECT 
                    COALESCE(c.nombre, 'Sin categoría') as categoria,
                    COUNT(p.id) as cantidad_productos,
                    SUM(p.stock) as stock_total,
                    AVG(p.precio_venta) as precio_promedio,
                    SUM(p.precio_venta * p.stock) as valor_total
                FROM productos p 
                LEFT JOIN categorias c ON p.categoria_id = c.id 
                WHERE p.activo = 1
                GROUP BY c.id, c.nombre
                ORDER BY valor_total DESC";
        
        $stmt = $pdo->query($sql);
        $categorias = $stmt->fetchAll();
        
        // Encabezados
        echo '<Row>';
        echo '<Cell ss:StyleID="header"><Data ss:Type="String">Categoría</Data></Cell>';
        echo '<Cell ss:StyleID="header"><Data ss:Type="String">Cantidad Productos</Data></Cell>';
        echo '<Cell ss:StyleID="header"><Data ss:Type="String">Stock Total</Data></Cell>';
        echo '<Cell ss:StyleID="header"><Data ss:Type="String">Precio Promedio</Data></Cell>';
        echo '<Cell ss:StyleID="header"><Data ss:Type="String">Valor Total</Data></Cell>';
        echo '</Row>';
        
        // Datos
        $total_general = 0;
        foreach ($categorias as $categoria) {
            $total_general += $categoria['valor_total'];
            echo '<Row>';
            echo '<Cell><Data ss:Type="String">' . limpiarParaExcel($categoria['categoria']) . '</Data></Cell>';
            echo '<Cell ss:StyleID="number"><Data ss:Type="Number">' . $categoria['cantidad_productos'] . '</Data></Cell>';
            echo '<Cell ss:StyleID="number"><Data ss:Type="Number">' . $categoria['stock_total'] . '</Data></Cell>';
            echo '<Cell ss:StyleID="currency"><Data ss:Type="Number">' . formatearMonedaExcel($categoria['precio_promedio']) . '</Data></Cell>';
            echo '<Cell ss:StyleID="currency"><Data ss:Type="Number">' . formatearMonedaExcel($categoria['valor_total']) . '</Data></Cell>';
            echo '</Row>';
        }
        
        // Fila de total
        echo '<Row>';
        echo '<Cell ss:StyleID="header"><Data ss:Type="String">TOTAL GENERAL</Data></Cell>';
        echo '<Cell></Cell>';
        echo '<Cell></Cell>';
        echo '<Cell></Cell>';
        echo '<Cell ss:StyleID="header"><Data ss:Type="Number">' . formatearMonedaExcel($total_general) . '</Data></Cell>';
        echo '</Row>';
        break;
        
    case 'productos_por_categoria':
        // Exportar productos agrupados por categoría
        $categoria_id = isset($_GET['categoria_id']) ? (int)$_GET['categoria_id'] : 0;
        
        if ($categoria_id > 0) {
            $sql = "SELECT p.codigo, p.nombre, p.descripcion, p.stock, p.precio_venta,
                           (p.stock * p.precio_venta) as valor_total,
                           c.nombre as categoria_nombre
                    FROM productos p 
                    LEFT JOIN categorias c ON p.categoria_id = c.id 
                    WHERE p.categoria_id = ? AND p.activo = 1
                    ORDER BY p.nombre";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$categoria_id]);
        } else {
            $sql = "SELECT p.codigo, p.nombre, p.descripcion, p.stock, p.precio_venta,
                           (p.stock * p.precio_venta) as valor_total,
                           COALESCE(c.nombre, 'Sin categoría') as categoria_nombre
                    FROM productos p 
                    LEFT JOIN categorias c ON p.categoria_id = c.id 
                    WHERE p.activo = 1
                    ORDER BY c.nombre, p.nombre";
            $stmt = $pdo->query($sql);
        }
        
        $productos = $stmt->fetchAll();
        
        // Encabezados
        echo '<Row>';
        echo '<Cell ss:StyleID="header"><Data ss:Type="String">Categoría</Data></Cell>';
        echo '<Cell ss:StyleID="header"><Data ss:Type="String">Código</Data></Cell>';
        echo '<Cell ss:StyleID="header"><Data ss:Type="String">Nombre</Data></Cell>';
        echo '<Cell ss:StyleID="header"><Data ss:Type="String">Stock</Data></Cell>';
        echo '<Cell ss:StyleID="header"><Data ss:Type="String">Precio Venta</Data></Cell>';
        echo '<Cell ss:StyleID="header"><Data ss:Type="String">Valor Total</Data></Cell>';
        echo '</Row>';
        
        // Datos
        foreach ($productos as $producto) {
            echo '<Row>';
            echo '<Cell><Data ss:Type="String">' . limpiarParaExcel($producto['categoria_nombre']) . '</Data></Cell>';
            echo '<Cell><Data ss:Type="String">' . limpiarParaExcel($producto['codigo']) . '</Data></Cell>';
            echo '<Cell><Data ss:Type="String">' . limpiarParaExcel($producto['nombre']) . '</Data></Cell>';
            echo '<Cell ss:StyleID="number"><Data ss:Type="Number">' . $producto['stock'] . '</Data></Cell>';
            echo '<Cell ss:StyleID="currency"><Data ss:Type="Number">' . formatearMonedaExcel($producto['precio_venta']) . '</Data></Cell>';
            echo '<Cell ss:StyleID="currency"><Data ss:Type="Number">' . formatearMonedaExcel($producto['valor_total']) . '</Data></Cell>';
            echo '</Row>';
        }
        break;
        
    case 'productos_por_lugar':
        // Exportar productos agrupados por lugar
        $lugar_id = isset($_GET['lugar_id']) ? (int)$_GET['lugar_id'] : 0;
        
        if ($lugar_id > 0) {
            $sql = "SELECT p.codigo, p.nombre, p.descripcion, p.stock, p.precio_venta,
                           (p.stock * p.precio_venta) as valor_total,
                           l.nombre as lugar_nombre
                    FROM productos p 
                    LEFT JOIN lugares l ON p.lugar_id = l.id 
                    WHERE p.lugar_id = ? AND p.activo = 1
                    ORDER BY p.nombre";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$lugar_id]);
        } else {
            $sql = "SELECT p.codigo, p.nombre, p.descripcion, p.stock, p.precio_venta,
                           (p.stock * p.precio_venta) as valor_total,
                           COALESCE(l.nombre, 'Sin ubicación') as lugar_nombre
                    FROM productos p 
                    LEFT JOIN lugares l ON p.lugar_id = l.id 
                    WHERE p.activo = 1
                    ORDER BY l.nombre, p.nombre";
            $stmt = $pdo->query($sql);
        }
        
        $productos = $stmt->fetchAll();
        
        // Encabezados
        echo '<Row>';
        echo '<Cell ss:StyleID="header"><Data ss:Type="String">Ubicación</Data></Cell>';
        echo '<Cell ss:StyleID="header"><Data ss:Type="String">Código</Data></Cell>';
        echo '<Cell ss:StyleID="header"><Data ss:Type="String">Nombre</Data></Cell>';
        echo '<Cell ss:StyleID="header"><Data ss:Type="String">Stock</Data></Cell>';
        echo '<Cell ss:StyleID="header"><Data ss:Type="String">Precio Venta</Data></Cell>';
        echo '<Cell ss:StyleID="header"><Data ss:Type="String">Valor Total</Data></Cell>';
        echo '</Row>';
        
        // Datos
        foreach ($productos as $producto) {
            echo '<Row>';
            echo '<Cell><Data ss:Type="String">' . limpiarParaExcel($producto['lugar_nombre']) . '</Data></Cell>';
            echo '<Cell><Data ss:Type="String">' . limpiarParaExcel($producto['codigo']) . '</Data></Cell>';
            echo '<Cell><Data ss:Type="String">' . limpiarParaExcel($producto['nombre']) . '</Data></Cell>';
            echo '<Cell ss:StyleID="number"><Data ss:Type="Number">' . $producto['stock'] . '</Data></Cell>';
            echo '<Cell ss:StyleID="currency"><Data ss:Type="Number">' . formatearMonedaExcel($producto['precio_venta']) . '</Data></Cell>';
            echo '<Cell ss:StyleID="currency"><Data ss:Type="Number">' . formatearMonedaExcel($producto['valor_total']) . '</Data></Cell>';
            echo '</Row>';
        }
        break;
        
    default:
        // Tipo no reconocido, exportar inventario completo por defecto
        echo '<Row>';
        echo '<Cell ss:StyleID="header"><Data ss:Type="String">Error</Data></Cell>';
        echo '</Row>';
        echo '<Row>';
        echo '<Cell><Data ss:Type="String">Tipo de exportación no reconocido</Data></Cell>';
        echo '</Row>';
        break;
}
?>

</Table>
</Worksheet>
</Workbook>

