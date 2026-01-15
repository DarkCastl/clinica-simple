<?php
// index.php - Página principal
session_start();
require_once 'config.php';

// Credenciales simples (en un sistema real usarías BD)
$usuarios_validos = [
    'admin' => 'admin123',
    'doctor' => 'doctor123'
];

$mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = $_POST['usuario'] ?? '';
    $clave = $_POST['clave'] ?? '';
    
    if (isset($usuarios_validos[$usuario]) && $usuarios_validos[$usuario] === $clave) {
        $_SESSION['usuario'] = $usuario;
        $_SESSION['logueado'] = true;
        header('Location: dashboard.php');
        exit;
    } else {
        $mensaje = '<div class="alert alert-danger">Usuario o clave incorrectos</div>';
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
        body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); height: 100vh; }
        .login-box { background: white; border-radius: 10px; padding: 30px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); }
    </style>
</head>
<body>
    <div class="container d-flex justify-content-center align-items-center" style="height: 100vh;">
        <div class="login-box col-md-4">
            <h2 class="text-center mb-4">🏥 Sistema Clínico</h2>
            <?php echo $mensaje; ?>
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Usuario</label>
                    <input type="text" name="usuario" class="form-control" required value="admin">
                </div>
                <div class="mb-3">
                    <label class="form-label">Contraseña</label>
                    <input type="password" name="clave" class="form-control" required value="admin123">
                </div>
                <button type="submit" class="btn btn-primary w-100">Ingresar</button>
                <p class="text-center mt-3 text-muted small">
                </p>
            </form>
        </div>
    </div>
</body>
</html>