<?php
require_once '../../config/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/inventario_functions.php';

iniciarSesionSegura();

requireLogin('../../login.php');

requirePermission('inventario_productos', 'view_list', '../../menu_principal.php');

$pdo = conectarDB();

$lugar_id = isset($_GET['lugar_id']) ? (int)$_GET['lugar_id'] : 0;

if ($lugar_id > 0) {
    $sql = "SELECT p.*, l.nombre as lugar_nombre
            FROM productos p 
            LEFT JOIN lugares l ON p.lugar_id = l.id 
            WHERE p.lugar_id = ? AND p.activo = 1
            ORDER BY p.nombre";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$lugar_id]);
    $productos = $stmt->fetchAll();
    
    $sql = "SELECT nombre FROM lugares WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$lugar_id]);
    $lugar_info = $stmt->fetch();
    
    $titulo_pagina = "Productos en ubicación: " . ($lugar_info['nombre'] ?? 'Sin ubicación');
} else {
    $sql = "SELECT p.*, l.nombre as lugar_nombre
            FROM productos p 
            LEFT JOIN lugares l ON p.lugar_id = l.id 
            WHERE p.activo = 1
            ORDER BY l.nombre, p.nombre";
    $stmt = $pdo->query($sql);
    $productos = $stmt->fetchAll();
    
    $titulo_pagina = "Productos por Ubicación";
}

$productos_por_lugar = [];
$totales_por_lugar = [];
$total_general = 0;

foreach ($productos as $producto) {
    $lugar = $producto['lugar_nombre'] ?? 'Sin ubicación';
    
    if (!isset($productos_por_lugar[$lugar])) {
        $productos_por_lugar[$lugar] = [];
        $totales_por_lugar[$lugar] = 0;
    }
    
    $productos_por_lugar[$lugar][] = $producto;
    
    $total_producto = $producto['precio_venta'] * $producto['stock'];
    $totales_por_lugar[$lugar] += $total_producto;
    $total_general += $total_producto;
}

$sql = "SELECT id, nombre FROM lugares WHERE activo = 1 ORDER BY nombre";
$stmt = $pdo->query($sql);
$lugares = $stmt->fetchAll();

$pageTitle = $titulo_pagina . " - " . SISTEMA_NOMBRE;
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
    <style>
        .lugar-header {
            background: linear-gradient(135deg, #28a745, #1e7e34);
            color: white;
            border-radius: 8px;
            margin-bottom: 15px;
            padding: 15px;
        }
        .total-flotante {
            position: fixed;
            bottom: 20px;
            left: 20px;
            z-index: 1000;
            background: rgba(40, 167, 69, 0.9);
            color: white;
            padding: 15px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.3);
        }
        .btn-volver-arriba {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 1000;
            display: none;
        }
        .producto-row {
            border-bottom: 1px solid #eee;
            padding: 8px 0;
        }
        .producto-row:last-child {
            border-bottom: none;
        }
        .total-lugar {
            background-color: #f8f9fa;
            font-weight: bold;
            border-top: 2px solid #28a745;
            padding: 10px;
            margin-top: 10px;
        }
    </style>
