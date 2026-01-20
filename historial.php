<?php
// historial.php - VERSIÓN COMPLETA CON PDF FUNCIONAL
require_once 'config.php';
verificarSesion();

// Incluir TCPDF - DEBE ESTAR AL INICIO
require_once 'vendor/autoload.php';

$paciente_id = isset($_GET['paciente_id']) ? intval($_GET['paciente_id']) : 0;
$accion = isset($_GET['accion']) ? $_GET['accion'] : '';
$buscar = isset($_GET['buscar']) ? trim($_GET['buscar']) : '';

// ========== ACCIONES DE PDF (DEBEN ESTAR AL INICIO, ANTES DE CUALQUIER HTML) ==========

// Acción para generar PDF del historial completo
if ($accion === 'pdf' && isset($_GET['paciente_id'])) {
    generarPDFHistorial($conexion, $_GET['paciente_id']);
    exit; // IMPORTANTE: Salir para no mostrar HTML
}

// Acción para generar PDF de receta específica
if ($accion === 'pdf_receta' && isset($_GET['id'])) {
    generarPDFReceta($conexion, $_GET['id']);
    exit; // IMPORTANTE: Salir para no mostrar HTML
}

// Acción para generar PDF de receta detallada
if ($accion === 'pdf_receta_detalle' && isset($_GET['id'])) {
    generarPDFRecetaDetalle($conexion, $_GET['id']);
    exit; // IMPORTANTE: Salir para no mostrar HTML
}

// ========== FIN DE ACCIONES DE PDF ==========

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

// ========== FUNCIONES DE PDF ==========

