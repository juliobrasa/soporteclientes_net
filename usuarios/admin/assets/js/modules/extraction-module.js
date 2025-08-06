/**
 * ==========================================================================
 * MÓDULO EXTRACTOR
 * Kavia Hoteles Panel de Administración
 * Sistema completo de extracción de reseñas con wizard y monitoreo
 * ==========================================================================
 */

class ExtractorModule {
    constructor() {
        // Estado del módulo
        this.jobs = [];
        this.filteredJobs = [];
        this.currentPage = 1;
        this.pageSize = 25;
        this.totalPages = 1;
        this.currentFilter = {
            search: '',
            status: '',
            period: 'month'
        };
        this.sortField = 'id';
        this.sortDirection = 'desc';
        this.isLoading = false;
        
        // Estado del wizard
        this.currentStep = 1;
        this.selectedProvider = null;
        this.wizardData = {};
        this.availableProviders = [];
        this.availableHotels = [];
        this.selectedHotels = [];
        
        // Estado del monitor
        this.isMonitoring = false;
        this.autoRefreshInterval = null;
        this.monitorJobs = [];
        this.showLogs = false;
        
        // Configuración
        this.config = {
            autoRefreshInterval: 5000, // 5 segundos
            maxLogEntries: 100,
            costPerRequest: 0.001, // €0.001 por request
            averageRequestsPerHotel: 10
        };
        
        this.init();
    }

    /**
     * Inicialización del módulo
     */
    init() {
        console.log('[ExtractorModule] Inicializando módulo de extractor...');
        
        // Configurar event listeners
        this.setupEventListeners();
        
        // Cargar datos iniciales
        this.loadSystemStatus();
        this.loadExtractionJobs();
        
        console.log('[ExtractorModule] Módulo inicializado correctamente');
    }

    /**
     * Configurar event listeners
     */
    setupEventListeners() {
        // Búsqueda en tiempo real
        const searchInput = document.getElementById('jobs-search');
        if (searchInput) {
            searchInput.addEventListener('input', AdminUI.debounce((e) => {
                this.filterJobs(e.target.value);
            }, 300));
        }
        
        // Filtros
        document.getElementById('jobs-status-filter')?.addEventListener('change', (e) => {
            this.filterByStatus(e.target.value);
        });
        
        document.getElementById('jobs-period-filter')?.addEventListener('change', (e) => {
            this.filterByPeriod(e.target.value);
        });
        
        document.getElementById('jobs-per-page')?.addEventListener('change', (e) => {
            this.changePageSize(parseInt(e.target.value));
        });

        // Wizard event listeners
        this.setupWizardEventListeners();
        
        // Monitor event listeners
        this.setupMonitorEventListeners();

        // Keyboard shortcuts
        document.addEventListener('keydown', (e) => {
            if (e.ctrlKey && e.key === 'e') {
                e.preventDefault();
                this.startExtractionWizard();
            }
            if (e.key === 'Escape') {
                this.closeWizard();
                this.closeJobsMonitor();
                this.closeJobsQueue();
            }
        });
    }

    /**
     * Configurar event listeners del wizard
     */
    setupWizardEventListeners() {
        // Radio buttons para modo de hotel
        document.querySelectorAll('input[name="hotel_mode"]').forEach(radio => {
            radio.addEventListener('change', (e) => {
                this.updateHotelMode(e.target.value);
            });
        });
        
        // Radio buttons para modo de ejecución
        document.querySelectorAll('input[name="execution_mode"]').forEach(radio => {
            radio.addEventListener('change', (e) => {
                this.updateExecutionMode(e.target.value);
            });
        });
        
        // Campos que afectan estimaciones
        ['max-reviews-per-hotel', 'extraction-priority'].forEach(id => {
            document.getElementById(id)?.addEventListener('change', () => {
                this.updateCostEstimation();
            });
        });
    }

    /**
     * Configurar event listeners del monitor
     */
    setupMonitorEventListeners() {
        // Auto-refresh toggle
        document.getElementById('auto-refresh')?.addEventListener('change', (e) => {
            this.toggleAutoRefresh(e.target.checked);
        });
        
        // Show logs toggle
        document.getElementById('show-logs')?.addEventListener('change', (e) => {
            this.toggleShowLogs(e.target.checked);
        });
    }

    // ==========================================================================
    // GESTIÓN DE TRABAJOS DE EXTRACCIÓN
    // ==========================================================================

    /**
     * Cargar estado del sistema
     */
    async loadSystemStatus() {
        try {
            const response = await AdminAPI.request('getExtractionSystemStatus');
            
            if (response.success) {
                const status = response.data;
                
                document.getElementById('apis-count').textContent = status.apis_configured || 0;
                document.getElementById('active-hotels-count').textContent = status.active_hotels || 0;
                document.getElementById('reviews-extracted-30d').textContent = 
                    this.formatNumber(status.reviews_30d || 0);
                document.getElementById('last-extraction').textContent = 
                    this.formatDate(status.last_extraction) || 'Nunca';
            }
        } catch (error) {
            console.error('[ExtractorModule] Error cargando estado del sistema:', error);
        }
    }

    /**
     * Cargar trabajos de extracción
     */
    async loadExtractionJobs() {
        if (this.isLoading) return;
        
        this.isLoading = true;
        this.showLoading();
        
        try {
            console.log('[ExtractorModule] Cargando trabajos de extracción...');
            
            const response = await AdminAPI.request('getExtractionJobs', {
                page: this.currentPage,
                limit: this.pageSize,
                search: this.currentFilter.search,
                status: this.currentFilter.status,
                period: this.currentFilter.period,
                sort_field: this.sortField,
                sort_direction: this.sortDirection
            });
            
            if (response.success) {
                this.jobs = response.data.jobs || [];
                this.filteredJobs = [...this.jobs];
                this.totalPages = response.data.total_pages || 1;
                
                this.renderJobsList();
                this.updateStats(response.data.stats);
                this.updatePagination(response.data);
                
                console.log(`[ExtractorModule] Cargados ${this.jobs.length} trabajos`);
            } else {
                throw new Error(response.message || 'Error al cargar trabajos');
            }
            
        } catch (error) {
            console.error('[ExtractorModule] Error cargando trabajos:', error);
            this.showError('Error al cargar trabajos de extracción: ' + error.message);
        } finally {
            this.isLoading = false;
        }
    }

