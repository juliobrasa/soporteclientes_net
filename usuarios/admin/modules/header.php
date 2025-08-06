<?php
/**
 * ==========================================================================
 * HEADER MODULAR - Kavia Hoteles Panel de Administración
 * Header reutilizable con indicadores de estado del sistema v2.0
 * ==========================================================================
 */

// Configuración del header
$headerConfig = [
    'title' => 'Panel de Administración - Kavia Hoteles',
    'subtitle' => 'Gestión Inteligente de Hoteles, IA, APIs y Extracción Automatizada - Versión Modular 2.0+',
    'version' => 'v2.0.1-stable',
    'showVersion' => true,
    'showUserInfo' => true,
    'showNotifications' => true,
    'showSystemStatus' => true,
    'showQuickActions' => true
];

// Obtener información del usuario si está disponible
$userInfo = [
    'name' => $_SESSION['user']['name'] ?? 'Administrador',
    'role' => $_SESSION['user']['role'] ?? 'Admin',
    'last_login' => $_SESSION['user']['last_login'] ?? date('Y-m-d H:i:s')
];

// Información del sistema para el header
$systemInfo = [
    'database' => 'soporteia_bookingkavia',
    'server' => $_SERVER['SERVER_NAME'] ?? 'localhost',
    'php_version' => PHP_VERSION,
    'environment' => $_SERVER['SERVER_NAME'] === 'localhost' ? 'development' : 'production',
    'timezone' => date_default_timezone_get()
];
?>

