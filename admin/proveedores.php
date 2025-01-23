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

// Obtener lista de proveedores
try {
    $stmt = $conn->prepare("
        SELECT 
            p.*,
            (SELECT COUNT(*) FROM Facturas f WHERE f.proveedor_id = p.id) as total_facturas
        FROM Proveedores p
        ORDER BY p.razon_social ASC
    ");
    $stmt->execute();
    $proveedores = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $mensaje = "Error al cargar proveedores: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Proveedores - Sistema de Nómina</title>
    <!-- jQuery primero -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Luego Bootstrap y otros CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/navbar.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <?php include '../includes/sidebar.php'; ?>

            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Gestión de Proveedores</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalProveedor">
                            <i class="bi bi-plus-lg"></i> Nuevo Proveedor
                        </button>
                    </div>
                </div>

                <?php if ($mensaje): ?>
                <div class="alert alert-info alert-dismissible fade show" role="alert">
                    <?php echo $mensaje; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>

                <div class="table-responsive">
                    <table class="table table-striped table-hover" id="tablaProveedores">
                        <thead>
                            <tr>
                                <th>NIT</th>
                                <th>Razón Social</th>
                                <th>Dirección</th>
                                <th>Teléfono</th>
                                <th>Correo</th>
                                <th>Contacto</th>
                                <th>Tel. Contacto</th>
                                <th>Ciudad</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($proveedores as $proveedor): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($proveedor['nit']); ?></td>
                                <td><?php echo htmlspecialchars($proveedor['razon_social']); ?></td>
                                <td><?php echo htmlspecialchars($proveedor['direccion']); ?></td>
                                <td><?php echo htmlspecialchars($proveedor['telefono']); ?></td>
                                <td><?php echo htmlspecialchars($proveedor['correo']); ?></td>
                                <td><?php echo htmlspecialchars($proveedor['contacto_nombre']); ?></td>
                                <td><?php echo htmlspecialchars($proveedor['contacto_telefono']); ?></td>
                                <td><?php echo htmlspecialchars($proveedor['ciudad']); ?></td>
                                <td><?php echo $proveedor['estado'] ? 'Activo' : 'Inactivo'; ?></td>
                                <td>
                                    <button class="btn btn-sm btn-primary" onclick="editarProveedor(<?php echo $proveedor['id']; ?>)">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button class="btn btn-sm btn-info" onclick="verFacturas(<?php echo $proveedor['id']; ?>)">
                                        <i class="bi bi-receipt"></i>
                                    </button>
                                    <button class="btn btn-sm btn-danger" onclick="eliminarProveedor(<?php echo $proveedor['id']; ?>)">
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

    <!-- Modal para Nuevo/Editar Proveedor -->
    <div class="modal fade" id="modalProveedor" tabindex="-1" aria-labelledby="modalProveedorLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalProveedorLabel">Nuevo Proveedor</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="formProveedor" action="../includes/procesar_proveedor.php" method="POST">
                    <div class="modal-body">
                        <input type="hidden" id="proveedor_id" name="id">
                        <div class="mb-3">
                            <label for="nit" class="form-label">NIT</label>
                            <input type="text" class="form-control" id="nit" name="nit" required>
                        </div>
                        <div class="mb-3">
                            <label for="razon_social" class="form-label">Razón Social</label>
                            <input type="text" class="form-control" id="razon_social" name="razon_social" required>
                        </div>
                        <div class="mb-3">
                            <label for="direccion" class="form-label">Dirección</label>
                            <input type="text" class="form-control" id="direccion" name="direccion" required>
                        </div>
                        <div class="mb-3">
                            <label for="telefono" class="form-label">Teléfono</label>
                            <input type="text" class="form-control" id="telefono" name="telefono" required>
                        </div>
                        <div class="mb-3">
                            <label for="correo" class="form-label">Correo</label>
                            <input type="email" class="form-control" id="correo" name="correo">
                        </div>
                        <div class="mb-3">
                            <label for="contacto_nombre" class="form-label">Nombre de Contacto</label>
                            <input type="text" class="form-control" id="contacto_nombre" name="contacto_nombre">
                        </div>
                        <div class="mb-3">
                            <label for="contacto_telefono" class="form-label">Teléfono de Contacto</label>
                            <input type="text" class="form-control" id="contacto_telefono" name="contacto_telefono">
                        </div>
                        <div class="mb-3">
                            <label for="ciudad" class="form-label">Ciudad</label>
                            <input type="text" class="form-control" id="ciudad" name="ciudad" required>
                        </div>
                        <div class="mb-3">
                            <label for="estado" class="form-label">Estado</label>
                            <select class="form-select" id="estado" name="estado">
                                <option value="1">Activo</option>
                                <option value="0">Inactivo</option>
                            </select>
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

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    
    <script>
        $(document).ready(function() {
            $('#tablaProveedores').DataTable({
                language: {
                    "decimal": "",
                    "emptyTable": "No hay datos disponibles",
                    "info": "Mostrando _START_ a _END_ de _TOTAL_ registros",
                    "infoEmpty": "Mostrando 0 a 0 de 0 registros",
                    "infoFiltered": "(filtrado de _MAX_ registros totales)",
                    "infoPostFix": "",
                    "thousands": ",",
                    "lengthMenu": "Mostrar _MENU_ registros",
                    "loadingRecords": "Cargando...",
                    "processing": "Procesando...",
                    "search": "Buscar:",
                    "zeroRecords": "No se encontraron registros coincidentes",
                    "paginate": {
                        "first": "Primero",
                        "last": "Último",
                        "next": "Siguiente",
                        "previous": "Anterior"
                    }
                },
                order: [[1, 'asc']], // Ordenar por razón social
                responsive: true
            });
        });

        function editarProveedor(id) {
            $.ajax({
                url: '../includes/obtener_proveedor.php',
                type: 'POST',
                data: { id: id },
                success: function(response) {
                    const proveedor = JSON.parse(response);
                    
                    $('#proveedor_id').val(proveedor.id);
                    $('#nit').val(proveedor.nit);
                    $('#razon_social').val(proveedor.razon_social);
                    $('#direccion').val(proveedor.direccion);
                    $('#telefono').val(proveedor.telefono);
                    $('#correo').val(proveedor.correo);
                    $('#contacto_nombre').val(proveedor.contacto_nombre);
                    $('#contacto_telefono').val(proveedor.contacto_telefono);
                    $('#ciudad').val(proveedor.ciudad);
                    $('#estado').val(proveedor.estado);
                    
                    $('#modalProveedorLabel').text('Editar Proveedor');
                    $('#modalProveedor').modal('show');
                },
                error: function() {
                    alert('Error al cargar los datos del proveedor');
                }
            });
        }

        function verFacturas(id) {
            window.location.href = `facturas.php?proveedor_id=${id}`;
        }

        function eliminarProveedor(id) {
            if (confirm('¿Está seguro de que desea eliminar este proveedor?')) {
                $.ajax({
                    url: '../includes/eliminar_proveedor.php',
                    type: 'POST',
                    data: { id: id },
                    success: function(response) {
                        const result = JSON.parse(response);
                        if (result.success) {
                            location.reload();
                        } else {
                            alert(result.message);
                        }
                    },
                    error: function() {
                        alert('Error al eliminar el proveedor');
                    }
                });
            }
        }

        // Limpiar formulario cuando se cierra el modal
        $('#modalProveedor').on('hidden.bs.modal', function () {
            $('#formProveedor')[0].reset();
            $('#proveedor_id').val('');
            $('#modalProveedorLabel').text('Nuevo Proveedor');
        });

        // Validación del formulario mejorada
        $('#formProveedor').on('submit', function(e) {
            let errores = [];
            
            // Validar NIT
            const nit = $('#nit').val().trim();
            if (!/^\d{8,12}$/.test(nit.replace(/\D/g, ''))) {
                errores.push('El NIT debe tener entre 8 y 12 dígitos');
            }
            
            // Validar teléfono
            const telefono = $('#telefono').val().trim();
            if (!/^\d{7,10}$/.test(telefono.replace(/\D/g, ''))) {
                errores.push('El teléfono debe tener entre 7 y 10 dígitos');
            }
            
            

                // Validar correo (solo si se proporciona)
            const correo = $('#correo').val().trim();
            if (correo && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(correo)) {
                errores.push('Ingrese un correo electrónico válido');
            }
            
            // Validar razón social
            const razonSocial = $('#razon_social').val().trim();
            if (!razonSocial) {
                errores.push('La razón social es obligatoria');
            }

            // Validar dirección
            const direccion = $('#direccion').val().trim();
            if (!direccion) {
                errores.push('La dirección es obligatoria');
            }

            // Validar ciudad
            const ciudad = $('#ciudad').val().trim();
            if (!ciudad) {
                errores.push('La ciudad es obligatoria');
            }

            // Validar teléfono de contacto (si se proporciona)
            const contactoTelefono = $('#contacto_telefono').val().trim();
            if (contactoTelefono && !/^\d{7,10}$/.test(contactoTelefono.replace(/\D/g, ''))) {
                errores.push('El teléfono de contacto debe tener entre 7 y 10 dígitos');
            }

            // Si hay errores, mostrarlos y prevenir el envío del formulario
            if (errores.length > 0) {
                e.preventDefault();
                alert('Por favor corrija los siguientes errores:\n\n- ' + errores.join('\n- '));
                return false;
            }
            
            return true;
        });
    </script>
</body>
</html>