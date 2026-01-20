<?php
// config.php - VERSIÓN COMPLETA CON TODAS LAS TABLAS Y ROLES
// NO incluir session_start() aquí - se maneja en verificarSesion()

$host = "localhost";
$usuario = "root";
$password = "";
$basedatos = "clinica_simple";

// PRIMERO: Conectar sin base de datos
$conexion = new mysqli($host, $usuario, $password);

if ($conexion->connect_error) {
    die("Error de conexión inicial: " . $conexion->connect_error);
}

// SEGUNDO: Crear base de datos si no existe
$conexion->query("CREATE DATABASE IF NOT EXISTS $basedatos CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci");

// TERCERO: Seleccionar la base de datos
$conexion->select_db($basedatos);

// CUARTO: Crear todas las tablas necesarias
$tablas_sql = [];

// Tabla pacientes
$tablas_sql[] = "CREATE TABLE IF NOT EXISTS pacientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    dui VARCHAR(20),
    telefono VARCHAR(20),
    email VARCHAR(100),
    fecha_nacimiento DATE,
    direccion TEXT,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

// Tabla historial
$tablas_sql[] = "CREATE TABLE IF NOT EXISTS historial (
    id INT AUTO_INCREMENT PRIMARY KEY,
    paciente_id INT NOT NULL,
    fecha DATE NOT NULL,
    motivo TEXT,
    diagnostico TEXT,
    tratamiento TEXT,
    observaciones TEXT,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (paciente_id) REFERENCES pacientes(id) ON DELETE CASCADE,
    INDEX idx_paciente_id (paciente_id),
    INDEX idx_fecha (fecha)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

// Tabla agenda (CITAS) - ACTUALIZADA con campo para relación con historial
$tablas_sql[] = "CREATE TABLE IF NOT EXISTS agenda (
    id INT AUTO_INCREMENT PRIMARY KEY,
    paciente_id INT NOT NULL,
    fecha DATE NOT NULL,
    hora TIME NOT NULL,
    duracion INT DEFAULT 30 COMMENT 'Minutos',
    tipo VARCHAR(50) DEFAULT 'consulta',
    motivo TEXT,
    estado VARCHAR(20) DEFAULT 'pendiente' COMMENT 'pendiente, confirmada, cancelada, completada',
    notas TEXT,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    consulta_realizada BOOLEAN DEFAULT FALSE COMMENT 'Si ya se convirtió en consulta',
    historial_id INT DEFAULT NULL COMMENT 'ID del historial relacionado',
    FOREIGN KEY (paciente_id) REFERENCES pacientes(id) ON DELETE CASCADE,
    INDEX idx_fecha (fecha),
    INDEX idx_estado (estado),
    INDEX idx_paciente_fecha (paciente_id, fecha),
    INDEX idx_consulta_realizada (consulta_realizada)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

