<?php
// dashboard.php - PANEL DE CONTROL PRINCIPAL
session_start();
require_once 'config.php';

// Verificar sesión
if (!isset($_SESSION['logueado']) || $_SESSION['logueado'] !== true) {
    header('Location: index.php');
    exit;
}

// Obtener estadísticas para el dashboard
$total_pacientes = $conexion->query("SELECT COUNT(*) as total FROM pacientes")->fetch_assoc()['total'];
$total_consultas = $conexion->query("SELECT COUNT(*) as total FROM historial")->fetch_assoc()['total'];
$pacientes_hoy = $conexion->query("SELECT COUNT(*) as total FROM pacientes WHERE DATE(fecha_registro) = CURDATE()")->fetch_assoc()['total'];
$consultas_hoy = $conexion->query("SELECT COUNT(*) as total FROM historial WHERE fecha = CURDATE()")->fetch_assoc()['total'];

// Obtener últimas actividades
$actividades = $conexion->query("
    SELECT 'Paciente registrado' as tipo, nombre, fecha_registro as fecha 
    FROM pacientes 
    UNION ALL
    SELECT 'Consulta registrada' as tipo, '' as nombre, fecha as fecha 
    FROM historial
    ORDER BY fecha DESC LIMIT 5
");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Sistema Clínico</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    
    <!-- Estilos adicionales para dashboard -->
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --success-color: #2ecc71;
            --warning-color: #f1c40f;
            --info-color: #3498db;
        }
        
        body {
            background-color: #f8fafc;
            font-family: 'Segoe UI', system-ui, sans-serif;
        }
        
        /* CONTENIDO PRINCIPAL */
        #main-content {
            padding: 20px;
            transition: all 0.3s ease;
        }
        
        /* STAT CARDS */
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 3px 15px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s ease;
            border-top: 4px solid;
            height: 100%;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 1rem;
            color: white;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-color);
            line-height: 1;
        }
        
        /* WELCOME CARD */
        .welcome-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.2);
        }
        
        /* QUICK ACTIONS */
        .quick-action-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            border: 1px solid #eef2f7;
            transition: all 0.3s ease;
            height: 100%;
        }
        
        .quick-action-card:hover {
            border-color: var(--secondary-color);
            transform: translateY(-3px);
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
        }
        
        .action-icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            color: white;
            margin-bottom: 1rem;
        }
        
        /* ACTIVITY LIST */
        .activity-item {
            padding: 1rem;
            border-bottom: 1px solid #f1f5f9;
            transition: background 0.2s ease;
        }
        
        .activity-item:hover {
            background: #f8fafc;
        }
        
        .activity-item:last-child {
            border-bottom: none;
        }
        
        /* MODULE CARDS */
        .module-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            text-align: center;
            border: 1px solid #eef2f7;
            transition: all 0.3s ease;
            height: 100%;
        }
        
        .module-card:hover {
            border-color: var(--secondary-color);
            transform: translateY(-3px);
        }
        
        .module-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
            margin: 0 auto 1rem;
        }
        
        /* TOP BAR */
        .top-bar {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--secondary-color), #9b59b6);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
        }
        
        /* COLORES */
        .bg-primary { background-color: #3498db !important; }
        .bg-success { background-color: #2ecc71 !important; }
        .bg-warning { background-color: #f1c40f !important; }
        .bg-info { background-color: #3498db !important; }
        .bg-purple { background-color: #9b59b6 !important; }
        
        .border-primary { border-top-color: #3498db !important; }
        .border-success { border-top-color: #2ecc71 !important; }
        .border-warning { border-top-color: #f1c40f !important; }
        .border-info { border-top-color: #3498db !important; }
        
        .text-primary { color: #3498db !important; }
        .text-success { color: #2ecc71 !important; }
        .text-warning { color: #f1c40f !important; }
        .text-info { color: #3498db !important; }
        
        /* Estilos para resultados de búsqueda */
        #resultadosBusqueda {
            max-height: 300px;
            overflow-y: auto;
            padding: 10px;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            background: #f8f9fa;
        }
        
        #resultadosBusqueda .result-card {
            border: none;
            border-left: 3px solid #3498db;
            transition: all 0.2s;
            margin-bottom: 10px;
        }
        
        #resultadosBusqueda .result-card:hover {
            transform: translateX(3px);
            background: #e8f4fd;
        }
        
        .paciente-info {
            font-size: 0.9rem;
        }
        
        /* Estilo para mensajes de error/éxito */
        .alert-result {
            padding: 10px 15px;
            margin: 0;
        }
    </style>
</head>
<body>
    <!-- INCLUIR EL MENÚ LATERAL -->
    <?php include 'menu_lateral.php'; ?>
    
    <!-- CONTENIDO PRINCIPAL -->
    <div id="main-content">
        <!-- TOP BAR -->
        <div class="top-bar">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-1">Panel de Control</h1>
                    <p class="text-muted small mb-0">
                        <i class="bi bi-calendar3 me-1"></i>
                        <?php echo date('d/m/Y'); ?> • 
                        <span id="liveClock"><?php echo date('H:i:s'); ?></span>
                    </p>
                </div>
                
                <div class="d-flex align-items-center gap-3">
                    <div class="dropdown">
                        <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <i class="bi bi-bell"></i>
                            <span class="badge bg-danger">3</span>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end">
                            <h6 class="dropdown-header">Notificaciones</h6>
                            <a class="dropdown-item" href="#">
                                <i class="bi bi-person-plus text-primary me-2"></i>
                                Nuevo paciente registrado
                            </a>
                            <a class="dropdown-item" href="#">
                                <i class="bi bi-calendar-check text-success me-2"></i>
                                Cita programada para hoy
                            </a>
                            <a class="dropdown-item" href="#">
                                <i class="bi bi-exclamation-triangle text-warning me-2"></i>
                                Recordatorio: Actualizar historial
                            </a>
                        </div>
                    </div>
                    
                    <div class="dropdown">
                        <button class="btn btn-light border rounded-pill d-flex align-items-center" type="button" data-bs-toggle="dropdown">
                            <div class="user-avatar me-2">
                                <?php echo strtoupper(substr($_SESSION['usuario'], 0, 1)); ?>
                            </div>
                            <span class="me-2"><?php echo $_SESSION['usuario']; ?></span>
                            <i class="bi bi-chevron-down"></i>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end">
                            <h6 class="dropdown-header">Mi Cuenta</h6>
                            <a class="dropdown-item" href="perfil.php">
                                <i class="bi bi-person me-2"></i> Mi Perfil
                            </a>
                            <a class="dropdown-item" href="configuracion.php">
                                <i class="bi bi-gear me-2"></i> Configuración
                            </a>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item text-danger" href="logout.php">
                                <i class="bi bi-box-arrow-right me-2"></i> Cerrar Sesión
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <?php
        // Mensaje logout
        if (isset($_GET['msg']) && $_GET['msg'] == 'sesion_cerrada') {
            echo '<div class="alert alert-info alert-dismissible fade show mb-4" role="alert">
                <i class="bi bi-info-circle"></i> Sesión cerrada correctamente.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>';
        }
        ?>
        
        <!-- WELCOME CARD -->
        <div class="welcome-card">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h2 class="mb-3">👋 ¡Hola, <?php echo $_SESSION['usuario']; ?>!</h2>
                    <p class="mb-0">Bienvenido al sistema de gestión clínica. Todo está funcionando correctamente.</p>
                </div>
                <div class="col-md-4 text-md-end">
                    <div class="bg-white text-dark d-inline-block px-4 py-2 rounded-pill">
                        <i class="bi bi-activity me-2"></i>
                        Sistema Activo
                    </div>
                </div>
            </div>
        </div>
        
        <!-- STATS CARDS -->
        <div class="row g-4 mb-4">
            <div class="col-xl-3 col-md-6">
                <div class="stat-card border-primary">
                    <div class="stat-icon bg-primary mb-3">
                        <i class="bi bi-people"></i>
                    </div>
                    <div class="stat-number"><?php echo $total_pacientes; ?></div>
                    <div class="fw-medium mb-1">Pacientes Totales</div>
                    <small class="text-muted d-block">
                        <i class="bi bi-plus-circle me-1"></i> <?php echo $pacientes_hoy; ?> registrados hoy
                    </small>
                </div>
            </div>
            
            <div class="col-xl-3 col-md-6">
                <div class="stat-card border-success">
                    <div class="stat-icon bg-success mb-3">
                        <i class="bi bi-file-medical"></i>
                    </div>
                    <div class="stat-number"><?php echo $total_consultas; ?></div>
                    <div class="fw-medium mb-1">Consultas Totales</div>
                    <small class="text-muted d-block">
                        <i class="bi bi-plus-circle me-1"></i> <?php echo $consultas_hoy; ?> consultas hoy
                    </small>
                </div>
            </div>
            
            <div class="col-xl-3 col-md-6">
                <div class="stat-card border-warning">
                    <div class="stat-icon bg-warning mb-3">
                        <i class="bi bi-calendar-check"></i>
                    </div>
                    <div class="stat-number">0</div>
                    <div class="fw-medium mb-1">Citas Programadas</div>
                    <small class="text-muted d-block">
                        <i class="bi bi-calendar me-1"></i> Agenda disponible
                    </small>
                </div>
            </div>
            
            <div class="col-xl-3 col-md-6">
                <div class="stat-card border-info">
                    <div class="stat-icon bg-info mb-3">
                        <i class="bi bi-activity"></i>
                    </div>
                    <div class="stat-number"><?php echo $total_pacientes + $total_consultas; ?></div>
                    <div class="fw-medium mb-1">Actividad Total</div>
                    <small class="text-muted d-block">
                        <i class="bi bi-graph-up me-1"></i> Todos los registros
                    </small>
                </div>
            </div>
        </div>
        
        <!-- QUICK ACTIONS Y MÓDULOS -->
        <div class="row g-4">
            <!-- QUICK ACTIONS -->
            <div class="col-lg-8">
                <h4 class="mb-3"><i class="bi bi-lightning-charge text-warning me-2"></i> Acciones Rápidas</h4>
                
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="quick-action-card">
                            <div class="action-icon bg-primary">
                                <i class="bi bi-person-plus"></i>
                            </div>
                            <h5 class="fw-semibold">Nuevo Paciente</h5>
                            <p class="text-muted small mb-3">Registra un nuevo paciente en el sistema</p>
                            <a href="pacientes.php" class="btn btn-primary w-100">
                                <i class="bi bi-plus-circle me-1"></i> Agregar
                            </a>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="quick-action-card">
                            <div class="action-icon bg-success">
                                <i class="bi bi-file-earmark-plus"></i>
                            </div>
                            <h5 class="fw-semibold">Nueva Consulta</h5>
                            <p class="text-muted small mb-3">Registra una consulta médica</p>
                            <a href="historial.php" class="btn btn-success w-100">
                                <i class="bi bi-plus-circle me-1"></i> Registrar
                            </a>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="quick-action-card">
                            <div class="action-icon bg-info">
                                <i class="bi bi-search"></i>
                            </div>
                            <h5 class="fw-semibold">Buscar Paciente</h5>
                            <p class="text-muted small mb-3">Encuentra pacientes rápidamente</p>
                            <button class="btn btn-info w-100" data-bs-toggle="modal" data-bs-target="#buscarModal">
                                <i class="bi bi-search me-1"></i> Buscar
                            </button>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="quick-action-card">
                            <div class="action-icon bg-purple">
                                <i class="bi bi-printer"></i>
                            </div>
                            <h5 class="fw-semibold">Generar Reportes</h5>
                            <p class="text-muted small mb-3">Crea reportes y estadísticas</p>
                            <a href="estadisticas.php" class="btn btn-outline-primary w-100">
                                <i class="bi bi-graph-up me-1"></i> Ver Reportes
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- ACTIVIDAD RECIENTE -->
                <div class="card border-0 shadow-sm mt-4">
                    <div class="card-body">
                        <h5 class="card-title mb-3">
                            <i class="bi bi-clock-history me-2"></i> Actividad Reciente
                        </h5>
                        
                        <div class="activity-list">
                            <?php if ($actividades->num_rows > 0): ?>
                                <?php while($act = $actividades->fetch_assoc()): ?>
                                    <?php
                                    $icon = strpos($act['tipo'], 'Paciente') !== false ? 'bi-person' : 'bi-file-medical';
                                    $color = strpos($act['tipo'], 'Paciente') !== false ? 'text-primary' : 'text-success';
                                    $time = date('H:i', strtotime($act['fecha']));
                                    ?>
                                    <div class="activity-item">
                                        <div class="d-flex align-items-center">
                                            <div class="me-3">
                                                <i class="bi <?php echo $icon; ?> fs-5 <?php echo $color; ?>"></i>
                                            </div>
                                            <div class="flex-grow-1">
                                                <div class="fw-medium"><?php echo $act['tipo']; ?></div>
                                                <?php if(!empty($act['nombre'])): ?>
                                                <small class="text-muted"><?php echo htmlspecialchars($act['nombre']); ?></small>
                                                <?php endif; ?>
                                            </div>
                                            <div class="text-muted">
                                                <small><?php echo $time; ?></small>
                                            </div>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <div class="text-center py-4">
                                    <i class="bi bi-inbox fs-1 text-muted"></i>
                                    <p class="text-muted mt-2 mb-0">No hay actividad reciente</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- MÓDULOS DEL SISTEMA -->
            <div class="col-lg-4">
                <h4 class="mb-3"><i class="bi bi-grid-3x3-gap text-primary me-2"></i> Módulos</h4>
                
                <div class="row g-3">
                    <div class="col-12">
                        <div class="module-card">
                            <div class="module-icon bg-primary">
                                <i class="bi bi-people"></i>
                            </div>
                            <h5 class="fw-semibold">Pacientes</h5>
                            <p class="text-muted small mb-3">Gestión completa de pacientes</p>
                            <a href="pacientes.php" class="btn btn-outline-primary w-100">Acceder</a>
                        </div>
                    </div>
                    
                    <div class="col-12">
                        <div class="module-card">
                            <div class="module-icon bg-success">
                                <i class="bi bi-file-medical"></i>
                            </div>
                            <h5 class="fw-semibold">Historial</h5>
                            <p class="text-muted small mb-3">Registro de consultas médicas</p>
                            <a href="historial.php" class="btn btn-outline-success w-100">Acceder</a>
                        </div>
                    </div>
                    
                    <div class="col-12">
                        <div class="module-card">
                            <div class="module-icon bg-warning">
                                <i class="bi bi-calendar"></i>
                            </div>
                            <h5 class="fw-semibold">Agenda</h5>
                            <p class="text-muted small mb-3">Citas programadas</p>
                            <a href="agenda.php" class="btn btn-outline-warning w-100">Acceder</a>
                        </div>
                    </div>
                    
                    <div class="col-12">
                        <div class="module-card">
                            <div class="module-icon bg-info">
                                <i class="bi bi-graph-up"></i>
                            </div>
                            <h5 class="fw-semibold">Estadísticas</h5>
                            <p class="text-muted small mb-3">Reportes y gráficos</p>
                            <a href="estadisticas.php" class="btn btn-outline-info w-100">Acceder</a>
                        </div>
                    </div>
                </div>
                
                <!-- RESUMEN -->
                <div class="card border-0 bg-light mt-4">
                    <div class="card-body">
                        <h6 class="fw-semibold mb-3">
                            <i class="bi bi-info-circle me-2"></i> Resumen del Sistema
                        </h6>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Pacientes registrados:</span>
                            <span class="fw-semibold"><?php echo $total_pacientes; ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Consultas totales:</span>
                            <span class="fw-semibold"><?php echo $total_consultas; ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-3">
                            <span>Actividad hoy:</span>
                            <span class="fw-semibold text-success"><?php echo $pacientes_hoy + $consultas_hoy; ?></span>
                        </div>
                        <div class="alert alert-success small mb-0">
                            <i class="bi bi-check-circle me-1"></i>
                            Sistema funcionando correctamente
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- FOOTER -->
        <footer class="mt-5 pt-4 border-top">
            <div class="row">
                <div class="col-md-6">
                    <h6>Sistema Clínico Simple</h6>
                    <p class="small text-muted">Gestión médica eficiente para clínicas pequeñas.</p>
                </div>
                <div class="col-md-6 text-end">
                    <p class="small text-muted mb-0">
                        Versión 1.0 | <?php echo date('Y'); ?> | 
                        <i class="bi bi-heart-fill text-danger"></i> Hecho con cuidado
                    </p>
                </div>
            </div>
        </footer>
    </div>
    
    <!-- MODAL BUSCAR -->
    <div class="modal fade" id="buscarModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-search me-2"></i> Buscar Paciente</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Buscar por:</label>
                        <select class="form-select" id="tipoBusqueda">
                            <option value="nombre">Nombre</option>
                            <option value="dui">DUI</option>
                            <option value="telefono">Teléfono</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <input type="text" class="form-control" id="terminoBusqueda" 
                               placeholder="Escribe para buscar... (mínimo 2 caracteres)">
                    </div>
                    
                    <div id="resultadosBusqueda" style="display: none;"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <button type="button" class="btn btn-primary" onclick="buscarPaciente()">
                        <i class="bi bi-search me-1"></i> Buscar
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // RELOJ EN TIEMPO REAL
        function updateClock() {
            const now = new Date();
            const hours = now.getHours().toString().padStart(2, '0');
            const minutes = now.getMinutes().toString().padStart(2, '0');
            const seconds = now.getSeconds().toString().padStart(2, '0');
            document.getElementById('liveClock').textContent = `${hours}:${minutes}:${seconds}`;
        }
        setInterval(updateClock, 1000);
        
        // BÚSQUEDA AUTOMÁTICA MIENTRAS ESCRIBE
        document.getElementById('terminoBusqueda').addEventListener('input', function(e) {
            const termino = this.value.trim();
            
            if (termino.length >= 2) {
                clearTimeout(window.buscarTimeout);
                window.buscarTimeout = setTimeout(() => {
                    buscarPaciente();
                }, 300);
            } else if (termino.length === 0) {
                document.getElementById('resultadosBusqueda').style.display = 'none';
            }
        });
        
        // BUSCAR AL PRESIONAR ENTER
        document.getElementById('terminoBusqueda').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                buscarPaciente();
            }
        });
        
        // FUNCIÓN PRINCIPAL DE BÚSQUEDA
        function buscarPaciente() {
            const tipo = document.getElementById('tipoBusqueda').value;
            const termino = document.getElementById('terminoBusqueda').value.trim();
            const resultados = document.getElementById('resultadosBusqueda');
            
            if (!termino) {
                resultados.innerHTML = `
                    <div class="alert alert-warning alert-result">
                        <i class="bi bi-exclamation-triangle"></i> 
                        Escribe algo para buscar
                    </div>`;
                resultados.style.display = 'block';
                return;
            }
            
            if (termino.length < 2) {
                resultados.innerHTML = `
                    <div class="alert alert-warning alert-result">
                        <i class="bi bi-exclamation-triangle"></i> 
                        Escribe al menos 2 caracteres
                    </div>`;
                resultados.style.display = 'block';
                return;
            }
            
            // Mostrar animación de carga
            resultados.innerHTML = `
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Buscando...</span>
                    </div>
                    <p class="mt-2 text-muted">Buscando pacientes...</p>
                </div>`;
            resultados.style.display = 'block';
            
            // Hacer petición AJAX
            fetch(`buscar_paciente.php?tipo=${encodeURIComponent(tipo)}&termino=${encodeURIComponent(termino)}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Error en la respuesta del servidor');
                    }
                    return response.json();
                })
                .then(data => {
                    mostrarResultados(data);
                })
                .catch(error => {
                    console.error('Error:', error);
                    resultados.innerHTML = `
                        <div class="alert alert-danger alert-result">
                            <i class="bi bi-x-circle"></i> 
                            Error en la búsqueda: ${error.message}
                        </div>`;
                });
        }
        
        // FUNCIÓN PARA MOSTRAR RESULTADOS
        function mostrarResultados(pacientes) {
            const resultados = document.getElementById('resultadosBusqueda');
            
            // Verificar si es un objeto de error
            if (typeof pacientes === 'object' && pacientes.error) {
                resultados.innerHTML = `
                    <div class="alert alert-danger alert-result">
                        <i class="bi bi-x-circle"></i> 
                        ${pacientes.error}
                    </div>`;
                return;
            }
            
            // Verificar si es un array
            if (!Array.isArray(pacientes)) {
                resultados.innerHTML = `
                    <div class="alert alert-warning alert-result">
                        <i class="bi bi-exclamation-triangle"></i> 
                        Formato de respuesta incorrecto
                    </div>`;
                return;
            }
            
            if (pacientes.length === 0) {
                resultados.innerHTML = `
                    <div class="alert alert-info alert-result">
                        <i class="bi bi-info-circle"></i> 
                        No se encontraron pacientes con esos criterios.
                    </div>`;
                return;
            }
            
            let html = `<h6 class="mb-3">Encontrados: ${pacientes.length} paciente(s)</h6>`;
            
            pacientes.forEach(paciente => {
                const telefono = paciente.telefono ? paciente.telefono : '<span class="text-muted">Sin teléfono</span>';
                const dui = paciente.dui ? paciente.dui : '<span class="text-muted">Sin DUI</span>';
                const email = paciente.email ? paciente.email : '<span class="text-muted">Sin email</span>';
                
                html += `
                    <div class="card result-card">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="flex-grow-1">
                                    <h6 class="mb-1 fw-semibold">${paciente.nombre}</h6>
                                    <div class="paciente-info text-muted">
                                        <div><i class="bi bi-person-badge me-1"></i> ${dui}</div>
                                        <div><i class="bi bi-telephone me-1"></i> ${telefono}</div>
                                        <div><i class="bi bi-envelope me-1"></i> ${email}</div>
                                    </div>
                                </div>
                                <div class="ms-2">
                                    <a href="pacientes.php?accion=ver&id=${paciente.id}" 
                                       class="btn btn-sm btn-primary"
                                       onclick="document.getElementById('buscarModal').querySelector('.btn-close').click()">
                                        <i class="bi bi-eye"></i> Ver
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>`;
            });
            
            // Botón para ver todos los resultados
            html += `
                <div class="text-center mt-3">
                    <a href="pacientes.php" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-list"></i> Ver todos los pacientes
                    </a>
                </div>`;
            
            resultados.innerHTML = html;
        }
        
        // LIMPIAR RESULTADOS AL CERRAR MODAL
        document.getElementById('buscarModal').addEventListener('hidden.bs.modal', function() {
            document.getElementById('terminoBusqueda').value = '';
            document.getElementById('resultadosBusqueda').innerHTML = '';
            document.getElementById('resultadosBusqueda').style.display = 'none';
        });
    </script>
</body>
</html>