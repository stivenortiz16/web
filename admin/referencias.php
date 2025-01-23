<?php
require_once '../includes/functions.php';
verificarPermiso('administrador');
require_once '../config/database.php';

// Iniciar sesión si no está iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$db = new Database();
$conn = $db->getConnection();

try {
    // Obtener todas las referencias con información del proveedor
    $stmt = $conn->prepare("
        SELECT r.*, p.razon_social as nombre_proveedor 
        FROM Referencias r 
        LEFT JOIN Proveedores p ON r.proveedor_id = p.id 
        ORDER BY r.id DESC
    ");
    $stmt->execute();
    $referencias = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Obtener lista de proveedores activos para el select
    $stmt = $conn->prepare("
        SELECT id, razon_social, nit 
        FROM Proveedores 
        WHERE estado = 1 
        ORDER BY razon_social
    ");
    $stmt->execute();
    $proveedores = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $_SESSION['error'] = "Error al cargar referencias: " . $e->getMessage();
    $referencias = [];
    $proveedores = [];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Referencias</title>
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
                    <h1 class="h2">Gestión de Referencias</h1>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#referenciaModal">
                        Nueva Referencia
                    </button>
                </div>

                <?php if (isset($_SESSION['mensaje'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php 
                        echo $_SESSION['mensaje'];
                        unset($_SESSION['mensaje']);
                        ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php 
                        echo $_SESSION['error'];
                        unset($_SESSION['error']);
                        ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <div class="table-responsive">
                    <table class="table table-striped table-hover" id="tablaPrincipal">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Referencia</th>
                                <th>Descripción</th>
                                <th>Proveedor</th>
                                <th>Cantidad</th>
                                <th>Valor Prenda</th>
                                <th>Valor por Prenda</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (isset($referencias) && is_array($referencias)): ?>
                                <?php foreach ($referencias as $referencia): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($referencia['id']); ?></td>
                                        <td><?php echo htmlspecialchars($referencia['referencia']); ?></td>
                                        <td><?php echo htmlspecialchars($referencia['descripcion']); ?></td>
                                        <td><?php echo htmlspecialchars($referencia['nombre_proveedor']); ?></td>
                                        <td><?php echo htmlspecialchars($referencia['cantidad']); ?></td>
                                        <td><?php echo number_format($referencia['valor_prenda'], 2); ?></td>
                                        <td><?php echo number_format($referencia['valor_por_prenda'], 2); ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-info btn-ver-tallas" data-id="<?php echo $referencia['id']; ?>">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                            <button class="btn btn-sm btn-primary btn-editar" 
                                                    data-id="<?php echo $referencia['id']; ?>"
                                                    data-referencia="<?php echo htmlspecialchars($referencia['referencia']); ?>"
                                                    data-descripcion="<?php echo htmlspecialchars($referencia['descripcion']); ?>"
                                                    data-proveedor-id="<?php echo htmlspecialchars($referencia['proveedor_id']); ?>"
                                                    data-cantidad="<?php echo htmlspecialchars($referencia['cantidad']); ?>"
                                                    data-valor-prenda="<?php echo htmlspecialchars($referencia['valor_prenda']); ?>"
                                                    data-valor-por-prenda="<?php echo htmlspecialchars($referencia['valor_por_prenda']); ?>">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <button class="btn btn-sm btn-danger btn-eliminar" data-id="<?php echo $referencia['id']; ?>">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </main>
        </div>
    </div>

    <!-- Modal para Crear/Editar Referencia -->
    <div class="modal fade" id="referenciaModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Nueva Referencia</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="referenciaForm" action="../includes/procesar_referencia.php" method="POST">
                    <div class="modal-body">
                        <input type="hidden" id="id" name="id">
                        <input type="hidden" name="fecha_actual" value="2025-01-14 02:51:28">
                        <input type="hidden" name="usuario_actual" value="sortizp20">
                        
                        <div class="mb-3">
                            <label for="referencia" class="form-label">Referencia</label>
                            <input type="text" class="form-control" id="referencia" name="referencia" required>
                        </div>

                        <div class="mb-3">
                            <label for="descripcion" class="form-label">Descripción</label>
                            <input type="text" class="form-control" id="descripcion" name="descripcion" required>
                        </div>

                        <div class="mb-3">
                            <label for="proveedor_id" class="form-label">Proveedor</label>
                            <select class="form-select" id="proveedor_id" name="proveedor_id" required>
                                <option value="">Seleccione un proveedor</option>
                                <?php foreach ($proveedores as $proveedor): ?>
                                    <option value="<?php echo $proveedor['id']; ?>">
                                        <?php echo htmlspecialchars($proveedor['razon_social'] . ' - ' . $proveedor['nit']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="cantidad" class="form-label">Cantidad</label>
                            <input type="number" class="form-control" id="cantidad" name="cantidad" required min="0">
                        </div>

                        <div class="mb-3">
                            <label for="valor_prenda" class="form-label">Valor Prenda</label>
                            <input type="number" class="form-control" id="valor_prenda" name="valor_prenda" required min="0" step="0.01">
                        </div>

                        <div class="mb-3">
                            <label for="valor_por_prenda" class="form-label">Valor por Prenda</label>
                            <input type="number" class="form-control" id="valor_por_prenda" name="valor_por_prenda" required min="0" step="0.01">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Tallas disponibles</label>
                            <div class="row">
                                <?php
                                $tallas = [4, 6, 8, 10, 12, 14, 16, 18, 20, 22, 28, 30, 31, 32, 33, 34, 36, 38, 40, 42, 44];
                                foreach ($tallas as $talla): ?>
                                    <div class="col-md-3 mb-2">
                                        <div class="input-group">
                                            <span class="input-group-text"><?php echo $talla; ?></span>
                                            <input type="number" class="form-control" 
                                                   name="tallas[<?php echo $talla; ?>]" 
                                                   value="0" min="0">
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
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

    <!-- Modal para Ver Tallas -->
    <div class="modal fade" id="tallasModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tallas Disponibles</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="tallasContainer" class="row">
                        <!-- Las tallas se cargarán aquí dinámicamente -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // Inicializar DataTable
            $('#tablaPrincipal').DataTable({
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
                    },
                    "aria": {
                        "sortAscending": ": activar para ordenar columna ascendentemente",
                        "sortDescending": ": activar para ordenar columna descendentemente"
                    }
                },
                order: [[0, 'desc']]
            });

           // Manejar el botón de editar
$('.btn-editar').click(function(e) {
    e.preventDefault(); // Prevenir el comportamiento por defecto
    const id = $(this).data('id');
    const referencia = $(this).data('referencia');
    const descripcion = $(this).data('descripcion');
    const proveedorId = $(this).data('proveedor-id');
    const cantidad = $(this).data('cantidad');
    const valorPrenda = $(this).data('valor-prenda');
    const valorPorPrenda = $(this).data('valor-por-prenda');

    // Actualizar el modal antes de mostrarlo
    $('#modalTitle').text('Editar Referencia');
    $('#id').val(id);
    $('#referencia').val(referencia);
    $('#descripcion').val(descripcion);
    $('#proveedor_id').val(proveedorId);
    $('#cantidad').val(cantidad);
    $('#valor_prenda').val(valorPrenda);
    $('#valor_por_prenda').val(valorPorPrenda);
    
    // Actualizar fecha y usuario
    $('input[name="fecha_actual"]').val('2025-01-14 03:04:48');
    $('input[name="usuario_actual"]').val('sortizp20');

    // Cargar las tallas existentes
    $.ajax({
        url: '../includes/obtener_tallas.php',
        type: 'GET',
        data: { id: id },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                // Resetear todas las tallas a 0
                $('input[name^="tallas["]').val(0);
                
                // Establecer las tallas guardadas
                const tallas = response.tallas;
                if (typeof tallas === 'string') {
                    try {
                        const tallasObj = JSON.parse(tallas);
                        Object.keys(tallasObj).forEach(talla => {
                            $(`input[name="tallas[${talla}]"]`).val(tallasObj[talla]);
                        });
                    } catch (e) {
                        console.error('Error al parsear tallas:', e);
                    }
                } else {
                    Object.keys(tallas).forEach(talla => {
                        $(`input[name="tallas[${talla}]"]`).val(tallas[talla]);
                    });
                }
            }
        },
        error: function(xhr, status, error) {
            console.error('Error al cargar tallas:', error);
            alert('Error al cargar las tallas');
        }
    });

    $('#referenciaModal').modal('show');
});

// Manejar el botón de nueva referencia
$('[data-bs-target="#referenciaModal"]').click(function(e) {
    if (!$(this).hasClass('btn-editar')) {
        $('#modalTitle').text('Nueva Referencia');
        $('#referenciaForm')[0].reset();
        $('#id').val('');
        $('input[name="fecha_actual"]').val('2025-01-14 03:04:48');
        $('input[name="usuario_actual"]').val('sortizp20');
        // Resetear todas las tallas a 0
        $('input[name^="tallas["]').val(0);
    }
});

// Limpiar el formulario cuando se cierra el modal
$('#referenciaModal').on('hidden.bs.modal', function () {
    if (!$('.btn-editar').is(':focus')) {
        $('#referenciaForm')[0].reset();
        $('#id').val('');
        $('input[name^="tallas["]').val(0);
    }
});

            // Manejar el botón de eliminar
            $('.btn-eliminar').click(function() {
                const id = $(this).data('id');
                if (confirm('¿Está seguro de que desea eliminar esta referencia?')) {
                    $.ajax({
                        url: '../includes/eliminar_referencia.php',
                        type: 'POST',
                        data: { id: id },
                        success: function(response) {
                            if (response.success) {
                                alert(response.message);
                                location.reload();
                            } else {
                                alert('Error: ' + response.message);
                            }
                        },
                        error: function() {
                            alert('Error al eliminar la referencia');
                        }
                    });
                }
            });

            // Manejar el botón de ver tallas
            $('.btn-ver-tallas').click(function() {
                const id = $(this).data('id');
                $.ajax({
                    url: '../includes/obtener_tallas.php',
                    type: 'GET',
                    data: { id: id },
                    success: function(response) {
                        if (response.success) {
                            let html = '';
                            const tallas = response.tallas;
                            Object.keys(tallas).sort((a, b) => Number(a) - Number(b)).forEach(talla => {
                                if (tallas[talla] > 0) {
                                    html += `
                                        <div class="col-md-4 mb-2">
                                            <div class="card">
                                                <div class="card-body text-center">
                                                    <h5 class="card-title">Talla ${talla}</h5>
                                                    <p class="card-text">Cantidad: ${tallas[talla]}</p>
                                                </div>
                                            </div>
                                        </div>
                                    `;
                                }
                            });
                            $('#tallasContainer').html(html || '<p class="col-12 text-center">No hay tallas disponibles</p>');
                            $('#tallasModal').modal('show');
                        } else {
                            alert('Error: ' + response.message);
                        }
                    },
                    error: function() {
                        alert('Error al cargar las tallas');
                    }
                });
            });

            // Validación del formulario
            $('#referenciaForm').submit(function(e) {
                let totalTallas = 0;
                $('input[name^="tallas["]').each(function() {
                    totalTallas += parseInt($(this).val()) || 0;
                });

                if (totalTallas !== parseInt($('#cantidad').val())) {
                    alert('La suma de las tallas debe ser igual a la cantidad total');
                    e.preventDefault();
                    return false;
                }
            });

            // Calcular total de tallas al cambiar cualquier input de talla
            $('input[name^="tallas["]').on('change', function() {
                let totalTallas = 0;
                $('input[name^="tallas["]').each(function() {
                    totalTallas += parseInt($(this).val()) || 0;
                });
                $('#cantidad').val(totalTallas);
            });

            // Validar que los valores sean números positivos
            $('input[type="number"]').on('input', function() {
                if ($(this).val() < 0) {
                    $(this).val(0);
                }
            });
        });
    </script>
</body>
</html>