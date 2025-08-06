<?php
/**
 * ==========================================================================
 * MÓDULO HOTELES - MODAL DE EDICIÓN
 * Kavia Hoteles Panel de Administración
 * Modal para crear/editar hoteles
 * ==========================================================================
 */
?>

<!-- Modal de Hotel -->
<div class="modal-overlay" id="hotel-modal">
    <div class="modal modal-lg">
        <div class="modal-header">
            <h3 class="modal-title">
                <i class="fas fa-hotel"></i>
                <span id="hotel-modal-title">Agregar Hotel</span>
            </h3>
            <button class="modal-close" type="button" onclick="hotelsModule.closeModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <form id="hotel-form" onsubmit="hotelsModule.saveHotel(event)">
            <div class="modal-body">
                <!-- Pestañas del formulario -->
                <div class="form-tabs">
                    <div class="form-tab-buttons">
                        <button 
                            type="button" 
                            class="form-tab-btn active" 
                            data-tab="basic"
                            onclick="hotelsModule.switchFormTab('basic')"
                        >
                            <i class="fas fa-info-circle"></i>
                            Información Básica
                        </button>
                        <button 
                            type="button" 
                            class="form-tab-btn" 
                            data-tab="details"
                            onclick="hotelsModule.switchFormTab('details')"
                        >
                            <i class="fas fa-cog"></i>
                            Detalles y Configuración
                        </button>
                        <button 
                            type="button" 
                            class="form-tab-btn" 
                            data-tab="advanced"
                            onclick="hotelsModule.switchFormTab('advanced')"
                        >
                            <i class="fas fa-puzzle-piece"></i>
                            Configuración Avanzada
                        </button>
                    </div>
                    
                    <!-- Tab Información Básica -->
                    <div id="form-tab-basic" class="form-tab-content active">
                        <div class="grid grid-cols-2 gap-4">
                            <div class="form-group">
                                <label class="form-label required" for="hotel-name">
                                    <i class="fas fa-hotel"></i>
                                    Nombre del Hotel
                                </label>
                                <input 
                                    type="text" 
                                    id="hotel-name" 
                                    name="name"
                                    class="form-control" 
                                    placeholder="Ej: Hotel Paradise"
                                    required
                                    maxlength="100"
                                    oninput="hotelsModule.validateField(this)"
                                >
                                <div class="field-error" id="hotel-name-error"></div>
                                <div class="field-help">
                                    Nombre completo del hotel como aparecerá en el sistema
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label" for="hotel-code">
                                    <i class="fas fa-barcode"></i>
                                    Código del Hotel
                                </label>
                                <input 
                                    type="text" 
                                    id="hotel-code" 
                                    name="code"
                                    class="form-control" 
                                    placeholder="Ej: HP001"
                                    maxlength="20"
                                    oninput="hotelsModule.validateField(this); hotelsModule.formatCode(this)"
                                >
                                <div class="field-error" id="hotel-code-error"></div>
                                <div class="field-help">
                                    Código único para identificación interna (opcional)
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="hotel-description">
                                <i class="fas fa-align-left"></i>
                                Descripción
                            </label>
                            <textarea 
                                id="hotel-description" 
                                name="description"
                                class="form-control" 
                                rows="3"
                                placeholder="Descripción del hotel, características principales..."
                                maxlength="500"
                                oninput="hotelsModule.updateCharCount(this, 'description-count')"
                            ></textarea>
                            <div class="field-help">
                                <span id="description-count">0</span>/500 caracteres
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-3 gap-4">
                            <div class="form-group">
                                <label class="form-label" for="hotel-status">
                                    <i class="fas fa-toggle-on"></i>
                                    Estado
                                </label>
                                <select 
                                    id="hotel-status" 
                                    name="status"
                                    class="form-control form-select"
                                >
                                    <option value="active">Activo</option>
                                    <option value="inactive">Inactivo</option>
                                    <option value="maintenance">En Mantenimiento</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label" for="hotel-priority">
                                    <i class="fas fa-star"></i>
                                    Prioridad
                                </label>
                                <select 
                                    id="hotel-priority" 
                                    name="priority"
                                    class="form-control form-select"
                                >
                                    <option value="normal">Normal</option>
                                    <option value="high">Alta</option>
                                    <option value="featured">Destacado</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label" for="hotel-category">
                                    <i class="fas fa-tags"></i>
                                    Categoría
                                </label>
                                <select 
                                    id="hotel-category" 
                                    name="category"
                                    class="form-control form-select"
                                >
                                    <option value="">Seleccionar categoría</option>
                                    <option value="luxury">Lujo</option>
                                    <option value="boutique">Boutique</option>
                                    <option value="business">Negocios</option>
                                    <option value="resort">Resort</option>
                                    <option value="economy">Económico</option>
                                    <option value="other">Otro</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Tab Detalles y Configuración -->
                    <div id="form-tab-details" class="form-tab-content" style="display: none;">
                        <div class="grid grid-cols-2 gap-4">
                            <div class="form-group">
                                <label class="form-label" for="hotel-website">
                                    <i class="fas fa-globe"></i>
                                    Sitio Web
                                </label>
                                <input 
                                    type="url" 
                                    id="hotel-website" 
                                    name="website"
                                    class="form-control" 
                                    placeholder="https://ejemplo.com"
                                    oninput="hotelsModule.validateField(this)"
                                >
                                <div class="field-error" id="hotel-website-error"></div>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label" for="hotel-email">
                                    <i class="fas fa-envelope"></i>
                                    Email de Contacto
                                </label>
                                <input 
                                    type="email" 
                                    id="hotel-email" 
                                    name="contact_email"
                                    class="form-control" 
                                    placeholder="contacto@hotel.com"
                                    oninput="hotelsModule.validateField(this)"
                                >
                                <div class="field-error" id="hotel-email-error"></div>
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-4">
                            <div class="form-group">
                                <label class="form-label" for="hotel-phone">
                                    <i class="fas fa-phone"></i>
                                    Teléfono
                                </label>
                                <input 
                                    type="tel" 
                                    id="hotel-phone" 
                                    name="phone"
                                    class="form-control" 
                                    placeholder="+1 (555) 123-4567"
                                    oninput="hotelsModule.formatPhone(this)"
                                >
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label" for="hotel-rooms">
                                    <i class="fas fa-bed"></i>
                                    Número de Habitaciones
                                </label>
                                <input 
                                    type="number" 
                                    id="hotel-rooms" 
                                    name="total_rooms"
                                    class="form-control" 
                                    placeholder="100"
                                    min="1"
                                    max="10000"
                                >
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="hotel-address">
                                <i class="fas fa-map-marker-alt"></i>
                                Dirección
                            </label>
                            <textarea 
                                id="hotel-address" 
                                name="address"
                                class="form-control" 
                                rows="2"
                                placeholder="Dirección completa del hotel..."
                                maxlength="300"
                            ></textarea>
                        </div>
                        
                        <div class="grid grid-cols-3 gap-4">
                            <div class="form-group">
                                <label class="form-label" for="hotel-city">
                                    <i class="fas fa-city"></i>
                                    Ciudad
                                </label>
                                <input 
                                    type="text" 
                                    id="hotel-city" 
                                    name="city"
                                    class="form-control" 
                                    placeholder="Ciudad"
                                    maxlength="50"
                                >
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label" for="hotel-country">
                                    <i class="fas fa-flag"></i>
                                    País
                                </label>
                                <select 
                                    id="hotel-country" 
                                    name="country"
                                    class="form-control form-select"
                                >
                                    <option value="">Seleccionar país</option>
                                    <option value="ES">España</option>
                                    <option value="FR">Francia</option>
                                    <option value="IT">Italia</option>
                                    <option value="PT">Portugal</option>
                                    <option value="US">Estados Unidos</option>
                                    <option value="MX">México</option>
                                    <option value="AR">Argentina</option>
                                    <option value="other">Otro</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label" for="hotel-timezone">
                                    <i class="fas fa-clock"></i>
                                    Zona Horaria
                                </label>
                                <select 
                                    id="hotel-timezone" 
                                    name="timezone"
                                    class="form-control form-select"
                                >
                                    <option value="Europe/Madrid">Europa/Madrid (CET)</option>
                                    <option value="Europe/London">Europa/Londres (GMT)</option>
                                    <option value="America/New_York">América/Nueva York (EST)</option>
                                    <option value="America/Los_Angeles">América/Los Ángeles (PST)</option>
                                    <option value="America/Mexico_City">América/Ciudad de México (CST)</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Tab Configuración Avanzada -->
                    <div id="form-tab-advanced" class="form-tab-content" style="display: none;">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            <strong>Configuración Avanzada:</strong> 
                            Estas opciones afectan cómo el sistema procesa datos de este hotel.
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">
                                <i class="fas fa-cogs"></i>
                                Opciones de Configuración
                            </label>
                            <div class="checkbox-group">
                                <label class="checkbox-item">
                                    <input 
                                        type="checkbox" 
                                        id="hotel-auto-sync"
                                        name="auto_sync_enabled"
                                        value="1"
                                    >
                                    <span class="checkbox-mark"></span>
                                    <span class="checkbox-label">
                                        Sincronización automática habilitada
                                        <small>Actualiza datos automáticamente desde fuentes externas</small>
                                    </span>
                                </label>
                                
                                <label class="checkbox-item">
                                    <input 
                                        type="checkbox" 
                                        id="hotel-review-monitoring"
                                        name="review_monitoring_enabled"
                                        value="1"
                                        checked
                                    >
                                    <span class="checkbox-mark"></span>
                                    <span class="checkbox-label">
                                        Monitoreo de reseñas activo
                                        <small>Supervisa nuevas reseñas en plataformas configuradas</small>
                                    </span>
                                </label>
                                
                                <label class="checkbox-item">
                                    <input 
                                        type="checkbox" 
                                        id="hotel-alerts-enabled"
                                        name="alerts_enabled"
                                        value="1"
                                        checked
                                    >
                                    <span class="checkbox-mark"></span>
                                    <span class="checkbox-label">
                                        Alertas y notificaciones
                                        <small>Recibe notificaciones sobre eventos importantes</small>
                                    </span>
                                </label>
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-4">
                            <div class="form-group">
                                <label class="form-label" for="hotel-api-key">
                                    <i class="fas fa-key"></i>
                                    API Key (opcional)
                                </label>
                                <input 
                                    type="password" 
                                    id="hotel-api-key" 
                                    name="api_key"
                                    class="form-control" 
                                    placeholder="Clave API para integraciones"
                                >
                                <div class="field-help">
                                    Clave API para integraciones específicas del hotel
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label" for="hotel-sync-interval">
                                    <i class="fas fa-sync"></i>
                                    Intervalo de Sincronización
                                </label>
                                <select 
                                    id="hotel-sync-interval" 
                                    name="sync_interval"
                                    class="form-control form-select"
                                >
                                    <option value="15">Cada 15 minutos</option>
                                    <option value="30">Cada 30 minutos</option>
                                    <option value="60" selected>Cada hora</option>
                                    <option value="180">Cada 3 horas</option>
                                    <option value="360">Cada 6 horas</option>
                                    <option value="720">Cada 12 horas</option>
                                    <option value="1440">Diario</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="hotel-notes">
                                <i class="fas fa-sticky-note"></i>
                                Notas Internas
                            </label>
                            <textarea 
                                id="hotel-notes" 
                                name="internal_notes"
                                class="form-control" 
                                rows="3"
                                placeholder="Notas internas sobre el hotel, configuraciones especiales, etc..."
                                maxlength="1000"
                            ></textarea>
                            <div class="field-help">
                                Estas notas son solo para uso interno del equipo
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="hotelsModule.closeModal()">
                    <i class="fas fa-times"></i>
                    Cancelar
                </button>
                <button type="button" class="btn btn-warning" id="test-connection-btn" style="display: none;">
                    <i class="fas fa-wifi"></i>
                    Probar Conexión
                </button>
                <button type="submit" class="btn btn-primary" id="save-hotel-btn">
                    <i class="fas fa-save"></i>
                    <span id="save-btn-text">Guardar Hotel</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal de Vista de Detalles (Solo lectura) -->
