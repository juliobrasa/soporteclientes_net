/**
 * ==========================================================================
 * TAB MANAGER - Kavia Hoteles Panel de Administración
 * Gestor centralizado para navegación por tabs
 * ==========================================================================
 */

class TabManager {
    constructor() {
        this.currentTab = AdminConfig?.tabs?.default || 'hotels';
        this.tabs = new Map();
        this.tabHistory = [];
        this.maxHistory = 10;
        
        // Configuración
        this.config = {
            animationDuration: AdminConfig?.ui?.animationDuration || 300,
            persistState: true,
            loadOnDemand: true,
            preloadNext: false
        };
        
        this.init();
    }
    
    /**
     * Inicializa el gestor de tabs
     */
    init() {
        this.bindEvents();
        this.setupTabs();
        this.loadFromUrl();
        
        if (AdminConfig?.debug?.enabled) {
            console.log('📋 Tab Manager inicializado');
        }
    }
    
    /**
     * Configura los tabs disponibles
     */
    setupTabs() {
        const availableTabs = AdminConfig?.tabs?.available || ['hotels'];
        
        availableTabs.forEach(tabName => {
            // Buscar elemento del tab (puede ser -tab o -direct-system)
            let element = document.getElementById(`${tabName}-tab`);
            if (!element) {
                element = document.getElementById(`${tabName}-direct-system`);
            }
            
            // Para el caso especial de 'ia' que usa 'providers' como elemento
            if (tabName === 'ia' && !element) {
                element = document.getElementById('providers-direct-system');
            }
            
            this.registerTab(tabName, {
                label: AdminConfig?.tabs?.labels?.[tabName] || tabName,
                icon: AdminConfig?.tabs?.icons?.[tabName] || 'fas fa-circle',
                loadFunction: this.getLoadFunction(tabName),
                element: element,
                button: document.querySelector(`[data-tab="${tabName}"]`)
            });
            
            if (AdminConfig?.debug?.enabled && element) {
                console.log(`✅ Tab registrado: ${tabName} -> ${element.id}`);
            } else if (AdminConfig?.debug?.enabled) {
                console.warn(`❌ No se encontró elemento para tab: ${tabName}`);
            }
        });
    }
    
    /**
     * Registra un nuevo tab
     */
    registerTab(tabName, config) {
        this.tabs.set(tabName, {
            name: tabName,
            label: config.label,
            icon: config.icon,
            element: config.element,
            button: config.button,
            loadFunction: config.loadFunction,
            isLoaded: false,
            isLoading: false,
            lastAccessed: null,
            data: null
        });
        
        if (AdminConfig?.debug?.enabled) {
            console.log(`📄 Tab registrado: ${tabName}`);
        }
    }
    
    /**
     * Vincula eventos globales
     */
    bindEvents() {
        // Eventos de teclado para navegación rápida
        document.addEventListener('keydown', (e) => {
            if (e.ctrlKey || e.metaKey) {
                switch(e.key) {
                    case '1':
                        e.preventDefault();
                        this.switchTab('hotels');
                        break;
                    case '2':
                        e.preventDefault();
                        this.switchTab('apis');
                        break;
                    case '3':
                        e.preventDefault();
                        this.switchTab('extraction');
                        break;
                    case '4':
                        e.preventDefault();
                        this.switchTab('ia');
                        break;
                    case '5':
                        e.preventDefault();
                        this.switchTab('prompts');
                        break;
                    case '6':
                        e.preventDefault();
                        this.switchTab('logs');
                        break;
                    case '7':
                        e.preventDefault();
                        this.switchTab('tools');
                        break;
                    case 'Tab':
                        e.preventDefault();
                        this.switchToNextTab();
                        break;
                    case 'ArrowLeft':
                        if (e.altKey) {
                            e.preventDefault();
                            this.goBack();
                        }
                        break;
                    case 'ArrowRight':
                        if (e.altKey) {
                            e.preventDefault();
                            this.goForward();
                        }
                        break;
                }
            }
        });
        
        // Eventos de botones de tab
        document.querySelectorAll('.tab-button').forEach(button => {
            button.addEventListener('click', (e) => {
                e.preventDefault();
                const tabName = button.getAttribute('data-tab');
                if (tabName) {
                    this.switchTab(tabName);
                }
            });
        });
        
        // Eventos del historial del navegador
        window.addEventListener('popstate', (e) => {
            if (e.state && e.state.tab) {
                this.switchTab(e.state.tab, false); // false = no actualizar historial
            }
        });
        
        // Evento de visibilidad de la página
        document.addEventListener('visibilitychange', () => {
            if (!document.hidden && this.currentTab) {
                this.refreshCurrentTab();
            }
        });
    }
    
