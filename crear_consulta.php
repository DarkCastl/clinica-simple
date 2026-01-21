<?php
// crear_consulta.php - FORMULARIO PARA CREAR CONSULTA DESDE CITA
session_start();
require_once 'config.php';

// Verificar sesión
if (!isset($_SESSION['logueado']) || $_SESSION['logueado'] !== true) {
    header('Location: index.php');
    exit;
}

$cita_id = $_GET['cita_id'] ?? 0;
$paciente_id = $_GET['paciente_id'] ?? 0;

if (!$cita_id || !$paciente_id) {
    header('Location: agenda.php');
    exit;
}

// Obtener datos de la cita y paciente - INCLUYENDO doctor_id
$sql = "SELECT a.*, p.nombre as paciente_nombre, p.telefono, p.email, 
               u.nombre as doctor_nombre, u.id as doctor_id
        FROM agenda a 
        JOIN pacientes p ON a.paciente_id = p.id 
        LEFT JOIN usuarios u ON a.doctor_id = u.id
        WHERE a.id = ? AND a.paciente_id = ? 
        AND (a.estado = 'pendiente' OR a.estado = 'confirmada')";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("ii", $cita_id, $paciente_id);
$stmt->execute();
$result = $stmt->get_result();
$cita = $result->fetch_assoc();
$stmt->close();

if (!$cita) {
    echo "<script>alert('Cita no encontrada, ya fue atendida o está cancelada'); window.location='agenda.php';</script>";
    exit;
}

