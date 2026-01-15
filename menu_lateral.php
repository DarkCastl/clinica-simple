<?php
// menu_lateral.php - VERSIÓN MODERNA Y MINIMALISTA
$usuario_actual = isset($_SESSION['usuario']) ? $_SESSION['usuario'] : 'Usuario';
$inicial_usuario = strtoupper(substr($usuario_actual, 0, 1));
$pagina_actual = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema Clínico</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Inter:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #2563eb;
            --primary-light: #3b82f6;
            --primary-dark: #1d4ed8;
            --secondary: #64748b;
            --success: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
            --sidebar-bg: #ffffff;
            --sidebar-border: #e2e8f0;
            --text-primary: #1e293b;
            --text-secondary: #64748b;
            --hover-bg: #f1f5f9;
            --active-bg: #eff6ff;
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --radius: 12px;
            --transition: all 0.2s ease;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: #f8fafc;
            color: var(--text-primary);
            min-height: 100vh;
            overflow-x: hidden;
        }
        
        /* SIDEBAR MODERNO */
        #sidebar {
            width: 260px;
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            background: var(--sidebar-bg);
            border-right: 1px solid var(--sidebar-border);
            transition: var(--transition);
            z-index: 1000;
            display: flex;
            flex-direction: column;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.05);
        }
        
        .sidebar-collapsed #sidebar {
            width: 80px;
        }
        
        /* HEADER SIMPLIFICADO */
        .sidebar-header {
            padding: 1.5rem 1.25rem;
            border-bottom: 1px solid var(--sidebar-border);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .logo-icon {
            width: 36px;
            height: 36px;
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.1rem;
            flex-shrink: 0;
            transition: var(--transition);
        }
        
        .logo-text {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--text-primary);
            letter-spacing: -0.5px;
            transition: var(--transition);
            white-space: nowrap;
        }
        
        .sidebar-collapsed .logo-text {
            opacity: 0;
            width: 0;
            margin: 0;
        }
        
        .logo-tagline {
            font-size: 0.75rem;
            color: var(--text-secondary);
            font-weight: 400;
            margin-top: 2px;
            transition: var(--transition);
        }
        
        .sidebar-collapsed .logo-tagline {
            opacity: 0;
        }
        
        /* MENÚ PRINCIPAL */
        .sidebar-menu {
            padding: 1rem 0;
            flex: 1;
            overflow-y: auto;
        }
        
        .menu-section {
            padding: 0 1rem;
            margin-bottom: 1.5rem;
        }
        
        .menu-section-title {
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--text-secondary);
            margin-bottom: 0.75rem;
            font-weight: 600;
            transition: var(--transition);
        }
        
        .sidebar-collapsed .menu-section-title {
            opacity: 0;
            height: 0;
            margin: 0;
            overflow: hidden;
        }
        
        .menu-item {
            display: flex;
            align-items: center;
            padding: 0.75rem 1rem;
            color: var(--text-secondary);
            text-decoration: none;
            border-radius: var(--radius);
            margin-bottom: 0.25rem;
            transition: var(--transition);
            position: relative;
            white-space: nowrap;
        }
        
        .menu-item:hover {
            background: var(--hover-bg);
            color: var(--text-primary);
            transform: translateX(3px);
        }
        
        .menu-item.active {
            background: var(--active-bg);
            color: var(--primary);
            font-weight: 500;
        }
        
        .menu-icon {
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            transition: var(--transition);
            flex-shrink: 0;
        }
        
        .menu-item.active .menu-icon {
            color: var(--primary);
        }
        
        .menu-text {
            margin-left: 0.75rem;
            font-size: 0.9rem;
            transition: var(--transition);
            flex-grow: 1;
        }
        
        .sidebar-collapsed .menu-text {
            opacity: 0;
            width: 0;
            margin-left: 0;
        }
        
        .menu-badge {
            background: var(--primary);
            color: white;
            font-size: 0.65rem;
            padding: 0.15rem 0.4rem;
            border-radius: 20px;
            font-weight: 500;
            min-width: 20px;
            text-align: center;
        }
        
        .sidebar-collapsed .menu-badge {
            display: none;
        }
        
        /* TOGGLE BUTTON */
        #sidebarToggle {
            position: fixed;
            top: 1.25rem;
            left: 1.25rem;
            z-index: 1001;
            background: var(--primary);
            border: none;
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 10px;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: var(--shadow);
        }
        
        #sidebarToggle:hover {
            background: var(--primary-dark);
            transform: scale(1.05);
        }
        
        .sidebar-collapsed #sidebarToggle {
            transform: rotate(180deg);
        }
        
        .sidebar-collapsed #sidebarToggle:hover {
            transform: rotate(180deg) scale(1.05);
        }
        
        /* USUARIO */
        .user-sidebar-info {
            padding: 1rem;
            border-top: 1px solid var(--sidebar-border);
            background: var(--sidebar-bg);
        }
        
        .user-container {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .user-avatar-sidebar {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            flex-shrink: 0;
        }
        
        .user-details {
            flex-grow: 1;
            transition: var(--transition);
            overflow: hidden;
        }
        
        .user-name {
            font-size: 0.85rem;
            font-weight: 500;
            color: var(--text-primary);
            margin-bottom: 2px;
        }
        
        .user-role {
            font-size: 0.7rem;
            color: var(--text-secondary);
            font-weight: 400;
        }
        
        .sidebar-collapsed .user-details {
            opacity: 0;
            width: 0;
        }
        
        .logout-btn {
            background: none;
            border: 1px solid var(--sidebar-border);
            width: 32px;
            height: 32px;
            border-radius: 8px;
            color: var(--text-secondary);
            display: flex;
            align-items: center;
            justify-content: center;
            transition: var(--transition);
            flex-shrink: 0;
        }
        
        .logout-btn:hover {
            background: var(--hover-bg);
            border-color: var(--primary);
            color: var(--primary);
        }
        
        /* CONTENIDO PRINCIPAL */
        #main-content {
            margin-left: 260px;
            padding: 2rem;
            transition: var(--transition);
            min-height: 100vh;
        }
        
        .sidebar-collapsed #main-content {
            margin-left: 80px;
        }
        
        /* TOOLTIPS */
        .menu-item .tooltip {
            position: absolute;
            left: calc(100% + 10px);
            background: var(--text-primary);
            color: white;
            padding: 0.5rem 0.75rem;
            border-radius: 6px;
            font-size: 0.8rem;
            white-space: nowrap;
            opacity: 0;
            visibility: hidden;
            transition: var(--transition);
            z-index: 1002;
            pointer-events: none;
        }
        
        .menu-item .tooltip::before {
            content: '';
            position: absolute;
            left: -5px;
            top: 50%;
            transform: translateY(-50%);
            border-right: 5px solid var(--text-primary);
            border-top: 5px solid transparent;
            border-bottom: 5px solid transparent;
        }
        
        .sidebar-collapsed .menu-item:hover .tooltip {
            opacity: 1;
            visibility: visible;
        }
        
        /* RESPONSIVE */
        @media (max-width: 768px) {
            #sidebar {
                transform: translateX(-100%);
                box-shadow: none;
            }
            
            #sidebar.mobile-open {
                transform: translateX(0);
                box-shadow: 4px 0 20px rgba(0, 0, 0, 0.1);
            }
            
            .sidebar-collapsed #sidebar {
                transform: translateX(-100%);
            }
            
            #main-content {
                margin-left: 0 !important;
                padding: 1.5rem;
            }
            
            #sidebarToggle {
                display: flex !important;
                left: 1rem;
                top: 1rem;
            }
            
            .overlay {
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0, 0, 0, 0.3);
                z-index: 999;
                display: none;
                backdrop-filter: blur(2px);
            }
            
            .overlay.active {
                display: block;
            }
        }
        
        /* ANIMACIONES SUAVES */
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(-10px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        
        .menu-item {
            animation: slideIn 0.3s ease forwards;
            animation-delay: calc(var(--i) * 0.05s);
            opacity: 0;
        }
        
        /* BADGE ANIMATION */
        @keyframes badgePulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }
        
        .menu-badge {
            animation: badgePulse 2s infinite;
        }
        
        /* HOVER EFFECTS */
        .menu-item::after {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            height: 100%;
            width: 3px;
            background: var(--primary);
            transform: scaleY(0);
            transition: transform 0.2s ease;
            border-radius: 0 3px 3px 0;
        }
        
        .menu-item:hover::after,
        .menu-item.active::after {
            transform: scaleY(1);
        }
    </style>
