<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

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

// Obtener la semana actual
$semanaActual = date('W');

// Obtener información del empleado logueado
$empleadoLogueado = [
    'cedula' => $_SESSION['cedula'],
    'nombre' => $_SESSION['nombre']
];

// Obtener lista de operaciones posibles
try {
// Obtener lista de empleados (solo para administradores)
$empleados = [];
if ($_SESSION['perfil'] === 'administrador') {
    try {
        $stmtEmpleados = $conn->query("SELECT cedula, nombre FROM empleados ORDER BY nombre");
        $empleados = $stmtEmpleados->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $mensaje = "Error al cargar los empleados: " . $e->getMessage();
    }
}

    $stmtOperaciones = $conn->query("SELECT id, nombre_operacion, costo FROM operacionesposibles");
    $operaciones = $stmtOperaciones->fetchAll(PDO::FETCH_ASSOC);

    // Obtener lista de referencias
    $stmtReferencias = $conn->query("SELECT id, referencia, cantidad FROM referencias");
    $referencias = $stmtReferencias->fetchAll(PDO::FETCH_ASSOC);

    // Convertir arrays a JSON para usar en JavaScript
    $operacionesJSON = json_encode($operaciones);
    $referenciasJSON = json_encode($referencias);
    $empleadoJSON = json_encode($empleadoLogueado);

} catch (PDOException $e) {
    $mensaje = "Error al cargar los datos: " . $e->getMessage();
}

