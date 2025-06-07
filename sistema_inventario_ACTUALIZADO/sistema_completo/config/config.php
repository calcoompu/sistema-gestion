<?php
// Configuración de la base de datos
define('DB_HOST', 'localhost');
define('DB_USER', 'sistemasia_inventpro');
define('DB_PASS', 'Santiago2980%%');
define('DB_NAME', 'sistemasia_inventpro');

// Configuración del sistema
define('SISTEMA_NOMBRE', 'InventPro');
define('SISTEMA_VERSION', '1.0.0');

// Rutas del sistema
define('UPLOADS_PATH', __DIR__ . '/../assets/uploads');

// Zona horaria
date_default_timezone_set('America/Argentina/Buenos_Aires');

// Función para conectar a la base de datos
function conectarDB() {
    try {
        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch (PDOException $e) {
        die("Error de conexión: " . $e->getMessage());
    }
}

// Función para iniciar sesión
function iniciarSesionSegura() {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
}

// Función para formatear moneda
function formatCurrency($amount) {
    return '$ ' . number_format($amount, 2, ',', '.');
}

// Función para verificar login
function requireLogin($redirect = 'login.php') {
    if (!isset($_SESSION['usuario_id'])) {
        header("Location: $redirect");
        exit;
    }
}

// Función para verificar permisos
function hasPermission($modulo, $accion) {
    return isset($_SESSION['usuario_id']);
}

// Función para requerir permisos
function requirePermission($modulo, $accion, $redirect = 'menu_principal.php') {
    if (!hasPermission($modulo, $accion)) {
        header("Location: $redirect");
        exit;
    }
}

// Función para obtener estadísticas básicas
function obtenerEstadisticasInventario($pdo) {
    try {
        $sql = "SELECT COUNT(*) as total_productos FROM productos WHERE activo = 1";
        $stmt = $pdo->query($sql);
        $result = $stmt->fetch();
        return array(
            'total_productos' => $result['total_productos'],
            'productos_bajo_stock' => 0,
            'valor_total_inventario' => 0,
            'precio_promedio' => 0
        );
    } catch (Exception $e) {
        return array(
            'total_productos' => 0,
            'productos_bajo_stock' => 0,
            'valor_total_inventario' => 0,
            'precio_promedio' => 0
        );
    }
}

