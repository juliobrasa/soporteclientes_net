/**
 * ==========================================================================
 * MÓDULO LOGS - JavaScript
 * Kavia Hoteles Panel de Administración
 * Sistema de logs y auditoría completo
 * ==========================================================================
 */

class LogsModule {
    constructor() {
        this.logs = [];
        this.currentFilter = {
            search: '',
            level: '',
            module: '',
            timerange: '24h',
            startDate: null,
            endDate: null
        };
        this.currentPage = 1;
        this.itemsPerPage = 50;
        this.totalLogs = 0;
        this.viewMode = 'timeline';
        this.sortBy = 'timestamp';
        this.sortOrder = 'desc';
        
        // Auto-refresh
        this.autoRefreshInterval = null;
        this.isAutoRefreshEnabled = false;
        
        // Real-time monitoring
        this.realTimeSocket = null;
        this.isRealTimeActive = false;
        
        // Charts
        this.charts = {};
        
        // Configuración de niveles
        this.levelConfig = {
            debug: { name: 'Debug', icon: 'fas fa-bug', color: '#6b7280' },
            info: { name: 'Info', icon: 'fas fa-info-circle', color: '#3b82f6' },
            warning: { name: 'Warning', icon: 'fas fa-exclamation-triangle', color: '#f59e0b' },
            error: { name: 'Error', icon: 'fas fa-times-circle', color: '#ef4444' },
            critical: { name: 'Critical', icon: 'fas fa-skull-crossbones', color: '#8b0000' }
        };
        
        // Configuración de módulos
        this.moduleConfig = {
            auth: { name: 'Autenticación', icon: 'fas fa-lock', color: '#8b5cf6' },
            hotels: { name: 'Hoteles', icon: 'fas fa-hotel', color: '#06b6d4' },
            apis: { name: 'APIs Externas', icon: 'fas fa-plug', color: '#10b981' },
            extraction: { name: 'Extracción', icon: 'fas fa-filter', color: '#f59e0b' },
            prompts: { name: 'Prompts', icon: 'fas fa-file-alt', color: '#8b5cf6' },
            system: { name: 'Sistema', icon: 'fas fa-server', color: '#6b7280' }
        };
        
        this.init();
    }
    
    /**
     * Inicializa el módulo
     */
    init() {
        console.log('🚀 Inicializando Módulo de Logs...');
        
        try {
            // Configurar event listeners
            this.setupEventListeners();
            
            // Cargar logs iniciales
            this.loadLogs();
            
            // Cargar estadísticas
            this.loadStats();
            
            // Iniciar auto-refresh si está habilitado
            if (localStorage.getItem('logs-auto-refresh') === 'true') {
                this.toggleAutoRefresh(true);
            }
            
            // Restaurar modo de vista
            const savedViewMode = localStorage.getItem('logs-view-mode');
            if (savedViewMode && ['timeline', 'table', 'chart'].includes(savedViewMode)) {
                this.setViewMode(savedViewMode);
            }
            
            console.log('✅ Módulo de Logs inicializado correctamente');
        } catch (error) {
            console.error('❌ Error al inicializar módulo de logs:', error);
            showError('Error al inicializar el módulo de logs: ' + error.message);
        }
    }
    
