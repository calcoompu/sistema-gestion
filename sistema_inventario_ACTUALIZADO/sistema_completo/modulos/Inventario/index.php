<?php
/**
 * Listado de productos
 * 
 * Este archivo muestra el listado de productos con filtros y paginación.
 */

// Incluir configuración
require_once '../../config/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/inventario_functions.php';

// Iniciar sesión segura
iniciarSesionSegura();

// Verificar si el usuario está logueado
requireLogin('../../login.php');

// Verificar permisos
requirePermission('inventario_productos', 'view_list', '../../menu_principal.php');

// Conectar a la base de datos
$pdo = conectarDB();

// Obtener parámetros de filtrado y paginación
$buscar = isset($_GET['buscar']) ? trim($_GET['buscar']) : '';
$categoria = isset($_GET['categoria']) ? $_GET['categoria'] : '';
$lugar = isset($_GET['lugar']) ? $_GET['lugar'] : '';
$activo = isset($_GET['activo']) ? (int)$_GET['activo'] : 1;
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$por_pagina = isset($_GET['por_pagina']) ? (int)$_GET['por_pagina'] : ITEMS_PER_PAGE;

// Filtros
$filtros = [
    'buscar' => $buscar,
    'categoria' => $categoria,
    'lugar' => $lugar,
    'activo' => $activo
];

// Obtener productos con paginación
$resultado = obtenerProductos($pdo, $filtros, $pagina, $por_pagina);
$productos = $resultado['productos'];
$paginacion = $resultado['paginacion'];

// Obtener categorías y lugares para filtros
$categorias = obtenerCategorias($pdo, false);
$lugares = obtenerLugares($pdo, false);

