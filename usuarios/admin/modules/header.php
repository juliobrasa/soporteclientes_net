<?php
/**
 * ==========================================================================
 * HEADER MODULAR - Kavia Hoteles Panel de Administración
 * Header reutilizable para el panel de administración
 * ==========================================================================
 */

// Configuración del header
$headerConfig = [
    'title' => 'Panel de Administración - Kavia Hoteles',
    'subtitle' => 'Gestión de Hoteles, IA, APIs y Extracción de Reseñas - Versión Modular 2.0',
    'version' => 'v2.0',
    'showVersion' => true,
    'showUserInfo' => true,
    'showNotifications' => true
];

// Obtener información del usuario si está disponible
$userInfo = null;
if (isset($_SESSION['user'])) {
    $userInfo = $_SESSION['user'];
}
?>

<!-- Header Principal -->
<div class="header">
    <div class="header-content">
        <div class="header-left">
            <h1 class="header-title">
                <i class="fas fa-hotel"></i> 
                <?php echo htmlspecialchars($headerConfig['title']); ?>
            </h1>
            <p class="header-subtitle">
                <?php echo htmlspecialchars($headerConfig['subtitle']); ?>
            </p>
        </div>
        
        <div class="header-right">
            <?php if ($headerConfig['showVersion']): ?>
                <div class="version-badge">
                    <span class="version-text"><?php echo $headerConfig['version']; ?></span>
                </div>
            <?php endif; ?>
            
            <?php if ($headerConfig['showUserInfo'] && $userInfo): ?>
                <div class="user-info">
                    <i class="fas fa-user-circle"></i>
                    <span class="user-name"><?php echo htmlspecialchars($userInfo['name'] ?? 'Usuario'); ?></span>
                </div>
            <?php endif; ?>
            
            <?php if ($headerConfig['showNotifications']): ?>
                <div class="notification-toggle" id="notification-toggle">
                    <i class="fas fa-bell"></i>
                    <span class="notification-count" id="notification-count" style="display: none;">0</span>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Barra de estado del sistema -->
    <div class="system-status" id="system-status">
        <div class="status-item">
            <i class="fas fa-circle status-indicator" id="api-status"></i>
            <span>API</span>
        </div>
        <div class="status-item">
            <i class="fas fa-circle status-indicator" id="db-status"></i>
            <span>Base de Datos</span>
        </div>
        <div class="status-item">
            <i class="fas fa-circle status-indicator" id="ai-status"></i>
            <span>IA</span>
        </div>
    </div>
</div>

<!-- Estilos específicos del header -->
<style>
.header {
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
    color: white;
    padding: 1.5rem 2rem;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    position: relative;
    z-index: 100;
}

.header-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    max-width: 1400px;
    margin: 0 auto;
}

.header-left {
    flex: 1;
}

.header-title {
    margin: 0 0 0.5rem 0;
    font-size: 1.8rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.header-title i {
    font-size: 1.5rem;
    opacity: 0.9;
}

.header-subtitle {
    margin: 0;
    opacity: 0.9;
    font-size: 0.95rem;
    font-weight: 400;
}

.header-right {
    display: flex;
    align-items: center;
    gap: 1.5rem;
}

.version-badge {
    background: rgba(255, 255, 255, 0.2);
    padding: 0.25rem 0.75rem;
    border-radius: 1rem;
    font-size: 0.8rem;
    font-weight: 500;
    backdrop-filter: blur(10px);
}

.user-info {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.9rem;
    opacity: 0.9;
}

.user-info i {
    font-size: 1.2rem;
}

.notification-toggle {
    position: relative;
    cursor: pointer;
    padding: 0.5rem;
    border-radius: 50%;
    transition: background-color 0.2s;
}

.notification-toggle:hover {
    background: rgba(255, 255, 255, 0.1);
}

.notification-toggle i {
    font-size: 1.2rem;
}

.notification-count {
    position: absolute;
    top: -5px;
    right: -5px;
    background: var(--danger);
    color: white;
    border-radius: 50%;
    width: 18px;
    height: 18px;
    font-size: 0.7rem;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
}

.system-status {
    display: flex;
    gap: 2rem;
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
}

.status-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.85rem;
    opacity: 0.8;
}

