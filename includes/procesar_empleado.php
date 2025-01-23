<?php
require_once '../config/database.php';
session_start();

if (!isset($_SESSION['usuario_id']) || $_SESSION['perfil'] !== 'administrador') {
    die('Acceso no autorizado');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = new Database();
    $conn = $db->getConnection();
    
    $accion = $_POST['accion'] ?? 'crear';
    
    try {
        if ($accion === 'crear') {
            $stmt = $conn->prepare("INSERT INTO Empleados (cedula, nombre, correo, telefono, direccion, salario, fecha_ingreso,numero_cuenta_bancaria) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
           
        } else {
            $stmt = $conn->prepare("UPDATE Empleados SET nombre = ?, correo = ?, telefono = ?, direccion = ?, salario = ?, fecha_ingreso = ?, numero_cuenta_bancaria = ? WHERE cedula = ?");
            
        }
        
        $params = [
            $_POST['nombre'],
            $_POST['correo'],
            $_POST['telefono'],
            $_POST['direccion'],
            $_POST['salario'],
            $_POST['fecha_ingreso'],
            $_POST['numero_cuenta_bancaria']
        ];
        
        if ($accion === 'crear') {
            array_unshift($params, $_POST['cedula']);
        } else {
            $params[] = $_POST['cedula'];
        }
        
        $stmt->execute($params);
        $_SESSION['mensaje'] = ($accion === 'crear' ? 'Empleado creado' : 'Empleado actualizado') . ' correctamente';
        
    } catch(PDOException $e) {
        $_SESSION['mensaje'] = "Error: " . $e->getMessage();
    }
    
    header('Location: ../admin/empleados.php');
    exit();
}