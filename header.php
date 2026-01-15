<?php
// header.php - ENCABEZADO COMÚN
// Este archivo se incluye después del menú

$pagina_actual = basename($_SERVER['PHP_SELF']);
$titulos = [
    'dashboard.php' => 'Panel de Control',
    'pacientes.php' => 'Gestión de Pacientes',
    'historial.php' => 'Historial Médico',
    'agenda.php' => 'Agenda de Citas',
    'estadisticas.php' => 'Estadísticas y Reportes',
    'configuracion.php' => 'Configuración del Sistema'
];

$titulo = $titulos[$pagina_actual] ?? 'Sistema Clínico';
?>
<!-- TOP BAR -->
<div class="top-bar mb-4">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1 class="h3 mb-1"><?php echo $titulo; ?></h1>
            <p class="text-muted small mb-0">
                <i class="bi bi-calendar3 me-1"></i>
                <?php echo date('d/m/Y'); ?> • 
                <span id="liveClock"><?php echo date('H:i:s'); ?></span>
            </p>
        </div>
        
        <div class="d-flex align-items-center gap-3">
            <div class="dropdown">
                <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                    <i class="bi bi-bell"></i>
                    <span class="badge bg-danger">3</span>
                </button>
                <div class="dropdown-menu dropdown-menu-end">
                    <h6 class="dropdown-header">Notificaciones</h6>
                    <a class="dropdown-item" href="#">
                        <i class="bi bi-person-plus text-primary me-2"></i>
                        Nuevo paciente registrado
                    </a>
                    <a class="dropdown-item" href="#">
                        <i class="bi bi-calendar-check text-success me-2"></i>
                        Cita programada para hoy
                    </a>
                    <a class="dropdown-item" href="#">
                        <i class="bi bi-exclamation-triangle text-warning me-2"></i>
                        Recordatorio: Actualizar historial
                    </a>
                </div>
            </div>
            
            <div class="dropdown">
                <button class="btn btn-light border rounded-pill d-flex align-items-center" type="button" data-bs-toggle="dropdown">
                    <div class="user-avatar me-2">
                        <?php echo isset($_SESSION['usuario']) ? strtoupper(substr($_SESSION['usuario'], 0, 1)) : 'U'; ?>
                    </div>
                    <span class="me-2"><?php echo isset($_SESSION['usuario']) ? $_SESSION['usuario'] : 'Usuario'; ?></span>
                    <i class="bi bi-chevron-down"></i>
                </button>
                <div class="dropdown-menu dropdown-menu-end">
                    <h6 class="dropdown-header">Mi Cuenta</h6>
                    <a class="dropdown-item" href="perfil.php">
                        <i class="bi bi-person me-2"></i> Mi Perfil
                    </a>
                    <a class="dropdown-item" href="configuracion.php">
                        <i class="bi bi-gear me-2"></i> Configuración
                    </a>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item text-danger" href="logout.php">
                        <i class="bi bi-box-arrow-right me-2"></i> Cerrar Sesión
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .top-bar {
        background: white;
        padding: 1.5rem;
        border-radius: 12px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
    }
    
    .user-avatar {
        width: 35px;
        height: 35px;
        border-radius: 50%;
        background: linear-gradient(135deg, #3498db, #9b59b6);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: bold;
    }
</style>

<script>
    // RELOJ EN TIEMPO REAL
    function updateClock() {
        const now = new Date();
        const hours = now.getHours().toString().padStart(2, '0');
        const minutes = now.getMinutes().toString().padStart(2, '0');
        const seconds = now.getSeconds().toString().padStart(2, '0');
        document.getElementById('liveClock').textContent = `${hours}:${minutes}:${seconds}`;
    }
    
    setInterval(updateClock, 1000);
</script>