<div class="modal-overlay" id="hotel-details-modal">
    <div class="modal modal-lg">
        <div class="modal-header">
            <h3 class="modal-title">
                <i class="fas fa-eye"></i>
                Detalles del Hotel
            </h3>
            <button class="modal-close" type="button" onclick="hotelsModule.closeDetailsModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <div class="modal-body">
            <div id="hotel-details-content">
                <!-- El contenido se carga dinámicamente -->
            </div>
        </div>
        
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="hotelsModule.closeDetailsModal()">
                <i class="fas fa-times"></i>
                Cerrar
            </button>
            <button type="button" class="btn btn-primary" onclick="hotelsModule.editFromDetails()">
                <i class="fas fa-edit"></i>
                Editar Hotel
            </button>
        </div>
    </div>
</div>

<style>
/* Estilos específicos para el modal de hotel */
.form-tabs {
    width: 100%;
}

.form-tab-buttons {
    display: flex;
    border-bottom: 1px solid var(--border-color);
    margin-bottom: 1.5rem;
    gap: 0;
}

.form-tab-btn {
    flex: 1;
    padding: 0.75rem 1rem;
    border: none;
    background: none;
    cursor: pointer;
    font-size: var(--font-size-sm);
    color: var(--gray);
    border-bottom: 2px solid transparent;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
}

