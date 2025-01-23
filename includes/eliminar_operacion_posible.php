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

        // Verificar si la operación está siendo utilizada (corregido a 'id' en lugar de 'operacion_id')
        $stmt = $conn->prepare("
            SELECT COUNT(*) FROM operaciones 
            WHERE id = ?
        ");
        $stmt->execute([$id]);
        if ($stmt->fetchColumn() > 0) {
            throw new Exception("No se puede eliminar la operación porque está siendo utilizada");
        }
        
        $stmt = $conn->prepare("DELETE FROM operacionesposibles WHERE id = ?");
        
        if ($stmt->execute([$id])) {
            $_SESSION['tipo_mensaje'] = 'success';
            $_SESSION['mensaje'] = "Operación eliminada correctamente";
        } else {
            throw new Exception("No se pudo eliminar la operación");
        }
        
    } catch (PDOException $e) {
        $_SESSION['tipo_mensaje'] = 'danger';
        $_SESSION['mensaje'] = "Error al eliminar la operación: " . $e->getMessage();
    } catch (Exception $e) {
        $_SESSION['tipo_mensaje'] = 'danger';
        $_SESSION['mensaje'] = $e->getMessage();
    }
}

header('Location: ../admin/operaciones_posibles.php');
exit;
?>