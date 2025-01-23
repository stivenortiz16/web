<?php
require_once '../config/database.php';
session_start();

if (!isset($_SESSION['usuario_id']) || $_SESSION['perfil'] !== 'administrador') {
    die(json_encode(['success' => false, 'message' => 'Acceso no autorizado']));
}

if (isset($_POST['id'])) {
    $db = new Database();
    $conn = $db->getConnection();
    
    try {
        // Verificar si hay facturas asociadas
        $stmt = $conn->prepare("SELECT COUNT(*) FROM Facturas WHERE proveedor_id = ?");
        $stmt->execute([$_POST['id']]);
        $count = $stmt->fetchColumn();
        
        if ($count > 0) {
            echo json_encode([
                'success' => false, 
                'message' => 'No se puede eliminar el proveedor porque tiene facturas asociadas'
            ]);
            exit();
        }
        
        // Eliminar proveedor
        $stmt = $conn->prepare("DELETE FROM Proveedores WHERE id = ?");
        $stmt->execute([$_POST['id']]);
        
        echo json_encode(['success' => true]);
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
?>