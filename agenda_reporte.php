<?php
// agenda_reporte.php - Reporte de agenda
session_start();
require_once 'config.php';

// Verificar sesión
if (!isset($_SESSION['logueado']) || $_SESSION['logueado'] !== true) {
    header('Location: index.php');
    exit;
}

// Filtrar por fechas
$fecha_inicio = isset($_GET['fecha_inicio']) ? $_GET['fecha_inicio'] : date('Y-m-01');
$fecha_fin = isset($_GET['fecha_fin']) ? $_GET['fecha_fin'] : date('Y-m-t');

// Obtener citas filtradas
$citas = $conexion->query("
    SELECT a.*, p.nombre as paciente_nombre, p.telefono, p.dui
    FROM agenda a 
    LEFT JOIN pacientes p ON a.paciente_id = p.id 
    WHERE a.fecha BETWEEN '$fecha_inicio' AND '$fecha_fin'
    ORDER BY a.fecha, a.hora
");

// Contar por estado
$estadisticas = $conexion->query("
    SELECT estado, COUNT(*) as total 
    FROM agenda 
    WHERE fecha BETWEEN '$fecha_inicio' AND '$fecha_fin'
    GROUP BY estado
");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de Agenda - Clínica Simple</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <?php include 'menu_lateral.php'; ?>
    <style>
        @media print {
            .no-print { display: none !important; }
            .container { width: 100% !important; }
        }
    </style>
</head>
<body>
    <div id="main-content">
        <div class="container mt-4">
            <div class="d-flex justify-content-between align-items-center mb-4 no-print">
                <h1 class="h3">📊 Reporte de Agenda</h1>
                <button onclick="window.print()" class="btn btn-primary">
                    <i class="bi bi-printer me-1"></i> Imprimir
                </button>
            </div>
            
            <!-- Filtros -->
            <div class="card mb-4 no-print">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-4">
                            <label>Fecha inicio</label>
                            <input type="date" name="fecha_inicio" class="form-control" value="<?php echo $fecha_inicio; ?>">
                        </div>
                        <div class="col-md-4">
                            <label>Fecha fin</label>
                            <input type="date" name="fecha_fin" class="form-control" value="<?php echo $fecha_fin; ?>">
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-filter me-1"></i> Filtrar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Estadísticas -->
            <div class="row mb-4">
                <?php while($est = $estadisticas->fetch_assoc()): ?>
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <h3><?php echo $est['total']; ?></h3>
                            <p class="text-muted mb-0"><?php echo ucfirst($est['estado']); ?></p>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
            
            <!-- Tabla de citas -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Citas del <?php echo date('d/m/Y', strtotime($fecha_inicio)); ?> al <?php echo date('d/m/Y', strtotime($fecha_fin)); ?></h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th>Fecha</th>
                                    <th>Hora</th>
                                    <th>Paciente</th>
                                    <th>Teléfono</th>
                                    <th>Motivo</th>
                                    <th>Tipo</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($cita = $citas->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo date('d/m/Y', strtotime($cita['fecha'])); ?></td>
                                    <td><?php echo date('H:i', strtotime($cita['hora'])); ?></td>
                                    <td><?php echo htmlspecialchars($cita['paciente_nombre']); ?></td>
                                    <td><?php echo htmlspecialchars($cita['telefono'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($cita['motivo']); ?></td>
                                    <td><?php echo ucfirst($cita['tipo']); ?></td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            switch($cita['estado']) {
                                                case 'pendiente': echo 'warning'; break;
                                                case 'confirmada': echo 'success'; break;
                                                case 'cancelada': echo 'danger'; break;
                                                default: echo 'info';
                                            }
                                        ?>">
                                            <?php echo ucfirst($cita['estado']); ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Pie de reporte -->
            <div class="text-center mt-4 no-print">
                <p class="text-muted">Reporte generado el <?php echo date('d/m/Y H:i:s'); ?> por <?php echo $_SESSION['usuario']; ?></p>
                <a href="agenda.php" class="btn btn-outline-primary">
                    <i class="bi bi-arrow-left me-1"></i> Volver a Agenda
                </a>
            </div>
        </div>
    </div>
</body>
</html>