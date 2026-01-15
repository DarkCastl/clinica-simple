<?php
// buscar_global.php - Búsqueda en múltiples tablas
session_start();
require_once 'config.php';

$termino = isset($_GET['termino']) ? trim($_GET['termino']) : '';

if (empty($termino)) {
    echo json_encode(['error' => 'Término de búsqueda vacío']);
    exit;
}

$search_term = '%' . $termino . '%';
$resultados = [];

// Buscar pacientes
$sql_pacientes = "SELECT id, nombre, dui, telefono FROM pacientes 
                  WHERE nombre LIKE ? OR dui LIKE ? OR telefono LIKE ? 
                  LIMIT 10";
$stmt = $conexion->prepare($sql_pacientes);
$stmt->bind_param("sss", $search_term, $search_term, $search_term);
$stmt->execute();
$resultados['pacientes'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Buscar consultas
$sql_consultas = "SELECT h.id, h.motivo, h.fecha, p.nombre as paciente_nombre 
                  FROM historial h 
                  JOIN pacientes p ON h.paciente_id = p.id 
                  WHERE h.motivo LIKE ? OR h.diagnostico LIKE ? 
                  LIMIT 5";
$stmt = $conexion->prepare($sql_consultas);
$stmt->bind_param("ss", $search_term, $search_term);
$stmt->execute();
$resultados['consultas'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Buscar citas
$sql_citas = "SELECT a.id, a.motivo, a.fecha, a.hora, p.nombre as paciente_nombre 
              FROM agenda a 
              JOIN pacientes p ON a.paciente_id = p.id 
              WHERE a.motivo LIKE ? OR p.nombre LIKE ? 
              LIMIT 5";
$stmt = $conexion->prepare($sql_citas);
$stmt->bind_param("ss", $search_term, $search_term);
$stmt->execute();
$resultados['citas'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

header('Content-Type: application/json');
echo json_encode($resultados);
?>