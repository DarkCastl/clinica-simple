<?php
// pacientes.php - VERSIÓN SIMPLIFICADA (SOLO GESTIÓN DE PACIENTES)
require_once 'config.php';
verificarSesion();

// Inicializar mensaje
$mensaje = '';

// DEPURACIÓN
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Mostrar mensaje si viene de agregar paciente
if (isset($_GET['mensaje']) && $_GET['mensaje'] === 'agregado') {
    $nombre_paciente = isset($_GET['nombre']) ? urldecode($_GET['nombre']) : '';
    $mensaje = '<div class="alert alert-success alert-dismissible fade show">
        <i class="bi bi-check-circle me-2"></i> ✅ Paciente "' . htmlspecialchars($nombre_paciente) . '" agregado exitosamente
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>';
}

// Obtener lista de pacientes con sus citas pendientes
$pacientes = $conexion->query("
    SELECT p.*, 
           COUNT(a.id) as citas_pendientes,
           GROUP_CONCAT(CONCAT(a.id, '|', a.fecha, '|', a.hora, '|', a.motivo, '|', IFNULL(a.estado, 'pendiente')) SEPARATOR ';') as citas_info
    FROM pacientes p
    LEFT JOIN agenda a ON p.id = a.paciente_id AND (a.estado = 'pendiente' OR a.estado IS NULL OR a.estado = 'confirmada')
    GROUP BY p.id
    ORDER BY p.fecha_registro DESC
");

// Contar total de pacientes
$total_pacientes = $pacientes->num_rows;

// Contar citas pendientes
$total_citas_pendientes = $conexion->query("SELECT COUNT(*) as total FROM agenda WHERE estado = 'pendiente' OR estado IS NULL")->fetch_assoc()['total'];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Pacientes - Clínica Simple</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        :root {
            --color-primary: #2c3e50;
            --color-secondary: #3498db;
            --color-success: #2ecc71;
            --color-warning: #f39c12;
            --color-danger: #e74c3c;
        }
        
        .badge-cita {
            background: linear-gradient(135deg, var(--color-warning), #e67e22);
            color: white;
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .badge-cita:hover {
            transform: scale(1.05);
            box-shadow: 0 3px 8px rgba(0,0,0,0.2);
        }
        
        .badge-cita.completada {
            background: linear-gradient(135deg, var(--color-success), #27ae60);
        }
        
        .badge-cita.cancelada {
            background: linear-gradient(135deg, var(--color-danger), #c0392b);
        }
        
        .badge-cita.confirmada {
            background: linear-gradient(135deg, #3498db, #2980b9);
        }
        
        .modal-cita {
            border-left: 4px solid var(--color-warning);
            background: white;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        }
        
        .modal-cita.completada {
            border-left-color: var(--color-success);
        }
        
        .modal-cita.confirmada {
            border-left-color: #3498db;
        }
        
        .btn-consulta {
            background: linear-gradient(135deg, #2ecc71, #27ae60);
            color: white;
        }
        
        .btn-consulta:hover {
            background: linear-gradient(135deg, #27ae60, #219653);
            color: white;
        }
        
        @media (max-width: 768px) {
            .floating-add-btn {
                position: fixed;
                bottom: 20px;
                right: 20px;
                z-index: 1000;
                border-radius: 50%;
                width: 60px;
                height: 60px;
                display: flex;
                align-items: center;
                justify-content: center;
                box-shadow: 0 4px 12px rgba(0,0,0,0.3);
            }
        }
    </style>
</head>
<body>
    <!-- INCLUIR EL MENÚ LATERAL -->
    <?php include 'menu_lateral.php'; ?>
    
    <div id="main-content">
        <div class="container-fluid mt-3">
            
            <!-- HEADER -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-1">👥 Gestión de Pacientes</h1>
                    <p class="text-muted mb-0">
                        <i class="bi bi-people me-1"></i>
                        Total: <?php echo $total_pacientes; ?> pacientes
                        <?php if ($total_citas_pendientes > 0): ?>
                        • <span class="text-warning"><i class="bi bi-clock me-1"></i> <?php echo $total_citas_pendientes; ?> citas pendientes</span>
                        <?php endif; ?>
                    </p>
                </div>
                <a href="agregar_paciente.php" class="btn btn-primary d-none d-md-inline-flex">
                    <i class="bi bi-person-plus me-1"></i> Nuevo Paciente
                </a>
            </div>
            
            <!-- Botón flotante para móviles -->
            <a href="agregar_paciente.php" class="btn btn-primary floating-add-btn d-md-none">
                <i class="bi bi-person-plus fs-5"></i>
            </a>
            
            <!-- Mensajes -->
            <?php echo $mensaje; ?>
            
            <!-- LISTA DE PACIENTES CON CITAS -->
            <div class="card">
                <div class="card-header" style="background: linear-gradient(135deg, var(--color-primary), var(--color-secondary)); color: white;">
                    <h5 class="mb-0"><i class="bi bi-list-ul me-2"></i> Pacientes Registrados</h5>
                </div>
                <div class="card-body">
                    <?php if ($total_pacientes > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Paciente</th>
                                    <th>Contacto</th>
                                    <th>Citas</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($paciente = $pacientes->fetch_assoc()): 
                                    $citas_pendientes = intval($paciente['citas_pendientes']);
                                    $citas_info = $paciente['citas_info'] ? explode(';', $paciente['citas_info']) : [];
                                ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold"><?php echo htmlspecialchars($paciente['nombre']); ?></div>
                                        <small class="text-muted">ID: <?php echo $paciente['id']; ?></small>
                                        <?php if (!empty($paciente['dui'])): ?>
                                        <br><small class="text-muted">DUI: <?php echo $paciente['dui']; ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div><i class="bi bi-telephone me-1"></i> <?php echo $paciente['telefono'] ?: 'N/A'; ?></div>
                                        <div><i class="bi bi-envelope me-1"></i> <?php echo $paciente['email'] ?: 'N/A'; ?></div>
                                    </td>
                                    <td>
                                        <?php if ($citas_pendientes > 0): ?>
                                        <div class="d-flex flex-wrap gap-2">
                                            <?php foreach($citas_info as $cita): 
                                                if (!empty($cita)) {
                                                    $parts = explode('|', $cita);
                                                    $cita_id = $parts[0] ?? '';
                                                    $fecha = $parts[1] ?? '';
                                                    $hora = $parts[2] ?? '';
                                                    $motivo = $parts[3] ?? '';
                                                    $estado = $parts[4] ?? 'pendiente';
                                                    
                                                    $badge_class = 'badge-cita';
                                                    if ($estado === 'completada') $badge_class .= ' completada';
                                                    elseif ($estado === 'cancelada') $badge_class .= ' cancelada';
                                                    elseif ($estado === 'confirmada') $badge_class .= ' confirmada';
                                            ?>
                                            <span class="<?php echo $badge_class; ?>" 
                                                  data-bs-toggle="modal" 
                                                  data-bs-target="#citaModal"
                                                  data-cita-id="<?php echo $cita_id; ?>"
                                                  data-paciente-id="<?php echo $paciente['id']; ?>"
                                                  data-paciente-nombre="<?php echo htmlspecialchars($paciente['nombre']); ?>"
                                                  data-fecha="<?php echo $fecha; ?>"
                                                  data-hora="<?php echo $hora; ?>"
                                                  data-motivo="<?php echo htmlspecialchars($motivo); ?>"
                                                  data-estado="<?php echo $estado; ?>">
                                                <i class="bi bi-calendar-check me-1"></i>
                                                <?php echo date('d/m', strtotime($fecha)); ?> 
                                                <?php echo date('H:i', strtotime($hora)); ?>
                                                <?php if ($estado !== 'pendiente'): ?>
                                                <small>(<?php echo $estado; ?>)</small>
                                                <?php endif; ?>
                                            </span>
                                            <?php } endforeach; ?>
                                        </div>
                                        <?php else: ?>
                                        <span class="text-muted">Sin citas pendientes</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="historial.php?paciente_id=<?php echo $paciente['id']; ?>" 
                                               class="btn btn-outline-info" title="Historial Médico">
                                                <i class="bi bi-file-medical"></i>
                                            </a>
                                            <a href="agenda.php?paciente_id=<?php echo $paciente['id']; ?>" 
                                               class="btn btn-outline-success" title="Agendar Nueva Cita">
                                                <i class="bi bi-calendar-plus"></i>
                                            </a>
                                            <a href="consultas.php?paciente_id=<?php echo $paciente['id']; ?>" 
                                               class="btn btn-outline-primary" title="Ver Consultas">
                                                <i class="bi bi-clipboard-pulse"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="text-center py-5">
                        <i class="bi bi-people fs-1 text-muted mb-3"></i>
                        <h5 class="text-muted">No hay pacientes registrados</h5>
                        <a href="agregar_paciente.php" class="btn btn-primary mt-2">
                            <i class="bi bi-person-plus me-1"></i> Agregar Primer Paciente
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- MODAL PARA VER DETALLES DE CITA -->
    <div class="modal fade" id="citaModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title"><i class="bi bi-calendar-check me-2"></i> Detalles de Cita</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="citasContainer">
                        <!-- Las citas se cargarán aquí dinámicamente -->
                        <div class="text-center py-4">
                            <i class="bi bi-calendar3 text-muted fs-1"></i>
                            <p class="text-muted mt-2">Selecciona una cita para ver los detalles</p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Cuando se hace clic en una badge de cita
        document.querySelectorAll('.badge-cita').forEach(badge => {
            badge.addEventListener('click', function() {
                const citaId = this.getAttribute('data-cita-id');
                const pacienteId = this.getAttribute('data-paciente-id');
                const pacienteNombre = this.getAttribute('data-paciente-nombre');
                const fecha = this.getAttribute('data-fecha');
                const hora = this.getAttribute('data-hora');
                const motivo = this.getAttribute('data-motivo');
                const estado = this.getAttribute('data-estado');
                
                // Mostrar la cita en el modal
                const fechaFormateada = new Date(fecha).toLocaleDateString('es-ES');
                const horaFormateada = hora.substring(0, 5);
                
                let accionesHTML = '';
                
                if (estado === 'pendiente' || estado === 'confirmada') {
                    accionesHTML = `
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-2"></i>
                            Esta cita está ${estado === 'confirmada' ? 'confirmada' : 'pendiente'}. 
                            Puedes crear una consulta médica a partir de ella.
                        </div>
                        
                        <div class="text-center mt-4">
                            <!-- BOTÓN PARA CREAR CONSULTA (NUEVO FLUJO) -->
                            <a href="crear_consulta.php?cita_id=${citaId}&paciente_id=${pacienteId}" 
                               class="btn btn-consulta btn-lg"
                               onclick="return confirm('¿Crear consulta médica a partir de esta cita?\\n\\nSerás redirigido al formulario de consulta.')">
                                <i class="bi bi-file-medical me-2"></i>
                                Crear Consulta
                            </a>
                            
                            <a href="agenda.php?accion=cancelar&id=${citaId}" 
                               class="btn btn-outline-danger ms-2"
                               onclick="return confirm('¿Cancelar esta cita?')">
                                <i class="bi bi-x-circle me-1"></i> Cancelar Cita
                            </a>
                            
                            <a href="historial.php?paciente_id=${pacienteId}" 
                               class="btn btn-outline-info ms-2">
                                <i class="bi bi-file-medical me-1"></i> Ver Historial
                            </a>
                        </div>
                    `;
                } else if (estado === 'completada') {
                    accionesHTML = `
                        <div class="alert alert-success">
                            <i class="bi bi-check-circle me-2"></i>
                            Esta cita ya fue completada y convertida en consulta.
                        </div>
                        
                        <div class="text-center mt-4">
                            <a href="historial.php?paciente_id=${pacienteId}" 
                               class="btn btn-info">
                                <i class="bi bi-file-medical me-1"></i> Ver Historial del Paciente
                            </a>
                            <a href="consultas.php?paciente_id=${pacienteId}" 
                               class="btn btn-outline-success ms-2">
                                <i class="bi bi-clipboard-pulse me-1"></i> Ver Consultas
                            </a>
                        </div>
                    `;
                } else if (estado === 'cancelada') {
                    accionesHTML = `
                        <div class="alert alert-danger">
                            <i class="bi bi-x-circle me-2"></i>
                            Esta cita fue cancelada.
                        </div>
                    `;
                }
                
                document.getElementById('citasContainer').innerHTML = `
                    <div class="modal-cita ${estado === 'completada' ? 'completada' : estado === 'confirmada' ? 'confirmada' : ''}">
                        <h6>Paciente: <strong>${pacienteNombre}</strong></h6>
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <p><i class="bi bi-calendar me-2"></i> <strong>Fecha:</strong> ${fechaFormateada}</p>
                                <p><i class="bi bi-clock me-2"></i> <strong>Hora:</strong> ${horaFormateada}</p>
                                <p><i class="bi bi-info-circle me-2"></i> <strong>Estado:</strong> 
                                    <span class="badge ${estado === 'pendiente' ? 'bg-warning' : 
                                                         estado === 'confirmada' ? 'bg-info' : 
                                                         estado === 'completada' ? 'bg-success' : 'bg-danger'}">
                                        ${estado}
                                    </span>
                                </p>
                            </div>
                            <div class="col-md-6">
                                <p><i class="bi bi-file-text me-2"></i> <strong>Motivo:</strong></p>
                                <p class="ps-3">${motivo}</p>
                            </div>
                        </div>
                        
                        <hr>
                        
                        ${accionesHTML}
                    </div>
                `;
            });
        });
        
        // Auto-cerrar alertas después de 5 segundos
        setTimeout(() => {
            document.querySelectorAll('.alert').forEach(alert => {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
    </script>
</body>
</html>