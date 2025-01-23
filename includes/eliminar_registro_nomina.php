<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once '../includes/functions.php';
require_once '../config/database.php';

header('Content-Type: application/json');

// Verificar si el usuario está autenticado
if (!isset($_SESSION['cedula']) || !isset($_SESSION['perfil'])) {
    echo json_encode([
        'success' => false,
        'message' => 'No autorizado: Debe iniciar sesión'
    ]);
    exit;
}

try {
    // Verificar si se recibió el ID
    if (!isset($_POST['id']) || empty($_POST['id'])) {
        throw new Exception('No se proporcionó un ID válido');
    }

    $id = intval($_POST['id']);
    $db = new Database();
    $conn = $db->getConnection();

    // Primero verificar si el registro existe y obtener el propietario
    $stmtVerificar = $conn->prepare("SELECT cedula_empleado FROM nomina_semanal WHERE id = ?");
    $stmtVerificar->execute([$id]);
    $registro = $stmtVerificar->fetch(PDO::FETCH_ASSOC);

    if (!$registro) {
        throw new Exception('No se encontró el registro');
    }

    // Verificar permisos
    if ($_SESSION['perfil'] !== 'administrador' && $registro['cedula_empleado'] !== $_SESSION['cedula']) {
        throw new Exception('No tiene permiso para eliminar este registro');
    }

    // Si llegamos aquí, el usuario tiene permiso para eliminar
    $stmt = $conn->prepare("DELETE FROM nomina_semanal WHERE id = ?");
    $stmt->execute([$id]);

    if ($stmt->rowCount() > 0) {
        // Registrar la acción
        $fecha_actual = date('Y-m-d H:i:s');
        $stmtLog = $conn->prepare("
            INSERT INTO logs (accion, usuario_id, fecha) 
            VALUES (?, ?, ?)
        ");
        $stmtLog->execute([
            "Eliminación de registro de nómina ID: $id",
            $_SESSION['cedula'],
            $fecha_actual
        ]);

        echo json_encode([
            'success' => true,
            'message' => 'Registro eliminado correctamente'
        ]);
    } else {
        throw new Exception('No se pudo eliminar el registro');
    }

} catch (PDOException $e) {
    error_log("Error de base de datos: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error en la base de datos al eliminar el registro'
    ]);
} catch (Exception $e) {
    error_log("Error: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>