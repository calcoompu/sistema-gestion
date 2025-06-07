<?php
/**
 * Funciones generales del sistema
 * 
 * Este archivo contiene funciones de utilidad que se utilizan en todo el sistema.
 */

/**
 * Verifica si el usuario está logueado, si no, redirige a la página de login
 * 
 * @param string $redirect_url URL a la que redirigir si no está logueado
 * @return void
 */
function requireLogin($redirect_url = 'login.php') {
    if (!isset($_SESSION['usuario_id'])) {
        header('Location: ' . $redirect_url);
        exit;
    }
}

/**
 * Verifica si el usuario tiene un permiso específico
 * 
 * @param string $modulo Nombre del módulo
 * @param string $accion Nombre de la acción (view_list, view_detail, create, edit, delete)
 * @param string $redirect_url URL a la que redirigir si no tiene permiso
 * @return void
 */
function requirePermission($modulo, $accion, $redirect_url = 'index.php') {
    if (!hasPermission($modulo, $accion)) {
        $_SESSION['error_message'] = "No tiene permiso para realizar esta acción.";
        header('Location: ' . $redirect_url);
        exit;
    }
}

/**
 * Comprueba si el usuario tiene un permiso específico
 * 
 * @param string $modulo Nombre del módulo
 * @param string $accion Nombre de la acción
 * @return bool True si tiene permiso, false en caso contrario
 */
function hasPermission($modulo, $accion) {
    // Si es administrador, tiene todos los permisos
    if ($_SESSION['usuario_rol'] === 'admin') {
        return true;
    }
    
    // Definir permisos por rol
    $permisos = [
        'gerente' => [
            'inventario_productos' => ['view_list', 'view_detail', 'create', 'edit', 'delete'],
            'inventario_categorias' => ['view_list', 'view_detail', 'create', 'edit', 'delete', 'manage'],
            'inventario_lugares' => ['view_list', 'view_detail', 'create', 'edit', 'delete', 'manage'],
            'inventario_stock' => ['view_list', 'view_detail', 'update'],
            'inventario_reportes' => ['view_list', 'generate'],
            'clientes' => ['view_list', 'view_detail', 'create', 'edit', 'delete'],
            'pedidos' => ['view_list', 'view_detail', 'create', 'edit', 'delete'],
            'facturacion' => ['view_list', 'view_detail', 'create', 'edit', 'delete'],
        ],
        'vendedor' => [
            'inventario_productos' => ['view_list', 'view_detail'],
            'inventario_categorias' => ['view_list', 'view_detail'],
            'inventario_lugares' => ['view_list', 'view_detail'],
            'clientes' => ['view_list', 'view_detail', 'create', 'edit'],
            'pedidos' => ['view_list', 'view_detail', 'create', 'edit'],
            'facturacion' => ['view_list', 'view_detail', 'create'],
        ],
        'inventario' => [
            'inventario_productos' => ['view_list', 'view_detail', 'create', 'edit'],
            'inventario_categorias' => ['view_list', 'view_detail'],
            'inventario_lugares' => ['view_list', 'view_detail'],
            'inventario_stock' => ['view_list', 'view_detail', 'update'],
        ],
    ];
    
    // Verificar si el rol tiene el permiso
    if (isset($permisos[$_SESSION['usuario_rol']][$modulo]) && 
        in_array($accion, $permisos[$_SESSION['usuario_rol']][$modulo])) {
        return true;
    }
    
    return false;
}

/**
 * Genera un token CSRF y lo guarda en la sesión
 * 
 * @return string Token CSRF
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Valida un token CSRF
 * 
 * @param string $token Token a validar
 * @return bool True si es válido, false en caso contrario
 */
function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Muestra mensajes de éxito o error almacenados en la sesión
 * 
 * @return void
 */
