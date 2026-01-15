<?php
// agenda.php - MÓDULO SIMPLIFICADO DE AGENDA CON OPCIÓN DE CREAR CONSULTA
session_start();
require_once 'config.php';

// Verificar sesión
if (!isset($_SESSION['logueado']) || $_SESSION['logueado'] !== true) {
    header('Location: index.php');
    exit;
}

// Inicializar mensajes
$mensaje_exito = '';
$mensaje_error = '';

// Procesar formulario de nueva cita
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_cita'])) {
    $paciente_id = intval($_POST['paciente_id']);
    $fecha = $_POST['fecha'];
    $hora = $_POST['hora'];
    $duracion = intval($_POST['duracion']);
    $tipo = $_POST['tipo'];
    $motivo = $_POST['motivo'];
    $notas = $_POST['notas'];
    
    // Validar datos
    if ($paciente_id > 0 && !empty($fecha) && !empty($hora) && !empty($motivo)) {
        $sql = "INSERT INTO agenda (paciente_id, fecha, hora, duracion, tipo, motivo, notas) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conexion->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("issiiss", $paciente_id, $fecha, $hora, $duracion, $tipo, $motivo, $notas);
            
            if ($stmt->execute()) {
                $mensaje_exito = "✅ Cita guardada correctamente";
            } else {
                $mensaje_error = "❌ Error al guardar la cita";
            }
            $stmt->close();
        } else {
            $mensaje_error = "❌ Error en la consulta SQL";
        }
    } else {
        $mensaje_error = "❌ Complete todos los campos requeridos";
    }
}

// Cambiar estado de cita
if (isset($_GET['cambiar_estado'])) {
    $cita_id = intval($_GET['id']);
    $nuevo_estado = $_GET['estado'];
    
    $sql = "UPDATE agenda SET estado = ? WHERE id = ?";
    $stmt = $conexion->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("si", $nuevo_estado, $cita_id);
        if ($stmt->execute()) {
            $mensaje_exito = "✅ Estado actualizado";
        }
        $stmt->close();
    }
}

// Eliminar cita
if (isset($_GET['eliminar_cita'])) {
    $cita_id = intval($_GET['id']);
    
    $sql = "DELETE FROM agenda WHERE id = ?";
    $stmt = $conexion->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("i", $cita_id);
        if ($stmt->execute()) {
            $mensaje_exito = "✅ Cita eliminada";
        }
        $stmt->close();
    }
}

