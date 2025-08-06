<?php
/**
 * ==========================================================================
 * NAVIGATION MODULAR - Kavia Hoteles Panel de Administración
 * Sistema de navegación por tabs reutilizable
 * ==========================================================================
 */

// Configuración de navegación
$navConfig = [
    'tabs' => [
        'hotels' => [
            'label' => 'Hoteles',
            'icon' => 'fas fa-hotel',
            'shortcut' => '1',
            'description' => 'Gestión de hoteles y propiedades'
        ],
        'apis' => [
            'label' => 'APIs',
            'icon' => 'fas fa-plug',
            'shortcut' => '2',
            'description' => 'Configuración de APIs externas'
        ],
        'extraction' => [
            'label' => 'Extractor',
            'icon' => 'fas fa-download',
            'shortcut' => '3',
            'description' => 'Extracción de reseñas y datos'
        ],
        'ia' => [
            'label' => 'Proveedores IA',
            'icon' => 'fas fa-robot',
            'shortcut' => '4',
            'description' => 'Configuración de servicios de IA'
        ],
        'prompts' => [
            'label' => 'Prompts',
            'icon' => 'fas fa-file-alt',
            'shortcut' => '5',
            'description' => 'Gestión de prompts de IA'
        ],
        'logs' => [
            'label' => 'Logs',
            'icon' => 'fas fa-chart-line',
            'shortcut' => '6',
            'description' => 'Registros y estadísticas del sistema'
        ],
        'tools' => [
            'label' => 'Herramientas',
            'icon' => 'fas fa-tools',
            'shortcut' => '7',
            'description' => 'Herramientas de mantenimiento'
        ]
    ],
    'showShortcuts' => true,
    'showDescriptions' => true,
    'enableAnimations' => true,
    'persistState' => true
];

// Obtener tab activo desde URL o sesión
$activeTab = $_GET['tab'] ?? $_SESSION['active_tab'] ?? 'hotels';
?>

<!-- Sistema de Navegación -->
<div class="navigation-container">
    <div class="tabs" id="main-tabs">
        <?php foreach ($navConfig['tabs'] as $tabId => $tabConfig): ?>
            <button class="tab-button <?php echo $activeTab === $tabId ? 'active' : ''; ?>" 
                    data-tab="<?php echo $tabId; ?>"
                    data-shortcut="<?php echo $tabConfig['shortcut']; ?>"
                    title="<?php echo htmlspecialchars($tabConfig['description']); ?>">
                <i class="<?php echo $tabConfig['icon']; ?>"></i>
                <span class="tab-label"><?php echo htmlspecialchars($tabConfig['label']); ?></span>
                <?php if ($navConfig['showShortcuts']): ?>
                    <span class="tab-shortcut">Ctrl+<?php echo $tabConfig['shortcut']; ?></span>
                <?php endif; ?>
            </button>
        <?php endforeach; ?>
    </div>
    
    <!-- Navegación secundaria -->
    <div class="secondary-nav">
        <div class="nav-actions">
            <button class="nav-action-btn" id="refresh-current" title="Actualizar contenido actual">
                <i class="fas fa-sync-alt"></i>
            </button>
            <button class="nav-action-btn" id="go-back" title="Volver atrás" disabled>
                <i class="fas fa-arrow-left"></i>
            </button>
            <button class="nav-action-btn" id="go-forward" title="Avanzar" disabled>
                <i class="fas fa-arrow-right"></i>
            </button>
        </div>
        
        <div class="nav-info">
            <span class="current-tab-info" id="current-tab-info">
                <?php echo htmlspecialchars($navConfig['tabs'][$activeTab]['label']); ?>
            </span>
        </div>
    </div>
</div>

<!-- Estilos específicos de navegación -->
<style>
.navigation-container {
    background: white;
    border-bottom: 1px solid var(--border-color);
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    position: sticky;
    top: 0;
    z-index: 90;
}

.tabs {
    display: flex;
    gap: 0;
    max-width: 1400px;
    margin: 0 auto;
    overflow-x: auto;
    scrollbar-width: thin;
    scrollbar-color: var(--border-color) transparent;
}

.tabs::-webkit-scrollbar {
    height: 4px;
}

