<?php
/**
 * ==========================================================================
 * MÓDULO EXTRACTOR - WIZARD MODAL
 * Kavia Hoteles Panel de Administración
 * Wizard de 3 pasos para configurar extracciones
 * ==========================================================================
 */
?>

<!-- Modal de Wizard de Extracción -->
<div class="modal-overlay" id="extraction-wizard-modal">
    <div class="modal modal-xl">
        <div class="modal-header">
            <h3 class="modal-title">
                <i class="fas fa-magic"></i>
                <span id="wizard-title">Asistente de Extracción</span>
            </h3>
            <button class="modal-close" type="button" onclick="extractorModule.closeWizard()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <!-- Progress indicator -->
        <div class="wizard-progress">
            <div class="progress-step active" data-step="1">
                <div class="step-number">1</div>
                <div class="step-label">Proveedor</div>
            </div>
            <div class="progress-line"></div>
            <div class="progress-step" data-step="2">
                <div class="step-number">2</div>
                <div class="step-label">Configuración</div>
            </div>
            <div class="progress-line"></div>
            <div class="progress-step" data-step="3">
                <div class="step-number">3</div>
                <div class="step-label">Revisión</div>
            </div>
        </div>
        
        <form id="extraction-wizard-form">
            <div class="modal-body">
                <!-- PASO 1: Selección de Proveedor API -->
                <div id="wizard-step-1" class="wizard-step active">
                    <div class="step-header">
                        <h4>
                            <i class="fas fa-plug"></i>
                            Seleccionar Proveedor de Datos
                        </h4>
                        <p class="step-description">
                            Elige el proveedor API que utilizarás para extraer las reseñas.
                        </p>
                    </div>
                    
                    <div class="step-content">
                        <!-- Lista de proveedores disponibles -->
                        <div id="api-providers-list" class="providers-grid">
                            <div class="loading-providers">
                                <i class="fas fa-spinner fa-spin"></i>
                                <p>Cargando proveedores configurados...</p>
                            </div>
                        </div>
                        
                        <!-- Provider seleccionado -->
                        <div id="selected-provider" class="selected-provider" style="display: none;">
                            <div class="provider-card selected">
                                <div class="provider-header">
                                    <div class="provider-logo">
                                        <i id="selected-provider-icon" class="fas fa-plug"></i>
                                    </div>
                                    <div class="provider-info">
                                        <h4 id="selected-provider-name">Proveedor</h4>
                                        <p id="selected-provider-description">Descripción</p>
                                    </div>
                                    <div class="provider-status">
                                        <span id="selected-provider-status" class="connection-badge">Estado</span>
                                    </div>
                                </div>
                                <div class="provider-details">
                                    <div class="detail-item">
                                        <span class="detail-label">Rate Limit:</span>
                                        <span id="selected-provider-rate-limit">-</span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="detail-label">Timeout:</span>
                                        <span id="selected-provider-timeout">30s</span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="detail-label">Última prueba:</span>
                                        <span id="selected-provider-last-test">-</span>
                                    </div>
                                </div>
                                <div class="provider-actions">
                                    <button type="button" class="btn btn-sm btn-info" onclick="extractorModule.testSelectedProvider()">
                                        <i class="fas fa-wifi"></i>
                                        Probar Conexión
                                    </button>
                                    <button type="button" class="btn btn-sm btn-secondary" onclick="extractorModule.unselectProvider()">
                                        <i class="fas fa-times"></i>
                                        Cambiar
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Información adicional -->
                        <div class="step-info">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i>
                                <strong>Consejo:</strong> 
                                Si no tienes proveedores configurados, ve al módulo "APIs" para configurar las conexiones con Booking.com, TripAdvisor, etc.
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- PASO 2: Configuración de Extracción -->
                <div id="wizard-step-2" class="wizard-step" style="display: none;">
                    <div class="step-header">
                        <h4>
                            <i class="fas fa-cogs"></i>
                            Configurar Extracción
                        </h4>
                        <p class="step-description">
                            Define qué hoteles incluir y los parámetros de extracción.
                        </p>
                    </div>
                    
                    <div class="step-content">
                        <div class="config-grid">
                            <!-- Configuración básica -->
                            <div class="config-section">
                                <h5>
                                    <i class="fas fa-tag"></i>
                                    Información General
                                </h5>
                                
                                <div class="form-group">
                                    <label class="form-label required" for="extraction-name">
                                        Nombre del Trabajo
                                    </label>
                                    <input 
                                        type="text" 
                                        id="extraction-name" 
                                        name="extraction_name"
                                        class="form-control" 
                                        placeholder="Ej: Extracción Booking Enero 2024"
                                        required
                                        maxlength="100"
                                    >
                                    <div class="field-help">
                                        Nombre descriptivo para identificar esta extracción
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label" for="extraction-description">
                                        Descripción (opcional)
                                    </label>
                                    <textarea 
                                        id="extraction-description" 
                                        name="extraction_description"
                                        class="form-control" 
                                        rows="3"
                                        placeholder="Detalles sobre esta extracción..."
                                        maxlength="500"
                                    ></textarea>
                                </div>
                            </div>
                            
                            <!-- Selección de hoteles -->
                            <div class="config-section">
                                <h5>
                                    <i class="fas fa-hotel"></i>
                                    Hoteles a Incluir
                                </h5>
                                
                                <div class="form-group">
                                    <label class="form-label">Modo de Selección</label>
                                    <div class="radio-group">
                                        <label class="radio-item">
                                            <input type="radio" name="hotel_mode" value="active" checked>
                                            <span class="radio-mark"></span>
                                            <span class="radio-label">
                                                <strong>Solo hoteles activos</strong>
                                                <small>Hoteles marcados como activos en el sistema</small>
                                            </span>
                                        </label>
                                        
                                        <label class="radio-item">
                                            <input type="radio" name="hotel_mode" value="all">
                                            <span class="radio-mark"></span>
                                            <span class="radio-label">
                                                <strong>Todos los hoteles</strong>
                                                <small>Incluir hoteles activos e inactivos</small>
                                            </span>
                                        </label>
                                        
                                        <label class="radio-item">
                                            <input type="radio" name="hotel_mode" value="selected">
                                            <span class="radio-mark"></span>
                                            <span class="radio-label">
                                                <strong>Selección manual</strong>
                                                <small>Elegir hoteles específicos</small>
                                            </span>
                                        </label>
                                    </div>
                                </div>
                                
                                <!-- Lista de hoteles para selección manual -->
                                <div id="hotels-selection" class="hotels-selection" style="display: none;">
                                    <div class="hotels-search">
                                        <input 
                                            type="text" 
                                            id="hotels-filter" 
                                            class="form-control form-control-sm" 
                                            placeholder="Buscar hoteles..."
                                            onkeyup="extractorModule.filterHotelsSelection(this.value)"
                                        >
                                    </div>
                                    <div id="hotels-list" class="hotels-list">
                                        <!-- Se llena dinámicamente -->
                                    </div>
                                </div>
                                
                                <!-- Resumen de hoteles seleccionados -->
                                <div id="hotels-summary" class="hotels-summary">
                                    <div class="summary-item">
                                        <span class="summary-label">Hoteles seleccionados:</span>
                                        <span class="summary-value" id="selected-hotels-count">0</span>
                                    </div>
                                    <div class="summary-item">
                                        <span class="summary-label">Hoteles activos:</span>
                                        <span class="summary-value" id="active-hotels-summary">0</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="config-grid">
                            <!-- Parámetros de extracción -->
                            <div class="config-section">
                                <h5>
                                    <i class="fas fa-sliders-h"></i>
                                    Parámetros de Extracción
                                </h5>
                                
                                <div class="grid grid-cols-2 gap-4">
                                    <div class="form-group">
                                        <label class="form-label" for="max-reviews-per-hotel">
                                            Máx. reseñas por hotel
                                        </label>
                                        <input 
                                            type="number" 
                                            id="max-reviews-per-hotel" 
                                            name="max_reviews_per_hotel"
                                            class="form-control" 
                                            value="200"
                                            min="10"
                                            max="10000"
                                            step="10"
                                        >
                                        <div class="field-help">
                                            Límite de reseñas a extraer por hotel
                                        </div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label class="form-label" for="extraction-priority">
                                            Prioridad del Trabajo
                                        </label>
                                        <select id="extraction-priority" name="priority" class="form-control form-select">
                                            <option value="normal">Normal</option>
                                            <option value="high">Alta</option>
                                            <option value="critical">Crítica</option>
                                        </select>
                                        <div class="field-help">
                                            Prioridad en la cola de trabajos
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Opciones Avanzadas</label>
                                    <div class="checkbox-group">
                                        <label class="checkbox-item">
                                            <input type="checkbox" id="include-responses" name="include_responses" value="1" checked>
                                            <span class="checkbox-mark"></span>
                                            <span class="checkbox-label">
                                                Incluir respuestas del hotel
                                                <small>Extraer también las respuestas a reseñas</small>
                                            </span>
                                        </label>
                                        
                                        <label class="checkbox-item">
                                            <input type="checkbox" id="extract-photos" name="extract_photos" value="1">
                                            <span class="checkbox-mark"></span>
                                            <span class="checkbox-label">
                                                Extraer URLs de fotos
                                                <small>Incluir enlaces a fotos de reseñas</small>
                                            </span>
                                        </label>
                                        
                                        <label class="checkbox-item">
                                            <input type="checkbox" id="skip-duplicates" name="skip_duplicates" value="1" checked>
                                            <span class="checkbox-mark"></span>
                                            <span class="checkbox-label">
                                                Omitir reseñas duplicadas
                                                <small>No extraer reseñas ya existentes</small>
                                            </span>
                                        </label>
                                        
                                        <label class="checkbox-item">
                                            <input type="checkbox" id="auto-translate" name="auto_translate" value="1">
                                            <span class="checkbox-mark"></span>
                                            <span class="checkbox-label">
                                                Traducir automáticamente
                                                <small>Traducir reseñas al español</small>
                                            </span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Estimación de costos -->
                            <div class="config-section">
                                <h5>
                                    <i class="fas fa-calculator"></i>
                                    Estimación de Costos
                                </h5>
                                
                                <div id="cost-estimation" class="cost-estimation">
                                    <div class="cost-item">
                                        <span class="cost-label">Hoteles a procesar:</span>
                                        <span class="cost-value" id="cost-hotels-count">0</span>
                                    </div>
                                    <div class="cost-item">
                                        <span class="cost-label">Reseñas estimadas:</span>
                                        <span class="cost-value" id="cost-reviews-estimate">0</span>
                                    </div>
                                    <div class="cost-item">
                                        <span class="cost-label">Requests API estimados:</span>
                                        <span class="cost-value" id="cost-api-requests">0</span>
                                    </div>
                                    <div class="cost-item total">
                                        <span class="cost-label">Costo estimado total:</span>
                                        <span class="cost-value" id="cost-total-estimate">€0.00</span>
                                    </div>
                                </div>
                                
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle"></i>
                                    Los costos son estimaciones basadas en tarifas típicas de APIs.
                                    El costo real puede variar según el proveedor.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- PASO 3: Revisión y Confirmación -->
                <div id="wizard-step-3" class="wizard-step" style="display: none;">
                    <div class="step-header">
                        <h4>
                            <i class="fas fa-check-circle"></i>
                            Revisión Final
                        </h4>
                        <p class="step-description">
                            Revisa la configuración antes de iniciar la extracción.
                        </p>
                    </div>
                    
                    <div class="step-content">
                        <div class="review-grid">
                            <!-- Resumen de configuración -->
                            <div class="review-section">
                                <h5>
                                    <i class="fas fa-list-check"></i>
                                    Resumen de Configuración
                                </h5>
                                
                                <div class="review-details">
                                    <div class="review-item">
                                        <span class="review-label">Nombre del trabajo:</span>
                                        <span class="review-value" id="review-job-name">-</span>
                                    </div>
                                    <div class="review-item">
                                        <span class="review-label">Proveedor API:</span>
                                        <span class="review-value" id="review-api-provider">-</span>
                                    </div>
                                    <div class="review-item">
                                        <span class="review-label">Modo de hoteles:</span>
                                        <span class="review-value" id="review-hotel-mode">-</span>
                                    </div>
                                    <div class="review-item">
                                        <span class="review-label">Hoteles seleccionados:</span>
                                        <span class="review-value" id="review-hotel-count">-</span>
                                    </div>
                                    <div class="review-item">
                                        <span class="review-label">Máx. reseñas por hotel:</span>
                                        <span class="review-value" id="review-max-reviews">-</span>
                                    </div>
                                    <div class="review-item">
                                        <span class="review-label">Prioridad:</span>
                                        <span class="review-value" id="review-priority">-</span>
                                    </div>
                                </div>
                                
                                <div id="review-options" class="review-options">
                                    <h6>Opciones seleccionadas:</h6>
                                    <ul id="review-options-list">
                                        <!-- Se llena dinámicamente -->
                                    </ul>
                                </div>
                            </div>
                            
                            <!-- Estimaciones finales -->
                            <div class="review-section">
                                <h5>
                                    <i class="fas fa-chart-bar"></i>
                                    Estimaciones Finales
                                </h5>
                                
                                <div class="final-estimates">
                                    <div class="estimate-card">
                                        <div class="estimate-icon">
                                            <i class="fas fa-hotel"></i>
                                        </div>
                                        <div class="estimate-content">
                                            <div class="estimate-number" id="final-hotels-count">0</div>
                                            <div class="estimate-label">Hoteles</div>
                                        </div>
                                    </div>
                                    
                                    <div class="estimate-card">
                                        <div class="estimate-icon">
                                            <i class="fas fa-star"></i>
                                        </div>
                                        <div class="estimate-content">
                                            <div class="estimate-number" id="final-reviews-estimate">0</div>
                                            <div class="estimate-label">Reseñas Est.</div>
                                        </div>
                                    </div>
                                    
                                    <div class="estimate-card">
                                        <div class="estimate-icon">
                                            <i class="fas fa-clock"></i>
                                        </div>
                                        <div class="estimate-content">
                                            <div class="estimate-number" id="final-time-estimate">0min</div>
                                            <div class="estimate-label">Tiempo Est.</div>
                                        </div>
                                    </div>
                                    
                                    <div class="estimate-card highlight">
                                        <div class="estimate-icon">
                                            <i class="fas fa-euro-sign"></i>
                                        </div>
                                        <div class="estimate-content">
                                            <div class="estimate-number" id="final-cost-estimate">€0.00</div>
                                            <div class="estimate-label">Costo Est.</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Opciones de ejecución -->
                        <div class="execution-options">
                            <h5>
                                <i class="fas fa-play"></i>
                                Opciones de Ejecución
                            </h5>
                            
                            <div class="radio-group">
                                <label class="radio-item">
                                    <input type="radio" name="execution_mode" value="immediate" checked>
                                    <span class="radio-mark"></span>
                                    <span class="radio-label">
                                        <strong>Ejecutar inmediatamente</strong>
                                        <small>Iniciar la extracción en cuanto se confirme</small>
                                    </span>
                                </label>
                                
                                <label class="radio-item">
                                    <input type="radio" name="execution_mode" value="schedule">
                                    <span class="radio-mark"></span>
                                    <span class="radio-label">
                                        <strong>Programar para más tarde</strong>
                                        <small>Definir fecha y hora de inicio</small>
                                    </span>
                                </label>
                                
                                <label class="radio-item">
                                    <input type="radio" name="execution_mode" value="draft">
                                    <span class="radio-mark"></span>
                                    <span class="radio-label">
                                        <strong>Guardar como borrador</strong>
                                        <small>Crear el trabajo sin ejecutar</small>
                                    </span>
                                </label>
                            </div>
                            
                            <!-- Campo de programación -->
                            <div id="schedule-options" class="schedule-options" style="display: none;">
                                <div class="form-group">
                                    <label class="form-label" for="scheduled-datetime">
                                        Fecha y hora de inicio
                                    </label>
                                    <input 
                                        type="datetime-local" 
                                        id="scheduled-datetime" 
                                        name="scheduled_datetime"
                                        class="form-control"
                                    >
                                </div>
                            </div>
                        </div>
                        
                        <!-- Confirmaciones finales -->
                        <div class="final-confirmations">
                            <div class="checkbox-group">
                                <label class="checkbox-item">
                                    <input type="checkbox" id="confirm-costs" name="confirm_costs" value="1" required>
                                    <span class="checkbox-mark"></span>
                                    <span class="checkbox-label">
                                        <strong>Confirmo los costos estimados</strong>
                                        <small>Entiendo que los costos reales pueden variar</small>
                                    </span>
                                </label>
                                
                                <label class="checkbox-item">
                                    <input type="checkbox" id="confirm-data-usage" name="confirm_data_usage" value="1" required>
                                    <span class="checkbox-mark"></span>
                                    <span class="checkbox-label">
                                        <strong>Confirmo el uso de datos de terceros</strong>
                                        <small>Respetaré los términos de uso de las APIs</small>
                                    </span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Modal footer con navegación -->
            <div class="modal-footer">
                <div class="wizard-navigation">
                    <button 
                        type="button" 
                        id="wizard-prev-btn" 
                        class="btn btn-secondary" 
                        onclick="extractorModule.wizardPreviousStep()"
                        style="display: none;"
                    >
                        <i class="fas fa-chevron-left"></i>
                        Anterior
                    </button>
                    
                    <button 
                        type="button" 
                        class="btn btn-secondary" 
                        onclick="extractorModule.closeWizard()"
                    >
                        <i class="fas fa-times"></i>
                        Cancelar
                    </button>
                    
                    <button 
                        type="button" 
                        id="wizard-next-btn" 
                        class="btn btn-primary" 
                        onclick="extractorModule.wizardNextStep()"
                        disabled
                    >
                        Siguiente
                        <i class="fas fa-chevron-right"></i>
                    </button>
                    
                    <button 
                        type="button" 
                        id="wizard-finish-btn" 
                        class="btn btn-success" 
                        onclick="extractorModule.createExtractionJob()"
                        style="display: none;"
                        disabled
                    >
                        <i class="fas fa-play"></i>
                        <span id="finish-btn-text">Crear y Ejecutar</span>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<style>
