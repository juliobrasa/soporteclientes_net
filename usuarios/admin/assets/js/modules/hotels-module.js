/**
 * ==========================================================================
 * HOTELS MODULE - Kavia Hoteles Panel de Administración
 * Módulo JavaScript para la gestión completa de hoteles
 * ==========================================================================
 */

class HotelsModule {
    constructor() {
        // Configuración del módulo
        this.config = {
            pageSize: AdminConfig?.ui?.tables?.defaultPageSize || 25,
            currentPage: 1,
            totalPages: 1,
            totalItems: 0,
            sortField: 'name',
            sortDirection: 'asc',
            searchTerm: '',
            statusFilter: '',
            isLoading: false
        };
        
        // Referencias a elementos DOM
        this.elements = {};
        
        // Datos cacheados
        this.hotelsData = [];
        this.filteredData = [];
        
        // Estado del modal
        this.modalState = {
            isOpen: false,
            isEditing: false,
            currentHotelId: null,
            currentTab: 'basic'
        };
        
        this.init();
    }
    
    /**
     * Inicializa el módulo
     */
    init() {
        this.bindElements();
        this.bindEvents();
        
        // Cargar datos si el tab de hoteles está activo
        if (document.getElementById('hotels-tab') && 
            document.getElementById('hotels-tab').style.display !== 'none') {
            // Pequeño delay para asegurar que el DOM esté listo
            setTimeout(() => {
                this.loadHotels();
            }, 100);
        }
        
        // Escuchar cambios de tab para cargar datos cuando se active hotels
        document.addEventListener('tabChanged', (event) => {
            if (event.detail && event.detail.tabName === 'hotels') {
                this.loadHotels();
            }
        });
        
        if (AdminConfig?.debug?.enabled) {
            console.log('🏨 Hotels Module inicializado');
        }
    }
    
    /**
     * Vincula elementos del DOM
     */
    bindElements() {
        this.elements = {
            container: document.getElementById('hotels-list-container'),
            pagination: document.getElementById('hotels-pagination'),
            searchInput: document.getElementById('hotels-search'),
            statusFilter: document.getElementById('hotels-status-filter'),
            pageSizeSelect: document.getElementById('hotels-per-page'),
            
            // Información de paginación
            showingSpan: document.getElementById('hotels-showing'),
            totalSpan: document.getElementById('hotels-total'),
            pageInfo: document.getElementById('hotels-page-info'),
            prevBtn: document.getElementById('hotels-prev-btn'),
            nextBtn: document.getElementById('hotels-next-btn'),
            
            // Modal elementos
            modal: document.getElementById('hotel-modal'),
            detailsModal: document.getElementById('hotel-details-modal'),
            modalTitle: document.getElementById('hotel-modal-title'),
            hotelForm: document.getElementById('hotel-form'),
            saveBtnText: document.getElementById('save-btn-text'),
            
            // Templates
            tableTemplate: document.getElementById('hotels-table-template'),
            rowTemplate: document.getElementById('hotel-row-template'),
            emptyTemplate: document.getElementById('hotels-empty-template'),
            errorTemplate: document.getElementById('hotels-error-template'),
            mobileTemplate: document.getElementById('hotels-mobile-template'),
            cardTemplate: document.getElementById('hotel-card-template')
        };
        
        // Debug: Verificar elementos críticos
        if (!this.elements.container) {
            console.error('❌ hotels-list-container no encontrado');
        }
        if (AdminConfig?.debug?.enabled) {
            console.log('🔗 Hotels elements bound:', {
                container: !!this.elements.container,
                pagination: !!this.elements.pagination,
                tableTemplate: !!this.elements.tableTemplate
            });
        }
    }
    