    /**
     * Cambia al tab especificado
     */
    async switchTab(tabName, updateHistory = true) {
        const tab = this.tabs.get(tabName);
        if (!tab) {
            console.error(`Tab no encontrado: ${tabName}`);
            showError(`Tab "${tabName}" no encontrado`);
            return false;
        }
        
        // No hacer nada si ya estamos en ese tab
        if (this.currentTab === tabName && tab.isLoaded) {
            return true;
        }
        
        try {
            // Ocultar tab actual
            this.hideCurrentTab();
            
            // Actualizar estado
            this.addToHistory(this.currentTab);
            this.currentTab = tabName;
            tab.lastAccessed = Date.now();
            
            // Mostrar nuevo tab
            await this.showTab(tab);
            
            // Actualizar UI
            this.updateTabButtons();
            
            // Actualizar URL y historial
            if (updateHistory) {
                this.updateUrl(tabName);
            }
            
            // Precargar siguiente tab si está configurado
            if (this.config.preloadNext) {
                this.preloadNextTab();
            }
            
            // Emitir evento
            this.emitTabChange(tabName);
            
            if (AdminConfig?.debug?.enabled) {
                console.log(`📋 Cambiado a tab: ${tabName}`);
            }
            
            return true;
            
        } catch (error) {
            console.error(`Error cambiando a tab ${tabName}:`, error);
            showError(`Error al cambiar a ${tab.label}: ${error.message}`);
            return false;
        }
    }
    
    /**
     * Muestra un tab específico
     */
    async showTab(tab) {
        if (!tab.element) {
            throw new Error(`Elemento no encontrado para tab: ${tab.name}`);
        }
        
        // Ocultar TODOS los otros elementos de tab
        this.hideAllTabs();
        
        // Cargar contenido si es necesario
        if (!tab.isLoaded && this.config.loadOnDemand) {
            await this.loadTabContent(tab);
        }
        
        // Mostrar elemento
        tab.element.style.display = 'block';
        
        // Aplicar animación
        if (this.config.animationDuration > 0) {
            tab.element.style.opacity = '0';
            tab.element.style.transform = 'translateY(10px)';
            
            // Forzar reflow
            tab.element.offsetHeight;
            
            // Animar entrada
            tab.element.style.transition = `all ${this.config.animationDuration}ms ease`;
            tab.element.style.opacity = '1';
            tab.element.style.transform = 'translateY(0)';
        }
    }
    
    /**
     * Oculta todos los tabs
     */
    hideAllTabs() {
        // Ocultar todos los elementos -tab y -direct-system
        const tabElements = document.querySelectorAll('.tab-content, .module-direct-system');
        tabElements.forEach(element => {
            element.style.display = 'none';
        });
    }
    
    /**
     * Oculta el tab actual
     */
    hideCurrentTab() {
        if (!this.currentTab) return;
        
        const currentTab = this.tabs.get(this.currentTab);
        if (currentTab && currentTab.element) {
            if (this.config.animationDuration > 0) {
                currentTab.element.style.transition = `all ${this.config.animationDuration}ms ease`;
                currentTab.element.style.opacity = '0';
                
                setTimeout(() => {
                    currentTab.element.style.display = 'none';
                    currentTab.element.style.transform = '';
                    currentTab.element.style.transition = '';
                }, this.config.animationDuration);
            } else {
                currentTab.element.style.display = 'none';
            }
        }
    }
    
    /**
     * Carga el contenido de un tab
     */
    async loadTabContent(tab) {
        if (tab.isLoading) return;
        
        tab.isLoading = true;
        
        try {
            // Mostrar estado de carga
            this.showTabLoading(tab);
            
            // Ejecutar función de carga específica
            if (tab.loadFunction && typeof tab.loadFunction === 'function') {
                await tab.loadFunction();
            }
            
            tab.isLoaded = true;
            
        } catch (error) {
            console.error(`Error cargando tab ${tab.name}:`, error);
            this.showTabError(tab, error.message);
        } finally {
            tab.isLoading = false;
        }
    }
    