/* Estilos específicos para el wizard de extracción */
.wizard-progress {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1.5rem 0;
    background: var(--light-gray);
    border-bottom: 1px solid var(--border-color);
    margin-bottom: 0;
}

.progress-step {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
    min-width: 100px;
    transition: all 0.3s;
}

.step-number {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 0.9rem;
    background: var(--light-gray);
    color: var(--gray);
    border: 2px solid var(--border-color);
}

.progress-step.active .step-number {
    background: var(--primary);
    color: white;
    border-color: var(--primary);
}

.progress-step.completed .step-number {
    background: var(--success);
    color: white;
    border-color: var(--success);
}

.step-label {
    font-size: 0.8rem;
    color: var(--gray);
    text-align: center;
    font-weight: 500;
}

.progress-step.active .step-label {
    color: var(--primary);
    font-weight: 600;
}

.progress-step.completed .step-label {
    color: var(--success);
}

.progress-line {
    flex: 1;
    height: 2px;
    background: var(--border-color);
    margin: 0 1rem;
    position: relative;
}

.progress-line::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    height: 100%;
    background: var(--success);
    width: 0%;
    transition: width 0.3s;
}

.wizard-step {
    min-height: 500px;
}

.step-header {
    margin-bottom: 2rem;
    text-align: center;
}

