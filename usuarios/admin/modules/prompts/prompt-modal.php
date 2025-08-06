<?php
/**
 * ==========================================================================
 * MÓDULO PROMPTS - MODAL DE EDICIÓN/CREACIÓN
 * Kavia Hoteles Panel de Administración
 * Editor avanzado de prompts para IA
 * ==========================================================================
 */
?>

<!-- Modal Principal de Prompt -->
<div class="modal-overlay" id="prompt-modal">
    <div class="modal modal-xl">
        <div class="modal-header">
            <h3 class="modal-title">
                <i class="fas fa-file-alt"></i>
                <span id="prompt-modal-title">Nuevo Prompt</span>
            </h3>
            <button class="modal-close" type="button" onclick="promptsModule.closePromptModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <form id="prompt-form" class="modal-form">
            <div class="modal-body prompt-editor">
                <!-- Tabs del editor -->
                <div class="editor-tabs">
                    <button type="button" class="tab-btn active" data-tab="basic" onclick="promptsModule.switchEditorTab('basic')">
                        <i class="fas fa-info-circle"></i>
                        Información Básica
                    </button>
                    <button type="button" class="tab-btn" data-tab="content" onclick="promptsModule.switchEditorTab('content')">
                        <i class="fas fa-edit"></i>
                        Contenido del Prompt
                    </button>
                    <button type="button" class="tab-btn" data-tab="variables" onclick="promptsModule.switchEditorTab('variables')">
                        <i class="fas fa-code"></i>
                        Variables
                    </button>
                    <button type="button" class="tab-btn" data-tab="testing" onclick="promptsModule.switchEditorTab('testing')">
                        <i class="fas fa-flask"></i>
                        Pruebas
                    </button>
                    <button type="button" class="tab-btn" data-tab="advanced" onclick="promptsModule.switchEditorTab('advanced')">
                        <i class="fas fa-cogs"></i>
                        Configuración Avanzada
                    </button>
                </div>
                
                <!-- Tab 1: Información Básica -->
                <div class="tab-content active" id="basic-tab">
                    <div class="form-grid">
                        <div class="form-group span-2">
                            <label for="prompt-name" class="required">Nombre del Prompt</label>
                            <input type="text" id="prompt-name" class="form-control" placeholder="Ej: Análisis de Sentimiento para Reseñas de Hoteles" required>
                            <div class="form-help">Nombre descriptivo que identifique claramente el propósito del prompt</div>
                        </div>
                        
                        <div class="form-group">
                            <label for="prompt-category" class="required">Categoría</label>
                            <select id="prompt-category" class="form-control form-select" required>
                                <option value="">Seleccionar categoría</option>
                                <option value="sentiment">Análisis de Sentimiento</option>
                                <option value="extraction">Extracción de Datos</option>
                                <option value="translation">Traducción</option>
                                <option value="classification">Clasificación</option>
                                <option value="summary">Resumen</option>
                                <option value="custom">Personalizado</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="prompt-language" class="required">Idioma Principal</label>
                            <select id="prompt-language" class="form-control form-select" required>
                                <option value="es">Español</option>
                                <option value="en">Inglés</option>
                                <option value="fr">Francés</option>
                                <option value="de">Alemán</option>
                                <option value="it">Italiano</option>
                                <option value="pt">Portugués</option>
                            </select>
                        </div>
                        
                        <div class="form-group span-3">
                            <label for="prompt-description">Descripción</label>
                            <textarea id="prompt-description" class="form-control" rows="3" placeholder="Describe qué hace este prompt y en qué situaciones debería usarse"></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="prompt-status">Estado</label>
                            <select id="prompt-status" class="form-control form-select">
                                <option value="draft">Borrador</option>
                                <option value="active">Activo</option>
                                <option value="archived">Archivado</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="prompt-version">Versión</label>
                            <input type="text" id="prompt-version" class="form-control" placeholder="1.0" value="1.0">
                            <div class="form-help">Se incrementa automáticamente al guardar cambios</div>
                        </div>
                        
                        <div class="form-group">
                            <label for="prompt-tags">Tags</label>
                            <input type="text" id="prompt-tags" class="form-control" placeholder="reseñas, hoteles, sentiment">
                            <div class="form-help">Separar con comas para facilitar búsquedas</div>
                        </div>
                    </div>
                </div>
                
                <!-- Tab 2: Contenido del Prompt -->
                <div class="tab-content" id="content-tab">
                    <div class="content-editor">
                        <div class="editor-toolbar">
                            <div class="toolbar-group">
                                <button type="button" class="btn btn-sm btn-secondary" onclick="promptsModule.insertTemplate('system')" title="Insertar mensaje de sistema">
                                    <i class="fas fa-robot"></i>
                                    Sistema
                                </button>
                                <button type="button" class="btn btn-sm btn-secondary" onclick="promptsModule.insertTemplate('user')" title="Insertar mensaje de usuario">
                                    <i class="fas fa-user"></i>
                                    Usuario
                                </button>
                                <button type="button" class="btn btn-sm btn-secondary" onclick="promptsModule.insertTemplate('assistant')" title="Insertar respuesta esperada">
                                    <i class="fas fa-robot"></i>
                                    Asistente
                                </button>
                            </div>
                            
                            <div class="toolbar-group">
                                <button type="button" class="btn btn-sm btn-info" onclick="promptsModule.insertVariable()" title="Insertar variable">
                                    <i class="fas fa-code"></i>
                                    Variable
                                </button>
                                <button type="button" class="btn btn-sm btn-info" onclick="promptsModule.showVariablesList()" title="Ver variables disponibles">
                                    <i class="fas fa-list"></i>
                                    Variables
                                </button>
                            </div>
                            
                            <div class="toolbar-group">
                                <button type="button" class="btn btn-sm btn-success" onclick="promptsModule.formatPrompt()" title="Formatear prompt">
                                    <i class="fas fa-magic"></i>
                                    Formatear
                                </button>
                                <button type="button" class="btn btn-sm btn-warning" onclick="promptsModule.validatePrompt()" title="Validar sintaxis">
                                    <i class="fas fa-check-circle"></i>
                                    Validar
                                </button>
                            </div>
                        </div>
                        
                        <div class="editor-container">
                            <div class="editor-main">
                                <label for="prompt-content" class="required">Contenido del Prompt</label>
                                <textarea id="prompt-content" class="form-control code-editor" rows="20" placeholder="Escribe aquí el contenido de tu prompt. Usa {variable_name} para insertar variables dinámicas." required></textarea>
                                
                                <div class="editor-footer">
                                    <div class="editor-stats">
                                        <span id="content-chars">0 caracteres</span>
                                        <span id="content-tokens">~0 tokens</span>
                                        <span id="content-variables">0 variables</span>
                                    </div>
                                    
                                    <div class="editor-actions">
                                        <button type="button" class="btn btn-sm btn-secondary" onclick="promptsModule.previewPrompt()">
                                            <i class="fas fa-eye"></i>
                                            Vista Previa
                                        </button>
                                        <button type="button" class="btn btn-sm btn-info" onclick="promptsModule.testPromptContent()">
                                            <i class="fas fa-play"></i>
                                            Probar
                                        </button>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="editor-sidebar">
                                <div class="sidebar-section">
                                    <h4>
                                        <i class="fas fa-lightbulb"></i>
                                        Consejos
                                    </h4>
                                    <ul class="tips-list">
                                        <li>Sé específico en las instrucciones</li>
                                        <li>Usa ejemplos para clarificar</li>
                                        <li>Define el formato de respuesta esperado</li>
                                        <li>Incluye contexto relevante</li>
                                        <li>Prueba con diferentes inputs</li>
                                    </ul>
                                </div>
                                
                                <div class="sidebar-section">
                                    <h4>
                                        <i class="fas fa-code"></i>
                                        Variables Comunes
                                    </h4>
                                    <div class="variables-list">
                                        <div class="variable-item" onclick="promptsModule.insertVariableText('{review_text}')">
                                            <code>{review_text}</code>
                                            <small>Texto de la reseña</small>
                                        </div>
                                        <div class="variable-item" onclick="promptsModule.insertVariableText('{hotel_name}')">
                                            <code>{hotel_name}</code>
                                            <small>Nombre del hotel</small>
                                        </div>
                                        <div class="variable-item" onclick="promptsModule.insertVariableText('{user_language}')">
                                            <code>{user_language}</code>
                                            <small>Idioma del usuario</small>
                                        </div>
                                        <div class="variable-item" onclick="promptsModule.insertVariableText('{date}')">
                                            <code>{date}</code>
                                            <small>Fecha actual</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Tab 3: Variables -->
                <div class="tab-content" id="variables-tab">
                    <div class="variables-manager">
                        <div class="variables-header">
                            <h4>
                                <i class="fas fa-code"></i>
                                Gestión de Variables
                            </h4>
                            <button type="button" class="btn btn-primary btn-sm" onclick="promptsModule.addCustomVariable()">
                                <i class="fas fa-plus"></i>
                                Nueva Variable
                            </button>
                        </div>
                        
                        <div class="variables-grid">
                            <!-- Variables detectadas automáticamente -->
                            <div class="variables-section">
                                <h5>Variables Detectadas</h5>
                                <div id="detected-variables" class="variables-container">
                                    <!-- Se llenan dinámicamente -->
                                </div>
                            </div>
                            
                            <!-- Variables personalizadas -->
                            <div class="variables-section">
                                <h5>Variables Personalizadas</h5>
                                <div id="custom-variables" class="variables-container">
                                    <!-- Se llenan dinámicamente -->
                                </div>
                            </div>
                        </div>
                        
                        <div class="variables-preview">
                            <h5>Preview con Variables de Ejemplo</h5>
                            <div id="variables-preview-content" class="preview-box">
                                <!-- Preview dinámico -->
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Tab 4: Pruebas -->
                <div class="tab-content" id="testing-tab">
                    <div class="testing-environment">
                        <div class="testing-header">
                            <h4>
                                <i class="fas fa-flask"></i>
                                Entorno de Pruebas
                            </h4>
                            <div class="testing-controls">
                                <select id="test-provider" class="form-control form-select">
                                    <option value="">Seleccionar proveedor IA</option>
                                    <!-- Se llena dinámicamente con proveedores configurados -->
                                </select>
                                <button type="button" class="btn btn-success" onclick="promptsModule.runTest()">
                                    <i class="fas fa-play"></i>
                                    Ejecutar Prueba
                                </button>
                            </div>
                        </div>
                        
                        <div class="testing-panels">
                            <div class="test-input-panel">
                                <h5>Datos de Entrada</h5>
                                <div class="test-inputs" id="test-inputs-container">
                                    <!-- Inputs dinámicos basados en variables -->
                                </div>
                                
                                <div class="test-examples">
                                    <h6>Ejemplos Predefinidos</h6>
                                    <div class="examples-list">
                                        <button type="button" class="btn btn-sm btn-secondary" onclick="promptsModule.loadTestExample('positive')">
                                            <i class="fas fa-smile"></i>
                                            Reseña Positiva
                                        </button>
                                        <button type="button" class="btn btn-sm btn-secondary" onclick="promptsModule.loadTestExample('negative')">
                                            <i class="fas fa-frown"></i>
                                            Reseña Negativa
                                        </button>
                                        <button type="button" class="btn btn-sm btn-secondary" onclick="promptsModule.loadTestExample('neutral')">
                                            <i class="fas fa-meh"></i>
                                            Reseña Neutral
                                        </button>
                                        <button type="button" class="btn btn-sm btn-secondary" onclick="promptsModule.loadTestExample('mixed')">
                                            <i class="fas fa-balance-scale"></i>
                                            Reseña Mixta
                                        </button>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="test-output-panel">
                                <h5>Resultado de la Prueba</h5>
                                <div id="test-results" class="test-results">
                                    <div class="no-test">
                                        <i class="fas fa-flask"></i>
                                        <p>Configura los datos de entrada y ejecuta una prueba</p>
                                    </div>
                                </div>
                                
                                <div class="test-metrics" id="test-metrics" style="display: none;">
                                    <div class="metric">
                                        <label>Tiempo de Respuesta:</label>
                                        <span id="response-time">--</span>
                                    </div>
                                    <div class="metric">
                                        <label>Tokens Usados:</label>
                                        <span id="tokens-used">--</span>
                                    </div>
                                    <div class="metric">
                                        <label>Costo Estimado:</label>
                                        <span id="estimated-cost">--</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="testing-history">
                            <h5>Historial de Pruebas</h5>
                            <div id="test-history" class="history-list">
                                <!-- Historial de pruebas -->
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Tab 5: Configuración Avanzada -->
                <div class="tab-content" id="advanced-tab">
                    <div class="advanced-config">
                        <div class="config-section">
                            <h4>
                                <i class="fas fa-cogs"></i>
                                Configuración del Modelo IA
                            </h4>
                            
                            <div class="form-grid">
                                <div class="form-group">
                                    <label for="model-temperature">Temperatura</label>
                                    <input type="range" id="model-temperature" class="form-range" min="0" max="2" step="0.1" value="0.7">
                                    <div class="range-value">0.7</div>
                                    <div class="form-help">Controla la creatividad. 0 = determinista, 2 = muy creativo</div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="max-tokens">Tokens Máximos</label>
                                    <input type="number" id="max-tokens" class="form-control" value="1000" min="50" max="4000">
                                    <div class="form-help">Límite de tokens en la respuesta</div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="top-p">Top P</label>
                                    <input type="range" id="top-p" class="form-range" min="0" max="1" step="0.05" value="0.9">
                                    <div class="range-value">0.9</div>
                                    <div class="form-help">Sampling por núcleo de probabilidad</div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="frequency-penalty">Penalización de Frecuencia</label>
                                    <input type="range" id="frequency-penalty" class="form-range" min="0" max="2" step="0.1" value="0">
                                    <div class="range-value">0</div>
                                    <div class="form-help">Reduce repeticiones basadas en frecuencia</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="config-section">
                            <h4>
                                <i class="fas fa-shield-alt"></i>
                                Filtros y Validación
                            </h4>
                            
                            <div class="form-grid">
                                <div class="form-group span-2">
                                    <label class="checkbox-item">
                                        <input type="checkbox" id="enable-content-filter">
                                        <span class="checkbox-mark"></span>
                                        <span class="checkbox-label">Habilitar filtro de contenido</span>
                                    </label>
                                    <div class="form-help">Filtra contenido inapropiado o sensible</div>
                                </div>
                                
                                <div class="form-group span-2">
                                    <label class="checkbox-item">
                                        <input type="checkbox" id="validate-output-format">
                                        <span class="checkbox-mark"></span>
                                        <span class="checkbox-label">Validar formato de salida</span>
                                    </label>
                                    <div class="form-help">Verifica que la respuesta tenga el formato esperado</div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="retry-attempts">Intentos de Reintento</label>
                                    <select id="retry-attempts" class="form-control form-select">
                                        <option value="0">Sin reintentos</option>
                                        <option value="1">1 reintento</option>
                                        <option value="2" selected>2 reintentos</option>
                                        <option value="3">3 reintentos</option>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label for="timeout-seconds">Timeout (segundos)</label>
                                    <input type="number" id="timeout-seconds" class="form-control" value="30" min="10" max="300">
                                </div>
                            </div>
                        </div>
                        
                        <div class="config-section">
                            <h4>
                                <i class="fas fa-chart-bar"></i>
                                Métricas y Logging
                            </h4>
                            
                            <div class="form-grid">
                                <div class="form-group span-2">
                                    <label class="checkbox-item">
                                        <input type="checkbox" id="track-usage" checked>
                                        <span class="checkbox-mark"></span>
                                        <span class="checkbox-label">Rastrear uso y métricas</span>
                                    </label>
                                </div>
                                
                                <div class="form-group span-2">
                                    <label class="checkbox-item">
                                        <input type="checkbox" id="log-requests">
                                        <span class="checkbox-mark"></span>
                                        <span class="checkbox-label">Registrar requests y responses</span>
                                    </label>
                                </div>
                                
                                <div class="form-group">
                                    <label for="log-level">Nivel de Log</label>
                                    <select id="log-level" class="form-control form-select">
                                        <option value="error">Solo errores</option>
                                        <option value="warning">Errores y warnings</option>
                                        <option value="info" selected>Info, warnings y errores</option>
                                        <option value="debug">Todos (debug)</option>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label for="retention-days">Retención de Logs (días)</label>
                                    <input type="number" id="retention-days" class="form-control" value="30" min="1" max="365">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="modal-footer">
                <div class="footer-info">
                    <span id="prompt-last-saved">Sin guardar</span>
                    <span id="prompt-usage-count">0 usos</span>
                </div>
                
                <div class="footer-actions">
                    <button type="button" class="btn btn-secondary" onclick="promptsModule.closePromptModal()">
                        <i class="fas fa-times"></i>
                        Cancelar
                    </button>
                    <button type="button" class="btn btn-info" onclick="promptsModule.saveAsDraft()">
                        <i class="fas fa-save"></i>
                        Guardar Borrador
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-check"></i>
                        Guardar Prompt
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Modal de Vista Previa -->
<div class="modal-overlay" id="prompt-preview-modal">
    <div class="modal modal-lg">
        <div class="modal-header">
            <h3 class="modal-title">
                <i class="fas fa-eye"></i>
                Vista Previa del Prompt
            </h3>
            <button class="modal-close" type="button" onclick="promptsModule.closePreviewModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <div class="modal-body">
            <div id="prompt-preview-content" class="preview-container">
                <!-- Contenido de la vista previa -->
            </div>
        </div>
        
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="promptsModule.closePreviewModal()">
                Cerrar
            </button>
            <button type="button" class="btn btn-primary" onclick="promptsModule.copyPreviewToClipboard()">
                <i class="fas fa-copy"></i>
                Copiar
            </button>
        </div>
    </div>
