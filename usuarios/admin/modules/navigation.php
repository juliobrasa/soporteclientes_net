<?php
/**
 * ==========================================================================
 * NAVIGATION MODULAR - Kavia Hoteles Panel de Administración
 * Sistema de navegación avanzado por tabs v2.0
 * ==========================================================================
 */

// Configuración de navegación mejorada
$navConfig = [
    'tabs' => [
        'hotels' => [
            'label' => 'Hoteles',
            'icon' => 'fas fa-hotel',
            'shortcut' => '1',
            'description' => 'Gestión completa de hoteles y propiedades',
            'badge' => 'active',
            'color' => '#3b82f6',
            'module' => 'hotels'
        ],
        'apis' => [
            'label' => 'APIs Externas',
            'icon' => 'fas fa-plug',
            'shortcut' => '2',
            'description' => 'Configuración de APIs de extracción y scrapers',
            'badge' => null,
            'color' => '#10b981',
            'module' => 'apis'
        ],
        'extraction' => [
            'label' => 'Extractor',
            'icon' => 'fas fa-download',
            'shortcut' => '3',
            'description' => 'Sistema de extracción automatizada de reseñas',
            'badge' => 'beta',
            'color' => '#f59e0b',
            'module' => 'extraction'
        ],
        'ia' => [
            'label' => 'IA & Proveedores',
            'icon' => 'fas fa-robot',
            'shortcut' => '4',
            'description' => 'Configuración de servicios de inteligencia artificial',
            'badge' => 'new',
            'color' => '#8b5cf6',
            'module' => 'providers'
        ],
        'prompts' => [
            'label' => 'Prompts',
            'icon' => 'fas fa-file-alt',
            'shortcut' => '5',
            'description' => 'Gestión avanzada de plantillas y prompts de IA',
            'badge' => null,
            'color' => '#ef4444',
            'module' => 'prompts'
        ],
        'logs' => [
            'label' => 'Analytics',
            'icon' => 'fas fa-chart-line',
            'shortcut' => '6',
            'description' => 'Registros detallados y análisis del sistema',
            'badge' => null,
            'color' => '#06b6d4',
            'module' => 'logs'
        ],
        'tools' => [
            'label' => 'Herramientas',
            'icon' => 'fas fa-tools',
            'shortcut' => '7',
            'description' => 'Utilidades de mantenimiento y optimización',
            'badge' => null,
            'color' => '#6b7280',
            'module' => 'tools'
        ]
    ],
    'layout' => [
        'style' => 'modern', // classic, modern, minimal
        'showShortcuts' => true,
        'showDescriptions' => true,
        'showBadges' => true,
        'enableAnimations' => true,
        'enableSearch' => true,
        'persistState' => true,
        'autoHide' => false
    ],
    'features' => [
        'breadcrumbs' => true,
        'tabHistory' => true,
        'quickActions' => true,
        'contextMenu' => true,
        'dragReorder' => false
    ]
];

// Obtener tab activo desde URL, sesión o default
$activeTab = $_GET['tab'] ?? $_SESSION['active_tab'] ?? 'hotels';

// Obtener información de rutas para breadcrumbs
$currentPath = [
    'section' => 'Panel de Administración',
    'subsection' => $navConfig['tabs'][$activeTab]['label'] ?? 'Dashboard',
    'action' => $_GET['action'] ?? null
];
?>