.form-tab-btn:hover {
    background: var(--light-gray);
    color: var(--dark);
}

.form-tab-btn.active {
    color: var(--primary);
    border-bottom-color: var(--primary);
    background: rgba(99, 102, 241, 0.05);
}

.form-tab-content {
    animation: fadeInUp 0.3s ease;
}

.required::after {
    content: ' *';
    color: var(--danger);
}

.field-error {
    color: var(--danger);
    font-size: var(--font-size-xs);
    margin-top: 0.25rem;
    display: none;
}

.field-error.show {
    display: block;
}

.field-help {
    color: var(--gray);
    font-size: var(--font-size-xs);
    margin-top: 0.25rem;
}

.checkbox-group {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.checkbox-item {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
    cursor: pointer;
    padding: 0.75rem;
    border-radius: var(--border-radius);
    transition: background-color 0.2s;
}

.checkbox-item:hover {
    background: var(--light-gray);
}

.checkbox-mark {
    width: 1.25rem;
    height: 1.25rem;
    border: 2px solid var(--border-color);
    border-radius: 0.25rem;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
    flex-shrink: 0;
    margin-top: 0.125rem;
}

.checkbox-item input[type="checkbox"] {
    display: none;
}

.checkbox-item input[type="checkbox"]:checked + .checkbox-mark {
    background: var(--primary);
    border-color: var(--primary);
}

.checkbox-item input[type="checkbox"]:checked + .checkbox-mark::after {
    content: '\f00c';
    font-family: 'Font Awesome 6 Free';
    font-weight: 900;
    color: white;
    font-size: 0.75rem;
}

.checkbox-label {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.checkbox-label small {
    color: var(--gray);
    font-size: var(--font-size-xs);
    line-height: 1.4;
}

.form-control.error {
    border-color: var(--danger);
    box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
}

.form-control.success {
    border-color: var(--success);
    box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
}

/* Loading state para el modal */
.modal.loading {
    pointer-events: none;
}

.modal.loading .modal-body {
    position: relative;
    min-height: 200px;
}

.modal.loading .modal-body::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(255, 255, 255, 0.8);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 10;
}

/* Responsive */
@media (max-width: 768px) {
    .form-tab-buttons {
        flex-direction: column;
    }
    
    .form-tab-btn {
        justify-content: flex-start;
        text-align: left;
    }
    
    .grid-cols-2,
    .grid-cols-3 {
        grid-template-columns: 1fr;
    }
    
    .modal-lg {
        max-width: 95%;
        margin: 1rem;
    }
}
</style>