</div>

<!-- Templates para variables dinámicas -->
<template id="custom-variable-template">
    <div class="variable-item custom" data-variable="{name}">
        <div class="variable-header">
            <input type="text" class="variable-name" placeholder="nombre_variable" value="{name}">
            <button type="button" class="btn btn-xs btn-danger" onclick="promptsModule.removeCustomVariable(this)">
                <i class="fas fa-trash"></i>
            </button>
        </div>
        <div class="variable-details">
            <select class="variable-type form-select">
                <option value="text">Texto</option>
                <option value="number">Número</option>
                <option value="date">Fecha</option>
                <option value="boolean">Booleano</option>
            </select>
            <input type="text" class="variable-description" placeholder="Descripción de la variable">
            <input type="text" class="variable-default" placeholder="Valor por defecto (opcional)">
        </div>
    </div>
</template>

<template id="test-input-template">
    <div class="test-input-group">
        <label for="test-{name}">{label}</label>
        <div class="input-with-help">
            <input type="{type}" id="test-{name}" class="form-control" placeholder="{placeholder}" value="{default_value}">
            <small class="input-help">{description}</small>
        </div>
    </div>
</template>

<style>
/* Estilos específicos para el modal de prompts */
.prompt-editor {
    padding: 0;
    max-height: 80vh;
    display: flex;
    flex-direction: column;
}

