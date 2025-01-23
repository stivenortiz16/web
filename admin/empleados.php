<?php
require_once '../includes/functions.php';
verificarPermiso('administrador');
require_once '../config/database.php';

$db = new Database();
$conn = $db->getConnection();

// Mensajes de estado
$mensaje = '';
if (isset($_SESSION['mensaje'])) {
    $mensaje = $_SESSION['mensaje'];
    unset($_SESSION['mensaje']);
}

// Obtener lista de empleados
try {
    $stmt = $conn->prepare("
        SELECT 
            e.*,
            CASE WHEN u.id IS NOT NULL THEN 'Sí' ELSE 'No' END as tiene_usuario,
            u.perfil
        FROM Empleados e
        LEFT JOIN Usuarios u ON e.cedula = u.cedula
        ORDER BY e.fecha_ingreso DESC
    ");
    $stmt->execute();
    $empleados = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $mensaje = "Error al cargar empleados: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Empleados - Sistema de Nómina</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <!-- Incluir el navbar -->
    <?php include '../includes/navbar.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include '../includes/sidebar.php'; ?>

            <!-- Contenido principal -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Gestión de Empleados</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalEmpleado">
                            <i class="bi bi-plus-lg"></i> Nuevo Empleado
                        </button>
                    </div>
                </div>

                <?php if ($mensaje): ?>
                <div class="alert alert-info alert-dismissible fade show" role="alert">
                    <?php echo $mensaje; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>

                <!-- Tabla de empleados -->
                <div class="table-responsive">
                    <table class="table table-striped table-hover" id="tablaEmpleados">
                        <thead>
                            <tr>
                                <th>Cédula</th>
                                <th>Nombre</th>
                                <th>Correo</th>
                                <th>Teléfono</th>
                                <th>Número de Cuenta Bancaria</th>
                                <th>Fecha Ingreso</th>
                                <th>Tiene Usuario</th>
                                <th>Perfil</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($empleados as $empleado): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($empleado['cedula']); ?></td>
                                <td><?php echo htmlspecialchars($empleado['nombre']); ?></td>
                                <td><?php echo htmlspecialchars($empleado['correo']); ?></td>
                                <td><?php echo htmlspecialchars($empleado['telefono']); ?></td>
                                <td><?php echo htmlspecialchars($empleado['numero_cuenta_bancaria']); ?></td>
                                <td><?php echo htmlspecialchars($empleado['fecha_ingreso']); ?></td>
                                <td><?php echo $empleado['tiene_usuario']; ?></td>
                                <td><?php echo $empleado['perfil'] ?? 'N/A'; ?></td>
                                <td>
                                    <button class="btn btn-sm btn-primary" onclick="editarEmpleado('<?php echo $empleado['cedula']; ?>')">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <?php if ($empleado['tiene_usuario'] === 'No'): ?>
                                    <button class="btn btn-sm btn-success" onclick="crearUsuario('<?php echo $empleado['cedula']; ?>')">
                                        <i class="bi bi-person-plus"></i>
                                    </button>
                                    <?php endif; ?>
                                    <button class="btn btn-sm btn-danger" onclick="eliminarEmpleado('<?php echo $empleado['cedula']; ?>')">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </main>
        </div>
    </div>

    <!-- Modal para Nuevo/Editar Empleado -->
    <div class="modal fade" id="modalEmpleado" tabindex="-1" aria-labelledby="modalEmpleadoLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalEmpleadoLabel">Nuevo Empleado</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="formEmpleado" action="../includes/procesar_empleado.php" method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="cedula" class="form-label">Cédula</label>
                            <input type="text" class="form-control" id="cedula" name="cedula" required>
                        </div>
                        <div class="mb-3">
                            <label for="nombre" class="form-label">Nombre Completo</label>
                            <input type="text" class="form-control" id="nombre" name="nombre" required>
                        </div>
                        <div class="mb-3">
                            <label for="correo" class="form-label">Correo Electrónico</label>
                            <input type="email" class="form-control" id="correo" name="correo" required>
                        </div>
                        <div class="mb-3">
    <label for="numero_cuenta_bancaria" class="form-label">Número de Cuenta Bancaria</label>
    <input type="text" class="form-control" id="numero_cuenta_bancaria" name="numero_cuenta_bancaria" required>
</div>
                        <div class="mb-3">
                            <label for="telefono" class="form-label">Teléfono</label>
                            <input type="text" class="form-control" id="telefono" name="telefono" required>
                        </div>
                        <div class="mb-3">
                            <label for="direccion" class="form-label">Dirección</label>
                            <textarea class="form-control" id="direccion" name="direccion" rows="2"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="salario" class="form-label">Salario</label>
                            <input type="number" class="form-control" id="salario" name="salario" step="0.01" required>
                        </div>
                        <div class="mb-3">
                            <label for="fecha_ingreso" class="form-label">Fecha de Ingreso</label>
                            <input type="date" class="form-control" id="fecha_ingreso" name="fecha_ingreso" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Guardar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal para Crear Usuario -->
    <div class="modal fade" id="modalUsuario" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Crear Usuario</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="formUsuario" action="../includes/procesar_usuario.php" method="POST">
                    <div class="modal-body">
                        <input type="hidden" id="usuario_cedula" name="cedula">
                        <div class="mb-3">
                            <label for="password" class="form-label">Contraseña</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <div class="mb-3">
                            <label for="perfil" class="form-label">Perfil</label>
                            <select class="form-select" id="perfil" name="perfil" required>
                                <option value="empleado">Empleado</option>
                                <option value="administrador">Administrador</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Crear Usuario</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    
    <script>
        $(document).ready(function() {
            $('#tablaEmpleados').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.11.5/i18n/es-ES.json'
                },
                order: [[4, 'desc']], // Ordenar por fecha de ingreso descendente
                responsive: true
            });
        });

        // Función para editar empleado
        function editarEmpleado(cedula) {
            // Obtener datos del empleado mediante AJAX
            $.ajax({
                url: '../includes/obtener_empleado.php',
                type: 'POST',
                data: { cedula: cedula },
                success: function(response) {
                    const empleado = JSON.parse(response);
                    
                    // Llenar el formulario con los datos
                    $('#cedula').val(empleado.cedula);
                    $('#nombre').val(empleado.nombre);
                    $('#correo').val(empleado.correo);
                    $('#telefono').val(empleado.telefono);
                    $('#direccion').val(empleado.direccion);
                    $('#salario').val(empleado.salario);
                    $('#fecha_ingreso').val(empleado.fecha_ingreso);
                    
                    // Cambiar el título del modal
                    $('#modalEmpleadoLabel').text('Editar Empleado');
                    
                    // Agregar campo oculto para indicar que es una edición
                    if (!$('#formEmpleado input[name="accion"]').length) {
                        $('#formEmpleado').append('<input type="hidden" name="accion" value="editar">');
                    }
                    
                    // Mostrar el modal
                    $('#modalEmpleado').modal('show');
                },
                error: function() {
                    alert('Error al cargar los datos del empleado');
                }
            });
        }

        // Función para crear usuario
        function crearUsuario(cedula) {
            $('#usuario_cedula').val(cedula);
            $('#modalUsuario').modal('show');
        }

        // Función para eliminar empleado
        function eliminarEmpleado(cedula) {
            if (confirm('¿Está seguro de que desea eliminar este empleado?')) {
                $.ajax({
                    url: '../includes/eliminar_empleado.php',
                    type: 'POST',
                    data: { cedula: cedula },
                    success: function(response) {
                        const result = JSON.parse(response);
                        if (result.success) {
                            location.reload();
                        } else {
                            alert(result.message);
                        }
                    },
                    error: function() {
                        alert('Error al eliminar el empleado');
                    }
                });
            }
        }

        // Limpiar formulario cuando se cierra el modal
        $('#modalEmpleado').on('hidden.bs.modal', function () {
            $('#formEmpleado')[0].reset();
            $('#modalEmpleadoLabel').text('Nuevo Empleado');
            $('#formEmpleado input[name="accion"]').remove();
        });

        // Validación del formulario
        $('#formEmpleado').on('submit', function(e) {
            const cedula = $('#cedula').val();
            const telefono = $('#telefono').val();
            
            // Validar cédula (solo números)
            if (!/^\d+$/.test(cedula)) {
                e.preventDefault();
                alert('La cédula debe contener solo números');
                return;
            }
            
            // Validar teléfono (formato básico)
            if (!/^\d{7,10}$/.test(telefono.replace(/\D/g, ''))) {
                e.preventDefault();
                alert('Por favor, ingrese un número de teléfono válido');
                return;
            }
        });

        // Validación del formulario de usuario
        $('#formUsuario').on('submit', function(e) {
            const password = $('#password').val();
            
            // Validar contraseña (mínimo 6 caracteres)
            if (password.length < 6) {
                e.preventDefault();
                alert('La contraseña debe tener al menos 6 caracteres');
                return;
            }
        });
    </script>
</body>
</html>