</head>
<body class="d-flex flex-column min-vh-100">
    <?php include '../../includes/header.php'; ?>
    
    <div class="container-fluid py-4">
        <div class="row mb-4">
            <div class="col-md-8">
                <h2 class="fw-bold text-success">
                    <i class="bi bi-geo-alt-fill me-2"></i><?php echo htmlspecialchars($titulo_pagina); ?>
                </h2>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="../../menu_principal.php">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="index.php">Inventario</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Por Ubicación</li>
                    </ol>
                </nav>
            </div>
            <div class="col-md-4 text-end">
                <div class="btn-group">
                    <button type="button" class="btn btn-success" onclick="exportarAExcel()">
                        <i class="bi bi-file-earmark-excel me-1"></i>Exportar Excel
                    </button>
                    <a href="index.php" class="btn btn-secondary">
                        <i class="bi bi-arrow-left me-1"></i>Volver al Inventario
                    </a>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label for="lugar_id" class="form-label">Filtrar por Ubicación</label>
                        <select class="form-select" id="lugar_id" name="lugar_id" onchange="this.form.submit()">
                            <option value="">Todas las ubicaciones</option>
                            <?php foreach ($lugares as $lugar): ?>
                                <option value="<?php echo $lugar['id']; ?>" <?php echo $lugar_id == $lugar['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($lugar['nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <a href="productos_por_lugar.php" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-clockwise me-1"></i>Limpiar
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <?php if (empty($productos_por_lugar)): ?>
            <div class="text-center py-5">
                <i class="bi bi-geo-alt display-1 text-muted"></i>
                <h4 class="text-muted mt-3">No hay productos para mostrar</h4>
                <p class="text-muted">No se encontraron productos en la ubicación seleccionada.</p>
            </div>
        <?php else: ?>
            <?php foreach ($productos_por_lugar as $lugar => $productos_lugar): ?>
                <div class="card mb-4">
                    <div class="lugar-header">
                        <h4 class="mb-0">
                            <i class="bi bi-geo-alt-fill me-2"></i><?php echo htmlspecialchars($lugar); ?>
                        </h4>
                    </div>
                    <div class="card-body">
                        <?php foreach ($productos_lugar as $producto): ?>
                            <?php $total_producto = $producto['precio_venta'] * $producto['stock']; ?>
                            <div class="producto-row">
                                <div class="row align-items-center">
                                    <div class="col-md-4">
                                        <strong><?php echo htmlspecialchars($producto['nombre']); ?></strong>
                                        <br><small class="text-muted"><?php echo htmlspecialchars($producto['codigo']); ?></small>
                                    </div>
                                    <div class="col-md-2 text-center">
                                        <span class="badge bg-success">Cantidad: <?php echo number_format($producto['stock']); ?></span>
                                    </div>
                                    <div class="col-md-2 text-center">
                                        <span class="text-muted">x</span>
                                    </div>
                                    <div class="col-md-2 text-center">
                                        <strong>Precio: <?php echo formatCurrency($producto['precio_venta']); ?></strong>
                                    </div>
                                    <div class="col-md-2 text-end">
                                        <strong class="text-success">Total: <?php echo formatCurrency($total_producto); ?></strong>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        
                        <div class="total-lugar">
                            <div class="row">
                                <div class="col-md-8">
                                    <h5 class="mb-0">Total en <?php echo htmlspecialchars($lugar); ?>:</h5>
                                </div>
                                <div class="col-md-4 text-end">
                                    <h5 class="mb-0 text-success"><?php echo formatCurrency($totales_por_lugar[$lugar]); ?></h5>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <div class="card border-success">
                <div class="card-header bg-success text-white">
                    <div class="row">
                        <div class="col-md-8">
                            <h4 class="mb-0">
                                <i class="bi bi-calculator me-2"></i>TOTAL GENERAL:
                            </h4>
                        </div>
                        <div class="col-md-4 text-end">
                            <h4 class="mb-0"><?php echo formatCurrency($total_general); ?></h4>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <div class="total-flotante" id="totalFlotante">
        <div class="text-center">
            <small>Total General</small><br>
            <strong class="fs-5"><?php echo formatCurrency($total_general); ?></strong>
        </div>
    </div>

    <button class="btn btn-success btn-volver-arriba" id="btnVolverArriba" onclick="volverArriba()">
        <i class="bi bi-arrow-up"></i>
    </button>

    <?php include '../../includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        window.addEventListener('scroll', function() {
            const boton = document.getElementById('btnVolverArriba');
            if (window.pageYOffset > 300) {
                boton.style.display = 'block';
            } else {
                boton.style.display = 'none';
            }
        });

        function volverArriba() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        }

        function exportarAExcel() {
            window.location.href = 'exportar_excel_formato.php?tipo=productos_por_lugar';
        }
    </script>
</body>
</html>

