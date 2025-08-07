/**
 * ==========================================================================
 * APIS MODULE SIMPLE - Kavia Hoteles Panel de Administración
 * Módulo JavaScript simplificado para APIs Externas
 * ==========================================================================
 */

class ApisModule {
    constructor() {
        this.apisData = [];
        this.init();
    }
    
    init() {
        console.log('🔌 APIs Module Simple inicializado');
    }
    
    /**
     * Cargar APIs desde el backend
     */
    async loadApis() {
        console.log('🔌 Cargando APIs...');
        const container = document.getElementById('apis-content-direct');
        
        if (!container) return;
        
        container.innerHTML = '<div style="text-align: center; padding: 20px;"><i class="fas fa-spinner fa-spin"></i> Cargando APIs...</div>';
        
        try {
            const response = await fetch('admin_api.php?action=getApiProviders');
            const result = await response.json();
            
            if (result.success) {
                this.apisData = result.providers || [];
                this.renderApisTable();
            } else {
                throw new Error(result.error || 'Error al cargar APIs');
            }
        } catch (error) {
            container.innerHTML = `<div style="color: #dc3545; text-align: center; padding: 20px;">❌ Error: ${error.message}</div>`;
        }
    }
    
    /**
     * Renderizar tabla de APIs
     */
    renderApisTable() {
        const container = document.getElementById('apis-content-direct');
        if (!container) return;
        
        if (this.apisData.length === 0) {
            this.showEmptyState();
            return;
        }
        
        let html = `
            <div class="apis-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding: 15px; background: #e8f4fd; border-radius: 8px;">
                <h3 style="margin: 0; color: #495057;">
                    <i class="fas fa-plug"></i> 
                    APIs Externas (${this.apisData.length})
                </h3>
                <button class="btn btn-primary" onclick="apisModule.showAddModal()" style="background: #17a2b8; color: white; padding: 10px 20px; border: none; border-radius: 6px; cursor: pointer;">
                    <i class="fas fa-plus"></i> 
                    Agregar API
                </button>
            </div>
            
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; margin-top: 15px;">
                    <thead>
                        <tr style="background: #495057; color: white;">
                            <th style="padding: 12px; border: 1px solid #ddd;">ID</th>
                            <th style="padding: 12px; border: 1px solid #ddd;">Nombre</th>
                            <th style="padding: 12px; border: 1px solid #ddd;">Tipo</th>
                            <th style="padding: 12px; border: 1px solid #ddd;">Descripción</th>
                            <th style="padding: 12px; border: 1px solid #ddd;">Estado</th>
                            <th style="padding: 12px; border: 1px solid #ddd;">Creado</th>
                            <th style="padding: 12px; border: 1px solid #ddd; width: 250px;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
        `;
        
        this.apisData.forEach(api => {
            const createdAt = api.created_at ? new Date(api.created_at).toLocaleDateString('es-ES') : 'N/A';
            const isActive = api.is_active == 1;
            
            html += `
                <tr style="border-bottom: 1px solid #ddd;">
                    <td style="padding: 12px; border: 1px solid #ddd;"><strong>#${api.id}</strong></td>
                    <td style="padding: 12px; border: 1px solid #ddd;">
                        <strong>${this.escapeHtml(api.name)}</strong>
                    </td>
                    <td style="padding: 12px; border: 1px solid #ddd;">
                        <span style="background: ${this.getTypeBadgeColor(api.provider_type)}; color: white; padding: 4px 8px; border-radius: 4px; font-size: 12px;">
                            ${this.getTypeLabel(api.provider_type)}
                        </span>
                    </td>
                    <td style="padding: 12px; border: 1px solid #ddd;">
                        ${api.description ? this.escapeHtml(api.description).substring(0, 50) + '...' : 'Sin descripción'}
                    </td>
                    <td style="padding: 12px; border: 1px solid #ddd;">
                        <span style="background: ${isActive ? '#28a745' : '#dc3545'}; color: white; padding: 4px 8px; border-radius: 4px; font-size: 12px;">
                            ${isActive ? 'Activo' : 'Inactivo'}
                        </span>
                    </td>
                    <td style="padding: 12px; border: 1px solid #ddd;">${createdAt}</td>
                    <td style="padding: 12px; border: 1px solid #ddd;">
                        <div style="display: flex; gap: 5px; flex-wrap: wrap;">
                            <button onclick="apisModule.viewApi(${api.id})" style="background: #007bff; color: white; border: none; padding: 6px 10px; border-radius: 4px; cursor: pointer; font-size: 12px;" title="Ver detalles">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button onclick="apisModule.editApi(${api.id})" style="background: #28a745; color: white; border: none; padding: 6px 10px; border-radius: 4px; cursor: pointer; font-size: 12px;" title="Editar">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button onclick="apisModule.testApi(${api.id})" style="background: #ffc107; color: #000; border: none; padding: 6px 10px; border-radius: 4px; cursor: pointer; font-size: 12px;" title="Probar conexión">
                                <i class="fas fa-flask"></i>
                            </button>
                            <button onclick="apisModule.toggleApiStatus(${api.id}, ${isActive ? 0 : 1})" style="background: ${isActive ? '#6c757d' : '#28a745'}; color: white; border: none; padding: 6px 10px; border-radius: 4px; cursor: pointer; font-size: 12px;" title="${isActive ? 'Desactivar' : 'Activar'}">
                                <i class="fas fa-${isActive ? 'pause' : 'play'}"></i>
                            </button>
                            <button onclick="apisModule.confirmDelete(${api.id}, '${this.escapeHtml(api.name).replace(/'/g, '\\\\\\'')}'))" style="background: #dc3545; color: white; border: none; padding: 6px 10px; border-radius: 4px; cursor: pointer; font-size: 12px;" title="Eliminar">
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
            <div style="margin-top: 15px; text-align: center; padding: 15px; background: #d4edda; border: 1px solid #c3e6cb; border-radius: 6px;">
                <strong>✅ ${this.apisData.length} APIs externas configuradas</strong><br>
                <small>Sistema de APIs funcionando correctamente</small>
            </div>
        `;
        
        container.innerHTML = html;
    }
    
    /**
     * Mostrar estado vacío
     */
    showEmptyState() {
        const container = document.getElementById('apis-content-direct');
        if (!container) return;
        
        container.innerHTML = `
            <div style="text-align: center; padding: 40px;">
                <i class="fas fa-plug" style="font-size: 3rem; color: #17a2b8; margin-bottom: 20px;"></i>
                <h3>No hay APIs configuradas</h3>
                <p style="margin: 20px 0;">Comienza agregando tu primera API externa para integrar servicios como Apify, Booking.com, etc.</p>
                <button onclick="apisModule.showAddModal()" style="background: #17a2b8; color: white; border: none; padding: 12px 24px; border-radius: 6px; cursor: pointer;">
                    <i class="fas fa-plus"></i> Agregar Primera API
                </button>
            </div>
        `;
    }
    
    /**
     * Mostrar modal para agregar API
     */
    showAddModal() {
        this.showApiModal();
    }
    
    /**
     * Mostrar modal de API (agregar/editar)
     */
    showApiModal(apiData = null) {
        const isEditing = apiData !== null;
        const modalTitle = isEditing ? 'Editar API Externa' : 'Agregar Nueva API Externa';
        
        const modalHtml = `
            <div id="apiModal" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 1000; display: flex; align-items: center; justify-content: center;">
                <div style="background: white; border-radius: 10px; padding: 30px; width: 90%; max-width: 600px; max-height: 80vh; overflow-y: auto;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; border-bottom: 2px solid #17a2b8; padding-bottom: 15px;">
                        <h3 style="margin: 0; color: #495057;">
                            <i class="fas fa-plug"></i> ${modalTitle}
                        </h3>
                        <button onclick="apisModule.closeModal()" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #6c757d;">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    
                    <form id="apiForm" style="display: flex; flex-direction: column; gap: 20px;">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                            <div>
                                <label style="display: block; margin-bottom: 5px; font-weight: bold; color: #495057;">Nombre de la API *</label>
                                <input type="text" id="apiName" value="${isEditing ? this.escapeHtml(apiData.name) : ''}" 
                                       style="width: 100%; padding: 10px; border: 2px solid #ddd; border-radius: 6px; box-sizing: border-box;" 
                                       placeholder="Ej: Apify Hotels" required>
                            </div>
                            <div>
                                <label style="display: block; margin-bottom: 5px; font-weight: bold; color: #495057;">Tipo de API *</label>
                                <select id="apiType" style="width: 100%; padding: 10px; border: 2px solid #ddd; border-radius: 6px; box-sizing: border-box;" required>
                                    <option value="">Seleccionar tipo...</option>
                                    <option value="apify" ${isEditing && apiData.provider_type === 'apify' ? 'selected' : ''}>Apify</option>
                                    <option value="booking" ${isEditing && apiData.provider_type === 'booking' ? 'selected' : ''}>Booking.com</option>
                                    <option value="expedia" ${isEditing && apiData.provider_type === 'expedia' ? 'selected' : ''}>Expedia</option>
                                    <option value="airbnb" ${isEditing && apiData.provider_type === 'airbnb' ? 'selected' : ''}>Airbnb</option>
                                    <option value="tripadvisor" ${isEditing && apiData.provider_type === 'tripadvisor' ? 'selected' : ''}>TripAdvisor</option>
                                    <option value="custom" ${isEditing && apiData.provider_type === 'custom' ? 'selected' : ''}>Personalizada</option>
                                </select>
                            </div>
                        </div>
                        
                        <div>
                            <label style="display: block; margin-bottom: 5px; font-weight: bold; color: #495057;">API Key *</label>
                            <input type="password" id="apiKey" value="${isEditing ? (apiData.api_key || '') : ''}" 
                                   style="width: 100%; padding: 10px; border: 2px solid #ddd; border-radius: 6px; box-sizing: border-box;" 
                                   placeholder="Ingresa tu API Key..." required>
                            <small style="color: #6c757d;">Esta información se almacena de forma segura</small>
                        </div>
                        
                        <div>
                            <label style="display: block; margin-bottom: 5px; font-weight: bold; color: #495057;">Descripción</label>
                            <textarea id="apiDescription" rows="4" 
                                      style="width: 100%; padding: 10px; border: 2px solid #ddd; border-radius: 6px; resize: vertical; box-sizing: border-box;" 
                                      placeholder="Descripción opcional de la API y su uso...">${isEditing ? this.escapeHtml(apiData.description || '') : ''}</textarea>
                        </div>
                        
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <input type="checkbox" id="apiActive" ${isEditing && apiData.is_active ? 'checked' : (!isEditing ? 'checked' : '')} 
                                   style="width: 20px; height: 20px;">
                            <label for="apiActive" style="font-weight: bold; color: #495057;">API Activa</label>
                        </div>
                        
                        <div style="display: flex; gap: 10px; margin-top: 20px; padding-top: 20px; border-top: 1px solid #ddd;">
                            <button type="button" onclick="apisModule.saveApi(${isEditing ? apiData.id : 'null'})" 
                                    style="flex: 1; background: #17a2b8; color: white; padding: 12px 20px; border: none; border-radius: 6px; cursor: pointer; font-weight: bold;">
                                <i class="fas fa-save"></i> ${isEditing ? 'Actualizar' : 'Guardar'} API
                            </button>
                            <button type="button" onclick="apisModule.closeModal()" 
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
        const modal = document.getElementById('apiModal');
        if (modal) {
            modal.remove();
        }
    }
    
    /**
     * Editar API
     */
    editApi(apiId) {
        const api = this.apisData.find(a => a.id == apiId);
        if (!api) {
            if (window.showError) showError('API no encontrada');
            return;
        }
        this.showApiModal(api);
    }
    
    /**
     * Guardar API (crear o actualizar)
     */
    async saveApi(apiId = null) {
        const form = document.getElementById('apiForm');
        if (!form) return;
        
        // Obtener valores del formulario
        const name = document.getElementById('apiName').value.trim();
        const type = document.getElementById('apiType').value;
        const apiKey = document.getElementById('apiKey').value.trim();
        const description = document.getElementById('apiDescription').value.trim();
        const isActive = document.getElementById('apiActive').checked;
        
        // Validaciones
        if (!name) {
            if (window.showError) showError('El nombre de la API es requerido');
            return;
        }
        
        if (!type) {
            if (window.showError) showError('Debes seleccionar un tipo de API');
            return;
        }
        
        if (!apiKey) {
            if (window.showError) showError('La API Key es requerida');
            return;
        }
        
        const isEditing = apiId !== null;
        const action = isEditing ? 'updateApiProvider' : 'createApiProvider';
        
        try {
            if (window.showInfo) showInfo(isEditing ? 'Actualizando API...' : 'Creando nueva API...');
            
            const requestData = {
                action: action,
                name: name,
                provider_type: type,
                api_key: apiKey,
                description: description,
                is_active: isActive ? 1 : 0
            };
            
            if (isEditing) {
                requestData.id = apiId;
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
                const successMsg = isEditing ? 'API actualizada correctamente' : 'Nueva API creada correctamente';
                if (window.showSuccess) showSuccess(successMsg);
                this.closeModal();
                this.loadApis();
            } else {
                throw new Error(result.error || (isEditing ? 'Error al actualizar API' : 'Error al crear API'));
            }
        } catch (error) {
            console.error('Error guardando API:', error);
            if (window.showError) showError('Error: ' + error.message);
        }
    }
    
    /**
     * Ver detalles de API
     */
    viewApi(apiId) {
        const api = this.apisData.find(a => a.id == apiId);
        if (!api) {
            alert('API no encontrada');
            return;
        }
        
        const details = `
DETALLES DE LA API:
==================
ID: ${api.id}
Nombre: ${api.name}
Tipo: ${this.getTypeLabel(api.provider_type)}
Descripción: ${api.description || 'Sin descripción'}
Estado: ${api.is_active ? 'Activa' : 'Inactiva'}
API Key: ${api.api_key ? 'Configurada (****)' : 'No configurada'}
Creado: ${api.created_at || 'N/A'}
        `;
        
        alert(details);
    }
    
    /**
     * Probar conexión con API
     */
    async testApi(apiId) {
        console.log(`🧪 Probando API ID: ${apiId}`);
        
        try {
            if (window.showInfo) showInfo('Probando conexión con la API...');
            
            const response = await fetch('admin_api.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action: 'testApiProvider',
                    id: apiId
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                const message = `✅ Test exitoso: ${result.test_message}\n\nDetalles:\n- Tiempo de respuesta: ${result.response_time || 'N/A'}\n- Estado: ${result.status || 'OK'}`;
                alert(message);
                if (window.showSuccess) showSuccess('API probada correctamente');
            } else {
                throw new Error(result.error || 'Error en el test de API');
            }
        } catch (error) {
            console.error('Error probando API:', error);
            if (window.showError) showError('Error al probar API: ' + error.message);
        }
    }
    
    /**
     * Alternar estado de la API
     */
    async toggleApiStatus(apiId, newStatus) {
        const action = newStatus ? 'activar' : 'desactivar';
        
        if (!confirm(`¿Estás seguro de que quieres ${action} esta API?`)) {
            return;
        }
        
        try {
            const api = this.apisData.find(a => a.id == apiId);
            if (!api) {
                if (window.showError) showError('API no encontrada');
                return;
            }
            
            const response = await fetch('admin_api.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action: 'updateApiProvider',
                    id: apiId,
                    name: api.name,
                    provider_type: api.provider_type,
                    api_key: api.api_key || '',
                    description: api.description || '',
                    is_active: newStatus ? 1 : 0
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                if (window.showSuccess) showSuccess(`API ${action}da correctamente`);
                this.loadApis();
            } else {
                throw new Error(result.error || `Error al ${action} API`);
            }
        } catch (error) {
            console.error(`Error al ${action} API:`, error);
            if (window.showError) showError(`Error al ${action} API: ` + error.message);
        }
    }
    
    /**
     * Confirmar eliminación
     */
    confirmDelete(apiId, apiName) {
        if (confirm(`¿Estás seguro de que quieres eliminar la API "${apiName}"?\n\nEsta acción no se puede deshacer.`)) {
            this.deleteApi(apiId);
        }
    }
    
    /**
     * Eliminar API
     */
    async deleteApi(apiId) {
        try {
            const response = await fetch('admin_api.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action: 'deleteApiProvider',
                    id: apiId
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                if (window.showSuccess) showSuccess('API eliminada correctamente');
                this.loadApis();
            } else {
                throw new Error(result.error || 'Error al eliminar API');
            }
        } catch (error) {
            console.error('Error eliminando API:', error);
            if (window.showError) showError('Error al eliminar API: ' + error.message);
        }
    }
    
    /**
     * Utilidades
     */
    getTypeBadgeColor(type) {
        const colors = {
            'apify': '#007bff',
            'booking': '#003580',
            'expedia': '#ffb700',
            'airbnb': '#ff5a5f',
            'tripadvisor': '#00af87',
            'custom': '#6c757d'
        };
        return colors[type] || '#6c757d';
    }
    
    getTypeLabel(type) {
        const labels = {
            'apify': 'Apify',
            'booking': 'Booking.com',
            'expedia': 'Expedia',
            'airbnb': 'Airbnb',
            'tripadvisor': 'TripAdvisor',
            'custom': 'Personalizada'
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
        if (window.showInfo) showInfo('Actualizando lista de APIs...');
        await this.loadApis();
        if (window.showSuccess) showSuccess('Lista de APIs actualizada');
    }
}

// Crear instancia global del módulo
window.apisModule = new ApisModule();