    /**
     * Renderizar lista de trabajos
     */
    renderJobsList() {
        const container = document.getElementById('extraction-jobs-container');
        if (!container) return;

        if (this.jobs.length === 0) {
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
        const container = document.getElementById('extraction-jobs-container');
        const template = document.getElementById('jobs-table-template');
        
        if (!template) return;
        
        const clone = template.content.cloneNode(true);
        const tbody = clone.getElementById('jobs-table-body');
        
        tbody.innerHTML = this.jobs.map(job => this.generateJobRowHTML(job)).join('');
        
        container.innerHTML = '';
        container.appendChild(clone);
        
        this.updateSortIcons();
    }

    /**
     * Renderizar cards para móvil
     */
    renderMobileCards() {
        const container = document.getElementById('extraction-jobs-container');
        const template = document.getElementById('jobs-mobile-template');
        
        if (!template) return;
        
        const clone = template.content.cloneNode(true);
        const cardsContainer = clone.querySelector('.data-cards');
        
        cardsContainer.innerHTML = this.jobs.map(job => this.generateJobCardHTML(job)).join('');
        
        container.innerHTML = '';
        container.appendChild(clone);
    }

    /**
     * Generar HTML para fila de trabajo
     */
    generateJobRowHTML(job) {
        const template = document.getElementById('job-row-template');
        if (!template) return '';

        let html = template.innerHTML;
        
        // Reemplazos básicos
        html = html.replace(/{id}/g, job.id);
        html = html.replace(/{name}/g, AdminUI.escapeHtml(job.name));
        
        // API provider info
        html = html.replace(/{api_icon}/g, this.getProviderIcon(job.api_provider_type));
        html = html.replace(/{api_provider_name}/g, job.api_provider_name || 'Sin asignar');
        
        // Status
        html = html.replace(/{status_class}/g, this.getJobStatusClass(job));
        html = html.replace(/{status_badge}/g, this.generateStatusBadge(job));
        
        // Mode badge
        html = html.replace(/{mode_badge}/g, this.generateModeBadge(job.mode));
        
        // Progress
        html = html.replace(/{progress_bar}/g, this.generateProgressBar(job));
        
        // Metrics
        html = html.replace(/{hotel_count}/g, job.hotel_count || 0);
        html = html.replace(/{reviews_extracted}/g, this.formatNumber(job.reviews_extracted || 0));
        html = html.replace(/{reviews_target}/g, 
            job.estimated_reviews ? `/ ${this.formatNumber(job.estimated_reviews)}` : '');
        html = html.replace(/{total_cost}/g, this.formatCurrency(job.total_cost || 0));
        
        // Dates
        html = html.replace(/{created_at_formatted}/g, this.formatDate(job.created_at));
        
        // Action buttons
        html = html.replace(/{action_buttons}/g, this.generateActionButtons(job));
        
        return html;
    }

    /**
     * Generar HTML para card de trabajo
     */
    generateJobCardHTML(job) {
        const template = document.getElementById('job-card-template');
        if (!template) return '';

        let html = template.innerHTML;
        
        // Reemplazos similares a la tabla
        html = html.replace(/{id}/g, job.id);
        html = html.replace(/{name}/g, AdminUI.escapeHtml(job.name));
        html = html.replace(/{api_icon}/g, this.getProviderIcon(job.api_provider_type));
        html = html.replace(/{api_provider_name}/g, job.api_provider_name || 'Sin asignar');
        html = html.replace(/{status_badge}/g, this.generateStatusBadge(job));
        html = html.replace(/{mode_badge}/g, this.generateModeBadge(job.mode));
        html = html.replace(/{progress_bar}/g, this.generateProgressBar(job));
        html = html.replace(/{hotel_count}/g, job.hotel_count || 0);
        html = html.replace(/{reviews_extracted}/g, this.formatNumber(job.reviews_extracted || 0));
        html = html.replace(/{reviews_target}/g, 
            job.estimated_reviews ? `/ ${this.formatNumber(job.estimated_reviews)}` : '');
        html = html.replace(/{total_cost}/g, this.formatCurrency(job.total_cost || 0));
        html = html.replace(/{created_at_formatted}/g, this.formatDate(job.created_at));
        html = html.replace(/{mobile_action_buttons}/g, this.generateMobileActionButtons(job));
        
        return html;
    }

    /**
     * Generar badge de estado
     */
    generateStatusBadge(job) {
        const statusConfig = {
            pending: { text: 'Pendiente', class: 'secondary', icon: 'clock' },
            running: { text: 'En Proceso', class: 'info', icon: 'play' },
            completed: { text: 'Completado', class: 'success', icon: 'check' },
            failed: { text: 'Fallido', class: 'danger', icon: 'exclamation-triangle' },
            cancelled: { text: 'Cancelado', class: 'warning', icon: 'ban' }
        };
        
        const config = statusConfig[job.status] || statusConfig.pending;
        
        return `<span class="badge badge-${config.class}">
            <i class="fas fa-${config.icon}"></i>
            ${config.text}
        </span>`;
    }

    /**
     * Generar badge de modo
     */
    generateModeBadge(mode) {
        const modeConfig = {
            active: { text: 'Activos', class: 'mode-active' },
            all: { text: 'Todos', class: 'mode-all' },
            selected: { text: 'Seleccionados', class: 'mode-selected' }
        };
        
        const config = modeConfig[mode] || modeConfig.active;
        
        return `<span class="mode-badge ${config.class}">
            ${config.text}
        </span>`;
    }

    /**
     * Generar barra de progreso
     */
    generateProgressBar(job) {
        const progress = job.progress || 0;
        const progressClass = job.status === 'running' ? 'running' : 
                            job.status === 'failed' ? 'failed' : 
                            job.status === 'completed' ? 'completed' : '';
        
        return `
            <div class="progress-bar">
                <div class="progress-fill ${progressClass}" style="width: ${progress}%"></div>
            </div>
            <div class="progress-text">${progress}%</div>
        `;
    }

    /**
     * Generar botones de acción
     */
    generateActionButtons(job) {
        let buttons = [];
        
        switch (job.status) {
            case 'pending':
                buttons.push(`
                    <button class="btn btn-xs btn-primary tooltip" 
                            onclick="extractorModule.startJob(${job.id})"
                            data-tooltip="Iniciar trabajo">
                        <i class="fas fa-play"></i>
                    </button>
                `);
                buttons.push(`
                    <button class="btn btn-xs btn-warning tooltip" 
                            onclick="extractorModule.editJob(${job.id})"
                            data-tooltip="Editar configuración">
                        <i class="fas fa-edit"></i>
                    </button>
                `);
                break;
                
            case 'running':
                buttons.push(`
                    <button class="btn btn-xs btn-info tooltip" 
                            onclick="extractorModule.monitorJob(${job.id})"
                            data-tooltip="Ver progreso">
                        <i class="fas fa-eye"></i>
                    </button>
                `);
                buttons.push(`
                    <button class="btn btn-xs btn-warning tooltip" 
                            onclick="extractorModule.pauseJob(${job.id})"
                            data-tooltip="Pausar trabajo">
                        <i class="fas fa-pause"></i>
                    </button>
                `);
                buttons.push(`
                    <button class="btn btn-xs btn-danger tooltip" 
                            onclick="extractorModule.cancelJob(${job.id})"
                            data-tooltip="Cancelar trabajo">
                        <i class="fas fa-stop"></i>
                    </button>
                `);
                break;
                
            case 'completed':
                buttons.push(`
                    <button class="btn btn-xs btn-info tooltip" 
                            onclick="extractorModule.viewJobResults(${job.id})"
                            data-tooltip="Ver resultados">
                        <i class="fas fa-chart-bar"></i>
                    </button>
                `);
                buttons.push(`
                    <button class="btn btn-xs btn-success tooltip" 
                            onclick="extractorModule.downloadResults(${job.id})"
                            data-tooltip="Descargar datos">
                        <i class="fas fa-download"></i>
                    </button>
                `);
                break;
                
            case 'failed':
                buttons.push(`
                    <button class="btn btn-xs btn-warning tooltip" 
                            onclick="extractorModule.retryJob(${job.id})"
                            data-tooltip="Reintentar trabajo">
                        <i class="fas fa-redo"></i>
                    </button>
                `);
                buttons.push(`
                    <button class="btn btn-xs btn-info tooltip" 
                            onclick="extractorModule.viewJobLogs(${job.id})"
                            data-tooltip="Ver logs de error">
                        <i class="fas fa-file-alt"></i>
                    </button>
                `);
                break;
        }
        
        // Botón de eliminar (siempre disponible excepto en running)
        if (job.status !== 'running') {
            buttons.push(`
                <button class="btn btn-xs btn-danger tooltip" 
                        onclick="extractorModule.deleteJob(${job.id})"
                        data-tooltip="Eliminar trabajo">
                    <i class="fas fa-trash"></i>
                </button>
            `);
        }
        
        return buttons.join('');
    }

    /**
     * Generar botones de acción para móvil
     */
    generateMobileActionButtons(job) {
        let buttons = [];
        
        switch (job.status) {
            case 'pending':
                buttons.push(`
                    <button class="btn btn-xs btn-primary" onclick="extractorModule.startJob(${job.id})">
                        <i class="fas fa-play"></i>
                    </button>
                `);
                break;
                
            case 'running':
                buttons.push(`
                    <button class="btn btn-xs btn-info" onclick="extractorModule.monitorJob(${job.id})">
                        <i class="fas fa-eye"></i>
                    </button>
                `);
                buttons.push(`
                    <button class="btn btn-xs btn-danger" onclick="extractorModule.cancelJob(${job.id})">
                        <i class="fas fa-stop"></i>
                    </button>
                `);
                break;
                
            case 'completed':
                buttons.push(`
                    <button class="btn btn-xs btn-info" onclick="extractorModule.viewJobResults(${job.id})">
                        <i class="fas fa-chart-bar"></i>
                    </button>
                `);
                break;
                
            case 'failed':
                buttons.push(`
                    <button class="btn btn-xs btn-warning" onclick="extractorModule.retryJob(${job.id})">
                        <i class="fas fa-redo"></i>
                    </button>
                `);
                break;
        }
        
        return buttons.join('');
    }

    /**
     * Actualizar estadísticas
     */
    updateStats(stats) {
        if (!stats) return;
        
        document.getElementById('total-jobs').textContent = stats.total || 0;
        document.getElementById('completed-jobs').textContent = stats.completed || 0;
        document.getElementById('running-jobs').textContent = stats.running || 0;
        document.getElementById('pending-jobs').textContent = stats.pending || 0;
        document.getElementById('failed-jobs').textContent = stats.failed || 0;
    }

    /**
     * Actualizar paginación
     */
    updatePagination(data) {
        const paginationElement = document.getElementById('jobs-pagination');
        if (!paginationElement) return;

        const showingElement = document.getElementById('jobs-showing');
        const totalElement = document.getElementById('jobs-total');
        const pageInfoElement = document.getElementById('jobs-page-info');
        const prevBtn = document.getElementById('jobs-prev-btn');
        const nextBtn = document.getElementById('jobs-next-btn');

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

    // ==========================================================================
    // FILTRADO Y NAVEGACIÓN
    // ==========================================================================

    /**
     * Filtrar trabajos por texto
     */
    filterJobs(searchText) {
        this.currentFilter.search = searchText.toLowerCase();
        this.currentPage = 1;
        this.loadExtractionJobs();
    }

    /**
     * Filtrar por estado
     */
    filterByStatus(status) {
        this.currentFilter.status = status;
        this.currentPage = 1;
        this.loadExtractionJobs();
    }

    /**
     * Filtrar por período
     */
    filterByPeriod(period) {
        this.currentFilter.period = period;
        this.currentPage = 1;
        this.loadExtractionJobs();
    }

    /**
     * Cambiar tamaño de página
     */
    changePageSize(size) {
        this.pageSize = size;
        this.currentPage = 1;
        this.loadExtractionJobs();
    }

    /**
     * Página anterior
     */
    previousPage() {
        if (this.currentPage > 1) {
            this.currentPage--;
            this.loadExtractionJobs();
        }
    }

    /**
     * Página siguiente
     */
    nextPage() {
        if (this.currentPage < this.totalPages) {
            this.currentPage++;
            this.loadExtractionJobs();
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
        this.loadExtractionJobs();
    }

    /**
     * Actualizar iconos de ordenamiento
     */
    updateSortIcons() {
        document.querySelectorAll('.sort-icon').forEach(icon => {
            icon.className = 'fas fa-sort sort-icon';
        });
        
        const currentIcon = document.querySelector(`[onclick="extractorModule.sortBy('${this.sortField}')"] .sort-icon`);
        if (currentIcon) {
            currentIcon.className = `fas fa-sort-${this.sortDirection === 'asc' ? 'up' : 'down'} sort-icon`;
        }
    }

    /**
     * Refrescar lista
     */
    async refreshJobs() {
        console.log('[ExtractorModule] Refrescando trabajos...');
        await this.loadExtractionJobs();
        NotificationManager.success('Lista actualizada correctamente');
    }

    // ==========================================================================
    // WIZARD DE EXTRACCIÓN (3 PASOS)
    // ==========================================================================

    /**
     * Iniciar wizard de extracción
     */
    async startExtractionWizard() {
        console.log('[ExtractorModule] Iniciando wizard de extracción...');
        
        // Resetear wizard
        this.currentStep = 1;
        this.selectedProvider = null;
        this.wizardData = {};
        this.selectedHotels = [];
        
        // Cargar datos necesarios
        await this.loadWizardData();
        
        // Mostrar modal
        this.openWizardModal();
        
        // Configurar primer paso
        this.setupWizardStep1();
    }

    /**
     * Cargar datos necesarios para el wizard
     */
    async loadWizardData() {
        try {
            // Cargar proveedores API disponibles
            const providersResponse = await AdminAPI.request('getExternalApis', {
                status: 'active',
                limit: 100
            });
            
            if (providersResponse.success) {
                this.availableProviders = providersResponse.data.apis || [];
            }
            
            // Cargar hoteles disponibles
            const hotelsResponse = await AdminAPI.request('getHotels');
            
            if (hotelsResponse.success) {
                this.availableHotels = hotelsResponse.hotels || [];
            }
            
        } catch (error) {
            console.error('[ExtractorModule] Error cargando datos del wizard:', error);
            NotificationManager.error('Error cargando datos: ' + error.message);
        }
    }

    /**
     * Abrir modal del wizard
     */
    openWizardModal() {
        const modal = document.getElementById('extraction-wizard-modal');
        if (modal) {
            modal.classList.add('show');
            document.body.style.overflow = 'hidden';
            
            // Resetear progreso visual
            this.updateWizardProgress(1);
            
            // Focus en el primer elemento relevante
            setTimeout(() => {
                this.focusFirstWizardElement();
            }, 150);
        }
    }

    /**
     * Cerrar wizard
     */
    closeWizard() {
        const modal = document.getElementById('extraction-wizard-modal');
        if (modal) {
            modal.classList.remove('show');
            document.body.style.overflow = '';
            
            // Limpiar datos
            this.currentStep = 1;
            this.selectedProvider = null;
            this.wizardData = {};
            this.selectedHotels = [];
        }
    }

    /**
     * Configurar paso 1: Selección de proveedor
     */
    setupWizardStep1() {
        const container = document.getElementById('api-providers-list');
        if (!container) return;
        
        if (this.availableProviders.length === 0) {
            container.innerHTML = `
                <div class="empty-state">
                    <i class="fas fa-plug"></i>
                    <h3>No hay proveedores configurados</h3>
                    <p>Ve al módulo "APIs" para configurar los proveedores de datos antes de crear extracciones.</p>
                    <button class="btn btn-primary" onclick="tabManager.switchTab('apis')">
                        <i class="fas fa-plus"></i>
                        Configurar APIs
                    </button>
                </div>
            `;
            return;
        }
        
        // Renderizar proveedores disponibles
        container.innerHTML = this.availableProviders.map(provider => 
            this.generateProviderCardHTML(provider)
        ).join('');
        
        // Actualizar navegación
        this.updateWizardNavigation();
    }

    /**
     * Generar HTML para card de proveedor
     */
    generateProviderCardHTML(provider) {
        const providerConfig = this.getProviderConfig(provider.provider_type);
        const connectionStatus = this.getConnectionStatusBadge(provider.connection_status);
        
        return `
            <div class="provider-card" onclick="extractorModule.selectProvider(${provider.id})">
                <div class="provider-header">
                    <div class="provider-logo" style="background: ${providerConfig.color}">
                        <i class="${providerConfig.icon}"></i>
                    </div>
                    <div class="provider-info">
                        <h4>${AdminUI.escapeHtml(provider.name)}</h4>
                        <p>${AdminUI.escapeHtml(provider.description || providerConfig.description)}</p>
                    </div>
                    <div class="provider-status">
                        ${connectionStatus}
                    </div>
                </div>
                <div class="provider-details">
                    <div class="detail-item">
                        <span class="detail-label">Rate Limit:</span>
                        <span>${provider.rate_limit ? provider.rate_limit + ' req/min' : 'Sin límite'}</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Timeout:</span>
                        <span>${provider.timeout || 30}s</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Última prueba:</span>
                        <span>${this.formatDate(provider.last_test)}</span>
                    </div>
                </div>
            </div>
        `;
    }

    /**
     * Seleccionar proveedor API
     */
    selectProvider(providerId) {
        const provider = this.availableProviders.find(p => p.id === providerId);
        if (!provider) return;
        
        this.selectedProvider = provider;
        
        // Ocultar lista de proveedores
        document.getElementById('api-providers-list').style.display = 'none';
        
        // Mostrar proveedor seleccionado
        const selectedContainer = document.getElementById('selected-provider');
        selectedContainer.style.display = 'block';
        
        // Llenar datos del proveedor seleccionado
        this.populateSelectedProvider(provider);
        
        // Habilitar botón siguiente
        document.getElementById('wizard-next-btn').disabled = false;
    }

    /**
     * Deseleccionar proveedor
     */
    unselectProvider() {
        this.selectedProvider = null;
        
        // Mostrar lista de proveedores
        document.getElementById('api-providers-list').style.display = 'grid';
        
        // Ocultar proveedor seleccionado
        document.getElementById('selected-provider').style.display = 'none';
        
        // Deshabilitar botón siguiente
        document.getElementById('wizard-next-btn').disabled = true;
    }

    /**
     * Poblar datos del proveedor seleccionado
     */
    populateSelectedProvider(provider) {
        const providerConfig = this.getProviderConfig(provider.provider_type);
        
        document.getElementById('selected-provider-icon').className = providerConfig.icon;
        document.getElementById('selected-provider-name').textContent = provider.name;
        document.getElementById('selected-provider-description').textContent = 
            provider.description || providerConfig.description;
        document.getElementById('selected-provider-status').innerHTML = 
            this.getConnectionStatusBadge(provider.connection_status);
        document.getElementById('selected-provider-rate-limit').textContent = 
            provider.rate_limit ? provider.rate_limit + ' req/min' : 'Sin límite';
        document.getElementById('selected-provider-timeout').textContent = 
            (provider.timeout || 30) + 's';
        document.getElementById('selected-provider-last-test').textContent = 
            this.formatDate(provider.last_test);
    }

    /**
     * Probar proveedor seleccionado
     */
    async testSelectedProvider() {
        if (!this.selectedProvider) return;
        
        try {
            const response = await AdminAPI.request('testExternalApi', { 
                id: this.selectedProvider.id 
            });
            
            const statusBadge = document.getElementById('selected-provider-status');
            
            if (response.success) {
                statusBadge.innerHTML = this.getConnectionStatusBadge('success');
                NotificationManager.success('Conexión exitosa: ' + response.message);
            } else {
                statusBadge.innerHTML = this.getConnectionStatusBadge('error');
                NotificationManager.error('Error de conexión: ' + response.message);
            }
            
        } catch (error) {
            console.error('[ExtractorModule] Error probando proveedor:', error);
            document.getElementById('selected-provider-status').innerHTML = 
                this.getConnectionStatusBadge('error');
            NotificationManager.error('Error al probar conexión: ' + error.message);
        }
    }

    /**
     * Configurar paso 2: Configuración
     */
    setupWizardStep2() {
        // Generar nombre automático
        const defaultName = this.generateDefaultJobName();
        document.getElementById('extraction-name').value = defaultName;
        
        // Configurar modo de hoteles por defecto
        this.updateHotelMode('active');
        
        // Configurar fecha mínima para programación
        const now = new Date();
        now.setMinutes(now.getMinutes() + 15); // Mínimo 15 minutos en el futuro
        document.getElementById('scheduled-datetime').min = 
            now.toISOString().slice(0, 16);
        
        // Actualizar estimaciones iniciales
        this.updateCostEstimation();
    }

    /**
     * Generar nombre por defecto para el trabajo
     */
    generateDefaultJobName() {
        const provider = this.selectedProvider;
        const date = new Date().toLocaleDateString('es-ES', { 
            month: 'long', 
            year: 'numeric' 
        });
        
        return `Extracción ${provider.name} - ${date}`;
    }

    /**
     * Actualizar modo de hoteles
     */
    updateHotelMode(mode) {
        const selectionContainer = document.getElementById('hotels-selection');
        
        if (mode === 'selected') {
            selectionContainer.style.display = 'block';
            this.loadHotelsForSelection();
        } else {
            selectionContainer.style.display = 'none';
            this.selectedHotels = [];
        }
        
        this.updateHotelsSummary(mode);
        this.updateCostEstimation();
    }

    /**
     * Cargar hoteles para selección manual
     */
    loadHotelsForSelection() {
        const container = document.getElementById('hotels-list');
        if (!container) return;
        
        container.innerHTML = this.availableHotels.map(hotel => `
            <div class="hotel-item" onclick="extractorModule.toggleHotelSelection(${hotel.id})">
                <label class="checkbox-item">
                    <input type="checkbox" value="${hotel.id}" onchange="extractorModule.toggleHotelSelection(${hotel.id})">
                    <span class="checkbox-mark"></span>
                    <span class="checkbox-label">
                        <strong>${AdminUI.escapeHtml(hotel.hotel_name)}</strong>
                        <small>${AdminUI.escapeHtml(hotel.hotel_destination || '')}</small>
                    </span>
                </label>
            </div>
        `).join('');
    }

    /**
     * Toggle selección de hotel
     */
    toggleHotelSelection(hotelId) {
        const index = this.selectedHotels.indexOf(hotelId);
        
        if (index > -1) {
            this.selectedHotels.splice(index, 1);
        } else {
            this.selectedHotels.push(hotelId);
        }
        
        // Actualizar checkbox visual
        const checkbox = document.querySelector(`input[value="${hotelId}"]`);
        if (checkbox) {
            checkbox.checked = this.selectedHotels.includes(hotelId);
        }
        
        // Actualizar resumen y estimaciones
        this.updateHotelsSummary('selected');
        this.updateCostEstimation();
    }

    /**
     * Filtrar hoteles en selección
     */
    filterHotelsSelection(searchText) {
        const items = document.querySelectorAll('.hotel-item');
        const search = searchText.toLowerCase();
        
        items.forEach(item => {
            const text = item.textContent.toLowerCase();
            item.style.display = text.includes(search) ? 'block' : 'none';
        });
    }

    /**
     * Actualizar resumen de hoteles
     */
    updateHotelsSummary(mode) {
        const activeHotels = this.availableHotels.filter(h => h.activo == 1).length;
        let selectedCount = 0;
        
        switch (mode) {
            case 'active':
                selectedCount = activeHotels;
                break;
            case 'all':
                selectedCount = this.availableHotels.length;
                break;
            case 'selected':
                selectedCount = this.selectedHotels.length;
                break;
        }
        
        document.getElementById('selected-hotels-count').textContent = selectedCount;
        document.getElementById('active-hotels-summary').textContent = activeHotels;
    }

    /**
     * Actualizar estimaciones de costo
     */
    updateCostEstimation() {
        const mode = document.querySelector('input[name="hotel_mode"]:checked')?.value || 'active';
        const maxReviews = parseInt(document.getElementById('max-reviews-per-hotel')?.value) || 200;
        
        // Calcular número de hoteles
        let hotelsCount = 0;
        switch (mode) {
            case 'active':
                hotelsCount = this.availableHotels.filter(h => h.activo == 1).length;
                break;
            case 'all':
                hotelsCount = this.availableHotels.length;
                break;
            case 'selected':
                hotelsCount = this.selectedHotels.length;
                break;
        }
        
        // Estimaciones
        const estimatedReviews = hotelsCount * maxReviews;
        const estimatedRequests = hotelsCount * this.config.averageRequestsPerHotel;
        const estimatedCost = estimatedRequests * this.config.costPerRequest;
        
        // Actualizar UI
        document.getElementById('cost-hotels-count').textContent = hotelsCount;
        document.getElementById('cost-reviews-estimate').textContent = this.formatNumber(estimatedReviews);
        document.getElementById('cost-api-requests').textContent = this.formatNumber(estimatedRequests);
        document.getElementById('cost-total-estimate').textContent = this.formatCurrency(estimatedCost);
        
        // Guardar en wizardData para uso posterior
        this.wizardData.estimations = {
            hotels: hotelsCount,
            reviews: estimatedReviews,
            requests: estimatedRequests,
            cost: estimatedCost
        };
    }

    /**
     * Actualizar modo de ejecución
     */
    updateExecutionMode(mode) {
        const scheduleContainer = document.getElementById('schedule-options');
        const finishBtnText = document.getElementById('finish-btn-text');
        
        switch (mode) {
            case 'immediate':
                scheduleContainer.style.display = 'none';
                finishBtnText.textContent = 'Crear y Ejecutar';
                break;
            case 'schedule':
                scheduleContainer.style.display = 'block';
                finishBtnText.textContent = 'Crear y Programar';
                break;
            case 'draft':
                scheduleContainer.style.display = 'none';
                finishBtnText.textContent = 'Guardar Borrador';
                break;
        }
    }

    /**
     * Configurar paso 3: Revisión
     */
    setupWizardStep3() {
        // Recopilar datos del formulario
        this.collectWizardData();
        
        // Mostrar resumen
        this.populateReviewStep();
        
        // Validar para habilitar botón final
        this.validateFinalStep();
    }

    /**
     * Recopilar datos del wizard
     */
    collectWizardData() {
        const form = document.getElementById('extraction-wizard-form');
        const formData = new FormData(form);
        
        this.wizardData.name = formData.get('extraction_name');
        this.wizardData.description = formData.get('extraction_description');
        this.wizardData.hotel_mode = formData.get('hotel_mode');
        this.wizardData.max_reviews = parseInt(formData.get('max_reviews_per_hotel'));
        this.wizardData.priority = formData.get('priority');
        this.wizardData.execution_mode = formData.get('execution_mode');
        this.wizardData.scheduled_datetime = formData.get('scheduled_datetime');
        
        // Opciones
        this.wizardData.options = {
            include_responses: formData.has('include_responses'),
            extract_photos: formData.has('extract_photos'),
            skip_duplicates: formData.has('skip_duplicates'),
            auto_translate: formData.has('auto_translate')
        };
        
        // Hoteles seleccionados
        if (this.wizardData.hotel_mode === 'selected') {
            this.wizardData.selected_hotels = [...this.selectedHotels];
        }
        
        // Proveedor seleccionado
        this.wizardData.api_provider = this.selectedProvider;
    }

    /**
     * Poblar paso de revisión
     */
    populateReviewStep() {
        const data = this.wizardData;
        const estimations = data.estimations || {};
        
        // Información básica
        document.getElementById('review-job-name').textContent = data.name || '';
        document.getElementById('review-api-provider').textContent = data.api_provider?.name || '';
        document.getElementById('review-hotel-mode').textContent = this.getModeDisplayText(data.hotel_mode);
        document.getElementById('review-hotel-count').textContent = estimations.hotels || 0;
        document.getElementById('review-max-reviews').textContent = data.max_reviews || 0;
        document.getElementById('review-priority').textContent = this.getPriorityDisplayText(data.priority);
        
        // Opciones seleccionadas
        const optionsList = document.getElementById('review-options-list');
        const options = [];
        
        if (data.options.include_responses) options.push('Incluir respuestas del hotel');
        if (data.options.extract_photos) options.push('Extraer URLs de fotos');
        if (data.options.skip_duplicates) options.push('Omitir reseñas duplicadas');
        if (data.options.auto_translate) options.push('Traducir automáticamente');
        
        optionsList.innerHTML = options.map(option => `<li>${option}</li>`).join('');
        
        // Estimaciones finales
        document.getElementById('final-hotels-count').textContent = estimations.hotels || 0;
        document.getElementById('final-reviews-estimate').textContent = 
            this.formatNumber(estimations.reviews || 0);
        document.getElementById('final-time-estimate').textContent = 
            this.estimateExecutionTime(estimations.hotels || 0) + 'min';
        document.getElementById('final-cost-estimate').textContent = 
            this.formatCurrency(estimations.cost || 0);
    }

    /**
     * Validar paso final
     */
    validateFinalStep() {
        const confirmCosts = document.getElementById('confirm-costs').checked;
        const confirmDataUsage = document.getElementById('confirm-data-usage').checked;
        
        const finishBtn = document.getElementById('wizard-finish-btn');
        finishBtn.disabled = !(confirmCosts && confirmDataUsage);
    }

    /**
     * Navegar al paso siguiente del wizard
     */
    wizardNextStep() {
        if (this.currentStep < 3) {
            // Validar paso actual
            if (!this.validateCurrentWizardStep()) {
                return;
            }
            
            this.currentStep++;
            this.updateWizardStep();
            
            // Configurar nuevo paso
            switch (this.currentStep) {
                case 2:
                    this.setupWizardStep2();
                    break;
                case 3:
                    this.setupWizardStep3();
                    break;
            }
        }
    }

    /**
     * Navegar al paso anterior del wizard
     */
    wizardPreviousStep() {
        if (this.currentStep > 1) {
            this.currentStep--;
            this.updateWizardStep();
        }
    }

    /**
     * Actualizar visualización del paso del wizard
     */
    updateWizardStep() {
        // Ocultar todos los pasos
        document.querySelectorAll('.wizard-step').forEach(step => {
            step.style.display = 'none';
        });
        
        // Mostrar paso actual
        document.getElementById(`wizard-step-${this.currentStep}`).style.display = 'block';
        
        // Actualizar progreso visual
        this.updateWizardProgress(this.currentStep);
        
        // Actualizar navegación
        this.updateWizardNavigation();
    }

    /**
     * Actualizar progreso visual del wizard
     */
    updateWizardProgress(step) {
        document.querySelectorAll('.progress-step').forEach((stepEl, index) => {
            const stepNumber = index + 1;
            
            if (stepNumber < step) {
                stepEl.classList.add('completed');
                stepEl.classList.remove('active');
            } else if (stepNumber === step) {
                stepEl.classList.add('active');
                stepEl.classList.remove('completed');
            } else {
                stepEl.classList.remove('active', 'completed');
            }
        });
        
        // Actualizar líneas de progreso
        document.querySelectorAll('.progress-line').forEach((line, index) => {
            const after = line.querySelector('::after') || line;
            if (index + 1 < step) {
                line.style.setProperty('--progress-width', '100%');
            } else {
                line.style.setProperty('--progress-width', '0%');
            }
        });
    }

    /**
     * Actualizar navegación del wizard
     */
    updateWizardNavigation() {
        const prevBtn = document.getElementById('wizard-prev-btn');
        const nextBtn = document.getElementById('wizard-next-btn');
        const finishBtn = document.getElementById('wizard-finish-btn');
        
        // Botón anterior
        prevBtn.style.display = this.currentStep > 1 ? 'inline-flex' : 'none';
        
        // Botón siguiente vs finalizar
        if (this.currentStep < 3) {
            nextBtn.style.display = 'inline-flex';
            finishBtn.style.display = 'none';
            
            // Habilitar/deshabilitar según validación
            nextBtn.disabled = !this.validateCurrentWizardStep();
        } else {
            nextBtn.style.display = 'none';
            finishBtn.style.display = 'inline-flex';
            
            // El botón finalizar se habilita con checkboxes de confirmación
            finishBtn.disabled = true;
        }
    }

    /**
     * Validar paso actual del wizard
     */
    validateCurrentWizardStep() {
        switch (this.currentStep) {
            case 1:
                return this.selectedProvider !== null;
            case 2:
                const name = document.getElementById('extraction-name')?.value.trim();
                const hotelMode = document.querySelector('input[name="hotel_mode"]:checked')?.value;
                
                if (!name || name.length < 3) {
                    NotificationManager.error('El nombre del trabajo debe tener al menos 3 caracteres');
                    return false;
                }
                
                if (hotelMode === 'selected' && this.selectedHotels.length === 0) {
                    NotificationManager.error('Debes seleccionar al menos un hotel');
                    return false;
                }
                
                return true;
            case 3:
                return true; // Se valida con checkboxes
        }
        return false;
    }

    /**
     * Crear trabajo de extracción
     */
    async createExtractionJob() {
        try {
            console.log('[ExtractorModule] Creando trabajo de extracción...');
            
            // Recopilar datos finales
            this.collectWizardData();
            
            // Validar datos
            if (!this.validateFinalData()) {
                return;
            }
            
            // Preparar datos para envío
            const jobData = this.prepareJobData();
            
            // Crear trabajo
            const response = await AdminAPI.request('createExtractionJob', jobData);
            
            if (response.success) {
                NotificationManager.success('Trabajo de extracción creado correctamente');
                this.closeWizard();
                
                // Refrescar lista
                this.refreshJobs();
                
                // Si es ejecución inmediata, mostrar monitor
                if (this.wizardData.execution_mode === 'immediate') {
                    setTimeout(() => {
                        this.showJobsMonitor();
                    }, 1000);
                }
            } else {
                throw new Error(response.message || 'Error al crear trabajo');
            }
            
        } catch (error) {
            console.error('[ExtractorModule] Error creando trabajo:', error);
            NotificationManager.error('Error al crear trabajo: ' + error.message);
        }
    }

    /**
     * Validar datos finales
     */
    validateFinalData() {
        const data = this.wizardData;
        
        if (!data.name || data.name.trim().length < 3) {
            NotificationManager.error('Nombre del trabajo requerido');
            return false;
        }
        
        if (!data.api_provider) {
            NotificationManager.error('Proveedor API requerido');
            return false;
        }
        
        if (!data.estimations || data.estimations.hotels === 0) {
            NotificationManager.error('Debes seleccionar al menos un hotel');
            return false;
        }
        
        return true;
    }

    /**
     * Preparar datos para envío
     */
    prepareJobData() {
        const data = this.wizardData;
        
        return {
            name: data.name.trim(),
            description: data.description?.trim() || '',
            api_provider_id: data.api_provider.id,
            hotel_mode: data.hotel_mode,
            selected_hotels: data.selected_hotels || [],
            max_reviews_per_hotel: data.max_reviews,
            priority: data.priority,
            execution_mode: data.execution_mode,
            scheduled_datetime: data.scheduled_datetime,
            options: data.options,
            estimations: data.estimations
        };
    }

    // ==========================================================================
    // ACCIONES DE TRABAJOS
    // ==========================================================================

    /**
     * Iniciar trabajo
     */
    async startJob(jobId) {
        try {
            console.log(`[ExtractorModule] Iniciando trabajo ${jobId}...`);
            
            const response = await AdminAPI.request('startExtractionJob', { id: jobId });
            
            if (response.success) {
                NotificationManager.success('Trabajo iniciado correctamente');
                this.refreshJobs();
            } else {
                throw new Error(response.message || 'Error al iniciar trabajo');
            }
            
        } catch (error) {
            console.error('[ExtractorModule] Error iniciando trabajo:', error);
            NotificationManager.error('Error al iniciar trabajo: ' + error.message);
        }
    }

    /**
     * Pausar trabajo
     */
    async pauseJob(jobId) {
        try {
            console.log(`[ExtractorModule] Pausando trabajo ${jobId}...`);
            
            const response = await AdminAPI.request('pauseExtractionJob', { id: jobId });
            
            if (response.success) {
                NotificationManager.success('Trabajo pausado correctamente');
                this.refreshJobs();
            } else {
                throw new Error(response.message || 'Error al pausar trabajo');
            }
            
        } catch (error) {
            console.error('[ExtractorModule] Error pausando trabajo:', error);
            NotificationManager.error('Error al pausar trabajo: ' + error.message);
        }
    }

    /**
     * Cancelar trabajo
     */
    async cancelJob(jobId) {
        const confirmed = confirm('¿Estás seguro de que deseas cancelar este trabajo?\n\nEsta acción no se puede deshacer.');
        
        if (!confirmed) return;
        
        try {
            console.log(`[ExtractorModule] Cancelando trabajo ${jobId}...`);
            
            const response = await AdminAPI.request('cancelExtractionJob', { id: jobId });
            
            if (response.success) {
                NotificationManager.success('Trabajo cancelado correctamente');
                this.refreshJobs();
            } else {
                throw new Error(response.message || 'Error al cancelar trabajo');
            }
            
        } catch (error) {
            console.error('[ExtractorModule] Error cancelando trabajo:', error);
            NotificationManager.error('Error al cancelar trabajo: ' + error.message);
        }
    }

    /**
     * Reintentar trabajo fallido
     */
    async retryJob(jobId) {
        try {
            console.log(`[ExtractorModule] Reintentando trabajo ${jobId}...`);
            
            const response = await AdminAPI.request('retryExtractionJob', { id: jobId });
            
            if (response.success) {
                NotificationManager.success('Trabajo reintentado correctamente');
                this.refreshJobs();
            } else {
                throw new Error(response.message || 'Error al reintentar trabajo');
            }
            
        } catch (error) {
            console.error('[ExtractorModule] Error reintentando trabajo:', error);
            NotificationManager.error('Error al reintentar trabajo: ' + error.message);
        }
    }

    /**
     * Eliminar trabajo
     */
    async deleteJob(jobId) {
        const confirmed = confirm('¿Estás seguro de que deseas eliminar este trabajo?\n\nSe eliminarán todos los datos asociados. Esta acción no se puede deshacer.');
        
        if (!confirmed) return;
        
        try {
            console.log(`[ExtractorModule] Eliminando trabajo ${jobId}...`);
            
            const response = await AdminAPI.request('deleteExtractionJob', { id: jobId });
            
            if (response.success) {
                NotificationManager.success('Trabajo eliminado correctamente');
                this.refreshJobs();
            } else {
                throw new Error(response.message || 'Error al eliminar trabajo');
            }
            
        } catch (error) {
            console.error('[ExtractorModule] Error eliminando trabajo:', error);
            NotificationManager.error('Error al eliminar trabajo: ' + error.message);
        }
    }

    /**
     * Ver resultados de trabajo completado
     */
    async viewJobResults(jobId) {
        try {
            console.log(`[ExtractorModule] Viendo resultados del trabajo ${jobId}...`);
            
            const response = await AdminAPI.request('getExtractionJobResults', { id: jobId });
            
            if (response.success) {
                // TODO: Mostrar modal de resultados
                console.log('Resultados:', response.data);
                NotificationManager.info('Vista de resultados - En desarrollo');
            } else {
                throw new Error(response.message || 'Error al cargar resultados');
            }
            
        } catch (error) {
            console.error('[ExtractorModule] Error cargando resultados:', error);
            NotificationManager.error('Error al cargar resultados: ' + error.message);
        }
    }

    /**
     * Descargar resultados
     */
    async downloadResults(jobId) {
        try {
            console.log(`[ExtractorModule] Descargando resultados del trabajo ${jobId}...`);
            
            // Crear enlace de descarga temporal
            const response = await AdminAPI.request('downloadExtractionResults', { 
                id: jobId,
                format: 'excel'
            });
            
            if (response.success && response.data.download_url) {
                // Abrir enlace de descarga
                window.open(response.data.download_url, '_blank');
                NotificationManager.success('Descarga iniciada');
            } else {
                throw new Error(response.message || 'Error al generar descarga');
            }
            
        } catch (error) {
            console.error('[ExtractorModule] Error descargando resultados:', error);
            NotificationManager.error('Error al descargar resultados: ' + error.message);
        }
    }

    /**
     * Ver logs de trabajo
     */
    async viewJobLogs(jobId) {
        try {
            console.log(`[ExtractorModule] Viendo logs del trabajo ${jobId}...`);
            
            const response = await AdminAPI.request('getExtractionJobLogs', { id: jobId });
            
            if (response.success) {
                // TODO: Mostrar modal de logs
                console.log('Logs:', response.data);
                NotificationManager.info('Vista de logs - En desarrollo');
            } else {
                throw new Error(response.message || 'Error al cargar logs');
            }
            
        } catch (error) {
            console.error('[ExtractorModule] Error cargando logs:', error);
            NotificationManager.error('Error al cargar logs: ' + error.message);
        }
    }

    /**
     * Editar trabajo
     */
    async editJob(jobId) {
        try {
            console.log(`[ExtractorModule] Editando trabajo ${jobId}...`);
            
            // Cargar datos del trabajo
            const response = await AdminAPI.request('getExtractionJob', { id: jobId });
            
            if (response.success) {
                // TODO: Abrir wizard con datos pre-cargados
                NotificationManager.info('Edición de trabajos - En desarrollo');
            } else {
                throw new Error(response.message || 'Error al cargar trabajo');
            }
            
        } catch (error) {
            console.error('[ExtractorModule] Error cargando trabajo para editar:', error);
            NotificationManager.error('Error al cargar trabajo: ' + error.message);
        }
    }

    /**
     * Monitorear trabajo específico
     */
    monitorJob(jobId) {
        console.log(`[ExtractorModule] Monitoreando trabajo ${jobId}...`);
        
        // Abrir monitor y filtrar por este trabajo
        this.showJobsMonitor();
        
        // TODO: Filtrar monitor por jobId específico
    }

    // ==========================================================================
    // MONITOR DE TRABAJOS
    // ==========================================================================

    /**
     * Mostrar monitor de trabajos
     */
    async showJobsMonitor() {
        console.log('[ExtractorModule] Abriendo monitor de trabajos...');
        
        // Abrir modal
        const modal = document.getElementById('job-monitor-modal');
        if (modal) {
            modal.classList.add('show');
            document.body.style.overflow = 'hidden';
            
            // Inicializar monitor
            this.isMonitoring = true;
            this.loadMonitorJobs();
            
            // Iniciar auto-refresh si está habilitado
            if (document.getElementById('auto-refresh')?.checked) {
                this.startAutoRefresh();
            }
        }
    }

    /**
     * Cerrar monitor de trabajos
     */
    closeJobsMonitor() {
        const modal = document.getElementById('job-monitor-modal');
        if (modal) {
            modal.classList.remove('show');
            document.body.style.overflow = '';
            
            // Detener monitoring
            this.isMonitoring = false;
            this.stopAutoRefresh();
        }
    }

    /**
     * Cargar trabajos para monitoreo
     */
    async loadMonitorJobs() {
        if (!this.isMonitoring) return;
        
        try {
            const filter = document.getElementById('monitor-status-filter')?.value || 'running';
            
            const response = await AdminAPI.request('getExtractionJobsMonitor', {
                status_filter: filter,
                include_progress: true,
                include_logs: this.showLogs
            });
            
            if (response.success) {
                this.monitorJobs = response.data.jobs || [];
                this.renderMonitorJobs();
                this.updateMonitorSummary(response.data.summary);
            }
            
        } catch (error) {
            console.error('[ExtractorModule] Error cargando trabajos del monitor:', error);
        }
    }

    /**
     * Renderizar trabajos en monitor
     */
    renderMonitorJobs() {
        const container = document.getElementById('monitor-jobs-container');
        if (!container) return;
        
        if (this.monitorJobs.length === 0) {
            container.innerHTML = `
                <div class="empty-state">
                    <i class="fas fa-tasks"></i>
                    <h3>No hay trabajos activos</h3>
                    <p>Los trabajos en ejecución aparecerán aquí automáticamente</p>
                </div>
            `;
            return;
        }
        
        container.innerHTML = this.monitorJobs.map(job => 
            this.generateMonitorJobHTML(job)
        ).join('');
    }

    /**
     * Generar HTML para trabajo monitoreado
     */
    generateMonitorJobHTML(job) {
        const template = document.getElementById('monitor-job-template');
        if (!template) return '';
        
        let html = template.innerHTML;
        
        // Reemplazos básicos
        html = html.replace(/{id}/g, job.id);
        html = html.replace(/{name}/g, AdminUI.escapeHtml(job.name));
        html = html.replace(/{status_icon}/g, this.getStatusIcon(job.status));
        html = html.replace(/{api_icon}/g, this.getProviderIcon(job.api_provider_type));
        html = html.replace(/{api_provider}/g, job.api_provider_name || 'Sin asignar');
        html = html.replace(/{running_time}/g, this.formatDuration(job.running_time));
        
        // Progreso
        html = html.replace(/{progress_text}/g, this.getProgressText(job));
        html = html.replace(/{progress_percentage}/g, Math.round(job.progress || 0));
        html = html.replace(/{progress_class}/g, this.getProgressClass(job.status));
        
        // Estadísticas
        html = html.replace(/{completed_hotels}/g, job.completed_hotels || 0);
        html = html.replace(/{total_hotels}/g, job.total_hotels || 0);
        html = html.replace(/{extracted_reviews}/g, this.formatNumber(job.extracted_reviews || 0));
        html = html.replace(/{estimated_completion}/g, this.formatETA(job.estimated_completion));
        
        // Botones de acción
        html = html.replace(/{action_buttons}/g, this.generateMonitorActionButtons(job));
        
        // Progreso de hoteles
        html = html.replace(/{hotels_progress}/g, this.generateHotelsProgress(job.hotels_progress || []));
        
        // Logs recientes
        html = html.replace(/{recent_logs}/g, this.generateRecentLogs(job.recent_logs || []));
        
        return html;
    }

    /**
     * Toggle auto-refresh
     */
    toggleAutoRefresh(enabled) {
        if (enabled) {
            this.startAutoRefresh();
        } else {
            this.stopAutoRefresh();
        }
    }

    /**
     * Iniciar auto-refresh
     */
    startAutoRefresh() {
        this.stopAutoRefresh(); // Evitar duplicados
        
        this.autoRefreshInterval = setInterval(() => {
            if (this.isMonitoring) {
                this.loadMonitorJobs();
            }
        }, this.config.autoRefreshInterval);
        
        // Actualizar badge de estado
        document.getElementById('monitor-status-badge').textContent = 'Actualizando...';
        document.getElementById('monitor-status-badge').className = 'badge badge-info';
    }

    /**
     * Detener auto-refresh
     */
    stopAutoRefresh() {
        if (this.autoRefreshInterval) {
            clearInterval(this.autoRefreshInterval);
            this.autoRefreshInterval = null;
        }
        
        // Actualizar badge de estado
        document.getElementById('monitor-status-badge').textContent = 'Detenido';
        document.getElementById('monitor-status-badge').className = 'badge badge-secondary';
    }

    /**
     * Toggle mostrar logs
     */
    toggleShowLogs(show) {
        this.showLogs = show;
        const logsPanel = document.getElementById('monitor-logs-panel');
        
        if (logsPanel) {
            logsPanel.style.display = show ? 'block' : 'none';
            
            if (show) {
                this.loadMonitorLogs();
            }
        }
    }

    /**
     * Cargar logs del monitor
     */
    async loadMonitorLogs() {
        try {
            const response = await AdminAPI.request('getExtractionLogsStream', {
                limit: this.config.maxLogEntries
            });
            
            if (response.success) {
                this.renderMonitorLogs(response.data.logs || []);
            }
            
        } catch (error) {
            console.error('[ExtractorModule] Error cargando logs del monitor:', error);
        }
    }

    /**
     * Renderizar logs del monitor
     */
    renderMonitorLogs(logs) {
        const container = document.getElementById('monitor-logs-content');
        if (!container) return;
        
        container.innerHTML = logs.map(log => 
            this.generateLogEntryHTML(log)
        ).join('');
        
        // Scroll al final
        container.scrollTop = container.scrollHeight;
    }

    /**
     * Limpiar logs
     */
    clearLogs() {
        const container = document.getElementById('monitor-logs-content');
        if (container) {
            container.innerHTML = '';
        }
    }

    /**
     * Refrescar monitor
     */
    async refreshMonitor() {
        await this.loadMonitorJobs();
        
        if (this.showLogs) {
            await this.loadMonitorLogs();
        }
        
        NotificationManager.success('Monitor actualizado');
    }

    // ==========================================================================
    // UTILIDADES
    // ==========================================================================

    /**
     * Obtener configuración de proveedor
     */
    getProviderConfig(providerType) {
        const configs = {
            booking: { 
                name: 'Booking.com', 
                icon: 'fas fa-bed', 
                color: '#003580',
                description: 'Extraer reseñas de Booking.com'
            },
            tripadvisor: { 
                name: 'TripAdvisor', 
                icon: 'fas fa-map-marker-alt', 
                color: '#00AF87',
                description: 'Extraer reseñas de TripAdvisor'
            },
            expedia: { 
                name: 'Expedia', 
                icon: 'fas fa-plane', 
                color: '#FFC72C',
                description: 'Extraer reseñas de Expedia'
            },
            google: { 
                name: 'Google Business', 
                icon: 'fab fa-google', 
                color: '#4285F4',
                description: 'Extraer reseñas de Google Business'
            },
            custom: { 
                name: 'API Personalizada', 
                icon: 'fas fa-code', 
                color: '#6B7280',
                description: 'Extraer desde API personalizada'
            }
        };
        
        return configs[providerType] || configs.custom;
    }

    /**
     * Obtener icono de proveedor
     */
    getProviderIcon(providerType) {
        return this.getProviderConfig(providerType).icon;
    }

    /**
     * Obtener badge de estado de conexión
     */
    getConnectionStatusBadge(status) {
        const badges = {
            success: '<span class="connection-badge connection-success"><i class="fas fa-check"></i> Conectado</span>',
            error: '<span class="connection-badge connection-error"><i class="fas fa-times"></i> Error</span>',
            testing: '<span class="connection-badge connection-testing"><i class="fas fa-spinner fa-spin"></i> Probando</span>',
            unknown: '<span class="connection-badge connection-unknown"><i class="fas fa-question"></i> Sin probar</span>'
        };
        
        return badges[status] || badges.unknown;
    }

    /**
     * Obtener clase de estado de trabajo
     */
    getJobStatusClass(job) {
        const classes = ['job-row'];
        if (job.status) classes.push(`status-${job.status}`);
        return classes.join(' ');
    }

    /**
     * Obtener icono de estado
     */
    getStatusIcon(status) {
        const icons = {
            pending: 'fas fa-clock',
            running: 'fas fa-play text-blue',
            completed: 'fas fa-check text-green',
            failed: 'fas fa-exclamation-triangle text-red',
            cancelled: 'fas fa-ban text-yellow'
        };
        
        return icons[status] || icons.pending;
    }

    /**
     * Obtener texto de modo para mostrar
     */
    getModeDisplayText(mode) {
        const texts = {
            active: 'Solo hoteles activos',
            all: 'Todos los hoteles',
            selected: 'Hoteles seleccionados'
        };
        
        return texts[mode] || mode;
    }

    /**
     * Obtener texto de prioridad para mostrar
     */
    getPriorityDisplayText(priority) {
        const texts = {
            normal: 'Normal',
            high: 'Alta',
            critical: 'Crítica'
        };
        
        return texts[priority] || priority;
    }

    /**
     * Estimar tiempo de ejecución
     */
    estimateExecutionTime(hotelsCount) {
        // Estimación: 2-5 minutos por hotel
        const minTime = hotelsCount * 2;
        const maxTime = hotelsCount * 5;
        
        return Math.round((minTime + maxTime) / 2);
    }

    /**
     * Formatear número
     */
    formatNumber(num) {
        if (num >= 1000000) {
            return (num / 1000000).toFixed(1) + 'M';
        }
        if (num >= 1000) {
            return (num / 1000).toFixed(1) + 'K';
        }
        return num.toString();
    }

    /**
     * Formatear moneda
     */
    formatCurrency(amount) {
        return new Intl.NumberFormat('es-ES', {
            style: 'currency',
            currency: 'EUR',
            minimumFractionDigits: 2
        }).format(amount);
    }

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
                    day: 'numeric',
                    month: 'short',
                    year: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                });
            }
        } catch (error) {
            return 'Fecha inválida';
        }
    }

    /**
     * Formatear duración
     */
    formatDuration(seconds) {
        if (!seconds) return '0s';
        
        const hours = Math.floor(seconds / 3600);
        const minutes = Math.floor((seconds % 3600) / 60);
        const secs = seconds % 60;
        
        if (hours > 0) {
            return `${hours}h ${minutes}m`;
        } else if (minutes > 0) {
            return `${minutes}m ${secs}s`;
        } else {
            return `${secs}s`;
        }
    }

    /**
     * Formatear ETA
     */
    formatETA(eta) {
        if (!eta) return 'Desconocido';
        
        try {
            const etaDate = new Date(eta);
            const now = new Date();
            const diffMinutes = Math.floor((etaDate - now) / (1000 * 60));
            
            if (diffMinutes <= 0) {
                return 'Finalizando...';
            } else if (diffMinutes < 60) {
                return `${diffMinutes}min`;
            } else {
                const hours = Math.floor(diffMinutes / 60);
                const mins = diffMinutes % 60;
                return `${hours}h ${mins}min`;
            }
        } catch (error) {
            return 'Desconocido';
        }
    }

    /**
     * Focus en primer elemento del wizard
     */
    focusFirstWizardElement() {
        // Intentar enfocar el primer elemento visible relevante
        const firstProvider = document.querySelector('.provider-card');
        const nameInput = document.getElementById('extraction-name');
        const confirmCheckbox = document.getElementById('confirm-costs');
        
        if (this.currentStep === 1 && firstProvider) {
            firstProvider.focus();
        } else if (this.currentStep === 2 && nameInput) {
            nameInput.focus();
        } else if (this.currentStep === 3 && confirmCheckbox) {
            confirmCheckbox.focus();
        }
    }

    /**
     * Mostrar estado de carga
     */
    showLoading() {
        const container = document.getElementById('extraction-jobs-container');
        if (container) {
            container.innerHTML = `
                <div class="loading-state">
                    <i class="fas fa-spinner fa-spin spinner"></i>
                    <h3>Cargando trabajos de extracción...</h3>
                    <p>Por favor espera mientras cargamos la información</p>
                </div>
            `;
        }
    }

    /**
     * Mostrar estado vacío
     */
    showEmpty() {
        const container = document.getElementById('extraction-jobs-container');
        const template = document.getElementById('jobs-empty-template');
        
        if (!container || !template) return;
        
        let emptyMessage = 'Comienza creando tu primer trabajo de extracción para obtener reseñas de proveedores externos.';
        
        if (this.currentFilter.search) {
            emptyMessage = `No se encontraron trabajos que coincidan con "${this.currentFilter.search}".`;
        } else if (this.currentFilter.status) {
            emptyMessage = 'No hay trabajos que coincidan con los filtros aplicados.';
        }
        
        let html = template.innerHTML;
        html = html.replace(/{empty_message}/g, emptyMessage);
        
        container.innerHTML = html;
        
        // Ocultar paginación
        const pagination = document.getElementById('jobs-pagination');
        if (pagination) {
            pagination.style.display = 'none';
        }
    }

    /**
     * Mostrar estado de error
     */
    showError(message) {
        const container = document.getElementById('extraction-jobs-container');
        const template = document.getElementById('jobs-error-template');
        
        if (!container || !template) return;
        
        let html = template.innerHTML;
        html = html.replace(/{error_message}/g, message);
        
        container.innerHTML = html;
        
        // Ocultar paginación
        const pagination = document.getElementById('jobs-pagination');
        if (pagination) {
            pagination.style.display = 'none';
        }
    }

    // ==========================================================================
    // MÉTODOS ADICIONALES (PENDIENTES DE IMPLEMENTAR)
    // ==========================================================================

    /**
     * Mostrar cola de trabajos
     */
    showJobsQueue() {
        console.log('[ExtractorModule] Mostrando cola de trabajos...');
        NotificationManager.info('Cola de trabajos - En desarrollo');
    }

    /**
     * Pausar todos los trabajos
     */
    async pauseAllJobs() {
        console.log('[ExtractorModule] Pausando todos los trabajos...');
        NotificationManager.info('Pausar todos - En desarrollo');
    }

    /**
     * Cerrar cola de trabajos
     */
    closeJobsQueue() {
        const modal = document.getElementById('jobs-queue-modal');
        if (modal) {
            modal.classList.remove('show');
            document.body.style.overflow = '';
        }
    }
}

// Crear instancia global del módulo
let extractorModule;

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', () => {
    extractorModule = new ExtractorModule();
});

// Exportar para uso global
window.ExtractorModule = ExtractorModule;