<?php
/**
 * ==========================================================================
 * PROVIDER MODAL - Kavia Hoteles Panel de Administración
 * Modal para agregar/editar proveedores de IA
 * ==========================================================================
 */
?>

<!-- Modal de Proveedor de IA -->
<div class="modal-overlay" id="provider-modal">
    <div class="modal modal-lg">
        <div class="modal-header">
            <h2 id="provider-modal-title">
                <i class="fas fa-robot"></i>
                <span id="provider-modal-title-text">Nuevo Proveedor de IA</span>
            </h2>
            <button class="modal-close" type="button" onclick="closeProviderModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div class="modal-body">
            <!-- Formulario de Proveedor -->
            <form id="provider-form" class="provider-form">
                <input type="hidden" id="provider-id" name="id">
                
                <!-- Información Básica -->
                <div class="form-section">
                    <h3 class="section-title">
                        <i class="fas fa-info-circle"></i>
                        Información Básica
                    </h3>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="provider-name" class="required">Nombre del Proveedor</label>
                            <input type="text" id="provider-name" name="name" 
                                   placeholder="ej: OpenAI GPT-4 Pro" required>
                            <small class="form-help">Nombre descriptivo para identificar el proveedor</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="provider-type" class="required">Tipo de Proveedor</label>
                            <select id="provider-type" name="provider_type" required onchange="handleProviderTypeChange()">
                                <option value="">Selecciona un tipo...</option>
                                <option value="openai">OpenAI (GPT-3.5, GPT-4, etc.)</option>
                                <option value="claude">Anthropic Claude</option>
                                <option value="deepseek">DeepSeek AI</option>
                                <option value="gemini">Google Gemini</option>
                                <option value="local">Sistema Local/Fallback</option>
                            </select>
                            <small class="form-help">Selecciona el tipo de proveedor de IA</small>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="provider-description">Descripción</label>
                        <textarea id="provider-description" name="description" 
                                  placeholder="Descripción opcional del proveedor y su uso específico..."
                                  rows="3"></textarea>
                    </div>
                </div>

                <!-- Configuración de API -->
                <div class="form-section" id="api-config-section">
                    <h3 class="section-title">
                        <i class="fas fa-key"></i>
                        Configuración de API
                    </h3>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="api-key">API Key</label>
                            <div class="input-with-toggle">
                                <input type="password" id="api-key" name="api_key" 
                                       placeholder="Introduce tu API key...">
                                <button type="button" class="toggle-password" onclick="toggleApiKeyVisibility()">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <small class="form-help">Mantén tu API key segura. Se almacenará encriptada</small>
                        </div>
                        
                        <div class="form-group" id="api-url-group" style="display: none;">
                            <label for="api-url">URL de la API</label>
                            <input type="url" id="api-url" name="api_url" 
                                   placeholder="https://api.ejemplo.com/v1">
                            <small class="form-help">URL personalizada para APIs locales o proxies</small>
                        </div>
                    </div>
                    
                    <div class="form-group" id="model-group">
                        <label for="model-name">Modelo</label>
                        <select id="model-name" name="model_name">
                            <option value="">Selecciona un modelo...</option>
                        </select>
                        <small class="form-help">Modelo específico del proveedor a utilizar</small>
                    </div>
                </div>

                <!-- Parámetros Avanzados -->
                <div class="form-section advanced-section" style="display: none;">
                    <h3 class="section-title">
                        <i class="fas fa-cogs"></i>
                        Parámetros Avanzados
                    </h3>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="temperature">Temperatura</label>
                            <input type="number" id="temperature" name="temperature" 
                                   min="0" max="2" step="0.1" value="0.7">
                            <small class="form-help">Creatividad del modelo (0 = conservador, 2 = muy creativo)</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="max-tokens">Tokens Máximos</label>
                            <input type="number" id="max-tokens" name="max_tokens" 
                                   min="1" max="4000" value="150">
                            <small class="form-help">Longitud máxima de la respuesta</small>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="custom-parameters">Parámetros Personalizados (JSON)</label>
                        <textarea id="custom-parameters" name="parameters" 
                                  placeholder='{"top_p": 0.9, "frequency_penalty": 0.0}'
                                  rows="4"></textarea>
                        <small class="form-help">Parámetros adicionales en formato JSON</small>
                    </div>
                </div>

                <!-- Configuración de Estado -->
                <div class="form-section">
                    <h3 class="section-title">
                        <i class="fas fa-toggle-on"></i>
                        Estado y Activación
                    </h3>
                    
                    <div class="form-group">
                        <div class="checkbox-group">
                            <label class="checkbox-label">
                                <input type="checkbox" id="is-active" name="is_active">
                                <span class="checkbox-custom"></span>
                                <span class="checkbox-text">Activar proveedor</span>
                            </label>
                            <small class="form-help">Solo un proveedor puede estar activo a la vez por tipo</small>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <div class="modal-footer">
            <div class="footer-left">
                <button type="button" class="btn btn-outline" onclick="toggleAdvancedOptions()">
                    <i class="fas fa-cogs"></i>
                    <span id="advanced-toggle-text">Mostrar Opciones Avanzadas</span>
                </button>
                <button type="button" class="btn btn-info" id="test-connection-btn" 
                        onclick="testProviderConnection()" disabled>
                    <i class="fas fa-plug"></i>
                    Probar Conexión
                </button>
            </div>
            <div class="footer-right">
                <button type="button" class="btn btn-secondary" onclick="closeProviderModal()">
                    Cancelar
                </button>
                <button type="submit" form="provider-form" class="btn btn-primary" id="save-provider-btn">
                    <i class="fas fa-save"></i>
                    <span id="save-btn-text">Crear Proveedor</span>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Estilos específicos del modal -->
