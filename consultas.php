<?php
// consultas.php - LISTA DE CONSULTAS REALIZADAS
require_once 'config.php';
verificarSesion();

// Obtener consultas (historial)
$consultas = $conexion->query("
    SELECT h.*, p.nombre as paciente_nombre 
    FROM historial h 
    JOIN pacientes p ON h.paciente_id = p.id 
    ORDER BY h.fecha DESC 
    LIMIT 50
");

$total_consultas = $consultas->num_rows;
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consultas Médicas - Clínica Simple</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
</head>
<body>
    <?php include 'menu_lateral.php'; ?>
    
    <div id="main-content">
        <div class="container-fluid mt-3">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-1"><i class="bi bi-file-medical me-2"></i> Consultas Médicas</h1>
                    <p class="text-muted mb-0">Historial de consultas realizadas</p>
                </div>
                <a href="agenda.php" class="btn btn-outline-primary">
                    <i class="bi bi-calendar me-1"></i> Ver Agenda
                </a>
            </div>
            
            <!-- LISTA DE CONSULTAS -->
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-list-ul me-2"></i> Últimas Consultas</h5>
                </div>
                <div class="card-body">
                    <?php if ($total_consultas > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>Paciente</th>
                                    <th>Motivo</th>
                                    <th>Diagnóstico</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($consulta = $consultas->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo date('d/m/Y', strtotime($consulta['fecha'])); ?></td>
                                    <td>
                                        <a href="historial.php?paciente_id=<?php echo $consulta['paciente_id']; ?>">
                                            <?php echo htmlspecialchars($consulta['paciente_nombre']); ?>
                                        </a>
                                    </td>
                                    <td><?php echo htmlspecialchars(substr($consulta['motivo'], 0, 50)) . '...'; ?></td>
                                    <td><?php echo htmlspecialchars(substr($consulta['diagnostico'], 0, 50)) . '...'; ?></td>
                                    <td>
                                        <a href="historial.php?paciente_id=<?php echo $consulta['paciente_id']; ?>&ver=<?php echo $consulta['id']; ?>" 
                                           class="btn btn-sm btn-outline-info" title="Ver Detalles">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="text-center py-5">
                        <i class="bi bi-file-medical fs-1 text-muted mb-3"></i>
                        <h5 class="text-muted">No hay consultas registradas</h5>
                        <p class="text-muted">Las consultas aparecerán aquí después de atender citas de la agenda</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>