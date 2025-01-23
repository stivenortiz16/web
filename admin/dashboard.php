<?php
require_once '../includes/functions.php';
verificarPermiso('administrador');

// Variables globales actualizadas
$currentDateTime = '2025-01-14 14:28:41';

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Administrador</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="#">Sistema de Nómina</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="empleados.php">Empleados</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="proveedores.php">Proveedores</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="referencias.php">Referencias</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="operaciones_posibles.php">Operaciones</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="nomina.php">Nómina</a>
                    </li>
                    
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <span class="nav-link text-light">
                            <i class="bi bi-clock"></i> <?php echo $currentDateTime; ?>
                        </span>
                    </li>
                    <li class="nav-item">
                        <span class="nav-link text-light">
                            <i class="bi bi-person-circle"></i> 
                            <?php echo htmlspecialchars($_SESSION['nombre'] ?? 'Usuario'); ?>
                        </span>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../includes/logout.php">
                            <i class="bi bi-box-arrow-right"></i> Cerrar Sesión
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h2>Bienvenido, <?php echo htmlspecialchars($_SESSION['nombre']); ?></h2>
        
        <div class="row mt-4">
            <!-- Tarjeta de Empleados -->
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title">
                            <i class="bi bi-people-fill"></i> Empleados
                        </h5>
                        <p class="card-text">Gestiona los empleados del sistema.</p>
                        <a href="empleados.php" class="btn btn-primary">
                            <i class="bi bi-person-plus"></i> Ver Empleados
                        </a>
                    </div>
                </div>
            </div>

            <!-- Tarjeta de Proveedores -->
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title">
                            <i class="bi bi-shop"></i> Proveedores
                        </h5>
                        <p class="card-text">Gestiona los proveedores del sistema.</p>
                        <a href="proveedores.php" class="btn btn-primary">
                            <i class="bi bi-building"></i> Ver Proveedores
                        </a>
                    </div>
                </div>
            </div>

            <!-- Tarjeta de Referencias -->
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title">
                            <i class="bi bi-tags-fill"></i> Referencias
                        </h5>
                        <p class="card-text">Gestiona las referencias del sistema.</p>
                        <a href="referencias.php" class="btn btn-primary">
                            <i class="bi bi-tag"></i> Ver Referencias
                        </a>
                    </div>
                </div>
            </div>

            <!-- Tarjeta de Operaciones -->
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title">
                            <i class="bi bi-gear-fill"></i> Operaciones
                        </h5>
                        <p class="card-text">Gestiona las operaciones del sistema.</p>
                        <a href="operaciones_posibles.php" class="btn btn-primary">
                            <i class="bi bi-gear"></i> Ver Operaciones
                        </a>
                    </div>
                </div>
            </div>

            <!-- Nueva Tarjeta de Nómina -->
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title">
                            <i class="bi bi-cash-stack"></i> Nómina
                        </h5>
                        <p class="card-text">Gestiona la nómina y registros de operaciones.</p>
                        <a href="nomina.php" class="btn btn-primary">
                            <i class="bi bi-calculator"></i> Ver Nómina
                        </a>
                    </div>
                </div>
            </div>

            <!-- Tarjeta de Reportes -->
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title">
                            <i class="bi bi-file-earmark-bar-graph"></i> Reportes
                        </h5>
                        <p class="card-text">Visualiza reportes y estadísticas del sistema.</p>
                        <a href="reportes.php" class="btn btn-primary">
                            <i class="bi bi-graph-up"></i> Ver Reportes
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <footer class="footer mt-auto py-3 bg-light">
        <div class="container text-center">
            <span class="text-muted">Sistema de Nómina &copy; 2025 | Usuario: <?php echo htmlspecialchars($_SESSION['nombre'] ?? 'Usuario'); ?></span>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>