.tabs::-webkit-scrollbar-track {
    background: transparent;
}

.tabs::-webkit-scrollbar-thumb {
    background: var(--border-color);
    border-radius: 2px;
}

.tab-button {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 1rem 1.5rem;
    background: transparent;
    border: none;
    border-bottom: 3px solid transparent;
    color: var(--text-muted);
    font-size: 0.9rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
    white-space: nowrap;
    position: relative;
    min-width: 120px;
    justify-content: center;
}

.tab-button:hover {
    background: var(--hover-bg);
    color: var(--text-primary);
}

.tab-button.active {
    color: var(--primary);
    border-bottom-color: var(--primary);
    background: var(--primary-bg);
}

.tab-button i {
    font-size: 1rem;
}

.tab-label {
    font-weight: 500;
}

.tab-shortcut {
    font-size: 0.7rem;
    opacity: 0.6;
    margin-left: 0.25rem;
}

.secondary-nav {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.5rem 2rem;
    background: var(--bg-secondary);
    border-top: 1px solid var(--border-color);
    max-width: 1400px;
    margin: 0 auto;
}

.nav-actions {
    display: flex;
    gap: 0.5rem;
}

.nav-action-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    background: white;
    border: 1px solid var(--border-color);
    border-radius: 0.25rem;
    color: var(--text-muted);
    cursor: pointer;
    transition: all 0.2s ease;
    font-size: 0.8rem;
}

.nav-action-btn:hover:not(:disabled) {
    background: var(--primary);
    color: white;
    border-color: var(--primary);
}

.nav-action-btn:disabled {
    opacity: 0.4;
    cursor: not-allowed;
}

.nav-info {
    font-size: 0.85rem;
    color: var(--text-muted);
}

.current-tab-info {
    font-weight: 500;
    color: var(--text-primary);
}

/* Animaciones */
.tab-button {
    transform: translateY(0);
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}

.tab-button:hover {
    transform: translateY(-1px);
}

.tab-button.active {
    transform: translateY(-2px);
}

/* Responsive */
@media (max-width: 768px) {
    .tabs {
        padding: 0 1rem;
    }
    
    .tab-button {
        min-width: 100px;
        padding: 0.75rem 1rem;
        font-size: 0.8rem;
    }
    
    .tab-shortcut {
        display: none;
    }
    
    .secondary-nav {
        padding: 0.5rem 1rem;
    }
}

/* Estados de carga */
.tab-button.loading {
    position: relative;
    pointer-events: none;
}

.tab-button.loading::after {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 16px;
    height: 16px;
    margin: -8px 0 0 -8px;
    border: 2px solid var(--primary);
    border-top-color: transparent;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}

/* Indicador de contenido nuevo */
.tab-button.has-updates::before {
    content: '';
    position: absolute;
    top: 0.5rem;
    right: 0.5rem;
    width: 8px;
    height: 8px;
    background: var(--success);
    border-radius: 50%;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0%, 100% {
        opacity: 1;
        transform: scale(1);
    }
    50% {
        opacity: 0.5;
        transform: scale(1.2);
    }
}
</style>

<!-- Script de navegación -->
<script>
/**
 * Navigation Manager - Gestor de navegación modular
 */
class NavigationManager {
    constructor() {
        this.currentTab = 'hotels';
        this.tabHistory = [];
        this.maxHistory = 10;
        this.init();
    }
    
    init() {
        this.bindEvents();
        this.updateNavigationState();
        this.setupKeyboardShortcuts();
        
        if (AdminConfig?.debug?.enabled) {
            console.log('🧭 Navigation Manager inicializado');
        }
    }
    
    /**
     * Vincula eventos de navegación
     */
    bindEvents() {
        // Eventos de tabs
        document.querySelectorAll('.tab-button').forEach(button => {
            button.addEventListener('click', (e) => {
                const tabName = e.currentTarget.dataset.tab;
                this.switchTab(tabName);
            });
        });
        
        // Botones de navegación
        document.getElementById('refresh-current')?.addEventListener('click', () => {
            this.refreshCurrentTab();
        });
        
        document.getElementById('go-back')?.addEventListener('click', () => {
            this.goBack();
        });
        
        document.getElementById('go-forward')?.addEventListener('click', () => {
            this.goForward();
        });
    }
    