// FUNCIÓN PARA GENERAR PDF DEL HISTORIAL COMPLETO
function generarPDFHistorial($conexion, $paciente_id) {
    $paciente_id = intval($paciente_id);
    
    // Obtener datos del paciente
    $stmt = $conexion->prepare("SELECT * FROM pacientes WHERE id = ?");
    $stmt->bind_param("i", $paciente_id);
    $stmt->execute();
    $paciente = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if (!$paciente) {
        // Crear un PDF de error
        $pdf = new TCPDF();
        $pdf->AddPage();
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->Cell(0, 10, 'ERROR: Paciente no encontrado', 0, 1, 'C');
        $pdf->Output('error_paciente.pdf', 'I');
        exit;
    }
    
    // Obtener historial del paciente
    $stmt = $conexion->prepare("SELECT * FROM historial WHERE paciente_id = ? ORDER BY fecha DESC");
    $stmt->bind_param("i", $paciente_id);
    $stmt->execute();
    $result_historial = $stmt->get_result();
    $stmt->close();
    
    // Crear PDF
    $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
    
    // Configurar documento
    $pdf->SetCreator('Clinica Simple');
    $pdf->SetAuthor('Clinica Simple');
    $pdf->SetTitle('Historial Clínico - ' . $paciente['nombre']);
    
    // Configurar márgenes
    $pdf->SetMargins(15, 20, 15);
    $pdf->SetHeaderMargin(5);
    $pdf->SetFooterMargin(10);
    $pdf->SetAutoPageBreak(TRUE, 15);
    
    // Agregar página
    $pdf->AddPage();
    
    // Logo (opcional)
    /*
    $logo_path = __DIR__ . '/../img/logo-clinica.png';
    if (file_exists($logo_path)) {
        $pdf->Image($logo_path, 15, 10, 25, '', 'PNG', '', 'T', false, 300);
    }
    */
    
    // Título
    $pdf->SetFont('helvetica', 'B', 18);
    $pdf->Cell(0, 10, 'HISTORIAL CLÍNICO', 0, 1, 'C');
    
    // Fecha de generación
    $pdf->SetFont('helvetica', 'I', 10);
    $pdf->Cell(0, 5, 'Generado el: ' . date('d/m/Y H:i:s'), 0, 1, 'C');
    
    $pdf->Ln(10);
    
    // Línea separadora
    $pdf->SetLineWidth(0.5);
    $pdf->Line(15, $pdf->GetY(), 195, $pdf->GetY());
    $pdf->Ln(5);
    
    // ===== INFORMACIÓN DEL PACIENTE =====
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->SetFillColor(240, 240, 240);
    $pdf->Cell(0, 8, 'DATOS DEL PACIENTE', 0, 1, 'L', true);
    $pdf->Ln(2);
    
    $pdf->SetFont('helvetica', '', 11);
    
    // Tabla de datos del paciente
    $data_paciente = array();
    
    if (!empty($paciente['nombre'])) {
        $data_paciente[] = array('Nombre:', htmlspecialchars($paciente['nombre']));
    }
    
    if (!empty($paciente['dui'])) {
        $data_paciente[] = array('DUI:', htmlspecialchars($paciente['dui']));
    }
    
    if (!empty($paciente['fecha_nacimiento']) && $paciente['fecha_nacimiento'] != '0000-00-00') {
        $edad = '';
        $fecha_nac = new DateTime($paciente['fecha_nacimiento']);
        $hoy = new DateTime();
        $edad_num = $hoy->diff($fecha_nac)->y;
        $data_paciente[] = array('Fecha Nacimiento:', date('d/m/Y', strtotime($paciente['fecha_nacimiento'])) . " ($edad_num años)");
    }
    
    if (!empty($paciente['telefono'])) {
        $data_paciente[] = array('Teléfono:', htmlspecialchars($paciente['telefono']));
    }
    
    if (!empty($paciente['email'])) {
        $data_paciente[] = array('Email:', htmlspecialchars($paciente['email']));
    }
    
    // Mostrar datos en dos columnas
    $col_width = 85;
    $altura_linea = 7;
    
    for ($i = 0; $i < count($data_paciente); $i += 2) {
        // Columna izquierda
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(30, $altura_linea, $data_paciente[$i][0], 0, 0);
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell($col_width - 30, $altura_linea, $data_paciente[$i][1], 0, 0);
        
        // Columna derecha (si existe)
        if (isset($data_paciente[$i + 1])) {
            $pdf->SetFont('helvetica', 'B', 10);
            $pdf->Cell(30, $altura_linea, $data_paciente[$i + 1][0], 0, 0);
            $pdf->SetFont('helvetica', '', 10);
            $pdf->Cell(0, $altura_linea, $data_paciente[$i + 1][1], 0, 1);
        } else {
            $pdf->Ln();
        }
    }
    
    $pdf->Ln(10);
    
    // ===== HISTORIAL DE CONSULTAS =====
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->SetFillColor(240, 240, 240);
    $pdf->Cell(0, 8, 'HISTORIAL DE CONSULTAS', 0, 1, 'L', true);
    $pdf->Ln(2);
    
    if ($result_historial->num_rows > 0) {
        $contador = 1;
        
        while ($registro = $result_historial->fetch_assoc()) {
            // Nueva página si es necesario (excepto la primera)
            if ($contador > 1 && $pdf->GetY() > 240) {
                $pdf->AddPage();
            }
            
            // Encabezado de consulta
            $pdf->SetFont('helvetica', 'B', 12);
            $pdf->SetFillColor(220, 230, 241);
            $pdf->Cell(0, 8, 'CONSULTA #' . $contador . ' - ' . date('d/m/Y', strtotime($registro['fecha'])), 0, 1, 'L', true);
            $pdf->Ln(2);
            
            $pdf->SetFont('helvetica', '', 10);
            
            // Motivo
            if (!empty($registro['motivo'])) {
                $pdf->SetFont('helvetica', 'B', 10);
                $pdf->Cell(25, 6, 'Motivo:', 0, 0);
                $pdf->SetFont('helvetica', '', 10);
                $pdf->MultiCell(0, 6, htmlspecialchars($registro['motivo']), 0, 'L');
                $pdf->Ln(2);
            }
            
            // Diagnóstico
            if (!empty($registro['diagnostico'])) {
                $pdf->SetFont('helvetica', 'B', 10);
                $pdf->Cell(25, 6, 'Diagnóstico:', 0, 0);
                $pdf->SetFont('helvetica', '', 10);
                $pdf->MultiCell(0, 6, htmlspecialchars($registro['diagnostico']), 0, 'L');
                $pdf->Ln(2);
            }
            
            // Tratamiento
            if (!empty($registro['tratamiento'])) {
                $pdf->SetFont('helvetica', 'B', 10);
                $pdf->Cell(25, 6, 'Tratamiento:', 0, 0);
                $pdf->SetFont('helvetica', '', 10);
                $pdf->MultiCell(0, 6, htmlspecialchars($registro['tratamiento']), 0, 'L');
                $pdf->Ln(2);
            }
            
            // Medicamentos Recetados (si hay - para recetas)
            if (!empty($registro['medicamentos_recetados'])) {
                $pdf->SetFont('helvetica', 'B', 10);
                $pdf->SetTextColor(220, 53, 69); // Rojo para destacar recetas
                $pdf->Cell(0, 6, 'RECETA MÉDICA:', 0, 1);
                $pdf->SetFont('helvetica', '', 10);
                $pdf->SetTextColor(0, 0, 0); // Volver a negro
                
                // Fondo para receta
                $pdf->SetFillColor(255, 243, 243);
                $pdf->MultiCell(0, 6, htmlspecialchars($registro['medicamentos_recetados']), 1, 'L', true);
                $pdf->Ln(2);
                
                // Indicaciones si hay
                if (!empty($registro['indicaciones_paciente'])) {
                    $pdf->SetFont('helvetica', 'B', 10);
                    $pdf->Cell(0, 6, 'Indicaciones:', 0, 1);
                    $pdf->SetFont('helvetica', '', 10);
                    $pdf->MultiCell(0, 6, htmlspecialchars($registro['indicaciones_paciente']), 0, 'L');
                    $pdf->Ln(2);
                }
            }
            
            // Observaciones
            if (!empty($registro['observaciones'])) {
                $pdf->SetFont('helvetica', 'B', 10);
                $pdf->Cell(25, 6, 'Observaciones:', 0, 0);
                $pdf->SetFont('helvetica', '', 10);
                $pdf->MultiCell(0, 6, htmlspecialchars($registro['observaciones']), 0, 'L');
                $pdf->Ln(2);
            }
            
            // Línea separadora entre consultas
            $pdf->SetLineWidth(0.2);
            $pdf->SetDrawColor(200, 200, 200);
            $pdf->Line(15, $pdf->GetY(), 195, $pdf->GetY());
            $pdf->Ln(8);
            
            $contador++;
        }
        
        // Total de consultas
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(0, 8, 'Total de consultas registradas: ' . ($contador - 1), 0, 1, 'R');
        
    } else {
        $pdf->SetFont('helvetica', 'I', 12);
        $pdf->Cell(0, 10, 'No hay consultas registradas para este paciente.', 0, 1, 'C');
        $pdf->Ln(5);
    }
    
    // Pie de página
    $pdf->SetY(-15);
    $pdf->SetFont('helvetica', 'I', 8);
    $pdf->Cell(0, 10, 'Página ' . $pdf->getAliasNumPage() . ' de ' . $pdf->getAliasNbPages(), 0, 0, 'C');
    
    // Salida del PDF
    $nombre_archivo = 'Historial_Clinico_' . 
                     (empty($paciente['dui']) ? 'Paciente_' . $paciente_id : preg_replace('/[^a-zA-Z0-9]/', '_', $paciente['dui'])) . 
                     '_' . date('Ymd_His') . '.pdf';
    
    $pdf->Output($nombre_archivo, 'I'); // 'I' = ver en navegador
}

