<?php
require_once '../config/database.php';
session_start();

if (!isset($_SESSION['usuario_id']) || $_SESSION['perfil'] !== 'administrador') {
    die('Acceso no autorizado');
}

if (isset($_POST['id'])) {
    $db = new Database();
    $conn = $db->getConnection();
    
    try {
        $stmt = $conn->prepare("SELECT * FROM Proveedores WHERE id = ?");
        $stmt->execute([$_POST['id']]);
        $proveedor = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($proveedor) {
            echo json_encode($proveedor);
        } else {
            echo json_encode(['error' => 'Proveedor no encontrado']);
        }
    } catch(PDOException $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
}
?>