// Título de la página
$pageTitle = "Inventario de Productos - " . SISTEMA_NOMBRE;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../../assets/css/style.css" rel="stylesheet">
</head>
<body class="d-flex flex-column min-vh-100">
    <?php include '../../includes/header.php'; ?>
    
    <div class="container-fluid py-4">
        <?php displayGlobalMessages(); ?>
        
        <div class="row mb-4">
            <div class="col-md-8">
                <h2 class="fw-bold text-primary">
                    <i class="bi bi-box-seam-fill me-2"></i>Inventario de Productos
                </h2>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="../../menu_principal.php">Dashboard</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Inventario</li>
                    </ol>
                </nav>
            </div>
            <div class="col-md-4 text-end">
                <?php if (hasPermission('inventario_productos', 'create')): ?>
                <a href="producto_form.php" class="btn btn-primary">
                    <i class="bi bi-plus-circle me-1"></i>Nuevo Producto
                </a>
                <?php endif; ?>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header bg-light">
                <h5 class="card-title mb-0">Filtros de Búsqueda</h5>
            </div>
            <div class="card-body">
                <form method="GET" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>" class="row g-3">
                    <div class="col-md-4">
                        <label for="buscar" class="form-label">Buscar</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-search"></i></span>
                            <input type="text" class="form-control" id="buscar" name="buscar" value="<?= htmlspecialchars($buscar) ?>" placeholder="Nombre, código o descripción">
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <label for="categoria" class="form-label">Categoría</label>
                        <select class="form-select" id="categoria" name="categoria">
                            <option value="">Todas las categorías</option>
                            <?php foreach ($categorias as $cat): ?>
                                <option value="<?= $cat['id'] ?>" <?= $categoria == $cat['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cat['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-3">
                        <label for="lugar" class="form-label">Ubicación</label>
                        <select class="form-select" id="lugar" name="lugar">
                            <option value="">Todas las ubicaciones</option>
                            <?php foreach ($lugares as $lug): ?>
                                <option value="<?= $lug['id'] ?>" <?= $lugar == $lug['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($lug['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-2">
                        <label for="activo" class="form-label">Estado</label>
                        <select class="form-select" id="activo" name="activo">
                            <option value="1" <?= $activo == 1 ? 'selected' : '' ?>>Activos</option>
                            <option value="0" <?= $activo === 0 ? 'selected' : '' ?>>Inactivos</option>
                            <option value="" <?= $activo === '' ? 'selected' : '' ?>>Todos</option>
                        </select>
                    </div>
                    
                    <div class="col-12 text-end">
                        <a href="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>" class="btn btn-secondary me-2">
                            <i class="bi bi-x-circle me-1"></i>Limpiar
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-filter me-1"></i>Filtrar
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Listado de Productos</h5>
                <span class="badge bg-primary">
                    Total: <?= number_format($paginacion['total_registros']) ?> productos
                </span>
            </div>
            <div class="card-body p-0">
                <?php if (empty($productos)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-search fs-1 text-muted mb-3"></i>
                        <h5 class="text-muted">No se encontraron productos</h5>
                        <p class="text-muted">Intenta con otros criterios de búsqueda</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Código</th>
                                    <th>Producto</th>
                                    <th>Categoría</th>
                                    <th>Ubicación</th>
                                    <th class="text-end">Precio Venta</th>
                                    <th class="text-center">Stock</th>
                                    <th class="text-center">Estado</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($productos as $producto): ?>
                                    <tr>
                                        <td>
                                            <code class="product-code"><?= htmlspecialchars($producto['codigo']) ?></code>
                                        </td>
                                        <td><?= htmlspecialchars($producto['nombre']) ?></td>
                                        <td>
                                            <?php if ($producto['categoria_nombre']): ?>
                                                <span class="badge bg-info">
                                                    <?= htmlspecialchars($producto['categoria_nombre']) ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Sin categoría</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($producto['lugar_nombre']): ?>
                                                <span class="badge bg-dark">
                                                    <?= htmlspecialchars($producto['lugar_nombre']) ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Sin ubicación</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end fw-bold">
                                            <?= formatCurrency($producto['precio_venta']) ?>
                                        </td>
                                        <td class="text-center">
                                            <?php
                                            $stock_class = 'stock-normal';
                                            if ($producto['stock'] <= 0) {
                                                $stock_class = 'stock-danger';
                                            } elseif ($producto['stock'] <= $producto['stock_minimo']) {
                                                $stock_class = 'stock-warning';
                                            }
                                            ?>
                                            <span class="<?= $stock_class ?>">
                                                <?= number_format($producto['stock']) ?>
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <?php if ($producto['activo']): ?>
                                                <span class="badge bg-success">Activo</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">Inactivo</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group btn-group-sm">
                                                <?php if (hasPermission('inventario_productos', 'view_detail')): ?>
                                                <a href="producto_detalle.php?id=<?= $producto['id'] ?>" class="btn btn-info" title="Ver detalles">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                <?php endif; ?>
                                                
                                                <?php if (hasPermission('inventario_stock', 'update')): ?>
                                                <a href="actualizar_stock.php?id=<?= $producto['id'] ?>" class="btn btn-success" title="Actualizar stock">
                                                    <i class="bi bi-arrow-down-up"></i>
                                                </a>
                                                <?php endif; ?>
                                                
                                                <?php if (hasPermission('inventario_productos', 'edit')): ?>
                                                <a href="producto_form.php?id=<?= $producto['id'] ?>" class="btn btn-warning" title="Editar">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <?php endif; ?>
                                                
                                                <?php if (hasPermission('inventario_productos', 'delete')): ?>
                                                <button type="button" class="btn btn-danger" title="Eliminar" 
                                                        onclick="confirmarEliminar(<?= $producto['id'] ?>, '<?= htmlspecialchars(addslashes($producto['nombre'])) ?>')">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
            
            <?php if ($paginacion['total_paginas'] > 1): ?>
                <div class="card-footer">
                    <div class="row align-items-center">
                        <div class="col-md-6 small text-muted">
                            Mostrando <?= ($paginacion['pagina_actual'] - 1) * $paginacion['por_pagina'] + 1 ?> 
                            a <?= min($paginacion['pagina_actual'] * $paginacion['por_pagina'], $paginacion['total_registros']) ?> 
                            de <?= number_format($paginacion['total_registros']) ?> productos
                        </div>
                        <div class="col-md-6">
                            <nav aria-label="Paginación de productos">
                                <ul class="pagination justify-content-end mb-0">
                                    <?php if ($paginacion['pagina_actual'] > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>?pagina=1&buscar=<?= urlencode($buscar) ?>&categoria=<?= urlencode($categoria) ?>&lugar=<?= urlencode($lugar) ?>&activo=<?= urlencode($activo) ?>&por_pagina=<?= $por_pagina ?>">
                                                <i class="bi bi-chevron-double-left"></i>
                                            </a>
                                        </li>
                                        <li class="page-item">
                                            <a class="page-link" href="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>?pagina=<?= $paginacion['pagina_actual'] - 1 ?>&buscar=<?= urlencode($buscar) ?>&categoria=<?= urlencode($categoria) ?>&lugar=<?= urlencode($lugar) ?>&activo=<?= urlencode($activo) ?>&por_pagina=<?= $por_pagina ?>">
                                                <i class="bi bi-chevron-left"></i>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                    
                                    <?php
                                    // Determinar rango de páginas a mostrar
                                    $rango = 2; // Mostrar 2 páginas antes y después de la actual
                                    $inicio_rango = max(1, $paginacion['pagina_actual'] - $rango);
                                    $fin_rango = min($paginacion['total_paginas'], $paginacion['pagina_actual'] + $rango);
                                    
                                    // Mostrar primera página si no está en el rango
                                    if ($inicio_rango > 1) {
                                        echo '<li class="page-item"><a class="page-link" href="' . htmlspecialchars($_SERVER['PHP_SELF']) . '?pagina=1&buscar=' . urlencode($buscar) . '&categoria=' . urlencode($categoria) . '&lugar=' . urlencode($lugar) . '&activo=' . urlencode($activo) . '&por_pagina=' . $por_pagina . '">1</a></li>';
                                        if ($inicio_rango > 2) {
                                            echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                        }
                                    }
                                    
                                    // Mostrar páginas en el rango
                                    for ($i = $inicio_rango; $i <= $fin_rango; $i++) {
                                        echo '<li class="page-item ' . ($i == $paginacion['pagina_actual'] ? 'active' : '') . '">';
                                        echo '<a class="page-link" href="' . htmlspecialchars($_SERVER['PHP_SELF']) . '?pagina=' . $i . '&buscar=' . urlencode($buscar) . '&categoria=' . urlencode($categoria) . '&lugar=' . urlencode($lugar) . '&activo=' . urlencode($activo) . '&por_pagina=' . $por_pagina . '">' . $i . '</a>';
                                        echo '</li>';
                                    }
                                    
                                    // Mostrar última página si no está en el rango
                                    if ($fin_rango < $paginacion['total_paginas']) {
                                        if ($fin_rango < $paginacion['total_paginas'] - 1) {
                                            echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                        }
                                        echo '<li class="page-item"><a class="page-link" href="' . htmlspecialchars($_SERVER['PHP_SELF']) . '?pagina=' . $paginacion['total_paginas'] . '&buscar=' . urlencode($buscar) . '&categoria=' . urlencode($categoria) . '&lugar=' . urlencode($lugar) . '&activo=' . urlencode($activo) . '&por_pagina=' . $por_pagina . '">' . $paginacion['total_paginas'] . '</a></li>';
                                    }
                                    ?>
                                    
                                    <?php if ($paginacion['pagina_actual'] < $paginacion['total_paginas']): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>?pagina=<?= $paginacion['pagina_actual'] + 1 ?>&buscar=<?= urlencode($buscar) ?>&categoria=<?= urlencode($categoria) ?>&lugar=<?= urlencode($lugar) ?>&activo=<?= urlencode($activo) ?>&por_pagina=<?= $por_pagina ?>">
                                                <i class="bi bi-chevron-right"></i>
                                            </a>
                                        </li>
                                        <li class="page-item">
                                            <a class="page-link" href="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>?pagina=<?= $paginacion['total_paginas'] ?>&buscar=<?= urlencode($buscar) ?>&categoria=<?= urlencode($categoria) ?>&lugar=<?= urlencode($lugar) ?>&activo=<?= urlencode($activo) ?>&por_pagina=<?= $por_pagina ?>">
                                                <i class="bi bi-chevron-double-right"></i>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Botón flotante para volver al inicio -->
    <a href="#" class="btn btn-primary btn-float" id="btnVolverArriba" style="display: none;">
        <i class="bi bi-arrow-up"></i>
    </a>
    
    <!-- Total flotante -->
    <div class="total-float bg-dark text-white" id="totalFlotante">
        <i class="bi bi-box-seam-fill me-1"></i> Total: <?= number_format($paginacion['total_registros']) ?> productos
    </div>

    <!-- Modal de confirmación para eliminar -->
    <div class="modal fade" id="modalEliminar" tabindex="-1" aria-labelledby="modalEliminarLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="modalEliminarLabel">Confirmar Eliminación</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>¿Estás seguro de que deseas eliminar el producto <strong id="nombreProducto"></strong>?</p>
                    <p class="text-danger">Esta acción no se puede deshacer.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <form id="formEliminar" method="POST" action="producto_acciones.php">
                        <input type="hidden" name="accion" value="eliminar">
                        <input type="hidden" name="id" id="idProducto">
                        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                        <button type="submit" class="btn btn-danger">Eliminar</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <?php include '../../includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Función para confirmar eliminación
        function confirmarEliminar(id, nombre) {
            document.getElementById('idProducto').value = id;
            document.getElementById('nombreProducto').textContent = nombre;
            
            const modal = new bootstrap.Modal(document.getElementById('modalEliminar'));
            modal.show();
        }
        
        // Mostrar/ocultar botón volver arriba
        window.addEventListener('scroll', function() {
            const btnVolverArriba = document.getElementById('btnVolverArriba');
            if (window.scrollY > 300) {
                btnVolverArriba.style.display = 'block';
            } else {
                btnVolverArriba.style.display = 'none';
            }
        });
        
        // Scroll suave al hacer clic en el botón
        document.getElementById('btnVolverArriba').addEventListener('click', function(e) {
            e.preventDefault();
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
    </script>
</body>
</html>