<!-- Header Principal -->
<header class="admin-header">
    <div class="header-content">
        <div class="header-left">
            <div class="header-brand">
                <h1 class="header-title">
                    <i class="fas fa-hotel header-icon"></i> 
                    <?php echo htmlspecialchars($headerConfig['title']); ?>
                </h1>
                <p class="header-subtitle">
                    <?php echo htmlspecialchars($headerConfig['subtitle']); ?>
                </p>
            </div>
        </div>
        
        <div class="header-center">
            <!-- Indicadores rápidos centrales -->
            <div class="quick-indicators" id="quick-indicators">
                <div class="indicator-item" id="hotels-indicator" title="Hoteles activos">
                    <i class="fas fa-hotel"></i>
                    <span class="indicator-value" id="hotels-count">-</span>
                    <span class="indicator-label">Hoteles</span>
                </div>
                <div class="indicator-item" id="reviews-indicator" title="Reseñas totales">
                    <i class="fas fa-star"></i>
                    <span class="indicator-value" id="reviews-count">-</span>
                    <span class="indicator-label">Reseñas</span>
                </div>
                <div class="indicator-item" id="ai-indicator" title="Proveedores IA activos">
                    <i class="fas fa-robot"></i>
                    <span class="indicator-value" id="ai-count">-</span>
                    <span class="indicator-label">IA Activos</span>
                </div>
            </div>
        </div>
        
        <div class="header-right">
            <?php if ($headerConfig['showVersion']): ?>
                <div class="version-badge">
                    <i class="fas fa-code-branch"></i>
                    <span class="version-text"><?php echo $headerConfig['version']; ?></span>
                </div>
            <?php endif; ?>
            
            <?php if ($headerConfig['showUserInfo'] && $userInfo): ?>
                <div class="user-info" id="user-info">
                    <div class="user-avatar">
                        <i class="fas fa-user-circle"></i>
                    </div>
                    <div class="user-details">
                        <span class="user-name"><?php echo htmlspecialchars($userInfo['name']); ?></span>
                        <span class="user-role"><?php echo htmlspecialchars($userInfo['role']); ?></span>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if ($headerConfig['showNotifications']): ?>
                <div class="notification-center" id="notification-center">
                    <button class="notification-toggle" id="notification-toggle" title="Centro de notificaciones">
                        <i class="fas fa-bell"></i>
                        <span class="notification-badge" id="notification-badge" style="display: none;">0</span>
                    </button>
                </div>
            <?php endif; ?>
            
            <?php if ($headerConfig['showQuickActions']): ?>
                <div class="header-actions">
                    <button class="action-btn" id="search-global" title="Búsqueda global (Ctrl+K)">
                        <i class="fas fa-search"></i>
                    </button>
                    <button class="action-btn" id="refresh-all" title="Actualizar sistema completo">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                    <button class="action-btn" id="settings-toggle" title="Configuración y preferencias">
                        <i class="fas fa-cog"></i>
                    </button>
                    <button class="action-btn" id="fullscreen-toggle" title="Pantalla completa (F11)">
                        <i class="fas fa-expand"></i>
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Barra de estado del sistema mejorada -->
    <?php if ($headerConfig['showSystemStatus']): ?>
    <div class="system-status" id="system-status">
        <div class="status-section">
            <div class="status-group">
                <div class="status-item" data-status="api">
                    <i class="fas fa-circle status-indicator" id="api-status"></i>
                    <span class="status-label">API</span>
                    <span class="status-detail" id="api-detail">Iniciando...</span>
                    <span class="status-metric" id="api-metric"></span>
                </div>
                <div class="status-item" data-status="database">
                    <i class="fas fa-circle status-indicator" id="db-status"></i>
                    <span class="status-label">Base de Datos</span>
                    <span class="status-detail" id="db-detail">Conectando...</span>
                    <span class="status-metric" id="db-metric"></span>
                </div>
            </div>
            
            <div class="status-group">
                <div class="status-item" data-status="ai">
                    <i class="fas fa-circle status-indicator" id="ai-status"></i>
                    <span class="status-label">Servicios IA</span>
                    <span class="status-detail" id="ai-detail">Verificando...</span>
                    <span class="status-metric" id="ai-metric"></span>
                </div>
                <div class="status-item" data-status="extraction">
                    <i class="fas fa-circle status-indicator" id="extraction-status"></i>
                    <span class="status-label">Extracción</span>
                    <span class="status-detail" id="extraction-detail">Listo</span>
                    <span class="status-metric" id="extraction-metric"></span>
                </div>
            </div>
        </div>
        
        <div class="system-info">
            <div class="info-group">
                <span class="info-item">
                    <i class="fas fa-database"></i>
                    <span class="info-label"><?php echo $systemInfo['database']; ?></span>
                </span>
                <span class="info-item">
                    <i class="fas fa-server"></i>
                    <span class="info-label"><?php echo $systemInfo['server']; ?></span>
                </span>
                <span class="info-item">
                    <i class="fab fa-php"></i>
                    <span class="info-label">PHP <?php echo $systemInfo['php_version']; ?></span>
                </span>
            </div>
            <div class="info-group">
                <span class="info-item">
                    <i class="fas fa-clock"></i>
                    <span class="info-label" id="current-time"><?php echo date('H:i:s'); ?></span>
                </span>
                <span class="info-item">
                    <i class="fas fa-<?php echo $systemInfo['environment'] === 'development' ? 'laptop-code' : 'cloud'; ?>"></i>
                    <span class="info-label"><?php echo ucfirst($systemInfo['environment']); ?></span>
                </span>
            </div>
        </div>
        
        <!-- Barra de progreso del sistema -->
        <div class="system-health" id="system-health">
            <div class="health-bar">
                <div class="health-fill" id="health-fill"></div>
            </div>
            <span class="health-label" id="health-label">Sistema: Inicializando...</span>
        </div>
    </div>
    <?php endif; ?>
</header>

<!-- Estilos específicos del header -->
<style>
:root {
    --header-primary: #4f46e5;
    --header-secondary: #7c3aed;
    --header-success: #059669;
    --header-warning: #d97706;
    --header-danger: #dc2626;
    --header-info: #0284c7;
    --header-dark: #111827;
    --header-light: #f9fafb;
    --header-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    --header-shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
}

.admin-header {
    background: linear-gradient(135deg, var(--header-primary) 0%, var(--header-secondary) 100%);
    color: white;
    box-shadow: var(--header-shadow-lg);
    position: sticky;
    top: 0;
    z-index: 1000;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(20px);
}

.header-content {
    display: grid;
    grid-template-columns: 1fr auto 1fr;
    align-items: center;
    max-width: 1400px;
    margin: 0 auto;
    padding: 1rem 2rem;
    gap: 2rem;
}

.header-left {
    justify-self: start;
}

.header-center {
    justify-self: center;
}

