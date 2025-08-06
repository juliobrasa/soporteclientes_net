/**
 * ==========================================================================
 * MODAL MANAGER - Kavia Hoteles Panel de Administración
 * Gestor centralizado de modales
 * ==========================================================================
 */

class ModalManager {
    constructor() {
        this.activeModals = new Map();
        this.modalStack = [];
        this.config = AdminConfig?.ui?.modals || {};
        
        this.init();
    }
    
    /**
     * Inicializa el gestor de modales
     */
    init() {
        this.bindGlobalEvents();
        
        if (AdminConfig?.debug?.enabled) {
            console.log('🎭 Modal Manager inicializado');
        }
    }
    
    /**
     * Vincula eventos globales
     */
    bindGlobalEvents() {
        // Manejar tecla Escape
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.config.closeOnEscape !== false) {
                this.closeTopModal();
            }
        });
        
        // Prevenir scroll del body cuando hay modales abiertos
        document.addEventListener('modal:opened', () => {
            if (this.modalStack.length > 0) {
                document.body.style.overflow = 'hidden';
            }
        });
        
        document.addEventListener('modal:closed', () => {
            if (this.modalStack.length === 0) {
                document.body.style.overflow = '';
            }
        });
    }
    
    /**
     * Abre un modal
     * @param {string} modalId - ID del modal
     * @param {Object} options - Opciones del modal
     */
    open(modalId, options = {}) {
        const config = {
            size: 'md',
            backdrop: true,
            closeOnBackdrop: this.config.closeOnBackdrop !== false,
            closeOnEscape: this.config.closeOnEscape !== false,
            module: null, // Contexto del módulo
            ...options
        };
        
        // Verificar si el modal está disponible (importante para evitar conflictos)
        let modalElement = document.getElementById(modalId);
        if (!modalElement) {
            // Log de advertencia si se intenta abrir un modal no disponible
            console.warn(`⚠️ Modal ${modalId} no encontrado. Puede que el módulo no esté implementado aún.`);
            
            if (window.notificationSystem) {
                window.notificationSystem.warning(
                    'Funcionalidad no disponible', 
                    { message: 'Esta funcionalidad será implementada próximamente.' }
                );
            }
            return null;
        }
        
        // Agregar al stack
        this.modalStack.push(modalId);
        this.activeModals.set(modalId, {
            element: modalElement,
            config: config,
            opened: Date.now()
        });
        
        // Mostrar el modal
        this.showModal(modalElement, config);
        
        // Emitir evento
        document.dispatchEvent(new CustomEvent('modal:opened', {
            detail: { modalId, element: modalElement }
        }));
        
        return modalElement;
    }
    
    /**
     * Cierra un modal específico
     * @param {string} modalId - ID del modal
     */
    close(modalId) {
        const modal = this.activeModals.get(modalId);
        if (!modal) return false;
        
        this.hideModal(modal.element);
        
        // Remover del stack y mapa
        const index = this.modalStack.indexOf(modalId);
        if (index > -1) {
            this.modalStack.splice(index, 1);
        }
        this.activeModals.delete(modalId);
        
        // Emitir evento
        document.dispatchEvent(new CustomEvent('modal:closed', {
            detail: { modalId, element: modal.element }
        }));
        
        return true;
    }
    
    /**
     * Cierra el modal superior
     */
    closeTopModal() {
        if (this.modalStack.length > 0) {
            const topModalId = this.modalStack[this.modalStack.length - 1];
            this.close(topModalId);
        }
    }
    
    /**
     * Cierra todos los modales
     */
    closeAll() {
        const modalIds = [...this.modalStack];
        modalIds.forEach(id => this.close(id));
    }
    
    /**
     * Crea un modal dinámicamente
     */
    createModal(modalId, config) {
        const overlay = document.createElement('div');
        overlay.className = 'modal-overlay';
        overlay.id = modalId;
        
        const modal = document.createElement('div');
        modal.className = `modal modal-${config.size}`;
        
        // Estructura básica del modal
        modal.innerHTML = `
            <div class="modal-header">
                <h3 class="modal-title">${config.title || 'Modal'}</h3>
                <button class="modal-close" type="button">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                ${config.content || ''}
            </div>
            ${config.footer ? `<div class="modal-footer">${config.footer}</div>` : ''}
        `;
        
        overlay.appendChild(modal);
        document.body.appendChild(overlay);
        
        // Bind eventos
        this.bindModalEvents(overlay, modalId, config);
        
        return overlay;
    }
    
    /**
     * Vincula eventos de un modal específico
     */
    bindModalEvents(modalElement, modalId, config) {
        // Botón de cerrar
        const closeBtn = modalElement.querySelector('.modal-close');
        if (closeBtn) {
            closeBtn.addEventListener('click', () => {
                this.close(modalId);
            });
        }
        
        // Click en backdrop
        if (config.closeOnBackdrop) {
            modalElement.addEventListener('click', (e) => {
                if (e.target === modalElement) {
                    this.close(modalId);
                }
            });
        }
    }
    
    /**
     * Muestra un modal con animación
     */
    showModal(modalElement, config) {
        modalElement.style.display = 'flex';
        
        // Forzar reflow
        modalElement.offsetHeight;
        
        // Agregar clase activa para animación
        modalElement.classList.add('active');
        
        // Focus en el primer elemento focuseable
        setTimeout(() => {
            const focusableElement = modalElement.querySelector('input, button, textarea, select, [tabindex]:not([tabindex="-1"])');
            if (focusableElement) {
                focusableElement.focus();
            }
        }, 100);
    }
    
    /**
     * Oculta un modal con animación
     */
    hideModal(modalElement) {
        modalElement.classList.remove('active');
        
        // Esperar animación antes de ocultar
        setTimeout(() => {
            modalElement.style.display = 'none';
        }, 300);
    }
    
    /**
     * Obtiene el modal activo en la cima del stack
     */
    getTopModal() {
        if (this.modalStack.length === 0) return null;
        
        const topModalId = this.modalStack[this.modalStack.length - 1];
        return this.activeModals.get(topModalId);
    }
    
    /**
     * Verifica si hay modales abiertos
     */
    hasOpenModals() {
        return this.modalStack.length > 0;
    }
    
    /**
     * Obtiene el número de modales abiertos
     */
    getOpenModalCount() {
        return this.modalStack.length;
    }
    
    /**
     * Verifica si un modal específico está abierto
     */
    isOpen(modalId) {
        return this.activeModals.has(modalId);
    }
    
    /**
     * Actualiza el contenido de un modal
     */
    updateContent(modalId, content, section = 'body') {
        const modal = this.activeModals.get(modalId);
        if (!modal) return false;
        
        const targetElement = modal.element.querySelector(`.modal-${section}`);
        if (targetElement) {
            targetElement.innerHTML = content;
            return true;
        }
        
        return false;
    }
    
    /**
     * Cambia el título de un modal
     */
    setTitle(modalId, title) {
        const modal = this.activeModals.get(modalId);
        if (!modal) return false;
        
        const titleElement = modal.element.querySelector('.modal-title');
        if (titleElement) {
            titleElement.textContent = title;
            return true;
        }
        
        return false;
    }
    
    /**
     * Agrega o remueve clase de un modal
     */
    toggleClass(modalId, className, force = undefined) {
        const modal = this.activeModals.get(modalId);
        if (!modal) return false;
        
        const modalElement = modal.element.querySelector('.modal');
        if (modalElement) {
            modalElement.classList.toggle(className, force);
            return true;
        }
        
        return false;
    }
    
    // Métodos de conveniencia para tipos específicos de modales
    
    /**
     * Muestra un modal de confirmación
     */
    confirm(title, message, options = {}) {
        return new Promise((resolve) => {
            const modalId = 'confirm-modal-' + Date.now();
            const config = {
                title: title,
                size: 'sm',
                content: `<p>${message}</p>`,
                footer: `
                    <button class="btn btn-secondary" data-action="cancel">
                        ${options.cancelText || 'Cancelar'}
                    </button>
                    <button class="btn btn-danger" data-action="confirm">
                        ${options.confirmText || 'Confirmar'}
                    </button>
                `,
                closeOnBackdrop: false,
                ...options
            };
            
            const modalElement = this.open(modalId, config);
            
            // Bind botones
            const buttons = modalElement.querySelectorAll('[data-action]');
            buttons.forEach(btn => {
                btn.addEventListener('click', () => {
                    const action = btn.getAttribute('data-action');
                    this.close(modalId);
                    resolve(action === 'confirm');
                });
            });
        });
    }
    
    /**
     * Muestra un modal de alerta
     */
    alert(title, message, options = {}) {
        return new Promise((resolve) => {
            const modalId = 'alert-modal-' + Date.now();
            const config = {
                title: title,
                size: 'sm',
                content: `<p>${message}</p>`,
                footer: `
                    <button class="btn btn-primary" data-action="ok">
                        ${options.okText || 'Entendido'}
                    </button>
                `,
                ...options
            };
            
            const modalElement = this.open(modalId, config);
            
            // Bind botón OK
            const okBtn = modalElement.querySelector('[data-action="ok"]');
            if (okBtn) {
                okBtn.addEventListener('click', () => {
                    this.close(modalId);
                    resolve(true);
                });
            }
        });
    }
    
    /**
     * Muestra un modal de carga
     */
    showLoading(message = 'Cargando...', options = {}) {
        const modalId = 'loading-modal';
        
        // Cerrar modal de carga anterior si existe
        if (this.isOpen(modalId)) {
            this.close(modalId);
        }
        
        const config = {
            title: '',
            size: 'sm',
            content: `
                <div class="text-center p-4">
                    <i class="fas fa-spinner fa-spin fa-2x mb-3"></i>
                    <p>${message}</p>
                </div>
            `,
            closeOnBackdrop: false,
            closeOnEscape: false,
            ...options
        };
        
        return this.open(modalId, config);
    }
    
    /**
     * Oculta el modal de carga
     */
    hideLoading() {
        return this.close('loading-modal');
    }
}

// Crear instancia global
window.modalManager = new ModalManager();

// Funciones de conveniencia globales
window.openModal = (modalId, options) => window.modalManager.open(modalId, options);
window.closeModal = (modalId) => window.modalManager.close(modalId);
window.confirmModal = (title, message, options) => window.modalManager.confirm(title, message, options);
window.alertModal = (title, message, options) => window.modalManager.alert(title, message, options);
window.showLoadingModal = (message, options) => window.modalManager.showLoading(message, options);
window.hideLoadingModal = () => window.modalManager.hideLoading();

// Exportar para ES6 modules si es necesario
if (typeof module !== 'undefined' && module.exports) {
    module.exports = ModalManager;
}