.editor-tabs {
    display: flex;
    border-bottom: 1px solid var(--border-color);
    background: var(--light-gray);
    flex-shrink: 0;
}

.tab-btn {
    padding: 1rem 1.5rem;
    background: none;
    border: none;
    color: var(--gray);
    cursor: pointer;
    border-bottom: 2px solid transparent;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
}

.tab-btn:hover {
    background: rgba(255, 255, 255, 0.5);
    color: var(--text-color);
}

.tab-btn.active {
    color: var(--primary);
    background: white;
    border-bottom-color: var(--primary);
}

.tab-content {
    display: none;
    flex: 1;
    overflow-y: auto;
    padding: 1.5rem;
}

.tab-content.active {
    display: block;
}

/* Formulario básico */
.form-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1rem;
}

.form-group.span-2 {
    grid-column: span 2;
}

.form-group.span-3 {
    grid-column: span 3;
}

.form-help {
    font-size: 0.75rem;
    color: var(--gray);
    margin-top: 0.25rem;
}

/* Editor de contenido */
.content-editor {
    height: 100%;
    display: flex;
    flex-direction: column;
}

.editor-toolbar {
    display: flex;
    gap: 1rem;
    padding: 1rem;
    background: var(--light-gray);
    border-bottom: 1px solid var(--border-color);
    flex-shrink: 0;
}

