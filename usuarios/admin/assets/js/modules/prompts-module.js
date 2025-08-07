/**
 * ==========================================================================
 * MÓDULO PROMPTS - JavaScript
 * Kavia Hoteles Panel de Administración
 * Gestión avanzada de prompts para IA
 * ==========================================================================
 */

class PromptsModule {
    constructor() {
        this.prompts = [];
        this.currentPrompt = null;
        this.currentFilter = {
            search: '',
            category: '',
            status: '',
            language: ''
        };
        this.currentPage = 1;
        this.itemsPerPage = 20;
        this.totalPrompts = 0;
        this.viewMode = 'grid';
        this.currentEditorTab = 'basic';
        
        // Variables para el editor
        this.detectedVariables = new Set();
        this.customVariables = [];
        
        // Testing
        this.testHistory = [];
        
        // Auto-save
        this.autoSaveInterval = null;
        this.hasUnsavedChanges = false;
        
        // Configuración de categorías
        this.categoryConfig = {
            sentiment: { name: 'Análisis de Sentimiento', icon: 'fas fa-heart', color: '#e74c3c' },
            extraction: { name: 'Extracción de Datos', icon: 'fas fa-filter', color: '#3498db' },
            translation: { name: 'Traducción', icon: 'fas fa-language', color: '#9b59b6' },
            classification: { name: 'Clasificación', icon: 'fas fa-tags', color: '#e67e22' },
            summary: { name: 'Resumen', icon: 'fas fa-compress-alt', color: '#27ae60' },
            custom: { name: 'Personalizado', icon: 'fas fa-cogs', color: '#95a5a6' }
        };
        
        // Idiomas soportados
        this.languageConfig = {
            es: { name: 'Español', flag: '🇪🇸' },
            en: { name: 'English', flag: '🇺🇸' },
            fr: { name: 'Français', flag: '🇫🇷' },
            de: { name: 'Deutsch', flag: '🇩🇪' },
            it: { name: 'Italiano', flag: '🇮🇹' },
            pt: { name: 'Português', flag: '🇵🇹' }
        };
        
        this.init();
    }
    
    /**
     * Inicializa el módulo
     */
    init() {
        console.log('🚀 Inicializando Módulo de Prompts...');
        
        try {
            // Configurar event listeners
            this.setupEventListeners();
            
            // Cargar prompts iniciales
            this.loadPrompts();
            
            // Cargar estadísticas
            this.loadStats();
            
            console.log('✅ Módulo de Prompts inicializado correctamente');
        } catch (error) {
            console.error('❌ Error al inicializar módulo de prompts:', error);
            showError('Error al inicializar el módulo de prompts: ' + error.message);
        }
    }
    
