<?php
require_once 'functions.php';
verificarPermiso('administrador');
require_once '../config/database.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (isset($_GET['id'])) {
    try {
        $db = new Database();
        $conn = $db->getConnection();
        
        $id = $_GET['id'];

        // Verificar si la factura tiene pagos asociados
        $stmt = $conn->prepare("SELECT COUNT(*) FROM pagos WHERE factura_id = ?");
        $stmt->execute([$id]);
        if ($stmt->fetchColumn() > 0) {
            throw new Exception("No se puede eliminar la factura porque tiene pagos asociados");
        }

        // Verificar si la factura tiene detalles
        $stmt = $conn->prepare("SELECT COUNT(*) FROM detalles_factura WHERE factura_id = ?");
        $stmt->execute([$id]);
        if ($stmt->fetchColumn() > 0) {
            // Eliminar primero los detalles
            $stmtDetalles = $conn->prepare("DELETE FROM detalles_factura WHERE factura_id = ?");
            $stmtDetalles->execute([$id]);
        }

        // Eliminar registros del historial
        $stmtHistorial = $conn->prepare("DELETE FROM historial_facturas WHERE factura_id = ?");
        $stmtHistorial->execute([$id]);

        // Finalmente, eliminar la factura
        $stmt = $conn->prepare("DELETE FROM facturas WHERE id = ?");
        
        if ($stmt->execute([$id])) {
            $_SESSION['tipo_mensaje'] = 'success';
            $_SESSION['mensaje'] = "Factura y sus registros relacionados eliminados correctamente";
        } else {
            throw new Exception("No se pudo eliminar la factura");
        }
        
    } catch (PDOException $e) {
        $_SESSION['tipo_mensaje'] = 'danger';
        $_SESSION['mensaje'] = "Error al eliminar la factura: " . $e->getMessage();
    } catch (Exception $e) {
        $_SESSION['tipo_mensaje'] = 'danger';
        $_SESSION['mensaje'] = $e->getMessage();
    }
}

header('Location: ../admin/facturas.php');
exit;
?>