<?php
// historial.php - VERSIÓN CORREGIDA (LISTA TODOS LOS PACIENTES)
require_once 'config.php';
verificarSesion();

$paciente_id = isset($_GET['paciente_id']) ? intval($_GET['paciente_id']) : 0;
$accion = isset($_GET['accion']) ? $_GET['accion'] : '';
$buscar = isset($_GET['buscar']) ? trim($_GET['buscar']) : '';

// Mensaje para mostrar
$mensaje = '';

// Obtener lista de pacientes con su último historial
$sql_pacientes = "SELECT 
    p.id, p.nombre, p.dui, p.telefono, p.email, p.fecha_nacimiento,
    MAX(h.fecha) as ultima_consulta,
    COUNT(h.id) as total_consultas
    FROM pacientes p
    LEFT JOIN historial h ON p.id = h.paciente_id
    WHERE 1=1";

$params = [];
$types = '';

// Aplicar filtro de búsqueda si existe
if (!empty($buscar)) {
    $sql_pacientes .= " AND (p.nombre LIKE ? OR p.dui LIKE ? OR p.telefono LIKE ?)";
    $search_term = "%$buscar%";
    $params = array_fill(0, 3, $search_term);
    $types = 'sss';
}

$sql_pacientes .= " GROUP BY p.id ORDER BY p.nombre ASC";

// Preparar y ejecutar consulta de pacientes
$stmt_pacientes = $conexion->prepare($sql_pacientes);
if (!empty($params)) {
    $stmt_pacientes->bind_param($types, ...$params);
}
$stmt_pacientes->execute();
$result_pacientes = $stmt_pacientes->get_result();

// Obtener datos del paciente específico si está seleccionado
$paciente = null;
$historial = null;
$stats = null;

