<?php
// buscar_paciente.php
require_once 'config.php';

$tipo = $_GET['tipo'] ?? 'nombre';
$termino = $_GET['termino'] ?? '';

if (empty($termino)) {
    echo json_encode(['error' => 'Término de búsqueda vacío']);
    exit;
}

// Limpiar y preparar término de búsqueda
$termino = '%' . trim($termino) . '%';

switch ($tipo) {
    case 'nombre':
        $sql = "SELECT id, nombre, dui, telefono, email FROM pacientes 
                WHERE nombre LIKE ? ORDER BY nombre LIMIT 10";
        break;
    case 'dui':
        $sql = "SELECT id, nombre, dui, telefono, email FROM pacientes 
                WHERE dui LIKE ? ORDER BY nombre LIMIT 10";
        break;
    case 'telefono':
        $sql = "SELECT id, nombre, dui, telefono, email FROM pacientes 
                WHERE telefono LIKE ? ORDER BY nombre LIMIT 10";
        break;
    default:
        $sql = "SELECT id, nombre, dui, telefono, email FROM pacientes 
                WHERE nombre LIKE ? ORDER BY nombre LIMIT 10";
}

$stmt = $conexion->prepare($sql);
$stmt->bind_param("s", $termino);
$stmt->execute();
$resultado = $stmt->get_result();

$pacientes = [];
while ($row = $resultado->fetch_assoc()) {
    $pacientes[] = $row;
}

header('Content-Type: application/json');
echo json_encode($pacientes);
?>