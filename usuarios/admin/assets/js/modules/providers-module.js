/**
 * ==========================================================================
 * PROVIDERS MODULE - Kavia Hoteles Panel de Administración
 * Módulo de gestión de proveedores de IA
 * ==========================================================================
 */

/**
 * Módulo de Proveedores de IA
 */
class ProvidersModule {
    constructor() {
        this.providers = [];
        this.filteredProviders = [];
        this.currentFilter = 'all';
        this.searchTerm = '';
        this.isLoading = false;
        
        this.init();
    }
    
    /**
     * Inicializa el módulo
     */
    init() {
        console.log('🤖 Inicializando módulo de proveedores...');
        
        this.bindEvents();
        this.loadProviders();
        
        if (AdminConfig?.debug?.enabled) {
            console.log('🤖 Módulo de proveedores inicializado');
        }
    }
    
    /**
     * Vincula eventos del módulo
     */
    bindEvents() {
        // Event listeners para cuando el contenido se carga dinámicamente
        document.addEventListener('tabContentLoaded', (e) => {
            if (e.detail.tabId === 'ia') {
                this.onTabActivated();
            }
        });
        
        // Escuchar eventos personalizados
        document.addEventListener('providerUpdated', () => {
            this.loadProviders();
        });
    }
    
    /**
     * Se ejecuta cuando el tab de proveedores es activado
     */
    onTabActivated() {
        console.log('🤖 Tab de proveedores activado');
        
        // Cargar datos si no están cargados
        if (this.providers.length === 0) {
            this.loadProviders();
        } else {
            this.updateStats();
        }
    }
    
    /**
     * Carga los proveedores desde la API
     */
    async loadProviders() {
        if (this.isLoading) return;
        
        this.isLoading = true;
        this.showLoading();
        
        try {
            console.log('📡 Cargando proveedores de IA...');
            
            const response = await apiClient.get('admin_api.php', {
                action: 'getAiProviders'
            });
            
            if (response.success) {
                this.providers = response.providers || [];
                this.filteredProviders = [...this.providers];
                
                console.log(`✅ ${this.providers.length} proveedores cargados`);
                
                this.renderProviders();
                this.updateStats();
                this.hideLoading();
                
            } else {
                throw new Error(response.error || 'Error al cargar proveedores');
            }
            
        } catch (error) {
            console.error('❌ Error al cargar proveedores:', error);
            this.showError('Error al cargar proveedores: ' + error.message);
        } finally {
            this.isLoading = false;
        }
    }
    
    /**
     * Renderiza la lista de proveedores
     */
    renderProviders() {
        const grid = document.getElementById('providers-grid');
        const emptyState = document.getElementById('providers-empty');
        
        if (!grid) return;
        
        if (this.filteredProviders.length === 0) {
            grid.style.display = 'none';
            if (emptyState) {
                emptyState.style.display = 'block';
            }
            return;
        }
        
        if (emptyState) {
            emptyState.style.display = 'none';
        }
        
        grid.style.display = 'grid';
        grid.innerHTML = this.filteredProviders.map(provider => this.renderProviderCard(provider)).join('');
        
        // Agregar eventos a las cards
        this.bindProviderEvents();
    }
    