.header-right {
    justify-self: end;
    display: flex;
    align-items: center;
    gap: 1rem;
}

.header-brand {
    display: flex;
    flex-direction: column;
}

.header-title {
    margin: 0;
    font-size: 1.5rem;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    line-height: 1.2;
}

.header-icon {
    font-size: 1.25rem;
    opacity: 0.9;
    filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.2));
}

.header-subtitle {
    margin: 0.25rem 0 0 0;
    opacity: 0.85;
    font-size: 0.8125rem;
    font-weight: 400;
    line-height: 1.3;
    max-width: 400px;
}

/* Indicadores rápidos centrales */
.quick-indicators {
    display: flex;
    gap: 1.5rem;
}

.indicator-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.25rem;
    padding: 0.75rem 1rem;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 0.75rem;
    border: 1px solid rgba(255, 255, 255, 0.15);
    backdrop-filter: blur(10px);
    transition: all 0.2s ease;
    cursor: pointer;
    min-width: 80px;
}

.indicator-item:hover {
    background: rgba(255, 255, 255, 0.15);
    border-color: rgba(255, 255, 255, 0.25);
    transform: translateY(-2px);
}

.indicator-item i {
    font-size: 1.25rem;
    opacity: 0.9;
}

.indicator-value {
    font-size: 1.25rem;
    font-weight: 700;
    line-height: 1;
}

.indicator-label {
    font-size: 0.6875rem;
    opacity: 0.8;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.025em;
}

.version-badge {
    background: rgba(255, 255, 255, 0.15);
    padding: 0.375rem 0.75rem;
    border-radius: 1rem;
    font-size: 0.75rem;
    font-weight: 600;
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.15);
    display: flex;
    align-items: center;
    gap: 0.375rem;
    transition: all 0.2s ease;
}

.version-badge:hover {
    background: rgba(255, 255, 255, 0.2);
    border-color: rgba(255, 255, 255, 0.25);
}

.version-badge i {
    font-size: 0.625rem;
}

.user-info {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.5rem 0.75rem;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 0.75rem;
    border: 1px solid rgba(255, 255, 255, 0.15);
    cursor: pointer;
    transition: all 0.2s ease;
    backdrop-filter: blur(10px);
}

.user-info:hover {
    background: rgba(255, 255, 255, 0.15);
    border-color: rgba(255, 255, 255, 0.25);
}

.user-avatar i {
    font-size: 1.5rem;
}

.user-details {
    display: flex;
    flex-direction: column;
    gap: 0.125rem;
}

.user-name {
    font-size: 0.875rem;
    font-weight: 600;
    line-height: 1.2;
}

.user-role {
    font-size: 0.6875rem;
    opacity: 0.8;
    font-weight: 500;
}

.notification-center {
    position: relative;
}

.notification-toggle {
    background: rgba(255, 255, 255, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.15);
    color: white;
    padding: 0.625rem;
    border-radius: 0.75rem;
    cursor: pointer;
    transition: all 0.2s ease;
    position: relative;
    backdrop-filter: blur(10px);
}

.notification-toggle:hover {
    background: rgba(255, 255, 255, 0.15);
    border-color: rgba(255, 255, 255, 0.25);
    transform: translateY(-1px);
}

.notification-toggle i {
    font-size: 1rem;
}

.notification-badge {
    position: absolute;
    top: -4px;
    right: -4px;
    background: var(--header-danger);
    color: white;
    border-radius: 50%;
    width: 20px;
    height: 20px;
    font-size: 0.625rem;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    border: 2px solid white;
    animation: pulse 2s infinite;
}

.header-actions {
    display: flex;
    gap: 0.5rem;
}

.action-btn {
    background: rgba(255, 255, 255, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.15);
    color: white;
    padding: 0.625rem;
    border-radius: 0.5rem;
    cursor: pointer;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    backdrop-filter: blur(10px);
}

.action-btn:hover {
    background: rgba(255, 255, 255, 0.15);
    border-color: rgba(255, 255, 255, 0.25);
    transform: translateY(-1px);
}

.action-btn i {
    font-size: 0.875rem;
}

/* Sistema de Estado Mejorado */
.system-status {
    background: rgba(0, 0, 0, 0.15);
    border-top: 1px solid rgba(255, 255, 255, 0.1);
    padding: 1rem 2rem;
    backdrop-filter: blur(10px);
}

