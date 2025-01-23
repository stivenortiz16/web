<?php 
$currentTime = date('Y-m-d H:i:s');
$currentUser = 'stivenortiz16';
// Determinar la página actual para el menú activo
$current_page = basename($_SERVER['PHP_SELF'], '.php');

// Verificar el rol del usuario (asumiendo que está almacenado en la sesión)
$isAdmin = isset($_SESSION['rol']) && $_SESSION['rol'] === 'administrador';
?>

<!-- Sidebar -->
<nav id="sidebar" class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
    <div class="position-sticky pt-3">
        <ul class="nav flex-column">
            <?php if ($isAdmin): ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($current_page == 'dashboard') ? 'active' : ''; ?>" href="dashboard.php">
                        <i class="bi bi-house-door"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($current_page == 'empleados') ? 'active' : ''; ?>" href="empleados.php">
                        <i class="bi bi-people"></i> Empleados
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($current_page == 'proveedores') ? 'active' : ''; ?>" href="proveedores.php">
                        <i class="bi bi-building"></i> Proveedores
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($current_page == 'facturas') ? 'active' : ''; ?>" href="facturas.php">
                        <i class="bi bi-receipt"></i> Facturas
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($current_page == 'referencias') ? 'active' : ''; ?>" href="referencias.php">
                        <i class="bi bi-box"></i> Referencias
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($current_page == 'reportes') ? 'active' : ''; ?>" href="reportes.php">
                        <i class="bi bi-file-text"></i> Reportes
                    </a>
                </li>
            <?php endif; ?>
            <!-- Módulo de Nómina visible para todos -->
            <li class="nav-item">
                <a class="nav-link <?php echo ($current_page == 'nomina') ? 'active' : ''; ?>" href="nomina.php">
                    <i class="bi bi-cash"></i> Nómina
                </a>
            </li>
        </ul>
    </div>
</nav>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
    <div class="container-fluid">
        <a class="navbar-brand" href="dashboard.php">Sistema de Nómina</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" 
                aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <?php if ($isAdmin): ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($current_page == 'dashboard') ? 'active' : ''; ?>" href="dashboard.php">
                            <i class="bi bi-speedometer2"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($current_page == 'empleados') ? 'active' : ''; ?>" href="empleados.php">
                            <i class="bi bi-people"></i> Empleados
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($current_page == 'proveedores') ? 'active' : ''; ?>" href="proveedores.php">
                            <i class="bi bi-building"></i> Proveedores
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($current_page == 'referencias') ? 'active' : ''; ?>" href="referencias.php">
                            <i class="bi bi-list-check"></i> Referencias
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($current_page == 'operaciones_posibles') ? 'active' : ''; ?>" href="operaciones_posibles.php">
                            <i class="bi bi-gear"></i> Operaciones
                        </a>
                    </li>
                <?php endif; ?>
                <!-- Módulo de Nómina visible para todos -->
                <li class="nav-item">
                    <a class="nav-link <?php echo ($current_page == 'nomina') ? 'active' : ''; ?>" href="nomina.php">
                        <i class="bi bi-cash"></i> Nómina
                    </a>
                </li>
            </ul>
            
            <div class="d-flex align-items-center text-white me-3">
                <small><i class="bi bi-clock me-2"></i><?php echo $currentTime; ?></small>
            </div>
            <div class="dropdown">
                <a class="nav-link dropdown-toggle text-white" href="#" role="button" id="userDropdown" 
                   data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-person-circle me-1"></i>
                    <?php echo htmlspecialchars($_SESSION['nombre'] ?? 'Usuario'); ?>
                </a>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                    <li><a class="dropdown-item" href="perfil.php">Mi Perfil</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="../includes/logout.php">Cerrar Sesión</a></li>
                </ul>
            </div>
        </div>
    </div>
</nav>