<?php
// menu_lateral.php - COMPONENTE REUTILIZABLE
// Este archivo se incluye en todas las páginas que necesiten el menú

// Verificar si hay una sesión activa
$usuario_actual = isset($_SESSION['usuario']) ? $_SESSION['usuario'] : 'Usuario';
$inicial_usuario = strtoupper(substr($usuario_actual, 0, 1));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema Clínico</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --sidebar-width: 250px;
            --sidebar-collapsed: 70px;
        }
        
        body {
            font-family: 'Segoe UI', system-ui, sans-serif;
            background-color: #f8fafc;
            overflow-x: hidden;
            transition: all 0.3s ease;
        }
        
        /* SIDEBAR FIJO */
        #sidebar {
            width: var(--sidebar-width);
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            background: linear-gradient(180deg, var(--primary-color) 0%, #1a252f 100%);
            color: white;
            transition: all 0.3s ease;
            z-index: 1000;
            box-shadow: 3px 0 15px rgba(0, 0, 0, 0.1);
        }
        
        .sidebar-collapsed #sidebar {
            width: var(--sidebar-collapsed);
        }
        
        .sidebar-header {
            padding: 1.5rem 1rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            text-align: center;
        }
        
        .logo {
            font-size: 1.5rem;
            font-weight: bold;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .logo-text {
            transition: opacity 0.3s ease;
        }
        
        .sidebar-collapsed .logo-text {
            opacity: 0;
            display: none;
        }
        
        /* MENÚ LATERAL */
        .sidebar-menu {
            padding: 1rem 0;
            height: calc(100vh - 180px);
            overflow-y: auto;
        }
        
        .menu-item {
            display: flex;
            align-items: center;
            padding: 0.8rem 1.5rem;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            transition: all 0.2s ease;
            border-left: 3px solid transparent;
            white-space: nowrap;
        }
        
        .menu-item:hover {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            border-left: 3px solid var(--secondary-color);
            text-decoration: none;
        }
        
        .menu-item.active {
            background: rgba(52, 152, 219, 0.2);
            color: white;
            border-left: 3px solid var(--secondary-color);
        }
        
        .menu-icon {
            font-size: 1.2rem;
            width: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        
        .menu-text {
            transition: opacity 0.3s ease;
            margin-left: 10px;
            flex-grow: 1;
        }
        
        .sidebar-collapsed .menu-text {
            opacity: 0;
            display: none;
        }
        
        /* TOGGLE BUTTON */
        #sidebarToggle {
            position: fixed;
            top: 1rem;
            left: 1rem;
            z-index: 1001;
            background: var(--secondary-color);
            border: none;
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .sidebar-collapsed #sidebarToggle {
            left: 1rem;
        }
        
        #sidebarToggle:hover {
            background: #2980b9;
            transform: scale(1.05);
        }
        
        /* CONTENIDO PRINCIPAL */
        #main-content {
            margin-left: var(--sidebar-width);
            padding: 1rem;
            transition: all 0.3s ease;
            min-height: 100vh;
        }
        
        .sidebar-collapsed #main-content {
            margin-left: var(--sidebar-collapsed);
        }
        
        /* USER INFO EN SIDEBAR */
        .user-sidebar-info {
            padding: 1rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            position: absolute;
            bottom: 0;
            width: 100%;
            background: rgba(0, 0, 0, 0.1);
        }
        
        .user-avatar-sidebar {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--secondary-color), #9b59b6);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            flex-shrink: 0;
        }
        
        .user-details {
            transition: opacity 0.3s ease;
            margin-left: 10px;
            overflow: hidden;
        }
        
        .sidebar-collapsed .user-details {
            opacity: 0;
            display: none;
        }
        
        /* RESPONSIVE */
        @media (max-width: 768px) {
            #sidebar {
                margin-left: -250px;
            }
            
            .sidebar-collapsed #sidebar {
                margin-left: 0;
                width: 250px;
            }
            
            #main-content {
                margin-left: 0 !important;
                padding: 1rem;
            }
            
            #sidebarToggle {
                display: block !important;
            }
        }
    </style>
