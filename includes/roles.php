<?php
// includes/roles.php - Sistema de Roles y Permisos

// Roles disponibles
define('ROL_ADMIN', 'admin');
define('ROL_DOCTOR', 'doctor');
define('ROL_SECRETARIA', 'secretaria');

// Obtener rol del usuario actual
function obtenerRolUsuario() {
    return $_SESSION['rol'] ?? ROL_SECRETARIA;
}

// Verificar si tiene un rol específico
function tieneRol($rolRequerido) {
    $rolActual = obtenerRolUsuario();
    return $rolActual === $rolRequerido;
}

// Verificar si tiene al menos uno de los roles
function tieneAlgunRol($roles) {
    $rolActual = obtenerRolUsuario();
    return in_array($rolActual, $roles);
}

// Obtener permisos según rol
function obtenerPermisos() {
    $rol = obtenerRolUsuario();
    
    $permisos = [
        'ver_dashboard' => true,
        'ver_perfil' => true,
        'cambiar_password' => true,
    ];
    
    switch($rol) {
        case ROL_ADMIN:
            $permisos = array_merge($permisos, [
                'gestionar_usuarios' => true,
                'gestionar_configuracion' => true,
                'ver_todos_pacientes' => true,
                'ver_todo_historial' => true,
                'crear_pacientes' => true,
                'editar_pacientes' => true,
                'eliminar_pacientes' => true,
                'crear_consultas' => true,
                'editar_consultas' => true,
                'eliminar_consultas' => true,
                'gestionar_agenda' => true,
                'ver_estadisticas' => true,
                'generar_reportes' => true,
                'acceder_configuracion' => true,
            ]);
            break;
            
        case ROL_DOCTOR:
            $permisos = array_merge($permisos, [
                'gestionar_usuarios' => false,
                'gestionar_configuracion' => false,
                'ver_todos_pacientes' => true,
                'ver_todo_historial' => true,
                'crear_pacientes' => true,
                'editar_pacientes' => true,
                'eliminar_pacientes' => false,
                'crear_consultas' => true,
                'editar_consultas' => true,
                'eliminar_consultas' => false,
                'gestionar_agenda' => true,
                'ver_estadisticas' => true,
                'generar_reportes' => true,
                'acceder_configuracion' => false,
            ]);
            break;
            
        case ROL_SECRETARIA:
            $permisos = array_merge($permisos, [
                'gestionar_usuarios' => false,
                'gestionar_configuracion' => false,
                'ver_todos_pacientes' => true,
                'ver_todo_historial' => false,
                'crear_pacientes' => true,
                'editar_pacientes' => true,
                'eliminar_pacientes' => false,
                'crear_consultas' => false,
                'editar_consultas' => false,
                'eliminar_consultas' => false,
                'gestionar_agenda' => true,
                'ver_estadisticas' => false,
                'generar_reportes' => false,
                'acceder_configuracion' => false,
            ]);
            break;
    }
    
    return $permisos;
}

// Verificar permiso
function tienePermiso($permiso) {
    $permisos = obtenerPermisos();
    return isset($permisos[$permiso]) && $permisos[$permiso] === true;
}

// Redirigir si no tiene permiso
function verificarPermiso($permiso, $redirigir = true) {
    if (!tienePermiso($permiso)) {
        if ($redirigir) {
            $_SESSION['error'] = 'No tienes permisos para acceder a esta sección';
            header('Location: dashboard.php');
            exit;
        }
        return false;
    }
    return true;
}

// Obtener nombre del rol
function nombreRol($rol = null) {
    if ($rol === null) {
        $rol = obtenerRolUsuario();
    }
    
    $nombres = [
        ROL_ADMIN => 'Administrador',
        ROL_DOCTOR => 'Médico',
        ROL_SECRETARIA => 'Secretaria'
    ];
    
    return $nombres[$rol] ?? 'Usuario';
}

// Obtener usuarios por rol
function obtenerUsuariosPorRol($rol = null) {
    global $conexion;
    
    $sql = "SELECT id, usuario, nombre, rol, activo FROM usuarios WHERE activo = 1";
    if ($rol !== null) {
        $sql .= " AND rol = ?";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("s", $rol);
        $stmt->execute();
        return $stmt->get_result();
    }
    
    return $conexion->query($sql);
}
?>