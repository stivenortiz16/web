<?php
session_start();
require_once 'functions.php';
require_once '../config/database.php';

// Verificar autenticación
if (!isset($_SESSION['cedula']) || !isset($_SESSION['perfil'])) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'No autorizado: Sesión no válida'
    ]);
    exit;
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Obtener y validar datos
        $cedulaEmpleado = $_POST['empleado'] ?? '';
        $semana = $_POST['semana'] ?? date('W');
        $fecha = date('Y-m-d');
        $referencia = $_POST['referencia'] ?? '';
        $operacion = $_POST['operacion'] ?? '';
        $costo_operacion = floatval(str_replace(['$', ','], '', $_POST['costo_operacion'] ?? 0));
        $cantidad = intval($_POST['cantidad'] ?? 0);
        $totalTallas = intval($_POST['totalTallas'] ?? 0);
        $tallas = $_POST['tallas'] ?? '';
        $subtotal = floatval(str_replace(['$', ','], '', $_POST['subtotal'] ?? 0));

        // Debug - Imprimir valores recibidos
        error_log("Datos recibidos: " . print_r($_POST, true));

        // Validaciones específicas
        if (empty($cedulaEmpleado)) {
            throw new Exception('La cédula del empleado es requerida');
        }
        if (empty($referencia)) {
            throw new Exception('La referencia es requerida');
        }
        if (empty($operacion)) {
            throw new Exception('La operación es requerida');
        }
        if ($costo_operacion <= 0) {
            throw new Exception('El costo de operación debe ser mayor a 0');
        }
        if ($subtotal <= 0) {
            throw new Exception('El subtotal debe ser mayor a 0');
        }
        if ($cantidad <= 0 && $totalTallas <= 0) {
            throw new Exception('Debe especificar una cantidad o seleccionar tallas');
        }

        // Validar que el empleado solo pueda registrar su propia nómina
        if ($_SESSION['perfil'] !== 'administrador' && $_SESSION['cedula'] !== $cedulaEmpleado) {
            throw new Exception('No tiene permiso para registrar nómina de otro empleado');
        }

        $db = new Database();
        $conn = $db->getConnection();

        // Verificar si ya existe la misma operación para la misma referencia en la semana actual
        $stmtVerificar = $conn->prepare("
            SELECT COUNT(*) as count 
            FROM nomina_semanal 
            WHERE referencia = ? 
            AND operacion = ? 
            AND semana = ? 
            AND YEAR(fecha) = YEAR(CURRENT_DATE)
        ");
        
        $stmtVerificar->execute([$referencia, $operacion, $semana]);
        $resultado = $stmtVerificar->fetch(PDO::FETCH_ASSOC);

        if ($resultado['count'] > 0) {
            throw new Exception('Esta operación ya ha sido registrada para esta referencia en la semana actual.');
        }

        // Si no hay duplicados, proceder con la inserción
        $stmt = $conn->prepare("INSERT INTO nomina_semanal (
            cedula_empleado, 
            semana, 
            fecha, 
            referencia, 
            operacion, 
            costo_operacion, 
            cantidad, 
            total_tallas, 
            tallas, 
            subtotal
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        $stmt->execute([
            $cedulaEmpleado, 
            $semana, 
            $fecha, 
            $referencia, 
            $operacion, 
            $costo_operacion, 
            $cantidad, 
            $totalTallas, 
            $tallas, 
            $subtotal
        ]);

        echo json_encode([
            'success' => true,
            'message' => 'Registro agregado exitosamente'
        ]);

    } catch (Exception $e) {
        error_log("Error en procesar_nomina.php: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Método no permitido'
    ]);
}
?>