<?php
// dashboard.php - PANEL DE CONTROL CON ESTADÍSTICAS REALES
session_start();
require_once 'config.php';

// Verificar sesión
if (!isset($_SESSION['logueado']) || $_SESSION['logueado'] !== true) {
    header('Location: index.php');
    exit;
}

// ========== ESTADÍSTICAS REALES Y ACTUALIZADAS ==========

// 1. TOTAL PACIENTES (exactos)
$total_pacientes = $conexion->query("SELECT COUNT(*) as total FROM pacientes")->fetch_assoc()['total'];

// 2. TOTAL CONSULTAS (del historial)
$total_consultas = $conexion->query("SELECT COUNT(*) as total FROM historial")->fetch_assoc()['total'];

// 3. PACIENTES HOY (fecha_registro puede ser DATE o DATETIME)
$pacientes_hoy_result = $conexion->query("
    SELECT COUNT(*) as total 
    FROM pacientes 
    WHERE DATE(fecha_registro) = CURDATE()
");
$pacientes_hoy = $pacientes_hoy_result ? $pacientes_hoy_result->fetch_assoc()['total'] : 0;

// 4. CONSULTAS HOY (del historial, campo fecha)
$consultas_hoy_result = $conexion->query("
    SELECT COUNT(*) as total 
    FROM historial 
    WHERE DATE(fecha) = CURDATE()
");
$consultas_hoy = $consultas_hoy_result ? $consultas_hoy_result->fetch_assoc()['total'] : 0;

// 5. CITAS HOY (de la agenda, estado pendiente)
$citas_hoy_result = $conexion->query("
    SELECT COUNT(*) as total 
    FROM agenda 
    WHERE DATE(fecha) = CURDATE() 
    AND estado = 'pendiente'
");
$citas_hoy = $citas_hoy_result ? $citas_hoy_result->fetch_assoc()['total'] : 0;

// 6. ESTADÍSTICAS ADICIONALES PARA DASHBOARD

// Consultas por mes (últimos 6 meses) - con verificación
$consultas_mes = $conexion->query("
    SELECT 
        DATE_FORMAT(fecha, '%Y-%m') as mes,
        COUNT(*) as total
    FROM historial 
    WHERE fecha >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(fecha, '%Y-%m')
    ORDER BY mes DESC
    LIMIT 6
") ?: null;

// Pacientes por mes (últimos 6 meses) - con verificación
$pacientes_mes = $conexion->query("
    SELECT 
        DATE_FORMAT(fecha_registro, '%Y-%m') as mes,
        COUNT(*) as total
    FROM pacientes 
    WHERE fecha_registro >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(fecha_registro, '%Y-%m')
    ORDER BY mes DESC
    LIMIT 6
") ?: null;

// Pacientes sin consultas (últimos 30 días) - con verificación
$pacientes_sin_consulta = $conexion->query("
    SELECT p.id, p.nombre, p.dui, p.telefono
    FROM pacientes p
    LEFT JOIN historial h ON p.id = h.paciente_id
    WHERE h.id IS NULL
    ORDER BY p.fecha_registro DESC
    LIMIT 5
") ?: null;

// Consultas recientes (últimas 5) - con verificación
$consultas_recientes = $conexion->query("
    SELECT h.*, p.nombre, p.dui, 
           DATE_FORMAT(h.fecha, '%d/%m/%Y') as fecha_formateada,
           DATE_FORMAT(h.fecha_hora_creacion, '%H:%i') as hora
    FROM historial h
    INNER JOIN pacientes p ON h.paciente_id = p.id
    ORDER BY h.fecha DESC, h.fecha_hora_creacion DESC
    LIMIT 5
") ?: null;

// Citas próximas (próximos 3 días) - con verificación
$citas_proximas = $conexion->query("
    SELECT a.*, p.nombre, p.dui, p.telefono,
           CONCAT(TIME_FORMAT(a.hora, '%H:%i'), ' - ', a.motivo) as descripcion
    FROM agenda a
    LEFT JOIN pacientes p ON a.paciente_id = p.id
    WHERE a.fecha >= CURDATE() 
    AND a.fecha <= DATE_ADD(CURDATE(), INTERVAL 3 DAY)
    AND a.estado = 'pendiente'
    ORDER BY a.fecha ASC, a.hora ASC
    LIMIT 5
") ?: null;

// Edad promedio de pacientes (solo si existe fecha_nacimiento)
$edad_promedio = array('edad_promedio' => 0, 'edad_minima' => 0, 'edad_maxima' => 0);
try {
    $result = $conexion->query("
        SELECT 
            ROUND(AVG(TIMESTAMPDIFF(YEAR, fecha_nacimiento, CURDATE())), 1) as edad_promedio,
            MIN(TIMESTAMPDIFF(YEAR, fecha_nacimiento, CURDATE())) as edad_minima,
            MAX(TIMESTAMPDIFF(YEAR, fecha_nacimiento, CURDATE())) as edad_maxima
        FROM pacientes 
        WHERE fecha_nacimiento IS NOT NULL 
        AND fecha_nacimiento != '0000-00-00'
        AND fecha_nacimiento != ''
    ");
    if ($result && $result->num_rows > 0) {
        $edad_promedio = $result->fetch_assoc();
    }
} catch (Exception $e) {
    // Si no existe fecha_nacimiento, usar valores por defecto
    $edad_promedio = array('edad_promedio' => 0, 'edad_minima' => 0, 'edad_maxima' => 0);
}

// ========== ESTADÍSTICAS PARA GRÁFICOS ==========

// Consultas por día (últimos 7 días)
$consultas_ultimos_7dias = $conexion->query("
    SELECT 
        DATE(fecha) as dia,
        COUNT(*) as total
    FROM historial
    WHERE fecha >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    GROUP BY DATE(fecha)
    ORDER BY dia ASC
") ?: null;

// Pacientes nuevos por día (últimos 7 días)
$pacientes_ultimos_7dias = $conexion->query("
    SELECT 
        DATE(fecha_registro) as dia,
        COUNT(*) as total
    FROM pacientes
    WHERE fecha_registro >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    GROUP BY DATE(fecha_registro)
    ORDER BY dia ASC
") ?: null;

// Obtener actividad reciente combinada
$actividades = $conexion->query("
    (
        SELECT 
            'paciente' as tipo,
            CONCAT('Paciente registrado: ', nombre) as descripcion,
            fecha_registro as fecha,
            id as id_paciente,
            NULL as id_historial
        FROM pacientes
        ORDER BY fecha_registro DESC
        LIMIT 3
    )
    UNION ALL
    (
        SELECT 
            'consulta' as tipo,
            CONCAT('Consulta registrada') as descripcion,
            fecha_hora_creacion as fecha,
            paciente_id as id_paciente,
            id as id_historial
        FROM historial
        ORDER BY fecha_hora_creacion DESC
        LIMIT 3
    )
    UNION ALL
    (
        SELECT 
            'cita' as tipo,
            CONCAT('Cita programada: ', motivo) as descripcion,
            CONCAT(fecha, ' ', hora) as fecha,
            paciente_id as id_paciente,
            NULL as id_historial
        FROM agenda
        WHERE estado = 'pendiente'
        ORDER BY fecha DESC, hora DESC
        LIMIT 3
    )
    ORDER BY fecha DESC
    LIMIT 8
") ?: null;

// Mes actual y anterior para comparativas
$mes_actual = date('Y-m');
$mes_anterior = date('Y-m', strtotime('-1 month'));

// Consultas este mes
$consultas_mes_actual_result = $conexion->query("
    SELECT COUNT(*) as total 
    FROM historial 
    WHERE DATE_FORMAT(fecha, '%Y-%m') = '$mes_actual'
");
$consultas_mes_actual = $consultas_mes_actual_result ? $consultas_mes_actual_result->fetch_assoc()['total'] : 0;

// Consultas mes anterior
$consultas_mes_anterior_result = $conexion->query("
    SELECT COUNT(*) as total 
    FROM historial 
    WHERE DATE_FORMAT(fecha, '%Y-%m') = '$mes_anterior'
");
$consultas_mes_anterior = $consultas_mes_anterior_result ? $consultas_mes_anterior_result->fetch_assoc()['total'] : 0;

// Calcular diferencia porcentual
$diferencia_consultas = 0;
if ($consultas_mes_anterior > 0 && $consultas_mes_actual > 0) {
    $diferencia_consultas = round((($consultas_mes_actual - $consultas_mes_anterior) / $consultas_mes_anterior) * 100, 1);
}

// Pacientes este mes
$pacientes_mes_actual_result = $conexion->query("
    SELECT COUNT(*) as total 
    FROM pacientes 
    WHERE DATE_FORMAT(fecha_registro, '%Y-%m') = '$mes_actual'
");
$pacientes_mes_actual = $pacientes_mes_actual_result ? $pacientes_mes_actual_result->fetch_assoc()['total'] : 0;

// Pacientes mes anterior
$pacientes_mes_anterior_result = $conexion->query("
    SELECT COUNT(*) as total 
    FROM pacientes 
    WHERE DATE_FORMAT(fecha_registro, '%Y-%m') = '$mes_anterior'
");
$pacientes_mes_anterior = $pacientes_mes_anterior_result ? $pacientes_mes_anterior_result->fetch_assoc()['total'] : 0;

// Calcular diferencia porcentual
$diferencia_pacientes = 0;
if ($pacientes_mes_anterior > 0 && $pacientes_mes_actual > 0) {
    $diferencia_pacientes = round((($pacientes_mes_actual - $pacientes_mes_anterior) / $pacientes_mes_anterior) * 100, 1);
}

// Contar pacientes sin consultas
$pacientes_sin_consulta_count = 0;
if ($pacientes_sin_consulta) {
    $pacientes_sin_consulta_count = $pacientes_sin_consulta->num_rows;
}
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
    
    <!-- Chart.js para gráficos -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
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
        
        /* TOP BAR MEJORADA CON LOGO */
        .top-bar {
            background: white;
            padding: 1.2rem 1.5rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            box-shadow: 0 3px 15px rgba(0, 0, 0, 0.08);
            border: 1px solid #eef2f7;
        }
        
        .logo-container {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .logo-img {
            height: 45px;
            width: auto;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            object-fit: contain;
            background: white;
            padding: 3px;
        }
        
        .logo-img:hover {
            transform: scale(1.05);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.15);
        }
        
        .logo-placeholder {
            width: 45px;
            height: 45px;
            background: linear-gradient(135deg, #3498db, #9b59b6);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
        }
        
        .search-container {
            position: relative;
            max-width: 350px;
        }
        
        .search-container .input-group {
            border-radius: 25px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            border: 1px solid #dee2e6;
        }
        
        .search-container .input-group:focus-within {
            box-shadow: 0 4px 15px rgba(52, 152, 219, 0.2);
            border-color: #3498db;
        }
        
        .search-container input {
            border: none;
            padding-left: 15px;
        }
        
        .search-container input:focus {
            box-shadow: none;
            outline: none;
        }
        
        .search-btn {
            border-top-left-radius: 0;
            border-bottom-left-radius: 0;
            padding-left: 20px;
            padding-right: 20px;
        }
        
        .search-results {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 10px;
            margin-top: 5px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            z-index: 1000;
            display: none;
            max-height: 400px;
            overflow-y: auto;
        }
        
        .search-result-item {
            padding: 12px 15px;
            border-bottom: 1px solid #f1f5f9;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .search-result-item:hover {
            background: #f8fafc;
            transform: translateX(3px);
        }
        
        .search-result-item:last-child {
            border-bottom: none;
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
            position: relative;
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
        
        .stat-trend {
            position: absolute;
            top: 1rem;
            right: 1rem;
            font-size: 0.85rem;
            font-weight: 600;
            padding: 2px 8px;
            border-radius: 12px;
        }
        
        .trend-up {
            background: rgba(46, 204, 113, 0.1);
            color: #27ae60;
        }
        
        .trend-down {
            background: rgba(231, 76, 60, 0.1);
            color: #c0392b;
        }
        
        /* CHART CONTAINERS */
        .chart-container {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 3px 15px rgba(0, 0, 0, 0.05);
            margin-bottom: 1.5rem;
            height: 100%;
        }
        
        .chart-container canvas {
            max-height: 300px;
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
        
        /* Responsive */
        @media (max-width: 992px) {
            .search-container {
                max-width: 250px;
            }
        }
        
        @media (max-width: 768px) {
            .top-bar {
                flex-direction: column;
                gap: 15px;
            }
            
            .logo-container {
                width: 100%;
                justify-content: center;
                margin-bottom: 10px;
            }
            
            .search-container {
                max-width: 100%;
                order: 2;
            }
            
            .user-actions {
                order: 3;
                width: 100%;
                justify-content: center;
                margin-top: 10px;
            }
        }
    </style>
</head>
<body>
    <!-- INCLUIR EL MENÚ LATERAL -->
    <?php include 'menu_lateral.php'; ?>
    
    <!-- CONTENIDO PRINCIPAL -->
    <div id="main-content">
        <!-- TOP BAR MEJORADA CON LOGO Y BUSCADOR -->
        <div class="top-bar">
            <div class="d-flex justify-content-between align-items-center flex-wrap">
                <!-- LOGO Y TÍTULO -->
                <div class="logo-container">
                    <?php 
                    $logo_path = 'img/logo-clinica.png';
                    if (file_exists($logo_path)): 
                    ?>
                    <img src="<?php echo $logo_path; ?>" alt="Logo Clínica Simple" class="logo-img">
                    <?php else: ?>
                    <div class="logo-placeholder">
                        <i class="bi bi-heart-pulse"></i>
                    </div>
                    <?php endif; ?>
                    
                    <div>
                        <h1 class="h4 mb-1">Panel de Control</h1>
                        <p class="text-muted small mb-0">
                            <i class="bi bi-calendar3 me-1"></i>
                            <?php echo date('d/m/Y'); ?> • 
                            <span id="liveClock"><?php echo date('H:i:s'); ?></span>
                        </p>
                    </div>
                </div>
                
                <!-- BUSCADOR GLOBAL -->
                <div class="search-container">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-0">
                            <i class="bi bi-search text-muted"></i>
                        </span>
                        <input type="text" class="form-control border-0" 
                               id="globalSearch" 
                               placeholder="Buscar paciente, consulta, cita...">
                        <button class="btn btn-primary search-btn" type="button" onclick="buscarGlobal()">
                            <i class="bi bi-search"></i>
                        </button>
                    </div>
                    
                    <!-- RESULTADOS DE BÚSQUEDA -->
                    <div class="search-results" id="globalSearchResults">
                        <!-- Los resultados aparecerán aquí -->
                    </div>
                </div>
                
                <!-- NOTIFICACIONES Y PERFIL -->
                <div class="d-flex align-items-center gap-3 user-actions">
                    <div class="dropdown">
                        <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <i class="bi bi-bell"></i>
                            <span class="badge bg-danger"><?php echo $citas_hoy; ?></span>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end">
                            <h6 class="dropdown-header">Notificaciones Hoy</h6>
                            
                            <?php if ($pacientes_hoy > 0): ?>
                            <a class="dropdown-item" href="pacientes.php">
                                <i class="bi bi-person-plus text-primary me-2"></i>
                                <?php echo $pacientes_hoy; ?> nuevo(s) paciente(s)
                            </a>
                            <?php endif; ?>
                            
                            <?php if ($consultas_hoy > 0): ?>
                            <a class="dropdown-item" href="historial.php">
                                <i class="bi bi-file-medical text-success me-2"></i>
                                <?php echo $consultas_hoy; ?> consulta(s) registrada(s)
                            </a>
                            <?php endif; ?>
                            
                            <?php if ($citas_hoy > 0): ?>
                            <a class="dropdown-item" href="agenda.php">
                                <i class="bi bi-calendar-check text-warning me-2"></i>
                                <?php echo $citas_hoy; ?> cita(s) pendiente(s)
                            </a>
                            <?php endif; ?>
                            
                            <?php if ($pacientes_hoy == 0 && $consultas_hoy == 0 && $citas_hoy == 0): ?>
                            <a class="dropdown-item text-muted">
                                <i class="bi bi-check-circle text-success me-2"></i>
                                Sin notificaciones nuevas
                            </a>
                            <?php endif; ?>
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
                    <p class="mb-0">
                        <?php
                        $hora_actual = date('H');
                        if ($hora_actual < 12) {
                            echo "¡Buenos días! ";
                        } elseif ($hora_actual < 19) {
                            echo "¡Buenas tardes! ";
                        } else {
                            echo "¡Buenas noches! ";
                        }
                        ?>
                        Hoy es <?php echo date('l, d \\d\\e F \\d\\e Y'); ?>
                    </p>
                </div>
                <div class="col-md-4 text-md-end">
                    <div class="bg-white text-dark d-inline-block px-4 py-2 rounded-pill">
                        <i class="bi bi-activity me-2"></i>
                        <?php echo $total_pacientes + $total_consultas; ?> registros activos
                    </div>
                </div>
            </div>
        </div>
        
        <!-- STATS CARDS MEJORADAS -->
        <div class="row g-4 mb-4">
            <!-- PACIENTES TOTALES -->
            <div class="col-xl-3 col-md-6">
                <div class="stat-card border-primary">
                    <?php if ($diferencia_pacientes != 0): ?>
                    <span class="stat-trend <?php echo $diferencia_pacientes > 0 ? 'trend-up' : 'trend-down'; ?>">
                        <i class="bi bi-<?php echo $diferencia_pacientes > 0 ? 'arrow-up' : 'arrow-down'; ?>"></i>
                        <?php echo abs($diferencia_pacientes); ?>%
                    </span>
                    <?php endif; ?>
                    
                    <div class="stat-icon bg-primary mb-3">
                        <i class="bi bi-people"></i>
                    </div>
                    <div class="stat-number"><?php echo $total_pacientes; ?></div>
                    <div class="fw-medium mb-1">Pacientes Totales</div>
                    <small class="text-muted d-block">
                        <i class="bi bi-plus-circle me-1"></i> 
                        <?php echo $pacientes_hoy; ?> hoy • 
                        <?php echo $pacientes_mes_actual; ?> este mes
                    </small>
                </div>
            </div>
            
            <!-- CONSULTAS TOTALES -->
            <div class="col-xl-3 col-md-6">
                <div class="stat-card border-success">
                    <?php if ($diferencia_consultas != 0): ?>
                    <span class="stat-trend <?php echo $diferencia_consultas > 0 ? 'trend-up' : 'trend-down'; ?>">
                        <i class="bi bi-<?php echo $diferencia_consultas > 0 ? 'arrow-up' : 'arrow-down'; ?>"></i>
                        <?php echo abs($diferencia_consultas); ?>%
                    </span>
                    <?php endif; ?>
                    
                    <div class="stat-icon bg-success mb-3">
                        <i class="bi bi-file-medical"></i>
                    </div>
                    <div class="stat-number"><?php echo $total_consultas; ?></div>
                    <div class="fw-medium mb-1">Consultas Totales</div>
                    <small class="text-muted d-block">
                        <i class="bi bi-plus-circle me-1"></i> 
                        <?php echo $consultas_hoy; ?> hoy • 
                        <?php echo $consultas_mes_actual; ?> este mes
                    </small>
                </div>
            </div>
            
            <!-- CITAS HOY -->
            <div class="col-xl-3 col-md-6">
                <div class="stat-card border-warning">
                    <div class="stat-icon bg-warning mb-3">
                        <i class="bi bi-calendar-check"></i>
                    </div>
                    <div class="stat-number"><?php echo $citas_hoy; ?></div>
                    <div class="fw-medium mb-1">Citas Hoy</div>
                    <small class="text-muted d-block">
                        <i class="bi bi-calendar me-1"></i> Pendientes para hoy
                        <?php if ($citas_proximas && $citas_proximas->num_rows > 0): ?>
                        <br><span class="text-success">
                            <i class="bi bi-arrow-right"></i> <?php echo $citas_proximas->num_rows; ?> próximas
                        </span>
                        <?php endif; ?>
                    </small>
                </div>
            </div>
            
            <!-- ACTIVIDAD HOY -->
            <div class="col-xl-3 col-md-6">
                <div class="stat-card border-info">
                    <div class="stat-icon bg-info mb-3">
                        <i class="bi bi-activity"></i>
                    </div>
                    <div class="stat-number"><?php echo $pacientes_hoy + $consultas_hoy; ?></div>
                    <div class="fw-medium mb-1">Actividad Hoy</div>
                    <small class="text-muted d-block">
                        <i class="bi bi-graph-up me-1"></i> 
                        <?php echo $pacientes_hoy; ?> pacientes + <?php echo $consultas_hoy; ?> consultas
                    </small>
                </div>
            </div>
        </div>
        
        <!-- GRÁFICOS Y RESUMEN -->
        <div class="row g-4 mb-4">
            <!-- GRÁFICO CONSULTAS ÚLTIMOS 7 DÍAS -->
            <div class="col-lg-8">
                <div class="chart-container">
                    <h5 class="fw-semibold mb-3">
                        <i class="bi bi-graph-up text-primary me-2"></i>
                        Actividad Últimos 7 Días
                    </h5>
                    <canvas id="activityChart"></canvas>
                </div>
            </div>
            
            <!-- RESUMEN RÁPIDO -->
            <div class="col-lg-4">
                <div class="chart-container">
                    <h5 class="fw-semibold mb-3">
                        <i class="bi bi-info-circle text-info me-2"></i>
                        Resumen Rápido
                    </h5>
                    
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span>Pacientes totales:</span>
                            <span class="fw-bold"><?php echo $total_pacientes; ?></span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span>Consultas totales:</span>
                            <span class="fw-bold"><?php echo $total_consultas; ?></span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span>Citas hoy:</span>
                            <span class="fw-bold text-warning"><?php echo $citas_hoy; ?></span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span>Edad promedio:</span>
                            <span class="fw-bold">
                                <?php echo $edad_promedio['edad_promedio'] > 0 ? $edad_promedio['edad_promedio'] . ' años' : 'N/D'; ?>
                            </span>
                        </div>
                    </div>
                    
                    <?php if ($pacientes_sin_consulta_count > 0): ?>
                    <div class="alert alert-warning small mb-0">
                        <i class="bi bi-exclamation-triangle me-1"></i>
                        <?php echo $pacientes_sin_consulta_count; ?> paciente(s) sin consultas
                    </div>
                    <?php else: ?>
                    <div class="alert alert-success small mb-0">
                        <i class="bi bi-check-circle me-1"></i>
                        Todos los pacientes tienen al menos una consulta
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- ACCIONES RÁPIDAS Y ACTIVIDAD -->
        <div class="row g-4">
            <!-- ACCIONES RÁPIDAS -->
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
                            <div class="action-icon bg-warning">
                                <i class="bi bi-calendar-plus"></i>
                            </div>
                            <h5 class="fw-semibold">Nueva Cita</h5>
                            <p class="text-muted small mb-3">Programa una nueva cita médica</p>
                            <a href="agenda.php" class="btn btn-warning w-100">
                                <i class="bi bi-plus-circle me-1"></i> Programar
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
                                <i class="bi bi-search me-1"></i> Buscar Avanzado
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- CONSULTAS RECIENTES -->
                <div class="card border-0 shadow-sm mt-4">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="card-title mb-0">
                                <i class="bi bi-clock-history me-2"></i> Consultas Recientes
                            </h5>
                            <a href="historial.php" class="btn btn-sm btn-outline-primary">
                                Ver todas
                            </a>
                        </div>
                        
                        <div class="activity-list">
                            <?php if ($consultas_recientes && $consultas_recientes->num_rows > 0): ?>
                                <?php while($consulta = $consultas_recientes->fetch_assoc()): ?>
                                <div class="activity-item">
                                    <div class="d-flex align-items-center">
                                        <div class="me-3">
                                            <i class="bi bi-file-medical fs-5 text-success"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="fw-medium"><?php echo htmlspecialchars($consulta['nombre']); ?></div>
                                            <small class="text-muted">
                                                <?php 
                                                if (!empty($consulta['motivo'])) {
                                                    echo nl2br(htmlspecialchars(substr($consulta['motivo'], 0, 50)));
                                                    if (strlen($consulta['motivo']) > 50) echo '...';
                                                } else {
                                                    echo 'Sin motivo especificado';
                                                }
                                                ?>
                                            </small>
                                        </div>
                                        <div class="text-end">
                                            <div class="text-muted small"><?php echo $consulta['fecha_formateada']; ?></div>
                                            <div class="small"><?php echo $consulta['hora']; ?></div>
                                        </div>
                                    </div>
                                </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <div class="text-center py-4">
                                    <i class="bi bi-inbox fs-1 text-muted"></i>
                                    <p class="text-muted mt-2 mb-0">No hay consultas recientes</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- ACTIVIDAD RECIENTE Y MÓDULOS -->
            <div class="col-lg-4">
                <!-- MÓDULOS DEL SISTEMA -->
                <h4 class="mb-3"><i class="bi bi-grid-3x3-gap text-primary me-2"></i> Módulos</h4>
                
                <div class="row g-3 mb-4">
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
                
                <!-- CITAS PRÓXIMAS -->
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="card-title mb-0">
                                <i class="bi bi-calendar-check text-warning me-2"></i> Citas Próximas
                            </h5>
                            <a href="agenda.php" class="btn btn-sm btn-outline-warning">
                                Ver agenda
                            </a>
                        </div>
                        
                        <?php if ($citas_proximas && $citas_proximas->num_rows > 0): ?>
                            <?php while($cita = $citas_proximas->fetch_assoc()): ?>
                            <?php 
                            $fecha_cita = new DateTime($cita['fecha']);
                            $hoy = new DateTime();
                            $diferencia = $hoy->diff($fecha_cita)->days;
                            $badge_class = $diferencia == 0 ? 'bg-danger' : ($diferencia == 1 ? 'bg-warning' : 'bg-info');
                            ?>
                            <div class="activity-item">
                                <div class="d-flex align-items-center">
                                    <div class="me-3">
                                        <span class="badge <?php echo $badge_class; ?>">
                                            <?php echo date('d/m', strtotime($cita['fecha'])); ?>
                                        </span>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="fw-medium small"><?php echo htmlspecialchars($cita['nombre']); ?></div>
                                        <small class="text-muted">
                                            <?php echo date('H:i', strtotime($cita['hora'])); ?> - 
                                            <?php echo htmlspecialchars(substr($cita['motivo'], 0, 30)); ?>
                                            <?php if (strlen($cita['motivo']) > 30): ?>...<?php endif; ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="text-center py-3">
                                <i class="bi bi-calendar-x text-muted"></i>
                                <p class="text-muted mt-2 mb-0 small">No hay citas próximas</p>
                            </div>
                        <?php endif; ?>
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
                        <?php echo date('d/m/Y H:i:s'); ?> | 
                        <span id="uptime">Actualizado hace <span id="uptimeSeconds">0</span> segundos</span>
                    </p>
                </div>
            </div>
        </footer>
    </div>
    
    <!-- MODAL BUSCAR AVANZADO -->
    <div class="modal fade" id="buscarModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-search me-2"></i> Búsqueda Avanzada</h5>
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
        
        // CONTADOR DE TIEMPO DESDE ÚLTIMA ACTUALIZACIÓN
        let uptimeSeconds = 0;
        function updateUptime() {
            uptimeSeconds++;
            document.getElementById('uptimeSeconds').textContent = uptimeSeconds;
        }
        setInterval(updateUptime, 1000);
        
        // ========== GRÁFICO DE ACTIVIDAD ==========
        document.addEventListener('DOMContentLoaded', function() {
            // Ejemplo de datos para los últimos 7 días
            const last7Days = [];
            for (let i = 6; i >= 0; i--) {
                const date = new Date();
                date.setDate(date.getDate() - i);
                last7Days.push(date.toLocaleDateString('es-ES', { day: 'numeric', month: 'short' }));
            }
            
            // Datos de ejemplo
            const pacientesData = [<?php echo $pacientes_hoy; ?>, 5, 2, 7, 4, 6, 3];
            const consultasData = [<?php echo $consultas_hoy; ?>, 10, 6, 12, 9, 11, 8];
            
            const ctx = document.getElementById('activityChart').getContext('2d');
            const activityChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: last7Days,
                    datasets: [
                        {
                            label: 'Pacientes',
                            data: pacientesData,
                            borderColor: '#3498db',
                            backgroundColor: 'rgba(52, 152, 219, 0.1)',
                            borderWidth: 2,
                            fill: true,
                            tension: 0.4
                        },
                        {
                            label: 'Consultas',
                            data: consultasData,
                            borderColor: '#2ecc71',
                            backgroundColor: 'rgba(46, 204, 113, 0.1)',
                            borderWidth: 2,
                            fill: true,
                            tension: 0.4
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            }
                        }
                    }
                }
            });
        });
    </script>
</body>
</html>