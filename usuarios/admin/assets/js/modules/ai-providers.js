/**
 * ==========================================================================
 * AI PROVIDERS MODULE - Kavia Hoteles Panel de Administración
 * Módulo JavaScript para Gestión de Proveedores de IA
 * ==========================================================================
 */

class AIProvidersModule {
    constructor() {
        this.providersData = [];
        this.init();
    }
    
    init() {
        console.log('🤖 AI Providers Module inicializado');
    }
    
    /**
     * Cargar proveedores de IA desde el backend
     */
    async loadProviders() {
        console.log('🤖 Cargando proveedores de IA...');
        const container = document.getElementById('ai-providers-content-direct');
        
        if (!container) return;
        
        container.innerHTML = '<div style="text-align: center; padding: 20px;"><i class="fas fa-spinner fa-spin"></i> Cargando proveedores de IA...</div>';
        
        try {
            const response = await fetch('admin_api.php?action=getAIProviders');
            const result = await response.json();
            
            if (result.success) {
                this.providersData = result.providers || [];
                this.renderProvidersTable();
            } else {
                throw new Error(result.error || 'Error al cargar proveedores de IA');
            }
        } catch (error) {
            container.innerHTML = `<div style="color: #dc3545; text-align: center; padding: 20px;">❌ Error: ${error.message}</div>`;
        }
    }
    
    /**
     * Renderizar tabla de proveedores
     */
    renderProvidersTable() {
        const container = document.getElementById('ai-providers-content-direct');
        if (!container) return;
        
        if (this.providersData.length === 0) {
            this.showEmptyState();
            return;
        }
        
        let html = `
            <div class="providers-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding: 15px; background: #f8f4ff; border-radius: 8px;">
                <h3 style="margin: 0; color: #495057;">
                    <i class="fas fa-brain"></i> 
                    Proveedores de IA (${this.providersData.length})
                </h3>
                <button class="btn btn-primary" onclick="aiProvidersModule.showAddModal()" style="background: #6f42c1; color: white; padding: 10px 20px; border: none; border-radius: 6px; cursor: pointer;">
                    <i class="fas fa-plus"></i> 
                    Agregar Proveedor
                </button>
            </div>
            
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; margin-top: 15px;">
                    <thead>
                        <tr style="background: #495057; color: white;">
                            <th style="padding: 12px; border: 1px solid #ddd;">ID</th>
                            <th style="padding: 12px; border: 1px solid #ddd;">Nombre</th>
                            <th style="padding: 12px; border: 1px solid #ddd;">Tipo</th>
                            <th style="padding: 12px; border: 1px solid #ddd;">Modelo</th>
                            <th style="padding: 12px; border: 1px solid #ddd;">Estado</th>
                            <th style="padding: 12px; border: 1px solid #ddd;">Límite</th>
                            <th style="padding: 12px; border: 1px solid #ddd;">Creado</th>
                            <th style="padding: 12px; border: 1px solid #ddd; width: 250px;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
        `;
        
        this.providersData.forEach(provider => {
            const createdAt = provider.created_at ? new Date(provider.created_at).toLocaleDateString('es-ES') : 'N/A';
            const isActive = provider.is_active == 1;
            
            html += `
                <tr style="border-bottom: 1px solid #ddd;">
                    <td style="padding: 12px; border: 1px solid #ddd;"><strong>#${provider.id}</strong></td>
                    <td style="padding: 12px; border: 1px solid #ddd;">
                        <strong>${this.escapeHtml(provider.name)}</strong>
                    </td>
                    <td style="padding: 12px; border: 1px solid #ddd;">
                        <span style="background: ${this.getProviderBadgeColor(provider.provider_type)}; color: white; padding: 4px 8px; border-radius: 4px; font-size: 12px;">
                            ${this.getProviderLabel(provider.provider_type)}
                        </span>
                    </td>
                    <td style="padding: 12px; border: 1px solid #ddd;">
                        <code style="background: #f8f9fa; padding: 2px 6px; border-radius: 3px; font-size: 11px;">
                            ${this.escapeHtml(provider.model_name || 'N/A')}
                        </code>
                    </td>
                    <td style="padding: 12px; border: 1px solid #ddd;">
                        <span style="background: ${isActive ? '#28a745' : '#dc3545'}; color: white; padding: 4px 8px; border-radius: 4px; font-size: 12px;">
                            ${isActive ? 'Activo' : 'Inactivo'}
                        </span>
                    </td>
                    <td style="padding: 12px; border: 1px solid #ddd;">
                        ${provider.rate_limit ? provider.rate_limit + '/min' : 'Sin límite'}
                    </td>
                    <td style="padding: 12px; border: 1px solid #ddd;">${createdAt}</td>
                    <td style="padding: 12px; border: 1px solid #ddd;">
                        <div style="display: flex; gap: 5px; flex-wrap: wrap;">
                            <button onclick="aiProvidersModule.viewProvider(${provider.id})" style="background: #007bff; color: white; border: none; padding: 6px 10px; border-radius: 4px; cursor: pointer; font-size: 12px;" title="Ver detalles">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button onclick="aiProvidersModule.editProvider(${provider.id})" style="background: #28a745; color: white; border: none; padding: 6px 10px; border-radius: 4px; cursor: pointer; font-size: 12px;" title="Editar">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button onclick="aiProvidersModule.testProvider(${provider.id})" style="background: #ffc107; color: #000; border: none; padding: 6px 10px; border-radius: 4px; cursor: pointer; font-size: 12px;" title="Probar conexión">
                                <i class="fas fa-flask"></i>
                            </button>
                            <button onclick="aiProvidersModule.toggleProviderStatus(${provider.id}, ${isActive ? 0 : 1})" style="background: ${isActive ? '#6c757d' : '#28a745'}; color: white; border: none; padding: 6px 10px; border-radius: 4px; cursor: pointer; font-size: 12px;" title="${isActive ? 'Desactivar' : 'Activar'}">
                                <i class="fas fa-${isActive ? 'pause' : 'play'}"></i>
                            </button>
                            <button onclick="aiProvidersModule.confirmDelete(${provider.id}, '${this.escapeHtml(provider.name).replace(/'/g, '\\\\\\\\\\\\'')})')" style="background: #dc3545; color: white; border: none; padding: 6px 10px; border-radius: 4px; cursor: pointer; font-size: 12px;" title="Eliminar">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `;
        });
        
        html += `
                    </tbody>
                </table>
            </div>
            <div style="margin-top: 15px; text-align: center; padding: 15px; background: #d1ecf1; border: 1px solid #bee5eb; border-radius: 6px;">
                <strong>🤖 ${this.providersData.length} proveedores de IA configurados</strong><br>
                <small>Sistema de IA funcionando correctamente</small>
            </div>
        `;
        
        container.innerHTML = html;
    }
    