    /**
     * Configura los event listeners
     */
    setupEventListeners() {
        // Búsqueda con debounce
        const searchInput = document.getElementById('prompts-search');
        if (searchInput) {
            let searchTimeout;
            searchInput.addEventListener('input', (e) => {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    this.currentFilter.search = e.target.value;
                    this.currentPage = 1;
                    this.loadPrompts();
                }, 300);
            });
        }
        
        // Filtros
        const categoryFilter = document.getElementById('category-filter');
        if (categoryFilter) {
            categoryFilter.addEventListener('change', (e) => {
                this.currentFilter.category = e.target.value;
                this.currentPage = 1;
                this.loadPrompts();
            });
        }
        
        const statusFilter = document.getElementById('status-filter');
        if (statusFilter) {
            statusFilter.addEventListener('change', (e) => {
                this.currentFilter.status = e.target.value;
                this.currentPage = 1;
                this.loadPrompts();
            });
        }
        
        const languageFilter = document.getElementById('language-filter');
        if (languageFilter) {
            languageFilter.addEventListener('change', (e) => {
                this.currentFilter.language = e.target.value;
                this.currentPage = 1;
                this.loadPrompts();
            });
        }
        
        // Formulario de prompt
        const promptForm = document.getElementById('prompt-form');
        if (promptForm) {
            promptForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.savePrompt();
            });
        }
        
        // Auto-save en el editor
        const promptContent = document.getElementById('prompt-content');
        if (promptContent) {
            promptContent.addEventListener('input', () => {
                this.hasUnsavedChanges = true;
                this.detectVariables();
                this.updateEditorStats();
                this.scheduleAutoSave();
            });
        }
        
        // Range inputs en configuración avanzada
        this.setupRangeInputs();
        
        // Keyboard shortcuts
        document.addEventListener('keydown', (e) => {
            if (e.ctrlKey || e.metaKey) {
                switch (e.key) {
                    case 'n':
                        e.preventDefault();
                        this.showCreatePromptModal();
                        break;
                    case 's':
                        e.preventDefault();
                        if (this.currentPrompt) {
                            this.savePrompt();
                        }
                        break;
                    case 't':
                        e.preventDefault();
                        if (this.currentPrompt) {
                            this.testPromptContent();
                        }
                        break;
                }
            }
            
            if (e.key === 'Escape') {
                this.closePromptModal();
                this.closePreviewModal();
            }
        });
    }
    
    /**
     * Configura los range inputs con actualización de valores
     */
    setupRangeInputs() {
        const ranges = ['model-temperature', 'top-p', 'frequency-penalty'];
        
        ranges.forEach(id => {
            const range = document.getElementById(id);
            if (range) {
                const valueDisplay = range.parentElement.querySelector('.range-value');
                
                range.addEventListener('input', (e) => {
                    if (valueDisplay) {
                        valueDisplay.textContent = e.target.value;
                    }
                    this.hasUnsavedChanges = true;
                });
            }
        });
    }
    
    /**
     * Carga la lista de prompts
     */
    async loadPrompts() {
        console.log('📝 Iniciando carga de prompts...');
        
        try {
            const loadingElement = document.getElementById('prompts-loading');
            const container = document.getElementById('prompts-grid');
            
            console.log('🔍 Elementos encontrados:', {
                loading: !!loadingElement,
                container: !!container
            });
            
            if (loadingElement) {
                loadingElement.style.display = 'flex';
            }
            
            const params = {
                page: this.currentPage,
                limit: this.itemsPerPage,
                search: this.currentFilter.search,
                category: this.currentFilter.category,
                status: this.currentFilter.status,
                language: this.currentFilter.language
            };
            
            console.log('📤 Parámetros de consulta:', params);
            
            const response = await apiClient.call('getPrompts', params);
            
            console.log('📥 Respuesta recibida:', response);
            
            if (response && response.success) {
                this.prompts = response.data?.prompts || [];
                this.totalPrompts = response.data?.total || 0;
                
                console.log(`✅ Prompts cargados: ${this.prompts.length} de ${this.totalPrompts}`);
                
                this.renderPrompts();
                this.updatePagination();
                
                // Actualizar contador en header
                const totalCountElement = document.getElementById('prompts-total-count');
                if (totalCountElement) {
                    totalCountElement.textContent = this.totalPrompts;
                }
            } else {
                throw new Error(response?.error || response?.message || 'Error desconocido al cargar prompts');
            }
        } catch (error) {
            console.error('❌ Error al cargar prompts:', error);
            
            // Mostrar error en el contenedor
            const container = document.getElementById('prompts-grid');
            if (container) {
                container.innerHTML = `
                    <div style="text-align: center; padding: 40px; color: #dc3545;">
                        <i class="fas fa-exclamation-triangle" style="font-size: 3rem; margin-bottom: 20px;"></i>
                        <h3>Error al cargar prompts</h3>
                        <p>${error.message}</p>
                        <button class="btn btn-primary" onclick="promptsModule.loadPrompts()" style="margin-top: 15px;">
                            <i class="fas fa-refresh"></i> Reintentar
                        </button>
                    </div>
                `;
            }
            
            if (window.showError) {
                showError('Error al cargar prompts: ' + error.message);
            }
        } finally {
            const loadingElement = document.getElementById('prompts-loading');
            if (loadingElement) {
                loadingElement.style.display = 'none';
                console.log('🔄 Elemento de carga ocultado');
            }
        }
    }
    
    /**
     * Carga las estadísticas del módulo
     */
    async loadStats() {
        try {
            const response = await apiClient.call('getPromptsStats');
            
            if (response.success) {
                const stats = response.data;
                
                // Actualizar estadísticas
                this.updateStatElement('total-prompts-stat', stats.total);
                this.updateStatElement('active-prompts-stat', stats.active);
                this.updateStatElement('ai-prompts-stat', stats.ai_prompts);
                this.updateStatElement('usage-count-stat', stats.total_usage);
                this.updateStatElement('languages-stat', stats.languages);
            }
        } catch (error) {
            console.error('Error al cargar estadísticas:', error);
        }
    }
    
    /**
     * Actualiza un elemento de estadística
     */
    updateStatElement(elementId, value) {
        const element = document.getElementById(elementId);
        if (element) {
            // Animación de contador
            const startValue = parseInt(element.textContent) || 0;
            const endValue = parseInt(value) || 0;
            const duration = 1000;
            const step = (endValue - startValue) / (duration / 16);
            
            let currentValue = startValue;
            const timer = setInterval(() => {
                currentValue += step;
                if ((step > 0 && currentValue >= endValue) || (step < 0 && currentValue <= endValue)) {
                    element.textContent = endValue;
                    clearInterval(timer);
                } else {
                    element.textContent = Math.round(currentValue);
                }
            }, 16);
        }
    }
    
    /**
     * Renderiza la lista de prompts según el modo de vista
     */
    renderPrompts() {
        if (this.viewMode === 'grid') {
            this.renderGridView();
        } else {
            this.renderListView();
        }
        
        // Mostrar vista móvil en dispositivos pequeños
        if (window.innerWidth <= 768) {
            this.renderMobileView();
        }
    }
    
    /**
     * Renderiza la vista en cuadrícula
     */
    renderGridView() {
        const container = document.getElementById('prompts-grid');
        if (!container) return;
        
        container.classList.remove('list-view');
        
        if (this.prompts.length === 0) {
            container.innerHTML = this.getEmptyState();
            return;
        }
        
        const template = document.getElementById('prompt-card-template');
        if (!template) return;
        
        let html = '';
        
        this.prompts.forEach(prompt => {
            const categoryConfig = this.categoryConfig[prompt.category] || this.categoryConfig.custom;
            const languageConfig = this.languageConfig[prompt.language] || { name: prompt.language };
            
            const cardHtml = template.innerHTML
                .replace(/{id}/g, prompt.id)
                .replace(/{name}/g, this.escapeHtml(prompt.name))
                .replace(/{version}/g, prompt.version || '1.0')
                .replace(/{description}/g, this.escapeHtml(prompt.description || ''))
                .replace(/{category_icon}/g, categoryConfig.icon)
                .replace(/{category_name}/g, categoryConfig.name)
                .replace(/{language}/g, languageConfig.name)
                .replace(/{usage_count}/g, prompt.usage_count || 0)
                .replace(/{last_used}/g, this.formatDate(prompt.last_used))
                .replace(/{status}/g, prompt.status)
                .replace(/{status_text}/g, this.getStatusText(prompt.status))
                .replace(/{preview_text}/g, this.getPreviewText(prompt.content));
            
            html += cardHtml;
        });
        
        container.innerHTML = html;
    }
    
    /**
     * Renderiza la vista en lista
     */
    renderListView() {
        const container = document.getElementById('prompts-grid');
        if (!container) return;
        
        container.classList.add('list-view');
        
        if (this.prompts.length === 0) {
            container.innerHTML = this.getEmptyState();
            return;
        }
        
        const template = document.getElementById('prompt-row-template');
        if (!template) return;
        
        let html = '';
        
        this.prompts.forEach(prompt => {
            const categoryConfig = this.categoryConfig[prompt.category] || this.categoryConfig.custom;
            const languageConfig = this.languageConfig[prompt.language] || { name: prompt.language };
            
            const rowHtml = template.innerHTML
                .replace(/{id}/g, prompt.id)
                .replace(/{name}/g, this.escapeHtml(prompt.name))
                .replace(/{version}/g, prompt.version || '1.0')
                .replace(/{description}/g, this.escapeHtml(prompt.description || ''))
                .replace(/{category_icon}/g, categoryConfig.icon)
                .replace(/{category_name}/g, categoryConfig.name)
                .replace(/{language_name}/g, languageConfig.name)
                .replace(/{usage_count}/g, prompt.usage_count || 0)
                .replace(/{status}/g, prompt.status)
                .replace(/{status_text}/g, this.getStatusText(prompt.status))
                .replace(/{updated_at}/g, this.formatDate(prompt.updated_at))
                .replace(/{tags_html}/g, this.generateTagsHtml(prompt.tags));
            
            html += rowHtml;
        });
        
        container.innerHTML = html;
    }
    
    /**
     * Renderiza la vista móvil
     */
    renderMobileView() {
        const container = document.getElementById('prompts-mobile');
        if (!container) return;
        
        if (this.prompts.length === 0) {
            container.innerHTML = this.getEmptyState();
            return;
        }
        
        const template = document.getElementById('prompt-mobile-template');
        if (!template) return;
        
        let html = '';
        
        this.prompts.forEach(prompt => {
            const categoryConfig = this.categoryConfig[prompt.category] || this.categoryConfig.custom;
            const languageConfig = this.languageConfig[prompt.language] || { name: prompt.language };
            
            const cardHtml = template.innerHTML
                .replace(/{id}/g, prompt.id)
                .replace(/{name}/g, this.escapeHtml(prompt.name))
                .replace(/{version}/g, prompt.version || '1.0')
                .replace(/{description}/g, this.escapeHtml(prompt.description || ''))
                .replace(/{category_icon}/g, categoryConfig.icon)
                .replace(/{category_name}/g, categoryConfig.name)
                .replace(/{language}/g, languageConfig.name)
                .replace(/{usage_count}/g, prompt.usage_count || 0)
                .replace(/{last_used}/g, this.formatDate(prompt.last_used))
                .replace(/{status}/g, prompt.status)
                .replace(/{status_text}/g, this.getStatusText(prompt.status))
                .replace(/{preview_text}/g, this.getPreviewText(prompt.content));
            
            html += cardHtml;
        });
        
        container.innerHTML = html;
    }
    
    /**
     * Obtiene el estado vacío
     */
    getEmptyState() {
        return `
            <div class="empty-state">
                <i class="fas fa-file-alt"></i>
                <h3>No hay prompts disponibles</h3>
                <p>Crea tu primer prompt para empezar a procesar reseñas con IA</p>
                <button class="btn btn-primary" onclick="promptsModule.showCreatePromptModal()">
                    <i class="fas fa-plus"></i>
                    Crear Primer Prompt
                </button>
            </div>
        `;
    }
    
    /**
     * Obtiene el texto de estado
     */
    getStatusText(status) {
        const statusTexts = {
            active: 'Activo',
            draft: 'Borrador',
            archived: 'Archivado'
        };
        
        return statusTexts[status] || status;
    }
    
    /**
     * Obtiene un preview del contenido del prompt
     */
    getPreviewText(content, maxLength = 100) {
        if (!content) return '';
        
        const cleaned = content.replace(/\s+/g, ' ').trim();
        if (cleaned.length <= maxLength) {
            return cleaned;
        }
        
        return cleaned.substring(0, maxLength) + '...';
    }
    
    /**
     * Genera el HTML para los tags
     */
    generateTagsHtml(tags) {
        if (!tags || !Array.isArray(tags)) return '';
        
        return tags.map(tag => 
            `<span class="tag">${this.escapeHtml(tag)}</span>`
        ).join('');
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
        
        const activeButton = document.getElementById(mode === 'grid' ? 'grid-view-btn' : 'list-view-btn');
        if (activeButton) {
            activeButton.classList.add('toggle-active');
        }
        
        // Re-renderizar
        this.renderPrompts();
        
        // Guardar preferencia
        localStorage.setItem('prompts-view-mode', mode);
    }
    
    /**
     * Actualiza la paginación
     */
    updatePagination() {
        const container = document.getElementById('prompts-pagination');
        const controlsContainer = document.getElementById('prompts-pagination-controls');
        const showingElement = document.getElementById('prompts-showing');
        
        if (!container || !controlsContainer) return;
        
        const totalPages = Math.ceil(this.totalPrompts / this.itemsPerPage);
        
        if (totalPages <= 1) {
            container.style.display = 'none';
            return;
        }
        
        container.style.display = 'flex';
        
        // Información de paginación
        if (showingElement) {
            const start = (this.currentPage - 1) * this.itemsPerPage + 1;
            const end = Math.min(this.currentPage * this.itemsPerPage, this.totalPrompts);
            showingElement.textContent = `Mostrando ${start}-${end} de ${this.totalPrompts} prompts`;
        }
        
        // Controles de paginación
        let paginationHtml = '';
        
        // Botón anterior
        paginationHtml += `
            <button class="pagination-btn ${this.currentPage === 1 ? 'disabled' : ''}" 
                    onclick="promptsModule.goToPage(${this.currentPage - 1})"
                    ${this.currentPage === 1 ? 'disabled' : ''}>
                <i class="fas fa-chevron-left"></i>
            </button>
        `;
        
        // Páginas
        const startPage = Math.max(1, this.currentPage - 2);
        const endPage = Math.min(totalPages, this.currentPage + 2);
        
        if (startPage > 1) {
            paginationHtml += `<button class="pagination-btn" onclick="promptsModule.goToPage(1)">1</button>`;
            if (startPage > 2) {
                paginationHtml += `<span class="pagination-ellipsis">...</span>`;
            }
        }
        
        for (let i = startPage; i <= endPage; i++) {
            paginationHtml += `
                <button class="pagination-btn ${i === this.currentPage ? 'active' : ''}" 
                        onclick="promptsModule.goToPage(${i})">
                    ${i}
                </button>
            `;
        }
        
        if (endPage < totalPages) {
            if (endPage < totalPages - 1) {
                paginationHtml += `<span class="pagination-ellipsis">...</span>`;
            }
            paginationHtml += `<button class="pagination-btn" onclick="promptsModule.goToPage(${totalPages})">${totalPages}</button>`;
        }
        
        // Botón siguiente
        paginationHtml += `
            <button class="pagination-btn ${this.currentPage === totalPages ? 'disabled' : ''}" 
                    onclick="promptsModule.goToPage(${this.currentPage + 1})"
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
        if (page < 1 || page > Math.ceil(this.totalPrompts / this.itemsPerPage)) {
            return;
        }
        
        this.currentPage = page;
        this.loadPrompts();
    }
    
    /**
     * Refresca la lista de prompts
     */
    refreshPrompts() {
        this.loadPrompts();
        this.loadStats();
        showSuccess('Prompts actualizados correctamente');
    }
    
    /**
     * Muestra el modal de creación de prompt
     */
    showCreatePromptModal() {
        this.currentPrompt = null;
        this.resetPromptForm();
        
        const modal = document.getElementById('prompt-modal');
        const title = document.getElementById('prompt-modal-title');
        
        if (title) {
            title.textContent = 'Nuevo Prompt';
        }
        
        if (modal) {
            modal.style.display = 'flex';
            
            // Focus en el nombre
            setTimeout(() => {
                const nameInput = document.getElementById('prompt-name');
                if (nameInput) nameInput.focus();
            }, 100);
        }
        
        this.startAutoSave();
    }
    
    /**
     * Muestra el modal de edición de prompt
     */
    async editPrompt(id) {
        try {
            const response = await apiClient.call('getPrompt', { id });
            
            if (response.success) {
                this.currentPrompt = response.data;
                this.populatePromptForm(this.currentPrompt);
                
                const modal = document.getElementById('prompt-modal');
                const title = document.getElementById('prompt-modal-title');
                
                if (title) {
                    title.textContent = `Editar: ${this.currentPrompt.name}`;
                }
                
                if (modal) {
                    modal.style.display = 'flex';
                }
                
                this.startAutoSave();
            } else {
                throw new Error(response.message || 'Error al cargar prompt');
            }
        } catch (error) {
            console.error('Error al cargar prompt:', error);
            showError('Error al cargar prompt: ' + error.message);
        }
    }
    
    /**
     * Resetea el formulario de prompt
     */
    resetPromptForm() {
        const form = document.getElementById('prompt-form');
        if (form) {
            form.reset();
        }
        
        // Resetear valores por defecto
        const statusSelect = document.getElementById('prompt-status');
        if (statusSelect) statusSelect.value = 'draft';
        
        const versionInput = document.getElementById('prompt-version');
        if (versionInput) versionInput.value = '1.0';
        
        const languageSelect = document.getElementById('prompt-language');
        if (languageSelect) languageSelect.value = 'es';
        
        // Limpiar variables
        this.detectedVariables.clear();
        this.customVariables = [];
        
        // Resetear configuración avanzada
        this.resetAdvancedConfig();
        
        // Volver al primer tab
        this.switchEditorTab('basic');
        
        this.hasUnsavedChanges = false;
    }
    
    /**
     * Resetea la configuración avanzada a valores por defecto
     */
    resetAdvancedConfig() {
        const defaults = {
            'model-temperature': 0.7,
            'max-tokens': 1000,
            'top-p': 0.9,
            'frequency-penalty': 0,
            'retry-attempts': '2',
            'timeout-seconds': 30,
            'log-level': 'info',
            'retention-days': 30
        };
        
        Object.entries(defaults).forEach(([id, value]) => {
            const element = document.getElementById(id);
            if (element) {
                element.value = value;
                
                // Actualizar display de range
                if (element.type === 'range') {
                    const valueDisplay = element.parentElement.querySelector('.range-value');
                    if (valueDisplay) {
                        valueDisplay.textContent = value;
                    }
                }
            }
        });
        
        // Checkboxes
        const checkboxes = {
            'track-usage': true,
            'enable-content-filter': false,
            'validate-output-format': false,
            'log-requests': false
        };
        
        Object.entries(checkboxes).forEach(([id, checked]) => {
            const element = document.getElementById(id);
            if (element) {
                element.checked = checked;
            }
        });
    }
    
    /**
     * Popula el formulario con datos de un prompt
     */
    populatePromptForm(prompt) {
        // Información básica
        this.setFormValue('prompt-name', prompt.name);
        this.setFormValue('prompt-category', prompt.category);
        this.setFormValue('prompt-language', prompt.language);
        this.setFormValue('prompt-description', prompt.description);
        this.setFormValue('prompt-status', prompt.status);
        this.setFormValue('prompt-version', prompt.version);
        this.setFormValue('prompt-tags', prompt.tags ? prompt.tags.join(', ') : '');
        
        // Contenido
        this.setFormValue('prompt-content', prompt.content);
        
        // Configuración avanzada si existe
        if (prompt.config) {
            const config = prompt.config;
            
            this.setFormValue('model-temperature', config.temperature || 0.7);
            this.setFormValue('max-tokens', config.max_tokens || 1000);
            this.setFormValue('top-p', config.top_p || 0.9);
            this.setFormValue('frequency-penalty', config.frequency_penalty || 0);
            this.setFormValue('retry-attempts', config.retry_attempts || '2');
            this.setFormValue('timeout-seconds', config.timeout_seconds || 30);
            this.setFormValue('log-level', config.log_level || 'info');
            this.setFormValue('retention-days', config.retention_days || 30);
            
            // Checkboxes
            this.setCheckboxValue('track-usage', config.track_usage !== false);
            this.setCheckboxValue('enable-content-filter', config.enable_content_filter || false);
            this.setCheckboxValue('validate-output-format', config.validate_output_format || false);
            this.setCheckboxValue('log-requests', config.log_requests || false);
        }
        
        // Variables personalizadas
        if (prompt.custom_variables) {
            this.customVariables = prompt.custom_variables;
        }
        
        // Detectar variables en el contenido
        this.detectVariables();
        this.updateEditorStats();
        
        // Actualizar información del footer
        this.updatePromptFooterInfo(prompt);
    }
    
    /**
     * Establece el valor de un campo del formulario
     */
    setFormValue(id, value) {
        const element = document.getElementById(id);
        if (element && value !== undefined && value !== null) {
            element.value = value;
            
            // Actualizar display de range si aplica
            if (element.type === 'range') {
                const valueDisplay = element.parentElement.querySelector('.range-value');
                if (valueDisplay) {
                    valueDisplay.textContent = value;
                }
            }
        }
    }
    
    /**
     * Establece el valor de un checkbox
     */
    setCheckboxValue(id, checked) {
        const element = document.getElementById(id);
        if (element) {
            element.checked = !!checked;
        }
    }
    
    /**
     * Actualiza la información del footer del modal
     */
    updatePromptFooterInfo(prompt) {
        const lastSavedElement = document.getElementById('prompt-last-saved');
        const usageCountElement = document.getElementById('prompt-usage-count');
        
        if (lastSavedElement && prompt.updated_at) {
            lastSavedElement.textContent = `Guardado: ${this.formatDate(prompt.updated_at)}`;
        }
        
        if (usageCountElement) {
            const count = prompt.usage_count || 0;
            usageCountElement.textContent = `${count} ${count === 1 ? 'uso' : 'usos'}`;
        }
    }
    
    /**
     * Cambia de tab en el editor
     */
    switchEditorTab(tabName) {
        // Actualizar botones
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        
        const activeButton = document.querySelector(`[data-tab="${tabName}"]`);
        if (activeButton) {
            activeButton.classList.add('active');
        }
        
        // Mostrar contenido
        document.querySelectorAll('.tab-content').forEach(content => {
            content.classList.remove('active');
        });
        
        const activeContent = document.getElementById(`${tabName}-tab`);
        if (activeContent) {
            activeContent.classList.add('active');
        }
        
        this.currentEditorTab = tabName;
        
        // Acciones específicas por tab
        switch (tabName) {
            case 'content':
                this.updateEditorStats();
                break;
            case 'variables':
                this.updateVariablesTab();
                break;
            case 'testing':
                this.updateTestingTab();
                break;
        }
    }
    
    /**
     * Detecta variables en el contenido del prompt
     */
    detectVariables() {
        const contentElement = document.getElementById('prompt-content');
        if (!contentElement) return;
        
        const content = contentElement.value;
        const variableRegex = /{([^}]+)}/g;
        const matches = content.matchAll(variableRegex);
        
        this.detectedVariables.clear();
        
        for (const match of matches) {
            const variableName = match[1].trim();
            if (variableName) {
                this.detectedVariables.add(variableName);
            }
        }
        
        this.updateVariablesTab();
    }
    
    /**
     * Actualiza las estadísticas del editor
     */
    updateEditorStats() {
        const contentElement = document.getElementById('prompt-content');
        if (!contentElement) return;
        
        const content = contentElement.value;
        const chars = content.length;
        const tokens = Math.ceil(chars / 4); // Aproximación simple
        const variables = this.detectedVariables.size;
        
        this.updateStatText('content-chars', `${chars} caracteres`);
        this.updateStatText('content-tokens', `~${tokens} tokens`);
        this.updateStatText('content-variables', `${variables} variables`);
    }
    
    /**
     * Actualiza un texto de estadística
     */
    updateStatText(id, text) {
        const element = document.getElementById(id);
        if (element) {
            element.textContent = text;
        }
    }
    
    /**
     * Actualiza el tab de variables
     */
    updateVariablesTab() {
        this.renderDetectedVariables();
        this.renderCustomVariables();
        this.updateVariablesPreview();
    }
    
    /**
     * Renderiza las variables detectadas
     */
    renderDetectedVariables() {
        const container = document.getElementById('detected-variables');
        if (!container) return;
        
        if (this.detectedVariables.size === 0) {
            container.innerHTML = '<p class="text-gray">No se han detectado variables en el contenido</p>';
            return;
        }
        
        let html = '';
        this.detectedVariables.forEach(variable => {
            html += `
                <div class="variable-item detected" data-variable="${variable}">
                    <code>{${variable}}</code>
                    <small>Detectada automáticamente</small>
                </div>
            `;
        });
        
        container.innerHTML = html;
    }
    
    /**
     * Renderiza las variables personalizadas
     */
    renderCustomVariables() {
        const container = document.getElementById('custom-variables');
        if (!container) return;
        
        if (this.customVariables.length === 0) {
            container.innerHTML = '<p class="text-gray">No hay variables personalizadas definidas</p>';
            return;
        }
        
        const template = document.getElementById('custom-variable-template');
        if (!template) return;
        
        let html = '';
        this.customVariables.forEach(variable => {
            const variableHtml = template.innerHTML
                .replace(/{name}/g, variable.name);
            
            html += variableHtml;
        });
        
        container.innerHTML = html;
        
        // Poblar datos
        this.customVariables.forEach((variable, index) => {
            const variableElement = container.children[index];
            if (variableElement) {
                const typeSelect = variableElement.querySelector('.variable-type');
                const descInput = variableElement.querySelector('.variable-description');
                const defaultInput = variableElement.querySelector('.variable-default');
                
                if (typeSelect) typeSelect.value = variable.type || 'text';
                if (descInput) descInput.value = variable.description || '';
                if (defaultInput) defaultInput.value = variable.default_value || '';
            }
        });
    }
    
    /**
     * Actualiza la vista previa de variables
     */
    updateVariablesPreview() {
        const container = document.getElementById('variables-preview-content');
        const contentElement = document.getElementById('prompt-content');
        
        if (!container || !contentElement) return;
        
        let content = contentElement.value;
        
        // Reemplazar variables con valores de ejemplo
        const examples = {
            review_text: 'El hotel estaba muy bien ubicado y el personal fue muy amable...',
            hotel_name: 'Hotel Ejemplo Plaza',
            user_language: 'es',
            date: new Date().toLocaleDateString(),
            rating: '4.5',
            guest_name: 'Juan Pérez'
        };
        
        // Combinar variables detectadas y personalizadas
        const allVariables = new Set([...this.detectedVariables]);
        this.customVariables.forEach(v => allVariables.add(v.name));
        
        allVariables.forEach(variable => {
            const example = examples[variable] || `[${variable}]`;
            const regex = new RegExp(`{${variable}}`, 'g');
            content = content.replace(regex, example);
        });
        
        container.textContent = content || 'El preview se mostrará aquí cuando agregues contenido...';
    }
    
    /**
     * Agrega una variable personalizada
     */
    addCustomVariable() {
        const variable = {
            name: 'nueva_variable',
            type: 'text',
            description: '',
            default_value: ''
        };
        
        this.customVariables.push(variable);
        this.renderCustomVariables();
        this.hasUnsavedChanges = true;
    }
    
    /**
     * Elimina una variable personalizada
     */
    removeCustomVariable(buttonElement) {
        const variableElement = buttonElement.closest('.variable-item');
        const variableName = variableElement.dataset.variable;
        
        this.customVariables = this.customVariables.filter(v => v.name !== variableName);
        this.renderCustomVariables();
        this.hasUnsavedChanges = true;
    }
    
    /**
     * Actualiza el tab de testing
     */
    async updateTestingTab() {
        await this.loadTestProviders();
        this.generateTestInputs();
        this.renderTestHistory();
    }
    
    /**
     * Carga los proveedores de IA para testing
     */
    async loadTestProviders() {
        try {
            const response = await apiClient.call('getAIProviders', { status: 'active' });
            
            if (response.success) {
                const select = document.getElementById('test-provider');
                if (select) {
                    let html = '<option value="">Seleccionar proveedor IA</option>';
                    
                    response.data.forEach(provider => {
                        html += `<option value="${provider.id}">${provider.name} (${provider.type})</option>`;
                    });
                    
                    select.innerHTML = html;
                }
            }
        } catch (error) {
            console.error('Error al cargar proveedores:', error);
        }
    }
    
    /**
     * Genera los inputs de prueba basados en variables
     */
    generateTestInputs() {
        const container = document.getElementById('test-inputs-container');
        if (!container) return;
        
        const template = document.getElementById('test-input-template');
        if (!template) return;
        
        let html = '';
        
        // Generar inputs para variables detectadas
        this.detectedVariables.forEach(variable => {
            const inputHtml = template.innerHTML
                .replace(/{name}/g, variable)
                .replace(/{label}/g, this.formatVariableName(variable))
                .replace(/{type}/g, 'text')
                .replace(/{placeholder}/g, `Valor para {${variable}}`)
                .replace(/{default_value}/g, this.getVariableExample(variable))
                .replace(/{description}/g, `Proporciona un valor para la variable ${variable}`);
            
            html += inputHtml;
        });
        
        // Inputs para variables personalizadas
        this.customVariables.forEach(variable => {
            const inputHtml = template.innerHTML
                .replace(/{name}/g, variable.name)
                .replace(/{label}/g, variable.description || this.formatVariableName(variable.name))
                .replace(/{type}/g, this.getInputType(variable.type))
                .replace(/{placeholder}/g, `Valor para {${variable.name}}`)
                .replace(/{default_value}/g, variable.default_value || '')
                .replace(/{description}/g, variable.description || '');
            
            html += inputHtml;
        });
        
        if (html === '') {
            html = '<p class="text-gray">No hay variables definidas para probar</p>';
        }
        
        container.innerHTML = html;
    }
    
    /**
     * Formatea el nombre de una variable para mostrar
     */
    formatVariableName(variable) {
        return variable
            .replace(/_/g, ' ')
            .replace(/\b\w/g, l => l.toUpperCase());
    }
    
    /**
     * Obtiene un ejemplo de valor para una variable
     */
    getVariableExample(variable) {
        const examples = {
            review_text: 'El hotel estaba muy bien ubicado y el personal fue muy amable. Las habitaciones estaban limpias y cómodas.',
            hotel_name: 'Hotel Ejemplo Plaza',
            user_language: 'es',
            date: new Date().toLocaleDateString(),
            rating: '4.5',
            guest_name: 'Juan Pérez',
            review_id: '12345',
            stay_date: '2024-01-15'
        };
        
        return examples[variable] || '';
    }
    
    /**
     * Obtiene el tipo de input HTML para un tipo de variable
     */
    getInputType(variableType) {
        const typeMap = {
            text: 'text',
            number: 'number',
            date: 'date',
            boolean: 'checkbox'
        };
        
        return typeMap[variableType] || 'text';
    }
    
    /**
     * Ejecuta una prueba del prompt
     */
    async runTest() {
        const providerSelect = document.getElementById('test-provider');
        if (!providerSelect || !providerSelect.value) {
            showError('Por favor selecciona un proveedor de IA');
            return;
        }
        
        const contentElement = document.getElementById('prompt-content');
        if (!contentElement || !contentElement.value.trim()) {
            showError('El prompt no tiene contenido para probar');
            return;
        }
        
        try {
            showInfo('Ejecutando prueba del prompt...');
            
            // Recopilar valores de variables
            const variables = {};
            
            this.detectedVariables.forEach(variable => {
                const input = document.getElementById(`test-${variable}`);
                if (input) {
                    variables[variable] = input.value;
                }
            });
            
            this.customVariables.forEach(variable => {
                const input = document.getElementById(`test-${variable.name}`);
                if (input) {
                    variables[variable.name] = input.value;
                }
            });
            
            const startTime = Date.now();
            
            const response = await apiClient.call('testPrompt', {
                provider_id: providerSelect.value,
                content: contentElement.value,
                variables: variables,
                config: this.getAdvancedConfig()
            });
            
            const endTime = Date.now();
            const responseTime = endTime - startTime;
            
            if (response.success) {
                this.displayTestResult(response.data, responseTime);
                this.addToTestHistory(response.data, responseTime);
                showSuccess('Prueba ejecutada correctamente');
            } else {
                throw new Error(response.message || 'Error en la prueba');
            }
        } catch (error) {
            console.error('Error en prueba:', error);
            showError('Error al ejecutar la prueba: ' + error.message);
            
            const resultsContainer = document.getElementById('test-results');
            if (resultsContainer) {
                resultsContainer.innerHTML = `
                    <div class="test-error">
                        <i class="fas fa-exclamation-triangle"></i>
                        <p>Error en la prueba:</p>
                        <pre>${error.message}</pre>
                    </div>
                `;
            }
        }
    }
    
    /**
     * Muestra el resultado de la prueba
     */
    displayTestResult(result, responseTime) {
        const container = document.getElementById('test-results');
        const metricsContainer = document.getElementById('test-metrics');
        
        if (container) {
            container.innerHTML = `
                <div class="test-success">
                    <div class="result-header">
                        <i class="fas fa-check-circle text-success"></i>
                        <span>Prueba exitosa</span>
                        <small>${new Date().toLocaleTimeString()}</small>
                    </div>
                    <div class="result-content">
                        <pre>${this.escapeHtml(result.response || 'Sin respuesta')}</pre>
                    </div>
                </div>
            `;
        }
        
        if (metricsContainer) {
            metricsContainer.style.display = 'grid';
            
            this.updateStatText('response-time', `${responseTime}ms`);
            this.updateStatText('tokens-used', result.tokens_used || 'N/A');
            this.updateStatText('estimated-cost', result.estimated_cost ? `$${result.estimated_cost}` : 'N/A');
        }
    }
    
    /**
     * Agrega un resultado al historial de pruebas
     */
    addToTestHistory(result, responseTime) {
        const historyEntry = {
            timestamp: new Date(),
            success: true,
            response_time: responseTime,
            tokens_used: result.tokens_used,
            estimated_cost: result.estimated_cost,
            response_preview: this.getPreviewText(result.response, 50)
        };
        
        this.testHistory.unshift(historyEntry);
        
        // Limitar historial a 10 entradas
        if (this.testHistory.length > 10) {
            this.testHistory = this.testHistory.slice(0, 10);
        }
        
        this.renderTestHistory();
    }
    
    /**
     * Renderiza el historial de pruebas
     */
    renderTestHistory() {
        const container = document.getElementById('test-history');
        if (!container) return;
        
        if (this.testHistory.length === 0) {
            container.innerHTML = '<p class="text-gray">No hay pruebas ejecutadas</p>';
            return;
        }
        
        let html = '';
        
        this.testHistory.forEach((entry, index) => {
            html += `
                <div class="history-entry">
                    <div class="history-header">
                        <i class="fas fa-${entry.success ? 'check-circle text-success' : 'exclamation-circle text-danger'}"></i>
                        <span class="history-time">${entry.timestamp.toLocaleTimeString()}</span>
                        <span class="history-metrics">${entry.response_time}ms • ${entry.tokens_used || 'N/A'} tokens</span>
                    </div>
                    <div class="history-preview">${entry.response_preview}</div>
                </div>
            `;
        });
        
        container.innerHTML = html;
    }
    
    /**
     * Obtiene la configuración avanzada del formulario
     */
    getAdvancedConfig() {
        return {
            temperature: parseFloat(document.getElementById('model-temperature')?.value) || 0.7,
            max_tokens: parseInt(document.getElementById('max-tokens')?.value) || 1000,
            top_p: parseFloat(document.getElementById('top-p')?.value) || 0.9,
            frequency_penalty: parseFloat(document.getElementById('frequency-penalty')?.value) || 0,
            retry_attempts: parseInt(document.getElementById('retry-attempts')?.value) || 2,
            timeout_seconds: parseInt(document.getElementById('timeout-seconds')?.value) || 30,
            enable_content_filter: document.getElementById('enable-content-filter')?.checked || false,
            validate_output_format: document.getElementById('validate-output-format')?.checked || false,
            track_usage: document.getElementById('track-usage')?.checked !== false,
            log_requests: document.getElementById('log-requests')?.checked || false,
            log_level: document.getElementById('log-level')?.value || 'info',
            retention_days: parseInt(document.getElementById('retention-days')?.value) || 30
        };
    }
    
    /**
     * Carga un ejemplo de prueba
     */
    loadTestExample(type) {
        const examples = {
            positive: {
                review_text: 'Excelente hotel! La habitación era muy cómoda y limpia. El personal fue extremadamente amable y servicial. El desayuno buffet tenía mucha variedad y todo estaba delicioso. La ubicación es perfecta, muy cerca de las principales atracciones. Definitivamente regresaríamos.',
                rating: '5',
                hotel_name: 'Hotel Paradise Resort',
                guest_name: 'María García'
            },
            negative: {
                review_text: 'Muy decepcionante. La habitación estaba sucia, encontramos pelos en las sábanas. El aire acondicionado no funcionaba bien y hacía mucho ruido. El servicio de recepción fue grosero y poco profesional. El wifi era muy lento. No lo recomiendo para nada.',
                rating: '2',
                hotel_name: 'Hotel Budget Inn',
                guest_name: 'Carlos López'
            },
            neutral: {
                review_text: 'El hotel está bien, cumple con lo básico. La habitación era de tamaño adecuado y estaba limpia. El personal fue correcto aunque no especialmente amable. La ubicación es buena para moverse por la ciudad. El precio está acorde con lo que ofrece.',
                rating: '3',
                hotel_name: 'Hotel Standard Plaza',
                guest_name: 'Ana Martínez'
            },
            mixed: {
                review_text: 'El hotel tiene cosas buenas y malas. La ubicación es excelente y las habitaciones son amplias. Sin embargo, el servicio de limpieza dejó mucho que desear y el desayuno era bastante limitado. El personal de recepción fue amable pero el proceso de check-in fue muy lento.',
                rating: '3.5',
                hotel_name: 'Hotel Mixed Experience',
                guest_name: 'Pedro Rodríguez'
            }
        };
        
        const example = examples[type];
        if (example) {
            Object.entries(example).forEach(([variable, value]) => {
                const input = document.getElementById(`test-${variable}`);
                if (input) {
                    input.value = value;
                }
            });
            
            showSuccess(`Ejemplo "${type}" cargado correctamente`);
        }
    }
    
    /**
     * Programa el auto-guardado
     */
    scheduleAutoSave() {
        if (this.autoSaveInterval) {
            clearTimeout(this.autoSaveInterval);
        }
        
        this.autoSaveInterval = setTimeout(() => {
            if (this.hasUnsavedChanges) {
                this.saveAsDraft();
            }
        }, 10000); // Auto-save cada 10 segundos
    }
    
    /**
     * Inicia el auto-save
     */
    startAutoSave() {
        this.hasUnsavedChanges = false;
        this.scheduleAutoSave();
    }
    
    /**
     * Guarda como borrador
     */
    async saveAsDraft() {
        const statusSelect = document.getElementById('prompt-status');
        if (statusSelect) {
            statusSelect.value = 'draft';
        }
        
        await this.savePrompt(true);
    }
    
    /**
     * Guarda el prompt
     */
    async savePrompt(isDraft = false) {
        try {
            const formData = this.collectFormData();
            
            if (!this.validatePromptData(formData)) {
                return;
            }
            
            const isCreating = !this.currentPrompt;
            const endpoint = isCreating ? 'createPrompt' : 'updatePrompt';
            
            if (!isCreating) {
                formData.id = this.currentPrompt.id;
            }
            
            if (!isDraft) {
                showInfo(isCreating ? 'Creando prompt...' : 'Actualizando prompt...');
            }
            
            const response = await apiClient.call(endpoint, formData);
            
            if (response.success) {
                this.currentPrompt = response.data;
                this.hasUnsavedChanges = false;
                
                if (!isDraft) {
                    showSuccess(isCreating ? 'Prompt creado correctamente' : 'Prompt actualizado correctamente');
                    this.closePromptModal();
                    this.refreshPrompts();
                } else {
                    this.updatePromptFooterInfo(this.currentPrompt);
                }
            } else {
                throw new Error(response.message || 'Error al guardar prompt');
            }
        } catch (error) {
            console.error('Error al guardar prompt:', error);
            if (!isDraft) {
                showError('Error al guardar prompt: ' + error.message);
            }
        }
    }
    
    /**
     * Recopila los datos del formulario
     */
    collectFormData() {
        return {
            name: document.getElementById('prompt-name')?.value || '',
            category: document.getElementById('prompt-category')?.value || '',
            language: document.getElementById('prompt-language')?.value || 'es',
            description: document.getElementById('prompt-description')?.value || '',
            status: document.getElementById('prompt-status')?.value || 'draft',
            version: document.getElementById('prompt-version')?.value || '1.0',
            tags: document.getElementById('prompt-tags')?.value ? 
                   document.getElementById('prompt-tags').value.split(',').map(t => t.trim()).filter(t => t) : [],
            content: document.getElementById('prompt-content')?.value || '',
            custom_variables: this.customVariables,
            config: this.getAdvancedConfig()
        };
    }
    
    /**
     * Valida los datos del prompt
     */
    validatePromptData(data) {
        const errors = [];
        
        if (!data.name?.trim()) {
            errors.push('El nombre es requerido');
        }
        
        if (!data.category) {
            errors.push('La categoría es requerida');
        }
        
        if (!data.content?.trim()) {
            errors.push('El contenido del prompt es requerido');
        }
        
        if (errors.length > 0) {
            showError('Errores en el formulario:\n' + errors.join('\n'));
            return false;
        }
        
        return true;
    }
    
    /**
     * Cierra el modal de prompt
     */
    closePromptModal() {
        if (this.hasUnsavedChanges) {
            if (!confirm('Tienes cambios sin guardar. ¿Estás seguro de cerrar?')) {
                return;
            }
        }
        
        const modal = document.getElementById('prompt-modal');
        if (modal) {
            modal.style.display = 'none';
        }
        
        // Limpiar auto-save
        if (this.autoSaveInterval) {
            clearTimeout(this.autoSaveInterval);
            this.autoSaveInterval = null;
        }
        
        this.currentPrompt = null;
        this.hasUnsavedChanges = false;
    }
    
    /**
     * Muestra la vista previa del prompt
     */
    showPromptPreview(id = null) {
        let content = '';
        
        if (id) {
            // Preview de un prompt existente
            const prompt = this.prompts.find(p => p.id === parseInt(id));
            if (prompt) {
                content = prompt.content || '';
            }
        } else {
            // Preview del prompt en edición
            const contentElement = document.getElementById('prompt-content');
            content = contentElement ? contentElement.value : '';
        }
        
        const modal = document.getElementById('prompt-preview-modal');
        const container = document.getElementById('prompt-preview-content');
        
        if (container) {
            // Reemplazar variables con valores de ejemplo para la vista previa
            let previewContent = content;
            
            const examples = {
                review_text: 'El hotel estaba muy bien ubicado y el personal fue muy amable. Las habitaciones estaban limpias y cómodas. El desayuno buffet tenía mucha variedad y todo estaba delicioso.',
                hotel_name: 'Hotel Ejemplo Plaza',
                user_language: 'es',
                date: new Date().toLocaleDateString(),
                rating: '4.5',
                guest_name: 'Juan Pérez'
            };
            
            Object.entries(examples).forEach(([variable, value]) => {
                const regex = new RegExp(`{${variable}}`, 'g');
                previewContent = previewContent.replace(regex, value);
            });
            
            container.textContent = previewContent || 'No hay contenido para mostrar';
        }
        
        if (modal) {
            modal.style.display = 'flex';
        }
    }
    
    /**
     * Cierra el modal de vista previa
     */
    closePreviewModal() {
        const modal = document.getElementById('prompt-preview-modal');
        if (modal) {
            modal.style.display = 'none';
        }
    }
    
    /**
     * Copia la vista previa al portapapeles
     */
    async copyPreviewToClipboard() {
        const container = document.getElementById('prompt-preview-content');
        if (!container) return;
        
        try {
            await navigator.clipboard.writeText(container.textContent);
            showSuccess('Contenido copiado al portapapeles');
        } catch (error) {
            console.error('Error al copiar:', error);
            showError('Error al copiar al portapapeles');
        }
    }
    
    /**
     * Copia un prompt
     */
    async copyPrompt(id) {
        try {
            const prompt = this.prompts.find(p => p.id === parseInt(id));
            if (prompt) {
                await navigator.clipboard.writeText(prompt.content || '');
                showSuccess('Contenido del prompt copiado al portapapeles');
            }
        } catch (error) {
            console.error('Error al copiar prompt:', error);
            showError('Error al copiar el prompt');
        }
    }
    
    /**
     * Prueba un prompt
     */
    async testPrompt(id) {
        await this.editPrompt(id);
        
        // Cambiar al tab de testing
        setTimeout(() => {
            this.switchEditorTab('testing');
        }, 500);
    }
    
    /**
     * Duplica un prompt
     */
    async duplicatePrompt(id) {
        try {
            const response = await apiClient.call('duplicatePrompt', { id });
            
            if (response.success) {
                showSuccess('Prompt duplicado correctamente');
                this.refreshPrompts();
            } else {
                throw new Error(response.message || 'Error al duplicar prompt');
            }
        } catch (error) {
            console.error('Error al duplicar prompt:', error);
            showError('Error al duplicar prompt: ' + error.message);
        }
    }
    
    /**
     * Archiva un prompt
     */
    async archivePrompt(id) {
        if (!confirm('¿Estás seguro de archivar este prompt?')) {
            return;
        }
        
        try {
            const response = await apiClient.call('updatePrompt', {
                id: id,
                status: 'archived'
            });
            
            if (response.success) {
                showSuccess('Prompt archivado correctamente');
                this.refreshPrompts();
            } else {
                throw new Error(response.message || 'Error al archivar prompt');
            }
        } catch (error) {
            console.error('Error al archivar prompt:', error);
            showError('Error al archivar prompt: ' + error.message);
        }
    }
    
    /**
     * Elimina un prompt
     */
    async deletePrompt(id) {
        if (!confirm('¿Estás seguro de eliminar este prompt? Esta acción no se puede deshacer.')) {
            return;
        }
        
        try {
            const response = await apiClient.call('deletePrompt', { id });
            
            if (response.success) {
                showSuccess('Prompt eliminado correctamente');
                this.refreshPrompts();
            } else {
                throw new Error(response.message || 'Error al eliminar prompt');
            }
        } catch (error) {
            console.error('Error al eliminar prompt:', error);
            showError('Error al eliminar prompt: ' + error.message);
        }
    }
    
    /**
     * Exporta prompts
     */
    async exportPrompts() {
        try {
            const response = await apiClient.call('exportPrompts', {
                format: 'json',
                filters: this.currentFilter
            });
            
            if (response.success) {
                const blob = new Blob([JSON.stringify(response.data, null, 2)], {
                    type: 'application/json'
                });
                
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `prompts-export-${new Date().toISOString().split('T')[0]}.json`;
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                URL.revokeObjectURL(url);
                
                showSuccess('Prompts exportados correctamente');
            } else {
                throw new Error(response.message || 'Error al exportar prompts');
            }
        } catch (error) {
            console.error('Error al exportar prompts:', error);
            showError('Error al exportar prompts: ' + error.message);
        }
    }
    
    /**
     * Importa prompts
     */
    importPrompts() {
        const input = document.createElement('input');
        input.type = 'file';
        input.accept = '.json';
        
        input.onchange = async (e) => {
            const file = e.target.files[0];
            if (!file) return;
            
            try {
                const text = await file.text();
                const data = JSON.parse(text);
                
                const response = await apiClient.call('importPrompts', { data });
                
                if (response.success) {
                    showSuccess(`${response.data.imported} prompts importados correctamente`);
                    this.refreshPrompts();
                } else {
                    throw new Error(response.message || 'Error al importar prompts');
                }
            } catch (error) {
                console.error('Error al importar prompts:', error);
                showError('Error al importar prompts: ' + error.message);
            }
        };
        
        input.click();
    }
    
    /**
     * Muestra la librería de plantillas
     */
    async showTemplatesLibrary() {
        try {
            const response = await apiClient.call('getTemplatesLibrary');
            
            if (response.success) {
                this.renderTemplatesLibrary(response.data);
            } else {
                throw new Error(response.message || 'Error al cargar biblioteca de templates');
            }
        } catch (error) {
            console.error('Error al cargar biblioteca:', error);
            showError('Error al cargar biblioteca de templates: ' + error.message);
        }
    }
    
    /**
     * Renderiza la biblioteca de templates
     */
    renderTemplatesLibrary(libraryData) {
        const modalHtml = `
            <div class="modal-overlay" id="templates-library-modal">
                <div class="modal modal-lg">
                    <div class="modal-header">
                        <h3 class="modal-title">
                            <i class="fas fa-book"></i>
                            Biblioteca de Templates
                        </h3>
                        <button class="modal-close" type="button" onclick="promptsModule.closeTemplatesLibrary()">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    
                    <div class="modal-body">
                        <div class="templates-library-content">
                            <div class="library-header">
                                <p class="library-description">
                                    Selecciona un template predefinido para comenzar rápidamente. 
                                    Los templates incluyen prompts optimizados para diferentes casos de uso.
                                </p>
                                <div class="library-stats">
                                    <span class="stat-item">
                                        <i class="fas fa-file-alt"></i>
                                        ${libraryData.metadata.total_templates} templates
                                    </span>
                                    <span class="stat-item">
                                        <i class="fas fa-layer-group"></i>
                                        ${libraryData.metadata.categories.length} categorías
                                    </span>
                                </div>
                            </div>
                            
                            <div class="templates-grid">
                                ${this.renderTemplateCards(libraryData.templates)}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        document.body.insertAdjacentHTML('beforeend', modalHtml);
    }
    
    /**
     * Renderiza las tarjetas de templates
     */
    renderTemplateCards(templates) {
        return templates.map(template => {
            const categoryConfig = this.categoryConfig[template.category] || this.categoryConfig.custom;
            
            return `
                <div class="template-card" data-template-id="${template.name.replace(/\s+/g, '-').toLowerCase()}">
                    <div class="template-header">
                        <div class="template-category">
                            <i class="${categoryConfig.icon}" style="color: ${categoryConfig.color}"></i>
                            <span>${categoryConfig.name}</span>
                        </div>
                        <div class="template-language">
                            ${this.languageConfig[template.language]?.flag || '🌐'} ${template.language.toUpperCase()}
                        </div>
                    </div>
                    
                    <div class="template-content">
                        <h4 class="template-title">${this.escapeHtml(template.name)}</h4>
                        <p class="template-description">${this.escapeHtml(template.description)}</p>
                        
                        <div class="template-features">
                            <div class="feature-item">
                                <i class="fas fa-code"></i>
                                <span>${template.variables?.length || 0} variables</span>
                            </div>
                            <div class="feature-item">
                                <i class="fas fa-tags"></i>
                                <span>${template.tags?.length || 0} tags</span>
                            </div>
                        </div>
                        
                        <div class="template-tags">
                            ${(template.tags || []).slice(0, 3).map(tag => 
                                `<span class="template-tag">${this.escapeHtml(tag)}</span>`
                            ).join('')}
                            ${template.tags?.length > 3 ? `<span class="template-tag-more">+${template.tags.length - 3} más</span>` : ''}
                        </div>
                    </div>
                    
                    <div class="template-actions">
                        <button class="btn btn-sm btn-secondary" onclick="promptsModule.previewTemplate('${template.name.replace(/'/g, '\\\\\\\\\\'')}')">
                            <i class="fas fa-eye"></i>
                            Vista previa
                        </button>
                        <button class="btn btn-sm btn-primary" onclick="promptsModule.importTemplate('${template.name.replace(/'/g, '\\\\\\\\\\'')}')">
                            <i class="fas fa-download"></i>
                            Usar Template
                        </button>
                    </div>
                </div>
            `;
        }).join('');
    }
    
    /**
     * Vista previa de un template
     */
    async previewTemplate(templateName) {
        try {
            const response = await apiClient.call('getTemplatesLibrary');
            if (response.success) {
                const template = response.data.templates.find(t => t.name === templateName);
                if (template) {
                    this.showTemplatePreview(template);
                } else {
                    showError('Template no encontrado');
                }
            }
        } catch (error) {
            console.error('Error al cargar template:', error);
            showError('Error al cargar template');
        }
    }
    
    /**
     * Muestra la vista previa de un template
     */
    showTemplatePreview(template) {
        const previewHtml = `
            <div class="modal-overlay" id="template-preview-modal">
                <div class="modal modal-lg">
                    <div class="modal-header">
                        <h3 class="modal-title">
                            <i class="fas fa-eye"></i>
                            Vista Previa: ${this.escapeHtml(template.name)}
                        </h3>
                        <button class="modal-close" type="button" onclick="promptsModule.closeTemplatePreview()">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    
                    <div class="modal-body">
                        <div class="template-preview-content">
                            <div class="preview-section">
                                <h4><i class="fas fa-info-circle"></i> Información</h4>
                                <div class="preview-info">
                                    <div class="info-row">
                                        <label>Categoría:</label>
                                        <span>${this.categoryConfig[template.category]?.name || template.category}</span>
                                    </div>
                                    <div class="info-row">
                                        <label>Idioma:</label>
                                        <span>${this.languageConfig[template.language]?.name || template.language}</span>
                                    </div>
                                    <div class="info-row">
                                        <label>Tags:</label>
                                        <span>${(template.tags || []).join(', ')}</span>
                                    </div>
                                </div>
                                <p><strong>Descripción:</strong> ${this.escapeHtml(template.description)}</p>
                            </div>
                            
                            ${template.variables && template.variables.length > 0 ? `
                            <div class="preview-section">
                                <h4><i class="fas fa-code"></i> Variables (${template.variables.length})</h4>
                                <div class="variables-list">
                                    ${template.variables.map(variable => `
                                        <div class="variable-preview-item">
                                            <code>{${variable.name}}</code>
                                            <span class="variable-type">${variable.type}</span>
                                            ${variable.required ? '<span class="variable-required">Requerida</span>' : ''}
                                            <p>${variable.description}</p>
                                        </div>
                                    `).join('')}
                                </div>
                            </div>
                            ` : ''}
                            
                            <div class="preview-section">
                                <h4><i class="fas fa-file-alt"></i> Contenido del Prompt</h4>
                                <div class="template-content-preview">
                                    <pre><code>${this.escapeHtml(template.content)}</code></pre>
                                </div>
                            </div>
                            
                            ${template.config && Object.keys(template.config).length > 0 ? `
                            <div class="preview-section">
                                <h4><i class="fas fa-cogs"></i> Configuración</h4>
                                <div class="config-preview">
                                    ${Object.entries(template.config).map(([key, value]) => `
                                        <div class="config-item">
                                            <label>${key}:</label>
                                            <span>${typeof value === 'boolean' ? (value ? 'Sí' : 'No') : value}</span>
                                        </div>
                                    `).join('')}
                                </div>
                            </div>
                            ` : ''}
                        </div>
                    </div>
                    
                    <div class="modal-footer">
                        <button class="btn btn-secondary" onclick="promptsModule.closeTemplatePreview()">
                            Cerrar
                        </button>
                        <button class="btn btn-primary" onclick="promptsModule.importTemplate('${template.name.replace(/'/g, '\\\\\\\\\\'')}'); promptsModule.closeTemplatePreview();">
                            <i class="fas fa-download"></i>
                            Usar Template
                        </button>
                    </div>
                </div>
            </div>
        `;
        
        document.body.insertAdjacentHTML('beforeend', previewHtml);
    }
    
    /**
     * Importa un template
     */
    async importTemplate(templateName) {
        try {
            const response = await apiClient.call('getTemplatesLibrary');
            if (response.success) {
                const template = response.data.templates.find(t => t.name === templateName);
                if (template) {
                    const importResponse = await apiClient.call('importTemplate', { template });
                    
                    if (importResponse.success) {
                        showSuccess('Template importado correctamente');
                        this.closeTemplatesLibrary();
                        this.refreshPrompts();
                        
                        // Abrir el template importado para edición
                        setTimeout(() => {
                            this.editPrompt(importResponse.data.id);
                        }, 500);
                    } else {
                        throw new Error(importResponse.message || 'Error al importar template');
                    }
                } else {
                    showError('Template no encontrado');
                }
            }
        } catch (error) {
            console.error('Error al importar template:', error);
            showError('Error al importar template: ' + error.message);
        }
    }
    
    /**
     * Cierra la biblioteca de templates
     */
    closeTemplatesLibrary() {
        const modal = document.getElementById('templates-library-modal');
        if (modal) {
            modal.remove();
        }
    }
    
    /**
     * Cierra la vista previa del template
     */
    closeTemplatePreview() {
        const modal = document.getElementById('template-preview-modal');
        if (modal) {
            modal.remove();
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
        
        return text.replace(/[&<>"']/g, (m) => map[m]);
    }
    
    /**
     * Formatea una fecha
     */
    formatDate(dateString) {
        if (!dateString) return 'Nunca';
        
        const date = new Date(dateString);
        const now = new Date();
        const diffTime = Math.abs(now - date);
        const diffDays = Math.floor(diffTime / (1000 * 60 * 60 * 24));
        
        if (diffDays === 0) {
            return 'Hoy';
        } else if (diffDays === 1) {
            return 'Ayer';
        } else if (diffDays < 7) {
            return `Hace ${diffDays} días`;
        } else {
            return date.toLocaleDateString();
        }
    }
}

// Inicializar módulo cuando se carga la página
let promptsModule;

document.addEventListener('DOMContentLoaded', function() {
    // Solo inicializar si estamos en el tab de prompts
    if (typeof tabManager !== 'undefined') {
        tabManager.on('tabActivated', (tabId) => {
            if (tabId === 'prompts-tab' && !promptsModule) {
                promptsModule = new PromptsModule();
            }
        });
        
        // Si ya estamos en el tab de prompts, inicializar inmediatamente
        if (tabManager.activeTab === 'prompts-tab') {
            promptsModule = new PromptsModule();
        }
    } else {
        // Fallback si no hay tabManager
        promptsModule = new PromptsModule();
    }
});

// Exportar para uso global
window.promptsModule = promptsModule;

// Función para el tab-manager
window.loadPromptsDirect = function() {
    console.log('🔄 loadPromptsDirect llamado desde tab-manager');
    
    if (!window.promptsModule) {
        console.log('📝 Inicializando módulo de prompts...');
        window.promptsModule = new PromptsModule();
    } else {
        console.log('📝 Módulo ya existente, recargando prompts...');
        window.promptsModule.loadPrompts();
        window.promptsModule.loadStats();
    }
    
    return Promise.resolve();
};