    /**
     * Muestra estado de carga para un tab
     */
    showTabLoading(tab) {
        if (!tab.element) return;
        
        tab.element.innerHTML = `
            <div class="card">
                <div class="card-body">
                    <div class="loading-state">
                        <i class="fas fa-spinner fa-spin spinner"></i>
                        <h3>Cargando ${tab.label}...</h3>
                        <p>Por favor espera mientras cargamos el contenido</p>
                    </div>
                </div>
            </div>
        `;
    }
    
    /**
     * Muestra estado de error para un tab
     */
    showTabError(tab, errorMessage) {
        if (!tab.element) return;
        
        tab.element.innerHTML = `
            <div class="card">
                <div class="card-body">
                    <div class="error-state">
                        <i class="fas fa-exclamation-triangle"></i>
                        <h3>Error al cargar ${tab.label}</h3>
                        <p>Ha ocurrido un error: ${errorMessage}</p>
                        <button class="btn btn-primary" onclick="tabManager.reloadTab('${tab.name}')">
                            <i class="fas fa-redo"></i> Reintentar
                        </button>
                    </div>
                </div>
            </div>
        `;
    }
    
    /**
     * Actualiza los botones de tab
     */
    updateTabButtons() {
        // Desactivar todos los botones
        document.querySelectorAll('.tab-button').forEach(button => {
            button.classList.remove('active');
        });
        
        // Activar botón actual
        const currentTab = this.tabs.get(this.currentTab);
        if (currentTab && currentTab.button) {
            currentTab.button.classList.add('active');
        }
    }
    
    /**
     * Obtiene la función de carga para un tab
     */
    getLoadFunction(tabName) {
        const loadFunctions = {
            hotels: () => {
                if (window.hotelsModule) {
                    return window.hotelsModule.loadHotels();
                }
            },
            apis: () => {
                if (window.loadApisDirect) {
                    return window.loadApisDirect();
                }
            },
            extraction: () => {
                if (window.loadExtractionDirect) {
                    return window.loadExtractionDirect();
                }
            },
            ia: () => {
                if (window.loadProvidersDirect) {
                    return window.loadProvidersDirect();
                }
            },
            prompts: () => {
                if (window.loadPromptsDirect) {
                    return window.loadPromptsDirect();
                }
            },
            logs: () => {
                if (window.loadLogsDirect) {
                    return window.loadLogsDirect();
                }
            },
            tools: () => {
                showInfo('Módulo de Herramientas en desarrollo');
            }
        };
        
        return loadFunctions[tabName];
    }
    
    /**
     * Actualiza la URL sin recargar
     */
    updateUrl(tabName) {
        if (!this.config.persistState) return;
        
        const newUrl = `${window.location.pathname}?tab=${tabName}`;
        history.pushState({ tab: tabName }, '', newUrl);
    }
    
    /**
     * Carga tab desde URL
     */
    loadFromUrl() {
        const urlParams = new URLSearchParams(window.location.search);
        const tabFromUrl = urlParams.get('tab');
        
        if (AdminConfig?.debug?.enabled) {
            console.log('📍 Tab desde URL:', tabFromUrl);
            console.log('📋 Tabs disponibles:', Array.from(this.tabs.keys()));
        }
        
        if (tabFromUrl && this.tabs.has(tabFromUrl)) {
            console.log(`🎯 Cambiando a tab desde URL: ${tabFromUrl}`);
            this.switchTab(tabFromUrl, false);
        } else {
            console.log(`🏠 Cargando tab por defecto: ${this.currentTab}`);
            this.switchTab(this.currentTab, false);
        }
    }
    
    /**
     * Gestión de historial
     */
    addToHistory(tabName) {
        if (!tabName || tabName === this.currentTab) return;
        
        // Eliminar duplicados
        this.tabHistory = this.tabHistory.filter(t => t !== tabName);
        
        // Agregar al inicio
        this.tabHistory.unshift(tabName);
        
        // Limitar tamaño
        if (this.tabHistory.length > this.maxHistory) {
            this.tabHistory = this.tabHistory.slice(0, this.maxHistory);
        }
    }
    
    goBack() {
        if (this.tabHistory.length > 0) {
            const previousTab = this.tabHistory.shift();
            this.switchTab(previousTab);
        }
    }
    
    goForward() {
        // Implementar navegación hacia adelante si es necesario
        this.switchToNextTab();
    }
    
