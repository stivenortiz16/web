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
        // Validar datos requeridos
        $campos_requeridos = ['nit', 'razon_social', 'telefono'];
        foreach ($campos_requeridos as $campo) {
            if (empty($_POST[$campo])) {
                throw new Exception("El campo $campo es requerido");
            }
        }

        // Validar formato del NIT
        if (!preg_match('/^[0-9\-]+$/', $_POST['nit'])) {
            throw new Exception("El NIT solo debe contener números y guiones");
        }

        // Validar formato del teléfono
        if (!preg_match('/^[0-9\+\-\s]+$/', $_POST['telefono'])) {
            throw new Exception("El teléfono solo debe contener números, +, - y espacios");
        }

        // Validar correo si está presente
        if (!empty($_POST['correo']) && !filter_var($_POST['correo'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception("El formato del correo electrónico no es válido");
        }

        // Iniciar transacción
        $conn->beginTransaction();

        // Verificar NIT duplicado
        $stmt = $conn->prepare("
            SELECT id FROM Proveedores 
            WHERE nit = ? 
            AND id != ?
        ");
        $stmt->execute([
            $_POST['nit'],
            $_POST['id'] ?? 0
        ]);
        
        if ($stmt->fetch()) {
            throw new Exception("Ya existe un proveedor con el NIT: " . $_POST['nit']);
        }

        if (empty($_POST['id'])) {
            // Crear nuevo proveedor
            $stmt = $conn->prepare("
                INSERT INTO Proveedores 
                (nit, razon_social, direccion, telefono, correo, contacto_nombre, 
                contacto_telefono, ciudad, estado) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $params = [
                $_POST['nit'],
                $_POST['razon_social'],
                $_POST['direccion'] ?? null,
                $_POST['telefono'],
                $_POST['correo'] ?? null,
                $_POST['contacto_nombre'] ?? null,
                $_POST['contacto_telefono'] ?? null,
                $_POST['ciudad'] ?? null,
                isset($_POST['estado']) ? $_POST['estado'] : 1
            ];
            $_SESSION['mensaje'] = "Proveedor creado correctamente";
        } else {
            // Actualizar proveedor existente
            $stmt = $conn->prepare("
                UPDATE Proveedores 
                SET nit = ?, 
                    razon_social = ?, 
                    direccion = ?,
                    telefono = ?, 
                    correo = ?, 
                    contacto_nombre = ?,
                    contacto_telefono = ?,
                    ciudad = ?,
                    estado = ?
                WHERE id = ?
            ");
            $params = [
                $_POST['nit'],
                $_POST['razon_social'],
                $_POST['direccion'] ?? null,
                $_POST['telefono'],
                $_POST['correo'] ?? null,
                $_POST['contacto_nombre'] ?? null,
                $_POST['contacto_telefono'] ?? null,
                $_POST['ciudad'] ?? null,
                isset($_POST['estado']) ? $_POST['estado'] : 1,
                $_POST['id']
            ];
            $_SESSION['mensaje'] = "Proveedor actualizado correctamente";
        }
        
        $stmt->execute($params);
        $conn->commit();
        $_SESSION['tipo_mensaje'] = 'success';
        
    } catch(PDOException $e) {
        $conn->rollBack();
        $_SESSION['tipo_mensaje'] = 'danger';
        
        if ($e->getCode() == '23000') {
            $_SESSION['mensaje'] = "Ya existe un proveedor con este NIT";
        } else {
            $_SESSION['mensaje'] = "Error en la base de datos: " . $e->getMessage();
        }
    } catch(Exception $e) {
        $conn->rollBack();
        $_SESSION['tipo_mensaje'] = 'danger';
        $_SESSION['mensaje'] = "Error: " . $e->getMessage();
    }
    
    header('Location: ../admin/proveedores.php');
    exit();
}

header('Location: ../admin/proveedores.php');
exit();
?>