<style>
/* Modal específico de proveedores */
.provider-form {
    display: flex;
    flex-direction: column;
    gap: 2rem;
}

.form-section {
    background: var(--bg-secondary);
    border: 1px solid var(--border-color);
    border-radius: 0.75rem;
    padding: 1.5rem;
}

.section-title {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin: 0 0 1.5rem 0;
    font-size: 1.1rem;
    font-weight: 600;
    color: var(--text-primary);
    padding-bottom: 0.75rem;
    border-bottom: 1px solid var(--border-color);
}

.section-title i {
    color: var(--primary);
    font-size: 1rem;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.5rem;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.form-group label {
    font-weight: 500;
    color: var(--text-primary);
    font-size: 0.9rem;
}

.form-group label.required::after {
    content: " *";
    color: var(--danger);
}

.form-group input,
.form-group select,
.form-group textarea {
    padding: 0.75rem;
    border: 1px solid var(--border-color);
    border-radius: 0.5rem;
    font-size: 0.9rem;
    background: white;
    transition: all 0.2s ease;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 3px var(--primary-bg);
}

/* Estados de validación */
.form-group input.error,
.form-group select.error,
.form-group textarea.error {
    border-color: var(--danger);
    background-color: #ffeaea;
    box-shadow: 0 0 0 3px rgba(220, 53, 69, 0.1);
}

.form-group input.success,
.form-group select.success,
.form-group textarea.success {
    border-color: var(--success);
    background-color: #eafff0;
    box-shadow: 0 0 0 3px rgba(40, 167, 69, 0.1);
}

.field-error {
    color: var(--danger);
    font-size: 0.8rem;
    margin-top: 0.25rem;
    display: block;
}

/* Indicadores visuales para campos validados */
.form-group input.success::after,
.form-group select.success::after {
    content: '✓';
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--success);
    font-weight: bold;
}

.form-group {
    position: relative;
}

.form-help {
    font-size: 0.8rem;
    color: var(--text-muted);
    line-height: 1.3;
}

/* Input con toggle para password */
.input-with-toggle {
    position: relative;
    display: flex;
}

.input-with-toggle input {
    flex: 1;
    padding-right: 3rem;
}

.toggle-password {
    position: absolute;
    right: 0.75rem;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    color: var(--text-muted);
    cursor: pointer;
    padding: 0.25rem;
    border-radius: 0.25rem;
    transition: color 0.2s ease;
}

.toggle-password:hover {
    color: var(--text-primary);
}

/* Checkbox personalizado */
.checkbox-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.checkbox-label {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    cursor: pointer;
    padding: 0.5rem 0;
}

.checkbox-label input[type="checkbox"] {
    display: none;
}

.checkbox-custom {
    width: 20px;
    height: 20px;
    border: 2px solid var(--border-color);
    border-radius: 0.25rem;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
    background: white;
}

.checkbox-label input:checked + .checkbox-custom {
    background: var(--primary);
    border-color: var(--primary);
}

.checkbox-label input:checked + .checkbox-custom::after {
    content: '✓';
    color: white;
    font-size: 0.8rem;
    font-weight: bold;
}

.checkbox-text {
    font-weight: 500;
    color: var(--text-primary);
}

/* Advanced section */
.advanced-section {
    border-color: var(--warning);
    background: var(--warning-bg);
}

/* Modal footer customizado */
.modal-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.5rem 2rem;
    background: var(--bg-secondary);
    border-top: 1px solid var(--border-color);
    border-radius: 0 0 0.75rem 0.75rem;
}