    /**
     * Renderiza una tarjeta de proveedor
     */
    renderProviderCard(provider) {
        const isActive = parseInt(provider.is_active) === 1;
        const statusClass = isActive ? 'active' : 'inactive';
        const statusIcon = isActive ? 'check-circle' : 'times-circle';
        const statusColor = isActive ? 'success' : 'muted';
        
        // Determinar icono por tipo
        const typeIcons = {
            'openai': 'fa-brain',
            'claude': 'fa-robot',
            'deepseek': 'fa-microchip',
            'gemini': 'fa-google',
            'local': 'fa-server'
        };
        
        const typeIcon = typeIcons[provider.provider_type] || 'fa-robot';
        const typeColors = {
            'openai': '#10a37f',
            'claude': '#d97757',
            'deepseek': '#2563eb',
            'gemini': '#4285f4',
            'local': '#6b7280'
        };
        
        const typeColor = typeColors[provider.provider_type] || '#6b7280';
        
        return `
            <div class="provider-card ${statusClass}" data-provider-id="${provider.id}" data-provider-type="${provider.provider_type}">
                <div class="provider-header">
                    <div class="provider-info">
                        <h3>${this.escapeHtml(provider.name)}</h3>
                        <span class="provider-type" style="background-color: ${typeColor}20; color: ${typeColor};">
                            <i class="fas ${typeIcon}"></i>
                            ${provider.provider_type}
                        </span>
                    </div>
                    <div class="provider-status">
                        <span class="status-indicator ${statusClass}"></span>
                        <small class="${statusColor}">${isActive ? 'Activo' : 'Inactivo'}</small>
                    </div>
                </div>
                
                <div class="provider-details">
                    ${provider.model_name ? `<div class="provider-model">
                        <i class="fas fa-cog"></i>
                        Modelo: ${this.escapeHtml(provider.model_name)}
                    </div>` : ''}
                    
                    ${provider.description ? `<div class="provider-description">
                        ${this.escapeHtml(provider.description)}
                    </div>` : ''}
                </div>
                
                <div class="provider-actions">
                    <button class="btn btn-sm btn-info" onclick="providersModule.testProvider(${provider.id})">
                        <i class="fas fa-plug"></i>
                        Test
                    </button>
                    <button class="btn btn-sm btn-outline" onclick="providersModule.editProvider(${provider.id})">
                        <i class="fas fa-edit"></i>
                        Editar
                    </button>
                    <button class="btn btn-sm ${isActive ? 'btn-warning' : 'btn-success'}" 
                            onclick="providersModule.toggleProvider(${provider.id}, ${!isActive})">
                        <i class="fas fa-${isActive ? 'pause' : 'play'}"></i>
                        ${isActive ? 'Desactivar' : 'Activar'}
                    </button>
                    <button class="btn btn-sm btn-danger" onclick="providersModule.deleteProvider(${provider.id}, '${this.escapeHtml(provider.name)}')">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        `;
    }
    
    /**
     * Vincula eventos a las tarjetas de proveedor
     */
    bindProviderEvents() {
        // Eventos de hover para preview
        const cards = document.querySelectorAll('.provider-card');
        cards.forEach(card => {
            card.addEventListener('mouseenter', () => {
                card.style.transform = 'translateY(-4px)';
            });
            
            card.addEventListener('mouseleave', () => {
                card.style.transform = 'translateY(0)';
            });
        });
    }
    
    /**
     * Actualiza las estadísticas
     */
    updateStats() {
        const totalElement = document.getElementById('total-providers');
        const activeElement = document.getElementById('active-providers');
        const inactiveElement = document.getElementById('inactive-providers');
        const connectedElement = document.getElementById('connected-providers');
        
        if (totalElement) totalElement.textContent = this.providers.length;
        
        const activeCount = this.providers.filter(p => parseInt(p.is_active) === 1).length;
        const inactiveCount = this.providers.length - activeCount;
        
        if (activeElement) activeElement.textContent = activeCount;
        if (inactiveElement) inactiveElement.textContent = inactiveCount;
        if (connectedElement) connectedElement.textContent = activeCount; // Simplificado
    }
    
    /**
     * Aplica filtro a los proveedores
     */
    applyFilter(filter) {
        this.currentFilter = filter;
        
        this.filteredProviders = this.providers.filter(provider => {
            const matchesSearch = this.searchTerm === '' || 
                provider.name.toLowerCase().includes(this.searchTerm) ||
                provider.provider_type.toLowerCase().includes(this.searchTerm);
            
            const matchesFilter = filter === 'all' || 
                (filter === 'active' && parseInt(provider.is_active) === 1) ||
                (filter === 'inactive' && parseInt(provider.is_active) === 0) ||
                provider.provider_type === filter;
            
            return matchesSearch && matchesFilter;
        });
        
        this.renderProviders();
        
        console.log(`🔍 Filtro aplicado: ${filter}, ${this.filteredProviders.length} resultados`);
    }
    
