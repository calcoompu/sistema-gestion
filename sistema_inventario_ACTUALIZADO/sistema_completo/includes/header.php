<?php
/**
 * Header principal con menu unico para todo el sistema
 * 
 * Este archivo contiene el header con el menu de navegacion que se incluye en todas las paginas
 */

// Funcion para determinar la ruta base
function getBasePath() {
    // Obtener la ruta del script actual
    $current_path = $_SERVER['SCRIPT_NAME'];
    $path_parts = explode('/', $current_path);
    
    // Si estamos en un subdirectorio (modulos/X/), subir dos niveles
    if (count($path_parts) > 3 && $path_parts[count($path_parts) - 3] == 'modulos') {
        return '../..';
    }
    // Si estamos en la raiz de modulos (modulos/), subir un nivel
    else if (count($path_parts) > 2 && $path_parts[count($path_parts) - 2] == 'modulos') {
        return '..';
    }
    // Si estamos en la raiz
    else {
        return '.';
    }
}

// Obtener la ruta base
$base_path = getBasePath();

// Determinar la página activa
$current_page = basename($_SERVER['SCRIPT_NAME']);
$current_dir = dirname($_SERVER['SCRIPT_NAME']);

// Función para verificar si una página está activa
function isActive($page_name) {
    global $current_page, $current_dir;
    
    if ($page_name == 'menu_principal.php' && $current_page == 'menu_principal.php') {
        return true;
    }
    
    if ($page_name == 'inventario' && (strpos($current_dir, '/Inventario') !== false || strpos($current_page, 'producto') !== false)) {
        return true;
    }
    
    return false;
}

// Función para obtener la clase activa
function getActiveClass($page_name) {
    return isActive($page_name) ? 'active' : '';
}
?>

<header>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="<?php echo $base_path; ?>/menu_principal.php">
                <i class="bi bi-box-seam-fill me-2"></i><?php echo htmlspecialchars(SISTEMA_NOMBRE); ?>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarMain">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link <?php echo getActiveClass('menu_principal.php'); ?>" href="<?php echo $base_path; ?>/menu_principal.php">
                            <i class="bi bi-house-fill me-1"></i>Dashboard
                        </a>
                    </li>
                    
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle <?php echo getActiveClass('inventario'); ?>" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-box-seam me-1"></i>Inventario
                        </a>
                        <ul class="dropdown-menu">
                            <li>
                                <a class="dropdown-item" href="<?php echo $base_path; ?>/modulos/Inventario/index.php">
                                    <i class="bi bi-list-ul me-2"></i>Productos
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="<?php echo $base_path; ?>/modulos/Inventario/producto_form.php">
                                    <i class="bi bi-plus-circle me-2"></i>Nuevo Producto
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item" href="<?php echo $base_path; ?>/modulos/Inventario/productos_por_categoria.php">
                                    <i class="bi bi-tags me-2"></i>Analisis por Categoria
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="<?php echo $base_path; ?>/modulos/Inventario/productos_por_lugar.php">
                                    <i class="bi bi-geo-alt me-2"></i>Analisis por Lugar
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item" href="<?php echo $base_path; ?>/modulos/Inventario/reportes.php">
                                    <i class="bi bi-file-earmark-bar-graph me-2"></i>Reportes
                                </a>
                            </li>
                        </ul>
                    </li>
                </ul>
                
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle me-1"></i><?php echo htmlspecialchars($_SESSION['usuario_nombre'] ?? 'Usuario'); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <a class="dropdown-item" href="<?php echo $base_path; ?>/logout.php">
                                    <i class="bi bi-box-arrow-right me-2"></i>Cerrar Sesion
                                </a>
                            </li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
</header>

