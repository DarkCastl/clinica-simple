<?php
// menu_lateral.php - VERSIÓN MEJORADA Y MODERNA
// Este archivo se incluye en todas las páginas que necesiten el menú

// Verificar si hay una sesión activa
$usuario_actual = isset($_SESSION['usuario']) ? $_SESSION['usuario'] : 'Usuario';
$inicial_usuario = strtoupper(substr($usuario_actual, 0, 1));

// Determinar página actual para resaltar menú
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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3a0ca3;
            --accent-color: #4cc9f0;
            --sidebar-bg: #1e1e2d;
            --sidebar-text: #a1a5b7;
            --sidebar-active: #2a2a3c;
            --sidebar-hover: #252533;
            --sidebar-width: 280px;
            --sidebar-collapsed: 80px;
            --border-radius: 12px;
            --transition-speed: 0.3s;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            color: #333;
            min-height: 100vh;
            transition: all var(--transition-speed) ease;
            overflow-x: hidden;
        }
        
        /* SIDEBAR ELEGANTE */
        #sidebar {
            width: var(--sidebar-width);
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            background: var(--sidebar-bg);
            color: var(--sidebar-text);
            transition: all var(--transition-speed) cubic-bezier(0.4, 0, 0.2, 1);
            z-index: 1000;
            border-right: 1px solid rgba(255, 255, 255, 0.05);
            box-shadow: 4px 0 25px rgba(0, 0, 0, 0.1);
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        
        .sidebar-collapsed #sidebar {
            width: var(--sidebar-collapsed);
        }
        
        /* HEADER DEL SIDEBAR */
        .sidebar-header {
            padding: 1.8rem 1.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            background: rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
        }
        
        .logo {
            display: flex;
            align-items: center;
            gap: 15px;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .logo-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
            flex-shrink: 0;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(67, 97, 238, 0.3);
        }
        
        .logo-text {
            color: white;
            font-weight: 700;
            font-size: 1.3rem;
            letter-spacing: -0.5px;
            transition: opacity var(--transition-speed) ease;
            white-space: nowrap;
            overflow: hidden;
        }
        
        .sidebar-collapsed .logo-text {
            opacity: 0;
            width: 0;
        }
        
        .logo-tagline {
            font-size: 0.75rem;
            color: var(--accent-color);
            font-weight: 500;
            margin-top: 2px;
            opacity: 0.8;
            transition: opacity var(--transition-speed) ease;
        }
        
        .sidebar-collapsed .logo-tagline {
            opacity: 0;
        }
        
        /* MENÚ PRINCIPAL */
        .sidebar-menu {
            padding: 1.5rem 0;
            flex: 1;
            overflow-y: auto;
            scrollbar-width: thin;
            scrollbar-color: rgba(255, 255, 255, 0.1) transparent;
        }
        
        .sidebar-menu::-webkit-scrollbar {
            width: 4px;
        }
        
        .sidebar-menu::-webkit-scrollbar-track {
            background: transparent;
        }
        
        .sidebar-menu::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
        }
        
        .menu-section {
            padding: 0 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .menu-section-title {
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #6c757d;
            margin-bottom: 0.8rem;
            font-weight: 600;
            transition: opacity var(--transition-speed) ease;
            white-space: nowrap;
        }
        
        .sidebar-collapsed .menu-section-title {
            opacity: 0;
        }
        
        .menu-item {
            display: flex;
            align-items: center;
            padding: 0.9rem 1rem;
            color: var(--sidebar-text);
            text-decoration: none;
            transition: all 0.2s ease;
            border-radius: var(--border-radius);
            margin-bottom: 0.3rem;
            position: relative;
            overflow: hidden;
            white-space: nowrap;
        }
        
        .menu-item::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            height: 100%;
            width: 3px;
            background: var(--primary-color);
            transform: scaleY(0);
            transition: transform 0.2s ease;
        }
        
        .menu-item:hover {
            background: var(--sidebar-hover);
            color: white;
            transform: translateX(5px);
        }
        
        .menu-item:hover::before {
            transform: scaleY(1);
        }
        
        .menu-item.active {
            background: var(--sidebar-active);
            color: white;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
        
        .menu-item.active::before {
            transform: scaleY(1);
        }
        
        .menu-item.active .menu-icon {
            color: var(--accent-color);
            transform: scale(1.1);
        }
        
        .menu-icon {
            font-size: 1.2rem;
            width: 35px;
            height: 35px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            transition: all 0.3s ease;
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.05);
        }
        
        .menu-text {
            margin-left: 12px;
            font-weight: 500;
            font-size: 0.95rem;
            transition: opacity var(--transition-speed) ease;
            flex-grow: 1;
        }
        
        .sidebar-collapsed .menu-text {
            opacity: 0;
            width: 0;
        }
        
        .menu-badge {
            background: var(--primary-color);
            color: white;
            font-size: 0.7rem;
            padding: 0.2rem 0.5rem;
            border-radius: 20px;
            font-weight: 600;
            animation: pulse 2s infinite;
        }
        
        .sidebar-collapsed .menu-badge {
            display: none;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }
        
        /* TOGGLE BUTTON - MODERNO */
        #sidebarToggle {
            position: fixed;
            top: 1.5rem;
            left: 1.5rem;
            z-index: 1001;
            background: var(--primary-color);
            border: none;
            color: white;
            width: 45px;
            height: 45px;
            border-radius: 50%;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 15px rgba(67, 97, 238, 0.4);
        }
        
        #sidebarToggle:hover {
            background: var(--secondary-color);
            transform: rotate(90deg) scale(1.1);
            box-shadow: 0 6px 20px rgba(67, 97, 238, 0.6);
        }
        
        .sidebar-collapsed #sidebarToggle {
            left: 1.5rem;
            transform: rotate(180deg);
        }
        
        .sidebar-collapsed #sidebarToggle:hover {
            transform: rotate(180deg) scale(1.1);
        }
        
        /* USER INFO - ELEGANTE */
        .user-sidebar-info {
            padding: 1.2rem 1.5rem;
            border-top: 1px solid rgba(255, 255, 255, 0.05);
            background: rgba(0, 0, 0, 0.15);
            backdrop-filter: blur(10px);
            transition: all var(--transition-speed) ease;
        }
        
        .user-container {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .user-avatar-sidebar {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 1.1rem;
            flex-shrink: 0;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
            transition: all 0.3s ease;
        }
        
        .user-avatar-sidebar:hover {
            transform: scale(1.05) rotate(5deg);
        }
        
        .user-details {
            flex-grow: 1;
            transition: opacity var(--transition-speed) ease;
            overflow: hidden;
        }
        
        .user-name {
            color: white;
            font-weight: 600;
            font-size: 0.9rem;
            margin-bottom: 2px;
        }
        
        .user-role {
            color: var(--accent-color);
            font-size: 0.75rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .user-role i {
            font-size: 0.7rem;
        }
        
        .sidebar-collapsed .user-details {
            opacity: 0;
            width: 0;
        }
        
        .logout-btn {
            background: rgba(255, 255, 255, 0.1);
            border: none;
            width: 35px;
            height: 35px;
            border-radius: 8px;
            color: var(--sidebar-text);
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
            flex-shrink: 0;
        }
        
        .logout-btn:hover {
            background: rgba(231, 76, 60, 0.2);
            color: #e74c3c;
            transform: translateY(-2px);
        }
        
        /* CONTENIDO PRINCIPAL */
        #main-content {
            margin-left: var(--sidebar-width);
            padding: 1.5rem;
            transition: all var(--transition-speed) cubic-bezier(0.4, 0, 0.2, 1);
            min-height: 100vh;
        }
        
        .sidebar-collapsed #main-content {
            margin-left: var(--sidebar-collapsed);
        }
        
        /* RESPONSIVE DESIGN */
        @media (max-width: 992px) {
            :root {
                --sidebar-width: 250px;
            }
            
            #sidebarToggle {
                top: 1rem;
                left: 1rem;
                width: 40px;
                height: 40px;
            }
        }
        
        @media (max-width: 768px) {
            #sidebar {
                transform: translateX(-100%);
                box-shadow: none;
            }
            
            #sidebar.mobile-open {
                transform: translateX(0);
                box-shadow: 4px 0 25px rgba(0, 0, 0, 0.2);
            }
            
            .sidebar-collapsed #sidebar {
                transform: translateX(-100%);
            }
            
            #main-content {
                margin-left: 0 !important;
                padding: 1rem;
            }
            
            #sidebarToggle {
                display: flex !important;
            }
            
            .overlay {
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0, 0, 0, 0.5);
                z-index: 999;
                display: none;
                backdrop-filter: blur(3px);
            }
            
            .overlay.active {
                display: block;
            }
        }
        
        /* ANIMACIONES */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .menu-item {
            animation: fadeIn 0.3s ease forwards;
            opacity: 0;
        }
        
        .menu-item:nth-child(1) { animation-delay: 0.1s; }
        .menu-item:nth-child(2) { animation-delay: 0.15s; }
        .menu-item:nth-child(3) { animation-delay: 0.2s; }
        .menu-item:nth-child(4) { animation-delay: 0.25s; }
        .menu-item:nth-child(5) { animation-delay: 0.3s; }
        .menu-item:nth-child(6) { animation-delay: 0.35s; }
        
        /* TOOLTIP PARA SIDEBAR COLAPSADO */
        .menu-item .tooltip {
            position: absolute;
            left: calc(var(--sidebar-collapsed) + 10px);
            background: var(--sidebar-bg);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            font-size: 0.9rem;
            white-space: nowrap;
            opacity: 0;
            visibility: hidden;
            transition: all 0.2s ease;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            z-index: 1002;
            pointer-events: none;
        }
        
        .sidebar-collapsed .menu-item:hover .tooltip {
            opacity: 1;
            visibility: visible;
            transform: translateX(0);
        }
        
        .menu-item .tooltip::before {
            content: '';
            position: absolute;
            left: -5px;
            top: 50%;
            transform: translateY(-50%);
            border-right: 5px solid var(--sidebar-bg);
            border-top: 5px solid transparent;
            border-bottom: 5px solid transparent;
        }
    </style>
