        <?php
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        require_once '../includes/functions.php';
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

        header('Content-Type: application/json; charset=utf-8');

        try {
            $db = new Database();
            $conn = $db->getConnection();

            // Obtener parámetros de filtro
            $semana = isset($_GET['semana']) ? intval($_GET['semana']) : date('W');
            $mes = isset($_GET['mes']) ? intval($_GET['mes']) : null;
            $empleado = isset($_GET['empleado']) ? $_GET['empleado'] : null;

            // Array para almacenar los parámetros de la consulta
            $params = [];
            $conditions = [];

            // Construir la consulta base
            $query = "SELECT 
                        n.id,
                        n.fecha,
                        n.cedula_empleado,
                        e.nombre as empleado,
                        n.referencia,
                        n.operacion,
                        n.costo_operacion,
                        n.cantidad,
                        n.total_tallas,
                        n.tallas,
                        n.subtotal
                    FROM nomina_semanal n
                    JOIN empleados e ON n.cedula_empleado = e.cedula
                    WHERE 1=1";

            // Aplicar filtros
            if ($semana) {
                $conditions[] = "n.semana = :semana";
                $params[':semana'] = $semana;
            }

            if ($mes) {
                $conditions[] = "MONTH(n.fecha) = :mes";
                $params[':mes'] = $mes;
            }

            // Si es empleado normal, mostrar solo sus registros
            if ($_SESSION['perfil'] !== 'administrador') {
                $conditions[] = "n.cedula_empleado = :cedula_empleado";
                $params[':cedula_empleado'] = $_SESSION['cedula'];
            } 
            // Si es admin y se especifica un empleado
            else if ($empleado) {
                $conditions[] = "n.cedula_empleado = :cedula_empleado";
                $params[':cedula_empleado'] = $empleado;
            }

            // Agregar condiciones a la consulta
            if (!empty($conditions)) {
                $query .= " AND " . implode(" AND ", $conditions);
            }

            // Ordenar por fecha descendente y luego por ID
            $query .= " ORDER BY n.fecha DESC, n.id DESC";

            $stmt = $conn->prepare($query);
            $stmt->execute($params);
            $registros = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Procesar y formatear los registros
            foreach ($registros as &$registro) {
                // Formatear fecha
                $registro['fecha'] = date('Y-m-d', strtotime($registro['fecha']));
                
                // Convertir tipos de datos
                $registro['cantidad'] = intval($registro['cantidad']);
                $registro['total_tallas'] = intval($registro['total_tallas']);
                $registro['costo_operacion'] = floatval($registro['costo_operacion']);
                $registro['subtotal'] = floatval($registro['subtotal']);
                
                // Asegurar que las cadenas de texto estén correctamente codificadas
                $registro['empleado'] = htmlspecialchars($registro['empleado']);
                $registro['referencia'] = htmlspecialchars($registro['referencia']);
                $registro['operacion'] = htmlspecialchars($registro['operacion']);
                $registro['tallas'] = htmlspecialchars($registro['tallas']);
            }

            // Calcular el total de los registros filtrados
            $total = array_reduce($registros, function($carry, $item) {
                return $carry + $item['subtotal'];
            }, 0);

            echo json_encode([
                'success' => true,
                'data' => $registros,
                'total' => $total,
                'filtros_aplicados' => [
                    'semana' => $semana,
                    'mes' => $mes,
                    'empleado' => $empleado
                ]
            ]);

        } catch (PDOException $e) {
            error_log("Error en la base de datos: " . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Error en la base de datos: ' . $e->getMessage()
            ]);
        } catch (Exception $e) {
            error_log("Error general: " . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Error general: ' . $e->getMessage()
            ]);
        }
        ?>