// FUNCIÓN PARA GENERAR PDF DE RECETA ESPECÍFICA
function generarPDFReceta($conexion, $historial_id) {
    $historial_id = intval($historial_id);
    
    // Obtener datos de la consulta
    $sql = "SELECT h.*, p.nombre, p.dui, p.fecha_nacimiento, p.telefono, p.email,
                   DATE_FORMAT(h.fecha, '%d/%m/%Y') as fecha_formateada
            FROM historial h
            INNER JOIN pacientes p ON h.paciente_id = p.id
            WHERE h.id = ?";
    
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $historial_id);
    $stmt->execute();
    $consulta = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if (!$consulta) {
        // PDF de error
        $pdf = new TCPDF();
        $pdf->AddPage();
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->Cell(0, 10, 'ERROR: Consulta no encontrada', 0, 1, 'C');
        $pdf->Output('error_consulta.pdf', 'I');
        exit;
    }
    
    // Verificar si tiene información para receta
    if (empty($consulta['medicamentos_recetados']) && 
        empty($consulta['tratamiento']) && 
        empty($consulta['indicaciones_paciente'])) {
        
        // PDF de advertencia
        $pdf = new TCPDF();
        $pdf->AddPage();
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->Cell(0, 10, 'INFORMACIÓN INSUFICIENTE', 0, 1, 'C');
        $pdf->SetFont('helvetica', '', 12);
        $pdf->MultiCell(0, 8, 'Esta consulta no contiene información de receta médica (medicamentos, tratamiento o indicaciones).', 0, 'C');
        $pdf->Output('sin_receta.pdf', 'I');
        exit;
    }
    
    // Crear PDF
    $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    $pdf->AddPage();
    
    // Logo y encabezado
    $pdf->SetFont('helvetica', 'B', 20);
    $pdf->Cell(0, 15, 'RECETA MÉDICA', 0, 1, 'C');
    
    $pdf->SetFont('helvetica', '', 12);
    $pdf->Cell(0, 5, 'CLÍNICA SIMPLE', 0, 1, 'C');
    $pdf->SetFont('helvetica', 'I', 10);
    $pdf->Cell(0, 5, 'Tel: [1234-5678] • Email: info@clinicassimple.com', 0, 1, 'C');
    
    $pdf->Ln(10);
    $pdf->Line(20, $pdf->GetY(), 190, $pdf->GetY());
    $pdf->Ln(10);
    
    // Datos del paciente
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->Cell(0, 8, 'DATOS DEL PACIENTE', 0, 1);
    $pdf->SetFont('helvetica', '', 11);
    
    $pdf->Cell(40, 6, 'Paciente:', 0, 0);
    $pdf->Cell(0, 6, htmlspecialchars($consulta['nombre']), 0, 1);
    
    if (!empty($consulta['dui'])) {
        $pdf->Cell(40, 6, 'DUI:', 0, 0);
        $pdf->Cell(0, 6, htmlspecialchars($consulta['dui']), 0, 1);
    }
    
    $pdf->Cell(40, 6, 'Fecha Consulta:', 0, 0);
    $pdf->Cell(0, 6, $consulta['fecha_formateada'], 0, 1);
    
    $pdf->Ln(10);
    
    // Receta médica
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->SetTextColor(220, 53, 69);
    $pdf->Cell(0, 10, 'PRESCRIPCIÓN MÉDICA', 0, 1, 'C');
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Ln(5);
    
    // Medicamentos (lo más importante)
    if (!empty($consulta['medicamentos_recetados'])) {
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 8, 'MEDICAMENTOS PRESCRITOS:', 0, 1);
        $pdf->SetFont('helvetica', '', 11);
        
        // Formatear medicamentos con viñetas
        $medicamentos = explode("\n", $consulta['medicamentos_recetados']);
        foreach ($medicamentos as $med) {
            if (trim($med) != '') {
                $pdf->Cell(5, 6, '•', 0, 0);
                $pdf->MultiCell(0, 6, htmlspecialchars(trim($med)), 0, 'L');
            }
        }
        $pdf->Ln(5);
    }
    
    // Tratamiento
    if (!empty($consulta['tratamiento'])) {
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 8, 'TRATAMIENTO INDICADO:', 0, 1);
        $pdf->SetFont('helvetica', '', 11);
        $pdf->MultiCell(0, 6, htmlspecialchars($consulta['tratamiento']), 0, 'L');
        $pdf->Ln(5);
    }
    
    // Indicaciones
    if (!empty($consulta['indicaciones_paciente'])) {
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 8, 'INDICACIONES AL PACIENTE:', 0, 1);
        $pdf->SetFont('helvetica', '', 11);
        $pdf->MultiCell(0, 6, htmlspecialchars($consulta['indicaciones_paciente']), 0, 'L');
        $pdf->Ln(5);
    }
    
    // Si no hay nada de lo anterior, mostrar mensaje
    if (empty($consulta['medicamentos_recetados']) && 
        empty($consulta['tratamiento']) && 
        empty($consulta['indicaciones_paciente'])) {
        
        $pdf->SetFont('helvetica', 'I', 12);
        $pdf->Cell(0, 10, 'Esta consulta no incluye prescripción médica específica.', 0, 1, 'C');
    }
    
    $pdf->Ln(15);
    
    // Firma del médico
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 8, '_________________________________', 0, 1, 'C');
    $pdf->Cell(0, 6, 'Dr. [Nombre del Médico]', 0, 1, 'C');
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(0, 6, 'Cédula Profesional: [Número]', 0, 1, 'C');
    $pdf->Cell(0, 6, 'Especialidad: [Especialidad]', 0, 1, 'C');
    
    // Fecha de generación
    $pdf->SetY(-15);
    $pdf->SetFont('helvetica', 'I', 8);
    $pdf->Cell(0, 10, 'Generado el ' . date('d/m/Y H:i:s'), 0, 0, 'C');
    
    // Salida
    $nombre_archivo = 'Receta_Medica_' . 
                     (empty($consulta['dui']) ? 'Consulta_' . $historial_id : preg_replace('/[^a-zA-Z0-9]/', '_', $consulta['dui'])) . 
                     '_' . date('Ymd_His') . '.pdf';
    
    $pdf->Output($nombre_archivo, 'I');
}

