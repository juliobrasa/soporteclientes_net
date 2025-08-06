<?php
/**
 * ==========================================================================
 * NAVIGATION MODULAR - Kavia Hoteles Panel de Administración
 * Sistema de tabs de navegación modular y reutilizable
 * ==========================================================================
 */

// Configuración de navegación
$navigationConfig = [
    'tabs' => [
        'hotels' => [
            'label' => 'Hoteles',
            'icon' => 'fas fa-hotel',
            'shortcut' => '1',
            'description' => 'Gestión de hoteles y sus configuraciones',
            'badge' => null,
            'enabled' => true
        ],
        'apis' => [
            'label' => 'APIs',
            'icon' => 'fas fa-plug',
            'shortcut' => '2',
            'description' => 'Configuración de APIs y proveedores',
            'badge' => null,
            'enabled' => true
        ],
        'extraction' => [
            'label' => 'Extractor',
            'icon' => 'fas fa-download',
            'shortcut' => '3',
            'description' => 'Extracción de reseñas y datos',
            'badge' => null,
            'enabled' => true
        ],
        'ia' => [
            'label' => 'Proveedores IA',
            'icon' => 'fas fa-robot',
            'shortcut' => '4',
            'description' => 'Configuración de proveedores de IA',
            'badge' => null,
            'enabled' => true
        ],
        'prompts' => [
            'label' => 'Prompts',
            'icon' => 'fas fa-file-alt',
            'shortcut' => '5',
            'description' => 'Gestión de prompts y plantillas',
            'badge' => null,
            'enabled' => true
        ],
        'logs' => [
            'label' => 'Logs',
            'icon' => 'fas fa-chart-line',
            'shortcut' => '6',
            'description' => 'Registros y estadísticas del sistema',
            'badge' => null,
            'enabled' => true
        ],
        'tools' => [
            'label' => 'Herramientas',
            'icon' => 'fas fa-tools',
            'shortcut' => '7',
            'description' => 'Herramientas de mantenimiento',
            'badge' => null,
            'enabled' => true
        ]
    ],
    'defaultTab' => 'hotels',
    'showShortcuts' => true,
    'showDescriptions' => false,
    'enableHistory' => true,
    'enableAnimations' => true
];

// Obtener tab activo desde URL o sesión
$activeTab = $_GET['tab'] ?? $_SESSION['active_tab'] ?? $navigationConfig['defaultTab'];

// Validar que el tab existe y está habilitado
if (!isset($navigationConfig['tabs'][$activeTab]) || !$navigationConfig['tabs'][$activeTab]['enabled']) {
    $activeTab = $navigationConfig['defaultTab'];
}

// Guardar tab activo en sesión
$_SESSION['active_tab'] = $activeTab;
?>

<!-- Sistema de Navegación -->
<div class="navigation-container">
    <div class="tabs" id="main-tabs">
        <?php foreach ($navigationConfig['tabs'] as $tabKey => $tabConfig): ?>
            <?php if ($tabConfig['enabled']): ?>
                <button class="tab-button <?php echo $activeTab === $tabKey ? 'active' : ''; ?>" 
                        data-tab="<?php echo htmlspecialchars($tabKey); ?>"
                        data-shortcut="<?php echo htmlspecialchars($tabConfig['shortcut']); ?>"
                        title="<?php echo htmlspecialchars($tabConfig['description']); ?>">
                    
                    <div class="tab-icon">
                        <i class="<?php echo htmlspecialchars($tabConfig['icon']); ?>"></i>
                    </div>
                    
                    <div class="tab-content">
                        <span class="tab-label"><?php echo htmlspecialchars($tabConfig['label']); ?></span>
                        
                        <?php if ($navigationConfig['showShortcuts']): ?>
                            <span class="tab-shortcut">Ctrl+<?php echo htmlspecialchars($tabConfig['shortcut']); ?></span>
                        <?php endif; ?>
                        
                        <?php if ($tabConfig['badge']): ?>
                            <span class="tab-badge"><?php echo htmlspecialchars($tabConfig['badge']); ?></span>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($tabConfig['badge']): ?>
                        <div class="tab-badge-container">
                            <span class="badge"><?php echo htmlspecialchars($tabConfig['badge']); ?></span>
                        </div>
                    <?php endif; ?>
                </button>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>

    <!-- Navegación Secundaria -->
    <div class="secondary-navigation" id="secondary-nav">
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
                <?php echo htmlspecialchars($navigationConfig['tabs'][$activeTab]['label']); ?>
            </span>
        </div>
    </div>