$mensaje = '';

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_consulta'])) {
    $fecha_consulta = $_POST['fecha_consulta'];
    $motivo = $_POST['motivo'];
    $diagnostico = $_POST['diagnostico'];
    $tratamiento = $_POST['tratamiento'];
    $medicamentos = $_POST['medicamentos'];
    $indicaciones = $_POST['indicaciones'];
    $observaciones = $_POST['observaciones'];
    
    // Iniciar transacción
    $conexion->begin_transaction();
    
    try {
        // 1. Primero, verificar si la tabla se llama 'historial' o 'historial_consultas'
        // Puedes verificar así:
        $tabla_historial = 'historial'; // Cambia esto según tu BD
        
        // Intentar con 'historial' primero, si no funciona, probar con 'historial_consultas'
        $sql_check = "SHOW TABLES LIKE 'historial_consultas'";
        $result_check = $conexion->query($sql_check);
        if ($result_check->num_rows > 0) {
            $tabla_historial = 'historial_consultas';
        }
        
        // 2. Insertar en historial (ajusta según tu estructura real)
        if ($tabla_historial == 'historial') {
            $sql_historial = "INSERT INTO historial 
                             (paciente_id, fecha, motivo, diagnostico, tratamiento, medicamentos, indicaciones, observaciones) 
                             VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conexion->prepare($sql_historial);
            $stmt->bind_param("isssssss", 
                $paciente_id, 
                $fecha_consulta,
                $motivo,
                $diagnostico,
                $tratamiento,
                $medicamentos,
                $indicaciones,
                $observaciones
            );
        } else {
            // Para historial_consultas con doctor_id
            $sql_historial = "INSERT INTO historial_consultas 
                             (paciente_id, doctor_id, fecha_consulta, motivo_consulta, 
                              diagnostico, tratamiento, medicamentos, indicaciones, observaciones, agenda_id) 
                             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conexion->prepare($sql_historial);
            $stmt->bind_param("iisssssssi", 
                $paciente_id, 
                $cita['doctor_id'],
                $fecha_consulta,
                $motivo,
                $diagnostico,
                $tratamiento,
                $medicamentos,
                $indicaciones,
                $observaciones,
                $cita_id
            );
        }
        
        if (!$stmt->execute()) {
            throw new Exception("Error al crear consulta: " . $stmt->error);
        }
        
        $historial_id = $conexion->insert_id;
        $stmt->close();
        
        // 3. Actualizar agenda (marcar como completada)
        // Si tu tabla agenda tiene columna historial_id, úsala
        $sql_agenda = "UPDATE agenda SET estado = 'completada' WHERE id = ?";
        
        $stmt = $conexion->prepare($sql_agenda);
        $stmt->bind_param("i", $cita_id);
        
        if (!$stmt->execute()) {
            throw new Exception("Error al actualizar cita: " . $stmt->error);
        }
        $stmt->close();
        
        // Confirmar transacción
        $conexion->commit();
        
        // Mostrar mensaje de éxito
        $mensaje = '<div class="alert alert-success">✅ Consulta creada exitosamente</div>';
        
        // Redirigir después de 2 segundos
        echo '<script>
            setTimeout(function() {
                window.location.href = "historial.php?paciente_id=' . $paciente_id . '&mensaje=consulta_creada";
            }, 2000);
        </script>';
        
    } catch (Exception $e) {
        $conexion->rollback();
        $mensaje = '<div class="alert alert-danger">❌ Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Consulta - Clínica Simple</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .info-box { background: #e8f4fd; border-left: 4px solid #3498db; padding: 1rem; border-radius: 5px; }
        .required::after { content: " *"; color: red; }
    </style>
</head>
<body>
    <?php include 'menu_lateral.php'; ?>
    
    <div id="main-content">
        <div class="container-fluid mt-3">
            <div class="row">
                <div class="col-12">
                    <!-- HEADER -->
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h1 class="h3 mb-1"><i class="bi bi-file-medical me-2"></i> Crear Consulta Médica</h1>
                            <p class="text-muted mb-0">Complete los datos de la consulta</p>
                        </div>
                        <a href="agenda.php" class="btn btn-secondary">
                            <i class="bi bi-arrow-left me-1"></i> Volver a Agenda
                        </a>
                    </div>
                    
                    <?php echo $mensaje; ?>
                    
                    <!-- INFORMACIÓN DE LA CITA -->
                    <div class="card mb-4">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0"><i class="bi bi-info-circle me-2"></i> Información de la Cita</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3">
                                    <p><strong>Paciente:</strong><br>
                                    <?php echo htmlspecialchars($cita['paciente_nombre']); ?></p>
                                </div>
                                <div class="col-md-3">
                                    <p><strong>Doctor:</strong><br>
                                    <?php echo htmlspecialchars($cita['doctor_nombre'] ?? 'No asignado'); ?></p>
                                </div>
                                <div class="col-md-3">
                                    <p><strong>Fecha de Cita:</strong><br>
                                    <?php echo date('d/m/Y', strtotime($cita['fecha'])); ?></p>
                                </div>
                                <div class="col-md-3">
                                    <p><strong>Hora:</strong><br>
                                    <?php echo date('H:i', strtotime($cita['hora'])); ?></p>
                                </div>
                                <div class="col-12">
                                    <p><strong>Motivo Original:</strong><br>
                                    <?php echo htmlspecialchars($cita['motivo']); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- FORMULARIO DE CONSULTA -->
                    <form method="POST" action="">
                        <input type="hidden" name="cita_id" value="<?php echo $cita_id; ?>">
                        <input type="hidden" name="paciente_id" value="<?php echo $paciente_id; ?>">
                        
                        <div class="card">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0"><i class="bi bi-clipboard2-pulse me-2"></i> Datos de la Consulta</h5>
                            </div>
                            <div class="card-body">
                                <!-- Fecha de Consulta -->
                                <div class="row mb-3">
                                    <div class="col-md-4">
                                        <label class="form-label required">Fecha de Consulta</label>
                                        <input type="date" class="form-control" name="fecha_consulta" 
                                               value="<?php echo date('Y-m-d'); ?>" required>
                                    </div>
                                </div>
                                
                                <!-- Motivo -->
                                <div class="mb-3">
                                    <label class="form-label required">Motivo de Consulta</label>
                                    <textarea class="form-control" name="motivo" rows="3" required 
                                              placeholder="Describa el motivo de la consulta..."><?php echo htmlspecialchars($cita['motivo']); ?></textarea>
                                </div>
                                
                                <!-- Diagnóstico -->
                                <div class="mb-3">
                                    <label class="form-label required">Diagnóstico</label>
                                    <textarea class="form-control" name="diagnostico" rows="3" required 
                                              placeholder="Escriba el diagnóstico médico..."></textarea>
                                </div>
                                
                                <!-- Tratamiento -->
                                <div class="mb-3">
                                    <label class="form-label required">Tratamiento Indicado</label>
                                    <textarea class="form-control" name="tratamiento" rows="3" required 
                                              placeholder="Describa el tratamiento indicado..."></textarea>
                                </div>
                                
                                <!-- Medicamentos -->
                                <div class="mb-3">
                                    <label class="form-label">Medicamentos Recetados</label>
                                    <textarea class="form-control" name="medicamentos" rows="2" 
                                              placeholder="Lista de medicamentos, dosis, frecuencia..."></textarea>
                                </div>
                                
                                <!-- Indicaciones -->
                                <div class="mb-3">
                                    <label class="form-label">Indicaciones al Paciente</label>
                                    <textarea class="form-control" name="indicaciones" rows="2" 
                                              placeholder="Recomendaciones, cuidados, reposo..."></textarea>
                                </div>
                                
                                <!-- Observaciones -->
                                <div class="mb-3">
                                    <label class="form-label">Observaciones Adicionales</label>
                                    <textarea class="form-control" name="observaciones" rows="2" 
                                              placeholder="Notas adicionales..."></textarea>
                                </div>
                            </div>
                        </div>
                        
                        <!-- BOTONES -->
                        <div class="mt-4">
                            <div class="d-flex justify-content-between">
                                <a href="agenda.php" class="btn btn-outline-secondary">
                                    <i class="bi bi-x-circle me-1"></i> Cancelar
                                </a>
                                <button type="submit" name="guardar_consulta" class="btn btn-success btn-lg">
                                    <i class="bi bi-save me-1"></i> Guardar Consulta
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-llenar fecha actual
        document.querySelector('input[name="fecha_consulta"]').value = new Date().toISOString().split('T')[0];
        
        // Validación antes de enviar
        document.querySelector('form').addEventListener('submit', function(e) {
            const diagnostico = document.querySelector('textarea[name="diagnostico"]').value.trim();
            const tratamiento = document.querySelector('textarea[name="tratamiento"]').value.trim();
            
            if (!diagnostico || !tratamiento) {
                e.preventDefault();
                alert('Por favor complete el diagnóstico y tratamiento antes de guardar.');
                return false;
            }
            
            if (!confirm('¿Guardar consulta médica?\n\nEsta acción marcará la cita como completada.')) {
                e.preventDefault();
                return false;
            }
            
            return true;
        });
    </script>
</body>
</html>