<!-- Sistema de Navegación Avanzado -->
<nav class="navigation-container modern-nav" data-layout="<?php echo $navConfig['layout']['style']; ?>">
    <!-- Barra de Navegación Principal -->
    <div class="nav-header">
        <div class="nav-brand">
            <i class="fas fa-layer-group nav-brand-icon"></i>
            <span class="nav-brand-text">Panel de Control</span>
        </div>
        
        <!-- Breadcrumbs -->
        <?php if ($navConfig['features']['breadcrumbs']): ?>
        <div class="breadcrumbs" id="breadcrumbs">
            <span class="breadcrumb-item">
                <i class="fas fa-home"></i>
                <?php echo htmlspecialchars($currentPath['section']); ?>
            </span>
            <i class="fas fa-chevron-right breadcrumb-separator"></i>
            <span class="breadcrumb-item current">
                <?php echo htmlspecialchars($currentPath['subsection']); ?>
            </span>
            <?php if ($currentPath['action']): ?>
            <i class="fas fa-chevron-right breadcrumb-separator"></i>
            <span class="breadcrumb-item current">
                <?php echo htmlspecialchars(ucfirst($currentPath['action'])); ?>
            </span>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <!-- Búsqueda de Tabs -->
        <?php if ($navConfig['layout']['enableSearch']): ?>
        <div class="nav-search" id="nav-search">
            <i class="fas fa-search search-icon"></i>
            <input type="text" class="search-input" placeholder="Buscar funcionalidad..." id="tab-search">
            <div class="search-results" id="search-results"></div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Tabs de Navegación -->
    <div class="nav-tabs-container">
        <div class="nav-tabs" id="main-tabs">
            <?php foreach ($navConfig['tabs'] as $tabId => $tabConfig): ?>
                <button class="nav-tab <?php echo $activeTab === $tabId ? 'active' : ''; ?>" 
                        data-tab="<?php echo $tabId; ?>"
                        data-module="<?php echo $tabConfig['module']; ?>"
                        data-shortcut="<?php echo $tabConfig['shortcut']; ?>"
                        data-color="<?php echo $tabConfig['color']; ?>"
                        title="<?php echo htmlspecialchars($tabConfig['description']); ?>"
                        style="--tab-color: <?php echo $tabConfig['color']; ?>">
                    
                    <!-- Icono y contenido principal -->
                    <div class="nav-tab-content">
                        <i class="<?php echo $tabConfig['icon']; ?> nav-tab-icon"></i>
                        <span class="nav-tab-label"><?php echo htmlspecialchars($tabConfig['label']); ?></span>
                        
                        <!-- Badges -->
                        <?php if ($navConfig['layout']['showBadges'] && $tabConfig['badge']): ?>
                            <span class="nav-tab-badge badge-<?php echo $tabConfig['badge']; ?>">
                                <?php echo ucfirst($tabConfig['badge']); ?>
                            </span>
                        <?php endif; ?>
                        
                        <!-- Shortcut -->
                        <?php if ($navConfig['layout']['showShortcuts']): ?>
                            <span class="nav-tab-shortcut">Ctrl+<?php echo $tabConfig['shortcut']; ?></span>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Indicator de estado -->
                    <div class="nav-tab-indicator" id="indicator-<?php echo $tabId; ?>"></div>
                    
                    <!-- Progress bar para carga -->
                    <div class="nav-tab-progress" id="progress-<?php echo $tabId; ?>"></div>
                </button>
            <?php endforeach; ?>
        </div>
        
        <!-- Navegación Scroll -->
        <div class="nav-scroll-controls">
            <button class="nav-scroll-btn" id="scroll-left" title="Desplazar izquierda">
                <i class="fas fa-chevron-left"></i>
            </button>
            <button class="nav-scroll-btn" id="scroll-right" title="Desplazar derecha">
                <i class="fas fa-chevron-right"></i>
            </button>
        </div>
    </div>

    <!-- Barra de Acciones y Estado -->
    <div class="nav-actions-bar">
        <div class="nav-actions-left">
            <!-- Historial de navegación -->
            <?php if ($navConfig['features']['tabHistory']): ?>
            <div class="nav-history">
                <button class="nav-action-btn" id="nav-back" title="Ir atrás (Alt+←)" disabled>
                    <i class="fas fa-arrow-left"></i>
                </button>
                <button class="nav-action-btn" id="nav-forward" title="Ir adelante (Alt+→)" disabled>
                    <i class="fas fa-arrow-right"></i>
                </button>
            </div>
            <?php endif; ?>
            
            <!-- Información del tab actual -->
            <div class="nav-current-info">
                <span class="current-tab-name" id="current-tab-name">
                    <?php echo htmlspecialchars($navConfig['tabs'][$activeTab]['label']); ?>
                </span>
                <span class="current-tab-description" id="current-tab-description">
                    <?php echo htmlspecialchars($navConfig['tabs'][$activeTab]['description']); ?>
                </span>
            </div>
        </div>
        
        <div class="nav-actions-right">
            <!-- Estado de carga -->
            <div class="nav-loading-indicator" id="nav-loading">
                <i class="fas fa-spinner fa-spin"></i>
                <span>Cargando...</span>
            </div>
            
            <!-- Acciones rápidas -->
            <?php if ($navConfig['features']['quickActions']): ?>
            <div class="nav-quick-actions">
                <button class="nav-action-btn" id="refresh-current-tab" title="Actualizar tab actual (F5)">
                    <i class="fas fa-sync-alt"></i>
                </button>
                <button class="nav-action-btn" id="pin-current-tab" title="Fijar tab actual">
                    <i class="fas fa-thumbtack"></i>
                </button>
                <button class="nav-action-btn" id="duplicate-tab" title="Duplicar en nueva ventana">
                    <i class="fas fa-external-link-alt"></i>
                </button>
                <button class="nav-action-btn" id="nav-settings" title="Configurar navegación">
                    <i class="fas fa-cog"></i>
                </button>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Progress bar global -->
    <div class="nav-global-progress" id="nav-global-progress"></div>
</nav>