.footer-left,
.footer-right {
    display: flex;
    gap: 1rem;
    align-items: center;
}

/* Estados del botón de test */
.btn.testing {
    background: var(--warning);
    border-color: var(--warning);
    color: white;
    position: relative;
    pointer-events: none;
}

.btn.testing::after {
    content: '';
    position: absolute;
    top: 50%;
    right: 1rem;
    transform: translateY(-50%);
    width: 12px;
    height: 12px;
    border: 2px solid white;
    border-top-color: transparent;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

.btn.success {
    background: var(--success);
    border-color: var(--success);
    color: white;
}

.btn.error {
    background: var(--danger);
    border-color: var(--danger);
    color: white;
}

/* Responsive */
@media (max-width: 768px) {
    .modal-container.large {
        max-width: 95vw;
        margin: 1rem;
    }
    
    .form-row {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    
    .modal-footer {
        flex-direction: column;
        gap: 1rem;
        align-items: stretch;
    }
    
    .footer-left,
    .footer-right {
        justify-content: center;
        flex-wrap: wrap;
    }
}
</style>

<script>
/**
 * Funciones específicas del modal de proveedores
 */

// Configuraciones de modelos por tipo de proveedor
const PROVIDER_MODELS = {
    'openai': [
        { value: 'gpt-4-turbo', label: 'GPT-4 Turbo (Recomendado)' },
        { value: 'gpt-4', label: 'GPT-4' },
        { value: 'gpt-3.5-turbo', label: 'GPT-3.5 Turbo' },
        { value: 'gpt-3.5-turbo-16k', label: 'GPT-3.5 Turbo 16K' }
    ],
    'claude': [
        { value: 'claude-3-sonnet-20240229', label: 'Claude 3 Sonnet (Recomendado)' },
        { value: 'claude-3-haiku-20240307', label: 'Claude 3 Haiku (Rápido)' },
        { value: 'claude-3-opus-20240229', label: 'Claude 3 Opus (Potente)' }
    ],
    'deepseek': [
        { value: 'deepseek-chat', label: 'DeepSeek Chat (Recomendado)' },
        { value: 'deepseek-coder', label: 'DeepSeek Coder' }
    ],
    'gemini': [
        { value: 'gemini-pro', label: 'Gemini Pro (Recomendado)' },
        { value: 'gemini-pro-vision', label: 'Gemini Pro Vision' }
    ],
    'local': [
        { value: 'local', label: 'Sistema Local' }
    ]
};

/**
 * Maneja el cambio de tipo de proveedor
 */
function handleProviderTypeChange() {
    const typeSelect = document.getElementById('provider-type');
    const modelSelect = document.getElementById('model-name');
    const apiUrlGroup = document.getElementById('api-url-group');
    const apiConfigSection = document.getElementById('api-config-section');
    const testBtn = document.getElementById('test-connection-btn');
    
    const selectedType = typeSelect.value;
    
    // Limpiar opciones anteriores
    modelSelect.innerHTML = '<option value="">Selecciona un modelo...</option>';
    
    if (selectedType && PROVIDER_MODELS[selectedType]) {
        // Llenar modelos según el tipo
        PROVIDER_MODELS[selectedType].forEach(model => {
            const option = document.createElement('option');
            option.value = model.value;
            option.textContent = model.label;
            modelSelect.appendChild(option);
        });
        
        // Configuraciones específicas por tipo
        if (selectedType === 'local') {
            apiConfigSection.style.display = 'none';
            testBtn.disabled = false;
        } else {
            apiConfigSection.style.display = 'block';
            apiUrlGroup.style.display = selectedType === 'local' ? 'block' : 'none';
            testBtn.disabled = true; // Habilitar cuando haya API key
        }
    }
    
    console.log(`🔄 Tipo de proveedor cambiado a: ${selectedType}`);
}

/**
 * Toggle visibilidad de API key
 */
function toggleApiKeyVisibility() {
    const apiKeyInput = document.getElementById('api-key');
    const toggleBtn = apiKeyInput.nextElementSibling.querySelector('i');
    
    if (apiKeyInput.type === 'password') {
        apiKeyInput.type = 'text';
        toggleBtn.className = 'fas fa-eye-slash';
    } else {
        apiKeyInput.type = 'password';
        toggleBtn.className = 'fas fa-eye';
    }
}

/**
 * Toggle opciones avanzadas
 */
function toggleAdvancedOptions() {
    const advancedSection = document.querySelector('.advanced-section');
    const toggleBtn = document.getElementById('advanced-toggle-text');
    
    if (advancedSection.style.display === 'none') {
        advancedSection.style.display = 'block';
        toggleBtn.textContent = 'Ocultar Opciones Avanzadas';
    } else {
        advancedSection.style.display = 'none';
        toggleBtn.textContent = 'Mostrar Opciones Avanzadas';
    }
}

/**
 * Prueba la conexión con el proveedor
 */
async function testProviderConnection() {
    const testBtn = document.getElementById('test-connection-btn');
    const originalText = testBtn.innerHTML;
    
    // UI feedback
    testBtn.classList.add('testing');
    testBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Probando...';
    testBtn.disabled = true;
    
    try {
        const formData = new FormData(document.getElementById('provider-form'));
        const data = Object.fromEntries(formData.entries());
        
        console.log('🧪 Probando conexión con proveedor...', data.provider_type);
        
        // Aquí iría la lógica real de test
        const response = await apiClient.post('admin_api.php', {
            action: 'testAiProvider',
            ...data
        });
        
        if (response.success) {
            testBtn.classList.remove('testing');
            testBtn.classList.add('success');
            testBtn.innerHTML = '<i class="fas fa-check"></i> Conexión Exitosa';
            
            showSuccess('Conexión establecida correctamente con ' + data.provider_type);
            
            // Restaurar después de 3 segundos
            setTimeout(() => {
                testBtn.classList.remove('success');
                testBtn.innerHTML = originalText;
                testBtn.disabled = false;
            }, 3000);
        } else {
            throw new Error(response.error || 'Error de conexión');
        }
        
    } catch (error) {
        console.error('❌ Error al probar conexión:', error);
        
        testBtn.classList.remove('testing');
        testBtn.classList.add('error');
        testBtn.innerHTML = '<i class="fas fa-times"></i> Error de Conexión';
        
        showError('Error al conectar: ' + error.message);
        
        // Restaurar después de 3 segundos
        setTimeout(() => {
            testBtn.classList.remove('error');
            testBtn.innerHTML = originalText;
            testBtn.disabled = false;
        }, 3000);
    }
}

/**
 * Cierra el modal de proveedor
 */
function closeProviderModal() {
    const modal = document.getElementById('provider-modal');
    if (modal && window.modalManager) {
        window.modalManager.close('provider-modal');
    }
}

// Event listeners
document.addEventListener('DOMContentLoaded', function() {
    const providerForm = document.getElementById('provider-form');
    const apiKeyInput = document.getElementById('api-key');
    const testBtn = document.getElementById('test-connection-btn');
    
    // Validar API key para habilitar test
    if (apiKeyInput && testBtn) {
        apiKeyInput.addEventListener('input', function() {
            const hasApiKey = this.value.trim().length > 0;
            const providerType = document.getElementById('provider-type').value;
            
            testBtn.disabled = !hasApiKey && providerType !== 'local';
            
            // Validar formato de API key según el tipo
            validateApiKey(providerType, this.value.trim());
        });
    }
    
    // Validación en tiempo real del formulario
    const formInputs = providerForm.querySelectorAll('input[required], select[required]');
    formInputs.forEach(input => {
        input.addEventListener('blur', function() {
            validateField(this);
        });
        
        input.addEventListener('input', function() {
            if (this.classList.contains('error')) {
                validateField(this);
            }
        });
    });
    
    // Validar parámetros JSON
    const customParamsInput = document.getElementById('custom-parameters');
    if (customParamsInput) {
        customParamsInput.addEventListener('blur', function() {
            validateJsonParameters(this);
        });
    }
    
    // Submit del formulario
    if (providerForm) {
        providerForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            // Validar formulario antes de enviar
            if (!validateProviderForm()) {
                showError('Por favor, corrije los errores en el formulario');
                return;
            }
            
            const saveBtn = document.getElementById('save-provider-btn');
            const originalText = saveBtn.innerHTML;
            
            try {
                saveBtn.disabled = true;
                saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
                
                const formData = new FormData(this);
                const data = Object.fromEntries(formData.entries());
                
                console.log('💾 Guardando proveedor...', data);
                
                if (window.providersModule && window.providersModule.saveProvider) {
                    await window.providersModule.saveProvider(data);
                } else {
                    // Fallback directo a API
                    const action = data.id ? 'updateAiProvider' : 'saveAiProvider';
                    const response = await apiClient.post('admin_api.php', {
                        action: action,
                        ...data
                    });
                    
                    if (response.success) {
                        showSuccess(response.message || 'Proveedor guardado correctamente');
                        closeProviderModal();
                        
                        // Refrescar lista si existe el módulo
                        if (window.providersModule && window.providersModule.refresh) {
                            window.providersModule.refresh();
                        }
                    } else {
                        throw new Error(response.error || 'Error al guardar proveedor');
                    }
                }
                
            } catch (error) {
                console.error('❌ Error al guardar proveedor:', error);
                showError('Error al guardar: ' + error.message);
            } finally {
                saveBtn.disabled = false;
                saveBtn.innerHTML = originalText;
            }
        });
    }
    
    console.log('✅ Modal de proveedores inicializado');
});

