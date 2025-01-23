<?php
require_once '../includes/functions.php';
verificarPermiso('empleado');

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$currentDateTime = date('Y-m-d H:i:s'); // Fecha y hora actual dinámicas
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Empleado</title>
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
                        <a class="nav-link" href="nomina.php">Nómina</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
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
        <div class="row">
            <div class="col-12">
                <div class="card mb-4">
                    <div class="card-body">
                        <h2 class="card-title">
                            <i class="bi bi-person-circle"></i> 
                            Bienvenido, <?php echo htmlspecialchars($_SESSION['nombre'] ?? 'Usuario'); ?>
                        </h2>
                        <p class="card-text text-muted">
                            Último acceso: <?php echo htmlspecialchars($currentDateTime); ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sección de Nómina -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title">
                            <i class="bi bi-table"></i> Nómina
                        </h5>
                        <p class="card-text">Accede al módulo de nómina para gestionar tus registros salariales y ver tus comprobantes de pago.</p>
                        <a href="nomina.php" class="btn btn-primary">
                            <i class="bi bi-arrow-right-circle"></i> Ir a Nómina
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-bell"></i> Notificaciones Recientes
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info" role="alert">
                            <i class="bi bi-info-circle"></i> 
                            Bienvenido al sistema de nómina. Aquí podrás ver tus notificaciones importantes.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <footer class="footer mt-auto py-3 bg-light">
        <div class="container text-center">
            <span class="text-muted">Sistema de Nómina &copy; 2025</span>
        </div>
    </footer>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
