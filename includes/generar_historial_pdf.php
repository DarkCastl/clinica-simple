<?php
session_start();
require_once 'config.php';

// Verificar si se pasó el ID del paciente
if (!isset($_GET['id_paciente']) || empty($_GET['id_paciente'])) {
    die("ID de paciente no especificado");
}

$id_paciente = $_GET['id_paciente'];

// OPCIÓN A: Con TCPDF (más control)
require_once('tcpdf/tcpdf.php');

// OPCION B: Con Dompdf (más fácil si ya tienes HTML)
// require_once 'dompdf/autoload.inc.php';
// use Dompdf\Dompdf;

// Crear conexión a la base de datos
// (asumiendo que ya tienes config.php con la conexión)

// Obtener datos del paciente
$query_paciente = "SELECT * FROM pacientes WHERE id = ?";
$stmt_paciente = $conn->prepare($query_paciente);
$stmt_paciente->bind_param("i", $id_paciente);
$stmt_paciente->execute();
$result_paciente = $stmt_paciente->get_result();
$paciente = $result_paciente->fetch_assoc();

// Obtener historial clínico
$query_historial = "SELECT * FROM historial_clinico WHERE id_paciente = ? ORDER BY fecha DESC";
$stmt_historial = $conn->prepare($query_historial);
$stmt_historial->bind_param("i", $id_paciente);
$stmt_historial->execute();
$result_historial = $stmt_historial->get_result();

// Obtener recetas
$query_recetas = "SELECT * FROM recetas WHERE id_paciente = ? ORDER BY fecha DESC";
$stmt_recetas = $conn->prepare($query_recetas);
$stmt_recetas->bind_param("i", $id_paciente);
$stmt_recetas->execute();
$result_recetas = $stmt_recetas->get_result();

// ========== CON TCPDF ==========
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// Configurar documento
$pdf->SetCreator('Clinica Simple');
$pdf->SetAuthor('Clinica Simple');
$pdf->SetTitle('Historial Clínico - ' . $paciente['nombre']);
$pdf->SetSubject('Historial Clínico');
$pdf->SetKeywords('historial, clinico, paciente');

// Eliminar header/footer por defecto
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

// Agregar página
$pdf->AddPage();

// Logo (si quieres agregarlo)
// $pdf->Image('img/logo-clinica.png', 10, 10, 30, '', 'PNG', '', 'T', false, 300, '', false, false, 0, false, false, false);

// Título
$pdf->SetFont('helvetica', 'B', 16);
$pdf->Cell(0, 10, 'HISTORIAL CLÍNICO', 0, 1, 'C');
$pdf->Ln(5);

// Información del paciente
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 10, 'DATOS DEL PACIENTE', 0, 1);
$pdf->SetFont('helvetica', '', 10);

$pdf->Cell(40, 6, 'Nombre:', 0, 0);
$pdf->Cell(0, 6, $paciente['nombre'] . ' ' . $paciente['apellido'], 0, 1);

$pdf->Cell(40, 6, 'Cédula:', 0, 0);
$pdf->Cell(0, 6, $paciente['cedula'] ?? 'No registrada', 0, 1);

$pdf->Cell(40, 6, 'Fecha Nacimiento:', 0, 0);
$pdf->Cell(0, 6, $paciente['fecha_nacimiento'] ?? 'No registrada', 0, 1);

$pdf->Cell(40, 6, 'Teléfono:', 0, 0);
$pdf->Cell(0, 6, $paciente['telefono'] ?? 'No registrada', 0, 1);

$pdf->Ln(10);

// Historial Clínico
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 10, 'REGISTROS CLÍNICOS', 0, 1);

if ($result_historial->num_rows > 0) {
    while ($registro = $result_historial->fetch_assoc()) {
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(0, 6, 'Fecha: ' . date('d/m/Y', strtotime($registro['fecha'])), 0, 1);
        
        $pdf->SetFont('helvetica', '', 10);
        $pdf->MultiCell(0, 6, 'Motivo: ' . $registro['motivo_consulta'], 0, 'L');
        $pdf->MultiCell(0, 6, 'Diagnóstico: ' . $registro['diagnostico'], 0, 'L');
        $pdf->MultiCell(0, 6, 'Tratamiento: ' . $registro['tratamiento'], 0, 'L');
        $pdf->MultiCell(0, 6, 'Observaciones: ' . $registro['observaciones'], 0, 'L');
        $pdf->Ln(3);
    }
} else {
    $pdf->Cell(0, 6, 'No hay registros clínicos', 0, 1);
}

$pdf->Ln(5);

// Recetas
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 10, 'RECETAS MÉDICAS', 0, 1);

if ($result_recetas->num_rows > 0) {
    while ($receta = $result_recetas->fetch_assoc()) {
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(0, 6, 'Receta del: ' . date('d/m/Y', strtotime($receta['fecha'])), 0, 1);
        
        $pdf->SetFont('helvetica', '', 10);
        $pdf->MultiCell(0, 6, 'Medicamentos: ' . $receta['medicamentos'], 0, 'L');
        $pdf->MultiCell(0, 6, 'Instrucciones: ' . $receta['instrucciones'], 0, 'L');
        $pdf->MultiCell(0, 6, 'Duración: ' . $receta['duracion'], 0, 'L');
        $pdf->Ln(5);
    }
} else {
    $pdf->Cell(0, 6, 'No hay recetas registradas', 0, 1);
}

// Pie de página
$pdf->SetY(-15);
$pdf->SetFont('helvetica', 'I', 8);
$pdf->Cell(0, 10, 'Generado el: ' . date('d/m/Y H:i'), 0, 0, 'C');

// Salida del PDF
$pdf->Output('historial_clinico_' . $paciente['cedula'] . '_' . date('Ymd') . '.pdf', 'I');

// Cerrar conexiones
$stmt_paciente->close();
$stmt_historial->close();
$stmt_recetas->close();
$conn->close();
?>