    /**
     * Configura los event listeners
     */
    setupEventListeners() {
        // Búsqueda con debounce
        const searchInput = document.getElementById('logs-search');
        if (searchInput) {
            let searchTimeout;
            searchInput.addEventListener('input', (e) => {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    this.currentFilter.search = e.target.value;
                    this.currentPage = 1;
                    this.loadLogs();
                }, 300);
            });
        }
        
        // Filtros
        ['level-filter', 'module-filter', 'timerange-filter'].forEach(filterId => {
            const filter = document.getElementById(filterId);
            if (filter) {
                filter.addEventListener('change', (e) => {
                    const filterKey = filterId.split('-')[0];
                    this.currentFilter[filterKey] = e.target.value;
                    
                    // Mostrar/ocultar rango de fechas personalizado
                    if (filterId === 'timerange-filter') {
                        const customRange = document.getElementById('custom-date-range');
                        if (customRange) {
                            customRange.style.display = e.target.value === 'custom' ? 'flex' : 'none';
                        }
                    }
                    
                    this.currentPage = 1;
                    this.loadLogs();
                });
            }
        });
        
        // Fechas personalizadas
        ['start-date', 'end-date'].forEach(dateId => {
            const dateInput = document.getElementById(dateId);
            if (dateInput) {
                dateInput.addEventListener('change', (e) => {
                    const key = dateId.split('-')[0] + 'Date';
                    this.currentFilter[key] = e.target.value;
                    
                    if (this.currentFilter.timerange === 'custom') {
                        this.currentPage = 1;
                        this.loadLogs();
                    }
                });
            }
        });
        
        // Keyboard shortcuts
        document.addEventListener('keydown', (e) => {
            if (e.ctrlKey || e.metaKey) {
                switch (e.key) {
                    case 'r':
                        e.preventDefault();
                        this.refreshLogs();
                        break;
                    case 'f':
                        e.preventDefault();
                        const searchInput = document.getElementById('logs-search');
                        if (searchInput) searchInput.focus();
                        break;
                    case '1':
                        e.preventDefault();
                        this.setViewMode('timeline');
                        break;
                    case '2':
                        e.preventDefault();
                        this.setViewMode('table');
                        break;
                    case '3':
                        e.preventDefault();
                        this.setViewMode('chart');
                        break;
                }
            }
        });
        
        // Cleanup al cerrar
        window.addEventListener('beforeunload', () => {
            this.cleanup();
        });
    }
    
    /**
     * Carga la lista de logs
     */
    async loadLogs() {
        try {
            const loadingElement = document.getElementById('logs-loading');
            if (loadingElement) {
                loadingElement.style.display = 'flex';
            }
            
            const params = {
                page: this.currentPage,
                limit: this.itemsPerPage,
                search: this.currentFilter.search,
                level: this.currentFilter.level,
                module: this.currentFilter.module,
                timerange: this.currentFilter.timerange,
                start_date: this.currentFilter.startDate,
                end_date: this.currentFilter.endDate,
                sort_by: this.sortBy,
                sort_order: this.sortOrder
            };
            
            const response = await AdminAPI.request('getLogs', params);
            
            if (response.success) {
                this.logs = response.data.logs;
                this.totalLogs = response.data.total;
                
                this.renderLogs();
                this.updatePagination();
                
                // Actualizar contador en header
                const totalCountElement = document.getElementById('logs-total-count');
                if (totalCountElement) {
                    totalCountElement.textContent = this.totalLogs;
                }
                
                // Si estamos en vista de gráficos, actualizar charts
                if (this.viewMode === 'chart') {
                    this.updateCharts(response.data.charts);
                }
            } else {
                throw new Error(response.message || 'Error al cargar logs');
            }
        } catch (error) {
            console.error('Error al cargar logs:', error);
            showError('Error al cargar logs: ' + error.message);
        } finally {
            const loadingElement = document.getElementById('logs-loading');
            if (loadingElement) {
                loadingElement.style.display = 'none';
            }
        }
    }
    
    /**
     * Carga las estadísticas del módulo
     */
    async loadStats() {
        try {
            const response = await AdminAPI.request('getLogsStats');
            
            if (response.success) {
                const stats = response.data;
                
                // Actualizar estadísticas con animación
                this.updateStatElement('total-logs-stat', stats.total_logs);
                this.updateStatElement('errors-today-stat', stats.errors_today);
                this.updateStatElement('active-users-stat', stats.active_users);
                this.updateStatElement('db-queries-stat', stats.db_queries_today);
                
                // Uptime especial
                const uptimeElement = document.getElementById('system-uptime-stat');
                if (uptimeElement) {
                    uptimeElement.textContent = this.formatUptime(stats.system_uptime);
                }
            }
        } catch (error) {
            console.error('Error al cargar estadísticas:', error);
        }
    }
    
    /**
     * Actualiza un elemento de estadística con animación
     */
    updateStatElement(elementId, value) {
        const element = document.getElementById(elementId);
        if (element) {
            const startValue = parseInt(element.textContent.replace(/[^\d]/g, '')) || 0;
            const endValue = parseInt(value) || 0;
            const duration = 1000;
            const step = (endValue - startValue) / (duration / 16);
            
            let currentValue = startValue;
            const timer = setInterval(() => {
                currentValue += step;
                if ((step > 0 && currentValue >= endValue) || (step < 0 && currentValue <= endValue)) {
                    element.textContent = this.formatNumber(endValue);
                    clearInterval(timer);
                } else {
                    element.textContent = this.formatNumber(Math.round(currentValue));
                }
            }, 16);
        }
    }
    
    /**
     * Formatea números con separadores de miles
     */
    formatNumber(num) {
        return new Intl.NumberFormat('es-ES').format(num);
    }
    
    /**
     * Formatea el tiempo de actividad
     */
    formatUptime(seconds) {
        if (!seconds) return '--';
        
        const days = Math.floor(seconds / 86400);
        const hours = Math.floor((seconds % 86400) / 3600);
        const minutes = Math.floor((seconds % 3600) / 60);
        
        if (days > 0) {
            return `${days}d ${hours}h`;
        } else if (hours > 0) {
            return `${hours}h ${minutes}m`;
        } else {
            return `${minutes}m`;
        }
    }
    
    /**
     * Renderiza los logs según el modo de vista
     */
    renderLogs() {
        switch (this.viewMode) {
            case 'timeline':
                this.renderTimelineView();
                break;
            case 'table':
                this.renderTableView();
                break;
            case 'chart':
                this.renderChartView();
                break;
        }
    }
    
    /**
     * Renderiza la vista de timeline
     */
    renderTimelineView() {
        const container = document.getElementById('logs-timeline');
        if (!container) return;
        
        if (this.logs.length === 0) {
            container.innerHTML = this.getEmptyState();
            return;
        }
        
        const template = document.getElementById('timeline-entry-template');
        if (!template) return;
        
        let html = '';
        
        this.logs.forEach(log => {
            const levelConfig = this.levelConfig[log.level] || this.levelConfig.info;
            const moduleConfig = this.moduleConfig[log.module] || { name: log.module, icon: 'fas fa-cube' };
            
            const entryHtml = template.innerHTML
                .replace(/{id}/g, log.id)
                .replace(/{level}/g, log.level)
                .replace(/{level_icon}/g, levelConfig.icon)
                .replace(/{formatted_time}/g, this.formatTime(log.timestamp))
                .replace(/{module}/g, moduleConfig.name)
                .replace(/{message}/g, this.escapeHtml(this.truncateMessage(log.message)))
                .replace(/{user_name}/g, this.escapeHtml(log.user_name || 'Sistema'))
                .replace(/{ip_address}/g, log.ip_address || '--')
                .replace(/{user_agent_short}/g, this.getUserAgentShort(log.user_agent))
                .replace(/{show_metadata}/g, log.user_name || log.ip_address ? 'block' : 'none')
                .replace(/{formatted_data}/g, log.data ? JSON.stringify(log.data, null, 2) : '{}');
            
            html += entryHtml;
        });
        
        container.innerHTML = html;
    }
    
    /**
     * Renderiza la vista de tabla
     */
    renderTableView() {
        const tbody = document.getElementById('logs-table-body');
        if (!tbody) return;
        
        if (this.logs.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="6" class="text-center">
                        ${this.getEmptyState()}
                    </td>
                </tr>
            `;
            return;
        }
        
        const template = document.getElementById('table-row-template');
        if (!template) return;
        
        let html = '';
        
        this.logs.forEach(log => {
            const levelConfig = this.levelConfig[log.level] || this.levelConfig.info;
            const moduleConfig = this.moduleConfig[log.module] || { name: log.module, icon: 'fas fa-cube' };
            
            const isLongMessage = log.message && log.message.length > 100;
            const truncatedMessage = this.truncateMessage(log.message, 100);
            
            const rowHtml = template.innerHTML
                .replace(/{id}/g, log.id)
                .replace(/{level}/g, log.level)
                .replace(/{level_icon}/g, levelConfig.icon)
                .replace(/{formatted_timestamp}/g, this.formatTimestamp(log.timestamp))
                .replace(/{relative_time}/g, this.getRelativeTime(log.timestamp))
                .replace(/{module}/g, moduleConfig.name)
                .replace(/{module_icon}/g, moduleConfig.icon)
                .replace(/{message}/g, this.escapeHtml(truncatedMessage))
                .replace(/{full_message}/g, this.escapeHtml(log.message || ''))
                .replace(/{show_expand}/g, isLongMessage ? 'inline' : 'none')
                .replace(/{user_name}/g, this.escapeHtml(log.user_name || 'Sistema'))
                .replace(/{ip_address}/g, log.ip_address || '--');
            
            html += rowHtml;
        });
        
        tbody.innerHTML = html;
        
        // Actualizar indicadores de ordenamiento
        this.updateSortIndicators();
    }
    
    /**
     * Renderiza la vista de gráficos
     */
    renderChartView() {
        // Los gráficos se actualizan en updateCharts()
        // Aquí solo nos aseguramos de que los contenedores estén disponibles
        this.initializeChartContainers();
    }
    
    /**
     * Inicializa los contenedores de gráficos
     */
    initializeChartContainers() {
        const chartIds = ['activity-chart', 'levels-chart', 'modules-chart', 'errors-chart'];
        
        chartIds.forEach(chartId => {
            const canvas = document.getElementById(chartId);
            if (canvas && !this.charts[chartId]) {
                // Placeholder hasta que se carguen los datos
                const ctx = canvas.getContext('2d');
                ctx.fillStyle = '#f3f4f6';
                ctx.fillRect(0, 0, canvas.width, canvas.height);
                ctx.fillStyle = '#6b7280';
                ctx.textAlign = 'center';
                ctx.font = '16px Arial';
                ctx.fillText('Cargando gráfico...', canvas.width / 2, canvas.height / 2);
            }
        });
    }
    
    /**
     * Actualiza los gráficos con nuevos datos
     */
    updateCharts(chartData) {
        if (!chartData) return;
        
        // Gráfico de actividad por hora
        if (chartData.activity && document.getElementById('activity-chart')) {
            this.updateActivityChart(chartData.activity);
        }
        
        // Gráfico de distribución por nivel
        if (chartData.levels && document.getElementById('levels-chart')) {
            this.updateLevelsChart(chartData.levels);
        }
        
        // Gráfico de actividad por módulo
        if (chartData.modules && document.getElementById('modules-chart')) {
            this.updateModulesChart(chartData.modules);
        }
        
        // Gráfico de tendencias de error
        if (chartData.errors && document.getElementById('errors-chart')) {
            this.updateErrorsChart(chartData.errors);
        }
    }
    
    /**
     * Actualiza el gráfico de actividad por hora
     */
    updateActivityChart(data) {
        const canvas = document.getElementById('activity-chart');
        if (!canvas) return;
        
        const ctx = canvas.getContext('2d');
        
        // Limpiar canvas
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        
        // Dibujar gráfico de líneas simple
        const padding = 40;
        const chartWidth = canvas.width - padding * 2;
        const chartHeight = canvas.height - padding * 2;
        
        // Encontrar valores máximo y mínimo
        const maxValue = Math.max(...data.map(d => d.count));
        const minValue = 0;
        
        // Dibujar ejes
        ctx.strokeStyle = '#e5e7eb';
        ctx.lineWidth = 1;
        
        // Eje Y
        ctx.beginPath();
        ctx.moveTo(padding, padding);
        ctx.lineTo(padding, padding + chartHeight);
        ctx.stroke();
        
        // Eje X
        ctx.beginPath();
        ctx.moveTo(padding, padding + chartHeight);
        ctx.lineTo(padding + chartWidth, padding + chartHeight);
        ctx.stroke();
        
        // Dibujar línea de datos
        if (data.length > 1) {
            ctx.strokeStyle = '#3b82f6';
            ctx.lineWidth = 2;
            ctx.beginPath();
            
            data.forEach((point, index) => {
                const x = padding + (index / (data.length - 1)) * chartWidth;
                const y = padding + chartHeight - ((point.count - minValue) / (maxValue - minValue)) * chartHeight;
                
                if (index === 0) {
                    ctx.moveTo(x, y);
                } else {
                    ctx.lineTo(x, y);
                }
            });
            
            ctx.stroke();
            
            // Dibujar puntos
            ctx.fillStyle = '#3b82f6';
            data.forEach((point, index) => {
                const x = padding + (index / (data.length - 1)) * chartWidth;
                const y = padding + chartHeight - ((point.count - minValue) / (maxValue - minValue)) * chartHeight;
                
                ctx.beginPath();
                ctx.arc(x, y, 4, 0, 2 * Math.PI);
                ctx.fill();
            });
        }
        
        // Etiquetas
        ctx.fillStyle = '#6b7280';
        ctx.font = '12px Arial';
        ctx.textAlign = 'center';
        
        // Etiquetas X (horas)
        data.forEach((point, index) => {
            if (index % Math.ceil(data.length / 6) === 0) {
                const x = padding + (index / (data.length - 1)) * chartWidth;
                ctx.fillText(point.hour + 'h', x, padding + chartHeight + 20);
            }
        });
        
        // Etiquetas Y (valores)
        ctx.textAlign = 'right';
        for (let i = 0; i <= 4; i++) {
            const value = minValue + ((maxValue - minValue) / 4) * i;
            const y = padding + chartHeight - (i / 4) * chartHeight;
            ctx.fillText(Math.round(value), padding - 10, y + 4);
        }
    }
    
    /**
     * Actualiza el gráfico de niveles (pie chart simple)
     */
    updateLevelsChart(data) {
        const canvas = document.getElementById('levels-chart');
        if (!canvas) return;
        
        const ctx = canvas.getContext('2d');
        const centerX = canvas.width / 2;
        const centerY = canvas.height / 2;
        const radius = Math.min(centerX, centerY) - 40;
        
        // Limpiar canvas
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        
        const total = data.reduce((sum, item) => sum + item.count, 0);
        if (total === 0) return;
        
        let currentAngle = -Math.PI / 2; // Empezar desde arriba
        
        data.forEach((item) => {
            const sliceAngle = (item.count / total) * 2 * Math.PI;
            const levelConfig = this.levelConfig[item.level] || { color: '#6b7280' };
            
            // Dibujar slice
            ctx.fillStyle = levelConfig.color;
            ctx.beginPath();
            ctx.moveTo(centerX, centerY);
            ctx.arc(centerX, centerY, radius, currentAngle, currentAngle + sliceAngle);
            ctx.closePath();
            ctx.fill();
            
            // Dibujar borde
            ctx.strokeStyle = '#ffffff';
            ctx.lineWidth = 2;
            ctx.stroke();
            
            // Dibujar etiqueta si el slice es lo suficientemente grande
            if (sliceAngle > 0.3) {
                const labelAngle = currentAngle + sliceAngle / 2;
                const labelX = centerX + Math.cos(labelAngle) * (radius * 0.7);
                const labelY = centerY + Math.sin(labelAngle) * (radius * 0.7);
                
                ctx.fillStyle = '#ffffff';
                ctx.font = 'bold 12px Arial';
                ctx.textAlign = 'center';
                ctx.fillText(item.level.toUpperCase(), labelX, labelY);
                ctx.fillText(item.count, labelX, labelY + 15);
            }
            
            currentAngle += sliceAngle;
        });
        
        // Leyenda
        let legendY = 20;
        data.forEach((item) => {
            const levelConfig = this.levelConfig[item.level] || { color: '#6b7280' };
            
            // Color box
            ctx.fillStyle = levelConfig.color;
            ctx.fillRect(canvas.width - 120, legendY, 15, 15);
            
            // Texto
            ctx.fillStyle = '#374151';
            ctx.font = '12px Arial';
            ctx.textAlign = 'left';
            ctx.fillText(`${item.level}: ${item.count}`, canvas.width - 100, legendY + 12);
            
            legendY += 25;
        });
    }
    
    /**
     * Actualiza el gráfico de módulos (bar chart simple)
     */
    updateModulesChart(data) {
        const canvas = document.getElementById('modules-chart');
        if (!canvas) return;
        
        const ctx = canvas.getContext('2d');
        const padding = 60;
        const chartWidth = canvas.width - padding * 2;
        const chartHeight = canvas.height - padding * 2;
        
        // Limpiar canvas
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        
        if (data.length === 0) return;
        
        const maxValue = Math.max(...data.map(d => d.count));
        const barWidth = chartWidth / data.length * 0.8;
        const barSpacing = chartWidth / data.length * 0.2;
        
        // Dibujar barras
        data.forEach((item, index) => {
            const barHeight = (item.count / maxValue) * chartHeight;
            const x = padding + index * (barWidth + barSpacing) + barSpacing / 2;
            const y = padding + chartHeight - barHeight;
            
            const moduleConfig = this.moduleConfig[item.module] || { color: '#6b7280' };
            
            // Barra
            ctx.fillStyle = moduleConfig.color;
            ctx.fillRect(x, y, barWidth, barHeight);
            
            // Valor en la parte superior
            ctx.fillStyle = '#374151';
            ctx.font = '12px Arial';
            ctx.textAlign = 'center';
            ctx.fillText(item.count, x + barWidth / 2, y - 5);
            
            // Etiqueta del módulo
            ctx.save();
            ctx.translate(x + barWidth / 2, padding + chartHeight + 15);
            ctx.rotate(-Math.PI / 4);
            ctx.textAlign = 'right';
            ctx.fillText(item.module, 0, 0);
            ctx.restore();
        });
        
        // Ejes
        ctx.strokeStyle = '#e5e7eb';
        ctx.lineWidth = 1;
        
        ctx.beginPath();
        ctx.moveTo(padding, padding);
        ctx.lineTo(padding, padding + chartHeight);
        ctx.lineTo(padding + chartWidth, padding + chartHeight);
        ctx.stroke();
    }
    
    /**
     * Actualiza el gráfico de errores (area chart simple)
     */
    updateErrorsChart(data) {
        const canvas = document.getElementById('errors-chart');
        if (!canvas) return;
        
        const ctx = canvas.getContext('2d');
        const padding = 40;
        const chartWidth = canvas.width - padding * 2;
        const chartHeight = canvas.height - padding * 2;
        
        // Limpiar canvas
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        
        if (data.length === 0) return;
        
        const maxValue = Math.max(...data.map(d => d.count), 1);
        
        // Dibujar área
        ctx.fillStyle = 'rgba(239, 68, 68, 0.2)';
        ctx.beginPath();
        ctx.moveTo(padding, padding + chartHeight);
        
        data.forEach((point, index) => {
            const x = padding + (index / (data.length - 1)) * chartWidth;
            const y = padding + chartHeight - (point.count / maxValue) * chartHeight;
            ctx.lineTo(x, y);
        });
        
        ctx.lineTo(padding + chartWidth, padding + chartHeight);
        ctx.closePath();
        ctx.fill();
        
        // Dibujar línea
        ctx.strokeStyle = '#ef4444';
        ctx.lineWidth = 2;
        ctx.beginPath();
        
        data.forEach((point, index) => {
            const x = padding + (index / (data.length - 1)) * chartWidth;
            const y = padding + chartHeight - (point.count / maxValue) * chartHeight;
            
            if (index === 0) {
                ctx.moveTo(x, y);
            } else {
                ctx.lineTo(x, y);
            }
        });
        
        ctx.stroke();
        
        // Ejes
        ctx.strokeStyle = '#e5e7eb';
        ctx.lineWidth = 1;
        
        ctx.beginPath();
        ctx.moveTo(padding, padding);
        ctx.lineTo(padding, padding + chartHeight);
        ctx.lineTo(padding + chartWidth, padding + chartHeight);
        ctx.stroke();
    }
    
    /**
     * Obtiene el estado vacío
     */
    getEmptyState() {
        return `
            <div class="empty-state">
                <i class="fas fa-chart-line"></i>
                <h3>No hay logs disponibles</h3>
                <p>No se encontraron logs que coincidan con los filtros seleccionados</p>
                <button class="btn btn-primary" onclick="logsModule.clearFilters()">
                    <i class="fas fa-filter"></i>
                    Limpiar Filtros
                </button>
            </div>
        `;
    }
    
    /**
     * Cambia el modo de vista
     */
    setViewMode(mode) {
        this.viewMode = mode;
        
        // Actualizar botones
        document.querySelectorAll('.view-toggle .btn').forEach(btn => {
            btn.classList.remove('toggle-active');
        });
        
        const activeButton = document.getElementById(`${mode}-view-btn`);
        if (activeButton) {
            activeButton.classList.add('toggle-active');
        }
        
        // Mostrar/ocultar vistas
        document.querySelectorAll('.logs-view').forEach(view => {
            view.style.display = 'none';
        });
        
        const activeView = document.getElementById(`${mode}-view`);
        if (activeView) {
            activeView.style.display = 'block';
        }
        
        // Cargar contenido si es necesario
        this.renderLogs();
        
        // Guardar preferencia
        localStorage.setItem('logs-view-mode', mode);
    }
    
    /**
     * Ordena por columna (solo para vista de tabla)
     */
    sortBy(column) {
        if (this.sortBy === column) {
            this.sortOrder = this.sortOrder === 'asc' ? 'desc' : 'asc';
        } else {
            this.sortBy = column;
            this.sortOrder = 'desc';
        }
        
        this.loadLogs();
    }
    
    /**
     * Actualiza los indicadores de ordenamiento
     */
    updateSortIndicators() {
        // Remover clases de ordenamiento anteriores
        document.querySelectorAll('.logs-table th').forEach(th => {
            th.classList.remove('sorted', 'asc', 'desc');
        });
        
        // Agregar clase al header activo
        const activeHeader = document.querySelector(`[onclick="logsModule.sortBy('${this.sortBy}')"]`);
        if (activeHeader) {
            activeHeader.classList.add('sorted', this.sortOrder);
        }
    }
    
    /**
     * Actualiza la paginación
     */
    updatePagination() {
        const container = document.getElementById('logs-pagination');
        const controlsContainer = document.getElementById('logs-pagination-controls');
        const showingElement = document.getElementById('logs-showing');
        
        if (!container || !controlsContainer) return;
        
        const totalPages = Math.ceil(this.totalLogs / this.itemsPerPage);
        
        if (totalPages <= 1) {
            container.style.display = 'none';
            return;
        }
        
        container.style.display = 'flex';
        
        // Información de paginación
        if (showingElement) {
            const start = (this.currentPage - 1) * this.itemsPerPage + 1;
            const end = Math.min(this.currentPage * this.itemsPerPage, this.totalLogs);
            showingElement.textContent = `Mostrando ${start}-${end} de ${this.totalLogs} logs`;
        }
        
        // Controles de paginación
        let paginationHtml = '';
        
        // Botón anterior
        paginationHtml += `
            <button class="pagination-btn ${this.currentPage === 1 ? 'disabled' : ''}" 
                    onclick="logsModule.goToPage(${this.currentPage - 1})"
                    ${this.currentPage === 1 ? 'disabled' : ''}>
                <i class="fas fa-chevron-left"></i>
            </button>
        `;
        
        // Páginas
        const startPage = Math.max(1, this.currentPage - 2);
        const endPage = Math.min(totalPages, this.currentPage + 2);
        
        if (startPage > 1) {
            paginationHtml += `<button class="pagination-btn" onclick="logsModule.goToPage(1)">1</button>`;
            if (startPage > 2) {
                paginationHtml += `<span class="pagination-ellipsis">...</span>`;
            }
        }
        
        for (let i = startPage; i <= endPage; i++) {
            paginationHtml += `
                <button class="pagination-btn ${i === this.currentPage ? 'active' : ''}" 
                        onclick="logsModule.goToPage(${i})">
                    ${i}
                </button>
            `;
        }
        
        if (endPage < totalPages) {
            if (endPage < totalPages - 1) {
                paginationHtml += `<span class="pagination-ellipsis">...</span>`;
            }
            paginationHtml += `<button class="pagination-btn" onclick="logsModule.goToPage(${totalPages})">${totalPages}</button>`;
        }
        
        // Botón siguiente
        paginationHtml += `
            <button class="pagination-btn ${this.currentPage === totalPages ? 'disabled' : ''}" 
                    onclick="logsModule.goToPage(${this.currentPage + 1})"
                    ${this.currentPage === totalPages ? 'disabled' : ''}>
                <i class="fas fa-chevron-right"></i>
            </button>
        `;
        
        controlsContainer.innerHTML = paginationHtml;
    }
    
    /**
     * Va a una página específica
     */
    goToPage(page) {
        if (page < 1 || page > Math.ceil(this.totalLogs / this.itemsPerPage)) {
            return;
        }
        
        this.currentPage = page;
        this.loadLogs();
    }
    
    /**
     * Refresca los logs
     */
    refreshLogs() {
        this.loadLogs();
        this.loadStats();
        showSuccess('Logs actualizados correctamente');
    }
    
    /**
     * Limpia todos los filtros
     */
    clearFilters() {
        // Resetear filtros
        this.currentFilter = {
            search: '',
            level: '',
            module: '',
            timerange: '24h',
            startDate: null,
            endDate: null
        };
        
        // Actualizar UI
        document.getElementById('logs-search').value = '';
        document.getElementById('level-filter').value = '';
        document.getElementById('module-filter').value = '';
        document.getElementById('timerange-filter').value = '24h';
        document.getElementById('custom-date-range').style.display = 'none';
        
        this.currentPage = 1;
        this.loadLogs();
        
        showInfo('Filtros limpiados');
    }
    
    /**
     * Activa/desactiva el auto-refresh
     */
    toggleAutoRefresh(enabled) {
        this.isAutoRefreshEnabled = enabled;
        
        if (enabled) {
            this.autoRefreshInterval = setInterval(() => {
                this.loadLogs();
                this.loadStats();
            }, 30000); // Refresh cada 30 segundos
            
            showInfo('Auto-refresh activado (30s)');
        } else {
            if (this.autoRefreshInterval) {
                clearInterval(this.autoRefreshInterval);
                this.autoRefreshInterval = null;
            }
            showInfo('Auto-refresh desactivado');
        }
        
        // Guardar preferencia
        localStorage.setItem('logs-auto-refresh', enabled.toString());
    }
    
    /**
     * Muestra detalles de un log específico
     */
    async viewLogDetails(id) {
        try {
            const response = await AdminAPI.request('getLogDetails', { id });
            
            if (response.success) {
                const log = response.data;
                
                // Crear modal dinámico
                const modalHtml = `
                    <div class="modal-overlay" id="log-details-modal">
                        <div class="modal modal-lg">
                            <div class="modal-header">
                                <h3 class="modal-title">
                                    <i class="fas fa-info-circle"></i>
                                    Detalles del Log
                                    <span class="badge badge-${log.level}">${log.level}</span>
                                </h3>
                                <button class="modal-close" type="button" onclick="logsModule.closeLogDetails()">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                            
                            <div class="modal-body">
                                <div class="log-details-content">
                                    <div class="detail-section">
                                        <h4>Información General</h4>
                                        <div class="detail-grid">
                                            <div class="detail-item">
                                                <label>ID:</label>
                                                <span>${log.id}</span>
                                            </div>
                                            <div class="detail-item">
                                                <label>Timestamp:</label>
                                                <span>${this.formatTimestamp(log.timestamp)}</span>
                                            </div>
                                            <div class="detail-item">
                                                <label>Nivel:</label>
                                                <span class="level-badge level-${log.level}">${log.level}</span>
                                            </div>
                                            <div class="detail-item">
                                                <label>Módulo:</label>
                                                <span>${log.module}</span>
                                            </div>
                                            <div class="detail-item">
                                                <label>Usuario:</label>
                                                <span>${log.user_name || 'Sistema'}</span>
                                            </div>
                                            <div class="detail-item">
                                                <label>IP:</label>
                                                <span>${log.ip_address || '--'}</span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="detail-section">
                                        <h4>Mensaje</h4>
                                        <div class="message-box">
                                            <pre>${this.escapeHtml(log.message)}</pre>
                                        </div>
                                    </div>
                                    
                                    ${log.data ? `
                                    <div class="detail-section">
                                        <h4>Datos Adicionales</h4>
                                        <div class="data-box">
                                            <pre>${JSON.stringify(log.data, null, 2)}</pre>
                                        </div>
                                    </div>
                                    ` : ''}
                                    
                                    ${log.stack_trace ? `
                                    <div class="detail-section">
                                        <h4>Stack Trace</h4>
                                        <div class="trace-box">
                                            <pre>${this.escapeHtml(log.stack_trace)}</pre>
                                        </div>
                                    </div>
                                    ` : ''}
                                    
                                    ${log.user_agent ? `
                                    <div class="detail-section">
                                        <h4>User Agent</h4>
                                        <div class="agent-box">
                                            <pre>${this.escapeHtml(log.user_agent)}</pre>
                                        </div>
                                    </div>
                                    ` : ''}
                                </div>
                            </div>
                            
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" onclick="logsModule.closeLogDetails()">
                                    Cerrar
                                </button>
                                <button type="button" class="btn btn-info" onclick="logsModule.copyLogDetails(${id})">
                                    <i class="fas fa-copy"></i>
                                    Copiar
                                </button>
                            </div>
                        </div>
                    </div>
                `;
                
                // Insertar modal en el DOM
                document.body.insertAdjacentHTML('beforeend', modalHtml);
                
                // Mostrar modal
                const modal = document.getElementById('log-details-modal');
                if (modal) {
                    modal.style.display = 'flex';
                }
            } else {
                throw new Error(response.message || 'Error al cargar detalles');
            }
        } catch (error) {
            console.error('Error al cargar detalles del log:', error);
            showError('Error al cargar detalles: ' + error.message);
        }
    }
    
    /**
     * Cierra el modal de detalles
     */
    closeLogDetails() {
        const modal = document.getElementById('log-details-modal');
        if (modal) {
            modal.remove();
        }
    }
    
    /**
     * Copia los detalles del log
     */
    async copyLogDetails(id) {
        try {
            const log = this.logs.find(l => l.id === parseInt(id));
            if (log) {
                const details = `
Log ID: ${log.id}
Timestamp: ${this.formatTimestamp(log.timestamp)}
Nivel: ${log.level}
Módulo: ${log.module}
Usuario: ${log.user_name || 'Sistema'}
IP: ${log.ip_address || '--'}

Mensaje:
${log.message}

${log.data ? `Datos: ${JSON.stringify(log.data, null, 2)}` : ''}
                `.trim();
                
                await navigator.clipboard.writeText(details);
                showSuccess('Detalles copiados al portapapeles');
            }
        } catch (error) {
            console.error('Error al copiar detalles:', error);
            showError('Error al copiar detalles');
        }
    }
    
    /**
     * Muestra el contexto de un log (logs relacionados)
     */
    async showLogContext(id) {
        showInfo('Funcionalidad de contexto en desarrollo');
    }
    
    /**
     * Marca un log como importante
     */
    async flagLog(id) {
        try {
            const response = await AdminAPI.request('flagLog', { id });
            
            if (response.success) {
                showSuccess('Log marcado correctamente');
                this.refreshLogs();
            } else {
                throw new Error(response.message || 'Error al marcar log');
            }
        } catch (error) {
            console.error('Error al marcar log:', error);
            showError('Error al marcar log: ' + error.message);
        }
    }
    
    /**
     * Expande/contrae un mensaje largo en la tabla
     */
    toggleMessageExpand(button) {
        const row = button.closest('.log-row');
        const fullMessage = row.querySelector('.message-full');
        const icon = button.querySelector('i');
        
        if (fullMessage.style.display === 'none') {
            fullMessage.style.display = 'block';
            icon.classList.remove('fa-chevron-down');
            icon.classList.add('fa-chevron-up');
        } else {
            fullMessage.style.display = 'none';
            icon.classList.remove('fa-chevron-up');
            icon.classList.add('fa-chevron-down');
        }
    }
    
    /**
     * Muestra el monitor en tiempo real
     */
    showRealTimeMonitor() {
        showInfo('Monitor en tiempo real en desarrollo');
    }
    
    /**
     * Muestra el estado del sistema
     */
    async showSystemHealth() {
        try {
            const response = await AdminAPI.request('getSystemHealth');
            
            if (response.success) {
                const health = response.data;
                
                const healthHtml = `
                    <div class="modal-overlay" id="system-health-modal">
                        <div class="modal modal-lg">
                            <div class="modal-header">
                                <h3 class="modal-title">
                                    <i class="fas fa-heartbeat"></i>
                                    Estado del Sistema
                                    <span class="badge badge-${health.status === 'healthy' ? 'success' : 'warning'}">${health.status}</span>
                                </h3>
                                <button class="modal-close" type="button" onclick="logsModule.closeSystemHealth()">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                            
                            <div class="modal-body">
                                <div class="health-grid">
                                    <div class="health-card">
                                        <h4><i class="fas fa-server"></i> Servidor</h4>
                                        <div class="health-metrics">
                                            <div class="metric">
                                                <label>CPU:</label>
                                                <span>${health.cpu_usage}%</span>
                                                <div class="progress-bar">
                                                    <div class="progress-fill" style="width: ${health.cpu_usage}%"></div>
                                                </div>
                                            </div>
                                            <div class="metric">
                                                <label>Memoria:</label>
                                                <span>${health.memory_usage}%</span>
                                                <div class="progress-bar">
                                                    <div class="progress-fill" style="width: ${health.memory_usage}%"></div>
                                                </div>
                                            </div>
                                            <div class="metric">
                                                <label>Disco:</label>
                                                <span>${health.disk_usage}%</span>
                                                <div class="progress-bar">
                                                    <div class="progress-fill" style="width: ${health.disk_usage}%"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="health-card">
                                        <h4><i class="fas fa-database"></i> Base de Datos</h4>
                                        <div class="health-status">
                                            <div class="status-item ${health.db_status}">
                                                <i class="fas fa-circle"></i>
                                                <span>Estado: ${health.db_status}</span>
                                            </div>
                                            <div class="status-item">
                                                <i class="fas fa-clock"></i>
                                                <span>Conexiones: ${health.db_connections}</span>
                                            </div>
                                            <div class="status-item">
                                                <i class="fas fa-tachometer-alt"></i>
                                                <span>Consultas/min: ${health.queries_per_minute}</span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="health-card">
                                        <h4><i class="fas fa-globe"></i> APIs Externas</h4>
                                        <div class="apis-status">
                                            ${health.external_apis.map(api => `
                                                <div class="api-status ${api.status}">
                                                    <i class="fas fa-${api.status === 'online' ? 'check-circle' : 'times-circle'}"></i>
                                                    <span>${api.name}</span>
                                                    <small>${api.response_time}ms</small>
                                                </div>
                                            `).join('')}
                                        </div>
                                    </div>
                                    
                                    <div class="health-card">
                                        <h4><i class="fas fa-exclamation-triangle"></i> Alertas</h4>
                                        <div class="alerts-list">
                                            ${health.alerts.length === 0 ? 
                                                '<p class="no-alerts">No hay alertas activas</p>' : 
                                                health.alerts.map(alert => `
                                                    <div class="alert-item ${alert.level}">
                                                        <i class="fas fa-${alert.level === 'critical' ? 'exclamation-triangle' : 'info-circle'}"></i>
                                                        <span>${alert.message}</span>
                                                        <small>${alert.timestamp}</small>
                                                    </div>
                                                `).join('')
                                            }
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" onclick="logsModule.closeSystemHealth()">
                                    Cerrar
                                </button>
                                <button type="button" class="btn btn-info" onclick="logsModule.refreshSystemHealth()">
                                    <i class="fas fa-sync-alt"></i>
                                    Actualizar
                                </button>
                            </div>
                        </div>
                    </div>
                `;
                
                // Insertar modal
                document.body.insertAdjacentHTML('beforeend', healthHtml);
                
                // Mostrar modal
                const modal = document.getElementById('system-health-modal');
                if (modal) {
                    modal.style.display = 'flex';
                }
            } else {
                throw new Error(response.message || 'Error al obtener estado del sistema');
            }
        } catch (error) {
            console.error('Error al obtener estado del sistema:', error);
            showError('Error al obtener estado del sistema: ' + error.message);
        }
    }
    
    /**
     * Cierra el modal de estado del sistema
     */
    closeSystemHealth() {
        const modal = document.getElementById('system-health-modal');
        if (modal) {
            modal.remove();
        }
    }
    
    /**
     * Actualiza el estado del sistema
     */
    refreshSystemHealth() {
        this.closeSystemHealth();
        this.showSystemHealth();
    }
    
    /**
     * Exporta logs
     */
    async exportLogs() {
        try {
            const response = await AdminAPI.request('exportLogs', {
                format: 'csv',
                filters: this.currentFilter
            });
            
            if (response.success) {
                const blob = new Blob([response.data], { type: 'text/csv' });
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `logs-export-${new Date().toISOString().split('T')[0]}.csv`;
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                URL.revokeObjectURL(url);
                
                showSuccess('Logs exportados correctamente');
            } else {
                throw new Error(response.message || 'Error al exportar logs');
            }
        } catch (error) {
            console.error('Error al exportar logs:', error);
            showError('Error al exportar logs: ' + error.message);
        }
    }
    
    /**
     * Muestra modal de filtros avanzados
     */
    showFiltersModal() {
        showInfo('Filtros avanzados en desarrollo');
    }
    
    /**
     * Formatea la hora para timeline
     */
    formatTime(timestamp) {
        const date = new Date(timestamp);
        return date.toLocaleTimeString('es-ES', { 
            hour: '2-digit', 
            minute: '2-digit', 
            second: '2-digit' 
        });
    }
    
    /**
     * Formatea timestamp completo
     */
    formatTimestamp(timestamp) {
        const date = new Date(timestamp);
        return date.toLocaleDateString('es-ES') + ' ' + date.toLocaleTimeString('es-ES');
    }
    
    /**
     * Obtiene tiempo relativo
     */
    getRelativeTime(timestamp) {
        const now = new Date();
        const date = new Date(timestamp);
        const diffTime = Math.abs(now - date);
        const diffMinutes = Math.floor(diffTime / (1000 * 60));
        const diffHours = Math.floor(diffTime / (1000 * 60 * 60));
        const diffDays = Math.floor(diffTime / (1000 * 60 * 60 * 24));
        
        if (diffMinutes < 1) {
            return 'Ahora';
        } else if (diffMinutes < 60) {
            return `Hace ${diffMinutes}m`;
        } else if (diffHours < 24) {
            return `Hace ${diffHours}h`;
        } else {
            return `Hace ${diffDays}d`;
        }
    }
    
    /**
     * Trunca mensaje largo
     */
    truncateMessage(message, maxLength = 150) {
        if (!message) return '';
        
        if (message.length <= maxLength) {
            return message;
        }
        
        return message.substring(0, maxLength) + '...';
    }
    
    /**
     * Obtiene versión corta del user agent
     */
    getUserAgentShort(userAgent) {
        if (!userAgent) return '--';
        
        // Detectar navegadores comunes
        if (userAgent.includes('Chrome')) return 'Chrome';
        if (userAgent.includes('Firefox')) return 'Firefox';
        if (userAgent.includes('Safari') && !userAgent.includes('Chrome')) return 'Safari';
        if (userAgent.includes('Edge')) return 'Edge';
        
        // Truncar si es muy largo
        return userAgent.length > 20 ? userAgent.substring(0, 20) + '...' : userAgent;
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
        
        return text.replace(/[&<>"']/g, (m) => map[m]);
    }
    
    /**
     * Limpia recursos al cerrar
     */
    cleanup() {
        if (this.autoRefreshInterval) {
            clearInterval(this.autoRefreshInterval);
        }
        
        if (this.realTimeSocket) {
            this.realTimeSocket.close();
        }
        
        // Limpiar charts
        Object.values(this.charts).forEach(chart => {
            if (chart && typeof chart.destroy === 'function') {
                chart.destroy();
            }
        });
    }
}

// Inicializar módulo cuando se carga la página
let logsModule;

document.addEventListener('DOMContentLoaded', function() {
    // Solo inicializar si estamos en el tab de logs
    if (typeof tabManager !== 'undefined') {
        tabManager.on('tabActivated', (tabId) => {
            if (tabId === 'logs-tab' && !logsModule) {
                logsModule = new LogsModule();
            }
        });
        
        // Si ya estamos en el tab de logs, inicializar inmediatamente
        if (tabManager.activeTab === 'logs-tab') {
            logsModule = new LogsModule();
        }
    } else {
        // Fallback si no hay tabManager
        logsModule = new LogsModule();
    }
});

// Exportar para uso global
window.logsModule = logsModule;