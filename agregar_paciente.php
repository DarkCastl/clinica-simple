<?php
// agregar_paciente.php
require_once 'config.php';
verificarSesion();

$mensaje = '';

// DEPURACIÓN: Activar errores
error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['agregar'])) {
    error_log("========= FORMULARIO ENVIADO =========");
    error_log("POST Data: " . print_r($_POST, true));
    
    // Validar y sanitizar datos
    $nombre = trim($_POST['nombre'] ?? '');
    $dui = trim($_POST['dui'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $fecha_nacimiento = $_POST['fecha_nacimiento'] ?? '';
    $direccion = trim($_POST['direccion'] ?? '');
    $observaciones = trim($_POST['observaciones'] ?? '');
    
    error_log("Nombre: $nombre");
    error_log("DUI: $dui");
    
    // Validaciones básicas
    if (empty($nombre)) {
        $mensaje = '<div class="alert alert-danger">❌ El nombre es obligatorio</div>';
    } else {
        // Preparar la consulta SQL
        $sql = "INSERT INTO pacientes (nombre, dui, telefono, email, fecha_nacimiento, direccion, observaciones, fecha_registro) 
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
        
        error_log("SQL: $sql");
        
        if ($stmt = $conexion->prepare($sql)) {
            $stmt->bind_param("sssssss", 
                $nombre, 
                $dui, 
                $telefono, 
                $email, 
                $fecha_nacimiento, 
                $direccion, 
                $observaciones
            );
            
            if ($stmt->execute()) {
                error_log("✅ Paciente insertado correctamente. ID: " . $conexion->insert_id);
                
                // Redirigir con mensaje de éxito
                header("Location: pacientes.php?mensaje=agregado&nombre=" . urlencode($nombre));
                exit();
            } else {
                $error_msg = $stmt->error;
                error_log("❌ Error SQL: " . $error_msg);
                $mensaje = '<div class="alert alert-danger">
                    <strong>❌ Error al guardar:</strong> ' . htmlspecialchars($error_msg) . '
                </div>';
            }
            
            $stmt->close();
        } else {
            $error_msg = $conexion->error;
            error_log("❌ Error preparando consulta: " . $error_msg);
            $mensaje = '<div class="alert alert-danger">
                <strong>❌ Error en la consulta:</strong> ' . htmlspecialchars($error_msg) . '
            </div>';
        }
    }
}

// Si hay mensaje GET (desde redirección)
if (isset($_GET['mensaje']) && $_GET['mensaje'] === 'agregado') {
    $mensaje = '<div class="alert alert-success alert-dismissible fade show">
        <i class="bi bi-check-circle me-2"></i> ✅ Paciente agregado exitosamente
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>';
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agregar Paciente - Clínica Simple</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        :root {
            --color-primary: #2c3e50;
            --color-secondary: #3498db;
        }
        
        .card-header-custom {
            background: linear-gradient(135deg, var(--color-primary), var(--color-secondary));
            color: white;
        }
        
        .required::after {
            content: " *";
            color: red;
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
                    <h1 class="h3 mb-1"><i class="bi bi-person-plus me-2"></i> Agregar Nuevo Paciente</h1>
                    <p class="text-muted mb-0">Complete el formulario para registrar un nuevo paciente</p>
                </div>
                <a href="pacientes.php" class="btn btn-secondary">
                    <i class="bi bi-arrow-left me-1"></i> Volver a Pacientes
                </a>
            </div>
            
            <!-- Mensajes -->
            <?php echo $mensaje; ?>
            
            <!-- FORMULARIO -->
            <div class="card">
                <div class="card-header card-header-custom">
                    <h5 class="mb-0"><i class="bi bi-person-vcard me-2"></i> Datos del Paciente</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="" id="formPaciente">
                        <div class="row">
                            <!-- Nombre -->
                            <div class="col-md-6 mb-3">
                                <label class="form-label required">Nombre Completo</label>
                                <input type="text" class="form-control" name="nombre" required 
                                       placeholder="Ej: Juan Pérez" 
                                       value="<?php echo htmlspecialchars($_POST['nombre'] ?? ''); ?>">
                                <div class="form-text">Nombre y apellidos del paciente</div>
                            </div>
                            
                            <!-- DUI -->
                            <div class="col-md-6 mb-3">
                                <label class="form-label">DUI</label>
                                <input type="text" class="form-control" name="dui" 
                                       placeholder="Ej: 12345678-9"
                                       pattern="[0-9]{8}-[0-9]{1}"
                                       title="Formato: 12345678-9"
                                       value="<?php echo htmlspecialchars($_POST['dui'] ?? ''); ?>">
                                <div class="form-text">Formato: 00000000-0</div>
                            </div>
                            
                            <!-- Teléfono -->
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Teléfono</label>
                                <input type="tel" class="form-control" name="telefono" 
                                       placeholder="Ej: 7777-1234"
                                       value="<?php echo htmlspecialchars($_POST['telefono'] ?? ''); ?>">
                            </div>
                            
                            <!-- Email -->
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" name="email" 
                                       placeholder="ejemplo@email.com"
                                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                            </div>
                            
                            <!-- Fecha Nacimiento -->
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Fecha de Nacimiento</label>
                                <input type="date" class="form-control" name="fecha_nacimiento"
                                       max="<?php echo date('Y-m-d'); ?>"
                                       value="<?php echo htmlspecialchars($_POST['fecha_nacimiento'] ?? ''); ?>">
                            </div>
                            
                            <!-- Dirección -->
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Dirección</label>
                                <input type="text" class="form-control" name="direccion" 
                                       placeholder="Calle, número, ciudad, departamento"
                                       value="<?php echo htmlspecialchars($_POST['direccion'] ?? ''); ?>">
                            </div>
                            
                            <!-- Observaciones -->
                            <div class="col-12 mb-3">
                                <label class="form-label">Observaciones / Notas Médicas</label>
                                <textarea class="form-control" name="observaciones" rows="4" 
                                          placeholder="Antecedentes médicos, alergias, medicamentos, etc."><?php echo htmlspecialchars($_POST['observaciones'] ?? ''); ?></textarea>
                                <div class="form-text">Información médica relevante del paciente</div>
                            </div>
                        </div>
                        
                        <!-- BOTONES -->
                        <div class="border-top pt-4 mt-3">
                            <div class="d-flex justify-content-between">
                                <a href="pacientes.php" class="btn btn-outline-secondary">
                                    <i class="bi bi-x-circle me-1"></i> Cancelar
                                </a>
                                <button type="submit" name="agregar" class="btn btn-primary btn-lg">
                                    <i class="bi bi-save me-1"></i> Guardar Paciente
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- INFO ADICIONAL -->
            <div class="alert alert-info mt-3">
                <div class="d-flex">
                    <i class="bi bi-info-circle fs-4 me-3"></i>
                    <div>
                        <strong>Información:</strong>
                        <ul class="mb-0 mt-1">
                            <li>Los campos marcados con * son obligatorios</li>
                            <li>El DUI debe tener formato: 00000000-0</li>
                            <li>Después de guardar, serás redirigido a la lista de pacientes</li>
                        </ul>
                    </div>
                </div>
            </div>
            
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    // Validación del formulario
    document.getElementById('formPaciente').addEventListener('submit', function(e) {
        const nombre = document.querySelector('[name="nombre"]').value.trim();
        
        if (!nombre) {
            e.preventDefault();
            alert('Por favor, ingrese el nombre del paciente');
            document.querySelector('[name="nombre"]').focus();
            return false;
        }
        
        // Validar DUI si está ingresado
        const dui = document.querySelector('[name="dui"]').value.trim();
        if (dui && !/^\d{8}-\d{1}$/.test(dui)) {
            e.preventDefault();
            alert('El DUI debe tener formato: 12345678-9');
            document.querySelector('[name="dui"]').focus();
            return false;
        }
        
        return true;
    });
    
    // Auto-cerrar alertas después de 5 segundos
    setTimeout(() => {
        document.querySelectorAll('.alert').forEach(alert => {
            if (alert.classList.contains('alert-dismissible')) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            }
        });
    }, 5000);
    </script>
</body>
</html>