    /**
     * Busca proveedores
     */
    search(term) {
        this.searchTerm = term.toLowerCase();
        this.applyFilter(this.currentFilter);
    }
    
    /**
     * Refresca los datos
     */
    async refresh() {
        console.log('🔄 Refrescando proveedores...');
        await this.loadProviders();
        showInfo('Proveedores actualizados');
    }
    
    /**
     * Abre el modal para crear/editar proveedor
     */
    async openModal(providerId = null) {
        try {
            if (providerId) {
                console.log(`✏️ Editando proveedor ID: ${providerId}`);
                
                // Cargar datos del proveedor para edición
                const response = await apiClient.get('admin_api.php', {
                    action: 'editAiProvider',
                    id: providerId
                });
                
                if (response.success && response.provider) {
                    this.populateModal(response.provider);
                } else {
                    throw new Error(response.error || 'Proveedor no encontrado');
                }
            } else {
                console.log('➕ Creando nuevo proveedor');
                this.clearModal();
            }
            
            // Abrir modal
            if (window.modalManager) {
                window.modalManager.open('provider-modal');
            }
            
        } catch (error) {
            console.error('❌ Error al abrir modal:', error);
            showError('Error al cargar proveedor: ' + error.message);
        }
    }
    
    /**
     * Llena el modal con datos del proveedor
     */
    populateModal(provider) {
        document.getElementById('provider-id').value = provider.id;
        document.getElementById('provider-name').value = provider.name || '';
        document.getElementById('provider-type').value = provider.provider_type || '';
        document.getElementById('provider-description').value = provider.description || '';
        document.getElementById('api-key').value = provider.api_key || '';
        document.getElementById('api-url').value = provider.api_url || '';
        document.getElementById('model-name').value = provider.model_name || '';
        document.getElementById('is-active').checked = parseInt(provider.is_active) === 1;
        
        // Parsear parámetros JSON
        if (provider.parameters) {
            document.getElementById('custom-parameters').value = provider.parameters;
        }
        
        // Actualizar título del modal
        document.getElementById('provider-modal-title-text').textContent = 'Editar Proveedor de IA';
        document.getElementById('save-btn-text').textContent = 'Actualizar Proveedor';
        
        // Triggear cambio de tipo para cargar modelos
        if (window.handleProviderTypeChange) {
            window.handleProviderTypeChange();
        }
    }
    
    /**
     * Limpia el formulario del modal
     */
    clearModal() {
        document.getElementById('provider-form').reset();
        document.getElementById('provider-id').value = '';
        
        // Restaurar título del modal
        document.getElementById('provider-modal-title-text').textContent = 'Nuevo Proveedor de IA';
        document.getElementById('save-btn-text').textContent = 'Crear Proveedor';
    }
    
    /**
     * Guarda un proveedor
     */
    async saveProvider(data) {
        try {
            const action = data.id ? 'updateAiProvider' : 'saveAiProvider';
            const response = await apiClient.post('admin_api.php', {
                action: action,
                ...data
            });
            
            if (response.success) {
                showSuccess(response.message || 'Proveedor guardado correctamente');
                
                if (window.modalManager) {
                    window.modalManager.close('provider-modal');
                }
                
                await this.loadProviders();
                
            } else {
                throw new Error(response.error || 'Error al guardar proveedor');
            }
            
        } catch (error) {
            console.error('❌ Error al guardar proveedor:', error);
            throw error;
        }
    }
    
    /**
     * Prueba un proveedor
     */
    async testProvider(providerId) {
        try {
            console.log(`🧪 Probando proveedor ID: ${providerId}`);
            
            const response = await apiClient.post('admin_api.php', {
                action: 'testAiProvider',
                id: providerId
            });
            
            if (response.success) {
                showSuccess(response.test_message || 'Conexión exitosa');
            } else {
                throw new Error(response.error || 'Error en la prueba de conexión');
            }
            
        } catch (error) {
            console.error('❌ Error al probar proveedor:', error);
            showError('Error al probar conexión: ' + error.message);
        }
    }
    
