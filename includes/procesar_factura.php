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
        $campos_requeridos = [
            'numero_factura', 'tipo', 'fecha_emision', 'fecha_vencimiento',
            'proveedor_id', 'empleado_cedula', 'subtotal', 'iva', 'total', 'estado'
        ];

        foreach ($campos_requeridos as $campo) {
            if (!isset($_POST[$campo]) || trim($_POST[$campo]) === '') {
                throw new Exception("El campo $campo es requerido");
            }
        }

        // Obtener y validar datos
        $id = isset($_POST['id']) ? trim($_POST['id']) : null;
        $numero_factura = trim($_POST['numero_factura']);
        $tipo = trim($_POST['tipo']);
        $fecha_emision = $_POST['fecha_emision'];
        $fecha_vencimiento = $_POST['fecha_vencimiento'];
        $proveedor_id = $_POST['proveedor_id'];
        $empleado_cedula = $_POST['empleado_cedula'];
        $subtotal = floatval($_POST['subtotal']);
        $iva = floatval($_POST['iva']);
        $total = floatval($_POST['total']);
        $estado = trim($_POST['estado']);
        $notas = isset($_POST['notas']) ? trim($_POST['notas']) : '';

        // Validar fechas
        if (strtotime($fecha_vencimiento) < strtotime($fecha_emision)) {
            throw new Exception('La fecha de vencimiento no puede ser anterior a la fecha de emisión');
        }

        // Validar montos
        if ($subtotal < 0 || $iva < 0 || $total < 0) {
            throw new Exception('Los montos no pueden ser negativos');
        }

        // Verificar número de factura duplicado
        $stmt = $conn->prepare("
            SELECT id FROM facturas 
            WHERE numero_factura = ? 
            AND id != ?
        ");
        $stmt->execute([$numero_factura, $id ?? 0]);
        
        if ($stmt->fetch()) {
            throw new Exception("Ya existe una factura con este número: " . $numero_factura);
        }

        // Preparar la consulta
        if ($id) {
            // Actualizar factura existente
            $stmt = $conn->prepare("
                UPDATE facturas 
                SET numero_factura = ?, tipo = ?, fecha_emision = ?, 
                    fecha_vencimiento = ?, proveedor_id = ?, empleado_cedula = ?,
                    subtotal = ?, iva = ?, total = ?, estado = ?, notas = ?,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = ?
            ");
            $params = [
                $numero_factura, $tipo, $fecha_emision, $fecha_vencimiento,
                $proveedor_id, $empleado_cedula, $subtotal, $iva, $total,
                $estado, $notas, $id
            ];
            $mensaje = "Factura actualizada correctamente";

            // Registrar cambio en historial
            $stmtHistorial = $conn->prepare("
                INSERT INTO historial_facturas 
                (factura_id, accion, estado_anterior, estado_nuevo, empleado_cedula, fecha_cambio, detalles)
                VALUES (?, 'actualización', ?, ?, ?, CURRENT_TIMESTAMP, ?)
            ");
            $stmtHistorial->execute([
                $id,
                $estado, // Estado anterior (asumimos que no cambió)
                $estado, // Estado nuevo
                $empleado_cedula,
                "Actualización de factura por usuario: $empleado_cedula"
            ]);

        } else {
            // Crear nueva factura
            $stmt = $conn->prepare("
                INSERT INTO facturas 
                (numero_factura, tipo, fecha_emision, fecha_vencimiento,
                proveedor_id, empleado_cedula, subtotal, iva, total,
                estado, notas, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 
                CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
            ");
            $params = [
                $numero_factura, $tipo, $fecha_emision, $fecha_vencimiento,
                $proveedor_id, $empleado_cedula, $subtotal, $iva, $total,
                $estado, $notas
            ];
            $mensaje = "Factura creada correctamente";
        }

        if ($stmt->execute($params)) {
            $_SESSION['tipo_mensaje'] = 'success';
            $_SESSION['mensaje'] = $mensaje;
        } else {
            throw new Exception('Error al procesar la factura');
        }

    } catch (PDOException $e) {
        $_SESSION['tipo_mensaje'] = 'danger';
        $_SESSION['mensaje'] = "Error en la base de datos: " . $e->getMessage();
    } catch (Exception $e) {
        $_SESSION['tipo_mensaje'] = 'danger';
        $_SESSION['mensaje'] = $e->getMessage();
    }
}

header('Location: ../admin/facturas.php');
exit;
?>