.step-header h4 {
    margin-bottom: 0.5rem;
    color: var(--text-color);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
}

.step-description {
    color: var(--gray);
    font-size: 0.9rem;
    margin: 0;
}

.step-content {
    max-width: 100%;
}

/* Providers grid */
.providers-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
}

.loading-providers {
    grid-column: 1 / -1;
    text-align: center;
    padding: 3rem;
    color: var(--gray);
}

.provider-card {
    border: 2px solid var(--border-color);
    border-radius: var(--border-radius);
    padding: 1.5rem;
    cursor: pointer;
    transition: all 0.2s;
    background: white;
}

.provider-card:hover {
    border-color: var(--primary);
    box-shadow: 0 4px 12px rgba(99, 102, 241, 0.1);
}

.provider-card.selected {
    border-color: var(--primary);
    background: rgba(99, 102, 241, 0.05);
}

.provider-header {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
    margin-bottom: 1rem;
}

.provider-logo {
    width: 48px;
    height: 48px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    color: white;
    flex-shrink: 0;
}

.provider-info {
    flex: 1;
}

.provider-info h4 {
    margin: 0 0 0.25rem 0;
    font-size: 1rem;
}

.provider-info p {
    margin: 0;
    font-size: 0.875rem;
    color: var(--gray);
    line-height: 1.4;
}