    /**
     * Mostrar estado vacío
     */
    showEmptyState() {
        const container = document.getElementById('ai-providers-content-direct');
        if (!container) return;
        
        container.innerHTML = `
            <div style="text-align: center; padding: 40px;">
                <i class="fas fa-brain" style="font-size: 3rem; color: #6f42c1; margin-bottom: 20px;"></i>
                <h3>No hay proveedores de IA configurados</h3>
                <p style="margin: 20px 0;">Comienza agregando tu primer proveedor de IA como OpenAI, Claude, Gemini, etc.</p>
                <button onclick="aiProvidersModule.showAddModal()" style="background: #6f42c1; color: white; border: none; padding: 12px 24px; border-radius: 6px; cursor: pointer;">
                    <i class="fas fa-plus"></i> Agregar Primer Proveedor
                </button>
            </div>
        `;
    }
    
    /**
     * Mostrar modal para agregar proveedor
     */
    showAddModal() {
        this.showProviderModal();
    }
    
    /**
     * Mostrar modal de proveedor (agregar/editar)
     */
    showProviderModal(providerData = null) {
        const isEditing = providerData !== null;
        const modalTitle = isEditing ? 'Editar Proveedor de IA' : 'Agregar Nuevo Proveedor de IA';
        
        const modalHtml = `
            <div id="providerModal" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 1000; display: flex; align-items: center; justify-content: center;">
                <div style="background: white; border-radius: 10px; padding: 30px; width: 90%; max-width: 700px; max-height: 80vh; overflow-y: auto;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; border-bottom: 2px solid #6f42c1; padding-bottom: 15px;">
                        <h3 style="margin: 0; color: #495057;">
                            <i class="fas fa-brain"></i> ${modalTitle}
                        </h3>
                        <button onclick="aiProvidersModule.closeModal()" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #6c757d;">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    
                    <form id="providerForm" style="display: flex; flex-direction: column; gap: 20px;">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                            <div>
                                <label style="display: block; margin-bottom: 5px; font-weight: bold; color: #495057;">Nombre del Proveedor *</label>
                                <input type="text" id="providerName" value="${isEditing ? this.escapeHtml(providerData.name) : ''}" 
                                       style="width: 100%; padding: 10px; border: 2px solid #ddd; border-radius: 6px; box-sizing: border-box;" 
                                       placeholder="Ej: OpenAI GPT-4" required>
                            </div>
                            <div>
                                <label style="display: block; margin-bottom: 5px; font-weight: bold; color: #495057;">Tipo de Proveedor *</label>
                                <select id="providerType" style="width: 100%; padding: 10px; border: 2px solid #ddd; border-radius: 6px; box-sizing: border-box;" required>
                                    <option value="">Seleccionar tipo...</option>
                                    <option value="openai" ${isEditing && providerData.provider_type === 'openai' ? 'selected' : ''}>OpenAI</option>
                                    <option value="anthropic" ${isEditing && providerData.provider_type === 'anthropic' ? 'selected' : ''}>Anthropic (Claude)</option>
                                    <option value="google" ${isEditing && providerData.provider_type === 'google' ? 'selected' : ''}>Google (Gemini)</option>
                                    <option value="microsoft" ${isEditing && providerData.provider_type === 'microsoft' ? 'selected' : ''}>Microsoft (Azure)</option>
                                    <option value="cohere" ${isEditing && providerData.provider_type === 'cohere' ? 'selected' : ''}>Cohere</option>
                                    <option value="huggingface" ${isEditing && providerData.provider_type === 'huggingface' ? 'selected' : ''}>Hugging Face</option>
                                    <option value="custom" ${isEditing && providerData.provider_type === 'custom' ? 'selected' : ''}>Personalizado</option>
                                </select>
                            </div>
                        </div>
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                            <div>
                                <label style="display: block; margin-bottom: 5px; font-weight: bold; color: #495057;">Modelo *</label>
                                <input type="text" id="modelName" value="${isEditing ? this.escapeHtml(providerData.model_name || '') : ''}" 
                                       style="width: 100%; padding: 10px; border: 2px solid #ddd; border-radius: 6px; box-sizing: border-box;" 
                                       placeholder="Ej: gpt-4, claude-3-opus" required>
                            </div>
                            <div>
                                <label style="display: block; margin-bottom: 5px; font-weight: bold; color: #495057;">Límite por Minuto</label>
                                <input type="number" id="rateLimit" value="${isEditing ? (providerData.rate_limit || '') : ''}" 
                                       style="width: 100%; padding: 10px; border: 2px solid #ddd; border-radius: 6px; box-sizing: border-box;" 
                                       placeholder="Ej: 60" min="1">
                            </div>
                        </div>
                        
                        <div>
                            <label style="display: block; margin-bottom: 5px; font-weight: bold; color: #495057;">API Key *</label>
                            <input type="password" id="apiKey" value="${isEditing ? (providerData.api_key || '') : ''}" 
                                   style="width: 100%; padding: 10px; border: 2px solid #ddd; border-radius: 6px; box-sizing: border-box;" 
                                   placeholder="Ingresa tu API Key..." required>
                            <small style="color: #6c757d;">Esta información se almacena de forma segura</small>
                        </div>
                        
                        <div>
                            <label style="display: block; margin-bottom: 5px; font-weight: bold; color: #495057;">Endpoint Base (Opcional)</label>
                            <input type="url" id="baseUrl" value="${isEditing ? (providerData.base_url || '') : ''}" 
                                   style="width: 100%; padding: 10px; border: 2px solid #ddd; border-radius: 6px; box-sizing: border-box;" 
                                   placeholder="https://api.openai.com/v1">
                        </div>
                        
                        <div>
                            <label style="display: block; margin-bottom: 5px; font-weight: bold; color: #495057;">Configuración Adicional</label>
                            <textarea id="additionalConfig" rows="3" 
                                      style="width: 100%; padding: 10px; border: 2px solid #ddd; border-radius: 6px; resize: vertical; box-sizing: border-box;" 
                                      placeholder="JSON con configuración adicional (opcional)...">${isEditing ? this.escapeHtml(providerData.additional_config || '') : ''}</textarea>
                        </div>
                        
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <input type="checkbox" id="providerActive" ${isEditing && providerData.is_active ? 'checked' : (!isEditing ? 'checked' : '')} 
                                   style="width: 20px; height: 20px;">
                            <label for="providerActive" style="font-weight: bold; color: #495057;">Proveedor Activo</label>
                        </div>
                        
                        <div style="display: flex; gap: 10px; margin-top: 20px; padding-top: 20px; border-top: 1px solid #ddd;">
                            <button type="button" onclick="aiProvidersModule.saveProvider(${isEditing ? providerData.id : 'null'})" 
                                    style="flex: 1; background: #6f42c1; color: white; padding: 12px 20px; border: none; border-radius: 6px; cursor: pointer; font-weight: bold;">
                                <i class="fas fa-save"></i> ${isEditing ? 'Actualizar' : 'Guardar'} Proveedor
                            </button>
                            <button type="button" onclick="aiProvidersModule.closeModal()" 
                                    style="flex: 1; background: #6c757d; color: white; padding: 12px 20px; border: none; border-radius: 6px; cursor: pointer;">
                                <i class="fas fa-times"></i> Cancelar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        `;
        
        document.body.insertAdjacentHTML('beforeend', modalHtml);
    }
    
