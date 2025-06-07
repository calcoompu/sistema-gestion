<?php
/**
 * Funciones específicas para el módulo de inventario
 * 
 * Este archivo contiene funciones relacionadas con la gestión de productos,
 * categorías, ubicaciones y movimientos de stock.
 */

/**
 * Obtiene la lista de productos con paginación y filtros
 * 
 * @param PDO $pdo Conexión a la base de datos
 * @param array $filtros Filtros a aplicar (buscar, categoria, lugar, activo)
 * @param int $pagina Número de página actual
 * @param int $por_pagina Número de elementos por página
 * @return array Array con productos y datos de paginación
 */
function obtenerProductos($pdo, $filtros = [], $pagina = 1, $por_pagina = 10) {
    // Valores por defecto para filtros
    $buscar = $filtros['buscar'] ?? '';
    $categoria = $filtros['categoria'] ?? '';
    $lugar = $filtros['lugar'] ?? '';
    $activo = isset($filtros['activo']) ? $filtros['activo'] : 1;
    
    // Construir consulta base
    $sql_base = "FROM productos p 
                LEFT JOIN categorias c ON p.categoria_id = c.id 
                LEFT JOIN lugares l ON p.lugar_id = l.id 
                WHERE 1=1";
    
    $params = [];
    
    // Aplicar filtros
    if (!empty($buscar)) {
        $sql_base .= " AND (p.nombre LIKE :buscar OR p.codigo LIKE :buscar OR p.descripcion LIKE :buscar)";
        $params['buscar'] = "%$buscar%";
    }
    
    if (!empty($categoria)) {
        $sql_base .= " AND p.categoria_id = :categoria";
        $params['categoria'] = $categoria;
    }
    
    if (!empty($lugar)) {
        $sql_base .= " AND p.lugar_id = :lugar";
        $params['lugar'] = $lugar;
    }
    
    if ($activo !== null) {
        $sql_base .= " AND p.activo = :activo";
        $params['activo'] = $activo;
    }
    
    // Contar total de registros
    $sql_count = "SELECT COUNT(*) AS total $sql_base";
    $stmt_count = $pdo->prepare($sql_count);
    $stmt_count->execute($params);
    $total_registros = $stmt_count->fetch()['total'];
    
    // Calcular paginación
    $total_paginas = ceil($total_registros / $por_pagina);
    $pagina = max(1, min($pagina, $total_paginas));
    $offset = ($pagina - 1) * $por_pagina;
    
    // Obtener productos para la página actual
    $sql = "SELECT p.*, c.nombre AS categoria_nombre, l.nombre AS lugar_nombre 
            $sql_base 
            ORDER BY p.id DESC 
            LIMIT :offset, :limit";
    
    $stmt = $pdo->prepare($sql);
    
    // Bind de parámetros
    foreach ($params as $key => $value) {
        $stmt->bindValue(":$key", $value);
    }
    
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $por_pagina, PDO::PARAM_INT);
    
    $stmt->execute();
    $productos = $stmt->fetchAll();
    
    // Datos de paginación
    $paginacion = [
        'total_registros' => $total_registros,
        'total_paginas' => $total_paginas,
        'pagina_actual' => $pagina,
        'por_pagina' => $por_pagina
    ];
    
    return [
        'productos' => $productos,
        'paginacion' => $paginacion
    ];
}

/**
 * Obtiene un producto por su ID
 * 
 * @param PDO $pdo Conexión a la base de datos
 * @param int $id ID del producto
 * @return array|false Datos del producto o false si no existe
 */
function obtenerProductoPorId($pdo, $id) {
    $sql = "SELECT p.*, c.nombre AS categoria_nombre, l.nombre AS lugar_nombre 
            FROM productos p 
            LEFT JOIN categorias c ON p.categoria_id = c.id 
            LEFT JOIN lugares l ON p.lugar_id = l.id 
            WHERE p.id = :id";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['id' => $id]);
    
    return $stmt->fetch();
}

/**
 * Obtiene un producto por su código
 * 
 * @param PDO $pdo Conexión a la base de datos
 * @param string $codigo Código del producto
 * @return array|false Datos del producto o false si no existe
 */
function obtenerProductoPorCodigo($pdo, $codigo) {
    $sql = "SELECT * FROM productos WHERE codigo = :codigo";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['codigo' => $codigo]);
    
    return $stmt->fetch();
}

