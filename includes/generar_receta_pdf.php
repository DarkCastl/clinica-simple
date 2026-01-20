<?php
session_start();
require_once 'config.php';

if (!isset($_GET['id_receta'])) {
    die("ID de receta no especificado");
}

$id_receta = $_GET['id_receta'];

require_once('tcpdf/tcpdf.php');

// Obtener datos de la receta y paciente
$query = "SELECT r.*, p.nombre, p.apellido, p.cedula, p.fecha_nacimiento 
          FROM recetas r 
          JOIN pacientes p ON r.id_paciente = p.id 
          WHERE r.id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $id_receta);
$stmt->execute();
$result = $stmt->get_result();
$receta = $result->fetch_assoc();

$pdf = new TCPDF('P', 'mm', 'Letter', true, 'UTF-8', false);
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->AddPage();

// Encabezado con logo
// $pdf->Image('img/logo-clinica.png', 20, 15, 30);

$pdf->SetFont('helvetica', 'B', 16);
$pdf->Cell(0, 20, 'RECETA MÉDICA', 0, 1, 'C');

// Línea decorativa
$pdf->Line(20, 45, 195, 45);

// Datos del paciente
$pdf->SetY(55);
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 10, 'PACIENTE:', 0, 1);
$pdf->SetFont('helvetica', '', 11);
$pdf->Cell(0, 7, 'Nombre: ' . $receta['nombre'] . ' ' . $receta['apellido'], 0, 1);
$pdf->Cell(0, 7, 'Cédula: ' . $receta['cedula'], 0, 1);
$pdf->Cell(0, 7, 'Fecha Nacimiento: ' . date('d/m/Y', strtotime($receta['fecha_nacimiento'])), 0, 1);

$pdf->Ln(10);

// Medicamentos
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 10, 'MEDICAMENTOS PRESCRITOS:', 0, 1);
$pdf->SetFont('helvetica', '', 11);
$pdf->MultiCell(0, 7, $receta['medicamentos'], 0, 'L');

$pdf->Ln(5);

// Instrucciones
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 10, 'INSTRUCCIONES:', 0, 1);
$pdf->SetFont('helvetica', '', 11);
$pdf->MultiCell(0, 7, $receta['instrucciones'], 0, 'L');

$pdf->Ln(5);

// Duración
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(40, 10, 'DURACIÓN:', 0, 0);
$pdf->SetFont('helvetica', '', 11);
$pdf->Cell(0, 10, $receta['duracion'], 0, 1);

$pdf->Ln(15);

// Firma del médico
$pdf->SetFont('helvetica', 'B', 11);
$pdf->Cell(0, 10, '_________________________________', 0, 1, 'C');
$pdf->Cell(0, 7, 'Dr. [Nombre del Médico]', 0, 1, 'C');
$pdf->Cell(0, 7, 'Cédula Profesional: [Número]', 0, 1, 'C');

// Fecha
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(0, 15, 'Fecha: ' . date('d/m/Y', strtotime($receta['fecha'])), 0, 1, 'R');

$pdf->Output('receta_' . $receta['cedula'] . '_' . date('Ymd') . '.pdf', 'I');

$stmt->close();
$conn->close();
?>