// Obtener datos
try {
    // Estadísticas
    $total_citas = $conexion->query("SELECT COUNT(*) as total FROM agenda")->fetch_assoc()['total'];
    $citas_hoy = $conexion->query("SELECT COUNT(*) as total FROM agenda WHERE fecha = CURDATE()")->fetch_assoc()['total'];
    $citas_semana = $conexion->query("SELECT COUNT(*) as total FROM agenda WHERE fecha BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)")->fetch_assoc()['total'];
    $pendientes = $conexion->query("SELECT COUNT(*) as total FROM agenda WHERE estado = 'pendiente'")->fetch_assoc()['total'];
    
    // Citas de hoy
    $citas_hoy_detalle = $conexion->query("
        SELECT a.*, p.nombre as paciente_nombre 
        FROM agenda a 
        LEFT JOIN pacientes p ON a.paciente_id = p.id 
        WHERE a.fecha = CURDATE() 
        ORDER BY a.hora
    ");
    
    // Próximas citas
    $proximas_citas = $conexion->query("
        SELECT a.*, p.nombre as paciente_nombre 
        FROM agenda a 
        LEFT JOIN pacientes p ON a.paciente_id = p.id 
        WHERE a.fecha >= CURDATE() 
        AND a.fecha <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)
        AND a.estado != 'cancelada'
        ORDER BY a.fecha, a.hora
        LIMIT 10
    ");
    
    // Todos los pacientes
    $pacientes = $conexion->query("SELECT id, nombre FROM pacientes ORDER BY nombre");
    
} catch (Exception $e) {
    $error_bd = "Error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agenda de Citas - Clínica Simple</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body { background-color: #f8f9fa; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .stat-card { background: white; border-radius: 10px; padding: 1.5rem; box-shadow: 0 3px 10px rgba(0,0,0,0.08); height: 100%; border-top: 4px solid; }
        .stat-number { font-size: 2.5rem; font-weight: bold; color: #2c3e50; }
        .cita-card { border-left: 4px solid; border-radius: 8px; margin-bottom: 1rem; padding: 1rem; background: white; }
        .estado-pendiente { border-left-color: #f39c12; background-color: #fff8e1; }
        .estado-confirmada { border-left-color: #2ecc71; background-color: #e8f5e9; }
        .estado-cancelada { border-left-color: #e74c3c; background-color: #ffebee; }
        .calendar-day { border: 1px solid #dee2e6; height: 120px; padding: 0.5rem; overflow-y: auto; }
        .today { background-color: #e7f5ff; border: 2px solid #3498db !important; }
        .btn-consulta { background: linear-gradient(135deg, #2ecc71, #27ae60); color: white; }
        .btn-consulta:hover { background: linear-gradient(135deg, #27ae60, #219653); color: white; }
    </style>
</head>
<body>
    <!-- INCLUIR EL MENÚ LATERAL -->
    <?php include 'menu_lateral.php'; ?>
    
    <div id="main-content">
        <div class="container mt-4">
            
            <!-- Mensajes -->
            <?php if (!empty($mensaje_exito)): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?php echo $mensaje_exito; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($mensaje_error)): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?php echo $mensaje_error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>
            
            <!-- HEADER -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-1">📅 Agenda de Citas</h1>
                    <p class="text-muted mb-0">
                        <i class="bi bi-calendar3 me-1"></i>
                        <?php echo date('d/m/Y'); ?>
                    </p>
                </div>
                <div>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#nuevaCitaModal">
                        <i class="bi bi-plus-circle me-1"></i> Nueva Cita
                    </button>
                    <a href="consultas.php" class="btn btn-success ms-2">
                        <i class="bi bi-file-medical me-1"></i> Ver Consultas
                    </a>
                </div>
            </div>
            
            <!-- ESTADÍSTICAS -->
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="stat-card border-primary">
                        <div class="text-primary mb-2"><i class="bi bi-calendar-check fs-1"></i></div>
                        <div class="stat-number"><?php echo $total_citas; ?></div>
                        <div class="fw-bold">Citas Totales</div>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="stat-card border-success">
                        <div class="text-success mb-2"><i class="bi bi-calendar-day fs-1"></i></div>
                        <div class="stat-number"><?php echo $citas_hoy; ?></div>
                        <div class="fw-bold">Citas Hoy</div>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="stat-card border-warning">
                        <div class="text-warning mb-2"><i class="bi bi-calendar-week fs-1"></i></div>
                        <div class="stat-number"><?php echo $citas_semana; ?></div>
                        <div class="fw-bold">Esta Semana</div>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="stat-card border-info">
                        <div class="text-info mb-2"><i class="bi bi-clock-history fs-1"></i></div>
                        <div class="stat-number"><?php echo $pendientes; ?></div>
                        <div class="fw-bold">Pendientes</div>
                    </div>
                </div>
            </div>
            
            <!-- CITAS DE HOY -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-calendar-day me-2"></i> Citas para hoy</h5>
                </div>
                <div class="card-body">
                    <?php if ($citas_hoy_detalle && $citas_hoy_detalle->num_rows > 0): ?>
                        <div class="row">
                            <?php while($cita = $citas_hoy_detalle->fetch_assoc()): ?>
                            <div class="col-md-6 mb-3">
                                <div class="cita-card estado-<?php echo $cita['estado']; ?>">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h6 class="mb-1"><?php echo htmlspecialchars($cita['paciente_nombre']); ?></h6>
                                            <p class="mb-1 small">
                                                <i class="bi bi-clock me-1"></i>
                                                <?php echo date('H:i', strtotime($cita['hora'])); ?> 
                                                (<?php echo $cita['duracion']; ?> min)
                                            </p>
                                            <p class="mb-1 small text-muted">
                                                <i class="bi bi-file-text me-1"></i>
                                                <?php echo htmlspecialchars($cita['motivo']); ?>
                                            </p>
                                        </div>
                                        <div>
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
                                            <div class="btn-group btn-group-sm mt-2">
                                                <!-- BOTÓN IMPORTANTE: CREAR CONSULTA -->
                                                <?php if ($cita['estado'] === 'pendiente' || $cita['estado'] === 'confirmada'): ?>
                                                <a href="crear_consulta.php?cita_id=<?php echo $cita['id']; ?>&paciente_id=<?php echo $cita['paciente_id']; ?>" 
                                                   class="btn btn-consulta btn-sm" 
                                                   title="Crear Consulta Médica">
                                                    <i class="bi bi-file-medical"></i>
                                                </a>
                                                <?php endif; ?>
                                                
                                                <a href="?cambiar_estado&id=<?php echo $cita['id']; ?>&estado=confirmada" 
                                                   class="btn btn-outline-success btn-sm" title="Confirmar Cita">
                                                    <i class="bi bi-check"></i>
                                                </a>
                                                <a href="?cambiar_estado&id=<?php echo $cita['id']; ?>&estado=cancelada" 
                                                   class="btn btn-outline-danger btn-sm" title="Cancelar Cita">
                                                    <i class="bi bi-x"></i>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="bi bi-calendar-x fs-1 text-muted mb-3"></i>
                            <p class="text-muted">No hay citas programadas para hoy</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- PRÓXIMAS CITAS -->
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="bi bi-calendar-week me-2"></i> Próximas citas (7 días)</h5>
                </div>
                <div class="card-body">
                    <?php if ($proximas_citas && $proximas_citas->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Hora</th>
                                        <th>Paciente</th>
                                        <th>Motivo</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($cita = $proximas_citas->fetch_assoc()): ?>
                                    <tr class="estado-<?php echo $cita['estado']; ?>">
                                        <td><?php echo date('d/m/Y', strtotime($cita['fecha'])); ?></td>
                                        <td><?php echo date('H:i', strtotime($cita['hora'])); ?></td>
                                        <td>
                                            <a href="pacientes.php?ver=<?php echo $cita['paciente_id']; ?>">
                                                <?php echo htmlspecialchars($cita['paciente_nombre']); ?>
                                            </a>
                                        </td>
                                        <td><?php echo htmlspecialchars(substr($cita['motivo'], 0, 30)) . '...'; ?></td>
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
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <!-- BOTÓN PARA CREAR CONSULTA DESDE LA TABLA -->
                                                <?php if ($cita['estado'] === 'pendiente' || $cita['estado'] === 'confirmada'): ?>
                                                <a href="crear_consulta.php?cita_id=<?php echo $cita['id']; ?>&paciente_id=<?php echo $cita['paciente_id']; ?>" 
                                                   class="btn btn-success btn-sm"
                                                   title="Crear Consulta">
                                                    <i class="bi bi-file-medical"></i>
                                                </a>
                                                <?php endif; ?>
                                                
                                                <a href="?cambiar_estado&id=<?php echo $cita['id']; ?>&estado=confirmada" 
                                                   class="btn btn-outline-success btn-sm" title="Confirmar">
                                                    <i class="bi bi-check"></i>
                                                </a>
                                                
                                                <a href="?cambiar_estado&id=<?php echo $cita['id']; ?>&estado=cancelada" 
                                                   class="btn btn-outline-danger btn-sm" title="Cancelar">
                                                    <i class="bi bi-x"></i>
                                                </a>
                                                
                                                <a href="?eliminar_cita&id=<?php echo $cita['id']; ?>" 
                                                   class="btn btn-outline-danger btn-sm"
                                                   onclick="return confirm('¿Eliminar esta cita?')"
                                                   title="Eliminar">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="bi bi-calendar-x fs-1 text-muted mb-3"></i>
                            <p class="text-muted">No hay citas programadas para los próximos días</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- MODAL NUEVA CITA -->
    <div class="modal fade" id="nuevaCitaModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title"><i class="bi bi-plus-circle me-2"></i> Nueva Cita</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Paciente *</label>
                            <select class="form-select" name="paciente_id" required>
                                <option value="">Seleccionar paciente...</option>
                                <?php if ($pacientes && $pacientes->num_rows > 0): ?>
                                    <?php while($pac = $pacientes->fetch_assoc()): ?>
                                    <option value="<?php echo $pac['id']; ?>"><?php echo htmlspecialchars($pac['nombre']); ?></option>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <option value="">No hay pacientes registrados</option>
                                <?php endif; ?>
                            </select>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Fecha *</label>
                                <input type="date" class="form-control" name="fecha" 
                                       value="<?php echo date('Y-m-d'); ?>" 
                                       min="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Hora *</label>
                                <input type="time" class="form-control" name="hora" 
                                       value="09:00" required>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Duración</label>
                                <select class="form-select" name="duracion">
                                    <option value="30">30 min</option>
                                    <option value="45" selected>45 min</option>
                                    <option value="60">60 min</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Tipo</label>
                                <select class="form-select" name="tipo">
                                    <option value="consulta" selected>Consulta</option>
                                    <option value="control">Control</option>
                                    <option value="seguimiento">Seguimiento</option>
                                    <option value="urgente">Urgente</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Motivo *</label>
                            <textarea class="form-control" name="motivo" rows="3" required 
                                      placeholder="Descripción del motivo de la cita..."></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Notas adicionales</label>
                            <textarea class="form-control" name="notas" rows="2" 
                                      placeholder="Observaciones o notas importantes..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" name="guardar_cita" class="btn btn-primary">Guardar Cita</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Validar fecha
        document.querySelector('input[name="fecha"]').addEventListener('change', function() {
            const fechaInput = this.value;
            const hoy = new Date().toISOString().split('T')[0];
            
            if (fechaInput < hoy) {
                alert('No puedes agendar citas en fechas pasadas');
                this.value = hoy;
            }
        });
        
        // Auto-cerrar mensajes después de 5 segundos
        setTimeout(() => {
            document.querySelectorAll('.alert').forEach(alert => {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
    </script>
</body>
</html>