// FUNCIÓN PARA PDF DE RECETA MÁS DETALLADA
function generarPDFRecetaDetalle($conexion, $historial_id) {
    $historial_id = intval($historial_id);
    
    // Obtener datos de la consulta
    $sql = "SELECT h.*, p.nombre, p.dui, p.fecha_nacimiento, p.telefono, p.email,
                   DATE_FORMAT(h.fecha, '%d/%m/%Y') as fecha_formateada
            FROM historial h
            INNER JOIN pacientes p ON h.paciente_id = p.id
            WHERE h.id = ?";
    
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $historial_id);
    $stmt->execute();
    $consulta = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if (!$consulta) {
        $pdf = new TCPDF();
        $pdf->AddPage();
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->Cell(0, 10, 'ERROR: Consulta no encontrada', 0, 1, 'C');
        $pdf->Output('error.pdf', 'I');
        exit;
    }
    
    // Crear PDF
    $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
    $pdf->SetMargins(15, 20, 15);
    $pdf->AddPage();
    
    // Título
    $pdf->SetFont('helvetica', 'B', 18);
    $pdf->Cell(0, 10, 'CONSULTA MÉDICA COMPLETA', 0, 1, 'C');
    $pdf->SetFont('helvetica', 'I', 10);
    $pdf->Cell(0, 5, 'Incluye receta médica', 0, 1, 'C');
    $pdf->Ln(10);
    
    // Datos del paciente
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->SetFillColor(240, 240, 240);
    $pdf->Cell(0, 8, 'DATOS DEL PACIENTE', 0, 1, 'L', true);
    $pdf->Ln(2);
    
    $pdf->SetFont('helvetica', '', 11);
    
    $data = array();
    $data[] = array('Paciente:', htmlspecialchars($consulta['nombre']));
    
    if (!empty($consulta['dui'])) {
        $data[] = array('DUI:', htmlspecialchars($consulta['dui']));
    }
    
    if (!empty($consulta['fecha_nacimiento']) && $consulta['fecha_nacimiento'] != '0000-00-00') {
        $data[] = array('Fecha Nacimiento:', date('d/m/Y', strtotime($consulta['fecha_nacimiento'])));
    }
    
    $data[] = array('Fecha Consulta:', $consulta['fecha_formateada']);
    
    foreach ($data as $item) {
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(40, 6, $item[0], 0, 0);
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(0, 6, $item[1], 0, 1);
    }
    
    $pdf->Ln(10);
    
    // Motivo de consulta
    if (!empty($consulta['motivo'])) {
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 8, 'MOTIVO DE CONSULTA', 0, 1);
        $pdf->SetFont('helvetica', '', 11);
        $pdf->MultiCell(0, 6, htmlspecialchars($consulta['motivo']), 0, 'L');
        $pdf->Ln(5);
    }
    
    // Diagnóstico
    if (!empty($consulta['diagnostico'])) {
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 8, 'DIAGNÓSTICO', 0, 1);
        $pdf->SetFont('helvetica', '', 11);
        $pdf->MultiCell(0, 6, htmlspecialchars($consulta['diagnostico']), 0, 'L');
        $pdf->Ln(5);
    }
    
    // ===== SECCIÓN DE RECETA (DESTACADA) =====
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->SetTextColor(220, 53, 69);
    $pdf->SetFillColor(255, 243, 243);
    $pdf->Cell(0, 10, 'PRESCRIPCIÓN MÉDICA', 0, 1, 'C', true);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Ln(5);
    
    // Medicamentos
    if (!empty($consulta['medicamentos_recetados'])) {
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 8, 'MEDICAMENTOS:', 0, 1);
        $pdf->SetFont('helvetica', '', 11);
        
        $meds = explode("\n", $consulta['medicamentos_recetados']);
        foreach ($meds as $med) {
            if (trim($med) != '') {
                $pdf->Cell(5, 6, '•', 0, 0);
                $pdf->MultiCell(0, 6, htmlspecialchars(trim($med)), 0, 'L');
            }
        }
        $pdf->Ln(5);
    }
    
    // Tratamiento
    if (!empty($consulta['tratamiento'])) {
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 8, 'TRATAMIENTO:', 0, 1);
        $pdf->SetFont('helvetica', '', 11);
        $pdf->MultiCell(0, 6, htmlspecialchars($consulta['tratamiento']), 0, 'L');
        $pdf->Ln(5);
    }
    
    // Indicaciones
    if (!empty($consulta['indicaciones_paciente'])) {
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 8, 'INDICACIONES:', 0, 1);
        $pdf->SetFont('helvetica', '', 11);
        $pdf->MultiCell(0, 6, htmlspecialchars($consulta['indicaciones_paciente']), 0, 'L');
        $pdf->Ln(5);
    }
    
    // Si no hay receta
    if (empty($consulta['medicamentos_recetados']) && 
        empty($consulta['tratamiento']) && 
        empty($consulta['indicaciones_paciente'])) {
        
        $pdf->SetFont('helvetica', 'I', 12);
        $pdf->Cell(0, 10, 'Esta consulta no incluye prescripción médica.', 0, 1, 'C');
        $pdf->Ln(5);
    }
    
    // Observaciones
    if (!empty($consulta['observaciones'])) {
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 8, 'OBSERVACIONES:', 0, 1);
        $pdf->SetFont('helvetica', '', 11);
        $pdf->MultiCell(0, 6, htmlspecialchars($consulta['observaciones']), 0, 'L');
    }
    
    $pdf->Ln(15);
    
    // Firma
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 8, '_________________________________', 0, 1, 'C');
    $pdf->Cell(0, 6, 'Dr. [Nombre del Médico]', 0, 1, 'C');
    
    // Fecha
    $pdf->SetY(-15);
    $pdf->SetFont('helvetica', 'I', 8);
    $pdf->Cell(0, 10, 'Documento generado el ' . date('d/m/Y H:i:s'), 0, 0, 'C');
    
    // Salida
    $nombre_archivo = 'Consulta_Completa_' . $historial_id . '_' . date('Ymd_His') . '.pdf';
    $pdf->Output($nombre_archivo, 'I');
}

