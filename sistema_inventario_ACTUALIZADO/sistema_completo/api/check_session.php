<?php
/**
 * API para verificar sesion del usuario
 */
require_once '../config/config.php';

iniciarSesionSegura();

header('Content-Type: application/json');

if (isset($_SESSION['usuario_id'])) {
    echo json_encode([
        'success' => true,
        'user' => [
            'id' => $_SESSION['usuario_id'],
            'nombre' => $_SESSION['usuario_nombre'] ?? 'Usuario',
            'email' => $_SESSION['usuario_email'] ?? ''
        ]
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Sesion no valida'
    ]);
}
?>

