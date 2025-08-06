/**
 * ==========================================================================
 * ADMIN NOTIFICATION SYSTEM - Kavia Hoteles Panel de Administración
 * Sistema de notificaciones y alertas
 * ==========================================================================
 */

class NotificationSystem {
    constructor() {
        this.notifications = new Map();
        this.toastContainer = null;
        this.defaultDuration = AdminConfig?.ui?.notificationDuration || 5000;
        this.maxNotifications = 5;
        
        this.init();
    }
    
    /**
     * Inicializa el sistema de notificaciones
     */
    init() {
        this.createToastContainer();
        this.bindKeyboardEvents();
        
        if (AdminConfig?.debug?.enabled) {
            console.log('🔔 Sistema de notificaciones inicializado');
        }
    }
    
    /**
     * Crea el contenedor para las notificaciones toast
     */
    createToastContainer() {
        if (this.toastContainer) return;
        
        this.toastContainer = document.createElement('div');
        this.toastContainer.className = 'toast-container';
        this.toastContainer.id = 'toast-container';
        
        document.body.appendChild(this.toastContainer);
    }
    
    /**
     * Vincula eventos de teclado
     */
    bindKeyboardEvents() {
        document.addEventListener('keydown', (e) => {
            // Escape para cerrar todas las notificaciones
            if (e.key === 'Escape') {
                this.clearAll();
            }
        });
    }
    
    /**
     * Muestra una notificación
     * @param {string} type - Tipo: success, error, warning, info
     * @param {string} message - Mensaje a mostrar
     * @param {Object} options - Opciones adicionales
     */
    show(type, message, options = {}) {
        const config = {
            duration: this.defaultDuration,
            closable: true,
            persistent: false,
            html: false,
            position: 'top-right',
            ...options
        };
        
        // Validar tipo
        const validTypes = ['success', 'error', 'warning', 'info'];
        if (!validTypes.includes(type)) {
            console.warn('Tipo de notificación inválido:', type);
            type = 'info';
        }
        
        // Limpiar notificaciones antiguas si hay demasiadas
        this.cleanupOldNotifications();
        
        const notification = this.createNotification(type, message, config);
        const id = this.generateId();
        
        // Guardar referencia
        this.notifications.set(id, {
            element: notification,
            type,
            message,
            config,
            created: Date.now()
        });
        
        // Mostrar la notificación
        this.displayNotification(notification, config);
        
        // Auto-ocultar si no es persistente
        if (!config.persistent && config.duration > 0) {
            setTimeout(() => {
                this.hide(id);
            }, config.duration);
        }
        
        return id;
    }
    
