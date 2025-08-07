<?php
/**
 * Test directo del módulo de prompts
 */
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Directo - Módulo Prompts</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f8f9fa;
            margin: 0;
            padding: 20px;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .btn {
            background: #667eea;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 6px;
            cursor: pointer;
            margin: 10px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn:hover {
            background: #5a6fd8;
        }
        
        .result {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 6px;
            margin-top: 20px;
            border-left: 4px solid #667eea;
        }
        
        .error {
            background: #f8d7da;
            color: #721c24;
            border-left-color: #dc3545;
        }
        
        .success {
            background: #d4edda;
            color: #155724;
            border-left-color: #28a745;
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.8);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        
        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 10px;
            width: 90%;
            max-width: 500px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        
        .form-group textarea {
            resize: vertical;
            height: 120px;
        }
        
        pre {
            background: #2d3748;
            color: #e2e8f0;
            padding: 15px;
            border-radius: 6px;
            overflow-x: auto;
            white-space: pre-wrap;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧪 Test Directo - Módulo Prompts</h1>
        <p>Prueba directa de funcionalidad sin dependencias del sistema principal</p>
        
        <div>
            <button class="btn" onclick="testGetStats()">
                <i class="fas fa-chart-bar"></i>
                Obtener Estadísticas
            </button>
            
            <button class="btn" onclick="testGetPrompts()">
                <i class="fas fa-list"></i>
                Listar Prompts
            </button>
            
            <button class="btn" onclick="showCreateModal()">
                <i class="fas fa-plus"></i>
                Crear Prompt
            </button>
            
            <button class="btn" onclick="testLibrary()">
                <i class="fas fa-book"></i>
                Ver Biblioteca
            </button>
        </div>
        
        <div id="result" class="result" style="display: none;"></div>
    </div>
    
    <!-- Modal para crear prompt -->
    <div id="createModal" class="modal">
        <div class="modal-content">
            <h3>Crear Nuevo Prompt</h3>
            <form id="promptForm">
                <div class="form-group">
                    <label>Nombre:</label>
                    <input type="text" id="promptName" required>
                </div>
                <div class="form-group">
                    <label>Categoría:</label>
                    <select id="promptCategory" required>
                        <option value="">Seleccionar...</option>
                        <option value="sentiment">Análisis de Sentimiento</option>
                        <option value="extraction">Extracción de Datos</option>
                        <option value="translation">Traducción</option>
                        <option value="classification">Clasificación</option>
                        <option value="summary">Resumen</option>
                        <option value="custom">Personalizado</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Contenido:</label>
                    <textarea id="promptContent" required></textarea>
                </div>
                <div>
                    <button type="submit" class="btn">
                        <i class="fas fa-save"></i>
                        Guardar
                    </button>
                    <button type="button" class="btn" onclick="closeModal()" style="background: #6c757d;">
                        Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function showResult(content, isError = false) {
            const result = document.getElementById('result');
            result.style.display = 'block';
            result.className = `result ${isError ? 'error' : 'success'}`;
            result.innerHTML = content;
        }
        
        async function makeRequest(action, data = {}) {
            try {
                const formData = new FormData();
                formData.append('action', action);
                
                Object.entries(data).forEach(([key, value]) => {
                    formData.append(key, typeof value === 'object' ? JSON.stringify(value) : value);
                });
                
                const response = await fetch('admin_api.php', {
                    method: 'POST',
                    body: formData
                });
                
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                
                const result = await response.json();
                return result;
                
            } catch (error) {
                console.error('Error:', error);
                throw error;
            }
        }
        
        async function testGetStats() {
            try {
                showResult('<i class="fas fa-spinner fa-spin"></i> Cargando estadísticas...');
                
                const result = await makeRequest('getPromptsStats');
                
                if (result.success) {
                    const stats = result.data;
                    showResult(`
                        <h4>✅ Estadísticas Obtenidas</h4>
                        <pre>${JSON.stringify(stats, null, 2)}</pre>
                    `);
                } else {
                    showResult(`<h4>❌ Error</h4><p>${result.error}</p>`, true);
                }
            } catch (error) {
                showResult(`<h4>❌ Error de Conexión</h4><p>${error.message}</p>`, true);
            }
        }
        
        async function testGetPrompts() {
            try {
                showResult('<i class="fas fa-spinner fa-spin"></i> Cargando prompts...');
                
                const result = await makeRequest('getPrompts', { page: 1, limit: 5 });
                
                if (result.success) {
                    showResult(`
                        <h4>✅ Prompts Cargados (${result.data.total} total)</h4>
                        <pre>${JSON.stringify(result.data, null, 2)}</pre>
                    `);
                } else {
                    showResult(`<h4>❌ Error</h4><p>${result.error}</p>`, true);
                }
            } catch (error) {
                showResult(`<h4>❌ Error de Conexión</h4><p>${error.message}</p>`, true);
            }
        }
        
        async function testLibrary() {
            try {
                showResult('<i class="fas fa-spinner fa-spin"></i> Cargando biblioteca...');
                
                const result = await makeRequest('getTemplatesLibrary');
                
                if (result.success) {
                    const templates = result.data.templates.map(t => ({ name: t.name, category: t.category }));
                    showResult(`
                        <h4>✅ Biblioteca Cargada (${result.data.metadata.total_templates} templates)</h4>
                        <pre>${JSON.stringify(templates, null, 2)}</pre>
                    `);
                } else {
                    showResult(`<h4>❌ Error</h4><p>${result.error}</p>`, true);
                }
            } catch (error) {
                showResult(`<h4>❌ Error de Conexión</h4><p>${error.message}</p>`, true);
            }
        }
        
        function showCreateModal() {
            document.getElementById('createModal').style.display = 'flex';
        }
        
        function closeModal() {
            document.getElementById('createModal').style.display = 'none';
        }
        
        document.getElementById('promptForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const name = document.getElementById('promptName').value;
            const category = document.getElementById('promptCategory').value;
            const content = document.getElementById('promptContent').value;
            
            try {
                showResult('<i class="fas fa-spinner fa-spin"></i> Creando prompt...');
                closeModal();
                
                const result = await makeRequest('createPrompt', {
                    name: name,
                    category: category,
                    content: content,
                    language: 'es',
                    status: 'draft',
                    description: 'Prompt creado desde test directo'
                });
                
                if (result.success) {
                    showResult(`
                        <h4>✅ Prompt Creado</h4>
                        <p><strong>ID:</strong> ${result.data.id}</p>
                        <p><strong>Nombre:</strong> ${result.data.name}</p>
                        <p><strong>Estado:</strong> ${result.data.status}</p>
                    `);
                    
                    // Limpiar formulario
                    document.getElementById('promptForm').reset();
                } else {
                    showResult(`<h4>❌ Error al Crear</h4><p>${result.error}</p>`, true);
                }
            } catch (error) {
                showResult(`<h4>❌ Error de Conexión</h4><p>${error.message}</p>`, true);
            }
        });
        
        // Cerrar modal con Escape
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeModal();
            }
        });
    </script>
</body>
</html>