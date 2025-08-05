<?php
/**
 * ==========================================================================
 * MÓDULO APIs - MODAL DE CONFIGURACIÓN
 * Kavia Hoteles Panel de Administración
 * Modal para configurar APIs y proveedores
 * ==========================================================================
 */
?>

<!-- Modal de Configuración de API -->
<div class="modal-overlay" id="api-modal">
    <div class="modal modal-xl">
        <div class="modal-header">
            <h3 class="modal-title">
                <i class="fas fa-plug"></i>
                <span id="api-modal-title">Configurar API</span>
            </h3>
            <button class="modal-close" type="button" onclick="apisModule.closeModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <form id="api-form" onsubmit="apisModule.saveApi(event)">
            <div class="modal-body">
                <!-- Pestañas del formulario -->
                <div class="form-tabs">
                    <div class="form-tab-buttons">
                        <button 
                            type="button" 
                            class="form-tab-btn active" 
                            data-tab="basic"
                            onclick="apisModule.switchFormTab('basic')"
                        >
                            <i class="fas fa-info-circle"></i>
                            Información Básica
                        </button>
                        <button 
                            type="button" 
                            class="form-tab-btn" 
                            data-tab="credentials"
                            onclick="apisModule.switchFormTab('credentials')"
                        >
                            <i class="fas fa-key"></i>
                            Credenciales y Acceso
                        </button>
                        <button 
                            type="button" 
                            class="form-tab-btn" 
                            data-tab="config"
                            onclick="apisModule.switchFormTab('config')"
                        >
                            <i class="fas fa-cog"></i>
                            Configuración Avanzada
                        </button>
                        <button 
                            type="button" 
                            class="form-tab-btn" 
                            data-tab="test"
                            onclick="apisModule.switchFormTab('test')"
                        >
                            <i class="fas fa-vial"></i>
                            Pruebas
                        </button>
                    </div>
                    
                    <!-- Tab Información Básica -->
                    <div id="form-tab-basic" class="form-tab-content active">
                        <div class="grid grid-cols-2 gap-4">
                            <div class="form-group">
                                <label class="form-label required" for="api-name">
                                    <i class="fas fa-tag"></i>
                                    Nombre de la API
                                </label>
                                <input 
                                    type="text" 
                                    id="api-name" 
                                    name="name"
                                    class="form-control" 
                                    placeholder="Ej: Booking.com Principal"
                                    required
                                    maxlength="100"
                                    oninput="apisModule.validateField(this)"
                                >
                                <div class="field-error" id="api-name-error"></div>
                                <div class="field-help">
                                    Nombre descriptivo para identificar esta configuración
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label required" for="api-provider">
                                    <i class="fas fa-building"></i>
                                    Proveedor
                                </label>
                                <select 
                                    id="api-provider" 
                                    name="provider_type"
                                    class="form-control form-select"
                                    required
                                    onchange="apisModule.updateProviderFields(this.value)"
                                >
                                    <option value="">Seleccionar proveedor</option>
                                    <option value="booking">Booking.com</option>
                                    <option value="tripadvisor">TripAdvisor</option>
                                    <option value="expedia">Expedia</option>
                                    <option value="google">Google Business Profile</option>
                                    <option value="airbnb">Airbnb</option>
                                    <option value="hotels">Hotels.com</option>
                                    <option value="custom">API Personalizada</option>
                                </select>
                                <div class="field-error" id="api-provider-error"></div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="api-description">
                                <i class="fas fa-align-left"></i>
                                Descripción
                            </label>
                            <textarea 
                                id="api-description" 
                                name="description"
                                class="form-control" 
                                rows="3"
                                placeholder="Descripción de la API, propósito, notas..."
                                maxlength="500"
                                oninput="apisModule.updateCharCount(this, 'description-count')"
                            ></textarea>
                            <div class="field-help">
                                <span id="description-count">0</span>/500 caracteres
                            </div>
                        </div>
                        
                        <!-- Información del proveedor (dinámico) -->
                        <div id="provider-info" class="provider-info" style="display: none;">
                            <div class="alert alert-info">
                                <div class="provider-details">
                                    <!-- Se llena dinámicamente según el proveedor -->
                                </div>
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-4">
                            <div class="form-group">
                                <label class="form-label" for="api-status">
                                    <i class="fas fa-toggle-on"></i>
                                    Estado
                                </label>
                                <select 
                                    id="api-status" 
                                    name="status"
                                    class="form-control form-select"
                                >
                                    <option value="active">Activa</option>
                                    <option value="inactive">Inactiva</option>
                                    <option value="testing">En Pruebas</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label" for="api-priority">
                                    <i class="fas fa-star"></i>
                                    Prioridad
                                </label>
                                <select 
                                    id="api-priority" 
                                    name="priority"
                                    class="form-control form-select"
                                >
                                    <option value="normal">Normal</option>
                                    <option value="high">Alta</option>
                                    <option value="critical">Crítica</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Tab Credenciales y Acceso -->
                    <div id="form-tab-credentials" class="form-tab-content" style="display: none;">
                        <div class="alert alert-warning">
                            <i class="fas fa-shield-alt"></i>
                            <strong>Seguridad:</strong> 
                            Las credenciales se almacenan de forma segura y encriptada.
                        </div>
                        
                        <!-- Campos base de URL -->
                        <div class="grid grid-cols-2 gap-4">
                            <div class="form-group">
                                <label class="form-label" for="api-base-url">
                                    <i class="fas fa-globe"></i>
                                    URL Base de la API
                                </label>
                                <input 
                                    type="url" 
                                    id="api-base-url" 
                                    name="base_url"
                                    class="form-control" 
                                    placeholder="https://api.ejemplo.com/v1"
                                    oninput="apisModule.validateField(this)"
                                >
                                <div class="field-error" id="api-base-url-error"></div>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label" for="api-version">
                                    <i class="fas fa-code-branch"></i>
                                    Versión de API
                                </label>
                                <input 
                                    type="text" 
                                    id="api-version" 
                                    name="api_version"
                                    class="form-control" 
                                    placeholder="v1, v2, 2023-01, etc."
                                    maxlength="20"
                                >
                            </div>
                        </div>
                        
                        <!-- Credenciales dinámicas según proveedor -->
                        <div id="credentials-fields">
                            <!-- Campos comunes -->
                            <div class="grid grid-cols-2 gap-4">
                                <div class="form-group">
                                    <label class="form-label" for="api-key">
                                        <i class="fas fa-key"></i>
                                        API Key / Token
                                    </label>
                                    <div class="input-group">
                                        <input 
                                            type="password" 
                                            id="api-key" 
                                            name="api_key"
                                            class="form-control" 
                                            placeholder="Tu clave API aquí"
                                        >
                                        <button 
                                            type="button" 
                                            class="btn btn-secondary"
                                            onclick="apisModule.togglePasswordVisibility('api-key')"
                                        >
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                    <div class="field-help">
                                        Obtén tu API key desde el panel del proveedor
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label" for="api-secret">
                                        <i class="fas fa-lock"></i>
                                        Secret Key (opcional)
                                    </label>
                                    <div class="input-group">
                                        <input 
                                            type="password" 
                                            id="api-secret" 
                                            name="api_secret"
                                            class="form-control" 
                                            placeholder="Secret key si es requerido"
                                        >
                                        <button 
                                            type="button" 
                                            class="btn btn-secondary"
                                            onclick="apisModule.togglePasswordVisibility('api-secret')"
                                        >
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Campos adicionales específicos por proveedor -->
                            <div id="provider-specific-fields">
                                <!-- Se llenan dinámicamente -->
                            </div>
                        </div>
                        
                        <!-- Headers personalizados -->
                        <div class="form-group">
                            <label class="form-label">
                                <i class="fas fa-list"></i>
                                Headers Personalizados
                            </label>
                            <div id="custom-headers">
                                <div class="header-row">
                                    <div class="grid grid-cols-5 gap-2 items-end">
                                        <div class="col-span-2">
                                            <input 
                                                type="text" 
                                                class="form-control form-control-sm" 
                                                placeholder="Nombre del header"
                                                name="header_names[]"
                                            >
                                        </div>
                                        <div class="col-span-2">
                                            <input 
                                                type="text" 
                                                class="form-control form-control-sm" 
                                                placeholder="Valor del header"
                                                name="header_values[]"
                                            >
                                        </div>
                                        <div>
                                            <button 
                                                type="button" 
                                                class="btn btn-success btn-sm"
                                                onclick="apisModule.addHeaderRow()"
                                            >
                                                <i class="fas fa-plus"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="field-help">
                                Agrega headers adicionales si son requeridos por la API
                            </div>
                        </div>
                    </div>
                    
                    <!-- Tab Configuración Avanzada -->
                    <div id="form-tab-config" class="form-tab-content" style="display: none;">
                        <div class="grid grid-cols-2 gap-4">
                            <div class="form-group">
                                <label class="form-label" for="api-timeout">
                                    <i class="fas fa-clock"></i>
                                    Timeout (segundos)
                                </label>
                                <input 
                                    type="number" 
                                    id="api-timeout" 
                                    name="timeout"
                                    class="form-control" 
                                    value="30"
                                    min="5"
                                    max="300"
                                >
                                <div class="field-help">
                                    Tiempo máximo de espera para respuestas
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label" for="api-retry-attempts">
                                    <i class="fas fa-redo"></i>
                                    Reintentos
                                </label>
                                <input 
                                    type="number" 
                                    id="api-retry-attempts" 
                                    name="retry_attempts"
                                    class="form-control" 
                                    value="3"
                                    min="0"
                                    max="10"
                                >
                                <div class="field-help">
                                    Número de reintentos en caso de fallo
                                </div>
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-4">
                            <div class="form-group">
                                <label class="form-label" for="api-rate-limit">
                                    <i class="fas fa-tachometer-alt"></i>
                                    Límite de Velocidad (req/min)
                                </label>
                                <input 
                                    type="number" 
                                    id="api-rate-limit" 
                                    name="rate_limit"
                                    class="form-control" 
                                    placeholder="60"
                                    min="1"
                                    max="10000"
                                >
                                <div class="field-help">
                                    Máximo de requests por minuto permitidos
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label" for="api-cache-ttl">
                                    <i class="fas fa-database"></i>
                                    Cache TTL (minutos)
                                </label>
                                <input 
                                    type="number" 
                                    id="api-cache-ttl" 
                                    name="cache_ttl"
                                    class="form-control" 
                                    value="5"
                                    min="0"
                                    max="1440"
                                >
                                <div class="field-help">
                                    Tiempo de vida del cache (0 = sin cache)
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">
                                <i class="fas fa-cogs"></i>
                                Opciones Avanzadas
                            </label>
                            <div class="checkbox-group">
                                <label class="checkbox-item">
                                    <input 
                                        type="checkbox" 
                                        id="api-auto-retry"
                                        name="auto_retry_enabled"
                                        value="1"
                                        checked
                                    >
                                    <span class="checkbox-mark"></span>
                                    <span class="checkbox-label">
                                        Reintentos automáticos
                                        <small>Reintenta automáticamente en caso de errores temporales</small>
                                    </span>
                                </label>
                                
                                <label class="checkbox-item">
                                    <input 
                                        type="checkbox" 
                                        id="api-ssl-verify"
                                        name="ssl_verify_enabled"
                                        value="1"
                                        checked
                                    >
                                    <span class="checkbox-mark"></span>
                                    <span class="checkbox-label">
                                        Verificar certificados SSL
                                        <small>Valida los certificados SSL (recomendado)</small>
                                    </span>
                                </label>
                                
                                <label class="checkbox-item">
                                    <input 
                                        type="checkbox" 
                                        id="api-logging"
                                        name="logging_enabled"
                                        value="1"
                                        checked
                                    >
                                    <span class="checkbox-mark"></span>
                                    <span class="checkbox-label">
                                        Logging habilitado
                                        <small>Registra las llamadas API para debugging</small>
                                    </span>
                                </label>
                                
                                <label class="checkbox-item">
                                    <input 
                                        type="checkbox" 
                                        id="api-monitoring"
                                        name="monitoring_enabled"
                                        value="1"
                                        checked
                                    >
                                    <span class="checkbox-mark"></span>
                                    <span class="checkbox-label">
                                        Monitoreo activo
                                        <small>Revisa el estado de la API periódicamente</small>
                                    </span>
                                </label>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="api-notes">
                                <i class="fas fa-sticky-note"></i>
                                Notas Técnicas
                            </label>
                            <textarea 
                                id="api-notes" 
                                name="technical_notes"
                                class="form-control" 
                                rows="3"
                                placeholder="Notas sobre configuración, limitaciones, peculiaridades de esta API..."
                                maxlength="1000"
                            ></textarea>
                            <div class="field-help">
                                Información técnica para el equipo de desarrollo
                            </div>
                        </div>
                    </div>
                    
                    <!-- Tab Pruebas -->
                    <div id="form-tab-test" class="form-tab-content" style="display: none;">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            <strong>Pruebas de Conectividad:</strong> 
                            Verifica que la configuración es correcta antes de guardar.
                        </div>
                        
                        <div class="test-controls">
                            <div class="flex gap-2 mb-4">
                                <button 
                                    type="button" 
                                    class="btn btn-primary"
                                    id="test-connection-btn"
                                    onclick="apisModule.testConnectionInModal()"
                                >
                                    <i class="fas fa-play"></i>
                                    Probar Conexión
                                </button>
                                <button 
                                    type="button" 
                                    class="btn btn-info"
                                    id="test-auth-btn"
                                    onclick="apisModule.testAuthentication()"
                                >
                                    <i class="fas fa-shield-alt"></i>
                                    Probar Autenticación
                                </button>
                                <button 
                                    type="button" 
                                    class="btn btn-warning"
                                    id="test-sample-btn"
                                    onclick="apisModule.testSampleRequest()"
                                >
                                    <i class="fas fa-code"></i>
                                    Prueba de Muestra
                                </button>
                            </div>
                        </div>
                        
                        <div id="test-results">
                            <div class="test-placeholder">
                                <i class="fas fa-vial"></i>
                                <p>Ejecuta una prueba para ver los resultados aquí</p>
                            </div>
                        </div>
                        
                        <!-- Historial de pruebas recientes -->
                        <div class="form-group">
                            <label class="form-label">
                                <i class="fas fa-history"></i>
                                Historial de Pruebas Recientes
                            </label>
                            <div id="test-history" class="test-history">
                                <div class="text-center text-gray">
                                    <small>No hay pruebas recientes</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="apisModule.closeModal()">
                    <i class="fas fa-times"></i>
                    Cancelar
                </button>
                <button 
                    type="button" 
                    class="btn btn-info" 
                    id="save-test-btn"
                    onclick="apisModule.saveAndTest()"
                >
                    <i class="fas fa-save"></i>
                    Guardar y Probar
                </button>
                <button type="submit" class="btn btn-primary" id="save-api-btn">
                    <i class="fas fa-save"></i>
                    <span id="save-btn-text">Guardar API</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal de Detalles de API (Solo lectura) -->