<!-- Estilos específicos de navegación v2.0 -->
<style>
:root {
    --nav-primary: #4f46e5;
    --nav-secondary: #7c3aed;
    --nav-success: #10b981;
    --nav-warning: #f59e0b;
    --nav-danger: #ef4444;
    --nav-info: #3b82f6;
    --nav-bg: #ffffff;
    --nav-bg-secondary: #f8fafc;
    --nav-border: #e2e8f0;
    --nav-text: #1e293b;
    --nav-text-muted: #64748b;
    --nav-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    --nav-shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
    --nav-radius: 0.75rem;
    --nav-transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.navigation-container {
    background: var(--nav-bg);
    border-bottom: 1px solid var(--nav-border);
    box-shadow: var(--nav-shadow);
    position: sticky;
    top: 0;
    z-index: 100;
    backdrop-filter: blur(20px);
    background: rgba(255, 255, 255, 0.95);
}

/* Header de navegación */
.nav-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1rem 2rem;
    border-bottom: 1px solid var(--nav-border);
}

.nav-brand {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    font-weight: 700;
    color: var(--nav-primary);
}

.nav-brand-icon {
    font-size: 1.25rem;
    opacity: 0.9;
}

.nav-brand-text {
    font-size: 1rem;
    letter-spacing: -0.025em;
}

/* Breadcrumbs */
.breadcrumbs {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
    color: var(--nav-text-muted);
}

.breadcrumb-item {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    transition: var(--nav-transition);
}

.breadcrumb-item.current {
    color: var(--nav-text);
    font-weight: 600;
}

.breadcrumb-item i {
    font-size: 0.75rem;
}

.breadcrumb-separator {
    font-size: 0.625rem;
    opacity: 0.5;
}

/* Búsqueda de tabs */
.nav-search {
    position: relative;
    width: 300px;
}

.nav-search .search-input {
    width: 100%;
    padding: 0.5rem 1rem 0.5rem 2.5rem;
    border: 1px solid var(--nav-border);
    border-radius: var(--nav-radius);
    background: var(--nav-bg-secondary);
    font-size: 0.875rem;
    transition: var(--nav-transition);
}

.nav-search .search-input:focus {
    outline: none;
    border-color: var(--nav-primary);
    background: var(--nav-bg);
    box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
}

.nav-search .search-icon {
    position: absolute;
    left: 0.75rem;
    top: 50%;
    transform: translateY(-50%);
    color: var(--nav-text-muted);
    font-size: 0.875rem;
}

.search-results {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: var(--nav-bg);
    border: 1px solid var(--nav-border);
    border-radius: var(--nav-radius);
    box-shadow: var(--nav-shadow-lg);
    z-index: 1000;
    display: none;
    max-height: 300px;
    overflow-y: auto;
}

/* Container de tabs */
.nav-tabs-container {
    display: flex;
    align-items: center;
    position: relative;
}

.nav-tabs {
    display: flex;
    overflow-x: auto;
    scroll-behavior: smooth;
    scrollbar-width: none;
    -ms-overflow-style: none;
    flex: 1;
    padding: 0 2rem;
}

.nav-tabs::-webkit-scrollbar {
    display: none;
}

/* Tabs individuales */
.nav-tab {
    display: flex;
    align-items: center;
    padding: 1rem 1.5rem;
    background: transparent;
    border: none;
    border-bottom: 3px solid transparent;
    cursor: pointer;
    transition: var(--nav-transition);
    white-space: nowrap;
    min-width: 140px;
    position: relative;
    color: var(--nav-text-muted);
    font-size: 0.875rem;
    font-weight: 500;
}

.nav-tab:hover {
    background: rgba(79, 70, 229, 0.05);
    color: var(--nav-text);
    transform: translateY(-1px);
}

.nav-tab.active {
    color: var(--tab-color, var(--nav-primary));
    border-bottom-color: var(--tab-color, var(--nav-primary));
    background: rgba(79, 70, 229, 0.08);
    transform: translateY(-2px);
}

.nav-tab-content {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    flex: 1;
    position: relative;
}

.nav-tab-icon {
    font-size: 1rem;
    transition: var(--nav-transition);
}

.nav-tab.active .nav-tab-icon {
    color: var(--tab-color, var(--nav-primary));
    transform: scale(1.1);
}

.nav-tab-label {
    font-weight: 600;
    line-height: 1.2;
}

/* Badges */
.nav-tab-badge {
    padding: 0.125rem 0.5rem;
    border-radius: 1rem;
    font-size: 0.625rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin-left: 0.5rem;
}

.badge-active {
    background: var(--nav-success);
    color: white;
}

.badge-beta {
    background: var(--nav-warning);
    color: white;
}

.badge-new {
    background: var(--nav-info);
    color: white;
    animation: pulse-badge 2s infinite;
}

/* Shortcuts */
.nav-tab-shortcut {
    font-size: 0.625rem;
    opacity: 0.6;
    background: rgba(0, 0, 0, 0.05);
    padding: 0.125rem 0.375rem;
    border-radius: 0.25rem;
    font-family: monospace;
    margin-left: 0.5rem;
}