.provider-status {
    flex-shrink: 0;
}

.provider-details {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
    gap: 0.5rem;
    margin-bottom: 1rem;
    padding-top: 1rem;
    border-top: 1px solid var(--border-color);
}

.detail-item {
    font-size: 0.75rem;
}

.detail-label {
    color: var(--gray);
}

.provider-actions {
    display: flex;
    gap: 0.5rem;
    justify-content: flex-end;
}

.selected-provider {
    margin-bottom: 2rem;
}

/* Configuration sections */
.config-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 2rem;
    margin-bottom: 2rem;
}

.config-section {
    background: white;
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
    padding: 1.5rem;
}

.config-section h5 {
    margin: 0 0 1rem 0;
    color: var(--text-color);
    font-size: 1rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.hotels-selection {
    margin-top: 1rem;
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
    max-height: 300px;
    overflow: hidden;
}

.hotels-search {
    padding: 0.75rem;
    border-bottom: 1px solid var(--border-color);
    background: var(--light-gray);
}

.hotels-list {
    max-height: 240px;
    overflow-y: auto;
}

.hotel-item {
    padding: 0.75rem;
    border-bottom: 1px solid var(--border-color);
    cursor: pointer;
    transition: background 0.2s;
}

.hotel-item:hover {
    background: var(--light-gray);
}

.hotel-item.selected {
    background: rgba(99, 102, 241, 0.1);
}

.hotels-summary {
    margin-top: 1rem;
    padding: 1rem;
    background: var(--light-gray);
    border-radius: var(--border-radius);
}

.summary-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.5rem;
}

