/**
 * ==========================================================================
 * APIS MODULE - Kavia Hoteles Panel de Administración
 * Módulo JavaScript para la gestión completa de APIs Externas
 * ==========================================================================
 */

class ApisModule {
    constructor() {
        // Configuración del módulo
        this.config = {
            pageSize: 25,
            currentPage: 1,
            totalPages: 1,
            totalItems: 0,
            sortField: 'created_at',
            sortDirection: 'desc',
            searchTerm: '',
            typeFilter: '',
            isLoading: false
        };
        
        // Referencias a elementos DOM
        this.elements = {};
        
        // Datos cacheados
        this.apisData = [];
        this.filteredData = [];
        
        // Estado del modal
        this.modalState = {
            isOpen: false,
            isEditing: false,
            currentApiId: null,
            currentTab: 'basic'
        };
        
        // Configuración de proveedores
        this.providerConfig = {
            booking: {
                name: 'Booking.com',
                icon: 'fas fa-bed',
                color: '#003580',
                baseUrl: 'https://distribution-xml.booking.com',
                fields: ['partner_id', 'username', 'password'],
                description: 'Acceso a inventario y precios de Booking.com'
            },
            tripadvisor: {
                name: 'TripAdvisor',
                icon: 'fas fa-map-marker-alt', 
                color: '#00AF87',
                baseUrl: 'https://api.tripadvisor.com',
                fields: ['api_key'],
                description: 'API de contenido y reseñas de TripAdvisor'
            },
            expedia: {
                name: 'Expedia',
                icon: 'fas fa-plane',
                color: '#FFC72C',
                baseUrl: 'https://services.expedia.com',
                fields: ['api_key', 'shared_secret'],
                description: 'Partner API de Expedia Group'
            },
            google: {
                name: 'Google Business Profile',
                icon: 'fab fa-google',
                color: '#4285F4',
                baseUrl: 'https://mybusinessbusinessinformation.googleapis.com',
                fields: ['api_key', 'oauth_token'],
                description: 'API de Google My Business'
            },
            airbnb: {
                name: 'Airbnb',
                icon: 'fab fa-airbnb',
                color: '#FF5A5F',
                baseUrl: 'https://api.airbnb.com',
                fields: ['api_key', 'access_token'],
                description: 'Airbnb Partner API'
            },
            hotels: {
                name: 'Hotels.com',
                icon: 'fas fa-building',
                color: '#C41E3A',
                baseUrl: 'https://api.hotels.com',
                fields: ['api_key'],
                description: 'Hotels.com Partner API'
            },
            custom: {
                name: 'API Personalizada',
                icon: 'fas fa-code',
                color: '#6B7280',
                baseUrl: '',
                fields: ['api_key'],
                description: 'Configuración personalizada para APIs propias'
            }
        };
        
        this.init();
    }

    /**
     * Inicialización del módulo
     */
    init() {
        console.log('[ApisModule] Inicializando módulo de APIs...');
        
        // Configurar event listeners
        this.setupEventListeners();
        
        // Cargar datos iniciales
        this.loadApis();
        
        console.log('[ApisModule] Módulo inicializado correctamente');
    }

    /**
     * Configurar event listeners
     */
    setupEventListeners() {
        // Búsqueda en tiempo real
        const searchInput = document.getElementById('apis-search');
        if (searchInput) {
            searchInput.addEventListener('input', AdminUI.debounce((e) => {
                this.filterApis(e.target.value);
            }, 300));
        }
        
        // Filtros de tipo y estado
        document.getElementById('apis-type-filter')?.addEventListener('change', (e) => {
            this.filterByType(e.target.value);
        });
        
        document.getElementById('apis-status-filter')?.addEventListener('change', (e) => {
            this.filterByStatus(e.target.value);
        });
        
        document.getElementById('apis-per-page')?.addEventListener('change', (e) => {
            this.changePageSize(parseInt(e.target.value));
        });

        // Keyboard shortcuts
        document.addEventListener('keydown', (e) => {
            if (e.ctrlKey && e.key === 'k') {
                e.preventDefault();
                document.getElementById('apis-search')?.focus();
            }
            if (e.key === 'Escape') {
                this.closeModal();
                this.closeDetailsModal();
            }
        });
    }

    /**
     * Cargar lista de APIs
     */
    async loadApis() {
        if (this.isLoading) return;
        
        this.isLoading = true;
        this.showLoading();
        
        try {
            console.log('[ApisModule] Cargando APIs...');
            
            const response = await AdminAPI.request('getExternalApis', {
                page: this.currentPage,
                limit: this.pageSize,
                search: this.currentFilter.search,
                type: this.currentFilter.type,
                status: this.currentFilter.status,
                sort_field: this.sortField,
                sort_direction: this.sortDirection
            });
            
            if (response.success) {
                this.apis = response.data.apis || [];
                this.filteredApis = [...this.apis];
                this.totalPages = response.data.total_pages || 1;
                
                this.renderApisList();
                this.updateStats(response.data.stats);
                this.updatePagination(response.data);
                
                console.log(`[ApisModule] Cargadas ${this.apis.length} APIs`);
            } else {
                throw new Error(response.message || 'Error al cargar APIs');
            }
            
        } catch (error) {
            console.error('[ApisModule] Error cargando APIs:', error);
            NotificationManager.error('Error al cargar APIs: ' + error.message);
            this.showError('Error al cargar APIs. Por favor, intenta nuevamente.');
        } finally {
            this.isLoading = false;
        }
    }

    /**
     * Renderizar lista de APIs
     */
    renderApisList() {
        const container = document.getElementById('apis-list-container');
        if (!container) return;

        if (this.apis.length === 0) {
            this.showEmpty();
            return;
        }

        const isMobile = window.innerWidth <= 768;
        
        if (isMobile) {
            this.renderMobileCards();
        } else {
            this.renderDesktopTable();
        }
    }

    /**
     * Renderizar tabla para desktop
     */
    renderDesktopTable() {
        const container = document.getElementById('apis-list-container');
        const template = document.getElementById('apis-table-template');
        
        if (!template) return;
        
        const clone = template.content.cloneNode(true);
        const tbody = clone.getElementById('apis-table-body');
        
        tbody.innerHTML = this.apis.map(api => this.generateApiRowHTML(api)).join('');
        
        container.innerHTML = '';
        container.appendChild(clone);
        
        // Actualizar iconos de ordenamiento
        this.updateSortIcons();
    }

    /**
     * Renderizar cards para móvil
     */
    renderMobileCards() {
        const container = document.getElementById('apis-list-container');
        const template = document.getElementById('apis-mobile-template');
        
        if (!template) return;
        
        const clone = template.content.cloneNode(true);
        const cardsContainer = clone.querySelector('.data-cards');
        
        cardsContainer.innerHTML = this.apis.map(api => this.generateApiCardHTML(api)).join('');
        
        container.innerHTML = '';
        container.appendChild(clone);
    }