// ========== FIN DE FUNCIONES DE PDF ==========
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historial Médico - Clínica Simple</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/print.css">
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
        
        .historial-receta {
            border-left: 4px solid #dc3545;
            background: #fff5f5;
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
        
        .btn-pdf {
            background: linear-gradient(135deg, #dc3545, #c82333);
            color: white;
            border: none;
        }
        
        .btn-pdf:hover {
            background: linear-gradient(135deg, #c82333, #bd2130);
            color: white;
        }
        
        .badge-receta {
            background: #dc3545;
            color: white;
        }
        
        .receta-medicamentos {
            background: #fff5f5;
            border-left: 4px solid #dc3545;
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
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
                    <input type="hidden" name="paciente_id" value="<?php echo $paciente_id; ?>">
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
                                <div class="col-md-6">
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
                                <div class="col-md-6 text-end">
                                    <div class="display-4 fw-bold"><?php echo $stats['total_consultas'] ?? 0; ?></div>
                                    <small>Consultas registradas</small>
                                    <div class="mt-2">
                                        <a href="historial.php?paciente_id=<?php echo $paciente_id; ?>&accion=pdf" 
                                           class="btn btn-pdf btn-sm" target="_blank">
                                            <i class="bi bi-file-pdf me-1"></i> PDF Completo
                                        </a>
                                        <button onclick="window.print()" class="btn btn-secondary btn-sm ms-2">
                                            <i class="bi bi-printer me-1"></i> Imprimir
                                        </button>
                                    </div>
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
                                                          placeholder="Lista de medicamentos, dosis, frecuencia...
Ejemplo:
- Ibuprofeno 400mg: 1 cada 8 horas
- Amoxicilina 500mg: 1 cada 12 horas"><?php 
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
                            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">
                                    <i class="bi bi-clock-history me-2"></i>
                                    Historial de Consultas
                                    <?php if ($historial && $historial->num_rows > 0): ?>
                                    <span class="badge bg-primary"><?php echo $historial->num_rows; ?></span>
                                    <?php endif; ?>
                                </h5>
                                <div class="btn-group">
                                    <button onclick="imprimirTodoHistorial()" class="btn btn-sm btn-outline-secondary">
                                        <i class="bi bi-printer"></i> Imprimir Todo
                                    </button>
                                </div>
                            </div>
                            <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                                <?php if (!$historial || $historial->num_rows === 0): ?>
                                <div class="empty-state py-4">
                                    <i class="bi bi-file-earmark-text"></i>
                                    <h5>No hay consultas registradas</h5>
                                    <p>Este paciente aún no tiene consultas médicas registradas</p>
                                </div>
                                <?php else: ?>
                                    <?php 
                                    while($registro = $historial->fetch_assoc()): 
                                        // Determinar si tiene receta
                                        $tiene_receta = !empty($registro['medicamentos_recetados']) || 
                                                        !empty($registro['tratamiento']) || 
                                                        !empty($registro['indicaciones_paciente']);
                                    ?>
                                    <div class="historial-card p-3 mb-3 <?php echo $tiene_receta ? 'historial-receta' : ''; ?>">
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
                                                    <?php if ($tiene_receta): ?>
                                                    <span class="badge badge-receta fecha-badge">
                                                        <i class="bi bi-capsule-pill me-1"></i>
                                                        Con receta
                                                    </span>
                                                    <?php endif; ?>
                                                    <?php if ($registro_editar && $registro_editar['id'] == $registro['id']): ?>
                                                    <span class="badge bg-warning fecha-badge">
                                                        <i class="bi bi-pencil me-1"></i>
                                                        Editando
                                                    </span>
                                                    <?php endif; ?>
                                                </div>
                                                <h6 class="mb-1 campo-label">Motivo:</h6>
                                                <p class="mb-2"><?php echo nl2br(htmlspecialchars($registro['motivo'])); ?></p>
                                            </div>
                                            <div class="btn-group">
                                                <a href="historial.php?paciente_id=<?php echo $paciente_id; ?>&accion=editar&id=<?php echo $registro['id']; ?>" 
                                                   class="btn btn-sm btn-outline-primary" title="Editar consulta">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                
                                                <a href="historial.php?paciente_id=<?php echo $paciente_id; ?>&accion=pdf_receta_detalle&id=<?php echo $registro['id']; ?>" 
                                                   class="btn btn-sm <?php echo $tiene_receta ? 'btn-outline-danger' : 'btn-outline-secondary'; ?>" 
                                                   target="_blank" 
                                                   title="<?php echo $tiene_receta ? 'PDF de Receta Completa' : 'Sin receta médica'; ?>"
                                                   <?php if (!$tiene_receta): ?>onclick="alert('Esta consulta no contiene información de receta médica'); return false;"<?php endif; ?>>
                                                    <i class="bi bi-file-pdf"></i>
                                                </a>
                                                
                                                <a href="historial.php?paciente_id=<?php echo $paciente_id; ?>&accion=eliminar&id=<?php echo $registro['id']; ?>" 
                                                   class="btn btn-sm btn-outline-danger" title="Eliminar consulta">
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
                                            <h6 class="mb-1 campo-label">Medicamentos Recetados:</h6>
                                            <div class="receta-medicamentos">
                                                <p class="mb-0"><?php echo nl2br(htmlspecialchars($registro['medicamentos_recetados'])); ?></p>
                                                <?php if (!empty($registro['indicaciones_paciente'])): ?>
                                                <hr class="my-2">
                                                <small class="text-muted">
                                                    <strong>Indicaciones:</strong> <?php echo nl2br(htmlspecialchars($registro['indicaciones_paciente'])); ?>
                                                </small>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                        
                                        <?php if (!empty($registro['observaciones'])): ?>
                                        <div class="mb-2">
                                            <h6 class="mb-1 campo-label">Observaciones:</h6>
                                            <p class="mb-0"><?php echo nl2br(htmlspecialchars($registro['observaciones'])); ?></p>
                                        </div>
                                        <?php endif; ?>
                                        
                                        <div class="d-flex justify-content-between align-items-center mt-2">
                                            <small class="text-muted">
                                                <i class="bi bi-clock-history me-1"></i>
                                                Registrado: <?php echo $registro['hora_registro']; ?>
                                            </small>
                                            <div>
                                                <?php if ($tiene_receta): ?>
                                                <a href="historial.php?paciente_id=<?php echo $paciente_id; ?>&accion=pdf_receta&id=<?php echo $registro['id']; ?>" 
                                                   class="btn btn-sm btn-danger" target="_blank">
                                                    <i class="bi bi-capsule-pill me-1"></i> Receta PDF
                                                </a>
                                                <?php else: ?>
                                                <button class="btn btn-sm btn-outline-secondary" disabled>
                                                    <i class="bi bi-capsule me-1"></i> Sin receta
                                                </button>
                                                <?php endif; ?>
                                            </div>
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
                                                Generar PDF del historial completo
                                            </div>
                                            <div class="list-group-item text-start">
                                                <i class="bi bi-check-circle-fill text-success me-2"></i>
                                                Generar recetas médicas en PDF
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
        
        // Filtrar pacientes con búsqueda en tiempo real
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
        
        // Funciones para impresión
        function imprimirTodoHistorial() {
            window.print();
        }
        
        // Función para verificar que PDF se abre en nueva pestaña
        function verificarPDF(url, elemento) {
            const ventana = window.open(url, '_blank');
            if (!ventana || ventana.closed) {
                alert('Por favor permite ventanas emergentes para generar el PDF');
                return false;
            }
            return true;
        }
        
        // Asignar evento a todos los enlaces PDF
        document.querySelectorAll('a[href*="accion=pdf"]').forEach(enlace => {
            enlace.addEventListener('click', function(e) {
                if (!verificarPDF(this.href, this)) {
                    e.preventDefault();
                }
            });
        });
    </script>
</body>
</html>