.summary-item:last-child {
    margin-bottom: 0;
}

.summary-label {
    font-size: 0.875rem;
    color: var(--gray);
}

.summary-value {
    font-weight: 600;
    color: var(--text-color);
}

/* Cost estimation */
.cost-estimation {
    background: var(--light-gray);
    border-radius: var(--border-radius);
    padding: 1rem;
    margin-bottom: 1rem;
}

.cost-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.75rem;
    font-size: 0.875rem;
}

.cost-item:last-child {
    margin-bottom: 0;
}

.cost-item.total {
    border-top: 1px solid var(--border-color);
    padding-top: 0.75rem;
    font-weight: 600;
    font-size: 1rem;
}

.cost-label {
    color: var(--gray);
}

.cost-value {
    font-weight: 600;
    color: var(--text-color);
}

.cost-item.total .cost-value {
    color: var(--success);
    font-size: 1.1rem;
}

/* Review step */
.review-grid {
    display: grid;
    grid-template-columns: 1fr 300px;
    gap: 2rem;
    margin-bottom: 2rem;
}

.review-section {
    background: white;
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
    padding: 1.5rem;
}

.review-section h5 {
    margin: 0 0 1rem 0;
    color: var(--text-color);
    font-size: 1rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.review-details {
    margin-bottom: 1.5rem;
}

.review-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.75rem;
    padding-bottom: 0.75rem;
    border-bottom: 1px solid var(--light-gray);
}