    /**
     * Cambia al siguiente tab
     */
    switchToNextTab() {
        const tabNames = Array.from(this.tabs.keys());
        const currentIndex = tabNames.indexOf(this.currentTab);
        const nextIndex = (currentIndex + 1) % tabNames.length;
        this.switchTab(tabNames[nextIndex]);
    }
    
    /**
     * Cambia al tab anterior
     */
    switchToPreviousTab() {
        const tabNames = Array.from(this.tabs.keys());
        const currentIndex = tabNames.indexOf(this.currentTab);
        const prevIndex = currentIndex === 0 ? tabNames.length - 1 : currentIndex - 1;
        this.switchTab(tabNames[prevIndex]);
    }
    
    /**
     * Refresca el tab actual
     */
    async refreshCurrentTab() {
        if (!this.currentTab) return;
        
        const tab = this.tabs.get(this.currentTab);
        if (tab) {
            tab.isLoaded = false;
            await this.loadTabContent(tab);
        }
    }
    
    /**
     * Recarga un tab específico
     */
    async reloadTab(tabName) {
        const tab = this.tabs.get(tabName);
        if (tab) {
            tab.isLoaded = false;
            
            if (tabName === this.currentTab) {
                await this.loadTabContent(tab);
            }
        }
    }
    
    /**
     * Precarga el siguiente tab
     */
    async preloadNextTab() {
        const tabNames = Array.from(this.tabs.keys());
        const currentIndex = tabNames.indexOf(this.currentTab);
        const nextIndex = (currentIndex + 1) % tabNames.length;
        const nextTab = this.tabs.get(tabNames[nextIndex]);
        
        if (nextTab && !nextTab.isLoaded && !nextTab.isLoading) {
            setTimeout(() => {
                this.loadTabContent(nextTab);
            }, 1000);
        }
    }
    
    /**
     * Emite evento de cambio de tab
     */
    emitTabChange(tabName) {
        const event = new CustomEvent('tabChange', {
            detail: {
                tabName: tabName,
                previousTab: this.tabHistory[0] || null,
                timestamp: Date.now()
            }
        });
        document.dispatchEvent(event);
    }
    
    /**
     * Obtiene información del tab actual
     */
    getCurrentTab() {
        return this.tabs.get(this.currentTab);
    }
    
    /**
     * Obtiene todos los tabs
     */
    getAllTabs() {
        return Array.from(this.tabs.values());
    }
    
    /**
     * Verifica si un tab está cargado
     */
    isTabLoaded(tabName) {
        const tab = this.tabs.get(tabName);
        return tab ? tab.isLoaded : false;
    }
    
    /**
     * Obtiene estadísticas de uso de tabs
     */
    getTabStats() {
        const stats = {};
        
        this.tabs.forEach((tab, name) => {
            stats[name] = {
                label: tab.label,
                isLoaded: tab.isLoaded,
                lastAccessed: tab.lastAccessed,
                accessCount: this.tabHistory.filter(t => t === name).length
            };
        });
        
        return stats;
    }
    
    /**
     * Configuración dinámica
     */
    setConfig(newConfig) {
        this.config = { ...this.config, ...newConfig };
    }
    
    /**
     * Limpia el historial
     */
    clearHistory() {
        this.tabHistory = [];
    }
    
    /**
     * Destructor para limpieza
     */
    destroy() {
        // Remover event listeners
        document.removeEventListener('keydown', this.handleKeyDown);
        window.removeEventListener('popstate', this.handlePopState);
        
        // Limpiar referencias
        this.tabs.clear();
        this.tabHistory = [];
    }
}

// Crear instancia global cuando DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    // Esperar un poco más para que NavigationManagerV2 se inicialice primero
    setTimeout(() => {
        window.tabManager = new TabManager();
        console.log('🎭 TabManager inicializado después de NavigationManager');
        
        // Configurar funciones globales después de la inicialización
        window.switchTab = (tabName) => window.tabManager?.switchTab(tabName);
        window.refreshCurrentTab = () => window.tabManager?.refreshCurrentTab();
        window.reloadTab = (tabName) => window.tabManager?.reloadTab(tabName);
    }, 100);
});

// Event listeners para integración con otros módulos
document.addEventListener('tabChange', (e) => {
    if (AdminConfig?.debug?.enabled) {
        console.log('📋 Tab changed:', e.detail);
    }
});

// Exportar para ES6 modules si es necesario
if (typeof module !== 'undefined' && module.exports) {
    module.exports = TabManager;
}