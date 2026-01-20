<?php
session_start();
require_once __DIR__ . '/config.php';

if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../index.php');
    exit;
}

if (!isset($_GET['id'])) {
    die("ID no especificado");
}

$id_receta = intval($_GET['id']);

// Obtener datos
$query = "SELECT r.*, p.nombre, p.apellido, p.cedula 
          FROM recetas r 
          INNER JOIN pacientes p ON r.id_paciente = p.id 
          WHERE r.id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $id_receta);
$stmt->execute();
$result = $stmt->get_result();
$receta = $result->fetch_assoc();

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receta Médica - Imprimir</title>
    <link rel="stylesheet" href="../assets/css/print.css">
    <style>
        body { font-family: Arial, sans-serif; }
        .receta-container { max-width: 800px; margin: 0 auto; padding: 20px; }
        .header { text-align: center; border-bottom: 2px solid #333; padding-bottom: 20px; margin-bottom: 30px; }
        .datos-paciente { margin-bottom: 30px; }
        .medicamentos { margin: 20px 0; }
        .firma { margin-top: 50px; text-align: center; }
    </style>
</head>
<body>
    <div class="receta-container">
        <div class="header">
            <h1>CLINICA SIMPLE</h1>
            <h2>RECETA MÉDICA</h2>
        </div>
        
        <div class="datos-paciente">
            <h3>DATOS DEL PACIENTE</h3>
            <p><strong>Nombre:</strong> <?php echo htmlspecialchars($receta['nombre'] . ' ' . $receta['apellido']); ?></p>
            <p><strong>Cédula:</strong> <?php echo htmlspecialchars($receta['cedula']); ?></p>
            <p><strong>Fecha de Receta:</strong> <?php echo date('d/m/Y', strtotime($receta['fecha'])); ?></p>
        </div>
        
        <div class="medicamentos">
            <h3>MEDICAMENTOS PRESCRITOS</h3>
            <div style="white-space: pre-line;"><?php echo htmlspecialchars($receta['medicamentos']); ?></div>
        </div>
        
        <?php if (!empty($receta['instrucciones'])): ?>
        <div class="instrucciones">
            <h3>INSTRUCCIONES</h3>
            <div style="white-space: pre-line;"><?php echo htmlspecialchars($receta['instrucciones']); ?></div>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($receta['duracion'])): ?>
        <div class="duracion">
            <h3>DURACIÓN DEL TRATAMIENTO</h3>
            <p><?php echo htmlspecialchars($receta['duracion']); ?></p>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($receta['observaciones'])): ?>
        <div class="observaciones">
            <h3>OBSERVACIONES ADICIONALES</h3>
            <div style="white-space: pre-line;"><?php echo htmlspecialchars($receta['observaciones']); ?></div>
        </div>
        <?php endif; ?>
        
        <div class="firma">
            <p>_________________________</p>
            <p><strong>Dr. [Nombre del Médico]</strong></p>
            <p>Cédula Profesional: [Número]</p>
            <p>Especialidad: [Especialidad]</p>
        </div>
    </div>
    
    <script>
        // Auto-imprimir al cargar (opcional)
        window.onload = function() {
            window.print();
        }
    </script>
</body>
</html>