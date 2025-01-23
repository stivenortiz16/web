<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

function verificarSesion() {
    if (!isset($_SESSION['usuario_id'])) {
        if (isAjaxRequest()) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Sesión no iniciada'
            ]);
        } else {
            header('Location: /nomina/index.php');
        }
        exit();
    }
}

function isAjaxRequest() {
    return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

function verificarPermiso($perfiles_permitidos) {
    verificarSesion();
    
    // Convertir un solo perfil en array
    if (!is_array($perfiles_permitidos)) {
        $perfiles_permitidos = [$perfiles_permitidos];
    }
    
    if (!in_array($_SESSION['perfil'], $perfiles_permitidos)) {
        if (isAjaxRequest()) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'No tiene permiso para realizar esta acción'
            ]);
        } else {
            header('Location: /nomina/acceso-denegado.php');
        }
        exit();
    }
}

function limpiarDato($dato) {
    $dato = trim($dato);
    $dato = stripslashes($dato);
    $dato = htmlspecialchars($dato);
    return $dato;
}
?>