.review-item:last-child {
    margin-bottom: 0;
    border-bottom: none;
    padding-bottom: 0;
}

.review-label {
    font-size: 0.875rem;
    color: var(--gray);
    font-weight: 500;
}

.review-value {
    font-weight: 600;
    color: var(--text-color);
    text-align: right;
}

.review-options h6 {
    font-size: 0.875rem;
    margin-bottom: 0.5rem;
    color: var(--gray);
}

.review-options ul {
    list-style: none;
    padding: 0;
    margin: 0;
}

.review-options li {
    font-size: 0.875rem;
    padding: 0.25rem 0;
    color: var(--text-color);
}

.review-options li::before {
    content: '✓';
    color: var(--success);
    font-weight: 600;
    margin-right: 0.5rem;
}

/* Final estimates */
.final-estimates {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}

.estimate-card {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    background: var(--light-gray);
    border-radius: var(--border-radius);
    border: 1px solid var(--border-color);
}

.estimate-card.highlight {
    background: rgba(16, 185, 129, 0.1);
    border-color: var(--success);
}

.estimate-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: var(--primary);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    flex-shrink: 0;
}

.estimate-card.highlight .estimate-icon {
    background: var(--success);
}

.estimate-content {
    text-align: center;
}

.estimate-number {
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--text-color);
    margin-bottom: 0.25rem;
}