function displayGlobalMessages() {
    if (isset($_SESSION['success_message'])) {
        echo '<div class="alert alert-success alert-dismissible fade show" role="alert">';
        echo '<i class="bi bi-check-circle-fill me-2"></i>' . htmlspecialchars($_SESSION['success_message']);
        echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
        echo '</div>';
        unset($_SESSION['success_message']);
    }
    
    if (isset($_SESSION['error_message'])) {
        echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">';
        echo '<i class="bi bi-exclamation-triangle-fill me-2"></i>' . htmlspecialchars($_SESSION['error_message']);
        echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
        echo '</div>';
        unset($_SESSION['error_message']);
    }
}

/**
 * Sube un archivo al servidor
 * 
 * @param array $file Archivo a subir ($_FILES['nombre'])
 * @param string $upload_dir Directorio de destino
 * @param array $allowed_types Tipos MIME permitidos
 * @param int $max_size Tamaño máximo en bytes
 * @return array Resultado de la operación
 */
function uploadFile($file, $upload_dir, $allowed_types = [], $max_size = 2097152) {
    $result = [
        'success' => false,
        'filename' => '',
        'message' => ''
    ];
    
    // Verificar si hay error en la subida
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $result['message'] = getFileUploadErrorMessage($file['error']);
        return $result;
    }
    
    // Verificar tamaño
    if ($file['size'] > $max_size) {
        $result['message'] = 'El archivo excede el tamaño máximo permitido.';
        return $result;
    }
    
    // Verificar tipo MIME
    if (!empty($allowed_types) && !in_array($file['type'], $allowed_types)) {
        $result['message'] = 'Tipo de archivo no permitido.';
        return $result;
    }
    
    // Crear directorio si no existe
    if (!is_dir($upload_dir)) {
        if (!mkdir($upload_dir, 0755, true)) {
            $result['message'] = 'Error al crear el directorio de destino.';
            return $result;
        }
    }
    
    // Generar nombre único
    $filename = uniqid() . '_' . basename($file['name']);
    $target_file = $upload_dir . $filename;
    
    // Mover archivo
    if (move_uploaded_file($file['tmp_name'], $target_file)) {
        $result['success'] = true;
        $result['filename'] = $filename;
    } else {
        $result['message'] = 'Error al mover el archivo subido.';
    }
    
    return $result;
}

/**
 * Obtiene el mensaje de error para códigos de error de subida de archivos
 * 
 * @param int $error_code Código de error
 * @return string Mensaje de error
 */
function getFileUploadErrorMessage($error_code) {
    switch ($error_code) {
        case UPLOAD_ERR_INI_SIZE:
            return 'El archivo excede el tamaño máximo permitido por PHP.';
        case UPLOAD_ERR_FORM_SIZE:
            return 'El archivo excede el tamaño máximo permitido por el formulario.';
        case UPLOAD_ERR_PARTIAL:
            return 'El archivo se subió parcialmente.';
        case UPLOAD_ERR_NO_FILE:
            return 'No se subió ningún archivo.';
        case UPLOAD_ERR_NO_TMP_DIR:
            return 'Falta la carpeta temporal.';
        case UPLOAD_ERR_CANT_WRITE:
            return 'Error al escribir el archivo en el disco.';
        case UPLOAD_ERR_EXTENSION:
            return 'Una extensión de PHP detuvo la subida del archivo.';
        default:
            return 'Error desconocido al subir el archivo.';
    }
}

/**
 * Sanitiza una cadena para evitar inyección SQL y XSS
 * 
 * @param string $str Cadena a sanitizar
 * @return string Cadena sanitizada
 */
function sanitizeString($str) {
    return htmlspecialchars(trim($str), ENT_QUOTES, 'UTF-8');
}

/**
 * Registra una acción en el log del sistema
 * 
 * @param string $accion Acción realizada
 * @param string $tabla Tabla afectada
 * @param int $registro_id ID del registro afectado
 * @param string $datos Datos adicionales (JSON)
 * @return void
 */