/**
 * Funciones de validación
 */

// Validar campo individual
function validateField(field) {
    const value = field.value.trim();
    const fieldName = field.name;
    let isValid = true;
    let errorMessage = '';
    
    // Remover clases de error anteriores
    field.classList.remove('error', 'success');
    removeFieldError(field);
    
    // Validaciones básicas
    if (field.hasAttribute('required') && !value) {
        isValid = false;
        errorMessage = 'Este campo es requerido';
    } else if (fieldName === 'name' && value.length < 3) {
        isValid = false;
        errorMessage = 'El nombre debe tener al menos 3 caracteres';
    } else if (fieldName === 'api_url' && value && !isValidUrl(value)) {
        isValid = false;
        errorMessage = 'Debe ser una URL válida';
    }
    
    // Aplicar estilos según validación
    if (!isValid) {
        field.classList.add('error');
        showFieldError(field, errorMessage);
    } else if (value) {
        field.classList.add('success');
    }
    
    return isValid;
}

// Validar API key según tipo de proveedor
function validateApiKey(providerType, apiKey) {
    const apiKeyInput = document.getElementById('api-key');
    if (!apiKeyInput || !apiKey) return;
    
    let isValid = true;
    let message = '';
    
    apiKeyInput.classList.remove('error', 'success');
    removeFieldError(apiKeyInput);
    
    switch (providerType) {
        case 'openai':
            // OpenAI keys start with sk-
            if (!apiKey.startsWith('sk-') || apiKey.length < 20) {
                isValid = false;
                message = 'Las API keys de OpenAI deben comenzar con "sk-"';
            }
            break;
            
        case 'claude':
            // Claude keys start with sk-ant-
            if (!apiKey.startsWith('sk-ant-') || apiKey.length < 30) {
                isValid = false;
                message = 'Las API keys de Claude deben comenzar con "sk-ant-"';
            }
            break;
            
        case 'deepseek':
            // DeepSeek keys are typically longer
            if (apiKey.length < 15) {
                isValid = false;
                message = 'API key de DeepSeek parece muy corta';
            }
            break;
            
        case 'gemini':
            // Gemini keys are typically 39 characters
            if (apiKey.length < 30) {
                isValid = false;
                message = 'API key de Gemini parece muy corta';
            }
            break;
    }
    
    if (!isValid) {
        apiKeyInput.classList.add('error');
        showFieldError(apiKeyInput, message);
    } else {
        apiKeyInput.classList.add('success');
    }
}

