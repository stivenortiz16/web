<?php
// Definir las variables globales
$currentDateTime = '2025-01-15 21:55:52';
$currentUser = 'stivenortiz16';

// Verificar el rol del usuario (asumiendo que está almacenado en la sesión)
$isAdmin = isset($_SESSION['rol']) && $_SESSION['rol'] === 'administrador';

// Determinar la página actual para el menú activo
$current_page = basename($_SERVER['PHP_SELF'], '.php');
?>

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
                    <a class="nav-link <?php echo ($current_page == 'referencias') ? 'active' : ''; ?>" href="referencias.php">
                        <i class="bi bi-box"></i> Referencias
                    </a>
                </li>

                <li class="nav-item">
                        <a class="nav-link <?php echo ($current_page == 'operaciones_posibles') ? 'active' : ''; ?>" href="operaciones_posibles.php">
                            <i class="bi bi-gear"></i> Operaciones
                        </a>
                    </li>

                    <li class="nav-item">
                    <a class="nav-link <?php echo ($current_page == 'facturas') ? 'active' : ''; ?>" href="facturas.php">
                        <i class="bi bi-receipt"></i> Facturas
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($current_page == 'reportes') ? 'active' : ''; ?>" href="reportes.php">
                        <i class="bi bi-file-text"></i> Reportes
                    </a>
                </li>
            <?php endif; ?>
            <!-- Módulo de Nómina visible para todos los usuarios -->
            <li class="nav-item">
                <a class="nav-link <?php echo ($current_page == 'nomina') ? 'active' : ''; ?>" href="nomina.php">
                    <i class="bi bi-cash"></i> Nómina
                </a>
            </li>
        </ul>
    </div>
</nav>