.status-section {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 1rem;
}

.status-group {
    display: flex;
    gap: 2rem;
}

.status-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.8125rem;
    transition: all 0.2s;
    padding: 0.375rem 0;
}

.status-item:hover {
    opacity: 1;
}

.status-indicator {
    font-size: 0.625rem;
    transition: all 0.3s ease;
}

.status-indicator.online {
    color: var(--header-success);
}

.status-indicator.offline {
    color: var(--header-danger);
}

.status-indicator.warning {
    color: var(--header-warning);
}

.status-indicator.loading {
    color: var(--header-info);
    animation: pulse 2s infinite;
}

.status-label {
    font-weight: 600;
    min-width: 80px;
}

.status-detail {
    font-size: 0.75rem;
    opacity: 0.8;
    min-width: 100px;
}

.status-metric {
    font-size: 0.6875rem;
    opacity: 0.6;
    margin-left: 0.5rem;
    font-weight: 500;
}

.system-info {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.75rem;
}

.info-group {
    display: flex;
    gap: 1.5rem;
}

.info-item {
    display: flex;
    align-items: center;
    gap: 0.375rem;
    font-size: 0.75rem;
    opacity: 0.8;
}

.info-label {
    font-weight: 500;
}

.system-health {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.health-bar {
    flex: 1;
    height: 4px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 2px;
    overflow: hidden;
    max-width: 200px;
}

.health-fill {
    height: 100%;
    background: linear-gradient(90deg, var(--header-success) 0%, var(--header-info) 100%);
    border-radius: 2px;
    width: 0%;
    transition: width 1s ease;
}

.health-label {
    font-size: 0.75rem;
    font-weight: 500;
    opacity: 0.9;
    min-width: 150px;
}

/* Animaciones */
@keyframes pulse {
    0%, 100% {
        opacity: 1;
    }
    50% {
        opacity: 0.5;
    }
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Responsive */
@media (max-width: 1200px) {
    .header-content {
        grid-template-columns: 1fr auto;
        gap: 1.5rem;
    }
    
    .header-center {
        display: none;
    }
}

@media (max-width: 1024px) {
    .header-content {
        padding: 1rem 1.5rem;
    }
    
    .system-status {
        padding: 1rem 1.5rem;
    }
    
    .status-section {
        flex-direction: column;
        gap: 1rem;
    }
    
    .status-group {
        gap: 1.5rem;
    }
    
    .info-group {
        gap: 1rem;
    }
}

@media (max-width: 768px) {
    .header-content {
        grid-template-columns: 1fr;
        text-align: center;
        padding: 1rem;
        gap: 1rem;
    }
    
    .header-right {
        justify-self: center;
        flex-wrap: wrap;
        justify-content: center;
    }
    
    .system-status {
        padding: 1rem;
    }
    
    .status-section {
        align-items: center;
    }
    
    .status-group {
        flex-direction: column;
        gap: 0.75rem;
        align-items: center;
    }
    
    .system-info {
        flex-direction: column;
        gap: 0.75rem;
        align-items: center;
    }
    
    .info-group {
        justify-content: center;
        flex-wrap: wrap;
        gap: 0.75rem;
    }
    
    .system-health {
        flex-direction: column;
        gap: 0.5rem;
        text-align: center;
    }
    
    .health-bar {
        max-width: 100%;
    }
    
    .header-subtitle {
        font-size: 0.75rem;
        max-width: none;
    }
}

@media (max-width: 480px) {
    .header-title {
        font-size: 1.25rem;
    }
    
    .header-subtitle {
        font-size: 0.6875rem;
    }
    
    .header-actions {
        gap: 0.25rem;
    }
    
    .action-btn {
        width: 36px;
        height: 36px;
        padding: 0.5rem;
    }
    
    .status-item {
        flex-direction: column;
        text-align: center;
        gap: 0.25rem;
    }
}
</style>

<!-- Script del header mejorado -->
<script>
/**
 * Header Manager v2.0 - Gestor avanzado del header del panel
 */
class HeaderManager {
    constructor() {
        this.statusCheckInterval = null;
        this.clockInterval = null;
        this.systemHealth = 0;
        this.quickStats = {
            hotels: 0,
            reviews: 0,
            aiProviders: 0
        };
        
        this.init();
    }
    
    init() {
        console.log('🎨 Header Manager v2.0 inicializando...');
        
        this.bindEvents();
        this.startStatusChecking();
        this.startClock();
        this.loadQuickStats();
        
        // Animación de inicio
        this.playInitAnimation();
        
        if (AdminConfig?.debug?.enabled) {
            console.log('🎨 Header Manager v2.0 inicializado');
        }
    }
    
    /**
     * Animación de inicio
     */
    playInitAnimation() {
        const indicators = document.querySelectorAll('.indicator-item, .status-item, .info-item');
        indicators.forEach((item, index) => {
            item.style.animation = `fadeIn 0.6s ease forwards ${index * 0.1}s`;
            item.style.opacity = '0';
        });
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
        
        // Búsqueda global
        const searchBtn = document.getElementById('search-global');
        if (searchBtn) {
            searchBtn.addEventListener('click', () => {
                this.openGlobalSearch();
            });
        }
        
        // Refrescar todo el sistema
        const refreshBtn = document.getElementById('refresh-all');
        if (refreshBtn) {
            refreshBtn.addEventListener('click', () => {
                this.refreshSystem();
            });
        }
        
        // Toggle de configuración
        const settingsBtn = document.getElementById('settings-toggle');
        if (settingsBtn) {
            settingsBtn.addEventListener('click', () => {
                this.showQuickSettings();
            });
        }
        
        // Pantalla completa
        const fullscreenBtn = document.getElementById('fullscreen-toggle');
        if (fullscreenBtn) {
            fullscreenBtn.addEventListener('click', () => {
                this.toggleFullscreen();
            });
        }
        
        // Indicadores rápidos
        const indicators = document.querySelectorAll('.indicator-item');
        indicators.forEach(indicator => {
            indicator.addEventListener('click', (e) => {
                this.handleIndicatorClick(e.currentTarget.id);
            });
        });
        
        // Atajos de teclado
        document.addEventListener('keydown', (e) => {
            if (e.ctrlKey && e.key === 'k') {
                e.preventDefault();
                this.openGlobalSearch();
            }
            if (e.key === 'F11') {
                e.preventDefault();
                this.toggleFullscreen();
            }
        });
    }
    
    /**
     * Inicia el reloj
     */
    startClock() {
        const updateClock = () => {
            const now = new Date();
            const timeElement = document.getElementById('current-time');
            if (timeElement) {
                timeElement.textContent = now.toLocaleTimeString();
            }
        };
        
        updateClock();
        this.clockInterval = setInterval(updateClock, 1000);
    }
    
    /**
     * Carga estadísticas rápidas
     */
    async loadQuickStats() {
        try {
            const response = await fetch('admin_api.php?action=getDbStats');
            const data = await response.json();
            
            if (data.success) {
                this.quickStats = {
                    hotels: data.stats.active_hotels || 0,
                    reviews: data.stats.total_reviews || 0,
                    aiProviders: data.stats.active_api_providers || 0
                };
                
                this.updateQuickIndicators();
            }
        } catch (error) {
            console.warn('No se pudieron cargar las estadísticas rápidas:', error);
        }
    }
    
    /**
     * Actualiza los indicadores rápidos
     */
    updateQuickIndicators() {
        const hotelsCount = document.getElementById('hotels-count');
        const reviewsCount = document.getElementById('reviews-count');
        const aiCount = document.getElementById('ai-count');
        
        if (hotelsCount) this.animateCounter(hotelsCount, this.quickStats.hotels);
        if (reviewsCount) this.animateCounter(reviewsCount, this.quickStats.reviews);
        if (aiCount) this.animateCounter(aiCount, this.quickStats.aiProviders);
    }
    
    /**
     * Anima un contador
     */
    animateCounter(element, targetValue) {
        const startValue = parseInt(element.textContent) || 0;
        const duration = 1000;
        const step = (targetValue - startValue) / (duration / 16);
        let currentValue = startValue;
        
        const animate = () => {
            currentValue += step;
            if ((step > 0 && currentValue >= targetValue) || (step < 0 && currentValue <= targetValue)) {
                element.textContent = targetValue.toLocaleString();
                return;
            }
            element.textContent = Math.round(currentValue).toLocaleString();
            requestAnimationFrame(animate);
        };
        
        animate();
    }
    
    /**
     * Inicia la verificación periódica del estado del sistema
     */
    startStatusChecking() {
        this.updateSystemStatus();
        
        // Actualizar cada 30 segundos
        this.statusCheckInterval = setInterval(() => {
            this.updateSystemStatus();
        }, 30000);
    }
    
    /**
     * Actualiza el estado del sistema
     */
    async updateSystemStatus() {
        try {
            let totalHealth = 0;
            let healthComponents = 0;
            
            // Verificar estado de API
            const apiStatus = await this.checkApiStatus();
            this.updateStatusIndicator('api-status', 'api-detail', 'api-metric', apiStatus);
            if (apiStatus.health !== undefined) {
                totalHealth += apiStatus.health;
                healthComponents++;
            }
            
            // Verificar estado de BD
            const dbStatus = await this.checkDatabaseStatus();
            this.updateStatusIndicator('db-status', 'db-detail', 'db-metric', dbStatus);
            if (dbStatus.health !== undefined) {
                totalHealth += dbStatus.health;
                healthComponents++;
            }
            
            // Verificar estado de IA
            const aiStatus = await this.checkAiStatus();
            this.updateStatusIndicator('ai-status', 'ai-detail', 'ai-metric', aiStatus);
            if (aiStatus.health !== undefined) {
                totalHealth += aiStatus.health;
                healthComponents++;
            }
            
            // Verificar estado de extracción
            const extractionStatus = await this.checkExtractionStatus();
            this.updateStatusIndicator('extraction-status', 'extraction-detail', 'extraction-metric', extractionStatus);
            if (extractionStatus.health !== undefined) {
                totalHealth += extractionStatus.health;
                healthComponents++;
            }
            
            // Actualizar salud del sistema
            this.systemHealth = healthComponents > 0 ? (totalHealth / healthComponents) * 100 : 0;
            this.updateSystemHealth();
            
            // Actualizar estadísticas rápidas
            this.loadQuickStats();
            
        } catch (error) {
            console.error('Error al actualizar estado del sistema:', error);
        }
    }
    
    /**
     * Verifica el estado de la API
     */
    async checkApiStatus() {
        try {
            const start = performance.now();
            const response = await fetch('admin_api.php?action=getDbStats');
            const responseTime = Math.round(performance.now() - start);
            
            if (response.ok) {
                const data = await response.json();
                return {
                    status: 'online',
                    detail: 'Operativo',
                    metric: `${responseTime}ms`,
                    health: 1,
                    tooltip: `API funcionando - Tiempo de respuesta: ${responseTime}ms`
                };
            }
            throw new Error('API no responde');
        } catch {
            return {
                status: 'offline',
                detail: 'Sin conexión',
                metric: 'Timeout',
                health: 0,
                tooltip: 'API no disponible'
            };
        }
    }
    
    /**
     * Verifica el estado de la base de datos
     */
    async checkDatabaseStatus() {
        try {
            const response = await fetch('admin_api.php?action=getDbStats');
            const data = await response.json();
            
            if (data.success) {
                const totalRecords = (data.stats.total_hotels || 0) + (data.stats.total_reviews || 0);
                return {
                    status: 'online',
                    detail: 'Conectada',
                    metric: `${totalRecords.toLocaleString()} registros`,
                    health: 1,
                    tooltip: `Base de datos operativa - ${totalRecords} registros totales`
                };
            }
            throw new Error(data.error || 'Error de BD');
        } catch (error) {
            return {
                status: 'offline',
                detail: 'Error conexión',
                metric: 'N/A',
                health: 0,
                tooltip: 'Base de datos no disponible'
            };
        }
    }
    
    /**
     * Verifica el estado de los servicios de IA
     */
    async checkAiStatus() {
        try {
            const response = await fetch('admin_api.php?action=getAiProviders');
            const data = await response.json();
            
            if (data.success) {
                const providers = data.providers || [];
                const activeProviders = providers.filter(p => parseInt(p.is_active) === 1);
                
                if (activeProviders.length > 0) {
                    return {
                        status: 'online',
                        detail: `${activeProviders.length} activos`,
                        metric: `${providers.length} total`,
                        health: 1,
                        tooltip: `${activeProviders.length} de ${providers.length} proveedores activos`
                    };
                } else {
                    return {
                        status: 'warning',
                        detail: 'Sin proveedores activos',
                        metric: `${providers.length} configurados`,
                        health: 0.5,
                        tooltip: 'Proveedores IA configurados pero ninguno activo'
                    };
                }
            } else {
                return {
                    status: 'warning',
                    detail: 'No configurados',
                    metric: '0 proveedores',
                    health: 0.3,
                    tooltip: 'Servicios IA no configurados'
                };
            }
        } catch {
            return {
                status: 'offline',
                detail: 'No disponible',
                metric: 'Error',
                health: 0,
                tooltip: 'Servicios IA no disponibles'
            };
        }
    }
    
    /**
     * Verifica el estado del sistema de extracción
     */
    async checkExtractionStatus() {
        try {
            const response = await fetch('admin_api.php?action=getApifyStatus');
            const data = await response.json();
            
            if (data.success) {
                return {
                    status: data.configured ? 'online' : 'warning',
                    detail: data.configured ? 'Configurado' : 'Pendiente config',
                    metric: data.configured ? 'Listo' : 'N/A',
                    health: data.configured ? 1 : 0.3,
                    tooltip: data.status
                };
            } else {
                return {
                    status: 'warning',
                    detail: 'No configurado',
                    metric: 'N/A',
                    health: 0.3,
                    tooltip: 'Sistema de extracción no configurado'
                };
            }
        } catch {
            return {
                status: 'offline',
                detail: 'Error',
                metric: 'N/A',
                health: 0,
                tooltip: 'Sistema de extracción no disponible'
            };
        }
    }
    
    /**
     * Actualiza un indicador de estado
     */
    updateStatusIndicator(indicatorId, detailId, metricId, statusData) {
        const indicator = document.getElementById(indicatorId);
        const detail = document.getElementById(detailId);
        const metric = document.getElementById(metricId);
        
        if (indicator) {
            indicator.className = `fas fa-circle status-indicator ${statusData.status}`;
            indicator.title = statusData.tooltip || statusData.detail;
        }
        
        if (detail) {
            detail.textContent = statusData.detail;
        }
        
        if (metric && statusData.metric) {
            metric.textContent = `(${statusData.metric})`;
        }
    }
    
    /**
     * Actualiza la salud del sistema
     */
    updateSystemHealth() {
        const healthFill = document.getElementById('health-fill');
        const healthLabel = document.getElementById('health-label');
        
        if (healthFill) {
            healthFill.style.width = `${this.systemHealth}%`;
        }
        
        if (healthLabel) {
            let status = 'Crítico';
            if (this.systemHealth >= 80) status = 'Excelente';
            else if (this.systemHealth >= 60) status = 'Bueno';
            else if (this.systemHealth >= 40) status = 'Regular';
            else if (this.systemHealth >= 20) status = 'Bajo';
            
            healthLabel.textContent = `Sistema: ${status} (${Math.round(this.systemHealth)}%)`;
        }
    }
    
    /**
     * Manejo de clics en indicadores
     */
    handleIndicatorClick(indicatorId) {
        switch (indicatorId) {
            case 'hotels-indicator':
                if (window.navigationManager) {
                    window.navigationManager.switchTab('hotels');
                }
                break;
            case 'reviews-indicator':
                this.showReviewsModal();
                break;
            case 'ai-indicator':
                if (window.navigationManager) {
                    window.navigationManager.switchTab('ia');
                }
                break;
        }
    }
    
    /**
     * Muestra modal de reseñas
     */
    showReviewsModal() {
        if (window.notificationSystem) {
            window.notificationSystem.info('Modal de estadísticas de reseñas - Próximamente');
        }
    }
    
    /**
     * Toggle del panel de notificaciones
     */
    toggleNotifications() {
        if (window.notificationSystem) {
            window.notificationSystem.info('Centro de notificaciones avanzado - Próximamente', {
                duration: 3000
            });
        }
    }
    
    /**
     * Abrir búsqueda global
     */
    openGlobalSearch() {
        if (window.notificationSystem) {
            window.notificationSystem.info('Búsqueda global (Ctrl+K) - Próximamente', {
                duration: 3000
            });
        }
    }
    
    /**
     * Toggle pantalla completa
     */
    async toggleFullscreen() {
        try {
            if (!document.fullscreenElement) {
                await document.documentElement.requestFullscreen();
                document.getElementById('fullscreen-toggle').innerHTML = '<i class="fas fa-compress"></i>';
            } else {
                await document.exitFullscreen();
                document.getElementById('fullscreen-toggle').innerHTML = '<i class="fas fa-expand"></i>';
            }
        } catch (error) {
            console.warn('Error al cambiar pantalla completa:', error);
        }
    }
    
    /**
     * Refrescar todo el sistema
     */
    async refreshSystem() {
        const refreshBtn = document.getElementById('refresh-all');
        if (refreshBtn) {
            refreshBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            refreshBtn.disabled = true;
        }
        
        try {
            // Actualizar estado del sistema
            await this.updateSystemStatus();
            
            // Refrescar tab actual si hay tab manager
            if (window.tabManager) {
                await window.tabManager.refreshCurrentTab();
            }
            
            if (window.notificationSystem) {
                window.notificationSystem.success('Sistema actualizado correctamente', {
                    duration: 2000
                });
            }
        } catch (error) {
            if (window.notificationSystem) {
                window.notificationSystem.error('Error al actualizar el sistema');
            }
        } finally {
            if (refreshBtn) {
                refreshBtn.innerHTML = '<i class="fas fa-sync-alt"></i>';
                refreshBtn.disabled = false;
            }
        }
    }
    
    /**
     * Mostrar configuración rápida
     */
    showQuickSettings() {
        const settings = [
            { label: 'Modo Debug', key: 'debug', value: AdminConfig?.debug?.enabled || false },
            { label: 'Auto-refresh Estado', key: 'autorefresh', value: true },
            { label: 'Notificaciones', key: 'notifications', value: true },
            { label: 'Sonidos', key: 'sounds', value: false },
            { label: 'Animaciones', key: 'animations', value: true }
        ];
        
        let settingsHtml = '<div class="quick-settings-grid">';
        settings.forEach(setting => {
            settingsHtml += `
                <div class="setting-item">
                    <label class="setting-label">
                        <input type="checkbox" ${setting.value ? 'checked' : ''} 
                               onchange="headerManager.toggleSetting('${setting.key}', this.checked)">
                        <span class="setting-text">${setting.label}</span>
                    </label>
                </div>
            `;
        });
        settingsHtml += '</div>';
        
        if (window.modalManager) {
            window.modalManager.custom({
                title: 'Configuración Rápida',
                content: settingsHtml,
                size: 'md'
            });
        } else {
            alert('Configuración rápida - Modal manager no disponible');
        }
    }
    
    /**
     * Toggle de configuración
     */
    toggleSetting(key, value) {
        switch(key) {
            case 'debug':
                if (AdminConfig && AdminConfig.debug) {
                    AdminConfig.debug.enabled = value;
                }
                console.log(`🔧 Debug mode: ${value ? 'ON' : 'OFF'}`);
                break;
            case 'autorefresh':
                if (value) {
                    this.startStatusChecking();
                } else {
                    if (this.statusCheckInterval) {
                        clearInterval(this.statusCheckInterval);
                    }
                }
                break;
            case 'animations':
                document.body.style.setProperty('--animation-duration', value ? '0.3s' : '0s');
                break;
        }
        
        if (window.notificationSystem) {
            window.notificationSystem.info(`${key} ${value ? 'activado' : 'desactivado'}`, {
                duration: 1500
            });
        }
    }
    
    /**
     * Destructor
     */
    destroy() {
        if (this.statusCheckInterval) {
            clearInterval(this.statusCheckInterval);
        }
        if (this.clockInterval) {
            clearInterval(this.clockInterval);
        }
    }
}

// Inicializar header manager cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    if (typeof HeaderManager !== 'undefined') {
        window.headerManager = new HeaderManager();
        console.log('✅ Header Manager v2.0 listo');
    }
});
</script>