</head>
<body>
    <!-- OVERLAY PARA MÓVIL -->
    <div class="overlay" id="overlay"></div>
    
    <!-- BOTÓN TOGGLE SIDEBAR -->
    <button id="sidebarToggle">
        <i class="bi bi-chevron-left"></i>
    </button>
    
    <!-- SIDEBAR MODERNO -->
    <nav id="sidebar">
        <!-- HEADER -->
        <div class="sidebar-header">
            <div class="logo-icon">
                <i class="bi bi-heart"></i>
            </div>
            <div>
                <div class="logo-text">Clínica Simple</div>
                <div class="logo-tagline">Salud & Tecnología</div>
            </div>
        </div>
        
        <!-- MENÚ PRINCIPAL -->
        <div class="sidebar-menu">
            <!-- SECCIÓN PRINCIPAL -->
            <div class="menu-section">
                <div class="menu-section-title">PRINCIPAL</div>
                
                <a href="dashboard.php" class="menu-item <?php echo $pagina_actual == 'dashboard.php' ? 'active' : ''; ?>" style="--i: 1">
                    <div class="menu-icon">
                        <i class="bi bi-house-door"></i>
                    </div>
                    <span class="menu-text">Dashboard</span>
                    <span class="tooltip">Dashboard</span>
                </a>
                
                <a href="pacientes.php" class="menu-item <?php echo $pagina_actual == 'pacientes.php' ? 'active' : ''; ?>" style="--i: 2">
                    <div class="menu-icon">
                        <i class="bi bi-people"></i>
                    </div>
                    <span class="menu-text">Pacientes</span>
                    <span class="menu-badge" id="badge-pacientes"><?php echo getTotalPacientes(); ?></span>
                    <span class="tooltip">Pacientes</span>
                </a>
                
                <a href="historial.php" class="menu-item <?php echo $pagina_actual == 'historial.php' ? 'active' : ''; ?>" style="--i: 3">
                    <div class="menu-icon">
                        <i class="bi bi-file-text"></i>
                    </div>
                    <span class="menu-text">Historial</span>
                    <span class="menu-badge" id="badge-historial"><?php echo getTotalConsultas(); ?></span>
                    <span class="tooltip">Historial Médico</span>
                </a>
                
                <a href="agenda.php" class="menu-item <?php echo $pagina_actual == 'agenda.php' ? 'active' : ''; ?>" style="--i: 4">
                    <div class="menu-icon">
                        <i class="bi bi-calendar-date"></i>
                    </div>
                    <span class="menu-text">Agenda</span>
                    <span class="menu-badge" id="badge-agenda"><?php echo getCitasHoy(); ?></span>
                    <span class="tooltip">Agenda</span>
                </a>
            </div>
            
            <!-- SECCIÓN GESTIÓN -->
            <div class="menu-section">
                <div class="menu-section-title">GESTIÓN</div>
                
                
                    <span class="tooltip">Inventario</span>
                </a>
                
                <a href="estadisticas.php" class="menu-item <?php echo $pagina_actual == 'estadisticas.php' ? 'active' : ''; ?>" style="--i: 6">
                    <div class="menu-icon">
                        <i class="bi bi-bar-chart"></i>
                    </div>
                    <span class="menu-text">Estadísticas</span>
                    <span class="tooltip">Estadísticas</span>
                </a>
                
                <a href="reportes.php" class="menu-item <?php echo $pagina_actual == 'reportes.php' ? 'active' : ''; ?>" style="--i: 7">
                    <div class="menu-icon">
                        <i class="bi bi-printer"></i>
                    </div>
                    <span class="menu-text">Reportes</span>
                    <span class="tooltip">Reportes</span>
                </a>
            </div>
            
            <!-- SECCIÓN SISTEMA -->
            <div class="menu-section">
                <div class="menu-section-title">SISTEMA</div>
                
                <a href="configuracion.php" class="menu-item <?php echo $pagina_actual == 'configuracion.php' ? 'active' : ''; ?>" style="--i: 8">
                    <div class="menu-icon">
                        <i class="bi bi-gear"></i>
                    </div>
                    <span class="menu-text">Configuración</span>
                    <span class="tooltip">Configuración</span>
                </a>
                
                <a href="usuarios.php" class="menu-item <?php echo $pagina_actual == 'usuarios.php' ? 'active' : ''; ?>" style="--i: 9">
                    <div class="menu-icon">
                        <i class="bi bi-person-badge"></i>
                    </div>
                    <span class="menu-text">Usuarios</span>
                    <span class="tooltip">Usuarios</span>
                </a>
                
                <a href="ayuda.php" class="menu-item <?php echo $pagina_actual == 'ayuda.php' ? 'active' : ''; ?>" style="--i: 10">
                    <div class="menu-icon">
                        <i class="bi bi-question-circle"></i>
                    </div>
                    <span class="menu-text">Ayuda</span>
                    <span class="tooltip">Ayuda</span>
                </a>
            </div>
        </div>
        
        <!-- INFO DEL USUARIO -->
        <div class="user-sidebar-info">
            <div class="user-container">
                <div class="user-avatar-sidebar">
                    <?php echo $inicial_usuario; ?>
                </div>
                <div class="user-details">
                    <div class="user-name"><?php echo $usuario_actual; ?></div>
                    <div class="user-role">Administrador</div>
                </div>
                <a href="logout.php" class="logout-btn" title="Cerrar sesión">
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
        const overlay = document.getElementById('overlay');
        
        sidebarToggle.addEventListener('click', (e) => {
            e.stopPropagation();
            if (window.innerWidth <= 768) {
                sidebar.classList.toggle('mobile-open');
                overlay.classList.toggle('active');
                document.body.style.overflow = sidebar.classList.contains('mobile-open') ? 'hidden' : '';
            } else {
                body.classList.toggle('sidebar-collapsed');
                localStorage.setItem('sidebarCollapsed', body.classList.contains('sidebar-collapsed'));
            }
        });
        
        overlay.addEventListener('click', () => {
            sidebar.classList.remove('mobile-open');
            overlay.classList.remove('active');
            document.body.style.overflow = '';
        });
        
        if (localStorage.getItem('sidebarCollapsed') === 'true' && window.innerWidth > 768) {
            body.classList.add('sidebar-collapsed');
        }
        
        function handleResize() {
            if (window.innerWidth <= 768) {
                body.classList.remove('sidebar-collapsed');
                sidebar.classList.remove('mobile-open');
                overlay.classList.remove('active');
            }
        }
        
        window.addEventListener('resize', handleResize);
        handleResize();
        
        // ACTUALIZAR BADGES
        function updateBadges() {
            fetch('ajax/badges.php')
                .then(response => response.json())
                .then(data => {
                    if (data.pacientes !== undefined) {
                        document.getElementById('badge-pacientes').textContent = data.pacientes;
                    }
                    if (data.historial !== undefined) {
                        document.getElementById('badge-historial').textContent = data.historial;
                    }
                    if (data.agenda !== undefined) {
                        document.getElementById('badge-agenda').textContent = data.agenda;
                    }
                })
                .catch(error => console.error('Error al cargar badges:', error));
        }
        
        // Actualizar cada 60 segundos
        setInterval(updateBadges, 60000);
        
        // Efecto hover sutil
        document.querySelectorAll('.menu-item').forEach(item => {
            item.addEventListener('mouseenter', function() {
                this.style.transform = 'translateX(3px)';
            });
            
            item.addEventListener('mouseleave', function() {
                if (!this.classList.contains('active')) {
                    this.style.transform = 'translateX(0)';
                }
            });
        });
        
        // Cerrar sidebar en móvil al hacer clic en el contenido
        mainContent.addEventListener('click', () => {
            if (window.innerWidth <= 768 && sidebar.classList.contains('mobile-open')) {
                sidebar.classList.remove('mobile-open');
                overlay.classList.remove('active');
                document.body.style.overflow = '';
            }
        });
    </script>
    
    <?php
    function getTotalPacientes() {
        global $conexion;
        $result = $conexion->query("SELECT COUNT(*) as total FROM pacientes");
        $row = $result->fetch_assoc();
        return $row['total'] ?? 0;
    }
    
    function getTotalConsultas() {
        global $conexion;
        $result = $conexion->query("SELECT COUNT(*) as total FROM historial");
        $row = $result->fetch_assoc();
        return $row['total'] ?? 0;
    }
    
    function getCitasHoy() {
        global $conexion;
        $result = $conexion->query("SELECT COUNT(*) as total FROM agenda WHERE DATE(fecha) = CURDATE() AND estado = 'pendiente'");
        $row = $result->fetch_assoc();
        return $row['total'] ?? 0;
    }
    ?>
</body>
</html>