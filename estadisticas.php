<?php
// estadisticas.php - VERSIÓN CORREGIDA (sin columna 'sexo')
session_start();
require_once 'config.php';
verificarSesion();

// Obtener estadísticas generales
$stats = [
    'total_pacientes' => $conexion->query("SELECT COUNT(*) as total FROM pacientes")->fetch_assoc()['total'],
    'total_consultas' => $conexion->query("SELECT COUNT(*) as total FROM historial")->fetch_assoc()['total'],
    'consultas_hoy' => $conexion->query("SELECT COUNT(*) as total FROM historial WHERE fecha = CURDATE()")->fetch_assoc()['total'],
    'pacientes_hoy' => $conexion->query("SELECT COUNT(*) as total FROM pacientes WHERE DATE(fecha_registro) = CURDATE()")->fetch_assoc()['total'],
    'citas_hoy' => $conexion->query("SELECT COUNT(*) as total FROM agenda WHERE fecha = CURDATE() AND estado = 'pendiente'")->fetch_assoc()['total'],
    'promedio_consultas' => $conexion->query("SELECT IFNULL(AVG(consultas), 0) as promedio FROM (SELECT COUNT(*) as consultas FROM historial GROUP BY paciente_id) as t")->fetch_assoc()['promedio']
];

// Obtener datos para gráficos
// 1. Consultas por mes (últimos 6 meses)
$consultas_por_mes = $conexion->query("
    SELECT 
        DATE_FORMAT(fecha, '%Y-%m') as mes,
        DATE_FORMAT(fecha, '%b') as mes_nombre,
        COUNT(*) as total
    FROM historial 
    WHERE fecha >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(fecha, '%Y-%m'), DATE_FORMAT(fecha, '%b')
    ORDER BY mes
");

$labels_meses = [];
$data_consultas = [];
while($row = $consultas_por_mes->fetch_assoc()) {
    $labels_meses[] = $row['mes_nombre'];
    $data_consultas[] = $row['total'];
}

// 2. Top 10 diagnósticos más comunes
$top_diagnosticos = $conexion->query("
    SELECT 
        diagnostico,
        COUNT(*) as total
    FROM historial 
    WHERE diagnostico IS NOT NULL AND diagnostico != ''
    GROUP BY diagnostico
    ORDER BY total DESC
    LIMIT 10
");

$labels_diagnosticos = [];
$data_diagnosticos = [];
while($row = $top_diagnosticos->fetch_assoc()) {
    $labels_diagnosticos[] = substr($row['diagnostico'], 0, 20) . (strlen($row['diagnostico']) > 20 ? '...' : '');
    $data_diagnosticos[] = $row['total'];
}

// 3. Estado de citas
$estado_citas = $conexion->query("
    SELECT 
        estado,
        COUNT(*) as total
    FROM agenda 
    GROUP BY estado
");

// 4. Últimos 10 registros de actividad
$actividad_reciente = $conexion->query("
    (
        SELECT 
            'paciente' as tipo,
            CONCAT('👤 ', nombre) as descripcion,
            fecha_registro as fecha,
            'primary' as color
        FROM pacientes 
        ORDER BY fecha_registro DESC 
        LIMIT 5
    )
    UNION ALL
    (
        SELECT 
            'consulta' as tipo,
            CONCAT('🏥 ', 'Consulta registrada') as descripcion,
            fecha as fecha,
            'success' as color
        FROM historial 
        ORDER BY fecha DESC 
        LIMIT 5
    )
    ORDER BY fecha DESC 
    LIMIT 10
");

// 5. Pacientes con más consultas
$pacientes_top = $conexion->query("
    SELECT 
        p.nombre,
        COUNT(h.id) as total_consultas,
        MAX(h.fecha) as ultima_consulta
    FROM pacientes p
    LEFT JOIN historial h ON p.id = h.paciente_id
    GROUP BY p.id
    HAVING COUNT(h.id) > 0
    ORDER BY total_consultas DESC
    LIMIT 10
");

// 6. Horas pico de consultas
$horas_pico = $conexion->query("
    SELECT 
        HOUR(fecha_hora_creacion) as hora,
        COUNT(*) as total
    FROM historial 
    WHERE fecha_hora_creacion IS NOT NULL
    GROUP BY HOUR(fecha_hora_creacion)
    ORDER BY total DESC
    LIMIT 5
");

// 7. Consultas por día de la semana (nuevo gráfico en lugar de género)
$consultas_semana = $conexion->query("
    SELECT 
        DAYNAME(fecha) as dia_semana,
        DAYOFWEEK(fecha) as dia_numero,
        COUNT(*) as total
    FROM historial 
    WHERE fecha >= DATE_SUB(CURDATE(), INTERVAL 3 MONTH)
    GROUP BY DAYNAME(fecha), DAYOFWEEK(fecha)
    ORDER BY dia_numero
");

$labels_dias = [];
$data_dias = [];
$dias_espanol = [
    'Monday' => 'Lunes',
    'Tuesday' => 'Martes',
    'Wednesday' => 'Miércoles',
    'Thursday' => 'Jueves',
    'Friday' => 'Viernes',
    'Saturday' => 'Sábado',
    'Sunday' => 'Domingo'
];

while($row = $consultas_semana->fetch_assoc()) {
    $labels_dias[] = $dias_espanol[$row['dia_semana']] ?? $row['dia_semana'];
    $data_dias[] = $row['total'];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estadísticas - Clínica Simple</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary-color: #3498db;
            --secondary-color: #2c3e50;
            --success-color: #27ae60;
            --warning-color: #f39c12;
            --danger-color: #e74c3c;
            --info-color: #17a2b8;
        }
        
        .stat-card {
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            color: white;
            transition: transform 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            opacity: 0.9;
        }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            font-size: 0.9rem;
            opacity: 0.9;
            margin-bottom: 0;
        }
        
        .chart-container {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 3px 15px rgba(0,0,0,0.05);
        }
        
        .chart-title {
            color: var(--secondary-color);
            font-weight: 600;
            margin-bottom: 1.5rem;
            border-bottom: 2px solid #f1f5f9;
            padding-bottom: 0.5rem;
        }
        
        .activity-item {
            padding: 1rem;
            border-left: 3px solid;
            margin-bottom: 0.5rem;
            background: #f8fafc;
            border-radius: 5px;
            transition: all 0.2s;
        }
        
        .activity-item:hover {
            background: #e8f4fd;
            transform: translateX(5px);
        }
        
        .badge-stat {
            font-size: 0.8rem;
            padding: 0.25rem 0.5rem;
        }
        
        .time-badge {
            background: #e8f4fd;
            color: var(--primary-color);
            padding: 0.25rem 0.5rem;
            border-radius: 20px;
            font-size: 0.75rem;
        }
        
        .export-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 0.5rem 1.5rem;
            border-radius: 25px;
            transition: all 0.3s;
        }
        
        .export-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        }
        
        .date-filter {
            background: white;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .top-patient-card {
            background: white;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem;
            border-left: 4px solid var(--primary-color);
            transition: all 0.3s;
        }
        
        .top-patient-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .patient-rank {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: var(--primary-color);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-right: 1rem;
        }
        
        .rank-1 { background: #f39c12 !important; }
        .rank-2 { background: #95a5a6 !important; }
        .rank-3 { background: #d35400 !important; }
        
        /* Colores para días de la semana */
        .lunes { color: #3498db; }
        .martes { color: #2ecc71; }
        .miercoles { color: #9b59b6; }
        .jueves { color: #e74c3c; }
        .viernes { color: #f39c12; }
        .sabado { color: #1abc9c; }
        .domingo { color: #e67e22; }
    </style>
</head>
<body>
    <?php include 'menu_lateral.php'; ?>
    
    <div id="main-content">
        <div class="container-fluid mt-3">
            
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-1">
                        <i class="bi bi-graph-up me-2"></i>
                        Estadísticas del Sistema
                    </h1>
                    <p class="text-muted mb-0">Análisis y métricas de tu clínica</p>
                </div>
                <div>
                    <button class="btn export-btn" onclick="exportarReporte()">
                        <i class="bi bi-download me-1"></i> Exportar Reporte
                    </button>
                </div>
            </div>
            
            <!-- Filtros de fecha -->
            <div class="date-filter">
                <form method="GET" class="row g-3 align-items-center">
                    <div class="col-md-4">
                        <label class="form-label">Fecha desde:</label>
                        <input type="date" class="form-control" name="fecha_desde" value="<?php echo date('Y-m-01'); ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Fecha hasta:</label>
                        <input type="date" class="form-control" name="fecha_hasta" value="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">&nbsp;</label>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-filter me-1"></i> Filtrar
                            </button>
                        </div>
                    </div>
                </form>
            </div>
            
            <!-- Tarjetas de estadísticas principales -->
            <div class="row">
                <div class="col-xl-3 col-md-6">
                    <div class="stat-card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                        <div class="stat-icon">
                            <i class="bi bi-people"></i>
                        </div>
                        <div class="stat-number"><?php echo $stats['total_pacientes']; ?></div>
                        <div class="stat-label">Pacientes Registrados</div>
                        <small class="d-block mt-2">
                            <i class="bi bi-plus-circle me-1"></i> <?php echo $stats['pacientes_hoy']; ?> hoy
                        </small>
                    </div>
                </div>
                
                <div class="col-xl-3 col-md-6">
                    <div class="stat-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                        <div class="stat-icon">
                            <i class="bi bi-file-medical"></i>
                        </div>
                        <div class="stat-number"><?php echo $stats['total_consultas']; ?></div>
                        <div class="stat-label">Consultas Totales</div>
                        <small class="d-block mt-2">
                            <i class="bi bi-plus-circle me-1"></i> <?php echo $stats['consultas_hoy']; ?> hoy
                        </small>
                    </div>
                </div>
                
                <div class="col-xl-3 col-md-6">
                    <div class="stat-card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                        <div class="stat-icon">
                            <i class="bi bi-calendar-check"></i>
                        </div>
                        <div class="stat-number"><?php echo $stats['citas_hoy']; ?></div>
                        <div class="stat-label">Citas Hoy</div>
                        <small class="d-block mt-2">
                            <i class="bi bi-calendar me-1"></i> Pendientes
                        </small>
                    </div>
                </div>
                
                <div class="col-xl-3 col-md-6">
                    <div class="stat-card" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
                        <div class="stat-icon">
                            <i class="bi bi-bar-chart"></i>
                        </div>
                        <div class="stat-number"><?php echo number_format($stats['promedio_consultas'], 1); ?></div>
                        <div class="stat-label">Promedio Consultas/Paciente</div>
                        <small class="d-block mt-2">
                            <i class="bi bi-graph-up me-1"></i> Ratio
                        </small>
                    </div>
                </div>
            </div>
            
            <!-- Gráficos principales -->
            <div class="row">
                <div class="col-lg-8">
                    <div class="chart-container">
                        <h5 class="chart-title">
                            <i class="bi bi-calendar-week me-2"></i>
                            Consultas por Mes (Últimos 6 meses)
                        </h5>
                        <canvas id="chartConsultasMensuales" height="150"></canvas>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="chart-container">
                                <h5 class="chart-title">
                                    <i class="bi bi-calendar-day me-2"></i>
                                    Consultas por Día de la Semana
                                </h5>
                                <canvas id="chartDiasSemana" height="200"></canvas>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="chart-container">
                                <h5 class="chart-title">
                                    <i class="bi bi-clipboard-data me-2"></i>
                                    Top 10 Diagnósticos
                                </h5>
                                <canvas id="chartDiagnosticos" height="200"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-4">
                    <!-- Actividad Reciente -->
                    <div class="chart-container">
                        <h5 class="chart-title">
                            <i class="bi bi-clock-history me-2"></i>
                            Actividad Reciente
                        </h5>
                        <div style="max-height: 300px; overflow-y: auto;">
                            <?php if ($actividad_reciente->num_rows > 0): ?>
                                <?php while($actividad = $actividad_reciente->fetch_assoc()): ?>
                                <div class="activity-item" style="border-left-color: var(--<?php echo $actividad['color']; ?>-color);">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div class="flex-grow-1">
                                            <p class="mb-1"><?php echo $actividad['descripcion']; ?></p>
                                            <small class="text-muted">
                                                <i class="bi bi-clock me-1"></i>
                                                <?php echo date('H:i', strtotime($actividad['fecha'])); ?>
                                            </small>
                                        </div>
                                        <span class="badge badge-stat bg-<?php echo $actividad['color']; ?>">
                                            <?php echo $actividad['tipo']; ?>
                                        </span>
                                    </div>
                                </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <div class="text-center py-4">
                                    <i class="bi bi-inbox text-muted fs-1"></i>
                                    <p class="text-muted mt-2">No hay actividad reciente</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Pacientes Top -->
                    <div class="chart-container">
                        <h5 class="chart-title">
                            <i class="bi bi-trophy me-2"></i>
                            Pacientes con Más Consultas
                        </h5>
                        <div style="max-height: 300px; overflow-y: auto;">
                            <?php if ($pacientes_top && $pacientes_top->num_rows > 0): ?>
                                <?php $rank = 1; ?>
                                <?php while($paciente = $pacientes_top->fetch_assoc()): ?>
                                <div class="top-patient-card">
                                    <div class="d-flex align-items-center">
                                        <div class="patient-rank rank-<?php echo $rank; ?>">
                                            <?php echo $rank; ?>
                                        </div>
                                        <div class="flex-grow-1">
                                            <h6 class="mb-1"><?php echo htmlspecialchars($paciente['nombre']); ?></h6>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <span class="badge bg-primary">
                                                    <i class="bi bi-file-medical me-1"></i>
                                                    <?php echo $paciente['total_consultas']; ?> consultas
                                                </span>
                                                <small class="text-muted">
                                                    <?php echo $paciente['ultima_consulta'] ? date('d/m', strtotime($paciente['ultima_consulta'])) : 'Sin consultas'; ?>
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php $rank++; ?>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <div class="text-center py-3">
                                    <i class="bi bi-people text-muted fs-1"></i>
                                    <p class="text-muted mt-2">No hay datos de pacientes</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Tablas adicionales -->
            <div class="row mt-3">
                <div class="col-md-6">
                    <div class="chart-container">
                        <h5 class="chart-title">
                            <i class="bi bi-calendar me-2"></i>
                            Estado de Citas
                        </h5>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Estado</th>
                                        <th>Cantidad</th>
                                        <th>Porcentaje</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $total_citas = 0;
                                    $estados_data = [];
                                    if ($estado_citas) {
                                        while($estado = $estado_citas->fetch_assoc()) {
                                            $estados_data[] = $estado;
                                            $total_citas += $estado['total'];
                                        }
                                        // Reset pointer
                                        $estado_citas->data_seek(0);
                                    }
                                    ?>
                                    <?php if ($total_citas > 0): ?>
                                        <?php foreach($estados_data as $estado): ?>
                                        <?php $porcentaje = ($estado['total'] / $total_citas) * 100; ?>
                                        <tr>
                                            <td>
                                                <?php 
                                                $badge_color = 'secondary';
                                                if ($estado['estado'] == 'pendiente') $badge_color = 'warning';
                                                if ($estado['estado'] == 'completada') $badge_color = 'success';
                                                if ($estado['estado'] == 'cancelada') $badge_color = 'danger';
                                                ?>
                                                <span class="badge bg-<?php echo $badge_color; ?>">
                                                    <?php echo ucfirst($estado['estado']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo $estado['total']; ?></td>
                                            <td>
                                                <div class="progress" style="height: 20px;">
                                                    <div class="progress-bar bg-<?php echo $badge_color; ?>" 
                                                         style="width: <?php echo $porcentaje; ?>%">
                                                        <?php echo number_format($porcentaje, 1); ?>%
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="3" class="text-center text-muted py-3">
                                                <i class="bi bi-calendar-x"></i> No hay citas registradas
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="chart-container">
                        <h5 class="chart-title">
                            <i class="bi bi-clock me-2"></i>
                            Horas Pico de Consultas
                        </h5>
                        <?php if ($horas_pico && $horas_pico->num_rows > 0): 
                            // Primero obtenemos todos los datos
                            $horas_data = [];
                            $max_consultas = 0;
                            while($hora = $horas_pico->fetch_assoc()) {
                                $horas_data[] = $hora;
                                if ($hora['total'] > $max_consultas) {
                                    $max_consultas = $hora['total'];
                                }
                            }
                        ?>
                        <div class="row">
                            <?php foreach($horas_data as $hora): ?>
                            <div class="col-12 mb-3">
                                <div class="d-flex align-items-center">
                                    <div class="time-badge me-3">
                                        <?php echo str_pad($hora['hora'], 2, '0', STR_PAD_LEFT); ?>:00
                                    </div>
                                    <div class="flex-grow-1">
                                        <?php $porcentaje_hora = ($max_consultas > 0) ? ($hora['total'] / $max_consultas) * 100 : 0; ?>
                                        <div class="progress" style="height: 25px;">
                                            <div class="progress-bar bg-info" style="width: <?php echo $porcentaje_hora; ?>%">
                                                <span class="ms-2 fw-semibold"><?php echo $hora['total']; ?> consultas</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php else: ?>
                        <div class="text-center py-4">
                            <i class="bi bi-clock-history text-muted fs-1"></i>
                            <p class="text-muted mt-2">No hay datos de horas</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Información del sistema -->
            <div class="chart-container mt-3">
                <h5 class="chart-title">
                    <i class="bi bi-info-circle me-2"></i>
                    Información del Sistema
                </h5>
                <div class="row">
                    <div class="col-md-4">
                        <div class="card bg-light border-0">
                            <div class="card-body">
                                <h6 class="card-title"><i class="bi bi-person me-2"></i> Usuario</h6>
                                <p class="card-text"><?php echo htmlspecialchars($_SESSION['usuario']); ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-light border-0">
                            <div class="card-body">
                                <h6 class="card-title"><i class="bi bi-calendar me-2"></i> Fecha y Hora</h6>
                                <p class="card-text"><?php echo date('d/m/Y H:i:s'); ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-light border-0">
                            <div class="card-body">
                                <h6 class="card-title"><i class="bi bi-database me-2"></i> Base de Datos</h6>
                                <p class="card-text">clinica_simple (MySQL)</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Gráfico de consultas mensuales
        const ctx1 = document.getElementById('chartConsultasMensuales').getContext('2d');
        new Chart(ctx1, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($labels_meses); ?>,
                datasets: [{
                    label: 'Consultas',
                    data: <?php echo json_encode($data_consultas); ?>,
                    borderColor: '#3498db',
                    backgroundColor: 'rgba(52, 152, 219, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            drawBorder: false
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
        
        // Gráfico de días de la semana
        const ctx2 = document.getElementById('chartDiasSemana').getContext('2d');
        new Chart(ctx2, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($labels_dias); ?>,
                datasets: [{
                    label: 'Consultas',
                    data: <?php echo json_encode($data_dias); ?>,
                    backgroundColor: [
                        'rgba(52, 152, 219, 0.7)',   // Lunes
                        'rgba(46, 204, 113, 0.7)',   // Martes
                        'rgba(155, 89, 182, 0.7)',   // Miércoles
                        'rgba(231, 76, 60, 0.7)',    // Jueves
                        'rgba(243, 156, 18, 0.7)',   // Viernes
                        'rgba(26, 188, 156, 0.7)',   // Sábado
                        'rgba(230, 126, 34, 0.7)'    // Domingo
                    ],
                    borderColor: [
                        '#3498db',
                        '#2ecc71',
                        '#9b59b6',
                        '#e74c3c',
                        '#f39c12',
                        '#1abc9c',
                        '#e67e22'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
        
        // Gráfico de diagnósticos (horizontal bar)
        const ctx3 = document.getElementById('chartDiagnosticos').getContext('2d');
        new Chart(ctx3, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($labels_diagnosticos); ?>,
                datasets: [{
                    label: 'Frecuencia',
                    data: <?php echo json_encode($data_diagnosticos); ?>,
                    backgroundColor: 'rgba(46, 204, 113, 0.7)',
                    borderColor: '#27ae60',
                    borderWidth: 1
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true
                    }
                }
            }
        });
        
        // Función para exportar reporte
        function exportarReporte() {
            if (confirm('¿Generar reporte en PDF?')) {
                // Aquí puedes implementar la generación de PDF
                // Por ahora solo un mensaje
                alert('Función de exportación en desarrollo.\nPronto podrás descargar reportes completos.');
            }
        }
        
        // Auto-refresh cada 5 minutos (opcional)
        setTimeout(() => {
            location.reload();
        }, 300000); // 5 minutos
    </script>
</body>
</html>