.estimate-label {
    font-size: 0.75rem;
    color: var(--gray);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* Execution options */
.execution-options {
    background: white;
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
    padding: 1.5rem;
    margin-bottom: 2rem;
}

.execution-options h5 {
    margin: 0 0 1rem 0;
    color: var(--text-color);
    font-size: 1rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.schedule-options {
    margin-top: 1rem;
    padding: 1rem;
    background: var(--light-gray);
    border-radius: var(--border-radius);
}

/* Final confirmations */
.final-confirmations {
    background: rgba(245, 158, 11, 0.1);
    border: 1px solid var(--warning);
    border-radius: var(--border-radius);
    padding: 1.5rem;
}

/* Wizard navigation */
.wizard-navigation {
    display: flex;
    align-items: center;
    gap: 1rem;
    width: 100%;
}

.wizard-navigation .btn {
    white-space: nowrap;
}

/* Responsive design */
@media (max-width: 768px) {
    .wizard-progress {
        padding: 1rem 0.5rem;
    }
    
    .progress-step {
        min-width: 80px;
    }
    
    .step-number {
        width: 28px;
        height: 28px;
        font-size: 0.8rem;
    }
    
    .step-label {
        font-size: 0.7rem;
    }
    
    .providers-grid {
        grid-template-columns: 1fr;
    }
    
    .config-grid {
        grid-template-columns: 1fr;
    }
    
    .review-grid {
        grid-template-columns: 1fr;
    }
    
    .final-estimates {
        grid-template-columns: 1fr;
    }
    
    .wizard-navigation {
        flex-wrap: wrap;
        justify-content: center;
    }
}
</style>