</head>
<body>
    <!-- BOTÓN TOGGLE SIDEBAR -->
    <button id="sidebarToggle">
        <i class="bi bi-list"></i>
    </button>
    
    <!-- SIDEBAR -->
    <nav id="sidebar">
        <div class="sidebar-header">
            <div class="logo">
                <i class="bi bi-heart-pulse"></i>
                <span class="logo-text">Clínica Simple</span>
            </div>
        </div>
        
        <div class="sidebar-menu">
            <a href="dashboard.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                <div class="menu-icon">
                    <i class="bi bi-speedometer2"></i>
                </div>
                <span class="menu-text">Dashboard</span>
            </a>
            
            <a href="pacientes.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'pacientes.php' ? 'active' : ''; ?>">
                <div class="menu-icon">
                    <i class="bi bi-people"></i>
                </div>
                <span class="menu-text">Pacientes</span>
            </a>
            
            <a href="historial.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'historial.php' ? 'active' : ''; ?>">
                <div class="menu-icon">
                    <i class="bi bi-file-medical"></i>
                </div>
                <span class="menu-text">Historial</span>
            </a>
            
            <a href="agenda.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'agenda.php' ? 'active' : ''; ?>">
                <div class="menu-icon">
                    <i class="bi bi-calendar"></i>
                </div>
                <span class="menu-text">Agenda</span>
            </a>
            
            <a href="estadisticas.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'estadisticas.php' ? 'active' : ''; ?>">
                <div class="menu-icon">
                    <i class="bi bi-graph-up"></i>
                </div>
                <span class="menu-text">Estadísticas</span>
            </a>
            
            <a href="configuracion.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'configuracion.php' ? 'active' : ''; ?>">
                <div class="menu-icon">
                    <i class="bi bi-gear"></i>
                </div>
                <span class="menu-text">Configuración</span>
            </a>
        </div>
        
        <!-- INFO DEL USUARIO EN SIDEBAR -->
        <div class="user-sidebar-info">
            <div class="d-flex align-items-center">
                <div class="user-avatar-sidebar">
                    <?php echo $inicial_usuario; ?>
                </div>
                <div class="user-details">
                    <div class="small fw-medium"><?php echo $usuario_actual; ?></div>
                    <div class="extra-small text-white-50">Administrador</div>
                </div>
                <a href="logout.php" class="ms-auto text-white-50" title="Cerrar sesión">
                    <i class="bi bi-box-arrow-right"></i>
                </a>
            </div>
        </div>
    </nav>
    
    <!-- CONTENIDO PRINCIPAL -->
    <div id="main-content">
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // TOGGLE SIDEBAR
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebar = document.getElementById('sidebar');
        const mainContent = document.getElementById('main-content');
        const body = document.body;
        
        sidebarToggle.addEventListener('click', () => {
            body.classList.toggle('sidebar-collapsed');
            localStorage.setItem('sidebarCollapsed', body.classList.contains('sidebar-collapsed'));
        });
        
        // Cargar preferencia de sidebar
        if (localStorage.getItem('sidebarCollapsed') === 'true') {
            body.classList.add('sidebar-collapsed');
        }
        
        // AUTO-HIDE SIDEBAR ON MOBILE
        function checkScreenSize() {
            if (window.innerWidth <= 768) {
                body.classList.add('sidebar-collapsed');
            }
        }
        
        window.addEventListener('resize', checkScreenSize);
        checkScreenSize();
        
        // HIGHLIGHT MENU ITEM ON HOVER
        document.querySelectorAll('.menu-item').forEach(item => {
            item.addEventListener('mouseenter', function() {
                this.style.transform = 'translateX(5px)';
            });
            item.addEventListener('mouseleave', function() {
                this.style.transform = 'translateX(0)';
            });
        });
    </script>
</body>
</html>