.status-indicator {
    font-size: 0.6rem;
    transition: color 0.3s;
}

.status-indicator.online {
    color: var(--success);
}

.status-indicator.offline {
    color: var(--danger);
}

.status-indicator.warning {
    color: var(--warning);
}

/* Responsive */
@media (max-width: 768px) {
    .header-content {
        flex-direction: column;
        gap: 1rem;
        text-align: center;
    }
    
    .header-right {
        justify-content: center;
    }
    
    .system-status {
        justify-content: center;
        flex-wrap: wrap;
    }
}
</style>

<!-- Script para el header -->
<script>
/**
 * Header Manager - Gestor del header del panel
 */
class HeaderManager {
    constructor() {
        this.init();
    }
    
    init() {
        this.updateSystemStatus();
        this.bindEvents();
        
        // Actualizar estado cada 30 segundos
        setInterval(() => {
            this.updateSystemStatus();
        }, 30000);
    }
    
    /**
     * Actualiza el estado del sistema
     */
    async updateSystemStatus() {
        try {
            // Verificar estado de API
            const apiStatus = await this.checkApiStatus();
            this.updateStatusIndicator('api-status', apiStatus);
            
            // Verificar estado de BD
            const dbStatus = await this.checkDatabaseStatus();
            this.updateStatusIndicator('db-status', dbStatus);
            
            // Verificar estado de IA
            const aiStatus = await this.checkAiStatus();
            this.updateStatusIndicator('ai-status', aiStatus);
            
        } catch (error) {
            console.error('Error al actualizar estado del sistema:', error);
        }
    }
    
    /**
     * Verifica el estado de la API
     */
    async checkApiStatus() {
        try {
            const response = await fetch('admin_api.php?action=ping');
            return response.ok ? 'online' : 'offline';
        } catch {
            return 'offline';
        }
    }
    
    /**
     * Verifica el estado de la base de datos
     */
    async checkDatabaseStatus() {
        try {
            const response = await fetch('admin_api.php?action=db_status');
            const data = await response.json();
            return data.success ? 'online' : 'offline';
        } catch {
            return 'offline';
        }
    }
    
    /**
     * Verifica el estado de los servicios de IA
     */
    async checkAiStatus() {
        try {
            const response = await fetch('admin_api.php?action=ai_status');
            const data = await response.json();
            return data.available ? 'online' : 'warning';
        } catch {
            return 'warning';
        }
    }
    
    /**
     * Actualiza un indicador de estado
     */
    updateStatusIndicator(elementId, status) {
        const element = document.getElementById(elementId);
        if (!element) return;
        
        element.className = `fas fa-circle status-indicator ${status}`;
        
        // Agregar tooltip
        const tooltips = {
            'online': 'Operativo',
            'offline': 'No disponible',
            'warning': 'Limitado'
        };
        
        element.title = tooltips[status] || 'Desconocido';
    }
    
    /**
     * Vincula eventos del header
     */
    bindEvents() {
        // Toggle de notificaciones
        const notificationToggle = document.getElementById('notification-toggle');
        if (notificationToggle) {
            notificationToggle.addEventListener('click', () => {
                this.toggleNotifications();
            });
        }
    }
    
    /**
     * Toggle del panel de notificaciones
     */
    toggleNotifications() {
        // Implementar panel de notificaciones
        showInfo('Panel de notificaciones en desarrollo');
    }
}

// Inicializar header manager cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    if (typeof HeaderManager !== 'undefined') {
        window.headerManager = new HeaderManager();
    }
});
</script>