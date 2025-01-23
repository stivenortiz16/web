<?php
require_once '../includes/functions.php';
verificarPermiso('administrador');
require_once '../config/database.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$db = new Database();
$conn = $db->getConnection();

try {
    $stmt = $conn->query("SELECT * FROM operacionesposibles ORDER BY nombre_operacion");
    $operaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $_SESSION['tipo_mensaje'] = 'danger';
    $_SESSION['mensaje'] = "Error al cargar las operaciones: " . $e->getMessage();
    $operaciones = [];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Operaciones Posibles</title>
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
                    <h1 class="h2">Gestión de Operaciones Posibles</h1>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#operacionModal">
                        Nueva Operación
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
                                <th>ID</th>
                                <th>Nombre de Operación</th>
                                <th>Costo</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($operaciones as $operacion): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($operacion['id']); ?></td>
                                    <td><?php echo htmlspecialchars($operacion['nombre_operacion']); ?></td>
                                    <td><?php echo number_format($operacion['costo'], 2); ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-primary btn-editar" 
                                                data-id="<?php echo $operacion['id']; ?>"
                                                data-nombre="<?php echo htmlspecialchars($operacion['nombre_operacion']); ?>"
                                                data-costo="<?php echo $operacion['costo']; ?>">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button class="btn btn-sm btn-danger btn-eliminar" 
                                                data-id="<?php echo $operacion['id']; ?>">
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

    <!-- Modal para Crear/Editar Operación -->
    <div class="modal fade" id="operacionModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Nueva Operación</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="operacionForm" action="../includes/procesar_operacion_posible.php" method="POST">
                    <div class="modal-body">
                        <input type="hidden" id="id" name="id">
                        
                        <div class="mb-3">
                            <label for="nombre_operacion" class="form-label">Nombre de Operación</label>
                            <input type="text" class="form-control" id="nombre_operacion" name="nombre_operacion" required>
                        </div>

                        <div class="mb-3">
                            <label for="costo" class="form-label">Costo</label>
                            <input type="number" class="form-control" id="costo" name="costo" step="0.01" min="0" required>
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
$(document).ready(function () {
    // Inicializar DataTable
    $('#tablaPrincipal').DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.11.5/i18n/es-ES.json',
        },
    });

    // Variable para controlar el modo de edición
    let isEditMode = false;

    // Manejar el botón de editar
    $('.btn-editar').click(function (e) {
        e.preventDefault();
        isEditMode = true;
        const id = $(this).data('id');
        const nombre = $(this).data('nombre');
        const costo = $(this).data('costo');

        // Cambiar el título del modal y rellenar los campos
        $('#modalTitle').text('Editar Operación');
        $('#id').val(id);
        $('#nombre_operacion').val(nombre);
        $('#costo').val(costo);

        // Mostrar el modal
        $('#operacionModal').modal('show');
    });

    // Manejar la apertura del modal
    $('#operacionModal').on('show.bs.modal', function (e) {
        // Solo resetear si NO estamos en modo edición y el modal se abre con el botón "Nueva Operación"
        if (!isEditMode && e.relatedTarget) {
            $('#modalTitle').text('Nueva Operación');
            $('#operacionForm')[0].reset();
            $('#id').val('');
        }
    });

    // Resetear el modo de edición cuando se cierra el modal
    $('#operacionModal').on('hidden.bs.modal', function () {
        isEditMode = false;
        if (!$('.btn-editar').is(':focus')) {
            $('#operacionForm')[0].reset();
            $('#id').val('');
            $('#modalTitle').text('Nueva Operación');
        }
    });

    // Manejar el botón de eliminar
    $('.btn-eliminar').click(function () {
        const id = $(this).data('id');
        if (confirm('¿Está seguro de que desea eliminar esta operación?')) {
            window.location.href = '../includes/eliminar_operacion_posible.php?id=' + id;
        }
    });

    // Validar que los valores sean números positivos
    $('input[type="number"]').on('input', function () {
        if ($(this).val() < 0) {
            $(this).val(0);
        }
    });

    // Validación del formulario antes de enviarlo
    $('#operacionForm').submit(function (e) {
        const costo = parseFloat($('#costo').val());
        const nombre = $('#nombre_operacion').val().trim();

        if (nombre === '') {
            alert('El nombre de la operación es requerido');
            e.preventDefault();
            return false;
        }

        if (isNaN(costo) || costo < 0) {
            alert('El costo debe ser un número positivo');
            e.preventDefault();
            return false;
        }
    });
});
</script>
</body>
</html>