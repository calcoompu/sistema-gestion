<?php
require_once 'config/config.php';

iniciarSesionSegura();

// Verificar si el usuario está logueado
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

$pageTitle = "Dashboard - " . SISTEMA_NOMBRE;
$usuario_nombre = $_SESSION['usuario_nombre'] ?? 'Usuario';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        
        .navbar-brand {
            font-weight: bold;
        }
        
        .content-area {
            min-height: calc(100vh - 120px);
            padding: 20px 0;
        }
        
        .loading {
            display: none;
            text-align: center;
            padding: 50px;
        }
        
        .loading.show {
            display: block;
        }
        
        .stats-card {
            transition: transform 0.2s;
            border: none;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .stats-card:hover {
            transform: translateY(-5px);
        }
        
        .stats-icon {
            font-size: 2.5rem;
            opacity: 0.8;
        }
        
        .nav-link.active {
            background-color: rgba(255,255,255,0.2) !important;
            border-radius: 5px;
        }
        
        .dropdown-item.active {
            background-color: #0d6efd;
            color: white;
        }
        
        .page-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px 0;
            margin-bottom: 30px;
            border-radius: 10px;
        }
        
        .btn-floating {
            position: fixed;
            bottom: 30px;
            right: 30px;
            z-index: 1000;
            border-radius: 50%;
            width: 60px;
            height: 60px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.3);
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary sticky-top">
        <div class="container-fluid">
            <a class="navbar-brand" href="#" onclick="loadPage('dashboard')">
                <i class="bi bi-box-seam-fill me-2"></i><?php echo htmlspecialchars(SISTEMA_NOMBRE); ?>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarMain">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="#" onclick="loadPage('dashboard')" id="nav-dashboard">
                            <i class="bi bi-house-fill me-1"></i>Dashboard
                        </a>
                    </li>
                    
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" id="nav-inventario">
                            <i class="bi bi-box-seam me-1"></i>Inventario
                        </a>
                        <ul class="dropdown-menu">
                            <li>
                                <a class="dropdown-item" href="#" onclick="loadPage('productos')" id="nav-productos">
                                    <i class="bi bi-list-ul me-2"></i>Productos
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="#" onclick="loadPage('nuevo-producto')" id="nav-nuevo-producto">
                                    <i class="bi bi-plus-circle me-2"></i>Nuevo Producto
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item" href="#" onclick="loadPage('analisis-categoria')" id="nav-analisis-categoria">
                                    <i class="bi bi-tags me-2"></i>Analisis por Categoria
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="#" onclick="loadPage('analisis-lugar')" id="nav-analisis-lugar">
                                    <i class="bi bi-geo-alt me-2"></i>Analisis por Lugar
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item" href="#" onclick="loadPage('reportes')" id="nav-reportes">
                                    <i class="bi bi-file-earmark-bar-graph me-2"></i>Reportes
                                </a>
                            </li>
                        </ul>
                    </li>
                    
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" id="nav-pedidos">
                            <i class="bi bi-cart me-1"></i>Pedidos
                        </a>
                        <ul class="dropdown-menu">
                            <li>
                                <a class="dropdown-item" href="#" onclick="loadPage('pedidos')" id="nav-lista-pedidos">
                                    <i class="bi bi-list-check me-2"></i>Lista de Pedidos
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="#" onclick="loadPage('nuevo-pedido')" id="nav-nuevo-pedido">
                                    <i class="bi bi-plus-circle me-2"></i>Nuevo Pedido
                                </a>
                            </li>
                        </ul>
                    </li>
                    
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" id="nav-clientes">
                            <i class="bi bi-people me-1"></i>Clientes
                        </a>
                        <ul class="dropdown-menu">
                            <li>
                                <a class="dropdown-item" href="#" onclick="loadPage('clientes')" id="nav-lista-clientes">
                                    <i class="bi bi-person-lines-fill me-2"></i>Lista de Clientes
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="#" onclick="loadPage('nuevo-cliente')" id="nav-nuevo-cliente">
                                    <i class="bi bi-person-plus me-2"></i>Nuevo Cliente
                                </a>
                            </li>
                        </ul>
                    </li>
                    
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" id="nav-facturacion">
                            <i class="bi bi-receipt me-1"></i>Facturacion
                        </a>
                        <ul class="dropdown-menu">
                            <li>
                                <a class="dropdown-item" href="#" onclick="loadPage('facturas')" id="nav-lista-facturas">
                                    <i class="bi bi-file-text me-2"></i>Facturas
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="#" onclick="loadPage('nueva-factura')" id="nav-nueva-factura">
                                    <i class="bi bi-file-earmark-plus me-2"></i>Nueva Factura
                                </a>
                            </li>
                        </ul>
                    </li>
                    
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" id="nav-administracion">
                            <i class="bi bi-gear me-1"></i>Administracion
                        </a>
                        <ul class="dropdown-menu">
                            <li>
                                <a class="dropdown-item" href="#" onclick="loadPage('usuarios')" id="nav-usuarios">
                                    <i class="bi bi-people-fill me-2"></i>Usuarios
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="#" onclick="loadPage('configuracion')" id="nav-configuracion">
                                    <i class="bi bi-sliders me-2"></i>Configuracion
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item" href="#" onclick="loadPage('backup')" id="nav-backup">
                                    <i class="bi bi-cloud-download me-2"></i>Backup
                                </a>
                            </li>
                        </ul>
                    </li>
                </ul>
                
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle me-1"></i><span id="usuario-nombre"><?php echo htmlspecialchars($usuario_nombre); ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <a class="dropdown-item" href="#" onclick="loadPage('perfil')">
                                    <i class="bi bi-person-badge me-2"></i>Mi Perfil
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item" href="logout.php">
                                    <i class="bi bi-box-arrow-right me-2"></i>Cerrar Sesion
                                </a>
                            </li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Loading Indicator -->
    <div class="loading" id="loading">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Cargando...</span>
        </div>
        <p class="mt-3">Cargando contenido...</p>
    </div>

    <!-- Main Content Area -->
    <div class="container-fluid content-area">
        <div id="main-content">
            <!-- El contenido se carga aquí dinámicamente -->
        </div>
    </div>

    <!-- Floating Action Button -->
    <button class="btn btn-primary btn-floating" onclick="loadPage('nuevo-producto')" title="Nuevo Producto">
        <i class="bi bi-plus-lg"></i>
    </button>

    <!-- Footer -->
    <footer class="bg-light py-3 mt-auto">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-6 text-center text-md-start">
                    <span class="text-muted">&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars(SISTEMA_NOMBRE); ?>. Todos los derechos reservados.</span>
                </div>
                <div class="col-md-6 text-center text-md-end">
                    <span class="text-muted">Version <?php echo htmlspecialchars(SISTEMA_VERSION); ?></span>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Variables globales
        let currentPage = 'dashboard';
        let userData = {
            id: <?php echo $_SESSION['usuario_id']; ?>,
            nombre: '<?php echo htmlspecialchars($usuario_nombre); ?>',
            email: '<?php echo htmlspecialchars($_SESSION['usuario_email'] ?? ''); ?>'
        };

        // Inicializar la aplicación
        document.addEventListener('DOMContentLoaded', function() {
            // Cargar página inicial
            loadPage('dashboard');
            
            // Configurar navegación del historial
            window.addEventListener('popstate', function(e) {
                if (e.state && e.state.page) {
                    loadPage(e.state.page, false);
                }
            });
        });

        // Función principal para cargar páginas
        function loadPage(page, addToHistory = true) {
            // Mostrar loading
            showLoading();
            
            // Actualizar navegación activa
            updateActiveNav(page);
            
            // Agregar al historial del navegador
            if (addToHistory) {
                history.pushState({page: page}, '', '#' + page);
            }
            
            // Cargar contenido según la página
            switch(page) {
                case 'dashboard':
                    loadDashboard();
                    break;
                case 'productos':
                    loadProductos();
                    break;
                case 'nuevo-producto':
                    loadNuevoProducto();
                    break;
                case 'analisis-categoria':
                    loadAnalisisCategoria();
                    break;
                case 'analisis-lugar':
                    loadAnalisisLugar();
                    break;
                case 'reportes':
                    loadReportes();
                    break;
                case 'pedidos':
                case 'nuevo-pedido':
                case 'clientes':
                case 'nuevo-cliente':
                case 'facturas':
                case 'nueva-factura':
                case 'usuarios':
                case 'configuracion':
                case 'backup':
                case 'perfil':
                    loadPlaceholder(page);
                    break;
                default:
                    loadDashboard();
            }
            
            currentPage = page;
        }

        // Mostrar/ocultar loading
        function showLoading() {
            document.getElementById('loading').classList.add('show');
            document.getElementById('main-content').style.opacity = '0.5';
        }

        function hideLoading() {
            document.getElementById('loading').classList.remove('show');
            document.getElementById('main-content').style.opacity = '1';
        }

        // Actualizar navegación activa
        function updateActiveNav(page) {
            // Remover todas las clases activas
            document.querySelectorAll('.nav-link, .dropdown-item').forEach(el => {
                el.classList.remove('active');
            });
            
            // Agregar clase activa según la página
            switch(page) {
                case 'dashboard':
                    document.getElementById('nav-dashboard').classList.add('active');
                    break;
                case 'productos':
                    document.getElementById('nav-inventario').classList.add('active');
                    document.getElementById('nav-productos').classList.add('active');
                    break;
                case 'nuevo-producto':
                    document.getElementById('nav-inventario').classList.add('active');
                    document.getElementById('nav-nuevo-producto').classList.add('active');
                    break;
                case 'analisis-categoria':
                    document.getElementById('nav-inventario').classList.add('active');
                    document.getElementById('nav-analisis-categoria').classList.add('active');
                    break;
                case 'analisis-lugar':
                    document.getElementById('nav-inventario').classList.add('active');
                    document.getElementById('nav-analisis-lugar').classList.add('active');
                    break;
                case 'reportes':
                    document.getElementById('nav-inventario').classList.add('active');
                    document.getElementById('nav-reportes').classList.add('active');
                    break;
                case 'pedidos':
                case 'nuevo-pedido':
                    document.getElementById('nav-pedidos').classList.add('active');
                    document.getElementById('nav-' + (page === 'pedidos' ? 'lista-pedidos' : 'nuevo-pedido')).classList.add('active');
                    break;
                case 'clientes':
                case 'nuevo-cliente':
                    document.getElementById('nav-clientes').classList.add('active');
                    document.getElementById('nav-' + (page === 'clientes' ? 'lista-clientes' : 'nuevo-cliente')).classList.add('active');
                    break;
                case 'facturas':
                case 'nueva-factura':
                    document.getElementById('nav-facturacion').classList.add('active');
                    document.getElementById('nav-' + (page === 'facturas' ? 'lista-facturas' : 'nueva-factura')).classList.add('active');
                    break;
                case 'usuarios':
                case 'configuracion':
                case 'backup':
                    document.getElementById('nav-administracion').classList.add('active');
                    document.getElementById('nav-' + page).classList.add('active');
                    break;
            }
        }

        // Cargar Dashboard
        function loadDashboard() {
            fetch('modulos/Inventario/get_dashboard.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        renderDashboard(data.stats);
                    } else {
                        showError('Error cargando dashboard: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showError('Error de conexión al cargar dashboard');
                })
                .finally(() => {
                    hideLoading();
                });
        }

        // Renderizar Dashboard
        function renderDashboard(stats) {
            const content = `
                <div class="page-header">
                    <div class="container-fluid">
                        <div class="row">
                            <div class="col-md-8">
                                <h1 class="mb-0">
                                    <i class="bi bi-speedometer2 me-2"></i>Dashboard
                                </h1>
                                <p class="mb-0 opacity-75">Bienvenido, ${userData.nombre}. Aqui tienes un resumen de tu sistema.</p>
                            </div>
                            <div class="col-md-4 text-end">
                                <button class="btn btn-light" onclick="loadPage('nuevo-producto')">
                                    <i class="bi bi-plus-circle me-1"></i>Nuevo Producto
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-3">
                        <div class="card bg-primary text-white stats-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title mb-0">Productos</h6>
                                        <h2 class="mb-0">${stats.productos || 0}</h2>
                                    </div>
                                    <div class="stats-icon">
                                        <i class="bi bi-box-seam-fill"></i>
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <a href="#" onclick="loadPage('productos')" class="text-white text-decoration-none small">
                                        Ver todos <i class="bi bi-arrow-right"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="card bg-success text-white stats-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title mb-0">Categorias</h6>
                                        <h2 class="mb-0">${stats.categorias || 0}</h2>
                                    </div>
                                    <div class="stats-icon">
                                        <i class="bi bi-tags-fill"></i>
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <a href="#" onclick="loadPage('analisis-categoria')" class="text-white text-decoration-none small">
                                        Ver analisis <i class="bi bi-arrow-right"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="card bg-info text-white stats-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title mb-0">Stock Total</h6>
                                        <h2 class="mb-0">${stats.stock_total || 0}</h2>
                                    </div>
                                    <div class="stats-icon">
                                        <i class="bi bi-boxes"></i>
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <span class="text-white text-decoration-none small">
                                        Unidades en stock
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="card bg-warning text-dark stats-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title mb-0">Valor Total</h6>
                                        <h2 class="mb-0">$ ${stats.valor_total || 0}</h2>
                                    </div>
                                    <div class="stats-icon">
                                        <i class="bi bi-currency-dollar"></i>
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <a href="#" onclick="loadPage('reportes')" class="text-dark text-decoration-none small">
                                        Ver reportes <i class="bi bi-arrow-right"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mt-4">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header bg-primary text-white">
                                <h5 class="card-title mb-0">
                                    <i class="bi bi-lightning-fill me-2"></i>Accesos Rapidos
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-3">
                                        <button onclick="loadPage('productos')" class="btn btn-outline-primary w-100 py-3">
                                            <i class="bi bi-box-seam-fill d-block fs-1 mb-2"></i>
                                            <span>Ver Inventario</span>
                                        </button>
                                    </div>
                                    <div class="col-md-3">
                                        <button onclick="loadPage('nuevo-producto')" class="btn btn-outline-success w-100 py-3">
                                            <i class="bi bi-plus-circle-fill d-block fs-1 mb-2"></i>
                                            <span>Nuevo Producto</span>
                                        </button>
                                    </div>
                                    <div class="col-md-3">
                                        <button onclick="loadPage('analisis-categoria')" class="btn btn-outline-info w-100 py-3">
                                            <i class="bi bi-tags-fill d-block fs-1 mb-2"></i>
                                            <span>Por Categoria</span>
                                        </button>
                                    </div>
                                    <div class="col-md-3">
                                        <button onclick="loadPage('reportes')" class="btn btn-outline-warning w-100 py-3">
                                            <i class="bi bi-file-earmark-bar-graph-fill d-block fs-1 mb-2"></i>
                                            <span>Reportes</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            document.getElementById('main-content').innerHTML = content;
        }

        // Cargar Productos
        function loadProductos() {
            fetch('modulos/Inventario/productos.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        renderProductos(data.productos);
                    } else {
                        showError('Error cargando productos: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showError('Error de conexión al cargar productos');
                })
                .finally(() => {
                    hideLoading();
                });
        }

        // Renderizar lista de productos
        function renderProductos(productos) {
            let productosHtml = '';
            
            if (productos && productos.length > 0) {
                productos.forEach(producto => {
                    productosHtml += `
                        <tr>
                            <td><code>${producto.codigo}</code></td>
                            <td>${producto.nombre}</td>
                            <td><span class="badge bg-info">${producto.categoria || 'N/A'}</span></td>
                            <td class="text-center">${producto.stock}</td>
                            <td class="text-end">$ ${producto.precio_formateado}</td>
                            <td class="text-center">
                                <button class="btn btn-sm btn-outline-primary" onclick="editarProducto(${producto.id})">
                                    <i class="bi bi-pencil"></i>
                                </button>
                            </td>
                        </tr>
                    `;
                });
            } else {
                productosHtml = `
                    <tr>
                        <td colspan="6" class="text-center py-4">
                            <i class="bi bi-box text-muted fs-1 d-block mb-2"></i>
                            <span class="text-muted">No hay productos registrados</span>
                        </td>
                    </tr>
                `;
            }

            const content = `
                <div class="page-header">
                    <div class="container-fluid">
                        <div class="row">
                            <div class="col-md-8">
                                <h1 class="mb-0">
                                    <i class="bi bi-box-seam me-2"></i>Productos
                                </h1>
                                <p class="mb-0 opacity-75">Gestiona tu inventario de productos</p>
                            </div>
                            <div class="col-md-4 text-end">
                                <button class="btn btn-light" onclick="loadPage('nuevo-producto')">
                                    <i class="bi bi-plus-circle me-1"></i>Nuevo Producto
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <div class="row align-items-center">
                            <div class="col-md-6">
                                <h5 class="mb-0">Lista de Productos</h5>
                            </div>
                            <div class="col-md-6 text-end">
                                <div class="input-group" style="max-width: 300px; margin-left: auto;">
                                    <input type="text" class="form-control" placeholder="Buscar productos..." id="searchInput">
                                    <button class="btn btn-outline-secondary" type="button">
                                        <i class="bi bi-search"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Codigo</th>
                                        <th>Nombre</th>
                                        <th>Categoria</th>
                                        <th class="text-center">Stock</th>
                                        <th class="text-end">Precio</th>
                                        <th class="text-center">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${productosHtml}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            `;
            
            document.getElementById('main-content').innerHTML = content;
        }

        // Placeholder para otras funciones de carga
        function loadNuevoProducto() {
            const content = `
                <div class="page-header">
                    <div class="container-fluid">
                        <h1 class="mb-0">
                            <i class="bi bi-plus-circle me-2"></i>Nuevo Producto
                        </h1>
                        <p class="mb-0 opacity-75">Agregar un nuevo producto al inventario</p>
                    </div>
                </div>
                <div class="card">
                    <div class="card-body">
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-2"></i>
                            Funcionalidad en desarrollo. Próximamente disponible.
                        </div>
                        <button class="btn btn-secondary" onclick="loadPage('productos')">
                            <i class="bi bi-arrow-left me-1"></i>Volver a Productos
                        </button>
                    </div>
                </div>
            `;
            document.getElementById('main-content').innerHTML = content;
            hideLoading();
        }

        function loadAnalisisCategoria() {
            const content = `
                <div class="page-header">
                    <div class="container-fluid">
                        <h1 class="mb-0">
                            <i class="bi bi-tags me-2"></i>Analisis por Categoria
                        </h1>
                        <p class="mb-0 opacity-75">Analiza tu inventario por categorias</p>
                    </div>
                </div>
                <div class="card">
                    <div class="card-body">
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-2"></i>
                            Funcionalidad en desarrollo. Próximamente disponible.
                        </div>
                        <button class="btn btn-secondary" onclick="loadPage('dashboard')">
                            <i class="bi bi-arrow-left me-1"></i>Volver al Dashboard
                        </button>
                    </div>
                </div>
            `;
            document.getElementById('main-content').innerHTML = content;
            hideLoading();
        }

        function loadAnalisisLugar() {
            const content = `
                <div class="page-header">
                    <div class="container-fluid">
                        <h1 class="mb-0">
                            <i class="bi bi-geo-alt me-2"></i>Analisis por Lugar
                        </h1>
                        <p class="mb-0 opacity-75">Analiza tu inventario por ubicaciones</p>
                    </div>
                </div>
                <div class="card">
                    <div class="card-body">
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-2"></i>
                            Funcionalidad en desarrollo. Próximamente disponible.
                        </div>
                        <button class="btn btn-secondary" onclick="loadPage('dashboard')">
                            <i class="bi bi-arrow-left me-1"></i>Volver al Dashboard
                        </button>
                    </div>
                </div>
            `;
            document.getElementById('main-content').innerHTML = content;
            hideLoading();
        }

        function loadReportes() {
            const content = `
                <div class="page-header">
                    <div class="container-fluid">
                        <h1 class="mb-0">
                            <i class="bi bi-file-earmark-bar-graph me-2"></i>Reportes
                        </h1>
                        <p class="mb-0 opacity-75">Reportes y estadisticas del sistema</p>
                    </div>
                </div>
                <div class="card">
                    <div class="card-body">
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-2"></i>
                            Funcionalidad en desarrollo. Próximamente disponible.
                        </div>
                        <button class="btn btn-secondary" onclick="loadPage('dashboard')">
                            <i class="bi bi-arrow-left me-1"></i>Volver al Dashboard
                        </button>
                    </div>
                </div>
            `;
            document.getElementById('main-content').innerHTML = content;
            hideLoading();
        }

        function loadPlaceholder(page) {
            const pageNames = {
                'pedidos': 'Pedidos',
                'nuevo-pedido': 'Nuevo Pedido',
                'clientes': 'Clientes',
                'nuevo-cliente': 'Nuevo Cliente',
                'facturas': 'Facturas',
                'nueva-factura': 'Nueva Factura',
                'usuarios': 'Usuarios',
                'configuracion': 'Configuración',
                'backup': 'Backup',
                'perfil': 'Mi Perfil'
            };

            const content = `
                <div class="page-header">
                    <div class="container-fluid">
                        <h1 class="mb-0">
                            <i class="bi bi-gear me-2"></i>${pageNames[page] || 'Página'}
                        </h1>
                        <p class="mb-0 opacity-75">Funcionalidad en desarrollo</p>
                    </div>
                </div>
                <div class="card">
                    <div class="card-body">
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-2"></i>
                            Esta funcionalidad estará disponible próximamente.
                        </div>
                        <button class="btn btn-secondary" onclick="loadPage('dashboard')">
                            <i class="bi bi-arrow-left me-1"></i>Volver al Dashboard
                        </button>
                    </div>
                </div>
            `;
            document.getElementById('main-content').innerHTML = content;
            hideLoading();
        }

        // Función para mostrar errores
        function showError(message) {
            const content = `
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    ${message}
                </div>
                <button class="btn btn-secondary" onclick="loadPage('dashboard')">
                    <i class="bi bi-arrow-left me-1"></i>Volver al Dashboard
                </button>
            `;
            document.getElementById('main-content').innerHTML = content;
        }

        // Función placeholder para editar producto
        function editarProducto(id) {
            alert('Funcionalidad de edición en desarrollo. ID: ' + id);
        }
    </script>
</body>
</html>

