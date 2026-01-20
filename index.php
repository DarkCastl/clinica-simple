<?php
// index.php - Página principal (LOGIN)
session_start();
require_once 'config.php';

$mensaje = '';

// Si ya está logueado, redirigir al dashboard
if (isset($_SESSION['logueado']) && $_SESSION['logueado'] === true) {
    header('Location: dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = $_POST['usuario'] ?? '';
    $clave = $_POST['clave'] ?? '';
    
    // Validar campos
    if (empty($usuario) || empty($clave)) {
        $mensaje = '<div class="alert alert-warning">Por favor ingrese usuario y contraseña</div>';
    } else {
        // Buscar usuario en la base de datos
        global $conexion;
        
        $stmt = $conexion->prepare("SELECT id, usuario, password, nombre, rol FROM usuarios WHERE usuario = ? AND activo = 1");
        $stmt->bind_param("s", $usuario);
        $stmt->execute();
        $resultado = $stmt->get_result();
        
        if ($resultado->num_rows === 1) {
            $usuario_data = $resultado->fetch_assoc();
            
            // Verificar contraseña usando password_verify
            if (password_verify($clave, $usuario_data['password'])) {
                // Login exitoso
                $_SESSION['logueado'] = true;
                $_SESSION['usuario_id'] = $usuario_data['id'];
                $_SESSION['usuario'] = $usuario_data['nombre'];
                $_SESSION['rol'] = $usuario_data['rol'];  // ← ¡ESTA LÍNEA ES NUEVA!
                
                // Registrar fecha de último acceso (opcional)
                $conexion->query("UPDATE usuarios SET fecha_registro = NOW() WHERE id = " . $usuario_data['id']);
                
                // Redireccionar según rol (opcional)
                header('Location: dashboard.php');
                exit;
            } else {
                $mensaje = '<div class="alert alert-danger">Contraseña incorrecta</div>';
            }
        } else {
            $mensaje = '<div class="alert alert-danger">Usuario no encontrado o inactivo</div>';
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema Clínico - Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
            height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .login-box { 
            background: white; 
            border-radius: 15px; 
            padding: 40px; 
            box-shadow: 0 15px 35px rgba(0,0,0,0.3);
            animation: fadeIn 0.5s ease;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .logo-container {
            text-align: center;
            margin-bottom: 25px;
        }
        .logo {
            font-size: 3.5rem;
            color: #667eea;
            margin-bottom: 10px;
        }
        .system-name {
            color: #333;
            font-weight: 600;
            margin-bottom: 5px;
        }
        .system-subtitle {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 20px;
        }
        .form-label {
            font-weight: 500;
            color: #555;
        }
        .form-control {
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            transition: all 0.3s;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.25rem rgba(102, 126, 234, 0.25);
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 12px;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        .demo-credentials {
            background-color: #f8f9fa;
            border-left: 4px solid #667eea;
            padding: 15px;
            border-radius: 8px;
            margin-top: 20px;
            font-size: 0.85rem;
        }
        .demo-title {
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
        }
        .demo-item {
            margin-bottom: 5px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="container d-flex justify-content-center align-items-center" style="height: 100vh;">
        <div class="login-box col-md-4 col-sm-10">
            <div class="logo-container">
                <div class="logo">🏥</div>
                <h3 class="system-name">Sistema Clínico</h3>
                <p class="system-subtitle">Gestión Médica Integral</p>
            </div>
            
            <?php echo $mensaje; ?>
            
            <form method="POST" id="loginForm">
                <div class="mb-3">
                    <label class="form-label">Usuario</label>
                    <input type="text" name="usuario" class="form-control" required 
                           placeholder="Ingrese su usuario" autocomplete="username">
                </div>
                <div class="mb-4">
                    <label class="form-label">Contraseña</label>
                    <input type="password" name="clave" class="form-control" required 
                           placeholder="Ingrese su contraseña" autocomplete="current-password">
                </div>
                <button type="submit" class="btn btn-primary w-100 py-2 mb-3">
                    <span id="btnText">Ingresar al Sistema</span>
                    <div id="btnSpinner" class="spinner-border spinner-border-sm d-none" role="status">
                        <span class="visually-hidden">Cargando...</span>
                    </div>
                </button>
                
                <!-- Credenciales de demostración -->
                <div class="demo-credentials">
                    <div class="demo-title">👨‍⚕️ Usuarios de Prueba:</div>
                    <div class="demo-item">• <strong>admin</strong> / admin123 (Administrador)</div>
                    <div class="demo-item">• <strong>medico</strong> / medico123 (Médico)</div>
                    <div class="demo-item">• <strong>secretaria</strong> / secretaria123 (Secretaria)</div>
                    <div class="small text-muted mt-2">Nota: Los usuarios se crean automáticamente al instalar</div>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Animación del formulario
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const btnText = document.getElementById('btnText');
            const btnSpinner = document.getElementById('btnSpinner');
            
            btnText.textContent = 'Verificando...';
            btnSpinner.classList.remove('d-none');
        });
        
        // Auto-completar para pruebas
        document.addEventListener('DOMContentLoaded', function() {
            // Solo para entorno de desarrollo/demo
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('demo') === '1') {
                document.querySelector('input[name="usuario"]').value = 'admin';
                document.querySelector('input[name="clave"]').value = 'admin123';
            }
        });
    </script>
</body>
</html>