.toolbar-group {
    display: flex;
    gap: 0.25rem;
}

.editor-container {
    flex: 1;
    display: grid;
    grid-template-columns: 1fr 300px;
    gap: 1rem;
    min-height: 0;
}

.editor-main {
    display: flex;
    flex-direction: column;
    min-height: 0;
}

.editor-main label {
    margin-bottom: 0.5rem;
    font-weight: 500;
}

.code-editor {
    flex: 1;
    font-family: 'Courier New', monospace;
    font-size: 0.875rem;
    line-height: 1.5;
    resize: none;
    background: #1a1a1a;
    color: #e0e0e0;
    border: 1px solid #333;
}

.code-editor:focus {
    background: #1a1a1a;
    color: #e0e0e0;
    border-color: var(--primary);
}

.editor-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 1rem;
    padding: 0.75rem;
    background: var(--light-gray);
    border-radius: var(--border-radius);
    flex-shrink: 0;
}

.editor-stats {
    display: flex;
    gap: 1rem;
    font-size: 0.75rem;
    color: var(--gray);
}

.editor-actions {
    display: flex;
    gap: 0.5rem;
}

.editor-sidebar {
    background: var(--light-gray);
    border-radius: var(--border-radius);
    padding: 1rem;
    overflow-y: auto;
}

