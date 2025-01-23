<?php
require_once '../includes/functions.php';
verificarPermiso('administrador');
require_once '../config/database.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Definir variables globales
$currentTime = '2025-01-14 05:33:50';
$currentUser = 'sortizp20';

$db = new Database();
$conn = $db->getConnection();

try {
    // Configuración por defecto de la empresa
    $datosEmpresa = [
        'nombre_empresa' => 'NOMINA XYZ',
        'nit' => '123456789-0',
        'direccion' => 'Calle Principal #123',
        'telefono' => '(+57) 123-4567',
        'email' => 'info@nominaxyz.com'
    ];

    // Obtener lista de proveedores
    $stmtProveedores = $conn->query("SELECT id, razon_social FROM proveedores WHERE estado = 'activo'");
    $proveedores = $stmtProveedores->fetchAll(PDO::FETCH_ASSOC);

    // Obtener referencias con información del proveedor
    $stmtReferencias = $conn->query("
        SELECT r.id, r.referencia, r.descripcion, r.cantidad_disponible, r.proveedor_id 
        FROM referencias r 
        WHERE r.estado = 'activo'
    ");
    $referencias = $stmtReferencias->fetchAll(PDO::FETCH_ASSOC);

    // Agrupar referencias por proveedor
    $referenciasPorProveedor = [];
    foreach ($referencias as $referencia) {
        $referenciasPorProveedor[$referencia['proveedor_id']][] = $referencia;
    }
    
    // Convertir a JSON para usar en JavaScript
    $referenciasPorProveedorJSON = json_encode($referenciasPorProveedor);

    // Obtener facturas con información completa
    $stmt = $conn->query("
        SELECT f.*, p.razon_social as proveedor_nombre, e.nombre as empleado_nombre,
               r.referencia as nombre_referencia, r.descripcion as descripcion_referencia,
               df.cantidad as cantidad_referencia
        FROM facturas f
        LEFT JOIN proveedores p ON f.proveedor_id = p.id
        LEFT JOIN empleados e ON f.empleado_cedula = e.cedula
        LEFT JOIN detalles_factura df ON f.id = df.factura_id
        LEFT JOIN referencias r ON df.referencia_id = r.id
        ORDER BY f.fecha_emision DESC
    ");
    $facturas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Obtener lista de empleados
    $stmtEmpleados = $conn->query("SELECT cedula, nombre FROM empleados");
    $empleados = $stmtEmpleados->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $_SESSION['tipo_mensaje'] = 'danger';
    $_SESSION['mensaje'] = "Error al cargar los datos: " . $e->getMessage();
    $facturas = [];
    $proveedores = [];
    $empleados = [];
    $referencias = [];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Facturas</title>
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
                <!-- Información de la empresa -->
                <div class="card mt-3 mb-4">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-8">
                                <h4><?php echo htmlspecialchars($datosEmpresa['nombre_empresa']); ?></h4>
                                <p class="mb-1">NIT: <?php echo htmlspecialchars($datosEmpresa['nit']); ?></p>
                                <p class="mb-1">Dirección: <?php echo htmlspecialchars($datosEmpresa['direccion']); ?></p>
                                <p class="mb-1">Teléfono: <?php echo htmlspecialchars($datosEmpresa['telefono']); ?></p>
                                <p class="mb-0">Email: <?php echo htmlspecialchars($datosEmpresa['email']); ?></p>
                            </div>
                            <div class="col-md-4 text-end">
                                <p class="mb-1">Fecha y Hora: <?php echo $currentTime; ?></p>
                                <p class="mb-0">Usuario: <?php echo htmlspecialchars($currentUser); ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Gestión de Facturas</h1>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#facturaModal">
                        Nueva Factura
                    </button>
                </div>

                <?php if (isset($_SESSION['mensaje'])): ?>
                    <div class="alert alert-<?php echo $_SESSION['tipo_mensaje'] ?? 'info'; ?> alert-dismissible fade show" role="alert">
                        <?php 
                        echo $_SESSION['mensaje'];
                        unset($_SESSION['mensaje']);
                        unset($_SESSION['tipo_mensaje']);
                        ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <div class="table-responsive">
                    <table class="table table-striped table-hover" id="tablaPrincipal">
                        <thead>
                            <tr>
                                <th>N° Factura</th>
                                <th>N° Remisión</th>
                                <th>Tipo</th>
                                <th>Referencia</th>
                                <th>Cantidad</th>
                                <th>Fecha Emisión</th>
                                <th>Fecha Vencimiento</th>
                                <th>Proveedor</th>
                                <th>Empleado</th>
                                <th>Subtotal</th>
                                <th>IVA</th>
                                <th>Total</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($facturas as $factura): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($factura['numero_factura']); ?></td>
                                    <td><?php echo htmlspecialchars($factura['numero_remision']); ?></td>
                                    <td><?php echo htmlspecialchars($factura['tipo']); ?></td>
                                    <td><?php echo htmlspecialchars($factura['nombre_referencia']); ?></td>
                                    <td><?php echo htmlspecialchars($factura['cantidad_referencia']); ?></td>
                                    <td><?php echo date('Y-m-d', strtotime($factura['fecha_emision'])); ?></td>
                                    <td><?php echo date('Y-m-d', strtotime($factura['fecha_vencimiento'])); ?></td>
                                    <td><?php echo htmlspecialchars($factura['proveedor_nombre']); ?></td>
                                    <td><?php echo htmlspecialchars($factura['empleado_nombre']); ?></td>
                                    <td><?php echo number_format($factura['subtotal'], 2); ?></td>
                                    <td><?php echo number_format($factura['iva'], 2); ?></td>
                                    <td><?php echo number_format($factura['total'], 2); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo getEstadoClass($factura['estado']); ?>">
                                            <?php echo htmlspecialchars($factura['estado']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-primary btn-editar" 
                                                data-id="<?php echo $factura['id']; ?>"
                                                data-numero="<?php echo htmlspecialchars($factura['numero_factura']); ?>"
                                                data-remision="<?php echo htmlspecialchars($factura['numero_remision']); ?>"
                                                data-tipo="<?php echo htmlspecialchars($factura['tipo']); ?>"
                                                data-referencia="<?php echo $factura['referencia_id']; ?>"
                                                data-cantidad="<?php echo $factura['cantidad_referencia']; ?>"
                                                data-emision="<?php echo $factura['fecha_emision']; ?>"
                                                data-vencimiento="<?php echo $factura['fecha_vencimiento']; ?>"
                                                data-proveedor="<?php echo $factura['proveedor_id']; ?>"
                                                data-empleado="<?php echo $factura['empleado_cedula']; ?>"
                                                data-subtotal="<?php echo $factura['subtotal']; ?>"
                                                data-iva="<?php echo $factura['iva']; ?>"
                                                data-total="<?php echo $factura['total']; ?>"
                                                data-estado="<?php echo htmlspecialchars($factura['estado']); ?>"
                                                data-notas="<?php echo htmlspecialchars($factura['notas']); ?>">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button class="btn btn-sm btn-info btn-detalles" 
                                                data-id="<?php echo $factura['id']; ?>">
                                            <i class="bi bi-list-ul"></i>
                                        </button>
                                        <button class="btn btn-sm btn-danger btn-eliminar" 
                                                data-id="<?php echo $factura['id']; ?>"><i class="bi bi-trash"></i>
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

    <!-- Modal para Crear/Editar Factura -->
    <div class="modal fade" id="facturaModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Nueva Factura</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="facturaForm" action="../includes/procesar_factura.php" method="POST">
                    <div class="modal-body">
                        <input type="hidden" id="id" name="id">
                        
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="numero_factura" class="form-label">Número de Factura</label>
                                <input type="text" class="form-control" id="numero_factura" name="numero_factura" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="numero_remision" class="form-label">Número de Remisión</label>
                                <input type="text" class="form-control" id="numero_remision" name="numero_remision">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="tipo" class="form-label">Tipo</label>
                                <select class="form-select" id="tipo" name="tipo" required>
                                    <option value="compra">Compra</option>
                                    <option value="venta">Venta</option>
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="proveedor_id" class="form-label">Proveedor</label>
                                <select class="form-select" id="proveedor_id" name="proveedor_id" required>
                                    <option value="">Seleccione un proveedor</option>
                                    <?php foreach ($proveedores as $proveedor): ?>
                                        <option value="<?php echo $proveedor['id']; ?>">
                                            <?php echo htmlspecialchars($proveedor['razon_social']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="referencia_id" class="form-label">Referencia</label>
                                <select class="form-select" id="referencia_id" name="referencia_id" required disabled>
                                    <option value="">Primero seleccione un proveedor</option>
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="cantidad_disponible" class="form-label">Cantidad Disponible</label>
                                <input type="number" class="form-control" id="cantidad_disponible" readonly>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="cantidad" class="form-label">Cantidad a Facturar</label>
                                <input type="number" class="form-control" id="cantidad" name="cantidad" min="1" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="fecha_emision" class="form-label">Fecha de Emisión</label>
                                <input type="date" class="form-control" id="fecha_emision" name="fecha_emision" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="fecha_vencimiento" class="form-label">Fecha de Vencimiento</label>
                                <input type="date" class="form-control" id="fecha_vencimiento" name="fecha_vencimiento" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label for="empleado_cedula" class="form-label">Empleado</label>
                                <select class="form-select" id="empleado_cedula" name="empleado_cedula" required>
                                    <option value="">Seleccione un empleado</option>
                                    <?php foreach ($empleados as $empleado): ?>
                                        <option value="<?php echo $empleado['cedula']; ?>">
                                            <?php echo htmlspecialchars($empleado['nombre']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="subtotal" class="form-label">Subtotal</label>
                                <input type="number" class="form-control" id="subtotal" name="subtotal" step="0.01" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="iva" class="form-label">IVA</label>
                                <input type="number" class="form-control" id="iva" name="iva" step="0.01" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="total" class="form-label">Total</label>
                                <input type="number" class="form-control" id="total" name="total" step="0.01" readonly required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="estado" class="form-label">Estado</label>
                            <select class="form-select" id="estado" name="estado" required>
                                <option value="pendiente">Pendiente</option>
                                <option value="pagada">Pagada</option>
                                <option value="anulada">Anulada</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="notas" class="form-label">Notas</label>
                            <textarea class="form-control" id="notas" name="notas" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                        <button type="submit" class="btn btn-primary">Guardar</button>
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
    // Almacenar las referencias por proveedor
    const referenciasPorProveedor = <?php echo $referenciasPorProveedorJSON; ?>;

    $(document).ready(function() {
        // Inicializar DataTable
        $('#tablaPrincipal').DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.11.5/i18n/es-ES.json'
            },
            order: [[5, 'desc']] // Ordenar por fecha de emisión descendente
        });

        // Variable para controlar el modo de edición
        let isEditMode = false;

        // Manejar cambio de proveedor
        $('#proveedor_id').on('change', function() {
            const proveedorId = $(this).val();
            const $referenciaSelect = $('#referencia_id');
            
            // Limpiar y deshabilitar el select de referencias si no hay proveedor seleccionado
            if (!proveedorId) {
                $referenciaSelect.html('<option value="">Primero seleccione un proveedor</option>').prop('disabled', true);
                $('#cantidad_disponible').val('');
                $('#cantidad').val('');
                return;
            }

            // Habilitar el select de referencias
            $referenciaSelect.prop('disabled', false);

            // Obtener referencias del proveedor seleccionado
            const referencias = referenciasPorProveedor[proveedorId] || [];

            // Actualizar el select de referencias
            let options = '<option value="">Seleccione una referencia</option>';
            referencias.forEach(ref => {
                options += `<option value="${ref.id}" 
                                   data-cantidad="${ref.cantidad_disponible}"
                                   data-descripcion="${ref.descripcion}">
                            ${ref.referencia}
                           </option>`;
            });
            $referenciaSelect.html(options);
        });

        // Manejar cambio de referencia
        $('#referencia_id').on('change', function() {
            const $selectedOption = $(this).find('option:selected');
            const cantidadDisponible = $selectedOption.data('cantidad');
            
            // Actualizar campo de cantidad disponible
            $('#cantidad_disponible').val(cantidadDisponible);
            
            // Actualizar cantidad máxima permitida
            $('#cantidad').attr('max', cantidadDisponible);
            
            // Limpiar cantidad a facturar
            $('#cantidad').val('');
        });

        // Validar que la cantidad a facturar no exceda la disponible
        $('#cantidad').on('input', function() {
            const cantidadDisponible = parseInt($('#cantidad_disponible').val()) || 0;
            const cantidadIngresada = parseInt($(this).val()) || 0;
            
            if (cantidadIngresada > cantidadDisponible) {
                alert('La cantidad a facturar no puede exceder la cantidad disponible');
                $(this).val(cantidadDisponible);
            }
        });

        // Manejar el botón de editar
        $('.btn-editar').click(function(e) {
            e.preventDefault();
            isEditMode = true;
            
            const id = $(this).data('id');
            const numero = $(this).data('numero');
            const remision = $(this).data('remision');
            const tipo = $(this).data('tipo');
            const referencia = $(this).data('referencia');
            const cantidad = $(this).data('cantidad');
            const emision = $(this).data('emision').split(' ')[0];
            const vencimiento = $(this).data('vencimiento').split(' ')[0];
            const proveedor = $(this).data('proveedor');
            const empleado = $(this).data('empleado');
            const subtotal = $(this).data('subtotal');
            const iva = $(this).data('iva');
            const total = $(this).data('total');
            const estado = $(this).data('estado');
            const notas = $(this).data('notas');

            $('#modalTitle').text('Editar Factura');
            $('#id').val(id);
            $('#numero_factura').val(numero);
            $('#numero_remision').val(remision);
            $('#tipo').val(tipo);
            
            // Primero establecer el proveedor y esperar a que se carguen las referencias
            $('#proveedor_id').val(proveedor).trigger('change');
            
            // Esperar a que se carguen las referencias antes de establecer el valor
            setTimeout(() => {
                $('#referencia_id').val(referencia);
                $('#cantidad_disponible').val(cantidad);
                $('#cantidad').val(cantidad);
            }, 100);

            $('#fecha_emision').val(emision);
            $('#fecha_vencimiento').val(vencimiento);
            $('#empleado_cedula').val(empleado);
            $('#subtotal').val(subtotal);
            $('#iva').val(iva);
            $('#total').val(total);
            $('#estado').val(estado);
            $('#notas').val(notas);

            $('#facturaModal').modal('show');
        });

        // Calcular IVA y total automáticamente
        $('#subtotal').on('input', function() {
            const subtotal = parseFloat($(this).val()) || 0;
            const iva = subtotal * 0.19; // 19% IVA
            $('#iva').val(iva.toFixed(2));
            calcularTotales();
        });

        function calcularTotales() {
            const subtotal = parseFloat($('#subtotal').val()) || 0;
            const iva = parseFloat($('#iva').val()) || 0;
            const total = subtotal + iva;
            $('#total').val(total.toFixed(2));
        }

        // Validación del formulario
        $('#facturaForm').submit(function(e) {
            e.preventDefault();
            
            // Validar fechas
            const fechaEmision = new Date($('#fecha_emision').val());
            const fechaVencimiento = new Date($('#fecha_vencimiento').val());
            
            if (fechaVencimiento < fechaEmision) {
                alert('La fecha de vencimiento no puede ser anterior a la fecha de emisión');
                return false;
            }

            // Validar montos y cantida// Validar montos y cantidades
            const cantidadDisponible = parseInt($('#cantidad_disponible').val()) || 0;
            const cantidadIngresada = parseInt($('#cantidad').val()) || 0;
            const subtotal = parseFloat($('#subtotal').val()) || 0;
            const iva = parseFloat($('#iva').val()) || 0;
            const total = parseFloat($('#total').val()) || 0;

            if (cantidadIngresada > cantidadDisponible) {
                alert('La cantidad a facturar no puede exceder la cantidad disponible');
                return false;
            }

            if (subtotal < 0 || iva < 0 || total < 0) {
                alert('Los montos no pueden ser negativos');
                return false;
            }

            if (cantidadIngresada < 1) {
                alert('La cantidad debe ser mayor a 0');
                return false;
            }

            // Validar campos requeridos
            if ($('#numero_factura').val().trim() === '') {
                alert('El número de factura es requerido');
                return false;
            }

            if ($('#proveedor_id').val() === '') {
                alert('Debe seleccionar un proveedor');
                return false;
            }

            if ($('#referencia_id').val() === '') {
                alert('Debe seleccionar una referencia');
                return false;
            }

            if ($('#empleado_cedula').val() === '') {
                alert('Debe seleccionar un empleado');
                return false;
            }

            // Agregar campos adicionales
            const currentDateTime = '2025-01-14 05:36:47'; // Usar la fecha y hora actuales
            const currentUser = 'sortizp20'; // Usar el usuario actual

            $('<input>').attr({
                type: 'hidden',
                name: 'fecha_creacion',
                value: currentDateTime
            }).appendTo(this);

            $('<input>').attr({
                type: 'hidden',
                name: 'usuario_creacion',
                value: currentUser
            }).appendTo(this);

            // Si todo está bien, enviar el formulario
            this.submit();
        });

        // Manejar el botón de eliminar
        $('.btn-eliminar').click(function() {
            const id = $(this).data('id');
            if (confirm('¿Está seguro de que desea eliminar esta factura? Esta acción no se puede deshacer.')) {
                window.location.href = '../includes/eliminar_factura.php?id=' + id;
            }
        });

        // Manejar el botón de detalles
        $('.btn-detalles').click(function() {
            const id = $(this).data('id');
            window.location.href = 'detalles_factura.php?id=' + id;
        });

        // Establecer fechas por defecto al abrir el modal para nueva factura
        $('#facturaModal').on('show.bs.modal', function(e) {
            if (!isEditMode) {
                const today = new Date().toISOString().split('T')[0];
                const thirtyDaysLater = new Date();
                thirtyDaysLater.setDate(thirtyDaysLater.getDate() + 30);
                
                $('#fecha_emision').val(today);
                $('#fecha_vencimiento').val(thirtyDaysLater.toISOString().split('T')[0]);
            }
        });

        // Resetear modo de edición cuando se cierra el modal
        $('#facturaModal').on('hidden.bs.modal', function() {
            isEditMode = false;
            $('#facturaForm')[0].reset();
            $('#referencia_id').prop('disabled', true);
            $('#cantidad_disponible').val('');
        });

        // Formatear números en la tabla
        function formatearNumero(numero) {
            return new Intl.NumberFormat('es-CO', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }).format(numero);
        }
    });

    // Función auxiliar para obtener la clase de estado
    function getEstadoClass(estado) {
        switch (estado.toLowerCase()) {
            case 'pendiente':
                return 'warning';
            case 'pagada':
                return 'success';
            case 'anulada':
                return 'danger';
            default:
                return 'secondary';
        }
    }
    </script>
</body>
</html>