function logSistema($accion, $tabla = null, $registro_id = null, $datos = null) {
    $pdo = conectarDB();
    
    $sql = "INSERT INTO sistema_log (usuario_id, accion, tabla, registro_id, datos, ip) 
            VALUES (:usuario_id, :accion, :tabla, :registro_id, :datos, :ip)";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'usuario_id' => $_SESSION['usuario_id'] ?? null,
        'accion' => $accion,
        'tabla' => $tabla,
        'registro_id' => $registro_id,
        'datos' => $datos,
        'ip' => $_SERVER['REMOTE_ADDR']
    ]);
}

/**
 * Obtiene el valor de una configuración del sistema
 * 
 * @param string $clave Clave de la configuración
 * @param mixed $default Valor por defecto si no existe
 * @return mixed Valor de la configuración
 */
function getConfiguracion($clave, $default = null) {
    static $config_cache = [];
    
    // Si ya está en caché, devolverla
    if (isset($config_cache[$clave])) {
        return $config_cache[$clave];
    }
    
    // Obtener de la base de datos
    $pdo = conectarDB();
    $stmt = $pdo->prepare("SELECT valor FROM configuracion WHERE clave = :clave");
    $stmt->execute(['clave' => $clave]);
    
    $resultado = $stmt->fetch();
    
    if ($resultado) {
        $config_cache[$clave] = $resultado['valor'];
        return $resultado['valor'];
    }
    
    return $default;
}

/**
 * Establece el valor de una configuración del sistema
 * 
 * @param string $clave Clave de la configuración
 * @param mixed $valor Valor de la configuración
 * @param string $descripcion Descripción de la configuración
 * @return bool True si se actualizó correctamente, false en caso contrario
 */
function setConfiguracion($clave, $valor, $descripcion = null) {
    $pdo = conectarDB();
    
    // Verificar si ya existe
    $stmt = $pdo->prepare("SELECT id FROM configuracion WHERE clave = :clave");
    $stmt->execute(['clave' => $clave]);
    
    if ($stmt->fetch()) {
        // Actualizar
        $sql = "UPDATE configuracion SET valor = :valor WHERE clave = :clave";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            'valor' => $valor,
            'clave' => $clave
        ]);
    } else {
        // Insertar
        $sql = "INSERT INTO configuracion (clave, valor, descripcion) VALUES (:clave, :valor, :descripcion)";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            'clave' => $clave,
            'valor' => $valor,
            'descripcion' => $descripcion
        ]);
    }
}

/**
 * Genera una contraseña aleatoria segura
 * 
 * @param int $length Longitud de la contraseña
 * @return string Contraseña generada
 */
function generateRandomPassword($length = 12) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()-_=+';
    $password = '';
    
    for ($i = 0; $i < $length; $i++) {
        $password .= $chars[random_int(0, strlen($chars) - 1)];
    }
    
    return $password;
}

/**
 * Envía un correo electrónico
 * 
 * @param string $to Destinatario
 * @param string $subject Asunto
 * @param string $message Mensaje (HTML)
 * @param array $headers Cabeceras adicionales
 * @return bool True si se envió correctamente, false en caso contrario
 */
function sendEmail($to, $subject, $message, $headers = []) {
    $default_headers = [
        'From' => getConfiguracion('EMAIL', SISTEMA_EMAIL),
        'Reply-To' => getConfiguracion('EMAIL', SISTEMA_EMAIL),
        'MIME-Version' => '1.0',
        'Content-Type' => 'text/html; charset=UTF-8'
    ];
    
    $headers = array_merge($default_headers, $headers);
    $header_str = '';
    
    foreach ($headers as $key => $value) {
        $header_str .= "$key: $value\r\n";
    }
    
    return mail($to, $subject, $message, $header_str);
}

/**
 * Genera una URL amigable (slug) a partir de un texto
 * 
 * @param string $text Texto a convertir
 * @return string Slug generado
 */
function generateSlug($text) {
    // Reemplazar caracteres especiales
    $text = mb_strtolower($text, 'UTF-8');
    $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
    $text = preg_replace('/[\s-]+/', '-', $text);
    $text = trim($text, '-');
    
    return $text;
}

