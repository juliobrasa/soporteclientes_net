/**
 * ==========================================================================
 * PROMPTS MANAGER MODULE - Kavia Hoteles Panel de Administración
 * Módulo JavaScript para Gestión de Prompts de IA
 * ==========================================================================
 */

class PromptsManagerModule {
    constructor() {
        this.promptsData = [];
        this.init();
    }
    
    init() {
        console.log('📝 Prompts Manager Module inicializado');
    }
    
    /**
     * Cargar prompts desde el backend
     */
    async loadPrompts() {
        console.log('📝 Cargando prompts...');
        const container = document.getElementById('prompts-content-direct');
        
        if (!container) return;
        
        container.innerHTML = '<div style="text-align: center; padding: 20px;"><i class="fas fa-spinner fa-spin"></i> Cargando prompts...</div>';
        
        try {
            const response = await fetch('admin_api.php?action=getPrompts');
            const result = await response.json();
            
            if (result.success) {
                this.promptsData = result.prompts || [];
                this.renderPromptsTable();
            } else {
                throw new Error(result.error || 'Error al cargar prompts');
            }
        } catch (error) {
            container.innerHTML = `<div style="color: #dc3545; text-align: center; padding: 20px;">❌ Error: ${error.message}</div>`;
        }
    }
    
    /**
     * Renderizar tabla de prompts
     */
    renderPromptsTable() {
        const container = document.getElementById('prompts-content-direct');
        if (!container) return;
        
        if (this.promptsData.length === 0) {
            this.showEmptyState();
            return;
        }
        
        let html = `
            <div class="prompts-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding: 15px; background: #fff3cd; border-radius: 8px;">
                <h3 style="margin: 0; color: #495057;">
                    <i class="fas fa-magic"></i> 
                    Prompts de IA (${this.promptsData.length})
                </h3>
                <button class="btn btn-primary" onclick="promptsManagerModule.showAddModal()" style="background: #fd7e14; color: white; padding: 10px 20px; border: none; border-radius: 6px; cursor: pointer;">
                    <i class="fas fa-plus"></i> 
                    Crear Prompt
                </button>
            </div>
            
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; margin-top: 15px;">
                    <thead>
                        <tr style="background: #495057; color: white;">
                            <th style="padding: 12px; border: 1px solid #ddd;">ID</th>
                            <th style="padding: 12px; border: 1px solid #ddd;">Nombre</th>
                            <th style="padding: 12px; border: 1px solid #ddd;">Categoría</th>
                            <th style="padding: 12px; border: 1px solid #ddd;">Descripción</th>
                            <th style="padding: 12px; border: 1px solid #ddd;">Variables</th>
                            <th style="padding: 12px; border: 1px solid #ddd;">Estado</th>
                            <th style="padding: 12px; border: 1px solid #ddd;">Uso</th>
                            <th style="padding: 12px; border: 1px solid #ddd;">Creado</th>
                            <th style="padding: 12px; border: 1px solid #ddd; width: 250px;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
        `;
        
        this.promptsData.forEach(prompt => {
            const createdAt = prompt.created_at ? new Date(prompt.created_at).toLocaleDateString('es-ES') : 'N/A';
            const isActive = prompt.is_active == 1;
            const variables = this.extractVariables(prompt.content);
            
            html += `
                <tr style="border-bottom: 1px solid #ddd;">
                    <td style="padding: 12px; border: 1px solid #ddd;"><strong>#${prompt.id}</strong></td>
                    <td style="padding: 12px; border: 1px solid #ddd;">
                        <strong>${this.escapeHtml(prompt.name)}</strong>
                    </td>
                    <td style="padding: 12px; border: 1px solid #ddd;">
                        <span style="background: ${this.getCategoryBadgeColor(prompt.category)}; color: white; padding: 4px 8px; border-radius: 4px; font-size: 12px;">
                            ${this.getCategoryLabel(prompt.category)}
                        </span>
                    </td>
                    <td style="padding: 12px; border: 1px solid #ddd;">
                        ${prompt.description ? this.escapeHtml(prompt.description).substring(0, 60) + '...' : 'Sin descripción'}
                    </td>
                    <td style="padding: 12px; border: 1px solid #ddd;">
                        ${variables.length > 0 ? 
                            `<small style="background: #e9ecef; padding: 2px 4px; border-radius: 3px;">${variables.length} vars</small>` : 
                            '<small style="color: #6c757d;">Sin vars</small>'
                        }
                    </td>
                    <td style="padding: 12px; border: 1px solid #ddd;">
                        <span style="background: ${isActive ? '#28a745' : '#dc3545'}; color: white; padding: 4px 8px; border-radius: 4px; font-size: 12px;">
                            ${isActive ? 'Activo' : 'Inactivo'}
                        </span>
                    </td>
                    <td style="padding: 12px; border: 1px solid #ddd;">
                        <small>${prompt.usage_count || 0} veces</small>
                    </td>
                    <td style="padding: 12px; border: 1px solid #ddd;">${createdAt}</td>
                    <td style="padding: 12px; border: 1px solid #ddd;">
                        <div style="display: flex; gap: 5px; flex-wrap: wrap;">
                            <button onclick="promptsManagerModule.viewPrompt(${prompt.id})" style="background: #007bff; color: white; border: none; padding: 6px 10px; border-radius: 4px; cursor: pointer; font-size: 12px;" title="Ver contenido">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button onclick="promptsManagerModule.editPrompt(${prompt.id})" style="background: #28a745; color: white; border: none; padding: 6px 10px; border-radius: 4px; cursor: pointer; font-size: 12px;" title="Editar">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button onclick="promptsManagerModule.duplicatePrompt(${prompt.id})" style="background: #17a2b8; color: white; border: none; padding: 6px 10px; border-radius: 4px; cursor: pointer; font-size: 12px;" title="Duplicar">
                                <i class="fas fa-copy"></i>
                            </button>
                            <button onclick="promptsManagerModule.testPrompt(${prompt.id})" style="background: #ffc107; color: #000; border: none; padding: 6px 10px; border-radius: 4px; cursor: pointer; font-size: 12px;" title="Probar prompt">
                                <i class="fas fa-play"></i>
                            </button>
                            <button onclick="promptsManagerModule.togglePromptStatus(${prompt.id}, ${isActive ? 0 : 1})" style="background: ${isActive ? '#6c757d' : '#28a745'}; color: white; border: none; padding: 6px 10px; border-radius: 4px; cursor: pointer; font-size: 12px;" title="${isActive ? 'Desactivar' : 'Activar'}">
                                <i class="fas fa-${isActive ? 'pause' : 'play'}"></i>
                            </button>
                            <button onclick="promptsManagerModule.confirmDelete(${prompt.id}, '${this.escapeHtml(prompt.name).replace(/'/g, '\\\\\\\\\\\\'')})')" style="background: #dc3545; color: white; border: none; padding: 6px 10px; border-radius: 4px; cursor: pointer; font-size: 12px;" title="Eliminar">
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
            <div style="margin-top: 15px; text-align: center; padding: 15px; background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 6px;">
                <strong>📝 ${this.promptsData.length} prompts configurados</strong><br>
                <small>Sistema de prompts funcionando correctamente</small>
            </div>
        `;
        
        container.innerHTML = html;
    }
    
    /**
     * Mostrar estado vacío
     */
    showEmptyState() {
        const container = document.getElementById('prompts-content-direct');
        if (!container) return;
        
        container.innerHTML = `
            <div style="text-align: center; padding: 40px;">
                <i class="fas fa-magic" style="font-size: 3rem; color: #fd7e14; margin-bottom: 20px;"></i>
                <h3>No hay prompts configurados</h3>
                <p style="margin: 20px 0;">Comienza creando tu primer prompt para automatizar interacciones con IA.</p>
                <button onclick="promptsManagerModule.showAddModal()" style="background: #fd7e14; color: white; border: none; padding: 12px 24px; border-radius: 6px; cursor: pointer;">
                    <i class="fas fa-plus"></i> Crear Primer Prompt
                </button>
            </div>
        `;
    }
    
    /**
     * Mostrar modal para agregar prompt
     */
    showAddModal() {
        this.showPromptModal();
    }
    
    /**
     * Mostrar modal de prompt (agregar/editar)
     */
    showPromptModal(promptData = null) {
        const isEditing = promptData !== null;
        const modalTitle = isEditing ? 'Editar Prompt' : 'Crear Nuevo Prompt';
        
        const modalHtml = `
            <div id="promptModal" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 1000; display: flex; align-items: center; justify-content: center;">
                <div style="background: white; border-radius: 10px; padding: 30px; width: 95%; max-width: 900px; max-height: 85vh; overflow-y: auto;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; border-bottom: 2px solid #fd7e14; padding-bottom: 15px;">
                        <h3 style="margin: 0; color: #495057;">
                            <i class="fas fa-magic"></i> ${modalTitle}
                        </h3>
                        <button onclick="promptsManagerModule.closeModal()" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #6c757d;">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    
                    <form id="promptForm" style="display: flex; flex-direction: column; gap: 20px;">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                            <div>
                                <label style="display: block; margin-bottom: 5px; font-weight: bold; color: #495057;">Nombre del Prompt *</label>
                                <input type="text" id="promptName" value="${isEditing ? this.escapeHtml(promptData.name) : ''}" 
                                       style="width: 100%; padding: 10px; border: 2px solid #ddd; border-radius: 6px; box-sizing: border-box;" 
                                       placeholder="Ej: Respuesta automática hoteles" required>
                            </div>
                            <div>
                                <label style="display: block; margin-bottom: 5px; font-weight: bold; color: #495057;">Categoría *</label>
                                <select id="promptCategory" style="width: 100%; padding: 10px; border: 2px solid #ddd; border-radius: 6px; box-sizing: border-box;" required>
                                    <option value="">Seleccionar categoría...</option>
                                    <option value="customer_service" ${isEditing && promptData.category === 'customer_service' ? 'selected' : ''}>Atención al Cliente</option>
                                    <option value="hotel_reviews" ${isEditing && promptData.category === 'hotel_reviews' ? 'selected' : ''}>Reviews de Hoteles</option>
                                    <option value="booking_assistance" ${isEditing && promptData.category === 'booking_assistance' ? 'selected' : ''}>Asistencia de Reservas</option>
                                    <option value="content_generation" ${isEditing && promptData.category === 'content_generation' ? 'selected' : ''}>Generación de Contenido</option>
                                    <option value="data_analysis" ${isEditing && promptData.category === 'data_analysis' ? 'selected' : ''}>Análisis de Datos</option>
                                    <option value="translation" ${isEditing && promptData.category === 'translation' ? 'selected' : ''}>Traducción</option>
                                    <option value="general" ${isEditing && promptData.category === 'general' ? 'selected' : ''}>General</option>
                                </select>
                            </div>
                        </div>
                        
                        <div>
                            <label style="display: block; margin-bottom: 5px; font-weight: bold; color: #495057;">Descripción</label>
                            <input type="text" id="promptDescription" value="${isEditing ? this.escapeHtml(promptData.description || '') : ''}" 
                                   style="width: 100%; padding: 10px; border: 2px solid #ddd; border-radius: 6px; box-sizing: border-box;" 
                                   placeholder="Breve descripción del propósito del prompt...">
                        </div>
                        
                        <div>
                            <label style="display: block; margin-bottom: 5px; font-weight: bold; color: #495057;">Contenido del Prompt *</label>
                            <textarea id="promptContent" rows="12" 
                                      style="width: 100%; padding: 15px; border: 2px solid #ddd; border-radius: 6px; resize: vertical; box-sizing: border-box; font-family: 'Courier New', monospace; line-height: 1.5;" 
                                      placeholder="Escribe aquí el contenido del prompt. Puedes usar variables como {{nombre_hotel}}, {{fecha_reserva}}, etc." required>${isEditing ? this.escapeHtml(promptData.content || '') : ''}</textarea>
                            <small style="color: #6c757d;">
                                💡 Tip: Usa variables con dobles llaves: {{variable_name}}. Ej: "Hola {{nombre_cliente}}, tu reserva en {{nombre_hotel}} está confirmada."
                            </small>
                        </div>
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                            <div>
                                <label style="display: block; margin-bottom: 5px; font-weight: bold; color: #495057;">Variables Detectadas</label>
                                <div id="variablesPreview" style="padding: 10px; border: 1px solid #ddd; border-radius: 6px; background: #f8f9fa; min-height: 50px;">
                                    <small style="color: #6c757d;">Las variables se detectarán automáticamente...</small>
                                </div>
                            </div>
                            <div>
                                <label style="display: block; margin-bottom: 5px; font-weight: bold; color: #495057;">Configuración</label>
                                <div style="display: flex; flex-direction: column; gap: 10px;">
                                    <label style="display: flex; align-items: center; gap: 10px;">
                                        <input type="checkbox" id="promptActive" ${isEditing && promptData.is_active ? 'checked' : (!isEditing ? 'checked' : '')} style="width: 18px; height: 18px;">
                                        <span>Prompt Activo</span>
                                    </label>
                                    <label style="display: flex; align-items: center; gap: 10px;">
                                        <input type="checkbox" id="allowPublic" ${isEditing && promptData.is_public ? 'checked' : ''} style="width: 18px; height: 18px;">
                                        <span>Permitir uso público</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <div style="display: flex; gap: 10px; margin-top: 20px; padding-top: 20px; border-top: 1px solid #ddd;">
                            <button type="button" onclick="promptsManagerModule.previewPrompt()" 
                                    style="background: #17a2b8; color: white; padding: 12px 20px; border: none; border-radius: 6px; cursor: pointer;">
                                <i class="fas fa-eye"></i> Vista Previa
                            </button>
                            <button type="button" onclick="promptsManagerModule.savePrompt(${isEditing ? promptData.id : 'null'})" 
                                    style="flex: 1; background: #fd7e14; color: white; padding: 12px 20px; border: none; border-radius: 6px; cursor: pointer; font-weight: bold;">
                                <i class="fas fa-save"></i> ${isEditing ? 'Actualizar' : 'Guardar'} Prompt
                            </button>
                            <button type="button" onclick="promptsManagerModule.closeModal()" 
                                    style="background: #6c757d; color: white; padding: 12px 20px; border: none; border-radius: 6px; cursor: pointer;">
                                <i class="fas fa-times"></i> Cancelar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        `;
        
        document.body.insertAdjacentHTML('beforeend', modalHtml);
        
        // Configurar detección de variables en tiempo real
        const contentTextarea = document.getElementById('promptContent');
        if (contentTextarea) {
            contentTextarea.addEventListener('input', () => this.updateVariablesPreview());
            this.updateVariablesPreview();
        }
    }
    
    /**
     * Actualizar vista previa de variables
     */
    updateVariablesPreview() {
        const content = document.getElementById('promptContent').value;
        const preview = document.getElementById('variablesPreview');
        
        if (!preview) return;
        
        const variables = this.extractVariables(content);
        
        if (variables.length === 0) {
            preview.innerHTML = '<small style="color: #6c757d;">No se detectaron variables</small>';
        } else {
            const variablesList = variables.map(v => 
                `<span style="background: #fd7e14; color: white; padding: 2px 6px; border-radius: 3px; font-size: 11px; margin: 2px;">${v}</span>`
            ).join(' ');
            preview.innerHTML = `<strong style="font-size: 12px;">Variables encontradas:</strong><br>${variablesList}`;
        }
    }
    
    /**
     * Extraer variables del contenido del prompt
     */
    extractVariables(content) {
        if (!content) return [];
        const matches = content.match(/\{\{([^}]+)\}\}/g);
        if (!matches) return [];
        return [...new Set(matches.map(match => match.replace(/[{}]/g, '')))];
    }
    
    /**
     * Vista previa del prompt
     */
    previewPrompt() {
        const content = document.getElementById('promptContent').value;
        const name = document.getElementById('promptName').value;
        
        if (!content.trim()) {
            if (window.showError) showError('Ingresa contenido para el prompt');
            return;
        }
        
        const variables = this.extractVariables(content);
        let previewContent = content;
        
        // Reemplazar variables con valores de ejemplo
        variables.forEach(variable => {
            const placeholder = this.getExampleValue(variable);
            previewContent = previewContent.replace(new RegExp(`\\{\\{${variable}\\}\\}`, 'g'), placeholder);
        });
        
        const previewWindow = window.open('', '_blank', 'width=700,height=500,scrollbars=yes');
        previewWindow.document.write(`
            <html>
                <head>
                    <title>Vista Previa: ${this.escapeHtml(name || 'Prompt sin nombre')}</title>
                    <style>
                        body { font-family: Arial, sans-serif; padding: 20px; line-height: 1.6; }
                        .header { background: #fd7e14; color: white; padding: 15px; border-radius: 8px; margin-bottom: 20px; }
                        .content { background: #f8f9fa; padding: 20px; border-radius: 8px; white-space: pre-wrap; }
                        .variables { margin-top: 20px; padding: 15px; background: #e9ecef; border-radius: 8px; }
                        .variable { background: #fd7e14; color: white; padding: 2px 6px; border-radius: 3px; margin: 2px; display: inline-block; }
                    </style>
                </head>
                <body>
                    <div class="header">
                        <h2>Vista Previa del Prompt</h2>
                        <p><strong>Nombre:</strong> ${this.escapeHtml(name || 'Sin nombre')}</p>
                    </div>
                    <div class="content">${this.escapeHtml(previewContent)}</div>
                    ${variables.length > 0 ? `
                        <div class="variables">
                            <strong>Variables utilizadas:</strong><br>
                            ${variables.map(v => `<span class="variable">${v}</span>`).join(' ')}
                        </div>
                    ` : ''}
                </body>
            </html>
        `);
    }
    
    /**
     * Obtener valor de ejemplo para una variable
     */
    getExampleValue(variable) {
        const examples = {
            'nombre_cliente': 'Juan Pérez',
            'nombre_hotel': 'Hotel Kavia Luma',
            'fecha_reserva': '15 de marzo de 2024',
            'fecha_checkin': '20 de marzo de 2024',
            'fecha_checkout': '25 de marzo de 2024',
            'numero_habitacion': '205',
            'tipo_habitacion': 'Suite Premium',
            'precio': '$299 USD',
            'numero_personas': '2 adultos',
            'email_cliente': 'juan.perez@email.com',
            'telefono_cliente': '+52 998 123 4567'
        };
        return examples[variable.toLowerCase()] || `[${variable}]`;
    }
    
    /**
     * Cerrar modal
     */
    closeModal() {
        const modal = document.getElementById('promptModal');
        if (modal) {
            modal.remove();
        }
    }
    
    /**
     * Editar prompt
     */
    editPrompt(promptId) {
        const prompt = this.promptsData.find(p => p.id == promptId);
        if (!prompt) {
            if (window.showError) showError('Prompt no encontrado');
            return;
        }
        this.showPromptModal(prompt);
    }
    
    /**
     * Guardar prompt (crear o actualizar)
     */
    async savePrompt(promptId = null) {
        const form = document.getElementById('promptForm');
        if (!form) return;
        
        // Obtener valores del formulario
        const name = document.getElementById('promptName').value.trim();
        const category = document.getElementById('promptCategory').value;
        const description = document.getElementById('promptDescription').value.trim();
        const content = document.getElementById('promptContent').value.trim();
        const isActive = document.getElementById('promptActive').checked;
        const isPublic = document.getElementById('allowPublic').checked;
        
        // Validaciones
        if (!name) {
            if (window.showError) showError('El nombre del prompt es requerido');
            return;
        }
        
        if (!category) {
            if (window.showError) showError('Debes seleccionar una categoría');
            return;
        }
        
        if (!content) {
            if (window.showError) showError('El contenido del prompt es requerido');
            return;
        }
        
        const isEditing = promptId !== null;
        const action = isEditing ? 'updatePrompt' : 'createPrompt';
        
        try {
            if (window.showInfo) showInfo(isEditing ? 'Actualizando prompt...' : 'Creando nuevo prompt...');
            
            const requestData = {
                action: action,
                name: name,
                category: category,
                description: description,
                content: content,
                is_active: isActive ? 1 : 0,
                is_public: isPublic ? 1 : 0
            };
            
            if (isEditing) {
                requestData.id = promptId;
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
                const successMsg = isEditing ? 'Prompt actualizado correctamente' : 'Nuevo prompt creado correctamente';
                if (window.showSuccess) showSuccess(successMsg);
                this.closeModal();
                this.loadPrompts();
            } else {
                throw new Error(result.error || (isEditing ? 'Error al actualizar prompt' : 'Error al crear prompt'));
            }
        } catch (error) {
            console.error('Error guardando prompt:', error);
            if (window.showError) showError('Error: ' + error.message);
        }
    }
    
    /**
     * Ver contenido del prompt
     */
    viewPrompt(promptId) {
        const prompt = this.promptsData.find(p => p.id == promptId);
        if (!prompt) {
            alert('Prompt no encontrado');
            return;
        }
        
        const variables = this.extractVariables(prompt.content);
        
        const details = `
DETALLES DEL PROMPT:
===================
ID: ${prompt.id}
Nombre: ${prompt.name}
Categoría: ${this.getCategoryLabel(prompt.category)}
Descripción: ${prompt.description || 'Sin descripción'}
Estado: ${prompt.is_active ? 'Activo' : 'Inactivo'}
Público: ${prompt.is_public ? 'Sí' : 'No'}
Variables: ${variables.length > 0 ? variables.join(', ') : 'Ninguna'}
Veces usado: ${prompt.usage_count || 0}
Creado: ${prompt.created_at || 'N/A'}

CONTENIDO:
----------
${prompt.content}
        `;
        
        alert(details);
    }
    
    /**
     * Duplicar prompt
     */
    async duplicatePrompt(promptId) {
        const prompt = this.promptsData.find(p => p.id == promptId);
        if (!prompt) {
            if (window.showError) showError('Prompt no encontrado');
            return;
        }
        
        const duplicatedPrompt = {
            ...prompt,
            name: prompt.name + ' (Copia)',
            id: null,
            is_active: 0
        };
        
        this.showPromptModal(duplicatedPrompt);
    }
    
    /**
     * Probar prompt
     */
    async testPrompt(promptId) {
        const prompt = this.promptsData.find(p => p.id == promptId);
        if (!prompt) {
            if (window.showError) showError('Prompt no encontrado');
            return;
        }
        
        const variables = this.extractVariables(prompt.content);
        let testContent = prompt.content;
        
        // Simular valores de variables
        variables.forEach(variable => {
            const example = this.getExampleValue(variable);
            testContent = testContent.replace(new RegExp(`\\{\\{${variable}\\}\\}`, 'g'), example);
        });
        
        try {
            if (window.showInfo) showInfo('Probando prompt con IA...');
            
            const response = await fetch('admin_api.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action: 'testPrompt',
                    prompt_id: promptId,
                    test_content: testContent
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                const message = `✅ Test exitoso!\n\nRespuesta de IA:\n"${result.ai_response}"\n\nTiempo de respuesta: ${result.response_time || 'N/A'}`;
                alert(message);
                if (window.showSuccess) showSuccess('Prompt probado correctamente');
            } else {
                throw new Error(result.error || 'Error al probar prompt');
            }
        } catch (error) {
            console.error('Error probando prompt:', error);
            if (window.showError) showError('Error al probar prompt: ' + error.message);
        }
    }
    
    /**
     * Alternar estado del prompt
     */
    async togglePromptStatus(promptId, newStatus) {
        const action = newStatus ? 'activar' : 'desactivar';
        
        if (!confirm(`¿Estás seguro de que quieres ${action} este prompt?`)) {
            return;
        }
        
        try {
            const prompt = this.promptsData.find(p => p.id == promptId);
            if (!prompt) {
                if (window.showError) showError('Prompt no encontrado');
                return;
            }
            
            const response = await fetch('admin_api.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action: 'updatePrompt',
                    id: promptId,
                    name: prompt.name,
                    category: prompt.category,
                    description: prompt.description || '',
                    content: prompt.content,
                    is_active: newStatus ? 1 : 0,
                    is_public: prompt.is_public || 0
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                if (window.showSuccess) showSuccess(`Prompt ${action}do correctamente`);
                this.loadPrompts();
            } else {
                throw new Error(result.error || `Error al ${action} prompt`);
            }
        } catch (error) {
            console.error(`Error al ${action} prompt:`, error);
            if (window.showError) showError(`Error al ${action} prompt: ` + error.message);
        }
    }
    
    /**
     * Confirmar eliminación
     */
    confirmDelete(promptId, promptName) {
        if (confirm(`¿Estás seguro de que quieres eliminar el prompt "${promptName}"?\n\nEsta acción no se puede deshacer.`)) {
            this.deletePrompt(promptId);
        }
    }
    
    /**
     * Eliminar prompt
     */
    async deletePrompt(promptId) {
        try {
            const response = await fetch('admin_api.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action: 'deletePrompt',
                    id: promptId
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                if (window.showSuccess) showSuccess('Prompt eliminado correctamente');
                this.loadPrompts();
            } else {
                throw new Error(result.error || 'Error al eliminar prompt');
            }
        } catch (error) {
            console.error('Error eliminando prompt:', error);
            if (window.showError) showError('Error al eliminar prompt: ' + error.message);
        }
    }
    
    /**
     * Utilidades
     */
    getCategoryBadgeColor(category) {
        const colors = {
            'customer_service': '#17a2b8',
            'hotel_reviews': '#28a745',
            'booking_assistance': '#007bff',
            'content_generation': '#fd7e14',
            'data_analysis': '#6f42c1',
            'translation': '#20c997',
            'general': '#6c757d'
        };
        return colors[category] || '#6c757d';
    }
    
    getCategoryLabel(category) {
        const labels = {
            'customer_service': 'Atención al Cliente',
            'hotel_reviews': 'Reviews de Hoteles',
            'booking_assistance': 'Asistencia de Reservas',
            'content_generation': 'Generación de Contenido',
            'data_analysis': 'Análisis de Datos',
            'translation': 'Traducción',
            'general': 'General'
        };
        return labels[category] || category.toUpperCase();
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
        if (window.showInfo) showInfo('Actualizando lista de prompts...');
        await this.loadPrompts();
        if (window.showSuccess) showSuccess('Lista de prompts actualizada');
    }
}

// Crear instancia global del módulo
window.promptsManagerModule = new PromptsManagerModule();