/**
 * Crea un nuevo producto
 * 
 * @param PDO $pdo Conexión a la base de datos
 * @param array $producto Datos del producto
 * @return int|false ID del producto creado o false si falla
 */
function crearProducto($pdo, $producto) {
    $sql = "INSERT INTO productos (
                codigo, nombre, descripcion, categoria_id, lugar_id, 
                precio_compra, precio_venta, stock, stock_minimo, imagen
            ) VALUES (
                :codigo, :nombre, :descripcion, :categoria_id, :lugar_id, 
                :precio_compra, :precio_venta, :stock, :stock_minimo, :imagen
            )";
    
    $stmt = $pdo->prepare($sql);
    $resultado = $stmt->execute([
        'codigo' => $producto['codigo'],
        'nombre' => $producto['nombre'],
        'descripcion' => $producto['descripcion'],
        'categoria_id' => $producto['categoria_id'],
        'lugar_id' => $producto['lugar_id'],
        'precio_compra' => $producto['precio_compra'],
        'precio_venta' => $producto['precio_venta'],
        'stock' => $producto['stock'],
        'stock_minimo' => $producto['stock_minimo'],
        'imagen' => $producto['imagen'] ?? null
    ]);
    
    if ($resultado) {
        $producto_id = $pdo->lastInsertId();
        
        // Si se especificó stock inicial, registrar movimiento
        if ($producto['stock'] > 0) {
            $descripcion = "Stock inicial al crear el producto";
            $usuario_id = $_SESSION['usuario_id'] ?? null;
            
            // Usar procedimiento almacenado para actualizar stock
            $stmt = $pdo->prepare("CALL actualizar_stock(:producto_id, :tipo, :cantidad, :descripcion, :usuario_id)");
            $stmt->execute([
                'producto_id' => $producto_id,
                'tipo' => 'entrada',
                'cantidad' => $producto['stock'],
                'descripcion' => $descripcion,
                'usuario_id' => $usuario_id
            ]);
        }
        
        return $producto_id;
    }
    
    return false;
}

/**
 * Actualiza un producto existente
 * 
 * @param PDO $pdo Conexión a la base de datos
 * @param int $id ID del producto
 * @param array $producto Datos del producto
 * @return bool True si se actualizó correctamente, false en caso contrario
 */
function actualizarProducto($pdo, $id, $producto) {
    $sql = "UPDATE productos SET 
                codigo = :codigo, 
                nombre = :nombre, 
                descripcion = :descripcion, 
                categoria_id = :categoria_id, 
                lugar_id = :lugar_id, 
                precio_compra = :precio_compra, 
                precio_venta = :precio_venta, 
                stock_minimo = :stock_minimo";
    
    $params = [
        'id' => $id,
        'codigo' => $producto['codigo'],
        'nombre' => $producto['nombre'],
        'descripcion' => $producto['descripcion'],
        'categoria_id' => $producto['categoria_id'],
        'lugar_id' => $producto['lugar_id'],
        'precio_compra' => $producto['precio_compra'],
        'precio_venta' => $producto['precio_venta'],
        'stock_minimo' => $producto['stock_minimo']
    ];
    
    // Si se actualizó la imagen
    if (isset($producto['imagen']) && !empty($producto['imagen'])) {
        $sql .= ", imagen = :imagen";
        $params['imagen'] = $producto['imagen'];
    }
    
    $sql .= " WHERE id = :id";
    
    $stmt = $pdo->prepare($sql);
    return $stmt->execute($params);
}

/**
 * Elimina un producto
 * 
 * @param PDO $pdo Conexión a la base de datos
 * @param int $id ID del producto
 * @return bool True si se eliminó correctamente, false en caso contrario
 */
function eliminarProducto($pdo, $id) {
    // En lugar de eliminar físicamente, marcar como inactivo
    $sql = "UPDATE productos SET activo = 0 WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    return $stmt->execute(['id' => $id]);
}

/**
 * Obtiene las categorías
 * 
 * @param PDO $pdo Conexión a la base de datos
 * @param bool $solo_activas Solo devolver categorías activas
 * @return array Lista de categorías
 */