/* Indicadores de estado */
.nav-tab-indicator {
    position: absolute;
    top: 0.5rem;
    right: 0.5rem;
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: transparent;
    transition: var(--nav-transition);
}

.nav-tab-indicator.online {
    background: var(--nav-success);
    box-shadow: 0 0 0 2px rgba(16, 185, 129, 0.3);
}

.nav-tab-indicator.loading {
    background: var(--nav-warning);
    animation: pulse 2s infinite;
}

.nav-tab-indicator.error {
    background: var(--nav-danger);
}

/* Progress bars */
.nav-tab-progress {
    position: absolute;
    bottom: 0;
    left: 0;
    height: 2px;
    background: var(--tab-color, var(--nav-primary));
    width: 0%;
    transition: width 0.3s ease;
}

.nav-tab.loading .nav-tab-progress {
    animation: progress-loading 2s infinite;
}

/* Controles de scroll */
.nav-scroll-controls {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
    padding: 0.5rem;
    border-left: 1px solid var(--nav-border);
}

.nav-scroll-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 24px;
    height: 24px;
    background: var(--nav-bg-secondary);
    border: 1px solid var(--nav-border);
    border-radius: 0.375rem;
    color: var(--nav-text-muted);
    cursor: pointer;
    transition: var(--nav-transition);
    font-size: 0.75rem;
}

.nav-scroll-btn:hover {
    background: var(--nav-primary);
    color: white;
    border-color: var(--nav-primary);
}

/* Barra de acciones */
.nav-actions-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.75rem 2rem;
    background: var(--nav-bg-secondary);
    border-top: 1px solid var(--nav-border);
}

.nav-actions-left,
.nav-actions-right {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.nav-history {
    display: flex;
    gap: 0.25rem;
}

.nav-action-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    background: var(--nav-bg);
    border: 1px solid var(--nav-border);
    border-radius: 0.5rem;
    color: var(--nav-text-muted);
    cursor: pointer;
    transition: var(--nav-transition);
    font-size: 0.875rem;
}

.nav-action-btn:hover:not(:disabled) {
    background: var(--nav-primary);
    color: white;
    border-color: var(--nav-primary);
    transform: translateY(-1px);
}

.nav-action-btn:disabled {
    opacity: 0.4;
    cursor: not-allowed;
}

/* Información del tab actual */
.nav-current-info {
    display: flex;
    flex-direction: column;
    gap: 0.125rem;
}

.current-tab-name {
    font-weight: 600;
    color: var(--nav-text);
    font-size: 0.875rem;
}

.current-tab-description {
    font-size: 0.75rem;
    color: var(--nav-text-muted);
    line-height: 1.2;
}

/* Indicador de carga */
.nav-loading-indicator {
    display: none;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.75rem;
    color: var(--nav-text-muted);
}

.nav-loading-indicator.active {
    display: flex;
}

/* Acciones rápidas */
.nav-quick-actions {
    display: flex;
    gap: 0.25rem;
}

/* Progress bar global */
.nav-global-progress {
    height: 2px;
    background: var(--nav-primary);
    width: 0%;
    transition: width 0.3s ease;
    position: absolute;
    bottom: 0;
    left: 0;
}

/* Animaciones */
@keyframes pulse-badge {
    0%, 100% {
        opacity: 1;
        transform: scale(1);
    }
    50% {
        opacity: 0.8;
        transform: scale(1.05);
    }
}

@keyframes pulse {
    0%, 100% {
        opacity: 1;
    }
    50% {
        opacity: 0.5;
    }
}