    /**
     * Edita un proveedor
     */
    editProvider(providerId) {
        this.openModal(providerId);
    }
    
    /**
     * Alterna el estado de un proveedor
     */
    async toggleProvider(providerId, newStatus) {
        try {
            console.log(`🔄 Cambiando estado del proveedor ID: ${providerId} a ${newStatus}`);
            
            const response = await apiClient.post('admin_api.php', {
                action: 'toggleAiProvider',
                id: providerId,
                active: newStatus ? 1 : 0
            });
            
            if (response.success) {
                showSuccess(response.message || 'Estado actualizado');
                await this.loadProviders();
            } else {
                throw new Error(response.error || 'Error al cambiar estado');
            }
            
        } catch (error) {
            console.error('❌ Error al cambiar estado:', error);
            showError('Error al cambiar estado: ' + error.message);
        }
    }
    
    /**
     * Elimina un proveedor
     */
    async deleteProvider(providerId, providerName) {
        try {
            const confirmed = await this.confirmDelete(providerName);
            if (!confirmed) return;
            
            console.log(`🗑️ Eliminando proveedor ID: ${providerId}`);
            
            const response = await apiClient.post('admin_api.php', {
                action: 'deleteAiProvider',
                id: providerId
            });
            
            if (response.success) {
                showSuccess(response.message || 'Proveedor eliminado');
                await this.loadProviders();
            } else {
                throw new Error(response.error || 'Error al eliminar proveedor');
            }
            
        } catch (error) {
            console.error('❌ Error al eliminar proveedor:', error);
            showError('Error al eliminar: ' + error.message);
        }
    }
    
    /**
     * Confirma la eliminación de un proveedor
     */
    async confirmDelete(providerName) {
        if (window.modalManager && window.modalManager.confirm) {
            return await window.modalManager.confirm(
                'Confirmar Eliminación',
                `¿Estás seguro de que quieres eliminar el proveedor "${providerName}"?\n\nEsta acción no se puede deshacer.`,
                {
                    type: 'danger',
                    confirmText: 'Eliminar',
                    cancelText: 'Cancelar'
                }
            );
        } else {
            return confirm(`¿Estás seguro de que quieres eliminar el proveedor "${providerName}"?`);
        }
    }
    
    /**
     * Muestra estado de carga
     */
    showLoading() {
        const loading = document.getElementById('providers-loading');
        const grid = document.getElementById('providers-grid');
        const empty = document.getElementById('providers-empty');
        
        if (loading) loading.style.display = 'block';
        if (grid) grid.style.display = 'none';
        if (empty) empty.style.display = 'none';
    }
    
    /**
     * Oculta estado de carga
     */
    hideLoading() {
        const loading = document.getElementById('providers-loading');
        if (loading) loading.style.display = 'none';
    }
    
    /**
     * Muestra error
     */
    showError(message) {
        this.hideLoading();
        
        const grid = document.getElementById('providers-grid');
        if (grid) {
            grid.innerHTML = `
                <div class="error-state">
                    <i class="fas fa-exclamation-triangle"></i>
                    <h3>Error al cargar proveedores</h3>
                    <p>${this.escapeHtml(message)}</p>
                    <button class="btn btn-primary" onclick="providersModule.loadProviders()">
                        <i class="fas fa-redo"></i>
                        Reintentar
                    </button>
                </div>
            `;
        }
    }
    
    /**
     * Escapa HTML para prevenir XSS
     */
    escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    }
}

// Instancia global del módulo
window.providersModule = new ProvidersModule();

// Exponer funciones globales para compatibilidad
window.openProviderModal = (providerId) => window.providersModule.openModal(providerId);

console.log('✅ Módulo de proveedores cargado');