.sidebar-section {
    margin-bottom: 1.5rem;
}

.sidebar-section:last-child {
    margin-bottom: 0;
}

.sidebar-section h4 {
    margin: 0 0 0.75rem 0;
    font-size: 0.9rem;
    color: var(--text-color);
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.tips-list {
    margin: 0;
    padding-left: 1rem;
}

.tips-list li {
    font-size: 0.8rem;
    color: var(--gray);
    margin-bottom: 0.25rem;
}

.variables-list {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.variable-item {
    padding: 0.5rem;
    background: white;
    border-radius: 4px;
    cursor: pointer;
    transition: all 0.2s;
}

.variable-item:hover {
    background: var(--primary);
    color: white;
}

.variable-item code {
    display: block;
    font-size: 0.75rem;
    margin-bottom: 0.25rem;
}

.variable-item small {
    font-size: 0.7rem;
    opacity: 0.8;
}

/* Variables manager */
.variables-manager {
    height: 100%;
    display: flex;
    flex-direction: column;
}

.variables-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
}

.variables-header h4 {
    margin: 0;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.variables-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.5rem;
    margin-bottom: 1.5rem;
    flex: 1;
}

.variables-section h5 {
    margin: 0 0 1rem 0;
    font-size: 0.9rem;
    color: var(--text-color);
}

.variables-container {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    max-height: 300px;
    overflow-y: auto;
}

.variable-item.custom {
    padding: 0.75rem;
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
    background: white;
    cursor: default;
}

.variable-header {
    display: flex;
    gap: 0.5rem;
    align-items: center;
    margin-bottom: 0.5rem;
}

.variable-name {
    flex: 1;
    font-size: 0.875rem;
    font-family: 'Courier New', monospace;
}

.variable-details {
    display: grid;
    grid-template-columns: 100px 1fr 120px;
    gap: 0.5rem;
    align-items: center;
}

.variable-details .form-select,
.variable-details input {
    font-size: 0.8rem;
    padding: 0.375rem;
}

.variables-preview {
    border-top: 1px solid var(--border-color);
    padding-top: 1rem;
}

.variables-preview h5 {
    margin: 0 0 0.75rem 0;
    font-size: 0.9rem;
}

.preview-box {
    background: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: 4px;
    padding: 1rem;
    font-family: 'Courier New', monospace;
    font-size: 0.875rem;
    line-height: 1.4;
    color: #495057;
    max-height: 200px;
    overflow-y: auto;
}

/* Testing environment */
.testing-environment {
    height: 100%;
    display: flex;
    flex-direction: column;
}

.testing-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
}