if ($paciente_id > 0) {
    // Obtener información del paciente
    $stmt = $conexion->prepare("SELECT * FROM pacientes WHERE id = ?");
    $stmt->bind_param("i", $paciente_id);
    $stmt->execute();
    $paciente = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if (!$paciente) {
        $mensaje = '<div class="alert alert-danger">
            <i class="bi bi-exclamation-triangle me-2"></i> Paciente no encontrado
        </div>';
        $paciente_id = 0;
    } else {
        // Obtener historial del paciente
        $sql_historial = "SELECT h.*, 
            DATE_FORMAT(h.fecha, '%d/%m/%Y') as fecha_formateada,
            TIME_FORMAT(h.fecha_hora_creacion, '%H:%i') as hora_registro,
            a.fecha as fecha_cita, a.hora as hora_cita
            FROM historial h
            LEFT JOIN agenda a ON h.agenda_id = a.id
            WHERE h.paciente_id = ? 
            ORDER BY h.fecha DESC, h.id DESC";
        
        $stmt = $conexion->prepare($sql_historial);
        $stmt->bind_param("i", $paciente_id);
        $stmt->execute();
        $historial = $stmt->get_result();
        $stmt->close();
        
        // Obtener estadísticas del paciente
        $stmt = $conexion->prepare("
            SELECT 
                COUNT(*) as total_consultas,
                MAX(fecha) as ultima_consulta,
                MIN(fecha) as primera_consulta
            FROM historial 
            WHERE paciente_id = ?
        ");
        $stmt->bind_param("i", $paciente_id);
        $stmt->execute();
        $stats = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    }
}

// Procesar acciones CRUD para historial
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // GUARDAR NUEVO HISTORIAL
    if (isset($_POST['guardar_historial'])) {
        $paciente_id_post = intval($_POST['paciente_id']);
        $fecha = $_POST['fecha_consulta'];
        $motivo = trim($_POST['motivo']);
        $diagnostico = trim($_POST['diagnostico']);
        $tratamiento = trim($_POST['tratamiento']);
        $medicamentos = trim($_POST['medicamentos']);
        $indicaciones = trim($_POST['indicaciones']);
        $observaciones = trim($_POST['observaciones']);
        $agenda_id = isset($_POST['agenda_id']) ? intval($_POST['agenda_id']) : null;
        
        if ($paciente_id_post > 0 && !empty($fecha) && !empty($motivo)) {
            // Preparar consulta con todos los campos
            $sql = "INSERT INTO historial (
                paciente_id, fecha, motivo, diagnostico, tratamiento, 
                medicamentos_recetados, indicaciones_paciente, observaciones,
                agenda_id, usuario_id
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $conexion->prepare($sql);
            $usuario_id = $_SESSION['usuario_id'] ?? null;
            
            $stmt->bind_param(
                "isssssssii", 
                $paciente_id_post, 
                $fecha,
                $motivo,
                $diagnostico,
                $tratamiento,
                $medicamentos,
                $indicaciones,
                $observaciones,
                $agenda_id,
                $usuario_id
            );
            
            if ($stmt->execute()) {
                $mensaje = '<div class="alert alert-success alert-dismissible fade show">
                    <i class="bi bi-check-circle me-2"></i> Consulta registrada correctamente
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>';
                
                // Refrescar datos del paciente
                if ($paciente_id == $paciente_id_post) {
                    $historial = $conexion->query("
                        SELECT h.*, 
                        DATE_FORMAT(h.fecha, '%d/%m/%Y') as fecha_formateada,
                        TIME_FORMAT(h.fecha_hora_creacion, '%H:%i') as hora_registro
                        FROM historial h
                        WHERE h.paciente_id = $paciente_id 
                        ORDER BY h.fecha DESC, h.id DESC
                    ");
                    
                    // Actualizar estadísticas
                    $stats = $conexion->query("
                        SELECT 
                            COUNT(*) as total_consultas,
                            MAX(fecha) as ultima_consulta,
                            MIN(fecha) as primera_consulta
                        FROM historial 
                        WHERE paciente_id = $paciente_id
                    ")->fetch_assoc();
                }
            } else {
                $mensaje = '<div class="alert alert-danger">
                    <i class="bi bi-x-circle me-2"></i> Error: ' . $stmt->error . '
                </div>';
            }
            $stmt->close();
        } else {
            $mensaje = '<div class="alert alert-warning">
                <i class="bi bi-exclamation-triangle me-2"></i> Faltan datos requeridos
            </div>';
        }
    }
    
    // ACTUALIZAR HISTORIAL
    if (isset($_POST['actualizar_historial'])) {
        $historial_id = intval($_POST['historial_id']);
        $fecha = $_POST['fecha_consulta'];
        $motivo = trim($_POST['motivo']);
        $diagnostico = trim($_POST['diagnostico']);
        $tratamiento = trim($_POST['tratamiento']);
        $medicamentos = trim($_POST['medicamentos']);
        $indicaciones = trim($_POST['indicaciones']);
        $observaciones = trim($_POST['observaciones']);
        
        $sql = "UPDATE historial SET 
                fecha = ?, motivo = ?, diagnostico = ?, tratamiento = ?, 
                medicamentos_recetados = ?, indicaciones_paciente = ?, observaciones = ?
                WHERE id = ?";
        
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param(
            "sssssssi",
            $fecha, $motivo, $diagnostico, $tratamiento,
            $medicamentos, $indicaciones, $observaciones,
            $historial_id
        );
        
        if ($stmt->execute()) {
            $mensaje = '<div class="alert alert-success alert-dismissible fade show">
                <i class="bi bi-check-circle me-2"></i> Consulta actualizada correctamente
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>';
            
            // Refrescar historial
            if ($paciente_id > 0) {
                $historial = $conexion->query("
                    SELECT h.*, 
                    DATE_FORMAT(h.fecha, '%d/%m/%Y') as fecha_formateada,
                    TIME_FORMAT(h.fecha_hora_creacion, '%H:%i') as hora_registro
                    FROM historial h
                    WHERE h.paciente_id = $paciente_id 
                    ORDER BY h.fecha DESC, h.id DESC
                ");
            }
        } else {
            $mensaje = '<div class="alert alert-danger">
                <i class="bi bi-x-circle me-2"></i> Error al actualizar: ' . $stmt->error . '
            </div>';
        }
        $stmt->close();
    }
}

// ELIMINAR HISTORIAL
if ($accion === 'eliminar' && isset($_GET['id'])) {
    $historial_id = intval($_GET['id']);
    $confirmado = isset($_GET['confirmado']) ? $_GET['confirmado'] === 'si' : false;
    
    if ($confirmado) {
        if ($conexion->query("DELETE FROM historial WHERE id = $historial_id")) {
            $mensaje = '<div class="alert alert-success alert-dismissible fade show">
                <i class="bi bi-check-circle me-2"></i> Registro eliminado correctamente
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>';
            
            // Refrescar historial
            if ($paciente_id > 0) {
                $historial = $conexion->query("
                    SELECT h.*, 
                    DATE_FORMAT(h.fecha, '%d/%m/%Y') as fecha_formateada,
                    TIME_FORMAT(h.fecha_hora_creacion, '%H:%i') as hora_registro
                    FROM historial h
                    WHERE h.paciente_id = $paciente_id 
                    ORDER BY h.fecha DESC, h.id DESC
                ");
                
                // Actualizar estadísticas
                $stats = $conexion->query("
                    SELECT 
                        COUNT(*) as total_consultas,
                        MAX(fecha) as ultima_consulta,
                        MIN(fecha) as primera_consulta
                    FROM historial 
                    WHERE paciente_id = $paciente_id
                ")->fetch_assoc();
            }
        } else {
            $mensaje = '<div class="alert alert-danger">
                <i class="bi bi-x-circle me-2"></i> Error al eliminar: ' . $conexion->error . '
            </div>';
        }
    } else {
        // Mostrar confirmación
        echo '<script>
            if (confirm("¿Está seguro de eliminar este registro de consulta?")) {
                window.location.href = "historial.php?paciente_id=' . $paciente_id . '&accion=eliminar&id=' . $historial_id . '&confirmado=si";
            } else {
                window.location.href = "historial.php?paciente_id=' . $paciente_id . '";
            }
        </script>';
        exit;
    }
}

// EDITAR HISTORIAL
$registro_editar = null;
if ($accion === 'editar' && isset($_GET['id'])) {
    $historial_id = intval($_GET['id']);
    $registro_editar = $conexion->query("SELECT * FROM historial WHERE id = $historial_id")->fetch_assoc();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historial Médico - Clínica Simple</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .paciente-card {
            border-left: 4px solid #3498db;
            border-radius: 8px;
            margin-bottom: 1rem;
            background: white;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .paciente-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            border-left-color: #2980b9;
        }
        
        .paciente-seleccionado {
            background: #e8f4fd;
            border-left-color: #2c3e50;
        }
        
        .historial-card {
            border-left: 4px solid #27ae60;
            border-radius: 8px;
            margin-bottom: 1rem;
            background: white;
            transition: all 0.3s ease;
        }
        
        .historial-card:hover {
            transform: translateY(-1px);
            box-shadow: 0 3px 10px rgba(0,0,0,0.08);
        }
        
        .sin-historial {
            border-left-color: #95a5a6;
            background: #f8f9fa;
        }
        
        .campo-label {
            font-weight: 600;
            color: #2c3e50;
        }
        
        .fecha-badge {
            font-size: 0.8rem;
        }
        
        .editando {
            border: 2px solid #3498db;
            background: #e8f4fd;
        }
        
        .stats-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1rem;
        }
        
        .search-box {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 3px 10px rgba(0,0,0,0.05);
        }
        
        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
            color: #95a5a6;
        }
        
        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <?php include 'menu_lateral.php'; ?>
    
    <div id="main-content">
        <div class="container-fluid mt-3">
            
            <!-- Mensajes -->
            <?php echo $mensaje; ?>
            
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-1">
                        <i class="bi bi-file-medical me-2"></i>
                        Historial Médico
                    </h1>
                    <p class="text-muted mb-0">Gestión completa de historiales médicos de pacientes</p>
                </div>
                <div>
                    <a href="pacientes.php" class="btn btn-outline-secondary">
                        <i class="bi bi-people me-1"></i> Pacientes
                    </a>
                    <a href="agenda.php" class="btn btn-outline-primary ms-2">
                        <i class="bi bi-calendar me-1"></i> Agenda
                    </a>
                </div>
            </div>
            
            <!-- Barra de búsqueda -->
            <div class="search-box">
                <form method="GET" class="row g-3">
                    <div class="col-md-8">
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="bi bi-search"></i>
                            </span>
                            <input type="text" class="form-control" name="buscar" 
                                   placeholder="Buscar paciente por nombre, DUI o teléfono..." 
                                   value="<?php echo htmlspecialchars($buscar); ?>">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-search me-1"></i> Buscar
                            </button>
                            <?php if (!empty($buscar)): ?>
                            <a href="historial.php" class="btn btn-outline-secondary">
                                <i class="bi bi-x-circle me-1"></i> Limpiar
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </form>
            </div>
            
            <div class="row">
                <!-- Lista de pacientes -->
                <div class="col-lg-5 mb-4">
                    <div class="card">
                        <div class="card-header bg-light">
                            <h5 class="mb-0">
                                <i class="bi bi-people me-2"></i>
                                Lista de Pacientes
                                <span class="badge bg-primary float-end"><?php echo $result_pacientes->num_rows; ?></span>
                            </h5>
                        </div>
                        <div class="card-body" style="max-height: 700px; overflow-y: auto;">
                            <?php if ($result_pacientes->num_rows > 0): ?>
                                <?php while($paciente_lista = $result_pacientes->fetch_assoc()): 
                                    $esta_seleccionado = $paciente && $paciente['id'] == $paciente_lista['id'];
                                    $tiene_historial = $paciente_lista['total_consultas'] > 0;
                                    
                                    // Calcular edad si tiene fecha de nacimiento
                                    $edad = '';
                                    if ($paciente_lista['fecha_nacimiento']) {
                                        $fecha_nac = new DateTime($paciente_lista['fecha_nacimiento']);
                                        $hoy = new DateTime();
                                        $edad = $hoy->diff($fecha_nac)->y . ' años';
                                    }
                                ?>
                                <div class="paciente-card p-3 <?php echo $esta_seleccionado ? 'paciente-seleccionado' : ''; ?>" 
                                     onclick="window.location.href='historial.php?paciente_id=<?php echo $paciente_lista['id']; ?>'">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div class="flex-grow-1">
                                            <h6 class="mb-1 fw-semibold"><?php echo htmlspecialchars($paciente_lista['nombre']); ?></h6>
                                            <div class="small text-muted">
                                                <div>
                                                    <i class="bi bi-person-badge me-1"></i>
                                                    <?php echo $paciente_lista['dui'] ? htmlspecialchars($paciente_lista['dui']) : 'Sin DUI'; ?>
                                                </div>
                                                <div>
                                                    <i class="bi bi-telephone me-1"></i>
                                                    <?php echo $paciente_lista['telefono'] ? htmlspecialchars($paciente_lista['telefono']) : 'Sin teléfono'; ?>
                                                </div>
                                                <?php if ($edad): ?>
                                                <div>
                                                    <i class="bi bi-calendar me-1"></i>
                                                    <?php echo $edad; ?>
                                                </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="text-end">
                                            <span class="badge <?php echo $tiene_historial ? 'bg-success' : 'bg-secondary'; ?>">
                                                <i class="bi bi-file-medical me-1"></i>
                                                <?php echo $paciente_lista['total_consultas']; ?>
                                            </span>
                                            <?php if ($paciente_lista['ultima_consulta']): ?>
                                            <div class="small text-muted mt-1">
                                                <i class="bi bi-clock-history"></i>
                                                <?php echo date('d/m/Y', strtotime($paciente_lista['ultima_consulta'])); ?>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <?php if ($esta_seleccionado): ?>
                                    <div class="mt-2">
                                        <div class="progress" style="height: 5px;">
                                            <div class="progress-bar bg-primary" style="width: 100%"></div>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <div class="empty-state">
                                    <i class="bi bi-people"></i>
                                    <h5>No hay pacientes registrados</h5>
                                    <p class="mb-3">Registra nuevos pacientes para comenzar</p>
                                    <a href="pacientes.php?accion=nuevo" class="btn btn-primary">
                                        <i class="bi bi-person-plus me-1"></i> Registrar Paciente
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Panel de historial y formulario -->
                <div class="col-lg-7">
                    <?php if ($paciente): ?>
                        <!-- Estadísticas del paciente seleccionado -->
                        <div class="stats-card">
                            <div class="row">
                                <div class="col-md-8">
                                    <h4 class="mb-2"><?php echo htmlspecialchars($paciente['nombre']); ?></h4>
                                    <p class="mb-0">
                                        <?php if ($paciente['dui']): ?>
                                        <i class="bi bi-person-badge me-1"></i> <?php echo htmlspecialchars($paciente['dui']); ?> | 
                                        <?php endif; ?>
                                        <?php if ($paciente['telefono']): ?>
                                        <i class="bi bi-telephone me-1"></i> <?php echo htmlspecialchars($paciente['telefono']); ?>
                                        <?php endif; ?>
                                    </p>
                                </div>
                                <div class="col-md-4 text-end">
                                    <div class="display-4 fw-bold"><?php echo $stats['total_consultas'] ?? 0; ?></div>
                                    <small>Consultas registradas</small>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Formulario de nueva consulta -->
                        <div class="card <?php echo $registro_editar ? 'editando' : ''; ?> mb-4">
                            <div class="card-header <?php echo $registro_editar ? 'bg-warning' : 'bg-primary'; ?> text-white">
                                <h5 class="mb-0">
                                    <i class="bi bi-<?php echo $registro_editar ? 'pencil' : 'plus'; ?> me-2"></i>
                                    <?php echo $registro_editar ? 'Editar Consulta' : 'Nueva Consulta Médica'; ?>
                                </h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" id="formHistorial">
                                    <input type="hidden" name="paciente_id" value="<?php echo $paciente_id; ?>">
                                    
                                    <?php if ($registro_editar): ?>
                                    <input type="hidden" name="historial_id" value="<?php echo $registro_editar['id']; ?>">
                                    <?php endif; ?>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label campo-label">Fecha de Consulta *</label>
                                                <input type="date" name="fecha_consulta" class="form-control" 
                                                       value="<?php 
                                                       if ($registro_editar) echo $registro_editar['fecha'];
                                                       else echo date('Y-m-d'); 
                                                       ?>" required>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label class="form-label campo-label">Motivo de Consulta *</label>
                                                <textarea name="motivo" class="form-control" rows="2" required 
                                                          placeholder="Describa el motivo de la consulta..."><?php 
                                                if ($registro_editar) echo htmlspecialchars($registro_editar['motivo']);
                                                ?></textarea>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label class="form-label campo-label">Diagnóstico</label>
                                                <textarea name="diagnostico" class="form-control" rows="2" 
                                                          placeholder="Escriba el diagnóstico médico..."><?php 
                                                if ($registro_editar) echo htmlspecialchars($registro_editar['diagnostico']);
                                                ?></textarea>
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label campo-label">Tratamiento Indicado</label>
                                                <textarea name="tratamiento" class="form-control" rows="2" 
                                                          placeholder="Describa el tratamiento indicado..."><?php 
                                                if ($registro_editar) echo htmlspecialchars($registro_editar['tratamiento']);
                                                ?></textarea>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label class="form-label campo-label">Medicamentos Recetados</label>
                                                <textarea name="medicamentos" class="form-control" rows="2" 
                                                          placeholder="Lista de medicamentos, dosis, frecuencia..."><?php 
                                                if ($registro_editar) echo htmlspecialchars($registro_editar['medicamentos_recetados']);
                                                ?></textarea>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label class="form-label campo-label">Indicaciones al Paciente</label>
                                                <textarea name="indicaciones" class="form-control" rows="2" 
                                                          placeholder="Recomendaciones, cuidados, reposo..."><?php 
                                                if ($registro_editar) echo htmlspecialchars($registro_editar['indicaciones_paciente']);
                                                ?></textarea>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label campo-label">Observaciones Adicionales</label>
                                        <textarea name="observaciones" class="form-control" rows="2" 
                                                  placeholder="Notas adicionales..."><?php 
                                        if ($registro_editar) echo htmlspecialchars($registro_editar['observaciones']);
                                        ?></textarea>
                                    </div>
                                    
                                    <div class="text-center mt-4">
                                        <?php if ($registro_editar): ?>
                                        <button type="submit" name="actualizar_historial" class="btn btn-warning btn-lg">
                                            <i class="bi bi-save me-1"></i> Actualizar Consulta
                                        </button>
                                        <a href="historial.php?paciente_id=<?php echo $paciente_id; ?>" class="btn btn-secondary ms-2">
                                            <i class="bi bi-x-circle me-1"></i> Cancelar
                                        </a>
                                        <?php else: ?>
                                        <button type="submit" name="guardar_historial" class="btn btn-success btn-lg">
                                            <i class="bi bi-save me-1"></i> Guardar Consulta
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </form>
                            </div>
                        </div>
                        
                        <!-- Historial existente del paciente -->
                        <div class="card">
                            <div class="card-header bg-light">
                                <h5 class="mb-0">
                                    <i class="bi bi-clock-history me-2"></i>
                                    Historial de Consultas
                                    <?php if ($historial && $historial->num_rows > 0): ?>
                                    <span class="badge bg-primary float-end"><?php echo $historial->num_rows; ?></span>
                                    <?php endif; ?>
                                </h5>
                            </div>
                            <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                                <?php if (!$historial || $historial->num_rows === 0): ?>
                                <div class="empty-state py-4">
                                    <i class="bi bi-file-earmark-text"></i>
                                    <h5>No hay consultas registradas</h5>
                                    <p>Este paciente aún no tiene consultas médicas registradas</p>
                                </div>
                                <?php else: ?>
                                    <?php while($registro = $historial->fetch_assoc()): ?>
                                    <div class="historial-card p-3">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <div>
                                                <div class="d-flex align-items-center mb-1">
                                                    <span class="badge bg-primary fecha-badge me-2">
                                                        <i class="bi bi-calendar me-1"></i>
                                                        <?php echo $registro['fecha_formateada']; ?>
                                                    </span>
                                                    <?php if (!empty($registro['fecha_cita'])): ?>
                                                    <span class="badge bg-warning fecha-badge">
                                                        <i class="bi bi-calendar-check me-1"></i>
                                                        Desde agenda
                                                    </span>
                                                    <?php endif; ?>
                                                </div>
                                                <h6 class="mb-1 campo-label">Motivo:</h6>
                                                <p class="mb-2"><?php echo nl2br(htmlspecialchars($registro['motivo'])); ?></p>
                                            </div>
                                            <div class="btn-group">
                                                <a href="historial.php?paciente_id=<?php echo $paciente_id; ?>&accion=editar&id=<?php echo $registro['id']; ?>" 
                                                   class="btn btn-sm btn-outline-primary" title="Editar">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <a href="historial.php?paciente_id=<?php echo $paciente_id; ?>&accion=eliminar&id=<?php echo $registro['id']; ?>" 
                                                   class="btn btn-sm btn-outline-danger" title="Eliminar">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                            </div>
                                        </div>
                                        
                                        <?php if (!empty($registro['diagnostico'])): ?>
                                        <div class="mb-2">
                                            <h6 class="mb-1 campo-label">Diagnóstico:</h6>
                                            <p class="mb-0"><?php echo nl2br(htmlspecialchars($registro['diagnostico'])); ?></p>
                                        </div>
                                        <?php endif; ?>
                                        
                                        <?php if (!empty($registro['tratamiento'])): ?>
                                        <div class="mb-2">
                                            <h6 class="mb-1 campo-label">Tratamiento:</h6>
                                            <p class="mb-0"><?php echo nl2br(htmlspecialchars($registro['tratamiento'])); ?></p>
                                        </div>
                                        <?php endif; ?>
                                        
                                        <?php if (!empty($registro['medicamentos_recetados'])): ?>
                                        <div class="mb-2">
                                            <h6 class="mb-1 campo-label">Medicamentos:</h6>
                                            <p class="mb-0"><?php echo nl2br(htmlspecialchars($registro['medicamentos_recetados'])); ?></p>
                                        </div>
                                        <?php endif; ?>
                                        
                                        <div class="text-end mt-2">
                                            <small class="text-muted">
                                                <i class="bi bi-clock-history me-1"></i>
                                                Registrado: <?php echo $registro['hora_registro']; ?>
                                                <?php if ($registro_editar && $registro_editar['id'] == $registro['id']): ?>
                                                <span class="badge bg-warning ms-2">Editando</span>
                                                <?php endif; ?>
                                            </small>
                                        </div>
                                    </div>
                                    <?php endwhile; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                    <?php else: ?>
                        <!-- Sin paciente seleccionado -->
                        <div class="card">
                            <div class="card-body text-center py-5">
                                <i class="bi bi-file-medical fs-1 text-muted"></i>
                                <h4 class="mt-3">Selecciona un paciente</h4>
                                <p class="text-muted mb-4">Haz clic en un paciente de la lista para ver y gestionar su historial médico</p>
                                <div class="row justify-content-center">
                                    <div class="col-md-6">
                                        <div class="list-group">
                                            <div class="list-group-item text-start">
                                                <i class="bi bi-check-circle-fill text-success me-2"></i>
                                                Ver historial completo de consultas
                                            </div>
                                            <div class="list-group-item text-start">
                                                <i class="bi bi-check-circle-fill text-success me-2"></i>
                                                Agregar nuevas consultas médicas
                                            </div>
                                            <div class="list-group-item text-start">
                                                <i class="bi bi-check-circle-fill text-success me-2"></i>
                                                Editar o eliminar registros existentes
                                            </div>
                                            <div class="list-group-item text-start">
                                                <i class="bi bi-check-circle-fill text-success me-2"></i>
                                                Ver estadísticas del paciente
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-cerrar alertas después de 5 segundos
        setTimeout(() => {
            document.querySelectorAll('.alert').forEach(alert => {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
        
        // Validar formulario
        document.getElementById('formHistorial')?.addEventListener('submit', function(e) {
            const motivo = this.querySelector('[name="motivo"]').value.trim();
            const fecha = this.querySelector('[name="fecha_consulta"]').value;
            
            if (!motivo) {
                e.preventDefault();
                alert('Por favor complete el motivo de la consulta');
                return false;
            }
            
            if (!fecha) {
                e.preventDefault();
                alert('Por complete la fecha de consulta');
                return false;
            }
            
            return true;
        });
        
        // Filtrar pacientes con búsqueda en tiempo real (opcional)
        const searchInput = document.querySelector('input[name="buscar"]');
        if (searchInput) {
            let searchTimeout;
            searchInput.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    if (this.value.length >= 2 || this.value.length === 0) {
                        this.form.submit();
                    }
                }, 500);
            });
        }
        
        // Scroll automático al formulario si está editando
        <?php if ($registro_editar): ?>
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelector('.editando').scrollIntoView({ behavior: 'smooth' });
        });
        <?php endif; ?>
    </script>
</body>
</html>