// Tabla usuarios (para login)
$tablas_sql[] = "CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    rol VARCHAR(20) DEFAULT 'medico',
    activo BOOLEAN DEFAULT TRUE,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_usuario (usuario),
    INDEX idx_activo (activo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

// Tabla recordatorios (opcional para futuras mejoras)
$tablas_sql[] = "CREATE TABLE IF NOT EXISTS recordatorios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cita_id INT NOT NULL,
    fecha_recordatorio DATETIME NOT NULL,
    metodo VARCHAR(20) DEFAULT 'sistema' COMMENT 'sistema, email, sms',
    enviado BOOLEAN DEFAULT FALSE,
    fecha_envio DATETIME,
    FOREIGN KEY (cita_id) REFERENCES agenda(id) ON DELETE CASCADE,
    INDEX idx_cita_id (cita_id),
    INDEX idx_fecha_recordatorio (fecha_recordatorio),
    INDEX idx_enviado (enviado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

// Tabla configuracion (para ajustes del sistema)
$tablas_sql[] = "CREATE TABLE IF NOT EXISTS configuracion (
    id INT AUTO_INCREMENT PRIMARY KEY,
    clave VARCHAR(50) NOT NULL UNIQUE,
    valor TEXT,
    descripcion VARCHAR(200),
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_clave (clave)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

// Ejecutar creación de tablas
foreach ($tablas_sql as $sql) {
    try {
        if (!$conexion->query($sql)) {
            error_log("Error creando tabla: " . $conexion->error);
        }
    } catch (Exception $e) {
        error_log("Excepción creando tabla: " . $e->getMessage());
    }
}

// ====================================================================
// CREAR USUARIOS POR DEFECTO SI NO EXISTEN
// ====================================================================

// Crear usuario admin por defecto si no existe
$verificar_admin = $conexion->query("SELECT COUNT(*) as total FROM usuarios WHERE usuario = 'admin'");
if ($verificar_admin && $verificar_admin->fetch_assoc()['total'] == 0) {
    $password_hash = password_hash('admin123', PASSWORD_DEFAULT);
    $conexion->query("INSERT INTO usuarios (usuario, password, nombre, rol) VALUES ('admin', '$password_hash', 'Administrador', 'admin')");
    error_log("Usuario admin creado automáticamente");
}

// Crear usuario médico por defecto si no existe
$verificar_medico = $conexion->query("SELECT COUNT(*) as total FROM usuarios WHERE usuario = 'medico'");
if ($verificar_medico && $verificar_medico->fetch_assoc()['total'] == 0) {
    $password_hash = password_hash('medico123', PASSWORD_DEFAULT);
    $conexion->query("INSERT INTO usuarios (usuario, password, nombre, rol) VALUES ('medico', '$password_hash', 'Dr. Carlos López', 'medico')");
    error_log("Usuario medico creado automáticamente");
}

// Crear usuario secretaria por defecto si no existe
$verificar_secretaria = $conexion->query("SELECT COUNT(*) as total FROM usuarios WHERE usuario = 'secretaria'");
if ($verificar_secretaria && $verificar_secretaria->fetch_assoc()['total'] == 0) {
    $password_hash = password_hash('secretaria123', PASSWORD_DEFAULT);
    $conexion->query("INSERT INTO usuarios (usuario, password, nombre, rol) VALUES ('secretaria', '$password_hash', 'Ana González', 'secretaria')");
    error_log("Usuario secretaria creado automáticamente");
}

// Crear usuario doctor1 por defecto si no existe (opcional)
$verificar_doctor1 = $conexion->query("SELECT COUNT(*) as total FROM usuarios WHERE usuario = 'doctor1'");
if ($verificar_doctor1 && $verificar_doctor1->fetch_assoc()['total'] == 0) {
    $password_hash = password_hash('doctor123', PASSWORD_DEFAULT);
    $conexion->query("INSERT INTO usuarios (usuario, password, nombre, rol) VALUES ('doctor1', '$password_hash', 'Dr. Juan Pérez', 'medico')");
    error_log("Usuario doctor1 creado automáticamente");
}

// Verificar y actualizar contraseñas de usuarios existentes (por si tienen texto plano)
$usuarios_existentes = $conexion->query("SELECT usuario, password FROM usuarios");
while ($usuario = $usuarios_existentes->fetch_assoc()) {
    // Si la contraseña es muy corta (probablemente texto plano), la actualizamos
    if (strlen($usuario['password']) < 50) {
        $nueva_password = '';
        
        // Asignar contraseña según el usuario
        switch($usuario['usuario']) {
            case 'admin':
                $nueva_password = 'admin123';
                break;
            case 'medico':
            case 'doctor1':
                $nueva_password = 'medico123';
                break;
            case 'secretaria':
                $nueva_password = 'secretaria123';
                break;
            default:
                $nueva_password = 'password123'; // Contraseña por defecto
        }
        
        $password_hash = password_hash($nueva_password, PASSWORD_DEFAULT);
        $conexion->query("UPDATE usuarios SET password = '$password_hash' WHERE usuario = '{$usuario['usuario']}'");
        error_log("Contraseña actualizada para usuario: {$usuario['usuario']}");
    }
}

// Insertar configuración básica si no existe
$config_basica = [
    ['nombre_clinica', 'Clínica Simple', 'Nombre de la clínica'],
    ['duracion_cita_default', '45', 'Duración predeterminada de citas (minutos)'],
    ['hora_apertura', '08:00', 'Hora de apertura de la clínica'],
    ['hora_cierre', '18:00', 'Hora de cierre de la clínica'],
    ['recordatorio_citas', '24', 'Horas de anticipación para recordatorios']
];

foreach ($config_basica as $config) {
    list($clave, $valor, $descripcion) = $config;
    $conexion->query("INSERT IGNORE INTO configuracion (clave, valor, descripcion) VALUES ('$clave', '$valor', '$descripcion')");
}

// Configurar MySQL para manejar errores
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$conexion->set_charset("utf8mb4");

// FUNCIONES DEL SISTEMA

// FUNCIÓN PARA VERIFICAR SESIÓN SEGURA
function verificarSesion() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!isset($_SESSION['logueado']) || $_SESSION['logueado'] !== true) {
        header('Location: index.php');
        exit;
    }
}

// FUNCIÓN PARA EJECUTAR CONSULTAS SEGURAS
function ejecutarConsulta($sql, $params = []) {
    global $conexion;
    
    $stmt = $conexion->prepare($sql);
    if (!$stmt) {
        return false;
    }
    
    if (!empty($params)) {
        $tipos = '';
        foreach ($params as $param) {
            if (is_int($param)) {
                $tipos .= 'i';
            } elseif (is_float($param)) {
                $tipos .= 'd';
            } else {
                $tipos .= 's';
            }
        }
        $stmt->bind_param($tipos, ...$params);
    }
    
    $stmt->execute();
    return $stmt->get_result();
}

