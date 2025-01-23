<?php
require_once '../includes/functions.php';
verificarPermiso('administrador');
require_once '../config/database.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $db = new Database();
        $conn = $db->getConnection();

        // Validar datos requeridos
        $campos_requeridos = ['referencia', 'descripcion', 'proveedor_id', 'cantidad', 'valor_prenda', 'valor_por_prenda'];
        foreach ($campos_requeridos as $campo) {
            if (empty($_POST[$campo])) {
                throw new Exception("El campo " . ucfirst(str_replace('_', ' ', $campo)) . " es requerido");
            }
        }

        // Obtener y validar datos básicos
        $id = isset($_POST['id']) ? trim($_POST['id']) : null;
        $referencia = trim($_POST['referencia']);
        $descripcion = trim($_POST['descripcion']);
        $proveedor_id = trim($_POST['proveedor_id']);
        $cantidad = intval($_POST['cantidad']);
        $valor_prenda = floatval($_POST['valor_prenda']);
        $valor_por_prenda = floatval($_POST['valor_por_prenda']);
        $tallas = isset($_POST['tallas']) ? $_POST['tallas'] : [];

        // Iniciar transacción
        $conn->beginTransaction();

        // Verificar referencia duplicada
        $stmt = $conn->prepare("
            SELECT id FROM Referencias 
            WHERE referencia = ? 
            AND id != ?
        ");
        $stmt->execute([$referencia, $id ?? 0]);
        
        if ($stmt->fetch()) {
            throw new Exception("Ya existe una referencia con el código: " . $referencia);
        }

        // Validar que el proveedor exista
        $stmt = $conn->prepare("SELECT id FROM Proveedores WHERE id = ?");
        $stmt->execute([$proveedor_id]);
        if (!$stmt->fetch()) {
            throw new Exception('El proveedor seleccionado no existe');
        }

        if ($cantidad <= 0) {
            throw new Exception('La cantidad debe ser mayor a 0');
        }

        if ($valor_prenda <= 0) {
            throw new Exception('El valor de la prenda debe ser mayor a 0');
        }

        if ($valor_por_prenda <= 0) {
            throw new Exception('El valor por prenda debe ser mayor a 0');
        }

        // Validar que la suma de las tallas sea igual a la cantidad total
        $suma_tallas = array_sum($tallas);
        if ($suma_tallas != $cantidad) {
            throw new Exception('La suma de las tallas debe ser igual a la cantidad total');
        }

        // Convertir el array de tallas a JSON
        $tallas_json = json_encode($tallas);

        if ($id) {
            // Actualizar referencia existente
            $stmt = $conn->prepare("
                UPDATE Referencias 
                SET referencia = ?,
                    descripcion = ?,
                    proveedor_id = ?,
                    cantidad = ?,
                    valor_prenda = ?,
                    valor_por_prenda = ?,
                    tallas = ?
                WHERE id = ?
            ");
            $params = [
                $referencia,
                $descripcion,
                $proveedor_id,
                $cantidad,
                $valor_prenda,
                $valor_por_prenda,
                $tallas_json,
                $id
            ];
            $mensaje = "Referencia actualizada correctamente";
        } else {
            // Insertar nueva referencia
            $stmt = $conn->prepare("
                INSERT INTO Referencias (
                    referencia,
                    descripcion,
                    proveedor_id,
                    cantidad,
                    valor_prenda,
                    valor_por_prenda,
                    tallas
                ) VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $params = [
                $referencia,
                $descripcion,
                $proveedor_id,
                $cantidad,
                $valor_prenda,
                $valor_por_prenda,
                $tallas_json
            ];
            $mensaje = "Referencia creada correctamente";
        }

        if ($stmt->execute($params)) {
            $conn->commit();
            $_SESSION['tipo_mensaje'] = 'success';
            $_SESSION['mensaje'] = $mensaje;
        } else {
            throw new Exception('Error al procesar la referencia');
        }

    } catch (PDOException $e) {
        $conn->rollBack();
        $_SESSION['tipo_mensaje'] = 'danger';
        
        if ($e->getCode() == '23000') {
            if (strpos($e->getMessage(), 'referencias.referencia') !== false) {
                $_SESSION['mensaje'] = "Ya existe una referencia con este código";
            } else {
                $_SESSION['mensaje'] = "Error: Registro duplicado";
            }
        } else {
            $_SESSION['mensaje'] = "Error en la base de datos: " . $e->getMessage();
        }
    } catch (Exception $e) {
        $conn->rollBack();
        $_SESSION['tipo_mensaje'] = 'danger';
        $_SESSION['mensaje'] = "Error: " . $e->getMessage();
    }

    header('Location: ../admin/referencias.php');
    exit;
}

header('Location: ../admin/referencias.php');
exit;
?>