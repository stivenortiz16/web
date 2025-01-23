<?php
require_once 'functions.php';
verificarPermiso('administrador');
require_once '../config/database.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$db = new Database();
$conn = $db->getConnection();

try {
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id'])) {
        $id = $_POST['id'];

        // Verificar si la referencia existe
        $stmt = $conn->prepare("SELECT * FROM Referencias WHERE id = :id");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        if ($stmt->rowCount() == 0) {
            throw new Exception('La referencia no existe');
        }

        // Eliminar la referencia
        $stmt = $conn->prepare("DELETE FROM Referencias WHERE id = :id");
        $stmt->bindParam(':id', $id);
        $stmt->execute();

        $response = ['success' => true, 'message' => 'Referencia eliminada exitosamente'];
    } else {
        throw new Exception('Solicitud inválida');
    }

} catch (Exception $e) {
    $response = ['success' => false, 'message' => $e->getMessage()];
}

// Devolver respuesta en formato JSON
header('Content-Type: application/json');
echo json_encode($response);