// FUNCIÓN PARA CONVERTIR CITA EN CONSULTA
function convertirCitaEnConsulta($cita_id, $paciente_id) {
    global $conexion;
    
    try {
        // Obtener datos de la cita
        $cita = $conexion->query("SELECT * FROM agenda WHERE id = $cita_id AND paciente_id = $paciente_id")->fetch_assoc();
        
        if (!$cita) {
            return false;
        }
        
        // Iniciar transacción
        $conexion->begin_transaction();
        
        // 1. Insertar en historial
        $sql_historial = "INSERT INTO historial (paciente_id, fecha, motivo, diagnostico, tratamiento, observaciones) 
                         VALUES (?, ?, ?, 'Consulta realizada', 'Por determinar', 'Convertido desde cita programada')";
        $stmt = $conexion->prepare($sql_historial);
        $stmt->bind_param("iss", $paciente_id, $cita['fecha'], $cita['motivo']);
        $stmt->execute();
        $historial_id = $conexion->insert_id;
        $stmt->close();
        
        // 2. Actualizar agenda
        $sql_agenda = "UPDATE agenda SET estado = 'completada', consulta_realizada = TRUE, historial_id = ? WHERE id = ?";
        $stmt = $conexion->prepare($sql_agenda);
        $stmt->bind_param("ii", $historial_id, $cita_id);
        $stmt->execute();
        $stmt->close();
        
        // Confirmar transacción
        $conexion->commit();
        return $historial_id;
        
    } catch (Exception $e) {
        $conexion->rollback();
        error_log("Error convirtiendo cita: " . $e->getMessage());
        return false;
    }
}

// FUNCIÓN PARA OBTENER CONFIGURACIÓN
function obtenerConfig($clave, $default = '') {
    global $conexion;
    
    $result = $conexion->query("SELECT valor FROM configuracion WHERE clave = '$clave'");
    if ($result && $result->num_rows > 0) {
        return $result->fetch_assoc()['valor'];
    }
    return $default;
}

// FUNCIÓN PARA DEPURACIÓN (solo en desarrollo)
function debug($mensaje) {
    if (isset($_GET['debug']) && $_GET['debug'] == '1') {
        echo "<!-- DEBUG: " . htmlspecialchars($mensaje) . " -->\n";
    }
}

// Configuración de zona horaria
date_default_timezone_set('America/El_Salvador');

// FUNCIONES DE ROLES
// NOTA: Asegúrate de que el archivo roles_simple.php exista en el directorio includes/
// Si no existe, crear una versión básica

// Verificar si el archivo de roles existe, si no, crear funciones básicas
if (!function_exists('miRol')) {
    // Si roles_simple.php no existe, definimos funciones básicas
    function miRol() {
        return $_SESSION['rol'] ?? 'secretaria';
    }
    
    function nombreMiRol() {
        $roles = [
            'admin' => 'Administrador',
            'medico' => 'Médico',
            'secretaria' => 'Secretaria'
        ];
        $rol = miRol();
        return $roles[$rol] ?? 'Secretaria';
    }
    
    function puedoVerPacientes() {
        $rol = miRol();
        return in_array($rol, ['admin', 'medico', 'secretaria']);
    }
    
    function puedoVerHistorial() {
        $rol = miRol();
        return in_array($rol, ['admin', 'medico']);
    }
    
    function puedoGestionarAgenda() {
        $rol = miRol();
        return in_array($rol, ['admin', 'medico', 'secretaria']);
    }
    
    function puedoVerEstadisticas() {
        $rol = miRol();
        return in_array($rol, ['admin', 'medico']);
    }
    
    function puedoGestionarUsuarios() {
        $rol = miRol();
        return $rol === 'admin';
    }
    
    function puedoAccederConfiguracion() {
        $rol = miRol();
        return $rol === 'admin';
    }
    
    function puedoCrearConsultas() {
        $rol = miRol();
        return in_array($rol, ['admin', 'medico']);
    }
} else {
    // Si el archivo existe, lo incluimos
    require_once 'includes/roles_simple.php';
}

// FUNCIÓN PARA OBTENER INFORMACIÓN DEL USUARIO ACTUAL
function obtenerUsuarioActual() {
    return [
        'id' => $_SESSION['usuario_id'] ?? null,
        'nombre' => $_SESSION['usuario'] ?? 'Usuario',
        'rol' => $_SESSION['rol'] ?? 'secretaria',
        'nombre_rol' => nombreMiRol()
    ];
}

// FUNCIÓN PARA VERIFICAR ACCESO A MÓDULOS
function verificarAcceso($modulo) {
    // Iniciar sesión si no está iniciada
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Verificar que el usuario esté logueado
    if (!isset($_SESSION['logueado']) || $_SESSION['logueado'] !== true) {
        header('Location: index.php');
        exit;
    }
    
    $rol = miRol();
    
    $permisos = [
        'pacientes' => puedoVerPacientes(),
        'historial' => puedoVerHistorial(),
        'agenda' => puedoGestionarAgenda(),
        'estadisticas' => puedoVerEstadisticas(),
        'usuarios' => puedoGestionarUsuarios(),
        'configuracion' => puedoAccederConfiguracion(),
        'consultas' => puedoCrearConsultas()
    ];
    
    if (!isset($permisos[$modulo]) || !$permisos[$modulo]) {
        $_SESSION['error'] = 'No tienes permiso para acceder a esta sección';
        header('Location: dashboard.php');
        exit;
    }
    
    return true;
}

// Mensaje de inicio (solo en desarrollo)
debug("Config.php cargado correctamente - Base: $basedatos");
?>