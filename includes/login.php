<?php
session_start();
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $database = new Database();
    $db = $database->getConnection();
    
    $cedula = $_POST['cedula'] ?? '';
    $password = $_POST['password'] ?? '';
    
    try {
        $query = "SELECT u.*, e.nombre 
                  FROM Usuarios u 
                  JOIN Empleados e ON u.cedula = e.cedula 
                  WHERE u.cedula = :cedula AND u.contraseña = :password";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(":cedula", $cedula);
        $stmt->bindParam(":password", $password);
        $stmt->execute();
        
        if ($stmt->rowCount() == 1) {
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $_SESSION['usuario_id'] = $usuario['id'];
            $_SESSION['cedula'] = $usuario['cedula'];
            $_SESSION['nombre'] = $usuario['nombre'];
            $_SESSION['perfil'] = $usuario['perfil'];
            $_SESSION['rol'] = $usuario['perfil']; // Agregar esta línea
            
            // Actualizar último acceso
            $update = "UPDATE Usuarios SET ultimo_acceso = NOW() WHERE id = :id";
            $stmt = $db->prepare($update);
            $stmt->bindParam(":id", $usuario['id']);
            $stmt->execute();
            
            // Redirigir según el perfil
            header("Location: ../" . ($usuario['perfil'] === 'administrador' ? 'admin' : 'empleado') . "/dashboard.php");
            exit();
        } else {
            header("Location: ../index.php?error=Credenciales inválidas");
            exit();
        }
    } catch(PDOException $e) {
        header("Location: ../index.php?error=Error del sistema");
        exit();
    }
} else {
    header("Location: ../index.php");
    exit();
}
?>