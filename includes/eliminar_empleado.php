<?php
require_once '../config/database.php';
session_start();

if (!isset($_SESSION['usuario_id']) || $_SESSION['perfil'] !== 'administrador') {
    die(json_encode(['success' => false, 'message' => 'Acceso no autorizado']));
}

if (isset($_POST['cedula'])) {
    $db = new Database();
    $conn = $db->getConnection();
    
    try {
        // Primero eliminar el usuario si existe
        $stmt = $conn->prepare("DELETE FROM Usuarios WHERE cedula = ?");
        $stmt->execute([$_POST['cedula']]);
        
        // Luego eliminar el empleado
        $stmt = $conn->prepare("DELETE FROM Empleados WHERE cedula = ?");
        $stmt->execute([$_POST['cedula']]);
        
        echo json_encode(['success' => true]);
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}