    /**
     * Generar HTML para fila de API
     */
    generateApiRowHTML(api) {
        const template = document.getElementById('api-row-template');
        if (!template) return '';

        const providerConfig = this.providerConfig[api.provider_type] || this.providerConfig.custom;
        
        let html = template.innerHTML;
        
        // Reemplazos básicos
        html = html.replace(/{id}/g, api.id);
        html = html.replace(/{name}/g, AdminUI.escapeHtml(api.name));
        html = html.replace(/{name_escaped}/g, AdminUI.escapeHtml(api.name).replace(/'/g, "\\'"));
        html = html.replace(/{provider_name}/g, providerConfig.name);
        html = html.replace(/{provider_type}/g, api.provider_type);
        html = html.replace(/{provider_display}/g, providerConfig.name);
        html = html.replace(/{provider_icon}/g, providerConfig.icon);
        
        // Estado y clases
        html = html.replace(/{status_class}/g, this.getStatusClass(api));
        html = html.replace(/{status}/g, api.status);
        html = html.replace(/{status_badge}/g, this.generateStatusBadge(api));
        html = html.replace(/{connection_badge}/g, this.generateConnectionBadge(api));
        
        // Información de rate limit
        const rateLimitInfo = api.rate_limit ? 
            `<div class="rate-limit-info">${api.rate_limit} req/min</div>` : '';
        html = html.replace(/{rate_limit_info}/g, rateLimitInfo);
        
        // Fecha de última prueba
        html = html.replace(/{last_test_formatted}/g, this.formatDate(api.last_test));
        
        // Iconos y texto de estado
        html = html.replace(/{status_icon}/g, this.getStatusIcon(api));
        html = html.replace(/{status_toggle_text}/g, this.getStatusToggleText(api));
        
        return html;
    }

    /**
     * Generar HTML para card de API
     */
    generateApiCardHTML(api) {
        const template = document.getElementById('api-card-template');
        if (!template) return '';

        const providerConfig = this.providerConfig[api.provider_type] || this.providerConfig.custom;
        
        let html = template.innerHTML;
        
        // Reemplazos básicos
        html = html.replace(/{id}/g, api.id);
        html = html.replace(/{name}/g, AdminUI.escapeHtml(api.name));
        html = html.replace(/{name_escaped}/g, AdminUI.escapeHtml(api.name).replace(/'/g, "\\'"));
        html = html.replace(/{provider_type}/g, api.provider_type);
        html = html.replace(/{provider_display}/g, providerConfig.name);
        html = html.replace(/{provider_icon}/g, providerConfig.icon);
        
        // Estado y badges
        html = html.replace(/{status_badge}/g, this.generateStatusBadge(api));
        html = html.replace(/{connection_badge}/g, this.generateConnectionBadge(api));
        
        // Información de rate limit
        const rateLimitField = api.rate_limit ? 
            `<div class="data-card-field">
                <span class="data-card-label">Rate Limit:</span>
                <span class="data-card-value">${api.rate_limit} req/min</span>
            </div>` : '';
        html = html.replace(/{rate_limit_field}/g, rateLimitField);
        
        // Fecha de última prueba
        html = html.replace(/{last_test_formatted}/g, this.formatDate(api.last_test));
        
        return html;
    }

    /**
     * Generar badge de estado
     */
    generateStatusBadge(api) {
        const statusConfig = {
            active: { text: 'Activa', class: 'success', icon: 'check-circle' },
            inactive: { text: 'Inactiva', class: 'secondary', icon: 'circle' },
            testing: { text: 'En Prueba', class: 'warning', icon: 'vial' },
            error: { text: 'Error', class: 'danger', icon: 'exclamation-triangle' }
        };
        
        const config = statusConfig[api.status] || statusConfig.inactive;
        
        return `<span class="badge badge-${config.class}">
            <i class="fas fa-${config.icon}"></i>
            ${config.text}
        </span>`;
    }

    /**
     * Generar badge de conexión
     */
    generateConnectionBadge(api) {
        const connectionConfig = {
            success: { text: 'OK', class: 'connection-success', icon: 'check' },
            error: { text: 'Error', class: 'connection-error', icon: 'times' },
            testing: { text: 'Probando...', class: 'connection-testing', icon: 'spinner fa-spin' },
            unknown: { text: 'Sin probar', class: 'connection-unknown', icon: 'question' }
        };
        
        const status = api.connection_status || 'unknown';
        const config = connectionConfig[status] || connectionConfig.unknown;
        
        return `<span class="connection-badge ${config.class}">
            <i class="fas fa-${config.icon}"></i>
            ${config.text}
        </span>`;
    }

    /**
     * Obtener clase CSS para el estado
     */
    getStatusClass(api) {
        const classes = ['api-row'];
        if (api.status === 'error') classes.push('status-error');
        if (api.status === 'testing') classes.push('status-testing');
        return classes.join(' ');
    }

    /**
     * Obtener icono para el estado
     */
    getStatusIcon(api) {
        const icons = {
            active: 'fa-pause',
            inactive: 'fa-play',
            testing: 'fa-stop',
            error: 'fa-redo'
        };
        return icons[api.status] || 'fa-play';
    }

    /**
     * Obtener texto para toggle de estado
     */
    getStatusToggleText(api) {
        const texts = {
            active: 'Desactivar',
            inactive: 'Activar',
            testing: 'Detener prueba',
            error: 'Reintentar'
        };
        return texts[api.status] || 'Activar';
    }

    /**
     * Actualizar estadísticas
     */
    updateStats(stats) {
        if (!stats) return;
        
        document.getElementById('total-apis').textContent = stats.total || 0;
        document.getElementById('active-apis').textContent = stats.active || 0;
        document.getElementById('testing-apis').textContent = stats.testing || 0;
        document.getElementById('failed-apis').textContent = stats.failed || 0;
    }

    /**
     * Actualizar paginación
     */
    updatePagination(data) {
        const paginationElement = document.getElementById('apis-pagination');
        if (!paginationElement) return;

        const showingElement = document.getElementById('apis-showing');
        const totalElement = document.getElementById('apis-total');
        const pageInfoElement = document.getElementById('apis-page-info');
        const prevBtn = document.getElementById('apis-prev-btn');
        const nextBtn = document.getElementById('apis-next-btn');

        if (data.total > 0) {
            const start = ((this.currentPage - 1) * this.pageSize) + 1;
            const end = Math.min(this.currentPage * this.pageSize, data.total);
            
            showingElement.textContent = `${start}-${end}`;
            totalElement.textContent = data.total;
            pageInfoElement.textContent = `Página ${this.currentPage} de ${this.totalPages}`;
            
            prevBtn.disabled = this.currentPage <= 1;
            nextBtn.disabled = this.currentPage >= this.totalPages;
            
            paginationElement.style.display = 'flex';
        } else {
            paginationElement.style.display = 'none';
        }
    }

    /**
     * Filtrar APIs por texto de búsqueda
     */
    filterApis(searchText) {
        this.currentFilter.search = searchText.toLowerCase();
        this.currentPage = 1;
        this.loadApis();
    }

    /**
     * Filtrar por tipo de proveedor
     */
    filterByType(type) {
        this.currentFilter.type = type;
        this.currentPage = 1;
        this.loadApis();
    }

    /**
     * Filtrar por estado
     */
    filterByStatus(status) {
        this.currentFilter.status = status;
        this.currentPage = 1;
        this.loadApis();
    }

    /**
     * Cambiar tamaño de página
     */
    changePageSize(size) {
        this.pageSize = size;
        this.currentPage = 1;
        this.loadApis();
    }

    /**
     * Página anterior
     */
    previousPage() {
        if (this.currentPage > 1) {
            this.currentPage--;
            this.loadApis();
        }
    }

    /**
     * Página siguiente
     */
    nextPage() {
        if (this.currentPage < this.totalPages) {
            this.currentPage++;
            this.loadApis();
        }
    }

    /**
     * Ordenar por campo
     */
    sortBy(field) {
        if (this.sortField === field) {
            this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            this.sortField = field;
            this.sortDirection = 'asc';
        }
        
        this.currentPage = 1;
        this.loadApis();
    }

    /**
     * Actualizar iconos de ordenamiento
     */
    updateSortIcons() {
        document.querySelectorAll('.sort-icon').forEach(icon => {
            icon.className = 'fas fa-sort sort-icon';
        });
        
        const currentIcon = document.querySelector(`[onclick="apisModule.sortBy('${this.sortField}')"] .sort-icon`);
        if (currentIcon) {
            currentIcon.className = `fas fa-sort-${this.sortDirection === 'asc' ? 'up' : 'down'} sort-icon`;
        }
    }

    /**
     * Refrescar lista
     */
    async refreshList() {
        console.log('[ApisModule] Refrescando lista...');
        await this.loadApis();
        NotificationManager.success('Lista actualizada correctamente');
    }

    /**
     * Mostrar modal para agregar API
     */
    showAddModal() {
        this.selectedApi = null;
        this.resetForm();
        document.getElementById('api-modal-title').textContent = 'Agregar Nueva API';
        document.getElementById('save-btn-text').textContent = 'Guardar API';
        this.openModal();
    }

    /**
     * Editar API existente
     */
    async editApi(id) {
        try {
            console.log(`[ApisModule] Editando API ${id}...`);
            
            const response = await AdminAPI.request('getExternalApi', { id: id });
            
            if (response.success && response.data) {
                this.selectedApi = response.data;
                this.populateForm(response.data);
                document.getElementById('api-modal-title').textContent = 'Editar API';
                document.getElementById('save-btn-text').textContent = 'Actualizar API';
                this.openModal();
            } else {
                throw new Error(response.message || 'API no encontrada');
            }
            
        } catch (error) {
            console.error('[ApisModule] Error al cargar API:', error);
            NotificationManager.error('Error al cargar la API: ' + error.message);
        }
    }

    /**
     * Ver detalles de API
     */
    async viewDetails(id) {
        try {
            console.log(`[ApisModule] Viendo detalles de API ${id}...`);
            
            const response = await AdminAPI.request('getExternalApi', { id: id });
            
            if (response.success && response.data) {
                this.showDetailsModal(response.data);
            } else {
                throw new Error(response.message || 'API no encontrada');
            }
            
        } catch (error) {
            console.error('[ApisModule] Error al cargar detalles:', error);
            NotificationManager.error('Error al cargar detalles: ' + error.message);
        }
    }

    /**
     * Probar conexión de API
     */
    async testConnection(id) {
        try {
            console.log(`[ApisModule] Probando conexión de API ${id}...`);
            
            // Actualizar UI para mostrar que está probando
            this.updateConnectionStatus(id, 'testing');
            
            const response = await AdminAPI.request('testExternalApi', { id: id });
            
            if (response.success) {
                this.updateConnectionStatus(id, 'success');
                NotificationManager.success(`Conexión exitosa: ${response.message}`);
            } else {
                this.updateConnectionStatus(id, 'error');
                NotificationManager.error(`Error de conexión: ${response.message}`);
            }
            
        } catch (error) {
            console.error('[ApisModule] Error en test de conexión:', error);
            this.updateConnectionStatus(id, 'error');
            NotificationManager.error('Error al probar conexión: ' + error.message);
        }
    }

    /**
     * Probar todas las conexiones
     */
    async testAllConnections() {
        if (this.apis.length === 0) {
            NotificationManager.info('No hay APIs para probar');
            return;
        }

        console.log('[ApisModule] Probando todas las conexiones...');
        NotificationManager.info(`Probando conexión de ${this.apis.length} APIs...`);

        let success = 0;
        let failed = 0;

        for (const api of this.apis) {
            try {
                this.updateConnectionStatus(api.id, 'testing');
                
                const response = await AdminAPI.request('testExternalApi', { id: api.id });
                
                if (response.success) {
                    this.updateConnectionStatus(api.id, 'success');
                    success++;
                } else {
                    this.updateConnectionStatus(api.id, 'error');
                    failed++;
                }
            } catch (error) {
                console.error(`[ApisModule] Error probando API ${api.id}:`, error);
                this.updateConnectionStatus(api.id, 'error');
                failed++;
            }
            
            // Pausa breve entre pruebas para no saturar
            await new Promise(resolve => setTimeout(resolve, 500));
        }

        const message = `Pruebas completadas: ${success} exitosas, ${failed} fallidas`;
        if (failed > 0) {
            NotificationManager.warning(message);
        } else {
            NotificationManager.success(message);
        }
    }

    /**
     * Actualizar estado de conexión en la UI
     */
    updateConnectionStatus(apiId, status) {
        const rows = document.querySelectorAll(`[data-api-id="${apiId}"]`);
        rows.forEach(row => {
            const badge = row.querySelector('.connection-badge');
            if (badge) {
                // Remover clases previas
                badge.className = badge.className.replace(/connection-\w+/g, '');
                
                const config = {
                    success: { text: 'OK', class: 'connection-success', icon: 'check' },
                    error: { text: 'Error', class: 'connection-error', icon: 'times' },
                    testing: { text: 'Probando...', class: 'connection-testing', icon: 'spinner fa-spin' },
                    unknown: { text: 'Sin probar', class: 'connection-unknown', icon: 'question' }
                };
                
                const statusConfig = config[status] || config.unknown;
                
                badge.className = `connection-badge ${statusConfig.class}`;
                badge.innerHTML = `<i class="fas fa-${statusConfig.icon}"></i> ${statusConfig.text}`;
            }
        });
    }

    /**
     * Toggle estado de API
     */
    async toggleStatus(id, currentStatus) {
        try {
            console.log(`[ApisModule] Cambiando estado de API ${id}...`);
            
            const newStatus = currentStatus === 'active' ? 'inactive' : 'active';
            
            const response = await AdminAPI.request('updateApiStatus', { 
                id: id, 
                status: newStatus 
            });
            
            if (response.success) {
                // Actualizar en la lista local
                const api = this.apis.find(a => a.id == id);
                if (api) {
                    api.status = newStatus;
                    this.renderApisList();
                }
                
                const action = newStatus === 'active' ? 'activada' : 'desactivada';
                NotificationManager.success(`API ${action} correctamente`);
            } else {
                throw new Error(response.message || 'Error al cambiar estado');
            }
            
        } catch (error) {
            console.error('[ApisModule] Error al cambiar estado:', error);
            NotificationManager.error('Error al cambiar estado: ' + error.message);
        }
    }

    /**
     * Confirmar eliminación de API
     */
    confirmDelete(id, name) {
        const message = `¿Estás seguro de que deseas eliminar la API "${name}"?\n\nEsta acción no se puede deshacer.`;
        
        if (confirm(message)) {
            this.deleteApi(id);
        }
    }

    /**
     * Eliminar API
     */
    async deleteApi(id) {
        try {
            console.log(`[ApisModule] Eliminando API ${id}...`);
            
            const response = await AdminAPI.request('deleteExternalApi', { id: id });
            
            if (response.success) {
                // Remover de la lista local
                this.apis = this.apis.filter(api => api.id != id);
                this.renderApisList();
                
                NotificationManager.success('API eliminada correctamente');
            } else {
                throw new Error(response.message || 'Error al eliminar API');
            }
            
        } catch (error) {
            console.error('[ApisModule] Error al eliminar:', error);
            NotificationManager.error('Error al eliminar API: ' + error.message);
        }
    }

    // ==========================================================================
    // MODAL DE CONFIGURACIÓN
    // ==========================================================================

    /**
     * Abrir modal de configuración
     */
    openModal() {
        const modal = document.getElementById('api-modal');
        if (modal) {
            modal.classList.add('show');
            document.body.style.overflow = 'hidden';
            
            // Focus en el primer campo
            setTimeout(() => {
                document.getElementById('api-name')?.focus();
            }, 150);
        }
    }

    /**
     * Cerrar modal de configuración
     */
    closeModal() {
        const modal = document.getElementById('api-modal');
        if (modal) {
            modal.classList.remove('show');
            document.body.style.overflow = '';
            this.resetForm();
        }
    }

    /**
     * Cambiar pestaña del formulario
     */
    switchFormTab(tabName) {
        // Actualizar botones de pestaña
        document.querySelectorAll('.form-tab-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        document.querySelector(`[data-tab="${tabName}"]`).classList.add('active');
        
        // Mostrar contenido de pestaña
        document.querySelectorAll('.form-tab-content').forEach(content => {
            content.style.display = 'none';
        });
        document.getElementById(`form-tab-${tabName}`).style.display = 'block';
    }

    /**
     * Actualizar campos según proveedor seleccionado
     */
    updateProviderFields(providerType) {
        const providerInfo = document.getElementById('provider-info');
        const providerDetails = providerInfo.querySelector('.provider-details');
        
        if (providerType && this.providerConfig[providerType]) {
            const config = this.providerConfig[providerType];
            
            providerDetails.innerHTML = `
                <div class="provider-logo provider-${providerType}">
                    <i class="${config.icon}"></i>
                </div>
                <div>
                    <h4>${config.name}</h4>
                    <p>${config.description}</p>
                    ${config.baseUrl ? `<small><strong>Base URL:</strong> ${config.baseUrl}</small>` : ''}
                </div>
            `;
            
            providerInfo.style.display = 'block';
            
            // Actualizar URL base si está definida
            if (config.baseUrl) {
                document.getElementById('api-base-url').value = config.baseUrl;
            }
            
            // Configurar campos específicos del proveedor
            this.setupProviderSpecificFields(providerType, config);
        } else {
            providerInfo.style.display = 'none';
        }
    }

    /**
     * Configurar campos específicos por proveedor
     */
    setupProviderSpecificFields(providerType, config) {
        const container = document.getElementById('provider-specific-fields');
        
        let fieldsHTML = '';
        
        // Campos específicos según el proveedor
        switch (providerType) {
            case 'booking':
                fieldsHTML = `
                    <div class="grid grid-cols-2 gap-4">
                        <div class="form-group">
                            <label class="form-label" for="partner-id">
                                <i class="fas fa-id-badge"></i>
                                Partner ID
                            </label>
                            <input type="text" id="partner-id" name="partner_id" class="form-control" 
                                   placeholder="Tu Partner ID de Booking.com">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="username">
                                <i class="fas fa-user"></i>
                                Username
                            </label>
                            <input type="text" id="username" name="username" class="form-control" 
                                   placeholder="Usuario de la API">
                        </div>
                    </div>
                `;
                break;
                
            case 'google':
                fieldsHTML = `
                    <div class="form-group">
                        <label class="form-label" for="oauth-token">
                            <i class="fab fa-google"></i>
                            OAuth Token
                        </label>
                        <textarea id="oauth-token" name="oauth_token" class="form-control" rows="3" 
                                  placeholder="Token OAuth 2.0 de Google"></textarea>
                        <div class="field-help">
                            <a href="https://developers.google.com/my-business" target="_blank">
                                <i class="fas fa-external-link-alt"></i>
                                Obtener credenciales de Google My Business
                            </a>
                        </div>
                    </div>
                `;
                break;
                
            case 'expedia':
                fieldsHTML = `
                    <div class="form-group">
                        <label class="form-label" for="shared-secret">
                            <i class="fas fa-fingerprint"></i>
                            Shared Secret
                        </label>
                        <input type="password" id="shared-secret" name="shared_secret" class="form-control" 
                               placeholder="Shared Secret de Expedia">
                    </div>
                `;
                break;
                
            case 'airbnb':
                fieldsHTML = `
                    <div class="form-group">
                        <label class="form-label" for="access-token">
                            <i class="fab fa-airbnb"></i>
                            Access Token
                        </label>
                        <textarea id="access-token" name="access_token" class="form-control" rows="2" 
                                  placeholder="Access Token de Airbnb"></textarea>
                    </div>
                `;
                break;
                
            case 'custom':
                fieldsHTML = `
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        <strong>API Personalizada:</strong> 
                        Configura los campos según la documentación de tu API.
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="custom-auth-method">
                            <i class="fas fa-shield-alt"></i>
                            Método de Autenticación
                        </label>
                        <select id="custom-auth-method" name="auth_method" class="form-control form-select">
                            <option value="api_key">API Key en Header</option>
                            <option value="bearer">Bearer Token</option>
                            <option value="basic">Basic Auth</option>
                            <option value="oauth">OAuth 2.0</option>
                            <option value="none">Sin autenticación</option>
                        </select>
                    </div>
                `;
                break;
        }
        
        container.innerHTML = fieldsHTML;
    }

    /**
     * Resetear formulario
     */
    resetForm() {
        const form = document.getElementById('api-form');
        if (form) {
            form.reset();
            
            // Limpiar mensajes de error
            document.querySelectorAll('.field-error').forEach(error => {
                error.textContent = '';
            });
            
            // Resetear pestañas
            this.switchFormTab('basic');
            
            // Limpiar campos específicos
            document.getElementById('provider-info').style.display = 'none';
            document.getElementById('provider-specific-fields').innerHTML = '';
            document.getElementById('custom-headers').innerHTML = this.getInitialHeaderRow();
            
            // Resetear contadores
            document.getElementById('description-count').textContent = '0';
        }
    }

    /**
     * Poblar formulario con datos existentes
     */
    populateForm(api) {
        // Información básica
        document.getElementById('api-name').value = api.name || '';
        document.getElementById('api-provider').value = api.provider_type || '';
        document.getElementById('api-description').value = api.description || '';
        document.getElementById('api-status').value = api.status || 'active';
        document.getElementById('api-priority').value = api.priority || 'normal';
        
        // Credenciales
        document.getElementById('api-base-url').value = api.base_url || '';
        document.getElementById('api-version').value = api.api_version || '';
        document.getElementById('api-key').value = api.api_key || '';
        document.getElementById('api-secret').value = api.api_secret || '';
        
        // Configuración avanzada
        document.getElementById('api-timeout').value = api.timeout || 30;
        document.getElementById('api-retry-attempts').value = api.retry_attempts || 3;
        document.getElementById('api-rate-limit').value = api.rate_limit || '';
        document.getElementById('api-cache-ttl').value = api.cache_ttl || 5;
        document.getElementById('api-notes').value = api.technical_notes || '';
        
        // Checkboxes
        document.getElementById('api-auto-retry').checked = api.auto_retry_enabled == 1;
        document.getElementById('api-ssl-verify').checked = api.ssl_verify_enabled != 0;
        document.getElementById('api-logging').checked = api.logging_enabled != 0;
        document.getElementById('api-monitoring').checked = api.monitoring_enabled != 0;
        
        // Actualizar campos del proveedor
        if (api.provider_type) {
            this.updateProviderFields(api.provider_type);
            
            // Poblar campos específicos del proveedor
            setTimeout(() => {
                this.populateProviderFields(api);
            }, 100);
        }
        
        // Headers personalizados
        if (api.custom_headers) {
            this.populateCustomHeaders(api.custom_headers);
        }
        
        // Actualizar contadores
        this.updateCharCount(document.getElementById('api-description'), 'description-count');
    }

    /**
     * Poblar campos específicos del proveedor
     */
    populateProviderFields(api) {
        // Booking.com
        if (document.getElementById('partner-id')) {
            document.getElementById('partner-id').value = api.partner_id || '';
        }
        if (document.getElementById('username')) {
            document.getElementById('username').value = api.username || '';
        }
        
        // Google
        if (document.getElementById('oauth-token')) {
            document.getElementById('oauth-token').value = api.oauth_token || '';
        }
        
        // Expedia
        if (document.getElementById('shared-secret')) {
            document.getElementById('shared-secret').value = api.shared_secret || '';
        }
        
        // Airbnb
        if (document.getElementById('access-token')) {
            document.getElementById('access-token').value = api.access_token || '';
        }
        
        // Custom
        if (document.getElementById('custom-auth-method')) {
            document.getElementById('custom-auth-method').value = api.auth_method || 'api_key';
        }
    }

    /**
     * Poblar headers personalizados
     */
    populateCustomHeaders(headers) {
        const container = document.getElementById('custom-headers');
        container.innerHTML = '';
        
        if (headers && typeof headers === 'object') {
            Object.entries(headers).forEach(([name, value]) => {
                this.addHeaderRow(name, value);
            });
        }
        
        // Agregar fila vacía al final
        this.addHeaderRow();
    }

    /**
     * Obtener fila inicial de header
     */
    getInitialHeaderRow() {
        return `
            <div class="header-row">
                <div class="grid grid-cols-5 gap-2 items-end">
                    <div class="col-span-2">
                        <input type="text" class="form-control form-control-sm" 
                               placeholder="Nombre del header" name="header_names[]">
                    </div>
                    <div class="col-span-2">
                        <input type="text" class="form-control form-control-sm" 
                               placeholder="Valor del header" name="header_values[]">
                    </div>
                    <div>
                        <button type="button" class="btn btn-success btn-sm" 
                                onclick="apisModule.addHeaderRow()">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>
                </div>
            </div>
        `;
    }

    /**
     * Agregar fila de header
     */
    addHeaderRow(name = '', value = '') {
        const container = document.getElementById('custom-headers');
        const headerRow = document.createElement('div');
        headerRow.className = 'header-row';
        
        const isLast = container.children.length === 0 || 
            container.lastElementChild.querySelector('input[name="header_names[]"]').value.trim() !== '';
        
        headerRow.innerHTML = `
            <div class="grid grid-cols-5 gap-2 items-end">
                <div class="col-span-2">
                    <input type="text" class="form-control form-control-sm" 
                           placeholder="Nombre del header" name="header_names[]" value="${name}"
                           ${isLast ? 'onblur="apisModule.checkAddNewHeaderRow()"' : ''}>
                </div>
                <div class="col-span-2">
                    <input type="text" class="form-control form-control-sm" 
                           placeholder="Valor del header" name="header_values[]" value="${value}">
                </div>
                <div>
                    <button type="button" class="btn ${isLast ? 'btn-success' : 'btn-danger'} btn-sm" 
                            onclick="apisModule.${isLast ? 'addHeaderRow' : 'removeHeaderRow'}(${isLast ? '' : 'this'})">
                        <i class="fas fa-${isLast ? 'plus' : 'minus'}"></i>
                    </button>
                </div>
            </div>
        `;
        
        container.appendChild(headerRow);
    }

    /**
     * Remover fila de header
     */
    removeHeaderRow(button) {
        const row = button.closest('.header-row');
        if (row) {
            row.remove();
        }
    }

    /**
     * Verificar si agregar nueva fila de header
     */
    checkAddNewHeaderRow() {
        const container = document.getElementById('custom-headers');
        const lastRow = container.lastElementChild;
        
        if (lastRow) {
            const nameInput = lastRow.querySelector('input[name="header_names[]"]');
            const valueInput = lastRow.querySelector('input[name="header_values[]"]');
            
            if (nameInput.value.trim() !== '' || valueInput.value.trim() !== '') {
                this.addHeaderRow();
            }
        }
    }

    /**
     * Validar campo individual
     */
    validateField(field) {
        const fieldId = field.id;
        const errorElement = document.getElementById(fieldId + '-error');
        let isValid = true;
        let errorMessage = '';
        
        switch (fieldId) {
            case 'api-name':
                if (field.value.trim().length < 3) {
                    isValid = false;
                    errorMessage = 'El nombre debe tener al menos 3 caracteres';
                }
                break;
                
            case 'api-base-url':
                if (field.value.trim() && !this.isValidUrl(field.value)) {
                    isValid = false;
                    errorMessage = 'La URL no es válida';
                }
                break;
        }
        
        if (errorElement) {
            errorElement.textContent = errorMessage;
            field.classList.toggle('error', !isValid);
        }
        
        return isValid;
    }

    /**
     * Validar URL
     */
    isValidUrl(string) {
        try {
            new URL(string);
            return true;
        } catch (_) {
            return false;
        }
    }

    /**
     * Actualizar contador de caracteres
     */
    updateCharCount(textarea, counterId) {
        const counter = document.getElementById(counterId);
        if (counter) {
            counter.textContent = textarea.value.length;
        }
    }

    /**
     * Toggle visibilidad de contraseña
     */
    togglePasswordVisibility(fieldId) {
        const field = document.getElementById(fieldId);
        const button = field.nextElementSibling;
        const icon = button.querySelector('i');
        
        if (field.type === 'password') {
            field.type = 'text';
            icon.className = 'fas fa-eye-slash';
        } else {
            field.type = 'password';
            icon.className = 'fas fa-eye';
        }
    }

    /**
     * Guardar API
     */
    async saveApi(event) {
        if (event) event.preventDefault();
        
        try {
            console.log('[ApisModule] Guardando API...');
            
            // Validar formulario
            if (!this.validateForm()) {
                NotificationManager.error('Por favor, corrige los errores en el formulario');
                return;
            }
            
            // Recopilar datos del formulario
            const formData = this.collectFormData();
            
            // Determinar endpoint
            const endpoint = this.selectedApi ? 'updateExternalApi' : 'createExternalApi';
            if (this.selectedApi) {
                formData.id = this.selectedApi.id;
            }
            
            // Enviar datos
            const response = await AdminAPI.request(endpoint, formData);
            
            if (response.success) {
                const action = this.selectedApi ? 'actualizada' : 'creada';
                NotificationManager.success(`API ${action} correctamente`);
                this.closeModal();
                this.refreshList();
            } else {
                throw new Error(response.message || 'Error al guardar API');
            }
            
        } catch (error) {
            console.error('[ApisModule] Error al guardar:', error);
            NotificationManager.error('Error al guardar API: ' + error.message);
        }
    }

    /**
     * Guardar y probar API
     */
    async saveAndTest() {
        try {
            // Primero guardar
            await this.saveApi();
            
            // Esperar un momento y luego probar
            setTimeout(() => {
                if (this.selectedApi?.id) {
                    this.testConnection(this.selectedApi.id);
                }
            }, 1000);
            
        } catch (error) {
            console.error('[ApisModule] Error en guardar y probar:', error);
        }
    }

    /**
     * Validar formulario completo
     */
    validateForm() {
        let isValid = true;
        
        // Validar campos requeridos
        const requiredFields = ['api-name', 'api-provider'];
        requiredFields.forEach(fieldId => {
            const field = document.getElementById(fieldId);
            if (field && !field.value.trim()) {
                isValid = false;
                this.showFieldError(fieldId, 'Este campo es requerido');
            }
        });
        
        // Validar campos individuales
        const validatableFields = document.querySelectorAll('[oninput*="validateField"]');
        validatableFields.forEach(field => {
            if (!this.validateField(field)) {
                isValid = false;
            }
        });
        
        return isValid;
    }

    /**
     * Mostrar error en campo
     */
    showFieldError(fieldId, message) {
        const field = document.getElementById(fieldId);
        const errorElement = document.getElementById(fieldId + '-error');
        
        if (field) field.classList.add('error');
        if (errorElement) errorElement.textContent = message;
    }

    /**
     * Recopilar datos del formulario
     */
    collectFormData() {
        const form = document.getElementById('api-form');
        const formData = new FormData(form);
        
        // Convertir a objeto
        const data = {};
        formData.forEach((value, key) => {
            data[key] = value;
        });
        
        // Procesar arrays (headers)
        data.custom_headers = this.collectCustomHeaders();
        
        // Procesar checkboxes
        data.auto_retry_enabled = document.getElementById('api-auto-retry').checked ? 1 : 0;
        data.ssl_verify_enabled = document.getElementById('api-ssl-verify').checked ? 1 : 0;
        data.logging_enabled = document.getElementById('api-logging').checked ? 1 : 0;
        data.monitoring_enabled = document.getElementById('api-monitoring').checked ? 1 : 0;
        
        return data;
    }

    /**
     * Recopilar headers personalizados
     */
    collectCustomHeaders() {
        const headers = {};
        const container = document.getElementById('custom-headers');
        
        container.querySelectorAll('.header-row').forEach(row => {
            const nameInput = row.querySelector('input[name="header_names[]"]');
            const valueInput = row.querySelector('input[name="header_values[]"]');
            
            if (nameInput.value.trim() && valueInput.value.trim()) {
                headers[nameInput.value.trim()] = valueInput.value.trim();
            }
        });
        
        return Object.keys(headers).length > 0 ? headers : null;
    }

    // ==========================================================================
    // MODAL DE DETALLES
    // ==========================================================================

    /**
     * Mostrar modal de detalles
     */
    showDetailsModal(api) {
        const modal = document.getElementById('api-details-modal');
        const content = document.getElementById('api-details-content');
        
        if (!modal || !content) return;
        
        content.innerHTML = this.generateDetailsHTML(api);
        
        modal.classList.add('show');
        document.body.style.overflow = 'hidden';
        
        this.selectedApi = api;
    }

    /**
     * Cerrar modal de detalles
     */
    closeDetailsModal() {
        const modal = document.getElementById('api-details-modal');
        if (modal) {
            modal.classList.remove('show');
            document.body.style.overflow = '';
        }
    }

    /**
     * Generar HTML de detalles
     */
    generateDetailsHTML(api) {
        const providerConfig = this.providerConfig[api.provider_type] || this.providerConfig.custom;
        
        return `
            <div class="api-details">
                <div class="api-details-header">
                    <div class="flex items-center gap-3">
                        <div class="provider-logo provider-${api.provider_type}">
                            <i class="${providerConfig.icon}"></i>
                        </div>
                        <div>
                            <h3>${AdminUI.escapeHtml(api.name)}</h3>
                            <p class="text-gray">${providerConfig.name}</p>
                        </div>
                    </div>
                    <div class="api-details-badges">
                        ${this.generateStatusBadge(api)}
                        ${this.generateConnectionBadge(api)}
                    </div>
                </div>
                
                <div class="api-details-grid">
                    <div class="api-details-section">
                        <h4><i class="fas fa-info-circle"></i> Información General</h4>
                        <dl>
                            <dt>ID:</dt>
                            <dd>#${api.id}</dd>
                            <dt>Proveedor:</dt>
                            <dd>${providerConfig.name}</dd>
                            <dt>Estado:</dt>
                            <dd>${this.getStatusText(api.status)}</dd>
                            <dt>Prioridad:</dt>
                            <dd>${this.getPriorityText(api.priority)}</dd>
                            ${api.description ? `<dt>Descripción:</dt><dd>${AdminUI.escapeHtml(api.description)}</dd>` : ''}
                        </dl>
                    </div>
                    
                    <div class="api-details-section">
                        <h4><i class="fas fa-cog"></i> Configuración</h4>
                        <dl>
                            ${api.base_url ? `<dt>URL Base:</dt><dd><code>${AdminUI.escapeHtml(api.base_url)}</code></dd>` : ''}
                            ${api.api_version ? `<dt>Versión:</dt><dd>${AdminUI.escapeHtml(api.api_version)}</dd>` : ''}
                            <dt>Timeout:</dt>
                            <dd>${api.timeout || 30} segundos</dd>
                            <dt>Reintentos:</dt>
                            <dd>${api.retry_attempts || 3}</dd>
                            ${api.rate_limit ? `<dt>Rate Limit:</dt><dd>${api.rate_limit} req/min</dd>` : ''}
                            <dt>Cache TTL:</dt>
                            <dd>${api.cache_ttl || 0} minutos</dd>
                        </dl>
                    </div>
                    
                    <div class="api-details-section">
                        <h4><i class="fas fa-shield-alt"></i> Seguridad</h4>
                        <dl>
                            <dt>API Key:</dt>
                            <dd>${api.api_key ? '••••••••••••' : 'No configurada'}</dd>
                            <dt>SSL Verify:</dt>
                            <dd>${api.ssl_verify_enabled != 0 ? 'Habilitado' : 'Deshabilitado'}</dd>
                            <dt>Auto Retry:</dt>
                            <dd>${api.auto_retry_enabled == 1 ? 'Habilitado' : 'Deshabilitado'}</dd>
                        </dl>
                    </div>
                    
                    <div class="api-details-section">
                        <h4><i class="fas fa-chart-bar"></i> Estadísticas</h4>
                        <dl>
                            <dt>Creada:</dt>
                            <dd>${this.formatDate(api.created_at)}</dd>
                            <dt>Actualizada:</dt>
                            <dd>${this.formatDate(api.updated_at)}</dd>
                            <dt>Última Prueba:</dt>
                            <dd>${this.formatDate(api.last_test)}</dd>
                            <dt>Estado de Conexión:</dt>
                            <dd>${this.getConnectionStatusText(api.connection_status)}</dd>
                        </dl>
                    </div>
                </div>
                
                ${api.technical_notes ? `
                    <div class="api-details-section">
                        <h4><i class="fas fa-sticky-note"></i> Notas Técnicas</h4>
                        <p>${AdminUI.escapeHtml(api.technical_notes).replace(/\n/g, '<br>')}</p>
                    </div>
                ` : ''}
                
                ${api.custom_headers && Object.keys(api.custom_headers).length > 0 ? `
                    <div class="api-details-section">
                        <h4><i class="fas fa-list"></i> Headers Personalizados</h4>
                        <dl>
                            ${Object.entries(api.custom_headers).map(([name, value]) => 
                                `<dt>${AdminUI.escapeHtml(name)}:</dt><dd><code>${AdminUI.escapeHtml(value)}</code></dd>`
                            ).join('')}
                        </dl>
                    </div>
                ` : ''}
            </div>
        `;
    }

    /**
     * Obtener texto de estado
     */
    getStatusText(status) {
        const texts = {
            active: 'Activa',
            inactive: 'Inactiva',
            testing: 'En Pruebas',
            error: 'Con Errores'
        };
        return texts[status] || 'Desconocido';
    }

    /**
     * Obtener texto de prioridad
     */
    getPriorityText(priority) {
        const texts = {
            normal: 'Normal',
            high: 'Alta',
            critical: 'Crítica'
        };
        return texts[priority] || 'Normal';
    }

    /**
     * Obtener texto de estado de conexión
     */
    getConnectionStatusText(status) {
        const texts = {
            success: 'Conexión exitosa',
            error: 'Error de conexión',
            testing: 'Probando conexión...',
            unknown: 'No probada'
        };
        return texts[status] || 'Desconocido';
    }

    /**
     * Probar desde detalles
     */
    testFromDetails() {
        if (this.selectedApi?.id) {
            this.testConnection(this.selectedApi.id);
        }
    }

    /**
     * Editar desde detalles
     */
    editFromDetails() {
        this.closeDetailsModal();
        if (this.selectedApi?.id) {
            this.editApi(this.selectedApi.id);
        }
    }

    // ==========================================================================
    // PRUEBAS EN MODAL
    // ==========================================================================

    /**
     * Probar conexión en modal
     */
    async testConnectionInModal() {
        const testResults = document.getElementById('test-results');
        
        try {
            console.log('[ApisModule] Probando conexión en modal...');
            
            // Mostrar estado de carga
            testResults.innerHTML = `
                <div class="test-result">
                    <div class="test-result-header">
                        <div class="test-result-title">
                            <i class="fas fa-spinner fa-spin"></i>
                            Probando Conexión
                        </div>
                        <div class="test-result-time">${new Date().toLocaleTimeString()}</div>
                    </div>
                    <div class="test-result-details">
                        Verificando conectividad con la API...
                    </div>
                </div>
            `;
            
            // Recopilar datos del formulario para la prueba
            const testData = this.collectFormData();
            
            const response = await AdminAPI.request('testApiConnection', testData);
            
            const resultClass = response.success ? 'success' : 'error';
            const resultIcon = response.success ? 'fa-check-circle' : 'fa-exclamation-triangle';
            const resultTitle = response.success ? 'Conexión Exitosa' : 'Error de Conexión';
            
            testResults.innerHTML = `
                <div class="test-result ${resultClass}">
                    <div class="test-result-header">
                        <div class="test-result-title">
                            <i class="fas ${resultIcon}"></i>
                            ${resultTitle}
                        </div>
                        <div class="test-result-time">${new Date().toLocaleTimeString()}</div>
                    </div>
                    <div class="test-result-details">
                        ${AdminUI.escapeHtml(response.message || 'Prueba completada')}
                        ${response.data ? `<pre>${JSON.stringify(response.data, null, 2)}</pre>` : ''}
                    </div>
                </div>
            `;
            
            // Agregar al historial
            this.addToTestHistory('connection', response.success, response.message);
            
        } catch (error) {
            console.error('[ApisModule] Error en test de conexión:', error);
            
            testResults.innerHTML = `
                <div class="test-result error">
                    <div class="test-result-header">
                        <div class="test-result-title">
                            <i class="fas fa-exclamation-triangle"></i>
                            Error de Prueba
                        </div>
                        <div class="test-result-time">${new Date().toLocaleTimeString()}</div>
                    </div>
                    <div class="test-result-details">
                        ${AdminUI.escapeHtml(error.message)}
                    </div>
                </div>
            `;
            
            this.addToTestHistory('connection', false, error.message);
        }
    }

    /**
     * Probar autenticación
     */
    async testAuthentication() {
        const testResults = document.getElementById('test-results');
        
        try {
            console.log('[ApisModule] Probando autenticación...');
            
            testResults.innerHTML = `
                <div class="test-result">
                    <div class="test-result-header">
                        <div class="test-result-title">
                            <i class="fas fa-spinner fa-spin"></i>
                            Probando Autenticación
                        </div>
                        <div class="test-result-time">${new Date().toLocaleTimeString()}</div>
                    </div>
                    <div class="test-result-details">
                        Verificando credenciales de acceso...
                    </div>
                </div>
            `;
            
            const testData = this.collectFormData();
            
            const response = await AdminAPI.request('testApiAuthentication', testData);
            
            const resultClass = response.success ? 'success' : 'error';
            const resultIcon = response.success ? 'fa-shield-alt' : 'fa-shield-alt';
            const resultTitle = response.success ? 'Autenticación Válida' : 'Error de Autenticación';
            
            testResults.innerHTML = `
                <div class="test-result ${resultClass}">
                    <div class="test-result-header">
                        <div class="test-result-title">
                            <i class="fas ${resultIcon}"></i>
                            ${resultTitle}
                        </div>
                        <div class="test-result-time">${new Date().toLocaleTimeString()}</div>
                    </div>
                    <div class="test-result-details">
                        ${AdminUI.escapeHtml(response.message || 'Prueba de autenticación completada')}
                    </div>
                </div>
            `;
            
            this.addToTestHistory('auth', response.success, response.message);
            
        } catch (error) {
            console.error('[ApisModule] Error en test de autenticación:', error);
            
            testResults.innerHTML = `
                <div class="test-result error">
                    <div class="test-result-header">
                        <div class="test-result-title">
                            <i class="fas fa-shield-alt"></i>
                            Error de Prueba
                        </div>
                        <div class="test-result-time">${new Date().toLocaleTimeString()}</div>
                    </div>
                    <div class="test-result-details">
                        ${AdminUI.escapeHtml(error.message)}
                    </div>
                </div>
            `;
            
            this.addToTestHistory('auth', false, error.message);
        }
    }

    /**
     * Probar request de muestra
     */
    async testSampleRequest() {
        const testResults = document.getElementById('test-results');
        
        try {
            console.log('[ApisModule] Ejecutando prueba de muestra...');
            
            testResults.innerHTML = `
                <div class="test-result">
                    <div class="test-result-header">
                        <div class="test-result-title">
                            <i class="fas fa-spinner fa-spin"></i>
                            Ejecutando Prueba de Muestra
                        </div>
                        <div class="test-result-time">${new Date().toLocaleTimeString()}</div>
                    </div>
                    <div class="test-result-details">
                        Ejecutando request de ejemplo...
                    </div>
                </div>
            `;
            
            const testData = this.collectFormData();
            
            const response = await AdminAPI.request('testApiSampleRequest', testData);
            
            const resultClass = response.success ? 'success' : 'error';
            const resultIcon = response.success ? 'fa-code' : 'fa-exclamation-triangle';
            const resultTitle = response.success ? 'Prueba Exitosa' : 'Error en Prueba';
            
            testResults.innerHTML = `
                <div class="test-result ${resultClass}">
                    <div class="test-result-header">
                        <div class="test-result-title">
                            <i class="fas ${resultIcon}"></i>
                            ${resultTitle}
                        </div>
                        <div class="test-result-time">${new Date().toLocaleTimeString()}</div>
                    </div>
                    <div class="test-result-details">
                        ${AdminUI.escapeHtml(response.message || 'Prueba de muestra completada')}
                        ${response.data ? `<pre>${JSON.stringify(response.data, null, 2)}</pre>` : ''}
                    </div>
                </div>
            `;
            
            this.addToTestHistory('sample', response.success, response.message);
            
        } catch (error) {
            console.error('[ApisModule] Error en test de muestra:', error);
            
            testResults.innerHTML = `
                <div class="test-result error">
                    <div class="test-result-header">
                        <div class="test-result-title">
                            <i class="fas fa-code"></i>
                            Error de Prueba
                        </div>
                        <div class="test-result-time">${new Date().toLocaleTimeString()}</div>
                    </div>
                    <div class="test-result-details">
                        ${AdminUI.escapeHtml(error.message)}
                    </div>
                </div>
            `;
            
            this.addToTestHistory('sample', false, error.message);
        }
    }

    /**
     * Agregar al historial de pruebas
     */
    addToTestHistory(type, success, message) {
        const history = document.getElementById('test-history');
        if (!history) return;
        
        // Remover placeholder si existe
        const placeholder = history.querySelector('.text-center');
        if (placeholder) {
            placeholder.remove();
        }
        
        const typeNames = {
            connection: 'Conexión',
            auth: 'Autenticación',
            sample: 'Muestra'
        };
        
        const item = document.createElement('div');
        item.className = 'test-history-item';
        item.innerHTML = `
            <div>
                <span class="test-history-type">${typeNames[type] || 'Prueba'}:</span>
                <span class="test-history-message">${AdminUI.escapeHtml(message || 'Sin mensaje')}</span>
            </div>
            <div class="test-history-status ${success ? 'success' : 'error'}">
                ${success ? 'OK' : 'ERROR'}
            </div>
        `;
        
        // Insertar al principio
        history.insertBefore(item, history.firstChild);
        
        // Mantener solo las últimas 5 pruebas
        while (history.children.length > 5) {
            history.removeChild(history.lastChild);
        }
    }

    // ==========================================================================
    // UTILIDADES
    // ==========================================================================

    /**
     * Formatear fecha
     */
    formatDate(dateString) {
        if (!dateString) return 'Nunca';
        
        try {
            const date = new Date(dateString);
            const now = new Date();
            const diffHours = Math.floor((now - date) / (1000 * 60 * 60));
            
            if (diffHours < 1) {
                return 'Hace menos de 1 hora';
            } else if (diffHours < 24) {
                return `Hace ${diffHours} hora${diffHours !== 1 ? 's' : ''}`;
            } else if (diffHours < 48) {
                return 'Ayer';
            } else {
                return date.toLocaleDateString('es-ES', {
                    year: 'numeric',
                    month: 'short',
                    day: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                });
            }
        } catch (error) {
            return 'Fecha inválida';
        }
    }

    /**
     * Mostrar estado de carga
     */
    showLoading() {
        const container = document.getElementById('apis-list-container');
        if (container) {
            container.innerHTML = `
                <div class="loading-state">
                    <i class="fas fa-spinner fa-spin spinner"></i>
                    <h3>Cargando APIs...</h3>
                    <p>Por favor espera mientras cargamos la información</p>
                </div>
            `;
        }
    }

    /**
     * Mostrar estado vacío
     */
    showEmpty() {
        const container = document.getElementById('apis-list-container');
        const template = document.getElementById('apis-empty-template');
        
        if (!container || !template) return;
        
        let emptyMessage = 'Comienza agregando tu primera API para conectar con proveedores externos.';
        
        if (this.currentFilter.search) {
            emptyMessage = `No se encontraron APIs que coincidan con "${this.currentFilter.search}".`;
        } else if (this.currentFilter.type || this.currentFilter.status) {
            emptyMessage = 'No hay APIs que coincidan con los filtros aplicados.';
        }
        
        let html = template.innerHTML;
        html = html.replace(/{empty_message}/g, emptyMessage);
        
        container.innerHTML = html;
        
        // Ocultar paginación
        const pagination = document.getElementById('apis-pagination');
        if (pagination) {
            pagination.style.display = 'none';
        }
    }

    /**
     * Mostrar estado de error
     */
    showError(message) {
        const container = document.getElementById('apis-list-container');
        const template = document.getElementById('apis-error-template');
        
        if (!container || !template) return;
        
        let html = template.innerHTML;
        html = html.replace(/{error_message}/g, message);
        
        container.innerHTML = html;
        
        // Ocultar paginación
        const pagination = document.getElementById('apis-pagination');
        if (pagination) {
            pagination.style.display = 'none';
        }
    }
}

// Crear instancia global del módulo
let apisModule;

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', () => {
    apisModule = new ApisModule();
});

// Exportar para uso global
window.ApisModule = ApisModule;