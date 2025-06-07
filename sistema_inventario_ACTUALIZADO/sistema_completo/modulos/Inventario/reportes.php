<?php
require_once '../../config/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/inventario_functions.php';

iniciarSesionSegura();

requireLogin('../../login.php');

requirePermission('inventario_reportes', 'generate', '../../menu_principal.php');

$pdo = conectarDB();

$estadisticas_generales = obtenerEstadisticasInventario($pdo);

$sql = "SELECT p.*, c.nombre as categoria_nombre
        FROM productos p 
        LEFT JOIN categorias c ON p.categoria_id = c.id 
        WHERE p.activo = 1 AND p.stock <= p.stock_minimo
        ORDER BY (p.stock_minimo - p.stock) DESC
        LIMIT 10";
$stmt = $pdo->query($sql);
$productos_bajo_stock = $stmt->fetchAll();

$sql = "SELECT 
            COALESCE(c.nombre, 'Sin categoria') as categoria_nombre,
            COUNT(p.id) as cantidad_productos,
            SUM(p.stock) as stock_total,
            SUM(p.precio_venta * p.stock) as valor_total
        FROM productos p 
        LEFT JOIN categorias c ON p.categoria_id = c.id 
        WHERE p.activo = 1
        GROUP BY c.id, c.nombre
        ORDER BY valor_total DESC";
$stmt = $pdo->query($sql);
$valoracion_categorias = $stmt->fetchAll();

$sql = "SELECT p.*, c.nombre as categoria_nombre,
               (p.precio_venta * p.stock) as valor_total
        FROM productos p 
        LEFT JOIN categorias c ON p.categoria_id = c.id 
        WHERE p.activo = 1
        ORDER BY valor_total DESC
        LIMIT 10";
$stmt = $pdo->query($sql);
$productos_mas_valiosos = $stmt->fetchAll();

$movimientos_recientes = [];
try {
    $sql = "SELECT 
                p.nombre as producto_nombre,
                p.codigo as producto_codigo,
                'Actualizacion' as tipo_movimiento,
                p.fecha_actualizacion as fecha,
                p.stock as cantidad_actual
            FROM productos p 
            WHERE p.activo = 1 AND p.fecha_actualizacion IS NOT NULL
            ORDER BY p.fecha_actualizacion DESC
            LIMIT 10";
    $stmt = $pdo->query($sql);
    $movimientos_recientes = $stmt->fetchAll();
} catch (Exception $e) {
    // Si no existe la tabla o columna, continuar sin movimientos
}

