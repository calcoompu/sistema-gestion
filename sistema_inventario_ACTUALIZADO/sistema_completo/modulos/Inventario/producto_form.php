<?php
/**
 * Formulario de producto
 * 
 * Este archivo maneja la creación y edición de productos.
 */

// Incluir configuración
require_once '../../config/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/inventario_functions.php';
require_once '../../includes/codigo_functions.php';

// Iniciar sesión segura
iniciarSesionSegura();

// Verificar si el usuario está logueado
requireLogin('../../login.php');

// Verificar permisos
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id > 0) {
    requirePermission('inventario_productos', 'edit', '../../menu_principal.php');
} else {
    requirePermission('inventario_productos', 'create', '../../menu_principal.php');
}

// Conectar a la base de datos
$pdo = conectarDB();

// Inicializar variables
$producto = [
    'id' => 0,
    'codigo' => '',
    'nombre' => '',
    'descripcion' => '',
    'categoria_id' => '',
    'lugar_id' => '',
    'precio_compra' => '',
    'precio_venta' => '',
    'stock' => 0,
    'stock_minimo' => 0,
    'imagen' => '',
    'activo' => 1
];

$es_nuevo = true;
$errores = [];

// Si es edición, cargar datos del producto
if ($id > 0) {
    $es_nuevo = false;
    $producto_db = obtenerProductoPorId($pdo, $id);
    
    if ($producto_db) {
        $producto = $producto_db;
    } else {
        $_SESSION['error_message'] = "El producto solicitado no existe.";
        header('Location: index.php');
        exit;
    }
} else {
    // Si es nuevo, generar código automático
    $producto['codigo'] = generarCodigoProducto($pdo);
}

// Procesar formulario si se envió
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verificar token CSRF
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $_SESSION['error_message'] = "Error de validación. Por favor, intente nuevamente.";
        header('Location: ' . $_SERVER['PHP_SELF'] . ($id ? "?id=$id" : ''));
        exit;
    }
    
    // Obtener datos del formulario
    $producto['nombre'] = isset($_POST['nombre']) ? trim($_POST['nombre']) : '';
    $producto['descripcion'] = isset($_POST['descripcion']) ? trim($_POST['descripcion']) : '';
    $producto['categoria_id'] = isset($_POST['categoria_id']) && $_POST['categoria_id'] !== '' ? (int)$_POST['categoria_id'] : null;
    $producto['lugar_id'] = isset($_POST['lugar_id']) && $_POST['lugar_id'] !== '' ? (int)$_POST['lugar_id'] : null;
    $producto['precio_compra'] = isset($_POST['precio_compra']) && $_POST['precio_compra'] !== '' ? (float)$_POST['precio_compra'] : null;
    $producto['precio_venta'] = isset($_POST['precio_venta']) ? (float)$_POST['precio_venta'] : 0;
    $producto['stock'] = $es_nuevo ? (isset($_POST['stock']) ? (int)$_POST['stock'] : 0) : $producto['stock'];
    $producto['stock_minimo'] = isset($_POST['stock_minimo']) ? (int)$_POST['stock_minimo'] : 0;
    $producto['activo'] = isset($_POST['activo']) ? 1 : 0;
    
    // Si es nuevo, usar el código generado automáticamente
    if ($es_nuevo) {
        $producto['codigo'] = isset($_POST['codigo']) ? trim($_POST['codigo']) : generarCodigoProducto($pdo);
    }
    
    // Validar datos
    if (empty($producto['nombre'])) {
        $errores[] = "El nombre del producto es obligatorio.";
    }
    
    if (empty($producto['codigo'])) {
        $errores[] = "El código del producto es obligatorio.";
    } else {
        // Verificar si el código ya existe (solo para nuevos productos)
        if ($es_nuevo) {
            $producto_existente = obtenerProductoPorCodigo($pdo, $producto['codigo']);
            if ($producto_existente) {
                $errores[] = "El código del producto ya está en uso.";
            }
        }
    }
    
    if ($producto['precio_venta'] <= 0) {
        $errores[] = "El precio de venta debe ser mayor que cero.";
    }
    
    // Procesar imagen si se subió
    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $max_size = 2 * 1024 * 1024; // 2MB
        
        $upload_result = uploadFile(
            $_FILES['imagen'],
            UPLOADS_PATH . '/',
            $allowed_types,
            $max_size
        );
        
        if ($upload_result['success']) {
            // Si hay una imagen anterior, eliminarla
            if (!empty($producto['imagen']) && file_exists(UPLOADS_PATH . '/' . $producto['imagen'])) {
                @unlink(UPLOADS_PATH . '/' . $producto['imagen']);
            }
            
            $producto['imagen'] = $upload_result['filename'];
        } else {
            $errores[] = "Error al subir la imagen: " . $upload_result['message'];
        }
    }
    
    // Si no hay errores, guardar producto
    if (empty($errores)) {
        if ($es_nuevo) {
            // Crear nuevo producto
            $resultado = crearProducto($pdo, $producto);
            
            if ($resultado) {
                $_SESSION['success_message'] = "Producto creado correctamente.";
                header('Location: index.php');
                exit;
            } else {
                $errores[] = "Error al crear el producto.";
            }
        } else {
            // Actualizar producto existente
            $resultado = actualizarProducto($pdo, $id, $producto);
            
            if ($resultado) {
                $_SESSION['success_message'] = "Producto actualizado correctamente.";
                header('Location: index.php');
                exit;
            } else {
                $errores[] = "Error al actualizar el producto.";
            }
        }
    }
}