    /**
     * Cerrar modal
     */
    closeModal() {
        const modal = document.getElementById('providerModal');
        if (modal) {
            modal.remove();
        }
    }
    
    /**
     * Editar proveedor
     */
    editProvider(providerId) {
        const provider = this.providersData.find(p => p.id == providerId);
        if (!provider) {
            if (window.showError) showError('Proveedor no encontrado');
            return;
        }
        this.showProviderModal(provider);
    }
    
    /**
     * Guardar proveedor (crear o actualizar)
     */
    async saveProvider(providerId = null) {
        const form = document.getElementById('providerForm');
        if (!form) return;
        
        // Obtener valores del formulario
        const name = document.getElementById('providerName').value.trim();
        const type = document.getElementById('providerType').value;
        const modelName = document.getElementById('modelName').value.trim();
        const rateLimit = document.getElementById('rateLimit').value;
        const apiKey = document.getElementById('apiKey').value.trim();
        const baseUrl = document.getElementById('baseUrl').value.trim();
        const additionalConfig = document.getElementById('additionalConfig').value.trim();
        const isActive = document.getElementById('providerActive').checked;
        
        // Validaciones
        if (!name) {
            if (window.showError) showError('El nombre del proveedor es requerido');
            return;
        }
        
        if (!type) {
            if (window.showError) showError('Debes seleccionar un tipo de proveedor');
            return;
        }
        
        if (!modelName) {
            if (window.showError) showError('El nombre del modelo es requerido');
            return;
        }
        
        if (!apiKey) {
            if (window.showError) showError('La API Key es requerida');
            return;
        }
        
        // Validar JSON adicional si se proporciona
        if (additionalConfig) {
            try {
                JSON.parse(additionalConfig);
            } catch (e) {
                if (window.showError) showError('La configuración adicional debe ser un JSON válido');
                return;
            }
        }
        
        const isEditing = providerId !== null;
        const action = isEditing ? 'updateAIProvider' : 'createAIProvider';
        
        try {
            if (window.showInfo) showInfo(isEditing ? 'Actualizando proveedor...' : 'Creando nuevo proveedor...');
            
            const requestData = {
                action: action,
                name: name,
                provider_type: type,
                model_name: modelName,
                api_key: apiKey,
                base_url: baseUrl,
                rate_limit: rateLimit ? parseInt(rateLimit) : null,
                additional_config: additionalConfig,
                is_active: isActive ? 1 : 0
            };
            
            if (isEditing) {
                requestData.id = providerId;
            }
            
            const response = await fetch('admin_api.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(requestData)
            });
            
            const result = await response.json();
            
            if (result.success) {
                const successMsg = isEditing ? 'Proveedor actualizado correctamente' : 'Nuevo proveedor creado correctamente';
                if (window.showSuccess) showSuccess(successMsg);
                this.closeModal();
                this.loadProviders();
            } else {
                throw new Error(result.error || (isEditing ? 'Error al actualizar proveedor' : 'Error al crear proveedor'));
            }
        } catch (error) {
            console.error('Error guardando proveedor:', error);
            if (window.showError) showError('Error: ' + error.message);
        }
    }
    
    /**
     * Ver detalles del proveedor
     */
    viewProvider(providerId) {
        const provider = this.providersData.find(p => p.id == providerId);
        if (!provider) {
            alert('Proveedor no encontrado');
            return;
        }
        
        const details = `
DETALLES DEL PROVEEDOR DE IA:
============================
ID: ${provider.id}
Nombre: ${provider.name}
Tipo: ${this.getProviderLabel(provider.provider_type)}
Modelo: ${provider.model_name || 'N/A'}
Estado: ${provider.is_active ? 'Activo' : 'Inactivo'}
API Key: ${provider.api_key ? 'Configurada (****)' : 'No configurada'}
Límite: ${provider.rate_limit ? provider.rate_limit + ' req/min' : 'Sin límite'}
Endpoint: ${provider.base_url || 'Predeterminado'}
Configuración: ${provider.additional_config ? 'Sí' : 'No'}
Creado: ${provider.created_at || 'N/A'}
        `;
        
        alert(details);
    }
    
    /**
     * Probar conexión con el proveedor
     */
    async testProvider(providerId) {
        console.log(`🧪 Probando proveedor ID: ${providerId}`);
        
        try {
            if (window.showInfo) showInfo('Probando conexión con el proveedor de IA...');
            
            const response = await fetch('admin_api.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action: 'testAIProvider',
                    id: providerId
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                const message = `✅ Test exitoso: ${result.test_message}\n\nDetalles:\n- Tiempo de respuesta: ${result.response_time || 'N/A'}\n- Modelo: ${result.model_info || 'N/A'}\n- Estado: ${result.status || 'OK'}`;
                alert(message);
                if (window.showSuccess) showSuccess('Proveedor de IA probado correctamente');
            } else {
                throw new Error(result.error || 'Error en el test del proveedor');
            }
        } catch (error) {
            console.error('Error probando proveedor:', error);
            if (window.showError) showError('Error al probar proveedor: ' + error.message);
        }
    }
    
    /**
     * Alternar estado del proveedor
     */
    async toggleProviderStatus(providerId, newStatus) {
        const action = newStatus ? 'activar' : 'desactivar';
        
        if (!confirm(`¿Estás seguro de que quieres ${action} este proveedor de IA?`)) {
            return;
        }
        
        try {
            const provider = this.providersData.find(p => p.id == providerId);
            if (!provider) {
                if (window.showError) showError('Proveedor no encontrado');
                return;
            }
            
            const response = await fetch('admin_api.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action: 'updateAIProvider',
                    id: providerId,
                    name: provider.name,
                    provider_type: provider.provider_type,
                    model_name: provider.model_name,
                    api_key: provider.api_key || '',
                    base_url: provider.base_url || '',
                    rate_limit: provider.rate_limit,
                    additional_config: provider.additional_config || '',
                    is_active: newStatus ? 1 : 0
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                if (window.showSuccess) showSuccess(`Proveedor ${action}do correctamente`);
                this.loadProviders();
            } else {
                throw new Error(result.error || `Error al ${action} proveedor`);
            }
        } catch (error) {
            console.error(`Error al ${action} proveedor:`, error);
            if (window.showError) showError(`Error al ${action} proveedor: ` + error.message);
        }
    }
    
    /**
     * Confirmar eliminación
     */
    confirmDelete(providerId, providerName) {
        if (confirm(`¿Estás seguro de que quieres eliminar el proveedor "${providerName}"?\n\nEsta acción no se puede deshacer.`)) {
            this.deleteProvider(providerId);
        }
    }
    
    /**
     * Eliminar proveedor
     */
    async deleteProvider(providerId) {
        try {
            const response = await fetch('admin_api.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action: 'deleteAIProvider',
                    id: providerId
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                if (window.showSuccess) showSuccess('Proveedor eliminado correctamente');
                this.loadProviders();
            } else {
                throw new Error(result.error || 'Error al eliminar proveedor');
            }
        } catch (error) {
            console.error('Error eliminando proveedor:', error);
            if (window.showError) showError('Error al eliminar proveedor: ' + error.message);
        }
    }
    
    /**
     * Utilidades
     */
    getProviderBadgeColor(type) {
        const colors = {
            'openai': '#00A67E',
            'anthropic': '#d97706',
            'google': '#4285f4',
            'microsoft': '#00BCF2',
            'cohere': '#39C6B4',
            'huggingface': '#ff6f00',
            'custom': '#6c757d'
        };
        return colors[type] || '#6c757d';
    }
    
    getProviderLabel(type) {
        const labels = {
            'openai': 'OpenAI',
            'anthropic': 'Anthropic',
            'google': 'Google',
            'microsoft': 'Microsoft',
            'cohere': 'Cohere',
            'huggingface': 'Hugging Face',
            'custom': 'Personalizado'
        };
        return labels[type] || type.toUpperCase();
    }
    
    escapeHtml(text) {
        if (!text) return '';
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, m => map[m]);
    }
    
    /**
     * Recargar lista
     */
    async refreshList() {
        if (window.showInfo) showInfo('Actualizando lista de proveedores...');
        await this.loadProviders();
        if (window.showSuccess) showSuccess('Lista de proveedores actualizada');
    }
}

// Crear instancia global del módulo
window.aiProvidersModule = new AIProvidersModule();