    /**
     * Crea el elemento DOM de la notificación
     */
    createNotification(type, message, config) {
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        
        // Icono según el tipo
        const icons = {
            success: 'fa-check-circle',
            error: 'fa-exclamation-circle',
            warning: 'fa-exclamation-triangle',
            info: 'fa-info-circle'
        };
        
        const icon = icons[type] || icons.info;
        
        // Contenido
        const content = config.html ? message : this.escapeHtml(message);
        
        notification.innerHTML = `
            <i class="fas ${icon}"></i>
            <span class="notification-message">${content}</span>
            ${config.closable ? '<button class="notification-close" aria-label="Cerrar"><i class="fas fa-times"></i></button>' : ''}
        `;
        
        // Event listener para cerrar
        if (config.closable) {
            const closeBtn = notification.querySelector('.notification-close');
            closeBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                this.hideElement(notification);
            });
        }
        
        return notification;
    }
    
    /**
     * Muestra la notificación en el DOM
     */
    displayNotification(notification, config) {
        // Agregar al contenedor
        this.toastContainer.appendChild(notification);
        
        // Animar entrada
        notification.style.opacity = '0';
        notification.style.transform = 'translateX(100%)';
        
        // Forzar reflow
        notification.offsetHeight;
        
        // Animar
        notification.style.transition = 'all 0.3s ease';
        notification.style.opacity = '1';
        notification.style.transform = 'translateX(0)';
        
        // Agregar evento click para acciones adicionales
        notification.addEventListener('click', () => {
            if (config.onClick && typeof config.onClick === 'function') {
                config.onClick();
            }
        });
    }
    
    /**
     * Oculta una notificación por ID
     */
    hide(id) {
        const notification = this.notifications.get(id);
        if (!notification) return false;
        
        this.hideElement(notification.element);
        this.notifications.delete(id);
        
        return true;
    }
    
    /**
     * Oculta un elemento de notificación
     */
    hideElement(element) {
        if (!element || !element.parentNode) return;
        
        element.classList.add('slideOut');
        
        setTimeout(() => {
            if (element.parentNode) {
                element.parentNode.removeChild(element);
            }
        }, 300);
    }
    
    /**
     * Limpia notificaciones antiguas
     */
    cleanupOldNotifications() {
        if (this.notifications.size < this.maxNotifications) return;
        
        // Convertir a array y ordenar por fecha de creación
        const notificationArray = Array.from(this.notifications.entries());
        notificationArray.sort((a, b) => a[1].created - b[1].created);
        
        // Eliminar las más antiguas
        const toRemove = notificationArray.slice(0, notificationArray.length - this.maxNotifications + 1);
        toRemove.forEach(([id]) => {
            this.hide(id);
        });
    }
    
    /**
     * Cierra todas las notificaciones
     */
    clearAll() {
        const ids = Array.from(this.notifications.keys());
        ids.forEach(id => this.hide(id));
    }
    
    /**
     * Genera un ID único
     */
    generateId() {
        return 'notification_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
    }
    
    /**
     * Escapa HTML para seguridad
     */
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
    
    // Métodos de conveniencia
    success(message, options = {}) {
        return this.show('success', message, options);
    }
    
    error(message, options = {}) {
        return this.show('error', message, {
            duration: options.persistent ? 0 : (options.duration || 8000),
            ...options
        });
    }
    
    warning(message, options = {}) {
        return this.show('warning', message, options);
    }
    
    info(message, options = {}) {
        return this.show('info', message, options);
    }
    
    /**
     * Muestra un modal de confirmación
     */
    confirm(message, options = {}) {
        return new Promise((resolve) => {
            const config = {
                title: 'Confirmar',
                confirmText: 'Confirmar',
                cancelText: 'Cancelar',
                type: 'warning',
                ...options
            };
            
            this.showModal('confirm', message, config, resolve);
        });
    }
    
    /**
     * Muestra un modal de alerta
     */
    alert(message, options = {}) {
        return new Promise((resolve) => {
            const config = {
                title: 'Alerta',
                confirmText: 'Entendido',
                type: 'info',
                ...options
            };
            
            this.showModal('alert', message, config, resolve);
        });
    }
    
    /**
     * Muestra un modal personalizado
     */
    showModal(modalType, message, config, callback) {
        // Crear overlay
        const overlay = document.createElement('div');
        overlay.className = 'modal-overlay active';
        
        // Crear modal
        const modal = document.createElement('div');
        modal.className = `modal modal-${modalType} modal-${config.type}`;
        
        modal.innerHTML = `
            <div class="modal-header">
                <h3 class="modal-title">
                    <i class="fas ${this.getModalIcon(config.type)}"></i>
                    ${config.title}
                </h3>
            </div>
            <div class="modal-body">
                <p>${this.escapeHtml(message)}</p>
            </div>
            <div class="modal-footer">
                ${modalType === 'confirm' ? `<button class="btn btn-secondary" data-action="cancel">${config.cancelText}</button>` : ''}
                <button class="btn btn-${config.type === 'danger' ? 'danger' : 'primary'}" data-action="confirm">${config.confirmText}</button>
            </div>
        `;
        
        overlay.appendChild(modal);
        
        // Event listeners
        const buttons = modal.querySelectorAll('button[data-action]');
        buttons.forEach(button => {
            button.addEventListener('click', () => {
                const action = button.getAttribute('data-action');
                const result = action === 'confirm';
                
                this.hideModal(overlay);
                
                if (callback) {
                    callback(result);
                }
            });
        });
        
        // Cerrar con Escape
        const escapeHandler = (e) => {
            if (e.key === 'Escape') {
                this.hideModal(overlay);
                if (callback) callback(false);
                document.removeEventListener('keydown', escapeHandler);
            }
        };
        document.addEventListener('keydown', escapeHandler);
        
        // Cerrar al hacer click en el overlay
        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) {
                this.hideModal(overlay);
                if (callback) callback(false);
                document.removeEventListener('keydown', escapeHandler);
            }
        });
        
        // Mostrar modal
        document.body.appendChild(overlay);
    }
    
    /**
     * Oculta un modal
     */
    hideModal(overlay) {
        overlay.classList.remove('active');
        setTimeout(() => {
            if (overlay.parentNode) {
                overlay.parentNode.removeChild(overlay);
            }
        }, 300);
    }
    
    /**
     * Obtiene el icono para un modal según su tipo
     */
    getModalIcon(type) {
        const icons = {
            success: 'fa-check-circle',
            error: 'fa-exclamation-circle',
            warning: 'fa-exclamation-triangle',
            info: 'fa-info-circle',
            danger: 'fa-trash'
        };
        return icons[type] || icons.info;
    }
    
    /**
     * Muestra una notificación de carga
     */
    showLoading(message = 'Cargando...', options = {}) {
        const config = {
            persistent: true,
            closable: false,
            ...options
        };
        
        const loadingMessage = `
            <i class="fas fa-spinner fa-spin"></i>
            <span>${message}</span>
        `;
        
        return this.show('info', loadingMessage, {
            ...config,
            html: true
        });
    }
    
    /**
     * Oculta una notificación de carga
     */
    hideLoading(id) {
        return this.hide(id);
    }
}

// Crear instancia global
window.notificationSystem = new NotificationSystem();

// Función legacy para compatibilidad
window.showNotification = function(type, message, duration) {
    return window.notificationSystem.show(type, message, { duration });
};

// Funciones de conveniencia globales
window.showSuccess = (message, options) => window.notificationSystem.success(message, options);
window.showError = (message, options) => window.notificationSystem.error(message, options);
window.showWarning = (message, options) => window.notificationSystem.warning(message, options);
window.showInfo = (message, options) => window.notificationSystem.info(message, options);
window.confirmAction = (message, options) => window.notificationSystem.confirm(message, options);
window.alertMessage = (message, options) => window.notificationSystem.alert(message, options);

// Exportar para ES6 modules si es necesario
if (typeof module !== 'undefined' && module.exports) {
    module.exports = NotificationSystem;
}