</head>
<body>
    <!-- OVERLAY PARA MÓVIL -->
    <div class="overlay" id="overlay"></div>
    
    <!-- BOTÓN TOGGLE SIDEBAR -->
    <button id="sidebarToggle">
        <i class="bi bi-list"></i>
    </button>
    
    <!-- SIDEBAR ELEGANTE -->
    <nav id="sidebar">
        <!-- HEADER -->
        <div class="sidebar-header">
            <a href="dashboard.php" class="logo">
                <div class="logo-icon">
                    <i class="bi bi-heart-pulse"></i>
                </div>
                <div>
                    <div class="logo-text">Clínica Simple</div>
                    <div class="logo-tagline">Salud & Tecnología</div>
                </div>
            </a>
        </div>
        
        <!-- MENÚ PRINCIPAL -->
        <div class="sidebar-menu">
            <!-- SECCIÓN PRINCIPAL -->
            <div class="menu-section">
                <div class="menu-section-title">PRINCIPAL</div>
                
                <a href="dashboard.php" class="menu-item <?php echo $pagina_actual == 'dashboard.php' ? 'active' : ''; ?>">
                    <div class="menu-icon">
                        <i class="bi bi-speedometer2"></i>
                    </div>
                    <span class="menu-text">Dashboard</span>
                    <span class="tooltip">Dashboard</span>
                </a>
                
                <a href="pacientes.php" class="menu-item <?php echo $pagina_actual == 'pacientes.php' ? 'active' : ''; ?>">
                    <div class="menu-icon">
                        <i class="bi bi-people-fill"></i>
                    </div>
                    <span class="menu-text">Pacientes</span>
                    <span class="menu-badge" id="badge-pacientes"><?php echo getTotalPacientes(); ?></span>
                    <span class="tooltip">Pacientes</span>
                </a>
                
                <a href="historial.php" class="menu-item <?php echo $pagina_actual == 'historial.php' ? 'active' : ''; ?>">
                    <div class="menu-icon">
                        <i class="bi bi-file-medical-fill"></i>
                    </div>
                    <span class="menu-text">Historial</span>
                    <span class="menu-badge" id="badge-historial"><?php echo getTotalConsultas(); ?></span>
                    <span class="tooltip">Historial Médico</span>
                </a>
            </div>
            
            <!-- SECCIÓN GESTIÓN -->
            <div class="menu-section">
                <div class="menu-section-title">GESTIÓN</div>
                
                <a href="agenda.php" class="menu-item <?php echo $pagina_actual == 'agenda.php' ? 'active' : ''; ?>">
                    <div class="menu-icon">
                        <i class="bi bi-calendar-week-fill"></i>
                    </div>
                    <span class="menu-text">Agenda</span>
                    <span class="menu-badge" id="badge-agenda"><?php echo getCitasHoy(); ?></span>
                    <span class="tooltip">Agenda de Citas</span>
                </a>
                
                <a href="estadisticas.php" class="menu-item <?php echo $pagina_actual == 'estadisticas.php' ? 'active' : ''; ?>">
                    <div class="menu-icon">
                        <i class="bi bi-graph-up-arrow"></i>
                    </div>
                    <span class="menu-text">Estadísticas</span>
                    <span class="tooltip">Estadísticas</span>
                </a>
            </div>
            
            <!-- SECCIÓN SISTEMA -->
            <div class="menu-section">
                <div class="menu-section-title">SISTEMA</div>
                
                <a href="configuracion.php" class="menu-item <?php echo $pagina_actual == 'configuracion.php' ? 'active' : ''; ?>">
                    <div class="menu-icon">
                        <i class="bi bi-gear-fill"></i>
                    </div>
                    <span class="menu-text">Configuración</span>
                    <span class="tooltip">Configuración</span>
                </a>
                
                <a href="reportes.php" class="menu-item <?php echo $pagina_actual == 'reportes.php' ? 'active' : ''; ?>">
                    <div class="menu-icon">
                        <i class="bi bi-printer-fill"></i>
                    </div>
                    <span class="menu-text">Reportes</span>
                    <span class="tooltip">Reportes</span>
                </a>
                
                <a href="ayuda.php" class="menu-item <?php echo $pagina_actual == 'ayuda.php' ? 'active' : ''; ?>">
                    <div class="menu-icon">
                        <i class="bi bi-question-circle-fill"></i>
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
                    <div class="user-role">
                        <i class="bi bi-shield-check"></i> Administrador
                    </div>
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
        // FUNCIONES PARA OBTENER DATOS (simuladas - implementa en PHP)
        function getTotalPacientes() {
            return '0'; // Reemplazar con PHP
        }
        
        function getTotalConsultas() {
            return '0'; // Reemplazar con PHP
        }
        
        function getCitasHoy() {
            return '0'; // Reemplazar con PHP
        }
        
        // TOGGLE SIDEBAR
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebar = document.getElementById('sidebar');
        const mainContent = document.getElementById('main-content');
        const body = document.body;
        const overlay = document.getElementById('overlay');
        
        // Toggle sidebar
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
        
        // Cerrar sidebar en móvil al hacer clic fuera
        overlay.addEventListener('click', () => {
            sidebar.classList.remove('mobile-open');
            overlay.classList.remove('active');
            document.body.style.overflow = '';
        });
        
        // Cargar preferencia de sidebar
        if (localStorage.getItem('sidebarCollapsed') === 'true' && window.innerWidth > 768) {
            body.classList.add('sidebar-collapsed');
        }
        
        // AUTO-HIDE SIDEBAR EN MÓVIL
        function handleResize() {
            if (window.innerWidth <= 768) {
                body.classList.remove('sidebar-collapsed');
                sidebar.classList.remove('mobile-open');
                overlay.classList.remove('active');
            } else {
                sidebar.classList.add('mobile-open');
                overlay.classList.remove('active');
            }
        }
        
        window.addEventListener('resize', handleResize);
        handleResize();
        
        // ANIMACIONES EN HOVER
        document.querySelectorAll('.menu-item').forEach(item => {
            item.addEventListener('mouseenter', function() {
                if (!body.classList.contains('sidebar-collapsed') || window.innerWidth <= 768) {
                    this.style.transform = 'translateX(5px)';
                }
            });
            
            item.addEventListener('mouseleave', function() {
                this.style.transform = 'translateX(0)';
            });
        });
        
        // ACTUALIZAR BADGES DINÁMICAMENTE
        function updateBadges() {
            // Aquí irían llamadas AJAX para actualizar los badges
            // Por ahora son valores estáticos
            document.getElementById('badge-pacientes').textContent = getTotalPacientes();
            document.getElementById('badge-historial').textContent = getTotalConsultas();
            document.getElementById('badge-agenda').textContent = getCitasHoy();
        }
        
        // Actualizar badges cada 30 segundos
        setInterval(updateBadges, 30000);
        
        // Efecto de onda en botones
        document.querySelectorAll('.menu-item, .logout-btn').forEach(button => {
            button.addEventListener('click', function(e) {
                const ripple = document.createElement('span');
                const rect = this.getBoundingClientRect();
                const size = Math.max(rect.width, rect.height);
                const x = e.clientX - rect.left - size / 2;
                const y = e.clientY - rect.top - size / 2;
                
                ripple.style.cssText = `
                    position: absolute;
                    border-radius: 50%;
                    background: rgba(255, 255, 255, 0.7);
                    transform: scale(0);
                    animation: ripple-animation 0.6s linear;
                    width: ${size}px;
                    height: ${size}px;
                    top: ${y}px;
                    left: ${x}px;
                    pointer-events: none;
                `;
                
                this.appendChild(ripple);
                
                setTimeout(() => {
                    ripple.remove();
                }, 600);
            });
        });
        
        // Agregar estilo para ripple animation
        const style = document.createElement('style');
        style.textContent = `
            @keyframes ripple-animation {
                to {
                    transform: scale(4);
                    opacity: 0;
                }
            }
        `;
        document.head.appendChild(style);
        
        // Detectar clics en el contenido principal para cerrar sidebar en móvil
        mainContent.addEventListener('click', () => {
            if (window.innerWidth <= 768 && sidebar.classList.contains('mobile-open')) {
                sidebar.classList.remove('mobile-open');
                overlay.classList.remove('active');
                document.body.style.overflow = '';
            }
        });
        
        // Inicializar badges
        updateBadges();
    </script>
    
    <?php
    // Funciones PHP para los badges (agregar al inicio del archivo o en config.php)
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
        $result = $conexion->query("SELECT COUNT(*) as total FROM agenda WHERE fecha = CURDATE() AND estado = 'pendiente'");
        $row = $result->fetch_assoc();
        return $row['total'] ?? 0;
    }
    ?>
</body>
</html>