    /**
     * Vincula eventos del módulo
     */
    bindEvents() {
        // Eventos de búsqueda con debounce
        if (this.elements.searchInput) {
            let searchTimeout;
            this.elements.searchInput.addEventListener('input', (e) => {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    this.filterHotels(e.target.value);
                }, 300);
            });
        }
        
        // Cambio de tamaño de página
        if (this.elements.pageSizeSelect) {
            this.elements.pageSizeSelect.addEventListener('change', (e) => {
                this.changePageSize(parseInt(e.target.value));
            });
        }
        
        // Filtro por estado
        if (this.elements.statusFilter) {
            this.elements.statusFilter.addEventListener('change', (e) => {
                this.filterByStatus(e.target.value);
            });
        }
        
        // Eventos del modal
        if (this.elements.modal) {
            // Cerrar modal al hacer click en el overlay
            this.elements.modal.addEventListener('click', (e) => {
                if (e.target === this.elements.modal) {
                    this.closeModal();
                }
            });
        }
        
        // Prevenir cierre del modal al hacer submit
        if (this.elements.hotelForm) {
            this.elements.hotelForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.saveHotel(e);
            });
        }
    }
    
    /**
     * Carga la lista de hoteles desde la API
     */
    async loadHotels() {
        if (this.config.isLoading) return;
        
        console.log('🏨 Iniciando carga de hoteles...');
        this.config.isLoading = true;
        this.showLoadingState();
        
        try {
            const result = await apiClient.getHotels({
                page: this.config.currentPage,
                limit: this.config.pageSize,
                search: this.config.searchTerm,
                status: this.config.statusFilter,
                sort: this.config.sortField,
                direction: this.config.sortDirection
            });
            
            if (result.success) {
                this.hotelsData = result.hotels || [];
                this.config.totalItems = result.total || this.hotelsData.length;
                this.config.totalPages = Math.ceil(this.config.totalItems / this.config.pageSize);
                
                this.renderHotelsList();
                this.updatePagination();
                
                if (AdminConfig?.debug?.enabled) {
                    console.log(`📊 Hoteles cargados: ${this.hotelsData.length}`);
                }
            } else {
                throw new Error(result.error || 'Error al cargar hoteles');
            }
        } catch (error) {
            console.error('Error cargando hoteles:', error);
            this.showErrorState(error.message);
            showError('Error al cargar hoteles: ' + error.message);
        } finally {
            this.config.isLoading = false;
        }
    }
    
    /**
     * Renderiza la lista de hoteles
     */
    renderHotelsList() {
        if (!this.elements.container) return;
        
        // Si no hay datos, mostrar estado vacío
        if (!this.hotelsData || this.hotelsData.length === 0) {
            this.showEmptyState();
            return;
        }
        
        // Determinar si usar vista móvil o desktop
        const isMobile = window.innerWidth <= 768;
        
        if (isMobile) {
            this.renderMobileView();
        } else {
            this.renderTableView();
        }
        
        // Mostrar paginación
        if (this.elements.pagination) {
            this.elements.pagination.style.display = 'flex';
        }
    }
    
    /**
     * Renderiza la vista de tabla (desktop)
     */
    renderTableView() {
        if (!this.elements.tableTemplate || !this.elements.rowTemplate) return;
        
        // Clonar template de tabla
        const tableClone = this.elements.tableTemplate.content.cloneNode(true);
        const tbody = tableClone.getElementById('hotels-table-body');
        
        // Generar filas
        this.hotelsData.forEach(hotel => {
            const rowHtml = this.generateHotelRow(hotel);
            tbody.innerHTML += rowHtml;
        });
        
        // Reemplazar contenido
        this.elements.container.innerHTML = '';
        this.elements.container.appendChild(tableClone);
        
        // Actualizar iconos de ordenamiento
        this.updateSortIcons();
    }
    
    /**
     * Renderiza la vista móvil (cards)
     */
    renderMobileView() {
        if (!this.elements.mobileTemplate || !this.elements.cardTemplate) return;
        
        // Clonar template móvil
        const mobileClone = this.elements.mobileTemplate.content.cloneNode(true);
        const cardsContainer = mobileClone.querySelector('.data-cards');
        
        // Generar cards
        this.hotelsData.forEach(hotel => {
            const cardHtml = this.generateHotelCard(hotel);
            cardsContainer.innerHTML += cardHtml;
        });
        
        // Reemplazar contenido
        this.elements.container.innerHTML = '';
        this.elements.container.appendChild(mobileClone);
    }
    
    /**
     * Genera HTML para una fila de hotel
     */
    generateHotelRow(hotel) {
        if (!this.elements.rowTemplate) return '';
        
        const template = this.elements.rowTemplate.innerHTML;
        
        // Preparar datos
        const data = {
            id: hotel.id,
            name: this.escapeHtml(hotel.nombre_hotel),
            name_escaped: this.escapeHtml(hotel.nombre_hotel).replace(/'/g, "\\'"),
            description: hotel.hoja_destino ? `<small class="text-gray">${this.escapeHtml(hotel.hoja_destino)}</small>` : '',
            status: hotel.activo == 1 ? 'active' : 'inactive',
            status_badge: this.getStatusBadge(hotel.activo == 1 ? 'active' : 'inactive'),
            status_icon: this.getStatusIcon(hotel.activo == 1 ? 'active' : 'inactive'),
            status_toggle_text: hotel.activo == 1 ? 'Desactivar' : 'Activar',
            featured_badge: hotel.priority === 'featured' ? '<span class="featured-badge">Destacado</span>' : '',
            created_at: this.formatDate(hotel.created_at),
            updated_at: this.formatDate(hotel.updated_at)
        };
        
        // Reemplazar placeholders
        return this.replacePlaceholders(template, data);
    }
    
    /**
     * Genera HTML para una card de hotel (móvil)
     */
    generateHotelCard(hotel) {
        if (!this.elements.cardTemplate) return '';
        
        const template = this.elements.cardTemplate.innerHTML;
        
        // Preparar datos
        const data = {
            id: hotel.id,
            name: this.escapeHtml(hotel.nombre_hotel),
            name_escaped: this.escapeHtml(hotel.nombre_hotel).replace(/'/g, "\\'"),
            featured_badge: '', // No priority field in Spanish format
            status_badge: this.getStatusBadge(hotel.activo == 1 ? 'active' : 'inactive'),
            created_at: this.formatDate(hotel.created_at),
            updated_at: this.formatDate(hotel.updated_at),
            description_field: hotel.hoja_destino ? 
                `<div class="data-card-field">
                    <span class="data-card-label">Descripción:</span>
                    <span class="data-card-value">${this.escapeHtml(hotel.hoja_destino)}</span>
                </div>` : ''
        };
        
        return this.replacePlaceholders(template, data);
    }
    
    /**
     * Muestra el estado de carga
     */
    showLoadingState() {
        if (!this.elements.container) {
            console.error('❌ No se puede mostrar loading state: container no encontrado');
            return;
        }
        console.log('⏳ Mostrando loading state...');
        
        this.elements.container.innerHTML = `
            <div class="loading-state">
                <i class="fas fa-spinner fa-spin spinner"></i>
                <h3>Cargando hoteles...</h3>
                <p>Por favor espera mientras cargamos la información</p>
            </div>
        `;
        
        // Ocultar paginación
        if (this.elements.pagination) {
            this.elements.pagination.style.display = 'none';
        }
    }
    
    /**
     * Muestra el estado vacío
     */
    showEmptyState() {
        if (!this.elements.container || !this.elements.emptyTemplate) return;
        
        const template = this.elements.emptyTemplate.innerHTML;
        const message = this.config.searchTerm || this.config.statusFilter ?
            'No se encontraron hoteles que coincidan con los filtros aplicados.' :
            'Comienza agregando tu primer hotel al sistema para empezar a gestionar reseñas y análisis.';
        
        const html = this.replacePlaceholders(template, {
            empty_message: message
        });
        
        this.elements.container.innerHTML = html;
        
        // Ocultar paginación
        if (this.elements.pagination) {
            this.elements.pagination.style.display = 'none';
        }
    }
    
    /**
     * Muestra el estado de error
     */
    showErrorState(errorMessage) {
        if (!this.elements.container || !this.elements.errorTemplate) return;
        
        const template = this.elements.errorTemplate.innerHTML;
        const html = this.replacePlaceholders(template, {
            error_message: this.escapeHtml(errorMessage)
        });
        
        this.elements.container.innerHTML = html;
        
        // Ocultar paginación
        if (this.elements.pagination) {
            this.elements.pagination.style.display = 'none';
        }
    }
    
    /**
     * Actualiza la información de paginación
     */
    updatePagination() {
        if (!this.elements.pagination) return;
        
        const start = (this.config.currentPage - 1) * this.config.pageSize + 1;
        const end = Math.min(this.config.currentPage * this.config.pageSize, this.config.totalItems);
        
        // Actualizar textos
        if (this.elements.showingSpan) {
            this.elements.showingSpan.textContent = this.config.totalItems > 0 ? `${start}-${end}` : '0';
        }
        if (this.elements.totalSpan) {
            this.elements.totalSpan.textContent = this.config.totalItems;
        }
        if (this.elements.pageInfo) {
            this.elements.pageInfo.textContent = `Página ${this.config.currentPage} de ${this.config.totalPages}`;
        }
        
        // Actualizar botones
        if (this.elements.prevBtn) {
            this.elements.prevBtn.disabled = this.config.currentPage <= 1;
        }
        if (this.elements.nextBtn) {
            this.elements.nextBtn.disabled = this.config.currentPage >= this.config.totalPages;
        }
    }
    
    /**
     * Funciones de navegación
     */
    previousPage() {
        if (this.config.currentPage > 1) {
            this.config.currentPage--;
            this.loadHotels();
        }
    }
    
    nextPage() {
        if (this.config.currentPage < this.config.totalPages) {
            this.config.currentPage++;
            this.loadHotels();
        }
    }
    
    changePageSize(newSize) {
        this.config.pageSize = newSize;
        this.config.currentPage = 1;
        this.loadHotels();
    }
    
    /**
     * Funciones de filtrado y búsqueda
     */
    filterHotels(searchTerm) {
        this.config.searchTerm = searchTerm.trim();
        this.config.currentPage = 1;
        this.loadHotels();
    }
    
    filterByStatus(status) {
        this.config.statusFilter = status;
        this.config.currentPage = 1;
        this.loadHotels();
    }
    
    /**
     * Funciones de ordenamiento
     */
    sortBy(field) {
        if (this.config.sortField === field) {
            this.config.sortDirection = this.config.sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            this.config.sortField = field;
            this.config.sortDirection = 'asc';
        }
        
        this.config.currentPage = 1;
        this.loadHotels();
    }
    
    updateSortIcons() {
        // Limpiar iconos anteriores
        document.querySelectorAll('.sortable').forEach(el => {
            el.classList.remove('sort-asc', 'sort-desc');
        });
        
        // Agregar icono al campo actual
        const currentSortElement = document.querySelector(`[onclick="hotelsModule.sortBy('${this.config.sortField}')"]`);
        if (currentSortElement) {
            currentSortElement.classList.add(`sort-${this.config.sortDirection}`);
        }
    }
    
    /**
     * Refresca la lista completa
     */
    async refreshList() {
        showInfo('Actualizando lista de hoteles...');
        
        // Limpiar cache
        apiClient.clearCache('getHotels');
        
        // Recargar datos
        await this.loadHotels();
        
        showSuccess('Lista de hoteles actualizada');
    }
    
    /**
     * Muestra el modal para agregar un nuevo hotel
     */
    showAddModal() {
        this.modalState.isEditing = false;
        this.modalState.currentHotelId = null;
        this.modalState.currentTab = 'basic';
        
        // Actualizar título
        if (this.elements.modalTitle) {
            this.elements.modalTitle.textContent = 'Agregar Hotel';
        }
        if (this.elements.saveBtnText) {
            this.elements.saveBtnText.textContent = 'Guardar Hotel';
        }
        
        // Limpiar formulario
        this.resetForm();
        
        // Mostrar modal
        this.openModal();
    }
    
    /**
     * Muestra el modal para editar un hotel
     */
    async editHotel(hotelId) {
        this.modalState.isEditing = true;
        this.modalState.currentHotelId = hotelId;
        this.modalState.currentTab = 'basic';
        
        // Actualizar título
        if (this.elements.modalTitle) {
            this.elements.modalTitle.textContent = 'Editar Hotel';
        }
        if (this.elements.saveBtnText) {
            this.elements.saveBtnText.textContent = 'Actualizar Hotel';
        }
        
        // Buscar datos del hotel
        const hotel = this.hotelsData.find(h => h.id == hotelId);
        if (!hotel) {
            showError('Hotel no encontrado');
            return;
        }
        
        // Cargar datos en el formulario
        this.loadHotelData(hotel);
        
        // Mostrar modal
        this.openModal();
    }
    
    /**
     * Abre el modal
     */
    openModal() {
        if (!this.elements.modal) return;
        
        this.modalState.isOpen = true;
        this.elements.modal.classList.add('active');
        this.elements.modal.style.display = 'flex';
        
        // Focus en el primer campo
        setTimeout(() => {
            const firstInput = this.elements.modal.querySelector('input[type="text"]');
            if (firstInput) {
                firstInput.focus();
            }
        }, 100);
        
        // Prevenir scroll del body
        document.body.style.overflow = 'hidden';
    }
    
    /**
     * Cierra el modal
     */
    closeModal() {
        if (!this.elements.modal) return;
        
        this.modalState.isOpen = false;
        this.elements.modal.classList.remove('active');
        
        setTimeout(() => {
            this.elements.modal.style.display = 'none';
            document.body.style.overflow = '';
        }, 300);
    }
    
    /**
     * Guarda o actualiza un hotel
     */
    async saveHotel(event) {
        event.preventDefault();
        
        if (!this.elements.hotelForm) return;
        
        // Validar formulario
        if (!this.validateForm()) {
            showError('Por favor, corrige los errores en el formulario');
            return;
        }
        
        // Recopilar datos del formulario
        const formData = new FormData(this.elements.hotelForm);
        const hotelData = Object.fromEntries(formData.entries());
        
        // Procesar checkboxes
        hotelData.auto_sync_enabled = formData.has('auto_sync_enabled') ? 1 : 0;
        hotelData.review_monitoring_enabled = formData.has('review_monitoring_enabled') ? 1 : 0;
        hotelData.alerts_enabled = formData.has('alerts_enabled') ? 1 : 0;
        
        // Agregar ID si estamos editando
        if (this.modalState.isEditing && this.modalState.currentHotelId) {
            hotelData.id = this.modalState.currentHotelId;
        }
        
        try {
            // Mostrar estado de carga
            this.setModalLoading(true);
            
            const result = await apiClient.saveHotel(hotelData);
            
            if (result.success) {
                const action = this.modalState.isEditing ? 'actualizado' : 'creado';
                showSuccess(`Hotel ${action} correctamente`);
                
                // Cerrar modal
                this.closeModal();
                
                // Recargar lista
                await this.loadHotels();
            } else {
                throw new Error(result.error || 'Error al guardar hotel');
            }
        } catch (error) {
            console.error('Error guardando hotel:', error);
            showError('Error al guardar hotel: ' + error.message);
        } finally {
            this.setModalLoading(false);
        }
    }
    
    /**
     * Ver detalles de un hotel
     */
    async viewDetails(hotelId) {
        // Implementar vista de detalles
        showInfo('Vista de detalles en desarrollo');
    }
    
    /**
     * Confirma la eliminación de un hotel
     */
    async confirmDelete(hotelId, hotelName) {
        const confirmed = await confirmAction(
            `¿Estás seguro de que quieres eliminar el hotel "${hotelName}"?`,
            {
                title: 'Confirmar Eliminación',
                type: 'danger',
                confirmText: 'Eliminar',
                cancelText: 'Cancelar'
            }
        );
        
        if (confirmed) {
            await this.deleteHotel(hotelId);
        }
    }
    
    /**
     * Elimina un hotel
     */
    async deleteHotel(hotelId) {
        try {
            const loadingId = showLoadingModal('Eliminando hotel...');
            
            const result = await apiClient.deleteHotel(hotelId);
            
            hideLoadingModal();
            
            if (result.success) {
                showSuccess('Hotel eliminado correctamente');
                await this.loadHotels();
            } else {
                throw new Error(result.error || 'Error al eliminar hotel');
            }
        } catch (error) {
            hideLoadingModal();
            console.error('Error eliminando hotel:', error);
            showError('Error al eliminar hotel: ' + error.message);
        }
    }
    
    /**
     * Alterna el estado de un hotel
     */
    async toggleStatus(hotelId, currentStatus) {
        const newStatus = currentStatus === 'active' ? 'inactive' : 'active';
        const action = newStatus === 'active' ? 'activar' : 'desactivar';
        
        try {
            const result = await apiClient.call('toggleHotelStatus', {
                id: hotelId,
                status: newStatus
            });
            
            if (result.success) {
                showSuccess(`Hotel ${action}do correctamente`);
                await this.loadHotels();
            } else {
                throw new Error(result.error || `Error al ${action} hotel`);
            }
        } catch (error) {
            console.error(`Error al ${action} hotel:`, error);
            showError(`Error al ${action} hotel: ` + error.message);
        }
    }
    
    /**
     * Funciones del formulario
     */
    
    switchFormTab(tabName) {
        this.modalState.currentTab = tabName;
        
        // Ocultar todas las pestañas
        document.querySelectorAll('.form-tab-content').forEach(tab => {
            tab.style.display = 'none';
        });
        
        // Desactivar todos los botones
        document.querySelectorAll('.form-tab-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        
        // Mostrar pestaña seleccionada
        const targetTab = document.getElementById(`form-tab-${tabName}`);
        const targetBtn = document.querySelector(`[data-tab="${tabName}"]`);
        
        if (targetTab) {
            targetTab.style.display = 'block';
        }
        if (targetBtn) {
            targetBtn.classList.add('active');
        }
    }
    
    validateForm() {
        let isValid = true;
        
        // Limpiar errores anteriores
        document.querySelectorAll('.field-error').forEach(error => {
            error.classList.remove('show');
        });
        document.querySelectorAll('.form-control').forEach(input => {
            input.classList.remove('error', 'success');
        });
        
        // Validar nombre (requerido)
        const nameInput = document.getElementById('hotel-name');
        if (nameInput) {
            const value = nameInput.value.trim();
            if (!value) {
                this.showFieldError(nameInput, 'El nombre del hotel es requerido');
                isValid = false;
            } else if (value.length < 2) {
                this.showFieldError(nameInput, 'El nombre debe tener al menos 2 caracteres');
                isValid = false;
            } else {
                nameInput.classList.add('success');
            }
        }
        
        // Validar email si está presente
        const emailInput = document.getElementById('hotel-email');
        if (emailInput && emailInput.value.trim()) {
            if (!this.isValidEmail(emailInput.value.trim())) {
                this.showFieldError(emailInput, 'Formato de email inválido');
                isValid = false;
            } else {
                emailInput.classList.add('success');
            }
        }
        
        // Validar website si está presente
        const websiteInput = document.getElementById('hotel-website');
        if (websiteInput && websiteInput.value.trim()) {
            if (!this.isValidUrl(websiteInput.value.trim())) {
                this.showFieldError(websiteInput, 'Formato de URL inválido');
                isValid = false;
            } else {
                websiteInput.classList.add('success');
            }
        }
        
        return isValid;
    }
    
    validateField(input) {
        const value = input.value.trim();
        
        // Limpiar estado anterior
        input.classList.remove('error', 'success');
        this.hideFieldError(input);
        
        switch (input.type) {
            case 'email':
                if (value && !this.isValidEmail(value)) {
                    this.showFieldError(input, 'Formato de email inválido');
                } else if (value) {
                    input.classList.add('success');
                }
                break;
                
            case 'url':
                if (value && !this.isValidUrl(value)) {
                    this.showFieldError(input, 'Formato de URL inválido');
                } else if (value) {
                    input.classList.add('success');
                }
                break;
                
            case 'text':
                if (input.hasAttribute('required') && !value) {
                    this.showFieldError(input, 'Este campo es requerido');
                } else if (value && input.minLength && value.length < input.minLength) {
                    this.showFieldError(input, `Mínimo ${input.minLength} caracteres`);
                } else if (value) {
                    input.classList.add('success');
                }
                break;
        }
    }
    
    showFieldError(input, message) {
        input.classList.add('error');
        const errorElement = document.getElementById(input.id + '-error');
        if (errorElement) {
            errorElement.textContent = message;
            errorElement.classList.add('show');
        }
    }
    
    hideFieldError(input) {
        const errorElement = document.getElementById(input.id + '-error');
        if (errorElement) {
            errorElement.classList.remove('show');
        }
    }
    
    resetForm() {
        if (!this.elements.hotelForm) return;
        
        this.elements.hotelForm.reset();
        
        // Limpiar errores
        document.querySelectorAll('.field-error').forEach(error => {
            error.classList.remove('show');
        });
        document.querySelectorAll('.form-control').forEach(input => {
            input.classList.remove('error', 'success');
        });
        
        // Resetear contadores
        document.querySelectorAll('[id$="-count"]').forEach(counter => {
            counter.textContent = '0';
        });
        
        // Volver a la primera pestaña
        this.switchFormTab('basic');
    }
    
    loadHotelData(hotel) {
        if (!hotel) return;
        
        // Cargar datos básicos
        const fields = [
            'name', 'code', 'description', 'status', 'priority', 'category',
            'website', 'contact_email', 'phone', 'total_rooms', 'address',
            'city', 'country', 'timezone', 'api_key', 'sync_interval', 'internal_notes'
        ];
        
        fields.forEach(field => {
            const input = document.getElementById(`hotel-${field.replace('_', '-')}`);
            if (input && hotel[field] !== undefined) {
                input.value = hotel[field];
            }
        });
        
        // Cargar checkboxes
        const checkboxes = [
            'auto_sync_enabled', 'review_monitoring_enabled', 'alerts_enabled'
        ];
        
        checkboxes.forEach(field => {
            const checkbox = document.getElementById(`hotel-${field.replace('_', '-')}`);
            if (checkbox) {
                checkbox.checked = hotel[field] == 1;
            }
        });
        
        // Actualizar contadores
        this.updateCharCount(document.getElementById('hotel-description'), 'description-count');
    }
    
    setModalLoading(isLoading) {
        if (!this.elements.modal) return;
        
        const modal = this.elements.modal.querySelector('.modal');
        const saveBtn = document.getElementById('save-hotel-btn');
        
        if (isLoading) {
            modal.classList.add('loading');
            if (saveBtn) {
                saveBtn.disabled = true;
                saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
            }
        } else {
            modal.classList.remove('loading');
            if (saveBtn) {
                saveBtn.disabled = false;
                const text = this.modalState.isEditing ? 'Actualizar Hotel' : 'Guardar Hotel';
                saveBtn.innerHTML = `<i class="fas fa-save"></i> ${text}`;
            }
        }
    }
    
    /**
     * Funciones de utilidad
     */
    
    updateCharCount(textarea, counterId) {
        if (!textarea || !counterId) return;
        
        const counter = document.getElementById(counterId);
        if (counter) {
            counter.textContent = textarea.value.length;
        }
    }
    
    formatCode(input) {
        // Formatear código del hotel (mayúsculas, sin espacios)
        input.value = input.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
    }
    
    formatPhone(input) {
        // Formateo básico de teléfono
        let value = input.value.replace(/\D/g, '');
        if (value.length >= 10) {
            value = value.replace(/(\d{3})(\d{3})(\d{4})/, '($1) $2-$3');
        }
        input.value = value;
    }
    
    getStatusBadge(status) {
        const badges = {
            active: '<span class="status-badge status-active"><i class="fas fa-check"></i> Activo</span>',
            inactive: '<span class="status-badge status-inactive"><i class="fas fa-times"></i> Inactivo</span>',
            maintenance: '<span class="status-badge status-pending"><i class="fas fa-wrench"></i> Mantenimiento</span>'
        };
        return badges[status] || badges.active;
    }
    
    getStatusIcon(status) {
        const icons = {
            active: 'fa-pause',
            inactive: 'fa-play',
            maintenance: 'fa-play'
        };
        return icons[status] || 'fa-pause';
    }
    
    formatDate(dateString) {
        if (!dateString) return 'N/A';
        
        try {
            const date = new Date(dateString);
            return date.toLocaleDateString('es-ES', {
                year: 'numeric',
                month: '2-digit',
                day: '2-digit',
                hour: '2-digit',
                minute: '2-digit'
            });
        } catch (error) {
            return 'Fecha inválida';
        }
    }
    
    escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, m => map[m]);
    }
    
    replacePlaceholders(template, data) {
        return template.replace(/\{(\w+)\}/g, (match, key) => {
            return data[key] !== undefined ? data[key] : match;
        });
    }
    
    isValidEmail(email) {
        const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return regex.test(email);
    }
    
    isValidUrl(url) {
        try {
            new URL(url);
            return true;
        } catch (error) {
            return false;
        }
    }
    
    // Funciones para modal de detalles
    closeDetailsModal() {
        const detailsModal = this.elements.detailsModal;
        if (detailsModal) {
            detailsModal.classList.remove('active');
            setTimeout(() => {
                detailsModal.style.display = 'none';
            }, 300);
        }
    }
    
    editFromDetails() {
        this.closeDetailsModal();
        if (this.modalState.currentHotelId) {
            this.editHotel(this.modalState.currentHotelId);
        }
    }
}

// Crear instancia global del módulo
window.hotelsModule = new HotelsModule();

// Exportar para ES6 modules si es necesario
if (typeof module !== 'undefined' && module.exports) {
    module.exports = HotelsModule;
}