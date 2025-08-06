/**
 * ==========================================================================
 * CONTENT LOADER - Kavia Hoteles Panel de Administración
 * Sistema de carga dinámica de contenido para módulos
 * ==========================================================================
 */

class ContentLoader {
    constructor() {
        this.cache = new Map();
        this.loadingStates = new Map();
        this.config = {
            cacheTimeout: 5 * 60 * 1000, // 5 minutos
            retryAttempts: 3,
            retryDelay: 1000,
            preloadEnabled: true,
            lazyLoading: true
        };
        
        this.init();
    }
    
    /**
     * Inicializa el content loader
     */
    init() {
        this.setupIntersectionObserver();
        this.bindEvents();
        
        if (AdminConfig?.debug?.enabled) {
            console.log('📦 Content Loader inicializado');
        }
    }
    
    /**
     * Configura el observer para lazy loading
     */
    setupIntersectionObserver() {
        if (!this.config.lazyLoading) return;
        
        this.intersectionObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const tabName = entry.target.dataset.tab;
                    if (tabName && !this.isTabLoaded(tabName)) {
                        this.preloadTab(tabName);
                    }
                }
            });
        }, {
            rootMargin: '50px'
        });
    }
    
    /**
     * Vincula eventos globales
     */
    bindEvents() {
        // Evento para limpiar cache cuando se hace logout
        document.addEventListener('logout', () => {
            this.clearCache();
        });
        
        // Evento para recargar contenido cuando se actualiza la página
        window.addEventListener('beforeunload', () => {
            this.saveCacheState();
        });
    }
    
    /**
     * Carga el contenido de un tab
     */
    async loadTabContent(tabName, forceReload = false) {
        if (this.loadingStates.get(tabName)) {
            console.log(`⏳ Tab ${tabName} ya está cargando...`);
            return;
        }
        
        if (!forceReload && this.isTabLoaded(tabName)) {
            console.log(`✅ Tab ${tabName} ya está cargado`);
            return;
        }
        
        this.loadingStates.set(tabName, true);
        
        try {
            const content = await this.fetchTabContent(tabName);
            this.renderTabContent(tabName, content);
            this.cacheContent(tabName, content);
            
            if (AdminConfig?.debug?.enabled) {
                console.log(`✅ Contenido cargado para tab: ${tabName}`);
            }
            
        } catch (error) {
            console.error(`❌ Error cargando tab ${tabName}:`, error);
            this.showTabError(tabName, error.message);
        } finally {
            this.loadingStates.delete(tabName);
        }
    }
    
    /**
     * Obtiene el contenido de un tab desde el servidor
     */
    async fetchTabContent(tabName) {
        const attempts = this.config.retryAttempts;
        
        for (let attempt = 1; attempt <= attempts; attempt++) {
            try {
                const response = await fetch(`admin_api.php?action=get_tab_content&tab=${tabName}`, {
                    method: 'GET',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                
                const data = await response.json();
                
                if (!data.success) {
                    throw new Error(data.message || 'Error desconocido');
                }
                
                return data.content;
                
            } catch (error) {
                if (attempt === attempts) {
                    throw error;
                }
                
                console.warn(`⚠️ Intento ${attempt} fallido para tab ${tabName}, reintentando...`);
                await this.delay(this.config.retryDelay * attempt);
            }
        }
    }
    
    /**
     * Renderiza el contenido en el tab
     */
    renderTabContent(tabName, content) {
        const tabElement = document.getElementById(`${tabName}-tab`);
        if (!tabElement) {
            console.error(`❌ Elemento del tab no encontrado: ${tabName}-tab`);
            return;
        }
        
        // Limpiar contenido existente
        tabElement.innerHTML = '';
        
        // Insertar nuevo contenido
        if (typeof content === 'string') {
            tabElement.innerHTML = content;
        } else if (content.html) {
            tabElement.innerHTML = content.html;
        } else {
            tabElement.innerHTML = this.createEmptyState(tabName);
        }
        
        // Ejecutar scripts si existen
        if (content.scripts) {
            this.executeScripts(content.scripts);
        }
        
        // Emitir evento de contenido cargado
        this.emitContentLoaded(tabName);
    }
    
    /**
     * Crea un estado vacío para un tab
     */
    createEmptyState(tabName) {
        const tabConfig = AdminConfig?.tabs?.labels?.[tabName] || tabName;
        
        return `
            <div class="empty-state">
                <i class="fas fa-inbox"></i>
                <h3>${tabConfig}</h3>
                <p>No hay contenido disponible para mostrar</p>
                <button class="btn btn-primary" onclick="contentLoader.refreshTab('${tabName}')">
                    <i class="fas fa-sync-alt"></i> Recargar
                </button>
            </div>
        `;
    }
    
    /**
     * Muestra un error en el tab
     */
    showTabError(tabName, errorMessage) {
        const tabElement = document.getElementById(`${tabName}-tab`);
        if (!tabElement) return;
        
        tabElement.innerHTML = `
            <div class="error-state">
                <i class="fas fa-exclamation-triangle"></i>
                <h3>Error al cargar contenido</h3>
                <p>${errorMessage}</p>
                <button class="btn btn-primary" onclick="contentLoader.refreshTab('${tabName}')">
                    <i class="fas fa-sync-alt"></i> Reintentar
                </button>
            </div>
        `;
    }
    
    /**
     * Ejecuta scripts de contenido
     */
    executeScripts(scripts) {
        scripts.forEach(script => {
            try {
                if (typeof script === 'string') {
                    eval(script);
                } else if (script.src) {
                    this.loadScript(script.src);
                }
            } catch (error) {
                console.error('Error ejecutando script:', error);
            }
        });
    }
    
    /**
     * Carga un script externo
     */
    loadScript(src) {
        return new Promise((resolve, reject) => {
            const script = document.createElement('script');
            script.src = src;
            script.onload = resolve;
            script.onerror = reject;
            document.head.appendChild(script);
        });
    }
    
    /**
     * Precarga un tab
     */
    async preloadTab(tabName) {
        if (!this.config.preloadEnabled || this.isTabLoaded(tabName)) {
            return;
        }
        
        try {
            await this.loadTabContent(tabName);
        } catch (error) {
            console.warn(`⚠️ Error precargando tab ${tabName}:`, error);
        }
    }
    
    /**
     * Refresca un tab
     */
    async refreshTab(tabName) {
        this.clearTabCache(tabName);
        await this.loadTabContent(tabName, true);
    }
    
    /**
     * Verifica si un tab está cargado
     */
    isTabLoaded(tabName) {
        const tabElement = document.getElementById(`${tabName}-tab`);
        return tabElement && tabElement.children.length > 0;
    }
    
    /**
     * Cachea el contenido de un tab
     */
    cacheContent(tabName, content) {
        this.cache.set(tabName, {
            content,
            timestamp: Date.now()
        });
    }
    
    /**
     * Obtiene contenido cacheado
     */
    getCachedContent(tabName) {
        const cached = this.cache.get(tabName);
        if (!cached) return null;
        
        const age = Date.now() - cached.timestamp;
        if (age > this.config.cacheTimeout) {
            this.cache.delete(tabName);
            return null;
        }
        
        return cached.content;
    }
    
    /**
     * Limpia el cache de un tab
     */
    clearTabCache(tabName) {
        this.cache.delete(tabName);
    }
    
    /**
     * Limpia todo el cache
     */
    clearCache() {
        this.cache.clear();
        console.log('🗑️ Cache limpiado');
    }
    
    /**
     * Guarda el estado del cache
     */
    saveCacheState() {
        const cacheState = {
            timestamp: Date.now(),
            tabs: Array.from(this.cache.keys())
        };
        
        try {
            sessionStorage.setItem('contentLoader_cache', JSON.stringify(cacheState));
        } catch (error) {
            console.warn('No se pudo guardar el estado del cache:', error);
        }
    }
    
    /**
     * Restaura el estado del cache
     */
    restoreCacheState() {
        try {
            const cacheState = sessionStorage.getItem('contentLoader_cache');
            if (cacheState) {
                const state = JSON.parse(cacheState);
                const age = Date.now() - state.timestamp;
                
                if (age < this.config.cacheTimeout) {
                    console.log('📦 Restaurando cache...');
                }
            }
        } catch (error) {
            console.warn('No se pudo restaurar el estado del cache:', error);
        }
    }
    
    /**
     * Emite evento de contenido cargado
     */
    emitContentLoaded(tabName) {
        const event = new CustomEvent('tabContentLoaded', {
            detail: { tabName }
        });
        document.dispatchEvent(event);
    }
    
    /**
     * Delay utility
     */
    delay(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    }
    
    /**
     * Actualiza la configuración
     */
    setConfig(newConfig) {
        this.config = { ...this.config, ...newConfig };
    }
    
    /**
     * Obtiene estadísticas del loader
     */
    getStats() {
        return {
            cachedTabs: this.cache.size,
            loadingTabs: this.loadingStates.size,
            config: this.config
        };
    }
    
    /**
     * Destruye el content loader
     */
    destroy() {
        this.clearCache();
        this.loadingStates.clear();
        
        if (this.intersectionObserver) {
            this.intersectionObserver.disconnect();
        }
        
        console.log('🗑️ Content Loader destruido');
    }
}

// Inicializar content loader cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    if (typeof ContentLoader !== 'undefined') {
        window.contentLoader = new ContentLoader();
    }
});