function obtenerCategorias($pdo, $solo_activas = true) {
    $sql = "SELECT * FROM categorias";
    
    if ($solo_activas) {
        $sql .= " WHERE activo = 1";
    }
    
    $sql .= " ORDER BY nombre";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    
    return $stmt->fetchAll();
}

/**
 * Obtiene una categoría por su ID
 * 
 * @param PDO $pdo Conexión a la base de datos
 * @param int $id ID de la categoría
 * @return array|false Datos de la categoría o false si no existe
 */
function obtenerCategoriaPorId($pdo, $id) {
    $sql = "SELECT * FROM categorias WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['id' => $id]);
    
    return $stmt->fetch();
}

/**
 * Crea una nueva categoría
 * 
 * @param PDO $pdo Conexión a la base de datos
 * @param array $categoria Datos de la categoría
 * @return int|false ID de la categoría creada o false si falla
 */
function crearCategoria($pdo, $categoria) {
    $sql = "INSERT INTO categorias (nombre, descripcion) VALUES (:nombre, :descripcion)";
    $stmt = $pdo->prepare($sql);
    $resultado = $stmt->execute([
        'nombre' => $categoria['nombre'],
        'descripcion' => $categoria['descripcion']
    ]);
    
    return $resultado ? $pdo->lastInsertId() : false;
}

/**
 * Actualiza una categoría existente
 * 
 * @param PDO $pdo Conexión a la base de datos
 * @param int $id ID de la categoría
 * @param array $categoria Datos de la categoría
 * @return bool True si se actualizó correctamente, false en caso contrario
 */
function actualizarCategoria($pdo, $id, $categoria) {
    $sql = "UPDATE categorias SET nombre = :nombre, descripcion = :descripcion WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([
        'id' => $id,
        'nombre' => $categoria['nombre'],
        'descripcion' => $categoria['descripcion']
    ]);
}

/**
 * Elimina una categoría
 * 
 * @param PDO $pdo Conexión a la base de datos
 * @param int $id ID de la categoría
 * @return bool True si se eliminó correctamente, false en caso contrario
 */
function eliminarCategoria($pdo, $id) {
    // En lugar de eliminar físicamente, marcar como inactiva
    $sql = "UPDATE categorias SET activo = 0 WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    return $stmt->execute(['id' => $id]);
}

/**
 * Obtiene los lugares (ubicaciones)
 * 
 * @param PDO $pdo Conexión a la base de datos
 * @param bool $solo_activos Solo devolver lugares activos
 * @return array Lista de lugares
 */
function obtenerLugares($pdo, $solo_activos = true) {
    $sql = "SELECT * FROM lugares";
    
    if ($solo_activos) {
        $sql .= " WHERE activo = 1";
    }
    
    $sql .= " ORDER BY nombre";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    
    return $stmt->fetchAll();
}

/**
 * Obtiene un lugar por su ID
 * 
 * @param PDO $pdo Conexión a la base de datos
 * @param int $id ID del lugar
 * @return array|false Datos del lugar o false si no existe
 */
function obtenerLugarPorId($pdo, $id) {
    $sql = "SELECT * FROM lugares WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['id' => $id]);
    
    return $stmt->fetch();
}

/**
 * Crea un nuevo lugar
 * 
 * @param PDO $pdo Conexión a la base de datos
 * @param array $lugar Datos del lugar
 * @return int|false ID del lugar creado o false si falla
 */
function crearLugar($pdo, $lugar) {
    $sql = "INSERT INTO lugares (nombre, descripcion) VALUES (:nombre, :descripcion)";
    $stmt = $pdo->prepare($sql);
    $resultado = $stmt->execute([
        'nombre' => $lugar['nombre'],
        'descripcion' => $lugar['descripcion']
    ]);
    
    return $resultado ? $pdo->lastInsertId() : false;
}

/**
 * Actualiza un lugar existente
 * 
 * @param PDO $pdo Conexión a la base de datos
 * @param int $id ID del lugar
 * @param array $lugar Datos del lugar
 * @return bool True si se actualizó correctamente, false en caso contrario
 */
function actualizarLugar($pdo, $id, $lugar) {
    $sql = "UPDATE lugares SET nombre = :nombre, descripcion = :descripcion WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([
        'id' => $id,
        'nombre' => $lugar['nombre'],
        'descripcion' => $lugar['descripcion']
    ]);
}