// Obtener categorías y lugares para el formulario
$categorias = obtenerCategorias($pdo);
$lugares = obtenerLugares($pdo);

// Título de la página
$pageTitle = ($es_nuevo ? "Crear Nuevo" : "Editar") . " Producto - " . SISTEMA_NOMBRE;
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
        <div class="row mb-4">
            <div class="col-md-8">
                <h2 class="fw-bold text-primary">
                    <i class="bi bi-<?= $es_nuevo ? 'plus-circle' : 'pencil' ?>-fill me-2"></i>
                    <?= $es_nuevo ? "Crear Nuevo" : "Editar" ?> Producto
                </h2>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="../../menu_principal.php">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="index.php">Inventario</a></li>
                        <li class="breadcrumb-item active" aria-current="page"><?= $es_nuevo ? "Nuevo Producto" : "Editar Producto" ?></li>
                    </ol>
                </nav>
            </div>
            <div class="col-md-4 text-end">
                <a href="index.php" class="btn btn-secondary">
                    <i class="bi bi-arrow-left me-1"></i>Volver al Listado
                </a>
            </div>
        </div>

        <?php if (!empty($errores)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <h5 class="alert-heading"><i class="bi bi-exclamation-triangle-fill me-2"></i>Error</h5>
                <ul class="mb-0">
                    <?php foreach ($errores as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0">
                    <i class="bi bi-box-seam-fill me-2"></i>Datos del Producto
                </h5>
            </div>
            <div class="card-body">
                <form method="POST" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) . ($id ? "?id=$id" : '') ?>" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="codigo" class="form-label">Código <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="codigo" name="codigo" value="<?= htmlspecialchars($producto['codigo']) ?>" <?= $es_nuevo ? '' : 'readonly' ?> required>
                            <div class="form-text">El código se genera automáticamente para nuevos productos.</div>
                        </div>
                        <div class="col-md-6">
                            <label for="nombre" class="form-label">Nombre <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="nombre" name="nombre" value="<?= htmlspecialchars($producto['nombre']) ?>" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="descripcion" class="form-label">Descripción</label>
                        <textarea class="form-control" id="descripcion" name="descripcion" rows="3"><?= htmlspecialchars($producto['descripcion']) ?></textarea>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="categoria_id" class="form-label">Categoría</label>
                            <select class="form-select" id="categoria_id" name="categoria_id">
                                <option value="">Seleccione una categoría...</option>
                                <?php foreach ($categorias as $categoria): ?>
                                    <option value="<?= $categoria['id'] ?>" <?= $producto['categoria_id'] == $categoria['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($categoria['nombre']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="lugar_id" class="form-label">Ubicación</label>
                            <select class="form-select" id="lugar_id" name="lugar_id">
                                <option value="">Seleccione una ubicación...</option>
                                <?php foreach ($lugares as $lugar): ?>
                                    <option value="<?= $lugar['id'] ?>" <?= $producto['lugar_id'] == $lugar['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($lugar['nombre']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <label for="precio_compra" class="form-label">Precio Compra</label>
                            <div class="input-group">
                                <span class="input-group-text">€</span>
                                <input type="number" class="form-control" id="precio_compra" name="precio_compra" value="<?= htmlspecialchars($producto['precio_compra']) ?>" step="0.01" min="0">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label for="precio_venta" class="form-label">Precio Venta <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">€</span>
                                <input type="number" class="form-control" id="precio_venta" name="precio_venta" value="<?= htmlspecialchars($producto['precio_venta']) ?>" step="0.01" min="0.01" required>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label for="stock" class="form-label">Stock Inicial</label>
                            <input type="number" class="form-control" id="stock" name="stock" value="<?= htmlspecialchars($producto['stock']) ?>" min="0" <?= $es_nuevo ? '' : 'readonly' ?>>
                            <?php if (!$es_nuevo): ?>
                                <div class="form-text">Para modificar el stock, use la opción "Actualizar Stock".</div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-3">
                            <label for="stock_minimo" class="form-label">Stock Mínimo</label>
                            <input type="number" class="form-control" id="stock_minimo" name="stock_minimo" value="<?= htmlspecialchars($producto['stock_minimo']) ?>" min="0">
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="imagen" class="form-label">Imagen</label>
                            <input type="file" class="form-control" id="imagen" name="imagen" accept="image/*">
                            <div class="form-text">Formatos permitidos: JPG, PNG, GIF, WebP. Tamaño máximo: 2MB.</div>
                        </div>
                        <div class="col-md-6">
                            <?php if (!empty($producto['imagen']) && file_exists(UPLOADS_PATH . '/' . $producto['imagen'])): ?>
                                <label class="form-label">Imagen Actual</label>
                                <div>
                                    <img src="../../assets/img/productos/<?= htmlspecialchars($producto['imagen']) ?>" alt="<?= htmlspecialchars($producto['nombre']) ?>" class="img-thumbnail" style="max-height: 100px;">
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="activo" name="activo" <?= $producto['activo'] ? 'checked' : '' ?>>
                        <label class="form-check-label" for="activo">Producto Activo</label>
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <a href="index.php" class="btn btn-secondary">
                            <i class="bi bi-x-circle me-1"></i>Cancelar
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save me-1"></i><?= $es_nuevo ? 'Crear' : 'Actualizar' ?> Producto
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php include '../../includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Calcular precio de venta automáticamente (ejemplo: 30% de margen)
        document.getElementById('precio_compra').addEventListener('change', function() {
            const precioCompra = parseFloat(this.value) || 0;
            const margen = 1.3; // 30% de margen
            
            if (precioCompra > 0) {
                const precioVenta = (precioCompra * margen).toFixed(2);
                
                // Solo actualizar si el campo está vacío o si el usuario lo permite
                const precioVentaInput = document.getElementById('precio_venta');
                if (precioVentaInput.value === '' || confirm('¿Desea actualizar el precio de venta basado en el precio de compra?')) {
                    precioVentaInput.value = precioVenta;
                }
            }
        });
    </script>
</body>
</html>