.testing-header h4 {
    margin: 0;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.testing-controls {
    display: flex;
    gap: 0.5rem;
    align-items: center;
}

.testing-panels {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.5rem;
    margin-bottom: 1.5rem;
    flex: 1;
}

.test-input-panel,
.test-output-panel {
    display: flex;
    flex-direction: column;
}

.test-input-panel h5,
.test-output-panel h5 {
    margin: 0 0 1rem 0;
    font-size: 0.9rem;
}

.test-inputs {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.test-input-group label {
    display: block;
    font-weight: 500;
    margin-bottom: 0.25rem;
}

.input-with-help .form-control {
    margin-bottom: 0.25rem;
}

.input-help {
    color: var(--gray);
    font-size: 0.75rem;
}

.test-examples h6 {
    margin: 0 0 0.5rem 0;
    font-size: 0.8rem;
    color: var(--gray);
}

.examples-list {
    display: flex;
    flex-wrap: wrap;
    gap: 0.25rem;
}

.test-results {
    flex: 1;
    background: #1a1a1a;
    color: #e0e0e0;
    border-radius: 4px;
    padding: 1rem;
    font-family: 'Courier New', monospace;
    font-size: 0.875rem;
    line-height: 1.4;
    overflow-y: auto;
    min-height: 200px;
}

.test-results .no-test {
    text-align: center;
    color: var(--gray);
    padding: 2rem;
}

.test-results .no-test i {
    font-size: 2rem;
    margin-bottom: 1rem;
    display: block;
}

.test-metrics {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1rem;
    margin-top: 1rem;
    padding: 1rem;
    background: var(--light-gray);
    border-radius: var(--border-radius);
}

.test-metrics .metric {
    text-align: center;
}

.test-metrics label {
    display: block;
    font-size: 0.75rem;
    color: var(--gray);
    margin-bottom: 0.25rem;
}

.test-metrics span {
    font-weight: 600;
    color: var(--text-color);
}

.testing-history h5 {
    margin: 0 0 0.75rem 0;
    font-size: 0.9rem;
}

.history-list {
    max-height: 150px;
    overflow-y: auto;
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
    background: white;
}

/* Advanced config */
.advanced-config {
    display: flex;
    flex-direction: column;
    gap: 2rem;
}

.config-section {
    background: white;
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
    padding: 1.5rem;
}

.config-section h4 {
    margin: 0 0 1rem 0;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: var(--text-color);
}

.form-range {
    width: 100%;
    margin-bottom: 0.5rem;
}

.range-value {
    text-align: center;
    font-weight: 600;
    color: var(--primary);
    font-size: 0.875rem;
}

/* Footer */
.footer-info {
    display: flex;
    gap: 2rem;
    font-size: 0.875rem;
    color: var(--gray);
}

.footer-actions {
    display: flex;
    gap: 0.5rem;
}

/* Preview modal */
.preview-container {
    background: #1a1a1a;
    color: #e0e0e0;
    border-radius: 4px;
    padding: 1.5rem;
    font-family: 'Courier New', monospace;
    font-size: 0.875rem;
    line-height: 1.6;
    max-height: 60vh;
    overflow-y: auto;
}

/* Responsive */
@media (max-width: 1200px) {
    .editor-container {
        grid-template-columns: 1fr;
    }
    
    .variables-grid {
        grid-template-columns: 1fr;
    }
    
    .testing-panels {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 768px) {
    .editor-tabs {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    
    .tab-btn {
        flex-shrink: 0;
        padding: 0.75rem 1rem;
        font-size: 0.8rem;
    }
    
    .form-grid {
        grid-template-columns: 1fr;
    }
    
    .form-group.span-2,
    .form-group.span-3 {
        grid-column: span 1;
    }
    
    .editor-toolbar {
        flex-direction: column;
        gap: 0.5rem;
    }
    
    .toolbar-group {
        justify-content: center;
    }
    
    .testing-header {
        flex-direction: column;
        gap: 1rem;
        align-items: stretch;
    }
    
    .testing-controls {
        justify-content: space-between;
    }
    
    .test-metrics {
        grid-template-columns: 1fr;
    }
    
    .footer-info {
        flex-direction: column;
        gap: 0.5rem;
    }
}
</style>