</div>

<!-- Breadcrumb de Navegación -->
<div class="breadcrumb-container" id="breadcrumb-container">
    <nav class="breadcrumb" aria-label="Breadcrumb">
        <ol class="breadcrumb-list">
            <li class="breadcrumb-item">
                <a href="#" class="breadcrumb-link" data-tab="hotels">
                    <i class="fas fa-home"></i>
                    <span>Inicio</span>
                </a>
            </li>
            <li class="breadcrumb-item active" aria-current="page">
                <span id="current-breadcrumb"><?php echo htmlspecialchars($navigationConfig['tabs'][$activeTab]['label']); ?></span>
            </li>
        </ol>
    </nav>
</div>

<!-- Scripts específicos de navegación -->
<script>
/**
 * Gestor de Navegación Modular
 */
class NavigationManager {
    constructor() {
        this.config = <?php echo json_encode($navigationConfig); ?>;
        this.currentTab = '<?php echo $activeTab; ?>';
        this.history = [];
        this.historyIndex = -1;
        this.maxHistory = 10;
        
        this.init();
    }

    init() {
        this.bindEvents();
        this.initializeShortcuts();
        this.updateNavigationState();
        this.setupBreadcrumb();
        
        if (AdminConfig?.debug?.enabled) {
            console.log('🧭 Navigation Manager inicializado');
        }
    }

