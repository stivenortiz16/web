<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once '../includes/functions.php';
require_once '../config/database.php';

header('Content-Type: application/json');

try {
    verificarPermiso('administrador');
    
    $db = new Database();
    $conn = $db->getConnection();

    $semana = isset($_GET['semana']) ? $_GET['semana'] : date('W');

    $query = "
        SELECT 
            e.cedula,
            CONCAT(e.nombre, ' ', e.apellido) as nombre_empleado,
            COUNT(n.id) as total_registros,
            COALESCE(SUM(n.subtotal), 0) as total_semana
        FROM 
            empleados e
        LEFT JOIN 
            nomina_semanal n ON e.cedula = n.cedula_empleado 
            AND n.semana = :semana
        WHERE 
            e.estado = 1
        GROUP BY 
            e.cedula, e.nombre, e.apellido
        ORDER BY 
            total_semana DESC
    ";

    $stmt = $conn->prepare($query);
    $stmt->execute([':semana' => $semana]);
    $totales = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'totales' => $totales
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>