/**
 * Elimina un lugar
 * 
 * @param PDO $pdo Conexión a la base de datos
 * @param int $id ID del lugar
 * @return bool True si se eliminó correctamente, false en caso contrario
 */
function eliminarLugar($pdo, $id) {
    // En lugar de eliminar físicamente, marcar como inactivo
    $sql = "UPDATE lugares SET activo = 0 WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    return $stmt->execute(['id' => $id]);
}

/**
 * Obtiene los movimientos de stock de un producto
 * 
 * @param PDO $pdo Conexión a la base de datos
 * @param int $producto_id ID del producto
 * @param int $limite Número máximo de movimientos a devolver (0 = sin límite)
 * @return array Lista de movimientos
 */
function obtenerMovimientosStock($pdo, $producto_id, $limite = 0) {
    $sql = "SELECT m.*, u.nombre AS usuario_nombre 
            FROM movimientos_stock m 
            LEFT JOIN usuarios u ON m.usuario_id = u.id 
            WHERE m.producto_id = :producto_id 
            ORDER BY m.fecha_creacion DESC";
    
    if ($limite > 0) {
        $sql .= " LIMIT :limite";
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':producto_id', $producto_id, PDO::PARAM_INT);
    
    if ($limite > 0) {
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
    }
    
    $stmt->execute();
    
    return $stmt->fetchAll();
}

/**
 * Registra un movimiento de stock
 * 
 * @param PDO $pdo Conexión a la base de datos
 * @param int $producto_id ID del producto
 * @param string $tipo Tipo de movimiento (entrada, salida, ajuste)
 * @param int $cantidad Cantidad del movimiento
 * @param string $descripcion Descripción del movimiento
 * @param int $usuario_id ID del usuario que realiza el movimiento
 * @return bool True si se registró correctamente, false en caso contrario
 */
function registrarMovimientoStock($pdo, $producto_id, $tipo, $cantidad, $descripcion, $usuario_id = null) {
    // Usar procedimiento almacenado para actualizar stock
    $stmt = $pdo->prepare("CALL actualizar_stock(:producto_id, :tipo, :cantidad, :descripcion, :usuario_id)");
    return $stmt->execute([
        'producto_id' => $producto_id,
        'tipo' => $tipo,
        'cantidad' => $cantidad,
        'descripcion' => $descripcion,
        'usuario_id' => $usuario_id
    ]);
}

/**
 * Obtiene estadísticas del inventario
 * 
 * @param PDO $pdo Conexión a la base de datos
 * @return array Estadísticas del inventario
 */
function obtenerEstadisticasInventario($pdo) {
    // Total de productos
    $stmt = $pdo->query("SELECT COUNT(*) AS total FROM productos WHERE activo = 1");
    $total_productos = $stmt->fetch()['total'];
    
    // Valor total del inventario
    $stmt = $pdo->query("SELECT SUM(stock * precio_venta) AS valor FROM productos WHERE activo = 1");
    $valor_total = $stmt->fetch()['valor'] ?? 0;
    
    // Productos con stock bajo
    $stmt = $pdo->query("SELECT COUNT(*) AS total FROM productos WHERE activo = 1 AND stock <= stock_minimo AND stock > 0");
    $stock_bajo = $stmt->fetch()['total'];
    
    // Productos sin stock
    $stmt = $pdo->query("SELECT COUNT(*) AS total FROM productos WHERE activo = 1 AND stock = 0");
    $sin_stock = $stmt->fetch()['total'];
    
    // Productos más vendidos (últimos 30 días)
    $sql = "SELECT p.id, p.codigo, p.nombre, SUM(m.cantidad) AS total_salidas 
            FROM productos p 
            JOIN movimientos_stock m ON p.id = m.producto_id 
            WHERE m.tipo = 'salida' AND m.fecha_creacion >= DATE_SUB(NOW(), INTERVAL 30 DAY) 
            GROUP BY p.id 
            ORDER BY total_salidas DESC 
            LIMIT 5";
    
    $stmt = $pdo->query($sql);
    $mas_vendidos = $stmt->fetchAll();
    
    return [
        'total_productos' => $total_productos,
        'valor_total' => $valor_total,
        'stock_bajo' => $stock_bajo,
        'sin_stock' => $sin_stock,
        'mas_vendidos' => $mas_vendidos
    ];
}

