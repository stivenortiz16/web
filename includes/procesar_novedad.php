<?php
// procesar_novedad.php
session_start();
require_once 'functions.php';
require_once '../config/database.php';

// Verificar si es administrador
if ($_SESSION['perfil'] !== 'administrador') {
    echo json_encode(['success' => false, 'message' => 'Acceso no autorizado']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = new Database();
    $conn = $db->getConnection();
    
    try {
        // Obtener datos del POST
        $empleado = $_POST['empleado'];
        $referencia = $_POST['referencia'];
        $nota = $_POST['nota'];
        $semana = $_POST['semana'];
        
        // Insertar la novedad en la tabla nomina_semanal
        $stmt = $conn->prepare("
            INSERT INTO nomina_semanal (
                cedula_empleado,
                referencia,
                fecha,
                semana,
                es_novedad,
                nota_novedad,
                operacion,
                costo_operacion,
                cantidad,
                total_tallas,
                tallas,
                subtotal
            ) VALUES (
                :empleado,
                :referencia,
                NOW(),
                :semana,
                1,
                :nota,
                'Novedad',  -- Operación por defecto
                0,          -- Costo de operación 0
                0,          -- Cantidad 0
                0,          -- Total de tallas 0
                'N/A',      -- Tallas N/A
                0           -- Subtotal 0
            )
        ");
        
        $stmt->execute([
            ':empleado' => $empleado,
            ':referencia' => $referencia,
            ':semana' => $semana,
            ':nota' => $nota
        ]);
        
        echo json_encode(['success' => true, 'message' => 'Novedad registrada exitosamente']);
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
}