@keyframes progress-loading {
    0% {
        width: 0%;
    }
    50% {
        width: 70%;
    }
    100% {
        width: 100%;
    }
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Estados especiales */
.nav-tab.pinned::before {
    content: '📌';
    position: absolute;
    top: 0.25rem;
    left: 0.25rem;
    font-size: 0.625rem;
}

.nav-tab.has-updates::after {
    content: '';
    position: absolute;
    top: 0.75rem;
    right: 1rem;
    width: 6px;
    height: 6px;
    background: var(--nav-success);
    border-radius: 50%;
    animation: pulse 2s infinite;
}

/* Responsive */
@media (max-width: 1024px) {
    .nav-header {
        padding: 0.75rem 1rem;
    }
    
    .nav-search {
        width: 200px;
    }
    
    .nav-tabs {
        padding: 0 1rem;
    }
    
    .nav-actions-bar {
        padding: 0.5rem 1rem;
    }
}

@media (max-width: 768px) {
    .nav-header {
        flex-direction: column;
        gap: 0.75rem;
        padding: 1rem;
    }
    
    .breadcrumbs {
        order: -1;
    }
    
    .nav-search {
        width: 100%;
        max-width: 300px;
    }
    
    .nav-tab {
        min-width: 120px;
        padding: 0.75rem 1rem;
    }
    
    .nav-tab-shortcut {
        display: none;
    }
    
    .nav-actions-bar {
        flex-direction: column;
        gap: 0.75rem;
        align-items: stretch;
    }
    
    .nav-actions-left,
    .nav-actions-right {
        justify-content: center;
    }
    
    .current-tab-description {
        display: none;
    }
}

@media (max-width: 480px) {
    .nav-tab {
        min-width: 100px;
        padding: 0.5rem 0.75rem;
    }
    
    .nav-tab-label {
        font-size: 0.75rem;
    }
    
    .nav-tab-badge {
        display: none;
    }
    
    .nav-quick-actions {
        flex-wrap: wrap;
        justify-content: center;
    }
}

/* Dark mode support */
@media (prefers-color-scheme: dark) {
    :root {
        --nav-bg: #1e293b;
        --nav-bg-secondary: #334155;
        --nav-border: #475569;
        --nav-text: #f1f5f9;
        --nav-text-muted: #94a3b8;
    }
}
</style>

<!-- Script de navegación v2.0 -->
<script>
/**
 * Navigation Manager v2.0 - Sistema avanzado de navegación
 */
class NavigationManagerV2 {
    constructor() {
        this.currentTab = <?php echo json_encode($activeTab); ?>;
        this.tabHistory = [];
        this.forwardHistory = [];
        this.maxHistory = 20;
        this.searchResults = [];
        this.pinnedTabs = new Set();
        this.loadingTabs = new Set();
        
        this.config = <?php echo json_encode($navConfig); ?>;
        
        this.init();
    }
    
    init() {
        console.log('🧭 Navigation Manager v2.0 inicializando...');
        
        this.bindEvents();
        this.setupKeyboardShortcuts();
        this.initializeSearch();
        this.loadPersistedState();
        this.updateNavigationState();
        this.startStatusUpdates();
        
        // Animación de entrada
        this.playEntryAnimation();
        
        console.log('✅ Navigation Manager v2.0 listo');
    }
    
    /**
     * Animación de entrada
     */
    playEntryAnimation() {
        const tabs = document.querySelectorAll('.nav-tab');
        tabs.forEach((tab, index) => {
            tab.style.animation = `fadeInUp 0.6s ease forwards ${index * 0.1}s`;
            tab.style.opacity = '0';
        });
    }
    
    /**
     * Vincula todos los eventos
     */
    bindEvents() {
        // Eventos de tabs
        document.querySelectorAll('.nav-tab').forEach(button => {
            button.addEventListener('click', (e) => {
                const tabName = e.currentTarget.dataset.tab;
                this.switchTab(tabName);
            });
            
            // Context menu
            if (this.config.features.contextMenu) {
                button.addEventListener('contextmenu', (e) => {
                    e.preventDefault();
                    this.showTabContextMenu(e, e.currentTarget.dataset.tab);
                });
            }
        });
        
        // Navegación con historial
        document.getElementById('nav-back')?.addEventListener('click', () => {
            this.goBack();
        });
        
        document.getElementById('nav-forward')?.addEventListener('click', () => {
            this.goForward();
        });
        
        // Acciones rápidas
        document.getElementById('refresh-current-tab')?.addEventListener('click', () => {
            this.refreshCurrentTab();
        });
        
        document.getElementById('pin-current-tab')?.addEventListener('click', () => {
            this.togglePinTab(this.currentTab);
        });
        
        document.getElementById('duplicate-tab')?.addEventListener('click', () => {
            this.duplicateTab(this.currentTab);
        });
        
        document.getElementById('nav-settings')?.addEventListener('click', () => {
            this.showNavSettings();
        });
        
        // Controles de scroll
        document.getElementById('scroll-left')?.addEventListener('click', () => {
            this.scrollTabs('left');
        });
        
        document.getElementById('scroll-right')?.addEventListener('click', () => {
            this.scrollTabs('right');
        });
    }
    
    /**
     * Configura atajos de teclado avanzados
     */
    setupKeyboardShortcuts() {
        document.addEventListener('keydown', (e) => {
            // Solo si no estamos en un input/textarea
            if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') {
                return;
            }
            
            // Atajos de tabs (Ctrl+1, Ctrl+2, etc.)
            if (e.ctrlKey && !e.shiftKey && !e.altKey) {
                const key = e.key;
                if (key >= '1' && key <= '9') {
                    e.preventDefault();
                    const tabButtons = document.querySelectorAll('.nav-tab');
                    const index = parseInt(key) - 1;
                    if (tabButtons[index]) {
                        const tabName = tabButtons[index].dataset.tab;
                        this.switchTab(tabName);
                    }
                }
            }
            
            // Navegación con Alt + flechas
            if (e.altKey && !e.ctrlKey && !e.shiftKey) {
                if (e.key === 'ArrowLeft') {
                    e.preventDefault();
                    this.goBack();
                } else if (e.key === 'ArrowRight') {
                    e.preventDefault();
                    this.goForward();
                }
            }
            
            // Refresh con F5
            if (e.key === 'F5' && !e.ctrlKey) {
                e.preventDefault();
                this.refreshCurrentTab();
            }
            
            // Buscar tabs con Ctrl+Shift+P
            if (e.ctrlKey && e.shiftKey && e.key === 'P') {
                e.preventDefault();
                this.focusSearch();
            }
        });
    }
    
    /**
     * Inicializa el sistema de búsqueda
     */
    initializeSearch() {
        const searchInput = document.getElementById('tab-search');
        const searchResults = document.getElementById('search-results');
        
        if (searchInput && searchResults) {
            searchInput.addEventListener('input', (e) => {
                this.performSearch(e.target.value);
            });
            
            searchInput.addEventListener('focus', () => {
                if (searchInput.value.trim()) {
                    this.performSearch(searchInput.value);
                }
            });
            
            // Cerrar resultados al hacer clic fuera
            document.addEventListener('click', (e) => {
                if (!e.target.closest('.nav-search')) {
                    searchResults.style.display = 'none';
                }
            });
        }
    }
    
    /**
     * Realiza búsqueda de tabs
     */
    performSearch(query) {
        const searchResults = document.getElementById('search-results');
        if (!searchResults) return;
        
        if (!query.trim()) {
            searchResults.style.display = 'none';
            return;
        }
        
        const results = [];
        Object.entries(this.config.tabs).forEach(([tabId, tabConfig]) => {
            const score = this.calculateSearchScore(query.toLowerCase(), tabConfig);
            if (score > 0) {
                results.push({ tabId, tabConfig, score });
            }
        });
        
        results.sort((a, b) => b.score - a.score);
        
        if (results.length === 0) {
            searchResults.innerHTML = '<div class="search-no-results">Sin resultados</div>';
        } else {
            searchResults.innerHTML = results.map(result => `
                <div class="search-result-item" data-tab="${result.tabId}">
                    <i class="${result.tabConfig.icon}"></i>
                    <div class="search-result-content">
                        <div class="search-result-title">${result.tabConfig.label}</div>
                        <div class="search-result-description">${result.tabConfig.description}</div>
                    </div>
                    <div class="search-result-shortcut">Ctrl+${result.tabConfig.shortcut}</div>
                </div>
            `).join('');
            
            // Agregar eventos a los resultados
            searchResults.querySelectorAll('.search-result-item').forEach(item => {
                item.addEventListener('click', () => {
                    this.switchTab(item.dataset.tab);
                    searchResults.style.display = 'none';
                    document.getElementById('tab-search').blur();
                });
            });
        }
        
        searchResults.style.display = 'block';
    }
    
    /**
     * Calcula puntuación de búsqueda
     */
    calculateSearchScore(query, tabConfig) {
        const label = tabConfig.label.toLowerCase();
        const description = tabConfig.description.toLowerCase();
        
        let score = 0;
        
        // Coincidencia exacta en label
        if (label.includes(query)) {
            score += 100;
        }
        
        // Coincidencia en descripción
        if (description.includes(query)) {
            score += 50;
        }
        
        // Coincidencia por palabras
        const queryWords = query.split(' ');
        queryWords.forEach(word => {
            if (label.includes(word)) score += 30;
            if (description.includes(word)) score += 15;
        });
        
        return score;
    }
    
    /**
     * Enfoca el campo de búsqueda
     */
    focusSearch() {
        const searchInput = document.getElementById('tab-search');
        if (searchInput) {
            searchInput.focus();
            searchInput.select();
        }
    }
    
    /**
     * Cambia al tab especificado
     */
    switchTab(tabName, addToHistory = true) {
        if (this.currentTab === tabName) return;
        
        console.log(`🔄 Cambiando a tab: ${tabName}`);
        
        // Agregar al historial
        if (addToHistory) {
            this.addToHistory(this.currentTab);
        }
        
        // Actualizar estado
        const previousTab = this.currentTab;
        this.currentTab = tabName;
        
        // Mostrar indicador de carga
        this.showTabLoading(tabName);
        
        // Actualizar UI
        this.updateTabButtons(tabName);
        this.updateNavigationState();
        this.updateBreadcrumbs(tabName);
        
        // Notificar al tab manager
        if (window.tabManager) {
            window.tabManager.switchTab(tabName).then(() => {
                this.hideTabLoading(tabName);
                this.setTabIndicator(tabName, 'online');
            }).catch(() => {
                this.hideTabLoading(tabName);
                this.setTabIndicator(tabName, 'error');
            });
        } else {
            // Simular carga
            setTimeout(() => {
                this.hideTabLoading(tabName);
                this.setTabIndicator(tabName, 'online');
            }, 1000);
        }
        
        // Actualizar URL
        this.updateUrl(tabName);
        
        // Persistir estado
        if (this.config.layout.persistState) {
            this.savePersistedState();
        }
        
        // Emitir evento personalizado
        this.dispatchTabChangeEvent(tabName, previousTab);
    }
    
    /**
     * Actualiza los botones de tabs
     */
    updateTabButtons(activeTab) {
        document.querySelectorAll('.nav-tab').forEach(button => {
            button.classList.remove('active');
            if (button.dataset.tab === activeTab) {
                button.classList.add('active');
            }
        });
    }
    
    /**
     * Actualiza breadcrumbs
     */
    updateBreadcrumbs(tabName) {
        const breadcrumbs = document.getElementById('breadcrumbs');
        if (breadcrumbs && this.config.tabs[tabName]) {
            const currentSpan = breadcrumbs.querySelector('.breadcrumb-item.current');
            if (currentSpan) {
                currentSpan.textContent = this.config.tabs[tabName].label;
            }
        }
    }
    
    /**
     * Actualiza el estado de navegación
     */
    updateNavigationState() {
        // Información del tab actual
        const tabName = document.getElementById('current-tab-name');
        const tabDescription = document.getElementById('current-tab-description');
        
        if (tabName && this.config.tabs[this.currentTab]) {
            tabName.textContent = this.config.tabs[this.currentTab].label;
        }
        
        if (tabDescription && this.config.tabs[this.currentTab]) {
            tabDescription.textContent = this.config.tabs[this.currentTab].description;
        }
        
        // Botones de navegación
        const backBtn = document.getElementById('nav-back');
        const forwardBtn = document.getElementById('nav-forward');
        
        if (backBtn) {
            backBtn.disabled = this.tabHistory.length === 0;
        }
        
        if (forwardBtn) {
            forwardBtn.disabled = this.forwardHistory.length === 0;
        }
        
        // Botón de pin
        const pinBtn = document.getElementById('pin-current-tab');
        if (pinBtn) {
            const icon = pinBtn.querySelector('i');
            if (this.pinnedTabs.has(this.currentTab)) {
                icon.className = 'fas fa-thumbtack';
                pinBtn.title = 'Desfijar tab';
            } else {
                icon.className = 'fas fa-thumbtack';
                pinBtn.title = 'Fijar tab';
            }
        }
    }
    
    /**
     * Agrega tab al historial
     */
    addToHistory(tabName) {
        if (tabName && tabName !== this.currentTab) {
            this.tabHistory.push(tabName);
            if (this.tabHistory.length > this.maxHistory) {
                this.tabHistory.shift();
            }
            // Limpiar historial hacia adelante
            this.forwardHistory = [];
        }
    }
    
    /**
     * Navega hacia atrás
     */
    goBack() {
        if (this.tabHistory.length > 0) {
            const previousTab = this.tabHistory.pop();
            this.forwardHistory.push(this.currentTab);
            this.switchTab(previousTab, false);
        }
    }
    
    /**
     * Navega hacia adelante
     */
    goForward() {
        if (this.forwardHistory.length > 0) {
            const nextTab = this.forwardHistory.pop();
            this.tabHistory.push(this.currentTab);
            this.switchTab(nextTab, false);
        }
    }
    
    /**
     * Refresca el tab actual
     */
    refreshCurrentTab() {
        console.log(`🔄 Refrescando tab: ${this.currentTab}`);
        
        this.showTabLoading(this.currentTab, 'Refrescando...');
        
        // Notificar al tab manager
        if (window.tabManager) {
            window.tabManager.refreshCurrentTab().then(() => {
                this.hideTabLoading(this.currentTab);
                if (window.notificationSystem) {
                    window.notificationSystem.success('Contenido actualizado', { duration: 2000 });
                }
            }).catch(() => {
                this.hideTabLoading(this.currentTab);
                if (window.notificationSystem) {
                    window.notificationSystem.error('Error al actualizar');
                }
            });
        } else {
            // Simular refresh
            setTimeout(() => {
                this.hideTabLoading(this.currentTab);
                if (window.notificationSystem) {
                    window.notificationSystem.success('Contenido actualizado');
                }
            }, 1500);
        }
    }
    
    /**
     * Toggle pin del tab
     */
    togglePinTab(tabName) {
        if (this.pinnedTabs.has(tabName)) {
            this.pinnedTabs.delete(tabName);
            const tab = document.querySelector(`[data-tab="${tabName}"]`);
            if (tab) tab.classList.remove('pinned');
        } else {
            this.pinnedTabs.add(tabName);
            const tab = document.querySelector(`[data-tab="${tabName}"]`);
            if (tab) tab.classList.add('pinned');
        }
        
        this.updateNavigationState();
        this.savePersistedState();
        
        if (window.notificationSystem) {
            const action = this.pinnedTabs.has(tabName) ? 'fijado' : 'desfijado';
            window.notificationSystem.info(`Tab ${action}`, { duration: 1500 });
        }
    }
    
    /**
     * Duplica tab en nueva ventana
     */
    duplicateTab(tabName) {
        const currentUrl = new URL(window.location);
        currentUrl.searchParams.set('tab', tabName);
        window.open(currentUrl.toString(), '_blank');
        
        if (window.notificationSystem) {
            window.notificationSystem.info('Tab abierto en nueva ventana');
        }
    }
    
    /**
     * Muestra carga en tab
     */
    showTabLoading(tabName, message = 'Cargando...') {
        this.loadingTabs.add(tabName);
        const tab = document.querySelector(`[data-tab="${tabName}"]`);
        if (tab) {
            tab.classList.add('loading');
            this.setTabIndicator(tabName, 'loading');
        }
        
        const loadingIndicator = document.getElementById('nav-loading');
        if (loadingIndicator) {
            loadingIndicator.classList.add('active');
            const span = loadingIndicator.querySelector('span');
            if (span) span.textContent = message;
        }
    }
    
    /**
     * Oculta carga en tab
     */
    hideTabLoading(tabName) {
        this.loadingTabs.delete(tabName);
        const tab = document.querySelector(`[data-tab="${tabName}"]`);
        if (tab) {
            tab.classList.remove('loading');
        }
        
        if (this.loadingTabs.size === 0) {
            const loadingIndicator = document.getElementById('nav-loading');
            if (loadingIndicator) {
                loadingIndicator.classList.remove('active');
            }
        }
    }
    
    /**
     * Establece indicador de estado del tab
     */
    setTabIndicator(tabName, status) {
        const indicator = document.getElementById(`indicator-${tabName}`);
        if (indicator) {
            indicator.className = `nav-tab-indicator ${status}`;
        }
    }
    
    /**
     * Scroll de tabs
     */
    scrollTabs(direction) {
        const tabsContainer = document.querySelector('.nav-tabs');
        if (tabsContainer) {
            const scrollAmount = 200;
            if (direction === 'left') {
                tabsContainer.scrollLeft -= scrollAmount;
            } else {
                tabsContainer.scrollLeft += scrollAmount;
            }
        }
    }
    
    /**
     * Actualiza URL
     */
    updateUrl(tabName) {
        const url = new URL(window.location);
        url.searchParams.set('tab', tabName);
        window.history.pushState({ tab: tabName }, '', url);
    }
    
    /**
     * Carga estado persistido
     */
    loadPersistedState() {
        if (this.config.layout.persistState) {
            try {
                const saved = localStorage.getItem('navState');
                if (saved) {
                    const state = JSON.parse(saved);
                    this.pinnedTabs = new Set(state.pinnedTabs || []);
                    
                    // Restaurar tabs fijados
                    this.pinnedTabs.forEach(tabName => {
                        const tab = document.querySelector(`[data-tab="${tabName}"]`);
                        if (tab) tab.classList.add('pinned');
                    });
                }
            } catch (error) {
                console.warn('Error cargando estado de navegación:', error);
            }
        }
    }
    
    /**
     * Guarda estado persistido
     */
    savePersistedState() {
        if (this.config.layout.persistState) {
            try {
                const state = {
                    currentTab: this.currentTab,
                    pinnedTabs: Array.from(this.pinnedTabs)
                };
                localStorage.setItem('navState', JSON.stringify(state));
            } catch (error) {
                console.warn('Error guardando estado de navegación:', error);
            }
        }
    }
    
    /**
     * Inicia actualizaciones de estado
     */
    startStatusUpdates() {
        // Simular actualizaciones de estado cada 30 segundos
        setInterval(() => {
            Object.keys(this.config.tabs).forEach(tabName => {
                if (!this.loadingTabs.has(tabName)) {
                    this.setTabIndicator(tabName, 'online');
                }
            });
        }, 30000);
    }
    
    /**
     * Emite evento de cambio de tab
     */
    dispatchTabChangeEvent(newTab, oldTab) {
        const event = new CustomEvent('tabChanged', {
            detail: { newTab, oldTab, timestamp: Date.now() }
        });
        document.dispatchEvent(event);
    }
    
    /**
     * Muestra configuración de navegación
     */
    showNavSettings() {
        if (window.notificationSystem) {
            window.notificationSystem.info('Configuración de navegación - Próximamente');
        }
    }
    
    /**
     * Muestra menú contextual del tab
     */
    showTabContextMenu(event, tabName) {
        // Implementar menú contextual
        console.log('Context menu para tab:', tabName);
    }
}

// Inicializar navigation manager cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    window.navigationManager = new NavigationManagerV2();
});
</script>