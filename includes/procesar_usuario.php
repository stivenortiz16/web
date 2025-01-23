<?php
require_once '../config/database.php';
session_start();

if (!isset($_SESSION['usuario_id']) || $_SESSION['perfil'] !== 'administrador') {
    die('Acceso no autorizado');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = new Database();
    $conn = $db->getConnection();
    
    try {
        $stmt = $conn->prepare("INSERT INTO Usuarios (cedula, contraseña, perfil) VALUES (?, ?, ?)");
        $stmt->execute([
            $_POST['cedula'],
            $_POST['password'], // En producción, usar password_hash()
            $_POST['perfil']
        ]);
        
        $_SESSION['mensaje'] = "Usuario creado correctamente";
    } catch(PDOException $e) {
        $_SESSION['mensaje'] = "Error al crear usuario: " . $e->getMessage();
    }
    
    header('Location: ../admin/empleados.php');
    exit();
}