    /**
     * Configura atajos de teclado
     */
    setupKeyboardShortcuts() {
        document.addEventListener('keydown', (e) => {
            // Solo si no estamos en un input
            if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') {
                return;
            }
            
            if (e.ctrlKey || e.metaKey) {
                const tabButtons = document.querySelectorAll('.tab-button');
                
                tabButtons.forEach(button => {
                    const shortcut = button.dataset.shortcut;
                    if (shortcut && e.key === shortcut) {
                        e.preventDefault();
                        this.switchTab(button.dataset.tab);
                    }
                });
            }
        });
    }
    
    /**
     * Cambia al tab especificado
     */
    switchTab(tabName) {
        if (this.currentTab === tabName) return;
        
        // Agregar a historial
        this.addToHistory(this.currentTab);
        
        // Actualizar estado
        this.currentTab = tabName;
        
        // Actualizar UI
        this.updateTabButtons(tabName);
        this.updateNavigationState();
        
        // Notificar al tab manager
        if (window.tabManager) {
            window.tabManager.switchTab(tabName);
        }
        
        // Actualizar URL
        this.updateUrl(tabName);
        
        if (AdminConfig?.debug?.enabled) {
            console.log(`🔄 Cambiando a tab: ${tabName}`);
        }
    }
    
    /**
     * Actualiza los botones de tabs
     */
    updateTabButtons(activeTab) {
        document.querySelectorAll('.tab-button').forEach(button => {
            button.classList.remove('active');
            if (button.dataset.tab === activeTab) {
                button.classList.add('active');
            }
        });
    }
    
    /**
     * Actualiza el estado de navegación
     */
    updateNavigationState() {
        // Actualizar información del tab actual
        const currentTabInfo = document.getElementById('current-tab-info');
        if (currentTabInfo) {
            const activeButton = document.querySelector('.tab-button.active');
            if (activeButton) {
                const label = activeButton.querySelector('.tab-label').textContent;
                currentTabInfo.textContent = label;
            }
        }
        
        // Actualizar botones de navegación
        const goBackBtn = document.getElementById('go-back');
        const goForwardBtn = document.getElementById('go-forward');
        
        if (goBackBtn) {
            goBackBtn.disabled = this.tabHistory.length === 0;
        }
        
        if (goForwardBtn) {
            goForwardBtn.disabled = true; // Por ahora siempre deshabilitado
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
        }
    }
    
    /**
     * Navega hacia atrás
     */
    goBack() {
        if (this.tabHistory.length > 0) {
            const previousTab = this.tabHistory.pop();
            this.switchTab(previousTab);
        }
    }
    
    /**
     * Navega hacia adelante
     */
    goForward() {
        // Implementar cuando sea necesario
        showInfo('Navegación hacia adelante en desarrollo');
    }
    
    /**
     * Actualiza la URL
     */
    updateUrl(tabName) {
        const url = new URL(window.location);
        url.searchParams.set('tab', tabName);
        window.history.pushState({ tab: tabName }, '', url);
    }
    
    /**
     * Refresca el tab actual
     */
    refreshCurrentTab() {
        const activeButton = document.querySelector('.tab-button.active');
        if (activeButton) {
            activeButton.classList.add('loading');
            
            // Simular carga
            setTimeout(() => {
                activeButton.classList.remove('loading');
                
                // Notificar al tab manager
                if (window.tabManager) {
                    window.tabManager.refreshCurrentTab();
                }
                
                showSuccess('Contenido actualizado');
            }, 1000);
        }
    }
    
    /**
     * Marca un tab como con actualizaciones
     */
    markTabAsUpdated(tabName) {
        const button = document.querySelector(`[data-tab="${tabName}"]`);
        if (button) {
            button.classList.add('has-updates');
        }
    }
    
    /**
     * Limpia la marca de actualizaciones
     */
    clearTabUpdates(tabName) {
        const button = document.querySelector(`[data-tab="${tabName}"]`);
        if (button) {
            button.classList.remove('has-updates');
        }
    }
}

// Inicializar navigation manager cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    if (typeof NavigationManager !== 'undefined') {
        window.navigationManager = new NavigationManager();
    }
});
</script>