$pageTitle = "Reportes del Inventario - " . SISTEMA_NOMBRE;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../../assets/css/style.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .stats-card {
            transition: transform 0.2s;
            border: none;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .stats-card:hover {
            transform: translateY(-5px);
        }
        .chart-container {
            position: relative;
            height: 300px;
        }
        .table-responsive {
            border-radius: 10px;
            overflow: hidden;
        }
        .badge-stock-bajo {
            background-color: #dc3545;
        }
        .badge-stock-normal {
            background-color: #28a745;
        }
    </style>
</head>
<body class="d-flex flex-column min-vh-100">
    <?php include '../../includes/header.php'; ?>
    
    <div class="container-fluid py-4">
        <div class="row mb-4">
            <div class="col-md-8">
                <h2 class="fw-bold text-primary">
                    <i class="bi bi-file-earmark-bar-graph-fill me-2"></i>Reportes del Inventario
                </h2>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="../../menu_principal.php">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="index.php">Inventario</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Reportes</li>
                    </ol>
                </nav>
            </div>
            <div class="col-md-4 text-end">
                <div class="btn-group">
                    <button type="button" class="btn btn-success dropdown-toggle" data-bs-toggle="dropdown">
                        <i class="bi bi-file-earmark-excel me-1"></i>Exportar
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="exportar_excel_formato.php?tipo=valoracion_inventario">
                            <i class="bi bi-currency-dollar me-2"></i>Valoracion
                        </a></li>
                        <li><a class="dropdown-item" href="exportar_excel_formato.php?tipo=productos_bajo_stock">
                            <i class="bi bi-exclamation-triangle me-2"></i>Bajo Stock
                        </a></li>
                        <li><a class="dropdown-item" href="exportar_excel_formato.php?tipo=inventario_completo">
                            <i class="bi bi-table me-2"></i>Inventario Completo
                        </a></li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card stats-card bg-primary text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title mb-0">Total Productos</h6>
                                <h2 class="mb-0"><?php echo number_format($estadisticas_generales['total_productos'] ?? 0); ?></h2>
                            </div>
                            <div>
                                <i class="bi bi-box-seam-fill fs-1"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card bg-warning text-dark">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title mb-0">Bajo Stock</h6>
                                <h2 class="mb-0"><?php echo number_format($estadisticas_generales['productos_bajo_stock'] ?? 0); ?></h2>
                            </div>
                            <div>
                                <i class="bi bi-exclamation-triangle-fill fs-1"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card bg-success text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title mb-0">Valor Total</h6>
                                <h2 class="mb-0"><?php echo formatCurrency($estadisticas_generales['valor_total_inventario'] ?? 0); ?></h2>
                            </div>
                            <div>
                                <i class="bi bi-currency-dollar fs-1"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card bg-info text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title mb-0">Precio Promedio</h6>
                                <h2 class="mb-0"><?php echo formatCurrency($estadisticas_generales['precio_promedio'] ?? 0); ?></h2>
                            </div>
                            <div>
                                <i class="bi bi-graph-up fs-1"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="bi bi-pie-chart me-2"></i>Valoracion por Categoria
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="chartCategorias"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="bi bi-exclamation-triangle me-2"></i>Productos con Bajo Stock
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($productos_bajo_stock)): ?>
                            <div class="text-center py-3">
                                <i class="bi bi-check-circle text-success fs-1"></i>
                                <p class="text-muted mt-2">Todos los productos tienen stock suficiente!</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Producto</th>
                                            <th>Stock</th>
                                            <th>Minimo</th>
                                            <th>Estado</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($productos_bajo_stock as $producto): ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($producto['nombre']); ?></strong>
                                                    <br><small class="text-muted"><?php echo htmlspecialchars($producto['codigo']); ?></small>
                                                </td>
                                                <td><?php echo number_format($producto['stock']); ?></td>
                                                <td><?php echo number_format($producto['stock_minimo']); ?></td>
                                                <td>
                                                    <span class="badge <?php echo $producto['stock'] == 0 ? 'badge-stock-bajo' : 'bg-warning'; ?>">
                                                        <?php echo $producto['stock'] == 0 ? 'Sin Stock' : 'Bajo Stock'; ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="bi bi-gem me-2"></i>Productos Mas Valiosos
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Producto</th>
                                        <th>Stock</th>
                                        <th>Valor Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($productos_mas_valiosos as $producto): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($producto['nombre']); ?></strong>
                                                <br><small class="text-muted"><?php echo htmlspecialchars($producto['codigo']); ?></small>
                                            </td>
                                            <td><?php echo number_format($producto['stock']); ?></td>
                                            <td><strong><?php echo formatCurrency($producto['valor_total']); ?></strong></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="bi bi-tags me-2"></i>Resumen por Categoria
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Categoria</th>
                                        <th>Productos</th>
                                        <th>Valor Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($valoracion_categorias as $categoria): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($categoria['categoria_nombre']); ?></strong></td>
                                            <td><?php echo number_format($categoria['cantidad_productos']); ?></td>
                                            <td><strong><?php echo formatCurrency($categoria['valor_total']); ?></strong></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include '../../includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const ctx = document.getElementById('chartCategorias').getContext('2d');
        const chartCategorias = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: [
                    <?php foreach ($valoracion_categorias as $categoria): ?>
                        '<?php echo addslashes($categoria['categoria_nombre']); ?>',
                    <?php endforeach; ?>
                ],
                datasets: [{
                    data: [
                        <?php foreach ($valoracion_categorias as $categoria): ?>
                            <?php echo $categoria['valor_total']; ?>,
                        <?php endforeach; ?>
                    ],
                    backgroundColor: [
                        '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', 
                        '#9966FF', '#FF9F40', '#FF6384', '#C9CBCF'
                    ],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.label + ': $' + context.parsed.toLocaleString('es-AR');
                            }
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>

