<?php
// obtener_citas.php - Obtener citas pendientes de un paciente
require_once 'config.php';
verificarSesion();

if (isset($_GET['paciente_id'])) {
    $paciente_id = intval($_GET['paciente_id']);
    
    // Obtener paciente
    $paciente = $conexion->query("SELECT * FROM pacientes WHERE id = $paciente_id")->fetch_assoc();
    
    // Obtener citas pendientes
    $citas = $conexion->query("
        SELECT * FROM agenda 
        WHERE paciente_id = $paciente_id 
        AND estado = 'pendiente'
        ORDER BY fecha, hora
    ");
    
    if ($citas->num_rows > 0): 
        while($cita = $citas->fetch_assoc()):
            $fecha_formateada = date('d/m/Y', strtotime($cita['fecha']));
            $hora_formateada = date('H:i', strtotime($cita['hora']));
?>
<div class="modal-cita mb-3">
    <h6>Cita #<?php echo $cita['id']; ?> - <?php echo $fecha_formateada; ?></h6>
    
    <div class="row mt-2">
        <div class="col-md-6">
            <p><i class="bi bi-clock me-2"></i> <strong>Hora:</strong> <?php echo $hora_formateada; ?></p>
            <p><i class="bi bi-clock-history me-2"></i> <strong>Duración:</strong> <?php echo $cita['duracion']; ?> min</p>
            <p><i class="bi bi-tag me-2"></i> <strong>Tipo:</strong> <?php echo ucfirst($cita['tipo']); ?></p>
        </div>
        <div class="col-md-6">
            <p><i class="bi bi-file-text me-2"></i> <strong>Motivo:</strong></p>
            <p class="ps-3"><?php echo nl2br(htmlspecialchars($cita['motivo'])); ?></p>
        </div>
    </div>
    
    <?php if (!empty($cita['notas'])): ?>
    <p><i class="bi bi-sticky me-2"></i> <strong>Notas:</strong> <?php echo htmlspecialchars($cita['notas']); ?></p>
    <?php endif; ?>
    
    <div class="text-center mt-3">
        <a href="?convertir_consulta&cita_id=<?php echo $cita['id']; ?>&paciente_id=<?php echo $paciente_id; ?>" 
           class="btn btn-success btn-sm"
           onclick="return confirm('¿Convertir esta cita en consulta médica?')">
            <i class="bi bi-check-circle me-1"></i> Convertir en Consulta
        </a>
        
        <a href="historial.php?paciente_id=<?php echo $paciente_id; ?>&from_cita=<?php echo $cita['id']; ?>" 
           class="btn btn-info btn-sm ms-2">
            <i class="bi bi-file-medical me-1"></i> Ir a Historial
        </a>
        
        <a href="agenda.php?editar_cita=<?php echo $cita['id']; ?>" 
           class="btn btn-warning btn-sm ms-2">
            <i class="bi bi-pencil me-1"></i> Editar Cita
        </a>
    </div>
</div>
<?php 
        endwhile;
    else: 
?>
<div class="alert alert-info text-center">
    <i class="bi bi-calendar-x fs-3 mb-2"></i>
    <p>Este paciente no tiene citas pendientes</p>
    <a href="agenda.php?paciente_id=<?php echo $paciente_id; ?>" class="btn btn-primary btn-sm">
        <i class="bi bi-calendar-plus me-1"></i> Agendar Nueva Cita
    </a>
</div>
<?php endif; 
}
?>