// Agregar función para verificar duplicados
function verificarOperacionDuplicada($conn, $id_referencia, $id_operacion) {
    try {
        $stmt = $conn->prepare("
            SELECT COUNT(*) as count 
            FROM nomina 
            WHERE id_referencia = :id_referencia 
            AND id_operacion = :id_operacion 
            AND WEEK(fecha) = WEEK(CURRENT_DATE())
        ");
        
        $stmt->execute([
            ':id_referencia' => $id_referencia,
            ':id_operacion' => $id_operacion
        ]);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'] > 0;
    } catch (PDOException $e) {
        return false;
    }
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Nómina</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
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
                    <h1 class="h2">Gestión de Nómina</h1>
                    
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#modalNovedad">
        <i class="bi bi-exclamation-triangle"></i> Agregar Novedad
    </button>
    <button type="button" class="btn btn-primary me-2" data-bs-toggle="modal" data-bs-target="#modalNomina">
        <i class="bi bi-plus-lg"></i> Nuevo Registro
    </button>
    <?php if ($_SESSION['perfil'] === 'administrador'): ?>
    
    <?php endif; ?>
</div>
                </div>

                <?php if ($mensaje): ?>
                <div class="alert alert-info alert-dismissible fade show" role="alert">
                    <?php echo $mensaje; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>

                <!-- Mostrar la semana actual -->
                <div class="alert alert-primary" role="alert">
                    Generando nómina para la semana: <?php echo $semanaActual; ?>
                </div>

                <!-- Filtros para administrador -->
<?php if ($_SESSION['perfil'] === 'administrador'): ?>
    <div class="card mb-3">
    <div class="card-body">
        <h5 class="card-title">Filtros</h5>
        <div class="row">
            <div class="col-md-3">
                <div class="mb-3">
                    <label for="filtroMes" class="form-label">Mes</label>
                    <select class="form-select" id="filtroMes">
                        <option value="">Todos los meses</option>
                        <?php
                        $meses = [
                            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 
                            4 => 'Abril', 5 => 'Mayo', 6 => 'Junio',
                            7 => 'Julio', 8 => 'Agosto', 9 => 'Septiembre', 
                            10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
                        ];
                        foreach ($meses as $num => $nombre) {
                            echo "<option value='{$num}'>{$nombre}</option>";
                        }
                        ?>
                    </select>
                </div>
            </div>
            <div class="col-md-3">
                <div class="mb-3">
                    <label for="filtroSemana" class="form-label">Semana</label>
                    <select class="form-select" id="filtroSemana">
                        <option value="">Todas las semanas</option>
                        <?php
                        for ($i = 1; $i <= 53; $i++) {
                            echo "<option value='{$i}'>Semana {$i}</option>";
                        }
                        ?>
                    </select>
                </div>
            </div>
            <div class="col-md-3">
                <div class="mb-3">
                    <label for="filtroEmpleado" class="form-label">Empleado</label>
                    <select class="form-select" id="filtroEmpleado">
                        <option value="">Todos los empleados</option>
                        <?php foreach ($empleados as $emp): ?>
                            <option value="<?php echo $emp['cedula']; ?>"><?php echo $emp['nombre']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="col-md-3">
                <div class="mb-3">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-flex gap-2">
                        <button class="btn btn-primary w-50" id="aplicarFiltros">
                            <i class="bi bi-search"></i> Filtrar
                        </button>
                        <button class="btn btn-secondary w-50" id="limpiarFiltros">
                            <i class="bi bi-x-circle"></i> Limpiar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

                <!-- Rango de fechas de la semana -->
                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-body">
                                <h6 class="card-subtitle mb-2 text-muted">Rango de fechas de la semana <?php echo $semanaActual; ?></h6>
                                <div id="rangoSemana" class="h5"></div>
                            </div>
                        </div>
                    </div>
                </div>

                

                <!-- Tabla de nómina -->
                <div class="table-responsive">
                    <table class="table table-striped table-hover" id="tablaNomina">
                        <thead>
                            <tr>
                                <th>N</th>
                                <th>Fecha</th>
                                <th>Empleado</th>
                                <th>Referencia</th>
                                <th>Operación</th>
                                <th>Costo Operación</th>
                                <th>Cantidad</th>
                                <th>Cantidad de Tallas</th>
                                <th>Tallas</th>
                                <th>Subtotal</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Aquí se cargarán los registros de nómina -->
                        </tbody>
                    </table>
                </div>

                <!-- Mostrar el total de la semana -->
                <div class="alert alert-success mt-3" role="alert" id="totalSemana">
                    Total de la semana: $0
                </div>
            </main>
        </div>
    </div>

<!-- Modal para Novedades -->
<div class="modal fade" id="modalNovedad" tabindex="-1" aria-labelledby="modalNovedadLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalNovedadLabel">Agregar Novedad</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formNovedad">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="novedadEmpleado" class="form-label">Empleado</label>
                        <select class="form-select" id="novedadEmpleado" name="empleado" required>
                            <option value="">Seleccione un empleado</option>
                            <?php foreach ($empleados as $emp): ?>
                                <option value="<?php echo $emp['cedula']; ?>"><?php echo $emp['nombre']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="novedadReferencia" class="form-label">Referencia</label>
                        <select class="form-select" id="novedadReferencia" name="referencia" required>
                            <option value="">Seleccione una referencia</option>
                            <?php foreach ($referencias as $ref): ?>
                                <option value="<?php echo $ref['id']; ?>"><?php echo $ref['referencia']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="novedadNota" class="form-label">Nota de Novedad</label>
                        <textarea class="form-control" id="novedadNota" name="nota" rows="4" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" id="guardarNovedad">Guardar Novedad</button>
                </div>
            </form>
        </div>
    </div>
</div>
    
    <!-- Modal para Nuevo/Editar Registro de Nómina -->
    <div class="modal fade" id="modalNomina" tabindex="-1" aria-labelledby="modalNominaLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalNominaLabel">Nuevo Registro</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="formNomina" action="../includes/procesar_nomina.php" method="POST">
                    <div class="modal-body">
                    <div class="mb-3">
    <label for="empleado" class="form-label">Empleado</label>
    <?php if ($_SESSION['perfil'] === 'administrador'): ?>
        <select class="form-select" id="empleado" name="empleado" required>
            <option value="">Seleccione un empleado</option>
            <?php foreach ($empleados as $emp): ?>
                <option value="<?php echo $emp['cedula']; ?>"><?php echo $emp['nombre']; ?></option>
            <?php endforeach; ?>
        </select>
    <?php else: ?>
        <input type="text" class="form-control" id="empleadoNombre" value="<?php echo $_SESSION['nombre']; ?>" readonly>
        <input type="hidden" id="empleado" name="empleado" value="<?php echo $_SESSION['cedula']; ?>">
    <?php endif; ?>
</div>
                        <div class="mb-3">
                            <label for="referencia" class="form-label">Referencia</label>
                            <select class="form-select" id="referencia" name="id_referencia" required>
                                <option value="">Seleccione una referencia</option>
                                <?php foreach ($referencias as $referencia): ?>
                                    <option value="<?php echo $referencia['id']; ?>" data-cantidad="<?php echo $referencia['cantidad']; ?>"><?php echo $referencia['referencia']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="tallas" class="form-label">Tallas</label>
                            <div id="tallasContainer" class="row">
                                <!-- Las tallas se cargarán aquí dinámicamente -->
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="totalTallas" class="form-label">Total Tallas</label>
                            <input type="number" class="form-control" id="totalTallas" name="totalTallas" readonly>
                        </div>
                        <div class="mb-3">
                            <label for="cantidad" class="form-label">Cantidad</label>
                            <input type="number" class="form-control" id="cantidad" name="cantidad" readonly>
                        </div>
                        <div class="mb-3">
                            <label for="operacion" class="form-label">Operación</label>
                            <select class="form-select" id="operacion" name="id_operacion" required>
                                <option value="">Seleccione una operación</option>
                                <?php foreach ($operaciones as $operacion): ?>
                                    <option value="<?php echo $operacion['id']; ?>" data-costo="<?php echo $operacion['costo']; ?>"><?php echo $operacion['nombre_operacion']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="costo" class="form-label">Costo</label>
                            <input type="number" class="form-control" id="costo" name="costo" readonly>
                        </div>
                        <div class="mb-3">
                            <label for="subtotal" class="form-label">Subtotal</label>
                            <input type="text" class="form-control" id="subtotal" name="subtotal" readonly>
                        </div>
                        <input type="hidden" id="semana" name="semana" value="<?php echo $semanaActual; ?>">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="button" class="btn btn-primary" id="agregarRegistro">Agregar Registro</button>
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
    function actualizarRangoSemana() {
        const now = new Date();
        const onejan = new Date(now.getFullYear(), 0, 1);
        const week = Math.ceil((((now - onejan) / 86400000) + onejan.getDay() + 1) / 7);
        
        const monday = new Date(now);
        monday.setDate(monday.getDate() - monday.getDay() + 1);
        
        const sunday = new Date(monday);
        sunday.setDate(monday.getDate() + 6);
        
        const formatoFecha = { day: '2-digit', month: '2-digit', year: 'numeric' };
        const rangoFechas = `${monday.toLocaleDateString('es-CO', formatoFecha)} - ${sunday.toLocaleDateString('es-CO', formatoFecha)}`;
        
        $('#rangoSemana').text(rangoFechas);
    }

    actualizarRangoSemana();
    

    $('#tablaNomina').DataTable({
        language: {
            url: '../assets/i18n/es-ES.json'
        },
        order: [[0, 'desc']],
        responsive: true
    });

    cargarRegistros();

    // Establecer el empleado logueado al abrir el modal
    $('#modalNomina').on('show.bs.modal', function () {
        const empleadoNombre = '<?php echo $_SESSION["nombre"]; ?>';
        $('#empleadoNombre').val(empleadoNombre);
    });

    $('#referencia').on('change', function() {
    const referenciaId = $(this).val();
    if (!referenciaId) {
        $('#tallasContainer').html('');
        $('#totalTallas').val('');
        $('#cantidad').val('');
        return;
    }

    const cantidad = $(this).find('option:selected').data('cantidad');
    $('#cantidad').val(cantidad || 0);

    $.ajax({
        url: '../includes/obtener_tallas.php',
        type: 'GET',
        data: { id: referenciaId },
        dataType: 'json',
        success: function(response) {
            if (response.success && response.tallas) {
                let tallasHtml = '';
                Object.entries(response.tallas).forEach(([talla, cantidad]) => {
                    if (cantidad > 0) {
                        tallasHtml += `
                            <div class="col-md-3 mb-2">
                                <div class="form-check">
                                    <input class="form-check-input talla-checkbox" 
                                           type="checkbox" 
                                           value="${cantidad}" 
                                           id="talla${talla}" 
                                           data-talla="${talla}">
                                    <label class="form-check-label" for="talla${talla}">
                                        Talla ${talla}: ${cantidad}
                                    </label>
                                </div>
                            </div>
                        `;
                    }
                });

                $('#tallasContainer').html(tallasHtml || '<div class="col-12"><p class="text-muted">No hay tallas disponibles</p></div>');
                calcularTotalTallas();
            } else {
                console.error('Error en la respuesta:', response);
                $('#tallasContainer').html('<div class="col-12"><p class="text-danger">Error al cargar las tallas</p></div>');
                alert('Error: ' + (response.message || 'No se pudieron cargar las tallas'));
            }
        },
        error: function(xhr, status, error) {
            console.error('Error AJAX:', {xhr, status, error});
            $('#tallasContainer').html('<div class="col-12"><p class="text-danger">Error de conexión al servidor</p></div>');
            alert('Error al obtener las tallas. Por favor, intente nuevamente.');
        }
    });
});

// Manejador de eventos para los checkboxes de tallas
$(document).on('change', '.talla-checkbox', calcularTotalTallas);

// Manejador de eventos para el cambio de operación
$('#operacion').on('change', function() {
    const selectedOption = $(this).find('option:selected');
    const costo = selectedOption.data('costo') || 0;
    $('#costo').val(costo);
    calcularSubtotal();
});

/**
 * Calcula el total de tallas seleccionadas y actualiza los campos relacionados
 */
function calcularTotalTallas() {
    let totalTallas = 0;
    
    $('.talla-checkbox:checked').each(function() {
        const valor = parseInt($(this).val());
        if (!isNaN(valor)) {
            totalTallas += valor;
        }
    });

    $('#totalTallas').val(totalTallas);
    calcularSubtotal();
}

/**
 * Calcula el subtotal basado en las tallas o cantidad y el costo
 */
function calcularSubtotal() {
    const totalTallas = parseInt($('#totalTallas').val()) || 0;
    const cantidad = parseInt($('#cantidad').val()) || 0;
    const costo = parseFloat($('#costo').val()) || 0;
    
    let subtotal = 0;
    if (totalTallas > 0) {
        subtotal = totalTallas * costo;
    } else {
        subtotal = cantidad * costo;
    }

    // Formatear el subtotal como moneda colombiana
    const subtotalFormateado = new Intl.NumberFormat('es-CO', {
        style: 'currency',
        currency: 'COP',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    }).format(subtotal);

    $('#subtotal').val(subtotalFormateado);
    // Guardar el valor sin formato para cálculos posteriores
    $('#subtotal').attr('data-valor-real', subtotal);
}

    function calcularSubtotal() {
        const totalTallas = parseInt($('#totalTallas').val()) || 0;
        const cantidad = parseInt($('#cantidad').val()) || 0;
        const costo = parseFloat($('#costo').val()) || 0;
        
        const subtotal = totalTallas > 0 ? totalTallas * costo : cantidad * costo;
        
        $('#subtotal').attr('data-valor-real', subtotal);
        
        $('#subtotal').val(subtotal.toLocaleString('es-CO', {
            style: 'currency',
            currency: 'COP',
            minimumFractionDigits: 0,
            maximumFractionDigits: 0
        }));
        
        calcularTotal();
    }

    function calcularTotal() {
        let total = 0;
        $('#tablaNomina tbody tr').each(function() {
            const subtotalStr = $(this).find('td:eq(9)').attr('data-valor-real') || 
                              $(this).find('td:eq(9)').text().replace(/[^0-9.-]/g, '');
            total += parseFloat(subtotalStr) || 0;
        });
        
        $('#totalSemana').text('Total de la semana: ' + total.toLocaleString('es-CO', {
            style: 'currency',
            currency: 'COP',
            minimumFractionDigits: 0,
            maximumFractionDigits: 0
        }));
    }

    $('#agregarRegistro').on('click', function() {
        const empleado = $('#empleado').val();
        const referencia = $('#referencia option:selected').text();
        const operacion = $('#operacion option:selected').text();
        const costo_operacion = parseFloat($('#costo').val()) || 0;
        const totalTallas = $('#totalTallas').val();
        const cantidad = $('#cantidad').val();
        const tallas = $('.talla-checkbox:checked').map(function() {
            return `Talla ${$(this).data('talla')}: ${$(this).val()}`;
        }).get().join(', ');
        
        const subtotal = parseFloat($('#subtotal').attr('data-valor-real'));
        const semana = $('#semana').val();

        if (!empleado || !referencia || !operacion || (!totalTallas && !cantidad) || !subtotal || costo_operacion <= 0) {
            alert('Por favor complete todos los campos antes de agregar el registro.');
            return;
        }

        $.ajax({
            url: '../includes/procesar_nomina.php',
            type: 'POST',
            data: {
                empleado,
                referencia,
                operacion,
                costo_operacion,
                cantidad,
                totalTallas,
                tallas,
                subtotal,
                semana
            },
            success: function(response) {
                if (response.success) {
                    cargarRegistros();
                    $('#formNomina')[0].reset();
                    $('#tallasContainer').html('');
                    $('#modalNomina').modal('hide');
                    alert('Registro agregado exitosamente.');
                } else {
                    alert('Error al agregar el registro: ' + response.message);
                }
            },
            error: function() {
                alert('Error al agregar el registro.');
            }
        });
    });

    

// Modificar la función cargarRegistros para aceptar filtros
function cargarRegistros(filtros = {}) {
    $.ajax({
        url: '../includes/obtener_registros_nomina.php',
        type: 'GET',
        data: filtros,
        dataType: 'json',
        success: function(response) {
            if (response.success && response.data) {
                const tbody = $('#tablaNomina tbody');
                tbody.empty();
                
                let totalSemana = 0;
                let contador = 1;
                
                response.data.forEach(registro => {
                    const fecha = new Date(registro.fecha).toLocaleDateString();
                    const subtotal = parseFloat(registro.subtotal);
                    totalSemana += subtotal;
                    
                    const nuevaFila = `
                        <tr>
                            <td>${contador++}</td>
                            <td>${fecha}</td>
                            <td>${registro.empleado || ''}</td>
                            <td>${registro.referencia || ''}</td>
                            <td>${registro.operacion || ''}</td>
                            <td>${parseFloat(registro.costo_operacion).toLocaleString('es-CO', {
                                style: 'currency',
                                currency: 'COP',
                                minimumFractionDigits: 0,
                                maximumFractionDigits: 0
                            })}</td>
                            <td>${registro.cantidad || 0}</td>
                            <td>${registro.total_tallas || 0}</td>
                            <td>${registro.tallas || ''}</td>
                            <td data-valor-real="${subtotal}">${subtotal.toLocaleString('es-CO', { 
                                style: 'currency', 
                                currency: 'COP',
                                minimumFractionDigits: 0,
                                maximumFractionDigits: 0
                            })}</td>
                            <td>
                                <button class="btn btn-sm btn-danger" onclick="eliminarRegistro(${registro.id})">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
                        </tr>
                    `;
                    tbody.append(nuevaFila);
                });
                
                $('#totalSemana').text('Total filtrado: ' + totalSemana.toLocaleString('es-CO', {
                    style: 'currency',
                    currency: 'COP',
                    minimumFractionDigits: 0,
                    maximumFractionDigits: 0
                }));
            }
        },
        error: function(jqXHR, textStatus, errorThrown) {
            console.error('Error:', textStatus, errorThrown);
            alert('Error al cargar los registros.');
        }
    });
}
$('#aplicarFiltros').on('click', function() {
    const filtros = {
        mes: $('#filtroMes').val(),
        semana: $('#filtroSemana').val(),
        empleado: $('#filtroEmpleado').val()
    };
    cargarRegistros(filtros);
});

// Agregar un botón para limpiar filtros
$('#limpiarFiltros').on('click', function() {
    $('#filtroMes').val('');
    $('#filtroSemana').val('');
    $('#filtroEmpleado').val('');
    cargarRegistros();
});

// Cargar registros iniciales
$(document).ready(function() {
    cargarRegistros();
});


function eliminarRegistro(button, id) {
    if (confirm('¿Está seguro de que desea eliminar este registro?')) {
        $.ajax({
            url: '../includes/eliminar_registro_nomina.php',
            type: 'POST',
            data: { id: id },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $(button).closest('tr').remove();
                    location.reload();
                    alert('Registro eliminado exitosamente.');
                } else {
                    alert('Error al eliminar el registro: ' + response.error);
                }
            },
            error: function(xhr, status, error) {
                console.error('Error:', error);
                alert('Error al intentar eliminar el registro.');
            }
        });
    }

     // Función para verificar operación duplicada
     async function verificarOperacionDuplicada(id_referencia, id_operacion) {
        try {
            const response = await $.ajax({
                url: '../includes/verificar_operacion.php',
                type: 'POST',
                data: {
                    id_referencia: id_referencia,
                    id_operacion: id_operacion
                },
                dataType: 'json'
            });
            return response;
        } catch (error) {
            console.error('Error al verificar operación:', error);
            return { success: false, message: 'Error al verificar operación' };
        }
    }

    // Modificar el evento click del botón agregarRegistro
    $('#agregarRegistro').on('click', async function() {
        const empleado = $('#empleado').val();
        const referencia = $('#referencia').val();
        const operacion = $('#operacion').val();
        const costo_operacion = parseFloat($('#costo').val()) || 0;
        const totalTallas = $('#totalTallas').val();
        const cantidad = $('#cantidad').val();
        const tallas = $('.talla-checkbox:checked').map(function() {
            return `Talla ${$(this).data('talla')}: ${$(this).val()}`;
        }).get().join(', ');
        
        const subtotal = parseFloat($('#subtotal').attr('data-valor-real'));
        const semana = $('#semana').val();

        // Validaciones básicas
        if (!empleado || !referencia || !operacion || (!totalTallas && !cantidad) || !subtotal || costo_operacion <= 0) {
            alert('Por favor complete todos los campos antes de agregar el registro.');
            return;
        }

        // Verificar operación duplicada
        const verificacion = await verificarOperacionDuplicada(referencia, operacion);
        if (!verificacion.success) {
            alert(verificacion.message);
            return;
        }

        // Si pasa todas las validaciones, proceder con el registro
        $.ajax({
            url: '../includes/procesar_nomina.php',
            type: 'POST',
            data: {
                empleado,
                referencia,
                operacion,
                costo_operacion,
                cantidad,
                totalTallas,
                tallas,
                subtotal,
                semana
            },
            success: function(response) {
                if (response.success) {
                    cargarRegistros();
                    $('#formNomina')[0].reset();
                    $('#tallasContainer').html('');
                    $('#modalNomina').modal('hide');
                    alert('Registro agregado exitosamente.');
                } else {
                    alert('Error al agregar el registro: ' + response.message);
                }
            },
            error: function() {
                alert('Error al agregar el registro.');
            }
        });
    });
}


// Manejador para guardar novedad
$('#guardarNovedad').on('click', function() {
        const empleado = $('#novedadEmpleado').val();
        const referencia = $('#novedadReferencia').val();
        const nota = $('#novedadNota').val();
        
        if (!empleado || !referencia || !nota) {
            alert('Por favor complete todos los campos.');
            return;
        }

        $.ajax({
            url: '../includes/procesar_novedad.php',
            type: 'POST',
            data: {
                empleado: empleado,
                referencia: referencia,
                nota: nota,
                semana: $('#semana').val()
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('#formNovedad')[0].reset();
                    $('#modalNovedad').modal('hide');
                    alert('Novedad registrada exitosamente.');
                    cargarRegistros(); // Recargar la tabla
                } else {
                    alert('Error al registrar la novedad: ' + response.message);
                }
            },
            error: function() {
                alert('Error al procesar la novedad.');
            }
        });
    });
});



</script>
</body>
</html>