    bindEvents() {
        // Eventos de tabs
        const tabButtons = document.querySelectorAll('.tab-button');
        tabButtons.forEach(button => {
            button.addEventListener('click', (e) => {
                e.preventDefault();
                const tabName = button.getAttribute('data-tab');
                this.switchTab(tabName);
            });
        });

        // Eventos de navegación secundaria
        const refreshBtn = document.getElementById('refresh-current');
        const backBtn = document.getElementById('go-back');
        const forwardBtn = document.getElementById('go-forward');

        if (refreshBtn) {
            refreshBtn.addEventListener('click', () => {
                this.refreshCurrentTab();
            });
        }

        if (backBtn) {
            backBtn.addEventListener('click', () => {
                this.goBack();
            });
        }

        if (forwardBtn) {
            forwardBtn.addEventListener('click', () => {
                this.goForward();
            });
        }

        // Eventos de breadcrumb
        const breadcrumbLinks = document.querySelectorAll('.breadcrumb-link');
        breadcrumbLinks.forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const tabName = link.getAttribute('data-tab');
                if (tabName) {
                    this.switchTab(tabName);
                }
            });
        });
    }

    initializeShortcuts() {
        document.addEventListener('keydown', (e) => {
            // Solo activar shortcuts si no estamos en un input
            if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') {
                return;
            }

            if (e.ctrlKey || e.metaKey) {
                const key = e.key;
                const tabConfig = Object.values(this.config.tabs).find(tab => tab.shortcut === key);
                
                if (tabConfig) {
                    e.preventDefault();
                    const tabName = Object.keys(this.config.tabs).find(key => this.config.tabs[key] === tabConfig);
                    this.switchTab(tabName);
                }
            }
        });
    }

    switchTab(tabName) {
        if (!this.config.tabs[tabName] || !this.config.tabs[tabName].enabled) {
            console.warn(`Tab "${tabName}" no está disponible`);
            return;
        }

        // Agregar a historial
        if (this.config.enableHistory) {
            this.addToHistory(tabName);
        }

        // Actualizar estado
        this.currentTab = tabName;
        this.updateTabButtons(tabName);
        this.updateNavigationState();
        this.updateBreadcrumb(tabName);

        // Notificar al tab manager
        if (typeof tabManager !== 'undefined') {
            tabManager.switchTab(tabName);
        }

        // Actualizar URL
        this.updateUrl(tabName);

        if (AdminConfig?.debug?.enabled) {
            console.log(`🧭 Cambiando a tab: ${tabName}`);
        }
    }

    updateTabButtons(activeTab) {
        const tabButtons = document.querySelectorAll('.tab-button');
        tabButtons.forEach(button => {
            const tabName = button.getAttribute('data-tab');
            if (tabName === activeTab) {
                button.classList.add('active');
            } else {
                button.classList.remove('active');
            }
        });
    }

    updateNavigationState() {
        const backBtn = document.getElementById('go-back');
        const forwardBtn = document.getElementById('go-forward');
        const currentTabInfo = document.getElementById('current-tab-info');

        // Actualizar botones de navegación
        if (backBtn) {
            backBtn.disabled = this.historyIndex <= 0;
        }

        if (forwardBtn) {
            forwardBtn.disabled = this.historyIndex >= this.history.length - 1;
        }

        // Actualizar información del tab actual
        if (currentTabInfo) {
            const currentTabConfig = this.config.tabs[this.currentTab];
            currentTabInfo.textContent = currentTabConfig.label;
        }
    }

    setupBreadcrumb() {
        this.updateBreadcrumb(this.currentTab);
    }

    updateBreadcrumb(tabName) {
        const currentBreadcrumb = document.getElementById('current-breadcrumb');
        if (currentBreadcrumb) {
            const tabConfig = this.config.tabs[tabName];
            currentBreadcrumb.textContent = tabConfig.label;
        }
    }

    addToHistory(tabName) {
        // Remover entradas futuras si navegamos desde el medio
        this.history = this.history.slice(0, this.historyIndex + 1);
        
        // Agregar nueva entrada
        this.history.push(tabName);
        this.historyIndex++;

        // Mantener tamaño máximo del historial
        if (this.history.length > this.maxHistory) {
            this.history.shift();
            this.historyIndex--;
        }
    }

    goBack() {
        if (this.historyIndex > 0) {
            this.historyIndex--;
            const tabName = this.history[this.historyIndex];
            this.switchTab(tabName, false); // No agregar al historial
        }
    }

    goForward() {
        if (this.historyIndex < this.history.length - 1) {
            this.historyIndex++;
            const tabName = this.history[this.historyIndex];
            this.switchTab(tabName, false); // No agregar al historial
        }
    }

    refreshCurrentTab() {
        if (typeof tabManager !== 'undefined') {
            tabManager.refreshCurrentTab();
        }
        
        // Mostrar indicador de carga
        this.showRefreshIndicator();
    }

    showRefreshIndicator() {
        const refreshBtn = document.getElementById('refresh-current');
        if (refreshBtn) {
            const icon = refreshBtn.querySelector('i');
            icon.classList.add('fa-spin');
            
            setTimeout(() => {
                icon.classList.remove('fa-spin');
            }, 1000);
        }
    }

    updateUrl(tabName) {
        const url = new URL(window.location);
        url.searchParams.set('tab', tabName);
        window.history.pushState({ tab: tabName }, '', url);
    }

    getCurrentTab() {
        return this.currentTab;
    }

    getTabConfig(tabName) {
        return this.config.tabs[tabName];
    }

    isTabEnabled(tabName) {
        return this.config.tabs[tabName]?.enabled || false;
    }

    updateTabBadge(tabName, badge) {
        const tabButton = document.querySelector(`[data-tab="${tabName}"]`);
        if (tabButton) {
            let badgeContainer = tabButton.querySelector('.tab-badge-container');
            
            if (!badgeContainer) {
                badgeContainer = document.createElement('div');
                badgeContainer.className = 'tab-badge-container';
                tabButton.appendChild(badgeContainer);
            }

            if (badge) {
                badgeContainer.innerHTML = `<span class="badge">${badge}</span>`;
                badgeContainer.style.display = 'block';
            } else {
                badgeContainer.style.display = 'none';
            }
        }
    }

    showTabLoading(tabName) {
        const tabButton = document.querySelector(`[data-tab="${tabName}"]`);
        if (tabButton) {
            const icon = tabButton.querySelector('.tab-icon i');
            icon.classList.add('fa-spin');
        }
    }

    hideTabLoading(tabName) {
        const tabButton = document.querySelector(`[data-tab="${tabName}"]`);
        if (tabButton) {
            const icon = tabButton.querySelector('.tab-icon i');
            icon.classList.remove('fa-spin');
        }
    }

    // Métodos de utilidad
    getNavigationStats() {
        return {
            currentTab: this.currentTab,
            historyLength: this.history.length,
            historyIndex: this.historyIndex,
            enabledTabs: Object.keys(this.config.tabs).filter(tab => this.config.tabs[tab].enabled)
        };
    }

    clearHistory() {
        this.history = [];
        this.historyIndex = -1;
        this.updateNavigationState();
    }
}

// Inicializar el navigation manager
let navigationManager;
document.addEventListener('DOMContentLoaded', () => {
    navigationManager = new NavigationManager();
});
</script>