// Validar parámetros JSON
function validateJsonParameters(textarea) {
    const value = textarea.value.trim();
    
    textarea.classList.remove('error', 'success');
    removeFieldError(textarea);
    
    if (!value) return true; // Opcional
    
    try {
        JSON.parse(value);
        textarea.classList.add('success');
        return true;
    } catch (e) {
        textarea.classList.add('error');
        showFieldError(textarea, 'JSON inválido: ' + e.message);
        return false;
    }
}

// Mostrar error en campo
function showFieldError(field, message) {
    removeFieldError(field);
    
    const errorDiv = document.createElement('div');
    errorDiv.className = 'field-error';
    errorDiv.textContent = message;
    errorDiv.style.cssText = `
        color: var(--danger);
        font-size: 0.8rem;
        margin-top: 0.25rem;
        display: block;
    `;
    
    field.parentNode.appendChild(errorDiv);
}

// Remover error de campo
function removeFieldError(field) {
    const existingError = field.parentNode.querySelector('.field-error');
    if (existingError) {
        existingError.remove();
    }
}

// Validar URL
function isValidUrl(string) {
    try {
        new URL(string);
        return true;
    } catch (_) {
        return false;
    }
}

// Validar todo el formulario
function validateProviderForm() {
    const form = document.getElementById('provider-form');
    const requiredFields = form.querySelectorAll('input[required], select[required]');
    let isValid = true;
    
    requiredFields.forEach(field => {
        if (!validateField(field)) {
            isValid = false;
        }
    });
    
    // Validar JSON si tiene contenido
    const customParams = document.getElementById('custom-parameters');
    if (customParams && customParams.value.trim()) {
        if (!validateJsonParameters(customParams)) {
            isValid = false;
        }
    }
    
    return isValid;
}
</script>