<?php
require_once 'functions.php';
verificarPermiso('administrador');
require_once '../config/database.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db = new Database();
        $conn = $db->getConnection();

        // Validar campos requeridos
        if (empty($_POST['nombre_operacion']) || !isset($_POST['costo'])) {
            throw new Exception('Todos los campos son requeridos');
        }

        // Obtener y validar datos
        $id = isset($_POST['id']) ? trim($_POST['id']) : null;
        $nombre_operacion = trim($_POST['nombre_operacion']);
        $costo = floatval($_POST['costo']);

        // Validar que el costo sea positivo
        if ($costo < 0) {
            throw new Exception('El costo debe ser un valor positivo');
        }

        // Verificar nombre duplicado
        $stmt = $conn->prepare("
            SELECT id FROM operacionesposibles 
            WHERE nombre_operacion = ? 
            AND id != ?
        ");
        $stmt->execute([$nombre_operacion, $id ?? 0]);
        
        if ($stmt->fetch()) {
            throw new Exception("Ya existe una operación con este nombre: " . $nombre_operacion);
        }

        if ($id) {
            // Actualizar operación existente
            $stmt = $conn->prepare("
                UPDATE operacionesposibles 
                SET nombre_operacion = ?,
                    costo = ?
                WHERE id = ?
            ");
            $params = [$nombre_operacion, $costo, $id];
            $mensaje = "Operación actualizada correctamente";
        } else {
            // Crear nueva operación
            $stmt = $conn->prepare("
                INSERT INTO operacionesposibles 
                (nombre_operacion, costo) 
                VALUES (?, ?)
            ");
            $params = [$nombre_operacion, $costo];
            $mensaje = "Operación creada correctamente";
        }

        if ($stmt->execute($params)) {
            $_SESSION['tipo_mensaje'] = 'success';
            $_SESSION['mensaje'] = $mensaje;
        } else {
            throw new Exception('Error al procesar la operación');
        }

    } catch (PDOException $e) {
        $_SESSION['tipo_mensaje'] = 'danger';
        if ($e->getCode() == '23000') {
            $_SESSION['mensaje'] = "Ya existe una operación con este nombre";
        } else {
            $_SESSION['mensaje'] = "Error en la base de datos: " . $e->getMessage();
        }
    } catch (Exception $e) {
        $_SESSION['tipo_mensaje'] = 'danger';
        $_SESSION['mensaje'] = "Error: " . $e->getMessage();
    }
}

header('Location: ../admin/operaciones_posibles.php');
exit;
?>