<div class="modal-overlay" id="api-details-modal">
    <div class="modal modal-lg">
        <div class="modal-header">
            <h3 class="modal-title">
                <i class="fas fa-eye"></i>
                Detalles de la API
            </h3>
            <button class="modal-close" type="button" onclick="apisModule.closeDetailsModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <div class="modal-body">
            <div id="api-details-content">
                <!-- El contenido se carga dinámicamente -->
            </div>
        </div>
        
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="apisModule.closeDetailsModal()">
                <i class="fas fa-times"></i>
                Cerrar
            </button>
            <button type="button" class="btn btn-info" onclick="apisModule.testFromDetails()">
                <i class="fas fa-wifi"></i>
                Probar Conexión
            </button>
            <button type="button" class="btn btn-primary" onclick="apisModule.editFromDetails()">
                <i class="fas fa-edit"></i>
                Editar API
            </button>
        </div>
    </div>
</div>

<style>
/* Estilos específicos para el modal de API */
.provider-info {
    margin: 1rem 0;
}

.provider-details {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.provider-logo {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
    font-size: 1.5rem;
}

.provider-booking { background: #003580; color: white; }
.provider-tripadvisor { background: #00AF87; color: white; }
.provider-expedia { background: #FFC72C; color: #003366; }
.provider-google { background: #4285F4; color: white; }
.provider-airbnb { background: #FF5A5F; color: white; }
.provider-hotels { background: #C41E3A; color: white; }
.provider-custom { background: var(--gray); color: white; }

.input-group {
    display: flex;
}

.input-group .form-control {
    border-top-right-radius: 0;
    border-bottom-right-radius: 0;
}

.input-group .btn {
    border-top-left-radius: 0;
    border-bottom-left-radius: 0;
    border-left: none;
}

.header-row {
    margin-bottom: 0.5rem;
}

.header-row:not(:last-child) .btn-success {
    background: var(--danger);
    border-color: var(--danger);
}

.header-row:not(:last-child) .btn-success:hover {
    background: #dc2626;
    border-color: #dc2626;
}

.header-row:not(:last-child) .btn-success i::before {
    content: '\f068'; /* fa-minus */
}

.test-controls {
    margin-bottom: 1.5rem;
}

.test-placeholder {
    text-align: center;
    padding: 2rem;
    color: var(--gray);
    background: var(--light-gray);
    border-radius: var(--border-radius);
    margin-bottom: 1rem;
}

.test-placeholder i {
    font-size: 2rem;
    margin-bottom: 0.5rem;
}

#test-results .test-result {
    margin-bottom: 1rem;
    padding: 1rem;
    border-radius: var(--border-radius);
    border: 1px solid var(--border-color);
}

.test-result.success {
    background: rgba(16, 185, 129, 0.05);
    border-color: var(--success);
}

.test-result.error {
    background: rgba(239, 68, 68, 0.05);
    border-color: var(--danger);
}

.test-result.warning {
    background: rgba(245, 158, 11, 0.05);
    border-color: var(--warning);
}

.test-result-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 0.5rem;
}

.test-result-title {
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.test-result-time {
    font-size: 0.75rem;
    color: var(--gray);
}

.test-result-details {
    font-size: 0.875rem;
    line-height: 1.4;
}

.test-history {
    max-height: 200px;
    overflow-y: auto;
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
    padding: 0.5rem;
}

.test-history-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.5rem;
    border-bottom: 1px solid var(--border-color);
    font-size: 0.75rem;
}

.test-history-item:last-child {
    border-bottom: none;
}

.test-history-status {
    font-weight: 600;
}

.test-history-status.success { color: var(--success); }
.test-history-status.error { color: var(--danger); }
.test-history-status.warning { color: var(--warning); }

/* Loading state para el modal */
.modal.loading .test-controls button {
    pointer-events: none;
    opacity: 0.6;
}

/* Responsive mejoras para modal */
@media (max-width: 768px) {
    .modal-xl {
        max-width: 95%;
        margin: 1rem;
    }
    
    .form-tab-buttons {
        flex-direction: column;
    }
    
    .form-tab-btn {
        justify-content: flex-start;
        text-align: left;
    }
    
    .grid-cols-2,
    .grid-cols-5 {
        grid-template-columns: 1fr;
    }
    
    .test-controls .flex {
        flex-direction: column;
    }
}
</style>