<?php
require_once '../config/database.php';
session_start();

if (!isset($_SESSION['usuario_id']) || $_SESSION['perfil'] !== 'administrador') {
    die('Acceso no autorizado');
}

$db = new Database();
$conn = $db->getConnection();

if (isset($_POST['cedula'])) {
    // Obtener detalles de un empleado específico
    try {
        $stmt = $conn->prepare("SELECT * FROM Empleados WHERE cedula = ?");
        $stmt->execute([$_POST['cedula']]);
        $empleado = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($empleado) {
            echo json_encode($empleado);
        } else {
            echo json_encode(['error' => 'Empleado no encontrado']);
        }
    } catch(PDOException $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
} else {
    // Obtener la lista de todos los empleados
    try {
        $stmt = $conn->query("SELECT cedula, nombre FROM Empleados");
        $empleados = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($empleados);
    } catch(PDOException $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
}
?>